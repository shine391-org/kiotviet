<?php

namespace Tests\Support\Database;

trait ProductSchemaTrait
{
    protected function resetSchema(): void
    {
        // Assumes $this->db is initialized and connected to the test database
        $auto = strtoupper($this->db->DBDriver ?? '') === 'SQLITE3' ? 'AUTOINCREMENT' : 'AUTO_INCREMENT';

        $this->db->query('DROP TABLE IF EXISTS branches');
        $this->db->query('DROP TABLE IF EXISTS users');
        $this->db->query('DROP TABLE IF EXISTS product_attribute_values');
        $this->db->query('DROP TABLE IF EXISTS product_images');
        $this->db->query('DROP TABLE IF EXISTS product_category_links');
        $this->db->query('DROP TABLE IF EXISTS product_variants_v2');
        $this->db->query('DROP TABLE IF EXISTS products');
        $this->db->query('DROP TABLE IF EXISTS attributes');
        $this->db->query('DROP TABLE IF EXISTS attribute_options');

        $this->db->query("CREATE TABLE products (
            id INTEGER PRIMARY KEY {$auto},
            product_type VARCHAR(50) DEFAULT 'goods',
            code TEXT,
            barcode TEXT,
            name TEXT,
            slug TEXT,
            brand TEXT,
            unit VARCHAR(50) DEFAULT 'Cái',
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
            status VARCHAR(20) DEFAULT 'active',
            meta_title TEXT,
            meta_description TEXT,
            meta_keywords TEXT,
            created_at TEXT,
            updated_at TEXT,
            deleted_at TEXT
        )");

        $this->db->query("CREATE TABLE branches (
            id INTEGER PRIMARY KEY {$auto},
            name TEXT,
            code TEXT,
            status TEXT,
            created_at TEXT,
            updated_at TEXT
        )");

        $this->db->query("CREATE TABLE users (
            id INTEGER PRIMARY KEY {$auto},
            username TEXT,
            email TEXT,
            status TEXT,
            created_at TEXT,
            updated_at TEXT
        )");

        $this->db->query("CREATE TABLE product_variants_v2 (
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

        $this->db->query("CREATE TABLE product_category_links (
             id INTEGER PRIMARY KEY {$auto},
             product_id INTEGER,
             category_id INTEGER,
             created_at TEXT
        )");

         $this->db->query("CREATE TABLE product_images (
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

        $this->db->query("CREATE TABLE product_attribute_values (
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

        $this->db->query("CREATE TABLE attributes (
             id INTEGER PRIMARY KEY {$auto},
             name TEXT,
             code TEXT,
             type TEXT,
             options TEXT,
             is_required INTEGER DEFAULT 0,
             is_filterable INTEGER DEFAULT 0,
             sort_order INTEGER DEFAULT 0,
             status VARCHAR(20) DEFAULT 'active',
             created_at TEXT,
             updated_at TEXT,
             deleted_at TEXT
        )");

        $this->db->query("CREATE TABLE attribute_options (
             id INTEGER PRIMARY KEY {$auto},
             attribute_id INTEGER,
             value TEXT,
             sort_order INTEGER DEFAULT 0,
             created_at TEXT,
             updated_at TEXT,
             deleted_at TEXT
        )");
    }
}
