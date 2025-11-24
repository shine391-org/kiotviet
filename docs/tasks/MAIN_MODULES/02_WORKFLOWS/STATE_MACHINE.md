---
title: "STATE MACHINE - Order Status Transitions"
id: "STATE-MACHINE-01"
last_updated: "2025-11-24"
version: "1.0"
type: "Workflow Document"
tags: ["workflow", "state-machine", "order-status", "inventory", "transitions", "business-rules"]
purpose: "Defines the State Machine for Order Status transitions, applicable only to SHIPPING orders, detailing states, rules, and inventory impact."
location: "docs/tasks/MAIN_MODULES/02_WORKFLOWS"
related_to:
  - id: "BUSINESS-DECISIONS-01"
    description: "References Business Decisions #1-7 related to order status and inventory."
  - id: "ORDER-WORKFLOW-INDEX"
    description: "Referenced by the main Order Workflow Index."
  - id: "POS-FLOW-01"
    description: "Contrasted with POS orders that have no status workflow."
  - id: "SHIPPING-FLOW-01"
    description: "Detailed shipping workflow where state machine is applied."
  - id: "TASK-06-STATUS-MANAGEMENT-01"
    description: "Implementation guide for order status management."
---

# STATE MACHINE - Order Status Transitions

**Last Updated:** 2025-11-24  
**Version:** 1.0  
**Related:** [BUSINESS_[DECISIONS.md](http://DECISIONS.md)](../01_BUSINESS_[DECISIONS.md](http://DECISIONS.md)#decision-2)

---

## MỤC ĐÍNH

Document này định nghĩa **State Machine** cho order status transitions.

**Applies to:** SHIPPING orders only (POS orders không có status workflow)

---

## OVERVIEW

```

┌─────────┐

│  DRAFT  │

└────┬────┘

│

↓

┌───────────┐

│ CONFIRMED │

└─────┬─────┘

│

↓

┌────────────┐

│ PROCESSING │

└──────┬─────┘

│

↓

┌──────────┐

│ SHIPPING │◄─── [Trừ kho tại đây]

└────┬─────┘

│

┌───────┼────────┐

│       │        │

↓       ↓        ↓

┌──────┐ ┌──────┐ ┌────────┐

│FAILED│ │DELIV.│ │CANCEL  │

└───┬──┘ └──┬───┘ └────────┘

│       │

↓       ↓

┌──────┐ ┌────────┐

│RETURN│ │COMPLETE│

└───┬──┘ └────────┘

│

↓

┌─────────────┐

│RETURN_CONF. │◄─── [Rollback kho]

└─────────────┘

```

---

## STATES DEFINITION

### DRAFT
**Nghĩa:** Order mới tạo, chưa confirm

**Characteristics:**
- Order vừa được tạo
- Chưa lock inventory
- Có thể edit items, quantities, prices
- Customer có thể cancel tự do

**Inventory:** Chưa trừ

**Next states:** CONFIRMED, CANCELLED

---

### CONFIRMED
**Nghĩa:** Order đã confirm, chuẩn bị xử lý

**Characteristics:**
- Shop đã accept order
- Không thể edit items nữa (chỉ admin)
- Bắt đầu prepare

**Inventory:** Chưa trừ (chưa chắc đủ hàng)

**Next states:** PROCESSING, CANCELLED

---

### PROCESSING
**Nghĩa:** Đang xử lý (kiểm tra hàng, đóng gói)

**Characteristics:**
- Staff đang kiểm tra stock
- Đang đóng gói
- Chuẩn bị giao shipper

**Inventory:** Chưa trừ (có thể discover không đủ hàng)

**Next states:** SHIPPING, CANCELLED

---

### SHIPPING
**Nghĩa:** Đã giao cho shipper, đang vận chuyển

**Characteristics:**
- Hàng đã giao shipper (GHN/GHTK/manual)
- **[CRITICAL]** Trừ kho tại đây
- Đang trên đường giao

**Inventory:** ✅ ĐÃ TRỪ

**Next states:** DELIVERED, FAILED, CANCELLED

**Hooks:**
- BEFORE → SHIPPING: Check stock availability
- AFTER → SHIPPING: Deduct inventory, log movements

---

### DELIVERED
**Nghĩa:** Đã giao hàng thành công

**Characteristics:**
- Khách đã nhận hàng
- Shipper confirm delivered
- COD collected (nếu COD order)

**Inventory:** Đã trừ (từ SHIPPING)

**Next states:** COMPLETED, CANCELLED (rare)

**Triggers:**
- Webhook từ GHN/GHTK: status=delivered
- Manual: staff confirm delivery

---

### COMPLETED
**Nghĩa:** Hoàn thành toàn bộ

**Characteristics:**
- Order đã hoàn tất
- Payment settled (hoặc debt tracked)
- Có thể tạo invoice (nếu có tax_code)
- **TERMINAL STATE** - không transition nữa

**Inventory:** Đã trừ (từ SHIPPING)

**Next states:** NONE (terminal)

**Note:** Nếu cần return, tạo return request riêng

---

### FAILED
**Nghĩa:** Giao hàng thất bại

**Characteristics:**
- Shipper không giao được (khách từ chối, sai địa chỉ, không liên lạc được)
- Hàng đang về shop
- Chưa rollback kho (hàng chưa về)

**Inventory:** Vẫn trừ (hàng đang trên đường về)

**Next states:** RETURN

**Triggers:**
- Webhook từ GHN/GHTK: status=failed
- Manual: staff confirm failed

---

### RETURN
**Nghĩa:** Hàng đang về kho (sau FAILED)

**Characteristics:**
- Hàng đang trên đường về shop
- Chờ staff confirm nhận hàng
- Chưa rollback kho

**Inventory:** Vẫn trừ (hàng chưa về kho)

**Next states:** RETURN_CONFIRMED

---

### RETURN_CONFIRMED
**Nghĩa:** Hàng đã về kho (sau FAILED)

**Characteristics:**
- Staff confirm hàng đã về kho
- **[CRITICAL]** Rollback inventory tại đây
- **TERMINAL STATE**

**Inventory:** ✅ ROLLBACK (cộng lại)

**Next states:** NONE (terminal)

**Hooks:**
- AFTER → RETURN_CONFIRMED: Restock inventory, log movements

---

### CANCELLED
**Nghĩa:** Order bị hủy

**Characteristics:**
- Admin cancel order
- Capture cancel_reason và cancel_notes
- Rollback inventory nếu đã trừ
- **TERMINAL STATE**

**Inventory:** 
- Nếu status < SHIPPING khi cancel: không cần rollback (chưa trừ)
- Nếu status >= SHIPPING khi cancel: ✅ ROLLBACK

**Next states:** NONE (terminal)

**Hooks:**
- AFTER → CANCELLED: Conditional rollback inventory

---

## TRANSITION RULES

### Valid Transitions Matrix

| From → To | DRAFT | CONF | PROC | SHIP | DELIV | COMP | FAIL | RET | RET_CONF | CANC |
|-----------|-------|------|------|------|-------|------|------|-----|----------|------|
| **null** (create) | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **DRAFT** | - | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| **CONFIRMED** | ❌ | - | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| **PROCESSING** | ❌ | ❌ | - | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| **SHIPPING** | ❌ | ❌ | ❌ | - | ✅ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **DELIVERED** | ❌ | ❌ | ❌ | ❌ | - | ✅ | ❌ | ❌ | ❌ | ✅ |
| **COMPLETED** | ❌ | ❌ | ❌ | ❌ | ❌ | - | ❌ | ❌ | ❌ | ❌ |
| **FAILED** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | - | ✅ | ❌ | ❌ |
| **RETURN** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | - | ✅ | ❌ |
| **RETURN_CONF** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | - | ❌ |
| **CANCELLED** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | - |

---

## IMPLEMENTATION RULES

### 1. Validation
```

BEFORE updating status:

- Check transition is valid (see matrix above)
- If invalid → throw InvalidArgumentException (400)

```

### 2. Transaction Boundary
```

BEGIN TRANSACTION

- Update orders.status
- Execute hooks (inventory, logs)
- Insert order_status_logs

COMMIT

```

### 3. Hooks Timing

**BEFORE transition:**
- Validate business rules
- Check inventory availability (for SHIPPING)

**AFTER transition:**
- Update inventory (SHIPPING, RETURN_CONFIRMED, CANCELLED)
- Log to order_status_logs (always)
- Log to inventory_movements (if inventory changed)

---

## INVENTORY IMPACT

### States that DEDUCT inventory:
- **SHIPPING** ← Trừ kho

### States that RESTORE inventory:
- **RETURN_CONFIRMED** ← Cộng kho (sau FAILED)
- **CANCELLED** ← Cộng kho (nếu đã trừ, i.e. status >= SHIPPING)

### States with NO inventory change:
- DRAFT, CONFIRMED, PROCESSING, DELIVERED, COMPLETED, FAILED, RETURN

---

## SPECIAL CASES

### Case 1: Cancel từ DRAFT/CONFIRMED/PROCESSING
```

Status: DRAFT → CANCELLED

Inventory: KHÔNG rollback (chưa trừ)

Logic: Chỉ update status, log audit

```

### Case 2: Cancel từ SHIPPING/DELIVERED
```

Status: SHIPPING → CANCELLED

Inventory: PHẢI rollback (đã trừ)

Logic: Update status + restock + log movements

```

### Case 3: FAILED → RETURN → RETURN_CONFIRMED
```

FAILED: Không rollback (hàng chưa về)

RETURN: Không rollback (hàng đang về)

RETURN_CONFIRMED: ROLLBACK (hàng đã về kho)

```

---

## TERMINAL STATES

Các states KHÔNG thể transition nữa:

1. **COMPLETED**
   - Order hoàn thành thành công
   - Nếu cần return: tạo return request riêng (không thay đổi order status)

2. **RETURN_CONFIRMED**
   - Hàng đã về kho sau FAILED
   - Kết thúc

3. **CANCELLED**
   - Order đã bị hủy
   - Kết thúc

---

## PERMISSIONS

### Who can transition?

| Transition | Admin | Staff | System (Webhook) | Customer |
|------------|-------|-------|------------------|----------|
| DRAFT → CONFIRMED | ✅ | ✅ | ❌ | ❌ |
| CONFIRMED → PROCESSING | ✅ | ✅ | ❌ | ❌ |
| PROCESSING → SHIPPING | ✅ | ✅ | ❌ | ❌ |
| SHIPPING → DELIVERED | ✅ | ✅ | ✅ (GHN/GHTK) | ❌ |
| SHIPPING → FAILED | ✅ | ✅ | ✅ (GHN/GHTK) | ❌ |
| DELIVERED → COMPLETED | ✅ | ✅ | ❌ | ❌ |
| FAILED → RETURN | ✅ | ✅ | ❌ | ❌ |
| RETURN → RETURN_CONFIRMED | ✅ | ✅ | ❌ | ❌ |
| **Any → CANCELLED** | ✅ | ❌ | ❌ | ❌ |

**Note:** Phase 1 hard-code permissions, Phase 2 flexible RBAC

---

## AUDIT LOGGING

**Every status change MUST log to:**
```

order_status_logs (

order_id,

old_status,

new_status,

user_id,      -- Who made the change (null if webhook)

notes,        -- Optional explanation

created_at

)

```

**Purpose:**
- Track order history
- Debug issues
- Customer service reference
- Audit compliance

---

## EXAMPLES

### Example 1: Normal SHIPPING flow
```

1. Create order → DRAFT
2. Admin confirm → CONFIRMED
3. Staff prepare → PROCESSING
4. Staff ship → SHIPPING [inventory deducted]
5. GHN webhook → DELIVERED
6. Admin close → COMPLETED

```

### Example 2: Failed delivery
```

1. ... → SHIPPING [inventory deducted]
2. GHN webhook (failed) → FAILED
3. Staff mark return → RETURN
4. Staff confirm received → RETURN_CONFIRMED [inventory restored]

```

### Example 3: Cancel after shipping
```

1. ... → SHIPPING [inventory deducted]
2. Admin cancel → CANCELLED [inventory restored]

```

### Example 4: Cancel before shipping
```

1. Create → DRAFT
2. Confirm → CONFIRMED
3. Admin cancel → CANCELLED [no inventory impact]

```

---

## ERROR HANDLING

### Invalid Transition
```

Status: DRAFT

Request: transition to DELIVERED

Result: 400 Bad Request

Message: "Invalid status transition from 'draft' to 'delivered'"

```

### Insufficient Stock (SHIPPING)
```

Status: PROCESSING

Request: transition to SHIPPING

Stock: Not enough

Result: 422 Unprocessable Entity

Message: "Insufficient stock for product X"

```

### Permission Denied
```

User: Staff

Request: transition to CANCELLED

Result: 403 Forbidden

Message: "Only admin can cancel orders"

```

### Order Not Found
```

Order ID: 999999

Result: 404 Not Found

Message: "Order not found"

```

---

## CONCURRENCY HANDLING

### Problem:
2 requests cùng lúc update cùng 1 order

### Solutions:

**Option A: Optimistic Locking**
```

orders.version INT

UPDATE orders

SET status = ?, version = version + 1

WHERE id = ? AND version = ?

If affected_rows = 0 → Conflict, retry

```

**Option B: Pessimistic Locking**
```

BEGIN TRANSACTION

SELECT * FROM orders WHERE id = ? FOR UPDATE

-- Check transition

UPDATE orders SET status = ?

COMMIT

```

**Recommended:** Option B (simpler, safer for critical operations)

---

## TESTING CHECKLIST

### Valid Transitions
- [ ] null → DRAFT works
- [ ] DRAFT → CONFIRMED works
- [ ] CONFIRMED → PROCESSING works
- [ ] PROCESSING → SHIPPING works (inventory deducted)
- [ ] SHIPPING → DELIVERED works
- [ ] DELIVERED → COMPLETED works
- [ ] SHIPPING → FAILED works
- [ ] FAILED → RETURN works
- [ ] RETURN → RETURN_CONFIRMED works (inventory restored)

### Invalid Transitions
- [ ] DRAFT → DELIVERED fails (400)
- [ ] COMPLETED → any fails (400)
- [ ] PROCESSING → COMPLETED fails (400)

### Inventory
- [ ] SHIPPING deducts correctly
- [ ] RETURN_CONFIRMED restores correctly
- [ ] CANCELLED restores correctly (if >= SHIPPING)
- [ ] CANCELLED doesn't change inventory (if < SHIPPING)

### Permissions
- [ ] Staff cannot cancel
- [ ] Admin can cancel any status (except COMPLETED)
- [ ] Webhook can update DELIVERED/FAILED

### Audit
- [ ] Every transition logs to order_status_logs
- [ ] user_id captured correctly
- [ ] Inventory changes log to inventory_movements

---

## RELATED DOCUMENTS

- [BUSINESS_[DECISIONS.md](http://BUSINESS_[DECISIONS.md](http://BUSINESS_DECISIONS.md))] - Decisions #1-7
- [SHIPPING_[FLOW.md](http://SHIPPING_[FLOW.md](http://SHIPPING_FLOW.md))] - Detailed SHIPPING workflow
- [POS_[FLOW.md](http://POS_[FLOW.md](http://POS_FLOW.md))] - POS orders (no status)
- [TASK_06_STATUS_[MANAGEMENT.md](http://MANAGEMENT.md)] - Implementation guide

---

**END OF STATE MACHINE**
```