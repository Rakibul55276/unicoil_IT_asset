-- IT Asset Management - COMPLETE cPanel database upgrade
-- Version: 2026-07-15
-- Compatible with MariaDB 10.4+ and MySQL 8.0+
--
-- Select the existing application database in phpMyAdmin, then import this
-- complete file. Do not copy SQL from a phpMyAdmin error preview because text
-- such as LEFT J[...] is truncated display text and is not valid SQL.
--
-- This upgrade preserves existing rows. It creates missing workflow tables,
-- extends the legacy base tables, and creates reporting views only after all
-- required tables exist.

SET NAMES utf8mb4;
SET @APP_DATABASE = DATABASE();
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;

DROP VIEW IF EXISTS `v_asset_current_assignment`;
DROP VIEW IF EXISTS `v_asset_issue_history`;
DROP VIEW IF EXISTS `v_asset_return_history`;
DROP VIEW IF EXISTS `v_asset_batch_receipts`;
DROP VIEW IF EXISTS `vassetdata`;
DROP VIEW IF EXISTS `vempdept`;

CREATE TABLE IF NOT EXISTS `schema_migrations` (
  `version` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL,
  `batch` int unsigned NOT NULL DEFAULT 1,
  `applied_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`version`),
  KEY `idx_schema_migrations_batch` (`batch`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Widen legacy asset numbers only when required. Known old foreign keys are
-- removed conditionally so the widening operation can succeed.
SET @NEED_ASSETNUM_WIDEN = (
  SELECT IF(COALESCE(CHARACTER_MAXIMUM_LENGTH,0) < 30,1,0)
  FROM information_schema.columns
  WHERE table_schema=@APP_DATABASE AND table_name='assetdata' AND column_name='assetNum'
  LIMIT 1
);
SET @NEED_ASSETNUM_WIDEN = COALESCE(@NEED_ASSETNUM_WIDEN,0);

SET @SQL = IF(@NEED_ASSETNUM_WIDEN=1 AND EXISTS(
  SELECT 1 FROM information_schema.table_constraints
  WHERE constraint_schema=@APP_DATABASE AND table_name='employee' AND constraint_name='fk_employee_asset'
),'ALTER TABLE `employee` DROP FOREIGN KEY `fk_employee_asset`','SELECT 1');
PREPARE upgrade_stmt FROM @SQL; EXECUTE upgrade_stmt; DEALLOCATE PREPARE upgrade_stmt;

SET @SQL = IF(@NEED_ASSETNUM_WIDEN=1 AND EXISTS(
  SELECT 1 FROM information_schema.table_constraints
  WHERE constraint_schema=@APP_DATABASE AND table_name='asset_issue' AND constraint_name='fk_issue_asset'
),'ALTER TABLE `asset_issue` DROP FOREIGN KEY `fk_issue_asset`','SELECT 1');
PREPARE upgrade_stmt FROM @SQL; EXECUTE upgrade_stmt; DEALLOCATE PREPARE upgrade_stmt;

SET @SQL = IF(@NEED_ASSETNUM_WIDEN=1,
  'ALTER TABLE `assetdata` MODIFY `assetNum` varchar(30) NOT NULL','SELECT 1');
PREPARE upgrade_stmt FROM @SQL; EXECUTE upgrade_stmt; DEALLOCATE PREPARE upgrade_stmt;

ALTER TABLE `users`
  MODIFY `email` varchar(100) NOT NULL,
  MODIFY `password` varchar(255) NOT NULL,
  MODIFY `active` tinyint(1) NOT NULL DEFAULT 1;

ALTER TABLE `department`
  MODIFY `deptName` varchar(100) NOT NULL;

SET @SQL = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@APP_DATABASE AND table_name='department' AND column_name='headEmpId'),'SELECT 1','ALTER TABLE `department` ADD COLUMN `headEmpId` int NULL AFTER `deptName`');
PREPARE upgrade_stmt FROM @SQL; EXECUTE upgrade_stmt; DEALLOCATE PREPARE upgrade_stmt;
SET @SQL = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@APP_DATABASE AND table_name='department' AND column_name='active'),'SELECT 1','ALTER TABLE `department` ADD COLUMN `active` tinyint(1) NOT NULL DEFAULT 1 AFTER `headEmpId`');
PREPARE upgrade_stmt FROM @SQL; EXECUTE upgrade_stmt; DEALLOCATE PREPARE upgrade_stmt;

ALTER TABLE `employee`
  MODIFY `fullname` varchar(101) NOT NULL,
  MODIFY `gender` varchar(10) NULL,
  MODIFY `aktif` tinyint(1) NOT NULL DEFAULT 1,
  MODIFY `assetNum` varchar(30) NULL;

SET @SQL = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@APP_DATABASE AND table_name='employee' AND column_name='email'),'SELECT 1','ALTER TABLE `employee` ADD COLUMN `email` varchar(190) NULL AFTER `fullname`');
PREPARE upgrade_stmt FROM @SQL; EXECUTE upgrade_stmt; DEALLOCATE PREPARE upgrade_stmt;
SET @SQL = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@APP_DATABASE AND table_name='employee' AND column_name='department'),'SELECT 1','ALTER TABLE `employee` ADD COLUMN `department` varchar(100) NULL AFTER `email`');
PREPARE upgrade_stmt FROM @SQL; EXECUTE upgrade_stmt; DEALLOCATE PREPARE upgrade_stmt;
SET @SQL = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@APP_DATABASE AND table_name='employee' AND column_name='deptId'),'SELECT 1','ALTER TABLE `employee` ADD COLUMN `deptId` varchar(10) NULL AFTER `department`');
PREPARE upgrade_stmt FROM @SQL; EXECUTE upgrade_stmt; DEALLOCATE PREPARE upgrade_stmt;

UPDATE `employee` e
LEFT JOIN `empdept` ed ON ed.`empId`=e.`empId` AND ed.`active`=1
LEFT JOIN `department` d ON d.`deptId`=ed.`deptId`
SET e.`deptId`=COALESCE(e.`deptId`,ed.`deptId`),
    e.`department`=COALESCE(NULLIF(e.`department`,''),d.`deptName`);

SET @NEED_STATUS_WIDEN = (
  SELECT IF(COALESCE(CHARACTER_MAXIMUM_LENGTH,0) < 20,1,0)
  FROM information_schema.columns
  WHERE table_schema=@APP_DATABASE AND table_name='assetstat' AND column_name='assetStatus'
  LIMIT 1
);
SET @NEED_STATUS_WIDEN = COALESCE(@NEED_STATUS_WIDEN,0);
SET @SQL = IF(@NEED_STATUS_WIDEN=1 AND EXISTS(
  SELECT 1 FROM information_schema.table_constraints
  WHERE constraint_schema=@APP_DATABASE AND table_name='assetdata' AND constraint_name='fk_asset_status'
),'ALTER TABLE `assetdata` DROP FOREIGN KEY `fk_asset_status`','SELECT 1');
PREPARE upgrade_stmt FROM @SQL; EXECUTE upgrade_stmt; DEALLOCATE PREPARE upgrade_stmt;
SET @SQL = IF(@NEED_STATUS_WIDEN=1,'ALTER TABLE `assetstat` MODIFY `assetStatus` varchar(20) NOT NULL','SELECT 1');
PREPARE upgrade_stmt FROM @SQL; EXECUTE upgrade_stmt; DEALLOCATE PREPARE upgrade_stmt;

SET @SQL = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@APP_DATABASE AND table_name='assetstat' AND column_name='is_terminal'),'SELECT 1','ALTER TABLE `assetstat` ADD COLUMN `is_terminal` tinyint(1) NOT NULL DEFAULT 0 AFTER `Description`');
PREPARE upgrade_stmt FROM @SQL; EXECUTE upgrade_stmt; DEALLOCATE PREPARE upgrade_stmt;

INSERT INTO `assetstat` (`assetStatus`,`Description`,`is_terminal`) VALUES
('IN_STOCK','In Stock',0),
('ISSUED_NEW','Issued - New',0),
('ISSUED_USED','Issued - Used',0),
('SCRAPPED','Scrapped',1)
ON DUPLICATE KEY UPDATE `Description`=VALUES(`Description`),`is_terminal`=VALUES(`is_terminal`);

SET @SQL = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@APP_DATABASE AND table_name='assetkat' AND column_name='asset_prefix'),'SELECT 1','ALTER TABLE `assetkat` ADD COLUMN `asset_prefix` varchar(6) NULL AFTER `Description`');
PREPARE upgrade_stmt FROM @SQL; EXECUTE upgrade_stmt; DEALLOCATE PREPARE upgrade_stmt;
SET @SQL = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@APP_DATABASE AND table_name='assetkat' AND column_name='active'),'SELECT 1','ALTER TABLE `assetkat` ADD COLUMN `active` tinyint(1) NOT NULL DEFAULT 1 AFTER `asset_prefix`');
PREPARE upgrade_stmt FROM @SQL; EXECUTE upgrade_stmt; DEALLOCATE PREPARE upgrade_stmt;

UPDATE `assetkat` SET `asset_prefix`=CASE UPPER(`assetKat`)
  WHEN 'LAPTOP' THEN 'LT' WHEN 'DESKTOP' THEN 'DT' WHEN 'MONITOR' THEN 'MN'
  WHEN 'PRINTER' THEN 'PR' WHEN 'NETWORK' THEN 'NW' WHEN 'SERVER' THEN 'SV'
  WHEN 'STORAGE' THEN 'ST' WHEN 'UPS' THEN 'UPS' WHEN 'MOBILE' THEN 'MB'
  WHEN 'TABLET' THEN 'TB' WHEN 'PROJECTOR' THEN 'PJ' WHEN 'SCANNER' THEN 'SC'
  WHEN 'CAMERA' THEN 'CM' WHEN 'PHONE' THEN 'PH' WHEN 'HEADSET' THEN 'HS'
  WHEN 'KEYMOUSE' THEN 'KM' WHEN 'DOCK' THEN 'DK' WHEN 'ADAPTER' THEN 'AD'
  WHEN 'DRIVE' THEN 'DR' WHEN 'RACK' THEN 'RK' WHEN 'FIREWALL' THEN 'FW'
  WHEN 'BIOMETRIC' THEN 'BM' WHEN 'ACCESS' THEN 'AC' WHEN 'MEETING' THEN 'MR'
  WHEN 'OTHER' THEN 'OT'
  WHEN '550001' THEN 'L2' WHEN '550002' THEN 'M2' WHEN '550003' THEN 'P2'
  WHEN '550004' THEN 'J2' WHEN '550005' THEN 'R2' WHEN '550006' THEN 'H2'
  WHEN '550007' THEN 'C2'
  ELSE CONCAT('X',UPPER(SUBSTRING(SHA1(`assetKat`),1,5))) END
WHERE `asset_prefix` IS NULL OR TRIM(`asset_prefix`)='';

ALTER TABLE `assetkat` MODIFY `asset_prefix` varchar(6) NOT NULL;

SET @SQL = IF(EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=@APP_DATABASE AND table_name='assetkat' AND index_name='uq_assetkat_prefix'),'SELECT 1','ALTER TABLE `assetkat` ADD UNIQUE KEY `uq_assetkat_prefix` (`asset_prefix`)');
PREPARE upgrade_stmt FROM @SQL; EXECUTE upgrade_stmt; DEALLOCATE PREPARE upgrade_stmt;

SET @SQL = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@APP_DATABASE AND table_name='location' AND column_name='active'),'SELECT 1','ALTER TABLE `location` ADD COLUMN `active` tinyint(1) NOT NULL DEFAULT 1 AFTER `description`');
PREPARE upgrade_stmt FROM @SQL; EXECUTE upgrade_stmt; DEALLOCATE PREPARE upgrade_stmt;

ALTER TABLE `assetdata`
  MODIFY `Description` varchar(255) NOT NULL,
  MODIFY `empId` int NULL,
  MODIFY `quantity` decimal(12,2) NOT NULL DEFAULT 1.00,
  MODIFY `assetStat` varchar(20) NOT NULL DEFAULT 'IN_STOCK';

SET @SQL = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@APP_DATABASE AND table_name='assetdata' AND column_name='serial_number'),'SELECT 1','ALTER TABLE `assetdata` ADD COLUMN `serial_number` varchar(100) NULL AFTER `Description`');
PREPARE upgrade_stmt FROM @SQL; EXECUTE upgrade_stmt; DEALLOCATE PREPARE upgrade_stmt;
SET @SQL = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@APP_DATABASE AND table_name='assetdata' AND column_name='manufacturer'),'SELECT 1','ALTER TABLE `assetdata` ADD COLUMN `manufacturer` varchar(100) NULL AFTER `serial_number`');
PREPARE upgrade_stmt FROM @SQL; EXECUTE upgrade_stmt; DEALLOCATE PREPARE upgrade_stmt;
SET @SQL = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@APP_DATABASE AND table_name='assetdata' AND column_name='model'),'SELECT 1','ALTER TABLE `assetdata` ADD COLUMN `model` varchar(100) NULL AFTER `manufacturer`');
PREPARE upgrade_stmt FROM @SQL; EXECUTE upgrade_stmt; DEALLOCATE PREPARE upgrade_stmt;
SET @SQL = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@APP_DATABASE AND table_name='assetdata' AND column_name='purchase_date'),'SELECT 1','ALTER TABLE `assetdata` ADD COLUMN `purchase_date` date NULL AFTER `model`');
PREPARE upgrade_stmt FROM @SQL; EXECUTE upgrade_stmt; DEALLOCATE PREPARE upgrade_stmt;
SET @SQL = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@APP_DATABASE AND table_name='assetdata' AND column_name='purchase_cost'),'SELECT 1','ALTER TABLE `assetdata` ADD COLUMN `purchase_cost` decimal(14,2) NULL AFTER `purchase_date`');
PREPARE upgrade_stmt FROM @SQL; EXECUTE upgrade_stmt; DEALLOCATE PREPARE upgrade_stmt;
SET @SQL = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@APP_DATABASE AND table_name='assetdata' AND column_name='warranty_expiry'),'SELECT 1','ALTER TABLE `assetdata` ADD COLUMN `warranty_expiry` date NULL AFTER `purchase_cost`');
PREPARE upgrade_stmt FROM @SQL; EXECUTE upgrade_stmt; DEALLOCATE PREPARE upgrade_stmt;
SET @SQL = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@APP_DATABASE AND table_name='assetdata' AND column_name='receipt_id'),'SELECT 1','ALTER TABLE `assetdata` ADD COLUMN `receipt_id` bigint unsigned NULL AFTER `warranty_expiry`');
PREPARE upgrade_stmt FROM @SQL; EXECUTE upgrade_stmt; DEALLOCATE PREPARE upgrade_stmt;
SET @SQL = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@APP_DATABASE AND table_name='assetdata' AND column_name='deptId'),'SELECT 1','ALTER TABLE `assetdata` ADD COLUMN `deptId` varchar(10) NULL AFTER `empId`');
PREPARE upgrade_stmt FROM @SQL; EXECUTE upgrade_stmt; DEALLOCATE PREPARE upgrade_stmt;
SET @SQL = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@APP_DATABASE AND table_name='assetdata' AND column_name='assetImage'),'SELECT 1','ALTER TABLE `assetdata` ADD COLUMN `assetImage` varchar(255) NULL AFTER `location`');
PREPARE upgrade_stmt FROM @SQL; EXECUTE upgrade_stmt; DEALLOCATE PREPARE upgrade_stmt;
SET @SQL = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@APP_DATABASE AND table_name='assetdata' AND column_name='notes'),'SELECT 1','ALTER TABLE `assetdata` ADD COLUMN `notes` text NULL AFTER `assetImage`');
PREPARE upgrade_stmt FROM @SQL; EXECUTE upgrade_stmt; DEALLOCATE PREPARE upgrade_stmt;
SET @SQL = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@APP_DATABASE AND table_name='assetdata' AND column_name='updated_at'),'SELECT 1','ALTER TABLE `assetdata` ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `notes`');
PREPARE upgrade_stmt FROM @SQL; EXECUTE upgrade_stmt; DEALLOCATE PREPARE upgrade_stmt;

UPDATE `assetdata` a LEFT JOIN `employee` e ON e.`empId`=a.`empId`
SET a.`deptId`=COALESCE(a.`deptId`,e.`deptId`);
UPDATE `assetdata` SET `assetStat`='SCRAPPED' WHERE `assetStat`='C';
UPDATE `assetdata` SET `assetStat`='IN_STOCK' WHERE `assetStat` IN ('A','B','D');

CREATE TABLE IF NOT EXISTS `vendor` (
  `vendor_id` int unsigned NOT NULL AUTO_INCREMENT,
  `vendor_name` varchar(150) NOT NULL,
  `contact_person` varchar(120) NULL,
  `email` varchar(190) NULL,
  `phone` varchar(50) NULL,
  `address` varchar(500) NULL,
  `tax_number` varchar(80) NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`vendor_id`),
  UNIQUE KEY `uq_vendor_name` (`vendor_name`),
  KEY `idx_vendor_active_name` (`active`,`vendor_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `asset_receipt` (
  `receipt_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `receipt_no` varchar(30) NOT NULL,
  `received_date` date NOT NULL,
  `supplier_name` varchar(150) NULL,
  `vendor_id` int unsigned NULL,
  `po_number` varchar(50) NULL,
  `invoice_number` varchar(50) NULL,
  `received_by` varchar(50) NOT NULL,
  `notes` text NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`receipt_id`),
  KEY `idx_receipt_no` (`receipt_no`),
  KEY `idx_receipt_vendor` (`vendor_id`),
  KEY `idx_receipt_user` (`received_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `asset_issue` (
  `issue_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `issue_no` varchar(30) NOT NULL,
  `assetNum` varchar(30) NOT NULL,
  `assignee_type` enum('DEPARTMENT','USER') NOT NULL,
  `deptId` varchar(10) NULL,
  `empId` int NULL,
  `issue_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expected_return_date` date NULL,
  `condition_out` enum('NEW','GOOD','FAIR','DAMAGED') NOT NULL DEFAULT 'GOOD',
  `purpose` varchar(255) NULL,
  `issued_by` varchar(50) NOT NULL,
  `acknowledged_by` varchar(150) NULL,
  `status` enum('ISSUED','RETURNED','SCRAPPED','CANCELLED') NOT NULL DEFAULT 'ISSUED',
  `returned_at` datetime NULL,
  `active_assetNum` varchar(30) GENERATED ALWAYS AS (CASE WHEN `status`='ISSUED' THEN `assetNum` ELSE NULL END) STORED,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`issue_id`),
  UNIQUE KEY `uq_issue_no` (`issue_no`),
  UNIQUE KEY `uq_one_active_issue_per_asset` (`active_assetNum`),
  KEY `idx_issue_asset` (`assetNum`),
  KEY `idx_issue_employee` (`empId`,`status`),
  KEY `idx_issue_department` (`deptId`,`status`),
  KEY `idx_issue_date` (`issue_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `asset_return` (
  `return_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `return_no` varchar(30) NOT NULL,
  `issue_id` bigint unsigned NOT NULL,
  `return_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `return_type` enum('REISSUE','SCRAP') NOT NULL,
  `condition_in` enum('GOOD','FAIR','DAMAGED','BEYOND_REPAIR') NOT NULL,
  `return_location` varchar(50) NULL,
  `reason` varchar(500) NULL,
  `scrap_reference` varchar(100) NULL,
  `received_by` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`return_id`),
  UNIQUE KEY `uq_return_no` (`return_no`),
  UNIQUE KEY `uq_one_return_per_issue` (`issue_id`),
  KEY `idx_return_type_date` (`return_type`,`return_date`),
  KEY `idx_return_condition_date` (`condition_in`,`return_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `asset_receipt_item` (
  `receipt_item_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `receipt_id` bigint unsigned NOT NULL,
  `assetNum` varchar(30) NOT NULL,
  `line_no` int unsigned NOT NULL,
  `condition_on_receipt` enum('NEW','USED','REFURBISHED') NOT NULL DEFAULT 'NEW',
  `remarks` varchar(500) NULL,
  PRIMARY KEY (`receipt_item_id`),
  UNIQUE KEY `uq_receipt_line` (`receipt_id`,`line_no`),
  UNIQUE KEY `uq_receipt_asset` (`assetNum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `asset_media` (
  `media_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `assetNum` varchar(30) NOT NULL,
  `issue_id` bigint unsigned NULL,
  `return_id` bigint unsigned NULL,
  `media_stage` enum('ENTRY','ASSIGN','RETURN') NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `original_name` varchar(255) NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_size` int unsigned NOT NULL,
  `uploaded_by` varchar(50) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`media_id`),
  KEY `idx_asset_media_asset_stage` (`assetNum`,`media_stage`),
  KEY `idx_asset_media_issue` (`issue_id`),
  KEY `idx_asset_media_return` (`return_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `assignment_email_log` (
  `email_log_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `issue_id` bigint unsigned NOT NULL,
  `notification_type` enum('ASSIGN','RETURN') NOT NULL DEFAULT 'ASSIGN',
  `recipient_email` varchar(190) NULL,
  `delivery_status` enum('SENT','FAILED','SKIPPED') NOT NULL,
  `attachment_count` int unsigned NOT NULL DEFAULT 0,
  `error_message` varchar(500) NULL,
  `attempted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`email_log_id`),
  KEY `idx_assignment_email_issue` (`issue_id`),
  KEY `idx_assignment_email_status` (`delivery_status`,`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @SQL = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@APP_DATABASE AND table_name='assignment_email_log' AND column_name='notification_type'),'SELECT 1','ALTER TABLE `assignment_email_log` ADD COLUMN `notification_type` enum(''ASSIGN'',''RETURN'') NOT NULL DEFAULT ''ASSIGN'' AFTER `issue_id`');
PREPARE upgrade_stmt FROM @SQL; EXECUTE upgrade_stmt; DEALLOCATE PREPARE upgrade_stmt;

CREATE TABLE IF NOT EXISTS `asset_deletion_audit` (
  `deletion_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `asset_num` varchar(30) NOT NULL,
  `asset_description` varchar(255) NOT NULL,
  `category_name` varchar(100) NULL,
  `serial_number` varchar(100) NULL,
  `archive_image` varchar(255) NULL,
  `deleted_by` varchar(50) NOT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deletion_reason` varchar(500) NOT NULL,
  `asset_snapshot` longtext NOT NULL,
  `transaction_snapshot` longtext NOT NULL,
  `media_snapshot` longtext NOT NULL,
  PRIMARY KEY (`deletion_id`),
  KEY `idx_deleted_asset_date` (`deleted_at`,`asset_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `vempdept` AS
SELECT e.`empId`,e.`fullname`,COALESCE(e.`deptId`,ed.`deptId`) AS `deptId`,d.`deptName`
FROM `employee` e
LEFT JOIN `empdept` ed ON ed.`empId`=e.`empId` AND ed.`active`=1
LEFT JOIN `department` d ON d.`deptId`=COALESCE(e.`deptId`,ed.`deptId`);

CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `vassetdata` AS
SELECT a.`assetNum`,a.`Description`,k.`Description` AS `assetkat`,a.`empId`,e.`fullname`,
  a.`createdBy`,a.`createdDate`,a.`quantity`,a.`unit`,s.`Description` AS `assetStat`,
  l.`description` AS `location`,a.`assetImage`
FROM `assetdata` a
LEFT JOIN `employee` e ON e.`empId`=a.`empId`
LEFT JOIN `location` l ON l.`location`=a.`location`
LEFT JOIN `assetstat` s ON s.`assetStatus`=a.`assetStat`
LEFT JOIN `assetkat` k ON k.`assetKat`=a.`assetKat`;

CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `v_asset_issue_history` AS
SELECT i.`issue_id`,i.`issue_no`,i.`issue_date`,i.`assetNum`,a.`Description` AS `asset_description`,
  a.`assetKat`,k.`Description` AS `category`,i.`assignee_type`,i.`empId`,
  COALESCE(e.`fullname`,i.`acknowledged_by`) AS `employee_name`,i.`deptId`,d.`deptName`,
  i.`condition_out`,i.`expected_return_date`,i.`purpose`,i.`status`,i.`issued_by`,i.`returned_at`
FROM `asset_issue` i
INNER JOIN `assetdata` a ON a.`assetNum`=i.`assetNum`
LEFT JOIN `assetkat` k ON k.`assetKat`=a.`assetKat`
LEFT JOIN `employee` e ON e.`empId`=i.`empId`
LEFT JOIN `department` d ON d.`deptId`=i.`deptId`;

CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `v_asset_current_assignment` AS
SELECT * FROM `v_asset_issue_history` WHERE `status`='ISSUED';

CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `v_asset_return_history` AS
SELECT r.`return_id`,r.`return_no`,r.`return_date`,r.`return_type`,r.`condition_in`,
  r.`reason`,r.`scrap_reference`,r.`return_location`,l.`description` AS `location_name`,
  r.`received_by`,i.`issue_id`,i.`issue_no`,i.`issue_date`,i.`assetNum`,
  a.`Description` AS `asset_description`,a.`serial_number`,a.`assetKat` AS `category_code`,
  k.`Description` AS `category_name`,i.`assignee_type`,i.`empId`,
  COALESCE(e.`fullname`,i.`acknowledged_by`) AS `employee_name`,i.`deptId`,d.`deptName`,
  i.`condition_out`,i.`purpose`,i.`issued_by`
FROM `asset_return` r
INNER JOIN `asset_issue` i ON i.`issue_id`=r.`issue_id`
INNER JOIN `assetdata` a ON a.`assetNum`=i.`assetNum`
LEFT JOIN `assetkat` k ON k.`assetKat`=a.`assetKat`
LEFT JOIN `employee` e ON e.`empId`=i.`empId`
LEFT JOIN `department` d ON d.`deptId`=i.`deptId`
LEFT JOIN `location` l ON l.`location`=r.`return_location`;

CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `v_asset_batch_receipts` AS
SELECT r.`receipt_id`,r.`receipt_no`,r.`received_date`,r.`supplier_name`,r.`vendor_id`,
  r.`po_number`,r.`invoice_number`,r.`received_by`,ri.`line_no`,ri.`condition_on_receipt`,
  ri.`remarks`,a.`assetNum`,a.`Description` AS `asset_description`,a.`serial_number`,
  a.`assetKat` AS `category_code`,k.`Description` AS `category_name`,a.`manufacturer`,
  a.`model`,a.`purchase_date`,a.`purchase_cost`,a.`warranty_expiry`,a.`assetStat`,a.`location`
FROM `asset_receipt` r
INNER JOIN `asset_receipt_item` ri ON ri.`receipt_id`=r.`receipt_id`
INNER JOIN `assetdata` a ON a.`assetNum`=ri.`assetNum`
LEFT JOIN `assetkat` k ON k.`assetKat`=a.`assetKat`;

INSERT INTO `schema_migrations` (`version`,`description`,`batch`)
VALUES ('20260715_complete_cpanel_workflow','Complete cPanel receipt, assignment, return, media, audit, and reporting upgrade',1)
ON DUPLICATE KEY UPDATE `description`=VALUES(`description`);

SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;

SELECT
  (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='asset_return') AS `asset_return_ready`,
  (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='asset_receipt_item') AS `batch_receipt_ready`,
  (SELECT COUNT(*) FROM information_schema.views WHERE table_schema=DATABASE() AND table_name='v_asset_return_history') AS `return_history_view_ready`,
  'Complete cPanel upgrade finished successfully' AS `result`;
