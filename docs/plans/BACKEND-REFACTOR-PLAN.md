# Backend Refactor Plan - LANO CRM

**Version:** 2.0  
**Updated:** 2025-11-21  
**Duration:** 8 tuần  
**Status:** In Progress (Week 1 - Phase 1)

---

## 🎯 MỤC TIÊU TỔNG QUAN

Refactor backend LANO CRM từ fat controllers sang clean architecture, đồng thời xây dựng 9 modules mới thiết yếu.

### Thành quả kỳ vọng:
- ✅ Code maintainable, testable, reusable
- ✅ AI-friendly architecture (agents dễ hiểu và maintain)
- ✅ Test coverage 80%+
- ✅ Performance tối ưu
- ✅ 9 modules mới production-ready

---

## 📊 HIỆN TRẠNG DỰ ÁN

### ✅ Đã hoàn thành:
- **Auth Module:** 100% (JWT, roles, permissions)
- **Users Module:** 100% (CRUD, roles assignment)
- **Products Module:** 80% (cần refactor từ fat controller)

### 🔄 Đang thực hiện:
- **REFACTOR-001:** ProductService extraction (Week 1)

### ❌ Chưa có:
- Inventory Management
- Customer Relationship Management (CRM)
- Orders & Sales
- Point of Sale (POS)
- Partners & Suppliers
- Finance & Accounting
- Reporting & Analytics
- Notifications
- Third-party Integrations

---

## 🗓️ ROADMAP 8 TUẦN

### **PHASE 1: REFACTOR CODE CŨ (Tuần 1)**
**Mục tiêu:** Refactor modules hiện có sang clean architecture để làm pattern mẫu

#### Week 1: Products Module Refactor
- **REFACTOR-001:** ProductService & Repository (2-3 ngày)
  - Tách ProductsController (16KB) → Controller + Service + Repository
  - File: `docs/tasks/refactor/REFACTOR-001-ProductService.md`
  - Deliverables:
    - ProductValidator.php
    - ProductRepository.php
    - ProductService.php
    - ProductsController.php (updated, <150 dòng)
    - Unit tests (80% coverage)

- **REFACTOR-002:** VariantService (1 ngày)
  - Tách ProductVariantsController
  - Pattern giống REFACTOR-001

- **REFACTOR-003:** AttributeService (1 ngày)
  - Tách AttributesController
  - Pattern giống REFACTOR-001

**Milestone 1:** Có 3 patterns mẫu để copy cho modules mới

---

### **PHASE 2: CORE MODULES (Tuần 2-3)**
**Mục tiêu:** Xây dựng 2 modules quan trọng nhất

#### Week 2: Inventory Module (CRITICAL)
- **TASK-001:** InventoryModule
  - File: `docs/tasks/new-modules/TASK-001-InventoryModule.md`
  - Features:
    - Stock management (multi-warehouse)
    - Stock movements (in/out/transfer)
    - Stock alerts
    - Valuation (FIFO/LIFO/Average)
    - Adjustments & audits
  - Deliverables:
    - InventoryService
    - InventoryRepository
    - StockMovementService
    - WarehouseService
    - API endpoints (15+)
    - Tests (80% coverage)

#### Week 3: Customer Module (CRM)
- **TASK-002:** CustomersModule
  - File: `docs/tasks/new-modules/TASK-002-CustomersModule.md`
  - Features:
    - Customer CRUD
    - Customer groups & tiers
    - Purchase history
    - Loyalty points
    - Credit limits
    - Notes & communications
  - Deliverables:
    - CustomerService
    - CustomerRepository
    - CustomerGroupService
    - LoyaltyService
    - API endpoints (12+)
    - Tests

**Milestone 2:** Có thể track inventory và customers

---

### **PHASE 3: SALES MODULES (Tuần 4-5)**
**Mục tiêu:** Hoàn thiện flow bán hàng

#### Week 4: Orders Module
- **TASK-003:** OrdersModule
  - File: `docs/tasks/new-modules/TASK-003-OrdersModule.md`
  - Features:
    - Order creation & management
    - Order status workflow
    - Order items & variants
    - Discounts & promotions
    - Payments & refunds
    - Shipping integration
  - Deliverables:
    - OrderService
    - OrderRepository
    - OrderItemService
    - PaymentService
    - RefundService
    - API endpoints (20+)
    - Tests

#### Week 5: Point of Sale (POS)
- **TASK-004:** POSModule
  - File: `docs/tasks/new-modules/TASK-004-POSModule.md`
  - Features:
    - Cart management
    - Quick product search
    - Multiple payment methods
    - Split payments
    - Receipt generation
    - Cash drawer management
    - Shift management
  - Deliverables:
    - POSService
    - CartService
    - PaymentProcessorService
    - ReceiptService
    - ShiftService
    - API endpoints (15+)
    - Tests

