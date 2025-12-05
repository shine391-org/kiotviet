# Seeding Strategy - LANO CRM

## 📋 Overview

This document defines the complete seeding strategy for all environments (dev, staging, production).

---

## 🎯 Seeding Philosophy

### Core Principles

1. **Environment-Aware**: Different data for different environments
2. **Idempotent**: Can run multiple times safely
3. **Modular**: Each seeder handles one domain
4. **Auto-Discovery**: DevDemoSeeder auto-scans Demo/ folder
5. **Safe**: Production never gets demo data

---

## 📁 Seeder Structure

```
backend-ci/app/Database/Seeds/
├── DemoSeeder.php             # Main entry point (auto-runs all Demo seeders)
├── DevSeeder.php              # Base dev data (users, roles, master data)
├── ProductSeeder.php          # Standalone product seeder
├── PaymentMethodSeeder.php    # Standalone payment methods
└── Demo/                      # Auto-scanned by DevDemoSeeder
    ├── CustomersDemoSeeder.php
    ├── ProductVariantsDemoSeeder.php
    ├── OrdersDemoSeeder.php
    ├── InvoicesDemoSeeder.php
    ├── PriceListDemoSeeder.php
    └── CashTransactionsDemoSeeder.php
```

---

## 🔄 Seeding Flow by Environment

### Development (Local Docker)

**When:** Container first start (automatic)

**What runs:**
```bash
php spark migrate --all
php spark db:seed DemoSeeder
```

**Data loaded:**
- ✅ Base dev data (users, roles, branches)
- ✅ All demo data from Demo/ folder
- ✅ ~100-500 records per module
- ✅ Realistic test data for FE development

**Trigger:** Automatic via [`docker-start.sh`](../backend-ci/docker-start.sh)

**Control:** Set `CI_ENVIRONMENT=development` in `.env`

---

### Staging

**When:** Manual after deployment

**What runs:**
```bash
php spark db:seed DevDemoSeeder
```

**Data loaded:**
- ✅ Demo/master data giống dev
- ✅ Test accounts for QA
- ⚠️ NO real customer data

**Trigger:** Manual via deployment script

---

### Production

Hiện chưa dùng seeding cho production; chỉ chạy DemoSeeder/DevDemoSeeder ở dev/staging. Khi cần prod seeding sẽ tạo hướng dẫn riêng.

---

## 📝 Current Seeders Status

### ✅ Implemented

| Seeder | Location | Purpose | Status |
|--------|----------|---------|--------|
| DemoSeeder    | Seeds/ | Auto-run all Demo seeders | ✅ Active |
| DevSeeder | Seeds/ | Base dev data | ✅ Active |
| CustomersDemoSeeder | Seeds/Demo/ | Demo customers | ✅ Active |
| ProductVariantsDemoSeeder | Seeds/Demo/ | Demo product variants | ✅ Active |
| OrdersDemoSeeder | Seeds/Demo/ | Demo orders | ✅ Active |
| InvoicesDemoSeeder | Seeds/Demo/ | Demo invoices | ✅ Active |
| PriceListDemoSeeder | Seeds/Demo/ | Demo price lists | ✅ Active |
| CashTransactionsDemoSeeder | Seeds/Demo/ | Demo cash transactions | ✅ Active |

### ⚠️ Missing (Need to Create)

| Seeder | Priority | Purpose |
|--------|----------|---------|
| DemoSeeder    | 🟡 MEDIUM | Unified demo/staging data    |
| UsersDemoSeeder | 🟡 MEDIUM | Demo users with different roles |
| BranchesDemoSeeder | 🟡 MEDIUM | Demo branches/warehouses |
| EmployeesDemoSeeder | 🟢 LOW | Demo employees for HR module |
| AssetsDemoSeeder | 🟢 LOW | Demo assets for asset management |

---

## 🛠️ How to Create New Seeder

### For Demo Data (Development)

1. **Create file in Demo/ folder:**
   ```bash
   touch backend-ci/app/Database/Seeds/Demo/YourModuleDemoSeeder.php
   ```

2. **Use this template:**
   ```php
   <?php
   namespace App\Database\Seeds\Demo;
   
   use CodeIgniter\Database\Seeder;
   
   /**
    * @agent-seeder: Your Module demo data
    * @agent-pattern: Idempotent demo seeder
    */
   class YourModuleDemoSeeder extends Seeder
   {
       public function run(): void
       {
           // Check environment
           if (ENVIRONMENT !== 'development') {
               echo "YourModuleDemoSeeder skipped (not dev)\n";
               return;
           }
           
           // Truncate for idempotency (or use REPLACE INTO)
           $this->db->table('your_table')->truncate();
           
           // Insert demo data
           $data = [
               ['field1' => 'value1', 'field2' => 'value2'],
               // ... more records
           ];
           
           $this->db->table('your_table')->insertBatch($data);
           
           echo "✓ Seeded " . count($data) . " demo records for your_table\n";
       }
   }
   ```

