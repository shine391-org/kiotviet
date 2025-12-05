---
title: "TASK 05: Order Create Implementation (CodeIgniter 4)"
id: "TASK-05-ORDER-CREATE-CI4"
priority: "P0 (Blocker)"
estimated_effort: "3 days"
dependencies: "PAY-001-CI4, TASK-04-SUPPORTING-TABLES-CI4"
status: "Done"
module: "Order Workflow"
type: "Implementation Task"
tags: ["task", "orders", "create", "POS", "SHIPPING", "validation", "inventory", "API", "backend", "codeigniter"]
purpose: "Implement the full order creation logic in CodeIgniter 4, including order number generation, financial calculations, stock validation, and initial status management for both POS and Shipping order types."
location: "docs/tasks/MAIN_MODULES/07_TASK"
---

# TASK 05: Order Create Implementation (CodeIgniter 4)

**Priority:** P0 (Blocker)
**Estimated Effort:** 3 days
**Dependencies:** TASK 01, TASK 04
**Status:** Done

---

## 🎯 OBJECTIVE

Implement the complete order creation logic for both **POS (Point of Sale)** and **SHIPPING** orders using **CodeIgniter 4**, including validation, pricing, and persistence.

---

## 📋 REQUIREMENTS

### **Functional Requirements**

- [x] Create `orders` and `order_items` tables via Migrations.
- [x] Generate a unique, sequential order number (`ORD-000001`).
- [x] Support two order types: `pos` and `shipping`.
- [x] Apply pricing rules via `PriceCalculatorService`.
- [x] Validate input payload, including customer data and items.
- [x] Validate stock availability before creation.
- [x] Immediately deduct inventory for `pos` orders.
- [x] Differentiate initial status: `completed` for `pos`, `draft` for `shipping`.

### **Non-Functional Requirements**

- [x] Use database transactions to ensure atomicity.
- [x] Order number generation must be thread-safe (using `lockForUpdate`).
- [x] API response time for order creation should be < 500ms.

---

## 🗄️ DATABASE SCHEMA (CodeIgniter 4)

### **Migration: `2025-11-23-000005_CreateOrderTables.php`**

This migration sets up the initial `orders` and `order_items` tables. Note that subsequent migrations (like `2025-11-24-000011_UpdateOrdersForCreate.php`) add more fields.

```php
<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class CreateOrderTables extends Migration
{
    public function up()
    {
        // `orders` table
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'customer_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'draft'],
            'total' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            // ...other fields like subtotal, discount_total, timestamps
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('orders', true);

        // `order_items` table
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'order_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'quantity' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 1],
            'base_price' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'final_price' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('order_id', 'orders', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('order_items', true);
    }

    public function down()
    {
        $this->forge->dropTable('order_items', true);
        $this->forge->dropTable('orders', true);
    }
}
```

---

## 🏗️ ARCHITECTURE (CodeIgniter 4)

### **Validator: `OrderCreateValidator.php`**
The first line of defense. It sanitizes and validates the raw input from the API request, ensuring all required fields are present and correctly formatted before any business logic is executed.

```php
// app/Validators/OrderCreateValidator.php
class OrderCreateValidator
{
    public function validate(array $input): array
    {
        if (empty($input['items'])) {
            throw new InvalidArgumentException('items is required');
        }
        // ... more validation rules for branch, customer, etc.
        return [...]; // Returns a sanitized and structured array
    }
}
```

### **Service: `OrderService.php`**
This is the core orchestrator for creating an order. It uses other services and repositories to perform its tasks.

```php
// app/Services/Orders/OrderService.php
class OrderService
{
    public function create(array $payload): array
    {
        // 1. Validate payload using OrderCreateValidator
        $validated = $this->createValidator->validate($payload);

        // 2. Calculate final prices using PriceCalculatorService
        $preview = $this->preview($validated);
        $data = $preview['data'];

        // 3. Generate a unique order number
        $orderNumber = $this->numberGen->generate();

        // 4. Prepare the final order and item payloads
        $orderPayload = [...];
        $itemRows = [...];

        // 5. Create the order and items in a database transaction
        $order = $this->orders->create($orderPayload, $itemRows);

        // 6. Handle side-effects for POS orders (inventory deduction, logging)
        if ($validated['order_type'] === 'pos') {
            $this->deductPosInventory($order, ...);
            $this->logPosStatus($order);
        }

        // 7. Dispatch 'order.created' event
        $this->emit('order.created', $order);
        
        return ['success' => true, 'data' => $order];
    }
}
```

### **Repository: `OrderRepository.php`**
Handles the direct database interaction for creating the `orders` and `order_items` records within a transaction.

```php
// app/Repositories/Orders/OrderRepository.php
public function create(array $orderData, array $itemsData): array
{
    $this->db->transStart();
    
    // Insert into `orders` table
    $this->db->table('orders')->insert($orderData);
    $orderId = $this->db->insertID();

    // Batch insert into `order_items` table
    $items = array_map(fn($item) => ['order_id' => $orderId] + $item, $itemsData);
    $this->db->table('order_items')->insertBatch($items);
    
    $this->db->transComplete();

    // ... return created order data
}
```
---

## 🧪 TESTING

-   **`OrderCreateValidatorTest.php`**: Ensures the validation logic correctly accepts valid payloads and rejects invalid ones.
-   **`OrderServiceTest.php`**: Mocks dependencies like the repository and pricing service to test the orchestration logic of the `create` method in isolation.
-   **Integration Tests**:
    -   `tests/Integration/Orders/OrderCreationTest.php`: Tests the full order creation flow via the API.
    -   `tests/Integration/Orders/OrderPriceListTest.php`: Specifically tests that pricing rules are correctly applied during order creation.

## 📝 ACCEPTANCE CRITERIA

- [x] API endpoint `POST /api/orders` successfully creates both `pos` and `shipping` orders.
- [x] Order numbers are generated uniquely and sequentially.
- [x] `orders` and `order_items` records are created correctly in a single transaction.
- [x] Prices are correctly calculated by `PriceCalculatorService`.
- [x] Input validation is strictly enforced.
- [x] Stock availability is checked before order creation.
- [x] `pos` orders are created with `completed` status, and inventory is deducted immediately.
- [x] `shipping` orders are created with `draft` status.
- [x] All related tests pass successfully.