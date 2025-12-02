#!/bin/bash
set -e
cd /var/www/html

# wait for DB
for i in {1..30}; do
  php -r 'try{new mysqli("db","lanocrm_user","KP7n4RjcDbedSE2W8GgA","lanocrm_shop"); exit(0);}catch(Throwable $e){ exit(1);}';
  if [ $? -eq 0 ]; then echo "DB ready"; break; fi
  echo "Waiting DB..."; sleep 2;
done

# Check if database is already initialized
DB_INITIALIZED_FILE="/var/www/html/writable/.db_initialized"

# Schema bootstrap via golden migration + optional demo seed (default group)
# Only run migration/seeder if database is not initialized
if [ ! -f "$DB_INITIALIZED_FILE" ]; then
    echo "Database not initialized, running setup..."
    MIGRATION_VERBOSE=0 php spark migrate --all || true
    MIGRATION_VERBOSE=0 php spark db:seed DevDemoSeeder || true
    # Create marker file to indicate database is initialized
    touch "$DB_INITIALIZED_FILE"
    echo "Database initialization completed."
else
    echo "Database already initialized, skipping setup..."
fi

# Kiểm tra health cho group tests (golden schema) để đảm bảo môi trường test sẵn sàng
MIGRATION_VERBOSE=0 php spark db:health tests || true

exec apache2-foreground
