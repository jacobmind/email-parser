FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip \
    libzip-dev libpq-dev libmcrypt-dev libssl-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring zip exif pcntl

# Install mailparse via PECL
RUN pecl install mailparse \
    && echo "extension=mailparse.so" > /usr/local/etc/php/conf.d/mailparse.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Add permissions
RUN chown -R www-data:www-data /var/www

EXPOSE 9000
