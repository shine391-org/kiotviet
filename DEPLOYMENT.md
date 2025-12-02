# Deployment Instructions

## Prerequisites
- Docker & Docker Compose
- Git
- Bash shell

## Quick Deploy

1. Clone repository:
   ```bash
   git clone <repository-url>
   cd <repository-name>
   ```

2. Run deployment script:
   ```bash
   chmod +x deploy.sh
   ./deploy.sh
   ```

3. Start application:
   ```bash
   docker-compose up -d --build
   ```

4. Monitor startup (wait for migration to complete):
   ```bash
   docker-compose logs -f api
   ```
   
   Look for: `✓ Database initialization completed`

5. Verify setup:
   ```bash
   chmod +x scripts/check-migration-status.sh
   ./scripts/check-migration-status.sh
   ```

6. Access application:
   - Frontend: http://localhost:8000
   - Backend API: http://localhost:8000/backend-ci/api

## Database Setup

### Automatic Setup (Recommended)

Database is **automatically initialized** on first container start:

- ✓ Waits for database connection
- ✓ Runs migrations automatically
- ✓ Seeds demo data (non-production only)
- ✓ Creates initialization marker

**No manual steps required!**

### Manual Setup (If Automatic Fails)

If automatic migration fails:

1. **Check logs:**
   ```bash
   docker-compose logs api | grep -i error
   docker exec meomeo2-api-1 cat /tmp/migration.log
   ```

2. **Re-run migration:**
   ```bash
   # Remove marker to trigger re-run
   docker exec meomeo2-api-1 rm -f /var/www/html/backend-ci/writable/.db_initialized
   
   # Restart container
   docker-compose restart api
   
   # Watch logs
   docker-compose logs -f api
   ```

3. **Or run manually:**
   ```bash
   docker exec meomeo2-api-1 bash -c "cd /var/www/html/backend-ci && php spark migrate --all"
   ```

### Migration Troubleshooting

For detailed troubleshooting guide:
```bash
cat docs/MIGRATION-TROUBLESHOOTING.md
```

Quick diagnostics:
```bash
./scripts/check-migration-status.sh
```

## Production Deployment

### 1. Pre-Deployment Checklist

- [ ] Backup current database
- [ ] Test migrations on staging
- [ ] Update environment variables
- [ ] Change default passwords
- [ ] Review security settings

### 2. Security Configuration

**Change passwords in docker-compose.yml:**
```yaml
environment:
  MYSQL_ROOT_PASSWORD: <strong-password>
  MYSQL_PASSWORD: <strong-password>
```

**Update backend-ci/.env:**
```ini
CI_ENVIRONMENT = production
# Disable demo seeder
# Set proper base URLs
# Configure secure session settings
```

### 3. Database Strategy for Production

**Option A: Use Database Dump (Recommended)**
```bash
# On staging (after successful migration):
docker exec meomeo2-db-1 mysqldump -u lanocrm_user -pKP7n4RjcDbedSE2W8GgA lanocrm_shop > production.sql

# On production:
docker exec -i meomeo2-db-1 mysql -u lanocrm_user -pKP7n4RjcDbedSE2W8GgA lanocrm_shop < production.sql
docker exec meomeo2-api-1 touch /var/www/html/backend-ci/writable/.db_initialized
```

**Option B: Let Auto-Migration Run**
```bash
# Just start containers, migration runs automatically
docker-compose up -d --build

# Monitor progress
docker-compose logs -f api
```

### 4. Backup Strategy

**Before deploy:**
```bash
./scripts/db-backup.sh
```

**Schedule regular backups:**
```bash
# Add to crontab
0 2 * * * /path/to/scripts/db-backup.sh
```

### 5. Monitoring

**Check migration status:**
```bash
./scripts/check-migration-status.sh
```

**Monitor logs:**
```bash
docker-compose logs -f api
docker-compose logs -f db
```

**Health check:**
```bash
docker exec meomeo2-api-1 bash -c "cd /var/www/html/backend-ci && php spark db:health"
```

## Troubleshooting

### Migration Issues

See detailed guide: `docs/MIGRATION-TROUBLESHOOTING.md`

**Quick fixes:**

1. **Migration timeout:**
   ```bash
   # Check logs
   docker exec meomeo2-api-1 cat /tmp/migration.log
   
   # Increase timeout in docker-start.sh if needed
   ```

2. **Tables missing:**
   ```bash
   # Re-run migration
   docker exec meomeo2-api-1 rm -f /var/www/html/backend-ci/writable/.db_initialized
   docker-compose restart api
   ```

3. **Database connection failed:**
   ```bash
   # Check db container
   docker-compose ps db
   docker-compose logs db
   
   # Restart database
   docker-compose restart db
   sleep 10
   docker-compose restart api
   ```

### Permission Issues

```bash
docker exec meomeo2-api-1 chmod -R 777 /var/www/html/backend-ci/writable
```

### Container Issues

```bash
# Check container status
docker-compose ps

# View logs
docker-compose logs api
docker-compose logs db

# Restart services
docker-compose restart api
docker-compose restart db
```

### Frontend Not Loading

```bash
# Check if build completed
ls -la dist/

# Verify .htaccess
cat dist/.htaccess

# Check Apache logs
docker-compose logs api | grep -i error
```

## Rollback Procedure

If deployment fails:

1. **Stop containers:**
   ```bash
   docker-compose down
   ```

2. **Restore database:**
   ```bash
   ./scripts/db-restore.sh backups/lanocrm_shop_YYYY-MM-DD_HH-MM-SS.sql
   ```

3. **Revert code:**
   ```bash
   git checkout <previous-commit>
   ./deploy.sh
   docker-compose up -d --build
   ```

## Performance Optimization

### Database

```yaml
# In docker-compose.yml, add to db service:
command:
  - --default-authentication-plugin=mysql_native_password
  - --max_connections=200
  - --innodb_buffer_pool_size=1G
```

### PHP

```dockerfile
# In Dockerfile, add:
RUN echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/memory.ini
RUN echo "opcache.enable=1" > /usr/local/etc/php/conf.d/opcache.ini
```

## Support

For issues:
1. Check logs: `docker-compose logs`
2. Run diagnostics: `./scripts/check-migration-status.sh`
3. Review troubleshooting guide: `docs/MIGRATION-TROUBLESHOOTING.md`
4. Check migration log: `docker exec meomeo2-api-1 cat /tmp/migration.log`
