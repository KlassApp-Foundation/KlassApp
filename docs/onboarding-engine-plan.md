# Onboarding Engine — Phase 1 Design Doc

> **Status:** design-only, pending human review. No implementation code has been written.
> **Goal:** eliminate the duplicate write layer between the manual onboarding wizard and Toshi AI onboarding by extracting a single `OnboardingEngine` service. Both paths will call the same methods; the only remaining differences will be UI/UX, not data or persistence.

## 1. Context

The manual wizard (`App\Livewire\ManualOnboardingWizard`) and the Toshi path (`App\Livewire\AgentToshi::commitAll()`) already agree on *which steps matter* because both use `OnboardingStepsService` for completion checks. They do **not** agree on *how to write the data*. This doc is the structural proposal for a single write engine.

## 2. Proposed `OnboardingEngine` API

File: `app/Services/OnboardingEngine.php`

One public method per step in `OnboardingStepsService::ALL_STEPS`, plus `createAdmin` because the platform create flow needs an admin user before the shared steps can run. All methods are designed to be **idempotent and school-scoped**.

### 2.1 Core identity steps

#### `createAdmin(School $school, array $data): User`
*Only for platform/superadmin “create a new school” flows; not in `ALL_STEPS` because the manual wizard starts with an existing admin.*

- **Input:**
  - `$data['name']` — required, `min:3`
  - `$data['email']` — required, unique email
  - `$data['phone']` — optional, normalized Uganda phone or null
  - `$data['password']` — optional, `min:6`; defaults to engine-configured default (`'password'` hashed)
- **Validation:** email must be unique; name must not be empty; phone normalized if present.
- **DB writes:**
  - `User::create(...)` with `usergroup_id = 3`
  - `Userprofile::create(...)` (`firstname` from name, `status = 'active'`)
  - `Subscription::create(...)` with a placeholder pending plan (mirrors `SchoolSignupBootstrapService` contract)

---

#### `saveSchoolName(School $school, string $name): School`
- **Input:** `$name` trimmed, required, `min:3`
- **Validation:**
  - `OnboardingStepsService::isPlaceholderSchoolName($name)` must be `false`
  - If changing an existing school, unique name via `SchoolSignupBootstrapService::uniqueSchoolName()`
- **DB writes:**
  - `$school->name = $name; $school->slug = Str::slug($name); $school->save()`

---

#### `saveCountry(School $school, string $country): void`
- **Input:** `$country` trimmed, required, `min:2`
- **Validation:** non-empty string.
- **DB writes:**
  - `OnboardingStepsService::persistCountry($school, $country)` (reuse existing shared country helper)
  - Sets `registration_country` and `country_id` if a matching `Country` row exists.

---

#### `saveCurriculum(School $school, string $curriculum): void`
- **Input:** `$curriculum` one of `uneb`, `cambridge`, `montessori`, `other` (lowercased)
- **Validation:** must be in allowed list.
- **DB writes:**
  - `$school->curriculum = $curriculum; $school->toshi_enabled = 1; $school->save()`

---

#### `saveSchoolCategory(School $school, ?string $category): void`
- **Input:** `$category` one of `SchoolCategorySeeder::CATEGORIES` or `null`/empty.
- **Validation:** if provided, must be a known key.
- **Disagreement resolution:** `school_category` becomes *optional but canonical*. Both paths can pass it. If provided, the engine persists it and seeds the canonical class/subject set.
- **DB writes:**
  - If `$category` is provided and valid: `$school->school_category = $category; $school->save()`
  - `SchoolCategorySeeder::seed($school)` only if not already seeded.
- **Note for Toshi:** Toshi currently collects `schoolType`, `schoolLevel`, `schoolGender` and `hasNursery`. The Toshi adapter will map those to a `school_category` value (e.g. `primary_nursery`, `o_a_level`) and pass it here. This makes `SchoolCategorySeeder::CATEGORIES` the single source of truth for defaults.

---

#### `saveEmis(School $school, string $ministryCode): void`
- **Input:** `$ministryCode` trimmed
- **Validation:** required if `OnboardingStepsService::isUganda($school->registration_country)`; skipped automatically for non-Uganda schools.
- **DB writes:**
  - `$school->ministry_code = $ministryCode; $school->save()`

---

#### `saveUnebCenter(School $school, ?string $unebCenterNumber): void`
- **Input:** `$unebCenterNumber` or `null`/`''` to skip
- **Validation:**
  - Optional even for UNEB schools.
  - If the column does not exist, no-op.
- **DB writes:**
  - `$school->uneb_center_number = $unebCenterNumber; $school->save()`
  - `null` means “not asked yet”; `''` means “asked and skipped”.

---

#### `saveAcademicYear(School $school, string $name, ?DateTime $start, ?DateTime $end): AcademicYear`
- **Input:** `$name` required; `$start`/`$end` optional
- **Disagreement resolution:** manual wizard passes real `start_date`/`end_date`; Toshi passes a label year. The engine treats the date pair as optional and defaults to start/end of the named year when not provided.
- **Validation:**
  - If `$start` and `$end` are present, `end after start`.
  - If no year exists, create one. If one exists, update only the name/dates if explicitly provided.
