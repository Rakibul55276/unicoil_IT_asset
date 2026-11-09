<?php

return [
    'version' => '20260804_000001_return_request_cancellation',
    'description' => 'Preserve return-request cancellation actor, time and reason',
    'up' => function (PDO $pdo): void {
        $columns = [
            'cancelled_by' => 'varchar(50) NULL AFTER processed_at',
            'cancelled_at' => 'datetime NULL AFTER cancelled_by',
            'cancellation_reason' => 'varchar(500) NULL AFTER cancelled_at',
        ];
        $exists = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema=DATABASE() AND table_name=:table_name
               AND column_name=:column_name'
        );
        foreach ($columns as $name => $definition) {
            $exists->execute([
                ':table_name' => 'asset_return_request',
                ':column_name' => $name,
            ]);
            if ((int) $exists->fetchColumn() === 0) {
                $pdo->exec(
                    'ALTER TABLE asset_return_request ADD COLUMN `' .
                    $name .
                    '` ' .
                    $definition
                );
            }
        }
    },
];
