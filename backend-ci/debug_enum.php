<?php
// debug_enum.php
$db = \Config\Database::connect();
$query = $db->query("SHOW CREATE TABLE partners");
$row = $query->getRowArray();
echo $row['Create Table'];
