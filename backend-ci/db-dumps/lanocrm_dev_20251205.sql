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
  KEY `idx_module_model` (`module`,`model_type`,`model_id`)
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
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `check_in` time DEFAULT NULL,
  `check_out` time DEFAULT NULL,
  `status` varchar(20) DEFAULT 'present',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance`
--

LOCK TABLES `attendance` WRITE;
/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
INSERT INTO `attendance` VALUES (1,1,'2025-12-05','08:00:00','17:00:00','present','2025-12-05 18:19:13','2025-12-05 18:19:13'),(2,2,'2025-12-05','08:00:00','17:00:00','present','2025-12-05 18:19:13','2025-12-05 18:19:13'),(3,3,'2025-12-05','08:00:00','17:00:00','present','2025-12-05 18:19:13','2025-12-05 18:19:13'),(4,1,'2025-12-04','08:00:00','17:00:00','present','2025-12-05 18:19:13','2025-12-05 18:19:13'),(5,2,'2025-12-04','08:00:00','17:00:00','present','2025-12-05 18:19:13','2025-12-05 18:19:13'),(6,3,'2025-12-04','08:00:00','17:00:00','present','2025-12-05 18:19:13','2025-12-05 18:19:13'),(7,1,'2025-12-03','08:00:00','17:00:00','present','2025-12-05 18:19:13','2025-12-05 18:19:13'),(8,2,'2025-12-03','08:00:00','17:00:00','present','2025-12-05 18:19:13','2025-12-05 18:19:13'),(9,3,'2025-12-03','08:00:00','17:00:00','present','2025-12-05 18:19:13','2025-12-05 18:19:13'),(10,1,'2025-12-02','08:00:00','17:00:00','present','2025-12-05 18:19:13','2025-12-05 18:19:13'),(11,2,'2025-12-02','08:00:00','17:00:00','present','2025-12-05 18:19:13','2025-12-05 18:19:13'),(12,3,'2025-12-02','08:00:00','17:00:00','present','2025-12-05 18:19:13','2025-12-05 18:19:13'),(13,1,'2025-12-01','08:00:00','17:00:00','present','2025-12-05 18:19:13','2025-12-05 18:19:13'),(14,2,'2025-12-01','08:00:00','17:00:00','present','2025-12-05 18:19:13','2025-12-05 18:19:13'),(15,3,'2025-12-01','08:00:00','17:00:00','present','2025-12-05 18:19:13','2025-12-05 18:19:13');
/*!40000 ALTER TABLE `attendance` ENABLE KEYS */;
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
-- Table structure for table `bom`
--

DROP TABLE IF EXISTS `bom`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bom` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `version` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `is_default` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bom`
--

LOCK TABLES `bom` WRITE;
/*!40000 ALTER TABLE `bom` DISABLE KEYS */;
INSERT INTO `bom` VALUES (1,501,'BOM for Combo Set','1.0',1,1,'2025-12-05 18:19:14','2025-12-05 18:19:14');
/*!40000 ALTER TABLE `bom` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
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
INSERT INTO `branches` VALUES (1,'Chi nhánh Hà Nội','HN01','active','2025-12-05 18:19:13',NULL,NULL),(2,'Chi nhánh HCM','HCM01','active','2025-12-05 18:19:13',NULL,NULL),(3,'Chi nhánh Đà Nẵng','DN01','active','2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(4,'Chi nhánh Cần Thơ','CT01','active','2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(5,'Chi nhánh Hải Phòng','HP01','active','2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(6,'Chi nhánh Test (Inactive)','TEST01','inactive','2025-12-05 18:19:13','2025-12-05 18:19:13',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_transactions`
--

