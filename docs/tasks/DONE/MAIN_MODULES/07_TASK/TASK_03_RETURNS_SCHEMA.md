---
title: "TASK 03: Returns Schema Implementation (CodeIgniter 4)"
id: "RETURN-001-CI4"
priority: "P1 (High)"
estimated_effort: "2 days"
dependencies: "TASK-05-ORDER-CREATE-01"
status: "Done"
module: "Order Workflow"
type: "Implementation Task"
tags: ["task", "returns", "database", "schema", "workflow", "refund", "backend", "codeigniter"]
purpose: "Implement the 'returns' and 'return_items' tables using CodeIgniter 4, including number generation, partial returns, approval workflow, and refund calculation."
location: "docs/tasks/MAIN_MODULES/07_TASK"
related_to:
  - id: "RETURNS-TABLES-01"
    description: "Details the schema to be implemented."
  - id: "RETURN-RULES-01"
    description: "Defines the validation rules to be implemented."
  - id: "RETURN-FLOW-01"
    description: "Describes the workflow for returns."
  - id: "ORDER-WORKFLOW-INDEX"
    description: "Task listed in the module index."
  - id: "TASK-05-ORDER-CREATE-01"
    description: "Dependency: Order Create implementation."
---

# TASK 03: Returns Schema Implementation (CodeIgniter 4)

**Priority:** P1 (High)
**Estimated Effort:** 2 days
**Dependencies:** TASK_05 (Order Create)
**Status:** Done

---

## 🎯 OBJECTIVE

Implement the **`returns`** and **`return_items`** tables to manage the entire return workflow, from request to completion, using **CodeIgniter 4**.

---

## 📋 REQUIREMENTS

### **Functional Requirements**

- [x] Create `returns` table for the main return request.
- [x] Create `return_items` table to track individual items being returned.
- [x] Automatically generate a unique return number (e.g., `TH-{order_id}-{counter}`).
- [x] Support partial returns (returning only some items from an order).
- [x] Implement a status-based approval workflow (`pending` → `approved` → `completed`).
- [x] Calculate refund amounts dynamically.
- [x] Track the condition of returned items (`new`, `used`, `damaged`).

### **Non-Functional Requirements**

- [x] Ensure return numbers are unique per order.
- [x] Use optimistic locking (`lock_version`) to prevent race conditions during status updates.
- [x] Ensure all database operations are wrapped in transactions for data integrity.

---

## 🗄️ DATABASE SCHEMA (CodeIgniter 4)

### **Migration: `2025-11-24-000009_CreateReturnTables.php`**

```php
<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class CreateReturnTables extends Migration
{
    public function up()
    {
        // `returns` table
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'return_number' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'order_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'customer_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'return_amount' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'refund_shipping_fee' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'refund_amount' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true],
            'refund_method' => ['type' => 'ENUM', 'constraint' => ['cash', 'bank_transfer'], 'null' => true],
            'reason' => ['type' => 'ENUM', 'constraint' => ['defective', 'wrong_item', 'not_satisfied', 'other'], 'null' => false],
            'reason_detail' => ['type' => 'TEXT', 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['pending', 'approved', 'rejected', 'completed'], 'default' => 'pending'],
            'lock_version' => ['type' => 'INT', 'constraint' => 10, 'default' => 0],
            'created_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            // ... timestamps and other audit fields
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('return_number');
        $this->forge->createTable('returns', true);

        // `return_items` table
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'return_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'order_item_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'quantity_returned' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'null' => false],
            'item_condition' => ['type' => 'ENUM', 'constraint' => ['new', 'used', 'damaged'], 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('return_id');
        $this->forge->createTable('return_items', true);
    }

    public function down()
    {
        $this->forge->dropTable('return_items', true);
        $this->forge->dropTable('returns', true);
    }
}
```

---

## 🏗️ ARCHITECTURE (CodeIgniter 4)

### **Models**
-   **`ReturnModel.php`**: Defines the schema for the `returns` table.
-   **`ReturnItemModel.php`**: Defines the schema for the `return_items` table.

### **Repository: `ReturnRepository.php`**
Manages data access, including generating the next return number for an order.

```php
// Example: Generate the next return number
public function nextNumber(int $orderId): string
{
    return $this->db->transAction(function () use ($orderId) {
        $count = $this->model->builder()
            ->where('order_id', $orderId)
            ->countAllResults();
        return "TH-{$orderId}-" . ($count + 1);
    });
}
```

### **Service: `ReturnService.php`**
Orchestrates the entire return process, from creation to completion.

```php
// Example: Create a new return request
public function create(array $payload): array
{
    // 1. Validate payload using ReturnValidator
    // 2. Fetch the order and perform business rule checks (is completed? within window?)
    // 3. Build return items and calculate return amount
    // 4. Generate a new return number using the repository
    // 5. Create the `returns` and `return_items` records in a transaction
    // 6. Dispatch 'return.requested' event
    // 7. Return transformed data
}

// Example: Approve a return
public function approve(int $id, array $payload): array
{
    // 1. Validate payload and check current status
    // 2. Recalculate refund_amount based on shipping fee decision
    // 3. Transition status to 'approved' using optimistic locking (version)
    // 4. Dispatch 'return.approved' event
}
```

---

## 🧪 TESTING (CodeIgniter 4)

### **Service Test: `tests/Services/ReturnServiceTest.php`**
Tests the business logic of the return workflow.

```php
<?php
class ReturnServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;

    public function test_it_creates_return_request_successfully()
    {
        // Setup: Create a completed order
        // Execute: Call $returnService->create(...)
        // Assert: Verify `returns` and `return_items` tables have the correct data
    }

    public function test_it_prevents_returning_more_than_purchased()
    {
        // Setup: Create an order with 2 items
        // Execute: Attempt to return 3 items via $returnService->create(...)
        // Assert: Expect an InvalidArgumentException
    }
}
```

### **Integration Test: `tests/Integration/Returns/ReturnsApiTest.php`**
Tests the API endpoints for creating and managing returns.

```php
<?php
class ReturnsApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function test_customer_can_create_return_request()
    {
        // Setup: Create a user and a completed order
        // Execute: POST to /api/returns with valid data
        $response = $this->post('api/returns', [...]);
        // Assert: Check for 201 status and correct JSON response
    }
}
```

---

## 📝 ACCEPTANCE CRITERIA

- [x]  `returns` and `return_items` tables are created correctly.
- [x]  Return number is generated uniquely per order (`TH-{order_id}-{counter}`).
- [x]  Can create a return request for one or more items from a completed order.
- [x]  Validation prevents returning more items than were purchased.
- [x]  Validation prevents creating returns for orders that are not `completed`.
- [x]  Refund amount is calculated correctly based on returned items.
- [x]  The approval workflow (`pending` -> `approved` -> `completed`) is correctly implemented.
- [x]  All unit and integration tests pass.
