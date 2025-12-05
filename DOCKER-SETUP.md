# Docker Setup Guide

Quick guide to deploy LANO CRM on a new machine.

## Prerequisites

- Docker Engine 24+
- Docker Compose v2+
- 4GB+ RAM (recommended 8GB)

## Quick Start

```bash
# 1. Clone and enter directory
git clone <repo-url> && cd kiotviet

# 2. Create .env from example
cp .env.example .env

# 3. Generate secure passwords
# Linux/Mac:
echo "DB_ROOT_PASSWORD=$(openssl rand -base64 32)" >> .env
echo "DB_PASSWORD=$(openssl rand -base64 32)" >> .env

# 4. Start services
docker compose up -d

# 5. Check status
docker compose ps
docker compose logs -f
```

## Environment Configuration

Edit `.env` before starting:

```bash
# Required - Generate secure passwords!
DB_ROOT_PASSWORD=<generate-secure-password>
DB_PASSWORD=<generate-secure-password>

# Adjust for your machine's RAM
# 2-4GB RAM: 512M | 4-8GB RAM: 1G | 8GB+ RAM: 2G
DB_MEMORY_LIMIT=1G
```

## Common Issues

### 1. Database Connection Failed

**Symptoms:** `Connection refused` or `Access denied`

**Fix:**
```bash
# Check if DB is healthy
docker compose ps db

# View DB logs
docker compose logs db

# Recreate DB volume (WARNING: deletes data)
docker compose down -v
docker compose up -d
```

### 2. Out of Memory (OOM)

**Symptoms:** Container keeps restarting, `Killed` in logs

**Fix:** Reduce memory in `.env`:
```bash
DB_MEMORY_LIMIT=512M
```

And edit `backend-ci/docker/mysql/custom.cnf`:
```ini
innodb_buffer_pool_size = 256M
max_connections = 50
```

### 3. Permission Denied

**Symptoms:** `Permission denied` on writable/ or uploads/

**Fix:**
```bash
# Reset volumes
docker compose down
docker volume rm kiotviet_backend_vendor kiotviet_backend_writable
docker compose up -d
```

### 4. Port Already in Use

**Symptoms:** `Bind: address already in use`

**Fix:** Change ports in `.env`:
```bash
APP_PORT=8001
FE_PORT=3001
PMA_PORT=8081
```

## Commands Reference

```bash
# Start all services
docker compose up -d

# Start with dev overrides
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d

# View logs
docker compose logs -f [service]

# Enter container shell
docker compose exec web bash
docker compose exec db mysql -u root -p

# Restart service
docker compose restart web

# Stop all
docker compose down

# Stop and remove volumes (CAUTION: deletes data)
docker compose down -v

# Rebuild images
docker compose build --no-cache
```

## Import SQL Dump

```bash
# Copy dump to container and import
docker compose exec -T db mysql -u root -p${DB_ROOT_PASSWORD} ${DB_DATABASE} < dump.sql

# Or use phpMyAdmin at http://localhost:8080
```

## Health Check

```bash
# Check all services
docker compose ps

# Test API
curl http://localhost:8000/health

# Test DB connection
docker compose exec db mysqladmin ping -u root -p
```
