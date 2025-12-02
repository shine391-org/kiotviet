#!/bin/bash

# Deployment Verification Script
# Usage: ./scripts/verify-deployment.sh [environment]

set -e

ENVIRONMENT="${1:-dev}"
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Load environment variables if .env exists
if [ -f "$PROJECT_ROOT/.env" ]; then
    export $(grep -v '^#' "$PROJECT_ROOT/.env" | xargs)
fi

# Determine configuration based on environment
case "$ENVIRONMENT" in
    dev)
        COMPOSE_FILES="-f docker-compose.yml -f docker-compose.override.yml"
        DB_NAME="lanocrm_dev"
        API_URL="http://localhost:${APP_PORT:-8000}"
        FE_URL="http://localhost:${FE_PORT:-3000}"
        ;;
    staging)
        COMPOSE_FILES="-f docker-compose.yml -f docker-compose.staging.yml"
        DB_NAME="lanocrm_staging"
        STAGING_FE_PORT=${STAGING_FE_PORT:-80}
        if [ "$STAGING_FE_PORT" = "80" ]; then
            API_URL="http://localhost/api"
            FE_URL="http://localhost"
        else
            API_URL="http://localhost:${STAGING_FE_PORT}/api"
            FE_URL="http://localhost:${STAGING_FE_PORT}"
        fi
        ;;
    *)
        print_error "Invalid environment: $ENVIRONMENT"
        exit 1
        ;;
esac

print_status "Verifying $ENVIRONMENT deployment..."

# 1. Check Container Status
print_status "Checking container status..."
if docker-compose $COMPOSE_FILES ps | grep -q "Up"; then
    print_status "✅ Containers are running"
else
    print_error "❌ Some containers are not running"
    docker-compose $COMPOSE_FILES ps
    exit 1
fi

# 2. Check Database Connection
print_status "Checking database connection..."
if docker-compose $COMPOSE_FILES exec -T db mysqladmin ping -h"localhost" -u"lanocrm_user" -p"KP7n4RjcDbedSE2W8GgA" --silent; then
    print_status "✅ Database is responsive"
else
    print_error "❌ Database is not responsive"
    exit 1
fi

# 3. Check Database Schema
print_status "Checking database schema in $DB_NAME..."
TABLE_COUNT=$(docker-compose $COMPOSE_FILES exec -T db mysql -u lanocrm_user -pKP7n4RjcDbedSE2W8GgA $DB_NAME -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$DB_NAME'" -s -N 2>/dev/null)

if [ -n "$TABLE_COUNT" ] && [ "$TABLE_COUNT" -gt 0 ]; then
    print_status "✅ Database has $TABLE_COUNT tables"
else
    print_error "❌ Database seems empty or inaccessible"
    exit 1
fi

# 4. Check API Health
print_status "Checking API health..."
if curl -f "$API_URL/health" > /dev/null 2>&1 || curl -f "$API_URL/api/health" > /dev/null 2>&1; then
    print_status "✅ API is healthy"
else
    print_warning "⚠️  API health check failed (might be starting up or path issue)"
fi

# 5. Check Frontend
print_status "Checking Frontend..."
if curl -f "$FE_URL" > /dev/null 2>&1; then
    print_status "✅ Frontend is accessible"
else
    print_warning "⚠️  Frontend check failed"
fi

print_status "Verification completed for $ENVIRONMENT"