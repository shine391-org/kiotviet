# ORDER_RULES - Order Validation Rules

# Order Validation Rules

**Module:** Order Workflow

**Related Tasks:** ORD-001, ORD-002, ORD-003

**Last Updated:** 2025-11-24

---

## 🎯 MỤC ĐÍCH

Document này định nghĩa **toàn bộ validation rules** cho Orders module.

---

## 📋 ORDER CREATION RULES

### **RULE-ORD-001: Customer Required**

**Rule:** Mọi order PHẢI có customer

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

**Error Code:** `ORD_CUSTOMER_REQUIRED`

**HTTP Status:** 400 Bad Request

---

### **RULE-ORD-002: Branch Required**

**Rule:** Mọi order PHẢI thuộc về 1 branch

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

**Error Code:** `ORD_BRANCH_REQUIRED`

---

### **RULE-ORD-003: Order Type Valid**

**Rule:** order_type PHẢI là 1 trong 2 giá trị: `POS`, `SHIPPING`

**Validation:**

```php
$validTypes = ['POS', 'SHIPPING'];
if (!in_array($data['order_type'], $validTypes)) {
    throw new ValidationException('Order type must be POS or SHIPPING');
}
```

**Error Code:** `ORD_TYPE_INVALID`

---

### **RULE-ORD-004: Payment Method Valid**

**Rule:** payment_method PHẢI tồn tại và active

**Validation:**

```php
$paymentMethod = PaymentMethod::where('code', $data['payment_method'])
    ->where('is_active', true)
    ->first();
    
if (!$paymentMethod) {
    throw new ValidationException('Payment method is invalid or inactive');
}
```

**Error Code:** `ORD_PAYMENT_METHOD_INVALID`

---

### **RULE-ORD-005: POS Orders Must Use CASH**

**Rule:** Nếu order_type = `POS`, payment_method PHẢI là `CASH`

**Validation:**

```php
if ($data['order_type'] === 'POS' && $data['payment_method'] !== 'CASH') {
    throw new ValidationException('POS orders must use CASH payment');
}
```

**Error Code:** `ORD_POS_MUST_CASH`

---

### **RULE-ORD-006: At Least One Item**

**Rule:** Order PHẢI có ít nhất 1 item

**Validation:**

```php
if (empty($data['items']) || count($data['items']) === 0) {
    throw new ValidationException('Order must have at least one item');
}
```

**Error Code:** `ORD_NO_ITEMS`

---

### **RULE-ORD-007: Item Quantity Positive**

**Rule:** Mỗi item PHẢI có quantity > 0

**Validation:**

```php
foreach ($data['items'] as $item) {
    if (!isset($item['quantity']) || $item['quantity'] <= 0) {
        throw new ValidationException('Item quantity must be positive');
    }
}
```

**Error Code:** `ORD_ITEM_QUANTITY_INVALID`

---

### **RULE-ORD-008: Product Exists**

**Rule:** Mỗi item PHẢI reference đến product tồn tại

**Validation:**

```php
foreach ($data['items'] as $item) {
    $product = Product::find($item['product_id']);
    if (!$product) {
        throw new ValidationException("Product #{$item['product_id']} not found");
    }
}
```

**Error Code:** `ORD_PRODUCT_NOT_FOUND`

---

### **RULE-ORD-009: Variant Exists If Specified**

**Rule:** Nếu product có variants, PHẢI chọn variant_id

**Validation:**

```php
foreach ($data['items'] as $item) {
    $product = Product::find($item['product_id']);
    
    if ($product->has_variants && empty($item['variant_id'])) {
        throw new ValidationException("Product #{$product->id} requires variant selection");
    }
    
    if (!empty($item['variant_id'])) {
        $variant = Variant::where('product_id', $product->id)
            ->where('id', $item['variant_id'])
            ->first();
            
        if (!$variant) {
            throw new ValidationException("Variant #{$item['variant_id']} not found");
        }
    }
}
```

**Error Code:** `ORD_VARIANT_REQUIRED` / `ORD_VARIANT_NOT_FOUND`

---

### **RULE-ORD-010: Stock Available**

**Rule:** Inventory PHẢI đủ stock

