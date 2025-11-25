#!/bin/bash
set -e
cd /var/www/html

# wait for DB
for i in {1..30}; do
  php -r 'try{new mysqli("db","lanocrm_user","KP7n4RjcDbedSE2W8GgA","lanocrm_shop"); exit(0);}catch(Throwable $e){ exit(1);}';
  if [ $? -eq 0 ]; then echo "DB ready"; break; fi
  echo "Waiting DB..."; sleep 2;
done

# Quick schema bootstrap (minimal tables for dev demo)
cat <<'PHP' | php || true
<?php
$mysqli = new mysqli('db','lanocrm_user','KP7n4RjcDbedSE2W8GgA','lanocrm_shop');
if ($mysqli->connect_errno) { fwrite(STDERR, "DB connect failed\n"); exit(1);} 
$sql = [
  "CREATE TABLE IF NOT EXISTS branches (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), code VARCHAR(50), status VARCHAR(20) DEFAULT 'active', created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  "CREATE TABLE IF NOT EXISTS roles (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), guard_name VARCHAR(50), description TEXT, is_system TINYINT DEFAULT 0, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  "CREATE TABLE IF NOT EXISTS permissions (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), display_name VARCHAR(255), module VARCHAR(100), module_group VARCHAR(100), guard_name VARCHAR(50), created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  "CREATE TABLE IF NOT EXISTS model_has_roles (role_id INT, model_type VARCHAR(100), model_id INT)",
  "CREATE TABLE IF NOT EXISTS role_has_permissions (permission_id INT, role_id INT)",
  "CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(100), email VARCHAR(255), password VARCHAR(255), full_name VARCHAR(255), branch_id INT, status VARCHAR(20), created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL)",
  "CREATE TABLE IF NOT EXISTS product_categories (id INT AUTO_INCREMENT PRIMARY KEY, product_id INT, parent_id INT NULL, level INT, is_variant_group TINYINT, code VARCHAR(50), name VARCHAR(255), slug VARCHAR(255), sort_order INT, status VARCHAR(20), created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL)",
  "CREATE TABLE IF NOT EXISTS product_attributes (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), slug VARCHAR(255), attribute_key VARCHAR(100), type VARCHAR(50), is_required TINYINT DEFAULT 0, is_filterable TINYINT DEFAULT 0, sort_order INT, status VARCHAR(20), is_visible TINYINT DEFAULT 1, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL)",
  "CREATE TABLE IF NOT EXISTS product_attribute_options (id INT AUTO_INCREMENT PRIMARY KEY, attribute_id INT, option_name VARCHAR(255), color_code VARCHAR(50), sort_order INT, status VARCHAR(20), created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL)",
  "CREATE TABLE IF NOT EXISTS products (id INT AUTO_INCREMENT PRIMARY KEY, product_type VARCHAR(50), code VARCHAR(100), barcode VARCHAR(100), name VARCHAR(255), slug VARCHAR(255), brand VARCHAR(255), unit VARCHAR(50), purchase_price DECIMAL(10,2) DEFAULT 0, selling_price DECIMAL(10,2) DEFAULT 0, wholesale_price DECIMAL(10,2), stock_quantity DECIMAL(10,2) DEFAULT 0, alert_stock DECIMAL(10,2) DEFAULT 0, has_variants TINYINT DEFAULT 0, image VARCHAR(500), images JSON, weight DECIMAL(10,2) DEFAULT 0, dimensions TEXT, description TEXT, content TEXT, is_active TINYINT DEFAULT 1, is_available_online TINYINT DEFAULT 1, is_featured TINYINT DEFAULT 0, status VARCHAR(20) DEFAULT 'active', created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  "CREATE TABLE IF NOT EXISTS product_variants_v2 (id INT AUTO_INCREMENT PRIMARY KEY, product_id INT, variant_name VARCHAR(255), variant_signature VARCHAR(255), sku VARCHAR(100), barcode VARCHAR(100), price DECIMAL(10,2), cost_price DECIMAL(10,2), stock_quantity DECIMAL(10,2), min_stock DECIMAL(10,2), max_stock DECIMAL(10,2), image_url VARCHAR(255), attributes TEXT, status VARCHAR(20), created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  "CREATE TABLE IF NOT EXISTS price_lists (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), type VARCHAR(50) DEFAULT 'custom', priority INT DEFAULT 0, is_active TINYINT DEFAULT 1, start_date DATE NULL, end_date DATE NULL, apply_to_groups JSON NULL, formula TEXT NULL, base_price_list_id INT NULL, auto_update TINYINT DEFAULT 0, rounding_rule VARCHAR(50) DEFAULT 'none', created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL)",
  "CREATE TABLE IF NOT EXISTS price_list_items (id INT AUTO_INCREMENT PRIMARY KEY, price_list_id INT, product_id INT, variant_id INT NULL, price DECIMAL(10,2) NOT NULL, discount_percent DECIMAL(5,2) DEFAULT 0, discount_amount DECIMAL(10,2) DEFAULT 0, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL)",
  "CREATE TABLE IF NOT EXISTS customers (id INT AUTO_INCREMENT PRIMARY KEY, customer_group_id INT NULL, name VARCHAR(255), email VARCHAR(255) NULL, phone VARCHAR(50) NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL)",
  "CREATE TABLE IF NOT EXISTS orders (id INT AUTO_INCREMENT PRIMARY KEY, order_number VARCHAR(50), customer_id INT NULL, customer_group_id INT NULL, branch_id INT NULL, order_date DATE NULL, status VARCHAR(50), subtotal DECIMAL(10,2) DEFAULT 0, discount_total DECIMAL(10,2) DEFAULT 0, total DECIMAL(10,2) DEFAULT 0, applied_price_list_id INT NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL)",
  "CREATE TABLE IF NOT EXISTS order_items (id INT AUTO_INCREMENT PRIMARY KEY, order_id INT, product_id INT, variant_id INT NULL, quantity DECIMAL(10,2) DEFAULT 0, base_price DECIMAL(10,2) DEFAULT 0, final_price DECIMAL(10,2) DEFAULT 0, price_list_id INT NULL, price_list_name VARCHAR(255) NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL)",
  "CREATE TABLE IF NOT EXISTS order_sequences (id INT AUTO_INCREMENT PRIMARY KEY, branch_id INT, sequence_number INT DEFAULT 1, prefix VARCHAR(20) DEFAULT 'ORD', created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL)",
  "CREATE TABLE IF NOT EXISTS payment_methods (id INT AUTO_INCREMENT PRIMARY KEY, code VARCHAR(50), name VARCHAR(255), name_translations JSON NULL, description TEXT, is_active TINYINT DEFAULT 1, display_order INT DEFAULT 0, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL)",
  "CREATE TABLE IF NOT EXISTS inventory_stock (id INT AUTO_INCREMENT PRIMARY KEY, branch_id INT, warehouse_id INT, product_id INT, variant_id INT, quantity_on_hand DECIMAL(10,2) DEFAULT 0, quantity_reserved DECIMAL(10,2) DEFAULT 0, minimum_stock DECIMAL(10,2) DEFAULT 0, last_movement_at TIMESTAMP NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];
foreach ($sql as $q) { $mysqli->query($q); }
?>
PHP

php spark db:seed DevDemoSeeder || true

exec apache2-foreground
