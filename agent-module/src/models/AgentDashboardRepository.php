<?php

class AgentDashboardRepository
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function summary(): array
    {
        // These thresholds are shared by the summary cards and device filters.
        $this->db->query("
            SELECT
                COUNT(*) AS total,
                COALESCE(SUM(last_seen_at >= UTC_TIMESTAMP() - INTERVAL 7 HOUR), 0) AS current_count,
                COALESCE(SUM(
                    last_seen_at < UTC_TIMESTAMP() - INTERVAL 7 HOUR
                    AND last_seen_at >= UTC_TIMESTAMP() - INTERVAL 24 HOUR
                ), 0) AS stale_count,
                COALESCE(SUM(last_seen_at < UTC_TIMESTAMP() - INTERVAL 24 HOUR), 0) AS offline_count,
                COALESCE(SUM(assetNum IS NULL OR assetNum = ''), 0) AS unlinked_count
            FROM agent_device
            WHERE active = 1
        ");

        return $this->db->single() ?: [];
    }

    public function devices(string $search, string $status, string $link): array
    {
        $sql = "
            SELECT
                device_id,
                assetNum,
                hostname,
                serial_number,
                manufacturer,
                model,
                os_name,
                os_version,
                cpu_name,
                cpu_cores,
                memory_bytes,
                primary_ip,
                agent_version,
                last_seen_at,
                CASE
                    WHEN last_seen_at >= UTC_TIMESTAMP() - INTERVAL 7 HOUR THEN 'CURRENT'
                    WHEN last_seen_at >= UTC_TIMESTAMP() - INTERVAL 24 HOUR THEN 'STALE'
                    ELSE 'OFFLINE'
                END AS agent_status
            FROM agent_device
            WHERE active = 1
        ";
        $bindings = [];

        if ($search !== "") {
            $sql .= " AND (
                hostname LIKE :q1
                OR serial_number LIKE :q2
                OR assetNum LIKE :q3
                OR manufacturer LIKE :q4
                OR model LIKE :q5
                OR os_name LIKE :q6
            )";
            $v = "%" . $search . "%";
            foreach (["q1", "q2", "q3", "q4", "q5", "q6"] as $n) {
                $bindings[$n] = $v;
            }
        }

        if ($status === "CURRENT") {
            $sql .= " AND last_seen_at >= UTC_TIMESTAMP() - INTERVAL 7 HOUR";
        } elseif ($status === "STALE") {
            $sql .= "
                AND last_seen_at < UTC_TIMESTAMP() - INTERVAL 7 HOUR
                AND last_seen_at >= UTC_TIMESTAMP() - INTERVAL 24 HOUR
            ";
        } elseif ($status === "OFFLINE") {
            $sql .= " AND last_seen_at < UTC_TIMESTAMP() - INTERVAL 24 HOUR";
        }

        if ($link === "LINKED") {
            $sql .= " AND assetNum IS NOT NULL AND assetNum <> ''";
        } elseif ($link === "UNLINKED") {
            $sql .= " AND (assetNum IS NULL OR assetNum = '')";
        }

        $sql .= " ORDER BY last_seen_at DESC, hostname LIMIT 500";
        $this->db->query($sql);

        foreach ($bindings as $n => $v) {
            $this->db->bind($n, $v);
        }

        return $this->db->resultSet();
    }

    public function device(int $id)
    {
        $this->db->query("
            SELECT
                *,
                CASE
                    WHEN last_seen_at >= UTC_TIMESTAMP() - INTERVAL 7 HOUR THEN 'CURRENT'
                    WHEN last_seen_at >= UTC_TIMESTAMP() - INTERVAL 24 HOUR THEN 'STALE'
                    ELSE 'OFFLINE'
                END AS agent_status
            FROM agent_device
            WHERE device_id = :id
            LIMIT 1
        ");
        $this->db->bind("id", $id);
        $d = $this->db->single();

        if ($d && !empty($d["assetNum"])) {
            $this->db->query(
                "SELECT Description,serial_number,assetStat FROM assetdata WHERE assetNum=:asset LIMIT 1",
            );
            $this->db->bind("asset", $d["assetNum"]);
            $d["linked_asset"] = $this->db->single();
        } elseif ($d) {
            $d["linked_asset"] = null;
        }

        return $d;
    }

    public function disks(int $id): array
    {
        $this->db->query("SELECT * FROM agent_disk WHERE device_id=:id ORDER BY device_key");
        $this->db->bind("id", $id);
        return $this->db->resultSet();
    }
    public function software(int $id, string $search = ""): array
    {
        $sql =
            "SELECT software_name,software_version,publisher,install_date,architecture FROM agent_software WHERE device_id=:id";
        if ($search !== "") {
            $sql .=
                " AND (software_name LIKE :q1 OR publisher LIKE :q2 OR software_version LIKE :q3)";
        }
        $sql .= " ORDER BY software_name,software_version LIMIT 5000";
        $this->db->query($sql);
        $this->db->bind("id", $id);
        if ($search !== "") {
            $v = "%" . $search . "%";
            $this->db->bind("q1", $v);
            $this->db->bind("q2", $v);
            $this->db->bind("q3", $v);
        }
        return $this->db->resultSet();
    }
    public function syncHistory(int $id): array
    {
        $this->db->query(
            "SELECT sync_status,source_ip,disk_count,software_count,received_at,completed_at FROM agent_sync_log WHERE device_id=:id ORDER BY received_at DESC LIMIT 25",
        );
        $this->db->bind("id", $id);
        return $this->db->resultSet();
    }
    public function assets(): array
    {
        $this->db->query(
            "SELECT assetNum,Description,serial_number FROM assetdata ORDER BY assetNum LIMIT 2000",
        );
        return $this->db->resultSet();
    }
    public function linkAsset(int $id, ?string $asset): void
    {
        if ($asset !== null) {
            $this->db->query("SELECT assetNum FROM assetdata WHERE assetNum=:asset LIMIT 1");
            $this->db->bind("asset", $asset);
            if (!$this->db->single()) {
                throw new InvalidArgumentException("The selected asset number does not exist.");
            }
        }
        $this->db->query(
            "UPDATE agent_device SET assetNum=:asset,asset_link_source=CASE WHEN :source IS NULL THEN 'AUTO' ELSE 'MANUAL' END WHERE device_id=:id",
        );
        $this->db->bind("asset", $asset);
        $this->db->bind("source", $asset);
        $this->db->bind("id", $id);
        $this->db->executeOrFail();
        if ($this->db->rowCount() === 0 && !$this->device($id)) {
            throw new InvalidArgumentException("Agent device was not found.");
        }
    }
}