- **DB writes:**
  - `AcademicYear::firstOrCreate` by `school_id`, then update `name`, `description`, `start_date`, `end_date`, `status = 1`
  - `Cache::forget('academic_year_for_school_'.$school->id)`
  - `SchoolCategorySeeder::seed($school)` if `school_category` is set (idempotent)

---

### 2.2 Content steps

#### `saveStandards(School $school, AcademicYear $year, array $classes): void`
- **Input:**
  - `$classes` = `[ ['name' => 'P1', 'streams' => ['A','B']], ... ]`
  - Each `name` is required; `streams` optional, default `[]`.
- **Validation:**
  - At least one class.
  - No duplicate class names within payload.
- **DB writes:**
  - `Standard::firstOrCreate` named after `school->school_category` or `primary` default
  - `Section::firstOrCreate` per class name
  - `StandardLink::firstOrCreate` for each class/section + year
  - If `school_category` is set, `SchoolCategorySeeder::seed()` runs as a default baseline before custom classes are applied; user-supplied classes supplement (not replace) seeded classes.

---

#### `saveSubjects(School $school, AcademicYear $year, array $subjectsByClass): void`
- **Input:**
  - `$subjectsByClass` = `[ 'P1' => ['Math','English'], ... ]`
- **Validation:**
  - Each class in keys must have an existing `StandardLink` for this year.
  - Each subject name non-empty.
- **DB writes:**
  - `Subject::firstOrCreate` for each `(school_id, section_id, name)`
  - If `school_category` was set, `SchoolCategorySeeder::seed()` already created core subjects; the engine merges user additions only.

---

#### `saveTeachers(School $school, AcademicYear $year, array $teachers): void`
- **Input:**
  - `$teachers` = `[ ['name' => '...', 'email' => '...', 'phone' => '...', 'subjects' => [...], 'classes' => [...] ], ... ]`
  - `email` optional; if omitted the engine generates one.
  - `phone` optional.
- **Disagreement resolution (emails):** the engine owns all email generation. Manual no longer passes teacher emails directly; it either collects from the admin or lets the engine generate a school-scoped, unique email.
- **Validation:**
  - `name` required, `min:2`.
  - Generated/resolved email unique under `users` table; if duplicate, suffix with an integer.
- **DB writes:**
  - `User::firstOrCreate` by email (`usergroup_id = 5`)
  - `Userprofile::firstOrCreate` by `user_id`
  - `Teacherlink::firstOrCreate` when `classes` and `subjects` are provided; otherwise no link.

---

#### `saveStudents(School $school, AcademicYear $year, array $students): void`
- **Input:**
  - `$students` = `[ ['name' => '...', 'class' => 'P1', 'stream' => '...', 'parent' => '...', 'parent_phone' => '...', 'gender' => 'male|female' ], ... ]`
- **Validation:**
  - `name` required, `min:2`.
  - `class` must resolve to an existing `StandardLink`.
- **DB writes:**
  - `User::firstOrCreate` unique school-scoped email
  - `Userprofile::firstOrCreate` (`firstname`, `lastname`, `gender`)
  - `StudentAcademic::create` with `klassapp_student_id` from `StudentIdGeneratorService::nextForStudent()`

---

#### `saveTerms(School $school, AcademicYear $year, array $terms): void`
- **Input:**
  - `$terms` = `[ ['name' => 'Term 1', 'start' => 'YYYY-MM-DD', 'end' => 'YYYY-MM-DD'], ... ]`
- **Validation:**
  - At least one term.
  - `end after start` for each.
- **DB writes:**
  - `AcademicTerm::firstOrCreate` by `(school_id, name)` for the year.

---

#### `saveFees(School $school, array $fees): void`
- **Input:**
  - `$fees` = `[ ['name' => '...', 'amount' => 0.00, 'term' => '...', 'class' => '...' ], ... ]`
- **Disagreement resolution (amount):** `amount` becomes optional in both paths; missing or `0` stores `0.00`.
- **Validation:**
  - `name` required, non-empty.
  - `amount` numeric, `>= 0`.
- **DB writes:**
  - `FeesCategories::firstOrCreate` by `(school_id, name)`
  - `standard_id` resolved from `class` if present; otherwise the school’s first `Standard`.
  - Optional `academic_term_id` resolved from `term` if present.

---

#### `saveWhatsAppVerification(User $user, School $school, string $phone): WhatsAppUser`
- **Input:** `$phone` normalized `+256...` number
- **Validation:**
  - Required valid Uganda-format number.
  - Phone must not already be linked to another user.
- **DB writes:**
  - `WhatsAppUser::updateOrCreate` by `user_id` with `phone`, `school_id`, `opted_in = true`, `verified_at = now()`

---

