# Financial Edge Cases

**Module:** Order Workflow

**Last Updated:** 2025-11-24

---

## 🎯 MỤC ĐÍCH

Document này phân tích **các edge cases về financial calculations** trong Order Workflow.

---

## 💵 DISCOUNT EDGE CASES

### **Problem: Discount Exceeds Subtotal**

**Scenario:**

```
Subtotal: 100,000đ
Discount: 150,000đ
Total: -50,000đ ❌ NEGATIVE TOTAL
```

**Validation:**

```php
if ($order->discount > $order->subtotal) {
    throw new ValidationException('Discount cannot exceed subtotal');
}
```

---

### **Problem: Percentage Discount Calculation**

**Scenario:**

```php
$subtotal = 1000000;
$discountPercent = 10; // 10%
$discount = $subtotal * ($discountPercent / 100); // 100000
$total = $subtotal - $discount; // 900000

// User changes subtotal
$newSubtotal = 1500000;
// Should discount be recalculated? 🤔
```

**Solution 1: Store Percentage**

```sql
ALTER TABLE orders ADD COLUMN discount_type ENUM('fixed', 'percent');
ALTER TABLE orders ADD COLUMN discount_value DECIMAL(15,2);
```

**Solution 2: Recalculate on Change**

```php
public function updateSubtotal($orderId, $newSubtotal) {
    $order = Order::find($orderId);
    
    if ($order->discount_type === 'percent') {
        $newDiscount = $newSubtotal * ($order->discount_value / 100);
        $order->discount = $newDiscount;
    }
    
    $order->subtotal = $newSubtotal;
    $order->total = $newSubtotal - $order->discount + $order->shipping_fee;
    $order->save();
}
```

---

## 🚚 SHIPPING FEE EDGE CASES

### **Problem: Free Shipping Threshold**

**Scenario:**

```
Subtotal: 499,000đ → Shipping: 30,000đ
Subtotal: 500,000đ → Shipping: 0đ (free)

User adds item → subtotal = 501,000đ
Shipping should become 0đ ✅

User removes item → subtotal = 498,000đ
Shipping should become 30,000đ ✅
```

**Implementation:**

```php
public function calculateShippingFee($subtotal, $orderType) {
    if ($orderType === 'POS') {
        return 0; // No shipping for POS
    }
    
    $freeShippingThreshold = 500000;
    $standardShippingFee = 30000;
    
    if ($subtotal >= $freeShippingThreshold) {
        return 0;
    }
    
    return $standardShippingFee;
}

public function updateOrderItems($orderId, $items) {
    $order = Order::find($orderId);
    
    // Recalculate subtotal
    $newSubtotal = 0;
    foreach ($items as $item) {
        $newSubtotal += $item['price'] * $item['quantity'];
    }
    
    // Recalculate shipping
    $newShippingFee = $this->calculateShippingFee($newSubtotal, $order->order_type);
    
    // Update order
    $order->update([
        'subtotal' => $newSubtotal,
        'shipping_fee' => $newShippingFee,
        'total' => $newSubtotal - $order->discount + $newShippingFee
    ]);
}
```

---

## 💳 PAYMENT EDGE CASES

### **Problem: Partial Payment Rounding**

**Scenario:**

```
Order total: 1,000,000đ
Customer pays: 333,333đ (1/3)
Customer pays: 333,333đ (1/3)
Customer pays: 333,333đ (1/3)
Total paid: 999,999đ
Remaining: 1đ ❌ Still unpaid!
```

**Solution: Allow Small Difference**

```php
public function markAsPaid($orderId) {
    $order = Order::find($orderId);
    
    $remaining = $order->total - $order->paid_amount;
    
    // Allow 1đ difference
    if ($remaining <= 1) {
        $order->update([
            'paid_amount' => $order->total,
            'is_paid' => true
        ]);
        return;
    }
    
    throw new ValidationException("Remaining amount: {$remaining}đ");
}
```

---

### **Problem: Overpayment**

**Scenario:**

```
Order total: 1,000,000đ
Customer pays: 1,500,000đ
What to do with extra 500,000đ? 🤔
```

**Options:**

**1. Reject Overpayment (Recommended)**

```php
if ($order->paid_amount + $amount > $order->total) {
    throw new ValidationException('Payment exceeds order total');
}
```

**2. Store as Credit**

