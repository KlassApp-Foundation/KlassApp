#!/bin/bash
set -e

# ====================================
# KlassApp App Server Provisioning Script
# Droplet: klassapp — 46.101.111.131 (2 GB / 50 GB)
# ====================================

# ── Config ──────────────────
APP_DOMAIN="klassapp.xyz"
APP_DIR="/var/www/klassapp"
DB_NAME="klassapp"
DB_USER="klassapp"
DB_PASS=$(openssl rand -base64 24)
EVOLUTION_PRIVATE_IP="10.19.0.6"
EVOLUTION_API_KEY_PLACEHOLDER="CHANGE_AFTER_EVOLUTION_SETUP"
HMAC_SECRET=$(openssl rand -hex 32)
GIT_REPO="git@github.com:Elijah-ug/KlassApp.git"
GIT_BRANCH="main"
PHP_VERSION="8.3"

echo "========================================"
echo " KlassApp App Server Provisioning"
echo " Domain: $APP_DOMAIN"
echo "========================================"

# ── 1. System Updates ──────────────────────
echo "[1/14] Updating system packages..."
apt update && apt upgrade -y

# ── 2. Install Core Packages ───────────────────────
echo "[2/14] Installing core packages..."
apt install -y \
    nginx \
    mysql-server \
    redis-server \
    supervisor \
    git \
    unzip \
    curl \
    certbot \
    python3-certbot-nginx \
    "php${PHP_VERSION}" \
    "php${PHP_VERSION}-fpm" \
    "php${PHP_VERSION}-mysql" \
    "php${PHP_VERSION}-mbstring" \
    "php${PHP_VERSION}-xml" \
    "php${PHP_VERSION}-curl" \
    "php${PHP_VERSION}-zip" \
    "php${PHP_VERSION}-bcmath" \
    "php${PHP_VERSION}-gd" \
    "php${PHP_VERSION}-intl" \
    "php${PHP_VERSION}-redis" \
    "php${PHP_VERSION}-sqlite3"

# ── 3. Install Composer ────────────────────
echo "[3/14] Installing Composer..."
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
fi

# ── 4. Configure MySQL (memory-tuned for 2 GB droplet) ─────
echo "[4/14] Configuring MySQL..."

# Memory-tuned config for 2 GB droplet
cat > /etc/mysql/mysql.conf.d/klassapp.cnf << 'MYSQLCONF'
[mysqld]
innodb_buffer_pool_size = 384M
innodb_redo_log_capacity = 134217728
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
max_connections = 50
key_buffer_size = 16M
thread_cache_size = 8
table_open_cache = 200
performance_schema = OFF
MYSQLCONF

systemctl restart mysql

# Create database
mysql -u root << SQL
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

# Secure MySQL (no password set yet, so skip validate_password)
mysql -u root << SQL
ALTER USER 'root'@'localhost' IDENTIFIED BY '$(openssl rand -base64 16)';
DELETE FROM mysql.user WHERE User='';
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';
FLUSH PRIVILEGES;
SQL

# ── 5. Configure PHP-FPM (tuned for 2 GB droplet) ──────────
echo "[5/14] Configuring PHP-FPM..."
PHP_FPM_POOL="/etc/php/${PHP_VERSION}/fpm/pool.d/www.conf"
sed -i 's/^pm.max_children.*/pm.max_children = 10/' "$PHP_FPM_POOL"
sed -i 's/^pm.start_servers.*/pm.start_servers = 3/' "$PHP_FPM_POOL"
sed -i 's/^pm.min_spare_servers.*/pm.min_spare_servers = 2/' "$PHP_FPM_POOL"
sed -i 's/^pm.max_spare_servers.*/pm.max_spare_servers = 6/' "$PHP_FPM_POOL"
sed -i 's/^;pm.max_requests.*/pm.max_requests = 500/' "$PHP_FPM_POOL"
sed -i 's/^memory_limit.*/memory_limit = 256M/' "/etc/php/${PHP_VERSION}/fpm/php.ini"
systemctl restart "php${PHP_VERSION}-fpm"

# ── 6. Setup Redis (memory-tuned) ───────────────────
echo "[6/14] Configuring Redis..."
sed -i 's/^# maxmemory .*/maxmemory 128mb/' /etc/redis/redis.conf
sed -i 's/^# maxmemory-policy noeviction/maxmemory-policy allkeys-lru/' /etc/redis/redis.conf
systemctl restart redis

