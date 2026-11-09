<?php

return [
    'version' => '20260712_000002_reset_assets_and_add_assignment_media',
    'description' => 'Clear old assets, make location optional, and add lifecycle image storage',
    'up' => function (PDO $pdo): void {
        // Requested clean start. Child lifecycle records must be removed first.
        $pdo->exec('DELETE FROM asset_return');
        $pdo->exec('DELETE FROM asset_issue');
        $pdo->exec('DELETE FROM asset_receipt_item');
        $pdo->exec('DELETE FROM assetdata');
        $pdo->exec('DELETE FROM asset_receipt');

        $nullable = $pdo->query(
            "SELECT IS_NULLABLE FROM information_schema.columns
             WHERE table_schema=DATABASE() AND table_name='assetdata' AND column_name='location'"
        )->fetchColumn();
        if ($nullable === 'NO') {
            $pdo->exec('ALTER TABLE assetdata MODIFY location varchar(10) NULL');
        }

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS asset_media (
                media_id bigint unsigned NOT NULL AUTO_INCREMENT,
                assetNum varchar(30) NOT NULL,
                issue_id bigint unsigned NULL,
                return_id bigint unsigned NULL,
                media_stage enum('ENTRY','ASSIGN','RETURN') NOT NULL,
                file_name varchar(255) NOT NULL,
                original_name varchar(255) NULL,
                mime_type varchar(100) NOT NULL,
                file_size int unsigned NOT NULL,
                uploaded_by varchar(50) NOT NULL,
                uploaded_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (media_id),
                KEY idx_asset_media_asset_stage (assetNum, media_stage),
                KEY idx_asset_media_issue (issue_id),
                KEY idx_asset_media_return (return_id),
                CONSTRAINT fk_asset_media_asset FOREIGN KEY (assetNum) REFERENCES assetdata(assetNum)
                    ON UPDATE CASCADE ON DELETE CASCADE,
                CONSTRAINT fk_asset_media_issue FOREIGN KEY (issue_id) REFERENCES asset_issue(issue_id)
                    ON UPDATE CASCADE ON DELETE CASCADE,
                CONSTRAINT fk_asset_media_return FOREIGN KEY (return_id) REFERENCES asset_return(return_id)
                    ON UPDATE CASCADE ON DELETE CASCADE,
                CONSTRAINT fk_asset_media_user FOREIGN KEY (uploaded_by) REFERENCES users(username)
                    ON UPDATE CASCADE ON DELETE RESTRICT,
                CONSTRAINT chk_asset_media_stage CHECK (
                    (media_stage='ENTRY' AND issue_id IS NULL AND return_id IS NULL) OR
                    (media_stage='ASSIGN' AND issue_id IS NOT NULL AND return_id IS NULL) OR
                    (media_stage='RETURN' AND return_id IS NOT NULL)
                )
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    },
];

