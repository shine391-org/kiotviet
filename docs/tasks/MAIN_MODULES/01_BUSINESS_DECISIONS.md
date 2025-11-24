```markdown
# BUSINESS DECISIONS - LanoCRM Order Workflow

**Last Updated:** 2025-11-24  
**Version:** 1.0  
**Status:** Final - Approved by Boss

---

## MỤC ĐÍCH

Document này ghi nhận **39 QUYẾT ĐỊNH BUSINESS** đã được chốt để implement Order Workflow cho LanoCRM.

Mọi implementation trong các TASK files phải tuân thủ các quyết định này.

---

## OVERVIEW

LanoCRM có **2 LOẠI ĐƠN HÀNG** hoàn toàn khác nhau:

### 1. POS Orders (Bán tại cửa hàng)
- Không có status workflow
- Trừ kho ngay khi tạo đơn
- Thu tiền mặt ngay lập tức
- Không có shipping, không có COD

### 2. SHIPPING Orders (Giao hàng)
- Có status workflow phức tạp
- Trừ kho khi chuyển sang trạng thái SHIPPING
- Hỗ trợ COD, theo dõi vận chuyển
- Có thể return, cancel

---

## PHẦN 1: ORDER STATUS & INVENTORY (7 quyết định)

### Decision #1: POS Orders - Không có status

**Rule:**
- POS orders KHÔNG CÓ status workflow
- Có thể để status = null HOẶC status = 'COMPLETED'
- Không bao giờ transition sang status khác

**Why:**
- POS là giao dịch tức thì
- Khách đưa tiền → nhận hàng → xong
- Không cần tracking intermediate states

**Implementation:**
- Khi tạo POS order: set status = null hoặc 'COMPLETED'
- Không cho phép update status của POS orders
- API status update: check order type trước

---

### Decision #2: SHIPPING Orders - Có status workflow

**Rule:**
Status workflow đầy đủ:
```

DRAFT → CONFIRMED → PROCESSING → SHIPPING → DELIVERED → COMPLETED

```

**Intermediate paths:**
```

Any status (except COMPLETED) → CANCELLED (admin only)

SHIPPING → FAILED → RETURN → RETURN_CONFIRMED

```

**Why:**
- Shipping có nhiều stages: chuẩn bị, giao, xác nhận
- Cần tracking để customer biết đơn đang ở đâu
- Cần hooks để xử lý inventory, payments

**Implementation:**
- State machine với valid transitions
- Audit log mọi status change
- Hooks ở từng transition

---

### Decision #3: Trừ kho khi SHIPPING

**Rule:**
- Inventory chỉ được deduct khi order chuyển sang status = **SHIPPING**
- Không trừ kho ở DRAFT, CONFIRMED, hay PROCESSING
- Khi SHIPPING: loop qua order_items và giảm inventory.quantity

**Why:**
- SHIPPING = đã bắt đầu fulfill order (đóng gói, giao shipper)
- CONFIRMED/PROCESSING = chưa chắc có đủ hàng, chưa đóng gói
- Tránh lock inventory quá sớm

**Edge cases:**
- Nếu không đủ stock khi SHIPPING: reject transition (422 error)
- Partial fulfillment: Phase 1 không hỗ trợ (all or nothing)

**Implementation:**
- Hook: AFTER status updated to SHIPPING
- Transaction: status update + inventory update cùng transaction
- Log vào inventory_movements (type = 'sale')

---

### Decision #4: Failed path → Return workflow

**Rule:**
```

SHIPPING → FAILED (giao hàng thất bại)

↓

RETURN (hàng đang về kho)

↓

RETURN_CONFIRMED (hàng đã về kho) → Rollback inventory

