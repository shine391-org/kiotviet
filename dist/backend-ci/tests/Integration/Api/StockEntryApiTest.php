<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: Stock entry API
 * @agent-pattern: FeatureTestTrait + DevDatabaseTrait
 */
class StockEntryApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DevDatabaseTrait;
    use AuthTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $config = config('Database');
        $config->tests['database'] = 'lanocrm_shop';
        require_once APPPATH . 'Database/Migrations/2025-11-21-000000_TestSchemaSetup.php';
        (new \App\Database\Migrations\TestSchemaSetup())->up();

        $this->setUpDatabase();
        $this->db->table('products')->truncate();
        $this->db->table('stock_bins')->truncate();
        $this->db->table('branches')->truncate();
        $this->db->table('warehouses')->truncate();
        $this->db->table('stock_entries')->truncate();
        $this->db->table('stock_moves')->truncate();
        
        $this->setUpAuthToken();
        $this->seedBase();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_transfer_and_updates_bins_via_api()
    {
        $createRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'type' => 'transfer',
                'branch_id' => 1,
                'source_warehouse_id' => 1,
                'target_warehouse_id' => 2,
                'items' => [
                    ['product_id' => 1, 'qty' => 5],
                ],
            ]))
            ->post('/api/stock-entries');
        $createRes->assertStatus(201);
        $entry = $this->getJsonFromResponse($createRes)['data'];

        $submitRes = $this->withHeaders($this->jsonHeaders())
            ->post('/api/stock-entries/' . $entry['id'] . '/submit');
        $submitRes->assertStatus(200);

        $sourceBin = $this->db->table('stock_bins')->where('branch_id', 1)->where('product_id', 1)->get()->getRowArray();
        $targetBin = $this->db->table('stock_bins')->where('branch_id', 2)->where('product_id', 1)->get()->getRowArray();
        $this->assertEquals(15.0, (float) $sourceBin['on_hand_qty']);
        $this->assertEquals(15.0, (float) $targetBin['on_hand_qty']);
    }

    /** @test */
    public function it_processes_return_entry_via_api()
    {
        $res = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'branch_id' => 1,
                'type' => 'return',
                'target_warehouse_id' => 1,
                'return_reason' => 'customer_return',
                'items' => [
                    ['product_id' => 1, 'qty' => 2],
                ],
            ]))
            ->post('/api/stock-entries/returns');
        $res->assertStatus(201);
        $payload = $this->getJsonFromResponse($res)['data'];
        $this->assertEquals('submitted', $payload['status']);

        $bin = $this->db->table('stock_bins')->where('branch_id', 1)->where('product_id', 1)->get()->getRowArray();
        $this->assertEquals(22.0, (float) $bin['on_hand_qty']);
    }

    /** @test */
    public function it_runs_pick_and_pack_flow()
    {
        $entryRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'type' => 'issue',
                'branch_id' => 1,
                'source_warehouse_id' => 1,
                'items' => [
                    ['product_id' => 1, 'qty' => 1],
                ],
            ]))
            ->post('/api/stock-entries');
        $entryRes->assertStatus(201);
        $entry = $this->getJsonFromResponse($entryRes)['data'];

        $pickRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'stock_entry_id' => $entry['id'],
                'source_warehouse_id' => 1,
                'items' => [
                    ['product_id' => 1, 'qty' => 1],
                ],
            ]))
            ->post('/api/pick-lists');
        $pickRes->assertStatus(201);
        $pick = $this->getJsonFromResponse($pickRes)['data'];

        $packRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'pick_list_id' => $pick['id'],
                'stock_entry_id' => $entry['id'],
                'source_warehouse_id' => 1,
                'target_warehouse_id' => 2,
                'items' => [
                    ['product_id' => 1, 'qty' => 1],
                ],
            ]))
            ->post('/api/packing-slips');
        $packRes->assertStatus(201);
        $pack = $this->getJsonFromResponse($packRes)['data'];

        $this->assertEquals('open', $pick['status']);
        $this->assertEquals('packed', $pack['status']);
    }

    private function seedBase(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('branches')->insert(['id' => 1, 'name' => 'HCM', 'created_at' => $now]);
        $this->db->table('branches')->insert(['id' => 2, 'name' => 'HN', 'created_at' => $now]);
        $this->db->table('warehouses')->insert(['id' => 1, 'name' => 'Main WH', 'branch_id' => 1, 'status' => 'active', 'created_at' => $now]);
        $this->db->table('warehouses')->insert(['id' => 2, 'name' => 'Secondary WH', 'branch_id' => 2, 'status' => 'active', 'created_at' => $now]);
        $this->db->table('products')->insert([
            'id' => 1,
            'code' => 'P1',
            'name' => 'Item',
            'product_type' => 'standard',
            'selling_price' => 10,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->db->table('stock_bins')->insert([
            'product_id' => 1,
            'variant_id' => null,
            'branch_id' => 1,
            'batch_id' => null,
            'on_hand_qty' => 20,
            'reserved_qty' => 0,
            'updated_at' => $now,
        ]);
        $this->db->table('stock_bins')->insert([
            'product_id' => 1,
            'variant_id' => null,
            'branch_id' => 2,
            'batch_id' => null,
            'on_hand_qty' => 10,
            'reserved_qty' => 0,
            'updated_at' => $now,
        ]);
    }

    private function getJsonFromResponse($response): array
    {
        $raw = $response->getBody();
        $jsonString = $raw;
        if (strpos($raw, '<!DOCTYPE html') !== false && preg_match('/<p>(.*?)<\\/p>/s', $raw, $m)) {
            $jsonString = html_entity_decode($m[1]);
        }
        return json_decode($jsonString, true);
    }

    private function jsonHeaders(): array
    {
        return $this->authHeaders(['Content-Type' => 'application/json']);
    }
}
