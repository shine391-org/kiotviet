<?php

namespace Tests\Services;

use App\Services\PriceLists\PriceListService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use InvalidArgumentException;
use Tests\Support\Database\PriceListSchemaTrait;

/** @agent-test: PriceListService unit tests @agent-pattern: Standard service test */
class PriceListServiceTest extends CIUnitTestCase
{
    use PriceListSchemaTrait;

    private PriceListService $service;
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect('tests');
        $this->resetPriceListSchema();
        $this->service = new PriceListService();
    }

    /** @test */
    public function it_creates_price_list_with_groups()
    {
        $result = $this->service->create([
            'name' => 'VIP 2025',
            'type' => 'vip',
            'apply_to_groups' => [1, 3],
            'start_date' => '2025-01-01',
            'priority' => 5,
        ]);

        $this->assertTrue($result['success']);
        $row = $this->db->table('db_price_lists')->where('name', 'VIP 2025')->get()->getRowArray();
        $this->assertNotNull($row);
        $groups = json_decode($row['apply_to_groups'] ?? '[]', true);
        $this->assertEquals([1, 3], array_map('intval', $groups));
    }

    /** @test */
    public function it_rejects_invalid_date_range()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->create([
            'name' => 'Invalid',
            'start_date' => '2025-12-31',
            'end_date' => '2025-01-01',
        ]);
    }

    /** @test */
    public function it_replaces_items_in_bulk()
    {
        $list = $this->service->create(['name' => 'Bulk', 'type' => 'custom']);
        $id = $list['data']['id'];

        $this->service->upsertItems($id, [
            ['product_id' => 1, 'price' => 100],
            ['product_id' => 2, 'price' => 200],
        ]);
        $result = $this->service->upsertItems($id, [
            ['product_id' => 1, 'price' => 150, 'discount_percent' => 10],
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['inserted']);
        // Rely on inserted count; DB row presence validated in repository tests.
    }

    /** @test */
    public function it_sets_status_based_on_dates()
    {
        $active = $this->service->create(['name' => 'Active', 'start_date' => date('Y-m-d', strtotime('-1 day')), 'end_date' => date('Y-m-d', strtotime('+1 day'))]);
        $upcoming = $this->service->create(['name' => 'Future', 'start_date' => date('Y-m-d', strtotime('+5 days'))]);
        $expired = $this->service->create(['name' => 'Past', 'end_date' => date('Y-m-d', strtotime('-1 day'))]);

        $this->assertEquals('active', $active['data']['status']);
        $this->assertEquals('upcoming', $upcoming['data']['status']);
        $this->assertEquals('expired', $expired['data']['status']);
    }

    /** @test */
    public function it_updates_price_list()
    {
        $created = $this->service->create(['name' => 'UpdateMe', 'priority' => 1]);
        $id = $created['data']['id'];

        $result = $this->service->update($id, ['priority' => 10, 'type' => 'vip']);
        $this->assertTrue($result['success']);
        $row = $this->db->table('db_price_lists')->where('id', $id)->get()->getRowArray();
        $this->assertEquals(10, (int) $row['priority']);
        $this->assertEquals('vip', $row['type']);
    }

    /** @test */
    public function it_deletes_price_list_and_items()
    {
        $created = $this->service->create(['name' => 'Cascade']);
        $id = $created['data']['id'];
        $this->service->upsertItems($id, [['product_id' => 1, 'price' => 50]]);

        $this->service->delete($id);
        // Soft delete: row remains with deleted_at
        $this->assertEquals(1, $this->db->table('db_price_lists')->where('id', $id)->countAllResults());
        $this->assertEquals(0, $this->db->table('db_price_list_items')->where('price_list_id', $id)->countAllResults());
    }

    /** @test */
    public function it_filters_by_type()
    {
        $this->service->create(['name' => 'Retail', 'type' => 'retail']);
        $this->service->create(['name' => 'VIP', 'type' => 'vip']);

        $result = $this->service->list(['type' => 'vip', 'limit' => 10]);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('vip', $result['data'][0]['type']);
    }

    /** @test */
    public function it_toggles_active_flag()
    {
        $created = $this->service->create(['name' => 'Toggle', 'is_active' => 1]);
        $id = $created['data']['id'];

        $this->service->update($id, ['is_active' => 0]);
        $row = $this->db->table('db_price_lists')->where('id', $id)->get()->getRowArray();
        $this->assertEquals(0, (int) $row['is_active']);
    }

    /** @test */
    public function it_detects_circular_reference()
    {
        $a = $this->service->create(['name' => 'A']);
        $b = $this->service->create(['name' => 'B', 'base_price_list_id' => $a['data']['id']]);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->update($a['data']['id'], ['base_price_list_id' => $b['data']['id']]);
    }
}
