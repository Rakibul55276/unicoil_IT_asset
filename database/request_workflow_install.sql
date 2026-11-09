-- Equipment request and approval workflow.
-- Safe to run more than once against the IT-asset working database.
SET NAMES utf8mb4;

-- Department prefixes are printed on assignment/return forms.
SET @department_prefix_exists = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema=DATABASE() AND table_name='department' AND column_name='deptPrefix'
);
SET @department_prefix_sql = IF(
  @department_prefix_exists=0,
  'ALTER TABLE `department` ADD COLUMN `deptPrefix` varchar(10) NULL AFTER `deptId`',
  'SELECT 1'
);
PREPARE department_prefix_stmt FROM @department_prefix_sql;
EXECUTE department_prefix_stmt;
DEALLOCATE PREPARE department_prefix_stmt;

UPDATE `department`
SET `deptPrefix`=LEFT(CONCAT('D',REPLACE(REPLACE(`deptId`,'-',''),' ','')),10)
WHERE `deptPrefix` IS NULL OR TRIM(`deptPrefix`)='';

SET @department_prefix_index_exists = (
  SELECT COUNT(*) FROM information_schema.statistics
  WHERE table_schema=DATABASE() AND table_name='department' AND index_name='uq_department_prefix'
);
SET @department_prefix_index_sql = IF(
  @department_prefix_index_exists=0,
  'ALTER TABLE `department` ADD UNIQUE KEY `uq_department_prefix` (`deptPrefix`)',
  'SELECT 1'
);
PREPARE department_prefix_index_stmt FROM @department_prefix_index_sql;
EXECUTE department_prefix_index_stmt;
DEALLOCATE PREPARE department_prefix_index_stmt;

SET @user_emp_exists = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema=DATABASE() AND table_name='users' AND column_name='empId'
);
SET @user_emp_sql = IF(
  @user_emp_exists=0,
  'ALTER TABLE `users` ADD COLUMN `empId` int(11) NULL AFTER `nama`, ADD UNIQUE KEY `uq_users_employee` (`empId`)',
  'SELECT 1'
);
PREPARE user_emp_stmt FROM @user_emp_sql;
EXECUTE user_emp_stmt;
DEALLOCATE PREPARE user_emp_stmt;

SET @user_emp_fk_exists = (
  SELECT COUNT(*) FROM information_schema.table_constraints
  WHERE constraint_schema=DATABASE() AND table_name='users'
    AND constraint_name='fk_users_employee' AND constraint_type='FOREIGN KEY'
);
SET @user_emp_fk_sql = IF(
  @user_emp_fk_exists=0,
  'ALTER TABLE `users` ADD CONSTRAINT `fk_users_employee` FOREIGN KEY (`empId`) REFERENCES `employee` (`empId`) ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT 1'
);
PREPARE user_emp_fk_stmt FROM @user_emp_fk_sql;
EXECUTE user_emp_fk_stmt;
DEALLOCATE PREPARE user_emp_fk_stmt;

