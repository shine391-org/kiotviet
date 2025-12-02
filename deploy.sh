#!/bin/bash

# Exit on error
set -e

# --- Frontend Build ---
echo "Building frontend..."
cd lanocrm
npm install
npm run build
cd ..

echo "Frontend build complete."

# --- Prepare deployment directory ---
echo "Preparing deployment directory..."
rm -rf dist
mkdir -p dist

# --- Copy backend ---
echo "Copying backend files..."
cp -r backend-ci dist/

# --- Copy frontend ---
echo "Copying frontend files..."
cp -r lanocrm/build/* dist/

# --- Create .htaccess files ---
echo "Creating .htaccess files..."

# Root .htaccess for React app
cat > dist/.htaccess << EOL
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /
  RewriteRule ^index\.html$ - [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteCond %{REQUEST_FILENAME} !-l
  RewriteRule . /index.html [L]
</IfModule>
EOL

# Backend .htaccess for CodeIgniter
cat > dist/backend-ci/.htaccess << EOL
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php/ [L]
</IfModule>
EOL

echo "Htaccess files created."

# --- Create Dockerfile ---
echo "Creating Dockerfile..."
cat > dist/Dockerfile << EOL
FROM php:8.4-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    mysqli \
    pdo_mysql \
    zip \
    gd \
    && docker-php-ext-enable mysqli pdo_mysql zip gd

# Enable Apache modules
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/writable

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
EOL

echo "Dockerfile created."

# --- Create docker-compose.yml ---
echo "Creating docker-compose.yml..."
cat > docker-compose.yml << EOL
version: '3.8'

services:
  web:
    build:
      context: ./dist
      dockerfile: Dockerfile
    ports:
      - "8000:80"
    volumes:
      - ./dist:/var/www/html
    depends_on:
      - db
    environment:
      # Environment variables
      CI_ENVIRONMENT: production
      APP_BASEURL: http://localhost:8000
      # Database configuration
      DB_HOST: db
      DB_NAME: lanocrm_shop
      DB_USER: lanocrm_user
      DB_PASS: KP7n4RjcDbedSE2W8GgA

  db:
    image: mysql:8.4
    environment:
      MYSQL_ROOT_PASSWORD: root_password # Change this in production
      MYSQL_DATABASE: lanocrm_shop
      MYSQL_USER: lanocrm_user
      MYSQL_PASSWORD: KP7n4RjcDbedSE2W8GgA
    volumes:
      - db_data:/var/lib/mysql
    ports:
      - "3306:3306"
    command: --default-authentication-plugin=mysql_native_password

volumes:
  db_data:
EOL

echo "docker-compose.yml created."

# --- Create production environment file ---
echo "Creating production environment file..."
cat > dist/backend-ci/.env << EOL
# Production Environment
CI_ENVIRONMENT = production

# Database Configuration
database.default.hostname = db
database.default.database = lanocrm_shop
database.default.username = lanocrm_user
database.default.password = KP7n4RjcDbedSE2W8GgA
database.default.DBDriver = MySQLi
database.default.DBPrefix = 
database.default.port = 3306

# App Configuration
app.baseURL = 'http://localhost:8000/'
app.indexPage = ''
app.appTimezone = 'UTC'

# Security
app.sessionDriver = 'file'
app.sessionSavePath = WRITEPATH . 'session'
app.sessionMatchIP = false
app.sessionTimeToUpdate = 300
app.sessionRegenerateDestroy = false

# Cookie
app.cookiePrefix = ''
app.cookieHTTPOnly = true
app.cookieSecure = false
app.cookieSameSite = 'Lax'

# CSRF Protection
app.CSRFProtection = true
app.CSRFTokenName = 'csrf_test_name'
app.CSRFCookieName = 'csrf_cookie_name'
app.CSRFExpire = 7200
app.CSRFRegenerate = true
app.CSRFExcludeURIs = []
app.CSRFSameSite = 'Lax'

# Content Security Policy
app.CSPEnabled = false
EOL

echo "Production environment file created."

# --- Create deployment instructions ---
echo "Creating deployment instructions..."
cat > DEPLOYMENT.md << EOL
# Deployment Instructions

## Prerequisites
- Docker & Docker Compose
- Git

## Quick Deploy

1. Clone repository:
   \`\`\`bash
   git clone <repository-url>
   cd <repository-name>
   \`\`\`

2. Run deployment script:
   \`\`\`bash
   chmod +x deploy.sh
   ./deploy.sh
   \`\`\`

3. Start application:
   \`\`\`bash
   docker-compose up -d --build
   \`\`\`

4. Access application:
   - Frontend: http://localhost:8000
   - Backend API: http://localhost:8000/backend-ci/api

## Database Setup

The first time you run the deployment, you'll need to:

1. Access the database container:
   \`\`\`bash
   docker-compose exec db mysql -u lanocrm_user -p lanocrm_shop
   \`\`\`

2. Run migrations:
   \`\`\`bash
   docker-compose exec web php spark migrate
   \`\`\`

3. Seed initial data (optional):
   \`\`\`bash
   docker-compose exec web php spark db:seed DevDemoSeeder
   \`\`\`

## Production Considerations

1. **Security**: Change default passwords in docker-compose.yml
2. **HTTPS**: Configure SSL/TLS for production
3. **Environment**: Update .env file for production settings
4. **Backups**: Set up regular database backups
5. **Monitoring**: Add health checks and monitoring

## Troubleshooting

### Permission Issues
If you get permission errors, run:
\`\`\`bash
docker-compose exec web chown -R www-data:www-data /var/www/html/writable
\`\`\`

### Database Connection
If database connection fails:
1. Check if db container is running: \`docker-compose ps\`
2. Check database logs: \`docker-compose logs db\`
3. Verify credentials in docker-compose.yml

### Frontend Not Loading
If frontend shows 404:
1. Check if build completed successfully
2. Verify .htaccess file in dist/
3. Check Apache error logs: \`docker-compose logs web\`
EOL

echo "Deployment script complete."
echo ""
echo "Next steps:"
echo "1. Review docker-compose.yml for production settings"
echo "2. Run 'docker-compose up -d --build' to start"
echo "3. See DEPLOYMENT.md for detailed instructions"
echo ""
echo "Application will be available at: http://localhost:8000"
