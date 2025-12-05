# Root Cause Analysis: Docker Environment Confusion
**Date:** 2025-11-25  
**Severity:** High  
**Impact:** Production functionality loss  

## 🎯 Executive Summary

Vấn đề xảy ra do **sự nhầm lẫn giữa development environment trong WSL và Docker containers**, dẫn đến việc hardcode table names sai và chạy commands sai environment. Đây là một classic case của **environment configuration drift**.

## 🔍 Detailed Root Cause Analysis

### 1. Primary Root Cause: Environment Configuration Drift

#### **Timeline of Events:**
```
Task: feature/unify-testing-mysql-auto-migrate
├── Step 1: Setup MySQL-only testing
├── Step 2: Configure test database with DBPrefix = 'db_'
├── Step 3: ❌ ACCIDENTAL: Hardcode table names in models
└── Step 4: ❌ CONFUSION: Mix WSL + Docker environments
```

#### **Technical Root Causes:**

##### **A. Model Table Name Hardcoding**
```php
// ❌ WRONG - Hardcoded from test environment
class ProductModel extends Model {
    protected $table = 'db_products';  // Should be 'products'
}

// ❌ WRONG - Test prefix leaked to production
class ProductCategoryLinkModel extends Model {
    protected $table = 'db_product_category_links';  // Should be 'product_category_links'
}
```

**Why this happened:**
- Developer đang làm task testing với `DBPrefix = 'db_'`
- Để test nhanh, đã hardcode table names thay vì dùng config
- Quên revert changes khi merge to main
- Code review không detect được issue này

##### **B. Environment Confusion**
```bash
# ❌ WSL Environment (Wrong)
cd backend-ci && php spark serve
# → Uses local PHP, connects to wrong database config

# ✅ Docker Environment (Correct)  
docker-compose up -d
docker exec meomeo2-api-1 php spark serve
# → Uses container PHP, connects to correct database
```

**Why this happened:**
- Developer quen thói quen chạy commands trực tiếp trong terminal
- Không nhận ra Docker containers đã đang chạy
- Thiếu clear documentation về workflow
- Environment variables khác nhau giữa WSL và Docker

### 2. Secondary Root Causes

#### **A. Database Configuration Complexity**
```php
// backend-ci/app/Config/Database.php
public array $tests = [
    'DBPrefix' => 'db_',  // Test environment
    'hostname' => 'db-test',
    'database' => 'lanocrm_test',
];

public array $default = [
    'DBPrefix' => '',      // Development environment  
    'hostname' => 'db',
    'database' => 'lanocrm_shop',
];
```

**Problem:** Multiple database configs với different prefixes tạo ra confusion.

#### **B. Lack of Environment Validation**
- Không có validation để detect table name mismatches
- Không có automated tests cho environment-specific configurations
- Không có clear separation giữa test và production models

#### **C. Documentation Gaps**
- Thiếu clear Docker workflow documentation
- Không có guidelines cho environment-specific development
- Không có troubleshooting guides cho common issues

## 🧪 Causal Chain Analysis

```
1. Task: MySQL-only testing migration
   ↓
2. Need: Test database with prefix 'db_'
   ↓  
3. Action: Configure DBPrefix = 'db_' in test config
   ↓
4. Shortcut: Hardcode table names in models for quick testing
   ↓
5. Mistake: Forget to revert hardcode changes
   ↓
6. Confusion: Run commands in WSL instead of Docker
   ↓
7. Symptom: API looks for 'db_products' table
   ↓
8. Error: Table doesn't exist in development database
   ↓
9. Impact: FE shows empty product list
```

## 📊 Impact Analysis

### **Technical Impact:**
- ✅ Data integrity: No data loss (800 products still in database)
- ❌ API functionality: Products endpoint completely broken
- ❌ User experience: FE shows empty product lists
- ❌ Development workflow: Confusion between environments

### **Business Impact:**
- 🔴 High: Core functionality (product listing) unavailable
- 🔴 Medium: Developer productivity loss during troubleshooting
- 🟡 Low: No customer-facing impact (internal development issue)

## 🛡️ Prevention Strategies

### **Immediate Actions:**
1. ✅ Fix hardcoded table names in affected models
2. ✅ Create Docker workflow guide
3. ✅ Add environment validation

### **Long-term Prevention:**

#### **1. Code Quality Controls**
```php
// ❌ Don't do this
protected $table = 'db_products';

// ✅ Do this - Use environment-aware config
protected $table = 'products';  // Always use base table name
// DBPrefix handled by framework config
```

#### **2. Environment Validation**
```php
// Add to BaseModel or validation
public function validateTableNames() {
    if (ENVIRONMENT === 'development' && strpos($this->table, 'db_') === 0) {
        throw new \Exception('Test table prefix detected in development!');
    }
}
```

#### **3. Automated Testing**
```bash
# Add to CI/CD pipeline
php spark validate:environment
php spark validate:table-names
```

#### **4. Documentation & Training**
- ✅ Docker workflow guide created
- 📋 Environment setup checklist
- 🎯 Developer onboarding training

#### **5. Development Process Improvements**
- Mandatory code review for model changes
- Environment-specific test suites
- Automated deployment validation

## 🎯 Lessons Learned

### **Technical Lessons:**
1. **Never hardcode environment-specific values** in models
2. **Always use framework configuration** for database prefixes
3. **Separate test and development configurations** clearly
4. **Validate environment-specific code** in CI/CD

### **Process Lessons:**
1. **Clear documentation is essential** for complex environments
2. **Environment consistency checks** prevent configuration drift
3. **Code review must catch** environment-specific issues
4. **Developer training** reduces environment confusion

### **Architectural Lessons:**
1. **Clean Architecture principle violated** - Models should not know about test prefixes
2. **Single Responsibility violated** - Models handling both business logic and environment config
3. **Configuration management** needs to be centralized and validated

## 📋 Action Items

### **Completed (✅):**
- [x] Fix ProductModel table name
- [x] Fix ProductCategoryLinkModel table name  
- [x] Create Docker workflow guide
- [x] Verify API functionality

### **In Progress (🔄):**
- [ ] Fix remaining models with `db_` prefix
- [ ] Add environment validation
- [ ] Update development documentation

### **Future (📅):**
- [ ] Implement automated environment checks
- [ ] Add model validation rules
- [ ] Create developer onboarding checklist
- [ ] Review and improve code review process

## 🔗 Related Issues

This issue reveals deeper architectural problems that need addressing:
- **Configuration Management:** Need centralized config validation
- **Environment Parity:** Ensure consistency across environments  
- **Code Quality:** Need better safeguards against environment-specific bugs
- **Developer Experience:** Need clearer workflows and documentation

---

**Conclusion:** This was a preventable issue caused by environment confusion and configuration shortcuts. The fix addresses immediate symptoms, but long-term prevention requires architectural improvements and process changes.