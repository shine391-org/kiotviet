---
title: "TASK 06: Order Status Management Implementation"
id: "TASK-06-STATUS-MANAGEMENT-01"
priority: "P0 (Blocker)"
estimated_effort: "6 days"
dependencies: "TASK_05"
status: "Blocked"
module: "Order Workflow"
type: "Implementation Task"
tags: ["task", "order-status", "workflow", "state-machine", "inventory", "API", "backend"]
purpose: "Implement the complete order status workflow, including valid transitions, inventory side effects (deduction/restoration), timestamp updates, and API endpoints for status changes."
location: "docs/tasks/MAIN_MODULES/07_TASK"
related_to:
  - id: "ORDERS-TABLE-01"
    description: "Schema for order status."
  - id: "STATE-MACHINE-01"
    description: "Defines the status transitions."
  - id: "SHIPPING-FLOW-01"
    description: "Describes the detailed shipping workflow."
  - id: "INVENTORY-EDGE-CASES-01"
    description: "Inventory side effects and challenges."
  - id: "CONCURRENCY-EDGE-CASES-01"
    description: "Concurrency challenges for status updates."
  - id: "ORDER-WORKFLOW-INDEX"
    description: "Task listed in the module index."
  - id: "TASK-05-ORDER-CREATE-01"
    description: "Dependency: Order Create implementation."
---

# TASK_06_STATUS_MANAGEMENT - Order Status Management

# TASK_06: Order Status Management

**Priority:** P0 (Blocker)

**Estimated Effort:** 6 days

**Dependencies:** TASK_05

**Status:** Blocked

---

## 🎯 OBJECTIVE

Implement **complete order status workflow** with transitions, validation, and side effects.

---

## 📋 STATUS WORKFLOW

```
POS Orders:
draft → completed

SHIPPING Orders:
draft → confirmed → processing → shipping → delivered → completed
              ↓           ↓          ↓
          cancelled   cancelled  cancelled
```

