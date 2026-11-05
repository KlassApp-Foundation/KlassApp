# Screen inventory: Riverside walkthrough

**Last updated:** 5 Nov 2026.

Destined for `docs/product/screen-inventory.md`. Notes only; no code changed.

- **Code base:** local branch `scratch/riverside-combined` (HEAD 10f58b12), read with `git show` and `git grep`. No code was changed.
- **Line numbers:** they refer to the combined branch. Route lines marked ≈ come from a grep offset and may be ±1.
- **Tags:** **DEMO** means needed for Innovation Week (next week). **LATER** means after the demo.
- **Source of the notes:** Rasta's reactions while signed in as the Riverside school admin. His words are paraphrased only lightly.

---

## Role 1: School admin

### 1. Student profile — DEMO
- **Screen:** `/admin/students/{id}`. Route at `routes/admin.php:272` → `StudentProfileController@show` (`app/Http/Controllers/Admin/StudentProfileController.php:13-27`). View: `resources/views/admin/students/profile.blade.php`, inside `<x-person-profile>` (`resources/views/components/person-profile.blade.php`).
- **What's wrong (his words):** overview should be first and the default; tabs belong on top; the details aren't presented as an ID card; joined date is missing; "More" does nothing.
- **Code check:**
  - **Overview, partly contradicted.** Overview is already the first and default tab: `StudentProfileController.php:17` defaults `tab` to `overview`, and `app/Support/StudentProfileTabs.php:14` lists it first.
  - **Tab position, confirmed.** The tabs render *below* the header and the four KPI tiles (`person-profile.blade.php:58-74`: actions, then tiles at 63-66, then the tab nav at 69-74). So "tabs on top" is a real layout point.
  - **Details placement, confirmed.** Name, KLS number, class, gender, date of birth and joined are split. Part of it is a facts line beside the photo (`person-profile.blade.php:29, 39-41`). The rest is a `<dl>` inside the Overview tab (`profile.blade.php:95-105`). There is no ID-card block.
  - **Joined date, confirmed.** It comes from `userprofiles.joining_date` (`app/Services/StudentProfileService.php:90`). Onboarding never sets that field: the student `Userprofile::firstOrCreate` sets name, gender, date of birth and LIN only (`app/Services/OnboardingEngine.php:2140-2154`). So it shows "Not given" (`StudentProfileService.php:518-526`).
  - **"More", contradicted in code.** The button is wired: `profile.blade.php:38-41` opens a menu whose only item is Deactivate, and the toggle script is at `profile.blade.php:166-185`. The CSP is report-only (`config/security_headers.php:15-18`), so it can't block the script. If it really does nothing in the browser, that's a runtime or JS issue to check live. Either way, the menu only offers Deactivate.
  - **Actions, confirmed missing.** The only actions are Message (which links to the parent profile), Edit (the legacy edit form, `profile.blade.php:7, 36-37`) and More. There are no fee, marks or attendance actions.
- **What I want:**
  - Overview first and default, with tabs on top.
  - A student ID card beside the avatar: name, KLS number, class, gender, date of birth, joined.
  - The rest of the page shared by performance (download the current progress report, or any single past exam), attendance and fees.
  - Actions: adjust fees or mark paid, add marks, record attendance with a comment.
  - Joined date defaults to the onboarding date.
  - "More" either removed or given real actions.
- **5 Nov 2026:** Built in #1274 (`feat/student-profile-report-cards`, head `6912f253`). The page is a header card: photo (add, replace, remove), KLS number, class and stream, gender, date of birth, joined, and class teacher. The tabs sit under that card. Actions are Record a payment, Add marks (saved as drafts only), Record attendance, and Mark as paid for the outstanding term balance (`data-testid="mark-paid"`). Joined date defaults to the onboarding date. The same PR builds the report-cards class screen (Role 2, item 6). Message parent and Deactivate stay on the page. Photo and Mark as paid landed in commits `8a55aa7e` and `6912f253`; the PR body's "not matched" list is older than those commits.

### 2. Student list — DEMO
- **Screen:** `/admin/students`. Route at `routes/admin.php:271` → `StudentController@index` (`app/Http/Controllers/Admin/StudentController.php:55-62`). Data from `app/Services/People/PeopleListQuery.php:28-102`. View: `resources/views/admin/member/index.blade.php` plus `components/people-list.blade.php`.
- **What's wrong (his words):** the class selector doesn't filter; "Add student" opens the legacy form; "Import" opens the bare legacy `/admin/import` page.
- **Code check:**
  - **Class selector, not reproduced in code.** The picker is a `<details>` chip menu, not a `<select>` (`people-list.blade.php:32-45`). Each option is a link to `?chip=class&standard={standards_link id}` (`PeopleListQuery.php:998-1019`). The server filters by standard, section and stream (`PeopleListQuery.php:506-518, 378-385`), and `public/js/people-list.js` doesn't intercept those links. The code path looks correct, so this needs a live repro (which option he picked, and whether the URL changed). Note that the options are ordered by standards_link id, not by level (`PeopleListQuery.php:896-913`, `orderBy('id')` at 907).
  - **Add student, confirmed legacy.** `index.blade.php:10` links to `/admin/student/add/` → `StudentController@create` (`admin.php:276`; `StudentController.php:117-122`). That view is the old Vue `<create-member>` component (`resources/views/admin/member/create.blade.php:21`).
  - **Import list, confirmed legacy.** `index.blade.php:9` links to `/admin/import` → `ImportMemberController@index` (`admin.php:502`; `ImportMemberController.php:31-34`). That view is a bare file input posting to `admin/importUsers` (`resources/views/admin/member/import/import.blade.php:20, 40`).
- **What I want:** both buttons use the modern onboarding student form and import, with a list upload. The branded template route already exists: `admin.students.upload-template`, `admin.php:209`. Retire the extra `/admin/import` page.

### 3. Edit forms (student, teacher, parent) — DEMO
- **Screen:**
  - Student: `/admin/student/edit/{name}` → `StudentController@edit` (`admin.php:301-304`; `StudentController.php:289-297`), view `admin/member/edit.blade.php`.
  - Teacher: `/admin/teacher/edit/{name}` → `TeacherEditController@edit` (`admin.php:385`; `TeacherEditController.php:112-119`), view `admin/teacher/edit.blade.php`.
  - Parent: `/admin/parent/edit/{name}` → `ParentController@edit` (`admin.php:340-343`; `ParentController.php:277-286`), view `admin/parent/edit.blade.php`.
- **What's wrong (his words):** they're legacy forms.
- **Code check, confirmed.** All three use old Vue components:
  - `<edit-member>` at `member/edit.blade.php:16`;
  - `<add-tab-teacher … type="edit">` with a `portal-target` at `teacher/edit.blade.php:17-18`;
  - `<edit-parent>` at `parent/edit.blade.php:18`.
  All three also carry legacy address, latitude and longitude fields (`member/edit.blade.php:22-62`, `teacher/edit.blade.php:52`, `parent/edit.blade.php:50`). None of those fields appear in the onboarding templates.
- **What I want:** rebuild them in the modern style so they ask only what the onboarding templates ask.

### 4. Parent profile — DEMO
- **Screen:** `/admin/parent/show/{name}`. Route at `admin.php:333` → `ParentController@show`, which delegates to `ParentProfileController@show` (`ParentProfileController.php:16-33`). View: `resources/views/admin/parent/show.blade.php`.
- **What's wrong (his words):** the design needs work; actions are missing; "More" shows personal settings that belong only on the parent's own dashboard; parent emails are generated `.local` addresses.
- **Code check:**
  - **"More", partly contradicted.** It holds Deactivate and Reset password only (`show.blade.php:28-31`, the reset dialog at 91-98, and the route at `admin.php:335`). Those are admin account actions, not the parent's personal settings. If what he saw was language or notification settings, they came from some other menu, so this needs a live check. Moving Reset password out of the menu is still reasonable.
  - **Actions, confirmed missing.** The page has Edit, a WhatsApp status tile (`show.blade.php:73-75`), and per-child "View student" and "Report card" links (114-116). There is no send-report, message, assign-child or link-child-with-invite action.
  - **Emails, confirmed.** A new parent from the student import gets `parent.<random8>@<slug>.local` (`OnboardingEngine.php:2269-2278`, using `allocateGeneratedEmail` at `OnboardingEngine.php:2638-2645`). Students get the same treatment at 2100. `linkImportedParent` reads only the name and phone (2254-2255), so the template collects no parent email.
- **What I want:**
  - A better design.
  - Actions: send an individual report, send a message, assign another child, link child (checks WhatsApp status, can invite by email).
  - Personal settings removed from the admin view.
  - Parent emails collected (a template column), with no generated `.local` addresses.

### 5. Teacher profile and teacher list — DEMO
- **Screen:**
  - Profile: `/admin/teacher/show/{name}` → `TeacherProfileController@show` (`admin.php:382`; `TeacherProfileController.php:22`), view `resources/views/admin/teacher/show.blade.php`.
  - List: `/admin/teachers` → `TeacherListController@index` (`admin.php:348`), with the row menu built in `PeopleListQuery.php:165-169`.
- **What's wrong (his words):** "More" only offers Deactivate; the "assign class" quick action is broken; "edit" and "send invite" go to the same route.
- **Code check:**
  - **"More", confirmed.** Its only item is Deactivate (`teacher/show.blade.php:30-32`).
  - **Same route, confirmed.** Edit and Send invite both point to `/admin/teacher/edit/{name}` (`PeopleListQuery.php:167-168`).
  - **Assign classes, confirmed broken.** It links to `/admin/teacher/show/classes/{name}` (`PeopleListQuery.php:169`; route `admin.php:376`). That's `TeacherShowController@showClasses`, which returns a raw `TeacherClassesResource` JSON collection, not a page (`TeacherShowController.php:87-94`). So the admin sees JSON, and there's no assignment UI.
- **What I want:**
  - Remove "More" until it has real actions.
  - Assign class opens a real class and subject assignment.
  - Send invite issues a real invite. `TeacherInviteLinkService::issue/sendEmail` already exists (`app/Services/TeacherInviteLinkService.php:35, 99`).

