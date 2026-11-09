-- cPanel / MariaDB login schema repair for IT Asset Management.
-- Safe to run more than once. It does not delete application data, create
-- views, use DEFINER, or enable the optional approval workflow UI.

SET NAMES utf8mb4;
SET @APP_DATABASE = DATABASE();

-- The current application links each login to an optional employee record.
SET @users_empid_exists = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @APP_DATABASE
    AND TABLE_NAME = 'users'
    AND COLUMN_NAME = 'empId'
);
SET @sql = IF(
  @users_empid_exists = 0,
  'ALTER TABLE `users` ADD COLUMN `empId` int(11) NULL AFTER `nama`',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add the one-login-per-employee index when the existing data permits it.
SET @users_empid_index_exists = (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = @APP_DATABASE
    AND TABLE_NAME = 'users'
    AND INDEX_NAME = 'uq_users_employee'
);
SET @duplicate_employee_links = (
  SELECT COUNT(*)
  FROM (
    SELECT `empId`
    FROM `users`
    WHERE `empId` IS NOT NULL
    GROUP BY `empId`
    HAVING COUNT(*) > 1
  ) duplicate_links
);
SET @sql = IF(
  @users_empid_index_exists = 0 AND @duplicate_employee_links = 0,
  'ALTER TABLE `users` ADD UNIQUE KEY `uq_users_employee` (`empId`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Older employee databases may not yet contain the newer linkage columns.
SET @employee_deptid_exists = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @APP_DATABASE
    AND TABLE_NAME = 'employee'
    AND COLUMN_NAME = 'deptId'
);
SET @sql = IF(
  @employee_deptid_exists = 0,
  'ALTER TABLE `employee` ADD COLUMN `deptId` varchar(10) NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @employee_department_exists = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @APP_DATABASE
    AND TABLE_NAME = 'employee'
    AND COLUMN_NAME = 'department'
);
SET @sql = IF(
  @employee_department_exists = 0,
  'ALTER TABLE `employee` ADD COLUMN `department` varchar(100) NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @employee_position_exists = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @APP_DATABASE
    AND TABLE_NAME = 'employee'
    AND COLUMN_NAME = 'position'
);
SET @sql = IF(
  @employee_position_exists = 0,
  'ALTER TABLE `employee` ADD COLUMN `position` varchar(100) NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Match the user_role username character set and collation to users.username.
-- This prevents mixed-collation errors during post-login role loading.
SET @users_username_charset = COALESCE((
  SELECT CHARACTER_SET_NAME
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @APP_DATABASE
    AND TABLE_NAME = 'users'
    AND COLUMN_NAME = 'username'
  LIMIT 1
), 'utf8mb4');
SET @users_username_collation = COALESCE((
  SELECT COLLATION_NAME
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @APP_DATABASE
    AND TABLE_NAME = 'users'
    AND COLUMN_NAME = 'username'
  LIMIT 1
), 'utf8mb4_unicode_ci');

SET @user_role_exists = (
  SELECT COUNT(*)
  FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = @APP_DATABASE
    AND TABLE_NAME = 'user_role'
    AND TABLE_TYPE = 'BASE TABLE'
);
SET @sql = IF(
  @user_role_exists = 0,
  CONCAT(
    'CREATE TABLE `user_role` (',
    '`username` varchar(50) CHARACTER SET ', @users_username_charset,
    ' COLLATE ', @users_username_collation, ' NOT NULL,',
    '`role_code` varchar(40) NOT NULL,',
    '`assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,',
    '`assigned_by` varchar(50) NULL,',
    'PRIMARY KEY (`username`,`role_code`),',
    'KEY `idx_user_role_code` (`role_code`)',
    ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
  ),
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Complete older user_role tables without overwriting existing role data.
SET @assigned_at_exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @APP_DATABASE
    AND TABLE_NAME = 'user_role'
    AND COLUMN_NAME = 'assigned_at'
);
SET @sql = IF(
  @assigned_at_exists = 0,
  'ALTER TABLE `user_role` ADD COLUMN `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @assigned_by_exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @APP_DATABASE
    AND TABLE_NAME = 'user_role'
    AND COLUMN_NAME = 'assigned_by'
);
SET @sql = IF(
  @assigned_by_exists = 0,
  'ALTER TABLE `user_role` ADD COLUMN `assigned_by` varchar(50) NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ensure the standard platform account can administer the system.
INSERT IGNORE INTO `user_role` (`username`, `role_code`, `assigned_by`)
SELECT `username`, 'ADMINISTRATOR', `username`
FROM `users`
WHERE LOWER(`username`) = 'admin';

-- Return a clear verification result in phpMyAdmin.
SELECT
  DATABASE() AS `database_checked`,
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'empId')
    AS `users_empId_ready`,
  (SELECT COUNT(*) FROM information_schema.TABLES
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_role' AND TABLE_TYPE = 'BASE TABLE')
    AS `user_role_ready`,
  (SELECT COUNT(*) FROM `users`) AS `user_count`,
  (SELECT COUNT(*) FROM `user_role`) AS `role_count`;
