# 🚀 PHPUnit Quick Start - LANO CRM

## 📋 Tóm Tắt Thay Đổi (2025-11-25)

**Chúng ta đã đơn giản hóa testing!**
- ❌ **Trước**: 2 database (db + db-test) → Phức tạp
- ✅ **Hiện**: 1 database chính (lanocrm_shop) + transaction rollback → Đơn giản

## 🎯 Lợi Ích

1. **Đơn giản** - Team dev chỉ quản lý 1 database
2. **An toàn** - Transaction rollback tự động bảo vệ data
3. **Nhanh** - Không cần cleanup manual
4. **Thực tế** - Test trên data thật, schema thật

## 🛠️ Cách Chạy Test

### 1. Khởi động containers
```bash
docker-compose up -d db api
```

### 2. Chạy migration (lần đầu tiên)
```bash
docker exec meomeo2-api-1 php spark migrate
```

### 3. Chạy tests
```bash
# Unit tests (nhanh nhất)
docker exec meomeo2-api-1 vendor/bin/phpunit

# Integration tests (API endpoints)
docker exec meomeo2-api-1 vendor/bin/phpunit -c phpunit.integration.xml

# Test với coverage report
docker exec meomeo2-api-1 vendor/bin/phpunit --coverage-text

# Chạy test cụ thể
docker exec meomeo2-api-1 vendor/bin/phpunit tests/Services/OrderServiceTest.php
```

## 🔍 Kiểm Tra Kết Quả

### Test thành công:
```
✓ 5/5 test cases passed
✓ Coverage: 85%
```

### Test thất bại:
```
✗ 1/5 test cases failed
- Check error messages
- Fix issues
- Run again
```

## 🚨 Vấn Đề Thường Gặp

### **"Connection refused"**
```bash
# Fix: Khởi động lại containers
docker-compose down
docker-compose up -d db api
```

### **"Table doesn't exist"**
```bash
# Fix: Chạy migration
docker exec meomeo2-api-1 php spark migrate
```

### **"Database error"**
```bash
# Fix: Kiểm tra database status
docker exec meomeo2-api-1 php spark db:info
```

## 📝 Viết Test Mới

### Pattern chuẩn:
```php
<?php
namespace Tests\Services;

use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\YourSchemaTrait;

class YourServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;      // Bắt buộc
    use YourSchemaTrait;       // Cho schema

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();     // Bắt buộc
        $this->resetYourSchema();   // Bắt buộc
    }
    
    protected function tearDown(): void
    {
        $this->tearDownDatabase();  // Bắt buộc
        parent::tearDown();
    }

    /** @test */
    public function it_works()
    {
        // Test code here
        $this->assertTrue(true);
    }
}
```

## 🎯 Quy Tắc Vàng

1. ✅ **LUÔN** dùng `DevDatabaseTrait`
2. ✅ **LUÔN** gọi `setUpDatabase()` và `tearDownDatabase()`
3. ✅ **LUÔN** dùng Schema Trait cho table creation
4. ❌ **KHÔNG** query data ngoài transaction
5. ❌ **KHÔNG** dùng SQLite patterns cũ

## 📊 Commands Reference

```bash
# Basic commands
docker exec meomeo2-api-1 vendor/bin/phpunit
docker exec meomeo2-api-1 vendor/bin/phpunit -c phpunit.integration.xml

# Specific tests
docker exec meomeo2-api-1 vendor/bin/phpunit tests/Services/
docker exec meomeo2-api-1 vendor/bin/phpunit tests/Integration/

# Coverage
docker exec meomeo2-api-1 vendor/bin/phpunit --coverage-text
docker exec meomeo2-api-1 vendor/bin/phpunit --coverage-html coverage

# Database
docker exec meomeo2-api-1 php spark migrate
docker exec meomeo2-api-1 php spark db:info
docker exec meomeo2-api-1 php spark migrate:status
```

## 🔗 Documentation

- 📖 **Guide chi tiết**: `docs/testing/TESTING-MAIN-DB-GUIDE.md`
- 🧩 **Patterns**: `docs/testing/TESTING-PATTERNS.md`
- 📋 **Session log**: `docs/session-logs/2025-11-25-PHPUNIT-TESTING-FIXES.md`

## 🎉 Ready to Go!

**Testing đã được đơn giản hóa và sẵn sàng sử dụng!**

1. Chạy migration 1 lần
2. Test tự động rollback data
3. Không lo về data corruption
4. Focus vào writing tests, không setup complexity

---

**Need help?** Check `docs/testing/TESTING-MAIN-DB-GUIDE.md` for detailed guide.