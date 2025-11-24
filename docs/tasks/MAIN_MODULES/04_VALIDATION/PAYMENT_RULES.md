# PAYMENT_RULES - Payment Validation Rules

# Payment Validation Rules

**Module:** Order Workflow

**Related Task:** PAY-001

**Last Updated:** 2025-11-24

---

## 🎯 MỤC ĐÍCH

Document này định nghĩa **toàn bộ validation rules** cho Payment Methods và Payment Processing.

---

## 📋 PAYMENT METHOD RULES

### **RULE-PAY-001: Code Required**

**Rule:** Payment method PHẢI có code

**Validation:**

```php
if (empty($data['code'])) {
    throw new ValidationException('Payment method code is required');
}
```

**Error Code:** `PAY_CODE_REQUIRED`

**HTTP Status:** 400 Bad Request

---

### **RULE-PAY-002: Code Format Valid**

**Rule:** Code PHẢI là UPPERCASE, alphanumeric + underscore

**Pattern:** `^[A-Z_]+$`

**Validation:**

```php
if (!preg_match('/^[A-Z_]+$/', $data['code'])) {
    throw new ValidationException('Payment method code must be UPPERCASE alphanumeric with underscores');
}
```

**Error Code:** `PAY_CODE_INVALID_FORMAT`

**Valid Examples:**

- `CASH` ✅
- `BANK_TRANSFER` ✅
- `E_WALLET` ✅
- `cash` ❌ (lowercase)
- `CASH-123` ❌ (hyphen not allowed)

---

### **RULE-PAY-003: Code Uniqueness**

**Rule:** Code PHẢI unique trong toàn hệ thống

**Validation:**

```php
$exists = PaymentMethod::where('code', $data['code'])
    ->where('id', '!=', $paymentMethodId) // Exclude self when updating
    ->exists();

if ($exists) {
    throw new ValidationException('Payment method code already exists');
}
```

**Error Code:** `PAY_CODE_DUPLICATE`

---

### **RULE-PAY-004: Name Required**

**Rule:** Payment method PHẢI có name

**Validation:**

```php
if (empty($data['name'])) {
    throw new ValidationException('Payment method name is required');
}

if (strlen($data['name']) > 255) {
    throw new ValidationException('Payment method name is too long (max 255 chars)');
}
```

**Error Code:** `PAY_NAME_REQUIRED` / `PAY_NAME_TOO_LONG`

---

### **RULE-PAY-005: Display Order Valid**

**Rule:** display_order PHẢI >= 0

**Validation:**

```php
if (isset($data['display_order']) && $data['display_order'] < 0) {
    throw new ValidationException('Display order must be >= 0');
}
```

**Error Code:** `PAY_DISPLAY_ORDER_INVALID`

**Default:** 0

---

## 💰 PAYMENT PROCESSING RULES

### **RULE-PAY-006: Payment Method Active**

**Rule:** Chỉ cho phép dùng payment methods có is_active = TRUE

**Validation:**

```php
$paymentMethod = PaymentMethod::where('code', $data['payment_method'])
    ->where('is_active', true)
    ->first();

if (!$paymentMethod) {
    throw new ValidationException('Payment method is inactive or not found');
}
```

**Error Code:** `PAY_METHOD_INACTIVE`

---

### **RULE-PAY-007: POS Orders Use CASH Only**

**Rule:** POS orders PHẢI dùng CASH payment

**Validation:**

```php
if ($order->order_type === 'POS' && $data['payment_method'] !== 'CASH') {
    throw new ValidationException('POS orders must use CASH payment method');
}
```

**Error Code:** `PAY_POS_MUST_CASH`

**Business Rule:** POS = bán tại quầy, luôn thu tiền mặt

---

### **RULE-PAY-008: CASH Not For Shipping Orders**

**Rule:** SHIPPING orders KHÔNG thể dùng CASH

**Validation:**

```php
if ($order->order_type === 'SHIPPING' && $data['payment_method'] === 'CASH') {
    throw new ValidationException('Cannot use CASH payment for shipping orders');
}
```

**Error Code:** `PAY_CASH_SHIPPING_FORBIDDEN`

**Business Rule:** Customer không ở cửa hàng, không thể trả tiền mặt

---

### **RULE-PAY-009: Payment Amount Valid**

**Rule:** paid_amount PHẢI >= 0 và <= total

**Validation:**

