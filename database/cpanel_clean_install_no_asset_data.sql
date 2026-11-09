-- IT Asset Management - clean cPanel installation
-- Generated from the current central_asset_db schema.
--
-- Contains:
--   * Complete application, workflow, Agent, reporting, optional accessory inventory,
--     and optional platform schema
--   * Organization/master records: categories, statuses, departments, employees,
--     employee-department mapping, locations, units, vendors, users, roles, migrations
--
-- Deliberately excludes:
--   * Assets and receipts
--   * Assignments, returns, requests, approvals, scrap, accessory stock and audit transactions
--   * Uploaded media and email logs
--   * Agent devices, inventory, sync logs and API tokens
--   * SaaS companies, vouchers, redemptions and platform administrators
--
-- cPanel/phpMyAdmin: create/select the destination database first, then Import
-- this file. No CREATE DATABASE, USE, local DEFINER, or plaintext credential is included.
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `agent_api_token`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agent_api_token` (
  `token_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `token_name` varchar(100) NOT NULL,
  `token_hash` char(64) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `expires_at` datetime DEFAULT NULL,
  `last_used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`token_id`),
  UNIQUE KEY `uq_agent_token_name` (`token_name`),
  UNIQUE KEY `uq_agent_token_hash` (`token_hash`),
  KEY `idx_agent_token_active` (`active`,`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `agent_device`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agent_device` (
  `device_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `device_uuid` char(36) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `assetNum` varchar(30) DEFAULT NULL,
  `asset_link_source` varchar(20) NOT NULL DEFAULT 'AUTO',
  `hostname` varchar(255) NOT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `os_name` varchar(190) DEFAULT NULL,
  `os_version` varchar(50) DEFAULT NULL,
  `os_build` varchar(50) DEFAULT NULL,
  `os_architecture` varchar(30) DEFAULT NULL,
  `cpu_name` varchar(255) DEFAULT NULL,
  `cpu_cores` smallint(5) unsigned DEFAULT NULL,
  `memory_bytes` bigint(20) unsigned DEFAULT NULL,
  `primary_ip` varchar(45) DEFAULT NULL,
  `mac_address` varchar(17) DEFAULT NULL,
  `agent_version` varchar(30) NOT NULL,
  `last_boot_at` datetime DEFAULT NULL,
  `collected_at` datetime DEFAULT NULL,
  `inventory_hash` char(64) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
  `last_seen_at` datetime NOT NULL,
  `last_inventory_at` datetime NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`device_id`),
  UNIQUE KEY `uq_agent_device_uuid` (`device_uuid`),
  KEY `idx_agent_device_asset` (`assetNum`),
  KEY `idx_agent_device_serial` (`serial_number`),
  KEY `idx_agent_device_seen` (`active`,`last_seen_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `agent_disk`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agent_disk` (
  `disk_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `device_id` bigint(20) unsigned NOT NULL,
  `device_key` varchar(50) NOT NULL,
  `volume_label` varchar(100) DEFAULT NULL,
  `filesystem` varchar(30) DEFAULT NULL,
  `size_bytes` bigint(20) unsigned DEFAULT NULL,
  `free_bytes` bigint(20) unsigned DEFAULT NULL,
  `health_status` varchar(30) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`disk_id`),
  UNIQUE KEY `uq_agent_disk_device_key` (`device_id`,`device_key`),
  CONSTRAINT `fk_agent_disk_device` FOREIGN KEY (`device_id`) REFERENCES `agent_device` (`device_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `agent_software`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agent_software` (
  `software_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `device_id` bigint(20) unsigned NOT NULL,
  `software_key` char(64) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `software_name` varchar(255) NOT NULL,
  `software_version` varchar(100) DEFAULT NULL,
  `publisher` varchar(190) DEFAULT NULL,
  `install_date` date DEFAULT NULL,
  `architecture` varchar(20) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`software_id`),
  UNIQUE KEY `uq_agent_software_device_key` (`device_id`,`software_key`),
  KEY `idx_agent_software_name` (`software_name`),
  CONSTRAINT `fk_agent_software_device` FOREIGN KEY (`device_id`) REFERENCES `agent_device` (`device_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `agent_sync_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agent_sync_log` (
  `sync_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request_uuid` char(36) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `device_id` bigint(20) unsigned NOT NULL,
  `token_id` bigint(20) unsigned NOT NULL,
  `sync_status` varchar(20) NOT NULL,
  `source_ip` varchar(45) DEFAULT NULL,
  `disk_count` int(10) unsigned NOT NULL DEFAULT 0,
  `software_count` int(10) unsigned NOT NULL DEFAULT 0,
  `payload_hash` char(64) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
  `error_message` varchar(500) DEFAULT NULL,
  `received_at` datetime NOT NULL,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`sync_id`),
  UNIQUE KEY `uq_agent_sync_request` (`request_uuid`),
  KEY `idx_agent_sync_device_date` (`device_id`,`received_at`),
  KEY `fk_agent_sync_token` (`token_id`),
  CONSTRAINT `fk_agent_sync_device` FOREIGN KEY (`device_id`) REFERENCES `agent_device` (`device_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_agent_sync_token` FOREIGN KEY (`token_id`) REFERENCES `agent_api_token` (`token_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asset_deletion_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asset_deletion_audit` (
  `deletion_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `asset_num` varchar(30) NOT NULL,
  `asset_description` varchar(255) NOT NULL,
  `category_name` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `archive_image` varchar(255) DEFAULT NULL,
  `deleted_by` varchar(50) NOT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deletion_reason` varchar(500) NOT NULL,
  `asset_snapshot` longtext NOT NULL,
  `transaction_snapshot` longtext NOT NULL,
  `media_snapshot` longtext NOT NULL,
  PRIMARY KEY (`deletion_id`),
  KEY `idx_deleted_asset_date` (`deleted_at`,`asset_num`),
  KEY `fk_deleted_asset_user` (`deleted_by`),
  KEY `idx_deletion_date_id` (`deleted_at`,`deletion_id`),
  KEY `idx_deletion_asset_date` (`asset_num`,`deleted_at`),
  CONSTRAINT `fk_deleted_asset_user` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`username`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asset_issue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asset_issue` (
  `issue_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `issue_no` varchar(30) NOT NULL,
  `batch_no` varchar(30) DEFAULT NULL,
  `assetNum` varchar(30) NOT NULL,
  `assignee_type` enum('DEPARTMENT','USER') NOT NULL,
  `deptId` varchar(10) DEFAULT NULL,
  `empId` int(11) DEFAULT NULL,
  `issue_date` datetime NOT NULL DEFAULT current_timestamp(),
  `expected_return_date` date DEFAULT NULL,
  `condition_out` enum('NEW','GOOD','FAIR','DAMAGED') NOT NULL DEFAULT 'GOOD',
  `purpose` varchar(255) DEFAULT NULL,
  `issued_by` varchar(50) NOT NULL,
  `acknowledged_by` varchar(150) DEFAULT NULL,
  `status` enum('ISSUED','RETURNED','SCRAPPED','CANCELLED') NOT NULL DEFAULT 'ISSUED',
  `returned_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`issue_id`),
  UNIQUE KEY `uq_issue_no` (`issue_no`),
  KEY `idx_issue_employee` (`empId`,`status`),
  KEY `idx_issue_department` (`deptId`,`status`),
  KEY `idx_issue_date` (`issue_date`),
  KEY `idx_issue_asset` (`assetNum`),
  KEY `fk_issue_user` (`issued_by`),
  KEY `idx_issue_status_date_id` (`status`,`issue_date`,`issue_id`),
  KEY `idx_issue_status_asset` (`status`,`assetNum`),
  KEY `idx_issue_batch_status_id` (`batch_no`,`status`,`issue_id`),
  KEY `idx_issue_user_status_id` (`assignee_type`,`empId`,`status`,`issue_id`),
  KEY `idx_issue_dept_status_id` (`assignee_type`,`deptId`,`status`,`issue_id`),
  KEY `idx_issue_date_id` (`issue_date`,`issue_id`),
  CONSTRAINT `fk_issue_asset` FOREIGN KEY (`assetNum`) REFERENCES `assetdata` (`assetNum`) ON UPDATE CASCADE,
  CONSTRAINT `fk_issue_department` FOREIGN KEY (`deptId`) REFERENCES `department` (`deptId`) ON UPDATE CASCADE,
  CONSTRAINT `fk_issue_employee` FOREIGN KEY (`empId`) REFERENCES `employee` (`empId`) ON UPDATE CASCADE,
  CONSTRAINT `fk_issue_user` FOREIGN KEY (`issued_by`) REFERENCES `users` (`username`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asset_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asset_media` (
  `media_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `assetNum` varchar(30) NOT NULL,
  `issue_id` bigint(20) unsigned DEFAULT NULL,
  `return_id` bigint(20) unsigned DEFAULT NULL,
  `media_stage` enum('ENTRY','ASSIGN','RETURN') NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_size` int(10) unsigned NOT NULL,
  `uploaded_by` varchar(50) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`media_id`),
  KEY `idx_asset_media_asset_stage` (`assetNum`,`media_stage`),
  KEY `idx_asset_media_issue` (`issue_id`),
  KEY `idx_asset_media_return` (`return_id`),
  KEY `fk_asset_media_user` (`uploaded_by`),
  KEY `idx_media_asset_stage_id` (`assetNum`,`media_stage`,`media_id`),
  CONSTRAINT `fk_asset_media_asset` FOREIGN KEY (`assetNum`) REFERENCES `assetdata` (`assetNum`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_asset_media_issue` FOREIGN KEY (`issue_id`) REFERENCES `asset_issue` (`issue_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_asset_media_return` FOREIGN KEY (`return_id`) REFERENCES `asset_return` (`return_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_asset_media_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`username`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asset_receipt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asset_receipt` (
  `receipt_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `receipt_no` varchar(30) NOT NULL,
  `received_date` date NOT NULL,
  `supplier_name` varchar(150) DEFAULT NULL,
  `vendor_id` int(10) unsigned DEFAULT NULL,
  `po_number` varchar(50) DEFAULT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  `invoice_pdf` varchar(255) DEFAULT NULL,
  `received_by` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`receipt_id`),
  KEY `idx_receipt_no` (`receipt_no`),
  KEY `idx_receipt_vendor` (`vendor_id`),
  KEY `idx_receipt_user` (`received_by`),
  KEY `idx_receipt_date_id` (`received_date`,`receipt_id`),
  CONSTRAINT `fk_receipt_user` FOREIGN KEY (`received_by`) REFERENCES `users` (`username`) ON UPDATE CASCADE,
  CONSTRAINT `fk_receipt_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendor` (`vendor_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asset_receipt_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asset_receipt_item` (
  `receipt_item_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `receipt_id` bigint(20) unsigned NOT NULL,
  `assetNum` varchar(30) NOT NULL,
  `line_no` int(10) unsigned NOT NULL,
  `condition_on_receipt` enum('NEW','USED','REFURBISHED') NOT NULL DEFAULT 'NEW',
  `remarks` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`receipt_item_id`),
  UNIQUE KEY `uq_receipt_line` (`receipt_id`,`line_no`),
  UNIQUE KEY `uq_receipt_asset` (`assetNum`),
  CONSTRAINT `fk_receipt_item_asset` FOREIGN KEY (`assetNum`) REFERENCES `assetdata` (`assetNum`) ON UPDATE CASCADE,
  CONSTRAINT `fk_receipt_item_header` FOREIGN KEY (`receipt_id`) REFERENCES `asset_receipt` (`receipt_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asset_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asset_request` (
  `request_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request_no` varchar(30) NOT NULL,
  `requested_by` varchar(50) NOT NULL,
  `requested_for` int(11) DEFAULT NULL,
  `receiver_emp_id` int(11) DEFAULT NULL,
  `deptId` varchar(10) NOT NULL,
  `request_target_type` varchar(20) NOT NULL DEFAULT 'USER',
  `request_source` varchar(20) NOT NULL DEFAULT 'SELF',
  `business_reason` varchar(500) NOT NULL,
  `required_date` date DEFAULT NULL,
  `work_location` varchar(150) DEFAULT NULL,
  `status` varchar(40) NOT NULL DEFAULT 'PENDING_DEPARTMENT_APPROVAL',
  `department_approved_by` varchar(50) DEFAULT NULL,
  `department_approved_at` datetime DEFAULT NULL,
  `it_approved_by` varchar(50) DEFAULT NULL,
  `it_approved_at` datetime DEFAULT NULL,
  `issued_by` varchar(50) DEFAULT NULL,
  `issued_at` datetime DEFAULT NULL,
  `receiver_accepted_at` datetime DEFAULT NULL,
  `receiver_accepted_by` varchar(50) DEFAULT NULL,
  `receiver_comment` varchar(500) DEFAULT NULL,
  `rejection_reason` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`request_id`),
  UNIQUE KEY `uq_asset_request_no` (`request_no`),
  KEY `idx_asset_request_status` (`status`,`created_at`),
  KEY `idx_asset_request_employee` (`requested_for`,`created_at`),
  KEY `idx_asset_request_department` (`deptId`,`status`),
  KEY `fk_asset_request_user` (`requested_by`),
  KEY `idx_asset_request_receiver` (`receiver_emp_id`),
  KEY `idx_request_status_created_id` (`status`,`created_at`,`request_id`),
  KEY `idx_request_employee_created_id` (`requested_for`,`created_at`,`request_id`),
  KEY `idx_request_department_created_id` (`deptId`,`created_at`,`request_id`),
  CONSTRAINT `fk_asset_request_department` FOREIGN KEY (`deptId`) REFERENCES `department` (`deptId`) ON UPDATE CASCADE,
  CONSTRAINT `fk_asset_request_employee` FOREIGN KEY (`requested_for`) REFERENCES `employee` (`empId`) ON UPDATE CASCADE,
  CONSTRAINT `fk_asset_request_receiver` FOREIGN KEY (`receiver_emp_id`) REFERENCES `employee` (`empId`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_asset_request_user` FOREIGN KEY (`requested_by`) REFERENCES `users` (`username`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asset_request_allocation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asset_request_allocation` (
  `allocation_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request_item_id` bigint(20) unsigned NOT NULL,
  `assetNum` varchar(30) NOT NULL,
  `issue_id` bigint(20) unsigned NOT NULL,
  `allocated_by` varchar(50) NOT NULL,
  `allocated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`allocation_id`),
  UNIQUE KEY `uq_request_allocation_asset` (`request_item_id`,`assetNum`),
  UNIQUE KEY `uq_request_allocation_issue` (`issue_id`),
  KEY `idx_request_allocation_asset` (`assetNum`),
  CONSTRAINT `fk_request_allocation_asset` FOREIGN KEY (`assetNum`) REFERENCES `assetdata` (`assetNum`) ON UPDATE CASCADE,
  CONSTRAINT `fk_request_allocation_issue` FOREIGN KEY (`issue_id`) REFERENCES `asset_issue` (`issue_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_request_allocation_item` FOREIGN KEY (`request_item_id`) REFERENCES `asset_request_item` (`request_item_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asset_request_approval_step`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asset_request_approval_step` (
  `approval_step_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request_id` bigint(20) unsigned NOT NULL,
  `step_order` smallint(5) unsigned NOT NULL,
  `step_type` varchar(30) NOT NULL,
  `approver_username` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'QUEUED',
  `acted_by` varchar(50) DEFAULT NULL,
  `acted_at` datetime DEFAULT NULL,
  `remarks` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`approval_step_id`),
  UNIQUE KEY `uq_request_approval_order` (`request_id`,`step_order`),
  KEY `idx_request_approval_queue` (`approver_username`,`status`),
  KEY `fk_request_approval_actor` (`acted_by`),
  KEY `idx_approval_user_status_step` (`approver_username`,`status`,`step_order`),
  CONSTRAINT `fk_request_approval_actor` FOREIGN KEY (`acted_by`) REFERENCES `users` (`username`) ON UPDATE CASCADE,
  CONSTRAINT `fk_request_approval_request` FOREIGN KEY (`request_id`) REFERENCES `asset_request` (`request_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_request_approval_user` FOREIGN KEY (`approver_username`) REFERENCES `users` (`username`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asset_request_event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asset_request_event` (
  `event_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request_id` bigint(20) unsigned NOT NULL,
  `event_type` varchar(40) NOT NULL,
  `from_status` varchar(40) DEFAULT NULL,
  `to_status` varchar(40) DEFAULT NULL,
  `actor_username` varchar(50) NOT NULL,
  `remarks` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`event_id`),
  KEY `idx_request_event_request` (`request_id`,`created_at`),
  KEY `fk_request_event_actor` (`actor_username`),
  CONSTRAINT `fk_request_event_actor` FOREIGN KEY (`actor_username`) REFERENCES `users` (`username`) ON UPDATE CASCADE,
  CONSTRAINT `fk_request_event_request` FOREIGN KEY (`request_id`) REFERENCES `asset_request` (`request_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asset_request_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asset_request_item` (
  `request_item_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request_id` bigint(20) unsigned NOT NULL,
  `assetKat` varchar(10) NOT NULL,
  `quantity` smallint(5) unsigned NOT NULL DEFAULT 1,
  `approved_quantity` smallint(5) unsigned DEFAULT NULL,
  `it_specification` varchar(500) DEFAULT NULL,
  `item_status` varchar(30) NOT NULL DEFAULT 'REQUESTED',
  PRIMARY KEY (`request_item_id`),
  UNIQUE KEY `uq_request_category` (`request_id`,`assetKat`),
  KEY `idx_request_item_category` (`assetKat`),
  CONSTRAINT `fk_request_item_category` FOREIGN KEY (`assetKat`) REFERENCES `assetkat` (`assetKat`) ON UPDATE CASCADE,
  CONSTRAINT `fk_request_item_request` FOREIGN KEY (`request_id`) REFERENCES `asset_request` (`request_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asset_return`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asset_return` (
  `return_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `return_no` varchar(30) NOT NULL,
  `batch_no` varchar(30) DEFAULT NULL,
  `issue_id` bigint(20) unsigned NOT NULL,
  `return_date` datetime NOT NULL DEFAULT current_timestamp(),
  `return_type` enum('REISSUE','SCRAP') NOT NULL,
  `condition_in` enum('GOOD','FAIR','DAMAGED','BEYOND_REPAIR') NOT NULL,
  `return_location` varchar(10) DEFAULT NULL,
  `reason` varchar(500) DEFAULT NULL,
  `scrap_reference` varchar(100) DEFAULT NULL,
  `received_by` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`return_id`),
  UNIQUE KEY `uq_return_no` (`return_no`),
  UNIQUE KEY `uq_one_return_per_issue` (`issue_id`),
  KEY `idx_return_type_date` (`return_type`,`return_date`),
  KEY `idx_return_condition_date` (`condition_in`,`return_date`),
  KEY `fk_return_location` (`return_location`),
  KEY `fk_return_user` (`received_by`),
  KEY `idx_return_date_id` (`return_date`,`return_id`),
  KEY `idx_return_batch_date_id` (`batch_no`,`return_date`,`return_id`),
  CONSTRAINT `fk_return_issue` FOREIGN KEY (`issue_id`) REFERENCES `asset_issue` (`issue_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_return_location` FOREIGN KEY (`return_location`) REFERENCES `location` (`location`) ON UPDATE CASCADE,
  CONSTRAINT `fk_return_user` FOREIGN KEY (`received_by`) REFERENCES `users` (`username`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asset_return_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asset_return_request` (
  `return_request_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `return_request_no` varchar(30) NOT NULL,
  `issue_id` bigint(20) unsigned NOT NULL,
  `requested_by` varchar(50) NOT NULL,
  `reason` varchar(500) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'PENDING',
  `return_id` bigint(20) unsigned DEFAULT NULL,
  `processed_by` varchar(50) DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `cancelled_by` varchar(50) DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancellation_reason` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`return_request_id`),
  UNIQUE KEY `uq_return_request_no` (`return_request_no`),
  KEY `idx_return_request_issue` (`issue_id`,`status`),
  KEY `idx_return_request_status` (`status`,`created_at`),
  KEY `fk_return_request_user` (`requested_by`),
  KEY `fk_return_request_return` (`return_id`),
  KEY `idx_return_request_created_id` (`created_at`,`return_request_id`),
  KEY `idx_return_request_status_created` (`status`,`created_at`),
  CONSTRAINT `fk_return_request_issue` FOREIGN KEY (`issue_id`) REFERENCES `asset_issue` (`issue_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_return_request_return` FOREIGN KEY (`return_id`) REFERENCES `asset_return` (`return_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_return_request_user` FOREIGN KEY (`requested_by`) REFERENCES `users` (`username`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asset_scrap_event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asset_scrap_event` (
  `scrap_event_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `scrap_request_id` bigint(20) unsigned NOT NULL,
  `event_type` varchar(30) NOT NULL,
  `from_status` varchar(30) DEFAULT NULL,
  `to_status` varchar(30) DEFAULT NULL,
  `actor_username` varchar(50) NOT NULL,
  `remarks` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`scrap_event_id`),
  KEY `idx_scrap_event_request` (`scrap_request_id`,`created_at`),
  KEY `fk_scrap_event_actor` (`actor_username`),
  CONSTRAINT `fk_scrap_event_actor` FOREIGN KEY (`actor_username`) REFERENCES `users` (`username`) ON UPDATE CASCADE,
  CONSTRAINT `fk_scrap_event_request` FOREIGN KEY (`scrap_request_id`) REFERENCES `asset_scrap_request` (`scrap_request_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asset_scrap_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asset_scrap_request` (
  `scrap_request_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `scrap_request_no` varchar(30) NOT NULL,
  `assetNum` varchar(30) NOT NULL,
  `requested_by` varchar(50) NOT NULL,
  `reason` varchar(500) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'PENDING_IT_APPROVAL',
  `reviewed_by` varchar(50) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_comment` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`scrap_request_id`),
  UNIQUE KEY `uq_scrap_request_no` (`scrap_request_no`),
  KEY `idx_scrap_request_asset` (`assetNum`,`status`),
  KEY `idx_scrap_request_status` (`status`,`created_at`),
  KEY `fk_scrap_request_user` (`requested_by`),
  KEY `idx_scrap_created_id` (`created_at`,`scrap_request_id`),
  KEY `idx_scrap_reviewed_id` (`reviewed_at`,`scrap_request_id`),
  CONSTRAINT `fk_scrap_request_asset` FOREIGN KEY (`assetNum`) REFERENCES `assetdata` (`assetNum`) ON UPDATE CASCADE,
  CONSTRAINT `fk_scrap_request_user` FOREIGN KEY (`requested_by`) REFERENCES `users` (`username`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asset_update_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asset_update_audit` (
  `update_audit_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `assetdata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assetdata` (
  `assetNum` varchar(30) NOT NULL,
  `assetKat` varchar(10) NOT NULL,
  `Description` varchar(255) NOT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_cost` decimal(14,2) DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `receipt_id` bigint(20) unsigned DEFAULT NULL,
  `empId` int(11) DEFAULT NULL,
  `deptId` varchar(10) DEFAULT NULL,
  `createdBy` varchar(50) NOT NULL,
  `createdDate` date NOT NULL,
  `quantity` decimal(12,2) NOT NULL DEFAULT 1.00,
  `unit` varchar(10) NOT NULL DEFAULT 'Unit',
  `assetStat` varchar(20) NOT NULL DEFAULT 'IN_STOCK',
  `asset_condition` enum('NEW','GOOD','FAIR','DAMAGED','BEYOND_REPAIR') NOT NULL DEFAULT 'NEW',
  `location` varchar(10) DEFAULT NULL,
  `assetImage` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`assetNum`),
  UNIQUE KEY `uq_asset_serial` (`serial_number`),
  KEY `idx_asset_category` (`assetKat`),
  KEY `idx_asset_status` (`assetStat`),
  KEY `idx_asset_employee` (`empId`),
  KEY `idx_asset_department` (`deptId`),
  KEY `idx_asset_receipt` (`receipt_id`),
  KEY `idx_asset_location` (`location`),
  KEY `fk_asset_created_by` (`createdBy`),
  KEY `idx_asset_status_number` (`assetStat`,`assetNum`),
  KEY `idx_asset_status_category_number` (`assetStat`,`assetKat`,`assetNum`),
  KEY `idx_asset_category_serial` (`assetKat`,`serial_number`),
  KEY `idx_asset_created_number` (`createdDate`,`assetNum`),
  KEY `idx_asset_description_prefix` (`Description`(80)),
  KEY `idx_asset_warranty_status` (`warranty_expiry`,`assetStat`,`assetNum`),
  KEY `idx_asset_purchase_warranty` (`purchase_date`,`warranty_expiry`,`assetNum`),
  KEY `idx_asset_status_condition_number` (`assetStat`,`asset_condition`,`assetNum`),
  CONSTRAINT `fk_asset_category` FOREIGN KEY (`assetKat`) REFERENCES `assetkat` (`assetKat`) ON UPDATE CASCADE,
  CONSTRAINT `fk_asset_created_by` FOREIGN KEY (`createdBy`) REFERENCES `users` (`username`) ON UPDATE CASCADE,
  CONSTRAINT `fk_asset_department` FOREIGN KEY (`deptId`) REFERENCES `department` (`deptId`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_asset_employee` FOREIGN KEY (`empId`) REFERENCES `employee` (`empId`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_asset_location` FOREIGN KEY (`location`) REFERENCES `location` (`location`) ON UPDATE CASCADE,
  CONSTRAINT `fk_asset_receipt` FOREIGN KEY (`receipt_id`) REFERENCES `asset_receipt` (`receipt_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_asset_status` FOREIGN KEY (`assetStat`) REFERENCES `assetstat` (`assetStatus`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `assetkat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assetkat` (
  `assetKat` varchar(10) NOT NULL,
  `Description` varchar(100) NOT NULL,
  `asset_prefix` varchar(6) NOT NULL,
  `next_asset_sequence` bigint(20) unsigned NOT NULL DEFAULT 1,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`assetKat`),
  UNIQUE KEY `uq_assetkat_description` (`Description`),
  UNIQUE KEY `uq_assetkat_prefix` (`asset_prefix`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `assetstat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assetstat` (
  `assetStatus` varchar(20) NOT NULL,
  `Description` varchar(100) NOT NULL,
  `is_terminal` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`assetStatus`),
  UNIQUE KEY `uq_assetstat_description` (`Description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `assignment_email_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assignment_email_log` (
  `email_log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `issue_id` bigint(20) unsigned NOT NULL,
  `notification_type` enum('ASSIGN','RETURN') NOT NULL DEFAULT 'ASSIGN',
  `recipient_email` varchar(190) DEFAULT NULL,
  `delivery_status` enum('SENT','FAILED','SKIPPED') NOT NULL,
  `attachment_count` int(10) unsigned NOT NULL DEFAULT 0,
  `error_message` varchar(500) DEFAULT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`email_log_id`),
  KEY `idx_assignment_email_issue` (`issue_id`),
  KEY `idx_assignment_email_status` (`delivery_status`,`attempted_at`),
  CONSTRAINT `fk_assignment_email_issue` FOREIGN KEY (`issue_id`) REFERENCES `asset_issue` (`issue_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `department`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `department` (
  `deptId` varchar(10) NOT NULL,
  `deptPrefix` varchar(10) DEFAULT NULL,
  `deptName` varchar(100) NOT NULL,
  `area` varchar(100) DEFAULT NULL,
  `building` varchar(100) DEFAULT NULL,
  `headEmpId` int(11) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`deptId`),
  UNIQUE KEY `uq_department_name` (`deptName`),
  UNIQUE KEY `uq_department_prefix` (`deptPrefix`),
  KEY `idx_department_head` (`headEmpId`),
  CONSTRAINT `fk_department_head` FOREIGN KEY (`headEmpId`) REFERENCES `employee` (`empId`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `department_head`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `department_head` (
  `deptId` varchar(10) NOT NULL,
  `empId` int(11) NOT NULL,
  `is_superior` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`deptId`,`empId`),
  KEY `idx_department_head_employee` (`empId`),
  KEY `idx_department_head_superior` (`deptId`,`is_superior`),
  CONSTRAINT `fk_department_head_department` FOREIGN KEY (`deptId`) REFERENCES `department` (`deptId`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_department_head_employee` FOREIGN KEY (`empId`) REFERENCES `employee` (`empId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `empdept`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `empdept` (
  `empId` int(11) NOT NULL,
  `deptId` varchar(10) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `assignedFrom` date DEFAULT NULL,
  `assignedTo` date DEFAULT NULL,
  PRIMARY KEY (`empId`,`deptId`),
  KEY `idx_empdept_dept` (`deptId`,`active`),
  CONSTRAINT `fk_empdept_department` FOREIGN KEY (`deptId`) REFERENCES `department` (`deptId`) ON UPDATE CASCADE,
  CONSTRAINT `fk_empdept_employee` FOREIGN KEY (`empId`) REFERENCES `employee` (`empId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employee`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee` (
  `empId` int(11) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `fullname` varchar(101) NOT NULL,
  `email` varchar(190) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `area` varchar(100) DEFAULT NULL,
  `building` varchar(100) DEFAULT NULL,
  `extension_number` varchar(30) DEFAULT NULL,
  `section` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `deptId` varchar(10) DEFAULT NULL,
  `supervisorEmpId` int(11) DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `assetNum` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`empId`),
  KEY `idx_employee_name` (`fullname`),
  KEY `idx_employee_dept` (`deptId`),
  KEY `idx_employee_email` (`email`),
  KEY `idx_employee_supervisor` (`supervisorEmpId`),
  KEY `idx_employee_active_name_id` (`aktif`,`fullname`,`empId`),
  KEY `idx_employee_active_dept_name` (`aktif`,`deptId`,`fullname`),
  CONSTRAINT `fk_employee_department` FOREIGN KEY (`deptId`) REFERENCES `department` (`deptId`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_employee_supervisor` FOREIGN KEY (`supervisorEmpId`) REFERENCES `employee` (`empId`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `location`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `location` (
  `location` varchar(10) NOT NULL,
  `description` varchar(100) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`location`),
  UNIQUE KEY `uq_location_description` (`description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `saas_company`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `saas_company` (
  `company_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_code` varchar(30) NOT NULL,
  `company_name` varchar(150) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'INACTIVE',
  `subscription_started_at` date DEFAULT NULL,
  `subscription_expires_at` date DEFAULT NULL,
  `grace_until` date DEFAULT NULL,
  `db_host` varchar(190) NOT NULL DEFAULT '',
  `db_name` varchar(190) NOT NULL,
  `db_user` varchar(190) NOT NULL DEFAULT '',
  `db_pass_encrypted` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`company_id`),
  UNIQUE KEY `uq_saas_company_code` (`company_code`),
  KEY `idx_saas_company_status_expiry` (`status`,`subscription_expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `saas_platform_admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `saas_platform_admin` (
  `admin_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `uq_saas_platform_admin_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `saas_voucher`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `saas_voucher` (
  `voucher_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code_prefix` varchar(20) NOT NULL,
  `duration_days` int(10) unsigned NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'UNUSED',
  `assigned_company_id` bigint(20) unsigned DEFAULT NULL,
  `redeemed_company_id` bigint(20) unsigned DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `created_by` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `redeemed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`voucher_id`),
  UNIQUE KEY `uq_saas_voucher_code_hash` (`code_hash`),
  KEY `idx_saas_voucher_status_valid` (`status`,`valid_until`),
  KEY `idx_saas_voucher_assigned_company` (`assigned_company_id`),
  KEY `idx_saas_voucher_redeemed_company` (`redeemed_company_id`),
  CONSTRAINT `fk_saas_voucher_assigned_company` FOREIGN KEY (`assigned_company_id`) REFERENCES `saas_company` (`company_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_saas_voucher_redeemed_company` FOREIGN KEY (`redeemed_company_id`) REFERENCES `saas_company` (`company_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `saas_voucher_redemption`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `saas_voucher_redemption` (
  `redemption_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `voucher_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `redeemed_by` varchar(100) NOT NULL,
  `previous_expiry` date DEFAULT NULL,
  `new_expiry` date NOT NULL,
  `redeemed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`redemption_id`),
  UNIQUE KEY `uq_saas_redemption_voucher` (`voucher_id`),
  KEY `idx_saas_redemption_company_date` (`company_id`,`redeemed_at`),
  CONSTRAINT `fk_saas_redemption_company` FOREIGN KEY (`company_id`) REFERENCES `saas_company` (`company_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_saas_redemption_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `saas_voucher` (`voucher_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `schema_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `schema_migrations` (
  `version` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL,
  `batch` int(10) unsigned NOT NULL DEFAULT 1,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`version`),
  KEY `idx_schema_migrations_batch` (`batch`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `unit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `unit` (
  `idx` int(11) NOT NULL AUTO_INCREMENT,
  `unit` varchar(10) NOT NULL,
  `description` varchar(50) NOT NULL,
  PRIMARY KEY (`idx`),
  UNIQUE KEY `uq_unit_code` (`unit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_role` (
  `username` varchar(50) NOT NULL,
  `role_code` varchar(40) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `assigned_by` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`username`,`role_code`),
  KEY `idx_user_role_code` (`role_code`),
  CONSTRAINT `fk_user_role_user` FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `empId` int(11) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `registeredOn` date NOT NULL,
  PRIMARY KEY (`username`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_employee` (`empId`),
  CONSTRAINT `fk_users_employee` FOREIGN KEY (`empId`) REFERENCES `employee` (`empId`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `accessory_transaction`;
DROP TABLE IF EXISTS `accessory_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accessory_item` (
  `accessory_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `accessory_code` varchar(30) NOT NULL,
  `accessory_name` varchar(150) NOT NULL,
  `category` varchar(100) NOT NULL,
  `unit` varchar(20) NOT NULL DEFAULT 'PCS',
  `quantity_on_hand` decimal(14,3) unsigned NOT NULL DEFAULT 0.000,
  `reorder_level` decimal(14,3) unsigned NOT NULL DEFAULT 0.000,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`accessory_id`),
  UNIQUE KEY `uq_accessory_code` (`accessory_code`),
  KEY `idx_accessory_name` (`accessory_name`),
  KEY `idx_accessory_category_active` (`category`,`active`),
  KEY `idx_accessory_stock` (`active`,`quantity_on_hand`),
  CONSTRAINT `fk_accessory_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`username`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `accessory_transaction` (
  `transaction_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `transaction_no` varchar(40) NOT NULL,
  `accessory_id` bigint(20) unsigned NOT NULL,
  `event_type` enum('RECEIPT','ISSUE','RETURN','ADJUSTMENT_IN','ADJUSTMENT_OUT') NOT NULL,
  `quantity` decimal(14,3) unsigned NOT NULL,
  `balance_before` decimal(14,3) unsigned NOT NULL,
  `balance_after` decimal(14,3) unsigned NOT NULL,
  `recipient_type` enum('EMPLOYEE','DEPARTMENT','NONE') NOT NULL DEFAULT 'NONE',
  `empId` int(11) DEFAULT NULL,
  `deptId` varchar(10) DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `remarks` varchar(500) NOT NULL,
  `performed_by` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`transaction_id`),
  UNIQUE KEY `uq_accessory_transaction_no` (`transaction_no`),
  KEY `idx_accessory_transaction_item_date` (`accessory_id`,`created_at`),
  KEY `idx_accessory_transaction_event_date` (`event_type`,`created_at`),
  KEY `idx_accessory_transaction_employee` (`empId`,`created_at`),
  KEY `idx_accessory_transaction_department` (`deptId`,`created_at`),
  KEY `idx_accessory_transaction_actor` (`performed_by`,`created_at`),
  CONSTRAINT `fk_accessory_transaction_actor` FOREIGN KEY (`performed_by`) REFERENCES `users` (`username`) ON UPDATE CASCADE,
  CONSTRAINT `fk_accessory_transaction_department` FOREIGN KEY (`deptId`) REFERENCES `department` (`deptId`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_accessory_transaction_employee` FOREIGN KEY (`empId`) REFERENCES `employee` (`empId`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_accessory_transaction_item` FOREIGN KEY (`accessory_id`) REFERENCES `accessory_item` (`accessory_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `v_asset_batch_receipts`;
/*!50001 DROP VIEW IF EXISTS `v_asset_batch_receipts`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_asset_batch_receipts` AS SELECT
 1 AS `receipt_id`,
  1 AS `receipt_no`,
  1 AS `received_date`,
  1 AS `supplier_name`,
  1 AS `vendor_id`,
  1 AS `po_number`,
  1 AS `invoice_number`,
  1 AS `received_by`,
  1 AS `line_no`,
  1 AS `condition_on_receipt`,
  1 AS `remarks`,
  1 AS `assetNum`,
  1 AS `asset_description`,
  1 AS `serial_number`,
  1 AS `category_code`,
  1 AS `category_name`,
  1 AS `manufacturer`,
  1 AS `model`,
  1 AS `purchase_date`,
  1 AS `purchase_cost`,
  1 AS `warranty_expiry`,
  1 AS `assetStat`,
  1 AS `location` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_asset_current_assignment`;
/*!50001 DROP VIEW IF EXISTS `v_asset_current_assignment`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_asset_current_assignment` AS SELECT
 1 AS `issue_id`,
  1 AS `issue_no`,
  1 AS `issue_date`,
  1 AS `assetNum`,
  1 AS `asset_description`,
  1 AS `assetKat`,
  1 AS `category`,
  1 AS `assignee_type`,
  1 AS `empId`,
  1 AS `employee_name`,
  1 AS `deptId`,
  1 AS `deptName`,
  1 AS `condition_out`,
  1 AS `expected_return_date`,
  1 AS `purpose`,
  1 AS `status`,
  1 AS `issued_by`,
  1 AS `returned_at` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_asset_issue_history`;
/*!50001 DROP VIEW IF EXISTS `v_asset_issue_history`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_asset_issue_history` AS SELECT
 1 AS `issue_id`,
  1 AS `issue_no`,
  1 AS `issue_date`,
  1 AS `assetNum`,
  1 AS `asset_description`,
  1 AS `assetKat`,
  1 AS `category`,
  1 AS `assignee_type`,
  1 AS `empId`,
  1 AS `employee_name`,
  1 AS `deptId`,
  1 AS `deptName`,
  1 AS `condition_out`,
  1 AS `expected_return_date`,
  1 AS `purpose`,
  1 AS `status`,
  1 AS `issued_by`,
  1 AS `returned_at` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_asset_return_history`;
/*!50001 DROP VIEW IF EXISTS `v_asset_return_history`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_asset_return_history` AS SELECT
 1 AS `return_id`,
  1 AS `return_no`,
  1 AS `return_date`,
  1 AS `return_type`,
  1 AS `condition_in`,
  1 AS `reason`,
  1 AS `scrap_reference`,
  1 AS `return_location`,
  1 AS `location_name`,
  1 AS `received_by`,
  1 AS `issue_id`,
  1 AS `issue_no`,
  1 AS `issue_date`,
  1 AS `assetNum`,
  1 AS `asset_description`,
  1 AS `serial_number`,
  1 AS `category_code`,
  1 AS `category_name`,
  1 AS `assignee_type`,
  1 AS `empId`,
  1 AS `employee_name`,
  1 AS `deptId`,
  1 AS `deptName`,
  1 AS `condition_out`,
  1 AS `purpose`,
  1 AS `issued_by` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `vassetdata`;
/*!50001 DROP VIEW IF EXISTS `vassetdata`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vassetdata` AS SELECT
 1 AS `assetNum`,
  1 AS `Description`,
  1 AS `assetkat`,
  1 AS `empId`,
  1 AS `fullname`,
  1 AS `createdBy`,
  1 AS `createdDate`,
  1 AS `quantity`,
  1 AS `unit`,
  1 AS `assetStat`,
  1 AS `location`,
  1 AS `assetImage` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `vempdept`;
/*!50001 DROP VIEW IF EXISTS `vempdept`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vempdept` AS SELECT
 1 AS `empId`,
  1 AS `fullname`,
  1 AS `deptId`,
  1 AS `deptName` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `vendor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vendor` (
  `vendor_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_name` varchar(150) NOT NULL,
  `contact_person` varchar(120) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `tax_number` varchar(80) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`vendor_id`),
  UNIQUE KEY `uq_vendor_name` (`vendor_name`),
  KEY `idx_vendor_active_name` (`active`,`vendor_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50001 DROP VIEW IF EXISTS `v_asset_batch_receipts`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `v_asset_batch_receipts` AS select `r`.`receipt_id` AS `receipt_id`,`r`.`receipt_no` AS `receipt_no`,`r`.`received_date` AS `received_date`,`r`.`supplier_name` AS `supplier_name`,`r`.`vendor_id` AS `vendor_id`,`r`.`po_number` AS `po_number`,`r`.`invoice_number` AS `invoice_number`,`r`.`received_by` AS `received_by`,`ri`.`line_no` AS `line_no`,`ri`.`condition_on_receipt` AS `condition_on_receipt`,`ri`.`remarks` AS `remarks`,`a`.`assetNum` AS `assetNum`,`a`.`Description` AS `asset_description`,`a`.`serial_number` AS `serial_number`,`a`.`assetKat` AS `category_code`,`k`.`Description` AS `category_name`,`a`.`manufacturer` AS `manufacturer`,`a`.`model` AS `model`,`a`.`purchase_date` AS `purchase_date`,`a`.`purchase_cost` AS `purchase_cost`,`a`.`warranty_expiry` AS `warranty_expiry`,`a`.`assetStat` AS `assetStat`,`a`.`location` AS `location` from (((`asset_receipt` `r` join `asset_receipt_item` `ri` on(`ri`.`receipt_id` = `r`.`receipt_id`)) join `assetdata` `a` on(`a`.`assetNum` = `ri`.`assetNum`)) left join `assetkat` `k` on(`k`.`assetKat` = `a`.`assetKat`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_asset_current_assignment`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `v_asset_current_assignment` AS select `v_asset_issue_history`.`issue_id` AS `issue_id`,`v_asset_issue_history`.`issue_no` AS `issue_no`,`v_asset_issue_history`.`issue_date` AS `issue_date`,`v_asset_issue_history`.`assetNum` AS `assetNum`,`v_asset_issue_history`.`asset_description` AS `asset_description`,`v_asset_issue_history`.`assetKat` AS `assetKat`,`v_asset_issue_history`.`category` AS `category`,`v_asset_issue_history`.`assignee_type` AS `assignee_type`,`v_asset_issue_history`.`empId` AS `empId`,`v_asset_issue_history`.`employee_name` AS `employee_name`,`v_asset_issue_history`.`deptId` AS `deptId`,`v_asset_issue_history`.`deptName` AS `deptName`,`v_asset_issue_history`.`condition_out` AS `condition_out`,`v_asset_issue_history`.`expected_return_date` AS `expected_return_date`,`v_asset_issue_history`.`purpose` AS `purpose`,`v_asset_issue_history`.`status` AS `status`,`v_asset_issue_history`.`issued_by` AS `issued_by`,`v_asset_issue_history`.`returned_at` AS `returned_at` from `v_asset_issue_history` where `v_asset_issue_history`.`status` = 'ISSUED' */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_asset_issue_history`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `v_asset_issue_history` AS select `i`.`issue_id` AS `issue_id`,`i`.`issue_no` AS `issue_no`,`i`.`issue_date` AS `issue_date`,`i`.`assetNum` AS `assetNum`,`a`.`Description` AS `asset_description`,`a`.`assetKat` AS `assetKat`,`k`.`Description` AS `category`,`i`.`assignee_type` AS `assignee_type`,`i`.`empId` AS `empId`,coalesce(`e`.`fullname`,`i`.`acknowledged_by`) AS `employee_name`,`i`.`deptId` AS `deptId`,`d`.`deptName` AS `deptName`,`i`.`condition_out` AS `condition_out`,`i`.`expected_return_date` AS `expected_return_date`,`i`.`purpose` AS `purpose`,`i`.`status` AS `status`,`i`.`issued_by` AS `issued_by`,`i`.`returned_at` AS `returned_at` from ((((`asset_issue` `i` join `assetdata` `a` on(`a`.`assetNum` = `i`.`assetNum`)) left join `assetkat` `k` on(`k`.`assetKat` = `a`.`assetKat`)) left join `employee` `e` on(`e`.`empId` = `i`.`empId`)) left join `department` `d` on(`d`.`deptId` = `i`.`deptId`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_asset_return_history`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `v_asset_return_history` AS select `r`.`return_id` AS `return_id`,`r`.`return_no` AS `return_no`,`r`.`return_date` AS `return_date`,`r`.`return_type` AS `return_type`,`r`.`condition_in` AS `condition_in`,`r`.`reason` AS `reason`,`r`.`scrap_reference` AS `scrap_reference`,`r`.`return_location` AS `return_location`,`l`.`description` AS `location_name`,`r`.`received_by` AS `received_by`,`i`.`issue_id` AS `issue_id`,`i`.`issue_no` AS `issue_no`,`i`.`issue_date` AS `issue_date`,`i`.`assetNum` AS `assetNum`,`a`.`Description` AS `asset_description`,`a`.`serial_number` AS `serial_number`,`a`.`assetKat` AS `category_code`,`k`.`Description` AS `category_name`,`i`.`assignee_type` AS `assignee_type`,`i`.`empId` AS `empId`,coalesce(`e`.`fullname`,`i`.`acknowledged_by`) AS `employee_name`,`i`.`deptId` AS `deptId`,`d`.`deptName` AS `deptName`,`i`.`condition_out` AS `condition_out`,`i`.`purpose` AS `purpose`,`i`.`issued_by` AS `issued_by` from ((((((`asset_return` `r` join `asset_issue` `i` on(`i`.`issue_id` = `r`.`issue_id`)) join `assetdata` `a` on(`a`.`assetNum` = `i`.`assetNum`)) left join `assetkat` `k` on(`k`.`assetKat` = `a`.`assetKat`)) left join `employee` `e` on(`e`.`empId` = `i`.`empId`)) left join `department` `d` on(`d`.`deptId` = `i`.`deptId`)) left join `location` `l` on(`l`.`location` = `r`.`return_location`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `vassetdata`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `vassetdata` AS select `a`.`assetNum` AS `assetNum`,`a`.`Description` AS `Description`,`k`.`Description` AS `assetkat`,`a`.`empId` AS `empId`,`e`.`fullname` AS `fullname`,`a`.`createdBy` AS `createdBy`,`a`.`createdDate` AS `createdDate`,`a`.`quantity` AS `quantity`,`a`.`unit` AS `unit`,`s`.`Description` AS `assetStat`,`l`.`description` AS `location`,`a`.`assetImage` AS `assetImage` from ((((`assetdata` `a` left join `employee` `e` on(`e`.`empId` = `a`.`empId`)) left join `location` `l` on(`l`.`location` = `a`.`location`)) left join `assetstat` `s` on(`s`.`assetStatus` = `a`.`assetStat`)) left join `assetkat` `k` on(`k`.`assetKat` = `a`.`assetKat`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `vempdept`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `vempdept` AS select `e`.`empId` AS `empId`,`e`.`fullname` AS `fullname`,coalesce(`e`.`deptId`,`ed`.`deptId`) AS `deptId`,`d`.`deptName` AS `deptName` from ((`employee` `e` left join `empdept` `ed` on(`ed`.`empId` = `e`.`empId` and `ed`.`active` = 1)) left join `department` `d` on(`d`.`deptId` = coalesce(`e`.`deptId`,`ed`.`deptId`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


-- --------------------------------------------------------
-- Retained master and login data
-- --------------------------------------------------------
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

INSERT INTO `assetkat` (`assetKat`, `Description`, `asset_prefix`, `next_asset_sequence`, `active`) VALUES ('ACCESS','Access Control Device','AC',11,1);
INSERT INTO `assetkat` (`assetKat`, `Description`, `asset_prefix`, `next_asset_sequence`, `active`) VALUES ('ADAPTER','Adapter / Charger','AD',11,1);
INSERT INTO `assetkat` (`assetKat`, `Description`, `asset_prefix`, `next_asset_sequence`, `active`) VALUES ('BIOMETRIC','Biometric Device','BM',11,1);
INSERT INTO `assetkat` (`assetKat`, `Description`, `asset_prefix`, `next_asset_sequence`, `active`) VALUES ('CAMERA','Camera / CCTV','CM',11,1);
INSERT INTO `assetkat` (`assetKat`, `Description`, `asset_prefix`, `next_asset_sequence`, `active`) VALUES ('DESKTOP','Desktop Computer','DT',20,1);
INSERT INTO `assetkat` (`assetKat`, `Description`, `asset_prefix`, `next_asset_sequence`, `active`) VALUES ('DOCK','Docking Station','DK',11,1);
INSERT INTO `assetkat` (`assetKat`, `Description`, `asset_prefix`, `next_asset_sequence`, `active`) VALUES ('DRIVE','External Drive','DR',11,1);
INSERT INTO `assetkat` (`assetKat`, `Description`, `asset_prefix`, `next_asset_sequence`, `active`) VALUES ('FIREWALL','Firewall / Security Appliance','FW',11,1);
INSERT INTO `assetkat` (`assetKat`, `Description`, `asset_prefix`, `next_asset_sequence`, `active`) VALUES ('HEADSET','Headset','HS',11,1);
INSERT INTO `assetkat` (`assetKat`, `Description`, `asset_prefix`, `next_asset_sequence`, `active`) VALUES ('KEYMOUSE','Keyboard / Mouse','KM',11,1);
INSERT INTO `assetkat` (`assetKat`, `Description`, `asset_prefix`, `next_asset_sequence`, `active`) VALUES ('LAPTOP','Laptop','LT',15,1);
INSERT INTO `assetkat` (`assetKat`, `Description`, `asset_prefix`, `next_asset_sequence`, `active`) VALUES ('MEETING','Meeting Room Equipment','MR',11,1);
INSERT INTO `assetkat` (`assetKat`, `Description`, `asset_prefix`, `next_asset_sequence`, `active`) VALUES ('MOBILE','Mobile Phone','MB',11,1);
INSERT INTO `assetkat` (`assetKat`, `Description`, `asset_prefix`, `next_asset_sequence`, `active`) VALUES ('MONITOR','Monitor','MN',15,1);
INSERT INTO `assetkat` (`assetKat`, `Description`, `asset_prefix`, `next_asset_sequence`, `active`) VALUES ('NETWORK','Network Equipment','NW',11,1);
INSERT INTO `assetkat` (`assetKat`, `Description`, `asset_prefix`, `next_asset_sequence`, `active`) VALUES ('OTHER','Other IT Equipment','OT',11,1);
INSERT INTO `assetkat` (`assetKat`, `Description`, `asset_prefix`, `next_asset_sequence`, `active`) VALUES ('PHONE','IP Phone','PH',15,1);
INSERT INTO `assetkat` (`assetKat`, `Description`, `asset_prefix`, `next_asset_sequence`, `active`) VALUES ('PRINTER','Printer / Scanner','PR',15,1);
INSERT INTO `assetkat` (`assetKat`, `Description`, `asset_prefix`, `next_asset_sequence`, `active`) VALUES ('PROJECTOR','Projector','PJ',11,1);
INSERT INTO `assetkat` (`assetKat`, `Description`, `asset_prefix`, `next_asset_sequence`, `active`) VALUES ('RACK','Server Rack / Cabinet','RK',11,1);
INSERT INTO `assetkat` (`assetKat`, `Description`, `asset_prefix`, `next_asset_sequence`, `active`) VALUES ('SCANNER','Document Scanner','SC',11,1);
INSERT INTO `assetkat` (`assetKat`, `Description`, `asset_prefix`, `next_asset_sequence`, `active`) VALUES ('SERVER','Server','SV',11,1);
INSERT INTO `assetkat` (`assetKat`, `Description`, `asset_prefix`, `next_asset_sequence`, `active`) VALUES ('STORAGE','Storage Device / NAS','ST',11,1);
INSERT INTO `assetkat` (`assetKat`, `Description`, `asset_prefix`, `next_asset_sequence`, `active`) VALUES ('TABLET','Tablet','TB',11,1);
INSERT INTO `assetkat` (`assetKat`, `Description`, `asset_prefix`, `next_asset_sequence`, `active`) VALUES ('UPS','UPS / Power Backup','UPS',11,1);

INSERT INTO `assetstat` (`assetStatus`, `Description`, `is_terminal`) VALUES ('ASSIGNED','Assigned',0);
INSERT INTO `assetstat` (`assetStatus`, `Description`, `is_terminal`) VALUES ('IN_STOCK','In Stock',0);
INSERT INTO `assetstat` (`assetStatus`, `Description`, `is_terminal`) VALUES ('SCRAPPED','Scrapped',1);

INSERT INTO `department` (`deptId`, `deptPrefix`, `deptName`, `area`, `building`, `headEmpId`, `active`) VALUES ('D001','D001','Admin/HR',NULL,NULL,NULL,1);
INSERT INTO `department` (`deptId`, `deptPrefix`, `deptName`, `area`, `building`, `headEmpId`, `active`) VALUES ('D002','D002','Admin/HR - General Service',NULL,NULL,NULL,1);
INSERT INTO `department` (`deptId`, `deptPrefix`, `deptName`, `area`, `building`, `headEmpId`, `active`) VALUES ('D003','D003','Admin/Safety',NULL,NULL,NULL,1);
INSERT INTO `department` (`deptId`, `deptPrefix`, `deptName`, `area`, `building`, `headEmpId`, `active`) VALUES ('D004','D004','Admin/Security',NULL,NULL,NULL,1);
INSERT INTO `department` (`deptId`, `deptPrefix`, `deptName`, `area`, `building`, `headEmpId`, `active`) VALUES ('D005','D005','CEO Office',NULL,NULL,NULL,1);
INSERT INTO `department` (`deptId`, `deptPrefix`, `deptName`, `area`, `building`, `headEmpId`, `active`) VALUES ('D006','D006','Commercial/Admin',NULL,NULL,NULL,1);
INSERT INTO `department` (`deptId`, `deptPrefix`, `deptName`, `area`, `building`, `headEmpId`, `active`) VALUES ('D007','D007','Commercial/ Business Development',NULL,NULL,NULL,1);
INSERT INTO `department` (`deptId`, `deptPrefix`, `deptName`, `area`, `building`, `headEmpId`, `active`) VALUES ('D008','D008','Commercial/Shipping',NULL,NULL,NULL,1);
INSERT INTO `department` (`deptId`, `deptPrefix`, `deptName`, `area`, `building`, `headEmpId`, `active`) VALUES ('D009','D009','Commercial/Warehouse',NULL,NULL,NULL,1);
INSERT INTO `department` (`deptId`, `deptPrefix`, `deptName`, `area`, `building`, `headEmpId`, `active`) VALUES ('D010','D010','Finance',NULL,NULL,NULL,1);
INSERT INTO `department` (`deptId`, `deptPrefix`, `deptName`, `area`, `building`, `headEmpId`, `active`) VALUES ('D011','D011','IT',NULL,NULL,1295,1);
INSERT INTO `department` (`deptId`, `deptPrefix`, `deptName`, `area`, `building`, `headEmpId`, `active`) VALUES ('D012','D012','Jed/Admin/Security',NULL,NULL,NULL,1);
INSERT INTO `department` (`deptId`, `deptPrefix`, `deptName`, `area`, `building`, `headEmpId`, `active`) VALUES ('D013','D013','OP/Automation',NULL,NULL,NULL,1);
INSERT INTO `department` (`deptId`, `deptPrefix`, `deptName`, `area`, `building`, `headEmpId`, `active`) VALUES ('D014','D014','OP/CCL',NULL,NULL,NULL,1);
INSERT INTO `department` (`deptId`, `deptPrefix`, `deptName`, `area`, `building`, `headEmpId`, `active`) VALUES ('D015','D015','OP/CCL - LOG ?',NULL,NULL,NULL,1);
INSERT INTO `department` (`deptId`, `deptPrefix`, `deptName`, `area`, `building`, `headEmpId`, `active`) VALUES ('D016','D016','OP/CGL',NULL,NULL,NULL,1);
INSERT INTO `department` (`deptId`, `deptPrefix`, `deptName`, `area`, `building`, `headEmpId`, `active`) VALUES ('D017','D017','OP/CRM',NULL,NULL,NULL,1);
INSERT INTO `department` (`deptId`, `deptPrefix`, `deptName`, `area`, `building`, `headEmpId`, `active`) VALUES ('D018','D018','OP/Electrical',NULL,NULL,NULL,1);
INSERT INTO `department` (`deptId`, `deptPrefix`, `deptName`, `area`, `building`, `headEmpId`, `active`) VALUES ('D019','D019','OP/Logistics',NULL,NULL,NULL,1);
INSERT INTO `department` (`deptId`, `deptPrefix`, `deptName`, `area`, `building`, `headEmpId`, `active`) VALUES ('D020','D020','OP/Mechanical',NULL,NULL,NULL,1);
INSERT INTO `department` (`deptId`, `deptPrefix`, `deptName`, `area`, `building`, `headEmpId`, `active`) VALUES ('D021','D021','OP/Mechanical/Projects',NULL,NULL,NULL,1);
INSERT INTO `department` (`deptId`, `deptPrefix`, `deptName`, `area`, `building`, `headEmpId`, `active`) VALUES ('D022','D022','OP/PPPL',NULL,NULL,NULL,1);
INSERT INTO `department` (`deptId`, `deptPrefix`, `deptName`, `area`, `building`, `headEmpId`, `active`) VALUES ('D023','D023','OP/Production Planning & R',NULL,NULL,NULL,1);
INSERT INTO `department` (`deptId`, `deptPrefix`, `deptName`, `area`, `building`, `headEmpId`, `active`) VALUES ('D024','D024','Op/Projects',NULL,NULL,NULL,1);
INSERT INTO `department` (`deptId`, `deptPrefix`, `deptName`, `area`, `building`, `headEmpId`, `active`) VALUES ('D025','D025','OP/QA & CS',NULL,NULL,NULL,1);
INSERT INTO `department` (`deptId`, `deptPrefix`, `deptName`, `area`, `building`, `headEmpId`, `active`) VALUES ('D026','D026','OP/UT',NULL,NULL,NULL,1);
INSERT INTO `department` (`deptId`, `deptPrefix`, `deptName`, `area`, `building`, `headEmpId`, `active`) VALUES ('D027','D027','Sales',NULL,NULL,NULL,1);
INSERT INTO `department` (`deptId`, `deptPrefix`, `deptName`, `area`, `building`, `headEmpId`, `active`) VALUES ('D028','D028','Sales/Jeddah',NULL,NULL,NULL,1);


INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (290,'Mohammed','Ali Al Muwais','Mohammed Ali Al Muwais',NULL,'Roll Assembly','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (549,'Habeeb','A. Al-Saeed','Habeeb A. Al-Saeed',NULL,'QA Senior Technician, CRM/CGL','OP/QA & CS',NULL,NULL,NULL,'OP/QA & CS','D025',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (567,'Yasser','Saleh Al Bin Ahmed','Yasser Saleh Al Bin Ahmed',NULL,'Foreman Utilities','OP/UT',NULL,NULL,NULL,'OP/UT','D026',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (582,'Hassan','Abdullah Ali Al-Nassir','Hassan Abdullah Ali Al-Nassir',NULL,'Overhead Crane & Forklift Operator, CRM','OP/CRM',NULL,NULL,NULL,'OP/CRM','D017',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (584,'Ali','Hussain Hassan Al-Saeed','Ali Hussain Hassan Al-Saeed',NULL,'Overhead Crane & Forklift Operator, CRM','OP/CRM',NULL,NULL,NULL,'OP/CRM','D017',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (587,'Wafi','Hussain Saleh Al-Awami','Wafi Hussain Saleh Al-Awami',NULL,'Planning Technician','OP/Production Planning & R',NULL,NULL,NULL,'OP/Production Planning & R','D023',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (589,'Hussain','Ali Al-Mugabgab','Hussain Ali Al-Mugabgab',NULL,'Supervisor- CCL','OP/Logistics',NULL,NULL,NULL,'OP/Logistics','D019',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (590,'Ali','Abdullah Ali Al Muwais','Ali Abdullah Ali Al Muwais',NULL,'Logistics Coordinator','OP/Logistics',NULL,NULL,NULL,'OP/Logistics','D019',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (609,'Akbar','Ali Mohammed Al-Abdullah','Akbar Ali Mohammed Al-Abdullah',NULL,'Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (648,'Sauod','Ali Al-Khazal','Sauod Ali Al-Khazal',NULL,'Operator, PPPL','OP/PPPL',NULL,NULL,NULL,'OP/PPPL','D022',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (798,'Yasser','Mohamed Al-Sharqi','Yasser Mohamed Al-Sharqi',NULL,'Logistics Coordinator','OP/Logistics',NULL,NULL,NULL,'OP/Logistics','D019',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (809,'Mohammed','Abdullah AL-Muwais','Mohammed Abdullah AL-Muwais',NULL,'Warehouse Officer','Commercial/Warehouse',NULL,NULL,NULL,'Commercial/Warehouse','D009',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (822,'Mohammed','Hamdan AlShamrani','Mohammed Hamdan AlShamrani',NULL,'Section Head, Benefits and Compensation','Admin/HR',NULL,NULL,NULL,'Admin/HR','D001',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (860,'Saad','Daagsh ALMelaihi','Saad Daagsh ALMelaihi',NULL,'Government Public Relations Administrator','Admin/HR',NULL,NULL,NULL,'Admin/HR','D001',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (877,'Mohammed','Rashed AL-Masoud','Mohammed Rashed AL-Masoud',NULL,'Machinist','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (926,'Essa','Makki Al Mahfoudh','Essa Makki Al Mahfoudh',NULL,'Energy Management Supervisor','OP/Electrical',NULL,NULL,NULL,'OP/Electrical','D018',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (927,'Kelvin','Villanueva Bayungan','Kelvin Villanueva Bayungan',NULL,'QA Inspector','OP/QA & CS',NULL,NULL,NULL,'OP/QA & CS','D025',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (932,'Richmond','De los Reyes Magpile','Richmond De los Reyes Magpile',NULL,'Operator, RGM','OP/CRM',NULL,NULL,NULL,'OP/CRM','D017',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (939,'Edwin','Hernandez Ruedas','Edwin Hernandez Ruedas',NULL,'QA Technician','OP/QA & CS',NULL,NULL,NULL,'OP/QA & CS','D025',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (944,'Henry','Delos Santos Banaag','Henry Delos Santos Banaag',NULL,'POT Operator, CGL','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (945,'Rommel','Balois Norte','Rommel Balois Norte',NULL,'Operator, Coater','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (948,'Gregorio','Lopez Mendoza','Gregorio Lopez Mendoza',NULL,'Operator, CRM','OP/CRM',NULL,NULL,NULL,'OP/CRM','D017',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (949,'Noel','Cabrigas Talacay','Noel Cabrigas Talacay',NULL,'Operator Sltting','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (951,'Abdulrahman','A. Al Shawan','Abdulrahman A. Al Shawan',NULL,'Senior Technical Sales Executive','Sales',NULL,NULL,NULL,'Sales','D027',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (958,'Cecep','Supriadi Bn Juardi Damin','Cecep Supriadi Bn Juardi Damin',NULL,'Mechanical Technician','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (959,'Yustinus','Galuh Purwanto Bn Joh','Yustinus Galuh Purwanto Bn Joh',NULL,'Operator, RGM','OP/CRM',NULL,NULL,NULL,'OP/CRM','D017',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (964,'Richard','Matalog Dastas','Richard Matalog Dastas',NULL,'Operator, CRM','OP/CRM',NULL,NULL,NULL,'OP/CRM','D017',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (973,'Mohammed','Rubeil','Mohammed Rubeil',NULL,'Operator, Cranes','OP/CRM',NULL,NULL,NULL,'OP/CRM','D017',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (990,'Isidro','Lara','Isidro Lara',NULL,'Mechanical Technician, CCL','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (992,'Ali','Shueai Debjah','Ali Shueai Debjah',NULL,'Operator, Cranes','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (993,'Mohammed','Khamis Ba Buregah','Mohammed Khamis Ba Buregah',NULL,'Coater Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1002,'Sayed','Shehata Saber Gaafar','Sayed Shehata Saber Gaafar',NULL,'Head -Shipping & Local Procurement','Commercial/Admin',NULL,NULL,NULL,'Commercial/Admin','D006',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1017,'Ahmed','Saud Al-Nakhle','Ahmed Saud Al-Nakhle',NULL,'QA Technician','OP/QA & CS',NULL,NULL,NULL,'OP/QA & CS','D025',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1019,'Ali','Mohammed Al-Muwais','Ali Mohammed Al-Muwais',NULL,'PPPL Operator','OP/PPPL',NULL,NULL,NULL,'OP/PPPL','D022',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1024,'Mohammed','Ibrahim Al Nahab','Mohammed Ibrahim Al Nahab',NULL,'Mechanical Technician','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1027,'Hussain','Ali Al Sadig','Hussain Ali Al Sadig',NULL,'Exit Operator','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1028,'Ali','Abdalraheem Al Essa','Ali Abdalraheem Al Essa',NULL,'CCL Foreman','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1030,'Faisal','Mubarak Al-Sultan','Faisal Mubarak Al-Sultan',NULL,'Security Guard Supervisor','Admin/Security',NULL,NULL,NULL,'Admin/Security','D004',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1036,'Ahmad','Irfan','Ahmad Irfan',NULL,'Senior Systems Engineer','OP/Automation',NULL,NULL,NULL,'OP/Automation','D013',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1040,'Mohammed','Radi Aboamer','Mohammed Radi Aboamer',NULL,'Logistics Coordinator','OP/Logistics',NULL,NULL,NULL,'OP/Logistics','D019',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1041,'Abdullah','Omran Alkhazali','Abdullah Omran Alkhazali',NULL,'QA Technician','OP/QA & CS',NULL,NULL,NULL,'OP/QA & CS','D025',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1047,'Raed','Ibrahem Al Ghamdi','Raed Ibrahem Al Ghamdi',NULL,'Crane Operator','OP/PPPL',NULL,NULL,NULL,'OP/PPPL','D022',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1056,'Anil','Gulabrao Jeevaji Sur','Anil Gulabrao Jeevaji Sur',NULL,'Shift Engineer (Electrical)','OP/Electrical',NULL,NULL,NULL,'OP/Electrical','D018',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1057,'Nestor','Jr Basa Teruel','Nestor Jr Basa Teruel',NULL,'Mechanical Technician','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1058,'Armando','De Los Reyes Candelaria','Armando De Los Reyes Candelaria',NULL,'Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1068,'Jomar','Jonson Villanueva','Jomar Jonson Villanueva',NULL,'Exit Operator','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1074,'Manuel','Icawat Antonio','Manuel Icawat Antonio',NULL,'QC Technician','OP/QA & CS',NULL,NULL,NULL,'OP/QA & CS','D025',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1076,'Mohamed','Rizwan Mohamed Moosin','Mohamed Rizwan Mohamed Moosin',NULL,'Planning Tech. CCL & Finishing line','OP/Production Planning & R',NULL,NULL,NULL,'OP/Production Planning & R','D023',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1078,'Joefrey','Macalindong Marasigan','Joefrey Macalindong Marasigan',NULL,'Operator','OP/QA & CS',NULL,NULL,NULL,'OP/QA & CS','D025',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1082,'Akashkumar','Vasant Kumar','Akashkumar Vasant Kumar',NULL,'Section Head ( Rolling Mills )','OP/CRM',NULL,NULL,NULL,'OP/CRM','D017',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1083,'Abdullah','Essa Al Muhaymid','Abdullah Essa Al Muhaymid',NULL,'Mechanical Technician','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1091,'Kumail','Mohammad Al-Moshiqri','Kumail Mohammad Al-Moshiqri',NULL,'Electrical Technician','OP/Electrical',NULL,NULL,NULL,'OP/Electrical','D018',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1092,'Abdullaziz','Mohammed Al-Zahrani','Abdullaziz Mohammed Al-Zahrani',NULL,'Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1097,'Primo','Jr Gabriel Getano','Primo Jr Gabriel Getano',NULL,'QA Technician','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1102,'Faisal','Mussad Al-Thgafi','Faisal Mussad Al-Thgafi',NULL,'Security Guard','Admin/Security',NULL,NULL,NULL,'Admin/Security','D004',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1103,'Haitham','Said Al Marshoud','Haitham Said Al Marshoud',NULL,'Security Guard','Admin/Security',NULL,NULL,NULL,'Admin/Security','D004',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1116,'Bhim','Kumar Shrestha','Bhim Kumar Shrestha',NULL,'Mechanical Technician','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1125,'Rakesh','Solanki','Rakesh Solanki',NULL,'Senior Operator, CGL','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1134,'Pravena','Kumar Vaggegowda','Pravena Kumar Vaggegowda',NULL,'Shift Engineer ( Electrical)','OP/Electrical',NULL,NULL,NULL,'OP/Electrical','D018',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1137,'Arnold','Tolentino Carandang','Arnold Tolentino Carandang',NULL,'Pot Operator','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1138,'Jose','Dennis Riosa Hernandez','Jose Dennis Riosa Hernandez',NULL,'Pot Operator','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1141,'Stephen','Cano Belmonte','Stephen Cano Belmonte',NULL,'Electrical Technician','OP/Electrical',NULL,NULL,NULL,'OP/Electrical','D018',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1143,'Rey','Amoguis Baguio','Rey Amoguis Baguio',NULL,'QA Technician','OP/QA & CS',NULL,NULL,NULL,'OP/QA & CS','D025',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1144,'Michael','Joseph Hernandez Agda','Michael Joseph Hernandez Agda',NULL,'Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1153,'Mustafa','Al Ibrahim','Mustafa Al Ibrahim',NULL,'CCL Foreman','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1158,'Mohammed','Alher Al Shatty','Mohammed Alher Al Shatty',NULL,'Exit Operator','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1166,'Ali','Mohammed Al-Shomary','Ali Mohammed Al-Shomary',NULL,'Senior Accounting Officer','Finance',NULL,NULL,NULL,'Finance','D010',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1168,'Mohammed','Akel Al-Khalidi','Mohammed Akel Al-Khalidi',NULL,'Coater Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1178,'Mohammed','Ismail','Mohammed Ismail',NULL,'CCL& Finish Lines Production Senior Eng.','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1182,'Kul','Bahadur Thapa','Kul Bahadur Thapa',NULL,'Utilities Technician','OP/UT',NULL,NULL,NULL,'OP/UT','D026',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1187,'Aklesh','Kumar Giri','Aklesh Kumar Giri',NULL,'Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1188,'Hari','Bhakta Giri','Hari Bhakta Giri',NULL,'Entry Operator- CGL','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1189,'Manoj','Adhikari','Manoj Adhikari',NULL,'Operator','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1191,'Tanka','Jirel','Tanka Jirel',NULL,'Electrical Technician','OP/Electrical',NULL,NULL,NULL,'OP/Electrical','D018',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1195,'Muntazar','Abdullah Al-Mater','Muntazar Abdullah Al-Mater',NULL,'Electrical Engineer','OP/Electrical',NULL,NULL,NULL,'OP/Electrical','D018',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1200,'Naif','Naji Al-Farwan','Naif Naji Al-Farwan',NULL,'Crane Operator -CRM','OP/CRM',NULL,NULL,NULL,'OP/CRM','D017',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1204,'Sanjay','Chaudhary','Sanjay Chaudhary',NULL,'Operator','OP/PPPL',NULL,NULL,NULL,'OP/PPPL','D022',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1206,'Manoj','Chaudhary','Manoj Chaudhary',NULL,'Operator','OP/PPPL',NULL,NULL,NULL,'OP/PPPL','D022',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1207,'Krishna','Bahadur Lama','Krishna Bahadur Lama',NULL,'Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1208,'Shrawan','Raut Kurmi','Shrawan Raut Kurmi',NULL,'Operator','OP/PPPL',NULL,NULL,NULL,'OP/PPPL','D022',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1211,'Ali','Jafar Al Radwan','Ali Jafar Al Radwan',NULL,'QA Technician','OP/QA & CS',NULL,NULL,NULL,'OP/QA & CS','D025',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1212,'Ibrahim','Hassan Al Sheef','Ibrahim Hassan Al Sheef',NULL,'Crane Operator','OP/CRM',NULL,NULL,NULL,'OP/CRM','D017',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1215,'Edgardo','L. Gonzales','Edgardo L. Gonzales',NULL,'Senior Raw Materials and Contracts','Commercial/Admin',NULL,NULL,NULL,'Commercial/Admin','D006',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1218,'Moidutty','Chathanelloor','Moidutty Chathanelloor',NULL,'Operator, CCL','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1220,'Khan','Moh\'d Noor Alam','Khan Moh\'d Noor Alam',NULL,'CCL Senior Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1222,'Shahul','Hameed Akbar Ali','Shahul Hameed Akbar Ali',NULL,'Operator, PPPL','OP/PPPL',NULL,NULL,NULL,'OP/PPPL','D022',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1223,'Mydeen','Mathar Mydeen','Mydeen Mathar Mydeen',NULL,'Senior Coater Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1227,'Mohammed','Ashraf Razick','Mohammed Ashraf Razick',NULL,'QA Technician, CCL','OP/QA & CS',NULL,NULL,NULL,'OP/QA & CS','D025',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1228,'Aboobaker','Adka Ahmed','Aboobaker Adka Ahmed',NULL,'Operator, CCL','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1229,'Prince','P. Mathew','Prince P. Mathew',NULL,'Sales Manager','Sales',NULL,NULL,NULL,'Sales','D027',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1233,'Mohammed','Niyaz Munna','Mohammed Niyaz Munna',NULL,'Operator, CCL','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1234,'Ikhlaque','Abdul Khader Sheikh','Ikhlaque Abdul Khader Sheikh',NULL,'Planning Foreman','OP/Production Planning & R',NULL,NULL,NULL,'OP/Production Planning & R','D023',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1236,'Herman','Bn Mochamad Sobri','Herman Bn Mochamad Sobri',NULL,'Senior Operator, CGL','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1237,'Aguedo','Arevalo Manguerra','Aguedo Arevalo Manguerra',NULL,'Operator, ARP','OP/PPPL',NULL,NULL,NULL,'OP/PPPL','D022',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1238,'Fakhruddin','Ali Ahmad','Fakhruddin Ali Ahmad',NULL,'Operator, ARP','OP/PPPL',NULL,NULL,NULL,'OP/PPPL','D022',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1240,'Joselito','Del Rosario Martinez','Joselito Del Rosario Martinez',NULL,'Senior Operator, PPPL','OP/PPPL',NULL,NULL,NULL,'OP/PPPL','D022',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1243,'Mudasser','Hussain Noor','Mudasser Hussain Noor',NULL,'Organizational Development Officer','Admin/HR',NULL,NULL,NULL,'Admin/HR','D001',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1245,'Shree','Hari Karki','Shree Hari Karki',NULL,'QA Technician','OP/QA & CS',NULL,NULL,NULL,'OP/QA & CS','D025',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1246,'Mohammed','Al Marzouq','Mohammed Al Marzouq',NULL,'Local Buyer','Commercial/Admin',NULL,NULL,NULL,'Commercial/Admin','D006',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1247,'Pius','R. D\'Souza','Pius R. D\'Souza',NULL,'Engineer, QA & CS','OP/QA & CS',NULL,NULL,NULL,'OP/QA & CS','D025',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1248,'Vipin','Barve','Vipin Barve',NULL,'CRM Foreman','OP/CRM',NULL,NULL,NULL,'OP/CRM','D017',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1249,'Rahul','Gajbhiye','Rahul Gajbhiye',NULL,'Senior Mechanical Technician','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1250,'Venkat','Srinivasu Rao Mullapudi','Venkat Srinivasu Rao Mullapudi',NULL,'Section Head, PP & R','OP/Production Planning & R',NULL,NULL,NULL,'OP/Production Planning & R','D023',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1252,'Keshava','Reddy Kunduru','Keshava Reddy Kunduru',NULL,'Chief Engineer (Electrical)','OP/Electrical',NULL,NULL,NULL,'OP/Electrical','D018',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1253,'Vijay','Tukaram Aglawe','Vijay Tukaram Aglawe',NULL,'Foreman, Electrical','OP/Electrical',NULL,NULL,NULL,'OP/Electrical','D018',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1254,'Basant','Kumar Das','Basant Kumar Das',NULL,'Electrical Engineer PPPL,CRM,RGM & ARP','OP/Electrical',NULL,NULL,NULL,'OP/Electrical','D018',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1259,'Jose','Mendones Adami Jr.','Jose Mendones Adami Jr.',NULL,'Maintenance Planner, Electrical','OP/Electrical',NULL,NULL,NULL,'OP/Electrical','D018',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1260,'Rodante','Angulo Illao','Rodante Angulo Illao',NULL,'Operator, CRM','OP/CRM',NULL,NULL,NULL,'OP/CRM','D017',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1264,'Syahrial','Bn. SahrudinHarun','Syahrial Bn. SahrudinHarun',NULL,'Exit Operator','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1266,'Wage','Siswadi Subijono','Wage Siswadi Subijono',NULL,'Operator, CGL','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1267,'Roldan','Coro Dalisay','Roldan Coro Dalisay',NULL,'Operator, CGL','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1278,'Apolinar','J. Montes','Apolinar J. Montes',NULL,'Electrical Engineer, CCL & SCL','OP/Electrical',NULL,NULL,NULL,'OP/Electrical','D018',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1280,'Ponso','Sahat Bn M. Sitiinjak','Ponso Sahat Bn M. Sitiinjak',NULL,'Overhead Crane & Forklift Operator, RGM','OP/CRM',NULL,NULL,NULL,'OP/CRM','D017',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1281,'Hotbriman','Bn J. Sinaga','Hotbriman Bn J. Sinaga',NULL,'Foreman, PPPL','OP/PPPL',NULL,NULL,NULL,'OP/PPPL','D022',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1283,'Yadi','Ali Rusamsi Bn Koko','Yadi Ali Rusamsi Bn Koko',NULL,'Operator, CGL','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1284,'Mohammad','Arshad Equbal','Mohammad Arshad Equbal',NULL,'Section Head Automation','OP/Automation',NULL,NULL,NULL,'OP/Automation','D013',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1286,'Roberto','Briones Reyes','Roberto Briones Reyes',NULL,'Operator, CCL','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1288,'Ponnamu','Ramesh','Ponnamu Ramesh',NULL,'Operator, CGL','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1289,'Andres','Catibog Ilao','Andres Catibog Ilao',NULL,'Operator, CRM','OP/CRM',NULL,NULL,NULL,'OP/CRM','D017',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1290,'Meshari','Ahmed Al Aamer','Meshari Ahmed Al Aamer',NULL,'Security Guard','Admin/Security',NULL,NULL,NULL,'Admin/Security','D004',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1294,'Khaled','Ali Bhrr','Khaled Ali Bhrr',NULL,'Security Guard','Admin/Security',NULL,NULL,NULL,'Admin/Security','D004',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1295,'Sanjay','Kumar Motilal Dewangan','Sanjay Kumar Motilal Dewangan',NULL,'Section Head, IT','IT',NULL,NULL,NULL,'IT','D011',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1296,'Madhu','Srinivasan','Madhu Srinivasan',NULL,'Section Manager, Mechanical','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1297,'Suman','Kumar Itha','Suman Kumar Itha',NULL,'Mechanical Shift Engineer CGL, CCL, ISRL & H2','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1298,'Nellore','Srinivasa Rao','Nellore Srinivasa Rao',NULL,'Section Head - Mechanical PPPL,CGL, ISRL,H2 Plant','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1302,'Edjohn','Perjes Bartolabac','Edjohn Perjes Bartolabac',NULL,'Operator, ARP','OP/PPPL',NULL,NULL,NULL,'OP/PPPL','D022',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1307,'Zamil','Saeed Al-Mabadi','Zamil Saeed Al-Mabadi',NULL,'Security Guard','Jed/Admin/Security',NULL,NULL,NULL,'Jed/Admin/Security','D012',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1309,'Vinod','Kesavan','Vinod Kesavan',NULL,'Mechanical Technician','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1310,'Gajeesh','Ayyappan','Gajeesh Ayyappan',NULL,'Utilities Technician','OP/UT',NULL,NULL,NULL,'OP/UT','D026',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1314,'Prashant','Prabhakar Hejib','Prashant Prabhakar Hejib',NULL,'Section Manager, Electrical & Automation','OP/Electrical',NULL,NULL,NULL,'OP/Electrical','D018',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1316,'Palagiri','Satyanarayana Raju','Palagiri Satyanarayana Raju',NULL,'Foreman, CGL Slitting & Packing','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1317,'Balaji','Sivaraman','Balaji Sivaraman',NULL,'Electrical Engineer, CGL, ISL, CCL, & SGP','OP/Electrical',NULL,NULL,NULL,'OP/Electrical','D018',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1323,'Sandro','Hernandez','Sandro Hernandez',NULL,'QA Techician, Laboratory','OP/QA & CS',NULL,NULL,NULL,'OP/QA & CS','D025',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1326,'Pullam','Raju Penmatcha','Pullam Raju Penmatcha',NULL,'Foreman- ISL','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1330,'Vuyyuru','Raghava Reddy','Vuyyuru Raghava Reddy',NULL,'Section Head (QA & CS)','OP/QA & CS',NULL,NULL,NULL,'OP/QA & CS','D025',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1331,'Ariel','Gonzales','Ariel Gonzales',NULL,'Operator, Utilities','OP/UT',NULL,NULL,NULL,'OP/UT','D026',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1332,'Wilfredo','Comia Zara','Wilfredo Comia Zara',NULL,'Mechanical Technician CRM, RGM & SB','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1334,'Arnel','Custodio Macalalad','Arnel Custodio Macalalad',NULL,'Mechanical Technician CRM, RGM & SB','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1335,'Rogelio','Brillo Aniano','Rogelio Brillo Aniano',NULL,'Logistics Coordinator','OP/Logistics',NULL,NULL,NULL,'OP/Logistics','D019',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1340,'Om','Bahadur Rana','Om Bahadur Rana',NULL,'Overhead Crane & Forklift Operator, CCL','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1341,'Yam','Prasad Chapagai','Yam Prasad Chapagai',NULL,'Crane Operator','OP/CRM',NULL,NULL,NULL,'OP/CRM','D017',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1343,'Eufrocino','Del Rosario Dayandayan','Eufrocino Del Rosario Dayandayan',NULL,'Pot Operator, CGL','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1346,'Edgar','Mabini Lorejo','Edgar Mabini Lorejo',NULL,'Mechanical Technician CGL & H2','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1347,'Renato','Balbin Banadera','Renato Balbin Banadera',NULL,'Welder','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1348,'Arnold','Navarro Gatchalian','Arnold Navarro Gatchalian',NULL,'Workshop Technician','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1350,'Arthur','Mayuga Conocida','Arthur Mayuga Conocida',NULL,'Operator, CGL','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1351,'Domingo','Lasheras Violante','Domingo Lasheras Violante',NULL,'Operator, PPPL','OP/CRM',NULL,NULL,NULL,'OP/CRM','D017',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1352,'Adel','El-Sayed Eliwa','Adel El-Sayed Eliwa',NULL,'Electrical Technician','OP/Electrical',NULL,NULL,NULL,'OP/Electrical','D018',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1361,'Bassim','Al Romaih','Bassim Al Romaih',NULL,'Electrical Technician','OP/Electrical',NULL,NULL,NULL,'OP/Electrical','D018',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1366,'Ahmed','Jamal','Ahmed Jamal',NULL,'Systems Engineer','OP/Automation',NULL,NULL,NULL,'OP/Automation','D013',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1367,'Venkata','Ramana Suresh Babu','Venkata Ramana Suresh Babu',NULL,'Section Manager Production','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1370,'Hanumantha','Rao Vaddempodi','Hanumantha Rao Vaddempodi',NULL,'Production Foreman, CRM','OP/CRM',NULL,NULL,NULL,'OP/CRM','D017',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1371,'Satyanarayana','Raju Buddaraju','Satyanarayana Raju Buddaraju',NULL,'Senior Production Engineer, PPPL & ARP','OP/PPPL',NULL,NULL,NULL,'OP/PPPL','D022',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1373,'Ganni','Pattabhi Ramayya','Ganni Pattabhi Ramayya',NULL,'Foreman-CRM','OP/CRM',NULL,NULL,NULL,'OP/CRM','D017',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1375,'Benjamin','Batac Barredo , JR.','Benjamin Batac Barredo , JR.',NULL,'QA Technician, CGL','OP/QA & CS',NULL,NULL,NULL,'OP/QA & CS','D025',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1376,'Farid','Hidayat Bin Jueni','Farid Hidayat Bin Jueni',NULL,'Operator, CRM','OP/CRM',NULL,NULL,NULL,'OP/CRM','D017',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1378,'Ahmer','Khan Abdul Khan Pathan','Ahmer Khan Abdul Khan Pathan',NULL,'Storekeeper','Commercial/Warehouse',NULL,NULL,NULL,'Commercial/Warehouse','D009',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1379,'Nasir','Khan','Nasir Khan',NULL,'Operator, CGL','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1380,'Vijaya','Naveen Kumar','Vijaya Naveen Kumar',NULL,'Operator, CRM','OP/CRM',NULL,NULL,NULL,'OP/CRM','D017',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1383,'Majeed','Kallarbe','Majeed Kallarbe',NULL,'Mechanical Engineer','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1384,'Rakesh','Sharma','Rakesh Sharma',NULL,'Electrical Engineer, CGL, ISL, CCL, & SGP','OP/Electrical',NULL,NULL,NULL,'OP/Electrical','D018',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1386,'Abdullaziz','Ahmed Almawri','Abdullaziz Ahmed Almawri',NULL,'Senior Technical Sales Executive','Sales/Jeddah',NULL,NULL,NULL,'Sales/Jeddah','D028',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1387,'Lou','Buligao Villanueva','Lou Buligao Villanueva',NULL,'Mechanical Technician, ARP','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1388,'Sachin','Ghorpade','Sachin Ghorpade',NULL,'CCL Lab in Charge','OP/QA & CS',NULL,NULL,NULL,'OP/QA & CS','D025',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1389,'Ranjeet','Singh','Ranjeet Singh',NULL,'Senior Operator','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1390,'Bhola','Gupta','Bhola Gupta',NULL,'CGL Entry Operator','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1391,'Sharman','Ray','Sharman Ray',NULL,'Operato','OP/PPPL',NULL,NULL,NULL,'OP/PPPL','D022',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1392,'Christo','Rey Amurao Firme','Christo Rey Amurao Firme',NULL,'Senior Operator, CRM','OP/CRM',NULL,NULL,NULL,'OP/CRM','D017',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1394,'Rizaldy','Pineda Relevo','Rizaldy Pineda Relevo',NULL,'Foreman, PPPL','OP/PPPL',NULL,NULL,NULL,'OP/PPPL','D022',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1395,'Edwin','Amurao Lalogo','Edwin Amurao Lalogo',NULL,'Senior Operator, ARP','OP/PPPL',NULL,NULL,NULL,'OP/PPPL','D022',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1397,'Ashwini','Kumar Basra','Ashwini Kumar Basra',NULL,'Senior Operator, ARP','OP/PPPL',NULL,NULL,NULL,'OP/PPPL','D022',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1400,'Edilberto','Daluz Panganiban','Edilberto Daluz Panganiban',NULL,'Pot Operator, CGL','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1403,'Heru','Prasetyo Bn Paido','Heru Prasetyo Bn Paido',NULL,'QA Technician, ISL & SCL','OP/QA & CS',NULL,NULL,NULL,'OP/QA & CS','D025',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1407,'Raj','Narayan Sharma','Raj Narayan Sharma',NULL,'Welder','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1409,'Ansar','Alam Ansari','Ansar Alam Ansari',NULL,'Utilities Technician','OP/UT',NULL,NULL,NULL,'OP/UT','D026',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1410,'Alberto','Bautista Diaz','Alberto Bautista Diaz',NULL,'Storekeeper','Commercial/Warehouse',NULL,NULL,NULL,'Commercial/Warehouse','D009',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1413,'Crisanto','Abellera Magahis','Crisanto Abellera Magahis',NULL,'Operator, Utilities','OP/UT',NULL,NULL,NULL,'OP/UT','D026',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1414,'Raj','kumar Singh','Raj kumar Singh',NULL,'Senior Operator, CGL','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1415,'Jumanto','Karto Wiryadi','Jumanto Karto Wiryadi',NULL,'Electrical Technician, CGL, ISL, CCL, & SGP','OP/Electrical',NULL,NULL,NULL,'OP/Electrical','D018',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1418,'Wasiulla','Khan','Wasiulla Khan',NULL,'Senior Coordinator (CEO\'s Office)','CEO Office',NULL,NULL,NULL,'CEO Office','D005',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1432,'Shamrokh','Ahmed Al Alburikanie','Shamrokh Ahmed Al Alburikanie',NULL,'Mechanical Technician','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1437,'Kasinath','Ram Gope','Kasinath Ram Gope',NULL,'Electrical Engineer','OP/Electrical',NULL,NULL,NULL,'OP/Electrical','D018',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1443,'Shamim','Akhtar Akhtarali','Shamim Akhtar Akhtarali',NULL,'Mechanical Technician','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1448,'Mohammad','Anwarul Islam','Mohammad Anwarul Islam',NULL,'Mechanical Technician','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1449,'Abul','Kalam Azad','Abul Kalam Azad',NULL,'Electrical Technician','OP/Electrical',NULL,NULL,NULL,'OP/Electrical','D018',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1453,'Abdullah','Maki Al-Bahrani','Abdullah Maki Al-Bahrani',NULL,'Exit Operator & Crane / Forklift Operator.','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1455,'Nasser','Alwan Babli','Nasser Alwan Babli',NULL,'Mechanical Technician','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1456,'Nasser','Abdullah Al-Sani','Nasser Abdullah Al-Sani',NULL,'Electrical Technician','OP/Electrical',NULL,NULL,NULL,'OP/Electrical','D018',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1462,'Dia','Abdullah Al Mzain','Dia Abdullah Al Mzain',NULL,'Crane & Forklift Operator','OP/CRM',NULL,NULL,NULL,'OP/CRM','D017',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1469,'Abdullah','Meqdad Al Khamees','Abdullah Meqdad Al Khamees',NULL,'Crane & Forklift Operator','OP/Logistics',NULL,NULL,NULL,'OP/Logistics','D019',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1471,'Abdullah','Adel Al Rhyman','Abdullah Adel Al Rhyman',NULL,'Security Guard','Admin/Security',NULL,NULL,NULL,'Admin/Security','D004',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1476,'Raeed','Ali Al Hayk','Raeed Ali Al Hayk',NULL,'Crane & Forklift Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1477,'Aamir','Mushtaq','Aamir Mushtaq',NULL,'Financial Controller','Finance',NULL,NULL,NULL,'Finance','D010',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1479,'Sanjay','Verma','Sanjay Verma',NULL,'CGL Shift Engineer','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1481,'Jubran','Salah Al Muqbili','Jubran Salah Al Muqbili',NULL,'Customer Service Coordinator','Sales/Jeddah',NULL,NULL,NULL,'Sales/Jeddah','D028',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1482,'Rolando','Alicante','Rolando Alicante',NULL,'Entry Operator','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1483,'Erwin','Gonzales','Erwin Gonzales',NULL,'Zinc Pot Operator','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1487,'Pradeep','Samal','Pradeep Samal',NULL,'Shift Engineer','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1488,'Ahmed','Khan Habeeb Khan','Ahmed Khan Habeeb Khan',NULL,'General Accountant','Finance',NULL,NULL,NULL,'Finance','D010',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1492,'Basheer','Koormath','Basheer Koormath',NULL,'Sr. Safety Engineer','Admin/Safety',NULL,NULL,NULL,'Admin/Safety','D003',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1495,'Mohd','Ayub','Mohd Ayub',NULL,'General Accountant','Finance',NULL,NULL,NULL,'Finance','D010',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1497,'Abdulaziz','Fahad Alaradi','Abdulaziz Fahad Alaradi',NULL,'Security Guard','Admin/Security',NULL,NULL,NULL,'Admin/Security','D004',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1501,'Mabkhout','Nwaishi Al-Maabadi','Mabkhout Nwaishi Al-Maabadi',NULL,'Security Guard','Jed/Admin/Security',NULL,NULL,NULL,'Jed/Admin/Security','D012',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1504,'Ali','Hussain Al Brahim','Ali Hussain Al Brahim',NULL,'Logistics Coordinator','OP/Logistics',NULL,NULL,NULL,'OP/Logistics','D019',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1506,'Sanjay','Roychoudhury','Sanjay Roychoudhury',NULL,'Chief Executive Officer','CEO Office',NULL,NULL,NULL,'CEO Office','D005',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1507,'Ayman','Mahmoud Hamza','Ayman Mahmoud Hamza',NULL,'GM Commercial Operations','Commercial/Admin',NULL,NULL,NULL,'Commercial/Admin','D006',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1508,'Turki','Farhan Al Khaldi','Turki Farhan Al Khaldi',NULL,'Security Guard - Reception','Admin/Security',NULL,NULL,NULL,'Admin/Security','D004',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1516,'Mohammed','Saleh Al Ashur','Mohammed Saleh Al Ashur',NULL,'Mechanical Technician','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1517,'Mohammad','Imtiyaz Ahmed','Mohammad Imtiyaz Ahmed',NULL,'Mechanical Technician','OP/UT',NULL,NULL,NULL,'OP/UT','D026',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1520,'Mohammad','Omran Al Khazali','Mohammad Omran Al Khazali',NULL,'Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1526,'Mohammad','Ahmed Al Hassan','Mohammad Ahmed Al Hassan',NULL,'Operator','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1532,'Rashid','Mounir Al Hajiri','Rashid Mounir Al Hajiri',NULL,'Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1533,'Maram','Sami Al Almaei','Maram Sami Al Almaei',NULL,'Senior Software Development','IT',NULL,NULL,NULL,'IT','D011',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1535,'Hussain','Jaafar Al Haddad','Hussain Jaafar Al Haddad',NULL,'Electrical Engineer','OP/Electrical',NULL,NULL,NULL,'OP/Electrical','D018',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1536,'Mohammed','Jajal Uddin','Mohammed Jajal Uddin',NULL,'Operator','OP/CRM',NULL,NULL,NULL,'OP/CRM','D017',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1537,'Aklash','Boksh Kayum Boksh','Aklash Boksh Kayum Boksh',NULL,'Operator','OP/CRM',NULL,NULL,NULL,'OP/CRM','D017',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1538,'Azmath','Khan Habeeb Khan','Azmath Khan Habeeb Khan',NULL,'Shipping Officer','Commercial/Shipping',NULL,NULL,NULL,'Commercial/Shipping','D008',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1540,'Mohammed','Imran Abdul Majeed','Mohammed Imran Abdul Majeed',NULL,'Machinist','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1541,'Subhash','Adhikari','Subhash Adhikari',NULL,'Payoff Operator','OP/CRM',NULL,NULL,NULL,'OP/CRM','D017',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1542,'Kedar','Khatri','Kedar Khatri',NULL,'Exit Operator','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1543,'Haifa','Fraij Al-Shammari','Haifa Fraij Al-Shammari',NULL,'Senior Officer (Accountant receivable)','Finance',NULL,NULL,NULL,'Finance','D010',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1544,'Md','Abu Talab','Md Abu Talab',NULL,'Exit Operator','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1545,'Mishu','Saha','Mishu Saha',NULL,'Operator','OP/PPPL',NULL,NULL,NULL,'OP/PPPL','D022',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1546,'Md','Sirajul Islam','Md Sirajul Islam',NULL,'Operator','OP/CRM',NULL,NULL,NULL,'OP/CRM','D017',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1547,'Md','Rehan Uddin','Md Rehan Uddin',NULL,'Electrical Technician','OP/Electrical',NULL,NULL,NULL,'OP/Electrical','D018',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1549,'Hassan','Jaafar Al Haddad','Hassan Jaafar Al Haddad',NULL,'Electrical Technician','OP/Electrical',NULL,NULL,NULL,'OP/Electrical','D018',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1551,'Ahmed','Ali Al Betty','Ahmed Ali Al Betty',NULL,'Mechanical Technician','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1553,'Amjad','Al Shammari','Amjad Al Shammari',NULL,'Senior Sales Administration','Sales',NULL,NULL,NULL,'Sales','D027',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1554,'Ali','Hussain Al Saleh','Ali Hussain Al Saleh',NULL,'Forklift Operator','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1556,'Sara','Abdulatif Al Arfaj','Sara Abdulatif Al Arfaj',NULL,'Accounts Payable Officer','Finance',NULL,NULL,NULL,'Finance','D010',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1558,'Hakimah','Ahmed Al Ahmed','Hakimah Ahmed Al Ahmed',NULL,'LAB Supervisor','OP/QA & CS',NULL,NULL,NULL,'OP/QA & CS','D025',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1559,'Leonardo','Nabus Velonta','Leonardo Nabus Velonta',NULL,'Mechanical Technician','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1560,'Reyaz','Ahmad Ejazi','Reyaz Ahmad Ejazi',NULL,'Sales Export Executive','Sales',NULL,NULL,NULL,'Sales','D027',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1563,'Montazar','Bassam Al Muwais','Montazar Bassam Al Muwais',NULL,'Mechanical Engineer','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1564,'Ibn','Batuta Rihan','Ibn Batuta Rihan',NULL,'Materials Officer','Commercial/Admin',NULL,NULL,NULL,'Commercial/Admin','D006',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1569,'Abbas','Hussain Al Mulla','Abbas Hussain Al Mulla',NULL,'Crane Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1574,'Montather','Abdulkareem Al Awami','Montather Abdulkareem Al Awami',NULL,'Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1576,'Moayad','Ajaj','Moayad Ajaj',NULL,'Crane & Forklift Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1577,'Ahmed','Al Khamis','Ahmed Al Khamis',NULL,'Operator','OP/Logistics',NULL,NULL,NULL,'OP/Logistics','D019',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1578,'Mohammad','Ibrahim','Mohammad Ibrahim',NULL,'Mechanical Technician','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1579,'Md','Nur Islam','Md Nur Islam',NULL,'Mechanical Technician','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1580,'Md','Abu Sayed Antar','Md Abu Sayed Antar',NULL,'Operator','OP/CRM',NULL,NULL,NULL,'OP/CRM','D017',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1584,'Ali','Mohammed AL Shumrani','Ali Mohammed AL Shumrani',NULL,'Local Buyer','Commercial/Admin',NULL,NULL,NULL,'Commercial/Admin','D006',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1585,'Khokan','Chandra Nath','Khokan Chandra Nath',NULL,'Mechanical Foreman','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1586,'Tofazzal','Hossan Ahamed','Tofazzal Hossan Ahamed',NULL,'QA Officer','OP/QA & CS',NULL,NULL,NULL,'OP/QA & CS','D025',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1587,'MD','Sharif Md Alamgir','MD Sharif Md Alamgir',NULL,'Pot Operator','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1594,'Sandeep','Kumar','Sandeep Kumar',NULL,'Mechanical Technician','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1596,'Syed','Fahad Ahmed','Syed Fahad Ahmed',NULL,'Infrastructure and Network Engineer','IT',NULL,NULL,NULL,'IT','D011',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1598,'Siddique','Ahammed Fazal Ahmed','Siddique Ahammed Fazal Ahmed',NULL,'Teaboy','Admin/HR - General Service',NULL,NULL,NULL,'Admin/HR - General Service','D002',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1602,'Abdulmalik','Adel Alshami','Abdulmalik Adel Alshami',NULL,'Crane and Forklift Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1604,'Ahmed','Saud Al-Malki','Ahmed Saud Al-Malki',NULL,'Crane and Forklift Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1605,'Moueyd','Abdulkareem Al-Sadiq','Moueyd Abdulkareem Al-Sadiq',NULL,'Storekeeper','Commercial/Warehouse',NULL,NULL,NULL,'Commercial/Warehouse','D009',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1610,'Manjeet','Kumar Patel','Manjeet Kumar Patel',NULL,'Utilities Operator','OP/UT',NULL,NULL,NULL,'OP/UT','D026',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1611,'Mohammed','Ali AL-Ghazwi','Mohammed Ali AL-Ghazwi',NULL,'Electrical Engineer','OP/Electrical',NULL,NULL,NULL,'OP/Electrical','D018',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1613,'Baqer','Hussain Al-Ibrahim','Baqer Hussain Al-Ibrahim',NULL,'Crane and Forklift Operator','OP/CRM',NULL,NULL,NULL,'OP/CRM','D017',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1616,'Dharmendra','Prasad Yadav','Dharmendra Prasad Yadav',NULL,'CGL Senior Operator','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1617,'Yasser','Rashed AlKhaldi','Yasser Rashed AlKhaldi',NULL,'Security Guard','Admin/Security',NULL,NULL,NULL,'Admin/Security','D004',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1618,'Amal','Fayez Al Shammari','Amal Fayez Al Shammari',NULL,'Materials Officer','Commercial/Admin',NULL,NULL,NULL,'Commercial/Admin','D006',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1621,'Shabib','Khalid AL-Subaie','Shabib Khalid AL-Subaie',NULL,'Crane and Forklift Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1622,'Mohammed','Faleh ALSubaie','Mohammed Faleh ALSubaie',NULL,'System Administrator','IT',NULL,NULL,NULL,'IT','D011',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1623,'Naresh','Kumar Singh','Naresh Kumar Singh',NULL,'Electrical Technician','OP/Electrical',NULL,NULL,NULL,'OP/Electrical','D018',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1630,'Mahdi','Abdulraouf Al Asif','Mahdi Abdulraouf Al Asif',NULL,'Crane and Forklift Operator ','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1631,'Daagsh','Saad Al Melaihi','Daagsh Saad Al Melaihi',NULL,'RGM Operator','OP/CRM',NULL,NULL,NULL,'OP/CRM','D017',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1634,'Abdullah','Mohammed Al Hassan','Abdullah Mohammed Al Hassan',NULL,'Mechanical Technician','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1635,'Vikas','Kumar Sudama Singh','Vikas Kumar Sudama Singh',NULL,'Shift Engineer','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1636,'Hussain','Mohammad Al Muwais','Hussain Mohammad Al Muwais',NULL,'Crane and Forklift Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1638,'Akram','Al Dawood','Akram Al Dawood',NULL,'Crane & Forklift Operator','OP/Logistics',NULL,NULL,NULL,'OP/Logistics','D019',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1639,'Omar','Hassan AlQurain','Omar Hassan AlQurain',NULL,'Crane & Forklift Operator','OP/Logistics',NULL,NULL,NULL,'OP/Logistics','D019',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1641,'Yahya','Al Gubaili','Yahya Al Gubaili',NULL,'Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1644,'Hamdan','Abdullah Al Shamrani','Hamdan Abdullah Al Shamrani',NULL,'Crane and Forklift Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1645,'Yahya','Zakaria Al Saeed','Yahya Zakaria Al Saeed',NULL,'Crane & Forklift Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1646,'Mustafa','Abdullah Al Matrood','Mustafa Abdullah Al Matrood',NULL,'Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1648,'Mohatashim','Ahmad','Mohatashim Ahmad',NULL,'Operator','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1650,'Ahmed','Al Dawood','Ahmed Al Dawood',NULL,'Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1651,'Nawaf','Salem Al Khalidi','Nawaf Salem Al Khalidi',NULL,'Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1652,'Ahmed','Al Shaikh Ali','Ahmed Al Shaikh Ali',NULL,'Crane & Forklift Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1653,'Mohammad','Nur Uddin','Mohammad Nur Uddin',NULL,'Operator - CRM','OP/CRM',NULL,NULL,NULL,'OP/CRM','D017',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1654,'Ayman','Hamaydeh','Ayman Hamaydeh',NULL,'Head- Supply Chain','Commercial/Admin',NULL,NULL,NULL,'Commercial/Admin','D006',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1655,'Mohammed','Shajahan','Mohammed Shajahan',NULL,'Mechanical Technician','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1657,'Odai','Thaer Rashad Obaid','Odai Thaer Rashad Obaid',NULL,'Marketing Coordinator','Commercial/ Business Development',NULL,NULL,NULL,'Commercial/ Business Development','D007',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1659,'Alexander','Balucanag','Alexander Balucanag',NULL,'Storekeeper','Commercial/Warehouse',NULL,NULL,NULL,'Commercial/Warehouse','D009',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1660,'Abdulellah','M. Aba Hussain','Abdulellah M. Aba Hussain',NULL,'Shipping & Clearance Coordinator','Commercial/Shipping',NULL,NULL,NULL,'Commercial/Shipping','D008',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1661,'Mohammed','Baroom','Mohammed Baroom',NULL,'Sales Engineer','Sales/Jeddah',NULL,NULL,NULL,'Sales/Jeddah','D028',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1662,'Unjang','Kardi Sakiman','Unjang Kardi Sakiman',NULL,'Operator, CGL','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1663,'Ahmad','Yanto','Ahmad Yanto',NULL,'Operator, CGL','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1664,'Tri','Mugiono','Tri Mugiono',NULL,'QA Technician','OP/QA & CS',NULL,NULL,NULL,'OP/QA & CS','D025',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1666,'Khaled','Naif Al Sultan','Khaled Naif Al Sultan',NULL,'Asst. Safety Officer','Admin/Safety',NULL,NULL,NULL,'Admin/Safety','D003',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1667,'Fahad','Al Olayan','Fahad Al Olayan',NULL,'Security Guard','Admin/Security',NULL,NULL,NULL,'Admin/Security','D004',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1668,'Ahmed','Qasim Al Alawi','Ahmed Qasim Al Alawi',NULL,'Crane Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1669,'Murdhi','Mohammed Al Dossari','Murdhi Mohammed Al Dossari',NULL,'OP/Logistics','OP/Logistics',NULL,NULL,NULL,'OP/Logistics','D019',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1670,'Abdullah','Mohammed Al Zahrani','Abdullah Mohammed Al Zahrani',NULL,'Crane Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1672,'Mohammad','Jahangir','Mohammad Jahangir',NULL,'Storekeeper','Commercial/Warehouse',NULL,NULL,NULL,'Commercial/Warehouse','D009',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1674,'Ammar','Yasir Al Khamis','Ammar Yasir Al Khamis',NULL,'Crane & Forklift Operator','OP/Logistics',NULL,NULL,NULL,'OP/Logistics','D019',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1675,'Mohammed','Hassan Al Khatem','Mohammed Hassan Al Khatem',NULL,'Crane Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1676,'Ali','Akbar Al Abdullah','Ali Akbar Al Abdullah',NULL,'Mechanical Technician / Welder','OP/CRM',NULL,NULL,NULL,'OP/CRM','D017',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1677,'Mohammed','Fouad Al Mouais','Mohammed Fouad Al Mouais',NULL,'Crane & Forklift Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1678,'Baburaj','Sankuru','Baburaj Sankuru',NULL,'Muti-Technician','OP/UT',NULL,NULL,NULL,'OP/UT','D026',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1679,'Nauphal','Raheema','Nauphal Raheema',NULL,'Driver','Admin/HR - General Service',NULL,NULL,NULL,'Admin/HR - General Service','D002',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1680,'Jaseem','Jabbar','Jaseem Jabbar',NULL,'Driver','Admin/HR - General Service',NULL,NULL,NULL,'Admin/HR - General Service','D002',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1681,'Ahmed','Mohammed Al Essa','Ahmed Mohammed Al Essa',NULL,'Crane & Forklift Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1683,'Hadi','Mohammed Al Essa','Hadi Mohammed Al Essa',NULL,'Operator','OP/CRM',NULL,NULL,NULL,'OP/CRM','D017',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1684,'Uttam','Kumar Prasad','Uttam Kumar Prasad',NULL,'Electrical Technician','OP/Electrical',NULL,NULL,NULL,'OP/Electrical','D018',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1685,'Hassan','Jassim Al Sanadi','Hassan Jassim Al Sanadi',NULL,'Mechanical Technician','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1687,'Joud','Abdullah Al Mutairi','Joud Abdullah Al Mutairi',NULL,'Data Analytics Officer','CEO Office',NULL,NULL,NULL,'CEO Office','D005',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1688,'Irshad','Thadangai Mahammad','Irshad Thadangai Mahammad',NULL,'HVAC Operator','OP/UT',NULL,NULL,NULL,'OP/UT','D026',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1689,'Sujith','Ambali Parambil','Sujith Ambali Parambil',NULL,'Senior Projects Engineer','OP/Mechanical/Projects',NULL,NULL,NULL,'OP/Mechanical/Projects','D021',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1690,'Rakibul','Islam','Rakibul Islam',NULL,'Web Developer','IT',NULL,NULL,NULL,'IT','D011',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1691,'Hussain','Ali Al Ajaj','Hussain Ali Al Ajaj',NULL,'Logistics Coordinator','OP/Logistics',NULL,NULL,NULL,'OP/Logistics','D019',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1692,'Sanjay','Kumar Yadav','Sanjay Kumar Yadav',NULL,'Mechanical Technician','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1693,'Abdullah','Mudahi Alraythi','Abdullah Mudahi Alraythi',NULL,'Security Guard','Admin/Security',NULL,NULL,NULL,'Admin/Security','D004',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1694,'MD','Sajid Ali','MD Sajid Ali',NULL,'Crane Mechanic','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1696,'Nasser','Abdulaziz Al Sahood','Nasser Abdulaziz Al Sahood',NULL,'Operator, Utilities','OP/UT',NULL,NULL,NULL,'OP/UT','D026',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1698,'Osamh','Saleh Al Shehri','Osamh Saleh Al Shehri',NULL,'Administrative Assistance','Admin/HR',NULL,NULL,NULL,'Admin/HR','D001',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1700,'Riswan','Valiyaveetil Salim','Riswan Valiyaveetil Salim',NULL,'Mechanical Technician','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1703,'Abdullah','Ali Al Madeh','Abdullah Ali Al Madeh',NULL,'Automation Technician','OP/Automation',NULL,NULL,NULL,'OP/Automation','D013',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1704,'Edirisinghe','Pasindu','Edirisinghe Pasindu',NULL,'Section Head – Customer Service','Sales',NULL,NULL,NULL,'Sales','D027',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1706,'Hassan','Al Yousif','Hassan Al Yousif',NULL,'Mechanical Technician','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1707,'Mohammad','Iliyas Khalid','Mohammad Iliyas Khalid',NULL,'Procurement Officer','Commercial/Admin',NULL,NULL,NULL,'Commercial/Admin','D006',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1708,'Meshal','Timah Al Subaie','Meshal Timah Al Subaie',NULL,'Mechanical Technician','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1709,'Siva','Baratam','Siva Baratam',NULL,'Shift Engineer','OP/Electrical',NULL,NULL,NULL,'OP/Electrical','D018',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1710,'Shaneer','Ummer Kunhi','Shaneer Ummer Kunhi',NULL,'UT & HVAC Technician','OP/UT',NULL,NULL,NULL,'OP/UT','D026',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1711,'Naimah','Radhi Al Jalal','Naimah Radhi Al Jalal',NULL,'Technical Services Officer','OP/QA & CS',NULL,NULL,NULL,'OP/QA & CS','D025',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1712,'Ahmed','Ali Al Kadim','Ahmed Ali Al Kadim',NULL,'Production Planning Technician','OP/Production Planning & R',NULL,NULL,NULL,'OP/Production Planning & R','D023',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1713,'Narender','Goswami','Narender Goswami',NULL,'Coater Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1714,'Harish','Kumanaru Koteswara','Harish Kumanaru Koteswara',NULL,'Coater Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1716,'Zahra','Abdullah Al brahim','Zahra Abdullah Al brahim',NULL,'QA Officer','OP/QA & CS',NULL,NULL,NULL,'OP/QA & CS','D025',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1717,'Mohammed','Yaser Al Abyadh','Mohammed Yaser Al Abyadh',NULL,'Warehouse Officer','Commercial/Warehouse',NULL,NULL,NULL,'Commercial/Warehouse','D009',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1718,'Mahdi','Adel Al Rubh','Mahdi Adel Al Rubh',NULL,'Laboratory Technician','OP/QA & CS',NULL,NULL,NULL,'OP/QA & CS','D025',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1719,'Alanood','Abdulaziz Al Fulaij','Alanood Abdulaziz Al Fulaij',NULL,'Executive Assistant','CEO Office',NULL,NULL,NULL,'CEO Office','D005',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1720,'Ahdab','Mohammed Al Qahtani','Ahdab Mohammed Al Qahtani',NULL,'HR & Admin Officer','Admin/HR',NULL,NULL,NULL,'Admin/HR','D001',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1721,'Sohail','Mohammed','Sohail Mohammed',NULL,'Production Planning Engineer','OP/Production Planning & R',NULL,NULL,NULL,'OP/Production Planning & R','D023',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1722,'Omar','Faruk','Omar Faruk',NULL,'Operator','OP/PPPL',NULL,NULL,NULL,'OP/PPPL','D022',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1723,'Parth','Barua','Parth Barua',NULL,'Sr. Operator','OP/PPPL',NULL,NULL,NULL,'OP/PPPL','D022',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1724,'Paul','Rhayan Balbin','Paul Rhayan Balbin',NULL,'Utilities Operator','OP/UT',NULL,NULL,NULL,'OP/UT','D026',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1725,'Samuel','Espaldon Hetoya','Samuel Espaldon Hetoya',NULL,'Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1726,'Francis','Pantua','Francis Pantua',NULL,'Mechanical Technician','OP/UT',NULL,NULL,NULL,'OP/UT','D026',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1727,'Joel','Jr Magada Patria','Joel Jr Magada Patria',NULL,'Mechanical Technician','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1728,'Mohammad','Saad Yanallah','Mohammad Saad Yanallah',NULL,'Accounting Officer','Finance',NULL,NULL,NULL,'Finance','D010',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1729,'Abhishek','Kumar Pandey','Abhishek Kumar Pandey',NULL,'Electrical Technician','OP/UT',NULL,NULL,NULL,'OP/UT','D026',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1730,'Shrikant','Omparkash','Shrikant Omparkash',NULL,'Mechanical Technician','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1731,'Deepak','Kumar','Deepak Kumar',NULL,'Mechanical Technician','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1732,'Riyasullah','Dhuniya','Riyasullah Dhuniya',NULL,'CGL Operator','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1733,'Umeshwor','Chaudhary Kurmi','Umeshwor Chaudhary Kurmi',NULL,'CGL Operator','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1734,'Abdulrehman','Ali Al Shehri','Abdulrehman Ali Al Shehri',NULL,'Assistant Safety Officer','Admin/Safety',NULL,NULL,NULL,'Admin/Safety','D003',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1735,'Abdul','Nayeem Mohammed','Abdul Nayeem Mohammed',NULL,'Senior Analyst (Sap Fico)','IT',NULL,NULL,NULL,'IT','D011',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1736,'Muhamad','Aliyana L. Abdul','Muhamad Aliyana L. Abdul',NULL,'QA Technician','OP/QA & CS',NULL,NULL,NULL,'OP/QA & CS','D025',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1737,'Zaenal','Arifin Syarifudin Yahya','Zaenal Arifin Syarifudin Yahya',NULL,'QA Technician','OP/QA & CS',NULL,NULL,NULL,'OP/QA & CS','D025',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1738,'Zion','Praveen Raj Stephen','Zion Praveen Raj Stephen',NULL,'Utilities Operator','OP/UT',NULL,NULL,NULL,'OP/UT','D026',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1739,'Montazar','Tamim Al Sadah','Montazar Tamim Al Sadah',NULL,'Shipping Operations Administrator','Commercial/Shipping',NULL,NULL,NULL,'Commercial/Shipping','D008',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1740,'Abdullah','Mohammed Abo Kabws','Abdullah Mohammed Abo Kabws',NULL,'Crane & Forklift Operator','OP/CCL - LOG ?',NULL,NULL,NULL,'OP/CCL - LOG ?','D015',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1741,'Mojtaba','Zaki Al Saeed','Mojtaba Zaki Al Saeed',NULL,'Crane & Forklift Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1743,'Abdulrahman','Abdullah Al Shehri','Abdulrahman Abdullah Al Shehri',NULL,'Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1745,'Meshari','Nazal Al Anazi','Meshari Nazal Al Anazi',NULL,'Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1746,'Ali','Mohammed Al Khalaf','Ali Mohammed Al Khalaf',NULL,'Logistics Coordinator','OP/Logistics',NULL,NULL,NULL,'OP/Logistics','D019',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1747,'Ali','Mohamed Al Dawood','Ali Mohamed Al Dawood',NULL,'Logistics Coordinator','OP/Logistics',NULL,NULL,NULL,'OP/Logistics','D019',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1748,'Faris','Abdullah Al Oraiyj','Faris Abdullah Al Oraiyj',NULL,'Operator','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1749,'Abdulmajeed','Mosa Al Faifi','Abdulmajeed Mosa Al Faifi',NULL,'Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1750,'Hassan','Abdulaziz Alameer','Hassan Abdulaziz Alameer',NULL,'Senior Officer-Safety & Compliance','Admin/Safety',NULL,NULL,NULL,'Admin/Safety','D003',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1751,'Moaiad','Mohammed Al Awami','Moaiad Mohammed Al Awami',NULL,'Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1752,'Hassan','Yousif Al Yousif','Hassan Yousif Al Yousif',NULL,'Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1753,'Abdulrahman','M. Al Mghrabi','Abdulrahman M. Al Mghrabi',NULL,'Mechanical Technician','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1754,'Rayan','Ali Otayn','Rayan Ali Otayn',NULL,'Mechanical Technician','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1755,'Hala','Mishkhas Aldossary','Hala Mishkhas Aldossary',NULL,'Sales Administrator','Sales',NULL,NULL,NULL,'Sales','D027',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1756,'Adnan','Qayyum Abdul Qayyum','Adnan Qayyum Abdul Qayyum',NULL,'Project Accountant','Finance',NULL,NULL,NULL,'Finance','D010',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1757,'AlHussain','Ali AL Sadiq','AlHussain Ali AL Sadiq',NULL,'Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1758,'Ibraheem','Saeed Al Qarni','Ibraheem Saeed Al Qarni',NULL,'Production Technician','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1759,'Mustafa','Mohammed ALEssa','Mustafa Mohammed ALEssa',NULL,'Crane & Forklift Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1760,'Rifash','Shareef Cheriyambadan','Rifash Shareef Cheriyambadan',NULL,'Customer Service Executive','Sales',NULL,NULL,NULL,'Sales','D027',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1761,'Ashutosh','Tiwari Prasiddha Narayan','Ashutosh Tiwari Prasiddha Narayan',NULL,'Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1762,'Sushil','Kumar Singh','Sushil Kumar Singh',NULL,'Electrical Technician','OP/Electrical',NULL,NULL,NULL,'OP/Electrical','D018',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1763,'Mohammad','Saimullah Jalil','Mohammad Saimullah Jalil',NULL,'HVAC Technician','OP/UT',NULL,NULL,NULL,'OP/UT','D026',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1764,'Gaurav','Khadka','Gaurav Khadka',NULL,'Paint Mixing Technician','OP/QA & CS',NULL,NULL,NULL,'OP/QA & CS','D025',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1765,'Musaab','Moosa Hamzy','Musaab Moosa Hamzy',NULL,'Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1766,'Hamdan','Mohammed Al-Shamrani','Hamdan Mohammed Al-Shamrani',NULL,'Operator','OP/CCL',NULL,NULL,NULL,'OP/CCL','D014',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1767,'Rakesh','Dahyabhai Kakadia','Rakesh Dahyabhai Kakadia',NULL,'Manager - Management Information System','IT',NULL,NULL,NULL,'IT','D011',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1768,'Siddharthan','Ramasundaram','Siddharthan Ramasundaram',NULL,'Shift Engineer','OP/Electrical',NULL,NULL,NULL,'OP/Electrical','D018',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1769,'Amar','Nasser Al Saad','Amar Nasser Al Saad',NULL,'Safety Officer','Admin/Safety',NULL,NULL,NULL,'Admin/Safety','D003',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1770,'Alberto','Perucho','Alberto Perucho',NULL,'Planning Engineer','OP/Electrical',NULL,NULL,NULL,'OP/Electrical','D018',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1771,'Irshad','Khan','Irshad Khan',NULL,'Mechanical Technician','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1773,'Javed','Ansari Ahmad','Javed Ansari Ahmad',NULL,'Senior Operator','OP/CGL',NULL,NULL,NULL,'OP/CGL','D016',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1774,'Sunil','Kumar Singh','Sunil Kumar Singh',NULL,'Mechanical Technician','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (1775,'Rajesh','Kumar Mahato','Rajesh Kumar Mahato',NULL,'Mechanical Technician','OP/Mechanical',NULL,NULL,NULL,'OP/Mechanical','D020',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (8002,'Wellington','Siriaco Antao','Wellington Siriaco Antao',NULL,'Technical Manager','OP/QA & CS',NULL,NULL,NULL,'OP/QA & CS','D025',NULL,1,NULL);
INSERT INTO `employee` (`empId`, `firstname`, `lastname`, `fullname`, `email`, `position`, `area`, `building`, `extension_number`, `section`, `department`, `deptId`, `supervisorEmpId`, `aktif`, `assetNum`) VALUES (8008,'Mahmoud','Ayman Hamzeh','Mahmoud Ayman Hamzeh',NULL,'Operations','Op/Projects',NULL,NULL,NULL,'Op/Projects','D024',NULL,1,NULL);

INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (290,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (549,'D025',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (567,'D026',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (582,'D017',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (584,'D017',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (587,'D023',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (589,'D019',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (590,'D019',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (609,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (648,'D022',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (798,'D019',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (809,'D009',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (822,'D001',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (860,'D001',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (877,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (926,'D018',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (927,'D025',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (932,'D017',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (939,'D025',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (944,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (945,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (948,'D017',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (949,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (951,'D027',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (958,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (959,'D017',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (964,'D017',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (973,'D017',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (990,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (992,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (993,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1002,'D006',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1017,'D025',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1019,'D022',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1024,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1027,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1028,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1030,'D004',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1036,'D013',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1040,'D019',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1041,'D025',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1047,'D022',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1056,'D018',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1057,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1058,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1068,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1074,'D025',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1076,'D023',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1078,'D025',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1082,'D017',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1083,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1091,'D018',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1092,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1097,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1102,'D004',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1103,'D004',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1116,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1125,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1134,'D018',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1137,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1138,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1141,'D018',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1143,'D025',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1144,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1153,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1158,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1166,'D010',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1168,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1178,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1182,'D026',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1187,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1188,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1189,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1191,'D018',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1195,'D018',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1200,'D017',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1204,'D022',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1206,'D022',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1207,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1208,'D022',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1211,'D025',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1212,'D017',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1215,'D006',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1218,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1220,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1222,'D022',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1223,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1227,'D025',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1228,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1229,'D027',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1233,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1234,'D023',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1236,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1237,'D022',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1238,'D022',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1240,'D022',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1243,'D001',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1245,'D025',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1246,'D006',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1247,'D025',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1248,'D017',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1249,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1250,'D023',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1252,'D018',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1253,'D018',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1254,'D018',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1259,'D018',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1260,'D017',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1264,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1266,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1267,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1278,'D018',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1280,'D017',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1281,'D022',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1283,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1284,'D013',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1286,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1288,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1289,'D017',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1290,'D004',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1294,'D004',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1295,'D011',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1296,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1297,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1298,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1302,'D022',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1307,'D012',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1309,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1310,'D026',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1314,'D018',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1316,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1317,'D018',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1323,'D025',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1326,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1330,'D025',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1331,'D026',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1332,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1334,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1335,'D019',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1340,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1341,'D017',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1343,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1346,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1347,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1348,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1350,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1351,'D017',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1352,'D018',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1361,'D018',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1366,'D013',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1367,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1370,'D017',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1371,'D022',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1373,'D017',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1375,'D025',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1376,'D017',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1378,'D009',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1379,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1380,'D017',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1383,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1384,'D018',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1386,'D028',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1387,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1388,'D025',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1389,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1390,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1391,'D022',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1392,'D017',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1394,'D022',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1395,'D022',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1397,'D022',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1400,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1403,'D025',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1407,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1409,'D026',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1410,'D009',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1413,'D026',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1414,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1415,'D018',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1418,'D005',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1432,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1437,'D018',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1443,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1448,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1449,'D018',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1453,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1455,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1456,'D018',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1462,'D017',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1469,'D019',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1471,'D004',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1476,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1477,'D010',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1479,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1481,'D028',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1482,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1483,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1487,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1488,'D010',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1492,'D003',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1495,'D010',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1497,'D004',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1501,'D012',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1504,'D019',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1506,'D005',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1507,'D006',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1508,'D004',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1516,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1517,'D026',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1520,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1526,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1532,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1533,'D011',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1535,'D018',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1536,'D017',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1537,'D017',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1538,'D008',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1540,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1541,'D017',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1542,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1543,'D010',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1544,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1545,'D022',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1546,'D017',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1547,'D018',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1549,'D018',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1551,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1553,'D027',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1554,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1556,'D010',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1558,'D025',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1559,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1560,'D027',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1563,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1564,'D006',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1569,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1574,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1576,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1577,'D019',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1578,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1579,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1580,'D017',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1584,'D006',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1585,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1586,'D025',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1587,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1594,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1596,'D011',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1598,'D002',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1602,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1604,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1605,'D009',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1610,'D026',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1611,'D018',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1613,'D017',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1616,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1617,'D004',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1618,'D006',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1621,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1622,'D011',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1623,'D018',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1630,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1631,'D017',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1634,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1635,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1636,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1638,'D019',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1639,'D019',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1641,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1644,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1645,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1646,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1648,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1650,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1651,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1652,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1653,'D017',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1654,'D006',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1655,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1657,'D007',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1659,'D009',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1660,'D008',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1661,'D028',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1662,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1663,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1664,'D025',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1666,'D003',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1667,'D004',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1668,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1669,'D019',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1670,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1672,'D009',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1674,'D019',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1675,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1676,'D017',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1677,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1678,'D026',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1679,'D002',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1680,'D002',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1681,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1683,'D017',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1684,'D018',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1685,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1687,'D005',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1688,'D026',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1689,'D021',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1690,'D011',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1691,'D019',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1692,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1693,'D004',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1694,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1696,'D026',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1698,'D001',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1700,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1703,'D013',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1704,'D027',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1706,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1707,'D006',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1708,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1709,'D018',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1710,'D026',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1711,'D025',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1712,'D023',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1713,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1714,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1716,'D025',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1717,'D009',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1718,'D025',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1719,'D005',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1720,'D001',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1721,'D023',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1722,'D022',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1723,'D022',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1724,'D026',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1725,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1726,'D026',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1727,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1728,'D010',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1729,'D026',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1730,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1731,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1732,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1733,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1734,'D003',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1735,'D011',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1736,'D025',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1737,'D025',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1738,'D026',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1739,'D008',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1740,'D015',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1741,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1743,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1745,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1746,'D019',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1747,'D019',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1748,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1749,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1750,'D003',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1751,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1752,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1753,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1754,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1755,'D027',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1756,'D010',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1757,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1758,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1759,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1760,'D027',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1761,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1762,'D018',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1763,'D026',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1764,'D025',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1765,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1766,'D014',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1767,'D011',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1768,'D018',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1769,'D003',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1770,'D018',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1771,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1773,'D016',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1774,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (1775,'D020',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (8002,'D025',1,'2026-08-10',NULL);
INSERT INTO `empdept` (`empId`, `deptId`, `active`, `assignedFrom`, `assignedTo`) VALUES (8008,'D024',1,'2026-08-10',NULL);

INSERT INTO `location` (`location`, `description`, `active`) VALUES ('1','WareHouse1',1);
INSERT INTO `location` (`location`, `description`, `active`) VALUES ('MAIN','Main IT Stock',1);

INSERT INTO `unit` (`idx`, `unit`, `description`) VALUES (1,'Unit','Unit');
INSERT INTO `unit` (`idx`, `unit`, `description`) VALUES (2,'PC','Pieces');
INSERT INTO `unit` (`idx`, `unit`, `description`) VALUES (3,'Bundle','Bundle');

INSERT INTO `users` (`username`, `email`, `nama`, `empId`, `password`, `active`, `registeredOn`) VALUES ('admin','admin@localhost.local','Administrator',NULL,'$2y$12$BXUHVM9PVyOT6A2BZ50vnOSZJvC8hT4RYRhg.o6kKSxGVg5qf7JRS',1,'2026-07-15');

INSERT INTO `user_role` (`username`, `role_code`, `assigned_at`, `assigned_by`) VALUES ('admin','ADMINISTRATOR','2026-07-29 09:48:19','admin');
INSERT INTO `user_role` (`username`, `role_code`, `assigned_at`, `assigned_by`) VALUES ('admin','DEPARTMENT_MANAGER','2026-07-29 09:48:19','admin');
INSERT INTO `user_role` (`username`, `role_code`, `assigned_at`, `assigned_by`) VALUES ('admin','HR_REQUESTER','2026-07-29 09:48:19','admin');
INSERT INTO `user_role` (`username`, `role_code`, `assigned_at`, `assigned_by`) VALUES ('admin','ISSUER','2026-07-29 09:48:19','admin');
INSERT INTO `user_role` (`username`, `role_code`, `assigned_at`, `assigned_by`) VALUES ('admin','IT_MANAGER','2026-07-29 09:48:19','admin');

INSERT INTO `vendor` (`vendor_id`, `vendor_name`, `contact_person`, `email`, `phone`, `address`, `tax_number`, `active`, `created_at`, `updated_at`) VALUES (1,'General Supplier',NULL,NULL,NULL,NULL,NULL,1,'2026-07-15 12:47:13','2026-07-15 12:47:13');
INSERT INTO `vendor` (`vendor_id`, `vendor_name`, `contact_person`, `email`, `phone`, `address`, `tax_number`, `active`, `created_at`, `updated_at`) VALUES (2,'Dell Technologies',NULL,NULL,NULL,NULL,NULL,1,'2026-07-16 11:29:04','2026-07-16 11:29:04');
INSERT INTO `vendor` (`vendor_id`, `vendor_name`, `contact_person`, `email`, `phone`, `address`, `tax_number`, `active`, `created_at`, `updated_at`) VALUES (3,'HP Inc.',NULL,NULL,NULL,NULL,NULL,1,'2026-07-16 11:29:04','2026-07-16 11:29:04');
INSERT INTO `vendor` (`vendor_id`, `vendor_name`, `contact_person`, `email`, `phone`, `address`, `tax_number`, `active`, `created_at`, `updated_at`) VALUES (4,'Lenovo',NULL,NULL,NULL,NULL,NULL,1,'2026-07-16 11:29:04','2026-07-16 11:29:04');
INSERT INTO `vendor` (`vendor_id`, `vendor_name`, `contact_person`, `email`, `phone`, `address`, `tax_number`, `active`, `created_at`, `updated_at`) VALUES (5,'Cisco Systems',NULL,NULL,NULL,NULL,NULL,1,'2026-07-16 11:29:04','2026-07-16 11:29:04');
INSERT INTO `vendor` (`vendor_id`, `vendor_name`, `contact_person`, `email`, `phone`, `address`, `tax_number`, `active`, `created_at`, `updated_at`) VALUES (6,'Microsoft',NULL,NULL,NULL,NULL,NULL,1,'2026-07-16 11:29:04','2026-07-16 11:29:04');
INSERT INTO `vendor` (`vendor_id`, `vendor_name`, `contact_person`, `email`, `phone`, `address`, `tax_number`, `active`, `created_at`, `updated_at`) VALUES (7,'APC by Schneider Electric','Rajesh','apc@electric.com','0123456789','DAMMAM, Saudi Arabia','30000056825',1,'2026-07-16 11:29:04','2026-08-09 06:14:13');
INSERT INTO `vendor` (`vendor_id`, `vendor_name`, `contact_person`, `email`, `phone`, `address`, `tax_number`, `active`, `created_at`, `updated_at`) VALUES (8,'Epson',NULL,NULL,NULL,NULL,NULL,1,'2026-07-16 11:29:04','2026-07-16 11:29:04');
INSERT INTO `vendor` (`vendor_id`, `vendor_name`, `contact_person`, `email`, `phone`, `address`, `tax_number`, `active`, `created_at`, `updated_at`) VALUES (9,'Synology',NULL,NULL,NULL,NULL,NULL,1,'2026-07-16 11:29:04','2026-07-16 11:29:04');

INSERT INTO `schema_migrations` (`version`, `description`, `batch`, `applied_at`) VALUES ('20260715_cpanel_full_database','Full fresh cPanel database schema and lookup data',1,'2026-07-15 12:47:13');
INSERT INTO `schema_migrations` (`version`, `description`, `batch`, `applied_at`) VALUES ('20260729_000001_warranty_timeline_indexes','Index purchase and warranty dates for scalable lifecycle alerts',2,'2026-07-29 10:08:06');
INSERT INTO `schema_migrations` (`version`, `description`, `batch`, `applied_at`) VALUES ('20260729_000002_remove_employee_gender','Remove the unused employee gender field',3,'2026-07-29 11:14:45');
INSERT INTO `schema_migrations` (`version`, `description`, `batch`, `applied_at`) VALUES ('20260804_000001_return_request_cancellation','Preserve return-request cancellation actor, time and reason',4,'2026-08-04 05:25:10');
INSERT INTO `schema_migrations` (`version`, `description`, `batch`, `applied_at`) VALUES ('20260809_000001_global_asset_condition','Add one current condition per asset and normalize live status',5,'2026-08-09 04:42:26');
INSERT INTO `schema_migrations` (`version`, `description`, `batch`, `applied_at`) VALUES ('20260809_000002_reconcile_global_asset_condition','Reconcile current asset condition and live lifecycle status',6,'2026-08-09 04:50:03');
INSERT INTO `schema_migrations` (`version`, `description`, `batch`, `applied_at`) VALUES ('20260810_000001_asset_update_audit','Retain an audit trail for every controlled in-stock asset edit',7,'2026-08-10 11:09:57');
INSERT INTO `schema_migrations` (`version`, `description`, `batch`, `applied_at`) VALUES ('20260813_000001_accessory_inventory','Add optional isolated quantity-based accessory inventory',8,'2026-08-13 00:00:00');
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
