# Demo walkthrough (staging)

How to log in to the demo schools and try real actions. Demo data only — see
`docs/ops/demo-data.md` for what the seeders produce and how `demo:refresh`
restores it.

## The two demo schools

| School | Id | URL |
|---|---|---|
| Demo Junior School | 38 | https://test.klassapp.xyz |
| Demo Senior School | 39 | https://test.klassapp.xyz |

**Password:** ask Rasta for the shared demo password (it is deliberately known
for the demo accounts and is never written into the repo or these docs). The
same password is set on every walkthrough account of both schools by
`php artisan demo:set-password 38 39`. Every account below is active and
verified — no code screen, no forced password change.

## Logins (demo-domain emails)

All addresses use the school's demo domain: `junior.demo.klassapp.test` or
`senior.demo.klassapp.test`.

| Role | Email | Notes |
|---|---|---|
| School admin | `admin@…` | full admin dashboard |
| Head teacher | `headteacher@…` | |
| Class teachers | `teacher1@…` … `teacher10@…` | `teacher1` is the walkthrough class teacher |
| Bursar | `bursar@…` | fee payments |
| Librarian | `library@…` | |
| Receptionist | `reception@…` | |
| Parents | `parent1@…` … `parent12@…` | each linked to two children |

## What to try, per role

**School admin** — log in and open the dashboard, then:
- Students, Teachers, Classes and streams, Exams, Fees and the class roster
  should all show real data.
- Dashboard v2: add `?v2=1` to the dashboard address to see the new layout.

**Class teacher** (`teacher1@…`):
- Attendance for their class — **today's attendance is untaken on purpose**,
  so it can be taken live on a call.
- Marks entry, the class roster, and the marks template download.

**Subject teacher** — marks entry only for their own subject; a different
class is refused.

**Bursar** — the fees area, including recording a fee payment.

**Parent** (`parent1@…`) — sees only their own child's information.

Log out after each role.

## Pre-arranged walkthrough state

- Today's attendance is **untaken** for the walkthrough class teacher's class
  (and one other class) in each school.
- One exam is **open and unmarked** for the walkthrough teacher's subject —
  Junior: Mathematics in P.7; Senior: Biology in S.6.
- Two **pending admissions** per school.

Normal walkthrough actions (taking attendance, entering marks, recording a
payment) are fine — `demo:refresh` resets the data afterwards.

## Changing the demo password later

From the staging environment's **Commands** tab, re-run:

```bash
php artisan demo:set-password 38 39 --password=<new password>
```

It only touches `is_demo` schools and prints per-role counts — never the
password. Alternatively, deactivate individual accounts.

> `demo:set-password` must **never** be run on production. It refuses
> non-demo schools by design.

## Restoring a demo school

```bash
php artisan demo:refresh 38     # one school
php artisan demo:refresh        # all demo schools
php artisan demo:refresh 38 --dry-run
```

This removes post-seed test data and re-runs the school's seeder; accounts and
passwords that existed at seed time are never touched.
