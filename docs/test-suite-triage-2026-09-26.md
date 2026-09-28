# Test-suite failure categorization — 2026-09-26 (final)

Scope: full-suite red count on `main` @ `cd4a231c`, run inside the `sms-app` Docker
container. Two runs captured: `/tmp/suite-full.txt` (raw env, 1705 tests, 99E+58F=157
red) and `/tmp/suite-run2.txt` (Toshi model + S3 vars neutralized, 1705 tests,
87E+57F=144 red). Every one of the 144 has been read, grouped, and assigned a root
cause. **No fixes made — documentation only.**

## Headline

- **144 red = 109 environment/harness failures (75%) + 35 test-drift failures (25%) + 0 confirmed new app bugs.**
- The suite is not lying about the app: neutralize the container env leaks and the
  demo-seed contamination and 3 of every 4 reds disappear. The remaining third bucket is
  **stale tests** — the app changed on purpose (mostly Sep 12–21 refactors: nav
  consolidation, display-name uppercase, Chart.js component, onboarding steps), and the
  tests were never updated. Those tests still encode valuable contracts and should be
  updated, not deleted.
- **Real findings that deserve follow-up** are marked ⚠️ below (design gaps and
  incident-adjacent behavior, not new regressions).

## Environment / harness families (109 tests)

### F3 — Demo-seed `schools.id` collision — 43 tests
- **Verdict: environment/harness issue.**
- Migration `2026_09_22_010000_add_is_demo_to_schools_and_seed_demo_data.php` seeds two
  demo schools into every freshly-migrated DB (including every `RefreshDatabase` test
  DB) claiming ids 1 and 2. Tests that hardcode `schools.id = 1` (KlsIdFormatGuardTest,
  TeacherDesignationTest, SchoolSubadminAdminAccessTest, TeacherApprovalRoutesTest,
  ToshiClassExtractionTest, AcademicTermPositionLabelTest) hit
  `UNIQUE constraint failed: schools.id`.
- **Fix direction (not done):** seed demo data only outside tests
  (`Schema::hasTable('migrations') && app()->environment('local')` guard or check
  `runningUnitTests()`), or stop hardcoding id=1 in tests.

### F3b — Demo-parent contamination via `firstOrFail` — 11 tests
- **Verdict: environment/harness issue (same root migration as F3, different surface).**
- ParentPortalServiceTest (6), ParentCrossSchoolQueryScopingTest (4),
  ParentCrossSchoolLinkingTest (1). These create their own parent via
  `ParentLinkService::linkByStudentId(...)` then fetch it with
  `User::where('usergroup_id', 7)->firstOrFail()` — which returns the **demo parent**
  (Joseph Wandera, school_id=1) instead. Assertions like `assertNull($parent->school_id)`
  fail with "1 is null"; `assertSame(1, User::where('usergroup_id',7)->count())` finds 5
  (2 demo + created + linked). 
- ⚠️ Side finding: this is a live instance of standing rule #18 (never identify a record
  by non-unique lookup). The tests do it; the same pattern in app code would be a real
  multi-tenant bug. Fix direction: query by the known id/email the test itself created.

### F4b — Toshi LLM API key empty at container level — 44 tests
- **Verdict: environment issue (by design — the guard is correct).**
- The `sms-app` container has `TOSHI_LLM_API_KEY=` (empty) baked into its environment
  (docker-compose `env_file: .env`). Laravel's immutable Dotenv means neither
  `.env.testing` nor phpunit.xml `<env>` can override it. 44 Toshi tests (Platform
  tools, Adversarial isolation, SchoolAdmin tools, ToshiLlmStatusCommandTest) fail with
  `MissingToshiLlmApiKeyException` before `Http::fake` is ever consulted — the
  2026-08-02 incident guard throws first, correctly.
- **Fix direction:** export a dummy key when running the suite in-container
  (`docker exec -e TOSHI_LLM_API_KEY=test ...`), as this session verified.
  Run2 did neutralize the *model/host* mismatch (F4a: 51 tests, `gpt-4o-mini` on
  deepseek URL → `AmbiguousToshiLlmConfigException`) by exporting
  `TOSHI_LLM_MODEL=deepseek-chat`, but run2's script forgot the key, so those 51 became
  the 44 MissingKey errors — F4a and F4b are two layers of the same family (95 combined
  in run1; only the 44 key-layer remains when the model is fixed).

### F5 — CSRF 419s from `APP_ENV=local` container leak — 10 tests (+1 adjacent)
- **Verdict: environment issue. Root cause newly confirmed empirically this session.**
- The container environment carries `APP_ENV=local`. PHPUnit's
  `<env name="APP_ENV" value="testing"/>` **without `force="true"` cannot override an
  already-set variable**, so `config('app.env') === 'local'` and
  `runningUnitTests() === false` inside the suite. The framework's `VerifyCsrfToken`
  test bypass therefore never fires → every unauthenticated web POST 419s.
