<?php

return [
    'version' => '20260712_000001_baseline',
    'description' => 'Register the imported IT asset management schema as the migration baseline',
    'up' => function (PDO $pdo): void {
        $requiredTables = [
            'users', 'department', 'employee', 'empdept', 'assetkat',
            'assetstat', 'unit', 'location', 'asset_receipt', 'assetdata',
            'asset_receipt_item', 'asset_issue', 'asset_return',
        ];

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = :table_name'
        );

        $missing = [];
        foreach ($requiredTables as $table) {
            $stmt->execute([':table_name' => $table]);
            if ((int) $stmt->fetchColumn() === 0) {
                $missing[] = $table;
            }
        }

        if ($missing) {
            throw new RuntimeException(
                'Baseline cannot be registered. Import it_asset_management_full.sql first. Missing: '
                . implode(', ', $missing)
            );
        }
    },
];

