# Laravel Cloud Migration Assessment — KlassApp

> **Status**: PLANNING ONLY. Nothing has been migrated and nothing will be as part of this document.
> **Date**: 2026-08-14 · **Author**: Sisyphus (evidence: live production metrics + official Laravel Cloud docs fetched 2026-08-14)
> **Purpose**: decision input to review and decide on later — not a task to execute.

## 0. TL;DR Recommendation

**Do not migrate right now — but the gap is narrower than expected, and the reason is cost, not effort.**

- **Cost objection largely dissolves.** Laravel Cloud's current published pricing (verified live from `cloud.laravel.com/docs/pricing`): **Starter $5/mo**, **Growth $20/mo**, **Business $200/mo**, Enterprise custom — each includes **$5/mo usage credit**, and **scale-to-zero is on by default** (Starter). For a low-traffic admin tool like KlassApp, an all-in bill of **$5–30/mo is realistic** — i.e. **comparable to or cheaper than the current DigitalOcean VPS (~$18–24/mo)**. (Earlier draft figures "Pro $59 / Scale $199" were unverified and are **wrong** — corrected below.)
- **The real decision factors are effort, lock-in, and preview environments**, not monthly cost:
  - Migration effort: **medium** (2–4 days incl. storage + DB move + webhook re-pointing), no app code changes required.
  - The one thing Cloud uniquely gives KlassApp that the VPS cannot cheaply: **fully isolated per-PR preview environments** — but those require the **Business plan ($200/mo)**. A $12 staging droplet achieves 90% of the same value for CI.
- **Recommended triggers to re-open this decision**: (1) a *third* deploy-related production incident, (2) sustained load >0.7 on the 2 vCPU droplet, or (3) queue backlog becoming routine. Until then: stay on the VPS with the fixes already applied tonight (queue restart on deploy) and optionally add a GitHub Actions pipeline (~$0 infra).

## 1. Current Production Reality (measured live 2026-08-14)

### Host
- **Provider: DigitalOcean** (not Hetzner — the "Hetzner VPS" claim in older notes is wrong). Droplet id 578598104, IP 46.101.111.131 (DO range), **2 vCPU / 2 GB / 87 GB disk** (23 GB used).
- Monthly cost class: **~$18–24/mo** (exact plan not queryable via metadata; verify in billing panel).

### Load (all measured live, near-idle)
| Signal | Value |
|---|---|
| Load average | 0.09 (≈ idle on 2 vCPU) |
| App container CPU | 0.17% |
| MySQL container CPU | 5.25% (busiest) |
| App memory | 290 MiB / 1.9 GiB |
| MySQL memory | 486 MiB / 1.9 GiB |
| php-fpm workers | 2 |

### Data scale
| Table | Rows |
|---|---|
| users | 1,376 |
| student_academics | 1,186 |
| marks | **664,336** |
| exams | 203 |
| schools | 20 |
| report_generations | 18 (all-time) |

### Storage
- `storage/app`: 421 MB total — **418 MB is generated report PDFs** (`storage/app/reports`).
- MySQL data dir: 339 MB.
- Public uploads (`storage/app/public`): 72 KB — negligible.
- Redis: sub-10 MB used (queue driver is redis).

### Queue
- `jobs` pending: 0 · `failed_jobs`: 1 · driver: **redis** (recently fixed — `QUEUE_DRIVER`/`QUEUE_CONNECTION` confusion).
- Heavy path: `GenerateClassReportsJob`, `onQueue('reports')`, **timeout 900 s**, tries 1. 18 lifetime report_generations rows → bulk PDF generation is occasional, not continuous.

### Traffic
- **nginx access logging is DISABLED** (`access_log off;` in nginx conf) — no request-volume data exists. All other signals (load, workers, 20 schools) indicate a low-traffic admin tool.

## 2. Laravel Cloud Pricing — VERIFIED (2026-08-14, cloud.laravel.com/docs/pricing)

| Tier | Base /mo | Usage credit | First month | Scaling | Compute | MySQL | Serverless Postgres | Valkey (cache) | Custom domains | Log retention |
|---|---|---|---|---|---|---|---|---|---|---|
| **Starter** | **$5** | $5 | free | 1x (no autoscale) | Flex only | Flex only | ≤1 vCPU | Flex | 10 | 1 day |
| **Growth** | **$20** | $5 | — | 1–10x (fixed or auto) | Flex & Pro | Flex & Pro | ≤4 vCPU | Flex & Pro | 50 | 7 days |
| **Business** | **$200** | $5 | — | Unlimited | Flex & Pro | Flex & Pro | ≤10 vCPU | Flex & Pro | 250 | 30 days |
| Enterprise | custom | — | — | custom | custom | custom | custom | custom | custom | custom |

