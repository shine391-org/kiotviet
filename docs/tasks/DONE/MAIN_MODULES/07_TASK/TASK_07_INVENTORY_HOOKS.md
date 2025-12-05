---
title: "TASK 07: Inventory Hooks Implementation (CodeIgniter 4)"
id: "TASK-07-INVENTORY-HOOKS-CI4"
priority: "P1 (High)"
estimated_effort: "3 days"
dependencies: "TASK-06-STATUS-MANAGEMENT-CI4"
status: "Done"
module: "Order Workflow"
type: "Implementation Task"
tags: ["task", "inventory", "hooks", "stock", "deduction", "restoration", "locking", "reconciliation", "backend", "codeigniter"]
purpose: "Implement a robust inventory management system in CodeIgniter 4 with stock checking, deduction, restoration, and movement logging, triggered by order status changes."
location: "docs/tasks/MAIN_MODULES/07_TASK"
---

# TASK 07: Inventory Hooks Implementation (CodeIgniter 4)

**Priority:** P1 (High)
**Estimated Effort:** 3 days
**Dependencies:** TASK 06 (Status Management)
**Status:** Done

---

## 🎯 OBJECTIVE

Implement a robust **inventory management system** in **CodeIgniter 4** that integrates seamlessly with the order workflow. This includes creating the necessary database schema and implementing the logic for stock deduction and restoration as side-effects of order status changes.

---

## 📋 REQUIREMENTS

- [x] Create the `inventory_stock` table to track stock levels per product/variant at each branch.
- [x] Implement stock checking logic to prevent overselling.
- [x] Implement atomic stock deduction when an order moves to `processing`.
- [x] Implement atomic stock restoration when a processed order is `cancelled`.
- [x] Log all inventory movements in the `inventory_movements` table for a complete audit trail.
- [x] Support multi-branch inventory.

---

## 🗄️ DATABASE SCHEMA (CodeIgniter 4)

### **Migration: `2025-11-24-000013_CreateInventoryStock.php`**

This migration creates the `inventory_stock` table, which is the single source of truth for current stock levels.

```php
<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class CreateInventoryStock extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'branch_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'variant_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'quantity_on_hand' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'quantity_reserved' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'minimum_stock' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'null' => true],
            'last_movement_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['branch_id', 'product_id', 'variant_id'], 'uq_inventory_stock');
        $this->forge->createTable('inventory_stock', true);
    }

    public function down()
    {
        $this->forge->dropTable('inventory_stock', true);
    }
}
```

---

## 🏗️ ARCHITECTURE & IMPLEMENTATION

The inventory logic is triggered as a side-effect within the `OrderStatusService`.

### **Core Logic: `OrderStatusService.php`**

This service contains the primary logic for inventory adjustments based on status changes.

```php
// app/Services/Orders/OrderStatusService.php

class OrderStatusService
{
    // ... constructor and other methods

    private function applySideEffects(array $order, string $from, string $to, ?int $userId): void
    {
        // Deduct inventory when moving to 'processing'
        if ($to === 'processing' && $from !== 'processing') {
            $this->deductInventory($order, $userId);
        }

        // Restore inventory if a processed order is cancelled
        if ($to === 'cancelled' && in_array($from, ['processing', 'shipping'], true)) {
            $this->restoreInventory($order, $userId);
        }
    }

    private function deductInventory(array $order, ?int $userId): void
    {
        foreach ($order['items'] as $item) {
            $this->adjustInventory($order, $item, -($item['quantity'] ?? 0), 'sale', $userId);
        }
    }
    
    private function restoreInventory(array $order, ?int $userId): void
    {
        foreach ($order['items'] as $item) {
            $this->adjustInventory($order, $item, ($item['quantity'] ?? 0), 'adjustment', $userId, 'Cancel order restore');
        }
    }
    
    private function adjustInventory(array $order, array $item, float $delta, string $type, ?int $userId, ?string $notes = null): void
    {
        $branchId = $order['branch_id'] ?? null;
        $productId = $item['product_id'] ?? null;
        $variantId = $item['variant_id'] ?? null;
        if (! $branchId || ! $productId) {
            return;
        }

        // Use the dedicated repository method with pessimistic locking
        try {
            $this->inventoryRepo->adjustStockWithLock((int)$productId, $variantId ? (int)$variantId : null, (int)$branchId, $delta);
        } catch (\Throwable $e) {
            // If locking fails or stock is insufficient, rethrow.
            throw new \RuntimeException('Failed to adjust inventory: ' . $e->getMessage(), 0, $e);
        }
        
        // Log movement, which is now decoupled from the stock update logic
        $this->movementLogger->log(
            branchId: (int) $branchId,
            productId: (int) $productId,
            variantId: $variantId ? (int) $variantId : null,
            type: $type,
            quantity: $delta,
            referenceType: 'order',
            referenceId: (int) $order['id'],
            notes: $notes,
            createdBy: $userId
        );

        $this->emitInventoryIfNeeded((int) $branchId, (int) $productId, $variantId ? (int) $variantId : null);
    }
}
```

### **Logging: `InventoryMovementLogger.php`**

A dedicated service to ensure every stock change is recorded in the `inventory_movements` table for auditing purposes.

```php
// app/Services/Inventory/InventoryMovementLogger.php
class InventoryMovementLogger
{
    public function log(...)
    {
        // Inserts a new record into the `inventory_movements` table
    }
}
```

---

## 🧪 TESTING

-   **`OrderStatusServiceTest.php`**: Contains critical tests to ensure inventory hooks work as expected.
    -   `it_deducts_inventory_when_order_is_processing`: Verifies that `quantity_on_hand` is reduced and a `sale` movement is logged.
    -   `it_restores_inventory_when_processing_order_is_cancelled`: Verifies that `quantity_on_hand` is increased and an `adjustment` movement is logged.
-   **Concurrency Tests**: While true pessimistic locking isn't used in the CI4 Query Builder, transaction integrity is tested to prevent basic race conditions. More complex scenarios would require dedicated load testing.

---

## 📝 ACCEPTANCE CRITERIA

- [x]  `inventory_stock` table is created by the migration.
- [x]  Stock checking logic prevents orders from being created with insufficient stock.
- [x]  When an order status changes to `processing`, the `quantity_on_hand` for each item is correctly deducted.
- [x]  When a `processing` or `shipping` order is `cancelled`, the `quantity_on_hand` is restored.
- [x]  Every deduction and restoration is recorded in the `inventory_movements` table with the correct type (`sale` or `adjustment`).
- [x]  The implementation uses database transactions to ensure atomicity.
- [x]  All relevant tests are passing.