#### `savePlan(School $school, User $admin, Plan $plan): CurrentPlan`
- **Input:** `$plan` instance, already validated active.
- **Disagreement resolution (plan rows):** both paths create `CurrentPlan` **and** `Subscription` (Toshi already does; manual must move to this model). Paid plans use `TrialService::startTrial`.
- **Validation:**
  - Plan active, not already blocked by current usage (`FreeTierPlanService::planWouldBlock` logic moved here).
  - `OnboardingStepsService::hasBlockingIncompleteSteps($school, $admin->id) === false` before any plan is assigned.
- **DB writes:**
  - `TrialService::startTrial($school->id, $plan->id)` if `$plan->amount > 0`
  - `CurrentPlan::updateOrCreate` by `school_id` with `plan_id`, `status = 'running'`
  - `Subscription::updateOrCreate` by `school_id + user_id` with `plan_id`, `status = 'pending'`, `start_date = now()`, `end_date = now()->addYear()`

---

## 3. Resolutions for the 9 flagged disharmonies

| # | Disharmony | Resolution |
|---|------------|------------|
| 1 | `school_category` is manual-only; Toshi never sets it. | **Make it optional but canonical.** Toshi maps `schoolType`/`hasNursery`/`schoolLevel` to a `school_category` value and passes it to `OnboardingEngine::saveSchoolCategory()`. If not provided, the step completes once `standards` exists. `SchoolCategorySeeder` becomes the single default source. |
| 2 | Default class/subject seeding differs (`SchoolCategorySeeder` vs `curriculumDefaults()`). | `SchoolCategorySeeder` is the canonical source. `AgentToshi::curriculumDefaults()` is removed; Toshi asks for `schoolType` and the engine seeds via `SchoolCategorySeeder`. Custom classes/subjects are layered on top. |
| 3 | Teacher/student email patterns differ. | **Unified email factory.** `OnboardingEngine` owns email generation and uniqueness suffixing. Teachers get `{slug-of-name}@{school-slug}.edu` (with duplicate suffix). Students get `student.{klassapp_student_id}@{school-slug}.sch.ug`. This replaces both manual and Toshi generators. |
| 4 | Fee `amount` required in manual, ignored in Toshi. | **`amount` optional in both; default `0.00`.** The engine accepts `amount` if provided and always writes a non-null decimal. Admins edit amounts later in the fee manager. |
| 5 | `exams` collected in Toshi but not persisted and not an `OnboardingStepsService` step. | **Remove exams from onboarding.** `exams` step is deleted from `AgentToshi::$steps` and `mandatorySteps`. The `handleExams()`/`saveExam()` UI is removed or moved to a post-onboarding “Add Exam” tool. No `Exam` creation in onboarding. |
| 6 | Manual `savePlan` only creates `CurrentPlan`; Toshi also creates `Subscription` and `TrialService`. | **Toshi behavior wins for both paths.** `OnboardingEngine::savePlan()` always creates `CurrentPlan` + `Subscription` and calls `TrialService` for paid plans. The manual wizard is updated to call this engine method. |
| 7 | Toshi `commit()` does not verify blocking steps before writing; manual `confirmReview()` does. | **Completion guard required in both.** `OnboardingEngine::savePlan()` (and any future `commit()` wrapper) calls `OnboardingStepsService::hasBlockingIncompleteSteps()` and aborts with a list of missing steps if not satisfied. |
| 8 | Toshi `mandatorySteps` omits `emis`, `uneb_center`, `fees`, `whatsapp_verify`, causing `skip` to save literal strings. | **Delete `AgentToshi::$mandatorySteps` and `isStepMandatory()`.** The engine uses `OnboardingStepsService` for “what is blocking”; the `send()` router uses `OPTIONAL_STEPS` to decide whether a typed `skip` is allowed. Applicable steps (`emis`, `uneb_center`) cannot be skipped. |
| 9 | `setSchoolType()` says “admin account” but advances to `country`. | **Fix the prompt string.** `setSchoolType()` will say the next step in `OnboardingStepsService` order (currently country). No reordering of `AgentToshi::$steps` unless product wants to move admin later. |

## 4. Migration / extraction plan

Principle: **write the new test before extracting each method**. The engine starts empty; each step is migrated one at a time from the top of the checklist downward. No wholesale replacement of `commitAll()` or `ManualOnboardingWizard` in one PR.

### 4.1 Phase 1A — engine skeleton + identity steps

1. **Create `app/Services/OnboardingEngine.php`** with no-op or identity methods first.
2. **Extract `saveSchoolName`, `saveCountry`, `saveCurriculum`, `saveSchoolCategory`, `saveEmis`, `saveUnebCenter`, `saveAcademicYear` into the engine.**
3. **Pre-extraction tests (must be added first):** `tests/Feature/Onboarding/OnboardingEngine/IdentityStepsTest.php` — for each method, pass a real `School`, assert exact `schools.*` and `academic_years` row shape. Run it before touching the wizard or Toshi.
4. **Refactor:** `ManualOnboardingWizard::saveSchoolName`, `saveCurriculum`, `saveCountry`, etc., become one-line calls to the engine. `AgentToshi::persistSchoolNameIfChanged`, `persistCountryFromInput`, `persistEmisFromInput`, `persistUnebCenterFromInput`, `persistAcademicYearIfMissing` become one-line engine calls.
5. **Run the existing suite:** `php artisan test --compact tests/Feature/Onboarding/`

