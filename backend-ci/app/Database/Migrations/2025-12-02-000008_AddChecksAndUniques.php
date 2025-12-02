<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Thêm unique cho bảng liên kết và check constraints đơn giản.
 *
 * @agent-migration: Constraints hardening
 * @agent-pattern: Conditional add unique + check
 */
class AddChecksAndUniques extends Migration
{
    public function up()
    {
        if ($this->isSqlite()) {
            return;
        }

        $this->addUniqueProductCategoryLinks();
        $this->addCheckConstraints();
    }

    public function down()
    {
        if ($this->isSqlite()) {
            return;
        }

        $this->dropConstraintIfExists('product_category_links', 'uq_product_category_links');
        $this->dropCheckIfExists('orders', 'chk_orders_total');
        $this->dropCheckIfExists('cash_transactions', 'chk_cash_amount');
    }

    private function addUniqueProductCategoryLinks(): void
    {
        if (! $this->db->tableExists('product_category_links')) {
            return;
        }
        // Dọn trùng trước khi tạo unique
        $sql = "DELETE pcl1 FROM product_category_links pcl1
                JOIN product_category_links pcl2
                  ON pcl1.product_id = pcl2.product_id AND pcl1.category_id = pcl2.category_id
                 AND pcl1.id > pcl2.id";
        $this->db->query($sql);
        // Đảm bảo có index duy nhất product-category
        $this->forge->addUniqueKey(['product_id', 'category_id'], 'uq_product_category_links');
        $this->forge->processIndexes('product_category_links');
    }

    private function addCheckConstraints(): void
    {
        if (! $this->supportsCheckConstraints()) {
            return;
        }

        if ($this->db->tableExists('orders')) {
            $this->db->query('ALTER TABLE orders ADD CONSTRAINT chk_orders_total CHECK (total >= 0)');
        }
        if ($this->db->tableExists('cash_transactions')) {
            $this->db->query('ALTER TABLE cash_transactions ADD CONSTRAINT chk_cash_amount CHECK (amount > 0)');
        }
    }

    private function dropConstraintIfExists(string $table, string $constraint): void
    {
        if (! $this->db->tableExists($table)) {
            return;
        }
        // MySQL không có drop constraint generic; dùng ALTER TABLE DROP INDEX cho unique
        $this->db->query("ALTER TABLE {$table} DROP INDEX IF EXISTS {$constraint}");
    }

    private function dropCheckIfExists(string $table, string $constraint): void
    {
        if (! $this->supportsCheckConstraints() || ! $this->db->tableExists($table)) {
            return;
        }
        $this->db->query("ALTER TABLE {$table} DROP CHECK IF EXISTS {$constraint}");
    }

    private function supportsCheckConstraints(): bool
    {
        if (strtolower($this->db->DBDriver) !== 'mysqli') {
            return false;
        }
        $versionRow = $this->db->query('SELECT VERSION() as v')->getRowArray();
        if (! $versionRow || empty($versionRow['v'])) {
            return false;
        }
        // MySQL 8.0.16+ hỗ trợ enforce CHECK
        return version_compare($versionRow['v'], '8.0.16', '>=');
    }

    private function isSqlite(): bool
    {
        return strtolower($this->db->DBDriver) === 'sqlite3';
    }
}
