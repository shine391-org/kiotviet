#!/bin/bash

# Database Backup Script for LANO CRM
# Usage: ./scripts/db-backup.sh [main|test]

set -e

DB_TYPE=${1:-main}
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="./backups"

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

if [ "$DB_TYPE" = "main" ]; then
    CONTAINER="meomeo2-db-1"
    DATABASE="lanocrm_shop"
    BACKUP_FILE="$BACKUP_DIR/lanocrm_main_$TIMESTAMP.sql"
    
    echo "Backing up main database: $DATABASE"
    docker exec "$CONTAINER" mysqldump -u lanocrm_user -pKP7n4RjcDbedSE2W8GgA "$DATABASE" > "$BACKUP_FILE"
    
elif [ "$DB_TYPE" = "test" ]; then
    CONTAINER="meomeo2-db-test-1"
    DATABASE="lanocrm_test"
    BACKUP_FILE="$BACKUP_DIR/lanocrm_test_$TIMESTAMP.sql"
    
    echo "Backing up test database: $DATABASE"
    docker exec "$CONTAINER" mysqldump -u lanocrm_user -pKP7n4RjcDbedSE2W8GgA "$DATABASE" > "$BACKUP_FILE"
    
else
    echo "Usage: $0 [main|test]"
    exit 1
fi

# Compress the backup file
gzip "$BACKUP_FILE"
echo "Backup completed: ${BACKUP_FILE}.gz"

# Keep only last 10 backups
find "$BACKUP_DIR" -name "lanocrm_${DB_TYPE}_*.sql.gz" -type f | sort -r | tail -n +11 | xargs -r rm
echo "Old backups cleaned up (keeping last 10)"