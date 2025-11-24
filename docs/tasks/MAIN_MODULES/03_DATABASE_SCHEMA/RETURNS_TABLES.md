# Returns Tables Schema

**Module:** Order Workflow

**Related Tasks:** RETURN-001, RETURN-002

**Last Updated:** 2025-11-24

---

## 🎯 MụC ĐÍCH

Document này mô tả chi tiết schema cho **2 tables**:

1. **returns** - Đơn trả hàng
2. **return_items** - Chi tiết items trả

---

## 📋 TABLE 1: returns

### **Definition**

```sql
CREATE TABLE returns (
  -- Primary Key
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  
  -- Identification
  return_number VARCHAR(50) UNIQUE NOT NULL,
  
  -- Relationships
  order_id BIGINT UNSIGNED NOT NULL,
  customer_id BIGINT UNSIGNED NOT NULL,
  
  -- Financials
  return_amount DECIMAL(15,2) NOT NULL,
  refund_shipping_fee BOOLEAN DEFAULT FALSE,
  refund_amount DECIMAL(15,2),
  refund_method ENUM('cash', 'bank_transfer'),
  
  -- Return Details
  reason ENUM('defective', 'wrong_item', 'not_satisfied', 'other') NOT NULL,
  reason_detail TEXT,
  
  -- Status
  status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
  
  -- Approval
  approved_by BIGINT UNSIGNED,
  approved_at TIMESTAMP,
  rejected_by BIGINT UNSIGNED,
  rejected_at TIMESTAMP,
  
  -- Completion
  completed_at TIMESTAMP,
  
  -- Additional Info
  notes TEXT,
  
  -- Audit
  created_by BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Indexes
  INDEX idx_order (order_id),
  INDEX idx_customer (customer_id),
  INDEX idx_status (status, created_at),
  INDEX idx_return_number (return_number),
  
  -- Foreign Keys
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE RESTRICT,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
  FOREIGN KEY (created_by) REFERENCES users(id),
  FOREIGN KEY (approved_by) REFERENCES users(id),
  FOREIGN KEY (rejected_by) REFERENCES users(id),
  
  -- Constraints
  CONSTRAINT chk_refund_amount_positive CHECK (refund_amount >= 0)
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### **Field Descriptions**

### **Primary Key**

**id** `BIGINT UNSIGNED AUTO_INCREMENT`

- Primary key

---

### **Identification**

**return_number** `VARCHAR(50) UNIQUE NOT NULL`

- Mã đơn trả hàng
- Format: `TH-{order_id}-{auto_increment}`
- Examples:
    - `TH-123-1` (order 123, return đầu tiên)
    - `TH-123-2` (order 123, return thứ 2)
    - `TH-456-1` (order 456, return đầu tiên)
- UNIQUE constraint
- Counter: Per order

---

### **Relationships**

**order_id** `BIGINT UNSIGNED NOT NULL`

- FK to [`orders.id`](http://orders.id)
- NOT NULL
- ON DELETE RESTRICT
- Mỗi return liên kết với 1 order

**customer_id** `BIGINT UNSIGNED NOT NULL`

- FK to [`customers.id`](http://customers.id)
- NOT NULL
- ON DELETE RESTRICT
- Thường là customer của order

---

### **Financials**

**return_amount** `DECIMAL(15,2) NOT NULL`

- Tổng tiền hàng trả
- Formula: `SUM(return_items.quantity * order_item.price)`
- NOT NULL
- Không bao gồm shipping fee

**refund_shipping_fee** `BOOLEAN`

- Có hoàn phí ship không?
- Default: FALSE
- Admin quyết định khi approve
- Policy:
    - Lỗi shop: TRUE
    - Khách đổi ý: FALSE

**refund_amount** `DECIMAL(15,2)`

- Tổng tiền hoàn lại
- Formula: `return_amount + (shipping_fee IF refund_shipping_fee)`
- NULL cho đến khi approved
- CHECK: `refund_amount >= 0`

**refund_method** `ENUM('cash', 'bank_transfer')`

- Phương thức hoàn tiền
- `cash`: Hoàn tiền mặt
- `bank_transfer`: Chuyển khoản
- NULL cho đến khi approved
- Phase 1: Không support store_credit, voucher

---

### **Return Details**

**reason** `ENUM(...) NOT NULL`

- Lý do trả:
    - `defective`: Hàng lỗi
    - `wrong_item`: Giao nhầm
    - `not_satisfied`: Không ưng ý
    - `other`: Lý do khác
- NOT NULL

**reason_detail** `TEXT`

- Chi tiết lý do
- Required nếu reason = 'other'
- Example: "Màn hình bị vỡ"

---

### **Status**

**status** `ENUM(...)`

- 4 statuses:
    - `pending`: Chờ admin duyệt
    - `approved`: Admin đồng ý
    - `rejected`: Admin từ chối
    - `completed`: Hàng đã về kho, đã restock
- Default: `pending`

---

### **Approval**

**approved_by** `BIGINT UNSIGNED`

- User ID của admin approve
- FK to [`users.id`](http://users.id)
- NULL cho đến khi approved

**approved_at** `TIMESTAMP`

- Thời điểm approve
- NULL cho đến khi approved

**rejected_by** `BIGINT UNSIGNED`

- User ID của admin reject
- FK to [`users.id`](http://users.id)
- NULL nếu không rejected

**rejected_at** `TIMESTAMP`

- Thời điểm reject
- NULL nếu không rejected

---

### **Completion**

**completed_at** `TIMESTAMP`

- Thời điểm staff confirm hàng đã về kho
- NULL cho đến khi completed
- **⚡ Trigger restock inventory**

---

### **Additional Info**

**notes** `TEXT`

- Ghi chú của admin
- Example: "Đồng ý trả hàng, hoàn tiền trong 3 ngày"
- NULL nếu không có

---

### **Audit**

**created_by** `BIGINT UNSIGNED NOT NULL`

- User ID của người tạo return request
- FK to [`users.id`](http://users.id)
- Có thể là customer hoặc staff

**created_at** `TIMESTAMP`

- Thời điểm tạo return request

**updated_at** `TIMESTAMP`

- Thời điểm update cuối

---

### **Sample Data**

```sql
INSERT INTO returns (
  return_number,
  order_id,
  customer_id,
  return_amount,
  refund_shipping_fee,
  refund_amount,
  refund_method,
  reason,
  reason_detail,
  status,
  approved_by,
  approved_at,
  created_by
) VALUES (
  'TH-123-1',
  123,
  456,
  500000,
  TRUE,  -- hoàn phí ship
  525000,  -- 500k + 25k ship
  'cash',
  'defective',
  'Màn hình bị vỡ',
  'approved',
  10,  -- admin user_id
  NOW(),
  456  -- customer user_id
);
```

---

## 📋 TABLE 2: return_items

### **Definition**

```sql
CREATE TABLE return_items (
  -- Primary Key
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  
  -- Relationships
  return_id BIGINT UNSIGNED NOT NULL,
  order_item_id BIGINT UNSIGNED NOT NULL,
  
  -- Quantity
  quantity_returned INT NOT NULL,
  
  -- Condition
  condition ENUM('new', 'used', 'damaged'),
  
  -- Audit
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  -- Indexes
  INDEX idx_return (return_id),
  INDEX idx_order_item (order_item_id),
  
  -- Foreign Keys
  FOREIGN KEY (return_id) REFERENCES returns(id) ON DELETE CASCADE,
  FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE RESTRICT,
  
  -- Constraints
  CONSTRAINT chk_quantity_positive CHECK (quantity_returned > 0)
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### **Field Descriptions**

**id** `BIGINT UNSIGNED AUTO_INCREMENT`

- Primary key

**return_id** `BIGINT UNSIGNED NOT NULL`

- FK to [`returns.id`](http://returns.id)
- NOT NULL
- ON DELETE CASCADE (xóa return → xóa luôn items)

**order_item_id** `BIGINT UNSIGNED NOT NULL`

- FK to `order_[items.id](http://items.id)`
- NOT NULL
- ON DELETE RESTRICT
- Reference đến item trong order gốc

**quantity_returned** `INT NOT NULL`

- Số lượng trả
- NOT NULL
- CHECK: `quantity_returned > 0`
- Must be <= `order_item.quantity - SUM(previous returns)`

**condition** `ENUM('new', 'used', 'damaged')`

- Tình trạng hàng trả:
    - `new`: Mới nguyên
    - `used`: Đã sử dụng
    - `damaged`: Hỏng
- NULL cho đến khi staff confirm hàng về
- **Note:** Chỉ dùng cho reporting, KHÔNG ảnh hưởng restock logic

**created_at** `TIMESTAMP`

- Thời điểm tạo

---

### **Sample Data**

```sql
-- Return 2 items from order
INSERT INTO return_items (return_id, order_item_id, quantity_returned) VALUES
(1, 10, 2),  -- 2x iPhone 15
(1, 11, 1);  -- 1x AirPods
```

---

## 🔗 RELATIONSHIPS

### **Entity Diagram**

```
orders (1) ───── (N) returns (1) ───── (N) return_items
                                               |
                                               |
                                               (N)
                                               |
order_items (1) ──────────────────
```

**Explanation:**

- 1 order có thể có nhiều returns (partial returns)
- 1 return có nhiều return_items
- Mỗi return_item reference 1 order_item

---

## 📊 COMMON QUERIES

### **Get returns for an order**

```sql
SELECT * FROM returns 
WHERE order_id = 123
ORDER BY created_at DESC;
```

---

### **Get return details with items**

```sql
SELECT 
  r.*,
  ri.quantity_returned,
  ri.condition,
  oi.product_name,
  oi.price
FROM returns r
JOIN return_items ri ON ri.return_id = [r.id](http://r.id)
JOIN order_items oi ON [oi.id](http://oi.id) = ri.order_item_id
WHERE [r.id](http://r.id) = 1;
```

---

### **Pending returns**

```sql
SELECT 
  r.*,
  [c.name](http://c.name) as customer_name,
  o.order_number
FROM returns r
JOIN customers c ON [c.id](http://c.id) = r.customer_id
JOIN orders o ON [o.id](http://o.id) = r.order_id
WHERE r.status = 'pending'
ORDER BY r.created_at ASC;
```

---

### **Calculate total returnable quantity**

```sql
-- Check bao nhiêu có thể trả cho 1 order_item
SELECT 
  [oi.id](http://oi.id),
  oi.quantity as ordered_quantity,
  COALESCE(SUM(ri.quantity_returned), 0) as returned_quantity,
  oi.quantity - COALESCE(SUM(ri.quantity_returned), 0) as returnable_quantity
FROM order_items oi
LEFT JOIN return_items ri ON ri.order_item_id = [oi.id](http://oi.id)
LEFT JOIN returns r ON [r.id](http://r.id) = ri.return_id 
  AND r.status IN ('approved', 'completed')
WHERE oi.order_id = 123
GROUP BY [oi.id](http://oi.id);
```

---

## 🔢 RETURN NUMBER GENERATION

```php
function generateReturnNumber($orderId) {
    // Get existing return count for this order
    $count = Return::where('order_id', $orderId)->count();
    
    // Increment
    $counter = $count + 1;
    
    // Format: TH-{order_id}-{counter}
    $returnNumber = sprintf("TH-%d-%d", $orderId, $counter);
    
    return $returnNumber;
}
```

**Example:**

- Order #123 chưa có return nào → TH-123-1
- Order #123 đã có 1 return → TH-123-2
- Order #456 chưa có return nào → TH-456-1

---

## 🔄 STATUS LIFECYCLE

```
CREATE (pending)
    ↓
ADMIN REVIEW
    ├───────────────┐
    │                 │
    ↓                 ↓
approved          rejected (TERMINAL)
    ↓
STAFF CONFIRM HÀNG VỀ
    ↓
completed (TERMINAL)
    ↓
⚡ RESTOCK INVENTORY
```

---

## ⚡ RESTOCK TRIGGER

### **When status = 'completed'**

```php
function completeReturn($returnId) {
    $return = Return::with('returnItems.orderItem')->find($returnId);
    $order = $return->order;
    
    DB::beginTransaction();
    
    try {
        // 1. Update return status
        $return->update([
            'status' => 'completed',
            'completed_at' => now()
        ]);
        
        // 2. Restock inventory
        foreach ($return->returnItems as $item) {
            $orderItem = $item->orderItem;
            
            Inventory::where('product_id', $orderItem->product_id)
                ->where('variant_id', $orderItem->variant_id)
                ->where('branch_id', $order->branch_id)
                ->increment('quantity', $item->quantity_returned);
            
            // 3. Log movement
            InventoryMovement::create([
                'branch_id' => $order->branch_id,
                'product_id' => $orderItem->product_id,
                'variant_id' => $orderItem->variant_id,
                'type' => 'return',
                'quantity' => +$item->quantity_returned,  // positive
                'reference_type' => 'return',
                'reference_id' => $return->id
            ]);
        }
        
        DB::commit();
        
    } catch (Exception $e) {
        DB::rollback();
        throw $e;
    }
}
```

**Important:** LUÔN restock bất kể condition (new/used/damaged)

---

## 📊 ANALYTICS

### **Return Rate**

```sql
SELECT 
  DATE_FORMAT(o.created_at, '%Y-%m') as month,
  COUNT(DISTINCT [o.id](http://o.id)) as total_orders,
  COUNT(DISTINCT [r.id](http://r.id)) as total_returns,
  ROUND(COUNT(DISTINCT [r.id](http://r.id)) * 100.0 / COUNT(DISTINCT [o.id](http://o.id)), 2) as return_rate
FROM orders o
LEFT JOIN returns r ON r.order_id = [o.id](http://o.id) AND r.status != 'rejected'
WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
GROUP BY DATE_FORMAT(o.created_at, '%Y-%m');
```

---

### **Return Reasons Distribution**

```sql
SELECT 
  reason,
  COUNT(*) as count,
  ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM returns), 2) as percentage
FROM returns
WHERE status != 'rejected'
GROUP BY reason
ORDER BY count DESC;
```

---

## 🔗 RELATED DOCUMENTS

- [**RETURN_](https://www.notion.so/RETURN_FLOW-Return-Orders-Workflow-7f5c5a4af4054c22a265d011c56b10e3?pvs=21)[FLOW.md](http://FLOW.md)** - Return workflow
- [**ORDERS_](https://www.notion.so/ORDERS_TABLE-Orders-Table-Schema-36b32ddd5ce6421abd19d92589270881?pvs=21)[TABLE.md](http://TABLE.md)** - Orders schema
- [**SCHEMA_](https://www.notion.so/SCHEMA_OVERVIEW-Database-Schema-Overview-399a75e39df64c8c8a343bd9c6bbe0a2?pvs=21)[OVERVIEW.md](http://OVERVIEW.md)** - Full schema
- **Business Decisions:** #23-33