### 6. Invite or change a class teacher — DEMO
- **Screen:** `/admin/class-teacher-invite/{section}/create` → `ClassTeacherInviteController@create` (`admin.php:204`; `ClassTeacherInviteController.php:21-56`), view `resources/views/admin/class-teacher-invite/create.blade.php`. It is opened from the classes card link "assign class teacher" (`livewire/class-roster/index.blade.php:109`).
- **What's wrong (his words):** it's a new-teacher form.
- **Code check, partly contradicted.**
  - An "Existing teacher?" dropdown does exist (`create.blade.php:40-50`; `existingTeachers` at `ClassTeacherInviteController.php:45-48`). When it's used, the name and phone fields hide (`create.blade.php:135-149`).
  - But the form leads with a required Email field (`create.blade.php:30-33`; validation at `ClassTeacherInviteController.php:72-76`), so it reads as a new-teacher form.
  - Choosing an existing teacher sends only a reassignment notice (`create.blade.php:50`; `ClassTeacherAssignmentService::assignExisting/notifyExistingTeacher`, 304, 430). It does not send a dashboard-setup link. Only a brand-new teacher gets a link (`inviteNew`, 346-393, which calls `TeacherInviteLinkService::issue` at 381).
- **What I want:** pick from the existing teachers first, then send that teacher an invitation to set up their dashboard for the class-teacher role (a claim link even when the account already exists), instead of a new-teacher form.

### 7. Classes — DEMO
- **Screen:** `/admin/classes`. A route closure at `admin.php:198-200` renders `class-roster/index.blade.php` → Livewire `app/Livewire/ClassRoster/Index.php`, view `resources/views/livewire/class-roster/index.blade.php`.
- **What's wrong (his words):** the filter is typed, not a dropdown; class names aren't clickable; cards have no stats; the order isn't Baby Class → Primary Seven.
- **Code check:**
  - **Filter, partly contradicted.** There are already dropdowns for year, level and assignment (`index.blade.php:39, 47, 56`). The class filter itself is a typed search (`index.blade.php:66`; `Index.php:104-106`).
  - **Name, confirmed not a link.** The class name is a plain `<h2>`. Only "Open roster" links to the class (`index.blade.php:85-92, 107`).
  - **Stats, confirmed thin.** The cards show the stream count, assigned streams and the class teacher only (`index.blade.php:94-104`). There are no gender, fees or performance stats.
  - **Order, confirmed.** Classes sort alphabetically by name: `RosterScopeService::visibleSections`, `orderBy('sections.name')` at `app/Services/RosterScopeService.php:47`. That gives Baby Class, Middle Class, Primary Five, Primary Four, Primary One… The level dropdown already uses `standards.order` (`Index.php:85-86`), so a level-order sort is available. See "Class ordering today" below.
- **What I want:**
  - A class dropdown filter.
  - Clickable class names that open the class page.
  - Card stats: gender split, fees collected, performance.
  - Order Baby Class → Primary Seven.

### 8. Exams — DEMO
- **Screen:**
  - `/admin/exams/add-new` → `ExamController@create` (≈`admin.php:897`; `app/Http/Controllers/Admin/ExamController.php:34-61`), view `resources/views/admin/exams/create.blade.php`.
  - Store: `ExamController.php:131-148`, validated by `app/Http/Requests/CreateExamRequest.php:44-53`.
- **What's wrong (his words):** the add-exam form is legacy and per subject.
- **Code check, confirmed.**
  - One exam row equals one subject in one class. `subject_id` is required (`CreateExamRequest.php:49`), and section and standard are derived from the subject (27-36).
  - The form has single selects: year, term, scheduled time, subject, type and teacher (`create.blade.php:72, 88, 106, 121, 138, 159`).
  - Exam types are the global list `ExamType::all()` (`ExamController.php:57`), seeded by `database/seeders/result/ExamTypes.php:18-51`.
  - **Correction:** the seeded codes are BOT, WE, MID, ME, WE (listed twice), EOT, MOCK, PreMOCK and FINAL. There is **no "MOT"**: mid-term is `MID` "Mid Term" (`ExamTypes.php:26-27`).
  - Class teachers already have their own create route (`routes/teacher.php:430`), still per subject.
- **What I want:**
  - Exam sets per term, with BOT, MOT (MID) and EOT defaults from the curriculum.
  - Each set applies to the chosen classes and all their subjects.
  - Admins, class heads or teachers can create them.
- **5 Nov 2026:** Creating the set stays #1261. The set page after that is built in #1272 (see E3). The November boards for these screens are in the repo via #1273 (`docs/design/2026-11-demo/`), which is merged and is a design reference only.

### 9. Marks / Mark submissions — DEMO
- **Screen:** `/admin/marks/submissions` → `ExamMarksSubmissionController@index` (≈`admin.php:926`, name `admin.marks.submissions`; `app/Http/Controllers/Admin/ExamMarksSubmissionController.php:18-29`), view `resources/views/admin/marks/submissions.blade.php`.
- **What's wrong (his words):** "Mark submissions" doesn't seem to work.
- **Code check: it works, it's just empty (see the full trace below).** The page lists only `exam_marks_submissions` rows with status `submitted` or `locked` and approval `pending` or null (`app/Services/Marks/MarksSubmissionService.php:291-300`). Such a row exists only after a teacher saves marks for an exam and presses Submit (`routes/teacher.php:465` → `Teacher/MarksController.php:126-146` → `MarksSubmissionService::submit`, 52-90). With no exams at Riverside there can't be any submissions, so the page shows "No submitted marks found." (`submissions.blade.php:44-50`). It's empty by design. Better empty-state copy would help: explain that submissions appear after teachers submit.
- **What I want:** to see marks coming in. That means the exam-set flow (entry 8) plus a clear path for teachers to enter marks (below).
- **5 Nov 2026:** That teacher path is built in #1272. See Role 2, item 5. The run sheet that scripts it is #1276 (`docs/demo/2026-11-12-run-sheet.md`), docs only.

### 10. Fees — DEMO
- **Screen:**
  - Fee types: `/admin/fees-categories` → `Academics\FeesCategoryController@index` (≈`admin.php:953-958`; `FeesCategoryController.php:39-40`), view `resources/views/admin/school/fees/index.blade.php`.
  - Payments: `/admin/fees/payments` → `FeePaymentController@index` and `@create` (≈`admin.php:961-964`; `app/Http/Controllers/Admin/FeePaymentController.php:23-71`), views `admin/fees/payments.blade.php` and `admin/fees/payment-create.blade.php`.
- **What's wrong (his words):** there's no simple table of fee types; the student list isn't sortable or editable; record payment has no student search and forces allocation.
- **Code check:**
  - **Fee table, confirmed.** It lists one row per fee category: Name, Amount, Level, Class, Term (`fees/index.blade.php:18-24`). There is no "classes applied" or "total per class" roll-up.
  - **Student search, confirmed missing.** The record-payment Student field is a plain select (`payment-create.blade.php:23`; inline form at `payments.blade.php:46`).
  - **Allocation, confirmed forced.** A fee category is required on every payment (`FeePaymentController.php:81-89`; `payment-create.blade.php:31`). There's no "against the total" option.
  - **Balances list, confirmed not editable.** It isn't editable on the recording page. Only the payments-history table is `sortable` (`payments.blade.php:134`).
- **What I want:**
  - A simple table: fee types, classes applied, total per class.
  - The student list sortable by class and editable on the recording page.
  - Record payment with student search, defaulting to recording against the total with no allocation, which is what most schools do.
- **5 Nov 2026:** Built in #1270 (`cursor/demo-fees-structure-a0f0`, head `98e65957`). One whole-school fee is one row. A class fee with the same name still saves and is added on top; the fee list then shows a warning (for example Tuition already exists as a whole-school fee, and the class total includes both). Older per-tier copies stay in the database. The list and the resolver hide them (`FeeStructureService::withoutHiddenLegacyCopies`); a TODO there is the clean-up follow-up. They are not deleted. A yearly fee counts once in the year total and only in its due term (`academic_term_id`, or the first term of the year by start date when that column is empty). Record payment can search by name, admission number or class, and the category can be left against the student's total. The walkthrough's sortable, editable balances list is not in this PR. Mark as paid on the student profile is #1274, not this fee-structure change.

### 11. Currency — DEMO
- **Screen:** cross-cutting.
  - The dashboard fees KPI (`resources/views/admin/dashboard/dashboard_v2.blade.php:203-205`).
  - The student profile fees tile (`profile.blade.php:70-72`).
  - The fee pages (`payments.blade.php:53, 75, 102, 154`; `payment-create.blade.php:27, 33`).
- **What's wrong (his words):** "Set your currency" appears even though the amounts are already in UGX.
- **Code check, confirmed.**
  - The hint shows whenever `school_details.meta_key = 'currency'` is missing (`app/Services/DashboardV2DataService.php:97-116`, "No hidden default").
  - Nothing in onboarding writes that key (no match in `OnboardingEngine.php` or `ManualOnboardingWizard.php`).
  - Meanwhile the fee pages hard-code "UGX" in labels and cells (lines above).
  - So the dashboard says "set currency" while the fee pages already say UGX.
- **What I want:** the currency set from the country at onboarding (Uganda → UGX), switchable to USD with conversion. No "Set your currency" hint once a currency is known.

### 12. Password reset — DEMO (production risk: **HIGH**)
- **Screen:** `/password/reset`. GET at `routes/web.php:305` → `Auth\ForgotPasswordController@showLinkRequestForm` (view `auth.preview.reset-request`, `ForgotPasswordController.php:80-91`). POST at `routes/web.php:306` → `@sendResetLinkEmail` (`ForgotPasswordController.php:29-74`). This code is identical on `main` and on the combined branch.
- **What's wrong:** for `wilson.adiga@riverside.demo.test`, the preview shows "We could not send a reset code right now. Please try again shortly."
- **Where the message comes from:** `ForgotPasswordController.php:67-69`, the only place it appears. It's inside `catch (Throwable $e)` (61), which wraps two steps (50-60):
  - the code-row insert, `Authentication::create` (51-58);
  - a **synchronous** `Mail::to()->send(new ResetPasswordCodeMail…)` (60).

  Any exception from either step becomes this one message. The real error is logged only as `Log::warning('Password reset code generation failed', ['email', 'error'])` (62-65).
