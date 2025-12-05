<?php
define('FCPATH', __DIR__ . '/public/');
require __DIR__ . '/vendor/codeigniter4/framework/system/Test/bootstrap.php';

use Config\Database;

$db = Database::connect('tests');

$tables = ['orders', 'cash_transactions'];
foreach ($tables as $table) {
    if ($db->tableExists($table)) {
        echo "Table: $table\n";
        $res = $db->query("SHOW CREATE TABLE $table")->getRowArray();
        echo $res['Create Table'] . "\n\n";
    } else {
        echo "Table $table does not exist.\n";
    }
}
