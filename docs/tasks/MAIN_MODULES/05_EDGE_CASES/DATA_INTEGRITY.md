# Data Integrity Edge Cases

**Module:** Order Workflow

**Last Updated:** 2025-11-24

---

## 🎯 MỤC ĐÍCH

Document này phân tích **các edge cases về data integrity** trong Order Workflow.

---

## 🔗 FOREIGN KEY VIOLATIONS

### **Problem: Deleting Referenced Customer**

**Scenario:**

```sql
-- Customer has orders
DELETE FROM customers WHERE id = 123;
-- Error: Cannot delete because orders reference this customer
```

**FK Constraint:**

```sql
FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT
```

---

### **Solution 1: Soft Delete (Recommended)**

**Schema:**

```sql
ALTER TABLE customers ADD COLUMN deleted_at TIMESTAMP NULL;
```

**Implementation:**

```php
// Soft delete
$customer->delete(); // Sets deleted_at

// Query excludes soft-deleted
Customer::all(); // WHERE deleted_at IS NULL

// Include soft-deleted
Customer::withTrashed()->get();

// Restore
$customer->restore();
```

**Benefits:**

- Preserves historical data
- Orders still valid
- Can restore if needed

---

### **Solution 2: Archive Customer**

**Schema:**

```sql
ALTER TABLE customers ADD COLUMN is_active BOOLEAN DEFAULT TRUE;
```

**Implementation:**

```php
// Archive instead of delete
$customer->update(['is_active' => false]);

// Prevent new orders
if (!$customer->is_active) {
    throw new ValidationException('Customer is inactive');
}
```

---

## 💰 FINANCIAL CALCULATIONS

### **Problem: Floating Point Precision**

**Scenario:**

```php
$price = 10.50;
$quantity = 3;
$total = $price * $quantity; // 31.5
$discount = 0.1; // 10%
$final = $total - ($total * $discount);
// Expected: 28.35
// Actual: 28.349999999999998 ❌
```

---

### **Solution: Use DECIMAL Type**

**Schema:**

```sql
CREATE TABLE orders (
    subtotal DECIMAL(15,2) NOT NULL,
    discount DECIMAL(15,2) DEFAULT 0,
    total DECIMAL(15,2) NOT NULL
);
```

**PHP:**

```php
// Use bcmath for precision
$subtotal = '31.50';
$discount = '3.15';
$total = bcsub($subtotal, $discount, 2); // '28.35'

// Or cast to string
$order->subtotal = (string) 31.50;
```

**Validation:**

```php
// Allow 0.01 difference for rounding
if (abs($calculated - $stored) > 0.01) {
    throw new ValidationException('Amount mismatch');
}
```

---

## 📊 CALCULATED FIELDS

### **Problem: debt_amount Out of Sync**

**Scenario:**

```sql
-- Order: total = 1,000,000, paid_amount = 300,000
-- Expected debt: 700,000
-- But debt_amount shows: 500,000 ❌
```

**Reason:** Direct UPDATE bypassed calculation

---

### **Solution 1: Generated Column (Recommended)**

**Schema:**

```sql
CREATE TABLE orders (
    total DECIMAL(15,2) NOT NULL,
    paid_amount DECIMAL(15,2) DEFAULT 0,
    debt_amount DECIMAL(15,2) AS (total - paid_amount) STORED
);
```

**Benefits:**

- Always accurate
- Database-level guarantee
- No application logic needed

**Limitation:** Cannot set debt_amount directly

---

### **Solution 2: Database Trigger**

**Implementation:**

```sql
CREATE TRIGGER update_debt_amount
BEFORE UPDATE ON orders
FOR EACH ROW
BEGIN
    SET NEW.debt_amount = [NEW.total](http://NEW.total) - NEW.paid_amount;
END;
```

**Benefits:**

- Always accurate
- Works with raw SQL updates

---

### **Solution 3: Application Observers**

**PHP:**

