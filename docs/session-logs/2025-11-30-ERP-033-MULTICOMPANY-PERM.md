# Session Log - ERP-033 Multi-company & Permissions

- **Date:** 2025-11-30
- **Task:** ERP-033 - Đa công ty & Phân quyền tài liệu

## What I did
- Thêm migration mới + golden schema (companies, company_permissions, document_shares, audit_logs) và cập nhật DevDatabaseTrait.
- Tạo model/repository/validator/service cho company, permission, sharing, audit; bổ sung controllers + routes: companies, shares, documents (ACL), audit logs.
- Viết unit tests cho PermissionService, SharingService, AuditService; integration test cross-company access + share + audit logging.

## Tests
- `docker exec meomeo2-api-1 vendor/bin/phpunit tests/Services/PermissionServiceTest.php tests/Services/SharingServiceTest.php tests/Services/AuditServiceTest.php`
- `docker exec meomeo2-api-1 vendor/bin/phpunit -c phpunit.integration.xml tests/Integration/Api/CompanyPermissionApiTest.php`

## Notes / Issues
- Response từ FeatureTestTrait trả về JSON bọc HTML; dùng `json_decode($response->getJSON())` để lấy payload.
