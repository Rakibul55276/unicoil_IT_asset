<?php

$rootConfig = require dirname(__DIR__, 2) . "/config.php";
$saasEnabled = filter_var(
    $rootConfig["saas_voucher_enabled"] ?? false,
    FILTER_VALIDATE_BOOLEAN,
);
$workflowUiEnabled = filter_var(
    $rootConfig["workflow_ui_enabled"] ?? true,
    FILTER_VALIDATE_BOOLEAN,
);
$issuerUiEnabled = filter_var(
    $rootConfig["issuer_ui_enabled"] ?? false,
    FILTER_VALIDATE_BOOLEAN,
);
$scrapApprovalEnabled = filter_var(
    $rootConfig["scrap_approval_enabled"] ?? true,
    FILTER_VALIDATE_BOOLEAN,
);
$agentEnabled = filter_var(
    $rootConfig["agent_enabled"] ?? false,
    FILTER_VALIDATE_BOOLEAN,
);
$bulkLabelPrintEnabled = filter_var(
    $rootConfig["bulk_label_print_enabled"] ?? false,
    FILTER_VALIDATE_BOOLEAN,
);
$accessoryInventoryEnabled = filter_var(
    $rootConfig["accessory_inventory_enabled"] ?? false,
    FILTER_VALIDATE_BOOLEAN,
);
$appTimezone = trim((string) ($rootConfig["app_timezone"] ?? "Asia/Riyadh"));
if (!in_array($appTimezone, timezone_identifiers_list(), true)) {
    $appTimezone = "Asia/Riyadh";
}
date_default_timezone_set($appTimezone);
define("SAAS_VOUCHER_ENABLED", $saasEnabled);
define("WORKFLOW_UI_ENABLED", $workflowUiEnabled);
define("ISSUER_UI_ENABLED", $issuerUiEnabled);
define("SCRAP_APPROVAL_ENABLED", $scrapApprovalEnabled);
define("AGENT_ENABLED", $agentEnabled);
define("AGENT_MODULE_PATH", dirname(__DIR__, 2) . "/agent-module");
define("BULK_LABEL_PRINT_ENABLED", $bulkLabelPrintEnabled);
define("ACCESSORY_INVENTORY_ENABLED", $accessoryInventoryEnabled);
define("APP_TIMEZONE", $appTimezone);
define(
    "SAAS_SETUP_KEY",
    trim((string) ($rootConfig["saas_setup_key"] ?? "")),
);

if ($saasEnabled) {
    SaasTenantContext::boot($rootConfig);
}

$databaseConfig = SaasTenantContext::databaseConfig() ?: [
    "db_host" => $rootConfig["db_host"],
    "db_name" => $rootConfig["db_name"],
    "db_user" => $rootConfig["db_user"],
    "db_pass" => $rootConfig["db_pass"],
];
$https =
    (!empty($_SERVER["HTTPS"]) && strtolower((string) $_SERVER["HTTPS"]) !== "off") ||
    strtolower((string) ($_SERVER["HTTP_X_FORWARDED_PROTO"] ?? "")) === "https";
$host = strtolower(trim((string) ($_SERVER["HTTP_HOST"] ?? "localhost")));
if (!preg_match('/^[a-z0-9.-]+(?::[0-9]{1,5})?$/i', $host)) {
    $host = "localhost";
}
$scriptName = str_replace("\\", "/", $_SERVER["SCRIPT_NAME"] ?? "/index.php");
$basePath = rtrim(str_replace("\\", "/", dirname($scriptName)), "/");
if ($basePath === "." || $basePath === "/") {
    $basePath = "";
}
$configuredAppUrl = rtrim(trim((string) ($rootConfig["app_url"] ?? "")), "/");
if (
    $configuredAppUrl !== "" &&
    filter_var($configuredAppUrl, FILTER_VALIDATE_URL) === false
) {
    throw new RuntimeException("The configured application URL is invalid.");
}
$baseUrl =
    $configuredAppUrl !== ""
        ? $configuredAppUrl
        : ($https ? "https" : "http") . "://" . $host . $basePath;

$localHost = preg_replace('/:[0-9]{1,5}$/', "", $host);
$isLocalRequest = in_array($localHost, ["localhost", "127.0.0.1", "::1"], true);
if (
    !$isLocalRequest &&
    (trim((string) ($databaseConfig["db_name"] ?? "")) === "" ||
        trim((string) ($databaseConfig["db_user"] ?? "")) === "" ||
        strtolower(trim((string) ($databaseConfig["db_user"] ?? ""))) === "root" ||
        (string) ($databaseConfig["db_pass"] ?? "") === "")
) {
    throw new RuntimeException("Production database credentials are not configured.");
}
if (
    $saasEnabled &&
    (strlen(trim((string) ($rootConfig["saas_encryption_key"] ?? ""))) < 32 ||
        strlen(trim((string) ($rootConfig["saas_setup_key"] ?? ""))) < 32)
) {
    throw new RuntimeException("SaaS security keys are not configured.");
}

define("BASEURL", $baseUrl);
define("DB_HOST", (string) $databaseConfig["db_host"]);
define("DB_USER", (string) $databaseConfig["db_user"]);
define("DB_PASS", (string) $databaseConfig["db_pass"]);
define("DB_NAME", (string) $databaseConfig["db_name"]);
unset(
    $databaseConfig,
    $saasEnabled,
    $workflowUiEnabled,
    $issuerUiEnabled,
    $scrapApprovalEnabled,
    $agentEnabled,
    $bulkLabelPrintEnabled,
    $accessoryInventoryEnabled,
    $appTimezone,
    $rootConfig,
    $https,
    $host,
    $scriptName,
    $basePath,
    $baseUrl,
    $configuredAppUrl,
    $localHost,
    $isLocalRequest,
);

// Optional company mail delivery through the existing Google Apps Script transport.
$mailSecretFile = __DIR__ . "/mail_secret.php";
$mailSecrets = is_file($mailSecretFile) ? require $mailSecretFile : [];
$mailSecrets = is_array($mailSecrets) ? $mailSecrets : [];
define("MAIL_FROM", "unicoil43@gmail.com");
define("MAIL_FROM_NAME", "UNICOIL IT Asset Management");
define("MAIL_TRANSPORT", "google_apps_script");
define("GOOGLE_MAIL_WEB_APP_URL", trim((string) ($mailSecrets["google_web_app_url"] ?? "")));
define("GOOGLE_MAIL_WEB_APP_SECRET", trim((string) ($mailSecrets["google_web_app_secret"] ?? "")));
unset($mailSecretFile, $mailSecrets);
?>