### 4.2 Phase 1B — content seeding steps

1. **Extract `saveStandards`, `saveSubjects`, `saveTerms`, `saveFees` into the engine.**
2. **Pre-extraction tests:** `tests/Feature/Onboarding/OnboardingEngine/ContentStepsTest.php` — assert `Section`, `Standard`, `StandardLink`, `Subject`, `AcademicTerm`, `FeesCategories` shapes match after engine calls. Explicitly assert `SchoolCategorySeeder` outputs are the canonical defaults.
3. **Refactor:** `ManualOnboardingWizard::saveClass`, `saveSubject`, `saveTerm`, `saveFee` delegate to the engine. `AgentToshi::commitAll()` create/complete branches’ class/subject/term/fee loops delegate to the engine.
4. **Run full onboarding test suite again.**

### 4.3 Phase 1C — people + plan steps (highest risk)

1. **Extract `saveTeachers`, `saveStudents`, `saveWhatsAppVerification`, `savePlan` into the engine.**
2. **Pre-extraction tests:** `tests/Feature/Onboarding/OnboardingEngine/PeopleAndPlanTest.php` — include zero-teacher/student and paid-plan scenarios. Assert `User`, `Userprofile`, `Teacherlink`, `StudentAcademic`, `WhatsAppUser`, `CurrentPlan`, `Subscription` shapes are identical regardless of caller.
3. **Refactor:** `ManualOnboardingWizard::saveTeachers`, `saveStudents`, `saveWhatsApp`, `savePlan` become engine calls. `AgentToshi::commitAll()` becomes a thin orchestrator that calls engine methods and then dispatches success messages.
4. **Run the full suite:** `php artisan test --compact` or the largest practical subset.

### 4.4 Phase 1D — cleanup (updated Aug 2026 after 1A–1C shipped)

**1D-a (this slice): standing parity test + doc correction only.**

What is actually true after 1A–1C (do **not** treat the old checklist as still accurate):

1. **`ManualOnboardingWizard::save*()` private methods are not dead.** They are intentional thin Livewire adapters (`persistCurrentStep` → `OnboardingEngine`). Keep them; do not delete as “dead code.”
2. **`AgentToshi::curriculumDefaults()`, exams UI (`handleExams` / `saveExam` / …), and `$mandatorySteps` / `isStepMandatory()` are still live.** Removing them is product/behavior work (class-name drift vs `SchoolCategorySeeder`, skip rules, exam UX) — **out of scope for 1D-a**. Track as follow-ups (1D-b+).
3. **`persistSelectedPlan` vs `OnboardingEngine::savePlan`** remains a dual write path in complete-mode — status/subscription shape can diverge. Deduping is **1D-b**, not 1D-a.
4. **✅ 1D-a deliverable:** permanent `tests/Feature/Onboarding/OnboardingEngineParityTest.php` (see §5) — wizard `confirmReview()` vs Toshi complete-mode `confirmOnboarding()`, normalized DB snapshot equality.

**Minimal unblocker found while building Scenario B:** Toshi `commitAll` student draft mapping dropped `board_registration_number` / `school_student_id` before calling the engine (wizard already passed them). Pass-through added so both paths can persist candidate-class board reg. No other runtime behavior change in 1D-a.

### 4.4 (original draft — superseded)

~~1. Remove `ManualOnboardingWizard::save*()` private methods once all are engine-wired (or keep as thin pass-throughs).~~  
~~2. Remove `AgentToshi::curriculumDefaults()` and `handleExams()`/`saveExam()` if the product confirms exam removal.~~  
~~3. Remove `AgentToshi::$mandatorySteps` and `isStepMandatory()`.~~  
~~4. Add the permanent parity test `OnboardingEngineParityTest` (see §5) and run it.~~

## 5. Permanent parity test design

Test file: `tests/Feature/Onboarding/OnboardingEngineParityTest.php` (**implemented in 1D-a**)

Two canonical scenarios exercised through **public** entry points:

- Wizard: Livewire `next()` chain ending in **`confirmReview()`**
- Toshi: complete-mode Livewire **`confirmOnboarding()`** (after `selectSchoolCategory` for seeder baseline; session `toshi_state` cleared so `curriculumDefaults()` names cannot override seeder)

### Scenario A: Simple primary (Freemium, zero people)
- Uganda / UNEB / `school_category=primary` / EMIS set / UNEB skip
- Academic year + `SchoolCategorySeeder` classes/subjects
- Term 1, school-wide Tuition (wizard uses empty `className`), WhatsApp verified, Freemium
- No teachers / no students

