---
title: "Supporting Tables Schema - Order Workflow"
id: "SUPPORTING-TABLES-01"
module: "Order Workflow"
last_updated: "2025-11-24"
type: "Database Schema"
tags: ["database", "schema", "supporting-tables", "logs", "inventory", "branches"]
purpose: "Describes the detailed database schema for supporting tables including order_status_logs, inventory_movements, and branches, which are crucial for auditing and tracking within the Order Workflow module."
location: "docs/tasks/MAIN_MODULES/03_DATABASE_SCHEMA"
related_to:
  - id: "ORDERS-TABLE-01"
    description: "order_status_logs relates directly to the orders table."
  - id: "SCHEMA-OVERVIEW-01"
    description: "Overall database schema overview."
  - id: "DATABASE-RELATIONSHIPS-01"
    description: "Detailed relationships document."
---

# Supporting Tables Schema

**Module:** Order Workflow

**Last Updated:** 2025-11-24

---

## 🎯 MỤC ĐÍCH

Document này mô tả chi tiết schema cho **3 supporting tables**:

1. **order_status_logs** - Log lịch sử thay đổi status
2. **inventory_movements** - Log biến động tồn kho
3. **branches** - Chi nhánh

---

## 📋 TABLE 1: order_status_logs

### **Definition**

