# Invoice Validation Rules

**Module:** Order Workflow

**Related Task:** INV-001

**Last Updated:** 2025-11-24

---

## 🎯 MỤC ĐÍCH

Document này định nghĩa **toàn bộ validation rules** cho Invoices module.

---

## 📋 INVOICE CREATION RULES

### **RULE-INV-001: Customer Required**

**Rule:** Mọi invoice PHẢI có customer

**Validation:**

```php
if (empty($data['customer_id'])) {
    throw new ValidationException('Customer is required');
}

$customer = Customer::find($data['customer_id']);
if (!$customer) {
    throw new ValidationException('Customer not found');
}
```

**Error Code:** `INV_CUSTOMER_REQUIRED`

**HTTP Status:** 400 Bad Request

---

### **RULE-INV-002: Customer Must Have Tax Code**

**Rule:** Customer PHẢI có tax_code để tạo invoice VAT

**Validation:**

```php
if (empty($customer->tax_code)) {
    throw new ValidationException('Customer must have tax code to generate invoice');
}
```

**Error Code:** `INV_TAX_CODE_REQUIRED`

**Business Rule:** Invoice = hóa đơn VAT, cần tax_code

---

### **RULE-INV-003: At Least One Order**

**Rule:** Invoice PHẢI có ít nhất 1 order

**Validation:**

```php
if (empty($data['order_ids']) || count($data['order_ids']) === 0) {
    throw new ValidationException('Invoice must have at least one order');
}
```

**Error Code:** `INV_NO_ORDERS`

---

### **RULE-INV-004: Orders Must Be Completed**

**Rule:** Tất cả orders PHẢI có status = `completed`

**Validation:**

```php
foreach ($data['order_ids'] as $orderId) {
    $order = Order::find($orderId);
    
    if (!$order) {
        throw new ValidationException("Order #{$orderId} not found");
    }
    
    if ($order->status !== 'completed') {
        throw new ValidationException("Order #{$orderId} is not completed");
    }
}
```

**Error Code:** `INV_ORDER_NOT_COMPLETED`

**Business Rule:** Chỉ xuất hóa đơn cho orders đã hoàn thành

---

### **RULE-INV-005: Orders Same Customer**

**Rule:** Tất cả orders PHẢI cùng 1 customer

**Validation:**

```php
$customerIds = Order::whereIn('id', $data['order_ids'])
    ->pluck('customer_id')
    ->unique();

if ($customerIds->count() > 1) {
    throw new ValidationException('All orders must belong to the same customer');
}

if ($customerIds->first() !== $data['customer_id']) {
    throw new ValidationException('Orders do not belong to specified customer');
}
```

**Error Code:** `INV_MIXED_CUSTOMERS`

---

### **RULE-INV-006: Orders Not Already Invoiced**

**Rule:** Orders KHÔNG được nằm trong invoice khác

**Validation:**

```php
foreach ($data['order_ids'] as $orderId) {
    $existingInvoice = InvoiceOrder::where('order_id', $orderId)->first();
    
    if ($existingInvoice) {
        throw new ValidationException("Order #{$orderId} is already in invoice #{$existingInvoice->invoice_id}");
    }
}
```

**Error Code:** `INV_ORDER_ALREADY_INVOICED`

**Note:** Phase 1 không cho phép 1 order trong nhiều invoices

---

### **RULE-INV-007: Branch Required**

**Rule:** Invoice PHẢI thuộc về 1 branch

**Validation:**

```php
if (empty($data['branch_id'])) {
    throw new ValidationException('Branch is required');
}

$branch = Branch::find($data['branch_id']);
if (!$branch || !$branch->is_active) {
    throw new ValidationException('Branch is invalid or inactive');
}
```

**Error Code:** `INV_BRANCH_REQUIRED`

---

## 💰 INVOICE FINANCIAL RULES

### **RULE-INV-008: Subtotal Valid**

**Rule:** subtotal PHẢI = tổng total của tất cả orders

**Validation:**

```php
$calculatedSubtotal = Order::whereIn('id', $data['order_ids'])
    ->sum('total');

if (abs($data['subtotal'] - $calculatedSubtotal) > 0.01) {
    throw new ValidationException('Subtotal mismatch');
}
```

**Error Code:** `INV_SUBTOTAL_MISMATCH`

---

