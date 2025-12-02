# Deployment Guide by Environment

## 📋 Overview

This document explains how to deploy and manage different environments in the KiotViet CRM system.

## 🏗️ Environment Architecture

We use a multi-environment setup with Docker Compose override files to separate concerns:

```
├── docker-compose.yml              # Base configuration
├── docker-compose.dev.yml      # Development overrides
├── docker-compose.staging.yml       # Staging overrides
└── docker-compose.prod.yml          # Production overrides (legacy)
```

## 🚀 Environment Types

### 1. Development Environment

**Purpose**: Local development with hot reload and debugging

**Configuration Files**:
- `docker-compose.yml` (base)
- `docker-compose.dev.yml` (dev-specific)

**Database**: `lanocrm_dev` (Port 3306)

**Ports**:
- Frontend: http://localhost:3000
- Backend: http://localhost:8000
- Database: localhost:3306
- Test DB: localhost:3307
- phpMyAdmin: http://localhost:8080

**Features**:
- Hot reload for frontend
- Source code mounted for live editing
- Debug logs enabled
- Slow query logging enabled

**Deployment Commands**:
```bash
# Start development environment
docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d

# View logs
docker-compose -f docker-compose.yml -f docker-compose.dev.yml logs -f

# Stop development environment
docker-compose -f docker-compose.yml -f docker-compose.dev.yml down
```

### 2. Staging Environment

**Purpose**: Production-like testing environment

**Configuration Files**:
- `docker-compose.yml` (base)
- `docker-compose.staging.yml` (staging-specific)

**Database**: `lanocrm_staging` (Port 3308)

**Ports**:
- Frontend: http://localhost (port 80)
- Backend: http://localhost/api (nginx proxy)
- Database: localhost:3308
- Test DB: localhost:3309
- phpMyAdmin: http://localhost:8081

**Features**:
- Production build (optimized)
- No source code mounting
- Limited logging
- Resource limits applied
- Separate database from development

**Deployment Commands**:
```bash
# Deploy staging environment
./scripts/deploy-staging.sh

# Or manually:
docker-compose -f docker-compose.yml -f docker-compose.staging.yml up -d --build

# View logs
docker-compose -f docker-compose.yml -f docker-compose.staging.yml logs -f

# Stop staging environment
docker-compose -f docker-compose.yml -f docker-compose.staging.yml down
```

### 3. Production Environment (Legacy)

**Purpose**: Production deployment (legacy configuration)

**Configuration Files**:
- `docker-compose.yml` (base)
- `docker-compose.prod.yml` (production-specific)

**Database**: `lanocrm_shop` (Port 3306)

**Note**: This configuration is being phased out in favor of the staging environment.

## 🗄️ Database Separation

| Environment | Database | Port | Volume | Purpose |
|-------------|----------|------|--------|---------|
| Development | lanocrm_dev | 3306 | db_dev_data | Local development |
| Test | lanocrm_test | 3307 | test_db_data | Unit/Integration tests |
| Staging | lanocrm_staging | 3308 | db_staging_data | Production-like testing |
| Production (Legacy) | lanocrm_shop | 3306 | db_data | Production data |

## 🔄 Git Branch to Environment Mapping

```
GitHub Branch → Environment
├── Feat/BE → Development (local)
├── feature/* → Development (local)
└── main → Staging (production-like)
```

## 🛠️ Deployment Scripts

### Development Deployment

```bash
# Quick start development
docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d

# With rebuild
docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d --build
```

### Staging Deployment

```bash
# Automated staging deployment
./scripts/deploy-staging.sh

# Manual staging deployment
docker-compose -f docker-compose.yml -f docker-compose.staging.yml up -d --build
```

## 🔧 Environment Variables

### Development Environment Variables
```yaml
CI_ENVIRONMENT: development
LOG_THRESHOLD: 4
DB_NAME: lanocrm_dev
NODE_ENV: development
VITE_API_URL: http://localhost:8000
MYSQL_SLOW_QUERY_LOG: 1
MYSQL_LONG_QUERY_TIME: 2
```

### Staging Environment Variables
```yaml
CI_ENVIRONMENT: staging
LOG_THRESHOLD: 2
DB_NAME: lanocrm_staging
NODE_ENV: staging
VITE_API_BASE_URL: /api
MYSQL_INNODB_BUFFER_POOL_SIZE: 1G
MYSQL_MAX_CONNECTIONS: 200
```

## 📊 Resource Allocation

### Development Resources
- **Backend**: No limits (development)
- **Frontend**: No limits (development)
- **Database**: Default limits

### Staging Resources
- **Backend**: 512MB limit, 256MB reservation
- **Frontend**: 256MB limit, 128MB reservation
- **Database**: 1GB limit, 512MB reservation

## 🔍 Troubleshooting

### Port Conflicts
If you encounter port conflicts, check which ports are in use:

```bash
# Check all Docker ports
docker ps --format "table {{.Names}}\t{{.Ports}}"

# Check specific ports
netstat -tulpn | grep :3000
netstat -tulpn | grep :80
netstat -tulpn | grep :3306
```

### Database Issues
```bash
# Check database logs
docker-compose -f docker-compose.yml -f docker-compose.staging.yml logs db

# Connect to database
docker-compose -f docker-compose.yml -f docker-compose.staging.yml exec db mysql -u lanocrm_user -pKP7n4RjcDbedSE2W8GgA lanocrm_staging
```

### Container Issues
```bash
# Check container status
docker-compose -f docker-compose.yml -f docker-compose.staging.yml ps

# View container logs
docker-compose -f docker-compose.yml -f docker-compose.staging.yml logs [service_name]

# Restart specific service
docker-compose -f docker-compose.yml -f docker-compose.staging.yml restart [service_name]
```

## 🚀 Best Practices

1. **Always use the appropriate environment** for your workflow
2. **Never commit sensitive data** to any environment
3. **Use staging for testing** before deploying to production
4. **Keep databases separate** to avoid data contamination
5. **Monitor resource usage** in staging to catch performance issues early
6. **Use environment-specific variables** for configuration
7. **Test migrations** in staging before running in production

## 📝 Migration from Legacy Production

If you're migrating from the legacy production setup:

1. **Backup existing data** from `lanocrm_shop` database
2. **Deploy staging environment** using the new configuration
3. **Migrate data** to `lanocrm_staging` database
4. **Test thoroughly** in staging
5. **Update deployment scripts** to use new configuration
6. **Decommission legacy setup** after successful migration

## 🔄 Environment Switching

To switch between environments:

```bash
# Stop current environment
docker-compose -f docker-compose.yml -f docker-compose.dev.yml down
# OR
docker-compose -f docker-compose.yml -f docker-compose.staging.yml down

# Start desired environment
docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d
# OR
docker-compose -f docker-compose.yml -f docker-compose.staging.yml up -d
```

## 📚 Additional Resources

- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Environment Variables Guide](../ENVIRONMENT-VARIABLES.md)
- [Database Management Guide](../DATABASE-MANAGEMENT.md)
- [Troubleshooting Guide](../TROUBLESHOOTING.md)