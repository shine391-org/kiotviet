<?php
$_SERVER['CI_ENVIRONMENT'] = 'testing';
require 'vendor/codeigniter4/framework/system/Test/bootstrap.php';
use Config\Database;
$db = Database::connect('tests');
$db->table('inventory_stock')->truncate();
$ok = $db->table('inventory_stock')->insert([
    'branch_id' => null,
    'product_id' => 1,
    'variant_id' => null,
    'warehouse_id' => 101,
    'quantity_on_hand' => 10,
    'quantity_reserved' => 0,
    'minimum_stock' => 0,
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
]);
var_dump('insert ok', $ok, $db->insertID(), $db->error());
var_dump($db->table('inventory_stock')->get()->getResultArray());
