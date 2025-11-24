# Payment Methods Table Schema

**Module:** Order Workflow

**Related Task:** PAY-001

**Last Updated:** 2025-11-24

---

## 🎯 MỤC ĐÍCH

Document này mô tả chi tiết schema cho table **payment_methods** - master data cho các phương thức thanh toán.

---

## 📋 TABLE DEFINITION

```sql
CREATE TABLE payment_methods (
  -- Primary Key
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  
  -- Identification
  code VARCHAR(50) UNIQUE NOT NULL,
  name VARCHAR(255) NOT NULL,
  
  -- Configuration
  is_active BOOLEAN DEFAULT TRUE,
  display_order INT DEFAULT 0,
  
  -- Additional Info
  description TEXT,
  
  -- Audit
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Indexes
  INDEX idx_code (code),
  INDEX idx_active (is_active, display_order)
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 📊 FIELD DESCRIPTIONS

### **Primary Key**

**id** `BIGINT UNSIGNED AUTO_INCREMENT`

- Primary key
- Auto-increment

---

### **Identification**

**code** `VARCHAR(50) UNIQUE NOT NULL`

- Mã phương thức (dùng trong code)
- UPPERCASE by convention
- Examples:
    - `CASH`
    - `BANK_TRANSFER`
    - `CARD`
    - `COD`
    - `E_WALLET`
- UNIQUE constraint
- NOT NULL

**name** `VARCHAR(255) NOT NULL`

- Tên hiển thị cho user
- Examples:
    - "Tiền mặt"
    - "Chuyển khoản"
    - "Thẻ tín dụng/ghi nợ"
    - "Thu hộ (COD)"
    - "Ví điện tử"
- NOT NULL

---

### **Configuration**

**is_active** `BOOLEAN`

- Phương thức có active không?
- TRUE = hiển thị cho user chọn
- FALSE = ẩn (disabled)
- Default: TRUE

**display_order** `INT`

- Thứ tự hiển thị
- Nhỏ hơn = hiển thị trước
- Default: 0
- Example:
    - CASH: 1
    - BANK: 2
    - CARD: 3
    - COD: 4
    - EWALLET: 5

---

### **Additional Info**

**description** `TEXT`

- Mô tả chi tiết
- Hiển thị cho user (optional)
- Example: "Thanh toán khi nhận hàng. Phí COD: 15,000đ"
- NULL if not needed

---

### **Audit Fields**

**created_at** `TIMESTAMP`

- Thời điểm tạo
- Default: CURRENT_TIMESTAMP

**updated_at** `TIMESTAMP`

- Thời điểm update cuối
- Default: CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

---

## 🗂️ MASTER DATA

### **5 Payment Methods**

```sql
INSERT INTO payment_methods (code, name, description, display_order) VALUES
('CASH', 'Tiền mặt', 'Thanh toán bằng tiền mặt tại cửa hàng', 1),
('BANK_TRANSFER', 'Chuyển khoản', 'Chuyển khoản qua ngân hàng', 2),
('CARD', 'Thẻ tín dụng/ghi nợ', 'Thanh toán bằng thẻ Visa/Mastercard/JCB', 3),
('COD', 'Thu hộ (COD)', 'Thanh toán khi nhận hàng. Phí COD: 15,000đ', 4),
('E_WALLET', 'Ví điện tử', 'Thanh toán qua MoMo, ZaloPay, VNPay', 5);
```

---

## 📋 METHOD DETAILS

### **1. CASH - Tiền mặt**

**Code:** `CASH`

**Use Case:**

- POS orders (bán tại quầy)
- Customer trả tiền mặt trực tiếp

**Characteristics:**

- Instant payment
- No transaction fee
- Require physical presence

**Business Rule:**

- POS orders: LUÔN = CASH
- SHIPPING orders: Không dùng CASH

---

### **2. BANK_TRANSFER - Chuyển khoản**

**Code:** `BANK_TRANSFER`

**Use Case:**

- Online orders
- B2B customers
- Large transactions

**Characteristics:**

- Need bank account info
- Manual verification
- 0-24h settlement

**Business Rule:**

- Customer chuyển khoản trước
- Staff verify transaction
- Ship sau khi confirm payment

---

### **3. CARD - Thẻ tín dụng/ghi nợ**

**Code:** `CARD`

**Use Case:**

- Online orders
- POS với máy POS

**Characteristics:**

- Instant settlement
- Transaction fee (~2-3%)
- Require payment gateway

**Integration:**

- Phase 1: Manual input card info
- Phase 2: Payment gateway (Stripe, VNPay)

---

### **4. COD - Thu hộ**

**Code:** `COD`

**Use Case:**

- SHIPPING orders
- Customer không tin tưởng online payment

**Characteristics:**

- Pay when receive
- COD fee (~15,000đ)
- Risk: customer từ chối nhận hàng

**Business Rule:**

- Shipper thu tiền từ customer
- Shipper trả tiền cho shop
- Track: cod_collected, cod_reconciled

---

### **5. E_WALLET - Ví điện tử**

**Code:** `E_WALLET`

**Use Case:**

- Online orders
- Mobile payments

**Popular Wallets:**

- MoMo
- ZaloPay
- VNPay
- ShopeePay

**Integration:**

- Phase 1: Manual verify
- Phase 2: API integration

---

## 🔍 INDEXES EXPLAINED

### **idx_code (code)**

**Purpose:** Quick lookup by code

**Query:**

```sql
SELECT * FROM payment_methods WHERE code = 'CASH';
```

**Why:** Application sử dụng code để reference

---

### **idx_active (is_active, display_order)**

**Purpose:** Get active methods, sorted

**Query:**

```sql
SELECT * FROM payment_methods 
WHERE is_active = TRUE 
ORDER BY display_order ASC;
```

**Use case:** Dropdown payment methods trong UI

---

## 📊 COMMON QUERIES

### **Get active payment methods**

```sql
SELECT * FROM payment_methods 
WHERE is_active = TRUE 
ORDER BY display_order ASC;
```

**Result:**

```json
[
  {"code": "CASH", "name": "Tiền mặt"},
  {"code": "BANK_TRANSFER", "name": "Chuyển khoản"},
  {"code": "CARD", "name": "Thẻ tín dụng/ghi nợ"},
  {"code": "COD", "name": "Thu hộ (COD)"},
  {"code": "E_WALLET", "name": "Ví điện tử"}
]
```

---

### **Get method by code**

```sql
SELECT * FROM payment_methods WHERE code = 'COD';
```

---

### **Disable a method**

```sql
UPDATE payment_methods 
SET is_active = FALSE 
WHERE code = 'CARD';
```

---

### **Change display order**

```sql
UPDATE payment_methods SET display_order = 10 WHERE code = 'E_WALLET';
```

---

## 🔗 RELATIONSHIP WITH ORDERS

### **Foreign Key**

```sql
-- In orders table
payment_method VARCHAR(50),
FOREIGN KEY (payment_method) REFERENCES payment_methods(code)
```

**Note:** FK on `code` (not `id`)

**Why:**

- Code is human-readable
- Easier to debug
- Consistent across environments

---

### **Get orders by payment method**

```sql
SELECT 
  [pm.name](http://pm.name) as payment_method_name,
  COUNT(*) as order_count,
  SUM([o.total](http://o.total)) as total_amount
FROM orders o
JOIN payment_methods pm ON pm.code = o.payment_method
WHERE o.created_at >= '2024-11-01'
GROUP BY pm.code, [pm.name](http://pm.name);
```

---

## 🎨 UI EXAMPLES

### **Checkout Page - Payment Method Selection**

```jsx
// React component
function PaymentMethodSelector() {
  const [methods, setMethods] = useState([]);
  
  useEffect(() => {
    fetch('/api/payment-methods')
      .then(res => res.json())
      .then(data => setMethods(data));
  }, []);
  
  return (
    <div>
      <h3>Chọn phương thức thanh toán</h3>
      {[methods.map](http://methods.map)(method => (
        <label key={method.code}>
          <input 
            type="radio" 
            name="payment_method" 
            value={method.code}
          />
          {[method.name](http://method.name)}
          {method.description && (
            <small>{method.description}</small>
          )}
        </label>
      ))}
    </div>
  );
}
```

---

### **Admin Page - Manage Payment Methods**

```jsx
function PaymentMethodsAdmin() {
  return (
    <table>
      <thead>
        <tr>
          <th>Code</th>
          <th>Name</th>
          <th>Active</th>
          <th>Order</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        {[methods.map](http://methods.map)(method => (
          <tr key={[method.id](http://method.id)}>
            <td>{method.code}</td>
            <td>{[method.name](http://method.name)}</td>
            <td>
              <Toggle 
                value={[method.is](http://method.is)_active}
                onChange={(val) => updateActive([method.id](http://method.id), val)}
              />
            </td>
            <td>
              <input 
                type="number"
                value={method.display_order}
                onChange={(e) => updateOrder([method.id](http://method.id), [e.target](http://e.target).value)}
              />
            </td>
            <td>
              <button onClick={() => editMethod(method)}>Edit</button>
            </td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}
```

---

## 🔒 DATA INTEGRITY

### **Cannot Delete**

- Master data table - không xóa
- Chỉ disable (is_active = FALSE)

**Why:**

- Giữ historical data
- Orders cũ vẫn reference đúng
- Analytics không bị sai

---

### **Validation Rules**

**Code:**

- UPPERCASE
- No spaces
- Alphanumeric + underscore only
- Pattern: `^[A-Z_]+$`

**Name:**

- Required
- Max 255 characters

**display_order:**

- 
    
    > = 0
    > 
- Integers only

---

## 🧪 TESTING

### **Seed Data**

```php
// database/seeders/PaymentMethodSeeder.php
class PaymentMethodSeeder extends Seeder
{
    public function run()
    {
        $methods = [
            [
                'code' => 'CASH',
                'name' => 'Tiền mặt',
                'description' => 'Thanh toán bằng tiền mặt tại cửa hàng',
                'display_order' => 1
            ],
            [
                'code' => 'BANK_TRANSFER',
                'name' => 'Chuyển khoản',
                'description' => 'Chuyển khoản qua ngân hàng',
                'display_order' => 2
            ],
            [
                'code' => 'CARD',
                'name' => 'Thẻ tín dụng/ghi nợ',
                'description' => 'Thanh toán bằng thẻ Visa/Mastercard/JCB',
                'display_order' => 3
            ],
            [
                'code' => 'COD',
                'name' => 'Thu hộ (COD)',
                'description' => 'Thanh toán khi nhận hàng. Phí COD: 15,000đ',
                'display_order' => 4
            ],
            [
                'code' => 'E_WALLET',
                'name' => 'Ví điện tử',
                'description' => 'Thanh toán qua MoMo, ZaloPay, VNPay',
                'display_order' => 5
            ]
        ];
        
        foreach ($methods as $method) {
            DB::table('payment_methods')->insert($method);
        }
    }
}
```

---

## 📈 ANALYTICS QUERIES

### **Payment Method Distribution**

```sql
SELECT 
  [pm.name](http://pm.name),
  COUNT(*) as order_count,
  ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM orders), 2) as percentage
FROM orders o
JOIN payment_methods pm ON pm.code = o.payment_method
WHERE o.status = 'completed'
GROUP BY pm.code, [pm.name](http://pm.name)
ORDER BY order_count DESC;
```

**Result:**

```
| name              | order_count | percentage |
|-------------------|-------------|------------|
| Tiền mặt          | 5000        | 50.00%     |
| Thu hộ (COD)      | 3000        | 30.00%     |
| Chuyển khoản      | 1500        | 15.00%     |
| Ví điện tử        | 400         | 4.00%      |
| Thẻ tín dụng      | 100         | 1.00%      |
```

---

### **Revenue by Payment Method**

```sql
SELECT 
  [pm.name](http://pm.name),
  COUNT(*) as orders,
  SUM([o.total](http://o.total)) as revenue,
  AVG([o.total](http://o.total)) as avg_order_value
FROM orders o
JOIN payment_methods pm ON pm.code = o.payment_method
WHERE o.status = 'completed'
  AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY pm.code, [pm.name](http://pm.name)
ORDER BY revenue DESC;
```

---

## 🚀 FUTURE ENHANCEMENTS

### **Phase 2: Additional Fields**

```sql
ALTER TABLE payment_methods ADD COLUMN (
  transaction_fee_percent DECIMAL(5,2) DEFAULT 0,
  min_amount DECIMAL(15,2) DEFAULT 0,
  max_amount DECIMAL(15,2),
  icon_url VARCHAR(255),
  requires_verification BOOLEAN DEFAULT FALSE
);
```

**transaction_fee_percent:**

- % phí giao dịch
- Example: CARD = 2.5%, COD = 0.77%

**min_amount / max_amount:**

- Giới hạn số tiền
- Example: COD max = 10,000,000đ

**icon_url:**

- Icon hiển thị trong UI

**requires_verification:**

- Cần verify payment hay không?
- CASH: FALSE (instant)
- BANK_TRANSFER: TRUE (manual verify)

---

## 🔗 RELATED DOCUMENTS

- [**ORDERS_](https://www.notion.so/ORDERS_TABLE-Orders-Table-Schema-36b32ddd5ce6421abd19d92589270881?pvs=21)[TABLE.md](http://TABLE.md)** - Orders schema
- [**SCHEMA_](https://www.notion.so/SCHEMA_OVERVIEW-Database-Schema-Overview-399a75e39df64c8c8a343bd9c6bbe0a2?pvs=21)[OVERVIEW.md](http://OVERVIEW.md)** - Full schema
- **Business Decisions:** #8-15