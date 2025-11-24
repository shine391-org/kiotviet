```markdown
# 📚 Order Workflow Documentation - Index

**Project:** LanoCRM  
**Module:** Order Workflow  
**Last Updated:** 2025-11-24  
**Version:** 1.0

---

## 🎯 MỤC ĐÍCH

Documentation này hướng dẫn AI agents implement **5 tasks backend** cho Order Workflow module:

1. **PAY-001:** Payment Methods Table
2. **INV-001:** Invoices Tables
3. **ORD-003:** Order Status Management
4. **RETURN-001:** Returns Tables
5. **RETURN-002:** Return Order API

---

## 📂 CẤU TRÚC DOCUMENTATION

### **Core Documents**

**1. Business Decisions (Foundation)**
- File: `01_BUSINESS_[DECISIONS.md](http://DECISIONS.md)`
- Nội dung: 39 quyết định business logic đã được chốt
- Mục đích: Reference cho tất cả tasks
- Trạng thái: ✅ Complete

---

### **Workflow Documents**

**2. Shipping Flow**
- File: `02_WORKFLOWS/SHIPPING_[FLOW.md](http://FLOW.md)`
- Nội dung: SHIPPING orders workflow chi tiết
- Flow: DRAFT → CONFIRMED → SHIPPING → DELIVERED → COMPLETED
- Related tasks: ORD-003
- Trạng thái: 📝 Ready

**3. Return Flow**
- File: `02_WORKFLOWS/RETURN_[FLOW.md](http://FLOW.md)`
- Nội dung: Return orders workflow chi tiết
- Flow: Request → Approve → Confirm → Refund
- Related tasks: RETURN-001, RETURN-002
- Trạng thái: 📝 Ready

**4. Invoice Flow**
- File: `02_WORKFLOWS/INVOICE_[FLOW.md](http://FLOW.md)`
- Nội dung: Invoice generation workflow
- Flow: Orders COMPLETED → Generate Invoice → PDF
- Related tasks: INV-001
- Trạng thái: 📝 Ready

---

## 🗺️ DEPENDENCY MAP

```

PAY-001 (Payment Methods)

↓

ORD-003 (Order Status) ← SHIPPING_[FLOW.md](http://FLOW.md)

↓

┌───┴───┐

│       │

↓       ↓

INV-001 RETURN-001 ← RETURN_[FLOW.md](http://FLOW.md)

(Invoice) (Returns)

↓       ↓

INV-002 RETURN-002

(API)   (API)

```

---

## 📋 5 TASKS OVERVIEW

### **PAY-001: Payment Methods Table**
- **Priority:** 🔴 Critical
- **Estimate:** 0.5 day
- **Dependencies:** None
- **Deliverable:** Table payment_methods với 5 methods

### **INV-001: Invoices Tables**
- **Priority:** 🔴 Critical
- **Estimate:** 1 day
- **Dependencies:** ORD-001 (Orders table)
- **Deliverable:** Tables invoices + invoice_orders

### **ORD-003: Order Status Management**
- **Priority:** 🟡 High
- **Estimate:** 2 days
- **Dependencies:** ORD-002 (Create Order API)
- **Deliverable:** Status workflow API + inventory hooks

### **RETURN-001: Returns Tables**
- **Priority:** 🟡 High
- **Estimate:** 0.5 day
- **Dependencies:** ORD-001
- **Deliverable:** Tables returns + return_items

### **RETURN-002: Return Order API**
- **Priority:** 🟡 High
- **Estimate:** 1.5 days
- **Dependencies:** RETURN-001
- **Deliverable:** Create/Approve/Confirm APIs

**Total Estimate:** 5.5 days

---

## 🎯 WORKFLOW SUMMARY

### **1. POS Orders (Không dùng workflow)**
```

Tạo order → Trừ kho NGAY → Thu tiền → Hoàn tất

(Vài phút)

```

### **2. SHIPPING Orders (Dùng workflow)**
```

DRAFT → CONFIRMED → PROCESSING → SHIPPING → DELIVERED → COMPLETED

(Vài ngày)

```

### **3. Return Orders**
```

Request → Admin Approve → Staff Confirm → Restock → Refund

(Vài ngày)

```

### **4. Invoice Generation**
```

Orders COMPLETED → Select Orders → Generate Invoice → Generate PDF

(On-demand)

```

---

## 📊 BUSINESS RULES SUMMARY

### **Orders**
- **2 loại:** POS (instant) vs SHIPPING (workflow)
- **POS:** Trừ kho ngay, payment = CASH, paid = total
- **SHIPPING:** Trừ kho khi SHIPPING, partial payment OK

### **Payments**
- **5 methods:** CASH, BANK_TRANSFER, CARD, COD, E_WALLET
- **1 method per order** (Phase 1)
- **Partial payment:** Track paid_amount, debt_amount
- **COD:** 2 cases (API webhook + manual confirm)

### **Invoices**
- **Chỉ tạo khi:** Customer có tax_code + explicitly request
- **Gộp được:** Nhiều orders → 1 invoice
- **VAT:** 0%, 5%, 10% (configurable)
- **Number format:** HD-{branch_id}-{auto_increment}

### **Returns**
- **Từ:** Orders DELIVERED trở lên
- **Window:** Không giới hạn (admin decide)
- **Partial:** Được (trả từng item)
- **Restock:** LUÔN LUÔN (không check condition)
- **Refund:** CASH hoặc BANK_TRANSFER

---

## 🔗 CROSS-REFERENCES

### **Từ Task → Workflow:**
- PAY-001 → Xem: SHIPPING_[FLOW.md](http://FLOW.md) (payment methods)
- ORD-003 → Xem: SHIPPING_[FLOW.md](http://FLOW.md) (full workflow)
- RETURN-001/002 → Xem: RETURN_[FLOW.md](http://FLOW.md)
- INV-001 → Xem: INVOICE_[FLOW.md](http://FLOW.md)

### **Từ Workflow → Business Decisions:**
- SHIPPING_FLOW → Decisions #1-15
- RETURN_FLOW → Decisions #23-33
- INVOICE_FLOW → Decisions #16-22

---

## 🚀 GETTING STARTED

### **Step 1: Đọc Foundation**
1. Đọc file này (INDEX) - hiểu overview
2. Đọc `01_BUSINESS_[DECISIONS.md](http://DECISIONS.md)` - hiểu business logic

### **Step 2: Chọn Task**
1. Chọn 1 trong 5 tasks để làm
2. Check dependencies (xem có task nào phải làm trước không)

### **Step 3: Đọc Workflow**
1. Nếu làm ORD-003 → đọc SHIPPING_[FLOW.md](http://FLOW.md)
2. Nếu làm RETURN-001/002 → đọc RETURN_[FLOW.md](http://FLOW.md)
3. Nếu làm INV-001 → đọc INVOICE_[FLOW.md](http://FLOW.md)

### **Step 4: Implement**
1. Tạo migration (nếu có table mới)
2. Tạo Model/Repository/Service
3. Tạo Controller + Routes
4. Viết tests
5. Update documentation

---

## 📝 CONVENTIONS

### **Code Style:**
- Follow existing codebase patterns
- Use Services for business logic
- Repositories for data access
- Validators for validation rules

### **Database:**
- Migration naming: `YYYY_MM_DD_HHMMSS_create_xxx_table.php`
- Foreign keys: ON DELETE CASCADE (hoặc RESTRICT tùy case)
- Indexes: Tạo cho foreign keys + query columns

### **API:**
- RESTful endpoints: `/api/resources`
- Response format: JSON với structure nhất quán
- Error codes: 400 (bad request), 422 (validation), 403 (forbidden), 404 (not found)

### **Testing:**
- Unit tests cho Services
- Integration tests cho APIs
- Fixtures cho test data

---

## 🔍 TROUBLESHOOTING

### **"Không hiểu business rule X"**
→ Đọc `01_BUSINESS_[DECISIONS.md](http://DECISIONS.md)` section tương ứng

### **"Không biết workflow như thế nào"**
→ Đọc SHIPPING_[FLOW.md](http://FLOW.md) / RETURN_[FLOW.md](http://FLOW.md) / INVOICE_[FLOW.md](http://FLOW.md)

### **"Task phụ thuộc vào task khác chưa xong"**
→ Xem Dependency Map, làm task dependency trước

### **"Code pattern không rõ"**
→ Xem existing code: ProductsController, OrdersController

---

## 📞 SUPPORT

**Repository:** [github.com/shine391-org/kiotviet](http://github.com/shine391-org/kiotviet)  
**Branch:** dev  
**Contact:** Boss (Nguyễn Trung)

---

## ✅ CHECKLIST

Trước khi bắt đầu task:
- [ ] Đã đọc INDEX này
- [ ] Đã đọc BUSINESS_[DECISIONS.md](http://DECISIONS.md)
- [ ] Đã đọc workflow tương ứng (nếu có)
- [ ] Đã check dependencies
- [ ] Đã hiểu deliverables

Sau khi hoàn thành task:
- [ ] Migration chạy được (up + down)
- [ ] Code follow conventions
- [ ] Tests pass
- [ ] API documentation updated
- [ ] Commit với message rõ ràng

---

**END OF INDEX**
```