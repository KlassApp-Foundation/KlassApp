FROM php:8.3-fpm

# PHP 8.4 MIGRATION PENDING — See knowledge.md "PHP 8.4 Move" incident plan.
# Do NOT bump this to php:8.4-fpm without completing the full migration:
#   - Audit all packages for PHP 8.4 compatibility (11 known blockers as of Jul 14)
#   - Docker image rebuild + full test suite run
#   - Smoke-test all 8 dashboards
#   - This is tracked as Part 2 of the laravel/ai incident follow-up

# Accept build arguments from docker-compose
ARG USER_ID=1000
ARG GROUP_ID=1000

# Install system dependencies
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
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Working directory
WORKDIR /var/www

# Create consistent app user
RUN groupadd -g ${GROUP_ID} appgroup \
    && useradd -u ${USER_ID} -g appgroup -m appuser

# Copy project (owned by appuser)
COPY --chown=appuser:appgroup . .

RUN chown -R appuser:appgroup /var/www
USER appuser
RUN composer install --no-interaction --prefer-dist --no-scripts

# Switch back to root for PHP-FPM config
USER root

# Align PHP-FPM with app user
RUN sed -i 's/user = www-data/user = appuser/g' /usr/local/etc/php-fpm.d/www.conf \
 && sed -i 's/group = www-data/group = appgroup/g' /usr/local/etc/php-fpm.d/www.conf \
 && sed -i 's|^;*listen = .*|listen = 0.0.0.0:9000|' /usr/local/etc/php-fpm.d/www.conf

# Xdebug (dev only — set BUILD_WITH_XDEBUG=1 to include)
ARG BUILD_WITH_XDEBUG=0
RUN if [ "$BUILD_WITH_XDEBUG" = "1" ]; then \
    pecl install xdebug && \
    docker-php-ext-enable xdebug && \
    { \
        echo "xdebug.mode=debug"; \
        echo "xdebug.client_host=host.docker.internal"; \
        echo "xdebug.client_port=9003"; \
        echo "xdebug.start_with_request=yes"; \
        echo "xdebug.idekey=PHPSTORM"; \
    } >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
    fi

# Entry point
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/entrypoint.sh"]