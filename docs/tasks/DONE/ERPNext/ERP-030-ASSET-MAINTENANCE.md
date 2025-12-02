ERP-030 - Tài sản & Bảo trì
Bạn là AI backend engineer phụ trách Asset/Maintenance như ERPNext.

1. Bối cảnh
- ERPNext có Asset, Depreciation, Maintenance Schedule/Work Order. LanoCRM chưa có.

2. Phạm vi & Deliverables
- Migration: assets (asset_number, category, purchase_date, cost, location, status), depreciation_schedules (method, rate, schedule lines), maintenance_schedules, maintenance_work_orders.
- Validator: AssetValidator, DepreciationValidator, MaintenanceValidator.
- Repository: AssetRepository, DepreciationScheduleRepository, MaintenanceRepository.
- Service: AssetService (create, capitalize, dispose, change status), DepreciationService (generate schedule, post GL), MaintenanceService (schedule/create WO, complete), numbering helper.
- Controller API: manage assets, run depreciation, schedule/complete maintenance WO.
- Integration: GL posting via AccountingService; StockEntry for maintenance parts consumption; ApprovalService optional for disposal.

3. Testing (DevDatabaseTrait)
- Unit: AssetServiceTest (lifecycle), DepreciationServiceTest (schedule/GL), MaintenanceServiceTest (WO lifecycle), repo tests.
- Integration: API create asset, run depreciation posting, maintenance WO consuming parts.
- Coverage ≥70% theo TESTING-PATTERNS.

4. Definition of Done
- Asset lifecycle + khấu hao + bảo trì hoạt động; GL và stock hooks sẵn sàng.
- Unit + integration tests pass.
