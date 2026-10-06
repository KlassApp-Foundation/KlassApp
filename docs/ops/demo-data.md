# Demo data on staging

The two walkthrough schools — **Demo Junior School** (id 38) and **Demo Senior
School** (id 39) — are seeded by `DemoJuniorSchoolSeeder` and
`DemoSeniorSchoolSeeder`. Both schools are `is_demo = 1`, so
`DemoSchoolCommsGuard` blocks every outbound email, SMS, WhatsApp and push for
them and their users. Neither seeder is wired into any automatic run; they are
seeded on purpose only.

## What a seed produces

Per school:

- **Accounts** — admin, head teacher, bursar, librarian, receptionist,
  teachers 1–10, students, and 12 parents linked to two children each. Every
  account is active and verified.
- **Classes and subjects** — Junior: nursery + P.1–P.7 (streams A/B on
  P.5–P.7); Senior: S.1–S.6 (streams East/West on S.1/S.2). A-level subject
  combinations are **not** modelled yet — S.5/S.6 carry a general subject set.
- **Timetable** — a full weekday grid per class, with recurring calendar
  events synced for every slot.
- **Notices and events** — parents meeting, sports day, fees reminder,
  visitation day, inter-house sports gala.
- **Admissions** — 2 pending applications with the Ugandan fields filled
  (`entry_term` stored as `1`/`2`/`3`, `boarding_type`, `home_district`, …).
- **Fees** — categories per standard and a realistic payment mix (paid in
  full, instalments, partial, owing).
- **Attendance** — 20 school days, two sessions per day. **The walkthrough
  classes have no attendance for today**, so it can be taken live on a call.
- **Exams and marks** — previous term complete and locked/approved; current
  term has one completed exam, one **open mid-term for the walkthrough
  teacher's own subject** (unmarked, no submission), and one upcoming exam.
- **Manifest** — `demo:refresh` bookkeeping stored in the `school_details`
  table (`meta_key = demo_manifest`), never in local storage (Laravel Cloud
  wipes that on redeploy) and never containing credentials.

## Password policy

Passwords are **unique random per account**, set at account creation only and
never echoed or logged. Seeders **never read a shared password from the
environment** (`STAGING_DEMO_PASSWORD` / `DEMO_SEED_PASSWORD` support was
removed); re-running a seeder never overwrites an existing account's password.
Walkthrough logins are handed out separately by the team (see
`docs/ops/demo-walkthrough.md`).

## `demo:refresh`

Restores a school to its seeded baseline:

```bash
php artisan demo:refresh            # all demo schools
php artisan demo:refresh 38         # one school
php artisan demo:refresh 38 --dry-run
```

Removes (per school, in FK-safe order inside a transaction):

- users created **after** the last seeding (admins are never touched), with
  their attendance/marks/fee/profile rows;
- fee payments and marks created after the last seeding;
- attendance rows created after the last seeding;
- attendance for **today** on the walkthrough classes;
- marks and submissions on the open walkthrough exam.

Then re-runs the school's seeder so missing data refills deterministically.
The command **refuses any school that is not `is_demo`** and never touches
accounts or passwords that existed at seed time.

## Known limitations

- Senior A-level subject combinations are not modelled (S.5/S.6 carry a
  general subject set).
- `demo:refresh` needs a manifest: run the seeder once before the first
  refresh.
- Payments for students without a fee category (e.g. test rows) are deleted by
  the refresh cutoff sweep; seeded payment mixes are rebuilt by the seeder.

## Tests

`tests/Feature/Demo/DemoRichDataTest.php` covers rich data for both schools,
idempotency, the DB manifest, per-account passwords, the refresh round-trip,
refusal of non-demo schools and the outbound-comms guard — with
`PRAGMA foreign_keys = ON` so delete-order bugs fail in the suite instead of
on staging (MySQL enforces FKs; sqlite does not by default).
