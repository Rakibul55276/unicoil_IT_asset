-- IT Asset Management large-data performance migration
-- Compatible with MariaDB 10.4+ / cPanel phpMyAdmin.
-- Safe to run more than once.

SET @schema_name := DATABASE();

-- Store the next category sequence instead of scanning every asset on each receipt.
ALTER TABLE `assetkat`
  ADD COLUMN IF NOT EXISTS `next_asset_sequence` BIGINT UNSIGNED NOT NULL DEFAULT 1
  AFTER `asset_prefix`;

UPDATE `assetkat` k
SET k.`next_asset_sequence` = GREATEST(
  k.`next_asset_sequence`,
  COALESCE(
    (
      SELECT MAX(
        CAST(SUBSTRING(a.`assetNum`, CHAR_LENGTH(k.`asset_prefix`) + 1) AS UNSIGNED)
      ) + 1
      FROM `assetdata` a
      WHERE a.`assetKat` = k.`assetKat`
        AND a.`assetNum` LIKE CONCAT(k.`asset_prefix`, '%')
        AND SUBSTRING(a.`assetNum`, CHAR_LENGTH(k.`asset_prefix`) + 1) REGEXP '^[0-9]+$'
    ),
    1
  )
);

-- Composite indexes used by paged inventory, assignment, history and employee views.
ALTER TABLE `assetdata`
  ADD INDEX IF NOT EXISTS `idx_asset_status_number` (`assetStat`, `assetNum`),
  ADD INDEX IF NOT EXISTS `idx_asset_status_category_number` (`assetStat`, `assetKat`, `assetNum`),
  ADD INDEX IF NOT EXISTS `idx_asset_category_serial` (`assetKat`, `serial_number`),
  ADD INDEX IF NOT EXISTS `idx_asset_created_number` (`createdDate`, `assetNum`),
  ADD INDEX IF NOT EXISTS `idx_asset_description_prefix` (`Description`(80));

ALTER TABLE `asset_issue`
  ADD INDEX IF NOT EXISTS `idx_issue_date_id` (`issue_date`, `issue_id`),
  ADD INDEX IF NOT EXISTS `idx_issue_status_date_id` (`status`, `issue_date`, `issue_id`),
  ADD INDEX IF NOT EXISTS `idx_issue_status_asset` (`status`, `assetNum`),
  ADD INDEX IF NOT EXISTS `idx_issue_batch_status_id` (`batch_no`, `status`, `issue_id`),
  ADD INDEX IF NOT EXISTS `idx_issue_user_status_id` (`assignee_type`, `empId`, `status`, `issue_id`),
  ADD INDEX IF NOT EXISTS `idx_issue_dept_status_id` (`assignee_type`, `deptId`, `status`, `issue_id`);

ALTER TABLE `asset_return`
  ADD INDEX IF NOT EXISTS `idx_return_date_id` (`return_date`, `return_id`),
  ADD INDEX IF NOT EXISTS `idx_return_batch_date_id` (`batch_no`, `return_date`, `return_id`);

ALTER TABLE `asset_receipt`
  ADD INDEX IF NOT EXISTS `idx_receipt_date_id` (`received_date`, `receipt_id`);

ALTER TABLE `asset_deletion_audit`
  ADD INDEX IF NOT EXISTS `idx_deletion_date_id` (`deleted_at`, `deletion_id`),
  ADD INDEX IF NOT EXISTS `idx_deletion_asset_date` (`asset_num`, `deleted_at`);

ALTER TABLE `asset_return_request`
  ADD INDEX IF NOT EXISTS `idx_return_request_created_id` (`created_at`, `return_request_id`),
  ADD INDEX IF NOT EXISTS `idx_return_request_status_created` (`status`, `created_at`);

ALTER TABLE `asset_scrap_request`
  ADD INDEX IF NOT EXISTS `idx_scrap_created_id` (`created_at`, `scrap_request_id`),
  ADD INDEX IF NOT EXISTS `idx_scrap_reviewed_id` (`reviewed_at`, `scrap_request_id`);

ALTER TABLE `employee`
  ADD INDEX IF NOT EXISTS `idx_employee_active_name_id` (`aktif`, `fullname`, `empId`),
  ADD INDEX IF NOT EXISTS `idx_employee_active_dept_name` (`aktif`, `deptId`, `fullname`);

ALTER TABLE `asset_media`
  ADD INDEX IF NOT EXISTS `idx_media_asset_stage_id` (`assetNum`, `media_stage`, `media_id`);

ALTER TABLE `asset_request`
  ADD INDEX IF NOT EXISTS `idx_request_status_created_id` (`status`, `created_at`, `request_id`),
  ADD INDEX IF NOT EXISTS `idx_request_employee_created_id` (`requested_for`, `created_at`, `request_id`),
  ADD INDEX IF NOT EXISTS `idx_request_department_created_id` (`deptId`, `created_at`, `request_id`);

ALTER TABLE `asset_request_approval_step`
  ADD INDEX IF NOT EXISTS `idx_approval_user_status_step` (`approver_username`, `status`, `step_order`);