### Scenario B: Candidate class + board registration
- Same primary baseline; one student on **Primary Seven** with `board_registration_number` (+ optional `school_student_id`)
- Asserts board reg persisted and section = Primary Seven on both paths

### DB assertions (identical across both paths after normalize)
Volatile uniqueness excluded from equality (`school` name/slug/phone, exact `ministry_code`, WhatsApp phone digits). Compared: curriculum/country/category/uneb, standards/sections/subjects/terms/fees (name/amount/standard/section), users by usergroup, student_academics (incl. board reg), whatsapp shape (opted_in/verified), current plan **name**, blocking-incomplete = false.

## 6. Non-goals for this design pass

- No UI reskinning; the engine is backend-only.
- No new `OnboardingStepsService` step keys (except removing `exams` from Toshi if product agrees).
- No migration or schema changes.
- No changes to `SchoolSignupBootstrapService` except that `OnboardingEngine::createAdmin` will supersede its admin-creation logic during Toshi create flow.

## 7. UI / UX design principles

> These principles guide the design of both Toshi's chat flow and the manual wizard's forms. They are **normative** — any new onboarding UI should follow them — but they are not yet fully implemented. See the status note on each principle.

### "Configurable, but never blank — prefill sensible defaults"

When a step's underlying data model is genuinely configurable (any number of terms, any class names, any fee structures), the UI presented to the user should still start with **sensible, real-world defaults pre-filled** — not an empty form asking them to build structure from scratch.

**Example:** academic terms are stored as fully configurable rows (any count, any names — see `OnboardingEngine::saveTerms`), but the UI should prefill the 3 standard UNEB terms (Term 1, Term 2, Term 3) as a starting point, which the school can then edit, add to, or remove from.

**Scope:** this principle applies across **both interfaces** — Toshi's chat flow and the manual wizard's forms — whenever either is designed or touched.

**Status: documented principle, not yet implemented.** The `saveTerms` backend is shipped; the terms UI in Toshi and the wizard has not been redesigned to prefill defaults yet. The canonical rule lives in **AGENTS.md standing rule #17**.

---

## 8. Open product questions before Phase 2

1. Do we keep the Toshi chat UI as the primary path, or should the manual wizard become the canonical path?
2. Should Toshi ask `school_category` explicitly, or continue deriving it from `schoolType`/`hasNursery`?
3. Do we remove the `exams` onboarding step, or should exams get a real `OnboardingStepsService` step and persistence?
4. Should the manual fee step require an amount (current behavior) or accept name-only (engine proposal)?

---

## 9. Conflicts with product decisions (flagged, not fixed)

Two product decisions are in force. Nothing in this section changes runtime behaviour. Each code site below has a one-line `FLAGGED(product-decision)` comment (a `//` comment in PHP, a `{{-- --}}` comment in Blade). Blade comments are not rendered.

1. **Invite links only.** KlassApp uses invite links and never sets or emails a password.
2. **Global.** KlassApp must accept any international phone number and any curriculum.

Proposed fixes, used in the table:

- **Invite-only.** Create the admin (and other people) with an invite link. Do not store a chosen, default, or random password, and do not email one.
- **E.164.** Normalise with `giggsey/libphonenumber-for-php`, using the school's country as the default region.
- **Profile data.** Drive curriculum, country, classes, terms, grading, and currency from the country profile and preset data, not from PHP constants.

Rough size is the follow-up that would apply that fix. Small is one method or one string. Medium is one flow plus its tests. Large is every create path, or the schema default plus every caller.

### 9.1 Passwords

The engine does **not** currently hash the literal string `password` for a school admin. Section 2.1 above (line 25) still specifies that default. The live create paths store a typed password or a random hash, and some copy still offers a "default" or "temporary" password. `database/factories/UserFactory.php` does hash `password`, and it is used from tests only (no `User::factory()` call under `app/`), so it is not flagged.

`UsersSchoolAdminTableSeeder` (`password123`), `UsersTeacherTableSeeder` and `UsersTableSeeder` (`password`), `UsersStudentTableSeeder` (`student123`), and `TestSchoolOneDataSeeder` (`password123`) are not flagged. They are not called by `ProductionSeeder`. `entrypoint.sh` and the Cloud deploy path do not run `DatabaseSeeder`. `UsersSiteAdminTableSeeder` does run from `ProductionSeeder`, so it is flagged.

Teacher and co-admin invite mail already sends a link and does not include a password. Those templates are not flagged. The user rows those invites create still receive a stored hash, and that write is flagged.

