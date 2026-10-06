# Proposal: catch MySQL-only migration problems before deploy (not built)

Status: proposal, 2026-10-06. Follows the user_preferences incident: the migration
passed the sqlite test suite twice, then failed on staging MySQL twice (foreign key
type mismatch 3780 because users.id is INT UNSIGNED while the new column was BIGINT;
then a half-created table made the retry fail with 1050). It was fixed forward
(#994, #995), and on 2026-10-06 the clean create path was proven on staging MySQL:
the migration was rolled back alone, the drop was confirmed, a fresh migrate
succeeded, and a second migrate was a no-op.

## What sqlite cannot see

- Foreign key column type compatibility (BIGINT vs INT UNSIGNED): sqlite ignores
  column types, MySQL rejects at constraint time; if the CREATE already ran, a
  failure leaves a half-created table behind.
- Unique-key length limits under utf8mb4 (191 vs 255), index prefix rules,
  reserved words, engine-specific defaults, date/datetime strictness.

## Proposed guard (small, CI only)

- A CI job, "MySQL migration smoke", that starts a mysql:8 service container and
  runs `php artisan migrate --force` from an empty database, then runs
  `php artisan migrate` again to prove the second run is a no-op. Full history is
  the honest check; `migrate --pretend` prints SQL without executing, so it would
  NOT have caught the 3780 case (the ALTER only fails when it runs).
- Scope: mark it required only for pull requests that touch database/migrations
  (path filter), so ordinary PRs stay fast.
- Keep it in GitHub Actions with the same PHP version as the app; budget about a
  minute or two per run.

Do not build until Rasta approves; it is one workflow file plus a service container.
