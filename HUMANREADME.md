# LANO CRM - Development Setup

React + CodeIgniter 4 CRM System

## Quick Start (Development)

### Prerequisites
- Docker & Docker Compose
- Node.js 18+ & npm

### Start Backend & Database
```bash
docker compose up -d db api
```
- API: http://localhost:8000/api
- Database: MySQL 8.4

### Start Frontend
```bash
cd lanocrm
npm install
npm run dev
```
- Frontend: http://localhost:5173

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

**Legacy Frontend (if needed):**
```bash
POST http://localhost:3000/backend-ci/api/users/login
```

## Seed Sample Data

```bash
docker exec -it meomeo2-api-1 php spark db:seed DevSeeder
```

Creates:
- Users: devadmin/Admin@123, manager1, viewer1
- Branches, categories
- Attributes & options
- Sample products with variants & images

## Testing

### Backend Tests

**Unit Tests (SQLite in-memory):**
```bash
docker exec meomeo2-api-1 vendor/bin/phpunit
```

**Integration Tests (MySQL):**
```bash
docker exec meomeo2-api-1 vendor/bin/phpunit -c phpunit.integration.xml
```

**Route Coverage Test:**
```bash
docker exec meomeo2-api-1 vendor/bin/phpunit --filter ApiRoutesTest
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

## Common Issues

### Docker Warnings
The `version` field in `docker-compose.yml` is deprecated but harmless. You can remove it if desired.

### Authentication
Current auth is development-level (JWT). Production deployment will need:
- Secure JWT secret rotation
- Proper permissions/roles enforcement
- Rate limiting
- HTTPS

### Xdebug
Xdebug is enabled in the container for coverage reporting.

## Development Notes

- Backend follows Clean Architecture (Controller → Service → Repository → Model)
- Frontend uses Redux for state management
- All API endpoints require JWT token (except auth endpoints)
- Soft deletes used throughout (`deleted_at` column)

## License

Private/Commercial Project
