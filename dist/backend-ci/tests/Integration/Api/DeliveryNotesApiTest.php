<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: Delivery notes API
 * @agent-pattern: Integration test with DevDatabaseTrait + FeatureTestTrait
 */
class DeliveryNotesApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use AuthTestTrait;
    use DevDatabaseTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->seedBaseData();
        $this->setUpAuthToken();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_from_order_and_delivers()
    {
        $create = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode([
                'order_id' => 1,
                'branch_id' => 1,
            ]))
            ->post('/api/delivery-notes/from-order');
        $createBody = $this->decodeResponse($create);
        $this->assertTrue($createBody['success'] ?? false, json_encode($createBody));
        $noteId = $createBody['data']['id'];
        $itemId = $createBody['data']['items'][0]['id'];

        $confirm = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->post('/api/delivery-notes/' . $noteId . '/confirm');
        $this->assertTrue(($this->decodeResponse($confirm)['success'] ?? false));

        $deliver = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode([
                'items' => [
                    ['delivery_note_item_id' => $itemId, 'quantity' => 2],
                ],
            ]))
            ->post('/api/delivery-notes/' . $noteId . '/deliver');
        $deliverBody = $this->decodeResponse($deliver);
        $this->assertTrue($deliverBody['success'] ?? false, json_encode($deliverBody));
        $this->assertEquals('delivered', $deliverBody['data']['status']);

        $stockQuery = $this->db->table('stock_bins')->where('product_id', 1)->where('branch_id', 1)->get();
        $stock = $stockQuery ? $stockQuery->getRowArray() : null;
        $this->assertNotNull($stock, 'Stock bin should exist');
        $this->assertEquals(3.0, (float) $stock['on_hand_qty']);
    }

    /** @test */
    public function it_blocks_over_delivery_via_api()
    {
        $create = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode([
                'order_id' => 1,
                'branch_id' => 1,
            ]))
            ->post('/api/delivery-notes/from-order');
        $body = $this->decodeResponse($create);
        $noteId = $body['data']['id'];
        $itemId = $body['data']['items'][0]['id'];

        $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->post('/api/delivery-notes/' . $noteId . '/confirm');

        $resp = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode([
                'items' => [
                    ['delivery_note_item_id' => $itemId, 'quantity' => 5],
                ],
            ]))
            ->post('/api/delivery-notes/' . $noteId . '/deliver');
        $resp->assertStatus(400);
    }

    private function seedBaseData(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('branches')->insert(['id' => 1, 'name' => 'HN', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('customers')->insert(['id' => 1, 'name' => 'Cust', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('products')->insert([
            'id' => 1,
            'code' => 'P1',
            'name' => 'Product 1',
            'selling_price' => 10000,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->db->table('orders')->insert([
            'id' => 1,
            'order_number' => 'ORD-100',
            'customer_id' => 1,
            'branch_id' => 1,
            'status' => 'confirmed',
            'order_type' => 'shipping',
            'payment_method' => 'CASH',
            'subtotal' => 20000,
            'total' => 20000,
            'paid_amount' => 0,
            'debt_amount' => 20000,
            'order_date' => date('Y-m-d'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->db->table('order_items')->insert([
            'id' => 1,
            'order_id' => 1,
            'product_id' => 1,
            'variant_id' => null,
            'quantity' => 2,
            'base_price' => 10000,
            'final_price' => 10000,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->db->table('stock_bins')->insert([
            'product_id' => 1,
            'variant_id' => null,
            'branch_id' => 1,
            'batch_id' => null,
            'on_hand_qty' => 5,
            'reserved_qty' => 0,
            'updated_at' => $now,
        ]);
    }

    private function decodeResponse($response): array
    {
        $raw = $response->getBody();
        if (is_string($raw) && strpos($raw, '<!DOCTYPE html') !== false && preg_match('/<p>(.*?)<\/p>/s', $raw, $m)) {
            $raw = html_entity_decode($m[1]);
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
