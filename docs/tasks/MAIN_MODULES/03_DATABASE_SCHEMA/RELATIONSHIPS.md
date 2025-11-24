# Database Relationships

**Module:** Order Workflow

**Last Updated:** 2025-11-24

---

## 🎯 MỤC ĐÍCH

Document này mô tả chi tiết các **relationships** giữa các tables trong Order Workflow module.

---

## 🗺️ ENTITY RELATIONSHIP DIAGRAM

### **Core Entities**

```
┌─────────────┐
│  customers  │
└──────┬──────┘
       │ 1
       │
       │ N
┌──────▼──────┐        1:N        ┌──────────────┐
│   orders    ├─────────────────►│ order_items  │
└──────┬──────┘                  └──────────────┘
       │
       │ N
       │
┌──────▼──────────────┐
│ order_status_logs   │
└─────────────────────┘

       │ N
       │
┌──────▼──────┐
│   returns   │
└──────┬──────┘
       │ 1
       │
       │ N
┌──────▼──────────┐
│  return_items   │
└─────────────────┘

┌──────────────┐        N:N        ┌──────────┐
│   invoices   ◄───────┬───────────►  orders  │
└──────────────┘       │            └──────────┘
                       │
                ┌──────▼──────────┐
                │ invoice_orders  │
                └─────────────────┘

┌─────────────────┐
│ payment_methods │
└────────┬────────┘
         │ 1
         │
         │ N
    ┌────▼─────┐
    │  orders  │
    └──────────┘

┌──────────┐
│ branches │
└────┬─────┘
     │ 1
     │
     │ N
┌────▼─────┐
│  orders  │
└──────────┘
```

---

## 📋 RELATIONSHIP DETAILS

### **1. customers → orders (1:N)**

**Type:** One-to-Many

**Foreign Key:**

```sql
orders.customer_id → [customers.id](http://customers.id)
```

**Cardinality:**

- 1 customer có thể có nhiều orders
- 1 order thuộc về 1 customer

**ON DELETE:** RESTRICT

- Không thể xóa customer nếu có orders

**Query:**

```sql
-- Get all orders of a customer
SELECT o.* 
FROM orders o
WHERE o.customer_id = 123
ORDER BY o.created_at DESC;
```

---

### **2. orders → order_items (1:N)**

**Type:** One-to-Many

**Foreign Key:**

```sql
order_items.order_id → [orders.id](http://orders.id)
```

**Cardinality:**

- 1 order có thể có nhiều items
- 1 item thuộc về 1 order

**ON DELETE:** CASCADE

- Xóa order → xóa luôn items

**Query:**

```sql
-- Get order with items
SELECT 
  o.*,
  oi.product_name,
  oi.quantity,
  oi.price
FROM orders o
JOIN order_items oi ON oi.order_id = [o.id](http://o.id)
WHERE [o.id](http://o.id) = 123;
```

---

### **3. orders → order_status_logs (1:N)**

**Type:** One-to-Many

**Foreign Key:**

```sql
order_status_logs.order_id → [orders.id](http://orders.id)
```

**Cardinality:**

- 1 order có nhiều status logs
- 1 log thuộc về 1 order

**ON DELETE:** CASCADE

- Xóa order → xóa luôn logs

**Query:**

```sql
-- Get order status history
SELECT 
  osl.*,
  [u.name](http://u.name) as changed_by_name
FROM order_status_logs osl
JOIN users u ON [u.id](http://u.id) = osl.changed_by
WHERE osl.order_id = 123
ORDER BY osl.changed_at ASC;
```

---

### **4. payment_methods → orders (1:N)**

**Type:** One-to-Many

**Foreign Key:**

```sql
orders.payment_method → payment_methods.code
```

**Note:** FK on **code**, not id

**Cardinality:**

- 1 payment method có thể dùng cho nhiều orders
- 1 order có 1 payment method

**ON DELETE:** RESTRICT (implicit, vì payment_methods là master data)

**Query:**

```sql
-- Get orders by payment method
SELECT 
  [pm.name](http://pm.name),
  COUNT([o.id](http://o.id)) as order_count
FROM payment_methods pm
LEFT JOIN orders o ON o.payment_method = pm.code
GROUP BY pm.code, [pm.name](http://pm.name);
```

---

### **5. branches → orders (1:N)**

**Type:** One-to-Many

**Foreign Key:**

```sql
orders.branch_id → [branches.id](http://branches.id)
```

**Cardinality:**

- 1 branch có nhiều orders
- 1 order thuộc về 1 branch

**ON DELETE:** RESTRICT

**Query:**

