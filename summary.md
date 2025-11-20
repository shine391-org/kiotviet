# TỔNG HỢP DỰ ÁN CRM - CODEIGNITER 3

## Ngày cập nhật: 24/10/2025

---

## I. THÔNG TIN DỰ ÁN

- **Tên dự án:** Hệ thống CRM quản lý bán hàng
- **Framework:** CodeIgniter 3
- **Database:** MySQL - `lanocrm_shop`
- **Base URL:** https://banhang.tuidanam.org/backend-ci/
- **Tài liệu:** crm_decription.docx
- **Kiến trúc:** RESTful API với JWT Authentication

---

## II. MODULE ĐÃ HOÀN THÀNH

### 1. MODULE BRANCH (CHI NHÁNH) ✅

#### Files đã tạo:
/application/controllers/api/Branches.php
/application/models/Branch_model.php

text

#### Chức năng:
- ✅ CRUD đầy đủ (Create, Read, Update, Delete)
- ✅ Phân quyền admin/super-admin
- ✅ Lọc, phân trang, sắp xếp
- ✅ Soft delete (deleted_at)
- ✅ Validation đầy đủ

#### API Endpoints:
GET /api/branches - Lấy danh sách chi nhánh
GET /api/branches/:id - Chi tiết chi nhánh
POST /api/branches - Tạo chi nhánh mới
PUT /api/branches/:id - Cập nhật chi nhánh
DELETE /api/branches/:id - Xóa chi nhánh (soft delete)

text

---

### 2. MODULE USER & AUTHENTICATION ✅

#### Files đã tạo:

**Controllers:**
/application/controllers/api/Users.php

text

**Models:**
/application/models/User_model.php
/application/models/Role_permission_model.php
/application/models/Activity_log_model.php
/application/models/Session_model.php
/application/models/Login_attempt_model.php
/application/models/Password_history_model.php

text

**Libraries:**
/application/libraries/JwtAuth.php
/application/libraries/ActivityLogger.php
/application/libraries/RateLimiter.php

text

**Helpers:**
/application/helpers/validation_helper.php

text

#### Chức năng đã triển khai:

##### A. Authentication
- ✅ Login với JWT token (24h expiration)
- ✅ Logout và revoke token
- ✅ Rate limiting (5 lần/15 phút)
- ✅ Session tracking trong database
- ✅ Auto lock account sau 5 lần đăng nhập sai

##### B. User Management
- ✅ CRUD users (Create, Read, Update, Delete)
- ✅ Get all users (admin only)
- ✅ Get user by ID
- ✅ Soft delete users
- ✅ Activate/Deactivate account

##### C. Password Management
- ✅ Change password
- ✅ Password strength validation
  - Min 8 ký tự
  - Có chữ hoa, chữ thường, số
- ✅ Password history (không cho dùng lại 5 mật khẩu gần nhất)
- ✅ Mã hóa bcrypt

##### D. Profile Management
- ✅ Get profile (current user)
- ✅ Update profile (fullname, phone, email, timezone)

##### E. Session Management
- ✅ Xem tất cả sessions (các thiết bị đăng nhập)
- ✅ Logout thiết bị khác
- ✅ Revoke token khi logout
- ✅ Token expiration tracking

##### F. Role & Permission
- ✅ Assign role cho user
- ✅ Get all roles
- ✅ Get permissions by role
- ✅ Assign permissions to role
- ✅ Check permission trước mỗi action

##### G. Activity Logging
- ✅ Ghi log mọi thao tác (create, update, delete, login, logout)
- ✅ Xem lịch sử hoạt động theo user
- ✅ Xem lịch sử đăng nhập
- ✅ Lưu IP, user agent, timestamp

#### API Endpoints:

Authentication
POST /api/auth/login - Đăng nhập
POST /api/auth/logout - Đăng xuất
POST /api/auth/change-password - Đổi mật khẩu

User CRUD (Admin only)
GET /api/users - Danh sách users
POST /api/users - Tạo user mới
GET /api/users/:id - Chi tiết user
PUT /api/users/:id - Cập nhật user
DELETE /api/users/:id - Xóa user

Profile
GET /api/users/profile - Thông tin profile
PUT /api/users/profile - Cập nhật profile

Sessions
GET /api/users/sessions - Danh sách sessions
POST /api/users/sessions/logout-others - Logout thiết bị khác

Roles & Permissions
GET /api/users/roles - Danh sách roles
GET /api/users/roles/:id/permissions - Permissions của role
POST /api/users/roles/assign-permissions - Gán permissions cho role
POST /api/users/assign-role/:user_id/:role_id - Gán role cho user

