---
title: "Invoices Tables Schema"
id: "INVOICES-TABLES-01"
module: "Order Workflow"
last_updated: "2025-11-24"
type: "Database Schema"
tags: ["database", "schema", "invoices", "invoice_orders", "tables", "financial", "VAT"]
purpose: "Details the database schema for invoices and invoice_orders tables, including field definitions, indexes, and relationships."
location: "docs/tasks/MAIN_MODULES/03_DATABASE_SCHEMA"
related_to:
  - id: "INV-001"
    description: "Related Task for Invoices Tables implementation."
  - id: "BUSINESS-DECISIONS-01"
    description: "References Business Decisions #16-22."
  - id: "INVOICE-FLOW-01"
    description: "Describes the workflow that uses this schema."
  - id: "ORDERS-TABLE-01"
    description: "Related to orders table, as invoice_orders links to orders."
  - id: "SCHEMA-OVERVIEW-01"
    description: "Overall database schema overview."
---

# Invoices Tables Schema

**Module:** Order Workflow

**Related Task:** INV-001

**Last Updated:** 2025-11-24

---

## 🎯 MỤC ĐÍCH

Document này mô tả chi tiết schema cho **2 tables**:

1. **invoices** - Hóa đơn VAT
2. **invoice_orders** - Mapping invoices ↔ orders

---

## 📋 TABLE 1: invoices

### **Definition**

