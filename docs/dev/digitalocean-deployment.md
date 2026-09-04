# DigitalOcean Deployment — 10-School Production Environment

## Context

GitHub Student Developer Pack provides **$200 in DigitalOcean credit for 1 year**. This spec covers provisioning a production environment on that credit, serving **10 real schools** (~500–1,000 parents + 100 staff users) with live WhatsApp traffic. When the school count grows beyond what the $36/month stack can handle, paying schools will cover the upgrade.

---

## Stack Overview

```
                      ┌──────────────┐
                      │  Cloudflare   │ (optional: DNS + SSL)
                      └──────┬───────┘
                             │
                 ┌───────────▼───────────┐
                 │    App Droplet (Laravel)   │
                 │   2 vCPU / 4 GB / 80 GB    │
                 │                            │
                 │  ┌──────────────────────┐  │
                 │  │ Nginx + PHP-FPM       │  │
                 │  │ Laravel 11+           │  │
                 │  │ Horizon (queue)       │  │
                 │  │ Scheduler (cron)      │  │
                 │  └──────────────────────┘  │
                 └───────────┬───────────────┘
                             │
          ┌──────────────────┼──────────────────┐
          │                                     │
 ┌────────▼────────┐                  ┌─────────▼─────────┐
 │  Managed MySQL   │                  │   Evolution API Droplet │
 │  1 vCPU / 1 GB   │                  │   1 vCPU / 2 GB / 50 GB │
 │  15 GB storage    │                  │                         │
 │  (DigitalOcean)  │                  │  Docker:                 │
 │                  │                  │  - Evolution API         │
 │                  │                  │  - MongoDB               │
 └─────────────────┘                  └──────────────────────────┘
```

### Why Two Droplets

Evolution API must remain **behind a firewall** — not directly exposed to the internet. Splitting the App and Evolution API onto separate droplets:

- Keeps the Evolution API on an internal VPC network (Laravel talks to it via private IP)
- Prevents WhatsApp session data from being co-located with the public-facing web server
- Makes it easy to scale or rebuild either component independently
- Follows the setup guide's production recommendation

---

## Resource Sizing & Cost

| Resource | Spec | Monthly Cost | Notes |
|---|---|---|---|
| **App Droplet** | 2 vCPU / 4 GB / 80 GB SSD | $24 | Laravel + Nginx + Horizon |
| **Evolution API Droplet** | 1 vCPU / 2 GB / 50 GB SSD | $12 | Docker: Evolution API + MongoDB |
| **Managed MySQL** | 1 vCPU / 1 GB / 15 GB | $15 | Backups included |
| **Spaces (optional)** | 100 GB + 500 GB transfer | $5 | Marksheet uploads, voice files |
| **Monthly Total** | | **$56** | |
| **Monthly Total (basic)** | MySQL on App Droplet instead of managed | **$36** | No managed DB backups |

### Credit Burn Rate

| Configuration | $200 lasts |
|---|---|
| Full stack (2 droplets + managed DB + Spaces) | ~3.5 months |
| Full stack, no Spaces | ~4 months |
| Basic stack (DB on App droplet, no Spaces) | ~5.5 months |
| Minimal (single droplet, DB on host) | ~8 months |

### Recommendation for 10-School Pilot

**Start with the basic stack** ($36/month = ~5.5 months):

- App Droplet (2 vCPU / 4 GB): Nginx, PHP-FPM, Laravel, MySQL on-host, Horizon, cron
- Evolution API Droplet (1 vCPU / 2 GB): Docker with Evolution API + MongoDB

Upgrade to Managed MySQL ($15/month) once testing proves stable and data becomes important to preserve.

---

## App Droplet Provisioning

### 1. Create Droplet

```
DigitalOcean → Droplets → Create Droplet

Region:    Frankfurt (best latency to Uganda/East Africa)
Image:     Ubuntu 24.04 LTS
Size:      Basic / 2 vCPU / 4 GB / 80 GB SSD
Auth:      SSH key
Hostname:  klassapp
```

### 2. Initial Setup

