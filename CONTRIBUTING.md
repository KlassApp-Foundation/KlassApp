# Contributing to KlassApp

Thanks for showing up. KlassApp is a real multi-tenant school product (Laravel + Toshi), not a demo repo. Contributions are welcome. Keep the bar practical: sync first, ship small, show evidence.

Questions and maintainer contact: **community@klassapp.xyz**.

Please also read the [Code of Conduct](CODE_OF_CONDUCT.md).

## Before you start: sync from `main`

Do not begin work on a stale checkout.

```bash
git fetch origin
git checkout main
git pull --ff-only origin main
git checkout -b your-branch-name
```

If you already have a feature branch, merge or rebase `origin/main` into it **before** new work. This matches how maintainers work on this repo every session.

## What this project is

| Layer | What we use |
|---|---|
| Backend | PHP 8.4, Laravel 12 |
| UI | Blade, Livewire 3, Alpine.js, Tailwind CSS v4 |
| App shell JS | Vue 3.5 via `@vue/compat` (MODE 2), Vite 8 |
| Data | MySQL 8, Redis |
| Tests | PHPUnit (feature/unit) and Playwright (`e2e/*.cjs`) |

More stack and local setup detail lives in [`README.md`](README.md). Agent and maintainer standing rules live in [`AGENTS.md`](AGENTS.md). Session history and verified ops notes live in [`knowledge.md`](knowledge.md).

## How we review external contributor PRs

External PRs get a real human review before merge. They are **not** self-merged on trust of the branch tip alone.

Concrete example from September 2026: Elijah-ug opened [#552](https://github.com/KlassApp-Foundation/KlassApp/pull/552) with hands-on onboarding findings and a mixed code branch. Maintainers:

1. Read the notes and the full diff.
2. Thanked the real findings in a public review comment.
3. Kept #552 open as the source of truth for those findings.
4. Closed a duplicate tip ([#625](https://github.com/KlassApp-Foundation/KlassApp/pull/625)).
5. Extracted only the safest, verified pieces into a clean follow-up ([#638](https://github.com/KlassApp-Foundation/KlassApp/pull/638)) with PHPUnit against real fixture sheets.
6. Filed the remaining wishlist as tracked issues instead of silently dropping it.

If a PR mixes good fixes with unsafe scope (for example removing production-required traits, adding undeclared packages, or hardcoding weak passwords), expect maintainers to take the good parts forward separately rather than merging the kitchen sink.

## What makes a good PR

- **One concern per PR.** Import validation and a docs rewrite do not belong together.
- **Evidence over claims.** "Should work" is not enough. Prefer a passing PHPUnit filter, a Playwright script at real viewports, or a Commands API / staging check when the change is UI or deploy-sensitive.
- **Staging first for product UI.** Production is Laravel Cloud and is not auto-deployed from `main`. Do not assume a merge is live on `klassapp.xyz`.
- **Multi-tenant by default.** Queries, jobs, caches, and lists must stay scoped by `school_id`.
- **No secrets.** Never commit `.env`, tokens, or real student/school data. Use fixtures and demo accounts.

## Running tests locally

PHPUnit (narrow is preferred while iterating):

```bash
php artisan test --compact tests/Feature/Path/ToYourTest.php
# or
php artisan test --compact --filter=test_name
```

Playwright (landing and UI verify scripts under `e2e/`):

```bash
# Against local app
PREVIEW_BASE=http://127.0.0.1:8000 node e2e/your-script.cjs

# Against staging after a staging deploy
PREVIEW_BASE=https://klassapp-staging-7mpoqg.laravel.cloud node e2e/your-script.cjs
```

Typical landing viewports we check: **375, 414, 768, 1280**.

Frontend assets: `npm run build` (or `npm run dev` while iterating). There is no Mix / `npm run production`.

## Opening a pull request

1. Sync from latest `main` (see above).
2. Push your branch and open a PR against `main`.
3. Fill in the PR template: what changed, how you verified it, staging notes if UI.
4. Wait for review. Maintainers may ask for a smaller PR, more tests, or a follow-up issue instead of merging everything at once.

## How to reach maintainers

- Email: **community@klassapp.xyz**
- GitHub: issues and PR comments on this repository
- Product site: [https://klassapp.xyz](https://klassapp.xyz)

Security vulnerabilities: see [`SECURITY.md`](SECURITY.md). Do not file public issues for undisclosed security reports.
