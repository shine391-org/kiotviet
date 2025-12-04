<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddConfigToPriceLists extends Migration
{
    public function up()
    {
        $this->forge->addColumn('price_lists', [
            'config' => [
                'type' => 'JSON',
                'null' => true,
                'default' => null,
                'after' => 'rounding_rule',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('price_lists', 'config');
    }
}
