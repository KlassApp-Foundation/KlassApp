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

### 4.4 Phase 1D — cleanup

1. Remove `ManualOnboardingWizard::save*()` private methods once all are engine-wired (or keep as thin pass-throughs).
2. Remove `AgentToshi::curriculumDefaults()` and `handleExams()`/`saveExam()` if the product confirms exam removal.
3. Remove `AgentToshi::$mandatorySteps` and `isStepMandatory()`.
4. Add the permanent parity test `OnboardingEngineParityTest` (see §5) and run it.

## 5. Permanent parity test design

Test file: `tests/Feature/Onboarding/OnboardingEngineParityTest.php`

Two canonical scenarios will be exercised through **both** the manual wizard Livewire component and the Toshi `commitAll()` Livewire component (or, after extraction, directly through `OnboardingEngine` calls for each path).

### Scenario A: “Zero teachers/students, Uganda/UNEB, free plan”
- Inputs:
  - Name: `Sunrise Academy`
  - Country: `Uganda`, Curriculum: `uneb`, School category: `primary`
  - EMIS: `EMIS-1001`, UNEB center: skip
  - Academic year: current year
  - Classes: `P1`
  - Subjects: `Mathematics`
  - Teachers: none
  - Students: none
  - Terms: `Term 1`
  - Fees: `Tuition` (amount 0)
  - WhatsApp: `+256700111222`
  - Plan: `Freemium` (amount 0)

### Scenario B: “School with people, paid plan, co-admin”
- Inputs:
  - 3 teachers (one with email, two without)
  - 5 students across `P1` and `P2`
  - 2 terms
  - 1 fee `Tuition` with amount `100000`
  - Plan: paid plan (e.g. `Growth`) → expects `TrialService::startTrial()` to be invoked.

### DB assertions (identical across both paths)
For each scenario, assert the following after onboarding is marked complete:
- `schools` row: same `name`, `slug`, `curriculum`, `registration_country`, `ministry_code`, `uneb_center_number`, `school_category`, `toshi_enabled = 1`
- `academic_years`: 1 row, `name` and `description`, `status = 1`
- `standards` + `sections` + `standard_links`: same count and names
- `subjects`: same per-class set
- `users`:
  - 1 admin (`usergroup_id = 3`)
  - N teachers (`usergroup_id = 5`)
  - M students (`usergroup_id = 6`)
  - All have `Userprofile` rows
  - All have deterministic, school-scoped emails
- `teacherlinks` and `student_academics`: consistent linking to `standard_links`
- `academic_terms`: same names and dates
- `fees_categories`: same names and amounts
- `whatsapp_users`: 1 row for the admin, `opted_in = 1`, `verified_at` not null
- `current_plans` + `subscriptions`: same `plan_id`; paid plans have `trial` flags set
- `OnboardingStepsService::hasBlockingIncompleteSteps($school, $admin->id) === false`

The test must fail if any of the 9 resolved disharmonies regresses.

## 6. Non-goals for this design pass

- No UI reskinning; the engine is backend-only.
- No new `OnboardingStepsService` step keys (except removing `exams` from Toshi if product agrees).
- No migration or schema changes.
- No changes to `SchoolSignupBootstrapService` except that `OnboardingEngine::createAdmin` will supersede its admin-creation logic during Toshi create flow.

## 7. Open product questions before Phase 2

1. Do we keep the Toshi chat UI as the primary path, or should the manual wizard become the canonical path?
2. Should Toshi ask `school_category` explicitly, or continue deriving it from `schoolType`/`hasNursery`?
3. Do we remove the `exams` onboarding step, or should exams get a real `OnboardingStepsService` step and persistence?
4. Should the manual fee step require an amount (current behavior) or accept name-only (engine proposal)?
