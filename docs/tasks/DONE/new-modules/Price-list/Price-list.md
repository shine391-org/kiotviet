# 🎯 TASK: Implement Price List System (PRICE-001 + PRICE-002)

## 📋 Context

Bạn đang implement hệ thống **Bảng giá (Price Lists)** cho LanoCRM - một CRM/POS system với CodeIgniter 3 + PHP 8.4.

**Dependencies đã complete:**

- ✅ PROD-001: Products Table
- ✅ CUST-002: Customer Groups
- ✅ ORD-002: Create Order API

**Reference UI:** KiotViet Price List Management (xem tại https://www.kiotviet.vn/huong-dan-su-dung-kiotviet/)

---

## 🗄️ PART 1: BACKEND - Database & API

### PRICE-001: Price Lists Table & CRUD

**Goal:** Tạo table `price_lists` và API để quản lý nhiều bảng giá (Sỉ, Lẻ, VIP, theo thời gian)

**Database Schema:**

```sql
CREATE TABLE IF NOT EXISTS `price_lists` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(255) NOT NULL COMMENT 'Tên bảng giá: Giá sỉ, Giá lẻ, Giá VIP',
  `type` enum('base','wholesale','retail','vip','custom') DEFAULT 'custom',
  `description` text DEFAULT NULL,
  `apply_to_groups` json DEFAULT NULL COMMENT 'Array customer_group_id áp dụng',
  `start_date` date DEFAULT NULL COMMENT 'Ngày bắt đầu áp dụng',
  `end_date` date DEFAULT NULL COMMENT 'Ngày kết thúc',
  `priority` int DEFAULT 0 COMMENT 'Độ ưu tiên (số cao hơn = ưu tiên cao hơn)',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  INDEX idx_active (`is_active`, `deleted_at`),
  INDEX idx_dates (`start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `price_list_items` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `price_list_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `variant_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'NULL = áp dụng cho all variants',
  `price` decimal(12,2) NOT NULL COMMENT 'Giá override',
  `discount_percent` decimal(5,2) DEFAULT 0 COMMENT 'Giảm giá %',
  `discount_amount` decimal(12,2) DEFAULT 0 COMMENT 'Giảm giá cố định',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`price_list_id`) REFERENCES `price_lists`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  UNIQUE KEY unique_price_item (`price_list_id`, `product_id`, `variant_id`),
  INDEX idx_product (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Files to Create:**

1. **Model:** `application/models/Price_list_model.php`
    - Tham khảo: `Product_model.php` (pattern CRUD + soft delete)
    - Methods cần có:
        - `get_all($filters)` - List với filter by active, dates
        - `get_by_id($id)` - Get with items
        - `create($data)` - Validate uniqueness
        - `update($id, $data)` - Update với transaction
        - `delete($id)` - Soft delete
        - `get_applicable_price_lists($customer_group_id, $date)` - Lọc theo nhóm KH và thời gian
2. **Controller:** `application/controllers/api/Price_lists.php`
    - Tham khảo: `Branches.php` (simple CRUD pattern)
    - Endpoints:
        
        ```
        GET    /api/price-lists          - List all
        GET    /api/price-lists/:id      - Get details
        POST   /api/price-lists          - Create
        PUT    /api/price-lists/:id      - Update
        DELETE /api/price-lists/:id      - Soft delete
        GET    /api/price-lists/:id/items - Get items của bảng giá
        POST   /api/price-lists/:id/items - Add/update items bulk
        ```
        
3. **Routes:** `application/config/routes.php`
    
    ```php
    $route['api/price-lists'] = 'api/Price_lists/index';
    $route['api/price-lists/(:num)'] = 'api/Price_lists/get/$1';
    $route['api/price-lists/(:num)/items'] = 'api/Price_lists/get_items/$1';
    ```
    

**Business Logic:**

- Validate `start_date` < `end_date`
- `apply_to_groups` phải là valid customer_group_id
- Priority cao hơn = áp dụng trước khi có conflict
- Active = true và trong khoảng dates mới áp dụng

**Reuse Libraries:**

- `JwtAuth` - Authentication
- `ActivityLogger` - Log mọi thay đổi
- `validation_helper` - Validate input

---

### PRICE-002: Apply Price List to Order

**Goal:** Tự động chọn bảng giá khi tạo order dựa trên customer group và thời gian

**Files to Modify:**

1. **Model:** `application/models/Order_model.php`
    - Thêm method: `calculate_order_with_price_list($order_data, $customer_id)`
    - Logic:
        1. Lấy customer → customer_group_id
        2. Get applicable price lists theo group + current date (order by priority DESC)
        3. Loop qua từng order item:
            - Check price_list_items có override giá không
            - Apply theo priority cao nhất
            - Tính discount nếu có
        4. Return order data với prices đã override
2. **Service Layer (new):** `application/libraries/Price_calculator.php`
    
    ```php
    class Price_calculator {
        public function get_product_price($product_id, $variant_id, $customer_group_id, $quantity = 1, $order_date = null) {
            // 1. Get default price từ product/variant
            // 2. Get applicable price lists
            // 3. Apply theo priority
            // 4. Return final price
        }
    }
    ```
    
3. **Controller:** `application/controllers/api/Orders.php`
    - Update method `create()`:
        - Before create order, call `Price_calculator->get_product_price()` cho mỗi item
        - Override prices trong order_items

**Integration Points:**

- Hook vào ORD-002 Create Order API
- Lấy customer_group từ CUST-002
- Override product prices từ PROD-001

---

## 🎨 PART 2: FRONTEND - UI Components

**Tech Stack (LanoCRM):**

- Vite + TypeScript/JavaScript
- Fetch API cho HTTP calls
- Không có framework FE specific (vanilla JS hoặc minimal framework)

### Page 1: Price Lists Management (`/price-lists`)

**Layout tham khảo KiotViet:**

```
┌─────────────────────────────────────────────┐
│ [+ Tạo bảng giá]         [Tìm kiếm...] 🔍  │
├─────────────────────────────────────────────┤
│ Tên bảng giá  │ Loại  │ Thời gian │ Trạng thái│
├─────────────────────────────────────────────┤
│ Giá sỉ        │ Sỉ    │ 01/01-∞   │ ✅ Áp dụng│
│ Giá VIP 2025  │ VIP   │ 01/01-31/12│ ✅ Áp dụng│
│ Black Friday  │ Custom│ 24/11-30/11│ ⏸️ Chờ    │
└─────────────────────────────────────────────┘
```

**Components:**

```jsx
// price-lists-list.js
class PriceListsList {
  async fetchPriceLists() {
    const res = await fetch('/api/price-lists', {
      headers: { 'Authorization': `Bearer ${getToken()}` }
    });
    return res.json();
  }

  render(data) {
    // Render table với status badges
  }
}
```

### Page 2: Create/Edit Price List (`/price-lists/create`, `/price-lists/:id/edit`)

**Form fields:**

- Tên bảng giá (required)
- Loại: Sỉ/Lẻ/VIP/Custom
- Mô tả
- Áp dụng cho nhóm khách hàng (multi-select)
- Thời gian áp dụng (date range picker)
- Độ ưu tiên (number)

**Product Price Items Table:**

```
┌──────────────────────────────────────────────┐
│ [+ Thêm sản phẩm]                             │
├──────────────────────────────────────────────┤
│ SKU   │ Tên SP │ Giá gốc │ Giá mới │ Giảm % │
├──────────────────────────────────────────────┤
│ VD001 │ Ví da  │ 200k    │ 180k   │ 10%    │
└──────────────────────────────────────────────┘
```

**API Calls:**

```jsx
// Create
POST /api/price-lists
Body: { name, type, apply_to_groups, start_date, end_date, priority }

// Add items
POST /api/price-lists/:id/items
Body: { items: [{ product_id, price, discount_percent }] }
```

---

## 🔗 PART 3: INTEGRATION

### POS Integration

**Scenario:** Khi tạo order tại POS, tự động áp giá theo bảng giá

1. **Order Creation Flow:**
    
    ```
    User chọn khách hàng
    → Frontend gọi GET /api/customers/:id (lấy customer_group_id)
    → User thêm sản phẩm vào giỏ
    → Frontend gọi POST /api/orders/calculate-preview
       Body: { customer_id, items: [...] }
    → Backend trả về prices đã apply price list
    → User confirm → POST /api/orders
    ```
    
2. **New Endpoint:** `POST /api/orders/calculate-preview`
    - Input: customer_id, items[]
    - Output: items với final_price, applied_price_list_name
    - Không lưu DB, chỉ preview

### Testing Checklist

**Backend:**

- [x]  Create price list với date range
- [x]  Apply to specific customer groups
- [x]  Priority handling khi có nhiều bảng giá conflict
- [x]  Soft delete hoạt động
- [ ]  Activity logs được tạo

**Frontend:**

- [x]  List page hiển thị status (active/expired/upcoming)
- [x]  Create form validate dates
- [x]  Bulk add products to price list
- [x]  Search và filter

**Integration:**

- [x]  Order tạo với customer VIP → apply giá VIP
- [x]  Order ngày Black Friday → apply giá khuyến mãi
- [x]  Không có bảng giá phù hợp → dùng giá gốc

### Công thức tính giá tự động
- Cú pháp: `base` đại diện giá gốc; toán tử `+ - * /`; ví dụ `base * 0.9`, `base - 50000`, `base * 0.85 + 5000`.
- Làm tròn: `none | thousand | ten_thousand | hundred`.
- Tự động cập nhật: `auto_update=true` + `base_price_list_id` → khi bảng giá gốc đổi thì các bảng phụ thuộc được tính lại (hỗ trợ chuỗi A→B→C).
- Không cho circular reference; công thức âm sẽ cắt về 0; thiếu base thì fallback giá sản phẩm.

### Hướng dẫn FE nhập công thức
- Form Price List thêm toggle "Dùng công thức" → hiển thị các field: `formula` (textarea), `base_price_list_id` (select), `rounding_rule` (select), `auto_update` (checkbox).
- Placeholder formula: `base * 0.9` và tooltip giải thích `base` = giá gốc / giá từ bảng giá gốc.
- Rounding select: `none`, `hundred`, `thousand`, `ten_thousand` kèm mô tả ví dụ ngay bên phải.
- Khi chọn `auto_update=true` bắt buộc chọn `base_price_list_id`; UI hiển thị cảnh báo đỏ nếu thiếu.
- Khi submit form, FE gọi `POST/PUT /api/price-lists` với các field mới; hiển thị error message trả về từ backend (validation công thức, circular reference).
- Trong trang danh sách, hiển thị badge "Auto-update" và tooltip hiển thị base list + rounding rule.

---

## 📝 Session Log Template

Sau khi complete, tạo file `/docs/session-logs/[2025-11-23-price-lists.md](http://2025-11-23-price-lists.md)`:

- Tasks completed: PRICE-001, PRICE-002
- Files created/modified: (list)
- Challenges: (nếu có)
- Testing results
- Screenshots (nếu có)
Bắt đầu với PRICE-001 Database + API trước, test xong mới làm PRICE-002 và Frontend.
