<?php

namespace Tests\Support\Database;

trait ProductSchemaTrait
{
    protected function resetSchema(): void
    {
        // Assumes $this->db is initialized and connected to the test database
        $auto = strtoupper($this->db->DBDriver ?? '') === 'SQLITE3' ? 'AUTOINCREMENT' : 'AUTO_INCREMENT';

        $this->db->query('DROP TABLE IF EXISTS db_product_attribute_values');
        $this->db->query('DROP TABLE IF EXISTS db_product_images');
        $this->db->query('DROP TABLE IF EXISTS db_product_category_links');
        $this->db->query('DROP TABLE IF EXISTS db_product_variants_v2');
        $this->db->query('DROP TABLE IF EXISTS db_products');

        $this->db->query("CREATE TABLE db_products (
            id INTEGER PRIMARY KEY {$auto},
            product_type TEXT DEFAULT 'goods',
            code TEXT,
            barcode TEXT,
            name TEXT,
            slug TEXT,
            brand TEXT,
            unit TEXT DEFAULT 'Cái',
            purchase_price REAL DEFAULT 0,
            selling_price REAL DEFAULT 0,
            wholesale_price REAL,
            stock_quantity INTEGER DEFAULT 0,
            alert_stock INTEGER DEFAULT 0,
            has_variants INTEGER DEFAULT 0,
            image TEXT,
            images TEXT,
            weight REAL DEFAULT 0,
            dimensions TEXT,
            description TEXT,
            content TEXT,
            is_active INTEGER DEFAULT 1,
            is_available_online INTEGER DEFAULT 1,
            is_featured INTEGER DEFAULT 0,
            status TEXT DEFAULT 'active',
            meta_title TEXT,
            meta_description TEXT,
            meta_keywords TEXT,
            created_at TEXT,
            updated_at TEXT,
            deleted_at TEXT
        )");

        $this->db->query("CREATE TABLE db_product_variants_v2 (
            id INTEGER PRIMARY KEY {$auto},
            product_id INTEGER,
            variant_name TEXT,
            variant_signature TEXT,
            sku TEXT,
            barcode TEXT,
            price REAL,
            cost_price REAL,
            stock_quantity REAL,
            min_stock REAL,
            max_stock REAL,
            image_url TEXT,
            attributes TEXT,
            status TEXT,
            created_at TEXT,
            updated_at TEXT,
            deleted_at TEXT
        )");

        $this->db->query("CREATE TABLE db_product_category_links (
             id INTEGER PRIMARY KEY {$auto},
             product_id INTEGER,
             category_id INTEGER,
             created_at TEXT
        )");

         $this->db->query("CREATE TABLE db_product_images (
             id INTEGER PRIMARY KEY {$auto},
             product_id INTEGER,
             variant_id INTEGER,
             image_path TEXT,
             image_url TEXT,
             is_primary INTEGER DEFAULT 0,
             sort_order INTEGER DEFAULT 0,
             file_name TEXT,
             created_at TEXT,
             updated_at TEXT,
             deleted_at TEXT
        )");

        $this->db->query("CREATE TABLE db_product_attribute_values (
             id INTEGER PRIMARY KEY {$auto},
             product_id INTEGER,
             variant_id INTEGER,
             attribute_id INTEGER,
             option_id INTEGER,
             value_text TEXT,
             created_at TEXT,
             updated_at TEXT,
             deleted_at TEXT
        )");
    }
}
