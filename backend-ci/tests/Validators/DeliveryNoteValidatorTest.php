<?php

namespace Tests\Validators;

use App\Validators\DeliveryNoteValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

/**
 * @agent-test: DeliveryNoteValidator
 * @agent-pattern: Validator unit coverage
 */
class DeliveryNoteValidatorTest extends CIUnitTestCase
{
    private DeliveryNoteValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new DeliveryNoteValidator();
    }

    public function testValidateCreateRequiresBranchId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate([
            'items' => [['product_id' => 1, 'quantity' => 2]],
        ]);
    }

    public function testValidateCreateRequiresItems(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate([
            'branch_id' => 1,
        ]);
    }

    public function testValidateCreateAcceptsValidData(): void
    {
        $data = [
            'branch_id' => 1,
            'items' => [
                ['product_id' => 1, 'quantity' => 2],
            ],
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertSame(1, $result['branch_id']);
        $this->assertCount(1, $result['items']);
        $this->assertSame('draft', $result['status']);
    }

    public function testValidateCreateSetsDefaultDeliveryDate(): void
    {
        $data = [
            'branch_id' => 1,
            'items' => [['product_id' => 1, 'quantity' => 2]],
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertSame(date('Y-m-d'), $result['delivery_date']);
    }

    public function testValidateCreateWithOptionalFields(): void
    {
        $data = [
            'branch_id' => 1,
            'order_id' => 5,
            'customer_id' => 3,
            'shipping_address' => '123 Main St',
            'tracking_number' => 'TRACK-001',
            'carrier' => 'GHTK',
            'items' => [['product_id' => 1, 'quantity' => 2]],
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertSame(5, $result['order_id']);
        $this->assertSame(3, $result['customer_id']);
        $this->assertSame('123 Main St', $result['shipping_address']);
        $this->assertSame('TRACK-001', $result['tracking_number']);
        $this->assertSame('GHTK', $result['carrier']);
    }

    public function testValidateCreateThrowsOnInvalidItem(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate([
            'branch_id' => 1,
            'items' => [['product_id' => 0, 'quantity' => 2]],
        ]);
    }

    public function testValidateCreateFromOrderRequiresOrderId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreateFromOrder([
            'branch_id' => 1,
        ]);
    }

    public function testValidateCreateFromOrderRequiresBranchId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreateFromOrder([
            'order_id' => 1,
        ]);
    }

    public function testValidateCreateFromOrderAcceptsValidData(): void
    {
        $data = [
            'order_id' => 1,
            'branch_id' => 1,
        ];

        $result = $this->validator->validateCreateFromOrder($data);

        $this->assertSame(1, $result['order_id']);
        $this->assertSame(1, $result['branch_id']);
    }

    public function testValidateStatusAcceptsValidStatus(): void
    {
        $allowedStatuses = ['draft', 'shipped', 'delivered'];

        $result = $this->validator->validateStatus(['status' => 'shipped'], $allowedStatuses);

        $this->assertSame('shipped', $result);
    }

    public function testValidateStatusThrowsOnInvalidStatus(): void
    {
        $allowedStatuses = ['draft', 'shipped', 'delivered'];

        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateStatus(['status' => 'invalid'], $allowedStatuses);
    }

    public function testValidateDeliverRequiresItems(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateDeliver([]);
    }

    public function testValidateDeliverAcceptsValidData(): void
    {
        $data = [
            'delivered_by' => 1,
            'items' => [
                ['delivery_note_item_id' => 1, 'quantity' => 5],
            ],
        ];

        $result = $this->validator->validateDeliver($data);

        $this->assertSame(1, $result['delivered_by']);
        $this->assertCount(1, $result['items']);
    }

    public function testValidateDeliverThrowsOnInvalidItemId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateDeliver([
            'items' => [['delivery_note_item_id' => 0, 'quantity' => 5]],
        ]);
    }

    public function testValidateDeliverThrowsOnZeroQuantity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateDeliver([
            'items' => [['delivery_note_item_id' => 1, 'quantity' => 0]],
        ]);
    }

    public function testValidateShipAcceptsValidData(): void
    {
        $data = [
            'tracking_number' => 'TRACK-001',
            'carrier' => 'GHTK',
            'notes' => 'Ship today',
        ];

        $result = $this->validator->validateShip($data);

        $this->assertSame('TRACK-001', $result['tracking_number']);
        $this->assertSame('GHTK', $result['carrier']);
        $this->assertSame('Ship today', $result['notes']);
    }

    public function testValidateCreateNormalizesSerialNumbers(): void
    {
        $data = [
            'branch_id' => 1,
            'items' => [
                [
                    'product_id' => 1,
                    'quantity' => 2,
                    'serial_numbers' => ['SN-001', 'SN-002'],
                ],
            ],
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertSame('SN-001,SN-002', $result['items'][0]['serial_number']);
    }

    public function testValidateCreateWithVariantAndBatch(): void
    {
        $data = [
            'branch_id' => 1,
            'items' => [
                [
                    'product_id' => 1,
                    'variant_id' => 2,
                    'batch_id' => 3,
                    'quantity' => 5,
                ],
            ],
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertSame(2, $result['items'][0]['variant_id']);
        $this->assertSame(3, $result['items'][0]['batch_id']);
    }
}
