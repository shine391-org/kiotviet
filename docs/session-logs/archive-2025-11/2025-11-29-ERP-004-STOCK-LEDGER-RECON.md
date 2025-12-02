# Session Log - 2025-11-29 - ERP-004 Stock Ledger & Reconciliation

## Work
- Thêm schema ledger/bin/reconciliation: stock_ledgers, stock_bins, stock_reconciliations, stock_reconciliation_items; cập nhật golden migration + DevDatabaseTrait.
- Implement clean layers: models, validators, repos, services (StockLedgerService với bin locking/idempotent ref, StockReconciliationService draft→submitted→approved/rejected, variance posting), bin repo lock/adjust, controllers/routes, services registry.
- Gắn ledger vào batch adjust và delivery note deduction để ghi sổ + bin song song.

## Files
- Schema: backend-ci/app/Database/Migrations/2025-11-29-001004_CreateStockLedgerTables.php, cập nhật 2025-11-27-000999_TestSchemaSetup.php, DevDatabaseTrait.
- Logic: backend-ci/app/Services/Inventory/StockLedgerService.php, StockReconciliationService.php; repositories/validators/models; controller StockReconciliationsController; routes/services config; adjustments in DeliveryNoteService, ProductBatchService.
- Tests: backend-ci/tests/Repositories/StockBinRepositoryTest.php, Services/StockLedgerServiceTest.php, Services/StockReconciliationServiceTest.php, Integration/Api/StockReconciliationsApiTest.php.
- Task: docs/tasks/ERPNext/ERP-004-STOCK-LEDGER-RECON.md.

## Tests (container)
- docker exec meomeo2-api-1 vendor/bin/phpunit tests/Repositories/StockBinRepositoryTest.php
- docker exec meomeo2-api-1 vendor/bin/phpunit tests/Services/StockLedgerServiceTest.php
- docker exec meomeo2-api-1 vendor/bin/phpunit tests/Services/StockReconciliationServiceTest.php
- docker exec meomeo2-api-1 vendor/bin/phpunit tests/Integration/Api/StockReconciliationsApiTest.php

## Notes
- Ledger record idempotent by reference_type/id/seq; bin locking uses SELECT FOR UPDATE to tránh oversell.
