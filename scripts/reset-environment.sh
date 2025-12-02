#!/bin/bash

# Complete environment reset script
set -e

echo "=== LanoCRM Environment Reset ==="
echo "Date: $(date)"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

# 1. Stop and remove containers
echo "1. Stopping and removing containers..."
docker-compose down -v 2>/dev/null || true
print_status "Containers stopped and removed"

# 2. Remove images
echo ""
echo "2. Removing Docker images..."
docker-compose down --rmi all 2>/dev/null || true
docker system prune -f 2>/dev/null || true
print_status "Docker images removed"

# 3. Clean up volumes (optional)
echo ""
read -p "Do you want to remove ALL data volumes? This will delete database data. (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "   Removing volumes..."
    docker volume prune -f 2>/dev/null || true
    print_status "Volumes removed"
else
    print_warning "Volumes preserved"
fi

# 4. Clean up logs
echo ""
echo "4. Cleaning up logs..."
rm -rf logs/*
print_status "Logs cleaned"

# 5. Fresh deployment
echo ""
echo "5. Starting fresh deployment..."
./scripts/deploy-with-checks.sh

echo ""
echo "=== Environment Reset Complete ==="