```php
if ($order->paid_amount + $amount > $order->total) {
    $excess = ($order->paid_amount + $amount) - $order->total;
    
    // Store excess as customer credit
    CustomerCredit::create([
        'customer_id' => $order->customer_id,
        'amount' => $excess,
        'source' => 'overpayment',
        'order_id' => $order->id
    ]);
    
    // Mark order as fully paid
    $order->update([
        'paid_amount' => $order->total,
        'is_paid' => true
    ]);
}
```

---

## 🧾 INVOICE FINANCIAL EDGE CASES

### **Problem: VAT Rounding**

**Scenario:**

```
Subtotal: 1,000,000đ
VAT 10%: 100,000đ ✅

Subtotal: 1,000,001đ
VAT 10%: 100,000.1đ
Rounded: 100,000đ ❌ Lost 0.1đ
```

**Solution: Standard Rounding Rules**

```php
public function calculateVAT($subtotal, $vatRate) {
    $vatAmount = $subtotal * $vatRate;
    
    // Round to 2 decimal places
    $rounded = round($vatAmount, 2);
    
    return $rounded;
}

// Examples:
// 100,000.1 → 100,000.10
// 100,000.4 → 100,000.40
// 100,000.5 → 100,000.50
// 100,000.6 → 100,000.60
```

**Vietnamese Rounding (to nearest 100đ):**

```php
public function roundVietnamese($amount) {
    // Round to nearest 100đ
    return round($amount / 100) * 100;
}

// Examples:
// 100,049đ → 100,000đ
// 100,050đ → 100,100đ
// 100,150đ → 100,200đ
```

---

### **Problem: Multiple Orders Invoice Total Mismatch**

**Scenario:**

```
Order #1: 500,000đ
Order #2: 300,000đ
Order #3: 200,000đ

Expected invoice subtotal: 1,000,000đ
Actual subtotal: 999,999đ ❌

Reason: Each order rounded individually, then summed
```

**Solution: Calculate Before Rounding**

```php
public function createInvoice($orderIds) {
    $orders = Order::whereIn('id', $orderIds)->get();
    
    // Sum exact totals first
    $exactSubtotal = 0;
    foreach ($orders as $order) {
        $exactSubtotal += $order->total; // Use exact values
    }
    
    // Then round once
    $subtotal = round($exactSubtotal, 2);
    
    // Calculate VAT
    $vatAmount = round($subtotal * 0.10, 2);
    $total = $subtotal + $vatAmount;
    
    Invoice::create([
        'subtotal' => $subtotal,
        'vat_rate' => 0.10,
        'vat_amount' => $vatAmount,
        'total' => $total
    ]);
}
```

---

## 🔄 RETURN REFUND EDGE CASES

### **Problem: Partial Return Calculation**

**Scenario:**

```
Order:
- 3x Product A @ 100,000đ = 300,000đ
- 2x Product B @ 150,000đ = 300,000đ
Subtotal: 600,000đ
Discount: 60,000đ (10%)
Total: 540,000đ

Return 1x Product A:
Refund = 100,000đ? Or 90,000đ (with discount)? 🤔
```

**Solution 1: Proportional Discount**

```php
public function calculateRefund($order, $returnItems) {
    $orderTotal = 0;
    $returnTotal = 0;
    
    foreach ($order->items as $item) {
        $orderTotal += $item->price * $item->quantity;
    }
    
    foreach ($returnItems as $item) {
        $orderItem = $order->items()->find($item['order_item_id']);
        $returnTotal += $orderItem->price * $item['quantity_returned'];
    }
    
    // Apply proportional discount
    $discountRatio = $order->discount / $orderTotal;
    $proportionalDiscount = $returnTotal * $discountRatio;
    
    $refundAmount = $returnTotal - $proportionalDiscount;
    
    return round($refundAmount, 2);
}

// Example:
// Return 1x Product A (100,000đ)
// Discount ratio: 60,000 / 600,000 = 0.1 (10%)
// Proportional discount: 100,000 * 0.1 = 10,000đ
// Refund: 100,000 - 10,000 = 90,000đ ✅
```

**Solution 2: No Discount on Return (Simpler)**

```php
public function calculateRefund($order, $returnItems) {
    $refundAmount = 0;
    
    foreach ($returnItems as $item) {
        $orderItem = $order->items()->find($item['order_item_id']);
        $refundAmount += $orderItem->price * $item['quantity_returned'];
    }
    
    return $refundAmount;
}

// Example:
// Return 1x Product A (100,000đ)
// Refund: 100,000đ ✅ (no discount applied)
```

