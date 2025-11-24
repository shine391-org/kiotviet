<?php

namespace Tests\Support\Database;

trait ReturnSchemaTrait
{
    protected function resetReturnSchema(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        foreach (['db_return_items','return_items','db_returns','returns','db_order_items','order_items','db_orders','orders','db_customers','customers','db_users','users'] as $tbl) {
            $this->db->query("DROP TABLE IF EXISTS {$tbl}");
        }

        $this->db->query('CREATE TABLE users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50),
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $this->db->query('CREATE TABLE db_users LIKE users');

        $this->db->query('CREATE TABLE customers (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255),
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $this->db->query('CREATE TABLE db_customers LIKE customers');

        $this->db->query('CREATE TABLE orders (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_id INT,
            branch_id INT,
            status VARCHAR(50),
            total DECIMAL(14,2),
            shipping_fee DECIMAL(14,2),
            completed_at DATETIME NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $this->db->query('CREATE TABLE db_orders LIKE orders');

        $this->db->query('CREATE TABLE order_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id INT,
            product_id INT,
            variant_id INT NULL,
            quantity DECIMAL(14,3),
            base_price DECIMAL(14,2),
            final_price DECIMAL(14,2),
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $this->db->query('CREATE TABLE db_order_items LIKE order_items');

        $this->db->query('CREATE TABLE returns (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            return_number VARCHAR(50) UNIQUE,
            order_id INT,
            customer_id INT,
            return_amount DECIMAL(14,2),
            refund_shipping_fee TINYINT(1),
            refund_amount DECIMAL(14,2),
            refund_method VARCHAR(50),
            reason VARCHAR(50),
            reason_detail TEXT,
            status VARCHAR(50),
            approved_by INT NULL,
            approved_at DATETIME NULL,
            rejected_by INT NULL,
            rejected_at DATETIME NULL,
            completed_at DATETIME NULL,
            notes TEXT,
            lock_version INT DEFAULT 0,
            created_by INT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $this->db->query('CREATE TABLE db_returns LIKE returns');

        $this->db->query('CREATE TABLE return_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            return_id INT,
            order_item_id INT,
            quantity_returned DECIMAL(14,3),
            item_condition VARCHAR(50),
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $this->db->query('CREATE TABLE db_return_items LIKE return_items');

        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }
}
