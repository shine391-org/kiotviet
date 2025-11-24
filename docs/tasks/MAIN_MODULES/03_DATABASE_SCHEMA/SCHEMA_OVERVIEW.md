---
title: "Database Schema Overview - Order Workflow"
id: "SCHEMA-OVERVIEW-01"
module: "Order Workflow"
last_updated: "2025-11-24"
version: "1.0"
type: "Database Schema"
tags: ["database", "schema", "overview", "ERD", "tables", "relationships"]
purpose: "Provides a high-level overview of the database schema for the Order Workflow module, summarizing core tables, payment, invoice, return, and supporting tables, along with key relationships and integrity rules."
location: "docs/tasks/MAIN_MODULES/03_DATABASE_SCHEMA"
related_to:
  - id: "BUSINESS-DECISIONS-01"
    description: "Related to all business decisions for the module."
  - id: "ORDERS-TABLE-01"
    description: "Detailed schema for the orders table."
  - id: "PAYMENT-METHODS-TABLE-01"
    description: "Detailed schema for the payment_methods table."
  - id: "INVOICES-TABLES-01"
    description: "Detailed schema for the invoices tables."
  - id: "RETURNS-TABLES-01"
    description: "Detailed schema for the returns tables."
  - id: "SUPPORTING-TABLES-01"
    description: "Detailed schema for various supporting tables."
  - id: "DATABASE-RELATIONSHIPS-01"
    description: "Detailed relationships document."
  - id: "ORDER-WORKFLOW-INDEX"
    description: "Referenced by the main Order Workflow Index."
---

# SCHEMA_OVERVIEW - Database Schema Overview

# Database Schema Overview

**Module:** Order Workflow

**Last Updated:** 2025-11-24

**Version:** 1.0

---

## 🎯 MỤC ĐÍCH

Document này cung cấp overview về database schema cho **Order Workflow module**.

**Scope:** 5 tasks (PAY-001, INV-001, ORD-003, RETURN-001, RETURN-002)

---

## 📊 TABLES OVERVIEW

### **Core Tables (3 tables)**

1. **orders** - Đơn hàng chính
2. **order_items** - Chi tiết items trong đơn
3. **order_status_logs** - Audit log status changes

### **Payment Tables (1 table)**

1. **payment_methods** - Phương thức thanh toán

### **Invoice Tables (2 tables)**

1. **invoices** - Hóa đơn VAT
2. **invoice_orders** - Mapping invoices ↔ orders

### **Return Tables (2 tables)**

1. **returns** - Đơn trả hàng
2. **return_items** - Chi tiết items trả

### **Supporting Tables (2 tables)**

1. **inventory_movements** - Log nhập/xuất kho
2. **customers** - Khách hàng (đã có sẵn)

**Total:** 10 tables (8 tables mới + 2 existing)

---

## 🗺️ ENTITY RELATIONSHIP DIAGRAM

```
┌──────────────┐
│  customers   │
└──────┬───────┘
       │ 1
       │
       │ N
┌──────▼───────┐        ┌─────────────────┐
│   orders     │───────►│ payment_methods │
└──────┬───────┘  N:1   └─────────────────┘
       │ 1
       ├──────────────┐
       │              │
       │ N            │ N
┌──────▼───────┐  ┌───▼──────────┐
│ order_items  │  │order_status_ │
│              │  │    logs      │
└──────────────┘  └──────────────┘
       │
       │
       ├──────────────────┬─────────────────┐
       │ 1                │ 1               │ N
       │ N                │ N               │
┌──────▼──────┐   ┌───────▼───────┐  ┌─────▼────────┐
│  invoices   │   │   returns     │  │  inventory_  │
│             │   │               │  │  movements   │
└──────┬──────┘   └───────┬───────┘  └──────────────┘
       │ N                │ 1
       │                  │
       │ N                │ N
┌──────▼──────┐   ┌───────▼────────┐
│invoice_     │   │ return_items   │
│  orders     │   │                │
└─────────────┘   └────────────────┘
```

---

## 📋 TABLE DETAILS SUMMARY

### 1. orders

**Purpose:** Lưu thông tin đơn hàng

**Key Fields:**

- `id` - Primary key
- `customer_id` - FK to customers
- `branch_id` - FK to branches
- `order_number` - Mã đơn (ORD-001234)
- `status` - ENUM (draft, confirmed, shipping, etc.)
- `payment_method` - FK to payment_methods
- `subtotal`, `discount`, `total`
- `paid_amount`, `debt_amount`
- `shipping_fee`, `shipping_partner`

**Size:** ~30 columns

**Related Tables:**

- customers (N:1)
- payment_methods (N:1)
- order_items (1:N)
- invoices (N:N via invoice_orders)
- returns (1:N)

---

### 2. order_items

**Purpose:** Chi tiết sản phẩm trong đơn hàng

**Key Fields:**

- `id` - Primary key
- `order_id` - FK to orders
- `product_id` - FK to products
- `variant_id` - FK to product_variants
- `quantity`
- `price` - Giá tại thời điểm bán
- `discount`
- `total`

**Size:** ~12 columns

