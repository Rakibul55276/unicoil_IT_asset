<?php

class AgentApi extends Controller
{
    private const MAX_PAYLOAD_BYTES = 5242880;
    private const MAX_DISKS = 100;
    private const MAX_SOFTWARE = 5000;

    public function index()
    {
        $this->health();
    }

    public function health()
    {
        $this->prepareJsonResponse();
        $this->respond(200, [
            "ok" => true,
            "service" => "UNICOIL Windows Asset Agent API",
            "schemaVersion" => 1,
            "serverTime" => gmdate("c"),
        ]);
    }

    public function sync()
    {
        $this->prepareJsonResponse();

        if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
            header("Allow: POST");
            $this->respond(405, ["ok" => false, "error" => "POST is required."]);
        }

        $contentLength = (int) ($_SERVER["CONTENT_LENGTH"] ?? 0);
        if ($contentLength > self::MAX_PAYLOAD_BYTES) {
            $this->respond(413, ["ok" => false, "error" => "Inventory payload is too large."]);
        }

        $rawToken = $this->readToken();
        if ($rawToken === "") {
            $this->respond(401, ["ok" => false, "error" => "Agent token is required."]);
        }

        $model = $this->model("AgentInventory_model");
        try {
            $token = $model->authenticateToken($rawToken);
        } catch (Throwable $error) {
            error_log("Agent token lookup failed: " . $error->getMessage());
            $this->respond(503, ["ok" => false, "error" => "Agent service is not initialized."]);
        }
        if (!$token) {
            $this->respond(401, ["ok" => false, "error" => "Agent token is invalid or expired."]);
        }

        $rawBody = file_get_contents("php://input");
        if (!is_string($rawBody) || $rawBody === "" || strlen($rawBody) > self::MAX_PAYLOAD_BYTES) {
            $this->respond(400, [
                "ok" => false,
                "error" => "A JSON inventory payload is required.",
            ]);
        }
        $payload = json_decode($rawBody, true);
        if (!is_array($payload) || json_last_error() !== JSON_ERROR_NONE) {
            $this->respond(400, ["ok" => false, "error" => "Inventory payload is not valid JSON."]);
        }

