# Session log 2025-11-21 - REFACTOR-001-ProductService

## Đã làm
- Tạo ProductValidator, ProductRepository, ProductService theo clean architecture.
- Refactor ProductsController mỏng, chỉ routing & xử lý lỗi chung.
- Thêm unit test ProductService (list/create/update/delete/validation).

## Files
- backend-ci/app/Validators/ProductValidator.php
- backend-ci/app/Repositories/Products/ProductRepository.php
- backend-ci/app/Services/Products/ProductService.php
- backend-ci/app/Controllers/Api/ProductsController.php
- backend-ci/app/Config/Services.php
- backend-ci/tests/Services/ProductServiceTest.php

## Tests
- Định nghĩa mới: Unit (ProductServiceTest), Integration (ProductRepositoryTest), API E2E (ProductsApiE2ETest)
- Đã chạy trong container: `vendor/bin/phpunit --testsuite App --testdox` (24/24 pass, cảnh báo thiếu coverage driver).

## Issues
- Composer/phpunit đã cài trong container; test backend pass. Frontend build cũ cần proxy `/backend-ci/api` (đã thêm `fe-proxy.conf`).

## Cập nhật 2025-11-21 (login redirect + FE)
- Sửa `lanocrm/src/pages/Login.jsx` bỏ flag chặn redirect, tự điều hướng về `/dashboard` khi `isAuthenticated` true (kể cả đã có token sẵn).
- Rebuild frontend, cập nhật `dist/index.html` trỏ tới bundle mới `assets/index-BBPQxz0b.js`, xóa bundle cũ `assets/index-AKne4l0A.js`.
- Kiểm tra lại quyền hiển thị nút sửa sản phẩm cha: tests React đã cover cả biến thể có/không biến thể.

### Tests chạy
- Frontend: `npm test` (Vitest) ✅
  - `authApi.int.test.js` đăng nhập devadmin thành công, lưu token vào localStorage.
  - `App.test.jsx` kiểm tra state khởi tạo authSlice.
  - `ProductTable.test.jsx` đảm bảo nút sửa hiển thị đúng cho sản phẩm có/không biến thể.
