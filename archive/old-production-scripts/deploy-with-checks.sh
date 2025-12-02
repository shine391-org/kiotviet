#!/bin/bash

# Deployment script with error checking and auto-fixing
set -e

echo "=== LanoCRM Deployment with Health Checks ==="
echo "Date: $(date)"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

# 1. Check prerequisites
echo "1. Checking prerequisites..."

if ! command -v docker &> /dev/null; then
    print_error "Docker is not installed"
    exit 1
fi
print_status "Docker is installed"

if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null; then
    print_error "Docker Compose is not installed"
    exit 1
fi
print_status "Docker Compose is installed"

# 2. Prepare environment
echo ""
echo "2. Preparing environment..."

if [ ! -f backend-ci/.env ]; then
    print_warning "Creating backend-ci/.env from example..."
    cp backend-ci/.env.example backend-ci/.env
    print_warning "⚠️  Please update backend-ci/.env with production secrets!"
fi

# Create necessary directories
mkdir -p logs/api logs/mysql logs/mysql-test
mkdir -p backups/mysql
print_status "Created necessary directories"

# 3. Stop existing containers
echo ""
echo "3. Stopping existing containers..."
docker-compose down 2>/dev/null || true
print_status "Stopped existing containers"

# 4. Build and deploy
echo ""
echo "4. Building and deploying containers..."
docker-compose -f docker-compose.yml -f docker-compose.override.yml up -d --build

# 5. Wait for containers to start
echo ""
echo "5. Waiting for containers to start..."
sleep 10

# 6. Check container status
echo ""
echo "6. Checking container status..."

# Function to check container health
check_container() {
    local container_name=$1
    local service_name=$2
    
    if docker-compose ps | grep -q "$service_name.*Up"; then
        if docker-compose ps | grep -q "$service_name.*healthy"; then
            print_status "$container_name is healthy"
        else
            print_warning "$container_name is running but not healthy yet"
        fi
    else
        print_error "$container_name is not running"
        echo "Logs for $service_name:"
        docker-compose logs --tail=10 $service_name
        return 1
    fi
}

# Check each container
check_container "Database" "db"
check_container "Backend API" "web"
check_container "Frontend" "fe"
check_container "phpMyAdmin" "phpmyadmin"

# 7. Check services
echo ""
echo "7. Checking services..."

# Check backend API
echo "   Checking Backend API..."
if curl -s http://localhost:8000/backend-ci/api/health > /dev/null; then
    print_status "Backend API is responding"
else
    print_error "Backend API is not responding"
    echo "Checking backend logs:"
    docker-compose logs --tail=10 web
fi

# Check frontend
echo "   Checking Frontend..."
if curl -s http://localhost:3000 > /dev/null; then
    print_status "Frontend is responding"
else
    print_error "Frontend is not responding"
    echo "Checking frontend logs:"
    docker-compose logs --tail=10 fe
fi

# Check phpMyAdmin
echo "   Checking phpMyAdmin..."
if curl -s http://localhost:8080 > /dev/null; then
    print_status "phpMyAdmin is responding"
else
    print_error "phpMyAdmin is not responding"
    echo "Checking phpMyAdmin logs:"
    docker-compose logs --tail=10 phpmyadmin
fi

# 8. Final status
echo ""
echo "=== Deployment Summary ==="
echo "Frontend: http://localhost:3000"
echo "Backend:  http://localhost:8000/backend-ci/api"
echo "phpMyAdmin: http://localhost:8080"
echo ""
echo "Useful commands:"
echo "  View logs: docker-compose logs -f [service]"
echo "  Stop all: docker-compose down"
echo "  Restart: docker-compose restart [service]"