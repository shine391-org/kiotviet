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
FROM php:7.4-apache

# Install mysqli extension
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy application files
COPY . /var/www/html/

# Set document root to /var/www/html
WORKDIR /var/www/html

# Update the base_url in config.php
RUN sed -i "s|https://banhang.tuidanam.org/backend-ci/|/backend-ci/|g" /var/www/html/backend-ci/application/config/config.php

# Update the database.php for docker-compose setup
RUN sed -i "s|'hostname' => 'localhost'|'hostname' => 'db'|g" /var/www/html/backend-ci/application/config/database.php
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
      # Pass environment variables to the web server
      # If you need more variables, add them here
      REACT_APP_API_BASE_URL: /backend-ci/api
      CI_ENVIRONMENT: production # CodeIgniter environment

  db:
    image: mysql:5.7
    environment:
      MYSQL_ROOT_PASSWORD: root_password # Change this in production
      MYSQL_DATABASE: lanocrm_shop
      MYSQL_USER: lanocrm_user
      MYSQL_PASSWORD: KP7n4RjcDbedSE2W8GgA
    volumes:
      - db_data:/var/lib/mysql
      - ./lanocrm_shop_v6.sql:/docker-entrypoint-initdb.d/lanocrm_shop_v6.sql

volumes:
  db_data:
EOL

echo "docker-compose.yml created."

echo "Deployment script complete. You can now run 'docker-compose up --build' to start the application."



