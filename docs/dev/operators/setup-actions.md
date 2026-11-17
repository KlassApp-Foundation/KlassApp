# Operator note: making a term current and linking a class

**Audience:** whoever runs the deployment. Not a school-facing page.
**Applies to:** the setup actions in PR #1356. Draft: not merged, not deployed.

The school-facing guide is `docs-site/help/reports/setup-actions.md`. It says only what the admin clicks. This note keeps the commands, the dry run and the approval rules out of that page.

## What the two actions change

- **Make this the current term** moves the `current` flag between terms of one school. The term that was current becomes `last`, or `next` when the chosen term starts later.
- **Link to a class** creates one `standards_link` row: school, academic year, class level, section. Running it twice does nothing the second time.

Neither action edits marks, releases or reports.

## Commands (dry run first)

```bash
php artisan terms:set-current {school} {term} [--apply]
php artisan classes:link-section {school} {section} {standard} [--year=] [--apply]
```

- Without `--apply`, each command prints what would change and writes nothing.
- With `--apply`, it writes and adds one log line: `setup.current-term` or `setup.link-section`, with the school, the ids, the actor and `via`.
- The screens write the same log line plus an activity-log entry under `setup`.

## Before every production write

1. Take a verified backup and record its id and time. Restore it to a scratch database and check row counts before relying on it.
2. Run the dry run and keep the output.
3. Have the person who owns the deployment approve that output.
4. Run with `--apply`.
5. Read back: the term flags, or the new `standards_link` row, plus the log line.

## Scoping and refusals

- Every lookup is scoped by `school_id`. An id from another school gets the same answer as a missing one ("not found for this school" in the command), and nothing changes.
- A term status is one of `last`, `current`, `next`. Do not write a fourth value.
- The screens are for school admins only (the existing `full-school-admin` gate). Head teachers, teachers and every other role are turned away.

## Rollback

- A wrong current term: run `terms:set-current` again for the right term, dry run first.
- A wrong class link: a link is one row. Removing it is a data decision, not covered by these commands; report it before changing anything.
