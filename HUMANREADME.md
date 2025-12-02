# LANO CRM - Development Setup

React + CodeIgniter 4 CRM System

## Quick Start (Development)

### Prerequisites
- Docker & Docker Compose
- Node.js 18+ & npm

### Start All Services (Recommended)
```bash
docker compose up -d
```
This starts: API (port 8000), Frontend (port 3000), Database, phpMyAdmin (port 8080)

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

## Seed Sample Data

```bash
docker exec -it meomeo2-api-1 php spark db:seed DevDemoSeeder
```

Creates:
- Users: devadmin/Admin@123, manager1, viewer1
- Branches, categories
- Attributes & options
- Sample products with variants & images

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

## Common Issues & Solutions

### Docker Environment Issues
**Problem:** "Table 'lanocrm_shop.db_products' doesn't exist"
**Solution:** Always use Docker commands, not local PHP
```bash
# Correct
docker exec meomeo2-api-1 php spark serve

# Wrong
cd backend-ci && php spark serve
```

### Database Connection Issues
**Problem:** "Connection refused"
**Solution:** Check containers are running
```bash
docker ps
docker compose restart db
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

## Useful Commands

```bash
# Check container status
docker ps

# View logs
docker logs meomeo2-api-1

# Access database
docker exec -it meomeo2-db-1 mysql -u lanocrm_user -p lanocrm_shop

# Reset demo data
docker compose down -v && docker compose up -d

# Validate environment
docker exec meomeo2-api-1 php spark validate:models
```

## Documentation

- Docker Workflow Guide: `docs/testing/docker-workflow-guide.md`
- Testing Guide: `docs/testing/TESTING-GUIDE.md`
- Architecture Guide: `docs/AGENT-GUIDE-01.md`

## License

Private/Commercial Project
