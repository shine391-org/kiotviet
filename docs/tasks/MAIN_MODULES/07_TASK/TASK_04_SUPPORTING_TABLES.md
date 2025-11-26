---
title: "TASK 04: Supporting Tables Implementation (CodeIgniter 4)"
id: "TASK-04-SUPPORTING-TABLES-CI4"
priority: "P1 (High)"
estimated_effort: "1.5 days"
dependencies: "None"
status: "Done"
module: "Order Workflow"
type: "Implementation Task"
tags: ["task", "database", "schema", "logs", "inventory", "branches", "auditing", "tracking", "backend", "codeigniter"]
purpose: "Implement three critical supporting tables (order_status_logs, inventory_movements, branches) using CodeIgniter 4 to enable auditing, inventory tracking, and multi-branch support."
location: "docs/tasks/MAIN_MODULES/07_TASK"
related_to:
  - id: "SUPPORTING-TABLES-01"
    description: "Details the schema to be implemented."
  - id: "INVENTORY-EDGE-CASES-01"
    description: "Related to inventory tracking and management."
  - id: "ORDER-WORKFLOW-INDEX"
    description: "Task listed in the module index."
---

# TASK 04: Supporting Tables Implementation (CodeIgniter 4)

**Priority:** P1 (High)
**Estimated Effort:** 1.5 days
**Dependencies:** None
**Status:** Done

---

## 🎯 OBJECTIVE

Implement three critical supporting tables using **CodeIgniter 4 Migrations**: **`branches`**, **`order_status_logs`**, and **`inventory_movements`**. These tables form the foundation for auditing, multi-branch functionality, and accurate inventory tracking.

---

## 📋 REQUIREMENTS

### **Functional Requirements**

- [x] Create `branches` table to support multi-location operations.
- [x] Create `order_status_logs` table for a complete audit trail of order status changes.
- [x] Create `inventory_movements` table to track every stock change (sale, return, adjustment).
- [x] Automatically log every order status change.
- [x] Ensure all inventory adjustments are recorded as movements.

---

## 🗄️ DATABASE SCHEMA (CodeIgniter 4)

### **Migration: `2025-11-24-000010_CreateSupportingTables.php`**

This single migration file is responsible for creating all three tables.

```php
<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class CreateSupportingTables extends Migration
{
    public function up()
    {
        // branches table
        $this->forge->addField([...]);
        $this->forge->createTable('branches', true);

        // order_status_logs table
        $this->forge->addField([...]);
        $this->forge->createTable('order_status_logs', true);

        // inventory_movements table
        $this->forge->addField([...]);
        $this->forge->createTable('inventory_movements', true);
    }

    public function down()
    {
        $this->forge->dropTable('inventory_movements', true);
        $this->forge->dropTable('order_status_logs', true);
        $this->forge->dropTable('branches', true);
    }
}
```

### **1. `branches` Table Schema**

```php
$this->forge->addField([
    'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
    'code' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false, 'unique' => true],
    'name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
    'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
    // ... other fields and timestamps
]);
```

### **2. `order_status_logs` Table Schema**

```php
$this->forge->addField([
    'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
    'order_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
    'from_status' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
    'to_status' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
    'notes' => ['type' => 'TEXT', 'null' => true],
    'changed_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
    'changed_at' => ['type' => 'DATETIME', 'null' => true],
]);
```

### **3. `inventory_movements` Table Schema**

```php
$this->forge->addField([
    'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
    'branch_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
    'product_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
    'variant_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
    'type' => ['type' => 'ENUM', 'constraint' => ['sale', 'return', 'adjustment', 'transfer_in', 'transfer_out'], 'null' => false],
    'quantity' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
    'reference_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true], // e.g., 'order', 'return'
    'reference_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],   // e.g., order_id, return_id
    'created_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
]);
```

---

## 🏗️ ARCHITECTURE & IMPLEMENTATION (CodeIgniter 4)

### **Models**
-   **`BranchModel.php`**
-   **`OrderStatusLogModel.php`**
-   **`InventoryMovementModel.php`**

These are standard CodeIgniter models defining table names and allowed fields.

### **Automatic Status Logging**

Status change logging is handled automatically within the `OrderStatusService`. After a status is successfully updated, a log entry is created.

```php
// app/Services/Orders/OrderStatusService.php

public function updateStatus(int $orderId, string $toStatus, ...): array
{
    // ... (status transition logic)

    $this->orders->updateFields($orderId, $updates);

    // Log status change AFTER the update is successful
    $this->logs->create($orderId, $fromStatus, $toStatus, $userId, $notes);

    // ...
}
```

### **Inventory Movement Tracking**

A dedicated `InventoryMovementLogger` service is used to create movement records. This service is called whenever stock levels change (e.g., during order processing, cancellation, or returns).

```php
// app/Services/Inventory/InventoryMovementLogger.php

class InventoryMovementLogger
{
    public function log(
        int $branchId,
        int $productId,
        ?int $variantId,
        string $type,
        float $quantity,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ...
    ): void {
        // Creates a new record in the `inventory_movements` table
    }
}
```
This logger is then used within other services, such as `OrderStatusService`:
```php
// app/Services/Orders/OrderStatusService.php

private function deductInventory(array $order, ?int $userId): void
{
    // ... (stock deduction logic) ...

    $this->movementLogger->log(
        branchId: ...,
        productId: ...,
        type: 'sale',
        quantity: -($item['quantity']),
        referenceType: 'order',
        referenceId: $order['id'],
        ...
    );
}
```

---

## 🧪 TESTING

-   **`OrderStatusServiceTest.php`**: Includes tests to verify that a log entry is created in `order_status_logs` whenever an order's status changes.
-   **`InventoryMovementLoggerTest.php`**: Unit tests for the logger service itself.
-   **Integration Tests** for services like `ReturnService` and `OrderStatusService` confirm that the correct inventory movements are logged during complex workflows like returns and cancellations.

---

## 📝 ACCEPTANCE CRITERIA

- [x] All three tables (`branches`, `order_status_logs`, `inventory_movements`) are created by the migration.
- [x] Any change in an order's status is automatically recorded in `order_status_logs`.
- [x] Any change in inventory quantity (sale, return, manual adjustment) is recorded in `inventory_movements`.
- [x] The system is architected to support multi-branch inventory and operations.
- [x] All relevant unit and integration tests are passing.
