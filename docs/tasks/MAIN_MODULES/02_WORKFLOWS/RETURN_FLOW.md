---
title: "Return Orders Workflow"
id: "RETURN-FLOW-01"
module: "Order Workflow"
last_updated: "2025-11-24"
type: "Workflow Document"
tags: ["workflow", "return", "orders", "refund", "inventory", "sales"]
purpose: "Describes the detailed workflow for Return Orders, from customer request to restock and refund."
location: "docs/tasks/MAIN_MODULES/02_WORKFLOWS"
related_to:
  - id: "RETURN-001"
    description: "Related Task for Returns Tables implementation."
  - id: "RETURN-002"
    description: "Related Task for Return Order API implementation."
  - id: "BUSINESS-DECISIONS-01"
    description: "References Business Decisions #23-33."
  - id: "ORDER-WORKFLOW-INDEX"
    description: "Referenced by the main Order Workflow Index."
  - id: "SHIPPING-FLOW-01"
    description: "Implied comparison of workflows."
  - id: "INVOICE-FLOW-01"
    description: "Related to the invoice generation flow."
---

# Return Orders Workflow

**Module:** Order Workflow

**Related Tasks:** RETURN-001, RETURN-002

**Last Updated:** 2025-11-24

---

## 🎯 MỤC ĐÍCH

Document này mô tả chi tiết workflow cho **Return Orders** - đơn trả hàng.

**Đặc điểm:** Customer request → Admin approve → Staff confirm → Restock inventory.

---

## 📊 WORKFLOW OVERVIEW

```
┌──────────────┐
│ Order        │
│ DELIVERED    │
└──────┬───────┘
       │
       ↓
┌──────────────┐
│ Customer     │
│ Request      │ ← POST /api/orders/:id/return
│ Return       │
└──────┬───────┘
       │
       ↓
┌──────────────┐
│ Return       │
│ PENDING      │
└──────┬───────┘
       │
       ↓
┌──────────────┐
│ Admin        │
│ Review       │ ← Approve or Reject?
└──────┬───────┘
       │
   ┌───┴────┐
   │        │
   ↓        ↓
┌─────┐  ┌────────┐
│REJECT│  │APPROVED│
└─────┘  └───┬────┘
             │
             ↓
      ┌──────────────┐
      │ Hàng về kho  │
      │ Staff confirm│
      └──────┬───────┘
             │
             ↓
      ┌──────────────┐
      │ COMPLETED    │ ← ⚡ RESTOCK INVENTORY
      │ Restock +    │
      │ Refund       │
      └──────────────┘
```

**Timeline:** 3-7 ngày (tùy shipping về)

---

## 📋 STATUS DEFINITIONS

### 1. PENDING

**Nghĩa:** Customer đã tạo return request, chờ admin duyệt

**Characteristics:**

- Customer submit form trả hàng
- Chọn items muốn trả + quantity
- Chọn reason + notes
- Admin chưa review

**Inventory:** Không ảnh hưởng

**Next states:** APPROVED, REJECTED

---

### 2. APPROVED

**Nghĩa:** Admin đồng ý cho trả hàng

**Characteristics:**

- Admin đã review và approve
- Đã quyết định refund shipping fee (yes/no)
- Đã chọn refund method (CASH/BANK_TRANSFER)
- Calculate refund_amount
- Chờ hàng về kho

**Inventory:** Chưa restock (hàng chưa về)

**Next states:** COMPLETED

**Calculation:**

```
refund_amount = return_amount + (shipping_fee IF refund_shipping_fee)
```

---

### 3. REJECTED

**Nghĩa:** Admin từ chối trả hàng

**Characteristics:**

- Admin reject với lý do
- TERMINAL STATE
- Customer không được refund

**Inventory:** Không ảnh hưởng

**Next states:** NONE (terminal)

---

### 4. COMPLETED ⚡ CRITICAL

**Nghĩa:** Hàng đã về kho, đã restock

**Characteristics:**

- Staff confirm hàng đã về kho
- **RESTOCK INVENTORY**
- Log inventory_movements
- Prepare refund (execution là step riêng)
- TERMINAL STATE

**Inventory:** ✅ RESTOCK (cộng lại)

**Next states:** NONE (terminal)

---

## 🔄 COMPLETE FLOW

### Step 1: Customer Create Return Request

**Endpoint:** `POST /api/orders/:id/return`

**Input:**

```json
{
  "items": [
    {
      "order_item_id": 123,
      "quantity": 1,
      "condition": "defective"
    }
  ],
  "reason": "defective",
  "reason_detail": "Màn hình bị vỡ"
}
```

**Validation:**

1. Order status >= DELIVERED
2. quantity_returned <= order_item.quantity - SUM(previous returns)
3. Items thuộc order này
4. Customer owns this order (hoặc staff)

**Business Logic:**

