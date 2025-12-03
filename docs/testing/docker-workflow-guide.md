# Docker Workflow Guide - LANO CRM

## 🚨 Vấn đề thường gặp

**SAI:** Chạy PHP trực tiếp trong WSL khi Docker containers đang chạy
```bash
# ❌ ĐỪNG LÀM ĐIỀU NÀY!
cd backend-ci && php spark serve
```

**ĐÚNG:** Luôn làm việc trong Docker containers
```bash
# ✅ LÀM ĐIỀU NÀY!
docker-compose up -d  # Khởi động containers
docker exec meomeo2-api-1 php spark serve  # Chạy commands trong container
```

## 📋 Quy trình làm việc đúng

- One-liner: `./scripts/dev-up.sh` (start api + db + phpmyadmin + fe)

### 1. Khởi động môi trường (API + DB + FE)
```bash
# Khởi động tất cả containers (api, db, phpmyadmin, fe)
docker-compose up -d

# Kiểm tra containers đang chạy
docker ps

# Xem logs của API container
docker logs meomeo2-api-1 -f
# Xem logs FE (Vite dev server)
docker logs meomeo2-fe-1 -f
```

### 1.1 Dữ liệu demo tự động
- Container DB sẽ import snapshot `db_lano` (hoặc chạy seeder demo) khi khởi động lần đầu.
- Nếu cần reset nhanh về trạng thái sạch + demo data:
```bash
docker-compose down -v      # xoá volume data (phá toàn bộ data dev)
docker-compose up -d        # DB tự import lại snapshot demo
# hoặc
docker exec meomeo2-web-1 php spark db:seed DemoSeeder
./scripts/reset-demo.sh     # script tiện dụng (tự down -v, up, seed)
```
- Seeder demo được auto-scan trong thư mục `App/Database/Seeds/Demo/` (xem docs/seeding/DEV-DEMO-SEEDER.md).
```

### 2. Chạy commands trong Docker

**Database commands:**
```bash
# Migration
docker exec meomeo2-api-1 php spark migrate

# Check migration status
docker exec meomeo2-api-1 php spark migrate:status

# Seed data
docker exec meomeo2-api-1 php spark db:seed
```

**Development server:**
```bash
# API đã chạy qua Apache trên port 8000, không cần spark serve
# Truy cập: http://localhost:8000

# FE Vite dev server (hot reload)
# Truy cập: http://localhost:3000
```

**Tests:**
```bash
# Unit tests
docker exec meomeo2-api-1 vendor/bin/phpunit

# Integration tests
docker exec meomeo2-api-1 vendor/bin/phpunit -c phpunit.integration.xml

# Specific test file
docker exec meomeo2-api-1 vendor/bin/phpunit tests/Services/ProductServiceTest.php

# FE unit tests (vitest)
docker exec meomeo2-fe-1 npm run test
```

**CodeIgniter commands:**
```bash
# List routes
docker exec meomeo2-api-1 php spark routes

# Generate key
docker exec meomeo2-api-1 php spark key:generate

# Clear cache
docker exec meomeo2-api-1 php spark cache:clear
```

### 3. Debugging trong Docker

**Kiểm tra container status:**
```bash
# Xem tất cả containers
docker ps -a

# Xem logs của container cụ thể
docker logs meomeo2-api-1
docker logs meomeo2-db-1
docker logs meomeo2-db-test-1
docker logs meomeo2-fe-1
```

**Truy cập vào container:**
```bash
# Shell vào API container
docker exec -it meomeo2-api-1 bash

# Shell vào database container
docker exec -it meomeo2-db-1 mysql -u lanocrm_user -p lanocrm_shop

# Shell vào FE container
docker exec -it meomeo2-fe-1 sh
```

**Kiểm tra database connection:**
```bash
# Test connection từ API container
docker exec meomeo2-api-1 php spark migrate:status
```

### 4. Troubleshooting

**Problem: "Connection refused" khi chạy tests**
```bash
# Kiểm tra test database container
docker ps | grep db-test

# Khởi động lại test database
docker-compose restart db-test