| File:line | What it does | Decision | Proposed fix and rough size |
|---|---|---|---|
| `docs/onboarding-engine-plan.md:25` | Design text says `createAdmin` defaults the password to `'password'`. | Invite-only | Drop that default from the design. Small. |
| `app/Livewire/AgentToshi.php:4706` | Create-mode chat asks for a password or the word `default`. | Invite-only | Ask for an invite, not a password. Small. |
| `app/Livewire/AgentToshi.php:6915` | Create mode stores the chosen password or a random hash, then can email a set-password link. | Invite-only | Invite-only admin creation. Medium. |
| `app/Http/Controllers/Auth/RegisterController.php:63` | Public signup passes the typed password into bootstrap. | Invite-only | Invite-only admin creation. Medium, with the signup form. |
| `app/Services/SchoolSignupBootstrapService.php:238` | Signup stores the typed password, or a random hash when none was typed. | Invite-only | Invite-only admin creation. Medium. |
| `resources/views/auth/register.blade.php:272` | The button is "Create account with password". | Invite-only | Invite-only admin creation. Small, same change as the preview register view. |
| `resources/views/auth/preview/register.blade.php:94` | The live register view uses the same button. | Invite-only | Same as the register view. Small. |
| `app/Services/OnboardingEngine.php:1843` | `saveTeachers` stores `bcrypt(Str::random(16))`. | Invite-only | Invite link for each teacher. Medium. |
| `app/Services/OnboardingEngine.php:2072` | `saveStudents` stores the same random hash. | Invite-only | Invite link, or no login until one is issued. Medium. |
| `app/Services/OnboardingEngine.php:2227` | An imported parent is created with `UserProvisioning::randomPasswordAttributes()`. | Invite-only | Invite link for the parent. Medium. |
| `app/Support/UserProvisioning.php:30` | Shared helper used at runtime to store a random password and `is_reset = 1`. | Invite-only | Stop issuing a hash from this helper. Medium, because several callers use it. |
| `app/Services/ToshiActionService.php:508` | Adding a teacher stores that random password. | Invite-only | Invite link. Small once the helper changes. |
| `app/Services/ToshiActionService.php:1437` | Adding a parent stores that random password. | Invite-only | Invite link. Small once the helper changes. |
| `app/Services/ParentLinkService.php:171` | WhatsApp parent linking stores `bcrypt(Str::random(12))`. | Invite-only | Invite link. Small. |
| `app/Services/Toshi/LiveAdversarialRunner.php:316` | The live adversarial command creates users with `Hash::make('password')`. | Invite-only | Do not use a shared password. Small. |
| `database/seeders/UsersSiteAdminTableSeeder.php:51` | `ProductionSeeder` stores `Str::password(20)` on a new site admin and does not email it. | Invite-only | Invite link for the site admin. Small. |
| `resources/views/livewire/agent-toshi.blade.php:692` | The create review says a password was set or a set-password link was emailed. | Invite-only | Say an invite was sent. Small. |
| `resources/views/livewire/agent-toshi.blade.php:1713` | The same sentence in the second review block. | Invite-only | Same copy change. Small. |
| `resources/views/emails/set-password-link.blade.php:2` | The mail Toshi queues after creating the admin is a set-password message. | Invite-only | Send an invite that does not talk about a stored password. Small. |
| `resources/views/auth/force-change-password.blade.php:71` | The gate says the account was created with a temporary password. | Invite-only | Remove the temporary-password gate when nothing sets one. Medium. |
| `resources/views/auth/preview/force-change-password.blade.php:21` | The preview of that gate uses the same sentence. | Invite-only | Same as the force-change view. Small. |
| `scripts/submit-whatsapp-message-templates.php:155` | The teacher WhatsApp template says "Your temporary login" and "set your password". | Invite-only | Invite-link copy, with no temporary login. Small, and a new template approval. |
| `scripts/retry-rejected-whatsapp-templates.php:41` | The same template body. | Invite-only | Same copy. Small. |
| `scripts/retry-rejected-whatsapp-templates-en-us.php:29` | The same template body for `en_US`. | Invite-only | Same copy. Small. |

### 9.2 Phones, country, curriculum, currency

No timezone constant lives in the engine or the onboarding paths. `config/app.php` uses `UTC`. `Africa/Kampala` appears on the demo nightly schedule in `config/klassapp.php`, which is outside onboarding, so it is not flagged. There is no UNEB "Division 1" constant on the onboarding path. The grade bands in `config/grading_uganda.php` are what onboarding seeds, and those are flagged.

Post-onboarding Toshi fee and payment replies still print `UGX` (`AgentToshi` around the payment and fee-balance prompts, and `ToshiActionService` fee and payment results). They are outside the onboarding steps, so they are not flagged here. The same is true of WhatsApp fee messages that print `UGX`.

