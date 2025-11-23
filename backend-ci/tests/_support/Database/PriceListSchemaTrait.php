<?php

namespace Tests\Support\Database;

trait PriceListSchemaTrait
{
    protected function resetPriceListSchema(): void
    {
        $auto = strtoupper($this->db->DBDriver ?? '') === 'SQLITE3' ? 'AUTOINCREMENT' : 'AUTO_INCREMENT';

        if (strtolower($this->db->DBDriver) !== 'sqlite3') {
            $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        }

        $this->db->query('DROP TABLE IF EXISTS db_order_items');
        $this->db->query('DROP TABLE IF EXISTS db_orders');
        $this->db->query('DROP TABLE IF EXISTS db_price_list_items');
        $this->db->query('DROP TABLE IF EXISTS price_list_items');
        $this->db->query('DROP TABLE IF EXISTS db_price_lists');
        $this->db->query('DROP TABLE IF EXISTS price_lists');
        $this->db->query('DROP TABLE IF EXISTS db_product_images');
        $this->db->query('DROP TABLE IF EXISTS product_images');
        $this->db->query('DROP TABLE IF EXISTS inventory_movement_items');
        $this->db->query('DROP TABLE IF EXISTS invoice_items');
        $this->db->query('DROP TABLE IF EXISTS invoices');
        $this->db->query('DROP TABLE IF EXISTS db_invoice_items');
        $this->db->query('DROP TABLE IF EXISTS db_invoices');
        $this->db->query('DROP TABLE IF EXISTS product_attribute_values');
        $this->db->query('DROP TABLE IF EXISTS db_product_attribute_values');
        $this->db->query('DROP TABLE IF EXISTS inventory_stock');
        $this->db->query('DROP TABLE IF EXISTS db_inventory_stock');
        $this->db->query('DROP TABLE IF EXISTS stock');
        $this->db->query('DROP TABLE IF EXISTS db_stock');
        $this->db->query('DROP TABLE IF EXISTS inventory_movements');
        $this->db->query('DROP TABLE IF EXISTS db_inventory_movements');
        $this->db->query('DROP TABLE IF EXISTS db_inventory_movement_items');
        $this->db->query('DROP TABLE IF EXISTS product_branch_stock');
        $this->db->query('DROP TABLE IF EXISTS db_product_branch_stock');
        $this->db->query('DROP TABLE IF EXISTS product_stock_by_branch');
        $this->db->query('DROP TABLE IF EXISTS db_product_stock_by_branch');
        $this->db->query('DROP TABLE IF EXISTS stock_transactions');
        $this->db->query('DROP TABLE IF EXISTS db_stock_transactions');
        $this->db->query('DROP TABLE IF EXISTS stock_transactions_v2');
        $this->db->query('DROP TABLE IF EXISTS db_stock_transactions_v2');
        $this->db->query('DROP TABLE IF EXISTS product_categories');
        $this->db->query('DROP TABLE IF EXISTS db_product_categories');
        $this->db->query('DROP TABLE IF EXISTS product_category_links');
        $this->db->query('DROP TABLE IF EXISTS db_product_category_links');
        $this->db->query('DROP TABLE IF EXISTS db_product_variants_v2');
        $this->db->query('DROP TABLE IF EXISTS product_variants_v2');
        $this->db->query('DROP TABLE IF EXISTS db_products');
        $this->db->query('DROP TABLE IF EXISTS products');

        // products
        $this->db->query("CREATE TABLE db_products (
            id INTEGER PRIMARY KEY {$auto},
            code TEXT,
            name TEXT,
            selling_price REAL DEFAULT 0,
            wholesale_price REAL,
            created_at TEXT,
            updated_at TEXT,
            deleted_at TEXT
        )");

        $this->db->query("CREATE TABLE products (
            id INTEGER PRIMARY KEY {$auto},
            code TEXT,
            name TEXT,
            selling_price REAL DEFAULT 0,
            wholesale_price REAL,
            created_at TEXT,
            updated_at TEXT,
            deleted_at TEXT
        )");

        // variants
        $this->db->query("CREATE TABLE db_product_variants_v2 (
            id INTEGER PRIMARY KEY {$auto},
            product_id INTEGER,
            sku TEXT,
            price REAL,
            created_at TEXT,
            updated_at TEXT,
            deleted_at TEXT
        )");

        $this->db->query("CREATE TABLE product_variants_v2 (
            id INTEGER PRIMARY KEY {$auto},
            product_id INTEGER,
            sku TEXT,
            price REAL,
            created_at TEXT,
            updated_at TEXT,
            deleted_at TEXT
        )");

        $this->db->query("CREATE TABLE db_product_images (
             id INTEGER PRIMARY KEY {$auto},
             product_id INTEGER,
             variant_id INTEGER,
             image_path TEXT,
             created_at TEXT,
             updated_at TEXT
        )");

        $this->db->query("CREATE TABLE product_images (
             id INTEGER PRIMARY KEY {$auto},
             product_id INTEGER,
             variant_id INTEGER,
             image_path TEXT,
             created_at TEXT,
             updated_at TEXT
        )");

        // price lists
        $this->db->query("CREATE TABLE db_price_lists (
            id INTEGER PRIMARY KEY {$auto},
            name TEXT,
            type TEXT,
            description TEXT,
            apply_to_groups TEXT,
            start_date TEXT,
            end_date TEXT,
            priority INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            formula TEXT,
            base_price_list_id INTEGER,
            auto_update INTEGER DEFAULT 0,
            rounding_rule TEXT DEFAULT 'none',
            created_at TEXT,
            updated_at TEXT,
            deleted_at TEXT
        )");

        $this->db->query("CREATE TABLE price_lists (
            id INTEGER PRIMARY KEY {$auto},
            name TEXT,
            type TEXT,
            description TEXT,
            apply_to_groups TEXT,
            start_date TEXT,
            end_date TEXT,
            priority INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            formula TEXT,
            base_price_list_id INTEGER,
            auto_update INTEGER DEFAULT 0,
            rounding_rule TEXT DEFAULT 'none',
            created_at TEXT,
            updated_at TEXT,
            deleted_at TEXT
        )");

        // price list items
        $this->db->query("CREATE TABLE db_price_list_items (
            id INTEGER PRIMARY KEY {$auto},
            price_list_id INTEGER,
            product_id INTEGER,
            variant_id INTEGER,
            price REAL,
            discount_percent REAL DEFAULT 0,
            discount_amount REAL DEFAULT 0,
            created_at TEXT,
            updated_at TEXT
        )");

        $this->db->query("CREATE TABLE price_list_items (
            id INTEGER PRIMARY KEY {$auto},
            price_list_id INTEGER,
            product_id INTEGER,
            variant_id INTEGER,
            price REAL,
            discount_percent REAL DEFAULT 0,
            discount_amount REAL DEFAULT 0,
            created_at TEXT,
            updated_at TEXT
        )");

        // orders
        $this->db->query("CREATE TABLE db_orders (
            id INTEGER PRIMARY KEY {$auto},
            customer_id INTEGER,
            customer_group_id INTEGER,
            order_date TEXT,
            status TEXT,
            subtotal REAL,
            discount_total REAL,
            total REAL,
            applied_price_list_id INTEGER,
            created_at TEXT,
            updated_at TEXT,
            deleted_at TEXT
        )");

        $this->db->query("CREATE TABLE db_order_items (
            id INTEGER PRIMARY KEY {$auto},
            order_id INTEGER,
            product_id INTEGER,
            variant_id INTEGER,
            quantity REAL,
            base_price REAL,
            final_price REAL,
            price_list_id INTEGER,
            price_list_name TEXT,
            created_at TEXT,
            updated_at TEXT
        )");

        if (strtolower($this->db->DBDriver) !== 'sqlite3') {
            // keep FK checks off for test schema
            $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        }
    }
}
