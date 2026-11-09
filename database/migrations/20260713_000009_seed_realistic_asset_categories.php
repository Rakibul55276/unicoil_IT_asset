<?php

return [
    'version' => '20260713_000009_seed_realistic_asset_categories',
    'description' => 'Add practical IT asset categories for daily inventory use',
    'up' => function (PDO $pdo): void {
        $categories = [
            'SERVER'     => 'Server',
            'STORAGE'    => 'Storage Device / NAS',
            'UPS'        => 'UPS / Power Backup',
            'MOBILE'     => 'Mobile Phone',
            'TABLET'     => 'Tablet',
            'PROJECTOR'  => 'Projector',
            'SCANNER'    => 'Document Scanner',
            'CAMERA'     => 'Camera / CCTV',
            'PHONE'      => 'IP Phone',
            'HEADSET'    => 'Headset',
            'KEYMOUSE'   => 'Keyboard / Mouse',
            'DOCK'       => 'Docking Station',
            'ADAPTER'    => 'Adapter / Charger',
            'DRIVE'      => 'External Drive',
            'RACK'       => 'Server Rack / Cabinet',
            'FIREWALL'   => 'Firewall / Security Appliance',
            'BIOMETRIC'  => 'Biometric Device',
            'ACCESS'     => 'Access Control Device',
            'MEETING'    => 'Meeting Room Equipment',
            'OTHER'      => 'Other IT Equipment',
        ];

        $statement = $pdo->prepare(
            'INSERT INTO assetkat (assetKat, Description, active)
             VALUES (:code, :description, 1)
             ON DUPLICATE KEY UPDATE Description=VALUES(Description), active=1'
        );

        foreach ($categories as $code => $description) {
            $statement->execute(['code' => $code, 'description' => $description]);
        }
    },
];
