---
title: "Migration File Split Strategy"
id: "MIGRATION-SPLIT-01"
purpose: "Strategy for splitting large migration file into manageable modules"
version: "1.0"
status: "Recommended"
priority: "Medium"
tags: ["migration", "refactoring", "database", "maintenance"]
related_to:
  - id: "MIGRATION-TROUBLESHOOTING-01"
    description: "Migration troubleshooting guide"
  - id: "DEPLOYMENT-BY-ENVIRONMENT-01"
    description: "Environment-specific deployment procedures"
---

# Migration File Split Strategy

## 📋 Overview

**Current State:**
- Single migration file: `2024-01-01-000001_CreateAuthTables.php`
- File size: **2279 lines**
- Contains: ~164 tables
- Status: **Working but difficult to maintain**

**Problem:**
- Hard to review changes
- Difficult to debug specific table issues
- Merge conflicts more likely
- Slower to load and execute

**Recommendation:** Split into **logical modules** for better maintainability.

---

## 🎯 Splitting Strategy

### Option 1: Module-Based Split (Recommended)

Split by business domain/module:

```
backend-ci/app/Database/Migrations/
├── 2024-01-01-000001_CreateAuthTables.php          (Auth & Users)
├── 2024-01-01-000002_CreateProductTables.php       (Products & Catalog)
├── 2024-01-01-000003_CreateOrderTables.php         (Orders & Sales)
├── 2024-01-01-000004_CreateInventoryTables.php     (Inventory & Warehouse)
├── 2024-01-01-000005_CreateAccountingTables.php    (Accounting & Finance)
├── 2024-01-01-000006_CreateCRMTables.php           (CRM & Customers)
├── 2024-01-01-000007_CreateHRTables.php            (HR & Payroll)
├── 2024-01-01-000008_CreateSystemTables.php        (System & Config)
└── 2024-01-01-000009_CreateIndexes.php             (All indexes)
```

**Pros:**
- ✅ Logical grouping by business domain
- ✅ Easier to understand and maintain
- ✅ Can be developed/reviewed independently
- ✅ Better for team collaboration

**Cons:**
- ⚠️ Need to handle foreign key dependencies carefully
- ⚠️ Migration order matters

---

### Option 2: Size-Based Split

Split by file size (~300 lines each):

```
backend-ci/app/Database/Migrations/
├── 2024-01-01-000001_CreateTables_Part1.php
├── 2024-01-01-000002_CreateTables_Part2.php
├── 2024-01-01-000003_CreateTables_Part3.php
├── ...
└── 2024-01-01-000008_CreateTables_Part8.php
```

**Pros:**
- ✅ Simple to implement
- ✅ Equal file sizes

**Cons:**
- ❌ No logical grouping
- ❌ Hard to find specific tables
- ❌ Not recommended

---

## 📦 Recommended Module Structure

### Module 1: Auth & Users (Priority: Critical)
**File:** `2024-01-01-000001_CreateAuthTables.php`
**Tables:** ~15 tables
```
- users
- roles
- permissions
- model_has_roles
- model_has_permissions
- role_has_permissions
- password_resets
- personal_access_tokens
- sessions
- oauth_clients
- oauth_tokens
- two_factor_authentications
- login_attempts
- user_preferences
- user_sessions
```

### Module 2: Products & Catalog (Priority: High)
**File:** `2024-01-01-000002_CreateProductTables.php`
**Tables:** ~25 tables
```
- products
- product_categories
- product_attributes
- product_attribute_options
- product_variants
- product_images
- product_reviews
- product_tags
- product_bundles
- product_related
- product_upsells
- product_crosssells
- product_specifications
- product_faqs
- product_videos
- brands
- manufacturers
- units_of_measure
- tax_categories
- product_templates
- product_kits
- bom (Bill of Materials)
- bom_items
- product_batches
- product_serials
```

### Module 3: Orders & Sales (Priority: High)
**File:** `2024-01-01-000003_CreateOrderTables.php`
**Tables:** ~20 tables
```
- orders
- order_items
- order_payments
- order_shipments
- order_statuses
- order_notes
- order_history
- order_templates
- order_subscriptions
- carts
- cart_items
- quotes
- quote_items
- sales_channels
- shipping_methods
- shipping_zones
- shipping_rates
- coupons
- discounts
- loyalty_programs
```

