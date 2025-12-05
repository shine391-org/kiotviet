#!/bin/bash

# Deploy Staging Environment Script
# Usage: ./scripts/deploy-staging.sh

set -e

echo "🚀 Deploying Staging Environment..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

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

# Load environment variables if .env exists
if [ -f ".env" ]; then
    export $(grep -v '^#' .env | xargs)
fi

# Set defaults for staging ports
STAGING_FE_PORT=${STAGING_FE_PORT:-80}
STAGING_DB_PORT=${STAGING_DB_PORT:-3308}
STAGING_DB_TEST_PORT=${STAGING_DB_TEST_PORT:-3309}
STAGING_PMA_PORT=${STAGING_PMA_PORT:-8081}

# Check if we're in the right directory
if [ ! -f "docker-compose.yml" ]; then
    print_error "docker-compose.yml not found. Please run this script from the project root."
    exit 1
fi

# Check if staging compose file exists
if [ ! -f "docker-compose.staging.yml" ]; then
    print_error "docker-compose.staging.yml not found."
    exit 1
fi

# Stop any existing containers
print_status "Stopping existing containers..."
docker-compose -f docker-compose.yml -f docker-compose.staging.yml down --remove-orphans

# Pull latest changes from main branch
print_status "Pulling latest changes from main branch..."
git fetch origin
git checkout main
git pull origin main

# Build and start staging containers
print_status "Building and starting staging containers..."
docker-compose -f docker-compose.yml -f docker-compose.staging.yml up -d --build

# Wait for services to be ready
print_status "Waiting for services to be ready..."
sleep 10

# Check if containers are running
print_status "Checking container status..."
docker-compose -f docker-compose.yml -f docker-compose.staging.yml ps

# Wait for database to be ready
print_status "Waiting for database to be ready..."
timeout=60
while [ $timeout -gt 0 ]; do
    if docker-compose -f docker-compose.yml -f docker-compose.staging.yml exec -T db mysqladmin ping -h"localhost" -u"lanocrm_user" -p"KP7n4RjcDbedSE2W8GgA" --silent; then
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
docker-compose -f docker-compose.yml -f docker-compose.staging.yml exec web php spark migrate --all

# Seed demo data if needed
print_status "Seeding demo data..."
docker-compose -f docker-compose.yml -f docker-compose.staging.yml exec web php spark db:seed DemoSeeder

# Check frontend health
print_status "Checking frontend health..."
FE_CHECK_URL="http://localhost"
if [ "$STAGING_FE_PORT" != "80" ]; then
    FE_CHECK_URL="http://localhost:${STAGING_FE_PORT}"
fi
if curl -f "${FE_CHECK_URL}/health" > /dev/null 2>&1 || curl -f "${FE_CHECK_URL}" > /dev/null 2>&1; then
    print_status "✅ Frontend is healthy!"
else
    print_warning "⚠️  Frontend health check failed, but container might still be starting..."
fi

# Check backend health
print_status "Checking backend health..."
if curl -f "${FE_CHECK_URL}/api/health" > /dev/null 2>&1; then
    print_status "✅ Backend is healthy!"
else
    print_warning "⚠️  Backend health check failed, but container might still be starting..."
fi

# Display access information
echo ""
print_status "🎉 Staging deployment completed!"
echo ""
echo "📋 Access Information:"
if [ "$STAGING_FE_PORT" = "80" ]; then
    echo "  Frontend: http://localhost"
    echo "  Backend API: http://localhost/api"
else
    echo "  Frontend: http://localhost:${STAGING_FE_PORT}"
    echo "  Backend API: http://localhost:${STAGING_FE_PORT}/api"
fi
echo "  Database: localhost:${STAGING_DB_PORT} (lanocrm_staging)"
echo "  Test DB: localhost:${STAGING_DB_TEST_PORT} (lanocrm_test)"
echo "  phpMyAdmin: http://localhost:${STAGING_PMA_PORT}"
echo ""
echo "🔧 Useful Commands:"
echo "  View logs: docker-compose -f docker-compose.yml -f docker-compose.staging.yml logs -f"
echo "  Stop staging: docker-compose -f docker-compose.yml -f docker-compose.staging.yml down"
echo "  Restart staging: docker-compose -f docker-compose.yml -f docker-compose.staging.yml restart"
echo ""
print_status "✅ Staging environment is ready for testing!"