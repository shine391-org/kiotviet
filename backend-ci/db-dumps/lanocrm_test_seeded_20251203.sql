-- MySQL dump 10.13  Distrib 8.4.7, for Linux (x86_64)
--
-- Host: localhost    Database: lanocrm_test
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendances`
--

LOCK TABLES `attendances` WRITE;
/*!40000 ALTER TABLE `attendances` DISABLE KEYS */;
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
INSERT INTO `branches` VALUES (1,'Chi nhánh Hà Nội','HN01','active','2025-12-03 02:28:54',NULL,NULL),(2,'Chi nhánh HCM','HCM01','active','2025-12-03 02:28:54',NULL,NULL),(3,'Chi nhánh Đà Nẵng','DN01','active','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(4,'Chi nhánh Cần Thơ','CT01','active','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(5,'Chi nhánh Hải Phòng','HP01','active','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(6,'Chi nhánh Test (Inactive)','TEST01','inactive','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_transactions`
--

LOCK TABLES `cash_transactions` WRITE;
/*!40000 ALTER TABLE `cash_transactions` DISABLE KEYS */;
INSERT INTO `cash_transactions` VALUES (1,'RECEIPT',9480000.00,'sales','CASH','approved','Quỹ demo','Thu đơn DH-DEMO-001 (unpaid)','order',1,'DH-DEMO-001',1,1,'Demo Admin','demo-staff','CUST-2001','Nguyễn Minh An','0912000001','12 Trần Hưng Đạo, Hà Nội',NULL,NULL,'2025-11-03','Thanh toán đủ','2025-12-03 02:28:55','2025-12-03 02:28:55',NULL),(2,'RECEIPT',3192000.00,'sales','BANK_TRANSFER','approved','Quỹ demo','Thu đơn DH-DEMO-002 (partial)','order',2,'DH-DEMO-002',2,1,'Demo Admin','demo-staff','CUST-2002','Trần Thu Hà','0912000002','89 Lý Thường Kiệt, Hà Nội',NULL,NULL,'2025-11-04','Thanh toán đủ','2025-12-03 02:28:55','2025-12-03 02:28:55',NULL),(3,'RECEIPT',8288500.00,'sales','COD','approved','Quỹ demo','Thu đơn DH-DEMO-003 (partial)','order',3,'DH-DEMO-003',3,1,'Demo Admin','demo-staff','CUST-2003','Phạm Gia Bảo','0912000003','22 Nguyễn Huệ, HCM',NULL,NULL,'2025-11-05','Thanh toán đủ','2025-12-03 02:28:55','2025-12-03 02:28:55',NULL),(4,'RECEIPT',3900000.00,'sales','CASH','approved','Quỹ demo','Thu đơn DH-DEMO-004 (partial)','order',4,'DH-DEMO-004',4,1,'Demo Admin','demo-staff','CUST-2004','Lê Hồng Nhung','0912000004','35 Hai Bà Trưng, HCM',NULL,NULL,'2025-11-06','Thanh toán đủ','2025-12-03 02:28:55','2025-12-03 02:28:55',NULL),(5,'RECEIPT',7607250.00,'sales','EWALLET','approved','Quỹ demo','Thu đơn DH-DEMO-005 (partial)','order',5,'DH-DEMO-005',5,1,'Demo Admin','demo-staff','CUST-2005','Vũ Hoàng Long','0912000005','15 Nguyễn Tri Phương, Đà Nẵng',NULL,NULL,'2025-11-07','Thanh toán đủ','2025-12-03 02:28:55','2025-12-03 02:28:55',NULL),(6,'RECEIPT',9927500.00,'sales','COD','approved','Quỹ demo','Thu đơn DH-DEMO-006 (partial)','order',6,'DH-DEMO-006',1,1,'Demo Admin','demo-staff','CUST-2006','Đặng Bích Trâm','0912000006','101 Võ Văn Tần, HCM',NULL,NULL,'2025-11-08','Thanh toán đủ','2025-12-03 02:28:55','2025-12-03 02:28:55',NULL),(7,'RECEIPT',3470000.00,'sales','BANK_TRANSFER','approved','Quỹ demo','Thu đơn DH-DEMO-007 (partial)','order',7,'DH-DEMO-007',2,1,'Demo Admin','demo-staff','CUST-2007','Huỳnh Tuấn Kiệt','0912000007','45 Trần Phú, Nha Trang',NULL,NULL,'2025-11-09','Thanh toán đủ','2025-12-03 02:28:55','2025-12-03 02:28:55',NULL),(8,'RECEIPT',8347500.00,'sales','CASH','approved','Quỹ demo','Thu đơn DH-DEMO-008 (partial)','order',8,'DH-DEMO-008',3,1,'Demo Admin','demo-staff','CUST-2008','Lý Thu Uyên','0912000008','68 Lê Lợi, Huế',NULL,NULL,'2025-11-10','Thanh toán đủ','2025-12-03 02:28:55','2025-12-03 02:28:55',NULL),(9,'RECEIPT',10395000.00,'sales','BANK_TRANSFER','approved','Quỹ demo','Thu đơn DH-DEMO-009 (paid)','order',9,'DH-DEMO-009',1,1,'Demo Admin','demo-staff','CUST-2011','Công ty Ánh Dương','0912000011','11 Duy Tân, Cầu Giấy, Hà Nội',NULL,NULL,'2025-11-11','Thanh toán đủ','2025-12-03 02:28:55','2025-12-03 02:28:55',NULL),(10,'RECEIPT',9030000.00,'sales','BANK_TRANSFER','approved','Quỹ demo','Thu đơn DH-DEMO-010 (paid)','order',10,'DH-DEMO-010',2,1,'Demo Admin','demo-staff','CUST-2012','CTCP Gỗ Xanh','0912000012','45 Pasteur, Quận 1, HCM',NULL,NULL,'2025-11-12','Thanh toán đủ','2025-12-03 02:28:55','2025-12-03 02:28:55',NULL),(11,'RECEIPT',7581000.00,'sales','CASH','approved','Quỹ demo','Thu đơn DH-DEMO-011 (paid)','order',11,'DH-DEMO-011',3,1,'Demo Admin','demo-staff','CUST-2013','Hộ KD Minh Quân','0912000013','22 Trần Phú, Nha Trang',NULL,NULL,'2025-11-13','Thanh toán đủ','2025-12-03 02:28:55','2025-12-03 02:28:55',NULL),(12,'RECEIPT',9900000.00,'sales','COD','approved','Quỹ demo','Thu đơn DH-DEMO-012 (paid)','order',12,'DH-DEMO-012',4,1,'Demo Admin','demo-staff','CUST-2014','Công ty Vận Tải Nhanh','0912000014','88 Kim Mã, Ba Đình, Hà Nội',NULL,NULL,'2025-11-14','Thanh toán đủ','2025-12-03 02:28:55','2025-12-03 02:28:55',NULL),(13,'RECEIPT',9465000.00,'sales','BANK_TRANSFER','approved','Quỹ demo','Thu đơn DH-DEMO-013 (paid)','order',13,'DH-DEMO-013',5,1,'Demo Admin','demo-staff','CUST-2015','CTY Thiết Kế Mộc','0912000015','12 Nguyễn Trãi, Quận 5, HCM',NULL,NULL,'2025-11-15','Thanh toán đủ','2025-12-03 02:28:55','2025-12-03 02:28:55',NULL),(14,'RECEIPT',4095000.00,'sales','CASH','approved','Quỹ demo','Thu đơn DH-DEMO-014 (paid)','order',14,'DH-DEMO-014',1,1,'Demo Admin','demo-staff','CUST-2016','Trịnh Quốc Thái','0912000016','14 Lê Duẩn, Hà Nội',NULL,NULL,'2025-11-16','Thanh toán đủ','2025-12-03 02:28:55','2025-12-03 02:28:55',NULL),(15,'RECEIPT',9927500.00,'sales','EWALLET','approved','Quỹ demo','Thu đơn DH-DEMO-015 (paid)','order',15,'DH-DEMO-015',2,1,'Demo Admin','demo-staff','CUST-2017','Đỗ Hồng Ngọc','0912000017','7 Nguyễn Văn Cừ, Hạ Long',NULL,NULL,'2025-11-17','Thanh toán đủ','2025-12-03 02:28:55','2025-12-03 02:28:55',NULL),(16,'RECEIPT',9450000.00,'sales','CASH','approved','Quỹ demo','Thu đơn DH-DEMO-016 (paid)','order',16,'DH-DEMO-016',3,1,'Demo Admin','demo-staff','CUST-2018','La Mỹ Duyên','0912000018','155 Lạch Tray, Hải Phòng',NULL,NULL,'2025-11-18','Thanh toán đủ','2025-12-03 02:28:55','2025-12-03 02:28:55',NULL),(17,'RECEIPT',3192000.00,'sales','BANK_TRANSFER','approved','Quỹ demo','Thu đơn DH-DEMO-017 (paid)','order',17,'DH-DEMO-017',4,1,'Demo Admin','demo-staff','CUST-2019','Đinh Mạnh Cường','0912000019','18 Lê Lợi, Vinh',NULL,NULL,'2025-11-19','Thanh toán đủ','2025-12-03 02:28:55','2025-12-03 02:28:55',NULL),(18,'RECEIPT',8745000.00,'sales','CASH','approved','Quỹ demo','Thu đơn DH-DEMO-018 (paid)','order',18,'DH-DEMO-018',5,1,'Demo Admin','demo-staff','CUST-2020','Phùng Thanh Mai','0912000020','3 Hùng Vương, Huế',NULL,NULL,'2025-11-20','Thanh toán đủ','2025-12-03 02:28:55','2025-12-03 02:28:55',NULL),(19,'PAYMENT',1500000.00,'refund','CASH','approved','Quỹ demo','Hoàn tiền trả hàng RET-DEMO-001','return_order',1,'RET-DEMO-001',2,2,'Demo Manager','demo-staff','CUST-2012','CTCP Gỗ Xanh','0912000012','45 Pasteur, Quận 1, HCM',NULL,NULL,'2025-12-03','Hoàn tiền theo phiếu trả hàng','2025-12-03 02:28:55','2025-12-03 02:28:55',NULL),(20,'PAYMENT',5250000.00,'refund','BANK_TRANSFER','approved','Quỹ demo','Hoàn tiền trả hàng RET-DEMO-002','return_order',2,'RET-DEMO-002',3,2,'Demo Manager','demo-staff','CUST-2013','Hộ KD Minh Quân','0912000013','22 Trần Phú, Nha Trang',NULL,NULL,'2025-12-03','Hoàn tiền theo phiếu trả hàng','2025-12-03 02:28:55','2025-12-03 02:28:55',NULL);
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_coa_code` (`code`),
  KEY `idx_coa_parent` (`parent_id`),
  CONSTRAINT `fk_chart_of_accounts_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `chart_of_accounts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chart_of_accounts`
--

LOCK TABLES `chart_of_accounts` WRITE;
/*!40000 ALTER TABLE `chart_of_accounts` DISABLE KEYS */;
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
INSERT INTO `companies` VALUES (1,'COMP-DEFAULT','Default Company',1,'active','2025-12-03 02:28:54','2025-12-03 02:28:54');
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company_permissions`
--

LOCK TABLES `company_permissions` WRITE;
/*!40000 ALTER TABLE `company_permissions` DISABLE KEYS */;
INSERT INTO `company_permissions` VALUES (2,1,10,'admin','[\"admin\", \"read\", \"write\", \"share\", \"delete\"]','2025-12-03 02:28:54','2025-12-03 02:28:54'),(3,1,11,'manager','[\"read\", \"write\", \"share\"]','2025-12-03 02:28:54','2025-12-03 02:28:54'),(4,1,12,'manager','[\"read\", \"write\", \"share\"]','2025-12-03 02:28:54','2025-12-03 02:28:54'),(5,1,13,'viewer','[\"read\"]','2025-12-03 02:28:54','2025-12-03 02:28:54'),(6,1,14,'viewer','[\"read\"]','2025-12-03 02:28:54','2025-12-03 02:28:54'),(7,1,15,'viewer','[\"read\"]','2025-12-03 02:28:54','2025-12-03 02:28:54');
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupons`
--

