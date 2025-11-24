<?php

namespace Tests\Support\Database;

trait InvoiceSchemaTrait
{
    protected function resetInvoiceSchema(): void
    {
        $auto = strtoupper($this->db->DBDriver ?? '') === 'SQLITE3' ? 'AUTOINCREMENT' : 'AUTO_INCREMENT';
        $isSqlite = strtolower($this->db->DBDriver ?? '') === 'sqlite3';
        $varchar50 = $isSqlite ? 'TEXT' : 'VARCHAR(50)';
        $varchar255 = $isSqlite ? 'TEXT' : 'VARCHAR(255)';

        if (! $isSqlite) {
            $this->db->query('SET FOREIGN_KEY_CHECKS=0');
            // Drop all existing tables in isolated test DB to avoid FK conflicts.
            foreach ($this->db->listTables() as $table) {
                $this->db->query('DROP TABLE IF EXISTS `' . $table . '`');
            }
        }

        $this->db->query('DROP TABLE IF EXISTS db_activity_logs');
        $this->db->query('DROP TABLE IF EXISTS activity_logs');
        $this->db->query('DROP TABLE IF EXISTS db_invoice_orders');
        $this->db->query('DROP TABLE IF EXISTS invoice_orders');
        $this->db->query('DROP TABLE IF EXISTS db_invoices');
        $this->db->query('DROP TABLE IF EXISTS invoices');
        $this->db->query('DROP TABLE IF EXISTS db_orders');
        $this->db->query('DROP TABLE IF EXISTS orders');
        $this->db->query('DROP TABLE IF EXISTS db_customers');
        $this->db->query('DROP TABLE IF EXISTS customers');
        $this->db->query('DROP TABLE IF EXISTS db_branches');
        $this->db->query('DROP TABLE IF EXISTS branches');
        $this->db->query('DROP TABLE IF EXISTS db_users');
        $this->db->query('DROP TABLE IF EXISTS users');

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

        $this->db->query("CREATE TABLE branches (
            id INTEGER PRIMARY KEY {$auto},
            name {$varchar255},
            created_at TEXT,
            updated_at TEXT
        )");
        $this->db->query("CREATE TABLE db_branches (
            id INTEGER PRIMARY KEY {$auto},
            name {$varchar255},
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
            order_date TEXT,
            total REAL,
            status TEXT,
            created_at TEXT,
            updated_at TEXT
        )");
        $this->db->query("CREATE TABLE db_orders (
            id INTEGER PRIMARY KEY {$auto},
            customer_id INTEGER,
            branch_id INTEGER,
            order_date TEXT,
            total REAL,
            status TEXT,
            created_at TEXT,
            updated_at TEXT
        )");

        $this->db->query("CREATE TABLE invoices (
            id INTEGER PRIMARY KEY {$auto},
            invoice_number {$varchar50} UNIQUE,
            customer_id INTEGER,
            branch_id INTEGER,
            issue_date TEXT,
            due_date TEXT,
            subtotal REAL,
            vat_rate REAL,
            vat_amount REAL,
            total REAL,
            pdf_path {$varchar255},
            notes TEXT,
            meta TEXT,
            created_by INTEGER,
            created_at TEXT,
            updated_at TEXT
        )");
        $this->db->query("CREATE TABLE db_invoices (
            id INTEGER PRIMARY KEY {$auto},
            invoice_number {$varchar50} UNIQUE,
            customer_id INTEGER,
            branch_id INTEGER,
            issue_date TEXT,
            due_date TEXT,
            subtotal REAL,
            vat_rate REAL,
            vat_amount REAL,
            total REAL,
            pdf_path {$varchar255},
            notes TEXT,
            meta TEXT,
            created_by INTEGER,
            created_at TEXT,
            updated_at TEXT
        )");

        $this->db->query("CREATE TABLE invoice_orders (
            id INTEGER PRIMARY KEY {$auto},
            invoice_id INTEGER,
            order_id INTEGER,
            created_at TEXT,
            updated_at TEXT
        )");
        $this->db->query("CREATE TABLE db_invoice_orders (
            id INTEGER PRIMARY KEY {$auto},
            invoice_id INTEGER,
            order_id INTEGER,
            created_at TEXT,
            updated_at TEXT
        )");

        if (! $isSqlite) {
            $this->db->query('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}
