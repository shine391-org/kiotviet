<?php

namespace Tests\Validators;

use App\Validators\InvoiceValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

/**
 * @agent-test: InvoiceValidator
 * @agent-pattern: Validator unit coverage
 */
class InvoiceValidatorTest extends CIUnitTestCase
{
    private InvoiceValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new InvoiceValidator();
    }

    public function testValidateListFiltersDefaultValues(): void
    {
        $result = $this->validator->validateListFilters([]);

        $this->assertSame(1, $result['page']);
        $this->assertSame(20, $result['limit']);
    }

    public function testValidateListFiltersCastsValues(): void
    {
        $result = $this->validator->validateListFilters([
            'page' => '2',
            'limit' => '30',
        ]);

        $this->assertSame(2, $result['page']);
        $this->assertSame(30, $result['limit']);
    }

    public function testValidateListFiltersWithCustomerId(): void
    {
        $result = $this->validator->validateListFilters(['customer_id' => 5]);

        $this->assertSame(5, $result['customer_id']);
    }

    public function testValidateListFiltersWithBranchId(): void
    {
        $result = $this->validator->validateListFilters(['branch_id' => 3]);

        $this->assertSame(3, $result['branch_id']);
    }

    public function testValidateListFiltersWithDateRange(): void
    {
        $result = $this->validator->validateListFilters([
            'issue_date_from' => '2024-01-01',
            'issue_date_to' => '2024-12-31',
        ]);

        $this->assertSame('2024-01-01', $result['issue_date_from']);
        $this->assertSame('2024-12-31', $result['issue_date_to']);
    }

    public function testValidateListFiltersWithInvoiceStatus(): void
    {
        $result = $this->validator->validateListFilters(['invoice_status' => 'completed']);

        $this->assertSame(['completed'], $result['invoice_status']);
    }

    public function testValidateListFiltersWithMultipleStatuses(): void
    {
        $result = $this->validator->validateListFilters([
            'invoice_status' => ['draft', 'processing'],
        ]);

        $this->assertSame(['draft', 'processing'], $result['invoice_status']);
    }

    public function testValidateListFiltersThrowsOnInvalidStatus(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateListFilters(['invoice_status' => 'invalid']);
    }

    public function testValidateListFiltersWithInvoiceTypes(): void
    {
        $result = $this->validator->validateListFilters([
            'invoice_types' => ['standard', 'return'],
        ]);

        $this->assertSame(['standard', 'return'], $result['invoice_types']);
    }

    public function testValidateListFiltersThrowsOnInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateListFilters(['invoice_types' => ['invalid']]);
    }

    public function testValidateCreateRequiresCustomerId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate([
            'branch_id' => 1,
            'order_ids' => [1],
        ]);
    }

    public function testValidateCreateRequiresBranchId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate([
            'customer_id' => 1,
            'order_ids' => [1],
        ]);
    }

    public function testValidateCreateRequiresOrderIds(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate([
            'customer_id' => 1,
            'branch_id' => 1,
        ]);
    }

    public function testValidateCreateAcceptsValidData(): void
    {
        $data = [
            'customer_id' => 1,
            'branch_id' => 1,
            'order_ids' => [1, 2],
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertSame(1, $result['customer_id']);
        $this->assertSame(1, $result['branch_id']);
        $this->assertSame([1, 2], $result['order_ids']);
        $this->assertSame('standard', $result['invoice_type']);
    }

    public function testValidateCreateSetsDefaultIssueDateToToday(): void
    {
        $data = [
            'customer_id' => 1,
            'branch_id' => 1,
            'order_ids' => [1],
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertSame(date('Y-m-d'), $result['issue_date']);
    }

    public function testValidateCreateThrowsOnDueDateBeforeIssueDate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate([
            'customer_id' => 1,
            'branch_id' => 1,
            'order_ids' => [1],
            'issue_date' => '2024-12-31',
            'due_date' => '2024-01-01',
        ]);
    }

    public function testValidateCreateWithVatRate(): void
    {
        $data = [
            'customer_id' => 1,
            'branch_id' => 1,
            'order_ids' => [1],
            'vat_rate' => 0.08,
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertSame(0.08, $result['vat_rate']);
    }

    public function testValidateCreateSetsDefaultVatRate(): void
    {
        $data = [
            'customer_id' => 1,
            'branch_id' => 1,
            'order_ids' => [1],
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertSame(0.1, $result['vat_rate']);
    }

    public function testValidateCreateNormalizesOrderIds(): void
    {
        $data = [
            'customer_id' => 1,
            'branch_id' => 1,
            'order_ids' => ['1', '2', '3'],
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertSame([1, 2, 3], $result['order_ids']);
    }

    public function testValidateCreateThrowsOnNonArrayOrderIds(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate([
            'customer_id' => 1,
            'branch_id' => 1,
            'order_ids' => '1,2,3',
        ]);
    }

    public function testValidateCreateThrowsOnNegativeOrderId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate([
            'customer_id' => 1,
            'branch_id' => 1,
            'order_ids' => [-1],
        ]);
    }

    public function testValidateUpdateAcceptsValidData(): void
    {
        $result = $this->validator->validateUpdate([
            'notes' => 'Updated notes',
            'invoice_status' => 'completed',
        ]);

        $this->assertSame('Updated notes', $result['notes']);
        $this->assertSame('completed', $result['invoice_status']);
    }

    public function testValidateUpdateWithDeliveryStatus(): void
    {
        $result = $this->validator->validateUpdate([
            'delivery_status' => 'delivered',
        ]);

        $this->assertSame('delivered', $result['delivery_status']);
    }

    public function testValidateUpdateWithShipmentCode(): void
    {
        $result = $this->validator->validateUpdate([
            'shipment_code' => 'SHIP-001',
        ]);

        $this->assertSame('SHIP-001', $result['shipment_code']);
    }

    public function testValidateUpdateReturnsOnlyAllowedFields(): void
    {
        $result = $this->validator->validateUpdate([
            'notes' => 'Test',
            'customer_id' => 999, // Not allowed
        ]);

        $this->assertArrayHasKey('notes', $result);
        $this->assertArrayNotHasKey('customer_id', $result);
    }
}