```sql
CREATE TABLE order_status_logs (
  -- Primary Key
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  
  -- Relationships
  order_id BIGINT UNSIGNED NOT NULL,
  
  -- Status Change
  from_status VARCHAR(50),
  to_status VARCHAR(50) NOT NULL,
  
  -- Additional Info
  notes TEXT,
  
  -- Audit
  changed_by BIGINT UNSIGNED NOT NULL,
  changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  -- Indexes
  INDEX idx_order (order_id, changed_at),
  INDEX idx_status (to_status),
  INDEX idx_changed_at (changed_at),
  
  -- Foreign Keys
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (changed_by) REFERENCES users(id)
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### **Field Descriptions**

### **Primary Key**

**id** `BIGINT UNSIGNED AUTO_INCREMENT`

- Primary key

---

### **Relationships**

**order_id** `BIGINT UNSIGNED NOT NULL`

- FK to [`orders.id`](http://orders.id)
- NOT NULL
- ON DELETE CASCADE (xóa order → xóa luôn logs)

---

### **Status Change**

**from_status** `VARCHAR(50)`

- Status cũ
- NULL nếu là lần đầu tiên (create order)
- Examples:
    - NULL → `draft`
    - `draft` → `confirmed`
    - `confirmed` → `processing`

**to_status** `VARCHAR(50) NOT NULL`

- Status mới
- NOT NULL
- Examples:
    - `draft`
    - `confirmed`
    - `processing`
    - `completed`
    - `cancelled`

---

### **Additional Info**

**notes** `TEXT`

- Ghi chú khi thay đổi status
- Example: "Khách yêu cầu hủy đơn"
- NULL nếu không có

---

### **Audit**

**changed_by** `BIGINT UNSIGNED NOT NULL`

- User ID của người thay đổi status
- FK to [`users.id`](http://users.id)
- NOT NULL

**changed_at** `TIMESTAMP`

- Thời điểm thay đổi
- Default: CURRENT_TIMESTAMP

---

### **Indexes Explained**

**idx_order (order_id, changed_at)**

- Get full status history của 1 order
- Query: `SELECT * FROM order_status_logs WHERE order_id = 123 ORDER BY changed_at`

**idx_status (to_status)**

- Count orders chuyển sang status X
- Query: `SELECT COUNT(*) FROM order_status_logs WHERE to_status = 'completed'`

**idx_changed_at (changed_at)**

- Filter by date range
- Query: `SELECT * FROM order_status_logs WHERE changed_at >= '2024-11-01'`

---

### **Sample Data**

```sql
-- Order #123 status history
INSERT INTO order_status_logs (order_id, from_status, to_status, changed_by) VALUES
(123, NULL, 'draft', 5),           -- Created
(123, 'draft', 'confirmed', 5),    -- Confirmed
(123, 'confirmed', 'processing', 5), -- Processing
(123, 'processing', 'shipping', 5),  -- Shipping
(123, 'shipping', 'delivered', 5),   -- Delivered
(123, 'delivered', 'completed', 5);  -- Completed
```

---

### **Common Queries**

### **Get order status history**

```sql
SELECT 
  osl.*,
  [u.name](http://u.name) as changed_by_name
FROM order_status_logs osl
JOIN users u ON [u.id](http://u.id) = osl.changed_by
WHERE osl.order_id = 123
ORDER BY osl.changed_at ASC;
```

---

### **Calculate average time in each status**

```sql
SELECT 
  from_status,
  to_status,
  AVG(TIMESTAMPDIFF(MINUTE, 
    prev.changed_at, 
    curr.changed_at
  )) as avg_minutes
FROM order_status_logs curr
JOIN order_status_logs prev ON prev.order_id = curr.order_id 
  AND prev.changed_at < curr.changed_at
GROUP BY from_status, to_status;
```

---

### **Orders cancelled today**

```sql
SELECT 
  o.*,
  osl.notes as cancel_reason
FROM orders o
JOIN order_status_logs osl ON osl.order_id = [o.id](http://o.id)
WHERE [osl.to](http://osl.to)_status = 'cancelled'
  AND DATE(osl.changed_at) = CURDATE();
```

---

### **Auto-Logging Strategy**

```php
// In OrderService::updateStatus()
function updateStatus($orderId, $newStatus, $userId, $notes = null) {
    $order = Order::find($orderId);
    $oldStatus = $order->status;
    
    DB::beginTransaction();
    
    try {
        // 1. Update order
        $order->update(['status' => $newStatus]);
        
        // 2. Auto-log status change
        OrderStatusLog::create([
            'order_id' => $orderId,
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'notes' => $notes,
            'changed_by' => $userId
        ]);
        
        DB::commit();
        
    } catch (Exception $e) {
        DB::rollback();
        throw $e;
    }
}
```

**Benefits:**

- Full audit trail
- No manual logging needed
- Automatic timestamp

---

## 📋 TABLE 2: inventory_movements

### **Definition**

```sql
CREATE TABLE inventory_movements (
  -- Primary Key
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  
  -- Relationships
  branch_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  variant_id BIGINT UNSIGNED,
  
  -- Movement
  type ENUM('sale', 'return', 'adjustment', 'transfer_in', 'transfer_out') NOT NULL,
  quantity INT NOT NULL,
  
  -- Reference
  reference_type VARCHAR(50),
  reference_id BIGINT UNSIGNED,
  
  -- Additional Info
  notes TEXT,
  
  -- Audit
  created_by BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  -- Indexes
  INDEX idx_branch_product (branch_id, product_id, created_at),
  INDEX idx_type (type, created_at),
  INDEX idx_reference (reference_type, reference_id),
  
  -- Foreign Keys
  FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE RESTRICT,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
  FOREIGN KEY (variant_id) REFERENCES variants(id) ON DELETE RESTRICT,
  FOREIGN KEY (created_by) REFERENCES users(id)
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### **Field Descriptions**

### **Primary Key**

**id** `BIGINT UNSIGNED AUTO_INCREMENT`

- Primary key

---

### **Relationships**

**branch_id** `BIGINT UNSIGNED NOT NULL`

- FK to [`branches.id`](http://branches.id)
- NOT NULL
- ON DELETE RESTRICT

**product_id** `BIGINT UNSIGNED NOT NULL`

- FK to [`products.id`](http://products.id)
- NOT NULL
- ON DELETE RESTRICT

**variant_id** `BIGINT UNSIGNED`

- FK to [`variants.id`](http://variants.id)
- NULL nếu product không có variants
- ON DELETE RESTRICT

---

### **Movement**

**type** `ENUM(...) NOT NULL`

- Loại biến động:
    - `sale`: Bán hàng (giảm tồn kho)
    - `return`: Trả hàng (tăng tồn kho)
    - `adjustment`: Điều chỉnh (tăng/giảm)
    - `transfer_in`: Chuyển kho vào (tăng)
    - `transfer_out`: Chuyển kho ra (giảm)
- NOT NULL

**quantity** `INT NOT NULL`

- Số lượng thay đổi
- Positive = tăng tồn kho
- Negative = giảm tồn kho
- NOT NULL
- Examples:
    - sale: -5 (giảm 5)
    - return: +2 (tăng 2)
    - adjustment: +10 hoặc -3

---

### **Reference**

**reference_type** `VARCHAR(50)`

- Loại entity gây ra movement:
    - `order`
    - `return`
    - `transfer`
    - `adjustment`
- NULL nếu không có reference

**reference_id** `BIGINT UNSIGNED`

- ID của entity
- Examples:
    - reference_type = 'order' → order_id
    - reference_type = 'return' → return_id
- NULL nếu không có reference

---

### **Additional Info**

**notes** `TEXT`

- Ghi chú
- Example: "Điều chỉnh sau kiểm kê"
- NULL nếu không có

---

### **Audit**

**created_by** `BIGINT UNSIGNED NOT NULL`

- User ID của người tạo movement
- FK to [`users.id`](http://users.id)
- NOT NULL

**created_at** `TIMESTAMP`

- Thời điểm movement

---

### **Sample Data**

```sql
-- Order #123 sells 2x iPhone 15
INSERT INTO inventory_movements (
  branch_id, product_id, variant_id,
  type, quantity,
  reference_type, reference_id,
  created_by
) VALUES (
  1, 456, 789,
  'sale', -2,
  'order', 123,
  5
);

-- Return #1 returns 1x iPhone 15
INSERT INTO inventory_movements (
  branch_id, product_id, variant_id,
  type, quantity,
  reference_type, reference_id,
  created_by
) VALUES (
  1, 456, 789,
  'return', +1,
  'return', 1,
  5
);

-- Manual adjustment: Found 3 missing units
INSERT INTO inventory_movements (
  branch_id, product_id, variant_id,
  type, quantity,
  notes,
  created_by
) VALUES (
  1, 456, 789,
  'adjustment', -3,
  'Kiểm kê: Thiếu 3 đơn vị',
  5
);
```

---

### **Common Queries**

### **Get product movement history**

```sql
SELECT 
  im.*,
  [b.name](http://b.name) as branch_name,
  [p.name](http://p.name) as product_name,
  [u.name](http://u.name) as created_by_name
FROM inventory_movements im
JOIN branches b ON [b.id](http://b.id) = im.branch_id
JOIN products p ON [p.id](http://p.id) = im.product_id
JOIN users u ON [u.id](http://u.id) = im.created_by
WHERE im.product_id = 456
  AND im.variant_id = 789
ORDER BY im.created_at DESC
LIMIT 50;
```

---

### **Calculate inventory from movements**

```sql
SELECT 
  branch_id,
  product_id,
  variant_id,
  SUM(quantity) as calculated_stock
FROM inventory_movements
WHERE product_id = 456
  AND variant_id = 789
GROUP BY branch_id, product_id, variant_id;
```

---

### **Top selling products today**

```sql
SELECT 
  [p.name](http://p.name) as product_name,
  SUM(-im.quantity) as quantity_sold
FROM inventory_movements im
JOIN products p ON [p.id](http://p.id) = im.product_id
WHERE im.type = 'sale'
  AND DATE(im.created_at) = CURDATE()
GROUP BY im.product_id, [p.name](http://p.name)
ORDER BY quantity_sold DESC
LIMIT 10;
```

---

## 📋 TABLE 3: branches

### **Definition**

```sql
CREATE TABLE branches (
  -- Primary Key
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  
  -- Identification
  code VARCHAR(50) UNIQUE NOT NULL,
  name VARCHAR(255) NOT NULL,
  
  -- Contact
  phone VARCHAR(20),
  email VARCHAR(255),
  
  -- Address
  address TEXT,
  ward VARCHAR(100),
  district VARCHAR(100),
  city VARCHAR(100),
  
  -- Configuration
  is_active BOOLEAN DEFAULT TRUE,
  
  -- Audit
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Indexes
  INDEX idx_code (code),
  INDEX idx_active (is_active)
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### **Field Descriptions**

### **Primary Key**

**id** `BIGINT UNSIGNED AUTO_INCREMENT`

- Primary key

---

### **Identification**

**code** `VARCHAR(50) UNIQUE NOT NULL`

- Mã chi nhánh
- UPPERCASE by convention
- Examples:
    - `HN` (Hà Nội)
    - `HCM` (TP.HCM)
    - `DN` (Đà Nẵng)
    - `HN-CG` (Hà Nội - Cầu Giấy)
- UNIQUE constraint

**name** `VARCHAR(255) NOT NULL`

- Tên chi nhánh
- Examples:
    - "Chi nhánh Hà Nội"
    - "Chi nhánh TP.HCM"
    - "Chi nhánh Đà Nẵng"
- NOT NULL

---

### **Contact**

**phone** `VARCHAR(20)`

- Số điện thoại chi nhánh
- Example: "024 1234 5678"

**email** `VARCHAR(255)`

- Email chi nhánh
- Example: "[hanoi@example.com](mailto:hanoi@example.com)"

---

### **Address**

**address** `TEXT`

- Địa chỉ chi tiết
- Example: "123 Nguyễn Trãi"

**ward** `VARCHAR(100)`

- Phường/Xã
- Example: "Phường Thanh Xuân Trung"

**district** `VARCHAR(100)`

- Quận/Huyện
- Example: "Quận Thanh Xuân"

**city** `VARCHAR(100)`

- Tỉnh/Thành phố
- Example: "Hà Nội"

---

### **Configuration**

**is_active** `BOOLEAN`

- Chi nhánh có active không?
- Default: TRUE

---

### **Audit**

**created_at** `TIMESTAMP`

- Thời điểm tạo

**updated_at** `TIMESTAMP`

- Thời điểm update cuối

---

### **Sample Data**

```sql
INSERT INTO branches (code, name, phone, address, ward, district, city) VALUES
('HN', 'Chi nhánh Hà Nội', '024 1234 5678', '123 Nguyễn Trãi', 'Phường Thanh Xuân Trung', 'Quận Thanh Xuân', 'Hà Nội'),
('HCM', 'Chi nhánh TP.HCM', '028 8765 4321', '456 Lê Lợi', 'Phường Bến Thành', 'Quận 1', 'TP.HCM'),
('DN', 'Chi nhánh Đà Nẵng', '0236 3456 789', '789 Trần Phú', 'Phường Thạch Thang', 'Quận Hải Châu', 'Đà Nẵng');
```

---

### **Common Queries**

### **Get active branches**

```sql
SELECT * FROM branches 
WHERE is_active = TRUE
ORDER BY name;
```

---

### **Branch with most orders**

```sql
SELECT 
  [b.name](http://b.name),
  COUNT([o.id](http://o.id)) as order_count,
  SUM([o.total](http://o.total)) as total_revenue
FROM branches b
LEFT JOIN orders o ON o.branch_id = [b.id](http://b.id)
GROUP BY [b.id](http://b.id), [b.name](http://b.name)
ORDER BY order_count DESC;
```

---

## 📊 SIZE ESTIMATES

### **order_status_logs**

**Assumptions:**

- 10,000 orders/month
- 6 status changes/order average
- Row size: ~200 bytes

**Size:**

- Per month: 10,000 × 6 × 200 = 12 MB
- Per year: 12 × 12 = 144 MB

---

### **inventory_movements**

**Assumptions:**

- 10,000 orders/month
- 2 items/order average
- Row size: ~150 bytes

**Size:**

- Per month: 10,000 × 2 × 150 = 3 MB
- Per year: 3 × 12 = 36 MB

---

### **branches**

**Assumptions:**

- 10 branches
- Row size: ~500 bytes

**Size:**

- Total: 10 × 500 = 5 KB (negligible)

---

## 🔗 RELATED DOCUMENTS

- [**SCHEMA_](https://www.notion.so/SCHEMA_OVERVIEW-Database-Schema-Overview-399a75e39df64c8c8a343bd9c6bbe0a2?pvs=21)[OVERVIEW.md](http://OVERVIEW.md)** - Full schema
- [**ORDERS_](https://www.notion.so/ORDERS_TABLE-Orders-Table-Schema-36b32ddd5ce6421abd19d92589270881?pvs=21)[TABLE.md](http://TABLES.md)** - Orders schema
- [**RETURNS_](https://www.notion.so/RETURNS_TABLES-Returns-Schema-4a31069a3d7f44999f9b01f61ed53b1a?pvs=21)[TABLES.md](http://TABLES.md)** - Returns schema