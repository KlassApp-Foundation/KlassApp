# KlassApp roles and tasks (draft)

**Date:** 2026-10-02  
**Source tip:** `origin/main` @ `2482fc1d`  
**Miro board:** [KlassApp roles and tasks (draft)](https://miro.com/app/board/uXjVEf6ImYk=/)  
**Method:** Read-only from routes, middleware, `config/navigation.php`, WhatsApp controller, Slack/Toshi connectors, mailables, seeders. No production access. No app code changed.

**Tagline:** An open education protocol for humans and agents.  
**Product:** KlassApp is a global product for schools anywhere, from nursery and primary to secondary (O and A level).

Items marked **assumed, to confirm** are inferred (frequency, who usually does the task) rather than read from code.

---

## 1. Legend (channels)

| Channel | How marked | Primary evidence |
|---|---|---|
| **Web** | Blue | Route files + `config/navigation.php` |
| **WhatsApp** | Green | `app/Http/Controllers/Api/WhatsAppController.php`, outbound services |
| **Slack** | Purple | Toshi Slack MCP skill — school admin / deputy only |
| **Spreadsheet** | Yellow | Admin/teacher import-export controllers, wizard/Toshi uploads |
| **Toshi** | Orange | Scripted onboarding + “Coming soon” assistant (`AgentToshi`, `ToshiUiSwitch`) |
| **Email** | Red/light | `app/Mail/*` |
| **Assumed, to confirm** | Amber sticky / italic note | Frequency or “who usually” not in code |

---

## 2. Roles

Primary gate is **`users.usergroup_id`**, not Laratrust. Laratrust tables/seeders remain as secondary capability labels; `attachRole` on staff update is commented out.

### Usergroups (`database/seeders/UsergroupTableSeeder.php`)

| ID | Name | Who in a school | Main responsibilities | How assigned |
|---|---|---|---|---|
| **1** | SiteAdmin | KlassApp platform operator | Schools, plans, subscriptions, platform co-admins, mail list | Platform only |
| **2** | *(not seeded)* Platform co-admin | KlassApp staff helper | Settings-level access via `fullschooladmin`; no school shell | `CoAdminService` (superadmin) |
| **3** | SchoolAdmin | Head / proprietor / co-admin | Full school ops, settings, onboarding, fees overview | Signup owner; product co-admin invite; promote teacher → ug3 |
| **4** | SchoolSubadmin | Head teacher / deputy (product language) | Most `/admin/*` modules; **not** Settings (`fullschooladmin` excludes ug4) | Staff create designation → ug4 |
| **5** | Teacher | Teaching staff | Classes, attendance, marks, homework; CT extras if class teacher | Staff/teacher invite; import; Toshi add teacher |
| **5 + CT** | Class teacher | Homeroom for a stream | Same ug5 + report cards, exams create, class streams (nav `condition: class_teacher`) | `standards_link.class_teacher_id` / `sections.class_teacher_id` |
| **5 subject** | Subject teacher | Subject specialist | Marks for own exams; no CT-only nav | `class_teacher_links` / Teacherlink |
| **6** | Student | Enrolled learner | Own marks, attendance, homework, notices | Student create / import / onboarding |
| **7** | Parent | Guardian | Children, fees, grades, attendance (web + WhatsApp) | Parent link / roster |
| **8** | Librarian | Library staff | Books, cards, borrowing | Staff designation → ug8 |
| **9** | OldStudent (Alumni) | Former student | Marks, directory | Legacy / promotion path |
| **10** | Receptionist | Front desk | Visitors, call log, postal, notices | Staff designation → ug10 |
| **11** | Accountant (Bursar) | Bursar / finance | Fees & payments, payroll, exports | Staff designation → ug11 |
| **12** | Stock Keeper | Inventory (intended) | Stock UI intended; **routes broken** | Staff designation → ug12 |
| **13** | *(not seeded)* Non-teaching staff | Generic staff | Account only; no role middleware / shell | StaffController “else” |

### Secondary `roles` table (`RolesTableSeeder`) — leave/transport capabilities

IDs 1–13 include leave_applier, leave_checker, principal, class_coordinator, bursar, librarian, parent, student, etc. These are **not** the web route buckets. Teacher designations also live in JSON `users.teacher_designations`.

### Co-admin naming trap

- **Product co-admin invite** → usergroup **3** (same as school admin).  
- **Platform co-admin** (superadmin Livewire) → usergroup **2**.

---

## 3. Role × task grid

Columns: SiteAdmin (1), SchoolAdmin (3), Subadmin/HT (4), Class teacher (5-CT), Subject teacher (5), Accountant (11), Librarian (8), Receptionist (10), Student (6), Parent (7).  
Cell format: does it? · channel(s) · route/keyword · frequency (**assumed, to confirm** unless noted).

### Admissions

| Task | Site | Admin | HT | CT | Subj | Bursar | Lib | Rec | Stud | Parent |
|---|---|---|---|---|---|---|---|---|---|---|
| Review / approve applications | — | Yes · Web · `/admin/admission*` (**not in sidebar**) · **assumed termly** | Via admin if ug4 | — | — | — | — | — | — | Receives approval email |
| Applicant / parent confirmation | — | — | — | — | — | — | — | — | — | Email · `AdmissionApprovalMail` |

### People — students

| Task | Site | Admin | HT | CT | Subj | Bursar | Lib | Rec | Stud | Parent |
|---|---|---|---|---|---|---|---|---|---|---|
| Add students one-by-one | — | Yes · Web · students / wizard · **assumed weekly early term** | Via admin | Edit own class student · Web · `teacher.student.edit` | — | — | — | — | — | — |
| Paste / bulk name list | — | Yes · Web wizard + Toshi upload · Onboarding | Via admin | — | — | — | — | — | — | — |
| Excel student import | — | Yes · Spreadsheet · `/admin/import` · **assumed once/term** | Via admin | — | — | — | — | — | — | — |
| Export students | — | Yes · Spreadsheet · export routes | Via admin | — | — | — | — | — | — | — |
| View roster on WhatsApp | — | Yes · WA · `students` | — | — | — | — | — | — | — | — |

### People — staff / teachers

| Task | Site | Admin | HT | CT | Subj | Bursar | Lib | Rec | Stud | Parent |
|---|---|---|---|---|---|---|---|---|---|---|
| Add single teacher | — | Yes · Web · `/admin/teacher/add` (**weak nav**) · Email invite · Toshi · **assumed weekly** | Via admin | — | — | — | — | — | — | — |
| Import teachers Excel | — | Yes · Spreadsheet · `/admin/import/teacher` | Via admin | — | — | — | — | — | — | — |
| Invite as class teacher | — | Yes · Web · `admin.class-teacher-invite.*` · Email | Via admin | — | — | — | — | — | — | — |
| Promote to co-admin | — | Yes · Toshi / invite · Email · ug5→ug3 | — | — | — | — | — | — | — | — |
| Platform co-admins | Yes · Web · superadmin settings | — | — | — | — | — | — | — | — | — |

### People — parents

| Task | Site | Admin | HT | CT | Subj | Bursar | Lib | Rec | Stud | Parent |
|---|---|---|---|---|---|---|---|---|---|---|
| Link parent ↔ child | — | Yes · Web parents + WA link flows | Via admin | — | — | — | — | — | — | WA · JOIN / code / link request |
| View children | — | List · Web | — | — | — | — | — | — | — | Web + WA · **assumed daily** |

### Classes and subjects

| Task | Site | Admin | HT | CT | Subj | Bursar | Lib | Rec | Stud | Parent |
|---|---|---|---|---|---|---|---|---|---|---|
| Configure classes / streams | — | Yes · Web · `admin/classes` · Toshi setup · **assumed once/year** | Via admin | CT streams · `teacher.class-stream` | — | — | — | — | — | — |
| Subjects | — | Yes · Web · `admin/subjects` · Toshi | Via admin | — | — | — | — | — | — | — |
| Teacher–class links import | — | Yes · Spreadsheet · teacher-links import | Via admin | — | — | — | — | — | — | — |

### Timetable

| Task | Site | Admin | HT | CT | Subj | Bursar | Lib | Rec | Stud | Parent |
|---|---|---|---|---|---|---|---|---|---|---|
| Build / edit timetable | — | Yes · Web · `admin/timetable` | Via admin | Yes · Web · `teacher.timetable.*` · **assumed weekly** | Same ug5 | — | — | — | View · Web/WA | — |

### Attendance

| Task | Site | Admin | HT | CT | Subj | Bursar | Lib | Rec | Stud | Parent |
|---|---|---|---|---|---|---|---|---|---|---|
| Mark attendance | — | Yes · Web · `admin/attendance` | Via admin | Yes · Web · `teacher.attendance` · WA · **assumed daily** | Scoped · Web/WA | — | — | — | View own | View child · Web/WA |
| Absent reminder | — | Triggers | — | Triggers | — | — | — | — | — | Email · `AbsentReminderMail` (**assumed**) |

### Marks and exams

| Task | Site | Admin | HT | CT | Subj | Bursar | Lib | Rec | Stud | Parent |
|---|---|---|---|---|---|---|---|---|---|---|
| Create exam | — | Yes · Web exams | Via admin | Yes · `teacher.exams.create` (CT nav) | May create if allowed by auth — **assumed CT-led** | — | — | — | — | — |
| Enter marks | — | Oversight | — | Yes · `teacher/exam/marks` · **assumed termly** | Yes · same | — | — | — | View | View · Web/WA `grades` |
| Export marksheet | — | Reports | — | Spreadsheet · marksheet download | Same | — | — | — | — | — |
| Bulk marks Excel upload UI | — | **No role UI** (ops/console only) | — | — | — | — | — | — | — | — |

### Report cards

| Task | Site | Admin | HT | CT | Subj | Bursar | Lib | Rec | Stud | Parent |
|---|---|---|---|---|---|---|---|---|---|---|
| Generate report cards | — | Yes · Web · `admin/reports/cards` · **assumed termly** | Via admin | Yes · `teacher.reports.cards` | — | — | — | — | Alumni download | — |
| Send to parents | — | Triggers WA PDF delivery | — | May trigger · **assumed termly** | — | — | — | — | — | Receive · WA · `report` |

### Fees

| Task | Site | Admin | HT | CT | Subj | Bursar | Lib | Rec | Stud | Parent |
|---|---|---|---|---|---|---|---|---|---|---|
| Configure fee structures | — | Yes · Web/Toshi fees step · **assumed once/year** | Via admin | — | — | Yes · accountant fees | — | — | — | — |
| Record payment | — | Yes · `admin/fees/payments` | Via admin | — | — | Yes · `accountant.fee-payments` · **assumed daily** | — | — | — | — |
| Fee reminder | — | Cron · WA `whatsapp:send-fee-reminders` | — | — | — | Triggers / monitors · **assumed weekly** | — | — | View WA | Receive · WA `fees` (**not email**) |
| Unmatched payments | — | Yes · Web · unmatched | Via admin | — | — | Likely · **assumed daily** | — | — | — | — |
| Payroll | — | Via `adminaccountant` | — | — | — | Yes · payroll submenu | — | — | — | — |

### Communication and notices

| Task | Site | Admin | HT | CT | Subj | Bursar | Lib | Rec | Stud | Parent |
|---|---|---|---|---|---|---|---|---|---|---|
| Messaging / notices | — | Web · Messaging | Via admin | Notices · Web | Notices | Holidays/notices | — | Notices · Web + WA | Notices · Web | Notifications · Web |
| Slack ops (channels, post) | — | Yes · Slack via Toshi · Integrations | Yes · Toshi gate | **Denied** | Denied | — | — | — | — | — |
| Events / calendar | — | Web Calendar | — | Events | Events | Events WA | — | Events | Events | — |

### Settings and school setup

| Task | Site | Admin | HT | CT | Subj | Bursar | Lib | Rec | Stud | Parent |
|---|---|---|---|---|---|---|---|---|---|---|
| Manual onboarding wizard | — | Yes · Web · Toshi checklist sibling · **once** | — | — | — | — | — | — | — | — |
| Toshi setup guide (scripted) | — | Yes · Toshi onboarding mode · **once** | — | — | — | — | — | — | — | — |
| Toshi assistant (AI) | — | Coming soon / preview unless assistant mode | Deputy Toshi gate | Ops if enabled | Ops if enabled | WA Toshi if enabled | WA if enabled | WA if enabled | WA if enabled | WA if enabled |
| School settings | — | Yes · `admin/settings` | **Blocked** (ug4) | — | — | — | — | — | — | — |
| Integrations (Slack, etc.) | — | Settings · Integrations | Blocked | — | — | — | — | — | — | — |
| Platform schools / plans | Yes · superadmin | — | — | — | — | — | — | — | — | — |

---

## 4. Key journeys

Dead ends / confusing steps marked **[RED]**.

### 4.1 New school setup — manual wizard

1. Register / create school → owner becomes **ug3**.  
2. Open Manual Onboarding Wizard (`ManualOnboardingWizard`) — steps from `OnboardingStepsService::ALL_STEPS` (school_name → … → plan_selection).  
3. Fill classes, subjects, teachers, students, terms, fees (uploads allowed).  
4. WhatsApp verify; plan selection.  
5. Privilegeconditions may bounce incomplete schools to setup.  
6. **[RED]** Dual path with Toshi checklist — easy to start both.  
7. After setup with assistant off → Toshi shows **Coming soon**.

### 4.2 New school setup — Toshi (scripted)

1. Same persistence via `OnboardingEngine`.  
2. Chat/chips for steps; extras vs wizard: `admin_account`, `co_admin_invite`, `exams`, `school_pay`, `review`.  
3. File attach for teacher/student/fee/exam templates.  
4. **[RED]** Soft-launch often leaves assistant as Coming soon — setup works, free-form AI may not.  
5. Finish → Coming soon card (~`AgentToshi` “assistant is coming soon”).

### 4.3 Add students (one by one / paste / Excel)

1. **One by one:** Admin Students UI / create flows under `/admin/students*` (and wizard).  
2. **Paste / bulk names:** Wizard + Toshi upload via `OnboardingNameListExtractor`.  
3. **Excel:** `/admin/import` → `ImportMemberController` / `UsersImport`.  
4. **[RED]** Three entry points; sidebar only shows “Students” — import is easy to miss.  
5. Parent linking is a separate WA/web flow after roster exists.

### 4.4 Invite a teacher and first login

1. Paths: `/admin/teacher/add` (multi-step Vue form), `/admin/import/teacher`, class-teacher invite from section, Toshi “Add Teacher”.  
2. Email: `TeacherInviteLinkMail` / `TeacherInviteMail` (no password in mail).  
3. Claim: `/invite/teacher/{token}` → set password.  
4. Login → `/teacher/dashboard`.  
5. **[RED]** Sidebar “Teachers” → list; **Add** is not a top-level nav item (settings hub / classes nudge / deep URL).  
6. **[RED]** Class-teacher invite vs generic add teacher — two invite mental models.

### 4.5 Teacher enters marks

1. Teacher → Marks (`teacher/exam/marks`) or CT Exams create.  
2. Enter marks → `teacher.exam.marks.enter` / `save` (school_id + teacher_id checks).  
3. Optional marksheet Excel download.  
4. Publish may trigger WhatsApp grade notify to parents.  
5. **[RED]** No bulk marks Excel **upload** in role UI.  
6. **[RED]** “Exams” nav only for class_teacher; subject teachers go via Marks — easy to confuse.

### 4.6 Generate and send report cards to parents

1. Admin `admin/reports/cards` and/or CT `teacher.reports.cards`.  
2. Generate PDF (`StudentReportCardService`).  
3. Delivery: WhatsApp report PDF (`WhatsAppReportCardDeliveryService`) — **not** email.  
4. Parent: WA keyword `report` / menu Report Card.  
5. **[RED]** Dual admin + CT surfaces; unclear who “owns” send (**assumed, to confirm**).

### 4.7 Record fee payment and send reminder

1. Admin Fees & Payments or Accountant `accountant.fee-payments`.  
2. Unmatched payments queue for reconciliation.  
3. Reminders: Artisan `whatsapp:send-fee-reminders` → parent WA — **not** email.  
4. Parent checks balance: WA `fees` / web Fees.  
5. **[RED]** Fee work split admin vs bursar with overlapping pages.  
6. **[RED]** Holidays on bursar sidebar next to fees — low priority crowding.

### 4.8 Teacher marks attendance

1. Teacher → Attendance → create/save.  
2. Export available.  
3. Parents of absentees may get WA / `AbsentReminderMail` (**assumed trigger path**).  
4. Parent: WA `attendance` or web Attendance.  
5. Scope depends on class teacher vs subject / attendance_scope — **assumed, to confirm** which schools use which.

### 4.9 Parent checks report card and fees on WhatsApp

1. Parent linked phone → `menu` / greeting.  
2. Report Card / `report` → PDF.  
3. Fee Balance / `fees` → balance.  
4. Optional `dashboard` / `web_login` magic link to web.  
5. **[RED]** Stranger phones need JOIN / parent-link flow before menus work.  
6. **[RED]** Dual-role staff with children use `my children` / `report` parent flows — easy to miss.

---

## 5. Navigation today (from `config/navigation.php`)

### SchoolAdmin (`admin`)

- Dashboard  
- **Academics:** Students, Teachers, Parents, Classes & Streams, Subjects, Timetable, Attendance, Exams & Marks, Grading, Report Cards  
- **Operations:** Library, Health records, Transport  
- **Finance:** Fees & Payments, Unmatched Payments  
- **Communication:** Messaging, Calendar  
- **System:** Approvals, Data Exports, Settings  
- Footer: Help & Docs  

**Missing from nav (but routed):** Admissions, teacher add/import, student import, many legacy modules (gallery, magazine, id-card, feed, class wall).

### SiteAdmin (`superadmin`)

Dashboard, Schools, Subscriptions, Plans, Reports, Mail List, Settings (co-admins, cities, features, emis, toshi).

### Teacher

Dashboard, Classes, Timetable, Attendance, Exams *(class_teacher)*, Homework, Marks, Report Cards *(class_teacher)*, Class Streams *(class_streams)*, Notices, Events, Library.

### Student

Dashboard, Marks, Attendance, Homework, Assignments, Events, Notices, Library, Holidays, Chats, Activity.

### Parent

Dashboard, Children, Fees, Grades, Attendance (child resolver).

### Librarian

Dashboard, Books, Cards, Borrowing, Data Exports. (Book categories routed but not nav.)

### Receptionist

Dashboard, Visitors, Call Log, Postal Record, Notices, Events, Tasks.

### Accountant (Bursar)

Dashboard, Fees & Payments, Data Exports, Holidays, Payroll (Templates, Salaries, Payslips, Batch Run).

### Stock (unreachable)

Dashboard, Products, Categories, Suppliers, Orders, Data Exports — `routes/stock.php` empty; middleware alias mismatch.

### Alumni

Dashboard, My Marks, Directory. Login redirect default may send to `/admin/dashboard` **[RED]**.

### SchoolSubadmin

No dedicated sidebar — uses admin nav on `/admin/*`; home `/subadmin/dashboard`.

---

## 6. Navigation proposal (Proposal, for discussion)

Prioritise frequent work; group clearly. Frequencies **assumed, to confirm**.

### School admin

1. **Today:** Attendance overview, Approvals, Messaging  
2. **People:** Students (+ Add / Import), Teachers (+ Add / Import / Invite), Parents  
3. **Classes:** Classes & Streams, Subjects, Timetable  
4. **Learning:** Exams & Marks, Grading, Report Cards  
5. **Money:** Fees & Payments, Unmatched  
6. **Setup:** Settings, Integrations, Onboarding / Toshi  
7. **Rare / hide:** Health redirect, Transport if unused, Data Exports under Tools  

### Class teacher

1. Attendance (daily)  
2. My class / roster  
3. Marks / Exams  
4. Report Cards (termly)  
5. Timetable, Homework, Notices  
6. Class Streams  

### Subject teacher

1. Marks (enter)  
2. My classes / timetable  
3. Attendance (if in scope)  
4. Homework  
5. Notices / Events  

### Bursar (Accountant)

1. Fees & Payments (daily)  
2. Unmatched Payments  
3. Fee reminders status / reports  
4. Payroll (templates → salaries → payslips → batch)  
5. Data Exports  
6. Demote Holidays out of primary list  

---

## 7. Problems and gaps

| # | Problem | Where it shows |
|---|---|---|
| 1 | **Add single teacher has weak entry** — route `/admin/teacher/add` exists; not in Academics sidebar | Journey 4.4; People–staff grid |
| 2 | **Admissions not in admin sidebar** despite full `/admin/admission*` routes | Admissions grid; Nav today |
| 3 | **Stock module dead** — empty `routes/stock.php`, middleware `stock` ≠ `stockkeeper`, nav still present | Roles ug12; Nav stock |
| 4 | **Two co-admin meanings** — product ug3 vs platform ug2 (unseeded) | Roles section |
| 5 | **SchoolSubadmin (ug4) Settings 404** — `fullschooladmin` excludes 4; no own sidebar | Settings grid; Nav |
| 6 | **Alumni login redirect wrong** — falls through to `/admin/dashboard` | Journey / Roles ug9 |
| 7 | **No bulk marks Excel upload in UI** — only download + ops/console imports | Journey 4.5; Marks grid |
| 8 | **Fee reminders are WhatsApp-only** — parents without WA miss them | Journey 4.7; Fees grid |
| 9 | **Duplicate fee surfaces** — admin and accountant both own payments | Journey 4.7 |
| 10 | **Health records nav → students redirect** | Nav operations |
| 11 | **Usergroup 13 unseeded** — staff accounts with no shell | Roles |
| 12 | **Inventory routes stub empty** | Admin routes |
| 13 | **GeGoK12 leftovers** — id-card, caste/aadhar fields, OldStudent naming, gallery/magazine/feed | Across admin |
| 14 | **Class teacher ≠ usergroup** — CT features hidden via nav conditions; subject teachers can miss paths | Teacher nav; Marks journey |
| 15 | **Slack only admin/deputy via Toshi** — marketing “admins manage in Slack” is connector+AI, not a Slack bot menu | Slack channel map |
| 16 | **Teacher invite vs class-teacher invite** — two flows | Journey 4.4 |
| 17 | **Three student-add paths** without clear primary CTA | Journey 4.3 |
| 18 | **Laratrust roles seeded but package disabled** — dual mental model | Roles |
| 19 | **Parent Toshi comments may still say no web dashboard** while parent portal exists | Parent role docs drift |
| 20 | **Book categories** routed for librarian but not in sidebar | Librarian nav |

### Top 10 for redesign discussion

1. Add-teacher entry invisible in primary nav  
2. Admissions orphaned from sidebar  
3. Dead stock + empty inventory  
4. Co-admin ug2 vs ug3 confusion  
5. ug4 cannot open Settings  
6. No marks bulk-upload UI  
7. Dual admin/bursar fee ownership  
8. Fee/report delivery WhatsApp-only  
9. CT vs subject teacher nav split without role clarity  
10. Alumni redirect / leftover modules clutter mental model  

---

## 8. Assumed, to confirm (Rasta first)

1. **How often** each task runs (daily / weekly / termly / once) — all frequency labels in the grid.  
2. **Who usually generates and sends report cards** — admin vs class teacher.  
3. **Whether bursars or school admins** are the primary fee operators in live schools.  
4. **Whether SchoolSubadmin (head teacher)** is a role schools actually use, or only SchoolAdmin + teachers.  
5. **Attendance scope** — class teacher only vs any subject teacher marking.  
6. **Whether parents primarily use WhatsApp or the web portal** after soft launch.  
7. **Whether Slack MCP** is intended as the long-term “admins manage in Slack” story, or a prototype.  
8. **Whether Stock Keeper / Alumni / Receptionist** stay in the product for global schools.  
9. **Single-teacher add UX goal** — keep multi-step `/admin/teacher/add`, invite-only, or Toshi-first.  
10. **Whether fee reminders should ever be email** for parents without WhatsApp.

---

## 9. Channel quick reference

| Role | WhatsApp | Slack | Spreadsheet | Toshi | Email |
|---|---|---|---|---|---|
| Parent | Fees, grades, attendance, report PDF, dashboard link | — | — | WA ops if enabled | Admission / absent / birthday / reset |
| Student | Results, attendance, fees, timetable, homework | — | — | WA ops if enabled | Reset / verify if email |
| Teacher | Marks, attendance, timetable, assignments | Denied | Marksheet / attendance export | Ops / Coming soon | Invite mails |
| School admin | Students, staff, exams, fees, reports, notices | Connect + Toshi read/post | Import/export + wizard | Setup guide → Coming soon | Co-admin invites, subscription |
| Deputy ug4 | No dedicated WA menu branch | Via Toshi gate | Via admin routes | No WA Toshi | If email set |
| Receptionist | Call log, notices, events | — | — | WA if enabled | Reminders if queued |
| Accountant | Fees, reports, events | — | Reports landing | WA if enabled | — |
| Librarian | Default menu only | — | Activity exports | WA if enabled | — |

---

*End of draft. Companion Miro board has the same structure across seven frames.*
