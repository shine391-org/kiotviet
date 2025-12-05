ERP-015 - POS Payment Entry & Tax/Charge Integration
Bạn là AI backend engineer phụ trách nối POS với kế toán/tax như ERPNext.

1. Bối cảnh
- ERPNext POS tạo Sales Invoice + Payment Entry, áp dụng tax/charge template, rounding, ghi GL. LanoCRM mới dừng ở order.

2. Phạm vi & Deliverables
- Migration: payment_entries (order_id, method, amount, reference, status), tax_templates (if chưa), tax_charges lines, rounding adjustments.
- Validator: PaymentEntryValidator, TaxTemplateValidator.
- Repository: PaymentEntryRepository, TaxTemplateRepository, TaxChargeRepository.
- Service: PaymentEntryService (create/settle/refund), POSTaxService (apply tax template, compute totals/rounding), hook to GL stub (if GL not present, chuẩn bị interfaces).
- Controller API: endpoints tạo payment entry từ POS checkout; manage tax templates.
- Integration: POS checkout tạo payment entries per split, apply tax/charge from POS profile template, compute rounding; ensure ledger hook stubbed for future GL.

3. Yêu cầu kỹ thuật
- Atomic with order creation; idempotent by order_id/payment ref.
- Support multiple payment methods and rounding rules.

4. Testing (DevDatabaseTrait)
- Unit: PaymentEntryServiceTest (create/settle/refund, idempotent), POSTaxServiceTest (tax calc, rounding).
- Integration: POS checkout with multi-payment + tax template applied; duplicate guard.
- Coverage ≥70% theo TESTING-PATTERNS.

5. Definition of Done
- POS payments/tax ghi nhận được, sẵn sàng nối GL sau này.
- Unit + integration tests pass.