```sql
CREATE TABLE invoices (
  -- Primary Key
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  
  -- Identification
  invoice_number VARCHAR(50) UNIQUE NOT NULL,
  
  -- Relationships
  customer_id BIGINT UNSIGNED NOT NULL,
  branch_id BIGINT UNSIGNED NOT NULL,
  
  -- Dates
  issue_date DATE NOT NULL,
  due_date DATE,
  
  -- Financials
  subtotal DECIMAL(15,2) NOT NULL,
  vat_rate DECIMAL(5,4) NOT NULL,
  vat_amount DECIMAL(15,2) NOT NULL,
  total DECIMAL(15,2) NOT NULL,
  
  -- PDF
  pdf_path VARCHAR(255),
  
  -- Additional Info
  notes TEXT,
  
  -- Audit
  created_by BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Indexes
  INDEX idx_customer (customer_id, issue_date),
  INDEX idx_branch (branch_id),
  INDEX idx_invoice_number (invoice_number),
  INDEX idx_issue_date (issue_date),
  INDEX idx_due_date (due_date),
  
  -- Foreign Keys
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
  FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE RESTRICT,
  FOREIGN KEY (created_by) REFERENCES users(id),
  
  -- Constraints
  CONSTRAINT chk_vat_rate_valid CHECK (vat_rate >= 0 AND vat_rate <= 1),
  CONSTRAINT chk_total_valid CHECK (total >= subtotal)
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### **Field Descriptions**

### **Primary Key**

**id** `BIGINT UNSIGNED AUTO_INCREMENT`

- Primary key

---

### **Identification**

**invoice_number** `VARCHAR(50) UNIQUE NOT NULL`

- Mã hóa đơn
- Format: `HD-{branch_id}-{auto_increment}`
- Examples:
    - `HD-1-0001` (branch 1, invoice đầu tiên)
    - `HD-1-0002`
    - `HD-2-0001` (branch 2)
- UNIQUE constraint
- Thread-safe generation (use transaction lock)

---

### **Relationships**

**customer_id** `BIGINT UNSIGNED NOT NULL`

- FK to [`customers.id`](http://customers.id)
- NOT NULL (mọi invoice phải có customer)
- ON DELETE RESTRICT

**branch_id** `BIGINT UNSIGNED NOT NULL`

- FK to [`branches.id`](http://branches.id)
- NOT NULL
- ON DELETE RESTRICT
- Dùng để generate invoice_number

---

### **Dates**

**issue_date** `DATE NOT NULL`

- Ngày xuất hóa đơn
- Default: TODAY
- NOT NULL

**due_date** `DATE`

- Ngày đáo hạn
- Admin nhập thủ công
- NULL allowed (một số hóa đơn không có due date)
- Example:
    - 7 days: issue_date + 7
    - 30 days: issue_date + 30
    - 60 days: issue_date + 60

---

### **Financials**

**subtotal** `DECIMAL(15,2) NOT NULL`

- Tổng tiền hàng (chưa VAT)
- Formula: `SUM([order.total](http://order.total))` for all orders in invoice
- NOT NULL

**vat_rate** `DECIMAL(5,4) NOT NULL`

- Thuế suất VAT
- Decimal: 0.1000 = 10%
- Snapshot tại thời điểm tạo invoice
- NOT NULL
- CHECK: `vat_rate >= 0 AND vat_rate <= 1`

**vat_amount** `DECIMAL(15,2) NOT NULL`

- Tiền thuế VAT
- Formula: `subtotal * vat_rate`
- NOT NULL

**total** `DECIMAL(15,2) NOT NULL`

- Tổng cộng phải thanh toán
- Formula: `subtotal + vat_amount`
- NOT NULL
- CHECK: `total >= subtotal`

---

### **PDF**

**pdf_path** `VARCHAR(255)`

- Đường dẫn lưu file PDF
- Example: `invoices/2024/HD-1-0001.pdf`
- NULL nếu chưa generate
- Generate on-demand (khi user click "Tải PDF")

---

### **Additional Info**

**notes** `TEXT`

- Ghi chú hóa đơn
- Example: "Hóa đơn tháng 11/2024"
- NULL nếu không có

---

### **Audit**

**created_by** `BIGINT UNSIGNED NOT NULL`

- User ID của người tạo invoice
- FK to [`users.id`](http://users.id)
- NOT NULL

**created_at** `TIMESTAMP`

- Thời điểm tạo

**updated_at** `TIMESTAMP`

- Thời điểm update cuối

---

### **Indexes Explained**

**idx_customer (customer_id, issue_date)**

- Get invoices của customer, sort by date
- Query: `SELECT * FROM invoices WHERE customer_id = 123 ORDER BY issue_date DESC`

**idx_invoice_number (invoice_number)**

- Quick lookup by invoice number
- Query: `SELECT * FROM invoices WHERE invoice_number = 'HD-1-0001'`

**idx_due_date (due_date)**

- Filter by due date (overdue invoices)
- Query: `SELECT * FROM invoices WHERE due_date < CURDATE() AND status = 'unpaid'`

---

### **Sample Data**

**Single Order Invoice:**

```sql
INSERT INTO invoices (
  invoice_number,
  customer_id,
  branch_id,
  issue_date,
  due_date,
  subtotal,
  vat_rate,
  vat_amount,
  total,
  notes,
  created_by
) VALUES (
  'HD-1-0001',
  123,
  1,
  '2024-11-24',
  '2024-12-24',  -- 30 days
  1000000,
  0.10,  -- 10%
  100000,
  1100000,
  'Hóa đơn đơn hàng #ORD-001234',
  5
);
```

**Multiple Orders Invoice (Gộp tháng):**

```sql
INSERT INTO invoices (
  invoice_number,
  customer_id,
  branch_id,
  issue_date,
  due_date,
  subtotal,
  vat_rate,
  vat_amount,
  total,
  notes,
  created_by
) VALUES (
  'HD-1-0002',
  456,
  1,
  '2024-11-30',
  '2024-12-30',
  4500000,  -- gộp 5 orders
  0.10,
  450000,
  4950000,
  'Hóa đơn tháng 11/2024 - Gộp 5 đơn hàng',
  5
);
```

---

## 📋 TABLE 2: invoice_orders

### **Definition**

```sql
CREATE TABLE invoice_orders (
  -- Primary Key
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  
  -- Relationships
  invoice_id BIGINT UNSIGNED NOT NULL,
  order_id BIGINT UNSIGNED NOT NULL,
  
  -- Audit
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  -- Indexes
  INDEX idx_invoice (invoice_id),
  INDEX idx_order (order_id),
  
  -- Unique Constraint
  UNIQUE KEY unique_invoice_order (invoice_id, order_id),
  
  -- Foreign Keys
  FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE RESTRICT
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### **Field Descriptions**

**id** `BIGINT UNSIGNED AUTO_INCREMENT`

- Primary key

**invoice_id** `BIGINT UNSIGNED NOT NULL`

- FK to [`invoices.id`](http://invoices.id)
- NOT NULL
- ON DELETE CASCADE (nếu xóa invoice → xóa luôn mappings)

**order_id** `BIGINT UNSIGNED NOT NULL`

- FK to [`orders.id`](http://orders.id)
- NOT NULL
- ON DELETE RESTRICT (không xóa order nếu còn invoice)

**created_at** `TIMESTAMP`

- Thời điểm liên kết

---

### **Constraints**

**UNIQUE (invoice_id, order_id)**

- Đảm bảo 1 order không nằm trong 1 invoice 2 lần
- Prevent duplicate mappings

---

### **Sample Data**

```sql
-- Invoice #1 has 1 order
INSERT INTO invoice_orders (invoice_id, order_id) VALUES (1, 123);

-- Invoice #2 has 5 orders (gộp tháng)
INSERT INTO invoice_orders (invoice_id, order_id) VALUES
(2, 456),
(2, 457),
(2, 458),
(2, 459),
(2, 460);
```

---

## 🔗 RELATIONSHIPS

### **Many-to-Many Relationship**

```
invoices (1) ───── (N) invoice_orders (N) ───── (1) orders
```

**Why N:N?**

- 1 invoice có thể gộp nhiều orders
- 1 order (hiếm khi) có thể nằm trong nhiều invoices

**Example:**

- Invoice HD-1-0001: chứa order #123
- Invoice HD-1-0002: chứa orders #456, #457, #458, #459, #460

---

## 📊 COMMON QUERIES

### **Get orders in an invoice**

```sql
SELECT 
  o.*
FROM orders o
JOIN invoice_orders io ON io.order_id = [o.id](http://o.id)
WHERE io.invoice_id = 1;
```

---

### **Get invoice for an order**

```sql
SELECT 
  i.*
FROM invoices i
JOIN invoice_orders io ON io.invoice_id = [i.id](http://i.id)
WHERE io.order_id = 123;
```

---

### **Get full invoice with orders and items**

```sql
SELECT 
  i.invoice_number,
  [i.total](http://i.total) as invoice_total,
  o.order_number,
  [o.total](http://o.total) as order_total,
  oi.product_name,
  oi.quantity,
  oi.price
FROM invoices i
JOIN invoice_orders io ON io.invoice_id = [i.id](http://i.id)
JOIN orders o ON [o.id](http://o.id) = io.order_id
JOIN order_items oi ON oi.order_id = [o.id](http://o.id)
WHERE [i.id](http://i.id) = 1;
```

---

### **Customer invoices**

```sql
SELECT 
  i.*,
  COUNT(io.order_id) as order_count
FROM invoices i
LEFT JOIN invoice_orders io ON io.invoice_id = [i.id](http://i.id)
WHERE i.customer_id = 123
GROUP BY [i.id](http://i.id)
ORDER BY i.issue_date DESC;
```

---

### **Overdue invoices**

```sql
SELECT 
  i.*,
  [c.name](http://c.name) as customer_name,
  DATEDIFF(CURDATE(), i.due_date) as days_overdue
FROM invoices i
JOIN customers c ON [c.id](http://c.id) = i.customer_id
WHERE i.due_date < CURDATE()
  AND i.status = 'unpaid'
ORDER BY days_overdue DESC;
```

---

## 🔢 INVOICE NUMBER GENERATION

### **Algorithm**

```php
function generateInvoiceNumber($branchId) {
    DB::beginTransaction();
    
    try {
        // Lock table to prevent race condition
        $counter = Invoice::where('branch_id', $branchId)
            ->lockForUpdate()
            ->count() + 1;
        
        // Format: HD-{branch}-{counter with padding}
        $invoiceNumber = sprintf("HD-%d-%04d", $branchId, $counter);
        
        // Create invoice
        $invoice = Invoice::create([
            'invoice_number' => $invoiceNumber,
            'branch_id' => $branchId,
            // ... other fields
        ]);
        
        DB::commit();
        
        return $invoiceNumber;
        
    } catch (Exception $e) {
        DB::rollback();
        throw $e;
    }
}
```

**Thread-safe:**

- Use transaction
- Use `lockForUpdate()` to lock rows
- Commit only if successful

---

## 📝 PDF GENERATION

### **On-Demand Strategy**

```php
function generateInvoicePDF($invoiceId) {
    $invoice = Invoice::with('orders.items')->find($invoiceId);
    
    // Check if PDF already generated
    if ($invoice->pdf_path && Storage::exists($invoice->pdf_path)) {
        return Storage::path($invoice->pdf_path);
    }
    
    // Generate PDF
    $pdf = PDF::loadView('invoices.template', [
        'invoice' => $invoice,
        'customer' => $invoice->customer,
        'orders' => $invoice->orders,
    ]);
    
    // Save to storage
    $filename = "HD-{$invoice->invoice_number}.pdf";
    $path = "invoices/" . date('Y') . "/" . $filename;
    
    Storage::put($path, $pdf->output());
    
    // Update invoice
    $invoice->update(['pdf_path' => $path]);
    
    return Storage::path($path);
}
```

**Benefits:**

- Save storage (chỉ generate khi cần)
- Faster invoice creation
- Cache for reuse

---

## 📊 ANALYTICS

### **Invoice Summary by Month**

```sql
SELECT 
  DATE_FORMAT(issue_date, '%Y-%m') as month,
  COUNT(*) as invoice_count,
  SUM(total) as total_amount,
  AVG(total) as avg_invoice_value
FROM invoices
WHERE issue_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
GROUP BY DATE_FORMAT(issue_date, '%Y-%m')
ORDER BY month DESC;
```

---

### **Customer Invoice Summary**

```sql
SELECT 
  [c.name](http://c.name),
  [c.tax](http://c.tax)_code,
  COUNT([i.id](http://i.id)) as invoice_count,
  SUM([i.total](http://i.total)) as total_amount,
  MAX(i.issue_date) as last_invoice_date
FROM customers c
JOIN invoices i ON i.customer_id = [c.id](http://c.id)
GROUP BY [c.id](http://c.id)
ORDER BY total_amount DESC
LIMIT 20;
```

---

## ⚠️ IMPORTANT NOTES

### **VAT Rate Immutability**

- VAT rate là snapshot tại thời điểm tạo invoice
- Nếu chính phủ thay đổi VAT rate, invoices cũ GIỮ NGUYÊN

**Example:**

```
Tháng 1: VAT = 10% → Invoice #1 lưu vat_rate = 0.10
Tháng 6: Chính phủ đổi VAT = 8%
Tháng 7: Tạo invoice mới
   → Invoice #2 lưu vat_rate = 0.08

Invoice #1 vẫn giữ 0.10 (immutability)
```

---

### **Customer Must Have tax_code**

```php
if (empty($customer->tax_code)) {
    throw new ValidationException('Customer must have tax code to generate invoice');
}
```

---

### **Orders Must Be COMPLETED**

```php
foreach ($orderIds as $orderId) {
    $order = Order::find($orderId);
    if ($order->status !== 'completed') {
        throw new ValidationException("Order #{$orderId} is not completed");
    }
}
```

---

## 🔗 RELATED DOCUMENTS

- [**INVOICE_](https://www.notion.so/INVOICE_FLOW-Invoice-Generation-Workflow-f870e3cf753e4c1abc41b11c6fe4748d?pvs=21)[FLOW.md](http://FLOW.md)** - Invoice workflow
- [**ORDERS_](https://www.notion.so/ORDERS_TABLE-Orders-Table-Schema-36b32ddd5ce6421abd19d92589270881?pvs=21)[TABLE.md](http://TABLE.md)** - Orders schema
- [**SCHEMA_](https://www.notion.so/SCHEMA_OVERVIEW-Database-Schema-Overview-399a75e39df64c8c8a343bd9c6bbe0a2?pvs=21)[OVERVIEW.md](http://OVERVIEW.md)** - Full schema
- **Business Decisions:** #16-22