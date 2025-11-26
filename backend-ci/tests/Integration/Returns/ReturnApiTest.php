<?php

namespace Tests\Integration\Returns;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\ReturnSchemaTrait;

/**
 * @agent-test: Return API integration
 * @agent-pattern: Standard API integration test
 */
class ReturnApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use AuthTestTrait;
    use ReturnSchemaTrait;

    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        // Use local HTTP kernel without external server requirement.
        $this->db = Database::connect('tests');
        $this->resetReturnSchema();
        $this->seedLookup();
        $this->setUpAuthToken();
    }

    public function test_create_return_success(): void
    {
        $orderId = $this->seedOrderWithItems(1, [
            ['quantity' => 2, 'price' => 40000],
        ]);

        $payload = [
            'order_id' => $orderId,
            'customer_id' => 1,
            'items' => [
                ['order_item_id' => 1, 'quantity_returned' => 1, 'condition' => 'new'],
            ],
            'reason' => 'defective',
            'refund_shipping_fee' => false,
            'created_by' => 1,
        ];

        $res = $this->withHeaders($this->authHeaders(['Accept' => 'application/json']))
            ->withBody(json_encode($payload))
            ->post('api/returns');

        $res->assertStatus(201);
        $body = $this->decodeResponse($res);
        $this->assertTrue($body['success']);
        $this->assertEquals(40000.0, $body['data']['return_amount']);
        $this->assertEquals('TH-' . $orderId . '-1', $body['data']['return_number']);
    }

    public function test_approve_return_adds_shipping_fee_when_shop_fault(): void
    {
        $orderId = $this->seedOrderWithItems(1, [
            ['quantity' => 1, 'price' => 100000],
        ], shippingFee: 15000);
        $payload = [
            'order_id' => $orderId,
            'customer_id' => 1,
            'items' => [
                ['order_item_id' => 1, 'quantity_returned' => 1, 'item_condition' => 'new'],
            ],
            'reason' => 'defective',
            'created_by' => 1,
        ];
        $this->withHeaders($this->authHeaders(['Accept' => 'application/json']))
            ->withBody(json_encode($payload))
            ->post('api/returns');

        $approvePayload = [
            'user_id' => 99,
            'refund_method' => 'cash',
        ];
        $res = $this->withHeaders($this->authHeaders(['Accept' => 'application/json']))
            ->withBody(json_encode($approvePayload))
            ->patch('api/returns/1/approve');

        $res->assertStatus(200);
        $body = $this->decodeResponse($res);
        $this->assertEquals(115000.0, $body['data']['refund_amount']);
        $this->assertTrue($body['data']['refund_shipping_fee']);
    }

    public function test_cannot_over_return(): void
    {
        $orderId = $this->seedOrderWithItems(1, [
            ['quantity' => 1, 'price' => 50000],
        ]);

        $payload = [
            'order_id' => $orderId,
            'customer_id' => 1,
            'items' => [
                ['order_item_id' => 1, 'quantity_returned' => 2, 'condition' => 'new'],
            ],
            'reason' => 'defective',
            'created_by' => 1,
        ];

        $res = $this->withHeaders($this->authHeaders(['Accept' => 'application/json']))
            ->withBody(json_encode($payload))
            ->post('api/returns');

        $res->assertStatus(400);
        $body = $this->decodeResponse($res);
        $message = $body['message'] ?? ($body['messages']['error'] ?? '');
        $this->assertStringContainsString('Cannot return more than', $message);
    }

    private function decodeResponse($res): array
    {
        $body = trim(strip_tags((string) $res->getBody()));
        $json = json_decode($body, true);
        return is_array($json) ? $json : ['__raw' => $body];
    }

    private function seedOrderWithItems(int $customerId, array $items, float $shippingFee = 0.0): int
    {
        $now = date('Y-m-d H:i:s');
        $total = array_sum(array_map(fn ($i) => $i['quantity'] * $i['price'], $items));
        $this->db->table('orders')->insert([
            'customer_id' => $customerId,
            'branch_id' => 1,
            'status' => 'completed',
            'total' => $total,
            'shipping_fee' => $shippingFee,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $orderId = (int) $this->db->insertID();
        $id = 1;
        foreach ($items as $item) {
            $this->db->table('order_items')->insert([
                'id' => $id,
                'order_id' => $orderId,
                'product_id' => 1,
                'variant_id' => null,
                'quantity' => $item['quantity'],
                'base_price' => $item['price'],
                'final_price' => $item['price'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $id++;
        }
        return $orderId;
    }

    private function seedLookup(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('customers')->insert(['id' => 1, 'name' => 'ACME', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('users')->insert(['id' => 1, 'username' => 'tester', 'created_at' => $now, 'updated_at' => $now]);
    }
}
