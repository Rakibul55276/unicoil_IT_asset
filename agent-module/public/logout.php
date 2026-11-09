<?php
require dirname(__DIR__) . "/bootstrap.php";
DashboardAuth::requireLogin();
if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
    http_response_code(405);
    exit("POST required");
}
try {
    DashboardAuth::validateCsrf($_POST["csrf"] ?? null);
} catch (DashboardCsrfException $e) {
    http_response_code(400);
    exit("The request expired.");
}
DashboardAuth::logout();
header("Location: login.php");
exit();
