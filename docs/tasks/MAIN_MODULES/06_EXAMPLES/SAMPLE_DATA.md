---
title: "Sample Data Examples - Order Workflow"
id: "SAMPLE-DATA-EXAMPLES-01"
module: "Order Workflow"
last_updated: "2025-11-24"
version: "1.0"
type: "Data Examples"
tags: ["sample-data", "examples", "database", "SQL", "seeding", "customers", "orders", "returns", "invoices"]
purpose: "Provides comprehensive SQL INSERT statements for various entities within the Order Workflow, including customers, branches, payment methods, POS/Shipping orders, returns, and invoices, along with complex scenarios."
location: "docs/tasks/MAIN_MODULES/06_EXAMPLES"
related_to:
  - id: "ORDERS-TABLE-01"
    description: "Provides sample data for orders table."
  - id: "INVOICES-TABLES-01"
    description: "Provides sample data for invoices tables."
  - id: "RETURNS-TABLES-01"
    description: "Provides sample data for returns tables."
  - id: "PAYMENT-METHODS-TABLE-01"
    description: "Provides sample data for payment methods."
  - id: "API-PAYLOADS-EXAMPLES-01"
    description: "Complements API examples with data."
  - id: "SQL-QUERIES-EXAMPLES-01"
    description: "Data used in SQL queries."
---

# SAMPLE_DATA - Sample Data Examples

# Sample Data Examples

**Module:** Order Workflow

**Last Updated:** 2025-11-24

---

## 🎯 MỤC ĐÍCH

Document này cung cấp **sample data** cho các entities trong Order Workflow.

---

## 👥 CUSTOMERS

### **B2C Customers**

```sql
INSERT INTO customers (name, phone, email, customer_type) VALUES
('Nguyễn Văn A', '0912345678', '[nguyenvana@example.com](mailto:nguyenvana@example.com)', 'retail'),
('Trần Thị B', '0923456789', '[tranthib@example.com](mailto:tranthib@example.com)', 'retail'),
('Lê Văn C', '0934567890', '[levanc@example.com](mailto:levanc@example.com)', 'retail');
```

---

### **B2B Customers**

```sql
INSERT INTO customers (
    name, 
    phone, 
    email, 
    customer_type,
    company_name,
    tax_code
) VALUES
('Công ty TNHH ABC', '0243456789', '[contact@abc.vn](mailto:contact@abc.vn)', 'wholesale', 'Công ty TNHH ABC', '0123456789'),
('Công ty CP XYZ', '0283456789', '[info@xyz.vn](mailto:info@xyz.vn)', 'wholesale', 'Công ty Cổ Phần XYZ', '9876543210');
```

---

## 🏪 BRANCHES

```sql
INSERT INTO branches (
    code, 
    name, 
    phone, 
    address, 
    ward, 
    district, 
    city
) VALUES
('HN', 'Chi nhánh Hà Nội', '024 1234 5678', '123 Nguyễn Trãi', 'Phường Thanh Xuân Trung', 'Quận Thanh Xuân', 'Hà Nội'),
('HCM', 'Chi nhánh TP.HCM', '028 8765 4321', '456 Lê Lợi', 'Phường Bến Thành', 'Quận 1', 'TP.HCM'),
('DN', 'Chi nhánh Đà Nẵng', '0236 3456 789', '789 Trần Phú', 'Phường Thạch Thang', 'Quận Hải Châu', 'Đà Nẵng');
```

---

## 💳 PAYMENT METHODS

```sql
INSERT INTO payment_methods (code, name, description, display_order) VALUES
('CASH', 'Tiền mặt', 'Thanh toán bằng tiền mặt tại cửa hàng', 1),
('BANK_TRANSFER', 'Chuyển khoản', 'Chuyển khoản qua ngân hàng', 2),
('CARD', 'Thẻ tín dụng/ghi nợ', 'Thanh toán bằng thẻ Visa/Mastercard/JCB', 3),
('COD', 'Thu hộ (COD)', 'Thanh toán khi nhận hàng. Phí COD: 15,000đ', 4),
('E_WALLET', 'Ví điện tử', 'Thanh toán qua MoMo, ZaloPay, VNPay', 5);
```

---

## 📭 ORDER - POS

### **Scenario: Bán tại quầy**

