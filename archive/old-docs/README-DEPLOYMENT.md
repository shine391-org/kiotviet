# 🚀 KiotViet CRM Deployment Guide

## 📋 Overview

This project uses a **Dev + Staging** environment setup with Docker Compose override files. Production environment is not currently supported.

## 🏗️ Environment Structure

```
├── docker-compose.yml              # Base configuration
├── docker-compose.override.yml      # Development overrides
├── docker-compose.staging.yml       # Staging overrides
└── scripts/
    ├── deploy-dev.sh              # Development deployment
    └── deploy-staging.sh           # Staging deployment
```

## 🚀 Quick Start

### Development Environment
```bash
# Deploy from default branch (Feat/BE)
./scripts/deploy-dev.sh

# Deploy from specific branch
./scripts/deploy-dev.sh feature/new-feature
./scripts/deploy-dev.sh hotfix/bug-fix
```

### Staging Environment
```bash
# Deploy from main branch
./scripts/deploy-staging.sh
```

## 📊 Environment Details

| Environment | Database | Frontend Port | Backend Port | Git Branch |
|-------------|----------|----------------|---------------|-------------|
| Development | lanocrm_dev | 3000 | 8000 | Parameter (default: Feat/BE) |
| Staging | lanocrm_staging | 80 | 80 (nginx proxy) | main |

## 🔧 Useful Commands

### Development
```bash
# Start development
./scripts/deploy-dev.sh

# View logs
docker-compose -f docker-compose.yml -f docker-compose.override.yml logs -f

# Stop development
docker-compose -f docker-compose.yml -f docker-compose.override.yml down
```

### Staging
```bash
# Start staging
./scripts/deploy-staging.sh

# View logs
docker-compose -f docker-compose.yml -f docker-compose.staging.yml logs -f

# Stop staging
docker-compose -f docker-compose.yml -f docker-compose.staging.yml down
```

## 🗄️ Database Access

| Environment | Host | Port | Database | User | Password |
|-------------|--------|-------|----------|-------|----------|
| Development | localhost | 3306 | lanocrm_dev | lanocrm_user | KP7n4RjcDbedSE2W8GgA |
| Staging | localhost | 3308 | lanocrm_staging | lanocrm_user | KP7n4RjcDbedSE2W8GgA |
| Test DB | localhost | 3307 | lanocrm_test | lanocrm_user | KP7n4RjcDbedSE2W8GgA |

## 🌐 Access URLs

| Environment | Frontend | Backend API | phpMyAdmin |
|-------------|-----------|--------------|-------------|
| Development | http://localhost:3000 | http://localhost:8000 | http://localhost:8080 |
| Staging | http://localhost | http://localhost/api | http://localhost:8081 |

## 📚 Documentation

- **[Deployment by Environment](docs/DEPLOYMENT-BY-ENVIRONMENT.md)** - Detailed environment guide
- **[Testing Guide](docs/testing/TESTING-GUIDE.md)** - Testing documentation
- **[Migration Guide](docs/MIGRATION-TROUBLESHOOTING.md)** - Database migration help

## 🔄 Workflow

1. **Development**: Work on feature branch → Deploy with `./scripts/deploy-dev.sh feature-name`
2. **Testing**: Create PR → Merge to main → Deploy with `./scripts/deploy-staging.sh`

## 🗂️ Archive

Old production scripts and configurations have been moved to `archive/old-production-scripts/` for reference.

## ❓ Troubleshooting

### Port conflicts
```bash
# Check what's using ports
netstat -tulpn | grep :3000
netstat -tulpn | grep :80
netstat -tulpn | grep :3306
```

### Database issues
```bash
# Check database logs
docker-compose logs db

# Connect to database
docker-compose exec db mysql -u lanocrm_user -pKP7n4RjcDbedSE2W8GgA lanocrm_dev
```

### Container issues
```bash
# Check container status
docker-compose ps

# View container logs
docker-compose logs [service_name]

# Restart specific service
docker-compose restart [service_name]
```

## 🎯 Best Practices

1. **Always use the correct script** for your environment
2. **Keep databases separate** - never share data between environments
3. **Test in staging** before making changes to main branch
4. **Use feature branches** for development work
5. **Check logs** if something goes wrong
6. **Clean up** old containers and images regularly

---
*Last updated: 2025-12-02*