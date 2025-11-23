<?php

namespace Tests\Validators;

use App\Validators\InventoryValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

/** @agent-test: InventoryValidator tests @agent-pattern: Validation tests */
class InventoryValidatorTest extends CIUnitTestCase
{
    private InventoryValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new InventoryValidator();
    }

    /** @test */
    public function it_validates_warehouse_create_with_all_fields(): void
    {
        $input = [
            'code' => 'WH001',
            'name' => 'Main Warehouse',
            'address' => '123 Main St',
            'phone' => '123-456-7890',
            'manager_id' => 1,
            'status' => 'active',
            'is_default' => '1',
        ];

        $result = $this->validator->validateWarehouseCreate($input);

        $this->assertSame('WH001', $result['code']);
        $this->assertSame('Main Warehouse', $result['name']);
        $this->assertSame('123 Main St', $result['address']);
        $this->assertSame('123-456-7890', $result['phone']);
        $this->assertSame(1, $result['manager_id']);
        $this->assertSame('active', $result['status']);
        $this->assertTrue($result['is_default']);
    }

    /** @test */
    public function it_validates_warehouse_create_with_minimal_fields(): void
    {
        $input = [
            'code' => 'WH002',
            'name' => 'Secondary Warehouse',
        ];

        $result = $this->validator->validateWarehouseCreate($input);

        $this->assertSame('WH002', $result['code']);
        $this->assertSame('Secondary Warehouse', $result['name']);
        $this->assertArrayNotHasKey('address', $result);
        $this->assertArrayNotHasKey('phone', $result);
    }

    /** @test */
    public function it_throws_on_warehouse_create_missing_required_fields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateWarehouseCreate(['name' => 'Warehouse without code']);
    }

    /** @test */
    public function it_throws_on_warehouse_create_invalid_status(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateWarehouseCreate([
            'code' => 'WH003',
            'name' => 'Invalid Warehouse',
            'status' => 'invalid_status'
        ]);
    }

    /** @test */
    public function it_validates_warehouse_update_with_partial_data(): void
    {
        $input = [
            'name' => 'Updated Warehouse Name',
            'status' => 'inactive',
        ];

        $result = $this->validator->validateWarehouseUpdate($input);

        $this->assertSame('Updated Warehouse Name', $result['name']);
        $this->assertSame('inactive', $result['status']);
    }

    /** @test */
    public function it_throws_on_warehouse_update_empty_data(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No data to update');
        $this->validator->validateWarehouseUpdate([]);
    }

    /** @test */
    public function it_validates_movement_in_type(): void
    {
        $input = [
            'movement_type' => 'IN',
            'product_id' => 1,
            'to_warehouse_id' => 2,
            'quantity' => 100,
            'unit_cost' => 50.5,
            'reason' => 'Stock in',
        ];

        $result = $this->validator->validateMovement($input);

        $this->assertSame('IN', $result['movement_type']);
        $this->assertSame(1, $result['product_id']);
        $this->assertSame(2, $result['to_warehouse_id']);
        $this->assertEquals(100.0, $result['quantity']);
        $this->assertSame(50.5, $result['unit_cost']);
        $this->assertSame('Stock in', $result['reason']);
        $this->assertSame(0, $result['created_by']);
        $this->assertSame('AVERAGE', $result['valuation_method']);
    }

    /** @test */
    public function it_validates_movement_out_type(): void
    {
        $input = [
            'movement_type' => 'OUT',
            'product_id' => 1,
            'from_warehouse_id' => 2,
            'quantity' => 50,
            'reason' => 'Stock out',
        ];

        $result = $this->validator->validateMovement($input);

        $this->assertSame('OUT', $result['movement_type']);
        $this->assertSame(2, $result['from_warehouse_id']);
        $this->assertArrayNotHasKey('to_warehouse_id', $result);
    }

    /** @test */
    public function it_validates_movement_transfer_type(): void
    {
        $input = [
            'movement_type' => 'TRANSFER',
            'product_id' => 1,
            'from_warehouse_id' => 2,
            'to_warehouse_id' => 3,
            'quantity' => 25,
        ];

        $result = $this->validator->validateMovement($input);

        $this->assertSame('TRANSFER', $result['movement_type']);
        $this->assertSame(2, $result['from_warehouse_id']);
        $this->assertSame(3, $result['to_warehouse_id']);
    }

    /** @test */
    public function it_validates_movement_adjustment_type(): void
    {
        $input = [
            'movement_type' => 'ADJUSTMENT',
            'product_id' => 1,
            'quantity' => 10,
            'reason' => 'Stock adjustment',
        ];

        $result = $this->validator->validateMovement($input);

        $this->assertSame('ADJUSTMENT', $result['movement_type']);
        $this->assertArrayNotHasKey('from_warehouse_id', $result);
        $this->assertArrayNotHasKey('to_warehouse_id', $result);
    }

    /** @test */
    public function it_throws_on_movement_transfer_missing_warehouses(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('TRANSFER requires both from_warehouse_id and to_warehouse_id');
        
        $this->validator->validateMovement([
            'movement_type' => 'TRANSFER',
            'product_id' => 1,
            'quantity' => 10,
        ]);
    }

    /** @test */
    public function it_throws_on_movement_in_missing_warehouse(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('IN requires to_warehouse_id');
        
        $this->validator->validateMovement([
            'movement_type' => 'IN',
            'product_id' => 1,
            'quantity' => 10,
        ]);
    }

    /** @test */
    public function it_throws_on_movement_out_missing_warehouse(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('OUT requires from_warehouse_id');
        
        $this->validator->validateMovement([
            'movement_type' => 'OUT',
            'product_id' => 1,
            'quantity' => 10,
        ]);
    }

    /** @test */
    public function it_throws_on_movement_invalid_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->validator->validateMovement([
            'movement_type' => 'INVALID',
            'product_id' => 1,
            'quantity' => 10,
        ]);
    }

    /** @test */
    public function it_throws_on_movement_missing_required_fields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->validator->validateMovement([
            'movement_type' => 'IN',
            // missing product_id and quantity
        ]);
    }

    /** @test */
    public function it_validates_reservation_with_all_fields(): void
    {
        $input = [
            'product_id' => 1,
            'variant_id' => 2,
            'warehouse_id' => 3,
            'quantity' => 10,
            'reference' => 'Order #123',
        ];

        $result = $this->validator->validateReservation($input);

        $this->assertSame(1, $result['product_id']);
        $this->assertSame(2, $result['variant_id']);
        $this->assertSame(3, $result['warehouse_id']);
        $this->assertEquals(10.0, $result['quantity']);
        $this->assertSame('Order #123', $result['reference']);
    }

    /** @test */
    public function it_validates_reservation_with_minimal_fields(): void
    {
        $input = [
            'product_id' => 1,
            'warehouse_id' => 2,
            'quantity' => 5,
        ];

        $result = $this->validator->validateReservation($input);

        $this->assertSame(1, $result['product_id']);
        $this->assertSame(2, $result['warehouse_id']);
        $this->assertEquals(5.0, $result['quantity']);
        $this->assertArrayNotHasKey('variant_id', $result);
        $this->assertArrayNotHasKey('reference', $result);
    }

    /** @test */
    public function it_throws_on_reservation_missing_required_fields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->validator->validateReservation([
            'product_id' => 1,
            // missing warehouse_id and quantity
        ]);
    }

    /** @test */
    public function it_throws_on_reservation_invalid_quantity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->validator->validateReservation([
            'product_id' => 1,
            'warehouse_id' => 2,
            'quantity' => -5, // negative quantity
        ]);
    }

    /** @test */
    public function it_validates_alert_action(): void
    {
        $input = [
            'alert_id' => 1,
            'resolved_by' => 2,
        ];

        $result = $this->validator->validateAlertAction($input);

        $this->assertSame(1, $result['alert_id']);
        $this->assertSame(2, $result['resolved_by']);
    }

    /** @test */
    public function it_validates_alert_action_minimal(): void
    {
        $input = [
            'alert_id' => 1,
        ];

        $result = $this->validator->validateAlertAction($input);

        $this->assertSame(1, $result['alert_id']);
        $this->assertArrayNotHasKey('resolved_by', $result);
    }

    /** @test */
    public function it_throws_on_alert_action_missing_alert_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->validator->validateAlertAction([
            'resolved_by' => 1,
        ]);
    }

    /** @test */
    public function it_throws_on_alert_action_invalid_alert_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->validator->validateAlertAction([
            'alert_id' => 0, // must be greater than 0
        ]);
    }

    /** @test */
    public function it_normalizes_boolean_fields(): void
    {
        $input = [
            'code' => 'WH004',
            'name' => 'Bool Test Warehouse',
            'is_default' => '1',
        ];

        $result = $this->validator->validateWarehouseCreate($input);

        $this->assertTrue($result['is_default']);
    }

    /** @test */
    public function it_normalizes_boolean_false_fields(): void
    {
        $input = [
            'code' => 'WH005',
            'name' => 'Bool False Test Warehouse',
            'is_default' => 'false',
        ];

        $result = $this->validator->validateWarehouseCreate($input);

        $this->assertFalse($result['is_default']);
    }
}