**Related Tables:**

- orders (N:1)
- products (N:1)
- product_variants (N:1)
- return_items (1:N)

---

### 3. order_status_logs

**Purpose:** Audit log cho status changes

**Key Fields:**

- `id` - Primary key
- `order_id` - FK to orders
- `old_status`
- `new_status`
- `user_id` - Who changed (null if webhook)
- `notes`
- `created_at`

**Size:** ~8 columns

**Related Tables:**

- orders (N:1)
- users (N:1)

---

### 4. payment_methods

**Purpose:** Master data - Phương thức thanh toán

**Key Fields:**

- `id` - Primary key
- `code` - CASH, BANK_TRANSFER, CARD, COD, E_WALLET
- `name` - Tên hiển thị
- `is_active`

**Size:** ~6 columns

**Type:** Master data (ít thay đổi)

**Related Tables:**

- orders (1:N)

---

### 5. invoices

**Purpose:** Hóa đơn VAT

**Key Fields:**

- `id` - Primary key
- `invoice_number` - HD-{branch}-{num}
- `customer_id` - FK to customers
- `branch_id` - FK to branches
- `issue_date`, `due_date`
- `subtotal`, `vat_rate`, `vat_amount`, `total`
- `pdf_path`

**Size:** ~15 columns

**Related Tables:**

- customers (N:1)
- branches (N:1)
- orders (N:N via invoice_orders)

---

### 6. invoice_orders

**Purpose:** Mapping table - nhiều orders → 1 invoice

**Key Fields:**

- `id` - Primary key
- `invoice_id` - FK to invoices
- `order_id` - FK to orders

**Size:** ~4 columns

**Type:** Join table

**Related Tables:**

- invoices (N:1)
- orders (N:1)

---

### 7. returns

**Purpose:** Đơn trả hàng

**Key Fields:**

- `id` - Primary key
- `return_number` - TH-{order_id}-{num}
- `order_id` - FK to orders
- `customer_id` - FK to customers
- `return_amount`, `refund_amount`
- `refund_method`, `refund_shipping_fee`
- `status` - ENUM (pending, approved, rejected, completed)
- `reason`, `reason_detail`

**Size:** ~18 columns

**Related Tables:**

- orders (N:1)
- customers (N:1)
- return_items (1:N)

---

### 8. return_items

**Purpose:** Chi tiết items trả hàng

**Key Fields:**

- `id` - Primary key
- `return_id` - FK to returns
- `order_item_id` - FK to order_items
- `quantity_returned`
- `condition` - ENUM (new, used, damaged)

**Size:** ~6 columns

**Related Tables:**

- returns (N:1)
- order_items (N:1)

---

### 9. inventory_movements

**Purpose:** Log mọi giao dịch inventory

**Key Fields:**

- `id` - Primary key
- `branch_id` - FK to branches
- `product_id` - FK to products
- `variant_id` - FK to product_variants
- `type` - ENUM (sale, return, cancel, adjustment)
- `quantity` - âm = trừ, dương = cộng
- `reference_type` - 'order', 'return'
- `reference_id`

**Size:** ~12 columns

**Related Tables:**

- branches (N:1)
- products (N:1)
- product_variants (N:1)
- orders (N:1 polymorphic)
- returns (N:1 polymorphic)

---

### 10. customers (Existing)

**Purpose:** Thông tin khách hàng

**Key Fields:**

- `id` - Primary key
- `name`, `phone`, `email`
- `address`
- `tax_code` - Mã số thuế (for invoices)

**Note:** Table đã tồn tại, chỉ cần thêm field `tax_code`

---

## 🔗 RELATIONSHIPS MATRIX

| From Table | To Table | Type | FK Field |
| --- | --- | --- | --- |
| orders | customers | N:1 | customer_id |
| orders | branches | N:1 | branch_id |
| orders | payment_methods | N:1 | payment_method |
| order_items | orders | N:1 | order_id |
| order_items | products | N:1 | product_id |
| order_items | product_variants | N:1 | variant_id |
| order_status_logs | orders | N:1 | order_id |
| order_status_logs | users | N:1 | user_id |
| invoices | customers | N:1 | customer_id |
| invoices | branches | N:1 | branch_id |
| invoice_orders | invoices | N:1 | invoice_id |
| invoice_orders | orders | N:1 | order_id |
| returns | orders | N:1 | order_id |
| returns | customers | N:1 | customer_id |
| return_items | returns | N:1 | return_id |
| return_items | order_items | N:1 | order_item_id |
| inventory_movements | branches | N:1 | branch_id |
| inventory_movements | products | N:1 | product_id |
| inventory_movements | product_variants | N:1 | variant_id |

---

## 📏 SIZE ESTIMATES

### **Storage Estimates (1 year)**

Assumptions:

- 10,000 orders/month
- Average 3 items/order
- 5% return rate
- 20% invoice rate

