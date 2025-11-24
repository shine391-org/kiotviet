```markdown
# 🚚 SHIPPING Orders Workflow

**Module:** Order Workflow  
**Related Task:** ORD-003  
**Last Updated:** 2025-11-24

---

## 🎯 MỤC ĐÍCH

Document này mô tả chi tiết workflow cho **SHIPPING Orders** - đơn hàng có giao hàng.

**Đặc điểm:** Có status workflow, trừ kho khi SHIPPING, hỗ trợ COD.

---

## 📊 WORKFLOW OVERVIEW

```

┌─────────┐

│  DRAFT  │ ← Tạo đơn

└────┬────┘

│

↓

┌──────────┐

│CONFIRMED │ ← Shop accept

└────┬─────┘

│

↓

┌───────────┐

│PROCESSING │ ← Đang chuẩn bị

└─────┬─────┘

│

↓

┌──────────┐

│ SHIPPING │ ← ⚡ TRỪ KHO TẠI ĐÂY

└────┬─────┘

│

├─────────┬─────────┐

↓         ↓         ↓

┌─────────┐ ┌────────┐ ┌─────────┐

│DELIVERED│ │ FAILED │ │CANCELLED│

└────┬────┘ └───┬────┘ └─────────┘

│          │

↓          ↓

┌─────────┐ ┌────────┐

│COMPLETED│ │ RETURN │

└─────────┘ └───┬────┘

│

↓

┌─────────────┐

│RETURN_CONF. │ ← ⚡ ROLLBACK KHO

└─────────────┘

```

---

## 📋 STATUS DEFINITIONS

### **1. DRAFT**
**Nghĩa:** Đơn mới tạo, chưa confirm

**Characteristics:**
- Order vừa được customer đặt (online) hoặc staff tạo
- Chưa lock inventory
- Có thể edit items, quantities, prices
- Customer có thể cancel tự do

**Inventory:** ❌ Chưa trừ

**Next states:** CONFIRMED, CANCELLED

**Example:**
```

Customer đặt hàng online → tạo DRAFT order

Staff có thể edit trước khi confirm

```

---

### **2. CONFIRMED**
**Nghĩa:** Shop đã accept đơn

**Characteristics:**
- Shop đã xác nhận nhận đơn
- Không thể edit items nữa (chỉ admin)
- Bắt đầu prepare

**Inventory:** ❌ Chưa trừ

**Next states:** PROCESSING, CANCELLED

**Example:**
```

Staff review đơn → click "Xác nhận"

System gửi notification cho customer

```

---

### **3. PROCESSING**
**Nghĩa:** Đang chuẩn bị hàng

**Characteristics:**
- Staff đang kiểm tra stock
- Đang đóng gói
- Chuẩn bị giao shipper

**Inventory:** ❌ Chưa trừ

**Next states:** SHIPPING, CANCELLED

**Why chưa trừ?**
- Có thể phát hiện không đủ hàng
- Có thể phát hiện hàng lỗi
- Chưa chắc ship được

---

### **4. SHIPPING** ⚡ CRITICAL

**Nghĩa:** Đã giao shipper, đang vận chuyển

**Characteristics:**
- Hàng đã giao cho shipper (GHN/GHTK/manual)
- **TRỪ KHO TẠI ĐÂY**
- Đang trên đường giao
- Track được shipping status

**Inventory:** ✅ ĐÃ TRỪ

**Next states:** DELIVERED, FAILED, CANCELLED

**Hooks:**

**BEFORE transition to SHIPPING:**
```

// Validate stock availability

foreach ($orderItems as $item) {

if (inventory.quantity < item.quantity) {

throw InsufficientStockException;

}

}

```

**AFTER transitioned to SHIPPING:**
```

// Deduct inventory

