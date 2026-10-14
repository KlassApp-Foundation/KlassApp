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
| School admin (Junior) | `demojunior@klassapp.xyz` | own password; display name Demo Admin |
| School admin (Senior) | `admin@senior.demo.klassapp.test` | shared password |
| Head teacher | `headteacher@…` | |
| Class teachers | `teacher1@…` … `teacher10@…` | `teacher1` is the walkthrough class teacher |
| Bursar | `bursar@…` | fee payments |
| Librarian | `library@…` | |
| Receptionist | `reception@…` | |
| Parents | `parent1@…` … `parent12@…` | each linked to two children |

## What to try, per role

**School admin**: log in and you land on the dashboard (`/admin/dashboard`,
greeting "Good morning, …", with the term under it and the school name in the
top bar). The sidebar has four sections:
- **People**:
  - **Students**, with All students (`/admin/students`), Admissions (with a
    pending count) and Health records.
  - **Teachers and staff** (`/admin/teachers`).
  - **Parents** (`/admin/parents`).
- **Academics**:
  - **Classes and subjects**, with Classes and streams (`/admin/classes`),
    Subjects, Timetable and Calendar (these three from sidebar fixture, not
    seen expanded on staging).
  - **Attendance**.
  - **Exams and reports**, with Exams and marks, Grading and Report cards.
  - **Library**.
- **Money**: **Fees** (`/admin/fees/payments`) and **Unmatched payments**.
- **School**: **WhatsApp**, **Approvals**, **Transport** and **Settings**
  (School details, Academic years and terms, Data exports; from sidebar
  fixture, not seen expanded on staging).

The account card is in the sidebar footer. Its menu has Edit profile, Change
password, Settings and Help, and also Log out (don't use Log out mid-demo).
The sidebar folds to an icon rail with the button at the top left. On a phone,
the menu button opens the same sections as a drawer.

Then check:
- Students, Teachers and staff, Parents, Classes and streams, Exams and marks,
  Fees and the class roster should all show real data.
- The Students, Teachers and staff and Parents lists have a **Select** button
  that turns on Select mode, with a checkbox on each row.
- Clicking a student row opens the student profile by id (`/admin/students/{id}`).
  It has the tabs Overview, Academics, Attendance, Fees and Parents and guardians.
- Clicking a teacher row opens the teacher profile
  (`/admin/teacher/show/{name}`).
- Clicking a parent row opens the parent profile (`/admin/parent/show/{name}`,
  older page).
- The dashboard now shows the new layout by default, so `?v2=1` is no longer
  needed.
- Toshi: the orange handle on the right edge (1280px and wider) opens the Toshi
  panel. Toshi is in preview, so it shows "Coming soon" and messaging is
  disabled. The sidebar footer reads "Toshi · coming soon".

**Class teacher** (`teacher1@…`): lands on `/teacher/dashboard`. The sidebar
has Attendance, Classes, Timetable, Exams, Homework, Marks, Report Cards,
Class Streams, Notices and Events.
- Attendance for their class — **today's attendance is untaken on purpose**,
  so it can be taken live on a call.
- Marks entry, the class roster, and the marks template download.

**Subject teacher** — marks entry only for their own subject; a different
class is refused.

**Bursar**: lands on the Accountant dashboard (`/accountant/dashboard`). It covers the fees area
(**Fees & Payments**, `/accountant/fees/payments`), including recording a fee payment.
The sidebar also has Data Exports, Holidays and Payroll.

**Parent** (`parent1@…`): lands on the Parent Portal (`/parent/dashboard`), which has a
child switcher and Children and Grades in the menu. They see only their own child's information.

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

On 16 October, run `php artisan demo:refresh` for the demo schools so the
current week has attendance registers. The dashboard attendance tile shows
this week's rate, and last week's rate labelled "Last week" only when this
week has no register yet.

This removes post-seed test data and re-runs the school's seeder; accounts and
passwords that existed at seed time are never touched.
