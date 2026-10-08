---
title: Getting started
---
# Getting started
Run KlassApp on your own computer in about 30 minutes.

## You need
| Tool | Version |
|---|---|
| PHP | 8.4+ |
| Composer | 2 |
| Node.js | 20+ |
| MySQL / Redis | 8 / 7, or Docker Compose |

## 1. Clone and install
```bash
git clone https://github.com/KlassApp-Foundation/KlassApp.git
cd KlassApp && composer install && cp .env.example .env && php artisan key:generate && npm ci
```
## 2. Configure .env
## 3. Database
## 4. Run
## Tests {#tests}
```bash
php artisan test --compact
```
::: warning
Never use real student or parent records locally. Use factories and seeders.
:::
<!-- Source: README.md "Quick Start". Keep ONE copy: README links here after migration. -->
