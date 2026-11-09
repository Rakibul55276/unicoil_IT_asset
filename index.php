<?php

// Production-safe defaults. Errors remain available to the server log but are
// never rendered into a response where paths, queries, or credentials can leak.
error_reporting(E_ALL);
ini_set("display_errors", "0");
ini_set("log_errors", "1");
ini_set("expose_php", "0");

$secureRequest =
    (!empty($_SERVER["HTTPS"]) && strtolower((string) $_SERVER["HTTPS"]) !== "off") ||
    strtolower((string) ($_SERVER["HTTP_X_FORWARDED_PROTO"] ?? "")) === "https";

ini_set("session.use_strict_mode", "1");
ini_set("session.use_only_cookies", "1");
ini_set("session.cookie_httponly", "1");
ini_set("session.cookie_samesite", "Lax");
ini_set("session.cookie_secure", $secureRequest ? "1" : "0");
$cookiePath = str_replace(
    "\\",
    "/",
    dirname((string) ($_SERVER["SCRIPT_NAME"] ?? "/index.php")),
);
$cookiePath = rtrim($cookiePath, "/") . "/";
if ($cookiePath === "//" || $cookiePath === "./") {
    $cookiePath = "/";
}
session_name("it_asset_management");
session_set_cookie_params([
    "lifetime" => 0,
    "path" => $cookiePath,
    "secure" => $secureRequest,
    "httponly" => true,
    "samesite" => "Lax",
]);

header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: same-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
header(
    "Content-Security-Policy: default-src 'self'; " .
        "base-uri 'self'; form-action 'self'; frame-ancestors 'self'; " .
        "object-src 'none'; img-src 'self' data:; font-src 'self' data:; " .
        "style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; " .
        "connect-src 'self'",
);
header("Cache-Control: no-store, private");
header_remove("X-Powered-By");
if ($secureRequest) {
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once "app/init.php";
new App();