```

**Why:**
- FAILED: shipper báo không giao được (khách từ chối, sai địa chỉ...)
- Hàng phải về kho mới cộng lại inventory
- Không cộng ngay khi FAILED (hàng còn đang trên đường về)

**Implementation:**
- FAILED: không rollback inventory
- RETURN_CONFIRMED: rollback inventory + log movement (type = 'return')
- Manual transition (staff confirm hàng về kho)

---

### Decision #5: Cancel order - Admin only, rollback inventory

**Rule:**
- **Chỉ admin** có quyền cancel order
- Staff/Customer: không có quyền cancel
- Cancel được từ bất kỳ status nào **TRỪ COMPLETED**
- Rollback inventory nếu đã trừ

**Rollback conditions:**
- Status < SHIPPING: không cần rollback (chưa trừ kho)
- Status >= SHIPPING: phải rollback (đã trừ kho)

**Why:**
- Cancel là quyết định nghiêm trọng (ảnh hưởng inventory, revenue)
- Chỉ admin đủ authority
- COMPLETED: đã hoàn tất, không cho cancel (dùng return thay)

**Implementation:**
- Permission check: user.role === 'admin' (Phase 1 hard-code)
- Cancel endpoint: POST /orders/:id/cancel
- Hook: rollback inventory nếu status >= SHIPPING

---

### Decision #6: FAILED vs CANCELLED - Phân biệt rõ

**Rule:**
- **FAILED:** Giao hàng thất bại (shipper báo)
- **CANCELLED:** Admin/khách hủy đơn

**Status fields:**
```

orders.status = 'failed' hoặc 'cancelled'

```

**Why:**
- Khác nhau về nguồn gốc (external failure vs internal decision)
- Metrics khác nhau (failure rate vs cancellation rate)
- Handling logic có thể khác (FAILED → auto return, CANCELLED → manual)

**Implementation:**
- 2 status riêng trong enum
- Webhook từ shipper → FAILED
- Admin action → CANCELLED

---

### Decision #7: Cancel tracking - Reason & Notes

**Rule:**
Capture khi cancel:
```

orders.cancel_reason ENUM('customer_request', 'out_of_stock', 'wrong_address', 'other')

orders.cancel_notes TEXT

```

**Why:**
- Analytics: tại sao orders bị cancel?
- Customer service: trace lại lịch sử
- Improve process: giảm cancellation

**Implementation:**
- Cancel endpoint input: {cancel_reason, cancel_notes}
- Validation: cancel_reason required, cancel_notes optional
- Store in orders table

---

## PHẦN 2: PAYMENT METHODS (8 quyết định)

### Decision #8: COD Tracking - 2 trường hợp

**Rule:**

**Case 1: Ship qua API (GHN/GHTK)**
- Webhook tự động update
- Events: delivered, cod_collected, cod_reconciled

**Case 2: Ship ngoài (tự do)**
- Staff manual confirm delivery
- Staff manual confirm thu tiền COD

**Fields:**
```

orders.cod_collected BOOLEAN      -- Shipper đã thu tiền từ khách

orders.cod_reconciled BOOLEAN     -- Shop đã nhận tiền từ shipper

orders.shipping_partner VARCHAR   -- 'GHN', 'GHTK', 'manual', etc

```

**Why:**
- Không phải lúc nào cũng dùng GHN/GHTK (shop nhỏ tự ship)
- Cần flexibility cho cả 2 cases
- COD tracking quan trọng cho financial reconciliation

**Implementation:**
- Webhook endpoint: POST /webhooks/shipping/:partner
- Manual confirm: POST /orders/:id/confirm-delivery
- Update cod_collected khi confirm

---

### Decision #9: Partial Payment - Track paid_amount & debt_amount

**Rule:**
```

