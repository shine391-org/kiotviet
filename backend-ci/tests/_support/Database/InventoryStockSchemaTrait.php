<?php

namespace Tests\Support\Database;

trait InventoryStockSchemaTrait
{
    protected function resetInventoryStockSchema(): void
    {
        $isSqlite = strtolower($this->db->DBDriver ?? '') === 'sqlite3';
        $auto = $isSqlite ? 'AUTOINCREMENT' : 'AUTO_INCREMENT';
        $varchar50 = $isSqlite ? 'TEXT' : 'VARCHAR(50)';

        if (! $isSqlite) {
            $this->db->query('SET FOREIGN_KEY_CHECKS=0');
            foreach ($this->db->listTables() as $table) {
                $this->db->query('DROP TABLE IF EXISTS `' . $table . '`');
            }
        }

        $this->db->query("CREATE TABLE inventory_stock (
            id INTEGER PRIMARY KEY {$auto},
            branch_id INTEGER,
            warehouse_id INTEGER,
            product_id INTEGER,
            variant_id INTEGER,
            quantity_on_hand REAL,
            quantity_reserved REAL,
            minimum_stock REAL,
            last_movement_at TEXT,
            created_at TEXT,
            updated_at TEXT
        )");
        $this->db->query("CREATE TABLE db_inventory_stock (
            id INTEGER PRIMARY KEY {$auto},
            branch_id INTEGER,
            warehouse_id INTEGER,
            product_id INTEGER,
            variant_id INTEGER,
            quantity_on_hand REAL,
            quantity_reserved REAL,
            minimum_stock REAL,
            last_movement_at TEXT,
            created_at TEXT,
            updated_at TEXT
        )");

        $this->db->query("CREATE TABLE inventory_alerts (
            id INTEGER PRIMARY KEY {$auto},
            alert_type {$varchar50},
            product_id INTEGER,
            variant_id INTEGER,
            warehouse_id INTEGER,
            current_quantity REAL,
            threshold_quantity REAL,
            status {$varchar50},
            resolved_by INTEGER,
            resolved_at TEXT,
            created_at TEXT,
            updated_at TEXT
        )");
        $this->db->query("CREATE TABLE db_inventory_alerts (
            id INTEGER PRIMARY KEY {$auto},
            alert_type {$varchar50},
            product_id INTEGER,
            variant_id INTEGER,
            warehouse_id INTEGER,
            current_quantity REAL,
            threshold_quantity REAL,
            status {$varchar50},
            resolved_by INTEGER,
            resolved_at TEXT,
            created_at TEXT,
            updated_at TEXT
        )");

        if (! $isSqlite) {
            $this->db->query('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}
