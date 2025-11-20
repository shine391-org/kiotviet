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
