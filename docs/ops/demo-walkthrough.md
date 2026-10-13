# Demo walkthrough (staging)

How to log in to the demo schools and try real actions. Demo data only — see
`docs/ops/demo-data.md` for what the seeders produce and how `demo:refresh`
restores it.

## The two demo schools

| School | Id | URL |
|---|---|---|
| Demo Junior School | 38 | https://test.klassapp.xyz |
| Demo Senior School | 39 | https://test.klassapp.xyz |

**Passwords:** ask Rasta. They are never written into the repo or these docs.
The Demo Junior school admin has its own password. Every other walkthrough
account on both demo schools uses the shared demo password. Every account
below is active and verified — no code screen, no forced password change.

## Logins

The Demo Junior school admin is `demojunior@klassapp.xyz`. That address is
not on the demo domain. The seeders recognise this account by its school-admin
role on Demo Junior School, so a refresh does not recreate
`admin@junior.demo.klassapp.test` and does not create a second admin.

Every other address uses the school's demo domain: `junior.demo.klassapp.test`
or `senior.demo.klassapp.test`.

| Role | Email | Notes |
|---|---|---|
| School admin (Junior) | `demojunior@klassapp.xyz` | own password; full admin dashboard |
| School admin (Senior) | `admin@senior.demo.klassapp.test` | shared password |
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

- Today's attendance is **untaken** for the walkthrough class teacher's class and one other class, including when today is a Saturday. The walkthrough teacher can still record that Saturday. Other classes have attendance for today.
  (and one other class) in each school.
- One exam is **open and unmarked** for the walkthrough teacher's subject —
  Junior: Mathematics in P.7; Senior: Biology in S.6.
- Two **pending admissions** per school.

Normal walkthrough actions (taking attendance, entering marks, recording a
payment) are fine — `demo:refresh` resets the data afterwards.

## Changing the demo password later

From the staging environment's **Commands** tab, re-run:

```bash
php artisan demo:set-password 38 39 --password=<shared password>
php artisan demo:set-admin-login 38
```

`demo:set-password` only touches `is_demo` schools and prints per-role counts.
`demo:set-admin-login` accepts only Demo Junior School. It refuses a non-demo
school and any other school, and it prints a count — never the password. The
password is an argument or a hidden prompt. Run `demo:set-admin-login` again
after a shared-password reset so the junior admin keeps its own password.
Neither command may be run on production.

## Restoring a demo school

```bash
php artisan demo:refresh 38     # one school
php artisan demo:refresh        # all demo schools
php artisan demo:refresh 38 --dry-run
```

This removes post-seed test data and re-runs the school's seeder; accounts and
passwords that existed at seed time are never touched.
