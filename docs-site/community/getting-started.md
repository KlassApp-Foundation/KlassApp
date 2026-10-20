---
title: Local setup
---

# Local setup

Run KlassApp on your own computer. This matches the repository `README.md`. There is no one-line installer.

## You need

| Tool | Version |
|---|---|
| PHP | 8.4+ |
| Composer | 2 |
| Node.js | 20+ (or current LTS) |
| MySQL and Redis | 8 and 7, or Docker Compose from this repo |
| Git | any current release |

Composer allows PHP 8.2 and newer. CI and the Docker image use 8.4. Use 8.4 if you can.

## 1. Clone and install

```bash
git clone https://github.com/KlassApp-Foundation/KlassApp.git
cd KlassApp
composer install
cp .env.example .env
php artisan key:generate
npm ci
```

If `npm ci` fails on peer dependencies, keep the repo `.npmrc`. It sets `legacy-peer-deps=true` for old Vue peer declarations.

## 2. Configure `.env`

Minimum to boot:

```ini
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=klassapp_local
DB_USERNAME=root
DB_PASSWORD=

CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database
```

Leave Toshi's model key empty for a plain UI check. `TOSHI_LLM_ENABLED=false` keeps the assistant off. New schools already start with the assistant off.

WhatsApp keys stay empty unless you are on the [local simulator](/community/whatsapp).

## 3. Database

Docker Compose (app, MySQL, Redis, nginx; the README uses port 8080):

```bash
docker compose up -d
```

Point `DB_HOST` at the compose services when PHP runs inside the app container. Use `127.0.0.1` when PHP runs on the host and the ports are published.

Or use MySQL and Redis you already have, matching the `.env` values above.

Then:

```bash
php artisan migrate
php artisan storage:link
```

Use factories, tests, and the demo school seeders. Never load real student or parent records.

## 4. Run

```bash
# Terminal 1 — Vite (writes public/hot while it runs)
npm run dev

# Terminal 2
php artisan serve
```

Open `http://127.0.0.1:8000`.

For a production-like asset build, stop `npm run dev`, delete `public/hot` if it is still there, then:

```bash
npm run build
php artisan serve
```

`npm run build` also builds these docs. If `public/hot` is left behind, Laravel tries to load assets from a Vite server that is not running.

## Next

[Tests](/community/tests). Messaging without Meta: [WhatsApp simulator](/community/whatsapp).