- **What the code rules out:**
  - **The account exists.** An unknown email takes the other branch: a redirect with "If that email is registered…" (33-38). So a user with exactly that email exists, and the class-teacher import stored the file's email, not a generated `.local` one.
  - **Rate limit:** none. There's no throttle on `web.php:306`, and the web group has none (`app/Http/Kernel.php:46-56`). A throttle would give a 429, not this message.
  - **Demo-school guard:** not the cause. `DemoSchoolBlockOutboundMail` (`app/Listeners/DemoSchoolBlockOutboundMail.php:17-35`, registered at `EventServiceProvider.php:16-18`) returns `false`, which cancels the mail silently, with no exception and the success screen shown. It also wouldn't match here: the domain rule is only `@demo.klassapp.test` (`DemoSchoolCommsGuard.php:79-81`), and the school rule applies only if Riverside has `is_demo` set (83, 37-41). The public-demo listener (`AppServiceProvider.php:102-110`) also returns false silently.
  - **WhatsApp or SMS:** not involved. Reset is email-only, and the controller never calls a WhatsApp or SMS service, so the empty WhatsApp keys don't matter.
  - **Code storage:** DB table `authentications`. All inserted columns exist (`2020_06_24_170946_create_authentications_table.php:17-26`), `attempts` defaults to 0 (`2026_09_30_000001_…:12-13`), and the model fillable matches (`app/Models/Authentication.php:28-30`). No cache is used.
  - **Mail template:** renders in CI (`tests/Feature/Mail/EmailShellTest.php:115`). The text `code` component exists (`resources/views/vendor/mail/text/code.blade.php`), and the theme exists (`html/themes/klassapp.css`, `config/mail.php:84-89`).
- **Likely causes on the preview, ranked** (log mailer, sqlite, empty WhatsApp keys):
  1. **The log mailer fails while writing the email to the log.** The log transport writes the whole MIME message to the default channel `stack` (`config/mail.php:49-52`, where `MAIL_LOG_CHANNEL` is unset). On Laravel Cloud that stack is `LOG_STACK` = `laravel-cloud-socket,nightwatch` (`config/logging.php:39-44`), with `'ignore_exceptions' => false` (45). So a failure in any one channel throws out of `Mail::send`. The code itself says staging Nightwatch ingest "is currently broken (No authentication details / quota)" (`app/Services/EmailVerificationCodeService.php:79-82`). Signup mail hides this because it is **queued** (`EmailVerificationCodeService.php:75`), so it fails in the worker, whereas reset sends synchronously (60). The short warning in the catch evidently still logs (you'd get a 500 if it didn't), which fits a failure on the large message rather than on logging in general.
  2. **Empty or invalid `MAIL_FROM_ADDRESS`.** `config/mail.php:74` falls back to `connect@klassapp.xyz` only when the variable is unset. A blank value gives `''`, and Symfony rejects the From address during `send` (the Mailable sets it at `ResetPasswordCodeMail.php:25`). `gego:auth-readiness` checks the From address only for smtp (`app/Console/Commands/AuthReadinessCheck.php:132-147`), so a log-mailer preview isn't flagged.
  3. **The sqlite write fails** at `Authentication::create` (51), e.g. a locked or read-only database file. Unlikely, since other writes on the preview work.
- **Production risk: HIGH.** The same controller ships on `main`.
  - Any SMTP problem, such as bad credentials, a timeout or a provider outage, hits the catch-all at 61 and surfaces only as "try again shortly". There's no retry or fallback: the `failover` mailer is defined at `config/mail.php:58-64` but isn't the default.
  - The send isn't queued, so one transient provider error fails the request.
  - The real cause goes only to a warning log line.
  - On the user side, `.local` emails generated for parents and students (`OnboardingEngine.php:2100, 2273`) can never receive a code. Those users get "a code has been sent" and nothing arrives.
- **How Rasta can confirm on the preview** (prints no secrets):
  - Cloud log viewer, or on the box: `grep -h "Password reset code generation failed" storage/logs/laravel*.log | tail -n 5`. The daily channel writes `laravel-YYYY-MM-DD.log`. Each line has the email and `error`, the exception message: for example a socket or Nightwatch error points to cause 1, and an "addr-spec" or RFC 2822 error to cause 2.
  - `php artisan tinker --execute="echo config('mail.default').' | '.var_export(config('mail.from.address'), true).' | '.implode(',', config('logging.channels.stack.channels'));"` prints the mailer, the From address and the log stack only. Never dump the whole `mail` config, because it includes SMTP credentials.
  - Quick workaround test, without a code change: set `MAIL_LOG_CHANNEL=stderr` (an existing channel, `config/logging.php:71-76`) on the preview and retry. If that works, it confirms cause 1.
- **What I want:**
  - A working reset by email on the preview and in production: queue the mail or use a failover mailer, and send log-mailer output to a channel that can't fail.
  - A clear error, distinguishing "we couldn't email you" from "try again", with the real cause logged at error level and alerted.
  - No codes for undeliverable `.local` addresses: the user is told to ask their school admin instead.
  - The production risk flagged **HIGH**.
- **5 Nov 2026:** Built in #1267 (`cursor/password-reset-code-send-a7c1`, head `aef2272e`), stacked on #1262. A blank `MAIL_FROM_ADDRESS` is sent as `connect@klassapp.xyz`, so the reset code goes out. `PasswordResetCodeDeliveryTest` covers an imported teacher, a parent and a student. The code still goes out in the request, because it expires in five minutes; it is not queued. A generated `.local` address can still complete this flow. A null password still gets the teacher setup link and no reset code.

#### 12b. First-login `is_reset` vs the setup links in #1262 — DEMO
*Read on #1262, branch `cursor/t091-teacher-email-invite-6d9a` at `2048072`. On that branch the `is_reset` check is at `AuthenticatesUsers.php:281`, not :275.*
- **Where it is set.**
  - **Teachers** are created with a null password and `is_reset=0` in four places: `OnboardingEngine.php:1845-1848`, `RegisterUser.php:604-606` (usergroup 5), `ClassTeacherSetupController.php:143-145` and `TeacherLinkImportController.php:101-102`.
  - **Students** get a random password and `is_reset=1` (`OnboardingEngine.php:2103-2108`).
  - **Parents** get the same through `UserProvisioning::randomPasswordAttributes()` (`app/Support/UserProvisioning.php:31-36`), used at `OnboardingEngine.php:2259-2267` and `RegisterUser.php:609-611`.
- **Setup link accept.** `TeacherAccountInviteService::completeClaim` sets the password and `is_reset=0` (`TeacherAccountInviteService.php:162-180`). A brand-new claim does the same (`TeacherInviteLinkService.php:270-271`). So the invite-link flow causes no double prompt.
- **Exception: the older self-setup link** (`/teacher/setup`, `TeacherSetupController.php:72-76`, through `TeacherSetupLinkService`) saves the chosen password with **`is_reset=1`**. That teacher is logged in directly. At their next password login, `sendLoginResponse` sends them to the force-change screen (`AuthenticatesUsers.php:281-282`), so they have to change the password they just chose. **That is a double prompt.**
- **Order and bypass.** The null-password refusal in `attemptLogin` (`AuthenticatesUsers.php:206-207`) runs before `sendLoginResponse` (:281), so a passwordless account never reaches the force-change check. The force-change form also needs the current password to match (`ForceChangePasswordController.php:44-46`), and that check fails for a null hash. The route needs `auth` (`routes/web.php:314-318`). **I found no bypass.**
- **Parents and students.** Their random password is never shown or sent (`UserProvisioning.php:29-36`, flagged as a product decision at `OnboardingEngine.php:2259`), so they can only get in through a reset code or another sign-in route. **This is a separate gap,** like L9.
- **Update (Rasta, Nov 4):** the `/teacher/setup` fix (save `is_reset=0`, or route the flow through `completeClaim`, with a test) **is now being added to #1262**. The parent and student gap is now L10 (T-094).
- **5 Nov 2026:** L10 is built in #1275 (`cursor/t094-parent-student-setup-links`, head `ccd56ead`), stacked on #1262. See the L10 row.
- **Verdict:** this **fits** #1262 for invite links. **Minimal fix (not implemented):** set `is_reset=0` in `TeacherSetupController.php:74`, or move that flow onto `completeClaim`. A separate follow-up should give parents and students a first-login path, for example a setup link or a magic link.

