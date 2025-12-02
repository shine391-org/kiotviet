#!/bin/bash
set -e

# Change to application directory
cd /var/www/html

echo "=== Starting Application Setup ==="

# Wait for database to be ready
echo "Waiting for database connection..."
for i in {1..30}; do
  php -r 'try{new mysqli("db","lanocrm_user","KP7n4RjcDbedSE2W8GgA","lanocrm_shop"); exit(0);}catch(Throwable $e){ exit(1);}' 2>/dev/null
  if [ $? -eq 0 ]; then
    echo "✓ Database connection established"
    break
  fi
  echo "  Attempt $i/30: Waiting for database..."
  sleep 2
done

# Check if we successfully connected
php -r 'try{new mysqli("db","lanocrm_user","KP7n4RjcDbedSE2W8GgA","lanocrm_shop"); exit(0);}catch(Throwable $e){ echo "ERROR: Cannot connect to database\n"; exit(1);}' 2>/dev/null
if [ $? -ne 0 ]; then
    echo "✗ Failed to connect to database after 60 seconds"
    exit 1
fi

# Check if database is already initialized
DB_INITIALIZED_FILE="/var/www/html/writable/.db_initialized"

# Run migrations if not initialized
if [ ! -f "$DB_INITIALIZED_FILE" ]; then
    echo ""
    echo "=== Database Initialization ==="
    echo "Running migrations..."
    
    # Run migrations with timeout protection
    timeout 300 php spark migrate --all 2>&1 | tee /tmp/migration.log
    MIGRATION_STATUS=${PIPESTATUS[0]}
    
    if [ $MIGRATION_STATUS -eq 0 ]; then
        echo "✓ Migrations completed successfully"
        
        # Seeding based on environment
        CURRENT_ENV=${CI_ENVIRONMENT:-production}
        
        if [ "$CURRENT_ENV" != "production" ]; then
            echo "Environment: $CURRENT_ENV - Running Unified DemoSeeder..."
            php spark db:seed DemoSeeder 2>&1 || echo "⚠ DemoSeeder skipped or failed"
        else
            echo "Environment: Production - Skipping auto-seeding (Manual seeding required)"
        fi
        
        # Create marker file
        touch "$DB_INITIALIZED_FILE"
        echo "✓ Database initialization completed"
    else
        echo "✗ Migration failed with status: $MIGRATION_STATUS"
        echo "Check logs at /tmp/migration.log"
        cat /tmp/migration.log
        exit 1
    fi
else
    echo "✓ Database already initialized (marker file exists)"
    echo "  To re-run migrations, delete: $DB_INITIALIZED_FILE"
fi

# Health check (non-blocking)
echo ""
echo "=== Health Check ==="
php spark db:health tests 2>&1 || echo "⚠ Health check warning (non-critical)"

echo ""
echo "=== Starting Apache ==="
cd /var/www/html
exec apache2-foreground
