# Rehearsal scripts (local only)

These scripts rehearse the report-card path from `docs/runbooks/kabale-report-cards.md` on a **scratch database**. They refuse to run unless the SQLite file name contains `rehearsal`.

```bash
cp .env .env.rehearsal          # then set DB_DATABASE=/tmp/klassapp-rehearsal.sqlite
php artisan --env=rehearsal migrate:fresh --seed
APP_ENV=rehearsal php rehearsal/01_school.php        # P5 school, term, bands, class, subjects
APP_ENV=rehearsal php rehearsal/02_learners.php      # 76 learners through UsersImport (real KLS)
APP_ENV=rehearsal php rehearsal/03_exams_import.php  # exam set + the eight CSVs
APP_ENV=rehearsal php rehearsal/04_release.php       # gaps as AB, submit, approve, release
APP_ENV=rehearsal php rehearsal/05_render.php        # 76 cards + merged class PDF
APP_ENV=rehearsal php rehearsal/06_parity.php        # every value against the fixture
APP_ENV=rehearsal php rehearsal/08_whatsapp.php      # 8 parents, stubbed sender, no network
APP_ENV=rehearsal php rehearsal/09_release_notify.php # release notify, stubbed sender
APP_ENV=rehearsal php rehearsal/10_scale.php school|learners|marks|release|render
```

Fixtures come from `~/Downloads/report-card-handoff/report-card-handoff/fixtures/` (synthetic, no real learners). Every sender is stubbed; nothing leaves the machine.

`state.json`, `scale-state.json` and the generated CSVs are scratch files and are not committed.
