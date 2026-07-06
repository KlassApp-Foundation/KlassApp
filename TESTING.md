# KlassApp Test Coverage

> **Last updated**: 2026-07-04
> **Total tests**: 84 (81 pass, 3 known pre-existing failures)
> **Browser E2E**: 0 — the single biggest testing gap (see §4)

---

## 1. Automated Test Suites

### 1.1 Toshi Onboarding (`tests/Feature/Toshi/ToshiOnboardingTest.php`) — 7 tests

| Test | Assertions | What it covers |
|---|---|---|
| `super_admin_can_mount_toshi_in_create_mode` | 2 | Component initializes in a valid mode/step |
| `curriculum_defaults_returns_primary_classes` | 1 | setSchoolType pre-populates standards |
| `edit_navigation_from_review_goes_to_correct_step` | 2 | Clicking "← Edit" from review clears state |
| `normalize_uganda_phone_validates_correctly` | 2 | Invalid phone doesn't crash; valid phone accepted |
| `onboarding_approve_allows_commit_over_existing_student` | 3 | Second commit() is a no-op (idempotency guard) |
| `onboarding_enforces_plan_limit_for_students` | 7 | Full flow: 4 students injected, limit=2 → 2 created, limit note set |
| `freemium_onboarding_completes_successfully` | 9 | Full flow: Freemium plan, all DB records valid (CurrentPlan, Subscription, AcademicTerms, students) |

**Gap**: All 7 tests call `Livewire::test()` directly, bypassing the HTTP middleware stack. They do not verify that the component is reachable through a real URL, that the redirect chain works, or that the browser renders the Toshi pill/panel correctly.

### 1.2 Toshi Assistant Agent (`tests/Feature/Toshi/ToshiAssistantAgentTest.php`) — 9 tests

| Test | What it covers |
|---|---|
| `it_returns_null_when_laragent_disabled` | LarAgent returns null when feature flag is off |
| `it_enforces_daily_budget_limit` | Daily LLM budget cap works |
| `budget_is_per_date` | Budget resets daily |
| `tool_add_student_returns_correct_structure` | #[Tool] method output format |
| `tool_list_classes_returns_correct_structure` | #[Tool] method output format |
| `tool_list_teachers_returns_correct_structure` | #[Tool] method output format |
| `laragent_config_has_nvidia_provider` | Config points to Nvidia NIM |
| `feature_flag_defaults_to_disabled` | env var leak guard (reflection-based) |
| `keyword_router_handles_hello` | Basic keyword routing works |

### 1.3 Plan-Limit Enforcement (`tests/Feature/PlanLimitEnforcementTest.php`) — 12 tests

| Test | What it covers |
|---|---|
| `enforce_plan_limit_passes_when_under_limit` | addStudent works below limit |
| `enforce_plan_limit_blocks_when_at_limit` | addStudent blocked at limit |
| `enforce_plan_limit_passes_when_no_plan_configured` | No CurrentPlan → no enforcement |
| `enforce_plan_limit_blocks_teachers_separately` | Teacher limit independent of student limit |
| `enforce_plan_limit_blocks_admins_separately` | Admin limit independent |
| `toshi_add_student_blocked_when_at_plan_limit` | ToshiActionService respects student limit |
| `toshi_add_teacher_blocked_when_at_plan_limit` | ToshiActionService respects teacher limit |
| `toshi_add_coadmin_blocked_when_at_plan_limit` | ToshiActionService respects admin limit |
| *4 more (detailed edge cases)* | |

### 1.4 Data Isolation (`tests/Feature/ParentCrossSchoolIsolationTest.php`) — 5 tests
### 1.5 Student Academic Scoping (`tests/Feature/StudentAcademicLatestScopingTest.php`) — 4 tests

**Total**: 9 tests covering cross-school data leaks and academic year scoping. These were the regression tests added when fixing the `studentAcademicLatest()` scope bug and the `ParentController` cross-school data leak.

### 1.6 Payroll (`tests/Feature/PayrollBatchEndToEndTest.php`) — 2 tests

Batch preview computation and batch confirm creates payroll items.

### 1.7 WhatsApp (`tests/Feature/WhatsApp/`) — 11 tests

- **WebhookValidationTest** (7 tests): event validation, missing/invalid fields, message body rules
- **OutboundNotificationTest** (4 tests): phone normalization, validation, message formatting

### 1.8 Unit Tests (`tests/Unit/`) — 31 tests

- **TeacherDesignationTest** (11 tests): role assignment, dedup, removal, null handling
- **UgandaPayrollCalculatorTest** (20 tests): PAYE brackets, NSSF caps, LST months, full computation structure

### 1.9 Auth Regression (`tests/Feature/Auth/LoginRegressionTest.php`) — 3 tests **KNOWN FAILURES**

| Test | Status | Root Cause |
|---|---|---|
| `test_guest_is_redirected_from_admin_dashboard_to_login` | ❌ Fails | `no such table: users` |
| `test_login_fails_with_invalid_password` | ❌ Fails | Same — no DB tables |
| `test_login_succeeds_with_valid_credentials` | ❌ Fails | Same — no DB tables |

**Issue**: `LoginRegressionTest` does not use the `RefreshDatabase` trait. Its `setUp()` calls `ensureTestUser()` which runs `DB::table('users')->updateOrInsert(...)` against SQLite `:memory:`, where no tables exist because migrations haven't run.

**Fix**: Either (a) add `use RefreshDatabase` + seed via factories, or (b) pin this test file to MySQL so the existing `ensureTestUser` works against persistent tables. Option (a) is preferred for isolation.

**Bug filed**: This has been a recurring asterisk on every test run for at least a month. Worth finally fixing given it's the only thing between "all tests green" and "83 pass, 3 known failures" every time.