### Module 4: Inventory & Warehouse (Priority: High)
**File:** `2024-01-01-000004_CreateInventoryTables.php`
**Tables:** ~20 tables
```
- warehouses
- stock_movements
- stock_movement_items
- inventory_levels
- stock_adjustments
- stock_transfers
- stock_counts
- stock_count_items
- bins
- bin_locations
- pick_lists
- pick_list_items
- packing_slips
- packing_slip_items
- delivery_notes
- goods_receipts
- goods_receipt_items
- reorder_levels
- stock_alerts
- inventory_valuation
```

### Module 5: Accounting & Finance (Priority: High)
**File:** `2024-01-01-000005_CreateAccountingTables.php`
**Tables:** ~25 tables
```
- chart_of_accounts
- journal_entries
- journal_entry_lines
- gl_entries
- invoices
- invoice_items
- invoice_payments
- credit_notes
- debit_notes
- payment_terms
- payment_methods
- bank_accounts
- bank_transactions
- bank_reconciliations
- cash_transactions
- cash_transaction_references
- tax_rates
- tax_rules
- currencies
- exchange_rates
- fiscal_years
- accounting_periods
- budgets
- budget_items
- financial_reports
```

### Module 6: CRM & Customers (Priority: Medium)
**File:** `2024-01-01-000006_CreateCRMTables.php`
**Tables:** ~20 tables
```
- customers
- customer_addresses
- customer_contacts
- customer_groups
- customer_price_lists
- customer_credit_limits
- leads
- opportunities
- opportunity_items
- campaigns
- campaign_members
- activities
- tasks
- appointments
- communications
- support_tickets
- ticket_communications
- contracts
- service_agreements
- customer_feedback
```

### Module 7: HR & Payroll (Priority: Medium)
**File:** `2024-01-01-000007_CreateHRTables.php`
**Tables:** ~15 tables
```
- employees
- departments
- positions
- attendance
- leave_applications
- timesheets
- payroll_entries
- salary_structures
- salary_components
- employee_benefits
- employee_deductions
- performance_reviews
- training_programs
- employee_documents
- employee_assets
```

### Module 8: System & Config (Priority: Low)
**File:** `2024-01-01-000008_CreateSystemTables.php`
**Tables:** ~15 tables
```
- companies
- company_permissions
- branches
- settings
- configurations
- email_templates
- sms_templates
- notification_rules
- audit_logs
- activity_logs
- error_logs
- job_logs
- scheduler_rules
- webhooks
- webhook_logs
```

### Module 9: Indexes & Constraints (Priority: Low)
**File:** `2024-01-01-000009_CreateIndexes.php`
**Tables:** All indexes and foreign keys
```
- All CREATE INDEX statements
- All foreign key constraints
- Performance optimization indexes
```

---

## 🔄 Migration Process

### Step 1: Backup Current State
```bash
# Backup current migration file
cp backend-ci/app/Database/Migrations/2024-01-01-000001_CreateAuthTables.php \
   backend-ci/app/Database/Migrations/2024-01-01-000001_CreateAuthTables.php.backup

# Backup database
./scripts/db-backup.sh
```

### Step 2: Create New Migration Files
```bash
# Create module migration files
php spark make:migration CreateProductTables
php spark make:migration CreateOrderTables
php spark make:migration CreateInventoryTables
# ... etc
```

### Step 3: Extract Tables by Module
For each module:
1. Copy relevant table creation code
2. Copy relevant indexes
3. Copy relevant foreign keys
4. Ensure dependencies are in correct order

### Step 4: Update Migration Order
Ensure migrations run in correct order:
```php
// In each migration file
public function up()
{
    // Check if dependent tables exist
    if (!$this->db->tableExists('users')) {
        throw new \Exception('Auth tables must be created first');
    }
    
    // Create tables
    // ...
}
```

### Step 5: Test Migration
```bash
# Test on fresh database
docker exec meomeo2-api-1 php spark migrate:refresh

# Verify table count
docker exec meomeo2-db-1 mysql -u root -proot_password \
  -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'lanocrm_shop'"

# Should still be ~164 tables
```

