<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRestoredForeignKeys extends Migration
{
    public function up()
    {
        // 1. product_images
        // Link to products
        $this->forge->addColumn('product_images', [
            'CONSTRAINT fk_product_images_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE'
        ]);
        // Link to product_variants_v2 (using variant_id)
        // Note: db_lano had variant_id linking to product_variants_v2
        $this->forge->addColumn('product_images', [
            'CONSTRAINT fk_product_images_variant FOREIGN KEY (variant_id) REFERENCES product_variants_v2(id) ON DELETE CASCADE ON UPDATE CASCADE'
        ]);

        // 2. product_prices
        // Link to products
        $this->forge->addColumn('product_prices', [
            'CONSTRAINT fk_product_prices_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE'
        ]);
        // Link to product_variants_v2 (assuming variant_id points here now as product_variants is gone)
        $this->forge->addColumn('product_prices', [
            'CONSTRAINT fk_product_prices_variant FOREIGN KEY (variant_id) REFERENCES product_variants_v2(id) ON DELETE CASCADE'
        ]);

        // 3. product_stock_by_branch
        // Link to products
        $this->forge->addColumn('product_stock_by_branch', [
            'CONSTRAINT fk_product_stock_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE'
        ]);
        // Link to branches
        $this->forge->addColumn('product_stock_by_branch', [
            'CONSTRAINT fk_product_stock_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE'
        ]);
    }

    public function down()
    {
        $this->forge->dropForeignKey('product_stock_by_branch', 'fk_product_stock_branch');
        $this->forge->dropForeignKey('product_stock_by_branch', 'fk_product_stock_product');
        
        $this->forge->dropForeignKey('product_prices', 'fk_product_prices_variant');
        $this->forge->dropForeignKey('product_prices', 'fk_product_prices_product');
        
        $this->forge->dropForeignKey('product_images', 'fk_product_images_variant');
        $this->forge->dropForeignKey('product_images', 'fk_product_images_product');
    }
}
