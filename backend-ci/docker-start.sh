#!/bin/bash
set -e
cd /var/www/html

# wait for DB
for i in {1..30}; do
  php -r 'try{new mysqli("db","lanocrm_user","KP7n4RjcDbedSE2W8GgA","lanocrm_shop"); exit(0);}catch(Throwable $e){ exit(1);}';
  if [ $? -eq 0 ]; then echo "DB ready"; break; fi
  echo "Waiting DB..."; sleep 2;
done

php spark migrate --all || true
php spark db:seed DevDemoSeeder || true

exec apache2-foreground
