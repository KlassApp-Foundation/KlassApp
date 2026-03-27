FROM php:8.3-fpm

# Accept build arguments from docker-compose
ARG USER_ID=1000
ARG GROUP_ID=1000

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
 libicu-dev \
 netcat-openbsd

 # Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl

# install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# set working directory
WORKDIR /var/www
# copy project files
COPY . .
# Change www-data UID and GID to match host user (elicom uid=1000)
RUN usermod -u ${USER_ID} www-data && \
    groupmod -g ${GROUP_ID} www-data 

# ownership for the whole project
RUN chown -R www-data:www-data /var/www

# make storage and cache writable
RUN chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# switch to www-data for composer install
USER www-data
# install dependencies and cache configs
RUN composer install --no-interaction --prefer-dist --optimize-autoloader \
     && php artisan config:cache \
     && php artisan route:cache \
     && php artisan view:cache

# switch back to root
USER root

# fix storage and cache permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000

COPY entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh

ENTRYPOINT [ "/entrypoint.sh" ]