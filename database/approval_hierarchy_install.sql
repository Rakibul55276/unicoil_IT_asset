-- Department approval hierarchy and stored request approval routes.
-- Safe to run more than once on MariaDB/MySQL in cPanel or XAMPP.
SET NAMES utf8mb4;

SET @department_area_exists = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema=DATABASE() AND table_name='department'
    AND column_name='area'
);
SET @department_area_sql = IF(
  @department_area_exists=0,
  'ALTER TABLE `department` ADD COLUMN `area` varchar(100) NULL AFTER `deptName`',
  'SELECT 1'
);
PREPARE department_area_stmt FROM @department_area_sql;
EXECUTE department_area_stmt;
DEALLOCATE PREPARE department_area_stmt;

SET @department_building_exists = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema=DATABASE() AND table_name='department'
    AND column_name='building'
);
SET @department_building_sql = IF(
  @department_building_exists=0,
  'ALTER TABLE `department` ADD COLUMN `building` varchar(100) NULL AFTER `area`',
  'SELECT 1'
);
PREPARE department_building_stmt FROM @department_building_sql;
EXECUTE department_building_stmt;
DEALLOCATE PREPARE department_building_stmt;

CREATE TABLE IF NOT EXISTS `department_head` (
  `deptId` varchar(10) NOT NULL,
  `empId` int(11) NOT NULL,
  `is_superior` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`deptId`,`empId`),
  KEY `idx_department_head_employee` (`empId`),
  KEY `idx_department_head_superior` (`deptId`,`is_superior`),
  CONSTRAINT `fk_department_head_department` FOREIGN KEY (`deptId`)
    REFERENCES `department` (`deptId`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_department_head_employee` FOREIGN KEY (`empId`)
    REFERENCES `employee` (`empId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `department_head` (`deptId`,`empId`,`is_superior`)
SELECT `deptId`,`headEmpId`,1 FROM `department` WHERE `headEmpId` IS NOT NULL;

SET @supervisor_exists = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema=DATABASE() AND table_name='employee'
    AND column_name='supervisorEmpId'
);
SET @supervisor_sql = IF(
  @supervisor_exists=0,
  'ALTER TABLE `employee` ADD COLUMN `supervisorEmpId` int(11) NULL AFTER `deptId`, ADD KEY `idx_employee_supervisor` (`supervisorEmpId`)',
  'SELECT 1'
);
PREPARE supervisor_stmt FROM @supervisor_sql;
EXECUTE supervisor_stmt;
DEALLOCATE PREPARE supervisor_stmt;

SET @supervisor_fk_exists = (
  SELECT COUNT(*) FROM information_schema.table_constraints
  WHERE constraint_schema=DATABASE() AND table_name='employee'
    AND constraint_name='fk_employee_supervisor'
    AND constraint_type='FOREIGN KEY'
);
SET @supervisor_fk_sql = IF(
  @supervisor_fk_exists=0,
  'ALTER TABLE `employee` ADD CONSTRAINT `fk_employee_supervisor` FOREIGN KEY (`supervisorEmpId`) REFERENCES `employee` (`empId`) ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT 1'
);
PREPARE supervisor_fk_stmt FROM @supervisor_fk_sql;
EXECUTE supervisor_fk_stmt;
DEALLOCATE PREPARE supervisor_fk_stmt;

CREATE TABLE IF NOT EXISTS `asset_request_approval_step` (
  `approval_step_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `request_id` bigint unsigned NOT NULL,
  `step_order` smallint unsigned NOT NULL,
  `step_type` varchar(30) NOT NULL,
  `approver_username` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'QUEUED',
  `acted_by` varchar(50) NULL,
  `acted_at` datetime NULL,
  `remarks` varchar(500) NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`approval_step_id`),
  UNIQUE KEY `uq_request_approval_order` (`request_id`,`step_order`),
  KEY `idx_request_approval_queue` (`approver_username`,`status`),
  CONSTRAINT `fk_request_approval_request` FOREIGN KEY (`request_id`)
    REFERENCES `asset_request` (`request_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_request_approval_user` FOREIGN KEY (`approver_username`)
    REFERENCES `users` (`username`) ON UPDATE CASCADE,
  CONSTRAINT `fk_request_approval_actor` FOREIGN KEY (`acted_by`)
    REFERENCES `users` (`username`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @request_receiver_exists = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema=DATABASE() AND table_name='asset_request'
    AND column_name='receiver_emp_id'
);
SET @request_receiver_sql = IF(
  @request_receiver_exists=0,
  'ALTER TABLE `asset_request` ADD COLUMN `receiver_emp_id` int(11) NULL AFTER `requested_for`, ADD KEY `idx_asset_request_receiver` (`receiver_emp_id`)',
  'SELECT 1'
);
PREPARE request_receiver_stmt FROM @request_receiver_sql;
EXECUTE request_receiver_stmt;
DEALLOCATE PREPARE request_receiver_stmt;

SET @request_receiver_fk_exists = (
  SELECT COUNT(*) FROM information_schema.table_constraints
  WHERE constraint_schema=DATABASE() AND table_name='asset_request'
    AND constraint_name='fk_asset_request_receiver'
    AND constraint_type='FOREIGN KEY'
);
SET @request_receiver_fk_sql = IF(
  @request_receiver_fk_exists=0,
  'ALTER TABLE `asset_request` ADD CONSTRAINT `fk_asset_request_receiver` FOREIGN KEY (`receiver_emp_id`) REFERENCES `employee` (`empId`) ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT 1'
);
PREPARE request_receiver_fk_stmt FROM @request_receiver_fk_sql;
EXECUTE request_receiver_fk_stmt;
DEALLOCATE PREPARE request_receiver_fk_stmt;
