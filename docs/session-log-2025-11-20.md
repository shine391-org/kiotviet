# Session log 2025-11-20

## Đã làm
- Nâng backend lên CI4, PHP 8.4, MySQL 8.4; mở đầy đủ routes FE (products, variants, images, media, attributes, users/roles/branches...).
- Bổ sung seed DevSeeder + ProductSeeder: user/role/permission/branch/categories/attributes/options + sản phẩm demo (biến thể, ảnh, attribute values).
- CORS bật, symlink `public/uploads` phục vụ file upload.
- Thêm test `ApiRoutesTest` (so khớp routes), chạy trên SQLite in-memory; bật Xdebug coverage.
- Cài Xdebug trong container, cấu hình `xdebug.mode=coverage`.

## Còn tồn đọng / follow-up
- Auth còn ở mức dev (devadmin static); cần JWT/permission thực khi lên prod.
- Chưa bổ sung migration/seed đầy đủ cho toàn bộ data thật; seed hiện chỉ dataset mẫu.
- Chưa có CI pipeline (lint/build/test) và chưa thêm ESLint/Prettier FE.
- `docker-compose.yml` cảnh báo field `version` obsolete (có thể dọn).

## Lệnh hữu ích
- Seed: `docker exec -it meomeo2-api-1 php spark db:seed DevSeeder`
- Test routes: `docker exec meomeo2-api-1 vendor/bin/phpunit --filter ApiRoutesTest`
- Build/restart API: `docker compose build api && docker compose up -d api`
