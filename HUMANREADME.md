# LANO CRM - Development Setup

React + CodeIgniter 4 CRM System

## 🚀 Quick Start (Development)

### Prerequisites
- Docker & Docker Compose
- Node.js 18+ & npm
- 8GB RAM minimum
- 20GB free disk space

### Start All Services (Recommended)
```bash
# Start all services
docker compose up -d --build

# Monitor startup (wait for migration to complete)
docker compose logs -f api

# Verify setup
chmod +x scripts/check-migration-status.sh
./scripts/check-migration-status.sh
```

**What happens automatically:**
- ✅ Database starts and waits for readiness
- ✅ Migrations run automatically (first time only)
- ✅ Demo data seeded automatically (development only)
- ✅ API server starts on port 8000
- ✅ Frontend dev server starts on port 3000
- ✅ phpMyAdmin available on port 8080

**Access points:**
- Frontend: http://localhost:3000
- Backend API: http://localhost:8000/api
- phpMyAdmin: http://localhost:8080
- Database: localhost:3306

### Start Backend Only
```bash
docker compose up -d db api
```
- API: http://localhost:8000/api
- Database: MySQL 8.4

### Start Frontend (if not using Docker)
```bash
cd lanocrm
npm install
npm run dev
```
- Frontend: http://localhost:3000 (Docker) or http://localhost:5173 (local)

## Default Credentials

**Admin Account:**
- Username: `admin`
- Password: `123aA@hai`

**API Login:**
```bash
POST http://localhost:8000/api/auth/login
{
  "username": "admin",
  "password": "123aA@hai"
}
```

## Docker Workflow (IMPORTANT)

### ✅ DO THIS - Use Docker containers
```bash
# Start all services
docker compose up -d

# Run commands in Docker
docker exec meomeo2-api-1 php spark migrate
docker exec meomeo2-api-1 vendor/bin/phpunit

# Or use helper scripts
./backend-ci/docker-spark migrate
./backend-ci/docker-php --version
```

### ❌ DON'T DO THIS - WSL/Local PHP
```bash
# WRONG - Don't run PHP directly when Docker is running
cd backend-ci && php spark serve
```

## 📊 Database & Seeding

### Automatic Setup (First Start)

Database is automatically initialized on first container start:
1. Waits for database connection (max 60s)
2. Runs migrations if not already run
3. Seeds demo data (development only)
4. Creates marker file to prevent re-running

### Manual Seeding

**Re-seed demo data:**
```bash
docker exec meomeo2-api-1 php spark db:seed DevDemoSeeder
```

**Reset database completely:**
```bash
# Remove marker to trigger re-initialization
docker exec meomeo2-api-1 rm -f /var/www/html/writable/.db_initialized

# Restart container
docker compose restart api

# Watch logs
docker compose logs -f api
```

**Demo data includes:**
- Users: devadmin/Admin@123, manager1, viewer1
- Branches, warehouses, categories
- Attributes & options
- Sample products with variants & images
- Demo customers, orders, invoices
- Cash transactions, price lists

### Migration Status

**Check migration status:**
```bash
./scripts/check-migration-status.sh
```

**View migration logs:**
```bash
docker exec meomeo2-api-1 cat /tmp/migration.log
```

**For detailed troubleshooting:**
See [`docs/MIGRATION-TROUBLESHOOTING.md`](docs/MIGRATION-TROUBLESHOOTING.md)

## Testing

### Backend Tests

**Unit Tests (MySQL with transactions):**
```bash
docker exec meomeo2-api-1 vendor/bin/phpunit
```

**Integration Tests (MySQL full stack):**
```bash
docker exec meomeo2-api-1 vendor/bin/phpunit -c phpunit.integration.xml
```

**Route Coverage Test:**
```bash
docker exec meomeo2-api-1 vendor/bin/phpunit --filter ApiRoutesTest
```

**Validate Models (check for db_ prefixes):**
```bash
docker exec meomeo2-api-1 php spark validate:models
```

### Frontend Tests

```bash
cd lanocrm
npm test
npm run test:coverage
npm run test:e2e
```

## File Uploads

Symlink required for serving uploaded files:
```bash
cd backend-ci/public
ln -s ../writable/uploads uploads
```

## Tech Stack

### Backend
- PHP 8.4
- CodeIgniter 4.5
- MySQL 8.4
- JWT Authentication
- PHPUnit 10

### Frontend
- React 18
- Vite
- Redux Toolkit
- React Router
- Vitest + Playwright

### Infrastructure
- Docker & Docker Compose
- Nginx (legacy proxy)

## Project Structure

```
meomeo2/
├── backend-ci/          # CodeIgniter 4 API
│   ├── app/
│   │   ├── Controllers/Api/
│   │   ├── Services/
│   │   ├── Repositories/
│   │   ├── Models/
│   │   └── Validators/
│   ├── tests/
│   └── public/
├── lanocrm/            # React Frontend
│   ├── src/
│   └── tests/
└── docs/               # Documentation
```

## 🔧 Common Issues & Solutions

### Migration Issues

**Problem:** Tables missing after deploy
**Solution:** Check migration status and re-run if needed
```bash
./scripts/check-migration-status.sh
docker exec meomeo2-api-1 rm -f /var/www/html/writable/.db_initialized
docker compose restart api
```