```bash
# SSH in
ssh root@<droplet-ip>

# Update & install core packages
apt update && apt upgrade -y
apt install -y nginx mysql-server php8.3 php8.3-fpm php8.3-mysql \
  php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath \
  php8.3-gd php8.3-intl php8.3-redis composer redis-server git \
  unzip supervisor certbot python3-certbot-nginx

# Secure MySQL
mysql_secure_installation
```

### 3. Database Setup

```sql
CREATE DATABASE klassapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'klassapp'@'localhost' IDENTIFIED BY '<strong-password>';
GRANT ALL PRIVILEGES ON klassapp.* TO 'klassapp'@'localhost';
FLUSH PRIVILEGES;
```

### 4. Deploy Application

```bash
# Create deploy user and directory
adduser deploy
mkdir -p /var/www/klassapp
chown deploy:deploy /var/www/klassapp

# Clone repo (as deploy user)
su - deploy
cd /var/www/klassapp
git clone <repo-url> .
cp .env.example .env
# Edit .env with production values (see below)

# Install dependencies
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate
php artisan storage:link

# Build frontend if any
npm ci && npm run build

# Set permissions
sudo chown -R deploy:www-data /var/www/klassapp
sudo chmod -R 775 /var/www/klassapp/storage
sudo chmod -R 775 /var/www/klassapp/bootstrap/cache
```

### 5. .env Configuration

```ini
# App
APP_NAME=KlassApp
APP_ENV=production
APP_DEBUG=false
APP_URL=https://klassapp.xyz

# Database (on-host MySQL)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=klassapp
DB_USERNAME=klassapp
DB_PASSWORD=<strong-password>

# Redis (for Horizon + cache)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null

# Queue
QUEUE_CONNECTION=redis

# Evolution API (internal VPC — private IP of Evolution droplet)
EVOLUTION_API_URL=http://10.0.0.3:8081
EVOLUTION_API_KEY=<evolution-api-key>
EVOLUTION_INSTANCE_NAME=klassapp

# WhatsApp
WHATSAPP_HMAC_SECRET=<openssl-rand-hex-32>
WHATSAPP_BUSINESS_NUMBER=+256793844906
WHATSAPP_BUSINESS_NAME=KlassApp Test
WHATSAPP_SEND_DELAY=1200

# Filesystem (local to start; Spaces later)
FILESYSTEM_DISK=local
```

### 6. Nginx Configuration

```nginx
# /etc/nginx/sites-available/klassapp
server {
    listen 80;
    server_name klassapp.xyz;
    root /var/www/klassapp/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    # Block access to sensitive paths
    location ~ /\.(?!well-known).* {
        deny all;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Webhook endpoints — no buffering for real-time WhatsApp
    location /api/whatsapp/ {
        proxy_buffering off;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root/index.php;
        include fastcgi_params;
    }
}
```

```bash
ln -s /etc/nginx/sites-available/klassapp /etc/nginx/sites-enabled/
rm /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx
```

### 7. SSL via Let's Encrypt

```bash
certbot --nginx -d klassapp.xyz
# Auto-renewal is configured by certbot
```

### 8. Supervisor for Horizon

```ini
# /etc/supervisor/conf.d/klassapp-horizon.conf
[program:klassapp-horizon]
process_name=%(program_name)s
command=php /var/www/klassapp/artisan horizon
autostart=true
autorestart=true
user=deploy
redirect_stderr=true
stdout_logfile=/var/www/klassapp/storage/logs/horizon.log
stopwaitsecs=3600
```

```bash
supervisorctl reread
supervisorctl update
supervisorctl start klassapp-horizon
```

### 9. Laravel Scheduler (Cron)

```bash
# As deploy user
crontab -e

# Add:
* * * * * cd /var/www/klassapp && php artisan schedule:run >> /dev/null 2>&1
```

---

## Evolution API Droplet Provisioning

### 1. Create Droplet

```
Region:    Same as App Droplet (Frankfurt)
Image:     Ubuntu 24.04 LTS
Size:      Basic / 1 vCPU / 2 GB / 50 GB SSD
Auth:      SSH key
Hostname:  klassapp-evo
VPC:       Same VPC as App Droplet (for private networking)
```