### **RULE-INV-009: VAT Rate Valid**

**Rule:** vat_rate PHẢI nằm trong khoảng 0-1 (0-100%)

**Validation:**

```php
if ($data['vat_rate'] < 0 || $data['vat_rate'] > 1) {
    throw new ValidationException('VAT rate must be between 0 and 1');
}
```

**Error Code:** `INV_VAT_RATE_INVALID`

**Common Values:**

- 0.00 (0%) - Không chịu thuế
- 0.05 (5%) - Thuế suất 5%
- 0.10 (10%) - Thuế suất 10% (standard)

---

### **RULE-INV-010: VAT Amount Valid**

**Rule:** vat_amount PHẢI = subtotal × vat_rate

**Validation:**

```php
$calculatedVAT = $data['subtotal'] * $data['vat_rate'];

if (abs($data['vat_amount'] - $calculatedVAT) > 0.01) {
    throw new ValidationException('VAT amount mismatch');
}
```

**Error Code:** `INV_VAT_AMOUNT_MISMATCH`

---

### **RULE-INV-011: Total Valid**

**Rule:** total PHẢI = subtotal + vat_amount

**Validation:**

```php
$calculatedTotal = $data['subtotal'] + $data['vat_amount'];

if (abs($data['total'] - $calculatedTotal) > 0.01) {
    throw new ValidationException('Total mismatch');
}
```

**Error Code:** `INV_TOTAL_MISMATCH`

---

## 📅 INVOICE DATE RULES

### **RULE-INV-012: Issue Date Required**

**Rule:** issue_date PHẢI được set

**Validation:**

```php
if (empty($data['issue_date'])) {
    throw new ValidationException('Issue date is required');
}
```

**Error Code:** `INV_ISSUE_DATE_REQUIRED`

**Default:** TODAY

---

### **RULE-INV-013: Issue Date Not Future**

**Rule:** issue_date KHÔNG thể là ngày tương lai

**Validation:**

```php
if (Carbon::parse($data['issue_date'])->isFuture()) {
    throw new ValidationException('Issue date cannot be in the future');
}
```

**Error Code:** `INV_ISSUE_DATE_FUTURE`

---

### **RULE-INV-014: Due Date After Issue Date**

**Rule:** Nếu có due_date, PHẢI >= issue_date

**Validation:**

```php
if (!empty($data['due_date'])) {
    $issueDate = Carbon::parse($data['issue_date']);
    $dueDate = Carbon::parse($data['due_date']);
    
    if ($dueDate->lt($issueDate)) {
        throw new ValidationException('Due date cannot be before issue date');
    }
}
```

**Error Code:** `INV_DUE_DATE_INVALID`

---

## 🔢 INVOICE NUMBER RULES

### **RULE-INV-015: Invoice Number Uniqueness**

**Rule:** invoice_number PHẢI unique trong toàn hệ thống

**Format:** `HD-{branch_id}-{counter}`

**Validation:**

```php
$exists = Invoice::where('invoice_number', $invoiceNumber)->exists();

if ($exists) {
    throw new ValidationException('Invoice number already exists');
}
```

**Error Code:** `INV_NUMBER_DUPLICATE`

**Implementation:** Use transaction lock để tránh race condition

```php
DB::transaction(function() use ($branchId) {
    $counter = Invoice::where('branch_id', $branchId)
        ->lockForUpdate()
        ->count() + 1;
    
    $invoiceNumber = sprintf("HD-%d-%04d", $branchId, $counter);
    
    Invoice::create([
        'invoice_number' => $invoiceNumber,
        // ...
    ]);
});
```

---

## 📝 INVOICE EDIT RULES

### **RULE-INV-016: Cannot Edit After Issued**

**Rule:** Invoice KHÔNG thể edit sau khi đã issued (có invoice_number)

**Validation:**

```php
if (!empty($invoice->invoice_number)) {
    throw new ValidationException('Cannot modify issued invoice');
}
```

**Error Code:** `INV_ISSUED_IMMUTABLE`

**Rationale:** Invoice = tài liệu kế toán, phải immutable

---

### **RULE-INV-017: Cannot Change Customer**

**Rule:** KHÔNG thể thay đổi customer sau khi tạo

**Validation:**

```php
if ($invoice->customer_id !== $data['customer_id']) {
    throw new ValidationException('Cannot change invoice customer');
}
```

