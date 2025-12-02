<?php

namespace Tests\Validators;

use App\Models\CashTransactionModel;
use App\Validators\CashTransactionReferenceValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: CashTransactionReferenceValidator return order coverage
 */
class CashTransactionReferenceValidatorTest extends CIUnitTestCase
{
    use DevDatabaseTrait;

    private CashTransactionReferenceValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        if (! $this->db->tableExists('returns')) {
            require_once APPPATH . 'Database/Migrations/2025-11-21-000000_TestSchemaSetup.php';
            (new \App\Database\Migrations\TestSchemaSetup())->up();
        }
        if (! $this->db->tableExists('returns')) {
            $this->markTestSkipped('returns table unavailable for reference validation tests');
        }
        $this->seedMasterData();
        $this->validator = new CashTransactionReferenceValidator($this->db);
    }

    private function seedMasterData(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('customers')->insert(['id' => 1, 'name' => 'Test Customer', 'customer_type' => 'individual', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('branches')->insert(['id' => 1, 'name' => 'Main', 'code' => 'B1', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('orders')->insert(['id' => 1, 'order_number' => 'ORD001', 'branch_id' => 1, 'customer_id' => 1, 'order_date' => date('Y-m-d'), 'total' => 0, 'created_at' => $now, 'updated_at' => $now]);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_validates_return_order_reference()
    {
        $returnId = $this->seedReturn(150000, 'approved');

        $result = $this->validator->validateReference(
            CashTransactionModel::REFERENCE_RETURN_ORDER,
            $returnId,
            150000
        );

        $this->assertTrue($result['valid']);
        $this->assertEquals($returnId, $result['return_order_id']);
    }

    /** @test */
    public function it_throws_when_return_not_approved()
    {
        $returnId = $this->seedReturn(120000, 'pending');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Return order is not approved');

        $this->validator->validateReference(
            CashTransactionModel::REFERENCE_RETURN_ORDER,
            $returnId,
            120000
        );
    }

    private function seedReturn(float $refundAmount, string $status): int
    {
        if (! $this->db->tableExists('returns')) {
            $this->db->query("CREATE TABLE returns (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                return_number VARCHAR(50),
                order_id INT NULL,
                customer_id INT NULL,
                return_amount DECIMAL(14,2) DEFAULT 0,
                refund_amount DECIMAL(14,2) DEFAULT 0,
                refund_method VARCHAR(50) NULL,
                status VARCHAR(50),
                created_at DATETIME NULL,
                updated_at DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        $now = date('Y-m-d H:i:s');
        $id = random_int(1000, 999999);
        $this->db->table('returns')->insert([
            'id' => $id,
            'return_number' => 'RT' . rand(1000, 9999),
            'order_id' => 1,
            'customer_id' => 1,
            'return_amount' => $refundAmount,
            'refund_amount' => $refundAmount,
            'refund_method' => 'cash',
            'status' => $status,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $query = $this->db->table('returns')->where('id', $id)->get();
        if ($query === false) {
            $this->markTestSkipped('returns table unavailable for reference validation tests');
        }
        $row = $query->getRowArray();
        $this->assertNotNull($row, 'Seeded return not found');
        return $id;
    }
}
