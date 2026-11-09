<?php

error_reporting(E_ALL);
ini_set("display_errors", "0");
ini_set("log_errors", "1");
ini_set("expose_php", "0");
header_remove("X-Powered-By");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: same-origin");

$config = require dirname(__DIR__) . "/config.php";
$agentEnabled = filter_var($config["agent_enabled"] ?? false, FILTER_VALIDATE_BOOLEAN);
define("AGENT_ENABLED", $agentEnabled);
define("AGENT_MODULE_PATH", __DIR__);
if (!$agentEnabled) {
    http_response_code(503);
    header("Content-Type: application/json; charset=utf-8");
    header("Cache-Control: no-store");
    exit(json_encode(["ok" => false, "error" => "Agent module is disabled."]));
}
foreach (["db_host", "db_name", "db_user", "db_pass"] as $required) {
    if (!array_key_exists($required, $config)) {
        http_response_code(500);
        header("Content-Type: application/json; charset=utf-8");
        exit(json_encode(["ok" => false, "error" => "Agent API configuration is incomplete."]));
    }
}

define("DB_HOST", (string) $config["db_host"]);
define("DB_NAME", (string) $config["db_name"]);
define("DB_USER", (string) $config["db_user"]);
define("DB_PASS", (string) $config["db_pass"]);

$host = strtolower((string) ($_SERVER["HTTP_HOST"] ?? "localhost"));
$host = preg_replace('/:[0-9]{1,5}$/', "", $host);
if (
    !in_array($host, ["localhost", "127.0.0.1", "::1"], true) &&
    (DB_NAME === "" || DB_USER === "" || strtolower(DB_USER) === "root" || DB_PASS === "")
) {
    http_response_code(500);
    header("Content-Type: application/json; charset=utf-8");
    exit(json_encode(["ok" => false, "error" => "Service configuration is incomplete."]));
}
unset($config);

require_once dirname(__DIR__) . "/app/core/Database.php";
require_once dirname(__DIR__) . "/app/core/Controller.php";
require_once __DIR__ . "/src/core/DashboardAuth.php";
require_once __DIR__ . "/src/core/DashboardView.php";
require_once __DIR__ . "/src/models/AgentDashboardRepository.php";
require_once __DIR__ . "/src/controllers/AgentApi.php";