**Milestone 3:** Có thể bán hàng qua web và POS

---

### **PHASE 4: SUPPLY CHAIN (Tuần 6)**
**Mục tiêu:** Quản lý nhà cung cấp và mua hàng

#### Week 6: Partners & Suppliers Module
- **TASK-005:** PartnersModule
  - File: `docs/tasks/new-modules/TASK-005-PartnersModule.md`
  - Features:
    - Supplier management
    - Purchase orders
    - Purchase order approval workflow
    - Goods receipt notes (GRN)
    - Supplier invoices
    - Payment to suppliers
  - Deliverables:
    - SupplierService
    - PurchaseOrderService
    - GoodsReceiptService
    - SupplierPaymentService
    - API endpoints (18+)
    - Tests

**Milestone 4:** Có thể đặt hàng và nhận hàng từ suppliers

---

### **PHASE 5: FINANCE & REPORTING (Tuần 7)**
**Mục tiêu:** Quản lý tài chính và báo cáo

#### Week 7: Finance & Reporting Modules
- **TASK-006:** FinanceModule
  - File: `docs/tasks/new-modules/TASK-006-FinanceModule.md`
  - Features:
    - Chart of accounts
    - Transactions & journal entries
    - Accounts receivable (AR)
    - Accounts payable (AP)
    - Bank reconciliation
    - Expense tracking
  - Deliverables:
    - AccountingService
    - TransactionService
    - ARService
    - APService
    - API endpoints (15+)
    - Tests

- **TASK-007:** ReportingModule
  - File: `docs/tasks/new-modules/TASK-007-ReportingModule.md`
  - Features:
    - Sales reports
    - Inventory reports
    - Financial reports
    - Customer reports
    - Custom report builder
    - Export (PDF, Excel, CSV)
  - Deliverables:
    - ReportService
    - SalesReportService
    - InventoryReportService
    - FinancialReportService
    - ExportService
    - API endpoints (12+)
    - Tests

**Milestone 5:** Có reports đầy đủ cho business decisions

---

### **PHASE 6: INTEGRATIONS & POLISH (Tuần 8)**
**Mục tiêu:** Hoàn thiện hệ thống

#### Week 8: Notifications & Integrations
- **TASK-008:** NotificationsModule
  - File: `docs/tasks/new-modules/TASK-008-NotificationsModule.md`
  - Features:
    - Email notifications
    - SMS notifications
    - In-app notifications
    - Notification templates
    - Notification queue
    - User preferences
  - Deliverables:
    - NotificationService
    - EmailService
    - SMSService
    - TemplateService
    - API endpoints (8+)
    - Tests

- **TASK-009:** IntegrationsModule
  - File: `docs/tasks/new-modules/TASK-009-IntegrationsModule.md`
  - Features:
    - Payment gateways (VNPay, Momo, ZaloPay)
    - Shipping providers (GHN, GHTK, VTP)
    - Accounting software (MISA, Fast)
    - E-commerce platforms (Shopee, Lazada API)
  - Deliverables:
    - IntegrationService
    - PaymentGatewayService
    - ShippingService
    - API endpoints (10+)
    - Tests

**Milestone 6:** System production-ready với integrations đầy đủ

---

## 📋 TASK SUMMARY

### Refactor Tasks (Phase 1):
| ID | Module | Priority | Effort | Status |
|----|--------|----------|--------|--------|
| REFACTOR-001 | ProductService | High | 2-3 days | In Progress |
| REFACTOR-002 | VariantService | Medium | 1 day | Pending |
| REFACTOR-003 | AttributeService | Medium | 1 day | Pending |

### New Module Tasks (Phase 2-6):
| ID | Module | Priority | Effort | Dependencies | Status |
|----|--------|----------|--------|--------------|--------|
| TASK-001 | Inventory | Critical | 5 days | REFACTOR-001 | Pending |
| TASK-002 | Customers (CRM) | Critical | 4 days | None | Pending |
| TASK-003 | Orders | High | 5 days | TASK-001, TASK-002 | Pending |
| TASK-004 | POS | High | 4 days | TASK-001, TASK-003 | Pending |
| TASK-005 | Partners/Suppliers | High | 5 days | TASK-001 | Pending |
| TASK-006 | Finance | Medium | 4 days | TASK-003, TASK-005 | Pending |
| TASK-007 | Reporting | Medium | 3 days | All above | Pending |
| TASK-008 | Notifications | Low | 2 days | None | Pending |
| TASK-009 | Integrations | Medium | 3 days | TASK-003, TASK-004 | Pending |

**Total Effort:** ~38-40 ngày (8 tuần với 5 ngày/tuần)

---

## 🔗 DEPENDENCIES

REFACTOR-001 (Products)
↓
TASK-001 (Inventory) ← CRITICAL PATH
↓
TASK-003 (Orders)
↓
TASK-004 (POS)
↓
TASK-006 (Finance)
↓
TASK-007 (Reporting)

