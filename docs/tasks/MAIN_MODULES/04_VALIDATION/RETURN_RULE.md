# RETURN_RULES - Return Validation Rules

# Return Validation Rules

**Module:** Order Workflow

**Related Tasks:** RETURN-001, RETURN-002

**Last Updated:** 2025-11-24

---

## 🎯 MỤC ĐÍCH

Document này định nghĩa **toàn bộ validation rules** cho Returns module.

---

## 📋 RETURN CREATION RULES

### **RULE-RET-001: Order Required**

**Rule:** Mọi return PHẢI liên kết với 1 order

**Validation:**

```php
if (empty($data['order_id'])) {
    throw new ValidationException('Order is required');
}

$order = Order::find($data['order_id']);
if (!$order) {
    throw new ValidationException('Order not found');
}
```

**Error Code:** `RET_ORDER_REQUIRED`

**HTTP Status:** 400 Bad Request

---

### **RULE-RET-002: Order Must Be Completed**

**Rule:** Chỉ cho phép return order có status = `completed`

**Validation:**

```php
if ($order->status !== 'completed') {
    throw new ValidationException('Can only return completed orders');
}
```

**Error Code:** `RET_ORDER_NOT_COMPLETED`

**Business Rule:** Phải đợi order hoàn thành mới cho phép return

---

### **RULE-RET-003: Within Return Window**

**Rule:** Return PHẢI trong vòng X ngày sau khi order completed

**Validation:**

```php
$returnWindowDays = 30; // Configurable

$completedDate = $order->completed_at;
$daysSinceCompleted = now()->diffInDays($completedDate);

if ($daysSinceCompleted > $returnWindowDays) {
    throw new ValidationException("Return window expired (max {$returnWindowDays} days)");
}
```

**Error Code:** `RET_WINDOW_EXPIRED`

**Default:** 30 days

---

### **RULE-RET-004: At Least One Item**

**Rule:** Return PHẢI có ít nhất 1 item

**Validation:**

```php
if (empty($data['items']) || count($data['items']) === 0) {
    throw new ValidationException('Return must have at least one item');
}
```

**Error Code:** `RET_NO_ITEMS`

---

### **RULE-RET-005: Item Belongs to Order**

**Rule:** Mỗi return_item PHẢI reference đến order_item của order đó

**Validation:**

```php
foreach ($data['items'] as $item) {
    $orderItem = OrderItem::where('id', $item['order_item_id'])
        ->where('order_id', $data['order_id'])
        ->first();
        
    if (!$orderItem) {
        throw new ValidationException("Order item #{$item['order_item_id']} not found in this order");
    }
}
```

**Error Code:** `RET_ITEM_NOT_IN_ORDER`

---

### **RULE-RET-006: Quantity Returnable**

**Rule:** quantity_returned KHÔNG vượt quá số lượng có thể return

**Validation:**

```php
foreach ($data['items'] as $item) {
    $orderItem = OrderItem::find($item['order_item_id']);
    
    // Calculate already returned quantity
    $alreadyReturned = ReturnItem::whereHas('return', function($q) {
            $q->whereIn('status', ['approved', 'completed']);
        })
        ->where('order_item_id', $orderItem->id)
        ->sum('quantity_returned');
    
    $returnable = $orderItem->quantity - $alreadyReturned;
    
    if ($item['quantity_returned'] > $returnable) {
        throw new ValidationException("Cannot return more than {$returnable} units");
    }
}
```

**Error Code:** `RET_QUANTITY_EXCEEDED`

---

### **RULE-RET-007: Quantity Positive**

**Rule:** quantity_returned PHẢI > 0

**Validation:**

```php
foreach ($data['items'] as $item) {
    if ($item['quantity_returned'] <= 0) {
        throw new ValidationException('Return quantity must be positive');
    }
}
```

**Error Code:** `RET_QUANTITY_INVALID`

---

### **RULE-RET-008: Reason Required**

**Rule:** return reason PHẢI được chọn

**Valid Reasons:**

- `defective` - Hàng lỗi
- `wrong_item` - Giao nhầm
- `not_satisfied` - Không ưng ý
- `other` - Lý do khác

**Validation:**

