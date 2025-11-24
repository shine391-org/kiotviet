<?php

namespace Tests\Services;

use App\Repositories\Returns\ReturnRepository;
use App\Services\Returns\ReturnService;
use App\Validators\ReturnValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use InvalidArgumentException;
use Tests\Support\Database\ReturnSchemaTrait;

/** @agent-test: ReturnService @agent-pattern: Standard service test */
class ReturnServiceTest extends CIUnitTestCase
{
    use ReturnSchemaTrait;

    protected $db;
    private ReturnService $service;

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
        } else {
            $config->tests = [
                'DSN'       => '',
                'hostname'  => '127.0.0.1',
                'port'      => 3307,
                'username'  => 'lanocrm_user',
                'password'  => 'KP7n4RjcDbedSE2W8GgA',
                'database'  => 'lanocrm_test',
                'DBDriver'  => 'MySQLi',
                'DBPrefix'  => 'db_',
                'pConnect'  => false,
                'DBDebug'   => true,
                'charset'   => 'utf8mb4',
                'DBCollat'  => 'utf8mb4_general_ci',
            ];
        }
        $config->defaultGroup = 'tests';

        $this->db = Database::connect('tests', false);
        $this->resetReturnSchema();
        $this->seedBase();

        $repo = new ReturnRepository(null, null, $this->db);
        $this->service = new ReturnService($repo, new ReturnValidator());
    }

    /** @test */
    public function it_creates_return_and_calculates_amounts()
    {
        $orderId = $this->seedOrderWithItems(1, [
            ['quantity' => 2, 'price' => 50000],
            ['quantity' => 1, 'price' => 100000],
        ]);

        $res = $this->service->create([
            'order_id' => $orderId,
            'customer_id' => 1,
            'items' => [
                ['order_item_id' => 1, 'quantity_returned' => 1, 'condition' => 'new'],
                ['order_item_id' => 2, 'quantity_returned' => 1, 'condition' => 'used'],
            ],
            'reason' => 'defective',
            'refund_shipping_fee' => false,
            'created_by' => 1,
        ]);

        $this->assertTrue($res['success']);
        $this->assertEquals(150000.0, $res['data']['return_amount']);
        $this->assertEquals('TH-' . $orderId . '-1', $res['data']['return_number']);
    }

    /** @test */
    public function it_prevents_over_returning_quantity()
    {
        $orderId = $this->seedOrderWithItems(1, [
            ['quantity' => 1, 'price' => 50000],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->service->create([
            'order_id' => $orderId,
            'customer_id' => 1,
            'items' => [
                ['order_item_id' => 1, 'quantity_returned' => 2, 'condition' => 'new'],
            ],
            'reason' => 'defective',
            'created_by' => 1,
        ]);
    }

    /** @test */
    public function it_blocks_uncompleted_orders()
    {
        $orderId = $this->seedOrderWithItems(1, [
            ['quantity' => 1, 'price' => 50000],
        ], status: 'confirmed');

        $this->expectException(InvalidArgumentException::class);
        $this->service->create([
            'order_id' => $orderId,
            'customer_id' => 1,
            'items' => [
                ['order_item_id' => 1, 'quantity_returned' => 1, 'condition' => 'new'],
            ],
            'reason' => 'defective',
            'created_by' => 1,
        ]);
    }

    private function seedBase(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('customers')->insert(['id' => 1, 'name' => 'ACME', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('users')->insert(['id' => 1, 'username' => 'tester', 'created_at' => $now, 'updated_at' => $now]);
    }

    private function seedOrderWithItems(int $customerId, array $items, string $status = 'completed'): int
    {
        $now = date('Y-m-d H:i:s');
        $total = array_sum(array_map(fn ($i) => $i['quantity'] * $i['price'], $items));
        $this->db->table('orders')->insert([
            'customer_id' => $customerId,
            'status' => $status,
            'total' => $total,
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
}
