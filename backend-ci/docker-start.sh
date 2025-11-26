#!/bin/bash
set -e
cd /var/www/html

# wait for DB
for i in {1..30}; do
  php -r 'try{new mysqli("db","lanocrm_user","KP7n4RjcDbedSE2W8GgA","lanocrm_shop"); exit(0);}catch(Throwable $e){ exit(1);}';
  if [ $? -eq 0 ]; then echo "DB ready"; break; fi
  echo "Waiting DB..."; sleep 2;
done

# Manual schema bootstrap (drop all tables, create minimal schema for demo/test)
cat <<'PHP' | php || true
<?php
$db = new mysqli('db','lanocrm_user','KP7n4RjcDbedSE2W8GgA','lanocrm_shop');
if ($db->connect_errno) { fwrite(STDERR, "DB connect failed\n"); exit(1);} 

// Drop everything
$tables = $db->query("SHOW TABLES");
while ($row = $tables->fetch_row()) { $db->query("DROP TABLE IF EXISTS `{$row[0]}`"); }

$sql = [
  "CREATE TABLE branches (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), code VARCHAR(50), status VARCHAR(20) DEFAULT 'active', created_at DATETIME NULL, updated_at DATETIME NULL, deleted_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  "CREATE TABLE users (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, username VARCHAR(100), email VARCHAR(255), password VARCHAR(255), full_name VARCHAR(255), phone VARCHAR(20), avatar VARCHAR(255), branch_id BIGINT UNSIGNED NULL, status ENUM('active','inactive','suspended') DEFAULT 'active', last_login_at DATETIME NULL, last_login_ip VARCHAR(45) NULL, remember_token VARCHAR(100) NULL, two_factor_secret VARCHAR(255) NULL, two_factor_enabled TINYINT(1) DEFAULT 0, password_changed_at DATETIME NULL, failed_login_attempts INT DEFAULT 0, account_locked_until DATETIME NULL, timezone VARCHAR(50) DEFAULT 'Asia/Ho_Chi_Minh', created_at DATETIME NULL, updated_at DATETIME NULL, deleted_at DATETIME NULL, UNIQUE KEY uk_users_username(username), UNIQUE KEY uk_users_email(email)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  "CREATE TABLE roles (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), guard_name VARCHAR(255), description TEXT NULL, is_system TINYINT(1) DEFAULT 0, created_at DATETIME NULL, updated_at DATETIME NULL, deleted_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  "CREATE TABLE permissions (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), display_name VARCHAR(255) NULL, description TEXT NULL, module VARCHAR(100) NULL, module_group VARCHAR(100) NULL, guard_name VARCHAR(50) NULL, created_at DATETIME NULL, updated_at DATETIME NULL, deleted_at DATETIME NULL, UNIQUE KEY uk_permissions_name(name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  "CREATE TABLE role_has_permissions (permission_id BIGINT UNSIGNED, role_id BIGINT UNSIGNED, PRIMARY KEY(permission_id, role_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  "CREATE TABLE model_has_roles (role_id BIGINT UNSIGNED, model_type VARCHAR(255), model_id BIGINT UNSIGNED, PRIMARY KEY(role_id, model_id, model_type)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

  "CREATE TABLE payment_methods (id INT AUTO_INCREMENT PRIMARY KEY, code VARCHAR(50), name VARCHAR(255), name_translations JSON NULL, description TEXT, is_active TINYINT(1) DEFAULT 1, display_order INT DEFAULT 0, created_at DATETIME NULL, updated_at DATETIME NULL, deleted_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

  "CREATE TABLE products (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, product_type ENUM('goods','service','combo') DEFAULT 'goods', code VARCHAR(50), barcode VARCHAR(100) NULL, name VARCHAR(500), slug VARCHAR(500), brand VARCHAR(100) NULL, unit VARCHAR(50) DEFAULT 'Cái', purchase_price DECIMAL(15,2) DEFAULT 0, selling_price DECIMAL(15,2) DEFAULT 0, wholesale_price DECIMAL(15,2) NULL, stock_quantity INT DEFAULT 0, alert_stock INT DEFAULT 0, has_variants TINYINT(1) DEFAULT 0, image VARCHAR(500) NULL, images TEXT NULL, weight DECIMAL(10,2) DEFAULT 0, dimensions VARCHAR(100) NULL, description TEXT NULL, content LONGTEXT NULL, is_active TINYINT(1) DEFAULT 1, is_available_online TINYINT(1) DEFAULT 1, is_featured TINYINT(1) DEFAULT 0, status ENUM('active','inactive','discontinued') DEFAULT 'active', meta_title VARCHAR(255) NULL, meta_description TEXT NULL, meta_keywords TEXT NULL, created_at DATETIME NULL, updated_at DATETIME NULL, deleted_at DATETIME NULL, UNIQUE KEY uk_products_code(code)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  "CREATE TABLE product_categories (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, parent_id BIGINT UNSIGNED NULL, product_id BIGINT UNSIGNED DEFAULT 0, level TINYINT DEFAULT 1, is_variant_group TINYINT DEFAULT 0, code VARCHAR(50), name VARCHAR(255), slug VARCHAR(255), description TEXT NULL, image VARCHAR(255) NULL, sort_order INT DEFAULT 0, status ENUM('active','inactive') DEFAULT 'active', created_at DATETIME NULL, updated_at DATETIME NULL, deleted_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  "CREATE TABLE product_category_links (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, product_id BIGINT UNSIGNED, category_id BIGINT UNSIGNED, created_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  "CREATE TABLE product_attributes (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), slug VARCHAR(255), attribute_key VARCHAR(100), type VARCHAR(50), is_required TINYINT DEFAULT 0, is_filterable TINYINT DEFAULT 0, sort_order INT DEFAULT 0, status VARCHAR(20) DEFAULT 'active', is_visible TINYINT DEFAULT 1, created_at DATETIME NULL, updated_at DATETIME NULL, deleted_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  "CREATE TABLE product_attribute_options (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, attribute_id BIGINT UNSIGNED, option_name VARCHAR(255), color_code VARCHAR(50) NULL, sort_order INT DEFAULT 0, status VARCHAR(20) DEFAULT 'active', created_at DATETIME NULL, updated_at DATETIME NULL, deleted_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  "CREATE TABLE product_variants_v2 (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, product_id BIGINT UNSIGNED, variant_name VARCHAR(255) NULL, variant_signature VARCHAR(255) NULL, sku VARCHAR(100), barcode VARCHAR(100) NULL, price DECIMAL(10,2), cost_price DECIMAL(10,2) NULL, stock_quantity DECIMAL(10,2) DEFAULT 0, min_stock DECIMAL(10,2) DEFAULT 0, max_stock DECIMAL(10,2) DEFAULT 0, image_url VARCHAR(255) NULL, attributes TEXT NULL, status ENUM('active','inactive') DEFAULT 'active', created_at DATETIME NULL, updated_at DATETIME NULL, deleted_at DATETIME NULL, KEY idx_variants_sku_deleted_at (sku, deleted_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  "CREATE TABLE product_images (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, product_id BIGINT UNSIGNED, variant_id BIGINT UNSIGNED NULL, image_path VARCHAR(255) NULL, image_url VARCHAR(255) NULL, is_primary TINYINT DEFAULT 0, sort_order INT DEFAULT 0, file_name VARCHAR(255) NULL, created_at DATETIME NULL, updated_at DATETIME NULL, deleted_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  "CREATE TABLE product_attribute_values (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, product_id BIGINT UNSIGNED, variant_id BIGINT UNSIGNED NULL, attribute_id BIGINT UNSIGNED, option_id BIGINT UNSIGNED, value_text TEXT NULL, created_at DATETIME NULL, updated_at DATETIME NULL, deleted_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

  "CREATE TABLE price_lists (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), type VARCHAR(50) DEFAULT 'custom', priority INT DEFAULT 0, is_active TINYINT DEFAULT 1, start_date DATE NULL, end_date DATE NULL, apply_to_groups JSON NULL, formula TEXT NULL, base_price_list_id INT NULL, auto_update TINYINT DEFAULT 0, rounding_rule VARCHAR(50) DEFAULT 'none', created_at DATETIME NULL, updated_at DATETIME NULL, deleted_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  "CREATE TABLE price_list_items (id INT AUTO_INCREMENT PRIMARY KEY, price_list_id INT, product_id INT, variant_id INT NULL, price DECIMAL(10,2) NOT NULL, discount_percent DECIMAL(5,2) DEFAULT 0, discount_amount DECIMAL(10,2) DEFAULT 0, created_at DATETIME NULL, updated_at DATETIME NULL, deleted_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

  "CREATE TABLE customers (id INT AUTO_INCREMENT PRIMARY KEY, organization_id INT UNSIGNED NOT NULL DEFAULT 1, customer_group_id INT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NULL, phone VARCHAR(50) NULL, phone2 VARCHAR(50) NULL, gender ENUM('MALE','FEMALE','OTHER') NULL, facebook VARCHAR(255) NULL, customer_type ENUM('INDIVIDUAL','COMPANY','HOUSEHOLD') NOT NULL DEFAULT 'INDIVIDUAL', company_name VARCHAR(255) NULL, tax_code VARCHAR(20) NULL, buyer_name VARCHAR(255) NULL, invoice_company_name VARCHAR(255) NULL, invoice_address VARCHAR(500) NULL, invoice_province VARCHAR(120) NULL, invoice_district VARCHAR(120) NULL, invoice_ward VARCHAR(120) NULL, invoice_email VARCHAR(255) NULL, invoice_phone VARCHAR(50) NULL, cccd_cmnd VARCHAR(50) NULL, id_number VARCHAR(50) NULL, bank_account VARCHAR(50) NULL, bank_name VARCHAR(255) NULL, notes TEXT NULL, created_at DATETIME NULL, updated_at DATETIME NULL, deleted_at DATETIME NULL, UNIQUE KEY unique_tax_code_per_org (organization_id, tax_code), KEY idx_customers_tax_code (tax_code)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

  "CREATE TABLE orders (id INT AUTO_INCREMENT PRIMARY KEY, order_number VARCHAR(50), customer_id INT NULL, customer_group_id INT NULL, branch_id INT NULL, order_date DATE NULL, order_type VARCHAR(50) DEFAULT 'online', payment_method VARCHAR(50) NULL, status VARCHAR(50) DEFAULT 'draft', subtotal DECIMAL(10,2) DEFAULT 0, discount_total DECIMAL(10,2) DEFAULT 0, shipping_fee DECIMAL(10,2) DEFAULT 0, total DECIMAL(10,2) DEFAULT 0, paid_amount DECIMAL(10,2) DEFAULT 0, debt_amount DECIMAL(10,2) DEFAULT 0, is_paid TINYINT DEFAULT 0, applied_price_list_id INT NULL, shipping_name VARCHAR(255) NULL, shipping_phone VARCHAR(50) NULL, shipping_address TEXT NULL, shipping_ward VARCHAR(100) NULL, shipping_district VARCHAR(100) NULL, shipping_city VARCHAR(100) NULL, notes TEXT NULL, created_by INT NULL, created_at DATETIME NULL, updated_at DATETIME NULL, deleted_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  "CREATE TABLE order_items (id INT AUTO_INCREMENT PRIMARY KEY, order_id INT, product_id INT, variant_id INT NULL, quantity DECIMAL(10,2) DEFAULT 0, base_price DECIMAL(10,2) DEFAULT 0, final_price DECIMAL(10,2) DEFAULT 0, price_list_id INT NULL, price_list_name VARCHAR(255) NULL, created_at DATETIME NULL, updated_at DATETIME NULL, deleted_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  "CREATE TABLE order_sequences (id INT AUTO_INCREMENT PRIMARY KEY, branch_id INT, sequence_number INT DEFAULT 1, prefix VARCHAR(20) DEFAULT 'ORD', created_at DATETIME NULL, updated_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

  "CREATE TABLE inventory_stock (id INT AUTO_INCREMENT PRIMARY KEY, branch_id INT, warehouse_id INT, product_id INT, variant_id INT, quantity_on_hand DECIMAL(10,2) DEFAULT 0, quantity_reserved DECIMAL(10,2) DEFAULT 0, minimum_stock DECIMAL(10,2) DEFAULT 0, last_movement_at DATETIME NULL, created_at DATETIME NULL, updated_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];
foreach ($sql as $q) { $db->query($q); }
?>
PHP

php spark db:seed DevDemoSeeder || true

exec apache2-foreground
