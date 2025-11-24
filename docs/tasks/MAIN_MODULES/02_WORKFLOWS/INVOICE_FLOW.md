---
title: "Invoice Generation Workflow"
id: "INVOICE-FLOW-01"
module: "Order Workflow"
last_updated: "2025-11-24"
type: "Workflow Document"
tags: ["workflow", "invoice", "VAT", "generation", "pdf", "order-workflow"]
purpose: "Describes the detailed workflow for Invoice Generation, including prerequisites, steps, business rules, schema, and scenarios."
location: "docs/tasks/MAIN_MODULES/02_WORKFLOWS"
related_to:
  - id: "INV-001"
    description: "Related Task for Invoice Tables implementation."
  - id: "BUSINESS-DECISIONS-01"
    description: "References Business Decisions #16-22."
  - id: "ORDER-WORKFLOW-INDEX"
    description: "Referenced by the main Order Workflow Index."
---

# INVOICE_FLOW - Invoice Generation Workflow

# Invoice Generation Workflow

**Module:** Order Workflow

**Related Task:** INV-001

**Last Updated:** 2025-11-24

---

## 🎯 MỤC ĐÍCH

Document này mô tả chi tiết workflow cho **Invoice Generation** - tạo hóa đơn VAT.

**Đặc điểm:** On-demand generation, gộp nhiều orders, VAT configurable, PDF generation.

---

## 📊 WORKFLOW OVERVIEW

```
┌──────────────┐
│ Orders       │
│ COMPLETED    │
└──────┬───────┘
       │
       ↓
┌──────────────────┐
│ Admin/Customer   │
│ Request Invoice  │ ← Chỉ khi có tax_code
└──────┬───────────┘
       │
       ↓
┌──────────────────┐
│ Select Orders    │ ← Có thể gộp nhiều orders
│ to Invoice       │
└──────┬───────────┘
       │
       ↓
┌──────────────────┐
│ Generate         │
│ Invoice Number   │ ← HD-{branch}-{num}
└──────┬───────────┘
       │
       ↓
┌──────────────────┐
│ Calculate        │
│ Totals + VAT     │ ← subtotal, vat, total
└──────┬───────────┘
       │
       ↓
┌──────────────────┐
│ Save Invoice     │
│ + Mappings       │ ← invoices + invoice_orders
└──────┬───────────┘
       │
       ↓
┌──────────────────┐
│ Generate PDF     │ ← On-demand
│ (Optional)       │
└──────────────────┘
```

**Timeline:** Instant (on-demand)

---

## 📋 PREREQUISITES

### 1. Customer Must Have tax_code

```sql
[customers.tax](http://customers.tax)_code VARCHAR(50)  -- Mã số thuế
```

**Validation:**

```php
if (empty($customer->tax_code)) {
    throw new Exception("Customer must have tax code to generate invoice");
}
```

### 2. Orders Must Be COMPLETED

**Validation:**

```php
foreach ($orderIds as $orderId) {
    $order = Order::find($orderId);
    if ($order->status !== 'completed') {
        throw new Exception("Order #{$orderId} is not completed");
    }
}
```

### 3. All Orders Same Customer

**Validation:**

```php
$customerIds = Order::whereIn('id', $orderIds)
    ->pluck('customer_id')
    ->unique();

if ($customerIds->count() > 1) {
    throw new Exception("All orders must belong to same customer");
}
```

---

## 🔄 COMPLETE FLOW

### Step 1: Request Invoice Generation

**Endpoint:** `POST /api/invoices`

**Input:**

```json
{
  "customer_id": 123,
  "order_ids": [456, 457, 458],
  "due_date": "2024-12-31",
  "notes": "Hóa đơn tháng 11"
}
```

**Validation:**

1. Customer has tax_code
2. All orders COMPLETED
3. All orders belong to customer_id
4. due_date >= today (optional)

---

### Step 2: Generate Invoice Number

**Format:** `HD-{branch_id}-{auto_increment}`

**Logic:**

```php
// Get branch from first order
$order = Order::find($orderIds[0]);
$branchId = $order->branch_id;

// Get counter for this branch
$counter = Invoice::where('branch_id', $branchId)
    ->count() + 1;

// Format
$invoiceNumber = sprintf("HD-%d-%04d", $branchId, $counter);
// Example: HD-1-0001, HD-1-0002, HD-2-0001
```

**Thread-safe:**

```php
// Option A: Lock table
DB::beginTransaction();
$counter = Invoice::where('branch_id', $branchId)
    ->lockForUpdate()
    ->count() + 1;
$invoiceNumber = sprintf("HD-%d-%04d", $branchId, $counter);
// ... insert invoice
DB::commit();

// Option B: Unique constraint + retry
try {
    Invoice::create(['invoice_number' => $invoiceNumber, ...]);
} catch (UniqueConstraintException $e) {
    // Retry với số tiếp theo
}
```

---

### Step 3: Calculate Totals

**Get VAT Rate:**

```php
// From settings table
$vatRate = Setting::where('key', 'tax.vat_rate')
    ->value('value'); // '0.10' = 10%
```

**Calculate:**

