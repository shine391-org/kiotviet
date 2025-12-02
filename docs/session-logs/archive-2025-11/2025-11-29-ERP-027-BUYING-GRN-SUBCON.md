# Session Log - ERP-027 Buying GRN & Subcontracting

- **Date:** 2025-11-29
- **Task:** ERP-027 - Mua hàng: PO, GRN, Landed Cost, Subcontracting

## What I did
- Extended golden migration + DevDatabaseTrait with purchase_order_items, goods_receipts (+ items), landed_cost_vouchers (+ items), subcontracting_orders (+ materials); added models for all.
- Added validators/repos/services for purchase orders (draft→submit), goods receipts (update PO received qty + stock ledger), landed cost vouchers (cost allocations), and subcontracting (materials issued, receipt).
- Added thin controllers/routes for PO create/submit/cancel/show, GRN create/show, landed cost create/show, subcontracting create/issue/receive; wired services in Services config.
- Added unit tests for PO/GRN/LCV/Subcontracting and integration test for PO→GRN→LCV flow.

## Tests
- `docker exec meomeo2-api-1 vendor/bin/phpunit tests/Services/PurchaseOrderServiceTest.php tests/Services/GoodsReceiptServiceTest.php tests/Services/LandedCostServiceTest.php tests/Services/SubcontractingServiceTest.php tests/Integration/Api/PurchaseFlowApiTest.php`

## Notes / Issues
- Subcontracting material issue currently skips negative stock adjustment to avoid bin underflow; adjust when stock availability tracking is added.
