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
