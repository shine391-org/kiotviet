# 🚀 Deployment Guide - KiotViet CRM

> **Single Source of Truth** for deployment across all environments

## 📋 Quick Start

### Development Environment

```bash
# Option 1: Using deployment script (recommended)
./scripts/deploy-dev.sh

# Option 2: Manual with custom branch
./scripts/deploy-dev.sh feature/my-feature

# Option 3: Quick start
./scripts/dev-up.sh
```

**Access URLs:**
- Frontend: http://localhost:3000 (or `${FE_PORT}`)
- Backend API: http://localhost:8000 (or `${APP_PORT}`)
- phpMyAdmin: http://localhost:8080 (or `${PMA_PORT}`)

### Staging Environment

```bash
# Deploy staging (from main branch)
./scripts/deploy-staging.sh

# Verify deployment
./scripts/verify-deployment.sh staging
```

**Access URLs:**
- Frontend: http://localhost (or Port 80)
- Backend API: http://localhost/api
- Database: localhost:3308
- phpMyAdmin: http://localhost:8081

---

## 🔧 Port Configuration

All ports are now configurable via environment variables!

### Setting Up .env

```bash
# 1. Copy example file
cp .env.example .env

# 2. Edit ports (optional)
nano .env
```

### Port Configuration Matrix

| Environment | Service | Default Port | Env Variable | Override in |
|------------|---------|--------------|--------------|-------------|
| **Development** | Frontend | 3000 | `FE_PORT` | `.env` |
| | Backend | 8000 | `APP_PORT` | `.env` |
| | PHPMyAdmin | 8080 | `PMA_PORT` | `.env` |
| | Test DB | 3307 | `DB_TEST_PORT` | `.env` |
| **Staging** | Frontend | 80 | `STAGING_FE_PORT` | `.env` |
| | Database | 3308 | `STAGING_DB_PORT` | `.env` |
| | Test DB | 3309 | `STAGING_DB_TEST_PORT` | `.env` |
| | PHPMyAdmin | 8081 | `STAGING_PMA_PORT` | `.env` |

### Example .env File

```bash
# Development Ports
APP_PORT=8000
FE_PORT=3000
PMA_PORT=8080
DB_TEST_PORT=3307

# Database Configuration
DB_ROOT_PASSWORD=root_password
DB_DATABASE=lanocrm_shop
DB_USER=lanocrm_user
DB_PASSWORD=KP7n4RjcDbedSE2W8GgA

# Staging Ports (optional)
STAGING_FE_PORT=80
STAGING_DB_PORT=3308
STAGING_DB_TEST_PORT=3309
STAGING_PMA_PORT=8081
```

---

## 📚 Available Deployment Scripts

### 1. deploy-dev.sh
Full development deployment with branch checkout, build, and migration.

```bash
# Deploy default branch (Feat/BE)
./scripts/deploy-dev.sh

# Deploy specific branch
./scripts/deploy-dev.sh feature/new-feature
./scripts/deploy-dev.sh hotfix/bug-fix
```

**What it does:**
- Stops existing containers
- Pulls latest code from specified branch
- Builds and starts containers
- Runs migrations and seeds demo data
- Performs health checks

### 2. deploy-staging.sh
Staging deployment from main branch.

```bash
./scripts/deploy-staging.sh
```

**What it does:**
- Deploys from `main` branch only
- Uses production-like configuration
- Runs migrations
- Seeds demo data
- Health checks frontend and backend

### 3. dev-up.sh
Quick start for development (no git operations).

```bash
./scripts/dev-up.sh
```

**What it does:**
- Starts containers without rebuild
- Displays access URLs
- Faster than deploy-dev.sh

### 4. verify-deployment.sh
Verify deployment health.

```bash
# Verify dev
./scripts/verify-deployment.sh dev

# Verify staging
./scripts/verify-deployment.sh staging
```

**Checks:**
- Container status
- Database connection
- Database schema
- API health
- Frontend accessibility

---

## ✅ Pre-Deployment Checklist

### Development

- [ ] Docker and Docker Compose installed
- [ ] Ports available (check with `netstat -tulpn | grep :PORT`)
- [ ] At least 8GB RAM available
- [ ] `.env` file configured (if custom ports needed)

### Staging

- [ ] Backup database: `./scripts/db-backup.sh` (if script exists)
- [ ] Test on development first
- [ ] `.env` file prepared with staging ports
- [ ] Strong passwords set (change from defaults)
- [ ] Firewall rules configured

---

## 🗄️ Database Access

| Environment | Host | Port | Database | User | Password |
|-------------|--------|-------|----------|-------|----------|
| Development | localhost | 3306 | lanocrm_dev | lanocrm_user | KP7n4RjcDbedSE2W8GgA |
| Staging | localhost | 3308 | lanocrm_staging | lanocrm_user | KP7n4RjcDbedSE2W8GgA |
| Test DB (Dev) | localhost | 3307 | lanocrm_test | lanocrm_user | KP7n4RjcDbedSE2W8GgA |
| Test DB (Staging) | localhost | 3309 | lanocrm_test | lanocrm_user | KP7n4RjcDbedSE2W8GgA |