- Proven with a probe script (`/tmp/csrf-probe.php`) showing
  `APP env: local / runningUnitTests(): false / getenv: testing`, and by re-running the
  affected files with `-e APP_ENV=testing`: ParentWebAuthShellTest, AuthPreviewTest,
  ClassTeacherInviteControllerTest, CrossSchoolSectionDeleteTest,
  IntegrationsSettingsTest, SchoolDetailsTenantIsolationTest went from 12 failures to 1
  (the 1 is unrelated — see A8). SchoolDetailsTenantIsolationTest passed 5/5.
- Same leak class as the TOSHI_LLM key: **container-level env beats .env/.env.testing/phpunit.xml.**
- **Fix direction:** `docker exec -e APP_ENV=testing` when running tests in-container,
  or add `force="true"` to phpunit.xml's APP_ENV line.

### F6 — `node` not installed in the container — 1 test
- **Verdict: environment issue.**
- EmptyStateProductDemoTest shells out to `node` to validate JS; the app container has
  no Node runtime (`sh: 1: node: not found`, exit 127).

## Test-drift families (35 tests) — app changed on purpose, tests not updated

### A1 — `SubscriptionService::approve()` never creates a `current_plans` row — 4 tests
- **Verdict: ⚠️ design gap (test encodes a contract the app never implemented — or a
  contract that was lost).**
- SubscriptionCurrentPlanTest (added Aug 12, f57da131) asserts that approving a
  subscription creates/overwrites `current_plans` (free → CurrentPlan running; paid →
  trial row). Today's `SubscriptionService::approve()` only flips status+dates; **no code
  path anywhere** creates a CurrentPlan on approval (verified by history search back to
  ff50fa22 Aug 1). The test has never had a matching implementation — it appears to have
  been authored against the intended contract (knowledge.md's own decision record:
  "CurrentPlan is canonical source for plan limits") and landed in a reports-fix commit
  without its feature.
- **Follow-up decision needed:** either implement approve→CurrentPlan (Toshi
  `persistSelectedPlan` already does this for chat-selected plans; the Filament approve
  action calls the plain service) or update the test to document the current behavior.

