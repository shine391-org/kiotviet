# Hướng dẫn Setup LANO CRM cho Jules (VM Environment)

## 📋 Tổng quan

Dự án LANO CRM sử dụng Docker Compose để chạy toàn bộ stack development. Tất cả services đã được container hóa để đảm bảo môi trường nhất quán.

## 🏗️ Kiến trúc Docker

```
meomeo2/
├── docker-compose.yml          # Main orchestration
├── .gitignore                   # Root ignore rules
├── backend-ci/                  # PHP/CodeIgniter 4 API
│   ├── Dockerfile              # Backend container
│   ├── .env                    # Backend environment
│   └── .gitignore              # PHP-specific ignores
├── lanocrm/                     # React Frontend
│   ├── Dockerfile              # Frontend container
│   ├── .gitignore              # Node.js-specific ignores
│   └── .dockerignore           # Docker build ignores
└── docs/                        # Documentation
```

## 🚀 Quick Start (5 phút)

### 1. Clone Repository
```bash
git clone <repository-url>
cd meomeo2
```

### 2. Start All Services
```bash
docker-compose up -d
```

### 3. Verify Services
```bash
# Check all containers running
docker-compose ps

# Check logs if needed
docker-compose logs -f
```

## 🌐 Access Points

| Service | URL | Description |
|---------|-----|-------------|
| Frontend (React) | http://localhost:3000 | Main CRM interface |
| Backend API | http://localhost:8000 | REST API endpoints |
| phpMyAdmin | http://localhost:8080 | Database management |
| Database (MySQL) | localhost:3306 | Main database |
| Test Database | localhost:3307 | Testing database |

## 🔧 Development Workflow

### Frontend Development
```bash
# View frontend logs
docker-compose logs -f fe

# Access frontend container
docker-compose exec fe bash

# Install new packages
docker-compose exec fe npm install <package-name>

# Run frontend tests
docker-compose exec fe npm test
```

### Backend Development
```bash
# View backend logs
docker-compose logs -f api

# Access backend container
docker-compose exec api bash

# Run migrations
docker-compose exec api php spark migrate

# Run backend tests
docker-compose exec api vendor/bin/phpunit

# Seed demo data
docker-compose exec api php spark db:seed DevDemoSeeder
```

### Database Operations
```bash
# Access database container
docker-compose exec db mysql -u lanocrm_user -p lanocrm_shop

# Import database (if needed)
docker-compose exec -T db mysql -u lanocrm_user -p lanocrm_shop < backup.sql

# Export database
docker-compose exec db mysqldump -u lanocrm_user -p lanocrm_shop > backup.sql
```

## 📁 Important Files & Directories

### Environment Configuration
- `backend-ci/.env` - Backend environment variables
- `docker-compose.yml` - Service definitions
- `.gitignore` - Git ignore rules (optimized for Docker)

### Development Directories
- `backend-ci/app/` - PHP application code
- `lanocrm/src/` - React application code
- `docs/` - Project documentation

### Temporary Directories (Ignored by Git)
- `uploads/` - Temporary file uploads
- `logs/` - Application logs
- `temp/` - Temporary files

## 🔄 Common Development Tasks

### Adding New Dependencies

**Frontend (Node.js):**
```bash
docker-compose exec fe npm install <package>
docker-compose restart fe
```

**Backend (PHP):**
```bash
docker-compose exec api composer require <package>
docker-compose restart api
```

### Running Tests

**Frontend Tests:**
```bash
# Unit tests
docker-compose exec fe npm test

# E2E tests
docker-compose exec fe npm run test:e2e

# Coverage
docker-compose exec fe npm run test:coverage
```

**Backend Tests:**
```bash
# Unit tests
docker-compose exec api vendor/bin/phpunit

# Integration tests
docker-compose exec api vendor/bin/phpunit -c phpunit.integration.xml

# Coverage
docker-compose exec api vendor/bin/phpunit --coverage-text
```

### Database Management

**Run Migrations:**
```bash
docker-compose exec api php spark migrate
```

**Seed Demo Data:**
```bash
docker-compose exec api php spark db:seed DevDemoSeeder
```

**Reset Database:**
```bash
docker-compose exec api php spark migrate:fresh --seed
```

## 🐛 Troubleshooting

### Common Issues

**1. Port conflicts:**
```bash
# Check what's using ports
netstat -tulpn | grep :3000
netstat -tulpn | grep :8000

# Kill processes if needed
sudo kill -9 <PID>
```

**2. Container won't start:**
```bash
# Check logs
docker-compose logs <service-name>

# Rebuild containers
docker-compose down
docker-compose up --build
```

**3. Database connection issues:**
```bash
# Check database container
docker-compose exec db mysql -u lanocrm_user -p

# Reset database volume
docker-compose down -v
docker-compose up -d db
```

**4. Frontend build issues:**
```bash
# Clear node_modules
docker-compose exec fe rm -rf node_modules
docker-compose exec fe npm install
docker-compose restart fe
```

### Performance Tips

**1. Use Docker volumes for development:**
```bash
# Volumes are already configured in docker-compose.yml
# Changes to code are reflected immediately
```

**2. Monitor resource usage:**
```bash
# Check container stats
docker stats

# Check disk usage
docker system df
```

## 📝 Git Workflow

### Best Practices

1. **Always check .gitignore before committing:**
   ```bash
   git status
   git add .
   git commit -m "Your commit message"
   ```

2. **Never commit sensitive files:**
   - Environment files (.env)
   - Database dumps
   - Temporary files
   - Build artifacts

3. **Use feature branches:**
   ```bash
   git checkout -b feature/your-feature-name
   # Make changes
   git add .
   git commit -m "feat: add new feature"
   git push origin feature/your-feature-name
   ```

### Ignored Files

The following files/directories are automatically ignored:
- OS files (.DS_Store, Thumbs.db)
- Database dumps (db_lano*, *.sql)
- Build artifacts (node_modules/, vendor/, dist/)
- Temporary files (logs/, temp/, uploads/)
- IDE files (.vscode/, .idea/)
- Environment files (.env*)

## 🎯 Next Steps

1. **Explore the codebase:**
   - Backend: `backend-ci/app/Controllers/Api/`
   - Frontend: `lanocrm/src/`
   - Documentation: `docs/`

2. **Read the architecture guide:**
   - `AGENTS.md` - AI agent guidelines
   - `docs/plans/BACKEND-REFACTOR-PLAN.md` - Backend architecture

3. **Check testing guidelines:**
   - `docs/testing/TESTING-GUIDE.md` - Testing philosophy
   - `docs/testing/TESTING-PATTERNS.md` - Test patterns

4. **Start development:**
   - Make changes to code
   - Run tests
   - Commit changes

## 📞 Support

If you encounter any issues:
1. Check this guide first
2. Look at `docs/` for detailed documentation
3. Check container logs: `docker-compose logs`
4. Ask the team for help

---

**Note:** This setup is optimized for Docker development. All dependencies are containerized, so you don't need to install PHP, Node.js, or MySQL locally on your VM.