```sql
-- Get orders by branch
SELECT 
  [b.name](http://b.name),
  COUNT([o.id](http://o.id)) as order_count,
  SUM([o.total](http://o.total)) as revenue
FROM branches b
LEFT JOIN orders o ON o.branch_id = [b.id](http://b.id)
GROUP BY [b.id](http://b.id), [b.name](http://b.name);
```

---

### **6. orders → returns (1:N)**

**Type:** One-to-Many

**Foreign Key:**

```sql
returns.order_id → [orders.id](http://orders.id)
```

**Cardinality:**

- 1 order có thể có nhiều returns (partial returns)
- 1 return thuộc về 1 order

**ON DELETE:** RESTRICT

**Query:**

```sql
-- Get returns for an order
SELECT r.*
FROM returns r
WHERE r.order_id = 123
ORDER BY r.created_at DESC;
```

---

### **7. returns → return_items (1:N)**

**Type:** One-to-Many

**Foreign Key:**

```sql
return_items.return_id → [returns.id](http://returns.id)
```

**Cardinality:**

- 1 return có nhiều items
- 1 return_item thuộc về 1 return

**ON DELETE:** CASCADE

**Query:**

```sql
-- Get return with items
SELECT 
  r.*,
  ri.quantity_returned,
  ri.condition
FROM returns r
JOIN return_items ri ON ri.return_id = [r.id](http://r.id)
WHERE [r.id](http://r.id) = 1;
```

---

### **8. order_items → return_items (1:N)**

**Type:** One-to-Many

**Foreign Key:**

```sql
return_items.order_item_id → order_[items.id](http://items.id)
```

**Cardinality:**

- 1 order_item có thể được trả nhiều lần (partial returns)
- 1 return_item reference 1 order_item

**ON DELETE:** RESTRICT

**Query:**

```sql
-- Check returnable quantity
SELECT 
  [oi.id](http://oi.id),
  oi.quantity as ordered,
  COALESCE(SUM(ri.quantity_returned), 0) as returned,
  oi.quantity - COALESCE(SUM(ri.quantity_returned), 0) as returnable
FROM order_items oi
LEFT JOIN return_items ri ON ri.order_item_id = [oi.id](http://oi.id)
LEFT JOIN returns r ON [r.id](http://r.id) = ri.return_id 
  AND r.status IN ('approved', 'completed')
WHERE oi.order_id = 123
GROUP BY [oi.id](http://oi.id);
```

---

### **9. invoices ↔ orders (N:N)**

**Type:** Many-to-Many

**Junction Table:** `invoice_orders`

**Foreign Keys:**

```sql
invoice_orders.invoice_id → [invoices.id](http://invoices.id)
invoice_orders.order_id → [orders.id](http://orders.id)
```

**Cardinality:**

- 1 invoice có thể gộp nhiều orders
- 1 order (hiếm khi) có thể thuộc nhiều invoices

**ON DELETE:**

- invoice_id: CASCADE (xóa invoice → xóa mappings)
- order_id: RESTRICT (không xóa order nếu có invoice)

**Query:**

```sql
-- Get orders in an invoice
SELECT o.*
FROM orders o
JOIN invoice_orders io ON io.order_id = [o.id](http://o.id)
WHERE io.invoice_id = 1;

-- Get invoices for an order
SELECT i.*
FROM invoices i
JOIN invoice_orders io ON io.invoice_id = [i.id](http://i.id)
WHERE io.order_id = 123;
```

---

### **10. customers → invoices (1:N)**

**Type:** One-to-Many

**Foreign Key:**

```sql
invoices.customer_id → [customers.id](http://customers.id)
```

**Cardinality:**

- 1 customer có nhiều invoices
- 1 invoice thuộc về 1 customer

**ON DELETE:** RESTRICT

**Query:**

```sql
-- Get customer invoices
SELECT i.*
FROM invoices i
WHERE i.customer_id = 123
ORDER BY i.issue_date DESC;
```

---

### **11. branches → inventory_movements (1:N)**

**Type:** One-to-Many

**Foreign Key:**

```sql
inventory_movements.branch_id → [branches.id](http://branches.id)
```

**Cardinality:**

- 1 branch có nhiều inventory movements
- 1 movement thuộc về 1 branch

**ON DELETE:** RESTRICT

**Query:**

```sql
-- Get branch inventory movements
SELECT im.*
FROM inventory_movements im
WHERE im.branch_id = 1
ORDER BY im.created_at DESC;
```

---

## 🔐 REFERENTIAL INTEGRITY

### **DELETE Cascade Rules**

**CASCADE (Xóa parent → xóa children):**

- orders → order_items
- orders → order_status_logs
- returns → return_items
- invoices → invoice_orders (junction table)

**RESTRICT (Không cho xóa parent nếu có children):**

- customers → orders
- customers → invoices
- orders → returns
- order_items → return_items
- branches → orders
- branches → inventory_movements

