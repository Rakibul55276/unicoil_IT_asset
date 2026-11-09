<?php

return [
    "version" => "20260813_000001_allow_repeated_receipt_references",
    "description" => "Allow SAP GRN numbers to be reused across asset receipts",
    "up" => function (PDO $pdo): void {
        $uniqueIndex = $pdo
            ->query("SHOW INDEX FROM asset_receipt WHERE Key_name = 'uq_receipt_no'")
            ->fetch();
        if ($uniqueIndex) {
            $pdo->exec("ALTER TABLE asset_receipt DROP INDEX uq_receipt_no");
        }

        $searchIndex = $pdo
            ->query("SHOW INDEX FROM asset_receipt WHERE Key_name = 'idx_receipt_no'")
            ->fetch();
        if (!$searchIndex) {
            $pdo->exec("ALTER TABLE asset_receipt ADD INDEX idx_receipt_no (receipt_no)");
        }
    },
];
