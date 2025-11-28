<?php

namespace Tests\Services;

use App\Services\Orders\OrderPaymentService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;

class OrderPaymentServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private OrderPaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Đảm bảo tất cả kết nối mới dùng DB group "tests"
        $config = config('Database');
        $config->defaultGroup = 'tests';
        \Config\Services::reset(true);

        $this->setUpDatabase();
        // Defensive: ensure schema exists for shared full-suite runs
        $this->ensureOrderPaymentTable();
        // Đảm bảo bảng order_payments tồn tại (tránh lỗi khi migrate bị skip)
        $this->db->query("CREATE TABLE IF NOT EXISTS order_payments (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id BIGINT UNSIGNED NOT NULL,
            payment_method VARCHAR(20),
            amount DECIMAL(15,2) NOT NULL DEFAULT 0,
            paid_at DATETIME NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // Pass same db to cash service through constructor for consistent connection
        $this->service = new OrderPaymentService(null, null, null, null, $this->db);
        $this->seedOrder();
    }

    private function ensureOrderPaymentTable(): void
    {
        $tables = $this->db->listTables();
        if (! in_array('orders', $tables, true) || ! in_array('order_payments', $tables, true)) {
            // chạy golden schema nếu bảng mất
            require_once APPPATH . 'Database/Migrations/2025-11-27-000999_TestSchemaSetup.php';
            (new \App\Database\Migrations\TestSchemaSetup())->up();
        }
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_cash_payment_and_receipt()
    {
        $res = $this->service->addPayment([
            'order_id' => 1,
            'payment_method' => 'CASH',
            'amount' => 50000,
            'created_by' => 1,
        ]);

        $this->assertTrue($res['success']);
        $this->assertEquals(1, $this->db->table('order_payments')->where('id', $res['data']['id'])->countAllResults());

        $tx = $this->db->table('cash_transactions')
            ->where('reference_type', 'order_payment')
            ->where('reference_id', $res['data']['id'])
            ->get()->getRowArray();
        $this->assertNotNull($tx);
        $this->assertEquals(50000.00, (float) $tx['amount']);
    }

    /** @test */
    public function it_does_not_create_receipt_for_bank_payment()
    {
        $res = $this->service->addPayment([
            'order_id' => 1,
            'payment_method' => 'BANK_TRANSFER',
            'amount' => 20000,
            'created_by' => 1,
        ]);
        $this->assertTrue($res['success']);
        $this->assertEquals(0, $this->db->table('cash_transactions')->where('reference_type', 'order_payment')->where('reference_id', $res['data']['id'])->countAllResults());
    }

    /** @test */
    public function it_blocks_overpaid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->addPayment([
            'order_id' => 1,
            'payment_method' => 'CASH',
            'amount' => 200000,
            'created_by' => 1,
        ]);
    }

    private function seedOrder(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('branches')->insert(['id' => 1, 'name' => 'Main', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('users')->insert(['id' => 1, 'username' => 'tester', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('orders')->insert([
            'id' => 1,
            'order_number' => 'ORD-1',
            'customer_id' => 1,
            'branch_id' => 1,
            'status' => 'confirmed',
            'order_type' => 'pos',
            'total' => 100000,
            'paid_amount' => 0,
            'debt_amount' => 100000,
            'payment_status' => 'unpaid',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
