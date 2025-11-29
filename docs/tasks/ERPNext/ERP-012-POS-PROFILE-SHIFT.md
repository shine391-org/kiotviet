ERP-012 - POS Profile, Payment Split & Shift Closing
Bạn là AI backend engineer phụ trách đưa các đặc thù POS của ERPNext vào LanoCRM.

1. Bối cảnh
- ERPNext POS có POS Profile (price list/tax/warehouse theo user), multi-payment split, cash drawer/shift closing voucher.
- LanoCRM mới có POS order type cơ bản, chưa có profile/shift.

2. Phạm vi & Deliverables
- Migration: pos_profiles (user/role, price_list_id, tax_template_id, warehouse_id/branch_id, company, allow_offline, credit_limit?), pos_payment_methods (profile_id, method, allowed), pos_shift (user, opening_balance, closing_balance, status, opened_at, closed_at), pos_shift_payments (method, amount, reference), pos_shift_logs.
- Validator: POSProfileValidator, POSShiftValidator.
- Repository: POSProfileRepository, POSShiftRepository, POSShiftPaymentRepository.
- Service: POSProfileService (CRUD, resolve profile for user/device), POSShiftService (open/close shift, enforce only one open per user, reconcile amounts), POSPaymentSplitService (validate multi-payment on order checkout).
- Controller API (thin): manage POS profiles, open/close shift, fetch profile for POS session; order checkout accepts multi-payment payload and validates against profile methods.
- Integration: OrderService POS flow uses profile (price list/tax/warehouse) + validates payments; shift closing generates summary/log; hook to ledger/payment if available.

3. Yêu cầu kỹ thuật
- Enforce only allowed payment methods per profile; require open shift before POS checkout if configured.
- Calculate expected vs actual cash/card per shift; store discrepancy.
- Transactions: checkout with multi-payment must be atomic (order + payments + shift update).

4. Testing (DevDatabaseTrait)
- Unit: POSProfileServiceTest (resolve profile, validation), POSShiftServiceTest (open/close, single open guard), POSPaymentSplitServiceTest (split validation, allowed methods).
- Integration: POS checkout API with profile/shift; close shift computes totals and discrepancies.
- Coverage ≥70% theo TESTING-PATTERNS.

5. Definition of Done
- POS order dùng profile (price list/tax/warehouse) + multi-payment valid.
- Shift lifecycle chạy được, lưu log, enforce single open per user.
- Unit + integration tests pass.