---

## 2. Known Gaps

### 2.1 Browser/UI Layer — ZERO coverage (Critical)

**There are zero browser-level tests across the entire application.** Every test calls PHP methods directly. The following have never been verified in a real browser:

- Toshi onboarding 15-step UI (floating pill, panel, progress indicators, form interactions)
- Login/registration flow rendering
- Admin dashboard rendering and navigation
- Any JavaScript-driven feature (Vue components, Livewire dynamic updates)
- Mobile responsive layouts
- Form validation error display
- Flash messages and toasts

### 2.4 Livewire + Alpine.js method name collision (Critical discovery)

**Naming a Livewire public method `commit` (or any Alpine-reserved keyword) causes silent interception.** Alpine.js v3 reserves `commit` as a store mutation keyword. When Livewire tries to dispatch `wire:click="commit"` or `c.call('commit')`, Alpine intercepts the method name before Livewire can send it. The `/livewire/update` request arrives with an empty `calls: []` array — no error is thrown, no exception is logged. The method simply never executes.

**Fix**: Rename the method to avoid Alpine keywords (e.g., `confirmOnboarding` instead of `commit`). This applies to all Alpine-reserved keywords: `commit`, `init`, `destroy`, `data`, `$data`, `$el`, `$refs`, `$store`, `$watch`, `$dispatch`, `$nextTick`, `$root`, `$id`.

**Audit scope**: All 43 `wire:click` method names in `agent-toshi.blade.php` were checked. Only `commit` was affected. This is a project-wide concern — any Livewire component with a method named `commit` would silently fail in the same way.

**This is the single biggest testing gap across the whole platform**, not just Toshi. The backend logic is well-covered (80 passing tests), but the frontend rendering layer has zero automated verification.

### 2.2 Middleware/Routing — Coverage gap exposed by Toshi

The superadmin redirect loop (`MustBeSchoolAdmin` → `/superadmin/dashboard` → 404) was only discovered during manual E2E investigation. No test verifies that a superadmin can actually reach any page after login. The routing layer and middleware chain are entirely untested.

### 2.3 MySQL strict-mode parity

Tests run against SQLite `:memory:` (per `phpunit.xml`). Production runs MySQL 8.0.46 with Laravel setting `sql_mode=NO_ENGINE_SUBSTITUTION`. The difference in ENUM handling (SQLite CHECK constraints vs MySQL ENUM lax mode) caused a bug where `status => 'active'` silently corrupted Subscription data in production but was caught as a hard error in tests. Any test that touches ENUM or column constraint behavior may behave differently between the two databases.

---

## 3. Test Infrastructure

| Setting | Value |
|---|---|
| **Test framework** | PHPUnit 10.5 |
| **PHPUnit config** | `phpunit.xml` |
| **Database** | SQLite `:memory:` (via phpunit.xml) |
| **Migration strategy** | `RefreshDatabase` trait on most tests |
| **CI** | Not configured (GitHub Actions workflow commented out) |
| **Browser E2E** | None |

### Running Tests

```bash
# All tests
php artisan test --compact

# Single suite
php artisan test --compact tests/Feature/Toshi/

# Single test
php artisan test --compact --filter=onboarding_enforces_plan_limit
```

---

## 4. Recommended: Browser E2E Coverage Plan

### Why Laravel Dusk

Laravel Dusk is the natural choice for this stack:
- **Zero additional toolchain** — no Node.js/npm dependency beyond what's already installed
- **Native Laravel integration** — shares the same app bootstrap as PHPUnit tests
- **Database seeding** — Dusk tests can use the same `RefreshDatabase` + factory patterns
- **Headless or headed** — runs Chrome headless in CI, visible browser for local debugging
- **Already compatible** — Laravel 10 ships Dusk support; only needs `composer require --dev laravel/dusk`

(Playwright was evaluated during the July 4 E2E investigation and works, but it's a separate toolchain. Dusk is the standard Laravel choice and avoids introducing another dependency.)

### Scope: Phase 1 (Minimum Viable)

1. **Login flow** (`LoginRegressionTest` replacement)
   - Navigate to `/login`, fill form, submit, verify redirect to dashboard
   - Verify invalid credentials show error message
   - Verify unauthenticated user is redirected from `/admin/dashboard` to `/login`

2. **Toshi onboarding** (the flow that caused this month's confusion)
   - Log in as superadmin
   - Click the Toshi floating pill to open the panel
   - Walk through plan selection → school info → admin account → all 15 steps
   - Type "create school" if Toshi starts in assistant mode
   - Verify the success card appears after commit
   - Verify database records via Dusk's `assertSeeInDatabase()` or a follow-up query

### Scope: Phase 2 (After Routing Fix)

Once the superadmin redirect bug and Toshi rendering gap are resolved:

3. **School admin dashboard** — basic navigation, sidebar menus load
4. **Student CRUD** — add/edit/delete student through the web UI
5. **Fee payment recording** — the School Pay webhook integration

### Implementation Notes

- Dusk tests live in `tests/Browser/` and use ChromeDriver
- The `phpunit.dusk.xml` config switches to a separate MySQL database (not SQLite) to match production's ENUM/constraint behavior
- Each test should clean up its own test data (Dusk provides `RefreshDatabase` support)
- The existing `LoginRegressionTest` can be migrated to Dusk (replacing the HTTP-based assertions with real browser interactions) when Phase 1 is implemented — this would also fix the pre-existing SQLite table-not-found failure since Dusk tests would use MySQL

### Non-goals (for now)

- Visual regression testing (screenshots diffs)
- Performance/load testing
- Mobile device emulation beyond responsive viewport checks
- Cross-browser testing beyond Chrome
