<?php

return [
    "version" => "20260809_000001_global_asset_condition",
    "description" => "Add one current condition per asset and normalize live status",
    "up" => function (PDO $pdo): void {
        $column = $pdo->query(
            "SELECT COUNT(*)
             FROM information_schema.columns
             WHERE table_schema=DATABASE()
               AND table_name='assetdata'
               AND column_name='asset_condition'",
        );
        if ((int) $column->fetchColumn() === 0) {
            $pdo->exec(
                "ALTER TABLE assetdata
                 ADD COLUMN asset_condition
                    ENUM('NEW','GOOD','FAIR','DAMAGED','BEYOND_REPAIR')
                    NOT NULL DEFAULT 'NEW'
                 AFTER assetStat",
            );
        }

        $pdo->exec(
            "INSERT INTO assetstat (assetStatus,Description)
             VALUES ('ASSIGNED','Assigned')
             ON DUPLICATE KEY UPDATE Description=VALUES(Description)",
        );

        // Preserve the latest known physical condition as the current value.
        // Issue and return tables remain immutable transaction snapshots.
        $pdo->exec(
            "UPDATE assetdata a
             SET a.asset_condition=COALESCE(
                 (SELECT open_issue.condition_out
                  FROM asset_issue open_issue
                  WHERE open_issue.assetNum=a.assetNum
                    AND open_issue.status='ISSUED'
                  ORDER BY open_issue.issue_date DESC,open_issue.issue_id DESC
                  LIMIT 1),
                 (SELECT r.condition_in
                  FROM asset_return r
                  JOIN asset_issue returned_issue ON returned_issue.issue_id=r.issue_id
                  WHERE returned_issue.assetNum=a.assetNum
                  ORDER BY r.return_date DESC,r.return_id DESC
                  LIMIT 1),
                 (SELECT receipt_item.condition_on_receipt
                  FROM asset_receipt_item receipt_item
                  WHERE receipt_item.assetNum=a.assetNum
                  ORDER BY receipt_item.receipt_item_id DESC
                  LIMIT 1),
                 'NEW'
             )",
        );

        $pdo->exec(
            "UPDATE assetdata
             SET assetStat='ASSIGNED'
             WHERE assetStat IN ('ISSUED_NEW','ISSUED_USED')",
        );

        // Reconcile the live status from the authoritative open issue. Assets
        // that are not currently issued return to stock; approved scrap is a
        // retained terminal lifecycle state.
        $pdo->exec(
            "UPDATE assetdata a
             SET a.assetStat=CASE
                 WHEN a.assetStat='SCRAPPED' THEN 'SCRAPPED'
                 WHEN EXISTS (
                     SELECT 1 FROM asset_issue open_issue
                     WHERE open_issue.assetNum=a.assetNum
                       AND open_issue.status='ISSUED'
                 ) THEN 'ASSIGNED'
                 ELSE 'IN_STOCK'
             END",
        );

        $index = $pdo->query(
            "SELECT COUNT(*)
             FROM information_schema.statistics
             WHERE table_schema=DATABASE()
               AND table_name='assetdata'
               AND index_name='idx_asset_status_condition_number'",
        );
        if ((int) $index->fetchColumn() === 0) {
            $pdo->exec(
                "ALTER TABLE assetdata
                 ADD INDEX idx_asset_status_condition_number
                    (assetStat,asset_condition,assetNum)",
            );
        }

        // These two display statuses encoded condition into status. They are
        // no longer referenced after the normalization above.
        $pdo->exec(
            "DELETE FROM assetstat
             WHERE assetStatus IN ('ISSUED_NEW','ISSUED_USED')",
        );
    },
];
