# Deployment Guide by Environment

## 📋 Overview

This guide provides specific deployment instructions for each environment: Development, Staging, and Production.

---

## 🏠 Development (Local Docker)

### Prerequisites
- Docker & Docker Compose
- Git
- 8GB RAM minimum
- 20GB free disk space

### Quick Start

```bash
# Clone repository
git clone <repository-url>
cd meomeo2

# Start all services
docker-compose up -d --build

# Monitor startup (wait for migration to complete)
docker-compose logs -f api

# Verify setup
chmod +x scripts/check-migration-status.sh
./scripts/check-migration-status.sh
```

### What Happens Automatically

1. ✅ Database container starts
2. ✅ API container waits for database (max 60s)
3. ✅ Migrations run automatically (if not already run)
4. ✅ Demo data seeded automatically
5. ✅ Apache starts
6. ✅ Frontend dev server starts

### Access Points

- **Frontend:** http://localhost:3000
- **Backend API:** http://localhost:8000/api
- **phpMyAdmin:** http://localhost:8080
- **Database:** localhost:3306

### Environment Variables

File: `backend-ci/.env`
```ini
CI_ENVIRONMENT = development
database.default.hostname = db
database.default.database = lanocrm_shop
database.default.username = lanocrm_user
database.default.password = KP7n4RjcDbedSE2W8GgA
```

### Docker Compose Files Used

- `docker-compose.yml` (base)
- `docker-compose.override.yml` (dev overrides - auto-loaded)

### Common Tasks

**Reset database:**
```bash
docker exec meomeo2-api-1 rm -f /var/www/html/writable/.db_initialized
docker-compose restart api
```

**Re-seed demo data:**
```bash
docker exec meomeo2-api-1 php spark db:seed DevDemoSeeder
```

**View logs:**
```bash
docker-compose logs -f api
docker-compose logs -f fe
docker-compose logs -f db
```

**Run tests:**
```bash
docker exec meomeo2-api-1 vendor/bin/phpunit
```

---

## 🧪 Staging

### Prerequisites
- Server with Docker & Docker Compose
- SSH access
- Domain name (optional)
- SSL certificate (recommended)

### Deployment Steps

#### 1. Prepare Server

```bash
# SSH to staging server
ssh user@staging-server

# Install Docker if not installed
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
```

#### 2. Clone & Configure

```bash
# Clone repository
git clone <repository-url>
cd meomeo2

# Create staging environment file
cp backend-ci/.env.example backend-ci/.env

# Edit environment variables
nano backend-ci/.env
```

**Staging `.env` settings:**
```ini
CI_ENVIRONMENT = staging
database.default.hostname = db
database.default.database = lanocrm_staging
database.default.username = lanocrm_user
database.default.password = <STRONG_PASSWORD>
app.baseURL = 'https://staging.yourdomain.com/'
```

#### 3. Deploy

```bash
# Build and start services
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build

# Monitor startup
docker-compose logs -f api

# Verify migration
./scripts/check-migration-status.sh
```

#### 4. Seed Staging Data

```bash
# Option 1: Use demo data
docker exec staging-api php spark db:seed DevDemoSeeder

# Option 2: Import from production backup (sanitized)
docker exec -i staging-db mysql -u lanocrm_user -p lanocrm_staging < staging-data.sql
docker exec staging-api touch /var/www/html/writable/.db_initialized
```

### Docker Compose Files Used

- `docker-compose.yml` (base)
- `docker-compose.prod.yml` (production-like settings)

### Monitoring

```bash
# Check container status
docker-compose ps

# View logs
docker-compose logs -f api

# Check migration status
./scripts/check-migration-status.sh

# Monitor resources
docker stats
```

### Backup Strategy

```bash
# Daily backup (add to crontab)
0 2 * * * /path/to/scripts/db-backup.sh

# Manual backup before deploy
./scripts/db-backup.sh
```

---

## 🚀 Production

