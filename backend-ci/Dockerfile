FROM php:8.4-apache

# System deps & PHP extensions
RUN apt-get update \
 && apt-get install -y git unzip libzip-dev libicu-dev \
 && docker-php-ext-install pdo pdo_mysql mysqli intl zip \
 && docker-php-ext-enable pdo_mysql mysqli intl \
 && rm -rf /var/lib/apt/lists/*

# Apache config for CI4 public directory
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf \
    && a2enmod rewrite \
    && printf '<Directory "%s">\n    AllowOverride All\n    Require all granted\n</Directory>\n' "${APACHE_DOCUMENT_ROOT}" > /etc/apache2/conf-available/allow-override.conf \
    && a2enconf allow-override

WORKDIR /var/www/html

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install PHP dependencies first for better layer cache
COPY backend-ci/composer.* ./
RUN composer install --prefer-dist --no-interaction --optimize-autoloader

# Copy application source
COPY backend-ci/ ./

# Permissions for writable directories
RUN chown -R www-data:www-data /var/www/html/writable /var/www/html/public \
    && chmod -R 775 /var/www/html/writable
