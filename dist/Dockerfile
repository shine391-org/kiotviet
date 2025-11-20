FROM php:8.4-apache

# Install mysqli extension
RUN apt-get update \
 && apt-get install -y git unzip libzip-dev \
 && docker-php-ext-install mysqli pdo pdo_mysql \
 && docker-php-ext-enable mysqli pdo pdo_mysql

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

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
