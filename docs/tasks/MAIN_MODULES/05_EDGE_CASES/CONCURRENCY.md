# Concurrency & Race Conditions

**Module:** Order Workflow

**Last Updated:** 2025-11-24

---

## 🎯 MỤC ĐÍCH

Document này phân tích **các edge cases về concurrency** và cách xử lý race conditions trong Order Workflow.

---

## 🔒 ORDER NUMBER GENERATION

### **Problem: Duplicate Order Numbers**

**Scenario:**

```
Time    User A                  User B
10:00   Query max order_number: ORD-1234
10:01                           Query max order_number: ORD-1234
10:02   Create order: ORD-1235
10:03                           Create order: ORD-1235 ❌ DUPLICATE
```

**Race Condition:** 2 requests cùng lúc → cùng counter → duplicate

---

### **Solution 1: Database Lock**

**Implementation:**

```php
public function generateOrderNumber() {
    return DB::transaction(function() {
        // Lock table to prevent concurrent access
        $lastOrder = Order::lockForUpdate()
            ->orderBy('id', 'desc')
            ->first();
        
        $counter = $lastOrder ? $lastOrder->id + 1 : 1;
        $orderNumber = sprintf("ORD-%06d", $counter);
        
        return $orderNumber;
    });
}
```

**Pros:**

- Thread-safe
- Guaranteed uniqueness

**Cons:**

- Performance bottleneck
- Locks entire table

---

### **Solution 2: Database Sequence (Recommended)**

**Implementation:**

```sql
-- Create sequence
CREATE SEQUENCE order_number_seq START WITH 1 INCREMENT BY 1;

-- Use in application
SELECT NEXTVAL('order_number_seq');
```

**PHP:**

```php
public function generateOrderNumber() {
    $counter = DB::selectOne("SELECT NEXTVAL('order_number_seq') as val")->val;
    return sprintf("ORD-%06d", $counter);
}
```

**Pros:**

- Best performance
- No locks needed
- Database-level guarantee

**Cons:**

- Requires database support (PostgreSQL, Oracle)
- MySQL: Use AUTO_INCREMENT

---

### **Solution 3: MySQL AUTO_INCREMENT**

**Schema:**

```sql
CREATE TABLE orders (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(50) UNIQUE GENERATED ALWAYS AS (CONCAT('ORD-', LPAD(id, 6, '0')))
);
```

**Implementation:**

```php
public function createOrder($data) {
    $order = Order::create($data);
    // order_number auto-generated from id
    return $order;
}
```

**Pros:**

- Simplest solution
- Database handles uniqueness
- No application logic needed

**Cons:**

- Cannot customize counter (always based on id)

---

## 💰 INVENTORY DEDUCTION

### **Problem: Overselling**

**Scenario:**

```
Current stock: 5 units

Time    Order A (qty=3)         Order B (qty=3)
10:00   Check stock: 5 ✅
10:01                           Check stock: 5 ✅
10:02   Deduct: 5 - 3 = 2
10:03                           Deduct: 2 - 3 = -1 ❌ NEGATIVE STOCK
```

**Race Condition:** 2 orders check stock simultaneously → both pass → overselling

---

### **Solution 1: Pessimistic Locking**

**Implementation:**

```php
public function deductInventory($productId, $variantId, $branchId, $quantity) {
    return DB::transaction(function() use ($productId, $variantId, $branchId, $quantity) {
        // Lock row
        $inventory = Inventory::where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->where('branch_id', $branchId)
            ->lockForUpdate()
            ->first();
        
        if (!$inventory || $inventory->quantity < $quantity) {
            throw new InsufficientStockException();
        }
        
        // Deduct
        $inventory->decrement('quantity', $quantity);
        
        return true;
    });
}
```

**Pros:**

- Guaranteed accuracy
- No overselling

**Cons:**

- Performance impact
- Locks during entire transaction

---

### **Solution 2: Optimistic Locking**

**Schema:**

```sql
ALTER TABLE inventory ADD COLUMN version INT DEFAULT 0;
```

**Implementation:**