### Step 6: Update Documentation
- Update `docs/MIGRATION-TROUBLESHOOTING.md`
- Update `docs/DEPLOYMENT-BY-ENVIRONMENT.md`
- Update `HUMANREADME.md`

---

## ⚠️ Important Considerations

### Foreign Key Dependencies
**Problem:** Tables with foreign keys must be created after their referenced tables.

**Solution:** Order migrations carefully:
```
1. Auth & Users (no dependencies)
2. System & Config (depends on users)
3. Products & Catalog (depends on users, companies)
4. CRM & Customers (depends on users, companies)
5. Orders & Sales (depends on products, customers)
6. Inventory & Warehouse (depends on products, orders)
7. Accounting & Finance (depends on orders, customers)
8. HR & Payroll (depends on users, companies)
9. Indexes & Constraints (depends on all tables)
```

### Rollback Strategy
Each migration must have proper `down()` method:
```php
public function down()
{
    // Drop tables in reverse order
    $this->forge->dropTable('table_name', true);
}
```

### Testing Strategy
1. **Unit Test:** Test each migration independently
2. **Integration Test:** Test full migration sequence
3. **Rollback Test:** Test rollback for each migration
4. **Performance Test:** Measure migration time

---

## 📊 Benefits vs Risks

### Benefits
- ✅ **Maintainability:** Easier to find and modify specific tables
- ✅ **Collaboration:** Multiple developers can work on different modules
- ✅ **Review:** Smaller files are easier to review
- ✅ **Debugging:** Easier to identify which module has issues
- ✅ **Performance:** Can optimize specific modules independently

### Risks
- ⚠️ **Complexity:** More files to manage
- ⚠️ **Dependencies:** Must handle foreign key order carefully
- ⚠️ **Testing:** Need comprehensive testing
- ⚠️ **Migration:** Existing deployments need careful handling

---

## 🚀 Implementation Timeline

### Phase 1: Planning (1 day)
- [ ] Review current migration file
- [ ] Identify table dependencies
- [ ] Create module groupings
- [ ] Document migration order

### Phase 2: Development (3-5 days)
- [ ] Create new migration files
- [ ] Extract tables by module
- [ ] Add dependency checks
- [ ] Write rollback methods

### Phase 3: Testing (2-3 days)
- [ ] Test on development
- [ ] Test on staging
- [ ] Test rollback scenarios
- [ ] Performance testing

### Phase 4: Documentation (1 day)
- [ ] Update all documentation
- [ ] Create migration guide
- [ ] Update deployment procedures

### Phase 5: Deployment (1 day)
- [ ] Deploy to staging
- [ ] Verify functionality
- [ ] Deploy to production

**Total Estimated Time:** 8-11 days

---

## 🎯 Decision: To Split or Not to Split?

### When to Split (Recommended if):
- ✅ Team size > 3 developers
- ✅ Frequent schema changes
- ✅ Multiple modules being developed simultaneously
- ✅ Need better code organization
- ✅ Planning major refactoring

### When NOT to Split (Keep as-is if):
- ❌ Small team (1-2 developers)
- ❌ Schema is stable
- ❌ No immediate maintenance issues
- ❌ Limited development resources
- ❌ Near production launch

---

## 📝 Current Recommendation

**Status:** **OPTIONAL - Not Critical**

**Reasoning:**
1. Current migration works fine
2. No immediate maintenance issues
3. Can be done later as refactoring task
4. Focus on features first

**When to Revisit:**
- After 6 months of development
- When team grows to 4+ developers
- When schema changes become frequent
- When merge conflicts become common

---

## 📚 Related Documentation

- **Migration Troubleshooting:** [`docs/MIGRATION-TROUBLESHOOTING.md`](MIGRATION-TROUBLESHOOTING.md)
- **Deployment Guide:** [`docs/DEPLOYMENT-BY-ENVIRONMENT.md`](DEPLOYMENT-BY-ENVIRONMENT.md)
- **Quick Reference:** [`docs/MIGRATION-QUICK-REFERENCE.md`](MIGRATION-QUICK-REFERENCE.md)

---

**Last Updated:** 2024-12-02  
**Status:** Documented - Implementation Optional  
**Priority:** Medium (Future Enhancement)