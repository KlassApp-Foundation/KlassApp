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

# give permission to www-data to create vendor folder
RUN chown -R www-data:www-data /var/www

# switch to www-data for composer install
USER www-data
# install dependencies and cache configs
RUN composer install --no-interaction --prefer-dist --optimize-autoloader \
     && php artisan config:cache \
     && php artisan route:cache \
     && php artisan view:cache

# Switch back to root so entrypoint can fix permissions after volume mount
USER root

# copy and prepare entry point
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 9000

ENTRYPOINT [ "/entrypoint.sh" ]