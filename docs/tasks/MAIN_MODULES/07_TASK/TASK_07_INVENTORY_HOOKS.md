---
title: "TASK 07: Inventory Hooks Implementation"
id: "TASK-07-INVENTORY-HOOKS-01"
priority: "P1 (High)"
estimated_effort: "5 days"
dependencies: "TASK_06"
status: "Done"
module: "Order Workflow"
type: "Implementation Task"
tags: ["task", "inventory", "hooks", "stock", "deduction", "restoration", "locking", "reconciliation", "multi-branch", "backend"]
purpose: "Implement a robust inventory management system with stock checking, deduction (using pessimistic locking), restoration, movement logging, and reconciliation, supporting multi-branch operations."
location: "docs/tasks/MAIN_MODULES/07_TASK"
related_to:
  - id: "INVENTORY-EDGE-CASES-01"
    description: "Addresses inventory edge cases discussed."
  - id: "CONCURRENCY-EDGE-CASES-01"
    description: "Deals with concurrency for inventory deduction."
  - id: "TASK-06-STATUS-MANAGEMENT-01"
    description: "Dependency for status-based inventory hooks."
  - id: "ORDERS-TABLE-01"
    description: "Inventory changes linked to orders."
  - id: "ORDER-WORKFLOW-INDEX"
    description: "Task listed in the module index."
---

# TASK_07: Inventory Hooks Implementation

**Priority:** P1 (High)

**Estimated Effort:** 5 days

**Dependencies:** TASK_06

**Status:** Done

---

## 🎯 OBJECTIVE

Implement **inventory management system** with deduction/restoration hooks.

---

## 📋 REQUIREMENTS

- [x]  Create inventory table
- [x]  Implement stock checking
- [x]  Implement stock deduction (pessimistic locking)
- [x]  Implement stock restoration
- [x]  Log all inventory movements
- [x]  Support multi-branch inventory

---

## 🗄️ INVENTORY TABLE

```sql
CREATE TABLE inventory (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    branch_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    variant_id BIGINT UNSIGNED,
    quantity INT NOT NULL DEFAULT 0,
    
    UNIQUE KEY unique_inventory (branch_id, product_id, variant_id),
    INDEX idx_product (product_id),
    
    CONSTRAINT chk_quantity_nonnegative CHECK (quantity >= 0)
);
```

---

## 🔒 STOCK CHECKER

```php
<?php

namespace App\Services;

use App\Models\Inventory;

class StockChecker
{
    public function check(
        int $branchId,
        int $productId,
        ?int $variantId,
        int $requiredQuantity
    ): bool {
        $inventory = Inventory::where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->first();
        
        if (!$inventory) {
            return false;
        }
        
        return $inventory->quantity >= $requiredQuantity;
    }

    public function checkBatch(int $branchId, array $items): array
    {
        $results = [];
        
        foreach ($items as $item) {
            $results[] = [
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'requested' => $item['quantity'],
                'available' => $this->getAvailable(
                    $branchId,
                    $item['product_id'],
                    $item['variant_id'] ?? null
                ),
                'sufficient' => $this->check(
                    $branchId,
                    $item['product_id'],
                    $item['variant_id'] ?? null,
                    $item['quantity']
                )
            ];
        }
        
        return $results;
    }

    private function getAvailable(int $branchId, int $productId, ?int $variantId): int
    {
        $inventory = Inventory::where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->first();
        
        return $inventory?->quantity ?? 0;
    }
}
```

---

## 🔒 INVENTORY DEDUCTION (Pessimistic Locking)

```php
<?php

namespace App\Services;

use App\Models\Inventory;
use Illuminate\Support\Facades\DB;

class InventoryDeduction
{
    public function deduct(
        int $branchId,
        int $productId,
        ?int $variantId,
        int $quantity
    ): void {
        DB::transaction(function () use ($branchId, $productId, $variantId, $quantity) {
            // Lock row to prevent race conditions
            $inventory = Inventory::where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->where('variant_id', $variantId)
                ->lockForUpdate()
                ->first();
            
            if (!$inventory) {
                throw new \Exception(
                    'Inventory not found',
                    'INV_NOT_FOUND'
                );
            }
            
            if ($inventory->quantity < $quantity) {
                throw new \Exception(
                    "Insufficient stock. Available: {$inventory->quantity}, Requested: {$quantity}",
                    'INV_INSUFFICIENT_STOCK'
                );
            }
            
            // Deduct
            $inventory->decrement('quantity', $quantity);
        });
    }
}
```

---

## 🔄 INVENTORY RESTORATION

```php
<?php

namespace App\Services;

use App\Models\Inventory;

class InventoryRestoration
{
    public function restore(
        int $branchId,
        int $productId,
        ?int $variantId,
        int $quantity
    ): void {
        $inventory = Inventory::where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->firstOrFail();
        
        $inventory->increment('quantity', $quantity);
    }
}
```

---

## 📊 INVENTORY RECONCILIATION

```php
<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;

class InventoryReconciliation
{
    public function reconcile(
        int $branchId,
        int $productId,
        ?int $variantId
    ): array {
        // Calculate from movements
        $calculated = InventoryMovement::where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->sum('quantity');
        
        // Get actual
        $inventory = Inventory::where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->firstOrFail();
        
        $actual = $inventory->quantity;
        $discrepancy = $calculated - $actual;
        
        return [
            'calculated' => $calculated,
            'actual' => $actual,
            'discrepancy' => $discrepancy,
            'status' => $discrepancy === 0 ? 'match' : 'mismatch'
        ];
    }

    public function fix(
        int $branchId,
        int $productId,
        ?int $variantId
    ): void {
        $result = $this->reconcile($branchId, $productId, $variantId);
        
        if ($result['discrepancy'] !== 0) {
            // Adjust inventory to match calculated
            $inventory = Inventory::where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->where('variant_id', $variantId)
                ->firstOrFail();
            
            $inventory->update(['quantity' => $result['calculated']]);
            
            // Log adjustment
            InventoryMovement::create([
                'branch_id' => $branchId,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'type' => 'adjustment',
                'quantity' => $result['discrepancy'],
                'notes' => 'Reconciliation adjustment',
                'created_by' => auth()->id(),
            ]);
        }
    }
}
```

---

## 🧪 TESTING

```php
/** @test */
public function it_prevents_overselling_with_pessimistic_locking()
{
    $inventory = Inventory::factory()->create([
        'branch_id' => 1,
        'product_id' => 1,
        'quantity' => 5
    ]);
    
    // Simulate concurrent deductions
    $promises = [];
    for ($i = 0; $i < 3; $i++) {
        $promises[] = async(function () use ($inventory) {
            try {
                $this->deduction->deduct(
                    $inventory->branch_id,
                    $inventory->product_id,
                    null,
                    3
                );
                return true;
            } catch (\Exception $e) {
                return false;
            }
        });
    }
    
    $results = await($promises);
    
    // Only one should succeed (5 - 3 = 2, cannot serve 3 more)
    $this->assertEquals(1, array_sum($results));
}
```

---

## 📝 ACCEPTANCE CRITERIA

- [x]  Inventory table created
- [x]  Stock checking works
- [x]  Pessimistic locking prevents overselling
- [x]  Movements logged
- [x]  Reconciliation works
- [x]  All tests passing

---

## 🔗 RELATED DOCUMENTS

- [**INVENTORY.md**](http://INVENTORY.md)
- [**CONCURRENCY.md**](http://CONCURRENCY.md)
