# KlassApp front-end design brief

For a human UI designer. This describes the product as it is in the code on `main` at `5f3274f9` (20 October 2026), plus a proposed shape for the redesign. It is not a build spec.

Screenshots in `docs/design/ux-brief/` are from a local copy of the app with the fictional Demo Junior School seeder (`database/seeders/DemoJuniorSchoolSeeder.php`) and a few extra local test accounts. No production data.

Where this file and an older audit disagree, the code wins, and the disagreement is called out. Where the repo has no evidence, the text says **Unknown, confirm with team**.

## 1. Product

KlassApp is "An open education protocol for humans and agents." It runs inside the tools educationists already use. School staff work in a web app. Parents can use that web app, and they can also message the school on WhatsApp. Teachers can type marks or bring them in from a spreadsheet. Toshi is the school's assistant: a setup guide, and later a conversation, inside the web app and (when a school turns it on) on WhatsApp.

The product is for nursery, primary, O level and A level, in any country. Class names, term names, grading, currency and extra registration fields are school settings. Some of those fields still appear only for particular answers during setup (see onboarding below). Do not design a single national school into the screens. The live parent channel today is WhatsApp through the Meta Cloud API (`routes/api.php`, `POST /api/whatsapp/inbound`). Spreadsheet import of marks is a real screen for teachers and school admins. Slack and Google tools are connector entries in `config/toshi.php` and are off unless an environment flag is set. They are not a second app the designer should draw as if it already ships. Draft pull requests for Google Drive, Classroom, Sheets and Calendar (#1137–#1140, #1149) are not on `main`.

## 2. Roles

There is no usage analytics in this repo. "How often" and "which device" below are **assumptions to validate with schools**, except where a channel is named because the code actually sends people there (parents on WhatsApp). Design targets of 375px and 1280px come from the handoffs, not from measured traffic.

The code does not have eight matching usergroups. Map the product names like this (`database/seeders/UsergroupTableSeeder.php`, `config/navigation.php`, `app/Providers/RouteServiceProvider.php`).

| Product role | In the code | Main jobs today | How often (assumption) | Device (assumption) |
|---|---|---|---|---|
| Platform admin | Usergroup 1, `SiteAdmin`. Middleware `superadmin`. Shell `/superadmin/*`. Also `/platform/early-access`. | See schools, plans, subscriptions, mail list, early-access invites. | Occasional. Unknown, confirm with team. | Desktop web. Unknown, confirm with team. |
| School admin | Usergroup 3, `SchoolAdmin`. Middleware `schooladmin` plus `privilegeconditions`. Shell `/admin/*`. Settings routes use `fullschooladmin`. | People, classes, attendance, marks, fees, WhatsApp, approvals, settings. | Most weekdays in term. Unknown, confirm with team. | Desktop first. A phone drawer exists. Share unknown. |
| Head teacher | Usergroup 4, `SchoolSubadmin`. The code comment in `app/Services/Toshi/ToshiUiSwitch.php` calls this a head teacher. The demo seeder creates `headteacher` as usergroup 4. Same `/admin/*` shell. | Same operational jobs as the school admin, without the Settings group and without "Classes and streams". | Similar to the school admin. Unknown, confirm with team. | Same as the school admin. Unknown, confirm with team. |
| Class teacher | Not a usergroup. Usergroup 5 `Teacher` who is homeroom teacher on a class (`standards_link.class_teacher_id` and/or `sections.class_teacher_id`). | Attendance, marks, homework, and (only if homeroom) create exams, report cards, class streams. | Daily in term, heavier at attendance and at exam time. Unknown, confirm with team. | Phone and laptop. Unknown, confirm with team. Design mobile first. |
| Subject teacher | Usergroup 5 with a subject assignment (`class_teacher_links`) and no homeroom. | Marks for their subjects, attendance if the school setting allows it, homework, timetable. | Heavier when marks are due. Unknown, confirm with team. | Phone and laptop. Unknown, confirm with team. Design mobile first. |
| Bursar | Usergroup 11, `Accountant`. The demo seeder names this account bursar. Shell `/accountant/*`. | Fee balances, record payments, exports, payroll screens. | Payment days. Unknown, confirm with team. | Desktop web. Unknown, confirm with team. |
| Parent | Usergroup 7, `Parent`. Shell `/parent/*`. | See linked children, fees, grades, attendance. Linking starts on WhatsApp. | When the school messages them. Unknown, confirm with team. | WhatsApp on a phone is the channel the code actually implements. The web portal is real. Split unknown. |
| Student | Usergroup 6, `Student`. Shell `/student/*`. | Own dashboard, released marks, attendance, homework, assignments, notices. | Unknown, confirm with team. | Unknown, confirm with team. Do not assume a laptop. |

Other usergroups exist and have shells: librarian (8), alumni / `OldStudent` (9), receptionist (10), stock keeper (12, navigation is empty because the stock module has no routes). They are out of the first design pass unless the team says otherwise.

`docs/internal/role-capability-matrix.md` (22 September 2026) is stale. It says the parent sidebar has two items, the student sidebar has no marks or attendance, and teacher Exams and Marks are one link. Current `config/navigation.php` has parent Fees, Grades and Attendance; student Marks and Attendance; and separate teacher Exams (class teacher only) and Marks. Trust the config file.

Head teacher versus school admin, checked locally against the rendered sidebar:

- School admin sees Settings (School details, Academic years and terms, Data exports) and Classes and streams.
- Head teacher does not. Those items use `condition => school_admin` in `config/navigation.php`.
- The school-details URL is still open to a head teacher. `GET /admin/schooldetails` returned HTTP 200 and the form. The nav hide is not a lock. Settings routes under `routes/setting.php` use `fullschooladmin` and are the ones actually limited to usergroup 3.
- The classes roster is the other way around. `RosterScopeService::assertActorCanAccessSchool()` allows usergroups 3 and 5 only, so a head teacher is refused there even though they can open much of `/admin/*`.

## 3. Key workflows

### Early access and onboarding

**Today**

1. Someone asks for access at `GET/POST /early-access` (`EarlyAccessController`, view `auth` preview). Screenshot: `ux-brief/early-access-1280.webp`.
2. A platform admin reviews `GET /platform/early-access` and sends an invite (`Platform\EarlyAccessRequestController`).
3. The invited person opens `GET /invite/school/{token}` and submits name, phone and a password they choose (`SchoolInviteClaimController`). The password is not emailed on this path. Teacher and co-admin invites are separate set-password links (`/invite/teacher/{token}`, `/invite/co-admin/{token}`).
4. After claim they land on `GET /onboarding/choose` (`OnboardingChoiceController`). Two cards: manual wizard, or Toshi. Screenshot: `ux-brief/onboarding-choice-1280.webp`.
5. Wizard sets `toshi_mode` to `preview` and opens `GET /admin/onboarding/wizard` (Livewire `ManualOnboardingWizard`). Toshi sets `toshi_mode` to `onboarding` and redirects to `/admin/dashboard`.
6. New schools start with Toshi off. `config/toshi.php` `default_mode` is `preview` unless `TOSHI_DEFAULT_MODE` says `onboarding`. `assistant` is not a valid default.

The wizard and Toshi share one step list in `OnboardingStepsService::ALL_STEPS`:

1. School name
2. Approximate school size (Up to 500 / Up to 1,000 / More than 1,000)
3. Country
4. Board / curriculum
5. School category (only for some curricula)
6. EMIS / ministry code (only when the country answer requires it)
7. Exam centre number (only when the curriculum answer requires it)
8. Academic year
9. Structure and class teachers (Toshi label: Classes)
10. Subjects
11. Teachers (optional, can be skipped)
12. Students (optional, can be skipped)
13. Academic terms
14. Fee structures
15. WhatsApp verification
16. Plan selection

`applicableSteps()` drops the ministry-code and exam-centre steps when they do not apply. Dashboards rename those two to "Registration code" and "Exam centre number" (`NEUTRAL_LABELS`). A local school that already had a name opened the wizard on step 2 of 14 ("Approximate school size"). Screenshot: `ux-brief/onboarding-wizard-1280.webp`.

`docs/proposals/2026-10-03-onboarding-steps.md` describes a shared step registry behind `TOSHI_ONBOARDING_V2`, default off in `config/toshi.php`. The wizard on `main` already reads `OnboardingStepsService`. The Toshi v2 chat driver (`ToshiOnboardingV2Driver`) stays behind that flag. Do not design two different step lists.

Two behaviours that do not match the two-path story:

- Choosing Toshi flashes `open_toshi_onboarding`, and `Admin\DashboardController` discards that flash. The panel opens only when the URL has `?toshi_onboarding=1`. The choice redirect does not add that query.
- `schools.onboarding_finished_at` is set by `ManualOnboardingWizard::confirmReview()` (and by the demo seeder). `AgentToshi` never calls `OnboardingStepsService::markOnboardingFinished()`. A school that finishes only in Toshi can still look unfinished, and the choice screen only skips itself when that timestamp is set.

The invite form's phone rule in `SchoolInviteClaimController` accepts one national mobile pattern. That is a code limit. The screen should work for any country.

**Proposed**

One setup story with two ways to answer it (form or Toshi), same steps, same words. Show only the steps that apply to this school. Prefill ordinary defaults (three terms, a typical class list for the chosen curriculum) and let the school edit them. Standing rule in `AGENTS.md` says this prefill is not built yet. Both paths should set the same finished timestamp, then land on the school dashboard with a short "what to do next" (invite a teacher, add a class, connect WhatsApp), not a second empty admin.

### Parents linking on WhatsApp, then the school approves

**Today**

Parents do not start in the web app. An unknown number hits `POST /api/whatsapp/inbound` (`Api\WhatsAppController`).

1. They can send a student id shaped `KLS` plus 7 digits. `ParentLinkRequestService::createFromKlassAppNumber` stores a pending request for that school.
2. Or they tap Request Link. The Flow submission calls `createFromFlowSubmission` (parent name, child name, class, school name). A single matching student is suggested. An approval row is created only when one school is resolved. If it is not, the request is stored and the admin inbox is skipped (`ParentLinkRequestService`, warning log). The code does not guess between schools.
3. A school's own student id or board registration number, when it matches one student, links immediately (`WhatsAppController` comment "instant, when unique enough" → `ParentLinkService::linkByStudentId`). That path does not wait for approval.
4. A 10-digit code is School Pay (`std_school_pay_number`), not the link form. If no parent is linked yet, it creates a `WhatsAppUser` for that school and sends a welcome. It does not create a `ParentLinkRequest`. If a parent link already exists, it attaches the number to that parent.
5. The approval queue, for the paths that use it, is on `GET /admin/whatsapp/dashboard` and `GET /admin/whatsapp/parents` (`LinkRequestBoard`) and on `GET /admin/approvals`. Approve or reject with `POST /admin/approvals/{approval}/approve` or `reject`.
6. After a link, the parent can sign in to `/parent/dashboard` and open fees, grades and attendance for each child. With one child, the sidebar Fees / Grades / Attendance links go straight to that child. With several, they go to `/parent/children` first (`sidebar-menu.blade.php`).

Local parent grades for an unreleased term correctly said nothing was published (`ux-brief/parent-grades-1280.webp`). That is the release gate (`ReleaseGate` in `ParentPortalService`), not an empty school.

**Proposed**

Decide which paths need a person to confirm. Today, a KlassApp id and Request Link wait for the school, while a matching school id or a payment code can attach without that queue. Keep one queue, not three lookalike screens. The parent home is this child, fees, latest released results, attendance. The web screens and the WhatsApp replies should use the same words.

### Marks

The proposal is `docs/product/marks-flow.md` (merged as PR #1118). It still says "proposal only". The behaviour below is what `main` does after #1122, #1124, #1125, #1127, #1130, #1134, #1135, #1136, #1142, #1144, #1145, #1150 and #1151. Notes: `docs/dev/marks-flow-open-prs.md`.

**Today**

1. **Enter or upload.** Subject teacher, class teacher, or admin. Teacher list: `GET /teacher/exam/marks` (`teacher.exam.marks`). `GET /teacher/exams` and `GET /teacher/exam` redirect there (301). Entry: `GET /teacher/exam/{exam}/marks/enter`. Spreadsheet: `GET /teacher/exam/{exam}/marks/import` (template, preview, confirm, result). Admin import: `GET /admin/exams/{exam}/marks/import`. A teacher who does not own the exam gets 403. Local entry for the teacher's own exam returned 200 (`ux-brief/class-teacher-enter-1280.webp`): subject and term in the header, blank grid, Download template, Import from spreadsheet, Submit for review (`resources/views/teacher/marks/enter.blade.php`).
2. **Submit.** `POST /teacher/exam/{exam}/submit`. Writes `exam_marks_submissions`. Does not message parents and does not flip `exams.status` (#1134).
3. **Review.** `GET /admin/marks/submissions` lists rows with `status=submitted`. Detail `GET /admin/marks/submissions/{exam}`. The order on that screen is lock, then approve or reject. Approve and reject refuse rows that are not locked (`ExamMarksSubmissionController`). Reopen lets the teacher edit again. The sidebar child "Mark submissions" shows a count.
4. **Mark sheet.** Several URLs, not one screen. Class cards `GET /admin/marks/classes/{section?}` and the per-student page `GET /admin/marks/student/{user}/class/{class}/{exam}` use `ResultsService` (#1127, #1142, #1144). The older filter `GET /admin/marks` and `GET /admin/marks/filter` still only counts exams whose `status` is `submitted` (`Admin\MarksController`). The PDF `GET /admin/marks/download` uses `MarksReportService`, not `ResultsService`. The old `/admin/report/student/...` redirects. The proposal's "one results service" and "stop using exam status" are not finished on those two older screens.
5. **Report cards.** Admin `GET /admin/reports/cards`. Class teacher `GET /teacher/reports/cards`. PDFs from `StudentReportCardService`. #1130 reads aggregate, division and position from `ResultsService`.
6. **Release.** `GET /admin/marks/releases/new` and the same page under `/teacher/marks/releases/new` for a class teacher. #1135 refuses release until every subject in scope is approved. A school admin may override with a reason of at least 10 characters. A class teacher cannot. Local release screen showed Approved / Pending / Rejected / Not submitted and disabled the release button while a subject was not approved (`ux-brief/admin-release-1280.webp`).
7. **Parents and students.** Parent `GET /parent/children/{student}/grades`. Student `GET /student/marks` (`Student\RecordsController`, calls `ReleaseGate`). WhatsApp keywords GRADES, MARKS and REPORT go through the same gate (#1122, #1124). Staff screens are not gated.

`MarksImportService` still says "with no UI attached" at the top of `app/Services/MarksImport/MarksImportService.php`. That comment is wrong. The import pages and routes exist.

The student dashboard "Recent marks" card does not use `ReleaseGate`. `Dashboard::studentDashboard()` only fills marks if the class `Gegok12\Exam\Models\Mark` exists. That class is not in this repo, so the card stays empty while `/student/marks` works. Local student marks page said results were not published yet (`ux-brief/student-marks-1280.webp`), which matches an unreleased demo term.

`docs/product/marks-flow.md` section 3 still lists work that these PRs already did (remove the status toggle, remove dead WhatsApp grade methods, gate parent and student output, one results service). Treat that doc as the original plan, not as a list of open bugs. What is still visibly unfinished: the admin dashboard is not reduced to the six marks actions the proposal describes, and several older mark URLs remain (filter form, class sheet, PDF, spreadsheet). See the inventory.

**Proposed**

One sitting (one exam type, one term, one class). The teacher sees "needs marks", enters or uploads, then submits. The admin sees one queue, then one sheet, then release. Parents and students see nothing until release, in the portal, on WhatsApp, and in Toshi, with the same numbers. Dashboard tiles for staff should point at those steps and not at a second calculator.

### Fees and payments

**Today**

- School admin: `GET /admin/fees/payments` (list), `GET /admin/fees/payments/create`, `POST /admin/fees/payments/store` (`FeePaymentController@store`). The form records student, amount, category, method, reference, date. It does not take a card payment.
- Unmatched incoming payments: `GET /admin/fees/payments/unmatched`, match with `POST /admin/fees/payments/unmatched/{transaction}/match`.
- Reminders: `POST /admin/fees/payments/reminders`.
- Bursar: `GET /accountant/dashboard` and `GET /accountant/fees/payments` (same idea, accountant controllers). The bursar dashboard labels the total with a single currency code in `resources/views/accountant/dashboard.blade.php` (`Total Fees (UGX)`). Use the school's currency instead.
- Parent: `GET /parent/children/{student}/fees` reads balances from `ParentPortalService`. There is no pay button on that page.
- Payroll lives under `/accountant/payroll/*` and is a separate, older area.

**Proposed**

One fees home per role. Admin and bursar should not be two products. Parent sees what is owed and what was paid. Recording a payment is a staff job. Matching an unknown payment is a small queue, not a second app.

### Attendance

**Today**

- Teacher: `GET /teacher/attendance`, add `GET /teacher/attendance/add`, save `POST` through `Teacher\AttendanceController@store`. `SiteHelper::canTeacherRecordAttendance()` enforces the school setting `attendance_scope`: `class_teacher_only`, `classes_i_teach` (default), or `school_wide` (`docs/plans/attendance-scope-configurable-setting-plan.md`). The sidebar no longer points Attendance at `teacher/dashboard#attendance`. That bug in the September role matrix is fixed in `config/navigation.php` (`teacher.attendance.index`).
- Admin: `GET /admin/attendance`.
- Parent: `GET /parent/children/{student}/attendance`.
- Student: `GET /student/attendance`.

**Proposed**

A teacher opens today's register for their class, marks it, and leaves. Parents and students see the same record, read only. The scope setting stays a school choice, not three different screens.

### Toshi

**Today**

- **Web.** Livewire `agent-toshi` is included from `resources/views/layouts/partials/toshi-embed.blade.php` for usergroups 1, 3, 4, 5, 6, 7, 8, 9, 10 and 11. Modes are preview, onboarding, and assistant (`ToshiUiSwitch`). The side launcher is only for school admins and head teachers (usergroups 3 and 4). Assistant mode needs an API key and `toshi_mode=assistant`. New schools do not get that by default.
- **WhatsApp.** Keyword menu (fees, grades, attendance, link help) is the live path in `Api\WhatsAppController`. Free-form Toshi on WhatsApp is behind `TOSHI_WHATSAPP_CHANNEL_ENABLED` (default false) and the SDK v2 flag (`config/toshi.php`). When those flags are on, `WhatsAppToshiChannelService` still skips usergroup 4 (head teacher). `docs/toshi-whatsapp-channel-audit.md` is an older audit. The blade allowlist it describes is narrower than `toshi-embed.blade.php` today.
- **Slack.** Catalog entry in `config/toshi.php` (`TOSHI_SLACK_CHANNEL_ENABLED`, default false). There is also a local mock MCP server (`app/Mcp/Servers/SpikeSlackMockServer.php`) that is not Slack. No parent conversation runs through Slack on `main`.
- **Google.** Same catalog, `TOSHI_GOOGLE_DRIVE_ENABLED` default false. The admin dashboard can show a Drive "connected" bit from `school_mcp_connectors`, but the flag is off unless the environment says so.

`docs/toshi-deputy-admin-audit.md` (2 August 2026) describes head-teacher Toshi. The embed list has grown since that note. Use the blade file, not the audit, for who sees the panel.

**Proposed**

Toshi is one assistant with a clear mode: setting the school up, or answering a question. It should not be a second navigation. WhatsApp remains the parent channel. Slack and Google stay "off until a school connects them", not fake buttons.

## 4. Screen inventory

Routes come from `php artisan route:list` on this commit (1,519 routes). This section lists the screens a person opens. JSON list fragments (`*/list`, counts) are not screens. The appendix lists the other GET pages and says which were not opened.

State words:

- **New shell** — admin v3 sidebar (`config/navigation.php` layout `v3`) and the v2 dashboard (`resources/views/admin/dashboard/dashboard_v2.blade.php`).
- **Mixed** — `ds-*` cards inside the older flat sidebar.
- **Older page** — utility classes, `container-fluid`, or a legacy layout, even if the shell around it is newer.
- **Not opened** — in the route list, not loaded in this local pass.

Local HTTP status is from a headless Chrome pass against `php artisan serve` on 20 October 2026.

### Platform admin

| Screen | URL | Purpose | State | Recommendation |
|---|---|---|---|---|
| Dashboard | `/superadmin/dashboard` | Platform totals | Mixed. Opened, 200. `ux-brief/platform-dashboard-1280.webp` | Keep. Redesign with the other dashboards. |
| Schools | `/superadmin/academics/schools` | School list | Opened, 200. `ux-brief/platform-schools-1280.webp` | Keep. |
| Early access | `/platform/early-access` | Invite queue | Opened, 200. Empty state works. `ux-brief/platform-early-access-1280.webp` | Keep. This is the start of onboarding. |
| Subscriptions | `superadmin.reports.subscriptionlist` | Plans in use | Not opened | Keep. Fold into Schools if it repeats the same rows. |
| Plans | `superadmin.setting.planlist` | Plan catalogue | Not opened | Keep. |
| Reports | `/superadmin/reports` | Platform reports | Not opened | Keep one reports home. |
| Mail list | `/superadmin/mail-list` | Mail list | Not opened | Confirm with team. Likely admin-only. |
| Settings | `/superadmin/settings` | Platform settings | Not opened | Keep. |

### School admin and head teacher

Both use `/admin/*`. Head teacher rows are the same URLs. Settings and Classes and streams are hidden for the head teacher. School details still loads for them.

| Screen | URL | Purpose | State | Recommendation |
|---|---|---|---|---|
| Dashboard | `/admin/dashboard` | School home, setup bar, quick actions | New shell. 200. Desktop `ux-brief/admin-dashboard-1280.webp`, phone `ux-brief/admin-dashboard-375.webp`. Head teacher: `ux-brief/head-dashboard-1280.webp` | Keep. First screen to redesign per role. |
| Students | `/admin/students` | Student list | New shell. 200. `ux-brief/admin-students-1280.webp` | Keep. |
| Student profile | `/admin/students/{id}` | One student | Not opened | Keep. Merge the old `/admin/student/show/{name}` tabs into it. |
| Admissions | `/admin/admissions` | Applications | Not opened as its own shot. Count badge showed 2. | Keep. |
| Health | `/admin/health` | Health summary | Not opened. `StudentHealthOverviewController` is a real page. The September matrix said this URL redirected to Students. That is no longer true. | Keep only if the summary is useful. Otherwise link health from the student. |
| Teachers and staff | `/admin/teachers` | Staff list | Not opened | Keep. |
| Parents | `/admin/parents` | Parent list | Not opened | Keep. |
| Classes | `/admin/classes` | Classes and streams | Opened, 200. `ux-brief/admin-classes-1280.webp` | Keep. `/admin/sections` is the same area. Merge. |
| Subjects | `/admin/subjects` | Subjects | Not opened | Keep. |
| Timetable | `/admin/timetable` | Timetable | Not opened | Keep. There is also `/admin/timetable/slots`. Merge. |
| Calendar | `/admin/calendar` | Events | Not opened | Keep. `/admin/events` overlaps it. Merge. |
| Attendance | `/admin/attendance` | School attendance | Opened, 200. `ux-brief/admin-attendance-1280.webp` | Keep. |
| Exams | `/admin/exams` | Exam list | Opened, 200. `ux-brief/admin-exams-1280.webp` | Keep as the staff exam list. |
| Marks filter | `/admin/marks` | Filter form | Opened, 200. `ux-brief/admin-marks-1280.webp` | Merge into the class mark sheet. |
| Class mark sheet | `/admin/marks/classes` | Combined sheet | Opened, 200. `ux-brief/admin-marks-classes-1280.webp` | Keep. Make this the one sheet. |
| Mark submissions | `/admin/marks/submissions` | Approval queue | Opened, 200. `ux-brief/admin-submissions-1280.webp` | Keep. |
| Release | `/admin/marks/releases/new` | Release to families | Opened, 200. `ux-brief/admin-release-1280.webp` | Keep. |
| Grading | `/admin/grades` | Scales | Not opened | Keep. |
| Report cards | `/admin/reports/cards` | Generate and download | Opened, 200. `ux-brief/admin-report-cards-1280.webp` | Keep. |
| Library | `/admin/library/books` | Books | Not opened | Later. Not in the first pass. |
| Fees | `/admin/fees/payments` | Payments | Opened, 200. `ux-brief/admin-fees-1280.webp` | Keep. Share the design with the bursar. |
| Unmatched payments | `/admin/fees/payments/unmatched` | Match queue | Opened, 200. `ux-brief/admin-fees-unmatched-1280.webp` | Keep as a queue, not a top-level product. |
| WhatsApp | `/admin/whatsapp/dashboard` | Link requests and threads | Opened, 200. `ux-brief/admin-whatsapp-1280.webp` | Keep. High priority. |
| WhatsApp parents | `/admin/whatsapp/parents` | Linked numbers | Opened, 200. `ux-brief/admin-whatsapp-parents-1280.webp` | Merge into the WhatsApp home. |
| Approvals | `/admin/approvals` | Cross-type queue | Opened, 200. `ux-brief/admin-approvals-1280.webp` | Keep one queue. Marks submissions should be a filter here, not a forgotten second list. |
| Transport | `/admin/transport` | Transport | Not opened | Later. Confirm with team whether schools use it. |
| School details | `/admin/schooldetails` | School profile | Opened as head teacher, 200. `ux-brief/head-settings-attempt-1280.webp` | Keep for school admins. Decide if head teachers should see it. Today the menu hides it and the URL does not. |
| Academic years | `/admin/academics` | Years and terms | Not opened | Keep under Settings. |
| Data exports | `/admin/reports` | Export home | Not opened | Keep one export screen. Many `/admin/report/*` URLs are separate exports. Merge. |
| Onboarding choice | `/onboarding/choose` | Wizard or Toshi | Opened, 200. `ux-brief/onboarding-choice-1280.webp` | Keep. |
| Wizard | `/admin/onboarding/wizard` | Manual setup | Opened, 200. `ux-brief/onboarding-wizard-1280.webp` | Keep. |
| Messages | `/admin/messages` | Another message screen | Not opened. Not in the sidebar. | Merge into WhatsApp or remove. |

Phone shots of the admin shell: `ux-brief/admin-dashboard-375.webp`, `ux-brief/admin-sidebar-375.webp`, `ux-brief/admin-marks-375.webp`. At 375px the dashboard is one column. The desktop sidebar is not pinned. The menu button opens `res_sidebar`.

### Class teacher and subject teacher

Both are usergroup 5. The extra rows appear only for a homeroom teacher.

| Screen | URL | Who | Purpose | State | Recommendation |
|---|---|---|---|---|---|
| Dashboard | `/teacher/dashboard` | Both | Today's work | Mixed. Class teacher `ux-brief/class-teacher-dashboard-1280.webp` and `…-375.webp`. Subject teacher `ux-brief/subject-teacher-dashboard-1280.webp` | Keep. Redesign first. |
| Classes | `/teacher/classes` | Both | Their classes | Not opened | Keep. |
| Timetable | `/teacher/timetable` | Both | Their week | Not opened | Keep. |
| Attendance | `/teacher/attendance` | Both, scoped | Register | Opened, 200. `ux-brief/class-teacher-attendance-1280.webp` | Keep. Mobile first. |
| Exams | `/teacher/exam/create` | Class teacher | Create an exam | Opened, 200. `ux-brief/class-teacher-exams-1280.webp` | Keep for class teachers only. |
| Homework | `/teacher/homeworks` | Both | Homework | Not opened | Keep. |
| Marks list | `/teacher/exam/marks` | Both | Exams they can mark | Opened, 200. Class teacher `ux-brief/class-teacher-marks-1280.webp`. Subject teacher `ux-brief/subject-teacher-marks-1280.webp` | Keep. This is the only list. `/teacher/exams` already redirects here. |
| Enter marks | `/teacher/exam/{exam}/marks/enter` | The exam's teacher | Grid | Opened, 200 for their exam. Older layout. `ux-brief/class-teacher-enter-1280.webp` and `…-375.webp` | Keep. Redesign with the marks flow. |
| Import marks | `/teacher/exam/{exam}/marks/import` | The exam's teacher | Spreadsheet | Opened, 200. `ux-brief/class-teacher-import-1280.webp` | Keep next to entry. |
| Report cards | `/teacher/reports/cards` | Class teacher | Class PDFs | Opened, 200. `ux-brief/class-teacher-reports-1280.webp` | Keep for class teachers. |
| Class streams | `/teacher/class-stream` | Class teacher (section ownership) | Streams | Not opened | Keep for class teachers. |
| Notices | `/teacher/notices` | Both | Read notices | Not opened | Keep. |
| Events | `/teacher/events` | Both | Read events | Not opened | Keep. |
| Release | `/teacher/marks/releases/new` | Class teacher, own class | Release | Not opened in this pass (admin release was) | Keep. Same screen as the admin release. |

Subject-teacher sidebar, rendered locally: Dashboard, Classes, Timetable, Attendance, Homework, Marks, Notices, Events. No Exams, Report Cards, or Class Streams.

Teacher phone shots: `ux-brief/class-teacher-dashboard-375.webp`, `ux-brief/class-teacher-sidebar-375.webp`, `ux-brief/class-teacher-enter-375.webp`. The menu drawer is taller than the phone, so the shot shows the top of the list.

### Bursar

| Screen | URL | Purpose | State | Recommendation |
|---|---|---|---|---|
| Dashboard | `/accountant/dashboard` | Fee totals | Mixed. The page title says Accountant. 200. `ux-brief/bursar-dashboard-1280.webp` | Keep. Call the person a bursar if that is the product word, and drop the hardcoded currency code. |
| Fees | `/accountant/fees/payments` | Record and review payments | Opened, 200. `ux-brief/bursar-fees-1280.webp` | Merge visually with `/admin/fees/payments`. |
| Exports | `accountant.reports` | Exports | Not opened | Keep with admin exports. |
| Holidays | `/accountant/holidays` | Holiday list | Not opened | Remove from the bursar. They do not own the calendar. |
| Payroll | `/accountant/payroll/template` and salary, payslip, batch | Payroll | Not opened | Later. Separate from parent fee balances. |

### Parent

| Screen | URL | Purpose | State | Recommendation |
|---|---|---|---|---|
| Dashboard | `/parent/dashboard` | Children and summary | Mixed. 200. `ux-brief/parent-dashboard-1280.webp`, `…-375.webp` | Keep. Mobile first. |
| Children | `/parent/children` | Pick a child | Opened, 200. `ux-brief/parent-children-1280.webp` | Keep. |
| Fees | `/parent/children/{id}/fees` | Balance | Opened, 200. `ux-brief/parent-fees-1280.webp` | Keep. Read only. |
| Grades | `/parent/children/{id}/grades` | Released results | Opened, 200. Empty until release. `ux-brief/parent-grades-1280.webp`, `…-375.webp` | Keep. |
| Attendance | `/parent/children/{id}/attendance` | Attendance | Opened, 200. `ux-brief/parent-attendance-1280.webp` | Keep. |

Parent routes in `routes/parent.php` are only these plus notifications. The sidebar matches the routes. Phone menu: `ux-brief/parent-sidebar-375.webp`.

### Student

| Screen | URL | Purpose | State | Recommendation |
|---|---|---|---|---|
| Dashboard | `/student/dashboard` | Summary | Mixed. Recent-marks card is wired to a missing class (see marks). 200. `ux-brief/student-dashboard-1280.webp`, `…-375.webp` | Keep. Fix the marks card so it uses the same released results as `/student/marks`. |
| Marks | `/student/marks` | Released marks | Opened, 200. `ux-brief/student-marks-1280.webp` | Keep. |
| Attendance | `/student/attendance` | Own attendance | Opened, 200. `ux-brief/student-attendance-1280.webp` | Keep. |
| Homework | `/student/homeworks` | Homework | Opened, 200. `ux-brief/student-homework-1280.webp` | Keep. |
| Assignments | `/student/assignments` | Submit work | Not opened | Keep. Students submit work. They do not create class assignments (`docs/internal/role-capability-matrix.md` finding, still true in `Student\AssignmentController`). |
| Events | `/student/events` | Events | Not opened | Keep. |
| Notices | `/student/notices` | Notices | Not opened | Keep. |
| Library | `/student/libraryactivity` | Loans | Not opened | Later. |
| Holidays | `/student/holidays` | Holidays | Not opened | Merge into Events or the dashboard. |
| Chats | `/student/conversations` | Conversations | Not opened | Confirm with team. Easy to confuse with WhatsApp. |
| Activity | `/student/activity` | Activity log | Not opened | Remove from the student home unless a school asks for it. |

`routes/student.php` has more GET URLs (tasks, class wall, feeds, notifications). They are not in the sidebar. See the appendix. Phone menu: `ux-brief/student-sidebar-375.webp`. The drawer is taller than the phone, so the shot shows the top of the list.

### Public

| Screen | URL | Purpose | State | Recommendation |
|---|---|---|---|---|
| Early access | `/early-access` | Request an invite | Opened, 200. `ux-brief/early-access-1280.webp`, `…-375.webp` | Keep. |
| Sign in | `/login` | Sign in | Used, not saved as a screenshot (it would show an address). | Keep. Redesign with early access. |
| School invite | `/invite/school/{token}` | Set name, phone, password | Not opened (needs a live token). | Keep. |
| Teacher invite | `/invite/teacher/{token}` | Set a password | Not opened | Keep. |

### Notable broken, duplicate, or overlapping screens

- **Two fee homes.** `/admin/fees/payments` and `/accountant/fees/payments`.
- **Two parent-link places plus Approvals.** WhatsApp dashboard, WhatsApp parents, and `/admin/approvals`.
- **Several mark sheets.** `/admin/marks`, `/admin/marks/filter`, `/admin/marks/classes`, release preview, PDF and spreadsheet exports. The proposal says one sheet. The routes are all still there.
- **Teacher exam URLs.** `/teacher/exams` and `/teacher/exam` redirect to `/teacher/exam/marks`. Good. Do not design them as separate pages.
- **Student marks card versus student marks page.** Dashboard card is effectively dead. The page works and is release-gated.
- **Head teacher Settings.** Hidden in the menu, school details still opens.
- **Bursar dashboard currency.** One currency code is hardcoded.
- **Health.** September audit said `/admin/health` duplicated Students. The controller is now a summary. Confirm the page is worth a nav row.
- **Messages versus WhatsApp.** `/admin/messages` is not in the sidebar.
- **Accountant holidays and payroll** sit on a fees role.
- **Stock** menu is empty on purpose (`config/navigation.php`).
- **Delete-on-GET** still exists in older route files (for example `/admin/admission/delete/{id}`, `/admin/holiday/delete/{id}`). Do not design those as buttons that navigate. They are a code problem, not a pattern to copy.

## 5. Navigation

The sidebar is data in `config/navigation.php`, drawn by `resources/views/layouts/partials/sidebar-menu.blade.php`. Admin uses the v3 renderer (`sidebar-menu-v3.blade.php`). Every other role uses the flat list.

### Today, by role

**School admin** (sections People, Academics, Money, School):

- Dashboard
- Students → All students, Admissions, Health records
- Teachers and staff
- Parents
- Classes and subjects → Classes and streams, Subjects, Timetable, Calendar
- Attendance
- Exams and reports → Exams and marks, Release marks, Mark submissions, Grading, Report cards
- Library
- Fees
- Unmatched payments
- WhatsApp
- Approvals
- Transport
- Settings → School details, Academic years and terms, Data exports

**Head teacher:** the same list without Classes and streams and without Settings.

**Class teacher:** Dashboard, Classes, Timetable, Attendance, Exams, Homework, Marks, Report Cards, Class Streams, Notices, Events.

**Subject teacher:** Dashboard, Classes, Timetable, Attendance, Homework, Marks, Notices, Events.

**Bursar:** Dashboard, Fees & Payments, Data Exports, Holidays, Payroll (Templates, Salaries, Payslips, Batch Run).

**Parent:** Dashboard, Children, Fees, Grades, Attendance.

**Student:** Dashboard, Marks, Attendance, Homework, Assignments, Events, Notices, Library, Holidays, Chats, Activity.

**Platform admin:** Dashboard, Schools, Subscriptions, Plans, Reports, Mail List, Settings.

### Problems

- Admin nesting is deep (Exams and reports has five children) while teacher, parent and student are one flat list. The same job has a different shape per role.
- "Exams and marks", "Release marks" and "Mark submissions" are siblings, but the work is a sequence. The menu reads like three products.
- Fees and Unmatched payments are both top-level. Unmatched is a queue inside fees.
- WhatsApp, Approvals and Messages overlap.
- Head teacher can open a settings URL the menu hides.
- Bursar nav includes Holidays.
- Student nav includes Activity, Holidays and Chats with no explanation of how they differ from notices and events.
- Names differ for the same idea: "Fees" versus "Fees & Payments", "Report Cards" versus "Report cards", "Mail List" versus sentence case.
- Librarian, receptionist and alumni have their own menus. They are easy to forget and easy to leave on the old visual style.
- The flat sidebars do not use the v3 grouping, so a redesign of the admin sidebar will not reach teachers or parents unless those shells are included.
- On the teacher and student shells at 375px, the header search button (`#command-palette-trigger` in `resources/views/layouts/partials/navigation.blade.php`) covers the menu button (`#mobile-menu-trigger`). A tap on the menu hits Search. The phone menu shots for those two roles were opened from the page, because a real click never landed. The admin and parent menu buttons accepted a real click in the same local run.

### Proposed

One pattern for every role: a short list, grouped only when there are more than about seven items. Sequence lives inside the screen, not as extra nav rows.

**Platform admin:** Dashboard, Schools, Early access, Plans, Settings.

**School admin:** Dashboard, People (students, staff, parents), Classes, Attendance, Marks, Fees, WhatsApp, Settings.

**Head teacher:** the school admin list without Settings, unless the team decides they should edit the school profile. If they should, show the link and allow the URL. If they should not, block the URL too.

**Class teacher:** Dashboard, Today (attendance), Marks, Homework, Report cards.

**Subject teacher:** Dashboard, Marks, Homework, Timetable.

**Bursar:** Dashboard, Fees. Payroll only if that bursar runs payroll.

**Parent:** Home, Fees, Results, Attendance. Children is a switcher, not a destination, when they have more than one.

**Student:** Home, Results, Attendance, Homework.

## 6. Constraints

- **Mobile first for teachers and parents.** Check 375px and 1280px. The local admin dashboard at 375px is already one column. The marks grid is the screen most likely to break on a phone (`ux-brief/class-teacher-enter-375.webp`).
- **Slow connections.** Prefer server-rendered pages, small images, and no extra font hosts if the tokens can be self-hosted. The app shell currently loads Sora and DM Sans from Google Fonts in `resources/views/layouts/app.blade.php`, plus Vite CSS, `public/css/admin.css`, `public/css/dashboard-refresh.css`, and Toshi CSS. That is a lot of CSS on every page. The docs handoff asks for Latin woff2 files and no Google Fonts request (`design/system/klassapp-handoff-2026-09-29-docs/HANDOFF.md`). There is no offline mode in the code. Unknown, confirm with team whether teachers must enter marks with no network. Do not promise offline until that is decided. Do keep each task on as few requests as possible.
- **Accessible.** Target WCAG 2.2 AA. The token file already darkens the green used for white button text (`design/system/tokens/colors.css`, `--d-accent` `#15803D`). Hit targets on the marks page are set to 44px in the Blade. Do not ship contrast that only works on a desktop mock.
- **Start from the existing tokens and handoffs.** `design/system/SOURCE-OF-TRUTH.md` is the index named in `AGENTS.md` and `docs/ops/BUILDER-BRIEF.md`. That file is not in the tree. Use these instead:
  - Tokens: `design/system/tokens/colors.css`, `fonts.css`, `spacing.css`, `typography.css`, `radii-shadows.css`, `surfaces.css`
  - Docs handoff (the one this brief was asked to start from): `design/system/klassapp-handoff-2026-09-29-docs/`
  - Newer merged handoff: `design/system/klassapp-handoff-2026-10-14-merged/HANDOFF.md`
  - Sidebar: `design/system/klassapp-handoff-2026-10-10-sidebar-v3/`
  - Admin concept: `design/system/klassapp-handoff-2026-10-08-admin-mvp/`
  - Guidelines: `design/system/guidelines/`
  - Legacy kits under `design/system/ui_kits/school-dashboard/` are not the target (`AGENTS.md` rule 46).
- **Do not depend on Vue 2 or Bootstrap 4.** The running front end is Vue 3.5.40 through `@vue/compat` in mode 2, bundled by Vite (`package.json`, `AGENTS.md`). Bootstrap 4.5.2 is still a dependency, and `resources/assets/sass/app.scss` has the Bootstrap import commented out. `layouts/app.blade.php` still loads jQuery from `public/js/jquery.min.js`. Some packages in `package.json` are Vue 2-era (the install log on this machine warned that `vue@2.7.16` is present as a nested dependency). Design in HTML and the token CSS. Do not specify Bootstrap grids, Bootstrap modals, or Vue 2 components as the way a screen is built.

## 7. Priority for the designer

1. **Dashboards, one per role.** Platform, school admin, head teacher, class teacher, subject teacher, bursar, parent, student. Same grid, same type, role-specific tasks. Shots: `ux-brief/platform-dashboard-1280.webp`, `admin-dashboard-1280.webp`, `admin-dashboard-375.webp`, `head-dashboard-1280.webp`, `class-teacher-dashboard-1280.webp`, `class-teacher-dashboard-375.webp`, `subject-teacher-dashboard-1280.webp`, `bursar-dashboard-1280.webp`, `parent-dashboard-1280.webp`, `parent-dashboard-375.webp`, `student-dashboard-1280.webp`, `student-dashboard-375.webp`.
2. **Marks flow.** List, enter, import, submit, submissions, sheet, report cards, release, parent grades, student marks. Shots: `class-teacher-marks-1280.webp`, `class-teacher-enter-1280.webp`, `class-teacher-enter-375.webp`, `class-teacher-import-1280.webp`, `admin-submissions-1280.webp`, `admin-marks-classes-1280.webp`, `admin-release-1280.webp`, `admin-report-cards-1280.webp`, `parent-grades-1280.webp`, `student-marks-1280.webp`.
3. **WhatsApp screens.** School queue and the parent link story, plus the empty and pending states. Shots: `admin-whatsapp-1280.webp`, `admin-whatsapp-parents-1280.webp`, `admin-approvals-1280.webp`. The phone thread itself is not a page in this app. Unknown, confirm with team if they want a designed WhatsApp script as well as the web queue.
4. **Onboarding.** Early access, invite claim (not screenshotted; needs a token), choice, wizard, Toshi setup. Shots: `early-access-1280.webp`, `early-access-375.webp`, `onboarding-choice-1280.webp`, `onboarding-wizard-1280.webp`.
5. **The rest.** People, classes, attendance, fees, timetable, library, transport, payroll, student chats. After the four above are reviewable.

## Screenshots

All files are WebP, desktop 1280×800 or phone 375×812, from local Chrome. Addresses in the account card were hidden before capture. Names on screen are the demo seeder's fictional people.

| File | What it is |
|---|---|
| `ux-brief/admin-dashboard-1280.webp` | School admin dashboard |
| `ux-brief/admin-dashboard-375.webp` | School admin dashboard, phone |
| `ux-brief/admin-sidebar-375.webp` | School admin menu, phone |
| `ux-brief/head-dashboard-1280.webp` | Head teacher dashboard |
| `ux-brief/head-settings-attempt-1280.webp` | Head teacher opened school details by URL |
| `ux-brief/class-teacher-dashboard-1280.webp` | Class teacher dashboard |
| `ux-brief/class-teacher-dashboard-375.webp` | Class teacher dashboard, phone |
| `ux-brief/class-teacher-sidebar-375.webp` | Class teacher menu, phone |
| `ux-brief/subject-teacher-dashboard-1280.webp` | Subject teacher dashboard |
| `ux-brief/bursar-dashboard-1280.webp` | Bursar dashboard |
| `ux-brief/parent-dashboard-1280.webp` | Parent dashboard |
| `ux-brief/parent-dashboard-375.webp` | Parent dashboard, phone |
| `ux-brief/parent-sidebar-375.webp` | Parent menu, phone |
| `ux-brief/student-dashboard-1280.webp` | Student dashboard |
| `ux-brief/student-dashboard-375.webp` | Student dashboard, phone |
| `ux-brief/student-sidebar-375.webp` | Student menu, phone |
| `ux-brief/platform-dashboard-1280.webp` | Platform admin dashboard |
| `ux-brief/class-teacher-marks-1280.webp` | Teacher marks list |
| `ux-brief/subject-teacher-marks-1280.webp` | Subject teacher marks list |
| `ux-brief/class-teacher-enter-1280.webp` | Enter marks |
| `ux-brief/class-teacher-enter-375.webp` | Enter marks, phone |
| `ux-brief/class-teacher-import-1280.webp` | Import marks |
| `ux-brief/admin-submissions-1280.webp` | Mark submissions |
| `ux-brief/admin-marks-1280.webp` | Admin marks filter |
| `ux-brief/admin-marks-375.webp` | Admin marks filter, phone |
| `ux-brief/admin-marks-classes-1280.webp` | Class mark sheet |
| `ux-brief/admin-release-1280.webp` | Release marks |
| `ux-brief/admin-exams-1280.webp` | Admin exams |
| `ux-brief/admin-report-cards-1280.webp` | Report cards |
| `ux-brief/class-teacher-reports-1280.webp` | Teacher report cards |
| `ux-brief/class-teacher-exams-1280.webp` | Teacher create exam |
| `ux-brief/parent-grades-1280.webp` | Parent grades, nothing released |
| `ux-brief/parent-grades-375.webp` | Parent grades, phone |
| `ux-brief/admin-whatsapp-1280.webp` | WhatsApp home |
| `ux-brief/admin-whatsapp-parents-1280.webp` | WhatsApp parents |
| `ux-brief/admin-approvals-1280.webp` | Approvals |
| `ux-brief/early-access-1280.webp` | Early access |
| `ux-brief/early-access-375.webp` | Early access, phone |
| `ux-brief/onboarding-choice-1280.webp` | Wizard or Toshi |
| `ux-brief/onboarding-wizard-1280.webp` | Wizard step |
| `ux-brief/admin-students-1280.webp` | Students |
| `ux-brief/admin-classes-1280.webp` | Classes |
| `ux-brief/admin-attendance-1280.webp` | Admin attendance |
| `ux-brief/class-teacher-attendance-1280.webp` | Teacher attendance |
| `ux-brief/admin-fees-1280.webp` | Admin fees |
| `ux-brief/admin-fees-unmatched-1280.webp` | Unmatched payments |
| `ux-brief/bursar-fees-1280.webp` | Bursar fees |
| `ux-brief/parent-fees-1280.webp` | Parent fees |
| `ux-brief/parent-children-1280.webp` | Parent children |
| `ux-brief/parent-attendance-1280.webp` | Parent attendance |
| `ux-brief/student-marks-1280.webp` | Student marks |
| `ux-brief/student-attendance-1280.webp` | Student attendance |
| `ux-brief/student-homework-1280.webp` | Student homework |
| `ux-brief/platform-schools-1280.webp` | Platform schools |
| `ux-brief/platform-early-access-1280.webp` | Platform invite queue |

## Appendix. Other GET pages

These are GET routes under the admin, teacher, parent, student, platform, superadmin and accountant prefixes that are not the primary rows above. List fragments and count endpoints are omitted. They were **not opened** in this pass unless the primary table says otherwise. Treat them as legacy until someone opens them. A GET that deletes or mutates should be removed, not redesigned.

Generated from `php artisan route:list` on `5f3274f9`.

Count: 640 GET routes in this appendix.

### `accountant/activity`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/accountant/activity` | `` | `Accountant\ActivityLogController@index` | Not opened. |

### `accountant/changeavatar`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/accountant/changeavatar` | `` | `Accountant\UserProfileController@changeavatar` | Not opened. |

### `accountant/changepassword`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/accountant/changepassword` | `` | `Accountant\UserProfileController@ChangePassword` | Not opened. |

### `accountant/dashboard`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/accountant/dashboard` | `` | `Accountant\DashboardController@index` | Not opened. |
| `/accountant/dashboard/birthday` | `` | `Accountant\BirthdayController@birthday` | Not opened. |
| `/accountant/dashboard/birthday/teacher` | `` | `Accountant\BirthdayController@birthdayCreate` | Not opened. |
| `/accountant/dashboard/birthdayTeacher` | `` | `Accountant\BirthdayController@birthdayTeacher` | Not opened. |
| `/accountant/dashboard/birthdayUser` | `` | `Accountant\BirthdayController@birthdayUser` | Not opened. |
| `/accountant/dashboard/showBirthday` | `` | `Accountant\BirthdayController@showBirthday` | Not opened. |
| `/accountant/dashboard/showBirthdayTeacher` | `` | `Accountant\BirthdayController@showBirthdayTeacher` | Not opened. |
| `/accountant/dashboard/showWorkAnniversary` | `` | `Accountant\BirthdayController@showWorkAnniversary` | Not opened. |
| `/accountant/dashboard/tasklist/{task_flag}` | `` | `Accountant\DashboardController@list` | Not opened. |
| `/accountant/dashboard/workAnniversary` | `` | `Accountant\BirthdayController@workAnniversaryCreate` | Not opened. |

### `accountant/events`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/accountant/events` | `` | `Accountant\EventsController@index` | Not opened. |
| `/accountant/events/details/{id}` | `` | `Accountant\EventsController@details` | Not opened. |
| `/accountant/events/show` | `` | `Accountant\EventsController@events` | Not opened. |
| `/accountant/events/show/details/{id}` | `` | `Accountant\EventsController@show` | Not opened. |
| `/accountant/events/showdetails/{id}` | `` | `Accountant\EventsController@showdetails` | Not opened. |

### `accountant/exportUnpaidpayroll`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/accountant/exportUnpaidpayroll` | `` | `Payroll\ReportsController@exportUnpaidPayrolls` | Not opened. |

### `accountant/feeds`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/accountant/feeds` | `` | `Accountant\FeedController@index` | Not opened. |

### `accountant/fees`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/accountant/fees/payments` | `accountant.fee-payments` | `Accountant\FeePaymentController@index` | Not opened. |
| `/accountant/fees/payments/create` | `accountant.fee-payments.create` | `Accountant\FeePaymentController@create` | Not opened. |

### `accountant/getavatar`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/accountant/getavatar` | `` | `Accountant\UserProfileController@getavatar` | Not opened. |

### `accountant/holidays`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/accountant/holidays` | `` | `Accountant\HolidaysController@index` | Not opened. |

### `accountant/notices`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/accountant/notices` | `` | `Accountant\NoticeBoardController@index` | Not opened. |

### `accountant/notifications`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/accountant/notifications` | `` | `Accountant\NotificationController@index` | Not opened. |

### `accountant/payroll`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/accountant/payroll/batch` | `` | `Payroll\PayrollController@batchIndex` | Not opened. |
| `/accountant/payroll/payslip` | `` | `Payroll\PayrollController@index` | Not opened. |
| `/accountant/payroll/payslip/create` | `` | `Payroll\PayrollController@create` | Not opened. |
| `/accountant/payroll/payslip/getpayrollno` | `` | `Payroll\PayrollController@getpayrollnumber` | Not opened. |
| `/accountant/payroll/payslip/showlist` | `` | `Payroll\PayrollController@list` | Not opened. |
| `/accountant/payroll/payslip/{id}/edit` | `` | `Payroll\PayrollController@edit` | Not opened. |
| `/accountant/payroll/payslip/{id}/editshow` | `` | `Payroll\PayrollController@editshow` | Not opened. |
| `/accountant/payroll/payslip/{id}/print` | `` | `Payroll\PayrollController@printPayslip` | Not opened. |
| `/accountant/payroll/payslip/{id}/show` | `` | `Payroll\PayrollController@show` | Not opened. |
| `/accountant/payroll/payslip/{id}/view` | `` | `Payroll\PayrollController@downloadpayroll` | Not opened. |
| `/accountant/payroll/salary` | `` | `Payroll\PayrollSalaryController@index` | Not opened. |
| `/accountant/payroll/salary/create` | `` | `Payroll\PayrollSalaryController@create` | Not opened. |
| `/accountant/payroll/salary/showlist` | `` | `Payroll\PayrollSalaryController@list` | Not opened. |
| `/accountant/payroll/salary/{id}/edit` | `` | `Payroll\PayrollSalaryController@edit` | Not opened. |
| `/accountant/payroll/salary/{id}/editshow` | `` | `Payroll\PayrollSalaryController@editshow` | Not opened. |
| `/accountant/payroll/salary/{id}/show` | `` | `Payroll\PayrollSalaryController@show` | Not opened. |
| `/accountant/payroll/template` | `` | `Payroll\PayrollTemplateController@index` | Not opened. |
| `/accountant/payroll/template/create` | `` | `Payroll\PayrollTemplateController@create` | Not opened. |
| `/accountant/payroll/template/showlist` | `` | `Payroll\PayrollTemplateController@list` | Not opened. |
| `/accountant/payroll/template/{id}/edit` | `` | `Payroll\PayrollTemplateController@edit` | Not opened. |
| `/accountant/payroll/template/{id}/editshow` | `` | `Payroll\PayrollTemplateController@editshow` | Not opened. |
| `/accountant/payroll/template/{id}/itemlist` | `` | `Payroll\PayrollTemplateController@itemlist` | Not opened. |
| `/accountant/payroll/template/{id}/salary` | `` | `Payroll\PayrollSalaryController@detail` | Not opened. |
| `/accountant/payroll/transaction` | `` | `Payroll\TransactionController@index` | Not opened. |
| `/accountant/payroll/transaction/create` | `` | `Payroll\TransactionController@create` | Not opened. |
| `/accountant/payroll/transaction/getvoucherno` | `` | `Payroll\TransactionController@getvouchernumber` | Not opened. |
| `/accountant/payroll/transaction/showlist` | `` | `Payroll\TransactionController@list` | Not opened. |
| `/accountant/payroll/transaction/{id}/edit` | `` | `Payroll\TransactionController@edit` | Not opened. |
| `/accountant/payroll/transaction/{id}/editshow` | `` | `Payroll\TransactionController@editshow` | Not opened. |
| `/accountant/payroll/transaction/{id}/show` | `` | `Payroll\TransactionController@show` | Not opened. |
| `/accountant/payroll/user/{id}/detail` | `` | `Payroll\TransactionController@detail` | Not opened. |

### `accountant/reports`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/accountant/reports` | `accountant.reports` | `closure` | Not opened. |

### `accountant/task`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/accountant/task/add` | `` | `Accountant\TaskController@create` | Not opened. |
| `/accountant/task/edit/{id}` | `` | `Accountant\TaskController@edit` | Not opened. |
| `/accountant/task/show/{id}` | `` | `Accountant\TaskController@show` | Not opened. |
| `/accountant/task/{id}/delete` | `` | `Accountant\TaskController@destroy` | GET deletes. Remove, do not redesign. |

### `accountant/tasks`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/accountant/tasks` | `` | `Accountant\TaskController@index` | Not opened. |

### `accountant/unpaid`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/accountant/unpaid/bank` | `` | `Payroll\ReportsController@showbank` | Not opened. |
| `/accountant/unpaid/report` | `` | `Payroll\ReportsController@show` | Not opened. |

### `admin/absentees`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/absentees/staff` | `` | `Admin\StaffAttendanceController@staff` | Not opened. |
| `/admin/absentees/student` | `` | `Admin\AttendanceController@student` | Not opened. |
| `/admin/absentees/teacher` | `` | `Admin\AttendanceController@teacher` | Not opened. |

### `admin/academic`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/academic/add` | `` | `Admin\AcademicYearController@create` | Not opened. |
| `/admin/academic/delete/{id}` | `` | `Admin\AcademicYearController@destroy` | GET deletes. Remove, do not redesign. |
| `/admin/academic/edit/{id}` | `` | `Admin\AcademicYearController@edit` | Not opened. |
| `/admin/academic/show/{id}` | `` | `Admin\AcademicYearController@show` | Not opened. |

### `admin/academic-term`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/academic-term` | `admin.academic-term` | `Admin\Academics\AcademicTermController@index` | Not opened. |
| `/admin/academic-term/create` | `admin.academic-term.create` | `Admin\Academics\AcademicTermController@create` | Not opened. |
| `/admin/academic-term/{term}/edit` | `admin.academic-term.edit` | `Admin\Academics\AcademicTermController@edit` | Not opened. |

### `admin/academics`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/academics` | `` | `Admin\AcademicYearController@index` | Not opened. |

### `admin/activity`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/activity` | `` | `Admin\ActivityLogController@index` | Not opened. |

### `admin/addon`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/addon` | `` | `closure` | Not opened. |
| `/admin/addon/install/exam` | `` | `Admin\Addon\AddonInstallExamController@create` | Not opened. |
| `/admin/addon/{name}/detail` | `` | `closure` | Not opened. |

### `admin/admission`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/admission/delete/{id}` | `` | `Admin\AdmissionController@destroy` | GET deletes. Remove, do not redesign. |
| `/admin/admission/edit/{id}` | `` | `Admin\AdmissionController@edit` | Not opened. |
| `/admin/admission/show/{id}` | `` | `Admin\AdmissionController@show` | Not opened. |

### `admin/admissionlist`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/admissionlist` | `` | `Admin\AdmissionController@admissionlist` | Not opened. |

### `admin/admissions`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/admissions` | `` | `Admin\AdmissionController@index` | Not opened. |

### `admin/approvals`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/approvals` | `admin.approvals.inbox` | `Admin\ApprovalController@inbox` | Not opened. |

### `admin/attendance`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/attendance` | `` | `Admin\AttendanceController@index` | Not opened. |
| `/admin/attendance/add` | `` | `Admin\AttendanceController@create` | Not opened. |
| `/admin/attendance/export/{standardLink_id}` | `` | `Admin\AttendanceController@export` | Not opened. |
| `/admin/attendance/staff/add` | `` | `Admin\StaffAttendanceController@create` | Not opened. |

### `admin/bankdetails`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/bankdetails/edit/{id}` | `` | `Admin\BankDetailController@edit` | Not opened. |
| `/admin/bankdetails/get/{name}` | `` | `Admin\BankDetailController@index` | Not opened. |

### `admin/calendar`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/calendar` | `` | `closure` | Not opened. |

### `admin/calllog`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/calllog` | `` | `Admin\CallLogController@index` | Not opened. |
| `/admin/calllog/add` | `` | `Admin\CallLogController@create` | Not opened. |
| `/admin/calllog/delete/{id}` | `` | `Admin\CallLogController@destroy` | GET deletes. Remove, do not redesign. |
| `/admin/calllog/edit/{id}` | `` | `Admin\CallLogController@edit` | Not opened. |
| `/admin/calllog/show/{id}` | `` | `Admin\CallLogController@show` | Not opened. |
| `/admin/calllog/showlist` | `` | `Admin\CallLogController@showlist` | Not opened. |

### `admin/changeavatar`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/changeavatar` | `` | `Admin\UserProfileController@changeavatar` | Not opened. |

### `admin/changepassword`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/changepassword` | `` | `Admin\UserProfileController@ChangePassword` | Not opened. |

### `admin/class-stream`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/class-stream/{section}/create` | `admin.class-stream.create` | `Admin\ClassStreamController@create` | Not opened. |

### `admin/class-teacher-invite`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/class-teacher-invite/{section}/create` | `admin.class-teacher-invite.create` | `Admin\ClassTeacherInviteController@create` | Not opened. |

### `admin/classes`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/classes` | `admin.classes.index` | `closure` | Not opened. |
| `/admin/classes/add` | `admin.classes.add` | `Admin\ClassesController@create` | Not opened. |
| `/admin/classes/create` | `admin.classes.create` | `Admin\SectionController@create` | Not opened. |
| `/admin/classes/{section}` | `admin.classes.show` | `closure` | Not opened. |

### `admin/classwall`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/classwall/page/add` | `` | `Admin\PagesController@create` | Not opened. |
| `/admin/classwall/page/delete/{id}` | `` | `Admin\PagesController@destroy` | GET deletes. Remove, do not redesign. |
| `/admin/classwall/page/edit/{id}` | `` | `Admin\PagesController@edit` | Not opened. |
| `/admin/classwall/page/editList/{id}` | `` | `Admin\PagesController@editList` | Not opened. |
| `/admin/classwall/page/show/{id}` | `` | `Admin\PagesController@show` | Not opened. |
| `/admin/classwall/pages` | `` | `Admin\PagesController@index` | Not opened. |
| `/admin/classwall/post/add` | `` | `Admin\PostAddController@create` | Not opened. |
| `/admin/classwall/post/delete/comment/{comment_id}` | `` | `Admin\PostCommentsController@destroy` | GET deletes. Remove, do not redesign. |
| `/admin/classwall/post/delete/{id}` | `` | `Admin\PostsController@destroy` | GET deletes. Remove, do not redesign. |
| `/admin/classwall/post/edit/commentList/{comment_id}` | `` | `Admin\PostCommentsController@editCommentList` | Not opened. |
| `/admin/classwall/post/edit/{id}` | `` | `Admin\PostEditController@edit` | Not opened. |
| `/admin/classwall/post/editList/{id}` | `` | `Admin\PostEditController@editList` | Not opened. |
| `/admin/classwall/post/reply/delete/comment/{post_comment_id}` | `` | `Admin\PostReplyCommentsController@destroy` | GET deletes. Remove, do not redesign. |
| `/admin/classwall/post/replyComment/{post_comment_id}` | `` | `Admin\PostReplyCommentsController@list` | Not opened. |
| `/admin/classwall/post/show/{id}` | `` | `Admin\PostsController@show` | Not opened. |
| `/admin/classwall/posts` | `` | `Admin\PostsController@index` | Not opened. |

### `admin/credentials`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/credentials/get/{name}` | `` | `Admin\UserProfileController@getCredentials` | Not opened. |

### `admin/dashboard`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/dashboard` | `dashboard` | `Admin\DashboardController@index` | Not opened. |
| `/admin/dashboard/birthday` | `` | `Admin\BirthdayController@birthday` | Not opened. |
| `/admin/dashboard/birthday/teacher` | `` | `Admin\BirthdayController@birthdayCreate` | Not opened. |
| `/admin/dashboard/birthdayTeacher` | `` | `Admin\BirthdayController@birthdayTeacher` | Not opened. |
| `/admin/dashboard/birthdayUser` | `` | `Admin\BirthdayController@birthdayUser` | Not opened. |
| `/admin/dashboard/event` | `` | `Admin\DashboardController@event` | Not opened. |
| `/admin/dashboard/feeslist/{fee_id}` | `` | `Admin\DashboardController@feeslist` | Not opened. |
| `/admin/dashboard/showBirthday` | `` | `Admin\BirthdayController@showBirthday` | Not opened. |
| `/admin/dashboard/showBirthdayTeacher` | `` | `Admin\BirthdayController@showBirthdayTeacher` | Not opened. |
| `/admin/dashboard/showWorkAnniversary` | `` | `Admin\BirthdayController@showWorkAnniversary` | Not opened. |
| `/admin/dashboard/tasklist/{task_flag}` | `` | `Admin\DashboardController@list` | Not opened. |
| `/admin/dashboard/unpaidList/{fee_id}` | `` | `Admin\DashboardController@unpaidList` | Not opened. |
| `/admin/dashboard/unpaidfees/{fee_id}` | `` | `Admin\DashboardController@show` | Not opened. |
| `/admin/dashboard/workAnniversary` | `` | `Admin\BirthdayController@workAnniversaryCreate` | Not opened. |

### `admin/discipline`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/discipline/add` | `` | `Admin\DisciplineController@create` | Not opened. |
| `/admin/discipline/delete/{id}` | `` | `Admin\DisciplineController@destroy` | GET deletes. Remove, do not redesign. |
| `/admin/discipline/edit/{id}` | `` | `Admin\DisciplineController@edit` | Not opened. |
| `/admin/discipline/show/{id}` | `` | `Admin\DisciplineController@show` | Not opened. |

### `admin/disciplines`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/disciplines` | `` | `Admin\DisciplineController@index` | Not opened. |

### `admin/display`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/display/photos/{event_id}` | `` | `Admin\EventsController@showimage` | Not opened. |

### `admin/document`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/document/delete/{id}` | `` | `Admin\DocumentsController@destroy` | GET deletes. Remove, do not redesign. |
| `/admin/document/get/{name}` | `` | `Admin\DocumentsController@index` | Not opened. |

### `admin/downloadformat`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/downloadformat` | `` | `Admin\ImportMemberController@downloadFormat` | Not opened. |
| `/admin/downloadformat/teacher` | `` | `Admin\TeacherImportExportController@downloadFormat` | Not opened. |

### `admin/editprofile`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/editprofile` | `` | `Admin\UserProfileController@edit` | Not opened. |

### `admin/emergency`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/emergency` | `` | `Admin\SendEmergencyMessageController@create` | Not opened. |

### `admin/event`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/event/approve/{id}` | `` | `Admin\EventsController@eventapprove` | Not opened. |

### `admin/events`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/events` | `` | `Admin\EventsController@index` | Not opened. |
| `/admin/events/delete/{id}` | `` | `Admin\EventsController@destroy` | GET deletes. Remove, do not redesign. |
| `/admin/events/details/{id}` | `` | `Admin\EventsController@details` | Not opened. |
| `/admin/events/edit/{id}` | `` | `Admin\EventsController@edit` | Not opened. |
| `/admin/events/show` | `` | `Admin\EventsController@events` | Not opened. |
| `/admin/events/show/details/{id}` | `` | `Admin\EventsController@show` | Not opened. |
| `/admin/events/showdetails/{id}` | `` | `Admin\EventsController@showdetails` | Not opened. |

### `admin/exams`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/exams` | `admin.exams` | `Admin\ExamController@index` | Not opened. |
| `/admin/exams/add-new` | `admin.exams.create` | `Admin\ExamController@create` | Not opened. |
| `/admin/exams/{exam}/edit` | `admin.exams.edit` | `Admin\ExamController@edit` | Not opened. |
| `/admin/exams/{exam}/marks/import` | `admin.exams.marks.import.page` | `Admin\MarksImportController@importPage` | Not opened. |
| `/admin/exams/{exam}/marks/import/result` | `admin.exams.marks.import.result` | `Admin\MarksImportController@importResult` | Not opened. |
| `/admin/exams/{exam}/marks/import/template` | `admin.exams.marks.import.template` | `Admin\MarksImportController@importTemplate` | Not opened. |
| `/admin/exams/{exam}/marksheet` | `admin.exams.marksheet` | `Admin\ExamController@marksheet` | Not opened. |

### `admin/export`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/export` | `` | `Admin\ExportMemberController@index` | Not opened. |

### `admin/exportTeachers`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/exportTeachers` | `` | `Admin\TeacherImportExportController@export` | Not opened. |

### `admin/exportUsers`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/exportUsers` | `` | `Admin\ExportMemberController@exportUsers` | Not opened. |

### `admin/feed`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/feed` | `` | `Admin\FeedController@create` | Not opened. |
| `/admin/feed/filter` | `` | `Admin\FeedController@filter` | Not opened. |

### `admin/feedback`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/feedback/edit/{feedbackid}` | `` | `Admin\FeedbackController@edit` | Not opened. |

### `admin/feedbacks`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/feedbacks` | `` | `Admin\FeedbackController@index` | Not opened. |

### `admin/feeds`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/feeds` | `` | `Admin\FeedController@index` | Not opened. |

### `admin/fees`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/fees/payments` | `admin.fee-payments` | `Admin\FeePaymentController@index` | Not opened. |
| `/admin/fees/payments/create` | `admin.fee-payments.create` | `Admin\FeePaymentController@create` | Not opened. |
| `/admin/fees/payments/unmatched` | `admin.fee-payments.unmatched` | `Admin\FeePaymentController@unmatched` | Not opened. |

### `admin/fees-categories`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/fees-categories` | `admin.fees-categories` | `Admin\Academics\FeesCategoryController@index` | Not opened. |
| `/admin/fees-categories/create` | `admin.fees-categories.create` | `Admin\Academics\FeesCategoryController@create` | Not opened. |
| `/admin/fees-categories/{fee}/edit` | `admin.fees-categories.edit` | `Admin\Academics\FeesCategoryController@edit` | Not opened. |

### `admin/getStandard`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/getStandard` | `` | `Admin\StandardsLinkController@getStandard` | Not opened. |

### `admin/getavatar`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/getavatar` | `` | `Admin\UserProfileController@getavatar` | Not opened. |

### `admin/getphoto`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/getphoto/{event_id}` | `` | `Admin\EventGalleryController@getPhoto` | Not opened. |

### `admin/grades`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/grades` | `admin.grades` | `Admin\Academics\SchoolGradingSystemController@index` | Not opened. |
| `/admin/grades/create` | `admin.grades.create` | `Admin\Academics\SchoolGradingSystemController@create` | Not opened. |
| `/admin/grades/{grade}/edit` | `admin.grades.edit` | `Admin\Academics\SchoolGradingSystemController@edit` | Not opened. |

### `admin/health`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/health` | `admin.health` | `Admin\StudentHealthOverviewController@index` | Not opened. |

### `admin/holiday`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/holiday/add` | `` | `Admin\HolidaysController@create` | Not opened. |
| `/admin/holiday/delete/{id}` | `` | `Admin\HolidaysController@destroy` | GET deletes. Remove, do not redesign. |
| `/admin/holiday/edit/{id}` | `` | `Admin\HolidaysController@edit` | Not opened. |

### `admin/holidays`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/holidays` | `` | `Admin\HolidaysController@index` | Not opened. |

### `admin/homework`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/homework/add` | `` | `Admin\Approval\HomeWorkController@create` | Not opened. |
| `/admin/homework/delete/{id}` | `` | `Admin\Approval\HomeWorkController@destroy` | GET deletes. Remove, do not redesign. |
| `/admin/homework/edit/{id}` | `` | `Admin\Approval\HomeWorkController@edit` | Not opened. |
| `/admin/homework/show/{id}` | `` | `Admin\Approval\HomeWorkController@show` | Not opened. |
| `/admin/homework/viewers/{id}` | `` | `Admin\Approval\HomeWorkController@view` | Not opened. |

### `admin/homeworks`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/homeworks` | `` | `Admin\Approval\HomeWorkController@index` | Not opened. |

### `admin/import`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/import` | `` | `Admin\ImportMemberController@index` | Not opened. |
| `/admin/import/teacher` | `` | `Admin\TeacherImportExportController@importCreate` | Not opened. |

### `admin/leavetype`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/leavetype/add` | `` | `Admin\LeaveTypesController@create` | Not opened. |
| `/admin/leavetype/delete/{id}` | `` | `Admin\LeaveTypesController@destroy` | GET deletes. Remove, do not redesign. |
| `/admin/leavetype/edit/{id}` | `` | `Admin\LeaveTypesController@edit` | Not opened. |

### `admin/leavetypes`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/leavetypes` | `` | `Admin\LeaveTypesController@index` | Not opened. |

### `admin/library`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/library/books` | `admin.library.books` | `Admin\LibraryController@bookIndex` | Not opened. |
| `/admin/library/books/create` | `admin.library.books.create` | `Admin\LibraryController@bookCreate` | Not opened. |
| `/admin/library/books/{id}/edit` | `admin.library.books.edit` | `Admin\LibraryController@bookEdit` | Not opened. |
| `/admin/library/cards` | `admin.library.cards` | `Admin\LibraryController@cardIndex` | Not opened. |
| `/admin/library/lends` | `admin.library.lends` | `Admin\LibraryController@lendIndex` | Not opened. |
| `/admin/library/lends/create` | `admin.library.lends.create` | `Admin\LibraryController@lendCreate` | Not opened. |

### `admin/magazine`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/magazine/create` | `` | `Admin\BulletinsController@create` | Not opened. |
| `/admin/magazine/delete/{id}` | `` | `Admin\BulletinsController@destroy` | GET deletes. Remove, do not redesign. |
| `/admin/magazine/download/{id}` | `` | `Admin\BulletinsController@downloadattachments` | Not opened. |
| `/admin/magazine/getDate` | `` | `Admin\BulletinsController@getDate` | Not opened. |

### `admin/magazines`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/magazines` | `` | `Admin\BulletinsController@index` | Not opened. |

### `admin/marks`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/marks` | `admin.marks` | `Admin\FilterMarksForm@filterForm` | Not opened. |
| `/admin/marks/classes/{section?}` | `admin.marks.classes` | `Admin\ClassMarksController@show` | Not opened. |
| `/admin/marks/download` | `admin.marksheet.download` | `Admin\DownloadMarksSheet@download` | Not opened. |
| `/admin/marks/filter` | `admin.marks.filter` | `Admin\MarksController@classExamOverview` | Not opened. |
| `/admin/marks/releases` | `admin.marks.releases.index` | `MarksReleaseController@index` | Not opened. |
| `/admin/marks/releases/new` | `admin.marks.releases.create` | `MarksReleaseController@create` | Not opened. |
| `/admin/marks/releases/{release}` | `admin.marks.releases.show` | `MarksReleaseController@show` | Not opened. |
| `/admin/marks/student/{user}/class/{class}/{exam}` | `admin.marks.student.class` | `Admin\GetStudentsMarks@GetStudentMarks` | Not opened. |
| `/admin/marks/submissions` | `admin.marks.submissions` | `Admin\ExamMarksSubmissionController@index` | Not opened. |
| `/admin/marks/submissions/{exam}` | `admin.marks.submissions.show` | `Admin\ExamMarksSubmissionController@show` | Not opened. |

### `admin/messages`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/messages` | `admin.messages` | `closure` | Not opened. |

### `admin/notes`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/notes` | `` | `Admin\NotesController@create` | Not opened. |
| `/admin/notes/delete/{id}` | `` | `Admin\NotesController@delete` | GET deletes. Remove, do not redesign. |
| `/admin/notes/edit/{id}` | `` | `Admin\NotesController@edit` | Not opened. |

### `admin/notice`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/notice/add` | `` | `Admin\NoticeBoardController@create` | Not opened. |
| `/admin/notice/delete/{id}` | `` | `Admin\NoticeBoardController@destroy` | GET deletes. Remove, do not redesign. |
| `/admin/notice/edit/{id}` | `` | `Admin\NoticeBoardController@edit` | Not opened. |
| `/admin/notice/show/{id}` | `` | `Admin\NoticeBoardController@show` | Not opened. |

### `admin/notices`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/notices` | `` | `Admin\NoticeBoardController@index` | Not opened. |

### `admin/notifications`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/notifications` | `` | `Admin\NotificationController@index` | Not opened. |

### `admin/onboarding`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/onboarding/wizard` | `admin.onboarding.wizard` | `closure` | Not opened. |

### `admin/parent`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/parent/add` | `` | `Admin\ParentController@create` | Not opened. |
| `/admin/parent/edit/{name}` | `` | `Admin\ParentController@edit` | Not opened. |
| `/admin/parent/get` | `` | `Admin\ParentController@addList` | Not opened. |
| `/admin/parent/show/activity/{name}` | `` | `Admin\ParentController@showActivityLog` | Not opened. |
| `/admin/parent/show/children/{name}` | `` | `Admin\ParentController@showChildren` | Not opened. |
| `/admin/parent/show/feedback/{name}` | `` | `Admin\ParentController@showFeedbacks` | Not opened. |
| `/admin/parent/show/{name}` | `` | `Admin\ParentController@show` | Not opened. |

### `admin/parents`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/parents` | `` | `Admin\ParentController@index` | Not opened. |

### `admin/payment`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/payment/index/{id}` | `` | `Admin\PaymentController@index` | Not opened. |
| `/admin/payment/subscription` | `` | `Admin\PaymentController@Subscription` | Not opened. |

### `admin/phonenumber`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/phonenumber/add` | `` | `Admin\TelephoneDirectoryController@create` | Not opened. |
| `/admin/phonenumber/delete/{id}` | `` | `Admin\TelephoneDirectoryController@destroy` | GET deletes. Remove, do not redesign. |
| `/admin/phonenumber/edit/{id}` | `` | `Admin\TelephoneDirectoryController@edit` | Not opened. |
| `/admin/phonenumber/show/{id}` | `` | `Admin\TelephoneDirectoryController@show` | Not opened. |

### `admin/phonenumbers`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/phonenumbers` | `` | `Admin\TelephoneDirectoryController@index` | Not opened. |

### `admin/postalrecord`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/postalrecord` | `` | `Admin\PostalRecordController@index` | Not opened. |
| `/admin/postalrecord/add` | `` | `Admin\PostalRecordController@create` | Not opened. |
| `/admin/postalrecord/delete/{id}` | `` | `Admin\PostalRecordController@destroy` | GET deletes. Remove, do not redesign. |
| `/admin/postalrecord/edit/{id}` | `` | `Admin\PostalRecordController@edit` | Not opened. |
| `/admin/postalrecord/show/{id}` | `` | `Admin\PostalRecordController@show` | Not opened. |
| `/admin/postalrecord/showlist` | `` | `Admin\PostalRecordController@showlist` | Not opened. |

### `admin/profile`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/profile` | `` | `Admin\UserProfileController@create` | Not opened. |

### `admin/promotion`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/promotion/create` | `` | `Admin\PromotionController@create` | Not opened. |

### `admin/report`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/report/activeStudents` | `` | `Admin\ReportsController@exportActiveStudents` | Not opened. |
| `/admin/report/birthday/{type}` | `` | `Admin\ReportsController@exportBirthday` | Not opened. |
| `/admin/report/currentstock` | `` | `Admin\ReportsController@currentstock` | Not opened. |
| `/admin/report/downloadholidayformat` | `` | `Admin\ReportsController@holidayFormat` | Not opened. |
| `/admin/report/events` | `` | `Admin\ReportsController@eventReport` | Not opened. |
| `/admin/report/exitStudents` | `` | `Admin\ReportsController@exportExitStudents` | Not opened. |
| `/admin/report/exportHolidays` | `` | `Admin\ReportsController@holidayExport` | Not opened. |
| `/admin/report/fees` | `` | `Admin\ReportsController@exportFee` | Not opened. |
| `/admin/report/holidays` | `` | `Admin\ReportsController@holidayCreate` | Not opened. |
| `/admin/report/monthlypurchase` | `` | `Admin\ReportsController@monthlypurchase` | Not opened. |
| `/admin/report/monthlysales` | `` | `Admin\ReportsController@monthlysales` | Not opened. |
| `/admin/report/parents` | `` | `Admin\ReportsController@exportParent` | Not opened. |
| `/admin/report/student/{learner}/class/{class}/{exam}` | `admin.report.student.class` | `Admin\GetStudentsMarks@redirectLegacyReport` | Not opened. |
| `/admin/report/suspendedStudents` | `` | `Admin\ReportsController@exportSuspendedStudents` | Not opened. |

### `admin/reports`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/reports` | `` | `Admin\ReportsController@report` | Not opened. |
| `/admin/reports/cards` | `admin.reports.cards.index` | `Admin\ReportCardsController@index` | Not opened. |
| `/admin/reports/cards/combined-marksheet/{stdLink}` | `admin.reports.cards.combinedMarksheet` | `Admin\ReportCardsController@combinedMarksheet` | Not opened. |
| `/admin/reports/cards/download/{stdLink}` | `admin.reports.cards.download` | `Admin\ReportCardsController@downloadClass` | Not opened. |
| `/admin/reports/cards/generation/{generation}/download` | `admin.reports.cards.generation.download` | `Admin\ReportCardsController@downloadGeneration` | Not opened. |
| `/admin/reports/cards/merged/{stdLink}` | `admin.reports.cards.merged` | `Admin\ReportCardsController@downloadMerged` | Not opened. |
| `/admin/reports/cards/missing` | `admin.reports.cards.missing` | `Admin\ReportCardsController@missingMarksPdf` | Not opened. |
| `/admin/reports/cards/preview/{template}` | `admin.reports.cards.preview` | `Admin\ReportCardsController@previewTemplate` | Not opened. |
| `/admin/reports/cards/{stdLink}/student/{learner}/download` | `admin.reports.cards.student.download` | `Admin\ReportCardsController@downloadStudent` | Not opened. |
| `/admin/reports/cards/{stdLink}/student/{learner}/preview` | `admin.reports.cards.student.preview` | `Admin\ReportCardsController@previewStudent` | Not opened. |

### `admin/school-profile`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/school-profile` | `admin.school-profile` | `closure` | Not opened. |

### `admin/schooldetails`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/schooldetails` | `` | `Admin\SchoolDetailsController@index` | Not opened. |
| `/admin/schooldetails/edit/{school_id}` | `` | `Admin\SchoolDetailsController@edit` | Not opened. |
| `/admin/schooldetails/editdetail/{school_id}` | `` | `Admin\SchoolDetailsController@editdetail` | Not opened. |

### `admin/sections`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/sections` | `admin.classes` | `Admin\SectionController@index` | Not opened. |

### `admin/sentmessages`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/sentmessages` | `` | `Admin\SendMessageController@index` | Not opened. |

### `admin/settings`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/settings` | `` | `closure` | Not opened. |
| `/admin/settings/exam-types` | `admin.settings.exam-types` | `Admin\Setting\ExamTypeController@index` | Not opened. |
| `/admin/settings/generalsettings` | `` | `Admin\Setting\GeneralController@create` | Not opened. |
| `/admin/settings/integrations` | `admin.settings.integrations` | `Admin\Setting\IntegrationsController@index` | Not opened. |
| `/admin/settings/maintenancesettings` | `` | `Admin\Setting\MaintenanceController@create` | Not opened. |
| `/admin/settings/seodetailsettings` | `` | `Admin\Setting\SeoDetailController@create` | Not opened. |

### `admin/staff`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/staff/add` | `` | `Admin\StaffController@create` | Not opened. |
| `/admin/staff/edit/{name}` | `` | `Admin\StaffController@edit` | Not opened. |
| `/admin/staff/export` | `` | `Admin\StaffController@staffexports` | Not opened. |
| `/admin/staff/show/{name}` | `` | `Admin\StaffController@show` | Not opened. |

### `admin/staffs`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/staffs` | `` | `Admin\StaffController@index` | Not opened. |
| `/admin/staffs/find` | `` | `Admin\StaffController@find` | Not opened. |

### `admin/standard`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/standard/create` | `` | `Admin\StandardController@create` | Not opened. |

### `admin/standardLink`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/standardLink/add` | `` | `Admin\StandardsLinkController@create` | Not opened. |
| `/admin/standardLink/delete/{id}` | `` | `Admin\StandardsLinkController@destroy` | GET deletes. Remove, do not redesign. |
| `/admin/standardLink/edit/{id}` | `` | `Admin\StandardsLinkController@edit` | Not opened. |
| `/admin/standardLink/id-card-print/{id}` | `` | `Admin\StandardsLinkController@printidcard` | Not opened. |
| `/admin/standardLink/id-card/{id}` | `` | `Admin\StandardsLinkController@idcard` | Not opened. |
| `/admin/standardLink/show/attendances/{id}` | `` | `Admin\StandardsLinkDetailsController@getAttendance` | Not opened. |
| `/admin/standardLink/show/classwall/{id}` | `` | `Admin\StandardsLinkDetailsController@showClassWall` | Not opened. |
| `/admin/standardLink/show/comments/{post_id}` | `` | `Admin\StandardsLinkDetailsController@showComments` | Not opened. |
| `/admin/standardLink/show/conference/{id}` | `` | `Admin\StandardsLinkDetailsController@showConference` | Not opened. |
| `/admin/standardLink/show/events/{id}` | `` | `Admin\StandardsLinkDetailsController@showEvents` | Not opened. |
| `/admin/standardLink/show/fees/{id}` | `` | `Admin\StandardsLinkDetailsController@showFees` | Not opened. |
| `/admin/standardLink/show/pastExams/{id}` | `` | `Admin\StandardsLinkDetailsController@showPastExams` | Not opened. |
| `/admin/standardLink/show/student/attendances/{id}` | `` | `Admin\StandardsLinkDetailsController@getStudentAttendance` | Not opened. |
| `/admin/standardLink/show/students/{id}` | `` | `Admin\StandardsLinkDetailsController@showStudents` | Not opened. |
| `/admin/standardLink/show/teachers/{id}` | `` | `Admin\StandardsLinkDetailsController@showTeachers` | Not opened. |
| `/admin/standardLink/show/timetable/{id}` | `` | `Admin\StandardsLinkDetailsController@showTimetable` | Not opened. |
| `/admin/standardLink/show/upcomingExams/{id}` | `` | `Admin\StandardsLinkDetailsController@showUpcomingExams` | Not opened. |
| `/admin/standardLink/show/{id}` | `` | `Admin\StandardsLinkDetailsController@show` | Not opened. |

### `admin/standardlinks`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/standardlinks` | `` | `Admin\StandardsLinkController@index` | Not opened. |

### `admin/standards`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/standards` | `` | `Admin\StandardController@index` | Not opened. |

### `admin/student`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/student` | `` | `Admin\StudentController@member` | Not opened. |
| `/admin/student/add` | `` | `Admin\StudentController@create` | Not opened. |
| `/admin/student/add/medicalHistory/{name}` | `` | `Admin\StudentDetailsController@createMedicalHistory` | Not opened. |
| `/admin/student/edit/{name}` | `` | `Admin\StudentController@edit` | Not opened. |
| `/admin/student/editStudent/{name}` | `` | `Admin\StudentController@editStudent` | Not opened. |
| `/admin/student/export` | `` | `Admin\ExportMemberController@studentexports` | Not opened. |
| `/admin/student/health/{userId}` | `` | `Admin\StudentHealthController@index` | Not opened. |
| `/admin/student/pdf` | `` | `Admin\ExportMemberController@exportpdfs` | Not opened. |
| `/admin/student/show/activity/{name}` | `` | `Admin\StudentDetailsController@showActivity` | Not opened. |
| `/admin/student/show/attendance/{name}` | `` | `Admin\StudentDetailsController@showAttendance` | Not opened. |
| `/admin/student/show/details/{name}` | `` | `Admin\StudentDetailsController@showDetails` | Not opened. |
| `/admin/student/show/discipline/{name}` | `` | `Admin\StudentDetailsController@showDisciplines` | Not opened. |
| `/admin/student/show/fees/{name}` | `` | `Admin\StudentDetailsController@showFees` | Not opened. |
| `/admin/student/show/libraryactivity/{name}` | `` | `Admin\StudentDetailsController@showBookLent` | Not opened. |
| `/admin/student/show/medicalHistory/{name}` | `` | `Admin\StudentDetailsController@showMedicalHistory` | Not opened. |
| `/admin/student/show/relations/{name}` | `` | `Admin\StudentDetailsController@showRelations` | Not opened. |
| `/admin/student/show/siblings/{name}` | `` | `Admin\StudentDetailsController@showSiblings` | Not opened. |
| `/admin/student/show/{name}` | `admin.student.show` | `Admin\StudentDetailsController@redirectToIdProfile` | Not opened. |

### `admin/studenthomework`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/studenthomework/show/{id}` | `` | `Admin\StudentHomeworkController@show` | Not opened. |

### `admin/studenthomeworks`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/studenthomeworks/{id}` | `` | `Admin\StudentHomeworkController@list` | Not opened. |

### `admin/students`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/students` | `` | `Admin\StudentController@index` | Not opened. |
| `/admin/students/blockedstudents` | `` | `Admin\StudentController@blockedstudents` | Not opened. |
| `/admin/students/find` | `` | `Admin\StudentController@find` | Not opened. |
| `/admin/students/promotions` | `students.promotion` | `Admin\StudentPromotionRuleController@index` | Not opened. |
| `/admin/students/promotions/create` | `students.promotion.create` | `Admin\StudentPromotionRuleController@create` | Not opened. |
| `/admin/students/promotions/edit/{rule}` | `students.promotion.edit` | `Admin\StudentPromotionRuleController@edit` | Not opened. |
| `/admin/students/upload-template` | `admin.students.upload-template` | `Admin\StudentUploadTemplateController@download` | Not opened. |
| `/admin/students/{student}` | `admin.students.profile` | `Admin\StudentProfileController@show` | Not opened. |

### `admin/subjects`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/subjects` | `admin.subjects` | `Admin\UgSubjectController@index` | Not opened. |
| `/admin/subjects/add-new` | `` | `Admin\UgSubjectController@create` | Not opened. |
| `/admin/subjects/{subject}/edit` | `admin.subjects.edit` | `Admin\UgSubjectController@edit` | Not opened. |

### `admin/subscriptions`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/subscriptions` | `admin.subscriptions.index` | `Admin\SubscriptionController@index` | Not opened. |
| `/admin/subscriptions/submit` | `admin.subscriptions.create` | `Admin\SubscriptionController@create` | Not opened. |
| `/admin/subscriptions/{subscription}` | `admin.subscriptions.edit` | `Admin\SubscriptionController@edit` | Not opened. |

### `admin/task`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/task/add` | `` | `Admin\TaskController@create` | Not opened. |
| `/admin/task/edit/{id}` | `` | `Admin\TaskController@edit` | Not opened. |
| `/admin/task/show/{id}` | `` | `Admin\TaskController@show` | Not opened. |
| `/admin/task/{id}/delete` | `` | `Admin\TaskController@destroy` | GET deletes. Remove, do not redesign. |

### `admin/tasks`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/tasks` | `` | `Admin\TaskController@index` | Not opened. |

### `admin/teacher`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/teacher` | `` | `Admin\TeacherAddController@member` | Not opened. |
| `/admin/teacher/add` | `` | `Admin\TeacherAddController@create` | Not opened. |
| `/admin/teacher/attendances/show/{name}` | `` | `Admin\StaffAttendanceController@getStudentAttendance` | Not opened. |
| `/admin/teacher/edit/{name}` | `` | `Admin\TeacherEditController@edit` | Not opened. |
| `/admin/teacher/editTeacher/{name}` | `` | `Admin\TeacherEditController@editTeacher` | Not opened. |
| `/admin/teacher/export` | `` | `Admin\TeacherImportExportController@teacherexports` | Not opened. |
| `/admin/teacher/my-timetable` | `admin.timetable.teacher` | `Admin\TimetableSlotController@teacherWeekly` | Not opened. |
| `/admin/teacher/show/activity/{name}` | `` | `Admin\TeacherShowController@showActivity` | Not opened. |
| `/admin/teacher/show/attendance/{name}` | `` | `Admin\StaffAttendanceController@showAttendance` | Not opened. |
| `/admin/teacher/show/classes/{name}` | `` | `Admin\TeacherShowController@showClasses` | Not opened. |
| `/admin/teacher/show/classteacher/{name}` | `` | `Admin\TeacherShowController@showClassTeacher` | Not opened. |
| `/admin/teacher/show/details/{name}` | `` | `Admin\TeacherShowController@showDetails` | Not opened. |
| `/admin/teacher/show/leave/{name}` | `` | `Admin\TeacherShowController@showLeaveHistory` | Not opened. |
| `/admin/teacher/show/logactivity/{name}` | `` | `Admin\TeacherShowController@showActivityLog` | Not opened. |
| `/admin/teacher/show/timetable/{name}` | `` | `Admin\TeacherShowController@showTimetable` | Not opened. |
| `/admin/teacher/show/{name}` | `` | `Admin\TeacherProfileController@show` | Not opened. |

### `admin/teacher-links`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/teacher-links` | `teacher-links.index` | `Admin\TeacherLinkImportController@index` | Not opened. |

### `admin/teachers`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/teachers` | `` | `Admin\TeacherListController@index` | Not opened. |
| `/admin/teachers/find` | `` | `Admin\TeacherListController@find` | Not opened. |

### `admin/timetable`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/timetable` | `admin.timetable.index` | `Admin\TimetableSlotController@index` | Not opened. |
| `/admin/timetable/slots` | `` | `Admin\TimetableSlotController@index` | Not opened. |
| `/admin/timetable/slots/create` | `admin.timetable.create` | `Admin\TimetableSlotController@create` | Not opened. |
| `/admin/timetable/slots/{slot}/edit` | `admin.timetable.edit` | `Admin\TimetableSlotController@edit` | Not opened. |

### `admin/toshi-activity`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/toshi-activity` | `admin.toshi.activity` | `Admin\ToshiActivityController@index` | Not opened. |

### `admin/transport`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/transport` | `admin.transport` | `Admin\TransportController@index` | Not opened. |
| `/admin/transport/create` | `admin.transport.create` | `Admin\TransportController@create` | Not opened. |
| `/admin/transport/{id}/edit` | `admin.transport.edit` | `Admin\TransportController@edit` | Not opened. |

### `admin/user`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/user/resetPassword/{id}` | `` | `Admin\UserController@resetPassword` | Not opened. |
| `/admin/user/{id}/verificationcode` | `` | `Admin\UserController@emailVerification` | Not opened. |

### `admin/visitorlog`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/visitorlog` | `` | `Admin\VisitorLogController@index` | Not opened. |
| `/admin/visitorlog/add` | `` | `Admin\VisitorLogController@create` | Not opened. |
| `/admin/visitorlog/delete/{id}` | `` | `Admin\VisitorLogController@destroy` | GET deletes. Remove, do not redesign. |
| `/admin/visitorlog/edit/{id}` | `` | `Admin\VisitorLogController@edit` | Not opened. |
| `/admin/visitorlog/print/{id}` | `` | `Admin\VisitorLogController@print` | Not opened. |
| `/admin/visitorlog/show/{id}` | `` | `Admin\VisitorLogController@show` | Not opened. |
| `/admin/visitorlog/showlist` | `` | `Admin\VisitorLogController@showlist` | Not opened. |
| `/admin/visitorlog/view/{id}` | `` | `Admin\VisitorLogController@view` | Not opened. |

### `admin/whatsapp`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/admin/whatsapp/dashboard` | `whatsapp.dashboard` | `Admin\WhatsAppDashboardController@index` | Not opened. |
| `/admin/whatsapp/parents` | `whatsapp.parents` | `Admin\WhatsAppParentController@index` | Not opened. |
| `/admin/whatsapp/phone` | `whatsapp.phone` | `Admin\UserProfileController@phoneLink` | Not opened. |

### `parent/children`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/parent/children` | `parent.children` | `Parent\ChildrenController@index` | Not opened. |
| `/parent/children/{student}/attendance` | `parent.children.attendance` | `Parent\ChildDataController@attendance` | Not opened. |
| `/parent/children/{student}/fees` | `parent.children.fees` | `Parent\ChildDataController@fees` | Not opened. |
| `/parent/children/{student}/grades` | `parent.children.grades` | `Parent\ChildDataController@grades` | Not opened. |

### `parent/dashboard`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/parent/dashboard` | `parent.dashboard` | `Parent\DashboardController@index` | Not opened. |

### `parent/magic-login`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/parent/magic-login/{user}/{nonce}` | `parent.magic-login` | `Auth\ParentMagicLoginController@show` | Not opened. |

### `parent/notifications`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/parent/notifications` | `` | `Parent\NotificationController@index` | Not opened. |

### `platform/early-access`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/platform/early-access` | `platform.early-access.index` | `Platform\EarlyAccessRequestController@index` | Not opened. |
| `/platform/early-access/export` | `platform.early-access.export` | `Platform\EarlyAccessRequestController@export` | Not opened. |

### `student/activity`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/student/activity` | `` | `Student\ActivityLogController@index` | Not opened. |

### `student/assignment`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/student/assignment/delete/{id}` | `` | `Student\AssignmentController@destroy` | GET deletes. Remove, do not redesign. |

### `student/assignments`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/student/assignments` | `` | `Student\AssignmentController@index` | Not opened. |

### `student/attendance`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/student/attendance` | `student.attendance` | `Student\RecordsController@attendance` | Not opened. |

### `student/changepassword`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/student/changepassword` | `` | `Student\UserProfileController@ChangePassword` | Not opened. |

### `student/classwall`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/student/classwall/page/show/{id}` | `` | `Student\PagesController@show` | Not opened. |
| `/student/classwall/pages` | `` | `Student\PagesController@index` | Not opened. |
| `/student/classwall/post/delete/comment/{comment_id}` | `` | `Student\PostCommentsController@destroy` | GET deletes. Remove, do not redesign. |
| `/student/classwall/post/edit/commentList/{comment_id}` | `` | `Student\PostCommentsController@editCommentList` | Not opened. |
| `/student/classwall/post/reply/delete/comment/{post_comment_id}` | `` | `Student\PostReplyCommentsController@destroy` | GET deletes. Remove, do not redesign. |
| `/student/classwall/post/replyComment/{post_comment_id}` | `` | `Student\PostReplyCommentsController@list` | Not opened. |
| `/student/classwall/post/show/{id}` | `` | `Student\PostsController@show` | Not opened. |
| `/student/classwall/posts` | `` | `Student\PostsController@index` | Not opened. |

### `student/conversations`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/student/conversations` | `student.conversations.index` | `Student\ConversationController@index` | Not opened. |
| `/student/conversations/create` | `student.conversations.create` | `Student\ConversationController@create` | Not opened. |
| `/student/conversations/{conversation}` | `student.conversations.show` | `Student\ConversationController@show` | Not opened. |

### `student/dashboard`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/student/dashboard` | `student.dashboard` | `Student\DashboardController@index` | Not opened. |
| `/student/dashboard/tasklist/{task_flag}` | `` | `Student\DashboardController@list` | Not opened. |

### `student/events`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/student/events` | `` | `Student\EventsController@index` | Not opened. |
| `/student/events/details/{id}` | `` | `Student\EventsController@details` | Not opened. |
| `/student/events/show` | `` | `Student\EventsController@events` | Not opened. |
| `/student/events/showdetails/{id}` | `` | `Student\EventsController@showdetails` | Not opened. |

### `student/feeds`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/student/feeds` | `` | `Student\FeedController@index` | Not opened. |

### `student/holidays`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/student/holidays` | `` | `Student\HolidaysController@index` | Not opened. |

### `student/homework`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/student/homework/add` | `` | `Student\HomeworkController@create` | Not opened. |
| `/student/homework/delete/{id}` | `` | `Student\HomeworkController@destroy` | GET deletes. Remove, do not redesign. |
| `/student/homework/show/{id}` | `` | `Student\HomeworkController@show` | Not opened. |

### `student/homeworks`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/student/homeworks` | `` | `Student\HomeworkController@index` | Not opened. |

### `student/libraryactivity`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/student/libraryactivity` | `` | `Student\LibraryActivityController@index` | Not opened. |
| `/student/libraryactivity/show` | `` | `Student\LibraryActivityController@show` | Not opened. |

### `student/marks`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/student/marks` | `student.marks` | `Student\RecordsController@marks` | Not opened. |

### `student/notices`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/student/notices` | `` | `Student\NoticeBoardController@index` | Not opened. |

### `student/notifications`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/student/notifications` | `` | `Student\NotificationController@index` | Not opened. |

### `student/task`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/student/task/add` | `` | `Student\TaskController@create` | Not opened. |
| `/student/task/edit/{id}` | `` | `Student\TaskController@edit` | Not opened. |
| `/student/task/show/{id}` | `` | `Student\TaskController@show` | Not opened. |
| `/student/task/{id}/delete` | `` | `Student\TaskController@destroy` | GET deletes. Remove, do not redesign. |

### `student/tasks`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/student/tasks` | `` | `Student\TaskController@index` | Not opened. |

### `student/{id}`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/student/{id}/impersonate` | `` | `Auth\ImpersonateController@studentimpersonate` | Not opened. |

### `subadmin/dashboard`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/subadmin/dashboard` | `subadmin.dashboard` | `Admin\SubadminController@index` | Not opened. |

### `superadmin/academics`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/superadmin/academics/school/admin/create/{id}` | `superadmin.academics.school.createadmin` | `closure` | Not opened. |
| `/superadmin/academics/school/admin/detail/{id}` | `superadmin.academics.school.admindetail` | `closure` | Not opened. |
| `/superadmin/academics/school/admin/update/{id}` | `superadmin.academics.school.updateadmin` | `closure` | Not opened. |
| `/superadmin/academics/school/create` | `superadmin.academics.school.create` | `closure` | Not opened. |
| `/superadmin/academics/school/detail/{id}` | `superadmin.academics.school.detail` | `closure` | Not opened. |
| `/superadmin/academics/school/update/{id}` | `superadmin.academics.school.update` | `closure` | Not opened. |
| `/superadmin/academics/school/user/detail/{id}` | `superadmin.academics.school.userdetail` | `closure` | Not opened. |
| `/superadmin/academics/school/userprofile/create/{id}` | `superadmin.academics.school.createuserprofile` | `closure` | Not opened. |
| `/superadmin/academics/school/userprofile/detail/{id}` | `superadmin.academics.school.userprofiledetail` | `closure` | Not opened. |
| `/superadmin/academics/school/userprofile/update/{id}` | `superadmin.academics.school.updateuserprofile` | `closure` | Not opened. |
| `/superadmin/academics/schools` | `superadmin.academics.schoollist` | `closure` | Not opened. |

### `superadmin/changeavatar`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/superadmin/changeavatar` | `superadmin.changeavatar` | `closure` | Not opened. |

### `superadmin/changepassword`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/superadmin/changepassword` | `superadmin.changepassword` | `closure` | Not opened. |

### `superadmin/dashboard`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/superadmin/dashboard` | `superadmin.dashboard` | `Superadmin\DashboardSuperController@index` | Not opened. |

### `superadmin/mail-list`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/superadmin/mail-list` | `superadmin.mail-list` | `closure` | Not opened. |

### `superadmin/notifications`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/superadmin/notifications` | `` | `Superadmin\NotificationController@index` | Not opened. |

### `superadmin/reports`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/superadmin/reports` | `superadmin.reports.index` | `closure` | Not opened. |
| `/superadmin/reports/contact` | `superadmin.reports.contactlist` | `closure` | Not opened. |
| `/superadmin/reports/demo-requests` | `superadmin.reports.demorequests` | `closure` | Not opened. |
| `/superadmin/reports/subscription/create` | `superadmin.reports.subscription.create` | `closure` | Not opened. |
| `/superadmin/reports/subscription/detail/{id}` | `superadmin.reports.subscription.detail` | `closure` | Not opened. |
| `/superadmin/reports/subscription/update/{id}` | `superadmin.reports.subscription.update` | `closure` | Not opened. |
| `/superadmin/reports/subscriptions` | `superadmin.reports.subscriptionlist` | `closure` | Not opened. |

### `superadmin/setting`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/superadmin/setting/cities` | `superadmin.setting.cities` | `closure` | Not opened. |
| `/superadmin/setting/city/create` | `superadmin.setting.cities.create` | `closure` | Not opened. |
| `/superadmin/setting/city/detail/{id}` | `superadmin.setting.city.detail` | `closure` | Not opened. |
| `/superadmin/setting/city/update/{id}` | `superadmin.setting.cities.update` | `closure` | Not opened. |
| `/superadmin/setting/countries` | `superadmin.setting.countries` | `closure` | Not opened. |
| `/superadmin/setting/country/create` | `superadmin.setting.countries.create` | `closure` | Not opened. |
| `/superadmin/setting/country/detail/{id}` | `superadmin.setting.countries.detail` | `closure` | Not opened. |
| `/superadmin/setting/country/update/{id}` | `superadmin.setting.countries.update` | `closure` | Not opened. |
| `/superadmin/setting/plan/create` | `superadmin.setting.plan.create` | `closure` | Not opened. |
| `/superadmin/setting/plan/detail/{id}` | `superadmin.setting.plan.detail` | `closure` | Not opened. |
| `/superadmin/setting/plan/update/{id}` | `superadmin.setting.plan.update` | `closure` | Not opened. |
| `/superadmin/setting/plans` | `superadmin.setting.planlist` | `closure` | Not opened. |

### `superadmin/settings`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/superadmin/settings` | `superadmin.settings` | `closure` | Not opened. |
| `/superadmin/settings/co-admins` | `superadmin.settings.co-admins` | `closure` | Not opened. |
| `/superadmin/settings/emis` | `superadmin.settings.emis` | `closure` | Not opened. |
| `/superadmin/settings/features` | `superadmin.settings.features` | `closure` | Not opened. |
| `/superadmin/settings/locations` | `superadmin.settings.locations` | `closure` | Not opened. |
| `/superadmin/settings/system` | `superadmin.settings.system` | `closure` | Not opened. |

### `superadmin/toshi`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/superadmin/toshi/ops/{conversation}` | `superadmin.toshi.ops.show` | `Superadmin\PlatformOpsConversationController@show` | Not opened. |

### `teacher/activity`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/activity` | `` | `Teacher\ActivityLogController@index` | Not opened. |

### `teacher/assignment`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/assignment/add` | `` | `Teacher\Approval\AssignmentController@create` | Not opened. |
| `/teacher/assignment/delete/{id}` | `` | `Teacher\Approval\AssignmentController@destroy` | GET deletes. Remove, do not redesign. |
| `/teacher/assignment/edit/{id}` | `` | `Teacher\Approval\AssignmentController@edit` | Not opened. |
| `/teacher/assignment/show/{id}` | `` | `Teacher\StudentAssignmentController@index` | Not opened. |
| `/teacher/assignment/viewers/{id}` | `` | `Teacher\Approval\AssignmentController@view` | Not opened. |

### `teacher/assignments`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/assignments` | `` | `Teacher\Approval\AssignmentController@index` | Not opened. |

### `teacher/attendance`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/attendance` | `teacher.attendance.index` | `Teacher\AttendanceController@index` | Not opened. |
| `/teacher/attendance/add` | `teacher.attendance.create` | `Teacher\AttendanceController@create` | Not opened. |
| `/teacher/attendance/export/{standardLink_id}` | `` | `Teacher\AttendanceController@export` | Not opened. |

### `teacher/calllog`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/calllog` | `` | `Teacher\CallLogController@index` | Not opened. |
| `/teacher/calllog/add` | `` | `Teacher\CallLogController@create` | Not opened. |
| `/teacher/calllog/delete/{id}` | `` | `Teacher\CallLogController@destroy` | GET deletes. Remove, do not redesign. |
| `/teacher/calllog/edit/{id}` | `` | `Teacher\CallLogController@edit` | Not opened. |
| `/teacher/calllog/show/{id}` | `` | `Teacher\CallLogController@show` | Not opened. |
| `/teacher/calllog/showlist` | `` | `Teacher\CallLogController@showlist` | Not opened. |

### `teacher/changeavatar`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/changeavatar` | `` | `Teacher\UserProfileController@changeavatar` | Not opened. |

### `teacher/changepassword`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/changepassword` | `` | `Teacher\UserProfileController@ChangePassword` | Not opened. |

### `teacher/class-streams`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/class-streams` | `teacher.class-stream.index` | `Teacher\ClassStreamController@index` | Not opened. |
| `/teacher/class-streams/{section}/create` | `teacher.class-stream.create` | `Teacher\ClassStreamController@create` | Not opened. |
| `/teacher/class-streams/{section}/edit` | `teacher.class-stream.edit` | `Teacher\ClassStreamController@edit` | Not opened. |

### `teacher/classes`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/classes` | `teacher.classes.index` | `closure` | Not opened. |
| `/teacher/classes/{section}` | `teacher.classes.show` | `closure` | Not opened. |
| `/teacher/classes/{section}/manage` | `teacher.classes.manage` | `closure` | Not opened. |

### `teacher/classwall`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/classwall/page/show/{id}` | `` | `Teacher\PagesController@show` | Not opened. |
| `/teacher/classwall/pages` | `` | `Teacher\PagesController@index` | Not opened. |
| `/teacher/classwall/post/add` | `` | `Teacher\PostAddController@create` | Not opened. |
| `/teacher/classwall/post/delete/comment/{comment_id}` | `` | `Teacher\PostCommentsController@destroy` | GET deletes. Remove, do not redesign. |
| `/teacher/classwall/post/delete/{id}` | `` | `Teacher\PostsController@destroy` | GET deletes. Remove, do not redesign. |
| `/teacher/classwall/post/edit/commentList/{comment_id}` | `` | `Teacher\PostCommentsController@editCommentList` | Not opened. |
| `/teacher/classwall/post/edit/{id}` | `` | `Teacher\PostEditController@edit` | Not opened. |
| `/teacher/classwall/post/editList/{id}` | `` | `Teacher\PostEditController@editList` | Not opened. |
| `/teacher/classwall/post/reply/delete/comment/{post_comment_id}` | `` | `Teacher\PostReplyCommentsController@destroy` | GET deletes. Remove, do not redesign. |
| `/teacher/classwall/post/replyComment/{post_comment_id}` | `` | `Teacher\PostReplyCommentsController@list` | Not opened. |
| `/teacher/classwall/post/show/{id}` | `` | `Teacher\PostsController@show` | Not opened. |
| `/teacher/classwall/posts` | `` | `Teacher\PostsController@index` | Not opened. |

### `teacher/conversations`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/conversations` | `teacher.conversations.index` | `Teacher\ConversationController@index` | Not opened. |
| `/teacher/conversations/create` | `teacher.conversations.create` | `Teacher\ConversationController@create` | Not opened. |
| `/teacher/conversations/{conversation}` | `teacher.conversations.show` | `Teacher\ConversationController@show` | Not opened. |

### `teacher/dashboard`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/dashboard` | `teacher.dashboard` | `Teacher\DashboardController@index` | Not opened. |
| `/teacher/dashboard/tasklist/{task_flag}` | `` | `Teacher\DashboardController@list` | Not opened. |
| `/teacher/dashboard/timetable` | `` | `Teacher\DashboardController@timetable` | Not opened. |

### `teacher/document`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/document/get/{name}` | `` | `Teacher\StudentDetailsController@showDocuments` | Not opened. |

### `teacher/events`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/events` | `` | `Teacher\EventsController@index` | Not opened. |
| `/teacher/events/details/{id}` | `` | `Teacher\EventsController@details` | Not opened. |
| `/teacher/events/show` | `` | `Teacher\EventsController@events` | Not opened. |
| `/teacher/events/showdetails/{id}` | `` | `Teacher\EventsController@showdetails` | Not opened. |

### `teacher/exam`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/exam` | `teacher.exam.index` | `Illuminate\Routing\RedirectController` | Not opened. |
| `/teacher/exam/class/{stdLink}/combined-marksheet` | `teacher.exam.combinedMarksheet` | `Teacher\MarksController@combinedMarksheet` | Not opened. |
| `/teacher/exam/create` | `teacher.exams.create` | `Teacher\ExamController@create` | Not opened. |
| `/teacher/exam/marks` | `teacher.exam.marks` | `Teacher\MarksController@teacherExamMarksList` | Not opened. |
| `/teacher/exam/{exam}/edit` | `teacher.exams.edit` | `Teacher\ExamController@edit` | Not opened. |
| `/teacher/exam/{exam}/marks/enter` | `teacher.exam.marks.enter` | `Teacher\MarksController@enterExamMarks` | Not opened. |
| `/teacher/exam/{exam}/marks/import` | `teacher.exam.marks.import.page` | `Teacher\MarksImportController@importPage` | Not opened. |
| `/teacher/exam/{exam}/marks/import/result` | `teacher.exam.marks.import.result` | `Teacher\MarksImportController@importResult` | Not opened. |
| `/teacher/exam/{exam}/marks/template` | `teacher.exam.marks.template` | `Teacher\MarksController@downloadTemplate` | Not opened. |
| `/teacher/exam/{exam}/marks/view` | `teacher.exam.marks.view` | `Teacher\MarksController@viewExamMarks` | Not opened. |
| `/teacher/exam/{exam}/marksheet` | `teacher.exam.marksheet` | `Teacher\MarksController@downloadMarksheet` | Not opened. |
| `/teacher/exam/{exam}/students/{student}/mark/{marks}/edit` | `teacher.student.marks.edit` | `Teacher\MarksController@editMark` | Not opened. |

### `teacher/exams`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/exams` | `` | `Illuminate\Routing\RedirectController` | Not opened. |

### `teacher/feeds`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/feeds` | `` | `Teacher\FeedController@index` | Not opened. |

### `teacher/getavatar`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/getavatar` | `` | `Teacher\UserProfileController@getavatar` | Not opened. |

### `teacher/holidays`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/holidays` | `` | `Teacher\HolidaysController@index` | Not opened. |

### `teacher/homework`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/homework/add` | `` | `Teacher\Approval\HomeWorkController@create` | Not opened. |
| `/teacher/homework/delete/{id}` | `` | `Teacher\Approval\HomeWorkController@destroy` | GET deletes. Remove, do not redesign. |
| `/teacher/homework/edit/{id}` | `` | `Teacher\Approval\HomeWorkController@edit` | Not opened. |
| `/teacher/homework/show/{id}` | `` | `Teacher\Approval\HomeWorkController@show` | Not opened. |
| `/teacher/homework/viewers/{id}` | `` | `Teacher\Approval\HomeWorkController@view` | Not opened. |

### `teacher/homeworks`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/homeworks` | `` | `Teacher\Approval\HomeWorkController@index` | Not opened. |

### `teacher/impersonate`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/impersonate/stop` | `` | `Auth\ImpersonateController@stopImpersonate` | Not opened. |

### `teacher/leave`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/leave/add` | `` | `Teacher\LeaveController@create` | Not opened. |
| `/teacher/leave/approve/{id}` | `` | `Teacher\LeaveController@approveCreate` | Not opened. |
| `/teacher/leave/delete/{id}` | `` | `Teacher\LeaveController@destroy` | GET deletes. Remove, do not redesign. |
| `/teacher/leave/edit/{id}` | `` | `Teacher\LeaveController@edit` | Not opened. |
| `/teacher/leave/mylist` | `` | `Teacher\LeaveController@myList` | Not opened. |
| `/teacher/leave/pendingCount` | `` | `Teacher\LeaveController@pendingCount` | Not opened. |
| `/teacher/leave/reject/{id}` | `` | `Teacher\LeaveController@rejectCreate` | Not opened. |
| `/teacher/leave/show/{id}` | `` | `Teacher\LeaveController@view` | Not opened. |

### `teacher/leaves`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/leaves` | `` | `Teacher\LeaveController@index` | Not opened. |

### `teacher/lessonplan`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/lessonplan/add` | `` | `Teacher\LessonPlanAddController@create` | Not opened. |
| `/teacher/lessonplan/delete/{id}` | `` | `Teacher\LessonPlanController@destroy` | GET deletes. Remove, do not redesign. |
| `/teacher/lessonplan/edit/{id}` | `` | `Teacher\LessonPlanEditController@edit` | Not opened. |
| `/teacher/lessonplan/print/{id}` | `` | `Teacher\LessonPlanController@print` | Not opened. |
| `/teacher/lessonplan/show/{id}` | `` | `Teacher\LessonPlanController@show` | Not opened. |

### `teacher/lessonplans`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/lessonplans` | `` | `Teacher\LessonPlanController@index` | Not opened. |

### `teacher/libraryactivity`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/libraryactivity` | `` | `closure` | Not opened. |

### `teacher/marks`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/marks/releases` | `teacher.marks.releases.index` | `MarksReleaseController@index` | Not opened. |
| `/teacher/marks/releases/new` | `teacher.marks.releases.create` | `MarksReleaseController@create` | Not opened. |
| `/teacher/marks/releases/{release}` | `teacher.marks.releases.show` | `MarksReleaseController@show` | Not opened. |

### `teacher/myleaves`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/myleaves` | `` | `Teacher\LeaveController@myIndex` | Not opened. |

### `teacher/notices`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/notices` | `teacher.notices.index` | `Teacher\NoticeBoardController@index` | Not opened. |

### `teacher/notifications`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/notifications` | `` | `Teacher\NotificationController@index` | Not opened. |

### `teacher/postalrecord`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/postalrecord` | `` | `Teacher\PostalRecordController@index` | Not opened. |
| `/teacher/postalrecord/add` | `` | `Teacher\PostalRecordController@create` | Not opened. |
| `/teacher/postalrecord/delete/{id}` | `` | `Teacher\PostalRecordController@destroy` | GET deletes. Remove, do not redesign. |
| `/teacher/postalrecord/edit/{id}` | `` | `Teacher\PostalRecordController@edit` | Not opened. |
| `/teacher/postalrecord/show/{id}` | `` | `Teacher\PostalRecordController@show` | Not opened. |
| `/teacher/postalrecord/showlist` | `` | `Teacher\PostalRecordController@showlist` | Not opened. |

### `teacher/report-cards`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/report-cards` | `` | `closure` | Not opened. |
| `/teacher/report-cards/{stdLink}` | `` | `closure` | Not opened. |
| `/teacher/report-cards/{stdLink}/student/{learner}/download` | `` | `closure` | Not opened. |
| `/teacher/report-cards/{stdLink}/student/{learner}/preview` | `` | `closure` | Not opened. |

### `teacher/reports`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/reports/cards` | `teacher.reports.cards.index` | `Teacher\ReportCardsController@index` | Not opened. |
| `/teacher/reports/cards/{stdLink}` | `teacher.reports.cards.show` | `Teacher\ReportCardsController@show` | Not opened. |
| `/teacher/reports/cards/{stdLink}/student/{learner}/download` | `teacher.reports.cards.student.download` | `Teacher\ReportCardsController@downloadStudent` | Not opened. |
| `/teacher/reports/cards/{stdLink}/student/{learner}/preview` | `teacher.reports.cards.student.preview` | `Teacher\ReportCardsController@previewStudent` | Not opened. |

### `teacher/standardLink`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/standardLink/show/attendances/{id}` | `` | `Teacher\StandardsLinkDetailsController@getAttendance` | Not opened. |
| `/teacher/standardLink/show/classwall/{id}` | `` | `Teacher\StandardsLinkDetailsController@showClassWall` | Not opened. |
| `/teacher/standardLink/show/comments/{post_id}` | `` | `Teacher\StandardsLinkDetailsController@showComments` | Not opened. |
| `/teacher/standardLink/show/events/{id}` | `` | `Teacher\StandardsLinkController@showEvents` | Not opened. |
| `/teacher/standardLink/show/fees/{id}` | `` | `Teacher\StandardsLinkController@showFees` | Not opened. |
| `/teacher/standardLink/show/pastExams/{id}` | `` | `Teacher\StandardsLinkController@showPastExams` | Not opened. |
| `/teacher/standardLink/show/student/attendances/{id}` | `` | `Teacher\StandardsLinkDetailsController@getStudentAttendance` | Not opened. |
| `/teacher/standardLink/show/students/{id}` | `` | `Teacher\StandardsLinkController@showStudents` | Not opened. |
| `/teacher/standardLink/show/teachers/{id}` | `` | `Teacher\StandardsLinkController@showTeachers` | Not opened. |
| `/teacher/standardLink/show/timetable/{id}` | `` | `Teacher\StandardsLinkController@showTimetable` | Not opened. |
| `/teacher/standardLink/show/upcomingExams/{id}` | `` | `Teacher\StandardsLinkDetailsController@showUpcomingExams` | Not opened. |
| `/teacher/standardLink/show/{id}` | `` | `Teacher\StandardsLinkController@show` | Not opened. |

### `teacher/standardLinks`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/standardLinks` | `` | `Teacher\StandardsLinkController@index` | Not opened. |

### `teacher/student`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/student/edit-data/{id}` | `teacher.student.data` | `Teacher\StudentController@data` | Not opened. |
| `/teacher/student/edit/{id}` | `teacher.student.edit` | `Teacher\StudentController@edit` | Not opened. |
| `/teacher/student/show/activity/{name}` | `` | `Teacher\StudentDetailsController@showActivity` | Not opened. |
| `/teacher/student/show/attendance/{name}` | `` | `Teacher\StudentDetailsController@showAttendance` | Not opened. |
| `/teacher/student/show/details/{name}` | `` | `Teacher\StudentDetailsController@showDetails` | Not opened. |
| `/teacher/student/show/discipline/{name}` | `` | `Teacher\StudentDetailsController@showDisciplines` | Not opened. |
| `/teacher/student/show/libraryactivity/{name}` | `` | `Teacher\StudentDetailsController@showBookLent` | Not opened. |
| `/teacher/student/show/medicalHistory/{name}` | `` | `Teacher\StudentDetailsController@showMedicalHistory` | Not opened. |
| `/teacher/student/show/relations/{name}` | `` | `Teacher\StudentDetailsController@showRelations` | Not opened. |
| `/teacher/student/show/siblings/{name}` | `` | `Teacher\StudentDetailsController@showSiblings` | Not opened. |
| `/teacher/student/show/{name}` | `` | `Teacher\StudentDetailsController@show` | Not opened. |

### `teacher/studentLeave`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/studentLeave/approve/{id}` | `` | `Teacher\StudentLeaveController@approveCreate` | Not opened. |
| `/teacher/studentLeave/reject/{id}` | `` | `Teacher\StudentLeaveController@rejectCreate` | Not opened. |

### `teacher/studentLeaves`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/studentLeaves` | `` | `Teacher\StudentLeaveController@index` | Not opened. |

### `teacher/studenthomework`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/studenthomework/show/{id}` | `` | `Teacher\StudentHomeworkController@show` | Not opened. |

### `teacher/studenthomeworks`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/studenthomeworks/{id}` | `` | `Teacher\StudentHomeworkController@list` | Not opened. |

### `teacher/task`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/task/add` | `` | `Teacher\TaskController@create` | Not opened. |
| `/teacher/task/edit/{id}` | `` | `Teacher\TaskController@edit` | Not opened. |
| `/teacher/task/show/{id}` | `` | `Teacher\TaskController@show` | Not opened. |
| `/teacher/task/{id}/delete` | `` | `Teacher\TaskController@destroy` | GET deletes. Remove, do not redesign. |

### `teacher/tasks`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/tasks` | `` | `Teacher\TaskController@index` | Not opened. |

### `teacher/timetable`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/timetable` | `teacher.timetable.index` | `Admin\TimetableSlotController@teacherIndex` | Not opened. |
| `/teacher/timetable/create` | `teacher.timetable.create` | `Admin\TimetableSlotController@teacherCreate` | Not opened. |
| `/teacher/timetable/{slot}/edit` | `teacher.timetable.edit` | `Admin\TimetableSlotController@teacherEdit` | Not opened. |

### `teacher/visitorlog`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/visitorlog` | `` | `Teacher\VisitorLogController@index` | Not opened. |
| `/teacher/visitorlog/add` | `` | `Teacher\VisitorLogController@create` | Not opened. |
| `/teacher/visitorlog/delete/{id}` | `` | `Teacher\VisitorLogController@destroy` | GET deletes. Remove, do not redesign. |
| `/teacher/visitorlog/edit/{id}` | `` | `Teacher\VisitorLogController@edit` | Not opened. |
| `/teacher/visitorlog/show/{id}` | `` | `Teacher\VisitorLogController@show` | Not opened. |
| `/teacher/visitorlog/showlist` | `` | `Teacher\VisitorLogController@showlist` | Not opened. |

### `teacher/{id}`

| URL | Route name | Action | Note |
|---|---|---|---|
| `/teacher/{id}/impersonate` | `` | `Auth\ImpersonateController@impersonate` | Not opened. |