```php
if ($data['paid_amount'] < 0) {
    throw new ValidationException('Paid amount cannot be negative');
}

if ($data['paid_amount'] > $order->total) {
    throw new ValidationException('Paid amount cannot exceed order total');
}
```

**Error Code:** `PAY_AMOUNT_NEGATIVE` / `PAY_AMOUNT_EXCEEDED`

---

### **RULE-PAY-010: POS Orders Fully Paid**

**Rule:** POS orders PHẢI fully paid (paid_amount = total)

**Validation:**

```php
if ($order->order_type === 'POS' && $data['paid_amount'] !== $order->total) {
    throw new ValidationException('POS orders must be fully paid');
}
```

**Error Code:** `PAY_POS_UNPAID`

**Business Rule:** Không cho phép nợ ở POS orders

---

## 💸 COD PAYMENT RULES

### **RULE-PAY-011: COD Only For Shipping**

**Rule:** COD chỉ dùng cho SHIPPING orders

**Validation:**

```php
if ($data['payment_method'] === 'COD' && $order->order_type !== 'SHIPPING') {
    throw new ValidationException('COD is only available for shipping orders');
}
```

**Error Code:** `PAY_COD_SHIPPING_ONLY`

---

### **RULE-PAY-012: COD Amount Equals Total**

**Rule:** Nếu payment_method = COD, cod_amount PHẢI = total

**Validation:**

```php
if ($order->payment_method === 'COD') {
    if ($order->cod_amount !== $order->total) {
        throw new ValidationException('COD amount must equal order total');
    }
}
```

**Error Code:** `PAY_COD_AMOUNT_MISMATCH`

---

### **RULE-PAY-013: COD Not Paid Initially**

**Rule:** COD orders có is_paid = FALSE ban đầu

**Validation:**

```php
if ($order->payment_method === 'COD' && $order->is_paid) {
    throw new ValidationException('COD orders cannot be marked as paid initially');
}
```

**Error Code:** `PAY_COD_PREPAID_FORBIDDEN`

**Business Rule:** COD = thanh toán khi nhận hàng

---

### **RULE-PAY-014: COD Collection Tracking**

**Rule:** Khi shipper thu tiền, update cod_collected

**Validation:**

```php
if ($order->payment_method === 'COD' && $data['cod_collected']) {
    if ($data['cod_collected_amount'] !== $order->total) {
        throw new ValidationException('COD collected amount mismatch');
    }
    
    // Update order
    $order->update([
        'cod_collected' => true,
        'paid_amount' => $order->total,
        'is_paid' => true
    ]);
}
```

**Error Code:** `PAY_COD_COLLECTED_MISMATCH`

---

### **RULE-PAY-015: COD Reconciliation**

**Rule:** Khi shipper trả tiền cho shop, update cod_reconciled

**Validation:**

```php
if ($order->payment_method === 'COD' && $data['cod_reconciled']) {
    if (!$order->cod_collected) {
        throw new ValidationException('Cannot reconcile before COD is collected');
    }
    
    $order->update([
        'cod_reconciled' => true,
        'cod_reconciled_at' => now()
    ]);
}
```

**Error Code:** `PAY_COD_NOT_COLLECTED`

---

## 💳 ONLINE PAYMENT RULES

### **RULE-PAY-016: BANK_TRANSFER Requires Manual Verification**

**Rule:** BANK_TRANSFER cần admin verify manually

**Workflow:**

1. Customer chuyển khoản
2. Admin verify trong admin panel
3. Admin update is_paid = TRUE

**Validation:**

```php
if ($order->payment_method === 'BANK_TRANSFER' && $data['mark_as_paid']) {
    if (!auth()->user()->hasRole('admin')) {
        throw new ValidationException('Only admins can verify bank transfers');
    }
    
    $order->update([
        'is_paid' => true,
        'paid_amount' => $order->total,
        'verified_by' => auth()->id(),
        'verified_at' => now()
    ]);
}
```

**Error Code:** `PAY_VERIFICATION_ADMIN_REQUIRED`

---

### **RULE-PAY-017: CARD Payment Gateway (Phase 2)**

**Rule:** CARD payment cần integration với payment gateway

**Phase 1:** Manual input card info

**Phase 2:** Payment gateway (Stripe, VNPay)

**Validation:**

```php
// Phase 1: Skip validation
if ($order->payment_method === 'CARD') {
    // TODO: Implement payment gateway
    logger()->warning('CARD payment not fully implemented yet');
}
```

---

