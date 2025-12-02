<?php

namespace Tests\Services;

use App\Repositories\Ecommerce\EcommerceWebhookLogRepository;
use App\Repositories\Orders\OrderRepository;
use App\Repositories\Products\ProductRepository;
use App\Services\Ecommerce\EcommerceIntegrationService;
use App\Services\Orders\OrderService;
use App\Validators\WebhookPayloadValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\EcommerceSchemaTrait;

/**
 * @agent-test: EcommerceIntegrationService
 * @agent-pattern: Service test with DevDatabaseTrait + idempotency log
 */
class EcommerceIntegrationServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use EcommerceSchemaTrait;

    private EcommerceIntegrationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetEcommerceSchema();
        $this->seedBranch();
        $this->seedProduct('P1', 'Prod 1', 10);

        $this->service = new EcommerceIntegrationService(
            new ProductRepository(null, null, null, $this->db),
            new OrderService(new OrderRepository(null, null, $this->db)),
            new EcommerceWebhookLogRepository(null, $this->db),
            new WebhookPayloadValidator()
        );
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    public function test_product_sync_creates_and_updates(): void
    {
        $payload = [
            'idempotency_key' => 'key-1',
            'event' => 'product.updated',
            'data' => [
                'code' => 'NEW',
                'name' => 'New Product',
                'price' => 25,
            ],
        ];

        $first = $this->service->handleProductWebhook($payload);
        $this->assertTrue($first['success']);
        $this->assertNotEmpty($first['data']['product_id']);

        $payload['data']['name'] = 'New Product Updated';
        $second = $this->service->handleProductWebhook($payload); // idempotent, same key
        $this->assertTrue($second['duplicate']);
        $row = $this->db->table('products')->where('code', 'NEW')->get()->getRowArray();
        $this->assertSame('New Product', $row['name']); // duplicate should not reprocess
    }

    public function test_order_sync_creates_order_and_items(): void
    {
        $payload = [
            'idempotency_key' => 'order-1',
            'event' => 'order.created',
            'data' => [
                'branch_id' => 1,
                'payment_method' => 'CASH',
                'items' => [
                    ['product_code' => 'P1', 'quantity' => 2, 'price' => 15],
                ],
            ],
        ];

        $result = $this->service->handleOrderWebhook($payload);
        $this->assertTrue($result['success']);

        $orders = $this->db->table('orders')->get()->getResultArray();
        $this->assertCount(1, $orders);
        $items = $this->db->table('order_items')->get()->getResultArray();
        $this->assertCount(1, $items);
        $this->assertEquals(2, (int) $items[0]['quantity']);
    }

    private function seedBranch(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('branches')->insert([
            'id' => 1,
            'name' => 'Main',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function seedProduct(string $code, string $name, float $price): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('products')->insert([
            'code' => $code,
            'name' => $name,
            'selling_price' => $price,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