### LATER entries (admin)
| # | Screen / area | What I want (his words, tidied) | Tag |
|---|---|---|---|
| L1 | Plan limits (`enforcePlanLimit`, earlier blueprint; the student-add notice is at `member/create.blade.php:13-18`) | Revisit the plan limits. | Built in #1279 (draft, `cursor/t082-plan-limits`, head `8b778880`). Early adopter is `no_of_users` 0, `no_of_students` 0, `is_platform_only` 1, assigned by a platform admin, and it stays out of a school's own plan picker. Every other plan is enforced on the single add forms, `OnboardingEngine` (wizard and Toshi), the teacher and student imports, and Toshi's direct add. This was LATER, not a walkthrough DEMO row. |
| L2 | Admissions | An admissions form and invitation. | LATER |
| L3 | Student status | Suspend, and recommend for transfer. | LATER |
| L4 | Teacher | A weekly schedule (`/admin/teacher/my-timetable`, `admin.php:375`); reassign, suspend or transfer. | LATER |
| L5 | Subject page | Charts, plus mark entry by class and stream. | LATER |
| L6 | Calendar (`/admin/calendar` redirects to events, ≈`admin.php:1025-1026`) | One place for timetable and calendar, synced with each user's Google Calendar. | LATER |
| L7 | Attendance records (≈`admin.php:569-578`) | An attendance records page. | LATER |
| L8 | Unmatched payments (`/admin/fees/payments/unmatched`, ≈`admin.php:989-990`; `FeePaymentController.php:117-131`) | Hide it until a payment integration exists. | LATER |
| L9 | Other staff accounts (librarians and other non-teacher staff) | They still get a random password on creation. Move them to the same setup-link invite as teachers (#1262 covers teachers only). Follow-up, not for the demo (Rasta, Nov 4, 2026). | LATER |
| L10 | Parent and student first-login access | Random password plus `is_reset=1`, never shared (`OnboardingEngine.php:2103-2108, 2259-2267`). **T-094** in Rasta's sheet. The first real schools will need it. Follow-up, not for the demo (Rasta, Nov 4, 2026). | Built in #1275 (draft, stacked on #1262). Import stores no password. A real email gets the same one-time setup link teachers get (`/invite/account/{token}`). Generated `.local`, `*.test` and the link-import `@school.edu` address send nothing. A parent with no email keeps the WhatsApp magic login, which now accepts a null password. Resend setup link is on the student and parent profiles. This was LATER, not a walkthrough DEMO row. The parent-profile redesign (entry 4) is not this PR. |

---

## Marks: trace and how-to (today's code)

### Why "Mark submissions" is empty
1. The route is `/admin/marks/submissions` → `ExamMarksSubmissionController@index` (`ExamMarksSubmissionController.php:18-29`).
2. The query is `MarksSubmissionService::awaitingReview($schoolId)` (`MarksSubmissionService.php:291-300`): rows in `exam_marks_submissions` for the school, with status in (submitted, locked) and approval_status pending or null. The sidebar badge uses the same filter (`pendingCount`, 279-289; `app/Support/SidebarCounts.php:69`).
3. Rows are created only by `submit()` (52-90). That runs from the teacher's Submit button (`teacher.php:465`) and only if marks have been saved for that exam (`hasWorkToSubmit`, 32-50; guard at `Teacher/MarksController.php:135-139`).
4. **Conclusion:** with no exams there are no sittings, no saved marks and no submissions, so the page is empty by design. It stays empty even after exams exist, until a teacher saves marks and submits.

### Steps to get marks entered today
**Admin**
1. **Year and term.** There must be an academic year whose `end_date` is in the future. The create form lists only those (`ExamController.php:40`). Terms are needed too (`ExamController.php:47`), and onboarding step 13 creates them.
2. **Subjects per class.** The subject list on the form is `Subject::where('section_id', …)` for the chosen class (`ExamController.php:43-46`), so the class must have subject rows from the onboarding subjects step.
3. **Optional subject-teacher link.** `Teacherlink` (subject + stream) sets the default exam teacher when the form's teacher is left blank. Without one, the exam teacher defaults to the admin (`ExamController.php:135-147`; `ExamAuthorization.php:128-147`). Links come from the onboarding teachers step (`OnboardingEngine.php:1905-1924`) or from `/admin/teacher-links` (`admin.php:43-46`; `TeacherLinkImportController.php:157`).
4. **Create the exams, one per subject per class.** Use `/admin/exams/add-new` (≈`admin.php:897`): pick year, term, subject, exam type (BOT, MID, EOT…) and **teacher**, then save (`ExamController.php:131-148`). For a whole class you repeat this for every subject.

**Teacher**

5. **Who sees an exam.** A teacher sees an exam at `/teacher/exam/marks` (`teacher.php:427` → `Teacher/MarksController.php:75-121`) if either:
   - `exams.teacher_id` is that teacher (89-90), or
   - the teacher is the class teacher of the exam's class for the active year (`ExamAuthorization::sectionIdsForClassTeacher`, 70-96; used at `MarksController.php:82-93`).

   An exam left defaulting to the admin is therefore invisible to subject teachers. Only the class teacher sees it.
6. **Enter marks.** Open `/teacher/exam/{exam}/marks/enter` (`teacher.php:437` → `MarksController.php:148-210`), enter marks, then save (`teacher.php:443`). Nursery classes get domain ratings instead (`MarksController.php:180-191`). A spreadsheet import is also available (`teacher.php:439-452`).
7. **Submit.** Use `POST /teacher/exam/{exam}/submit` (`teacher.php:465`). The row then appears on the admin's Mark submissions page for approval.

Class teachers can also create exams for their own class (`teacher.php:430-434`; `canCreateExams` at `MarksController.php:110`), still one per subject.

### Class ordering today (Baby Class → Primary Seven)
- **Classes page:** alphabetical by `sections.name` (`RosterScopeService.php:47`), streams by name (85).
- **Student-list class picker:** by `standards_link.id`, i.e. creation order (`PeopleListQuery.php:907`).
- **Level dropdown:** by `standards.order` then name (`ClassRoster/Index.php:85-86`). Only the level, not the class, has an order column in use.
- **Fix:** sort sections by an explicit curriculum rank. That could be a `sections.order` value set at onboarding from the curriculum list (Baby, Middle, Top, P1–P7), or a sort by `standards.order` and then by a curriculum rank.

---

## DEMO summary

| # | Screen | Effort | Dependency |
|---|---|---|---|
| 1 | Student profile: ID card, tabs on top, actions, joined date | M | Set `joining_date` at onboarding (`OnboardingEngine.php:2140-2154`); fee and marks actions depend on 10 and 8/9 |
| 2 | Student list: Add and Import use the onboarding flow; retire `/admin/import`; check the class picker live | M | Reusable onboarding student form and import (wizard) |
| 3 | Edit forms (student, teacher, parent) rebuilt to the template fields | M (×3 small forms) | Template field lists (blueprint's branded templates) |
| 4 | Parent profile: design, actions, collect emails | M–L | Parent email column in the student template; WhatsApp status; invite service |
| 5 | Teacher profile and list: drop More; fix Assign classes; split Edit and Send invite | S–M | `TeacherInviteLinkService` (exists) |
| 6 | Class-teacher invite: pick an existing teacher and send a setup invite | M | Claim link for existing accounts (`ClassTeacherAssignmentService` 304/346) |
| 7 | Classes: class dropdown, clickable names, stats, curriculum order | M | Ordering column or rank; fee and marks aggregates |
| 8 | Exam sets per term for chosen classes and all subjects | L | Exam types (BOT, MID, EOT), subject rows per class, teacher links |
| 9 | Marks: empty-state copy plus a working teacher path | S (copy) / depends on 8 | 8; teacher login (Role 2) |
| 10 | Fees: summary table, student search, pay against the total | M–L | Unallocated-payment model (today `fee_category_id` is required) |
| 11 | Currency from country, USD switch, hint logic | S (default) / M (conversion) | Write `school_details.currency` at onboarding; an FX rate source for USD |
| 12 | Password reset: working email reset, clear error (**HIGH** production risk) | S–M | Log channel or From address on preview; queued or failover mail; handling for `.local` addresses |

---

## Role 2: Class teacher

### Account options
**A. `teacher1@junior.demo.klassapp.test`**
- **Defined in** `database/seeders/DemoJuniorSchoolSeeder.php`. The domain is `DOMAIN` at :48, overridable by provision at :807-810. The teacher is created at :215-226 as "Grace Nabirye", usergroup 5.
- **School:** "Demo Junior School" (`schoolName()`, :864-867), flagged `is_demo=1` (:150).
- **Class-teacher assignment:** classes are assigned round-robin over teacher1–10 (:259-263, 279-308). teacher1 is first, so teacher1 is class teacher of **Baby Class**, the first class at :267. That's set on both the section (:294) and its stream link (:308). Baby Class is nursery, so mark entry there is domain ratings, not scores.
- **Password:** set only on create, via `Hash::make($this->freshPassword())` at :978. `freshPassword()` (:126-131) generates a unique random value that is never echoed or stored, so nobody knows it.
- **Getting in:** the only way is an operator running `php artisan demo:set-password <school id> --password=…` (`app/Console/Commands/DemoSetPasswordCommand.php:42-44`; demo schools only). A mail reset won't help, because the `is_demo` comms guard blocks outbound mail (`DemoJuniorSchoolSeeder.php:35`).
- **Caveat:** the seeder is manual ("never automatic", :137), so the account exists only if it was run on the preview.

**B. Riverside class teacher from the class-teacher import**
- **Password:** imported teachers get a random password (`OnboardingEngine.php:1839, 1883-1884`).
- **Invite link:** not available. Step 9 sent no invites, so there's no `teacher_invites` row to resend (`ClassTeacherInviteController@resend`, `ClassTeacherInviteController.php:90-118`). Assigning an *existing* teacher sends only a notice with no link (`ClassTeacherAssignmentService.php:304, 430`), so the claim route `/invite/teacher/{token}` (`routes/web.php:253-258`) can't be reached for them today.
- **Password reset:** `/password/reset` and `/password/reset-code` exist (`routes/web.php:305-309`). The mail goes to the configured mailer. Staging uses `MAIL_MAILER=log` (`app/Services/EmailVerificationCodeService.php:79`), so the reset link lands in the Cloud runtime log. That works only for whoever can read the preview logs (Rasta), and only if the teacher's email is the real one from the file, not a generated one.
- **Impersonation:** `POST /teacher/{id}/impersonate` (`routes/web.php:297`; `Auth/ImpersonateController.php:24-45`, redirects to `/teacher/dashboard`). It's limited to `schooladmin` and the same school. But there's no button for it in the modern teacher pages (the only impersonate form is in the legacy student show page), so it isn't a clean demo path.

### Recommendation
- **Use a Riverside primary class teacher,** e.g. *Wilson Adiga, Primary Four E* (synthetic, `wilson.adiga@riverside.demo.test`, `+256700000112`). That keeps the walkthrough on Riverside data and uses scores rather than nursery ratings.
- **Way in:** Rasta requests a reset at `/password/reset` for that email and opens the reset link from the preview log (mail-to-log).
- **Fallback:** if the logs aren't readable, an operator runs `demo:set-password` for Demo Junior and uses `teacher1@junior.demo.klassapp.test`. That path lands on Baby Class, which is nursery.
- **Landing after login:** `/teacher/dashboard` (`app/Helpers/AuthRedirectHelper.php:27` via `LoginController::redirectTo`, `LoginController.php:57-60`; route `teacher.php:4` → `Teacher/DashboardController@index`, 27-40). It shows the greeting, the teacher dashboard data, pending approvals and upcoming deadlines.
- **First class-teacher screen:** `/teacher/classes` (`teacher.php:18-20`, the same `class-roster.index` Livewire component, scoped by `RosterScopeService::visibleSections` to the teacher's classes). It should show their class card (Primary Four, stream E), and "Open roster" goes to `/teacher/classes/{section}/manage` (`teacher.php:21-26`).
- **Next screen:** `/teacher/exam/marks` (`teacher.php:427`). It is empty until entry 8's exams exist for Primary Four. As class teacher they will see every exam in the class (`MarksController.php:89-93`) and can create exams (`teacher.php:430`).

### Rasta's review: Wilson Adiga, Primary Four E (Nov 4, 2026)
*Note: I didn't receive Rasta's screenshots. These entries come from his written notes. Code references were read on `cursor/marks-phase-1-class-teacher-v2`, and on main where noted.*

1. **Top bar — DEMO.** The profile and notifications sit in a different place than on the admin top bar. The profile menu shows only "Change password", and using it logs the user out. **This is being fixed in a separate PR for all roles.** The menu is in `resources/views/layouts/partials/profile-dropdown.blade.php` ("Change password" at :98, logout form at :142-145).
2. **Dashboard says "no classes" — DEMO.** The banner reads "You haven't been assigned any classes yet" and every card shows 0, even though Wilson is class teacher of Primary Four E.
   - **Cause:** the banner shows whenever `myStudents === 0` (`resources/views/teacher/dashboard/dashboard.blade.php:21-25`). `Dashboard::teacherDashboard` (`app/Traits/Dashboard.php:386`) builds its class list only from subject allocations: `teacherlinkCurrentAcademicYear` (`app/Models/User.php:977-980`, the `Teacherlink` rows keyed on `teacher_id`) → `$standardLinks` (:401). That list drives timetable (:408), upcoming exams (:437), `myStudents` (:454-456) and `myClasses` (:458).
   - **Where the link is actually stored:** the class-teacher import and the wizard store it in `standards_link.class_teacher_id` and `sections.class_teacher_id` (`app/Services/ClassTeacherAssignmentService.php:138-148`). The dashboard never reads either column. Wilson teaches no subjects, so he has no Teacherlink rows and everything counts 0. The same code is on main.
   - **Fix (not implemented):** in `teacherDashboard`, merge into `$standardLinks` the ids of current-year `standards_link` rows where `class_teacher_id` is the teacher, plus the streams of sections where he is the class head, i.e. the same rule `RosterScopeService` uses.
   - **Owner:** Rasta's local agent builds this fix along with the other demo blockers (Rasta, Nov 4). It is not part of this walkthrough's fixes PR.
   - **For contrast:** `/teacher/classes` works because `RosterScopeService::visibleSections` (`app/Services/RosterScopeService.php:21-60`) checks `sections.class_teacher_id` and `standards_link.class_teacher_id` / `class_teacher_links` (:249-258).
3. **Student access scoping — DEMO.** A class or stream head sees their full class lists, and a class head sees every stream in the class. A subject teacher sees only the students who take their subject in each class they teach. Marks and exams follow the same rule.
   - **Today:** rosters are scoped through `RosterScopeService` (class head and stream head, see above). On `/teacher/exam/marks`, a class teacher sees every exam in their sections, and other teachers see only exams where `teacher_id` is them (`app/Http/Controllers/Teacher/MarksController.php:80-93`).
   - **Gap:** I found no per-subject student filter for subject teachers (who takes which subject). This is now DEMO item 9.
4. **Exams — DEMO.** Exams are created through exam sets (#1261), using the agreed design.
5. **Marks — DEMO.** Rename "Release marks" to **"Marks Release"**. The label appears in `resources/views/marks-releases/_button.blade.php:8` (the dashboard button, included at `teacher/dashboard/dashboard.blade.php:16`), `marks-releases/index.blade.php:14`, `create.blade.php:17`, `preview.blade.php:11`, and the comments at `_button.blade.php:2` and `app/Http/Controllers/MarksReleaseController.php:16`. The marks page needs more functions, such as recording marks by class or stream and subject. The design must make the teacher's part of the marks workflow clear.
   - **5 Nov 2026:** The workflow is built in #1272 (`cursor/exam-sets-page-marks-f7b0`). Manzi's note names head `0add32aa` (the ka-screen stylesheet commit). The PR head is now `f44c7298`, one commit later: a base-class release reaches the streams, and cards resolve the class. Base-class marks entry has a stream column and a stream filter (`teacher/marks/enter.blade.php`). A stream's subject teacher sees only that stream; the class head sees every stream (`BaseClassExamRoster`). AB is grade `AB`, `absent = true`, and a null mark, and it is left out of totals, averages, positions and divisions. A mark above 100 can be saved, shows "Above 100. Check the mark.", and cannot be submitted. The release page has the checklist (`data-testid="release-steps"`) and the audience panel (`data-testid="release-audience"`). Selected submissions can be approved together. The mark sheet has a phone card per student. The release-page test still expects the string "Release marks", so this PR does not do the rename.
6. **Report cards — DEMO.** Only admins and class heads can open them. They should be a mark-sheet style table with a performance overview, and offer a PDF of one student or of the whole class at once.
   - **Today:** teachers reach them at `/teacher/reports/cards` (`routes/teacher.php:392-396`, `ReportCardsController` index/show/preview/download for a single student). *I haven't verified how `ReportCardsController` restricts access to class heads.* The whole-class PDF is DEMO item 10.
   - **5 Nov 2026:** The class screen (class and exam filters, KPIs, average by subject, students by division, positions, a PDF per student, class-PDF states) is built in #1274. The one PDF of every card in the class is built in #1271 (item 10).
7. **Dashboard cards — DEMO.** Style them like the admin's cards and add charts of class stats: performance, attendance and gender.
8. **Attendance — DEMO (stretch).** A one-click present/absent register per class or stream. Build it if time allows. **Today:** `app/Http/Controllers/Teacher/AttendanceController.php` has `index` (:44), `create` (:154) and `store` (:166). The one-click register is DEMO item 11.
   - **5 Nov 2026:** Built in #1277 (`cursor/t095-one-click-attendance`, head `cc5f38a5`). Take attendance opens the roster with Present already selected. A class head gets every active stream of that class. Anyone else gets only the streams `attendance_scope` already allows. One save writes the register; a second save updates the same rows. The parent attendance page also gets a weekly summary (`weeks`, `missed_days`, `this_week`). The old add form stays.

9. **Subject-teacher filter — DEMO.** A subject teacher sees only the students who take their subject in each class they teach. No per-subject filter exists today (see item 3).
10. **Whole-class report card PDF — DEMO.** Download the report cards for a whole class at once. Today there is only single-student preview and download (`routes/teacher.php:392-396`).
    - **5 Nov 2026:** Built in #1271 (`cursor/whole-class-report-pdf-cd2d`, head `71d97202`). Admins, deputy admins on the admin pages, and class heads. Another school's class is 404. Everyone else is 403. Up to 12 students download in the request (`StudentReportCardService::CLASS_PDF_DIRECT_MAX`). A larger class is queued, and the class page says "Preparing the class PDF".
11. **One-click attendance register — DEMO.** One click marks each student present or absent, per class or stream. Today `app/Http/Controllers/Teacher/AttendanceController.php` has `index` (:44), `create` (:154) and `store` (:166).
    - **5 Nov 2026:** Built in #1277. Same register as item 8.

#### LATER (class teacher)
| # | Screen / area | What he wants (his words, tidied) | Tag |
|---|---|---|---|
| R2-L1 | Homework and assignments | Fold them into the exams page as assignments, tests and exams, all set up the same way. | LATER |
| R2-L2 | Timetable and Events | Turn them into one Calendar synced with each user's Google Calendar. Same as admin L6. | LATER |
| R2-L3 | Classes and Class Streams | Merge them into one page. | LATER |

### DEMO summary (class teacher)
| # | Item | Status |
|---|---|---|
| 1 | Top bar position and profile menu | Separate PR, all roles |
| 2 | Dashboard ignores the class-teacher link | Bug: cause found (`Dashboard.php:401`). Rasta's local agent fixes it with the demo blockers |
| 3 | Student, marks and exam scoping by class head or subject | Gap: subject-level scoping is missing |
| 4 | Exams via exam sets | #1261 |
| 5 | "Marks Release" rename and more marks functions | Workflow built in #1272 (stream roster, AB, over-100 block, release checklist and audience, bulk approve, phone sheet). The rename is not in #1272 |
| 6 | Report cards: admins and class heads only, mark sheet, class PDF | Built in #1274 (class screen) and #1271 (one PDF for the class) |
| 7 | Dashboard cards styled like admin, with charts | To build |
| 8 | One-click attendance register | Built in #1277 |
| 9 | Subject-teacher student filter | To build |
| 10 | Whole-class report card PDF | Built in #1271 (direct up to 12 students, queued above that) |
| 11 | One-click present/absent register (with 8) | Built in #1277 |

## Role 3: Parent

### Rasta's review: Shamim Kabagambe, parent of Aaron Ahimbisibwe, Primary Seven W (Nov 4, 2026)
*Synthetic data. I didn't receive Rasta's screenshot, so these entries come from his written notes. Code was read on `cursor/marks-phase-1-class-teacher-v2` at `a13295e`. I didn't check whether main differs.*

**Where the page comes from:** `/parent/dashboard` (`routes/parent.php:3`) → `Parent/DashboardController@index` (`app/Http/Controllers/Parent/DashboardController.php:13-39`) → `ParentPortalService::childDashboard` (`app/Services/Parent/ParentPortalService.php:222-238`) → view `resources/views/parent/dashboard.blade.php`, inside the layout `layouts/parent/layout.blade.php`.

1. **Dashboard layout — DEMO.** Realign the page. The stat cards should look like the admin's cards. Show the child's information more clearly.
   - **Stat cards today:** three `x-ds-kpi-card` cards, Fee Balance, Attendance (30 days) and Recent Marks (`parent/dashboard.blade.php:63-81`).
   - **The child's name and class appear three times at the top:**
     - (a) the page-header subtitle, name · class · school (`parent/dashboard.blade.php:6-10`)
     - (b) the child selector chip (`parent/dashboard.blade.php:42-43`)
     - (c) the header family menu (`layouts/partials/family-menu.blade.php:29-31`, included from `navigation.blade.php:104-106`)
   - **Capitals:** only the family menu applies `ucwords(strtolower(…))`. (a) and (b) print the stored name as it is, so the capitals probably come from the imported data. *That last point is unverified.*
   - **5 Nov 2026:** Built in #1266 (`cursor/parent-student-portal-0657`, head `c751e53f`) for the name: the selected child's name is shown once, in title case, and the stored name is not rewritten. The header family menu identifies children by school and class. The PR body does not describe a visual restyle of the stat cards to the admin cards.
2. **Performance — DEMO.** Show the child's results for every released exam and test set, not only the latest. Show nothing unreleased.
   - **Today:** `ParentPortalService::grades` (`ParentPortalService.php:335-380`) takes the exams that have marks for this student in the current academic year, newest first (`latest('id')`, :360). It then applies the release gate, `$this->releases->applyToExams` (:366), and keeps at most **15** exams (`take(15)`, :367).
   - **The gate** is `EloquentReleaseGate::applyToExams` (`app/Services/Results/EloquentReleaseGate.php:60-80`). It returns nothing when no sittings are released (:63-64). Otherwise it limits results to released sittings matched on school, exam type, term and, where set, section (:66-78). *I didn't read `releasedSittings()` or the `MarksRelease` model (`app/Models/MarksRelease.php`).*
   - **So unreleased results are already hidden.** The gaps are the 15-exam cap, the current-year limit, and the dashboard showing only a "Recent Marks" count (:76-81).
   - **5 Nov 2026:** Built in #1266. The list is every released exam and test set, with a year filter that starts on the current year. The 15-exam cap and the hard current-year-only query are gone. `EloquentReleaseGate::applyToExams` is unchanged, so an unreleased sitting stays hidden.
3. **Clickable cards and simple pages — DEMO.** The cards should open three pages: Marks (each released set), Attendance and Fees (structure, paid, balance). Each page gets a chart: performance over time, attendance, and fees paid against fees due.
   - **Pages that already exist:**
     - `/parent/children` (`routes/parent.php:4`)
     - `/parent/children/{student}/fees`, `/grades` and `/attendance` (`routes/parent.php:7-9`, `ChildDataController`)
   - The sidebar lists Dashboard, Children, Fees, Grades and Attendance (`config/navigation.php:246-256`).
   - **Not wired yet:** the cards aren't links, and the pages have no charts. *I didn't verify the `ChildDataController` views.* The sidebar's active paths are `parent/*/fees` etc. (:253-255), but the routes are `parent/children/{id}/…`. *It's unverified whether that mismatch breaks the highlight.*
   - **5 Nov 2026:** Built in #1266. The stat cards link to Marks, Attendance and Fees for the selected child, and to the student's own `/student/marks`, `/student/attendance` and `/student/fees`. Fees shows structure, paid and balance. Each of those pages has a Chart.js chart. #1277 later adds the parent weekly attendance bars on top of the attendance page.
4. **Sidebar — DEMO.** The active item has a dark red background, and the other items look greyed out as if disabled. Restyle it to match the admin sidebar.
   - **Why it differs:** the parent menu sets no `active_class`, so it falls back to a plain `active` (`layouts/partials/sidebar-menu.blade.php:25`). The admin menu uses `'active dashboard-active'` (`config/navigation.php:113`). The parent sidebar wrapper adds the `parent-sidebar` class (`layouts/parent/sidebar.blade.php:2`).
   - *I didn't find the CSS rule that makes it dark red or greys out the other items. Unverified.*
   - **5 Nov 2026:** Built in #1266. Parent and student sidebars use the admin active classes (`active dashboard-active`). Parent active paths match `parent/children/{id}/fees|grades|attendance`. Resting links stay full-strength text.
5. **Top bar — DEMO.** It has only a Logout button and no profile menu. **The cross-role header PR covers this** (same as class-teacher entry 1). The parent layout passes `'showLogout' => true` (`layouts/parent/layout.blade.php:5`), which swaps the profile menu for a plain Logout button (`layouts/partials/navigation.blade.php:149-153`).
   - **5 Nov 2026:** Built in #1266 for this item. The parent header uses `layouts.partials.profile-dropdown`, and `showLogout` is removed. Logout stays inside the menu. The cross-role top bar for every role is #1265, which is not one of the PRs recorded here, so class-teacher item 1 and student item 3 stay as they were.
6. **Sign-in shows "Page not found" — DEMO.** Signing in as a teacher showed "Page not found". After a refresh, the signed-in parent appeared. **Rasta's local agent is reproducing it** (possibly a session issue).
   - **Hypothesis, not verified:** after a successful login, `sendLoginResponse` uses `redirect()->intended(...)` (`app/Traits/AuthenticatesUsers.php:281`). So a URL saved in the session earlier, such as a parent page, wins over the role dashboard from `LoginController::redirectTo` → `AuthRedirectHelper::dashboardPathForUsergroup` (`LoginController.php:57-60`; teacher `/teacher/dashboard`, parent `/parent/dashboard`, `AuthRedirectHelper.php:25-33`).
   - That would explain a 404 or a wrong-role page. The fact that a refresh showed the *parent* suggests the old session was still active, for example a login done in another tab or without logging out first.
   - Worth checking that logout invalidates the session and clears `url.intended`. Also check whether role middleware returns 404 instead of redirecting.

#### LATER (parent)
| # | Screen / area | What he wants (his words, tidied) | Tag |
|---|---|---|---|
| R3-L1 | Parent portal extras | Anything beyond the four pages above: messages, documents, online payments. | LATER |

### DEMO summary (parent)
| # | Item | Status |
|---|---|---|
| 1 | Layout, admin-style cards, child info once and not in capitals | Built in #1266 for the name once, in title case. The PR body does not describe an admin-card restyle |
| 2 | Every released exam and test set, nothing unreleased | Built in #1266 |
| 3 | Clickable cards → Marks, Attendance, Fees pages with charts | Built in #1266. #1277 adds the parent weekly attendance bars |
| 4 | Sidebar active and inactive styling like admin | Built in #1266 |
| 5 | Top bar: Logout only | Built in #1266 (shared profile menu). The all-roles header is #1265 |
| 6 | "Page not found" after teacher login | Local agent is reproducing it. Hypothesis: intended-URL redirect |

## Role 4: Student

### Rasta's review: Aaron Ahimbisibwe, Primary Seven W (Nov 4, 2026)
*Synthetic data. These entries come from Rasta's written notes. Code was read on `cursor/marks-phase-1-class-teacher-v2` at `a13295e`.*

**Where the page comes from:** `/student/dashboard` (`routes/student.php:4`) → `Student/DashboardController@index` (`app/Http/Controllers/Student/DashboardController.php:26`), which returns the view `student/dashboard/dashboard` (:41). The layout is `layouts/student/layout.blade.php`. It includes the shared navigation without `showLogout` (:5), along with `layouts/student/sidebar.blade.php`. The sidebar items are defined in `config/navigation.php:226-242`: Dashboard, Marks, Attendance, Homework, Assignments, Events, Notices, Library, Holidays, Chats and Activity. Like the parent menu, the student menu sets no `active_class`, so it falls back to plain `active` (`sidebar-menu.blade.php:25`; admin uses `active dashboard-active`, `config/navigation.php:113`).

1. **Same discipline as the parent dashboard — DEMO.** That means an aligned layout, admin-style cards, all released results and nothing unreleased, and clickable cards that open simple Marks, Attendance and Fees pages with charts. **Built in #1266** (draft, `cursor/parent-student-portal-0657`). See Role 3 entries 1-4. The student pages are `/student/marks`, `/student/attendance` and `/student/fees`, and they share the parent query. *This note did not re-read the student results service beyond the PR body and its file list.*
2. **Timetable — DEMO.** The timetable is the Calendar view. The student sidebar has **no Timetable item** today; Events is at `config/navigation.php:236`. This ties to R2-L2 and admin L6.
3. **Top bar — DEMO.** The cross-role header PR covers it (same as R2-1 and R3-5).

#### LATER (student)
| # | Screen / area | What he wants (his words, tidied) | Tag |
|---|---|---|---|
| R4-L1 | Other sidebar items: Homework, Assignments, Events, Notices, Library, Holidays, Chats, Activity (`config/navigation.php:234-241`) | Keep them for now and complete them over time. | LATER |
| R4-L2 | Homework and Assignments | Planned to merge into the exams page. Same as R2-L1. | LATER |

### DEMO summary (student)
| # | Item | Status |
|---|---|---|
| 1 | Parent-style dashboard: cards, released results, Marks, Attendance and Fees pages with charts | Built in #1266 (draft) |
| 2 | Timetable shown as the Calendar view | Design-only |
| 3 | Top bar | Cross-role header PR |

## Exam sets test (Nov 4, 2026)
*Rasta's test of #1261 created **360 exams for Term 1**. **Rasta's local agent is fixing the logic in #1261.** Code was read on `cursor/exam-sets` at `4bad9bd` (draft, stacked on #1255).*

**Where 360 comes from:** `ExamSetService::create` (`app/Services/Academics/ExamSetService.php:25-75`) loops over **every subject row in scope**. It makes one exam for each subject and each exam type chosen (:34-37, `Exam::create` :51-61). It skips an exam only if one already exists for that subject, type and term (:38-49). `subjectsInScope` (:81-92) returns every active subject row for the year, filtered by `section_id` only when classes are picked (:87). Subject rows carry their own `standard_id` and `section_id` (:54-55), so each stream's subject rows become their own exams. **So 360 = (subject rows across all classes and streams) × (types chosen)**, for example 120 subject rows × BOT/MID/EOT. *The exact row count on the preview is unverified.* The streams are expanded because subjects are stored per stream. The service doesn't loop over streams itself.

- **E1. Configurable exam types, including school-owned ones — DEMO.** #1261 adds a nullable `exam_types.school_id` and `ExamType::visibleToSchool()` (from the PR body). The form lists the types as checkboxes and marks shared ones "(shared)" (`resources/views/admin/exams/exam-set-create.blade.php:38-48`). What's left to design is where a school adds and edits its own types.
- **E2. Class chips grouped by level — DEMO.**
  - **The request:** classes appear as clickable chips grouped by level, and the whole school is selected by default. Streams are picked only when needed. A class counts as one by default and covers all its streams.
  - **Today:** "Whole school" is an unchecked checkbox (`exam-set-create.blade.php:54`), and the classes are a plain multi-select list of section names (:62-66). The service scopes by `section_id` (`ExamSetService.php:87`).
- **E3. Preview after creating — DEMO.** Show the set grouped by class and exam type. **Today** the service returns only counts: created, skipped, sections and subjects (`ExamSetService.php:68-74`).
  - **5 Nov 2026:** Built in #1272. The set page (`admin/exams/exam-set-show.blade.php`, `data-testid="exam-set-page"`) groups the set by class, with a column per exam type, KPIs (exams, have marks, subjects with no teacher), and type and class filters.
- **E4. Setting exams and entering marks kept clearly separate — DEMO.** The flow is the exam sets page, then marks entry by exam, with submission. The exam sets page is `/admin/exams/sets/create` and `/teacher/exam/sets/create` (from the PR body). The marks entry and submission design is still to come (ties to R2-5).
  - **5 Nov 2026:** Built in #1272. The set page and the marks-entry screen are separate. Marks entry is the base-class roster described under Role 2, item 5.
- **E5. No required "All" filters on the marks screen — DEMO.** In `resources/views/admin/marks/filter-form.blade.php`, Term (:5-6), Class (:17-18) and Exam type (:29-30) are each `required` selects whose first option is an empty "All …". So "All" looks selectable but fails validation. Stream (:41) isn't required. This form is included at `admin/marks/combined-sheet.blade.php:31`.
  - **5 Nov 2026:** Built in #1272. On that branch the three selects are not `required`. The empty options read "All terms", "All classes" and "All exam types". Stream's empty option reads "Whole class".
- **Not built in #1272,** from that PR's own follow-ups: new-set switches, rename, "counts towards the report", Add exam type, Add classes, Assign teacher, an "All released" set status, marks-grid comments, offline save, the MarksUpload redraw, live loading skeletons for create and release, and a per-exam maximum (for example out of 50). The maximum in the PR is 100. E1's type-management screen and E2's class chips stay open.

## Master DEMO table (all roles)
*The Status column was updated on 5 Nov 2026 where one of #1266, #1267, #1270, #1271, #1272, #1273, #1274, #1275, #1276, #1277 or #1279 implements the item. Detail is in “PR coverage — 5 November 2026”. Every other row is unchanged.*

*This table is the brief for Claude Design. **Status** is one of three things. "Being built: …" names the PR or stream doing the work. "Onboarding fixes PR" means planned after the walkthrough. "Design-only" means it needs a design before anyone builds it. "Unassigned" means it's buildable, but no open PR covers it. A PR is credited only where its title, body or diff shows the item:*
- *#1261: exam sets (local agent)*
- *#1262: teacher setup links*
- *#1263: demo blockers part 1, currency and the "What's set up" list*
- *#1264: T-092, login while signed in goes to your own dashboard*
- *#1266: parent and student portals*
- *demo blockers part 2: local agent*
- *fees*
- *header PR: Claude Code, cross-role top bar*

*Effort: S is under half a day, M is 1-2 days, L is 3 days or more.*

| ID | Role | Item (short) | File:line | Effort | Dependency | Status |
|---|---|---|---|---|---|---|
| A1 | Admin | Student profile: ID card, tabs on top, action buttons, joined date | `OnboardingEngine.php:2140-2154` (joining date) | M | Fee (A10) and marks (A8/A9) actions | Built in #1274. Header card with photo, facts and joined date; tabs under the card; Record a payment, Add marks, Record attendance, Mark as paid |
| A2 | Admin | Student list: Add and Import reuse the onboarding form and import; retire `/admin/import`; check the class picker live | see entry 2 | M | Reusable onboarding student form | Unassigned |
| A3 | Admin | Student, teacher and parent edit forms rebuilt on the import-template fields | see entry 3 | M (3 small forms) | Branded templates (blueprint) | Unassigned |
| A4 | Admin | Parent profile: new design, actions, collect parent emails | see entry 4 | M–L | Parent email column in the student template | Unassigned |
| A5 | Admin | Teacher profile and list: drop "More"; fix "Assign classes"; split Edit from "Send / Resend setup link" | see entry 5 | S–M | `TeacherInviteLinkService` | Being built: #1262 (resend setup link on profile and list). The rest is Unassigned |
| A6 | Admin | Pick an existing teacher as class teacher; they get a setup link | `ClassTeacherAssignmentService.php:304, 430` | M | #1239 | Being built: #1262 |
| A7 | Admin | Classes page: class dropdown, clickable names, stats, curriculum order | see entry 7 | M | Ordering rank; fee and marks totals | Unassigned |
| A8 | Admin | Create a term's exam set for chosen classes and all subjects | `ExamSetService.php:25-75` | L | Exam types, subject rows, teacher links | Being built: #1261 |
| A9 | Admin | Marks: clear empty state plus a working teacher path | see entry 9 | S (copy) | A8 | Built in #1272 for the teacher path (base-class roster, stream column and filter, AB, over-100 block, phone sheet, bulk approve, release checklist). Empty-state copy stays with #1261 and was not re-checked here |
| A10 | Admin | Fees: structure table (name, amount, classes, duration: one-off, termly by default, yearly; total per class); any fee editable; one row per whole-school fee (today 4 became 8, one per level); record payment with student search against the total (`fee_category_id` optional) | see entry 10; `fees/index.blade.php:18-24`; `FeePaymentController.php:23-71` | M–L | Unallocated-payment model | Built in #1270 (draft). One whole-school row; a same-name class fee adds on top and the list warns after save; old duplicates are hidden, not deleted; a yearly fee counts once in the year total and in its due term |
| A11 | Admin | Currency from the school's country, with a USD switch and correct hint | `DashboardV2DataService::currencyFor` (per #1263 body) | S | — | Being built: #1263 |
| A12 | Admin | Password reset works for teachers, parents and students, with a clear error and no silent failure in production (HIGH) | see entry 12; `ForgotPasswordController.php:51-69` | S–M | Mail channel and From address; #1262 (same controller) | Built in #1267 (draft, stacked on #1262). The code sends when MAIL_FROM_ADDRESS is blank. The send stays in the request. A .local address can still be used |
| A12b | Admin | `/teacher/setup` saves `is_reset=0`, so there's no second password prompt | `TeacherSetupController.php:74` | S | — | Being built: #1262 (Rasta, Nov 4) |
| R2-1 | Class teacher | Top bar like the admin's: profile menu, notifications | `profile-dropdown.blade.php:98, 142-145` | M | — | Being built: header PR |
| R2-2 | Class teacher | Dashboard counts class-teacher streams, not only subject links ("no classes", all 0) | `Dashboard.php:401`; `ClassTeacherAssignmentService.php:138-148` | S | — | Being built: demo blockers part 2 |
| R2-3 | Class teacher | Student, marks and exam access: class or stream head sees the whole class; subject teacher sees only their subject's students | `RosterScopeService.php:21-60`; `MarksController.php:80-93` | M | R2-9 | Design-only |
| R2-4 | Class teacher | Exams are created through exam sets | see E1-E5 | L | A8 | Being built: #1261 |
| R2-5 | Class teacher | Rename "Release marks" to "Marks Release"; record marks by class or stream and subject; clear teacher marks workflow | `marks-releases/_button.blade.php:8`, `index:14`, `create:17`, `preview:11` | S (rename) / L (workflow) | E4 | Workflow built in #1272. The rename is not in #1272: the release-page test still expects "Release marks". The rename stays with demo blockers part 2 |
| R2-6 | Class teacher | Report cards for admins and class heads only: mark-sheet table, performance overview, PDF | `routes/teacher.php:392-396` | M–L | R2-10 | Built in #1274 (class screen) and #1271 (one PDF for the class) |
| R2-7 | Class teacher | Dashboard cards styled like the admin's, with class charts (performance, attendance, gender) | `teacher/dashboard/dashboard.blade.php` | M | R2-2 | Design-only |
| R2-8 | Class teacher | One-click present/absent attendance register (demo stretch) | `Teacher/AttendanceController.php:44, 154, 166` | M | — | Built in #1277 (same register as R2-11) |
| R2-9 | Class teacher | Subject-teacher filter: only students taking their subject | none exists | M | Who-takes-which-subject data | Unassigned |
| R2-10 | Class teacher | Whole-class report card PDF download | `routes/teacher.php:392-396` (single student only) | M | PDF queue for a whole class | Built in #1271. Direct for 12 or fewer students; queued above that. Admins and class heads |
| R2-11 | Class teacher | One-click register per class or stream (as R2-8) | `Teacher/AttendanceController.php:44, 154, 166` | M | — | Built in #1277 |
| R3-1 | Parent | Realigned dashboard, admin-style cards, child's name once in normal capitalisation | `parent/dashboard.blade.php:6-10, 42-43, 63-81`; `family-menu.blade.php:29-31` | M | — | Built in #1266 for the name once, in title case. The PR body does not describe an admin-card restyle |
| R3-2 | Parent | Every released exam and test set, all years, not capped at 15; nothing unreleased | `ParentPortalService.php:360-367`; `EloquentReleaseGate.php:60-80` | S–M | Release gate (exists) | Built in #1266 |
| R3-3 | Parent | Cards open simple Marks, Attendance and Fees pages; each with a chart | `routes/parent.php:4-9` | M | Chart component | Built in #1266. #1277 adds weekly attendance bars on the parent attendance page |
| R3-4 | Parent | Sidebar active item styled like admin; no greyed-out items | `sidebar-menu.blade.php:25`; `config/navigation.php:113, 246-256` | S | — | Built in #1266 |
| R3-5 | Parent | Profile menu instead of the bare Logout button | `layouts/parent/layout.blade.php:5`; `navigation.blade.php:149-153` | S | Header PR | Built in #1266 (shared profile menu). The all-roles header remains #1265 |
| R3-6 | Parent | Teacher login showed "Page not found", then the parent session | `AuthenticatesUsers.php:281` (hypothesis) | S | — | Being built: #1264 (couldn't reproduce; hardens `/login`) |
| R4-1 | Student | Same as the parent dashboard: cards, released results, Marks, Attendance and Fees pages with charts | `routes/student.php:4`; `Student/DashboardController.php:26-41`; `config/navigation.php:226-242` | M | R3-1 to R3-4 | Built in #1266 |
| R4-2 | Student | Timetable shown as the Calendar view | `config/navigation.php:236` (Events; no Timetable item) | M | Calendar (admin L6, R2-L2) | Design-only |
| R4-3 | Student | Top bar | `layouts/student/layout.blade.php:5` | S | Header PR | Being built: header PR |
| E1 | Exam sets | Configurable exam types, including the school's own | `exam-set-create.blade.php:38-48` | M | — | Being built: #1261 (school-owned types). The management screen is Design-only |
| E2 | Exam sets | Class chips grouped by level, whole school by default, streams only when needed | `exam-set-create.blade.php:54, 62-66`; `ExamSetService.php:81-92` | M | Fix to the subject-row expansion (360 exams) | Design-only (local agent fixing #1261 logic) |
| E3 | Exam sets | Preview after creating, grouped by class and type | `ExamSetService.php:68-74` | M | E2 | Built in #1272 (set page grouped by class, a column per exam type) |
| E4 | Exam sets | Exam set page, then marks entry by exam, with submission, clearly separate | — | M–L | E2, R2-5 | Built in #1272 |
| E5 | Exam sets | Marks screen: no required "All" filters | `admin/marks/filter-form.blade.php:5-6, 17-18, 29-30` | S | — | Built in #1272. Empty options are All terms, All classes, All exam types; stream is Whole class |
| A13 | Admin | Setup links for imported parents and students | `StudentParentAccountInviteService` | M | #1262 | Built in #1275 (draft, stacked on #1262). Was L10, not a walkthrough DEMO row. No password at import. A real email gets the link. A parent with no email keeps WhatsApp magic login. Resend is on both profiles |
| MK-1 | Marks | AB for an absent student: null mark, left out of totals, averages, positions and divisions | `ExamMarkWriter`; `marks.absent` | S | — | Built in #1272 |
| MK-2 | Marks | A mark above the maximum warns and blocks submit. The maximum in this PR is 100 | `MarksSubmissionService` | S | — | Built in #1272. A per-exam maximum (for example out of 50) is a follow-up |
| MK-3 | Marks | Bulk approve, release checklist and audience panel, phone mark sheet | `admin/marks/submissions.blade.php`; `marks-releases/create.blade.php`; `results-table2.blade.php` | M | — | Built in #1272 |
| PLAT-1 | Platform | Teacher and student plan limits, and the Early adopter plan | `PlanLimitService` | M | — | Built in #1279 (draft). Was L1, not a walkthrough DEMO row. Early adopter is 0/0, platform-only, and assigned by a platform admin |
| DOC-1 | Docs | November 2026 demo designs, 60 boards | `docs/design/2026-11-demo/` | — | — | #1273, merged (head `9075f65f`). Design reference only. No DEMO item is built by it |
| DOC-2 | Docs | Run sheet for the 12 November 2026 demo | `docs/demo/2026-11-12-run-sheet.md` | — | — | #1276 (draft, head `4938df71`). Docs only. No DEMO item is built by it |
| OB-F1 | Onboarding | Remove the school-size step; use the counted students (issues 1-2) | `onboarding-blueprint.md` Fix 1 | S–M | — | Onboarding fixes PR |
| OB-F2 | Onboarding | One class-teacher invite service for wizard, Classes and Toshi: accept and decline, preview, invite lists (issues 3-8) | `onboarding-blueprint.md` Fix 2 | L | #1239; #1262 | Onboarding fixes PR (#1262 already sends links for imports) |
| OB-F3 | Onboarding | Never generate teacher emails silently; add a "No email yet" option (issues 13-14) | `ManualOnboardingWizard.php:378, 3946-3951` | S–M | — | Onboarding fixes PR |
| OB-C1 | Onboarding | URL follows the current step; no lost input on refresh (issue 9) | `ManualOnboardingWizard.php:413-430` | S–M | — | Onboarding fixes PR |
| OB-C2 | Onboarding | Header (school name and year) updates after a save (issue 10) | `navigation.blade.php:88-97` | S | — | Onboarding fixes PR |
| OB-C3 | Onboarding | One step counter instead of two (issue 11) | `manual-onboarding-wizard.blade.php:71, 198` | S | — | Onboarding fixes PR |
| OB-12 | Onboarding | Header shows the school name exactly as entered (issue 12) | `navigation.blade.php:89` | S | — | Onboarding fixes PR |

## PR coverage — 5 November 2026

*“Built” means the code is in the named pull request. Only #1273 is merged. The others were open drafts when this note was written. Heads below are the GitHub heads read on 5 Nov 2026.*

| PR | Branch | What it builds | DEMO items it covers |
|---|---|---|---|
| #1266 | `cursor/parent-student-portal-0657` (head `c751e53f`) | Parent and student portals. Every released set, with a year filter; the 15-exam cap is gone; unreleased sittings stay hidden. The child's name once, in title case. Stat cards link to Marks, Attendance and Fees, each with a chart. Fees show structure, paid and balance. Sidebars use the admin active classes. The parent header uses the shared profile menu | R3-1 (name only; no admin-card restyle in the PR body), R3-2, R3-3, R3-4, R3-5, R4-1 |
| #1267 | `cursor/password-reset-code-send-a7c1` (head `aef2272e`, stacked on #1262) | The password reset code sends when `MAIL_FROM_ADDRESS` is blank. A blank or invalid From becomes `connect@klassapp.xyz`. Teachers, parents and students are covered. The send stays in the request. A `.local` address can still be used. A null password still gets the teacher setup link | A12 |
| #1270 | `cursor/demo-fees-structure-a0f0` (head `98e65957`) | Demo fees. One whole-school row. A same-name class fee adds on top, and the fee list warns after save. Old per-tier copies are hidden, not deleted, with a clean-up TODO in `FeeStructureService`. A yearly fee counts once in the year total and only in its due term. Record payment can search and can be left against the student's total | A10. Not the sortable, editable balances list |
| #1271 | `cursor/whole-class-report-pdf-cd2d` (head `71d97202`) | One PDF of every report card in a class. Direct for 12 or fewer students, queued above that. Admins, deputy admins on the admin pages, and class heads | R2-10, and the class-PDF half of R2-6 |
| #1272 | `cursor/exam-sets-page-marks-f7b0` (head `f44c7298`, stacked on #1261). Manzi's note names `0add32aa`; the later commit lets a base-class release reach the streams and lets cards resolve the class | The exam set page. Base-class marks entry with a stream column and filter. A stream teacher sees only their stream; the class head sees every stream. AB for absent. The over-maximum warning blocks submit (maximum 100). The release checklist and audience panel. Bulk approve. The phone mark sheet. Marks filters are optional | A9 (teacher path), R2-5 (workflow only; the rename is not in this PR), E3, E4, E5, MK-1, MK-2, MK-3. Not E1's type screen, not E2's class chips, not A8's create form (that stays #1261) |
| #1273 | `cursor/docs-demo-designs` (merged, head `9075f65f`) | The November demo designs, `docs/design/2026-11-demo/`: 60 boards, `canvas.json`, design tokens. README is the page map | DOC-1. No DEMO item is built by it |
| #1274 | `feat/student-profile-report-cards` (head `6912f253`) | Student profile: header card, photo (add, replace, remove), Mark as paid for the outstanding term balance, add-marks drafts, record attendance, joined date, Performance / Attendance / Fees. Report-cards class screen (design page 4): filters, KPIs, divisions, positions, a PDF per student, class-PDF states | A1 and the class-screen half of R2-6. Design pages 5 and 4 from #1273. Mark as paid is this profile action, not the fee-structure work in #1270 |
| #1275 | `cursor/t094-parent-student-setup-links` (head `ccd56ead`, stacked on #1262) | Setup links for imported parents and students. No password at import. A real email gets the teacher-style link. A parent with no email keeps WhatsApp magic login. Resend on both profiles | A13 (was L10). Not A4: the parent-profile redesign is not in this PR. The wizard does keep the Email and Parent Email columns |
| #1276 | `docs/demo-run-sheet-2026-11-12` (head `4938df71`) | The 12 November 2026 demo run sheet, `docs/demo/2026-11-12-run-sheet.md` | DOC-2. No DEMO item is built by it |
| #1277 | `cursor/t095-one-click-attendance` (head `cc5f38a5`) | One-click attendance. Present is already selected. A class head gets the whole class. A stream teacher gets their own stream. One save writes the register. The parent attendance page gains a weekly summary | R2-8, R2-11. The weekly bars sit on the attendance page from #1266; they are not a separate DEMO item |
| #1279 | `cursor/t082-plan-limits` (head `8b778880`) | Plan limits on the add forms, the onboarding engine, the imports and Toshi. Early adopter plan, assigned by a platform admin, unlimited, and hidden from a school's own picker | PLAT-1 (was L1). Not a walkthrough DEMO row |

### Built DEMO items

| Master ID | Item | Built in | Note |
|---|---|---|---|
| A1 | Student profile: ID card, tabs under the card, actions, joined date | #1274 | Photo, Mark as paid, add marks, record attendance, progress report. This is design page 5 |
| A9 | Working teacher marks path | #1272 | Stream roster, AB, over-100 block, phone sheet, bulk approve, release checklist. Empty-state copy was not re-checked |
| A10 | Fees structure and payment against the total | #1270 | Same-name class fee adds on top with a warning after save. Old duplicates hidden, not deleted. Yearly fee once in the year and in its due term |
| A12 | Password reset code sends when the From address is blank | #1267 | Teachers, parents and students. Not queued. `.local` still works |
| R2-5 | Marks workflow (not the rename) | #1272 | Checklist, audience panel, bulk approve, stream entry. The release-page test still expects "Release marks" |
| R2-6 | Report cards class screen and class PDF | #1274 and #1271 | Class screen in #1274 (design page 4). One PDF for the class in #1271 |
| R2-8 / R2-11 | One-click attendance register | #1277 | |
| R2-10 | Whole-class report card PDF | #1271 | Direct up to 12 students, queued above that |
| R3-1 | Child's name once, in title case | #1266 | Admin-style card restyle is not in the PR body, so that part is not marked built |
| R3-2 | Every released set, nothing unreleased | #1266 | |
| R3-3 | Marks, Attendance and Fees pages with charts | #1266 | |
| R3-4 | Sidebar active classes | #1266 | |
| R3-5 | Profile menu instead of the bare Logout button | #1266 | The all-roles header is #1265, not marked here |
| R4-1 | Student portal, same pages and released results | #1266 | |
| E3 | Set page grouped by class and exam type | #1272 | |
| E4 | Set page and marks entry kept separate | #1272 | |
| E5 | Marks filters no longer required | #1272 | |
| MK-1 | AB for absent | #1272 | |
| MK-2 | Over-100 warning blocks submit | #1272 | Maximum is 100 |
| MK-3 | Bulk approve, release checklist and audience, phone mark sheet | #1272 | |

Left as they were, because none of these PRs implement them: A2, A3, A4, A5, A6, A7, A8 (create form stays #1261), A11, A12b, R2-1, R2-2, R2-3, R2-4, R2-7, R2-9, R3-6, R4-2, R4-3, E1, E2, and the onboarding rows. A13 and PLAT-1 are built, and they were L10 and L1, not walkthrough DEMO rows. #1273 and #1276 do not build a DEMO item. Design page 5 from #1273 is A1 (an earlier note called that screen SP-1). Design page 4 is the class screen in R2-6 (SP-2). They are not separate items.

### Follow-ups — #1272

Read on head `f44c7298`. The migration is `database/migrations/2026_11_04_210000_add_absent_to_marks_table.php`.

The rollback test is `tests/Feature/Marks/MarksFollowUpTest.php`, `test_rolling_back_absent_marks_refuses_while_an_absence_exists`, lines 225–249:

- It does not check that `marks.marks` becomes non-nullable again after `down()`. After the absent rows are deleted it calls `down()` and asserts only that the `absent` column is gone (lines 243–245).
- That clean `down()` runs in the same test, after the refusal case, not on its own fresh database.

Still not built, from the PR body:

- a per-exam maximum (for example out of 50)
- new-set switches, rename, "counts towards the report", Add exam type, Add classes, Assign teacher
- an "All released" set status
- marks-grid comments
- offline save
- the MarksUpload redraw
- live loading skeletons for create and release

#1270's own follow-up, recorded there: hiding the old duplicate fee rows is enough for now. Retiring or merging them is later, and they are not deleted.

