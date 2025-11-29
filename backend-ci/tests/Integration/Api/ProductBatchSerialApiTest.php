<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\ProductBatchSerialSchemaTrait;

/**
 * @agent-test: Product batch & serial API integration
 * @agent-pattern: Integration test with DevDatabaseTrait + FeatureTestTrait
 */
class ProductBatchSerialApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DevDatabaseTrait;
    use AuthTestTrait;
    use ProductBatchSerialSchemaTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetProductBatchSerialSchema();
        $this->seedBaseData();
        $this->setUpAuthToken();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_and_adjusts_batch_via_api()
    {
        $create = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode([
                'product_id' => 1,
                'branch_id' => 1,
                'warehouse_id' => 1,
                'batch_number' => 'API-001',
                'initial_quantity' => 4,
            ]))
            ->post('/api/product-batches');
        $created = $this->decodeResponse($create);
        $this->assertTrue($created['success'] ?? false, json_encode($created));
        $batchId = $created['data']['id'];

        $adjust = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode([
                'quantity_delta' => -1,
                'branch_id' => 1,
                'warehouse_id' => 1,
                'reason' => 'API adjust',
            ]))
            ->post('/api/product-batches/' . $batchId . '/adjust-quantity');
        $adjust->assertStatus(200);
        $data = $this->decodeResponse($adjust);
        $this->assertTrue($data['success'] ?? false, json_encode($data));
        $this->assertEquals(3.0, (float) $data['data']['current_quantity']);

        $stock = $this->db->table('inventory_stock')->where('product_id', 1)->get()->getRowArray();
        $this->assertEquals(3.0, (float) $stock['quantity_on_hand']);
    }

    /** @test */
    public function it_reserves_sells_and_returns_serials_via_api()
    {
        $serialRes = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode([
                'product_id' => 1,
                'serial_number' => 'API-SN-1',
            ]))
            ->post('/api/product-serials');
        $serialBody = $this->decodeResponse($serialRes);
        $this->assertTrue($serialBody['success'] ?? false, json_encode($serialBody));

        $reserve = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode([
                'serial_numbers' => ['API-SN-1'],
                'order_id' => 10,
            ]))
            ->post('/api/product-serials/reserve');
        $reserve->assertStatus(200);
        $this->assertTrue(($this->decodeResponse($reserve)['success'] ?? false));

        $sell = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode([
                'serial_numbers' => ['API-SN-1'],
                'order_id' => 10,
            ]))
            ->post('/api/product-serials/sell');
        $sell->assertStatus(200);
        $this->assertTrue(($this->decodeResponse($sell)['success'] ?? false));

        $return = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode([
                'serial_numbers' => ['API-SN-1'],
                'order_id' => 10,
            ]))
            ->post('/api/product-serials/return');
        $return->assertStatus(200);
        $this->assertTrue(($this->decodeResponse($return)['success'] ?? false));

        $row = $this->db->table('product_serial_numbers')->where('serial_number', 'API-SN-1')->get()->getRowArray();
        $this->assertEquals('returned', $row['status']);
    }

    /** @test */
    public function order_with_batch_and_serial_reduces_stock_and_updates_statuses()
    {
        $batchCreate = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode([
                'product_id' => 1,
                'branch_id' => 1,
                'warehouse_id' => 1,
                'batch_number' => 'ORD-B1',
                'initial_quantity' => 2,
            ]))
            ->post('/api/product-batches');
        $batchBody = $this->decodeResponse($batchCreate);
        $this->assertTrue($batchBody['success'] ?? false, json_encode($batchBody));
        $batchId = $batchBody['data']['id'];

        $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode([
                'product_id' => 1,
                'batch_id' => $batchId,
                'serial_number' => 'ORD-SN-1',
            ]))
            ->post('/api/product-serials')
            ->assertStatus(201);
        $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode([
                'product_id' => 1,
                'batch_id' => $batchId,
                'serial_number' => 'ORD-SN-2',
            ]))
            ->post('/api/product-serials')
            ->assertStatus(201);

        $orderRes = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode([
                'customer_id' => 1,
                'branch_id' => 1,
                'order_type' => 'shipping',
                'payment_method' => 'CASH',
                'shipping_fee' => 0,
                'paid_amount' => 0,
                'shipping_name' => 'Test',
                'shipping_phone' => '0909',
                'shipping_address' => 'Addr',
                'items' => [
                    [
                        'product_id' => 1,
                        'quantity' => 2,
                        'batch_id' => $batchId,
                        'serial_numbers' => ['ORD-SN-1', 'ORD-SN-2'],
                    ],
                ],
            ]))
            ->post('/api/orders');
        $orderData = $this->decodeResponse($orderRes);
        $this->assertTrue($orderData['success'] ?? false, json_encode($orderData));
        $orderId = $orderData['data']['id'];

        $confirm = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode(['status' => 'confirmed']))
            ->patch('/api/orders/' . $orderId . '/status');
        $confirmBody = $this->decodeResponse($confirm);
        $this->assertTrue($confirmBody['success'] ?? false, json_encode($confirmBody));

        $process = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode(['status' => 'processing']))
            ->patch('/api/orders/' . $orderId . '/status');
        $processBody = $this->decodeResponse($process);
        $this->assertTrue($processBody['success'] ?? false, json_encode($processBody));

        $shipping = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode(['status' => 'shipping']))
            ->patch('/api/orders/' . $orderId . '/status');
        $shippingBody = $this->decodeResponse($shipping);
        $this->assertTrue($shippingBody['success'] ?? false, json_encode($shippingBody));

        $delivered = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode(['status' => 'delivered']))
            ->patch('/api/orders/' . $orderId . '/status');
        $deliveredBody = $this->decodeResponse($delivered);
        $this->assertTrue($deliveredBody['success'] ?? false, json_encode($deliveredBody));

        $batchRow = $this->db->table('product_batches')->where('id', $batchId)->get()->getRowArray();
        $this->assertEquals(0.0, (float) $batchRow['current_quantity']);

        $serialRow = $this->db->table('product_serial_numbers')->where('serial_number', 'ORD-SN-1')->get()->getRowArray();
        $this->assertEquals('reserved', $serialRow['status']);

        $complete = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode(['status' => 'completed']))
            ->patch('/api/orders/' . $orderId . '/status');
        $completeBody = $this->decodeResponse($complete);
        $this->assertTrue($completeBody['success'] ?? false, json_encode($completeBody));

        $serialRow = $this->db->table('product_serial_numbers')->where('serial_number', 'ORD-SN-1')->get()->getRowArray();
        $this->assertEquals('sold', $serialRow['status']);
    }

    private function seedBaseData(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('products')->insert([
            'id' => 1,
            'code' => 'API-P1',
            'name' => 'API Product',
            'selling_price' => 100000,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->db->table('branches')->insert([
            'id' => 1,
            'name' => 'HN',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->db->table('customers')->insert([
            'id' => 1,
            'name' => 'Customer A',
            'created_at' => $now,
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
