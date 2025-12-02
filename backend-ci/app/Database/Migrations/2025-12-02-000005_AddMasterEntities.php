<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Thêm các bảng master còn thiếu và liên kết FK tương ứng.
 *
 * @agent-migration: Master entities
 * @agent-pattern: Create tables + alter links
 */
class AddMasterEntities extends Migration
{
    public function up()
    {
        if ($this->isSqlite()) {
            return;
        }

        $this->createCustomerGroups();
        $this->createSuppliers();
        $this->createOrganizations();
        $this->createDevices();
        $this->createTempQueue();

        $this->linkCustomers();
        $this->linkLoyaltyPrograms();
        $this->linkOrders();
        $this->linkPurchaseInvoices();
        $this->linkSubcontractingOrders();
        $this->linkPosOfflineQueue();
        $this->linkInventoryStockWarehouse();
    }

    public function down()
    {
        if ($this->isSqlite()) {
            return;
        }

        // Drop FKs then tables
        $this->dropFkIfExists('customers', 'fk_customers_customer_group');
        $this->dropFkIfExists('loyalty_programs', 'fk_loyalty_programs_customer_group');
        $this->dropFkIfExists('orders', 'fk_orders_customer_group');
        $this->dropFkIfExists('purchase_invoices', 'fk_purchase_invoices_supplier');
        $this->dropFkIfExists('subcontracting_orders', 'fk_subcontracting_orders_supplier');
        $this->dropFkIfExists('pos_offline_queue', 'fk_pos_offline_queue_device');
        $this->dropFkIfExists('inventory_stock', 'fk_inventory_stock_warehouse');

        $this->forge->dropTable('temp_queue', true);
        $this->forge->dropTable('devices', true);
        $this->forge->dropTable('organizations', true);
        $this->forge->dropTable('suppliers', true);
        $this->forge->dropTable('customer_groups', true);
    }

