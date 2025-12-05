#!/bin/bash

# Exit on error
set -e

echo "=== LanoCRM Deployment Script ==="
echo "Environment: Production"
echo "Date: $(date)"
echo ""

# 1. Check Prerequisites
echo "1. Checking prerequisites..."
if ! command -v docker &> /dev/null; then
    echo "Error: docker is not installed."
    exit 1
fi

if ! command -v docker &> /dev/null; then
    echo "Error: docker is not installed."
    exit 1
fi

# Check for docker compose (v2) or docker-compose (v1)
if command -v docker-compose &> /dev/null; then
    DOCKER_COMPOSE="docker-compose"
elif docker compose version &> /dev/null; then
    DOCKER_COMPOSE="docker compose"
else
    echo "Error: docker-compose or docker compose is not installed."
    exit 1
fi

# 2. Prepare Environment
echo "2. Preparing environment..."
if [ ! -f backend-ci/.env ]; then
    echo "Creating backend-ci/.env from example..."
    cp backend-ci/.env.example backend-ci/.env
    echo "⚠️  Please update backend-ci/.env with production secrets!"
fi

# 3. Build and Deploy
echo "3. Building and deploying containers..."
$DOCKER_COMPOSE -f docker-compose.yml -f docker-compose.prod.yml up -d --build

# 4. Wait for Services
echo "4. Waiting for services to start..."
echo "   Waiting for Web Service to be ready..."
sleep 10

# 5. Verify Deployment
echo "5. Verifying deployment..."
if [ -f scripts/check-migration-status.sh ]; then
    chmod +x scripts/check-migration-status.sh
    ./scripts/check-migration-status.sh
else
    echo "   Skipping migration check (script not found)"
fi

echo ""
echo "=== Deployment Complete ==="
echo "Frontend: http://localhost (or your domain)"
echo "Backend:  http://localhost/backend-ci/api"
echo ""
echo "Next steps:"
echo "1. Verify logs: $DOCKER_COMPOSE logs -f web"
echo "2. Check health: curl http://localhost/backend-ci/api/health"