# Kiểm tra connection
docker exec meomeo2-api-1 php spark migrate:status --group=test
```

**Problem: API không phản hồi**
```bash
# Kiểm tra API container
docker logs meomeo2-api-1

# Restart API container
docker-compose restart api

# Kiểm tra port
curl http://localhost:8000
```

**Problem: Database connection errors**
```bash
# Kiểm tra database container
docker logs meomeo2-db-1

# Test connection manual
docker exec meomeo2-api-1 php spark migrate:status

# Restart database
docker-compose restart db
```

### 5. Development workflow

**Morning setup:**
```bash
# 1. Start containers
docker-compose up -d

# 2. Check status
docker ps

# 3. Run any pending migrations
docker exec meomeo2-api-1 php spark migrate

# 4. Access API at http://localhost:8000
# 5. Access phpMyAdmin at http://localhost:8080
```

**During development:**
```bash
# Edit files in VSCode (local)
# Changes automatically sync to container via volume

# Run tests
docker exec meomeo2-api-1 vendor/bin/phpunit

# Check API
curl http://localhost:8000/api/products
```

**End of day:**
```bash
# Stop containers (optional)
docker-compose down

# Or keep running for next day
# Containers use minimal resources when idle
```

## 🔧 Cấu hình quan trọng

### Ports
- **API**: http://localhost:8000
- **phpMyAdmin**: http://localhost:8080
- **Database (dev)**: localhost:3306 (internal)
- **Database (test)**: localhost:3307 (internal)

### Environment files
- **Development**: `backend-ci/.env`
- **Database config**: Xem trong `docker-compose.yml`

### Volumes
- Code: `./backend-ci:/var/www/html` (auto-sync)
- Database data: `db_data` và `test_db_data` (persistent)

## 🛠 Lộ trình chuyển sang Docker-only (tóm tắt)

- **Bắt buộc dùng Docker CLI**: Tạo alias trong shell `php/composer/spark` → `docker exec meomeo2-api-1 ...` hoặc dùng script `backend-ci/docker-*` để tránh chạy PHP cục bộ.
- **Gỡ PHP/Composer cài trong host cho dự án này** (nếu không dùng cho dự án khác) để hết nhầm lẫn môi trường.
- **Dọn prefix test trong model**: đảm bảo các model không còn `db_` ở `$table` (OrderModel, OrderItemModel, WebhookEventModel, WebhookSubscriptionModel, v.v.).
- **Onboarding nhanh**: `docker-compose up -d` → `docker exec meomeo2-api-1 php spark migrate` → chạy test `vendor/bin/phpunit` trong container.
- **Script hoá**: thêm/cấp quyền thực thi `backend-ci/docker-spark`, `docker-php`, `docker-composer` để mọi lệnh PHP thống nhất qua container.

## 🚫 Đừng bao giờ

1. **Chạy `php spark serve` trong WSL** khi Docker đang chạy
2. **Cài PHP/MySQL trong WSL** cho project này
3. **Chạy tests trực tiếp trong WSL** - sẽ lỗi connection
4. **Edit code trong container** - edit trong VSCode local
5. **Chạy `DROP DATABASE`/`DROP TABLE`** để dọn data (kể cả DB test) - dùng transaction rollback + `TRUNCATE`/schema reset thay thế

## ✅ Luôn luôn

1. **Làm việc qua Docker containers**
2. **Kiểm tra `docker ps` trước khi chạy commands**
3. **Dùng `docker exec` cho tất cả PHP commands**
4. **Xem logs khi có vấn đề**: `docker logs <container-name>`

## 📞 Quick reference

```bash
# Start everything
docker-compose up -d

# Check status
docker ps

# Run tests
docker exec meomeo2-api-1 vendor/bin/phpunit

# Migration
docker exec meomeo2-api-1 php spark migrate

# View logs
docker logs meomeo2-api-1 -f

# Stop everything
docker-compose down
```

---

**Remember:** Docker là môi trường chính của project. WSL chỉ để edit code và chạy Docker commands!
