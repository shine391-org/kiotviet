```markdown
# POS FLOW - Point of Sale Orders

**Last Updated:** 2025-11-24  
**Version:** 1.0  
**Related:** [BUSINESS_[DECISIONS.md](http://DECISIONS.md)](../01_BUSINESS_[DECISIONS.md](http://DECISIONS.md)#decision-1)

---

## MỤC ĐÍCH

Document này mô tả workflow cho **POS Orders** - đơn hàng bán tại quầy (Point of Sale).

**Key characteristic:** Không có status workflow, giao dịch tức thì.

---

## OVERVIEW

```

┌─────────────┐

│  Khách đến  │

│    quầy     │

└──────┬──────┘

│

↓

┌─────────────┐

│  Tạo đơn    │

│  POS        │

└──────┬──────┘

│

├─► payment_method = 'CASH'

├─► paid_amount = total

├─► status = null/COMPLETED

│

↓

┌─────────────┐

│  Trừ kho    │◄── NGAY LẬP TỨC

│  NGAY       │

└──────┬──────┘

│

↓

┌─────────────┐

│  Thu tiền   │

│  mặt        │

└──────┬──────┘

│

↓

┌─────────────┐

│  In hóa đơn │

│  (optional) │

└──────┬──────┘

│

↓

┌─────────────┐

│ Giao hàng   │

│ cho khách   │

└─────────────┘

```

**Timeline:** Toàn bộ flow trong vài phút, không có waiting states.

---

## CHARACTERISTICS

### 1. Không có Status Workflow
- POS orders **KHÔNG có** status transitions
- Không DRAFT → CONFIRMED → PROCESSING...
- Status field:
  - Option A: `status = null`
  - Option B: `status = 'COMPLETED'`
- Implementation quyết định option A hay B

**Why:**
- Giao dịch tức thì
- Không cần tracking intermediate states
- Khách đưa tiền → nhận hàng → xong

---

### 2. Payment Method = CASH

**Rule:**
```

orders.payment_method = 'CASH'

```

**Always CASH because:**
- Bán tại quầy = thu tiền mặt ngay
- Không có COD (không ship)
- Không có pay later (không credit)
- Card/E-wallet: có thể support nhưng vẫn mark CASH (paid immediately)

**Validation:**
- Khi tạo POS order: auto set payment_method = 'CASH'
- Không cho phép methods khác

---

### 3. Paid Amount = Total

**Rule:**
```