### 2. Install Docker

```bash
ssh root@<evolution-droplet-ip>

apt update && apt upgrade -y
apt install -y docker.io docker-compose-v2
systemctl enable --now docker
```

### 3. Docker Compose

```yaml
# /opt/evolution/docker-compose.yml
version: "3.8"
services:
  evolution-api:
    image: evolutionapi/evolution-api:latest
    ports:
      - "8081:8081"
    environment:
      AUTHENTICATION_API_KEY: "${EVOLUTION_AUTH_KEY}"
      DATABASE_ENABLED: "true"
      DATABASE_CONNECTION_URI: "mongodb://mongo:27017/evolution"
    restart: unless-stopped
    depends_on:
      - mongo
    networks:
      - evolution-net

  mongo:
    image: mongo:6
    volumes:
      - mongo-data:/data/db
    restart: unless-stopped
    networks:
      - evolution-net

volumes:
  mongo-data:

networks:
  evolution-net:
```

```bash
# Create .env
mkdir -p /opt/evolution
cd /opt/evolution
echo 'EVOLUTION_AUTH_KEY=<generate-strong-key>' > .env

# Start
docker compose up -d
```

### 4. Firewall (UFW)

```bash
ufw default deny incoming
ufw default allow outgoing

# Allow SSH
ufw allow 22

# Allow Evolution API only from App Droplet's private IP
ufw allow from 10.0.0.2 to any port 8081

ufw enable
```

### 5. Create Evolution API Instance

```bash
curl -X POST http://localhost:8081/instance/create \
  -H "apikey: <evolution-auth-key>" \
  -H "Content-Type: application/json" \
  -d '{"instanceName": "klassapp", "qrcode": true}'

# Save the instance.apiKey from the response — this is EVOLUTION_API_KEY in .env
```

### 6. Scan QR Code + Verify

```bash
# Get QR
curl http://localhost:8081/instance/qrcode/klassapp \
  -H "apikey: <evolution-auth-key>"

# Verify connected
curl http://localhost:8081/instance/connectionState/klassapp \
  -H "apikey: <evolution-auth-key>"
```

### 7. Configure Webhooks

```bash
# Inbound messages
curl -X POST http://localhost:8081/webhook/set/klassapp \
  -H "apikey: <evolution-auth-key>" \
  -H "Content-Type: application/json" \
  -d '{
    "webhookUrl": "https://klassapp.xyz/api/whatsapp/inbound",
    "webhookEvents": ["messages.upsert"],
    "webhookHeaders": {"apikey": "<evolution-auth-key>"}
  }'

# Delivery receipts
curl -X POST http://localhost:8081/webhook/set/klassapp \
  -H "apikey: <evolution-auth-key>" \
  -H "Content-Type: application/json" \
  -d '{
    "webhookUrl": "https://klassapp.xyz/api/whatsapp/delivery",
    "webhookEvents": ["messages.ack", "messages.error"],
    "webhookHeaders": {"apikey": "<evolution-auth-key>"}
  }'
```

---

## DigitalOcean Firewall (VPC-Level)

Apply a firewall at the DigitalOcean account level for defense-in-depth:

| Rule | Direction | Protocol | Port | Source |
|---|---|---|---|---|
| SSH | Inbound | TCP | 22 | Your IP |
| HTTP | Inbound | TCP | 80 | All IPv4/IPv6 |
| HTTPS | Inbound | TCP | 443 | All IPv4/IPv6 |
| Evolution API | Inbound | TCP | 8081 | App droplet private IP only |
| All outbound | Outbound | TCP/UDP | All | All |

Assign this firewall to **both droplets**.

---

## 10-School Capacity Estimate

### Expected Load

| Metric | Per School | 10 Schools |
|---|---|---|
| Parents (WhatsApp users) | 50–100 | 500–1,000 |
| Staff (WhatsApp users) | 10 | 100 |
| Monthly inbound messages | 200–500 | 2,000–5,000 |
| Monthly outbound messages | 300–800 | 3,000–8,000 |
| Peak concurrent requests | — | ~20 req/sec |
| Database size (3 months) | ~50 MB | ~500 MB |

