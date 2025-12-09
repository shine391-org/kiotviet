<?php

namespace Tests\Validators;

use App\Validators\ProductBatchValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

/**
 * @agent-test: ProductBatchValidator
 * @agent-pattern: Validator unit coverage
 */
class ProductBatchValidatorTest extends CIUnitTestCase
{
    private ProductBatchValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ProductBatchValidator();
    }

    public function testValidateCreateRequiresProductId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate([
            'branch_id' => 1,
            'batch_number' => 'BATCH-001',
        ]);
    }

    public function testValidateCreateRequiresBranchId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate([
            'product_id' => 1,
            'batch_number' => 'BATCH-001',
        ]);
    }

    public function testValidateCreateRequiresBatchNumber(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate([
            'product_id' => 1,
            'branch_id' => 1,
        ]);
    }

    public function testValidateCreateAcceptsValidData(): void
    {
        $data = [
            'product_id' => 1,
            'branch_id' => 1,
            'batch_number' => 'BATCH-001',
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertSame(1, $result['product_id']);
        $this->assertSame(1, $result['branch_id']);
        $this->assertSame('BATCH-001', $result['batch_number']);
        $this->assertSame('active', $result['status']);
    }

    public function testValidateCreateSetsDefaultQuantity(): void
    {
        $data = [
            'product_id' => 1,
            'branch_id' => 1,
            'batch_number' => 'BATCH-001',
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertSame(0.0, $result['initial_quantity']);
        $this->assertSame(0.0, $result['current_quantity']);
    }

    public function testValidateCreateSetsCurrentQuantityFromInitial(): void
    {
        $data = [
            'product_id' => 1,
            'branch_id' => 1,
            'batch_number' => 'BATCH-001',
            'initial_quantity' => 100,
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertSame(100.0, $result['initial_quantity']);
        $this->assertSame(100.0, $result['current_quantity']);
    }

    public function testValidateCreateWithDates(): void
    {
        $data = [
            'product_id' => 1,
            'branch_id' => 1,
            'batch_number' => 'BATCH-001',
            'manufacture_date' => '2024-01-01',
            'expiry_date' => '2024-12-31',
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertSame('2024-01-01', $result['manufacture_date']);
        $this->assertSame('2024-12-31', $result['expiry_date']);
    }

    public function testValidateCreateThrowsWhenExpiryBeforeManufacture(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate([
            'product_id' => 1,
            'branch_id' => 1,
            'batch_number' => 'BATCH-001',
            'manufacture_date' => '2024-12-31',
            'expiry_date' => '2024-01-01',
        ]);
    }

    public function testValidateUpdateAcceptsPartialData(): void
    {
        $data = ['status' => 'inactive'];

        $result = $this->validator->validateUpdate($data);

        $this->assertSame('inactive', $result['status']);
    }

    public function testValidateUpdateThrowsOnEmptyData(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateUpdate([]);
    }

    public function testValidateAdjustRequiresQuantityDelta(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateAdjust(['reason' => 'Test']);
    }

    public function testValidateAdjustThrowsOnZeroDelta(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateAdjust(['quantity_delta' => 0]);
    }

    public function testValidateAdjustAcceptsPositiveDelta(): void
    {
        $result = $this->validator->validateAdjust(['quantity_delta' => 10]);

        $this->assertSame(10.0, $result['quantity_delta']);
    }

    public function testValidateAdjustAcceptsNegativeDelta(): void
    {
        $result = $this->validator->validateAdjust(['quantity_delta' => -5]);

        $this->assertSame(-5.0, $result['quantity_delta']);
    }

    public function testValidateListFiltersAcceptsEmptyFilters(): void
    {
        $result = $this->validator->validateListFilters([]);

        $this->assertIsArray($result);
    }

    public function testValidateListFiltersWithProductId(): void
    {
        $result = $this->validator->validateListFilters(['product_id' => 1]);

        $this->assertSame(1, $result['product_id']);
    }

    public function testValidateListFiltersWithExpiringInDays(): void
    {
        $result = $this->validator->validateListFilters(['expiring_in_days' => 30]);

        $this->assertSame(30, $result['expiring_in_days']);
    }

    public function testValidateListFiltersWithSearch(): void
    {
        $result = $this->validator->validateListFilters(['search' => 'BATCH']);

        $this->assertSame('BATCH', $result['search']);
    }
}
