<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\PriceLists\PriceListService;
use App\Repositories\PriceLists\PriceListItemRepository;

class TestAddItems extends BaseCommand
{
    protected $group       = 'Testing';
    protected $name        = 'test:add_items';
    protected $description = 'Test Price List Add Items Logic';

    public function run(array $params)
    {
        $service = new PriceListService();
        $repo = new PriceListItemRepository();

        CLI::write("1. Creating Test Price List...", 'yellow');
        try {
            // Cleanup existing
            $db = \Config\Database::connect();
            $db->query("DELETE FROM price_lists WHERE name = 'Test Add List'");
            
            $data = [
                'name' => 'Test Add List',
                'start_date' => date('Y-m-d'),
                'is_active' => 1,
                'is_system' => 0
            ];
            $pl = $service->create($data)['data'];
            $id = $pl['id'];
            CLI::write("   -> Created ID: $id", 'green');

            CLI::write("\n2. Initial Check (Should be Empty)...", 'yellow');
            $items = $service->items($id)['data'];
            CLI::write("   -> Count: " . count($items));
            if (count($items) === 0) {
                 CLI::write("   -> OK", 'green');
            } else {
                 CLI::error("   -> Failed: Not empty");
            }

            CLI::write("\n3. Adding Items...", 'yellow');
            // Fetch real product ID
            $prodRepo = new \App\Repositories\Products\ProductRepository();
            $products = $prodRepo->findAll(['limit' => 2]);
            if (empty($products)) {
                CLI::error("No products found to add!");
                return;
            }
            $p1 = $products[0]['id'];
            $p2 = isset($products[1]) ? $products[1]['id'] : $p1;
            
            $payload = [
                ['product_id' => $p1, 'price' => 120000],
                ['product_id' => $p2, 'price' => 250000]
            ];
            $res = $service->addItems($id, $payload);
            CLI::write("   -> Inserted Result: " . print_r($res, true));
            
            $items = $service->items($id)['data'];
            CLI::write("   -> New Count: " . count($items));
             if (count($items) >= 1) {
                 CLI::write("   -> OK", 'green');
            } else {
                 CLI::error("   -> Failed: Count mismatch (Expected >0)");
            }

            CLI::write("\n4. Adding Dupes (Should Update/Ignore)...", 'yellow');
            $payload2 = [
                ['product_id' => $p1, 'price' => 999999] 
            ];
            $service->addItems($id, $payload2);
            
            $item1 = $repo->findItem($id, $p1, null);
            if (!$item1) {
                CLI::error("   -> Item not found!");
            } else {
                CLI::write("   -> Product $p1 Price: " . $item1['price']);
                if ($item1['price'] == 999999) {
                    CLI::write("   -> OK: Price updated", 'green');
                } else {
                    CLI::error("   -> Failed: Price not updated");
                }
            }
            
            // Clean up
            $service->delete($id);
            CLI::write("\nDone. Cleanup complete.", 'green');

        } catch (\Exception $e) {
            CLI::error($e->getMessage());
            CLI::error($e->getTraceAsString());
        }
    }
}