```sql
-- Order
INSERT INTO orders (
    order_number,
    customer_id,
    branch_id,
    order_type,
    payment_method,
    subtotal,
    discount,
    shipping_fee,
    total,
    paid_amount,
    is_paid,
    debt_amount,
    status,
    created_by
) VALUES (
    'ORD-000001',
    1,  -- Nguyễn Văn A
    1,  -- HN branch
    'POS',
    'CASH',
    2000000,  -- 2,000,000đ
    200000,   -- 200,000đ (10%)
    0,        -- No shipping for POS
    1800000,  -- 1,800,000đ
    1800000,  -- Fully paid
    TRUE,
    0,
    'completed',
    1  -- Staff user_id
);

-- Order Items
INSERT INTO order_items (
    order_id,
    product_id,
    variant_id,
    product_name,
    variant_name,
    sku,
    price,
    quantity,
    subtotal
) VALUES
(1, 101, 201, 'iPhone 15 Pro', '256GB Tím', 'IP15P-256-PUR', 25000000, 1, 25000000),
(1, 102, 202, 'AirPods Pro 2', NULL, 'APP2-GEN2', 5000000, 1, 5000000);
```

---

## 🚚 ORDER - SHIPPING

### **Scenario: Đơn hàng giao hàng**

```sql
-- Order
INSERT INTO orders (
    order_number,
    customer_id,
    branch_id,
    order_type,
    payment_method,
    subtotal,
    discount,
    shipping_fee,
    total,
    paid_amount,
    is_paid,
    debt_amount,
    status,
    shipping_name,
    shipping_phone,
    shipping_address,
    shipping_ward,
    shipping_district,
    shipping_city,
    cod_amount,
    created_by
) VALUES (
    'ORD-000002',
    2,  -- Trần Thị B
    1,  -- HN branch
    'SHIPPING',
    'COD',
    1500000,  -- 1,500,000đ
    0,        -- No discount
    30000,    -- 30,000đ shipping
    1530000,  -- 1,530,000đ
    0,        -- Not paid yet (COD)
    FALSE,
    1530000,
    'shipping',
    'Trần Thị B',
    '0923456789',
    '456 Hoàng Hoa Thám',
    'Phường 12',
    'Quận Tân Bình',
    'TP.HCM',
    1530000,
    1  -- Staff user_id
);

-- Order Items
INSERT INTO order_items (
    order_id,
    product_id,
    variant_id,
    product_name,
    variant_name,
    sku,
    price,
    quantity,
    subtotal
) VALUES
(2, 103, 203, 'MacBook Air M2', '13 inch 256GB', 'MBA-M2-256', 25000000, 1, 25000000),
(2, 104, NULL, 'Magic Mouse', NULL, 'MM-WHT', 2000000, 1, 2000000);
```

---

## 🔄 RETURN

### **Scenario: Trả hàng lỗi**

```sql
-- Return
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
    notes,
    created_by
) VALUES (
    'TH-2-1',
    2,  -- Order ORD-000002
    2,  -- Trần Thị B
    2000000,   -- Magic Mouse price
    TRUE,      -- Refund shipping (defective)
    2030000,   -- 2,000,000 + 30,000 shipping
    'bank_transfer',
    'defective',
    'Chuột bị lỗi, không kết nối được',
    'approved',
    3,  -- Admin user_id
    NOW(),
    'Đồng ý trả hàng, hoàn tiền trong 3 ngày',
    2  -- Customer user_id
);

-- Return Items
INSERT INTO return_items (
    return_id,
    order_item_id,
    quantity_returned,
    condition
) VALUES
(1, 4, 1, 'damaged');  -- Order item #4 (Magic Mouse)
```

---

## 🧾 INVOICE

### **Scenario: Hóa đơn VAT cho doanh nghiệp**

```sql
-- Invoice
INSERT INTO invoices (
    invoice_number,
    customer_id,
    branch_id,
    issue_date,
    due_date,
    subtotal,
    vat_rate,
    vat_amount,
    total,
    notes,
    created_by
) VALUES (
    'HD-1-0001',
    4,  -- Công ty TNHH ABC
    1,  -- HN branch
    '2024-11-24',
    '2024-12-24',  -- 30 days
    5000000,   -- 5,000,000đ
    0.10,      -- 10%
    500000,    -- 500,000đ
    5500000,   -- 5,500,000đ
    'Hóa đơn tháng 11/2024 - Gộp 2 đơn hàng',
    3  -- Admin user_id
);

-- Invoice Orders (Multiple orders in one invoice)
INSERT INTO invoice_orders (invoice_id, order_id) VALUES
(1, 1),  -- ORD-000001
(1, 3);  -- ORD-000003 (not shown above)
```

---

## 📊 ORDER STATUS LOGS

```sql
INSERT INTO order_status_logs (
    order_id,
    from_status,
    to_status,
    notes,
    changed_by
) VALUES
-- Order #2 lifecycle
(2, NULL, 'draft', NULL, 1),
(2, 'draft', 'confirmed', 'Khách xác nhận đặt hàng', 1),
(2, 'confirmed', 'processing', 'Bắt đầu chuẩn bị hàng', 1),
(2, 'processing', 'shipping', 'Chuyển cho đơn vị vận chuyển', 1),
(2, 'shipping', 'delivered', 'Khách đã nhận hàng', 1);
```

