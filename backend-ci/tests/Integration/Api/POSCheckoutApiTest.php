<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\POSSchemaTrait;

/**
 * @agent-test: POS checkout API integration
 * @agent-pattern: FeatureTestTrait + DevDatabaseTrait (main DB)
 */
class POSCheckoutApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DevDatabaseTrait;
    use POSSchemaTrait;
    use AuthTestTrait;

    private int $profileId;
    private int $priceListId;
    private int $productId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();

        $this->setUpDatabase();
        $this->resetPOSSchema();
        $this->db->table('orders')->truncate();
        $this->db->table('order_items')->truncate();
        $this->db->table('order_payments')->truncate();
        $this->db->table('pos_shifts')->truncate();
        $this->db->table('pos_shift_logs')->truncate();
        $this->db->table('pos_shift_payments')->truncate();
        $this->db->table('products')->truncate();
        $this->db->table('price_lists')->truncate();
        $this->db->table('price_list_items')->truncate();
        $this->db->table('branches')->truncate();
        $this->db->table('users')->truncate();
        $this->db->table('pos_profiles')->truncate();
        $this->db->table('pos_payment_methods')->truncate();
        $this->db->table('cash_transactions')->truncate();
        $this->seedBaseData();
        $this->setUpAuthToken();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_processes_pos_order_with_split_payments_and_closes_shift()
    {
        $shiftResponse = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'user_id' => 1,
                'branch_id' => 1,
                'profile_id' => $this->profileId,
                'opening_balance' => 100000,
            ]))
            ->post('/api/pos/shifts/open');
        $shiftResponse->assertStatus(201);
        $shiftBody = $this->getJsonFromResponse($shiftResponse);
        $shiftId = $shiftBody['data']['id'];
        $this->assertEquals($shiftId, (new \App\Services\POS\POSShiftService())->requireOpenShift(1, 1)['id']);

        $orderPayload = [
            'branch_id' => 1,
            'order_type' => 'pos',
            'user_id' => 1,
            'customer_id' => null,
            'payment_method' => 'CASH',
            'pos_profile_id' => $this->profileId,
            'shipping_name' => 'Walk-in',
            'shipping_phone' => '0909',
            'shipping_address' => 'POS',
            'shipping_city' => 'HN',
            'shipping_district' => 'Hoan Kiem',
            'shipping_ward' => 'Hang Trong',
            'items' => [
                ['product_id' => $this->productId, 'quantity' => 1],
            ],
            'payments' => [
                ['payment_method' => 'CASH', 'amount' => 50],
                ['payment_method' => 'CARD', 'amount' => 30],
            ],
        ];

        $orderResponse = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode($orderPayload))
            ->post('/api/orders');
        $orderResponse->assertStatus(201);
        $body = $this->getJsonFromResponse($orderResponse);

        $this->assertTrue($body['success']);
        $orderData = $body['data'];
        $this->assertEquals($this->profileId, (int) $orderData['pos_profile_id']);
        $this->assertEquals($shiftId, (int) $orderData['pos_shift_id']);
        $this->assertEquals($this->priceListId, (int) $orderData['applied_price_list_id']);
        $this->assertEquals(80.0, (float) $orderData['total']);
        $orderRow = $this->db->table('orders')->where('id', $orderData['id'])->get()->getRowArray();
        $this->assertEquals($shiftId, (int) ($orderRow['pos_shift_id'] ?? 0));

        $shiftRow = $this->db->table('pos_shifts')->where('id', $shiftId)->get()->getRowArray();
        $this->assertEquals(80.0, (float) $shiftRow['expected_total']);
        $this->assertEquals(50.0, (float) $shiftRow['expected_cash']);

        $closeResponse = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'shift_id' => $shiftId,
                'actual_payments' => [
                    'CASH' => 45,
                    'CARD' => 30,
                ],
                'closing_note' => 'End of day',
            ]))
            ->post('/api/pos/shifts/close');
        $closeResponse->assertStatus(200);
        $closeBody = $this->getJsonFromResponse($closeResponse);
        $this->assertTrue($closeBody['success']);

        $closedShift = $this->db->table('pos_shifts')->where('id', $shiftId)->get()->getRowArray();
        $this->assertEquals('closed', $closedShift['status']);
        $this->assertEquals(-5.0, round((float) $closedShift['discrepancy'], 2));
    }

    private function seedBaseData(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('branches')->insert(['id' => 1, 'name' => 'Main', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('users')->insert(['id' => 1, 'username' => 'cashier', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('payment_methods')->insertBatch([
            ['code' => 'CASH', 'name' => 'Cash', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'CARD', 'name' => 'Card', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->priceListId = 1;
        $this->db->table('price_lists')->insert([
            'id' => $this->priceListId,
            'name' => 'POS',
            'type' => 'custom',
            'priority' => 1,
            'is_active' => 1,
            'start_date' => date('Y-m-d', strtotime('-1 day')),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->productId = 1;
        $this->db->table('products')->insert([
            'id' => $this->productId,
            'code' => 'P001',
            'product_type' => 'standard',
            'name' => 'POS Item',
            'selling_price' => 100,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->db->table('price_list_items')->insert([
            'price_list_id' => $this->priceListId,
            'product_id' => $this->productId,
            'price' => 80,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->db->table('pos_profiles')->insert([
            'id' => 1,
            'name' => 'Default POS',
            'user_id' => 1,
            'branch_id' => 1,
            'price_list_id' => $this->priceListId,
            'require_shift' => 1,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->db->table('pos_payment_methods')->insertBatch([
            ['profile_id' => 1, 'payment_method' => 'CASH', 'is_allowed' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['profile_id' => 1, 'payment_method' => 'CARD', 'is_allowed' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
        $this->profileId = 1;
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

    private function jsonHeaders(): array
    {
        return $this->authHeaders(['Content-Type' => 'application/json']);
    }
}
