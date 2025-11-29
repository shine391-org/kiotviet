ERP-024 - Payment Entry & Bank Reconciliation
Bạn là AI backend engineer phụ trách Payment Entry và bank reconciliation.

1. Bối cảnh
- ERPNext có Payment Entry (receive/pay/transfer) và Bank Reconciliation. LanoCRM mới có POS payment stub.

2. Phạm vi & Deliverables
- Migration: payment_entries (party_type/id, reference_type/id, mode_of_payment, amount, status, reference_no/date, currency), payment_entry_allocations (alloc invoice/credit), bank_statements (imported lines), bank_reconciliations (matching status), bank_reconciliation_logs.
- Validator: PaymentEntryValidator, BankReconciliationValidator.
- Repository: PaymentEntryRepository (+ allocations), BankStatementRepository, BankReconciliationRepository.
- Service: PaymentEntryService (receive/pay/transfer, allocate to invoices, post GL via AccountingService), BankReconciliationService (import statements, auto-match by amount/ref, manual match, mark reconciled), ModeOfPayment helper if needed.
- Controller API: create/submit/cancel payment entry; import/list statements; reconcile endpoints.
- Integration: Sales/Purchase Invoice settlement; POS payments can reuse PaymentEntryService.

3. Testing (DevDatabaseTrait)
- Unit: PaymentEntryServiceTest (receive/pay/transfer, allocation, GL posting), BankReconciliationServiceTest (auto-match, manual match, unreconcile).
- Integration: API payment apply to invoice, reconciliation flow.
- Coverage ≥70% theo TESTING-PATTERNS.

4. Definition of Done
- Payment entries tạo/allocate/cancel đúng, GL cân bằng.
- Bank reconciliation nhập sao kê, match được, lưu log.
- Unit + integration tests pass.
