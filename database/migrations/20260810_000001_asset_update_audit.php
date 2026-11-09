<?php

return [
    "version" => "20260810_000001_asset_update_audit",
    "description" => "Retain an audit trail for every controlled in-stock asset edit",
    "up" => function (PDO $pdo): void {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS asset_update_audit (
                update_audit_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                assetNum VARCHAR(30) NOT NULL,
                changed_by VARCHAR(50) NOT NULL,
                change_reason VARCHAR(500) NOT NULL,
                changed_fields LONGTEXT NOT NULL,
                previous_snapshot LONGTEXT NOT NULL,
                new_snapshot LONGTEXT NOT NULL,
                change_summary TEXT NOT NULL,
                changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (update_audit_id),
                KEY idx_asset_update_audit_asset_date (assetNum, changed_at),
                KEY idx_asset_update_audit_changed_date (changed_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        );
    },
];