TASK-002 (Customers) → TASK-003 (Orders)
TASK-005 (Suppliers) → TASK-006 (Finance)
TASK-008 (Notifications) - Parallel (anytime)
TASK-009 (Integrations) → TASK-003, TASK-004
---

## 🎨 KIẾN TRÚC CHUẨN (Áp dụng cho tất cả modules)

Controller Layer (Routing only)
↓
Service Layer (Business Logic)
↓
Repository Layer (Database)
↓
Model Layer (Schema definition - passive)

text---

## 🎨 KIẾN TRÚC CHUẨN (Áp dụng cho tất cả modules)

Controller Layer (Routing only)
↓
Service Layer (Business Logic)
↓
Repository Layer (Database)
↓
Model Layer (Schema definition - passive)

### File Structure Template:
app/
├── Controllers/Api/
│ └── {Module}Controller.php (~100-250 dòng)
├── Services/{Module}/
│ ├── {Module}Service.php (~200-500 dòng)
│ └── {Feature}Service.php (nếu cần tách)
├── Repositories/{Module}/
│ └── {Module}Repository.php (~150-400 dòng)
├── Validators/
│ └── {Module}Validator.php (~60-200 dòng)
└── Models/
└── {Module}Model.php (passive, chỉ schema)

tests/
├── Services/
│ └── {Module}ServiceTest.php
└── Repositories/
└── {Module}RepositoryTest.php

---

## ✅ DEFINITION OF DONE (Mỗi task)

- [ ] Files tạo đúng structure
- [ ] Tuân thủ clean architecture
- [ ] Inline docs đầy đủ (@agent- tags)
- [ ] Unit tests viết và pass
- [ ] Integration tests pass
- [ ] API endpoints test thủ công OK
- [ ] Pre-commit checks pass
- [ ] Session log tạo
- [ ] Commit đúng format
- [ ] Code review (nếu có team)

---

## 🚀 GETTING STARTED

### Hiện tại (Week 1):
1. Review REFACTOR-001 task
cat docs/tasks/refactor/REFACTOR-001-ProductService.md

2. Assign to agent
→ Follow instructions in AGENTS.md
3. Monitor progress
→ Check session logs in docs/session-logs/
4. After REFACTOR-001 done → Start TASK-001 (Inventory)

---

## 📝 TRACKING & REPORTING

### Daily:
- Session logs: `docs/session-logs/YYYY-MM-DD-TASK-XXX.md`
- Git commits với proper format

### Weekly:
- Update task status trong file này
- Review completed modules
- Adjust timeline nếu cần

### Milestones:
- Milestone 1 (Week 1): Refactor patterns ready
- Milestone 2 (Week 3): Core modules (Inventory + Customers)
- Milestone 3 (Week 5): Sales flow complete
- Milestone 4 (Week 6): Supply chain complete
- Milestone 5 (Week 7): Finance & reporting
- Milestone 6 (Week 8): Production ready

---

## 🎯 SUCCESS CRITERIA

### Code Quality:
- ✅ All modules follow clean architecture
- ✅ Test coverage 80%+ cho Services & Repositories
- ✅ Zero critical bugs
- ✅ Performance: API response < 200ms (average)

### Functionality:
- ✅ 9 modules mới hoạt động đầy đủ
- ✅ Integration giữa modules ổn định
- ✅ Frontend integration không vỡ
- ✅ Backward compatible với code cũ

### Documentation:
- ✅ Inline docs đầy đủ
- ✅ API documentation (Postman/Swagger)
- ✅ Session logs chi tiết
- ✅ README cập nhật

---

## 🔧 TOOLS & TECH STACK

- **Backend:** PHP 8.4 + CodeIgniter 4.5
- **Database:** MySQL 8.4
- **Testing:** PHPUnit 10
- **Docker:** meomeo2 containers
- **Version Control:** Git + GitHub
- **AI Agents:** Cursor, Claude, Gemini, Jules, Codex

---

## 📞 STAKEHOLDERS

- **Project Lead:** Shine (PM + Review)
- **AI Agents:** Code implementation
- **Testing:** Automated (PHPUnit) + Manual (Shine)

---

## 🔄 VERSION HISTORY

- **v2.0 (2025-11-21):** Flexible guidelines, 8-week roadmap
- **v1.0 (2025-11-20):** Initial plan với hard limits

---

## 📌 NOTES

- Tuần 1 là quan trọng nhất: Tạo patterns mẫu đúng
- TASK-001 (Inventory) là critical path cho các task sau
- Có thể điều chỉnh thứ tự tasks dựa vào business priority
- Quality > Speed: Làm chậm nhưng đúng tốt hơn làm nhanh sai

---

**Last Updated:** 2025-11-21 13:55 ICT  
**Next Review:** End of Week 1 (sau REFACTOR-001)
