# Session Log - ERP-022 Sales Invoice & Tax

- **Date:** 2025-11-29
- **Task:** ERP-022 - Accounting Sales Invoice & Tax Template

## What I did
- Added sales invoice schema (sales_invoices, sales_invoice_items, sales_invoice_taxes, payment_schedules) and cleanup hooks in golden migration + DevDatabaseTrait.
- Introduced models, validators, repositories, and services for sales invoices (preview/create/submit/cancel) with totals, tax calculation, payment schedule validation, numbering helper, and GL posting/reversal via AccountingService.
- Exposed thin controllers/routes for preview/create/submit/cancel and GL/COA dependencies wired in Services config.
- Added tests: SalesInvoiceService (totals, GL post/cancel, schedule validation), SalesInvoice API integration, reused accounting seeds for COA.

## Tests
- `docker exec meomeo2-api-1 vendor/bin/phpunit tests/Services/SalesInvoiceServiceTest.php tests/Integration/Api/SalesInvoicesApiTest.php`

## Notes / Issues
- GL reversal implemented by posting opposite debit/credit entries; payment schedule generation defaults to single installment when not provided.
