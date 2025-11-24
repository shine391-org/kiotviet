---
title: "TASK 08: Cancel Order Implementation"
id: "TASK-08-CANCEL-ORDER-01"
priority: "P1 (High)"
estimated_effort: "3 days"
dependencies: "TASK_06"
status: "Done"
module: "Order Workflow"
type: "Implementation Task"
tags: ["task", "orders", "cancel", "inventory", "status-management", "API", "backend"]
purpose: "Implement the order cancellation logic, including validating cancellable statuses, conditionally restoring inventory, logging movements and status changes, and providing an API endpoint for cancellation."
location: "docs/tasks/MAIN_MODULES/07_TASK"
related_to:
  - id: "ORDERS-TABLE-01"
    description: "Schema for orders to be cancelled."
  - id: "SHIPPING-FLOW-01"
    description: "Cancellation rules within the shipping workflow."
  - id: "INVENTORY-EDGE-CASES-01"
    description: "Inventory restoration during cancellation."
  - id: "CONCURRENCY-EDGE-CASES-01"
    description: "Concurrency for order updates."
  - id: "TASK-06-STATUS-MANAGEMENT-01"
    description: "Dependency: Status management for core logic."
  - id: "ORDER-WORKFLOW-INDEX"
    description: "Task listed in the module index."
---

# TASK_08: Cancel Order Implementation

**Priority:** P1 (High)

**Estimated Effort:** 3 days

**Dependencies:** TASK_06

**Status:** Done

---

## 🎯 OBJECTIVE

Implement **order cancellation** with inventory restoration and refund logic.

---

## 📋 CANCEL RULES

### **When Can Cancel**

- ✅ draft → cancelled
- ✅ confirmed → cancelled
- ✅ processing → cancelled
- ✅ shipping → cancelled
- ❌ delivered (cannot cancel)
- ❌ completed (cannot cancel)
- ❌ cancelled (already cancelled)

### **Side Effects**

- Restore inventory if status was `processing` or `shipping`
- Log inventory movements
- Update timestamps
- Auto-log status change

