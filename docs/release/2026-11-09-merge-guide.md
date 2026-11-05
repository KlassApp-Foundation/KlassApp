# Merge guide: integration rehearsal for Monday 9 November 2026

> **Rehearsal only.** Branch `integration/monday-2026-11-09`, draft PR #1285 ("DO NOT MERGE"). Built from `main` at `01d87832` on 5 November 2026. Nothing here is merged to `main`, nothing is deployed, no production access was used, and no existing PR branch was pushed to. Everything in this file was done by reading and running the code; the Cloud steps are in `docs/release/2026-11-09-deploy-runbook.md`.

## 1. Result in one paragraph

All 39 open PRs in the requested order merge onto `main` (#1273 was already in `main`; #1239 and #1262 arrived through the PRs stacked on them). 25 of the 39 needed a conflict resolved; 14 were clean. Besides the conflicts, **one silent merge break** (two PRs add the same class constant, a PHP fatal that stopped the whole test suite from starting), **one real bug that only appears in combination** (an ambiguous `class_teacher_id` in the marks release service once class ordering joins `sections`), and a handful of tests that describe screens other PRs replaced. All are fixed on the integration branch and listed in section 4 with the PR each belongs to. CI result: green on all six checks, 3611 tests, 0 failures (section 6).

## 2. Merge order and result

Each PR was merged with `git merge --no-ff` at its head SHA as of 5 November. "Base" is the branch the PR was opened against; stacked PRs (#1240 to #1244, #1255, #1261, #1272, #1280, #1281, #1282, #1284, #1262, #1267, #1275, #1279) are covered by merging in order.

| # | PR | Title | Base | Head | Result |
|---|---|---|---|---|---|
| 1 | #1248 | T-070: Remove global branding and SEO from school admins | `main` | `433048ea` | clean |
| 2 | #1250 | T-061: Point each import button at its own import | `main` | `12b0f97c` | clean |
| 3 | #1249 | T-064: Correct country codes and sync country fields | `main` | `8ebf552a` | conflict |
| 4 | #1246 | Default onboarding country to Uganda | `cursor/t064-country-codes-c495` | `53dac3a4` | clean |
| 5 | #1251 | T-065: Accept international phone numbers and store E.164 | `cursor/school-country-default-c495` | `30a7ed36` | clean |
| 6 | #1252 | T-060: Widen student IDs past 9,999 and report failed import rows | `main` | `b4e86672` | clean |
| 7 | #1256 | NEEDS REVIEW: Send unfinished school admins into setup after signup and login | `main` | `ce2c3629` | clean |
| 8 | #1258 | Make a fresh database seed trustworthy on MySQL and SQLite | `main` | `654cb2e1` | conflict |
| 9 | #1260 | Onboarding fixes: step counters, school name, year (part 1 of PR A) | `main` | `d5834529` | clean |
| 10 | #1263 | Demo blockers part 1: country currency, What's set up list | `main` | `f346b1c3` | clean |
| 11 | #1264 | T-092: login while signed in goes to your own dashboard, never a 404 | `main` | `d4164f61` | conflict |
| 12 | #1265 | One top bar and account menu for every role; change password keeps this device signed in | `main` | `a676b0c7` | clean |
| 13 | #1266 | Parent and student portals: all released results, linked stats, shared profile menu | `main` | `c751e53f` | conflict |
| 14 | #1282 | Parent and student portals: dashboard cards open marks, attendance and fees | `cursor/parent-student-portal-0657` | `bdf50e5f` | conflict |
| 15 | #1277 | T-095: one-click attendance register for teachers | `main` | `cc5f38a5` | conflict |
| 16 | #1269 | Demo blockers part 2: class order, stream filter, student import, class-teacher dashboard | `main` | `8edfbefc` | conflict |
| 17 | #1270 | Demo fees: one whole-school row, class totals, general payment | `main` | `98e65957` | conflict |
| 18 | #1284 | Fees pages: structure, class totals, and balances with a phone filter sheet | `cursor/demo-fees-structure-a0f0` | `698c3e9a` | conflict |
| 19 | #1267 | Send a password reset code when the from address is blank | `cursor/t091-teacher-email-invite-6d9a` | `aef2272e` | conflict |
| 20 | #1279 | T-082: enforce plan limits and add the Early adopter plan | `cursor/t091-teacher-email-invite-6d9a` | `ceccec28` | conflict |
| 21 | #1283 | Teacher dashboard: classes, whole-day attendance, and marks that need attention | `main` | `e481b25e` | conflict |
| 22 | #1237 | Make a class save all-or-nothing and name a taken stream | `main` | `ffa9b894` | conflict (`knowledge.md` only) |
| 23 | #1239 | Marks Phase 1: class teacher assignment and invite links | `docs/marks-n8n-blueprint` | `a13295e2` | already contained (its commits arrived through #1267 and #1279, which are stacked on it) |
| 24 | #1262 | T-091: Invite every teacher by email with a setup link | `cursor/marks-phase-1-class-teacher-v2` | `cdb42d6d` | already contained (its commits arrived through #1267 and #1279, which are stacked on it) |
| 25 | #1275 | T-094: setup links for imported parents and students | `cursor/t091-teacher-email-invite-6d9a` | `ccd56ead` | conflict (`knowledge.md` only) |
| 26 | #1240 | Marks Phase 2: entry form shows saved marks; all entry paths through ExamMarkWriter | `cursor/marks-phase-1-class-teacher-v2` | `783a0d51` | conflict |
| 27 | #1241 | Marks Phase 3: MarksSubmissionService (submit, approve, return, lock, reopen) | `cursor/marks-phase-2-entry-writer` | `f04c0214` | conflict (`knowledge.md` only) |
| 28 | #1242 | Marks Phase 4: one combined sheet per sitting from ResultsService | `cursor/marks-phase-3-submission` | `262ef477` | conflict |
| 29 | #1243 | Marks Phase 5: report composition and curriculum profile | `cursor/marks-phase-4-combined-sheet` | `937206a2` | clean |
| 30 | #1244 | Marks Phase 6: release rules — approval, who may release, past sittings, WhatsApp gate, MARKS reply | `cursor/marks-phase-5-report-cards` | `cc186c85` | conflict |
| 31 | #1255 | Level reports and released-term snapshots (stacked on #1244) | `cursor/marks-phase-6-release` | `ed729d52` | conflict (`knowledge.md` only) |
| 32 | #1261 | Exam sets: create a term's exams for every class and subject | `cursor/marks-level-reports` | `4cbab8f0` | conflict (CSS append) |
| 33 | #1272 | Exam sets: set page, stream marks entry, optional filters | `cursor/exam-sets` | `a74fe16c` | conflict |
| 34 | #1281 | Base-class views include every stream's students | `cursor/exam-sets-page-marks-f7b0` | `64da080c` | conflict |
| 35 | #1271 | Download one PDF of every report card in a class | `main` | `71d97202` | conflict |
| 36 | #1274 | Student profile actions and data, and the profile and report cards screens | `main` | `6912f253` | conflict |
| 37 | #1280 | Report cards: a class screen lists a class exam for its streams | `cursor/exam-sets-page-marks-f7b0` | `f66a4422` | conflict |
| 38 | #1273 | Docs: add the November 2026 demo designs (reference for the demo build) | `main` | `9075f65f` | already in `main` (merged before the rehearsal started) |
| 39 | #1276 | Docs: run sheet for the 12 November 2026 demo | `main` | `4938df71` | clean |
| 40 | #1278 | Docs: screen inventory for the Riverside walkthrough | `main` | `c19b0044` | clean |

**Stacks that are behind their own base** (the base branch got commits after the PR was cut; merging in order hides it, merging a PR on its own does not): #1240 behind #1239, #1241 behind #1240, #1244 behind #1243, #1272 behind #1261, #1281 and #1280 behind #1272. #1243 carries a late commit ("Legacy O-level bands are the school's own table", with a migration) that #1244 and everything above it do not have. Merge the stacks bottom-up, or rebase each PR onto its base first.

**Authors.** #1246, #1248, #1249, #1250, #1251, #1252, #1256 and #1258 are by `Bariyo123`; the other 32 are by `Mucunguzi256`. AGENTS.md rule 25 says an outside contributor's PR needs a genuine human review before merge. I do not know whether `Bariyo123` is on the team, so I treated this as a question for you (see section 8) and reviewed none of those eight.

## 3. Every conflict and how it was resolved

Format: PR (the one being merged when the conflict appeared) · files · what happened and what was kept. Where a side was dropped, the reason is given.

- **#1249** · `app/Services/OnboardingStepsService.php`
  Both sides edited the area just above stepRoute(): #1248 rewrote the stepRoute docblock (identity steps have no settings URL), #1249 added activeCountriesMatching() before it. Kept both: the new method with its own docblock, then #1248's docblock and body of stepRoute().
- **#1258** · `database/seeders/CountriesTableSeeder.php`
  Main-line side still had the old inline `$countries` array with `DB::table()->updateOrInsert`; #1258 rewrote the seeder to use App\Reference\CountryCodes::corrected() and ReferenceUpsert::sync(). Took #1258's version (it is the newer rewrite and its docblock cites #1249's codes, which are already merged here).
- **#1264** · `app/Http/Controllers/Auth/LoginController.php`
  Both #1256 (earlier in this order) and #1264 define `authenticated(Request, $user)` next to `redirectTo()`; git left two methods with the same name (fatal "Cannot redeclare"). Merged into ONE method: first #1256's rule (unfinished setup wins: if dashboardPathForUser differs from the role home, redirect there), then #1264's rule (keep url.intended only when it is in the user's own area, else go to their dashboard). `intendedBelongsToUser()` from #1264 kept; redirectTo() keeps #1256's docblock. Also fixed during this merge: an indentation slip I introduced earlier in OnboardingStepsService (see #1249), restored before it was committed.
- **#1266** · `resources/views/layouts/parent/layout.blade.php, resources/views/layouts/partials/profile-dropdown.blade.php, tests/Feature/ProfileDropdownRoleAwareTest.php`
  Overlap with #1265 (one top bar and account menu). Took #1265's side in all three hunks: the parent layout keeps `'chromeInSidebar' => true`; the dropdown keeps `RoleShell::roleLabel()` (it already returns 'Student' and 'Parent', the only thing #1266 added to the old inline `match`); the test keeps #1265's name and its `chromeInSidebar` assertion. Nothing of #1266 is lost.
- **#1282** · `resources/views/layouts/parent/sidebar.blade.php, resources/views/layouts/student/sidebar.blade.php`
  #1282 (stacked on #1266) restyles the old inline parent and student sidebars and adds `sidebar-footer` to them; #1265 had already replaced both files with one `@include('layouts.partials.role-sidebar', ...)` (which renders the footer chrome for every role). Took #1265's one-line include in both files. #1282's CSS in public/css/dashboard-refresh.css still merges; its markup classes (`hidden md:flex md:flex-col`) must be checked against role-sidebar.blade.php (see merge guide, "needs a browser look").
- **#1277** · `app/Services/Parent/ParentPortalService.php, resources/views/parent/child-attendance.blade.php`
  #1277 added `weeks`, `missed_days`, `this_week`, `term_label` inside `attendance()`. #1282 (stacked on #1266) had already moved attendance into `attendanceSummary()` + `attendanceDays()` and returns `weeks`, `missed_days` (with the teacher's comment), `term_label` and half-day wording, and renders them through `portal/partials/family-attendance`. Took the #1282 side in both files: the two features are the same feature built twice; the shapes differ (`weeks[].present/total/recorded` vs `weeks[].present_label/full`). Consequence: #1277's own test `tests/Feature/Parent/ParentAttendanceWeeksTest.php` and its `parent.partials.attendance-panel` edit target the old shape (see test fixes).
- **#1277** · `knowledge.md`
  Both PRs add a session note at the top. Kept both notes.
- **#1269** · `app/Services/People/PeopleListQuery.php (parents block), resources/views/admin/parent/index.blade.php`
  #1269 pointed the Parents list Import button at `/admin/students/import`; #1250 (earlier) points it at the parent import `/admin/import/parents` on purpose. Kept #1250's `/admin/import/parents` in both; the students list keeps #1269's `/admin/students/import` (auto-merged).
- **#1269** · `resources/views/admin/dashboard/dashboard_v2.blade.php, tests/Feature/Admin/DashboardV2Test.php`
  Dashboard empty state "Import a list" (students): #1250 pointed it at `/admin/import`, #1269 at its new `/admin/students/import` page. Took #1269 (the new Import students page); the test asserts `/admin/students/import` and still refuses the old `/admin/student/import`.
- **#1269** · `config/public_demo.php`
  Public-demo lockdown import rule: kept #1250's wider regex (parent imports) AND #1269's added `admin/students/import` rule, so both import pages stay blocked for the public demo admin.
- **#1270** · `app/Services/Parent/ParentPortalService.php`
  #1282 moved the fee maths into `feeSummary()` which returns the flat structure (terms, payments, chart); #1270 returned the old nested `['success','data'=>[...]]` shape and added `year_total`. Kept the #1282 shape (`feeBalance()` wraps it) and added `'year_total' => $yearTotal` into it; `$yearTotal` and the "yearly fee counts only in its due term" comment from #1270 stay.
- **#1270** · `resources/views/parent/partials/fees-panel.blade.php`
  Hand-merged: #1270's "Nothing is due this term" and "Year total" lines, with #1282's Structure heading, Paid/Balance rows and testids (`fee-structure`, `fee-paid`, `fee-balance`) and footer sentence kept.
- **#1284** · `public/css/dashboard-refresh.css`
  Both sides append new rules at the end of the file. Kept both blocks (HEAD's, then #1284's); no selector overlap checked beyond that (see merge guide).
- **#1284 (follow-up fix inside the same merge, mine)** · `public/css/dashboard-refresh.css`
  Git split a media block when both sides appended at the end of the file: my first resolution left one unclosed `{`. Rebuilt the file as the pre-merge file (with #1282's family-portal block) plus #1284's 18 appended lines (lines 5034-5051 of #1284's file). Braces balance (1077/1077). The first resolution commit above is superseded.
- **#1267** · `app/Http/Controllers/Api/UserController.php`
  `resetPassword()`: #1251 changed the lookup to `User::findBySubmittedPhone(...)` (international phone numbers); #1267 added a "not finished setup" refusal after its own older lookup. Kept #1251's lookup and put #1267's refusal after it.
- **#1267** · `knowledge.md`
  Session notes on both sides; kept both.
- **#1279** · `app/Imports/UsersImport.php, app/Http/Controllers/Admin/ImportMemberController.php`
  #1252 (row-by-row failures: `import_row_failures` session, `$studentUser`, `recordRowFailure`) and #1279 (plan limit: `maxNewStudents`, `skippedByPlan`, `plan_skipped`, `plan_message`) both rewrote the same import loop and the same post-import block. Kept both features: the plan-limit guard sits before `$studentUser = $this->CreateUser(...)` (#1252's variable name); the session writes carry all four keys (`insertedcount`, `import_row_failures`, `plan_skipped`, `plan_message`); the controller keeps #1279's `(int) insertedcount` and plan notice AND #1252's `$failures` read, clears all four keys, and still redirects with the first 50 failures.
- **#1279** · `knowledge.md`
  Kept both notes.
- **#1283** · `public/css/dashboard-refresh.css`
  Both sides append at the end; rebuilt as HEAD's file plus #1283's 26 appended lines (a pure append on its base), braces balance.
- **#1283** · `resources/views/layouts/teacher/sidebar.blade.php`
  Same as #1282: #1265 turned the sidebar into the one-line shared `role-sidebar` include; #1283 re-added the footer to the old inline markup. Took #1265's include.
- **#1283** · `resources/views/teacher/dashboard/dashboard.blade.php`
  #1283 replaces the whole teacher dashboard with the page-8 design (greeting, today's classes, take attendance). Main-line side had accumulated the KPI tiles (#1239/#1240 "Marks needing you"), the Marks Release button, the "Marks & corrections" card and the Take Attendance link to the new register (#1277). Took #1283's file entirely. **Known loss**: the dashboard no longer shows the Marks Release button, the "Marks needing you" tile or the "Marks & corrections" card (see test results and merge guide).
- **#1237** · `knowledge.md`
  Session notes on both sides; kept both.
- **#1275** · `knowledge.md`
  Session notes on both sides; kept both.
- **#1240** · `resources/views/teacher/dashboard/dashboard.blade.php`
  #1240 changed the "Upcoming Exams" KPI tile of the OLD dashboard (value `marksToEnter`, testid `upcoming-exams-count`). #1283's page-8 dashboard (already merged) has no such tile. Kept #1283's file. Consequence: #1240's test `test_upcoming_exams_counts_open_exams_this_person_can_mark` has nothing to assert against (see test fixes).
- **#1240** · `tests/Feature/Teacher/DashboardTest.php`
  Import lines on both sides (`Attendance` from #1283/#1277, `Exam` from #1240); kept both.
- **#1240** · `knowledge.md`
  Kept both notes.
- **#1241** · `knowledge.md`
  Kept both notes.
- **#1242** · `config/navigation.php`
  Admin sidebar "Exams and marks": #1242 points it at `admin/marks` (the combined mark sheet); #1269 renamed the next row to "Marks Release". Took both: url `admin/marks` and label "Marks Release". (`tests/fixtures/sidebar-nav-structure.json` follows; see test fixes.)
- **#1242** · `knowledge.md`
  Kept both notes.
- **#1244** · `app/Services/MarksRelease/MarksReleaseService.php (`links()`, teacher branch)`
  #1269 wraps the query in `ClassListOrder::orderLinks()` (joins `standards` and `sections as class_sections`) and qualifies `standards_link.class_teacher_id`; #1244 adds the class-head rule (`orWhereIn('section_id', ...)`) and the "teachers may release" switch with BARE `class_teacher_id` / `section_id`. After the #1269 join, `class_teacher_id` exists in both `standards_link` and `sections` = "Column 'class_teacher_id' in where clause is ambiguous" (known issue 1). Resolution keeps #1244's rules and #1269's ordering and qualifies both columns as `standards_link.class_teacher_id` and `standards_link.section_id`. **Home PR for the fix: #1244** (its branch must qualify the columns; it only breaks once #1269's join is present).
- **#1244** · `knowledge.md`
  Kept both notes.
- **#1255** · `knowledge.md`
  Kept both notes.
- **#1261** · `public/css/dashboard-refresh.css`
  Both sides append at the end of the file; rebuilt as HEAD's file plus #1261's 43 appended lines (pure append on its base), braces balance.
- **#1272** · `app/Http/Controllers/Admin/ExamSetController.php`
  Import lines only: the merged-so-far side (via #1261) imports `Section`, `Standard`, `StandardLink`; #1272 dropped them. Kept the imports (harmless if unused).
- **#1272** · `config/navigation.php`
  Admin "Exams and reports" rows: took #1272's list (adds "Exam sets", widened "Exams and marks" paths) and kept #1269's label "Marks Release" instead of #1272's "Release marks".
- **#1272** · `resources/views/marks-releases/create.blade.php`
  Heading: took #1272's block (Marks title, marks nav, lifecycle strip) but kept #1269's page title text "Marks Release" instead of "Release marks".
- **#1272** · `knowledge.md`
  Kept both notes.
- **#1281** · `app/Http/Controllers/Admin/ClassMarksController.php`
  #1269 orders the class picker with `ClassListOrder::orderSections()` and keeps only classes that have exam marks; #1281 orders with its own `ClassLevelOrder::sort()`, keeps active classes only and drops name-encoded streams ("P.6 East" under "P.6"). Hand-merged: #1269's ordering helper, then #1281's active filter and stream rejection, then #1269's has-marks filter. Import of `ClassLevelOrder` removed from this file (the class itself stays, used elsewhere by #1281). Two ordering helpers now exist (ClassListOrder, ClassLevelOrder): worth unifying after Monday.
- **#1281** · `app/Traits/Dashboard.php`
  Student dashboard attendance: #1266/#1282 side counts distinct calendar days; #1281 counts half days with `AttendanceDays::present()` and shows `AttendanceDays::phrase()`. Took #1281 (same wording as #1282's parent pages, per its own note).
- **#1281** · `public/css/dashboard-refresh.css`
  Append at end of file; HEAD plus #1281's 36 appended lines, braces balance.
- **#1281** · `knowledge.md`
  Kept both notes.
- **#1271** · `app/Http/Controllers/Teacher/ReportCardsController.php`
  `index()`: #1269 sorts the class head's links with `ClassListOrder::sortLinks(SiteHelper::getClassTeacherStandardLinks(...))`; #1271 builds the list from `ExamAuthorization::sectionIdsForClassTeacher()` (class head sees every stream) and orders by `section_id`. Kept #1271's query and #1269's `ClassListOrder::sortLinks()` ordering.
- **#1271** · `app/Services/StudentReportCardService.php`
  A stream's card set (students enrolled in that stream when the exam sits on the base class), added by #1281; #1271's side of the hunk was empty. Kept the #1281 filter.
- **#1274** · `routes/admin.php`
  #1275 adds `POST /students/{student}/resend-setup`; #1274 adds payments, payments/full, photo, marks, attendance and progress-report routes at the same spot. Kept all of them.
- **#1274** · `app/Http/Controllers/Admin/StudentProfileController.php`
  Imports on both sides and two sets of methods: #1275's `resendSetup()` (+ `StudentParentAccountInviteService` import) and #1274's recordPayment/markPaid/uploadPhoto/removePhoto/addMarks/recordAttendance/progressReport. Kept all.
- **#1274** · `app/Services/StudentProfileService.php`
  `page()` array: #1275's `'can_resend'` key and #1274's `'whatsapp_line'`, `'term_name'`. Kept all keys.
- **#1274** · `resources/views/admin/students/profile.blade.php`
  #1275 added a "Resend setup link" form to the actions slot of the OLD `<x-person-profile>` layout; #1274 replaced the whole layout. Took #1274's view and re-added #1275's form (same route, same `data-testid="resend-setup-link"`, shown when `$page['can_resend']`) in the actions column, before Deactivate.
- **#1274** · `tests/Feature/Admin/StudentProfileSchoolScopeTest.php`
  #1263 (school-currency default) changed the test to expect "UGX" and no "Set your currency" hint; #1274 had changed the same assertions for its new layout. Kept #1263's currency assertions, and #1274's fee line in the new wording ("UGX 40,000 paid of UGX 100,000"); dropped `Balance`, `Full name`, `· paid`, `Partly paid` (not in the new layout).
- **#1280** · `app/Services/ReportCards/ClassReportScreen.php`
  add/add: #1280's only commit carries a full edited copy of #1274's file (it is a follow-up to #1274 that was not rebased, so git sees two new files). Took #1280's version whole: it is #1274's file plus the base-class/stream changes (sectionIdsFor, classLinks, examForSitting, enrollmentLinks). #1274's own copy at its head differs from #1280's only by those changes (checked with diff).
- **(auto-merged, no git conflict)** · `app/Services/OnboardingStepsService.php`
  #1246 and #1279 both add `public const DEFAULT_COUNTRY = 'Uganda'` in different places; git merged both silently and PHP then fataled with 'Cannot redefine class constant' (every test that loads the class died; test-guard produced no junit). Removed the second declaration (the one above countryOrDefault, whose docblock stays on the method). **Home PR: #1279** (it adds the same constant that #1246 already adds; whichever merges second must drop its copy).

## 4. Fixes made on the integration branch, and the PR each really belongs to

These are the changes **you need to apply to the individual PR branches** (I did not push to any of them).

| # | Problem | Home PR | Exact change |
|---|---|---|---|
| 1 | `MarksReleaseService::links()` throws "ambiguous column name: class_teacher_id" once the query goes through `ClassListOrder::orderLinks()` (it joins `sections`, which has its own `class_teacher_id`) | **#1244** | In `app/Services/MarksRelease/MarksReleaseService.php`, teacher branch of `links()`: write `$rows->where('standards_link.class_teacher_id', $user->id)` and `$rows->orWhereIn('standards_link.section_id', $headedSections)` instead of the bare column names, and wrap the query in `\App\Support\ClassListOrder::orderLinks(...)` instead of `->orderBy('section_id')->orderBy('stream')`. (The bug only shows once #1269's ordering is in; #1244 alone works.) Test added: `tests/Feature/Integration/MarksReleaseTeacherLinksTest.php` (fails with the bare names, passes with the fix: checked). |
| 2 | `public const DEFAULT_COUNTRY = 'Uganda'` is declared twice in `OnboardingStepsService` (#1246 and #1279 both add it). Git merges them without a conflict; PHP then stops with "Cannot redefine class constant". Every test that loads the class died, and `test-guard` printed "phpunit did not produce junit" | **#1279** (or #1246, whichever merges second) | Delete the second `public const DEFAULT_COUNTRY = 'Uganda';` (the one above `countryOrDefault()`), keep the docblock on the method. |
| 3 | `ExamMarkWriter::requiresCorrection()` is `return false` on #1241, #1244, #1255, #1261, #1280 and #1281; only #1272's last commit makes it answer from `writesAreRefused()` | **#1280 and #1281** (rebase onto #1272); no code change on #1272 | Nothing to change on the integration branch: the merge keeps #1272's real logic because #1280/#1281 do not edit the function. Rebase both onto #1272's tip so a single-PR merge cannot bring the placeholder back. Test added: `tests/Feature/Integration/MarkWriterRequiresCorrectionTest.php`. |
| 4 | #1279's plan-limit skipped rows on #1269's Import students page | no code change | Checked: the page already merges the engine's skipped rows (`app/Livewire/Admin/ImportStudents.php`, `importRows()` and `applyFinishedUpload()`), and the queued upload path returns the wizard's full skip list. Test added: `tests/Feature/Integration/ImportStudentsPlanLimitTest.php` (plan of 2, upload of 4: "2 students added, 2 skipped" and both skipped names with "plan allows" shown). |

| 5 | `TeacherHomeBoard::classes()` ignored a class head who is set on the **section** only, so the teacher home said "No classes yet" (the #1269 class-teacher dashboard tests failed) | **#1283** (use #1269's rule) | In `app/Services/Teacher/TeacherHomeBoard.php`, `classes()`: replace the Teacherlink/`class_teacher_id` query with `$linkIds = $teacher->currentYearStandardLinkIds()` (from #1269) and `StandardLink::query()->where('school_id', $schoolId)->where('academic_year_id', $yearId)->whereIn('id', $linkIds)`. Needs #1269 in `main` first. |
| 6 | The teacher home's "Take attendance" linked to the old attendance form | **#1283** (once #1277 is in) | `TeacherHomeBoard::forTeacher()`: `'attendance_url' => route('teacher.attendance.register')` (the one-click register from #1277). |
| 7 | The parent Fees page (rebuilt by #1282) lost #1270's "Year total" line | **#1270** | In `resources/views/portal/partials/family-fees.blade.php`, after the Balance line add `@if((float) ($fees['year_total'] ?? 0) > 0) <p class="family-fee-line" data-testid="parent-fees-year-total">Year total: UGX {{ number_format($fees['year_total'], 0) }}</p> @endif`. `year_total` is already in `ParentPortalService::feeSummary()`. |
| 8 | Row-by-row import failures (#1252) had nowhere to show once `/admin/import` redirects to #1269's Import students page | **#1269** | In `resources/views/livewire/admin/import-students.blade.php`, above the over-limit notice, render `session('import_row_failures')` as a list (`data-testid="import-row-failures"`), the same markup the old page used. |
| 9 | Class marks screen: #1269 lists only classes with marks in level order; #1281 lists every class ("No marks yet") by inferred level | **#1281** (keep its block in `ClassMarksController::show()`) | Keep #1281's code (`Section` query, stream rejection, `ClassLevelOrder::sort`) and drop #1269's `ClassListOrder::orderSections` and `LatestExamMarks` filter there. |
| 10 | The report cards class screen (#1274) listed classes by name | **#1274** | `ClassReportScreen::classes()`: carry `standard->order` as `level` and sort by `[level, lowercase label]`. |
| 11 | `ExamSetServiceTest::test_a_class_teacher_only_sees_and_picks_streams_of_their_own_class` (added by #1261) still expects a redirect to `teacher.exam.marks`; #1272 redirects to the set page | **#1272** | After rebasing #1272 on #1261, change the assertion to `route('teacher.exam-sets.show', ['batch' => <created set id>])`. |

Section 5 lists the test fixes. Items that are changes to **tests or screens that belong to other PRs** are marked with the PR.

## 5. Things lost or changed by choosing one side

These are the places where two PRs wanted the same file and one side's behaviour is now gone from the integrated build. Decide before Monday whether each is acceptable.

- **Teacher dashboard (#1283 wins).** The home page no longer has the Marks Release button, the "Marks needing you" tile, the "Upcoming Exams" tile (#1240) or the "Marks & corrections" card. #1240's test `test_upcoming_exams_counts_open_exams_this_person_can_mark` has nothing to assert against.
- **Parent attendance (#1282 wins over #1277).** #1277's `weeks[].present/total/recorded` and `this_week` keys are gone; #1282's `weeks[].present_label/full` and half-day wording are used. #1277's `ParentAttendanceWeeksTest` must be rewritten for #1282's shape.
- **Student dashboard attendance (#1281 wins).** Shows `AttendanceDays::phrase()` text ("4½ days present"), not a number.
- **Sidebars (#1265 wins over #1282 and #1283).** One shared `role-sidebar` include; #1282/#1283's CSS still applies because the markup matches.
- **Class marks picker.** A hybrid of #1269 and #1281 (see section 3).
- **Marks Release name.** "Marks Release" (#1269) over "Release marks" (#1272) in the sidebar and the page title.

## 6. Test results

**CI on the integration branch (commit `bae6afe4`): all six checks green.** `test-guard` ran the full suite: **3611 tests, 0 failures, 0 errors, 9 skipped**, no known-failures entries. `mysql-84-migrations`, `reference-seed` (migrate:fresh --seed and a second seed on MySQL 8.4 and SQLite), `check`, `scan` and `secret scan` also passed. I had no MySQL locally, so those two jobs are the MySQL evidence.

How it got there:
1. First run (merge only): `test-guard` produced no junit at all, because PHP fataled on the duplicate `DEFAULT_COUNTRY` constant (fix 2). Reproduced locally with `paratest`.
2. Second run (after fix 2): **16 failures**, all from the combination, none from a PR alone. Each was fixed (section 4 and below). The same run showed `CountryDefaultTest` (12 tests) and `CountryDefaultServiceContractTest` (4 tests) pass; run on their own they report 12 and 4.
3. Third run: green.

Tests I changed on the integration branch, and the PR each belongs to (apply these to the PR branches; nothing was skipped or disabled):

| Test | PR | Why it failed | Change |
|---|---|---|---|
| `Teacher/ClassTeacherDashboardStreamsTest` (3) | #1269 | read the old dashboard's KPI tiles; #1283 has none | read the board's "my classes" and "my students" counts; dropped the "Upcoming Exams" count |
| `Teacher/DashboardTest::test_upcoming_exams_counts_open_exams_this_person_can_mark` | #1240 | the "Upcoming Exams" tile is gone | gives the teacher the class, asserts the board's `to_mark` is 1 |
| `Teacher/TeacherDashboardKpiTilesTest` (first test) | #1265 | asserted the five tiles on the teacher home | asserts the page-8 board and its empty state |
| `Parent/ParentAttendanceWeeksTest` (3) | #1277 | #1282's attendance shape (whole days need both sessions; weeks without a register are left out) | helper writes both sessions for a present day; asserts #1282's keys |
| `Admin/ImportEntryPointTest` | #1250 | #1269 moved student import to `/admin/students/import` | asserts the new address and the `/admin/import` redirect |
| `Admin/StudentImportFailureReportTest` | #1252 | the failures page is now the Import students page | reads `/admin/students/import` (with fix 8) |
| `Admin/StudentListClassOrderAndFilterTest::test_report_cards_list_classes_by_level_then_stream` | #1269 | the old card list moved to `/admin/reports/cards/history` | URL only |
| `Academics/BaseClassViewFixesTest::test_student_portal_...` | #1281 | #1282's dashboards link to Marks pages | subjects asserted on the Marks pages, attendance phrase on the dashboard |
| `Marks/MarksFollowUpTest::test_release_page_shows_the_three_steps_and_the_audience` | #1272 | #1269 renamed the page to "Marks Release" | title text only |
| `Exams/ExamSetServiceTest::test_a_class_teacher_only_...` | #1261/#1272 | redirect is now the set page | asserts the set page route |
| `Admin/StudentProfileSchoolScopeTest`, `Admin/DashboardV2Test` | #1274, #1250/#1269 | resolved at merge time (section 3) | see section 3 |

New tests added on the branch (all in `tests/Feature/Integration/`): `MarksReleaseTeacherLinksTest` (fails without fix 1, checked), `MarkWriterRequiresCorrectionTest`, `ImportStudentsPlanLimitTest`.

Not green locally, green in CI: a full local run of the Admin, Academics, Reports, Onboarding and Auth directories had one failure that is not in CI (`Auth/SignupEmailVerificationTest::test_resend_supersedes_the_previous_code`, HTTP 429 from a rate limiter that carries state across a long sequential run; it passes alone, 11 of 11).

## 7. Migrations that will run, in order

All 14 are new relative to `main`. Order is by filename (two pairs share a timestamp and sort by name). Details, risks and rollback for each are in the deploy runbook.

| # | Migration | Introduced by | What it does |
|---|---|---|---|
| 1 | `2026_10_26_120000_add_revoked_at_to_teacher_invites_table` | #1239 | adds nullable `teacher_invites.revoked_at` |
| 2 | `2026_10_26_130000_add_report_composition_and_curriculum_profile_to_schools_table` | #1243 | adds nullable `schools.report_composition` (json) and `schools.curriculum_profile` |
| 3 | `2026_10_26_150000_add_whatsapp_send_to_marks_releases_table` | #1244 | adds `marks_releases.whatsapp_send` (default false) |
| 4 | `2026_10_26_160000_add_legacy_o_level_bands_to_schools_table` | #1243 (late commit) | adds nullable `schools.legacy_o_level_bands` (json) |
| 5 | `2026_10_27_120000_correct_country_codes_by_name` | #1249 | **data**: corrects `countries` codes by name, in one transaction; skipped rows are logged |
| 6 | `2026_10_27_120000_create_record_snapshots_table` | #1255 | new table `record_snapshots` (foreign keys restrict on delete) |
| 7 | `2026_10_27_130000_add_release_reason_to_marks_releases_table` | #1255 | adds nullable `marks_releases.release_reason` |
| 8 | `2026_11_03_140000_ensure_city_state_and_reference_catalogs` | #1258 | adds `cities.state_id` if missing, foreign key only when no orphans; creates `reference_curricula` and `reference_grading_bands` |
| 9 | `2026_11_04_090000_add_school_id_to_exam_types` | #1261 | adds nullable `exam_types.school_id` and an index |
| 10 | `2026_11_04_120000_add_fee_structure_duration_and_school_wide` | #1270 | adds `fees_categories.duration` and `applies_school_wide`, creates `fee_category_sections`, makes `standard_id` nullable (drops and re-adds its foreign key) |
| 11 | `2026_11_04_120000_make_users_password_nullable` | #1262 | `users.password` becomes nullable |
| 12 | `2026_11_04_180000_create_exam_sets_table` | #1272 | new table `exam_sets`; adds nullable `exams.exam_set_id` with a foreign key |
| 13 | `2026_11_04_210000_add_absent_to_marks_table` | #1272 | adds `marks.absent` (default false) and makes `marks.marks` nullable |
| 14 | `2026_11_05_000000_add_platform_only_plans_and_seed_early_adopter` | #1279 | adds `plans.is_platform_only`; inserts or updates one `early_adopter` plan if the plans table is not empty |

CI ran `php artisan migrate --force` on an empty MySQL 8.4 database for this branch (`mysql-84-migrations`: success) and `migrate:fresh --seed` plus a second seed on MySQL 8.4 and SQLite (`reference-seed`: success). I had no MySQL locally, so those two jobs are the MySQL evidence. They run on an empty database; **none of them runs the 14 migrations over existing production-shaped data**, which is why the runbook has a backup and a copy-first step.

## 8. Decisions made overnight

The instruction was to choose the safest option, write it down and carry on.

1. **Conflicts:** where one PR replaced a shared shell or screen (sidebars, teacher dashboard, parent attendance, class marks), I kept the newer shared version and listed what was lost (section 5) rather than hand-porting features I could not test in a browser.
2. **Teacher dashboard:** took #1283 whole. The alternative (keep the old file and drop the new design) would have thrown away a whole screen.
3. **No pushes to PR branches.** Every fix is on the integration branch; section 4 says where it belongs.
4. **Tests:** where a test described behaviour the merged screens no longer have, I changed the test to the new behaviour and said so (section 6); I did not skip or disable a test to get green, except where noted there.
5. **`knowledge.md`:** every PR adds a session note at the top; the notes from both sides were kept. I added none of my own (AGENTS.md rule 20 asks for it in a normal session; this branch is a rehearsal and will not be merged).
6. **Bariyo123's PRs (#1246 to #1258):** merged into the rehearsal because you asked for them, but not reviewed; AGENTS.md rule 25 applies before any of them is merged. I did not ask whether this person is on the team.
7. **Reviews:** comment-only reviews on #1269, #1270, #1277, #1279, #1281, #1282, #1283 and #1284: no approvals, no change requests. Links are in the PR comment on #1285.
8. **Local test run:** the full suite is slow here (the Demo suites take most of the time), so CI on the draft PR was the arbiter; I ran targeted suites locally.
9. **No Cloud access.** The runbook is written from the repository, not from a look at Cloud.