LOCK TABLES `cash_transactions` WRITE;
/*!40000 ALTER TABLE `cash_transactions` DISABLE KEYS */;
INSERT INTO `cash_transactions` VALUES (1,'RECEIPT',446000.00,'sales','CASH','approved','Quỹ demo','Thu đơn DH-DEMO-001 (unpaid)','order',1021,'DH-DEMO-001',1,1,'Demo Admin','demo-staff','CUST-2001','Nguyễn Minh An','0912000001','12 Trần Hưng Đạo, Hà Nội',NULL,NULL,'2025-11-05','Thanh toán đủ','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(2,'RECEIPT',474600.00,'sales','BANK_TRANSFER','approved','Quỹ demo','Thu đơn DH-DEMO-002 (partial)','order',1022,'DH-DEMO-002',2,1,'Demo Admin','demo-staff','CUST-2002','Trần Thu Hà','0912000002','89 Lý Thường Kiệt, Hà Nội',NULL,NULL,'2025-11-06','Thanh toán đủ','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(3,'RECEIPT',451660.00,'sales','COD','approved','Quỹ demo','Thu đơn DH-DEMO-003 (partial)','order',1023,'DH-DEMO-003',3,1,'Demo Admin','demo-staff','CUST-2003','Phạm Gia Bảo','0912000003','22 Nguyễn Huệ, HCM',NULL,NULL,'2025-11-07','Thanh toán đủ','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(4,'RECEIPT',408000.00,'sales','CASH','approved','Quỹ demo','Thu đơn DH-DEMO-004 (partial)','order',1024,'DH-DEMO-004',4,1,'Demo Admin','demo-staff','CUST-2004','Lê Hồng Nhung','0912000004','35 Hai Bà Trưng, HCM',NULL,NULL,'2025-11-08','Thanh toán đủ','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(5,'RECEIPT',424620.00,'sales','EWALLET','approved','Quỹ demo','Thu đơn DH-DEMO-005 (partial)','order',1025,'DH-DEMO-005',5,1,'Demo Admin','demo-staff','CUST-2005','Vũ Hoàng Long','0912000005','15 Nguyễn Tri Phương, Đà Nẵng',NULL,NULL,'2025-11-09','Thanh toán đủ','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(6,'RECEIPT',487300.00,'sales','COD','approved','Quỹ demo','Thu đơn DH-DEMO-006 (partial)','order',1026,'DH-DEMO-006',1,1,'Demo Admin','demo-staff','CUST-2006','Đặng Bích Trâm','0912000006','101 Võ Văn Tần, HCM',NULL,NULL,'2025-11-10','Thanh toán đủ','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(7,'RECEIPT',430000.00,'sales','BANK_TRANSFER','approved','Quỹ demo','Thu đơn DH-DEMO-007 (partial)','order',1027,'DH-DEMO-007',2,1,'Demo Admin','demo-staff','CUST-2007','Huỳnh Tuấn Kiệt','0912000007','45 Trần Phú, Nha Trang',NULL,NULL,'2025-11-11','Thanh toán đủ','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(8,'RECEIPT',392280.00,'sales','CASH','approved','Quỹ demo','Thu đơn DH-DEMO-008 (partial)','order',1028,'DH-DEMO-008',3,1,'Demo Admin','demo-staff','CUST-2008','Lý Thu Uyên','0912000008','68 Lê Lợi, Huế',NULL,NULL,'2025-11-12','Thanh toán đủ','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(9,'RECEIPT',457600.00,'sales','BANK_TRANSFER','approved','Quỹ demo','Thu đơn DH-DEMO-009 (paid)','order',1029,'DH-DEMO-009',1,1,'Demo Admin','demo-staff','CUST-2011','Công ty Ánh Dương','0912000011','11 Duy Tân, Cầu Giấy, Hà Nội',NULL,NULL,'2025-11-13','Thanh toán đủ','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(10,'RECEIPT',611600.00,'sales','BANK_TRANSFER','approved','Quỹ demo','Thu đơn DH-DEMO-010 (paid)','order',1030,'DH-DEMO-010',2,1,'Demo Admin','demo-staff','CUST-2012','CTCP Gỗ Xanh','0912000012','45 Pasteur, Quận 1, HCM',NULL,NULL,'2025-11-14','Thanh toán đủ','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(11,'RECEIPT',398370.00,'sales','CASH','approved','Quỹ demo','Thu đơn DH-DEMO-011 (paid)','order',1031,'DH-DEMO-011',3,1,'Demo Admin','demo-staff','CUST-2013','Hộ KD Minh Quân','0912000013','22 Trần Phú, Nha Trang',NULL,NULL,'2025-11-15','Thanh toán đủ','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(12,'RECEIPT',459800.00,'sales','COD','approved','Quỹ demo','Thu đơn DH-DEMO-012 (paid)','order',1032,'DH-DEMO-012',4,1,'Demo Admin','demo-staff','CUST-2014','Công ty Vận Tải Nhanh','0912000014','88 Kim Mã, Ba Đình, Hà Nội',NULL,NULL,'2025-11-16','Thanh toán đủ','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(13,'RECEIPT',594600.00,'sales','BANK_TRANSFER','approved','Quỹ demo','Thu đơn DH-DEMO-013 (paid)','order',1033,'DH-DEMO-013',5,1,'Demo Admin','demo-staff','CUST-2015','CTY Thiết Kế Mộc','0912000015','12 Nguyễn Trãi, Quận 5, HCM',NULL,NULL,'2025-11-17','Thanh toán đủ','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(14,'RECEIPT',428400.00,'sales','CASH','approved','Quỹ demo','Thu đơn DH-DEMO-014 (paid)','order',1034,'DH-DEMO-014',1,1,'Demo Admin','demo-staff','CUST-2016','Trịnh Quốc Thái','0912000016','14 Lê Duẩn, Hà Nội',NULL,NULL,'2025-11-18','Thanh toán đủ','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(15,'RECEIPT',487300.00,'sales','EWALLET','approved','Quỹ demo','Thu đơn DH-DEMO-015 (paid)','order',1035,'DH-DEMO-015',2,1,'Demo Admin','demo-staff','CUST-2017','Đỗ Hồng Ngọc','0912000017','7 Nguyễn Văn Cừ, Hạ Long',NULL,NULL,'2025-11-19','Thanh toán đủ','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(16,'RECEIPT',416000.00,'sales','CASH','approved','Quỹ demo','Thu đơn DH-DEMO-016 (paid)','order',1036,'DH-DEMO-016',3,1,'Demo Admin','demo-staff','CUST-2018','La Mỹ Duyên','0912000018','155 Lạch Tray, Hải Phòng',NULL,NULL,'2025-11-20','Thanh toán đủ','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(17,'RECEIPT',474600.00,'sales','BANK_TRANSFER','approved','Quỹ demo','Thu đơn DH-DEMO-017 (paid)','order',1037,'DH-DEMO-017',4,1,'Demo Admin','demo-staff','CUST-2019','Đinh Mạnh Cường','0912000019','18 Lê Lợi, Vinh',NULL,NULL,'2025-11-21','Thanh toán đủ','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(18,'RECEIPT',410960.00,'sales','CASH','approved','Quỹ demo','Thu đơn DH-DEMO-018 (paid)','order',1038,'DH-DEMO-018',5,1,'Demo Admin','demo-staff','CUST-2020','Phùng Thanh Mai','0912000020','3 Hùng Vương, Huế',NULL,NULL,'2025-11-22','Thanh toán đủ','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(19,'RECEIPT',785400.00,'sales','EWALLET','approved','Quỹ demo','Thu đơn DH-DEMO-021 (partial)','order',1041,'DH-DEMO-021',1,1,'Demo Admin','demo-staff','CUST-2003','Phạm Gia Bảo','0912000003','22 Nguyễn Huệ, HCM',NULL,NULL,'2025-11-25','Thanh toán đủ','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(20,'RECEIPT',536000.00,'sales','BANK_TRANSFER','approved','Quỹ demo','Thu đơn DH-DEMO-022 (unpaid)','order',1042,'DH-DEMO-022',2,1,'Demo Admin','demo-staff','CUST-2005','Vũ Hoàng Long','0912000005','15 Nguyễn Tri Phương, Đà Nẵng',NULL,NULL,'2025-11-26','Thanh toán đủ','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(21,'RECEIPT',1940000.00,'sales','EWALLET','approved','Quỹ demo','Thu đơn DH-DEMO-025 (unpaid)','order',1045,'DH-DEMO-025',5,1,'Demo Admin','demo-staff','CUST-2020','Phùng Thanh Mai','0912000020','3 Hùng Vương, Huế',NULL,NULL,'2025-11-29','Thanh toán đủ','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(22,'RECEIPT',561000.00,'sales','EWALLET','approved','Quỹ demo','Thu đơn DH-DEMO-027 (partial)','order',1047,'DH-DEMO-027',5,1,'Demo Admin','demo-staff','CUST-2014','Công ty Vận Tải Nhanh','0912000014','88 Kim Mã, Ba Đình, Hà Nội',NULL,NULL,'2025-12-01','Thanh toán đủ','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(23,'RECEIPT',1060000.00,'sales','CASH','approved','Quỹ demo','Thu đơn DH-DEMO-028 (partial)','order',1048,'DH-DEMO-028',2,1,'Demo Admin','demo-staff','CUST-2011','Công ty Ánh Dương','0912000011','11 Duy Tân, Cầu Giấy, Hà Nội',NULL,NULL,'2025-12-02','Thanh toán đủ','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(24,'RECEIPT',1467900.00,'sales','COD','approved','Quỹ demo','Thu đơn DH-DEMO-029 (paid)','order',1049,'DH-DEMO-029',1,1,'Demo Admin','demo-staff','CUST-2015','CTY Thiết Kế Mộc','0912000015','12 Nguyễn Trãi, Quận 5, HCM',NULL,NULL,'2025-12-03','Thanh toán đủ','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(25,'RECEIPT',1206000.00,'sales','CASH','approved','Quỹ demo','Thu đơn DH-DEMO-031 (paid)','order',1051,'DH-DEMO-031',5,1,'Demo Admin','demo-staff','CUST-2012','CTCP Gỗ Xanh','0912000012','45 Pasteur, Quận 1, HCM',NULL,NULL,'2025-12-05','Thanh toán đủ','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(26,'RECEIPT',764400.00,'sales','EWALLET','approved','Quỹ demo','Thu đơn DH-DEMO-032 (paid)','order',1052,'DH-DEMO-032',5,1,'Demo Admin','demo-staff','CUST-2013','Hộ KD Minh Quân','0912000013','22 Trần Phú, Nha Trang',NULL,NULL,'2025-12-06','Thanh toán đủ','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(27,'RECEIPT',1903000.00,'sales','EWALLET','approved','Quỹ demo','Thu đơn DH-DEMO-033 (paid)','order',1053,'DH-DEMO-033',1,1,'Demo Admin','demo-staff','CUST-2014','Công ty Vận Tải Nhanh','0912000014','88 Kim Mã, Ba Đình, Hà Nội',NULL,NULL,'2025-12-07','Thanh toán đủ','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(28,'RECEIPT',2050000.00,'sales','CASH','approved','Quỹ demo','Thu đơn DH-DEMO-034 (paid)','order',1054,'DH-DEMO-034',4,1,'Demo Admin','demo-staff','CUST-2011','Công ty Ánh Dương','0912000011','11 Duy Tân, Cầu Giấy, Hà Nội',NULL,NULL,'2025-12-08','Thanh toán đủ','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(29,'RECEIPT',1824900.00,'sales','BANK_TRANSFER','approved','Quỹ demo','Thu đơn DH-DEMO-035 (paid)','order',1055,'DH-DEMO-035',4,1,'Demo Admin','demo-staff','CUST-2013','Hộ KD Minh Quân','0912000013','22 Trần Phú, Nha Trang',NULL,NULL,'2025-12-09','Thanh toán đủ','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(30,'PAYMENT',206000.00,'refund','CASH','approved','Quỹ demo','Hoàn tiền trả hàng RET-DEMO-001','return_order',6,'RET-DEMO-001',2,2,'Demo Manager','demo-staff','CUST-2012','CTCP Gỗ Xanh','0912000012','45 Pasteur, Quận 1, HCM',NULL,NULL,'2025-12-05','Hoàn tiền theo phiếu trả hàng','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(31,'PAYMENT',155400.00,'refund','BANK_TRANSFER','approved','Quỹ demo','Hoàn tiền trả hàng RET-DEMO-002','return_order',7,'RET-DEMO-002',3,2,'Demo Manager','demo-staff','CUST-2013','Hộ KD Minh Quân','0912000013','22 Trần Phú, Nha Trang',NULL,NULL,'2025-12-05','Hoàn tiền theo phiếu trả hàng','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chart_of_accounts`
--

LOCK TABLES `chart_of_accounts` WRITE;
/*!40000 ALTER TABLE `chart_of_accounts` DISABLE KEYS */;
INSERT INTO `chart_of_accounts` VALUES (1,'111','Tiền mặt','asset',NULL,NULL,0,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(2,'112','Tiền gửi ngân hàng','asset',NULL,NULL,0,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(3,'131','Phải thu khách hàng','asset',NULL,NULL,0,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(4,'156','Hàng hóa','asset',NULL,NULL,0,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(5,'331','Phải trả người bán','liability',NULL,NULL,0,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(6,'511','Doanh thu bán hàng','revenue',NULL,NULL,0,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(7,'632','Giá vốn hàng bán','expense',NULL,NULL,0,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(8,'642','Chi phí quản lý','expense',NULL,NULL,0,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL);
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
INSERT INTO `companies` VALUES (1,'COMP-DEFAULT','Default Company',1,'active','2025-12-05 18:19:13','2025-12-05 18:19:13');
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
INSERT INTO `company_permissions` VALUES (2,1,10,'admin','[\"admin\", \"read\", \"write\", \"share\", \"delete\"]','2025-12-05 18:19:13','2025-12-05 18:19:13'),(3,1,11,'manager','[\"read\", \"write\", \"share\"]','2025-12-05 18:19:13','2025-12-05 18:19:13'),(4,1,12,'manager','[\"read\", \"write\", \"share\"]','2025-12-05 18:19:13','2025-12-05 18:19:13'),(5,1,13,'viewer','[\"read\"]','2025-12-05 18:19:13','2025-12-05 18:19:13'),(6,1,14,'viewer','[\"read\"]','2025-12-05 18:19:13','2025-12-05 18:19:13'),(7,1,15,'viewer','[\"read\"]','2025-12-05 18:19:13','2025-12-05 18:19:13');
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
INSERT INTO `customer_groups` VALUES (1,'CG-STD','Khách chuẩn',NULL,NULL,NULL,1,'active','2025-12-05 08:22:42','2025-12-05 08:22:42',NULL),(2,'CG-VIP','Khách VIP',NULL,NULL,NULL,0,'active','2025-12-05 08:22:42','2025-12-05 08:22:42',NULL),(3,'CG-WHS','Khách sỉ',NULL,NULL,NULL,0,'active','2025-12-05 08:22:42','2025-12-05 08:22:42',NULL);
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
INSERT INTO `customers` VALUES (1,NULL,NULL,'Nguyễn Văn An','an.nguyen@example.com','0901234567',NULL,NULL,NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'KH0001','Địa chỉ số 1, Hà Nội','Hà Nội',NULL,NULL,NULL,NULL,'active',NULL,0.00,0.00,0.00,'2025-12-05 09:02:22','2025-12-05 09:02:22',NULL),(2,NULL,NULL,'Trần Thị Bình','binh.tran@example.com','0902345678',NULL,NULL,NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'KH0002','Địa chỉ số 2, TP.HCM','TP.HCM',NULL,NULL,NULL,NULL,'active',NULL,0.00,0.00,0.00,'2025-12-05 09:02:22','2025-12-05 09:02:22',NULL),(3,NULL,NULL,'Lê Hoàng Cường','cuong.le@example.com','0903456789',NULL,NULL,NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'KH0003','Địa chỉ số 3, Đà Nẵng','Đà Nẵng',NULL,NULL,NULL,NULL,'active',NULL,0.00,0.00,0.00,'2025-12-05 09:02:22','2025-12-05 09:02:22',NULL),(4,NULL,NULL,'Phạm Minh Dung','dung.pham@example.com','0904567890',NULL,NULL,NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'KH0004','Địa chỉ số 4, Hải Phòng','Hải Phòng',NULL,NULL,NULL,NULL,'active',NULL,0.00,0.00,0.00,'2025-12-05 09:02:22','2025-12-05 09:02:22',NULL),(5,NULL,NULL,'Hoàng Văn Em','em.hoang@example.com','0905678901',NULL,NULL,NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'KH0005','Địa chỉ số 5, Cần Thơ','Cần Thơ',NULL,NULL,NULL,NULL,'active',NULL,0.00,0.00,0.00,'2025-12-05 09:02:22','2025-12-05 09:02:22',NULL),(6,NULL,NULL,'Vũ Thị Phương','phuong.vu@example.com','0906789012',NULL,NULL,NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'KH0006','Địa chỉ số 6, Hà Nội','Hà Nội',NULL,NULL,NULL,NULL,'active',NULL,0.00,0.00,0.00,'2025-12-05 09:02:22','2025-12-05 09:02:22',NULL),(7,NULL,NULL,'Đặng Văn Giàu','giau.dang@example.com','0907890123',NULL,NULL,NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'KH0007','Địa chỉ số 7, TP.HCM','TP.HCM',NULL,NULL,NULL,NULL,'active',NULL,0.00,0.00,0.00,'2025-12-05 09:02:22','2025-12-05 09:02:22',NULL),(8,NULL,NULL,'Bùi Thị Hạnh','hanh.bui@example.com','0908901234',NULL,NULL,NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'KH0008','Địa chỉ số 8, Đà Nẵng','Đà Nẵng',NULL,NULL,NULL,NULL,'active',NULL,0.00,0.00,0.00,'2025-12-05 09:02:22','2025-12-05 09:02:22',NULL),(9,NULL,NULL,'Đỗ Văn Hùng','hung.do@example.com','0909012345',NULL,NULL,NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'KH0009','Địa chỉ số 9, Hải Phòng','Hải Phòng',NULL,NULL,NULL,NULL,'active',NULL,0.00,0.00,0.00,'2025-12-05 09:02:22','2025-12-05 09:02:22',NULL),(10,NULL,NULL,'Ngô Thị Lan','lan.ngo@example.com','0910123456',NULL,NULL,NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'KH0010','Địa chỉ số 10, Hà Nội','Hà Nội',NULL,NULL,NULL,NULL,'active',NULL,0.00,0.00,0.00,'2025-12-05 09:02:22','2025-12-05 09:02:22',NULL),(2001,1,NULL,'Nguyễn Minh An','an.demo@lano.local','0912000001',NULL,'MALE',NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-001','12 Trần Hưng Đạo, Hà Nội','Hà Nội','Hoàn Kiếm','Hàng Bài',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(2002,1,NULL,'Trần Thu Hà','ha.demo@lano.local','0912000002',NULL,'FEMALE',NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-002','89 Lý Thường Kiệt, Hà Nội','Hà Nội','Hoàn Kiếm','Cửa Nam',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(2003,1,NULL,'Phạm Gia Bảo','bao.demo@lano.local','0912000003',NULL,'MALE',NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-003','22 Nguyễn Huệ, HCM','Hồ Chí Minh','Quận 1','Bến Nghé',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(2004,1,NULL,'Lê Hồng Nhung','nhung.demo@lano.local','0912000004',NULL,'FEMALE',NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-004','35 Hai Bà Trưng, HCM','Hồ Chí Minh','Quận 1','Bến Thành',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(2005,1,NULL,'Vũ Hoàng Long','long.demo@lano.local','0912000005',NULL,'MALE',NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-005','15 Nguyễn Tri Phương, Đà Nẵng','Đà Nẵng','Hải Châu','Thạch Thang',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(2006,1,NULL,'Đặng Bích Trâm','tram.demo@lano.local','0912000006',NULL,'FEMALE',NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-006','101 Võ Văn Tần, HCM','Hồ Chí Minh','Quận 3','6',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(2007,1,NULL,'Huỳnh Tuấn Kiệt','kiet.demo@lano.local','0912000007',NULL,'MALE',NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-007','45 Trần Phú, Nha Trang','Khánh Hòa','Nha Trang','Lộc Thọ',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(2008,1,NULL,'Lý Thu Uyên','uyen.demo@lano.local','0912000008',NULL,'FEMALE',NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-008','68 Lê Lợi, Huế','Thừa Thiên Huế','Huế','Phú Hội',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(2009,1,NULL,'Ngô Nhật Anh','nha.demo@lano.local','0912000009',NULL,'MALE',NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-009','12 Nguyễn Văn Linh, Đà Nẵng','Đà Nẵng','Hải Châu','Nam Dương',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(2010,1,NULL,'Tạ Kim Yến','yen.demo@lano.local','0912000010',NULL,'FEMALE',NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-010','99 Phan Chu Trinh, Đà Nẵng','Đà Nẵng','Hải Châu','Hải Châu 1',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(2011,1,NULL,'Công ty Ánh Dương','contact@anhduong.vn','0912000011',NULL,NULL,NULL,'COMPANY','Công ty TNHH Ánh Dương','0101234567',NULL,'Công ty TNHH Ánh Dương','11 Duy Tân, Hà Nội',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-011','11 Duy Tân, Cầu Giấy, Hà Nội','Hà Nội','Cầu Giấy','Dịch Vọng',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(2012,1,NULL,'CTCP Gỗ Xanh','ke.toan@goxanh.vn','0912000012',NULL,NULL,NULL,'COMPANY','CTCP Gỗ Xanh','0312345678',NULL,'CTCP Gỗ Xanh','45 Pasteur, Quận 1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-012','45 Pasteur, Quận 1, HCM','Hồ Chí Minh','Quận 1','Bến Nghé',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(2013,1,NULL,'Hộ KD Minh Quân','minhquan@hkd.vn','0912000013',NULL,NULL,NULL,'HOUSEHOLD','Hộ KD Minh Quân','4200123456',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-013','22 Trần Phú, Nha Trang','Khánh Hòa','Nha Trang','Vạn Thạnh',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(2014,1,NULL,'Công ty Vận Tải Nhanh','sale@vantaNhanh.vn','0912000014',NULL,NULL,NULL,'COMPANY','Công ty Vận Tải Nhanh','0109988776',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-014','88 Kim Mã, Ba Đình, Hà Nội','Hà Nội','Ba Đình','Kim Mã',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(2015,1,NULL,'CTY Thiết Kế Mộc','info@thietkemoc.vn','0912000015',NULL,NULL,NULL,'COMPANY','CTY Thiết Kế Mộc','0311122233',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-015','12 Nguyễn Trãi, Quận 5, HCM','Hồ Chí Minh','Quận 5','7',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(2016,1,NULL,'Trịnh Quốc Thái','thai.demo@lano.local','0912000016',NULL,NULL,NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-016','14 Lê Duẩn, Hà Nội','Hà Nội','Ba Đình','Điện Biên',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(2017,1,NULL,'Đỗ Hồng Ngọc','ngoc.demo@lano.local','0912000017',NULL,NULL,NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-017','7 Nguyễn Văn Cừ, Hạ Long','Quảng Ninh','Hạ Long','Bạch Đằng',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(2018,1,NULL,'La Mỹ Duyên','duyen.demo@lano.local','0912000018',NULL,NULL,NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-018','155 Lạch Tray, Hải Phòng','Hải Phòng','Ngô Quyền','Lạch Tray',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(2019,1,NULL,'Đinh Mạnh Cường','cuong.demo@lano.local','0912000019',NULL,NULL,NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-019','18 Lê Lợi, Vinh','Nghệ An','Vinh','Hưng Bình',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(2020,1,NULL,'Phùng Thanh Mai','mai.demo@lano.local','0912000020',NULL,NULL,NULL,'INDIVIDUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CUST-DEMO-020','3 Hùng Vương, Huế','Thừa Thiên Huế','Huế','Phú Nhuận',NULL,1,'ACTIVE',NULL,0.00,0.00,0.00,'2025-12-05 18:19:13','2025-12-05 18:19:13',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_note_items`
--

LOCK TABLES `delivery_note_items` WRITE;
/*!40000 ALTER TABLE `delivery_note_items` DISABLE KEYS */;
INSERT INTO `delivery_note_items` VALUES (1,31,104,501,50101,NULL,NULL,1.000,0.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(2,31,105,502,NULL,NULL,NULL,1.000,0.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(3,32,106,503,NULL,NULL,NULL,2.000,0.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(4,33,107,501,50101,NULL,NULL,1.000,0.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(5,33,108,503,NULL,NULL,NULL,1.000,0.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(6,34,109,502,NULL,NULL,NULL,2.000,0.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(7,35,110,501,50102,NULL,NULL,1.000,0.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(8,35,111,502,NULL,NULL,NULL,1.000,0.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(9,36,112,501,50101,NULL,NULL,1.000,0.500,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(10,36,113,503,NULL,NULL,NULL,1.000,0.500,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(11,37,114,502,NULL,NULL,NULL,1.000,1.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(12,37,115,503,NULL,NULL,NULL,1.000,1.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(13,38,116,501,50101,NULL,NULL,1.000,0.500,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(14,38,117,502,NULL,NULL,NULL,1.000,0.500,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(15,39,118,501,50101,NULL,NULL,1.000,1.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(16,39,119,502,NULL,NULL,NULL,1.000,1.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(17,40,120,503,NULL,NULL,NULL,2.000,2.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(18,40,121,501,50101,NULL,NULL,1.000,1.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(19,41,122,502,NULL,NULL,NULL,1.000,1.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(20,41,123,501,50102,NULL,NULL,1.000,1.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(21,42,124,503,NULL,NULL,NULL,1.000,1.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(22,42,125,501,50101,NULL,NULL,1.000,1.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(23,43,126,502,NULL,NULL,NULL,1.000,1.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(24,43,127,503,NULL,NULL,NULL,1.000,1.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(25,43,128,501,50101,NULL,NULL,1.000,1.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(26,44,129,502,NULL,NULL,NULL,2.000,2.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(27,45,130,501,50101,NULL,NULL,1.000,1.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(28,45,131,503,NULL,NULL,NULL,1.000,1.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(29,46,132,502,NULL,NULL,NULL,1.000,1.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(30,46,133,501,50101,NULL,NULL,1.000,1.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(31,47,134,503,NULL,NULL,NULL,2.000,2.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(32,48,135,501,50101,NULL,NULL,1.000,1.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(33,48,136,502,NULL,NULL,NULL,1.000,1.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(34,49,139,515,51501,NULL,NULL,2.000,2.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(35,49,140,502,NULL,NULL,NULL,1.000,1.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(36,50,141,523,NULL,NULL,NULL,1.000,0.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(37,50,142,525,52502,NULL,NULL,1.000,0.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(38,51,148,510,51002,NULL,NULL,3.000,0.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(39,51,149,509,50902,NULL,NULL,5.000,0.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(40,52,151,529,NULL,NULL,NULL,1.000,0.500,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(41,52,152,501,NULL,NULL,NULL,1.000,0.500,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(42,53,153,520,52002,NULL,NULL,4.000,0.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(43,54,154,509,50901,NULL,NULL,1.000,1.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(44,54,155,516,NULL,NULL,NULL,5.000,5.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(45,55,157,507,50701,NULL,NULL,4.000,4.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(46,55,158,530,NULL,NULL,NULL,1.000,1.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(47,56,159,512,NULL,NULL,NULL,1.000,1.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(48,56,160,511,51101,NULL,NULL,2.000,2.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(49,57,161,526,52601,NULL,NULL,4.000,4.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(50,57,162,513,NULL,NULL,NULL,2.000,2.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(51,57,163,515,NULL,NULL,NULL,1.000,1.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(52,58,164,519,NULL,NULL,NULL,2.000,2.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(53,58,165,501,NULL,NULL,NULL,2.000,2.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(54,58,166,530,53002,NULL,NULL,4.000,4.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(55,59,167,503,50301,NULL,NULL,5.000,5.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(56,59,168,503,NULL,NULL,NULL,3.000,3.000,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_notes`
--

LOCK TABLES `delivery_notes` WRITE;
/*!40000 ALTER TABLE `delivery_notes` DISABLE KEYS */;
INSERT INTO `delivery_notes` VALUES (16,'VNMDH251205-0001',1001,2,NULL,NULL,NULL,'picking','Địa chỉ số 2, TP.HCM',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(17,'VNMDH251205-0002',1002,8,NULL,NULL,NULL,'delivered','Địa chỉ số 8, Đà Nẵng',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(18,'VNMDH251205-0003',1003,4,NULL,NULL,NULL,'delivered','Địa chỉ số 4, Hải Phòng',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(19,'VNMDH251205-0005',1005,1,NULL,NULL,NULL,'picking','Địa chỉ số 1, Hà Nội',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(20,'VNMDH251205-0006',1006,3,NULL,NULL,NULL,'delivered','Địa chỉ số 3, Đà Nẵng',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(21,'VNMDH251205-0007',1007,6,NULL,NULL,NULL,'delivered','Địa chỉ số 6, Hà Nội',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(22,'VNMDH251205-0009',1009,7,NULL,NULL,NULL,'picking','Địa chỉ số 7, TP.HCM',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(23,'VNMDH251205-0010',1010,2,NULL,NULL,NULL,'delivered','Địa chỉ số 2, TP.HCM',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(24,'VNMDH251205-0011',1011,2,NULL,NULL,NULL,'delivered','Địa chỉ số 2, TP.HCM',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(25,'VNMDH251205-0013',1013,10,NULL,NULL,NULL,'picking','Địa chỉ số 10, Hà Nội',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(26,'VNMDH251205-0014',1014,10,NULL,NULL,NULL,'delivered','Địa chỉ số 10, Hà Nội',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(27,'VNMDH251205-0015',1015,2,NULL,NULL,NULL,'delivered','Địa chỉ số 2, TP.HCM',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(28,'VNMDH251205-0017',1017,10,NULL,NULL,NULL,'picking','Địa chỉ số 10, Hà Nội',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(29,'VNMDH251205-0018',1018,4,NULL,NULL,NULL,'delivered','Địa chỉ số 4, Hải Phòng',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(30,'VNMDH251205-0019',1019,2,NULL,NULL,NULL,'delivered','Địa chỉ số 2, TP.HCM',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(31,'DN-DEMO-001',1021,2001,1,'2025-11-06','2025-11-06','draft','12 Trần Hưng Đạo, Hà Nội','TRK-001','Demo Carrier','Demo delivery note from DH-DEMO-001',NULL,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(32,'DN-DEMO-002',1022,2002,2,'2025-11-07','2025-11-07','draft','89 Lý Thường Kiệt, Hà Nội','TRK-002','Demo Carrier','Demo delivery note from DH-DEMO-002',NULL,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(33,'DN-DEMO-003',1023,2003,3,'2025-11-08','2025-11-08','confirmed','22 Nguyễn Huệ, HCM','TRK-003','Demo Carrier','Demo delivery note from DH-DEMO-003',NULL,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(34,'DN-DEMO-004',1024,2004,4,'2025-11-09','2025-11-09','confirmed','35 Hai Bà Trưng, HCM','TRK-004','Demo Carrier','Demo delivery note from DH-DEMO-004',NULL,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(35,'DN-DEMO-005',1025,2005,5,'2025-11-10','2025-11-10','confirmed','15 Nguyễn Tri Phương, Đà Nẵng','TRK-005','Demo Carrier','Demo delivery note from DH-DEMO-005',NULL,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(36,'DN-DEMO-006',1026,2006,1,'2025-11-11','2025-11-11','shipped','101 Võ Văn Tần, HCM','TRK-006','Demo Carrier','Demo delivery note from DH-DEMO-006',NULL,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(37,'DN-DEMO-007',1027,2007,2,'2025-11-12','2025-11-12','delivered','45 Trần Phú, Nha Trang','TRK-007','Demo Carrier','Demo delivery note from DH-DEMO-007',NULL,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(38,'DN-DEMO-008',1028,2008,3,'2025-11-13','2025-11-13','shipped','68 Lê Lợi, Huế','TRK-008','Demo Carrier','Demo delivery note from DH-DEMO-008',NULL,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(39,'DN-DEMO-009',1029,2011,1,'2025-11-14','2025-11-14','delivered','11 Duy Tân, Cầu Giấy, Hà Nội','TRK-009','Demo Carrier','Demo delivery note from DH-DEMO-009',NULL,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(40,'DN-DEMO-010',1030,2012,2,'2025-11-15','2025-11-15','delivered','45 Pasteur, Quận 1, HCM','TRK-010','Demo Carrier','Demo delivery note from DH-DEMO-010',NULL,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(41,'DN-DEMO-011',1031,2013,3,'2025-11-16','2025-11-16','delivered','22 Trần Phú, Nha Trang','TRK-011','Demo Carrier','Demo delivery note from DH-DEMO-011',NULL,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(42,'DN-DEMO-012',1032,2014,4,'2025-11-17','2025-11-17','delivered','88 Kim Mã, Ba Đình, Hà Nội','TRK-012','Demo Carrier','Demo delivery note from DH-DEMO-012',NULL,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(43,'DN-DEMO-013',1033,2015,5,'2025-11-18','2025-11-18','delivered','12 Nguyễn Trãi, Quận 5, HCM','TRK-013','Demo Carrier','Demo delivery note from DH-DEMO-013',NULL,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(44,'DN-DEMO-014',1034,2016,1,'2025-11-19','2025-11-19','delivered','14 Lê Duẩn, Hà Nội','TRK-014','Demo Carrier','Demo delivery note from DH-DEMO-014',NULL,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(45,'DN-DEMO-015',1035,2017,2,'2025-11-20','2025-11-20','delivered','7 Nguyễn Văn Cừ, Hạ Long','TRK-015','Demo Carrier','Demo delivery note from DH-DEMO-015',NULL,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(46,'DN-DEMO-016',1036,2018,3,'2025-11-21','2025-11-21','delivered','155 Lạch Tray, Hải Phòng','TRK-016','Demo Carrier','Demo delivery note from DH-DEMO-016',NULL,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(47,'DN-DEMO-017',1037,2019,4,'2025-11-22','2025-11-22','delivered','18 Lê Lợi, Vinh','TRK-017','Demo Carrier','Demo delivery note from DH-DEMO-017',NULL,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(48,'DN-DEMO-018',1038,2020,5,'2025-11-23','2025-11-23','delivered','3 Hùng Vương, Huế','TRK-018','Demo Carrier','Demo delivery note from DH-DEMO-018',NULL,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(49,'DN-DEMO-019',1041,2003,1,'2025-11-26','2025-11-26','delivered','22 Nguyễn Huệ, HCM','TRK-019','Demo Carrier','Demo delivery note from DH-DEMO-021',NULL,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(50,'DN-DEMO-020',1042,2005,2,'2025-11-27','2025-11-27','draft','15 Nguyễn Tri Phương, Đà Nẵng','TRK-020','Demo Carrier','Demo delivery note from DH-DEMO-022',NULL,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(51,'DN-DEMO-021',1045,2020,5,'2025-11-30','2025-11-30','draft','3 Hùng Vương, Huế','TRK-021','Demo Carrier','Demo delivery note from DH-DEMO-025',NULL,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(52,'DN-DEMO-022',1047,2014,5,'2025-12-02','2025-12-02','shipped','88 Kim Mã, Ba Đình, Hà Nội','TRK-022','Demo Carrier','Demo delivery note from DH-DEMO-027',NULL,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(53,'DN-DEMO-023',1048,2011,2,'2025-12-03','2025-12-03','confirmed','11 Duy Tân, Cầu Giấy, Hà Nội','TRK-023','Demo Carrier','Demo delivery note from DH-DEMO-028',NULL,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(54,'DN-DEMO-024',1049,2015,1,'2025-12-04','2025-12-04','delivered','12 Nguyễn Trãi, Quận 5, HCM','TRK-024','Demo Carrier','Demo delivery note from DH-DEMO-029',NULL,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(55,'DN-DEMO-025',1051,2012,5,'2025-12-06','2025-12-06','delivered','45 Pasteur, Quận 1, HCM','TRK-025','Demo Carrier','Demo delivery note from DH-DEMO-031',NULL,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(56,'DN-DEMO-026',1052,2013,5,'2025-12-07','2025-12-07','delivered','22 Trần Phú, Nha Trang','TRK-026','Demo Carrier','Demo delivery note from DH-DEMO-032',NULL,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(57,'DN-DEMO-027',1053,2014,1,'2025-12-08','2025-12-08','delivered','88 Kim Mã, Ba Đình, Hà Nội','TRK-027','Demo Carrier','Demo delivery note from DH-DEMO-033',NULL,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(58,'DN-DEMO-028',1054,2011,4,'2025-12-09','2025-12-09','delivered','11 Duy Tân, Cầu Giấy, Hà Nội','TRK-028','Demo Carrier','Demo delivery note from DH-DEMO-034',NULL,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(59,'DN-DEMO-029',1055,2013,4,'2025-12-10','2025-12-10','delivered','22 Trần Phú, Nha Trang','TRK-029','Demo Carrier','Demo delivery note from DH-DEMO-035',NULL,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL);
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
INSERT INTO `departments` VALUES (1,'Ban Giám Đốc','BGD','Board of Directors','active','2025-12-05 18:19:13','2025-12-05 18:19:13'),(2,'Phòng Kinh Doanh','KD','Sales Department','active','2025-12-05 18:19:13','2025-12-05 18:19:13'),(3,'Phòng Kế Toán','KT','Accounting Department','active','2025-12-05 18:19:13','2025-12-05 18:19:13'),(4,'Phòng Nhân Sự','NS','HR Department','active','2025-12-05 18:19:13','2025-12-05 18:19:13'),(5,'Kho Vận','KV','Logistics Department','active','2025-12-05 18:19:13','2025-12-05 18:19:13');
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
INSERT INTO `devices` VALUES (1,'DEV-POS-01','POS Hà Nội','pos',1,'active',NULL,'2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(2,'DEV-POS-02','POS HCM','pos',2,'active',NULL,'2025-12-05 18:19:13','2025-12-05 18:19:13',NULL);
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
  CONSTRAINT `fk_employees_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (1,'EMP001','Admin User',NULL,'active',NULL,'2025-12-05 18:19:13','2025-12-05 18:19:13',1,'EMP001','Admin','User','admin@lanocrm.local','0900000001',1,1,'2023-12-05'),(2,'EMP002','Manager Test',NULL,'active',NULL,'2025-12-05 18:19:13','2025-12-05 18:19:13',2,'EMP002','Manager','Test','manager@lanocrm.local','0900000002',2,2,'2024-12-05'),(3,'EMP003','Staff Test',NULL,'active',NULL,'2025-12-05 18:19:13','2025-12-05 18:19:13',3,'EMP003','Staff','Test','staff@lanocrm.local','0900000003',2,3,'2025-06-05');
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
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoice_orders`
--

LOCK TABLES `invoice_orders` WRITE;
/*!40000 ALTER TABLE `invoice_orders` DISABLE KEYS */;
INSERT INTO `invoice_orders` VALUES (11,5002,1002,'2025-12-05 09:02:23',NULL),(12,5003,1003,'2025-12-05 09:02:23',NULL),(13,5006,1006,'2025-12-05 09:02:23',NULL),(14,5007,1007,'2025-12-05 09:02:23',NULL),(15,5010,1010,'2025-12-05 09:02:23',NULL),(16,5011,1011,'2025-12-05 09:02:23',NULL),(17,5014,1014,'2025-12-05 09:02:23',NULL),(18,5015,1015,'2025-12-05 09:02:23',NULL),(19,5018,1018,'2025-12-05 09:02:23',NULL),(20,5019,1019,'2025-12-05 09:02:23',NULL),(21,5020,1029,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(22,5021,1030,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(23,5022,1031,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(24,5023,1032,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(25,5024,1033,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(26,5025,1049,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(27,5026,1051,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(28,5027,1052,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(29,5028,1053,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(30,5029,1054,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(31,5030,1055,'2025-12-05 18:19:14','2025-12-05 18:19:14');
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
) ENGINE=InnoDB AUTO_INCREMENT=5031 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
INSERT INTO `invoices` VALUES (5002,'HDDH251205-0002','complete',NULL,NULL,8,NULL,'2025-12-05',NULL,0.00,0.00,0.00,5054000.00,0.00,0.00,0.00,0.00,0.00,5054000.00,5054000.00,0.00,0.00,'paid',0.00,'VND',1.000000,NULL,5054000.00,NULL,NULL,NULL,NULL,'2025-12-05 09:02:23','2025-12-05 09:02:23'),(5003,'HDDH251205-0003','complete',NULL,NULL,4,NULL,'2025-12-05',NULL,0.00,0.00,0.00,5132000.00,0.00,0.00,0.00,0.00,0.00,5132000.00,5132000.00,0.00,0.00,'paid',0.00,'VND',1.000000,NULL,5132000.00,NULL,NULL,NULL,NULL,'2025-12-05 09:02:23','2025-12-05 09:02:23'),(5006,'HDDH251205-0006','complete',NULL,NULL,3,NULL,'2025-12-05',NULL,0.00,0.00,0.00,372000.00,0.00,0.00,0.00,0.00,0.00,372000.00,372000.00,0.00,0.00,'paid',0.00,'VND',1.000000,NULL,372000.00,NULL,NULL,NULL,NULL,'2025-12-05 09:02:23','2025-12-05 09:02:23'),(5007,'HDDH251205-0007','complete',NULL,NULL,6,NULL,'2025-12-05',NULL,0.00,0.00,0.00,1920000.00,0.00,0.00,0.00,0.00,0.00,1920000.00,1920000.00,0.00,0.00,'paid',0.00,'VND',1.000000,NULL,1920000.00,NULL,NULL,NULL,NULL,'2025-12-05 09:02:23','2025-12-05 09:02:23'),(5010,'HDDH251205-0010','complete',NULL,NULL,2,NULL,'2025-12-05',NULL,0.00,0.00,0.00,4127000.00,0.00,0.00,0.00,0.00,0.00,4127000.00,4127000.00,0.00,0.00,'paid',0.00,'VND',1.000000,NULL,4127000.00,NULL,NULL,NULL,NULL,'2025-12-05 09:02:23','2025-12-05 09:02:23'),(5011,'HDDH251205-0011','complete',NULL,NULL,2,NULL,'2025-12-05',NULL,0.00,0.00,0.00,4189000.00,0.00,0.00,0.00,0.00,0.00,4189000.00,4189000.00,0.00,0.00,'paid',0.00,'VND',1.000000,NULL,4189000.00,NULL,NULL,NULL,NULL,'2025-12-05 09:02:23','2025-12-05 09:02:23'),(5014,'HDDH251205-0014','complete',NULL,NULL,10,NULL,'2025-12-05',NULL,0.00,0.00,0.00,3515000.00,0.00,0.00,0.00,0.00,0.00,3515000.00,3515000.00,0.00,0.00,'paid',0.00,'VND',1.000000,NULL,3515000.00,NULL,NULL,NULL,NULL,'2025-12-05 09:02:23','2025-12-05 09:02:23'),(5015,'HDDH251205-0015','complete',NULL,NULL,2,NULL,'2025-12-05',NULL,0.00,0.00,0.00,3529000.00,0.00,0.00,0.00,0.00,0.00,3529000.00,3529000.00,0.00,0.00,'paid',0.00,'VND',1.000000,NULL,3529000.00,NULL,NULL,NULL,NULL,'2025-12-05 09:02:23','2025-12-05 09:02:23'),(5018,'HDDH251205-0018','complete',NULL,NULL,4,NULL,'2025-12-05',NULL,0.00,0.00,0.00,4696000.00,0.00,0.00,0.00,0.00,0.00,4696000.00,4696000.00,0.00,0.00,'paid',0.00,'VND',1.000000,NULL,4696000.00,NULL,NULL,NULL,NULL,'2025-12-05 09:02:23','2025-12-05 09:02:23'),(5019,'HDDH251205-0019','complete',NULL,NULL,2,NULL,'2025-12-05',NULL,0.00,0.00,0.00,9257000.00,0.00,0.00,0.00,0.00,0.00,9257000.00,9257000.00,0.00,0.00,'paid',0.00,'VND',1.000000,NULL,9257000.00,NULL,NULL,NULL,NULL,'2025-12-05 09:02:23','2025-12-05 09:02:23'),(5020,'HD-DEMO-1-0001','completed','standard',NULL,2011,1,'2025-11-25','2025-12-07',416000.00,416000.00,0.00,416000.00,0.10,41600.00,41600.00,0.00,0.00,457600.00,457600.00,0.00,0.00,'paid',457600.00,'VND',1.000000,NULL,457600.00,NULL,'Hóa đơn demo gắn với DH-DEMO-009','{\"source\": \"demo\"}',1,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(5021,'HD-DEMO-2-0001','completed','standard',NULL,2012,2,'2025-11-26','2025-12-08',611600.00,624000.00,42400.00,611600.00,0.05,30580.00,30580.00,0.00,30000.00,611600.00,611600.00,0.00,0.00,'paid',611600.00,'VND',1.000000,NULL,611600.00,NULL,'Hóa đơn demo gắn với DH-DEMO-010','{\"source\": \"demo\"}',1,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(5022,'HD-DEMO-3-0001','completed','standard',NULL,2013,3,'2025-11-27','2025-12-09',379400.00,426000.00,66600.00,379400.00,0.05,18970.00,18970.00,0.00,20000.00,398370.00,398370.00,0.00,0.00,'paid',398370.00,'VND',1.000000,NULL,398370.00,NULL,'Hóa đơn demo gắn với DH-DEMO-011','{\"source\": \"demo\"}',1,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(5023,'HD-DEMO-4-0001','completed','standard',NULL,2014,4,'2025-11-28','2025-12-10',418000.00,418000.00,0.00,418000.00,0.10,41800.00,41800.00,0.00,0.00,459800.00,459800.00,0.00,0.00,'paid',459800.00,'VND',1.000000,NULL,459800.00,NULL,'Hóa đơn demo gắn với DH-DEMO-012','{\"source\": \"demo\"}',1,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(5024,'HD-DEMO-5-0001','completed','standard',NULL,2015,5,'2025-11-29','2025-12-11',594600.00,622000.00,42400.00,594600.00,0.05,29730.00,29730.00,0.00,15000.00,594600.00,594600.00,0.00,0.00,'paid',594600.00,'VND',1.000000,NULL,594600.00,NULL,'Hóa đơn demo gắn với DH-DEMO-013','{\"source\": \"demo\"}',1,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(5025,'HD-DEMO-1-0002','completed','standard',NULL,2015,1,'2025-11-30','2025-12-12',1398000.00,1388000.00,0.00,1398000.00,0.05,69900.00,69900.00,0.00,10000.00,1467900.00,1467900.00,0.00,0.00,'paid',1467900.00,'VND',1.000000,NULL,1467900.00,NULL,'Hóa đơn demo gắn với DH-DEMO-029','{\"source\": \"demo\"}',1,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(5026,'HD-DEMO-5-0002','completed','standard',NULL,2012,5,'2025-12-01','2025-12-13',1206000.00,1156000.00,0.00,1206000.00,0.00,0.00,0.00,0.00,50000.00,1206000.00,1206000.00,0.00,0.00,'paid',1206000.00,'VND',1.000000,NULL,1206000.00,NULL,'Hóa đơn demo gắn với DH-DEMO-031','{\"source\": \"demo\"}',1,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(5027,'HD-DEMO-5-0003','completed','standard',NULL,2013,5,'2025-12-02','2025-12-14',728000.00,688000.00,0.00,728000.00,0.05,36400.00,36400.00,0.00,40000.00,764400.00,764400.00,0.00,0.00,'paid',764400.00,'VND',1.000000,NULL,764400.00,NULL,'Hóa đơn demo gắn với DH-DEMO-032','{\"source\": \"demo\"}',1,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(5028,'HD-DEMO-1-0003','completed','standard',NULL,2014,1,'2025-12-03','2025-12-15',1730000.00,1730000.00,0.00,1730000.00,0.10,173000.00,173000.00,0.00,0.00,1903000.00,1903000.00,0.00,0.00,'paid',1903000.00,'VND',1.000000,NULL,1903000.00,NULL,'Hóa đơn demo gắn với DH-DEMO-033','{\"source\": \"demo\"}',1,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(5029,'HD-DEMO-4-0002','completed','standard',NULL,2011,4,'2025-12-04','2025-12-16',2050000.00,2000000.00,0.00,2050000.00,0.00,0.00,0.00,0.00,50000.00,2050000.00,2050000.00,0.00,0.00,'paid',2050000.00,'VND',1.000000,NULL,2050000.00,NULL,'Hóa đơn demo gắn với DH-DEMO-034','{\"source\": \"demo\"}',1,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(5030,'HD-DEMO-4-0003','completed','standard',NULL,2013,4,'2025-12-05','2025-12-17',1738000.00,1698000.00,0.00,1738000.00,0.05,86900.00,86900.00,0.00,40000.00,1824900.00,1824900.00,0.00,0.00,'paid',1824900.00,'VND',1.000000,NULL,1824900.00,NULL,'Hóa đơn demo gắn với DH-DEMO-035','{\"source\": \"demo\"}',1,'2025-12-05 18:19:14','2025-12-05 18:19:14');
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
INSERT INTO `journal_entries` VALUES (1,'JE-2024-001','2025-11-30','Thu tiền bán hàng','posted',1,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(2,'JE-2024-002','2025-12-03','Thanh toán tiền điện','posted',1,'2025-12-05 18:19:14','2025-12-05 18:19:14');
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `journal_entry_lines`
--

LOCK TABLES `journal_entry_lines` WRITE;
/*!40000 ALTER TABLE `journal_entry_lines` DISABLE KEYS */;
INSERT INTO `journal_entry_lines` VALUES (1,1,'111',5000000.00,0.00,'Thu tiền mặt','2025-12-05 18:19:14','2025-12-05 18:19:14'),(2,1,'511',0.00,5000000.00,'Doanh thu','2025-12-05 18:19:14','2025-12-05 18:19:14'),(3,2,'642',1000000.00,0.00,'Chi phí điện','2025-12-05 18:19:14','2025-12-05 18:19:14'),(4,2,'112',0.00,1000000.00,'Chuyển khoản','2025-12-05 18:19:14','2025-12-05 18:19:14');
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
INSERT INTO `leads` VALUES (1,NULL,'','john.doe@example.com','0912345678','website','new','Example Corp',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14','John','Doe',2),(2,NULL,'','jane.smith@example.com','0987654321','referral','contacted','Tech Solutions',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14','Jane','Smith',3);
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_applications`
--

LOCK TABLES `leave_applications` WRITE;
/*!40000 ALTER TABLE `leave_applications` DISABLE KEYS */;
INSERT INTO `leave_applications` VALUES (1,3,1,'2025-12-10','2025-12-11',0.00,'pending','Personal matters',NULL,NULL,'2025-12-05 18:19:13','2025-12-05 18:19:13');
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
INSERT INTO `leave_types` VALUES (1,'Annual Leave',12.00,'2025-12-05 18:19:13','2025-12-05 18:19:13'),(2,'Sick Leave',10.00,'2025-12-05 18:19:13','2025-12-05 18:19:13'),(3,'Unpaid Leave',0.00,'2025-12-05 18:19:13','2025-12-05 18:19:13');
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2025-12-05-000000','App\\Database\\Migrations\\BaselineSchema','default','App',1764920854,4),(2,'2025-12-05-074709','App\\Database\\Migrations\\AddIsSystemToPriceLists','default','App',1764920854,4),(3,'2025-12-05-100000','App\\Database\\Migrations\\AddConfigToPriceLists','default','App',1764920854,4);
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
  `name` varchar(255) DEFAULT NULL,
  `amount` decimal(14,2) DEFAULT '0.00',
  `close_date` date DEFAULT NULL,
  `assigned_to` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_opportunities_customer_id` (`customer_id`),
  KEY `fk_opportunities_lead_id` (`lead_id`),
  CONSTRAINT `fk_opportunities_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_opportunities_lead_id` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `opportunities`
--

LOCK TABLES `opportunities` WRITE;
/*!40000 ALTER TABLE `opportunities` DISABLE KEYS */;
INSERT INTO `opportunities` VALUES (1,NULL,1,'','proposal',60,0.00,NULL,'open','2025-12-05 18:19:14','2025-12-05 18:19:14','Big Deal with Example Corp',50000000.00,'2026-01-04',2),(2,NULL,2,'','negotiation',80,0.00,NULL,'open','2025-12-05 18:19:14','2025-12-05 18:19:14','Service Contract',12000000.00,'2025-12-20',3);
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
) ENGINE=InnoDB AUTO_INCREMENT=169 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (104,1021,501,50101,NULL,NULL,1.000,212000.00,212000.00,NULL,NULL,'2025-11-05 10:00:00','2025-11-05 10:00:00',NULL),(105,1021,502,NULL,NULL,NULL,1.000,204000.00,204000.00,NULL,NULL,'2025-11-05 10:00:00','2025-11-05 10:00:00',NULL),(106,1022,503,NULL,NULL,NULL,2.000,206000.00,206000.00,NULL,NULL,'2025-11-06 10:00:00','2025-11-06 10:00:00',NULL),(107,1023,501,50101,NULL,NULL,1.000,212000.00,169600.00,1,'VIP 20%','2025-11-07 10:00:00','2025-11-07 10:00:00',NULL),(108,1023,503,NULL,NULL,NULL,1.000,206000.00,206000.00,NULL,NULL,'2025-11-07 10:00:00','2025-11-07 10:00:00',NULL),(109,1024,502,NULL,NULL,NULL,2.000,204000.00,204000.00,NULL,NULL,'2025-11-08 10:00:00','2025-11-08 10:00:00',NULL),(110,1025,501,50102,NULL,NULL,1.000,222000.00,155400.00,3,'Flash Sale 30%','2025-11-09 10:00:00','2025-11-09 10:00:00',NULL),(111,1025,502,NULL,NULL,NULL,1.000,204000.00,204000.00,NULL,NULL,'2025-11-09 10:00:00','2025-11-09 10:00:00',NULL),(112,1026,501,50101,NULL,NULL,1.000,212000.00,212000.00,NULL,NULL,'2025-11-10 10:00:00','2025-11-10 10:00:00',NULL),(113,1026,503,NULL,NULL,NULL,1.000,206000.00,206000.00,NULL,NULL,'2025-11-10 10:00:00','2025-11-10 10:00:00',NULL),(114,1027,502,NULL,NULL,NULL,1.000,204000.00,204000.00,NULL,NULL,'2025-11-11 10:00:00','2025-11-11 10:00:00',NULL),(115,1027,503,NULL,NULL,NULL,1.000,206000.00,206000.00,NULL,NULL,'2025-11-11 10:00:00','2025-11-11 10:00:00',NULL),(116,1028,501,50101,NULL,NULL,1.000,212000.00,169600.00,1,'VIP 20%','2025-11-12 10:00:00','2025-11-12 10:00:00',NULL),(117,1028,502,NULL,NULL,NULL,1.000,204000.00,204000.00,NULL,NULL,'2025-11-12 10:00:00','2025-11-12 10:00:00',NULL),(118,1029,501,50101,NULL,NULL,1.000,212000.00,212000.00,NULL,NULL,'2025-11-13 10:00:00','2025-11-13 10:00:00',NULL),(119,1029,502,NULL,NULL,NULL,1.000,204000.00,204000.00,NULL,NULL,'2025-11-13 10:00:00','2025-11-13 10:00:00',NULL),(120,1030,503,NULL,NULL,NULL,2.000,206000.00,206000.00,NULL,NULL,'2025-11-14 10:00:00','2025-11-14 10:00:00',NULL),(121,1030,501,50101,NULL,NULL,1.000,212000.00,169600.00,1,'VIP 20%','2025-11-14 10:00:00','2025-11-14 10:00:00',NULL),(122,1031,502,NULL,NULL,NULL,1.000,204000.00,204000.00,NULL,NULL,'2025-11-15 10:00:00','2025-11-15 10:00:00',NULL),(123,1031,501,50102,NULL,NULL,1.000,222000.00,155400.00,3,'Flash Sale 30%','2025-11-15 10:00:00','2025-11-15 10:00:00',NULL),(124,1032,503,NULL,NULL,NULL,1.000,206000.00,206000.00,NULL,NULL,'2025-11-16 10:00:00','2025-11-16 10:00:00',NULL),(125,1032,501,50101,NULL,NULL,1.000,212000.00,212000.00,NULL,NULL,'2025-11-16 10:00:00','2025-11-16 10:00:00',NULL),(126,1033,502,NULL,NULL,NULL,1.000,204000.00,204000.00,NULL,NULL,'2025-11-17 10:00:00','2025-11-17 10:00:00',NULL),(127,1033,503,NULL,NULL,NULL,1.000,206000.00,206000.00,NULL,NULL,'2025-11-17 10:00:00','2025-11-17 10:00:00',NULL),(128,1033,501,50101,NULL,NULL,1.000,212000.00,169600.00,1,'VIP 20%','2025-11-17 10:00:00','2025-11-17 10:00:00',NULL),(129,1034,502,NULL,NULL,NULL,2.000,204000.00,204000.00,NULL,NULL,'2025-11-18 10:00:00','2025-11-18 10:00:00',NULL),(130,1035,501,50101,NULL,NULL,1.000,212000.00,212000.00,NULL,NULL,'2025-11-19 10:00:00','2025-11-19 10:00:00',NULL),(131,1035,503,NULL,NULL,NULL,1.000,206000.00,206000.00,NULL,NULL,'2025-11-19 10:00:00','2025-11-19 10:00:00',NULL),(132,1036,502,NULL,NULL,NULL,1.000,204000.00,204000.00,NULL,NULL,'2025-11-20 10:00:00','2025-11-20 10:00:00',NULL),(133,1036,501,50101,NULL,NULL,1.000,212000.00,212000.00,NULL,NULL,'2025-11-20 10:00:00','2025-11-20 10:00:00',NULL),(134,1037,503,NULL,NULL,NULL,2.000,206000.00,206000.00,NULL,NULL,'2025-11-21 10:00:00','2025-11-21 10:00:00',NULL),(135,1038,501,50101,NULL,NULL,1.000,212000.00,169600.00,1,'VIP 20%','2025-11-22 10:00:00','2025-11-22 10:00:00',NULL),(136,1038,502,NULL,NULL,NULL,1.000,204000.00,204000.00,NULL,NULL,'2025-11-22 10:00:00','2025-11-22 10:00:00',NULL),(137,1039,502,NULL,NULL,NULL,1.000,204000.00,204000.00,NULL,NULL,'2025-11-23 10:00:00','2025-11-23 10:00:00',NULL),(138,1040,503,NULL,NULL,NULL,1.000,206000.00,206000.00,NULL,NULL,'2025-11-24 10:00:00','2025-11-24 10:00:00',NULL),(139,1041,515,51501,NULL,NULL,2.000,240000.00,240000.00,NULL,NULL,'2025-11-25 10:00:00','2025-11-25 10:00:00',NULL),(140,1041,502,NULL,NULL,NULL,1.000,204000.00,204000.00,NULL,NULL,'2025-11-25 10:00:00','2025-11-25 10:00:00',NULL),(141,1042,523,NULL,NULL,NULL,1.000,246000.00,246000.00,NULL,NULL,'2025-11-26 10:00:00','2025-11-26 10:00:00',NULL),(142,1042,525,52502,NULL,NULL,1.000,270000.00,270000.00,NULL,NULL,'2025-11-26 10:00:00','2025-11-26 10:00:00',NULL),(143,1043,508,NULL,NULL,NULL,2.000,216000.00,216000.00,NULL,NULL,'2025-11-27 10:00:00','2025-11-27 10:00:00',NULL),(144,1043,526,NULL,NULL,NULL,2.000,252000.00,252000.00,NULL,NULL,'2025-11-27 10:00:00','2025-11-27 10:00:00',NULL),(145,1043,507,50701,NULL,NULL,2.000,224000.00,224000.00,NULL,NULL,'2025-11-27 10:00:00','2025-11-27 10:00:00',NULL),(146,1044,502,50201,NULL,NULL,4.000,214000.00,214000.00,NULL,NULL,'2025-11-28 10:00:00','2025-11-28 10:00:00',NULL),(147,1044,504,50402,NULL,NULL,1.000,228000.00,228000.00,NULL,NULL,'2025-11-28 10:00:00','2025-11-28 10:00:00',NULL),(148,1045,510,51002,NULL,NULL,3.000,240000.00,240000.00,NULL,NULL,'2025-11-29 10:00:00','2025-11-29 10:00:00',NULL),(149,1045,509,50902,NULL,NULL,5.000,238000.00,238000.00,NULL,NULL,'2025-11-29 10:00:00','2025-11-29 10:00:00',NULL),(150,1046,522,NULL,NULL,NULL,1.000,244000.00,244000.00,NULL,NULL,'2025-11-30 10:00:00','2025-11-30 10:00:00',NULL),(151,1047,529,NULL,NULL,NULL,1.000,258000.00,258000.00,NULL,NULL,'2025-12-01 10:00:00','2025-12-01 10:00:00',NULL),(152,1047,501,NULL,NULL,NULL,1.000,202000.00,202000.00,NULL,NULL,'2025-12-01 10:00:00','2025-12-01 10:00:00',NULL),(153,1048,520,52002,NULL,NULL,4.000,260000.00,260000.00,NULL,NULL,'2025-12-02 10:00:00','2025-12-02 10:00:00',NULL),(154,1049,509,50901,NULL,NULL,1.000,228000.00,228000.00,NULL,NULL,'2025-12-03 10:00:00','2025-12-03 10:00:00',NULL),(155,1049,516,NULL,NULL,NULL,5.000,232000.00,232000.00,NULL,NULL,'2025-12-03 10:00:00','2025-12-03 10:00:00',NULL),(156,1050,520,NULL,NULL,NULL,2.000,240000.00,240000.00,NULL,NULL,'2025-12-04 10:00:00','2025-12-04 10:00:00',NULL),(157,1051,507,50701,NULL,NULL,4.000,224000.00,224000.00,NULL,NULL,'2025-12-05 10:00:00','2025-12-05 10:00:00',NULL),(158,1051,530,NULL,NULL,NULL,1.000,260000.00,260000.00,NULL,NULL,'2025-12-05 10:00:00','2025-12-05 10:00:00',NULL),(159,1052,512,NULL,NULL,NULL,1.000,224000.00,224000.00,NULL,NULL,'2025-12-06 10:00:00','2025-12-06 10:00:00',NULL),(160,1052,511,51101,NULL,NULL,2.000,232000.00,232000.00,NULL,NULL,'2025-12-06 10:00:00','2025-12-06 10:00:00',NULL),(161,1053,526,52601,NULL,NULL,4.000,262000.00,262000.00,NULL,NULL,'2025-12-07 10:00:00','2025-12-07 10:00:00',NULL),(162,1053,513,NULL,NULL,NULL,2.000,226000.00,226000.00,NULL,NULL,'2025-12-07 10:00:00','2025-12-07 10:00:00',NULL),(163,1053,515,NULL,NULL,NULL,1.000,230000.00,230000.00,NULL,NULL,'2025-12-07 10:00:00','2025-12-07 10:00:00',NULL),(164,1054,519,NULL,NULL,NULL,2.000,238000.00,238000.00,NULL,NULL,'2025-12-08 10:00:00','2025-12-08 10:00:00',NULL),(165,1054,501,NULL,NULL,NULL,2.000,202000.00,202000.00,NULL,NULL,'2025-12-08 10:00:00','2025-12-08 10:00:00',NULL),(166,1054,530,53002,NULL,NULL,4.000,280000.00,280000.00,NULL,NULL,'2025-12-08 10:00:00','2025-12-08 10:00:00',NULL),(167,1055,503,50301,NULL,NULL,5.000,216000.00,216000.00,NULL,NULL,'2025-12-09 10:00:00','2025-12-09 10:00:00',NULL),(168,1055,503,NULL,NULL,NULL,3.000,206000.00,206000.00,NULL,NULL,'2025-12-09 10:00:00','2025-12-09 10:00:00',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_payments`
--

LOCK TABLES `order_payments` WRITE;
/*!40000 ALTER TABLE `order_payments` DISABLE KEYS */;
INSERT INTO `order_payments` VALUES (1,1021,'CASH',0.00,NULL,'2025-11-05 12:00:00','2025-11-05 12:00:00'),(2,1022,'BANK_TRANSFER',166110.00,'2025-11-06 12:00:00','2025-11-06 12:00:00','2025-11-06 12:00:00'),(3,1023,'COD',180664.00,'2025-11-07 12:00:00','2025-11-07 12:00:00','2025-11-07 12:00:00'),(4,1024,'CASH',204000.00,'2025-11-08 12:00:00','2025-11-08 12:00:00','2025-11-08 12:00:00'),(5,1025,'EWALLET',84924.00,'2025-11-09 12:00:00','2025-11-09 12:00:00','2025-11-09 12:00:00'),(6,1026,'COD',292380.00,'2025-11-10 12:00:00','2025-11-10 12:00:00','2025-11-10 12:00:00'),(7,1027,'BANK_TRANSFER',215000.00,'2025-11-11 12:00:00','2025-11-11 12:00:00','2025-11-11 12:00:00'),(8,1028,'CASH',274596.00,'2025-11-12 12:00:00','2025-11-12 12:00:00','2025-11-12 12:00:00'),(9,1029,'BANK_TRANSFER',457600.00,'2025-11-13 12:00:00','2025-11-13 12:00:00','2025-11-13 12:00:00'),(10,1030,'BANK_TRANSFER',611600.00,'2025-11-14 12:00:00','2025-11-14 12:00:00','2025-11-14 12:00:00'),(11,1031,'CASH',398370.00,'2025-11-15 12:00:00','2025-11-15 12:00:00','2025-11-15 12:00:00'),(12,1032,'COD',459800.00,'2025-11-16 12:00:00','2025-11-16 12:00:00','2025-11-16 12:00:00'),(13,1033,'BANK_TRANSFER',594600.00,'2025-11-17 12:00:00','2025-11-17 12:00:00','2025-11-17 12:00:00'),(14,1034,'CASH',428400.00,'2025-11-18 12:00:00','2025-11-18 12:00:00','2025-11-18 12:00:00'),(15,1035,'EWALLET',487300.00,'2025-11-19 12:00:00','2025-11-19 12:00:00','2025-11-19 12:00:00'),(16,1036,'CASH',416000.00,'2025-11-20 12:00:00','2025-11-20 12:00:00','2025-11-20 12:00:00'),(17,1037,'BANK_TRANSFER',474600.00,'2025-11-21 12:00:00','2025-11-21 12:00:00','2025-11-21 12:00:00'),(18,1038,'CASH',410960.00,'2025-11-22 12:00:00','2025-11-22 12:00:00','2025-11-22 12:00:00'),(19,1039,'CASH',0.00,NULL,'2025-11-23 12:00:00','2025-11-23 12:00:00'),(20,1040,'COD',35595.00,'2025-11-24 12:00:00','2025-11-24 12:00:00','2025-11-24 12:00:00'),(21,1041,'EWALLET',392700.00,'2025-11-25 12:00:00','2025-11-25 12:00:00','2025-11-25 12:00:00'),(22,1042,'BANK_TRANSFER',0.00,NULL,'2025-11-26 12:00:00','2025-11-26 12:00:00'),(23,1043,'COD',0.00,NULL,'2025-11-27 12:00:00','2025-11-27 12:00:00'),(24,1044,'EWALLET',0.00,NULL,'2025-11-28 12:00:00','2025-11-28 12:00:00'),(25,1045,'EWALLET',0.00,NULL,'2025-11-29 12:00:00','2025-11-29 12:00:00'),(26,1046,'COD',0.00,NULL,'2025-11-30 12:00:00','2025-11-30 12:00:00'),(27,1047,'EWALLET',280500.00,'2025-12-01 12:00:00','2025-12-01 12:00:00','2025-12-01 12:00:00'),(28,1048,'CASH',530000.00,'2025-12-02 12:00:00','2025-12-02 12:00:00','2025-12-02 12:00:00'),(29,1049,'COD',1467900.00,'2025-12-03 12:00:00','2025-12-03 12:00:00','2025-12-03 12:00:00'),(30,1050,'EWALLET',0.00,NULL,'2025-12-04 12:00:00','2025-12-04 12:00:00'),(31,1051,'CASH',1206000.00,'2025-12-05 12:00:00','2025-12-05 12:00:00','2025-12-05 12:00:00'),(32,1052,'EWALLET',764400.00,'2025-12-06 12:00:00','2025-12-06 12:00:00','2025-12-06 12:00:00'),(33,1053,'EWALLET',1903000.00,'2025-12-07 12:00:00','2025-12-07 12:00:00','2025-12-07 12:00:00'),(34,1054,'CASH',2050000.00,'2025-12-08 12:00:00','2025-12-08 12:00:00','2025-12-08 12:00:00'),(35,1055,'BANK_TRANSFER',1824900.00,'2025-12-09 12:00:00','2025-12-09 12:00:00','2025-12-09 12:00:00');
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
) ENGINE=InnoDB AUTO_INCREMENT=145 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_status_logs`
--

LOCK TABLES `order_status_logs` WRITE;
/*!40000 ALTER TABLE `order_status_logs` DISABLE KEYS */;
INSERT INTO `order_status_logs` VALUES (1,1021,NULL,'created',1,'Auto demo log','2025-11-05 10:00:00','2025-11-05 10:00:00','2025-11-05 10:00:00'),(2,1022,NULL,'created',1,'Auto demo log','2025-11-06 10:00:00','2025-11-06 10:00:00','2025-11-06 10:00:00'),(3,1023,NULL,'created',1,'Auto demo log','2025-11-07 10:00:00','2025-11-07 10:00:00','2025-11-07 10:00:00'),(4,1023,'created','confirmed',1,'Auto demo log','2025-11-07 11:00:00','2025-11-07 11:00:00','2025-11-07 11:00:00'),(5,1023,'confirmed','processing',1,'Auto demo log','2025-11-07 12:00:00','2025-11-07 12:00:00','2025-11-07 12:00:00'),(6,1024,NULL,'created',1,'Auto demo log','2025-11-08 10:00:00','2025-11-08 10:00:00','2025-11-08 10:00:00'),(7,1024,'created','confirmed',1,'Auto demo log','2025-11-08 11:00:00','2025-11-08 11:00:00','2025-11-08 11:00:00'),(8,1024,'confirmed','processing',1,'Auto demo log','2025-11-08 12:00:00','2025-11-08 12:00:00','2025-11-08 12:00:00'),(9,1025,NULL,'created',1,'Auto demo log','2025-11-09 10:00:00','2025-11-09 10:00:00','2025-11-09 10:00:00'),(10,1025,'created','confirmed',1,'Auto demo log','2025-11-09 11:00:00','2025-11-09 11:00:00','2025-11-09 11:00:00'),(11,1025,'confirmed','processing',1,'Auto demo log','2025-11-09 12:00:00','2025-11-09 12:00:00','2025-11-09 12:00:00'),(12,1026,NULL,'created',1,'Auto demo log','2025-11-10 10:00:00','2025-11-10 10:00:00','2025-11-10 10:00:00'),(13,1026,'created','confirmed',1,'Auto demo log','2025-11-10 11:00:00','2025-11-10 11:00:00','2025-11-10 11:00:00'),(14,1026,'confirmed','processing',1,'Auto demo log','2025-11-10 12:00:00','2025-11-10 12:00:00','2025-11-10 12:00:00'),(15,1026,'processing','shipping',1,'Auto demo log','2025-11-10 13:00:00','2025-11-10 13:00:00','2025-11-10 13:00:00'),(16,1027,NULL,'created',1,'Auto demo log','2025-11-11 10:00:00','2025-11-11 10:00:00','2025-11-11 10:00:00'),(17,1027,'created','confirmed',1,'Auto demo log','2025-11-11 11:00:00','2025-11-11 11:00:00','2025-11-11 11:00:00'),(18,1027,'confirmed','processing',1,'Auto demo log','2025-11-11 12:00:00','2025-11-11 12:00:00','2025-11-11 12:00:00'),(19,1027,'processing','shipping',1,'Auto demo log','2025-11-11 13:00:00','2025-11-11 13:00:00','2025-11-11 13:00:00'),(20,1028,NULL,'created',1,'Auto demo log','2025-11-12 10:00:00','2025-11-12 10:00:00','2025-11-12 10:00:00'),(21,1028,'created','confirmed',1,'Auto demo log','2025-11-12 11:00:00','2025-11-12 11:00:00','2025-11-12 11:00:00'),(22,1028,'confirmed','processing',1,'Auto demo log','2025-11-12 12:00:00','2025-11-12 12:00:00','2025-11-12 12:00:00'),(23,1028,'processing','shipping',1,'Auto demo log','2025-11-12 13:00:00','2025-11-12 13:00:00','2025-11-12 13:00:00'),(24,1029,NULL,'created',1,'Auto demo log','2025-11-13 10:00:00','2025-11-13 10:00:00','2025-11-13 10:00:00'),(25,1029,'created','confirmed',1,'Auto demo log','2025-11-13 11:00:00','2025-11-13 11:00:00','2025-11-13 11:00:00'),(26,1029,'confirmed','processing',1,'Auto demo log','2025-11-13 12:00:00','2025-11-13 12:00:00','2025-11-13 12:00:00'),(27,1029,'processing','shipping',1,'Auto demo log','2025-11-13 13:00:00','2025-11-13 13:00:00','2025-11-13 13:00:00'),(28,1029,'shipping','delivered',1,'Auto demo log','2025-11-13 14:00:00','2025-11-13 14:00:00','2025-11-13 14:00:00'),(29,1029,'delivered','completed',1,'Auto demo log','2025-11-13 15:00:00','2025-11-13 15:00:00','2025-11-13 15:00:00'),(30,1030,NULL,'created',1,'Auto demo log','2025-11-14 10:00:00','2025-11-14 10:00:00','2025-11-14 10:00:00'),(31,1030,'created','confirmed',1,'Auto demo log','2025-11-14 11:00:00','2025-11-14 11:00:00','2025-11-14 11:00:00'),(32,1030,'confirmed','processing',1,'Auto demo log','2025-11-14 12:00:00','2025-11-14 12:00:00','2025-11-14 12:00:00'),(33,1030,'processing','shipping',1,'Auto demo log','2025-11-14 13:00:00','2025-11-14 13:00:00','2025-11-14 13:00:00'),(34,1030,'shipping','delivered',1,'Auto demo log','2025-11-14 14:00:00','2025-11-14 14:00:00','2025-11-14 14:00:00'),(35,1030,'delivered','completed',1,'Auto demo log','2025-11-14 15:00:00','2025-11-14 15:00:00','2025-11-14 15:00:00'),(36,1031,NULL,'created',1,'Auto demo log','2025-11-15 10:00:00','2025-11-15 10:00:00','2025-11-15 10:00:00'),(37,1031,'created','confirmed',1,'Auto demo log','2025-11-15 11:00:00','2025-11-15 11:00:00','2025-11-15 11:00:00'),(38,1031,'confirmed','processing',1,'Auto demo log','2025-11-15 12:00:00','2025-11-15 12:00:00','2025-11-15 12:00:00'),(39,1031,'processing','shipping',1,'Auto demo log','2025-11-15 13:00:00','2025-11-15 13:00:00','2025-11-15 13:00:00'),(40,1031,'shipping','delivered',1,'Auto demo log','2025-11-15 14:00:00','2025-11-15 14:00:00','2025-11-15 14:00:00'),(41,1031,'delivered','completed',1,'Auto demo log','2025-11-15 15:00:00','2025-11-15 15:00:00','2025-11-15 15:00:00'),(42,1032,NULL,'created',1,'Auto demo log','2025-11-16 10:00:00','2025-11-16 10:00:00','2025-11-16 10:00:00'),(43,1032,'created','confirmed',1,'Auto demo log','2025-11-16 11:00:00','2025-11-16 11:00:00','2025-11-16 11:00:00'),(44,1032,'confirmed','processing',1,'Auto demo log','2025-11-16 12:00:00','2025-11-16 12:00:00','2025-11-16 12:00:00'),(45,1032,'processing','shipping',1,'Auto demo log','2025-11-16 13:00:00','2025-11-16 13:00:00','2025-11-16 13:00:00'),(46,1032,'shipping','delivered',1,'Auto demo log','2025-11-16 14:00:00','2025-11-16 14:00:00','2025-11-16 14:00:00'),(47,1032,'delivered','completed',1,'Auto demo log','2025-11-16 15:00:00','2025-11-16 15:00:00','2025-11-16 15:00:00'),(48,1033,NULL,'created',1,'Auto demo log','2025-11-17 10:00:00','2025-11-17 10:00:00','2025-11-17 10:00:00'),(49,1033,'created','confirmed',1,'Auto demo log','2025-11-17 11:00:00','2025-11-17 11:00:00','2025-11-17 11:00:00'),(50,1033,'confirmed','processing',1,'Auto demo log','2025-11-17 12:00:00','2025-11-17 12:00:00','2025-11-17 12:00:00'),(51,1033,'processing','shipping',1,'Auto demo log','2025-11-17 13:00:00','2025-11-17 13:00:00','2025-11-17 13:00:00'),(52,1033,'shipping','delivered',1,'Auto demo log','2025-11-17 14:00:00','2025-11-17 14:00:00','2025-11-17 14:00:00'),(53,1033,'delivered','completed',1,'Auto demo log','2025-11-17 15:00:00','2025-11-17 15:00:00','2025-11-17 15:00:00'),(54,1034,NULL,'created',1,'Auto demo log','2025-11-18 10:00:00','2025-11-18 10:00:00','2025-11-18 10:00:00'),(55,1034,'created','confirmed',1,'Auto demo log','2025-11-18 11:00:00','2025-11-18 11:00:00','2025-11-18 11:00:00'),(56,1034,'confirmed','processing',1,'Auto demo log','2025-11-18 12:00:00','2025-11-18 12:00:00','2025-11-18 12:00:00'),(57,1034,'processing','shipping',1,'Auto demo log','2025-11-18 13:00:00','2025-11-18 13:00:00','2025-11-18 13:00:00'),(58,1034,'shipping','delivered',1,'Auto demo log','2025-11-18 14:00:00','2025-11-18 14:00:00','2025-11-18 14:00:00'),(59,1034,'delivered','completed',1,'Auto demo log','2025-11-18 15:00:00','2025-11-18 15:00:00','2025-11-18 15:00:00'),(60,1035,NULL,'created',1,'Auto demo log','2025-11-19 10:00:00','2025-11-19 10:00:00','2025-11-19 10:00:00'),(61,1035,'created','confirmed',1,'Auto demo log','2025-11-19 11:00:00','2025-11-19 11:00:00','2025-11-19 11:00:00'),(62,1035,'confirmed','processing',1,'Auto demo log','2025-11-19 12:00:00','2025-11-19 12:00:00','2025-11-19 12:00:00'),(63,1035,'processing','shipping',1,'Auto demo log','2025-11-19 13:00:00','2025-11-19 13:00:00','2025-11-19 13:00:00'),(64,1035,'shipping','delivered',1,'Auto demo log','2025-11-19 14:00:00','2025-11-19 14:00:00','2025-11-19 14:00:00'),(65,1035,'delivered','completed',1,'Auto demo log','2025-11-19 15:00:00','2025-11-19 15:00:00','2025-11-19 15:00:00'),(66,1036,NULL,'created',1,'Auto demo log','2025-11-20 10:00:00','2025-11-20 10:00:00','2025-11-20 10:00:00'),(67,1036,'created','confirmed',1,'Auto demo log','2025-11-20 11:00:00','2025-11-20 11:00:00','2025-11-20 11:00:00'),(68,1036,'confirmed','processing',1,'Auto demo log','2025-11-20 12:00:00','2025-11-20 12:00:00','2025-11-20 12:00:00'),(69,1036,'processing','shipping',1,'Auto demo log','2025-11-20 13:00:00','2025-11-20 13:00:00','2025-11-20 13:00:00'),(70,1036,'shipping','delivered',1,'Auto demo log','2025-11-20 14:00:00','2025-11-20 14:00:00','2025-11-20 14:00:00'),(71,1036,'delivered','completed',1,'Auto demo log','2025-11-20 15:00:00','2025-11-20 15:00:00','2025-11-20 15:00:00'),(72,1037,NULL,'created',1,'Auto demo log','2025-11-21 10:00:00','2025-11-21 10:00:00','2025-11-21 10:00:00'),(73,1037,'created','confirmed',1,'Auto demo log','2025-11-21 11:00:00','2025-11-21 11:00:00','2025-11-21 11:00:00'),(74,1037,'confirmed','processing',1,'Auto demo log','2025-11-21 12:00:00','2025-11-21 12:00:00','2025-11-21 12:00:00'),(75,1037,'processing','shipping',1,'Auto demo log','2025-11-21 13:00:00','2025-11-21 13:00:00','2025-11-21 13:00:00'),(76,1037,'shipping','delivered',1,'Auto demo log','2025-11-21 14:00:00','2025-11-21 14:00:00','2025-11-21 14:00:00'),(77,1037,'delivered','completed',1,'Auto demo log','2025-11-21 15:00:00','2025-11-21 15:00:00','2025-11-21 15:00:00'),(78,1038,NULL,'created',1,'Auto demo log','2025-11-22 10:00:00','2025-11-22 10:00:00','2025-11-22 10:00:00'),(79,1038,'created','confirmed',1,'Auto demo log','2025-11-22 11:00:00','2025-11-22 11:00:00','2025-11-22 11:00:00'),(80,1038,'confirmed','processing',1,'Auto demo log','2025-11-22 12:00:00','2025-11-22 12:00:00','2025-11-22 12:00:00'),(81,1038,'processing','shipping',1,'Auto demo log','2025-11-22 13:00:00','2025-11-22 13:00:00','2025-11-22 13:00:00'),(82,1038,'shipping','delivered',1,'Auto demo log','2025-11-22 14:00:00','2025-11-22 14:00:00','2025-11-22 14:00:00'),(83,1038,'delivered','completed',1,'Auto demo log','2025-11-22 15:00:00','2025-11-22 15:00:00','2025-11-22 15:00:00'),(84,1039,NULL,'created',1,'Auto demo log','2025-11-23 10:00:00','2025-11-23 10:00:00','2025-11-23 10:00:00'),(85,1039,'created','cancelled',1,'Auto demo log','2025-11-23 11:00:00','2025-11-23 11:00:00','2025-11-23 11:00:00'),(86,1040,NULL,'created',1,'Auto demo log','2025-11-24 10:00:00','2025-11-24 10:00:00','2025-11-24 10:00:00'),(87,1040,'created','cancelled',1,'Auto demo log','2025-11-24 11:00:00','2025-11-24 11:00:00','2025-11-24 11:00:00'),(88,1041,NULL,'created',1,'Auto demo log','2025-11-25 10:00:00','2025-11-25 10:00:00','2025-11-25 10:00:00'),(89,1041,'created','confirmed',1,'Auto demo log','2025-11-25 11:00:00','2025-11-25 11:00:00','2025-11-25 11:00:00'),(90,1041,'confirmed','processing',1,'Auto demo log','2025-11-25 12:00:00','2025-11-25 12:00:00','2025-11-25 12:00:00'),(91,1041,'processing','shipping',1,'Auto demo log','2025-11-25 13:00:00','2025-11-25 13:00:00','2025-11-25 13:00:00'),(92,1042,NULL,'created',1,'Auto demo log','2025-11-26 10:00:00','2025-11-26 10:00:00','2025-11-26 10:00:00'),(93,1043,NULL,'created',1,'Auto demo log','2025-11-27 10:00:00','2025-11-27 10:00:00','2025-11-27 10:00:00'),(94,1043,'created','cancelled',1,'Auto demo log','2025-11-27 11:00:00','2025-11-27 11:00:00','2025-11-27 11:00:00'),(95,1044,NULL,'created',1,'Auto demo log','2025-11-28 10:00:00','2025-11-28 10:00:00','2025-11-28 10:00:00'),(96,1044,'created','cancelled',1,'Auto demo log','2025-11-28 11:00:00','2025-11-28 11:00:00','2025-11-28 11:00:00'),(97,1045,NULL,'created',1,'Auto demo log','2025-11-29 10:00:00','2025-11-29 10:00:00','2025-11-29 10:00:00'),(98,1046,NULL,'created',1,'Auto demo log','2025-11-30 10:00:00','2025-11-30 10:00:00','2025-11-30 10:00:00'),(99,1046,'created','cancelled',1,'Auto demo log','2025-11-30 11:00:00','2025-11-30 11:00:00','2025-11-30 11:00:00'),(100,1047,NULL,'created',1,'Auto demo log','2025-12-01 10:00:00','2025-12-01 10:00:00','2025-12-01 10:00:00'),(101,1047,'created','confirmed',1,'Auto demo log','2025-12-01 11:00:00','2025-12-01 11:00:00','2025-12-01 11:00:00'),(102,1047,'confirmed','processing',1,'Auto demo log','2025-12-01 12:00:00','2025-12-01 12:00:00','2025-12-01 12:00:00'),(103,1047,'processing','shipping',1,'Auto demo log','2025-12-01 13:00:00','2025-12-01 13:00:00','2025-12-01 13:00:00'),(104,1048,NULL,'created',1,'Auto demo log','2025-12-02 10:00:00','2025-12-02 10:00:00','2025-12-02 10:00:00'),(105,1048,'created','confirmed',1,'Auto demo log','2025-12-02 11:00:00','2025-12-02 11:00:00','2025-12-02 11:00:00'),(106,1048,'confirmed','processing',1,'Auto demo log','2025-12-02 12:00:00','2025-12-02 12:00:00','2025-12-02 12:00:00'),(107,1049,NULL,'created',1,'Auto demo log','2025-12-03 10:00:00','2025-12-03 10:00:00','2025-12-03 10:00:00'),(108,1049,'created','confirmed',1,'Auto demo log','2025-12-03 11:00:00','2025-12-03 11:00:00','2025-12-03 11:00:00'),(109,1049,'confirmed','processing',1,'Auto demo log','2025-12-03 12:00:00','2025-12-03 12:00:00','2025-12-03 12:00:00'),(110,1049,'processing','shipping',1,'Auto demo log','2025-12-03 13:00:00','2025-12-03 13:00:00','2025-12-03 13:00:00'),(111,1049,'shipping','delivered',1,'Auto demo log','2025-12-03 14:00:00','2025-12-03 14:00:00','2025-12-03 14:00:00'),(112,1049,'delivered','completed',1,'Auto demo log','2025-12-03 15:00:00','2025-12-03 15:00:00','2025-12-03 15:00:00'),(113,1050,NULL,'created',1,'Auto demo log','2025-12-04 10:00:00','2025-12-04 10:00:00','2025-12-04 10:00:00'),(114,1050,'created','cancelled',1,'Auto demo log','2025-12-04 11:00:00','2025-12-04 11:00:00','2025-12-04 11:00:00'),(115,1051,NULL,'created',1,'Auto demo log','2025-12-05 10:00:00','2025-12-05 10:00:00','2025-12-05 10:00:00'),(116,1051,'created','confirmed',1,'Auto demo log','2025-12-05 11:00:00','2025-12-05 11:00:00','2025-12-05 11:00:00'),(117,1051,'confirmed','processing',1,'Auto demo log','2025-12-05 12:00:00','2025-12-05 12:00:00','2025-12-05 12:00:00'),(118,1051,'processing','shipping',1,'Auto demo log','2025-12-05 13:00:00','2025-12-05 13:00:00','2025-12-05 13:00:00'),(119,1051,'shipping','delivered',1,'Auto demo log','2025-12-05 14:00:00','2025-12-05 14:00:00','2025-12-05 14:00:00'),(120,1051,'delivered','completed',1,'Auto demo log','2025-12-05 15:00:00','2025-12-05 15:00:00','2025-12-05 15:00:00'),(121,1052,NULL,'created',1,'Auto demo log','2025-12-06 10:00:00','2025-12-06 10:00:00','2025-12-06 10:00:00'),(122,1052,'created','confirmed',1,'Auto demo log','2025-12-06 11:00:00','2025-12-06 11:00:00','2025-12-06 11:00:00'),(123,1052,'confirmed','processing',1,'Auto demo log','2025-12-06 12:00:00','2025-12-06 12:00:00','2025-12-06 12:00:00'),(124,1052,'processing','shipping',1,'Auto demo log','2025-12-06 13:00:00','2025-12-06 13:00:00','2025-12-06 13:00:00'),(125,1052,'shipping','delivered',1,'Auto demo log','2025-12-06 14:00:00','2025-12-06 14:00:00','2025-12-06 14:00:00'),(126,1052,'delivered','completed',1,'Auto demo log','2025-12-06 15:00:00','2025-12-06 15:00:00','2025-12-06 15:00:00'),(127,1053,NULL,'created',1,'Auto demo log','2025-12-07 10:00:00','2025-12-07 10:00:00','2025-12-07 10:00:00'),(128,1053,'created','confirmed',1,'Auto demo log','2025-12-07 11:00:00','2025-12-07 11:00:00','2025-12-07 11:00:00'),(129,1053,'confirmed','processing',1,'Auto demo log','2025-12-07 12:00:00','2025-12-07 12:00:00','2025-12-07 12:00:00'),(130,1053,'processing','shipping',1,'Auto demo log','2025-12-07 13:00:00','2025-12-07 13:00:00','2025-12-07 13:00:00'),(131,1053,'shipping','delivered',1,'Auto demo log','2025-12-07 14:00:00','2025-12-07 14:00:00','2025-12-07 14:00:00'),(132,1053,'delivered','completed',1,'Auto demo log','2025-12-07 15:00:00','2025-12-07 15:00:00','2025-12-07 15:00:00'),(133,1054,NULL,'created',1,'Auto demo log','2025-12-08 10:00:00','2025-12-08 10:00:00','2025-12-08 10:00:00'),(134,1054,'created','confirmed',1,'Auto demo log','2025-12-08 11:00:00','2025-12-08 11:00:00','2025-12-08 11:00:00'),(135,1054,'confirmed','processing',1,'Auto demo log','2025-12-08 12:00:00','2025-12-08 12:00:00','2025-12-08 12:00:00'),(136,1054,'processing','shipping',1,'Auto demo log','2025-12-08 13:00:00','2025-12-08 13:00:00','2025-12-08 13:00:00'),(137,1054,'shipping','delivered',1,'Auto demo log','2025-12-08 14:00:00','2025-12-08 14:00:00','2025-12-08 14:00:00'),(138,1054,'delivered','completed',1,'Auto demo log','2025-12-08 15:00:00','2025-12-08 15:00:00','2025-12-08 15:00:00'),(139,1055,NULL,'created',1,'Auto demo log','2025-12-09 10:00:00','2025-12-09 10:00:00','2025-12-09 10:00:00'),(140,1055,'created','confirmed',1,'Auto demo log','2025-12-09 11:00:00','2025-12-09 11:00:00','2025-12-09 11:00:00'),(141,1055,'confirmed','processing',1,'Auto demo log','2025-12-09 12:00:00','2025-12-09 12:00:00','2025-12-09 12:00:00'),(142,1055,'processing','shipping',1,'Auto demo log','2025-12-09 13:00:00','2025-12-09 13:00:00','2025-12-09 13:00:00'),(143,1055,'shipping','delivered',1,'Auto demo log','2025-12-09 14:00:00','2025-12-09 14:00:00','2025-12-09 14:00:00'),(144,1055,'delivered','completed',1,'Auto demo log','2025-12-09 15:00:00','2025-12-09 15:00:00','2025-12-09 15:00:00');
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
) ENGINE=InnoDB AUTO_INCREMENT=1056 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1001,'DH251205-0001','DH251205-0001',2,NULL,NULL,NULL,'2025-12-05','online',NULL,NULL,NULL,0.00,0.00,NULL,'processing',NULL,0.00,0,0.00,0,4671000.00,0.00,0.00,4671000.00,0.00,0.00,'unpaid',0,NULL,NULL,NULL,'Địa chỉ số 2, TP.HCM',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(1002,'DH251205-0002','DH251205-0002',8,NULL,NULL,NULL,'2025-12-05','online',NULL,NULL,NULL,0.00,0.00,NULL,'completed',NULL,0.00,0,0.00,0,5054000.00,0.00,0.00,5054000.00,0.00,0.00,'paid',0,NULL,NULL,NULL,'Địa chỉ số 8, Đà Nẵng',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(1003,'DH251205-0003','DH251205-0003',4,NULL,NULL,NULL,'2025-12-05','online',NULL,NULL,NULL,0.00,0.00,NULL,'completed',NULL,0.00,0,0.00,0,5132000.00,0.00,0.00,5132000.00,0.00,0.00,'paid',0,NULL,NULL,NULL,'Địa chỉ số 4, Hải Phòng',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(1004,'DH251205-0004','DH251205-0004',8,NULL,NULL,NULL,'2025-12-05','online',NULL,NULL,NULL,0.00,0.00,NULL,'draft',NULL,0.00,0,0.00,0,390000.00,0.00,0.00,390000.00,0.00,0.00,'unpaid',0,NULL,NULL,NULL,'Địa chỉ số 8, Đà Nẵng',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(1005,'DH251205-0005','DH251205-0005',1,NULL,NULL,NULL,'2025-12-05','online',NULL,NULL,NULL,0.00,0.00,NULL,'processing',NULL,0.00,0,0.00,0,1063000.00,0.00,0.00,1063000.00,0.00,0.00,'unpaid',0,NULL,NULL,NULL,'Địa chỉ số 1, Hà Nội',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(1006,'DH251205-0006','DH251205-0006',3,NULL,NULL,NULL,'2025-12-05','online',NULL,NULL,NULL,0.00,0.00,NULL,'completed',NULL,0.00,0,0.00,0,372000.00,0.00,0.00,372000.00,0.00,0.00,'paid',0,NULL,NULL,NULL,'Địa chỉ số 3, Đà Nẵng',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(1007,'DH251205-0007','DH251205-0007',6,NULL,NULL,NULL,'2025-12-05','online',NULL,NULL,NULL,0.00,0.00,NULL,'completed',NULL,0.00,0,0.00,0,1920000.00,0.00,0.00,1920000.00,0.00,0.00,'paid',0,NULL,NULL,NULL,'Địa chỉ số 6, Hà Nội',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(1008,'DH251205-0008','DH251205-0008',3,NULL,NULL,NULL,'2025-12-05','online',NULL,NULL,NULL,0.00,0.00,NULL,'draft',NULL,0.00,0,0.00,0,2859000.00,0.00,0.00,2859000.00,0.00,0.00,'unpaid',0,NULL,NULL,NULL,'Địa chỉ số 3, Đà Nẵng',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(1009,'DH251205-0009','DH251205-0009',7,NULL,NULL,NULL,'2025-12-05','online',NULL,NULL,NULL,0.00,0.00,NULL,'processing',NULL,0.00,0,0.00,0,6050000.00,0.00,0.00,6050000.00,0.00,0.00,'unpaid',0,NULL,NULL,NULL,'Địa chỉ số 7, TP.HCM',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(1010,'DH251205-0010','DH251205-0010',2,NULL,NULL,NULL,'2025-12-05','online',NULL,NULL,NULL,0.00,0.00,NULL,'completed',NULL,0.00,0,0.00,0,4127000.00,0.00,0.00,4127000.00,0.00,0.00,'paid',0,NULL,NULL,NULL,'Địa chỉ số 2, TP.HCM',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(1011,'DH251205-0011','DH251205-0011',2,NULL,NULL,NULL,'2025-12-05','online',NULL,NULL,NULL,0.00,0.00,NULL,'completed',NULL,0.00,0,0.00,0,4189000.00,0.00,0.00,4189000.00,0.00,0.00,'paid',0,NULL,NULL,NULL,'Địa chỉ số 2, TP.HCM',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(1012,'DH251205-0012','DH251205-0012',10,NULL,NULL,NULL,'2025-12-05','online',NULL,NULL,NULL,0.00,0.00,NULL,'draft',NULL,0.00,0,0.00,0,3271000.00,0.00,0.00,3271000.00,0.00,0.00,'unpaid',0,NULL,NULL,NULL,'Địa chỉ số 10, Hà Nội',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(1013,'DH251205-0013','DH251205-0013',10,NULL,NULL,NULL,'2025-12-05','online',NULL,NULL,NULL,0.00,0.00,NULL,'processing',NULL,0.00,0,0.00,0,2870000.00,0.00,0.00,2870000.00,0.00,0.00,'unpaid',0,NULL,NULL,NULL,'Địa chỉ số 10, Hà Nội',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(1014,'DH251205-0014','DH251205-0014',10,NULL,NULL,NULL,'2025-12-05','online',NULL,NULL,NULL,0.00,0.00,NULL,'completed',NULL,0.00,0,0.00,0,3515000.00,0.00,0.00,3515000.00,0.00,0.00,'paid',0,NULL,NULL,NULL,'Địa chỉ số 10, Hà Nội',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(1015,'DH251205-0015','DH251205-0015',2,NULL,NULL,NULL,'2025-12-05','online',NULL,NULL,NULL,0.00,0.00,NULL,'completed',NULL,0.00,0,0.00,0,3529000.00,0.00,0.00,3529000.00,0.00,0.00,'paid',0,NULL,NULL,NULL,'Địa chỉ số 2, TP.HCM',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(1016,'DH251205-0016','DH251205-0016',3,NULL,NULL,NULL,'2025-12-05','online',NULL,NULL,NULL,0.00,0.00,NULL,'draft',NULL,0.00,0,0.00,0,2622000.00,0.00,0.00,2622000.00,0.00,0.00,'unpaid',0,NULL,NULL,NULL,'Địa chỉ số 3, Đà Nẵng',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(1017,'DH251205-0017','DH251205-0017',10,NULL,NULL,NULL,'2025-12-05','online',NULL,NULL,NULL,0.00,0.00,NULL,'processing',NULL,0.00,0,0.00,0,1315000.00,0.00,0.00,1315000.00,0.00,0.00,'unpaid',0,NULL,NULL,NULL,'Địa chỉ số 10, Hà Nội',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(1018,'DH251205-0018','DH251205-0018',4,NULL,NULL,NULL,'2025-12-05','online',NULL,NULL,NULL,0.00,0.00,NULL,'completed',NULL,0.00,0,0.00,0,4696000.00,0.00,0.00,4696000.00,0.00,0.00,'paid',0,NULL,NULL,NULL,'Địa chỉ số 4, Hải Phòng',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(1019,'DH251205-0019','DH251205-0019',2,NULL,NULL,NULL,'2025-12-05','online',NULL,NULL,NULL,0.00,0.00,NULL,'completed',NULL,0.00,0,0.00,0,9257000.00,0.00,0.00,9257000.00,0.00,0.00,'paid',0,NULL,NULL,NULL,'Địa chỉ số 2, TP.HCM',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(1020,'DH251205-0020','DH251205-0020',3,NULL,NULL,NULL,'2025-12-05','online',NULL,NULL,NULL,0.00,0.00,NULL,'draft',NULL,0.00,0,0.00,0,4671000.00,0.00,0.00,4671000.00,0.00,0.00,'unpaid',0,NULL,NULL,NULL,'Địa chỉ số 3, Đà Nẵng',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(1021,NULL,'DH-DEMO-001',2001,NULL,1,NULL,'2025-11-05','offline',NULL,NULL,9001,0.00,0.00,'CASH','draft',NULL,0.00,0,0.00,0,416000.00,0.00,30000.00,446000.00,0.00,446000.00,'unpaid',0,NULL,'Nguyễn Minh An','0912000001','12 Trần Hưng Đạo, Hà Nội','Hàng Bài','Hoàn Kiếm','Hà Nội',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-11-05 10:00:00','2025-11-05 10:00:00',NULL),(1022,NULL,'DH-DEMO-002',2002,NULL,2,NULL,'2025-11-06','online',NULL,NULL,9002,22600.00,0.00,'BANK_TRANSFER','draft',NULL,0.00,0,0.00,0,412000.00,0.00,40000.00,474600.00,166110.00,308490.00,'partial',0,NULL,'Trần Thu Hà','0912000002','89 Lý Thường Kiệt, Hà Nội','Cửa Nam','Hoàn Kiếm','Hà Nội',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-11-06 10:00:00','2025-11-06 10:00:00',NULL),(1023,NULL,'DH-DEMO-003',2003,NULL,3,NULL,'2025-11-07','online',NULL,NULL,9003,41060.00,0.00,'COD','processing',NULL,0.00,0,0.00,0,418000.00,42400.00,35000.00,451660.00,180664.00,270996.00,'partial',0,NULL,'Phạm Gia Bảo','0912000003','22 Nguyễn Huệ, HCM','Bến Nghé','Quận 1','Hồ Chí Minh',NULL,'2025-11-07 11:00:00','2025-11-07 12:00:00',NULL,NULL,NULL,NULL,NULL,0,'2025-11-07 10:00:00','2025-11-07 12:00:00',NULL),(1024,NULL,'DH-DEMO-004',2004,NULL,4,NULL,'2025-11-08','offline',NULL,NULL,9001,0.00,0.00,'CASH','processing',NULL,0.00,0,0.00,0,408000.00,0.00,0.00,408000.00,204000.00,204000.00,'partial',0,NULL,'Lê Hồng Nhung','0912000004','35 Hai Bà Trưng, HCM','Bến Thành','Quận 1','Hồ Chí Minh',NULL,'2025-11-08 11:00:00','2025-11-08 12:00:00',NULL,NULL,NULL,NULL,NULL,0,'2025-11-08 10:00:00','2025-11-08 12:00:00',NULL),(1025,NULL,'DH-DEMO-005',2005,NULL,5,NULL,'2025-11-09','online',NULL,NULL,9002,20220.00,0.00,'EWALLET','processing',NULL,0.00,0,0.00,0,426000.00,66600.00,45000.00,424620.00,84924.00,339696.00,'partial',0,NULL,'Vũ Hoàng Long','0912000005','15 Nguyễn Tri Phương, Đà Nẵng','Thạch Thang','Hải Châu','Đà Nẵng',NULL,'2025-11-09 11:00:00','2025-11-09 12:00:00',NULL,NULL,NULL,NULL,NULL,0,'2025-11-09 10:00:00','2025-11-09 12:00:00',NULL),(1026,NULL,'DH-DEMO-006',2006,NULL,1,NULL,'2025-11-10','online',NULL,NULL,9003,44300.00,0.00,'COD','shipping',NULL,0.00,0,0.00,0,418000.00,0.00,25000.00,487300.00,292380.00,194920.00,'partial',0,NULL,'Đặng Bích Trâm','0912000006','101 Võ Văn Tần, HCM','6','Quận 3','Hồ Chí Minh',NULL,'2025-11-10 11:00:00','2025-11-10 12:00:00','2025-11-10 13:00:00',NULL,NULL,NULL,NULL,0,'2025-11-10 10:00:00','2025-11-10 13:00:00',NULL),(1027,NULL,'DH-DEMO-007',2007,NULL,2,NULL,'2025-11-11','online',NULL,NULL,9001,0.00,0.00,'BANK_TRANSFER','shipping',NULL,0.00,0,0.00,0,410000.00,0.00,20000.00,430000.00,215000.00,215000.00,'partial',0,NULL,'Huỳnh Tuấn Kiệt','0912000007','45 Trần Phú, Nha Trang','Lộc Thọ','Nha Trang','Khánh Hòa',NULL,'2025-11-11 11:00:00','2025-11-11 12:00:00','2025-11-11 13:00:00',NULL,NULL,NULL,NULL,0,'2025-11-11 10:00:00','2025-11-11 13:00:00',NULL),(1028,NULL,'DH-DEMO-008',2008,NULL,3,NULL,'2025-11-12','offline',NULL,NULL,9002,18680.00,0.00,'CASH','shipping',NULL,0.00,0,0.00,0,416000.00,42400.00,0.00,392280.00,274596.00,117684.00,'partial',0,NULL,'Lý Thu Uyên','0912000008','68 Lê Lợi, Huế','Phú Hội','Huế','Thừa Thiên Huế',NULL,'2025-11-12 11:00:00','2025-11-12 12:00:00','2025-11-12 13:00:00',NULL,NULL,NULL,NULL,0,'2025-11-12 10:00:00','2025-11-12 13:00:00',NULL),(1029,NULL,'DH-DEMO-009',2011,NULL,1,NULL,'2025-11-13','offline',NULL,NULL,9003,41600.00,0.00,'BANK_TRANSFER','completed',NULL,0.00,0,0.00,0,416000.00,0.00,0.00,457600.00,457600.00,0.00,'paid',1,NULL,'Công ty Ánh Dương','0912000011','11 Duy Tân, Cầu Giấy, Hà Nội','Dịch Vọng','Cầu Giấy','Hà Nội',NULL,'2025-11-13 11:00:00','2025-11-13 12:00:00','2025-11-13 13:00:00','2025-11-13 14:00:00','2025-11-13 15:00:00',NULL,NULL,0,'2025-11-13 10:00:00','2025-11-13 15:00:00',NULL),(1030,NULL,'DH-DEMO-010',2012,NULL,2,NULL,'2025-11-14','online',NULL,NULL,9001,0.00,0.00,'BANK_TRANSFER','completed',NULL,0.00,0,0.00,0,624000.00,42400.00,30000.00,611600.00,611600.00,0.00,'paid',1,NULL,'CTCP Gỗ Xanh','0912000012','45 Pasteur, Quận 1, HCM','Bến Nghé','Quận 1','Hồ Chí Minh',NULL,'2025-11-14 11:00:00','2025-11-14 12:00:00','2025-11-14 13:00:00','2025-11-14 14:00:00','2025-11-14 15:00:00',NULL,NULL,0,'2025-11-14 10:00:00','2025-11-14 15:00:00',NULL),(1031,NULL,'DH-DEMO-011',2013,NULL,3,NULL,'2025-11-15','offline',NULL,NULL,9002,18970.00,0.00,'CASH','completed',NULL,0.00,0,0.00,0,426000.00,66600.00,20000.00,398370.00,398370.00,0.00,'paid',1,NULL,'Hộ KD Minh Quân','0912000013','22 Trần Phú, Nha Trang','Vạn Thạnh','Nha Trang','Khánh Hòa',NULL,'2025-11-15 11:00:00','2025-11-15 12:00:00','2025-11-15 13:00:00','2025-11-15 14:00:00','2025-11-15 15:00:00',NULL,NULL,0,'2025-11-15 10:00:00','2025-11-15 15:00:00',NULL),(1032,NULL,'DH-DEMO-012',2014,NULL,4,NULL,'2025-11-16','online',NULL,NULL,9003,41800.00,0.00,'COD','completed',NULL,0.00,0,0.00,0,418000.00,0.00,0.00,459800.00,459800.00,0.00,'paid',1,NULL,'Công ty Vận Tải Nhanh','0912000014','88 Kim Mã, Ba Đình, Hà Nội','Kim Mã','Ba Đình','Hà Nội',NULL,'2025-11-16 11:00:00','2025-11-16 12:00:00','2025-11-16 13:00:00','2025-11-16 14:00:00','2025-11-16 15:00:00',NULL,NULL,0,'2025-11-16 10:00:00','2025-11-16 15:00:00',NULL),(1033,NULL,'DH-DEMO-013',2015,NULL,5,NULL,'2025-11-17','offline',NULL,NULL,9001,0.00,0.00,'BANK_TRANSFER','completed',NULL,0.00,0,0.00,0,622000.00,42400.00,15000.00,594600.00,594600.00,0.00,'paid',1,NULL,'CTY Thiết Kế Mộc','0912000015','12 Nguyễn Trãi, Quận 5, HCM','7','Quận 5','Hồ Chí Minh',NULL,'2025-11-17 11:00:00','2025-11-17 12:00:00','2025-11-17 13:00:00','2025-11-17 14:00:00','2025-11-17 15:00:00',NULL,NULL,0,'2025-11-17 10:00:00','2025-11-17 15:00:00',NULL),(1034,NULL,'DH-DEMO-014',2016,NULL,1,NULL,'2025-11-18','offline',NULL,NULL,9002,20400.00,0.00,'CASH','completed',NULL,0.00,0,0.00,0,408000.00,0.00,0.00,428400.00,428400.00,0.00,'paid',1,NULL,'Trịnh Quốc Thái','0912000016','14 Lê Duẩn, Hà Nội','Điện Biên','Ba Đình','Hà Nội',NULL,'2025-11-18 11:00:00','2025-11-18 12:00:00','2025-11-18 13:00:00','2025-11-18 14:00:00','2025-11-18 15:00:00',NULL,NULL,0,'2025-11-18 10:00:00','2025-11-18 15:00:00',NULL),(1035,NULL,'DH-DEMO-015',2017,NULL,2,NULL,'2025-11-19','online',NULL,NULL,9003,44300.00,0.00,'EWALLET','completed',NULL,0.00,0,0.00,0,418000.00,0.00,25000.00,487300.00,487300.00,0.00,'paid',1,NULL,'Đỗ Hồng Ngọc','0912000017','7 Nguyễn Văn Cừ, Hạ Long','Bạch Đằng','Hạ Long','Quảng Ninh',NULL,'2025-11-19 11:00:00','2025-11-19 12:00:00','2025-11-19 13:00:00','2025-11-19 14:00:00','2025-11-19 15:00:00',NULL,NULL,0,'2025-11-19 10:00:00','2025-11-19 15:00:00',NULL),(1036,NULL,'DH-DEMO-016',2018,NULL,3,NULL,'2025-11-20','offline',NULL,NULL,9001,0.00,0.00,'CASH','completed',NULL,0.00,0,0.00,0,416000.00,0.00,0.00,416000.00,416000.00,0.00,'paid',1,NULL,'La Mỹ Duyên','0912000018','155 Lạch Tray, Hải Phòng','Lạch Tray','Ngô Quyền','Hải Phòng',NULL,'2025-11-20 11:00:00','2025-11-20 12:00:00','2025-11-20 13:00:00','2025-11-20 14:00:00','2025-11-20 15:00:00',NULL,NULL,0,'2025-11-20 10:00:00','2025-11-20 15:00:00',NULL),(1037,NULL,'DH-DEMO-017',2019,NULL,4,NULL,'2025-11-21','online',NULL,NULL,9002,22600.00,0.00,'BANK_TRANSFER','completed',NULL,0.00,0,0.00,0,412000.00,0.00,40000.00,474600.00,474600.00,0.00,'paid',1,NULL,'Đinh Mạnh Cường','0912000019','18 Lê Lợi, Vinh','Hưng Bình','Vinh','Nghệ An',NULL,'2025-11-21 11:00:00','2025-11-21 12:00:00','2025-11-21 13:00:00','2025-11-21 14:00:00','2025-11-21 15:00:00',NULL,NULL,0,'2025-11-21 10:00:00','2025-11-21 15:00:00',NULL),(1038,NULL,'DH-DEMO-018',2020,NULL,5,NULL,'2025-11-22','offline',NULL,NULL,9003,37360.00,0.00,'CASH','completed',NULL,0.00,0,0.00,0,416000.00,42400.00,0.00,410960.00,410960.00,0.00,'paid',1,NULL,'Phùng Thanh Mai','0912000020','3 Hùng Vương, Huế','Phú Nhuận','Huế','Thừa Thiên Huế',NULL,'2025-11-22 11:00:00','2025-11-22 12:00:00','2025-11-22 13:00:00','2025-11-22 14:00:00','2025-11-22 15:00:00',NULL,NULL,0,'2025-11-22 10:00:00','2025-11-22 15:00:00',NULL),(1039,NULL,'DH-DEMO-019',2009,NULL,6,NULL,'2025-11-23','offline',NULL,NULL,9001,0.00,0.00,'CASH','cancelled',NULL,0.00,0,0.00,0,204000.00,0.00,0.00,204000.00,0.00,204000.00,'unpaid',0,NULL,'Ngô Nhật Anh','0912000009','12 Nguyễn Văn Linh, Đà Nẵng','Nam Dương','Hải Châu','Đà Nẵng',NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-23 11:00:00','Khách đổi ý',0,'2025-11-23 10:00:00','2025-11-23 11:00:00',NULL),(1040,NULL,'DH-DEMO-020',2010,NULL,2,NULL,'2025-11-24','online',NULL,NULL,9002,11300.00,0.00,'COD','cancelled',NULL,0.00,0,0.00,0,206000.00,0.00,20000.00,237300.00,35595.00,201705.00,'partial',0,NULL,'Tạ Kim Yến','0912000010','99 Phan Chu Trinh, Đà Nẵng','Hải Châu 1','Hải Châu','Đà Nẵng',NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-24 11:00:00','Hết hàng',0,'2025-11-24 10:00:00','2025-11-24 11:00:00',NULL),(1041,NULL,'DH-DEMO-021',2003,NULL,1,NULL,'2025-11-25','offline',NULL,NULL,9003,71400.00,0.00,'EWALLET','shipping',NULL,0.00,0,0.00,0,684000.00,0.00,30000.00,785400.00,392700.00,392700.00,'partial',0,NULL,'Phạm Gia Bảo','0912000003','22 Nguyễn Huệ, HCM','Bến Nghé','Quận 1','Hồ Chí Minh','Auto-generated demo order #21','2025-11-25 11:00:00','2025-11-25 12:00:00','2025-11-25 13:00:00',NULL,NULL,NULL,NULL,0,'2025-11-25 10:00:00','2025-11-25 13:00:00',NULL),(1042,NULL,'DH-DEMO-022',2005,NULL,2,NULL,'2025-11-26','offline',NULL,NULL,9001,0.00,0.00,'BANK_TRANSFER','draft',NULL,0.00,0,0.00,0,516000.00,0.00,20000.00,536000.00,0.00,536000.00,'unpaid',0,NULL,'Vũ Hoàng Long','0912000005','15 Nguyễn Tri Phương, Đà Nẵng','Thạch Thang','Hải Châu','Đà Nẵng','Auto-generated demo order #22',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-11-26 10:00:00','2025-11-26 10:00:00',NULL),(1043,NULL,'DH-DEMO-023',2015,NULL,1,NULL,'2025-11-27','online',NULL,NULL,9002,71200.00,0.00,'COD','cancelled',NULL,0.00,0,0.00,0,1384000.00,0.00,40000.00,1495200.00,0.00,1495200.00,'unpaid',0,NULL,'CTY Thiết Kế Mộc','0912000015','12 Nguyễn Trãi, Quận 5, HCM','7','Quận 5','Hồ Chí Minh','Auto-generated demo order #23',NULL,NULL,NULL,NULL,NULL,'2025-11-27 11:00:00',NULL,0,'2025-11-27 10:00:00','2025-11-27 11:00:00',NULL),(1044,NULL,'DH-DEMO-024',2017,NULL,1,NULL,'2025-11-28','offline',NULL,NULL,9003,109400.00,0.00,'EWALLET','cancelled',NULL,0.00,0,0.00,0,1084000.00,0.00,10000.00,1203400.00,0.00,1203400.00,'unpaid',0,NULL,'Đỗ Hồng Ngọc','0912000017','7 Nguyễn Văn Cừ, Hạ Long','Bạch Đằng','Hạ Long','Quảng Ninh','Auto-generated demo order #24',NULL,NULL,NULL,NULL,NULL,'2025-11-28 11:00:00',NULL,0,'2025-11-28 10:00:00','2025-11-28 11:00:00',NULL),(1045,NULL,'DH-DEMO-025',2020,NULL,5,NULL,'2025-11-29','offline',NULL,NULL,9001,0.00,0.00,'EWALLET','draft',NULL,0.00,0,0.00,0,1910000.00,0.00,30000.00,1940000.00,0.00,1940000.00,'unpaid',0,NULL,'Phùng Thanh Mai','0912000020','3 Hùng Vương, Huế','Phú Nhuận','Huế','Thừa Thiên Huế','Auto-generated demo order #25',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2025-11-29 10:00:00','2025-11-29 10:00:00',NULL),(1046,NULL,'DH-DEMO-026',2017,NULL,3,NULL,'2025-11-30','offline',NULL,NULL,9002,14700.00,0.00,'COD','cancelled',NULL,0.00,0,0.00,0,244000.00,0.00,50000.00,308700.00,0.00,308700.00,'unpaid',0,NULL,'Đỗ Hồng Ngọc','0912000017','7 Nguyễn Văn Cừ, Hạ Long','Bạch Đằng','Hạ Long','Quảng Ninh','Auto-generated demo order #26',NULL,NULL,NULL,NULL,NULL,'2025-11-30 11:00:00',NULL,0,'2025-11-30 10:00:00','2025-11-30 11:00:00',NULL),(1047,NULL,'DH-DEMO-027',2014,NULL,5,NULL,'2025-12-01','online',NULL,NULL,9003,51000.00,0.00,'EWALLET','shipping',NULL,0.00,0,0.00,0,460000.00,0.00,50000.00,561000.00,280500.00,280500.00,'partial',0,NULL,'Công ty Vận Tải Nhanh','0912000014','88 Kim Mã, Ba Đình, Hà Nội','Kim Mã','Ba Đình','Hà Nội','Auto-generated demo order #27','2025-12-01 11:00:00','2025-12-01 12:00:00','2025-12-01 13:00:00',NULL,NULL,NULL,NULL,0,'2025-12-01 10:00:00','2025-12-01 13:00:00',NULL),(1048,NULL,'DH-DEMO-028',2011,NULL,2,NULL,'2025-12-02','offline',NULL,NULL,9001,0.00,0.00,'CASH','processing',NULL,0.00,0,0.00,0,1040000.00,0.00,20000.00,1060000.00,530000.00,530000.00,'partial',0,NULL,'Công ty Ánh Dương','0912000011','11 Duy Tân, Cầu Giấy, Hà Nội','Dịch Vọng','Cầu Giấy','Hà Nội','Auto-generated demo order #28','2025-12-02 11:00:00','2025-12-02 12:00:00',NULL,NULL,NULL,NULL,NULL,0,'2025-12-02 10:00:00','2025-12-02 12:00:00',NULL),(1049,NULL,'DH-DEMO-029',2015,NULL,1,NULL,'2025-12-03','offline',NULL,NULL,9002,69900.00,0.00,'COD','completed',NULL,0.00,0,0.00,0,1388000.00,0.00,10000.00,1467900.00,1467900.00,0.00,'paid',1,NULL,'CTY Thiết Kế Mộc','0912000015','12 Nguyễn Trãi, Quận 5, HCM','7','Quận 5','Hồ Chí Minh','Auto-generated demo order #29','2025-12-03 11:00:00','2025-12-03 12:00:00','2025-12-03 13:00:00','2025-12-03 14:00:00','2025-12-03 15:00:00',NULL,NULL,0,'2025-12-03 10:00:00','2025-12-03 15:00:00',NULL),(1050,NULL,'DH-DEMO-030',2007,NULL,3,NULL,'2025-12-04','offline',NULL,NULL,9003,49000.00,0.00,'EWALLET','cancelled',NULL,0.00,0,0.00,0,480000.00,0.00,10000.00,539000.00,0.00,539000.00,'unpaid',0,NULL,'Huỳnh Tuấn Kiệt','0912000007','45 Trần Phú, Nha Trang','Lộc Thọ','Nha Trang','Khánh Hòa','Auto-generated demo order #30',NULL,NULL,NULL,NULL,NULL,'2025-12-04 11:00:00',NULL,0,'2025-12-04 10:00:00','2025-12-04 11:00:00',NULL),(1051,NULL,'DH-DEMO-031',2012,NULL,5,NULL,'2025-12-05','online',NULL,NULL,9001,0.00,0.00,'CASH','completed',NULL,0.00,0,0.00,0,1156000.00,0.00,50000.00,1206000.00,1206000.00,0.00,'paid',1,NULL,'CTCP Gỗ Xanh','0912000012','45 Pasteur, Quận 1, HCM','Bến Nghé','Quận 1','Hồ Chí Minh','Auto-generated demo order #31','2025-12-05 11:00:00','2025-12-05 12:00:00','2025-12-05 13:00:00','2025-12-05 14:00:00','2025-12-05 15:00:00',NULL,NULL,0,'2025-12-05 10:00:00','2025-12-05 15:00:00',NULL),(1052,NULL,'DH-DEMO-032',2013,NULL,5,NULL,'2025-12-06','online',NULL,NULL,9002,36400.00,0.00,'EWALLET','completed',NULL,0.00,0,0.00,0,688000.00,0.00,40000.00,764400.00,764400.00,0.00,'paid',1,NULL,'Hộ KD Minh Quân','0912000013','22 Trần Phú, Nha Trang','Vạn Thạnh','Nha Trang','Khánh Hòa','Auto-generated demo order #32','2025-12-06 11:00:00','2025-12-06 12:00:00','2025-12-06 13:00:00','2025-12-06 14:00:00','2025-12-06 15:00:00',NULL,NULL,0,'2025-12-06 10:00:00','2025-12-06 15:00:00',NULL),(1053,NULL,'DH-DEMO-033',2014,NULL,1,NULL,'2025-12-07','offline',NULL,NULL,9003,173000.00,0.00,'EWALLET','completed',NULL,0.00,0,0.00,0,1730000.00,0.00,0.00,1903000.00,1903000.00,0.00,'paid',1,NULL,'Công ty Vận Tải Nhanh','0912000014','88 Kim Mã, Ba Đình, Hà Nội','Kim Mã','Ba Đình','Hà Nội','Auto-generated demo order #33','2025-12-07 11:00:00','2025-12-07 12:00:00','2025-12-07 13:00:00','2025-12-07 14:00:00','2025-12-07 15:00:00',NULL,NULL,0,'2025-12-07 10:00:00','2025-12-07 15:00:00',NULL),(1054,NULL,'DH-DEMO-034',2011,NULL,4,NULL,'2025-12-08','online',NULL,NULL,9001,0.00,0.00,'CASH','completed',NULL,0.00,0,0.00,0,2000000.00,0.00,50000.00,2050000.00,2050000.00,0.00,'paid',1,NULL,'Công ty Ánh Dương','0912000011','11 Duy Tân, Cầu Giấy, Hà Nội','Dịch Vọng','Cầu Giấy','Hà Nội','Auto-generated demo order #34','2025-12-08 11:00:00','2025-12-08 12:00:00','2025-12-08 13:00:00','2025-12-08 14:00:00','2025-12-08 15:00:00',NULL,NULL,0,'2025-12-08 10:00:00','2025-12-08 15:00:00',NULL),(1055,NULL,'DH-DEMO-035',2013,NULL,4,NULL,'2025-12-09','online',NULL,NULL,9002,86900.00,0.00,'BANK_TRANSFER','completed',NULL,0.00,0,0.00,0,1698000.00,0.00,40000.00,1824900.00,1824900.00,0.00,'paid',1,NULL,'Hộ KD Minh Quân','0912000013','22 Trần Phú, Nha Trang','Vạn Thạnh','Nha Trang','Khánh Hòa','Auto-generated demo order #35','2025-12-09 11:00:00','2025-12-09 12:00:00','2025-12-09 13:00:00','2025-12-09 14:00:00','2025-12-09 15:00:00',NULL,NULL,0,'2025-12-09 10:00:00','2025-12-09 15:00:00',NULL);
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
INSERT INTO `organizations` VALUES (1,'ORG-001','Tổ chức Demo 1',NULL,'0303030303',NULL,NULL,NULL,'active',NULL,NULL,'2025-12-05 08:22:42','2025-12-05 08:22:42',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partners`
--

LOCK TABLES `partners` WRITE;
/*!40000 ALTER TABLE `partners` DISABLE KEYS */;
INSERT INTO `partners` VALUES (1,'NCC0001','Công ty May Việt Tiến','supplier','Phòng Kinh Doanh','contact@viettien.com.vn','0243111222','Hà Nội','Hà Nội',NULL,NULL,NULL,0.00,0.00,0.00,'active','2025-12-05 09:02:22','2025-12-05 09:02:22',NULL),(2,'NCC0002','Công ty Giày Thượng Đình','supplier','Phòng Kinh Doanh','sales@thuongdinh.com','0243333444','Hà Nội','Hà Nội',NULL,NULL,NULL,0.00,0.00,0.00,'active','2025-12-05 09:02:22','2025-12-05 09:02:22',NULL),(3,'NCC0003','NPP Thời Trang Owen','supplier','Phòng Kinh Doanh','support@owen.vn','0285555666','TP.HCM','TP.HCM',NULL,NULL,NULL,0.00,0.00,0.00,'active','2025-12-05 09:02:22','2025-12-05 09:02:22',NULL),(4,'SUP-004','Nhà Máy Dệt May Tân Tiến','supplier',NULL,'sales@tantien.vn','0236-3555-6666','321 Lê Duẩn, Đà Nẵng',NULL,'0444555666',NULL,NULL,0.00,0.00,0.00,'active','2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(5,'SUP-005','Xưởng Gia Công Đồng Phát','supplier',NULL,'dongphat@workshop.vn','0292-3444-5555','654 Đường 3/2, Cần Thơ',NULL,'0777888999',NULL,NULL,0.00,0.00,0.00,'active','2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(6,'SUP-006','Công ty TNHH Vải Cao Cấp','supplier',NULL,'premium@fabric.vn','024-3333-4444','987 Trần Hưng Đạo, Hà Nội',NULL,'0222333444',NULL,NULL,0.00,0.00,0.00,'active','2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(7,'SUP-007','Nhà Cung Cấp Phụ Liệu Minh Anh','supplier',NULL,'minhanh@materials.vn','028-3222-3333','147 Lê Lợi, HCM',NULL,'0555666777',NULL,NULL,0.00,0.00,0.00,'active','2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(8,'SUP-008','Xưởng Thêu Ren Hoa Mai','supplier',NULL,'hoamai@embroidery.vn','0225-3111-2222','258 Lạch Tray, Hải Phòng',NULL,'0888999000',NULL,NULL,0.00,0.00,0.00,'active','2025-12-05 18:19:13','2025-12-05 18:19:13',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_methods`
--

LOCK TABLES `payment_methods` WRITE;
/*!40000 ALTER TABLE `payment_methods` DISABLE KEYS */;
INSERT INTO `payment_methods` VALUES (1,'CASH','Tiền mặt',NULL,'Thanh toán bằng tiền mặt tại cửa hàng',1,1,'2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(2,'BANK_TRANSFER','Chuyển khoản ngân hàng',NULL,'Chuyển khoản qua tài khoản ngân hàng',1,2,'2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(3,'CARD','Thẻ tín dụng/ghi nợ',NULL,'Thanh toán bằng thẻ Visa/Mastercard/JCB',1,3,'2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(4,'COD','Thu hộ (COD)',NULL,'Thanh toán khi nhận hàng. Phí COD: 15,000đ',1,4,'2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(5,'EWALLET','Ví điện tử',NULL,'Thanh toán qua MoMo, ZaloPay, VNPay',1,5,'2025-12-05 18:19:13','2025-12-05 18:19:13',NULL);
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
INSERT INTO `permissions` VALUES (1,'users.view','Xem người dùng',NULL,'users','admin','api','2025-12-05 18:19:13',NULL,NULL),(2,'users.manage','Quản lý người dùng',NULL,'users','admin','api','2025-12-05 18:19:13',NULL,NULL),(3,'products.view','Xem sản phẩm',NULL,'products','catalog','api','2025-12-05 18:19:13',NULL,NULL),(4,'products.manage','Quản lý sản phẩm',NULL,'products','catalog','api','2025-12-05 18:19:13',NULL,NULL);
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `positions`
--

LOCK TABLES `positions` WRITE;
/*!40000 ALTER TABLE `positions` DISABLE KEYS */;
INSERT INTO `positions` VALUES (1,'Giám Đốc','CEO',1,1,'2025-12-05 18:19:13','2025-12-05 18:19:13'),(2,'Trưởng Phòng','MGR',2,2,'2025-12-05 18:19:13','2025-12-05 18:19:13'),(3,'Nhân Viên','STAFF',2,3,'2025-12-05 18:19:13','2025-12-05 18:19:13'),(4,'Kế Toán Trưởng','ACC_MGR',3,2,'2025-12-05 18:19:13','2025-12-05 18:19:13'),(5,'Thủ Kho','WH_KEEPER',5,3,'2025-12-05 18:19:13','2025-12-05 18:19:13');
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
) ENGINE=InnoDB AUTO_INCREMENT=75 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `price_list_items`
--

LOCK TABLES `price_list_items` WRITE;
/*!40000 ALTER TABLE `price_list_items` DISABLE KEYS */;
INSERT INTO `price_list_items` VALUES (69,1,501,NULL,6000000.00,20.00,0.00,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(70,1,502,NULL,1560000.00,20.00,0.00,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(71,1,503,NULL,1200000.00,20.00,0.00,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(72,2,503,NULL,975000.00,35.00,0.00,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(73,3,501,50102,5250000.00,30.00,0.00,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(74,8,524,NULL,273000.00,0.00,0.00,'2025-12-05 18:20:11','2025-12-05 18:20:11',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `price_lists`
--

LOCK TABLES `price_lists` WRITE;
/*!40000 ALTER TABLE `price_lists` DISABLE KEYS */;
INSERT INTO `price_lists` VALUES (1,'Bảng giá chung','default',NULL,NULL,'2025-12-05',NULL,0,1,1,NULL,NULL,0,'none',NULL,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(2,'Bảng giá VIP (Giảm 10%)','normal',NULL,NULL,'2025-12-05',NULL,0,1,0,NULL,NULL,0,'none',NULL,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(3,'aaaaaa','custom',NULL,NULL,'2025-12-05','2026-12-05',0,1,0,NULL,NULL,0,'thousand','{\"allow_add_items_not_in_list\": true, \"warn_when_add_items_not_in_list\": false}','2025-12-05 11:57:06','2025-12-05 11:57:06',NULL),(4,'test','custom',NULL,NULL,'2025-12-05','2026-12-05',0,1,0,NULL,NULL,0,'thousand','{\"scope_branch\": \"all\", \"scope_creator\": \"all\", \"specific_branches\": [], \"specific_creators\": [], \"scope_customer_group\": \"all\", \"specific_customer_groups\": [], \"allow_add_items_not_in_list\": true, \"warn_when_add_items_not_in_list\": true}','2025-12-05 13:38:04','2025-12-05 13:38:04',NULL),(5,'test2','custom',NULL,NULL,'2025-12-05','2026-12-05',0,1,0,NULL,NULL,0,'thousand','{\"specific_branches\": [], \"specific_creators\": [], \"specific_customer_groups\": [], \"allow_add_items_not_in_list\": true, \"warn_when_add_items_not_in_list\": false}','2025-12-05 13:42:57','2025-12-05 13:42:57',NULL),(6,'test 3 ','custom',NULL,NULL,'2025-12-05','2026-12-05',0,1,0,NULL,NULL,0,'thousand','{\"specific_branches\": [], \"specific_creators\": [], \"specific_customer_groups\": [], \"allow_add_items_not_in_list\": true, \"warn_when_add_items_not_in_list\": false}','2025-12-05 13:46:56','2025-12-05 13:46:56',NULL),(7,'test 6','custom',NULL,NULL,'2025-12-05','2026-12-05',0,1,0,NULL,NULL,0,'thousand','{\"specific_branches\": [], \"specific_creators\": [], \"specific_customer_groups\": [], \"allow_add_items_not_in_list\": true, \"warn_when_add_items_not_in_list\": false}','2025-12-05 13:51:27','2025-12-05 13:51:27','2025-12-05 18:27:12'),(8,'test 11','custom',NULL,NULL,'2025-12-05','2026-12-05',0,1,0,NULL,1,0,'thousand','{\"formula_config\": {\"base\": 1, \"unit\": \"%\", \"value\": 10, \"operator\": \"+\", \"rounding\": \"thousand\"}, \"specific_branches\": [], \"specific_creators\": [], \"specific_customer_groups\": [], \"allow_add_items_not_in_list\": true, \"warn_when_add_items_not_in_list\": false}','2025-12-05 13:56:05','2025-12-05 13:56:05',NULL),(9,'TEST 12','custom',NULL,NULL,'2025-12-05','2026-12-05',0,1,0,NULL,1,0,'thousand','{\"scope_branch\": \"all\", \"scope_creator\": \"all\", \"formula_config\": {\"base\": 1, \"unit\": \"%\", \"value\": 10, \"operator\": \"+\", \"rounding\": \"thousand\"}, \"specific_branches\": [], \"specific_creators\": [], \"scope_customer_group\": \"all\", \"specific_customer_groups\": [], \"allow_add_items_not_in_list\": true, \"warn_when_add_items_not_in_list\": false}','2025-12-05 14:00:33','2025-12-05 14:00:33','2025-12-05 18:31:02');
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
INSERT INTO `product_attribute_options` VALUES (301,201,'Đen','#000000',1,'active','2025-12-05 18:19:13',NULL,NULL),(302,201,'Nâu','#5b3a29',2,'active','2025-12-05 18:19:13',NULL,NULL),(303,202,'M',NULL,1,'active','2025-12-05 18:19:13',NULL,NULL),(304,202,'L',NULL,2,'active','2025-12-05 18:19:13',NULL,NULL),(305,201,'Xanh rêu','#556b2f',3,'active','2025-12-05 18:19:14',NULL,NULL);
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
INSERT INTO `product_attributes` VALUES (201,'Màu sắc','mau-sac','color','select',0,1,1,'active',1,'2025-12-05 18:19:13',NULL,NULL),(202,'Kích thước','size','size','select',0,1,2,'active',1,'2025-12-05 18:19:13',NULL,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=104 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_categories`
--

LOCK TABLES `product_categories` WRITE;
/*!40000 ALTER TABLE `product_categories` DISABLE KEYS */;
INSERT INTO `product_categories` VALUES (10,NULL,0,1,0,'CAT_MEN','Thời trang Nam','thoi-trang-nam',NULL,NULL,1,'active','2025-12-05 09:02:22','2025-12-05 09:02:22',NULL),(11,10,0,2,0,'CAT_MEN_TSHIRT','Áo Thun Nam','ao-thun-nam',NULL,NULL,4,'active','2025-12-05 09:02:22','2025-12-05 09:02:22',NULL),(12,10,0,2,0,'CAT_MEN_SHIRT','Áo Sơ mi Nam','ao-somi-nam',NULL,NULL,5,'active','2025-12-05 09:02:22','2025-12-05 09:02:22',NULL),(13,10,0,2,0,'CAT_MEN_JEANS','Quần Jeans Nam','quan-jeans-nam',NULL,NULL,6,'active','2025-12-05 09:02:22','2025-12-05 09:02:22',NULL),(20,NULL,0,1,0,'CAT_WOMEN','Thời trang Nữ','thoi-trang-nu',NULL,NULL,2,'active','2025-12-05 09:02:22','2025-12-05 09:02:22',NULL),(21,20,0,2,0,'CAT_WOMEN_DRESS','Đầm Váy','dam-vay',NULL,NULL,7,'active','2025-12-05 09:02:22','2025-12-05 09:02:22',NULL),(22,20,0,2,0,'CAT_WOMEN_TOP','Áo Kiểu','ao-kieu',NULL,NULL,8,'active','2025-12-05 09:02:22','2025-12-05 09:02:22',NULL),(30,NULL,0,1,0,'CAT_ACCESSORIES','Phụ kiện','phu-kien',NULL,NULL,3,'active','2025-12-05 09:02:22','2025-12-05 09:02:22',NULL),(31,30,0,2,0,'CAT_BAGS','Túi xách','tui-xach',NULL,NULL,9,'active','2025-12-05 09:02:22','2025-12-05 09:02:22',NULL),(32,30,0,2,0,'CAT_SHOES','Giày dép','giay-dep',NULL,NULL,10,'active','2025-12-05 09:02:22','2025-12-05 09:02:22',NULL),(33,30,0,2,0,'CAT_WATCHES','Đồng hồ','dong-ho',NULL,NULL,11,'active','2025-12-05 09:02:22','2025-12-05 09:02:22',NULL),(101,NULL,0,1,0,'TUI','Túi xách','tui-xach',NULL,NULL,1,'active','2025-12-05 18:19:13',NULL,NULL),(102,NULL,0,1,0,'VI','Ví','vi',NULL,NULL,2,'active','2025-12-05 18:19:13',NULL,NULL),(103,101,0,2,0,'TUI-DA','Túi da','tui-da',NULL,NULL,1,'active','2025-12-05 18:19:13',NULL,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_category_links`
--

LOCK TABLES `product_category_links` WRITE;
/*!40000 ALTER TABLE `product_category_links` DISABLE KEYS */;
INSERT INTO `product_category_links` VALUES (21,501,101,'2025-12-05 18:19:14'),(22,502,101,'2025-12-05 18:19:14'),(23,503,101,'2025-12-05 18:19:14'),(24,504,101,'2025-12-05 18:19:14'),(25,505,101,'2025-12-05 18:19:14'),(26,506,101,'2025-12-05 18:19:14'),(27,507,101,'2025-12-05 18:19:14'),(28,508,101,'2025-12-05 18:19:14'),(29,509,101,'2025-12-05 18:19:14'),(30,510,101,'2025-12-05 18:19:14'),(31,511,101,'2025-12-05 18:19:14'),(32,512,101,'2025-12-05 18:19:14'),(33,513,101,'2025-12-05 18:19:14'),(34,514,101,'2025-12-05 18:19:14'),(35,515,101,'2025-12-05 18:19:14'),(36,516,101,'2025-12-05 18:19:14'),(37,517,101,'2025-12-05 18:19:14'),(38,518,101,'2025-12-05 18:19:14'),(39,519,101,'2025-12-05 18:19:14'),(40,520,101,'2025-12-05 18:19:14'),(41,521,101,'2025-12-05 18:19:14'),(42,522,101,'2025-12-05 18:19:14'),(43,523,101,'2025-12-05 18:19:14'),(44,524,101,'2025-12-05 18:19:14'),(45,525,101,'2025-12-05 18:19:14'),(46,526,101,'2025-12-05 18:19:14'),(47,527,101,'2025-12-05 18:19:14'),(48,528,101,'2025-12-05 18:19:14'),(49,529,101,'2025-12-05 18:19:14'),(50,530,101,'2025-12-05 18:19:14');
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
  `channel` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `channel_product_id` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'disconnected',
  `sync_status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'synced',
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
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_images`
--

LOCK TABLES `product_images` WRITE;
/*!40000 ALTER TABLE `product_images` DISABLE KEYS */;
INSERT INTO `product_images` VALUES (21,501,50101,'/uploads/products/PROD-001-V1.jpg','https://picsum.photos/seed/PROD-001-V1/500/500',1,1,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(22,501,50102,'/uploads/products/PROD-001-V2.jpg','https://picsum.photos/seed/PROD-001-V2/500/500',0,2,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(23,502,50201,'/uploads/products/PROD-002-V1.jpg','https://picsum.photos/seed/PROD-002-V1/500/500',1,1,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(24,502,50202,'/uploads/products/PROD-002-V2.jpg','https://picsum.photos/seed/PROD-002-V2/500/500',0,2,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(25,503,50301,'/uploads/products/PROD-003-V1.jpg','https://picsum.photos/seed/PROD-003-V1/500/500',1,1,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(26,503,50302,'/uploads/products/PROD-003-V2.jpg','https://picsum.photos/seed/PROD-003-V2/500/500',0,2,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(27,504,50401,'/uploads/products/PROD-004-V1.jpg','https://picsum.photos/seed/PROD-004-V1/500/500',1,1,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(28,504,50402,'/uploads/products/PROD-004-V2.jpg','https://picsum.photos/seed/PROD-004-V2/500/500',0,2,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(29,505,50501,'/uploads/products/PROD-005-V1.jpg','https://picsum.photos/seed/PROD-005-V1/500/500',1,1,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(30,505,50502,'/uploads/products/PROD-005-V2.jpg','https://picsum.photos/seed/PROD-005-V2/500/500',0,2,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(31,506,50601,'/uploads/products/PROD-006-V1.jpg','https://picsum.photos/seed/PROD-006-V1/500/500',1,1,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(32,506,50602,'/uploads/products/PROD-006-V2.jpg','https://picsum.photos/seed/PROD-006-V2/500/500',0,2,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(33,507,50701,'/uploads/products/PROD-007-V1.jpg','https://picsum.photos/seed/PROD-007-V1/500/500',1,1,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(34,507,50702,'/uploads/products/PROD-007-V2.jpg','https://picsum.photos/seed/PROD-007-V2/500/500',0,2,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(35,508,50801,'/uploads/products/PROD-008-V1.jpg','https://picsum.photos/seed/PROD-008-V1/500/500',1,1,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(36,508,50802,'/uploads/products/PROD-008-V2.jpg','https://picsum.photos/seed/PROD-008-V2/500/500',0,2,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(37,509,50901,'/uploads/products/PROD-009-V1.jpg','https://picsum.photos/seed/PROD-009-V1/500/500',1,1,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(38,509,50902,'/uploads/products/PROD-009-V2.jpg','https://picsum.photos/seed/PROD-009-V2/500/500',0,2,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(39,510,51001,'/uploads/products/PROD-010-V1.jpg','https://picsum.photos/seed/PROD-010-V1/500/500',1,1,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(40,510,51002,'/uploads/products/PROD-010-V2.jpg','https://picsum.photos/seed/PROD-010-V2/500/500',0,2,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(41,511,51101,'/uploads/products/PROD-011-V1.jpg','https://picsum.photos/seed/PROD-011-V1/500/500',1,1,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(42,511,51102,'/uploads/products/PROD-011-V2.jpg','https://picsum.photos/seed/PROD-011-V2/500/500',0,2,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(43,512,51201,'/uploads/products/PROD-012-V1.jpg','https://picsum.photos/seed/PROD-012-V1/500/500',1,1,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(44,512,51202,'/uploads/products/PROD-012-V2.jpg','https://picsum.photos/seed/PROD-012-V2/500/500',0,2,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(45,513,51301,'/uploads/products/PROD-013-V1.jpg','https://picsum.photos/seed/PROD-013-V1/500/500',1,1,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(46,513,51302,'/uploads/products/PROD-013-V2.jpg','https://picsum.photos/seed/PROD-013-V2/500/500',0,2,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(47,514,51401,'/uploads/products/PROD-014-V1.jpg','https://picsum.photos/seed/PROD-014-V1/500/500',1,1,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(48,514,51402,'/uploads/products/PROD-014-V2.jpg','https://picsum.photos/seed/PROD-014-V2/500/500',0,2,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(49,515,51501,'/uploads/products/PROD-015-V1.jpg','https://picsum.photos/seed/PROD-015-V1/500/500',1,1,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(50,515,51502,'/uploads/products/PROD-015-V2.jpg','https://picsum.photos/seed/PROD-015-V2/500/500',0,2,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(51,516,51601,'/uploads/products/PROD-016-V1.jpg','https://picsum.photos/seed/PROD-016-V1/500/500',1,1,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(52,516,51602,'/uploads/products/PROD-016-V2.jpg','https://picsum.photos/seed/PROD-016-V2/500/500',0,2,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(53,517,51701,'/uploads/products/PROD-017-V1.jpg','https://picsum.photos/seed/PROD-017-V1/500/500',1,1,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(54,517,51702,'/uploads/products/PROD-017-V2.jpg','https://picsum.photos/seed/PROD-017-V2/500/500',0,2,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(55,518,51801,'/uploads/products/PROD-018-V1.jpg','https://picsum.photos/seed/PROD-018-V1/500/500',1,1,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(56,518,51802,'/uploads/products/PROD-018-V2.jpg','https://picsum.photos/seed/PROD-018-V2/500/500',0,2,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(57,519,51901,'/uploads/products/PROD-019-V1.jpg','https://picsum.photos/seed/PROD-019-V1/500/500',1,1,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(58,519,51902,'/uploads/products/PROD-019-V2.jpg','https://picsum.photos/seed/PROD-019-V2/500/500',0,2,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(59,520,52001,'/uploads/products/PROD-020-V1.jpg','https://picsum.photos/seed/PROD-020-V1/500/500',1,1,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(60,520,52002,'/uploads/products/PROD-020-V2.jpg','https://picsum.photos/seed/PROD-020-V2/500/500',0,2,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(61,521,52101,'/uploads/products/PROD-021-V1.jpg','https://picsum.photos/seed/PROD-021-V1/500/500',1,1,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(62,521,52102,'/uploads/products/PROD-021-V2.jpg','https://picsum.photos/seed/PROD-021-V2/500/500',0,2,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(63,522,52201,'/uploads/products/PROD-022-V1.jpg','https://picsum.photos/seed/PROD-022-V1/500/500',1,1,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(64,522,52202,'/uploads/products/PROD-022-V2.jpg','https://picsum.photos/seed/PROD-022-V2/500/500',0,2,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(65,523,52301,'/uploads/products/PROD-023-V1.jpg','https://picsum.photos/seed/PROD-023-V1/500/500',1,1,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(66,523,52302,'/uploads/products/PROD-023-V2.jpg','https://picsum.photos/seed/PROD-023-V2/500/500',0,2,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(67,524,52401,'/uploads/products/PROD-024-V1.jpg','https://picsum.photos/seed/PROD-024-V1/500/500',1,1,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(68,524,52402,'/uploads/products/PROD-024-V2.jpg','https://picsum.photos/seed/PROD-024-V2/500/500',0,2,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(69,525,52501,'/uploads/products/PROD-025-V1.jpg','https://picsum.photos/seed/PROD-025-V1/500/500',1,1,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(70,525,52502,'/uploads/products/PROD-025-V2.jpg','https://picsum.photos/seed/PROD-025-V2/500/500',0,2,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(71,526,52601,'/uploads/products/PROD-026-V1.jpg','https://picsum.photos/seed/PROD-026-V1/500/500',1,1,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(72,526,52602,'/uploads/products/PROD-026-V2.jpg','https://picsum.photos/seed/PROD-026-V2/500/500',0,2,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(73,527,52701,'/uploads/products/PROD-027-V1.jpg','https://picsum.photos/seed/PROD-027-V1/500/500',1,1,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(74,527,52702,'/uploads/products/PROD-027-V2.jpg','https://picsum.photos/seed/PROD-027-V2/500/500',0,2,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(75,528,52801,'/uploads/products/PROD-028-V1.jpg','https://picsum.photos/seed/PROD-028-V1/500/500',1,1,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(76,528,52802,'/uploads/products/PROD-028-V2.jpg','https://picsum.photos/seed/PROD-028-V2/500/500',0,2,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(77,529,52901,'/uploads/products/PROD-029-V1.jpg','https://picsum.photos/seed/PROD-029-V1/500/500',1,1,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(78,529,52902,'/uploads/products/PROD-029-V2.jpg','https://picsum.photos/seed/PROD-029-V2/500/500',0,2,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(79,530,53001,'/uploads/products/PROD-030-V1.jpg','https://picsum.photos/seed/PROD-030-V1/500/500',1,1,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(80,530,53002,'/uploads/products/PROD-030-V2.jpg','https://picsum.photos/seed/PROD-030-V2/500/500',0,2,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14');
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
  CONSTRAINT `fk_product_prices_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_product_prices_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants_v2` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=119 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_prices`
--

LOCK TABLES `product_prices` WRITE;
/*!40000 ALTER TABLE `product_prices` DISABLE KEYS */;
INSERT INTO `product_prices` VALUES (29,501,NULL,NULL,'retail',202000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(30,501,50101,NULL,'retail',212000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(31,501,50102,NULL,'retail',222000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(32,502,NULL,NULL,'retail',204000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(33,502,50201,NULL,'retail',214000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(34,502,50202,NULL,'retail',224000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(35,503,NULL,NULL,'retail',206000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(36,503,50301,NULL,'retail',216000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(37,503,50302,NULL,'retail',226000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(38,504,NULL,NULL,'retail',208000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(39,504,50401,NULL,'retail',218000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(40,504,50402,NULL,'retail',228000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(41,505,NULL,NULL,'retail',210000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(42,505,50501,NULL,'retail',220000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(43,505,50502,NULL,'retail',230000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(44,506,NULL,NULL,'retail',212000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(45,506,50601,NULL,'retail',222000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(46,506,50602,NULL,'retail',232000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(47,507,NULL,NULL,'retail',214000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(48,507,50701,NULL,'retail',224000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(49,507,50702,NULL,'retail',234000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(50,508,NULL,NULL,'retail',216000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(51,508,50801,NULL,'retail',226000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(52,508,50802,NULL,'retail',236000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(53,509,NULL,NULL,'retail',218000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(54,509,50901,NULL,'retail',228000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(55,509,50902,NULL,'retail',238000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(56,510,NULL,NULL,'retail',220000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(57,510,51001,NULL,'retail',230000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(58,510,51002,NULL,'retail',240000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(59,511,NULL,NULL,'retail',222000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(60,511,51101,NULL,'retail',232000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(61,511,51102,NULL,'retail',242000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(62,512,NULL,NULL,'retail',224000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(63,512,51201,NULL,'retail',234000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(64,512,51202,NULL,'retail',244000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(65,513,NULL,NULL,'retail',226000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(66,513,51301,NULL,'retail',236000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(67,513,51302,NULL,'retail',246000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(68,514,NULL,NULL,'retail',228000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(69,514,51401,NULL,'retail',238000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(70,514,51402,NULL,'retail',248000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(71,515,NULL,NULL,'retail',230000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(72,515,51501,NULL,'retail',240000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(73,515,51502,NULL,'retail',250000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(74,516,NULL,NULL,'retail',232000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(75,516,51601,NULL,'retail',242000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(76,516,51602,NULL,'retail',252000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(77,517,NULL,NULL,'retail',234000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(78,517,51701,NULL,'retail',244000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(79,517,51702,NULL,'retail',254000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(80,518,NULL,NULL,'retail',236000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(81,518,51801,NULL,'retail',246000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(82,518,51802,NULL,'retail',256000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(83,519,NULL,NULL,'retail',238000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(84,519,51901,NULL,'retail',248000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(85,519,51902,NULL,'retail',258000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(86,520,NULL,NULL,'retail',240000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(87,520,52001,NULL,'retail',250000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(88,520,52002,NULL,'retail',260000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(89,521,NULL,NULL,'retail',242000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(90,521,52101,NULL,'retail',252000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(91,521,52102,NULL,'retail',262000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(92,522,NULL,NULL,'retail',244000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(93,522,52201,NULL,'retail',254000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(94,522,52202,NULL,'retail',264000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(95,523,NULL,NULL,'retail',246000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(96,523,52301,NULL,'retail',256000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(97,523,52302,NULL,'retail',266000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(98,524,NULL,NULL,'retail',248000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(99,524,52401,NULL,'retail',258000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(100,524,52402,NULL,'retail',268000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(101,525,NULL,NULL,'retail',250000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(102,525,52501,NULL,'retail',260000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(103,525,52502,NULL,'retail',270000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(104,526,NULL,NULL,'retail',252000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(105,526,52601,NULL,'retail',262000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(106,526,52602,NULL,'retail',272000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(107,527,NULL,NULL,'retail',254000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(108,527,52701,NULL,'retail',264000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(109,527,52702,NULL,'retail',274000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(110,528,NULL,NULL,'retail',256000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(111,528,52801,NULL,'retail',266000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(112,528,52802,NULL,'retail',276000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(113,529,NULL,NULL,'retail',258000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(114,529,52901,NULL,'retail',268000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(115,529,52902,NULL,'retail',278000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(116,530,NULL,NULL,'retail',260000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(117,530,53001,NULL,'retail',270000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(118,530,53002,NULL,'retail',280000.00,1,NULL,NULL,'active',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14');
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
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_stock_by_branch`
--

LOCK TABLES `product_stock_by_branch` WRITE;
/*!40000 ALTER TABLE `product_stock_by_branch` DISABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=53003 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_variants_v2`
--

LOCK TABLES `product_variants_v2` WRITE;
/*!40000 ALTER TABLE `product_variants_v2` DISABLE KEYS */;
INSERT INTO `product_variants_v2` VALUES (50101,501,'Variant 1','PROD-001-V1','PROD-001-V1','PROD-001-V1-BAR',212000.00,101000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"M\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(50102,501,'Variant 2','PROD-001-V2','PROD-001-V2','PROD-001-V2-BAR',222000.00,101000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"L\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(50201,502,'Variant 1','PROD-002-V1','PROD-002-V1','PROD-002-V1-BAR',214000.00,102000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"M\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(50202,502,'Variant 2','PROD-002-V2','PROD-002-V2','PROD-002-V2-BAR',224000.00,102000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"L\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(50301,503,'Variant 1','PROD-003-V1','PROD-003-V1','PROD-003-V1-BAR',216000.00,103000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"M\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(50302,503,'Variant 2','PROD-003-V2','PROD-003-V2','PROD-003-V2-BAR',226000.00,103000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"L\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(50401,504,'Variant 1','PROD-004-V1','PROD-004-V1','PROD-004-V1-BAR',218000.00,104000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"M\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(50402,504,'Variant 2','PROD-004-V2','PROD-004-V2','PROD-004-V2-BAR',228000.00,104000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"L\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(50501,505,'Variant 1','PROD-005-V1','PROD-005-V1','PROD-005-V1-BAR',220000.00,105000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"M\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(50502,505,'Variant 2','PROD-005-V2','PROD-005-V2','PROD-005-V2-BAR',230000.00,105000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"L\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(50601,506,'Variant 1','PROD-006-V1','PROD-006-V1','PROD-006-V1-BAR',222000.00,106000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"M\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(50602,506,'Variant 2','PROD-006-V2','PROD-006-V2','PROD-006-V2-BAR',232000.00,106000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"L\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(50701,507,'Variant 1','PROD-007-V1','PROD-007-V1','PROD-007-V1-BAR',224000.00,107000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"M\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(50702,507,'Variant 2','PROD-007-V2','PROD-007-V2','PROD-007-V2-BAR',234000.00,107000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"L\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(50801,508,'Variant 1','PROD-008-V1','PROD-008-V1','PROD-008-V1-BAR',226000.00,108000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"M\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(50802,508,'Variant 2','PROD-008-V2','PROD-008-V2','PROD-008-V2-BAR',236000.00,108000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"L\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(50901,509,'Variant 1','PROD-009-V1','PROD-009-V1','PROD-009-V1-BAR',228000.00,109000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"M\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(50902,509,'Variant 2','PROD-009-V2','PROD-009-V2','PROD-009-V2-BAR',238000.00,109000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"L\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(51001,510,'Variant 1','PROD-010-V1','PROD-010-V1','PROD-010-V1-BAR',230000.00,110000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"M\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(51002,510,'Variant 2','PROD-010-V2','PROD-010-V2','PROD-010-V2-BAR',240000.00,110000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"L\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(51101,511,'Variant 1','PROD-011-V1','PROD-011-V1','PROD-011-V1-BAR',232000.00,111000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"M\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(51102,511,'Variant 2','PROD-011-V2','PROD-011-V2','PROD-011-V2-BAR',242000.00,111000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"L\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(51201,512,'Variant 1','PROD-012-V1','PROD-012-V1','PROD-012-V1-BAR',234000.00,112000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"M\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(51202,512,'Variant 2','PROD-012-V2','PROD-012-V2','PROD-012-V2-BAR',244000.00,112000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"L\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(51301,513,'Variant 1','PROD-013-V1','PROD-013-V1','PROD-013-V1-BAR',236000.00,113000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"M\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(51302,513,'Variant 2','PROD-013-V2','PROD-013-V2','PROD-013-V2-BAR',246000.00,113000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"L\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(51401,514,'Variant 1','PROD-014-V1','PROD-014-V1','PROD-014-V1-BAR',238000.00,114000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"M\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(51402,514,'Variant 2','PROD-014-V2','PROD-014-V2','PROD-014-V2-BAR',248000.00,114000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"L\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(51501,515,'Variant 1','PROD-015-V1','PROD-015-V1','PROD-015-V1-BAR',240000.00,115000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"M\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(51502,515,'Variant 2','PROD-015-V2','PROD-015-V2','PROD-015-V2-BAR',250000.00,115000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"L\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(51601,516,'Variant 1','PROD-016-V1','PROD-016-V1','PROD-016-V1-BAR',242000.00,116000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"M\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(51602,516,'Variant 2','PROD-016-V2','PROD-016-V2','PROD-016-V2-BAR',252000.00,116000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"L\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(51701,517,'Variant 1','PROD-017-V1','PROD-017-V1','PROD-017-V1-BAR',244000.00,117000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"M\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(51702,517,'Variant 2','PROD-017-V2','PROD-017-V2','PROD-017-V2-BAR',254000.00,117000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"L\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(51801,518,'Variant 1','PROD-018-V1','PROD-018-V1','PROD-018-V1-BAR',246000.00,118000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"M\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(51802,518,'Variant 2','PROD-018-V2','PROD-018-V2','PROD-018-V2-BAR',256000.00,118000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"L\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(51901,519,'Variant 1','PROD-019-V1','PROD-019-V1','PROD-019-V1-BAR',248000.00,119000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"M\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(51902,519,'Variant 2','PROD-019-V2','PROD-019-V2','PROD-019-V2-BAR',258000.00,119000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"L\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(52001,520,'Variant 1','PROD-020-V1','PROD-020-V1','PROD-020-V1-BAR',250000.00,120000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"M\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(52002,520,'Variant 2','PROD-020-V2','PROD-020-V2','PROD-020-V2-BAR',260000.00,120000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"L\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(52101,521,'Variant 1','PROD-021-V1','PROD-021-V1','PROD-021-V1-BAR',252000.00,121000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"M\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(52102,521,'Variant 2','PROD-021-V2','PROD-021-V2','PROD-021-V2-BAR',262000.00,121000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"L\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(52201,522,'Variant 1','PROD-022-V1','PROD-022-V1','PROD-022-V1-BAR',254000.00,122000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"M\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(52202,522,'Variant 2','PROD-022-V2','PROD-022-V2','PROD-022-V2-BAR',264000.00,122000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"L\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(52301,523,'Variant 1','PROD-023-V1','PROD-023-V1','PROD-023-V1-BAR',256000.00,123000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"M\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(52302,523,'Variant 2','PROD-023-V2','PROD-023-V2','PROD-023-V2-BAR',266000.00,123000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"L\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(52401,524,'Variant 1','PROD-024-V1','PROD-024-V1','PROD-024-V1-BAR',258000.00,124000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"M\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(52402,524,'Variant 2','PROD-024-V2','PROD-024-V2','PROD-024-V2-BAR',268000.00,124000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"L\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(52501,525,'Variant 1','PROD-025-V1','PROD-025-V1','PROD-025-V1-BAR',260000.00,125000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"M\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(52502,525,'Variant 2','PROD-025-V2','PROD-025-V2','PROD-025-V2-BAR',270000.00,125000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"L\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(52601,526,'Variant 1','PROD-026-V1','PROD-026-V1','PROD-026-V1-BAR',262000.00,126000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"M\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(52602,526,'Variant 2','PROD-026-V2','PROD-026-V2','PROD-026-V2-BAR',272000.00,126000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"L\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(52701,527,'Variant 1','PROD-027-V1','PROD-027-V1','PROD-027-V1-BAR',264000.00,127000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"M\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(52702,527,'Variant 2','PROD-027-V2','PROD-027-V2','PROD-027-V2-BAR',274000.00,127000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"L\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(52801,528,'Variant 1','PROD-028-V1','PROD-028-V1','PROD-028-V1-BAR',266000.00,128000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"M\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(52802,528,'Variant 2','PROD-028-V2','PROD-028-V2','PROD-028-V2-BAR',276000.00,128000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"L\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(52901,529,'Variant 1','PROD-029-V1','PROD-029-V1','PROD-029-V1-BAR',268000.00,129000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"M\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(52902,529,'Variant 2','PROD-029-V2','PROD-029-V2','PROD-029-V2-BAR',278000.00,129000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"L\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(53001,530,'Variant 1','PROD-030-V1','PROD-030-V1','PROD-030-V1-BAR',270000.00,130000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"M\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(53002,530,'Variant 2','PROD-030-V2','PROD-030-V2','PROD-030-V2-BAR',280000.00,130000.00,50.00,5.00,500.00,NULL,'{\"Size\":\"L\"}','active','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_warranties`
--

LOCK TABLES `product_warranties` WRITE;
/*!40000 ALTER TABLE `product_warranties` DISABLE KEYS */;
INSERT INTO `product_warranties` VALUES (1,510,NULL,NULL,'SN-000001','WAR-000001','2025-12-05','2026-12-05','active','Demo warranty 1',1,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(2,510,NULL,NULL,'SN-000002','WAR-000002','2025-12-05','2026-12-05','active','Demo warranty 2',1,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(3,510,NULL,NULL,'SN-000003','WAR-000003','2025-12-05','2026-12-05','active','Demo warranty 3',1,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(4,510,NULL,NULL,'SN-000004','WAR-000004','2025-12-05','2026-12-05','active','Demo warranty 4',1,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(5,510,NULL,NULL,'SN-000005','WAR-000005','2025-12-05','2026-12-05','active','Demo warranty 5',1,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(6,510,NULL,NULL,'SN-000006','WAR-000006','2025-12-05','2026-12-05','active','Demo warranty 6',1,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(7,510,NULL,NULL,'SN-000007','WAR-000007','2025-12-05','2026-12-05','active','Demo warranty 7',1,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(8,510,NULL,NULL,'SN-000008','WAR-000008','2025-12-05','2026-12-05','active','Demo warranty 8',1,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(9,510,NULL,NULL,'SN-000009','WAR-000009','2025-12-05','2026-12-05','active','Demo warranty 9',1,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(10,510,NULL,NULL,'SN-000010','WAR-000010','2025-12-05','2026-12-05','active','Demo warranty 10',1,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(11,510,NULL,NULL,'SN-000011','WAR-000011','2025-12-05','2026-12-05','active','Demo warranty 11',1,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(12,510,NULL,NULL,'SN-000012','WAR-000012','2025-12-05','2026-12-05','active','Demo warranty 12',1,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(13,510,NULL,NULL,'SN-000013','WAR-000013','2025-12-05','2026-12-05','active','Demo warranty 13',1,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(14,510,NULL,NULL,'SN-000014','WAR-000014','2025-12-05','2026-12-05','active','Demo warranty 14',1,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(15,510,NULL,NULL,'SN-000015','WAR-000015','2025-12-05','2026-12-05','active','Demo warranty 15',1,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(16,510,NULL,NULL,'SN-000016','WAR-000016','2025-12-05','2026-12-05','active','Demo warranty 16',1,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(17,510,NULL,NULL,'SN-000017','WAR-000017','2025-12-05','2026-12-05','active','Demo warranty 17',1,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(18,510,NULL,NULL,'SN-000018','WAR-000018','2025-12-05','2026-12-05','active','Demo warranty 18',1,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(19,510,NULL,NULL,'SN-000019','WAR-000019','2025-12-05','2026-12-05','active','Demo warranty 19',1,'2025-12-05 18:19:14','2025-12-05 18:19:14'),(20,510,NULL,NULL,'SN-000020','WAR-000020','2025-12-05','2026-12-05','active','Demo warranty 20',1,'2025-12-05 18:19:14','2025-12-05 18:19:14');
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
) ENGINE=InnoDB AUTO_INCREMENT=531 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (501,'goods','PROD-001','PROD-001-BAR','Sản phẩm Demo 1','san-pham-demo-1','Lano','A-1','Cái',NULL,1.00,1,NULL,'https://picsum.photos/seed/PROD-001/600/600','[]',1,0.50,'10x10x10','Mô tả sản phẩm demo 1',NULL,1,1,0,'active',202000.00,5.00,0.00,181800.00,101000.00,100,10,365,0,NULL,5,1000,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(502,'goods','PROD-002','PROD-002-BAR','Sản phẩm Demo 2','san-pham-demo-2','Lano','A-2','Cái',NULL,1.00,1,NULL,'https://picsum.photos/seed/PROD-002/600/600','[]',1,0.50,'10x10x10','Mô tả sản phẩm demo 2',NULL,1,1,0,'active',204000.00,5.00,0.00,183600.00,102000.00,100,10,365,0,NULL,5,1000,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(503,'goods','PROD-003','PROD-003-BAR','Sản phẩm Demo 3','san-pham-demo-3','Lano','A-3','Cái',NULL,1.00,1,NULL,'https://picsum.photos/seed/PROD-003/600/600','[]',1,0.50,'10x10x10','Mô tả sản phẩm demo 3',NULL,1,1,0,'active',206000.00,5.00,0.00,185400.00,103000.00,100,10,365,0,NULL,5,1000,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(504,'goods','PROD-004','PROD-004-BAR','Sản phẩm Demo 4','san-pham-demo-4','Lano','A-4','Cái',NULL,1.00,1,NULL,'https://picsum.photos/seed/PROD-004/600/600','[]',1,0.50,'10x10x10','Mô tả sản phẩm demo 4',NULL,1,1,0,'active',208000.00,5.00,0.00,187200.00,104000.00,100,10,365,0,NULL,5,1000,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(505,'goods','PROD-005','PROD-005-BAR','Sản phẩm Demo 5','san-pham-demo-5','Lano','A-5','Cái',NULL,1.00,1,NULL,'https://picsum.photos/seed/PROD-005/600/600','[]',1,0.50,'10x10x10','Mô tả sản phẩm demo 5',NULL,1,1,1,'active',210000.00,5.00,0.00,189000.00,105000.00,100,10,365,0,NULL,5,1000,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(506,'goods','PROD-006','PROD-006-BAR','Sản phẩm Demo 6','san-pham-demo-6','Lano','A-6','Cái',NULL,1.00,1,NULL,'https://picsum.photos/seed/PROD-006/600/600','[]',1,0.50,'10x10x10','Mô tả sản phẩm demo 6',NULL,1,1,0,'active',212000.00,5.00,0.00,190800.00,106000.00,100,10,365,0,NULL,5,1000,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(507,'goods','PROD-007','PROD-007-BAR','Sản phẩm Demo 7','san-pham-demo-7','Lano','A-7','Cái',NULL,1.00,1,NULL,'https://picsum.photos/seed/PROD-007/600/600','[]',1,0.50,'10x10x10','Mô tả sản phẩm demo 7',NULL,1,1,0,'active',214000.00,5.00,0.00,192600.00,107000.00,100,10,365,0,NULL,5,1000,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(508,'goods','PROD-008','PROD-008-BAR','Sản phẩm Demo 8','san-pham-demo-8','Lano','A-8','Cái',NULL,1.00,1,NULL,'https://picsum.photos/seed/PROD-008/600/600','[]',1,0.50,'10x10x10','Mô tả sản phẩm demo 8',NULL,1,1,0,'active',216000.00,5.00,0.00,194400.00,108000.00,100,10,365,0,NULL,5,1000,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(509,'goods','PROD-009','PROD-009-BAR','Sản phẩm Demo 9','san-pham-demo-9','Lano','A-9','Cái',NULL,1.00,1,NULL,'https://picsum.photos/seed/PROD-009/600/600','[]',1,0.50,'10x10x10','Mô tả sản phẩm demo 9',NULL,1,1,0,'active',218000.00,5.00,0.00,196200.00,109000.00,100,10,365,0,NULL,5,1000,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(510,'goods','PROD-010','PROD-010-BAR','Sản phẩm Demo 10','san-pham-demo-10','Lano','A-10','Cái',NULL,1.00,1,NULL,'https://picsum.photos/seed/PROD-010/600/600','[]',1,0.50,'10x10x10','Mô tả sản phẩm demo 10',NULL,1,1,1,'active',220000.00,5.00,0.00,198000.00,110000.00,100,10,365,0,NULL,5,1000,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(511,'goods','PROD-011','PROD-011-BAR','Sản phẩm Demo 11','san-pham-demo-11','Lano','A-11','Cái',NULL,1.00,1,NULL,'https://picsum.photos/seed/PROD-011/600/600','[]',1,0.50,'10x10x10','Mô tả sản phẩm demo 11',NULL,1,1,0,'active',222000.00,5.00,0.00,199800.00,111000.00,100,10,365,0,NULL,5,1000,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(512,'goods','PROD-012','PROD-012-BAR','Sản phẩm Demo 12','san-pham-demo-12','Lano','A-12','Cái',NULL,1.00,1,NULL,'https://picsum.photos/seed/PROD-012/600/600','[]',1,0.50,'10x10x10','Mô tả sản phẩm demo 12',NULL,1,1,0,'active',224000.00,5.00,0.00,201600.00,112000.00,100,10,365,0,NULL,5,1000,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(513,'goods','PROD-013','PROD-013-BAR','Sản phẩm Demo 13','san-pham-demo-13','Lano','A-13','Cái',NULL,1.00,1,NULL,'https://picsum.photos/seed/PROD-013/600/600','[]',1,0.50,'10x10x10','Mô tả sản phẩm demo 13',NULL,1,1,0,'active',226000.00,5.00,0.00,203400.00,113000.00,100,10,365,0,NULL,5,1000,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(514,'goods','PROD-014','PROD-014-BAR','Sản phẩm Demo 14','san-pham-demo-14','Lano','A-14','Cái',NULL,1.00,1,NULL,'https://picsum.photos/seed/PROD-014/600/600','[]',1,0.50,'10x10x10','Mô tả sản phẩm demo 14',NULL,1,1,0,'active',228000.00,5.00,0.00,205200.00,114000.00,100,10,365,0,NULL,5,1000,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(515,'goods','PROD-015','PROD-015-BAR','Sản phẩm Demo 15','san-pham-demo-15','Lano','A-15','Cái',NULL,1.00,1,NULL,'https://picsum.photos/seed/PROD-015/600/600','[]',1,0.50,'10x10x10','Mô tả sản phẩm demo 15',NULL,1,1,1,'active',230000.00,5.00,0.00,207000.00,115000.00,100,10,365,0,NULL,5,1000,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(516,'goods','PROD-016','PROD-016-BAR','Sản phẩm Demo 16','san-pham-demo-16','Lano','A-16','Cái',NULL,1.00,1,NULL,'https://picsum.photos/seed/PROD-016/600/600','[]',1,0.50,'10x10x10','Mô tả sản phẩm demo 16',NULL,1,1,0,'active',232000.00,5.00,0.00,208800.00,116000.00,100,10,365,0,NULL,5,1000,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(517,'goods','PROD-017','PROD-017-BAR','Sản phẩm Demo 17','san-pham-demo-17','Lano','A-17','Cái',NULL,1.00,1,NULL,'https://picsum.photos/seed/PROD-017/600/600','[]',1,0.50,'10x10x10','Mô tả sản phẩm demo 17',NULL,1,1,0,'active',234000.00,5.00,0.00,210600.00,117000.00,100,10,365,0,NULL,5,1000,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(518,'goods','PROD-018','PROD-018-BAR','Sản phẩm Demo 18','san-pham-demo-18','Lano','A-18','Cái',NULL,1.00,1,NULL,'https://picsum.photos/seed/PROD-018/600/600','[]',1,0.50,'10x10x10','Mô tả sản phẩm demo 18',NULL,1,1,0,'active',236000.00,5.00,0.00,212400.00,118000.00,100,10,365,0,NULL,5,1000,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(519,'goods','PROD-019','PROD-019-BAR','Sản phẩm Demo 19','san-pham-demo-19','Lano','A-19','Cái',NULL,1.00,1,NULL,'https://picsum.photos/seed/PROD-019/600/600','[]',1,0.50,'10x10x10','Mô tả sản phẩm demo 19',NULL,1,1,0,'active',238000.00,5.00,0.00,214200.00,119000.00,100,10,365,0,NULL,5,1000,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(520,'goods','PROD-020','PROD-020-BAR','Sản phẩm Demo 20','san-pham-demo-20','Lano','A-20','Cái',NULL,1.00,1,NULL,'https://picsum.photos/seed/PROD-020/600/600','[]',1,0.50,'10x10x10','Mô tả sản phẩm demo 20',NULL,1,1,1,'active',240000.00,5.00,0.00,216000.00,120000.00,100,10,365,0,NULL,5,1000,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(521,'goods','PROD-021','PROD-021-BAR','Sản phẩm Demo 21','san-pham-demo-21','Lano','A-21','Cái',NULL,1.00,1,NULL,'https://picsum.photos/seed/PROD-021/600/600','[]',1,0.50,'10x10x10','Mô tả sản phẩm demo 21',NULL,1,1,0,'active',242000.00,5.00,0.00,217800.00,121000.00,100,10,365,0,NULL,5,1000,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(522,'goods','PROD-022','PROD-022-BAR','Sản phẩm Demo 22','san-pham-demo-22','Lano','A-22','Cái',NULL,1.00,1,NULL,'https://picsum.photos/seed/PROD-022/600/600','[]',1,0.50,'10x10x10','Mô tả sản phẩm demo 22',NULL,1,1,0,'active',244000.00,5.00,0.00,219600.00,122000.00,100,10,365,0,NULL,5,1000,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(523,'goods','PROD-023','PROD-023-BAR','Sản phẩm Demo 23','san-pham-demo-23','Lano','A-23','Cái',NULL,1.00,1,NULL,'https://picsum.photos/seed/PROD-023/600/600','[]',1,0.50,'10x10x10','Mô tả sản phẩm demo 23',NULL,1,1,0,'active',246000.00,5.00,0.00,221400.00,123000.00,100,10,365,0,NULL,5,1000,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(524,'goods','PROD-024','PROD-024-BAR','Sản phẩm Demo 24','san-pham-demo-24','Lano','A-24','Cái',NULL,1.00,1,NULL,'https://picsum.photos/seed/PROD-024/600/600','[]',1,0.50,'10x10x10','Mô tả sản phẩm demo 24',NULL,1,1,0,'active',248000.00,5.00,0.00,223200.00,124000.00,100,10,365,0,NULL,5,1000,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(525,'goods','PROD-025','PROD-025-BAR','Sản phẩm Demo 25','san-pham-demo-25','Lano','A-25','Cái',NULL,1.00,1,NULL,'https://picsum.photos/seed/PROD-025/600/600','[]',1,0.50,'10x10x10','Mô tả sản phẩm demo 25',NULL,1,1,1,'active',250000.00,5.00,0.00,225000.00,125000.00,100,10,365,0,NULL,5,1000,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(526,'goods','PROD-026','PROD-026-BAR','Sản phẩm Demo 26','san-pham-demo-26','Lano','A-26','Cái',NULL,1.00,1,NULL,'https://picsum.photos/seed/PROD-026/600/600','[]',1,0.50,'10x10x10','Mô tả sản phẩm demo 26',NULL,1,1,0,'active',252000.00,5.00,0.00,226800.00,126000.00,100,10,365,0,NULL,5,1000,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(527,'goods','PROD-027','PROD-027-BAR','Sản phẩm Demo 27','san-pham-demo-27','Lano','A-27','Cái',NULL,1.00,1,NULL,'https://picsum.photos/seed/PROD-027/600/600','[]',1,0.50,'10x10x10','Mô tả sản phẩm demo 27',NULL,1,1,0,'active',254000.00,5.00,0.00,228600.00,127000.00,100,10,365,0,NULL,5,1000,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(528,'goods','PROD-028','PROD-028-BAR','Sản phẩm Demo 28','san-pham-demo-28','Lano','A-28','Cái',NULL,1.00,1,NULL,'https://picsum.photos/seed/PROD-028/600/600','[]',1,0.50,'10x10x10','Mô tả sản phẩm demo 28',NULL,1,1,0,'active',256000.00,5.00,0.00,230400.00,128000.00,100,10,365,0,NULL,5,1000,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(529,'goods','PROD-029','PROD-029-BAR','Sản phẩm Demo 29','san-pham-demo-29','Lano','A-29','Cái',NULL,1.00,1,NULL,'https://picsum.photos/seed/PROD-029/600/600','[]',1,0.50,'10x10x10','Mô tả sản phẩm demo 29',NULL,1,1,0,'active',258000.00,5.00,0.00,232200.00,129000.00,100,10,365,0,NULL,5,1000,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(530,'goods','PROD-030','PROD-030-BAR','Sản phẩm Demo 30','san-pham-demo-30','Lano','A-30','Cái',NULL,1.00,1,NULL,'https://picsum.photos/seed/PROD-030/600/600','[]',1,0.50,'10x10x10','Mô tả sản phẩm demo 30',NULL,1,1,1,'active',260000.00,5.00,0.00,234000.00,130000.00,100,10,365,0,NULL,5,1000,NULL,NULL,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL);
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
  `unit_price` decimal(14,2) DEFAULT NULL,
  `total_price` decimal(14,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_po_item_po` (`purchase_order_id`),
  KEY `fk_purchase_order_items_product_id` (`product_id`),
  CONSTRAINT `fk_purchase_order_items_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_purchase_order_items_purchase_order_id` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_order_items`
--

LOCK TABLES `purchase_order_items` WRITE;
/*!40000 ALTER TABLE `purchase_order_items` DISABLE KEYS */;
INSERT INTO `purchase_order_items` VALUES (1,1,501,100.000,0.000,0.00,0.00,'2025-12-05 18:19:14','2025-12-05 18:19:14',400000.00,40000000.00),(2,1,502,20.000,0.000,0.00,0.00,'2025-12-05 18:19:14','2025-12-05 18:19:14',500000.00,10000000.00),(3,2,503,50.000,0.000,0.00,0.00,'2025-12-05 18:19:14','2025-12-05 18:19:14',450000.00,22500000.00),(4,2,501,25.000,0.000,0.00,0.00,'2025-12-05 18:19:14','2025-12-05 18:19:14',500000.00,12500000.00),(5,3,502,30.000,0.000,0.00,0.00,'2025-12-05 18:19:14','2025-12-05 18:19:14',1500000.00,45000000.00),(6,4,501,150.000,0.000,0.00,0.00,'2025-12-05 18:19:14','2025-12-05 18:19:14',400000.00,60000000.00),(7,5,502,50.000,0.000,0.00,0.00,'2025-12-05 18:19:14','2025-12-05 18:19:14',500000.00,25000000.00),(8,6,503,80.000,0.000,0.00,0.00,'2025-12-05 18:19:14','2025-12-05 18:19:14',500000.00,40000000.00),(9,7,501,110.000,0.000,0.00,0.00,'2025-12-05 18:19:14','2025-12-05 18:19:14',500000.00,55000000.00),(10,8,502,20.000,0.000,0.00,0.00,'2025-12-05 18:19:14','2025-12-05 18:19:14',1500000.00,30000000.00);
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_orders`
--

LOCK TABLES `purchase_orders` WRITE;
/*!40000 ALTER TABLE `purchase_orders` DISABLE KEYS */;
INSERT INTO `purchase_orders` VALUES (1,NULL,NULL,NULL,1,1,NULL,1,'PO-2024-001','2025-11-05 18:19:14','2025-11-12 18:19:14',NULL,NULL,50000000.00,0.00,0.00,0.00,'completed',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL,0.00,50000000.00,'paid','Đơn hàng đầu tiên trong tháng',1),(2,NULL,NULL,NULL,1,2,NULL,2,'PO-2024-002','2025-11-10 18:19:14','2025-11-17 18:19:14',NULL,NULL,35000000.00,0.00,0.00,0.00,'completed',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL,0.00,35000000.00,'paid','Nhập hàng túi xách',2),(3,NULL,NULL,NULL,2,3,NULL,1,'PO-2024-003','2025-11-15 18:19:14','2025-11-22 18:19:14',NULL,NULL,45000000.00,0.00,0.00,0.00,'received',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL,0.00,22500000.00,'partial','Đã nhận hàng, chờ thanh toán phần còn lại',1),(4,NULL,NULL,NULL,1,4,NULL,2,'PO-2024-004','2025-11-20 18:19:14','2025-11-27 18:19:14',NULL,NULL,60000000.00,0.00,0.00,0.00,'in_transit',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL,0.00,30000000.00,'partial','Hàng đang trên đường về kho',2),(5,NULL,NULL,NULL,2,5,NULL,1,'PO-2024-005','2025-11-25 18:19:14','2025-12-02 18:19:14',NULL,NULL,25000000.00,0.00,0.00,0.00,'confirmed',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL,0.00,0.00,'unpaid','Nhà cung cấp đã xác nhận đơn',1),(6,NULL,NULL,NULL,1,6,NULL,2,'PO-2024-006','2025-11-28 18:19:14','2025-12-12 18:19:14',NULL,NULL,40000000.00,0.00,0.00,0.00,'pending',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL,0.00,0.00,'unpaid','Chờ nhà cung cấp xác nhận',2),(7,NULL,NULL,NULL,2,7,NULL,1,'PO-2024-007','2025-11-30 18:19:14','2025-12-15 18:19:14',NULL,NULL,55000000.00,0.00,0.00,0.00,'draft',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL,0.00,0.00,'unpaid','Đơn nháp, chưa gửi cho nhà cung cấp',1),(8,NULL,NULL,NULL,1,8,NULL,2,'PO-2024-008','2025-12-02 18:19:14','2025-12-17 18:19:14',NULL,NULL,30000000.00,0.00,0.00,0.00,'cancelled',NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL,0.00,0.00,'unpaid','Hủy do nhà cung cấp không đủ hàng',2);
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `return_items`
--

LOCK TABLES `return_items` WRITE;
/*!40000 ALTER TABLE `return_items` DISABLE KEYS */;
INSERT INTO `return_items` VALUES (1,6,120,1.000,'damaged','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(2,7,123,1.000,'used','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(3,8,126,1.000,'new','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(4,9,131,1.000,'new','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(5,10,159,1.000,'opened','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(6,11,167,1.000,'new','2025-12-05 18:19:14','2025-12-05 18:19:14',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `returns`
--

LOCK TABLES `returns` WRITE;
/*!40000 ALTER TABLE `returns` DISABLE KEYS */;
INSERT INTO `returns` VALUES (1,'THDH251205-0003',1003,4,5132000.00,NULL,5132000.00,NULL,'Defective',NULL,'approved',NULL,NULL,NULL,NULL,'2025-12-05 09:02:23',NULL,0,NULL,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(2,'THDH251205-0007',1007,6,1920000.00,NULL,1920000.00,NULL,'Defective',NULL,'approved',NULL,NULL,NULL,NULL,'2025-12-05 09:02:23',NULL,0,NULL,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(3,'THDH251205-0011',1011,2,4189000.00,NULL,4189000.00,NULL,'Defective',NULL,'approved',NULL,NULL,NULL,NULL,'2025-12-05 09:02:23',NULL,0,NULL,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(4,'THDH251205-0015',1015,2,3529000.00,NULL,3529000.00,NULL,'Defective',NULL,'approved',NULL,NULL,NULL,NULL,'2025-12-05 09:02:23',NULL,0,NULL,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(5,'THDH251205-0019',1019,2,9257000.00,NULL,9257000.00,NULL,'Defective',NULL,'approved',NULL,NULL,NULL,NULL,'2025-12-05 09:02:23',NULL,0,NULL,'2025-12-05 09:02:23','2025-12-05 09:02:23',NULL),(6,'RET-DEMO-001',1030,2012,206000.00,0,206000.00,'cash','defective',NULL,'approved',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(7,'RET-DEMO-002',1031,2013,155400.00,0,155400.00,'bank_transfer','not_satisfied',NULL,'completed',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(8,'RET-DEMO-003',1033,2015,204000.00,0,0.00,NULL,'wrong_item',NULL,'pending',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(9,'RET-DEMO-004',1035,2017,206000.00,0,0.00,NULL,'other','Không phù hợp với nhu cầu','rejected',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(10,'RET-DEMO-032',1052,2013,224000.00,0,0.00,'cash','other',NULL,'pending',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL),(11,'RET-DEMO-035',1055,2013,216000.00,0,0.00,'cash','other',NULL,'pending',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2025-12-05 18:19:14','2025-12-05 18:19:14',NULL);
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
INSERT INTO `roles` VALUES (1,'super-admin','api','Super Admin',1,'2025-12-05 18:19:13',NULL,NULL),(2,'manager','api','Quản lý',0,'2025-12-05 18:19:13',NULL,NULL),(3,'viewer','api','Xem chỉ đọc',0,'2025-12-05 18:19:13',NULL,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=5020 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_invoices`
--

LOCK TABLES `sales_invoices` WRITE;
/*!40000 ALTER TABLE `sales_invoices` DISABLE KEYS */;
INSERT INTO `sales_invoices` VALUES (5002,'HDDH251205-0002',9,'2025-12-05',NULL,'posted','VND',1.0000,4252000.00,0.00,4252000.00,0.00,NULL,NULL,'2025-12-05 09:01:37','2025-12-05 09:01:37'),(5003,'HDDH251205-0003',8,'2025-12-05',NULL,'posted','VND',1.0000,1050000.00,0.00,1050000.00,0.00,NULL,NULL,'2025-12-05 09:01:37','2025-12-05 09:01:37'),(5006,'HDDH251205-0006',6,'2025-12-05',NULL,'posted','VND',1.0000,3949000.00,0.00,3949000.00,0.00,NULL,NULL,'2025-12-05 09:01:37','2025-12-05 09:01:37'),(5007,'HDDH251205-0007',3,'2025-12-05',NULL,'posted','VND',1.0000,4385000.00,0.00,4385000.00,0.00,NULL,NULL,'2025-12-05 09:01:37','2025-12-05 09:01:37'),(5010,'HDDH251205-0010',4,'2025-12-05',NULL,'posted','VND',1.0000,9620000.00,0.00,9620000.00,0.00,NULL,NULL,'2025-12-05 09:01:37','2025-12-05 09:01:37'),(5011,'HDDH251205-0011',5,'2025-12-05',NULL,'posted','VND',1.0000,3021000.00,0.00,3021000.00,0.00,NULL,NULL,'2025-12-05 09:01:37','2025-12-05 09:01:37'),(5014,'HDDH251205-0014',9,'2025-12-05',NULL,'posted','VND',1.0000,4051000.00,0.00,4051000.00,0.00,NULL,NULL,'2025-12-05 09:01:37','2025-12-05 09:01:37'),(5015,'HDDH251205-0015',1,'2025-12-05',NULL,'posted','VND',1.0000,782000.00,0.00,782000.00,0.00,NULL,NULL,'2025-12-05 09:01:37','2025-12-05 09:01:37'),(5018,'HDDH251205-0018',9,'2025-12-05',NULL,'posted','VND',1.0000,4044000.00,0.00,4044000.00,0.00,NULL,NULL,'2025-12-05 09:01:37','2025-12-05 09:01:37'),(5019,'HDDH251205-0019',7,'2025-12-05',NULL,'posted','VND',1.0000,7812000.00,0.00,7812000.00,0.00,NULL,NULL,'2025-12-05 09:01:37','2025-12-05 09:01:37');
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,'SUP-001',NULL,'Nhà cung cấp A',NULL,'0101010101',NULL,NULL,NULL,NULL,'active',NULL,NULL,'2025-12-05 08:22:42','2025-12-05 08:22:42',NULL,'company',0),(2,'SUP-002',NULL,'Nhà cung cấp B',NULL,'0202020202',NULL,NULL,NULL,NULL,'active',NULL,NULL,'2025-12-05 08:22:42','2025-12-05 08:22:42',NULL,'company',0);
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasks`
--

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
INSERT INTO `tasks` VALUES (1,NULL,NULL,'','pending',0.00,0.00,0.00,NULL,'2025-12-06',2,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14','Call John Doe','Follow up on proposal','high','lead',1),(2,NULL,NULL,'','in_progress',0.00,0.00,0.00,NULL,'2025-12-07',3,NULL,'2025-12-05 18:19:14','2025-12-05 18:19:14','Prepare Contract','Draft service agreement for Tech Solutions','medium','opportunity',2);
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
INSERT INTO `tax_templates` VALUES (9001,'VAT 0%',0.000,0,'nearest','active','2025-12-05 18:19:13','2025-12-05 18:19:13'),(9002,'VAT 5%',5.000,0,'nearest','active','2025-12-05 18:19:13','2025-12-05 18:19:13'),(9003,'VAT 10%',10.000,0,'nearest','active','2025-12-05 18:19:13','2025-12-05 18:19:13');
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
INSERT INTO `users` VALUES (1,'admin.staging','admin@staging.lanocrm.local','$2y$12$7SI9yb1rxz2lzuKynLZLBekiXP8uYoeW6hO4TQWsgKWV.3nEW1o9G','Staging Admin',NULL,NULL,1,'active','2025-12-05 18:21:57','172.18.0.1',NULL,NULL,0,NULL,0,NULL,'Asia/Ho_Chi_Minh','2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(2,'manager.staging','manager@staging.lanocrm.local','$2y$12$pRDQCai3nUuqKTP3GzRxpuLXm52X56HoEyUv/q4ji5rwQuZDHfxC2','Staging Manager',NULL,NULL,1,'active',NULL,NULL,NULL,NULL,0,NULL,0,NULL,'Asia/Ho_Chi_Minh','2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(3,'staff.staging','staff@staging.lanocrm.local','$2y$12$3Y1HoAuurcIwSCt7ZYeCCuCs1Hkkd1sKaoFkjJd4z/LhkfKC2ifmu','Staging Staff',NULL,NULL,1,'active',NULL,NULL,NULL,NULL,0,NULL,0,NULL,'Asia/Ho_Chi_Minh','2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(10,'demo.admin','demo.admin@lanocrm.local','$2y$12$7SI9yb1rxz2lzuKynLZLBekiXP8uYoeW6hO4TQWsgKWV.3nEW1o9G','Demo Admin User',NULL,NULL,1,'active',NULL,NULL,NULL,NULL,0,NULL,0,NULL,'Asia/Ho_Chi_Minh','2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(11,'demo.manager.hn','manager.hn@lanocrm.local','$2y$12$pRDQCai3nUuqKTP3GzRxpuLXm52X56HoEyUv/q4ji5rwQuZDHfxC2','Demo Manager Hanoi',NULL,NULL,1,'active',NULL,NULL,NULL,NULL,0,NULL,0,NULL,'Asia/Ho_Chi_Minh','2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(12,'demo.manager.hcm','manager.hcm@lanocrm.local','$2y$12$pRDQCai3nUuqKTP3GzRxpuLXm52X56HoEyUv/q4ji5rwQuZDHfxC2','Demo Manager HCM',NULL,NULL,2,'active',NULL,NULL,NULL,NULL,0,NULL,0,NULL,'Asia/Ho_Chi_Minh','2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(13,'demo.staff1','staff1@lanocrm.local','$2y$12$3Y1HoAuurcIwSCt7ZYeCCuCs1Hkkd1sKaoFkjJd4z/LhkfKC2ifmu','Demo Staff 1',NULL,NULL,1,'active',NULL,NULL,NULL,NULL,0,NULL,0,NULL,'Asia/Ho_Chi_Minh','2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(14,'demo.staff2','staff2@lanocrm.local','$2y$12$3Y1HoAuurcIwSCt7ZYeCCuCs1Hkkd1sKaoFkjJd4z/LhkfKC2ifmu','Demo Staff 2',NULL,NULL,2,'active',NULL,NULL,NULL,NULL,0,NULL,0,NULL,'Asia/Ho_Chi_Minh','2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(15,'demo.inactive','inactive@lanocrm.local','$2y$12$3Y1HoAuurcIwSCt7ZYeCCuCs1Hkkd1sKaoFkjJd4z/LhkfKC2ifmu','Demo Inactive User',NULL,NULL,1,'inactive',NULL,NULL,NULL,NULL,0,NULL,0,NULL,'Asia/Ho_Chi_Minh','2025-12-05 18:19:13','2025-12-05 18:19:13',NULL);
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
INSERT INTO `warehouses` VALUES (1,'Kho chính Hà Nội','WH-HN-MAIN',1,'active','2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(2,'Kho bán lẻ Hà Nội','WH-HN-RETAIL',1,'active','2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(3,'Kho chính HCM','WH-HCM-MAIN',2,'active','2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(4,'Kho bán lẻ HCM','WH-HCM-RETAIL',2,'active','2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(5,'Kho chính Đà Nẵng','WH-DN-MAIN',3,'active','2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(6,'Kho chính Cần Thơ','WH-CT-MAIN',4,'active','2025-12-05 18:19:13','2025-12-05 18:19:13',NULL),(7,'Kho chính Hải Phòng','WH-HP-MAIN',5,'active','2025-12-05 18:19:13','2025-12-05 18:19:13',NULL);
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

-- Dump completed on 2025-12-05 18:34:46