```php
// Sum all orders
$subtotal = 0;
foreach ($orderIds as $orderId) {
    $order = Order::find($orderId);
    $subtotal += $order->total;
}

// Calculate VAT
$vatAmount = $subtotal * $vatRate;

// Grand total
$total = $subtotal + $vatAmount;
```

**Example:**

```
Order 1: 1,000,000
Order 2: 2,000,000
Order 3: 1,500,000

Subtotal: 4,500,000
VAT (10%): 450,000
Total: 4,950,000
```

---

### Step 4: Save Invoice

**Insert invoice:**

```php
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
    pdf_path,
    notes,
    created_by
) VALUES (
    'HD-1-0001',
    123,
    1,
    NOW(),
    '2024-12-31',
    4500000,
    0.10,
    450000,
    4950000,
    'Hóa đơn tháng 11',
    $userId
);
```

**Insert mappings:**

```php
foreach ($orderIds as $orderId) {
    INSERT INTO invoice_orders (
        invoice_id,
        order_id
    ) VALUES (
        $invoiceId,
        $orderId
    );
}
```

**Response:**

```json
{
  "invoice_id": 789,
  "invoice_number": "HD-1-0001",
  "customer_id": 123,
  "subtotal": 4500000,
  "vat_amount": 450000,
  "total": 4950000,
  "issue_date": "2024-11-24",
  "due_date": "2024-12-31",
  "pdf_path": null
}
```

---

### Step 5: Generate PDF (On-Demand)

**Endpoint:** `GET /api/invoices/:id/pdf`

**Logic:**

```php
// Check if PDF already generated
if (!empty($invoice->pdf_path) && file_exists($invoice->pdf_path)) {
    // Serve cached PDF
    return response()->file($invoice->pdf_path);
}

// Generate new PDF
$pdf = PDF::loadView('invoices.template', [
    'invoice' => $invoice,
    'customer' => $customer,
    'orders' => $orders,
    'company' => $company
]);

// Save to storage
$filename = "HD-{$invoice->invoice_number}.pdf";
$path = storage_path("invoices/2024/{$filename}");
$pdf->save($path);

// Update invoice
Update invoices SET pdf_path = ? WHERE id = ?;

// Return PDF
return response()->file($path);
```

**Template Example:**

```html
<!DOCTYPE html>
<html>
<head>
    <title>Invoice  $invoice->invoice_number </title>
    <style>
        /* Invoice styling */
    </style>
</head>
</head>
<body>
    <h1>HÓA ĐƠN GIÁ TRỊ GIA TĂNG</h1>
    <p>Số:  $invoice->invoice_number </p>
    <p>Ngày:  $invoice->issue_date </p>
    
    <h2>Thông tin khách hàng</h2>
    <p>Tên:  $customer->name </p>
    <p>MST:  $customer->tax_code </p>
    
    <table>
        <tr>
            <th>STT</th>
            <th>Mô tả</th>
            <th>Số lượng</th>
            <th>Đơn giá</th>
            <th>Thành tiền</th>
        </tr>
        @foreach($orders as $order)
            @foreach($order->items as $item)
            <tr>
                <td> $loop->iteration </td>
                <td> $item->product_name </td>
                <td> $item->quantity </td>
                <td> number_format($item->price) </td>
                <td> number_format($item->total) </td>
            </tr>
            @endforeach
        @endforeach
    </table>
    
    <p>Tổng cộng:  number_format($invoice->subtotal) </p>
    <p>VAT (10%):  number_format($invoice->vat_amount) </p>
    <p><strong>Tổng thanh toán:  number_format($invoice->total) </strong></p>
</body>
</html>
```

---

## 📊 BUSINESS RULES

### 1. Chỉ Tạo Khi Có tax_code

**Why:**

- Không phải customer nào cũng cần hóa đơn VAT
- Retail customers: không cần
- B2B/Corporate: bắt buộc cần

**Validation:**

```php
if (empty($customer->tax_code)) {
    return response()->json([
        'error' => 'customer_no_tax_code',
        'message' => 'Customer must have tax code'
    ], 422);
}
```

---

### 2. Gộp Nhiều Orders

**Use Case:**

- B2B customer có nhiều đơn hàng trong tháng
- Muốn gộp thành 1 hóa đơn để đối soát

**Example:**

```
Customer A:
- Order 1 (05/11): 1tr
- Order 2 (10/11): 2tr
- Order 3 (20/11): 1.5tr

Invoice (30/11): HD-1-0001
- Total: 4.5tr
- VAT: 450k
- Grand Total: 4.95tr
```

---

### 3. VAT Configurable

**Settings Table:**

```sql
INSERT INTO settings (key, value) VALUES
('tax.vat_rate', '0.10');  -- 10%
```

**Admin có thể update:**

- 0% (miễn thuế)
- 5% (thuế giảm)
- 10% (thuế chuẩn)

**Snapshot in Invoice:**

```sql
invoices.vat_rate DECIMAL(5,4)  -- Lưu rate tại thời điểm tạo
```

**Why:** Immutability - VAT rate có thể thay đổi sau này

---

### 4. Due Date - Admin Nhập

**Không auto calculate:**