```php
// Generate return number
$returnNumber = "TH-{$orderId}-" . ($existingReturnsCount + 1);

// Calculate return amount
$returnAmount = 0;
foreach ($items as $item) {
    $orderItem = OrderItem::find($item['order_item_id']);
    $returnAmount += $orderItem->price * $item['quantity'];
}

// Create return
Insert INTO returns (
    return_number,
    order_id,
    customer_id,
    return_amount,
    reason,
    reason_detail,
    status = 'pending'
);

// Create return items
foreach ($items as $item) {
    Insert INTO return_items (
        return_id,
        order_item_id,
        quantity_returned,
        condition
    );
}
```

**Response:**

```json
{
  "return_id": 456,
  "return_number": "TH-123-1",
  "status": "pending",
  "return_amount": 500000
}
```

---

### Step 2: Admin Approve/Reject

**Endpoint:** `POST /api/returns/:id/approve`

**Input:**

```json
{
  "decision": "approve",
  "refund_shipping_fee": true,
  "refund_method": "cash",
  "notes": "Đồng ý trả hàng"
}
```

**Business Logic:**

```php
if ($decision === 'approve') {
    // Calculate refund amount
    $refundAmount = $return->return_amount;
    if ($refundShippingFee) {
        $refundAmount += $order->shipping_fee;
    }
    
    // Update return
    Update returns SET
        status = 'approved',
        refund_shipping_fee = ?,
        refund_method = ?,
        refund_amount = ?,
        approved_by = ?,
        approved_at = NOW(),
        notes = ?
    WHERE id = ?;
    
} else {
    // Reject
    Update returns SET
        status = 'rejected',
        rejected_by = ?,
        rejected_at = NOW(),
        notes = ?
    WHERE id = ?;
}
```

**Response (Approved):**

```json
{
  "return_id": 456,
  "status": "approved",
  "refund_amount": 525000,
  "refund_method": "cash"
}
```

**Response (Rejected):**

```json
{
  "return_id": 456,
  "status": "rejected",
  "notes": "Quá hạn trả hàng"
}
```

---

### Step 3: Staff Confirm Hàng Về Kho

**Endpoint:** `POST /api/returns/:id/confirm`

**Business Logic:**

```php
// 1. Update return status
Update returns SET
    status = 'completed',
    completed_at = NOW()
WHERE id = ?;

// 2. Restock inventory
foreach ($returnItems as $item) {
    $orderItem = OrderItem::find($item->order_item_id);
    
    Update inventory SET
        quantity = quantity + $item->quantity_returned
    WHERE product_id = $orderItem->product_id
      AND variant_id = $orderItem->variant_id
      AND branch_id = $order->branch_id;
    
    // 3. Log movement
    Insert INTO inventory_movements (
        type = 'return',
        quantity = +$item->quantity_returned,
        reference_type = 'return',
        reference_id = $return->id,
        notes = "Return: {$return->return_number}"
    );
}

// 4. Update return_items condition
Update return_items SET
    condition = ?  // 'new', 'used', 'damaged'
WHERE return_id = ?;
```

**Response:**

```json
{
  "return_id": 456,
  "status": "completed",
  "inventory_restocked": true,
  "refund_ready": true
}
```

---

## 📊 BUSINESS RULES

### 1. Return Window

- **Không giới hạn hard** (không bắt buộc 7 ngày, 15 ngày)
- Admin quyết định từng case
- Flexible policy

### 2. Partial Return

- Được phép trả **từng item**
- Không bắt buộc trả toàn bộ order

**Example:**

```
Order:
- 5x iPhone 15 (10 triệu/cái)
- 3x AirPods (5 triệu/cái)

Return:
- 2x iPhone 15 only

Calculation:
- return_amount = 2 × 10tr = 20tr
- shipping_fee = 30k
- refund_amount = 20tr + 30k = 20,030,000

Restock:
- iPhone 15: +2
- AirPods: không đổi
```

### 3. Return Reasons

```sql
returns.reason ENUM(
  'defective',      -- Hàng lỗi
  'wrong_item',     -- Giao nhầm
  'not_satisfied',  -- Không ưng ý
  'other'
)
returns.reason_detail TEXT
```

**Validation:**

- reason: required
- reason_detail: required if reason = 'other'

### 4. Shipping Fee Refund

**Admin quyết định khi approve:**

- `refund_shipping_fee = true/false`

**Policy:**

- **Lỗi shop:** refund
- **Khách đổi ý:** không refund
- **Negotiable:** tùy case

### 5. Restock LUÔN LUÔN

**Rule:**

- Khi COMPLETED: **LUÔN** cộng lại inventory
- **KHÔNG** check condition (new/used/damaged)
- Condition chỉ dùng cho reporting

**Why:**

- Simplicity
- Inventory accuracy > condition tracking
- Admin có thể adjust manual sau

### 6. Refund Methods

**Supported:**

- **CASH:** Hoàn tiền mặt
- **BANK_TRANSFER:** Chuyển khoản

**NOT supported (Phase 1):**

- STORE_CREDIT
- Voucher

