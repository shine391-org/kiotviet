<?php

namespace Tests\Validators;

use App\Validators\ReturnValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

class ReturnValidatorTest extends CIUnitTestCase
{
    private ReturnValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ReturnValidator();
    }

    public function testValidateCreateSuccess(): void
    {
        $data = [
            'order_id' => 1,
            'customer_id' => 1,
            'items' => [
                ['order_item_id' => 1, 'quantity_returned' => 1],
            ],
            'reason' => 'defective',
        ];
        $result = $this->validator->validateCreate($data);
        
        $this->assertEquals(1, $result['order_id']);
        $this->assertEquals(1, $result['customer_id']);
        $this->assertEquals('defective', $result['reason']);
    }

    public function testValidateCreateThrowsWhenOrderIdMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->validator->validateCreate([
            'customer_id' => 1,
            'items' => [['order_item_id' => 1, 'quantity_returned' => 1]],
            'reason' => 'defective',
        ]);
    }

    public function testValidateCreateThrowsWhenCustomerIdMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->validator->validateCreate([
            'order_id' => 1,
            'items' => [['order_item_id' => 1, 'quantity_returned' => 1]],
            'reason' => 'defective',
        ]);
    }

    public function testValidateCreateThrowsWhenItemsMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->validator->validateCreate([
            'order_id' => 1,
            'customer_id' => 1,
            'reason' => 'defective',
        ]);
    }

    public function testValidateCreateThrowsWhenItemsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->validator->validateCreate([
            'order_id' => 1,
            'customer_id' => 1,
            'items' => [],
            'reason' => 'defective',
        ]);
    }

    public function testValidateCreateThrowsWhenItemsNotArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('items must be an array');
        
        $this->validator->validateCreate([
            'order_id' => 1,
            'customer_id' => 1,
            'items' => 'invalid',
            'reason' => 'defective',
        ]);
    }

    public function testValidateCreateThrowsWhenReasonMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->validator->validateCreate([
            'order_id' => 1,
            'customer_id' => 1,
            'items' => [['order_item_id' => 1, 'quantity_returned' => 1]],
        ]);
    }

    public function testValidateCreateThrowsWhenReasonInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->validator->validateCreate([
            'order_id' => 1,
            'customer_id' => 1,
            'items' => [['order_item_id' => 1, 'quantity_returned' => 1]],
            'reason' => 'invalid_reason',
        ]);
    }

    public function testValidateCreateThrowsWhenOtherReasonWithoutDetail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Reason detail is required for "other" reason');
        
        $this->validator->validateCreate([
            'order_id' => 1,
            'customer_id' => 1,
            'items' => [['order_item_id' => 1, 'quantity_returned' => 1]],
            'reason' => 'other',
        ]);
    }

    public function testValidateCreateSuccessWithOtherReasonAndDetail(): void
    {
        $data = [
            'order_id' => 1,
            'customer_id' => 1,
            'items' => [['order_item_id' => 1, 'quantity_returned' => 1]],
            'reason' => 'other',
            'reason_detail' => 'Custom reason',
        ];
        $result = $this->validator->validateCreate($data);
        
        $this->assertEquals('other', $result['reason']);
        $this->assertEquals('Custom reason', $result['reason_detail']);
    }

    public function testValidateCreateThrowsWhenOrderItemIdMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('order_item_id is required');
        
        $this->validator->validateCreate([
            'order_id' => 1,
            'customer_id' => 1,
            'items' => [['quantity_returned' => 1]],
            'reason' => 'defective',
        ]);
    }

    public function testValidateCreateThrowsWhenQuantityZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('quantity_returned must be > 0');
        
        $this->validator->validateCreate([
            'order_id' => 1,
            'customer_id' => 1,
            'items' => [['order_item_id' => 1, 'quantity_returned' => 0]],
            'reason' => 'defective',
        ]);
    }

    public function testValidateCreateThrowsWhenConditionInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('condition is invalid');
        
        $this->validator->validateCreate([
            'order_id' => 1,
            'customer_id' => 1,
            'items' => [['order_item_id' => 1, 'quantity_returned' => 1, 'condition' => 'invalid']],
            'reason' => 'defective',
        ]);
    }

    public function testValidateCreateWithValidConditions(): void
    {
        $conditions = ['new', 'used', 'damaged'];
        foreach ($conditions as $condition) {
            $data = [
                'order_id' => 1,
                'customer_id' => 1,
                'items' => [['order_item_id' => 1, 'quantity_returned' => 1, 'condition' => $condition]],
                'reason' => 'defective',
            ];
            $result = $this->validator->validateCreate($data);
            $this->assertEquals($condition, $result['items'][0]['item_condition']);
        }
    }

    public function testValidateCreateWithRefundShippingFee(): void
    {
        $data = [
            'order_id' => 1,
            'customer_id' => 1,
            'items' => [['order_item_id' => 1, 'quantity_returned' => 1]],
            'reason' => 'defective',
            'refund_shipping_fee' => '1',
        ];
        $result = $this->validator->validateCreate($data);
        
        $this->assertTrue($result['refund_shipping_fee']);
    }

    public function testValidateCreateWithRefundMethod(): void
    {
        $data = [
            'order_id' => 1,
            'customer_id' => 1,
            'items' => [['order_item_id' => 1, 'quantity_returned' => 1]],
            'reason' => 'defective',
            'refund_method' => 'cash',
        ];
        $result = $this->validator->validateCreate($data);
        
        $this->assertEquals('cash', $result['refund_method']);
    }

    public function testValidateTransitionSuccess(): void
    {
        $data = ['user_id' => 1];
        $result = $this->validator->validateTransition($data);
        
        $this->assertEquals(1, $result['user_id']);
    }

    public function testValidateTransitionThrowsWhenUserIdMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->validator->validateTransition([]);
    }

    public function testValidateTransitionWithVersion(): void
    {
        $data = ['user_id' => 1, 'version' => 5];
        $result = $this->validator->validateTransition($data);
        
        $this->assertEquals(5, $result['version']);
    }

    public function testValidateApprovalSuccess(): void
    {
        $data = ['user_id' => 1, 'refund_method' => 'cash'];
        $result = $this->validator->validateApproval($data);
        
        $this->assertEquals(1, $result['user_id']);
        $this->assertEquals('cash', $result['refund_method']);
    }

    public function testValidateApprovalThrowsWhenRefundMethodMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->validator->validateApproval(['user_id' => 1]);
    }

    public function testValidateApprovalThrowsWhenRefundMethodInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->validator->validateApproval(['user_id' => 1, 'refund_method' => 'invalid']);
    }

    public function testValidateApprovalWithNotes(): void
    {
        $data = ['user_id' => 1, 'refund_method' => 'bank_transfer', 'notes' => 'Approved'];
        $result = $this->validator->validateApproval($data);
        
        $this->assertEquals('Approved', $result['notes']);
    }

    public function testValidateCreateWithItemConditionAlias(): void
    {
        $data = [
            'order_id' => 1,
            'customer_id' => 1,
            'items' => [['order_item_id' => 1, 'quantity_returned' => 1, 'item_condition' => 'new']],
            'reason' => 'defective',
        ];
        $result = $this->validator->validateCreate($data);
        
        $this->assertEquals('new', $result['items'][0]['item_condition']);
    }
}