- Mỗi customer có credit terms khác nhau
- 7 days / 30 days / 60 days
- Admin nhập thủ công

**Optional field:**

```sql
invoices.due_date DATE NULL
```

---

### 5. PDF On-Demand

**Không tự động generate:**

- Performance
- Storage
- Chỉ generate khi cần

**Cache:**

- First request: generate + save
- Subsequent: serve cached file

---

## 📝 DATABASE SCHEMA

### invoices table

```sql
CREATE TABLE invoices (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  invoice_number VARCHAR(50) UNIQUE NOT NULL,
  customer_id BIGINT NOT NULL,
  branch_id BIGINT NOT NULL,
  issue_date DATE NOT NULL,
  due_date DATE,
  subtotal DECIMAL(15,2) NOT NULL,
  vat_rate DECIMAL(5,4) NOT NULL,
  vat_amount DECIMAL(15,2) NOT NULL,
  total DECIMAL(15,2) NOT NULL,
  pdf_path VARCHAR(255),
  notes TEXT,
  created_by BIGINT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  FOREIGN KEY (branch_id) REFERENCES branches(id),
  INDEX (customer_id),
  INDEX (invoice_number),
  INDEX (issue_date)
);
```

---

### invoice_orders table

```sql
CREATE TABLE invoice_orders (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  invoice_id BIGINT NOT NULL,
  order_id BIGINT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
  FOREIGN KEY (order_id) REFERENCES orders(id),
  UNIQUE KEY unique_invoice_order (invoice_id, order_id),
  INDEX (invoice_id),
  INDEX (order_id)
);
```

**Purpose:** Many-to-many mapping

- 1 invoice → nhiều orders
- 1 order → có thể có nhiều invoices (rare)

---

## 🚨 ERROR HANDLING

### 1. Customer No Tax Code

```json
Response: 422
{
  "error": "customer_no_tax_code",
  "message": "Customer must have tax code to generate invoice",
  "customer_id": 123
}
```

---

### 2. Order Not COMPLETED

```json
Response: 422
{
  "error": "invalid_order_status",
  "message": "Order #456 is not completed",
  "order_id": 456,
  "status": "shipping"
}
```

---

### 3. Orders Different Customers

```json
Response: 422
{
  "error": "different_customers",
  "message": "All orders must belong to same customer",
  "order_ids": [456, 457],
  "customer_ids": [123, 124]
}
```

---

### 4. Duplicate Invoice Number (Race Condition)

```json
Response: 500
{
  "error": "duplicate_invoice_number",
  "message": "Invoice number already exists. Please try again."
}

Action: Retry generation
```

---

## 🎬 COMPLETE SCENARIOS

### Scenario 1: Single Order Invoice

```
1. Customer mua hàng → Order COMPLETED

2. Customer yêu cầu hóa đơn VAT
   → Staff check: customer có tax_code ✓

3. Staff tạo invoice
   → POST /api/invoices
   → {customer_id: 123, order_ids: [456]}

4. System generate
   → Invoice number: HD-1-0001
   → Subtotal: 1,000,000
   → VAT (10%): 100,000
   → Total: 1,100,000

5. Customer request PDF
   → GET /api/invoices/789/pdf
   → System generate PDF
   → Save to storage
   → Return file
```

---

### Scenario 2: Multiple Orders Invoice (Gộp Tháng)

```
1. Customer A (B2B) có 5 orders trong tháng 11
   → Tất cả COMPLETED

2. Cuối tháng, customer yêu cầu gộp hóa đơn

3. Staff tạo invoice gộp
   → POST /api/invoices
   → {
       customer_id: 123,
       order_ids: [456, 457, 458, 459, 460],
       due_date: "2024-12-31",
       notes: "Hóa đơn tháng 11/2024"
     }

4. System calculate
   → Order 1: 1tr
   → Order 2: 2tr
   → Order 3: 1.5tr
   → Order 4: 800k
   → Order 5: 1.2tr
   → Subtotal: 6.5tr
   → VAT (10%): 650k
   → Total: 7.15tr

5. Generate PDF với tất cả items
```

---

### Scenario 3: Update VAT Rate

```
1. Tháng 1: VAT = 10%
   → Invoice 1: subtotal 1tr → VAT 100k → total 1.1tr
   → Lưu vat_rate = 0.10 trong invoice

2. Tháng 6: Chính phủ thay đổi VAT = 8%
   → Admin update settings: vat_rate = 0.08

3. Tháng 7: Tạo invoice mới
   → Invoice 2: subtotal 1tr → VAT 80k → total 1.08tr
   → Lưu vat_rate = 0.08

4. Invoice 1 GIỮ NGUYÊN vat_rate = 0.10
   → Immutability
```

---

## 🔗 RELATED DOCUMENTS

- **Business Decisions:** #16-22
- **API Implementation:** Task INV-001
- **Shipping Flow:** SHIPPING_FLOW
- **Return Flow:** RETURN_FLOW

---

**Notes:**

- Phase 1: Manual refund execution (không auto)
- Phase 2: Có thể thêm e-invoice integration (VNPT, Viettel)
- Phase 2: Có thể thêm email invoice tự động