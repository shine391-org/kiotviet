<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIsSystemToPriceLists extends Migration
{
    public function up()
    {
        $fields = [
            'is_system' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'is_active',
            ],
        ];
        $this->forge->addColumn('price_lists', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('price_lists', 'is_system');
    }
}