**Error Code:** `INV_CUSTOMER_IMMUTABLE`

---

### **RULE-INV-018: Cannot Change VAT After Issue**

**Rule:** KHÔNG thể thay đổi VAT rate sau khi issued

**Validation:**

```php
if (!empty($invoice->invoice_number) && $invoice->vat_rate !== $data['vat_rate']) {
    throw new ValidationException('Cannot change VAT rate after invoice is issued');
}
```

**Error Code:** `INV_VAT_IMMUTABLE`

**Rationale:** VAT rate = snapshot tại thời điểm issue

---

## 🗑️ DELETE RULES

### **RULE-INV-019: Cannot Delete After Issued**

**Rule:** Invoice KHÔNG thể xóa sau khi issued

**Validation:**

```php
if (!empty($invoice->invoice_number)) {
    throw new ValidationException('Cannot delete issued invoice');
}
```

**Error Code:** `INV_DELETE_ISSUED`

**Alternative:** Dùng soft delete hoặc cancel mechanism

---

## 📄 PDF GENERATION RULES

### **RULE-INV-020: PDF Template Required**

**Rule:** PHẢI có PDF template hợp lệ

**Validation:**

```php
if (!view()->exists('invoices.template')) {
    throw new ValidationException('Invoice PDF template not found');
}
```

**Error Code:** `INV_TEMPLATE_NOT_FOUND`

---

### **RULE-INV-021: PDF Path Unique**

**Rule:** pdf_path PHẢI unique

**Format:** `invoices/{year}/HD-{invoice_number}.pdf`

**Implementation:**

```php
$year = date('Y');
$filename = "HD-{$invoice->invoice_number}.pdf";
$path = "invoices/{$year}/{$filename}";

if (Storage::exists($path)) {
    // Use cached version
    return Storage::path($path);
}

// Generate new PDF
$pdf = PDF::loadView('invoices.template', ['invoice' => $invoice]);
Storage::put($path, $pdf->output());
```

---

## ⚠️ BUSINESS LOGIC CONSTRAINTS

### **CONSTRAINT-001: VAT Rate Snapshot**

**Rule:** VAT rate là snapshot tại thời điểm tạo invoice

**Example:**

```
Tháng 1/2024: VAT = 10%
→ Invoice #HD-1-0001: vat_rate = 0.10

Tháng 6/2024: Chính phủ đổi VAT = 8%
→ Invoice #HD-1-0100: vat_rate = 0.08

Invoice #HD-1-0001 vẫn giữ 0.10 (không đổi)
```

---

### **CONSTRAINT-002: Monthly Invoice Aggregation**

**Rule:** Cho phép gộp nhiều orders vào 1 invoice (monthly billing)

**Use Case:** B2B customers

**Example:**

```
Invoice #HD-1-0001:
- Order #ORD-001 (500k)
- Order #ORD-002 (300k)
- Order #ORD-003 (200k)
→ Subtotal: 1,000k
→ VAT 10%: 100k
→ Total: 1,100k
```

---

### **CONSTRAINT-003: On-Demand PDF Generation**

**Strategy:** Không generate PDF ngay khi tạo invoice

**Benefits:**

- Tiết kiệm storage
- Faster invoice creation
- Cache for reuse

**Implementation:**

```php
// Generate when user clicks "Download PDF"
public function downloadPDF($invoiceId) {
    $invoice = Invoice::find($invoiceId);
    
    // Check cache
    if ($invoice->pdf_path && Storage::exists($invoice->pdf_path)) {
        return Storage::download($invoice->pdf_path);
    }
    
    // Generate new
    $path = $this->generatePDF($invoice);
    return Storage::download($path);
}
```

---

## 🔗 RELATED DOCUMENTS

- [**INVOICES_](https://www.notion.so/INVOICES_TABLES-Invoices-Schema-9ff5db4f3f3a4ea78d2298ecca309a73?pvs=21)[TABLES.md](http://TABLES.md)** - Invoices schema
- [**INVOICE_](https://www.notion.so/INVOICE_FLOW-Invoice-Generation-Workflow-f870e3cf753e4c1abc41b11c6fe4748d?pvs=21)[FLOW.md](http://FLOW.md)** - Invoice workflow
- **Business Decisions:** #16-22