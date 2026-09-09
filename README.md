# KlassApp

**Tools connected by intelligence.**

KlassApp is a multi-tenant school management platform built around **Toshi**, an AI agent that orchestrates the channels schools already use: WhatsApp, email, Drive, Slack, SMS, and the web dashboard. It is not just another SMS admin panel. The product direction is an **agentic protocol** for education: role-aware actions, human-in-the-loop approvals, and connectors that grow with the school.

Live product: [https://klassapp.xyz](https://klassapp.xyz)

Public open-source release (MIT, self-hostable) is planned for **Q1 2027**, after a full security review. Until then this repository is the working codebase for the hosted SaaS. Contributions and early feedback are welcome via GitHub and `community@klassapp.xyz`.

## What you get today

- **Multi-tenant SaaS**: every school is scoped by `school_id` end to end (queries, jobs, caches, UI).
- **School operations**: academics, attendance, exams and report cards, fees, staff and parent roles, onboarding wizard.
- **WhatsApp (live)**: Meta Cloud API for parent messaging, interactive menus, notifications, and delivery logging. This is shipped, not "upcoming."
- **Toshi**: in-product AI assistant for school workflows (role-aware, gated, auditable). Broader connector orchestration (Drive, Slack, and friends) is the protocol roadmap; WhatsApp and the dashboard are the primary live surfaces today.
- **Hosted on Laravel Cloud** at `klassapp.xyz` (EU-West-1). Local development uses Docker Compose or your own MySQL/Redis.

## Stack

| Layer | Version / notes |
|---|---|
| PHP | 8.4 |
| Laravel | 12.x |
| Frontend | Blade, Livewire 3, Vue 3.5 via `@vue/compat` (MODE 2), Vite 8 |
| CSS | Tailwind CSS 4 (CSS-first; no `tailwind.config.js`) |
| Data | MySQL 8, Redis 7 |
| Auth / API | Session web auth, Laravel Sanctum |
| AI | Laravel AI SDK + OpenAI-compatible LLM config for Toshi |
| Production | Laravel Cloud (`klassapp.xyz`) |

Agent and contributor conventions live in [`AGENTS.md`](AGENTS.md). Project history and verified ops notes live in [`knowledge.md`](knowledge.md). Provenance of the GeGoK12 fork is documented in [`docs/project-provenance.md`](docs/project-provenance.md).

## Local setup

### Requirements

- PHP 8.4+, Composer 2, Node.js 20+ (or current LTS), npm
- MySQL 8 and Redis 7 (or Docker Compose from this repo)
- Git

### 1. Clone and install

```bash
git clone https://github.com/KlassApp-Foundation/KlassApp.git
cd KlassApp
composer install
cp .env.example .env
php artisan key:generate
npm ci
```

If `npm ci` fails on peer deps, the repo ships `.npmrc` with `legacy-peer-deps=true` for known Vue 2-era peer declarations. Prefer that over deleting packages without an audit.

### 2. Configure `.env`

Minimum for a local boot:

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

# Optional: Toshi (disabled by default)
TOSHI_LLM_ENABLED=false
OPENAI_COMPATIBLE_URL=
OPENAI_COMPATIBLE_API_KEY=
OPENAI_COMPATIBLE_MODEL=

# Optional: WhatsApp Meta Cloud API (only if testing messaging locally)
WHATSAPP_BUSINESS_API_TOKEN=
WHATSAPP_BUSINESS_PHONE_NUMBER_ID=
WHATSAPP_BUSINESS_WABA_ID=
WHATSAPP_BUSINESS_VERIFY_TOKEN=
```

Generate `APP_KEY` with `php artisan key:generate` if you have not already. Toshi LLM calls fail loudly when enabled without an API key; leave Toshi off for a plain local UI smoke test.

### 3. Database and Redis

**Option A: Docker Compose** (app + MySQL + Redis + nginx on port 8080):

```bash
docker compose up -d
# Point DB_HOST / REDIS_HOST at the compose services when running PHP inside the app container,
# or keep 127.0.0.1 when using published ports from the host.
```

**Option B: Host MySQL/Redis** matching the `.env` values above.

Then:

```bash
php artisan migrate
php artisan storage:link
```

Seed data, if you use it, depends on the seeders you need for your task. Prefer factories and feature tests over production-like school data.

### 4. Frontend and app server

```bash
# Terminal 1: Vite (writes public/hot while running)
npm run dev

# Terminal 2: Laravel
php artisan serve
```

Open `http://127.0.0.1:8000`. For a production-like asset build without Vite HMR:

```bash
npm run build
php artisan serve
```

Do not leave `public/hot` behind when testing a production-style build; Laravel will try to load assets from a dead Vite server.

### 5. Tests

```bash
php artisan test --compact
# or a focused file:
php artisan test --compact tests/Feature/ExampleTest.php
```

PHPUnit is the project standard. Prefer factories and school-scoped fixtures. Never use real student or parent data for verification.

## Repository layout (high level)

- `app/` Laravel application code (HTTP, Livewire, services, jobs, console)
- `resources/views/` Blade (including the public marketing landing)
- `resources/assets/js/` Vue SFCs and app bootstrap (Vite entry)
- `routes/` web, API, and role-scoped route files
- `docs/` deeper guides (WhatsApp, testing, provenance). Some older `docs/dev/*` pages still describe retired stacks; trust this README and `knowledge.md` for current hosting and bundler facts.
- `AGENTS.md` standing rules for anyone (human or agent) changing the codebase

## Contributing

1. Open a focused branch off `main`. Prefer small, atomic PRs.
2. Match existing conventions in sibling files. Read `AGENTS.md` before non-trivial work.
3. Add or update PHPUnit coverage for behavior you change, then run the affected tests.
4. Keep every feature multi-tenant: scope by `school_id` throughout.
5. Do not commit secrets (`.env`, API keys, deploy keys). Fail loud when required keys are missing rather than baking defaults into config.

Questions and community contact: **community@klassapp.xyz**.

## Open source timeline

| Milestone | Target |
|---|---|
| Hosted SaaS (`klassapp.xyz`) | Live now |
| Public MIT source + self-hosting docs | **Q1 2027** |
| MCP-compatible connectors as a first-class protocol surface | Roadmap alongside the OSS release |

Until the public release, treat this tree as the private/working product codebase. The Q1 2027 open-source date is a real shipping plan, not a placeholder slogan.

## License

Open-source licensing (MIT) lands with the **Q1 2027** public release. Until then, all rights are reserved by KlassApp Foundation unless a separate agreement says otherwise.
