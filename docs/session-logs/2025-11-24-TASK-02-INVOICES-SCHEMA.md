# Session Log - 2025-11-24 - TASK_02_INVOICES_SCHEMA

- Task: docs/tasks/MAIN_MODULES/07_TASK/TASK_02_INVOICES_SCHEMA.md (INV-001)
- Work done:
  - Added invoices schema migration + junction table (invoice_orders).
  - Created Invoice models, validator, repository, service, transformer, PDF stub, controller, routes, and service bindings.
  - Implemented invoice number generation, VAT calc, branch validation, duplicate-order guard, PDF snapshot.
  - Added schema trait, unit tests for InvoiceService, and API integration tests.
  - Adjusted phpunit integration configs to use /tmp logging and MySQL test DB at 127.0.0.1:3307.
- Tests run (host):
  - `vendor/bin/phpunit tests/Services/InvoiceServiceTest.php`
  - `vendor/bin/phpunit -c phpunit.integration.xml tests/Integration/Invoices/InvoiceApiTest.php`
- Notes: Tests isolate DB by dropping all tables in test DB before recreating minimal schema to avoid FK conflicts.