Key mechanics (all verified from the pricing doc):
- **Scale to Zero is on by default** (Starter) — idle environments cost ~nothing; resources "sleep" after a configurable idle window (5 m in examples).
- **Always-on resources cap at 28 days (672 h) per billing cycle** — you are not billed for a full month of always-on compute.
- **Metered usage** rides on top of the base, offset by the $5 credit, reset each cycle. Sample line items in the doc: Flex 2 GB compute ≈ **$0.03571/h** (~$13/mo for 365 always-on hours; far less with scale-to-zero).
- **Managed MySQL**: Flex sizes **scale to zero when idle**, daily backups, all regions.
- **Serverless Postgres** (Neon-powered): scale to zero, autoscaling, point-in-time recovery.
- **Object Storage** (Cloudflare R2-backed, S3-compatible): **$0.02/GB-mo** storage, $0.005/1k Class A ops, $0.0005/1k Class B ops, **data transfer FREE**.
- **Managed queues**: per-second worker billing + per-queue-operation metering; scales to zero; built-in failed-job dashboard with retry/bulk actions (no Horizon needed).
- **Preview environments** (fully isolated per PR: DB + storage + app): available across plans (Business includes the largest limits; per-PR envs are a headline feature — verify exact plan-gating in the docs before deciding, earlier agent research claimed Scale-only but the current plan structure has no "Scale" tier).

### Honest all-in estimate for KlassApp's shape (low traffic + occasional batch PDF jobs)
- **Starter ($5 base):** compute mostly asleep outside school hours; ~421 MB R2 storage ≈ $0.01; minimal egress (free); a few queue runs per month. **Realistic: $5–15/mo.**
- **Growth ($20 base):** autoscaling + Pro compute if ever needed, 7-day logs. **Realistic: $20–40/mo.**
- **Business ($200 base):** only worth it if per-PR preview environments justify 10× the price of Growth.

**vs. current VPS ~$18–24/mo → Laravel Cloud Starter is likely CHEAPER; Growth is roughly parity.**

## 3. Tonight's Pain Points vs Each Option

| Tonight's problem | Laravel Cloud | In-place fix (already done or ~$0) |
|---|---|---|
| 1. Queue worker ran stale code after deploys | ✅ managed workers restart on deploy, zero-downtime | ✅ **DONE tonight**: deploy script now runs `php artisan queue:restart` |
| 2. No isolated env before touching prod | ✅ per-PR preview environments (full stack) | ⚠️ staging droplet (~$12/mo) or GitHub Actions deploy-to-staging |
| 3. Fragile hand-maintained SSH deploy | ✅ git-push + deploy hooks, managed runtime | ⚠️ GitHub Actions pipeline reusing existing script ($0) |

2 of 3 are solved tonight for free; isolation is the only real Cloud-only win and it comes with the Business plan.

## 4. Migration Requirements — VERIFIED (official docs, 2026-08-14)

### Deploy model
- **git-push deploys** from GitHub/GitLab/Bitbucket; optional deploy hooks (HTTP POST). **Build command** (e.g. `composer install --no-dev && npm run build`) and **deploy command** (migrations) configured per environment.
- **Dockerfile/entrypoint.sh are NOT used** — Cloud runs its own managed runtime (PHP 8.2/8.3/8.4/8.5; Octane+FrankenPHP optional; Inertia SSR optional). Our hand-rolled `queue:work --timeout=900` loop in entrypoint.sh is replaced by managed queues / app-cluster background processes (scheduler runs on the App or Worker cluster, no cron).

### Database
- Options: **managed MySQL** (Flex/Pro, scale-to-zero, daily backups), **Serverless Postgres** (Neon), or **BYO** external DB via env vars.
- Migration = standard **dump/import** (mysqldump → import). 339 MB → trivial. Verify utf8mb4/collation on import.

### File storage
- **Object Storage (R2-backed, S3-compatible)** — attaching a bucket auto-injects `FILESYSTEM_DISK` + S3-compatible env vars. `Storage::` facade calls keep working; `config/filesystems.php` already has an s3 disk (dummy values to be replaced).
- **Must migrate 421 MB** (418 MB report PDFs + uploads) from local disk to R2 bucket. Report-card PDF URLs / download links must be re-pointed (public bucket base URL or pre-signed URLs).
- ~$0.02/GB-mo → 421 MB ≈ **$0.01/mo**. Egress free.

### Queue
- Managed queues recommended (per-second billing, scale-to-zero, built-in failed-job dashboard). Driver stays redis-compatible; no app code change. `--timeout=900` maps to queue/worker configuration.

