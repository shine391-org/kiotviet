<?php

namespace Tests\Validators;

use App\Validators\StockLedgerValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

/**
 * @agent-test: StockLedgerValidator
 * @agent-pattern: Validator unit coverage
 */
class StockLedgerValidatorTest extends CIUnitTestCase
{
    private StockLedgerValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new StockLedgerValidator();
    }

    public function testValidateRecordRequiresProductId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateRecord([
            'branch_id' => 1,
            'reference_type' => 'order',
            'reference_id' => 1,
            'qty_delta' => 10,
        ]);
    }

    public function testValidateRecordRequiresBranchId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateRecord([
            'product_id' => 1,
            'reference_type' => 'order',
            'reference_id' => 1,
            'qty_delta' => 10,
        ]);
    }

    public function testValidateRecordRequiresReferenceType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateRecord([
            'product_id' => 1,
            'branch_id' => 1,
            'reference_id' => 1,
            'qty_delta' => 10,
        ]);
    }

    public function testValidateRecordRequiresReferenceId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateRecord([
            'product_id' => 1,
            'branch_id' => 1,
            'reference_type' => 'order',
            'qty_delta' => 10,
        ]);
    }

    public function testValidateRecordRequiresQtyDelta(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateRecord([
            'product_id' => 1,
            'branch_id' => 1,
            'reference_type' => 'order',
            'reference_id' => 1,
        ]);
    }

    public function testValidateRecordAcceptsValidData(): void
    {
        $data = [
            'product_id' => 1,
            'branch_id' => 1,
            'reference_type' => 'order',
            'reference_id' => 1,
            'qty_delta' => 10,
        ];

        $result = $this->validator->validateRecord($data);

        $this->assertSame(1, $result['product_id']);
        $this->assertSame(1, $result['branch_id']);
        $this->assertSame('order', $result['reference_type']);
        $this->assertSame(1, $result['reference_id']);
        $this->assertSame(10, (int) $result['qty_delta']);
    }

    public function testValidateRecordSetsDefaultMovementDate(): void
    {
        $data = [
            'product_id' => 1,
            'branch_id' => 1,
            'reference_type' => 'order',
            'reference_id' => 1,
            'qty_delta' => 10,
        ];

        $result = $this->validator->validateRecord($data);

        $this->assertNotEmpty($result['movement_date']);
    }

    public function testValidateRecordSetsDefaultReferenceSeq(): void
    {
        $data = [
            'product_id' => 1,
            'branch_id' => 1,
            'reference_type' => 'order',
            'reference_id' => 1,
            'qty_delta' => 10,
        ];

        $result = $this->validator->validateRecord($data);

        $this->assertSame(1, $result['reference_seq']);
    }

    public function testValidateRecordCalculatesTotalCost(): void
    {
        $data = [
            'product_id' => 1,
            'branch_id' => 1,
            'reference_type' => 'order',
            'reference_id' => 1,
            'qty_delta' => 10,
            'unit_cost' => 1000,
        ];

        $result = $this->validator->validateRecord($data);

        $this->assertSame(10000.0, $result['total_cost']);
    }

    public function testValidateRecordWithExplicitTotalCost(): void
    {
        $data = [
            'product_id' => 1,
            'branch_id' => 1,
            'reference_type' => 'order',
            'reference_id' => 1,
            'qty_delta' => 10,
            'unit_cost' => 1000,
            'total_cost' => 9500, // Discounted
        ];

        $result = $this->validator->validateRecord($data);

        $this->assertSame(9500.0, $result['total_cost']);
    }

    public function testValidateRecordWithNegativeQtyDelta(): void
    {
        $data = [
            'product_id' => 1,
            'branch_id' => 1,
            'reference_type' => 'return',
            'reference_id' => 1,
            'qty_delta' => -5,
        ];

        $result = $this->validator->validateRecord($data);

        $this->assertSame(-5, (int) $result['qty_delta']);
    }

    public function testValidateRecordWithOptionalFields(): void
    {
        $data = [
            'product_id' => 1,
            'variant_id' => 2,
            'branch_id' => 1,
            'warehouse_id' => 3,
            'batch_id' => 4,
            'serial_number' => 'SN-001',
            'reference_type' => 'order',
            'reference_id' => 1,
            'qty_delta' => 10,
        ];

        $result = $this->validator->validateRecord($data);

        $this->assertSame(2, $result['variant_id']);
        $this->assertSame(3, $result['warehouse_id']);
        $this->assertSame(4, $result['batch_id']);
        $this->assertSame('SN-001', $result['serial_number']);
    }
}
