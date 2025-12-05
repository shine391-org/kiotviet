ERP-021 - Accounting Core: Chart of Accounts & GL Entry
Bạn là AI backend engineer phụ trách nền tảng kế toán (COA + GL) theo ERPNext.

1. Bối cảnh
- ERPNext có COA, GL Entry cho mọi giao dịch. LanoCRM chưa có sổ cái.

2. Phạm vi & Deliverables
- Migration: chart_of_accounts (hierarchy, account_type, currency), gl_entries (posting_date, account, debit, credit, party_type/id, reference_type/id, remarks), cost_center optional.
- Validator: COAValidator, GLEntryValidator.
- Repository: COARepository, GLEntryRepository.
- Service: AccountingService (post entries, balance check), COAService (CRUD with constraints), helper interfaces for modules (POS Payment, Invoice, Reconciliation) to post GL.
- Controller API: manage COA, query GL (read-only), post manual journal (optional if needed).

3. Testing (DevDatabaseTrait)
- Unit: COAServiceTest (hierarchy rules), AccountingServiceTest (post balanced entries, reject unbalanced), GLEntryRepositoryTest (query by account/party/date).
- Integration: API post journal entry (if exposed), ensure balanced; GL reflects POS payment hook stub.
- Coverage ≥70% theo TESTING-PATTERNS.

4. Definition of Done
- COA + GL posting nền tảng hoạt động, kiểm tra cân bằng, sẵn sàng nhận entry từ POS/Invoice modules.
- Unit + integration tests pass.
