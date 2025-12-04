<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\AuthTestTrait;

/**
 * @agent-test: Orders API list & detail
 * @agent-pattern: Integration test with DevDatabaseTrait + FeatureTestTrait
 */
class OrdersApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DevDatabaseTrait;
    use AuthTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure schema present for this suite
        $this->setUpDatabase();

        $this->setUpDatabase();
        $this->truncateTables();
        $this->seedBaseData();
        $this->setUpAuthToken();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_lists_orders_with_filters()
    {
        $this->createOrder('ORD-001', 'completed', 1, 1200000, 200000);
        $this->createOrder('ORD-002', 'draft', 2, 500000, 0);

        $response = $this->withHeaders($this->authHeaders())->get('/api/orders?status[]=completed&branch_id=1&limit=10');

        $response->assertStatus(200);
        $body = $this->getJsonFromResponse($response);
        $this->assertTrue($body['success']);
        $this->assertCount(1, $body['data']);
        $this->assertEquals('ORD-001', $body['data'][0]['order_number']);
        $this->assertEquals(1, $body['pagination']['total']);
        $this->assertEquals(1200000.0, (float) $body['totals']['total_amount']);
    }

    /** @test */
    public function it_paginates_orders()
    {
        foreach (range(1, 3) as $i) {
            $this->createOrder('ORD-00' . $i, 'completed', 1, 100000 * $i, 0);
        }

        $response = $this->withHeaders($this->authHeaders())->get('/api/orders?page=2&limit=2');
        $response->assertStatus(200);
        $body = $this->getJsonFromResponse($response);

        $this->assertEquals(3, $body['pagination']['total']);
        $this->assertEquals(2, $body['pagination']['total_pages']);
        $this->assertCount(1, $body['data']);
    }

    /** @test */
    public function it_returns_order_detail_with_items()
    {
        $orderId = $this->createOrder('ORD-010', 'confirmed', 1, 900000, 300000, [
            ['product_id' => 10, 'variant_id' => null, 'quantity' => 2, 'base_price' => 150000, 'final_price' => 140000],
            ['product_id' => 11, 'variant_id' => 3, 'quantity' => 1, 'base_price' => 200000, 'final_price' => 200000],
        ]);

        $response = $this->withHeaders($this->authHeaders())->get('/api/orders/' . $orderId);
        $response->assertStatus(200);
        $body = $this->getJsonFromResponse($response);

        $this->assertTrue($body['success']);
        $this->assertEquals('ORD-010', $body['data']['order_number']);
        $this->assertCount(2, $body['data']['items']);
    }

    private function seedBaseData(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('branches')->insertBatch([
            ['id' => 1, 'name' => 'Lano - HN', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'Lano - HCM', 'created_at' => $now, 'updated_at' => $now],
        ]);
        $this->db->table('customers')->insertBatch([
            ['id' => 1, 'name' => 'Khách A', 'phone' => '0900000001', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'Khách B', 'phone' => '0900000002', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    private function truncateTables(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        foreach (['order_items','orders','branches','customers'] as $tbl) {
            if ($this->db->tableExists($tbl)) {
                $this->db->table($tbl)->truncate();
            }
        }
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }

    private function createOrder(string $code, string $status, int $branchId, float $total, float $paid, array $items = []): int
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('orders')->insert([
            'order_number' => $code,
            'customer_id' => 1,
            'branch_id' => $branchId,
            'order_date' => date('Y-m-d'),
            'status' => $status,
            'order_type' => 'shipping',
            'payment_method' => 'CASH',
            'subtotal' => $total,
            'discount_total' => 0,
            'shipping_fee' => 0,
            'total' => $total,
            'paid_amount' => $paid,
            'debt_amount' => $total - $paid,
            'is_paid' => $paid >= $total ? 1 : 0,
            'shipping_name' => 'Test',
            'shipping_phone' => '0909',
            'shipping_address' => 'Addr',
            'shipping_city' => 'HN',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $orderId = (int) $this->db->insertID();

        if (empty($items)) {
            $items = [[
                'product_id' => 1,
                'variant_id' => null,
                'quantity' => 1,
                'base_price' => $total,
                'final_price' => $total,
                'price_list_name' => 'Base',
            ]];
        }
        $itemRows = [];
        foreach ($items as $item) {
            $itemRows[] = $item + [
                'order_id' => $orderId,
                'price_list_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        $this->db->table('order_items')->insertBatch($itemRows);
        return $orderId;
    }

    private function getJsonFromResponse($response): array
    {
        $raw = $response->getBody();
        $jsonString = $raw;
        if (strpos($raw, '<!DOCTYPE html') !== false && preg_match('/<p>(.*?)<\/p>/s', $raw, $m)) {
            $jsonString = html_entity_decode($m[1]);
        }
        return json_decode($jsonString, true);
    }
}