**Validation:**

```php
foreach ($data['items'] as $item) {
    $inventory = Inventory::where('branch_id', $data['branch_id'])
        ->where('product_id', $item['product_id'])
        ->where('variant_id', $item['variant_id'])
        ->first();
        
    if (!$inventory || $inventory->quantity < $item['quantity']) {
        throw new ValidationException("Insufficient stock for product #{$item['product_id']}");
    }
}
```

**Error Code:** `ORD_INSUFFICIENT_STOCK`

**Note:** Có thể skip rule này bằng flag `allow_negative_stock`

---

## 📊 ORDER FINANCIAL RULES

### **RULE-ORD-011: Subtotal >= 0**

**Rule:** subtotal PHẢI >= 0

**Validation:**

```php
if ($order->subtotal < 0) {
    throw new ValidationException('Subtotal cannot be negative');
}
```

**Error Code:** `ORD_SUBTOTAL_NEGATIVE`

---

### **RULE-ORD-012: Discount <= Subtotal**

**Rule:** discount KHÔNG thể > subtotal

**Validation:**

```php
if ($order->discount > $order->subtotal) {
    throw new ValidationException('Discount cannot exceed subtotal');
}
```

**Error Code:** `ORD_DISCOUNT_TOO_LARGE`

---

### **RULE-ORD-013: Total = Subtotal - Discount + Shipping**

**Rule:** total PHẢI = subtotal - discount + shipping_fee

**Validation:**

```php
$expectedTotal = $order->subtotal - $order->discount + $order->shipping_fee;

if (abs($order->total - $expectedTotal) > 0.01) {
    throw new ValidationException('Total amount mismatch');
}
```

**Error Code:** `ORD_TOTAL_MISMATCH`

---

### **RULE-ORD-014: Paid Amount <= Total**

**Rule:** paid_amount KHÔNG thể > total

**Validation:**

```php
if ($order->paid_amount > $order->total) {
    throw new ValidationException('Paid amount cannot exceed total');
}
```

**Error Code:** `ORD_OVERPAID`

---

### **RULE-ORD-015: COD Amount Valid**

**Rule:** Nếu payment_method = `COD`, cod_amount PHẢI = total

**Validation:**

```php
if ($order->payment_method === 'COD') {
    if ($order->cod_amount !== $order->total) {
        throw new ValidationException('COD amount must equal total');
    }
}
```

**Error Code:** `ORD_COD_AMOUNT_MISMATCH`

---

## 🔄 ORDER STATUS RULES

### **RULE-ORD-016: Status Transition Valid**

**Rule:** Chỉ cho phép chuyển status theo workflow

**Valid Transitions:**

```
draft → confirmed
draft → cancelled
confirmed → processing
confirmed → cancelled
processing → shipping
processing → cancelled
shipping → delivered
delivered → completed
any → cancelled (except completed)
```

**Validation:**

```php
$validTransitions = [
    'draft' => ['confirmed', 'cancelled'],
    'confirmed' => ['processing', 'cancelled'],
    'processing' => ['shipping', 'cancelled'],
    'shipping' => ['delivered'],
    'delivered' => ['completed'],
];

$fromStatus = $order->status;
$toStatus = $newStatus;

if (!isset($validTransitions[$fromStatus]) || 
    !in_array($toStatus, $validTransitions[$fromStatus])) {
    throw new ValidationException("Cannot transition from {$fromStatus} to {$toStatus}");
}
```

**Error Code:** `ORD_INVALID_STATUS_TRANSITION`

---

### **RULE-ORD-017: Cannot Edit Completed Order**

**Rule:** Không thể edit order đã completed

**Validation:**

```php
if ($order->status === 'completed') {
    throw new ValidationException('Cannot modify completed order');
}
```

**Error Code:** `ORD_COMPLETED_IMMUTABLE`

---

### **RULE-ORD-018: Cannot Edit Items After Confirmed**

**Rule:** Không thể thêm/xóa/sửa items sau khi confirmed

**Validation:**

```php
if (in_array($order->status, ['confirmed', 'processing', 'shipping', 'delivered', 'completed'])) {
    throw new ValidationException('Cannot modify items after order is confirmed');
}
```

