<?php

return [
    'version' => '20260713_000013_scrapped_asset_archive_image',
    'description' => 'Preserve a representative image with each scrapped asset archive record',
    'up' => function (PDO $pdo): void {
        $exists=(int)$pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='asset_deletion_audit' AND column_name='archive_image'")->fetchColumn();
        if($exists===0)$pdo->exec('ALTER TABLE asset_deletion_audit ADD COLUMN archive_image varchar(255) NULL AFTER serial_number');
    },
];