See [**ORDER_](https://www.notion.so/SHIPPING_FLOW-SHIPPING-Orders-Workflow-c9c2807fa20e46d99712079779edf072?pvs=21)[FLOW.md](http://FLOW.md)**

---

## 🏗️ SERVICE IMPLEMENTATION

### **OrderCancellationService**

```php
<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;
use App\Exceptions\ValidationException;

class OrderCancellationService
{
    private const CANCELLABLE_STATUSES = [
        'draft',
        'confirmed',
        'processing',
        'shipping'
    ];
    
    private const INVENTORY_DEDUCTED_STATUSES = [
        'processing',
        'shipping'
    ];

    public function __construct(
        private InventoryMovementLogger $movementLogger
    ) {}

    public function cancel(int $orderId, string $reason): Order
    {
        return DB::transaction(function () use ($orderId, $reason) {
            // Lock order
            $order = Order::where('id', $orderId)
                ->lockForUpdate()
                ->firstOrFail();
            
            // Validate can cancel
            $this->validateCanCancel($order);
            
            $oldStatus = $order->status;
            
            // Restore inventory if needed
            if ($this->shouldRestoreInventory($order)) {
                $this->restoreInventory($order);
            }
            
            // Update order status
            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);
            
            // Status log will be created automatically by Observer
            
            return $order->fresh(['items', 'customer']);
        });
    }

    private function validateCanCancel(Order $order): void
    {
        if (!in_array($order->status, self::CANCELLABLE_STATUSES)) {
            throw new ValidationException(
                "Cannot cancel order in status: {$order->status}",
                'ORD_CANNOT_CANCEL'
            );
        }
    }

    private function shouldRestoreInventory(Order $order): bool
    {
        return in_array($order->status, self::INVENTORY_DEDUCTED_STATUSES);
    }

    private function restoreInventory(Order $order): void
    {
        foreach ($order->items as $item) {
            // Restore inventory
            $inventory = Inventory::where('branch_id', $order->branch_id)
                ->where('product_id', $item->product_id)
                ->where('variant_id', $item->variant_id)
                ->lockForUpdate()
                ->firstOrFail();
            
            $inventory->increment('quantity', $item->quantity);
            
            // Log movement
            $this->movementLogger->log(
                branchId: $order->branch_id,
                productId: $item->product_id,
                variantId: $item->variant_id,
                type: 'adjustment',
                quantity: +$item->quantity,
                referenceType: 'order',
                referenceId: $order->id,
                notes: "Restored from cancelled order #{$order->order_number}"
            );
        }
    }
}
```

---

## 🌐 API CONTROLLER

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OrderCancellationService;
use Illuminate\Http\Request;

class OrderCancellationController extends Controller
{
    public function __construct(
        private OrderCancellationService $cancellationService
    ) {}

    /**
     * Cancel an order
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(Request $request, int $id)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $order = $this->cancellationService->cancel(
                $id,
                $validated['reason']
            );

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully',
                'data' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'previous_status' => $order->getOriginal('status'),
                    'cancelled_at' => $order->cancelled_at->toIso8601String(),
                    'cancellation_reason' => $order->cancellation_reason,
                ]
            ]);
        } catch (\App\Exceptions\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getCode()
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel order',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
```

---

## 🛣️ ROUTES

```php
// routes/api.php

Route::prefix('orders')->group(function () {
    Route::patch('/{id}/cancel', [OrderCancellationController::class, 'cancel']);
});
```

---

## 🧪 TESTING

### **Unit Tests**

```php
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\OrderCancellationService;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderCancellationServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderCancellationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(OrderCancellationService::class);
    }

    /** @test */
    public function it_can_cancel_order_from_draft()
    {
        $order = Order::factory()->create(['status' => 'draft']);

        $result = $this->service->cancel($order->id, 'Customer changed mind');

        $this->assertEquals('cancelled', $result->status);
        $this->assertNotNull($result->cancelled_at);
        $this->assertEquals('Customer changed mind', $result->cancellation_reason);
    }

    /** @test */
    public function it_restores_inventory_when_cancelling_processing_order()
    {
        // Setup
        $order = Order::factory()->create([
            'status' => 'processing',
            'branch_id' => 1
        ]);
        
        $item = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => 101,
            'variant_id' => 201,
            'quantity' => 5
        ]);
        
        $inventory = Inventory::factory()->create([
            'branch_id' => 1,
            'product_id' => 101,
            'variant_id' => 201,
            'quantity' => 10 // Already deducted 5
        ]);

        // Execute
        $this->service->cancel($order->id, 'Test cancellation');

        // Assert
        $inventory->refresh();
        $this->assertEquals(15, $inventory->quantity); // 10 + 5
        
        // Check movement logged
        $this->assertDatabaseHas('inventory_movements', [
            'branch_id' => 1,
            'product_id' => 101,
            'variant_id' => 201,
            'type' => 'adjustment',
            'quantity' => 5,
            'reference_type' => 'order',
            'reference_id' => $order->id
        ]);
    }

    /** @test */
    public function it_does_not_restore_inventory_when_cancelling_draft_order()
    {
        $order = Order::factory()->create(['status' => 'draft']);
        $item = OrderItem::factory()->create(['order_id' => $order->id]);

        $movementCountBefore = InventoryMovement::count();

        $this->service->cancel($order->id, 'Test');

        $movementCountAfter = InventoryMovement::count();
        $this->assertEquals($movementCountBefore, $movementCountAfter);
    }

    /** @test */
    public function it_cannot_cancel_delivered_order()
    {
        $order = Order::factory()->create(['status' => 'delivered']);

        $this->expectException(\App\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('Cannot cancel order in status: delivered');

        $this->service->cancel($order->id, 'Test');
    }

    /** @test */
    public function it_cannot_cancel_completed_order()
    {
        $order = Order::factory()->create(['status' => 'completed']);

        $this->expectException(\App\Exceptions\ValidationException::class);

        $this->service->cancel($order->id, 'Test');
    }

    /** @test */
    public function it_cannot_cancel_already_cancelled_order()
    {
        $order = Order::factory()->create(['status' => 'cancelled']);

        $this->expectException(\App\Exceptions\ValidationException::class);

        $this->service->cancel($order->id, 'Test');
    }
}
```

---

### **Feature Tests**

```php
<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderCancellationApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_cancel_order_via_api()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['status' => 'confirmed']);

        $response = $this->actingAs($user, 'api')
            ->patchJson("/api/orders/{$order->id}/cancel", [
                'reason' => 'Customer requested cancellation'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'cancelled'
                ]
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Customer requested cancellation'
        ]);
    }

    /** @test */
    public function it_requires_reason_to_cancel()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['status' => 'confirmed']);

        $response = $this->actingAs($user, 'api')
            ->patchJson("/api/orders/{$order->id}/cancel", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    /** @test */
    public function it_returns_error_when_cancelling_delivered_order()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['status' => 'delivered']);

        $response = $this->actingAs($user, 'api')
            ->patchJson("/api/orders/{$order->id}/cancel", [
                'reason' => 'Test'
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error_code' => 'ORD_CANNOT_CANCEL'
            ]);
    }
}
```

---

## 📝 ACCEPTANCE CRITERIA

- [x]  Can cancel from draft, confirmed, processing, shipping
- [x]  Cannot cancel from delivered, completed, cancelled
- [x]  Inventory restored if status was processing/shipping
- [x]  Inventory NOT restored if status was draft/confirmed
- [x]  Timestamps (cancelled_at) updated
- [x]  Cancellation reason stored
- [x]  Inventory movements logged
- [x]  Status logs created automatically
- [x]  API endpoint works
- [x]  All unit tests passing
- [x]  All feature tests passing

---

## 🔗 RELATED DOCUMENTS

- [**ORDER_](https://www.notion.so/SHIPPING_FLOW-SHIPPING-Orders-Workflow-c9c2807fa20e46d99712079779edf072?pvs=21)[FLOW.md](http://FLOW.md)** - Order workflow
- [**ORDERS_](https://www.notion.so/ORDERS_TABLE-Orders-Table-Schema-36b32ddd5ce6421abd19d92589270881?pvs=21)[TABLE.md](http://TABLE.md)** - Schema
- [**INVENTORY.md**](http://INVENTORY.md) - Inventory edge cases
- [**CONCURRENCY.md**](http://CONCURRENCY.md) - Race conditions