---

## 📊 COMMON JOIN PATTERNS

### **Pattern 1: Order với customer và items**

```sql
SELECT 
  o.order_number,
  [c.name](http://c.name) as customer_name,
  oi.product_name,
  oi.quantity,
  oi.price
FROM orders o
JOIN customers c ON [c.id](http://c.id) = o.customer_id
JOIN order_items oi ON oi.order_id = [o.id](http://o.id)
WHERE [o.id](http://o.id) = 123;
```

---

### **Pattern 2: Order với status history**

```sql
SELECT 
  o.order_number,
  osl.from_status,
  [osl.to](http://osl.to)_status,
  osl.changed_at,
  [u.name](http://u.name) as changed_by
FROM orders o
JOIN order_status_logs osl ON osl.order_id = [o.id](http://o.id)
JOIN users u ON [u.id](http://u.id) = osl.changed_by
WHERE [o.id](http://o.id) = 123
ORDER BY osl.changed_at ASC;
```

---

### **Pattern 3: Invoice với orders và items**

```sql
SELECT 
  i.invoice_number,
  o.order_number,
  oi.product_name,
  oi.quantity,
  oi.price
FROM invoices i
JOIN invoice_orders io ON io.invoice_id = [i.id](http://i.id)
JOIN orders o ON [o.id](http://o.id) = io.order_id
JOIN order_items oi ON oi.order_id = [o.id](http://o.id)
WHERE [i.id](http://i.id) = 1;
```

---

### **Pattern 4: Return với order và items**

```sql
SELECT 
  r.return_number,
  o.order_number,
  ri.quantity_returned,
  oi.product_name,
  oi.price
FROM returns r
JOIN orders o ON [o.id](http://o.id) = r.order_id
JOIN return_items ri ON ri.return_id = [r.id](http://r.id)
JOIN order_items oi ON [oi.id](http://oi.id) = ri.order_item_id
WHERE [r.id](http://r.id) = 1;
```

---

### **Pattern 5: Customer summary**

```sql
SELECT 
  [c.name](http://c.name),
  COUNT(DISTINCT [o.id](http://o.id)) as order_count,
  SUM([o.total](http://o.total)) as total_spent,
  COUNT(DISTINCT [r.id](http://r.id)) as return_count,
  COUNT(DISTINCT [i.id](http://i.id)) as invoice_count
FROM customers c
LEFT JOIN orders o ON o.customer_id = [c.id](http://c.id)
LEFT JOIN returns r ON r.customer_id = [c.id](http://c.id)
LEFT JOIN invoices i ON i.customer_id = [c.id](http://c.id)
WHERE [c.id](http://c.id) = 123
GROUP BY [c.id](http://c.id), [c.name](http://c.name);
```

---

## ⚠️ IMPORTANT NOTES

### **1. Circular Dependencies**

**Problem:** orders ↔ invoices (N:N)

- Không có circular dependency
- Sử dụng junction table `invoice_orders`

---

### **2. Soft Delete Considerations**

**If implementing soft deletes:**

- Thêm `deleted_at` column
- Update FK checks để exclude soft-deleted records

```sql
-- Example: Get orders excluding soft-deleted customers
SELECT o.*
FROM orders o
JOIN customers c ON [c.id](http://c.id) = o.customer_id
WHERE c.deleted_at IS NULL;
```

---

### **3. Performance Tips**

**Indexes for JOINs:**

- Tất cả FK columns đã có indexes
- Composite indexes cho frequently joined columns

**Query Optimization:**

- Sử dụng `EXPLAIN` để check query performance
- Avoid N+1 queries bằng cách dùng eager loading

---

## 🔗 RELATED DOCUMENTS

- [**SCHEMA_](https://www.notion.so/SCHEMA_OVERVIEW-Database-Schema-Overview-399a75e39df64c8c8a343bd9c6bbe0a2?pvs=21)[OVERVIEW.md](http://OVERVIEW.md)** - Full schema overview
- [**ORDERS_](https://www.notion.so/ORDERS_TABLE-Orders-Table-Schema-36b32ddd5ce6421abd19d92589270881?pvs=21)[TABLE.md](http://TABLE.md)** - Orders table
- [**INVOICES_](https://www.notion.so/INVOICES_TABLES-Invoices-Schema-9ff5db4f3f3a4ea78d2298ecca309a73?pvs=21)[TABLES.md](http://TABLES.md)** - Invoices tables
- [**RETURNS_](https://www.notion.so/RETURNS_TABLES-Returns-Schema-4a31069a3d7f44999f9b01f61ed53b1a?pvs=21)[TABLES.md](http://TABLES.md)** - Returns tables
- **[SUPPORTING_[TABLES.md](http://TABLES.md)]** - Supporting tables