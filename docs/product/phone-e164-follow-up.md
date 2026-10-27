# Follow-up: convert stored phone numbers to E.164

This is a proposal only. No command in this change rewrites stored numbers, and nothing here should be run against production.

New and edited numbers are already stored as E.164. Older rows can still be local (`0701 234 567`, `256701234567`, or digits with spaces). Lookups accept those shapes, so login and WhatsApp matching keep working. A later conversion should be a reviewed artisan command, not a migration that writes on deploy.

## Proposed command

`php artisan phones:to-e164`

- The default is a dry run. It writes nothing.
- `--commit` is the only switch that updates rows, and only after a dry run for that environment has been read.
- The school's country is the region used when a number has no `+`. A number that already starts with `+` is parsed as written.
- A value that is already E.164 is reported as unchanged.
- A value libphonenumber cannot parse is reported and left as it is.

## What the dry run reports

One line per candidate column, at least:

| Column | Where |
| --- | --- |
| `users.mobile_no` | every user |
| `schools.phone` | every school |
| parent and visitor contact fields that store a phone | the same parser |

Each line includes the table, primary key, school id, the current value, and either the E.164 it would become or `unparseable`. A summary counts unchanged, convertible, and unparseable rows. The dry run does not print a full dump of customer numbers into a commit, a pull request, or `knowledge.md`.

## What `--commit` would do

For each convertible row, store the E.164 form and leave every other column alone. Unparseable rows stay unchanged and are counted. The command is idempotent: a second dry run after a commit reports those rows as unchanged.

Do not add this command to a deploy step. Run the dry run, read the counts, then decide whether `--commit` is warranted for that environment.