    private function createCustomerGroups(): void
    {
        if ($this->db->tableExists('customer_groups')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 50],
            'name_vi' => ['type' => 'VARCHAR', 'constraint' => 255],
            'name_en' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'parent_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'branch_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'is_default' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code', 'uq_customer_group_code');
        $this->forge->addForeignKey('parent_id', 'customer_groups', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('branch_id', 'branches', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('customer_groups', true);
    }

    private function createSuppliers(): void
    {
        if ($this->db->tableExists('suppliers')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 50],
            'name_vi' => ['type' => 'VARCHAR', 'constraint' => 255],
            'name_en' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'tax_code' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'phone' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'email' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'address' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'region' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            'parent_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code', 'uq_supplier_code');
        $this->forge->addForeignKey('parent_id', 'suppliers', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('suppliers', true);
    }

    private function createOrganizations(): void
    {
        if ($this->db->tableExists('organizations')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 50],
            'name_vi' => ['type' => 'VARCHAR', 'constraint' => 255],
            'name_en' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'tax_code' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'phone' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'email' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'address' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            'parent_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'branch_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code', 'uq_organization_code');
        $this->forge->addForeignKey('parent_id', 'organizations', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('branch_id', 'branches', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('organizations', true);
    }

    private function createDevices(): void
    {
        if ($this->db->tableExists('devices')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 50],
            'name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'type' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'pos'],
            'branch_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            'meta' => ['type' => 'JSON', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code', 'uq_device_code');
        $this->forge->addForeignKey('branch_id', 'branches', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('devices', true);
    }

    private function createTempQueue(): void
    {
        if ($this->db->tableExists('temp_queue')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'device_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'payload' => ['type' => 'JSON', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
            'expired_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('device_id', 'devices', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('temp_queue', true);
    }

    private function linkCustomers(): void
    {
        if (! $this->db->tableExists('customers')) {
            return;
        }
        $this->db->query('ALTER TABLE customers MODIFY customer_group_id BIGINT UNSIGNED NULL');
        $this->db->query('ALTER TABLE customers MODIFY organization_id BIGINT UNSIGNED NULL');

        if (! $this->foreignKeyExists('customers', 'fk_customers_customer_group')) {
            $this->forge->addColumn('customers', [
                'CONSTRAINT fk_customers_customer_group FOREIGN KEY (customer_group_id) REFERENCES customer_groups(id) ON DELETE SET NULL ON UPDATE CASCADE',
            ]);
        }
        if (! $this->foreignKeyExists('customers', 'fk_customers_organization')) {
            $this->forge->addColumn('customers', [
                'CONSTRAINT fk_customers_organization FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE SET NULL ON UPDATE CASCADE',
            ]);
        }
    }

    private function linkLoyaltyPrograms(): void
    {
        if (! $this->db->tableExists('loyalty_programs')) {
            return;
        }
        $this->db->query('ALTER TABLE loyalty_programs MODIFY customer_group_id BIGINT UNSIGNED NULL');
        if (! $this->foreignKeyExists('loyalty_programs', 'fk_loyalty_programs_customer_group')) {
            $this->forge->addColumn('loyalty_programs', [
                'CONSTRAINT fk_loyalty_programs_customer_group FOREIGN KEY (customer_group_id) REFERENCES customer_groups(id) ON DELETE SET NULL ON UPDATE CASCADE',
            ]);
        }
    }

    private function linkOrders(): void
    {
        if (! $this->db->tableExists('orders')) {
            return;
        }
        $this->db->query('ALTER TABLE orders MODIFY customer_group_id BIGINT UNSIGNED NULL');
        if (! $this->foreignKeyExists('orders', 'fk_orders_customer_group')) {
            $this->forge->addColumn('orders', [
                'CONSTRAINT fk_orders_customer_group FOREIGN KEY (customer_group_id) REFERENCES customer_groups(id) ON DELETE SET NULL ON UPDATE CASCADE',
            ]);
        }
    }

    private function linkPurchaseInvoices(): void
    {
        if (! $this->db->tableExists('purchase_invoices')) {
            return;
        }
        $this->db->query('ALTER TABLE purchase_invoices MODIFY supplier_id BIGINT UNSIGNED NULL');
        if (! $this->foreignKeyExists('purchase_invoices', 'fk_purchase_invoices_supplier')) {
            $this->forge->addColumn('purchase_invoices', [
                'CONSTRAINT fk_purchase_invoices_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL ON UPDATE CASCADE',
            ]);
        }
    }

    private function linkSubcontractingOrders(): void
    {
        if (! $this->db->tableExists('subcontracting_orders')) {
            return;
        }
        $this->db->query('ALTER TABLE subcontracting_orders MODIFY supplier_id BIGINT UNSIGNED NULL');
        if (! $this->foreignKeyExists('subcontracting_orders', 'fk_subcontracting_orders_supplier')) {
            $this->forge->addColumn('subcontracting_orders', [
                'CONSTRAINT fk_subcontracting_orders_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL ON UPDATE CASCADE',
            ]);
        }
    }

    private function linkPosOfflineQueue(): void
    {
        if (! $this->db->tableExists('pos_offline_queue')) {
            return;
        }
        $this->db->query('ALTER TABLE pos_offline_queue MODIFY device_id BIGINT UNSIGNED NULL');
        if (! $this->foreignKeyExists('pos_offline_queue', 'fk_pos_offline_queue_device')) {
            $this->forge->addColumn('pos_offline_queue', [
                'CONSTRAINT fk_pos_offline_queue_device FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL ON UPDATE CASCADE',
            ]);
        }
    }

    private function linkInventoryStockWarehouse(): void
    {
        if (! $this->db->tableExists('inventory_stock')) {
            return;
        }
        $this->db->query('ALTER TABLE inventory_stock MODIFY warehouse_id BIGINT UNSIGNED NULL');
        if (! $this->foreignKeyExists('inventory_stock', 'fk_inventory_stock_warehouse')) {
            $this->forge->addColumn('inventory_stock', [
                'CONSTRAINT fk_inventory_stock_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE SET NULL ON UPDATE CASCADE',
            ]);
        }
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        $sql = "SELECT 1 FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=? AND TABLE_NAME=? AND CONSTRAINT_NAME=? LIMIT 1";
        return (bool) $this->db->query($sql, [$this->db->getDatabase(), $table, $constraint])->getRowArray();
    }

    private function isSqlite(): bool
    {
        return strtolower($this->db->DBDriver) === 'sqlite3';
    }
}
