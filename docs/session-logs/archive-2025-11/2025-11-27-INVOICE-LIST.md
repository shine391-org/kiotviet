# Session Log - FE Invoice List

- Added invoice domain scaffolding (constants, API client, Redux slice) and registered reducer.
- Built Invoice List page with search, filter sidebar, column picker, export CSV, wide table, detail + payment history tabs; wired routes `/invoices` and `/orders/invoices`.
- Created reusable Invoice filters/table/detail components styled for KiotViet-like layout.
- Tests: `npm test -- invoiceSlice.test.js InvoiceListPage.test.jsx` (vitest). Known warnings from Ant Design about deprecated `bodyStyle` in Card during tests; no functional impact.
- Next: hook real API endpoints for invoices and populate dropdown options (creators/sellers/price books/regions) when backend ready.
