<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Repositories\Products\ProductRepository;

class TestFilterV2 extends BaseCommand
{
    protected $group       = 'Testing';
    protected $name        = 'test:filter_v2';
    protected $description = 'Test Product Filter Logic V2';

    public function run(array $params)
    {
        $repo = new ProductRepository();

        CLI::write("1. Testing General Price List (ID=1) - Should be Full List", 'yellow');
        $all = $repo->count(['price_list_id' => 1]);
        CLI::write("   -> Count: $all (Expected ~100)");

        CLI::write("\n2. Testing 'Sale 20%' Price List (ID=2) - Should be Subset", 'yellow');
        $subset = $repo->count(['price_list_id' => 2]);
        CLI::write("   -> Count: $subset (Expected ~10)");
        
        if ($subset > 0 && $subset < $all) {
            CLI::write("   -> OK: Custom list is a subset.", 'green');
        } else {
            CLI::error("   -> Error: Subset logic failed. Subset: $subset, All: $all");
        }

        // Check Price difference
        $res = $repo->findAll(['price_list_id' => 2, 'limit' => 1]);
        if (!empty($res)) {
            CLI::write("   -> Sale Price: " . $res[0]['selling_price']);
            // Compare with base
            $p = \Config\Database::connect()->table('products')->where('id', $res[0]['id'])->get()->getRowArray();
            if ($p) {
                CLI::write("   -> Base Price: " . $p['selling_price']);
                if ($res[0]['selling_price'] < $p['selling_price']) {
                    CLI::write("   -> OK: Price is reduced.", 'green');
                }
            }
        }
    }
}
