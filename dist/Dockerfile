FROM php:8.4-apache

# Install system dependencies
RUN apt-get update && apt-get install -y     libzip-dev     libpng-dev     libjpeg-dev     libfreetype6-dev     && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install     mysqli     pdo_mysql     zip     gd     && docker-php-ext-enable mysqli pdo_mysql zip gd

# Enable Apache modules
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html     && chmod -R 755 /var/www/html     && chmod -R 777 /var/www/html/backend-ci/writable

# Copy and set permissions for docker-start.sh
COPY backend-ci/docker-start.sh /usr/local/bin/docker-start.sh
RUN chmod +x /usr/local/bin/docker-start.sh

# Expose port 80
EXPOSE 80

# Use docker-start.sh as entrypoint (handles DB wait + migration)
CMD ["/usr/local/bin/docker-start.sh"]
