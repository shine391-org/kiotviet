<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration to cleanup duplicate tables identified in DB audit.
 * 
 * Duplicate tables:
 * 1. attendance (8 cols) vs attendances (6 cols) -> Keep attendances (has FK)
 * 2. bom (8 cols) vs bill_of_materials (9 cols) -> Keep bill_of_materials (has FK)
 * 
 * IMPORTANT: This migration will migrate data from old tables to new tables
 * before dropping them. Run with caution on production!
 */
class CleanupDuplicateTables extends Migration
{
    public function up()
    {
        $this->db->disableForeignKeyChecks();

        // 1. Migrate data from `attendance` to `attendances` (if any)
        // Only migrate if attendance has data and attendances doesn't have that data
        $this->db->query('
            INSERT IGNORE INTO `attendances` (employee_id, attendance_date, status, created_at, updated_at)
            SELECT employee_id, date, status, created_at, updated_at
            FROM `attendance`
            WHERE NOT EXISTS (
                SELECT 1 FROM `attendances` a 
                WHERE a.employee_id = attendance.employee_id 
                AND a.attendance_date = attendance.date
            )
        ');

        // 2. Migrate data from `bom` to `bill_of_materials` (if any)
        // Note: bom has different structure, so we migrate what we can
        $this->db->query('
            INSERT IGNORE INTO `bill_of_materials` (product_id, version, is_active, created_at, updated_at)
            SELECT product_id, version, is_active, created_at, updated_at
            FROM `bom`
            WHERE NOT EXISTS (
                SELECT 1 FROM `bill_of_materials` b 
                WHERE b.product_id = bom.product_id 
                AND COALESCE(b.version, \'\') = COALESCE(bom.version, \'\')
            )
        ');

        // Now drop the old duplicate tables
        // First remove FK constraints added in previous migration
        try {
            $this->db->query('ALTER TABLE `attendance` DROP FOREIGN KEY `fk_attendance_employee`');
        } catch (\Exception $e) {
            // FK might not exist
        }
        
        try {
            $this->db->query('ALTER TABLE `bom` DROP FOREIGN KEY `fk_bom_product`');
        } catch (\Exception $e) {
            // FK might not exist
        }

        // Drop duplicate tables
        $this->forge->dropTable('attendance', true);
        $this->forge->dropTable('bom', true);

        $this->db->enableForeignKeyChecks();
    }

    public function down()
    {
        $this->db->disableForeignKeyChecks();

        // Recreate attendance table (old version)
        $this->db->query('
            CREATE TABLE IF NOT EXISTS `attendance` (
                `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `employee_id` bigint unsigned NOT NULL,
                `date` date NOT NULL,
                `check_in` time DEFAULT NULL,
                `check_out` time DEFAULT NULL,
                `status` varchar(20) DEFAULT \'present\',
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk_attendance_employee` 
                    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) 
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        ');

        // Recreate bom table (old version)
        $this->db->query('
            CREATE TABLE IF NOT EXISTS `bom` (
                `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `product_id` bigint unsigned NOT NULL,
                `name` varchar(255) NOT NULL,
                `version` varchar(50) DEFAULT NULL,
                `is_active` tinyint(1) DEFAULT \'1\',
                `is_default` tinyint(1) DEFAULT \'0\',
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk_bom_product` 
                    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) 
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        ');

        $this->db->enableForeignKeyChecks();
    }
}
