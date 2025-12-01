#!/bin/bash
set -e
cd /var/www/html

# wait for DB
for i in {1..30}; do
  php -r 'try{new mysqli("db","lanocrm_user","KP7n4RjcDbedSE2W8GgA","lanocrm_shop"); exit(0);}catch(Throwable $e){ exit(1);}';
  if [ $? -eq 0 ]; then echo "DB ready"; break; fi
  echo "Waiting DB..."; sleep 2;
done

# Schema bootstrap via golden migration + optional demo seed (default group)
MIGRATION_VERBOSE=0 php spark migrate --all || true
MIGRATION_VERBOSE=0 php spark db:seed DevDemoSeeder || true
# Kiểm tra health cho group tests (golden schema) để đảm bảo môi trường test sẵn sàng
MIGRATION_VERBOSE=0 php spark db:health tests || true

exec apache2-foreground