CREATE TABLE IF NOT EXISTS `user_role` (
  `username` varchar(50) NOT NULL,
  `role_code` varchar(40) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `assigned_by` varchar(50) NULL,
  PRIMARY KEY (`username`,`role_code`),
  KEY `idx_user_role_code` (`role_code`),
  CONSTRAINT `fk_user_role_user` FOREIGN KEY (`username`)
    REFERENCES `users` (`username`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `asset_request` (
  `request_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `request_no` varchar(30) NOT NULL,
  `requested_by` varchar(50) NOT NULL,
  `requested_for` int(11) NULL,
  `deptId` varchar(10) NOT NULL,
  `request_target_type` varchar(20) NOT NULL DEFAULT 'USER',
  `request_source` varchar(20) NOT NULL DEFAULT 'SELF',
  `business_reason` varchar(500) NOT NULL,
  `required_date` date NULL,
  `work_location` varchar(150) NULL,
  `status` varchar(40) NOT NULL DEFAULT 'PENDING_DEPARTMENT_APPROVAL',
  `department_approved_by` varchar(50) NULL,
  `department_approved_at` datetime NULL,
  `it_approved_by` varchar(50) NULL,
  `it_approved_at` datetime NULL,
  `issued_by` varchar(50) NULL,
  `issued_at` datetime NULL,
  `receiver_accepted_at` datetime NULL,
  `receiver_accepted_by` varchar(50) NULL,
  `receiver_comment` varchar(500) NULL,
  `rejection_reason` varchar(500) NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`request_id`),
  UNIQUE KEY `uq_asset_request_no` (`request_no`),
  KEY `idx_asset_request_status` (`status`,`created_at`),
  KEY `idx_asset_request_employee` (`requested_for`,`created_at`),
  KEY `idx_asset_request_department` (`deptId`,`status`),
  CONSTRAINT `fk_asset_request_user` FOREIGN KEY (`requested_by`)
    REFERENCES `users` (`username`) ON UPDATE CASCADE,
  CONSTRAINT `fk_asset_request_employee` FOREIGN KEY (`requested_for`)
    REFERENCES `employee` (`empId`) ON UPDATE CASCADE,
  CONSTRAINT `fk_asset_request_department` FOREIGN KEY (`deptId`)
    REFERENCES `department` (`deptId`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @request_target_exists = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema=DATABASE() AND table_name='asset_request' AND column_name='request_target_type'
);
SET @request_target_sql = IF(
  @request_target_exists=0,
  'ALTER TABLE `asset_request` ADD COLUMN `request_target_type` varchar(20) NOT NULL DEFAULT ''USER'' AFTER `deptId`, MODIFY COLUMN `requested_for` int(11) NULL',
  'ALTER TABLE `asset_request` MODIFY COLUMN `requested_for` int(11) NULL'
);
PREPARE request_target_stmt FROM @request_target_sql;
EXECUTE request_target_stmt;
DEALLOCATE PREPARE request_target_stmt;

SET @request_acceptor_exists = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema=DATABASE() AND table_name='asset_request' AND column_name='receiver_accepted_by'
);
SET @request_acceptor_sql = IF(
  @request_acceptor_exists=0,
  'ALTER TABLE `asset_request` ADD COLUMN `receiver_accepted_by` varchar(50) NULL AFTER `receiver_accepted_at`',
  'SELECT 1'
);
PREPARE request_acceptor_stmt FROM @request_acceptor_sql;
EXECUTE request_acceptor_stmt;
DEALLOCATE PREPARE request_acceptor_stmt;

CREATE TABLE IF NOT EXISTS `asset_request_item` (
  `request_item_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `request_id` bigint unsigned NOT NULL,
  `assetKat` varchar(10) NOT NULL,
  `quantity` smallint unsigned NOT NULL DEFAULT 1,
  `approved_quantity` smallint unsigned NULL,
  `it_specification` varchar(500) NULL,
  `item_status` varchar(30) NOT NULL DEFAULT 'REQUESTED',
  PRIMARY KEY (`request_item_id`),
  UNIQUE KEY `uq_request_category` (`request_id`,`assetKat`),
  KEY `idx_request_item_category` (`assetKat`),
  CONSTRAINT `fk_request_item_request` FOREIGN KEY (`request_id`)
    REFERENCES `asset_request` (`request_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_request_item_category` FOREIGN KEY (`assetKat`)
    REFERENCES `assetkat` (`assetKat`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `asset_request_allocation` (
  `allocation_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `request_item_id` bigint unsigned NOT NULL,
  `assetNum` varchar(30) NOT NULL,
  `issue_id` bigint unsigned NOT NULL,
  `allocated_by` varchar(50) NOT NULL,
  `allocated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`allocation_id`),
  UNIQUE KEY `uq_request_allocation_asset` (`request_item_id`,`assetNum`),
  UNIQUE KEY `uq_request_allocation_issue` (`issue_id`),
  KEY `idx_request_allocation_asset` (`assetNum`),
  CONSTRAINT `fk_request_allocation_item` FOREIGN KEY (`request_item_id`)
    REFERENCES `asset_request_item` (`request_item_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_request_allocation_asset` FOREIGN KEY (`assetNum`)
    REFERENCES `assetdata` (`assetNum`) ON UPDATE CASCADE,
  CONSTRAINT `fk_request_allocation_issue` FOREIGN KEY (`issue_id`)
    REFERENCES `asset_issue` (`issue_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `asset_request_event` (
  `event_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `request_id` bigint unsigned NOT NULL,
  `event_type` varchar(40) NOT NULL,
  `from_status` varchar(40) NULL,
  `to_status` varchar(40) NULL,
  `actor_username` varchar(50) NOT NULL,
  `remarks` varchar(500) NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`event_id`),
  KEY `idx_request_event_request` (`request_id`,`created_at`),
  CONSTRAINT `fk_request_event_request` FOREIGN KEY (`request_id`)
    REFERENCES `asset_request` (`request_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_request_event_actor` FOREIGN KEY (`actor_username`)
    REFERENCES `users` (`username`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `asset_scrap_request` (
  `scrap_request_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `scrap_request_no` varchar(30) NOT NULL,
  `assetNum` varchar(30) NOT NULL,
  `requested_by` varchar(50) NOT NULL,
  `reason` varchar(500) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'PENDING_IT_APPROVAL',
  `reviewed_by` varchar(50) NULL,
  `reviewed_at` datetime NULL,
  `review_comment` varchar(500) NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`scrap_request_id`),
  UNIQUE KEY `uq_scrap_request_no` (`scrap_request_no`),
  KEY `idx_scrap_request_asset` (`assetNum`,`status`),
  KEY `idx_scrap_request_status` (`status`,`created_at`),
  CONSTRAINT `fk_scrap_request_asset` FOREIGN KEY (`assetNum`)
    REFERENCES `assetdata` (`assetNum`) ON UPDATE CASCADE,
  CONSTRAINT `fk_scrap_request_user` FOREIGN KEY (`requested_by`)
    REFERENCES `users` (`username`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `asset_scrap_event` (
  `scrap_event_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `scrap_request_id` bigint unsigned NOT NULL,
  `event_type` varchar(30) NOT NULL,
  `from_status` varchar(30) NULL,
  `to_status` varchar(30) NULL,
  `actor_username` varchar(50) NOT NULL,
  `remarks` varchar(500) NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`scrap_event_id`),
  KEY `idx_scrap_event_request` (`scrap_request_id`,`created_at`),
  CONSTRAINT `fk_scrap_event_request` FOREIGN KEY (`scrap_request_id`)
    REFERENCES `asset_scrap_request` (`scrap_request_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_scrap_event_actor` FOREIGN KEY (`actor_username`)
    REFERENCES `users` (`username`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `asset_return_request` (
  `return_request_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `return_request_no` varchar(30) NOT NULL,
  `issue_id` bigint unsigned NOT NULL,
  `requested_by` varchar(50) NOT NULL,
  `reason` varchar(500) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'PENDING',
  `return_id` bigint unsigned NULL,
  `processed_by` varchar(50) NULL,
  `processed_at` datetime NULL,
  `cancelled_by` varchar(50) NULL,
  `cancelled_at` datetime NULL,
  `cancellation_reason` varchar(500) NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`return_request_id`),
  UNIQUE KEY `uq_return_request_no` (`return_request_no`),
  KEY `idx_return_request_issue` (`issue_id`,`status`),
  KEY `idx_return_request_status` (`status`,`created_at`),
  CONSTRAINT `fk_return_request_issue` FOREIGN KEY (`issue_id`)
    REFERENCES `asset_issue` (`issue_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_return_request_user` FOREIGN KEY (`requested_by`)
    REFERENCES `users` (`username`) ON UPDATE CASCADE,
  CONSTRAINT `fk_return_request_return` FOREIGN KEY (`return_id`)
    REFERENCES `asset_return` (`return_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The existing administrator can configure and demonstrate every workflow stage.
INSERT IGNORE INTO `user_role` (`username`,`role_code`,`assigned_by`)
SELECT `username`, roles.`role_code`, `username`
FROM `users`
CROSS JOIN (
  SELECT 'ADMINISTRATOR' role_code UNION ALL
  SELECT 'HR_REQUESTER' UNION ALL
  SELECT 'DEPARTMENT_MANAGER' UNION ALL
  SELECT 'IT_MANAGER' UNION ALL
  SELECT 'ISSUER'
) roles
WHERE `username`='admin';

-- Requesting is a standard capability of every active employee-linked account.
DELETE FROM `user_role` WHERE `role_code`='EMPLOYEE_REQUESTER';
