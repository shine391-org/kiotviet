# Session Log - ERP Backend Integration Gaps

- **Date:** 2025-11-30
- **Scope:** Bổ sung integration tests còn thiếu (Stock Reconciliation, Subcontracting, Withholding)

## What I did
- Thêm `StockReconciliationApiTest` (create → submit → approve, kiểm tra bin on_hand cập nhật).
- Thêm `SubcontractingApiTest` (create → issue → receive, kiểm tra thành phẩm nhập kho).
- Thêm `WithholdingRulesApiTest` (tạo rule, apply theo threshold/rate).

## Tests
- `docker exec meomeo2-api-1 vendor/bin/phpunit -c phpunit.integration.xml tests/Integration/Api/StockReconciliationApiTest.php tests/Integration/Api/SubcontractingApiTest.php tests/Integration/Api/WithholdingRulesApiTest.php`

## Notes / Issues
- Stock recon test seed sẵn bin để tránh thiếu số dư, dùng DevDatabaseTrait rollback an toàn. Subcontracting test không cần supplier thực, chỉ check status và bin thành phẩm.
