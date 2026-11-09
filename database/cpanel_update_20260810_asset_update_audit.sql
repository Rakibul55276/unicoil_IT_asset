-- Run once in the company asset database through cPanel phpMyAdmin.
-- No DEFINER clause or elevated database privilege is required.

CREATE TABLE IF NOT EXISTS `asset_update_audit` (
  `update_audit_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `assetNum` varchar(30) NOT NULL,
  `changed_by` varchar(50) NOT NULL,
  `change_reason` varchar(500) NOT NULL,
  `changed_fields` longtext NOT NULL,
  `previous_snapshot` longtext NOT NULL,
  `new_snapshot` longtext NOT NULL,
  `change_summary` text NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`update_audit_id`),
  KEY `idx_asset_update_audit_asset_date` (`assetNum`,`changed_at`),
  KEY `idx_asset_update_audit_changed_date` (`changed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `schema_migrations` (`version`, `description`, `batch`)
SELECT
  '20260810_000001_asset_update_audit',
  'Retain an audit trail for every controlled in-stock asset edit',
  COALESCE((SELECT MAX(`batch`) + 1 FROM `schema_migrations` sm), 1)
WHERE NOT EXISTS (
  SELECT 1 FROM `schema_migrations`
  WHERE `version` = '20260810_000001_asset_update_audit'
);
