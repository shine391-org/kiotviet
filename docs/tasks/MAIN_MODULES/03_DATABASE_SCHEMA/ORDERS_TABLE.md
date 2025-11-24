---
title: "Orders Table Schema"
id: "ORDERS-TABLE-01"
module: "Order Workflow"
last_updated: "2025-11-24"
type: "Database Schema"
tags: ["database", "schema", "orders", "order-workflow", "financial", "status", "shipping", "payment"]
purpose: "Describes the detailed database schema for the central 'orders' table within the Order Workflow module."
location: "docs/tasks/MAIN_MODULES/03_DATABASE_SCHEMA"
related_to:
  - id: "ORD-003"
    description: "Related Task: Order Status Management implementation."
  - id: "BUSINESS-DECISIONS-01"
    description: "References Business Decisions #1-15 related to order structure and workflow."
  - id: "SHIPPING-FLOW-01"
    description: "Describes the status workflow for SHIPPING orders."
  - id: "SCHEMA-OVERVIEW-01"
    description: "Overall database schema overview."
  - id: "INVOICES-TABLES-01"
    description: "Related tables: invoices via invoice_orders mapping."
  - id: "POS-FLOW-01"
    description: "Contrasted with POS orders, which have no status workflow."
  - id: "RETURN-FLOW-01"
    description: "Related to the return process, which references orders."
---

# ORDERS_TABLE - Orders Table Schema

# Orders Table Schema

**Module:** Order Workflow

**Related Task:** ORD-003

**Last Updated:** 2025-11-24

---

## 🎯 MỤC ĐÍCH

Document này mô tả chi tiết schema cho table **orders** - table trung tâm của Order Workflow.

---

## 📋 TABLE DEFINITION

