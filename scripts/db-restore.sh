#!/bin/bash

# Database Restore Script for LANO CRM
# Usage: ./scripts/db-restore.sh [main|test] [backup_file]

set -e

DB_TYPE=${1:-main}
BACKUP_FILE=${2}

if [ -z "$BACKUP_FILE" ]; then
    echo "Usage: $0 [main|test] [backup_file]"
    echo "Example: $0 main ./backups/lanocrm_main_20251201_120000.sql.gz"
    exit 1
fi

# Check if backup file exists
if [ ! -f "$BACKUP_FILE" ]; then
    echo "Error: Backup file not found: $BACKUP_FILE"
    exit 1
fi

# Create temporary file for decompressed backup
TEMP_FILE="/tmp/restore_$(date +%s).sql"

# Decompress backup file if it's gzipped
if [[ "$BACKUP_FILE" == *.gz ]]; then
    echo "Decompressing backup file..."
    gunzip -c "$BACKUP_FILE" > "$TEMP_FILE"
else
    cp "$BACKUP_FILE" "$TEMP_FILE"
fi

if [ "$DB_TYPE" = "main" ]; then
    CONTAINER="meomeo2-db-1"
    DATABASE="lanocrm_dev"
    
    echo "Restoring main database: $DATABASE"
    echo "WARNING: This will overwrite all data in the main database!"
    read -p "Are you sure you want to continue? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Restore cancelled."
        rm -f "$TEMP_FILE"
        exit 1
    fi
    
    # Copy backup file to container and restore
    docker cp "$TEMP_FILE" "$CONTAINER:/tmp/restore.sql"
    docker exec "$CONTAINER" mysql -u lanocrm_user -pKP7n4RjcDbedSE2W8GgA "$DATABASE" -e "DROP DATABASE IF EXISTS $DATABASE; CREATE DATABASE $DATABASE;"
    docker exec "$CONTAINER" mysql -u lanocrm_user -pKP7n4RjcDbedSE2W8GgA "$DATABASE" < /tmp/restore.sql
    docker exec "$CONTAINER" rm -f /tmp/restore.sql
    
elif [ "$DB_TYPE" = "test" ]; then
    CONTAINER="meomeo2-db-test-1"
    DATABASE="lanocrm_test"
    
    echo "Restoring test database: $DATABASE"
    
    # Copy backup file to container and restore
    docker cp "$TEMP_FILE" "$CONTAINER:/tmp/restore.sql"
    docker exec "$CONTAINER" mysql -u lanocrm_user -pKP7n4RjcDbedSE2W8GgA "$DATABASE" -e "DROP DATABASE IF EXISTS $DATABASE; CREATE DATABASE $DATABASE;"
    docker exec "$CONTAINER" mysql -u lanocrm_user -pKP7n4RjcDbedSE2W8GgA "$DATABASE" < /tmp/restore.sql
    docker exec "$CONTAINER" rm -f /tmp/restore.sql
    
else
    echo "Usage: $0 [main|test] [backup_file]"
    rm -f "$TEMP_FILE"
    exit 1
fi

# Clean up temporary file
rm -f "$TEMP_FILE"

echo "Database restore completed successfully!"

# Reset database initialization marker to ensure proper setup
if [ "$DB_TYPE" = "main" ]; then
    docker exec ${DOCKER_CONTAINER:-kiotviet-web-1} rm -f /var/www/html/writable/.db_initialized
    echo "Database initialization marker reset. Please restart the API container."
fi