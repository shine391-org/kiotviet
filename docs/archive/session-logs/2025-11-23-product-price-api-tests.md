# 2025-11-23 - Product price calculation API tests

## What I did
- Thêm tính năng áp dụng `price_list_id` vào API sản phẩm (GET detail & list) và truyền xuống service tính giá.
- Bổ sung pricing cho sản phẩm/phiên bản qua `PriceCalculatorService::getProductPriceByListId`.
- Viết integration tests (MySQL) cho luồng lấy giá sản phẩm với bảng giá, danh sách sản phẩm, và trường hợp bảng giá không tồn tại.
- Cập nhật cấu hình phpunit integration để chạy toàn bộ thư mục `tests/Integration`.

## Files touched
- backend-ci/app/Services/PriceLists/PriceCalculatorService.php
- backend-ci/app/Services/Products/ProductService.php
- backend-ci/app/Validators/ProductValidator.php
- backend-ci/app/Controllers/Api/ProductsController.php
- backend-ci/app/Config/Services.php
- backend-ci/phpunit.integration.mysql.xml
- backend-ci/tests/Integration/Products/ProductPriceCalculationApiTest.php

## Tests
- `docker exec meomeo2-api-1 vendor/bin/phpunit --testsuite App`
- `docker exec meomeo2-api-1 vendor/bin/phpunit -c phpunit.integration.mysql.xml --filter ProductPriceCalculationApiTest`