### Env / secrets
- `.env` values move to the Cloud **dashboard env store** (encrypted **Secrets** feature). APP_KEY etc. managed there. No checked-in .env.

### Webhooks / integrations
- WhatsApp Evolution API webhooks pointing at the VPS/domain must be **re-pointed** after migration (domain stays klassapp.xyz, so DNS cutover is the main step).

### App-code changes required
- **None found.** No vapor.yml, no scheduler entries in `routes/console.php`, composer PHP ^8.2–8.4 compatible with Cloud's PHP 8.2–8.5 runtimes. Hardcoded-host assumptions (localhost DB/Redis) become env-driven as on any PaaS.

## 5. Maturity / Risk Assessment (2026-08-14)

- **Mature platform**: multi-runtime (PHP/Node/Bun/Deno), managed queues, managed DBs, R2 object storage, Nightwatch monitoring, WAF/edge security — all documented and GA-consistent. Backed by Laravel + AWS + Cloudflare R2/Neon partnerships.
- **Residual risks** for a system holding real student records:
  - **Cold starts**: scale-to-zero means first request after idle can be slow (community-reported; mitigate by not scaling to zero on production or keeping a minimum instance).
  - **Pricing/feature churn**: plans have changed (this doc itself corrects earlier stale figures) — re-verify the pricing page at decision time.
  - **Lock-in**: cloud.yaml, managed queues, R2 blob, env store, dashboard-managed deployments. Porting back to self-host is real work.
  - **Data residency**: regions available (US East (Ohio) shown in examples; region selectable at app creation) — choose consciously for Ugandan school data.
  - **Nightly batch jobs**: 900 s timeout jobs must fit queue/worker config; R2 egress free, so PDF download load is not a cost issue.

## 6. Decision Matrix

| Option | Monthly cost | Fixes tonight's bugs? | Effort | Risk |
|---|---|---|---|---|
| **Stay on DO + fixes applied tonight** | ~$18–24 | ✅ 2/3 (isolation still missing) | done | low |
| Hardened DO + GH Actions CI + staging droplet | ~$30–40 | ✅ 3/3 (staging ≈ isolation) | 1–2 days | low |
| **Laravel Cloud Starter** | ~$5–15 | ✅ 3/3 (no per-PR envs) | 2–4 days | medium (new platform, cold starts, lock-in) |
| **Laravel Cloud Growth** | ~$20–40 | ✅ 3/3 + autoscaling + 7-day logs | 2–4 days | medium |
| Laravel Cloud Business (per-PR envs) | $200+ | ✅ 3/3 incl. full preview envs | 2–4 days | medium |

## 7. Recommendation

**Do not migrate now.** The cost objection is gone (Starter is likely cheaper than the VPS), but there is no *present pain* that Cloud uniquely solves for KlassApp:
- The deploy-related incident tonight is fixed in place (`queue:restart` on deploy).
- The only Cloud-only feature with real value (per-PR preview environments) requires the Business plan — overkill while a $12 staging droplet + GH Actions covers the CI gap.
- Migration itself is 2–4 days of undramatic work (dump/import, R2 sync, webhook re-point) — it will not get harder later; the platform will only mature further.

**Triggers to re-open (any one):**
1. A third deploy-related production incident (pattern, not one-off).
2. Sustained load >0.7 on the droplet or routine queue backlog.
3. Data volume grows past ~2 GB or schools grow past ~50 (then managed DB + R2 + autoscaling start paying for themselves).
4. A need for frequent full-stack preview environments before merge (e.g. onboarding Toshi-style multi-step flows).

**If a trigger fires**: re-verify pricing page, then target **Growth ($20/mo)** as the likely right tier; plan the storage cutover (R2 sync + PDF URL re-point) as the only task with user-visible impact.

## 8. Open Questions (non-blocking)

1. Exact DO droplet plan/billing ($18 vs $24) — check billing panel.
2. Exact plan-gating of per-PR preview environments on the current plan structure (pricing page fetched shows limits per tier; confirm the feature's tier gate in docs).
3. Cold-start tolerance for school-hours teacher usage (test with a Starter trial before committing).
4. Data-residency requirement for Ugandan student data (region choice: US vs EU).

---
*Sources: live production metrics (SSH into 46.101.111.131, 2026-08-14); cloud.laravel.com/docs/pricing + docs (runtimes, compute, queues, quickstart, object-storage) fetched 2026-08-14; codebase review (config/*, docker-compose.yml, entrypoint.sh, scripts/deploy-manual.sh, app/Jobs, ReportCardsController).*
