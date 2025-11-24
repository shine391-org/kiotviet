<?php

namespace Tests\Integration\MultiModule;

use App\Services\Orders\OrderService;
use App\Services\Webhooks\WebhookDispatcher;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Tests\Support\Database\StatusSchemaTrait;

/**
 * @agent-test: Event + webhook integration (order created)
 * @agent-pattern: Service + dispatcher with fake sender
 */
class EventWebhookIntegrationTest extends CIUnitTestCase
{
    use StatusSchemaTrait;

    protected $db;
    protected OrderService $orders;
    protected WebhookDispatcher $dispatcher;
    private int $productId;

    private function resetProducts(): void
    {
        $this->db->query('DROP TABLE IF EXISTS products');
        $this->db->query('DROP TABLE IF EXISTS db_products');
        $this->db->query('CREATE TABLE products (id INTEGER PRIMARY KEY AUTOINCREMENT, code TEXT, name TEXT, selling_price REAL, created_at TEXT, updated_at TEXT, deleted_at TEXT)');
        $this->db->query('CREATE TABLE db_products (id INTEGER PRIMARY KEY AUTOINCREMENT, code TEXT, name TEXT, selling_price REAL, created_at TEXT, updated_at TEXT, deleted_at TEXT)');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $config = config('Database');
        if (extension_loaded('sqlite3')) {
            $config->tests = [
                'DBDriver'    => 'SQLite3',
                'database'    => ':memory:',
                'DBPrefix'    => 'db_',
                'foreignKeys' => true,
                'DBDebug'     => true,
            ];
        }
        $config->defaultGroup = 'tests';
        $this->db = Database::connect('tests', false);
        if (strtolower($this->db->DBDriver) === 'sqlite3') {
            $this->markTestSkipped('Webhook integration requires MySQL schema.');
        }
        $this->resetStatusSchema();
        $this->resetProducts();

        // seed webhook subscription
        $this->db->query("CREATE TABLE IF NOT EXISTS webhook_subscriptions (id INTEGER PRIMARY KEY AUTOINCREMENT, event VARCHAR(100), target_url VARCHAR(255), secret VARCHAR(255), is_active INTEGER, created_at TEXT, updated_at TEXT)");
        $this->db->query("CREATE TABLE IF NOT EXISTS db_webhook_subscriptions (id INTEGER PRIMARY KEY AUTOINCREMENT, event VARCHAR(100), target_url VARCHAR(255), secret VARCHAR(255), is_active INTEGER, created_at TEXT, updated_at TEXT)");
        $this->db->query("CREATE TABLE IF NOT EXISTS webhook_events (id INTEGER PRIMARY KEY AUTOINCREMENT, event VARCHAR(100), payload TEXT, status VARCHAR(20), attempts INTEGER, last_error TEXT, created_at TEXT, updated_at TEXT)");
        $this->db->query("CREATE TABLE IF NOT EXISTS db_webhook_events (id INTEGER PRIMARY KEY AUTOINCREMENT, event VARCHAR(100), payload TEXT, status VARCHAR(20), attempts INTEGER, last_error TEXT, created_at TEXT, updated_at TEXT)");
        $this->db->table('webhook_subscriptions')->insert([
            'event' => 'order.created',
            'target_url' => 'https://hooks.test/order-created',
            'secret' => 'secret',
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->db->table('db_webhook_subscriptions')->insert([
            'event' => 'order.created',
            'target_url' => 'https://hooks.test/order-created',
            'secret' => 'secret',
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $now = date('Y-m-d H:i:s');
        $this->db->table('products')->insert([
            'code' => 'P' . random_int(100, 999),
            'name' => 'Prod',
            'selling_price' => 100000,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $productId = (int) $this->db->insertID();
        $this->db->table('db_products')->insert([
            'code' => 'P' . $productId,
            'name' => 'Prod',
            'selling_price' => 100000,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->productId = $productId;

        $this->orders = service('orderService');
        // custom dispatcher with fake sender to avoid HTTP
        $this->dispatcher = new WebhookDispatcher(null, null, function () {
            return ['success' => true, 'status_code' => 200];
        });
    }

    /** @test */
    public function order_creation_dispatches_webhook(): void
    {
        $this->db->table('inventory_stock')->insert([
            'branch_id' => 1,
            'product_id' => 11,
            'variant_id' => null,
            'quantity_on_hand' => 5,
            'quantity_reserved' => 0,
            'minimum_stock' => 0,
        ]);

        // inject dispatcher into order service
        $orderService = new OrderService(
            null,
            null,
            service('priceCalculatorService'),
            null,
            null,
            $this->dispatcher
        );

        $res = $orderService->create([
            'customer_id' => 1,
            'branch_id' => 1,
            'order_type' => 'pos',
            'payment_method' => 'CASH',
            'items' => [
                ['product_id' => $this->productId, 'quantity' => 1],
            ],
            'paid_amount' => 100000,
            'shipping' => [
                'name' => 'John',
                'phone' => '0909',
                'address' => 'Addr',
                'ward' => 'W',
                'district' => 'D',
                'city' => 'C',
            ],
        ]);

        $this->assertTrue($res['success']);
        $events = $this->db->table('webhook_events')->get()->getResultArray();
        $this->assertNotEmpty($events, 'Webhook event not queued');
        $this->assertEquals('order.created', $events[0]['event']);
    }
}
