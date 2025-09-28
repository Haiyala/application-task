# Use official PHP with Apache
FROM php:8.2-apache

# Enable curl extension
RUN docker-php-ext-install curl

# Copy project into container
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html
