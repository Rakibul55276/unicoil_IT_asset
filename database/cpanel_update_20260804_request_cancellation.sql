-- IT Asset Management: safe return-request cancellation support.
-- Run once in phpMyAdmin after selecting the application database.

ALTER TABLE `asset_return_request`
  ADD COLUMN `cancelled_by` varchar(50) NULL AFTER `processed_at`,
  ADD COLUMN `cancelled_at` datetime NULL AFTER `cancelled_by`,
  ADD COLUMN `cancellation_reason` varchar(500) NULL AFTER `cancelled_at`;

INSERT INTO `schema_migrations` (`version`, `description`, `batch`)
SELECT
  '20260804_000001_return_request_cancellation',
  'Preserve return-request cancellation actor, time and reason',
  COALESCE(MAX(`batch`), 0) + 1
FROM `schema_migrations`
ON DUPLICATE KEY UPDATE `version` = VALUES(`version`);
