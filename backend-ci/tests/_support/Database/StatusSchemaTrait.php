<?php

namespace Tests\Support\Database;

trait StatusSchemaTrait
{
    protected function resetStatusSchema(): void
    {
        $isSqlite = strtolower($this->db->DBDriver ?? '') === 'sqlite3';
        $auto = $isSqlite ? 'AUTOINCREMENT' : 'AUTO_INCREMENT';
        $varchar50 = $isSqlite ? 'TEXT' : 'VARCHAR(50)';
        $varchar30 = $isSqlite ? 'TEXT' : 'VARCHAR(30)';

        if (! $isSqlite) {
            $this->db->query('SET FOREIGN_KEY_CHECKS=0');
            foreach ($this->db->listTables() as $table) {
                $this->db->query('DROP TABLE IF EXISTS `' . $table . '`');
            }
        }

        $this->db->query("CREATE TABLE inventory_stock (
            id INTEGER PRIMARY KEY {$auto},
            branch_id INTEGER,
            product_id INTEGER,
            variant_id INTEGER,
            quantity_on_hand REAL,
            quantity_reserved REAL,
            minimum_stock REAL
        )");
        $this->db->query("CREATE TABLE db_inventory_stock (
            id INTEGER PRIMARY KEY {$auto},
            branch_id INTEGER,
            product_id INTEGER,
            variant_id INTEGER,
            quantity_on_hand REAL,
            quantity_reserved REAL,
            minimum_stock REAL
        )");

        $this->db->query("CREATE TABLE inventory_movements (
            id INTEGER PRIMARY KEY {$auto},
            branch_id INTEGER,
            product_id INTEGER,
            variant_id INTEGER,
            type {$varchar50},
            quantity REAL,
            reference_type {$varchar50},
            reference_id INTEGER,
            notes TEXT,
            created_by INTEGER,
            created_at TEXT,
            updated_at TEXT
        )");
        $this->db->query("CREATE TABLE db_inventory_movements (
            id INTEGER PRIMARY KEY {$auto},
            branch_id INTEGER,
            product_id INTEGER,
            variant_id INTEGER,
            type {$varchar50},
            quantity REAL,
            reference_type {$varchar50},
            reference_id INTEGER,
            notes TEXT,
            created_by INTEGER,
            created_at TEXT,
            updated_at TEXT
        )");

        $this->db->query("CREATE TABLE order_status_logs (
            id INTEGER PRIMARY KEY {$auto},
            order_id INTEGER,
            from_status {$varchar50},
            to_status {$varchar50},
            notes TEXT,
            changed_by INTEGER,
            changed_at TEXT,
            created_at TEXT,
            updated_at TEXT
        )");
        $this->db->query("CREATE TABLE db_order_status_logs (
            id INTEGER PRIMARY KEY {$auto},
            order_id INTEGER,
            from_status {$varchar50},
            to_status {$varchar50},
            notes TEXT,
            changed_by INTEGER,
            changed_at TEXT,
            created_at TEXT,
            updated_at TEXT
        )");

        $this->db->query("CREATE TABLE orders (
            id INTEGER PRIMARY KEY {$auto},
            order_number {$varchar30},
            customer_id INTEGER,
            branch_id INTEGER,
            status {$varchar50},
            order_type {$varchar50},
            payment_method {$varchar50},
            subtotal REAL,
            discount_total REAL,
            total REAL,
            shipping_fee REAL,
            paid_amount REAL,
            debt_amount REAL,
            is_paid INTEGER,
            cod_collected INTEGER,
            confirmed_at TEXT,
            processing_at TEXT,
            shipping_at TEXT,
            delivered_at TEXT,
            completed_at TEXT,
            cancelled_at TEXT,
            cancellation_reason TEXT,
            created_at TEXT,
            updated_at TEXT
        )");
        $this->db->query("CREATE TABLE db_orders (
            id INTEGER PRIMARY KEY {$auto},
            order_number {$varchar30},
            customer_id INTEGER,
            branch_id INTEGER,
            status {$varchar50},
            order_type {$varchar50},
            payment_method {$varchar50},
            subtotal REAL,
            discount_total REAL,
            total REAL,
            shipping_fee REAL,
            paid_amount REAL,
            debt_amount REAL,
            is_paid INTEGER,
            cod_collected INTEGER,
            confirmed_at TEXT,
            processing_at TEXT,
            shipping_at TEXT,
            delivered_at TEXT,
            completed_at TEXT,
            cancelled_at TEXT,
            cancellation_reason TEXT,
            created_at TEXT,
            updated_at TEXT
        )");

        $this->db->query("CREATE TABLE order_items (
            id INTEGER PRIMARY KEY {$auto},
            order_id INTEGER,
            product_id INTEGER,
            variant_id INTEGER,
            quantity REAL,
            base_price REAL,
            final_price REAL
        )");
        $this->db->query("CREATE TABLE db_order_items (
            id INTEGER PRIMARY KEY {$auto},
            order_id INTEGER,
            product_id INTEGER,
            variant_id INTEGER,
            quantity REAL,
            base_price REAL,
            final_price REAL
        )");

        if (! $isSqlite) {
            $this->db->query('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}
