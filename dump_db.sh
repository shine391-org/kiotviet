#!/bin/bash
# Script to dump the development database
# Run this from Windows PowerShell or CMD

# Get the MySQL container name
CONTAINER_NAME=$(docker ps --filter "name=db" --format "{{.Names}}" | grep -v "db-test")

# Database credentials from docker-compose.yml
DB_USER="lanocrm_user"
DB_PASSWORD="KP7n4RjcDbedSE2W8GgA"
DB_NAME="lanocrm_dev"

# Output file with timestamp
OUTPUT_FILE="dev_dump_$(date +%Y%m%d_%H%M%S).sql"

echo "Dumping database: $DB_NAME"
echo "Container: $CONTAINER_NAME"
echo "Output file: $OUTPUT_FILE"

# Execute mysqldump
docker exec $CONTAINER_NAME mysqldump -u $DB_USER -p$DB_PASSWORD $DB_NAME > $OUTPUT_FILE

echo "Database dump completed: $OUTPUT_FILE"