### A2 — Freemium assign blocked by `student_size` — 3 tests
- **Verdict: test bug (stale helper).**
- FreeTierPlanServiceTest (2) + ToshiOnboardingTest::freemium (1). The Sep 10
  `student_size` wizard step (fe846881/#493) added a new blocking step that
  `completeAllContentExceptPlan()` never sets — proven by probe:
  `nextBlockingIncompleteStep` returns `student_size`, so `contentOnboardingComplete()`
  is false and `assignIfEligible()` correctly refuses. Known-issue-logged Aug 12 in
  knowledge.md as "Plan service setup mismatch" — root cause now identified.

### A3 — Onboarding step-machine drift — 12 tests
- **Verdict: test bugs (stale expectations), with two ⚠️ behavioral questions.**
- `student_size` step insertion (Sep 10) + "Subjects checkpoint" wizard landing (Sep 11,
  de312b0c) + complete-mode mount reconcile (Sep 7, 11e62e6c) changed step indices and
  flows on purpose:
  - WizardPreviousFromTeachersTest (2): Previous from teachers now lands on `subjects`
    (deliberate checkpoint), test expects old order.
  - ToshiSchoolCategoryJumpResumeTest (2): mount now jumps to `student_size`, tests
    expect `school_category`/`emis`.
  - ToshiSchoolNameRoutingTest (2): ⚠️ the Aug-24 stale-actionStep hijack fix is **still
    implemented** (`jumpToIncompleteOnboardingStep` clears actionStep) — but the two
    tests now seed state where the *next incomplete step* differs from what the tests
    assume, so the reconcile lands elsewhere and their assertions on `actionStep` null
    fail. Behavior needs re-review: is stale `onboarding_country` actionStep being
    honored again, or is the test's fixture just out of step order? Evidence leans
    test-stale (fixtures assume pre-student_size order).
  - ToshiCompleteModeFourFindingsTest (1): step index 15 vs 16 (`school_pay` inserted).
  - ManualOnboardingParityTest (2): ⚠️ PR #766 (Sep 21) made `moto`/`about_us` required
    in DetailRequest; the parity test posts payloads without them → session errors.
    The test needs updating, but note this makes the onboarding School Details form
    require moto/about_us — worth confirming that was intended for onboarding parity.
  - SaveStudentsStreamMatchTest (1): test asserts unknown stream → ValidationException;
    app now deliberately auto-creates streams (e61174f9 Sep 11, 1d3d64dd Sep 15). Test
    encodes the old contract.
  - ManualUiWave2SchoolDetailsDsTest (1): expects `id="map_canvas"` on the legacy TW
    form; the map half-feature was gated in PR #766.
  - ToshiOnboardingTest::onboarding_enforces_plan_limit (1): "Should be at review step
    15" — step-count drift again.

### A4 — Stale auth tests after Sep 12/15 login changes — 2 tests
- **Verdict: test bugs.**
- IsResetEnforcementTest expects teacher login → `/admin/dashboard`; `AuthRedirectHelper`
  (PR #611, Sep 15) deliberately sends usergroup 5 → `/teacher/dashboard`. Second test
  expects view `auth.force-change-password`; the live route
  `password.force-change` renders `auth.preview.force-change-password` since the Sep 12
  preview cutover (0b794479, PR #536). Both behavioral changes are documented PRs; tests
  were never updated.

### A5 — Stale UI-copy / name-format tests — 10 tests
- **Verdict: test bugs (assertions on exact markup/copy that later PRs changed).**
- Display-name uppercase (08ea9028, Sep 4: `User::displayName` =
  `mb_strtoupper(stripTrailingDigitSuffix(...))`) breaks case-sensitive `assertSee` on
  "Alice Child"/"Shared Name"/"James Okello" in WhatsAppToshiChannelTest (2),
  ToshiDuplicateNameGuardTest (1), ExamMarksheetEnrolledStudentsTest (1). Note: those
  three failures are *guard/list renderers correctly using the new uppercase
  displayName* — tests expect mixed case.
- Nav consolidation (PR #705, Sep 20) deleted `layouts/parent/navigation.blade.php`;
  ProfileDropdownRoleAwareTest still reads that file.
- Chart.js component rewrite (PR #740, Sep 21) removed `var femaleCount = 476;` inline
  JS → DashboardGenderChartTest (2) fail on the old marker strings.
- Landing tower refactor (PR #680, Sep 19) removed `data-toshi-tower="1"` attribute;
  LandingPreviewV3Test still asserts it.
- ToshiPiece2DockingPillContractTest (2): app.blade.php now includes the toggle via
  `toshi-embed` partial (PR #724, Sep 21) — test asserts inline markup; and CSS
  breakpoints changed 640px→1279px (6d3142b1, Sep 21) — test asserts the old breakpoint.

### A6 — E2E live-LLM tests stale after complete-mode reconcile — 2 tests
- **Verdict: test bugs (also inherently live-LLM tests; arguably should be skipped in CI).**
- ToshiE2EVerificationTest (from Jul 16, d8683ddd) mounts AgentToshi for a schooladmin
  of a bare school; since the Sep 7 mount reconcile, that admin is pulled into complete
  mode at `student_size`, so "hello" is consumed by the student-size chooser (not the
  keyword-router greeting that must mention Toshi) and the LLM round-trip needs a real
  key/endpoint the suite can't provide. No Http::fake in this file — it was written as a
  live-verification test.

### A8 — Magic-link test expects pre-confirm-page behavior — 1 test
- **Verdict: test bug.**
- ParentWebAuthShellTest::test_parent_reaches_dashboard (Aug 28) expects GET of the
  signed link to redirect straight to the dashboard; PR c43617b1 (Sep 3) deliberately
  added a POST confirm page (to stop WhatsApp link previews burning the nonce). Test
  not updated. (The checkstatus sibling failure in this file was F5-adjacent env noise —
  it passes solo with APP_ENV=testing.)

## Residual / previously-known

- 2 risky tests (ParentMagicLoginWhatsAppTest — no assertions) — separate hygiene item,
  not part of the 144.
- 360 PHPUnit deprecations — PHPUnit 10.5-style warnings, future upgrade debt.
- Suite totals: run1 157 red / run2 144 red / with all env neutralized (modeled):
  **~35 red, all test-drift.** After additionally fixing F3/F3b (demo-seed guard), the
  modeled clean count is **0 environment failures and 35 stale tests to update.**

## Priority follow-ups (decision, not fixes)

1. **Container test invocation** should always use
   `docker exec -e APP_ENV=testing -e TOSHI_LLM_API_KEY=dummy -e TOSHI_LLM_MODEL=deepseek-chat`
   (kills F4a+F4b+F5 = 105 of 157 run1 failures instantly). Consider adding `force="true"`
   to phpunit.xml APP_ENV so plain in-container runs work too.
2. **Demo-seed migration needs a test-environment guard** (F3+F3b = 54 tests).
3. **A1 CurrentPlan contract decision** — implement or re-scope the 4 tests.
4. **Stale-test sweep** (A2–A8 = 31 tests): each has a named PR that changed the
   behavior; update tests to the new contracts. Several encode genuinely valuable
   regression protection (onboarding parity, tenant isolation, guard messages).
5. ⚠️ Confirm PR #766's moto/about_us-required was intended to apply to the onboarding
   parity form flow (A3/ManualOnboardingParity).
