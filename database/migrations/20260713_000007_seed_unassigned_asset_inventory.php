<?php

return [
    'version' => '20260713_000007_seed_unassigned_asset_inventory',
    'description' => 'Seed realistic in-stock assets with catalog images and no assignments',
    'up' => function (PDO $pdo): void {
        $categories = [
            'LAPTOP' => 'Laptop',
            'DESKTOP' => 'Desktop Computer',
            'MONITOR' => 'Monitor',
            'PRINTER' => 'Printer / Scanner',
            'NETWORK' => 'Network Equipment',
        ];

        $categorySql = $pdo->prepare(
            'INSERT INTO assetkat (assetKat, Description) VALUES (:code, :description)
             ON DUPLICATE KEY UPDATE Description = VALUES(Description)'
        );
        foreach ($categories as $code => $description) {
            $categorySql->execute(['code' => $code, 'description' => $description]);
        }

        $pdo->exec(
            "INSERT INTO assetstat (assetStatus, Description) VALUES ('IN_STOCK', 'InStock')
             ON DUPLICATE KEY UPDATE Description = VALUES(Description)"
        );
        $pdo->exec(
            "INSERT INTO unit (unit, Description) VALUES ('Unit', 'Unit')
             ON DUPLICATE KEY UPDATE Description = VALUES(Description)"
        );

        $uploader = $pdo->query("SELECT username FROM users ORDER BY username='admin' DESC, username LIMIT 1")
            ->fetchColumn();
        if (!$uploader) {
            throw new RuntimeException('At least one application user is required before seeding assets.');
        }

        $location = $pdo->query('SELECT location FROM location ORDER BY location LIMIT 1')->fetchColumn();
        if ($location === false) {
            throw new RuntimeException('At least one stock location is required before seeding assets.');
        }

        $receiptNo = 'MIG-STARTER-20260713';
        $receipt = $pdo->prepare(
            'INSERT INTO asset_receipt
                (receipt_no, received_date, supplier_name, po_number, invoice_number, received_by, notes)
             VALUES
                (:receiptNo, :receivedDate, :supplier, :poNumber, :invoiceNumber, :receivedBy, :notes)
             ON DUPLICATE KEY UPDATE receipt_id = LAST_INSERT_ID(receipt_id)'
        );
        $receipt->execute([
            'receiptNo' => $receiptNo,
            'receivedDate' => '2026-07-13',
            'supplier' => 'Starter IT Inventory',
            'poNumber' => 'PO-DEMO-2026-001',
            'invoiceNumber' => 'INV-DEMO-2026-001',
            'receivedBy' => $uploader,
            'notes' => 'Migration-created unassigned inventory. Assign each asset manually when required.',
        ]);
        $receiptId = (int) $pdo->lastInsertId();

        $assets = [
            ['IT-LT-0001', 'LAPTOP', 'Dell Latitude 5440 business laptop, Core i5, 16 GB RAM, 512 GB SSD', 'DL5440-UQ01', 'Dell', 'Latitude 5440', 4250.00, '2029-07-12', 'catalog-laptop.png'],
            ['IT-LT-0002', 'LAPTOP', 'Dell Latitude 5440 business laptop, Core i5, 16 GB RAM, 512 GB SSD', 'DL5440-UQ02', 'Dell', 'Latitude 5440', 4250.00, '2029-07-12', 'catalog-laptop.png'],
            ['IT-LT-0003', 'LAPTOP', 'HP ProBook 450 G10 business laptop, Core i7, 16 GB RAM, 512 GB SSD', 'HP450G10-01', 'HP', 'ProBook 450 G10', 4590.00, '2029-07-12', 'catalog-laptop.png'],
            ['IT-LT-0004', 'LAPTOP', 'Lenovo ThinkPad E14 Gen 5 laptop, Core i5, 16 GB RAM, 512 GB SSD', 'TPE14G5-001', 'Lenovo', 'ThinkPad E14 Gen 5', 3995.00, '2029-07-12', 'catalog-laptop.png'],
            ['IT-LT-0005', 'LAPTOP', 'Lenovo ThinkPad E14 Gen 5 laptop, Core i5, 16 GB RAM, 512 GB SSD', 'TPE14G5-002', 'Lenovo', 'ThinkPad E14 Gen 5', 3995.00, '2029-07-12', 'catalog-laptop.png'],
            ['IT-DT-0001', 'DESKTOP', 'Dell OptiPlex 7010 tower workstation, Core i7, 16 GB RAM, 512 GB SSD', 'OPT7010-001', 'Dell', 'OptiPlex 7010', 3650.00, '2029-07-12', 'catalog-desktop.png'],
            ['IT-DT-0002', 'DESKTOP', 'Dell OptiPlex 7010 tower workstation, Core i7, 16 GB RAM, 512 GB SSD', 'OPT7010-002', 'Dell', 'OptiPlex 7010', 3650.00, '2029-07-12', 'catalog-desktop.png'],
            ['IT-DT-0003', 'DESKTOP', 'HP Pro Tower 400 G9 workstation, Core i5, 16 GB RAM, 512 GB SSD', 'HPT400G9-01', 'HP', 'Pro Tower 400 G9', 3290.00, '2029-07-12', 'catalog-desktop.png'],
            ['IT-MN-0001', 'MONITOR', '24-inch Full HD IPS business monitor with height-adjustable stand', 'MON24-FHD-01', 'Dell', 'P2422H', 780.00, '2029-07-12', 'catalog-monitor.png'],
            ['IT-MN-0002', 'MONITOR', '24-inch Full HD IPS business monitor with height-adjustable stand', 'MON24-FHD-02', 'Dell', 'P2422H', 780.00, '2029-07-12', 'catalog-monitor.png'],
            ['IT-MN-0003', 'MONITOR', '24-inch Full HD IPS business monitor with height-adjustable stand', 'MON24-FHD-03', 'Dell', 'P2422H', 780.00, '2029-07-12', 'catalog-monitor.png'],
            ['IT-MN-0004', 'MONITOR', '27-inch QHD IPS professional office monitor', 'MON27-QHD-01', 'HP', 'E27q G5', 1350.00, '2029-07-12', 'catalog-monitor.png'],
            ['IT-MN-0005', 'MONITOR', '27-inch QHD IPS professional office monitor', 'MON27-QHD-02', 'HP', 'E27q G5', 1350.00, '2029-07-12', 'catalog-monitor.png'],
            ['IT-PR-0001', 'PRINTER', 'Monochrome multifunction laser printer with network printing and scanning', 'PRMFP-001', 'HP', 'LaserJet Pro MFP 4103fdw', 1890.00, '2028-07-12', 'catalog-printer.png'],
            ['IT-PR-0002', 'PRINTER', 'Color multifunction laser printer with duplex printing and scanning', 'PRMFP-002', 'Canon', 'Color imageCLASS MF754Cdw', 2450.00, '2028-07-12', 'catalog-printer.png'],
            ['IT-PR-0003', 'PRINTER', 'Compact monochrome network laser printer for office workgroups', 'PRLASER-003', 'Brother', 'HL-L5210DN', 1280.00, '2028-07-12', 'catalog-printer.png'],
            ['IT-NW-0001', 'NETWORK', 'Managed 24-port Gigabit PoE+ network switch with SFP uplinks', 'SW24POE-001', 'Cisco', 'CBS350-24P-4G', 3290.00, '2029-07-12', 'catalog-network.png'],
            ['IT-NW-0002', 'NETWORK', 'Managed 24-port Gigabit PoE+ network switch with SFP uplinks', 'SW24POE-002', 'Cisco', 'CBS350-24P-4G', 3290.00, '2029-07-12', 'catalog-network.png'],
            ['IT-NW-0003', 'NETWORK', 'Enterprise indoor Wi-Fi 6 wireless access point', 'WIFI6AP-001', 'Aruba', 'AP-515', 2750.00, '2029-07-12', 'catalog-network.png'],
            ['IT-NW-0004', 'NETWORK', 'Enterprise indoor Wi-Fi 6 wireless access point', 'WIFI6AP-002', 'Aruba', 'AP-515', 2750.00, '2029-07-12', 'catalog-network.png'],
        ];

        $insertAsset = $pdo->prepare(
            'INSERT IGNORE INTO assetdata
                (assetNum, assetKat, Description, serial_number, manufacturer, model,
                 purchase_date, purchase_cost, warranty_expiry, receipt_id, empId, deptId,
                 createdBy, createdDate, quantity, unit, assetStat, location, assetImage, notes)
             VALUES
                (:assetNum, :assetKat, :description, :serialNumber, :manufacturer, :model,
                 :purchaseDate, :purchaseCost, :warrantyExpiry, :receiptId, NULL, :deptId,
                 :createdBy, :createdDate, 1, :unit, :assetStat, :location, :assetImage, :notes)'
        );
        $insertReceiptItem = $pdo->prepare(
            'INSERT IGNORE INTO asset_receipt_item
                (receipt_id, assetNum, line_no, condition_on_receipt, remarks)
             VALUES (:receiptId, :assetNum, :lineNo, :condition, :remarks)'
        );
        $mediaExists = $pdo->prepare(
            "SELECT COUNT(*) FROM asset_media
             WHERE assetNum=:assetNum AND media_stage='ENTRY' AND file_name=:fileName"
        );
        $insertMedia = $pdo->prepare(
            "INSERT INTO asset_media
                (assetNum, issue_id, return_id, media_stage, file_name, original_name,
                 mime_type, file_size, uploaded_by)
             VALUES (:assetNum, NULL, NULL, 'ENTRY', :fileName, :originalName,
                 'image/png', :fileSize, :uploadedBy)"
        );

        $imageDir = dirname(__DIR__, 2) . '/public/uploads/asset/';
        foreach ($assets as $index => $asset) {
            [$assetNum, $category, $description, $serial, $manufacturer, $model,
                $cost, $warranty, $image] = $asset;
            $imagePath = $imageDir . $image;
            if (!is_file($imagePath)) {
                throw new RuntimeException('Required catalog image is missing: ' . $image);
            }

            $insertAsset->execute([
                'assetNum' => $assetNum,
                'assetKat' => $category,
                'description' => $description,
                'serialNumber' => $serial,
                'manufacturer' => $manufacturer,
                'model' => $model,
                'purchaseDate' => '2026-07-13',
                'purchaseCost' => $cost,
                'warrantyExpiry' => $warranty,
                'receiptId' => $receiptId,
                'deptId' => '',
                'createdBy' => $uploader,
                'createdDate' => '2026-07-13',
                'unit' => 'Unit',
                'assetStat' => 'IN_STOCK',
                'location' => $location,
                'assetImage' => $image,
                'notes' => 'Starter inventory record. Unassigned and ready for manual issue.',
            ]);

            $insertReceiptItem->execute([
                'receiptId' => $receiptId,
                'assetNum' => $assetNum,
                'lineNo' => $index + 1,
                'condition' => 'NEW',
                'remarks' => 'In stock; no employee or department assignment.',
            ]);

            $mediaExists->execute(['assetNum' => $assetNum, 'fileName' => $image]);
            if ((int) $mediaExists->fetchColumn() === 0) {
                $insertMedia->execute([
                    'assetNum' => $assetNum,
                    'fileName' => $image,
                    'originalName' => $image,
                    'fileSize' => filesize($imagePath),
                    'uploadedBy' => $uploader,
                ]);
            }
        }
    },
];
