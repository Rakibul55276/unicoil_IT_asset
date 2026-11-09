<?php

return [
    'version' => '20260713_000014_vendor_master',
    'description' => 'Add vendor master data and connect asset receipts to searchable suppliers',
    'up' => function (PDO $pdo): void {
        $pdo->exec("CREATE TABLE IF NOT EXISTS vendor (
            vendor_id int unsigned NOT NULL AUTO_INCREMENT,
            vendor_name varchar(150) NOT NULL,
            contact_person varchar(120) NULL,
            email varchar(190) NULL,
            phone varchar(50) NULL,
            address varchar(500) NULL,
            tax_number varchar(80) NULL,
            active tinyint(1) NOT NULL DEFAULT 1,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (vendor_id),
            UNIQUE KEY uq_vendor_name (vendor_name),
            KEY idx_vendor_active_name (active,vendor_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $exists=(int)$pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='asset_receipt' AND column_name='vendor_id'")->fetchColumn();
        if($exists===0){$pdo->exec('ALTER TABLE asset_receipt ADD COLUMN vendor_id int unsigned NULL AFTER supplier_name');$pdo->exec('CREATE INDEX idx_receipt_vendor ON asset_receipt(vendor_id)');$pdo->exec('ALTER TABLE asset_receipt ADD CONSTRAINT fk_receipt_vendor FOREIGN KEY(vendor_id) REFERENCES vendor(vendor_id) ON UPDATE CASCADE ON DELETE RESTRICT');}
        $pdo->exec("INSERT IGNORE INTO vendor(vendor_name) SELECT DISTINCT TRIM(supplier_name) FROM asset_receipt WHERE supplier_name IS NOT NULL AND TRIM(supplier_name)<>''");
        $pdo->exec('UPDATE asset_receipt r JOIN vendor v ON v.vendor_name=r.supplier_name SET r.vendor_id=v.vendor_id WHERE r.vendor_id IS NULL');
    },
];
