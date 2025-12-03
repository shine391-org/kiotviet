<?php
define('FCPATH', __DIR__ . '/public/');
require __DIR__ . '/vendor/codeigniter4/framework/system/Test/bootstrap.php';

use Config\Database;

$db = Database::connect('tests');
$db->query('DROP TABLE IF EXISTS migrations');
echo "Dropped migrations table.\n";
