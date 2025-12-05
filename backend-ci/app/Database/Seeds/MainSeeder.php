<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MainSeeder extends Seeder
{
    public function run()
    {
        $this->call('CategorySeeder');
        $this->call('PartnerSeeder'); // Suppliers
        $this->call('CustomerSeeder'); // Customers
        $this->call('ProductSeeder'); 
        $this->call('PriceListSeeder');
        $this->call('OrderSeeder'); 
    }
}
