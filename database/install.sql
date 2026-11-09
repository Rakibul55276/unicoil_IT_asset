-- Windows inventory agent database extension for the existing asset database.
-- Select the application database before importing this file in phpMyAdmin.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `agent_api_token` (
  `token_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `token_name` varchar(100) NOT NULL,
  `token_hash` char(64) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `expires_at` datetime NULL,
  `last_used_at` datetime NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`token_id`),
  UNIQUE KEY `uq_agent_token_name` (`token_name`),
  UNIQUE KEY `uq_agent_token_hash` (`token_hash`),
  KEY `idx_agent_token_active` (`active`,`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `agent_device` (
  `device_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `device_uuid` char(36) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `assetNum` varchar(30) NULL,
  `asset_link_source` varchar(20) NOT NULL DEFAULT 'AUTO',
  `hostname` varchar(255) NOT NULL,
  `serial_number` varchar(100) NULL,
  `manufacturer` varchar(100) NULL,
  `model` varchar(100) NULL,
  `os_name` varchar(190) NULL,
  `os_version` varchar(50) NULL,
  `os_build` varchar(50) NULL,
  `os_architecture` varchar(30) NULL,
  `cpu_name` varchar(255) NULL,
  `cpu_cores` smallint unsigned NULL,
  `memory_bytes` bigint unsigned NULL,
  `primary_ip` varchar(45) NULL,
  `mac_address` varchar(17) NULL,
  `agent_version` varchar(30) NOT NULL,
  `last_boot_at` datetime NULL,
  `collected_at` datetime NULL,
  `inventory_hash` char(64) CHARACTER SET ascii COLLATE ascii_general_ci NULL,
  `last_seen_at` datetime NOT NULL,
  `last_inventory_at` datetime NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`device_id`),
  UNIQUE KEY `uq_agent_device_uuid` (`device_uuid`),
  KEY `idx_agent_device_asset` (`assetNum`),
  KEY `idx_agent_device_serial` (`serial_number`),
  KEY `idx_agent_device_seen` (`active`,`last_seen_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @agent_link_source_exists = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema=DATABASE() AND table_name='agent_device' AND column_name='asset_link_source'
);
SET @agent_link_source_sql = IF(
  @agent_link_source_exists=0,
  'ALTER TABLE `agent_device` ADD COLUMN `asset_link_source` varchar(20) NOT NULL DEFAULT ''AUTO'' AFTER `assetNum`',
  'SELECT 1'
);
PREPARE agent_link_source_stmt FROM @agent_link_source_sql;
EXECUTE agent_link_source_stmt;
DEALLOCATE PREPARE agent_link_source_stmt;

CREATE TABLE IF NOT EXISTS `agent_disk` (
  `disk_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `device_id` bigint unsigned NOT NULL,
  `device_key` varchar(50) NOT NULL,
  `volume_label` varchar(100) NULL,
  `filesystem` varchar(30) NULL,
  `size_bytes` bigint unsigned NULL,
  `free_bytes` bigint unsigned NULL,
  `health_status` varchar(30) NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`disk_id`),
  UNIQUE KEY `uq_agent_disk_device_key` (`device_id`,`device_key`),
  CONSTRAINT `fk_agent_disk_device` FOREIGN KEY (`device_id`)
    REFERENCES `agent_device` (`device_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `agent_software` (
  `software_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `device_id` bigint unsigned NOT NULL,
  `software_key` char(64) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `software_name` varchar(255) NOT NULL,
  `software_version` varchar(100) NULL,
  `publisher` varchar(190) NULL,
  `install_date` date NULL,
  `architecture` varchar(20) NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`software_id`),
  UNIQUE KEY `uq_agent_software_device_key` (`device_id`,`software_key`),
  KEY `idx_agent_software_name` (`software_name`),
  CONSTRAINT `fk_agent_software_device` FOREIGN KEY (`device_id`)
    REFERENCES `agent_device` (`device_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `agent_sync_log` (
  `sync_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `request_uuid` char(36) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `device_id` bigint unsigned NOT NULL,
  `token_id` bigint unsigned NOT NULL,
  `sync_status` varchar(20) NOT NULL,
  `source_ip` varchar(45) NULL,
  `disk_count` int unsigned NOT NULL DEFAULT 0,
  `software_count` int unsigned NOT NULL DEFAULT 0,
  `payload_hash` char(64) CHARACTER SET ascii COLLATE ascii_general_ci NULL,
  `error_message` varchar(500) NULL,
  `received_at` datetime NOT NULL,
  `completed_at` datetime NULL,
  PRIMARY KEY (`sync_id`),
  UNIQUE KEY `uq_agent_sync_request` (`request_uuid`),
  KEY `idx_agent_sync_device_date` (`device_id`,`received_at`),
  CONSTRAINT `fk_agent_sync_device` FOREIGN KEY (`device_id`)
    REFERENCES `agent_device` (`device_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_agent_sync_token` FOREIGN KEY (`token_id`)
    REFERENCES `agent_api_token` (`token_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT
  (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='agent_device') AS `agent_device_ready`,
  (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='agent_software') AS `agent_software_ready`,
  'Windows agent schema installed successfully' AS `result`;
