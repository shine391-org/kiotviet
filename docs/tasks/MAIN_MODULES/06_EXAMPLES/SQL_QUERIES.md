---
title: "Common SQL Queries - Order Workflow"
id: "SQL-QUERIES-EXAMPLES-01"
module: "Order Workflow"
last_updated: "2025-11-24"
version: "1.0"
type: "SQL Examples"
tags: ["SQL", "queries", "examples", "database", "analytics", "reporting", "orders", "returns", "invoices", "inventory"]
purpose: "Provides a collection of common SQL queries for various operations and analytics within the Order Workflow module, including order details, status tracking, financial reporting, return analysis, invoice management, and inventory reconciliation."
location: "docs/tasks/MAIN_MODULES/06_EXAMPLES"
related_to:
  - id: "ORDERS-TABLE-01"
    description: "Queries directly interact with the orders table."
  - id: "INVOICES-TABLES-01"
    description: "Queries for invoices."
  - id: "RETURNS-TABLES-01"
    description: "Queries for returns."
  - id: "PAYMENT-METHODS-TABLE-01"
    description: "Queries for payment methods."
  - id: "SUPPORTING-TABLES-01"
    description: "Queries for inventory_movements and order_status_logs."
  - id: "SAMPLE-DATA-EXAMPLES-01"
    description: "Data used in these queries."
  - id: "API-PAYLOADS-EXAMPLES-01"
    description: "Queries validate data from API operations."
  - id: "DATABASE-RELATIONSHIPS-01"
    description: "Uses the defined relationships."
---

# Common SQL Queries

**Module:** Order Workflow

**Last Updated:** 2025-11-24

---

## 🎯 MỤC ĐÍCH

Document này cung cấp **common SQL queries** cho Order Workflow module.

---

## 📭 ORDER QUERIES

### **Get Order with Details**

```sql
SELECT 
    o.*,
    [c.name](http://c.name) as customer_name,
    [c.phone](http://c.phone) as customer_phone,
    [b.name](http://b.name) as branch_name,
    [pm.name](http://pm.name) as payment_method_name
FROM orders o
JOIN customers c ON [c.id](http://c.id) = o.customer_id
JOIN branches b ON [b.id](http://b.id) = o.branch_id
JOIN payment_methods pm ON pm.code = o.payment_method
WHERE [o.id](http://o.id) = 1;
```

---

### **Get Order with Items**

```sql
SELECT 
    o.order_number,
    [o.total](http://o.total),
    o.status,
    oi.product_name,
    oi.variant_name,
    oi.quantity,
    oi.price,
    oi.subtotal
FROM orders o
JOIN order_items oi ON oi.order_id = [o.id](http://o.id)
WHERE [o.id](http://o.id) = 1;
```

---

### **Today's Orders**

```sql
SELECT 
    COUNT(*) as order_count,
    SUM(total) as total_revenue,
    SUM(CASE WHEN is_paid = TRUE THEN total ELSE 0 END) as paid_revenue,
    SUM(debt_amount) as total_debt
FROM orders
WHERE DATE(created_at) = CURDATE();
```

---

### **Orders by Status**

```sql
SELECT 
    status,
    COUNT(*) as count,
    SUM(total) as total_amount
FROM orders
WHERE DATE(created_at) = CURDATE()
GROUP BY status
ORDER BY count DESC;
```

---

### **Customer Orders**

```sql
SELECT 
    o.order_number,
    o.created_at,
    [o.total](http://o.total),
    o.status,
    [o.is](http://o.is)_paid,
    COUNT([oi.id](http://oi.id)) as item_count
FROM orders o
LEFT JOIN order_items oi ON oi.order_id = [o.id](http://o.id)
WHERE o.customer_id = 1
GROUP BY [o.id](http://o.id)
ORDER BY o.created_at DESC;
```

---

## 📊 ANALYTICS QUERIES

### **Revenue by Month**

