<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BaselineSchema extends Migration
{
    public function up()
    {
        $this->db->disableForeignKeyChecks();

        $this->db->query('DROP TABLE IF EXISTS `activity_logs`;');
        $this->db->query('CREATE TABLE `activity_logs` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`user_id` bigint unsigned DEFAULT NULL,
`action` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
`module` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
`model_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
`model_id` bigint unsigned DEFAULT NULL,
`old_values` text COLLATE utf8mb4_general_ci,
`new_values` text COLLATE utf8mb4_general_ci,
`ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
`user_agent` text COLLATE utf8mb4_general_ci,
`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (`id`),
KEY `idx_user_date` (`user_id`,`created_at`),
KEY `idx_module_model` (`module`,`model_type`,`model_id`),
CONSTRAINT `fk_activity_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;');
        $this->db->query('DROP TABLE IF EXISTS `activity_types`;');
        $this->db->query('CREATE TABLE `activity_types` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`activity_name` varchar(150) NOT NULL,
`billing_rate` decimal(12,2) DEFAULT \'0.00\',
`cost_rate` decimal(12,2) DEFAULT \'0.00\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `uq_activity_name` (`activity_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `appointments`;');
        $this->db->query('CREATE TABLE `appointments` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`customer_id` bigint unsigned DEFAULT NULL,
`lead_id` bigint unsigned DEFAULT NULL,
`contract_id` bigint unsigned DEFAULT NULL,
`start_time` datetime NOT NULL,
`end_time` datetime NOT NULL,
`status` varchar(30) DEFAULT \'scheduled\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `approval_actions`;');
        $this->db->query('CREATE TABLE `approval_actions` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `approvals`;');
        $this->db->query('CREATE TABLE `approvals` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`entity_type` varchar(50) DEFAULT \'order\',
`entity_id` bigint unsigned NOT NULL,
`order_id` bigint unsigned DEFAULT NULL,
`status` varchar(20) DEFAULT \'pending\',
`approver_queue` text NOT NULL,
`current_index` int DEFAULT \'0\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `assets`;');
        $this->db->query('CREATE TABLE `assets` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`asset_number` varchar(60) NOT NULL,
`asset_name` varchar(255) NOT NULL,
`category` varchar(120) DEFAULT NULL,
`purchase_date` date DEFAULT NULL,
`cost` decimal(14,2) DEFAULT \'0.00\',
`location` varchar(255) DEFAULT NULL,
`status` varchar(30) DEFAULT \'draft\',
`salvage_value` decimal(14,2) DEFAULT \'0.00\',
`useful_life_months` int DEFAULT \'0\',
`created_by` bigint unsigned DEFAULT NULL,
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `uq_asset_number` (`asset_number`),
KEY `idx_asset_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `assignment_logs`;');
        $this->db->query('CREATE TABLE `assignment_logs` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `assignment_rules`;');
        $this->db->query('CREATE TABLE `assignment_rules` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`name` varchar(150) NOT NULL,
`entity_type` varchar(80) NOT NULL,
`strategy` varchar(50) DEFAULT \'round_robin\',
`team_members` json NOT NULL,
`last_assigned_id` bigint unsigned DEFAULT NULL,
`is_active` tinyint(1) DEFAULT \'1\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_assignment_entity` (`entity_type`,`is_active`),
KEY `fk_assignment_rules_last_assigned_id` (`last_assigned_id`),
CONSTRAINT `fk_assignment_rules_last_assigned_id` FOREIGN KEY (`last_assigned_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `attendance`;');
        $this->db->query('CREATE TABLE `attendance` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`employee_id` bigint unsigned NOT NULL,
`date` date NOT NULL,
`check_in` time DEFAULT NULL,
`check_out` time DEFAULT NULL,
`status` varchar(20) DEFAULT \'present\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `fk_attendance_employee_id` (`employee_id`),
CONSTRAINT `fk_attendance_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `attendances`;');
        $this->db->query('CREATE TABLE `attendances` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`employee_id` bigint unsigned NOT NULL,
`attendance_date` date NOT NULL,
`status` varchar(20) DEFAULT \'present\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `uq_attendance_day` (`employee_id`,`attendance_date`),
CONSTRAINT `fk_attendances_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `attribute_options`;');
        $this->db->query('CREATE TABLE `attribute_options` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`attribute_id` bigint unsigned NOT NULL,
`option_name` varchar(255) DEFAULT NULL,
`color_code` varchar(50) DEFAULT NULL,
`sort_order` int DEFAULT \'0\',
`status` varchar(20) DEFAULT \'active\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
`deleted_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `fk_attribute_options_attribute_id` (`attribute_id`),
CONSTRAINT `fk_attribute_options_attribute_id` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `attributes`;');
        $this->db->query('CREATE TABLE `attributes` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`name` varchar(255) NOT NULL,
`slug` varchar(255) DEFAULT NULL,
`attribute_key` varchar(100) DEFAULT NULL,
`type` varchar(50) DEFAULT \'select\',
`is_required` tinyint DEFAULT \'0\',
`is_filterable` tinyint DEFAULT \'0\',
`sort_order` int DEFAULT \'0\',
`status` varchar(20) DEFAULT \'active\',
`is_visible` tinyint DEFAULT \'1\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
`deleted_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `audit_logs`;');
        $this->db->query('CREATE TABLE `audit_logs` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `bank_reconciliation_logs`;');
        $this->db->query('CREATE TABLE `bank_reconciliation_logs` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`bank_reconciliation_id` bigint unsigned NOT NULL,
`action` varchar(50) NOT NULL,
`message` text,
`created_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_bank_reco_log` (`bank_reconciliation_id`),
CONSTRAINT `fk_bank_reconciliation__bank_reconciliation_` FOREIGN KEY (`bank_reconciliation_id`) REFERENCES `bank_reconciliations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `bank_reconciliations`;');
        $this->db->query('CREATE TABLE `bank_reconciliations` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`bank_statement_id` bigint unsigned NOT NULL,
`payment_entry_id` bigint unsigned DEFAULT NULL,
`status` varchar(20) DEFAULT \'pending\',
`matched_amount` decimal(14,2) DEFAULT \'0.00\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_bank_reco_statement` (`bank_statement_id`),
KEY `fk_bank_reconciliations_payment_entry_id` (`payment_entry_id`),
CONSTRAINT `fk_bank_reconciliations_bank_statement_id` FOREIGN KEY (`bank_statement_id`) REFERENCES `bank_statements` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
CONSTRAINT `fk_bank_reconciliations_payment_entry_id` FOREIGN KEY (`payment_entry_id`) REFERENCES `payment_entries` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `bank_statements`;');
        $this->db->query('CREATE TABLE `bank_statements` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`account_number` varchar(50) NOT NULL,
`amount` decimal(14,2) NOT NULL,
`currency` varchar(10) DEFAULT \'VND\',
`reference_no` varchar(120) DEFAULT NULL,
`reference_date` date DEFAULT NULL,
`description` varchar(255) DEFAULT NULL,
`status` varchar(20) DEFAULT \'imported\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_bank_statement_ref` (`reference_no`,`reference_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `bill_of_materials`;');
        $this->db->query('CREATE TABLE `bill_of_materials` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`product_id` bigint unsigned NOT NULL,
`version` varchar(50) DEFAULT NULL,
`quantity` decimal(12,3) DEFAULT \'1.000\',
`uom` varchar(50) DEFAULT NULL,
`cost` decimal(14,4) DEFAULT \'0.0000\',
`is_active` tinyint(1) DEFAULT \'1\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_bom_product` (`product_id`),
KEY `idx_bom_active` (`is_active`),
CONSTRAINT `fk_bill_of_materials_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `bom`;');
        $this->db->query('CREATE TABLE `bom` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`product_id` bigint unsigned NOT NULL,
`name` varchar(255) NOT NULL,
`version` varchar(50) DEFAULT NULL,
`is_active` tinyint(1) DEFAULT \'1\',
`is_default` tinyint(1) DEFAULT \'0\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `fk_bom_product_id` (`product_id`),
CONSTRAINT `fk_bom_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `bom_items`;');
        $this->db->query('CREATE TABLE `bom_items` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`bom_id` bigint unsigned NOT NULL,
`component_product_id` bigint unsigned NOT NULL,
`quantity` decimal(12,3) DEFAULT \'0.000\',
`uom` varchar(50) DEFAULT NULL,
`scrap_percent` decimal(6,3) DEFAULT \'0.000\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_bom_item_bom` (`bom_id`),
KEY `idx_bom_item_component` (`component_product_id`),
CONSTRAINT `fk_bom_items_bom_id` FOREIGN KEY (`bom_id`) REFERENCES `bill_of_materials` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
CONSTRAINT `fk_bom_items_component_product_id` FOREIGN KEY (`component_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `branches`;');
        $this->db->query('CREATE TABLE `branches` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`name` varchar(255) NOT NULL,
`code` varchar(20) DEFAULT NULL,
`status` varchar(20) DEFAULT \'active\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
`deleted_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `campaign_members`;');
        $this->db->query('CREATE TABLE `campaign_members` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `campaigns`;');
        $this->db->query('CREATE TABLE `campaigns` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`name` varchar(150) NOT NULL,
`status` varchar(50) DEFAULT \'draft\',
`source` varchar(100) DEFAULT NULL,
`budget` decimal(14,2) DEFAULT \'0.00\',
`start_date` date DEFAULT NULL,
`end_date` date DEFAULT NULL,
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `cash_transactions`;');
        $this->db->query('CREATE TABLE `cash_transactions` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`type` enum(\'RECEIPT\',\'PAYMENT\') NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `chart_of_accounts`;');
        $this->db->query('CREATE TABLE `chart_of_accounts` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`code` varchar(50) NOT NULL,
`name` varchar(255) NOT NULL,
`account_type` varchar(50) NOT NULL,
`currency` varchar(10) DEFAULT NULL,
`parent_id` bigint unsigned DEFAULT NULL,
`is_group` tinyint(1) DEFAULT \'0\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
`type` varchar(50) DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `uq_coa_code` (`code`),
KEY `idx_coa_parent` (`parent_id`),
CONSTRAINT `fk_chart_of_accounts_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `chart_of_accounts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `companies`;');
        $this->db->query('CREATE TABLE `companies` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`code` varchar(50) NOT NULL,
`name` varchar(255) NOT NULL,
`is_default` tinyint(1) DEFAULT \'0\',
`status` varchar(30) DEFAULT \'active\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `uq_company_code` (`code`),
KEY `idx_company_status` (`status`,`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `company_permissions`;');
        $this->db->query('CREATE TABLE `company_permissions` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `contract_templates`;');
        $this->db->query('CREATE TABLE `contract_templates` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`name` varchar(150) NOT NULL,
`terms` text,
`status` varchar(30) DEFAULT \'active\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `contract_terms`;');
        $this->db->query('CREATE TABLE `contract_terms` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`contract_id` bigint unsigned NOT NULL,
`description` text,
`is_completed` tinyint(1) DEFAULT \'0\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_contract_terms` (`contract_id`),
CONSTRAINT `fk_contract_terms_contract_id` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `contracts`;');
        $this->db->query('CREATE TABLE `contracts` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`customer_id` bigint unsigned DEFAULT NULL,
`template_id` bigint unsigned DEFAULT NULL,
`start_date` date DEFAULT NULL,
`end_date` date DEFAULT NULL,
`value` decimal(14,2) DEFAULT \'0.00\',
`status` varchar(30) DEFAULT \'draft\',
`auto_renew` tinyint(1) DEFAULT \'0\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `fk_contracts_customer_id` (`customer_id`),
KEY `fk_contracts_template_id` (`template_id`),
CONSTRAINT `fk_contracts_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
CONSTRAINT `fk_contracts_template_id` FOREIGN KEY (`template_id`) REFERENCES `contract_templates` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `coupon_usages`;');
        $this->db->query('CREATE TABLE `coupon_usages` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `coupons`;');
        $this->db->query('CREATE TABLE `coupons` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`code` varchar(100) NOT NULL,
`discount_type` varchar(20) DEFAULT \'percent\',
`discount_value` decimal(14,2) NOT NULL DEFAULT \'0.00\',
`min_amount` decimal(14,2) DEFAULT \'0.00\',
`expiry_date` date DEFAULT NULL,
`usage_limit` int DEFAULT \'0\',
`used_count` int DEFAULT \'0\',
`status` varchar(20) DEFAULT \'active\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `credit_limits`;');
        $this->db->query('CREATE TABLE `credit_limits` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`customer_id` bigint unsigned NOT NULL,
`limit_amount` decimal(14,2) DEFAULT \'0.00\',
`on_hold` tinyint(1) DEFAULT \'0\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `uq_credit_limit_customer` (`customer_id`),
CONSTRAINT `fk_credit_limits_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `customer_groups`;');
        $this->db->query('CREATE TABLE `customer_groups` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`name_vi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`name_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`parent_id` bigint unsigned DEFAULT NULL,
`branch_id` bigint unsigned DEFAULT NULL,
`is_default` tinyint(1) NOT NULL DEFAULT \'0\',
`status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT \'active\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
`deleted_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `uq_customer_group_code` (`code`),
KEY `customer_groups_parent_id_foreign` (`parent_id`),
KEY `customer_groups_branch_id_foreign` (`branch_id`),
CONSTRAINT `customer_groups_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE SET NULL,
CONSTRAINT `customer_groups_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `customer_groups` (`id`) ON DELETE CASCADE ON UPDATE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
        $this->db->query('DROP TABLE IF EXISTS `customer_price_lists`;');
        $this->db->query('CREATE TABLE `customer_price_lists` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`customer_id` bigint unsigned NOT NULL,
`price_list_id` bigint unsigned NOT NULL,
`valid_from` date DEFAULT NULL,
`valid_to` date DEFAULT NULL,
`is_active` tinyint(1) DEFAULT \'1\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_cpl_customer` (`customer_id`,`price_list_id`),
KEY `fk_customer_price_lists_price_list_id` (`price_list_id`),
CONSTRAINT `fk_customer_price_lists_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
CONSTRAINT `fk_customer_price_lists_price_list_id` FOREIGN KEY (`price_list_id`) REFERENCES `price_lists` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `customers`;');
        $this->db->query('CREATE TABLE `customers` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`organization_id` bigint unsigned DEFAULT NULL,
`customer_group_id` bigint unsigned DEFAULT NULL,
`name` varchar(255) NOT NULL,
`email` varchar(255) DEFAULT NULL,
`phone` varchar(50) DEFAULT NULL,
`phone2` varchar(50) DEFAULT NULL,
`gender` enum(\'MALE\',\'FEMALE\',\'OTHER\') DEFAULT NULL,
`facebook` varchar(255) DEFAULT NULL,
`customer_type` enum(\'INDIVIDUAL\',\'COMPANY\',\'HOUSEHOLD\') NOT NULL DEFAULT \'INDIVIDUAL\',
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
`status` varchar(20) DEFAULT \'active\',
`last_transaction_at` datetime DEFAULT NULL,
`current_debt` decimal(15,2) DEFAULT \'0.00\',
`total_sales` decimal(15,2) DEFAULT \'0.00\',
`total_sales_net` decimal(15,2) DEFAULT \'0.00\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
`deleted_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `unique_tax_code_per_org` (`organization_id`,`tax_code`),
KEY `idx_customers_tax_code` (`tax_code`),
KEY `fk_customers_customer_group` (`customer_group_id`),
CONSTRAINT `fk_customers_customer_group` FOREIGN KEY (`customer_group_id`) REFERENCES `customer_groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
CONSTRAINT `fk_customers_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `delivery_note_items`;');
        $this->db->query('CREATE TABLE `delivery_note_items` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`delivery_note_id` bigint unsigned NOT NULL,
`order_item_id` bigint unsigned DEFAULT NULL,
`product_id` bigint unsigned NOT NULL,
`variant_id` bigint unsigned DEFAULT NULL,
`batch_id` bigint unsigned DEFAULT NULL,
`serial_number` varchar(160) DEFAULT NULL,
`quantity` decimal(12,3) DEFAULT \'0.000\',
`delivered_quantity` decimal(12,3) DEFAULT \'0.000\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `delivery_notes`;');
        $this->db->query('CREATE TABLE `delivery_notes` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`delivery_number` varchar(50) NOT NULL,
`order_id` bigint unsigned DEFAULT NULL,
`customer_id` bigint unsigned DEFAULT NULL,
`branch_id` bigint unsigned DEFAULT NULL,
`delivery_date` date DEFAULT NULL,
`expected_delivery_date` date DEFAULT NULL,
`status` varchar(30) DEFAULT \'draft\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `departments`;');
        $this->db->query('CREATE TABLE `departments` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`name` varchar(255) NOT NULL,
`code` varchar(50) NOT NULL,
`description` varchar(255) DEFAULT NULL,
`status` varchar(20) DEFAULT \'active\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `depreciation_schedule_lines`;');
        $this->db->query('CREATE TABLE `depreciation_schedule_lines` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`schedule_id` bigint unsigned NOT NULL,
`period_no` int NOT NULL,
`posting_date` date NOT NULL,
`amount` decimal(14,2) DEFAULT \'0.00\',
`posted_gl_entry_id` bigint unsigned DEFAULT NULL,
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `uq_dep_line` (`schedule_id`,`period_no`),
KEY `idx_dep_line_schedule` (`schedule_id`),
KEY `fk_depreciation_schedul_posted_gl_entry_id` (`posted_gl_entry_id`),
CONSTRAINT `fk_depreciation_schedul_posted_gl_entry_id` FOREIGN KEY (`posted_gl_entry_id`) REFERENCES `gl_entries` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
CONSTRAINT `fk_depreciation_schedul_schedule_id` FOREIGN KEY (`schedule_id`) REFERENCES `depreciation_schedules` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `depreciation_schedules`;');
        $this->db->query('CREATE TABLE `depreciation_schedules` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`asset_id` bigint unsigned NOT NULL,
`method` varchar(50) DEFAULT \'straight_line\',
`rate` decimal(6,3) DEFAULT \'0.000\',
`start_date` date DEFAULT NULL,
`total_periods` int DEFAULT \'0\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_dep_asset` (`asset_id`),
CONSTRAINT `fk_depreciation_schedul_asset_id` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `devices`;');
        $this->db->query('CREATE TABLE `devices` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT \'pos\',
`branch_id` bigint unsigned DEFAULT NULL,
`status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT \'active\',
`meta` json DEFAULT NULL,
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
`deleted_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `uq_device_code` (`code`),
KEY `devices_branch_id_foreign` (`branch_id`),
CONSTRAINT `devices_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
        $this->db->query('DROP TABLE IF EXISTS `document_shares`;');
        $this->db->query('CREATE TABLE `document_shares` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `e_invoice_logs`;');
        $this->db->query('CREATE TABLE `e_invoice_logs` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`invoice_id` bigint unsigned DEFAULT NULL,
`status` varchar(30) DEFAULT \'queued\',
`payload` json DEFAULT NULL,
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_einvoice_invoice` (`invoice_id`),
CONSTRAINT `fk_e_invoice_logs_invoice_id` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `ecommerce_webhook_logs`;');
        $this->db->query('CREATE TABLE `ecommerce_webhook_logs` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`source` varchar(50) DEFAULT NULL,
`event_type` varchar(100) NOT NULL,
`idempotency_key` varchar(150) NOT NULL,
`status` varchar(30) DEFAULT \'processed\',
`payload_hash` varchar(64) DEFAULT NULL,
`processed_at` datetime DEFAULT NULL,
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `uq_ecommerce_idempotency` (`idempotency_key`),
KEY `idx_ecommerce_event_status` (`event_type`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `email_campaign_logs`;');
        $this->db->query('CREATE TABLE `email_campaign_logs` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`email_campaign_id` bigint unsigned NOT NULL,
`member_id` bigint unsigned DEFAULT NULL,
`status` varchar(30) DEFAULT \'queued\',
`message` text,
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_email_campaign_log` (`email_campaign_id`),
KEY `fk_email_campaign_logs_member_id` (`member_id`),
CONSTRAINT `fk_email_campaign_logs_email_campaign_id` FOREIGN KEY (`email_campaign_id`) REFERENCES `email_campaigns` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
CONSTRAINT `fk_email_campaign_logs_member_id` FOREIGN KEY (`member_id`) REFERENCES `campaign_members` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `email_campaigns`;');
        $this->db->query('CREATE TABLE `email_campaigns` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`campaign_id` bigint unsigned DEFAULT NULL,
`subject` varchar(255) NOT NULL,
`template` text,
`schedule_at` datetime DEFAULT NULL,
`status` varchar(50) DEFAULT \'draft\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `fk_email_campaigns_campaign_id` (`campaign_id`),
CONSTRAINT `fk_email_campaigns_campaign_id` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `employees`;');
        $this->db->query('CREATE TABLE `employees` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`employee_code` varchar(60) NOT NULL,
`full_name` varchar(255) NOT NULL,
`branch_id` bigint unsigned DEFAULT NULL,
`status` varchar(30) DEFAULT \'active\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `exchange_rates`;');
        $this->db->query('CREATE TABLE `exchange_rates` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`currency` varchar(10) NOT NULL,
`rate` decimal(16,6) NOT NULL,
`valid_from` date NOT NULL,
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_exchange_rate_curr_date` (`currency`,`valid_from`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `gl_entries`;');
        $this->db->query('CREATE TABLE `gl_entries` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`posting_date` date NOT NULL,
`account_id` bigint unsigned NOT NULL,
`debit` decimal(14,2) DEFAULT \'0.00\',
`credit` decimal(14,2) DEFAULT \'0.00\',
`party_type` varchar(60) DEFAULT NULL,
`party_id` bigint unsigned DEFAULT NULL,
`reference_type` varchar(100) DEFAULT NULL,
`reference_id` bigint unsigned DEFAULT NULL,
`remarks` text,
`currency` varchar(10) DEFAULT \'VND\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_gl_account` (`account_id`),
KEY `idx_gl_posting_date` (`posting_date`),
KEY `idx_gl_party` (`party_type`,`party_id`),
CONSTRAINT `fk_gl_entries_account_id` FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `goods_receipt_items`;');
        $this->db->query('CREATE TABLE `goods_receipt_items` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`goods_receipt_id` bigint unsigned NOT NULL,
`product_id` bigint unsigned DEFAULT NULL,
`quantity` decimal(14,3) DEFAULT \'0.000\',
`rate` decimal(14,2) DEFAULT \'0.00\',
`amount` decimal(14,2) DEFAULT \'0.00\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_grn_item_grn` (`goods_receipt_id`),
KEY `fk_goods_receipt_items_product_id` (`product_id`),
CONSTRAINT `fk_goods_receipt_items_goods_receipt_id` FOREIGN KEY (`goods_receipt_id`) REFERENCES `goods_receipts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
CONSTRAINT `fk_goods_receipt_items_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `goods_receipts`;');
        $this->db->query('CREATE TABLE `goods_receipts` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`receipt_number` varchar(50) NOT NULL,
`purchase_order_id` bigint unsigned DEFAULT NULL,
`branch_id` bigint unsigned DEFAULT NULL,
`status` varchar(30) DEFAULT \'draft\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `uq_grn_number` (`receipt_number`),
KEY `fk_goods_receipts_branch_id` (`branch_id`),
KEY `fk_goods_receipts_purchase_order_id` (`purchase_order_id`),
CONSTRAINT `fk_goods_receipts_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
CONSTRAINT `fk_goods_receipts_purchase_order_id` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `inventory_alerts`;');
        $this->db->query('CREATE TABLE `inventory_alerts` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `inventory_movements`;');
        $this->db->query('CREATE TABLE `inventory_movements` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `inventory_stock`;');
        $this->db->query('CREATE TABLE `inventory_stock` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`branch_id` bigint unsigned DEFAULT NULL,
`warehouse_id` bigint unsigned DEFAULT NULL,
`product_id` bigint unsigned DEFAULT NULL,
`variant_id` bigint unsigned DEFAULT NULL,
`quantity_on_hand` decimal(10,2) DEFAULT \'0.00\',
`quantity_reserved` decimal(10,2) DEFAULT \'0.00\',
`minimum_stock` decimal(10,2) DEFAULT \'0.00\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `inventory_valuation`;');
        $this->db->query('CREATE TABLE `inventory_valuation` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`product_id` bigint unsigned DEFAULT NULL,
`variant_id` bigint unsigned DEFAULT NULL,
`avg_cost` decimal(12,2) DEFAULT \'0.00\',
`total_cost` decimal(14,2) DEFAULT \'0.00\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `fk_inventory_valuation_product_id` (`product_id`),
KEY `fk_inventory_valuation_variant_id` (`variant_id`),
CONSTRAINT `fk_inventory_valuation_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
CONSTRAINT `fk_inventory_valuation_variant_id` FOREIGN KEY (`variant_id`) REFERENCES `product_variants_v2` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `invoice_orders`;');
        $this->db->query('CREATE TABLE `invoice_orders` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `invoices`;');
        $this->db->query('CREATE TABLE `invoices` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`invoice_number` varchar(50) DEFAULT NULL COMMENT \'Generated when issuing invoice\',
`invoice_status` varchar(30) DEFAULT NULL,
`invoice_type` varchar(20) DEFAULT NULL,
`e_invoice_status` varchar(30) DEFAULT NULL,
`customer_id` bigint unsigned DEFAULT NULL,
`branch_id` bigint unsigned DEFAULT NULL,
`issue_date` date DEFAULT NULL,
`due_date` date DEFAULT NULL,
`subtotal` decimal(10,2) DEFAULT \'0.00\',
`goods_total` decimal(10,2) DEFAULT \'0.00\',
`discount_total` decimal(10,2) DEFAULT \'0.00\',
`net_total` decimal(10,2) DEFAULT \'0.00\',
`vat_rate` decimal(5,2) DEFAULT \'0.00\',
`vat_amount` decimal(10,2) DEFAULT \'0.00\',
`tax_amount` decimal(10,2) DEFAULT \'0.00\',
`other_fee` decimal(10,2) DEFAULT \'0.00\',
`shipping_fee` decimal(10,2) DEFAULT \'0.00\',
`customer_payable` decimal(10,2) DEFAULT \'0.00\',
`customer_paid` decimal(10,2) DEFAULT \'0.00\',
`cod_amount` decimal(10,2) DEFAULT \'0.00\',
`rounding_adjustment` decimal(10,2) DEFAULT \'0.00\',
`payment_status` varchar(20) DEFAULT NULL,
`total_paid` decimal(10,2) DEFAULT \'0.00\',
`currency_code` varchar(10) DEFAULT \'VND\',
`exchange_rate` decimal(15,6) DEFAULT \'1.000000\',
`last_payment_date` datetime DEFAULT NULL,
`total` decimal(10,2) DEFAULT \'0.00\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `job_logs`;');
        $this->db->query('CREATE TABLE `job_logs` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`job_id` bigint unsigned NOT NULL,
`status` varchar(30) NOT NULL,
`message` text,
`created_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_job_log_job` (`job_id`),
CONSTRAINT `fk_job_logs_job_id` FOREIGN KEY (`job_id`) REFERENCES `job_queue` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `job_queue`;');
        $this->db->query('CREATE TABLE `job_queue` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`name` varchar(150) NOT NULL,
`payload` json DEFAULT NULL,
`status` varchar(30) DEFAULT \'queued\',
`attempts` int DEFAULT \'0\',
`max_attempts` int DEFAULT \'3\',
`next_run_at` datetime DEFAULT NULL,
`last_error` text,
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_job_status` (`status`,`next_run_at`),
KEY `idx_job_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `journal_entries`;');
        $this->db->query('CREATE TABLE `journal_entries` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`reference_number` varchar(50) DEFAULT NULL,
`date` date NOT NULL,
`description` varchar(255) DEFAULT NULL,
`status` varchar(30) DEFAULT \'draft\',
`created_by` bigint unsigned DEFAULT NULL,
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `journal_entry_lines`;');
        $this->db->query('CREATE TABLE `journal_entry_lines` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`journal_entry_id` bigint unsigned NOT NULL,
`account_code` varchar(50) NOT NULL,
`debit` decimal(14,2) DEFAULT \'0.00\',
`credit` decimal(14,2) DEFAULT \'0.00\',
`description` varchar(255) DEFAULT NULL,
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `fk_jel_journal_entry_id` (`journal_entry_id`),
CONSTRAINT `fk_jel_journal_entry` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `knowledge_base_articles`;');
        $this->db->query('CREATE TABLE `knowledge_base_articles` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`category_id` bigint unsigned DEFAULT NULL,
`title` varchar(255) NOT NULL,
`content` text,
`is_published` tinyint(1) DEFAULT \'0\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_kb_article_category` (`category_id`),
KEY `idx_kb_article_published` (`is_published`),
CONSTRAINT `fk_knowledge_base_artic_category_id` FOREIGN KEY (`category_id`) REFERENCES `knowledge_base_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `knowledge_base_categories`;');
        $this->db->query('CREATE TABLE `knowledge_base_categories` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`name` varchar(150) NOT NULL,
`description` text,
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `uq_kb_category` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `landed_cost_items`;');
        $this->db->query('CREATE TABLE `landed_cost_items` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`landed_cost_voucher_id` bigint unsigned NOT NULL,
`goods_receipt_item_id` bigint unsigned DEFAULT NULL,
`cost_component` varchar(150) NOT NULL,
`amount` decimal(14,2) DEFAULT \'0.00\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_lcv_item` (`landed_cost_voucher_id`),
KEY `fk_landed_cost_items_goods_receipt_item_i` (`goods_receipt_item_id`),
CONSTRAINT `fk_landed_cost_items_goods_receipt_item_i` FOREIGN KEY (`goods_receipt_item_id`) REFERENCES `goods_receipt_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
CONSTRAINT `fk_landed_cost_items_landed_cost_voucher_` FOREIGN KEY (`landed_cost_voucher_id`) REFERENCES `landed_cost_vouchers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `landed_cost_vouchers`;');
        $this->db->query('CREATE TABLE `landed_cost_vouchers` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`voucher_number` varchar(50) NOT NULL,
`goods_receipt_id` bigint unsigned DEFAULT NULL,
`total_cost` decimal(14,2) DEFAULT \'0.00\',
`status` varchar(30) DEFAULT \'draft\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `uq_lcv_number` (`voucher_number`),
KEY `fk_landed_cost_vouchers_goods_receipt_id` (`goods_receipt_id`),
CONSTRAINT `fk_landed_cost_vouchers_goods_receipt_id` FOREIGN KEY (`goods_receipt_id`) REFERENCES `goods_receipts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `leads`;');
        $this->db->query('CREATE TABLE `leads` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`lead_number` varchar(50) DEFAULT NULL,
`name` varchar(255) NOT NULL,
`email` varchar(255) DEFAULT NULL,
`phone` varchar(50) DEFAULT NULL,
`source` varchar(100) DEFAULT NULL,
`status` varchar(50) DEFAULT \'new\',
`company` varchar(255) DEFAULT NULL,
`notes` text,
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
`first_name` varchar(100) DEFAULT NULL,
`last_name` varchar(100) DEFAULT NULL,
`assigned_to` bigint unsigned DEFAULT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `leave_applications`;');
        $this->db->query('CREATE TABLE `leave_applications` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`employee_id` bigint unsigned NOT NULL,
`leave_type_id` bigint unsigned NOT NULL,
`from_date` date NOT NULL,
`to_date` date NOT NULL,
`total_days` decimal(10,2) DEFAULT \'0.00\',
`status` varchar(30) DEFAULT \'pending\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `leave_types`;');
        $this->db->query('CREATE TABLE `leave_types` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`leave_name` varchar(150) NOT NULL,
`default_allocation` decimal(10,2) DEFAULT \'0.00\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `uq_leave_name` (`leave_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `login_attempts`;');
        $this->db->query('CREATE TABLE `login_attempts` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`username` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
`success` tinyint(1) NOT NULL DEFAULT \'0\',
`failure_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`attempted_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
        $this->db->query('DROP TABLE IF EXISTS `loyalty_programs`;');
        $this->db->query('CREATE TABLE `loyalty_programs` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`name` varchar(150) NOT NULL,
`customer_group_id` bigint unsigned DEFAULT NULL,
`earn_rate` decimal(12,4) DEFAULT \'0.0000\',
`redeem_rate` decimal(12,4) DEFAULT \'0.0000\',
`expiry_days` int DEFAULT \'365\',
`status` varchar(20) DEFAULT \'active\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `fk_loyalty_programs_customer_group` (`customer_group_id`),
CONSTRAINT `fk_loyalty_programs_customer_group` FOREIGN KEY (`customer_group_id`) REFERENCES `customer_groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `loyalty_transactions`;');
        $this->db->query('CREATE TABLE `loyalty_transactions` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `loyalty_wallets`;');
        $this->db->query('CREATE TABLE `loyalty_wallets` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`customer_id` bigint unsigned NOT NULL,
`points_balance` decimal(14,2) DEFAULT \'0.00\',
`last_earned_at` datetime DEFAULT NULL,
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `uq_loyalty_wallet_customer` (`customer_id`),
CONSTRAINT `fk_loyalty_wallets_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `maintenance_schedules`;');
        $this->db->query('CREATE TABLE `maintenance_schedules` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`asset_id` bigint unsigned NOT NULL,
`schedule_name` varchar(150) NOT NULL,
`frequency` varchar(50) DEFAULT \'monthly\',
`next_due_date` date DEFAULT NULL,
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_maint_asset` (`asset_id`),
CONSTRAINT `fk_maintenance_schedule_asset_id` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `maintenance_work_orders`;');
        $this->db->query('CREATE TABLE `maintenance_work_orders` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`work_order_number` varchar(60) NOT NULL,
`asset_id` bigint unsigned NOT NULL,
`schedule_id` bigint unsigned DEFAULT NULL,
`status` varchar(30) DEFAULT \'open\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `migrations`;');
        $this->db->query('CREATE TABLE `migrations` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`version` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`class` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`group` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`namespace` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`time` int NOT NULL,
`batch` int unsigned NOT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
        $this->db->query('DROP TABLE IF EXISTS `model_has_permissions`;');
        $this->db->query('CREATE TABLE `model_has_permissions` (
`permission_id` bigint unsigned NOT NULL,
`model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
`model_id` bigint unsigned NOT NULL,
PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
        $this->db->query('DROP TABLE IF EXISTS `model_has_roles`;');
        $this->db->query('CREATE TABLE `model_has_roles` (
`role_id` bigint unsigned NOT NULL,
`model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`model_id` bigint unsigned NOT NULL,
PRIMARY KEY (`role_id`,`model_id`,`model_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
        $this->db->query('DROP TABLE IF EXISTS `notification_rules`;');
        $this->db->query('CREATE TABLE `notification_rules` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`name` varchar(150) NOT NULL,
`event_type` varchar(80) NOT NULL,
`channel` varchar(50) DEFAULT \'email\',
`template` text NOT NULL,
`is_active` tinyint(1) DEFAULT \'1\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_notification_rule_event` (`event_type`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `notifications`;');
        $this->db->query('CREATE TABLE `notifications` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`rule_id` bigint unsigned DEFAULT NULL,
`event_type` varchar(80) NOT NULL,
`entity_type` varchar(80) DEFAULT NULL,
`entity_id` bigint unsigned DEFAULT NULL,
`payload` json DEFAULT NULL,
`status` varchar(30) DEFAULT \'queued\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_notification_rule` (`rule_id`),
KEY `idx_notification_event` (`event_type`),
CONSTRAINT `fk_notifications_rule_id` FOREIGN KEY (`rule_id`) REFERENCES `notification_rules` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `opportunities`;');
        $this->db->query('CREATE TABLE `opportunities` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`lead_id` bigint unsigned DEFAULT NULL,
`customer_id` bigint unsigned DEFAULT NULL,
`title` varchar(255) NOT NULL,
`stage` varchar(50) DEFAULT \'qualification\',
`probability` int DEFAULT \'10\',
`expected_value` decimal(14,2) DEFAULT \'0.00\',
`closing_date` date DEFAULT NULL,
`status` varchar(30) DEFAULT \'open\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
`name` varchar(255) DEFAULT NULL,
`amount` decimal(14,2) DEFAULT \'0.00\',
`close_date` date DEFAULT NULL,
`assigned_to` bigint unsigned DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `fk_opportunities_customer_id` (`customer_id`),
KEY `fk_opportunities_lead_id` (`lead_id`),
CONSTRAINT `fk_opportunities_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
CONSTRAINT `fk_opportunities_lead_id` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `opportunity_items`;');
        $this->db->query('CREATE TABLE `opportunity_items` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`opportunity_id` bigint unsigned NOT NULL,
`product_id` bigint unsigned NOT NULL,
`quantity` decimal(14,2) DEFAULT \'1.00\',
`price` decimal(14,2) DEFAULT \'0.00\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_opp_items_opp` (`opportunity_id`),
KEY `fk_opportunity_items_product_id` (`product_id`),
CONSTRAINT `fk_opportunity_items_opportunity_id` FOREIGN KEY (`opportunity_id`) REFERENCES `opportunities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
CONSTRAINT `fk_opportunity_items_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `order_approval_rules`;');
        $this->db->query('CREATE TABLE `order_approval_rules` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`name` varchar(150) NOT NULL,
`condition_type` varchar(50) DEFAULT \'amount\',
`threshold_amount` decimal(14,2) DEFAULT \'0.00\',
`customer_id` bigint unsigned DEFAULT NULL,
`custom_condition` text,
`approver_ids` text NOT NULL,
`priority` int DEFAULT \'100\',
`is_active` tinyint(1) DEFAULT \'1\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_rule_condition` (`condition_type`,`customer_id`),
KEY `idx_rule_priority` (`priority`),
KEY `fk_order_approval_rules_customer_id` (`customer_id`),
CONSTRAINT `fk_order_approval_rules_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `order_items`;');
        $this->db->query('CREATE TABLE `order_items` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`order_id` bigint unsigned NOT NULL,
`product_id` bigint unsigned DEFAULT NULL,
`variant_id` bigint unsigned DEFAULT NULL,
`batch_id` bigint unsigned DEFAULT NULL,
`serial_numbers` text,
`quantity` decimal(14,3) DEFAULT \'0.000\',
`base_price` decimal(14,2) DEFAULT \'0.00\',
`final_price` decimal(14,2) DEFAULT \'0.00\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `order_payments`;');
        $this->db->query('CREATE TABLE `order_payments` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`order_id` bigint unsigned NOT NULL,
`payment_method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
`amount` decimal(15,2) NOT NULL DEFAULT \'0.00\',
`paid_at` datetime DEFAULT NULL,
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `fk_order_payments_order` (`order_id`),
KEY `fk_order_payments_method` (`payment_method`),
CONSTRAINT `fk_order_payments_method` FOREIGN KEY (`payment_method`) REFERENCES `payment_methods` (`code`) ON DELETE SET NULL ON UPDATE CASCADE,
CONSTRAINT `fk_order_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `order_sequences`;');
        $this->db->query('CREATE TABLE `order_sequences` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`branch_id` bigint unsigned DEFAULT NULL,
`sequence_number` int DEFAULT \'1\',
`prefix` varchar(20) DEFAULT \'ORD\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `fk_order_sequences_branch_id` (`branch_id`),
CONSTRAINT `fk_order_sequences_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `order_status_logs`;');
        $this->db->query('CREATE TABLE `order_status_logs` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `order_subscriptions`;');
        $this->db->query('CREATE TABLE `order_subscriptions` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`template_id` bigint unsigned NOT NULL,
`branch_id` bigint unsigned NOT NULL,
`payment_method` varchar(50) NOT NULL,
`order_type` varchar(20) DEFAULT \'shipping\',
`next_run_at` datetime DEFAULT NULL,
`last_run_at` datetime DEFAULT NULL,
`frequency_interval` int DEFAULT \'7\',
`status` varchar(20) DEFAULT \'active\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_order_subscription_template` (`template_id`),
KEY `idx_order_subscription_next` (`next_run_at`),
KEY `idx_order_subscription_status` (`status`),
KEY `fk_order_subscriptions_branch_id` (`branch_id`),
CONSTRAINT `fk_order_subscriptions_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
CONSTRAINT `fk_order_subscriptions_template_id` FOREIGN KEY (`template_id`) REFERENCES `order_templates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `order_template_items`;');
        $this->db->query('CREATE TABLE `order_template_items` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`template_id` bigint unsigned NOT NULL,
`product_id` bigint unsigned NOT NULL,
`variant_id` bigint unsigned DEFAULT NULL,
`quantity` decimal(12,3) DEFAULT \'0.000\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `order_templates`;');
        $this->db->query('CREATE TABLE `order_templates` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`name` varchar(150) NOT NULL,
`customer_id` bigint unsigned DEFAULT NULL,
`frequency` varchar(50) DEFAULT NULL,
`is_active` tinyint(1) DEFAULT \'1\',
`notes` text,
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `uq_order_template_name` (`name`),
KEY `idx_order_template_customer` (`customer_id`),
KEY `idx_order_template_active` (`is_active`),
CONSTRAINT `fk_order_templates_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `orders`;');
        $this->db->query('CREATE TABLE `orders` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`code` varchar(50) DEFAULT NULL,
`order_number` varchar(50) NOT NULL,
`customer_id` bigint unsigned DEFAULT NULL,
`customer_group_id` bigint unsigned DEFAULT NULL,
`branch_id` bigint unsigned DEFAULT NULL,
`warehouse_id` bigint unsigned DEFAULT NULL,
`order_date` date DEFAULT NULL,
`order_type` varchar(50) DEFAULT \'online\',
`pos_profile_id` bigint unsigned DEFAULT NULL,
`pos_shift_id` bigint unsigned DEFAULT NULL,
`tax_template_id` bigint unsigned DEFAULT NULL,
`tax_total` decimal(15,2) NOT NULL DEFAULT \'0.00\',
`rounding_adjustment` decimal(15,2) NOT NULL DEFAULT \'0.00\',
`payment_method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
`status` varchar(50) DEFAULT \'draft\',
`coupon_code` varchar(120) DEFAULT NULL,
`coupon_discount` decimal(15,2) NOT NULL DEFAULT \'0.00\',
`loyalty_points_redeemed` int DEFAULT \'0\',
`loyalty_discount` decimal(15,2) NOT NULL DEFAULT \'0.00\',
`loyalty_points_earned` int DEFAULT \'0\',
`subtotal` decimal(15,2) NOT NULL DEFAULT \'0.00\',
`discount_total` decimal(15,2) NOT NULL DEFAULT \'0.00\',
`shipping_fee` decimal(15,2) NOT NULL DEFAULT \'0.00\',
`total` decimal(15,2) NOT NULL DEFAULT \'0.00\',
`paid_amount` decimal(15,2) NOT NULL DEFAULT \'0.00\',
`debt_amount` decimal(15,2) NOT NULL DEFAULT \'0.00\',
`payment_status` varchar(20) DEFAULT NULL,
`is_paid` tinyint(1) DEFAULT \'0\',
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
`cod_collected` tinyint(1) DEFAULT \'0\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `organizations`;');
        $this->db->query('CREATE TABLE `organizations` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`name_vi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`name_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`tax_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`email` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT \'active\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
        $this->db->query('DROP TABLE IF EXISTS `packing_slip_items`;');
        $this->db->query('CREATE TABLE `packing_slip_items` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`packing_slip_id` bigint unsigned NOT NULL,
`pick_list_item_id` bigint unsigned DEFAULT NULL,
`stock_entry_item_id` bigint unsigned DEFAULT NULL,
`product_id` bigint unsigned NOT NULL,
`qty` decimal(12,3) DEFAULT \'0.000\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `packing_slips`;');
        $this->db->query('CREATE TABLE `packing_slips` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`packing_slip_number` varchar(60) NOT NULL,
`pick_list_id` bigint unsigned DEFAULT NULL,
`stock_entry_id` bigint unsigned DEFAULT NULL,
`status` varchar(20) DEFAULT \'packed\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `partners`;');
        $this->db->query('CREATE TABLE `partners` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`code` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
`name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
`type` enum(\'supplier\',\'vendor\',\'distributor\',\'manufacturer\') COLLATE utf8mb4_general_ci NOT NULL,
`contact_person` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
`email` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
`phone` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
`address` text COLLATE utf8mb4_general_ci,
`city` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
`tax_code` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
`bank_account` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
`bank_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
`credit_limit` decimal(15,2) NOT NULL DEFAULT \'0.00\',
`debt_amount` decimal(15,2) NOT NULL DEFAULT \'0.00\',
`total_purchased` decimal(15,2) NOT NULL DEFAULT \'0.00\',
`status` enum(\'active\',\'inactive\') COLLATE utf8mb4_general_ci NOT NULL DEFAULT \'active\',
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
`deleted_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;');
        $this->db->query('DROP TABLE IF EXISTS `payment_entries`;');
        $this->db->query('CREATE TABLE `payment_entries` (
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
`amount` decimal(14,2) NOT NULL DEFAULT \'0.00\',
`currency` varchar(10) DEFAULT \'VND\',
`exchange_rate` decimal(12,4) DEFAULT \'1.0000\',
`reference` varchar(120) DEFAULT NULL,
`reference_no` varchar(120) DEFAULT NULL,
`reference_date` date DEFAULT NULL,
`status` varchar(20) DEFAULT \'posted\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `payment_entry_allocations`;');
        $this->db->query('CREATE TABLE `payment_entry_allocations` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`payment_entry_id` bigint unsigned NOT NULL,
`reference_type` varchar(120) DEFAULT NULL,
`reference_id` bigint unsigned DEFAULT NULL,
`allocated_amount` decimal(14,2) DEFAULT \'0.00\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_payment_alloc_entry` (`payment_entry_id`),
CONSTRAINT `fk_payment_entry_alloca_payment_entry_id` FOREIGN KEY (`payment_entry_id`) REFERENCES `payment_entries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `payment_methods`;');
        $this->db->query('CREATE TABLE `payment_methods` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
`name` varchar(255) NOT NULL,
`name_translations` json DEFAULT NULL,
`description` text,
`is_active` tinyint(1) DEFAULT \'1\',
`display_order` int DEFAULT \'0\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
`deleted_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `payment_schedules`;');
        $this->db->query('CREATE TABLE `payment_schedules` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`invoice_id` bigint unsigned NOT NULL,
`due_date` date NOT NULL,
`amount` decimal(14,2) DEFAULT \'0.00\',
`status` varchar(20) DEFAULT \'pending\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_payment_schedule_invoice` (`invoice_id`),
CONSTRAINT `fk_payment_schedules_invoice_id` FOREIGN KEY (`invoice_id`) REFERENCES `sales_invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `payroll_entries`;');
        $this->db->query('CREATE TABLE `payroll_entries` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`payroll_number` varchar(60) NOT NULL,
`period_start` date NOT NULL,
`period_end` date NOT NULL,
`status` varchar(30) DEFAULT \'draft\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `uq_payroll_number` (`payroll_number`),
KEY `idx_payroll_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `permissions`;');
        $this->db->query('CREATE TABLE `permissions` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
        $this->db->query('DROP TABLE IF EXISTS `pick_list_items`;');
        $this->db->query('CREATE TABLE `pick_list_items` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`pick_list_id` bigint unsigned NOT NULL,
`stock_entry_item_id` bigint unsigned DEFAULT NULL,
`product_id` bigint unsigned NOT NULL,
`qty` decimal(12,3) DEFAULT \'0.000\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `pick_lists`;');
        $this->db->query('CREATE TABLE `pick_lists` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`pick_list_number` varchar(60) NOT NULL,
`stock_entry_id` bigint unsigned DEFAULT NULL,
`source_warehouse_id` bigint unsigned DEFAULT NULL,
`status` varchar(20) DEFAULT \'open\',
`created_by` bigint unsigned DEFAULT NULL,
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `uq_pick_list_number` (`pick_list_number`),
KEY `idx_pick_list_entry` (`stock_entry_id`),
KEY `fk_pick_lists_source_warehouse_id` (`source_warehouse_id`),
CONSTRAINT `fk_pick_lists_source_warehouse_id` FOREIGN KEY (`source_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
CONSTRAINT `fk_pick_lists_stock_entry_id` FOREIGN KEY (`stock_entry_id`) REFERENCES `stock_entries` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `portal_access_tokens`;');
        $this->db->query('CREATE TABLE `portal_access_tokens` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`portal_user_id` bigint unsigned NOT NULL,
`token` varchar(120) NOT NULL,
`expires_at` datetime DEFAULT NULL,
`created_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `uq_portal_token` (`token`),
KEY `idx_portal_token_user` (`portal_user_id`),
CONSTRAINT `fk_portal_access_tokens_portal_user_id` FOREIGN KEY (`portal_user_id`) REFERENCES `portal_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `portal_users`;');
        $this->db->query('CREATE TABLE `portal_users` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`customer_id` bigint unsigned DEFAULT NULL,
`email` varchar(255) NOT NULL,
`password_hash` varchar(255) NOT NULL,
`status` varchar(30) DEFAULT \'active\',
`last_login_at` datetime DEFAULT NULL,
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `uq_portal_email` (`email`),
KEY `fk_portal_users_customer_id` (`customer_id`),
CONSTRAINT `fk_portal_users_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `pos_offline_queue`;');
        $this->db->query('CREATE TABLE `pos_offline_queue` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`temp_id` bigint unsigned DEFAULT NULL,
`device_id` bigint unsigned DEFAULT NULL,
`idempotency_key` varchar(200) NOT NULL,
`user_id` bigint unsigned DEFAULT NULL,
`branch_id` bigint unsigned DEFAULT NULL,
`payload` json DEFAULT NULL,
`status` varchar(20) DEFAULT \'pending\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `pos_payment_methods`;');
        $this->db->query('CREATE TABLE `pos_payment_methods` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`profile_id` bigint unsigned NOT NULL,
`payment_method` varchar(50) NOT NULL,
`is_allowed` tinyint(1) DEFAULT \'1\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `uq_profile_method` (`profile_id`,`payment_method`),
KEY `idx_pos_payment_profile` (`profile_id`),
CONSTRAINT `fk_pos_payment_methods_profile_id` FOREIGN KEY (`profile_id`) REFERENCES `pos_profiles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `pos_profiles`;');
        $this->db->query('CREATE TABLE `pos_profiles` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`name` varchar(150) NOT NULL,
`user_id` bigint unsigned DEFAULT NULL,
`role_id` bigint unsigned DEFAULT NULL,
`price_list_id` bigint unsigned DEFAULT NULL,
`tax_template_id` bigint unsigned DEFAULT NULL,
`warehouse_id` bigint unsigned DEFAULT NULL,
`branch_id` bigint unsigned DEFAULT NULL,
`company` varchar(150) DEFAULT NULL,
`allow_offline` tinyint(1) DEFAULT \'0\',
`require_shift` tinyint(1) DEFAULT \'1\',
`credit_limit` decimal(14,2) DEFAULT \'0.00\',
`status` varchar(20) DEFAULT \'active\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `pos_shift_logs`;');
        $this->db->query('CREATE TABLE `pos_shift_logs` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`shift_id` bigint unsigned NOT NULL,
`action` varchar(50) NOT NULL,
`message` text,
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_pos_shift_log` (`shift_id`),
CONSTRAINT `fk_pos_shift_logs_shift_id` FOREIGN KEY (`shift_id`) REFERENCES `pos_shifts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `pos_shift_payments`;');
        $this->db->query('CREATE TABLE `pos_shift_payments` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`shift_id` bigint unsigned NOT NULL,
`order_id` bigint unsigned DEFAULT NULL,
`payment_method` varchar(50) NOT NULL,
`amount` decimal(14,2) NOT NULL DEFAULT \'0.00\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `pos_shifts`;');
        $this->db->query('CREATE TABLE `pos_shifts` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`user_id` bigint unsigned NOT NULL,
`profile_id` bigint unsigned DEFAULT NULL,
`branch_id` bigint unsigned DEFAULT NULL,
`opening_balance` decimal(14,2) DEFAULT \'0.00\',
`expected_total` decimal(14,2) DEFAULT \'0.00\',
`expected_cash` decimal(14,2) DEFAULT \'0.00\',
`expected_card` decimal(14,2) DEFAULT \'0.00\',
`actual_total` decimal(14,2) DEFAULT \'0.00\',
`actual_cash` decimal(14,2) DEFAULT \'0.00\',
`actual_card` decimal(14,2) DEFAULT \'0.00\',
`discrepancy` decimal(14,2) DEFAULT \'0.00\',
`status` varchar(20) DEFAULT \'open\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `positions`;');
        $this->db->query('CREATE TABLE `positions` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `price_history`;');
        $this->db->query('CREATE TABLE `price_history` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`product_id` bigint unsigned NOT NULL,
`variant_id` bigint unsigned DEFAULT NULL,
`source_type` varchar(50) NOT NULL,
`source_id` bigint unsigned DEFAULT NULL,
`old_price` decimal(14,4) DEFAULT \'0.0000\',
`new_price` decimal(14,4) DEFAULT \'0.0000\',
`changed_by` bigint unsigned DEFAULT NULL,
`changed_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_price_history` (`product_id`,`variant_id`),
KEY `fk_price_history_variant_id` (`variant_id`),
CONSTRAINT `fk_price_history_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
CONSTRAINT `fk_price_history_variant_id` FOREIGN KEY (`variant_id`) REFERENCES `product_variants_v2` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `price_list_items`;');
        $this->db->query('CREATE TABLE `price_list_items` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`price_list_id` bigint unsigned DEFAULT NULL,
`product_id` bigint unsigned DEFAULT NULL,
`variant_id` bigint unsigned DEFAULT NULL,
`price` decimal(10,2) NOT NULL,
`discount_percent` decimal(5,2) DEFAULT \'0.00\',
`discount_amount` decimal(10,2) DEFAULT \'0.00\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `price_lists`;');
        $this->db->query('CREATE TABLE `price_lists` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`name` varchar(255) NOT NULL,
`type` varchar(50) DEFAULT \'custom\',
`description` text,
`apply_to_groups` json DEFAULT NULL,
`start_date` date DEFAULT NULL,
`end_date` date DEFAULT NULL,
`priority` int DEFAULT \'0\',
`is_active` tinyint(1) DEFAULT \'1\',
`is_system` tinyint(1) DEFAULT \'0\',
`formula` text,
`base_price_list_id` bigint unsigned DEFAULT NULL,
`auto_update` tinyint(1) DEFAULT \'0\',
`rounding_rule` varchar(50) DEFAULT \'none\',
`config` json DEFAULT NULL,
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
`deleted_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `fk_price_lists_base_price_list_id` (`base_price_list_id`),
CONSTRAINT `fk_price_lists_base_price_list_id` FOREIGN KEY (`base_price_list_id`) REFERENCES `price_lists` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `pricing_rules`;');
        $this->db->query('CREATE TABLE `pricing_rules` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`name` varchar(150) NOT NULL,
`condition_type` varchar(50) DEFAULT \'amount\',
`customer_id` bigint unsigned DEFAULT NULL,
`project_id` bigint unsigned DEFAULT NULL,
`product_id` bigint unsigned DEFAULT NULL,
`variant_id` bigint unsigned DEFAULT NULL,
`min_qty` decimal(12,3) DEFAULT \'0.000\',
`start_date` date DEFAULT NULL,
`end_date` date DEFAULT NULL,
`price` decimal(14,4) DEFAULT \'0.0000\',
`discount_percent` decimal(6,3) DEFAULT \'0.000\',
`priority` int DEFAULT \'100\',
`is_active` tinyint(1) DEFAULT \'1\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `product_attribute_options`;');
        $this->db->query('CREATE TABLE `product_attribute_options` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`attribute_id` bigint unsigned DEFAULT NULL,
`option_name` varchar(255) DEFAULT NULL,
`color_code` varchar(50) DEFAULT NULL,
`sort_order` int DEFAULT \'0\',
`status` varchar(20) DEFAULT \'active\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
`deleted_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `fk_product_attribute_op_attribute_id` (`attribute_id`),
CONSTRAINT `fk_product_attribute_op_attribute_id` FOREIGN KEY (`attribute_id`) REFERENCES `product_attributes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `product_attribute_values`;');
        $this->db->query('CREATE TABLE `product_attribute_values` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `product_attributes`;');
        $this->db->query('CREATE TABLE `product_attributes` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`name` varchar(255) DEFAULT NULL,
`slug` varchar(255) DEFAULT NULL,
`attribute_key` varchar(100) DEFAULT NULL,
`type` varchar(50) DEFAULT NULL,
`is_required` tinyint DEFAULT \'0\',
`is_filterable` tinyint DEFAULT \'0\',
`sort_order` int DEFAULT \'0\',
`status` varchar(20) DEFAULT \'active\',
`is_visible` tinyint DEFAULT \'1\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
`deleted_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `product_batches`;');
        $this->db->query('CREATE TABLE `product_batches` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`product_id` bigint unsigned NOT NULL,
`variant_id` bigint unsigned DEFAULT NULL,
`branch_id` bigint unsigned DEFAULT NULL,
`warehouse_id` bigint unsigned DEFAULT NULL,
`batch_number` varchar(120) NOT NULL,
`manufacture_date` date DEFAULT NULL,
`expiry_date` date DEFAULT NULL,
`initial_quantity` decimal(12,3) DEFAULT \'0.000\',
`current_quantity` decimal(12,3) DEFAULT \'0.000\',
`cost_per_unit` decimal(14,4) DEFAULT \'0.0000\',
`supplier_name` varchar(255) DEFAULT NULL,
`reference_document` varchar(160) DEFAULT NULL,
`status` varchar(30) DEFAULT \'active\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `product_bundles`;');
        $this->db->query('CREATE TABLE `product_bundles` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`parent_product_id` bigint unsigned NOT NULL,
`child_product_id` bigint unsigned NOT NULL,
`quantity` decimal(12,3) NOT NULL DEFAULT \'1.000\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `product_bundles_parent_product_id_foreign` (`parent_product_id`),
KEY `product_bundles_child_product_id_foreign` (`child_product_id`),
CONSTRAINT `product_bundles_child_product_id_foreign` FOREIGN KEY (`child_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
CONSTRAINT `product_bundles_parent_product_id_foreign` FOREIGN KEY (`parent_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;');
        $this->db->query('DROP TABLE IF EXISTS `product_channels`;');
        $this->db->query('CREATE TABLE `product_channels` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`product_id` bigint unsigned NOT NULL,
`channel` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
`channel_product_id` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
`status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT \'disconnected\',
`sync_status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT \'synced\',
`last_sync_at` datetime DEFAULT NULL,
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `product_id_channel` (`product_id`,`channel`),
CONSTRAINT `product_channels_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;');
        $this->db->query('DROP TABLE IF EXISTS `product_categories`;');
        $this->db->query('CREATE TABLE `product_categories` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`parent_id` bigint unsigned DEFAULT NULL,
`product_id` bigint unsigned DEFAULT \'0\',
`level` tinyint DEFAULT \'1\',
`is_variant_group` tinyint DEFAULT \'0\',
`code` varchar(50) DEFAULT NULL,
`name` varchar(255) DEFAULT NULL,
`slug` varchar(255) DEFAULT NULL,
`description` text,
`image` varchar(255) DEFAULT NULL,
`sort_order` int DEFAULT \'0\',
`status` enum(\'active\',\'inactive\') DEFAULT \'active\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
`deleted_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `fk_product_categories_parent_id` (`parent_id`),
CONSTRAINT `fk_product_categories_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `product_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `product_category_links`;');
        $this->db->query('CREATE TABLE `product_category_links` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`product_id` bigint unsigned DEFAULT NULL,
`category_id` bigint unsigned DEFAULT NULL,
`created_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `uq_product_category_links` (`product_id`,`category_id`),
KEY `fk_product_category_lin_category_id` (`category_id`),
CONSTRAINT `fk_product_category_lin_category_id` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
CONSTRAINT `fk_product_category_lin_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `product_images`;');
        $this->db->query('CREATE TABLE `product_images` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`product_id` bigint unsigned DEFAULT NULL,
`variant_id` bigint unsigned DEFAULT NULL,
`image_path` varchar(255) DEFAULT NULL,
`image_url` varchar(255) DEFAULT NULL,
`is_primary` tinyint DEFAULT \'0\',
`sort_order` int DEFAULT \'0\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `product_prices`;');
        $this->db->query('CREATE TABLE `product_prices` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`product_id` bigint unsigned NOT NULL,
`variant_id` bigint unsigned DEFAULT NULL,
`customer_group_id` bigint unsigned DEFAULT NULL COMMENT \'NULL = giá chung\',
`price_type` enum(\'retail\',\'wholesale\',\'special\') COLLATE utf8mb4_unicode_ci DEFAULT \'retail\',
`price` decimal(15,2) NOT NULL,
`min_quantity` int DEFAULT \'1\' COMMENT \'SL tối thiểu\',
`valid_from` date DEFAULT NULL,
`valid_to` date DEFAULT NULL,
`status` enum(\'active\',\'inactive\') COLLATE utf8mb4_unicode_ci DEFAULT \'active\',
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
CONSTRAINT `fk_product_prices_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants_v2` (`id`) ON DELETE CASCADE,
CONSTRAINT `fk_product_prices_customer_group` FOREIGN KEY (`customer_group_id`) REFERENCES `customer_groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
        $this->db->query('DROP TABLE IF EXISTS `product_serial_numbers`;');
        $this->db->query('CREATE TABLE `product_serial_numbers` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`product_id` bigint unsigned NOT NULL,
`variant_id` bigint unsigned DEFAULT NULL,
`batch_id` bigint unsigned DEFAULT NULL,
`serial_number` varchar(160) NOT NULL,
`status` varchar(30) DEFAULT \'available\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `product_stock_by_branch`;');
        $this->db->query('CREATE TABLE `product_stock_by_branch` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`product_id` bigint unsigned NOT NULL,
`branch_id` bigint unsigned NOT NULL,
`stock_quantity` int DEFAULT \'0\' COMMENT \'Số lượng tồn\',
`alert_stock` int DEFAULT \'10\' COMMENT \'Ngưỡng cảnh báo\',
`reserved_stock` int DEFAULT \'0\' COMMENT \'Hàng đang giữ\',
`available_stock` int DEFAULT \'0\' COMMENT \'Có thể bán\',
`expiry_days` int DEFAULT NULL COMMENT \'Dự kiến hết hàng (ngày)\',
`last_stock_date` datetime DEFAULT NULL COMMENT \'Lần cập nhật tồn cuối\',
`status` enum(\'in_stock\',\'low_stock\',\'out_of_stock\') COLLATE utf8mb4_unicode_ci DEFAULT \'in_stock\',
`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
`updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`id`),
UNIQUE KEY `unique_product_branch` (`product_id`,`branch_id`),
KEY `idx_branch` (`branch_id`),
KEY `idx_status` (`status`),
CONSTRAINT `fk_psbb_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
CONSTRAINT `fk_psbb_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
        $this->db->query('DROP TABLE IF EXISTS `product_variants_v2`;');
        $this->db->query('CREATE TABLE `product_variants_v2` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`product_id` bigint unsigned NOT NULL,
`variant_name` varchar(255) DEFAULT NULL,
`variant_signature` varchar(255) DEFAULT NULL,
`sku` varchar(100) DEFAULT NULL,
`barcode` varchar(100) DEFAULT NULL,
`price` decimal(15,2) DEFAULT \'0.00\',
`cost_price` decimal(15,2) DEFAULT \'0.00\',
`stock_quantity` decimal(10,2) DEFAULT \'0.00\',
`min_stock` decimal(10,2) DEFAULT \'0.00\',
`max_stock` decimal(10,2) DEFAULT \'0.00\',
`image_url` varchar(255) DEFAULT NULL,
`attributes` text,
`status` enum(\'active\',\'inactive\') DEFAULT \'active\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
`deleted_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_variants_sku_deleted_at` (`sku`,`deleted_at`),
KEY `fk_product_variants_v2_product_id` (`product_id`),
CONSTRAINT `fk_product_variants_v2_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `product_warranties`;');
        $this->db->query('CREATE TABLE `product_warranties` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`product_id` bigint unsigned NOT NULL,
`order_id` bigint unsigned DEFAULT NULL,
`customer_id` bigint unsigned DEFAULT NULL,
`serial_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT \'Số serial\',
`warranty_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT \'Mã phiếu BH\',
`start_date` date NOT NULL,
`end_date` date NOT NULL,
`status` enum(\'active\',\'expired\',\'claimed\',\'cancelled\') COLLATE utf8mb4_unicode_ci DEFAULT \'active\',
`note` text COLLATE utf8mb4_unicode_ci,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
        $this->db->query('DROP TABLE IF EXISTS `products`;');
        $this->db->query('CREATE TABLE `products` (
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
`unit_conversion` decimal(10,2) DEFAULT \'1.00\',
`has_variants` tinyint(1) DEFAULT \'0\',
`related_product_codes` text,
`image` varchar(255) DEFAULT NULL,
`images` text,
`image_count` int DEFAULT \'0\',
`weight` decimal(10,2) DEFAULT NULL,
`dimensions` varchar(100) DEFAULT NULL,
`description` text,
`content` text,
`is_active` tinyint(1) DEFAULT \'1\',
`is_available_online` tinyint(1) DEFAULT \'0\',
`is_featured` tinyint(1) DEFAULT \'0\',
`status` enum(\'active\',\'inactive\') DEFAULT \'active\',
`selling_price` decimal(10,2) DEFAULT \'0.00\',
`commission_percent` decimal(5,2) DEFAULT \'0.00\',
`commission_amount` decimal(15,2) DEFAULT \'0.00\',
`wholesale_price` decimal(10,2) DEFAULT \'0.00\',
`purchase_price` decimal(10,2) DEFAULT \'0.00\',
`stock_quantity` int DEFAULT \'0\',
`alert_stock` int DEFAULT \'0\',
`expiry_days` int DEFAULT NULL,
`customer_ordered` int DEFAULT \'0\',
`expected_out_date` varchar(50) DEFAULT NULL,
`min_stock_alert` int DEFAULT \'0\',
`max_stock_alert` int DEFAULT \'0\',
`meta_title` varchar(255) DEFAULT NULL,
`meta_description` varchar(500) DEFAULT NULL,
`meta_keywords` varchar(500) DEFAULT NULL,
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
`deleted_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_products_code_deleted_at` (`code`,`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `project_price_lists`;');
        $this->db->query('CREATE TABLE `project_price_lists` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`project_id` bigint unsigned NOT NULL,
`price_list_id` bigint unsigned DEFAULT NULL,
`valid_from` date DEFAULT NULL,
`valid_to` date DEFAULT NULL,
`is_active` tinyint(1) DEFAULT \'1\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_ppl_project` (`project_id`,`price_list_id`),
KEY `fk_project_price_lists_price_list_id` (`price_list_id`),
CONSTRAINT `fk_project_price_lists_price_list_id` FOREIGN KEY (`price_list_id`) REFERENCES `price_lists` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
CONSTRAINT `fk_project_price_lists_project_id` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `projects`;');
        $this->db->query('CREATE TABLE `projects` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`project_name` varchar(255) NOT NULL,
`project_code` varchar(80) DEFAULT NULL,
`customer_id` bigint unsigned DEFAULT NULL,
`status` varchar(30) DEFAULT \'open\',
`progress` decimal(5,2) DEFAULT \'0.00\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `purchase_invoice_items`;');
        $this->db->query('CREATE TABLE `purchase_invoice_items` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`invoice_id` bigint unsigned NOT NULL,
`product_id` bigint unsigned DEFAULT NULL,
`description` varchar(255) DEFAULT NULL,
`quantity` decimal(12,2) DEFAULT \'0.00\',
`rate` decimal(14,2) DEFAULT \'0.00\',
`amount` decimal(14,2) DEFAULT \'0.00\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_purchase_invoice_item_invoice` (`invoice_id`),
KEY `fk_purchase_invoice_ite_product_id` (`product_id`),
CONSTRAINT `fk_purchase_invoice_ite_invoice_id` FOREIGN KEY (`invoice_id`) REFERENCES `purchase_invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
CONSTRAINT `fk_purchase_invoice_ite_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `purchase_invoice_taxes`;');
        $this->db->query('CREATE TABLE `purchase_invoice_taxes` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`invoice_id` bigint unsigned NOT NULL,
`tax_name` varchar(150) NOT NULL,
`rate_percent` decimal(8,3) DEFAULT \'0.000\',
`amount` decimal(14,2) DEFAULT \'0.00\',
`template_id` bigint unsigned DEFAULT NULL,
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_purchase_invoice_tax_invoice` (`invoice_id`),
KEY `fk_purchase_invoice_tax_template_id` (`template_id`),
CONSTRAINT `fk_purchase_invoice_tax_invoice_id` FOREIGN KEY (`invoice_id`) REFERENCES `purchase_invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
CONSTRAINT `fk_purchase_invoice_tax_template_id` FOREIGN KEY (`template_id`) REFERENCES `tax_templates` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `purchase_invoices`;');
        $this->db->query('CREATE TABLE `purchase_invoices` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`invoice_number` varchar(50) NOT NULL,
`supplier_id` bigint unsigned DEFAULT NULL,
`posting_date` date NOT NULL,
`due_date` date DEFAULT NULL,
`status` varchar(20) DEFAULT \'draft\',
`currency` varchar(10) DEFAULT \'VND\',
`exchange_rate` decimal(12,4) DEFAULT \'1.0000\',
`total` decimal(14,2) DEFAULT \'0.00\',
`taxes_total` decimal(14,2) DEFAULT \'0.00\',
`grand_total` decimal(14,2) DEFAULT \'0.00\',
`rounding_adjustment` decimal(12,2) DEFAULT \'0.00\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `purchase_order_items`;');
        $this->db->query('CREATE TABLE `purchase_order_items` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`purchase_order_id` bigint unsigned NOT NULL,
`product_id` bigint unsigned DEFAULT NULL,
`quantity` decimal(14,3) DEFAULT \'0.000\',
`received_quantity` decimal(14,3) DEFAULT \'0.000\',
`rate` decimal(14,2) DEFAULT \'0.00\',
`amount` decimal(14,2) DEFAULT \'0.00\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
`unit_price` decimal(14,2) DEFAULT NULL,
`total_price` decimal(14,2) DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_po_item_po` (`purchase_order_id`),
KEY `fk_purchase_order_items_product_id` (`product_id`),
CONSTRAINT `fk_purchase_order_items_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
CONSTRAINT `fk_purchase_order_items_purchase_order_id` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `purchase_orders`;');
        $this->db->query('CREATE TABLE `purchase_orders` (
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
`total` decimal(12,2) NOT NULL DEFAULT \'0.00\',
`subtotal` decimal(15,2) NOT NULL DEFAULT \'0.00\',
`tax_amount` decimal(15,2) NOT NULL DEFAULT \'0.00\',
`shipping_fee` decimal(15,2) NOT NULL DEFAULT \'0.00\',
`status` varchar(50) DEFAULT \'draft\',
`received_at` datetime DEFAULT NULL,
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
`deleted_at` datetime DEFAULT NULL,
`total_amount` decimal(14,2) DEFAULT \'0.00\',
`paid_amount` decimal(14,2) DEFAULT \'0.00\',
`payment_status` varchar(50) DEFAULT \'unpaid\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `purchase_suggestions`;');
        $this->db->query('CREATE TABLE `purchase_suggestions` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`reorder_level_id` bigint unsigned DEFAULT NULL,
`product_id` bigint unsigned NOT NULL,
`variant_id` bigint unsigned DEFAULT NULL,
`branch_id` bigint unsigned NOT NULL,
`generated_for_date` date NOT NULL,
`suggested_qty` decimal(12,3) DEFAULT \'0.000\',
`on_hand_qty` decimal(12,3) DEFAULT \'0.000\',
`reserved_qty` decimal(12,3) DEFAULT \'0.000\',
`available_qty` decimal(12,3) DEFAULT \'0.000\',
`min_level` decimal(12,3) DEFAULT \'0.000\',
`max_level` decimal(12,3) DEFAULT \'0.000\',
`safety_stock` decimal(12,3) DEFAULT \'0.000\',
`status` varchar(30) DEFAULT \'pending\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `quality_inspection_items`;');
        $this->db->query('CREATE TABLE `quality_inspection_items` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `quality_inspections`;');
        $this->db->query('CREATE TABLE `quality_inspections` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`reference_type` varchar(80) NOT NULL,
`reference_id` bigint unsigned NOT NULL,
`status` varchar(20) DEFAULT \'draft\',
`result` varchar(20) DEFAULT \'pending\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `quality_parameters`;');
        $this->db->query('CREATE TABLE `quality_parameters` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`name` varchar(150) NOT NULL,
`uom` varchar(50) DEFAULT NULL,
`min_value` decimal(14,4) DEFAULT NULL,
`max_value` decimal(14,4) DEFAULT NULL,
`specification` varchar(255) DEFAULT NULL,
`is_active` tinyint(1) DEFAULT \'1\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `uq_quality_parameter_name` (`name`),
KEY `idx_quality_parameter_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `quotation_items`;');
        $this->db->query('CREATE TABLE `quotation_items` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`quotation_id` bigint unsigned NOT NULL,
`product_id` bigint unsigned NOT NULL,
`quantity` decimal(14,2) DEFAULT \'1.00\',
`price` decimal(14,2) DEFAULT \'0.00\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_quote_items_quote` (`quotation_id`),
KEY `fk_quotation_items_product_id` (`product_id`),
CONSTRAINT `fk_quotation_items_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
CONSTRAINT `fk_quotation_items_quotation_id` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `quotations`;');
        $this->db->query('CREATE TABLE `quotations` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`quote_number` varchar(50) DEFAULT NULL,
`opportunity_id` bigint unsigned DEFAULT NULL,
`customer_id` bigint unsigned DEFAULT NULL,
`lead_id` bigint unsigned DEFAULT NULL,
`status` varchar(50) DEFAULT \'draft\',
`validity_date` date DEFAULT NULL,
`subtotal` decimal(14,2) DEFAULT \'0.00\',
`discount_total` decimal(14,2) DEFAULT \'0.00\',
`tax_total` decimal(14,2) DEFAULT \'0.00\',
`total` decimal(14,2) DEFAULT \'0.00\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `fk_quotations_customer_id` (`customer_id`),
KEY `fk_quotations_lead_id` (`lead_id`),
KEY `fk_quotations_opportunity_id` (`opportunity_id`),
CONSTRAINT `fk_quotations_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
CONSTRAINT `fk_quotations_lead_id` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
CONSTRAINT `fk_quotations_opportunity_id` FOREIGN KEY (`opportunity_id`) REFERENCES `opportunities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `regional_tax_rules`;');
        $this->db->query('CREATE TABLE `regional_tax_rules` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`country` varchar(5) NOT NULL,
`rule_json` json NOT NULL,
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `uq_region_country` (`country`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `reorder_levels`;');
        $this->db->query('CREATE TABLE `reorder_levels` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`product_id` bigint unsigned NOT NULL,
`variant_id` bigint unsigned DEFAULT NULL,
`branch_id` bigint unsigned NOT NULL,
`min_level` decimal(12,3) DEFAULT \'0.000\',
`max_level` decimal(12,3) DEFAULT \'0.000\',
`safety_stock` decimal(12,3) DEFAULT \'0.000\',
`is_active` tinyint(1) DEFAULT \'1\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `return_items`;');
        $this->db->query('CREATE TABLE `return_items` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `returns`;');
        $this->db->query('CREATE TABLE `returns` (
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
`lock_version` int DEFAULT \'0\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `role_has_permissions`;');
        $this->db->query('CREATE TABLE `role_has_permissions` (
`permission_id` bigint unsigned NOT NULL,
`role_id` bigint unsigned NOT NULL,
PRIMARY KEY (`permission_id`,`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
        $this->db->query('DROP TABLE IF EXISTS `roles`;');
        $this->db->query('CREATE TABLE `roles` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`guard_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
`is_system` tinyint(1) NOT NULL DEFAULT \'0\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
`deleted_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
        $this->db->query('DROP TABLE IF EXISTS `salary_components`;');
        $this->db->query('CREATE TABLE `salary_components` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`salary_slip_id` bigint unsigned NOT NULL,
`component_name` varchar(150) NOT NULL,
`component_type` varchar(20) DEFAULT \'earning\',
`amount` decimal(14,2) DEFAULT \'0.00\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_salary_component_slip` (`salary_slip_id`),
CONSTRAINT `fk_salary_components_salary_slip_id` FOREIGN KEY (`salary_slip_id`) REFERENCES `salary_slips` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `salary_slips`;');
        $this->db->query('CREATE TABLE `salary_slips` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`payroll_entry_id` bigint unsigned NOT NULL,
`employee_id` bigint unsigned NOT NULL,
`status` varchar(30) DEFAULT \'draft\',
`period_start` date DEFAULT NULL,
`period_end` date DEFAULT NULL,
`total_earnings` decimal(14,2) DEFAULT \'0.00\',
`total_deductions` decimal(14,2) DEFAULT \'0.00\',
`net_pay` decimal(14,2) DEFAULT \'0.00\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `uq_salary_slip` (`payroll_entry_id`,`employee_id`),
KEY `fk_salary_slips_employee_id` (`employee_id`),
CONSTRAINT `fk_salary_slips_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
CONSTRAINT `fk_salary_slips_payroll_entry_id` FOREIGN KEY (`payroll_entry_id`) REFERENCES `payroll_entries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `sales_invoice_items`;');
        $this->db->query('CREATE TABLE `sales_invoice_items` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`invoice_id` bigint unsigned NOT NULL,
`product_id` bigint unsigned DEFAULT NULL,
`description` varchar(255) DEFAULT NULL,
`quantity` decimal(12,2) DEFAULT \'0.00\',
`rate` decimal(14,2) DEFAULT \'0.00\',
`amount` decimal(14,2) DEFAULT \'0.00\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_sales_invoice_item_invoice` (`invoice_id`),
KEY `fk_sales_invoice_items_product_id` (`product_id`),
CONSTRAINT `fk_sales_invoice_items_invoice_id` FOREIGN KEY (`invoice_id`) REFERENCES `sales_invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
CONSTRAINT `fk_sales_invoice_items_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `sales_invoice_taxes`;');
        $this->db->query('CREATE TABLE `sales_invoice_taxes` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`invoice_id` bigint unsigned NOT NULL,
`tax_name` varchar(150) NOT NULL,
`rate_percent` decimal(8,3) DEFAULT \'0.000\',
`amount` decimal(14,2) DEFAULT \'0.00\',
`template_id` bigint unsigned DEFAULT NULL,
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_sales_invoice_tax_invoice` (`invoice_id`),
KEY `fk_sales_invoice_taxes_template_id` (`template_id`),
CONSTRAINT `fk_sales_invoice_taxes_invoice_id` FOREIGN KEY (`invoice_id`) REFERENCES `sales_invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
CONSTRAINT `fk_sales_invoice_taxes_template_id` FOREIGN KEY (`template_id`) REFERENCES `tax_templates` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `sales_invoices`;');
        $this->db->query('CREATE TABLE `sales_invoices` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`invoice_number` varchar(50) NOT NULL,
`customer_id` bigint unsigned NOT NULL,
`posting_date` date NOT NULL,
`due_date` date DEFAULT NULL,
`status` varchar(20) DEFAULT \'draft\',
`currency` varchar(10) DEFAULT \'VND\',
`exchange_rate` decimal(12,4) DEFAULT \'1.0000\',
`total` decimal(14,2) DEFAULT \'0.00\',
`taxes_total` decimal(14,2) DEFAULT \'0.00\',
`grand_total` decimal(14,2) DEFAULT \'0.00\',
`rounding_adjustment` decimal(12,2) DEFAULT \'0.00\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `scheduler_rules`;');
        $this->db->query('CREATE TABLE `scheduler_rules` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`name` varchar(150) NOT NULL,
`cron_expression` varchar(60) NOT NULL,
`handler` varchar(120) NOT NULL,
`is_active` tinyint(1) DEFAULT \'1\',
`last_run_at` datetime DEFAULT NULL,
`next_run_at` datetime DEFAULT NULL,
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `uq_scheduler_name` (`name`),
KEY `idx_scheduler_active` (`is_active`,`next_run_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `sessions`;');
        $this->db->query('CREATE TABLE `sessions` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
        $this->db->query('DROP TABLE IF EXISTS `stock_bins`;');
        $this->db->query('CREATE TABLE `stock_bins` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`product_id` bigint unsigned NOT NULL,
`variant_id` bigint unsigned DEFAULT NULL,
`branch_id` bigint unsigned NOT NULL,
`batch_id` bigint unsigned DEFAULT NULL,
`on_hand_qty` decimal(12,3) DEFAULT \'0.000\',
`reserved_qty` decimal(12,3) DEFAULT \'0.000\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `stock_entries`;');
        $this->db->query('CREATE TABLE `stock_entries` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`entry_number` varchar(60) NOT NULL,
`type` varchar(20) NOT NULL,
`status` varchar(20) DEFAULT \'draft\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `stock_entry_items`;');
        $this->db->query('CREATE TABLE `stock_entry_items` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`stock_entry_id` bigint unsigned NOT NULL,
`product_id` bigint unsigned NOT NULL,
`variant_id` bigint unsigned DEFAULT NULL,
`qty` decimal(12,3) DEFAULT \'0.000\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `stock_ledgers`;');
        $this->db->query('CREATE TABLE `stock_ledgers` (
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
`reference_seq` int unsigned DEFAULT \'1\',
`qty_delta` decimal(12,3) DEFAULT \'0.000\',
`unit_cost` decimal(14,4) DEFAULT \'0.0000\',
`total_cost` decimal(14,4) DEFAULT \'0.0000\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `stock_reconciliation_items`;');
        $this->db->query('CREATE TABLE `stock_reconciliation_items` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`reconciliation_id` bigint unsigned NOT NULL,
`product_id` bigint unsigned NOT NULL,
`variant_id` bigint unsigned DEFAULT NULL,
`batch_id` bigint unsigned DEFAULT NULL,
`counted_qty` decimal(12,3) DEFAULT \'0.000\',
`current_qty` decimal(12,3) DEFAULT \'0.000\',
`variance_qty` decimal(12,3) DEFAULT \'0.000\',
`unit_cost` decimal(14,4) DEFAULT \'0.0000\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `stock_reconciliations`;');
        $this->db->query('CREATE TABLE `stock_reconciliations` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`recon_number` varchar(60) NOT NULL,
`branch_id` bigint unsigned NOT NULL,
`status` varchar(20) DEFAULT \'draft\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `subcontracting_materials`;');
        $this->db->query('CREATE TABLE `subcontracting_materials` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`subcontracting_order_id` bigint unsigned NOT NULL,
`material_product_id` bigint unsigned DEFAULT NULL,
`quantity` decimal(14,3) DEFAULT \'0.000\',
`issued_quantity` decimal(14,3) DEFAULT \'0.000\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_subcon_material` (`subcontracting_order_id`),
KEY `fk_subcontracting_mater_material_product_id` (`material_product_id`),
CONSTRAINT `fk_subcontracting_mater_material_product_id` FOREIGN KEY (`material_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
CONSTRAINT `fk_subcontracting_mater_subcontracting_order` FOREIGN KEY (`subcontracting_order_id`) REFERENCES `subcontracting_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `subcontracting_orders`;');
        $this->db->query('CREATE TABLE `subcontracting_orders` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`order_number` varchar(50) NOT NULL,
`supplier_id` bigint unsigned DEFAULT NULL,
`product_id` bigint unsigned DEFAULT NULL,
`quantity` decimal(14,3) DEFAULT \'0.000\',
`status` varchar(30) DEFAULT \'draft\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `uq_subcon_order` (`order_number`),
KEY `fk_subcontracting_order_product_id` (`product_id`),
KEY `fk_subcontracting_orders_supplier` (`supplier_id`),
CONSTRAINT `fk_subcontracting_order_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
CONSTRAINT `fk_subcontracting_orders_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `subscription_cycles`;');
        $this->db->query('CREATE TABLE `subscription_cycles` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`subscription_id` bigint unsigned NOT NULL,
`run_date` date NOT NULL,
`order_id` bigint unsigned DEFAULT NULL,
`status` varchar(30) DEFAULT \'processed\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
`deleted_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `uq_subscription_cycle` (`subscription_id`,`run_date`),
KEY `idx_cycle_status` (`status`),
KEY `fk_subscription_cycles_order_id` (`order_id`),
CONSTRAINT `fk_subscription_cycles_order_id` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
CONSTRAINT `fk_subscription_cycles_subscription_id` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `subscriptions`;');
        $this->db->query('CREATE TABLE `subscriptions` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`customer_id` bigint unsigned DEFAULT NULL,
`template_id` bigint unsigned DEFAULT NULL,
`plan_name` varchar(150) NOT NULL,
`interval_days` int DEFAULT \'30\',
`next_run_at` datetime DEFAULT NULL,
`status` varchar(30) DEFAULT \'active\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_subscription_status` (`status`),
KEY `idx_subscription_next` (`next_run_at`),
KEY `fk_subscriptions_customer_id` (`customer_id`),
KEY `fk_subscriptions_template_id` (`template_id`),
CONSTRAINT `fk_subscriptions_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
CONSTRAINT `fk_subscriptions_template_id` FOREIGN KEY (`template_id`) REFERENCES `order_templates` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `suppliers`;');
        $this->db->query('CREATE TABLE `suppliers` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`name_vi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`name_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`tax_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`email` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`region` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT \'active\',
`parent_id` bigint unsigned DEFAULT NULL,
`created_by` bigint unsigned DEFAULT NULL,
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
`deleted_at` datetime DEFAULT NULL,
`type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT \'company\',
`payment_terms` int DEFAULT \'0\',
PRIMARY KEY (`id`),
UNIQUE KEY `uq_supplier_code` (`code`),
KEY `suppliers_parent_id_foreign` (`parent_id`),
KEY `suppliers_created_by_foreign` (`created_by`),
CONSTRAINT `suppliers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE SET NULL,
CONSTRAINT `suppliers_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE ON UPDATE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
        $this->db->query('DROP TABLE IF EXISTS `support_tickets`;');
        $this->db->query('CREATE TABLE `support_tickets` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`subject` varchar(255) NOT NULL,
`customer_id` bigint unsigned DEFAULT NULL,
`lead_id` bigint unsigned DEFAULT NULL,
`priority` varchar(20) DEFAULT \'medium\',
`status` varchar(30) DEFAULT \'open\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `tasks`;');
        $this->db->query('CREATE TABLE `tasks` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`project_id` bigint unsigned DEFAULT NULL,
`parent_id` bigint unsigned DEFAULT NULL,
`task_name` varchar(255) NOT NULL,
`status` varchar(30) DEFAULT \'open\',
`progress` decimal(5,2) DEFAULT \'0.00\',
`estimated_hours` decimal(10,2) DEFAULT \'0.00\',
`actual_hours` decimal(10,2) DEFAULT \'0.00\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `tax_certificate_records`;');
        $this->db->query('CREATE TABLE `tax_certificate_records` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`certificate_number` varchar(80) NOT NULL,
`country` varchar(5) NOT NULL,
`party_type` varchar(60) DEFAULT NULL,
`party_id` bigint unsigned DEFAULT NULL,
`base_amount` decimal(14,2) DEFAULT \'0.00\',
`withheld_amount` decimal(14,2) DEFAULT \'0.00\',
`issue_date` date DEFAULT NULL,
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `uq_tax_certificate` (`certificate_number`),
KEY `fk_tax_certificate_reco_party_id` (`party_id`),
CONSTRAINT `fk_tax_certificate_reco_party_id` FOREIGN KEY (`party_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `tax_charges`;');
        $this->db->query('CREATE TABLE `tax_charges` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`template_id` bigint unsigned DEFAULT NULL,
`name` varchar(150) NOT NULL,
`rate_percent` decimal(8,3) DEFAULT \'0.000\',
`charge_type` varchar(20) DEFAULT \'on_net_total\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_tax_charges_template` (`template_id`),
CONSTRAINT `fk_tax_charges_template_id` FOREIGN KEY (`template_id`) REFERENCES `tax_templates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `tax_template_items`;');
        $this->db->query('CREATE TABLE `tax_template_items` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`template_id` bigint unsigned DEFAULT NULL,
`tax_name` varchar(150) NOT NULL,
`rate_percent` decimal(8,3) DEFAULT \'0.000\',
`charge_type` varchar(30) DEFAULT \'on_net_total\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_tax_template_items` (`template_id`),
CONSTRAINT `fk_tax_template_items_template_id` FOREIGN KEY (`template_id`) REFERENCES `tax_templates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `tax_templates`;');
        $this->db->query('CREATE TABLE `tax_templates` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`name` varchar(150) NOT NULL,
`rate_percent` decimal(8,3) DEFAULT \'0.000\',
`is_inclusive` tinyint(1) DEFAULT \'0\',
`rounding_rule` varchar(20) DEFAULT \'nearest\',
`status` varchar(20) DEFAULT \'active\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `temp_queue`;');
        $this->db->query('CREATE TABLE `temp_queue` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`device_id` bigint unsigned DEFAULT NULL,
`payload` json DEFAULT NULL,
`status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT \'pending\',
`expired_at` datetime DEFAULT NULL,
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `temp_queue_device_id_foreign` (`device_id`),
CONSTRAINT `temp_queue_device_id_foreign` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE ON UPDATE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
        $this->db->query('DROP TABLE IF EXISTS `ticket_communications`;');
        $this->db->query('CREATE TABLE `ticket_communications` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`ticket_id` bigint unsigned NOT NULL,
`type` varchar(30) DEFAULT \'note\',
`content` text,
`attachments` text,
`created_by` bigint unsigned DEFAULT NULL,
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_ticket_comm_ticket` (`ticket_id`),
CONSTRAINT `fk_ticket_communication_ticket_id` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `ticket_events`;');
        $this->db->query('CREATE TABLE `ticket_events` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `timesheet_details`;');
        $this->db->query('CREATE TABLE `timesheet_details` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`timesheet_id` bigint unsigned NOT NULL,
`project_id` bigint unsigned DEFAULT NULL,
`task_id` bigint unsigned DEFAULT NULL,
`activity_type_id` bigint unsigned DEFAULT NULL,
`work_date` date DEFAULT NULL,
`hours` decimal(10,2) DEFAULT \'0.00\',
`billing_rate` decimal(12,2) DEFAULT \'0.00\',
`cost_rate` decimal(12,2) DEFAULT \'0.00\',
`billable_amount` decimal(14,2) DEFAULT \'0.00\',
`cost_amount` decimal(14,2) DEFAULT \'0.00\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `timesheets`;');
        $this->db->query('CREATE TABLE `timesheets` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`timesheet_number` varchar(60) NOT NULL,
`project_id` bigint unsigned DEFAULT NULL,
`employee_id` bigint unsigned DEFAULT NULL,
`status` varchar(30) DEFAULT \'draft\',
`total_hours` decimal(12,2) DEFAULT \'0.00\',
`total_billable` decimal(14,2) DEFAULT \'0.00\',
`total_cost` decimal(14,2) DEFAULT \'0.00\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `users`;');
        $this->db->query('CREATE TABLE `users` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`username` varchar(100) DEFAULT NULL,
`email` varchar(255) DEFAULT NULL,
`password` varchar(255) DEFAULT NULL,
`full_name` varchar(255) DEFAULT NULL,
`phone` varchar(20) DEFAULT NULL,
`avatar` varchar(255) DEFAULT NULL,
`branch_id` bigint unsigned DEFAULT NULL,
`status` enum(\'active\',\'inactive\',\'suspended\') DEFAULT \'active\',
`last_login_at` datetime DEFAULT NULL,
`last_login_ip` varchar(45) DEFAULT NULL,
`remember_token` varchar(100) DEFAULT NULL,
`two_factor_secret` varchar(255) DEFAULT NULL,
`two_factor_enabled` tinyint(1) DEFAULT \'0\',
`password_changed_at` datetime DEFAULT NULL,
`failed_login_attempts` int DEFAULT \'0\',
`account_locked_until` datetime DEFAULT NULL,
`timezone` varchar(50) DEFAULT \'Asia/Ho_Chi_Minh\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
`deleted_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `username` (`username`),
UNIQUE KEY `email` (`email`),
KEY `fk_users_branch_id` (`branch_id`),
CONSTRAINT `fk_users_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `warehouses`;');
        $this->db->query('CREATE TABLE `warehouses` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`name` varchar(255) NOT NULL,
`code` varchar(50) DEFAULT NULL,
`branch_id` bigint unsigned DEFAULT NULL,
`status` varchar(20) DEFAULT \'active\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
`deleted_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `fk_warehouses_branch_id` (`branch_id`),
CONSTRAINT `fk_warehouses_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `webhook_events`;');
        $this->db->query('CREATE TABLE `webhook_events` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`event` varchar(100) DEFAULT NULL,
`payload` json DEFAULT NULL,
`status` varchar(50) DEFAULT NULL,
`attempts` int DEFAULT \'0\',
`last_error` text,
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `webhook_subscriptions`;');
        $this->db->query('CREATE TABLE `webhook_subscriptions` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`event` varchar(100) DEFAULT NULL,
`target_url` varchar(500) DEFAULT NULL,
`secret` varchar(255) DEFAULT NULL,
`is_active` tinyint(1) DEFAULT \'1\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `withholding_rules`;');
        $this->db->query('CREATE TABLE `withholding_rules` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`name` varchar(150) NOT NULL,
`rate_percent` decimal(8,3) DEFAULT \'0.000\',
`apply_threshold` decimal(14,2) DEFAULT \'0.00\',
`status` varchar(20) DEFAULT \'active\',
`created_at` datetime DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        $this->db->query('DROP TABLE IF EXISTS `work_orders`;');
        $this->db->query('CREATE TABLE `work_orders` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`product_id` bigint unsigned NOT NULL,
`bom_id` bigint unsigned NOT NULL,
`branch_id` bigint unsigned NOT NULL,
`quantity` decimal(12,3) DEFAULT \'0.000\',
`status` varchar(30) DEFAULT \'draft\',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');

        $this->db->enableForeignKeyChecks();
    }

    public function down()
    {
        $this->db->disableForeignKeyChecks();
        
        $this->forge->dropTable('work_orders', true);
        $this->forge->dropTable('withholding_rules', true);
        $this->forge->dropTable('webhook_subscriptions', true);
        $this->forge->dropTable('webhook_events', true);
        $this->forge->dropTable('warehouses', true);
        $this->forge->dropTable('users', true);
        $this->forge->dropTable('timesheets', true);
        $this->forge->dropTable('timesheet_details', true);
        $this->forge->dropTable('ticket_events', true);
        $this->forge->dropTable('ticket_communications', true);
        $this->forge->dropTable('temp_queue', true);
        $this->forge->dropTable('tax_templates', true);
        $this->forge->dropTable('tax_template_items', true);
        $this->forge->dropTable('tax_charges', true);
        $this->forge->dropTable('tax_certificate_records', true);
        $this->forge->dropTable('tasks', true);
        $this->forge->dropTable('support_tickets', true);
        $this->forge->dropTable('suppliers', true);
        $this->forge->dropTable('subscriptions', true);
        $this->forge->dropTable('subscription_cycles', true);
        $this->forge->dropTable('subcontracting_orders', true);
        $this->forge->dropTable('subcontracting_materials', true);
        $this->forge->dropTable('stock_reconciliations', true);
        $this->forge->dropTable('stock_reconciliation_items', true);
        $this->forge->dropTable('stock_ledgers', true);
        $this->forge->dropTable('stock_entry_items', true);
        $this->forge->dropTable('stock_entries', true);
        $this->forge->dropTable('stock_bins', true);
        $this->forge->dropTable('sessions', true);
        $this->forge->dropTable('scheduler_rules', true);
        $this->forge->dropTable('sales_invoices', true);
        $this->forge->dropTable('sales_invoice_taxes', true);
        $this->forge->dropTable('sales_invoice_items', true);
        $this->forge->dropTable('salary_slips', true);
        $this->forge->dropTable('salary_components', true);
        $this->forge->dropTable('roles', true);
        $this->forge->dropTable('role_has_permissions', true);
        $this->forge->dropTable('returns', true);
        $this->forge->dropTable('return_items', true);
        $this->forge->dropTable('reorder_levels', true);
        $this->forge->dropTable('regional_tax_rules', true);
        $this->forge->dropTable('quotations', true);
        $this->forge->dropTable('quotation_items', true);
        $this->forge->dropTable('quality_parameters', true);
        $this->forge->dropTable('quality_inspections', true);
        $this->forge->dropTable('quality_inspection_items', true);
        $this->forge->dropTable('purchase_suggestions', true);
        $this->forge->dropTable('purchase_orders', true);
        $this->forge->dropTable('purchase_order_items', true);
        $this->forge->dropTable('purchase_invoices', true);
        $this->forge->dropTable('purchase_invoice_taxes', true);
        $this->forge->dropTable('purchase_invoice_items', true);
        $this->forge->dropTable('projects', true);
        $this->forge->dropTable('project_price_lists', true);
        $this->forge->dropTable('products', true);
        $this->forge->dropTable('product_warranties', true);
        $this->forge->dropTable('product_variants_v2', true);
        $this->forge->dropTable('product_stock_by_branch', true);
        $this->forge->dropTable('product_serial_numbers', true);
        $this->forge->dropTable('product_prices', true);
        $this->forge->dropTable('product_images', true);
        $this->forge->dropTable('product_category_links', true);
        $this->forge->dropTable('product_categories', true);
        $this->forge->dropTable('product_batches', true);
        $this->forge->dropTable('product_attributes', true);
        $this->forge->dropTable('product_attribute_values', true);
        $this->forge->dropTable('product_attribute_options', true);
        $this->forge->dropTable('pricing_rules', true);
        $this->forge->dropTable('price_lists', true);
        $this->forge->dropTable('price_list_items', true);
        $this->forge->dropTable('price_history', true);
        $this->forge->dropTable('positions', true);
        $this->forge->dropTable('pos_shifts', true);
        $this->forge->dropTable('pos_shift_payments', true);
        $this->forge->dropTable('pos_shift_logs', true);
        $this->forge->dropTable('pos_profiles', true);
        $this->forge->dropTable('pos_payment_methods', true);
        $this->forge->dropTable('pos_offline_queue', true);
        $this->forge->dropTable('portal_users', true);
        $this->forge->dropTable('portal_access_tokens', true);
        $this->forge->dropTable('pick_lists', true);
        $this->forge->dropTable('pick_list_items', true);
        $this->forge->dropTable('permissions', true);
        $this->forge->dropTable('payroll_entries', true);
        $this->forge->dropTable('payment_schedules', true);
        $this->forge->dropTable('payment_methods', true);
        $this->forge->dropTable('payment_entry_allocations', true);
        $this->forge->dropTable('payment_entries', true);
        $this->forge->dropTable('partners', true);
        $this->forge->dropTable('packing_slips', true);
        $this->forge->dropTable('packing_slip_items', true);
        $this->forge->dropTable('organizations', true);
        $this->forge->dropTable('orders', true);
        $this->forge->dropTable('order_templates', true);
        $this->forge->dropTable('order_template_items', true);
        $this->forge->dropTable('order_subscriptions', true);
        $this->forge->dropTable('order_status_logs', true);
        $this->forge->dropTable('order_sequences', true);
        $this->forge->dropTable('order_payments', true);
        $this->forge->dropTable('order_items', true);
        $this->forge->dropTable('order_approval_rules', true);
        $this->forge->dropTable('opportunity_items', true);
        $this->forge->dropTable('opportunities', true);
        $this->forge->dropTable('notifications', true);
        $this->forge->dropTable('notification_rules', true);
        $this->forge->dropTable('model_has_roles', true);
        $this->forge->dropTable('model_has_permissions', true);
        $this->forge->dropTable('migrations', true);
        $this->forge->dropTable('maintenance_work_orders', true);
        $this->forge->dropTable('maintenance_schedules', true);
        $this->forge->dropTable('loyalty_wallets', true);
        $this->forge->dropTable('loyalty_transactions', true);
        $this->forge->dropTable('loyalty_programs', true);
        $this->forge->dropTable('login_attempts', true);
        $this->forge->dropTable('leave_types', true);
        $this->forge->dropTable('leave_applications', true);
        $this->forge->dropTable('leads', true);
        $this->forge->dropTable('landed_cost_vouchers', true);
        $this->forge->dropTable('landed_cost_items', true);
        $this->forge->dropTable('knowledge_base_categories', true);
        $this->forge->dropTable('knowledge_base_articles', true);
        $this->forge->dropTable('journal_entry_lines', true);
        $this->forge->dropTable('journal_entries', true);
        $this->forge->dropTable('job_queue', true);
        $this->forge->dropTable('job_logs', true);
        $this->forge->dropTable('invoices', true);
        $this->forge->dropTable('invoice_orders', true);
        $this->forge->dropTable('inventory_valuation', true);
        $this->forge->dropTable('inventory_stock', true);
        $this->forge->dropTable('inventory_movements', true);
        $this->forge->dropTable('inventory_alerts', true);
        $this->forge->dropTable('goods_receipts', true);
        $this->forge->dropTable('goods_receipt_items', true);
        $this->forge->dropTable('gl_entries', true);
        $this->forge->dropTable('exchange_rates', true);
        $this->forge->dropTable('employees', true);
        $this->forge->dropTable('email_campaigns', true);
        $this->forge->dropTable('email_campaign_logs', true);
        $this->forge->dropTable('ecommerce_webhook_logs', true);
        $this->forge->dropTable('e_invoice_logs', true);
        $this->forge->dropTable('document_shares', true);
        $this->forge->dropTable('devices', true);
        $this->forge->dropTable('depreciation_schedules', true);
        $this->forge->dropTable('depreciation_schedule_lines', true);
        $this->forge->dropTable('departments', true);
        $this->forge->dropTable('delivery_notes', true);
        $this->forge->dropTable('delivery_note_items', true);
        $this->forge->dropTable('customers', true);
        $this->forge->dropTable('customer_price_lists', true);
        $this->forge->dropTable('customer_groups', true);
        $this->forge->dropTable('credit_limits', true);
        $this->forge->dropTable('coupons', true);
        $this->forge->dropTable('coupon_usages', true);
        $this->forge->dropTable('contracts', true);
        $this->forge->dropTable('contract_terms', true);
        $this->forge->dropTable('contract_templates', true);
        $this->forge->dropTable('company_permissions', true);
        $this->forge->dropTable('companies', true);
        $this->forge->dropTable('chart_of_accounts', true);
        $this->forge->dropTable('cash_transactions', true);
        $this->forge->dropTable('campaigns', true);
        $this->forge->dropTable('campaign_members', true);
        $this->forge->dropTable('branches', true);
        $this->forge->dropTable('bom_items', true);
        $this->forge->dropTable('bom', true);
        $this->forge->dropTable('bill_of_materials', true);
        $this->forge->dropTable('bank_statements', true);
        $this->forge->dropTable('bank_reconciliations', true);
        $this->forge->dropTable('bank_reconciliation_logs', true);
        $this->forge->dropTable('audit_logs', true);
        $this->forge->dropTable('attributes', true);
        $this->forge->dropTable('attribute_options', true);
        $this->forge->dropTable('attendances', true);
        $this->forge->dropTable('attendance', true);
        $this->forge->dropTable('assignment_rules', true);
        $this->forge->dropTable('assignment_logs', true);
        $this->forge->dropTable('assets', true);
        $this->forge->dropTable('approvals', true);
        $this->forge->dropTable('approval_actions', true);
        $this->forge->dropTable('appointments', true);
        $this->forge->dropTable('activity_types', true);
        $this->forge->dropTable('activity_logs', true);

        $this->db->enableForeignKeyChecks();
    }
}
