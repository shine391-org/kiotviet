# Session Log: Docker Migration Fix
**Date:** 2025-11-25  
**Issue:** FE mất sản phẩm sau khi chuyển từ WSL sang Docker  
**Root Cause:** Model table names hardcoded với prefix `db_`  

## 🔍 Problem Analysis

### Symptoms
- FE không thấy sản phẩm (empty list)
- API trả về 404 "Table 'lanocrm_shop.db_products' doesn't exist"
- Database có 800 sản phẩm nhưng API không truy cập được

### Root Cause
Khi làm task `feature/unify-testing-mysql-auto-migrate`, đã có sự nhầm lẫn:
1. **ProductModel** đang dùng `protected $table = 'db_products';` 
2. **ProductCategoryLinkModel** đang dùng `protected $table = 'db_product_category_links';`
3. Các model này bị hardcode prefix `db_` từ test environment

### Environment Confusion
- User chạy `php spark serve` trong WSL thay vì Docker container
- API container trong Docker hoạt động đúng nhưng FE không kết nối được
- Database config có `DBPrefix = 'db_'` cho test environment nhưng model bị hardcode

## 🛠️ Solution Applied

### 1. Docker Environment Setup
```bash
# ✅ Đúng: Khởi động Docker containers
docker-compose up -d

# ❌ Sai: Chạy PHP trong WSL
cd backend-ci && php spark serve  # ĐÃ DỪNG
```

### 2. Model Fixes
**Fixed ProductModel:**
```php
// ❌ Trước
protected $table = 'db_products';

// ✅ Sau  
protected $table = 'products';
```

**Fixed ProductCategoryLinkModel:**
```php
// ❌ Trước
protected $table = 'db_product_category_links';

// ✅ Sau
protected $table = 'product_category_links';
```

### 3. API Verification
```bash
# Login để lấy JWT token
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"123aA@hai"}'

# Test products API với token
curl -H "Authorization: Bearer <TOKEN>" http://localhost:8000/api/products
```

## ✅ Results

### Before Fix
```json
{
    "status": 404,
    "error": 404,
    "messages": {
        "error": "Table 'lanocrm_shop.db_products' doesn't exist"
    }
}
```

### After Fix
```json
{
    "success": true,
    "data": [
        {
            "id": "2049",
            "product_type": "goods",
            "code": "TEST-002t5",
            "name": "ffdadfa",
            // ... full product data
        }
    ]
}
```

## 📋 Key Learnings

### 1. Docker Workflow Rules
- **LUÔN LUÔN** làm việc qua Docker containers
- **KHÔNG BAO GIỜ** chạy `php spark serve` trong WSL khi Docker đang chạy
- **DÙNG** `docker exec meomeo2-api-1 php spark <command>` cho tất cả operations

### 2. Database Config Best Practices
- **KHÔNG** hardcode table names với prefix
- **DÙNG** environment variables cho database config
- **PHÂN BIỆT** rõ ràng giữa development và testing environments

### 3. Troubleshooting Steps
1. Check Docker containers: `docker ps`
2. Check API response: `curl http://localhost:8000/api/products`
3. Check database tables: `docker exec meomeo2-db-1 mysql ...`
4. Check model table names
5. Fix and test again

## 🚨 Remaining Issues

Cần fix thêm các models khác vẫn đang dùng prefix `db_`:
- `OrderModel` - `db_orders` → `orders`
- `OrderItemModel` - `db_order_items` → `order_items`  
- `WebhookEventModel` - `db_webhook_events` → `webhook_events`
- `WebhookSubscriptionModel` - `db_webhook_subscriptions` → `webhook_subscriptions`

## 📝 Next Steps

1. Fix remaining models with `db_` prefix
2. Update Docker workflow guide ([`docs/docker-workflow-guide.md`](../docker-workflow-guide.md))
3. Add validation để prevent hardcode table names
4. Update testing documentation

## 🔗 Related Files

- **Fixed:** [`backend-ci/app/Models/ProductModel.php`](../../backend-ci/app/Models/ProductModel.php)
- **Fixed:** [`backend-ci/app/Models/ProductCategoryLinkModel.php`](../../backend-ci/app/Models/ProductCategoryLinkModel.php)
- **Guide:** [`docs/docker-workflow-guide.md`](../docker-workflow-guide.md)
- **Config:** [`backend-ci/app/Config/Database.php`](../../backend-ci/app/Config/Database.php)
- **Env:** [`backend-ci/.env`](../../backend-ci/.env)

---

**Status:** ✅ **RESOLVED** - FE now shows products correctly  
**Impact:** High - Fixed core functionality  
**Time to resolve:** ~30 minutes