3. **Test it:**
   ```bash
   docker exec meomeo2-web-1 php spark db:seed DemoSeeder
   ```

4. **No need to register!** DevDemoSeeder auto-discovers it.

---

## 🔒 Idempotency Patterns

### Pattern 1: Truncate (Simple, Fast)
```php
$this->db->table('demo_table')->truncate();
$this->db->table('demo_table')->insertBatch($data);
```

**Pros:** Simple, fast  
**Cons:** Loses all data (OK for demo)

---

### Pattern 2: REPLACE INTO (Upsert)
```php
foreach ($data as $row) {
    $this->db->table('table')->replace($row);
}
```

**Pros:** Updates existing, inserts new  
**Cons:** Slower for large datasets

---

### Pattern 3: Check Before Insert
```php
foreach ($data as $row) {
    $exists = $this->db->table('table')
        ->where('unique_field', $row['unique_field'])
        ->countAllResults() > 0;
    
    if (!$exists) {
        $this->db->table('table')->insert($row);
    }
}
```

**Pros:** Preserves existing data  
**Cons:** Slowest, most complex

---

## 📊 Data Volume Guidelines

| Environment | Records per Table | Total Records |
|-------------|-------------------|---------------|
| Development | 50-500 | ~5,000 |
| Staging | 500-5,000 | ~50,000 |

---

## 🚀 Running Seeders

### Development (Automatic)
```bash
# Runs automatically on container start
docker-compose up -d

# Or manually
docker exec meomeo2-web-1 php spark db:seed DemoSeeder
```

### Staging (Manual)
```bash
# After deployment
docker exec staging-web php spark db:seed DemoSeeder
```

## 🔄 Reset Demo Data

### Quick Reset (Development)
```bash
# Remove marker to trigger re-seed
docker exec meomeo2-api-1 rm -f /var/www/html/writable/.db_initialized

# Restart container
docker-compose restart api

# Watch logs
docker-compose logs -f api
```

### Full Reset (Development)
```bash
# Delete everything and start fresh
docker-compose down -v
docker-compose up -d --build
```

---

## 📋 Seeding Checklist

### Before Creating New Seeder

- [ ] Determine environment (dev/staging/prod)
- [ ] Choose idempotency pattern
- [ ] Plan data volume
- [ ] Consider dependencies (foreign keys)
- [ ] Add environment check

### After Creating Seeder

- [ ] Test in development
- [ ] Verify idempotency (run twice)
- [ ] Check data quality
- [ ] Document in this file
- [ ] Add to appropriate folder (Demo/ or Seeds/)

---

## 🆘 Troubleshooting

### Seeder Not Running

**Check:**
1. File in correct location?
2. Namespace correct?
3. Class name matches filename?
4. Environment check correct?

**Debug:**
```bash
# List all seeders
docker exec meomeo2-api-1 php spark db:seed --list

# Run specific seeder
docker exec meomeo2-api-1 php spark db:seed YourSeeder
```

### Foreign Key Errors

**Solution:** Seed in correct order
```php
// In DevDemoSeeder or main seeder
$this->call('CustomersDemoSeeder');  // First
$this->call('OrdersDemoSeeder');     // Then (depends on customers)
```

### Duplicate Data

**Solution:** Use idempotent pattern (truncate or REPLACE)

---

## 📚 Related Documentation

- **Migration Guide:** [`docs/MIGRATION-TROUBLESHOOTING.md`](MIGRATION-TROUBLESHOOTING.md)
- **Dev Demo Seeder:** [`docs/seeding/DEV-DEMO-SEEDER.md`](seeding/DEV-DEMO-SEEDER.md)
- **Docker Start Script:** [`backend-ci/docker-start.sh`](../backend-ci/docker-start.sh)
- **Agent Guide:** [`AGENTS.md`](../AGENTS.md)

---

## 🎯 Next Steps

### Immediate (High Priority)

1. **Create Missing Demo Seeders**
   - UsersDemoSeeder
   - BranchesDemoSeeder

2. **Document Seeder Dependencies**
   - Create dependency graph
   - Ensure correct order

### Future (Medium Priority)

1. **Create StagingSeeder**
   - Production-like data
   - Sanitized customer data

2. **Add Data Validation**
   - Verify foreign keys
   - Check data integrity

3. **Performance Optimization**
   - Batch inserts
   - Disable foreign key checks during seed