```php
public function deductInventory($productId, $variantId, $branchId, $quantity) {
    $maxRetries = 3;
    
    for ($i = 0; $i < $maxRetries; $i++) {
        $inventory = Inventory::where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->where('branch_id', $branchId)
            ->first();
        
        if ($inventory->quantity < $quantity) {
            throw new InsufficientStockException();
        }
        
        $oldVersion = $inventory->version;
        
        // Try to update with version check
        $affected = Inventory::where('id', $inventory->id)
            ->where('version', $oldVersion)
            ->update([
                'quantity' => DB::raw('quantity - ' . $quantity),
                'version' => $oldVersion + 1
            ]);
        
        if ($affected > 0) {
            return true; // Success
        }
        
        // Retry if version mismatch
        usleep(100000); // 100ms
    }
    
    throw new ConcurrencyException('Failed to deduct inventory after retries');
}
```

**Pros:**

- Better performance
- No locks

**Cons:**

- Retry logic needed
- Can fail if high contention

---

### **Solution 3: Database Constraint**

**Schema:**

```sql
ALTER TABLE inventory ADD CONSTRAINT chk_quantity_nonnegative CHECK (quantity >= 0);
```

**Implementation:**

```php
public function deductInventory($productId, $variantId, $branchId, $quantity) {
    try {
        Inventory::where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->where('branch_id', $branchId)
            ->decrement('quantity', $quantity);
        
        return true;
    } catch (\PDOException $e) {
        // Check constraint violation
        if (strpos($e->getMessage(), 'chk_quantity_nonnegative') !== false) {
            throw new InsufficientStockException();
        }
        throw $e;
    }
}
```

**Pros:**

- Database-level guarantee
- Simple application logic

**Cons:**

- Error handling via exceptions
- Less clear error messages

---

## 🔢 INVOICE NUMBER GENERATION

### **Problem: Duplicate Invoice Numbers**

**Scenario:**

```
Branch 1, current max: HD-1-0100

Time    Admin A                 Admin B
10:00   Query max: HD-1-0100
10:01                           Query max: HD-1-0100
10:02   Create: HD-1-0101
10:03                           Create: HD-1-0101 ❌ DUPLICATE
```

---

### **Solution: Transaction Lock**

**Implementation:**

```php
public function generateInvoiceNumber($branchId) {
    return DB::transaction(function() use ($branchId) {
        // Lock invoices for this branch
        $counter = Invoice::where('branch_id', $branchId)
            ->lockForUpdate()
            ->count() + 1;
        
        $invoiceNumber = sprintf("HD-%d-%04d", $branchId, $counter);
        
        // Create invoice immediately
        $invoice = Invoice::create([
            'invoice_number' => $invoiceNumber,
            'branch_id' => $branchId,
            // ...
        ]);
        
        return $invoiceNumber;
    });
}
```

**Key Points:**

- Lock per branch (not entire table)
- Create invoice in same transaction
- Release lock after commit

---

## 💳 PAYMENT PROCESSING

### **Problem: Double Payment**

**Scenario:**

```
Order total: 1,000,000đ

Time    Admin A                 Admin B
10:00   Mark as paid: 1,000,000đ
10:01                           Mark as paid: 1,000,000đ
10:02   paid_amount = 1,000,000đ
10:03                           paid_amount = 2,000,000đ ❌ OVERPAID
```

---

### **Solution: Atomic Increment**

**Implementation:**

```php
public function addPayment($orderId, $amount) {
    return DB::transaction(function() use ($orderId, $amount) {
        $order = Order::where('id', $orderId)
            ->lockForUpdate()
            ->first();
        
        $newPaid = $order->paid_amount + $amount;
        
        if ($newPaid > $order->total) {
            throw new ValidationException('Payment exceeds total');
        }
        
        $order->update([
            'paid_amount' => $newPaid,
            'is_paid' => ($newPaid >= $order->total)
        ]);
        
        return $order;
    });
}
```

**Alternative: Database Increment**

```php
Order::where('id', $orderId)
    ->where(DB::raw('paid_amount + ?'), '<=', 'total')
    ->increment('paid_amount', $amount);
```

---

## 🔄 RETURN PROCESSING

### **Problem: Over-Return**

**Scenario:**

```
Order: 5x iPhone

Time    Return A (qty=3)        Return B (qty=3)
10:00   Check returnable: 5 ✅
10:01                           Check returnable: 5 ✅
10:02   Create return: 3
10:03                           Create return: 3
10:04   Total returned: 6 > 5 ❌ OVER-RETURNED
```

