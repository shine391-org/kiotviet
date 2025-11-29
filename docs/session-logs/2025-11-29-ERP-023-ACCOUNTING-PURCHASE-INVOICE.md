# Session Log - ERP-023 Purchase Invoice

- **Date:** 2025-11-29
- **Task:** ERP-023 - Purchase Invoice & Tax

## What I did
- Added purchase invoice schema (purchase_invoices, items, taxes) to golden migration and DevDatabaseTrait cleanup.
- Introduced models, validator, repository, and service for purchase invoices (preview/create/submit/cancel) with tax calculation, totals, numbering, and GL posting/reversal via AccountingService.
- Added thin controller/routes for preview/create/submit/cancel; wired service providers.
- Added unit test for PurchaseInvoiceService and API integration test; reused COA seeds for GL accounts.

## Tests
- `docker exec meomeo2-api-1 vendor/bin/phpunit tests/Services/PurchaseInvoiceServiceTest.php tests/Integration/Api/PurchaseInvoicesApiTest.php`

## Notes / Issues
- Supplier_id required but supplier master not yet modeled; currently accepts numeric ID as provided.
