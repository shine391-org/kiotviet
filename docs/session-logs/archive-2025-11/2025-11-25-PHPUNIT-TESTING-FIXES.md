---
title: "PHPUnit Testing Fixes - Main Database Migration"
id: "PHPUNIT-FIXES-2025-11-25"
purpose: "Record of PHPUnit testing fixes and migration to main database approach"
version: "1.0"
status: "Completed"
date: "2025-11-25"
tags: ["phpunit", "testing", "mysql", "database", "migration", "fixes"]
---

# PHPUnit Testing Fixes - Main Database Migration

## 🎯 Mục Tiêu

Sửa lỗi PHPUnit và đơn giản hóa testing bằng cách chuyển từ 2 database (db + db-test) sang dùng database chính (`lanocrm_shop`) với transaction rollback.

## 🔍 Vấn Đề Đã Xác Định

### 1. **Vấn đề kết nối database**
- Có 2 MySQL containers: `db` (port 3306) và `db-test` (port 3307)
- Config test kết nối đến `db-test` nhưng gây khó hiểu cho team dev
- Team dev bị rối vì phải maintain 2 database

### 2. **Lỗi trong DevDatabaseTrait**
```php
// Lỗi logic trong method isDatabaseConnected()
return $this->db && !$this->db->connID === false;  // SAI
// Đúng phải là:
return $this->db && $this->db->connID !== false;   // ĐÚNG
```

### 3. **OrderServiceTest lỗi**
- Gọi method `forceFreshMigrate()` không tồn tại
- Thiếu bảng `order_sequences` trong schema
- Không dùng đúng Schema Trait

### 4. **InventoryMovementLoggerTest lỗi**
- Query table `inventory_movements` nhưng schema tạo `db_inventory_movements`
- Không nhất quán về prefix

### 5. **Thiếu migration**
- Bảng `order_sequences` cần cho OrderServiceTest nhưng không có trong migration

## 🛠️ Giải Pháp Đã Thực Hiện

### 1. **Tạo migration thiếu**
**File:** `backend-ci/app/Database/Migrations/2025-11-25-000015_CreateOrderSequences.php`
```php
public function up()
{
    $this->forge->addField([
        'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
        'branch_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
        'sequence_number' => ['type' => 'INT', 'constraint' => 10, 'default' => 1],
        'prefix' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'ORD'],
        'created_at' => ['type' => 'DATETIME', 'null' => true],
        'updated_at' => ['type' => 'DATETIME', 'null' => true],
    ]);
    $this->forge->addKey('id', true);
    $this->forge->addUniqueKey(['branch_id', 'prefix'], 'uk_branch_prefix');
    $this->forge->createTable('order_sequences', true);
}
```

### 2. **Sửa lỗi DevDatabaseTrait**
**File:** `backend-ci/tests/_support/Database/DevDatabaseTrait.php`
```php
// Sửa lỗi logic trong isDatabaseConnected()
protected function isDatabaseConnected(): bool
{
    return $this->db && $this->db->connID !== false;  // Đã sửa
}
```

### 3. **Cập nhật config Database.php**
**File:** `backend-ci/app/Config/Database.php`
```php
// Using main database for testing with transaction rollback
$this->tests['hostname'] = env('database.tests.hostname', 'db');           // Thay từ 'db-test'
$this->tests['database'] = env('database.tests.database', 'lanocrm_shop'); // Thay từ 'lanocrm_test'
$this->tests['DBPrefix'] = env('database.tests.DBPrefix', '');              // Thay từ 'db_'
```

### 4. **Cập nhật PHPUnit config**
**Files:** `backend-ci/phpunit.xml.dist` và `backend-ci/phpunit.integration.xml`
```xml
<!-- Using main database with transaction rollback for data protection -->
<env name="database.tests.hostname" value="db"/>
<env name="database.tests.database" value="lanocrm_shop"/>
<env name="database.tests.DBPrefix" value=""/>
```

