-- UNICOIL starter category and asset master data.
-- Select the application database in phpMyAdmin before importing.
-- This file is idempotent: importing it again updates the same master records.

SET NAMES utf8mb4;
START TRANSACTION;

INSERT INTO `assetkat` (`assetKat`, `Description`, `asset_prefix`, `active`) VALUES
('DESKTOP', 'Desktop Computer', 'DT', 1),
('LAPTOP', 'Laptop', 'LT', 1),
('MONITOR', 'Monitor', 'MN', 1),
('PRINTER', 'Printer / Scanner', 'PR', 1),
('SERVER', 'Server', 'SV', 1),
('NETWORK', 'Network Equipment', 'NW', 1),
('UPS', 'UPS / Power Backup', 'UPS', 1),
('PHONE', 'IP Phone', 'PH', 1),
('MOBILE', 'Mobile Phone', 'MB', 1),
('TABLET', 'Tablet', 'TB', 1),
('PROJECTOR', 'Projector', 'PJ', 1),
('STORAGE', 'Storage Device / NAS', 'ST', 1),
('CAMERA', 'Camera / CCTV', 'CM', 1),
('ACCESS', 'Access Control Device', 'AC', 1),
('OTHER', 'Other IT Equipment', 'OT', 1)
ON DUPLICATE KEY UPDATE
  `Description` = VALUES(`Description`),
  `asset_prefix` = VALUES(`asset_prefix`),
  `active` = VALUES(`active`);

INSERT INTO `assetdata`
(`assetNum`, `assetKat`, `Description`, `serial_number`, `manufacturer`, `model`,
 `purchase_date`, `purchase_cost`, `warranty_expiry`, `createdBy`, `createdDate`,
 `quantity`, `unit`, `assetStat`, `location`, `notes`) VALUES
('DT0001', 'DESKTOP', 'Sample Finance Desktop PC', 'DEMO-DT-0001', 'Dell', 'OptiPlex 7010',
 '2026-01-15', 3200.00, '2029-01-14', 'admin', CURRENT_DATE, 1, 'Unit', 'IN_STOCK', 'MAIN', 'Starter sample record - replace the serial number and specifications before operational use.'),
('DT0002', 'DESKTOP', 'Sample Engineering Desktop PC', 'DEMO-DT-0002', 'HP', 'Pro Tower 400 G9',
 '2026-01-15', 3450.00, '2029-01-14', 'admin', CURRENT_DATE, 1, 'Unit', 'IN_STOCK', 'MAIN', 'Starter sample record - replace the serial number and specifications before operational use.'),
('LT0001', 'LAPTOP', 'Sample Business Laptop', 'DEMO-LT-0001', 'Lenovo', 'ThinkPad T14 Gen 5',
 '2026-02-10', 5100.00, '2029-02-09', 'admin', CURRENT_DATE, 1, 'Unit', 'IN_STOCK', 'MAIN', 'Starter sample record - replace the serial number and specifications before operational use.'),
('LT0002', 'LAPTOP', 'Sample Management Laptop', 'DEMO-LT-0002', 'Dell', 'Latitude 5450',
 '2026-02-10', 4950.00, '2029-02-09', 'admin', CURRENT_DATE, 1, 'Unit', 'IN_STOCK', 'MAIN', 'Starter sample record - replace the serial number and specifications before operational use.'),
('MN0001', 'MONITOR', 'Sample 24-inch Office Monitor', 'DEMO-MN-0001', 'Dell', 'P2425H',
 '2026-01-15', 820.00, '2029-01-14', 'admin', CURRENT_DATE, 1, 'Unit', 'IN_STOCK', 'MAIN', 'Starter sample record.'),
('MN0002', 'MONITOR', 'Sample 27-inch Office Monitor', 'DEMO-MN-0002', 'HP', 'Series 5 Pro 527pq',
 '2026-01-15', 1150.00, '2029-01-14', 'admin', CURRENT_DATE, 1, 'Unit', 'IN_STOCK', 'MAIN', 'Starter sample record.'),
('PR0001', 'PRINTER', 'Sample Network Laser Printer', 'DEMO-PR-0001', 'HP', 'LaserJet Pro 4003dn',
 '2026-03-01', 1750.00, '2027-02-28', 'admin', CURRENT_DATE, 1, 'Unit', 'IN_STOCK', 'MAIN', 'Starter sample record.'),
('NW0001', 'NETWORK', 'Sample Managed Network Switch', 'DEMO-NW-0001', 'Cisco', 'CBS350-24T-4G',
 '2026-03-05', 2450.00, '2029-03-04', 'admin', CURRENT_DATE, 1, 'Unit', 'IN_STOCK', 'MAIN', 'Starter sample record.'),
('UPS0001', 'UPS', 'Sample 1500VA UPS', 'DEMO-UPS-0001', 'APC', 'Smart-UPS 1500VA',
 '2026-03-05', 2600.00, '2028-03-04', 'admin', CURRENT_DATE, 1, 'Unit', 'IN_STOCK', 'MAIN', 'Starter sample record.'),
('PH0001', 'PHONE', 'Sample Office IP Phone', 'DEMO-PH-0001', 'Cisco', 'IP Phone 7821',
 '2026-03-12', 650.00, '2027-03-11', 'admin', CURRENT_DATE, 1, 'Unit', 'IN_STOCK', 'MAIN', 'Starter sample record.'),
('PJ0001', 'PROJECTOR', 'Sample Meeting Room Projector', 'DEMO-PJ-0001', 'Epson', 'EB-L260F',
 '2026-04-01', 6900.00, '2029-03-31', 'admin', CURRENT_DATE, 1, 'Unit', 'IN_STOCK', 'MAIN', 'Starter sample record.'),
('ST0001', 'STORAGE', 'Sample Department NAS', 'DEMO-ST-0001', 'Synology', 'DiskStation DS923+',
 '2026-04-08', 3800.00, '2029-04-07', 'admin', CURRENT_DATE, 1, 'Unit', 'IN_STOCK', 'MAIN', 'Starter sample record; disk costs are not included.')
ON DUPLICATE KEY UPDATE
  `assetKat` = VALUES(`assetKat`),
  `Description` = VALUES(`Description`),
  `serial_number` = VALUES(`serial_number`),
  `manufacturer` = VALUES(`manufacturer`),
  `model` = VALUES(`model`),
  `purchase_date` = VALUES(`purchase_date`),
  `purchase_cost` = VALUES(`purchase_cost`),
  `warranty_expiry` = VALUES(`warranty_expiry`),
  `quantity` = VALUES(`quantity`),
  `unit` = VALUES(`unit`),
  `assetStat` = VALUES(`assetStat`),
  `location` = VALUES(`location`),
  `notes` = VALUES(`notes`);

COMMIT;

SELECT COUNT(*) AS `active_categories` FROM `assetkat` WHERE `active` = 1;
SELECT COUNT(*) AS `sample_assets` FROM `assetdata` WHERE `serial_number` LIKE 'DEMO-%';