### Prerequisites
- Production server with Docker & Docker Compose
- Domain name with DNS configured
- SSL certificate (Let's Encrypt recommended)
- Backup strategy in place
- Monitoring system (optional but recommended)

### Pre-Deployment Checklist

- [ ] Backup current database
- [ ] Test deployment on staging
- [ ] Review all environment variables
- [ ] Change all default passwords
- [ ] Verify SSL certificate
- [ ] Plan rollback strategy
- [ ] Notify team of deployment window

### Deployment Steps

#### 1. Prepare Production Server

```bash
# SSH to production server
ssh user@production-server

# Ensure Docker is installed and updated
docker --version
docker-compose --version

# Create deployment directory
mkdir -p /opt/lanocrm
cd /opt/lanocrm
```

#### 2. Clone & Configure

```bash
# Clone repository (use specific tag/release)
git clone --branch v1.0.0 <repository-url> .

# Create production environment file
cp backend-ci/.env.example backend-ci/.env

# Edit with production settings
nano backend-ci/.env
```

**Production `.env` settings:**
```ini
CI_ENVIRONMENT = production
database.default.hostname = db
database.default.database = lanocrm_prod
database.default.username = lanocrm_user
database.default.password = <VERY_STRONG_PASSWORD>
app.baseURL = 'https://yourdomain.com/'

# Security settings
app.CSRFProtection = true
app.sessionMatchIP = true
app.cookieSecure = true
app.cookieSameSite = 'Strict'

# Logging
LOG_THRESHOLD = 1  # Errors only
```

#### 3. Update docker-compose.prod.yml

```yaml
# Update passwords in docker-compose.prod.yml
services:
  db:
    environment:
      MYSQL_ROOT_PASSWORD: <VERY_STRONG_ROOT_PASSWORD>
      MYSQL_PASSWORD: <VERY_STRONG_PASSWORD>
```

#### 4. Deploy Strategy

**Option A: Fresh Install (Recommended for first deploy)**

```bash
# Backup first (if upgrading)
./scripts/db-backup.sh

# Build and start
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build

# Monitor migration
docker-compose logs -f api

# Verify
./scripts/check-migration-status.sh

# Seed production master data only
docker exec prod-api php spark db:seed ProductionSeeder
```

**Option B: Database Import (Recommended for migrations)**

```bash
# Stop API to prevent writes
docker-compose stop api

# Import database from staging/backup
docker exec -i prod-db mysql -u lanocrm_user -p lanocrm_prod < production-ready.sql

# Create marker to skip migration
docker exec prod-api touch /var/www/html/writable/.db_initialized

# Start API
docker-compose start api

# Verify
./scripts/check-migration-status.sh
```

### Docker Compose Files Used

- `docker-compose.yml` (base)
- `docker-compose.prod.yml` (production settings)

### Post-Deployment Verification

```bash
# 1. Check all containers running
docker-compose ps

# 2. Verify migration status
./scripts/check-migration-status.sh

# 3. Test API endpoints
curl https://yourdomain.com/api/health

# 4. Check logs for errors
docker-compose logs api | grep -i error

# 5. Verify database connections
docker exec prod-api php -r "new mysqli('db','lanocrm_user','password','lanocrm_prod');"

# 6. Test frontend access
curl -I https://yourdomain.com
```

### Production Monitoring

#### Health Checks

```bash
# Add to crontab for monitoring
*/5 * * * * curl -f https://yourdomain.com/api/health || alert-team

# Check migration status daily
0 6 * * * /opt/lanocrm/scripts/check-migration-status.sh | mail -s "DB Status" admin@yourdomain.com
```

#### Log Monitoring

```bash
# View real-time logs
docker-compose logs -f api

# Check for errors
docker-compose logs api | grep -i error | tail -50

# Monitor database
docker-compose logs db | grep -i error
```

#### Resource Monitoring

```bash
# Container stats
docker stats

# Disk usage
df -h
docker system df

# Database size
docker exec prod-db mysql -u lanocrm_user -p -e "SELECT table_schema AS 'Database', ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)' FROM information_schema.tables WHERE table_schema = 'lanocrm_prod';"
```

### Backup Strategy

#### Automated Backups

```bash
# Add to crontab
# Daily backup at 2 AM
0 2 * * * /opt/lanocrm/scripts/db-backup.sh

# Weekly full backup
0 3 * * 0 /opt/lanocrm/scripts/db-backup.sh && cp /opt/lanocrm/backups/latest.sql /backup/weekly/$(date +\%Y-\%W).sql

# Monthly archive
0 4 1 * * /opt/lanocrm/scripts/db-backup.sh && cp /opt/lanocrm/backups/latest.sql /backup/monthly/$(date +\%Y-\%m).sql
```

#### Backup Verification

```bash
# Test restore on staging
scp production-backup.sql staging-server:/tmp/
ssh staging-server "docker exec -i staging-db mysql -u lanocrm_user -p lanocrm_staging < /tmp/production-backup.sql"
```

### Rollback Procedure

If deployment fails:

```bash
# 1. Stop new containers
docker-compose down

# 2. Restore database
./scripts/db-restore.sh backups/pre-deploy-backup.sql

# 3. Checkout previous version
git checkout <previous-tag>

# 4. Rebuild and start
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build

# 5. Verify
./scripts/check-migration-status.sh
```

### Security Hardening

#### 1. Firewall Rules

```bash
# Allow only necessary ports
sudo ufw allow 22/tcp   # SSH
sudo ufw allow 80/tcp   # HTTP
sudo ufw allow 443/tcp  # HTTPS
sudo ufw enable
```

#### 2. SSL/TLS Setup

```bash
# Using Let's Encrypt
sudo apt install certbot
sudo certbot certonly --standalone -d yourdomain.com

# Update docker-compose.prod.yml to mount certificates
```

#### 3. Database Security

```bash
# Restrict database access to internal network only
# In docker-compose.prod.yml, remove ports exposure for db service
```

#### 4. Regular Updates

```bash
# Update Docker images monthly
docker-compose pull
docker-compose up -d --build

# Update system packages
sudo apt update && sudo apt upgrade -y
```

---

## 📊 Comparison Matrix

| Feature | Development | Staging | Production |
|---------|-------------|---------|------------|
| **Environment** | Local Docker | Server | Server |
| **Data** | Demo data | Sanitized prod data | Real data |
| **Migration** | Auto on start | Auto on deploy | Manual/Import |
| **Seeding** | DevDemoSeeder | StagingSeeder | ProductionSeeder |
| **Monitoring** | Logs only | Basic | Full monitoring |
| **Backup** | Not required | Daily | Hourly + Daily + Weekly |
| **SSL** | Not required | Recommended | Required |
| **Resources** | 2GB RAM | 4GB RAM | 8GB+ RAM |
| **Compose Files** | yml + override | yml + prod | yml + prod |

---

## 🆘 Troubleshooting by Environment

### Development Issues

**Problem:** Migration not running  
**Solution:** Remove marker and restart
```bash
docker exec meomeo2-api-1 rm -f /var/www/html/writable/.db_initialized
docker-compose restart api
```

**Problem:** Port already in use  
**Solution:** Change ports in docker-compose.yml or stop conflicting service

### Staging Issues

**Problem:** Out of disk space  
**Solution:** Clean up old images and volumes
```bash
docker system prune -a
docker volume prune
```

**Problem:** Slow performance  
**Solution:** Increase resources in docker-compose.prod.yml

### Production Issues

**Problem:** Database connection timeout  
**Solution:** Check database container and increase connection pool

**Problem:** High memory usage  
**Solution:** Optimize PHP memory limit and database buffer pool

---

## 📚 Related Documentation

- **Migration Guide:** [`docs/MIGRATION-TROUBLESHOOTING.md`](MIGRATION-TROUBLESHOOTING.md)
- **Seeding Strategy:** [`docs/SEEDING-STRATEGY.md`](SEEDING-STRATEGY.md)
- **Quick Reference:** [`docs/MIGRATION-QUICK-REFERENCE.md`](MIGRATION-QUICK-REFERENCE.md)
- **Main Deployment:** [`DEPLOYMENT.md`](../DEPLOYMENT.md)

---

## 🎯 Next Steps After Deployment

1. ✅ Verify all services running
2. ✅ Test critical API endpoints
3. ✅ Check frontend loads correctly
4. ✅ Verify database migration status
5. ✅ Set up monitoring alerts
6. ✅ Configure automated backups
7. ✅ Document any custom configurations
8. ✅ Train team on deployment process