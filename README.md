# KiotViet React + CI4 (nâng cấp)

## Chạy nhanh (dev)
```
docker compose up -d db api
# FE
cd lanocrm && npm install && npm run dev
```
API: http://localhost:8000/api, FE: http://localhost:5173

## Tài khoản mặc định
- Username: `admin`
- Password: `123aA@hai`
- API login: `POST http://localhost:8000/api/auth/login`
- Legacy FE build (proxy nginx): `POST http://localhost:3000/backend-ci/api/users/login`

## Seed dữ liệu mẫu
```
docker exec -it meomeo2-api-1 php spark db:seed DevSeeder
```
Tạo sẵn: users (devadmin/Admin@123, manager1, viewer1), branches, categories, attributes/options, sản phẩm demo + biến thể/ảnh.

## Test
- Route coverage (SQLite in-memory):
```
docker exec meomeo2-api-1 vendor/bin/phpunit --filter ApiRoutesTest
```
- Xdebug bật coverage trong container; cảnh báo coverage đã hết.

## Uploads
- Symlink `backend-ci/public/uploads -> ../writable/uploads` để serve file upload.

## Lưu ý
- `docker-compose.yml` vẫn warn về field `version` (có thể bỏ nếu muốn sạch log).
- Auth hiện mức dev (login devadmin); cần hoàn thiện JWT/permissions khi lên prod.
