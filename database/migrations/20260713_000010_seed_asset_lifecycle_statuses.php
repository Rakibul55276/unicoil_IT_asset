<?php

return [
    'version' => '20260713_000010_seed_asset_lifecycle_statuses',
    'description' => 'Add statuses required by asset assignment and return workflows',
    'up' => function (PDO $pdo): void {
        $statuses = [
            'IN_STOCK'    => 'InStock',
            'ISSUED_NEW'  => 'New',
            'ISSUED_USED' => 'Used',
            'SCRAPPED'    => 'Disposed',
        ];

        $statement = $pdo->prepare(
            'INSERT INTO assetstat (assetStatus, Description)
             VALUES (:status, :description)
             ON DUPLICATE KEY UPDATE Description=VALUES(Description)'
        );

        foreach ($statuses as $status => $description) {
            $statement->execute(['status' => $status, 'description' => $description]);
        }
    },
];
