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
        $itemRepo->replaceItems($list, $items);
        $this->assertEquals($count, $this->db->table('db_price_list_items')->where('price_list_id', $list)->countAllResults());
    }

    /** @test */
    public function it_soft_deletes_preserve_row()
    {
        $list = $this->seedList('Soft', 'custom');
        $this->repo->delete($list);
        $row = $this->db->table('db_price_lists')->where('id', $list)->get()->getRowArray();
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
        $this->db->table('db_price_lists')->insert($payload);
        return (int) $this->db->insertID();
    }
}