---

### **Problem: Shipping Fee Refund**

**Scenario:**

```
Order:
Subtotal: 500,000đ
Shipping: 30,000đ
Total: 530,000đ

Customer returns all items.
Should we refund shipping fee? 🤔
```

**Policy:**

- **Defective product**: YES (shop's fault)
- **Wrong item**: YES (shop's fault)
- **Customer changed mind**: NO (customer's fault)

**Implementation:**

```php
public function calculateRefund($return) {
    $order = $return->order;
    
    // Calculate item refund
    $itemRefund = 0;
    foreach ($return->returnItems as $item) {
        $itemRefund += $item->orderItem->price * $item->quantity_returned;
    }
    
    // Shipping fee refund policy
    $shippingRefund = 0;
    if (in_array($return->reason, ['defective', 'wrong_item'])) {
        // Check if returning all items
        $totalReturned = $return->returnItems->sum('quantity_returned');
        $totalOrdered = $order->items->sum('quantity');
        
        if ($totalReturned === $totalOrdered) {
            $shippingRefund = $order->shipping_fee;
        }
    }
    
    return $itemRefund + $shippingRefund;
}
```

---

## 💰 CURRENCY EDGE CASES

### **Problem: Currency Storage**

**Bad Practice:**

```php
// DON'T store as float
$order->total = 1000000.50; // Precision issues
```

**Good Practice:**

```php
// DO store as DECIMAL
CREATE TABLE orders (
    total DECIMAL(15,2) NOT NULL
);

// DO use bcmath for calculations
$subtotal = '1000000.50';
$discount = '100000.00';
$total = bcsub($subtotal, $discount, 2); // '900000.50'
```

---

### **Problem: Zero Amount Orders**

**Scenario:**

```
Subtotal: 100,000đ
Discount: 100,000đ (100% promo)
Total: 0đ

Is this a valid order? 🤔
```

**Solution: Allow Zero Total**

```php
// Update validation
if ($order->total < 0) {
    throw new ValidationException('Total cannot be negative');
}

// Zero is OK (free order)
if ($order->total === 0) {
    $order->update([
        'paid_amount' => 0,
        'is_paid' => true // Auto-mark as paid
    ]);
}
```

---

## 📊 REPORTING EDGE CASES

### **Problem: Revenue Calculation with Returns**

**Scenario:**

```
Month 1:
Orders: 10,000,000đ
Returns: 0đ
Revenue: 10,000,000đ ✅

Month 2:
Orders: 8,000,000đ
Returns: 2,000,000đ (from Month 1)
Revenue: 6,000,000đ? Or 8,000,000đ? 🤔
```

**Solution 1: Recognize Revenue When Completed**

```sql
SELECT 
    DATE_FORMAT(completed_at, '%Y-%m') as month,
    SUM(total) as gross_revenue,
    COALESCE(SUM(refund_amount), 0) as returns,
    SUM(total) - COALESCE(SUM(refund_amount), 0) as net_revenue
FROM orders o
LEFT JOIN returns r ON r.order_id = [o.id](http://o.id) AND r.status = 'completed'
WHERE o.status = 'completed'
GROUP BY DATE_FORMAT(completed_at, '%Y-%m');
```

**Solution 2: Separate Returns to Original Month**

```sql
-- Month 2 revenue includes Month 1 returns
SELECT 
    '2024-02' as month,
    SUM([o.total](http://o.total)) as orders,
    (
        SELECT SUM(refund_amount) 
        FROM returns r
        WHERE r.status = 'completed'
          AND DATE_FORMAT(r.completed_at, '%Y-%m') = '2024-02'
    ) as returns
FROM orders o
WHERE o.status = 'completed'
  AND DATE_FORMAT(o.completed_at, '%Y-%m') = '2024-02';
```

---

## 🔗 RELATED DOCUMENTS

- [**CONCURRENCY.md**](http://CONCURRENCY.md) - Concurrency edge cases
- [**DATA_](https://www.notion.so/DATA_INTEGRITY-Data-Integrity-Edge-Cases-a99dccea34224a7d8470b6c28c5e458d?pvs=21)[INTEGRITY.md](http://INTEGRITY.md)** - Data integrity edge cases
- **[[INVENTORY.md](http://INVENTORY.md)]** - Inventory edge cases