        try {
            $inventory = $this->normalizeInventory($payload);
            $existing = $model->findCompletedSync($inventory["requestId"]);
            if ($existing) {
                $this->respond(200, [
                    "ok" => true,
                    "duplicate" => true,
                    "deviceId" => (int) $existing["device_id"],
                    "diskCount" => (int) $existing["disk_count"],
                    "softwareCount" => (int) $existing["software_count"],
                    "syncedAt" => $existing["completed_at"],
                ]);
            }

            $result = $model->syncInventory(
                $inventory,
                $token,
                $this->sourceIp(),
                hash("sha256", $rawBody),
            );
            $this->respond(200, ["ok" => true, "duplicate" => false] + $result);
        } catch (InvalidArgumentException $error) {
            $this->respond(422, ["ok" => false, "error" => $error->getMessage()]);
        } catch (Throwable $error) {
            error_log("Agent inventory sync failed: " . $error->getMessage());
            $this->respond(500, ["ok" => false, "error" => "Inventory synchronization failed."]);
        }
    }

    private function normalizeInventory(array $payload): array
    {
        if ((int) ($payload["schemaVersion"] ?? 0) !== 1) {
            throw new InvalidArgumentException("Unsupported inventory schema version.");
        }
        $requestId = $this->uuid($payload["requestId"] ?? null, "requestId");
        $agent = is_array($payload["agent"] ?? null) ? $payload["agent"] : [];
        $device = is_array($payload["device"] ?? null) ? $payload["device"] : [];
        $agentVersion = $this->text($agent["version"] ?? null, 30, "agent.version", true);

        $normalizedDevice = [
            "deviceId" => $this->uuid($device["deviceId"] ?? null, "device.deviceId"),
            "assetNumber" => $this->text($device["assetNumber"] ?? null, 30, "device.assetNumber"),
            "hostname" => $this->text($device["hostname"] ?? null, 255, "device.hostname", true),
            "serialNumber" => $this->text(
                $device["serialNumber"] ?? null,
                100,
                "device.serialNumber",
            ),
            "manufacturer" => $this->text(
                $device["manufacturer"] ?? null,
                100,
                "device.manufacturer",
            ),
            "model" => $this->text($device["model"] ?? null, 100, "device.model"),
            "osName" => $this->text($device["osName"] ?? null, 190, "device.osName"),
            "osVersion" => $this->text($device["osVersion"] ?? null, 50, "device.osVersion"),
            "osBuild" => $this->text($device["osBuild"] ?? null, 50, "device.osBuild"),
            "osArchitecture" => $this->text(
                $device["osArchitecture"] ?? null,
                30,
                "device.osArchitecture",
            ),
            "cpuName" => $this->text($device["cpuName"] ?? null, 255, "device.cpuName"),
            "cpuCores" => $this->unsignedInteger($device["cpuCores"] ?? null, 65535),
            "memoryBytes" => $this->unsignedInteger($device["memoryBytes"] ?? null, PHP_INT_MAX),
            "primaryIp" => $this->ip($device["primaryIp"] ?? null),
            "macAddress" => $this->mac($device["macAddress"] ?? null),
            "lastBootAt" => $this->dateTime($device["lastBootAt"] ?? null),
        ];

        $disksInput = is_array($payload["disks"] ?? null) ? $payload["disks"] : [];
        if (count($disksInput) > self::MAX_DISKS) {
            throw new InvalidArgumentException("Disk inventory exceeds the allowed limit.");
        }
        $disks = [];
        foreach ($disksInput as $disk) {
            if (!is_array($disk)) {
                continue;
            }
            $key = strtoupper(
                (string) $this->text($disk["deviceKey"] ?? null, 50, "disk.deviceKey", true),
            );
            $disks[$key] = [
                "deviceKey" => $key,
                "volumeLabel" => $this->text($disk["volumeLabel"] ?? null, 100, "disk.volumeLabel"),
                "filesystem" => $this->text($disk["filesystem"] ?? null, 30, "disk.filesystem"),
                "sizeBytes" => $this->unsignedInteger($disk["sizeBytes"] ?? null, PHP_INT_MAX),
                "freeBytes" => $this->unsignedInteger($disk["freeBytes"] ?? null, PHP_INT_MAX),
                "healthStatus" => $this->text(
                    $disk["healthStatus"] ?? null,
                    30,
                    "disk.healthStatus",
                ),
            ];
        }

        $softwareInput = is_array($payload["software"] ?? null) ? $payload["software"] : [];
        if (count($softwareInput) > self::MAX_SOFTWARE) {
            throw new InvalidArgumentException("Software inventory exceeds the allowed limit.");
        }
        $software = [];
        foreach ($softwareInput as $item) {
            if (!is_array($item)) {
                continue;
            }
            $name = $this->text($item["name"] ?? null, 255, "software.name");
            if ($name === null) {
                continue;
            }
            $version = $this->text($item["version"] ?? null, 100, "software.version");
            $publisher = $this->text($item["publisher"] ?? null, 190, "software.publisher");
            $architecture = $this->text($item["architecture"] ?? null, 20, "software.architecture");
            $key = hash(
                "sha256",
                strtolower($name . "|" . $version . "|" . $publisher . "|" . $architecture),
            );
            $software[$key] = [
                "softwareKey" => $key,
                "softwareName" => $name,
                "softwareVersion" => $version,
                "publisher" => $publisher,
                "installDate" => $this->date($item["installDate"] ?? null),
                "architecture" => $architecture,
            ];
        }

        $collectedAt = $this->dateTime($payload["collectedAt"] ?? null);
        if ($collectedAt === null) {
            throw new InvalidArgumentException("collectedAt is required.");
        }
        $hashSource = [$normalizedDevice, array_values($disks), array_values($software)];
        return [
            "requestId" => $requestId,
            "agentVersion" => $agentVersion,
            "device" => $normalizedDevice,
            "disks" => array_values($disks),
            "software" => array_values($software),
            "collectedAt" => $collectedAt,
            "inventoryHash" => hash("sha256", json_encode($hashSource, JSON_UNESCAPED_SLASHES)),
        ];
    }

    private function readToken(): string
    {
        $token = trim((string) ($_SERVER["HTTP_X_AGENT_TOKEN"] ?? ""));
        if ($token !== "") {
            return $token;
        }
        $authorization = trim(
            (string) ($_SERVER["HTTP_AUTHORIZATION"] ??
                ($_SERVER["REDIRECT_HTTP_AUTHORIZATION"] ?? "")),
        );
        return preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches) ? trim($matches[1]) : "";
    }

    private function text($value, int $max, string $field, bool $required = false): ?string
    {
        $value = trim((string) ($value ?? ""));
        if ($value === "") {
            if ($required) {
                throw new InvalidArgumentException($field . " is required.");
            }
            return null;
        }
        return substr($value, 0, $max);
    }

    private function uuid($value, string $field): string
    {
        $value = strtolower(trim((string) $value));
        if (
            !preg_match(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
                $value,
            )
        ) {
            throw new InvalidArgumentException($field . " must be a valid UUID.");
        }
        return $value;
    }

    private function unsignedInteger($value, int $max): ?int
    {
        if ($value === null || $value === "") {
            return null;
        }
        if (!is_numeric($value) || (float) $value < 0 || (float) $value > $max) {
            throw new InvalidArgumentException("Inventory contains an invalid numeric value.");
        }
        return (int) $value;
    }

    private function ip($value): ?string
    {
        $value = trim((string) ($value ?? ""));
        return $value !== "" && filter_var($value, FILTER_VALIDATE_IP) ? $value : null;
    }

    private function mac($value): ?string
    {
        $value = strtoupper(str_replace("-", ":", trim((string) ($value ?? ""))));
        return preg_match('/^[0-9A-F]{2}(?::[0-9A-F]{2}){5}$/', $value) ? $value : null;
    }

    private function dateTime($value): ?string
    {
        $value = trim((string) ($value ?? ""));
        if ($value === "") {
            return null;
        }
        try {
            return (new DateTimeImmutable($value))
                ->setTimezone(new DateTimeZone("UTC"))
                ->format("Y-m-d H:i:s");
        } catch (Throwable $error) {
            throw new InvalidArgumentException("Inventory contains an invalid date/time value.");
        }
    }

    private function date($value): ?string
    {
        $value = trim((string) ($value ?? ""));
        if ($value === "") {
            return null;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        return $value;
    }

    private function sourceIp(): string
    {
        $ip = trim((string) ($_SERVER["REMOTE_ADDR"] ?? ""));
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : "";
    }

    private function prepareJsonResponse(): void
    {
        ini_set("display_errors", "0");
        if (!defined("AGENT_ENABLED") || !AGENT_ENABLED) {
            $this->respond(503, ["ok" => false, "error" => "Agent module is disabled."]);
        }
        if (ob_get_level() === 0) {
            ob_start();
        }
        header("Content-Type: application/json; charset=utf-8");
        header("Cache-Control: no-store");
        header("X-Content-Type-Options: nosniff");
    }

    private function respond(int $status, array $payload): void
    {
        if (ob_get_level() > 0) {
            ob_clean();
        }
        http_response_code($status);
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit();
    }
}