---

### **Solution: Pessimistic Lock**

**Implementation:**

```php
public function createReturn($orderId, $items) {
    return DB::transaction(function() use ($orderId, $items) {
        $order = Order::where('id', $orderId)
            ->lockForUpdate()
            ->first();
        
        foreach ($items as $item) {
            $orderItem = $order->items()->find($item['order_item_id']);
            
            // Calculate already returned
            $alreadyReturned = ReturnItem::whereHas('return', function($q) use ($orderId) {
                    $q->where('order_id', $orderId)
                      ->whereIn('status', ['approved', 'completed']);
                })
                ->where('order_item_id', $orderItem->id)
                ->lockForUpdate()
                ->sum('quantity_returned');
            
            $returnable = $orderItem->quantity - $alreadyReturned;
            
            if ($item['quantity_returned'] > $returnable) {
                throw new ValidationException('Cannot return more than purchased');
            }
        }
        
        // Create return
        $return = Return::create([
            'order_id' => $orderId,
            // ...
        ]);
        
        return $return;
    });
}
```

---

## 📊 STATUS UPDATES

### **Problem: Status Race Condition**

**Scenario:**

```
Order status: confirmed

Time    Admin A                 Admin B
10:00   Update: processing
10:01                           Update: cancelled
10:02   Status = processing
10:03                           Status = cancelled
```

**Result:** Admin A's update lost (last write wins)

---

### **Solution: Transition Validation**

**Implementation:**

```php
public function updateStatus($orderId, $newStatus) {
    return DB::transaction(function() use ($orderId, $newStatus) {
        $order = Order::where('id', $orderId)
            ->lockForUpdate()
            ->first();
        
        $oldStatus = $order->status;
        
        // Validate transition
        if (!$this->isValidTransition($oldStatus, $newStatus)) {
            throw new InvalidStatusTransitionException(
                "Cannot transition from {$oldStatus} to {$newStatus}"
            );
        }
        
        // Update
        $order->update(['status' => $newStatus]);
        
        // Log
        OrderStatusLog::create([
            'order_id' => $orderId,
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'changed_by' => auth()->id()
        ]);
        
        return $order;
    });
}
```

---

## ⚠️ BEST PRACTICES

### **1. Use Transactions**

```php
// BAD: No transaction
$order->update(['status' => 'completed']);
$inventory->increment('quantity', 10);

// GOOD: Atomic transaction
DB::transaction(function() use ($order, $inventory) {
    $order->update(['status' => 'completed']);
    $inventory->increment('quantity', 10);
});
```

---

### **2. Lock Only What You Need**

```php
// BAD: Lock entire table
DB::table('orders')->lockForUpdate()->get();

// GOOD: Lock specific row
Order::where('id', $orderId)->lockForUpdate()->first();
```

---

### **3. Keep Transactions Short**

```php
// BAD: Long transaction
DB::transaction(function() {
    $order = Order::create($data);
    
    // API call - can take 5 seconds!
    $this->notifyCustomer($order);
    
    $order->update(['notified' => true]);
});

// GOOD: Move slow operations outside
DB::transaction(function() use ($data) {
    $order = Order::create($data);
    return $order;
});

// Outside transaction
$this->notifyCustomer($order);
```

---

### **4. Handle Deadlocks**

```php
public function updateOrderWithRetry($orderId, $data, $maxRetries = 3) {
    for ($i = 0; $i < $maxRetries; $i++) {
        try {
            return DB::transaction(function() use ($orderId, $data) {
                $order = Order::where('id', $orderId)
                    ->lockForUpdate()
                    ->first();
                
                $order->update($data);
                return $order;
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Deadlock detected
            if ($e->getCode() === '40001' || strpos($e->getMessage(), 'Deadlock') !== false) {
                if ($i === $maxRetries - 1) {
                    throw $e;
                }
                usleep(100000 * ($i + 1)); // Exponential backoff
                continue;
            }
            throw $e;
        }
    }
}
```

---

## 🔗 RELATED DOCUMENTS

- **[DATA_[INTEGRITY.md](http://INTEGRITY.md)]** - Data integrity edge cases
- **[[FINANCIAL.md](http://FINANCIAL.md)]** - Financial edge cases
- **[[INVENTORY.md](http://INVENTORY.md)]** - Inventory edge cases