ERP-023 - Purchase Invoice & Tax
Bạn là AI backend engineer phụ trách Purchase Invoice như ERPNext.

1. Bối cảnh
- ERPNext có Purchase Invoice (hóa đơn mua) với tax/charge, nhận hàng, GL. LanoCRM chưa có.

2. Phạm vi & Deliverables
- Migration: purchase_invoices (number, supplier_id, posting_date, due_date, status, currency, total, taxes_total, grand_total, rounding_adjustment), purchase_invoice_items, purchase_invoice_taxes.
- Validator: PurchaseInvoiceValidator, TaxTemplateValidator (reuse).
- Repository: PurchaseInvoiceRepository (+ items, taxes).
- Service: PurchaseInvoiceService (create/submit/cancel, tax calc, link to GRN/delivery if exists, post GL), numbering helper.
- Controller API: CRUD/submit/cancel purchase invoice, preview totals.
- Integration: Link to stock receipts if available; ApprovalService optional; GL posting via AccountingService.

3. Testing (DevDatabaseTrait)
- Unit: PurchaseInvoiceServiceTest (totals, tax, submit/cancel GL), negative scenarios.
- Integration: API create/submit/cancel, GL entries balanced.
- Coverage ≥70% theo TESTING-PATTERNS.

4. Definition of Done
- Purchase invoice hoạt động, tax/charge đầy đủ, GL posting/cancel.
- Unit + integration tests pass.