```sql
SELECT 
    DATE_FORMAT(completed_at, '%Y-%m') as month,
    COUNT(*) as order_count,
    SUM(total) as total_revenue,
    AVG(total) as avg_order_value
FROM orders
WHERE status = 'completed'
  AND completed_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
GROUP BY DATE_FORMAT(completed_at, '%Y-%m')
ORDER BY month DESC;
```

---

### **Revenue by Branch**

```sql
SELECT 
    [b.name](http://b.name) as branch_name,
    COUNT([o.id](http://o.id)) as order_count,
    SUM([o.total](http://o.total)) as total_revenue,
    SUM(CASE WHEN o.order_type = 'POS' THEN [o.total](http://o.total) ELSE 0 END) as pos_revenue,
    SUM(CASE WHEN o.order_type = 'SHIPPING' THEN [o.total](http://o.total) ELSE 0 END) as shipping_revenue
FROM branches b
LEFT JOIN orders o ON o.branch_id = [b.id](http://b.id) AND o.status = 'completed'
WHERE o.completed_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
GROUP BY [b.id](http://b.id), [b.name](http://b.name)
ORDER BY total_revenue DESC;
```

---

### **Top Customers**

```sql
SELECT 
    [c.name](http://c.name),
    [c.phone](http://c.phone),
    COUNT([o.id](http://o.id)) as order_count,
    SUM([o.total](http://o.total)) as total_spent,
    MAX(o.created_at) as last_order_date
FROM customers c
JOIN orders o ON o.customer_id = [c.id](http://c.id)
WHERE o.status = 'completed'
GROUP BY [c.id](http://c.id), [c.name](http://c.name), [c.phone](http://c.phone)
ORDER BY total_spent DESC
LIMIT 20;
```

---

### **Top Products**

```sql
SELECT 
    oi.product_name,
    oi.variant_name,
    SUM(oi.quantity) as total_quantity,
    SUM(oi.subtotal) as total_revenue,
    COUNT(DISTINCT oi.order_id) as order_count
FROM order_items oi
JOIN orders o ON [o.id](http://o.id) = oi.order_id
WHERE o.status = 'completed'
  AND o.completed_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
GROUP BY oi.product_id, oi.variant_id, oi.product_name, oi.variant_name
ORDER BY total_revenue DESC
LIMIT 20;
```

---

## 🔄 RETURN QUERIES

### **Return Rate by Month**

```sql
SELECT 
    DATE_FORMAT(o.completed_at, '%Y-%m') as month,
    COUNT(DISTINCT [o.id](http://o.id)) as total_orders,
    COUNT(DISTINCT [r.id](http://r.id)) as total_returns,
    ROUND(COUNT(DISTINCT [r.id](http://r.id)) * 100.0 / COUNT(DISTINCT [o.id](http://o.id)), 2) as return_rate
FROM orders o
LEFT JOIN returns r ON r.order_id = [o.id](http://o.id) AND r.status != 'rejected'
WHERE o.status = 'completed'
  AND o.completed_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
GROUP BY DATE_FORMAT(o.completed_at, '%Y-%m')
ORDER BY month DESC;
```

---

### **Return Reasons**

```sql
SELECT 
    reason,
    COUNT(*) as count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM returns), 2) as percentage,
    SUM(refund_amount) as total_refunded
FROM returns
WHERE status IN ('approved', 'completed')
GROUP BY reason
ORDER BY count DESC;
```

---

## 🧾 INVOICE QUERIES

### **Overdue Invoices**

```sql
SELECT 
    i.invoice_number,
    [c.name](http://c.name) as customer_name,
    [c.phone](http://c.phone),
    i.issue_date,
    i.due_date,
    [i.total](http://i.total),
    DATEDIFF(CURDATE(), i.due_date) as days_overdue
FROM invoices i
JOIN customers c ON [c.id](http://c.id) = i.customer_id
WHERE i.due_date < CURDATE()
ORDER BY days_overdue DESC;
```

---

### **Customer Invoice Summary**

