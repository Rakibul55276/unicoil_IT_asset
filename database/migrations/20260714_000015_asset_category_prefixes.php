<?php

return [
    'version' => '20260714_000015_asset_category_prefixes',
    'description' => 'Add unique category prefixes for automatic asset numbering',
    'up' => function (PDO $pdo): void {
        $column = $pdo->query(
            "SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema=DATABASE() AND table_name='assetkat' AND column_name='asset_prefix'"
        )->fetchColumn();

        if (!(int)$column) {
            $pdo->exec("ALTER TABLE assetkat ADD COLUMN asset_prefix varchar(6) NULL AFTER Description");
        }

        $preferred = [
            'LAPTOP'=>'LT','DESKTOP'=>'DT','MONITOR'=>'MN','PRINTER'=>'PR','NETWORK'=>'NW',
            'SERVER'=>'SV','STORAGE'=>'ST','UPS'=>'UPS','MOBILE'=>'MB','TABLET'=>'TB',
            'PROJECTOR'=>'PJ','SCANNER'=>'SC','CAMERA'=>'CM','PHONE'=>'PH','HEADSET'=>'HS',
            'KEYMOUSE'=>'KM','DOCK'=>'DK','ADAPTER'=>'AD','DRIVE'=>'DR','RACK'=>'RK',
            'FIREWALL'=>'FW','BIOMETRIC'=>'BM','ACCESS'=>'AC','MEETING'=>'MR','OTHER'=>'OT',
        ];

        $rows = $pdo->query('SELECT assetKat,Description,asset_prefix FROM assetkat')->fetchAll(PDO::FETCH_ASSOC);
        usort($rows, static function (array $left, array $right) use ($preferred): int {
            $leftKnown = isset($preferred[strtoupper($left['assetKat'])]);
            $rightKnown = isset($preferred[strtoupper($right['assetKat'])]);
            return $leftKnown === $rightKnown
                ? strcmp((string)$left['assetKat'], (string)$right['assetKat'])
                : ($leftKnown ? -1 : 1);
        });

        $used = [];
        $update = $pdo->prepare('UPDATE assetkat SET asset_prefix=:prefix WHERE assetKat=:category');
        foreach ($rows as $row) {
            $category = strtoupper(trim((string)$row['assetKat']));
            $current = strtoupper(trim((string)($row['asset_prefix'] ?? '')));
            $base = $current !== '' ? $current : ($preferred[$category] ?? deriveAssetPrefix($row['Description']));
            $base = substr(preg_replace('/[^A-Z0-9]/', '', $base), 0, 6);
            if (strlen($base) < 2) $base = str_pad($base, 2, 'X');

            $prefix = $base;
            for ($suffix = 2; isset($used[$prefix]); $suffix++) {
                $suffixText = (string)$suffix;
                $prefix = substr($base, 0, 6 - strlen($suffixText)) . $suffixText;
            }
            $used[$prefix] = true;
            $update->execute(['prefix'=>$prefix, 'category'=>$row['assetKat']]);
        }

        $pdo->exec('ALTER TABLE assetkat MODIFY asset_prefix varchar(6) NOT NULL');
        $index = $pdo->query(
            "SELECT COUNT(*) FROM information_schema.statistics
             WHERE table_schema=DATABASE() AND table_name='assetkat' AND index_name='uq_assetkat_prefix'"
        )->fetchColumn();
        if (!(int)$index) {
            $pdo->exec('ALTER TABLE assetkat ADD UNIQUE KEY uq_assetkat_prefix (asset_prefix)');
        }
    },
];

function deriveAssetPrefix(string $description): string
{
    $description = strtoupper(preg_replace('/^\s*IT\s+ASSET\s*[-:]?\s*/i', '', $description));
    $words = preg_split('/[^A-Z0-9]+/', $description, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    if (count($words) >= 2) return substr($words[0], 0, 1) . substr($words[1], 0, 1);
    $word = $words[0] ?? 'ASSET';
    return substr($word, 0, 2);
}
