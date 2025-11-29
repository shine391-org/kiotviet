ERP-035 - Báo cáo Tài chính & Tồn kho chuẩn
Bạn là AI backend engineer phụ trách các báo cáo chuẩn như ERPNext.

1. Bối cảnh
- ERPNext cung cấp nhiều báo cáo: GL, P&L, Balance Sheet, AR/AP Aging, Stock Balance, Stock Ledger. LanoCRM chưa đủ.

2. Phạm vi & Deliverables
- Không cần migration lớn (tận dụng GL/ledger/bin), có thể thêm bảng cache/report_runs nếu cần.
- Service: ReportService bundle (GL report, P&L, Balance Sheet, AR/AP Aging sử dụng GL, Stock Balance/Stock Ledger từ ledger/bin), filter theo thời gian, company, warehouse, currency.
- Controller API: endpoints read-only cho từng báo cáo, nhận filter và paging.
- Integration: Dùng CurrencyService cho multi-currency, Company permission filter.

3. Testing (DevDatabaseTrait)
- Unit: từng report service test với sample GL/ledger data (balance calc, filters, currency conversion), performance guard (limit/paging).
- Integration: API call trả dữ liệu đúng với filter, respect company scope.
- Coverage ≥70% theo TESTING-PATTERNS.

4. Definition of Done
- Các báo cáo GL/P&L/Balance Sheet/AR-AP Aging/Stock Balance hoạt động, filter/permission đúng.
- Unit + integration tests pass.