```sql
SELECT 
    [c.name](http://c.name),
    [c.tax](http://c.tax)_code,
    COUNT([i.id](http://i.id)) as invoice_count,
    SUM([i.total](http://i.total)) as total_amount,
    MAX(i.issue_date) as last_invoice_date
FROM customers c
JOIN invoices i ON i.customer_id = [c.id](http://c.id)
WHERE c.customer_type = 'wholesale'
GROUP BY [c.id](http://c.id), [c.name](http://c.name), [c.tax](http://c.tax)_code
ORDER BY total_amount DESC;
```

---

## 📦 INVENTORY QUERIES

### **Low Stock Alert**

```sql
SELECT 
    [p.name](http://p.name) as product_name,
    [v.name](http://v.name) as variant_name,
    [b.name](http://b.name) as branch_name,
    i.quantity as current_stock,
    p.min_stock_threshold
FROM inventory i
JOIN products p ON [p.id](http://p.id) = i.product_id
LEFT JOIN variants v ON [v.id](http://v.id) = i.variant_id
JOIN branches b ON [b.id](http://b.id) = i.branch_id
WHERE i.quantity < p.min_stock_threshold
ORDER BY i.quantity ASC;
```

---

### **Inventory Movement History**

```sql
SELECT 
    im.created_at,
    im.type,
    im.quantity,
    im.reference_type,
    im.notes,
    [u.name](http://u.name) as created_by_name
FROM inventory_movements im
JOIN users u ON [u.id](http://u.id) = im.created_by
WHERE im.product_id = 101
  AND im.variant_id = 201
  AND im.branch_id = 1
ORDER BY im.created_at DESC
LIMIT 50;
```

---

### **Inventory Reconciliation**

```sql
SELECT 
    i.product_id,
    [p.name](http://p.name) as product_name,
    i.branch_id,
    [b.name](http://b.name) as branch_name,
    i.quantity as current_quantity,
    COALESCE(SUM(im.quantity), 0) as calculated_quantity,
    i.quantity - COALESCE(SUM(im.quantity), 0) as discrepancy
FROM inventory i
JOIN products p ON [p.id](http://p.id) = i.product_id
JOIN branches b ON [b.id](http://b.id) = i.branch_id
LEFT JOIN inventory_movements im ON im.product_id = i.product_id 
    AND im.variant_id = i.variant_id
    AND im.branch_id = i.branch_id
GROUP BY [i.id](http://i.id), i.product_id, [p.name](http://p.name), i.branch_id, [b.name](http://b.name), i.quantity
HAVING discrepancy != 0;
```

---

## 📅 STATUS LOG QUERIES

### **Order Lifecycle Time**

```sql
SELECT 
    o.order_number,
    MIN(CASE WHEN [osl.to](http://osl.to)_status = 'confirmed' THEN osl.changed_at END) as confirmed_at,
    MIN(CASE WHEN [osl.to](http://osl.to)_status = 'processing' THEN osl.changed_at END) as processing_at,
    MIN(CASE WHEN [osl.to](http://osl.to)_status = 'shipping' THEN osl.changed_at END) as shipping_at,
    MIN(CASE WHEN [osl.to](http://osl.to)_status = 'delivered' THEN osl.changed_at END) as delivered_at,
    MIN(CASE WHEN [osl.to](http://osl.to)_status = 'completed' THEN osl.changed_at END) as completed_at,
    TIMESTAMPDIFF(
        HOUR,
        MIN(CASE WHEN [osl.to](http://osl.to)_status = 'confirmed' THEN osl.changed_at END),
        MIN(CASE WHEN [osl.to](http://osl.to)_status = 'completed' THEN osl.changed_at END)
    ) as total_hours
FROM orders o
JOIN order_status_logs osl ON osl.order_id = [o.id](http://o.id)
WHERE o.status = 'completed'
GROUP BY [o.id](http://o.id), o.order_number
ORDER BY completed_at DESC
LIMIT 10;
```

---

## 🔗 RELATED DOCUMENTS

- [**SAMPLE_[DATA.md](http://DATA.md)]** - Sample data examples
- [**API_[PAYLOADS.md](http://PAYLOADS.md)]** - API payload examples
- [**RELATIONSHIPS.md**](http://RELATIONSHIPS.md) - Database relationships