| Table | Rows/Year | Avg Row Size | Total Size |
| --- | --- | --- | --- |
| orders | 120,000 | 1 KB | 120 MB |
| order_items | 360,000 | 200 B | 72 MB |
| order_status_logs | 600,000 | 150 B | 90 MB |
| invoices | 24,000 | 500 B | 12 MB |
| invoice_orders | 50,000 | 100 B | 5 MB |
| returns | 6,000 | 800 B | 5 MB |
| return_items | 15,000 | 150 B | 2 MB |
| inventory_movements | 400,000 | 200 B | 80 MB |
| **TOTAL** |  |  | **~386 MB** |

**Conclusion:** Nhỏ, không cần partition trong Phase 1

---

## 🔍 INDEXES STRATEGY

### **Primary Keys**

All tables có `id BIGINT AUTO_INCREMENT PRIMARY KEY`

### **Foreign Keys**

Auto index trên mọi FK columns

### **Additional Indexes**

**orders:**

- `INDEX (customer_id, created_at)` - Customer order history
- `INDEX (status, created_at)` - Status filtering
- `INDEX (order_number)` - Quick lookup
- `UNIQUE (order_number)` - Enforce uniqueness

**order_items:**

- `INDEX (order_id)` - Get items for order
- `INDEX (product_id)` - Product analytics

**invoices:**

- `INDEX (customer_id, issue_date)` - Customer invoices
- `INDEX (invoice_number)` - Quick lookup
- `UNIQUE (invoice_number)` - Enforce uniqueness

**returns:**

- `INDEX (order_id)` - Get returns for order
- `INDEX (status, created_at)` - Pending returns
- `UNIQUE (return_number)` - Enforce uniqueness

**inventory_movements:**

- `INDEX (product_id, created_at)` - Product history
- `INDEX (reference_type, reference_id)` - Polymorphic lookup

---

## 🔒 DATA INTEGRITY

### **Foreign Key Constraints**

**ON DELETE CASCADE:**

- `order_items.order_id` → orders
- `order_status_logs.order_id` → orders
- `invoice_orders.invoice_id` → invoices
- `return_items.return_id` → returns

**Why:** Nếu xóa order/invoice/return → xóa luôn children

**ON DELETE RESTRICT:**

- `customers.customer_id` → orders
- `invoices.customer_id` → customers
- `returns.order_id` → orders

**Why:** Không cho xóa customer/order nếu còn references

### **UNIQUE Constraints**

- `orders.order_number` UNIQUE
- `invoices.invoice_number` UNIQUE
- `returns.return_number` UNIQUE
- `invoice_orders(invoice_id, order_id)` UNIQUE

### **CHECK Constraints**

```sql
-- orders
CHECK (total >= 0)
CHECK (paid_amount >= 0)
CHECK (paid_amount <= total)

-- order_items
CHECK (quantity > 0)
CHECK (price >= 0)

-- invoices
CHECK (vat_rate >= 0 AND vat_rate <= 1)
CHECK (total >= subtotal)

-- returns
CHECK (refund_amount >= 0)
CHECK (refund_amount <= [order.total](http://order.total))
```

---

## 🏗️ MIGRATION STRATEGY

### **Phase 1: Core Tables**

1. Create `payment_methods` (master data)
2. Alter `customers` (add tax_code)
3. Alter `orders` (add new fields)
4. Create `order_status_logs`

### **Phase 2: Invoice Tables**

1. Create `invoices`
2. Create `invoice_orders`

### **Phase 3: Return Tables**

1. Create `returns`
2. Create `return_items`

### **Phase 4: Supporting**

1. Create `inventory_movements`

### **Migration Naming Convention**

```
YYYY_MM_DD_HHMMSS_action_table_name.php

Examples:
2025_11_24_100000_create_payment_methods_table.php
2025_11_24_100100_alter_customers_add_tax_code.php
2025_11_24_100200_create_invoices_table.php
```

---

## 🧪 TESTING DATA

### **Seed Data Requirements**

**payment_methods:**

- 5 methods (CASH, BANK, CARD, COD, EWALLET)

**orders:**

- 10 POS orders (various states)
- 20 SHIPPING orders (various states)

**order_items:**

- 3-5 items per order

**invoices:**

- 5 invoices (single order)
- 2 invoices (multiple orders)

**returns:**

- 3 returns (various states)

---

## 📖 DETAILED DOCUMENTATION

Chi tiết từng table:

- [**ORDERS_](760)[TABLE.md](http://TABLE.md)** - Schema đầy đủ cho orders
- [**PAYMENT_METHODS_](761)[TABLE.md](http://TABLE.md)** - Payment methods master data
- [**INVOICES_](762)[TABLES.md](http://TABLES.md)** - Invoices + invoice_orders
- [**RETURNS_](763)[TABLES.md](http://TABLES.md)** - Returns + return_items
- [**SUPPORTING_](764)[TABLES.md](http://TABLES.md)** - inventory_movements, logs
- [**RELATIONSHIPS.md**](http://RELATIONSHIPS.md) - Chi tiết relationships

---

## 🔗 RELATED DOCUMENTS

- **Business Decisions:** All 39 decisions
- **Workflows:** SHIPPING_FLOW, RETURN_FLOW, INVOICE_FLOW
- **Tasks:** PAY-001, INV-001, ORD-003, RETURN-001, RETURN-002