---

## 📦 INVENTORY MOVEMENTS

```sql
INSERT INTO inventory_movements (
    branch_id,
    product_id,
    variant_id,
    type,
    quantity,
    reference_type,
    reference_id,
    notes,
    created_by
) VALUES
-- Sale from Order #1
(1, 101, 201, 'sale', -1, 'order', 1, NULL, 1),
(1, 102, 202, 'sale', -1, 'order', 1, NULL, 1),

-- Return from Return #1
(1, 104, NULL, 'return', +1, 'return', 1, NULL, 2),

-- Manual adjustment
(1, 101, 201, 'adjustment', +10, NULL, NULL, 'Nhập kho mới', 3);
```

---

## 💰 COMPLEX SCENARIOS

### **Scenario 1: Partial Return**

```sql
-- Order: 5x iPhone 15
INSERT INTO orders (
    order_number, customer_id, branch_id, order_type, payment_method,
    subtotal, discount, shipping_fee, total, paid_amount, is_paid,
    status, created_by
) VALUES (
    'ORD-000005', 3, 1, 'SHIPPING', 'BANK_TRANSFER',
    125000000, 0, 50000, 125050000, 125050000, TRUE,
    'completed', 1
);

INSERT INTO order_items (
    order_id, product_id, variant_id, product_name, sku,
    price, quantity, subtotal
) VALUES
(5, 101, 201, 'iPhone 15 Pro 256GB', 'IP15P-256', 25000000, 5, 125000000);

-- Return 1: 2 units
INSERT INTO returns (
    return_number, order_id, customer_id,
    return_amount, refund_amount, refund_method,
    reason, status, approved_by, approved_at, created_by
) VALUES (
    'TH-5-1', 5, 3,
    50000000, 50000000, 'bank_transfer',
    'not_satisfied', 'completed', 3, NOW(), 3
);

INSERT INTO return_items (return_id, order_item_id, quantity_returned, condition)
VALUES (2, 10, 2, 'new');

-- Return 2: 1 unit
INSERT INTO returns (
    return_number, order_id, customer_id,
    return_amount, refund_amount, refund_method,
    reason, status, approved_by, approved_at, created_by
) VALUES (
    'TH-5-2', 5, 3,
    25000000, 25000000, 'bank_transfer',
    'not_satisfied', 'approved', 3, NOW(), 3
);

INSERT INTO return_items (return_id, order_item_id, quantity_returned, condition)
VALUES (3, 10, 1, 'new');

-- Remaining: 2 units (not returned)
```

---

### **Scenario 2: Monthly Invoice for B2B**

```sql
-- 5 orders in November from Company ABC
INSERT INTO orders (
    order_number, customer_id, branch_id, order_type, payment_method,
    subtotal, total, status, created_at
) VALUES
('ORD-001001', 4, 1, 'SHIPPING', 'BANK_TRANSFER', 10000000, 10000000, 'completed', '2024-11-05'),
('ORD-001002', 4, 1, 'SHIPPING', 'BANK_TRANSFER', 8000000, 8000000, 'completed', '2024-11-10'),
('ORD-001003', 4, 1, 'SHIPPING', 'BANK_TRANSFER', 12000000, 12000000, 'completed', '2024-11-15'),
('ORD-001004', 4, 1, 'SHIPPING', 'BANK_TRANSFER', 9000000, 9000000, 'completed', '2024-11-20'),
('ORD-001005', 4, 1, 'SHIPPING', 'BANK_TRANSFER', 11000000, 11000000, 'completed', '2024-11-25');

-- Create monthly invoice
INSERT INTO invoices (
    invoice_number, customer_id, branch_id,
    issue_date, due_date,
    subtotal, vat_rate, vat_amount, total,
    notes, created_by
) VALUES (
    'HD-1-0011',
    4,  -- Company ABC
    1,
    '2024-11-30',
    '2024-12-30',
    50000000,  -- Sum of 5 orders
    0.10,
    5000000,
    55000000,
    'Hóa đơn tháng 11/2024 - Gộp 5 đơn hàng',
    3
);

-- Link orders to invoice
INSERT INTO invoice_orders (invoice_id, order_id)
SELECT 11, id FROM orders WHERE order_number IN (
    'ORD-001001', 'ORD-001002', 'ORD-001003', 'ORD-001004', 'ORD-001005'
);
```

---

## 🔗 RELATED DOCUMENTS

- [**API_[PAYLOADS.md](http://PAYLOADS.md)]** - API request/response examples
- [**SQL_[QUERIES.md](http://QUERIES.md)]** - Common SQL queries
- [**ORDERS_](https://www.notion.so/ORDERS_TABLE-Orders-Table-Schema-36b32ddd5ce6421abd19d92589270881?pvs=21)[TABLE.md](http://TABLES.md)** - Orders schema