<?php

if (PHP_SAPI !== "cli") {
    http_response_code(404);
    exit();
}

$config = require dirname(__DIR__) . "/config.php";
$pdo = new PDO(
    "mysql:host={$config["db_host"]};dbname={$config["db_name"]};charset=utf8mb4",
    $config["db_user"],
    $config["db_pass"],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
    ],
);
$sql = file_get_contents(__DIR__ . "/large_scale_performance_migration.sql");
if ($sql === false) {
    throw new RuntimeException("Migration SQL could not be read.");
}
$pdo->exec($sql);
echo "Large-scale database migration completed." . PHP_EOL;