### 7. Return Number Format

**Format:** `TH-{order_id}-{auto_increment}`

**Examples:**

- TH-123-1 (order 123, return đầu tiên)
- TH-123-2 (order 123, return thứ 2)
- TH-456-1 (order 456, return đầu tiên)

**Counter:** Per order

---

## 🔐 PERMISSIONS

| Action | Admin | Staff | Customer |
| --- | --- | --- | --- |
| Create Return | ✅ | ✅ | ✅ (own orders) |
| Approve Return | ✅ | ❌ | ❌ |
| Reject Return | ✅ | ❌ | ❌ |
| Confirm Return | ✅ | ✅ (warehouse) | ❌ |
| View Returns | ✅ | ✅ | ✅ (own only) |

---

## 📝 DATABASE SCHEMA

### returns table

```sql
CREATE TABLE returns (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  return_number VARCHAR(50) UNIQUE NOT NULL,
  order_id BIGINT NOT NULL,
  customer_id BIGINT NOT NULL,
  return_amount DECIMAL(10,2) NOT NULL,
  refund_shipping_fee BOOLEAN DEFAULT FALSE,
  refund_amount DECIMAL(10,2),
  refund_method ENUM('cash', 'bank_transfer'),
  reason ENUM('defective', 'wrong_item', 'not_satisfied', 'other'),
  reason_detail TEXT,
  status ENUM('pending', 'approved', 'rejected', 'completed'),
  approved_by BIGINT,
  approved_at TIMESTAMP,
  rejected_by BIGINT,
  rejected_at TIMESTAMP,
  completed_at TIMESTAMP,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (order_id) REFERENCES orders(id),
  INDEX (order_id),
  INDEX (customer_id),
  INDEX (status)
);
```

---

### return_items table

```sql
CREATE TABLE return_items (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  return_id BIGINT NOT NULL,
  order_item_id BIGINT NOT NULL,
  quantity_returned INT NOT NULL,
  condition ENUM('new', 'used', 'damaged'),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (return_id) REFERENCES returns(id) ON DELETE CASCADE,
  FOREIGN KEY (order_item_id) REFERENCES order_items(id),
  INDEX (return_id)
);
```

---

## 🚨 ERROR HANDLING

### 1. Return from Invalid Order Status

```json
Order status: DRAFT
Request: Create return

Response: 422 Unprocessable Entity
{
  "error": "invalid_order_status",
  "message": "Cannot return order with status 'draft'",
  "allowed_statuses": ["delivered", "completed"]
}
```

---

### 2. Exceed Return Quantity

```json
Order item quantity: 3
Previous returns: 1
Request return: 3

Response: 422
{
  "error": "exceed_return_quantity",
  "order_item_id": 123,
  "available_for_return": 2,
  "requested": 3
}
```

---

### 3. Already Approved/Rejected

```json
Return status: approved
Request: Approve again

Response: 400
{
  "error": "invalid_status",
  "message": "Return already approved"
}
```

---

## 🎬 COMPLETE SCENARIOS

### Scenario 1: Normal Return (Happy Path)

```
1. Customer nhận hàng, phát hiện lỗi
   → Order status = DELIVERED

2. Customer tạo return request
   → POST /api/orders/123/return
   → Return status = PENDING

3. Admin review và approve
   → POST /api/returns/456/approve
   → {refund_shipping_fee: true, refund_method: "cash"}
   → Return status = APPROVED
   → Calculate refund_amount = 500k + 25k = 525k

4. Customer gửi hàng về shop
   → (2-3 ngày)

5. Staff nhận hàng, confirm
   → POST /api/returns/456/confirm
   → Return status = COMPLETED
   → ⚡ System restock inventory
   → Log inventory_movements

6. Staff/Admin execute refund
   → (Manual process hoặc API riêng)

Timeline: 3-7 ngày
```

---

### Scenario 2: Partial Return

```
Order items:
- 5x iPhone 15 (10 triệu/cái)
- 3x AirPods (5 triệu/cái)

Return request:
- 2x iPhone 15 only

Calculation:
- return_amount = 2 × 10tr = 20tr
- shipping_fee = 30k
- refund_amount = 20tr + 30k = 20,030,000

Restock:
- iPhone 15: +2
- AirPods: không đổi
```

---

### Scenario 3: Rejected Return

```
1. Customer tạo return sau 60 ngày
   → Return status = PENDING

2. Admin review
   → Quá hạn policy (30 ngày)
   → Reject

3. Admin reject
   → POST /api/returns/456/approve
   → {decision: "reject", notes: "Quá hạn 30 ngày"}
   → Return status = REJECTED
   → TERMINAL STATE

4. Không restock, không refund
```

---

## 🔗 RELATED DOCUMENTS

- **Business Decisions:** #23-33
- **API Implementation:** Task RETURN-001, RETURN-002
- **Shipping Flow:** SHIPPING_FLOW
- **Invoice Flow:** INVOICE_FLOW