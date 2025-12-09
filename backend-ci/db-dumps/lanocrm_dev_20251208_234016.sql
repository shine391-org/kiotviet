-- MySQL dump 10.13  Distrib 8.4.7, for Linux (x86_64)
--
-- Host: localhost    Database: lanocrm_dev
-- ------------------------------------------------------
-- Server version	8.4.7

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `module` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `model_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `model_id` bigint unsigned DEFAULT NULL,
  `old_values` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `new_values` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_date` (`user_id`,`created_at`),
  KEY `idx_module_model` (`module`,`model_type`,`model_id`),
  CONSTRAINT `fk_activity_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `activity_types`
--

DROP TABLE IF EXISTS `activity_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `activity_name` varchar(150) NOT NULL,
  `billing_rate` decimal(12,2) DEFAULT '0.00',
  `cost_rate` decimal(12,2) DEFAULT '0.00',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_activity_name` (`activity_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_types`
--

LOCK TABLES `activity_types` WRITE;
/*!40000 ALTER TABLE `activity_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `appointments`
--

DROP TABLE IF EXISTS `appointments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `appointments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned DEFAULT NULL,
  `lead_id` bigint unsigned DEFAULT NULL,
  `contract_id` bigint unsigned DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` varchar(30) DEFAULT 'scheduled',
  `notes` text,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_appointment_time` (`start_time`,`end_time`),
  KEY `fk_appointments_customer_id` (`customer_id`),
  KEY `fk_appointments_lead_id` (`lead_id`),
  KEY `fk_appointments_contract_id` (`contract_id`),
  CONSTRAINT `fk_appointments_contract_id` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_appointments_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_appointments_lead_id` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointments`
--

LOCK TABLES `appointments` WRITE;
/*!40000 ALTER TABLE `appointments` DISABLE KEYS */;
/*!40000 ALTER TABLE `appointments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `approval_actions`
--

DROP TABLE IF EXISTS `approval_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `approval_actions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `approval_id` bigint unsigned NOT NULL,
  `action` varchar(20) NOT NULL,
  `actor_id` bigint unsigned DEFAULT NULL,
  `notes` text,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_actions_approval` (`approval_id`),
  KEY `fk_approval_actions_actor_id` (`actor_id`),
  CONSTRAINT `fk_approval_actions_actor_id` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_approval_actions_approval_id` FOREIGN KEY (`approval_id`) REFERENCES `approvals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approval_actions`
--

LOCK TABLES `approval_actions` WRITE;
/*!40000 ALTER TABLE `approval_actions` DISABLE KEYS */;
/*!40000 ALTER TABLE `approval_actions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `approvals`
--

DROP TABLE IF EXISTS `approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `approvals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(50) DEFAULT 'order',
  `entity_id` bigint unsigned NOT NULL,
  `order_id` bigint unsigned DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `approver_queue` text NOT NULL,
  `current_index` int DEFAULT '0',
  `current_approver_id` bigint unsigned DEFAULT NULL,
  `requested_by` bigint unsigned DEFAULT NULL,
  `requested_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_approval_entity` (`entity_type`,`entity_id`),
  KEY `idx_approval_order` (`order_id`,`status`),
  KEY `fk_approvals_current_approver_id` (`current_approver_id`),
  CONSTRAINT `fk_approvals_current_approver_id` FOREIGN KEY (`current_approver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_approvals_order_id` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approvals`
--

LOCK TABLES `approvals` WRITE;
/*!40000 ALTER TABLE `approvals` DISABLE KEYS */;
/*!40000 ALTER TABLE `approvals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assets`
--

DROP TABLE IF EXISTS `assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `asset_number` varchar(60) NOT NULL,
  `asset_name` varchar(255) NOT NULL,
  `category` varchar(120) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `cost` decimal(14,2) DEFAULT '0.00',
  `location` varchar(255) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'draft',
  `salvage_value` decimal(14,2) DEFAULT '0.00',
  `useful_life_months` int DEFAULT '0',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_asset_number` (`asset_number`),
  KEY `idx_asset_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assets`
--

LOCK TABLES `assets` WRITE;
/*!40000 ALTER TABLE `assets` DISABLE KEYS */;
/*!40000 ALTER TABLE `assets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assignment_logs`
--

DROP TABLE IF EXISTS `assignment_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assignment_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `assignment_rule_id` bigint unsigned NOT NULL,
  `entity_type` varchar(80) NOT NULL,
  `entity_id` bigint unsigned NOT NULL,
  `assignee_id` bigint unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_assignment_log_rule` (`assignment_rule_id`),
  KEY `idx_assignment_log_entity` (`entity_type`,`entity_id`),
  KEY `fk_assignment_logs_assignee_id` (`assignee_id`),
  CONSTRAINT `fk_assignment_logs_assignee_id` FOREIGN KEY (`assignee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_assignment_logs_assignment_rule_id` FOREIGN KEY (`assignment_rule_id`) REFERENCES `assignment_rules` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assignment_logs`
--

LOCK TABLES `assignment_logs` WRITE;
/*!40000 ALTER TABLE `assignment_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `assignment_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assignment_rules`
--

DROP TABLE IF EXISTS `assignment_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assignment_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `entity_type` varchar(80) NOT NULL,
  `strategy` varchar(50) DEFAULT 'round_robin',
  `team_members` json NOT NULL,
  `last_assigned_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_assignment_entity` (`entity_type`,`is_active`),
  KEY `fk_assignment_rules_last_assigned_id` (`last_assigned_id`),
  CONSTRAINT `fk_assignment_rules_last_assigned_id` FOREIGN KEY (`last_assigned_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assignment_rules`
--

LOCK TABLES `assignment_rules` WRITE;
/*!40000 ALTER TABLE `assignment_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `assignment_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendances`
--

DROP TABLE IF EXISTS `attendances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `attendance_date` date NOT NULL,
  `status` varchar(20) DEFAULT 'present',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attendance_day` (`employee_id`,`attendance_date`),
  CONSTRAINT `fk_attendances_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendances`
--

LOCK TABLES `attendances` WRITE;
/*!40000 ALTER TABLE `attendances` DISABLE KEYS */;
INSERT INTO `attendances` VALUES (1,1,'2025-12-06','present','2025-12-06 03:43:05','2025-12-06 03:43:05'),(2,2,'2025-12-06','present','2025-12-06 03:43:05','2025-12-06 03:43:05'),(3,3,'2025-12-06','present','2025-12-06 03:43:05','2025-12-06 03:43:05'),(4,1,'2025-12-05','present','2025-12-06 03:43:05','2025-12-06 03:43:05'),(5,2,'2025-12-05','present','2025-12-06 03:43:05','2025-12-06 03:43:05'),(6,3,'2025-12-05','present','2025-12-06 03:43:05','2025-12-06 03:43:05'),(7,1,'2025-12-04','present','2025-12-06 03:43:05','2025-12-06 03:43:05'),(8,2,'2025-12-04','present','2025-12-06 03:43:05','2025-12-06 03:43:05'),(9,3,'2025-12-04','present','2025-12-06 03:43:05','2025-12-06 03:43:05'),(10,1,'2025-12-03','present','2025-12-06 03:43:05','2025-12-06 03:43:05'),(11,2,'2025-12-03','present','2025-12-06 03:43:05','2025-12-06 03:43:05'),(12,3,'2025-12-03','present','2025-12-06 03:43:05','2025-12-06 03:43:05'),(13,1,'2025-12-02','present','2025-12-06 03:43:05','2025-12-06 03:43:05'),(14,2,'2025-12-02','present','2025-12-06 03:43:05','2025-12-06 03:43:05'),(15,3,'2025-12-02','present','2025-12-06 03:43:05','2025-12-06 03:43:05');
/*!40000 ALTER TABLE `attendances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attribute_options`
--

DROP TABLE IF EXISTS `attribute_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attribute_options` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `attribute_id` bigint unsigned NOT NULL,
  `option_name` varchar(255) DEFAULT NULL,
  `color_code` varchar(50) DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `status` varchar(20) DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_attribute_options_attribute_id` (`attribute_id`),
  CONSTRAINT `fk_attribute_options_attribute_id` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attribute_options`
--

LOCK TABLES `attribute_options` WRITE;
/*!40000 ALTER TABLE `attribute_options` DISABLE KEYS */;
/*!40000 ALTER TABLE `attribute_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attributes`
--

DROP TABLE IF EXISTS `attributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attributes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `attribute_key` varchar(100) DEFAULT NULL,
  `type` varchar(50) DEFAULT 'select',
  `is_required` tinyint DEFAULT '0',
  `is_filterable` tinyint DEFAULT '0',
  `sort_order` int DEFAULT '0',
  `status` varchar(20) DEFAULT 'active',
  `is_visible` tinyint DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attributes`
--

LOCK TABLES `attributes` WRITE;
/*!40000 ALTER TABLE `attributes` DISABLE KEYS */;
/*!40000 ALTER TABLE `attributes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `entity_type` varchar(120) NOT NULL,
  `entity_id` bigint unsigned NOT NULL,
  `action` varchar(80) NOT NULL,
  `changes` json DEFAULT NULL,
  `actor_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_audit_logs_entity` (`entity_type`,`entity_id`),
  KEY `idx_audit_logs_company` (`company_id`),
  KEY `fk_audit_logs_actor_id` (`actor_id`),
  CONSTRAINT `fk_audit_logs_actor_id` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_audit_logs_company_id` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bank_accounts`
--

DROP TABLE IF EXISTS `bank_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bank_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `bank_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `account_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `account_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `branch_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `qr_template` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'QR code template URL pattern',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bank_code` (`bank_code`),
  KEY `idx_bank_active` (`is_active`),
  KEY `idx_bank_branch` (`branch_id`),
  CONSTRAINT `fk_bank_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_accounts`
--

LOCK TABLES `bank_accounts` WRITE;
/*!40000 ALTER TABLE `bank_accounts` DISABLE KEYS */;
INSERT INTO `bank_accounts` VALUES (1,'BIDV','BIDV','2206331765','NGUYEN THI PHUONG ANH','Chi nhánh Hà Nội',1,1,1,NULL,NULL,1,NULL,'2025-12-06 10:24:18','2025-12-06 10:24:18'),(2,'Vietcombank','VCB','1234567890','NGUYEN THI PHUONG ANH','Chi nhánh Cầu Giấy',1,0,1,NULL,NULL,2,NULL,'2025-12-06 10:24:18','2025-12-06 10:24:18'),(3,'Techcombank','TCB','19035678901234','NGUYEN THI PHUONG ANH','Chi nhánh Đống Đa',NULL,0,1,NULL,NULL,3,NULL,'2025-12-06 10:24:18','2025-12-06 10:24:18'),(4,'MB Bank','MB','0981234567890','NGUYEN THI PHUONG ANH','Chi nhánh HCM',2,1,1,NULL,NULL,1,NULL,'2025-12-06 10:24:18','2025-12-06 10:24:18'),(5,'VPBank','VPB','123456789012','NGUYEN THI PHUONG ANH',NULL,NULL,0,1,NULL,NULL,4,NULL,'2025-12-06 10:24:18','2025-12-06 10:24:18');
/*!40000 ALTER TABLE `bank_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bank_reconciliation_logs`
--

DROP TABLE IF EXISTS `bank_reconciliation_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank_reconciliation_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bank_reconciliation_id` bigint unsigned NOT NULL,
  `action` varchar(50) NOT NULL,
  `message` text,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bank_reco_log` (`bank_reconciliation_id`),
  CONSTRAINT `fk_bank_reconciliation__bank_reconciliation_` FOREIGN KEY (`bank_reconciliation_id`) REFERENCES `bank_reconciliations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_reconciliation_logs`
--

LOCK TABLES `bank_reconciliation_logs` WRITE;
/*!40000 ALTER TABLE `bank_reconciliation_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `bank_reconciliation_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bank_reconciliations`
--

DROP TABLE IF EXISTS `bank_reconciliations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank_reconciliations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bank_statement_id` bigint unsigned NOT NULL,
  `payment_entry_id` bigint unsigned DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `matched_amount` decimal(14,2) DEFAULT '0.00',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bank_reco_statement` (`bank_statement_id`),
  KEY `fk_bank_reconciliations_payment_entry_id` (`payment_entry_id`),
  CONSTRAINT `fk_bank_reconciliations_bank_statement_id` FOREIGN KEY (`bank_statement_id`) REFERENCES `bank_statements` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bank_reconciliations_payment_entry_id` FOREIGN KEY (`payment_entry_id`) REFERENCES `payment_entries` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_reconciliations`
--

LOCK TABLES `bank_reconciliations` WRITE;
/*!40000 ALTER TABLE `bank_reconciliations` DISABLE KEYS */;
/*!40000 ALTER TABLE `bank_reconciliations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bank_statements`
--

DROP TABLE IF EXISTS `bank_statements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank_statements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `account_number` varchar(50) NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'VND',
  `reference_no` varchar(120) DEFAULT NULL,
  `reference_date` date DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'imported',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bank_statement_ref` (`reference_no`,`reference_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_statements`
--

LOCK TABLES `bank_statements` WRITE;
/*!40000 ALTER TABLE `bank_statements` DISABLE KEYS */;
/*!40000 ALTER TABLE `bank_statements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bill_of_materials`
--

DROP TABLE IF EXISTS `bill_of_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bill_of_materials` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `version` varchar(50) DEFAULT NULL,
  `quantity` decimal(12,3) DEFAULT '1.000',
  `uom` varchar(50) DEFAULT NULL,
  `cost` decimal(14,4) DEFAULT '0.0000',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bom_product` (`product_id`),
  KEY `idx_bom_active` (`is_active`),
  CONSTRAINT `fk_bill_of_materials_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bill_of_materials`
--

LOCK TABLES `bill_of_materials` WRITE;
/*!40000 ALTER TABLE `bill_of_materials` DISABLE KEYS */;
/*!40000 ALTER TABLE `bill_of_materials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bom_items`
--

DROP TABLE IF EXISTS `bom_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bom_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bom_id` bigint unsigned NOT NULL,
  `component_product_id` bigint unsigned NOT NULL,
  `quantity` decimal(12,3) DEFAULT '0.000',
  `uom` varchar(50) DEFAULT NULL,
  `scrap_percent` decimal(6,3) DEFAULT '0.000',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bom_item_bom` (`bom_id`),
  KEY `idx_bom_item_component` (`component_product_id`),
  CONSTRAINT `fk_bom_items_bom_id` FOREIGN KEY (`bom_id`) REFERENCES `bill_of_materials` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bom_items_component_product_id` FOREIGN KEY (`component_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bom_items`
--

LOCK TABLES `bom_items` WRITE;
/*!40000 ALTER TABLE `bom_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `bom_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `branches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
INSERT INTO `branches` VALUES (1,'Chi nhánh Hà Nội','HN01','active','2025-12-06 03:04:12',NULL,NULL),(2,'Chi nhánh HCM','HCM01','active','2025-12-06 03:04:12',NULL,NULL),(3,'Chi nhánh Đà Nẵng','DN01','active','2025-12-06 03:35:22','2025-12-06 03:35:22',NULL),(4,'Chi nhánh Cần Thơ','CT01','active','2025-12-06 03:35:22','2025-12-06 03:35:22',NULL),(5,'Chi nhánh Hải Phòng','HP01','active','2025-12-06 03:35:22','2025-12-06 03:35:22',NULL),(6,'Chi nhánh Test (Inactive)','TEST01','inactive','2025-12-06 03:35:22','2025-12-06 03:35:22',NULL);
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `campaign_members`
--

DROP TABLE IF EXISTS `campaign_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_members` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `campaign_id` bigint unsigned NOT NULL,
  `lead_id` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_campaign_member_campaign` (`campaign_id`),
  KEY `fk_campaign_members_customer_id` (`customer_id`),
  KEY `fk_campaign_members_lead_id` (`lead_id`),
  CONSTRAINT `fk_campaign_members_campaign_id` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_campaign_members_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_campaign_members_lead_id` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `campaign_members`
--

LOCK TABLES `campaign_members` WRITE;
/*!40000 ALTER TABLE `campaign_members` DISABLE KEYS */;
/*!40000 ALTER TABLE `campaign_members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `campaigns`
--

DROP TABLE IF EXISTS `campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaigns` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `status` varchar(50) DEFAULT 'draft',
  `source` varchar(100) DEFAULT NULL,
  `budget` decimal(14,2) DEFAULT '0.00',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `campaigns`
--

LOCK TABLES `campaigns` WRITE;
/*!40000 ALTER TABLE `campaigns` DISABLE KEYS */;
/*!40000 ALTER TABLE `campaigns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cash_transactions`
--

DROP TABLE IF EXISTS `cash_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cash_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('RECEIPT','PAYMENT') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `category` varchar(50) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `account_name` varchar(120) DEFAULT NULL,
  `description` text,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `reference_code` varchar(100) DEFAULT NULL,
  `branch_id` bigint unsigned NOT NULL,
  `created_by` bigint unsigned NOT NULL,
  `created_by_name` varchar(120) DEFAULT NULL,
  `staff_name` varchar(120) DEFAULT NULL,
  `payer_code` varchar(60) DEFAULT NULL,
  `payer_name` varchar(180) DEFAULT NULL,
  `payer_phone` varchar(50) DEFAULT NULL,
  `payer_address` varchar(255) DEFAULT NULL,
  `bank_account` varchar(60) DEFAULT NULL,
  `transfer_note` varchar(255) DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `note` text,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_type_category` (`type`,`category`),
  KEY `idx_branch` (`branch_id`),
  KEY `idx_reference` (`reference_type`,`reference_id`),
  KEY `idx_transaction_date` (`transaction_date`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_deleted_at` (`deleted_at`),
  KEY `idx_reference_id` (`reference_id`),
  CONSTRAINT `fk_cash_transactions_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cash_transactions_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cash_transactions_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cash_transactions_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_cash_amount` CHECK ((`amount` > 0))
) ENGINE=InnoDB AUTO_INCREMENT=227 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_transactions`
--

LOCK TABLES `cash_transactions` WRITE;
/*!40000 ALTER TABLE `cash_transactions` DISABLE KEYS */;
INSERT INTO `cash_transactions` VALUES (175,'RECEIPT',446000.00,'sales','CASH','approved','Quỹ demo','Thu đơn DH-DEMO-001 (unpaid)','order',211,'DH-DEMO-001',1,1,'Demo Admin','demo-staff','CUST-2001','Nguyễn Minh An','0912000001','12 Trần Hưng Đạo, Hà Nội',NULL,NULL,'2025-11-06','Thanh toán đủ','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(176,'RECEIPT',474600.00,'sales','BANK_TRANSFER','approved','Quỹ demo','Thu đơn DH-DEMO-002 (partial)','order',212,'DH-DEMO-002',2,1,'Demo Admin','demo-staff','CUST-2002','Trần Thu Hà','0912000002','89 Lý Thường Kiệt, Hà Nội',NULL,NULL,'2025-11-07','Thanh toán đủ','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(177,'RECEIPT',451660.00,'sales','COD','approved','Quỹ demo','Thu đơn DH-DEMO-003 (partial)','order',213,'DH-DEMO-003',3,1,'Demo Admin','demo-staff','CUST-2003','Phạm Gia Bảo','0912000003','22 Nguyễn Huệ, HCM',NULL,NULL,'2025-11-08','Thanh toán đủ','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(178,'RECEIPT',408000.00,'sales','CASH','approved','Quỹ demo','Thu đơn DH-DEMO-004 (partial)','order',214,'DH-DEMO-004',4,1,'Demo Admin','demo-staff','CUST-2004','Lê Hồng Nhung','0912000004','35 Hai Bà Trưng, HCM',NULL,NULL,'2025-11-09','Thanh toán đủ','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(179,'RECEIPT',424620.00,'sales','EWALLET','approved','Quỹ demo','Thu đơn DH-DEMO-005 (partial)','order',215,'DH-DEMO-005',5,1,'Demo Admin','demo-staff','CUST-2005','Vũ Hoàng Long','0912000005','15 Nguyễn Tri Phương, Đà Nẵng',NULL,NULL,'2025-11-10','Thanh toán đủ','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(180,'RECEIPT',487300.00,'sales','COD','approved','Quỹ demo','Thu đơn DH-DEMO-006 (partial)','order',216,'DH-DEMO-006',1,1,'Demo Admin','demo-staff','CUST-2006','Đặng Bích Trâm','0912000006','101 Võ Văn Tần, HCM',NULL,NULL,'2025-11-11','Thanh toán đủ','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(181,'RECEIPT',430000.00,'sales','BANK_TRANSFER','approved','Quỹ demo','Thu đơn DH-DEMO-007 (partial)','order',217,'DH-DEMO-007',2,1,'Demo Admin','demo-staff','CUST-2007','Huỳnh Tuấn Kiệt','0912000007','45 Trần Phú, Nha Trang',NULL,NULL,'2025-11-12','Thanh toán đủ','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(182,'RECEIPT',392280.00,'sales','CASH','approved','Quỹ demo','Thu đơn DH-DEMO-008 (partial)','order',218,'DH-DEMO-008',3,1,'Demo Admin','demo-staff','CUST-2008','Lý Thu Uyên','0912000008','68 Lê Lợi, Huế',NULL,NULL,'2025-11-13','Thanh toán đủ','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(183,'RECEIPT',457600.00,'sales','BANK_TRANSFER','approved','Quỹ demo','Thu đơn DH-DEMO-009 (paid)','order',219,'DH-DEMO-009',1,1,'Demo Admin','demo-staff','CUST-2011','Công ty Ánh Dương','0912000011','11 Duy Tân, Cầu Giấy, Hà Nội',NULL,NULL,'2025-11-14','Thanh toán đủ','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(184,'RECEIPT',611600.00,'sales','BANK_TRANSFER','approved','Quỹ demo','Thu đơn DH-DEMO-010 (paid)','order',220,'DH-DEMO-010',2,1,'Demo Admin','demo-staff','CUST-2012','CTCP Gỗ Xanh','0912000012','45 Pasteur, Quận 1, HCM',NULL,NULL,'2025-11-15','Thanh toán đủ','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(185,'RECEIPT',398370.00,'sales','CASH','approved','Quỹ demo','Thu đơn DH-DEMO-011 (paid)','order',221,'DH-DEMO-011',3,1,'Demo Admin','demo-staff','CUST-2013','Hộ KD Minh Quân','0912000013','22 Trần Phú, Nha Trang',NULL,NULL,'2025-11-16','Thanh toán đủ','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(186,'RECEIPT',459800.00,'sales','COD','approved','Quỹ demo','Thu đơn DH-DEMO-012 (paid)','order',222,'DH-DEMO-012',4,1,'Demo Admin','demo-staff','CUST-2014','Công ty Vận Tải Nhanh','0912000014','88 Kim Mã, Ba Đình, Hà Nội',NULL,NULL,'2025-11-17','Thanh toán đủ','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(187,'RECEIPT',594600.00,'sales','BANK_TRANSFER','approved','Quỹ demo','Thu đơn DH-DEMO-013 (paid)','order',223,'DH-DEMO-013',5,1,'Demo Admin','demo-staff','CUST-2015','CTY Thiết Kế Mộc','0912000015','12 Nguyễn Trãi, Quận 5, HCM',NULL,NULL,'2025-11-18','Thanh toán đủ','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(188,'RECEIPT',428400.00,'sales','CASH','approved','Quỹ demo','Thu đơn DH-DEMO-014 (paid)','order',224,'DH-DEMO-014',1,1,'Demo Admin','demo-staff','CUST-2016','Trịnh Quốc Thái','0912000016','14 Lê Duẩn, Hà Nội',NULL,NULL,'2025-11-19','Thanh toán đủ','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(189,'RECEIPT',487300.00,'sales','EWALLET','approved','Quỹ demo','Thu đơn DH-DEMO-015 (paid)','order',225,'DH-DEMO-015',2,1,'Demo Admin','demo-staff','CUST-2017','Đỗ Hồng Ngọc','0912000017','7 Nguyễn Văn Cừ, Hạ Long',NULL,NULL,'2025-11-20','Thanh toán đủ','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(190,'RECEIPT',416000.00,'sales','CASH','approved','Quỹ demo','Thu đơn DH-DEMO-016 (paid)','order',226,'DH-DEMO-016',3,1,'Demo Admin','demo-staff','CUST-2018','La Mỹ Duyên','0912000018','155 Lạch Tray, Hải Phòng',NULL,NULL,'2025-11-21','Thanh toán đủ','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(191,'RECEIPT',474600.00,'sales','BANK_TRANSFER','approved','Quỹ demo','Thu đơn DH-DEMO-017 (paid)','order',227,'DH-DEMO-017',4,1,'Demo Admin','demo-staff','CUST-2019','Đinh Mạnh Cường','0912000019','18 Lê Lợi, Vinh',NULL,NULL,'2025-11-22','Thanh toán đủ','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(192,'RECEIPT',410960.00,'sales','CASH','approved','Quỹ demo','Thu đơn DH-DEMO-018 (paid)','order',228,'DH-DEMO-018',5,1,'Demo Admin','demo-staff','CUST-2020','Phùng Thanh Mai','0912000020','3 Hùng Vương, Huế',NULL,NULL,'2025-11-23','Thanh toán đủ','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(193,'RECEIPT',541200.00,'sales','CASH','approved','Quỹ demo','Thu đơn DH-DEMO-021 (partial)','order',231,'DH-DEMO-021',2,1,'Demo Admin','demo-staff','CUST-2012','CTCP Gỗ Xanh','0912000012','45 Pasteur, Quận 1, HCM',NULL,NULL,'2025-11-26','Thanh toán đủ','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(194,'RECEIPT',1840000.00,'sales','COD','approved','Quỹ demo','Thu đơn DH-DEMO-022 (unpaid)','order',232,'DH-DEMO-022',3,1,'Demo Admin','demo-staff','CUST-2015','CTY Thiết Kế Mộc','0912000015','12 Nguyễn Trãi, Quận 5, HCM',NULL,NULL,'2025-11-27','Thanh toán đủ','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(195,'RECEIPT',2683800.00,'sales','EWALLET','approved','Quỹ demo','Thu đơn DH-DEMO-023 (unpaid)','order',233,'DH-DEMO-023',5,1,'Demo Admin','demo-staff','CUST-2009','Ngô Nhật Anh','0912000009','12 Nguyễn Văn Linh, Đà Nẵng',NULL,NULL,'2025-11-28','Thanh toán đủ','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(196,'RECEIPT',562000.00,'sales','COD','approved','Quỹ demo','Thu đơn DH-DEMO-025 (paid)','order',235,'DH-DEMO-025',2,1,'Demo Admin','demo-staff','CUST-2019','Đinh Mạnh Cường','0912000019','18 Lê Lợi, Vinh',NULL,NULL,'2025-11-30','Thanh toán đủ','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(197,'RECEIPT',1906800.00,'sales','CASH','approved','Quỹ demo','Thu đơn DH-DEMO-026 (unpaid)','order',236,'DH-DEMO-026',2,1,'Demo Admin','demo-staff','CUST-2020','Phùng Thanh Mai','0912000020','3 Hùng Vương, Huế',NULL,NULL,'2025-12-01','Thanh toán đủ','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(198,'RECEIPT',1866000.00,'sales','BANK_TRANSFER','approved','Quỹ demo','Thu đơn DH-DEMO-028 (partial)','order',238,'DH-DEMO-028',2,1,'Demo Admin','demo-staff','CUST-2018','La Mỹ Duyên','0912000018','155 Lạch Tray, Hải Phòng',NULL,NULL,'2025-12-03','Thanh toán đủ','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(199,'RECEIPT',256200.00,'sales','BANK_TRANSFER','approved','Quỹ demo','Thu đơn DH-DEMO-029 (unpaid)','order',239,'DH-DEMO-029',3,1,'Demo Admin','demo-staff','CUST-2007','Huỳnh Tuấn Kiệt','0912000007','45 Trần Phú, Nha Trang',NULL,NULL,'2025-12-04','Thanh toán đủ','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(200,'RECEIPT',1430000.00,'sales','BANK_TRANSFER','approved','Quỹ demo','Thu đơn DH-DEMO-030 (unpaid)','order',240,'DH-DEMO-030',4,1,'Demo Admin','demo-staff','CUST-2019','Đinh Mạnh Cường','0912000019','18 Lê Lợi, Vinh',NULL,NULL,'2025-12-05','Thanh toán đủ','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(201,'RECEIPT',2154000.00,'sales','CASH','approved','Quỹ demo','Thu đơn DH-DEMO-031 (paid)','order',241,'DH-DEMO-031',5,1,'Demo Admin','demo-staff','CUST-2011','Công ty Ánh Dương','0912000011','11 Duy Tân, Cầu Giấy, Hà Nội',NULL,NULL,'2025-12-06','Thanh toán đủ','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(202,'RECEIPT',991200.00,'sales','COD','approved','Quỹ demo','Thu đơn DH-DEMO-032 (paid)','order',242,'DH-DEMO-032',3,1,'Demo Admin','demo-staff','CUST-2011','Công ty Ánh Dương','0912000011','11 Duy Tân, Cầu Giấy, Hà Nội',NULL,NULL,'2025-12-07','Thanh toán đủ','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(203,'RECEIPT',1276000.00,'sales','CASH','approved','Quỹ demo','Thu đơn DH-DEMO-033 (paid)','order',243,'DH-DEMO-033',1,1,'Demo Admin','demo-staff','CUST-2012','CTCP Gỗ Xanh','0912000012','45 Pasteur, Quận 1, HCM',NULL,NULL,'2025-12-08','Thanh toán đủ','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(204,'RECEIPT',1596000.00,'sales','CASH','approved','Quỹ demo','Thu đơn DH-DEMO-034 (paid)','order',244,'DH-DEMO-034',3,1,'Demo Admin','demo-staff','CUST-2015','CTY Thiết Kế Mộc','0912000015','12 Nguyễn Trãi, Quận 5, HCM',NULL,NULL,'2025-12-09','Thanh toán đủ','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(205,'RECEIPT',2188200.00,'sales','BANK_TRANSFER','approved','Quỹ demo','Thu đơn DH-DEMO-035 (paid)','order',245,'DH-DEMO-035',2,1,'Demo Admin','demo-staff','CUST-2011','Công ty Ánh Dương','0912000011','11 Duy Tân, Cầu Giấy, Hà Nội',NULL,NULL,'2025-12-10','Thanh toán đủ','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(206,'PAYMENT',206000.00,'refund','CASH','approved','Quỹ demo','Hoàn tiền trả hàng RET-DEMO-001','return_order',36,'RET-DEMO-001',2,2,'Demo Manager','demo-staff','CUST-2012','CTCP Gỗ Xanh','0912000012','45 Pasteur, Quận 1, HCM',NULL,NULL,'2025-12-06','Hoàn tiền theo phiếu trả hàng','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(207,'PAYMENT',155400.00,'refund','BANK_TRANSFER','approved','Quỹ demo','Hoàn tiền trả hàng RET-DEMO-002','return_order',37,'RET-DEMO-002',3,2,'Demo Manager','demo-staff','CUST-2013','Hộ KD Minh Quân','0912000013','22 Trần Phú, Nha Trang',NULL,NULL,'2025-12-06','Hoàn tiền theo phiếu trả hàng','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(208,'PAYMENT',276000.00,'refund','CASH','approved','Quỹ demo','Hoàn tiền trả hàng RET-DEMO-025','return_order',40,'RET-DEMO-025',2,2,'Demo Manager','demo-staff','CUST-2019','Đinh Mạnh Cường','0912000019','18 Lê Lợi, Vinh',NULL,NULL,'2025-12-06','Hoàn tiền theo phiếu trả hàng','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(209,'PAYMENT',248000.00,'refund','CASH','approved','Quỹ demo','Hoàn tiền trả hàng RET-DEMO-031','return_order',41,'RET-DEMO-031',5,2,'Demo Manager','demo-staff','CUST-2011','Công ty Ánh Dương','0912000011','11 Duy Tân, Cầu Giấy, Hà Nội',NULL,NULL,'2025-12-06','Hoàn tiền theo phiếu trả hàng','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(210,'RECEIPT',226000.00,'sales','cash','approved','Tiền mặt','Thu tiền đơn #ORD-000036 - phần tiền mặt','order_payment',246,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-06',NULL,'2025-12-06 12:42:32','2025-12-06 12:42:32',NULL),(211,'RECEIPT',222000.00,'sales','cash','approved','Tiền mặt','Thu tiền đơn #ORD-000037 - phần tiền mặt','order_payment',247,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-06',NULL,'2025-12-06 12:50:53','2025-12-06 12:50:53',NULL),(212,'RECEIPT',222000.00,'sales','cash','approved','Tiền mặt','Thu tiền đơn #ORD-000038 - phần tiền mặt','order_payment',248,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-06',NULL,'2025-12-06 12:54:27','2025-12-06 12:54:27',NULL),(213,'RECEIPT',222000.00,'sales','cash','approved','Tiền mặt','Thu tiền đơn #ORD-000039 - phần tiền mặt','order_payment',249,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-06',NULL,'2025-12-06 13:19:08','2025-12-06 13:19:08',NULL),(214,'RECEIPT',222000.00,'sales','cash','approved','Tiền mặt','Thu tiền đơn #ORD-000040 - phần tiền mặt','order_payment',250,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-06',NULL,'2025-12-06 13:21:42','2025-12-06 13:21:42',NULL),(215,'RECEIPT',812000.00,'sales','cash','approved','Tiền mặt','Thu tiền đơn #ORD-000041 - phần tiền mặt','order_payment',251,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-07',NULL,'2025-12-07 11:45:48','2025-12-07 11:45:48',NULL),(216,'RECEIPT',812000.00,'sales','cash','approved','Tiền mặt','Thu tiền đơn #ORD-000042 - phần tiền mặt','order_payment',252,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-07',NULL,'2025-12-07 19:34:42','2025-12-07 19:34:42',NULL),(217,'RECEIPT',1624000.00,'sales','cash','approved','Tiền mặt','Thu tiền đơn #ORD-000043 - phần tiền mặt','order_payment',253,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-07',NULL,'2025-12-07 19:41:24','2025-12-07 19:41:24',NULL),(218,'RECEIPT',812000.00,'sales','cash','approved','Tiền mặt','Thu tiền đơn #ORD-000044 - phần tiền mặt','order_payment',254,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-07',NULL,'2025-12-07 19:41:37','2025-12-07 19:41:37',NULL),(219,'RECEIPT',812000.00,'sales','cash','approved','Tiền mặt','Thu tiền đơn #ORD-000045 - phần tiền mặt','order_payment',255,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-08',NULL,'2025-12-08 01:20:08','2025-12-08 01:20:08',NULL),(220,'RECEIPT',812000.00,'sales','cash','approved','Tiền mặt','Thu tiền đơn #ORD-000046 - phần tiền mặt','order_payment',256,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-08',NULL,'2025-12-08 08:27:39','2025-12-08 08:27:39',NULL),(222,'RECEIPT',812000.00,'sales','cash','approved','Tiền mặt','Thu tiền đơn #ORD-000047 - phần tiền mặt','order_payment',258,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-08',NULL,'2025-12-08 09:28:33','2025-12-08 09:28:33',NULL),(223,'RECEIPT',331000.00,'sales','cash','approved','Tiền mặt','Thu tiền đơn #ORD-000048 - phần tiền mặt','order_payment',259,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-08',NULL,'2025-12-08 09:38:07','2025-12-08 09:38:07',NULL),(224,'RECEIPT',1196000.00,'sales','cash','approved','Tiền mặt','Thu tiền đơn #ORD-000049 - phần tiền mặt','order_payment',260,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-08',NULL,'2025-12-08 09:51:09','2025-12-08 09:51:09',NULL),(225,'RECEIPT',1393000.00,'sales','cash','approved','Tiền mặt','Thu tiền đơn #ORD-000050 - phần tiền mặt','order_payment',261,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-08',NULL,'2025-12-08 10:00:21','2025-12-08 10:00:21',NULL),(226,'RECEIPT',1196000.00,'sales','cash','approved','Tiền mặt','Thu tiền đơn #ORD-000051 - phần tiền mặt','order_payment',262,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-08',NULL,'2025-12-08 10:15:12','2025-12-08 10:15:12',NULL);
/*!40000 ALTER TABLE `cash_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chart_of_accounts`
--

DROP TABLE IF EXISTS `chart_of_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chart_of_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `account_type` varchar(50) NOT NULL,
  `currency` varchar(10) DEFAULT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `is_group` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_coa_code` (`code`),
  KEY `idx_coa_parent` (`parent_id`),
  CONSTRAINT `fk_chart_of_accounts_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `chart_of_accounts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chart_of_accounts`
--

LOCK TABLES `chart_of_accounts` WRITE;
/*!40000 ALTER TABLE `chart_of_accounts` DISABLE KEYS */;
INSERT INTO `chart_of_accounts` VALUES (1,'111','Tiền mặt','asset',NULL,NULL,0,'2025-12-06 03:35:23','2025-12-06 03:35:23',NULL),(2,'112','Tiền gửi ngân hàng','asset',NULL,NULL,0,'2025-12-06 03:35:23','2025-12-06 03:35:23',NULL),(3,'131','Phải thu khách hàng','asset',NULL,NULL,0,'2025-12-06 03:35:23','2025-12-06 03:35:23',NULL),(4,'156','Hàng hóa','asset',NULL,NULL,0,'2025-12-06 03:35:23','2025-12-06 03:35:23',NULL),(5,'331','Phải trả người bán','liability',NULL,NULL,0,'2025-12-06 03:35:23','2025-12-06 03:35:23',NULL),(6,'511','Doanh thu bán hàng','revenue',NULL,NULL,0,'2025-12-06 03:35:23','2025-12-06 03:35:23',NULL),(7,'632','Giá vốn hàng bán','expense',NULL,NULL,0,'2025-12-06 03:35:23','2025-12-06 03:35:23',NULL),(8,'642','Chi phí quản lý','expense',NULL,NULL,0,'2025-12-06 03:35:23','2025-12-06 03:35:23',NULL);
/*!40000 ALTER TABLE `chart_of_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_default` tinyint(1) DEFAULT '0',
  `status` varchar(30) DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_company_code` (`code`),
  KEY `idx_company_status` (`status`,`is_default`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies`
--

LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
INSERT INTO `companies` VALUES (1,'COMP-DEFAULT','Default Company',1,'active','2025-12-06 03:04:12','2025-12-06 03:04:12');
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `company_permissions`
--

DROP TABLE IF EXISTS `company_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `role_name` varchar(100) DEFAULT NULL,
  `permissions` json DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_company_permission` (`company_id`,`user_id`,`role_name`),
  KEY `idx_company_permissions_company` (`company_id`),
  KEY `fk_company_permissions_user_id` (`user_id`),
  CONSTRAINT `fk_company_permissions_company_id` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_company_permissions_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company_permissions`
--

LOCK TABLES `company_permissions` WRITE;
/*!40000 ALTER TABLE `company_permissions` DISABLE KEYS */;
INSERT INTO `company_permissions` VALUES (2,1,1,'admin','[\"admin\", \"read\", \"write\", \"share\"]','2025-12-06 03:04:55','2025-12-06 03:04:55'),(6,1,10,'admin','[\"admin\", \"read\", \"write\", \"share\", \"delete\"]','2025-12-06 03:35:22','2025-12-06 03:35:22'),(7,1,11,'manager','[\"read\", \"write\", \"share\"]','2025-12-06 03:35:22','2025-12-06 03:35:22'),(8,1,12,'manager','[\"read\", \"write\", \"share\"]','2025-12-06 03:35:22','2025-12-06 03:35:22'),(9,1,13,'viewer','[\"read\"]','2025-12-06 03:35:22','2025-12-06 03:35:22'),(10,1,14,'viewer','[\"read\"]','2025-12-06 03:35:22','2025-12-06 03:35:22'),(11,1,15,'viewer','[\"read\"]','2025-12-06 03:35:22','2025-12-06 03:35:22');
/*!40000 ALTER TABLE `company_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contract_templates`
--

DROP TABLE IF EXISTS `contract_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contract_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `terms` text,
  `status` varchar(30) DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contract_templates`
--

LOCK TABLES `contract_templates` WRITE;
/*!40000 ALTER TABLE `contract_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `contract_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contract_terms`
--

DROP TABLE IF EXISTS `contract_terms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contract_terms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `contract_id` bigint unsigned NOT NULL,
  `description` text,
  `is_completed` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_contract_terms` (`contract_id`),
  CONSTRAINT `fk_contract_terms_contract_id` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contract_terms`
--

LOCK TABLES `contract_terms` WRITE;
/*!40000 ALTER TABLE `contract_terms` DISABLE KEYS */;
/*!40000 ALTER TABLE `contract_terms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contracts`
--

DROP TABLE IF EXISTS `contracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contracts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned DEFAULT NULL,
  `template_id` bigint unsigned DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `value` decimal(14,2) DEFAULT '0.00',
  `status` varchar(30) DEFAULT 'draft',
  `auto_renew` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_contracts_customer_id` (`customer_id`),
  KEY `fk_contracts_template_id` (`template_id`),
  CONSTRAINT `fk_contracts_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_contracts_template_id` FOREIGN KEY (`template_id`) REFERENCES `contract_templates` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contracts`
--

LOCK TABLES `contracts` WRITE;
/*!40000 ALTER TABLE `contracts` DISABLE KEYS */;
/*!40000 ALTER TABLE `contracts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupon_usages`
--

DROP TABLE IF EXISTS `coupon_usages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupon_usages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `coupon_id` bigint unsigned NOT NULL,
  `order_id` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_coupon_usage_coupon` (`coupon_id`),
  KEY `idx_coupon_usage_order` (`order_id`),
  KEY `fk_coupon_usages_customer_id` (`customer_id`),
  CONSTRAINT `fk_coupon_usages_coupon_id` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_coupon_usages_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_coupon_usages_order_id` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupon_usages`
--

LOCK TABLES `coupon_usages` WRITE;
/*!40000 ALTER TABLE `coupon_usages` DISABLE KEYS */;
/*!40000 ALTER TABLE `coupon_usages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupons`
--

DROP TABLE IF EXISTS `coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(100) NOT NULL,
  `discount_type` varchar(20) DEFAULT 'percent',
  `discount_value` decimal(14,2) NOT NULL DEFAULT '0.00',
  `min_amount` decimal(14,2) DEFAULT '0.00',
  `expiry_date` date DEFAULT NULL,
  `usage_limit` int DEFAULT '0',
  `used_count` int DEFAULT '0',
  `status` varchar(20) DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupons`
--

LOCK TABLES `coupons` WRITE;
/*!40000 ALTER TABLE `coupons` DISABLE KEYS */;
INSERT INTO `coupons` VALUES (1,'WELCOME10','percent',10.00,100000.00,'2026-01-06',100,0,'active','2025-12-06 10:35:15','2025-12-06 10:35:15'),(2,'SALE50K','fixed',50000.00,500000.00,'2026-01-06',50,0,'active','2025-12-06 10:35:15','2025-12-06 10:35:15'),(3,'VIP20','percent',20.00,1000000.00,'2026-01-06',20,0,'active','2025-12-06 10:35:15','2025-12-06 10:35:15'),(4,'FREESHIP','fixed',30000.00,200000.00,'2026-01-06',0,0,'active','2025-12-06 10:35:15','2025-12-06 10:35:15');
/*!40000 ALTER TABLE `coupons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `credit_limits`
--

DROP TABLE IF EXISTS `credit_limits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_limits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `limit_amount` decimal(14,2) DEFAULT '0.00',
  `on_hold` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_credit_limit_customer` (`customer_id`),
  CONSTRAINT `fk_credit_limits_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credit_limits`
--

LOCK TABLES `credit_limits` WRITE;
/*!40000 ALTER TABLE `credit_limits` DISABLE KEYS */;
/*!40000 ALTER TABLE `credit_limits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_groups`
--

DROP TABLE IF EXISTS `customer_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_groups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_vi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_customer_group_code` (`code`),
  KEY `customer_groups_parent_id_foreign` (`parent_id`),
  KEY `customer_groups_branch_id_foreign` (`branch_id`),
  CONSTRAINT `customer_groups_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE SET NULL,
  CONSTRAINT `customer_groups_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `customer_groups` (`id`) ON DELETE CASCADE ON UPDATE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_groups`
--

LOCK TABLES `customer_groups` WRITE;
/*!40000 ALTER TABLE `customer_groups` DISABLE KEYS */;
INSERT INTO `customer_groups` VALUES (1,'CG-STD','Khách chuẩn',NULL,NULL,NULL,1,'active','2025-12-06 03:35:22','2025-12-06 03:35:22',NULL),(2,'CG-VIP','Khách VIP',NULL,NULL,NULL,0,'active','2025-12-06 03:35:22','2025-12-06 03:35:22',NULL),(3,'CG-WHS','Khách sỉ',NULL,NULL,NULL,0,'active','2025-12-06 03:35:22','2025-12-06 03:35:22',NULL);
/*!40000 ALTER TABLE `customer_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_price_lists`
--

DROP TABLE IF EXISTS `customer_price_lists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_price_lists` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `price_list_id` bigint unsigned NOT NULL,
  `valid_from` date DEFAULT NULL,
  `valid_to` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cpl_customer` (`customer_id`,`price_list_id`),
  KEY `fk_customer_price_lists_price_list_id` (`price_list_id`),
  CONSTRAINT `fk_customer_price_lists_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_customer_price_lists_price_list_id` FOREIGN KEY (`price_list_id`) REFERENCES `price_lists` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_price_lists`
--

LOCK TABLES `customer_price_lists` WRITE;
/*!40000 ALTER TABLE `customer_price_lists` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_price_lists` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint unsigned DEFAULT NULL,
  `customer_group_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `phone2` varchar(50) DEFAULT NULL,
  `gender` enum('MALE','FEMALE','OTHER') DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `customer_type` enum('INDIVIDUAL','COMPANY','HOUSEHOLD') NOT NULL DEFAULT 'INDIVIDUAL',
  `company_name` varchar(255) DEFAULT NULL,
  `tax_code` varchar(20) DEFAULT NULL,
  `buyer_name` varchar(255) DEFAULT NULL,
  `invoice_company_name` varchar(255) DEFAULT NULL,
  `invoice_address` varchar(500) DEFAULT NULL,
  `invoice_province` varchar(120) DEFAULT NULL,
  `invoice_district` varchar(120) DEFAULT NULL,
  `invoice_ward` varchar(120) DEFAULT NULL,
  `invoice_email` varchar(255) DEFAULT NULL,
  `invoice_phone` varchar(50) DEFAULT NULL,
  `cccd_cmnd` varchar(50) DEFAULT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `bank_account` varchar(50) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `notes` text,
  `code` varchar(50) DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `province` varchar(120) DEFAULT NULL,
  `district` varchar(120) DEFAULT NULL,
  `ward` varchar(120) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `last_transaction_at` datetime DEFAULT NULL,
  `current_debt` decimal(15,2) DEFAULT '0.00',
  `total_sales` decimal(15,2) DEFAULT '0.00',
  `total_sales_net` decimal(15,2) DEFAULT '0.00',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tax_code_per_org` (`organization_id`,`tax_code`),
  KEY `idx_customers_tax_code` (`tax_code`),
  KEY `fk_customers_customer_group` (`customer_group_id`),
  CONSTRAINT `fk_customers_customer_group` FOREIGN KEY (`customer_group_id`) REFERENCES `customer_groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_customers_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (1,NULL,NULL,'Nguyễn Văn An','an.nguyen@example.com','0901234567',NULL,NULL,NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'KH0001','Địa chỉ số 1, Hà Nội','Hà Nội',NULL,NULL,NULL,NULL,'active',NULL,0.00,0.00,0.00,'2025-12-07 10:47:20','2025-12-07 10:47:20',NULL),(2,NULL,NULL,'Trần Thị Bình','binh.tran@example.com','0902345678',NULL,NULL,NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'KH0002','Địa chỉ số 2, TP.HCM','TP.HCM',NULL,NULL,NULL,NULL,'active',NULL,0.00,0.00,0.00,'2025-12-07 10:47:20','2025-12-07 10:47:20',NULL),(3,NULL,NULL,'Lê Hoàng Cường','cuong.le@example.com','0903456789',NULL,NULL,NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'KH0003','Địa chỉ số 3, Đà Nẵng','Đà Nẵng',NULL,NULL,NULL,NULL,'active',NULL,0.00,0.00,0.00,'2025-12-07 10:47:20','2025-12-07 10:47:20',NULL),(4,NULL,NULL,'Phạm Minh Dung','dung.pham@example.com','0904567890',NULL,NULL,NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'KH0004','Địa chỉ số 4, Hải Phòng','Hải Phòng',NULL,NULL,NULL,NULL,'active',NULL,0.00,0.00,0.00,'2025-12-07 10:47:20','2025-12-07 10:47:20',NULL),(5,NULL,NULL,'Hoàng Văn Em','em.hoang@example.com','0905678901',NULL,NULL,NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'KH0005','Địa chỉ số 5, Cần Thơ','Cần Thơ',NULL,NULL,NULL,NULL,'active',NULL,0.00,0.00,0.00,'2025-12-07 10:47:20','2025-12-07 10:47:20',NULL),(6,NULL,NULL,'Vũ Thị Phương','phuong.vu@example.com','0906789012',NULL,NULL,NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'KH0006','Địa chỉ số 6, Hà Nội','Hà Nội',NULL,NULL,NULL,NULL,'active',NULL,0.00,0.00,0.00,'2025-12-07 10:47:20','2025-12-07 10:47:20',NULL),(7,NULL,NULL,'Đặng Văn Giàu','giau.dang@example.com','0907890123',NULL,NULL,NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'KH0007','Địa chỉ số 7, TP.HCM','TP.HCM',NULL,NULL,NULL,NULL,'active',NULL,0.00,0.00,0.00,'2025-12-07 10:47:20','2025-12-07 10:47:20',NULL),(8,NULL,NULL,'Bùi Thị Hạnh','hanh.bui@example.com','0908901234',NULL,NULL,NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'KH0008','Địa chỉ số 8, Đà Nẵng','Đà Nẵng',NULL,NULL,NULL,NULL,'active',NULL,0.00,0.00,0.00,'2025-12-07 10:47:20','2025-12-07 10:47:20',NULL),(9,NULL,NULL,'Đỗ Văn Hùng','hung.do@example.com','0909012345',NULL,NULL,NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'KH0009','Địa chỉ số 9, Hải Phòng','Hải Phòng',NULL,NULL,NULL,NULL,'active',NULL,0.00,0.00,0.00,'2025-12-07 10:47:20','2025-12-07 10:47:20',NULL),(10,NULL,NULL,'Ngô Thị Lan','lan.ngo@example.com','0910123456',NULL,NULL,NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'KH0010','Địa chỉ số 10, Hà Nội','Hà Nội',NULL,NULL,NULL,NULL,'active',NULL,0.00,0.00,0.00,'2025-12-07 10:47:20','2025-12-07 10:47:20',NULL),(11,1,NULL,'anh an','shinebingx@gmail.com','09666012385',NULL,'MALE',NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'gf dg',NULL,NULL,NULL,'2025-12-18',NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-08 08:23:49','2025-12-08 08:23:49',NULL),(12,1,NULL,'anh an','shinebingx@gmail.com','09666012385',NULL,'MALE',NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'gf dg',NULL,NULL,NULL,'2025-12-18',NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-08 08:27:25','2025-12-08 08:27:25',NULL);
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `delivery_note_items`
--

DROP TABLE IF EXISTS `delivery_note_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delivery_note_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `delivery_note_id` bigint unsigned NOT NULL,
  `order_item_id` bigint unsigned DEFAULT NULL,
  `product_id` bigint unsigned NOT NULL,
  `variant_id` bigint unsigned DEFAULT NULL,
  `batch_id` bigint unsigned DEFAULT NULL,
  `serial_number` varchar(160) DEFAULT NULL,
  `quantity` decimal(12,3) DEFAULT '0.000',
  `delivered_quantity` decimal(12,3) DEFAULT '0.000',
  `notes` text,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_delivery_note_items_order_item` (`order_item_id`),
  KEY `idx_dn_items_note` (`delivery_note_id`),
  KEY `idx_dn_items_product` (`product_id`,`variant_id`),
  KEY `fk_delivery_note_items_variant_id` (`variant_id`),
  KEY `fk_delivery_note_items_batch_id` (`batch_id`),
  CONSTRAINT `fk_delivery_note_items_batch_id` FOREIGN KEY (`batch_id`) REFERENCES `product_batches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_delivery_note_items_note` FOREIGN KEY (`delivery_note_id`) REFERENCES `delivery_notes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_delivery_note_items_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_delivery_note_items_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_delivery_note_items_variant_id` FOREIGN KEY (`variant_id`) REFERENCES `product_variants_v2` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=413 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_note_items`
--

LOCK TABLES `delivery_note_items` WRITE;
/*!40000 ALTER TABLE `delivery_note_items` DISABLE KEYS */;
INSERT INTO `delivery_note_items` VALUES (354,188,384,501,50101,NULL,NULL,1.000,0.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(355,188,385,502,NULL,NULL,NULL,1.000,0.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(356,189,386,503,NULL,NULL,NULL,2.000,0.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(357,190,387,501,50101,NULL,NULL,1.000,0.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(358,190,388,503,NULL,NULL,NULL,1.000,0.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(359,191,389,502,NULL,NULL,NULL,2.000,0.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(360,192,390,501,50102,NULL,NULL,1.000,0.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(361,192,391,502,NULL,NULL,NULL,1.000,0.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(362,193,392,501,50101,NULL,NULL,1.000,0.500,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(363,193,393,503,NULL,NULL,NULL,1.000,0.500,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(364,194,394,502,NULL,NULL,NULL,1.000,1.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(365,194,395,503,NULL,NULL,NULL,1.000,1.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(366,195,396,501,50101,NULL,NULL,1.000,0.500,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(367,195,397,502,NULL,NULL,NULL,1.000,0.500,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(368,196,398,501,50101,NULL,NULL,1.000,1.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(369,196,399,502,NULL,NULL,NULL,1.000,1.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(370,197,400,503,NULL,NULL,NULL,2.000,2.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(371,197,401,501,50101,NULL,NULL,1.000,1.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(372,198,402,502,NULL,NULL,NULL,1.000,1.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(373,198,403,501,50102,NULL,NULL,1.000,1.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(374,199,404,503,NULL,NULL,NULL,1.000,1.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(375,199,405,501,50101,NULL,NULL,1.000,1.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(376,200,406,502,NULL,NULL,NULL,1.000,1.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(377,200,407,503,NULL,NULL,NULL,1.000,1.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(378,200,408,501,50101,NULL,NULL,1.000,1.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(379,201,409,502,NULL,NULL,NULL,2.000,2.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(380,202,410,501,50101,NULL,NULL,1.000,1.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(381,202,411,503,NULL,NULL,NULL,1.000,1.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(382,203,412,502,NULL,NULL,NULL,1.000,1.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(383,203,413,501,50101,NULL,NULL,1.000,1.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(384,204,414,503,NULL,NULL,NULL,2.000,2.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(385,205,415,501,50101,NULL,NULL,1.000,1.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(386,205,416,502,NULL,NULL,NULL,1.000,1.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(387,206,419,513,NULL,NULL,NULL,2.000,2.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(388,207,420,512,NULL,NULL,NULL,3.000,0.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(389,207,421,519,NULL,NULL,NULL,2.000,0.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(390,207,422,512,NULL,NULL,NULL,3.000,0.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(391,208,423,512,51202,NULL,NULL,4.000,0.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(392,208,424,508,50802,NULL,NULL,3.000,0.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(393,208,425,504,NULL,NULL,NULL,4.000,0.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(394,209,429,528,52802,NULL,NULL,2.000,2.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(395,210,430,504,NULL,NULL,NULL,4.000,0.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(396,210,431,508,50802,NULL,NULL,4.000,0.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(397,211,435,527,NULL,NULL,NULL,2.000,1.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(398,211,436,503,50301,NULL,NULL,4.000,2.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(399,211,437,511,51101,NULL,NULL,2.000,1.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(400,212,438,512,51201,NULL,NULL,1.000,0.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(401,213,439,523,52301,NULL,NULL,5.000,0.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(402,214,440,504,50402,NULL,NULL,2.000,2.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(403,214,441,507,50701,NULL,NULL,2.000,2.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(404,214,442,519,51901,NULL,NULL,5.000,5.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(405,215,443,514,NULL,NULL,NULL,3.000,3.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(406,215,444,510,NULL,NULL,NULL,1.000,1.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(407,216,445,509,50901,NULL,NULL,5.000,5.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(408,217,446,504,NULL,NULL,NULL,2.000,2.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(409,217,447,518,NULL,NULL,NULL,5.000,5.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(410,218,448,530,53001,NULL,NULL,3.000,3.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(411,218,449,515,51502,NULL,NULL,4.000,4.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(412,218,450,502,50202,NULL,NULL,1.000,1.000,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL);
/*!40000 ALTER TABLE `delivery_note_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `delivery_notes`
--

DROP TABLE IF EXISTS `delivery_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delivery_notes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `delivery_number` varchar(50) NOT NULL,
  `order_id` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `status` varchar(30) DEFAULT 'draft',
  `shipping_address` text,
  `tracking_number` varchar(120) DEFAULT NULL,
  `carrier` varchar(120) DEFAULT NULL,
  `notes` text,
  `confirmed_by` bigint unsigned DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `delivered_by` bigint unsigned DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_delivery_number` (`delivery_number`),
  KEY `fk_delivery_notes_customer` (`customer_id`),
  KEY `fk_delivery_notes_branch` (`branch_id`),
  KEY `idx_delivery_order_branch` (`order_id`,`branch_id`),
  CONSTRAINT `fk_delivery_notes_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_delivery_notes_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_delivery_notes_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=219 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_notes`
--

LOCK TABLES `delivery_notes` WRITE;
/*!40000 ALTER TABLE `delivery_notes` DISABLE KEYS */;
INSERT INTO `delivery_notes` VALUES (188,'DN-DEMO-001',211,2001,1,'2025-11-07','2025-11-07','draft','12 Trần Hưng Đạo, Hà Nội','TRK-001','Demo Carrier','Demo delivery note from DH-DEMO-001',NULL,NULL,NULL,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(189,'DN-DEMO-002',212,2002,2,'2025-11-08','2025-11-08','draft','89 Lý Thường Kiệt, Hà Nội','TRK-002','Demo Carrier','Demo delivery note from DH-DEMO-002',NULL,NULL,NULL,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(190,'DN-DEMO-003',213,2003,3,'2025-11-09','2025-11-09','confirmed','22 Nguyễn Huệ, HCM','TRK-003','Demo Carrier','Demo delivery note from DH-DEMO-003',NULL,NULL,NULL,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(191,'DN-DEMO-004',214,2004,4,'2025-11-10','2025-11-10','confirmed','35 Hai Bà Trưng, HCM','TRK-004','Demo Carrier','Demo delivery note from DH-DEMO-004',NULL,NULL,NULL,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(192,'DN-DEMO-005',215,2005,5,'2025-11-11','2025-11-11','confirmed','15 Nguyễn Tri Phương, Đà Nẵng','TRK-005','Demo Carrier','Demo delivery note from DH-DEMO-005',NULL,NULL,NULL,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(193,'DN-DEMO-006',216,2006,1,'2025-11-12','2025-11-12','shipped','101 Võ Văn Tần, HCM','TRK-006','Demo Carrier','Demo delivery note from DH-DEMO-006',NULL,NULL,NULL,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(194,'DN-DEMO-007',217,2007,2,'2025-11-13','2025-11-13','delivered','45 Trần Phú, Nha Trang','TRK-007','Demo Carrier','Demo delivery note from DH-DEMO-007',NULL,NULL,NULL,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(195,'DN-DEMO-008',218,2008,3,'2025-11-14','2025-11-14','shipped','68 Lê Lợi, Huế','TRK-008','Demo Carrier','Demo delivery note from DH-DEMO-008',NULL,NULL,NULL,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(196,'DN-DEMO-009',219,2011,1,'2025-11-15','2025-11-15','delivered','11 Duy Tân, Cầu Giấy, Hà Nội','TRK-009','Demo Carrier','Demo delivery note from DH-DEMO-009',NULL,NULL,NULL,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(197,'DN-DEMO-010',220,2012,2,'2025-11-16','2025-11-16','delivered','45 Pasteur, Quận 1, HCM','TRK-010','Demo Carrier','Demo delivery note from DH-DEMO-010',NULL,NULL,NULL,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(198,'DN-DEMO-011',221,2013,3,'2025-11-17','2025-11-17','delivered','22 Trần Phú, Nha Trang','TRK-011','Demo Carrier','Demo delivery note from DH-DEMO-011',NULL,NULL,NULL,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(199,'DN-DEMO-012',222,2014,4,'2025-11-18','2025-11-18','delivered','88 Kim Mã, Ba Đình, Hà Nội','TRK-012','Demo Carrier','Demo delivery note from DH-DEMO-012',NULL,NULL,NULL,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(200,'DN-DEMO-013',223,2015,5,'2025-11-19','2025-11-19','delivered','12 Nguyễn Trãi, Quận 5, HCM','TRK-013','Demo Carrier','Demo delivery note from DH-DEMO-013',NULL,NULL,NULL,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(201,'DN-DEMO-014',224,2016,1,'2025-11-20','2025-11-20','delivered','14 Lê Duẩn, Hà Nội','TRK-014','Demo Carrier','Demo delivery note from DH-DEMO-014',NULL,NULL,NULL,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(202,'DN-DEMO-015',225,2017,2,'2025-11-21','2025-11-21','delivered','7 Nguyễn Văn Cừ, Hạ Long','TRK-015','Demo Carrier','Demo delivery note from DH-DEMO-015',NULL,NULL,NULL,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(203,'DN-DEMO-016',226,2018,3,'2025-11-22','2025-11-22','delivered','155 Lạch Tray, Hải Phòng','TRK-016','Demo Carrier','Demo delivery note from DH-DEMO-016',NULL,NULL,NULL,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(204,'DN-DEMO-017',227,2019,4,'2025-11-23','2025-11-23','delivered','18 Lê Lợi, Vinh','TRK-017','Demo Carrier','Demo delivery note from DH-DEMO-017',NULL,NULL,NULL,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(205,'DN-DEMO-018',228,2020,5,'2025-11-24','2025-11-24','delivered','3 Hùng Vương, Huế','TRK-018','Demo Carrier','Demo delivery note from DH-DEMO-018',NULL,NULL,NULL,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(206,'DN-DEMO-019',231,2012,2,'2025-11-27','2025-11-27','delivered','45 Pasteur, Quận 1, HCM','TRK-019','Demo Carrier','Demo delivery note from DH-DEMO-021',NULL,NULL,NULL,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(207,'DN-DEMO-020',232,2015,3,'2025-11-28','2025-11-28','draft','12 Nguyễn Trãi, Quận 5, HCM','TRK-020','Demo Carrier','Demo delivery note from DH-DEMO-022',NULL,NULL,NULL,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(208,'DN-DEMO-021',233,2009,5,'2025-11-29','2025-11-29','draft','12 Nguyễn Văn Linh, Đà Nẵng','TRK-021','Demo Carrier','Demo delivery note from DH-DEMO-023',NULL,NULL,NULL,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(209,'DN-DEMO-022',235,2019,2,'2025-12-01','2025-12-01','delivered','18 Lê Lợi, Vinh','TRK-022','Demo Carrier','Demo delivery note from DH-DEMO-025',NULL,NULL,NULL,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(210,'DN-DEMO-023',236,2020,2,'2025-12-02','2025-12-02','draft','3 Hùng Vương, Huế','TRK-023','Demo Carrier','Demo delivery note from DH-DEMO-026',NULL,NULL,NULL,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(211,'DN-DEMO-024',238,2018,2,'2025-12-04','2025-12-04','shipped','155 Lạch Tray, Hải Phòng','TRK-024','Demo Carrier','Demo delivery note from DH-DEMO-028',NULL,NULL,NULL,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(212,'DN-DEMO-025',239,2007,3,'2025-12-05','2025-12-05','draft','45 Trần Phú, Nha Trang','TRK-025','Demo Carrier','Demo delivery note from DH-DEMO-029',NULL,NULL,NULL,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(213,'DN-DEMO-026',240,2019,4,'2025-12-06','2025-12-06','draft','18 Lê Lợi, Vinh','TRK-026','Demo Carrier','Demo delivery note from DH-DEMO-030',NULL,NULL,NULL,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(214,'DN-DEMO-027',241,2011,5,'2025-12-07','2025-12-07','delivered','11 Duy Tân, Cầu Giấy, Hà Nội','TRK-027','Demo Carrier','Demo delivery note from DH-DEMO-031',NULL,NULL,NULL,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(215,'DN-DEMO-028',242,2011,3,'2025-12-08','2025-12-08','delivered','11 Duy Tân, Cầu Giấy, Hà Nội','TRK-028','Demo Carrier','Demo delivery note from DH-DEMO-032',NULL,NULL,NULL,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(216,'DN-DEMO-029',243,2012,1,'2025-12-09','2025-12-09','delivered','45 Pasteur, Quận 1, HCM','TRK-029','Demo Carrier','Demo delivery note from DH-DEMO-033',NULL,NULL,NULL,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(217,'DN-DEMO-030',244,2015,3,'2025-12-10','2025-12-10','delivered','12 Nguyễn Trãi, Quận 5, HCM','TRK-030','Demo Carrier','Demo delivery note from DH-DEMO-034',NULL,NULL,NULL,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(218,'DN-DEMO-031',245,2011,2,'2025-12-11','2025-12-11','delivered','11 Duy Tân, Cầu Giấy, Hà Nội','TRK-031','Demo Carrier','Demo delivery note from DH-DEMO-035',NULL,NULL,NULL,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL);
/*!40000 ALTER TABLE `delivery_notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'Ban Giám Đốc','BGD','Board of Directors','active','2025-12-06 03:43:05','2025-12-06 03:43:05'),(2,'Phòng Kinh Doanh','KD','Sales Department','active','2025-12-06 03:43:05','2025-12-06 03:43:05'),(3,'Phòng Kế Toán','KT','Accounting Department','active','2025-12-06 03:43:05','2025-12-06 03:43:05'),(4,'Phòng Nhân Sự','NS','HR Department','active','2025-12-06 03:43:05','2025-12-06 03:43:05'),(5,'Kho Vận','KV','Logistics Department','active','2025-12-06 03:43:05','2025-12-06 03:43:05');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `depreciation_schedule_lines`
--

DROP TABLE IF EXISTS `depreciation_schedule_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `depreciation_schedule_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `schedule_id` bigint unsigned NOT NULL,
  `period_no` int NOT NULL,
  `posting_date` date NOT NULL,
  `amount` decimal(14,2) DEFAULT '0.00',
  `posted_gl_entry_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dep_line` (`schedule_id`,`period_no`),
  KEY `idx_dep_line_schedule` (`schedule_id`),
  KEY `fk_depreciation_schedul_posted_gl_entry_id` (`posted_gl_entry_id`),
  CONSTRAINT `fk_depreciation_schedul_posted_gl_entry_id` FOREIGN KEY (`posted_gl_entry_id`) REFERENCES `gl_entries` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_depreciation_schedul_schedule_id` FOREIGN KEY (`schedule_id`) REFERENCES `depreciation_schedules` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `depreciation_schedule_lines`
--

LOCK TABLES `depreciation_schedule_lines` WRITE;
/*!40000 ALTER TABLE `depreciation_schedule_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `depreciation_schedule_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `depreciation_schedules`
--

DROP TABLE IF EXISTS `depreciation_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `depreciation_schedules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` bigint unsigned NOT NULL,
  `method` varchar(50) DEFAULT 'straight_line',
  `rate` decimal(6,3) DEFAULT '0.000',
  `start_date` date DEFAULT NULL,
  `total_periods` int DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_dep_asset` (`asset_id`),
  CONSTRAINT `fk_depreciation_schedul_asset_id` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `depreciation_schedules`
--

LOCK TABLES `depreciation_schedules` WRITE;
/*!40000 ALTER TABLE `depreciation_schedules` DISABLE KEYS */;
/*!40000 ALTER TABLE `depreciation_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `devices`
--

DROP TABLE IF EXISTS `devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `devices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pos',
  `branch_id` bigint unsigned DEFAULT NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `meta` json DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_device_code` (`code`),
  KEY `devices_branch_id_foreign` (`branch_id`),
  CONSTRAINT `devices_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `devices`
--

LOCK TABLES `devices` WRITE;
/*!40000 ALTER TABLE `devices` DISABLE KEYS */;
INSERT INTO `devices` VALUES (1,'DEV-POS-01','POS Hà Nội','pos',1,'active',NULL,'2025-12-06 03:35:22','2025-12-06 03:35:22',NULL),(2,'DEV-POS-02','POS HCM','pos',2,'active',NULL,'2025-12-06 03:35:22','2025-12-06 03:35:22',NULL);
/*!40000 ALTER TABLE `devices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `districts`
--

DROP TABLE IF EXISTS `districts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `districts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `province_id` bigint unsigned NOT NULL,
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name_en` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `full_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `full_name_en` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `code_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_district_code` (`code`),
  KEY `idx_district_province` (`province_id`),
  KEY `idx_district_active` (`is_active`),
  CONSTRAINT `fk_district_province` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `districts`
--

LOCK TABLES `districts` WRITE;
/*!40000 ALTER TABLE `districts` DISABLE KEYS */;
INSERT INTO `districts` VALUES (1,1,'001','Ba Đình',NULL,'Quận Ba Đình',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(2,1,'002','Hoàn Kiếm',NULL,'Quận Hoàn Kiếm',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(3,1,'003','Tây Hồ',NULL,'Quận Tây Hồ',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(4,1,'004','Long Biên',NULL,'Quận Long Biên',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(5,1,'005','Cầu Giấy',NULL,'Quận Cầu Giấy',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(6,1,'006','Đống Đa',NULL,'Quận Đống Đa',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(7,1,'007','Hai Bà Trưng',NULL,'Quận Hai Bà Trưng',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(8,1,'008','Hoàng Mai',NULL,'Quận Hoàng Mai',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(9,1,'009','Thanh Xuân',NULL,'Quận Thanh Xuân',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(10,1,'016','Sóc Sơn',NULL,'Huyện Sóc Sơn',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(11,1,'017','Đông Anh',NULL,'Huyện Đông Anh',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(12,1,'018','Gia Lâm',NULL,'Huyện Gia Lâm',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(13,1,'019','Nam Từ Liêm',NULL,'Quận Nam Từ Liêm',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(14,1,'020','Thanh Trì',NULL,'Huyện Thanh Trì',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(15,1,'021','Bắc Từ Liêm',NULL,'Quận Bắc Từ Liêm',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(16,1,'250','Mê Linh',NULL,'Huyện Mê Linh',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(17,1,'268','Hà Đông',NULL,'Quận Hà Đông',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(18,1,'269','Sơn Tây',NULL,'Thị xã Sơn Tây',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(19,1,'271','Ba Vì',NULL,'Huyện Ba Vì',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(20,1,'272','Phúc Thọ',NULL,'Huyện Phúc Thọ',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(21,1,'273','Đan Phượng',NULL,'Huyện Đan Phượng',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(22,1,'274','Hoài Đức',NULL,'Huyện Hoài Đức',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(23,1,'275','Quốc Oai',NULL,'Huyện Quốc Oai',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(24,1,'276','Thạch Thất',NULL,'Huyện Thạch Thất',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(25,1,'277','Chương Mỹ',NULL,'Huyện Chương Mỹ',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(26,1,'278','Thanh Oai',NULL,'Huyện Thanh Oai',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(27,1,'279','Thường Tín',NULL,'Huyện Thường Tín',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(28,1,'280','Phú Xuyên',NULL,'Huyện Phú Xuyên',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(29,1,'281','Ứng Hòa',NULL,'Huyện Ứng Hòa',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(30,1,'282','Mỹ Đức',NULL,'Huyện Mỹ Đức',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(31,2,'760','Quận 1',NULL,'Quận 1',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(32,2,'761','Quận 12',NULL,'Quận 12',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(33,2,'764','Gò Vấp',NULL,'Quận Gò Vấp',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(34,2,'765','Bình Thạnh',NULL,'Quận Bình Thạnh',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(35,2,'766','Tân Bình',NULL,'Quận Tân Bình',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(36,2,'767','Tân Phú',NULL,'Quận Tân Phú',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(37,2,'768','Phú Nhuận',NULL,'Quận Phú Nhuận',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(38,2,'769','Thủ Đức',NULL,'Thành phố Thủ Đức',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(39,2,'770','Quận 3',NULL,'Quận 3',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(40,2,'771','Quận 10',NULL,'Quận 10',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(41,2,'772','Quận 11',NULL,'Quận 11',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(42,2,'773','Quận 4',NULL,'Quận 4',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(43,2,'774','Quận 5',NULL,'Quận 5',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(44,2,'775','Quận 6',NULL,'Quận 6',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(45,2,'776','Quận 8',NULL,'Quận 8',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(46,2,'777','Bình Tân',NULL,'Quận Bình Tân',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(47,2,'778','Quận 7',NULL,'Quận 7',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(48,2,'783','Củ Chi',NULL,'Huyện Củ Chi',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(49,2,'784','Hóc Môn',NULL,'Huyện Hóc Môn',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(50,2,'785','Bình Chánh',NULL,'Huyện Bình Chánh',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(51,2,'786','Nhà Bè',NULL,'Huyện Nhà Bè',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(52,2,'787','Cần Giờ',NULL,'Huyện Cần Giờ',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(53,3,'490','Liên Chiểu',NULL,'Quận Liên Chiểu',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(54,3,'491','Thanh Khê',NULL,'Quận Thanh Khê',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(55,3,'492','Hải Châu',NULL,'Quận Hải Châu',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(56,3,'493','Sơn Trà',NULL,'Quận Sơn Trà',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(57,3,'494','Ngũ Hành Sơn',NULL,'Quận Ngũ Hành Sơn',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(58,3,'495','Cẩm Lệ',NULL,'Quận Cẩm Lệ',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(59,3,'497','Hòa Vang',NULL,'Huyện Hòa Vang',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(60,3,'498','Hoàng Sa',NULL,'Huyện Hoàng Sa',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10');
/*!40000 ALTER TABLE `districts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_shares`
--

DROP TABLE IF EXISTS `document_shares`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_shares` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `entity_type` varchar(120) NOT NULL,
  `entity_id` bigint unsigned NOT NULL,
  `shared_with_user_id` bigint unsigned DEFAULT NULL,
  `shared_with_role` varchar(100) DEFAULT NULL,
  `permissions` json DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_doc_share_user` (`company_id`,`entity_type`,`entity_id`,`shared_with_user_id`,`shared_with_role`),
  KEY `idx_document_shares_entity` (`entity_type`,`entity_id`),
  KEY `idx_document_shares_company` (`company_id`),
  KEY `fk_document_shares_shared_with_user_id` (`shared_with_user_id`),
  CONSTRAINT `fk_document_shares_company_id` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_document_shares_shared_with_user_id` FOREIGN KEY (`shared_with_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_shares`
--

LOCK TABLES `document_shares` WRITE;
/*!40000 ALTER TABLE `document_shares` DISABLE KEYS */;
/*!40000 ALTER TABLE `document_shares` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `e_invoice_logs`
--

DROP TABLE IF EXISTS `e_invoice_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `e_invoice_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint unsigned DEFAULT NULL,
  `status` varchar(30) DEFAULT 'queued',
  `payload` json DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_einvoice_invoice` (`invoice_id`),
  CONSTRAINT `fk_e_invoice_logs_invoice_id` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `e_invoice_logs`
--

LOCK TABLES `e_invoice_logs` WRITE;
/*!40000 ALTER TABLE `e_invoice_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `e_invoice_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ecommerce_webhook_logs`
--

DROP TABLE IF EXISTS `ecommerce_webhook_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_webhook_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `source` varchar(50) DEFAULT NULL,
  `event_type` varchar(100) NOT NULL,
  `idempotency_key` varchar(150) NOT NULL,
  `status` varchar(30) DEFAULT 'processed',
  `payload_hash` varchar(64) DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ecommerce_idempotency` (`idempotency_key`),
  KEY `idx_ecommerce_event_status` (`event_type`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ecommerce_webhook_logs`
--

LOCK TABLES `ecommerce_webhook_logs` WRITE;
/*!40000 ALTER TABLE `ecommerce_webhook_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `ecommerce_webhook_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_campaign_logs`
--

DROP TABLE IF EXISTS `email_campaign_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_campaign_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `email_campaign_id` bigint unsigned NOT NULL,
  `member_id` bigint unsigned DEFAULT NULL,
  `status` varchar(30) DEFAULT 'queued',
  `message` text,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_email_campaign_log` (`email_campaign_id`),
  KEY `fk_email_campaign_logs_member_id` (`member_id`),
  CONSTRAINT `fk_email_campaign_logs_email_campaign_id` FOREIGN KEY (`email_campaign_id`) REFERENCES `email_campaigns` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_email_campaign_logs_member_id` FOREIGN KEY (`member_id`) REFERENCES `campaign_members` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_campaign_logs`
--

LOCK TABLES `email_campaign_logs` WRITE;
/*!40000 ALTER TABLE `email_campaign_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_campaign_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_campaigns`
--

DROP TABLE IF EXISTS `email_campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_campaigns` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `campaign_id` bigint unsigned DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `template` text,
  `schedule_at` datetime DEFAULT NULL,
  `status` varchar(50) DEFAULT 'draft',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_email_campaigns_campaign_id` (`campaign_id`),
  CONSTRAINT `fk_email_campaigns_campaign_id` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_campaigns`
--

LOCK TABLES `email_campaigns` WRITE;
/*!40000 ALTER TABLE `email_campaigns` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_campaigns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employees` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_code` varchar(60) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `status` varchar(30) DEFAULT 'active',
  `join_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `code` varchar(60) DEFAULT NULL,
  `first_name` varchar(120) DEFAULT NULL,
  `last_name` varchar(120) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `position_id` bigint unsigned DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_employee_code` (`employee_code`),
  KEY `idx_employee_status` (`status`),
  KEY `fk_employees_branch_id` (`branch_id`),
  KEY `fk_employees_department_id` (`department_id`),
  KEY `fk_employees_position_id` (`position_id`),
  KEY `fk_employees_user_id` (`user_id`),
  CONSTRAINT `fk_employees_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_employees_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_employees_position` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_employees_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (1,'EMP001','Admin User',NULL,'active',NULL,'2025-12-06 03:43:05','2025-12-06 03:43:05',1,'EMP001','Admin','User','admin@lanocrm.local','0900000001',1,1,'2023-12-06'),(2,'EMP002','Manager Test',NULL,'active',NULL,'2025-12-06 03:43:05','2025-12-06 03:43:05',2,'EMP002','Manager','Test','manager@lanocrm.local','0900000002',2,2,'2024-12-06'),(3,'EMP003','Staff Test',NULL,'active',NULL,'2025-12-06 03:43:05','2025-12-06 03:43:05',3,'EMP003','Staff','Test','staff@lanocrm.local','0900000003',2,3,'2025-06-06');
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exchange_rates`
--

DROP TABLE IF EXISTS `exchange_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `exchange_rates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `currency` varchar(10) NOT NULL,
  `rate` decimal(16,6) NOT NULL,
  `valid_from` date NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_exchange_rate_curr_date` (`currency`,`valid_from`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exchange_rates`
--

LOCK TABLES `exchange_rates` WRITE;
/*!40000 ALTER TABLE `exchange_rates` DISABLE KEYS */;
/*!40000 ALTER TABLE `exchange_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gl_entries`
--

DROP TABLE IF EXISTS `gl_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gl_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `posting_date` date NOT NULL,
  `account_id` bigint unsigned NOT NULL,
  `debit` decimal(14,2) DEFAULT '0.00',
  `credit` decimal(14,2) DEFAULT '0.00',
  `party_type` varchar(60) DEFAULT NULL,
  `party_id` bigint unsigned DEFAULT NULL,
  `reference_type` varchar(100) DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `remarks` text,
  `currency` varchar(10) DEFAULT 'VND',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_gl_account` (`account_id`),
  KEY `idx_gl_posting_date` (`posting_date`),
  KEY `idx_gl_party` (`party_type`,`party_id`),
  CONSTRAINT `fk_gl_entries_account_id` FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gl_entries`
--

LOCK TABLES `gl_entries` WRITE;
/*!40000 ALTER TABLE `gl_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `gl_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `goods_receipt_items`
--

DROP TABLE IF EXISTS `goods_receipt_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `goods_receipt_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `goods_receipt_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `quantity` decimal(14,3) DEFAULT '0.000',
  `rate` decimal(14,2) DEFAULT '0.00',
  `amount` decimal(14,2) DEFAULT '0.00',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_grn_item_grn` (`goods_receipt_id`),
  KEY `fk_goods_receipt_items_product_id` (`product_id`),
  CONSTRAINT `fk_goods_receipt_items_goods_receipt_id` FOREIGN KEY (`goods_receipt_id`) REFERENCES `goods_receipts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_goods_receipt_items_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `goods_receipt_items`
--

LOCK TABLES `goods_receipt_items` WRITE;
/*!40000 ALTER TABLE `goods_receipt_items` DISABLE KEYS */;
INSERT INTO `goods_receipt_items` VALUES (6,4,501,100.000,400000.00,40000000.00,'2025-12-06 03:58:33','2025-12-06 03:58:33'),(7,4,502,20.000,500000.00,10000000.00,'2025-12-06 03:58:33','2025-12-06 03:58:33'),(8,5,503,50.000,450000.00,22500000.00,'2025-12-06 03:58:33','2025-12-06 03:58:33'),(9,5,501,25.000,500000.00,12500000.00,'2025-12-06 03:58:33','2025-12-06 03:58:33'),(10,6,502,30.000,1500000.00,45000000.00,'2025-12-06 03:58:33','2025-12-06 03:58:33');
/*!40000 ALTER TABLE `goods_receipt_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `goods_receipts`
--

DROP TABLE IF EXISTS `goods_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `goods_receipts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `receipt_number` varchar(50) NOT NULL,
  `purchase_order_id` bigint unsigned DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `status` varchar(30) DEFAULT 'draft',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_grn_number` (`receipt_number`),
  KEY `fk_goods_receipts_branch_id` (`branch_id`),
  KEY `fk_goods_receipts_purchase_order_id` (`purchase_order_id`),
  CONSTRAINT `fk_goods_receipts_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_goods_receipts_purchase_order_id` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `goods_receipts`
--

LOCK TABLES `goods_receipts` WRITE;
/*!40000 ALTER TABLE `goods_receipts` DISABLE KEYS */;
INSERT INTO `goods_receipts` VALUES (4,'GRN-2024-001',41,1,'completed','2025-11-13 03:58:33','2025-11-13 03:58:33'),(5,'GRN-2024-002',42,1,'completed','2025-11-18 03:58:33','2025-11-18 03:58:33'),(6,'GRN-2024-003',43,2,'completed','2025-11-23 03:58:33','2025-11-23 03:58:33');
/*!40000 ALTER TABLE `goods_receipts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_alerts`
--

DROP TABLE IF EXISTS `inventory_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_alerts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `alert_type` varchar(50) DEFAULT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `variant_id` bigint unsigned DEFAULT NULL,
  `warehouse_id` bigint unsigned DEFAULT NULL,
  `current_quantity` decimal(10,2) DEFAULT NULL,
  `threshold_quantity` decimal(10,2) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `resolved_by` int DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_inventory_alerts_product_id` (`product_id`),
  KEY `fk_inventory_alerts_variant_id` (`variant_id`),
  KEY `fk_inventory_alerts_warehouse_id` (`warehouse_id`),
  CONSTRAINT `fk_inventory_alerts_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_inventory_alerts_variant_id` FOREIGN KEY (`variant_id`) REFERENCES `product_variants_v2` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_inventory_alerts_warehouse_id` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_alerts`
--

LOCK TABLES `inventory_alerts` WRITE;
/*!40000 ALTER TABLE `inventory_alerts` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_movements`
--

DROP TABLE IF EXISTS `inventory_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_movements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned DEFAULT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `variant_id` bigint unsigned DEFAULT NULL,
  `batch_id` bigint unsigned DEFAULT NULL,
  `serial_number` varchar(160) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int DEFAULT NULL,
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_inventory_movements_product_id` (`product_id`),
  KEY `fk_inventory_movements_variant_id` (`variant_id`),
  KEY `fk_inventory_movements_branch_id` (`branch_id`),
  KEY `fk_inventory_movements_batch_id` (`batch_id`),
  CONSTRAINT `fk_inventory_movements_batch_id` FOREIGN KEY (`batch_id`) REFERENCES `product_batches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_inventory_movements_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_inventory_movements_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_inventory_movements_variant_id` FOREIGN KEY (`variant_id`) REFERENCES `product_variants_v2` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_movements`
--

LOCK TABLES `inventory_movements` WRITE;
/*!40000 ALTER TABLE `inventory_movements` DISABLE KEYS */;
INSERT INTO `inventory_movements` VALUES (1,1,513,NULL,NULL,NULL,'sale',-1.00,'order',246,'POS auto-complete deduction',NULL,'2025-12-06 12:42:32','2025-12-06 12:42:32'),(2,1,511,NULL,NULL,NULL,'sale',-1.00,'order',247,'POS auto-complete deduction',NULL,'2025-12-06 12:50:53','2025-12-06 12:50:53'),(3,1,511,NULL,NULL,NULL,'sale',-1.00,'order',248,'POS auto-complete deduction',NULL,'2025-12-06 12:54:27','2025-12-06 12:54:27'),(4,1,511,NULL,NULL,NULL,'sale',-1.00,'order',249,'POS auto-complete deduction',NULL,'2025-12-06 13:19:08','2025-12-06 13:19:08'),(5,1,511,NULL,NULL,NULL,'sale',-1.00,'order',250,'POS auto-complete deduction',NULL,'2025-12-06 13:21:42','2025-12-06 13:21:42'),(6,1,515,NULL,NULL,NULL,'sale',-1.00,'order',251,'POS auto-complete deduction',NULL,'2025-12-07 11:45:48','2025-12-07 11:45:48'),(7,1,515,NULL,NULL,NULL,'sale',-1.00,'order',253,'POS auto-complete deduction',NULL,'2025-12-07 19:34:42','2025-12-07 19:34:42'),(8,1,515,NULL,NULL,NULL,'sale',-2.00,'order',254,'POS auto-complete deduction',NULL,'2025-12-07 19:41:24','2025-12-07 19:41:24'),(9,1,515,NULL,NULL,NULL,'sale',-1.00,'order',255,'POS auto-complete deduction',NULL,'2025-12-07 19:41:37','2025-12-07 19:41:37'),(10,1,515,NULL,NULL,NULL,'sale',-1.00,'order',256,'POS auto-complete deduction',NULL,'2025-12-08 01:20:08','2025-12-08 01:20:08'),(11,1,515,NULL,NULL,NULL,'sale',-1.00,'order',259,'POS auto-complete deduction',NULL,'2025-12-08 08:27:39','2025-12-08 08:27:39'),(12,1,515,NULL,NULL,NULL,'sale',-1.00,'order',261,'POS auto-complete deduction',NULL,'2025-12-08 09:28:34','2025-12-08 09:28:34'),(13,1,513,NULL,NULL,NULL,'sale',-1.00,'order',262,'POS auto-complete deduction',NULL,'2025-12-08 09:38:07','2025-12-08 09:38:07'),(14,1,502,NULL,NULL,NULL,'sale',-1.00,'order',263,'POS auto-complete deduction',NULL,'2025-12-08 09:51:09','2025-12-08 09:51:09'),(15,1,506,NULL,NULL,NULL,'sale',-1.00,'order',264,'POS auto-complete deduction',NULL,'2025-12-08 10:00:21','2025-12-08 10:00:21');
/*!40000 ALTER TABLE `inventory_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_stock`
--

DROP TABLE IF EXISTS `inventory_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_stock` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned DEFAULT NULL,
  `warehouse_id` bigint unsigned DEFAULT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `variant_id` bigint unsigned DEFAULT NULL,
  `quantity_on_hand` decimal(10,2) DEFAULT '0.00',
  `quantity_reserved` decimal(10,2) DEFAULT '0.00',
  `minimum_stock` decimal(10,2) DEFAULT '0.00',
  `last_movement_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_inventory_stock_product_id` (`product_id`),
  KEY `fk_inventory_stock_variant_id` (`variant_id`),
  KEY `fk_inventory_stock_branch_id` (`branch_id`),
  KEY `fk_inventory_stock_warehouse` (`warehouse_id`),
  CONSTRAINT `fk_inventory_stock_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_inventory_stock_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_inventory_stock_variant_id` FOREIGN KEY (`variant_id`) REFERENCES `product_variants_v2` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_inventory_stock_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=215 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_stock`
--

LOCK TABLES `inventory_stock` WRITE;
/*!40000 ALTER TABLE `inventory_stock` DISABLE KEYS */;
INSERT INTO `inventory_stock` VALUES (2,1,2,501,NULL,88.00,4.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(3,2,3,501,NULL,15.00,0.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(4,2,4,501,NULL,49.00,3.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(5,3,5,501,NULL,75.00,0.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(6,4,6,501,NULL,37.00,0.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(7,5,7,501,NULL,44.00,5.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(9,1,2,502,NULL,61.00,1.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(10,2,3,502,NULL,33.00,4.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(11,2,4,502,NULL,85.00,2.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(12,3,5,502,NULL,64.00,4.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(13,4,6,502,NULL,32.00,0.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(14,5,7,502,NULL,46.00,3.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(16,1,2,503,NULL,43.00,0.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(17,2,3,503,NULL,33.00,2.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(18,2,4,503,NULL,16.00,3.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(19,3,5,503,NULL,47.00,1.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(20,4,6,503,NULL,98.00,4.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(21,5,7,503,NULL,65.00,2.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(23,1,2,504,NULL,33.00,4.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(24,2,3,504,NULL,63.00,3.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(25,2,4,504,NULL,40.00,4.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(26,3,5,504,NULL,22.00,1.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(27,4,6,504,NULL,19.00,1.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(28,5,7,504,NULL,16.00,0.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(30,1,2,505,NULL,80.00,5.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(31,2,3,505,NULL,100.00,2.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(32,2,4,505,NULL,95.00,4.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(33,3,5,505,NULL,67.00,4.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(34,4,6,505,NULL,77.00,0.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(35,5,7,505,NULL,41.00,0.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(37,1,2,506,NULL,27.00,1.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(38,2,3,506,NULL,74.00,5.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(39,2,4,506,NULL,43.00,4.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(40,3,5,506,NULL,84.00,1.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(41,4,6,506,NULL,92.00,0.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(42,5,7,506,NULL,30.00,1.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(44,1,2,507,NULL,33.00,3.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(45,2,3,507,NULL,63.00,0.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(46,2,4,507,NULL,71.00,3.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(47,3,5,507,NULL,79.00,4.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(48,4,6,507,NULL,65.00,1.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(49,5,7,507,NULL,39.00,5.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(51,1,2,508,NULL,95.00,4.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(52,2,3,508,NULL,43.00,5.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(53,2,4,508,NULL,91.00,4.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(54,3,5,508,NULL,36.00,5.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(55,4,6,508,NULL,64.00,2.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(56,5,7,508,NULL,47.00,1.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(58,1,2,509,NULL,22.00,1.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(59,2,3,509,NULL,66.00,2.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(60,2,4,509,NULL,61.00,5.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(61,3,5,509,NULL,15.00,0.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(62,4,6,509,NULL,34.00,3.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(63,5,7,509,NULL,61.00,2.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(65,1,2,510,NULL,20.00,5.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(66,2,3,510,NULL,77.00,0.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(67,2,4,510,NULL,69.00,2.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(68,3,5,510,NULL,53.00,0.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(69,4,6,510,NULL,80.00,5.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(70,5,7,510,NULL,72.00,5.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(72,1,2,511,NULL,10.00,1.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(73,2,3,511,NULL,92.00,3.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(74,2,4,511,NULL,94.00,2.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(75,3,5,511,NULL,50.00,0.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(76,4,6,511,NULL,20.00,1.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(77,5,7,511,NULL,91.00,0.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(79,1,2,512,NULL,74.00,5.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(80,2,3,512,NULL,26.00,2.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(81,2,4,512,NULL,25.00,5.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(82,3,5,512,NULL,12.00,0.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(83,4,6,512,NULL,26.00,3.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(84,5,7,512,NULL,19.00,2.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(86,1,2,513,NULL,12.00,2.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(87,2,3,513,NULL,15.00,3.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(88,2,4,513,NULL,25.00,1.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(89,3,5,513,NULL,81.00,0.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(90,4,6,513,NULL,36.00,4.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(91,5,7,513,NULL,89.00,1.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(93,1,2,514,NULL,78.00,0.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(94,2,3,514,NULL,45.00,5.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(95,2,4,514,NULL,80.00,2.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(96,3,5,514,NULL,61.00,2.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(97,4,6,514,NULL,71.00,5.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(98,5,7,514,NULL,27.00,5.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(100,1,2,515,NULL,43.00,3.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(101,2,3,515,NULL,19.00,4.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(102,2,4,515,NULL,51.00,3.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(103,3,5,515,NULL,49.00,0.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(104,4,6,515,NULL,87.00,4.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(105,5,7,515,NULL,75.00,0.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(107,1,2,516,NULL,32.00,0.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(108,2,3,516,NULL,83.00,2.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(109,2,4,516,NULL,16.00,1.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(110,3,5,516,NULL,65.00,5.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(111,4,6,516,NULL,28.00,3.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(112,5,7,516,NULL,54.00,1.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(114,1,2,517,NULL,36.00,5.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(115,2,3,517,NULL,58.00,3.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(116,2,4,517,NULL,26.00,0.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(117,3,5,517,NULL,37.00,5.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(118,4,6,517,NULL,73.00,1.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(119,5,7,517,NULL,61.00,1.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(121,1,2,518,NULL,19.00,2.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(122,2,3,518,NULL,13.00,2.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(123,2,4,518,NULL,78.00,5.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(124,3,5,518,NULL,87.00,0.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(125,4,6,518,NULL,20.00,4.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(126,5,7,518,NULL,54.00,2.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(128,1,2,519,NULL,66.00,1.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(129,2,3,519,NULL,30.00,2.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(130,2,4,519,NULL,68.00,4.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(131,3,5,519,NULL,29.00,3.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(132,4,6,519,NULL,34.00,2.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(133,5,7,519,NULL,88.00,2.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(135,1,2,520,NULL,81.00,3.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(136,2,3,520,NULL,99.00,1.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(137,2,4,520,NULL,21.00,4.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(138,3,5,520,NULL,25.00,5.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(139,4,6,520,NULL,71.00,4.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(140,5,7,520,NULL,65.00,1.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(142,1,2,521,NULL,75.00,2.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(143,2,3,521,NULL,23.00,1.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(144,2,4,521,NULL,17.00,2.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(145,3,5,521,NULL,35.00,3.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(146,4,6,521,NULL,51.00,2.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(147,5,7,521,NULL,12.00,3.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(149,1,2,522,NULL,44.00,0.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(150,2,3,522,NULL,98.00,5.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(151,2,4,522,NULL,74.00,3.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(152,3,5,522,NULL,21.00,5.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(153,4,6,522,NULL,11.00,0.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(154,5,7,522,NULL,36.00,5.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(156,1,2,523,NULL,74.00,2.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(157,2,3,523,NULL,50.00,1.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(158,2,4,523,NULL,47.00,0.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(159,3,5,523,NULL,25.00,2.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(160,4,6,523,NULL,24.00,2.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(161,5,7,523,NULL,85.00,4.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(163,1,2,524,NULL,12.00,2.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(164,2,3,524,NULL,39.00,2.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(165,2,4,524,NULL,71.00,1.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(166,3,5,524,NULL,92.00,2.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(167,4,6,524,NULL,27.00,1.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(168,5,7,524,NULL,26.00,1.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(170,1,2,525,NULL,13.00,4.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(171,2,3,525,NULL,28.00,2.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(172,2,4,525,NULL,11.00,1.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(173,3,5,525,NULL,25.00,4.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(174,4,6,525,NULL,55.00,2.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(175,5,7,525,NULL,86.00,3.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(177,1,2,526,NULL,64.00,0.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(178,2,3,526,NULL,31.00,2.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(179,2,4,526,NULL,55.00,5.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(180,3,5,526,NULL,54.00,4.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(181,4,6,526,NULL,53.00,2.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(182,5,7,526,NULL,87.00,3.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(184,1,2,527,NULL,10.00,4.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(185,2,3,527,NULL,72.00,1.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(186,2,4,527,NULL,94.00,0.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(187,3,5,527,NULL,20.00,4.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(188,4,6,527,NULL,86.00,4.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(189,5,7,527,NULL,58.00,1.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(191,1,2,528,NULL,64.00,5.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(192,2,3,528,NULL,63.00,3.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(193,2,4,528,NULL,33.00,4.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(194,3,5,528,NULL,81.00,1.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(195,4,6,528,NULL,86.00,4.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(196,5,7,528,NULL,33.00,0.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(198,1,2,529,NULL,30.00,2.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(199,2,3,529,NULL,84.00,4.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(200,2,4,529,NULL,97.00,1.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(201,3,5,529,NULL,80.00,4.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(202,4,6,529,NULL,61.00,1.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(203,5,7,529,NULL,93.00,4.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(205,1,2,530,NULL,76.00,5.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(206,2,3,530,NULL,74.00,4.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(207,2,4,530,NULL,95.00,3.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(208,3,5,530,NULL,38.00,1.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(209,4,6,530,NULL,61.00,1.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(210,5,7,530,NULL,37.00,0.00,5.00,'2025-12-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(211,1,1,515,NULL,-1.00,0.00,0.00,'2025-12-08 09:28:34','2025-12-08 09:28:34','2025-12-08 09:28:34'),(212,1,1,513,NULL,-1.00,0.00,0.00,'2025-12-08 09:38:07','2025-12-08 09:38:07','2025-12-08 09:38:07'),(213,1,1,502,NULL,-1.00,0.00,0.00,'2025-12-08 09:51:09','2025-12-08 09:51:09','2025-12-08 09:51:09'),(214,1,1,506,NULL,-1.00,0.00,0.00,'2025-12-08 10:00:21','2025-12-08 10:00:21','2025-12-08 10:00:21');
/*!40000 ALTER TABLE `inventory_stock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_valuation`
--

DROP TABLE IF EXISTS `inventory_valuation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_valuation` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned DEFAULT NULL,
  `variant_id` bigint unsigned DEFAULT NULL,
  `avg_cost` decimal(12,2) DEFAULT '0.00',
  `total_cost` decimal(14,2) DEFAULT '0.00',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_inventory_valuation_product_id` (`product_id`),
  KEY `fk_inventory_valuation_variant_id` (`variant_id`),
  CONSTRAINT `fk_inventory_valuation_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_inventory_valuation_variant_id` FOREIGN KEY (`variant_id`) REFERENCES `product_variants_v2` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_valuation`
--

LOCK TABLES `inventory_valuation` WRITE;
/*!40000 ALTER TABLE `inventory_valuation` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_valuation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoice_orders`
--

DROP TABLE IF EXISTS `invoice_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint unsigned DEFAULT NULL,
  `order_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_invoice_orders_invoice` (`invoice_id`),
  KEY `fk_invoice_orders_order` (`order_id`),
  CONSTRAINT `fk_invoice_orders_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_invoice_orders_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoice_orders`
--

LOCK TABLES `invoice_orders` WRITE;
/*!40000 ALTER TABLE `invoice_orders` DISABLE KEYS */;
INSERT INTO `invoice_orders` VALUES (61,61,219,'2025-12-06 03:58:33','2025-12-06 03:58:33'),(62,62,220,'2025-12-06 03:58:33','2025-12-06 03:58:33'),(63,63,221,'2025-12-06 03:58:33','2025-12-06 03:58:33'),(64,64,222,'2025-12-06 03:58:33','2025-12-06 03:58:33'),(65,65,223,'2025-12-06 03:58:33','2025-12-06 03:58:33'),(66,66,241,'2025-12-06 03:58:33','2025-12-06 03:58:33'),(67,67,242,'2025-12-06 03:58:33','2025-12-06 03:58:33'),(68,68,243,'2025-12-06 03:58:33','2025-12-06 03:58:33'),(69,69,244,'2025-12-06 03:58:33','2025-12-06 03:58:33'),(70,70,245,'2025-12-06 03:58:33','2025-12-06 03:58:33'),(71,71,263,'2025-12-08 09:51:09','2025-12-08 09:51:09'),(72,72,264,'2025-12-08 10:00:21','2025-12-08 10:00:21');
/*!40000 ALTER TABLE `invoice_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) DEFAULT NULL COMMENT 'Generated when issuing invoice',
  `invoice_status` varchar(30) DEFAULT NULL,
  `invoice_type` varchar(20) DEFAULT NULL,
  `e_invoice_status` varchar(30) DEFAULT NULL,
  `delivery_status` varchar(30) DEFAULT NULL,
  `shipment_code` varchar(100) DEFAULT NULL,
  `shipping_partner` varchar(50) DEFAULT NULL,
  `delivery_time` datetime DEFAULT NULL,
  `delivery_note` varchar(500) DEFAULT NULL,
  `sales_channel` varchar(50) DEFAULT NULL,
  `seller_id` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT '0.00',
  `goods_total` decimal(10,2) DEFAULT '0.00',
  `discount_total` decimal(10,2) DEFAULT '0.00',
  `net_total` decimal(10,2) DEFAULT '0.00',
  `vat_rate` decimal(5,2) DEFAULT '0.00',
  `vat_amount` decimal(10,2) DEFAULT '0.00',
  `tax_amount` decimal(10,2) DEFAULT '0.00',
  `tax_discount` decimal(10,2) DEFAULT '0.00',
  `other_fee` decimal(10,2) DEFAULT '0.00',
  `shipping_fee` decimal(10,2) DEFAULT '0.00',
  `customer_payable` decimal(10,2) DEFAULT '0.00',
  `customer_paid` decimal(10,2) DEFAULT '0.00',
  `cod_amount` decimal(10,2) DEFAULT '0.00',
  `rounding_adjustment` decimal(10,2) DEFAULT '0.00',
  `payment_status` varchar(20) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_discount` decimal(10,2) DEFAULT '0.00',
  `total_paid` decimal(10,2) DEFAULT '0.00',
  `currency_code` varchar(10) DEFAULT 'VND',
  `exchange_rate` decimal(15,6) DEFAULT '1.000000',
  `last_payment_date` datetime DEFAULT NULL,
  `total` decimal(10,2) DEFAULT '0.00',
  `pdf_path` varchar(255) DEFAULT NULL,
  `notes` text,
  `meta` json DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `fk_invoices_customer_id` (`customer_id`),
  KEY `fk_invoices_branch_id` (`branch_id`),
  CONSTRAINT `fk_invoices_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_invoices_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
INSERT INTO `invoices` VALUES (61,'HD-DEMO-1-0001','completed','standard',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2011,1,'2025-11-26','2025-12-08',416000.00,416000.00,0.00,416000.00,0.10,41600.00,41600.00,0.00,0.00,0.00,457600.00,457600.00,0.00,0.00,'paid',NULL,0.00,457600.00,'VND',1.000000,NULL,457600.00,NULL,'Hóa đơn demo gắn với DH-DEMO-009','{\"source\": \"demo\"}',1,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(62,'HD-DEMO-2-0001','completed','standard',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2012,2,'2025-11-27','2025-12-09',611600.00,624000.00,42400.00,611600.00,0.05,30580.00,30580.00,0.00,0.00,30000.00,611600.00,611600.00,0.00,0.00,'paid',NULL,0.00,611600.00,'VND',1.000000,NULL,611600.00,NULL,'Hóa đơn demo gắn với DH-DEMO-010','{\"source\": \"demo\"}',1,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(63,'HD-DEMO-3-0001','completed','standard',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2013,3,'2025-11-28','2025-12-10',379400.00,426000.00,66600.00,379400.00,0.05,18970.00,18970.00,0.00,0.00,20000.00,398370.00,398370.00,0.00,0.00,'paid',NULL,0.00,398370.00,'VND',1.000000,NULL,398370.00,NULL,'Hóa đơn demo gắn với DH-DEMO-011','{\"source\": \"demo\"}',1,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(64,'HD-DEMO-4-0001','completed','standard',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2014,4,'2025-11-29','2025-12-11',418000.00,418000.00,0.00,418000.00,0.10,41800.00,41800.00,0.00,0.00,0.00,459800.00,459800.00,0.00,0.00,'paid',NULL,0.00,459800.00,'VND',1.000000,NULL,459800.00,NULL,'Hóa đơn demo gắn với DH-DEMO-012','{\"source\": \"demo\"}',1,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(65,'HD-DEMO-5-0001','completed','standard',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2015,5,'2025-11-30','2025-12-12',594600.00,622000.00,42400.00,594600.00,0.05,29730.00,29730.00,0.00,0.00,15000.00,594600.00,594600.00,0.00,0.00,'paid',NULL,0.00,594600.00,'VND',1.000000,NULL,594600.00,NULL,'Hóa đơn demo gắn với DH-DEMO-013','{\"source\": \"demo\"}',1,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(66,'HD-DEMO-5-0002','completed','standard',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2011,5,'2025-12-01','2025-12-13',2154000.00,2144000.00,0.00,2154000.00,0.10,215400.00,215400.00,0.00,0.00,10000.00,2154000.00,2154000.00,0.00,0.00,'paid',NULL,0.00,2154000.00,'VND',1.000000,NULL,2154000.00,NULL,'Hóa đơn demo gắn với DH-DEMO-031','{\"source\": \"demo\"}',1,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(67,'HD-DEMO-3-0002','completed','standard',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2011,3,'2025-12-02','2025-12-14',944000.00,904000.00,0.00,944000.00,0.05,47200.00,47200.00,0.00,0.00,40000.00,991200.00,991200.00,0.00,0.00,'paid',NULL,0.00,991200.00,'VND',1.000000,NULL,991200.00,NULL,'Hóa đơn demo gắn với DH-DEMO-032','{\"source\": \"demo\"}',1,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(68,'HD-DEMO-1-0002','completed','standard',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2012,1,'2025-12-03','2025-12-15',1160000.00,1140000.00,0.00,1160000.00,0.10,116000.00,116000.00,0.00,0.00,20000.00,1276000.00,1276000.00,0.00,0.00,'paid',NULL,0.00,1276000.00,'VND',1.000000,NULL,1276000.00,NULL,'Hóa đơn demo gắn với DH-DEMO-033','{\"source\": \"demo\"}',1,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(69,'HD-DEMO-3-0003','completed','standard',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2015,3,'2025-12-04','2025-12-16',1596000.00,1596000.00,0.00,1596000.00,0.10,159600.00,159600.00,0.00,0.00,0.00,1596000.00,1596000.00,0.00,0.00,'paid',NULL,0.00,1596000.00,'VND',1.000000,NULL,1596000.00,NULL,'Hóa đơn demo gắn với DH-DEMO-034','{\"source\": \"demo\"}',1,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(70,'HD-DEMO-2-0002','completed','standard',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2011,2,'2025-12-05','2025-12-17',2084000.00,2034000.00,0.00,2084000.00,0.05,104200.00,104200.00,0.00,0.00,50000.00,2188200.00,2188200.00,0.00,0.00,'paid',NULL,0.00,2188200.00,'VND',1.000000,NULL,2188200.00,NULL,'Hóa đơn demo gắn với DH-DEMO-035','{\"source\": \"demo\"}',1,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(71,'HN0125120800001','completed','pickup',NULL,NULL,NULL,NULL,NULL,NULL,'pos',NULL,NULL,1,'2025-12-08',NULL,818000.00,818000.00,-378000.00,1196000.00,0.00,0.00,0.00,0.00,0.00,0.00,1196000.00,1196000.00,0.00,0.00,'paid','CASH',0.00,1196000.00,'VND',1.000000,NULL,1196000.00,NULL,'',NULL,NULL,'2025-12-08 09:51:09','2025-12-08 09:51:09',NULL),(72,'HN0125120800002','completed','pickup',NULL,NULL,NULL,NULL,NULL,NULL,'pos',NULL,NULL,1,'2025-12-08',NULL,706000.00,706000.00,-687000.00,1393000.00,0.00,0.00,0.00,0.00,0.00,0.00,1393000.00,1393000.00,0.00,0.00,'paid','CASH',0.00,1393000.00,'VND',1.000000,NULL,1393000.00,NULL,'',NULL,NULL,'2025-12-08 10:00:21','2025-12-08 10:00:21',NULL);
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_logs`
--

DROP TABLE IF EXISTS `job_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `job_id` bigint unsigned NOT NULL,
  `status` varchar(30) NOT NULL,
  `message` text,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_job_log_job` (`job_id`),
  CONSTRAINT `fk_job_logs_job_id` FOREIGN KEY (`job_id`) REFERENCES `job_queue` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_logs`
--

LOCK TABLES `job_logs` WRITE;
/*!40000 ALTER TABLE `job_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_queue`
--

DROP TABLE IF EXISTS `job_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_queue` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `payload` json DEFAULT NULL,
  `status` varchar(30) DEFAULT 'queued',
  `attempts` int DEFAULT '0',
  `max_attempts` int DEFAULT '3',
  `next_run_at` datetime DEFAULT NULL,
  `last_error` text,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_job_status` (`status`,`next_run_at`),
  KEY `idx_job_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_queue`
--

LOCK TABLES `job_queue` WRITE;
/*!40000 ALTER TABLE `job_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `journal_entries`
--

DROP TABLE IF EXISTS `journal_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `journal_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reference_number` varchar(50) DEFAULT NULL,
  `date` date NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'draft',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `journal_entries`
--

LOCK TABLES `journal_entries` WRITE;
/*!40000 ALTER TABLE `journal_entries` DISABLE KEYS */;
INSERT INTO `journal_entries` VALUES (1,'JE-2024-001','2025-12-01','Thu tiền bán hàng','posted',1,'2025-12-06 03:35:23','2025-12-06 03:35:23'),(2,'JE-2024-002','2025-12-04','Thanh toán tiền điện','posted',1,'2025-12-06 03:35:23','2025-12-06 03:35:23');
/*!40000 ALTER TABLE `journal_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `journal_entry_lines`
--

DROP TABLE IF EXISTS `journal_entry_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `journal_entry_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `journal_entry_id` bigint unsigned NOT NULL,
  `account_code` varchar(50) NOT NULL,
  `debit` decimal(14,2) DEFAULT '0.00',
  `credit` decimal(14,2) DEFAULT '0.00',
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_jel_journal_entry_id` (`journal_entry_id`),
  CONSTRAINT `fk_jel_journal_entry` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `journal_entry_lines`
--

LOCK TABLES `journal_entry_lines` WRITE;
/*!40000 ALTER TABLE `journal_entry_lines` DISABLE KEYS */;
INSERT INTO `journal_entry_lines` VALUES (1,1,'111',5000000.00,0.00,'Thu tiền mặt','2025-12-06 03:35:23','2025-12-06 03:35:23'),(2,1,'511',0.00,5000000.00,'Doanh thu','2025-12-06 03:35:23','2025-12-06 03:35:23'),(3,2,'642',1000000.00,0.00,'Chi phí điện','2025-12-06 03:35:23','2025-12-06 03:35:23'),(4,2,'112',0.00,1000000.00,'Chuyển khoản','2025-12-06 03:35:23','2025-12-06 03:35:23'),(5,1,'111',5000000.00,0.00,'Thu tiền mặt','2025-12-06 03:40:39','2025-12-06 03:40:39'),(6,1,'511',0.00,5000000.00,'Doanh thu','2025-12-06 03:40:39','2025-12-06 03:40:39'),(7,2,'642',1000000.00,0.00,'Chi phí điện','2025-12-06 03:40:39','2025-12-06 03:40:39'),(8,2,'112',0.00,1000000.00,'Chuyển khoản','2025-12-06 03:40:39','2025-12-06 03:40:39'),(9,1,'111',5000000.00,0.00,'Thu tiền mặt','2025-12-06 03:43:34','2025-12-06 03:43:34'),(10,1,'511',0.00,5000000.00,'Doanh thu','2025-12-06 03:43:34','2025-12-06 03:43:34'),(11,2,'642',1000000.00,0.00,'Chi phí điện','2025-12-06 03:43:34','2025-12-06 03:43:34'),(12,2,'112',0.00,1000000.00,'Chuyển khoản','2025-12-06 03:43:34','2025-12-06 03:43:34'),(13,1,'111',5000000.00,0.00,'Thu tiền mặt','2025-12-06 03:44:48','2025-12-06 03:44:48'),(14,1,'511',0.00,5000000.00,'Doanh thu','2025-12-06 03:44:48','2025-12-06 03:44:48'),(15,2,'642',1000000.00,0.00,'Chi phí điện','2025-12-06 03:44:48','2025-12-06 03:44:48'),(16,2,'112',0.00,1000000.00,'Chuyển khoản','2025-12-06 03:44:48','2025-12-06 03:44:48'),(17,1,'111',5000000.00,0.00,'Thu tiền mặt','2025-12-06 03:54:23','2025-12-06 03:54:23'),(18,1,'511',0.00,5000000.00,'Doanh thu','2025-12-06 03:54:23','2025-12-06 03:54:23'),(19,2,'642',1000000.00,0.00,'Chi phí điện','2025-12-06 03:54:23','2025-12-06 03:54:23'),(20,2,'112',0.00,1000000.00,'Chuyển khoản','2025-12-06 03:54:23','2025-12-06 03:54:23'),(21,1,'111',5000000.00,0.00,'Thu tiền mặt','2025-12-06 03:58:33','2025-12-06 03:58:33'),(22,1,'511',0.00,5000000.00,'Doanh thu','2025-12-06 03:58:33','2025-12-06 03:58:33'),(23,2,'642',1000000.00,0.00,'Chi phí điện','2025-12-06 03:58:33','2025-12-06 03:58:33'),(24,2,'112',0.00,1000000.00,'Chuyển khoản','2025-12-06 03:58:33','2025-12-06 03:58:33');
/*!40000 ALTER TABLE `journal_entry_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `knowledge_base_articles`
--

DROP TABLE IF EXISTS `knowledge_base_articles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `knowledge_base_articles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `content` text,
  `is_published` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_kb_article_category` (`category_id`),
  KEY `idx_kb_article_published` (`is_published`),
  CONSTRAINT `fk_knowledge_base_artic_category_id` FOREIGN KEY (`category_id`) REFERENCES `knowledge_base_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `knowledge_base_articles`
--

LOCK TABLES `knowledge_base_articles` WRITE;
/*!40000 ALTER TABLE `knowledge_base_articles` DISABLE KEYS */;
/*!40000 ALTER TABLE `knowledge_base_articles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `knowledge_base_categories`
--

DROP TABLE IF EXISTS `knowledge_base_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `knowledge_base_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `description` text,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kb_category` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `knowledge_base_categories`
--

LOCK TABLES `knowledge_base_categories` WRITE;
/*!40000 ALTER TABLE `knowledge_base_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `knowledge_base_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `landed_cost_items`
--

DROP TABLE IF EXISTS `landed_cost_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `landed_cost_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `landed_cost_voucher_id` bigint unsigned NOT NULL,
  `goods_receipt_item_id` bigint unsigned DEFAULT NULL,
  `cost_component` varchar(150) NOT NULL,
  `amount` decimal(14,2) DEFAULT '0.00',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_lcv_item` (`landed_cost_voucher_id`),
  KEY `fk_landed_cost_items_goods_receipt_item_i` (`goods_receipt_item_id`),
  CONSTRAINT `fk_landed_cost_items_goods_receipt_item_i` FOREIGN KEY (`goods_receipt_item_id`) REFERENCES `goods_receipt_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_landed_cost_items_landed_cost_voucher_` FOREIGN KEY (`landed_cost_voucher_id`) REFERENCES `landed_cost_vouchers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `landed_cost_items`
--

LOCK TABLES `landed_cost_items` WRITE;
/*!40000 ALTER TABLE `landed_cost_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `landed_cost_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `landed_cost_vouchers`
--

DROP TABLE IF EXISTS `landed_cost_vouchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `landed_cost_vouchers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `voucher_number` varchar(50) NOT NULL,
  `goods_receipt_id` bigint unsigned DEFAULT NULL,
  `total_cost` decimal(14,2) DEFAULT '0.00',
  `status` varchar(30) DEFAULT 'draft',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lcv_number` (`voucher_number`),
  KEY `fk_landed_cost_vouchers_goods_receipt_id` (`goods_receipt_id`),
  CONSTRAINT `fk_landed_cost_vouchers_goods_receipt_id` FOREIGN KEY (`goods_receipt_id`) REFERENCES `goods_receipts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `landed_cost_vouchers`
--

LOCK TABLES `landed_cost_vouchers` WRITE;
/*!40000 ALTER TABLE `landed_cost_vouchers` DISABLE KEYS */;
/*!40000 ALTER TABLE `landed_cost_vouchers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leads`
--

DROP TABLE IF EXISTS `leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lead_number` varchar(50) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'new',
  `company` varchar(255) DEFAULT NULL,
  `notes` text,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `assigned_to` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leads`
--

LOCK TABLES `leads` WRITE;
/*!40000 ALTER TABLE `leads` DISABLE KEYS */;
INSERT INTO `leads` VALUES (1,NULL,'','john.doe@example.com','0912345678','website','new','Example Corp',NULL,'2025-12-06 03:35:23','2025-12-06 03:35:23','John','Doe',2),(2,NULL,'','jane.smith@example.com','0987654321','referral','contacted','Tech Solutions',NULL,'2025-12-06 03:35:23','2025-12-06 03:35:23','Jane','Smith',3);
/*!40000 ALTER TABLE `leads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_applications`
--

DROP TABLE IF EXISTS `leave_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_applications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `leave_type_id` bigint unsigned NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `total_days` decimal(10,2) DEFAULT '0.00',
  `status` varchar(30) DEFAULT 'pending',
  `reason` text,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_leave_employee` (`employee_id`),
  KEY `idx_leave_status` (`status`),
  KEY `fk_leave_applications_leave_type_id` (`leave_type_id`),
  CONSTRAINT `fk_leave_applications_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_leave_applications_leave_type_id` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_applications`
--

LOCK TABLES `leave_applications` WRITE;
/*!40000 ALTER TABLE `leave_applications` DISABLE KEYS */;
INSERT INTO `leave_applications` VALUES (1,3,1,'2025-12-11','2025-12-12',0.00,'pending','Personal matters',NULL,NULL,'2025-12-06 03:43:05','2025-12-06 03:43:05'),(2,3,1,'2025-12-11','2025-12-12',0.00,'pending','Personal matters',NULL,NULL,'2025-12-06 03:43:33','2025-12-06 03:43:33'),(3,3,1,'2025-12-11','2025-12-12',0.00,'pending','Personal matters',NULL,NULL,'2025-12-06 03:44:47','2025-12-06 03:44:47'),(4,3,1,'2025-12-11','2025-12-12',0.00,'pending','Personal matters',NULL,NULL,'2025-12-06 03:54:22','2025-12-06 03:54:22'),(5,3,1,'2025-12-11','2025-12-12',0.00,'pending','Personal matters',NULL,NULL,'2025-12-06 03:58:32','2025-12-06 03:58:32');
/*!40000 ALTER TABLE `leave_applications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_types`
--

DROP TABLE IF EXISTS `leave_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `leave_name` varchar(150) NOT NULL,
  `default_allocation` decimal(10,2) DEFAULT '0.00',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_leave_name` (`leave_name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_types`
--

LOCK TABLES `leave_types` WRITE;
/*!40000 ALTER TABLE `leave_types` DISABLE KEYS */;
INSERT INTO `leave_types` VALUES (1,'Annual Leave',12.00,'2025-12-06 03:43:05','2025-12-06 03:43:05'),(2,'Sick Leave',10.00,'2025-12-06 03:43:05','2025-12-06 03:43:05'),(3,'Unpaid Leave',0.00,'2025-12-06 03:43:05','2025-12-06 03:43:05');
/*!40000 ALTER TABLE `leave_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_attempts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `success` tinyint(1) NOT NULL DEFAULT '0',
  `failure_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attempted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loyalty_programs`
--

DROP TABLE IF EXISTS `loyalty_programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loyalty_programs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `customer_group_id` bigint unsigned DEFAULT NULL,
  `earn_rate` decimal(12,4) DEFAULT '0.0000',
  `redeem_rate` decimal(12,4) DEFAULT '0.0000',
  `expiry_days` int DEFAULT '365',
  `status` varchar(20) DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_loyalty_programs_customer_group` (`customer_group_id`),
  CONSTRAINT `fk_loyalty_programs_customer_group` FOREIGN KEY (`customer_group_id`) REFERENCES `customer_groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loyalty_programs`
--

LOCK TABLES `loyalty_programs` WRITE;
/*!40000 ALTER TABLE `loyalty_programs` DISABLE KEYS */;
/*!40000 ALTER TABLE `loyalty_programs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loyalty_transactions`
--

DROP TABLE IF EXISTS `loyalty_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loyalty_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `wallet_id` bigint unsigned NOT NULL,
  `order_id` bigint unsigned DEFAULT NULL,
  `points_delta` decimal(14,2) NOT NULL,
  `reason` varchar(120) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_loyalty_tx_wallet` (`wallet_id`),
  KEY `idx_loyalty_tx_order` (`order_id`),
  CONSTRAINT `fk_loyalty_transactions_order_id` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_loyalty_transactions_wallet_id` FOREIGN KEY (`wallet_id`) REFERENCES `loyalty_wallets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loyalty_transactions`
--

LOCK TABLES `loyalty_transactions` WRITE;
/*!40000 ALTER TABLE `loyalty_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `loyalty_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loyalty_wallets`
--

DROP TABLE IF EXISTS `loyalty_wallets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loyalty_wallets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `points_balance` decimal(14,2) DEFAULT '0.00',
  `last_earned_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_loyalty_wallet_customer` (`customer_id`),
  CONSTRAINT `fk_loyalty_wallets_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loyalty_wallets`
--

LOCK TABLES `loyalty_wallets` WRITE;
/*!40000 ALTER TABLE `loyalty_wallets` DISABLE KEYS */;
/*!40000 ALTER TABLE `loyalty_wallets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_schedules`
--

DROP TABLE IF EXISTS `maintenance_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `maintenance_schedules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` bigint unsigned NOT NULL,
  `schedule_name` varchar(150) NOT NULL,
  `frequency` varchar(50) DEFAULT 'monthly',
  `next_due_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_maint_asset` (`asset_id`),
  CONSTRAINT `fk_maintenance_schedule_asset_id` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_schedules`
--

LOCK TABLES `maintenance_schedules` WRITE;
/*!40000 ALTER TABLE `maintenance_schedules` DISABLE KEYS */;
/*!40000 ALTER TABLE `maintenance_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_work_orders`
--

DROP TABLE IF EXISTS `maintenance_work_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `maintenance_work_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `work_order_number` varchar(60) NOT NULL,
  `asset_id` bigint unsigned NOT NULL,
  `schedule_id` bigint unsigned DEFAULT NULL,
  `status` varchar(30) DEFAULT 'open',
  `description` text,
  `planned_date` date DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_maint_wo_number` (`work_order_number`),
  KEY `idx_maint_wo_asset` (`asset_id`),
  KEY `idx_maint_wo_status` (`status`),
  KEY `fk_maintenance_work_ord_schedule_id` (`schedule_id`),
  CONSTRAINT `fk_maintenance_work_ord_asset_id` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_maintenance_work_ord_schedule_id` FOREIGN KEY (`schedule_id`) REFERENCES `maintenance_schedules` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_work_orders`
--

LOCK TABLES `maintenance_work_orders` WRITE;
/*!40000 ALTER TABLE `maintenance_work_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `maintenance_work_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `class` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `group` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `namespace` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `time` int NOT NULL,
  `batch` int unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2025-12-05-000000','App\\Database\\Migrations\\BaselineSchema','default','App',1764990252,1),(2,'2025-12-05-150000','App\\Database\\Migrations\\AddMissingForeignKeys','default','App',1764990252,1),(3,'2025-12-05-160000','App\\Database\\Migrations\\CleanupDuplicateTables','default','App',1764990252,1),(4,'2025-12-05-170000','App\\Database\\Migrations\\CreateSupplierDebtTransactions','default','App',1764990252,1),(5,'2025-12-06-000000','App\\Database\\Migrations\\CreateLocationTables','default','App',1765016104,2),(6,'2025-12-06-000001','App\\Database\\Migrations\\CreateBankAccountsTable','default','App',1765016636,3),(7,'2025-12-06-000002','App\\Database\\Migrations\\CreateSalesChannelsTable','default','App',1765017276,4),(9,'2025-12-06-151800','App\\Database\\Migrations\\CreateStockTransfersTables','default','App',1765102500,5),(10,'2025-12-07-000000','App\\Database\\Migrations\\CreateStockDisposalsTables','default','App',1765102500,5),(11,'2025-12-07-100000','App\\Database\\Migrations\\AddDeliveryColumnsToInvoices','default','App',1765102500,5),(13,'2025-12-08-000000','App\\Database\\Migrations\\AddDeletedAtToInvoices','default','App',1765186459,6);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES (1,'App\\Models\\User',1),(1,'App\\Models\\User',4),(1,'App\\Models\\User',10),(2,'App\\Models\\User',2),(2,'App\\Models\\User',11),(2,'App\\Models\\User',12),(3,'App\\Models\\User',3),(3,'App\\Models\\User',5),(3,'App\\Models\\User',13),(3,'App\\Models\\User',14),(3,'App\\Models\\User',15),(4,'App\\Models\\User',6);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_rules`
--

DROP TABLE IF EXISTS `notification_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `event_type` varchar(80) NOT NULL,
  `channel` varchar(50) DEFAULT 'email',
  `template` text NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notification_rule_event` (`event_type`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_rules`
--

LOCK TABLES `notification_rules` WRITE;
/*!40000 ALTER TABLE `notification_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `notification_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `rule_id` bigint unsigned DEFAULT NULL,
  `event_type` varchar(80) NOT NULL,
  `entity_type` varchar(80) DEFAULT NULL,
  `entity_id` bigint unsigned DEFAULT NULL,
  `payload` json DEFAULT NULL,
  `status` varchar(30) DEFAULT 'queued',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notification_rule` (`rule_id`),
  KEY `idx_notification_event` (`event_type`),
  CONSTRAINT `fk_notifications_rule_id` FOREIGN KEY (`rule_id`) REFERENCES `notification_rules` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `opportunities`
--

DROP TABLE IF EXISTS `opportunities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `opportunities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `stage` varchar(50) DEFAULT 'qualification',
  `probability` int DEFAULT '10',
  `expected_value` decimal(14,2) DEFAULT '0.00',
  `closing_date` date DEFAULT NULL,
  `status` varchar(30) DEFAULT 'open',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `amount` decimal(14,2) DEFAULT '0.00',
  `close_date` date DEFAULT NULL,
  `assigned_to` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_opportunities_customer_id` (`customer_id`),
  KEY `fk_opportunities_lead_id` (`lead_id`),
  CONSTRAINT `fk_opportunities_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_opportunities_lead_id` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `opportunities`
--

LOCK TABLES `opportunities` WRITE;
/*!40000 ALTER TABLE `opportunities` DISABLE KEYS */;
/*!40000 ALTER TABLE `opportunities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `opportunity_items`
--

DROP TABLE IF EXISTS `opportunity_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `opportunity_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `opportunity_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `quantity` decimal(14,2) DEFAULT '1.00',
  `price` decimal(14,2) DEFAULT '0.00',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_opp_items_opp` (`opportunity_id`),
  KEY `fk_opportunity_items_product_id` (`product_id`),
  CONSTRAINT `fk_opportunity_items_opportunity_id` FOREIGN KEY (`opportunity_id`) REFERENCES `opportunities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_opportunity_items_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `opportunity_items`
--

LOCK TABLES `opportunity_items` WRITE;
/*!40000 ALTER TABLE `opportunity_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `opportunity_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_approval_rules`
--

DROP TABLE IF EXISTS `order_approval_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_approval_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `condition_type` varchar(50) DEFAULT 'amount',
  `threshold_amount` decimal(14,2) DEFAULT '0.00',
  `customer_id` bigint unsigned DEFAULT NULL,
  `custom_condition` text,
  `approver_ids` text NOT NULL,
  `priority` int DEFAULT '100',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rule_condition` (`condition_type`,`customer_id`),
  KEY `idx_rule_priority` (`priority`),
  KEY `fk_order_approval_rules_customer_id` (`customer_id`),
  CONSTRAINT `fk_order_approval_rules_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_approval_rules`
--

LOCK TABLES `order_approval_rules` WRITE;
/*!40000 ALTER TABLE `order_approval_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_approval_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `variant_id` bigint unsigned DEFAULT NULL,
  `batch_id` bigint unsigned DEFAULT NULL,
  `serial_numbers` text,
  `quantity` decimal(14,3) DEFAULT '0.000',
  `base_price` decimal(14,2) DEFAULT '0.00',
  `final_price` decimal(14,2) DEFAULT '0.00',
  `price_list_id` bigint unsigned DEFAULT NULL,
  `price_list_name` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_order_items_order_id` (`order_id`),
  KEY `fk_order_items_product_id` (`product_id`),
  KEY `fk_order_items_variant_id` (`variant_id`),
  KEY `fk_order_items_price_list_id` (`price_list_id`),
  KEY `fk_order_items_batch_id` (`batch_id`),
  CONSTRAINT `fk_order_items_batch_id` FOREIGN KEY (`batch_id`) REFERENCES `product_batches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_order_items_order_id` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_order_items_price_list_id` FOREIGN KEY (`price_list_id`) REFERENCES `price_lists` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_order_items_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_order_items_variant_id` FOREIGN KEY (`variant_id`) REFERENCES `product_variants_v2` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=471 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (384,211,501,50101,NULL,NULL,1.000,212000.00,212000.00,NULL,NULL,'2025-11-06 10:00:00','2025-11-06 10:00:00',NULL),(385,211,502,NULL,NULL,NULL,1.000,204000.00,204000.00,NULL,NULL,'2025-11-06 10:00:00','2025-11-06 10:00:00',NULL),(386,212,503,NULL,NULL,NULL,2.000,206000.00,206000.00,NULL,NULL,'2025-11-07 10:00:00','2025-11-07 10:00:00',NULL),(387,213,501,50101,NULL,NULL,1.000,212000.00,169600.00,1,'VIP 20%','2025-11-08 10:00:00','2025-11-08 10:00:00',NULL),(388,213,503,NULL,NULL,NULL,1.000,206000.00,206000.00,NULL,NULL,'2025-11-08 10:00:00','2025-11-08 10:00:00',NULL),(389,214,502,NULL,NULL,NULL,2.000,204000.00,204000.00,NULL,NULL,'2025-11-09 10:00:00','2025-11-09 10:00:00',NULL),(390,215,501,50102,NULL,NULL,1.000,222000.00,155400.00,3,'Flash Sale 30%','2025-11-10 10:00:00','2025-11-10 10:00:00',NULL),(391,215,502,NULL,NULL,NULL,1.000,204000.00,204000.00,NULL,NULL,'2025-11-10 10:00:00','2025-11-10 10:00:00',NULL),(392,216,501,50101,NULL,NULL,1.000,212000.00,212000.00,NULL,NULL,'2025-11-11 10:00:00','2025-11-11 10:00:00',NULL),(393,216,503,NULL,NULL,NULL,1.000,206000.00,206000.00,NULL,NULL,'2025-11-11 10:00:00','2025-11-11 10:00:00',NULL),(394,217,502,NULL,NULL,NULL,1.000,204000.00,204000.00,NULL,NULL,'2025-11-12 10:00:00','2025-11-12 10:00:00',NULL),(395,217,503,NULL,NULL,NULL,1.000,206000.00,206000.00,NULL,NULL,'2025-11-12 10:00:00','2025-11-12 10:00:00',NULL),(396,218,501,50101,NULL,NULL,1.000,212000.00,169600.00,1,'VIP 20%','2025-11-13 10:00:00','2025-11-13 10:00:00',NULL),(397,218,502,NULL,NULL,NULL,1.000,204000.00,204000.00,NULL,NULL,'2025-11-13 10:00:00','2025-11-13 10:00:00',NULL),(398,219,501,50101,NULL,NULL,1.000,212000.00,212000.00,NULL,NULL,'2025-11-14 10:00:00','2025-11-14 10:00:00',NULL),(399,219,502,NULL,NULL,NULL,1.000,204000.00,204000.00,NULL,NULL,'2025-11-14 10:00:00','2025-11-14 10:00:00',NULL),(400,220,503,NULL,NULL,NULL,2.000,206000.00,206000.00,NULL,NULL,'2025-11-15 10:00:00','2025-11-15 10:00:00',NULL),(401,220,501,50101,NULL,NULL,1.000,212000.00,169600.00,1,'VIP 20%','2025-11-15 10:00:00','2025-11-15 10:00:00',NULL),(402,221,502,NULL,NULL,NULL,1.000,204000.00,204000.00,NULL,NULL,'2025-11-16 10:00:00','2025-11-16 10:00:00',NULL),(403,221,501,50102,NULL,NULL,1.000,222000.00,155400.00,3,'Flash Sale 30%','2025-11-16 10:00:00','2025-11-16 10:00:00',NULL),(404,222,503,NULL,NULL,NULL,1.000,206000.00,206000.00,NULL,NULL,'2025-11-17 10:00:00','2025-11-17 10:00:00',NULL),(405,222,501,50101,NULL,NULL,1.000,212000.00,212000.00,NULL,NULL,'2025-11-17 10:00:00','2025-11-17 10:00:00',NULL),(406,223,502,NULL,NULL,NULL,1.000,204000.00,204000.00,NULL,NULL,'2025-11-18 10:00:00','2025-11-18 10:00:00',NULL),(407,223,503,NULL,NULL,NULL,1.000,206000.00,206000.00,NULL,NULL,'2025-11-18 10:00:00','2025-11-18 10:00:00',NULL),(408,223,501,50101,NULL,NULL,1.000,212000.00,169600.00,1,'VIP 20%','2025-11-18 10:00:00','2025-11-18 10:00:00',NULL),(409,224,502,NULL,NULL,NULL,2.000,204000.00,204000.00,NULL,NULL,'2025-11-19 10:00:00','2025-11-19 10:00:00',NULL),(410,225,501,50101,NULL,NULL,1.000,212000.00,212000.00,NULL,NULL,'2025-11-20 10:00:00','2025-11-20 10:00:00',NULL),(411,225,503,NULL,NULL,NULL,1.000,206000.00,206000.00,NULL,NULL,'2025-11-20 10:00:00','2025-11-20 10:00:00',NULL),(412,226,502,NULL,NULL,NULL,1.000,204000.00,204000.00,NULL,NULL,'2025-11-21 10:00:00','2025-11-21 10:00:00',NULL),(413,226,501,50101,NULL,NULL,1.000,212000.00,212000.00,NULL,NULL,'2025-11-21 10:00:00','2025-11-21 10:00:00',NULL),(414,227,503,NULL,NULL,NULL,2.000,206000.00,206000.00,NULL,NULL,'2025-11-22 10:00:00','2025-11-22 10:00:00',NULL),(415,228,501,50101,NULL,NULL,1.000,212000.00,169600.00,1,'VIP 20%','2025-11-23 10:00:00','2025-11-23 10:00:00',NULL),(416,228,502,NULL,NULL,NULL,1.000,204000.00,204000.00,NULL,NULL,'2025-11-23 10:00:00','2025-11-23 10:00:00',NULL),(417,229,502,NULL,NULL,NULL,1.000,204000.00,204000.00,NULL,NULL,'2025-11-24 10:00:00','2025-11-24 10:00:00',NULL),(418,230,503,NULL,NULL,NULL,1.000,206000.00,206000.00,NULL,NULL,'2025-11-25 10:00:00','2025-11-25 10:00:00',NULL),(419,231,513,NULL,NULL,NULL,2.000,226000.00,226000.00,NULL,NULL,'2025-11-26 10:00:00','2025-11-26 10:00:00',NULL),(420,232,512,NULL,NULL,NULL,3.000,224000.00,224000.00,NULL,NULL,'2025-11-27 10:00:00','2025-11-27 10:00:00',NULL),(421,232,519,NULL,NULL,NULL,2.000,238000.00,238000.00,NULL,NULL,'2025-11-27 10:00:00','2025-11-27 10:00:00',NULL),(422,232,512,NULL,NULL,NULL,3.000,224000.00,224000.00,NULL,NULL,'2025-11-27 10:00:00','2025-11-27 10:00:00',NULL),(423,233,512,51202,NULL,NULL,4.000,244000.00,244000.00,NULL,NULL,'2025-11-28 10:00:00','2025-11-28 10:00:00',NULL),(424,233,508,50802,NULL,NULL,3.000,236000.00,236000.00,NULL,NULL,'2025-11-28 10:00:00','2025-11-28 10:00:00',NULL),(425,233,504,NULL,NULL,NULL,4.000,208000.00,208000.00,NULL,NULL,'2025-11-28 10:00:00','2025-11-28 10:00:00',NULL),(426,234,507,NULL,NULL,NULL,2.000,214000.00,214000.00,NULL,NULL,'2025-11-29 10:00:00','2025-11-29 10:00:00',NULL),(427,234,514,51401,NULL,NULL,4.000,238000.00,238000.00,NULL,NULL,'2025-11-29 10:00:00','2025-11-29 10:00:00',NULL),(428,234,506,50601,NULL,NULL,5.000,222000.00,222000.00,NULL,NULL,'2025-11-29 10:00:00','2025-11-29 10:00:00',NULL),(429,235,528,52802,NULL,NULL,2.000,276000.00,276000.00,NULL,NULL,'2025-11-30 10:00:00','2025-11-30 10:00:00',NULL),(430,236,504,NULL,NULL,NULL,4.000,208000.00,208000.00,NULL,NULL,'2025-12-01 10:00:00','2025-12-01 10:00:00',NULL),(431,236,508,50802,NULL,NULL,4.000,236000.00,236000.00,NULL,NULL,'2025-12-01 10:00:00','2025-12-01 10:00:00',NULL),(432,237,511,NULL,NULL,NULL,2.000,222000.00,222000.00,NULL,NULL,'2025-12-02 10:00:00','2025-12-02 10:00:00',NULL),(433,237,530,53001,NULL,NULL,2.000,270000.00,270000.00,NULL,NULL,'2025-12-02 10:00:00','2025-12-02 10:00:00',NULL),(434,237,519,51901,NULL,NULL,1.000,248000.00,248000.00,NULL,NULL,'2025-12-02 10:00:00','2025-12-02 10:00:00',NULL),(435,238,527,NULL,NULL,NULL,2.000,254000.00,254000.00,NULL,NULL,'2025-12-03 10:00:00','2025-12-03 10:00:00',NULL),(436,238,503,50301,NULL,NULL,4.000,216000.00,216000.00,NULL,NULL,'2025-12-03 10:00:00','2025-12-03 10:00:00',NULL),(437,238,511,51101,NULL,NULL,2.000,232000.00,232000.00,NULL,NULL,'2025-12-03 10:00:00','2025-12-03 10:00:00',NULL),(438,239,512,51201,NULL,NULL,1.000,234000.00,234000.00,NULL,NULL,'2025-12-04 10:00:00','2025-12-04 10:00:00',NULL),(439,240,523,52301,NULL,NULL,5.000,256000.00,256000.00,NULL,NULL,'2025-12-05 10:00:00','2025-12-05 10:00:00',NULL),(440,241,504,50402,NULL,NULL,2.000,228000.00,228000.00,NULL,NULL,'2025-12-06 10:00:00','2025-12-06 10:00:00',NULL),(441,241,507,50701,NULL,NULL,2.000,224000.00,224000.00,NULL,NULL,'2025-12-06 10:00:00','2025-12-06 10:00:00',NULL),(442,241,519,51901,NULL,NULL,5.000,248000.00,248000.00,NULL,NULL,'2025-12-06 10:00:00','2025-12-06 10:00:00',NULL),(443,242,514,NULL,NULL,NULL,3.000,228000.00,228000.00,NULL,NULL,'2025-12-07 10:00:00','2025-12-07 10:00:00',NULL),(444,242,510,NULL,NULL,NULL,1.000,220000.00,220000.00,NULL,NULL,'2025-12-07 10:00:00','2025-12-07 10:00:00',NULL),(445,243,509,50901,NULL,NULL,5.000,228000.00,228000.00,NULL,NULL,'2025-12-08 10:00:00','2025-12-08 10:00:00',NULL),(446,244,504,NULL,NULL,NULL,2.000,208000.00,208000.00,NULL,NULL,'2025-12-09 10:00:00','2025-12-09 10:00:00',NULL),(447,244,518,NULL,NULL,NULL,5.000,236000.00,236000.00,NULL,NULL,'2025-12-09 10:00:00','2025-12-09 10:00:00',NULL),(448,245,530,53001,NULL,NULL,3.000,270000.00,270000.00,NULL,NULL,'2025-12-10 10:00:00','2025-12-10 10:00:00',NULL),(449,245,515,51502,NULL,NULL,4.000,250000.00,250000.00,NULL,NULL,'2025-12-10 10:00:00','2025-12-10 10:00:00',NULL),(450,245,502,50202,NULL,NULL,1.000,224000.00,224000.00,NULL,NULL,'2025-12-10 10:00:00','2025-12-10 10:00:00',NULL),(451,246,513,NULL,NULL,NULL,1.000,688000.00,226000.00,1,'Bảng giá chung','2025-12-06 12:42:32','2025-12-06 12:42:32',NULL),(452,247,511,NULL,NULL,NULL,1.000,1044000.00,222000.00,1,'Bảng giá chung','2025-12-06 12:50:53','2025-12-06 12:50:53',NULL),(453,248,511,NULL,NULL,NULL,1.000,1044000.00,222000.00,1,'Bảng giá chung','2025-12-06 12:54:27','2025-12-06 12:54:27',NULL),(454,249,511,NULL,NULL,NULL,1.000,1044000.00,222000.00,1,'Bảng giá chung','2025-12-06 13:19:08','2025-12-06 13:19:08',NULL),(455,250,511,NULL,NULL,NULL,1.000,1044000.00,222000.00,1,'Bảng giá chung','2025-12-06 13:21:42','2025-12-06 13:21:42',NULL),(456,251,515,NULL,NULL,NULL,1.000,325000.00,812000.00,1,'Bảng giá chung','2025-12-07 11:45:48','2025-12-07 11:45:48',NULL),(458,253,515,NULL,NULL,NULL,1.000,325000.00,812000.00,1,'Bảng giá chung','2025-12-07 19:34:42','2025-12-07 19:34:42',NULL),(459,254,515,NULL,NULL,NULL,2.000,325000.00,812000.00,1,'Bảng giá chung','2025-12-07 19:41:24','2025-12-07 19:41:24',NULL),(460,255,515,NULL,NULL,NULL,1.000,325000.00,812000.00,1,'Bảng giá chung','2025-12-07 19:41:37','2025-12-07 19:41:37',NULL),(461,256,515,NULL,NULL,NULL,1.000,325000.00,812000.00,1,'Bảng giá chung','2025-12-08 01:20:08','2025-12-08 01:20:08',NULL),(464,259,515,NULL,NULL,NULL,1.000,325000.00,812000.00,1,'Bảng giá chung','2025-12-08 08:27:39','2025-12-08 08:27:39',NULL),(466,261,515,NULL,NULL,NULL,1.000,325000.00,812000.00,1,'Bảng giá chung','2025-12-08 09:28:33','2025-12-08 09:28:33',NULL),(467,262,513,NULL,NULL,NULL,1.000,603000.00,331000.00,1,'Bảng giá chung','2025-12-08 09:38:07','2025-12-08 09:38:07',NULL),(468,263,502,NULL,NULL,NULL,1.000,818000.00,1196000.00,1,'Bảng giá chung','2025-12-08 09:51:09','2025-12-08 09:51:09',NULL),(469,264,506,NULL,NULL,NULL,1.000,706000.00,1393000.00,1,'Bảng giá chung','2025-12-08 10:00:21','2025-12-08 10:00:21',NULL),(470,265,502,NULL,NULL,NULL,1.000,818000.00,1196000.00,1,'Bảng giá chung','2025-12-08 10:15:12','2025-12-08 10:15:12',NULL);
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_payments`
--

DROP TABLE IF EXISTS `order_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `payment_method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `paid_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_order_payments_order` (`order_id`),
  KEY `fk_order_payments_method` (`payment_method`),
  CONSTRAINT `fk_order_payments_method` FOREIGN KEY (`payment_method`) REFERENCES `payment_methods` (`code`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_order_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=263 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_payments`
--

LOCK TABLES `order_payments` WRITE;
/*!40000 ALTER TABLE `order_payments` DISABLE KEYS */;
INSERT INTO `order_payments` VALUES (211,211,'CASH',0.00,NULL,'2025-11-06 12:00:00','2025-11-06 12:00:00'),(212,212,'BANK_TRANSFER',166110.00,'2025-11-07 12:00:00','2025-11-07 12:00:00','2025-11-07 12:00:00'),(213,213,'COD',180664.00,'2025-11-08 12:00:00','2025-11-08 12:00:00','2025-11-08 12:00:00'),(214,214,'CASH',204000.00,'2025-11-09 12:00:00','2025-11-09 12:00:00','2025-11-09 12:00:00'),(215,215,'EWALLET',84924.00,'2025-11-10 12:00:00','2025-11-10 12:00:00','2025-11-10 12:00:00'),(216,216,'COD',292380.00,'2025-11-11 12:00:00','2025-11-11 12:00:00','2025-11-11 12:00:00'),(217,217,'BANK_TRANSFER',215000.00,'2025-11-12 12:00:00','2025-11-12 12:00:00','2025-11-12 12:00:00'),(218,218,'CASH',274596.00,'2025-11-13 12:00:00','2025-11-13 12:00:00','2025-11-13 12:00:00'),(219,219,'BANK_TRANSFER',457600.00,'2025-11-14 12:00:00','2025-11-14 12:00:00','2025-11-14 12:00:00'),(220,220,'BANK_TRANSFER',611600.00,'2025-11-15 12:00:00','2025-11-15 12:00:00','2025-11-15 12:00:00'),(221,221,'CASH',398370.00,'2025-11-16 12:00:00','2025-11-16 12:00:00','2025-11-16 12:00:00'),(222,222,'COD',459800.00,'2025-11-17 12:00:00','2025-11-17 12:00:00','2025-11-17 12:00:00'),(223,223,'BANK_TRANSFER',594600.00,'2025-11-18 12:00:00','2025-11-18 12:00:00','2025-11-18 12:00:00'),(224,224,'CASH',428400.00,'2025-11-19 12:00:00','2025-11-19 12:00:00','2025-11-19 12:00:00'),(225,225,'EWALLET',487300.00,'2025-11-20 12:00:00','2025-11-20 12:00:00','2025-11-20 12:00:00'),(226,226,'CASH',416000.00,'2025-11-21 12:00:00','2025-11-21 12:00:00','2025-11-21 12:00:00'),(227,227,'BANK_TRANSFER',474600.00,'2025-11-22 12:00:00','2025-11-22 12:00:00','2025-11-22 12:00:00'),(228,228,'CASH',410960.00,'2025-11-23 12:00:00','2025-11-23 12:00:00','2025-11-23 12:00:00'),(229,229,'CASH',0.00,NULL,'2025-11-24 12:00:00','2025-11-24 12:00:00'),(230,230,'COD',35595.00,'2025-11-25 12:00:00','2025-11-25 12:00:00','2025-11-25 12:00:00'),(231,231,'CASH',270600.00,'2025-11-26 12:00:00','2025-11-26 12:00:00','2025-11-26 12:00:00'),(232,232,'COD',0.00,NULL,'2025-11-27 12:00:00','2025-11-27 12:00:00'),(233,233,'EWALLET',0.00,NULL,'2025-11-28 12:00:00','2025-11-28 12:00:00'),(234,234,'EWALLET',0.00,NULL,'2025-11-29 12:00:00','2025-11-29 12:00:00'),(235,235,'COD',562000.00,'2025-11-30 12:00:00','2025-11-30 12:00:00','2025-11-30 12:00:00'),(236,236,'CASH',0.00,NULL,'2025-12-01 12:00:00','2025-12-01 12:00:00'),(237,237,'COD',0.00,NULL,'2025-12-02 12:00:00','2025-12-02 12:00:00'),(238,238,'BANK_TRANSFER',933000.00,'2025-12-03 12:00:00','2025-12-03 12:00:00','2025-12-03 12:00:00'),(239,239,'BANK_TRANSFER',0.00,NULL,'2025-12-04 12:00:00','2025-12-04 12:00:00'),(240,240,'BANK_TRANSFER',0.00,NULL,'2025-12-05 12:00:00','2025-12-05 12:00:00'),(241,241,'CASH',2154000.00,'2025-12-06 12:00:00','2025-12-06 12:00:00','2025-12-06 12:00:00'),(242,242,'COD',991200.00,'2025-12-07 12:00:00','2025-12-07 12:00:00','2025-12-07 12:00:00'),(243,243,'CASH',1276000.00,'2025-12-08 12:00:00','2025-12-08 12:00:00','2025-12-08 12:00:00'),(244,244,'CASH',1596000.00,'2025-12-09 12:00:00','2025-12-09 12:00:00','2025-12-09 12:00:00'),(245,245,'BANK_TRANSFER',2188200.00,'2025-12-10 12:00:00','2025-12-10 12:00:00','2025-12-10 12:00:00'),(246,246,'CASH',226000.00,NULL,'2025-12-06 12:42:32','2025-12-06 12:42:32'),(247,247,'CASH',222000.00,NULL,'2025-12-06 12:50:53','2025-12-06 12:50:53'),(248,248,'CASH',222000.00,NULL,'2025-12-06 12:54:27','2025-12-06 12:54:27'),(249,249,'CASH',222000.00,NULL,'2025-12-06 13:19:08','2025-12-06 13:19:08'),(250,250,'CASH',222000.00,NULL,'2025-12-06 13:21:42','2025-12-06 13:21:42'),(251,251,'CASH',812000.00,NULL,'2025-12-07 11:45:48','2025-12-07 11:45:48'),(252,253,'CASH',812000.00,NULL,'2025-12-07 19:34:42','2025-12-07 19:34:42'),(253,254,'CASH',1624000.00,NULL,'2025-12-07 19:41:24','2025-12-07 19:41:24'),(254,255,'CASH',812000.00,NULL,'2025-12-07 19:41:37','2025-12-07 19:41:37'),(255,256,'CASH',812000.00,NULL,'2025-12-08 01:20:08','2025-12-08 01:20:08'),(256,259,'CASH',812000.00,NULL,'2025-12-08 08:27:39','2025-12-08 08:27:39'),(258,261,'CASH',812000.00,NULL,'2025-12-08 09:28:33','2025-12-08 09:28:33'),(259,262,'CASH',331000.00,NULL,'2025-12-08 09:38:07','2025-12-08 09:38:07'),(260,263,'CASH',1196000.00,NULL,'2025-12-08 09:51:09','2025-12-08 09:51:09'),(261,264,'CASH',1393000.00,NULL,'2025-12-08 10:00:21','2025-12-08 10:00:21'),(262,265,'CASH',1196000.00,NULL,'2025-12-08 10:15:12','2025-12-08 10:15:12');
/*!40000 ALTER TABLE `order_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_sequences`
--

DROP TABLE IF EXISTS `order_sequences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_sequences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned DEFAULT NULL,
  `sequence_number` int DEFAULT '1',
  `prefix` varchar(20) DEFAULT 'ORD',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_order_sequences_branch_id` (`branch_id`),
  CONSTRAINT `fk_order_sequences_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_sequences`
--

LOCK TABLES `order_sequences` WRITE;
/*!40000 ALTER TABLE `order_sequences` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_sequences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_status_logs`
--

DROP TABLE IF EXISTS `order_status_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_status_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `from_status` varchar(50) DEFAULT NULL,
  `to_status` varchar(50) NOT NULL,
  `changed_by` int DEFAULT NULL,
  `notes` text,
  `changed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_order_status_logs_order_id` (`order_id`),
  CONSTRAINT `fk_order_status_logs_order_id` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1043 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_status_logs`
--

LOCK TABLES `order_status_logs` WRITE;
/*!40000 ALTER TABLE `order_status_logs` DISABLE KEYS */;
INSERT INTO `order_status_logs` VALUES (888,211,NULL,'created',1,'Auto demo log','2025-11-06 10:00:00','2025-11-06 10:00:00','2025-11-06 10:00:00'),(889,212,NULL,'created',1,'Auto demo log','2025-11-07 10:00:00','2025-11-07 10:00:00','2025-11-07 10:00:00'),(890,213,NULL,'created',1,'Auto demo log','2025-11-08 10:00:00','2025-11-08 10:00:00','2025-11-08 10:00:00'),(891,213,'created','confirmed',1,'Auto demo log','2025-11-08 11:00:00','2025-11-08 11:00:00','2025-11-08 11:00:00'),(892,213,'confirmed','processing',1,'Auto demo log','2025-11-08 12:00:00','2025-11-08 12:00:00','2025-11-08 12:00:00'),(893,214,NULL,'created',1,'Auto demo log','2025-11-09 10:00:00','2025-11-09 10:00:00','2025-11-09 10:00:00'),(894,214,'created','confirmed',1,'Auto demo log','2025-11-09 11:00:00','2025-11-09 11:00:00','2025-11-09 11:00:00'),(895,214,'confirmed','processing',1,'Auto demo log','2025-11-09 12:00:00','2025-11-09 12:00:00','2025-11-09 12:00:00'),(896,215,NULL,'created',1,'Auto demo log','2025-11-10 10:00:00','2025-11-10 10:00:00','2025-11-10 10:00:00'),(897,215,'created','confirmed',1,'Auto demo log','2025-11-10 11:00:00','2025-11-10 11:00:00','2025-11-10 11:00:00'),(898,215,'confirmed','processing',1,'Auto demo log','2025-11-10 12:00:00','2025-11-10 12:00:00','2025-11-10 12:00:00'),(899,216,NULL,'created',1,'Auto demo log','2025-11-11 10:00:00','2025-11-11 10:00:00','2025-11-11 10:00:00'),(900,216,'created','confirmed',1,'Auto demo log','2025-11-11 11:00:00','2025-11-11 11:00:00','2025-11-11 11:00:00'),(901,216,'confirmed','processing',1,'Auto demo log','2025-11-11 12:00:00','2025-11-11 12:00:00','2025-11-11 12:00:00'),(902,216,'processing','shipping',1,'Auto demo log','2025-11-11 13:00:00','2025-11-11 13:00:00','2025-11-11 13:00:00'),(903,217,NULL,'created',1,'Auto demo log','2025-11-12 10:00:00','2025-11-12 10:00:00','2025-11-12 10:00:00'),(904,217,'created','confirmed',1,'Auto demo log','2025-11-12 11:00:00','2025-11-12 11:00:00','2025-11-12 11:00:00'),(905,217,'confirmed','processing',1,'Auto demo log','2025-11-12 12:00:00','2025-11-12 12:00:00','2025-11-12 12:00:00'),(906,217,'processing','shipping',1,'Auto demo log','2025-11-12 13:00:00','2025-11-12 13:00:00','2025-11-12 13:00:00'),(907,218,NULL,'created',1,'Auto demo log','2025-11-13 10:00:00','2025-11-13 10:00:00','2025-11-13 10:00:00'),(908,218,'created','confirmed',1,'Auto demo log','2025-11-13 11:00:00','2025-11-13 11:00:00','2025-11-13 11:00:00'),(909,218,'confirmed','processing',1,'Auto demo log','2025-11-13 12:00:00','2025-11-13 12:00:00','2025-11-13 12:00:00'),(910,218,'processing','shipping',1,'Auto demo log','2025-11-13 13:00:00','2025-11-13 13:00:00','2025-11-13 13:00:00'),(911,219,NULL,'created',1,'Auto demo log','2025-11-14 10:00:00','2025-11-14 10:00:00','2025-11-14 10:00:00'),(912,219,'created','confirmed',1,'Auto demo log','2025-11-14 11:00:00','2025-11-14 11:00:00','2025-11-14 11:00:00'),(913,219,'confirmed','processing',1,'Auto demo log','2025-11-14 12:00:00','2025-11-14 12:00:00','2025-11-14 12:00:00'),(914,219,'processing','shipping',1,'Auto demo log','2025-11-14 13:00:00','2025-11-14 13:00:00','2025-11-14 13:00:00'),(915,219,'shipping','delivered',1,'Auto demo log','2025-11-14 14:00:00','2025-11-14 14:00:00','2025-11-14 14:00:00'),(916,219,'delivered','completed',1,'Auto demo log','2025-11-14 15:00:00','2025-11-14 15:00:00','2025-11-14 15:00:00'),(917,220,NULL,'created',1,'Auto demo log','2025-11-15 10:00:00','2025-11-15 10:00:00','2025-11-15 10:00:00'),(918,220,'created','confirmed',1,'Auto demo log','2025-11-15 11:00:00','2025-11-15 11:00:00','2025-11-15 11:00:00'),(919,220,'confirmed','processing',1,'Auto demo log','2025-11-15 12:00:00','2025-11-15 12:00:00','2025-11-15 12:00:00'),(920,220,'processing','shipping',1,'Auto demo log','2025-11-15 13:00:00','2025-11-15 13:00:00','2025-11-15 13:00:00'),(921,220,'shipping','delivered',1,'Auto demo log','2025-11-15 14:00:00','2025-11-15 14:00:00','2025-11-15 14:00:00'),(922,220,'delivered','completed',1,'Auto demo log','2025-11-15 15:00:00','2025-11-15 15:00:00','2025-11-15 15:00:00'),(923,221,NULL,'created',1,'Auto demo log','2025-11-16 10:00:00','2025-11-16 10:00:00','2025-11-16 10:00:00'),(924,221,'created','confirmed',1,'Auto demo log','2025-11-16 11:00:00','2025-11-16 11:00:00','2025-11-16 11:00:00'),(925,221,'confirmed','processing',1,'Auto demo log','2025-11-16 12:00:00','2025-11-16 12:00:00','2025-11-16 12:00:00'),(926,221,'processing','shipping',1,'Auto demo log','2025-11-16 13:00:00','2025-11-16 13:00:00','2025-11-16 13:00:00'),(927,221,'shipping','delivered',1,'Auto demo log','2025-11-16 14:00:00','2025-11-16 14:00:00','2025-11-16 14:00:00'),(928,221,'delivered','completed',1,'Auto demo log','2025-11-16 15:00:00','2025-11-16 15:00:00','2025-11-16 15:00:00'),(929,222,NULL,'created',1,'Auto demo log','2025-11-17 10:00:00','2025-11-17 10:00:00','2025-11-17 10:00:00'),(930,222,'created','confirmed',1,'Auto demo log','2025-11-17 11:00:00','2025-11-17 11:00:00','2025-11-17 11:00:00'),(931,222,'confirmed','processing',1,'Auto demo log','2025-11-17 12:00:00','2025-11-17 12:00:00','2025-11-17 12:00:00'),(932,222,'processing','shipping',1,'Auto demo log','2025-11-17 13:00:00','2025-11-17 13:00:00','2025-11-17 13:00:00'),(933,222,'shipping','delivered',1,'Auto demo log','2025-11-17 14:00:00','2025-11-17 14:00:00','2025-11-17 14:00:00'),(934,222,'delivered','completed',1,'Auto demo log','2025-11-17 15:00:00','2025-11-17 15:00:00','2025-11-17 15:00:00'),(935,223,NULL,'created',1,'Auto demo log','2025-11-18 10:00:00','2025-11-18 10:00:00','2025-11-18 10:00:00'),(936,223,'created','confirmed',1,'Auto demo log','2025-11-18 11:00:00','2025-11-18 11:00:00','2025-11-18 11:00:00'),(937,223,'confirmed','processing',1,'Auto demo log','2025-11-18 12:00:00','2025-11-18 12:00:00','2025-11-18 12:00:00'),(938,223,'processing','shipping',1,'Auto demo log','2025-11-18 13:00:00','2025-11-18 13:00:00','2025-11-18 13:00:00'),(939,223,'shipping','delivered',1,'Auto demo log','2025-11-18 14:00:00','2025-11-18 14:00:00','2025-11-18 14:00:00'),(940,223,'delivered','completed',1,'Auto demo log','2025-11-18 15:00:00','2025-11-18 15:00:00','2025-11-18 15:00:00'),(941,224,NULL,'created',1,'Auto demo log','2025-11-19 10:00:00','2025-11-19 10:00:00','2025-11-19 10:00:00'),(942,224,'created','confirmed',1,'Auto demo log','2025-11-19 11:00:00','2025-11-19 11:00:00','2025-11-19 11:00:00'),(943,224,'confirmed','processing',1,'Auto demo log','2025-11-19 12:00:00','2025-11-19 12:00:00','2025-11-19 12:00:00'),(944,224,'processing','shipping',1,'Auto demo log','2025-11-19 13:00:00','2025-11-19 13:00:00','2025-11-19 13:00:00'),(945,224,'shipping','delivered',1,'Auto demo log','2025-11-19 14:00:00','2025-11-19 14:00:00','2025-11-19 14:00:00'),(946,224,'delivered','completed',1,'Auto demo log','2025-11-19 15:00:00','2025-11-19 15:00:00','2025-11-19 15:00:00'),(947,225,NULL,'created',1,'Auto demo log','2025-11-20 10:00:00','2025-11-20 10:00:00','2025-11-20 10:00:00'),(948,225,'created','confirmed',1,'Auto demo log','2025-11-20 11:00:00','2025-11-20 11:00:00','2025-11-20 11:00:00'),(949,225,'confirmed','processing',1,'Auto demo log','2025-11-20 12:00:00','2025-11-20 12:00:00','2025-11-20 12:00:00'),(950,225,'processing','shipping',1,'Auto demo log','2025-11-20 13:00:00','2025-11-20 13:00:00','2025-11-20 13:00:00'),(951,225,'shipping','delivered',1,'Auto demo log','2025-11-20 14:00:00','2025-11-20 14:00:00','2025-11-20 14:00:00'),(952,225,'delivered','completed',1,'Auto demo log','2025-11-20 15:00:00','2025-11-20 15:00:00','2025-11-20 15:00:00'),(953,226,NULL,'created',1,'Auto demo log','2025-11-21 10:00:00','2025-11-21 10:00:00','2025-11-21 10:00:00'),(954,226,'created','confirmed',1,'Auto demo log','2025-11-21 11:00:00','2025-11-21 11:00:00','2025-11-21 11:00:00'),(955,226,'confirmed','processing',1,'Auto demo log','2025-11-21 12:00:00','2025-11-21 12:00:00','2025-11-21 12:00:00'),(956,226,'processing','shipping',1,'Auto demo log','2025-11-21 13:00:00','2025-11-21 13:00:00','2025-11-21 13:00:00'),(957,226,'shipping','delivered',1,'Auto demo log','2025-11-21 14:00:00','2025-11-21 14:00:00','2025-11-21 14:00:00'),(958,226,'delivered','completed',1,'Auto demo log','2025-11-21 15:00:00','2025-11-21 15:00:00','2025-11-21 15:00:00'),(959,227,NULL,'created',1,'Auto demo log','2025-11-22 10:00:00','2025-11-22 10:00:00','2025-11-22 10:00:00'),(960,227,'created','confirmed',1,'Auto demo log','2025-11-22 11:00:00','2025-11-22 11:00:00','2025-11-22 11:00:00'),(961,227,'confirmed','processing',1,'Auto demo log','2025-11-22 12:00:00','2025-11-22 12:00:00','2025-11-22 12:00:00'),(962,227,'processing','shipping',1,'Auto demo log','2025-11-22 13:00:00','2025-11-22 13:00:00','2025-11-22 13:00:00'),(963,227,'shipping','delivered',1,'Auto demo log','2025-11-22 14:00:00','2025-11-22 14:00:00','2025-11-22 14:00:00'),(964,227,'delivered','completed',1,'Auto demo log','2025-11-22 15:00:00','2025-11-22 15:00:00','2025-11-22 15:00:00'),(965,228,NULL,'created',1,'Auto demo log','2025-11-23 10:00:00','2025-11-23 10:00:00','2025-11-23 10:00:00'),(966,228,'created','confirmed',1,'Auto demo log','2025-11-23 11:00:00','2025-11-23 11:00:00','2025-11-23 11:00:00'),(967,228,'confirmed','processing',1,'Auto demo log','2025-11-23 12:00:00','2025-11-23 12:00:00','2025-11-23 12:00:00'),(968,228,'processing','shipping',1,'Auto demo log','2025-11-23 13:00:00','2025-11-23 13:00:00','2025-11-23 13:00:00'),(969,228,'shipping','delivered',1,'Auto demo log','2025-11-23 14:00:00','2025-11-23 14:00:00','2025-11-23 14:00:00'),(970,228,'delivered','completed',1,'Auto demo log','2025-11-23 15:00:00','2025-11-23 15:00:00','2025-11-23 15:00:00'),(971,229,NULL,'created',1,'Auto demo log','2025-11-24 10:00:00','2025-11-24 10:00:00','2025-11-24 10:00:00'),(972,229,'created','cancelled',1,'Auto demo log','2025-11-24 11:00:00','2025-11-24 11:00:00','2025-11-24 11:00:00'),(973,230,NULL,'created',1,'Auto demo log','2025-11-25 10:00:00','2025-11-25 10:00:00','2025-11-25 10:00:00'),(974,230,'created','cancelled',1,'Auto demo log','2025-11-25 11:00:00','2025-11-25 11:00:00','2025-11-25 11:00:00'),(975,231,NULL,'created',1,'Auto demo log','2025-11-26 10:00:00','2025-11-26 10:00:00','2025-11-26 10:00:00'),(976,231,'created','confirmed',1,'Auto demo log','2025-11-26 11:00:00','2025-11-26 11:00:00','2025-11-26 11:00:00'),(977,231,'confirmed','processing',1,'Auto demo log','2025-11-26 12:00:00','2025-11-26 12:00:00','2025-11-26 12:00:00'),(978,231,'processing','shipping',1,'Auto demo log','2025-11-26 13:00:00','2025-11-26 13:00:00','2025-11-26 13:00:00'),(979,232,NULL,'created',1,'Auto demo log','2025-11-27 10:00:00','2025-11-27 10:00:00','2025-11-27 10:00:00'),(980,233,NULL,'created',1,'Auto demo log','2025-11-28 10:00:00','2025-11-28 10:00:00','2025-11-28 10:00:00'),(981,234,NULL,'created',1,'Auto demo log','2025-11-29 10:00:00','2025-11-29 10:00:00','2025-11-29 10:00:00'),(982,234,'created','cancelled',1,'Auto demo log','2025-11-29 11:00:00','2025-11-29 11:00:00','2025-11-29 11:00:00'),(983,235,NULL,'created',1,'Auto demo log','2025-11-30 10:00:00','2025-11-30 10:00:00','2025-11-30 10:00:00'),(984,235,'created','confirmed',1,'Auto demo log','2025-11-30 11:00:00','2025-11-30 11:00:00','2025-11-30 11:00:00'),(985,235,'confirmed','processing',1,'Auto demo log','2025-11-30 12:00:00','2025-11-30 12:00:00','2025-11-30 12:00:00'),(986,235,'processing','shipping',1,'Auto demo log','2025-11-30 13:00:00','2025-11-30 13:00:00','2025-11-30 13:00:00'),(987,235,'shipping','delivered',1,'Auto demo log','2025-11-30 14:00:00','2025-11-30 14:00:00','2025-11-30 14:00:00'),(988,235,'delivered','completed',1,'Auto demo log','2025-11-30 15:00:00','2025-11-30 15:00:00','2025-11-30 15:00:00'),(989,236,NULL,'created',1,'Auto demo log','2025-12-01 10:00:00','2025-12-01 10:00:00','2025-12-01 10:00:00'),(990,237,NULL,'created',1,'Auto demo log','2025-12-02 10:00:00','2025-12-02 10:00:00','2025-12-02 10:00:00'),(991,237,'created','cancelled',1,'Auto demo log','2025-12-02 11:00:00','2025-12-02 11:00:00','2025-12-02 11:00:00'),(992,238,NULL,'created',1,'Auto demo log','2025-12-03 10:00:00','2025-12-03 10:00:00','2025-12-03 10:00:00'),(993,238,'created','confirmed',1,'Auto demo log','2025-12-03 11:00:00','2025-12-03 11:00:00','2025-12-03 11:00:00'),(994,238,'confirmed','processing',1,'Auto demo log','2025-12-03 12:00:00','2025-12-03 12:00:00','2025-12-03 12:00:00'),(995,238,'processing','shipping',1,'Auto demo log','2025-12-03 13:00:00','2025-12-03 13:00:00','2025-12-03 13:00:00'),(996,239,NULL,'created',1,'Auto demo log','2025-12-04 10:00:00','2025-12-04 10:00:00','2025-12-04 10:00:00'),(997,240,NULL,'created',1,'Auto demo log','2025-12-05 10:00:00','2025-12-05 10:00:00','2025-12-05 10:00:00'),(998,241,NULL,'created',1,'Auto demo log','2025-12-06 10:00:00','2025-12-06 10:00:00','2025-12-06 10:00:00'),(999,241,'created','confirmed',1,'Auto demo log','2025-12-06 11:00:00','2025-12-06 11:00:00','2025-12-06 11:00:00'),(1000,241,'confirmed','processing',1,'Auto demo log','2025-12-06 12:00:00','2025-12-06 12:00:00','2025-12-06 12:00:00'),(1001,241,'processing','shipping',1,'Auto demo log','2025-12-06 13:00:00','2025-12-06 13:00:00','2025-12-06 13:00:00'),(1002,241,'shipping','delivered',1,'Auto demo log','2025-12-06 14:00:00','2025-12-06 14:00:00','2025-12-06 14:00:00'),(1003,241,'delivered','completed',1,'Auto demo log','2025-12-06 15:00:00','2025-12-06 15:00:00','2025-12-06 15:00:00'),(1004,242,NULL,'created',1,'Auto demo log','2025-12-07 10:00:00','2025-12-07 10:00:00','2025-12-07 10:00:00'),(1005,242,'created','confirmed',1,'Auto demo log','2025-12-07 11:00:00','2025-12-07 11:00:00','2025-12-07 11:00:00'),(1006,242,'confirmed','processing',1,'Auto demo log','2025-12-07 12:00:00','2025-12-07 12:00:00','2025-12-07 12:00:00'),(1007,242,'processing','shipping',1,'Auto demo log','2025-12-07 13:00:00','2025-12-07 13:00:00','2025-12-07 13:00:00'),(1008,242,'shipping','delivered',1,'Auto demo log','2025-12-07 14:00:00','2025-12-07 14:00:00','2025-12-07 14:00:00'),(1009,242,'delivered','completed',1,'Auto demo log','2025-12-07 15:00:00','2025-12-07 15:00:00','2025-12-07 15:00:00'),(1010,243,NULL,'created',1,'Auto demo log','2025-12-08 10:00:00','2025-12-08 10:00:00','2025-12-08 10:00:00'),(1011,243,'created','confirmed',1,'Auto demo log','2025-12-08 11:00:00','2025-12-08 11:00:00','2025-12-08 11:00:00'),(1012,243,'confirmed','processing',1,'Auto demo log','2025-12-08 12:00:00','2025-12-08 12:00:00','2025-12-08 12:00:00'),(1013,243,'processing','shipping',1,'Auto demo log','2025-12-08 13:00:00','2025-12-08 13:00:00','2025-12-08 13:00:00'),(1014,243,'shipping','delivered',1,'Auto demo log','2025-12-08 14:00:00','2025-12-08 14:00:00','2025-12-08 14:00:00'),(1015,243,'delivered','completed',1,'Auto demo log','2025-12-08 15:00:00','2025-12-08 15:00:00','2025-12-08 15:00:00'),(1016,244,NULL,'created',1,'Auto demo log','2025-12-09 10:00:00','2025-12-09 10:00:00','2025-12-09 10:00:00'),(1017,244,'created','confirmed',1,'Auto demo log','2025-12-09 11:00:00','2025-12-09 11:00:00','2025-12-09 11:00:00'),(1018,244,'confirmed','processing',1,'Auto demo log','2025-12-09 12:00:00','2025-12-09 12:00:00','2025-12-09 12:00:00'),(1019,244,'processing','shipping',1,'Auto demo log','2025-12-09 13:00:00','2025-12-09 13:00:00','2025-12-09 13:00:00'),(1020,244,'shipping','delivered',1,'Auto demo log','2025-12-09 14:00:00','2025-12-09 14:00:00','2025-12-09 14:00:00'),(1021,244,'delivered','completed',1,'Auto demo log','2025-12-09 15:00:00','2025-12-09 15:00:00','2025-12-09 15:00:00'),(1022,245,NULL,'created',1,'Auto demo log','2025-12-10 10:00:00','2025-12-10 10:00:00','2025-12-10 10:00:00'),(1023,245,'created','confirmed',1,'Auto demo log','2025-12-10 11:00:00','2025-12-10 11:00:00','2025-12-10 11:00:00'),(1024,245,'confirmed','processing',1,'Auto demo log','2025-12-10 12:00:00','2025-12-10 12:00:00','2025-12-10 12:00:00'),(1025,245,'processing','shipping',1,'Auto demo log','2025-12-10 13:00:00','2025-12-10 13:00:00','2025-12-10 13:00:00'),(1026,245,'shipping','delivered',1,'Auto demo log','2025-12-10 14:00:00','2025-12-10 14:00:00','2025-12-10 14:00:00'),(1027,245,'delivered','completed',1,'Auto demo log','2025-12-10 15:00:00','2025-12-10 15:00:00','2025-12-10 15:00:00'),(1028,246,NULL,'completed',NULL,'POS auto complete','2025-12-06 12:42:32','2025-12-06 12:42:32','2025-12-06 12:42:32'),(1029,247,NULL,'completed',NULL,'POS auto complete','2025-12-06 12:50:53','2025-12-06 12:50:53','2025-12-06 12:50:53'),(1030,248,NULL,'completed',NULL,'POS auto complete','2025-12-06 12:54:27','2025-12-06 12:54:27','2025-12-06 12:54:27'),(1031,249,NULL,'completed',NULL,'POS auto complete','2025-12-06 13:19:08','2025-12-06 13:19:08','2025-12-06 13:19:08'),(1032,250,NULL,'completed',NULL,'POS auto complete','2025-12-06 13:21:42','2025-12-06 13:21:42','2025-12-06 13:21:42'),(1033,251,NULL,'completed',NULL,'POS auto complete','2025-12-07 11:45:48','2025-12-07 11:45:48','2025-12-07 11:45:48'),(1034,253,NULL,'completed',NULL,'POS auto complete','2025-12-07 19:34:42','2025-12-07 19:34:42','2025-12-07 19:34:42'),(1035,254,NULL,'completed',NULL,'POS auto complete','2025-12-07 19:41:24','2025-12-07 19:41:24','2025-12-07 19:41:24'),(1036,255,NULL,'completed',NULL,'POS auto complete','2025-12-07 19:41:37','2025-12-07 19:41:37','2025-12-07 19:41:37'),(1037,256,NULL,'completed',NULL,'POS auto complete','2025-12-08 01:20:08','2025-12-08 01:20:08','2025-12-08 01:20:08'),(1038,259,NULL,'completed',NULL,'POS auto complete','2025-12-08 08:27:39','2025-12-08 08:27:39','2025-12-08 08:27:39'),(1039,261,NULL,'completed',NULL,'POS auto complete','2025-12-08 09:28:34','2025-12-08 09:28:34','2025-12-08 09:28:34'),(1040,262,NULL,'completed',NULL,'POS auto complete','2025-12-08 09:38:07','2025-12-08 09:38:07','2025-12-08 09:38:07'),(1041,263,NULL,'completed',NULL,'POS auto complete','2025-12-08 09:51:09','2025-12-08 09:51:09','2025-12-08 09:51:09'),(1042,264,NULL,'completed',NULL,'POS auto complete','2025-12-08 10:00:21','2025-12-08 10:00:21','2025-12-08 10:00:21');
/*!40000 ALTER TABLE `order_status_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_subscriptions`
--

DROP TABLE IF EXISTS `order_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `template_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `order_type` varchar(20) DEFAULT 'shipping',
  `next_run_at` datetime DEFAULT NULL,
  `last_run_at` datetime DEFAULT NULL,
  `frequency_interval` int DEFAULT '7',
  `status` varchar(20) DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_subscription_template` (`template_id`),
  KEY `idx_order_subscription_next` (`next_run_at`),
  KEY `idx_order_subscription_status` (`status`),
  KEY `fk_order_subscriptions_branch_id` (`branch_id`),
  CONSTRAINT `fk_order_subscriptions_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_order_subscriptions_template_id` FOREIGN KEY (`template_id`) REFERENCES `order_templates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_subscriptions`
--

LOCK TABLES `order_subscriptions` WRITE;
/*!40000 ALTER TABLE `order_subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_template_items`
--

DROP TABLE IF EXISTS `order_template_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_template_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `template_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `variant_id` bigint unsigned DEFAULT NULL,
  `quantity` decimal(12,3) DEFAULT '0.000',
  `price` decimal(14,2) DEFAULT NULL,
  `notes` text,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_template_item_template` (`template_id`),
  KEY `idx_template_item_product` (`product_id`,`variant_id`),
  KEY `fk_order_template_items_variant_id` (`variant_id`),
  CONSTRAINT `fk_order_template_items_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_order_template_items_template_id` FOREIGN KEY (`template_id`) REFERENCES `order_templates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_order_template_items_variant_id` FOREIGN KEY (`variant_id`) REFERENCES `product_variants_v2` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_template_items`
--

LOCK TABLES `order_template_items` WRITE;
/*!40000 ALTER TABLE `order_template_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_template_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_templates`
--

DROP TABLE IF EXISTS `order_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `frequency` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `notes` text,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_order_template_name` (`name`),
  KEY `idx_order_template_customer` (`customer_id`),
  KEY `idx_order_template_active` (`is_active`),
  CONSTRAINT `fk_order_templates_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_templates`
--

LOCK TABLES `order_templates` WRITE;
/*!40000 ALTER TABLE `order_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) DEFAULT NULL,
  `order_number` varchar(50) NOT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `customer_group_id` bigint unsigned DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `warehouse_id` bigint unsigned DEFAULT NULL,
  `order_date` date DEFAULT NULL,
  `order_type` varchar(50) DEFAULT 'online',
  `pos_profile_id` bigint unsigned DEFAULT NULL,
  `pos_shift_id` bigint unsigned DEFAULT NULL,
  `tax_template_id` bigint unsigned DEFAULT NULL,
  `tax_total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `rounding_adjustment` decimal(15,2) NOT NULL DEFAULT '0.00',
  `payment_method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `status` varchar(50) DEFAULT 'draft',
  `coupon_code` varchar(120) DEFAULT NULL,
  `coupon_discount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `loyalty_points_redeemed` int DEFAULT '0',
  `loyalty_discount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `loyalty_points_earned` int DEFAULT '0',
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `discount_total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `shipping_fee` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `paid_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `debt_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `payment_status` varchar(20) DEFAULT NULL,
  `is_paid` tinyint(1) DEFAULT '0',
  `applied_price_list_id` bigint unsigned DEFAULT NULL,
  `shipping_name` varchar(255) DEFAULT NULL,
  `shipping_phone` varchar(50) DEFAULT NULL,
  `shipping_address` text,
  `shipping_ward` varchar(100) DEFAULT NULL,
  `shipping_district` varchar(100) DEFAULT NULL,
  `shipping_city` varchar(100) DEFAULT NULL,
  `notes` text,
  `confirmed_at` datetime DEFAULT NULL,
  `processing_at` datetime DEFAULT NULL,
  `shipping_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancellation_reason` text,
  `cod_collected` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_orders_customer` (`customer_id`),
  KEY `fk_orders_branch` (`branch_id`),
  KEY `fk_orders_payment_method` (`payment_method`),
  KEY `fk_orders_applied_price_list_i` (`applied_price_list_id`),
  KEY `idx_customer_group_id` (`customer_group_id`),
  KEY `idx_warehouse_id` (`warehouse_id`),
  KEY `idx_pos_profile_id` (`pos_profile_id`),
  KEY `idx_pos_shift_id` (`pos_shift_id`),
  KEY `idx_tax_template_id` (`tax_template_id`),
  CONSTRAINT `fk_orders_applied_price_list_i` FOREIGN KEY (`applied_price_list_id`) REFERENCES `price_lists` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_orders_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_orders_customer_group` FOREIGN KEY (`customer_group_id`) REFERENCES `customer_groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_orders_payment_method` FOREIGN KEY (`payment_method`) REFERENCES `payment_methods` (`code`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_orders_pos_profile_id` FOREIGN KEY (`pos_profile_id`) REFERENCES `pos_profiles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_orders_pos_shift_id` FOREIGN KEY (`pos_shift_id`) REFERENCES `pos_shifts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_orders_tax_template_id` FOREIGN KEY (`tax_template_id`) REFERENCES `tax_templates` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_orders_warehouse_id` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_orders_total` CHECK ((`total` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=266 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (211,NULL,'DH-DEMO-001',2001,NULL,1,NULL,'2025-11-06','offline',NULL,NULL,9001,0.00,0.00,'CASH','draft',NULL,0.00,0,0.00,0,416000.00,0.00,30000.00,446000.00,0.00,446000.00,'unpaid',0,NULL,'Nguyễn Minh An','0912000001','12 Trần Hưng Đạo, Hà Nội','Hàng Bài','Hoàn Kiếm','Hà Nội',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-11-06 10:00:00','2025-11-06 10:00:00',NULL),(212,NULL,'DH-DEMO-002',2002,NULL,2,NULL,'2025-11-07','online',NULL,NULL,9002,22600.00,0.00,'BANK_TRANSFER','draft',NULL,0.00,0,0.00,0,412000.00,0.00,40000.00,474600.00,166110.00,308490.00,'partial',0,NULL,'Trần Thu Hà','0912000002','89 Lý Thường Kiệt, Hà Nội','Cửa Nam','Hoàn Kiếm','Hà Nội',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-11-07 10:00:00','2025-11-07 10:00:00',NULL),(213,NULL,'DH-DEMO-003',2003,NULL,3,NULL,'2025-11-08','online',NULL,NULL,9003,41060.00,0.00,'COD','processing',NULL,0.00,0,0.00,0,418000.00,42400.00,35000.00,451660.00,180664.00,270996.00,'partial',0,NULL,'Phạm Gia Bảo','0912000003','22 Nguyễn Huệ, HCM','Bến Nghé','Quận 1','Hồ Chí Minh',NULL,'2025-11-08 11:00:00','2025-11-08 12:00:00',NULL,NULL,NULL,NULL,NULL,0,'2025-11-08 10:00:00','2025-11-08 12:00:00',NULL),(214,NULL,'DH-DEMO-004',2004,NULL,4,NULL,'2025-11-09','offline',NULL,NULL,9001,0.00,0.00,'CASH','processing',NULL,0.00,0,0.00,0,408000.00,0.00,0.00,408000.00,204000.00,204000.00,'partial',0,NULL,'Lê Hồng Nhung','0912000004','35 Hai Bà Trưng, HCM','Bến Thành','Quận 1','Hồ Chí Minh',NULL,'2025-11-09 11:00:00','2025-11-09 12:00:00',NULL,NULL,NULL,NULL,NULL,0,'2025-11-09 10:00:00','2025-11-09 12:00:00',NULL),(215,NULL,'DH-DEMO-005',2005,NULL,5,NULL,'2025-11-10','online',NULL,NULL,9002,20220.00,0.00,'EWALLET','processing',NULL,0.00,0,0.00,0,426000.00,66600.00,45000.00,424620.00,84924.00,339696.00,'partial',0,NULL,'Vũ Hoàng Long','0912000005','15 Nguyễn Tri Phương, Đà Nẵng','Thạch Thang','Hải Châu','Đà Nẵng',NULL,'2025-11-10 11:00:00','2025-11-10 12:00:00',NULL,NULL,NULL,NULL,NULL,0,'2025-11-10 10:00:00','2025-11-10 12:00:00',NULL),(216,NULL,'DH-DEMO-006',2006,NULL,1,NULL,'2025-11-11','online',NULL,NULL,9003,44300.00,0.00,'COD','shipping',NULL,0.00,0,0.00,0,418000.00,0.00,25000.00,487300.00,292380.00,194920.00,'partial',0,NULL,'Đặng Bích Trâm','0912000006','101 Võ Văn Tần, HCM','6','Quận 3','Hồ Chí Minh',NULL,'2025-11-11 11:00:00','2025-11-11 12:00:00','2025-11-11 13:00:00',NULL,NULL,NULL,NULL,0,'2025-11-11 10:00:00','2025-11-11 13:00:00',NULL),(217,NULL,'DH-DEMO-007',2007,NULL,2,NULL,'2025-11-12','online',NULL,NULL,9001,0.00,0.00,'BANK_TRANSFER','shipping',NULL,0.00,0,0.00,0,410000.00,0.00,20000.00,430000.00,215000.00,215000.00,'partial',0,NULL,'Huỳnh Tuấn Kiệt','0912000007','45 Trần Phú, Nha Trang','Lộc Thọ','Nha Trang','Khánh Hòa',NULL,'2025-11-12 11:00:00','2025-11-12 12:00:00','2025-11-12 13:00:00',NULL,NULL,NULL,NULL,0,'2025-11-12 10:00:00','2025-11-12 13:00:00',NULL),(218,NULL,'DH-DEMO-008',2008,NULL,3,NULL,'2025-11-13','offline',NULL,NULL,9002,18680.00,0.00,'CASH','shipping',NULL,0.00,0,0.00,0,416000.00,42400.00,0.00,392280.00,274596.00,117684.00,'partial',0,NULL,'Lý Thu Uyên','0912000008','68 Lê Lợi, Huế','Phú Hội','Huế','Thừa Thiên Huế',NULL,'2025-11-13 11:00:00','2025-11-13 12:00:00','2025-11-13 13:00:00',NULL,NULL,NULL,NULL,0,'2025-11-13 10:00:00','2025-11-13 13:00:00',NULL),(219,NULL,'DH-DEMO-009',2011,NULL,1,NULL,'2025-11-14','offline',NULL,NULL,9003,41600.00,0.00,'BANK_TRANSFER','completed',NULL,0.00,0,0.00,0,416000.00,0.00,0.00,457600.00,457600.00,0.00,'paid',1,NULL,'Công ty Ánh Dương','0912000011','11 Duy Tân, Cầu Giấy, Hà Nội','Dịch Vọng','Cầu Giấy','Hà Nội',NULL,'2025-11-14 11:00:00','2025-11-14 12:00:00','2025-11-14 13:00:00','2025-11-14 14:00:00','2025-11-14 15:00:00',NULL,NULL,0,'2025-11-14 10:00:00','2025-11-14 15:00:00',NULL),(220,NULL,'DH-DEMO-010',2012,NULL,2,NULL,'2025-11-15','online',NULL,NULL,9001,0.00,0.00,'BANK_TRANSFER','completed',NULL,0.00,0,0.00,0,624000.00,42400.00,30000.00,611600.00,611600.00,0.00,'paid',1,NULL,'CTCP Gỗ Xanh','0912000012','45 Pasteur, Quận 1, HCM','Bến Nghé','Quận 1','Hồ Chí Minh',NULL,'2025-11-15 11:00:00','2025-11-15 12:00:00','2025-11-15 13:00:00','2025-11-15 14:00:00','2025-11-15 15:00:00',NULL,NULL,0,'2025-11-15 10:00:00','2025-11-15 15:00:00',NULL),(221,NULL,'DH-DEMO-011',2013,NULL,3,NULL,'2025-11-16','offline',NULL,NULL,9002,18970.00,0.00,'CASH','completed',NULL,0.00,0,0.00,0,426000.00,66600.00,20000.00,398370.00,398370.00,0.00,'paid',1,NULL,'Hộ KD Minh Quân','0912000013','22 Trần Phú, Nha Trang','Vạn Thạnh','Nha Trang','Khánh Hòa',NULL,'2025-11-16 11:00:00','2025-11-16 12:00:00','2025-11-16 13:00:00','2025-11-16 14:00:00','2025-11-16 15:00:00',NULL,NULL,0,'2025-11-16 10:00:00','2025-11-16 15:00:00',NULL),(222,NULL,'DH-DEMO-012',2014,NULL,4,NULL,'2025-11-17','online',NULL,NULL,9003,41800.00,0.00,'COD','completed',NULL,0.00,0,0.00,0,418000.00,0.00,0.00,459800.00,459800.00,0.00,'paid',1,NULL,'Công ty Vận Tải Nhanh','0912000014','88 Kim Mã, Ba Đình, Hà Nội','Kim Mã','Ba Đình','Hà Nội',NULL,'2025-11-17 11:00:00','2025-11-17 12:00:00','2025-11-17 13:00:00','2025-11-17 14:00:00','2025-11-17 15:00:00',NULL,NULL,0,'2025-11-17 10:00:00','2025-11-17 15:00:00',NULL),(223,NULL,'DH-DEMO-013',2015,NULL,5,NULL,'2025-11-18','offline',NULL,NULL,9001,0.00,0.00,'BANK_TRANSFER','completed',NULL,0.00,0,0.00,0,622000.00,42400.00,15000.00,594600.00,594600.00,0.00,'paid',1,NULL,'CTY Thiết Kế Mộc','0912000015','12 Nguyễn Trãi, Quận 5, HCM','7','Quận 5','Hồ Chí Minh',NULL,'2025-11-18 11:00:00','2025-11-18 12:00:00','2025-11-18 13:00:00','2025-11-18 14:00:00','2025-11-18 15:00:00',NULL,NULL,0,'2025-11-18 10:00:00','2025-11-18 15:00:00',NULL),(224,NULL,'DH-DEMO-014',2016,NULL,1,NULL,'2025-11-19','offline',NULL,NULL,9002,20400.00,0.00,'CASH','completed',NULL,0.00,0,0.00,0,408000.00,0.00,0.00,428400.00,428400.00,0.00,'paid',1,NULL,'Trịnh Quốc Thái','0912000016','14 Lê Duẩn, Hà Nội','Điện Biên','Ba Đình','Hà Nội',NULL,'2025-11-19 11:00:00','2025-11-19 12:00:00','2025-11-19 13:00:00','2025-11-19 14:00:00','2025-11-19 15:00:00',NULL,NULL,0,'2025-11-19 10:00:00','2025-11-19 15:00:00',NULL),(225,NULL,'DH-DEMO-015',2017,NULL,2,NULL,'2025-11-20','online',NULL,NULL,9003,44300.00,0.00,'EWALLET','completed',NULL,0.00,0,0.00,0,418000.00,0.00,25000.00,487300.00,487300.00,0.00,'paid',1,NULL,'Đỗ Hồng Ngọc','0912000017','7 Nguyễn Văn Cừ, Hạ Long','Bạch Đằng','Hạ Long','Quảng Ninh',NULL,'2025-11-20 11:00:00','2025-11-20 12:00:00','2025-11-20 13:00:00','2025-11-20 14:00:00','2025-11-20 15:00:00',NULL,NULL,0,'2025-11-20 10:00:00','2025-11-20 15:00:00',NULL),(226,NULL,'DH-DEMO-016',2018,NULL,3,NULL,'2025-11-21','offline',NULL,NULL,9001,0.00,0.00,'CASH','completed',NULL,0.00,0,0.00,0,416000.00,0.00,0.00,416000.00,416000.00,0.00,'paid',1,NULL,'La Mỹ Duyên','0912000018','155 Lạch Tray, Hải Phòng','Lạch Tray','Ngô Quyền','Hải Phòng',NULL,'2025-11-21 11:00:00','2025-11-21 12:00:00','2025-11-21 13:00:00','2025-11-21 14:00:00','2025-11-21 15:00:00',NULL,NULL,0,'2025-11-21 10:00:00','2025-11-21 15:00:00',NULL),(227,NULL,'DH-DEMO-017',2019,NULL,4,NULL,'2025-11-22','online',NULL,NULL,9002,22600.00,0.00,'BANK_TRANSFER','completed',NULL,0.00,0,0.00,0,412000.00,0.00,40000.00,474600.00,474600.00,0.00,'paid',1,NULL,'Đinh Mạnh Cường','0912000019','18 Lê Lợi, Vinh','Hưng Bình','Vinh','Nghệ An',NULL,'2025-11-22 11:00:00','2025-11-22 12:00:00','2025-11-22 13:00:00','2025-11-22 14:00:00','2025-11-22 15:00:00',NULL,NULL,0,'2025-11-22 10:00:00','2025-11-22 15:00:00',NULL),(228,NULL,'DH-DEMO-018',2020,NULL,5,NULL,'2025-11-23','offline',NULL,NULL,9003,37360.00,0.00,'CASH','completed',NULL,0.00,0,0.00,0,416000.00,42400.00,0.00,410960.00,410960.00,0.00,'paid',1,NULL,'Phùng Thanh Mai','0912000020','3 Hùng Vương, Huế','Phú Nhuận','Huế','Thừa Thiên Huế',NULL,'2025-11-23 11:00:00','2025-11-23 12:00:00','2025-11-23 13:00:00','2025-11-23 14:00:00','2025-11-23 15:00:00',NULL,NULL,0,'2025-11-23 10:00:00','2025-11-23 15:00:00',NULL),(229,NULL,'DH-DEMO-019',2009,NULL,6,NULL,'2025-11-24','offline',NULL,NULL,9001,0.00,0.00,'CASH','cancelled',NULL,0.00,0,0.00,0,204000.00,0.00,0.00,204000.00,0.00,204000.00,'unpaid',0,NULL,'Ngô Nhật Anh','0912000009','12 Nguyễn Văn Linh, Đà Nẵng','Nam Dương','Hải Châu','Đà Nẵng',NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-24 11:00:00','Khách đổi ý',0,'2025-11-24 10:00:00','2025-11-24 11:00:00',NULL),(230,NULL,'DH-DEMO-020',2010,NULL,2,NULL,'2025-11-25','online',NULL,NULL,9002,11300.00,0.00,'COD','cancelled',NULL,0.00,0,0.00,0,206000.00,0.00,20000.00,237300.00,35595.00,201705.00,'partial',0,NULL,'Tạ Kim Yến','0912000010','99 Phan Chu Trinh, Đà Nẵng','Hải Châu 1','Hải Châu','Đà Nẵng',NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-25 11:00:00','Hết hàng',0,'2025-11-25 10:00:00','2025-11-25 11:00:00',NULL),(231,NULL,'DH-DEMO-021',2012,NULL,2,NULL,'2025-11-26','online',NULL,NULL,9003,49200.00,0.00,'CASH','shipping',NULL,0.00,0,0.00,0,452000.00,0.00,40000.00,541200.00,270600.00,270600.00,'partial',0,NULL,'CTCP Gỗ Xanh','0912000012','45 Pasteur, Quận 1, HCM','Bến Nghé','Quận 1','Hồ Chí Minh','Auto-generated demo order #21','2025-11-26 11:00:00','2025-11-26 12:00:00','2025-11-26 13:00:00',NULL,NULL,NULL,NULL,0,'2025-11-26 10:00:00','2025-11-26 13:00:00',NULL),(232,NULL,'DH-DEMO-022',2015,NULL,3,NULL,'2025-11-27','online',NULL,NULL,9001,0.00,0.00,'COD','draft',NULL,0.00,0,0.00,0,1820000.00,0.00,20000.00,1840000.00,0.00,1840000.00,'unpaid',0,NULL,'CTY Thiết Kế Mộc','0912000015','12 Nguyễn Trãi, Quận 5, HCM','7','Quận 5','Hồ Chí Minh','Auto-generated demo order #22',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-11-27 10:00:00','2025-11-27 10:00:00',NULL),(233,NULL,'DH-DEMO-023',2009,NULL,5,NULL,'2025-11-28','online',NULL,NULL,9002,127800.00,0.00,'EWALLET','draft',NULL,0.00,0,0.00,0,2516000.00,0.00,40000.00,2683800.00,0.00,2683800.00,'unpaid',0,NULL,'Ngô Nhật Anh','0912000009','12 Nguyễn Văn Linh, Đà Nẵng','Nam Dương','Hải Châu','Đà Nẵng','Auto-generated demo order #23',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-11-28 10:00:00','2025-11-28 10:00:00',NULL),(234,NULL,'DH-DEMO-024',2020,NULL,2,NULL,'2025-11-29','online',NULL,NULL,9003,254000.00,0.00,'EWALLET','cancelled',NULL,0.00,0,0.00,0,2490000.00,0.00,50000.00,2794000.00,0.00,2794000.00,'unpaid',0,NULL,'Phùng Thanh Mai','0912000020','3 Hùng Vương, Huế','Phú Nhuận','Huế','Thừa Thiên Huế','Auto-generated demo order #24',NULL,NULL,NULL,NULL,NULL,'2025-11-29 11:00:00',NULL,0,'2025-11-29 10:00:00','2025-11-29 11:00:00',NULL),(235,NULL,'DH-DEMO-025',2019,NULL,2,NULL,'2025-11-30','online',NULL,NULL,9001,0.00,0.00,'COD','completed',NULL,0.00,0,0.00,0,552000.00,0.00,10000.00,562000.00,562000.00,0.00,'paid',1,NULL,'Đinh Mạnh Cường','0912000019','18 Lê Lợi, Vinh','Hưng Bình','Vinh','Nghệ An','Auto-generated demo order #25','2025-11-30 11:00:00','2025-11-30 12:00:00','2025-11-30 13:00:00','2025-11-30 14:00:00','2025-11-30 15:00:00',NULL,NULL,0,'2025-11-30 10:00:00','2025-11-30 15:00:00',NULL),(236,NULL,'DH-DEMO-026',2020,NULL,2,NULL,'2025-12-01','online',NULL,NULL,9002,90800.00,0.00,'CASH','draft',NULL,0.00,0,0.00,0,1776000.00,0.00,40000.00,1906800.00,0.00,1906800.00,'unpaid',0,NULL,'Phùng Thanh Mai','0912000020','3 Hùng Vương, Huế','Phú Nhuận','Huế','Thừa Thiên Huế','Auto-generated demo order #26',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-01 10:00:00','2025-12-01 10:00:00',NULL),(237,NULL,'DH-DEMO-027',2012,NULL,4,NULL,'2025-12-02','online',NULL,NULL,9003,128200.00,0.00,'COD','cancelled',NULL,0.00,0,0.00,0,1232000.00,0.00,50000.00,1410200.00,0.00,1410200.00,'unpaid',0,NULL,'CTCP Gỗ Xanh','0912000012','45 Pasteur, Quận 1, HCM','Bến Nghé','Quận 1','Hồ Chí Minh','Auto-generated demo order #27',NULL,NULL,NULL,NULL,NULL,'2025-12-02 11:00:00',NULL,0,'2025-12-02 10:00:00','2025-12-02 11:00:00',NULL),(238,NULL,'DH-DEMO-028',2018,NULL,2,NULL,'2025-12-03','offline',NULL,NULL,9001,0.00,0.00,'BANK_TRANSFER','shipping',NULL,0.00,0,0.00,0,1836000.00,0.00,30000.00,1866000.00,933000.00,933000.00,'partial',0,NULL,'La Mỹ Duyên','0912000018','155 Lạch Tray, Hải Phòng','Lạch Tray','Ngô Quyền','Hải Phòng','Auto-generated demo order #28','2025-12-03 11:00:00','2025-12-03 12:00:00','2025-12-03 13:00:00',NULL,NULL,NULL,NULL,0,'2025-12-03 10:00:00','2025-12-03 13:00:00',NULL),(239,NULL,'DH-DEMO-029',2007,NULL,3,NULL,'2025-12-04','online',NULL,NULL,9002,12200.00,0.00,'BANK_TRANSFER','draft',NULL,0.00,0,0.00,0,234000.00,0.00,10000.00,256200.00,0.00,256200.00,'unpaid',0,NULL,'Huỳnh Tuấn Kiệt','0912000007','45 Trần Phú, Nha Trang','Lộc Thọ','Nha Trang','Khánh Hòa','Auto-generated demo order #29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-04 10:00:00','2025-12-04 10:00:00',NULL),(240,NULL,'DH-DEMO-030',2019,NULL,4,NULL,'2025-12-05','online',NULL,NULL,9003,130000.00,0.00,'BANK_TRANSFER','draft',NULL,0.00,0,0.00,0,1280000.00,0.00,20000.00,1430000.00,0.00,1430000.00,'unpaid',0,NULL,'Đinh Mạnh Cường','0912000019','18 Lê Lợi, Vinh','Hưng Bình','Vinh','Nghệ An','Auto-generated demo order #30',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-05 10:00:00','2025-12-05 10:00:00',NULL),(241,NULL,'DH-DEMO-031',2011,NULL,5,NULL,'2025-12-06','offline',NULL,NULL,9001,0.00,0.00,'CASH','completed',NULL,0.00,0,0.00,0,2144000.00,0.00,10000.00,2154000.00,2154000.00,0.00,'paid',1,NULL,'Công ty Ánh Dương','0912000011','11 Duy Tân, Cầu Giấy, Hà Nội','Dịch Vọng','Cầu Giấy','Hà Nội','Auto-generated demo order #31','2025-12-06 11:00:00','2025-12-06 12:00:00','2025-12-06 13:00:00','2025-12-06 14:00:00','2025-12-06 15:00:00',NULL,NULL,0,'2025-12-06 10:00:00','2025-12-06 15:00:00',NULL),(242,NULL,'DH-DEMO-032',2011,NULL,3,NULL,'2025-12-07','offline',NULL,NULL,9002,47200.00,0.00,'COD','completed',NULL,0.00,0,0.00,0,904000.00,0.00,40000.00,991200.00,991200.00,0.00,'paid',1,NULL,'Công ty Ánh Dương','0912000011','11 Duy Tân, Cầu Giấy, Hà Nội','Dịch Vọng','Cầu Giấy','Hà Nội','Auto-generated demo order #32','2025-12-07 11:00:00','2025-12-07 12:00:00','2025-12-07 13:00:00','2025-12-07 14:00:00','2025-12-07 15:00:00',NULL,NULL,0,'2025-12-07 10:00:00','2025-12-07 15:00:00',NULL),(243,NULL,'DH-DEMO-033',2012,NULL,1,NULL,'2025-12-08','offline',NULL,NULL,9003,116000.00,0.00,'CASH','completed',NULL,0.00,0,0.00,0,1140000.00,0.00,20000.00,1276000.00,1276000.00,0.00,'paid',1,NULL,'CTCP Gỗ Xanh','0912000012','45 Pasteur, Quận 1, HCM','Bến Nghé','Quận 1','Hồ Chí Minh','Auto-generated demo order #33','2025-12-08 11:00:00','2025-12-08 12:00:00','2025-12-08 13:00:00','2025-12-08 14:00:00','2025-12-08 15:00:00',NULL,NULL,0,'2025-12-08 10:00:00','2025-12-08 15:00:00',NULL),(244,NULL,'DH-DEMO-034',2015,NULL,3,NULL,'2025-12-09','online',NULL,NULL,9001,0.00,0.00,'CASH','completed',NULL,0.00,0,0.00,0,1596000.00,0.00,0.00,1596000.00,1596000.00,0.00,'paid',1,NULL,'CTY Thiết Kế Mộc','0912000015','12 Nguyễn Trãi, Quận 5, HCM','7','Quận 5','Hồ Chí Minh','Auto-generated demo order #34','2025-12-09 11:00:00','2025-12-09 12:00:00','2025-12-09 13:00:00','2025-12-09 14:00:00','2025-12-09 15:00:00',NULL,NULL,0,'2025-12-09 10:00:00','2025-12-09 15:00:00',NULL),(245,NULL,'DH-DEMO-035',2011,NULL,2,NULL,'2025-12-10','offline',NULL,NULL,9002,104200.00,0.00,'BANK_TRANSFER','completed',NULL,0.00,0,0.00,0,2034000.00,0.00,50000.00,2188200.00,2188200.00,0.00,'paid',1,NULL,'Công ty Ánh Dương','0912000011','11 Duy Tân, Cầu Giấy, Hà Nội','Dịch Vọng','Cầu Giấy','Hà Nội','Auto-generated demo order #35','2025-12-10 11:00:00','2025-12-10 12:00:00','2025-12-10 13:00:00','2025-12-10 14:00:00','2025-12-10 15:00:00',NULL,NULL,0,'2025-12-10 10:00:00','2025-12-10 15:00:00',NULL),(246,NULL,'ORD-000036',NULL,NULL,1,NULL,'2025-12-06','pos',NULL,NULL,NULL,0.00,0.00,'CASH','completed',NULL,0.00,0,0.00,0,688000.00,462000.00,0.00,226000.00,226000.00,0.00,'paid',1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-06 12:42:32','2025-12-06 12:42:32',NULL),(247,NULL,'ORD-000037',NULL,NULL,1,NULL,'2025-12-06','pos',NULL,NULL,NULL,0.00,0.00,'CASH','completed',NULL,0.00,0,0.00,0,1044000.00,822000.00,0.00,222000.00,222000.00,0.00,'paid',1,1,'','','','','','','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-06 12:50:53','2025-12-06 12:50:53',NULL),(248,NULL,'ORD-000038',NULL,NULL,1,NULL,'2025-12-06','pos',NULL,NULL,NULL,0.00,0.00,'CASH','completed',NULL,0.00,0,0.00,0,1044000.00,822000.00,0.00,222000.00,222000.00,0.00,'paid',1,1,'','','','','','','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-06 12:54:27','2025-12-06 12:54:27',NULL),(249,NULL,'ORD-000039',NULL,NULL,1,NULL,'2025-12-06','pos',NULL,NULL,NULL,0.00,0.00,'CASH','completed',NULL,0.00,0,0.00,0,1044000.00,822000.00,0.00,222000.00,222000.00,0.00,'paid',1,1,'','','','','','','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-06 13:19:08','2025-12-06 13:19:08',NULL),(250,NULL,'ORD-000040',NULL,NULL,1,NULL,'2025-12-06','pos',NULL,NULL,NULL,0.00,0.00,'CASH','completed',NULL,0.00,0,0.00,0,1044000.00,822000.00,0.00,222000.00,222000.00,0.00,'paid',1,1,'','','','','','','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-06 13:21:42','2025-12-06 13:21:42',NULL),(251,NULL,'ORD-000041',NULL,NULL,1,NULL,'2025-12-07','pos',NULL,NULL,NULL,0.00,0.00,'CASH','completed',NULL,0.00,0,0.00,0,325000.00,-487000.00,0.00,812000.00,812000.00,0.00,'paid',1,1,'','','','','','','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-07 11:45:48','2025-12-07 11:45:48',NULL),(253,NULL,'ORD-000042',NULL,NULL,1,NULL,'2025-12-07','pos',NULL,NULL,NULL,0.00,0.00,'CASH','completed',NULL,0.00,0,0.00,0,325000.00,-487000.00,0.00,812000.00,812000.00,0.00,'paid',1,1,'','','','','','','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-07 19:34:42','2025-12-07 19:34:42',NULL),(254,NULL,'ORD-000043',NULL,NULL,1,NULL,'2025-12-07','pos',NULL,NULL,NULL,0.00,0.00,'CASH','completed',NULL,0.00,0,0.00,0,650000.00,-974000.00,0.00,1624000.00,1624000.00,0.00,'paid',1,1,'','','','','','','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-07 19:41:24','2025-12-07 19:41:24',NULL),(255,NULL,'ORD-000044',NULL,NULL,1,NULL,'2025-12-07','pos',NULL,NULL,NULL,0.00,0.00,'CASH','completed',NULL,0.00,0,0.00,0,325000.00,-487000.00,0.00,812000.00,812000.00,0.00,'paid',1,1,'','','','','','','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-07 19:41:37','2025-12-07 19:41:37',NULL),(256,NULL,'ORD-000045',NULL,NULL,1,NULL,'2025-12-08','pos',NULL,NULL,NULL,0.00,0.00,'CASH','completed',NULL,0.00,0,0.00,0,325000.00,-487000.00,0.00,812000.00,812000.00,0.00,'paid',1,1,'','','','','','','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-08 01:20:08','2025-12-08 01:20:08',NULL),(259,NULL,'ORD-000046',NULL,NULL,1,NULL,'2025-12-08','pos',NULL,NULL,NULL,0.00,0.00,'CASH','completed',NULL,0.00,0,0.00,0,325000.00,-487000.00,0.00,812000.00,812000.00,0.00,'paid',1,1,'','','','','','','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-08 08:27:39','2025-12-08 08:27:39',NULL),(261,NULL,'ORD-000047',9,NULL,1,NULL,'2025-12-08','pos',NULL,NULL,NULL,0.00,0.00,'CASH','completed',NULL,0.00,0,0.00,0,325000.00,-487000.00,0.00,812000.00,812000.00,0.00,'paid',1,1,'','','','','','','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-08 09:28:33','2025-12-08 09:28:33',NULL),(262,NULL,'ORD-000048',10,NULL,1,NULL,'2025-12-08','pos',NULL,NULL,NULL,0.00,0.00,'CASH','completed',NULL,0.00,0,0.00,0,603000.00,272000.00,0.00,331000.00,331000.00,0.00,'paid',1,1,'','','','','','','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-08 09:38:07','2025-12-08 09:38:07',NULL),(263,NULL,'ORD-000049',NULL,NULL,1,NULL,'2025-12-08','pos',NULL,NULL,NULL,0.00,0.00,'CASH','completed',NULL,0.00,0,0.00,0,818000.00,-378000.00,0.00,1196000.00,1196000.00,0.00,'paid',1,1,'','','','','','','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-08 09:51:09','2025-12-08 09:51:09',NULL),(264,NULL,'ORD-000050',NULL,NULL,1,NULL,'2025-12-08','pos',NULL,NULL,NULL,0.00,0.00,'CASH','completed',NULL,0.00,0,0.00,0,706000.00,-687000.00,0.00,1393000.00,1393000.00,0.00,'paid',1,1,'','','','','','','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-08 10:00:21','2025-12-08 10:00:21',NULL),(265,NULL,'ORD-000051',NULL,NULL,1,NULL,'2025-12-08','pos',NULL,NULL,NULL,0.00,0.00,'CASH','completed',NULL,0.00,0,0.00,0,818000.00,-378000.00,0.00,1196000.00,1196000.00,0.00,'paid',1,1,'','','','','','','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-08 10:15:12','2025-12-08 10:15:12',NULL);
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `organizations`
--

DROP TABLE IF EXISTS `organizations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `organizations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_vi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `parent_id` bigint unsigned DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_organization_code` (`code`),
  KEY `organizations_parent_id_foreign` (`parent_id`),
  KEY `organizations_branch_id_foreign` (`branch_id`),
  CONSTRAINT `organizations_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE SET NULL,
  CONSTRAINT `organizations_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE ON UPDATE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `organizations`
--

LOCK TABLES `organizations` WRITE;
/*!40000 ALTER TABLE `organizations` DISABLE KEYS */;
INSERT INTO `organizations` VALUES (1,'ORG-001','Tổ chức Demo 1',NULL,'0303030303',NULL,NULL,NULL,'active',NULL,NULL,'2025-12-06 03:35:22','2025-12-06 03:35:22',NULL);
/*!40000 ALTER TABLE `organizations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `packing_slip_items`
--

DROP TABLE IF EXISTS `packing_slip_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `packing_slip_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `packing_slip_id` bigint unsigned NOT NULL,
  `pick_list_item_id` bigint unsigned DEFAULT NULL,
  `stock_entry_item_id` bigint unsigned DEFAULT NULL,
  `product_id` bigint unsigned NOT NULL,
  `qty` decimal(12,3) DEFAULT '0.000',
  `batch_id` bigint unsigned DEFAULT NULL,
  `serial_number` varchar(160) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_packing_slip_item` (`packing_slip_id`),
  KEY `fk_packing_slip_items_product_id` (`product_id`),
  KEY `fk_packing_slip_items_stock_entry_item_id` (`stock_entry_item_id`),
  KEY `fk_packing_slip_items_pick_list_item_id` (`pick_list_item_id`),
  KEY `fk_packing_slip_items_batch_id` (`batch_id`),
  CONSTRAINT `fk_packing_slip_items_batch_id` FOREIGN KEY (`batch_id`) REFERENCES `product_batches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_packing_slip_items_packing_slip_id` FOREIGN KEY (`packing_slip_id`) REFERENCES `packing_slips` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_packing_slip_items_pick_list_item_id` FOREIGN KEY (`pick_list_item_id`) REFERENCES `pick_list_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_packing_slip_items_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_packing_slip_items_stock_entry_item_id` FOREIGN KEY (`stock_entry_item_id`) REFERENCES `stock_entry_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `packing_slip_items`
--

LOCK TABLES `packing_slip_items` WRITE;
/*!40000 ALTER TABLE `packing_slip_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `packing_slip_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `packing_slips`
--

DROP TABLE IF EXISTS `packing_slips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `packing_slips` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `packing_slip_number` varchar(60) NOT NULL,
  `pick_list_id` bigint unsigned DEFAULT NULL,
  `stock_entry_id` bigint unsigned DEFAULT NULL,
  `status` varchar(20) DEFAULT 'packed',
  `source_warehouse_id` bigint unsigned DEFAULT NULL,
  `target_warehouse_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_packing_slip_number` (`packing_slip_number`),
  KEY `idx_packing_slip_pick` (`pick_list_id`),
  KEY `fk_packing_slips_source_warehouse_id` (`source_warehouse_id`),
  KEY `fk_packing_slips_target_warehouse_id` (`target_warehouse_id`),
  KEY `fk_packing_slips_stock_entry_id` (`stock_entry_id`),
  CONSTRAINT `fk_packing_slips_pick_list_id` FOREIGN KEY (`pick_list_id`) REFERENCES `pick_lists` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_packing_slips_source_warehouse_id` FOREIGN KEY (`source_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_packing_slips_stock_entry_id` FOREIGN KEY (`stock_entry_id`) REFERENCES `stock_entries` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_packing_slips_target_warehouse_id` FOREIGN KEY (`target_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `packing_slips`
--

LOCK TABLES `packing_slips` WRITE;
/*!40000 ALTER TABLE `packing_slips` DISABLE KEYS */;
/*!40000 ALTER TABLE `packing_slips` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `partners`
--

DROP TABLE IF EXISTS `partners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `partners` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `type` enum('supplier','vendor','distributor','manufacturer') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `contact_person` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `city` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tax_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank_account` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `credit_limit` decimal(15,2) NOT NULL DEFAULT '0.00',
  `debt_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_purchased` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partners`
--

LOCK TABLES `partners` WRITE;
/*!40000 ALTER TABLE `partners` DISABLE KEYS */;
INSERT INTO `partners` VALUES (1,'SUP-001','Công ty TNHH Da Giày Việt Nam','supplier',NULL,'contact@dagiay.vn','024-3888-9999','123 Đường Láng, Hà Nội',NULL,'0123456789',NULL,NULL,0.00,0.00,0.00,'active','2025-12-06 03:35:22','2025-12-06 03:35:22',NULL),(2,'SUP-002','Xưởng Sản Xuất Túi Xách Hồng Hà','supplier',NULL,'hongha@tuixach.vn','024-3777-8888','456 Phố Huế, Hà Nội',NULL,'0987654321',NULL,NULL,0.00,0.00,0.00,'active','2025-12-06 03:35:22','2025-12-06 03:35:22',NULL),(3,'SUP-003','Công ty CP Phụ Kiện Thời Trang','supplier',NULL,'info@phukien.com.vn','028-3666-7777','789 Nguyễn Huệ, HCM',NULL,'0111222333',NULL,NULL,0.00,22500000.00,0.00,'active','2025-12-06 03:35:22','2025-12-06 03:35:22',NULL),(4,'SUP-004','Nhà Máy Dệt May Tân Tiến','supplier',NULL,'sales@tantien.vn','0236-3555-6666','321 Lê Duẩn, Đà Nẵng',NULL,'0444555666',NULL,NULL,0.00,30000000.00,0.00,'active','2025-12-06 03:35:22','2025-12-06 03:35:22',NULL),(5,'SUP-005','Xưởng Gia Công Đồng Phát','supplier',NULL,'dongphat@workshop.vn','0292-3444-5555','654 Đường 3/2, Cần Thơ',NULL,'0777888999',NULL,NULL,0.00,25000000.00,0.00,'active','2025-12-06 03:35:22','2025-12-06 03:35:22',NULL),(6,'SUP-006','Công ty TNHH Vải Cao Cấp','supplier',NULL,'premium@fabric.vn','024-3333-4444','987 Trần Hưng Đạo, Hà Nội',NULL,'0222333444',NULL,NULL,0.00,0.00,0.00,'active','2025-12-06 03:35:22','2025-12-06 04:30:49',NULL),(7,'SUP-007','Nhà Cung Cấp Phụ Liệu Minh Anh','supplier',NULL,'minhanh@materials.vn','028-3222-3333','147 Lê Lợi, HCM',NULL,'0555666777',NULL,NULL,0.00,0.00,0.00,'active','2025-12-06 03:35:22','2025-12-06 03:35:22',NULL),(8,'SUP-008','Xưởng Thêu Ren Hoa Mai','supplier',NULL,'hoamai@embroidery.vn','0225-3111-2222','258 Lạch Tray, Hải Phòng',NULL,'0888999000',NULL,NULL,0.00,0.00,0.00,'active','2025-12-06 03:35:22','2025-12-06 03:35:22',NULL),(9,'NCC001','Công ty ABC','supplier','Nguyễn Văn A','contact@abc.com','0901234567','123 Đường ABC, Quận 1, TP.HCM','TP.HCM','0123456789',NULL,NULL,0.00,0.00,0.00,'active','2025-12-06 04:21:26','2025-12-06 04:21:26',NULL),(10,'DT775342','Nhân viên giao hàng 1','vendor',NULL,NULL,'0912345678',NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,'active','2025-12-06 09:46:03','2025-12-06 09:46:03',NULL),(11,'DT040737','Shipper A','vendor','Nguyen Van A',NULL,'0987654321',NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,'active','2025-12-06 09:46:11','2025-12-06 09:46:11',NULL);
/*!40000 ALTER TABLE `partners` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_entries`
--

DROP TABLE IF EXISTS `payment_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `mode_of_payment` varchar(50) DEFAULT NULL,
  `party_type` varchar(50) DEFAULT NULL,
  `party_id` bigint unsigned DEFAULT NULL,
  `reference_type` varchar(120) DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `debit_account_id` bigint unsigned DEFAULT NULL,
  `credit_account_id` bigint unsigned DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(10) DEFAULT 'VND',
  `exchange_rate` decimal(12,4) DEFAULT '1.0000',
  `reference` varchar(120) DEFAULT NULL,
  `reference_no` varchar(120) DEFAULT NULL,
  `reference_date` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'posted',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_payment_entry_order` (`order_id`,`payment_method`,`reference`),
  KEY `idx_payment_party` (`party_type`,`party_id`),
  KEY `idx_payment_ref` (`reference_type`,`reference_id`),
  KEY `fk_payment_entries_credit_account_id` (`credit_account_id`),
  KEY `fk_payment_entries_debit_account_id` (`debit_account_id`),
  CONSTRAINT `fk_payment_entries_credit_account_id` FOREIGN KEY (`credit_account_id`) REFERENCES `chart_of_accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_payment_entries_debit_account_id` FOREIGN KEY (`debit_account_id`) REFERENCES `chart_of_accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_payment_entries_order_id` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_entries`
--

LOCK TABLES `payment_entries` WRITE;
/*!40000 ALTER TABLE `payment_entries` DISABLE KEYS */;
INSERT INTO `payment_entries` VALUES (1,246,'CASH',NULL,NULL,NULL,NULL,NULL,NULL,NULL,226000.00,'VND',1.0000,'',NULL,NULL,'posted','2025-12-06 12:42:32','2025-12-06 12:42:32'),(2,247,'CASH',NULL,NULL,NULL,NULL,NULL,NULL,NULL,222000.00,'VND',1.0000,'',NULL,NULL,'posted','2025-12-06 12:50:53','2025-12-06 12:50:53'),(3,248,'CASH',NULL,NULL,NULL,NULL,NULL,NULL,NULL,222000.00,'VND',1.0000,'',NULL,NULL,'posted','2025-12-06 12:54:27','2025-12-06 12:54:27'),(4,249,'CASH',NULL,NULL,NULL,NULL,NULL,NULL,NULL,222000.00,'VND',1.0000,'',NULL,NULL,'posted','2025-12-06 13:19:08','2025-12-06 13:19:08'),(5,250,'CASH',NULL,NULL,NULL,NULL,NULL,NULL,NULL,222000.00,'VND',1.0000,'',NULL,NULL,'posted','2025-12-06 13:21:42','2025-12-06 13:21:42'),(6,251,'CASH',NULL,NULL,NULL,NULL,NULL,NULL,NULL,812000.00,'VND',1.0000,'',NULL,NULL,'posted','2025-12-07 11:45:48','2025-12-07 11:45:48'),(7,253,'CASH',NULL,NULL,NULL,NULL,NULL,NULL,NULL,812000.00,'VND',1.0000,'',NULL,NULL,'posted','2025-12-07 19:34:42','2025-12-07 19:34:42'),(8,254,'CASH',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1624000.00,'VND',1.0000,'',NULL,NULL,'posted','2025-12-07 19:41:24','2025-12-07 19:41:24'),(9,255,'CASH',NULL,NULL,NULL,NULL,NULL,NULL,NULL,812000.00,'VND',1.0000,'',NULL,NULL,'posted','2025-12-07 19:41:37','2025-12-07 19:41:37'),(10,256,'CASH',NULL,NULL,NULL,NULL,NULL,NULL,NULL,812000.00,'VND',1.0000,'',NULL,NULL,'posted','2025-12-08 01:20:08','2025-12-08 01:20:08'),(11,259,'CASH',NULL,NULL,NULL,NULL,NULL,NULL,NULL,812000.00,'VND',1.0000,'',NULL,NULL,'posted','2025-12-08 08:27:39','2025-12-08 08:27:39'),(13,261,'CASH',NULL,NULL,NULL,NULL,NULL,NULL,NULL,812000.00,'VND',1.0000,'',NULL,NULL,'posted','2025-12-08 09:28:33','2025-12-08 09:28:33'),(14,262,'CASH',NULL,NULL,NULL,NULL,NULL,NULL,NULL,331000.00,'VND',1.0000,'',NULL,NULL,'posted','2025-12-08 09:38:07','2025-12-08 09:38:07'),(15,263,'CASH',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1196000.00,'VND',1.0000,'',NULL,NULL,'posted','2025-12-08 09:51:09','2025-12-08 09:51:09'),(16,264,'CASH',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1393000.00,'VND',1.0000,'',NULL,NULL,'posted','2025-12-08 10:00:21','2025-12-08 10:00:21'),(17,265,'CASH',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1196000.00,'VND',1.0000,'',NULL,NULL,'posted','2025-12-08 10:15:12','2025-12-08 10:15:12');
/*!40000 ALTER TABLE `payment_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_entry_allocations`
--

DROP TABLE IF EXISTS `payment_entry_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_entry_allocations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payment_entry_id` bigint unsigned NOT NULL,
  `reference_type` varchar(120) DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `allocated_amount` decimal(14,2) DEFAULT '0.00',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_payment_alloc_entry` (`payment_entry_id`),
  CONSTRAINT `fk_payment_entry_alloca_payment_entry_id` FOREIGN KEY (`payment_entry_id`) REFERENCES `payment_entries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_entry_allocations`
--

LOCK TABLES `payment_entry_allocations` WRITE;
/*!40000 ALTER TABLE `payment_entry_allocations` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_entry_allocations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_methods`
--

DROP TABLE IF EXISTS `payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_methods` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `name_translations` json DEFAULT NULL,
  `description` text,
  `is_active` tinyint(1) DEFAULT '1',
  `display_order` int DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_methods`
--

LOCK TABLES `payment_methods` WRITE;
/*!40000 ALTER TABLE `payment_methods` DISABLE KEYS */;
INSERT INTO `payment_methods` VALUES (1,'CASH','Tiền mặt',NULL,'Thanh toán bằng tiền mặt tại cửa hàng',1,1,'2025-12-06 03:04:12','2025-12-06 03:04:12',NULL),(2,'BANK_TRANSFER','Chuyển khoản ngân hàng',NULL,'Chuyển khoản qua tài khoản ngân hàng',1,2,'2025-12-06 03:04:12','2025-12-06 03:04:12',NULL),(3,'CARD','Thẻ tín dụng/ghi nợ',NULL,'Thanh toán bằng thẻ Visa/Mastercard/JCB',1,3,'2025-12-06 03:04:12','2025-12-06 03:04:12',NULL),(4,'COD','Thu hộ (COD)',NULL,'Thanh toán khi nhận hàng. Phí COD: 15,000đ',1,4,'2025-12-06 03:04:12','2025-12-06 03:04:12',NULL),(5,'EWALLET','Ví điện tử',NULL,'Thanh toán qua MoMo, ZaloPay, VNPay',1,5,'2025-12-06 03:04:12','2025-12-06 03:04:12',NULL);
/*!40000 ALTER TABLE `payment_methods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_schedules`
--

DROP TABLE IF EXISTS `payment_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_schedules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint unsigned NOT NULL,
  `due_date` date NOT NULL,
  `amount` decimal(14,2) DEFAULT '0.00',
  `status` varchar(20) DEFAULT 'pending',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_payment_schedule_invoice` (`invoice_id`),
  CONSTRAINT `fk_payment_schedules_invoice_id` FOREIGN KEY (`invoice_id`) REFERENCES `sales_invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_schedules`
--

LOCK TABLES `payment_schedules` WRITE;
/*!40000 ALTER TABLE `payment_schedules` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_entries`
--

DROP TABLE IF EXISTS `payroll_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payroll_number` varchar(60) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `status` varchar(30) DEFAULT 'draft',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_payroll_number` (`payroll_number`),
  KEY `idx_payroll_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_entries`
--

LOCK TABLES `payroll_entries` WRITE;
/*!40000 ALTER TABLE `payroll_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `module` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `module_group` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guard_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'users.view','Xem người dùng',NULL,'users','admin','api','2025-12-06 03:04:12',NULL,NULL),(2,'users.manage','Quản lý người dùng',NULL,'users','admin','api','2025-12-06 03:04:12',NULL,NULL),(3,'products.view','Xem sản phẩm',NULL,'products','catalog','api','2025-12-06 03:04:12',NULL,NULL),(4,'products.manage','Quản lý sản phẩm',NULL,'products','catalog','api','2025-12-06 03:04:12',NULL,NULL);
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pick_list_items`
--

DROP TABLE IF EXISTS `pick_list_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pick_list_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pick_list_id` bigint unsigned NOT NULL,
  `stock_entry_item_id` bigint unsigned DEFAULT NULL,
  `product_id` bigint unsigned NOT NULL,
  `qty` decimal(12,3) DEFAULT '0.000',
  `batch_id` bigint unsigned DEFAULT NULL,
  `serial_number` varchar(160) DEFAULT NULL,
  `source_warehouse_id` bigint unsigned DEFAULT NULL,
  `target_warehouse_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pick_list_item_list` (`pick_list_id`),
  KEY `fk_pick_list_items_product_id` (`product_id`),
  KEY `fk_pick_list_items_stock_entry_item_id` (`stock_entry_item_id`),
  KEY `fk_pick_list_items_source_warehouse_id` (`source_warehouse_id`),
  KEY `fk_pick_list_items_target_warehouse_id` (`target_warehouse_id`),
  KEY `fk_pick_list_items_batch_id` (`batch_id`),
  CONSTRAINT `fk_pick_list_items_batch_id` FOREIGN KEY (`batch_id`) REFERENCES `product_batches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pick_list_items_pick_list_id` FOREIGN KEY (`pick_list_id`) REFERENCES `pick_lists` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pick_list_items_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pick_list_items_source_warehouse_id` FOREIGN KEY (`source_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pick_list_items_stock_entry_item_id` FOREIGN KEY (`stock_entry_item_id`) REFERENCES `stock_entry_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pick_list_items_target_warehouse_id` FOREIGN KEY (`target_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pick_list_items`
--

LOCK TABLES `pick_list_items` WRITE;
/*!40000 ALTER TABLE `pick_list_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `pick_list_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pick_lists`
--

DROP TABLE IF EXISTS `pick_lists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pick_lists` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pick_list_number` varchar(60) NOT NULL,
  `stock_entry_id` bigint unsigned DEFAULT NULL,
  `source_warehouse_id` bigint unsigned DEFAULT NULL,
  `status` varchar(20) DEFAULT 'open',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pick_list_number` (`pick_list_number`),
  KEY `idx_pick_list_entry` (`stock_entry_id`),
  KEY `fk_pick_lists_source_warehouse_id` (`source_warehouse_id`),
  CONSTRAINT `fk_pick_lists_source_warehouse_id` FOREIGN KEY (`source_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pick_lists_stock_entry_id` FOREIGN KEY (`stock_entry_id`) REFERENCES `stock_entries` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pick_lists`
--

LOCK TABLES `pick_lists` WRITE;
/*!40000 ALTER TABLE `pick_lists` DISABLE KEYS */;
/*!40000 ALTER TABLE `pick_lists` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `portal_access_tokens`
--

DROP TABLE IF EXISTS `portal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `portal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `portal_user_id` bigint unsigned NOT NULL,
  `token` varchar(120) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_portal_token` (`token`),
  KEY `idx_portal_token_user` (`portal_user_id`),
  CONSTRAINT `fk_portal_access_tokens_portal_user_id` FOREIGN KEY (`portal_user_id`) REFERENCES `portal_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `portal_access_tokens`
--

LOCK TABLES `portal_access_tokens` WRITE;
/*!40000 ALTER TABLE `portal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `portal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `portal_users`
--

DROP TABLE IF EXISTS `portal_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `portal_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` varchar(30) DEFAULT 'active',
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_portal_email` (`email`),
  KEY `fk_portal_users_customer_id` (`customer_id`),
  CONSTRAINT `fk_portal_users_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `portal_users`
--

LOCK TABLES `portal_users` WRITE;
/*!40000 ALTER TABLE `portal_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `portal_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pos_offline_queue`
--

DROP TABLE IF EXISTS `pos_offline_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pos_offline_queue` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `temp_id` bigint unsigned DEFAULT NULL,
  `device_id` bigint unsigned DEFAULT NULL,
  `idempotency_key` varchar(200) NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `payload` json DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `order_id` bigint unsigned DEFAULT NULL,
  `error_message` text,
  `created_at` datetime DEFAULT NULL,
  `synced_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pos_offline_key` (`device_id`,`temp_id`),
  KEY `idx_pos_offline_status` (`status`),
  KEY `idx_pos_offline_idem` (`idempotency_key`),
  KEY `fk_pos_offline_queue_branch_id` (`branch_id`),
  KEY `fk_pos_offline_queue_order_id` (`order_id`),
  KEY `fk_pos_offline_queue_user_id` (`user_id`),
  KEY `fk_pos_offline_queue_temp` (`temp_id`),
  CONSTRAINT `fk_pos_offline_queue_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pos_offline_queue_device` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pos_offline_queue_order_id` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pos_offline_queue_temp` FOREIGN KEY (`temp_id`) REFERENCES `temp_queue` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pos_offline_queue_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pos_offline_queue`
--

LOCK TABLES `pos_offline_queue` WRITE;
/*!40000 ALTER TABLE `pos_offline_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `pos_offline_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pos_payment_methods`
--

DROP TABLE IF EXISTS `pos_payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pos_payment_methods` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `profile_id` bigint unsigned NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `is_allowed` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_profile_method` (`profile_id`,`payment_method`),
  KEY `idx_pos_payment_profile` (`profile_id`),
  CONSTRAINT `fk_pos_payment_methods_profile_id` FOREIGN KEY (`profile_id`) REFERENCES `pos_profiles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pos_payment_methods`
--

LOCK TABLES `pos_payment_methods` WRITE;
/*!40000 ALTER TABLE `pos_payment_methods` DISABLE KEYS */;
/*!40000 ALTER TABLE `pos_payment_methods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pos_profiles`
--

DROP TABLE IF EXISTS `pos_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pos_profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `role_id` bigint unsigned DEFAULT NULL,
  `price_list_id` bigint unsigned DEFAULT NULL,
  `tax_template_id` bigint unsigned DEFAULT NULL,
  `warehouse_id` bigint unsigned DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `company` varchar(150) DEFAULT NULL,
  `allow_offline` tinyint(1) DEFAULT '0',
  `require_shift` tinyint(1) DEFAULT '1',
  `credit_limit` decimal(14,2) DEFAULT '0.00',
  `status` varchar(20) DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pos_profile_user_branch` (`user_id`,`branch_id`),
  KEY `idx_pos_profile_role` (`role_id`),
  KEY `fk_pos_profiles_branch_id` (`branch_id`),
  KEY `fk_pos_profiles_warehouse_id` (`warehouse_id`),
  KEY `fk_pos_profiles_price_list_id` (`price_list_id`),
  KEY `fk_pos_profiles_tax_template_id` (`tax_template_id`),
  CONSTRAINT `fk_pos_profiles_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pos_profiles_price_list_id` FOREIGN KEY (`price_list_id`) REFERENCES `price_lists` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pos_profiles_role_id` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pos_profiles_tax_template_id` FOREIGN KEY (`tax_template_id`) REFERENCES `tax_templates` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pos_profiles_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pos_profiles_warehouse_id` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pos_profiles`
--

LOCK TABLES `pos_profiles` WRITE;
/*!40000 ALTER TABLE `pos_profiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `pos_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pos_shift_logs`
--

DROP TABLE IF EXISTS `pos_shift_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pos_shift_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shift_id` bigint unsigned NOT NULL,
  `action` varchar(50) NOT NULL,
  `message` text,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pos_shift_log` (`shift_id`),
  CONSTRAINT `fk_pos_shift_logs_shift_id` FOREIGN KEY (`shift_id`) REFERENCES `pos_shifts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pos_shift_logs`
--

LOCK TABLES `pos_shift_logs` WRITE;
/*!40000 ALTER TABLE `pos_shift_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `pos_shift_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pos_shift_payments`
--

DROP TABLE IF EXISTS `pos_shift_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pos_shift_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shift_id` bigint unsigned NOT NULL,
  `order_id` bigint unsigned DEFAULT NULL,
  `payment_method` varchar(50) NOT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pos_shift_payment` (`shift_id`),
  KEY `idx_pos_shift_payment_method` (`shift_id`,`payment_method`),
  KEY `fk_pos_shift_payments_order_id` (`order_id`),
  CONSTRAINT `fk_pos_shift_payments_order_id` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pos_shift_payments_shift_id` FOREIGN KEY (`shift_id`) REFERENCES `pos_shifts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pos_shift_payments`
--

LOCK TABLES `pos_shift_payments` WRITE;
/*!40000 ALTER TABLE `pos_shift_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `pos_shift_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pos_shifts`
--

DROP TABLE IF EXISTS `pos_shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pos_shifts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `profile_id` bigint unsigned DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `opening_balance` decimal(14,2) DEFAULT '0.00',
  `expected_total` decimal(14,2) DEFAULT '0.00',
  `expected_cash` decimal(14,2) DEFAULT '0.00',
  `expected_card` decimal(14,2) DEFAULT '0.00',
  `actual_total` decimal(14,2) DEFAULT '0.00',
  `actual_cash` decimal(14,2) DEFAULT '0.00',
  `actual_card` decimal(14,2) DEFAULT '0.00',
  `discrepancy` decimal(14,2) DEFAULT '0.00',
  `status` varchar(20) DEFAULT 'open',
  `opened_at` datetime DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `closing_note` text,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pos_shift_user_status` (`user_id`,`status`),
  KEY `idx_pos_shift_profile` (`profile_id`),
  KEY `fk_pos_shifts_branch_id` (`branch_id`),
  CONSTRAINT `fk_pos_shifts_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pos_shifts_profile_id` FOREIGN KEY (`profile_id`) REFERENCES `pos_profiles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pos_shifts_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pos_shifts`
--

LOCK TABLES `pos_shifts` WRITE;
/*!40000 ALTER TABLE `pos_shifts` DISABLE KEYS */;
/*!40000 ALTER TABLE `pos_shifts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `positions`
--

DROP TABLE IF EXISTS `positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `positions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `level` int DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_positions_department_id` (`department_id`),
  CONSTRAINT `fk_positions_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `positions`
--

LOCK TABLES `positions` WRITE;
/*!40000 ALTER TABLE `positions` DISABLE KEYS */;
INSERT INTO `positions` VALUES (1,'Giám Đốc','CEO',1,1,'2025-12-06 03:43:05','2025-12-06 03:43:05'),(2,'Trưởng Phòng','MGR',2,2,'2025-12-06 03:43:05','2025-12-06 03:43:05'),(3,'Nhân Viên','STAFF',2,3,'2025-12-06 03:43:05','2025-12-06 03:43:05'),(4,'Kế Toán Trưởng','ACC_MGR',3,2,'2025-12-06 03:43:05','2025-12-06 03:43:05'),(5,'Thủ Kho','WH_KEEPER',5,3,'2025-12-06 03:43:05','2025-12-06 03:43:05');
/*!40000 ALTER TABLE `positions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `price_history`
--

DROP TABLE IF EXISTS `price_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `price_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `variant_id` bigint unsigned DEFAULT NULL,
  `source_type` varchar(50) NOT NULL,
  `source_id` bigint unsigned DEFAULT NULL,
  `old_price` decimal(14,4) DEFAULT '0.0000',
  `new_price` decimal(14,4) DEFAULT '0.0000',
  `changed_by` bigint unsigned DEFAULT NULL,
  `changed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_price_history` (`product_id`,`variant_id`),
  KEY `fk_price_history_variant_id` (`variant_id`),
  CONSTRAINT `fk_price_history_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_price_history_variant_id` FOREIGN KEY (`variant_id`) REFERENCES `product_variants_v2` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `price_history`
--

LOCK TABLES `price_history` WRITE;
/*!40000 ALTER TABLE `price_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `price_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `price_list_items`
--

DROP TABLE IF EXISTS `price_list_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `price_list_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `price_list_id` bigint unsigned DEFAULT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `variant_id` bigint unsigned DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `discount_percent` decimal(5,2) DEFAULT '0.00',
  `discount_amount` decimal(10,2) DEFAULT '0.00',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_price_list_items_price_list_id` (`price_list_id`),
  KEY `fk_price_list_items_product_id` (`product_id`),
  KEY `fk_price_list_items_variant_id` (`variant_id`),
  CONSTRAINT `fk_price_list_items_price_list_id` FOREIGN KEY (`price_list_id`) REFERENCES `price_lists` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_price_list_items_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_price_list_items_variant_id` FOREIGN KEY (`variant_id`) REFERENCES `product_variants_v2` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `price_list_items`
--

LOCK TABLES `price_list_items` WRITE;
/*!40000 ALTER TABLE `price_list_items` DISABLE KEYS */;
INSERT INTO `price_list_items` VALUES (1,1,501,NULL,572000.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(2,2,501,NULL,514800.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(3,1,502,NULL,1196000.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(4,2,502,NULL,1076400.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(5,1,503,NULL,1355000.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(6,2,503,NULL,1219500.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(7,1,504,NULL,1172000.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(8,2,504,NULL,1054800.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(9,1,505,NULL,920000.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(10,2,505,NULL,828000.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(11,1,506,NULL,1393000.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(12,2,506,NULL,1253700.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(13,1,507,NULL,802000.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(14,2,507,NULL,721800.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(15,1,508,NULL,1246000.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(16,2,508,NULL,1121400.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(17,1,509,NULL,966000.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(18,2,509,NULL,869400.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(19,1,510,NULL,598000.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(20,2,510,NULL,538200.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(21,1,511,NULL,738000.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(22,2,511,NULL,664200.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(23,1,512,NULL,1457000.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(24,2,512,NULL,1311300.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(25,1,513,NULL,331000.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(26,2,513,NULL,297900.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(27,1,514,NULL,1438000.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(28,2,514,NULL,1294200.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(29,1,515,NULL,812000.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(30,2,515,NULL,730800.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(31,1,516,NULL,1374000.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(32,2,516,NULL,1236600.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(33,1,517,NULL,1019000.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(34,2,517,NULL,917100.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(35,1,518,NULL,201000.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(36,2,518,NULL,180900.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(37,1,519,NULL,1099000.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(38,2,519,NULL,989100.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(39,1,520,NULL,873000.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(40,2,520,NULL,785700.00,0.00,0.00,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL);
/*!40000 ALTER TABLE `price_list_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `price_lists`
--

DROP TABLE IF EXISTS `price_lists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `price_lists` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(50) DEFAULT 'custom',
  `description` text,
  `apply_to_groups` json DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `priority` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `is_system` tinyint(1) DEFAULT '0',
  `formula` text,
  `base_price_list_id` bigint unsigned DEFAULT NULL,
  `auto_update` tinyint(1) DEFAULT '0',
  `rounding_rule` varchar(50) DEFAULT 'none',
  `config` json DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_price_lists_base_price_list_id` (`base_price_list_id`),
  CONSTRAINT `fk_price_lists_base_price_list_id` FOREIGN KEY (`base_price_list_id`) REFERENCES `price_lists` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `price_lists`
--

LOCK TABLES `price_lists` WRITE;
/*!40000 ALTER TABLE `price_lists` DISABLE KEYS */;
INSERT INTO `price_lists` VALUES (1,'Bảng giá chung','default',NULL,NULL,'2025-12-07',NULL,0,1,1,NULL,NULL,0,'none',NULL,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL),(2,'Bảng giá VIP (Giảm 10%)','normal',NULL,NULL,'2025-12-07',NULL,0,1,0,NULL,NULL,0,'none',NULL,'2025-12-07 10:47:25','2025-12-07 10:47:25',NULL);
/*!40000 ALTER TABLE `price_lists` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pricing_rules`
--

DROP TABLE IF EXISTS `pricing_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pricing_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `condition_type` varchar(50) DEFAULT 'amount',
  `customer_id` bigint unsigned DEFAULT NULL,
  `project_id` bigint unsigned DEFAULT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `variant_id` bigint unsigned DEFAULT NULL,
  `min_qty` decimal(12,3) DEFAULT '0.000',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `price` decimal(14,4) DEFAULT '0.0000',
  `discount_percent` decimal(6,3) DEFAULT '0.000',
  `priority` int DEFAULT '100',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pricing_rule` (`condition_type`,`customer_id`,`project_id`),
  KEY `idx_pricing_priority` (`priority`),
  KEY `fk_pricing_rules_customer_id` (`customer_id`),
  KEY `fk_pricing_rules_product_id` (`product_id`),
  KEY `fk_pricing_rules_variant_id` (`variant_id`),
  KEY `fk_pricing_rules_project_id` (`project_id`),
  CONSTRAINT `fk_pricing_rules_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pricing_rules_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pricing_rules_project_id` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pricing_rules_variant_id` FOREIGN KEY (`variant_id`) REFERENCES `product_variants_v2` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pricing_rules`
--

LOCK TABLES `pricing_rules` WRITE;
/*!40000 ALTER TABLE `pricing_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `pricing_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_attribute_options`
--

DROP TABLE IF EXISTS `product_attribute_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_attribute_options` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `attribute_id` bigint unsigned DEFAULT NULL,
  `option_name` varchar(255) DEFAULT NULL,
  `color_code` varchar(50) DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `status` varchar(20) DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_product_attribute_op_attribute_id` (`attribute_id`),
  CONSTRAINT `fk_product_attribute_op_attribute_id` FOREIGN KEY (`attribute_id`) REFERENCES `product_attributes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=306 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_attribute_options`
--

LOCK TABLES `product_attribute_options` WRITE;
/*!40000 ALTER TABLE `product_attribute_options` DISABLE KEYS */;
INSERT INTO `product_attribute_options` VALUES (301,201,'Đen','#000000',1,'active','2025-12-06 03:04:12',NULL,NULL),(302,201,'Nâu','#5b3a29',2,'active','2025-12-06 03:04:12',NULL,NULL),(303,202,'M',NULL,1,'active','2025-12-06 03:04:12',NULL,NULL),(304,202,'L',NULL,2,'active','2025-12-06 03:04:12',NULL,NULL),(305,201,'Xanh rêu','#556b2f',3,'active','2025-12-06 03:35:22',NULL,NULL);
/*!40000 ALTER TABLE `product_attribute_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_attribute_values`
--

DROP TABLE IF EXISTS `product_attribute_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_attribute_values` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned DEFAULT NULL,
  `variant_id` bigint unsigned DEFAULT NULL,
  `attribute_id` bigint unsigned DEFAULT NULL,
  `option_id` bigint unsigned DEFAULT NULL,
  `value_text` text,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_product_attribute_va_attribute_id` (`attribute_id`),
  KEY `fk_product_attribute_va_option_id` (`option_id`),
  KEY `fk_product_attribute_va_product_id` (`product_id`),
  KEY `fk_product_attribute_va_variant_id` (`variant_id`),
  CONSTRAINT `fk_product_attribute_va_attribute_id` FOREIGN KEY (`attribute_id`) REFERENCES `product_attributes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_product_attribute_va_option_id` FOREIGN KEY (`option_id`) REFERENCES `product_attribute_options` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_product_attribute_va_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_product_attribute_va_variant_id` FOREIGN KEY (`variant_id`) REFERENCES `product_variants_v2` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_attribute_values`
--

LOCK TABLES `product_attribute_values` WRITE;
/*!40000 ALTER TABLE `product_attribute_values` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_attribute_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_attributes`
--

DROP TABLE IF EXISTS `product_attributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_attributes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `attribute_key` varchar(100) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `is_required` tinyint DEFAULT '0',
  `is_filterable` tinyint DEFAULT '0',
  `sort_order` int DEFAULT '0',
  `status` varchar(20) DEFAULT 'active',
  `is_visible` tinyint DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=203 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_attributes`
--

LOCK TABLES `product_attributes` WRITE;
/*!40000 ALTER TABLE `product_attributes` DISABLE KEYS */;
INSERT INTO `product_attributes` VALUES (201,'Màu sắc','mau-sac','color','select',0,1,1,'active',1,'2025-12-06 03:04:12',NULL,NULL),(202,'Kích thước','size','size','select',0,1,2,'active',1,'2025-12-06 03:04:12',NULL,NULL);
/*!40000 ALTER TABLE `product_attributes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_batches`
--

DROP TABLE IF EXISTS `product_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_batches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `variant_id` bigint unsigned DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `warehouse_id` bigint unsigned DEFAULT NULL,
  `batch_number` varchar(120) NOT NULL,
  `manufacture_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `initial_quantity` decimal(12,3) DEFAULT '0.000',
  `current_quantity` decimal(12,3) DEFAULT '0.000',
  `cost_per_unit` decimal(14,4) DEFAULT '0.0000',
  `supplier_name` varchar(255) DEFAULT NULL,
  `reference_document` varchar(160) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_product_batch_number` (`product_id`,`batch_number`),
  KEY `idx_product_batch_expiry` (`expiry_date`),
  KEY `idx_product_batch_product` (`product_id`,`variant_id`),
  KEY `fk_product_batches_branch_id` (`branch_id`),
  KEY `fk_product_batches_variant_id` (`variant_id`),
  KEY `fk_product_batches_warehouse_id` (`warehouse_id`),
  CONSTRAINT `fk_product_batches_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_product_batches_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_product_batches_variant_id` FOREIGN KEY (`variant_id`) REFERENCES `product_variants_v2` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_product_batches_warehouse_id` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_batches`
--

LOCK TABLES `product_batches` WRITE;
/*!40000 ALTER TABLE `product_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_bundles`
--

DROP TABLE IF EXISTS `product_bundles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_bundles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `parent_product_id` bigint unsigned NOT NULL,
  `child_product_id` bigint unsigned NOT NULL,
  `quantity` decimal(12,3) NOT NULL DEFAULT '1.000',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_bundles_parent_product_id_foreign` (`parent_product_id`),
  KEY `product_bundles_child_product_id_foreign` (`child_product_id`),
  CONSTRAINT `product_bundles_child_product_id_foreign` FOREIGN KEY (`child_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `product_bundles_parent_product_id_foreign` FOREIGN KEY (`parent_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_bundles`
--

LOCK TABLES `product_bundles` WRITE;
/*!40000 ALTER TABLE `product_bundles` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_bundles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_categories`
--

DROP TABLE IF EXISTS `product_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint unsigned DEFAULT NULL,
  `product_id` bigint unsigned DEFAULT '0',
  `level` tinyint DEFAULT '1',
  `is_variant_group` tinyint DEFAULT '0',
  `code` varchar(50) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `description` text,
  `image` varchar(255) DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_product_categories_parent_id` (`parent_id`),
  CONSTRAINT `fk_product_categories_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `product_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_categories`
--

LOCK TABLES `product_categories` WRITE;
/*!40000 ALTER TABLE `product_categories` DISABLE KEYS */;
INSERT INTO `product_categories` VALUES (10,NULL,0,1,0,'CAT_MEN','Thời trang Nam','thoi-trang-nam',NULL,NULL,1,'active','2025-12-06 10:17:08','2025-12-06 10:17:08',NULL),(11,10,0,2,0,'CAT_MEN_TSHIRT','Áo Thun Nam','ao-thun-nam',NULL,NULL,4,'active','2025-12-06 10:17:08','2025-12-06 10:17:08',NULL),(12,10,0,2,0,'CAT_MEN_SHIRT','Áo Sơ mi Nam','ao-somi-nam',NULL,NULL,5,'active','2025-12-06 10:17:08','2025-12-06 10:17:08',NULL),(13,10,0,2,0,'CAT_MEN_JEANS','Quần Jeans Nam','quan-jeans-nam',NULL,NULL,6,'active','2025-12-06 10:17:08','2025-12-06 10:17:08',NULL),(20,NULL,0,1,0,'CAT_WOMEN','Thời trang Nữ','thoi-trang-nu',NULL,NULL,2,'active','2025-12-06 10:17:08','2025-12-06 10:17:08',NULL),(21,20,0,2,0,'CAT_WOMEN_DRESS','Đầm Váy','dam-vay',NULL,NULL,7,'active','2025-12-06 10:17:08','2025-12-06 10:17:08',NULL),(22,20,0,2,0,'CAT_WOMEN_TOP','Áo Kiểu','ao-kieu',NULL,NULL,8,'active','2025-12-06 10:17:08','2025-12-06 10:17:08',NULL),(30,NULL,0,1,0,'CAT_ACCESSORIES','Phụ kiện','phu-kien',NULL,NULL,3,'active','2025-12-06 10:17:08','2025-12-06 10:17:08',NULL),(31,30,0,2,0,'CAT_BAGS','Túi xách','tui-xach',NULL,NULL,9,'active','2025-12-06 10:17:08','2025-12-06 10:17:08',NULL),(32,30,0,2,0,'CAT_SHOES','Giày dép','giay-dep',NULL,NULL,10,'active','2025-12-06 10:17:08','2025-12-06 10:17:08',NULL),(33,30,0,2,0,'CAT_WATCHES','Đồng hồ','dong-ho',NULL,NULL,11,'active','2025-12-06 10:17:08','2025-12-06 10:17:08',NULL);
/*!40000 ALTER TABLE `product_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_category_links`
--

DROP TABLE IF EXISTS `product_category_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_category_links` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned DEFAULT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_product_category_links` (`product_id`,`category_id`),
  KEY `fk_product_category_lin_category_id` (`category_id`),
  CONSTRAINT `fk_product_category_lin_category_id` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_product_category_lin_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_category_links`
--

LOCK TABLES `product_category_links` WRITE;
/*!40000 ALTER TABLE `product_category_links` DISABLE KEYS */;
INSERT INTO `product_category_links` VALUES (1,501,21,'2025-12-07 10:50:49'),(2,502,11,'2025-12-07 10:50:49'),(3,503,11,'2025-12-07 10:50:49'),(4,504,11,'2025-12-07 10:50:49'),(5,505,12,'2025-12-07 10:50:49'),(6,506,13,'2025-12-07 10:50:49'),(7,507,11,'2025-12-07 10:50:49'),(8,508,21,'2025-12-07 10:50:49'),(9,509,13,'2025-12-07 10:50:49'),(10,510,13,'2025-12-07 10:50:49'),(11,511,12,'2025-12-07 10:50:49'),(12,512,21,'2025-12-07 10:50:49'),(13,513,12,'2025-12-07 10:50:49'),(14,514,21,'2025-12-07 10:50:49'),(15,515,21,'2025-12-07 10:50:49'),(16,516,12,'2025-12-07 10:50:49'),(17,517,32,'2025-12-07 10:50:49'),(18,518,32,'2025-12-07 10:50:49'),(19,519,11,'2025-12-07 10:50:49'),(20,520,32,'2025-12-07 10:50:49');
/*!40000 ALTER TABLE `product_category_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_channels`
--

DROP TABLE IF EXISTS `product_channels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_channels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `channel` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `channel_product_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'disconnected',
  `sync_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'synced',
  `last_sync_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_id_channel` (`product_id`,`channel`),
  CONSTRAINT `product_channels_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_channels`
--

LOCK TABLES `product_channels` WRITE;
/*!40000 ALTER TABLE `product_channels` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_channels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_images`
--

DROP TABLE IF EXISTS `product_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_images` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned DEFAULT NULL,
  `variant_id` bigint unsigned DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_primary` tinyint DEFAULT '0',
  `sort_order` int DEFAULT '0',
  `file_name` varchar(255) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_product_images_product_id` (`product_id`),
  KEY `fk_product_images_variant_id` (`variant_id`),
  CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_product_images_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_product_images_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants_v2` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_product_images_variant_id` FOREIGN KEY (`variant_id`) REFERENCES `product_variants_v2` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_images`
--

LOCK TABLES `product_images` WRITE;
/*!40000 ALTER TABLE `product_images` DISABLE KEYS */;
INSERT INTO `product_images` VALUES (1,501,NULL,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/9b9cf7569bd348fd9c73ab9c167a96b5.jpeg',1,1,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(2,502,NULL,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/15/lano/677874c0246b4f93ab1843fb4ddab0a8.jpeg',1,1,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(3,503,NULL,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/67b7e3f88926487e873b064379fa451c.jpeg',1,1,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(4,504,NULL,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/1966289b43444855871891963240e946.jpeg',1,1,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(5,505,NULL,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/3fcf08b6f8b240fa99a661e56cdc7b3f.jpeg',1,1,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(6,506,NULL,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/9b9cf7569bd348fd9c73ab9c167a96b5.jpeg',1,1,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(7,507,NULL,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/15/lano/677874c0246b4f93ab1843fb4ddab0a8.jpeg',1,1,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(8,508,NULL,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/67b7e3f88926487e873b064379fa451c.jpeg',1,1,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(9,509,NULL,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/1966289b43444855871891963240e946.jpeg',1,1,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(10,510,NULL,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/3fcf08b6f8b240fa99a661e56cdc7b3f.jpeg',1,1,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(11,511,NULL,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/9b9cf7569bd348fd9c73ab9c167a96b5.jpeg',1,1,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(12,512,NULL,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/15/lano/677874c0246b4f93ab1843fb4ddab0a8.jpeg',1,1,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(13,513,NULL,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/67b7e3f88926487e873b064379fa451c.jpeg',1,1,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(14,514,NULL,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/1966289b43444855871891963240e946.jpeg',1,1,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(15,515,NULL,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/3fcf08b6f8b240fa99a661e56cdc7b3f.jpeg',1,1,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(16,516,NULL,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/9b9cf7569bd348fd9c73ab9c167a96b5.jpeg',1,1,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(17,517,NULL,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/15/lano/677874c0246b4f93ab1843fb4ddab0a8.jpeg',1,1,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(18,518,NULL,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/67b7e3f88926487e873b064379fa451c.jpeg',1,1,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(19,519,NULL,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/1966289b43444855871891963240e946.jpeg',1,1,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(20,520,NULL,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/3fcf08b6f8b240fa99a661e56cdc7b3f.jpeg',1,1,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49');
/*!40000 ALTER TABLE `product_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_prices`
--

DROP TABLE IF EXISTS `product_prices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_prices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `variant_id` bigint unsigned DEFAULT NULL,
  `customer_group_id` bigint unsigned DEFAULT NULL COMMENT 'NULL = giá chung',
  `price_type` enum('retail','wholesale','special') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'retail',
  `price` decimal(15,2) NOT NULL,
  `min_quantity` int DEFAULT '1' COMMENT 'SL tối thiểu',
  `valid_from` date DEFAULT NULL,
  `valid_to` date DEFAULT NULL,
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `variant_id` (`variant_id`),
  KEY `customer_group_id` (`customer_group_id`),
  KEY `price_type` (`price_type`),
  KEY `status` (`status`),
  CONSTRAINT `fk_product_prices_customer_group` FOREIGN KEY (`customer_group_id`) REFERENCES `customer_groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_product_prices_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_product_prices_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants_v2` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_prices`
--

LOCK TABLES `product_prices` WRITE;
/*!40000 ALTER TABLE `product_prices` DISABLE KEYS */;
INSERT INTO `product_prices` VALUES (1,501,NULL,NULL,'retail',1285000.00,1,NULL,NULL,'active',NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(2,502,NULL,NULL,'retail',818000.00,1,NULL,NULL,'active',NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(3,503,NULL,NULL,'retail',890000.00,1,NULL,NULL,'active',NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(4,504,NULL,NULL,'retail',1437000.00,1,NULL,NULL,'active',NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(5,505,NULL,NULL,'retail',1209000.00,1,NULL,NULL,'active',NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(6,505,7051,NULL,'retail',1209000.00,1,NULL,NULL,'active',NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(7,505,7052,NULL,'retail',1229000.00,1,NULL,NULL,'active',NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(8,506,NULL,NULL,'retail',706000.00,1,NULL,NULL,'active',NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(9,507,NULL,NULL,'retail',1006000.00,1,NULL,NULL,'active',NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(10,508,NULL,NULL,'retail',310000.00,1,NULL,NULL,'active',NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(11,509,NULL,NULL,'retail',278000.00,1,NULL,NULL,'active',NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(12,510,NULL,NULL,'retail',279000.00,1,NULL,NULL,'active',NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(13,510,7101,NULL,'retail',279000.00,1,NULL,NULL,'active',NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(14,510,7102,NULL,'retail',299000.00,1,NULL,NULL,'active',NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(15,511,NULL,NULL,'retail',1282000.00,1,NULL,NULL,'active',NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(16,512,NULL,NULL,'retail',691000.00,1,NULL,NULL,'active',NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(17,513,NULL,NULL,'retail',603000.00,1,NULL,NULL,'active',NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(18,514,NULL,NULL,'retail',1099000.00,1,NULL,NULL,'active',NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(19,515,NULL,NULL,'retail',325000.00,1,NULL,NULL,'active',NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(20,515,7151,NULL,'retail',325000.00,1,NULL,NULL,'active',NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(21,515,7152,NULL,'retail',345000.00,1,NULL,NULL,'active',NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(22,516,NULL,NULL,'retail',157000.00,1,NULL,NULL,'active',NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(23,517,NULL,NULL,'retail',1338000.00,1,NULL,NULL,'active',NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(24,518,NULL,NULL,'retail',1335000.00,1,NULL,NULL,'active',NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(25,519,NULL,NULL,'retail',388000.00,1,NULL,NULL,'active',NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(26,520,NULL,NULL,'retail',363000.00,1,NULL,NULL,'active',NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(27,520,7201,NULL,'retail',363000.00,1,NULL,NULL,'active',NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49'),(28,520,7202,NULL,'retail',383000.00,1,NULL,NULL,'active',NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49');
/*!40000 ALTER TABLE `product_prices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_serial_numbers`
--

DROP TABLE IF EXISTS `product_serial_numbers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_serial_numbers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `variant_id` bigint unsigned DEFAULT NULL,
  `batch_id` bigint unsigned DEFAULT NULL,
  `serial_number` varchar(160) NOT NULL,
  `status` varchar(30) DEFAULT 'available',
  `warranty_expiry_date` date DEFAULT NULL,
  `reserved_for_order_id` bigint unsigned DEFAULT NULL,
  `reserved_at` datetime DEFAULT NULL,
  `sold_to_order_id` bigint unsigned DEFAULT NULL,
  `sold_date` datetime DEFAULT NULL,
  `returned_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_serial_number` (`serial_number`),
  KEY `idx_serial_status_product` (`status`,`product_id`),
  KEY `idx_serial_reserved` (`reserved_for_order_id`),
  KEY `fk_product_serial_numbe_product_id` (`product_id`),
  KEY `fk_product_serial_numbe_variant_id` (`variant_id`),
  KEY `fk_product_serial_numbe_batch_id` (`batch_id`),
  KEY `fk_product_serial_numbe_sold_to_order_id` (`sold_to_order_id`),
  CONSTRAINT `fk_product_serial_numbe_batch_id` FOREIGN KEY (`batch_id`) REFERENCES `product_batches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_product_serial_numbe_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_product_serial_numbe_reserved_for_order_i` FOREIGN KEY (`reserved_for_order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_product_serial_numbe_sold_to_order_id` FOREIGN KEY (`sold_to_order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_product_serial_numbe_variant_id` FOREIGN KEY (`variant_id`) REFERENCES `product_variants_v2` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_serial_numbers`
--

LOCK TABLES `product_serial_numbers` WRITE;
/*!40000 ALTER TABLE `product_serial_numbers` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_serial_numbers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_stock_by_branch`
--

DROP TABLE IF EXISTS `product_stock_by_branch`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_stock_by_branch` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned NOT NULL,
  `stock_quantity` int DEFAULT '0' COMMENT 'Số lượng tồn',
  `alert_stock` int DEFAULT '10' COMMENT 'Ngưỡng cảnh báo',
  `reserved_stock` int DEFAULT '0' COMMENT 'Hàng đang giữ',
  `available_stock` int DEFAULT '0' COMMENT 'Có thể bán',
  `expiry_days` int DEFAULT NULL COMMENT 'Dự kiến hết hàng (ngày)',
  `last_stock_date` datetime DEFAULT NULL COMMENT 'Lần cập nhật tồn cuối',
  `status` enum('in_stock','low_stock','out_of_stock') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'in_stock',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_product_branch` (`product_id`,`branch_id`),
  KEY `idx_branch` (`branch_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_psbb_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_psbb_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=151 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_stock_by_branch`
--

LOCK TABLES `product_stock_by_branch` WRITE;
/*!40000 ALTER TABLE `product_stock_by_branch` DISABLE KEYS */;
INSERT INTO `product_stock_by_branch` VALUES (1,501,1,30,10,7,23,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(2,501,2,91,10,8,83,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(3,501,3,20,10,5,15,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(4,501,4,85,10,7,78,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(5,501,5,101,10,10,91,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(6,502,1,43,10,9,34,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(7,502,2,89,10,2,87,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(8,502,3,81,10,10,71,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(9,502,4,58,10,4,54,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(10,502,5,20,10,4,16,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(11,503,1,80,10,8,72,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(12,503,2,129,10,5,124,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(13,503,3,24,10,10,14,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(14,503,4,27,10,7,20,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(15,503,5,52,10,6,46,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(16,504,1,20,10,4,16,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(17,504,2,67,10,8,59,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(18,504,3,65,10,2,63,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(19,504,4,128,10,5,123,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(20,504,5,77,10,10,67,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(21,505,1,111,10,2,109,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(22,505,2,21,10,5,16,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(23,505,3,128,10,8,120,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(24,505,4,126,10,10,116,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(25,505,5,30,10,10,20,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(26,506,1,97,10,3,94,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(27,506,2,44,10,4,40,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(28,506,3,83,10,6,77,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(29,506,4,66,10,6,60,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(30,506,5,111,10,4,107,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(31,507,1,115,10,10,105,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(32,507,2,135,10,9,126,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(33,507,3,65,10,9,56,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(34,507,4,116,10,10,106,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(35,507,5,140,10,1,139,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(36,508,1,24,10,7,17,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(37,508,2,144,10,0,144,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(38,508,3,147,10,6,141,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(39,508,4,27,10,6,21,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(40,508,5,84,10,2,82,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(41,509,1,88,10,1,87,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(42,509,2,91,10,5,86,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(43,509,3,91,10,7,84,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(44,509,4,115,10,3,112,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(45,509,5,132,10,0,132,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(46,510,1,94,10,6,88,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(47,510,2,27,10,10,17,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(48,510,3,56,10,2,54,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(49,510,4,64,10,4,60,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(50,510,5,138,10,5,133,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(51,511,1,98,10,1,97,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(52,511,2,37,10,7,30,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(53,511,3,134,10,3,131,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(54,511,4,83,10,5,78,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(55,511,5,128,10,7,121,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(56,512,1,103,10,6,97,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(57,512,2,89,10,1,88,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(58,512,3,148,10,8,140,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(59,512,4,86,10,0,86,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(60,512,5,131,10,8,123,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(61,513,1,60,10,9,51,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(62,513,2,99,10,9,90,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(63,513,3,86,10,0,86,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(64,513,4,138,10,2,136,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(65,513,5,88,10,6,82,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(66,514,1,45,10,5,40,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(67,514,2,49,10,4,45,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(68,514,3,140,10,9,131,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(69,514,4,108,10,6,102,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(70,514,5,113,10,9,104,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(71,515,1,101,10,6,95,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(72,515,2,22,10,5,17,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(73,515,3,110,10,9,101,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(74,515,4,139,10,10,129,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(75,515,5,97,10,1,96,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(76,516,1,101,10,7,94,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(77,516,2,96,10,6,90,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(78,516,3,87,10,10,77,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(79,516,4,44,10,4,40,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(80,516,5,60,10,7,53,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(81,517,1,90,10,3,87,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(82,517,2,144,10,2,142,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(83,517,3,62,10,0,62,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(84,517,4,97,10,4,93,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(85,517,5,129,10,0,129,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(86,518,1,82,10,3,79,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(87,518,2,145,10,3,142,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(88,518,3,133,10,9,124,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(89,518,4,64,10,4,60,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(90,518,5,146,10,1,145,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(91,519,1,107,10,0,107,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(92,519,2,86,10,9,77,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(93,519,3,38,10,2,36,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(94,519,4,24,10,3,21,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(95,519,5,54,10,3,51,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(96,520,1,136,10,6,130,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(97,520,2,48,10,0,48,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(98,520,3,106,10,1,105,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(99,520,4,143,10,6,137,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(100,520,5,123,10,7,116,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(101,521,1,65,10,10,55,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(102,521,2,55,10,7,48,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(103,521,3,117,10,5,112,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(104,521,4,44,10,6,38,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(105,521,5,124,10,0,124,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(106,522,1,29,10,5,24,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(107,522,2,53,10,8,45,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(108,522,3,58,10,2,56,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(109,522,4,97,10,0,97,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(110,522,5,72,10,1,71,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(111,523,1,90,10,0,90,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(112,523,2,84,10,9,75,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(113,523,3,137,10,0,137,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(114,523,4,24,10,10,14,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(115,523,5,123,10,8,115,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(116,524,1,137,10,2,135,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(117,524,2,120,10,2,118,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(118,524,3,54,10,5,49,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(119,524,4,53,10,6,47,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(120,524,5,58,10,5,53,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(121,525,1,108,10,2,106,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(122,525,2,40,10,9,31,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(123,525,3,137,10,6,131,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(124,525,4,104,10,1,103,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(125,525,5,124,10,5,119,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(126,526,1,73,10,3,70,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(127,526,2,45,10,3,42,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(128,526,3,22,10,7,15,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(129,526,4,48,10,7,41,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(130,526,5,122,10,0,122,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(131,527,1,87,10,5,82,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(132,527,2,89,10,1,88,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(133,527,3,40,10,1,39,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(134,527,4,137,10,8,129,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(135,527,5,120,10,8,112,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(136,528,1,46,10,5,41,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(137,528,2,44,10,8,36,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(138,528,3,122,10,1,121,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(139,528,4,69,10,8,61,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(140,528,5,70,10,6,64,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(141,529,1,119,10,10,109,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(142,529,2,138,10,9,129,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(143,529,3,114,10,2,112,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(144,529,4,119,10,8,111,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(145,529,5,111,10,3,108,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(146,530,1,33,10,9,24,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(147,530,2,139,10,6,133,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(148,530,3,118,10,8,110,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(149,530,4,26,10,7,19,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33'),(150,530,5,51,10,7,44,NULL,NULL,'in_stock','2025-12-06 03:58:33','2025-12-06 03:58:33');
/*!40000 ALTER TABLE `product_stock_by_branch` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_variants_v2`
--

DROP TABLE IF EXISTS `product_variants_v2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_variants_v2` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `variant_name` varchar(255) DEFAULT NULL,
  `variant_signature` varchar(255) DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `price` decimal(15,2) DEFAULT '0.00',
  `cost_price` decimal(15,2) DEFAULT '0.00',
  `stock_quantity` decimal(10,2) DEFAULT '0.00',
  `min_stock` decimal(10,2) DEFAULT '0.00',
  `max_stock` decimal(10,2) DEFAULT '0.00',
  `image_url` varchar(255) DEFAULT NULL,
  `attributes` text,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_variants_sku_deleted_at` (`sku`,`deleted_at`),
  KEY `fk_product_variants_v2_product_id` (`product_id`),
  CONSTRAINT `fk_product_variants_v2_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7203 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_variants_v2`
--

LOCK TABLES `product_variants_v2` WRITE;
/*!40000 ALTER TABLE `product_variants_v2` DISABLE KEYS */;
INSERT INTO `product_variants_v2` VALUES (7051,505,'Áo Sơ Mi Công Sở Mẫu 5 - Size M',NULL,'SP005-M',NULL,1209000.00,725400.00,20.00,0.00,0.00,NULL,NULL,'active','2025-12-07 10:50:49','2025-12-07 10:50:49',NULL),(7052,505,'Áo Sơ Mi Công Sở Mẫu 5 - Size L',NULL,'SP005-L',NULL,1229000.00,725400.00,20.00,0.00,0.00,NULL,NULL,'active','2025-12-07 10:50:49','2025-12-07 10:50:49',NULL),(7101,510,'Quần Jeans Slimfit Mẫu 10 - Size M',NULL,'SP010-M',NULL,279000.00,167400.00,20.00,0.00,0.00,NULL,NULL,'active','2025-12-07 10:50:49','2025-12-07 10:50:49',NULL),(7102,510,'Quần Jeans Slimfit Mẫu 10 - Size L',NULL,'SP010-L',NULL,299000.00,167400.00,20.00,0.00,0.00,NULL,NULL,'active','2025-12-07 10:50:49','2025-12-07 10:50:49',NULL),(7151,515,'Đầm Dạ Hội Mẫu 15 - Size M',NULL,'SP015-M',NULL,325000.00,195000.00,20.00,0.00,0.00,NULL,NULL,'active','2025-12-07 10:50:49','2025-12-07 10:50:49',NULL),(7152,515,'Đầm Dạ Hội Mẫu 15 - Size L',NULL,'SP015-L',NULL,345000.00,195000.00,20.00,0.00,0.00,NULL,NULL,'active','2025-12-07 10:50:49','2025-12-07 10:50:49',NULL),(7201,520,'Giày Tây Nam Mẫu 20 - Size M',NULL,'SP020-M',NULL,363000.00,217800.00,20.00,0.00,0.00,NULL,NULL,'active','2025-12-07 10:50:49','2025-12-07 10:50:49',NULL),(7202,520,'Giày Tây Nam Mẫu 20 - Size L',NULL,'SP020-L',NULL,383000.00,217800.00,20.00,0.00,0.00,NULL,NULL,'active','2025-12-07 10:50:49','2025-12-07 10:50:49',NULL);
/*!40000 ALTER TABLE `product_variants_v2` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_warranties`
--

DROP TABLE IF EXISTS `product_warranties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_warranties` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `order_id` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `serial_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Số serial',
  `warranty_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Mã phiếu BH',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','expired','claimed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warranty_code` (`warranty_code`),
  KEY `idx_product` (`product_id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_warranty_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_warranty_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_warranty_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=141 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_warranties`
--

LOCK TABLES `product_warranties` WRITE;
/*!40000 ALTER TABLE `product_warranties` DISABLE KEYS */;
INSERT INTO `product_warranties` VALUES (121,510,NULL,NULL,'SN-000001','WAR-000001','2025-12-06','2026-12-06','active','Demo warranty 1',1,'2025-12-06 03:58:32','2025-12-06 03:58:32'),(122,510,NULL,NULL,'SN-000002','WAR-000002','2025-12-06','2026-12-06','active','Demo warranty 2',1,'2025-12-06 03:58:32','2025-12-06 03:58:32'),(123,510,NULL,NULL,'SN-000003','WAR-000003','2025-12-06','2026-12-06','active','Demo warranty 3',1,'2025-12-06 03:58:32','2025-12-06 03:58:32'),(124,510,NULL,NULL,'SN-000004','WAR-000004','2025-12-06','2026-12-06','active','Demo warranty 4',1,'2025-12-06 03:58:32','2025-12-06 03:58:32'),(125,510,NULL,NULL,'SN-000005','WAR-000005','2025-12-06','2026-12-06','active','Demo warranty 5',1,'2025-12-06 03:58:32','2025-12-06 03:58:32'),(126,510,NULL,NULL,'SN-000006','WAR-000006','2025-12-06','2026-12-06','active','Demo warranty 6',1,'2025-12-06 03:58:32','2025-12-06 03:58:32'),(127,510,NULL,NULL,'SN-000007','WAR-000007','2025-12-06','2026-12-06','active','Demo warranty 7',1,'2025-12-06 03:58:32','2025-12-06 03:58:32'),(128,510,NULL,NULL,'SN-000008','WAR-000008','2025-12-06','2026-12-06','active','Demo warranty 8',1,'2025-12-06 03:58:32','2025-12-06 03:58:32'),(129,510,NULL,NULL,'SN-000009','WAR-000009','2025-12-06','2026-12-06','active','Demo warranty 9',1,'2025-12-06 03:58:32','2025-12-06 03:58:32'),(130,510,NULL,NULL,'SN-000010','WAR-000010','2025-12-06','2026-12-06','active','Demo warranty 10',1,'2025-12-06 03:58:32','2025-12-06 03:58:32'),(131,510,NULL,NULL,'SN-000011','WAR-000011','2025-12-06','2026-12-06','active','Demo warranty 11',1,'2025-12-06 03:58:32','2025-12-06 03:58:32'),(132,510,NULL,NULL,'SN-000012','WAR-000012','2025-12-06','2026-12-06','active','Demo warranty 12',1,'2025-12-06 03:58:32','2025-12-06 03:58:32'),(133,510,NULL,NULL,'SN-000013','WAR-000013','2025-12-06','2026-12-06','active','Demo warranty 13',1,'2025-12-06 03:58:32','2025-12-06 03:58:32'),(134,510,NULL,NULL,'SN-000014','WAR-000014','2025-12-06','2026-12-06','active','Demo warranty 14',1,'2025-12-06 03:58:32','2025-12-06 03:58:32'),(135,510,NULL,NULL,'SN-000015','WAR-000015','2025-12-06','2026-12-06','active','Demo warranty 15',1,'2025-12-06 03:58:32','2025-12-06 03:58:32'),(136,510,NULL,NULL,'SN-000016','WAR-000016','2025-12-06','2026-12-06','active','Demo warranty 16',1,'2025-12-06 03:58:32','2025-12-06 03:58:32'),(137,510,NULL,NULL,'SN-000017','WAR-000017','2025-12-06','2026-12-06','active','Demo warranty 17',1,'2025-12-06 03:58:32','2025-12-06 03:58:32'),(138,510,NULL,NULL,'SN-000018','WAR-000018','2025-12-06','2026-12-06','active','Demo warranty 18',1,'2025-12-06 03:58:32','2025-12-06 03:58:32'),(139,510,NULL,NULL,'SN-000019','WAR-000019','2025-12-06','2026-12-06','active','Demo warranty 19',1,'2025-12-06 03:58:32','2025-12-06 03:58:32'),(140,510,NULL,NULL,'SN-000020','WAR-000020','2025-12-06','2026-12-06','active','Demo warranty 20',1,'2025-12-06 03:58:32','2025-12-06 03:58:32');
/*!40000 ALTER TABLE `product_warranties` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_type` varchar(50) DEFAULT NULL,
  `code` varchar(100) NOT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `brand` varchar(255) DEFAULT NULL,
  `warehouse_location` varchar(100) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `base_unit_code` varchar(50) DEFAULT NULL,
  `unit_conversion` decimal(10,2) DEFAULT '1.00',
  `has_variants` tinyint(1) DEFAULT '0',
  `related_product_codes` text,
  `image` varchar(255) DEFAULT NULL,
  `images` text,
  `image_count` int DEFAULT '0',
  `weight` decimal(10,2) DEFAULT NULL,
  `dimensions` varchar(100) DEFAULT NULL,
  `description` text,
  `content` text,
  `is_active` tinyint(1) DEFAULT '1',
  `is_available_online` tinyint(1) DEFAULT '0',
  `is_featured` tinyint(1) DEFAULT '0',
  `status` enum('active','inactive') DEFAULT 'active',
  `selling_price` decimal(10,2) DEFAULT '0.00',
  `commission_percent` decimal(5,2) DEFAULT '0.00',
  `commission_amount` decimal(15,2) DEFAULT '0.00',
  `wholesale_price` decimal(10,2) DEFAULT '0.00',
  `purchase_price` decimal(10,2) DEFAULT '0.00',
  `stock_quantity` int DEFAULT '0',
  `alert_stock` int DEFAULT '0',
  `expiry_days` int DEFAULT NULL,
  `customer_ordered` int DEFAULT '0',
  `expected_out_date` varchar(50) DEFAULT NULL,
  `min_stock_alert` int DEFAULT '0',
  `max_stock_alert` int DEFAULT '0',
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(500) DEFAULT NULL,
  `meta_keywords` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_products_code_deleted_at` (`code`,`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=521 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (501,'goods','SP001','SP001','Đầm Dạ Hội Mẫu 1','đầm-dạ-hội-mẫu-1','Lano Official',NULL,'Cái',NULL,1.00,0,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/9b9cf7569bd348fd9c73ab9c167a96b5.jpeg','[\"https:\\/\\/cdn2-retail-images.kiotviet.vn\\/2025\\/10\\/19\\/lano\\/9b9cf7569bd348fd9c73ab9c167a96b5.jpeg\"]',0,NULL,NULL,NULL,NULL,1,0,0,'active',1285000.00,0.00,0.00,0.00,771000.00,78,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49',NULL),(502,'goods','SP002','SP002','Áo Thun Nam Cao Cấp Mẫu 2','áo-thun-nam-cao-cấp-mẫu-2','Lano Official',NULL,'Cái',NULL,1.00,0,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/15/lano/677874c0246b4f93ab1843fb4ddab0a8.jpeg','[\"https:\\/\\/cdn2-retail-images.kiotviet.vn\\/2025\\/10\\/15\\/lano\\/677874c0246b4f93ab1843fb4ddab0a8.jpeg\"]',0,NULL,NULL,NULL,NULL,1,0,0,'active',818000.00,0.00,0.00,0.00,490800.00,68,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49',NULL),(503,'goods','SP003','SP003','Áo Thun Nam Cao Cấp Mẫu 3','áo-thun-nam-cao-cấp-mẫu-3','Lano Official',NULL,'Cái',NULL,1.00,0,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/67b7e3f88926487e873b064379fa451c.jpeg','[\"https:\\/\\/cdn2-retail-images.kiotviet.vn\\/2025\\/10\\/19\\/lano\\/67b7e3f88926487e873b064379fa451c.jpeg\"]',0,NULL,NULL,NULL,NULL,1,0,0,'active',890000.00,0.00,0.00,0.00,534000.00,42,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49',NULL),(504,'goods','SP004','SP004','Áo Thun Nam Cao Cấp Mẫu 4','áo-thun-nam-cao-cấp-mẫu-4','Lano Official',NULL,'Cái',NULL,1.00,0,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/1966289b43444855871891963240e946.jpeg','[\"https:\\/\\/cdn2-retail-images.kiotviet.vn\\/2025\\/10\\/19\\/lano\\/1966289b43444855871891963240e946.jpeg\"]',0,NULL,NULL,NULL,NULL,1,0,0,'active',1437000.00,0.00,0.00,0.00,862200.00,37,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49',NULL),(505,'goods','SP005','SP005','Áo Sơ Mi Công Sở Mẫu 5','áo-sơ-mi-công-sở-mẫu-5','Lano Official',NULL,'Cái',NULL,1.00,1,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/3fcf08b6f8b240fa99a661e56cdc7b3f.jpeg','[\"https:\\/\\/cdn2-retail-images.kiotviet.vn\\/2025\\/10\\/19\\/lano\\/3fcf08b6f8b240fa99a661e56cdc7b3f.jpeg\"]',0,NULL,NULL,NULL,NULL,1,0,0,'active',1209000.00,0.00,0.00,0.00,725400.00,66,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49',NULL),(506,'goods','SP006','SP006','Quần Jeans Slimfit Mẫu 6','quần-jeans-slimfit-mẫu-6','Lano Official',NULL,'Cái',NULL,1.00,0,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/9b9cf7569bd348fd9c73ab9c167a96b5.jpeg','[\"https:\\/\\/cdn2-retail-images.kiotviet.vn\\/2025\\/10\\/19\\/lano\\/9b9cf7569bd348fd9c73ab9c167a96b5.jpeg\"]',0,NULL,NULL,NULL,NULL,1,0,0,'active',706000.00,0.00,0.00,0.00,423600.00,85,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49',NULL),(507,'goods','SP007','SP007','Áo Thun Nam Cao Cấp Mẫu 7','áo-thun-nam-cao-cấp-mẫu-7','Lano Official',NULL,'Cái',NULL,1.00,0,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/15/lano/677874c0246b4f93ab1843fb4ddab0a8.jpeg','[\"https:\\/\\/cdn2-retail-images.kiotviet.vn\\/2025\\/10\\/15\\/lano\\/677874c0246b4f93ab1843fb4ddab0a8.jpeg\"]',0,NULL,NULL,NULL,NULL,1,0,0,'active',1006000.00,0.00,0.00,0.00,603600.00,48,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49',NULL),(508,'goods','SP008','SP008','Đầm Dạ Hội Mẫu 8','đầm-dạ-hội-mẫu-8','Lano Official',NULL,'Cái',NULL,1.00,0,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/67b7e3f88926487e873b064379fa451c.jpeg','[\"https:\\/\\/cdn2-retail-images.kiotviet.vn\\/2025\\/10\\/19\\/lano\\/67b7e3f88926487e873b064379fa451c.jpeg\"]',0,NULL,NULL,NULL,NULL,1,0,0,'active',310000.00,0.00,0.00,0.00,186000.00,65,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49',NULL),(509,'goods','SP009','SP009','Quần Jeans Slimfit Mẫu 9','quần-jeans-slimfit-mẫu-9','Lano Official',NULL,'Cái',NULL,1.00,0,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/1966289b43444855871891963240e946.jpeg','[\"https:\\/\\/cdn2-retail-images.kiotviet.vn\\/2025\\/10\\/19\\/lano\\/1966289b43444855871891963240e946.jpeg\"]',0,NULL,NULL,NULL,NULL,1,0,0,'active',278000.00,0.00,0.00,0.00,166800.00,11,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49',NULL),(510,'goods','SP010','SP010','Quần Jeans Slimfit Mẫu 10','quần-jeans-slimfit-mẫu-10','Lano Official',NULL,'Cái',NULL,1.00,1,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/3fcf08b6f8b240fa99a661e56cdc7b3f.jpeg','[\"https:\\/\\/cdn2-retail-images.kiotviet.vn\\/2025\\/10\\/19\\/lano\\/3fcf08b6f8b240fa99a661e56cdc7b3f.jpeg\"]',0,NULL,NULL,NULL,NULL,1,0,0,'active',279000.00,0.00,0.00,0.00,167400.00,12,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49',NULL),(511,'goods','SP011','SP011','Áo Sơ Mi Công Sở Mẫu 11','áo-sơ-mi-công-sở-mẫu-11','Lano Official',NULL,'Cái',NULL,1.00,0,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/9b9cf7569bd348fd9c73ab9c167a96b5.jpeg','[\"https:\\/\\/cdn2-retail-images.kiotviet.vn\\/2025\\/10\\/19\\/lano\\/9b9cf7569bd348fd9c73ab9c167a96b5.jpeg\"]',0,NULL,NULL,NULL,NULL,1,0,0,'active',1282000.00,0.00,0.00,0.00,769200.00,50,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49',NULL),(512,'goods','SP012','SP012','Đầm Dạ Hội Mẫu 12','đầm-dạ-hội-mẫu-12','Lano Official',NULL,'Cái',NULL,1.00,0,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/15/lano/677874c0246b4f93ab1843fb4ddab0a8.jpeg','[\"https:\\/\\/cdn2-retail-images.kiotviet.vn\\/2025\\/10\\/15\\/lano\\/677874c0246b4f93ab1843fb4ddab0a8.jpeg\"]',0,NULL,NULL,NULL,NULL,1,0,0,'active',691000.00,0.00,0.00,0.00,414600.00,26,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49',NULL),(513,'goods','SP013','SP013','Áo Sơ Mi Công Sở Mẫu 13','áo-sơ-mi-công-sở-mẫu-13','Lano Official',NULL,'Cái',NULL,1.00,0,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/67b7e3f88926487e873b064379fa451c.jpeg','[\"https:\\/\\/cdn2-retail-images.kiotviet.vn\\/2025\\/10\\/19\\/lano\\/67b7e3f88926487e873b064379fa451c.jpeg\"]',0,NULL,NULL,NULL,NULL,1,0,0,'active',603000.00,0.00,0.00,0.00,361800.00,20,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49',NULL),(514,'goods','SP014','SP014','Đầm Dạ Hội Mẫu 14','đầm-dạ-hội-mẫu-14','Lano Official',NULL,'Cái',NULL,1.00,0,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/1966289b43444855871891963240e946.jpeg','[\"https:\\/\\/cdn2-retail-images.kiotviet.vn\\/2025\\/10\\/19\\/lano\\/1966289b43444855871891963240e946.jpeg\"]',0,NULL,NULL,NULL,NULL,1,0,0,'active',1099000.00,0.00,0.00,0.00,659400.00,92,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49',NULL),(515,'goods','SP015','SP015','Đầm Dạ Hội Mẫu 15','đầm-dạ-hội-mẫu-15','Lano Official',NULL,'Cái',NULL,1.00,1,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/3fcf08b6f8b240fa99a661e56cdc7b3f.jpeg','[\"https:\\/\\/cdn2-retail-images.kiotviet.vn\\/2025\\/10\\/19\\/lano\\/3fcf08b6f8b240fa99a661e56cdc7b3f.jpeg\"]',0,NULL,NULL,NULL,NULL,1,0,0,'active',325000.00,0.00,0.00,0.00,195000.00,93,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49',NULL),(516,'goods','SP016','SP016','Áo Sơ Mi Công Sở Mẫu 16','áo-sơ-mi-công-sở-mẫu-16','Lano Official',NULL,'Cái',NULL,1.00,0,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/9b9cf7569bd348fd9c73ab9c167a96b5.jpeg','[\"https:\\/\\/cdn2-retail-images.kiotviet.vn\\/2025\\/10\\/19\\/lano\\/9b9cf7569bd348fd9c73ab9c167a96b5.jpeg\"]',0,NULL,NULL,NULL,NULL,1,0,0,'active',157000.00,0.00,0.00,0.00,94200.00,43,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49',NULL),(517,'goods','SP017','SP017','Giày Tây Nam Mẫu 17','giày-tây-nam-mẫu-17','Lano Official',NULL,'Cái',NULL,1.00,0,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/15/lano/677874c0246b4f93ab1843fb4ddab0a8.jpeg','[\"https:\\/\\/cdn2-retail-images.kiotviet.vn\\/2025\\/10\\/15\\/lano\\/677874c0246b4f93ab1843fb4ddab0a8.jpeg\"]',0,NULL,NULL,NULL,NULL,1,0,0,'active',1338000.00,0.00,0.00,0.00,802800.00,13,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49',NULL),(518,'goods','SP018','SP018','Giày Tây Nam Mẫu 18','giày-tây-nam-mẫu-18','Lano Official',NULL,'Cái',NULL,1.00,0,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/67b7e3f88926487e873b064379fa451c.jpeg','[\"https:\\/\\/cdn2-retail-images.kiotviet.vn\\/2025\\/10\\/19\\/lano\\/67b7e3f88926487e873b064379fa451c.jpeg\"]',0,NULL,NULL,NULL,NULL,1,0,0,'active',1335000.00,0.00,0.00,0.00,801000.00,20,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49',NULL),(519,'goods','SP019','SP019','Áo Thun Nam Cao Cấp Mẫu 19','áo-thun-nam-cao-cấp-mẫu-19','Lano Official',NULL,'Cái',NULL,1.00,0,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/1966289b43444855871891963240e946.jpeg','[\"https:\\/\\/cdn2-retail-images.kiotviet.vn\\/2025\\/10\\/19\\/lano\\/1966289b43444855871891963240e946.jpeg\"]',0,NULL,NULL,NULL,NULL,1,0,0,'active',388000.00,0.00,0.00,0.00,232800.00,19,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49',NULL),(520,'goods','SP020','SP020','Giày Tây Nam Mẫu 20','giày-tây-nam-mẫu-20','Lano Official',NULL,'Cái',NULL,1.00,1,NULL,'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/3fcf08b6f8b240fa99a661e56cdc7b3f.jpeg','[\"https:\\/\\/cdn2-retail-images.kiotviet.vn\\/2025\\/10\\/19\\/lano\\/3fcf08b6f8b240fa99a661e56cdc7b3f.jpeg\"]',0,NULL,NULL,NULL,NULL,1,0,0,'active',363000.00,0.00,0.00,0.00,217800.00,90,0,NULL,0,NULL,0,0,NULL,NULL,NULL,'2025-12-07 10:50:49','2025-12-07 10:50:49',NULL);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_price_lists`
--

DROP TABLE IF EXISTS `project_price_lists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_price_lists` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint unsigned NOT NULL,
  `price_list_id` bigint unsigned DEFAULT NULL,
  `valid_from` date DEFAULT NULL,
  `valid_to` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ppl_project` (`project_id`,`price_list_id`),
  KEY `fk_project_price_lists_price_list_id` (`price_list_id`),
  CONSTRAINT `fk_project_price_lists_price_list_id` FOREIGN KEY (`price_list_id`) REFERENCES `price_lists` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_project_price_lists_project_id` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_price_lists`
--

LOCK TABLES `project_price_lists` WRITE;
/*!40000 ALTER TABLE `project_price_lists` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_price_lists` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `projects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_name` varchar(255) NOT NULL,
  `project_code` varchar(80) DEFAULT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `status` varchar(30) DEFAULT 'open',
  `progress` decimal(5,2) DEFAULT '0.00',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `description` text,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_project_code` (`project_code`),
  KEY `idx_project_status` (`status`),
  KEY `fk_projects_customer_id` (`customer_id`),
  CONSTRAINT `fk_projects_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projects`
--

LOCK TABLES `projects` WRITE;
/*!40000 ALTER TABLE `projects` DISABLE KEYS */;
/*!40000 ALTER TABLE `projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `provinces`
--

DROP TABLE IF EXISTS `provinces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `provinces` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name_en` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `full_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `full_name_en` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `code_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_province_code` (`code`),
  KEY `idx_province_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `provinces`
--

LOCK TABLES `provinces` WRITE;
/*!40000 ALTER TABLE `provinces` DISABLE KEYS */;
INSERT INTO `provinces` VALUES (1,'01','Hà Nội','Ha Noi','Thành phố Hà Nội',NULL,NULL,1,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(2,'79','Hồ Chí Minh','Ho Chi Minh','Thành phố Hồ Chí Minh',NULL,NULL,2,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(3,'48','Đà Nẵng','Da Nang','Thành phố Đà Nẵng',NULL,NULL,3,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(4,'92','Cần Thơ','Can Tho','Thành phố Cần Thơ',NULL,NULL,4,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(5,'31','Hải Phòng','Hai Phong','Thành phố Hải Phòng',NULL,NULL,5,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(6,'02','Hà Giang','Ha Giang','Tỉnh Hà Giang',NULL,NULL,10,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(7,'04','Cao Bằng','Cao Bang','Tỉnh Cao Bằng',NULL,NULL,11,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(8,'06','Bắc Kạn','Bac Kan','Tỉnh Bắc Kạn',NULL,NULL,12,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(9,'08','Tuyên Quang','Tuyen Quang','Tỉnh Tuyên Quang',NULL,NULL,13,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(10,'10','Lào Cai','Lao Cai','Tỉnh Lào Cai',NULL,NULL,14,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(11,'11','Điện Biên','Dien Bien','Tỉnh Điện Biên',NULL,NULL,15,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(12,'12','Lai Châu','Lai Chau','Tỉnh Lai Châu',NULL,NULL,16,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(13,'14','Sơn La','Son La','Tỉnh Sơn La',NULL,NULL,17,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(14,'15','Yên Bái','Yen Bai','Tỉnh Yên Bái',NULL,NULL,18,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(15,'17','Hoà Bình','Hoa Binh','Tỉnh Hoà Bình',NULL,NULL,19,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(16,'19','Thái Nguyên','Thai Nguyen','Tỉnh Thái Nguyên',NULL,NULL,20,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(17,'20','Lạng Sơn','Lang Son','Tỉnh Lạng Sơn',NULL,NULL,21,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(18,'22','Quảng Ninh','Quang Ninh','Tỉnh Quảng Ninh',NULL,NULL,22,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(19,'24','Bắc Giang','Bac Giang','Tỉnh Bắc Giang',NULL,NULL,23,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(20,'25','Phú Thọ','Phu Tho','Tỉnh Phú Thọ',NULL,NULL,24,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(21,'26','Vĩnh Phúc','Vinh Phuc','Tỉnh Vĩnh Phúc',NULL,NULL,25,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(22,'27','Bắc Ninh','Bac Ninh','Tỉnh Bắc Ninh',NULL,NULL,26,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(23,'30','Hải Dương','Hai Duong','Tỉnh Hải Dương',NULL,NULL,27,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(24,'33','Hưng Yên','Hung Yen','Tỉnh Hưng Yên',NULL,NULL,28,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(25,'34','Thái Bình','Thai Binh','Tỉnh Thái Bình',NULL,NULL,29,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(26,'35','Hà Nam','Ha Nam','Tỉnh Hà Nam',NULL,NULL,30,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(27,'36','Nam Định','Nam Dinh','Tỉnh Nam Định',NULL,NULL,31,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(28,'37','Ninh Bình','Ninh Binh','Tỉnh Ninh Bình',NULL,NULL,32,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(29,'38','Thanh Hóa','Thanh Hoa','Tỉnh Thanh Hóa',NULL,NULL,40,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(30,'40','Nghệ An','Nghe An','Tỉnh Nghệ An',NULL,NULL,41,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(31,'42','Hà Tĩnh','Ha Tinh','Tỉnh Hà Tĩnh',NULL,NULL,42,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(32,'44','Quảng Bình','Quang Binh','Tỉnh Quảng Bình',NULL,NULL,43,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(33,'45','Quảng Trị','Quang Tri','Tỉnh Quảng Trị',NULL,NULL,44,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(34,'46','Thừa Thiên Huế','Thua Thien Hue','Tỉnh Thừa Thiên Huế',NULL,NULL,45,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(35,'49','Quảng Nam','Quang Nam','Tỉnh Quảng Nam',NULL,NULL,46,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(36,'51','Quảng Ngãi','Quang Ngai','Tỉnh Quảng Ngãi',NULL,NULL,47,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(37,'52','Bình Định','Binh Dinh','Tỉnh Bình Định',NULL,NULL,48,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(38,'54','Phú Yên','Phu Yen','Tỉnh Phú Yên',NULL,NULL,49,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(39,'56','Khánh Hòa','Khanh Hoa','Tỉnh Khánh Hòa',NULL,NULL,50,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(40,'58','Ninh Thuận','Ninh Thuan','Tỉnh Ninh Thuận',NULL,NULL,51,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(41,'60','Bình Thuận','Binh Thuan','Tỉnh Bình Thuận',NULL,NULL,52,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(42,'62','Kon Tum','Kon Tum','Tỉnh Kon Tum',NULL,NULL,60,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(43,'64','Gia Lai','Gia Lai','Tỉnh Gia Lai',NULL,NULL,61,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(44,'66','Đắk Lắk','Dak Lak','Tỉnh Đắk Lắk',NULL,NULL,62,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(45,'67','Đắk Nông','Dak Nong','Tỉnh Đắk Nông',NULL,NULL,63,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(46,'68','Lâm Đồng','Lam Dong','Tỉnh Lâm Đồng',NULL,NULL,64,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(47,'70','Bình Phước','Binh Phuoc','Tỉnh Bình Phước',NULL,NULL,70,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(48,'72','Tây Ninh','Tay Ninh','Tỉnh Tây Ninh',NULL,NULL,71,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(49,'74','Bình Dương','Binh Duong','Tỉnh Bình Dương',NULL,NULL,72,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(50,'75','Đồng Nai','Dong Nai','Tỉnh Đồng Nai',NULL,NULL,73,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(51,'77','Bà Rịa - Vũng Tàu','Ba Ria - Vung Tau','Tỉnh Bà Rịa - Vũng Tàu',NULL,NULL,74,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(52,'80','Long An','Long An','Tỉnh Long An',NULL,NULL,75,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(53,'82','Tiền Giang','Tien Giang','Tỉnh Tiền Giang',NULL,NULL,76,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(54,'83','Bến Tre','Ben Tre','Tỉnh Bến Tre',NULL,NULL,77,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(55,'84','Trà Vinh','Tra Vinh','Tỉnh Trà Vinh',NULL,NULL,78,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(56,'86','Vĩnh Long','Vinh Long','Tỉnh Vĩnh Long',NULL,NULL,79,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(57,'87','Đồng Tháp','Dong Thap','Tỉnh Đồng Tháp',NULL,NULL,80,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(58,'89','An Giang','An Giang','Tỉnh An Giang',NULL,NULL,81,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(59,'91','Kiên Giang','Kien Giang','Tỉnh Kiên Giang',NULL,NULL,82,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(60,'93','Hậu Giang','Hau Giang','Tỉnh Hậu Giang',NULL,NULL,83,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(61,'94','Sóc Trăng','Soc Trang','Tỉnh Sóc Trăng',NULL,NULL,84,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(62,'95','Bạc Liêu','Bac Lieu','Tỉnh Bạc Liêu',NULL,NULL,85,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(63,'96','Cà Mau','Ca Mau','Tỉnh Cà Mau',NULL,NULL,86,1,'2025-12-06 10:15:10','2025-12-06 10:15:10');
/*!40000 ALTER TABLE `provinces` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_invoice_items`
--

DROP TABLE IF EXISTS `purchase_invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_invoice_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `quantity` decimal(12,2) DEFAULT '0.00',
  `rate` decimal(14,2) DEFAULT '0.00',
  `amount` decimal(14,2) DEFAULT '0.00',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_purchase_invoice_item_invoice` (`invoice_id`),
  KEY `fk_purchase_invoice_ite_product_id` (`product_id`),
  CONSTRAINT `fk_purchase_invoice_ite_invoice_id` FOREIGN KEY (`invoice_id`) REFERENCES `purchase_invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_purchase_invoice_ite_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_invoice_items`
--

LOCK TABLES `purchase_invoice_items` WRITE;
/*!40000 ALTER TABLE `purchase_invoice_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_invoice_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_invoice_taxes`
--

DROP TABLE IF EXISTS `purchase_invoice_taxes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_invoice_taxes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint unsigned NOT NULL,
  `tax_name` varchar(150) NOT NULL,
  `rate_percent` decimal(8,3) DEFAULT '0.000',
  `amount` decimal(14,2) DEFAULT '0.00',
  `template_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_purchase_invoice_tax_invoice` (`invoice_id`),
  KEY `fk_purchase_invoice_tax_template_id` (`template_id`),
  CONSTRAINT `fk_purchase_invoice_tax_invoice_id` FOREIGN KEY (`invoice_id`) REFERENCES `purchase_invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_purchase_invoice_tax_template_id` FOREIGN KEY (`template_id`) REFERENCES `tax_templates` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_invoice_taxes`
--

LOCK TABLES `purchase_invoice_taxes` WRITE;
/*!40000 ALTER TABLE `purchase_invoice_taxes` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_invoice_taxes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_invoices`
--

DROP TABLE IF EXISTS `purchase_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) NOT NULL,
  `supplier_id` bigint unsigned DEFAULT NULL,
  `posting_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'draft',
  `currency` varchar(10) DEFAULT 'VND',
  `exchange_rate` decimal(12,4) DEFAULT '1.0000',
  `total` decimal(14,2) DEFAULT '0.00',
  `taxes_total` decimal(14,2) DEFAULT '0.00',
  `grand_total` decimal(14,2) DEFAULT '0.00',
  `rounding_adjustment` decimal(12,2) DEFAULT '0.00',
  `debit_account_id` bigint unsigned DEFAULT NULL,
  `credit_account_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_purchase_invoice_number` (`invoice_number`),
  KEY `idx_purchase_invoice_supplier` (`supplier_id`),
  KEY `fk_purchase_invoices_credit_account_id` (`credit_account_id`),
  KEY `fk_purchase_invoices_debit_account_id` (`debit_account_id`),
  CONSTRAINT `fk_purchase_invoices_credit_account_id` FOREIGN KEY (`credit_account_id`) REFERENCES `chart_of_accounts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_purchase_invoices_debit_account_id` FOREIGN KEY (`debit_account_id`) REFERENCES `chart_of_accounts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_purchase_invoices_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_invoices`
--

LOCK TABLES `purchase_invoices` WRITE;
/*!40000 ALTER TABLE `purchase_invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_order_items`
--

DROP TABLE IF EXISTS `purchase_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_order_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `quantity` decimal(14,3) DEFAULT '0.000',
  `received_quantity` decimal(14,3) DEFAULT '0.000',
  `rate` decimal(14,2) DEFAULT '0.00',
  `amount` decimal(14,2) DEFAULT '0.00',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `unit_price` decimal(14,2) DEFAULT NULL,
  `total_price` decimal(14,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_po_item_po` (`purchase_order_id`),
  KEY `fk_purchase_order_items_product_id` (`product_id`),
  CONSTRAINT `fk_purchase_order_items_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_purchase_order_items_purchase_order_id` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_order_items`
--

LOCK TABLES `purchase_order_items` WRITE;
/*!40000 ALTER TABLE `purchase_order_items` DISABLE KEYS */;
INSERT INTO `purchase_order_items` VALUES (51,41,501,100.000,0.000,0.00,0.00,'2025-12-06 03:58:33','2025-12-06 03:58:33',400000.00,40000000.00),(52,41,502,20.000,0.000,0.00,0.00,'2025-12-06 03:58:33','2025-12-06 03:58:33',500000.00,10000000.00),(53,42,503,50.000,0.000,0.00,0.00,'2025-12-06 03:58:33','2025-12-06 03:58:33',450000.00,22500000.00),(54,42,501,25.000,0.000,0.00,0.00,'2025-12-06 03:58:33','2025-12-06 03:58:33',500000.00,12500000.00),(55,43,502,30.000,0.000,0.00,0.00,'2025-12-06 03:58:33','2025-12-06 03:58:33',1500000.00,45000000.00),(56,44,501,150.000,0.000,0.00,0.00,'2025-12-06 03:58:33','2025-12-06 03:58:33',400000.00,60000000.00),(57,45,502,50.000,0.000,0.00,0.00,'2025-12-06 03:58:33','2025-12-06 03:58:33',500000.00,25000000.00),(58,46,503,80.000,0.000,0.00,0.00,'2025-12-06 03:58:33','2025-12-06 03:58:33',500000.00,40000000.00),(59,47,501,110.000,0.000,0.00,0.00,'2025-12-06 03:58:33','2025-12-06 03:58:33',500000.00,55000000.00),(60,48,502,20.000,0.000,0.00,0.00,'2025-12-06 03:58:33','2025-12-06 03:58:33',1500000.00,30000000.00);
/*!40000 ALTER TABLE `purchase_order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `po_number` varchar(50) DEFAULT NULL,
  `code` varchar(50) DEFAULT NULL,
  `supplier_id` bigint unsigned DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `partner_id` bigint unsigned DEFAULT NULL,
  `warehouse_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `order_number` varchar(50) DEFAULT NULL,
  `order_date` datetime DEFAULT NULL,
  `expected_date` datetime DEFAULT NULL,
  `received_date` datetime DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `shipping_fee` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` varchar(50) DEFAULT 'draft',
  `received_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `total_amount` decimal(14,2) DEFAULT '0.00',
  `paid_amount` decimal(14,2) DEFAULT '0.00',
  `payment_status` varchar(50) DEFAULT 'unpaid',
  `notes` text,
  `created_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_purchase_orders_branch_id` (`branch_id`),
  KEY `purchase_orders_partner_id_foreign` (`partner_id`),
  KEY `purchase_orders_supplier_id_foreign` (`supplier_id`),
  KEY `purchase_orders_warehouse_id_foreign` (`warehouse_id`),
  KEY `purchase_orders_user_id_foreign` (`user_id`),
  KEY `purchase_orders_created_by_foreign` (`created_by`),
  CONSTRAINT `fk_purchase_orders_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `purchase_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `purchase_orders_partner_id_foreign` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `purchase_orders_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `purchase_orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `purchase_orders_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_orders`
--

LOCK TABLES `purchase_orders` WRITE;
/*!40000 ALTER TABLE `purchase_orders` DISABLE KEYS */;
INSERT INTO `purchase_orders` VALUES (41,NULL,NULL,1,1,1,NULL,1,'PO-2024-001','2025-11-06 03:58:33','2025-11-13 03:58:33',NULL,NULL,50000000.00,0.00,0.00,0.00,'completed',NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL,0.00,50000000.00,'paid','Đơn hàng đầu tiên trong tháng',1),(42,NULL,NULL,2,1,2,NULL,2,'PO-2024-002','2025-11-11 03:58:33','2025-11-18 03:58:33',NULL,NULL,35000000.00,0.00,0.00,0.00,'completed',NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL,0.00,35000000.00,'paid','Nhập hàng túi xách',2),(43,NULL,NULL,3,2,3,NULL,1,'PO-2024-003','2025-11-16 03:58:33','2025-11-23 03:58:33',NULL,NULL,45000000.00,0.00,0.00,0.00,'received',NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL,0.00,22500000.00,'partial','Đã nhận hàng, chờ thanh toán phần còn lại',1),(44,NULL,NULL,4,1,4,NULL,2,'PO-2024-004','2025-11-21 03:58:33','2025-11-28 03:58:33',NULL,NULL,60000000.00,0.00,0.00,0.00,'in_transit',NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL,0.00,30000000.00,'partial','Hàng đang trên đường về kho',2),(45,NULL,NULL,5,2,5,NULL,1,'PO-2024-005','2025-11-26 03:58:33','2025-12-03 03:58:33',NULL,NULL,25000000.00,0.00,0.00,0.00,'confirmed',NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL,0.00,0.00,'unpaid','Nhà cung cấp đã xác nhận đơn',1),(46,NULL,NULL,6,1,6,NULL,2,'PO-2024-006','2025-11-29 03:58:33','2025-12-13 03:58:33',NULL,NULL,40000000.00,0.00,0.00,0.00,'pending',NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL,0.00,0.00,'unpaid','Chờ nhà cung cấp xác nhận',2),(47,NULL,NULL,7,2,7,NULL,1,'PO-2024-007','2025-12-01 03:58:33','2025-12-16 03:58:33',NULL,NULL,55000000.00,0.00,0.00,0.00,'draft',NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL,0.00,0.00,'unpaid','Đơn nháp, chưa gửi cho nhà cung cấp',1),(48,NULL,NULL,8,1,8,NULL,2,'PO-2024-008','2025-12-03 03:58:33','2025-12-18 03:58:33',NULL,NULL,30000000.00,0.00,0.00,0.00,'cancelled',NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL,0.00,0.00,'unpaid','Hủy do nhà cung cấp không đủ hàng',2);
/*!40000 ALTER TABLE `purchase_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_suggestions`
--

DROP TABLE IF EXISTS `purchase_suggestions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_suggestions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reorder_level_id` bigint unsigned DEFAULT NULL,
  `product_id` bigint unsigned NOT NULL,
  `variant_id` bigint unsigned DEFAULT NULL,
  `branch_id` bigint unsigned NOT NULL,
  `generated_for_date` date NOT NULL,
  `suggested_qty` decimal(12,3) DEFAULT '0.000',
  `on_hand_qty` decimal(12,3) DEFAULT '0.000',
  `reserved_qty` decimal(12,3) DEFAULT '0.000',
  `available_qty` decimal(12,3) DEFAULT '0.000',
  `min_level` decimal(12,3) DEFAULT '0.000',
  `max_level` decimal(12,3) DEFAULT '0.000',
  `safety_stock` decimal(12,3) DEFAULT '0.000',
  `status` varchar(30) DEFAULT 'pending',
  `reason` varchar(255) DEFAULT NULL,
  `purchase_order_id` bigint unsigned DEFAULT NULL,
  `acknowledged_by` bigint unsigned DEFAULT NULL,
  `acknowledged_at` datetime DEFAULT NULL,
  `converted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_purchase_suggestion_day` (`product_id`,`variant_id`,`branch_id`,`generated_for_date`),
  KEY `idx_purchase_suggestion_status` (`branch_id`,`status`),
  KEY `fk_purchase_suggestions_purchase_order_id` (`purchase_order_id`),
  KEY `fk_purchase_suggestions_reorder_level_id` (`reorder_level_id`),
  KEY `fk_purchase_suggestions_variant_id` (`variant_id`),
  CONSTRAINT `fk_purchase_suggestions_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_purchase_suggestions_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_purchase_suggestions_purchase_order_id` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_purchase_suggestions_reorder_level_id` FOREIGN KEY (`reorder_level_id`) REFERENCES `reorder_levels` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_purchase_suggestions_variant_id` FOREIGN KEY (`variant_id`) REFERENCES `product_variants_v2` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_suggestions`
--

LOCK TABLES `purchase_suggestions` WRITE;
/*!40000 ALTER TABLE `purchase_suggestions` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_suggestions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quality_inspection_items`
--

DROP TABLE IF EXISTS `quality_inspection_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quality_inspection_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `inspection_id` bigint unsigned NOT NULL,
  `parameter_id` bigint unsigned NOT NULL,
  `parameter_name` varchar(150) NOT NULL,
  `uom` varchar(50) DEFAULT NULL,
  `value_numeric` decimal(14,4) DEFAULT NULL,
  `value_text` varchar(255) DEFAULT NULL,
  `pass_flag` tinyint(1) DEFAULT NULL,
  `notes` text,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_quality_item_inspection` (`inspection_id`),
  KEY `idx_quality_item_parameter` (`parameter_id`),
  CONSTRAINT `fk_quality_inspection_i_inspection_id` FOREIGN KEY (`inspection_id`) REFERENCES `quality_inspections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_quality_inspection_i_parameter_id` FOREIGN KEY (`parameter_id`) REFERENCES `quality_parameters` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quality_inspection_items`
--

LOCK TABLES `quality_inspection_items` WRITE;
/*!40000 ALTER TABLE `quality_inspection_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `quality_inspection_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quality_inspections`
--

DROP TABLE IF EXISTS `quality_inspections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quality_inspections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reference_type` varchar(80) NOT NULL,
  `reference_id` bigint unsigned NOT NULL,
  `status` varchar(20) DEFAULT 'draft',
  `result` varchar(20) DEFAULT 'pending',
  `inspected_by` bigint unsigned DEFAULT NULL,
  `inspected_at` datetime DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejected_by` bigint unsigned DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `notes` text,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_quality_reference` (`reference_type`,`reference_id`),
  KEY `idx_quality_status` (`status`),
  KEY `idx_quality_result` (`result`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quality_inspections`
--

LOCK TABLES `quality_inspections` WRITE;
/*!40000 ALTER TABLE `quality_inspections` DISABLE KEYS */;
/*!40000 ALTER TABLE `quality_inspections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quality_parameters`
--

DROP TABLE IF EXISTS `quality_parameters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quality_parameters` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `uom` varchar(50) DEFAULT NULL,
  `min_value` decimal(14,4) DEFAULT NULL,
  `max_value` decimal(14,4) DEFAULT NULL,
  `specification` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_quality_parameter_name` (`name`),
  KEY `idx_quality_parameter_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quality_parameters`
--

LOCK TABLES `quality_parameters` WRITE;
/*!40000 ALTER TABLE `quality_parameters` DISABLE KEYS */;
/*!40000 ALTER TABLE `quality_parameters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quotation_items`
--

DROP TABLE IF EXISTS `quotation_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quotation_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `quotation_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `quantity` decimal(14,2) DEFAULT '1.00',
  `price` decimal(14,2) DEFAULT '0.00',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_quote_items_quote` (`quotation_id`),
  KEY `fk_quotation_items_product_id` (`product_id`),
  CONSTRAINT `fk_quotation_items_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_quotation_items_quotation_id` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quotation_items`
--

LOCK TABLES `quotation_items` WRITE;
/*!40000 ALTER TABLE `quotation_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `quotation_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quotations`
--

DROP TABLE IF EXISTS `quotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quotations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `quote_number` varchar(50) DEFAULT NULL,
  `opportunity_id` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `lead_id` bigint unsigned DEFAULT NULL,
  `status` varchar(50) DEFAULT 'draft',
  `validity_date` date DEFAULT NULL,
  `subtotal` decimal(14,2) DEFAULT '0.00',
  `discount_total` decimal(14,2) DEFAULT '0.00',
  `tax_total` decimal(14,2) DEFAULT '0.00',
  `total` decimal(14,2) DEFAULT '0.00',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_quotations_customer_id` (`customer_id`),
  KEY `fk_quotations_lead_id` (`lead_id`),
  KEY `fk_quotations_opportunity_id` (`opportunity_id`),
  CONSTRAINT `fk_quotations_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_quotations_lead_id` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_quotations_opportunity_id` FOREIGN KEY (`opportunity_id`) REFERENCES `opportunities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quotations`
--

LOCK TABLES `quotations` WRITE;
/*!40000 ALTER TABLE `quotations` DISABLE KEYS */;
/*!40000 ALTER TABLE `quotations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `regional_tax_rules`
--

DROP TABLE IF EXISTS `regional_tax_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `regional_tax_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `country` varchar(5) NOT NULL,
  `rule_json` json NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_region_country` (`country`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `regional_tax_rules`
--

LOCK TABLES `regional_tax_rules` WRITE;
/*!40000 ALTER TABLE `regional_tax_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `regional_tax_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reorder_levels`
--

DROP TABLE IF EXISTS `reorder_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reorder_levels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `variant_id` bigint unsigned DEFAULT NULL,
  `branch_id` bigint unsigned NOT NULL,
  `min_level` decimal(12,3) DEFAULT '0.000',
  `max_level` decimal(12,3) DEFAULT '0.000',
  `safety_stock` decimal(12,3) DEFAULT '0.000',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reorder_level` (`product_id`,`variant_id`,`branch_id`),
  KEY `idx_reorder_branch` (`branch_id`,`product_id`),
  KEY `fk_reorder_levels_variant_id` (`variant_id`),
  CONSTRAINT `fk_reorder_levels_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reorder_levels_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reorder_levels_variant_id` FOREIGN KEY (`variant_id`) REFERENCES `product_variants_v2` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reorder_levels`
--

LOCK TABLES `reorder_levels` WRITE;
/*!40000 ALTER TABLE `reorder_levels` DISABLE KEYS */;
/*!40000 ALTER TABLE `reorder_levels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `return_items`
--

DROP TABLE IF EXISTS `return_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `return_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `return_id` bigint unsigned DEFAULT NULL,
  `order_item_id` bigint unsigned DEFAULT NULL,
  `quantity_returned` decimal(14,3) DEFAULT NULL,
  `item_condition` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_return_items_return` (`return_id`),
  KEY `idx_return_items_order_item` (`order_item_id`),
  CONSTRAINT `fk_return_items_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_return_items_return` FOREIGN KEY (`return_id`) REFERENCES `returns` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `return_items`
--

LOCK TABLES `return_items` WRITE;
/*!40000 ALTER TABLE `return_items` DISABLE KEYS */;
INSERT INTO `return_items` VALUES (36,36,400,1.000,'damaged','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(37,37,403,1.000,'used','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(38,38,406,1.000,'new','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(39,39,411,1.000,'new','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(40,40,429,1.000,'new','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(41,41,442,1.000,'damaged','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(42,42,443,1.000,'opened','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(43,43,448,1.000,'damaged','2025-12-06 03:58:33','2025-12-06 03:58:33',NULL);
/*!40000 ALTER TABLE `return_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `returns`
--

DROP TABLE IF EXISTS `returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `returns` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `return_number` varchar(50) DEFAULT NULL,
  `order_id` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `return_amount` decimal(14,2) DEFAULT NULL,
  `refund_shipping_fee` tinyint(1) DEFAULT NULL,
  `refund_amount` decimal(14,2) DEFAULT NULL,
  `refund_method` varchar(50) DEFAULT NULL,
  `reason` varchar(50) DEFAULT NULL,
  `reason_detail` text,
  `status` varchar(50) DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejected_by` bigint unsigned DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `notes` text,
  `lock_version` int DEFAULT '0',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `return_number` (`return_number`),
  KEY `idx_returns_order` (`order_id`),
  KEY `idx_returns_customer` (`customer_id`),
  CONSTRAINT `fk_returns_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_returns_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `returns`
--

LOCK TABLES `returns` WRITE;
/*!40000 ALTER TABLE `returns` DISABLE KEYS */;
INSERT INTO `returns` VALUES (36,'RET-DEMO-001',220,2012,206000.00,0,206000.00,'cash','defective',NULL,'approved',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(37,'RET-DEMO-002',221,2013,155400.00,0,155400.00,'bank_transfer','not_satisfied',NULL,'completed',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(38,'RET-DEMO-003',223,2015,204000.00,0,0.00,NULL,'wrong_item',NULL,'pending',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(39,'RET-DEMO-004',225,2017,206000.00,0,0.00,NULL,'other','Không phù hợp với nhu cầu','rejected',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(40,'RET-DEMO-025',235,2019,276000.00,0,276000.00,'cash','wrong_item',NULL,'completed',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(41,'RET-DEMO-031',241,2011,248000.00,0,248000.00,'cash','wrong_item',NULL,'approved',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(42,'RET-DEMO-032',242,2011,228000.00,0,0.00,'cash','other',NULL,'pending',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL),(43,'RET-DEMO-035',245,2011,270000.00,0,0.00,'cash','wrong_item',NULL,'pending',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2025-12-06 03:58:33','2025-12-06 03:58:33',NULL);
/*!40000 ALTER TABLE `returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
INSERT INTO `role_has_permissions` VALUES (1,1),(1,2),(1,3),(2,1),(3,1),(3,2),(3,3),(4,1),(4,2);
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'super-admin','api','Super Admin',1,'2025-12-06 03:04:12',NULL,NULL),(2,'manager','api','Quản lý',0,'2025-12-06 03:04:12',NULL,NULL),(3,'viewer','api','Xem chỉ đọc',0,'2025-12-06 03:04:12',NULL,NULL);
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salary_components`
--

DROP TABLE IF EXISTS `salary_components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `salary_components` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `salary_slip_id` bigint unsigned NOT NULL,
  `component_name` varchar(150) NOT NULL,
  `component_type` varchar(20) DEFAULT 'earning',
  `amount` decimal(14,2) DEFAULT '0.00',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_salary_component_slip` (`salary_slip_id`),
  CONSTRAINT `fk_salary_components_salary_slip_id` FOREIGN KEY (`salary_slip_id`) REFERENCES `salary_slips` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salary_components`
--

LOCK TABLES `salary_components` WRITE;
/*!40000 ALTER TABLE `salary_components` DISABLE KEYS */;
/*!40000 ALTER TABLE `salary_components` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salary_slips`
--

DROP TABLE IF EXISTS `salary_slips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `salary_slips` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payroll_entry_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `status` varchar(30) DEFAULT 'draft',
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `total_earnings` decimal(14,2) DEFAULT '0.00',
  `total_deductions` decimal(14,2) DEFAULT '0.00',
  `net_pay` decimal(14,2) DEFAULT '0.00',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_salary_slip` (`payroll_entry_id`,`employee_id`),
  KEY `fk_salary_slips_employee_id` (`employee_id`),
  CONSTRAINT `fk_salary_slips_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_salary_slips_payroll_entry_id` FOREIGN KEY (`payroll_entry_id`) REFERENCES `payroll_entries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salary_slips`
--

LOCK TABLES `salary_slips` WRITE;
/*!40000 ALTER TABLE `salary_slips` DISABLE KEYS */;
/*!40000 ALTER TABLE `salary_slips` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_channels`
--

DROP TABLE IF EXISTS `sales_channels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_channels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `icon` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `color` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0',
  `settings` json DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sales_channel_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_channels`
--

LOCK TABLES `sales_channels` WRITE;
/*!40000 ALTER TABLE `sales_channels` DISABLE KEYS */;
INSERT INTO `sales_channels` VALUES (1,'pos','Bán tại quầy','Bán hàng trực tiếp tại cửa hàng','store','#4CAF50',1,1,1,'{\"allow_debt\": true, \"require_customer\": false}','2025-12-06 10:35:15','2025-12-06 10:35:15'),(2,'delivery','Giao hàng','Đơn hàng giao đến khách','truck','#2196F3',1,0,2,'{\"require_phone\": true, \"require_address\": true}','2025-12-06 10:35:15','2025-12-06 10:35:15'),(3,'online','Bán online','Đơn từ website, app','globe','#9C27B0',1,0,3,'{\"auto_confirm\": false}','2025-12-06 10:35:15','2025-12-06 10:35:15'),(4,'marketplace','Sàn TMĐT','Shopee, Lazada, Tiki...','shopping-bag','#FF5722',1,0,4,'{\"platforms\": [\"shopee\", \"lazada\", \"tiki\"]}','2025-12-06 10:35:15','2025-12-06 10:35:15');
/*!40000 ALTER TABLE `sales_channels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_invoice_items`
--

DROP TABLE IF EXISTS `sales_invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_invoice_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `quantity` decimal(12,2) DEFAULT '0.00',
  `rate` decimal(14,2) DEFAULT '0.00',
  `amount` decimal(14,2) DEFAULT '0.00',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sales_invoice_item_invoice` (`invoice_id`),
  KEY `fk_sales_invoice_items_product_id` (`product_id`),
  CONSTRAINT `fk_sales_invoice_items_invoice_id` FOREIGN KEY (`invoice_id`) REFERENCES `sales_invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_sales_invoice_items_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_invoice_items`
--

LOCK TABLES `sales_invoice_items` WRITE;
/*!40000 ALTER TABLE `sales_invoice_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales_invoice_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_invoice_taxes`
--

DROP TABLE IF EXISTS `sales_invoice_taxes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_invoice_taxes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint unsigned NOT NULL,
  `tax_name` varchar(150) NOT NULL,
  `rate_percent` decimal(8,3) DEFAULT '0.000',
  `amount` decimal(14,2) DEFAULT '0.00',
  `template_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sales_invoice_tax_invoice` (`invoice_id`),
  KEY `fk_sales_invoice_taxes_template_id` (`template_id`),
  CONSTRAINT `fk_sales_invoice_taxes_invoice_id` FOREIGN KEY (`invoice_id`) REFERENCES `sales_invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_sales_invoice_taxes_template_id` FOREIGN KEY (`template_id`) REFERENCES `tax_templates` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_invoice_taxes`
--

LOCK TABLES `sales_invoice_taxes` WRITE;
/*!40000 ALTER TABLE `sales_invoice_taxes` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales_invoice_taxes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_invoices`
--

DROP TABLE IF EXISTS `sales_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `posting_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'draft',
  `currency` varchar(10) DEFAULT 'VND',
  `exchange_rate` decimal(12,4) DEFAULT '1.0000',
  `total` decimal(14,2) DEFAULT '0.00',
  `taxes_total` decimal(14,2) DEFAULT '0.00',
  `grand_total` decimal(14,2) DEFAULT '0.00',
  `rounding_adjustment` decimal(12,2) DEFAULT '0.00',
  `debit_account_id` bigint unsigned DEFAULT NULL,
  `credit_account_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sales_invoice_number` (`invoice_number`),
  KEY `idx_sales_invoice_customer` (`customer_id`),
  KEY `fk_sales_invoices_credit_account_id` (`credit_account_id`),
  KEY `fk_sales_invoices_debit_account_id` (`debit_account_id`),
  CONSTRAINT `fk_sales_invoices_credit_account_id` FOREIGN KEY (`credit_account_id`) REFERENCES `chart_of_accounts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_sales_invoices_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_sales_invoices_debit_account_id` FOREIGN KEY (`debit_account_id`) REFERENCES `chart_of_accounts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_invoices`
--

LOCK TABLES `sales_invoices` WRITE;
/*!40000 ALTER TABLE `sales_invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `scheduler_rules`
--

DROP TABLE IF EXISTS `scheduler_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scheduler_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `cron_expression` varchar(60) NOT NULL,
  `handler` varchar(120) NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `last_run_at` datetime DEFAULT NULL,
  `next_run_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_scheduler_name` (`name`),
  KEY `idx_scheduler_active` (`is_active`,`next_run_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `scheduler_rules`
--

LOCK TABLES `scheduler_rules` WRITE;
/*!40000 ALTER TABLE `scheduler_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `scheduler_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `token` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `device_info` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_activity` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `fk_sessions_user_id` (`user_id`),
  CONSTRAINT `fk_sessions_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shipping_rates`
--

DROP TABLE IF EXISTS `shipping_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shipping_rates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `zone_id` bigint unsigned NOT NULL,
  `delivery_partner_id` bigint unsigned DEFAULT NULL,
  `min_weight` decimal(10,2) NOT NULL DEFAULT '0.00',
  `max_weight` decimal(10,2) NOT NULL DEFAULT '999999.00',
  `min_value` decimal(14,2) NOT NULL DEFAULT '0.00',
  `max_value` decimal(14,2) NOT NULL DEFAULT '999999999.00',
  `base_fee` decimal(14,2) NOT NULL DEFAULT '0.00',
  `per_kg_fee` decimal(14,2) NOT NULL DEFAULT '0.00',
  `free_shipping_threshold` decimal(14,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_shipping_rate_zone` (`zone_id`),
  KEY `idx_shipping_rate_partner` (`delivery_partner_id`),
  CONSTRAINT `shipping_rates_zone_id_foreign` FOREIGN KEY (`zone_id`) REFERENCES `shipping_zones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipping_rates`
--

LOCK TABLES `shipping_rates` WRITE;
/*!40000 ALTER TABLE `shipping_rates` DISABLE KEYS */;
INSERT INTO `shipping_rates` VALUES (1,1,NULL,0.00,999999.00,0.00,999999999.00,20000.00,0.00,500000.00,1,'2025-12-06 10:35:15','2025-12-06 10:35:15'),(2,2,NULL,0.00,999999.00,0.00,999999999.00,20000.00,0.00,500000.00,1,'2025-12-06 10:35:15','2025-12-06 10:35:15'),(3,3,NULL,0.00,999999.00,0.00,999999999.00,30000.00,5000.00,1000000.00,1,'2025-12-06 10:35:15','2025-12-06 10:35:15'),(4,4,NULL,0.00,999999.00,0.00,999999999.00,35000.00,6000.00,1000000.00,1,'2025-12-06 10:35:15','2025-12-06 10:35:15'),(5,5,NULL,0.00,999999.00,0.00,999999999.00,30000.00,5000.00,1000000.00,1,'2025-12-06 10:35:15','2025-12-06 10:35:15'),(6,6,NULL,0.00,999999.00,0.00,999999999.00,40000.00,7000.00,NULL,1,'2025-12-06 10:35:15','2025-12-06 10:35:15');
/*!40000 ALTER TABLE `shipping_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shipping_zones`
--

DROP TABLE IF EXISTS `shipping_zones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shipping_zones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `province_ids` json DEFAULT NULL COMMENT 'Array of province IDs',
  `district_ids` json DEFAULT NULL COMMENT 'Array of district IDs',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipping_zones`
--

LOCK TABLES `shipping_zones` WRITE;
/*!40000 ALTER TABLE `shipping_zones` DISABLE KEYS */;
INSERT INTO `shipping_zones` VALUES (1,'Nội thành Hà Nội','[1]','[1, 2, 3, 4, 5]',1,1,'2025-12-06 10:35:15','2025-12-06 10:35:15'),(2,'Nội thành HCM','[2]','[21, 22, 23, 24, 25]',1,2,'2025-12-06 10:35:15','2025-12-06 10:35:15'),(3,'Miền Bắc','[1, 4, 5, 6, 7, 8, 9, 10]','[]',1,3,'2025-12-06 10:35:15','2025-12-06 10:35:15'),(4,'Miền Trung','[3, 11, 12, 13, 14, 15, 16]','[]',1,4,'2025-12-06 10:35:15','2025-12-06 10:35:15'),(5,'Miền Nam','[2, 17, 18, 19, 20]','[]',1,5,'2025-12-06 10:35:15','2025-12-06 10:35:15'),(6,'Toàn quốc (mặc định)','[]','[]',1,99,'2025-12-06 10:35:15','2025-12-06 10:35:15');
/*!40000 ALTER TABLE `shipping_zones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_bins`
--

DROP TABLE IF EXISTS `stock_bins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_bins` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `variant_id` bigint unsigned DEFAULT NULL,
  `branch_id` bigint unsigned NOT NULL,
  `batch_id` bigint unsigned DEFAULT NULL,
  `on_hand_qty` decimal(12,3) DEFAULT '0.000',
  `reserved_qty` decimal(12,3) DEFAULT '0.000',
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_stock_bin` (`product_id`,`variant_id`,`branch_id`,`batch_id`),
  KEY `fk_stock_bins_variant_id` (`variant_id`),
  KEY `fk_stock_bins_branch_id` (`branch_id`),
  KEY `fk_stock_bins_batch_id` (`batch_id`),
  CONSTRAINT `fk_stock_bins_batch_id` FOREIGN KEY (`batch_id`) REFERENCES `product_batches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_stock_bins_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_stock_bins_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_stock_bins_variant_id` FOREIGN KEY (`variant_id`) REFERENCES `product_variants_v2` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_bins`
--

LOCK TABLES `stock_bins` WRITE;
/*!40000 ALTER TABLE `stock_bins` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_bins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_disposal_items`
--

DROP TABLE IF EXISTS `stock_disposal_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_disposal_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `disposal_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `variant_id` bigint unsigned DEFAULT NULL,
  `sku` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `quantity` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `cost_price` decimal(18,4) NOT NULL DEFAULT '0.0000',
  `disposal_value` decimal(18,4) NOT NULL DEFAULT '0.0000',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disposal_id` (`disposal_id`),
  KEY `product_id` (`product_id`),
  KEY `variant_id` (`variant_id`),
  CONSTRAINT `stock_disposal_items_disposal_id_foreign` FOREIGN KEY (`disposal_id`) REFERENCES `stock_disposals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `stock_disposal_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `stock_disposal_items_variant_id_foreign` FOREIGN KEY (`variant_id`) REFERENCES `product_variants_v2` (`id`) ON DELETE CASCADE ON UPDATE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_disposal_items`
--

LOCK TABLES `stock_disposal_items` WRITE;
/*!40000 ALTER TABLE `stock_disposal_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_disposal_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_disposals`
--

DROP TABLE IF EXISTS `stock_disposals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_disposals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `branch_id` bigint unsigned NOT NULL,
  `status` enum('draft','completed','cancelled') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'draft',
  `disposed_at` datetime DEFAULT NULL,
  `total_quantity` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `total_value` decimal(18,4) NOT NULL DEFAULT '0.0000',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `executor_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `branch_id` (`branch_id`),
  KEY `status` (`status`),
  KEY `disposed_at` (`disposed_at`),
  KEY `created_by` (`created_by`),
  KEY `executor_id` (`executor_id`),
  CONSTRAINT `stock_disposals_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `stock_disposals_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE SET NULL,
  CONSTRAINT `stock_disposals_executor_id_foreign` FOREIGN KEY (`executor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_disposals`
--

LOCK TABLES `stock_disposals` WRITE;
/*!40000 ALTER TABLE `stock_disposals` DISABLE KEYS */;
INSERT INTO `stock_disposals` VALUES (1,'XH24-0001',1,'completed','2025-12-07 08:00:00',10.0000,500000.0000,'Hủy hàng hư hỏng',1,1,'2025-12-07 10:21:05','2025-12-07 10:21:05'),(2,'XH24-0002',1,'completed','2025-12-06 14:30:00',5.0000,250000.0000,'Hủy hàng hết hạn',1,1,'2025-12-07 10:21:05','2025-12-07 10:21:05'),(3,'XH24-0003',1,'draft','2025-12-07 09:00:00',3.0000,150000.0000,'Chờ duyệt',1,NULL,'2025-12-07 10:21:05','2025-12-07 10:21:05');
/*!40000 ALTER TABLE `stock_disposals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_entries`
--

DROP TABLE IF EXISTS `stock_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `entry_number` varchar(60) NOT NULL,
  `type` varchar(20) NOT NULL,
  `status` varchar(20) DEFAULT 'draft',
  `branch_id` bigint unsigned DEFAULT NULL,
  `source_warehouse_id` bigint unsigned DEFAULT NULL,
  `target_warehouse_id` bigint unsigned DEFAULT NULL,
  `reference_type` varchar(80) DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `return_reason` varchar(255) DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `submitted_by` bigint unsigned DEFAULT NULL,
  `cancelled_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_stock_entry_number` (`entry_number`),
  KEY `idx_stock_entry_type_status` (`type`,`status`),
  KEY `fk_stock_entries_branch_id` (`branch_id`),
  KEY `fk_stock_entries_source_warehouse_id` (`source_warehouse_id`),
  KEY `fk_stock_entries_target_warehouse_id` (`target_warehouse_id`),
  CONSTRAINT `fk_stock_entries_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_stock_entries_source_warehouse_id` FOREIGN KEY (`source_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_stock_entries_target_warehouse_id` FOREIGN KEY (`target_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_entries`
--

LOCK TABLES `stock_entries` WRITE;
/*!40000 ALTER TABLE `stock_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_entry_items`
--

DROP TABLE IF EXISTS `stock_entry_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_entry_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `stock_entry_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `variant_id` bigint unsigned DEFAULT NULL,
  `qty` decimal(12,3) DEFAULT '0.000',
  `uom` varchar(50) DEFAULT NULL,
  `batch_id` bigint unsigned DEFAULT NULL,
  `serial_number` varchar(160) DEFAULT NULL,
  `source_warehouse_id` bigint unsigned DEFAULT NULL,
  `target_warehouse_id` bigint unsigned DEFAULT NULL,
  `source_branch_id` bigint unsigned DEFAULT NULL,
  `target_branch_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_stock_entry_item_entry` (`stock_entry_id`),
  KEY `idx_stock_entry_item_product` (`product_id`),
  KEY `fk_stock_entry_items_variant_id` (`variant_id`),
  KEY `fk_stock_entry_items_source_branch_id` (`source_branch_id`),
  KEY `fk_stock_entry_items_target_branch_id` (`target_branch_id`),
  KEY `fk_stock_entry_items_source_warehouse_id` (`source_warehouse_id`),
  KEY `fk_stock_entry_items_target_warehouse_id` (`target_warehouse_id`),
  KEY `fk_stock_entry_items_batch_id` (`batch_id`),
  CONSTRAINT `fk_stock_entry_items_batch_id` FOREIGN KEY (`batch_id`) REFERENCES `product_batches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_stock_entry_items_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_stock_entry_items_source_branch_id` FOREIGN KEY (`source_branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_stock_entry_items_source_warehouse_id` FOREIGN KEY (`source_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_stock_entry_items_stock_entry_id` FOREIGN KEY (`stock_entry_id`) REFERENCES `stock_entries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_stock_entry_items_target_branch_id` FOREIGN KEY (`target_branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_stock_entry_items_target_warehouse_id` FOREIGN KEY (`target_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_stock_entry_items_variant_id` FOREIGN KEY (`variant_id`) REFERENCES `product_variants_v2` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_entry_items`
--

LOCK TABLES `stock_entry_items` WRITE;
/*!40000 ALTER TABLE `stock_entry_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_entry_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_ledgers`
--

DROP TABLE IF EXISTS `stock_ledgers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_ledgers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `variant_id` bigint unsigned DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `warehouse_id` bigint unsigned DEFAULT NULL,
  `batch_id` bigint unsigned DEFAULT NULL,
  `serial_number` varchar(160) DEFAULT NULL,
  `movement_date` datetime NOT NULL,
  `reference_type` varchar(80) NOT NULL,
  `reference_id` bigint unsigned NOT NULL,
  `reference_seq` int unsigned DEFAULT '1',
  `qty_delta` decimal(12,3) DEFAULT '0.000',
  `unit_cost` decimal(14,4) DEFAULT '0.0000',
  `total_cost` decimal(14,4) DEFAULT '0.0000',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ledger_ref_seq` (`reference_type`,`reference_id`,`reference_seq`),
  KEY `idx_ledger_product` (`product_id`,`branch_id`),
  KEY `fk_stock_ledgers_variant_id` (`variant_id`),
  KEY `fk_stock_ledgers_branch_id` (`branch_id`),
  KEY `fk_stock_ledgers_warehouse_id` (`warehouse_id`),
  KEY `fk_stock_ledgers_batch_id` (`batch_id`),
  CONSTRAINT `fk_stock_ledgers_batch_id` FOREIGN KEY (`batch_id`) REFERENCES `product_batches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_stock_ledgers_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_stock_ledgers_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_stock_ledgers_variant_id` FOREIGN KEY (`variant_id`) REFERENCES `product_variants_v2` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_stock_ledgers_warehouse_id` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_ledgers`
--

LOCK TABLES `stock_ledgers` WRITE;
/*!40000 ALTER TABLE `stock_ledgers` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_ledgers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_reconciliation_items`
--

DROP TABLE IF EXISTS `stock_reconciliation_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_reconciliation_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reconciliation_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `variant_id` bigint unsigned DEFAULT NULL,
  `batch_id` bigint unsigned DEFAULT NULL,
  `counted_qty` decimal(12,3) DEFAULT '0.000',
  `current_qty` decimal(12,3) DEFAULT '0.000',
  `variance_qty` decimal(12,3) DEFAULT '0.000',
  `unit_cost` decimal(14,4) DEFAULT '0.0000',
  `remarks` text,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_recon_item` (`reconciliation_id`),
  KEY `idx_recon_product` (`product_id`,`batch_id`),
  KEY `fk_stock_reconciliation_variant_id` (`variant_id`),
  KEY `fk_stock_reconciliation_batch_id` (`batch_id`),
  CONSTRAINT `fk_stock_reconciliation_batch_id` FOREIGN KEY (`batch_id`) REFERENCES `product_batches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_stock_reconciliation_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_stock_reconciliation_reconciliation_id` FOREIGN KEY (`reconciliation_id`) REFERENCES `stock_reconciliations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_stock_reconciliation_variant_id` FOREIGN KEY (`variant_id`) REFERENCES `product_variants_v2` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_reconciliation_items`
--

LOCK TABLES `stock_reconciliation_items` WRITE;
/*!40000 ALTER TABLE `stock_reconciliation_items` DISABLE KEYS */;
INSERT INTO `stock_reconciliation_items` VALUES (1,1,501,NULL,NULL,56.000,58.000,-2.000,231426.0000,NULL,'2025-12-02 06:40:28','2025-12-02 06:40:28',NULL),(2,1,502,NULL,NULL,47.000,37.000,10.000,110617.0000,NULL,'2025-12-02 06:40:28','2025-12-02 06:40:28',NULL),(3,1,503,NULL,NULL,77.000,79.000,-2.000,123345.0000,NULL,'2025-12-02 06:40:28','2025-12-02 06:40:28',NULL),(4,2,501,NULL,NULL,22.000,26.000,-4.000,295116.0000,NULL,'2025-12-05 06:40:28','2025-12-05 06:40:28',NULL),(5,2,502,NULL,NULL,68.000,67.000,1.000,87030.0000,NULL,'2025-12-05 06:40:28','2025-12-05 06:40:28',NULL),(6,2,503,NULL,NULL,95.000,91.000,4.000,288124.0000,NULL,'2025-12-05 06:40:28','2025-12-05 06:40:28',NULL),(7,3,501,NULL,NULL,24.000,23.000,1.000,177111.0000,NULL,'2025-12-07 06:40:28','2025-12-07 06:40:28',NULL),(8,3,502,NULL,NULL,58.000,62.000,-4.000,24105.0000,NULL,'2025-12-07 06:40:28','2025-12-07 06:40:28',NULL),(9,3,503,NULL,NULL,24.000,14.000,10.000,453433.0000,NULL,'2025-12-07 06:40:28','2025-12-07 06:40:28',NULL),(10,4,501,NULL,NULL,48.000,41.000,7.000,132483.0000,NULL,'2025-11-27 06:40:28','2025-11-28 06:40:28',NULL),(11,4,502,NULL,NULL,105.000,99.000,6.000,490038.0000,NULL,'2025-11-27 06:40:28','2025-11-28 06:40:28',NULL),(12,4,503,NULL,NULL,51.000,48.000,3.000,133500.0000,NULL,'2025-11-27 06:40:28','2025-11-28 06:40:28',NULL);
/*!40000 ALTER TABLE `stock_reconciliation_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_reconciliations`
--

DROP TABLE IF EXISTS `stock_reconciliations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_reconciliations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `recon_number` varchar(60) NOT NULL,
  `branch_id` bigint unsigned NOT NULL,
  `status` varchar(20) DEFAULT 'draft',
  `notes` text,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_recon_number` (`recon_number`),
  KEY `fk_stock_reconciliation_branch_id` (`branch_id`),
  CONSTRAINT `fk_stock_reconciliation_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_reconciliations`
--

LOCK TABLES `stock_reconciliations` WRITE;
/*!40000 ALTER TABLE `stock_reconciliations` DISABLE KEYS */;
INSERT INTO `stock_reconciliations` VALUES (1,'SK1-20251207001',1,'approved','Kiểm kho cuối tuần - đã cân bằng',1,'2025-12-02 06:40:28','2025-12-02 06:40:28',1,'2025-12-03 06:40:28'),(2,'SK1-20251207002',1,'draft','Kiểm kho tháng 12 - đang thực hiện',1,'2025-12-05 06:40:28','2025-12-05 06:40:28',NULL,NULL),(3,'SK1-20251207003',1,'draft','Kiểm kho hàng điện tử',1,'2025-12-07 06:40:28','2025-12-07 06:40:28',NULL,NULL),(4,'SK1-20251207004',1,'rejected','Đã hủy do sai thông tin nhập',1,'2025-11-27 06:40:28','2025-11-28 06:40:28',NULL,NULL);
/*!40000 ALTER TABLE `stock_reconciliations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_transfer_items`
--

DROP TABLE IF EXISTS `stock_transfer_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_transfer_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transfer_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `variant_id` bigint unsigned DEFAULT NULL,
  `product_code` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `unit` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `quantity_sent` decimal(12,3) NOT NULL DEFAULT '0.000',
  `quantity_received` decimal(12,3) NOT NULL DEFAULT '0.000',
  `unit_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `notes` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transfer_id` (`transfer_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `stock_transfer_items_transfer_id_foreign` FOREIGN KEY (`transfer_id`) REFERENCES `stock_transfers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_transfer_items`
--

LOCK TABLES `stock_transfer_items` WRITE;
/*!40000 ALTER TABLE `stock_transfer_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_transfer_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_transfers`
--

DROP TABLE IF EXISTS `stock_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_transfers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('draft','in_transit','received','cancelled') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'draft',
  `from_branch_id` bigint unsigned NOT NULL,
  `to_branch_id` bigint unsigned NOT NULL,
  `transfer_date` datetime DEFAULT NULL,
  `receive_date` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `receiving_notes` text COLLATE utf8mb4_general_ci,
  `total_items` int unsigned NOT NULL DEFAULT '0',
  `quantity_sent` decimal(12,3) NOT NULL DEFAULT '0.000',
  `value_sent` decimal(15,2) NOT NULL DEFAULT '0.00',
  `quantity_received` decimal(12,3) NOT NULL DEFAULT '0.000',
  `value_received` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_by` bigint unsigned DEFAULT NULL,
  `received_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `from_branch_id` (`from_branch_id`),
  KEY `to_branch_id` (`to_branch_id`),
  KEY `status` (`status`),
  KEY `transfer_date` (`transfer_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_transfers`
--

LOCK TABLES `stock_transfers` WRITE;
/*!40000 ALTER TABLE `stock_transfers` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_transfers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subcontracting_materials`
--

DROP TABLE IF EXISTS `subcontracting_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subcontracting_materials` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subcontracting_order_id` bigint unsigned NOT NULL,
  `material_product_id` bigint unsigned DEFAULT NULL,
  `quantity` decimal(14,3) DEFAULT '0.000',
  `issued_quantity` decimal(14,3) DEFAULT '0.000',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_subcon_material` (`subcontracting_order_id`),
  KEY `fk_subcontracting_mater_material_product_id` (`material_product_id`),
  CONSTRAINT `fk_subcontracting_mater_material_product_id` FOREIGN KEY (`material_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_subcontracting_mater_subcontracting_order` FOREIGN KEY (`subcontracting_order_id`) REFERENCES `subcontracting_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subcontracting_materials`
--

LOCK TABLES `subcontracting_materials` WRITE;
/*!40000 ALTER TABLE `subcontracting_materials` DISABLE KEYS */;
/*!40000 ALTER TABLE `subcontracting_materials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subcontracting_orders`
--

DROP TABLE IF EXISTS `subcontracting_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subcontracting_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_number` varchar(50) NOT NULL,
  `supplier_id` bigint unsigned DEFAULT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `quantity` decimal(14,3) DEFAULT '0.000',
  `status` varchar(30) DEFAULT 'draft',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_subcon_order` (`order_number`),
  KEY `fk_subcontracting_order_product_id` (`product_id`),
  KEY `fk_subcontracting_orders_supplier` (`supplier_id`),
  CONSTRAINT `fk_subcontracting_order_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_subcontracting_orders_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subcontracting_orders`
--

LOCK TABLES `subcontracting_orders` WRITE;
/*!40000 ALTER TABLE `subcontracting_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `subcontracting_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscription_cycles`
--

DROP TABLE IF EXISTS `subscription_cycles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscription_cycles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subscription_id` bigint unsigned NOT NULL,
  `run_date` date NOT NULL,
  `order_id` bigint unsigned DEFAULT NULL,
  `status` varchar(30) DEFAULT 'processed',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_subscription_cycle` (`subscription_id`,`run_date`),
  KEY `idx_cycle_status` (`status`),
  KEY `fk_subscription_cycles_order_id` (`order_id`),
  CONSTRAINT `fk_subscription_cycles_order_id` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_subscription_cycles_subscription_id` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscription_cycles`
--

LOCK TABLES `subscription_cycles` WRITE;
/*!40000 ALTER TABLE `subscription_cycles` DISABLE KEYS */;
/*!40000 ALTER TABLE `subscription_cycles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscriptions`
--

DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned DEFAULT NULL,
  `template_id` bigint unsigned DEFAULT NULL,
  `plan_name` varchar(150) NOT NULL,
  `interval_days` int DEFAULT '30',
  `next_run_at` datetime DEFAULT NULL,
  `status` varchar(30) DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_subscription_status` (`status`),
  KEY `idx_subscription_next` (`next_run_at`),
  KEY `fk_subscriptions_customer_id` (`customer_id`),
  KEY `fk_subscriptions_template_id` (`template_id`),
  CONSTRAINT `fk_subscriptions_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_subscriptions_template_id` FOREIGN KEY (`template_id`) REFERENCES `order_templates` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscriptions`
--

LOCK TABLES `subscriptions` WRITE;
/*!40000 ALTER TABLE `subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier_debt_transactions`
--

DROP TABLE IF EXISTS `supplier_debt_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `supplier_debt_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `partner_id` bigint unsigned NOT NULL,
  `type` enum('adjust','payment','discount') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `debt_before` decimal(15,2) NOT NULL DEFAULT '0.00',
  `debt_after` decimal(15,2) NOT NULL DEFAULT '0.00',
  `payment_method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `executor_id` bigint unsigned DEFAULT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `reference_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `transaction_date` datetime NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `partner_id` (`partner_id`),
  KEY `type` (`type`),
  KEY `transaction_date` (`transaction_date`),
  CONSTRAINT `supplier_debt_transactions_partner_id_foreign` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_debt_transactions`
--

LOCK TABLES `supplier_debt_transactions` WRITE;
/*!40000 ALTER TABLE `supplier_debt_transactions` DISABLE KEYS */;
INSERT INTO `supplier_debt_transactions` VALUES (9,1,'payment',50000000.00,0.00,-50000000.00,'bank_transfer',1,'Thanh toán đơn PO-2024-001','purchase_order',NULL,'2025-11-06 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(10,2,'payment',35000000.00,0.00,-35000000.00,'bank_transfer',2,'Thanh toán đơn PO-2024-002','purchase_order',NULL,'2025-11-11 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(11,3,'adjust',45000000.00,0.00,45000000.00,NULL,1,'Nhập hàng từ đơn PO-2024-003','purchase_order',NULL,'2025-11-16 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(12,3,'payment',22500000.00,45000000.00,22500000.00,'bank_transfer',1,'Thanh toán đơn PO-2024-003','purchase_order',NULL,'2025-11-16 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(13,4,'adjust',60000000.00,0.00,60000000.00,NULL,2,'Nhập hàng từ đơn PO-2024-004','purchase_order',NULL,'2025-11-21 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(14,4,'payment',30000000.00,60000000.00,30000000.00,'bank_transfer',2,'Thanh toán đơn PO-2024-004','purchase_order',NULL,'2025-11-21 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(15,5,'adjust',25000000.00,0.00,25000000.00,NULL,1,'Nhập hàng từ đơn PO-2024-005','purchase_order',NULL,'2025-11-26 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(16,6,'adjust',40000000.00,0.00,40000000.00,NULL,2,'Nhập hàng từ đơn PO-2024-006','purchase_order',NULL,'2025-11-29 03:58:33','2025-12-06 03:58:33','2025-12-06 03:58:33'),(17,6,'adjust',-39999995.00,40000000.00,5.00,NULL,NULL,NULL,NULL,NULL,'2025-12-06 11:06:21','2025-12-06 04:06:27','2025-12-06 04:06:27'),(18,6,'adjust',0.00,5.00,5.00,NULL,NULL,NULL,NULL,NULL,'2025-12-06 11:06:27','2025-12-06 04:06:43','2025-12-06 04:06:43'),(19,6,'adjust',0.00,5.00,5.00,NULL,NULL,NULL,NULL,NULL,'2025-12-06 11:24:29','2025-12-06 04:24:35','2025-12-06 04:24:35'),(20,6,'adjust',995.00,5.00,1000.00,NULL,NULL,NULL,NULL,NULL,'2025-12-06 11:24:35','2025-12-06 04:30:41','2025-12-06 04:30:41'),(21,6,'payment',1000.00,1000.00,0.00,'cash',NULL,NULL,NULL,NULL,'2025-12-06 11:24:48','2025-12-06 04:30:49','2025-12-06 04:30:49');
/*!40000 ALTER TABLE `supplier_debt_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name_vi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `region` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `parent_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'company',
  `payment_terms` int DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_supplier_code` (`code`),
  KEY `suppliers_parent_id_foreign` (`parent_id`),
  KEY `suppliers_created_by_foreign` (`created_by`),
  CONSTRAINT `suppliers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE SET NULL,
  CONSTRAINT `suppliers_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE ON UPDATE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,'SUP-001','Công ty TNHH Da Giày Việt Nam','Công ty TNHH Da Giày Việt Nam','Viet Nam Leather Co., Ltd','0123456789','024-3888-9999','contact@dagiay.vn','123 Đường Láng, Hà Nội','Hà Nội','active',NULL,1,'2025-12-06 03:58:32','2025-12-06 03:58:32',NULL,'company',30),(2,'SUP-002','Xưởng Sản Xuất Túi Xách Hồng Hà','Xưởng Sản Xuất Túi Xách Hồng Hà','Hong Ha Bag Workshop','0987654321','024-3777-8888','hongha@tuixach.vn','456 Phố Huế, Hà Nội','Hà Nội','active',NULL,1,'2025-12-06 03:58:32','2025-12-06 03:58:32',NULL,'company',15),(3,'SUP-003','Công ty CP Phụ Kiện Thời Trang','Công ty CP Phụ Kiện Thời Trang','Fashion Accessories JSC','0111222333','028-3666-7777','info@phukien.com.vn','789 Nguyễn Huệ, HCM','Hồ Chí Minh','active',NULL,1,'2025-12-06 03:58:32','2025-12-06 03:58:32',NULL,'company',30),(4,'SUP-004','Nhà Máy Dệt May Tân Tiến','Nhà Máy Dệt May Tân Tiến','Tan Tien Textile Factory','0444555666','0236-3555-6666','sales@tantien.vn','321 Lê Duẩn, Đà Nẵng','Đà Nẵng','active',NULL,1,'2025-12-06 03:58:32','2025-12-06 03:58:32',NULL,'company',45),(5,'SUP-005','Xưởng Gia Công Đồng Phát','Xưởng Gia Công Đồng Phát','Dong Phat Workshop','0777888999','0292-3444-5555','dongphat@workshop.vn','654 Đường 3/2, Cần Thơ','Cần Thơ','active',NULL,1,'2025-12-06 03:58:32','2025-12-06 03:58:32',NULL,'individual',7),(6,'SUP-006','Công ty TNHH Vải Cao Cấp','Công ty TNHH Vải Cao Cấp','Premium Fabric Co., Ltd','0222333444','024-3333-4444','premium@fabric.vn','987 Trần Hưng Đạo, Hà Nội','Hà Nội','active',NULL,1,'2025-12-06 03:58:32','2025-12-06 03:58:32',NULL,'company',30),(7,'SUP-007','Nhà Cung Cấp Phụ Liệu Minh Anh','Nhà Cung Cấp Phụ Liệu Minh Anh','Minh Anh Materials','0555666777','028-3222-3333','minhanh@materials.vn','147 Lê Lợi, HCM','Hồ Chí Minh','active',NULL,1,'2025-12-06 03:58:32','2025-12-06 03:58:32',NULL,'individual',14),(8,'SUP-008','Xưởng Thêu Ren Hoa Mai','Xưởng Thêu Ren Hoa Mai','Hoa Mai Embroidery','0888999000','0225-3111-2222','hoamai@embroidery.vn','258 Lạch Tray, Hải Phòng','Hải Phòng','active',NULL,1,'2025-12-06 03:58:32','2025-12-06 03:58:32',NULL,'company',30);
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `support_tickets`
--

DROP TABLE IF EXISTS `support_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_tickets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subject` varchar(255) NOT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `lead_id` bigint unsigned DEFAULT NULL,
  `priority` varchar(20) DEFAULT 'medium',
  `status` varchar(30) DEFAULT 'open',
  `assigned_to` bigint unsigned DEFAULT NULL,
  `description` text,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_support_ticket_status` (`status`),
  KEY `idx_support_ticket_customer` (`customer_id`),
  KEY `idx_support_ticket_lead` (`lead_id`),
  CONSTRAINT `fk_support_tickets_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_support_tickets_lead_id` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_tickets`
--

LOCK TABLES `support_tickets` WRITE;
/*!40000 ALTER TABLE `support_tickets` DISABLE KEYS */;
/*!40000 ALTER TABLE `support_tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tasks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint unsigned DEFAULT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `task_name` varchar(255) NOT NULL,
  `status` varchar(30) DEFAULT 'open',
  `progress` decimal(5,2) DEFAULT '0.00',
  `estimated_hours` decimal(10,2) DEFAULT '0.00',
  `actual_hours` decimal(10,2) DEFAULT '0.00',
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `assigned_to` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `description` text,
  `priority` varchar(20) DEFAULT NULL,
  `related_to_type` varchar(50) DEFAULT NULL,
  `related_to_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_task_project` (`project_id`),
  KEY `idx_task_parent` (`parent_id`),
  KEY `idx_task_status` (`status`),
  CONSTRAINT `fk_tasks_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tasks_project_id` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasks`
--

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
INSERT INTO `tasks` VALUES (1,NULL,NULL,'','pending',0.00,0.00,0.00,NULL,'2025-12-07',2,NULL,'2025-12-06 03:35:23','2025-12-06 03:35:23','Call John Doe','Follow up on proposal','high','lead',1),(2,NULL,NULL,'','in_progress',0.00,0.00,0.00,NULL,'2025-12-08',3,NULL,'2025-12-06 03:35:23','2025-12-06 03:35:23','Prepare Contract','Draft service agreement for Tech Solutions','medium','opportunity',2),(3,NULL,NULL,'','pending',0.00,0.00,0.00,NULL,'2025-12-07',2,NULL,'2025-12-06 03:40:38','2025-12-06 03:40:38','Call John Doe','Follow up on proposal','high','lead',1),(4,NULL,NULL,'','in_progress',0.00,0.00,0.00,NULL,'2025-12-08',3,NULL,'2025-12-06 03:40:38','2025-12-06 03:40:38','Prepare Contract','Draft service agreement for Tech Solutions','medium','opportunity',2),(5,NULL,NULL,'','pending',0.00,0.00,0.00,NULL,'2025-12-07',2,NULL,'2025-12-06 03:43:34','2025-12-06 03:43:34','Call John Doe','Follow up on proposal','high','lead',1),(6,NULL,NULL,'','in_progress',0.00,0.00,0.00,NULL,'2025-12-08',3,NULL,'2025-12-06 03:43:34','2025-12-06 03:43:34','Prepare Contract','Draft service agreement for Tech Solutions','medium','opportunity',2),(7,NULL,NULL,'','pending',0.00,0.00,0.00,NULL,'2025-12-07',2,NULL,'2025-12-06 03:44:48','2025-12-06 03:44:48','Call John Doe','Follow up on proposal','high','lead',1),(8,NULL,NULL,'','in_progress',0.00,0.00,0.00,NULL,'2025-12-08',3,NULL,'2025-12-06 03:44:48','2025-12-06 03:44:48','Prepare Contract','Draft service agreement for Tech Solutions','medium','opportunity',2),(9,NULL,NULL,'','pending',0.00,0.00,0.00,NULL,'2025-12-07',2,NULL,'2025-12-06 03:54:23','2025-12-06 03:54:23','Call John Doe','Follow up on proposal','high','lead',1),(10,NULL,NULL,'','in_progress',0.00,0.00,0.00,NULL,'2025-12-08',3,NULL,'2025-12-06 03:54:23','2025-12-06 03:54:23','Prepare Contract','Draft service agreement for Tech Solutions','medium','opportunity',2),(11,NULL,NULL,'','pending',0.00,0.00,0.00,NULL,'2025-12-07',2,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33','Call John Doe','Follow up on proposal','high','lead',1),(12,NULL,NULL,'','in_progress',0.00,0.00,0.00,NULL,'2025-12-08',3,NULL,'2025-12-06 03:58:33','2025-12-06 03:58:33','Prepare Contract','Draft service agreement for Tech Solutions','medium','opportunity',2);
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tax_certificate_records`
--

DROP TABLE IF EXISTS `tax_certificate_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tax_certificate_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `certificate_number` varchar(80) NOT NULL,
  `country` varchar(5) NOT NULL,
  `party_type` varchar(60) DEFAULT NULL,
  `party_id` bigint unsigned DEFAULT NULL,
  `base_amount` decimal(14,2) DEFAULT '0.00',
  `withheld_amount` decimal(14,2) DEFAULT '0.00',
  `issue_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tax_certificate` (`certificate_number`),
  KEY `fk_tax_certificate_reco_party_id` (`party_id`),
  CONSTRAINT `fk_tax_certificate_reco_party_id` FOREIGN KEY (`party_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_certificate_records`
--

LOCK TABLES `tax_certificate_records` WRITE;
/*!40000 ALTER TABLE `tax_certificate_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `tax_certificate_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tax_charges`
--

DROP TABLE IF EXISTS `tax_charges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tax_charges` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `template_id` bigint unsigned DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `rate_percent` decimal(8,3) DEFAULT '0.000',
  `charge_type` varchar(20) DEFAULT 'on_net_total',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tax_charges_template` (`template_id`),
  CONSTRAINT `fk_tax_charges_template_id` FOREIGN KEY (`template_id`) REFERENCES `tax_templates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_charges`
--

LOCK TABLES `tax_charges` WRITE;
/*!40000 ALTER TABLE `tax_charges` DISABLE KEYS */;
/*!40000 ALTER TABLE `tax_charges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tax_template_items`
--

DROP TABLE IF EXISTS `tax_template_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tax_template_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `template_id` bigint unsigned DEFAULT NULL,
  `tax_name` varchar(150) NOT NULL,
  `rate_percent` decimal(8,3) DEFAULT '0.000',
  `charge_type` varchar(30) DEFAULT 'on_net_total',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tax_template_items` (`template_id`),
  CONSTRAINT `fk_tax_template_items_template_id` FOREIGN KEY (`template_id`) REFERENCES `tax_templates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_template_items`
--

LOCK TABLES `tax_template_items` WRITE;
/*!40000 ALTER TABLE `tax_template_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `tax_template_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tax_templates`
--

DROP TABLE IF EXISTS `tax_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tax_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `rate_percent` decimal(8,3) DEFAULT '0.000',
  `is_inclusive` tinyint(1) DEFAULT '0',
  `rounding_rule` varchar(20) DEFAULT 'nearest',
  `status` varchar(20) DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9004 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_templates`
--

LOCK TABLES `tax_templates` WRITE;
/*!40000 ALTER TABLE `tax_templates` DISABLE KEYS */;
INSERT INTO `tax_templates` VALUES (9001,'VAT 0%',0.000,0,'nearest','active','2025-12-06 03:35:22','2025-12-06 03:35:22'),(9002,'VAT 5%',5.000,0,'nearest','active','2025-12-06 03:35:22','2025-12-06 03:35:22'),(9003,'VAT 10%',10.000,0,'nearest','active','2025-12-06 03:35:22','2025-12-06 03:35:22');
/*!40000 ALTER TABLE `tax_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temp_queue`
--

DROP TABLE IF EXISTS `temp_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `temp_queue` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `device_id` bigint unsigned DEFAULT NULL,
  `payload` json DEFAULT NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `expired_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `temp_queue_device_id_foreign` (`device_id`),
  CONSTRAINT `temp_queue_device_id_foreign` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE ON UPDATE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temp_queue`
--

LOCK TABLES `temp_queue` WRITE;
/*!40000 ALTER TABLE `temp_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `temp_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_communications`
--

DROP TABLE IF EXISTS `ticket_communications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_communications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint unsigned NOT NULL,
  `type` varchar(30) DEFAULT 'note',
  `content` text,
  `attachments` text,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ticket_comm_ticket` (`ticket_id`),
  CONSTRAINT `fk_ticket_communication_ticket_id` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_communications`
--

LOCK TABLES `ticket_communications` WRITE;
/*!40000 ALTER TABLE `ticket_communications` DISABLE KEYS */;
/*!40000 ALTER TABLE `ticket_communications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_events`
--

DROP TABLE IF EXISTS `ticket_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint unsigned NOT NULL,
  `event_type` varchar(30) NOT NULL,
  `from_status` varchar(30) DEFAULT NULL,
  `to_status` varchar(30) DEFAULT NULL,
  `description` text,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ticket_event_ticket` (`ticket_id`),
  CONSTRAINT `fk_ticket_events_ticket_id` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_events`
--

LOCK TABLES `ticket_events` WRITE;
/*!40000 ALTER TABLE `ticket_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `ticket_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `timesheet_details`
--

DROP TABLE IF EXISTS `timesheet_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `timesheet_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `timesheet_id` bigint unsigned NOT NULL,
  `project_id` bigint unsigned DEFAULT NULL,
  `task_id` bigint unsigned DEFAULT NULL,
  `activity_type_id` bigint unsigned DEFAULT NULL,
  `work_date` date DEFAULT NULL,
  `hours` decimal(10,2) DEFAULT '0.00',
  `billing_rate` decimal(12,2) DEFAULT '0.00',
  `cost_rate` decimal(12,2) DEFAULT '0.00',
  `billable_amount` decimal(14,2) DEFAULT '0.00',
  `cost_amount` decimal(14,2) DEFAULT '0.00',
  `description` text,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_timesheet_detail_header` (`timesheet_id`),
  KEY `idx_timesheet_detail_task` (`task_id`),
  KEY `fk_timesheet_details_project_id` (`project_id`),
  KEY `fk_timesheet_details_activity_type_id` (`activity_type_id`),
  CONSTRAINT `fk_timesheet_details_activity_type_id` FOREIGN KEY (`activity_type_id`) REFERENCES `activity_types` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_timesheet_details_project_id` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_timesheet_details_task_id` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_timesheet_details_timesheet_id` FOREIGN KEY (`timesheet_id`) REFERENCES `timesheets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `timesheet_details`
--

LOCK TABLES `timesheet_details` WRITE;
/*!40000 ALTER TABLE `timesheet_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `timesheet_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `timesheets`
--

DROP TABLE IF EXISTS `timesheets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `timesheets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `timesheet_number` varchar(60) NOT NULL,
  `project_id` bigint unsigned DEFAULT NULL,
  `employee_id` bigint unsigned DEFAULT NULL,
  `status` varchar(30) DEFAULT 'draft',
  `total_hours` decimal(12,2) DEFAULT '0.00',
  `total_billable` decimal(14,2) DEFAULT '0.00',
  `total_cost` decimal(14,2) DEFAULT '0.00',
  `notes` text,
  `created_by` bigint unsigned DEFAULT NULL,
  `submitted_by` bigint unsigned DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_timesheet_number` (`timesheet_number`),
  KEY `idx_timesheet_project` (`project_id`),
  KEY `idx_timesheet_status` (`status`),
  KEY `fk_timesheets_employee_id` (`employee_id`),
  CONSTRAINT `fk_timesheets_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_timesheets_project_id` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `timesheets`
--

LOCK TABLES `timesheets` WRITE;
/*!40000 ALTER TABLE `timesheets` DISABLE KEYS */;
/*!40000 ALTER TABLE `timesheets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `two_factor_secret` varchar(255) DEFAULT NULL,
  `two_factor_enabled` tinyint(1) DEFAULT '0',
  `password_changed_at` datetime DEFAULT NULL,
  `failed_login_attempts` int DEFAULT '0',
  `account_locked_until` datetime DEFAULT NULL,
  `timezone` varchar(50) DEFAULT 'Asia/Ho_Chi_Minh',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_users_branch_id` (`branch_id`),
  CONSTRAINT `fk_users_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'devadmin','admin@lanocrm.local','$2y$12$7SI9yb1rxz2lzuKynLZLBekiXP8uYoeW6hO4TQWsgKWV.3nEW1o9G','Dev Admin',NULL,NULL,1,'active','2025-12-08 09:34:30','172.18.0.1',NULL,NULL,0,NULL,0,NULL,'Asia/Ho_Chi_Minh','2025-12-06 03:04:55',NULL,NULL),(2,'manager1','manager@lanocrm.local','$2y$12$pRDQCai3nUuqKTP3GzRxpuLXm52X56HoEyUv/q4ji5rwQuZDHfxC2','Manager Test',NULL,NULL,1,'active',NULL,NULL,NULL,NULL,0,NULL,0,NULL,'Asia/Ho_Chi_Minh','2025-12-06 03:04:55',NULL,NULL),(3,'viewer1','viewer@lanocrm.local','$2y$12$3Y1HoAuurcIwSCt7ZYeCCuCs1Hkkd1sKaoFkjJd4z/LhkfKC2ifmu','Viewer Test',NULL,NULL,1,'active',NULL,NULL,NULL,NULL,0,NULL,0,NULL,'Asia/Ho_Chi_Minh','2025-12-06 03:04:55',NULL,NULL),(4,'admin.staging','admin@staging.lanocrm.local','$2y$12$7SI9yb1rxz2lzuKynLZLBekiXP8uYoeW6hO4TQWsgKWV.3nEW1o9G','Staging Admin',NULL,NULL,1,'active','2025-12-07 06:38:28','172.18.0.6',NULL,NULL,0,NULL,0,NULL,'Asia/Ho_Chi_Minh','2025-12-06 03:35:22','2025-12-06 03:35:22',NULL),(5,'manager.staging','manager@staging.lanocrm.local','$2y$12$pRDQCai3nUuqKTP3GzRxpuLXm52X56HoEyUv/q4ji5rwQuZDHfxC2','Staging Manager',NULL,NULL,1,'active',NULL,NULL,NULL,NULL,0,NULL,0,NULL,'Asia/Ho_Chi_Minh','2025-12-06 03:35:22','2025-12-06 03:35:22',NULL),(6,'staff.staging','staff@staging.lanocrm.local','$2y$12$3Y1HoAuurcIwSCt7ZYeCCuCs1Hkkd1sKaoFkjJd4z/LhkfKC2ifmu','Staging Staff',NULL,NULL,1,'active',NULL,NULL,NULL,NULL,0,NULL,0,NULL,'Asia/Ho_Chi_Minh','2025-12-06 03:35:22','2025-12-06 03:35:22',NULL),(10,'demo.admin','demo.admin@lanocrm.local','$2y$12$7SI9yb1rxz2lzuKynLZLBekiXP8uYoeW6hO4TQWsgKWV.3nEW1o9G','Demo Admin User',NULL,NULL,1,'active','2025-12-06 13:55:15','172.18.0.6',NULL,NULL,0,NULL,0,NULL,'Asia/Ho_Chi_Minh','2025-12-06 03:35:22','2025-12-06 03:35:22',NULL),(11,'demo.manager.hn','manager.hn@lanocrm.local','$2y$12$pRDQCai3nUuqKTP3GzRxpuLXm52X56HoEyUv/q4ji5rwQuZDHfxC2','Demo Manager Hanoi',NULL,NULL,1,'active',NULL,NULL,NULL,NULL,0,NULL,0,NULL,'Asia/Ho_Chi_Minh','2025-12-06 03:35:22','2025-12-06 03:35:22',NULL),(12,'demo.manager.hcm','manager.hcm@lanocrm.local','$2y$12$pRDQCai3nUuqKTP3GzRxpuLXm52X56HoEyUv/q4ji5rwQuZDHfxC2','Demo Manager HCM',NULL,NULL,2,'active',NULL,NULL,NULL,NULL,0,NULL,0,NULL,'Asia/Ho_Chi_Minh','2025-12-06 03:35:22','2025-12-06 03:35:22',NULL),(13,'demo.staff1','staff1@lanocrm.local','$2y$12$3Y1HoAuurcIwSCt7ZYeCCuCs1Hkkd1sKaoFkjJd4z/LhkfKC2ifmu','Demo Staff 1',NULL,NULL,1,'active',NULL,NULL,NULL,NULL,0,NULL,0,NULL,'Asia/Ho_Chi_Minh','2025-12-06 03:35:22','2025-12-06 03:35:22',NULL),(14,'demo.staff2','staff2@lanocrm.local','$2y$12$3Y1HoAuurcIwSCt7ZYeCCuCs1Hkkd1sKaoFkjJd4z/LhkfKC2ifmu','Demo Staff 2',NULL,NULL,2,'active',NULL,NULL,NULL,NULL,0,NULL,0,NULL,'Asia/Ho_Chi_Minh','2025-12-06 03:35:22','2025-12-06 03:35:22',NULL),(15,'demo.inactive','inactive@lanocrm.local','$2y$12$3Y1HoAuurcIwSCt7ZYeCCuCs1Hkkd1sKaoFkjJd4z/LhkfKC2ifmu','Demo Inactive User',NULL,NULL,1,'inactive',NULL,NULL,NULL,NULL,0,NULL,0,NULL,'Asia/Ho_Chi_Minh','2025-12-06 03:35:22','2025-12-06 03:35:22',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wards`
--

DROP TABLE IF EXISTS `wards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `district_id` bigint unsigned NOT NULL,
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name_en` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `full_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `full_name_en` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `code_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ward_code` (`code`),
  KEY `idx_ward_district` (`district_id`),
  KEY `idx_ward_active` (`is_active`),
  CONSTRAINT `fk_ward_district` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wards`
--

LOCK TABLES `wards` WRITE;
/*!40000 ALTER TABLE `wards` DISABLE KEYS */;
INSERT INTO `wards` VALUES (1,1,'00001','Phúc Xá',NULL,'Phường Phúc Xá',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(2,1,'00004','Trúc Bạch',NULL,'Phường Trúc Bạch',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(3,1,'00006','Vĩnh Phúc',NULL,'Phường Vĩnh Phúc',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(4,1,'00007','Cống Vị',NULL,'Phường Cống Vị',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(5,1,'00008','Liễu Giai',NULL,'Phường Liễu Giai',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(6,1,'00010','Nguyễn Trung Trực',NULL,'Phường Nguyễn Trung Trực',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(7,1,'00013','Quán Thánh',NULL,'Phường Quán Thánh',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(8,1,'00016','Ngọc Hà',NULL,'Phường Ngọc Hà',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(9,1,'00019','Điện Biên',NULL,'Phường Điện Biên',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(10,1,'00022','Đội Cấn',NULL,'Phường Đội Cấn',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(11,1,'00025','Ngọc Khánh',NULL,'Phường Ngọc Khánh',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(12,1,'00028','Kim Mã',NULL,'Phường Kim Mã',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(13,1,'00031','Giảng Võ',NULL,'Phường Giảng Võ',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(14,1,'00034','Thành Công',NULL,'Phường Thành Công',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(15,2,'00037','Phúc Tân',NULL,'Phường Phúc Tân',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(16,2,'00040','Đồng Xuân',NULL,'Phường Đồng Xuân',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(17,2,'00043','Hàng Mã',NULL,'Phường Hàng Mã',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(18,2,'00046','Hàng Buồm',NULL,'Phường Hàng Buồm',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(19,2,'00049','Hàng Đào',NULL,'Phường Hàng Đào',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(20,2,'00052','Hàng Bồ',NULL,'Phường Hàng Bồ',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(21,2,'00055','Cửa Đông',NULL,'Phường Cửa Đông',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(22,2,'00058','Lý Thái Tổ',NULL,'Phường Lý Thái Tổ',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(23,2,'00061','Hàng Bạc',NULL,'Phường Hàng Bạc',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(24,2,'00064','Hàng Gai',NULL,'Phường Hàng Gai',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(25,2,'00067','Chương Dương',NULL,'Phường Chương Dương',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(26,2,'00070','Hàng Trống',NULL,'Phường Hàng Trống',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(27,2,'00073','Cửa Nam',NULL,'Phường Cửa Nam',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(28,2,'00076','Hàng Bông',NULL,'Phường Hàng Bông',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(29,2,'00079','Tràng Tiền',NULL,'Phường Tràng Tiền',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(30,2,'00082','Trần Hưng Đạo',NULL,'Phường Trần Hưng Đạo',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(31,2,'00085','Phan Chu Trinh',NULL,'Phường Phan Chu Trinh',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(32,2,'00088','Hàng Bài',NULL,'Phường Hàng Bài',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(33,31,'26734','Tân Định',NULL,'Phường Tân Định',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(34,31,'26737','Đa Kao',NULL,'Phường Đa Kao',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(35,31,'26740','Bến Nghé',NULL,'Phường Bến Nghé',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(36,31,'26743','Bến Thành',NULL,'Phường Bến Thành',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(37,31,'26746','Nguyễn Thái Bình',NULL,'Phường Nguyễn Thái Bình',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(38,31,'26749','Phạm Ngũ Lão',NULL,'Phường Phạm Ngũ Lão',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(39,31,'26752','Cầu Ông Lãnh',NULL,'Phường Cầu Ông Lãnh',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(40,31,'26755','Cô Giang',NULL,'Phường Cô Giang',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(41,31,'26758','Nguyễn Cư Trinh',NULL,'Phường Nguyễn Cư Trinh',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10'),(42,31,'26761','Cầu Kho',NULL,'Phường Cầu Kho',NULL,NULL,0,1,'2025-12-06 10:15:10','2025-12-06 10:15:10');
/*!40000 ALTER TABLE `wards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouses`
--

DROP TABLE IF EXISTS `warehouses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `warehouses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_warehouses_branch_id` (`branch_id`),
  CONSTRAINT `fk_warehouses_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouses`
--

LOCK TABLES `warehouses` WRITE;
/*!40000 ALTER TABLE `warehouses` DISABLE KEYS */;
INSERT INTO `warehouses` VALUES (1,'Kho chính Hà Nội','WH-HN-MAIN',1,'active','2025-12-06 03:35:22','2025-12-06 03:35:22',NULL),(2,'Kho bán lẻ Hà Nội','WH-HN-RETAIL',1,'active','2025-12-06 03:35:22','2025-12-06 03:35:22',NULL),(3,'Kho chính HCM','WH-HCM-MAIN',2,'active','2025-12-06 03:35:22','2025-12-06 03:35:22',NULL),(4,'Kho bán lẻ HCM','WH-HCM-RETAIL',2,'active','2025-12-06 03:35:22','2025-12-06 03:35:22',NULL),(5,'Kho chính Đà Nẵng','WH-DN-MAIN',3,'active','2025-12-06 03:35:22','2025-12-06 03:35:22',NULL),(6,'Kho chính Cần Thơ','WH-CT-MAIN',4,'active','2025-12-06 03:35:22','2025-12-06 03:35:22',NULL),(7,'Kho chính Hải Phòng','WH-HP-MAIN',5,'active','2025-12-06 03:35:22','2025-12-06 03:35:22',NULL);
/*!40000 ALTER TABLE `warehouses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webhook_events`
--

DROP TABLE IF EXISTS `webhook_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `webhook_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event` varchar(100) DEFAULT NULL,
  `payload` json DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `attempts` int DEFAULT '0',
  `last_error` text,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `webhook_events`
--

LOCK TABLES `webhook_events` WRITE;
/*!40000 ALTER TABLE `webhook_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `webhook_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webhook_subscriptions`
--

DROP TABLE IF EXISTS `webhook_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `webhook_subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event` varchar(100) DEFAULT NULL,
  `target_url` varchar(500) DEFAULT NULL,
  `secret` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `webhook_subscriptions`
--

LOCK TABLES `webhook_subscriptions` WRITE;
/*!40000 ALTER TABLE `webhook_subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `webhook_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `withholding_rules`
--

DROP TABLE IF EXISTS `withholding_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `withholding_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `rate_percent` decimal(8,3) DEFAULT '0.000',
  `apply_threshold` decimal(14,2) DEFAULT '0.00',
  `status` varchar(20) DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `withholding_rules`
--

LOCK TABLES `withholding_rules` WRITE;
/*!40000 ALTER TABLE `withholding_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `withholding_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `work_orders`
--

DROP TABLE IF EXISTS `work_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `work_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `bom_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned NOT NULL,
  `quantity` decimal(12,3) DEFAULT '0.000',
  `status` varchar(30) DEFAULT 'draft',
  `planned_start` datetime DEFAULT NULL,
  `planned_end` datetime DEFAULT NULL,
  `actual_start` datetime DEFAULT NULL,
  `actual_end` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_work_order_product` (`product_id`),
  KEY `idx_work_order_bom` (`bom_id`),
  KEY `idx_work_order_status` (`status`),
  KEY `fk_work_orders_branch_id` (`branch_id`),
  CONSTRAINT `fk_work_orders_bom_id` FOREIGN KEY (`bom_id`) REFERENCES `bill_of_materials` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_work_orders_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_work_orders_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `work_orders`
--

LOCK TABLES `work_orders` WRITE;
/*!40000 ALTER TABLE `work_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `work_orders` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-08 23:40:18
