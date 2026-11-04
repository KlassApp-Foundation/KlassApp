# Screen inventory: Riverside walkthrough

Destined for `docs/product/screen-inventory.md` in the preview. Nothing here is committed.

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

### 9. Marks / Mark submissions — DEMO
- **Screen:** `/admin/marks/submissions` → `ExamMarksSubmissionController@index` (≈`admin.php:926`, name `admin.marks.submissions`; `app/Http/Controllers/Admin/ExamMarksSubmissionController.php:18-29`), view `resources/views/admin/marks/submissions.blade.php`.
- **What's wrong (his words):** "Mark submissions" doesn't seem to work.
- **Code check: it works, it's just empty (see the full trace below).** The page lists only `exam_marks_submissions` rows with status `submitted` or `locked` and approval `pending` or null (`app/Services/Marks/MarksSubmissionService.php:291-300`). Such a row exists only after a teacher saves marks for an exam and presses Submit (`routes/teacher.php:465` → `Teacher/MarksController.php:126-146` → `MarksSubmissionService::submit`, 52-90). With no exams at Riverside there can't be any submissions, so the page shows "No submitted marks found." (`submissions.blade.php:44-50`). It's empty by design. Better empty-state copy would help: explain that submissions appear after teachers submit.
- **What I want:** to see marks coming in. That means the exam-set flow (entry 8) plus a clear path for teachers to enter marks (below).

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

### LATER entries (admin)
| # | Screen / area | What I want (his words, tidied) | Tag |
|---|---|---|---|
| L1 | Plan limits (`enforcePlanLimit`, earlier blueprint; the student-add notice is at `member/create.blade.php:13-18`) | Revisit the plan limits. | LATER |
| L2 | Admissions | An admissions form and invitation. | LATER |
| L3 | Student status | Suspend, and recommend for transfer. | LATER |
| L4 | Teacher | A weekly schedule (`/admin/teacher/my-timetable`, `admin.php:375`); reassign, suspend or transfer. | LATER |
| L5 | Subject page | Charts, plus mark entry by class and stream. | LATER |
| L6 | Calendar (`/admin/calendar` redirects to events, ≈`admin.php:1025-1026`) | One place for timetable and calendar, synced with each user's Google Calendar. | LATER |
| L7 | Attendance records (≈`admin.php:569-578`) | An attendance records page. | LATER |
| L8 | Unmatched payments (`/admin/fees/payments/unmatched`, ≈`admin.php:989-990`; `FeePaymentController.php:117-131`) | Hide it until a payment integration exists. | LATER |

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

*(Role 2 notes from Rasta go here next.)*