See [**ORDER_](https://www.notion.so/SHIPPING_FLOW-SHIPPING-Orders-Workflow-c9c2807fa20e46d99712079779edf072?pvs=21)[FLOW.md](http://FLOW.md)** for details.

---

## 📑 STATUS TRANSITION VALIDATOR

```php
<?php

namespace App\Services;

class OrderStatusTransition
{
    private const VALID_TRANSITIONS = [
        'draft' => ['confirmed', 'cancelled'],
        'confirmed' => ['processing', 'cancelled'],
        'processing' => ['shipping', 'cancelled'],
        'shipping' => ['delivered'],
        'delivered' => ['completed'],
        'completed' => [],
        'cancelled' => [],
    ];

    public function isValid(string $from, string $to): bool
    {
        return in_array($to, self::VALID_TRANSITIONS[$from] ?? []);
    }

    public function getAllowedTransitions(string $currentStatus): array
    {
        return self::VALID_TRANSITIONS[$currentStatus] ?? [];
    }
}
```

---

## 🔄 UPDATE STATUS SERVICE

```php
<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderStatusService
{
    public function __construct(
        private OrderStatusTransition $transition,
        private InventoryService $inventoryService
    ) {}

    public function updateStatus(
        int $orderId,
        string $newStatus,
        ?string $notes = null
    ): Order {
        return DB::transaction(function () use ($orderId, $newStatus, $notes) {
            $order = Order::where('id', $orderId)
                ->lockForUpdate()
                ->firstOrFail();
            
            $oldStatus = $order->status;
            
            // Validate transition
            if (!$this->transition->isValid($oldStatus, $newStatus)) {
                throw new \Exception(
                    "Cannot transition from {$oldStatus} to {$newStatus}",
                    'ORD_INVALID_STATUS_TRANSITION'
                );
            }
            
            // Execute side effects BEFORE update
            $this->executeSideEffects($order, $oldStatus, $newStatus);
            
            // Update status
            $order->update(['status' => $newStatus]);
            
            // Set timestamps
            $this->updateTimestamps($order, $newStatus);
            
            return $order->fresh();
        });
    }

    private function executeSideEffects(Order $order, string $from, string $to): void
    {
        // Deduct inventory when entering processing
        if ($to === 'processing' && $from !== 'processing') {
            $this->inventoryService->deduct($order);
        }
        
        // Restore inventory when cancelled
        if ($to === 'cancelled' && in_array($from, ['processing', 'shipping'])) {
            $this->inventoryService->restore($order);
        }
        
        // Mark as paid for completed COD orders
        if ($to === 'completed' && $order->payment_method === 'COD') {
            $order->update([
                'paid_amount' => $order->total,
                'is_paid' => true,
                'debt_amount' => 0,
                'cod_collected' => true,
            ]);
        }
    }

    private function updateTimestamps(Order $order, string $status): void
    {
        $timestamps = [
            'confirmed' => 'confirmed_at',
            'delivered' => 'delivered_at',
            'completed' => 'completed_at',
            'cancelled' => 'cancelled_at',
        ];
        
        if (isset($timestamps[$status])) {
            $order->update([$timestamps[$status] => now()]);
        }
    }
}
```

---

## 📦 INVENTORY SERVICE

```php
<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function deduct(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                // Deduct from inventory
                $inventory = Inventory::where('branch_id', $order->branch_id)
                    ->where('product_id', $item->product_id)
                    ->where('variant_id', $item->variant_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                
                if ($inventory->quantity < $item->quantity) {
                    throw new \Exception('Insufficient stock');
                }
                
                $inventory->decrement('quantity', $item->quantity);
                
                // Log movement
                InventoryMovement::create([
                    'branch_id' => $order->branch_id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'type' => 'sale',
                    'quantity' => -$item->quantity,
                    'reference_type' => 'order',
                    'reference_id' => $order->id,
                    'created_by' => auth()->id(),
                ]);
            }
        });
    }

    public function restore(Order $order): void
    {
        foreach ($order->items as $item) {
            Inventory::where('branch_id', $order->branch_id)
                ->where('product_id', $item->product_id)
                ->where('variant_id', $item->variant_id)
                ->increment('quantity', $item->quantity);
            
            // Log movement
            InventoryMovement::create([
                'branch_id' => $order->branch_id,
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'type' => 'adjustment',
                'quantity' => +$item->quantity,
                'reference_type' => 'order',
                'reference_id' => $order->id,
                'notes' => 'Restored from cancelled order',
                'created_by' => auth()->id(),
            ]);
        }
    }
}
```

---

## 🌐 API CONTROLLER

```php
public function updateStatus(Request $request, $id)
{
    $validated = $request->validate([
        'status' => 'required|string|in:confirmed,processing,shipping,delivered,completed,cancelled',
        'notes' => 'nullable|string',
    ]);

    try {
        $order = $this->statusService->updateStatus(
            $id,
            $validated['status'],
            $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Order status updated',
            'data' => $order
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'error_code' => $e->getCode()
        ], 400);
    }
}
```

---

## 🧪 TESTING

```php
/** @test */
public function it_deducts_inventory_when_processing()
{
    $order = Order::factory()->create(['status' => 'confirmed']);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => 1,
        'quantity' => 5
    ]);
    
    Inventory::create([
        'branch_id' => $order->branch_id,
        'product_id' => 1,
        'quantity' => 10
    ]);
    
    $this->statusService->updateStatus($order->id, 'processing');
    
    $this->assertDatabaseHas('inventory', [
        'product_id' => 1,
        'quantity' => 5 // 10 - 5
    ]);
}
```

---

## 📝 ACCEPTANCE CRITERIA

- [x]  Status transitions validated
- [x]  Inventory deducted on processing
- [x]  Inventory restored on cancel
- [x]  Status logs created automatically
- [x]  Timestamps updated
- [x]  All tests passing

---

## 🔗 RELATED DOCUMENTS

- [**ORDER_](https://www.notion.so/SHIPPING_FLOW-SHIPPING-Orders-Workflow-c9c2807fa20e46d99712079779edf072?pvs=21)[FLOW.md](http://FLOW.md)**
- [**INVENTORY.md**](http://INVENTORY.md)
- [**CONCURRENCY.md**](http://CONCURRENCY.md)