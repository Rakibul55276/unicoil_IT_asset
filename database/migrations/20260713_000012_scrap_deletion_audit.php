<?php

return [
    'version' => '20260713_000012_scrap_deletion_audit',
    'description' => 'Preserve an audit snapshot before permanently deleting scrapped assets',
    'up' => function (PDO $pdo): void {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS asset_deletion_audit (
                deletion_id bigint unsigned NOT NULL AUTO_INCREMENT,
                asset_num varchar(30) NOT NULL,
                asset_description varchar(255) NOT NULL,
                category_name varchar(100) NULL,
                serial_number varchar(100) NULL,
                deleted_by varchar(50) NOT NULL,
                deleted_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                deletion_reason varchar(500) NOT NULL,
                asset_snapshot longtext NOT NULL,
                transaction_snapshot longtext NOT NULL,
                media_snapshot longtext NOT NULL,
                PRIMARY KEY (deletion_id),
                KEY idx_deleted_asset_date (deleted_at, asset_num),
                KEY fk_deleted_asset_user (deleted_by),
                CONSTRAINT fk_deleted_asset_user FOREIGN KEY (deleted_by)
                    REFERENCES users(username) ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    },
];
