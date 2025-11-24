<?php

namespace Tests\Support\Database;

trait ReturnSchemaTrait
{
    protected function resetReturnSchema(): void
    {
        $isSqlite = strtolower($this->db->DBDriver ?? '') === 'sqlite3';
        $auto = $isSqlite ? 'AUTOINCREMENT' : 'AUTO_INCREMENT';
        $varchar50 = $isSqlite ? 'TEXT' : 'VARCHAR(50)';
        $varchar255 = $isSqlite ? 'TEXT' : 'VARCHAR(255)';

        if (! $isSqlite) {
            $this->db->query('SET FOREIGN_KEY_CHECKS=0');
            foreach ($this->db->listTables() as $table) {
                $this->db->query('DROP TABLE IF EXISTS `' . $table . '`');
            }
        }

        // users, customers, orders, order_items
        $this->db->query("CREATE TABLE users (
            id INTEGER PRIMARY KEY {$auto},
            username {$varchar50},
            created_at TEXT,
            updated_at TEXT
        )");
        $this->db->query("CREATE TABLE db_users (
            id INTEGER PRIMARY KEY {$auto},
            username {$varchar50},
            created_at TEXT,
            updated_at TEXT
        )");
        $this->db->query("CREATE TABLE customers (
            id INTEGER PRIMARY KEY {$auto},
            name {$varchar255},
            created_at TEXT,
            updated_at TEXT
        )");
        $this->db->query("CREATE TABLE db_customers (
            id INTEGER PRIMARY KEY {$auto},
            name {$varchar255},
            created_at TEXT,
            updated_at TEXT
        )");
        $this->db->query("CREATE TABLE orders (
            id INTEGER PRIMARY KEY {$auto},
            customer_id INTEGER,
            branch_id INTEGER,
            status TEXT,
            total REAL,
            shipping_fee REAL,
            completed_at TEXT,
            created_at TEXT,
            updated_at TEXT
        )");
        $this->db->query("CREATE TABLE db_orders (
            id INTEGER PRIMARY KEY {$auto},
            customer_id INTEGER,
            branch_id INTEGER,
            status TEXT,
            total REAL,
            shipping_fee REAL,
            completed_at TEXT,
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
            final_price REAL,
            created_at TEXT,
            updated_at TEXT
        )");
        $this->db->query("CREATE TABLE db_order_items (
            id INTEGER PRIMARY KEY {$auto},
            order_id INTEGER,
            product_id INTEGER,
            variant_id INTEGER,
            quantity REAL,
            base_price REAL,
            final_price REAL,
            created_at TEXT,
            updated_at TEXT
        )");

        // returns
        $this->db->query("CREATE TABLE returns (
            id INTEGER PRIMARY KEY {$auto},
            return_number {$varchar50} UNIQUE,
            order_id INTEGER,
            customer_id INTEGER,
            return_amount REAL,
            refund_shipping_fee INTEGER,
            refund_amount REAL,
            refund_method {$varchar50},
            reason {$varchar50},
            reason_detail TEXT,
            status {$varchar50},
            approved_by INTEGER,
            approved_at TEXT,
            rejected_by INTEGER,
            rejected_at TEXT,
            completed_at TEXT,
            notes TEXT,
            lock_version INTEGER DEFAULT 0,
            created_by INTEGER,
            created_at TEXT,
            updated_at TEXT
        )");
        $this->db->query("CREATE TABLE db_returns (
            id INTEGER PRIMARY KEY {$auto},
            return_number {$varchar50} UNIQUE,
            order_id INTEGER,
            customer_id INTEGER,
            return_amount REAL,
            refund_shipping_fee INTEGER,
            refund_amount REAL,
            refund_method {$varchar50},
            reason {$varchar50},
            reason_detail TEXT,
            status {$varchar50},
            approved_by INTEGER,
            approved_at TEXT,
            rejected_by INTEGER,
            rejected_at TEXT,
            completed_at TEXT,
            notes TEXT,
            lock_version INTEGER DEFAULT 0,
            created_by INTEGER,
            created_at TEXT,
            updated_at TEXT
        )");

        $this->db->query("CREATE TABLE return_items (
            id INTEGER PRIMARY KEY {$auto},
            return_id INTEGER,
            order_item_id INTEGER,
            quantity_returned REAL,
            item_condition {$varchar50},
            created_at TEXT,
            updated_at TEXT
        )");
        $this->db->query("CREATE TABLE db_return_items (
            id INTEGER PRIMARY KEY {$auto},
            return_id INTEGER,
            order_item_id INTEGER,
            quantity_returned REAL,
            item_condition {$varchar50},
            created_at TEXT,
            updated_at TEXT
        )");

        if (! $isSqlite) {
            $this->db->query('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}
