<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Create payment_methods master table and link to orders.
 *
 * @agent-migration: Payment methods
 * @agent-pattern: Master data + FK
 */
class CreatePaymentMethodTables extends Migration
{
    public function up()
    {
        $jsonType = strtolower($this->db->DBDriver) === 'sqlite3' ? 'TEXT' : 'JSON';

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false, 'comment' => 'UPPERCASE code'],
            'name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'name_translations' => ['type' => $jsonType, 'null' => true, 'comment' => 'Optional i18n names'],
            'description' => ['type' => 'TEXT', 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'display_order' => ['type' => 'INT', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('is_active');
        $this->forge->addKey('display_order');
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('payment_methods', true);

        // Add payment_method column to orders for FK relationship if missing.
        if ($this->db->tableExists('orders')) {
            $fields = array_map('strtolower', $this->db->getFieldNames('orders'));
            if (! in_array('payment_method', $fields, true)) {
                $this->forge->addColumn('orders', [
                    'payment_method' => [
                        'type' => 'VARCHAR',
                        'constraint' => 50,
                        'null' => true,
                        'after' => 'status',
                        'comment' => 'FK to payment_methods.code',
                    ],
                ]);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('orders')) {
            $fields = array_map('strtolower', $this->db->getFieldNames('orders'));
            if (in_array('payment_method', $fields, true)) {
                $this->forge->dropColumn('orders', 'payment_method');
            }
        }

        $this->forge->dropTable('payment_methods', true);
    }
}
