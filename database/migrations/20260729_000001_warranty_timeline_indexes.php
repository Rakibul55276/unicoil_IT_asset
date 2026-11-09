<?php

return [
    "version" => "20260729_000001_warranty_timeline_indexes",
    "description" => "Index purchase and warranty dates for scalable lifecycle alerts",
    "up" => function (PDO $pdo): void {
        $indexStatement = $pdo->prepare(
            "SELECT COUNT(*)
             FROM information_schema.statistics
             WHERE table_schema=DATABASE()
               AND table_name='assetdata'
               AND index_name=:index_name",
        );

        $indexes = [
            "idx_asset_warranty_status" =>
                "ALTER TABLE assetdata
                 ADD INDEX idx_asset_warranty_status (warranty_expiry, assetStat, assetNum)",
            "idx_asset_purchase_warranty" =>
                "ALTER TABLE assetdata
                 ADD INDEX idx_asset_purchase_warranty (purchase_date, warranty_expiry, assetNum)",
        ];

        foreach ($indexes as $indexName => $sql) {
            $indexStatement->execute([":index_name" => $indexName]);
            if ((int) $indexStatement->fetchColumn() === 0) {
                $pdo->exec($sql);
            }
        }
    },
];
