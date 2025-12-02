#!/bin/bash

# Script to check migration status on deployed environment
# Usage: ./scripts/check-migration-status.sh

echo "=== Migration Status Check ==="
echo ""

# Check if container is running
if ! docker ps | grep -q meomeo2-api-1; then
    echo "✗ API container is not running"
    echo "  Run: docker-compose up -d"
    exit 1
fi

echo "✓ API container is running"
echo ""

# Check database connection
echo "Checking database connection..."
docker exec meomeo2-api-1 php -r 'try{new mysqli("db","lanocrm_user","KP7n4RjcDbedSE2W8GgA","lanocrm_shop"); echo "✓ Database connected\n"; exit(0);}catch(Throwable $e){ echo "✗ Database connection failed: " . $e->getMessage() . "\n"; exit(1);}' 2>/dev/null

if [ $? -ne 0 ]; then
    echo "  Check if db container is running: docker-compose ps db"
    exit 1
fi

echo ""

# Check if database is initialized
echo "Checking database initialization..."
if docker exec meomeo2-api-1 test -f /var/www/html/backend-ci/writable/.db_initialized; then
    echo "✓ Database initialization marker exists"
else
    echo "⚠ Database initialization marker NOT found"
    echo "  This means migrations may not have run yet"
fi

echo ""

# Check migration status
echo "Checking migration status..."
docker exec meomeo2-api-1 bash -c "cd /var/www/html/backend-ci && php spark migrate:status" 2>&1

echo ""

# Check table count
echo "Checking table count..."
TABLE_COUNT=$(docker exec meomeo2-db-1 mysql -u lanocrm_user -pKP7n4RjcDbedSE2W8GgA lanocrm_shop -e "SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = 'lanocrm_shop'" -s -N 2>/dev/null)

if [ -n "$TABLE_COUNT" ]; then
    echo "✓ Found $TABLE_COUNT tables in database"
    
    # Expected table count from migration
    EXPECTED_COUNT=164
    if [ "$TABLE_COUNT" -ge "$EXPECTED_COUNT" ]; then
        echo "✓ Table count looks good (expected ~$EXPECTED_COUNT)"
    else
        echo "⚠ Table count is low (expected ~$EXPECTED_COUNT, found $TABLE_COUNT)"
        echo "  Migrations may be incomplete"
    fi
else
    echo "✗ Could not query table count"
fi

echo ""
echo "=== Summary ==="
echo "If you see issues above, try:"
echo "  1. Check logs: docker-compose logs api"
echo "  2. Re-run migrations: docker exec meomeo2-api-1 bash -c 'cd /var/www/html/backend-ci && php spark migrate --all'"
echo "  3. Check migration log: docker exec meomeo2-api-1 cat /tmp/migration.log"