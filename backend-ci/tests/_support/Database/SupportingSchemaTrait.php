<?php

namespace Tests\Support\Database;

trait SupportingSchemaTrait
{
    protected function resetSupportingSchema(): void
    {
        $isSqlite = strtolower($this->db->DBDriver ?? '') === 'sqlite3';
        $auto = $isSqlite ? 'AUTOINCREMENT' : 'AUTO_INCREMENT';
        $varchar20 = $isSqlite ? 'TEXT' : 'VARCHAR(20)';
        $varchar50 = $isSqlite ? 'TEXT' : 'VARCHAR(50)';
        $varchar100 = $isSqlite ? 'TEXT' : 'VARCHAR(100)';
        $varchar255 = $isSqlite ? 'TEXT' : 'VARCHAR(255)';

        if (! $isSqlite) {
            $this->db->query('SET FOREIGN_KEY_CHECKS=0');
            foreach ($this->db->listTables() as $table) {
                $this->db->query('DROP TABLE IF EXISTS `' . $table . '`');
            }
        }

        // branches
        $this->db->query("CREATE TABLE branches (
            id INTEGER PRIMARY KEY {$auto},
            code {$varchar20},
            name {$varchar255},
            phone {$varchar20},
            address TEXT,
            ward {$varchar100},
            district {$varchar100},
            city {$varchar100},
            status {$varchar20},
            is_active INTEGER DEFAULT 1,
            created_at TEXT,
            updated_at TEXT,
            deleted_at TEXT
        )");

        // orders + items minimal
        $this->db->query("CREATE TABLE orders (
            id INTEGER PRIMARY KEY {$auto},
            status {$varchar50},
            branch_id INTEGER,
            created_at TEXT,
            updated_at TEXT
        )");
        $this->db->query("CREATE TABLE db_orders (
            id INTEGER PRIMARY KEY {$auto},
            status {$varchar50},
            branch_id INTEGER,
            created_at TEXT,
            updated_at TEXT
        )");

        // order_status_logs
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

        // inventory_movements
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

        if (! $isSqlite) {
            $this->db->query('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}
