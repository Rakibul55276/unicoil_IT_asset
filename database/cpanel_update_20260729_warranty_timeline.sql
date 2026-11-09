-- cPanel/MariaDB-compatible warranty timeline performance update.
-- Safe to run more than once. No asset records are changed.

SET @idx_warranty_exists = (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'assetdata'
    AND index_name = 'idx_asset_warranty_status'
);
SET @idx_warranty_sql = IF(
  @idx_warranty_exists = 0,
  'ALTER TABLE `assetdata` ADD INDEX `idx_asset_warranty_status` (`warranty_expiry`,`assetStat`,`assetNum`)',
  'SELECT 1'
);
PREPARE idx_warranty_stmt FROM @idx_warranty_sql;
EXECUTE idx_warranty_stmt;
DEALLOCATE PREPARE idx_warranty_stmt;

SET @idx_lifecycle_exists = (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'assetdata'
    AND index_name = 'idx_asset_purchase_warranty'
);
SET @idx_lifecycle_sql = IF(
  @idx_lifecycle_exists = 0,
  'ALTER TABLE `assetdata` ADD INDEX `idx_asset_purchase_warranty` (`purchase_date`,`warranty_expiry`,`assetNum`)',
  'SELECT 1'
);
PREPARE idx_lifecycle_stmt FROM @idx_lifecycle_sql;
EXECUTE idx_lifecycle_stmt;
DEALLOCATE PREPARE idx_lifecycle_stmt;