**Connect via phpMyAdmin:**
- Dev: http://localhost:8080
- Staging: http://localhost:8081

---

## 🔍 Troubleshooting

### Port Conflicts

If you see "port already in use":

```bash
# Check what's using the port
netstat -tulpn | grep :3000
netstat -tulpn | grep :8000

# Option 1: Stop the conflicting service
# Option 2: Change port in .env file
```

### Container Not Starting

```bash
# Check container status
docker-compose ps

# View logs
docker-compose logs web
docker-compose logs fe
docker-compose logs db

# Restart specific service
docker-compose restart web
```

### Database Migration Issues

```bash
# Check migration status
./scripts/check-migration-status.sh

# View database logs
docker-compose logs db

# Re-run migrations manually
docker-compose exec web php spark migrate --all
```

### Login Returns 500 Error

**Problem:** Vite proxy cannot connect to backend

**Solution:** Check `docker-compose.override.yml`:

```yaml
# Should be:
environment:
  - VITE_API_URL=http://web  # ← Service name, NOT localhost
```

```bash
# After fixing, recreate container:
docker-compose up -d fe
```

### Frontend Not Loading

```bash
# Check if frontend container is running
docker-compose ps fe

# Check frontend logs
docker-compose logs fe

# Restart frontend
docker-compose restart fe
```

### Database Connection Refused

```bash
# Check database container
docker-compose ps db

# Wait for database to be ready (takes ~10-30 seconds)
docker-compose logs db | grep "ready for connections"

# Restart backend after database is ready
docker-compose restart web
```

---

## 🎯 Best Practices

1. **Always use scripts** - They handle git, build, and health checks
2. **Test locally first** - Before deploying to staging
3. **Use .env for customization** - Never hardcode ports in files
4. **Separate databases** - Dev and staging should never share data
5. **Check logs** - When something goes wrong: `docker-compose logs [service]`
6. **Verify after deployment** - Run `./scripts/verify-deployment.sh`

---

## 🔄 Common Workflows

### Starting Fresh Development

```bash
# 1. Clone repository
git clone <repository-url>
cd kiotviet

# 2. (Optional) Create custom .env
cp .env.example .env
nano .env  # Edit if needed

# 3. Deploy
./scripts/deploy-dev.sh

# 4. Verify
./scripts/verify-deployment.sh dev
```

### Switching Branches

```bash
# Use deploy script with branch name
./scripts/deploy-dev.sh feature/new-feature
```

### Running Both Dev and Staging

```bash
# 1. Ensure ports don't conflict (use .env)
# Dev uses: 3000, 8000, 8080, 3307
# Staging uses: 80, 3308, 3309, 8081

# 2. Start dev
./scripts/deploy-dev.sh

# 3. Start staging
./scripts/deploy-staging.sh

# Both can run simultaneously!
```

### Stopping Environments

```bash
# Stop dev
docker-compose -f docker-compose.yml -f docker-compose.override.yml down

# Stop staging
docker-compose -f docker-compose.yml -f docker-compose.staging.yml down

# Stop and remove volumes (clean slate)
docker-compose down -v
```

---

## 📊 Service Names Reference

| Service | Container Name | Purpose |
|---------|----------------|---------|
| `web` | kiotviet-web-1 | Backend API (CodeIgniter) |
| `fe` | kiotviet-fe-1 | Frontend (React + Vite) |
| `db` | kiotviet-db-1 | MySQL Database |
| `db-test` | kiotviet-db-test-1 | Test Database |
| `phpmyadmin` | kiotviet-phpmyadmin-1 | Database UI |

**Important:** Always use `web` (not `api`) when referencing backend service.

---

## 🔐 Default Credentials

**Login:**
- Username: `devadmin`
- Password: `123aA@hai`

**Database:**
- User: `lanocrm_user`  
- Password: `KP7n4RjcDbedSE2W8GgA`

> ⚠️ **Security:** Change these in production!

---

## 📁 Related Documentation

- **[Root Cause Analysis](docs/root-cause-analysis/)** - Troubleshooting history
- **[Testing Guide](docs/testing/TESTING-GUIDE.md)** - How to run tests
- **[AGENTS.md](AGENTS.md)** - Development guide
- **[Refactor Walkthrough](/.gemini/antigravity/brain/*/walkthrough.md)** - Recent changes

---

## 🆘 Emergency Procedures

### Rollback Development

```bash
# 1. Stop containers
docker-compose down

# 2. Checkout previous version
git checkout <previous-commit>

# 3. Redeploy
./scripts/deploy-dev.sh
```

### Reset Database

```bash
# 1. Stop containers
docker-compose down

# 2. Remove database volume
docker volume rm kiotviet_db_dev_data

# 3. Start fresh
./scripts/deploy-dev.sh
```

---

**Last Updated:** 2025-12-02  
**Maintained By:** Development Team
