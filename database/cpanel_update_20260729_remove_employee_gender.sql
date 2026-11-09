-- Remove the unused employee gender field.
-- Compatible with cPanel MariaDB/MySQL and safe to run more than once.
SET @APP_DATABASE = DATABASE();
SET @DROP_GENDER_SQL = IF(
  EXISTS(
    SELECT 1
    FROM information_schema.columns
    WHERE table_schema = @APP_DATABASE
      AND table_name = 'employee'
      AND column_name = 'gender'
  ),
  'ALTER TABLE `employee` DROP COLUMN `gender`',
  'SELECT 1'
);
PREPARE drop_employee_gender_stmt FROM @DROP_GENDER_SQL;
EXECUTE drop_employee_gender_stmt;
DEALLOCATE PREPARE drop_employee_gender_stmt;
