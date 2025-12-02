ERP-022 - Sales Invoice & Tax Template
Bạn là AI backend engineer phụ trách Sales Invoice như ERPNext.

1. Bối cảnh
- ERPNext có Sales Invoice với tax/charge, payment schedule, GL posting. LanoCRM chỉ có order/invoice cơ bản.

2. Phạm vi & Deliverables
- Migration: sales_invoices (invoice_number, customer_id, posting_date, due_date, status draft/submitted/paid/cancelled, currency, exchange_rate, total, taxes_total, grand_total, rounding_adjustment), sales_invoice_items, sales_invoice_taxes (template line), payment_schedules.
- Validator: SalesInvoiceValidator, TaxTemplateValidator (reuse if ERP-015 created), PaymentScheduleValidator.
- Repository: SalesInvoiceRepository (+ items, taxes, payment schedule), TaxTemplateRepository if not yet.
- Service: SalesInvoiceService (create/submit/cancel, apply tax templates, compute totals/rounding, generate payment schedule, post GL via AccountingService), numbering helper.
- Controller API: CRUD/submit/cancel invoice, preview totals.
- Integration: Link from Order/Delivery; ApprovalService optional for high-value; PaymentEntry integration later.

3. Testing (DevDatabaseTrait)
- Unit: SalesInvoiceServiceTest (totals, tax calc, rounding, submit/cancel GL posting), PaymentScheduleTest.
- Integration: API create/submit invoice from order/delivery; tax template applied; GL entries balanced.
- Coverage ≥70% theo TESTING-PATTERNS.

4. Definition of Done
- Sales invoice đầy đủ tax/charge, schedule, GL posting; cancel reverses entries.
- Unit + integration tests pass.