[orders.total](http://orders.total) DECIMAL(15,2)           -- Tổng giá trị đơn

orders.paid_amount DECIMAL(15,2)     -- Số tiền đã trả

orders.debt_amount DECIMAL(15,2)     -- = total - paid_amount

```

**Use cases:**
- Customer đặt cọc 30%, ship thu 70%
- Customer trả góp
- B2B: trả sau 30 ngày

**Why:**
- Linh hoạt hơn boolean is_paid
- Track được customer debt
- Support multiple payment installments

**Implementation:**
- Create order: paid_amount = 0 (hoặc deposit amount)
- Update paid_amount: POST /orders/:id/payment
- Validation: paid_amount <= total

---

### Decision #10: Payment Methods - 1 method per order

**Rule:**
- Mỗi order chỉ có **1 payment method**
- Không split payment (50% cash + 50% card)

**Methods:**
```

orders.payment_method ENUM('CASH', 'BANK_TRANSFER', 'CARD', 'COD', 'E_WALLET')

```

**Why:**
- Simplicity (Phase 1)
- Dễ tracking
- Most use cases chỉ cần 1 method

**Future:**
- Phase 2 có thể thêm table order_payments cho split

**Implementation:**
- payment_method: required field
- Validation: must be in allowed list

---

### Decision #11: 5 Payment Methods

**List:**
1. CASH: Tiền mặt
2. BANK_TRANSFER: Chuyển khoản
3. CARD: Thẻ tín dụng/ghi nợ
4. COD: Thu hộ
5. E_WALLET: Ví điện tử (MoMo, ZaloPay...)

**Configuration:**
- Table payment_methods lưu config cho từng method
- Có thể bật/tắt từng method
- Config JSON: bank account, gateway info...

**Implementation:**
- Seed 5 methods mặc định
- Admin có thể disable methods không dùng

---

### Decision #12: POS Orders - Payment Method = CASH

**Rule:**
POS orders luôn có:
```

payment_method = 'CASH'

paid_amount = total

debt_amount = 0

```

**Why:**
- POS = bán tại quầy = thu tiền mặt ngay
- Không có case khác (không thể COD tại quầy)

**Implementation:**
- Khi tạo POS order: auto set payment_method = 'CASH'
- Auto set paid_amount = total
- Validation: POS order không cho phép methods khác

---

### Decision #13: SHIPPING Orders - Thường là COD

**Rule:**
SHIPPING orders thường:
```

payment_method = 'COD' (hoặc methods khác nếu pre-paid)

paid_amount = 0 (hoặc deposit amount)

debt_amount = total (hoặc remaining)

```

**Flexibility:**
- Cho phép BANK_TRANSFER (customer chuyển khoản trước)
- Cho phép E_WALLET
- Tùy business model

**Implementation:**
- payment_method: required khi tạo order
- paid_amount: default 0, có thể override
- debt_amount: auto calculate

---

### Decision #14: Ship ngoài - Manual Confirm

**Rule:**
Khi staff tự giao hàng (không qua GHN/GHTK):
- Staff click "Confirm Delivery"
- Status: SHIPPING → DELIVERED
- COD collected: staff confirm đã thu tiền

**Fields:**
```

shipping_partner = 'manual'

```

**Why:**
- Shop nhỏ tự ship
- Vùng không có GHN/GHTK
- Giao tận tay quen biết

**Implementation:**
- Endpoint: POST /orders/:id/confirm-delivery
- Input: {cod_collected: true/false, notes}
- Permission: staff có quyền

---

### Decision #15: Manual Confirm → DELIVERED

**Rule:**
Manual confirm chuyển status trực tiếp:
```

SHIPPING → DELIVERED (không qua intermediate status)

```

**Why:**
- Không cần tracking chi tiết như API shipping
- Staff confirm = hàng đã giao

**Implementation:**
- Gọi updateStatus('delivered')
- Update cod_collected nếu COD order

---

## PHẦN 3: INVOICES & TAX (7 quyết định)

### Decision #16: VAT Configurable - 0%, 5%, 10%

**Rule:**
- VAT rate có thể là: 0%, 5%, hoặc 10%
- Admin config trong settings
- Default: 10%

**Storage:**
```

-- Settings table

settings.key = 'tax.vat_rate'

settings.value = '0.10'  -- hoặc '0.00', '0.05'

```

**Why:**
- Different products có VAT khác nhau
- Government thay đổi VAT rate
- Some customers miễn VAT

**Implementation:**
- Settings management UI
- Invoice snapshot VAT rate (immutability)
- Admin có thể update settings

---

### Decision #17: Invoice - Chỉ tạo khi khách yêu cầu

**Rule:**
- Invoice CHỈ tạo khi customer có tax_code (mã số thuế)
- Không tự động tạo cho mọi order
- Customer phải explicitly request

**Why:**
- Không phải customer nào cũng cần hóa đơn VAT
- Retail customers: không cần
- B2B/Corporate: bắt buộc cần

**Implementation:**
- Check: [customer.tax](http://customer.tax)_code IS NOT NULL
- Create invoice: manual action (admin or customer request)
- Validation: reject nếu customer không có tax_code

---

### Decision #18: Nhiều Orders → 1 Invoice (Gộp tháng)

**Rule:**
- 1 invoice có thể chứa nhiều orders
- Thường gộp orders của 1 customer trong 1 tháng
- Table invoice_orders để map

**Schema:**
```

invoices (id, invoice_number, customer_id, ...)

invoice_orders (invoice_id, order_id)

```

**Why:**
- B2B customers: gộp để đối soát dễ
- Giảm số lượng invoices
- Tax reporting tiện hơn

**Implementation:**
- Create invoice: input array order_ids[]
- Validation: tất cả orders cùng customer
- Insert multiple rows vào invoice_orders

---

### Decision #19: Invoice Number - HD-{branch_id}-{auto_increment}

**Rule:**
Format: `HD-{branch_id}-{số tự tăng}`

Examples:
- HD-1-0001 (branch 1, invoice đầu tiên)
- HD-1-0002
- HD-2-0001 (branch 2, invoice đầu tiên)

**Counter:**
- Per branch (mỗi branch có counter riêng)
- Auto-increment

**Why:**
- Dễ identify branch
- Counter per branch dễ track
- Unique globally (branch_id + number)

**Implementation:**
- Table sequences hoặc computed on-the-fly
- Lock để tránh race condition
- Zero-padding: 0001, 0002...

---

### Decision #20: Due Date - Admin nhập thủ công

**Rule:**
- Due date KHÔNG tự động calculate
- Admin nhập khi tạo invoice
- Optional field (có thể null)

**Why:**
- Mỗi customer có credit terms khác nhau
- Some: 7 ngày, some: 30 ngày, some: 60 ngày
- Không có universal rule

**Implementation:**
- Invoice form: due_date input (date picker)
- Validation: due_date >= issue_date (nếu có)
- Can be null

---

### Decision #21: PDF - Generate On-Demand

**Rule:**
- PDF KHÔNG tự động generate khi tạo invoice
- Generate khi:
  - Admin click "Tải PDF"
  - Customer click "In hóa đơn"
  
**Storage:**
```

invoices.pdf_path VARCHAR  -- NULL cho đến khi generate

```

**Why:**
- Performance: không cần generate ngay
- Storage: không lưu PDF không cần thiết
- On-demand: generate khi cần mới nhất

**Implementation:**
- Endpoint: GET /invoices/:id/pdf
- Check: nếu pdf_path NULL → generate
- Nếu có rồi: serve cached file

---

### Decision #22: Invoice chỉ cho Orders COMPLETED

**Rule:**
- Chỉ tạo invoice cho orders có status = **COMPLETED**
- Không invoice cho DRAFT, CONFIRMED, PROCESSING...

**Why:**
- Invoice = chứng từ tài chính chính thức
- Chỉ issue khi order đã hoàn thành
- Tránh issue invoice rồi order bị cancel

**Validation:**
- Check tất cả orders trong request
- Reject nếu bất kỳ order nào chưa COMPLETED

**Implementation:**
- Query: WHERE status = 'completed'
- Validation: 422 error nếu orders invalid

---

## PHẦN 4: RETURNS & REFUNDS (11 quyết định)

### Decision #23: Return Window - Không giới hạn

**Rule:**
- KHÔNG có hard limit (7 ngày, 15 ngày...)
- Admin quyết định từng case
- Có thể set policy mặc định nhưng override được

**Why:**
- Flexibility
- Different products có policy khác nhau
- Customer service có thể accommodate special cases

**Implementation:**
- Không validate return window trong code
- Admin approve/reject dựa trên business judgment
- Optional: settings có default policy (display only)

---

### Decision #24: Return Approval - Admin (có thể cấp quyền)

**Rule:**
- Default: chỉ **Admin** approve return
- Flexible: Admin có thể cấp quyền cho users khác
- Không hard-code role check

**Permission system:**
```

Permission: 'returns.approve'

Admin có thể assign cho staff/manager

```

**Why:**
- Scalability: admin không phải approve mọi request
- Trust: manager có thể approve
- Flexibility: different businesses có structure khác nhau

**Implementation:**
- Phase 1: simple role check (admin only)
- Permission table ready cho Phase 2
- hasPermission($user, 'returns.approve')

---

### Decision #25: Shipping Fee Refund - Linh hoạt

**Rule:**
- Admin quyết định khi approve return
- Field: refund_shipping_fee (boolean)
- Policies:
  - Lỗi shop: refund
  - Khách đổi ý: không refund
  - Negotiable

**Calculation:**
```

refund_amount = return_amount + (shipping_fee IF refund_shipping_fee)

```

**Why:**
- Tùy case
- Ai lỗi: người đó chịu phí ship
- Customer service flexibility

**Implementation:**
- Approve endpoint input: {refund_shipping_fee: true/false}
- Calculate refund_amount accordingly
- Store decision in returns table

---

### Decision #26: Restock - LUÔN LUÔN restock

**Rule:**
- Khi return APPROVED và hàng về kho (RETURN_CONFIRMED)
- **LUÔN LUÔN** cộng lại inventory
- KHÔNG check condition (new/used/damaged)

**Why:**
- Simplicity
- Inventory accuracy > condition tracking
- Condition chỉ dùng cho reporting

**Edge case:**
- Hàng hỏng: vẫn restock (inventory count đúng)
- Admin có thể adjust inventory sau (manual adjustment)

**Implementation:**
- Loop return_items
- inventory.quantity += return_quantity
- Log inventory_movements (type = 'return')

---

### Decision #27: Partial Return - Cho phép

**Rule:**
- Customer có thể return **từng item**
- Không bắt buộc return toàn bộ order

**Example:**
- Order: 5 items A, 3 items B
- Return: 2 items A only
- Keep: 3 items A, 3 items B

**Schema:**
```

return_items (

return_id,

order_item_id,

quantity_returned  -- <= order_item.quantity

)

```

**Implementation:**
- Validate: quantity_returned <= order_item.quantity
- Track: SUM(previous returns) để không vượt quá
- Return amount: proportional

---

### Decision #28: Refund Methods - CASH + BANK_TRANSFER

**Rule:**
Supported refund methods:
1. CASH: Hoàn tiền mặt
2. BANK_TRANSFER: Chuyển khoản

**NOT supported (Phase 1):**
- STORE_CREDIT / Voucher

**Why:**
- Simple
- Most common methods
- Voucher: complexity cao (Phase 2)

**Implementation:**
```

returns.refund_method ENUM('cash', 'bank_transfer')

```

---

### Decision #29: Return từ DELIVERED trở lên

**Rule:**
- Chỉ cho return orders có status >= **DELIVERED**
- Không cho return DRAFT, CONFIRMED, PROCESSING

**Why:**
- Customer chưa nhận hàng → không thể return
- CANCELLED: không cần return
- DELIVERED/COMPLETED: đã nhận hàng → có thể return

**Implementation:**
- Validation: order.status IN ('delivered', 'completed')
- Reject: 422 error nếu status invalid

---

### Decision #30: Return Number - TH-{order_id}-{auto_increment}

**Rule:**
Format: `TH-{order_id}-{số tự tăng}`

Examples:
- TH-123-1 (order 123, return request đầu tiên)
- TH-123-2 (order 123, return request thứ 2)
- TH-456-1 (order 456, return request đầu tiên)

**Counter:**
- Per order (mỗi order có counter riêng)
- Auto-increment

**Why:**
- Link rõ ràng với order
- Dễ track multiple returns cho 1 order
- Unique per order

**Implementation:**
- Query: COUNT returns WHERE order_id = X
- Number = count + 1
- Format: sprintf("TH-%d-%d", order_id, number)

---

### Decision #31: Return Reason - Dropdown + Free Text

**Rule:**
**Dropdown (ENUM):**
- defective: Hàng lỗi
- wrong_item: Giao nhầm
- not_satisfied: Không ưng ý
- other: Khác

**Free text:**
```

returns.reason ENUM(...)

returns.reason_detail TEXT  -- Mô tả chi tiết

```

**Validation:**
- reason: required
- reason_detail: required if reason = 'other'

**Why:**
- Structured data cho analytics
- Flexibility cho edge cases
- Customer có thể explain chi tiết

**Implementation:**
- UI: dropdown + textarea
- Backend: validate both fields

---

### Decision #32: Refund Timing - Chờ hàng về kho

**Rule:**
Workflow:
```

Customer request → PENDING

↓

Admin approve → APPROVED (chưa refund)

↓

Hàng về kho → Staff confirm → RETURN_CONFIRMED

↓

Execute refund

```

**Why:**
- Không refund trước khi nhận hàng
- Verify hàng đã về kho
- Avoid fraud (customer claim return nhưng không gửi hàng)

**Implementation:**
- APPROVED: update returns.status, chưa refund
- RETURN_CONFIRMED: restock + mark ready for refund
- Refund execution: Phase 2 (payment integration)

---

### Decision #33: Track Condition - For Reporting

**Rule:**
```

return_items.condition ENUM('new', 'used', 'damaged')

```

**Usage:**
- Staff nhập khi confirm hàng về
- For analytics/reporting only
- KHÔNG ảnh hưởng restock logic

**Why:**
- Track quality of returned items
- Analytics: % hàng damaged
- Improve product/shipping quality

**Implementation:**
- Input khi confirm return
- Store in return_items
- Report: group by condition

---

## PHẦN 5: OTHER DECISIONS (6 quyết định)

### Decision #34: Table invoice_orders - Mapping

**Rule:**
```

invoice_orders (

id BIGINT PRIMARY KEY,

invoice_id BIGINT,

order_id BIGINT,

FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,

FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE

)

```

**Why:**
- Many-to-many relationship
- 1 invoice chứa nhiều orders
- 1 order có thể có nhiều invoices (rare, but possible)

**Implementation:**
- Create khi generate invoice
- Query: JOIN để lấy orders của invoice

---

### Decision #35: Table order_status_logs - Audit Trail

**Rule:**
```

order_status_logs (

id BIGINT PRIMARY KEY,

order_id BIGINT,

old_status VARCHAR,

new_status VARCHAR,

user_id BIGINT,

notes TEXT,

created_at DATETIME

)

```

**Purpose:**
- Track mọi lần status change
- Audit: ai đổi, khi nào, tại sao
- Debug issues

**Implementation:**
- Insert AFTER mỗi status update
- Never delete (audit trail)
- Index: (order_id, created_at)

---

### Decision #36: Table inventory_movements - Stock Tracking

**Rule:**
```

inventory_movements (

id BIGINT PRIMARY KEY,

branch_id BIGINT,

product_id BIGINT,

variant_id BIGINT,

type ENUM('sale', 'return', 'cancel', 'restock', 'adjustment'),

quantity INT,  -- positive or negative

reference_type VARCHAR,

reference_id BIGINT,

notes TEXT,

created_at DATETIME

)

```

**Purpose:**
- Track mọi giao dịch inventory
- Audit: tại sao stock thay đổi
- Reconciliation

**Implementation:**
- Insert khi: sale, return, cancel, adjustment
- Never delete
- Query: SUM để verify inventory balance

---

### Decision #37: Permissions - Flexible System

**Rule:**
- Phase 1: Hard-code role checks (admin/staff/customer)
- Phase 2+: Granular permission system

**Permissions:**
- orders.cancel
- orders.update_status
- returns.approve
- returns.confirm
- invoices.create

**Why:**
- Scalability
- Different businesses có org structure khác nhau
- Admin có thể delegate authority

**Implementation:**
- hasPermission($user, '[permission.name](http://permission.name)') → bool
- Phase 1: return user.role === 'admin'
- Phase 2: query permissions table

---

### Decision #38: Manual Delivery Confirm - Endpoint

**Rule:**
Endpoint: `POST /orders/:id/confirm-delivery`

Input:
```

{

"cod_collected": true,

"notes": "Giao tận tay khách"

}

```

**Who:**
- Staff có quyền
- Admin có quyền

**Action:**
- Update status: SHIPPING → DELIVERED
- Update cod_collected nếu COD order
- Log status change

**Implementation:**
- Permission check
- Call updateStatus('delivered')
- Update COD flags

---

### Decision #39: Customer Debt - Real-time Query

**Rule:**
- KHÔNG cache debt trong customers table
- Query real-time khi cần:
```

SELECT SUM(total - paid_amount)

FROM orders

WHERE customer_id = ?

AND status NOT IN ('cancelled')

AND deleted_at IS NULL

```

**Why:**
- Accuracy > performance
- Avoid sync issues
- Debt thay đổi khi: order tạo, payment update, order cancel, return refund

**Implementation:**
- Method: CustomerService::getDebt($customerId)
- Cache ở application layer (Redis) nếu cần
- Invalidate cache khi orders change

---

## TÓM TẮT THEO LOẠI

### A. Order Management (15 decisions)
- #1-7: Status workflow & inventory
- #8-15: Payment methods & tracking

### B. Invoices (7 decisions)
- #16-22: VAT, generation, numbering

### C. Returns (11 decisions)
- #23-33: Return workflow, approval, refund

### D. Infrastructure (6 decisions)
- #34-39: Supporting tables, permissions

---

## CROSS-REFERENCES

| Decision | Related Tasks | Priority |
|----------|---------------|----------|
| #1-7 | TASK_04,05,06,07,08 | 🔴 Critical |
| #8-15 | TASK_01,05,13 | 🔴 Critical |
| #16-22 | TASK_02,12 | 🟡 High |
| #23-33 | TASK_03,09,10,11 | 🟡 High |
| #34-39 | TASK_04 | 🟢 Medium |

---

**END OF BUSINESS DECISIONS**

**Mọi implementation phải tuân thủ 39 quyết định này.**  
**Nếu có conflict: Business decisions > Technical preferences.**
```