**Error Code:** `ORD_ITEMS_LOCKED`

---

## 🚚 SHIPPING RULES

### **RULE-ORD-019: Shipping Required for SHIPPING Orders**

**Rule:** Nếu order_type = `SHIPPING`, PHẢI có shipping info

**Validation:**

```php
if ($order->order_type === 'SHIPPING') {
    if (empty($order->shipping_address)) {
        throw new ValidationException('Shipping address is required');
    }
    
    if (empty($order->shipping_ward) || 
        empty($order->shipping_district) || 
        empty($order->shipping_city)) {
        throw new ValidationException('Complete shipping address is required');
    }
}
```

**Error Code:** `ORD_SHIPPING_ADDRESS_REQUIRED`

---

### **RULE-ORD-020: Shipping Fee >= 0**

**Rule:** shipping_fee PHẢI >= 0

**Validation:**

```php
if ($order->shipping_fee < 0) {
    throw new ValidationException('Shipping fee cannot be negative');
}
```

**Error Code:** `ORD_SHIPPING_FEE_NEGATIVE`

---

## 💰 PAYMENT RULES

### **RULE-ORD-021: POS Orders Must Be Fully Paid**

**Rule:** Nếu order_type = `POS`, paid_amount PHẢI = total

**Validation:**

```php
if ($order->order_type === 'POS' && $order->paid_amount !== $order->total) {
    throw new ValidationException('POS orders must be fully paid');
}
```

**Error Code:** `ORD_POS_UNPAID`

---

### **RULE-ORD-022: Mark Paid Requires Full Payment**

**Rule:** Chỉ cho phép set is_paid = true nếu paid_amount = total

**Validation:**

```php
if ($isPaid && $order->paid_amount < $order->total) {
    throw new ValidationException('Cannot mark as paid when payment is incomplete');
}
```

**Error Code:** `ORD_PARTIAL_PAYMENT`

---

## 🗑️ DELETE RULES

### **RULE-ORD-023: Cannot Delete After Confirmed**

**Rule:** Chỉ cho phép xóa order ở status draft

**Validation:**

```php
if ($order->status !== 'draft') {
    throw new ValidationException('Can only delete draft orders');
}
```

**Error Code:** `ORD_DELETE_NOT_DRAFT`

**Alternative:** Dùng soft delete hoặc cancel

---

## ⚠️ BUSINESS LOGIC CONSTRAINTS

### **CONSTRAINT-001: Order Number Uniqueness**

**Rule:** order_number PHẢI unique trong toàn hệ thống

**Implementation:** Database UNIQUE constraint + application check

---

### **CONSTRAINT-002: Customer Debt Limit**

**Rule:** Nếu customer có debt limit, tổng debt KHÔNG vượt quá limit

**Validation:**

```php
$customer = Customer::find($order->customer_id);

if ($customer->debt_limit > 0) {
    $currentDebt = $customer->debt_amount;
    $newDebt = $order->total - $order->paid_amount;
    
    if (($currentDebt + $newDebt) > $customer->debt_limit) {
        throw new ValidationException('Customer debt limit exceeded');
    }
}
```

**Error Code:** `ORD_DEBT_LIMIT_EXCEEDED`

---

### **CONSTRAINT-003: Inventory Deduction on Processing**

**Rule:** Khi chuyển sang `processing`, PHẢI deduct inventory

**Implementation:**

```php
if ($newStatus === 'processing' && $oldStatus !== 'processing') {
    foreach ($order->items as $item) {
        Inventory::where('branch_id', $order->branch_id)
            ->where('product_id', $item->product_id)
            ->where('variant_id', $item->variant_id)
            ->decrement('quantity', $item->quantity);
    }
}
```

---

## 🔗 RELATED DOCUMENTS

- [**ORDERS_](https://www.notion.so/ORDERS_TABLE-Orders-Table-Schema-36b32ddd5ce6421abd19d92589270881?pvs=21)[TABLE.md](http://TABLE.md)** - Orders schema
- **[ORDER_[FLOW.md](http://FLOW.md)]** - Order workflow
- **Business Decisions:** #1-15