orders.paid_amount = [orders.total](http://orders.total)

orders.debt_amount = 0

```

**Why:**
- Thu đủ tiền ngay
- Không có partial payment
- Không có debt

**Validation:**
- Auto calculate khi create
- paid_amount MUST equal total

---

### 4. Trừ Kho NGAY

**Rule:**
- Inventory deducted **NGAY KHI TẠO ĐƠN**
- Không delay, không waiting

**Implementation:**
```

BEGIN TRANSACTION

1. Insert orders
2. Insert order_items
3. Deduct inventory
4. Log inventory_movements

COMMIT

```

**Why:**
- Khách đang đứng chờ
- Hàng đã xuất khỏi kho
- Must reflect real-time

---

### 5. Không Shipping

**Rule:**
```

orders.shipping_fee = 0 (hoặc null)

orders.shipping_partner = null

orders.shipping_address = null

```

**Why:**
- Khách mua tại quầy, mang về ngay
- Không có delivery

**Edge case:**
- Nếu shop offer "mua tại quầy, giao về nhà" → đó là SHIPPING order, không phải POS

---

## FLOW CHI TIẾT

### Step 1: Cashier Tạo Đơn

**Input:**
```

{

"customer_id": 123,          // Optional (có thể null nếu khách vãng lai)

"branch_id": 1,              // Quầy nào

"items": [

{

"product_id": 10,

"variant_id": 5,         // Optional

"quantity": 2,

"price": 100000

}

],

"discount": 10000,           // Optional

"notes": "Khách mua lẻ"      // Optional

}

```

**Business Logic:**
- Customer optional (walk-in customers không cần tạo record)
- Branch: cashier's branch
- Price: có thể override (discount, promotion)

---

### Step 2: Validate Items

**Checks:**
1. Products exist và available
2. Stock sufficient tại branch này
3. Prices valid (không âm)
4. Quantities valid (> 0)

**If validation fails:**
- Return 422 error
- Don't create order

---

### Step 3: Calculate Totals

**Formula:**
```

subtotal = SUM(item.price × item.quantity)

discount = [input.discount](http://input.discount)

total = subtotal - discount

paid_amount = total

debt_amount = 0

```

**Validation:**
- total > 0
- discount <= subtotal

---

### Step 4: Create Order (in Transaction)

**Transaction:**
```

BEGIN TRANSACTION

-- 1. Insert order

INSERT INTO orders (

customer_id,

branch_id,

order_type,          -- 'POS' (nếu có field này)

payment_method,      -- 'CASH'

status,              -- null hoặc 'COMPLETED'

subtotal,

discount,

total,

paid_amount,         -- = total

debt_amount,         -- = 0

created_by,

created_at

) VALUES (...);

-- 2. Insert order_items

INSERT INTO order_items (order_id, product_id, variant_id, quantity, price, ...)

VALUES (...), (...), ...;

-- 3. Deduct inventory (CRITICAL)

UPDATE inventory

SET quantity = quantity - ?

WHERE branch_id = ?

AND product_id = ?

AND variant_id = ?;

-- Check affected rows = expected

-- If mismatch → ROLLBACK (concurrent update?)

-- 4. Log inventory movements

INSERT INTO inventory_movements (

branch_id, product_id, variant_id,

type,              -- 'sale'

quantity,          -- negative

reference_type,    -- 'order'

reference_id,      -- order_id

created_at

) VALUES (...), (...), ...;

COMMIT

```

**If any step fails:**
- ROLLBACK entire transaction
- Return error to cashier

---

### Step 5: Return Response

**Success response:**
```

{

"success": true,

"order_id": 456,

"order_number": "POS-2024-001234",

"total": 190000,

"paid_amount": 190000,

"items": [...],

"created_at": "2024-11-24T10:30:00Z"

}

```

**Cashier actions:**
- Print receipt (optional)
- Give items to customer
- Complete transaction

---

## SPECIAL CASES

### Case 1: Customer Vãng Lai (Walk-in)

**Scenario:** Khách mua không cần thông tin

**Solution:**
```

orders.customer_id = null

```

**Or:**
- Tạo customer default "Khách vãng lai" (id = 1)
- All walk-in orders → customer_id = 1

**Implementation choice:** Tùy business

---

### Case 2: Insufficient Stock

**Scenario:** Order 5 items, chỉ còn 3

**Handling:**
```

Validation BEFORE create:

- Check stock availability
- If insufficient → return 422
- Message: "Chỉ còn 3 sản phẩm trong kho"

```

**Don't:**
- Partial fulfill (create order với 3 items)
- Phase 1: all or nothing

**Cashier action:**
- Reduce quantity
- Or remove item
- Re-submit

---

### Case 3: Cancel POS Order

**Scenario:** Khách đổi ý, hoặc nhầm lẫn

**Rule:**
- Admin có thể cancel POS orders
- Rollback inventory NGAY

**Implementation:**
```

BEGIN TRANSACTION

-- Update order

UPDATE orders

SET

status = 'cancelled',

cancel_reason = ?,

cancel_notes = ?,

updated_at = NOW()

WHERE id = ?;

-- Rollback inventory

UPDATE inventory

SET quantity = quantity + ?

WHERE ...;

-- Log movement

INSERT INTO inventory_movements (type = 'cancel', quantity = +X, ...);

COMMIT

```

**Why allow cancel:**
- Human errors happen
- Wrong items scanned
- Customer changed mind before leaving

---

### Case 4: Exchange/Return POS Items

**Scenario:** Khách mua rồi về, phát hiện lỗi

**Rule:**
- Không dùng order status workflow (POS không có status)
- **Tạo return request riêng** (như SHIPPING orders)

**Flow:**
```

1. Customer đến quầy với hàng + receipt
2. Staff tạo return request (link to original order)
3. Admin approve
4. Staff confirm items received
5. Restock inventory
6. Refund cash

```

**Reference:** [RETURN_[FLOW.md](http://FLOW.md)](./RETURN_[FLOW.md](http://FLOW.md))

---

## INVENTORY IMPACT

### When Created:
```

inventory.quantity -= order_item.quantity

```

### When Cancelled:
```

inventory.quantity += order_item.quantity

```

### Movements Log:
```

Type 'sale': quantity = negative

Type 'cancel': quantity = positive

```

---

## PERMISSIONS

### Who can create POS orders?
- **Cashier** (staff at branch)
- **Admin**

### Who can cancel POS orders?
- **Admin only**
- Staff cannot cancel (prevent fraud)

### Who can view POS orders?
- Admin: all POS orders
- Cashier: POS orders at their branch
- Customer: their orders (if customer_id not null)

---

## VALIDATION RULES

### Order Level:
- branch_id: required, exists
- items: required, array, min 1 item
- total: required, > 0
- payment_method: must be 'CASH'
- paid_amount: must equal total

### Item Level:
- product_id: required, exists
- quantity: required, > 0
- price: required, >= 0
- Stock: quantity <= available stock

---

## RESPONSE TIMES

**Target performance:**
- Create order: < 500ms
- Includes: validation + DB insert + inventory update

**Why important:**
- Cashier interaction
- Customer waiting
- Peak hours traffic

**Optimization:**
- Index on inventory (branch_id, product_id, variant_id)
- Transaction as short as possible
- Avoid N+1 queries

---

## ERROR HANDLING

### Insufficient Stock
```

{

"error": "insufficient_stock",

"message": "Product 'iPhone 15' only has 3 units available",

"product_id": 10,

"available": 3,

"requested": 5

}

```

### Invalid Product
```

{

"error": "invalid_product",

"message": "Product not found or inactive",

"product_id": 999

}

```

### Transaction Failed
```

{

"error": "transaction_failed",

"message": "Failed to create order. Please try again."

}

```

---

## REPORTING & ANALYTICS

### POS-specific metrics:

**Daily sales:**
```

SELECT

DATE(created_at) as date,

COUNT(*) as order_count,

SUM(total) as revenue

FROM orders

WHERE payment_method = 'CASH'

AND status IS NULL OR status = 'COMPLETED'

AND created_at >= ?

GROUP BY DATE(created_at)

```

**Top selling products (POS):**
```

SELECT

[p.name](http://p.name),

SUM(oi.quantity) as units_sold,

SUM(oi.quantity * oi.price) as revenue

FROM order_items oi

JOIN orders o ON [o.id](http://o.id) = oi.order_id

JOIN products p ON [p.id](http://p.id) = oi.product_id

WHERE o.payment_method = 'CASH'

GROUP BY [p.id](http://p.id)

ORDER BY units_sold DESC

LIMIT 10

```

**Cashier performance:**
```

SELECT

[u.name](http://u.name) as cashier,

COUNT(*) as orders_processed,

SUM([o.total](http://o.total)) as total_sales

FROM orders o

JOIN users u ON [u.id](http://u.id) = o.created_by

WHERE o.payment_method = 'CASH'

AND o.created_at >= ?

GROUP BY [u.id](http://u.id)

```

---

## COMPARISON: POS vs SHIPPING

| Feature | POS Order | SHIPPING Order |
|---------|-----------|----------------|
| Status workflow | ❌ No | ✅ Yes (11 states) |
| Payment method | CASH only | COD, BANK, CARD, etc |
| Paid amount | = total (100%) | Partial OK |
| Inventory deduct | NGAY khi tạo | Khi SHIPPING |
| Shipping fee | 0 | > 0 |
| Delivery | Khách tự mang | Ship đến địa chỉ |
| Timeline | Vài phút | Vài ngày |
| Cancel | Admin only | Admin only |
| Return | Via return request | Via return request |

---

## TESTING CHECKLIST

### Happy Path:
- [ ] Create POS order with 1 item
- [ ] Create POS order with multiple items
- [ ] Inventory deducted correctly
- [ ] paid_amount = total
- [ ] payment_method = 'CASH'
- [ ] Order created successfully

### Edge Cases:
- [ ] Insufficient stock → 422 error
- [ ] Invalid product → 422 error
- [ ] Quantity = 0 → 422 error
- [ ] Negative price → 422 error
- [ ] Walk-in customer (customer_id = null) works
- [ ] Discount > subtotal → 422 error

### Cancel:
- [ ] Admin can cancel POS order
- [ ] Inventory restored correctly
- [ ] Staff cannot cancel → 403

### Concurrent:
- [ ] 2 cashiers create order with same product → both succeed or one fails gracefully
- [ ] Race condition on inventory handled

### Performance:
- [ ] Create order < 500ms
- [ ] Peak load (100 orders/min) handled

---

## INTEGRATION POINTS

### Frontend (Cashier UI):
- Product search/scan
- Cart management
- Price override (if allowed)
- Payment confirmation
- Receipt printing

### Hardware:
- Barcode scanner
- Receipt printer
- Cash drawer
- Display for customer

### Backend:
- Inventory real-time check
- Order creation API
- Receipt generation API

---

## EXAMPLES

### Example 1: Simple POS Order
```

POST /api/orders

{

"customer_id": null,

"branch_id": 1,

"items": [

{

"product_id": 15,

"variant_id": null,

"quantity": 2,

"price": 50000

}

]

}

Response:

{

"order_id": 789,

"order_number": "POS-001789",

"total": 100000,

"paid_amount": 100000,

"created_at": "2024-11-24T14:30:00Z"

}

```

### Example 2: POS Order with Discount
```

POST /api/orders

{

"customer_id": 45,

"branch_id": 1,

"items": [

{

"product_id": 20,

"quantity": 1,

"price": 500000

}

],

"discount": 50000,

"notes": "Khách VIP giảm 10%"

}

Response:

{

"order_id": 790,

"subtotal": 500000,

"discount": 50000,

"total": 450000,

"paid_amount": 450000

}

```

### Example 3: Cancel POS Order
```

POST /api/orders/789/cancel

{

"cancel_reason": "wrong_items",

"cancel_notes": "Cashier scanned wrong product"

}

Response:

{

"success": true,

"order_id": 789,

"status": "cancelled",

"inventory_restored": true

}

```

---

## RELATED DOCUMENTS

- [BUSINESS_[DECISIONS.md](http://DECISIONS.md)](../01_BUSINESS_[DECISIONS.md](http://DECISIONS.md)) - Decisions #1, #12
- [STATE_[MACHINE.md](http://MACHINE.md)](./STATE_[MACHINE.md](http://MACHINE.md)) - Why POS orders excluded
- [SHIPPING_[FLOW.md](http://FLOW.md)](./SHIPPING_[FLOW.md](http://FLOW.md)) - Comparison
- [TASK_05_ORDER_[CREATE.md](http://CREATE.md)](../07_TASKS/TASK_05_ORDER_[CREATE.md](http://CREATE.md)) - Implementation

---

**END OF POS FLOW**
```