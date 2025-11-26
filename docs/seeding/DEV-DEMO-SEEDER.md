---
title: "Dev Demo Seeder - LANO CRM"
id: "DEV-DEMO-SEEDER-01"
version: "1.0"
status: "Active"
module: "Seeding"
type: "Guide"
tags: ["seeding", "demo-data", "docker-only", "mysql"]
purpose: "Chuẩn hoá cách nạp dữ liệu demo cho môi trường dev để FE thấy dữ liệu ngay, không ảnh hưởng test."
location: "docs/seeding"
related_to:
  - id: "TESTING-GUIDE-01"
    description: "MySQL-only testing (main DB + rollback)."
  - id: "AGENT-GUIDE-01"
    description: "Agent guide – seeding demo for dev."
---

# Dev Demo Seeder

## Mục tiêu
- Dev/FE luôn có sẵn dữ liệu demo ngay sau `docker-compose up -d`.
- Không cần prod/staging; chỉ dùng DB dev cục bộ.
- Tests vẫn sạch: dùng `DevDatabaseTrait` + transaction, tự seed trong test.

## Cấu trúc
- Thư mục seeders demo: `backend-ci/app/Database/Seeds/Demo/`
- Entry point: `DevDemoSeeder` (gọi tự động tất cả seeder trong thư mục Demo).
- `DevDemoSeeder` sẽ gọi thêm `DevSeeder` (roles/users/master data) nếu có.
- Snapshot DB: `db_lano` (hoặc `db_lano.gz`) được import khi DB container khởi động lần đầu.

## Cách thêm dữ liệu demo mới
1. Tạo file seeder mới trong `app/Database/Seeds/Demo/` (ví dụ `OrdersDemoSeeder.php`).
2. Seeder phải **idempotent**: truncate/REPLACE hoặc kiểm tra tồn tại trước khi insert.
3. Đặt namespace chuẩn `App\Database\Seeds\Demo` và class tên trùng file.
4. DevDemoSeeder sẽ auto-scan và chạy toàn bộ seeder trong thư mục Demo, không cần sửa code.

## Auto-run khi khởi động container
- `docker-compose up -d` với file compose hiện tại sẽ chạy `php spark migrate --all && php spark db:seed DevDemoSeeder` trong container API.
- Nếu volume đã tồn tại, seed vẫn chạy (nên idempotent).

## Chạy seeder (dev)
```bash
docker exec meomeo2-api-1 php spark db:seed DevDemoSeeder
```

## Reset nhanh về demo data
```bash
docker-compose down -v   # xoá volume → mất toàn bộ data dev
docker-compose up -d     # DB tự import snapshot + có thể chạy lại DevDemoSeeder
```

## Lưu ý
- Chỉ chạy ở môi trường **development**.
- Test group (`tests`) không dùng demo data; luôn rollback.
- Không còn prod nên không cần tách seed prod/dev, nhưng vẫn giữ idempotent để dễ reset.