LOCK TABLES `coupons` WRITE;
/*!40000 ALTER TABLE `coupons` DISABLE KEYS */;
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
INSERT INTO `customer_groups` VALUES (1,'DEFAULT-GROUP','Default Customer Group',NULL,NULL,NULL,0,'active','2025-12-03 01:35:52','2025-12-03 01:35:52',NULL),(2,'CG-VIP','Khách VIP',NULL,NULL,NULL,0,'active','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(3,'CG-WHS','Khách sỉ',NULL,NULL,NULL,0,'active','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=2021 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (2001,1,NULL,'Nguyễn Minh An','an.demo@lano.local','0912000001',NULL,'MALE',NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-001','12 Trần Hưng Đạo, Hà Nội','Hà Nội','Hoàn Kiếm','Hàng Bài',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(2002,1,NULL,'Trần Thu Hà','ha.demo@lano.local','0912000002',NULL,'FEMALE',NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-002','89 Lý Thường Kiệt, Hà Nội','Hà Nội','Hoàn Kiếm','Cửa Nam',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(2003,1,NULL,'Phạm Gia Bảo','bao.demo@lano.local','0912000003',NULL,'MALE',NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-003','22 Nguyễn Huệ, HCM','Hồ Chí Minh','Quận 1','Bến Nghé',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(2004,1,NULL,'Lê Hồng Nhung','nhung.demo@lano.local','0912000004',NULL,'FEMALE',NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-004','35 Hai Bà Trưng, HCM','Hồ Chí Minh','Quận 1','Bến Thành',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(2005,1,NULL,'Vũ Hoàng Long','long.demo@lano.local','0912000005',NULL,'MALE',NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-005','15 Nguyễn Tri Phương, Đà Nẵng','Đà Nẵng','Hải Châu','Thạch Thang',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(2006,1,NULL,'Đặng Bích Trâm','tram.demo@lano.local','0912000006',NULL,'FEMALE',NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-006','101 Võ Văn Tần, HCM','Hồ Chí Minh','Quận 3','6',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(2007,1,NULL,'Huỳnh Tuấn Kiệt','kiet.demo@lano.local','0912000007',NULL,'MALE',NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-007','45 Trần Phú, Nha Trang','Khánh Hòa','Nha Trang','Lộc Thọ',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(2008,1,NULL,'Lý Thu Uyên','uyen.demo@lano.local','0912000008',NULL,'FEMALE',NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-008','68 Lê Lợi, Huế','Thừa Thiên Huế','Huế','Phú Hội',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(2009,1,NULL,'Ngô Nhật Anh','nha.demo@lano.local','0912000009',NULL,'MALE',NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-009','12 Nguyễn Văn Linh, Đà Nẵng','Đà Nẵng','Hải Châu','Nam Dương',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(2010,1,NULL,'Tạ Kim Yến','yen.demo@lano.local','0912000010',NULL,'FEMALE',NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-010','99 Phan Chu Trinh, Đà Nẵng','Đà Nẵng','Hải Châu','Hải Châu 1',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(2011,1,NULL,'Công ty Ánh Dương','contact@anhduong.vn','0912000011',NULL,NULL,NULL,'COMPANY','Công ty TNHH Ánh Dương','0101234567',NULL,'Công ty TNHH Ánh Dương','11 Duy Tân, Hà Nội',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-011','11 Duy Tân, Cầu Giấy, Hà Nội','Hà Nội','Cầu Giấy','Dịch Vọng',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(2012,1,NULL,'CTCP Gỗ Xanh','ke.toan@goxanh.vn','0912000012',NULL,NULL,NULL,'COMPANY','CTCP Gỗ Xanh','0312345678',NULL,'CTCP Gỗ Xanh','45 Pasteur, Quận 1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-012','45 Pasteur, Quận 1, HCM','Hồ Chí Minh','Quận 1','Bến Nghé',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(2013,1,NULL,'Hộ KD Minh Quân','minhquan@hkd.vn','0912000013',NULL,NULL,NULL,'HOUSEHOLD','Hộ KD Minh Quân','4200123456',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-013','22 Trần Phú, Nha Trang','Khánh Hòa','Nha Trang','Vạn Thạnh',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(2014,1,NULL,'Công ty Vận Tải Nhanh','sale@vantaNhanh.vn','0912000014',NULL,NULL,NULL,'COMPANY','Công ty Vận Tải Nhanh','0109988776',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-014','88 Kim Mã, Ba Đình, Hà Nội','Hà Nội','Ba Đình','Kim Mã',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(2015,1,NULL,'CTY Thiết Kế Mộc','info@thietkemoc.vn','0912000015',NULL,NULL,NULL,'COMPANY','CTY Thiết Kế Mộc','0311122233',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-015','12 Nguyễn Trãi, Quận 5, HCM','Hồ Chí Minh','Quận 5','7',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(2016,1,NULL,'Trịnh Quốc Thái','thai.demo@lano.local','0912000016',NULL,NULL,NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-016','14 Lê Duẩn, Hà Nội','Hà Nội','Ba Đình','Điện Biên',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(2017,1,NULL,'Đỗ Hồng Ngọc','ngoc.demo@lano.local','0912000017',NULL,NULL,NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-017','7 Nguyễn Văn Cừ, Hạ Long','Quảng Ninh','Hạ Long','Bạch Đằng',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(2018,1,NULL,'La Mỹ Duyên','duyen.demo@lano.local','0912000018',NULL,NULL,NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-018','155 Lạch Tray, Hải Phòng','Hải Phòng','Ngô Quyền','Lạch Tray',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(2019,1,NULL,'Đinh Mạnh Cường','cuong.demo@lano.local','0912000019',NULL,NULL,NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-019','18 Lê Lợi, Vinh','Nghệ An','Vinh','Hưng Bình',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(2020,1,NULL,'Phùng Thanh Mai','mai.demo@lano.local','0912000020',NULL,NULL,NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-020','3 Hùng Vương, Huế','Thừa Thiên Huế','Huế','Phú Nhuận',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_note_items`
--

LOCK TABLES `delivery_note_items` WRITE;
/*!40000 ALTER TABLE `delivery_note_items` DISABLE KEYS */;
INSERT INTO `delivery_note_items` VALUES (1,1,1,501,7001,NULL,NULL,1.000,0.000,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(2,1,2,502,NULL,NULL,NULL,1.000,0.000,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(3,2,3,503,NULL,NULL,NULL,2.000,0.000,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(4,3,4,501,7001,NULL,NULL,1.000,0.000,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(5,3,5,503,NULL,NULL,NULL,1.000,0.000,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(6,4,6,502,NULL,NULL,NULL,2.000,0.000,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(7,5,7,501,7002,NULL,NULL,1.000,0.000,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(8,5,8,502,NULL,NULL,NULL,1.000,0.000,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(9,6,9,501,7001,NULL,NULL,1.000,0.500,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(10,6,10,503,NULL,NULL,NULL,1.000,0.500,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(11,7,11,502,NULL,NULL,NULL,1.000,1.000,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(12,7,12,503,NULL,NULL,NULL,1.000,1.000,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(13,8,13,501,7001,NULL,NULL,1.000,0.500,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(14,8,14,502,NULL,NULL,NULL,1.000,0.500,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(15,9,15,501,7001,NULL,NULL,1.000,1.000,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(16,9,16,502,NULL,NULL,NULL,1.000,1.000,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(17,10,17,503,NULL,NULL,NULL,2.000,2.000,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(18,10,18,501,7001,NULL,NULL,1.000,1.000,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(19,11,19,502,NULL,NULL,NULL,1.000,1.000,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(20,11,20,501,7002,NULL,NULL,1.000,1.000,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(21,12,21,503,NULL,NULL,NULL,1.000,1.000,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(22,12,22,501,7001,NULL,NULL,1.000,1.000,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(23,13,23,502,NULL,NULL,NULL,1.000,1.000,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(24,13,24,503,NULL,NULL,NULL,1.000,1.000,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(25,13,25,501,7001,NULL,NULL,1.000,1.000,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(26,14,26,502,NULL,NULL,NULL,2.000,2.000,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(27,15,27,501,7001,NULL,NULL,1.000,1.000,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(28,15,28,503,NULL,NULL,NULL,1.000,1.000,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(29,16,29,502,NULL,NULL,NULL,1.000,1.000,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(30,16,30,501,7001,NULL,NULL,1.000,1.000,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(31,17,31,503,NULL,NULL,NULL,2.000,2.000,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(32,18,32,501,7001,NULL,NULL,1.000,1.000,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(33,18,33,502,NULL,NULL,NULL,1.000,1.000,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_notes`
--

LOCK TABLES `delivery_notes` WRITE;
/*!40000 ALTER TABLE `delivery_notes` DISABLE KEYS */;
INSERT INTO `delivery_notes` VALUES (1,'DN-DEMO-001',1,2001,1,'2025-11-04','2025-11-04','draft','12 Trần Hưng Đạo, Hà Nội','TRK-001','Demo Carrier','Demo delivery note from DH-DEMO-001',NULL,NULL,NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(2,'DN-DEMO-002',2,2002,2,'2025-11-05','2025-11-05','draft','89 Lý Thường Kiệt, Hà Nội','TRK-002','Demo Carrier','Demo delivery note from DH-DEMO-002',NULL,NULL,NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(3,'DN-DEMO-003',3,2003,3,'2025-11-06','2025-11-06','confirmed','22 Nguyễn Huệ, HCM','TRK-003','Demo Carrier','Demo delivery note from DH-DEMO-003',NULL,NULL,NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(4,'DN-DEMO-004',4,2004,4,'2025-11-07','2025-11-07','confirmed','35 Hai Bà Trưng, HCM','TRK-004','Demo Carrier','Demo delivery note from DH-DEMO-004',NULL,NULL,NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(5,'DN-DEMO-005',5,2005,5,'2025-11-08','2025-11-08','confirmed','15 Nguyễn Tri Phương, Đà Nẵng','TRK-005','Demo Carrier','Demo delivery note from DH-DEMO-005',NULL,NULL,NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(6,'DN-DEMO-006',6,2006,1,'2025-11-09','2025-11-09','shipped','101 Võ Văn Tần, HCM','TRK-006','Demo Carrier','Demo delivery note from DH-DEMO-006',NULL,NULL,NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(7,'DN-DEMO-007',7,2007,2,'2025-11-10','2025-11-10','delivered','45 Trần Phú, Nha Trang','TRK-007','Demo Carrier','Demo delivery note from DH-DEMO-007',NULL,NULL,NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(8,'DN-DEMO-008',8,2008,3,'2025-11-11','2025-11-11','shipped','68 Lê Lợi, Huế','TRK-008','Demo Carrier','Demo delivery note from DH-DEMO-008',NULL,NULL,NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(9,'DN-DEMO-009',9,2011,1,'2025-11-12','2025-11-12','delivered','11 Duy Tân, Cầu Giấy, Hà Nội','TRK-009','Demo Carrier','Demo delivery note from DH-DEMO-009',NULL,NULL,NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(10,'DN-DEMO-010',10,2012,2,'2025-11-13','2025-11-13','delivered','45 Pasteur, Quận 1, HCM','TRK-010','Demo Carrier','Demo delivery note from DH-DEMO-010',NULL,NULL,NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(11,'DN-DEMO-011',11,2013,3,'2025-11-14','2025-11-14','delivered','22 Trần Phú, Nha Trang','TRK-011','Demo Carrier','Demo delivery note from DH-DEMO-011',NULL,NULL,NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(12,'DN-DEMO-012',12,2014,4,'2025-11-15','2025-11-15','delivered','88 Kim Mã, Ba Đình, Hà Nội','TRK-012','Demo Carrier','Demo delivery note from DH-DEMO-012',NULL,NULL,NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(13,'DN-DEMO-013',13,2015,5,'2025-11-16','2025-11-16','delivered','12 Nguyễn Trãi, Quận 5, HCM','TRK-013','Demo Carrier','Demo delivery note from DH-DEMO-013',NULL,NULL,NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(14,'DN-DEMO-014',14,2016,1,'2025-11-17','2025-11-17','delivered','14 Lê Duẩn, Hà Nội','TRK-014','Demo Carrier','Demo delivery note from DH-DEMO-014',NULL,NULL,NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(15,'DN-DEMO-015',15,2017,2,'2025-11-18','2025-11-18','delivered','7 Nguyễn Văn Cừ, Hạ Long','TRK-015','Demo Carrier','Demo delivery note from DH-DEMO-015',NULL,NULL,NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(16,'DN-DEMO-016',16,2018,3,'2025-11-19','2025-11-19','delivered','155 Lạch Tray, Hải Phòng','TRK-016','Demo Carrier','Demo delivery note from DH-DEMO-016',NULL,NULL,NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(17,'DN-DEMO-017',17,2019,4,'2025-11-20','2025-11-20','delivered','18 Lê Lợi, Vinh','TRK-017','Demo Carrier','Demo delivery note from DH-DEMO-017',NULL,NULL,NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(18,'DN-DEMO-018',18,2020,5,'2025-11-21','2025-11-21','delivered','3 Hùng Vương, Huế','TRK-018','Demo Carrier','Demo delivery note from DH-DEMO-018',NULL,NULL,NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL);
/*!40000 ALTER TABLE `delivery_notes` ENABLE KEYS */;
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
INSERT INTO `devices` VALUES (1,'DEFAULT-DEVICE','Default Device','pos',NULL,'active',NULL,'2025-12-03 01:35:52','2025-12-03 01:35:52',NULL),(2,'DEV-POS-02','POS HCM','pos',2,'active',NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL);
/*!40000 ALTER TABLE `devices` ENABLE KEYS */;
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_employee_code` (`employee_code`),
  KEY `idx_employee_status` (`status`),
  KEY `fk_employees_branch_id` (`branch_id`),
  CONSTRAINT `fk_employees_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `goods_receipt_items`
--

LOCK TABLES `goods_receipt_items` WRITE;
/*!40000 ALTER TABLE `goods_receipt_items` DISABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `goods_receipts`
--

LOCK TABLES `goods_receipts` WRITE;
/*!40000 ALTER TABLE `goods_receipts` DISABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_movements`
--

LOCK TABLES `inventory_movements` WRITE;
/*!40000 ALTER TABLE `inventory_movements` DISABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_stock`
--

LOCK TABLES `inventory_stock` WRITE;
/*!40000 ALTER TABLE `inventory_stock` DISABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoice_orders`
--

LOCK TABLES `invoice_orders` WRITE;
/*!40000 ALTER TABLE `invoice_orders` DISABLE KEYS */;
INSERT INTO `invoice_orders` VALUES (1,1,9,'2025-12-03 02:28:54','2025-12-03 02:28:54'),(2,2,10,'2025-12-03 02:28:54','2025-12-03 02:28:54'),(3,3,11,'2025-12-03 02:28:54','2025-12-03 02:28:54'),(4,4,12,'2025-12-03 02:28:54','2025-12-03 02:28:54'),(5,5,13,'2025-12-03 02:28:54','2025-12-03 02:28:54');
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
  `other_fee` decimal(10,2) DEFAULT '0.00',
  `shipping_fee` decimal(10,2) DEFAULT '0.00',
  `customer_payable` decimal(10,2) DEFAULT '0.00',
  `customer_paid` decimal(10,2) DEFAULT '0.00',
  `cod_amount` decimal(10,2) DEFAULT '0.00',
  `rounding_adjustment` decimal(10,2) DEFAULT '0.00',
  `payment_status` varchar(20) DEFAULT NULL,
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `fk_invoices_customer_id` (`customer_id`),
  KEY `fk_invoices_branch_id` (`branch_id`),
  CONSTRAINT `fk_invoices_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_invoices_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
INSERT INTO `invoices` VALUES (1,'HD-DEMO-1-0001','completed','standard',NULL,2011,1,'2025-11-23','2025-12-05',9450000.00,9450000.00,0.00,9450000.00,0.10,945000.00,945000.00,0.00,0.00,10395000.00,10395000.00,0.00,0.00,'paid',10395000.00,'VND',1.000000,NULL,10395000.00,NULL,'Hóa đơn demo gắn với DH-DEMO-009','{\"source\": \"demo\"}',1,'2025-12-03 02:28:54','2025-12-03 02:28:54'),(2,'HD-DEMO-2-0001','completed','standard',NULL,2012,2,'2025-11-24','2025-12-06',9030000.00,10500000.00,1500000.00,9030000.00,0.05,451500.00,451500.00,0.00,30000.00,9030000.00,9030000.00,0.00,0.00,'paid',9030000.00,'VND',1.000000,NULL,9030000.00,NULL,'Hóa đơn demo gắn với DH-DEMO-010','{\"source\": \"demo\"}',1,'2025-12-03 02:28:54','2025-12-03 02:28:54'),(3,'HD-DEMO-3-0001','completed','standard',NULL,2013,3,'2025-11-25','2025-12-07',7220000.00,9450000.00,2250000.00,7220000.00,0.05,361000.00,361000.00,0.00,20000.00,7581000.00,7581000.00,0.00,0.00,'paid',7581000.00,'VND',1.000000,NULL,7581000.00,NULL,'Hóa đơn demo gắn với DH-DEMO-011','{\"source\": \"demo\"}',1,'2025-12-03 02:28:54','2025-12-03 02:28:54'),(4,'HD-DEMO-4-0001','completed','standard',NULL,2014,4,'2025-11-26','2025-12-08',9000000.00,9000000.00,0.00,9000000.00,0.10,900000.00,900000.00,0.00,0.00,9900000.00,9900000.00,0.00,0.00,'paid',9900000.00,'VND',1.000000,NULL,9900000.00,NULL,'Hóa đơn demo gắn với DH-DEMO-012','{\"source\": \"demo\"}',1,'2025-12-03 02:28:54','2025-12-03 02:28:54'),(5,'HD-DEMO-5-0001','completed','standard',NULL,2015,5,'2025-11-27','2025-12-09',9465000.00,10950000.00,1500000.00,9465000.00,0.05,473250.00,473250.00,0.00,15000.00,9465000.00,9465000.00,0.00,0.00,'paid',9465000.00,'VND',1.000000,NULL,9465000.00,NULL,'Hóa đơn demo gắn với DH-DEMO-013','{\"source\": \"demo\"}',1,'2025-12-03 02:28:54','2025-12-03 02:28:54');
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leads`
--

LOCK TABLES `leads` WRITE;
/*!40000 ALTER TABLE `leads` DISABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_applications`
--

LOCK TABLES `leave_applications` WRITE;
/*!40000 ALTER TABLE `leave_applications` DISABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_types`
--

LOCK TABLES `leave_types` WRITE;
/*!40000 ALTER TABLE `leave_types` DISABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2024-01-01-000001','App\\Database\\Migrations\\CreateAuthTables','default','App',1764725629,1),(2,'2025-11-20-000002','App\\Database\\Migrations\\CreateProductTables','default','App',1764725629,1),(3,'2025-11-22-000003','App\\Database\\Migrations\\AddSkuIndexes','default','App',1764725736,2),(4,'2025-11-23-000004','App\\Database\\Migrations\\CreatePriceListTables','default','App',1764725736,2),(5,'2025-11-23-000005','App\\Database\\Migrations\\CreateOrderTables','default','App',1764725736,2),(6,'2025-11-24-000006','App\\Database\\Migrations\\AddPriceListFormula','default','App',1764725736,2),(7,'2025-11-24-000007','App\\Database\\Migrations\\CreatePaymentMethodTables','default','App',1764725736,2),(8,'2025-11-24-000008','App\\Database\\Migrations\\CreateInvoiceTables','default','App',1764725736,2),(9,'2025-11-24-000009','App\\Database\\Migrations\\CreateReturnTables','default','App',1764725736,2),(10,'2025-11-24-000010','App\\Database\\Migrations\\CreateSupportingTables','default','App',1764725736,2),(11,'2025-11-24-000011','App\\Database\\Migrations\\UpdateOrdersForCreate','default','App',1764725736,2),(12,'2025-11-24-000012','App\\Database\\Migrations\\AddOrderStatusTimestamps','default','App',1764725736,2),(13,'2025-11-24-000013','App\\Database\\Migrations\\CreateInventoryStock','default','App',1764725736,2),(14,'2025-11-24-000014','App\\Database\\Migrations\\CreateWebhookTables','default','App',1764725736,2),(15,'2025-11-25-000015','App\\Database\\Migrations\\CreateOrderSequences','default','App',1764725736,2),(16,'2025-11-26-000016','App\\Database\\Migrations\\CreateCustomersTable','default','App',1764725736,2),(17,'2025-11-26-000017','App\\Database\\Migrations\\AddCustomerExtendedFields','default','App',1764725736,2),(18,'2025-11-26-000017','App\\Database\\Migrations\\CreateCashTransactionsTable','default','App',1764725736,2),(19,'2025-11-26-000080','App\\Database\\Migrations\\AlterCashTransactionsAddPaymentFields','default','App',1764725736,2),(20,'2025-11-26-000081','App\\Database\\Migrations\\AlterCashTransactionsAddPayerFields','default','App',1764725736,2),(21,'2025-11-26-000082','App\\Database\\Migrations\\CreateOrderPaymentsTable','default','App',1764725736,2),(22,'2025-11-27-000021','App\\Database\\Migrations\\UpdateInvoicesSnapshots','default','App',1764725736,2),(23,'2025-11-28-001001','App\\Database\\Migrations\\CreateBatchSerialTracking','default','App',1764725736,2),(24,'2025-11-29-001002','App\\Database\\Migrations\\CreateDeliveryNoteTables','default','App',1764725736,2),(25,'2025-11-29-001003','App\\Database\\Migrations\\CreateApprovalTables','default','App',1764725736,2),(26,'2025-11-29-001004','App\\Database\\Migrations\\CreateStockLedgerTables','default','App',1764725736,2),(27,'2025-11-29-001005','App\\Database\\Migrations\\CreateAdvancedPricingTables','default','App',1764725736,2),(28,'2025-11-29-001006','App\\Database\\Migrations\\CreateReorderPlanningTables','default','App',1764725736,2),(29,'2025-11-29-001007','App\\Database\\Migrations\\CreateQualityTables','default','App',1764725737,2),(30,'2025-11-29-001008','App\\Database\\Migrations\\CreateOrderTemplateTables','default','App',1764725737,2),(31,'2025-11-29-001009','App\\Database\\Migrations\\CreateManufacturingTables','default','App',1764725737,2),(32,'2025-11-29-001010','App\\Database\\Migrations\\CreateEcommerceWebhookLogs','default','App',1764725737,2),(33,'2025-11-29-001011','App\\Database\\Migrations\\CreateSubscriptionTables','default','App',1764725737,2),(34,'2025-11-30-001012','App\\Database\\Migrations\\CreateCompanyPermissionTables','default','App',1764725737,2),(35,'2025-12-02-000001','App\\Database\\Migrations\\AddMissingForeignKeys','default','App',1764725737,2),(36,'2025-12-02-000002','App\\Database\\Migrations\\StrengthenOrderRelations','default','App',1764725737,2),(37,'2025-12-02-000003','App\\Database\\Migrations\\AddComprehensiveForeignKeys','default','App',1764725752,2),(38,'2025-12-02-000004','App\\Database\\Migrations\\AddRemainingForeignKeys','default','App',1764725752,2),(39,'2025-12-02-000005','App\\Database\\Migrations\\AddMasterEntities','default','App',1764725753,2),(40,'2025-12-02-000006','App\\Database\\Migrations\\AddLateForeignKeys','default','App',1764725754,2),(41,'2025-12-02-000007','App\\Database\\Migrations\\SoftDeleteAndSchemaAdjust','default','App',1764725755,2),(42,'2025-12-02-000008','App\\Database\\Migrations\\AddChecksAndUniques','default','App',1764725756,2);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
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
INSERT INTO `model_has_roles` VALUES (1,'App\\Models\\User',1),(1,'App\\Models\\User',10),(2,'App\\Models\\User',2),(2,'App\\Models\\User',11),(2,'App\\Models\\User',12),(3,'App\\Models\\User',2),(3,'App\\Models\\User',3),(3,'App\\Models\\User',13),(3,'App\\Models\\User',14),(3,'App\\Models\\User',15),(4,'App\\Models\\User',3);
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
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,1,501,7001,NULL,NULL,1.000,7500000.00,7500000.00,NULL,NULL,'2025-11-03 10:00:00','2025-11-03 10:00:00',NULL),(2,1,502,NULL,NULL,NULL,1.000,1950000.00,1950000.00,NULL,NULL,'2025-11-03 10:00:00','2025-11-03 10:00:00',NULL),(3,2,503,NULL,NULL,NULL,2.000,1500000.00,1500000.00,NULL,NULL,'2025-11-04 10:00:00','2025-11-04 10:00:00',NULL),(4,3,501,7001,NULL,NULL,1.000,7500000.00,6000000.00,1,'VIP 20%','2025-11-05 10:00:00','2025-11-05 10:00:00',NULL),(5,3,503,NULL,NULL,NULL,1.000,1500000.00,1500000.00,NULL,NULL,'2025-11-05 10:00:00','2025-11-05 10:00:00',NULL),(6,4,502,NULL,NULL,NULL,2.000,1950000.00,1950000.00,NULL,NULL,'2025-11-06 10:00:00','2025-11-06 10:00:00',NULL),(7,5,501,7002,NULL,NULL,1.000,7500000.00,5250000.00,3,'Flash Sale 30%','2025-11-07 10:00:00','2025-11-07 10:00:00',NULL),(8,5,502,NULL,NULL,NULL,1.000,1950000.00,1950000.00,NULL,NULL,'2025-11-07 10:00:00','2025-11-07 10:00:00',NULL),(9,6,501,7001,NULL,NULL,1.000,7500000.00,7500000.00,NULL,NULL,'2025-11-08 10:00:00','2025-11-08 10:00:00',NULL),(10,6,503,NULL,NULL,NULL,1.000,1500000.00,1500000.00,NULL,NULL,'2025-11-08 10:00:00','2025-11-08 10:00:00',NULL),(11,7,502,NULL,NULL,NULL,1.000,1950000.00,1950000.00,NULL,NULL,'2025-11-09 10:00:00','2025-11-09 10:00:00',NULL),(12,7,503,NULL,NULL,NULL,1.000,1500000.00,1500000.00,NULL,NULL,'2025-11-09 10:00:00','2025-11-09 10:00:00',NULL),(13,8,501,7001,NULL,NULL,1.000,7500000.00,6000000.00,1,'VIP 20%','2025-11-10 10:00:00','2025-11-10 10:00:00',NULL),(14,8,502,NULL,NULL,NULL,1.000,1950000.00,1950000.00,NULL,NULL,'2025-11-10 10:00:00','2025-11-10 10:00:00',NULL),(15,9,501,7001,NULL,NULL,1.000,7500000.00,7500000.00,NULL,NULL,'2025-11-11 10:00:00','2025-11-11 10:00:00',NULL),(16,9,502,NULL,NULL,NULL,1.000,1950000.00,1950000.00,NULL,NULL,'2025-11-11 10:00:00','2025-11-11 10:00:00',NULL),(17,10,503,NULL,NULL,NULL,2.000,1500000.00,1500000.00,NULL,NULL,'2025-11-12 10:00:00','2025-11-12 10:00:00',NULL),(18,10,501,7001,NULL,NULL,1.000,7500000.00,6000000.00,1,'VIP 20%','2025-11-12 10:00:00','2025-11-12 10:00:00',NULL),(19,11,502,NULL,NULL,NULL,1.000,1950000.00,1950000.00,NULL,NULL,'2025-11-13 10:00:00','2025-11-13 10:00:00',NULL),(20,11,501,7002,NULL,NULL,1.000,7500000.00,5250000.00,3,'Flash Sale 30%','2025-11-13 10:00:00','2025-11-13 10:00:00',NULL),(21,12,503,NULL,NULL,NULL,1.000,1500000.00,1500000.00,NULL,NULL,'2025-11-14 10:00:00','2025-11-14 10:00:00',NULL),(22,12,501,7001,NULL,NULL,1.000,7500000.00,7500000.00,NULL,NULL,'2025-11-14 10:00:00','2025-11-14 10:00:00',NULL),(23,13,502,NULL,NULL,NULL,1.000,1950000.00,1950000.00,NULL,NULL,'2025-11-15 10:00:00','2025-11-15 10:00:00',NULL),(24,13,503,NULL,NULL,NULL,1.000,1500000.00,1500000.00,NULL,NULL,'2025-11-15 10:00:00','2025-11-15 10:00:00',NULL),(25,13,501,7001,NULL,NULL,1.000,7500000.00,6000000.00,1,'VIP 20%','2025-11-15 10:00:00','2025-11-15 10:00:00',NULL),(26,14,502,NULL,NULL,NULL,2.000,1950000.00,1950000.00,NULL,NULL,'2025-11-16 10:00:00','2025-11-16 10:00:00',NULL),(27,15,501,7001,NULL,NULL,1.000,7500000.00,7500000.00,NULL,NULL,'2025-11-17 10:00:00','2025-11-17 10:00:00',NULL),(28,15,503,NULL,NULL,NULL,1.000,1500000.00,1500000.00,NULL,NULL,'2025-11-17 10:00:00','2025-11-17 10:00:00',NULL),(29,16,502,NULL,NULL,NULL,1.000,1950000.00,1950000.00,NULL,NULL,'2025-11-18 10:00:00','2025-11-18 10:00:00',NULL),(30,16,501,7001,NULL,NULL,1.000,7500000.00,7500000.00,NULL,NULL,'2025-11-18 10:00:00','2025-11-18 10:00:00',NULL),(31,17,503,NULL,NULL,NULL,2.000,1500000.00,1500000.00,NULL,NULL,'2025-11-19 10:00:00','2025-11-19 10:00:00',NULL),(32,18,501,7001,NULL,NULL,1.000,7500000.00,6000000.00,1,'VIP 20%','2025-11-20 10:00:00','2025-11-20 10:00:00',NULL),(33,18,502,NULL,NULL,NULL,1.000,1950000.00,1950000.00,NULL,NULL,'2025-11-20 10:00:00','2025-11-20 10:00:00',NULL),(34,19,502,NULL,NULL,NULL,1.000,1950000.00,1950000.00,NULL,NULL,'2025-11-21 10:00:00','2025-11-21 10:00:00',NULL),(35,20,503,NULL,NULL,NULL,1.000,1500000.00,1500000.00,NULL,NULL,'2025-11-22 10:00:00','2025-11-22 10:00:00',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_payments`
--

LOCK TABLES `order_payments` WRITE;
/*!40000 ALTER TABLE `order_payments` DISABLE KEYS */;
INSERT INTO `order_payments` VALUES (1,1,'CASH',0.00,NULL,'2025-11-03 12:00:00','2025-11-03 12:00:00'),(2,2,'BANK_TRANSFER',1117200.00,'2025-11-04 12:00:00','2025-11-04 12:00:00','2025-11-04 12:00:00'),(3,3,'COD',3315400.00,'2025-11-05 12:00:00','2025-11-05 12:00:00','2025-11-05 12:00:00'),(4,4,'CASH',1950000.00,'2025-11-06 12:00:00','2025-11-06 12:00:00','2025-11-06 12:00:00'),(5,5,'EWALLET',1521450.00,'2025-11-07 12:00:00','2025-11-07 12:00:00','2025-11-07 12:00:00'),(6,6,'COD',5956500.00,'2025-11-08 12:00:00','2025-11-08 12:00:00','2025-11-08 12:00:00'),(7,7,'BANK_TRANSFER',1735000.00,'2025-11-09 12:00:00','2025-11-09 12:00:00','2025-11-09 12:00:00'),(8,8,'CASH',5843250.00,'2025-11-10 12:00:00','2025-11-10 12:00:00','2025-11-10 12:00:00'),(9,9,'BANK_TRANSFER',10395000.00,'2025-11-11 12:00:00','2025-11-11 12:00:00','2025-11-11 12:00:00'),(10,10,'BANK_TRANSFER',9030000.00,'2025-11-12 12:00:00','2025-11-12 12:00:00','2025-11-12 12:00:00'),(11,11,'CASH',7581000.00,'2025-11-13 12:00:00','2025-11-13 12:00:00','2025-11-13 12:00:00'),(12,12,'COD',9900000.00,'2025-11-14 12:00:00','2025-11-14 12:00:00','2025-11-14 12:00:00'),(13,13,'BANK_TRANSFER',9465000.00,'2025-11-15 12:00:00','2025-11-15 12:00:00','2025-11-15 12:00:00'),(14,14,'CASH',4095000.00,'2025-11-16 12:00:00','2025-11-16 12:00:00','2025-11-16 12:00:00'),(15,15,'EWALLET',9927500.00,'2025-11-17 12:00:00','2025-11-17 12:00:00','2025-11-17 12:00:00'),(16,16,'CASH',9450000.00,'2025-11-18 12:00:00','2025-11-18 12:00:00','2025-11-18 12:00:00'),(17,17,'BANK_TRANSFER',3192000.00,'2025-11-19 12:00:00','2025-11-19 12:00:00','2025-11-19 12:00:00'),(18,18,'CASH',8745000.00,'2025-11-20 12:00:00','2025-11-20 12:00:00','2025-11-20 12:00:00'),(19,19,'CASH',0.00,NULL,'2025-11-21 12:00:00','2025-11-21 12:00:00'),(20,20,'COD',239400.00,'2025-11-22 12:00:00','2025-11-22 12:00:00','2025-11-22 12:00:00');
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
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_status_logs`
--

LOCK TABLES `order_status_logs` WRITE;
/*!40000 ALTER TABLE `order_status_logs` DISABLE KEYS */;
INSERT INTO `order_status_logs` VALUES (1,1,NULL,'created',1,'Auto demo log','2025-11-03 10:00:00','2025-11-03 10:00:00','2025-11-03 10:00:00'),(2,2,NULL,'created',1,'Auto demo log','2025-11-04 10:00:00','2025-11-04 10:00:00','2025-11-04 10:00:00'),(3,3,NULL,'created',1,'Auto demo log','2025-11-05 10:00:00','2025-11-05 10:00:00','2025-11-05 10:00:00'),(4,3,'created','confirmed',1,'Auto demo log','2025-11-05 11:00:00','2025-11-05 11:00:00','2025-11-05 11:00:00'),(5,3,'confirmed','processing',1,'Auto demo log','2025-11-05 12:00:00','2025-11-05 12:00:00','2025-11-05 12:00:00'),(6,4,NULL,'created',1,'Auto demo log','2025-11-06 10:00:00','2025-11-06 10:00:00','2025-11-06 10:00:00'),(7,4,'created','confirmed',1,'Auto demo log','2025-11-06 11:00:00','2025-11-06 11:00:00','2025-11-06 11:00:00'),(8,4,'confirmed','processing',1,'Auto demo log','2025-11-06 12:00:00','2025-11-06 12:00:00','2025-11-06 12:00:00'),(9,5,NULL,'created',1,'Auto demo log','2025-11-07 10:00:00','2025-11-07 10:00:00','2025-11-07 10:00:00'),(10,5,'created','confirmed',1,'Auto demo log','2025-11-07 11:00:00','2025-11-07 11:00:00','2025-11-07 11:00:00'),(11,5,'confirmed','processing',1,'Auto demo log','2025-11-07 12:00:00','2025-11-07 12:00:00','2025-11-07 12:00:00'),(12,6,NULL,'created',1,'Auto demo log','2025-11-08 10:00:00','2025-11-08 10:00:00','2025-11-08 10:00:00'),(13,6,'created','confirmed',1,'Auto demo log','2025-11-08 11:00:00','2025-11-08 11:00:00','2025-11-08 11:00:00'),(14,6,'confirmed','processing',1,'Auto demo log','2025-11-08 12:00:00','2025-11-08 12:00:00','2025-11-08 12:00:00'),(15,6,'processing','shipping',1,'Auto demo log','2025-11-08 13:00:00','2025-11-08 13:00:00','2025-11-08 13:00:00'),(16,7,NULL,'created',1,'Auto demo log','2025-11-09 10:00:00','2025-11-09 10:00:00','2025-11-09 10:00:00'),(17,7,'created','confirmed',1,'Auto demo log','2025-11-09 11:00:00','2025-11-09 11:00:00','2025-11-09 11:00:00'),(18,7,'confirmed','processing',1,'Auto demo log','2025-11-09 12:00:00','2025-11-09 12:00:00','2025-11-09 12:00:00'),(19,7,'processing','shipping',1,'Auto demo log','2025-11-09 13:00:00','2025-11-09 13:00:00','2025-11-09 13:00:00'),(20,8,NULL,'created',1,'Auto demo log','2025-11-10 10:00:00','2025-11-10 10:00:00','2025-11-10 10:00:00'),(21,8,'created','confirmed',1,'Auto demo log','2025-11-10 11:00:00','2025-11-10 11:00:00','2025-11-10 11:00:00'),(22,8,'confirmed','processing',1,'Auto demo log','2025-11-10 12:00:00','2025-11-10 12:00:00','2025-11-10 12:00:00'),(23,8,'processing','shipping',1,'Auto demo log','2025-11-10 13:00:00','2025-11-10 13:00:00','2025-11-10 13:00:00'),(24,9,NULL,'created',1,'Auto demo log','2025-11-11 10:00:00','2025-11-11 10:00:00','2025-11-11 10:00:00'),(25,9,'created','confirmed',1,'Auto demo log','2025-11-11 11:00:00','2025-11-11 11:00:00','2025-11-11 11:00:00'),(26,9,'confirmed','processing',1,'Auto demo log','2025-11-11 12:00:00','2025-11-11 12:00:00','2025-11-11 12:00:00'),(27,9,'processing','shipping',1,'Auto demo log','2025-11-11 13:00:00','2025-11-11 13:00:00','2025-11-11 13:00:00'),(28,9,'shipping','delivered',1,'Auto demo log','2025-11-11 14:00:00','2025-11-11 14:00:00','2025-11-11 14:00:00'),(29,9,'delivered','completed',1,'Auto demo log','2025-11-11 15:00:00','2025-11-11 15:00:00','2025-11-11 15:00:00'),(30,10,NULL,'created',1,'Auto demo log','2025-11-12 10:00:00','2025-11-12 10:00:00','2025-11-12 10:00:00'),(31,10,'created','confirmed',1,'Auto demo log','2025-11-12 11:00:00','2025-11-12 11:00:00','2025-11-12 11:00:00'),(32,10,'confirmed','processing',1,'Auto demo log','2025-11-12 12:00:00','2025-11-12 12:00:00','2025-11-12 12:00:00'),(33,10,'processing','shipping',1,'Auto demo log','2025-11-12 13:00:00','2025-11-12 13:00:00','2025-11-12 13:00:00'),(34,10,'shipping','delivered',1,'Auto demo log','2025-11-12 14:00:00','2025-11-12 14:00:00','2025-11-12 14:00:00'),(35,10,'delivered','completed',1,'Auto demo log','2025-11-12 15:00:00','2025-11-12 15:00:00','2025-11-12 15:00:00'),(36,11,NULL,'created',1,'Auto demo log','2025-11-13 10:00:00','2025-11-13 10:00:00','2025-11-13 10:00:00'),(37,11,'created','confirmed',1,'Auto demo log','2025-11-13 11:00:00','2025-11-13 11:00:00','2025-11-13 11:00:00'),(38,11,'confirmed','processing',1,'Auto demo log','2025-11-13 12:00:00','2025-11-13 12:00:00','2025-11-13 12:00:00'),(39,11,'processing','shipping',1,'Auto demo log','2025-11-13 13:00:00','2025-11-13 13:00:00','2025-11-13 13:00:00'),(40,11,'shipping','delivered',1,'Auto demo log','2025-11-13 14:00:00','2025-11-13 14:00:00','2025-11-13 14:00:00'),(41,11,'delivered','completed',1,'Auto demo log','2025-11-13 15:00:00','2025-11-13 15:00:00','2025-11-13 15:00:00'),(42,12,NULL,'created',1,'Auto demo log','2025-11-14 10:00:00','2025-11-14 10:00:00','2025-11-14 10:00:00'),(43,12,'created','confirmed',1,'Auto demo log','2025-11-14 11:00:00','2025-11-14 11:00:00','2025-11-14 11:00:00'),(44,12,'confirmed','processing',1,'Auto demo log','2025-11-14 12:00:00','2025-11-14 12:00:00','2025-11-14 12:00:00'),(45,12,'processing','shipping',1,'Auto demo log','2025-11-14 13:00:00','2025-11-14 13:00:00','2025-11-14 13:00:00'),(46,12,'shipping','delivered',1,'Auto demo log','2025-11-14 14:00:00','2025-11-14 14:00:00','2025-11-14 14:00:00'),(47,12,'delivered','completed',1,'Auto demo log','2025-11-14 15:00:00','2025-11-14 15:00:00','2025-11-14 15:00:00'),(48,13,NULL,'created',1,'Auto demo log','2025-11-15 10:00:00','2025-11-15 10:00:00','2025-11-15 10:00:00'),(49,13,'created','confirmed',1,'Auto demo log','2025-11-15 11:00:00','2025-11-15 11:00:00','2025-11-15 11:00:00'),(50,13,'confirmed','processing',1,'Auto demo log','2025-11-15 12:00:00','2025-11-15 12:00:00','2025-11-15 12:00:00'),(51,13,'processing','shipping',1,'Auto demo log','2025-11-15 13:00:00','2025-11-15 13:00:00','2025-11-15 13:00:00'),(52,13,'shipping','delivered',1,'Auto demo log','2025-11-15 14:00:00','2025-11-15 14:00:00','2025-11-15 14:00:00'),(53,13,'delivered','completed',1,'Auto demo log','2025-11-15 15:00:00','2025-11-15 15:00:00','2025-11-15 15:00:00'),(54,14,NULL,'created',1,'Auto demo log','2025-11-16 10:00:00','2025-11-16 10:00:00','2025-11-16 10:00:00'),(55,14,'created','confirmed',1,'Auto demo log','2025-11-16 11:00:00','2025-11-16 11:00:00','2025-11-16 11:00:00'),(56,14,'confirmed','processing',1,'Auto demo log','2025-11-16 12:00:00','2025-11-16 12:00:00','2025-11-16 12:00:00'),(57,14,'processing','shipping',1,'Auto demo log','2025-11-16 13:00:00','2025-11-16 13:00:00','2025-11-16 13:00:00'),(58,14,'shipping','delivered',1,'Auto demo log','2025-11-16 14:00:00','2025-11-16 14:00:00','2025-11-16 14:00:00'),(59,14,'delivered','completed',1,'Auto demo log','2025-11-16 15:00:00','2025-11-16 15:00:00','2025-11-16 15:00:00'),(60,15,NULL,'created',1,'Auto demo log','2025-11-17 10:00:00','2025-11-17 10:00:00','2025-11-17 10:00:00'),(61,15,'created','confirmed',1,'Auto demo log','2025-11-17 11:00:00','2025-11-17 11:00:00','2025-11-17 11:00:00'),(62,15,'confirmed','processing',1,'Auto demo log','2025-11-17 12:00:00','2025-11-17 12:00:00','2025-11-17 12:00:00'),(63,15,'processing','shipping',1,'Auto demo log','2025-11-17 13:00:00','2025-11-17 13:00:00','2025-11-17 13:00:00'),(64,15,'shipping','delivered',1,'Auto demo log','2025-11-17 14:00:00','2025-11-17 14:00:00','2025-11-17 14:00:00'),(65,15,'delivered','completed',1,'Auto demo log','2025-11-17 15:00:00','2025-11-17 15:00:00','2025-11-17 15:00:00'),(66,16,NULL,'created',1,'Auto demo log','2025-11-18 10:00:00','2025-11-18 10:00:00','2025-11-18 10:00:00'),(67,16,'created','confirmed',1,'Auto demo log','2025-11-18 11:00:00','2025-11-18 11:00:00','2025-11-18 11:00:00'),(68,16,'confirmed','processing',1,'Auto demo log','2025-11-18 12:00:00','2025-11-18 12:00:00','2025-11-18 12:00:00'),(69,16,'processing','shipping',1,'Auto demo log','2025-11-18 13:00:00','2025-11-18 13:00:00','2025-11-18 13:00:00'),(70,16,'shipping','delivered',1,'Auto demo log','2025-11-18 14:00:00','2025-11-18 14:00:00','2025-11-18 14:00:00'),(71,16,'delivered','completed',1,'Auto demo log','2025-11-18 15:00:00','2025-11-18 15:00:00','2025-11-18 15:00:00'),(72,17,NULL,'created',1,'Auto demo log','2025-11-19 10:00:00','2025-11-19 10:00:00','2025-11-19 10:00:00'),(73,17,'created','confirmed',1,'Auto demo log','2025-11-19 11:00:00','2025-11-19 11:00:00','2025-11-19 11:00:00'),(74,17,'confirmed','processing',1,'Auto demo log','2025-11-19 12:00:00','2025-11-19 12:00:00','2025-11-19 12:00:00'),(75,17,'processing','shipping',1,'Auto demo log','2025-11-19 13:00:00','2025-11-19 13:00:00','2025-11-19 13:00:00'),(76,17,'shipping','delivered',1,'Auto demo log','2025-11-19 14:00:00','2025-11-19 14:00:00','2025-11-19 14:00:00'),(77,17,'delivered','completed',1,'Auto demo log','2025-11-19 15:00:00','2025-11-19 15:00:00','2025-11-19 15:00:00'),(78,18,NULL,'created',1,'Auto demo log','2025-11-20 10:00:00','2025-11-20 10:00:00','2025-11-20 10:00:00'),(79,18,'created','confirmed',1,'Auto demo log','2025-11-20 11:00:00','2025-11-20 11:00:00','2025-11-20 11:00:00'),(80,18,'confirmed','processing',1,'Auto demo log','2025-11-20 12:00:00','2025-11-20 12:00:00','2025-11-20 12:00:00'),(81,18,'processing','shipping',1,'Auto demo log','2025-11-20 13:00:00','2025-11-20 13:00:00','2025-11-20 13:00:00'),(82,18,'shipping','delivered',1,'Auto demo log','2025-11-20 14:00:00','2025-11-20 14:00:00','2025-11-20 14:00:00'),(83,18,'delivered','completed',1,'Auto demo log','2025-11-20 15:00:00','2025-11-20 15:00:00','2025-11-20 15:00:00'),(84,19,NULL,'created',1,'Auto demo log','2025-11-21 10:00:00','2025-11-21 10:00:00','2025-11-21 10:00:00'),(85,19,'created','cancelled',1,'Auto demo log','2025-11-21 11:00:00','2025-11-21 11:00:00','2025-11-21 11:00:00'),(86,20,NULL,'created',1,'Auto demo log','2025-11-22 10:00:00','2025-11-22 10:00:00','2025-11-22 10:00:00'),(87,20,'created','cancelled',1,'Auto demo log','2025-11-22 11:00:00','2025-11-22 11:00:00','2025-11-22 11:00:00');
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
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,NULL,'DH-DEMO-001',2001,NULL,1,NULL,'2025-11-03','offline',NULL,NULL,9001,0.00,0.00,'CASH','draft',NULL,0.00,0,0.00,0,9450000.00,0.00,30000.00,9480000.00,0.00,9480000.00,'unpaid',0,NULL,'Nguyễn Minh An','0912000001','12 Trần Hưng Đạo, Hà Nội','Hàng Bài','Hoàn Kiếm','Hà Nội',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-11-03 10:00:00','2025-11-03 10:00:00',NULL),(2,NULL,'DH-DEMO-002',2002,NULL,2,NULL,'2025-11-04','online',NULL,NULL,9002,152000.00,0.00,'BANK_TRANSFER','draft',NULL,0.00,0,0.00,0,3000000.00,0.00,40000.00,3192000.00,1117200.00,2074800.00,'partial',0,NULL,'Trần Thu Hà','0912000002','89 Lý Thường Kiệt, Hà Nội','Cửa Nam','Hoàn Kiếm','Hà Nội',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-11-04 10:00:00','2025-11-04 10:00:00',NULL),(3,NULL,'DH-DEMO-003',2003,NULL,3,NULL,'2025-11-05','online',NULL,NULL,9003,753500.00,0.00,'COD','processing',NULL,0.00,0,0.00,0,9000000.00,1500000.00,35000.00,8288500.00,3315400.00,4973100.00,'partial',0,NULL,'Phạm Gia Bảo','0912000003','22 Nguyễn Huệ, HCM','Bến Nghé','Quận 1','Hồ Chí Minh',NULL,'2025-11-05 11:00:00','2025-11-05 12:00:00',NULL,NULL,NULL,NULL,NULL,0,'2025-11-05 10:00:00','2025-11-05 12:00:00',NULL),(4,NULL,'DH-DEMO-004',2004,NULL,4,NULL,'2025-11-06','offline',NULL,NULL,9001,0.00,0.00,'CASH','processing',NULL,0.00,0,0.00,0,3900000.00,0.00,0.00,3900000.00,1950000.00,1950000.00,'partial',0,NULL,'Lê Hồng Nhung','0912000004','35 Hai Bà Trưng, HCM','Bến Thành','Quận 1','Hồ Chí Minh',NULL,'2025-11-06 11:00:00','2025-11-06 12:00:00',NULL,NULL,NULL,NULL,NULL,0,'2025-11-06 10:00:00','2025-11-06 12:00:00',NULL),(5,NULL,'DH-DEMO-005',2005,NULL,5,NULL,'2025-11-07','online',NULL,NULL,9002,362250.00,0.00,'EWALLET','processing',NULL,0.00,0,0.00,0,9450000.00,2250000.00,45000.00,7607250.00,1521450.00,6085800.00,'partial',0,NULL,'Vũ Hoàng Long','0912000005','15 Nguyễn Tri Phương, Đà Nẵng','Thạch Thang','Hải Châu','Đà Nẵng',NULL,'2025-11-07 11:00:00','2025-11-07 12:00:00',NULL,NULL,NULL,NULL,NULL,0,'2025-11-07 10:00:00','2025-11-07 12:00:00',NULL),(6,NULL,'DH-DEMO-006',2006,NULL,1,NULL,'2025-11-08','online',NULL,NULL,9003,902500.00,0.00,'COD','shipping',NULL,0.00,0,0.00,0,9000000.00,0.00,25000.00,9927500.00,5956500.00,3971000.00,'partial',0,NULL,'Đặng Bích Trâm','0912000006','101 Võ Văn Tần, HCM','6','Quận 3','Hồ Chí Minh',NULL,'2025-11-08 11:00:00','2025-11-08 12:00:00','2025-11-08 13:00:00',NULL,NULL,NULL,NULL,0,'2025-11-08 10:00:00','2025-11-08 13:00:00',NULL),(7,NULL,'DH-DEMO-007',2007,NULL,2,NULL,'2025-11-09','online',NULL,NULL,9001,0.00,0.00,'BANK_TRANSFER','shipping',NULL,0.00,0,0.00,0,3450000.00,0.00,20000.00,3470000.00,1735000.00,1735000.00,'partial',0,NULL,'Huỳnh Tuấn Kiệt','0912000007','45 Trần Phú, Nha Trang','Lộc Thọ','Nha Trang','Khánh Hòa',NULL,'2025-11-09 11:00:00','2025-11-09 12:00:00','2025-11-09 13:00:00',NULL,NULL,NULL,NULL,0,'2025-11-09 10:00:00','2025-11-09 13:00:00',NULL),(8,NULL,'DH-DEMO-008',2008,NULL,3,NULL,'2025-11-10','offline',NULL,NULL,9002,397500.00,0.00,'CASH','shipping',NULL,0.00,0,0.00,0,9450000.00,1500000.00,0.00,8347500.00,5843250.00,2504250.00,'partial',0,NULL,'Lý Thu Uyên','0912000008','68 Lê Lợi, Huế','Phú Hội','Huế','Thừa Thiên Huế',NULL,'2025-11-10 11:00:00','2025-11-10 12:00:00','2025-11-10 13:00:00',NULL,NULL,NULL,NULL,0,'2025-11-10 10:00:00','2025-11-10 13:00:00',NULL),(9,NULL,'DH-DEMO-009',2011,NULL,1,NULL,'2025-11-11','offline',NULL,NULL,9003,945000.00,0.00,'BANK_TRANSFER','completed',NULL,0.00,0,0.00,0,9450000.00,0.00,0.00,10395000.00,10395000.00,0.00,'paid',1,NULL,'Công ty Ánh Dương','0912000011','11 Duy Tân, Cầu Giấy, Hà Nội','Dịch Vọng','Cầu Giấy','Hà Nội',NULL,'2025-11-11 11:00:00','2025-11-11 12:00:00','2025-11-11 13:00:00','2025-11-11 14:00:00','2025-11-11 15:00:00',NULL,NULL,0,'2025-11-11 10:00:00','2025-11-11 15:00:00',NULL),(10,NULL,'DH-DEMO-010',2012,NULL,2,NULL,'2025-11-12','online',NULL,NULL,9001,0.00,0.00,'BANK_TRANSFER','completed',NULL,0.00,0,0.00,0,10500000.00,1500000.00,30000.00,9030000.00,9030000.00,0.00,'paid',1,NULL,'CTCP Gỗ Xanh','0912000012','45 Pasteur, Quận 1, HCM','Bến Nghé','Quận 1','Hồ Chí Minh',NULL,'2025-11-12 11:00:00','2025-11-12 12:00:00','2025-11-12 13:00:00','2025-11-12 14:00:00','2025-11-12 15:00:00',NULL,NULL,0,'2025-11-12 10:00:00','2025-11-12 15:00:00',NULL),(11,NULL,'DH-DEMO-011',2013,NULL,3,NULL,'2025-11-13','offline',NULL,NULL,9002,361000.00,0.00,'CASH','completed',NULL,0.00,0,0.00,0,9450000.00,2250000.00,20000.00,7581000.00,7581000.00,0.00,'paid',1,NULL,'Hộ KD Minh Quân','0912000013','22 Trần Phú, Nha Trang','Vạn Thạnh','Nha Trang','Khánh Hòa',NULL,'2025-11-13 11:00:00','2025-11-13 12:00:00','2025-11-13 13:00:00','2025-11-13 14:00:00','2025-11-13 15:00:00',NULL,NULL,0,'2025-11-13 10:00:00','2025-11-13 15:00:00',NULL),(12,NULL,'DH-DEMO-012',2014,NULL,4,NULL,'2025-11-14','online',NULL,NULL,9003,900000.00,0.00,'COD','completed',NULL,0.00,0,0.00,0,9000000.00,0.00,0.00,9900000.00,9900000.00,0.00,'paid',1,NULL,'Công ty Vận Tải Nhanh','0912000014','88 Kim Mã, Ba Đình, Hà Nội','Kim Mã','Ba Đình','Hà Nội',NULL,'2025-11-14 11:00:00','2025-11-14 12:00:00','2025-11-14 13:00:00','2025-11-14 14:00:00','2025-11-14 15:00:00',NULL,NULL,0,'2025-11-14 10:00:00','2025-11-14 15:00:00',NULL),(13,NULL,'DH-DEMO-013',2015,NULL,5,NULL,'2025-11-15','offline',NULL,NULL,9001,0.00,0.00,'BANK_TRANSFER','completed',NULL,0.00,0,0.00,0,10950000.00,1500000.00,15000.00,9465000.00,9465000.00,0.00,'paid',1,NULL,'CTY Thiết Kế Mộc','0912000015','12 Nguyễn Trãi, Quận 5, HCM','7','Quận 5','Hồ Chí Minh',NULL,'2025-11-15 11:00:00','2025-11-15 12:00:00','2025-11-15 13:00:00','2025-11-15 14:00:00','2025-11-15 15:00:00',NULL,NULL,0,'2025-11-15 10:00:00','2025-11-15 15:00:00',NULL),(14,NULL,'DH-DEMO-014',2016,NULL,1,NULL,'2025-11-16','offline',NULL,NULL,9002,195000.00,0.00,'CASH','completed',NULL,0.00,0,0.00,0,3900000.00,0.00,0.00,4095000.00,4095000.00,0.00,'paid',1,NULL,'Trịnh Quốc Thái','0912000016','14 Lê Duẩn, Hà Nội','Điện Biên','Ba Đình','Hà Nội',NULL,'2025-11-16 11:00:00','2025-11-16 12:00:00','2025-11-16 13:00:00','2025-11-16 14:00:00','2025-11-16 15:00:00',NULL,NULL,0,'2025-11-16 10:00:00','2025-11-16 15:00:00',NULL),(15,NULL,'DH-DEMO-015',2017,NULL,2,NULL,'2025-11-17','online',NULL,NULL,9003,902500.00,0.00,'EWALLET','completed',NULL,0.00,0,0.00,0,9000000.00,0.00,25000.00,9927500.00,9927500.00,0.00,'paid',1,NULL,'Đỗ Hồng Ngọc','0912000017','7 Nguyễn Văn Cừ, Hạ Long','Bạch Đằng','Hạ Long','Quảng Ninh',NULL,'2025-11-17 11:00:00','2025-11-17 12:00:00','2025-11-17 13:00:00','2025-11-17 14:00:00','2025-11-17 15:00:00',NULL,NULL,0,'2025-11-17 10:00:00','2025-11-17 15:00:00',NULL),(16,NULL,'DH-DEMO-016',2018,NULL,3,NULL,'2025-11-18','offline',NULL,NULL,9001,0.00,0.00,'CASH','completed',NULL,0.00,0,0.00,0,9450000.00,0.00,0.00,9450000.00,9450000.00,0.00,'paid',1,NULL,'La Mỹ Duyên','0912000018','155 Lạch Tray, Hải Phòng','Lạch Tray','Ngô Quyền','Hải Phòng',NULL,'2025-11-18 11:00:00','2025-11-18 12:00:00','2025-11-18 13:00:00','2025-11-18 14:00:00','2025-11-18 15:00:00',NULL,NULL,0,'2025-11-18 10:00:00','2025-11-18 15:00:00',NULL),(17,NULL,'DH-DEMO-017',2019,NULL,4,NULL,'2025-11-19','online',NULL,NULL,9002,152000.00,0.00,'BANK_TRANSFER','completed',NULL,0.00,0,0.00,0,3000000.00,0.00,40000.00,3192000.00,3192000.00,0.00,'paid',1,NULL,'Đinh Mạnh Cường','0912000019','18 Lê Lợi, Vinh','Hưng Bình','Vinh','Nghệ An',NULL,'2025-11-19 11:00:00','2025-11-19 12:00:00','2025-11-19 13:00:00','2025-11-19 14:00:00','2025-11-19 15:00:00',NULL,NULL,0,'2025-11-19 10:00:00','2025-11-19 15:00:00',NULL),(18,NULL,'DH-DEMO-018',2020,NULL,5,NULL,'2025-11-20','offline',NULL,NULL,9003,795000.00,0.00,'CASH','completed',NULL,0.00,0,0.00,0,9450000.00,1500000.00,0.00,8745000.00,8745000.00,0.00,'paid',1,NULL,'Phùng Thanh Mai','0912000020','3 Hùng Vương, Huế','Phú Nhuận','Huế','Thừa Thiên Huế',NULL,'2025-11-20 11:00:00','2025-11-20 12:00:00','2025-11-20 13:00:00','2025-11-20 14:00:00','2025-11-20 15:00:00',NULL,NULL,0,'2025-11-20 10:00:00','2025-11-20 15:00:00',NULL),(19,NULL,'DH-DEMO-019',2009,NULL,6,NULL,'2025-11-21','offline',NULL,NULL,9001,0.00,0.00,'CASH','cancelled',NULL,0.00,0,0.00,0,1950000.00,0.00,0.00,1950000.00,0.00,1950000.00,'unpaid',0,NULL,'Ngô Nhật Anh','0912000009','12 Nguyễn Văn Linh, Đà Nẵng','Nam Dương','Hải Châu','Đà Nẵng',NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-21 11:00:00','Khách đổi ý',0,'2025-11-21 10:00:00','2025-11-21 11:00:00',NULL),(20,NULL,'DH-DEMO-020',2010,NULL,2,NULL,'2025-11-22','online',NULL,NULL,9002,76000.00,0.00,'COD','cancelled',NULL,0.00,0,0.00,0,1500000.00,0.00,20000.00,1596000.00,239400.00,1356600.00,'partial',0,NULL,'Tạ Kim Yến','0912000010','99 Phan Chu Trinh, Đà Nẵng','Hải Châu 1','Hải Châu','Đà Nẵng',NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-22 11:00:00','Hết hàng',0,'2025-11-22 10:00:00','2025-11-22 11:00:00',NULL);
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
INSERT INTO `organizations` VALUES (1,'DEFAULT-ORG','Default Organization',NULL,NULL,NULL,NULL,NULL,'active',NULL,NULL,'2025-12-03 01:35:52','2025-12-03 01:35:52',NULL);
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_entries`
--

LOCK TABLES `payment_entries` WRITE;
/*!40000 ALTER TABLE `payment_entries` DISABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_methods`
--

LOCK TABLES `payment_methods` WRITE;
/*!40000 ALTER TABLE `payment_methods` DISABLE KEYS */;
INSERT INTO `payment_methods` VALUES (1,'CASH','Tiền mặt',NULL,'Thanh toán tiền mặt',1,1,'2025-12-03 01:35:37','2025-12-03 01:35:37',NULL),(2,'BANK_TRANSFER','Chuyển khoản',NULL,'Chuyển khoản ngân hàng',1,2,'2025-12-03 01:35:37','2025-12-03 01:35:37',NULL),(3,'CARD','Thẻ',NULL,'Thẻ tín dụng/ghi nợ',1,3,'2025-12-03 01:35:37','2025-12-03 01:35:37',NULL),(4,'COD','Thu hộ (COD)',NULL,'Thanh toán khi nhận hàng',1,4,'2025-12-03 01:35:37','2025-12-03 01:35:37',NULL),(5,'EWALLET','Ví điện tử',NULL,'MoMo/ZaloPay/VNPay',1,5,'2025-12-03 01:35:37','2025-12-03 01:35:37',NULL);
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
INSERT INTO `permissions` VALUES (1,'users.view','Xem người dùng',NULL,'users','admin','api','2025-12-03 02:28:54',NULL,NULL),(2,'users.manage','Quản lý người dùng',NULL,'users','admin','api','2025-12-03 02:28:54',NULL,NULL),(3,'products.view','Xem sản phẩm',NULL,'products','catalog','api','2025-12-03 02:28:54',NULL,NULL),(4,'products.manage','Quản lý sản phẩm',NULL,'products','catalog','api','2025-12-03 02:28:54',NULL,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `price_list_items`
--

LOCK TABLES `price_list_items` WRITE;
/*!40000 ALTER TABLE `price_list_items` DISABLE KEYS */;
INSERT INTO `price_list_items` VALUES (1,1,501,NULL,6000000.00,20.00,0.00,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(2,1,502,NULL,1560000.00,20.00,0.00,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(3,1,503,NULL,1200000.00,20.00,0.00,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(4,2,503,NULL,975000.00,35.00,0.00,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(5,3,501,7002,5250000.00,30.00,0.00,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL);
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
  `formula` text,
  `base_price_list_id` bigint unsigned DEFAULT NULL,
  `auto_update` tinyint(1) DEFAULT '0',
  `rounding_rule` varchar(50) DEFAULT 'none',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_price_lists_base_price_list_id` (`base_price_list_id`),
  CONSTRAINT `fk_price_lists_base_price_list_id` FOREIGN KEY (`base_price_list_id`) REFERENCES `price_lists` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `price_lists`
--

LOCK TABLES `price_lists` WRITE;
/*!40000 ALTER TABLE `price_lists` DISABLE KEYS */;
INSERT INTO `price_lists` VALUES (1,'Standard','base','Default price list',NULL,NULL,NULL,0,1,NULL,NULL,0,'none','2025-12-03 01:35:37','2025-12-03 01:35:37',NULL),(2,'Group B -35%','custom',NULL,'[3]','2025-11-28','2025-12-18',4,1,NULL,NULL,0,'none','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(3,'Flash Sale 30%','custom','Demo flash sale 30%',NULL,NULL,NULL,10,1,NULL,NULL,0,'none','2025-12-03 01:35:37','2025-12-03 01:35:37',NULL);
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
INSERT INTO `product_attribute_options` VALUES (301,201,'Đen','#000000',1,'active','2025-12-03 02:28:54',NULL,NULL),(302,201,'Nâu','#5b3a29',2,'active','2025-12-03 02:28:54',NULL,NULL),(303,202,'M',NULL,1,'active','2025-12-03 02:28:54',NULL,NULL),(304,202,'L',NULL,2,'active','2025-12-03 02:28:54',NULL,NULL),(305,201,'Xanh rêu','#556b2f',3,'active','2025-12-03 02:28:54',NULL,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=9627 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_attribute_values`
--

LOCK TABLES `product_attribute_values` WRITE;
/*!40000 ALTER TABLE `product_attribute_values` DISABLE KEYS */;
INSERT INTO `product_attribute_values` VALUES (9501,501,7001,201,301,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(9502,501,7001,202,303,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(9503,501,7002,201,302,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(9504,501,7002,202,304,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(9601,510,8001,201,301,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(9602,510,8001,202,303,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(9603,510,8002,201,301,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(9604,510,8002,202,304,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(9605,510,8003,201,302,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(9606,510,8003,202,303,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(9607,510,8004,201,302,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(9608,510,8004,202,304,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(9611,511,8011,201,301,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(9612,511,8011,202,303,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(9613,511,8012,201,301,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(9614,511,8012,202,304,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(9615,511,8013,201,302,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(9616,511,8013,202,303,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(9617,511,8014,201,302,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(9618,511,8014,202,304,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(9619,510,8005,201,305,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(9620,510,8005,202,303,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(9621,510,8006,201,305,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(9622,510,8006,202,304,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(9623,511,8015,201,305,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(9624,511,8015,202,303,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(9625,511,8016,201,305,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(9626,511,8016,202,304,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL);
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
INSERT INTO `product_attributes` VALUES (201,'Màu sắc','mau-sac','color','select',0,1,1,'active',1,'2025-12-03 02:28:54',NULL,NULL),(202,'Kích thước','size','size','select',0,1,2,'active',1,'2025-12-03 02:28:54',NULL,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=104 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_categories`
--

LOCK TABLES `product_categories` WRITE;
/*!40000 ALTER TABLE `product_categories` DISABLE KEYS */;
INSERT INTO `product_categories` VALUES (101,NULL,0,1,0,'TUI','Túi xách','tui-xach',NULL,NULL,1,'active','2025-12-03 02:28:54',NULL,NULL),(102,NULL,0,1,0,'VI','Ví','vi',NULL,NULL,2,'active','2025-12-03 02:28:54',NULL,NULL),(103,101,0,2,0,'TUI-DA','Túi da','tui-da',NULL,NULL,1,'active','2025-12-03 02:28:54',NULL,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_category_links`
--

LOCK TABLES `product_category_links` WRITE;
/*!40000 ALTER TABLE `product_category_links` DISABLE KEYS */;
INSERT INTO `product_category_links` VALUES (1,501,103,'2025-12-03 02:28:54'),(2,502,102,'2025-12-03 02:28:54'),(3,503,102,'2025-12-03 02:28:54'),(4,510,101,'2025-12-03 02:28:54'),(5,511,101,'2025-12-03 02:28:54');
/*!40000 ALTER TABLE `product_category_links` ENABLE KEYS */;
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
  CONSTRAINT `fk_product_images_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_product_images_variant_id` FOREIGN KEY (`variant_id`) REFERENCES `product_variants_v2` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9107 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_images`
--

LOCK TABLES `product_images` WRITE;
/*!40000 ALTER TABLE `product_images` DISABLE KEYS */;
INSERT INTO `product_images` VALUES (9001,501,7001,'/uploads/products/tdh016-m-den.jpg','https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/3fcf08b6f8b240fa99a661e56cdc7b3f.jpeg',1,0,NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54'),(9002,501,7002,'/uploads/products/tdh016-l-nau.jpg','https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/3fcf08b6f8b240fa99a661e56cdc7b3f.jpeg',1,0,NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54'),(9101,510,8001,'/uploads/products/tshirt-den-m.jpg','https://picsum.photos/seed/tshirt-den/500/500',1,0,NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54'),(9102,510,8003,'/uploads/products/tshirt-nau-m.jpg','https://picsum.photos/seed/tshirt-nau/500/500',1,0,NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54'),(9103,511,8011,'/uploads/products/balo-den-m.jpg','https://picsum.photos/seed/balo-den/500/500',1,0,NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54'),(9104,511,8013,'/uploads/products/balo-nau-m.jpg','https://picsum.photos/seed/balo-nau/500/500',1,0,NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54'),(9105,510,8005,'/uploads/products/tshirt-xanh-m.jpg','https://picsum.photos/seed/tshirt-xanh/500/500',0,1,NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54'),(9106,511,8015,'/uploads/products/balo-xanh-m.jpg','https://picsum.photos/seed/balo-xanh/500/500',0,1,NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54');
/*!40000 ALTER TABLE `product_images` ENABLE KEYS */;
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
  `price` decimal(10,2) DEFAULT '0.00',
  `cost_price` decimal(10,2) DEFAULT '0.00',
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
) ENGINE=InnoDB AUTO_INCREMENT=8017 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_variants_v2`
--

LOCK TABLES `product_variants_v2` WRITE;
/*!40000 ALTER TABLE `product_variants_v2` DISABLE KEYS */;
INSERT INTO `product_variants_v2` VALUES (7001,501,'TDH016 M Đen','TDH016-M-DEN','TDH016-M-BLK',NULL,7500000.00,1500000.00,5.00,0.00,100.00,NULL,NULL,'active','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(7002,501,'TDH016 L Nâu','TDH016-L-NAU','TDH016-L-BRN',NULL,7500000.00,1500000.00,3.00,0.00,100.00,NULL,NULL,'active','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(8001,510,'TSHIRT Đen M','TSHIRT-PERF-DEN-M','TSHIRT-DEN-M',NULL,450000.00,180000.00,15.00,5.00,200.00,NULL,NULL,'active','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(8002,510,'TSHIRT Đen L','TSHIRT-PERF-DEN-L','TSHIRT-DEN-L',NULL,450000.00,180000.00,12.00,5.00,200.00,NULL,NULL,'active','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(8003,510,'TSHIRT Nâu M','TSHIRT-PERF-NAU-M','TSHIRT-NAU-M',NULL,450000.00,180000.00,10.00,5.00,200.00,NULL,NULL,'active','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(8004,510,'TSHIRT Nâu L','TSHIRT-PERF-NAU-L','TSHIRT-NAU-L',NULL,450000.00,180000.00,9.00,5.00,200.00,NULL,NULL,'active','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(8005,510,'TSHIRT Xanh rêu M','TSHIRT-PERF-XR-M','TSHIRT-XR-M',NULL,450000.00,180000.00,11.00,5.00,200.00,NULL,NULL,'active','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(8006,510,'TSHIRT Xanh rêu L','TSHIRT-PERF-XR-L','TSHIRT-XR-L',NULL,450000.00,180000.00,8.00,5.00,200.00,NULL,NULL,'active','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(8011,511,'Balo Urban Đen M','BALO-URBAN-DEN-M','BALO-DEN-M',NULL,1250000.00,520000.00,18.00,4.00,120.00,NULL,NULL,'active','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(8012,511,'Balo Urban Đen L','BALO-URBAN-DEN-L','BALO-DEN-L',NULL,1320000.00,520000.00,14.00,4.00,120.00,NULL,NULL,'active','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(8013,511,'Balo Urban Nâu M','BALO-URBAN-NAU-M','BALO-NAU-M',NULL,1280000.00,520000.00,11.00,4.00,120.00,NULL,NULL,'active','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(8014,511,'Balo Urban Nâu L','BALO-URBAN-NAU-L','BALO-NAU-L',NULL,1350000.00,520000.00,9.00,4.00,120.00,NULL,NULL,'active','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(8015,511,'Balo Urban Xanh rêu M','BALO-URBAN-XR-M','BALO-XR-M',NULL,1280000.00,520000.00,10.00,4.00,120.00,NULL,NULL,'active','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(8016,511,'Balo Urban Xanh rêu L','BALO-URBAN-XR-L','BALO-XR-L',NULL,1360000.00,520000.00,8.00,4.00,120.00,NULL,NULL,'active','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL);
/*!40000 ALTER TABLE `product_variants_v2` ENABLE KEYS */;
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
  `unit` varchar(50) DEFAULT NULL,
  `has_variants` tinyint(1) DEFAULT '0',
  `image` varchar(255) DEFAULT NULL,
  `images` text,
  `weight` decimal(10,2) DEFAULT NULL,
  `dimensions` varchar(100) DEFAULT NULL,
  `description` text,
  `content` text,
  `is_active` tinyint(1) DEFAULT '1',
  `is_available_online` tinyint(1) DEFAULT '0',
  `is_featured` tinyint(1) DEFAULT '0',
  `status` enum('active','inactive') DEFAULT 'active',
  `selling_price` decimal(10,2) DEFAULT '0.00',
  `wholesale_price` decimal(10,2) DEFAULT '0.00',
  `purchase_price` decimal(10,2) DEFAULT '0.00',
  `stock_quantity` int DEFAULT '0',
  `alert_stock` int DEFAULT '0',
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(500) DEFAULT NULL,
  `meta_keywords` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_products_code_deleted_at` (`code`,`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=512 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (501,'goods','TDH016',NULL,'Túi đeo chéo da bò cao cấp khâu tay thủ công Lano TDH016','tui-deo-cheo-da-bo-cao-cap-khau-tay-thu-cong-lano-tdh016-tdh016',NULL,'Cái',1,'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/3fcf08b6f8b240fa99a661e56cdc7b3f.jpeg','[]',0.00,NULL,'Sản phẩm demo có biến thể màu/size',NULL,1,1,0,'active',7500000.00,NULL,1500000.00,8,10,NULL,NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(502,'goods','VDNTK035',NULL,'Ví da handmade khâu tay thủ công Lano VDNTK035','vi-da-handmade-khau-tay-thu-cong-lano-vdntk035-vdntk035',NULL,'Cái',0,'https://cdn2-retail-images.kiotviet.vn/2025/10/19/lano/9b9cf7569bd348fd9c73ab9c167a96b5.jpeg','[]',0.00,NULL,'Ví da demo 1',NULL,1,1,0,'active',1950000.00,NULL,650000.00,10,5,NULL,NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(503,'goods','VDNTK034',NULL,'Ví da epsom italia nhỏ gọn Lano VDNTK034','vi-da-epsom-italia-nho-gon-lano-vdntk034-vdntk034',NULL,'Cái',0,'https://cdn2-retail-images.kiotviet.vn/2025/10/15/lano/677874c0246b4f93ab1843fb4ddab0a8.jpeg','[]',0.00,NULL,'Ví da demo 2',NULL,1,1,0,'active',1500000.00,NULL,350000.00,6,5,NULL,NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(510,'goods','TSHIRT-PERF','TSHIRT-PERF-001','Áo thun Performance','ao-thun-performance-tshirt-perf','Lano','Cái',1,'https://picsum.photos/seed/tshirt-perf/600/600','[]',0.00,NULL,'Áo thun hiệu suất cao, thoáng khí, nhiều màu/size.',NULL,1,1,1,'active',450000.00,400000.00,180000.00,0,10,NULL,NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(511,'goods','BALO-URBAN','BALO-URBAN-001','Balo Urban đa năng','balo-urban-da-nang-balo-urban','Lano','Cái',1,'https://picsum.photos/seed/balo-urban/600/600','[]',0.00,NULL,'Balo city 17\\\" chống nước, nhiều màu + 2 kích cỡ.',NULL,1,1,1,'active',1250000.00,1100000.00,520000.00,0,8,NULL,NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL);
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
  PRIMARY KEY (`id`),
  KEY `idx_po_item_po` (`purchase_order_id`),
  KEY `fk_purchase_order_items_product_id` (`product_id`),
  CONSTRAINT `fk_purchase_order_items_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_purchase_order_items_purchase_order_id` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_order_items`
--

LOCK TABLES `purchase_order_items` WRITE;
/*!40000 ALTER TABLE `purchase_order_items` DISABLE KEYS */;
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
  `branch_id` bigint unsigned DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` varchar(50) DEFAULT 'draft',
  `received_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_purchase_orders_branch_id` (`branch_id`),
  CONSTRAINT `fk_purchase_orders_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_orders`
--

LOCK TABLES `purchase_orders` WRITE;
/*!40000 ALTER TABLE `purchase_orders` DISABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `return_items`
--

LOCK TABLES `return_items` WRITE;
/*!40000 ALTER TABLE `return_items` DISABLE KEYS */;
INSERT INTO `return_items` VALUES (1,1,17,1.000,'damaged','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(2,2,20,1.000,'used','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(3,3,23,1.000,'new','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(4,4,28,1.000,'new','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `returns`
--

LOCK TABLES `returns` WRITE;
/*!40000 ALTER TABLE `returns` DISABLE KEYS */;
INSERT INTO `returns` VALUES (1,'RET-DEMO-001',10,2012,1500000.00,0,1500000.00,'cash','defective',NULL,'approved',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(2,'RET-DEMO-002',11,2013,5250000.00,0,5250000.00,'bank_transfer','not_satisfied',NULL,'completed',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(3,'RET-DEMO-003',13,2015,1950000.00,0,0.00,NULL,'wrong_item',NULL,'pending',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(4,'RET-DEMO-004',15,2017,1500000.00,0,0.00,NULL,'other','Không phù hợp với nhu cầu','rejected',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL);
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
INSERT INTO `roles` VALUES (1,'super-admin','api','Super Admin',1,'2025-12-03 02:28:54',NULL,NULL),(2,'manager','api','Quản lý',0,'2025-12-03 02:28:54',NULL,NULL),(3,'viewer','api','Xem chỉ đọc',0,'2025-12-03 02:28:54',NULL,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_bins`
--

LOCK TABLES `stock_bins` WRITE;
/*!40000 ALTER TABLE `stock_bins` DISABLE KEYS */;
INSERT INTO `stock_bins` VALUES (1,501,7001,1,NULL,23.500,0.000,'2025-12-03 02:28:55'),(2,501,7002,1,NULL,15.000,0.000,'2025-12-03 02:28:54'),(3,502,NULL,1,NULL,37.000,0.000,'2025-12-03 02:28:55'),(4,503,NULL,1,NULL,34.500,0.000,'2025-12-03 02:28:55'),(5,501,7001,2,NULL,23.000,0.000,'2025-12-03 02:28:55'),(6,501,7002,2,NULL,15.000,0.000,'2025-12-03 02:28:54'),(7,502,NULL,2,NULL,39.000,0.000,'2025-12-03 02:28:55'),(8,503,NULL,2,NULL,32.000,0.000,'2025-12-03 02:28:55'),(9,501,7001,3,NULL,23.500,0.000,'2025-12-03 02:28:55'),(10,501,7002,3,NULL,15.000,0.000,'2025-12-03 02:28:55'),(11,502,NULL,3,NULL,37.500,0.000,'2025-12-03 02:28:55'),(12,503,NULL,3,NULL,35.000,0.000,'2025-12-03 02:28:54'),(13,501,7001,4,NULL,24.000,0.000,'2025-12-03 02:28:55'),(14,501,7002,4,NULL,15.000,0.000,'2025-12-03 02:28:54'),(15,502,NULL,4,NULL,40.000,0.000,'2025-12-03 02:28:54'),(16,503,NULL,4,NULL,32.000,0.000,'2025-12-03 02:28:55'),(17,501,7001,5,NULL,23.000,0.000,'2025-12-03 02:28:55'),(18,501,7002,5,NULL,15.000,0.000,'2025-12-03 02:28:55'),(19,502,NULL,5,NULL,38.000,0.000,'2025-12-03 02:28:55'),(20,503,NULL,5,NULL,34.000,0.000,'2025-12-03 02:28:55');
/*!40000 ALTER TABLE `stock_bins` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_ledgers`
--

LOCK TABLES `stock_ledgers` WRITE;
/*!40000 ALTER TABLE `stock_ledgers` DISABLE KEYS */;
INSERT INTO `stock_ledgers` VALUES (1,501,7001,1,1,NULL,NULL,'2025-11-28 02:28:54','demo_opening',1001,1,25.000,1500000.0000,37500000.0000,'2025-12-03 02:28:54'),(2,501,7002,1,1,NULL,NULL,'2025-11-28 02:28:54','demo_opening',1002,1,15.000,1500000.0000,22500000.0000,'2025-12-03 02:28:54'),(3,502,NULL,1,1,NULL,NULL,'2025-11-28 02:28:54','demo_opening',1003,1,40.000,650000.0000,26000000.0000,'2025-12-03 02:28:54'),(4,503,NULL,1,1,NULL,NULL,'2025-11-28 02:28:54','demo_opening',1004,1,35.000,350000.0000,12250000.0000,'2025-12-03 02:28:54'),(5,501,7001,2,3,NULL,NULL,'2025-11-28 02:28:54','demo_opening',2001,1,25.000,1500000.0000,37500000.0000,'2025-12-03 02:28:54'),(6,501,7002,2,3,NULL,NULL,'2025-11-28 02:28:54','demo_opening',2002,1,15.000,1500000.0000,22500000.0000,'2025-12-03 02:28:54'),(7,502,NULL,2,3,NULL,NULL,'2025-11-28 02:28:54','demo_opening',2003,1,40.000,650000.0000,26000000.0000,'2025-12-03 02:28:54'),(8,503,NULL,2,3,NULL,NULL,'2025-11-28 02:28:54','demo_opening',2004,1,35.000,350000.0000,12250000.0000,'2025-12-03 02:28:54'),(9,501,7001,3,5,NULL,NULL,'2025-11-28 02:28:54','demo_opening',3001,1,25.000,1500000.0000,37500000.0000,'2025-12-03 02:28:54'),(10,501,7002,3,5,NULL,NULL,'2025-11-28 02:28:54','demo_opening',3002,1,15.000,1500000.0000,22500000.0000,'2025-12-03 02:28:54'),(11,502,NULL,3,5,NULL,NULL,'2025-11-28 02:28:54','demo_opening',3003,1,40.000,650000.0000,26000000.0000,'2025-12-03 02:28:54'),(12,503,NULL,3,5,NULL,NULL,'2025-11-28 02:28:54','demo_opening',3004,1,35.000,350000.0000,12250000.0000,'2025-12-03 02:28:54'),(13,501,7001,4,6,NULL,NULL,'2025-11-28 02:28:54','demo_opening',4001,1,25.000,1500000.0000,37500000.0000,'2025-12-03 02:28:54'),(14,501,7002,4,6,NULL,NULL,'2025-11-28 02:28:54','demo_opening',4002,1,15.000,1500000.0000,22500000.0000,'2025-12-03 02:28:54'),(15,502,NULL,4,6,NULL,NULL,'2025-11-28 02:28:54','demo_opening',4003,1,40.000,650000.0000,26000000.0000,'2025-12-03 02:28:54'),(16,503,NULL,4,6,NULL,NULL,'2025-11-28 02:28:54','demo_opening',4004,1,35.000,350000.0000,12250000.0000,'2025-12-03 02:28:54'),(17,501,7001,5,7,NULL,NULL,'2025-11-28 02:28:54','demo_opening',5001,1,25.000,1500000.0000,37500000.0000,'2025-12-03 02:28:54'),(18,501,7002,5,7,NULL,NULL,'2025-11-28 02:28:55','demo_opening',5002,1,15.000,1500000.0000,22500000.0000,'2025-12-03 02:28:55'),(19,502,NULL,5,7,NULL,NULL,'2025-11-28 02:28:55','demo_opening',5003,1,40.000,650000.0000,26000000.0000,'2025-12-03 02:28:55'),(20,503,NULL,5,7,NULL,NULL,'2025-11-28 02:28:55','demo_opening',5004,1,35.000,350000.0000,12250000.0000,'2025-12-03 02:28:55'),(21,501,7001,1,1,NULL,NULL,'2025-11-09 12:00:00','demo_delivery',6,1,-0.500,1500000.0000,-750000.0000,'2025-12-03 02:28:55'),(22,503,NULL,1,1,NULL,NULL,'2025-11-09 12:00:00','demo_delivery',6,2,-0.500,350000.0000,-175000.0000,'2025-12-03 02:28:55'),(23,502,NULL,2,3,NULL,NULL,'2025-11-10 12:00:00','demo_delivery',7,1,-1.000,650000.0000,-650000.0000,'2025-12-03 02:28:55'),(24,503,NULL,2,3,NULL,NULL,'2025-11-10 12:00:00','demo_delivery',7,2,-1.000,350000.0000,-350000.0000,'2025-12-03 02:28:55'),(25,501,7001,3,5,NULL,NULL,'2025-11-11 12:00:00','demo_delivery',8,1,-0.500,1500000.0000,-750000.0000,'2025-12-03 02:28:55'),(26,502,NULL,3,5,NULL,NULL,'2025-11-11 12:00:00','demo_delivery',8,2,-0.500,650000.0000,-325000.0000,'2025-12-03 02:28:55'),(27,501,7001,1,1,NULL,NULL,'2025-11-12 12:00:00','demo_delivery',9,1,-1.000,1500000.0000,-1500000.0000,'2025-12-03 02:28:55'),(28,502,NULL,1,1,NULL,NULL,'2025-11-12 12:00:00','demo_delivery',9,2,-1.000,650000.0000,-650000.0000,'2025-12-03 02:28:55'),(29,503,NULL,2,3,NULL,NULL,'2025-11-13 12:00:00','demo_delivery',10,1,-2.000,350000.0000,-700000.0000,'2025-12-03 02:28:55'),(30,501,7001,2,3,NULL,NULL,'2025-11-13 12:00:00','demo_delivery',10,2,-1.000,1500000.0000,-1500000.0000,'2025-12-03 02:28:55'),(31,502,NULL,3,5,NULL,NULL,'2025-11-14 12:00:00','demo_delivery',11,1,-1.000,650000.0000,-650000.0000,'2025-12-03 02:28:55'),(32,501,7002,3,5,NULL,NULL,'2025-11-14 12:00:00','demo_delivery',11,2,-1.000,1500000.0000,-1500000.0000,'2025-12-03 02:28:55'),(33,503,NULL,4,6,NULL,NULL,'2025-11-15 12:00:00','demo_delivery',12,1,-1.000,350000.0000,-350000.0000,'2025-12-03 02:28:55'),(34,501,7001,4,6,NULL,NULL,'2025-11-15 12:00:00','demo_delivery',12,2,-1.000,1500000.0000,-1500000.0000,'2025-12-03 02:28:55'),(35,502,NULL,5,7,NULL,NULL,'2025-11-16 12:00:00','demo_delivery',13,1,-1.000,650000.0000,-650000.0000,'2025-12-03 02:28:55'),(36,503,NULL,5,7,NULL,NULL,'2025-11-16 12:00:00','demo_delivery',13,2,-1.000,350000.0000,-350000.0000,'2025-12-03 02:28:55'),(37,501,7001,5,7,NULL,NULL,'2025-11-16 12:00:00','demo_delivery',13,3,-1.000,1500000.0000,-1500000.0000,'2025-12-03 02:28:55'),(38,502,NULL,1,1,NULL,NULL,'2025-11-17 12:00:00','demo_delivery',14,1,-2.000,650000.0000,-1300000.0000,'2025-12-03 02:28:55'),(39,501,7001,2,3,NULL,NULL,'2025-11-18 12:00:00','demo_delivery',15,1,-1.000,1500000.0000,-1500000.0000,'2025-12-03 02:28:55'),(40,503,NULL,2,3,NULL,NULL,'2025-11-18 12:00:00','demo_delivery',15,2,-1.000,350000.0000,-350000.0000,'2025-12-03 02:28:55'),(41,502,NULL,3,5,NULL,NULL,'2025-11-19 12:00:00','demo_delivery',16,1,-1.000,650000.0000,-650000.0000,'2025-12-03 02:28:55'),(42,501,7001,3,5,NULL,NULL,'2025-11-19 12:00:00','demo_delivery',16,2,-1.000,1500000.0000,-1500000.0000,'2025-12-03 02:28:55'),(43,503,NULL,4,6,NULL,NULL,'2025-11-20 12:00:00','demo_delivery',17,1,-2.000,350000.0000,-700000.0000,'2025-12-03 02:28:55'),(44,501,7001,5,7,NULL,NULL,'2025-11-21 12:00:00','demo_delivery',18,1,-1.000,1500000.0000,-1500000.0000,'2025-12-03 02:28:55'),(45,502,NULL,5,7,NULL,NULL,'2025-11-21 12:00:00','demo_delivery',18,2,-1.000,650000.0000,-650000.0000,'2025-12-03 02:28:55'),(46,503,NULL,2,3,NULL,NULL,'2025-12-03 02:28:55','demo_return',1,1,1.000,350000.0000,350000.0000,'2025-12-03 02:28:55'),(47,501,7002,3,5,NULL,NULL,'2025-12-03 02:28:55','demo_return',2,1,1.000,1500000.0000,1500000.0000,'2025-12-03 02:28:55');
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_reconciliation_items`
--

LOCK TABLES `stock_reconciliation_items` WRITE;
/*!40000 ALTER TABLE `stock_reconciliation_items` DISABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_reconciliations`
--

LOCK TABLES `stock_reconciliations` WRITE;
/*!40000 ALTER TABLE `stock_reconciliations` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_reconciliations` ENABLE KEYS */;
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
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
INSERT INTO `suppliers` VALUES (1,'DEFAULT-SUP','Default Supplier',NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,NULL,'2025-12-03 01:35:52','2025-12-03 01:35:52',NULL),(2,'SUP-002','Nhà cung cấp B',NULL,'0202020202',NULL,NULL,NULL,NULL,'active',NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(3,'SUP-003','Công ty CP Phụ Kiện Thời Trang','Fashion Accessories JSC','0111222333','028-3666-7777','info@phukien.com.vn','789 Nguyễn Huệ, HCM',NULL,'active',NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(4,'SUP-004','Nhà Máy Dệt May Tân Tiến','Tan Tien Textile Factory','0444555666','0236-3555-6666','sales@tantien.vn','321 Lê Duẩn, Đà Nẵng',NULL,'active',NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(5,'SUP-005','Xưởng Gia Công Đồng Phát','Dong Phat Workshop','0777888999','0292-3444-5555','dongphat@workshop.vn','654 Đường 3/2, Cần Thơ',NULL,'active',NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(6,'SUP-006','Công ty TNHH Vải Cao Cấp','Premium Fabric Co., Ltd','0222333444','024-3333-4444','premium@fabric.vn','987 Trần Hưng Đạo, Hà Nội',NULL,'active',NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(7,'SUP-007','Nhà Cung Cấp Phụ Liệu Minh Anh','Minh Anh Materials','0555666777','028-3222-3333','minhanh@materials.vn','147 Lê Lợi, HCM',NULL,'active',NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(8,'SUP-008','Xưởng Thêu Ren Hoa Mai','Hoa Mai Embroidery','0888999000','0225-3111-2222','hoamai@embroidery.vn','258 Lạch Tray, Hải Phòng',NULL,'active',NULL,NULL,'2025-12-03 02:28:54','2025-12-03 02:28:54',NULL);
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
  PRIMARY KEY (`id`),
  KEY `idx_task_project` (`project_id`),
  KEY `idx_task_parent` (`parent_id`),
  KEY `idx_task_status` (`status`),
  CONSTRAINT `fk_tasks_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tasks_project_id` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasks`
--

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
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
INSERT INTO `tax_templates` VALUES (9001,'VAT 0%',0.000,0,'nearest','active','2025-12-03 02:28:54','2025-12-03 02:28:54'),(9002,'VAT 5%',5.000,0,'nearest','active','2025-12-03 02:28:54','2025-12-03 02:28:54'),(9003,'VAT 10%',10.000,0,'nearest','active','2025-12-03 02:28:54','2025-12-03 02:28:54');
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
INSERT INTO `users` VALUES (1,'admin.staging','admin@staging.lanocrm.local','$2y$12$7SI9yb1rxz2lzuKynLZLBekiXP8uYoeW6hO4TQWsgKWV.3nEW1o9G','Staging Admin',NULL,NULL,1,'active',NULL,NULL,NULL,NULL,0,NULL,0,NULL,'Asia/Ho_Chi_Minh','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(2,'manager.staging','manager@staging.lanocrm.local','$2y$12$pRDQCai3nUuqKTP3GzRxpuLXm52X56HoEyUv/q4ji5rwQuZDHfxC2','Staging Manager',NULL,NULL,1,'active',NULL,NULL,NULL,NULL,0,NULL,0,NULL,'Asia/Ho_Chi_Minh','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(3,'staff.staging','staff@staging.lanocrm.local','$2y$12$3Y1HoAuurcIwSCt7ZYeCCuCs1Hkkd1sKaoFkjJd4z/LhkfKC2ifmu','Staging Staff',NULL,NULL,1,'active',NULL,NULL,NULL,NULL,0,NULL,0,NULL,'Asia/Ho_Chi_Minh','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(10,'demo.admin','demo.admin@lanocrm.local','$2y$12$7SI9yb1rxz2lzuKynLZLBekiXP8uYoeW6hO4TQWsgKWV.3nEW1o9G','Demo Admin User',NULL,NULL,1,'active',NULL,NULL,NULL,NULL,0,NULL,0,NULL,'Asia/Ho_Chi_Minh','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(11,'demo.manager.hn','manager.hn@lanocrm.local','$2y$12$pRDQCai3nUuqKTP3GzRxpuLXm52X56HoEyUv/q4ji5rwQuZDHfxC2','Demo Manager Hanoi',NULL,NULL,1,'active',NULL,NULL,NULL,NULL,0,NULL,0,NULL,'Asia/Ho_Chi_Minh','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(12,'demo.manager.hcm','manager.hcm@lanocrm.local','$2y$12$pRDQCai3nUuqKTP3GzRxpuLXm52X56HoEyUv/q4ji5rwQuZDHfxC2','Demo Manager HCM',NULL,NULL,2,'active',NULL,NULL,NULL,NULL,0,NULL,0,NULL,'Asia/Ho_Chi_Minh','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(13,'demo.staff1','staff1@lanocrm.local','$2y$12$3Y1HoAuurcIwSCt7ZYeCCuCs1Hkkd1sKaoFkjJd4z/LhkfKC2ifmu','Demo Staff 1',NULL,NULL,1,'active',NULL,NULL,NULL,NULL,0,NULL,0,NULL,'Asia/Ho_Chi_Minh','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(14,'demo.staff2','staff2@lanocrm.local','$2y$12$3Y1HoAuurcIwSCt7ZYeCCuCs1Hkkd1sKaoFkjJd4z/LhkfKC2ifmu','Demo Staff 2',NULL,NULL,2,'active',NULL,NULL,NULL,NULL,0,NULL,0,NULL,'Asia/Ho_Chi_Minh','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(15,'demo.inactive','inactive@lanocrm.local','$2y$12$3Y1HoAuurcIwSCt7ZYeCCuCs1Hkkd1sKaoFkjJd4z/LhkfKC2ifmu','Demo Inactive User',NULL,NULL,1,'inactive',NULL,NULL,NULL,NULL,0,NULL,0,NULL,'Asia/Ho_Chi_Minh','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
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
INSERT INTO `warehouses` VALUES (1,'Kho chính Hà Nội','WH-HN-MAIN',1,'active','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(2,'Kho bán lẻ Hà Nội','WH-HN-RETAIL',1,'active','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(3,'Kho chính HCM','WH-HCM-MAIN',2,'active','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(4,'Kho bán lẻ HCM','WH-HCM-RETAIL',2,'active','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(5,'Kho chính Đà Nẵng','WH-DN-MAIN',3,'active','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(6,'Kho chính Cần Thơ','WH-CT-MAIN',4,'active','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL),(7,'Kho chính Hải Phòng','WH-HP-MAIN',5,'active','2025-12-03 02:28:54','2025-12-03 02:28:54',NULL);
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

--
-- Dumping routines for database 'lanocrm_test'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-03  2:30:08