Activity Logs
GET /api/users/activities - Logs của current user
GET /api/users/:id/activities - Logs của user (admin)
GET /api/users/:id/login-history - Lịch sử đăng nhập

text

---

## III. DATABASE CHANGES

### Bảng mới đã tạo:

#### 1. activity_logs
CREATE TABLE activity_logs (
id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
user_id BIGINT(20) UNSIGNED,
action VARCHAR(100) NOT NULL,
module VARCHAR(50) NOT NULL,
model_type VARCHAR(100),
model_id BIGINT(20) UNSIGNED,
old_values TEXT,
new_values TEXT,
ip_address VARCHAR(45),
user_agent TEXT,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
INDEX idx_user_date (user_id, created_at),
INDEX idx_module_model (module, model_type, model_id),
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

text

#### 2. sessions
CREATE TABLE sessions (
id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
user_id BIGINT(20) UNSIGNED NOT NULL,
token VARCHAR(500) NOT NULL,
device_info TEXT,
ip_address VARCHAR(45),
last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
expires_at TIMESTAMP NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
UNIQUE KEY unique_token (token),
INDEX idx_user_active (user_id, last_activity),
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

text

#### 3. login_attempts
CREATE TABLE login_attempts (
id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
username VARCHAR(100) NOT NULL,
ip_address VARCHAR(45) NOT NULL,
user_agent TEXT,
success BOOLEAN DEFAULT FALSE,
failure_reason VARCHAR(255),
attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
INDEX idx_username_time (username, attempted_at),
INDEX idx_ip_time (ip_address, attempted_at)
);

text

#### 4. password_history
CREATE TABLE password_history (
id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
user_id BIGINT(20) UNSIGNED NOT NULL,
password_hash VARCHAR(255) NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
INDEX idx_user_date (user_id, created_at),
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

text

### Bảng đã cập nhật:

#### users - Bổ sung trường:
ALTER TABLE users
ADD COLUMN two_factor_secret VARCHAR(255),
ADD COLUMN two_factor_enabled BOOLEAN DEFAULT FALSE,
ADD COLUMN password_changed_at TIMESTAMP NULL,
ADD COLUMN failed_login_attempts INT DEFAULT 0,
ADD COLUMN account_locked_until TIMESTAMP NULL,
ADD COLUMN timezone VARCHAR(50) DEFAULT 'Asia/Ho_Chi_Minh';

text

#### branches - Bổ sung:
ALTER TABLE branches
ADD COLUMN coordinates VARCHAR(100),
ADD INDEX idx_code (code),
ADD INDEX idx_status (status),
ADD INDEX idx_manager_id (manager_id),
ADD UNIQUE INDEX unique_code (code);

text

---

## IV. ROUTES ĐÃ CẤU HÌNH

File: `/application/config/routes.php`

Đã cấu hình đầy đủ routes cho:
- Authentication (login, logout, change password)
- User Management (CRUD)
- Profile Management
- Session Management
- Role & Permission Management
- Activity Logs
- Branch Management

---

## V. TÍNH NĂNG BẢO MẬT

### Đã triển khai:

1. **JWT Authentication** ✅
   - Token expiration: 24 giờ
   - Session tracking trong database
   - Revoke token khi logout

2. **Rate Limiting** ✅
   - 5 lần đăng nhập sai / 15 phút
   - Auto lock account
   - Track failed attempts

3. **Password Security** ✅
   - Bcrypt hashing
   - Password strength validation
   - Password history (5 passwords)
   - No password reuse

4. **Session Management** ✅
   - Multi-device tracking
   - Logout other devices
   - Session expiration

5. **Activity Logging** ✅
   - Audit trail đầy đủ
   - Track IP, user agent
   - Log mọi thao tác quan trọng

6. **Access Control** ✅
   - Role-based permissions
   - Route-level authorization
   - Data-level permissions

7. **Input Validation** ✅
   - Username format validation
   - Email format validation
   - Phone number validation (VN)
   - SQL injection prevention

---

## VI. TESTING ĐÃ THỰC HIỆN

### Test Cases:

- ✅ Login thành công
- ✅ Login thất bại (wrong password)
- ✅ Rate limiting (too many failed attempts)
- ✅ Logout và revoke token
- ✅ Get users (admin only)
- ✅ Create user với validation
- ✅ Update user
- ✅ Delete user (soft delete)
- ✅ Get profile
- ✅ Update profile
- ✅ Change password
- ✅ Password strength validation
- ✅ Password history check
- ✅ Get sessions
- ✅ Logout other sessions
- ✅ Activity logs
- ✅ Login history
- ✅ Get branches
- ✅ Create branch
- ✅ Authorization check (Forbidden response)

---

## VII. VẤN ĐỀ ĐÃ GIẢI QUYẾT

### Lỗi đã fix:

1. ✅ **Database Error: Unknown column 'token'**
   - Nguyên nhân: Bảng sessions chưa được tạo
   - Giải pháp: Chạy migration script tạo bảng

2. ✅ **Database Error: Unknown column 'device_info'**
   - Nguyên nhân: Thiếu cột trong bảng sessions
   - Giải pháp: ALTER TABLE thêm cột

3. ✅ **Too many requests (429)**
   - Nguyên nhân: Rate limiting hoạt động
   - Giải pháp: Reset login_attempts hoặc đợi 15 phút

4. ✅ **Activity logs trả về []**
   - Nguyên nhân: Chưa có dữ liệu test
   - Giải pháp: Tạo dữ liệu test bằng các API actions

5. ✅ **Password mã hóa**
   - Giải pháp: Sử dụng bcrypt hash generator hoặc PHP script

---

## VIII. CHỨC NĂNG CHƯA HOÀN THIỆN

### Ưu tiên cao:

1. ⚠️ **Forgot Password / Reset Password**
   - Gửi email reset link
   - Tạo token reset có thời hạn
   - Validate token và reset password

2. ⚠️ **Upload Avatar**
   - Upload file avatar
   - Resize và optimize image
   - Update user avatar path

3. ⚠️ **Branch Statistics API**
   - Thống kê doanh thu chi nhánh
   - Số nhân viên theo chi nhánh
   - Top sản phẩm bán chạy

4. ⚠️ **Branch Employees Management**
   - API lấy nhân viên theo chi nhánh
   - Assign employee to branch
   - Transfer employee between branches

5. ⚠️ **Email Notifications**
   - Email khi đăng nhập thiết bị mới
   - Email khi thay đổi mật khẩu
   - Email reset password

### Ưu tiên trung bình:

6. ⚠️ **2FA (Two-Factor Authentication)**
   - Setup 2FA với QR code
   - Verify OTP code
   - Backup codes

7. ⚠️ **Import/Export**
   - Import users từ CSV/Excel
   - Export users ra CSV
   - Import/Export branches

8. ⚠️ **Branch Inventory**
   - API tồn kho theo chi nhánh
   - Transfer inventory between branches

9. ⚠️ **Advanced Validation**
   - Validation địa chỉ chi tiết
   - Validation mã số thuế
   - Validation số điện thoại theo operator

### Module mới cần phát triển:

10. ⚠️ **Module Product** - Quản lý sản phẩm
11. ⚠️ **Module Customer** - Quản lý khách hàng (CRM)
12. ⚠️ **Module Partner** - Nhà cung cấp, vận chuyển
13. ⚠️ **Module Order** - Quản lý đơn hàng
14. ⚠️ **Module Cashbook** - Sổ quỹ thu chi
15. ⚠️ **Module Inventory** - Xuất nhập tồn kho
16. ⚠️ **Module Report** - Báo cáo thống kê

---

## IX. HƯỚNG DẪN SỬ DỤNG

### Test với Postman:

#### 1. Setup Environment:
Environment name: LanoCRM
Variables:

BASE_URL: https://banhang.tuidanam.org/backend-ci

TOKEN: [Lấy từ response login]

text

#### 2. Login để lấy token:
POST {{BASE_URL}}/api/auth/login
Content-Type: application/json

{
"username": "admin",
"password": "your_password"
}

text

Response:
{
"token": "eyJ0eXAiOiJKV1QiLCJhbGci...",
"user": {
"id": "1",
"username": "admin",
"role": "super-admin"
}
}

text

#### 3. Sử dụng token cho các request khác:
GET {{BASE_URL}}/api/users
Authorization: Bearer {{TOKEN}}

text

### Reset mật khẩu admin:

**Cách 1: Tạo hash bcrypt online**
1. Truy cập: https://bcrypt-generator.com/
2. Nhập password mới
3. Chọn Rounds: 10
4. Copy hash
5. Chạy SQL:
UPDATE users
SET password = 'HASH_VỪA_TẠO'
WHERE username = 'admin';

text

**Cách 2: Script PHP**
<?php $password = "Admin@123456"; $hash = password_hash($password, PASSWORD_BCRYPT); echo "Hash: " . $hash; ?>
text

---

## X. CẤU TRÚC CODE CHUẨN

### Design Patterns áp dụng:

1. **MVC Pattern**
   - Model: Database operations
   - View: JSON response (RESTful API)
   - Controller: Business logic

2. **Repository Pattern**
   - Models làm repository
   - Tách biệt database logic

3. **Service Layer Pattern**
   - Libraries làm service layer
   - Reusable business logic

4. **Dependency Injection**
   - Inject qua constructor
   - Load models/libraries khi cần

### Code Standards:

- ✅ PSR-12 coding style (adapted)
- ✅ Comment tiếng Việt cho dễ hiểu
- ✅ Function naming: snake_case
- ✅ Class naming: PascalCase
- ✅ Constant naming: UPPER_CASE
- ✅ RESTful API design
- ✅ HTTP status codes chuẩn
- ✅ Error handling đầy đủ
- ✅ Validation at controller level
- ✅ Security best practices

---

## XI. LƯU Ý QUAN TRỌNG

### Security:

1. **JWT Secret Key**
   - Hiện tại: `ffb4430639648be05bdcc19a454586c%`
   - ⚠️ **ĐỔI KEY TRONG PRODUCTION!**
   - File: `/application/libraries/JwtAuth.php`

2. **Session Timeout**
   - Mặc định: 24 giờ
   - Có thể điều chỉnh trong JwtAuth::generateToken()

3. **Rate Limiting**
   - Default: 5 lần / 15 phút
   - Có thể điều chỉnh trong Users::login()

4. **Password Policy**
   - Min 8 ký tự
   - Phải có chữ hoa, chữ thường, số
   - Không cho dùng lại 5 mật khẩu gần nhất

### Database:

5. **Soft Delete**
   - Tất cả delete đều là soft delete
   - Set `deleted_at` thay vì DELETE
   - Có thể restore sau này

6. **Activity Logs**
   - Tự động ghi log mọi CREATE, UPDATE, DELETE
   - Lưu old_values và new_values (JSON)
   - Không được xóa logs

7. **Index Optimization**
   - Đã thêm index cho các trường thường query
   - Composite index cho query phức tạp

### Development:

8. **Error Log**
   - Check PHP error log khi có lỗi
   - Location: `/home/user/public_html/error_log`

9. **Database Backup**
   - Backup database trước khi migration
   - File SQL hiện tại: `lanocrm_shop.sql`

10. **Version Control**
    - Nên dùng Git để quản lý source code
    - Ignore: vendor/, .env, error_log

---

## XII. TROUBLESHOOTING

### Lỗi thường gặp:

#### 1. "Unauthorized" (401)
**Nguyên nhân:** Token không hợp lệ hoặc hết hạn
**Giải pháp:** Login lại để lấy token mới

#### 2. "Forbidden" (403)
**Nguyên nhân:** User không có quyền truy cập
**Giải pháp:** Check role và permissions của user

#### 3. "Too Many Requests" (429)
**Nguyên nhân:** Đăng nhập sai quá nhiều lần
**Giải pháp:**
DELETE FROM login_attempts WHERE username = 'admin';
UPDATE users SET failed_login_attempts = 0 WHERE username = 'admin';

text

#### 4. "Database Error: Unknown column"
**Nguyên nhân:** Thiếu cột trong bảng
**Giải pháp:** Chạy migration script bổ sung cột

#### 5. Activity logs trả về []
**Nguyên nhân:** Chưa có dữ liệu
**Giải pháp:** Thực hiện các action để tạo logs

---

## XIII. ROADMAP TIẾP THEO

### Phase 1 (Tuần 1-2):
- [ ] Forgot Password / Reset Password
- [ ] Upload Avatar
- [ ] Email Notifications
- [ ] Branch Statistics

### Phase 2 (Tuần 3-4):
- [ ] Module Product (CRUD)
- [ ] Module Customer (CRM Basic)
- [ ] Import/Export Users & Branches
- [ ] 2FA Implementation

### Phase 3 (Tuần 5-6):
- [ ] Module Order (Sales Order)
- [ ] Module Partner (Suppliers)
- [ ] Module Inventory (Basic)
- [ ] Dashboard Analytics

### Phase 4 (Tuần 7-8):
- [ ] Module Cashbook (Financial)
- [ ] Module Report (Advanced)
- [ ] Mobile App API
- [ ] Performance Optimization

---

## XIV. LIÊN HỆ & HỖ TRỢ

### Tài liệu tham khảo:
- CodeIgniter 3: https://codeigniter.com/userguide3/
- JWT: https://jwt.io/
- Bcrypt: https://bcrypt-generator.com/

### Testing Tools:
- Postman: https://www.postman.com/
- phpMyAdmin: Đã có sẵn trên hosting

---

**Cập nhật lần cuối:** 24/10/2025 11:20 AM
**Phiên bản:** 1.0.0
**Người thực hiện:** Development Team