```sql
CREATE TABLE orders (
  -- Primary Key
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  
  -- Order Identification
  order_number VARCHAR(50) UNIQUE NOT NULL,
  order_type ENUM('POS', 'SHIPPING') DEFAULT 'SHIPPING',
  
  -- Relationships
  customer_id BIGINT UNSIGNED,
  branch_id BIGINT UNSIGNED NOT NULL,
  
  -- Status & Workflow
  status ENUM(
    'draft',
    'confirmed', 
    'processing',
    'shipping',
    'delivered',
    'completed',
    'failed',
    'return',
    'return_confirmed',
    'cancelled'
  ) DEFAULT 'draft',
  
  cancel_reason ENUM(
    'customer_request',
    'out_of_stock',
    'wrong_address',
    'other'
  ),
  cancel_notes TEXT,
  
  -- Financials
  subtotal DECIMAL(15,2) NOT NULL DEFAULT 0,
  discount DECIMAL(15,2) DEFAULT 0,
  shipping_fee DECIMAL(15,2) DEFAULT 0,
  total DECIMAL(15,2) NOT NULL DEFAULT 0,
  
  -- Payment
  payment_method VARCHAR(50),
  paid_amount DECIMAL(15,2) DEFAULT 0,
  debt_amount DECIMAL(15,2) AS (total - paid_amount) STORED,
  
  -- Shipping (for SHIPPING orders only)
  shipping_partner ENUM('GHN', 'GHTK', 'manual'),
  shipping_address TEXT,
  shipping_phone VARCHAR(20),
  shipping_notes TEXT,
  
  -- COD Tracking
  cod_collected BOOLEAN DEFAULT FALSE,
  cod_reconciled BOOLEAN DEFAULT FALSE,
  
  -- Additional Info
  notes TEXT,
  internal_notes TEXT,
  
  -- Audit Fields
  created_by BIGINT UNSIGNED NOT NULL,
  updated_by BIGINT UNSIGNED,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at TIMESTAMP NULL,
  
  -- Indexes
  INDEX idx_customer (customer_id, created_at),
  INDEX idx_branch (branch_id),
  INDEX idx_status (status, created_at),
  INDEX idx_order_number (order_number),
  INDEX idx_payment_method (payment_method),
  INDEX idx_created_at (created_at),
  
  -- Foreign Keys
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
  FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE RESTRICT,
  FOREIGN KEY (created_by) REFERENCES users(id),
  FOREIGN KEY (updated_by) REFERENCES users(id),
  
  -- Constraints
  CONSTRAINT chk_total_positive CHECK (total >= 0),
  CONSTRAINT chk_paid_valid CHECK (paid_amount >= 0 AND paid_amount <= total)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 📊 FIELD DESCRIPTIONS

### **Primary Key**

**id** `BIGINT UNSIGNED AUTO_INCREMENT`

- Primary key
- Auto-increment
- UNSIGNED để tăng range (0 to 18 quintillion)

---

### **Order Identification**

**order_number** `VARCHAR(50) UNIQUE NOT NULL`

- Mã đơn hàng hiển thị cho user
- Format: `ORD-{YYYYMMDD}-{sequence}`
- Example: `ORD-20241124-001234`
- UNIQUE constraint
- NOT NULL

**order_type** `ENUM('POS', 'SHIPPING')`

- Phân biệt 2 loại đơn:
    - `POS`: Bán tại quầy
    - `SHIPPING`: Giao hàng
- Default: `SHIPPING`

---

### **Relationships**

**customer_id** `BIGINT UNSIGNED`

- FK to [`customers.id`](http://customers.id)
- NULL allowed (khách vãng lai)
- ON DELETE RESTRICT (không xóa được customer nếu còn orders)

**branch_id** `BIGINT UNSIGNED NOT NULL`

- FK to [`branches.id`](http://branches.id)
- NOT NULL (mọi order phải thuộc 1 branch)
- ON DELETE RESTRICT

---

### **Status & Workflow**

**status** `ENUM(...)`

- 10 statuses:
    - `draft` - Đơn mới tạo
    - `confirmed` - Đã xác nhận
    - `processing` - Đang chuẩn bị
    - `shipping` - Đang giao hàng (⚡ TRỪ KHO)
    - `delivered` - Đã giao
    - `completed` - Hoàn thành
    - `failed` - Giao thất bại
    - `return` - Hàng đang về
    - `return_confirmed` - Hàng đã về (⚡ ROLLBACK KHO)
    - `cancelled` - Đã hủy
- Default: `draft`
- **Note:** POS orders thường NULL hoặc `completed`

**cancel_reason** `ENUM(...)`

- Lý do hủy:
    - `customer_request`
    - `out_of_stock`
    - `wrong_address`
    - `other`
- NULL nếu chưa cancel

**cancel_notes** `TEXT`

- Chi tiết lý do hủy
- NULL nếu chưa cancel

---

### **Financials**

**subtotal** `DECIMAL(15,2) NOT NULL`

- Tổng tiền hàng (chưa trừ discount, chưa cộng ship)
- Formula: `SUM(order_[items.total](http://items.total))`
- NOT NULL, default 0

**discount** `DECIMAL(15,2)`

- Giảm giá tổng đơn
- Default 0
- Có thể từ:
    - Manual discount
    - Promotion codes
    - Loyalty points

**shipping_fee** `DECIMAL(15,2)`

- Phí giao hàng
- Default 0
- POS orders: luôn = 0
- SHIPPING orders: calculate từ distance/weight

**total** `DECIMAL(15,2) NOT NULL`

- Tổng cộng phải thanh toán
- Formula: `subtotal - discount + shipping_fee`
- NOT NULL
- CHECK constraint: `total >= 0`

---

### **Payment**

**payment_method** `VARCHAR(50)`

- FK to `payment_methods.code`
- Examples: `CASH`, `BANK_TRANSFER`, `COD`
- POS orders: luôn = `CASH`
- SHIPPING orders: flexible

**paid_amount** `DECIMAL(15,2)`

- Số tiền đã thanh toán
- Default 0
- Có thể < total (partial payment)
- CHECK: `paid_amount >= 0 AND paid_amount <= total`

**debt_amount** `DECIMAL(15,2) GENERATED ALWAYS AS (total - paid_amount) STORED`

- Số tiền còn nợ
- Generated column (auto calculate)
- STORED để có thể index nếu cần
- Formula: `total - paid_amount`

---

### **Shipping (SHIPPING orders only)**

**shipping_partner** `ENUM('GHN', 'GHTK', 'manual')`

- Đơn vị vận chuyển:
    - `GHN` - Giao Hàng Nhanh (API)
    - `GHTK` - Giao Hàng Tiết Kiệm (API)
    - `manual` - Ship ngoài (tự do)
- NULL cho POS orders

**shipping_address** `TEXT`

- Địa chỉ giao hàng
- Full address string
- NULL cho POS orders

**shipping_phone** `VARCHAR(20)`

- SĐT người nhận
- NULL cho POS orders

**shipping_notes** `TEXT`

- Ghi chú giao hàng
- Example: "Gọi trước 30 phút"
- NULL nếu không có

---

### **COD Tracking**

**cod_collected** `BOOLEAN`

- Shipper đã thu tiền từ khách chưa?
- Default: FALSE
- TRUE khi:
    - Webhook từ GHN/GHTK báo collected
    - Staff manual confirm

**cod_reconciled** `BOOLEAN`

- Shop đã nhận tiền từ shipper chưa?
- Default: FALSE
- TRUE khi:
    - Admin confirm đã đối soát với shipper
    - Tiền đã về tài khoản

**Note:** Chỉ áp dụng cho orders có `payment_method = 'COD'`

---

### **Additional Info**

**notes** `TEXT`

- Ghi chú của customer
- Visible to customer
- Example: "Giao giờ hành chính"

**internal_notes** `TEXT`

- Ghi chú nội bộ
- NOT visible to customer
- Example: "Khách khó tính, cẩn thận"

---

### **Audit Fields**

**created_by** `BIGINT UNSIGNED NOT NULL`

- User ID của người tạo order
- FK to [`users.id`](http://users.id)
- NOT NULL

**updated_by** `BIGINT UNSIGNED`

- User ID của người update cuối cùng
- FK to [`users.id`](http://users.id)
- NULL nếu chưa update

**created_at** `TIMESTAMP`

- Thời điểm tạo order
- Default: CURRENT_TIMESTAMP

**updated_at** `TIMESTAMP`

- Thời điểm update cuối
- Default: CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

**deleted_at** `TIMESTAMP NULL`

- Soft delete
- NULL = chưa xóa
- NOT NULL = đã xóa (không hiển thị)

---

## 🔍 INDEXES EXPLAINED

### **idx_customer (customer_id, created_at)**

**Purpose:** Get orders của 1 customer, sort by date

**Query:**

```sql
SELECT * FROM orders 
WHERE customer_id = 123 
ORDER BY created_at DESC;
```

**Why composite:** customer_id để filter, created_at để sort

---

### **idx_branch (branch_id)**

**Purpose:** Get orders của 1 branch

**Query:**

```sql
SELECT * FROM orders WHERE branch_id = 1;
```

---

### **idx_status (status, created_at)**

**Purpose:** Filter orders theo status

**Query:**

```sql
SELECT * FROM orders 
WHERE status = 'shipping' 
ORDER BY created_at DESC;
```

**Use case:**

- Danh sách đơn đang ship
- Đơn pending

---

### **idx_order_number (order_number)**

**Purpose:** Quick lookup by order number

**Query:**

```sql
SELECT * FROM orders WHERE order_number = 'ORD-20241124-001234';
```

**Why:** Customer tra cứu đơn

---

### **idx_payment_method (payment_method)**

**Purpose:** Filter by payment method

**Query:**

```sql
SELECT * FROM orders WHERE payment_method = 'COD';
```

**Use case:** Báo cáo COD, reconciliation

---

### **idx_created_at (created_at)**

**Purpose:** Sort/filter by date

**Query:**

```sql
SELECT * FROM orders 
WHERE created_at BETWEEN '2024-11-01' AND '2024-11-30';
```

**Use case:** Báo cáo doanh thu theo tháng

---

## 🔒 CONSTRAINTS

### **UNIQUE Constraint**

```sql
UNIQUE (order_number)
```

- Đảm bảo mỗi order_number chỉ xuất hiện 1 lần
- Prevent duplicate order numbers

---

### **CHECK Constraints**

**chk_total_positive:**

```sql
CHECK (total >= 0)
```

- Tổng tiền không được âm

**chk_paid_valid:**

```sql
CHECK (paid_amount >= 0 AND paid_amount <= total)
```

- Paid amount phải:
    - 
        
        > = 0 (không âm)
        > 
    - <= total (không vượt quá tổng)

---

### **Foreign Key Constraints**

**FK to customers:**

```sql
FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT
```

- ON DELETE RESTRICT: Không xóa được customer nếu còn orders

**FK to branches:**

```sql
FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE RESTRICT
```

- ON DELETE RESTRICT: Không xóa được branch nếu còn orders

**FK to users:**

```sql
FOREIGN KEY (created_by) REFERENCES users(id)
FOREIGN KEY (updated_by) REFERENCES users(id)
```

- No ON DELETE (default: RESTRICT)

---

## 📝 SAMPLE DATA

### **POS Order Example**

```sql
INSERT INTO orders (
  order_number,
  order_type,
  customer_id,
  branch_id,
  status,
  subtotal,
  discount,
  shipping_fee,
  total,
  payment_method,
  paid_amount,
  created_by
) VALUES (
  'ORD-20241124-000001',
  'POS',
  NULL,  -- khách vãng lai
  1,
  'completed',
  1000000,
  0,
  0,  -- POS không ship
  1000000,
  'CASH',
  1000000,  -- thu đủ ngay
  5  -- cashier user_id
);
```

---

### **SHIPPING Order Example**

```sql
INSERT INTO orders (
  order_number,
  order_type,
  customer_id,
  branch_id,
  status,
  subtotal,
  discount,
  shipping_fee,
  total,
  payment_method,
  paid_amount,
  shipping_partner,
  shipping_address,
  shipping_phone,
  created_by
) VALUES (
  'ORD-20241124-000002',
  'SHIPPING',
  123,
  1,
  'draft',
  2000000,
  100000,  -- giảm 100k
  30000,   -- phí ship
  1930000,
  'COD',
  0,  -- chưa thu tiền
  'GHN',
  '123 Nguyễn Trãi, Q1, TP.HCM',
  '0901234567',
  5
);
```

---

## 🔄 STATUS LIFECYCLE

### **POS Orders**

```
NULL/completed (không transition)
```

### **SHIPPING Orders - Happy Path**

```
draft → confirmed → processing → shipping → delivered → completed
```

### **SHIPPING Orders - Failed Path**

```
shipping → failed → return → return_confirmed
```

### **Cancel Path**

```
Any status (except completed) → cancelled
```

---

## 🧮 CALCULATED FIELDS

### **debt_amount (Generated Column)**

```sql
debt_amount DECIMAL(15,2) AS (total - paid_amount) STORED
```

**Purpose:** Tự động tính số nợ

**Example:**

- total = 1,000,000
- paid_amount = 300,000
- debt_amount = 700,000 (auto)

**Benefits:**

- Không cần calculate manually
- Có thể query trực tiếp
- Có thể index

**Query example:**

```sql
SELECT * FROM orders WHERE debt_amount > 0;
```

---

## 📊 COMMON QUERIES

### **Get orders của customer**

```sql
SELECT * FROM orders 
WHERE customer_id = 123 
ORDER BY created_at DESC;
```

---

### **Get pending orders**

```sql
SELECT * FROM orders 
WHERE status IN ('draft', 'confirmed', 'processing')
ORDER BY created_at ASC;
```

---

### **Get orders đang ship**

```sql
SELECT * FROM orders 
WHERE status = 'shipping'
ORDER BY created_at ASC;
```

---

### **Doanh thu tháng**

```sql
SELECT 
  DATE(created_at) as date,
  COUNT(*) as order_count,
  SUM(total) as revenue
FROM orders
WHERE created_at >= '2024-11-01' 
  AND created_at < '2024-12-01'
  AND status = 'completed'
GROUP BY DATE(created_at);
```

---

### **COD chưa thu**

```sql
SELECT * FROM orders
WHERE payment_method = 'COD'
  AND cod_collected = FALSE
  AND status = 'delivered';
```

---

### **Orders có nợ**

```sql
SELECT 
  o.*,
  [c.name](http://c.name) as customer_name,
  o.debt_amount
FROM orders o
JOIN customers c ON [c.id](http://c.id) = o.customer_id
WHERE o.debt_amount > 0
ORDER BY o.debt_amount DESC;
```

---

## 🔗 RELATED TABLES

### **order_items (1:N)**

```sql
SELECT 
  o.*,
  oi.product_id,
  oi.quantity,
  oi.price
FROM orders o
JOIN order_items oi ON oi.order_id = [o.id](http://o.id)
WHERE [o.id](http://o.id) = 123;
```

---

### **order_status_logs (1:N)**

```sql
SELECT 
  osl.*,
  [u.name](http://u.name) as user_name
FROM order_status_logs osl
LEFT JOIN users u ON [u.id](http://u.id) = osl.user_id
WHERE osl.order_id = 123
ORDER BY osl.created_at DESC;
```

---

### **invoices (N:N via invoice_orders)**

```sql
SELECT 
  i.*
FROM invoices i
JOIN invoice_orders io ON io.invoice_id = [i.id](http://i.id)
WHERE io.order_id = 123;
```

---

### **returns (1:N)**

```sql
SELECT * FROM returns
WHERE order_id = 123;
```

---

## 🔗 RELATED DOCUMENTS

- [**SCHEMA_](766)[OVERVIEW.md](http://OVERVIEW.md)** - Full schema overview
- [**SHIPPING_](https://www.notion.so/SHIPPING_FLOW-SHIPPING-Orders-Workflow-c9c2807fa20e46d99712079779edf072?pvs=21)[FLOW.md](http://FLOW.md)** - Status workflow
- **Business Decisions:** #1-15