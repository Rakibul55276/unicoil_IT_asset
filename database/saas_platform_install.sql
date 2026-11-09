-- IT Asset Management SaaS control database
-- For this installation, import into central_asset_db.
-- The script only creates tables prefixed with saas_ and does not alter the
-- existing asset-management tables. Do not import into it_asset_management.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `saas_company` (
  `company_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'INACTIVE',
  `subscription_started_at` date DEFAULT NULL,
  `subscription_expires_at` date DEFAULT NULL,
  `grace_until` date DEFAULT NULL,
  `db_host` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `db_name` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `db_user` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `db_pass_encrypted` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  PRIMARY KEY (`company_id`),
  UNIQUE KEY `uq_saas_company_code` (`company_code`),
  KEY `idx_saas_company_status_expiry`
    (`status`,`subscription_expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `saas_platform_admin` (
  `admin_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `uq_saas_platform_admin_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `saas_voucher` (
  `voucher_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code_hash` char(64) COLLATE ascii_bin NOT NULL,
  `code_prefix` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `duration_days` int unsigned NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'UNUSED',
  `assigned_company_id` bigint unsigned DEFAULT NULL,
  `redeemed_company_id` bigint unsigned DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `created_by` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `redeemed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`voucher_id`),
  UNIQUE KEY `uq_saas_voucher_code_hash` (`code_hash`),
  KEY `idx_saas_voucher_status_valid` (`status`,`valid_until`),
  KEY `idx_saas_voucher_assigned_company` (`assigned_company_id`),
  KEY `idx_saas_voucher_redeemed_company` (`redeemed_company_id`),
  CONSTRAINT `fk_saas_voucher_assigned_company`
    FOREIGN KEY (`assigned_company_id`) REFERENCES `saas_company` (`company_id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_saas_voucher_redeemed_company`
    FOREIGN KEY (`redeemed_company_id`) REFERENCES `saas_company` (`company_id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `saas_voucher_redemption` (
  `redemption_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `voucher_id` bigint unsigned NOT NULL,
  `company_id` bigint unsigned NOT NULL,
  `redeemed_by` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `previous_expiry` date DEFAULT NULL,
  `new_expiry` date NOT NULL,
  `redeemed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`redemption_id`),
  UNIQUE KEY `uq_saas_redemption_voucher` (`voucher_id`),
  KEY `idx_saas_redemption_company_date` (`company_id`,`redeemed_at`),
  CONSTRAINT `fk_saas_redemption_voucher`
    FOREIGN KEY (`voucher_id`) REFERENCES `saas_voucher` (`voucher_id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_saas_redemption_company`
    FOREIGN KEY (`company_id`) REFERENCES `saas_company` (`company_id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