# ── 7. Setup Deploy Key for Git ───────────────────
echo "[7/14] Setting up deploy key..."
mkdir -p /root/.ssh
chmod 700 /root/.ssh

cat > /root/.ssh/klassapp-deploy << 'DEPLOYKEY'
-----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAAAMwAAAAtzc2gtZW
QyNTUxOQAAACDbkLI6FoWM8qMuCbX5/NXCI/2JWVzlUeA95xxmmc4IuAAAAJgXQ+YXF0Pm
FwAAAAtzc2gtZWQyNTUxOQAAACDbkLI6FoWM8qMuCbX5/NXCI/2JWVzlUeA95xxmmc4IuA
AAAED8l+qSCa1UxotU6BddSd34TI8zytbc6liPunJFJqEETtuQsjoWhYzyoy4Jtfn81cIj
/YlZXOVR4D3nHGaZzgi4AAAAD2tsYXNzYXBwLWRlcGxveQECAwQFBg==
-----END OPENSSH PRIVATE KEY-----
DEPLOYKEY

chmod 600 /root/.ssh/klassapp-deploy

cat > /root/.ssh/config << 'SSHCONFIG'
Host github.com
    HostName github.com
    IdentityFile /root/.ssh/klassapp-deploy
    StrictHostKeyChecking no
SSHCONFIG

chmod 600 /root/.ssh/config

# Add GitHub to known hosts
ssh-keyscan github.com >> /root/.ssh/known_hosts 2>/dev/null

# ── 8. Clone Repository ───────────────────
echo "[8/14] Cloning repository..."
if [ -d "$APP_DIR/.git" ]; then
    echo "Repository exists, pulling latest..."
    cd "$APP_DIR"
    git pull origin "$GIT_BRANCH"
else
    mkdir -p "$APP_DIR"
    git clone --branch "$GIT_BRANCH" "$GIT_REPO" "$APP_DIR"
fi

# ── 9. Setup .env ─────────────────
echo "[9/14] Configuring environment..."
cd "$APP_DIR"

if [ ! -f ".env" ]; then
    cp .env.example .env
fi

# Set env values BEFORE composer install so artisan can read them
sed -i "s|^APP_URL=.*|APP_URL=https://${APP_DOMAIN}|" .env
sed -i "s|^APP_ENV=.*|APP_ENV=production|" .env
sed -i "s|^APP_DEBUG=.*|APP_DEBUG=false|" .env
sed -i "s|^APP_NAME=.*|APP_NAME=KlassApp|" .env

sed -i "s|^DB_HOST=.*|DB_HOST=127.0.0.1|" .env
sed -i "s|^DB_PORT=.*|DB_PORT=3306|" .env
sed -i "s|^DB_DATABASE=.*|DB_DATABASE=${DB_NAME}|" .env
sed -i "s|^DB_USERNAME=.*|DB_USERNAME=${DB_USER}|" .env
sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASS}|" .env

sed -i "s|^REDIS_HOST=.*|REDIS_HOST=127.0.0.1|" .env
sed -i "s|^REDIS_PASSWORD=.*|REDIS_PASSWORD=null|" .env

sed -i "s|^QUEUE_DRIVER=.*|QUEUE_DRIVER=redis|" .env

sed -i "s|^EVOLUTION_API_URL=.*|EVOLUTION_API_URL=http://${EVOLUTION_PRIVATE_IP}:8081|" .env
sed -i "s|^EVOLUTION_API_KEY=.*|EVOLUTION_API_KEY=${EVOLUTION_API_KEY_PLACEHOLDER}|" .env
sed -i "s|^EVOLUTION_INSTANCE_NAME=.*|EVOLUTION_INSTANCE_NAME=klassapp|" .env

sed -i "s|^WHATSAPP_HMAC_SECRET=.*|WHATSAPP_HMAC_SECRET=${HMAC_SECRET}|" .env
sed -i "s|^WHATSAPP_BUSINESS_NUMBER=.*|WHATSAPP_BUSINESS_NUMBER=+256793844906|" .env
sed -i "s|^WHATSAPP_BUSINESS_NAME=.*|WHATSAPP_BUSINESS_NAME=KlassApp|" .env
sed -i "s|^WHATSAPP_SEND_DELAY=.*|WHATSAPP_SEND_DELAY=1200|" .env

sed -i "s|^FILESYSTEM_DRIVER=.*|FILESYSTEM_DRIVER=local|" .env

sed -i "s|^MAIL_DRIVER=.*|MAIL_DRIVER=log|" .env

