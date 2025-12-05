# Migration Quick Reference

## 🚀 Quick Commands

### Check Status
```bash
./scripts/check-migration-status.sh
```

### Re-run Migration
```bash
docker exec kiotviet-web-1 rm -f /var/www/html/backend-ci/writable/.db_initialized
docker-compose restart api
```

### Manual Migration
```bash
docker exec kiotviet-web-1 bash -c "cd /var/www/html/backend-ci && php spark migrate --all"
```

### View Logs
```bash
docker-compose logs -f api
docker exec kiotviet-web-1 cat /tmp/migration.log
```

---

## 📊 How It Works

### Automatic Migration Flow

```
Container Start
    ↓
Wait for DB (max 60s)
    ↓
Check marker file (.db_initialized)
    ↓
    ├─ Exists → Skip migration
    └─ Not exists → Run migration
        ↓
    Run: php spark migrate --all (timeout 300s)
        ↓
    Success → Create marker file
        ↓
    Start Apache
```

### Marker File Location
```
/var/www/html/backend-ci/writable/.db_initialized
```

**Purpose:** Prevents migration from running multiple times

---

## ✅ Success Indicators

When migration succeeds, you'll see:
```
✓ Database connection established
=== Database Initialization ===
Running migrations...
✓ Migrations completed successfully
✓ Database initialization completed
=== Starting Apache ===
```

---

## ❌ Failure Indicators

### Timeout
```
✗ Migration failed with status: 124
```
**Fix:** Migration took > 5 minutes. Check database performance.

### Connection Failed
```
✗ Failed to connect to database after 60 seconds
```
**Fix:** Database container not ready. Check `docker-compose logs db`

### Missing Tables
```
⚠ Table count is low (expected ~164, found 50)
```
**Fix:** Migration incomplete. Re-run migration.

---

## 🔧 Common Fixes

### 1. Reset Migration
```bash
# Remove marker
docker exec kiotviet-web-1 rm -f /var/www/html/backend-ci/writable/.db_initialized

# Restart (auto-runs migration)
docker-compose restart api

# Watch
docker-compose logs -f api
```

### 2. Force Manual Migration
```bash
docker exec kiotviet-web-1 bash -c "cd /var/www/html/backend-ci && php spark migrate --all"
```

### 3. Check What Went Wrong
```bash
# View migration log
docker exec kiotviet-web-1 cat /tmp/migration.log

# Check database
docker exec meomeo2-db-1 mysql -u lanocrm_user -pKP7n4RjcDbedSE2W8GgA lanocrm_shop -e "SHOW TABLES"

# Count tables
docker exec meomeo2-db-1 mysql -u lanocrm_user -pKP7n4RjcDbedSE2W8GgA lanocrm_shop -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'lanocrm_shop'"
```

### 4. Full Reset (⚠️ Deletes all data)
```bash
docker-compose down
docker volume rm meomeo2_db_data
docker-compose up -d --build
```

---

## 📝 Migration File Info

**Location:** `backend-ci/app/Database/Migrations/2025-11-21-000000_TestSchemaSetup.php`

**Size:** 2279 lines

**Tables Created:** 164 tables

**Expected Time:** 30-120 seconds (depends on server)

**Timeout:** 300 seconds (5 minutes)

---

## 🎯 Production Best Practices

### Before Deploy
1. ✅ Backup database: `./scripts/db-backup.sh`
2. ✅ Test on staging first
3. ✅ Review migration file for changes

### Deploy Strategy

**Option A: Auto-Migration (Simple)**
```bash
docker-compose up -d --build
# Migration runs automatically
```

**Option B: Database Dump (Safer)**
```bash
# On staging:
docker exec meomeo2-db-1 mysqldump -u lanocrm_user -pKP7n4RjcDbedSE2W8GgA lanocrm_shop > prod.sql

# On production:
docker exec -i meomeo2-db-1 mysql -u lanocrm_user -pKP7n4RjcDbedSE2W8GgA lanocrm_shop < prod.sql
docker exec kiotviet-web-1 touch /var/www/html/backend-ci/writable/.db_initialized
```

### After Deploy
1. ✅ Check status: `./scripts/check-migration-status.sh`
2. ✅ Verify table count: Should be ~164
3. ✅ Test API endpoints
4. ✅ Monitor logs for errors

---

## 🆘 Emergency Contacts

### Rollback
```bash
docker-compose down
./scripts/db-restore.sh backups/latest.sql
docker-compose up -d
```

### Get Help
1. Collect logs: `docker-compose logs > logs.txt`
2. Run diagnostics: `./scripts/check-migration-status.sh > status.txt`
3. Check migration log: `docker exec kiotviet-web-1 cat /tmp/migration.log > migration.txt`

---

## 📚 Related Docs

- **Full Guide:** `docs/MIGRATION-TROUBLESHOOTING.md`
- **Deployment:** `DEPLOYMENT.md`
- **Docker Issues:** `DOCKER-ISSUES-ANALYSIS.md`