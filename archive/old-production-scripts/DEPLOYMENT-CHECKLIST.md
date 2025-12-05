# Deployment Checklist for LanoCRM

## Prerequisites
- [ ] Docker và Docker Compose đã được cài đặt
- [ ] Git đã được cài đặt
- [ ] Có quyền truy cập vào repository

## Steps for Fresh Deployment

### 1. Clone Repository
```bash
git clone https://github.com/shine391-org/kiotviet.git
cd kiotviet
git checkout Feat/BE
```

### 2. Environment Setup
```bash
# Copy environment file
cp backend-ci/.env.example backend-ci/.env

# Create necessary directories
mkdir -p logs/api logs/mysql logs/mysql-test
mkdir -p backups/mysql
```

### 3. Development Deployment
```bash
docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d --build
```

### 4. Production Deployment
```bash
./deploy.sh
```

## Common Issues and Solutions

### Issue 1: Volume Mount Error
**Error**: `failed to create mountpoint for /var/www/html/uploads mount: make mountpoint "/var/www/html/uploads": read-only file system`

**Solution**: 
- Ensure `docker-compose.prod.yml` mounts individual directories instead of the entire backend-ci directory
- Fixed in current version by mounting specific directories with read-only flag

### Issue 2: Docker Start Script Path Error
**Error**: `/usr/local/bin/docker-start.sh: line 5: cd: /var/www/html/backend-ci: No such file or directory`

**Solution**:
- Update docker-start.sh to use `/var/www/html` instead of `/var/www/html/backend-ci`
- Fixed in current version

### Issue 3: Nginx Upstream Error
**Error**: `host not found in upstream "api" in /etc/nginx/conf.d/default.conf`

**Solution**:
- Update nginx.conf to use correct service name ("web" instead of "api")
- Fixed in current version

### Issue 4: Frontend Command Error
**Error**: `sh: npm: not found`

**Solution**:
- Add explicit nginx command in docker-compose.prod.yml
- Fixed in current version

## Verification Steps

### Check Container Status
```bash
docker-compose ps
```
Expected: All containers should be "Up" and "healthy"

### Check Backend API
```bash
curl -s http://localhost:8000/backend-ci/api/health
```
Expected: JSON response with health status

### Check Frontend
```bash
curl -s http://localhost:3000
```
Expected: HTML response with React app

### Check Database
```bash
docker-compose logs db | grep "ready for connections"
```
Expected: MySQL ready message

## Troubleshooting

### If containers fail to start:
1. Check logs: `docker-compose logs [service-name]`
2. Verify Docker is running: `docker version`
3. Check ports availability: `netstat -tulpn | grep :8000`

### If database connection fails:
1. Wait for database to be ready: `docker-compose logs db`
2. Check environment variables in backend-ci/.env
3. Verify database credentials

### If frontend cannot connect to backend:
1. Check nginx configuration in lanocrm/nginx.conf
2. Verify service names in docker-compose files
3. Check network connectivity between containers

## Clean Up (if needed)
```bash
# Stop all containers
docker-compose down

# Remove volumes (WARNING: This deletes all data)
docker-compose down -v

# Remove images
docker-compose down --rmi all