### 5. **Sửa OrderServiceTest**
**File:** `backend-ci/tests/Services/OrderServiceTest.php`
```php
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\PriceListSchemaTrait;  // Thêm

class OrderServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use PriceListSchemaTrait;  // Thêm

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        
        // Use PriceListSchemaTrait cho comprehensive schema
        $this->resetPriceListSchema();  // Thay thế forceFreshMigrate()
        
        $this->seedOrderSequences();
        $this->service = new OrderService();
    }
}
```

### 6. **Cập nhật PriceListSchemaTrait**
**File:** `backend-ci/tests/_support/Database/PriceListSchemaTrait.php`
```php
// Thêm method createOrderSequenceTables()
private function createOrderSequenceTables(): void
{
    $this->db->query("CREATE TABLE order_sequences (
        id INT AUTO_INCREMENT PRIMARY KEY,
        branch_id INT,
        sequence_number INT DEFAULT 1,
        prefix VARCHAR(20) DEFAULT 'ORD',
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
```

### 7. **Sửa InventoryMovementLoggerTest**
**File:** `backend-ci/tests/Services/InventoryMovementLoggerTest.php`
```php
// Chuẩn hóa prefix table
$stored = $this->db->table('db_inventory_movements')->where('id', $row['id'])->get()->getRowArray();
```

### 8. **Tạo documentation mới**
**File:** `docs/testing/TESTING-MAIN-DB-GUIDE.md`
- Hướng dẫn chạy test với database chính
- Giải thích cách transaction rollback bảo vệ data
- Commands reference và troubleshooting

### 9. **Cập nhật AGENTS.md**
- Cập nhật section testing để phản ánh thay đổi
- Thêm reference đến guide mới
- Cập nhật migration status

## 📊 Kết Quả

### ✅ **Đã hoàn thành:**
1. **Migration cho order_sequences** - Đã tạo và tích hợp
2. **Sửa lỗi DevDatabaseTrait** - Logic đã được sửa
3. **Config database** - Chuyển sang database chính
4. **PHPUnit config** - Cập nhật cho main database
5. **OrderServiceTest** - Remove method không tồn tại, dùng Schema Trait
6. **InventoryMovementLoggerTest** - Chuẩn hóa prefix
7. **Documentation** - Guide mới và AGENTS.md cập nhật

### 🔄 **Cần làm tiếp:**
1. Chạy migration: `docker exec meomeo2-api-1 php spark migrate`
2. Chạy test: `docker exec meomeo2-api-1 vendor/bin/phpunit`
3. Kiểm tra coverage: `docker exec meomeo2-api-1 vendor/bin/phpunit --coverage-text`

## 🎯 Lợi Ích Đạt Được

1. **Đơn giản hóa cho team dev** - Chỉ quản lý 1 database
2. **Dùng data mẫu có sẵn** - File `db_lano` không sợ hỏng
3. **Transaction rollback bảo vệ data** - Test không ảnh hưởng data thật
4. **Test gần production hơn** - Cùng schema và data
5. **Loại bỏ complexity** - Không cần maintain 2 database

## 🚀 Commands Để Chạy Test

```bash
# Khởi động containers
docker-compose up -d db api

# Chạy migration (lần đầu)
docker exec meomeo2-api-1 php spark migrate

# Chạy unit tests
docker exec meomeo2-api-1 vendor/bin/phpunit

# Chạy integration tests
docker exec meomeo2-api-1 vendor/bin/phpunit -c phpunit.integration.xml

# Test với coverage
docker exec meomeo2-api-1 vendor/bin/phpunit --coverage-text
```

## 📝 Notes

- **Transaction rollback tự động**: Mọi test data được rollback tự động
- **Data safety**: Database chính được bảo vệ bởi transactions
- **Performance**: Vẫn nhanh vì transactions, không cần cleanup manual
- **Simplicity**: Team dev chỉ cần quan tâm đến 1 database

## 🔗 Files Tham Khảo

- `docs/testing/TESTING-MAIN-DB-GUIDE.md` - Guide chi tiết
- `docs/testing/TESTING-PATTERNS.md` - Patterns chuẩn
- `AGENTS.md` - Updated testing section
- `backend-ci/app/Database/Migrations/2025-11-25-000015_CreateOrderSequences.php` - Migration mới

---

**Status**: ✅ Completed - Ready for testing validation