foreach ($orderItems as $item) {

UPDATE inventory

SET quantity = quantity - item.quantity

WHERE product_id = item.product_id

AND branch_id = order.branch_id;

// Log movement

INSERT INTO inventory_movements (

type = 'sale',

quantity = -item.quantity,

reference_type = 'order',

reference_id = [order.id](http://order.id)

);

}

```

**Example:**
```

GHN nhận hàng → scan barcode → webhook update status = SHIPPING

System tự động trừ kho

```

---

### **5. DELIVERED**
**Nghĩa:** Đã giao hàng thành công

**Characteristics:**
- Khách đã nhận hàng
- Shipper confirm delivered
- COD collected (nếu COD order)

**Inventory:** Đã trừ (từ SHIPPING)

**Next states:** COMPLETED

**Triggers:**
- **Webhook từ GHN/GHTK:** `status=delivered`
- **Manual:** Staff confirm delivery (ship ngoài)

**Example API:**
```

GHN webhook:

POST /api/webhooks/shipping/ghn

{

"order_code": "ORD-123",

"status": "delivered",

"cod_collected": true

}

```

---

### **6. COMPLETED**
**Nghĩa:** Hoàn thành toàn bộ

**Characteristics:**
- Order đã hoàn tất
- Payment settled (hoặc debt tracked)
- Có thể tạo invoice (nếu có tax_code)
- **TERMINAL STATE** - không transition nữa

**Inventory:** Đã trừ (từ SHIPPING)

**Next states:** NONE (terminal)

**Note:** Nếu cần return, tạo return request riêng (không thay đổi order status)

---

### **7. FAILED**
**Nghĩa:** Giao hàng thất bại

**Characteristics:**
- Shipper không giao được (khách từ chối, sai địa chỉ, không liên lạc được)
- Hàng đang về shop
- Chưa rollback kho (hàng chưa về)

**Inventory:** Vẫn trừ (hàng đang trên đường về)

**Next states:** RETURN

**Triggers:**
- Webhook từ GHN/GHTK: `status=failed`
- Manual: Staff confirm failed

**Example:**
```

Shipper gọi khách 3 lần không nghe máy

→ Hủy giao hàng

→ Webhook update status = FAILED

```

---

### **8. RETURN**
**Nghĩa:** Hàng đang về kho (sau FAILED)

**Characteristics:**
- Hàng đang trên đường về shop
- Chờ staff confirm nhận hàng
- Chưa rollback kho

**Inventory:** Vẫn trừ (hàng chưa về kho)

**Next states:** RETURN_CONFIRMED

**Manual transition:**
```

Staff: "Hàng đã về kho chưa?"

→ Chưa → giữ RETURN

→ Rồi → click "Confirm về kho" → RETURN_CONFIRMED

```

---

### **9. RETURN_CONFIRMED** ⚡ CRITICAL

**Nghĩa:** Hàng đã về kho (sau FAILED)

**Characteristics:**
- Staff confirm hàng đã về kho
- **ROLLBACK INVENTORY tại đây**
- **TERMINAL STATE**

**Inventory:** ✅ ROLLBACK (cộng lại)

**Next states:** NONE (terminal)

**Hooks:**

**AFTER transitioned to RETURN_CONFIRMED:**
```

// Restock inventory

foreach ($orderItems as $item) {

UPDATE inventory

SET quantity = quantity + item.quantity

WHERE product_id = item.product_id

AND branch_id = order.branch_id;

// Log movement

INSERT INTO inventory_movements (

type = 'return',

quantity = +item.quantity,

reference_type = 'order',

reference_id = [order.id](http://order.id)

);

}

```

---

### **10. CANCELLED**
**Nghĩa:** Đơn bị hủy

**Characteristics:**
- Admin cancel order
- Capture cancel_reason và cancel_notes
- Rollback inventory nếu đã trừ
- **TERMINAL STATE**

**Inventory:**
- Nếu status < SHIPPING: ❌ Không rollback (chưa trừ)
- Nếu status >= SHIPPING: ✅ ROLLBACK (đã trừ)

**Next states:** NONE (terminal)

**Permission:** Chỉ Admin

**Hooks:**

**AFTER transitioned to CANCELLED:**
```

// Conditional rollback

if (old_status >= 'SHIPPING') {

// Restock inventory

foreach ($orderItems as $item) {

UPDATE inventory

SET quantity = quantity + item.quantity;

// Log movement

INSERT INTO inventory_movements (

type = 'cancel',

quantity = +item.quantity

);

}

}

```

---

## 🔄 TRANSITION RULES

### **Valid Transitions Matrix**

| From → To | CONF | PROC | SHIP | DELIV | COMP | FAIL | RET | RET_C | CANC |
|-----------|------|------|------|-------|------|------|-----|-------|------|
| **DRAFT** | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| **CONFIRMED** | - | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| **PROCESSING** | ❌ | - | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| **SHIPPING** | ❌ | ❌ | - | ✅ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **DELIVERED** | ❌ | ❌ | ❌ | - | ✅ | ❌ | ❌ | ❌ | ✅ |
| **COMPLETED** | ❌ | ❌ | ❌ | ❌ | - | ❌ | ❌ | ❌ | ❌ |
| **FAILED** | ❌ | ❌ | ❌ | ❌ | ❌ | - | ✅ | ❌ | ❌ |
| **RETURN** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | - | ✅ | ❌ |
| **RETURN_CONF** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | - | ❌ |
| **CANCELLED** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | - |

---

## 🎬 COMPLETE SCENARIOS

### **Scenario 1: Normal Flow (Happy Path)**

```

1. Customer đặt hàng online
    
    → Tạo order status = DRAFT
    
2. Staff review và xác nhận
    
    → Update status = CONFIRMED
    
    → Send notification
    
3. Staff chuẩn bị hàng
    
    → Update status = PROCESSING
    
    → Pick items from warehouse
    
4. Staff giao cho GHN
    
    → Update status = SHIPPING
    
    → ⚡ System trừ kho
    
    → Log inventory_movements
    
5. GHN giao hàng thành công
    
    → Webhook: status = DELIVERED
    
    → Update cod_collected = true
    
6. Admin close order
    
    → Update status = COMPLETED
    

Timeline: 2-5 ngày

```

---

### **Scenario 2: Failed Delivery**

```

1-4. Giống Scenario 1 đến SHIPPING

→ Inventory đã trừ

1. GHN giao thất bại (khách không nghe máy)
    
    → Webhook: status = FAILED
    
    → Inventory vẫn trừ (hàng chưa về)
    
2. Staff nhận hàng từ GHN
    
    → Manual update: status = RETURN
    
    → Inventory vẫn trừ
    
3. Staff confirm hàng đã về kho
    
    → Update status = RETURN_CONFIRMED
    
    → ⚡ System rollback inventory
    
    → Log inventory_movements (type = 'return')
    

Timeline: 3-7 ngày

```

---

### **Scenario 3: Cancel After Shipping**

```

1-4. Giống Scenario 1 đến SHIPPING

→ Inventory đã trừ

1. Customer gọi yêu cầu hủy đơn
    
    → Admin đồng ý
    
2. Admin cancel order
    
    → POST /api/orders/123/cancel
    
    → {
    
    "cancel_reason": "customer_request",
    
    "cancel_notes": "Khách mua nhầm"
    
    }
    
    → Update status = CANCELLED
    
    → ⚡ System rollback inventory (vì đã SHIPPING)
    
    → Log inventory_movements (type = 'cancel')
    

Note: Phải thu hồi hàng từ shipper trước

```

---

### **Scenario 4: Cancel Before Shipping**

```

1. Create order → DRAFT
2. Admin confirm → CONFIRMED
3. Customer đổi ý ngay
4. Admin cancel
    
    → Update status = CANCELLED
    
    → ❌ KHÔNG rollback inventory (chưa trừ)
    
    → Just log cancel reason
    

Timeline: < 1 ngày

```

---

## 🔐 PERMISSIONS

| Action | Admin | Staff | Webhook | Customer |
|--------|-------|-------|---------|----------|
| DRAFT → CONFIRMED | ✅ | ✅ | ❌ | ❌ |
| CONFIRMED → PROCESSING | ✅ | ✅ | ❌ | ❌ |
| PROCESSING → SHIPPING | ✅ | ✅ | ❌ | ❌ |
| SHIPPING → DELIVERED | ✅ | ✅ | ✅ | ❌ |
| SHIPPING → FAILED | ✅ | ✅ | ✅ | ❌ |
| DELIVERED → COMPLETED | ✅ | ✅ | ❌ | ❌ |
| FAILED → RETURN | ✅ | ✅ | ❌ | ❌ |
| RETURN → RETURN_CONFIRMED | ✅ | ✅ | ❌ | ❌ |
| **Any → CANCELLED** | ✅ | ❌ | ❌ | ❌ |

---

## 📝 DATABASE TRACKING

### **1. order_status_logs**

**Purpose:** Audit trail - track mọi status change

```

CREATE TABLE order_status_logs (

id BIGINT PRIMARY KEY AUTO_INCREMENT,

order_id BIGINT NOT NULL,

old_status VARCHAR(50),

new_status VARCHAR(50),

user_id BIGINT,  -- null nếu webhook

notes TEXT,

created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

INDEX (order_id, created_at)

);

```

**Insert sau MỖI status update:**
```

INSERT INTO order_status_logs (

order_id,

old_status,

new_status,

user_id,

notes

) VALUES (

123,

'SHIPPING',

'DELIVERED',

NULL,  -- webhook

'GHN webhook: delivered'

);

```

---

### **2. inventory_movements**

**Purpose:** Track mọi giao dịch inventory

```

CREATE TABLE inventory_movements (

id BIGINT PRIMARY KEY AUTO_INCREMENT,

branch_id BIGINT NOT NULL,

product_id BIGINT NOT NULL,

variant_id BIGINT,

type ENUM('sale', 'return', 'cancel', 'adjustment'),

quantity INT,  -- âm = trừ, dương = cộng

reference_type VARCHAR(50),  -- 'order'

reference_id BIGINT,  -- order_id

notes TEXT,

created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

INDEX (product_id, created_at),

INDEX (reference_type, reference_id)

);

```

**Insert khi:**
- SHIPPING (type = 'sale', quantity = negative)
- RETURN_CONFIRMED (type = 'return', quantity = positive)
- CANCELLED (type = 'cancel', quantity = positive if >= SHIPPING)

---

## 🚨 ERROR HANDLING

### **1. Insufficient Stock (when SHIPPING)**

```

Request: PROCESSING → SHIPPING

Stock: Product A only has 3, order needs 5

Response: 422 Unprocessable Entity

{

"error": "insufficient_stock",

"message": "Product 'iPhone 15' only has 3 units available",

"product_id": 10,

"available": 3,

"requested": 5

}

Action: Staff giảm quantity hoặc cancel order

```

---

### **2. Invalid Transition**

```

Request: DRAFT → DELIVERED (skip intermediate states)

Response: 400 Bad Request

{

"error": "invalid_transition",

"message": "Cannot transition from 'draft' to 'delivered'",

"from": "draft",

"to": "delivered",

"allowed": ["confirmed", "cancelled"]

}

```

---

### **3. Permission Denied**

```

User: Staff

Request: Any status → CANCELLED

Response: 403 Forbidden

{

"error": "permission_denied",

"message": "Only admin can cancel orders"

}

```

---

### **4. Concurrent Update**

```

User A: Update order 123 status

User B: Update order 123 status (cùng lúc)

Solution: Pessimistic locking

SELECT * FROM orders WHERE id = 123 FOR UPDATE;

-- User B phải chờ User A commit

```

---

## 🔗 RELATED DOCUMENTS

- **Business Decisions:** `01_BUSINESS_[DECISIONS.md](http://DECISIONS.md)` #1-7
- **API Implementation:** Task ORD-003
- **Return Flow:** `RETURN_[FLOW.md](http://FLOW.md)`
- **Invoice Flow:** `INVOICE_[FLOW.md](http://FLOW.md)`

---

**END OF SHIPPING FLOW**
```