**Problem:** Migration timeout
**Solution:** Check logs and database performance
```bash
docker exec meomeo2-api-1 cat /tmp/migration.log
docker compose logs db
```

**For detailed troubleshooting:** See [`docs/MIGRATION-TROUBLESHOOTING.md`](docs/MIGRATION-TROUBLESHOOTING.md)

### Docker Environment Issues

**Problem:** "Table 'lanocrm_shop.db_products' doesn't exist"
**Solution:** Always use Docker commands, not local PHP
```bash
# Correct
docker exec meomeo2-api-1 php spark migrate

# Wrong
cd backend-ci && php spark migrate
```

**Problem:** Container won't start
**Solution:** Check logs and rebuild
```bash
docker compose logs api
docker compose up -d --build
```

### Database Connection Issues

**Problem:** "Connection refused"
**Solution:** Check containers are running
```bash
docker ps
docker compose restart db
sleep 10
docker compose restart api
```

**Problem:** Database not ready
**Solution:** Wait for database initialization
```bash
docker compose logs -f db  # Wait for "ready for connections"
```

### Authentication
Current auth is development-level (JWT). Production deployment will need:
- Secure JWT secret rotation
- Proper permissions/roles enforcement
- Rate limiting
- HTTPS

### Xdebug
Xdebug is enabled in container for coverage reporting.

## Development Notes

- Backend follows Clean Architecture (Controller → Service → Repository → Model)
- Frontend uses Redux for state management
- All API endpoints require JWT token (except auth endpoints)
- Soft deletes used throughout (`deleted_at` column)
- **Always work through Docker containers**
- **Use helper scripts in backend-ci/docker-* for consistency**

## 🛠️ Useful Commands

### Container Management
```bash
# Check container status
docker ps

# View logs
docker compose logs -f api
docker compose logs -f fe
docker compose logs -f db

# Restart services
docker compose restart api
docker compose restart db

# Rebuild containers
docker compose up -d --build
```

### Database Operations
```bash
# Access database
docker exec -it meomeo2-db-1 mysql -u lanocrm_user -p lanocrm_shop

# Backup database
./scripts/db-backup.sh

# Restore database
./scripts/db-restore.sh backups/latest.sql

# Check migration status
./scripts/check-migration-status.sh

# View migration log
docker exec meomeo2-api-1 cat /tmp/migration.log
```

### Development
```bash
# Run migrations
docker exec meomeo2-api-1 php spark migrate

# Seed demo data
docker exec meomeo2-api-1 php spark db:seed DevDemoSeeder

# Validate models
docker exec meomeo2-api-1 php spark validate:models

# Run tests
docker exec meomeo2-api-1 vendor/bin/phpunit

# Reset everything
docker compose down -v && docker compose up -d --build
```

### Debugging
```bash
# Check migration marker
docker exec meomeo2-api-1 test -f /var/www/html/writable/.db_initialized && echo "Initialized" || echo "NOT initialized"

# Count tables
docker exec meomeo2-db-1 mysql -u lanocrm_user -pKP7n4RjcDbedSE2W8GgA lanocrm_shop -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'lanocrm_shop'"

# Check disk space
docker system df

# View container stats
docker stats
```

## 📚 Documentation

### Core Guides
- **Architecture:** [`AGENTS.md`](AGENTS.md) - Complete development guide
- **Testing:** [`docs/testing/TESTING-GUIDE.md`](docs/testing/TESTING-GUIDE.md)
- **Docker Workflow:** [`docs/testing/docker-workflow-guide.md`](docs/testing/docker-workflow-guide.md)

### Deployment & DevOps
- **Migration Guide:** [`docs/MIGRATION-TROUBLESHOOTING.md`](docs/MIGRATION-TROUBLESHOOTING.md)
- **Quick Reference:** [`docs/MIGRATION-QUICK-REFERENCE.md`](docs/MIGRATION-QUICK-REFERENCE.md)
- **Seeding Strategy:** [`docs/SEEDING-STRATEGY.md`](docs/SEEDING-STRATEGY.md)
- **Deployment by Environment:** [`docs/DEPLOYMENT-BY-ENVIRONMENT.md`](docs/DEPLOYMENT-BY-ENVIRONMENT.md)
- **Production Deployment:** [`DEPLOYMENT.md`](DEPLOYMENT.md) (generated by deploy.sh)

### Testing Documentation
- **Backend Testing:** [`docs/testing/TESTING-PATTERNS.md`](docs/testing/TESTING-PATTERNS.md)
- **Frontend Testing:** [`docs/testing/FE-TESTING-GUIDE.md`](docs/testing/FE-TESTING-GUIDE.md)
- **Test Checklists:** [`docs/testing/TEST-CHECKLIST.md`](docs/testing/TEST-CHECKLIST.md)

### Audit Reports
- **Test Coverage:** [`docs/audits/2025-11-27-FINAL-TEST-COVERAGE-REPORT.md`](docs/audits/2025-11-27-FINAL-TEST-COVERAGE-REPORT.md)
- **Documentation Index:** [`docs/DOCUMENTATION_INDEX.md`](docs/DOCUMENTATION_INDEX.md)

## License

Private/Commercial Project