### **RULE-PAY-018: E_WALLET Requires Manual Verification (Phase 1)**

**Rule:** E_WALLET cần manual verify (giống BANK_TRANSFER)

**Phase 1:** Manual verify

**Phase 2:** API integration (MoMo, ZaloPay, VNPay)

**Validation:**

```php
if ($order->payment_method === 'E_WALLET' && $data['mark_as_paid']) {
    if (!auth()->user()->hasRole('admin')) {
        throw new ValidationException('Only admins can verify e-wallet payments');
    }
    
    $order->update([
        'is_paid' => true,
        'paid_amount' => $order->total,
        'verified_by' => auth()->id(),
        'verified_at' => now()
    ]);
}
```

**Error Code:** `PAY_VERIFICATION_ADMIN_REQUIRED`

---

## 🗑️ DELETE RULES

### **RULE-PAY-019: Cannot Delete Used Payment Method**

**Rule:** KHÔNG thể xóa payment method đang được dùng

**Validation:**

```php
$ordersCount = Order::where('payment_method', $paymentMethod->code)->count();

if ($ordersCount > 0) {
    throw new ValidationException('Cannot delete payment method that is used in orders');
}
```

**Error Code:** `PAY_METHOD_IN_USE`

**Alternative:** Set is_active = FALSE (disable thay vì xóa)

---

### **RULE-PAY-020: Disable Instead of Delete**

**Rule:** Recommend disable thay vì delete payment methods

**Validation:**

```php
// Prefer this
$paymentMethod->update(['is_active' => false]);

// Over this
$paymentMethod->delete();
```

**Benefits:**

- Giữ historical data
- Orders cũ vẫn reference đúng
- Analytics không bị sai

---

## ⚠️ BUSINESS LOGIC CONSTRAINTS

### **CONSTRAINT-001: Master Data Table**

**Rule:** payment_methods là master data, ít khi thay đổi

**5 Methods:**

1. CASH - Tiền mặt
2. BANK_TRANSFER - Chuyển khoản
3. CARD - Thẻ tín dụng/ghi nợ
4. COD - Thu hộ
5. E_WALLET - Ví điện tử

**Seeding:**

```php
PaymentMethod::insert([
    ['code' => 'CASH', 'name' => 'Tiền mặt', 'display_order' => 1],
    ['code' => 'BANK_TRANSFER', 'name' => 'Chuyển khoản', 'display_order' => 2],
    ['code' => 'CARD', 'name' => 'Thẻ tín dụng/ghi nợ', 'display_order' => 3],
    ['code' => 'COD', 'name' => 'Thu hộ (COD)', 'display_order' => 4],
    ['code' => 'E_WALLET', 'name' => 'Ví điện tử', 'display_order' => 5],
]);
```

---

### **CONSTRAINT-002: Payment Method Code Immutability**

**Rule:** KHÔNG thay đổi code sau khi tạo

**Validation:**

```php
if ($paymentMethod->exists && $paymentMethod->isDirty('code')) {
    throw new ValidationException('Cannot change payment method code after creation');
}
```

**Rationale:** Orders reference payment methods by code

---

### **CONSTRAINT-003: Partial Payment Support**

**Rule:** Cho phép partial payment (trả trước 1 phần)

**Example:**

```
Order total: 1,000,000đ
Paid amount: 300,000đ
Debt amount: 700,000đ (calculated)

Customer trả thêm: 200,000đ
New paid: 500,000đ
New debt: 500,000đ
```

**Implementation:**

```php
public function addPayment($orderId, $amount) {
    $order = Order::find($orderId);
    
    $newPaid = $order->paid_amount + $amount;
    
    if ($newPaid > $order->total) {
        throw new ValidationException('Payment exceeds order total');
    }
    
    $order->update([
        'paid_amount' => $newPaid,
        'is_paid' => ($newPaid >= $order->total)
    ]);
}
```

---

## 🔗 RELATED DOCUMENTS

- [**PAYMENT_METHODS_](https://www.notion.so/PAYMENT_METHODS_TABLE-Payment-Methods-Schema-ac2e0402fa7b48099e71738e419ccff5?pvs=21)[TABLE.md](http://TABLE.md)** - Payment methods schema
- [**ORDERS_](https://www.notion.so/ORDERS_TABLE-Orders-Table-Schema-36b32ddd5ce6421abd19d92589270881?pvs=21)[TABLE.md](http://TABLE.md)** - Orders schema
- **Business Decisions:** #8-15