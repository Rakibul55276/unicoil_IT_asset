<?php

class AgentInventory_model
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function authenticateToken(string $rawToken)
    {
        $this->db->query(
            'SELECT token_id,token_name,expires_at
             FROM agent_api_token
             WHERE token_hash=:tokenHash AND active=1
               AND (expires_at IS NULL OR expires_at>UTC_TIMESTAMP())
             LIMIT 1',
        );
        $this->db->bind("tokenHash", hash("sha256", $rawToken));
        return $this->db->single();
    }

    public function findCompletedSync(string $requestUuid)
    {
        $this->db->query(
            'SELECT sync_id,device_id,disk_count,software_count,completed_at
             FROM agent_sync_log
             WHERE request_uuid=:requestUuid AND sync_status=\'SUCCESS\'
             LIMIT 1',
        );
        $this->db->bind("requestUuid", $requestUuid);
        return $this->db->single();
    }

    public function syncInventory(
        array $inventory,
        array $token,
        string $sourceIp,
        string $payloadHash,
    ): array {
        $device = $inventory["device"];
        $assetNumber = $this->resolveAssetNumber($device["assetNumber"], $device["serialNumber"]);
        $now = gmdate("Y-m-d H:i:s");

        $this->db->beginTransaction();
        try {
            $this->db->query(
                'INSERT INTO agent_device
                 (device_uuid,assetNum,asset_link_source,hostname,serial_number,manufacturer,model,os_name,os_version,
                  os_build,os_architecture,cpu_name,cpu_cores,memory_bytes,primary_ip,mac_address,
                  agent_version,last_boot_at,collected_at,inventory_hash,last_seen_at,last_inventory_at,active)
                 VALUES
                 (:deviceUuid,:assetNum,\'AUTO\',:hostname,:serialNumber,:manufacturer,:model,:osName,:osVersion,
                  :osBuild,:osArchitecture,:cpuName,:cpuCores,:memoryBytes,:primaryIp,:macAddress,
                  :agentVersion,:lastBootAt,:collectedAt,:inventoryHash,:lastSeenAt,:lastInventoryAt,1)
                 ON DUPLICATE KEY UPDATE
                  device_id=LAST_INSERT_ID(device_id),
                  assetNum=IF(asset_link_source=\'MANUAL\',assetNum,VALUES(assetNum)),hostname=VALUES(hostname),
                  serial_number=VALUES(serial_number),manufacturer=VALUES(manufacturer),model=VALUES(model),
                  os_name=VALUES(os_name),os_version=VALUES(os_version),os_build=VALUES(os_build),
                  os_architecture=VALUES(os_architecture),cpu_name=VALUES(cpu_name),cpu_cores=VALUES(cpu_cores),
                  memory_bytes=VALUES(memory_bytes),primary_ip=VALUES(primary_ip),mac_address=VALUES(mac_address),
                  agent_version=VALUES(agent_version),last_boot_at=VALUES(last_boot_at),
                  collected_at=VALUES(collected_at),inventory_hash=VALUES(inventory_hash),
                  last_seen_at=VALUES(last_seen_at),last_inventory_at=VALUES(last_inventory_at),active=1',
            );
            $params = [
                "deviceUuid" => $device["deviceId"],
                "assetNum" => $assetNumber,
                "hostname" => $device["hostname"],
                "serialNumber" => $device["serialNumber"],
                "manufacturer" => $device["manufacturer"],
                "model" => $device["model"],
                "osName" => $device["osName"],
                "osVersion" => $device["osVersion"],
                "osBuild" => $device["osBuild"],
                "osArchitecture" => $device["osArchitecture"],
                "cpuName" => $device["cpuName"],
                "cpuCores" => $device["cpuCores"],
                "memoryBytes" => $device["memoryBytes"],
                "primaryIp" => $device["primaryIp"],
                "macAddress" => $device["macAddress"],
                "agentVersion" => $inventory["agentVersion"],
                "lastBootAt" => $device["lastBootAt"],
                "collectedAt" => $inventory["collectedAt"],
                "inventoryHash" => $inventory["inventoryHash"],
                "lastSeenAt" => $now,
                "lastInventoryAt" => $now,
            ];
            foreach ($params as $name => $value) {
                $this->db->bind($name, $value);
            }
            $this->db->executeOrFail();
            $deviceId = (int) $this->db->lastInsertId();

            $this->replaceDisks($deviceId, $inventory["disks"]);
            $this->replaceSoftware($deviceId, $inventory["software"]);

            $this->db->query(
                'INSERT INTO agent_sync_log
                 (request_uuid,device_id,token_id,sync_status,source_ip,disk_count,software_count,
                  payload_hash,received_at,completed_at)
                 VALUES(:requestUuid,:deviceId,:tokenId,\'SUCCESS\',:sourceIp,:diskCount,:softwareCount,
                        :payloadHash,:receivedAt,:completedAt)',
            );
            $log = [
                "requestUuid" => $inventory["requestId"],
                "deviceId" => $deviceId,
                "tokenId" => (int) $token["token_id"],
                "sourceIp" => $sourceIp ?: null,
                "diskCount" => count($inventory["disks"]),
                "softwareCount" => count($inventory["software"]),
                "payloadHash" => $payloadHash,
                "receivedAt" => $now,
                "completedAt" => $now,
            ];
            foreach ($log as $name => $value) {
                $this->db->bind($name, $value);
            }
            $this->db->executeOrFail();

            $this->db->query(
                "UPDATE agent_api_token SET last_used_at=:usedAt WHERE token_id=:tokenId",
            );
            $this->db->bind("usedAt", $now);
            $this->db->bind("tokenId", (int) $token["token_id"]);
            $this->db->executeOrFail();

            $this->db->commit();
            return [
                "deviceId" => $deviceId,
                "assetNumber" => $assetNumber,
                "diskCount" => count($inventory["disks"]),
                "softwareCount" => count($inventory["software"]),
                "syncedAt" => gmdate("c"),
            ];
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    private function resolveAssetNumber(?string $requested, ?string $serialNumber): ?string
    {
        if ($requested !== null && $requested !== "") {
            $this->db->query("SELECT assetNum FROM assetdata WHERE assetNum=:assetNum LIMIT 1");
            $this->db->bind("assetNum", $requested);
            $row = $this->db->single();
            if ($row) {
                return $row["assetNum"];
            }
        }

        if ($serialNumber !== null && $serialNumber !== "") {
            $this->db->query(
                'SELECT assetNum FROM assetdata
                 WHERE serial_number=:serialNumber
                 ORDER BY assetNum LIMIT 1',
            );
            $this->db->bind("serialNumber", $serialNumber);
            $row = $this->db->single();
            if ($row) {
                return $row["assetNum"];
            }
        }

        return null;
    }

    private function replaceDisks(int $deviceId, array $disks): void
    {
        $this->db->query("DELETE FROM agent_disk WHERE device_id=:deviceId");
        $this->db->bind("deviceId", $deviceId);
        $this->db->executeOrFail();

        foreach ($disks as $disk) {
            $this->db->query(
                'INSERT INTO agent_disk
                 (device_id,device_key,volume_label,filesystem,size_bytes,free_bytes,health_status)
                 VALUES(:deviceId,:deviceKey,:volumeLabel,:filesystem,:sizeBytes,:freeBytes,:healthStatus)',
            );
            $values = ["deviceId" => $deviceId] + $disk;
            foreach ($values as $name => $value) {
                $this->db->bind($name, $value);
            }
            $this->db->executeOrFail();
        }
    }

    private function replaceSoftware(int $deviceId, array $software): void
    {
        $this->db->query("DELETE FROM agent_software WHERE device_id=:deviceId");
        $this->db->bind("deviceId", $deviceId);
        $this->db->executeOrFail();

        foreach ($software as $item) {
            $this->db->query(
                'INSERT INTO agent_software
                 (device_id,software_key,software_name,software_version,publisher,install_date,architecture)
                 VALUES(:deviceId,:softwareKey,:softwareName,:softwareVersion,:publisher,:installDate,:architecture)',
            );
            $values = ["deviceId" => $deviceId] + $item;
            foreach ($values as $name => $value) {
                $this->db->bind($name, $value);
            }
            $this->db->executeOrFail();
        }
    }
}