```php
$validReasons = ['defective', 'wrong_item', 'not_satisfied', 'other'];

if (empty($data['reason']) || !in_array($data['reason'], $validReasons)) {
    throw new ValidationException('Valid return reason is required');
}

if ($data['reason'] === 'other' && empty($data['reason_detail'])) {
    throw new ValidationException('Reason detail is required for "other" reason');
}
```

**Error Code:** `RET_REASON_REQUIRED` / `RET_REASON_DETAIL_REQUIRED`

---

## 💰 RETURN FINANCIAL RULES

### **RULE-RET-009: Return Amount Valid**

**Rule:** return_amount PHẢI = tổng tiền của items trả

**Validation:**

```php
$calculatedAmount = 0;

foreach ($return->returnItems as $item) {
    $orderItem = $item->orderItem;
    $calculatedAmount += $orderItem->price * $item->quantity_returned;
}

if (abs($return->return_amount - $calculatedAmount) > 0.01) {
    throw new ValidationException('Return amount mismatch');
}
```

**Error Code:** `RET_AMOUNT_MISMATCH`

---

### **RULE-RET-010: Refund Amount Valid**

**Rule:** refund_amount PHẢI >= return_amount

**Formula:**

```
refund_amount = return_amount + (shipping_fee IF refund_shipping_fee)
```

**Validation:**

```php
$expectedRefund = $return->return_amount;

if ($return->refund_shipping_fee) {
    $expectedRefund += $return->order->shipping_fee;
}

if (abs($return->refund_amount - $expectedRefund) > 0.01) {
    throw new ValidationException('Refund amount mismatch');
}
```

**Error Code:** `RET_REFUND_AMOUNT_MISMATCH`

---

### **RULE-RET-011: Refund Shipping Policy**

**Rule:** refund_shipping_fee chỉ cho phép nếu lỗi shop

**Policy:**

- `defective`: TRUE (shop ship hàng lỗi)
- `wrong_item`: TRUE (shop ship nhầm)
- `not_satisfied`: FALSE (khách đổi ý)
- `other`: Admin quyết định

**Validation:**

```php
if ($data['refund_shipping_fee']) {
    $allowedReasons = ['defective', 'wrong_item'];
    
    if (!in_array($return->reason, $allowedReasons)) {
        // Require admin approval
        if (empty($data['admin_approved'])) {
            throw new ValidationException('Refund shipping requires admin approval');
        }
    }
}
```

**Error Code:** `RET_REFUND_SHIPPING_NOT_ALLOWED`

---

## 🔄 RETURN STATUS RULES

### **RULE-RET-012: Status Transition Valid**

**Rule:** Chỉ cho phép chuyển status theo workflow

**Valid Transitions:**

```
pending → approved
pending → rejected
approved → completed
```

**Terminal States:** `rejected`, `completed`

**Validation:**

```php
$validTransitions = [
    'pending' => ['approved', 'rejected'],
    'approved' => ['completed'],
];

$fromStatus = $return->status;
$toStatus = $newStatus;

if (!isset($validTransitions[$fromStatus]) || 
    !in_array($toStatus, $validTransitions[$fromStatus])) {
    throw new ValidationException("Cannot transition from {$fromStatus} to {$toStatus}");
}
```

**Error Code:** `RET_INVALID_STATUS_TRANSITION`

---

### **RULE-RET-013: Cannot Edit After Approved**

**Rule:** Không thể edit return sau khi approved/rejected/completed

**Validation:**

```php
if (in_array($return->status, ['approved', 'rejected', 'completed'])) {
    throw new ValidationException('Cannot modify return after approval decision');
}
```

**Error Code:** `RET_LOCKED`

---

### **RULE-RET-014: Approval Requires Admin**

**Rule:** Chỉ admin mới approve/reject return

**Validation:**

```php
if (!$currentUser->hasRole('admin')) {
    throw new ValidationException('Only admins can approve/reject returns');
}
```

**Error Code:** `RET_ADMIN_REQUIRED`

---

### **RULE-RET-015: Refund Info Required on Approval**

**Rule:** Khi approve, PHẢI set refund_amount và refund_method

**Validation:**