```php
class OrderObserver
{
    public function saving(Order $order)
    {
        // Recalculate debt before saving
        $order->debt_amount = $order->total - $order->paid_amount;
    }
}

// Register observer
Order::observe(OrderObserver::class);
```

**Limitation:** Only works when using Eloquent

---

## 🔢 UNIQUE CONSTRAINTS

### **Problem: order_number NULL Duplicates**

**Scenario:**

```sql
-- MySQL allows multiple NULL values in UNIQUE column
INSERT INTO orders (order_number) VALUES (NULL);
INSERT INTO orders (order_number) VALUES (NULL); -- ✅ Allowed!
```

---

### **Solution: NOT NULL + UNIQUE**

**Schema:**

```sql
CREATE TABLE orders (
    order_number VARCHAR(50) UNIQUE NOT NULL
);
```

**Application:**

```php
// Generate order_number immediately on create
public function createOrder($data) {
    $orderNumber = $this->generateOrderNumber();
    
    return Order::create([
        'order_number' => $orderNumber,
        // ...
    ]);
}
```

---

## 📅 TIMESTAMP EDGE CASES

### **Problem: created_at vs actual creation time**

**Scenario:**

```php
// User starts creating order at 10:00
$order = new Order($data);

// Network delay... 5 minutes pass

// Order saved at 10:05
$order->save();
// created_at = 10:05, not 10:00
```

---

### **Solution: Explicit Timestamps**

```php
public function createOrder($data) {
    $now = now(); // Capture current time
    
    $order = Order::create([
        'created_at' => $now,
        'updated_at' => $now,
        // ...
    ]);
    
    return $order;
}
```

---

## 🗑️ CASCADING DELETES

### **Problem: Unintended Data Loss**

**Scenario:**

```sql
-- Delete order
DELETE FROM orders WHERE id = 123;

-- CASCADE deletes order_items
-- CASCADE deletes order_status_logs
-- But we wanted to keep logs! ❌
```

---

### **Solution: Strategic CASCADE vs RESTRICT**

**Good Cascades (Dependent Data):**

```sql
-- order_items depend on order
FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE

-- invoice_orders is junction table
FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
```

**Use RESTRICT (Important Data):**

```sql
-- Don't delete customer if has orders
FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT

-- Don't delete order if has returns
FOREIGN KEY (order_id) REFERENCES returns(id) ON DELETE RESTRICT
```

**Manual Handling (Logs):**

```php
public function deleteOrder($orderId) {
    // Archive logs before deleting
    $logs = OrderStatusLog::where('order_id', $orderId)->get();
    ArchivedLog::insert($logs->toArray());
    
    // Then delete
    Order::destroy($orderId);
}
```

---

## 🔄 UPDATE ANOMALIES

### **Problem: Lost Update**

**Scenario:**

```
Order: subtotal = 1,000,000, discount = 0, total = 1,000,000

Time    User A                  User B
10:00   Read order (total=1M)
10:01                           Read order (total=1M)
10:02   Set discount = 100k
        Calculate total = 900k
        Save (total=900k)
10:03                           Set subtotal = 1.1M
                                Calculate total = 1.1M
                                Save (total=1.1M) ❌
```

**Result:** User A's discount lost

---

### **Solution: Optimistic Locking**

**Schema:**

```sql
ALTER TABLE orders ADD COLUMN version INT DEFAULT 0;
```

**Implementation:**

```php
public function updateOrder($orderId, $data) {
    $order = Order::find($orderId);
    $oldVersion = $order->version;
    
    // Update with version check
    $affected = Order::where('id', $orderId)
        ->where('version', $oldVersion)
        ->update([
            'subtotal' => $data['subtotal'],
            'discount' => $data['discount'],
            'total' => $data['subtotal'] - $data['discount'],
            'version' => $oldVersion + 1
        ]);
    
    if ($affected === 0) {
        throw new ConcurrentUpdateException('Order was modified by another user');
    }
}
```

---

## 📦 INVENTORY INCONSISTENCIES

### **Problem: Inventory vs Order Items Mismatch**

**Scenario:**

```
Inventory: 100 units
Order items: 120 units sold

Mismatch: Where did 20 units come from? ❌
```

