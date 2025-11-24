<?php

namespace Tests\Support\Database;

trait PaymentMethodSchemaTrait
{
    protected function resetPaymentSchema(): void
    {
        $auto = strtoupper($this->db->DBDriver ?? '') === 'SQLITE3' ? 'AUTOINCREMENT' : 'AUTO_INCREMENT';
        $isSqlite = strtolower($this->db->DBDriver ?? '') === 'sqlite3';
        $codeType = $isSqlite ? 'TEXT' : 'VARCHAR(50)';
        $nameType = $isSqlite ? 'TEXT' : 'VARCHAR(255)';
        $jsonType = $isSqlite ? 'TEXT' : 'JSON';
        $boolType = $isSqlite ? 'INTEGER' : 'TINYINT(1)';

        if (strtolower($this->db->DBDriver) !== 'sqlite3') {
            $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        }

        $this->db->query('DROP TABLE IF EXISTS db_payment_methods');
        $this->db->query('DROP TABLE IF EXISTS payment_methods');
        $this->db->query('DROP TABLE IF EXISTS db_order_items');
        $this->db->query('DROP TABLE IF EXISTS order_items');
        $this->db->query('DROP TABLE IF EXISTS db_orders');
        $this->db->query('DROP TABLE IF EXISTS orders');

        $this->db->query("CREATE TABLE db_payment_methods (
            id INTEGER PRIMARY KEY {$auto},
            code {$codeType} UNIQUE,
            name {$nameType},
            name_translations {$jsonType},
            description TEXT,
            is_active {$boolType} DEFAULT 1,
            display_order INTEGER DEFAULT 0,
            created_at TEXT,
            updated_at TEXT,
            deleted_at TEXT
        )");

        $this->db->query("CREATE TABLE payment_methods (
            id INTEGER PRIMARY KEY {$auto},
            code {$codeType} UNIQUE,
            name {$nameType},
            name_translations {$jsonType},
            description TEXT,
            is_active {$boolType} DEFAULT 1,
            display_order INTEGER DEFAULT 0,
            created_at TEXT,
            updated_at TEXT,
            deleted_at TEXT
        )");

        $orderIdType = $isSqlite ? 'INTEGER' : 'BIGINT UNSIGNED';

        $this->db->query("CREATE TABLE db_orders (
            id {$orderIdType} PRIMARY KEY {$auto},
            customer_id INTEGER,
            payment_method TEXT,
            total REAL,
            created_at TEXT,
            updated_at TEXT,
            deleted_at TEXT
        )");

        $this->db->query("CREATE TABLE orders (
            id {$orderIdType} PRIMARY KEY {$auto},
            customer_id INTEGER,
            payment_method TEXT,
            total REAL,
            created_at TEXT,
            updated_at TEXT,
            deleted_at TEXT
        )");
    }
}
