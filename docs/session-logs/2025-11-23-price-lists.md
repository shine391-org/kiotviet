# 2025-11-23 - Price Lists

## Tasks completed
- PRICE-001: Price list tables, CRUD API, items bulk upsert.
- PRICE-002: Price calculator + order preview/apply pricing, minimal order persistence.

## Files touched
- Backend: migrations `2025-11-23-000004_CreatePriceListTables.php`, `2025-11-23-000005_CreateOrderTables.php`; models, repositories, validators, services, controllers, routes, services registration.
- Frontend: API client `priceListApi.js`, Redux slice `priceListSlice.js`, pages `PriceListPage.jsx`, `PriceListFormPage.jsx`, routing + sidebar wiring, tests for API/slice.
- Docs: checklist in `docs/tasks/new-modules/Price-list.md` updated.

## Testing
- Frontend: `npm test` (vitest) ✅ passed.
- Backend: phpunit not run (php CLI missing in environment). Pricing-related unit tests added (`backend-ci/tests/Services/*Price*Test.php`) ready for execution.

## Notes / Issues
- Activity logging not implemented; awaiting logger facility.
- Navigation entry added under Hàng hóa when user has products.view permission.
