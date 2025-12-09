<?php

namespace Tests\Validators;

use App\Validators\ProductSerialValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

/**
 * @agent-test: ProductSerialValidator
 * @agent-pattern: Validator unit coverage
 */
class ProductSerialValidatorTest extends CIUnitTestCase
{
    private ProductSerialValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ProductSerialValidator();
    }

    public function testValidateCreateRequiresProductId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate([
            'serial_number' => 'SN-001',
        ]);
    }

    public function testValidateCreateRequiresSerialNumber(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate([
            'product_id' => 1,
        ]);
    }

    public function testValidateCreateAcceptsValidData(): void
    {
        $data = [
            'product_id' => 1,
            'serial_number' => 'SN-001',
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertSame(1, $result['product_id']);
        $this->assertSame('SN-001', $result['serial_number']);
        $this->assertSame('available', $result['status']);
    }

    public function testValidateCreateWithStatus(): void
    {
        $data = [
            'product_id' => 1,
            'serial_number' => 'SN-001',
            'status' => 'reserved',
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertSame('reserved', $result['status']);
    }

    public function testValidateCreateWithWarrantyDate(): void
    {
        $data = [
            'product_id' => 1,
            'serial_number' => 'SN-001',
            'warranty_expiry_date' => '2025-12-31',
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertSame('2025-12-31', $result['warranty_expiry_date']);
    }

    public function testValidateFiltersAcceptsEmptyFilters(): void
    {
        $result = $this->validator->validateFilters([]);

        $this->assertIsArray($result);
    }

    public function testValidateFiltersWithProductId(): void
    {
        $result = $this->validator->validateFilters(['product_id' => 1]);

        $this->assertSame(1, $result['product_id']);
    }

    public function testValidateFiltersWithStatus(): void
    {
        $result = $this->validator->validateFilters(['status' => 'available']);

        $this->assertSame('available', $result['status']);
    }

    public function testValidateFiltersWithSearch(): void
    {
        $result = $this->validator->validateFilters(['search' => 'SN-001']);

        $this->assertSame('SN-001', $result['search']);
    }

    public function testValidateReserveRequiresOrderId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateReserve([
            'serial_numbers' => ['SN-001'],
        ]);
    }

    public function testValidateReserveRequiresSerialNumbers(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateReserve([
            'order_id' => 1,
        ]);
    }

    public function testValidateReserveAcceptsValidData(): void
    {
        $data = [
            'order_id' => 1,
            'serial_numbers' => ['SN-001', 'SN-002'],
        ];

        $result = $this->validator->validateReserve($data);

        $this->assertSame(1, $result['order_id']);
        $this->assertCount(2, $result['serial_numbers']);
    }

    public function testValidateReserveAcceptsSingleSerialNumber(): void
    {
        $data = [
            'order_id' => 1,
            'serial_number' => 'SN-001',
        ];

        $result = $this->validator->validateReserve($data);

        $this->assertCount(1, $result['serial_numbers']);
        $this->assertSame('SN-001', $result['serial_numbers'][0]);
    }

    public function testValidateSellRequiresOrderId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateSell([
            'serial_numbers' => ['SN-001'],
        ]);
    }

    public function testValidateSellRequiresSerialNumbers(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateSell([
            'order_id' => 1,
        ]);
    }

    public function testValidateSellAcceptsValidData(): void
    {
        $data = [
            'order_id' => 1,
            'serial_numbers' => ['SN-001'],
        ];

        $result = $this->validator->validateSell($data);

        $this->assertSame(1, $result['order_id']);
        $this->assertCount(1, $result['serial_numbers']);
    }

    public function testValidateReturnRequiresSerialNumbers(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateReturn([
            'order_id' => 1,
        ]);
    }

    public function testValidateReturnAcceptsValidData(): void
    {
        $data = [
            'serial_numbers' => ['SN-001'],
            'reason' => 'Defective',
        ];

        $result = $this->validator->validateReturn($data);

        $this->assertCount(1, $result['serial_numbers']);
        $this->assertSame('Defective', $result['reason']);
    }

    public function testValidateReturnWithOrderId(): void
    {
        $data = [
            'serial_numbers' => ['SN-001'],
            'order_id' => 1,
        ];

        $result = $this->validator->validateReturn($data);

        $this->assertSame(1, $result['order_id']);
    }

    public function testValidateReserveDeduplicatesSerialNumbers(): void
    {
        $data = [
            'order_id' => 1,
            'serial_numbers' => ['SN-001', 'SN-001', 'SN-002'],
        ];

        $result = $this->validator->validateReserve($data);

        $this->assertCount(2, $result['serial_numbers']);
    }

    public function testValidateReserveFiltersEmptySerials(): void
    {
        $data = [
            'order_id' => 1,
            'serial_numbers' => ['SN-001', '', '  ', 'SN-002'],
        ];

        $result = $this->validator->validateReserve($data);

        $this->assertCount(2, $result['serial_numbers']);
    }

    public function testValidateReserveHandlesNumericSerials(): void
    {
        $data = [
            'order_id' => 1,
            'serial_numbers' => [12345, '67890'],
        ];

        $result = $this->validator->validateReserve($data);

        $this->assertContains('12345', $result['serial_numbers']);
        $this->assertContains('67890', $result['serial_numbers']);
    }
}
