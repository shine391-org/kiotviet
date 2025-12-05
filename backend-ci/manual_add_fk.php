<?php
define('FCPATH', __DIR__ . '/public/');
require __DIR__ . '/vendor/codeigniter4/framework/system/Test/bootstrap.php';

use Config\Database;

$db = Database::connect('tests');

try {
    $sql = "ALTER TABLE cash_transactions ADD CONSTRAINT fk_cash_transactions_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE ON UPDATE CASCADE";
    $db->query($sql);
    echo "Successfully added FK.\n";
} catch (\Throwable $e) {
    echo "Error adding FK: " . $e->getMessage() . "\n";
}
