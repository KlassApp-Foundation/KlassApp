---
title: Tests
---

# Tests

PHPUnit is the project standard. UI checks use Playwright under `e2e/`.

## PHPUnit

Narrow runs while you work:

```bash
php artisan test --compact tests/Feature/Path/ToYourTest.php
php artisan test --compact --filter=test_name
```

`composer.json` does not define a `test` script. Use `php artisan test`.

`phpunit.xml` registers `tests/Unit` and `tests/Feature`. `tests/Architecture/` is on disk and is not part of that default list.

There is no `npm test` script. Frontend checks are Playwright and `npm run build`.

## Full suite guard

```bash
bash scripts/test-guard.sh
```

That runs the full PHPUnit suite and compares failures with `tests/known-failures.txt`. A failure that is not on that list fails the script. A listed test that now passes also fails the script, so the list has to be updated in the same change that fixes it.

Do not export extra `phpunit.xml` variables in the shell before you run it. The script stops when those are set.

## Playwright

```bash
npm run test:e2e
```

Landing and UI scripts can also be run directly:

```bash
PREVIEW_BASE=http://127.0.0.1:8000 node e2e/your-script.cjs
```

Viewports used for landing checks are 375, 414, 768, and 1280.

## Rules for test data

- Build records with factories or a demo school seeder.
- Do not copy a real school, student, parent, or phone number into a test, a fixture, or a screenshot.
- Soft-deleted models stay in the database. Assert on `deleted_at`.
- Deletes inside a seeder stay scoped by `school_id`.

::: warning
A green test is not a substitute for opening the page when the change is visible. Docs pages are not a product UI test.
:::