### Resource Fit

| Resource | Capacity | 10-School Load | Headroom |
|---|---|---|---|
| App (2 vCPU / 4 GB) | ~100 req/sec PHP | ~20 req/sec | 5x |
| MySQL (1 vCPU / 1 GB — on-host) | ~500 qps | ~100 qps | 5x |
| Evolution API (1 vCPU / 2 GB) | ~50 msg/sec | ~5 msg/sec | 10x |
| 80 GB SSD (app) | ~70 GB usable | ~5 GB (code + logs + uploads) | 14x |

The basic stack has comfortable headroom for 10 schools. Scale-up triggers:

- App droplet CPU > 70% sustained → bump to 4 GB Premium ($48)
- Evolution API latency > 2s → bump to 2 GB Premium ($24)
- DB outgrows on-host → migrate to Managed MySQL

---

## Monitoring

### Minimal Viable Monitoring (Free)

```bash
# DigitalOcean built-in (free)
# - CPU, memory, disk, bandwidth graphs
# - Alert policies on CPU > 80% or disk > 85%
```

### Uptime & Health Checks

```bash
# Cron-based health check on App droplet (every 5 min)
*/5 * * * * curl -sf https://klassapp.xyz/api/health | grep -q '"ok"' \
  || echo "App down" | mail -s "Alert" admin@klassapp.com
```

### Evolution API Connection Monitor

```bash
# On App droplet (every 5 min)
*/5 * * * * curl -sf http://10.0.0.3:8081/instance/connectionState/klassapp \
  -H "apikey: <evolution-auth-key>" | grep -q '"open"' \
  || echo "Evolution API disconnected" | mail -s "Alert" admin@klassapp.com
```

---

## Pre-Launch Checklist

- [ ] App droplet provisioned, Nginx + PHP-FPM running
- [ ] MySQL database created, migrations run
- [ ] .env configured with production values
- [ ] Supervisor running Horizon
- [ ] Crontab running Laravel scheduler
- [ ] SSL active on klassapp.xyz
- [ ] Evolution API droplet provisioned, Docker running
- [ ] Evolution API instance created and QR scanned (connected)
- [ ] Webhooks configured and receiving (test with inbound message)
- [ ] UFW firewalls active on both droplets
- [ ] DigitalOcean cloud firewall active
- [ ] `WHATSAPP_HMAC_SECRET` generated and shared with n8n/Typebot
- [ ] Meta WhatsApp templates submitted and approved
- [ ] Fee reminder dry-run tested (`--dry-run`)
- [ ] Admin phone linked and menu verified
- [ ] Health check endpoint responding
- [ ] Alert policies configured for CPU/disk
- [ ] Delivery webhook confirmed receiving ACKs

---

## Post-Launch (Week 1)

- [ ] Monitor `message_delivery_log` for failed deliveries
- [ ] Watch Evolution API logs for disconnections
- [ ] Check Laravel Horizon dashboard for failed jobs
- [ ] Verify 24-hour window cost optimization is working (`last_inbound_at` being set)
- [ ] Review actual message costs (Meta Business Manager → Insights)
- [ ] Decide: migrate DB to Managed MySQL or stay on-host

---

## Monthly Cost Recap

| Configuration | Droplets | DB | Storage | Total/Month | $200 Duration |
|---|---|---|---|---|---|
| **Basic (recommended)** | $24 + $12 | On-host | Local | **$36** | ~5.5 months |
| Managed DB upgrade | $24 + $12 | $15 | Local | **$51** | ~4 months |
| Full stack | $24 + $12 | $15 | $5 (Spaces) | **$56** | ~3.5 months |
| Minimal single droplet* | $24 | On-host | Local | **$24** | ~8 months |

\* Single droplet: Evolution API runs on the same droplet as the app. Not recommended for production-like testing — violates the security model and the setup guide's production recommendation. Only use for very early smoke tests.

### Beyond the Credit

Once the $200 runs out (month 5–6), the ongoing cost for 10 schools is **$36/month**. As schools grow → scale up. The credit gives you nearly half a year to prove the model and secure paying customers.