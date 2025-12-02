<?php

namespace Tests\Repositories;

use App\Repositories\PriceLists\PriceListRepository;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Tests\Support\Database\PriceListSchemaTrait;

/** @agent-test: PriceListRepository tests @agent-pattern: Repository coverage */
class PriceListRepositoryTest extends CIUnitTestCase
{
    use PriceListSchemaTrait;

    private PriceListRepository $repo;
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect('tests');
        $this->resetPriceListSchema();
        $this->repo = new PriceListRepository(null, $this->db);
    }

    /** @test */
    public function it_finds_applicable_lists_with_filters()
    {
        $vip = $this->seedList('VIP', 'vip', ['apply_to_groups' => [2], 'priority' => 5]);
        $retail = $this->seedList('Retail', 'retail', ['apply_to_groups' => [], 'priority' => 1]);

        $result = $this->repo->applicablePriceLists(2, date('Y-m-d'));
        $ids = array_column($result, 'id');
        $this->assertEquals([$vip, $retail], $ids);
    }

    /** @test */
    public function it_bulk_upserts_items()
    {
        $list = $this->seedList('Bulk', 'custom');
        $count = 50;
        $items = [];
        for ($i = 1; $i <= $count; $i++) {
            $items[] = ['product_id' => $i, 'price' => 100 + $i];
        }
        $res = $this->repo->findById($list); // ensure list exists
        $this->assertNotNull($res);
        $itemRepo = new \App\Repositories\PriceLists\PriceListItemRepository(null, $this->db);
        $res = $itemRepo->replaceItems($list, $items);
        $this->assertEquals($count, $res['inserted']);
    }

    /** @test */
    public function it_handles_large_bulk_insert()
    {
        $list = $this->seedList('BigBulk', 'custom');
        $count = 1000;
        $rows = [];
        for ($i = 1; $i <= $count; $i++) {
            $rows[] = ['product_id' => $i, 'price' => 10000 + $i];
        }
        $repo = new \App\Repositories\PriceLists\PriceListItemRepository(null, $this->db);
        $res = $repo->replaceItems($list, $rows);
        $this->assertEquals($count, $res['inserted']);
    }

    /** @test */
    public function it_imports_50_lists_with_1000_items_each()
    {
        $itemsPerList = 1000;
        $lists = 50;

        $this->seedProductsBulk($itemsPerList);

        $repo = new \App\Repositories\PriceLists\PriceListItemRepository(null, $this->db);
        $totalInserted = 0;

        for ($i = 1; $i <= $lists; $i++) {
            $listId = $this->seedList('Perf-' . $i, 'custom');
            $rows = [];
            for ($p = 1; $p <= $itemsPerList; $p++) {
                $rows[] = ['product_id' => $p, 'price' => 10000 + $p];
            }
            $res = $repo->replaceItems($listId, $rows);
            $totalInserted += $res['inserted'];
        }

        $this->assertEquals($lists * $itemsPerList, $totalInserted);
    }

    /** @test */
    public function it_imports_items_from_csv_and_json()
    {
        $list = $this->seedList('Import', 'custom');
        $this->seedProductsBulk(5);

        $csv = "product_id,price\n1,101\n2,202\n";
        $csvItems = [];
        foreach (explode("\n", trim($csv)) as $index => $line) {
            if ($index === 0) { continue; }
            $parts = str_getcsv($line);
            $csvItems[] = ['product_id' => (int) $parts[0], 'price' => (float) $parts[1]];
        }

        $json = '[{"product_id":3,"price":303},{"product_id":4,"price":404},{"product_id":5,"price":505}]';
        $jsonItems = json_decode($json, true);

        $rows = array_merge($csvItems, $jsonItems);

        $repo = new \App\Repositories\PriceLists\PriceListItemRepository(null, $this->db);
        $res = $repo->replaceItems($list, $rows);

        $this->assertEquals(count($rows), $res['inserted']);
    }

    /** @test */
    public function it_soft_deletes_preserve_row()
    {
        $list = $this->seedList('Soft', 'custom');
        $this->repo->delete($list);
        $row = $this->db->table('price_lists')->where('id', $list)->get()->getRowArray();
        $this->assertNotNull($row);
        $this->assertNotNull($row['deleted_at']);
    }

    private function seedList(string $name, string $type, array $extra = []): int
    {
        $payload = array_merge([
            'name' => $name,
            'type' => $type,
            'priority' => 0,
            'is_active' => 1,
            'start_date' => date('Y-m-d', strtotime('-1 day')),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $extra);
        $payload['apply_to_groups'] = isset($payload['apply_to_groups']) ? json_encode($payload['apply_to_groups']) : null;
        $this->db->table('price_lists')->insert($payload);
        return (int) $this->db->insertID();
    }

    private function seedProductsBulk(int $count): void
    {
        $existing = $this->db->table('products')->countAllResults();
        if ($existing >= $count) { return; }
        for ($i = 1; $i <= $count; $i++) {
            $this->db->table('products')->insert([
                'id' => $i,
                'code' => 'P' . $i,
                'name' => 'Product ' . $i,
                'selling_price' => 100 + $i,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