sed -i "s|^TIMEZONE.*|TIMEZONE='Africa/Kampala'|" .env

# ── 10. Install Dependencies & Generate Key ─────────────────────
echo "[10/14] Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Generate app key (now that vendor exists)
php artisan key:generate --force --no-interaction

# Install Node dependencies if package.json exists
if [ -f "package.json" ]; then
    echo "Installing Node dependencies..."
    if ! command -v npm &> /dev/null; then
        apt install -y nodejs npm
    fi
    npm ci --no-audit 2>/dev/null || npm install --no-audit
    npm run build 2>/dev/null || echo "No build script found, skipping."
fi

# ── 11. Run Migrations ────────────────────
echo "[11/14] Running database migrations..."
php artisan migrate --force --no-interaction

# ── 12. Set Permissions & Optimize ────────────────────────
echo "[12/14] Setting permissions..."
chown -R www-data:www-data "$APP_DIR/storage"
chown -R www-data:www-data "$APP_DIR/bootstrap/cache"
chmod -R 775 "$APP_DIR/storage"
chmod -R 775 "$APP_DIR/bootstrap/cache"

php artisan storage:link --no-interaction 2>/dev/null || true

echo "Optimizing Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# ── 13. Configure Nginx ───────────────────
echo "[13/14] Configuring Nginx..."

cat > "/etc/nginx/sites-available/klassapp" << NGINXCONF
server {
    listen 80;
    server_name ${APP_DOMAIN} www.${APP_DOMAIN};
    root ${APP_DIR}/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    index index.php;

    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    location /api/whatsapp/ {
        proxy_buffering off;
        fastcgi_pass unix:/var/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root/index.php;
        include fastcgi_params;
    }

    location ~ \.php\$ {
        fastcgi_pass unix:/var/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }
}
NGINXCONF

ln -sf /etc/nginx/sites-available/klassapp /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

# ── 14. SSL via Let's Encrypt ─────────────────────
echo "[14/14] Obtaining SSL certificate..."
certbot --nginx -d "${APP_DOMAIN}" -d "www.${APP_DOMAIN}" \
    --non-interactive --agree-tos \
    -m "admin@${APP_DOMAIN}" \
    --redirect 2>/dev/null || echo "SSL: Will retry after DNS propagates. Run: certbot --nginx -d ${APP_DOMAIN}"

# ── Supervisor: Horizon ─────────────────────
echo "Configuring Supervisor for Horizon..."
cat > /etc/supervisor/conf.d/klassapp-horizon.conf << SUPERVISOR
[program:klassapp-horizon]
process_name=%(program_name)s
command=php ${APP_DIR}/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/horizon.log
stopwaitsecs=3600
SUPERVISOR

supervisorctl reread
supervisorctl update
supervisorctl start klassapp-horizon

# ── Laravel Scheduler ───────────────────────
echo "Configuring crontab..."
(crontab -l 2>/dev/null; echo "* * * * * cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1") | crontab -

# ── Health Check Endpoint ───────────────────
# Laravel's built-in /up endpoint handles this

# ── Final State ─────────────────────
echo ""
echo "========================================"
echo " Provisioning Complete!"
echo "========================================"
echo ""
echo " App URL:        https://${APP_DOMAIN}"
echo " App Dir:        ${APP_DIR}"
echo " DB Name:        ${DB_NAME}"
echo " DB User:        ${DB_USER}"
echo " DB Password:    ${DB_PASS}"
echo " HMAC Secret:    ${HMAC_SECRET}"
echo ""
echo " IMPORTANT — After Evolution API is set up on klassapp-evo:"
echo "   Update EVOLUTION_API_KEY in ${APP_DIR}/.env"
echo "   Then run: php ${APP_DIR}/artisan config:cache"
echo ""
echo " Save these credentials! They are not stored elsewhere."
echo "========================================"

# Save credentials to a file for reference
cat > /root/klassapp-credentials.txt << CREDS
=================================
KlassApp Credentials — $(date)
=================================
App URL:        https://${APP_DOMAIN}
DB Name:        ${DB_NAME}
DB User:        ${DB_USER}
DB Password:    ${DB_PASS}
HMAC Secret:    ${HMAC_SECRET}
Evolution API:  http://${EVOLUTION_PRIVATE_IP}:8081
WhatsApp No:    +256793844906
=================================
CREDS

chmod 600 /root/klassapp-credentials.txt
echo "Credentials saved to /root/klassapp-credentials.txt"
