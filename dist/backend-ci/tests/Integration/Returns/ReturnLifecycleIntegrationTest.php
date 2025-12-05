<?php

namespace Tests\Integration\Returns;

use App\Services\Returns\ReturnService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Tests\Support\Database\ReturnSchemaTrait;

/**
 * @agent-test: Return lifecycle integration
 * @agent-pattern: End-to-end create -> approve -> complete
 */
class ReturnLifecycleIntegrationTest extends CIUnitTestCase
{
    use ReturnSchemaTrait;

    protected $db;
    protected ReturnService $returns;

    protected function setUp(): void
    {
        parent::setUp();
        $config = config('Database');
        $config->defaultGroup = 'tests';
        $this->db = Database::connect('tests', false);
        $this->resetReturnSchema();

        // seed base lookup
        $now = date('Y-m-d H:i:s');
        $this->db->table('customers')->insert(['id' => 1, 'name' => 'ACME', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('users')->insert(['id' => 1, 'username' => 'admin', 'created_at' => $now, 'updated_at' => $now]);

        $this->returns = service('returnService');
    }

    /** @test */
    public function full_return_lifecycle(): void
    {
        // order + items
        $orderId = $this->seedOrderWithItems(1, [
            ['quantity' => 10, 'price' => 100000],
        ], status: 'completed');
        $item = $this->db->table('order_items')->where('order_id', $orderId)->get()->getRowArray();

        $res = $this->returns->create([
            'order_id' => $orderId,
            'customer_id' => 1,
            'items' => [
                ['order_item_id' => $item['id'], 'quantity_returned' => 3, 'condition' => 'damaged'],
            ],
            'reason' => 'defective',
            'refund_shipping_fee' => '1',
            'refund_method' => 'bank_transfer',
            'created_by' => 1,
        ]);
        $data = $res['data'];
        $this->assertEquals('pending', $data['status']);
        $this->assertEquals(300000.0, (float) $data['return_amount']);

        $approved = $this->returns->approve($data['id'], [
            'refund_shipping_fee' => '1',
            'refund_method' => 'bank_transfer',
            'user_id' => 1,
            'notes' => 'ok',
            'version' => $data['lock_version'] ?? 0,
        ])['data'];
        $this->assertEquals('approved', $approved['status']);
        $this->assertGreaterThan(300000, (float) $approved['refund_amount']);

        $latest = $this->db->table('returns')->where('id', $data['id'])->get()->getRowArray();

        $completed = $this->returns->complete($data['id'], [
            'user_id' => 1,
            'version' => $latest['lock_version'] ?? ($approved['lock_version'] ?? 1),
        ])['data'];
        $this->assertEquals('completed', $completed['status']);
        $this->assertNotNull($completed['completed_at']);
    }

    private function seedOrderWithItems(int $customerId, array $items, string $status = 'completed'): int
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('orders')->insert([
            'customer_id' => $customerId,
            'branch_id' => 1,
            'status' => $status,
            'total' => array_sum(array_map(fn ($i) => $i['quantity'] * $i['price'], $items)),
            'shipping_fee' => 50000,
            'completed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $orderId = (int) $this->db->insertID();
        $id = 1;
        foreach ($items as $item) {
            $this->db->table('order_items')->insert([
                'id' => $id++,
                'order_id' => $orderId,
                'product_id' => 1,
                'variant_id' => null,
                'quantity' => $item['quantity'],
                'base_price' => $item['price'],
                'final_price' => $item['price'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        return $orderId;
    }
}
