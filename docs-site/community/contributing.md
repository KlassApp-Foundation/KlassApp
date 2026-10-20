---
title: Contributing
---

# Contributing

The source is public on GitHub; supported self-hosting opens after an independent security review. The licence is MIT.

Questions: [community@klassapp.xyz](mailto:community@klassapp.xyz). Also read the [Code of Conduct](https://github.com/KlassApp-Foundation/KlassApp/blob/main/CODE_OF_CONDUCT.md).

## Before you start

Sync from the latest `main`. Do not start from a stale checkout.

```bash
git fetch origin
git checkout main
git pull --ff-only origin main
git checkout -b your-branch-name
```

If you already have a branch, merge or rebase `origin/main` into it before new work.

Local install and tests: [Local setup](/community/getting-started) and [Tests](/community/tests).

## Pull requests

- One concern per pull request.
- Show evidence. A PHPUnit filter, a Playwright run at the real viewports, or a staging check for UI. "Should work" is not enough.
- Scope queries, jobs, caches, and lists by `school_id`.
- Do not assume a merge is live on klassapp.xyz. Production is not auto-deployed from `main`.
- Keep the repository root clean. New screenshots and one-off scripts do not belong at the top. Run `BASE_REF=origin/main bash scripts/check-root-clutter.sh`.

## The public-repo rule

::: danger This repository is public
Never commit secrets, `.env` values, real student, parent, or school data, phone numbers, or screenshots of a real school. Use a demo school seeder. If you commit something by mistake, tell the maintainers at once. Rewriting history is not enough.
:::

Security reports go through `SECURITY.md`. Do not file a public issue for an undisclosed vulnerability.

## Review

External pull requests get a human review before merge. They are not self-merged on trust of the branch tip. If a branch mixes a useful fix with unsafe scope, maintainers may take the safe part into a smaller follow-up and leave the rest open.

## Tests to run

```bash
php artisan test --compact --filter=test_name
bash scripts/test-guard.sh
```

UI: `npm run build` or `npm run dev`. There is no Mix build and no `npm run production`.
