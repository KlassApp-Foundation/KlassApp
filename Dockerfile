FROM php:8.3-fpm
RUN apt-get update && apt-get install -y \
 git \
 curl \
 zip \
 unzip \
 openssl \
 libpng-dev \
 libonig-dev \
 libxml2-dev \
 libzip-dev \
 libicu-dev

 # Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl

# install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# set working directory
WORKDIR /var/www
COPY . .
# ownership for the whole project
RUN chown -R www-data:www-data /var/www
# switch to www-data User before running the composer
USER www-data
# install dependencies and cache configs
RUN composer install --no-interaction --prefer-dist --optimize-autoloader \
     && php artisan config:cache \
     && php artisan route:cache \
     && php artisan view:cache

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000

COPY entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh

ENTRYPOINT [ "/entrypoint.sh" ]