<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropLegacyStockTable extends Migration
{
    public function up()
    {
        // Drop the table if it exists
        // Foreign keys inside it will be dropped automatically with the table
        // But if there were external keys referencing it, we'd need to drop them first.
        // We added FKs *to* it in the previous migration, but nothing references *it* (except maybe itself? No).
        $this->forge->dropTable('product_stock_by_branch', true);
    }

    public function down()
    {
        // Re-create the table if we roll back
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'branch_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'stock_quantity' => ['type' => 'INT', 'default' => 0, 'comment' => 'Số lượng tồn'],
            'alert_stock' => ['type' => 'INT', 'default' => 10, 'comment' => 'Ngưỡng cảnh báo'],
            'reserved_stock' => ['type' => 'INT', 'default' => 0, 'comment' => 'Hàng đang giữ'],
            'available_stock' => ['type' => 'INT', 'default' => 0, 'comment' => 'Có thể bán'],
            'expiry_days' => ['type' => 'INT', 'null' => true, 'comment' => 'Dự kiến hết hàng (ngày)'],
            'last_stock_date' => ['type' => 'DATETIME', 'null' => true, 'comment' => 'Lần cập nhật tồn cuối'],
            'status' => ['type' => 'ENUM', 'constraint' => ['in_stock','low_stock','out_of_stock'], 'default' => 'in_stock'],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP')],
            'updated_at' => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['product_id', 'branch_id'], 'unique_product_branch');
        $this->forge->addKey('branch_id', 'idx_branch');
        $this->forge->addKey('status', 'idx_status');
        $this->forge->createTable('product_stock_by_branch', true);

        // Restore Foreign Keys
        $this->db->query('ALTER TABLE product_stock_by_branch ADD CONSTRAINT fk_product_stock_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE');
        $this->db->query('ALTER TABLE product_stock_by_branch ADD CONSTRAINT fk_product_stock_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE');
    }
}
