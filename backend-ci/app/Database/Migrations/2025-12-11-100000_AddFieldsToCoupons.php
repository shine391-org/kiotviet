<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFieldsToCoupons extends Migration
{
    public function up()
    {
        // Add new fields to coupons table for voucher campaign support
        $fields = [
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'after' => 'id',
                'null' => true,
            ],
            'start_date' => [
                'type' => 'DATE',
                'null' => true,
                'after' => 'min_amount',
            ],
            'validity_type' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'date_range',
                'after' => 'expiry_date',
            ],
            'validity_period' => [
                'type' => 'INT',
                'null' => true,
                'after' => 'validity_type',
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'status',
            ],
            'branch_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
                'after' => 'description',
            ],
            'customer_group_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
                'after' => 'branch_id',
            ],
            'creator_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
                'after' => 'customer_group_id',
            ],
            'is_combinable' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'after' => 'creator_id',
            ],
        ];

        $this->forge->addColumn('coupons', $fields);

        // Add foreign keys
        $this->db->query('ALTER TABLE `coupons` ADD CONSTRAINT `fk_coupons_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE SET NULL ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE `coupons` ADD CONSTRAINT `fk_coupons_customer_group` FOREIGN KEY (`customer_group_id`) REFERENCES `customer_groups`(`id`) ON DELETE SET NULL ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE `coupons` ADD CONSTRAINT `fk_coupons_creator` FOREIGN KEY (`creator_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE');
    }

    public function down()
    {
        // Drop foreign keys first
        $this->db->query('ALTER TABLE `coupons` DROP FOREIGN KEY `fk_coupons_branch`');
        $this->db->query('ALTER TABLE `coupons` DROP FOREIGN KEY `fk_coupons_customer_group`');
        $this->db->query('ALTER TABLE `coupons` DROP FOREIGN KEY `fk_coupons_creator`');

        // Drop columns
        $this->forge->dropColumn('coupons', [
            'name',
            'start_date',
            'validity_type',
            'validity_period',
            'description',
            'branch_id',
            'customer_group_id',
            'creator_id',
            'is_combinable',
        ]);
    }
}
