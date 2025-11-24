# 2025-11-23 - Price Lists

## Tasks completed
- PRICE-001: Price list tables, CRUD API, items bulk upsert.
- PRICE-002: Price calculator + order preview/apply pricing, minimal order persistence.
- UI: Thêm bộ lọc gọn (nhóm hàng/tồn kho/giá bán) cho trang Bảng giá; đảm bảo menu “Bảng giá” hiển thị theo quyền (admin hoặc price_lists.view/products.view).
- E2E: Playwright live tests (admin login → điều hướng Bảng giá; tạo bảng giá/áp dụng giá thật; screenshot cập nhật).
- Integration MySQL: 5 test case apply price list cho order (discount %, discount fixed + edge 0, recalc khi đổi list, priority conflict, date range).

## Files touched (mới)
- Backend tests: `tests/Integration/Orders/OrderPriceListTest.php` (MySQL), `phpunit.integration.mysql.xml`.
- Backend support: `tests/_support/Database/PriceListSchemaTrait.php` (schema reset tweaks), `app/Repositories/PriceLists/PriceListRepository.php` (hydrate apply_to_groups an toàn).
- Frontend: `src/pages/price-lists/PriceListPage.jsx` (filter panel), `src/components/Layout/Sidebar.jsx`, `src/components/Layout/TopMenu/TopMenu.jsx`.
- E2E: `tests/e2e/price-lists-live.spec.ts`, `tests/e2e/price-lists-nav-login.spec.ts`.
- Assets: `screenshots/price-lists-menu-latest.png`, `screenshots/price-lists-filters.png`.

## Testing
- Backend integration (MySQL): `phpunit -c phpunit.integration.mysql.xml` ✅ (5 tests, 15 assertions).
- Backend unit/feature (SQLite): `phpunit` ✅ (128 tests, 351 assertions).
- Frontend E2E (Playwright, live API): `npx playwright test tests/e2e/price-lists-live.spec.ts` và `tests/e2e/price-lists-nav-login.spec.ts` ✅.

## Notes / Issues
- Activity logging vẫn chưa làm.
- MySQL DB test đã có sẵn data; chỉ tạo/clear price_lists, price_list_items trong test.***
