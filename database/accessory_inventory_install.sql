-- Optional quantity-based Accessory Inventory module.
-- Import this file before setting accessory_inventory_enabled=true.
-- No serialized asset table or workflow table is altered.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `accessory_item` (
  `accessory_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `accessory_code` varchar(30) NOT NULL,
  `accessory_name` varchar(150) NOT NULL,
  `category` varchar(100) NOT NULL,
  `unit` varchar(20) NOT NULL DEFAULT 'PCS',
  `quantity_on_hand` decimal(14,3) unsigned NOT NULL DEFAULT 0.000,
  `reorder_level` decimal(14,3) unsigned NOT NULL DEFAULT 0.000,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`accessory_id`),
  UNIQUE KEY `uq_accessory_code` (`accessory_code`),
  KEY `idx_accessory_name` (`accessory_name`),
  KEY `idx_accessory_category_active` (`category`,`active`),
  KEY `idx_accessory_stock` (`active`,`quantity_on_hand`),
  CONSTRAINT `fk_accessory_created_by` FOREIGN KEY (`created_by`)
    REFERENCES `users` (`username`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `accessory_transaction` (
  `transaction_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `transaction_no` varchar(40) NOT NULL,
  `accessory_id` bigint(20) unsigned NOT NULL,
  `event_type` enum('RECEIPT','ISSUE','RETURN','ADJUSTMENT_IN','ADJUSTMENT_OUT') NOT NULL,
  `quantity` decimal(14,3) unsigned NOT NULL,
  `balance_before` decimal(14,3) unsigned NOT NULL,
  `balance_after` decimal(14,3) unsigned NOT NULL,
  `recipient_type` enum('EMPLOYEE','DEPARTMENT','NONE') NOT NULL DEFAULT 'NONE',
  `empId` int(11) DEFAULT NULL,
  `deptId` varchar(10) DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `remarks` varchar(500) NOT NULL,
  `performed_by` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`transaction_id`),
  UNIQUE KEY `uq_accessory_transaction_no` (`transaction_no`),
  KEY `idx_accessory_transaction_item_date` (`accessory_id`,`created_at`),
  KEY `idx_accessory_transaction_event_date` (`event_type`,`created_at`),
  KEY `idx_accessory_transaction_employee` (`empId`,`created_at`),
  KEY `idx_accessory_transaction_department` (`deptId`,`created_at`),
  KEY `idx_accessory_transaction_actor` (`performed_by`,`created_at`),
  CONSTRAINT `fk_accessory_transaction_item` FOREIGN KEY (`accessory_id`)
    REFERENCES `accessory_item` (`accessory_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_accessory_transaction_employee` FOREIGN KEY (`empId`)
    REFERENCES `employee` (`empId`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_accessory_transaction_department` FOREIGN KEY (`deptId`)
    REFERENCES `department` (`deptId`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_accessory_transaction_actor` FOREIGN KEY (`performed_by`)
    REFERENCES `users` (`username`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
