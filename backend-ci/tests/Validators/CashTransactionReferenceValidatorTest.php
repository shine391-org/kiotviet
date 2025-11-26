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
        $this->validator = new CashTransactionReferenceValidator($this->db);
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
        $now = date('Y-m-d H:i:s');
        $this->db->table('returns')->insert([
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
        return (int) $this->db->insertID();
    }
}
