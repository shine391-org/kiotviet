---
title: "TASK 02: Invoices Schema Implementation (CodeIgniter 4)"
id: "INV-001-CI4"
priority: "P1 (High)"
estimated_effort: "2 days"
dependencies: "PAY-001-CI4"
status: "Done"
module: "Order Workflow"
type: "Implementation Task"
tags: ["task", "invoices", "database", "schema", "junction-table", "PDF", "VAT", "backend", "codeigniter"]
purpose: "Implement the 'invoices' and 'invoice_orders' tables using CodeIgniter 4, including number generation, VAT calculation, and on-demand PDF generation."
location: "docs/tasks/MAIN_MODULES/07_TASK"
related_to:
  - id: "INVOICES-TABLES-01"
    description: "Details the schema to be implemented."
  - id: "INVOICE-RULES-01"
    description: "Defines the validation rules to be implemented."
  - id: "INVOICE-FLOW-01"
    description: "Describes the workflow for invoice generation."
  - id: "ORDER-WORKFLOW-INDEX"
    description: "Task listed in the module index."
  - id: "PAY-001-CI4"
    description: "Dependency: Payment Methods implementation."
---

# TASK_02: Invoices Schema Implementation (CodeIgniter 4)

**Priority:** P1 (High)
**Estimated Effort:** 2 days
**Dependencies:** TASK 01 (Payment Methods)
**Status:** Done

---

## 🎯 OBJECTIVE

Implement the **`invoices`** and **`invoice_orders`** tables using **CodeIgniter 4**, establishing a many-to-many relationship between invoices and orders.

---

## 📋 REQUIREMENTS

### **Functional Requirements**

- [x] Create `invoices` table.
- [x] Create `invoice_orders` junction table.
- [x] Implement invoice number generation (`HD-{branch_id}-{counter}`).
- [x] Support linking multiple orders to a single invoice.
- [x] Automatically calculate VAT based on a given rate.
- [x] Generate invoice PDFs on-demand.

### **Non-Functional Requirements**

- [x] Ensure invoice numbers are unique.
- [x] Use database transactions for data integrity.
- [x] Aim for PDF generation time under 3 seconds.

---

## 🗄️ DATABASE SCHEMA (CodeIgniter 4)

### **Migration: `2025-11-24-000008_CreateInvoiceTables.php`**

This migration creates both the `invoices` and `invoice_orders` tables.

```php
<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class CreateInvoiceTables extends Migration
{
    public function up()
    {
        // `invoices` table
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'invoice_number' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'customer_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'branch_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'issue_date' => ['type' => 'DATE', 'null' => false],
            'due_date' => ['type' => 'DATE', 'null' => true],
            'subtotal' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false],
            'vat_rate' => ['type' => 'DECIMAL', 'constraint' => '5,4', 'null' => false],
            'vat_amount' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false],
            'total' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false],
            'pdf_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'meta' => ['type' => 'JSON', 'null' => true],
            'created_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('invoice_number');
        $this->forge->addKey('customer_id');
        $this->forge->createTable('invoices', true);

        // `invoice_orders` junction table
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'invoice_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'order_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['invoice_id', 'order_id']);
        $this->forge->addKey('order_id');
        $this->forge->createTable('invoice_orders', true);
    }

    public function down()
    {
        $this->forge->dropTable('invoice_orders', true);
        $this->forge->dropTable('invoices', true);
    }
}
```

---

## 🏗️ ARCHITECTURE (CodeIgniter 4)

### **Models**

-   **`InvoiceModel.php`**: Defines the schema for the `invoices` table.
-   **`InvoiceOrderModel.php`**: Defines the schema for the `invoice_orders` junction table.

```php
// app/Models/InvoiceModel.php
<?php
namespace App\Models;
use CodeIgniter\Model;

class InvoiceModel extends Model
{
    protected $table = 'invoices';
    protected $allowedFields = [
        'invoice_number', 'customer_id', 'branch_id', 'issue_date', 
        'due_date', 'subtotal', 'vat_rate', 'vat_amount', 'total', 
        'pdf_path', 'notes', 'meta', 'created_by'
    ];
}
```

### **Repository: `InvoiceRepository.php`**

Handles complex queries, such as generating the next invoice number within a transaction to prevent race conditions.

```php
// Example: Generate the next invoice number
public function nextNumber(int $branchId): string
{
    return $this->db->transAction(function () use ($branchId) {
        $prefix = "HD-{$branchId}-";
        $builder = $this->model->builder()
            ->select('invoice_number')
            ->like('invoice_number', $prefix, 'after')
            ->orderBy('invoice_number', 'DESC')
            ->limit(1)
            ->lockForUpdate(); // Pessimistic locking

        $last = $builder->get()->getRowArray();
        
        $counter = $last ? (int)str_replace($prefix, '', $last['invoice_number']) + 1 : 1;
        return $prefix . str_pad($counter, 4, '0', STR_PAD_LEFT);
    });
}
```

### **Service: `InvoiceService.php`**

Orchestrates the logic for creating invoices from orders, calculating totals, and dispatching events.

```php
// Example: Creating an invoice from orders
public function generateFromOrders(array $payload): array
{
    // 1. Validate payload (order_ids, branch_id, etc.)
    // 2. Fetch orders and perform business rule checks
    // 3. Check if orders are already invoiced
    // 4. Calculate subtotal and VAT
    // 5. Generate a new invoice number using the repository
    // 6. Create the invoice and links in a transaction
    // 7. Dispatch 'invoice.generated' event
    // 8. Return transformed data
}
```

---

## 🧪 TESTING (CodeIgniter 4)

### **Service Test: `tests/Services/InvoiceServiceTest.php`**

Tests the business logic for invoice creation, validation, and calculations in isolation.

```php
<?php
class InvoiceServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;

    public function test_it_generates_invoice_from_multiple_orders()
    {
        // Setup: Create completed orders for the same customer and branch
        // Execute: Call $invoiceService->generateFromOrders(...)
        // Assert: Check that `invoices` and `invoice_orders` tables are populated correctly
    }
}
```

### **Integration Test: `tests/Integration/Invoices/MultiOrderInvoiceIntegrationTest.php`**

Tests the `POST /api/invoices/generate` endpoint to ensure the entire flow works correctly.

```php
<?php
class MultiOrderInvoiceIntegrationTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function test_generate_invoice_for_multiple_orders_success()
    {
        // Setup: Create orders in the database
        // Execute: Call the API endpoint
        $response = $this->post('api/invoices/generate', [...]);
        // Assert: Check for 201 status and correct response payload
    }
}
```

---

## 📝 ACCEPTANCE CRITERIA

- [x] `invoices` and `invoice_orders` tables created via CodeIgniter Migration.
- [x] Invoice number generation is thread-safe and follows the `HD-{branch}-{counter}` format.
- [x] VAT calculation is accurate to 2 decimal places.
- [x] PDF generation service is implemented (though PDF content is out of scope for this task).
- [x] Can link multiple completed orders to one invoice.
- [x] Business logic prevents linking already-invoiced orders.
- [x] All unit and integration tests are passing.