**Causes:**

- Manual inventory adjustments
- Failed inventory deductions
- Data corruption

---

### **Solution: Inventory Audit**

**Query:**

```sql
-- Calculate sold quantity from orders
SELECT 
    product_id,
    variant_id,
    SUM(quantity) as sold_quantity
FROM order_items oi
JOIN orders o ON [o.id](http://o.id) = oi.order_id
WHERE o.status IN ('completed', 'processing', 'shipping')
GROUP BY product_id, variant_id;

-- Compare with inventory movements
SELECT 
    product_id,
    variant_id,
    SUM(quantity) as total_movements
FROM inventory_movements
WHERE type = 'sale'
GROUP BY product_id, variant_id;
```

**Validation:**

```php
public function auditInventory($productId, $variantId, $branchId) {
    // Sold quantity
    $sold = OrderItem::whereHas('order', function($q) use ($branchId) {
            $q->where('branch_id', $branchId)
              ->whereIn('status', ['completed', 'processing', 'shipping']);
        })
        ->where('product_id', $productId)
        ->where('variant_id', $variantId)
        ->sum('quantity');
    
    // Movements
    $movements = InventoryMovement::where('product_id', $productId)
        ->where('variant_id', $variantId)
        ->where('branch_id', $branchId)
        ->sum('quantity');
    
    // Current stock
    $currentStock = Inventory::where('product_id', $productId)
        ->where('variant_id', $variantId)
        ->where('branch_id', $branchId)
        ->value('quantity');
    
    // Calculate expected
    $expected = $movements; // Assuming movements start from 0
    
    if ($currentStock !== $expected) {
        logger()->warning("Inventory mismatch: expected {$expected}, actual {$currentStock}");
        return false;
    }
    
    return true;
}
```

---

## 🔐 DATA CORRUPTION PREVENTION

### **1. Database Constraints**

```sql
-- NOT NULL for required fields
total DECIMAL(15,2) NOT NULL

-- CHECK constraints
CONSTRAINT chk_total_positive CHECK (total >= 0)
CONSTRAINT chk_discount_valid CHECK (discount <= subtotal)

-- UNIQUE constraints
UNIQUE (order_number)

-- FOREIGN KEY constraints
FOREIGN KEY (customer_id) REFERENCES customers(id)
```

---

### **2. Application Validation**

```php
public function updateOrder($orderId, $data) {
    // Validate BEFORE save
    if ($data['discount'] > $data['subtotal']) {
        throw new ValidationException('Discount cannot exceed subtotal');
    }
    
    if ($data['paid_amount'] > $data['total']) {
        throw new ValidationException('Paid amount cannot exceed total');
    }
    
    // Calculate total
    $calculatedTotal = $data['subtotal'] - $data['discount'] + $data['shipping_fee'];
    if (abs($data['total'] - $calculatedTotal) > 0.01) {
        throw new ValidationException('Total amount mismatch');
    }
    
    // Save
    Order::where('id', $orderId)->update($data);
}
```

---

### **3. Audit Logging**

```php
class AuditLog extends Model
{
    protected $fillable = [
        'model_type',
        'model_id',
        'action',
        'old_values',
        'new_values',
        'user_id',
        'ip_address'
    ];
}

// Observer
class OrderObserver
{
    public function updated(Order $order)
    {
        AuditLog::create([
            'model_type' => Order::class,
            'model_id' => $order->id,
            'action' => 'updated',
            'old_values' => json_encode($order->getOriginal()),
            'new_values' => json_encode($order->getAttributes()),
            'user_id' => auth()->id(),
            'ip_address' => request()->ip()
        ]);
    }
}
```

---

## 🔗 RELATED DOCUMENTS

- **[[CONCURRENCY.md](http://CONCURRENCY.md)]** - Concurrency edge cases
- **[[FINANCIAL.md](http://FINANCIAL.md)]** - Financial edge cases
- **[[INVENTORY.md](http://INVENTORY.md)]** - Inventory edge cases