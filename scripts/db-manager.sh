#!/bin/bash

# Database Management Script for LANO CRM
# Usage: ./scripts/db-manager.sh [backup|restore|list|clean] [options]

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKUP_DIR="./backups"

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

case "${1:-help}" in
    backup)
        DB_TYPE=${2:-main}
        "$SCRIPT_DIR/db-backup.sh" "$DB_TYPE"
        ;;
    restore)
        DB_TYPE=${2:-main}
        if [ -z "$3" ]; then
            echo "Available backups:"
            find "$BACKUP_DIR" -name "lanocrm_${DB_TYPE}_*.sql.gz" -type f | sort -r | head -10 | while read -r file; do
                echo "  $(basename "$file")"
            done
            echo ""
            echo "Usage: $0 restore $DB_TYPE [backup_file]"
            exit 1
        fi
        "$SCRIPT_DIR/db-restore.sh" "$DB_TYPE" "$3"
        ;;
    list)
        DB_TYPE=${2:-main}
        echo "Available backups for $DB_TYPE database:"
        find "$BACKUP_DIR" -name "lanocrm_${DB_TYPE}_*.sql.gz" -type f | sort -r | while read -r file; do
            SIZE=$(du -h "$file" | cut -f1)
            DATE=$(stat -c %y "$file" | cut -d' ' -f1,2 | cut -d'.' -f1)
            echo "  $(basename "$file") - $SIZE - $DATE"
        done
        ;;
    clean)
        DB_TYPE=${2:-main}
        DAYS=${3:-30}
        echo "Cleaning backups older than $DAYS days for $DB_TYPE database..."
        find "$BACKUP_DIR" -name "lanocrm_${DB_TYPE}_*.sql.gz" -type f -mtime +$DAYS -delete
        echo "Cleanup completed."
        ;;
    reset)
        DB_TYPE=${2:-main}
        echo "WARNING: This will completely reset the $DB_TYPE database!"
        read -p "Are you sure you want to continue? (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            if [ "$DB_TYPE" = "main" ]; then
                CONTAINER="meomeo2-db-1"
                DATABASE="lanocrm_shop"
            elif [ "$DB_TYPE" = "test" ]; then
                CONTAINER="meomeo2-db-test-1"
                DATABASE="lanocrm_test"
            else
                echo "Invalid database type: $DB_TYPE"
                exit 1
            fi
            
            echo "Resetting database: $DATABASE"
            docker exec "$CONTAINER" mysql -u lanocrm_user -pKP7n4RjcDbedSE2W8GgA -e "DROP DATABASE IF EXISTS $DATABASE; CREATE DATABASE $DATABASE;"
            
            # Reset database initialization marker
            if [ "$DB_TYPE" = "main" ]; then
                docker exec meomeo2-api-1 rm -f /var/www/html/writable/.db_initialized
                echo "Database reset completed. Please restart the API container to reinitialize."
            else
                echo "Test database reset completed."
            fi
        else
            echo "Reset cancelled."
        fi
        ;;
    status)
        echo "Database Status:"
        echo "================"
        
        # Check main database
        if docker ps | grep -q "meomeo2-db-1"; then
            echo "Main Database: Running"
            TABLES=$(docker exec meomeo2-db-1 mysql -u lanocrm_user -pKP7n4RjcDbedSE2W8GgA lanocrm_shop -e "SHOW TABLES;" | wc -l)
            echo "  Tables: $TABLES"
        else
            echo "Main Database: Not running"
        fi
        
        # Check test database
        if docker ps | grep -q "meomeo2-db-test-1"; then
            echo "Test Database: Running"
            TABLES=$(docker exec meomeo2-db-test-1 mysql -u lanocrm_user -pKP7n4RjcDbedSE2W8GgA lanocrm_test -e "SHOW TABLES;" | wc -l)
            echo "  Tables: $TABLES"
        else
            echo "Test Database: Not running"
        fi
        
        # Check API container
        if docker ps | grep -q "meomeo2-api-1"; then
            if docker exec meomeo2-api-1 test -f /var/www/html/writable/.db_initialized; then
                echo "API Container: Database initialized"
            else
                echo "API Container: Database not initialized"
            fi
        else
            echo "API Container: Not running"
        fi
        ;;
    help|*)
        echo "Database Management Script for LANO CRM"
        echo "====================================="
        echo ""
        echo "Usage: $0 [command] [options]"
        echo ""
        echo "Commands:"
        echo "  backup [main|test]     - Create a backup of the specified database"
        echo "  restore [main|test]     - Restore a database from backup"
        echo "  list [main|test]        - List available backups"
        echo "  clean [main|test] [days] - Clean old backups (default: 30 days)"
        echo "  reset [main|test]        - Reset database to empty state"
        echo "  status                  - Show database status"
        echo "  help                    - Show this help message"
        echo ""
        echo "Examples:"
        echo "  $0 backup main          # Backup main database"
        echo "  $0 restore main backup.sql.gz # Restore main database"
        echo "  $0 list test            # List test database backups"
        echo "  $0 clean main 7         # Clean main database backups older than 7 days"
        echo "  $0 reset test           # Reset test database"
        echo "  $0 status              # Show database status"
        ;;
esac