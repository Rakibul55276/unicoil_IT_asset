<?php

return [
    "version" => "20261108_000001_simplify_asset_conditions",
    "description" => "Use only New, Good, and Damaged asset conditions",
    "up" => function (PDO $pdo): void {
        // Normalize legacy values before narrowing the enum definitions.
        $pdo->exec("UPDATE assetdata SET asset_condition='GOOD' WHERE asset_condition='FAIR'");
        $pdo->exec("UPDATE assetdata SET asset_condition='DAMAGED' WHERE asset_condition='BEYOND_REPAIR'");
        $pdo->exec("UPDATE asset_issue SET condition_out='GOOD' WHERE condition_out='FAIR'");
        $pdo->exec("UPDATE asset_return SET condition_in='GOOD' WHERE condition_in='FAIR'");
        $pdo->exec("UPDATE asset_return SET condition_in='DAMAGED' WHERE condition_in='BEYOND_REPAIR'");

        $pdo->exec("ALTER TABLE assetdata MODIFY asset_condition ENUM('NEW','GOOD','DAMAGED') NOT NULL DEFAULT 'NEW'");
        $pdo->exec("ALTER TABLE asset_issue MODIFY condition_out ENUM('NEW','GOOD','DAMAGED') NOT NULL DEFAULT 'GOOD'");
        $pdo->exec("ALTER TABLE asset_return MODIFY condition_in ENUM('NEW','GOOD','DAMAGED') NOT NULL");
    },
];