```php
if ($newStatus === 'approved') {
    if (empty($data['refund_amount'])) {
        throw new ValidationException('Refund amount is required');
    }
    
    if (empty($data['refund_method'])) {
        throw new ValidationException('Refund method is required');
    }
    
    $validMethods = ['cash', 'bank_transfer'];
    if (!in_array($data['refund_method'], $validMethods)) {
        throw new ValidationException('Invalid refund method');
    }
}
```

**Error Code:** `RET_REFUND_INFO_REQUIRED`

---

## 📦 RETURN COMPLETION RULES

### **RULE-RET-016: Complete Requires Items Received**

**Rule:** Chỉ set status = `completed` khi đã nhận hàng về kho

**Validation:**

```php
if ($newStatus === 'completed') {
    if (!$data['items_received_confirmed']) {
        throw new ValidationException('Must confirm items received before completing return');
    }
}
```

**Error Code:** `RET_ITEMS_NOT_RECEIVED`

---

### **RULE-RET-017: Restock on Complete**

**Rule:** Khi complete, PHẢI restock inventory

**Implementation:**

```php
if ($newStatus === 'completed' && $oldStatus !== 'completed') {
    foreach ($return->returnItems as $item) {
        $orderItem = $item->orderItem;
        
        // Restock
        Inventory::where('branch_id', $return->order->branch_id)
            ->where('product_id', $orderItem->product_id)
            ->where('variant_id', $orderItem->variant_id)
            ->increment('quantity', $item->quantity_returned);
        
        // Log movement
        InventoryMovement::create([
            'branch_id' => $return->order->branch_id,
            'product_id' => $orderItem->product_id,
            'variant_id' => $orderItem->variant_id,
            'type' => 'return',
            'quantity' => +$item->quantity_returned,
            'reference_type' => 'return',
            'reference_id' => $return->id,
            'created_by' => auth()->id()
        ]);
    }
}
```

---

## 🗑️ DELETE RULES

### **RULE-RET-018: Cannot Delete After Pending**

**Rule:** Chỉ cho phép xóa return ở status pending

**Validation:**

```php
if ($return->status !== 'pending') {
    throw new ValidationException('Can only delete pending returns');
}
```

**Error Code:** `RET_DELETE_NOT_PENDING`

**Alternative:** Dùng soft delete

---

## ⚠️ BUSINESS LOGIC CONSTRAINTS

### **CONSTRAINT-001: Return Number Uniqueness**

**Rule:** return_number PHẢI unique trong toàn hệ thống

**Format:** `TH-{order_id}-{counter}`

**Implementation:** Database UNIQUE constraint + application check

---

### **CONSTRAINT-002: Multiple Partial Returns**

**Rule:** Cho phép return nhiều lần cho cùng 1 order (partial returns)

**Example:**

```
Order #123: Mua 5x iPhone
Return #1: Trả 2x iPhone
Return #2: Trả 1x iPhone (còn 2x chưa trả)
```

---

### **CONSTRAINT-003: No Return After Full Return**

**Rule:** KHÔNG cho phép return nếu đã return hết

**Validation:**

```php
$totalOrdered = $order->items->sum('quantity');

$totalReturned = ReturnItem::whereHas('return', function($q) use ($order) {
        $q->where('order_id', $order->id)
          ->whereIn('status', ['approved', 'completed']);
    })
    ->sum('quantity_returned');

if ($totalReturned >= $totalOrdered) {
    throw new ValidationException('All items have been returned');
}
```

**Error Code:** `RET_FULLY_RETURNED`

---

### **CONSTRAINT-004: Condition Tracking**

**Rule:** Staff ghi nhận condition khi confirm hàng về

**Valid Conditions:**

- `new` - Mới nguyên
- `used` - Đã sử dụng
- `damaged` - Hỏng

**Note:** Condition KHÔNG ảnh hưởng restock logic (luôn restock)

---

## 🔗 RELATED DOCUMENTS

- [**RETURNS_](https://www.notion.so/RETURNS_TABLES-Returns-Schema-4a31069a3d7f44999f9b01f61ed53b1a?pvs=21)[TABLES.md](http://TABLES.md)** - Returns schema
- [**RETURN_](https://www.notion.so/RETURN_FLOW-Return-Orders-Workflow-7f5c5a4af4054c22a265d011c56b10e3?pvs=21)[FLOW.md](http://FLOW.md)** - Return workflow
- **Business Decisions:** #23-33