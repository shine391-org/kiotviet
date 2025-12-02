#!/bin/bash

# Deploy Development Environment Script
# Usage: ./scripts/deploy-dev.sh [branch-name]
# Examples:
#   ./scripts/deploy-dev.sh Feat/BE
#   ./scripts/deploy-dev.sh feature/new-feature
#   ./scripts/deploy-dev.sh hotfix/bug-fix

set -e

# Get branch from parameter or use default
BRANCH_NAME=${1:-Feat/BE}

echo "🚀 Deploying Development Environment..."
echo "📋 Branch: $BRANCH_NAME"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Load environment variables if .env exists
if [ -f ".env" ]; then
    export $(grep -v '^#' .env | xargs)
fi

# Set defaults for ports
APP_PORT=${APP_PORT:-8000}
FE_PORT=${FE_PORT:-3000}
PMA_PORT=${PMA_PORT:-8080}
DB_TEST_PORT=${DB_TEST_PORT:-3307}

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if we're in the right directory
if [ ! -f "docker-compose.yml" ]; then
    print_error "docker-compose.yml not found. Please run this script from the project root."
    exit 1
fi

# Check if development compose file exists
if [ ! -f "docker-compose.override.yml" ]; then
    print_error "docker-compose.override.yml not found."
    exit 1
fi

# Stop any existing containers
print_status "Stopping existing containers..."
docker-compose -f docker-compose.yml -f docker-compose.override.yml down --remove-orphans

# Pull latest changes from specified branch
print_status "Pulling latest changes from $BRANCH_NAME branch..."
git fetch origin
git checkout $BRANCH_NAME
git pull origin $BRANCH_NAME

# Build and start development containers
print_status "Building and starting development containers..."
docker-compose -f docker-compose.yml -f docker-compose.override.yml up -d --build

# Wait for services to be ready
print_status "Waiting for services to be ready..."
sleep 10

# Check if containers are running
print_status "Checking container status..."
docker-compose -f docker-compose.yml -f docker-compose.override.yml ps

# Wait for database to be ready
print_status "Waiting for database to be ready..."
timeout=60
while [ $timeout -gt 0 ]; do
    if docker-compose -f docker-compose.yml -f docker-compose.override.yml exec -T db mysqladmin ping -h"localhost" -u"lanocrm_user" -p"KP7n4RjcDbedSE2W8GgA" --silent; then
        print_status "Database is ready!"
        break
    fi
    echo -n "."
    sleep 1
    timeout=$((timeout-1))
done

if [ $timeout -eq 0 ]; then
    print_error "Database failed to start within 60 seconds."
    exit 1
fi

# Run database migrations
print_status "Running database migrations..."
docker-compose -f docker-compose.yml -f docker-compose.override.yml exec web php spark migrate --all

# Seed demo data if needed
print_status "Seeding demo data..."
docker-compose -f docker-compose.yml -f docker-compose.override.yml exec web php spark db:seed DemoSeeder

# Check frontend health
print_status "Checking frontend health..."
if curl -f http://localhost:${FE_PORT} > /dev/null 2>&1; then
    print_status "✅ Frontend is healthy!"
else
    print_warning "⚠️  Frontend health check failed, but container might still be starting..."
fi

# Check backend health
print_status "Checking backend health..."
if curl -f http://localhost:${APP_PORT}/health > /dev/null 2>&1; then
    print_status "✅ Backend is healthy!"
else
    print_warning "⚠️  Backend health check failed, but container might still be starting..."
fi

# Display access information
echo ""
print_status "🎉 Development deployment completed!"
echo ""
echo "📋 Access Information:"
echo "  Frontend: http://localhost:${FE_PORT}"
echo "  Backend API: http://localhost:${APP_PORT}"
echo "  Database: localhost:3306 (lanocrm_dev)"
echo "  Test DB: localhost:${DB_TEST_PORT} (lanocrm_test)"
echo "  phpMyAdmin: http://localhost:${PMA_PORT}"
echo ""
echo "🔧 Useful Commands:"
echo "  View logs: docker-compose -f docker-compose.yml -f docker-compose.override.yml logs -f"
echo "  Stop development: docker-compose -f docker-compose.yml -f docker-compose.override.yml down"
echo "  Restart development: docker-compose -f docker-compose.yml -f docker-compose.override.yml restart"
echo ""
echo "🌿 Git Information:"
echo "  Current branch: $(git branch --show-current)"
echo "  Last commit: $(git log -1 --oneline)"
echo ""
print_status "✅ Development environment is ready for coding!"