| File:line | What it does | Decision | Proposed fix and rough size |
|---|---|---|---|
| `app/Helpers/WhatsAppPhoneHelper.php:6` | `normalise()` rewrites a leading `0` or any short number to `+256`. | E.164 | libphonenumber with the school country as the region. Medium. The method is also the WhatsApp send fallback. |
| `app/Helpers/WhatsAppPhoneHelper.php:241` | `validate()` accepts only `+256` numbers matching `7[0578]`. | E.164 | Same library. Small once `normalise()` moves. |
| `app/Helpers/WhatsAppPhoneHelper.php:190` | `readable()` special-cases a 12-digit `256` number. | E.164 | Format with the library. Small. |
| `app/Services/SchoolSignupBootstrapService.php:138` | Signup strips a leading `0` and prefixes `+256` for `7[0578]` numbers. | E.164 | libphonenumber, school country as the region. Small. |
| `app/Support/PhoneCountry.php:59` | Onboarding normalisation uses `config/phone_countries.php`, then a coarse 8–12 digit fallback. | E.164 | Replace the list with libphonenumber. Medium. |
| `config/phone_countries.php:13` | Calling codes and national lengths are a fixed list, including Uganda `256` / 9 digits. | E.164 | Same replacement. Medium. |
| `app/Livewire/AgentToshi.php:6884` | A missing school phone is stored as `0700000000`. | E.164 | Leave the phone empty. Small. |
| `database/seeders/UsersSiteAdminTableSeeder.php:25` | The production seeder defaults one mobile to `+256700000000`. | E.164 | No country default. Small. |
| `database/seeders/UsersSiteAdminTableSeeder.php:34` | The second site-admin mobile uses the same default. | E.164 | Same. Small. |
| `app/Livewire/ManualOnboardingWizard.php:94` | The wizard property starts as `Uganda`. | Profile data | Default from the country profile, or no default. Small. |
| `app/Livewire/ManualOnboardingWizard.php:1905` | A blank saved country is shown as `Uganda`. | Profile data | Show the saved value only. Small. |
| `app/Livewire/AgentToshi.php:6897` | Create mode stores `Uganda` when no country was chosen. | Profile data | Require a country. Small. |
| `app/Onboarding/Steps/StepSchemaCatalog.php:93` | The Toshi v2 country field defaults to `Uganda`. | Profile data | No country constant. Small. |
| `app/Onboarding/Steps/StepSchemaCatalog.php:97` | The country answer defaults to `Uganda`. | Profile data | Same. Small. |
| `app/Onboarding/Steps/StepSchemaCatalog.php:506` | An empty `countries` table falls back to Uganda, Kenya, and Tanzania. | Profile data | Use the country table only. Small. |
| `resources/views/livewire/partials/manual-wizard-step-fields.blade.php:52` | The wizard select uses that same three-country fallback. | Profile data | Same. Small. |
| `app/Services/OnboardingStepsService.php:194` | `isUganda()` still special-cases the country name. | Profile data | Ask the country profile. Small. |
| `app/Onboarding/Steps/Steps/EmisStep.php:40` | The skip summary says EMIS applies only to Uganda. | Profile data | Use the profile's ministry-code rule. Small. |
| `app/Onboarding/Country/UgandaOnboardingProfile.php:9` | EMIS, `UGX`, `uneb`, the three term dates, and the class preset are constants on the Uganda profile. | Profile data | Move them into preset data. Medium. Keeping a Uganda profile is fine; the constants should not be the only source. |
| `app/Onboarding/Country/GenericOnboardingProfile.php:39` | A country with no profile is offered `uneb`. | Profile data | No curriculum until the profile says so. Small. |
| `app/Onboarding/Country/GenericOnboardingProfile.php:47` | That same fallback copies Uganda's term dates. | Profile data | Terms come from the profile. Small. |
| `app/Services/OnboardingEngine.php:127` | `saveCurriculum` allows only `uneb`, `cambridge`, `montessori`, and `other`. | Profile data | Allow the curricula the profile offers. Medium. |
| `app/Onboarding/Steps/Steps/CurriculumStep.php:10` | Toshi v2 offers that same list, with UNEB first. | Profile data | Options from the profile. Small. |
| `resources/views/livewire/partials/manual-wizard-step-fields.blade.php:65` | The wizard select labels the first option "UNEB (Uganda National Examinations Board)". | Profile data | Options from the profile. Small. |
| `app/Livewire/ManualOnboardingWizard.php:89` | The wizard opens on `uneb`. | Profile data | Open on the profile default, which may be empty. Small. |
| `app/Livewire/ManualOnboardingWizard.php:1902` | A blank saved curriculum is shown as `uneb`. | Profile data | Show the saved value only. Small. |
| `app/Livewire/AgentToshi.php:146` | The curriculum property starts as `uneb`, and an unanswered picker can be committed. | Profile data | Do not commit a constant. Small. |
| `app/Livewire/AgentToshi.php:642` | A restored draft falls back to `uneb`. | Profile data | Keep the draft empty. Small. |
| `app/Livewire/AgentToshi.php:4895` | The words `uganda` and `ncdc` map to `uneb`. | Profile data | Map from the profile. Small. |
| `app/Livewire/AgentToshi.php:6888` | Create mode stores `uneb` when no curriculum was chosen. | Profile data | Require a curriculum from the profile. Small. |
| `database/migrations/2026_08_03_235031_make_schools_curriculum_nullable.php:43` | `schools.curriculum` is `NOT NULL DEFAULT 'uneb'`. The migration already ran; the default remains in the database. | Profile data | A later migration drops the default. Medium, because existing rows stay. |
| `app/Onboarding/Steps/Steps/UnebCenterStep.php:17` | The exam-centre question asks for a UNEB centre number. | Profile data | The profile names the exam centre, or the step is absent. Small. |
| `app/Services/OnboardingEngine.php:196` | `saveUnebCenter` writes `uneb_center_number`. | Profile data | A profile-named centre field. Medium, with the column. |
| `resources/views/livewire/partials/manual-wizard-step-fields.blade.php:103` | The wizard label is "UNEB centre number". | Profile data | Label from the profile. Small. |
| `app/Onboarding/Steps/Steps/TermsStep.php:108` | Typing `uneb` accepts the default term calendar. | Profile data | The profile's term preset, under a neutral word. Small. |
| `app/Services/SchoolCategorySeeder.php:29` | Categories are Nursery, Primary, O-Level, and O-Level + A-Level. | Profile data | Categories from preset data. Large, because seeding, the wizard, and Toshi all read this list. |
| `app/Services/SchoolCategorySeeder.php:63` | Seeded classes are Baby/Middle/Top, Primary One–Seven, and Senior One–Six. | Profile data | Class names from the preset. Large, same callers. |
| `app/Onboarding/Country/SchoolTypePreset.php:59` | `uganda()` is the only preset, and it reads those class lists. | Profile data | One preset per country profile. Medium. |
| `app/Onboarding/Country/SchoolTypePreset.php:27` | A-level streams are the constants PCM, BCM, HEG, and MEG. | Profile data | Streams from the preset. Small. |
| `app/Services/OnboardingEngine.php:311` | Candidate classes are P.7, S.4, and S.6 only. | Profile data | The profile says which classes take a board number. Medium. |
| `app/Services/OnboardingEngine.php:357` | Class names are mapped onto nursery, O-level, and A-level. | Profile data | Mapping from the preset. Medium. |
| `app/Services/OnboardingEngine.php:394` | Ordinals cover Primary 1–7 and Senior 1–6 only. | Profile data | Aliases from the preset. Medium. |
| `app/Livewire/AgentToshi.php:7263` | `curriculumDefaults()` is the NCDC nursery, P1–P7, and S1–S6 list. | Profile data | Delete this list and use the preset. Medium. |
| `app/Livewire/ManualOnboardingWizard.php:107` | The class field opens on `P1`. | Profile data | Open on the preset's first class, or empty. Small. |
| `app/Helpers/GradingHelper.php:50` | Onboarding seeding loads `config/grading_uganda.php`. | Profile data | Grading scales from the profile. Medium. |
| `config/grading_uganda.php:27` | The file is the Uganda primary nine-band scale, the O-level A–E scale, and the A-level points scale. | Profile data | Preset data per curriculum. Large, because report cards read it too. |
| `app/Onboarding/Country/UgandaOnboardingProfile.php:9` | Currency is the constant `UGX`. Listed again because fee screens do not read it. | Profile data | Callers read `currency()`. Small on the profile, medium at the screens. |
| `app/Livewire/ManualOnboardingWizard.php:1770` | The review prints fee amounts as `UGX`. | Profile data | Use the profile currency. Small. |
| `resources/views/livewire/partials/manual-wizard-step-fields.blade.php:713` | The fee list prints `UGX`. | Profile data | Same. Small. |
| `resources/views/livewire/partials/manual-wizard-step-fields.blade.php:730` | The amount label says "Amount (UGX)". | Profile data | Same. Small. |
| `app/Livewire/AgentToshi.php:2459` | The onboarding fee reply says `UGX`. | Profile data | Use the profile currency. Small. |
| `resources/views/livewire/agent-toshi.blade.php:423` | The onboarding fee list prints `UGX`. | Profile data | Same. Small. |
| `resources/views/livewire/agent-toshi.blade.php:434` | The amount placeholder says "Amount (UGX)". | Profile data | Same. Small. |
| `resources/views/livewire/agent-toshi.blade.php:1361` | The second fee list prints `UGX`. | Profile data | Same. Small. |
| `resources/views/livewire/agent-toshi.blade.php:1372` | The second amount placeholder says "Amount (UGX)". | Profile data | Same. Small. |
| `app/Livewire/AgentToshi.php:6882` | A missing school email uses `@klassapp.sch.ug`. | Profile data | A neutral domain, or no invented email. Small. |
| `app/Livewire/AgentToshi.php:6920` | A missing admin email uses `.sch.ug`. | Profile data | Same. Small. |
| `app/Services/ToshiActionService.php:1435` | A generated parent email uses `@school.ug`. | Profile data | A neutral domain. Small. |
| `app/Services/ParentLinkService.php:169` | A generated parent email uses `@klassapp.sch.ug`. | Profile data | A neutral domain. Small. |
