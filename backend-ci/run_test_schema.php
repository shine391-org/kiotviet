<?php
require 'vendor/autoload.php';
require 'vendor/codeigniter4/framework/system/Test/bootstrap.php';
$mig = new \App\Database\Migrations\TestSchemaSetup();
$mig->up();
echo "done\n";
