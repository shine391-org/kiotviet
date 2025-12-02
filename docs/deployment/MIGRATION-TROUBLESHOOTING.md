# Migration Troubleshooting Guide

## Quick Diagnosis

Run the migration status checker:
```bash
chmod +x scripts/check-migration-status.sh
./scripts/check-migration-status.sh
```

---

## Common Issues & Solutions

### 1. "Tables Missing After Deploy"

**Symptoms:**
- API returns database errors
- Tables don't exist in database
- Migration marker file missing

**Diagnosis:**
```bash
# Check if migration ran
docker exec meomeo2-api-1 test -f /var/www/html/backend-ci/writable/.db_initialized && echo "Initialized" || echo "NOT initialized"

# Check table count
docker exec meomeo2-db-1 mysql -u lanocrm_user -pKP7n4RjcDbedSE2W8GgA lanocrm_shop -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'lanocrm_shop'"
```

**Solution A - Re-run Migration:**
```bash
# Remove marker file
docker exec meomeo2-api-1 rm -f /var/www/html/backend-ci/writable/.db_initialized

# Restart container (will auto-run migration)
docker-compose restart api

# Watch logs
docker-compose logs -f api
```

**Solution B - Manual Migration:**
```bash
# Run migration manually
docker exec meomeo2-api-1 bash -c "cd /var/www/html/backend-ci && php spark migrate --all"

# Check status
docker exec meomeo2-api-1 bash -c "cd /var/www/html/backend-ci && php spark migrate:status"
```

---

### 2. "Migration Timeout"

**Symptoms:**
- Container starts but migration hangs
- Logs show "Running migrations..." but never completes
- After 5 minutes, container stops

**Diagnosis:**
```bash
# Check migration log
docker exec meomeo2-api-1 cat /tmp/migration.log

# Check database locks
docker exec meomeo2-db-1 mysql -u lanocrm_user -pKP7n4RjcDbedSE2W8GgA lanocrm_shop -e "SHOW PROCESSLIST"
```

**Solution:**
```bash
# Increase timeout in docker-start.sh (already set to 300s)
# If still timing out, database may be too slow

# Option 1: Use database dump instead
docker exec meomeo2-db-1 mysql -u lanocrm_user -pKP7n4RjcDbedSE2W8GgA lanocrm_shop < backup.sql

# Option 2: Run migration in background
docker exec -d meomeo2-api-1 bash -c "cd /var/www/html/backend-ci && php spark migrate --all"
```

---

### 3. "Migration Runs Every Restart"

**Symptoms:**
- Migration runs every time container restarts
- Marker file keeps disappearing
- Duplicate data or errors

**Diagnosis:**
```bash
# Check if writable directory is persistent
docker exec meomeo2-api-1 ls -la /var/www/html/backend-ci/writable/

# Check volume mounts
docker inspect meomeo2-api-1 | grep -A 10 Mounts
```

**Solution:**
```bash
# Ensure writable directory has correct permissions
docker exec meomeo2-api-1 chmod -R 777 /var/www/html/backend-ci/writable

# Create marker file manually
docker exec meomeo2-api-1 touch /var/www/html/backend-ci/writable/.db_initialized
```

---

### 4. "Database Connection Failed"

**Symptoms:**
- "Cannot connect to database" error
- Migration never starts
- Container exits immediately

**Diagnosis:**
```bash
# Check if db container is running
docker-compose ps db

# Check database logs
docker-compose logs db

# Test connection manually
docker exec meomeo2-api-1 php -r 'new mysqli("db","lanocrm_user","KP7n4RjcDbedSE2W8GgA","lanocrm_shop");'
```

**Solution:**
```bash
# Restart database
docker-compose restart db

# Wait for database to be ready
sleep 10

# Restart API
docker-compose restart api

# If still failing, check credentials in docker-compose.yml
```

---

### 5. "Some Tables Missing"

**Symptoms:**
- Migration completes but some tables missing
- Specific features don't work
- Partial migration

**Diagnosis:**
```bash
# List all tables
docker exec meomeo2-db-1 mysql -u lanocrm_user -pKP7n4RjcDbedSE2W8GgA lanocrm_shop -e "SHOW TABLES"

# Check migration log for errors
docker exec meomeo2-api-1 cat /tmp/migration.log | grep -i error
```

**Solution:**
```bash
# Run specific migration
docker exec meomeo2-api-1 bash -c "cd /var/www/html/backend-ci && php spark migrate:version 2025-11-21-000000"

# Or rollback and re-run
docker exec meomeo2-api-1 bash -c "cd /var/www/html/backend-ci && php spark migrate:rollback"
docker exec meomeo2-api-1 bash -c "cd /var/www/html/backend-ci && php spark migrate --all"
```

---

## Emergency Recovery

### Full Database Reset

**⚠️ WARNING: This will delete ALL data!**

```bash
# Stop containers
docker-compose down

# Remove database volume
docker volume rm meomeo2_db_data

# Start fresh
docker-compose up -d

# Migration will run automatically
docker-compose logs -f api
```

### Restore from Backup

```bash
# Stop API to prevent writes
docker-compose stop api

# Restore database
docker exec -i meomeo2-db-1 mysql -u lanocrm_user -pKP7n4RjcDbedSE2W8GgA lanocrm_shop < backup.sql

# Create marker file
docker exec meomeo2-api-1 touch /var/www/html/backend-ci/writable/.db_initialized

# Start API
docker-compose start api
```

---

## Prevention Best Practices

### 1. Always Backup Before Deploy
```bash
# Create backup
./scripts/db-backup.sh

# Verify backup
ls -lh backups/
```

### 2. Test Migration Locally First
```bash
# Test on local environment
docker-compose -f docker-compose.yml -f docker-compose.override.yml up -d
./scripts/check-migration-status.sh
```

### 3. Monitor Migration Progress
```bash
# Watch logs during deploy
docker-compose logs -f api

# Check for errors
docker-compose logs api | grep -i error
```

### 4. Use Database Dumps for Production
Instead of running migrations on production, consider:
1. Run migrations on staging
2. Create database dump
3. Restore dump on production

```bash
# On staging
docker exec meomeo2-db-1 mysqldump -u lanocrm_user -pKP7n4RjcDbedSE2W8GgA lanocrm_shop > production-ready.sql

# On production
docker exec -i meomeo2-db-1 mysql -u lanocrm_user -pKP7n4RjcDbedSE2W8GgA lanocrm_shop < production-ready.sql
```

---

## Getting Help

If issues persist:

1. **Collect logs:**
   ```bash
   docker-compose logs api > api-logs.txt
   docker-compose logs db > db-logs.txt
   docker exec meomeo2-api-1 cat /tmp/migration.log > migration.log
   ```

2. **Check system resources:**
   ```bash
   docker stats
   df -h
   free -m
   ```

3. **Verify file permissions:**
   ```bash
   docker exec meomeo2-api-1 ls -la /var/www/html/backend-ci/writable/
   ```

4. **Contact support with:**
   - Log files
   - Migration status output
   - System resource info
   - Steps to reproduce