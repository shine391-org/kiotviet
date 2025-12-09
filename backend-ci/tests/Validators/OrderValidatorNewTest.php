<?php

namespace Tests\Validators;

use App\Validators\OrderValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

class OrderValidatorNewTest extends CIUnitTestCase
{
    private OrderValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new OrderValidator();
    }

    public function testValidateOrderSuccess(): void
    {
        $data = [
            'items' => [
                ['product_id' => 1, 'quantity' => 2],
            ],
        ];
        $result = $this->validator->validateOrder($data);
        
        $this->assertCount(1, $result['items']);
        $this->assertEquals(1, $result['items'][0]['product_id']);
        $this->assertEquals(2, $result['items'][0]['quantity']);
    }

    public function testValidateOrderThrowsWhenItemsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('items is required');
        
        $this->validator->validateOrder(['items' => []]);
    }

    public function testValidateOrderThrowsWhenItemsMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('items is required');
        
        $this->validator->validateOrder([]);
    }

    public function testValidateOrderThrowsWhenItemsNotArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('items is required');
        
        $this->validator->validateOrder(['items' => 'invalid']);
    }

    public function testValidateOrderThrowsOnInvalidDate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('order_date is invalid');
        
        $this->validator->validateOrder([
            'items' => [['product_id' => 1, 'quantity' => 1]],
            'order_date' => 'invalid-date',
        ]);
    }

    public function testValidateOrderAcceptsValidDate(): void
    {
        $data = [
            'items' => [['product_id' => 1, 'quantity' => 1]],
            'order_date' => '2024-06-15',
        ];
        $result = $this->validator->validateOrder($data);
        
        $this->assertEquals('2024-06-15', $result['order_date']);
    }

    public function testValidateOrderThrowsWhenProductIdMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('product_id is required');
        
        $this->validator->validateOrder([
            'items' => [['quantity' => 1]],
        ]);
    }

    public function testValidateOrderThrowsWhenProductIdZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('product_id is required');
        
        $this->validator->validateOrder([
            'items' => [['product_id' => 0, 'quantity' => 1]],
        ]);
    }

    public function testValidateOrderThrowsWhenQuantityZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('quantity must be > 0');
        
        $this->validator->validateOrder([
            'items' => [['product_id' => 1, 'quantity' => 0]],
        ]);
    }

    public function testValidateOrderThrowsWhenQuantityNegative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('quantity must be > 0');
        
        $this->validator->validateOrder([
            'items' => [['product_id' => 1, 'quantity' => -5]],
        ]);
    }

    public function testValidateOrderDefaultsQuantityToOne(): void
    {
        $data = [
            'items' => [['product_id' => 1]],
        ];
        $result = $this->validator->validateOrder($data);
        
        $this->assertEquals(1, $result['items'][0]['quantity']);
    }

    public function testValidateOrderWithVariantId(): void
    {
        $data = [
            'items' => [['product_id' => 1, 'quantity' => 1, 'variant_id' => 5]],
        ];
        $result = $this->validator->validateOrder($data);
        
        $this->assertEquals(5, $result['items'][0]['variant_id']);
    }

    public function testValidateOrderWithBatchId(): void
    {
        $data = [
            'items' => [['product_id' => 1, 'quantity' => 1, 'batch_id' => 10]],
        ];
        $result = $this->validator->validateOrder($data);
        
        $this->assertEquals(10, $result['items'][0]['batch_id']);
    }

    public function testValidateOrderWithSerialNumbers(): void
    {
        $data = [
            'items' => [[
                'product_id' => 1,
                'quantity' => 2,
                'serial_numbers' => ['SN001', 'SN002'],
            ]],
        ];
        $result = $this->validator->validateOrder($data);
        
        $this->assertEquals(['SN001', 'SN002'], $result['items'][0]['serial_numbers']);
    }

    public function testValidateOrderThrowsWhenSerialCountMismatch(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('serial_numbers count must match quantity');
        
        $this->validator->validateOrder([
            'items' => [[
                'product_id' => 1,
                'quantity' => 3,
                'serial_numbers' => ['SN001', 'SN002'],
            ]],
        ]);
    }

    public function testValidateOrderWithCustomerId(): void
    {
        $data = [
            'items' => [['product_id' => 1, 'quantity' => 1]],
            'customer_id' => 100,
        ];
        $result = $this->validator->validateOrder($data);
        
        $this->assertEquals(100, $result['customer_id']);
    }

    public function testValidateOrderWithCustomerGroupId(): void
    {
        $data = [
            'items' => [['product_id' => 1, 'quantity' => 1]],
            'customer_group_id' => 5,
        ];
        $result = $this->validator->validateOrder($data);
        
        $this->assertEquals(5, $result['customer_group_id']);
    }

    public function testValidateOrderWithPriceListId(): void
    {
        $data = [
            'items' => [['product_id' => 1, 'quantity' => 1]],
            'price_list_id' => 3,
        ];
        $result = $this->validator->validateOrder($data);
        
        $this->assertEquals(3, $result['price_list_id']);
    }

    public function testValidateOrderThrowsWhenPriceListIdZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('price_list_id must be positive');
        
        $this->validator->validateOrder([
            'items' => [['product_id' => 1, 'quantity' => 1]],
            'price_list_id' => 0,
        ]);
    }

    public function testValidateOrderThrowsWhenPriceListIdNegative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('price_list_id must be positive');
        
        $this->validator->validateOrder([
            'items' => [['product_id' => 1, 'quantity' => 1]],
            'price_list_id' => -1,
        ]);
    }

    public function testValidateOrderWithMultipleItems(): void
    {
        $data = [
            'items' => [
                ['product_id' => 1, 'quantity' => 2],
                ['product_id' => 3, 'quantity' => 1],
            ],
        ];
        $result = $this->validator->validateOrder($data);
        
        $this->assertCount(2, $result['items']);
        $this->assertEquals(1, $result['items'][0]['product_id']);
        $this->assertEquals(3, $result['items'][1]['product_id']);
    }

    public function testValidateOrderWithSerialNumberAsString(): void
    {
        $data = [
            'items' => [[
                'product_id' => 1,
                'quantity' => 1,
                'serial_numbers' => 'SN001',
            ]],
        ];
        $result = $this->validator->validateOrder($data);
        
        $this->assertEquals(['SN001'], $result['items'][0]['serial_numbers']);
    }

    public function testValidateOrderWithDecimalQuantity(): void
    {
        $data = [
            'items' => [['product_id' => 1, 'quantity' => 1.5]],
        ];
        $result = $this->validator->validateOrder($data);
        
        $this->assertEquals(1.5, $result['items'][0]['quantity']);
    }

    public function testValidateOrderDefaultsOrderDate(): void
    {
        $data = [
            'items' => [['product_id' => 1, 'quantity' => 1]],
        ];
        $result = $this->validator->validateOrder($data);
        
        $this->assertEquals(date('Y-m-d'), $result['order_date']);
    }
}
