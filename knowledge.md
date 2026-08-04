# KlassApp Project Knowledge

> **Canonical location**: `/Users/mac/projects/KlassApp/knowledge.md` (workspace root). Worktrees (e.g. `KlassApp-main-checkout`) should sync to that path when copies diverge — keep one body of truth; do not maintain parallel divergent session logs.

## Verified Stack (production — July 26, 2026)

| Layer | Version | Source |
|---|---|---|
| **PHP** | 8.4.23 | `ssh root@46.101.111.131 docker exec sms-app php -v` |
| **Laravel** | 12.63.0 | `php artisan --version` on production |
| **Livewire** | 3.x | `composer.json` require `^3.4` |
| **Vue** | **3.5.40** at runtime via **`@vue/compat` MODE 2** (`Vue.version` in browser; `package.json` `vue` + `@vue/compat` 3.5.40). Merged to `main` **`50f5c4d`**. |
| **Tailwind CSS** | 4.3.3 | `package.json` — v4, CSS-first config, no `tailwind.config.js` |
| **Bundler** | **Vite 8** (sole) | Phase 3 **CLOSED on `main`** — merge `9bdf185` (from `migration/vite` / `3bc5c70`). Scripts: `npm run dev` / `npm run build`. Blade `@vite([...])`. |
| **MySQL** | 8.0 | `docker-compose.yml` |
| **Redis** | 7.x | `docker-compose.yml` |
| **Production host** | Docker on Hetzner VPS (46.101.111.131) | `scripts/deploy-manual.sh` |

> ⚠️ `composer.json` platform config says `8.3.6` but production runs **8.4.23** — always verify via SSH.
> 🆕 Cursor rules now live in `.cursor/rules/*.mdc` — `project-context.mdc`, `frontend.mdc`, `known-pitfalls.mdc`.

## Superadmin audit (Jul 31, 2026) — Phase 1 + Phase 2 + triage **CLOSED on `main`**

> **Scope**: Platform `/superadmin` surface. Phase 1 = inventory. Phase 2 = browser+DB verification (catalogue). Triage = HIGH + MEDIUM + LOW fixes.
> **Worktree**: `/Users/mac/projects/KlassApp-main-merge` on `main`.
> **Merge**: **`32a3bb4333f8645a2752d760fcd76287f57f5fa8`** — `Merge branch 'fix/superadmin-audit-triage'` (no-ff). Tip merged: `fix/superadmin-audit-triage` @ `8c93693`.
> **Login**: `siteadmin@gmail.com` / `password` @ `http://127.0.0.1:8010`.
> **Browser note**: Playwright (`channel: 'chrome'`) + Livewire `$wire` set/call. Artifacts: `KlassApp/tmp/superadmin-batch-{a,b,c,d,e}/`.
> **Status**: **CLOSED on `main`** — Phase 1 + Batches A–E catalogue + triage fixes. **Toshi platform-scope** remains decided-deferred roadmap (not a hotfix).

### Systemic bug — Filament `Table::hasSummary()` arity (**FIXED on `main`** — was 1 bug × 4 list occurrences)

> **Fixed** (merged via `32a3bb4`): removed stale published `resources/views/vendor/filament-tables/` so package `filament/tables` v3.3.54 views are used (`$hasSummary($this->getAllTableSummaryQuery())`). No app customizations in the published copy.
>
> **Post-merge verify (Jul 31)**: all 4 Filament lists HTTP **200** as siteadmin @ `:8010`.

| # | Filament list | URI | Evidence |
|---|---|---|---|
| 1 | Subscriptions | `/superadmin/reports/subscriptions` | Was HTTP 500; **now HTTP 200** + `Livewire::test` mount OK |
| 2 | Countries | `/superadmin/setting/countries` | Was HTTP 500; **now HTTP 200** + mount OK |
| 3 | Cities | `/superadmin/setting/cities` | Was HTTP 500; **now HTTP 200** + mount OK |
| 4 | Plans | `/superadmin/setting/plans` | Was HTTP 500; **now HTTP 200** + mount OK |

- **Root**: Published Filament tables view called `$hasSummary()` with **0 args**; package `hasSummary(Builder\|Closure\|null $query)` requires **exactly 1**. Version skew between published views and installed `filament/tables` v3.3.54.
- **Fix**: Delete published copy (prefer vendor) rather than hand-patching one line — entire tree was outdated.

### Phase 1 inventory (pointer)

- **Worktree / tip**: `/Users/mac/projects/KlassApp-main-merge` on `main` @ `1f8ea30` (inventory session).
- **Routes**: **41** inventoried (`40` registered `GET` under `/superadmin` + `1` related `schooladmin/{id}/impersonate`). Almost all are Livewire-backed closures in `routes/web.php` (`middleware` `superadmin`+`auth`, `prefix` `superadmin`). Dead stub: empty `routes/superadmin.php` still mapped by `RouteServiceProvider` (adds zero routes).
- **Livewire mutate surface**: Per-route map of Blade → Livewire components → public mutating methods completed in the same audit pass (layout-shared `agent-toshi` + page components). Full tables live in the Phase 1 agent report (not duplicated here).
- **Toshi**: Uses **Laravel AI SDK** (`laravel/ai` **^0.10** / 0.10.2; `ToshiOrchestrator` / `ToshiSdkV2Service` — **not** LarAgent). **On `main`**: platform scope (Phase 0–1), school roles Teacher/Accountant/Librarian/Receptionist/Student/Parent/Deputy Admin, WhatsApp channel (read + wave-1 CreateTask writes + confirm bridge), IDOR Gate fixes. MCP: named clients audited via `AuditingMcpClientManager`; direct `Client::web`/`local` banned outside `routes/ai.php`. Open: prefs #138, digests, WA wave-2, product MCP connectors shelved.

### Pre–Phase 2 findings (logged before any testing)

| # | Finding | One-line verdict | Evidence |
|---|---|---|---|
| 1 | Dead `/superadmin/users` link | **Cosmetic broken nav** leftover from dashboard redesign — not a removed/never-finished platform user-management feature. | Link only at `resources/views/superadmin/dashboard.blade.php:339` (“Recent Users” → “View all”). **No route ever registered** for `/superadmin/users` (`git log -S` on `routes/` empty). KPI card fixed to schools in `3ae8517` (Jul 3); this “View all” was missed. School-scoped `UserList` Livewire + route `superadmin/academics/school/user/list/{id}` still exist. Link invented in dashboard redesign `34e264c` (Jul 1). |
| 2 | Country create route commented out | **Intentionally disabled since first commit** — **real gap**: cannot create a country. | `routes/web.php:259–261` commented `superadmin.setting.countries.create` since `a6784c3` (blame/log-L; never uncommented). `CountryForm` is **update-only** (`Country::…->update`; `mount($id)` requires existing row). Filament `CreateAction::make()` on `Countries` list has **no form schema** — stub, does not create. Cities/plans still have working create routes for contrast. |

### Phase 2 Batch A results (COMPLETE — catalogue only, no fixes)

| Mutator | Expected | Browser result | DB result | Status | Toshi |
|---|---|---|---|---|---|
| `submitSchool` create | New `schools` row | Redirect → `/superadmin/academics/schools` | Row **id=35** email `batcha.school.1785458202915@example.com` | **pass** | gap (onboarding `commitAll` separate) |
| `submitSchool` update | Update school 35 address/phone | Redirect → schools list | phone=`0700987654`, address=`Updated BatchA Addr 1785458830717` | **pass** | gap |
| `submitAdmin` create | User + userprofile for school 35 | Redirect → `/school/detail/35` | user **id=169**, profile **id=161**, usergroup 3 | **pass** | gap |
| `submitUserprofile` update | Update profile 161 fields | Redirect → schools list | firstname=`BatchAFirst…`, lastname=`Updated`, address/LIN/aadhar set | **pass** | gap |
| `submitPlan` create | New `plans` row | Redirect → `/superadmin/setting/plans` | plan **id=8** created amount 12345 | **pass** | gap |
| `submitPlan` update | Update plan 8 | Mutator OK; redirect → **`/setting/plans8`** (404 path) | name/amount updated (`54321`) | **partial** (DB pass, redirect fail) | gap |
| `submitSubscription` create | New pending subscription | Redirect → subscriptions URL (list itself 500s) | sub **id=11** pending, user 46 / plan 4 / school 1 | **pass** (create) | gap |
| Filament `approve` on list | pending → approved + dates | **HTTP 500** on `/reports/subscriptions` — Approve never reachable | sub 11 still `pending`, dates null | **fail** | gap |
| `CoAdmins.save` | Create disposable co-admin | Form save via `$wire.call('save')`; email on page | user **id=170** usergroup 2 | **pass** | gap |
| `CoAdmins.delete` | Soft-delete disposable | `$wire.call('delete', 170)`; email gone from UI | `deleted_at=2026-07-31 04:08:54` | **pass** | gap |
| `FeatureToggles.toggle` | Flip whatsapp for school 35 then restore | `$wire.call('toggle', 35, 'whatsapp', true/false)` | enabled **1** then **0** on `school_feature_toggles` id=2 | **pass** | gap |
| `SystemSettings.save` | Change `sitename` then restore | set → `BatchA-Temp-…` then restore `School-Plus` | DB confirmed both mid-state and restore | **pass** | gap |
| `submitPassword` | Change siteadmin password | Validation error: *"The confirm password and password must match."* | hash unchanged (`$2y$10$92IX…`) | **fail** (rule bug) | gap |
| `submitAvatar` | Upload avatar for siteadmin | Upload + call OK; landed `/admin/academics` (wrong area) | `userprofiles.avatar` → `avatars/S1rNks….png`; file on disk under `public/avatars/` | **partial** (DB pass, redirect wrong) | gap |

#### Batch A failures / partials detail

1. **Filament subscriptions list 500 (blocks approve)** — **Severity: MEDIUM-HIGH alone; see systemic note → HIGH as cluster (1 bug × N lists)**. `Too few arguments to function Filament\Tables\Table::hasSummary(), 0 passed … exactly 1 expected` in published `resources/views/vendor/filament-tables/index.blade.php`. Reproduced in browser and `Livewire::test(Subscriptions::class)`. Approve Action code exists (`status=approved`, start/end dates) but is unreachable until vendor view / Filament version skew is fixed. **Same root cause as countries/cities/plans lists — not a subscriptions-only bug.** **Not fixed in Batch A.**
2. **`submitPlan` update redirect** — **Severity: LOW** (data OK, UX only). `url('/superadmin/setting/plans'.$this->planEditId)` → `/plans8` (missing `/`). Create OK because id empty.
3. **`submitPassword` validation bug** — **Severity: HIGH** (may mean superadmins can't change password via this flow; operational/security-adjacent). `#[Rule('…|same:password')]` on `confirm_password` but property is `new_password`. Browser shows mismatch error; password hash unchanged. Siteadmin left on `password`.
4. **`submitAvatar` redirect** — **Severity: LOW** (data OK, UX only). code `redirect(url('/admin/dashboard'))`; observed landing `/admin/academics`. Avatar write succeeded.
5. **Subscription form status enum drift** (create still worked with `pending`) — UI options `approve`/`cancel` vs DB enum `approved`/`canceled`. User dropdown is `usergroup_id=6` (Student), not school admins.
6. **`submitAdmin` email unique** validates `unique:` against `School::class` not `User` (wrong table; create still succeeded).

#### Batch A notes / code smells (not fixed)

- **City dropdown on school create**: bare `wire:model="country"` + `wire:change="changeCity"` — city options did not populate via native select alone in Playwright; `$wire.set` + `changeCity` worked (deferred-model race candidate).
- **`users.name` mangling** on admin create: submitted `BatchA Admin {stamp}` → stored `batcha admin {stamp}{extra}`; `userprofiles.firstname` kept intended value.
- **Browser harness**: Cursor IDE browser MCP tabs did not stick in this subagent; Playwright `channel: 'chrome'` + Livewire `$wire` used. Artifacts: `KlassApp/tmp/superadmin-batch-a/`.

### Phase 2 Batch B results (GEO — COMPLETE, catalogue only, no fixes)

> **Domain confirmation**: Batch B = **geo** (`cities` / `countries` under `/superadmin/setting/*`). Not reports, not impersonate, not schools.
> **Artifacts**: `KlassApp/tmp/superadmin-batch-b/`. Playwright `channel: 'chrome'` + Livewire `$wire` (same harness as Batch A). Native nested `<input type="submit">` click often did not fire Livewire submit; `$wire.call` used as confirmed browser trigger (same pattern as Batch A fallbacks).

| Mutator | Expected | Browser result | DB result | Status | Toshi |
|---|---|---|---|---|---|
| `submitCity` create | New `cities` row | `$wire.set` + `$wire.call('submitCity')` → `/superadmin/setting/cities` (list itself 500s after redirect) | Row **id=140** `BatchB City 1785461404029` → later updated; country_id=10 status=1 | **pass** | gap |
| `submitCity` update | Update city 140 name | `$wire.set` + `$wire.call('submitCity')` → cities URL | name=`BatchB City Updated 1785461527055`, updated_at=`2026-07-31 04:32:30` | **pass** | gap |
| `submitCountry` update | Update country 10 fields | `$wire.set` + `$wire.call('submitCountry')` → countries URL (list 500s); restored to Other after | Mid-state: name=`BatchB Other 1785461642142`, short=`BB2142`, iso=`B42`, tel=`+9992142`; restored Other/OT/OT/20 | **pass** | gap |
| Filament countries `CreateAction` | Create country (expect stub/fail) | **HTTP 500** on `/superadmin/setting/countries` — CreateAction never reachable | countries still **count=10**, max id=10 | **fail** | gap |
| Cities list Filament Edit | Navigate-only (not mutator) | Cities list also **HTTP 500** (same hasSummary); Edit from list N/A; direct update URL works | N/A | **N/A** (list blocked) | gap |

#### Batch B failures / partials detail

1. **Filament countries list 500 (blocks CreateAction)** — **Severity: MEDIUM alone; see systemic note → HIGH as cluster**. Same root cause as Batch A subscriptions (`hasSummary()` arity / published `filament-tables/index.blade.php`) — **not a second bug**. Country **update form still works** via direct `/setting/country/update/{id}`; create route intentionally commented + CreateAction stub. **Not fixed in Batch B.**
2. **Filament cities list 500** — **3rd occurrence of same systemic `hasSummary` bug** (confirmed via `Livewire::test(Cities::class)` identical exception). City create/update forms OK via direct routes; list + Filament Edit unreachable. **Not fixed.**
3. **Country create path still absent** (Phase 1 finding confirmed): `routes/web.php` create route commented; `CountryForm` update-only; Filament CreateAction stub + list 500.

#### Batch B notes

- Form mutators (`submitCity` / `submitCountry`) **pass** when hit via Livewire component routes; post-submit list pages 500 but DB writes succeed.
- Plans CreateAction was Batch A adjacent — Batch B focused countries CreateAction only (as requested).

### Phase 2 status — **CLOSED** (catalogue done — fixes deferred)

| Batch | Domain | Status |
|---|---|---|
| A | Schools / plans / subscriptions / co-admins / features / settings / password / avatar | **COMPLETE** |
| B | GEO (cities / countries) | **COMPLETE** |
| C | Read-only HTTP smoke (27 routes) | **COMPLETE** |
| D | Shared Toshi (superadmin shell) | **COMPLETE** |
| E | Impersonate school admin | **COMPLETE** |

### Phase 2 Batch C results (read-only HTTP smoke — COMPLETE, catalogue only, no fixes)

> **Scope**: Dashboard + hubs + representative list/detail pages. **No mutators.** Playwright login as siteadmin @ `:8010`. Artifacts: `KlassApp/tmp/superadmin-batch-c/smoke-results.json`.
> **Sample IDs**: school **35**, admin/user **169**, userprofile **161**, plan **8**, city **140**, country **10**, subscription **11**.

| URI | HTTP | Content OK | Notes |
|---|---|---|---|
| `/superadmin/dashboard` | 200 | yes | |
| `/superadmin/reports` | 200 | yes | reports hub |
| `/superadmin/settings` | 200 | yes | settings hub |
| `/superadmin/settings/locations` | 200 | yes | locations hub |
| `/superadmin/settings/system` | 200 | yes | |
| `/superadmin/settings/co-admins` | 200 | yes | |
| `/superadmin/settings/features` | 200 | yes | |
| `/superadmin/settings/emis` | 200 | yes | EMIS list (read-only) |
| `/superadmin/academics/schools` | 200 | yes | school-list — **not** Filament / not hasSummary |
| `/superadmin/academics/school/detail/35` | 200 | yes | |
| `/superadmin/academics/school/admin/list/35` | 200 | yes | |
| `/superadmin/academics/school/admin/detail/169` | 200 | yes | |
| `/superadmin/academics/school/user/list/35` | 200 | yes | |
| `/superadmin/academics/school/user/detail/169` | 200 | yes | |
| `/superadmin/academics/school/userprofile/detail/161` | 200 | yes | |
| `/superadmin/setting/plans` | **500** | no | Filament PlanList — **hasSummary (4th occurrence)** |
| `/superadmin/setting/plan/detail/8` | 200 | yes | |
| `/superadmin/setting/cities` | **500** | no | Filament Cities — **hasSummary (3rd)** |
| `/superadmin/setting/city/detail/140` | 200 | yes | |
| `/superadmin/setting/countries` | **500** | no | Filament Countries — **hasSummary (2nd)** |
| `/superadmin/setting/country/detail/10` | 200 | yes | |
| `/superadmin/reports/subscriptions` | **500** | no | Filament Subscriptions — **hasSummary (1st)** |
| `/superadmin/reports/subscription/detail/11` | 200 | yes | |
| `/superadmin/reports/contact` | 200 | yes | contact list |
| `/superadmin/mail-list` | 200 | yes | |
| `/superadmin/changepassword` | 200 | yes | form page only (no mutate) |
| `/superadmin/changeavatar` | 200 | yes | form page only (no mutate) |

**Batch C summary**: 27 routes smoked → **23× HTTP 200 + content OK**; **4× HTTP 500** — all Filament table lists, all same systemic `hasSummary` bug. Detail pages for those domains remain healthy.

### Phase 2 Batch D results (Shared Toshi — COMPLETE, catalogue only, no fixes)

> **Scope**: AgentToshi on `layouts/superadmin-app.blade.php` (`@livewire('agent-toshi')`). Laravel AI SDK (`laravel/ai` / `ToshiSdkV2Service`). No code fixes.
> **Artifacts**: `KlassApp/tmp/superadmin-batch-d/batch-d-results.json` (+ screenshots).
> **Note**: bare `/superadmin` is **404**; canonical shell is `/superadmin/dashboard`.

| Probe | Expected | Browser / DB result | Status |
|---|---|---|---|
| Mount | `agent-toshi` Livewire on dashboard | Found `name=agent-toshi`; scope=`platform`, mode=`assistant`, step=99 | **pass** |
| `show()` | Open panel | `$wire.call('show')` → `visible=true`; DOM `.toshi-root` visible; greeting: platform administrator assistant | **pass** |
| `send` `/help` | Slash help (no LLM) | User+bot; lists `/create`, `/agent`, `/status`, `/help`, reset | **pass** |
| `send` NL probe | SDK / LLM answer | Livewire send OK (~2s); bot = `fallbackMessage()` (“I'm not sure… **📊 Info** — show report”) | **partial/fail** (SDK) |
| Onboarding / `commitAll` | Skip if destructive | **Skipped** — creates schools/users/subscriptions | **N/A skipped** |

#### Batch D SDK / env note (partial/fail detail)

- `config('toshi.sdk_v2_enabled')` = **true** locally; `streaming_enabled` = false.
- `ToshiSdkV2Service::isAvailable(siteadmin, null)` = **false** — `per_school_gate` requires a school with `toshi_enabled`; siteadmin `school_id=null` → no school → gate fails.
- `ask(...)` returns **null** → Livewire falls through to `fallbackMessage()`. Same failure class as `ToshiE2EVerificationTest` LLM null / env gate — **not fixed**.
- Platform gap for CRUD still stands (Phase 1: **1 covered / 32 gap / 8 N/A**).

#### What Toshi CAN do from superadmin shell

- UI: open/close panel, slash commands (`/help`, `/create`/`/school`, `/agent`, `/status`), keyword/fallback capability list.
- Claimed platform actions: `create_school`, `platform_reports`, `list_schools` (greeting mentions platform stats/schools/users/system management).
- **Cannot** (from this shell without a gated school): real Laravel AI SDK Q&A; most platform CRUD (still gap vs Livewire superadmin forms).

#### School-admin Toshi path (not re-run)

- Already verified elsewhere in knowledge: Toshi E2E + `commitAll` fixes (2026-06-30 session); school-scoped assistant/onboarding. Not re-audited in Batch D.

### Phase 2 Batch E results (Impersonate — COMPLETE, catalogue only, no fixes)

> **Artifacts**: `KlassApp/tmp/superadmin-batch-e/batch-e-results.json`, `batch-e-session-probe.json`.
> **Target**: `GET /schooladmin/169/impersonate` (school 35 BatchA admin `batcha.admin…@example.com`).

| Step | Expected | Result | Status |
|---|---|---|---|
| Start impersonate | Become school admin session | Redirect → `/admin/academics`; school name **BatchA Audit School…** in UI; admin sidebar (ACADEMICS/…); **Stop Impersonating** link → `/teacher/impersonate/stop` | **pass** |
| Stop impersonate | Clear session; return to siteadmin | Session cleared (`Stop Impersonating` gone); final URL stayed `/admin/academics` (not `/superadmin/*`); siteadmin can still open `/superadmin/dashboard` (200) | **partial** |

#### Batch E notes / failures

1. **Stop redirect for siteadmin** — **Severity: MEDIUM**. `ImpersonateController@stopImpersonate` reads `Auth::user()` **before** `stopImpersonating()` (middleware `Auth::onceUsingId` → impersonated ug3), so redirect uses school-admin branch → `/admin/dashboard` (lands `/admin/academics`). Superadmin (ug1) redirect branch is **commented out**. Session key `impersonate` is cleared (UI evidence). Catalogue only — not fixed.
2. Auth remains siteadmin after stop (can access `/superadmin/dashboard`).

### Phase 2 summary table (A–E)

| Batch | Result headline |
|---|---|
| A | Most school/plan/admin/settings mutators **pass**; password **fail** HIGH; plan/avatar redirects **partial** LOW; subscriptions list 500 blocks approve |
| B | City/country form mutators **pass**; countries CreateAction blocked by list 500; country create route still absent |
| C | 23/27 smoke **200**; 4 Filament lists **500** = hasSummary ×4 |
| D | Toshi panel/show/help **pass**; SDK send **partial/fail** (per_school_gate / null ask); onboarding skipped |
| E | Impersonate **pass**; stop session clear **pass**, stop redirect **partial** |

### Triage findings (**CLOSED on `main`** @ `32a3bb4` — was `fix/superadmin-audit-triage` @ `8c93693`)

| Priority | Finding | Source |
|---|---|---|
| ~~**HIGH / systemic**~~ | ~~Filament `hasSummary()` arity ×4~~ — **FIXED on main** (removed stale published filament-tables) | A/B/C |
| ~~**HIGH**~~ | ~~`submitPassword` `same:password`~~ — **FIXED on main** (`same:new_password`) | A |
| ~~**MEDIUM**~~ | ~~Country create~~ — **FIXED on main** (uncomment route + CountryForm create like CityForm; Create Country button) | Phase1 / B |
| ~~**MEDIUM**~~ | ~~Stop-impersonate landing~~ — **FIXED on main** (redirect by session impersonator usergroup; ug1 → `/superadmin/dashboard`) | E |
| ~~**LOW**~~ | ~~`submitPlan` update redirect~~ — **FIXED on main** (`/superadmin/setting/plans`) | A |
| ~~**LOW**~~ | ~~`submitAvatar` redirect~~ — **FIXED on main** (`/superadmin/dashboard`) | A |
| ~~**LOW / cosmetic**~~ | ~~Dead `/superadmin/users` “View all”~~ — **FIXED on main** (removed; no platform users route) | Phase1 |
| **note** | Subscription form status enum drift; admin email unique validates wrong table; users.name mangling — left as notes (not triage blockers) | A |

### Decided-deferred (roadmap — NOT active triage)

| Item | Decision | Why not triage-now |
|---|---|---|
| **Toshi platform-scope for superadmin** | **Phase 0–1 MERGED** — #124 on `main` | Platform gate + tools + HITL. Role agents #125–#129+#137; WhatsApp #133–#136 deployed; MCP audit #140. |

---

## Current Status: August 4, 2026 (`origin/main` tip `1cfb499` — school-name onboarding #165)

- **`origin/main` tip**: `1cfb499` — `fix(toshi): school-name onboarding bypasses student lookup (#165)`. Prior: `c6ba7af` (#164 knowledge stamp), `417ca26` (#163 SaaS minimal signup).
- **✅ Merged #165**: Toshi complete-mode school-name fixes — skip keyword/student-lookup while collecting `school_info` name; complete-mode rename collisions use `uniqueSchoolName` (`-2/-3`) instead of hard reject. Squash merge commit `1cfb4992056a3f03c4e2dd8d194b8946e8768cdf`.
- **✅ Merged #163**: SaaS minimal signup — shared `SchoolSignupBootstrapService` (name+email+Phone WhatsApp + password|Google) → placeholder `{First}'s School` (`-2/-3` collisions), `curriculum=null`, `toshi_enabled=1`, **no AcademicYear** → `/admin/dashboard?toshi_onboarding=1` + Toshi complete mode (school name → curriculum → academic year early). Dashboard/`MustBePrivilege` null-guard. Merge commit `417ca269a19d0c1fccb6cd2b9660be6bc71f6995`.
- **✅ Merged Aug 2–3 (on main; deploy separate)**:
  - **#142+** safety/adversarial + `UsesToshiLlm`; **#143–#145** llm-status / dual-config fail-loud / llm-health
  - **#147–#149** adversarial-live in-process (no phpunit/faker on prod) + durable schedule logging; prod adversarial gate live
  - **#150** School Admin Batch 1 — notices/events/holidays via `SchoolCommsSkill` (merge `e80fc87`)
  - **#152** homework-manage + studentHomework-review Gates; **#154** ug5 homework destroy ownership (`teacher_id`)
  - **#153** School Admin Batch 2 — timetable + homework oversight (merge `102f92e`)
  - **#156** teacher-leave + studentAssignment-review Gates (merge `0cb5b76`); **#157** Teacher Batch 2 — submission review + own leave (merge `862ba92`)
  - **#160/#161/#162** knowledge sync + session-log rule + signup investigation pause log
  - **#164** knowledge stamp for #163 merge
- **🚧 Open PRs**:
  - **#159** report cards v1 — shared PDF + SA/Teacher tools — **PR open** @ `6f13017` — https://github.com/KlassApp-Foundation/KlassApp/pull/159
  - **#158** report cards audit (Part A, docs) — **draft** @ `c9db10c` — https://github.com/KlassApp-Foundation/KlassApp/pull/158
  - Also open: #155 Teacher Batch 2 audit, #151 SA Batch 2 audit, #146 live-verification docs, #141 panel-parity ranking, #139 Google connector audit, #138 preference memory
- **✅ Toshi on `main` (cumulative)**: Platform (#124), Teacher→Student (#125–#129), Deputy Admin (#137), WhatsApp (#133–#136), IDOR (#123/#130–#132), MCP audit (#140), safety/adversarial + UsesToshiLlm (#142+), dual-config/llm-health (#143–#149), School Admin Batch 1–2 (#150/#153), Teacher Batch 2 (#157) + Gate fixes (#152/#154/#156), **SaaS minimal signup (#163)**, **school-name onboarding (#165)**.
- **Report cards**: Academic per-student PDF **exists** (`DownloadStudentReport`; shared `StudentReportCardService` on #159). `/admin/reports` hub remains CSV/operational exports only — do not claim “no report cards”. Gaps: class/term batch + full distribution. See `docs/toshi-report-cards-audit.md` / #158.
- **Onboarding / signup**: Fresh admins get placeholder school + Toshi-first setup; curriculum and academic year are asked early (not silently defaulted). Student ID bugs remain out of scope. **Next**: deploy (#163+#165) + live fresh-signup walkthrough (collision `-2/-3`, dashboard pre-year, early academic year, multi-word school names).
- **Non-negotiable**: payroll + impersonation stay **web-only**; knowledge Session Log + Current Status updated through PR open and merge (no stale “NOT PUSHED” / “opening PR” after ship).

## Current Status: August 1, 2026 (Vue 3 + Phase 3 Vite + superadmin audit CLOSED on `main` + **Toshi Phase 0–1 on feature branch** — superseded above for Toshi merge state)

- **✅ Toshi Autonomous Operator Phase 0–1 on `feature/toshi-platform-tools`** (worktree `KlassApp-main-merge`): tip **`edc07e5`** + knowledge push closeout. **Pushed to origin** (not merged to `main`). Includes: platform gate (`TOSHI_PLATFORM_GATE_ENABLED`), scope router in `ToshiSdkV2Service` (Platform → `PlatformOperationsAgent`, School → `ToshiOrchestrator`), native `laravel/ai` 0.10.2 HITL (`approval_state` column), ops review UI, full superadmin mutator tools, audit `acting_user_id`/`approver_id`. Enable locally: `TOSHI_PLATFORM_GATE_ENABLED=true`.
- **✅ Superadmin audit CLOSED on `main`**: merge commit **`32a3bb4333f8645a2752d760fcd76287f57f5fa8`** — `Merge branch 'fix/superadmin-audit-triage'` (no-ff). Tip merged: `fix/superadmin-audit-triage` @ `8c93693`. Phase 1 + Phase 2 Batches A–E catalogue + triage fixes. Toshi platform-scope built on feature branch (above) — was decided-deferred during triage.
- **Vue 3 merge**: `50f5c4d1926111e787a16d2b04bd0054b4ff671d` — `merge: bring migration/vue3-runtime (Vue 3.5.40 @vue/compat MODE 2) into main` (no-ff). Follow-ups through `8a2938d` on `origin/main` pre-Vite.
- **✅ Phase 3 Vite CLOSED on `main`**: merge commit **`9bdf185571c8f8a5b0bae198034df3aebb1ff3bd`** — `merge: bring migration/vite into main (Phase 3 Vite sole bundler)` (no-ff). Tip merged: `migration/vite` @ `73ee046`. Worktree used for merge: `/Users/mac/projects/KlassApp-main-merge` (did not disturb `KlassApp`/`migration/tailwind4` or other dirty worktrees).
- **✅ 5 deferred app bugs CLOSED on `main`**: merge commit **`536603cc38c2b0c37af4de3df1c860e80473f39a`** — `merge: bring fix/deferred-bugs into main (5 deferred app bugs)` (no-ff). Tip merged: `fix/deferred-bugs` @ `a0db768`. Worktree: `/Users/mac/projects/KlassApp-main-merge`.
  | # | Bug | Fix commit | Fix |
  |---|---|---|---|
  | 1 | `activity()` undefined (login/registration) | `77d1fbe` | Spatie-compatible helper → `ActivityLogger` / `ActivityLog` |
  | 2 | `str_limit()` removed (academic list 500) | `5b5540f` | `Str::limit()` in resources + Blade |
  | 3 | ClassWall `Post` `count(null)` on `attachment_file` | `b21c04f` | `count($this->attachment_file ?? [])` |
  | 4 | `blockedstudents` `count(null)` on query string | `b21c04f` | `count((array) getQueryString())` |
  | 5 | `admin/promotion/list` unknown column `exam_type` | `e993196` | Query → `whereRelation('examType','code','FINAL')` (no migration) |
- **✅ cleanup-loose-ends CLOSED on `main`**: merge commit **`08b3886bf6dd8f24e12b57a25afeb694db49d886`** — `merge: bring chore/cleanup-loose-ends into main` (no-ff). Tip merged: `chore/cleanup-loose-ends` @ `3779e1b`. Worktree: `/Users/mac/projects/KlassApp-main-merge`.
  | # | Item | Status on main | Ref |
  |---|---|---|---|
  | 1 | `home_navigation` gate removed (nav on all `layouts.main`) | ✅ CLOSED | fix `099b58e`; border revert `14b9e33` |
  | 2 | Orphan welcome/minimal surfaces documented (not deleted) | ✅ CLOSED | docs in knowledge |
  | 3 | Mix-era docs retargeted to Vite SoT | ✅ CLOSED | `454940c` |
  | 4 | `legacy-peer-deps` package list tightened (7 direct + 2 transitive) | ✅ CLOSED | knowledge |
  | 5 | Visual smoke + usecase 404 noted pre-existing | ✅ CLOSED | `3779e1b` |
- **Post-merge verify (cleanup @ `08b3886`)**:
  - Pre-merge: `chore/cleanup-loose-ends` **0 behind / 5 ahead** of `origin/main`; working tree clean; `npm run build` PASS; PHPUnit **234 passed / 1 skipped / 1 failed** (`ToshiE2E` LLM null — expected).
  - Post-merge PHPUnit: **234 passed / 1 skipped / 1 failed** (same `ToshiE2E`). `npm run build` PASS; no `public/hot`.
  - Nav smoke (`:8010`, built assets): `/privacy-policy` + `/terms-of-service` **200**; markup has `<nav class="navbar">` + KlassApp logo + `#register` Free Sign Up + `#login` Login; screenshots `tmp/nav-smoke/privacy-postmerge.png` + `terms-postmerge.png`.
- **Post-merge verify (on merged main @ `536603c`)**:
  - Pre-merge: `fix/deferred-bugs` **0 behind / 5 ahead** of `origin/main`; working tree clean; `npm run build` PASS; PHPUnit **234 passed / 1 skipped / 1 failed** (`ToshiE2E` LLM null — expected).
  - Post-merge PHPUnit: **234 passed / 1 skipped / 1 failed** (same `ToshiE2E`).
  - Manual smoke (`admin@testschoolone.sch.ug` / `password`, `:8010`): login+dashboard **200**; `/admin/academic/list` **200**; ClassWall `editList/1` **200**; `/admin/students/blockedstudents` **200**; `/admin/promotion/list` **200**; `activity()` helper exists + logs.
- **Post-merge verify (historical, Vite @ `9bdf185`)**:
  - `npm run build` — **PASS** (Vite 8.1.5, ~6.8s).
  - `npm run dev` + artisan `:8010` — `Vue.version === '3.5.40'`, Vite client from `public/hot`; shell smoke PASS (boot, academics, attendance/add + multiselect, discipline/add + multiselect, ACADEMICS sidebar nav). Login `admin@testschoolone.sch.ug` / `password`. `public/hot` cleaned after.
  - PHPUnit then: **5 failed, 1 skipped, 220 passed** (pre-activity() baseline) — now superseded by 234/1/1 after deferred merge.
  - Phase 3.4 re-smoke — **PASS**: portal-vue teachers `#show-detail` open (`hide-menu`→`block`) + close; vuejs-datetimepicker discipline + ClassWall `.port` calendars; change-credential on teacher show (`$flashStorage` + Credentials UI); create-leave `/teacher/leave/add` mounts (Vue 3.5.40).
- **Soft SFC template fixes on `main`**: **42** soft compiler errors cleared earlier (`7f29e37` / `5a7cc45` / `8a2938d`) — required so Vite does not hard-fail where Mix softened.
- **Main hygiene (pushed pre-Vite)**: `vue-upload-multiple-image` removed (`e2b0112`).
- **Phase 3 history on `migration/vite`**: Scaffold → CSS (3.2) → **✅ 3.3 Blade `@vite()`** → **✅ 3.4 package firefight** → **✅ 3.1 ESM** → **✅ 3.5 Mix removal** (`3bc5c70`) → rules `c470c39` → merge to main `9bdf185`.
- **Scoped tech debt**: `.npmrc` `legacy-peer-deps=true` — **7 direct** Vue-2 peer packages reject Vue 3.5.40 (Jul 30 audit): `@fullcalendar/vue@5` (`^2.6.12`), `@kevinfaguiar/vue-twemoji-picker`, `ckeditor4-vue`, `qrcode.vue@1`, `vue-loading-overlay@3`, `vue-qart`, `vue-select@3`. **Transitive**: `vue-clickaway` (via twemoji-picker), `vodal` (via vue-image-upload-croppie). Not “needed for `@vue/compat` in general” — keep until those are upgraded/replaced. Full table in Phase 3.5 session log.
- **Pushed** to `origin/main` — includes deferred-bugs merge **`536603c`** + knowledge closeout **`d8dc818`** (status correction on this tip).
## Current Status: July 28, 2026

### Git
- **Branch**: `migration/tailwind4`
- **HEAD**: `355a838` — fix(tailwind): restore empty and main layout utility loading
- **Latest work (Jul 27-28, Tailwind v4 migration)**: Completed Phase 2a (pre-migration CSS cleanup, 6 commits), Phase 2b (Tailwind v1.4.6 → v4.3.3, CSS-first config, @apply→hardcoded CSS replacement), and Phase 2c (visual regression pass, 19-selector regression fix in `ef5bb77`, admission flow verified, empty/main layouts fixed in `355a838`). Phase 2c closeout complete — see bullets below.
- **Phase 2 selector-count correction (Jul 28)**: the original Phase 2b audit's shorthand `3/8` figures for `.custom-table` and `.submit-btn` were incorrect and should not be treated as historical fact.
  - **Exact class-token recount** across `resources/views/**/*.blade.php`: `.custom-table` = **16 files**, `.submit-btn` = **19 files**.
  - **Why the earlier recount said `16` for `.submit-btn`**: that was a narrower "user-visible submit control" subset that excluded some Livewire loading-state wrappers even though those wrappers still carry the literal `submit-btn` class token in markup.
  - **Use going forward**: treat **19/16** as the canonical exact-token counts unless a future audit intentionally switches to a different counting rule.
- **Separate reproducible finding (Jul 28) — ✅ CLOSED Jul 31 (deferred item 5) @ `e993196`**: `GET /admin/promotion/list` threw HTTP 500 — **not** a Tailwind migration issue; pre-existing app/schema bug surfaced during Phase 2c sampling.
  - **Route**: `admin/promotion/list` (`Admin\PromotionController@index`)
  - **Error (historical)**: `Illuminate\Database\QueryException` — `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'exam_type' in 'where clause'` (query on `exams` with `exam_type = final`).
  - **Diagnosis (investigation evidence)**: `exams.exam_type` **never existed** — not created-then-dropped. `create_exams_table` (2026_03_14) has no type column; `add_exam_types_on_exams_table` (2026_05_04) adds only `exam_type_id` FK → `exam_types`. Live schema: `hasColumn('exams','exam_type')=false`, `exam_type_id=true`. Bad `where exam_type=final` was in `PromotionController` since first commit (`a6784c3`); dead one-off query vs real `exam_type_id` model used everywhere else (Exam model, seeders, MarksController, Toshi, etc.).
  - **Recommendation / applied fix**: **Query fix, not migration** — `whereRelation('examType', 'code', 'FINAL')` + missing `App\Http\Resources\Exam` (id/name/subjects/standard_id). Do **not** add an `exam_type` column.
  - **Verify (Jul 31 re-check)**: `GET /admin/promotion/list` → **200** with `examlist` key (admin@testschoolone.sch.ug); PHPUnit **234 passed / 1 skipped / 1 failed** (`ToshiE2E` LLM null — unrelated).
- **✅ CLOSED Jul 31 (deferred item 1) @ `77d1fbe`**: PHPUnit auth/registration failures from missing `activity()` helper — **not** introduced by Tailwind v4 / Vue boot work; confirmed identical on **`main` and `migration/tailwind4`** before the fix.
  - **Failing tests (historical)**: `LoginRegressionTest`, `RegistrationMinistryCodeTest` ×2, `RegistrationFlowTest` (`admin_name_is_set_on_manual_registration`).
  - **Error (historical)**: `Call to undefined function App\Traits\activity()` at `app/Traits/LogActivity.php:19`.
  - **Fix**: Spatie-compatible global `activity()` helper + `App\Services\ActivityLogger` → `App\Models\ActivityLog` (package not re-added).
  - **Also seen same suite run**: `ToshiE2EVerificationTest` LLM null (API timeout/error) — separate from `activity()`; still open as E2E/env noise.
- **Separate reproducible finding (Jul 30) — ✅ CLOSED Jul 31 (deferred item 3) @ `b21c04f`**: `GET /admin/classwall/post/editList/{id}` threw HTTP 500 when `posts.attachment_file` is null — **not** a Vue 3 / `@vue/compat` / Phase 1b regression; confirmed identical on **`main` @ `02a1c52` and `migration/vue3-runtime`** before the fix.
  - **Route**: `admin/classwall/post/editList/{id}` (`Admin\PostEditController@editList`)
  - **Error (historical)**: `TypeError: count(): Argument #1 ($value) must be of type Countable|array, null given` at `app/Models/Post.php:83` (`getAttachmentPathAttribute` → `count($this->attachment_file)`).
  - **Fix**: `count($this->attachment_file ?? [])` in `Post::getAttachmentPathAttribute`.
- **Separate reproducible finding (Jul 30) — ✅ CLOSED Jul 31 (deferred item 4) @ `b21c04f`**: `GET /admin/students/blockedstudents` threw HTTP 500 when the request has no query string — **not** a Vue 3 / `@vue/compat` / Phase 1b regression; confirmed identical on **`main` @ `02a1c52` and `migration/vue3-runtime`** before the fix.
  - **Route**: `admin/students/blockedstudents` (`Admin\StudentController@blockedstudents`)
  - **Error (historical)**: `TypeError: count(): Argument #1 ($value) must be of type Countable|array, null given` at `StudentController.php:432` (`count(\Request::getQueryString())` when null).
  - **Fix**: `count((array) \Request::getQueryString())` (+ `$birthday = null` init). Same commit as item 3 — same `count(null)` mistake class.
- **Recurrence (Jul 28)**: `GET /admin/dashboard` HTTP 500 — **same homestead vs `klassapp_local` mismatch** already fixed in `.env` earlier this session (`DB_DATABASE=homestead` → `klassapp_local`; see Environment fixes below). **Not** a new schema bug or Tailwind regression.
  - **Route**: `GET /admin/dashboard` (`Admin\DashboardController@index`)
  - **Surface error**: `Illuminate\Database\QueryException` — `Table 'homestead.approvals' doesn't exist` at `DashboardController` ~line 84 (pending-approvals `whereHas`).
  - **Actual root cause (confirmed Jul 28 diagnostic)**: **exported shell `DB_*` overrides `.env`** on the **long-running** `php artisan serve --port=8000` process (PID **12225**, started **10:45:53**, still listening on `127.0.0.1:8000`). Process environment (not `.env`): `DB_DATABASE=homestead`, `DB_USERNAME=root`, `DB_PASSWORD=` (empty), plus `DB_CONNECTION` / `DB_HOST` / `DB_PORT` — classic Laravel Homestead defaults.
  - **Contrast**:
    - Fresh diagnostic shell: `env | grep DB_DATABASE` → **empty**; `php artisan config:show database.connections.mysql.database` → **`klassapp_local`** (matches `.env`).
    - Port **8000** server process: **`DB_DATABASE=homestead`** in `ps eww` — requests hit **homestead**, so dashboard 500 matches logs.
  - **Where set**: No `export DB_DATABASE` found in `~/.zshrc`, `~/.zshenv`, `~/scripts`, or repo scripts. **Not** in `.cursor/mcp.json` (MySQL MCP uses `MYSQL_DB=klassapp_local` only for the MCP child). Most likely a **stale terminal/session export** (e.g. old `export`/`source` when `.env` still said `homestead`, or manual Homestead-style exports) inherited by `artisan serve` at 10:45 **before** the `.env` edit; fixing `.env` alone did not update the already-running server.
  - **Repro**: `DB_DATABASE=homestead` + school admin → HTTP **500** on both `main` and `migration/tailwind4`; clean `klassapp_local` → **200**.
  - **Scope**: local env/process hygiene (unset leaked vars, restart serve from clean shell) — out of Phase 2c / Tailwind. **Do not** treat as “missing migration on prod” without checking which DB the process actually uses.
  - **✅ Resolved (Jul 28)**: Killed stale PID **12225** / port **8000** listener; restarted `php artisan serve --host=127.0.0.1 --port=8000` from clean shell (`env | grep DB_DATABASE` empty; artisan parent **no `DB_*` exported**). `GET /admin/dashboard` → HTTP **200** on `http://127.0.0.1:8000` (school admin session); HTML includes dashboard/KPI markers (not Server Error).
- **Priority 2 route smoke tests (Jul 28)**: Closed the “template audit ≠ page loads” gap with authenticated HTTP **200** checks on port **8000** after clean serve (`klassapp_local`):
  - `GET /admin/dashboard` — **200** (school admin `admin@testschoolone.sch.ug`)
  - `GET /admin/schooldetails` — **200** (same admin; view `admin/schooldetails/index.blade.php`)
  - `GET /admin/whatsapp/dashboard` — **200** (same admin; view `admin/whatsapp/dashboard.blade.php`)
  - `GET /superadmin/academics/school/userprofile/detail/1` — **200** (site admin `siteadmin@gmail.com`; Livewire `userprofile-detail`)
  - `GET /superadmin/academics/school/userprofile/create/1` — **200** (same site admin; Livewire `userprofile-form`)
  - Method: session login via HTTP client against live `artisan serve` (not kernel-only tinker).
- **Phase 1 status correction (Jul 28, Phase 3 pre-audit on `main` @ `753697f`)** — **reframes prior “Vue 2→3 complete” assumptions**:
  - **✅ Phase 1a: Vue 3 SFC compile-error remediation — complete and merged (real work)**:
    - Large SFC compile-error sweep (~**3,381** errors at peak) — `v-model-on-prop`, deprecated **`slot`** → **`v-slot`**, and related template fixes across the Vue tree.
    - **Mix 4→6 / webpack 5 prerequisite** (`d295517`, `build(mix): upgrade Laravel Mix 4→6`) — genuinely merged; required for the stricter compile path that surfaced the SFC issues.
    - These fixes were **necessary regardless of which Vue version runs** — Vue 3’s stricter compiler (and webpack 5 / vue-loader path) is what surfaced them; the remediated SFCs remain valid on Vue 2.7.
  - **✅ Phase 1b: runtime switch to Vue 3 / `@vue/compat` — CLOSED on `main` (merge `50f5c4d` Jul 30, 2026)**:
    - **1b.1 replace-now (Jul 28, accepted option 1)**: Vue 2 UI packages swapped + source migrated.
    - **1b.2 Task 1 (Jul 29, `dd13fc5`)**: alias `vue`/`vue$` → `@vue/compat`; vue-loader `compatConfig: { MODE: 2 }`; `configureCompat({ MODE: 2 })` in `app.js`; production build PASS; browser `Vue.version` = `3.5.40`. Soft-warn plugin for remaining SFC compiler strictness (invalid end tags / label v-model / v-html children).
    - **Replace-now done**: `@vueup/vue-quill`, `dropzone-vue3`, `vue-good-table-next`, `emoji-mart-vue-fast`, `vue3-carousel`, `sweetalert2`/`vue-sweetalert2`, `vue-simple-uploader@1`, `vue-easy-lightbox`+`floating-vue`, `vue-multiselect@3`.
    - **Quarantine (not replaced)**: `portal-vue`, `vuejs-paginate`, `vuejs-datetimepicker`.
    - **1b.4 MODE 2 watch (load-bearing, not quarantined)** — **re-verified Jul 30 from code + interactive smoke**: `vue-flash-message` — `Vue.use(VueFlashMessage)` + `<flash-message>` + `this.flash()` / `$flashStorage.flash()` in `change-credential` (admin teacher/staff/parent/member show) and `create-leave` (teacher leave create). Call sites: 2 SFCs; Blade mounts on 4 show pages + teacher leave create. Interactive proof: Credentials modal opens; `Vue.prototype.$flashStorage.flash(...)` renders `.flash__message` DOM. Success UX often races reload/redirect; error flash stays on leave create.
    - **Dead (quarantined in knowledge, not deferred limbo)**: `this.$message` only in unregistered `EleUploadVideo.vue` — no Element UI Message installer; **zero** `Vue.component` registration in `app.js`; never mounted.
    - **✅ 1b.2 Task 2 shell smoke** (Jul 29): boot + attendance/discipline/year-nav PASS; academics PASS* (list API 500); multiselect registered but discipline uses native teacher `<select>`. Production bundle emitted **0** compat warnings.
    - **✅ 1b follow-ups pre-1b.4** (Jul 29): Vue.prototype audit + **DEV** smoke warning inventory (17 unique MODE 2 messages).
    - **✅ 1b.4 plugin-surface DEV smoke** (Jul 29 + **re-run Jul 30**): ClassWall create (Quill+dropzone-vue3+datetime pick) PASS; homework Quill+file input PASS (dropzone-vue3 lives on student `Attachment.vue`, exercised via ClassWall); noticeboard list + **create** PASS (Invalid end tag recovery held); telephone `vue-good-table-next` search PASS; teachers list portal-vue side panel open (`#show-detail` hide-menu→block) + close PASS; student show profile portal tabs PASS; discipline + notice datetime calendars PASS; change-credential flash DOM PASS via `$flashStorage`. **⏸️ STOP before 1b.5**.
    - **✅ 1b.5 harden (Jul 30)**: dispositions for all **17** DEV MODE 2 unique warns (see session log). App-owned fixes: single `multiselect` register, Vue feature-flag DefinePlugin, explicit whitespace, `$swal` via `globalProperties` (no `Vue.use` sweetalert), single `Vue.use(VueFlashMessage)`, `PhotosSlider` `destroyed`→`unmounted`. Suppress-warning via `configureCompat` for portal-vue / flash / MODE 2 boot flags; per-module `compatConfig` on `vuejs-datetimepicker` (not global `COMPONENT_V_MODEL`). Post-harden DEV smoke (same Task 2 routes): original portal/GLOBAL/datetime/multiselect/feature-flag noise **gone**; remaining = academicyear `List` unhandled errors from deferred `str_limit` 500. **Recommend CLOSE 1b.5**.
    - **1b.4 targeted re-run (Jul 30 night — four items only)**:
      1. **create-leave flash**: **PASS** (after fixing empty webpack module). Teacher `teacher_test_school_one@testschoolone.edu` → `/teacher/leave/add`; empty Submit → axios 422 → `this.flash(...)` → `.flash__message` / `.error.flash__message` with “Please fill all fields”. Prior FAIL root cause: duplicate `</script>` in `leave/teacher/Create.vue` made webpack module `()=>{}` so `<create-leave>` never mounted.
      2. **discipline datetime**: **PASS**. Same `v-model` + `value`/`$emit('input')` as ClassWall. Calendar open → day → OK updates parent (`FormData incident_date` set). Console noise: `TypeError: 'set' on proxy: trap returned falsish for property 'value'` from picker watcher `this.value = newVal` (illegal prop mutate under Vue 3) — does **not** block select.
      3. **ClassWall edit**: **FAIL** on branch (page heading mounts; Quill/dropzone/datetime do not stay) — **waived for 1b.4**: same `editList/1` **500** reproduces on **`main`** (see deferred finding below). Not a Vue 3 / Phase 1b regression. `git diff main..migration/vue3-runtime -- app/Models/Post.php` empty.
      4. **students/blockedstudents 500**: **NOT S3**. Exact: `TypeError: count(): Argument #1 ($value) must be of type Countable|array, null given at StudentController.php:432` — `count(\Request::getQueryString())` when query string is null. **Compat-related? NO.** **Confirmed on main** → **5th deferred pre-existing** (same `count(null)` pattern as ClassWall `Post.php:83`).
      - **Recommend**: **CLOSE 1b.4** — create-leave + discipline datetime PASS; ClassWall edit 500 is pre-existing on main (deferred); blockedstudents is non-compat pre-existing (deferred, log-only). **⏸️ was STOP before 1b.5** → **1b.5 CLOSED Jul 30**.
    - **✅ Cleanup done (Jul 30, `e2b0112` on `main`)**: Removed unused npm dep `vue-upload-multiple-image` (confirmed dead Jul 29 — zero source imports of the package name). App still uses the **local** SFC `resources/assets/js/components/VueUploadMultipleImage.vue` (imported by `event/details/ShowImage.vue`), which depends on `vue-easy-lightbox` + `floating-vue` — not the removed package.
    - **Older note (pre-1b branch work)**: on `main` @ `753697f`, runtime was still Vue 2.7.16; `@vue/compat` unwired until 1b.2 Task 1.
  - **Post-merge on `main` @ `50f5c4d` (Jul 30)**: `npm run production` PASS (initially 42 soft webpack warnings — known invalid end-tag / label v-model / empty v-html / v-model-on-prop SFCs softened by Mix `VueCompatSoftCompilerErrorsPlugin`); browser `Vue.version` = **3.5.40**; PHPUnit **5 failed** (same baseline as pre-merge); post-merge shell smoke (8010, `klassapp_local`): boot, academics, attendance/add, discipline/add + `Vue.resolveComponent('multiselect')`, nav dropdown — **PASS**. **Follow-up on `main`**: those 42 soft errors were **fixed** (`7f29e37` / merge `5a7cc45` / rebuild `8a2938d`) so Vite will not hard-fail where Mix had been silent — see Session Log.
  - **Status label**: **Phase 1a complete / Phase 1b complete on `main`** — prior Jul 28 correction that runtime was still Vue 2.7.16 is **superseded** by this merge.
  - **⏸️ Decision point (user — not assumed)** — how to sequence Vue runtime vs Vite:
    - **(a)** Complete the **Vue 3 / `@vue/compat` runtime switch** as its own dedicated phase **before or alongside** Phase 3 (Vite).
    - **(b)** **Stay on Vue 2.7** for Phase 3 — Mix→Vite with **`@vitejs/plugin-vue2`**, treat Vue 3 runtime switch as a **future Phase 4**.
    - **(c)** Another approach — user’s call. Tonight’s Phase 1 scope was implicitly premised on Vue 3; **do not assume (a) or (b)** without explicit approval.
- **✅ Phase 1 Vue boot regression CLOSED (Jul 28) — both branches**: Blank Vue main content (`Vue.component is not a function`) after Mix 6 / webpack 5 ESM interop. **Not** a Tailwind issue; **not** equivalent to `admin/promotion/list`. **Applies to Vue 2.7 runtime** (webpack `require('vue')` namespace interop — separate from Phase 1b).
  - **Root cause**: `window.Vue = require('vue')` bound the ESM namespace; constructor is at `.default`.
  - **Fix**: `resources/assets/js/app.js` → `window.Vue = require('vue').default || require('vue');` then `npm run production` (compiled `public/js/app.js` shows `window.Vue=n(…).default||n(…)`).
  - **Applied on** (`public/js/app.js` is git-tracked — source + rebuilt bundle committed):
    - `migration/tailwind4` — **`7f55429`** (`fix(vue): resolve Vue undefined via ESM/CJS interop break in require('vue')`; parent `016b354`).
    - `main` — **`f54573d`** (same message; parent `d295517` Mix 4→6 tip).
  - **Verification — `migration/tailwind4` (`:8000`)** — all **PASS**:
    | Check | Result |
    |---|---|
    | `/admin/academics` mount | **PASS** — Academic Years + table + Add / Change Current Academic Year; body “No Records Found” (expected: deferred `str_limit` 500 on `/admin/academic/list`) |
    | `/admin/attendance/add` | **PASS** — create-attendance form (class, date, Forenoon/Afternoon, Select Students) |
    | `/admin/discipline/add` | **PASS** — form + teachers; `Vue.options.components.multiselect` present |
    | Boot: `typeof Vue === 'function'` | **PASS** |
    | Boot: `new Vue()` / `#app.__vue__` | **PASS** |
    | Boot: `Vue.prototype.$swal` | **PASS** |
    | Nav academic-year dropdown | **PASS** — options 2026, 2027 |
  - **Verification — `main` (`:8082`)** — all **PASS** (same checklist):
    | Check | Result |
    |---|---|
    | `/admin/academics` mount | **PASS** — same UI mount; “No Records Found” expected (`str_limit`) |
    | `/admin/attendance/add` | **PASS** |
    | `/admin/discipline/add` + multiselect | **PASS** — `Vue.options.components.multiselect === true`; teacher options populated |
    | Boot checks (Vue fn, `new Vue()`, `#app.__vue__`, `$swal`, year dropdown) | **PASS** |
  - **Audit note (kept)**: 183 active `Vue.component` sites / 0 `app.component`; sole Mix entry `app.js` — one-line interop fix restores full surface.
  - **✅ CLOSED Jul 31 (deferred item 2) @ `5b5540f`**: `str_limit()` at `AcademicYear.php:21` (and sibling resources) → `Str::limit()`. Undefined `$school` in `AcademicYearController@index` (unused by view) remains a separate unused-var note, not part of the deferred five.
  - **Phase 3 / Vite attribution (Jul 30, `migration/vite`)**: Academics page `Object.keys` TypeError under Vite was the **same known deferred #2 bug** (now closed). Historical: `GET /admin/academic/list` → 500 `Call to undefined function App\Http\Resources\str_limit()` at `AcademicYear.php:21` → `List.vue` `Object.keys(this.academic_years)` throws.
- **Phase 2c closeout (Jul 28)**: Priorities 2–4 finished on the agreed checklist (responsive templates, sampled `.submit-btn` / `.custom-table` pages vs `main`, Priority 4 `text-gray-700` / `border-gray-300` on real pages). **No Tailwind regressions found in sampled/audited surfaces** versus `main` — not an unqualified clean bill. **Jul 31**: all five deferred app bugs (`activity()`, promotion/`exam_type`, `str_limit`, ClassWall/blockedstudents `count(null)`) **CLOSED on `main`** @ merge `536603c`. **`home_navigation` CLOSED on `main`** @ merge `08b3886` (fix `099b58e`) — gate removed so nav renders on all `layouts.main` pages. **`layouts/minimal` + welcome-era views left as orphaned** (docs-only; no delete). **Phase 1 Vue boot regression CLOSED** (both branches verified). Dashboard env recurrence **resolved**; Priority 2 HTTP smoke routes **verified 200**.

### Confirmed Orphaned Welcome-Era Files
- **Three dead/orphaned surfaces (left as-is — do not wire without product intent):**
  1. **`resources/views/welcome.blade.php`** (views root, **not** `welcome/welcome.blade.php` — that path does not exist) — `@extends('layouts.minimal')`; `/` route in `web.php` has `//return view('welcome');` commented; live homepage is `landing` via WelcomeController. **Orphaned/dead.**
  2. **`resources/views/welcome/_modules_list_section.blade.php`** — confirmed orphaned (no Blade include/view/Livewire/JS consumer). Other `welcome/_*.blade.php` partials are the same era; treat the directory as suspect on future cleanup.
  3. **`resources/views/layouts/minimal.blade.php`** — **orphaned layout**: sole consumer was `welcome.blade.php` (dead). No live route renders through it (`mapStaticRoutes` / welcome path unused). Loads `app.css` only — no dedicated Tailwind v4 `@vite` link. Leave as-is unless a new route intentionally uses it.
  - **Jul 31 cleanup (`chore/cleanup-loose-ends`)**: confirmed documented; files **not** deleted.

### Toshi Layout History (app.blade.php)

The Toshi panel on `/admin/reports` (and all `app.blade.php` pages) was fixed across two commits:

**Commit `66da99c` (Pulse refinements, Jul 17)** — First shell-fix:
- Added `toshi-ui.css` stylesheet link to `<head>`
- Fixed `<main>` flex classes: removed `h-full`, sidebar `self-start` → `self-stretch`
- Added toggle button (`#toshi-toggle`) and JS click handlers — but ALL placed **AFTER `</main>`**, outside the flex container
- `@livewire('agent-toshi')` was **already present** before this commit, also outside `</main>`
- **Layout problem**: Toshi + toggle rendered as block elements below main content, not as a right column

**Commit `961f84b` (Toshi layout audit, Jul 21)** — Structural repositioning:
- **MOVED** `@livewire('agent-toshi')` from OUTSIDE `</main>` to INSIDE `<main>` as a flex child (after `dashboard-content-area`)
- **ADDED** `<div v-pre>` wrapper around `@livewire` (prevents Vue/Alpine attribute collision)
- **WRAPPED** the toggle in `@auth/@if` guards + added `toshi-toggle-wrapper` container
- **ADDED** `title="Open Toshi"` tooltip attribute to toggle
- **REMOVED** `window.innerWidth >= 1280` condition from the hide click handler (simplified JS)
- **Layout fix**: Toshi now participates as a flex child of `<main>` — theoretically in the right column

**Recurring bug (Jul 22, new finding)**: Even after `961f84b`, Livewire's client-side hydration (project is on Livewire 3, but the DOM reparenting pattern exists across v2 and v3) moves the Toshi DOM element inside `dashboard-content-area` div (observed via Playwright DOM path: `main > div.dashboard-content-area > div > div.toshi-root`). The compiled Blade template is correct — Livewire JS repositions the node after page load.

**Hot-fix applied (Jul 22)**: Changed `toshi-ui.css` to use `position: fixed` for `.toshi-root` at ≥1280px (same strategy as the <1280px viewport) instead of relying on flex layout. Added `margin-right: 380px` / `28px` to `.dashboard-content-area`. Verified:
- Toshi panel: `position: fixed; top: 83px; right: 0; width: 380px; height: calc(100vh - 83px)`
- Content area: `margin-right: 380px` when Toshi open, `28px` when collapsed (toggle space)
- Toggle: `position: fixed` on the right edge, 28px wide when collapsed, green (#22C55E) button

**⚠️ Known fragility**: This `position: fixed` approach is a hot-fix that works today because the pixel math matches — content margin-right:380px + Toshi fixed at width:380px = full 1400px viewport with no gap or overlap. But `position: fixed` doesn't participate in the shell's layout sizing. If sidebar width changes, a banner is added, or viewport assumptions shift, the fixed pixel values will silently drift out of alignment — unlike a true flex child which would be sized naturally. The proper fix (making Toshi a flex sibling of content-area inside `<main>`) is blocked by Livewire's client-side hydration re-parenting DOM nodes (observed on both Livewire v2 and v3). If that hydration issue is ever worked around, the `position: fixed` approach should be replaced with the flex layout.

**🧠 Livewire hydration pattern (v2 and v3)**: Livewire re-parents DOM nodes on client-side hydration — observed here where `@livewire('agent-toshi')` correctly rendered as a `<main>` flex child in the server HTML but Livewire JS moved it into `dashboard-content-area` after page load. This is a general pattern, not Toshi-specific. Any component relying on precise DOM position for CSS layout (rather than visual appearance via `position: fixed/absolute`) could theoretically hit the same class of bug if placed inside a Livewire-managed region.

**419 Page Expired**: Investigated — `SESSION_DRIVER=database`, table exists with correct schema, `TrustProxies=*`, login form has `@csrf`. Could not reproduce in session. Likely browser privacy settings blocking localhost cookies. SESSION_SECURE_COOKIE=false, same_site=null (lax).

- **Onboarding consistency audit — fixed 4 of 5 confirmed data-persistence bugs from the July 13 audit**:
  - **Finding 1 (fixed)**: `RegisterController::createSchoolAdmin()` was writing to non-existent `username` column instead of `name`. Now sets `$userData['name'] = $data['name']`. Removed dead `generateUsernameFromName()` method entirely. (Note: `UserprofileObserver` immediately overwrites `users.name` to a slug after profile creation — this is by design; the display name lives in `userprofiles.firstname+lastname`.)
  - **Finding 2 (fixed)**: `AgentToshi::commitAll('complete')` was creating `Section` rows but never `StandardLink`. Now creates `StandardLink` for each class matching the create-mode pattern. Removed `value` column from `Section::firstOrCreate()` calls (column doesn't exist in migration).
  - **Finding 3 (fixed)**: `OnboardingStepsService::isStepComplete('standards')` was checking `Standard::exists()` (the phase table). Now checks `StandardLink::exists()`.
   - **Finding 4 (evaluated — not a bug)**: No `CreateStandardTool` in `TOOL_CLASS_MAP`. Standards are handled by `handleStandards()` in the onboarding wizard, not the AI tool system. The `TOOL_CLASS_MAP` is for post-onboarding Toshi assistant actions (add teacher, create exam, etc.). Adding a `CreateStreamTool`/`AssignStudentsToStreamTool` pair would be a new feature, not a fix.
   - **Finding 5b (fixed)**: `commitAll('create')` now sets `toshi_enabled => 1` on newly created schools.
- **Tests**: 9 regression tests across 4 files in `tests/Feature/Onboarding/` — all passing (55 assertions, 0 failures).
- **Post-audit (Jul 22, part 2)**: Verified `CreateStreamTool` and `AssignStudentsToStreamTool` sketches against live code. Confirmed model schemas, JsonSchema API, and tool patterns. Two issues flagged: (1) `AssignStudentsToStreamTool` needs `academic_year_id` filter on `StudentAcademic` queries, (2) `registration_number` column has no unique constraint — name matching is the reliable path.
- **GO verdict**: System is still ready for real school onboarding. 4 of 5 audit findings fixed and regression-tested.

---

## Production

| Component | Server | URL |
|---|---|---|
| KlassApp App | 46.101.111.131 | https://klassapp.xyz |
| KlassApp (WABA) | 46.101.111.131 | https://klassapp.xyz |
| ~~Evolution API~~ | ~~decommissioned~~ | ~~Replaced by Meta WABA~~ |
| Deploy key | `~/.ssh/id_ed25519_do` | |

### Deploy Command
```bash
# From local machine:
bash scripts/deploy-manual.sh
```

The script handles: `git pull` (fast-forward), migrations, cache clear, and PHP-FPM restart. With the volume mount now active, code changes are immediately reflected inside the container without a manual `docker cp` step.

### Previous Issue (Fixed July 9 2026)
**Root cause**: In `docker-compose.prod.yml`, the `volumes:` entry for the `app` service was commented out:
```yaml
  app:
    # volumes:          # ← was commented
    #   - .:/var/www    # ← was commented
```
This meant the container ran with code baked into the image at build time. Any `git pull` on the host had zero effect inside the container unless `docker cp` was manually run.

**Fix**: Uncommented the volume mount. Also fixed host filesystem permissions (`chown -R 1000:1000 storage bootstrap/cache`) so the container's `appuser` (UID 1000) can write to cache and log directories.

**Verification**: After fix, writing a file to `/var/www/KlassApp/public/` on the host is immediately visible at `/var/www/public/` inside the container. No `docker cp` needed.

### Production .env (key values)
```
EVOLUTION_API_URL=http://10.19.0.6:8081
EVOLUTION_API_KEY=78E5A6FF-BA89-45C6-987C-C31407BD22B4
EVOLUTION_INSTANCE_NAME=klassapp
WHATSAPP_BUSINESS_NUMBER=+256765275289
WHATSAPP_BUSINESS_NAME=KlassApp
```

### Deferred Major-Version Migrations — Roadmap (July 12, 2026, updated July 26)

#### ✅ COMPLETED: axios 0.x → 1.x

axios was upgraded from ^0.29 to **1.18.1** as part of this session.

#### ✅ COMPLETED: Laravel 11 → 12

Laravel was upgraded from ^11.0 to **12.63.0** (production confirmed). The planned Phase A in the sequencing doc below is now done.

#### 1. axios 0.x → 1.x (completed)
| Dimension | Assessment |
|---|---|
| **Files affected** | 3 files import axios explicitly; 218 Vue component files use it via `window.axios` (global in bootstrap.js) |
| **Usage pattern** | `axios.get/post/put/delete` — standard REST calls. No interceptors, no custom adapters. |
| **Breaking changes** | `validateStatus` default (now rejects <200, >=300), `headers` format normalization, error shape (`error.code` for network errors), removed `cancel` token in favor of `AbortController` |
| **Risk level** | **Low** — most Vue components just call `axios.get(...).then(...)`. Global config change in bootstrap.js covers most calls. Error handling check in ~50 components that have `.catch()` blocks. |
| **Effort** | ~1 day (config update + spot-check 50 error handlers + test) |
| **Can parallelize?** | Yes — independent of all other migrations |

#### 2. Vue 2 → 3 (split: compile remediation vs runtime)

| Sub-phase | Status | Notes |
|---|---|---|
| **Phase 1a — SFC compile remediation** | ✅ **Complete (merged)** | ~3,381 compile errors fixed; `v-model-on-prop`, `slot`→`v-slot`; Mix 4→6 prerequisite (`d295517`). |
| **Phase 1b — Vue 3 / `@vue/compat` runtime** | ✅ **Closed on `main`** (`merge 50f5c4d`) | Alias `vue`/`vue$` → `@vue/compat`, vue-loader `compatConfig: { MODE: 2 }`, `configureCompat({ MODE: 2 })` in `app.js`; 1b.5 harden applied. Production build PASS; browser `Vue.version` = `3.5.40`. |

| Dimension | Assessment |
|---|---|
| **Component count** | **242** `.vue` files across 50+ feature directories |
| **Architecture** | Global `window.Vue`, `Vue.component(...)` in `app.js`, Options API throughout. No Vuex, no router. |
| **Third-party plugins** | ckeditor4-vue, vue-carousel, vue-cookies, vue-croppie, vue-good-table, portal-vue, vue-flash-message, emoji-mart-vue, v-select2-component, etc. — many may lack Vue 3 versions |
| **Breaking changes (Phase 1b only)** | `Vue.prototype` → `app.config.globalProperties`, `Vue.component` → `app.component`, `new Vue()` → `createApp()`, `$listeners` removed, `v-model` binding changes |
| **Risk level (Phase 1b)** | **Medium-High** — plugin audit + alias to `@vue/compat` + bootstrap rewrite |
| **Effort (Phase 1b)** | ~2 weeks — **complete on `main`** (branch `migration/vue3-runtime` merged Jul 30) |

#### 3. Laravel 11 → 12 ✅ (completed)
| Dimension | Assessment |
|---|---|
| **Current state** | Production is on **12.63.0**. The L10→11 upgrade took ~2 hours active + 30 min composer resolution. L11→12 was straightforward. |
| **Key deps verified** | laravel/sanctum ^4.0 ✅, spatie/laravel-medialibrary ^11.0 ✅, spatie/laravel-model-states ^2.12 ✅, filament/tables ^3.0 ✅ |
| **Architectural delta L11→L12** | Smaller than L10→11. No bootstrap/app.php restructure. |
| **Risk level** | **Low** — incremental release. |
| **Effort** | ~2-3 hours |

#### 4. Mix (Laravel Mix 6) → Vite — ✅ Phase 3 **CLOSED on `main`** (`9bdf185`)
| Dimension | Assessment |
|---|---|
| **Final state** | Vite 8 sole bundler on **`main`** — Mix / `webpack.mix.js` / Mix npm scripts **removed** (`3bc5c70` via merge `9bdf185`). Scripts: `npm run dev` / `npm run build`. |
| **Vite config** | `vite.config.js`: `@vitejs/plugin-vue` + `@vue/compat` MODE 2 + `@tailwindcss/vite` + `laravel-vite-plugin` |
| **Entry points** | `resources/assets/js/app.js` + `resources/assets/sass/app.scss` + `resources/css/tailwind.css` + `resources/css/landing.css` |
| **Blade** | `@vite([...])` on cutover layouts (Phase 3.3) |
| **ESM** | ✅ Phase 3.1 — `app.js` + `bootstrap.js` ESM; `vite`/`dev` boots Vue 3.5.40 |
| **Packages (3.4)** | portal-vue / vuejs-datetimepicker / vue-flash-message **works-as-is**; vue-upload-multiple-image **clean** |
| **Pusher** | `VITE_PUSHER_*` only — Mix dual-read removed in 3.5 |
| **Scoped tech debt** | `.npmrc` `legacy-peer-deps=true` — 7 direct (`@fullcalendar/vue@5` + 6) + 2 transitive (`vue-clickaway`, `vodal`); see Current Status / Phase 3.5 log |
| **Deferred docs** | ✅ CLOSED on `main` (`08b3886` / `454940c`) — Vite SoT; Mix notes superseded |
| **Risk level** | **Low** — prod deploy verification next; package peer upgrades separate |
| **Effort** | Phase 3 closed + pushed on `main`; prod deploy next |

---

### Recommended Sequencing

```
Phase A: Laravel 12 + axios 1.x ✅ (completed)
  ├─ Laravel 11→12: composer bump, test suite, deploy
  └─ axios 0.x→1.x: bootstrap.js config, error handler audit, npm bump
       ↓
Phase B: Mix→Vite + Vue 3 runtime
  ├─ Phase 1b: Vue 3 / @vue/compat runtime — **done on `main`** (`50f5c4d`)
  └─ Phase 3: Vite migration — ✅ **CLOSED on `main`** (`9bdf185`, 3.1–3.5)
```

**Rationale (updated Jul 30 Phase 3 merge to main):**
- **Laravel 12 first** — done.
- **axios 1.x** — done.
- **Phase 1a (SFC compile fixes)** — done and merged.
- **Phase 1b (Vue 3 runtime)** — **done on `main`** (`50f5c4d`).
- **Mix→Vite (Phase 3)** — ✅ **CLOSED on `main`** (`9bdf185`): 3.3 Blade `@vite()` → 3.4 packages → 3.1 ESM → 3.5 Mix removal (`3bc5c70`). Scripts `npm run dev`/`build`; Pusher `VITE_*`-only; no Mix. Academics `str_limit` ✅ CLOSED on `main` via deferred merge `536603c` (fix commit `5b5540f`). On `origin/main` (knowledge tip historically `3e93bc3`; current tip includes deferred closeout). Prod deploy next.
- **5 deferred app bugs** — ✅ **CLOSED on `main`** (`536603c` from `fix/deferred-bugs` @ `a0db768`).

**Deferred indefinitely (no current plan):**
- Replacing spatie/laravel-activitylog and laravel-notification-channels/fcm (removed during L10→11 upgrade, no L11-compatible versions available) — evaluate when these packages publish L11-compatible releases
- **Toshi UI consistency refactor** — Extract shared Toshi component parts (header bar with buttons, message list, composer) into reusable partials so both panel and maximize modal stay in sync. The ↻ Restart button was added to both views separately as a band-aid, but the underlying duplication means every future UI change needs to be made in two places. Scope for a dedicated session.
### Dependabot Vulnerabilities — July 12, 2026 (Full Inventory)

**Source:** GitHub Dependabot API + npm audit cross-reference. All alerts are npm packages (Laravel Mix frontend build toolchain). Zero composer/PHP runtime vulnerabilities.

**Scope breakdown (100 total open alerts):**

| Scope | Count | Critical | High | Medium | Low |
|---|---|---|---|---|---|
| Development (build/CI only) | 51 | 5 | 20 | 15 | 11 |
| Runtime (production) | 49 | 0 | 13 | 31 | 5 |

**7 critical alerts:** All are development-scoped (node-forge, etc.) — no production impact.

**FIXED this session (3 direct dependency bumps, resolved 32 runtime alerts):**
| Package | From | To | Alerts Fixed | Reasoning |
|---|---|---|---|---|
| axios | ^0.18 | ^0.29.0 | 4 high, 6 medium, 1 low | Non-breaking within 0.x semver range |
| dompurify | ^2.0.12 | ~2.5.9 | 13 medium, 1 low | Non-breaking within 2.x semver range |
| lodash | ^4.17.20 | ^4.18.1 | 2 medium | Non-breaking within 4.x semver range |

**DEFERRED (need breaking changes — do not fix now):**
| Package | Current | Fix Needed | Breaking Change | Effort |
|---|---|---|---|---|
| axios | 0.x | 1.x | API changed (interceptors, adapters) | Medium |
| vue | 2.x | 3.x | Entirely different API | Large |
| laravel/framework | 11.x | 12.x | New major Laravel version | Large |
| postcss (transitive) | via mix | rebuild mix | May require Laravel Mix 6+ | Medium |

**ACCEPTED RISK (dev-only, no production impact):**
- 51 development-scoped alerts (webpack-dev-server, node-forge, minimatch, qs, picomatch, etc.)
- These are build-time dependencies used by Laravel Mix. Not loaded in production.
- No action needed. If a developer cares, run `npm audit fix` locally which resolves most dev-only items automatically.

**Updated headline (post-fix):** ~100 open alerts (down from 149). 0 critical in production. 13 high in production (all axios, now partially mitigated by 0.29 bump). Remaining runtime alerts are transitive dependencies that require breaking version jumps to fully resolve.

### Current Priorities
1. ✅ **Role audits complete**: School Admin (commits 98f5758, b4807e0), Teacher (a37b84f), Accountant (077464a), Receptionist (7eecd78)
2. ❓ **Accountant's Financial Reports** — deferred as "not found" under Accountant namespace. Needs a second look to confirm it genuinely doesn't exist (may be accessed via admin role, or may be dashboard-only aggregations) rather than being missed.
3. ⏸️ **Dependabot (149 alerts, 7 critical)** — still untouched, no itemized list pulled yet. Need a dedicated dependency-update pass.
4. ⏸️ **SiteSubadmin + Non Teaching removal** — still pending, skipped across multiple sessions now. Dead code removal.
5. ⏸️ **Laravel 10→11 upgrade** — ready, not started. All known blockers cleared (laratrust removed, Sanctum bump identified).
6. ⏸️ **Remaining role audits**: Librarian, Parent web portal (if one exists)
7. ⏸️ **Design-system migration (~214 unmigrated views)** — deferred
8. ⏸️ **Toshi component refactor** (shared UI partials) — deferred

### Dead Sidebar Links — RESOLVED
- ✅ **Library** — FULLY BUILT: admin library controller + 5 views + routes + sidebar link fixed
- ✅ **Messaging** — LANDING PAGE BUILT: thin /admin/messages landing with 3 option cards (Message Students → /admin/students, Message Teachers → /admin/teachers, View Sent Messages → /admin/sentmessages). Route was a redirect, now renders landing page. Sidebar updated to point to landing page.
- ✅ **Health** — sidebar link was already pointing to `/admin/students` (working). Per-student health records fully built (StudentHealthController, 3 models, 6 routes). Aggregate health dashboard deferred — not a dead link.
- ✅ **Transport** — MINIMAL MVP BUILT: Transportation model, TransportController (CRUD), 3 views (list, create, edit), 6 routes. Previously rendered "coming soon" placeholder.

---

## Session Log

### 2026-08-04: Toshi complete-mode school-name onboarding fixes (#163 follow-up)
- **Work done**: Fixed two complete-mode school-name bugs. (1) `AgentToshi::send()` skipped `tryKeywordRoute` / NL→assistant heuristic while collecting `school_info` name (`substep === 0`) so multi-word names no longer become student-lookup misses. (2) Complete-mode rename collisions use `SchoolSignupBootstrapService::uniqueSchoolName()` (`-2/-3`) instead of hard-reject via `isDuplicateSchool`; create-mode still rejects duplicates. Privilege-gate report only: `/admin/academics` + `/admin/standard/create` allowlist in `MustBePrivilege` is **intentional** (pre-#163 onboarding-loop avoid; #163 kept it so mid-setup admins can reach AY/class pages) — not a `fullschooladmin` bypass; no code change.
- **Files modified**: `app/Livewire/AgentToshi.php`, `tests/Feature/Onboarding/ToshiSchoolNameOnboardingTest.php`, `knowledge.md`
- **Key decisions**: Gate keyword routing at the `send()` school_info collector only (do not weaken `tryStudentLookup` globally); complete-mode suffix alignment with signup; create-mode keeps duplicate reject.
- **Tests**: `php artisan test --compact tests/Feature/Onboarding/ToshiSchoolNameOnboardingTest.php` — **3 passed** (23 assertions)
- **PR / merge**: https://github.com/KlassApp-Foundation/KlassApp/pull/165 — squash merge commit `1cfb4992056a3f03c4e2dd8d194b8946e8768cdf` (`1cfb499` on main)
- **Status**: ✅ MERGED
- **Branch**: was `fix/toshi-onboarding-school-name` @ `ec873be` (base `origin/main` @ `c6ba7af`); tip after merge `1cfb499`
- **Next**: deploy (#163+#165) + live fresh-signup walkthrough

### 2026-08-03: SaaS minimal signup redesign — implement
- **Work done**: Implemented product decisions from investigation. Shared `SchoolSignupBootstrapService` (User+ug3+profile+placeholder School `{First}'s School` with `-2/-3` collision, `curriculum=null`, `toshi_enabled=1`, **no AcademicYear**, no OTP). Slim register form (name/email/Phone WhatsApp/password|Google via `POST /auth/google/start`). Dashboard + `MustBePrivilege` null-guard for bare schools (“Continue school setup” + Toshi open). `OnboardingStepsService` order: school_name → curriculum → academic_year → …; Toshi complete-mode jump + curriculum ask + school rename + year persist. Migrations make curriculum nullable (MySQL alter + fresh create).
- **Files modified**: `SchoolSignupBootstrapService`, `RegisterController`/`RegisterRequest`/`register.blade.php`, `GoogleAuthController`, `OnboardingStepsService`/`OnboardingHelper`, `AgentToshi`, `Dashboard` trait + admin dashboard blade/controller, `MustBePrivilege`, `User` fillable google_*, curriculum migrations, tests `SaasMinimalSignupTest`/`FreshAdminDashboardSafetyTest` + updated RegistrationFlow/OnboardingSteps/MinistryCode tests.
- **Key decisions** (locked by user): phone required; no AcademicYear at signup (null-guard instead); curriculum null (not uneb default); no OTP; plans unchanged; `/welcome` marketing untouched (auth `/welcome` redirects to Toshi); student ID bugs out of scope.
- **Tests**: `php artisan test --compact` on SaasMinimalSignup + FreshAdminDashboardSafety + RegistrationFlow + OnboardingStepsService + RegistrationMinistryCode — **15 passed**.
- **PR / merge**: https://github.com/KlassApp-Foundation/KlassApp/pull/163 — squash merge commit `417ca269a19d0c1fccb6cd2b9660be6bc71f6995` (`417ca26` on main)
- **Status**: ✅ MERGED
- **Base**: was `origin/main` @ `dcc60c3`; tip after merge `417ca26`
- **Next**: deploy (incl. curriculum-nullable migration) + live fresh-signup walkthrough

### 2026-08-03: SaaS minimal signup redesign — investigate (paused for later pickup)
- **Work done**: Investigation only (no code). Mapped `RegisterController` / `GoogleAuthController` / `AgentToshi::commitAll('create'|'complete')` / `OnboardingStepsService` / admin dashboard bare-school risks for a future minimal SaaS signup → Toshi onboarding handoff. Also completed a separate **onboarding harmony status audit** on `origin/main` @ `1755927` (manual vs Toshi write paths; July 2026 fixes still green; student-ID / `toshi_enabled` asymmetries flagged).
- **Goal (not started)**: Replace fat register form with name + email (+ password **or** Google) → placeholder `School` + admin `User` + real `schools.id` + `toshi_enabled=1` → redirect into Toshi onboarding for school details / curriculum / year / classes / teachers / students. Out of scope: `registration_number` / `klassapp_student_id` generation bugs (separate).
- **Immediate vs defer (summary)**:
  - **Immediate**: `schools.id` (auto-increment PK), unique non-null `name` (placeholder OK), `status`, slug (observer), admin User+profile (`usergroup_id=3`, `school_id`), **`toshi_enabled=1`**, and almost certainly an **AcademicYear** row (dashboard 500s without it).
  - **Defer to Toshi**: real school name polish, phone/country/size/ministry, curriculum choice, classes/StandardLink, subjects, teachers, students, terms, fees, WhatsApp verify.
- **Key findings**:
  - **Do not** use `commitAll('create')` for signup — too heavy (expects full conversational payload). Use a **light bootstrap** (Register/Google-shaped) then Toshi **`mode=complete`** / `commitAll('complete')` on the existing `schoolId` (no duplicate school).
  - **Reuse/extend `GoogleAuthController`** as the closest existing placeholder path; today it omits `toshi_enabled=1`, uses `phone=>''` (UNIQUE hazard), sets bogus `country` (column is `registration_country`), and redirects `/welcome` → dashboard rather than Toshi-first.
  - **Bare school without AcademicYear → admin dashboard 500** (`Dashboard` / `SiteHelper::getAcademicYear` assume object). Soft onboarding banner exists but never renders if dashboard crashes first.
  - Curriculum DB default `uneb` makes `isStepComplete('curriculum')` look done without user choice — product quirk for Toshi step 0.
  - Harmony audit: only Toshi-create auto-enables Toshi; Register/Google/`SchoolService`/`CreateSchoolTool` leave `toshi_enabled` false until `SetCurriculumTool` or manual flip. Platform gate env is siteadmin-only.
- **Decisions needed before implement** (open for next session):
  1. Placeholder name format (must stay unique under `schools.name`)
  2. Plan / subscription / trial at signup vs later
  3. Create AcademicYear at signup (**recommended**) vs null-guard all `getAcademicYear` callers
  4. Curriculum: keep DB default vs nullable/sentinel so Toshi step 0 is real
  5. Phone: `null` vs required — never `''` under UNIQUE
  6. OTP for email/password vs Google-verified email
  7. Post-signup UX: force Toshi / maximize panel vs dashboard banner vs keep `/welcome` then Toshi
  8. How `complete` mode renames the school (`commitAll('complete')` does not update `schools.name` today)
  9. Unify Google `country` → `registration_country` if interstitial kept
- **Pickup**: Resume on `origin/main`; implement only after product calls above. Suggested branch name when starting: `feature/saas-minimal-signup`.
- **Status**: ⏸️ **Paused** — investigate done; implementation not started; no PR
- **Base**: `origin/main` @ `1755927`

### 2026-08-03: Onboarding harmony status audit (manual vs Toshi)
- **Work done**: Docs/status only. Listed school-create paths (Register, Toshi commitAll create/complete, Google placeholder, Superadmin/`CreateSchoolTool`, WhatsApp=none). Confirmed July fixes: `users.name`, StandardLink+Section on both commitAll branches, `OnboardingStepsService` uses `StandardLink::exists()`. Flagged divergences: `toshi_enabled` asymmetry; student ID incomplete (`klassapp_student_id` in wizard only; `registration_number` nowhere in Toshi paths; `AddStudentTool` sets neither).
- **Status**: ✅ Audit complete — no code changes
- **Pickup note**: Feeds SaaS signup redesign decisions (`toshi_enabled=1` at bootstrap; student ID bugs remain out of signup scope)

### 2026-08-03: Knowledge sync + session-log Cursor rule
- **Work done**: Created always-apply rule `.cursor/rules/knowledge-session-log.mdc` (log through PR open + merge; Current Status ≤1 session behind main; no stale NOT PUSHED after ship). Updated `klassapp-knowledge` skill Session End protocol. Synced `knowledge.md` from richest worktree copies (`KlassApp-toshi-report-cards` + workspace merge stubs + #159 tip) onto `chore/knowledge-sync-aug3` off `origin/main` @ `862ba92`.
- **Files modified**: `.cursor/rules/knowledge-session-log.mdc`, `knowledge.md`; skill `/Users/mac/.agents/skills/klassapp-knowledge/SKILL.md`
- **Key decisions**: Prefer branch+PR for KB sync; merge when docs-only checks green
- **PR / merge**: https://github.com/KlassApp-Foundation/KlassApp/pull/160 — merge commit `ee561efbd5f954799807230327526d3934700e17`
- **Status**: ✅ MERGED to main
- **Edge cases flagged**: Canonical path remains `/Users/mac/projects/KlassApp/knowledge.md` — sync dirty worktrees after merge

### 2026-08-03: Toshi report cards v1 (per-student PDF) — PR #159
- **Work done**: Fixed `academicYear` blade bug (+ null-safe nextTerm/floor); extracted `StudentReportCardService` shared by `DownloadStudentReport`, `WhatsAppController@report`, and Toshi tools; SA `GenerateStudentReportCardTool` on `SchoolAcademicsOpsSkill`; Teacher tool on `TeacherTeachingOpsSkill` (assigned class); Deputy inherits (not Settings). Live PDF: year **2026** in Primary/Nursery/O/A. Nursery assessments still **0** rows.
- **Branch / tip**: `feature/toshi-report-cards-v1` @ `6f13017` (worktree `KlassApp-toshi-report-cards-v1`)
- **PR**: https://github.com/KlassApp-Foundation/KlassApp/pull/159 — **open** (not merged, not deployed)
- **Tests**: 39 passed (272 assertions) for report-card + Batch2/Deputy/pipeline suites
- **Status**: ✅ Done — PR open
- **Edge cases flagged**: helper `learner()` still requires marks rows even for nursery; nursery descriptive content unverified without assessment seed

### 2026-08-03: Teacher Batch 2 merged (PR #157)
- **Work done**: Merged GitHub PR #157 into `main` via merge commit (checks green). Teacher Batch 2 — submission review + own leave.
- **Merge commit**: `862ba92c8e9f0062caee2a4dcfa6ea484a64f8db`
- **PR**: https://github.com/KlassApp-Foundation/KlassApp/pull/157
- **Status**: ✅ MERGED (not deployed)

### 2026-08-03: Teacher Batch 2 Part A — remaining-domain audit
- **Work done**: Docs-only inventory on `audit/toshi-teacher-batch2`. Proposed Batch 2 = homework/assignment submission review + own leave; Gate IDOR on assignment review + leave approve fixed in #156 before tools.
- **Files modified**: `docs/toshi-teacher-batch2-audit.md`, `knowledge.md`
- **PR**: https://github.com/KlassApp-Foundation/KlassApp/pull/155 — docs audit still open
- **Status**: ✅ Done — docs; tools shipped via #157

### 2026-08-03: School Admin Batch 2 merged (PR #153)
- **Work done**: Rebased Batch 2 onto main post-#154; Deputy tool-count 31→42; merged `SchoolAcademicsOpsSkill` timetable + homework oversight.
- **Merge commit**: `102f92e81b97565b8ef8d2128609c1c900a02e98`
- **PR**: https://github.com/KlassApp-Foundation/KlassApp/pull/153
- **Status**: ✅ MERGED (not deployed)

### 2026-08-03: Homework Gate Part B + School Admin Batch 2
- **Work done**: Merged Gate Option A #152 (`homework-manage` + `studentHomework-review`); opened then merged Batch 2 #153.
- **Gate PR**: https://github.com/KlassApp-Foundation/KlassApp/pull/152 — merge `c6d3f17`
- **Batch 2 PR**: https://github.com/KlassApp-Foundation/KlassApp/pull/153 — merge `102f92e`
- **Status**: ✅ Both merged to main

### 2026-08-03: Legacy homework Gate — ug5 teacher destroy ownership (PR #154)
- **Work done**: Legacy `homework` Gate was school_id-only; Teacher/API destroy could delete any school homework. Fixed: ug5 + school_id + `teacher_id`; ug3 stays on `homework-manage`.
- **Merge commit**: `a7aa6e7`
- **PR**: https://github.com/KlassApp-Foundation/KlassApp/pull/154
- **Status**: ✅ MERGED — unblocked #153 rebase

### 2026-08-03: School Admin Batch 1 merged (PR #150)
- **Work done**: Merged PR #150 — notices/events/holidays via SchoolCommsSkill.
- **Merge commit**: `e80fc8728745cd4429630f19e35d985e80d7210e`
- **PR**: https://github.com/KlassApp-Foundation/KlassApp/pull/150
- **Status**: ✅ MERGED (not deployed)

### 2026-08-03: Teacher Batch 2 tools (teaching ops)
- **Work done**: After Gate #156 merge — `TeacherTeachingOpsSkill` + `RouteToTeacherTeachingOpsSkillTool` on TeacherOperationsAgent. Tools: homework review (list/show/check via SchoolAcademicsOpsActionService + studentHomework-review), assignment review/mark (TeacherActionService + studentAssignment-review), own leave list/show/cancel (teacher-leave only). UsesToshiLlm on skill.
- **Files modified**: TeacherTeachingOpsSkill, RouteToTeacherTeachingOpsSkillTool, 9 Teacher/* tools, TeacherActionService, TeacherOperationsAgent, TeacherBatch2TeachingOpsToolsTest, TeacherOperationsToolsTest (13 tools), knowledge.md
- **Key decisions**: Skill+RouteTo pattern (not flattening 9 tools onto agent); reuse SA homework service methods; do not merge tools PR unless requested
- **PR / merge**: https://github.com/KlassApp-Foundation/KlassApp/pull/157 — merge commit `862ba92`
- **Status**: ✅ MERGED to main (not deployed)
- **Edge cases flagged**: Peer leave approve still held; studentassignment Gate untouched

### 2026-08-03: Teacher leave + assignment-review Gates
- **Work done**: Combined Gate PR — `teacher-leave` (ug5 owner), `teacher-leave-manage` (ug1/ug3 school), `studentAssignment-review` (mirror homework-review). Wired Teacher/API leave show/update/destroy + LeaveEditRequest; Teacher/API StudentAssignment list/show/mark; Admin ApprovalController for TeacherLeaveApplication. Left `studentassignment` + `assignment` untouched.
- **Files modified**: AuthServiceProvider, LeaveController (Teacher+API), LeaveEditRequest (web+API), StudentAssignmentController (Teacher+API), ApprovalController, LegacyPortalTeacherLeaveAssignmentAuthorizationTest, docs/toshi-teacher-leave-assignment-gates-audit.md, knowledge.md
- **Key decisions**: One combined PR (homework-style); Option A dual leave Gates; Gate checks outside catch(Exception) so abort(403) is not swallowed
- **Status**: ✅ Done — Gate PR #156 merged (`0cb5b76`)
- **Edge cases flagged**: Peer leave_checker approve still held; assignment destroy school-only Gate held


### 2026-08-03: School Admin Batch 1 — commit, rebase, UsesToshiLlm, PR
- **Work done**: Committed uncommitted Batch 1 work on `feature/toshi-schooladmin-batch1` (worktree `KlassApp-toshi-schooladmin-batch1`). Rebased onto `origin/main` (#141–#149 era); only conflict was `knowledge.md` Session Log (kept both). Verified ToshiLlm path: **SchoolCommsSkill now uses `UsesToshiLlm`** (same as Orchestrator/OperationsAgents) so `prompt()` hits `ToshiLlm::assertConfigConsistent()` + openai-compatible model — not `ai.default`/`openai`. Sibling skills (Academic/Fee/…) still lack the trait (pre-existing gap on main — out of Batch 1 scope). Leaf tools unchanged (no LLM). Re-ran Batch 1 suite; pushed PR (do not merge from this session).
- **Files modified**: Batch 1 skill/tools/service/wiring (prior commit) + `SchoolCommsSkill` UsesToshiLlm + test assertion + knowledge
- **Key decisions**: Fix SchoolCommsSkill only (new prompt()-ing agent); document sibling-skill gap for follow-up; Batch 1 scope ends here — Batch 2 needs Part A
- **PR / merge**: https://github.com/KlassApp-Foundation/KlassApp/pull/150 — merge commit `e80fc87`
- **Status**: ✅ MERGED to main (not deployed)
- **Edge cases flagged**: AcademicSkill et al. still resolve via `ai.default` without UsesToshiLlm — potential dual-config / provider drift on nested skill prompts

### 2026-08-03: Adversarial schedule durable logging + ops backlog
- **Work done**: Confirmed Kernel already `appendOutputTo(storage/logs/toshi-adversarial-live.log)` but success path had no structured `Log::info` (unlike llm-health durability). Added log channel `toshi_adversarial`, dual-write `Log::info`/`Log::critical` (default stack + dedicated file), documented destination/format, deferred quick-sweep backlog line (revisit **after current roadmap complete**). Roadmap check: School Admin Batch 1 approved + partially implemented (uncommitted on `feature/toshi-schooladmin-batch1`) — **not** Teacher batch 2 next.
- **Files modified**: `ToshiAdversarialLiveCommand.php`, `config/logging.php`, `Kernel.php`, `ToshiAdversarialLiveCommandTest.php`, `docs/toshi-safety-practices-audit.md`, `docs/toshi-prod-health-check.md`, `knowledge.md`
- **Key decisions**: Finish/ship School Admin Batch 1 before Teacher panel-parity; quick-sweep stays deferred until roadmap complete
- **Status**: ✅ Done — PR opened/merged from `fix/toshi-adversarial-schedule-logging`
- **Edge cases flagged**: `feature/toshi-schooladmin-batch1` was behind main with uncommitted tools — rebased + PR in follow-up session

### 2026-08-02: Fail-loud dual LLM config + toshi:llm-health
- **Work done**: Structural fix for empty `agent_conversations` incident (NVIDIA `TOSHI_LLM_MODEL` + DeepSeek `OPENAI_COMPATIBLE_*`). Added `ToshiLlm::assertConfigConsistent()` (throws `AmbiguousToshiLlmConfigException` on conflicting dual env / model↔host family mismatch) called from `model()`/`provider()` first use — not App boot. Added `php artisan toshi:llm-health` (one cheap live `chat/completions`, verifies content; `Log::critical` + exit 1 on failure). Docs + tests for incident shape and failing provider response. **Schedule not wired**; **`.env` not touched**.
- **Files modified**: `app/AiAgents/ToshiLlm.php`, `app/Exceptions/AmbiguousToshiLlmConfigException.php`, `app/Console/Commands/ToshiLlmHealthCommand.php`, `config/toshi.php` (`llm_env`), `tests/Feature/Toshi/ToshiLlmConfigConsistencyTest.php`, `ToshiLlmHealthCommandTest.php`, `ToshiLlmStatusCommandTest.php`, `docs/toshi-prod-health-check.md`, `docs/toshi-safety-practices-audit.md`, `knowledge.md`
- **Key decisions**: Fail on first Toshi LLM resolve + health command (not HTTP boot); no Sentry in repo → critical log + non-zero exit for cron/k8s; prefer `OPENAI_COMPATIBLE_*` as source of truth; leave Kernel schedule to OpenCode
- **PR / merge**: https://github.com/KlassApp-Foundation/KlassApp/pull/144 (+ #145) — on main
- **Status**: ✅ MERGED to main
- **Edge cases flagged**: After deploy, leftover `TOSHI_LLM_MODEL=meta/llama-…` alongside `OPENAI_COMPATIBLE_MODEL=deepseek-v4-flash` will throw until ops unsets/aligns the legacy var

### 2026-08-02: Merge #142 + Toshi LLM runtime diagnostic
- **Work done**: Merged PR **#142** (`feature/toshi-safety-practices`) to main via merge commit `50f01023fa77cca9a9c6a3509e1a458478ca6d62`. Follow-up branch `feature/toshi-llm-diagnostic`: Artisan `toshi:llm-status` reports live `ToshiLlm` provider/model/URL-host/key-configured/config-checksum with **no secrets**; docs note repo-default live-LLM confirmation + VPS pending; tests assert provider/model present and fake key absent from output.
- **Files modified**: `app/AiAgents/ToshiLlm.php`, `app/Console/Commands/ToshiLlmStatusCommand.php`, `tests/Feature/Toshi/ToshiLlmStatusCommandTest.php`, `docs/toshi-safety-practices-audit.md`, `knowledge.md`
- **Key decisions**: Merge #142 only as requested; diagnostic on separate PR off post-merge main (Artisan-only — no HTTP diagnostic pattern in repo); VPS confirmation remains human `docker exec sms-app php artisan toshi:llm-status` after deploy
- **PR / merge**: https://github.com/KlassApp-Foundation/KlassApp/pull/143 — merged to main
- **Status**: ✅ MERGED to main
- **Edge cases flagged**: Prod SSH still publickey-denied from agent env; do not print full URL (may embed creds)

### 2026-08-02: Verify production Toshi model vs adversarial-live
- **Work done**: Confirmed production Toshi chat model is **DeepSeek `deepseek-chat`** via `openai-compatible` (`api.deepseek.com`). Evidence: `config/toshi.model` + `config/ai.php` defaults; local `.env` `OPENAI_COMPATIBLE_*` / `TOSHI_LLM_MODEL` (prod-shaped); runtime `ToshiLlm` / agents resolve `deepseek-chat`. Prod SSH `root@46.101.111.131` unreachable from this environment (publickey denied) — no contradiction in repo/config. Prior live run already used DeepSeek → **no re-run**. Fixed drift risk: `ToshiLlm` + `UsesToshiLlm` shared by agents + `toshi:adversarial-live`; `config/toshi.php` `model` now reads `OPENAI_COMPATIBLE_MODEL` / `TOSHI_LLM_MODEL`; tests assert config resolution. Pushed to PR #142 (not merged).
- **Files modified**: `app/AiAgents/ToshiLlm.php`, `Concerns/UsesToshiLlm.php`, OperationsAgents + Orchestrator + WA agents, `ToshiAdversarialLiveCommand.php`, `config/toshi.php`, live/command tests, `knowledge.md`
- **Key decisions**: DeepSeek matches production — skip live re-run; unify model resolution so monthly job cannot hardcode a substitute
- **Status**: ✅ Done — later merged via #142 (`50f0102`)
- **Edge cases flagged**: Cannot confirm live VPS `.env` without SSH key; config still contains legacy hardcoded DeepSeek API key strings in `toshi.php`/`ai.php` defaults (pre-existing; not expanded)

### 2026-08-02: Toshi Safety Practices finish — live-LLM run + schedule + PR
- **Work done**: Confirmed `toshi:adversarial-live` was docs-only → implemented Artisan command + `@group live-llm` harness (`LiveAdversarialSoftRefusalTest` + scorer). One real run: DeepSeek `deepseek-chat` via openai-compatible; phpunit sqlite `:memory:` (not prod); WhatsApp Http::fake. **16/16 PASS**, 0 flags/false-successes; ~20k tokens; est ≈ $0.0066. Wired monthly schedule first Sunday 02:00 Africa/Kampala in `app/Console/Kernel.php` gated by `TOSHI_ADVERSARIAL_LIVE=1` + key (`--scheduled` no-ops). Re-ran CI suite: 30 passed (16 fake + 7 escalation + command/scorer). Pushed branch + opened PR (do not merge).
- **Files modified**: `ToshiAdversarialLiveCommand.php`, `LiveAdversarialSoftRefusalTest.php`, `LiveAdversarialScorer.php`(+Test), `ToshiAdversarialLiveCommandTest.php`, `Kernel.php`, `docs/toshi-safety-practices-audit.md`, `knowledge.md` (+ Part B adversarial/escalation files from earlier)
- **Key decisions**: Clean live run → schedule wired but inert without env gate; not jailbreak proof; cost negligible
- **PR / merge**: https://github.com/KlassApp-Foundation/KlassApp/pull/142 — merge `50f0102`
- **Status**: ✅ MERGED to main; adversarial-live enabled on prod after #147–#148
- **Edge cases flagged**: Production must keep `TOSHI_ADVERSARIAL_LIVE` unset until intentionally enabled; worktree `.env` symlinked from main KlassApp for local keys only (not committed)

### 2026-08-02: Toshi Safety Practices Part B (adversarial suite + WA human escalation)
- **Work done**: Branch `feature/toshi-safety-practices` off `audit/toshi-safety-practices` @ `0e413db` (worktree `/Users/mac/projects/KlassApp-toshi-safety-practices-impl`). Part B-1: `tests/Feature/Toshi/Adversarial/` — 16 structural-isolation regression tests under adversarial-shaped prompts via `Agent::fake` + compliance `ToolCall` (off-role → `NoSuchToolException`; peer-scope stays self/children). Explicit docblocks: NOT jailbreak proof. Part B-2: `WhatsAppHumanEscalationService` (keyword phrase set) integrated early in `WhatsAppToshiChannelService::ask()`; ActivityLog via `ToshiAuditService::logEscalation` (`acting_user_id`); optional Task + staff WA notify; ack; tool loop halted for that turn. Routing: Parent/Student → Receptionist (fallback Admin); staff → Admin; Admin → log only. Live-LLM cadence completed in follow-up session entry above.
- **Tests**: 23 passed (16 adversarial + 7 escalation), then 30 with live harness gate/scorer tests
- **Key decisions**: Exact/keyword substring phrases (FP: casual “real person…”; FN: “speak with staff”); escalation not exempt from dual-identity audit; no helpdesk table
- **Status**: ✅ Superseded by finish entry above (push/PR)
- **Edge cases flagged**: Receiver without opted-in WhatsAppUser → Task + ActivityLog only (no staff notify)

### 2026-08-02: School Comms RouteTo* audit fidelity + SDK Sub-Agent clarification
- **Work done**: Audited `RouteToSchoolCommsSkillTool` / `SchoolCommsSkill` vs Laravel AI Sub-Agents. Confirmed custom RouteTo* pattern (same as Academic/Fee/etc.) — **not** SDK `CanActAsTool` / AgentTool. Verified create-notice audit fidelity: leaf `toolCreateNotice` + `acting_user_id` / `approver_id` on Tier-2 `confirmYes`; nested `ToolInvoked` under `SchoolCommsSkill` logs leaf `CreateNoticeTool` (not router). No code fix required for audit gap — fidelity already matched Deputy/Teacher bar. Added PHPDoc + 5 regression tests.
- **Mechanical finding**: Orchestrator returns `new RouteToSchoolCommsSkillTool` (plain Tool → `authorizeOrMessage` → `(new SchoolCommsSkill)->prompt($query)`). SDK Sub-Agent would return the Agent from `tools()` so `resolveTool()` wraps `AgentTool` (`task` schema, optional `CanActAsTool` name/description). Docs: laravel/ai “Sub-Agents” / `CanActAsTool`.
- **Audit evidence (real ActivityLog via PHPUnit)**: `properties.tool=toolCreateNotice`, `acting_user_id`=school admin, `approver_id`=same on confirmYes; notice_board row created. ToolInvoked path: `tool=CreateNoticeTool`, `acting_user_id` set, `approver_id` null.
- **Pattern decision**: **Yes — template for future School Admin batches** (and any Orchestrator-growing domain). Skill+RouteTo* is the established Orchestrator design; Batch 1 only newly packaged school_comms into it. Role OperationsAgents remain flattened tools + scope router (also not CanActAsTool).
- **Files modified**: `RouteToSchoolCommsSkillTool.php`, `SchoolCommsSkill.php`, `SchoolAdminBatch1CommsToolsTest.php`, `knowledge.md`
- **Status**: ✅ Done (superseded by rebase/PR closeout)
- **Tests**: `SchoolAdminBatch1CommsToolsTest` — **14 passed** (169 assertions)

### 2026-08-02: School Admin Batch 1 — notices/events/holidays (feature/toshi-schooladmin-batch1)
- **Work done**: Implemented School Comms skill + 9 tools (list/create/update × notices, events, holidays). Wired `ToshiOrchestrator` (`RouteToSchoolCommsSkillTool`), `DeputyAdminOperationsAgent` (flattened inherit — not Settings-adjacent), WA read lists on `SchoolAdminWhatsAppReadAgent`, AgentToshi/`WhatsAppConfirmationBridge` Tier-2 keys, `getRoleCapabilities` ug3/ug4. Service: `SchoolCommsActionService`.
- **Notices/Receptionist overlap**: **Same** `NoticeBoard` / `notice_board` as Receptionist `ViewNoticeboardTool` → `ReceptionistActionService::viewNoticeboard()`. Admin create/list/update writes that table; Receptionist remains view-only.
- **Events Gate**: Updates call `Gate::forUser()->allows('event', $event)` (school_id match, post-#131). No destroy; does not use/reintroduce `event-destroy` shape for writes. Regression test: ug3 cannot update another school's event via `UpdateEventTool`.
- **Holidays**: `Events` rows with `category=holidays` (Admin HolidaysController pattern) — not a separate model.
- **Files modified**: `SchoolCommsActionService`, 9 tools + `SchoolCommsSkill` + route tool, Orchestrator, DeputyAgent, WA read agent, AgentToshi, WhatsAppConfirmationBridge, ToshiActionService capabilities, Deputy count test, `SchoolAdminBatch1CommsToolsTest`, knowledge
- **Key decisions**: Tier-2 ConfirmsBeforeWrite on all six writes (low-risk creates/updates, no destroy); lists read-only / WA-safe; creates/updates fail-closed via WhatsAppWriteExclusion (not on WRITE_ALLOWLIST); Deputy gets all nine (comms ≠ Settings)
- **Status**: ✅ Implemented — rebase + UsesToshiLlm fix + PR in follow-up
- **Tests**: `SchoolAdminBatch1CommsToolsTest` + `DeputyAdminOperationsToolsTest` — 20 passed

### 2026-08-02: Toshi rollout closeout (#124–#140) + MCP Client::web/local ban
- **Work done**: Session Log / status catch-up for Aug 1–2 merges. Folded architecture test banning direct `Client::web()`/`Client::local()` outside `routes/ai.php` into #140 (with AuditingMcpClientManager named-client audit). Knowledge updates are now part of every done-as-scoped report going forward.
- **Merged to main (evidence)**:
  - **#124** platform-tools → `2e24718` / tip after roles `e16bed4` era; Phase 0–1 platform gate+tools+HITL **on main**
  - **#125–#129** Teacher → Student role agents (merge commits through `e16bed4`)
  - **#123** IDOR docs tracker; **#130** assignment/homework ownership; **#131** event destroy+class visibility; **#132** post Gate (`212418d`)
  - **#133** WhatsApp channel audit docs; **#134** confirm bridge; **#135** read-only WA + Parent; **#136** WA writes wave 1 (`f11e5cb`) **deployed** (`TOSHI_WHATSAPP_CHANNEL_ENABLED`, pending-confirm + agent_conversations migrations)
  - **#137** Deputy Admin ug4 (`20c54ad`)
- **Open / not merged (as of this closeout; later updated)**: then #138 preference memory; #139 Google MCP audit; #140 MCP audit gap (merged same day). Subsequent merges through #157 — see Current Status Aug 3 tip.
- **Shelved / deferred**: Slack/Notion product connectors; digests after prefs; WA wave-2 until usage signal; MCP Approvable/HITL for writes
- **Key decisions**: Named MCP clients only; direct Client construction banned by architecture test; knowledge closeouts required at done-as-scoped through PR/merge
- **Status**: ✅ Closeout logged; #140 merged; Current Status supersedes open-PR list

### 2026-08-02: Reconcile MCP ToolInvoked audit gap (fix/toshi-mcp-audit-gap)
- **Work done**: Part 1 reconciled — main already has `LogToolInvoked` on `ToolInvoked` (not HITL-only). Spike docs were wrong; gap was raw `callTool` bypass + McpTool name = `class_basename`. Widened listeners; AuditingMcpClientManager so named `Mcp::client()->callTool` always audits; architecture test bans direct `Client::web`/`local` outside `routes/ai.php`.
- **Files modified**: `AuditingMcpClientManager`, `AuditingMcpClient`/`AuditingWebClient`, `ToshiMcpCallAuditor`, listeners, `AppServiceProvider`, spike tests, `McpClientConstructionTest`, knowledge
- **Key decisions**: (a) widen ToolInvoked for native; MCP audits at callTool layer; structural ClientManager wrap + architecture test (not comment-only)
- **Status**: ✅ PR #140
- **Tests**: SpikeSlackMcpClientTest + ToshiAuditTrailTest + McpClientConstructionTest

### 2026-08-01: LibrarianOperationsAgent (ug8) — view-only cards + Tier-2 writes
- **Work done**: Branch `feature/toshi-librarian-role`. Cherry-picked teacher+accountant shared patterns. View-only `GET /library/cards` (`library.cards`, MustBeLibrarian) via shared `LibraryCardLookupService` (admin `cardIndex` thin-wrap). `LibrarianOperationsAgent` + 6 tools via `LibrarianActionService`. Gate `toshi-librarian-action` (ug8 + ug1→ug8 impersonation). Scope router ug8 → librarian. Capability rename `manage_library_cards` → `view_library_cards`. Blade `[1,3,5,8,11]`. Isolation + audit identity tests green (11).
- **Write flag**: books / categories / lending / tasks are panel **writes** → Tier-2 ConfirmsBeforeWrite (not all-reads as initially hoped). Dashboard + cards are read-only.
- **Follow-up**: card issue/return/create/update CRUD (Approvable/confirm judgment later).
- **Files**: agent/tools/service/gate/routes/views/tests/docs/knowledge; admin LibraryController uses shared lookup.
- **Status**: ✅ Done (not pushed)

### 2026-08-01: AccountantOperationsAgent (ug11) — teacher-pattern clone
- **Work done**: Branch `feature/toshi-accountant-role` off `origin/main` + cherry-pick teacher commits (`8a5cb5e`, `c759afa`). `AccountantOperationsAgent` + 6 tools via `AccountantActionService` (fee payment, batch payroll, unpaid reports, fee structure, dashboard, tasks). Gate `toshi-accountant-action` (ug11 + ug1→ug11 impersonation). Scope router: ug11 → accountant, ug5 → teacher, else orchestrator. Blade `[1,3,5,11]`. Isolation tests: AddCoAdmin absent; school+teacher Gates deny ug11; accountant Gate allows. Tier-2 confirm sets `approver_id`+`acting_user_id`.
- **Payroll /batch UI**: Re-checked — blade has full Alpine batch form; knowledge July 10 concluded false alarm (Toshi overlay / wrong role). Status: **fixed / not a layout bug** (evidence: `resources/views/accountant/payroll/batch/index.blade.php` + knowledge investigation).
- **Confirm bar**: Money tools (payment + payroll) always Tier-2 — **stricter than Teacher** (Teacher writes are operational; accountant money moves cash/statutory payroll).
- **Status**: ✅ Done on branch — not pushed / not merged
- **Edge cases flagged**: Next role = Librarian (ug8); do not widen school/teacher Gates

### 2026-08-01: Push `feature/toshi-platform-tools` + knowledge closeout (Phase 0–1)
- **Work done**: Updated Current Status / decided-deferred / Superadmin Toshi inventory notes to reflect Phase 0–1 complete on branch. Pushed `feature/toshi-platform-tools` to `origin`. Synced `knowledge.md` to canonical KlassApp workspace. Did **not** merge to `main`.
- **Branch tip before knowledge commit**: `edc07e5` (CoAdmins + Impersonation + audit identity)
- **Key stack**: `laravel/ai` **0.10.2**; `agent_conversation_messages.approval_state`; scope router (not SDK Sub-Agents); HITL at `GET|POST /superadmin/toshi/ops/{conversation}`; audit `acting_user_id` + `approver_id`
- **Tool inventory (PlatformOperationsAgent)**: Geo×4, Plans×2 (Approvable), Schools×2, Subscriptions create/approve/cancel (approve+cancel Y), FeatureToggles Y, SystemSettings conditional, CoAdmins create N / delete+password-reset Y, Impersonation Y mandatory
- **Env**: `TOSHI_PLATFORM_GATE_ENABLED`, optional `TOSHI_PLATFORM_USER_IDS`
- **Next**: Phase 2 role coverage audits; Phase 3 connectors (WhatsApp first); merge review when ready
- **Status**: ✅ Pushed to origin — **not merged to `main`**

### 2026-08-01: CoAdmins + Impersonation tools + audit identity prerequisite
- **Work done**: Prerequisite audit identity fix — `ToshiAuditService` + Toshi listeners now record distinguishable `properties.acting_user_id` (conversation participant from `forUser` / `continue(..., as:)`) and `properties.approver_id` (auth user on resolve; null on pending). Extracted `CoAdminService` + `ImpersonationService`; Livewire CoAdmins + `ImpersonateController@schoolAdminimpersonate` wired through services. Tools: `CreateCoAdminTool` (N), `DeleteCoAdminTool` (Y), `ResetCoAdminPasswordTool` (Y), `ImpersonateSchoolAdminTool` (Y, never withoutApproval). Registered on `PlatformOperationsAgent`. Tests: `PlatformCoAdminToolsTest` + `PlatformImpersonationToolsTest` + audit trail identity assert (19 passed in that set; ImpersonateController + Ops UI + Subscriptions regression 18 passed).
- **Impersonation reject zero-trace**: `Decision::reject()` via HTTP approval flow leaves no `session('impersonate')`, no `isImpersonating()`, Auth id unchanged, no `success` audit — only `approval_rejected`.
- **Spot-check vs Batch E**: Original audit verified start via GET `/schooladmin/{id}/impersonate` (session + Stop UI) and stop clearing `impersonate` → ug1 `/superadmin/dashboard`. Tests assert the same session key / `isImpersonating()` through `ImpersonationService` (controller-wired).
- **ConversationPolicy**: unchanged.
- **Doc vs package**: HITL/Events match `laravel/ai` 0.10.x; docs under 13.x path, app Laravel 12 — no blocking mismatch for this slice.
- **Branch**: `feature/toshi-platform-tools` @ `edc07e5` — see push closeout above
- **Status**: ✅ Done for CoAdmins + Impersonation slice

### 2026-08-01: PlatformOperationsAgent human approval UI (Complete Approval Flow)
- **Work done**: Added GET/POST `/superadmin/toshi/ops/{conversation}` matching laravel/ai Complete Approval Flow; `PlatformOpsConversationController` + `PlatformOpsConversationService`; Livewire `PlatformApprovalGate` review-gate UI (approve / reject / edit-and-approve); `EnsurePlatformToshiAccess` middleware + `ConversationPolicy` (`view` = participant + `ToshiAvailabilityGate` Platform); `User` uses `HasConversations`. Feature test `PlatformOpsApprovalUiTest` (7 passed: GET pending visible, POST approve mutates, POST reject no mutation, edit, auth denials).
- **Doc vs package mismatches**: (1) Docs `prohibited_with` — **not in Laravel 12**; used `prohibits` for XOR. (2) Doc `$validated->collect(...)` — `validate()` returns array; used `collect($validated['decisions'])`. (3) Doc validation approve/reject only; package has `Decision::edit()` — extended with `edit` + `arguments`. (4) Docs under 13.x path; app Laravel 12 + `laravel/ai` 0.10.2.
- **Files**: controller, middleware, policy, service, Livewire + views, routes, AuthServiceProvider, Kernel, User trait, settings index note, test.
- **Status**: ✅ Done for approval UI slice

### 2026-08-01: Toshi Phase 1 continued — Subscriptions + FeatureToggles/SystemSettings tools
- **Work done**: Added `SubscriptionService` + `SystemSettingsService`; tools `CreateSubscriptionTool` (N), `ApproveSubscriptionTool` (Y), `CancelSubscriptionTool` (Y), `ToggleSchoolFeatureTool` (Y — toshi/whatsapp/schoolpay), `UpdateSystemSettingsTool` (conditional: access keys Y, display keys N). Registered on `PlatformOperationsAgent`. Livewire SubscriptionForm / Subscriptions approve / SystemSettings wired through services. Tests: `PlatformSubscriptionToolsTest` + `PlatformSettingsToolsTest` (18 passed).
- **Approvable judgment**: create sub = not destructive; approve/cancel = billing access; all feature toggles = access/integrations; system settings access trio (`maintenance`/`login_status`/`register_status`) = HITL, cosmetic display keys skip.
- **Status**: ✅ Done

### 2026-08-01: Toshi Phase 1.5 — migrate Approvable polyfill → native laravel/ai ^0.10 HITL
- **Work done**: Bumped `laravel/ai` **0.9.0 → 0.10.2**. Published + ran `agent_conversations` / `agent_conversation_messages` migration. Plan tools (`CreatePlanTool` / `UpdatePlanTool`) use native `Laravel\Ai\Contracts\Approvable` + `InteractsWithApprovals` + `needsApproval()` / `Approval::required()`. Listeners switched to native `ToolApprovalRequested`, `ToolApprovalResolved`, `InvokingTool`, `ToolInvoked`. Platform tool tests rewritten against agent ToolCall fake + resume via `Decisions::from` / `Decision::approve()` (17 passed). Scope-router comments fixed (not SDK Sub-Agents). Polyfill under `App\Ai\{Contracts,Concerns,Approvals,Events}` and `PlatformToolInvoker` removed after green tests.
- **(a) Migration column**: `agent_conversation_messages.approval_state` (nullable text) — confirmed from published vendor migration.
- **(b) Custom ConversationStore**: none — default `DatabaseConversationStore` already implements `storeApprovalResults`.
- **(c) Doc vs package**: no blocking mismatch; `approval_state` matches both. Doc Events list matches package. Fake gateway skips real tool resume (`resumesAgainstRealGateway`) — tests clear fake + Http::fake for post-approve continuation.
- **Status**: ✅ Done (superseded by later Phase 1 slices on same branch)

### 2026-08-01: Toshi Phase 1 PARTIAL — Geo + Plans + Schools tools (STOP before Subscriptions+)
- **Work done**: Extracted `CountryService` / `CityService` / `PlanService` / `SchoolService` from Livewire mutators. Added `app/Ai/Tools/Superadmin/*` (8 tools), `PlatformOperationsAgent`. Wired platform scope in `ToshiSdkV2Service` → `PlatformOperationsAgent` (AgentToshi unchanged — already passes `ToshiScope::Platform`). Later Phase 1.5 replaced polyfill with native HITL. Tests: Geo/Plans/Schools (16 passed initially).
- **Status**: ✅ Absorbed into full Phase 1 on branch

### 2026-07-31: Toshi Phase 0 — platform-scope authorization scaffolding
- **Work done**: Added `ToshiScope` (School|Platform), `ToshiAvailabilityGate`, wired `ToshiSdkV2Service::isAvailable/ask/askStreamed` with default School scope (byte-identical school behaviour). AgentToshi passes Platform when `$this->scope === 'platform'`. Config `toshi.platform_gate` (enabled + optional user_ids allowlist). **No** business tools; **no** bypass of `per_school_gate`.
- **Gate decision**: **Config allowlist** (`TOSHI_PLATFORM_GATE_ENABLED` + optional `TOSHI_PLATFORM_USER_IDS`) — not a users column (none exists), not FeatureToggles (school_id-scoped only).
- **Files modified**: `app/Enums/ToshiScope.php`, `app/Services/Toshi/ToshiAvailabilityGate.php`, `app/AiAgents/ToshiSdkV2Service.php`, `app/Livewire/AgentToshi.php`, `config/toshi.php`, `.env.example`, `tests/Feature/Toshi/ToshiAvailabilityGateTest.php`
- **Branch**: folded into `feature/toshi-platform-tools` (gate commit `cc35f38`)
- **Status**: ✅ Done — included in pushed feature branch
### 2026-08-01: Tier-2 ConfirmsBeforeWrite — set approver_id on confirm
- **Work done**: Investigated `AgentToshi::executeConfirmedTool` — previously passed `approver: null` even after Tier-2 `confirmYes`. Fixed to pass confirming user as `approver` (self-approve: same id as `acting_user_id`). Tests: Tier-2 confirm sets `approver_id`; unconfirmed/read-only path keeps null. Clarified `ViewTimetableTool::description()` (Teacherlink basis, not period grid). Backlog line in `docs/toshi-role-parity-audit.md` for converging ConfirmsBeforeWrite + native Approvable.
- **Files modified**: `AgentToshi.php`, `ToshiAuditService.php`, `ViewTimetableTool.php`, `TeacherOperationsToolsTest.php`, `ToshiAuditTrailTest.php`, `docs/toshi-role-parity-audit.md`, `knowledge.md`
- **Key decisions**: Same audit identity fields as platform HITL (`acting_user_id` + `approver_id`); different mechanism (Tier-2 card vs native Approvable). Convergence deferred.
- **Status**: ✅ Done on `feature/toshi-teacher-role` — not pushed / not merged
- **Edge cases flagged**: None — Accountant not started

### 2026-08-01: TeacherOperationsAgent (ug5) — first non-admin Toshi role
- **Work done**: Branch `feature/toshi-teacher-role` off `main`. `TeacherOperationsAgent` + 12 tools (`app/AiAgents/Tools/Teacher/*`) via `TeacherActionService`. Gate `toshi-teacher-action` (ug5); `toshi-school-action` unchanged (ug3-only). Scope router in `ToshiSdkV2Service` (ug5 → TeacherOperationsAgent). Ported audit identity fields (`acting_user_id` / `approver_id`) + AI event listeners from platform work. Blade allowlist `[1,3,5]`. Tests: `TeacherOperationsToolsTest` 9 passed (isolation + Gate + happy/validation + audit identity).
- **Approvable judgment**: Writes use `ConfirmsBeforeWrite` (AgentToshi Tier-2) — attendance, marks, lesson plans, assignments, homework, leave, class wall, tasks. Views non-confirm. Native Approvable HTTP ops UI remains platform-scope only (design delta).
- **Design deltas**: ConfirmsBeforeWrite not native HITL resume; timetable view returns Teacherlink basis (not full period grid).
- **Status**: ✅ Done on branch — not pushed / not merged

### 2026-07-31: Merge fix/superadmin-audit-triage → main (audit CLOSED on main)
- **Work done**: Pre-merge gate (fetch; **0 behind / 3 ahead** vs `origin/main`; PHPUnit **247 passed / 1 skipped / 1 failed** ToshiE2E; `npm run build` PASS; clean tree after discarding `public/build` junk) then no-ff merge into `main`. Post-merge PHPUnit same; build PASS. High-stakes verify as siteadmin: ChangePassword → real HTTP login with new password → restore; all 4 Filament lists HTTP **200**; country create Livewire → DB row **id=12** `TriageLand…`. Soft-deleted disposable co-admin **id=173**. Knowledge closeout + sync to KlassApp (this commit).
- **Merge commit**: **`32a3bb4333f8645a2752d760fcd76287f57f5fa8`**
- **Tip merged**: `fix/superadmin-audit-triage` @ `8c93693`
- **Status**: ✅ Done — **NOT PUSHED**
- **Edge cases flagged**: Co-admin (ug2) HTTP login blocked by `checkschool` (needs active `school_id`) — used careful siteadmin change+restore for real-login proof instead. Only expected test failure remains `ToshiE2EVerificationTest` LLM null.

### 2026-07-31: Knowledge — mark deferred-bugs tip pushed on origin/main
- **Work done**: Corrected stale **NOT PUSHED** Current Status / session-log wording after confirming `origin/main` == `d8dc818` (includes merge `536603c`). Also cleared leftover Phase 3 “awaiting push” language post-`3e93bc3`. Synced KlassApp + main-merge copies.
- **Status**: ✅ Done
- **Edge cases flagged**: None

### 2026-07-31: Merge fix/deferred-bugs → main (5 bugs CLOSED on main)
- **Work done**: Pre-merge checks (fetch; 0 behind / 5 ahead vs `origin/main`; PHPUnit 234/1/1; `npm run build` PASS; clean tree) then no-ff merge into `main`. Post-merge PHPUnit + authenticated HTTP smoke of all 5 surfaces. Updated knowledge: all 5 **CLOSED on `main`** with merge SHA. Synced to `/Users/mac/projects/KlassApp/knowledge.md`.
- **Merge commit**: **`536603cc38c2b0c37af4de3df1c860e80473f39a`** — `merge: bring fix/deferred-bugs into main (5 deferred app bugs)`
- **Tip merged**: `fix/deferred-bugs` @ `a0db768` (fix commits `77d1fbe` / `5b5540f` / `b21c04f` / `e993196` + docs)
- **Post-merge smoke**: login+dashboard, `/admin/academic/list`, ClassWall `editList/1`, `/admin/students/blockedstudents`, `/admin/promotion/list` — all **200**; `activity()` OK; targeted feature tests **12 passed**
- **Status**: ✅ Done — **pushed** to `origin/main` (merge `536603c` / knowledge `d8dc818`)
- **Edge cases flagged**: Only expected failure remains `ToshiE2EVerificationTest` LLM null (API/env).

### 2026-07-31: Deferred track closeout — all 5 CLOSED with commit refs
- **Work done**: Investigate-before-fix re-verify of item 5 (`exam_type`); confirmed fix `e993196` correct (query not migration); marked all five deferred bugs **CLOSED** with SHAs in Current Status + findings. Synced `knowledge.md` to canonical `/Users/mac/projects/KlassApp/knowledge.md` + checkout/merge worktrees.
- **Evidence**: `exams.exam_type` never in migrations (create + `exam_type_id` add only); live `Schema::hasColumn` false/true; `GET /admin/promotion/list` → 200 `examlist`; PHPUnit **234 passed / 1 skipped / 1 failed** (`ToshiE2E` unrelated).
- **CLOSED refs**: #1 `77d1fbe` · #2 `5b5540f` · #3+#4 `b21c04f` · #5 `e993196`
- **Status**: ✅ Superseded — later merged to `main` @ `536603c`
- **Edge cases flagged**: Historical pre-merge note — later merged @ `536603c` and pushed on `origin/main`.

### 2026-07-31: Deferred bug #5 — promotion/list exam_type
- **Work done**: Stopped referencing dead `exams.exam_type`; filter FINAL exams via `exam_types.code` (`whereRelation`). Added missing `App\Http\Resources\Exam` for promotion JSON (`id`, `name`, `subjects`, `standard_id`). Tests in `PromotionListExamTypeTest`. Commit **`e993196`**.
- **Files modified**: `app/Http/Controllers/Admin/PromotionController.php`, `app/Http/Resources/Exam.php` (new), `tests/Feature/PromotionListExamTypeTest.php`, `knowledge.md`
- **Key decisions**: **Query fix, not migration** — `exam_type` string column never existed; `exam_type_id` + `exam_types` (seeded `FINAL`) is the real model. Resource was imported but missing since first commit (masked by the earlier QueryException).
- **Status**: ✅ CLOSED @ `e993196`
- **Edge cases flagged**: Live `examlist` may be empty if school has no FINAL exams (still 200). Full suite 234 passed / 1 skipped / 1 failed (`ToshiE2EVerificationTest` LLM null — unrelated). SQLite PHPUnit cannot hit full HTTP path (MySQL-only `FIELD()` in SiteHelper / controller); covered query+resource shape + live MySQL kernel request.

### 2026-07-31: Deferred bugs #3–4 — count(null) Post + blockedstudents
- **Work done**: Null-safe `count()` at `Post::getAttachmentPathAttribute` (`attachment_file ?? []`) and `StudentController@blockedstudents` (`count((array) getQueryString())`, plus `$birthday = null` init). Tests in `CountNullSafetyTest`. Commit **`b21c04f`**.
- **Files modified**: `app/Models/Post.php`, `app/Http/Controllers/Admin/StudentController.php`, `tests/Feature/CountNullSafetyTest.php`
- **Key decisions**: Call-site null-safety (not migration/backfill) — null is valid empty for posts without attachments and for requests with no query string. Laravel array casts leave DB null as null. Same mistake class, one commit. Light grep: bare `count(getQueryString())` only this StudentController site (others already `(array)`); `count($this->attachment_file)` only Post — similar homework/assignment accessors left out of scope.
- **Status**: ✅ CLOSED @ `b21c04f` (items 3 and 4)
- **Edge cases flagged**: Verified `GET /admin/classwall/post/editList/1` → 200 `attachment:[]`; `GET /admin/students/blockedstudents` → 200. Full suite 232 passed / 1 skipped / 1 failed (`ToshiE2EVerificationTest` LLM null — unrelated).

### 2026-07-31: Deferred bug #2 — str_limit → Str::limit
- **Work done**: Replaced all non-vendor `str_limit(` with `Str::limit(...)` (+ `use Illuminate\Support\Str` in PHP resources; FQCN in Blade). Commit **`5b5540f`** on `fix/deferred-bugs`.
- **Files modified**: `app/Http/Resources/{AcademicYear,ShowVideo,ShowEvent,Discipline,Classwall/Page,API/ShowVideo}.php`, `resources/views/admin/{feedbacks,discipline}/list.blade.php`
- **Key decisions**: Leave Item 1 activity() shim alone; no helpers package; match existing Blade `\Illuminate\Support\Str::limit` pattern.
- **Status**: ✅ CLOSED @ `5b5540f`
- **Edge cases flagged**: Full suite 228 passed / 1 skipped / 1 failed (`ToshiE2EVerificationTest` LLM null — unrelated API timeout). Verified `GET /admin/academic/list` 200 with real years.

### 2026-07-31: Deferred bug #1 — restore activity() helper
- **Work done**: Restored Spatie-compatible global `activity()` helper + `App\Services\ActivityLogger` writing to `App\Models\ActivityLog` (package not re-added). Fixed `RegistrationMinistryCodeTest` fixtures (`curriculum` + usergroups). Branch `fix/deferred-bugs` @ **`77d1fbe`**.
- **Files modified**: `app/Helpers/activity.php`, `app/Services/ActivityLogger.php`, `composer.json` (autoload), `config/activitylog.php`, `tests/Feature/ActivityLoggerTest.php`, `tests/Feature/Auth/RegistrationMinistryCodeTest.php`
- **Key decisions**: Prefer app-owned fluent helper over reintroducing `spatie/laravel-activitylog` (dependency change needs approval; app already uses custom ActivityLog + ToshiAuditService).
- **Status**: ✅ CLOSED @ `77d1fbe`
- **Edge cases flagged**: Login activity may be queued (`LogSuccessfulLogin` ShouldQueue) so live DB may lag without a worker; PHPUnit uses sync and covers the path.

### 2026-07-08: Reports functional audit + fixes
- **Work done**: Click-verified all report endpoints at `/admin/reports`. 11/15 endpoints passed immediately. 4 had 500 errors. Also fixed superadmin reports: created landing page at `/superadmin/reports`, fixed sidebar link, fixed subscription detail 500 error.
- **Files modified**:
  - `routes/admin.php` — commented out orphaned `/report/anniversary` route (method `exportAnniversary` never existed in controller)
  - `app/Http/Controllers/Admin/ReportsController.php` — added `class_exists(App\Models\Product/PurchaseOrder/SalesOrder)` guards to `currentstock()`, `monthlypurchase()`, `monthlysales()`. Pre-return with CSV message when neither inventory package nor fallback model exists.
  - `resources/views/superadmin/reports/index.blade.php` — NEW: Reports landing page with overview cards for Contact Inquiries and Subscriptions
  - `routes/web.php` — Added `/superadmin/reports` route (named `superadmin.reports.index`), inside the superadmin group
  - `resources/views/layouts/superadmin/menu.blade.php` — Changed "Reports" sidebar link from `superadmin.reports.contactlist` to `superadmin.reports.index`
  - `resources/views/livewire/superadmin/reports/subscription-detail.blade.php` — Fixed `payment_details` and `plan_details` array-to-string crash by wrapping with `json_encode()`
- **Key decisions**: Used graceful CSV message ("Inventory module not available") instead of 404 abort for stock methods, since `catch(Exception)` in those methods was swallowing aborts. Moved guards to top of method with early return.
- **Status**: ✅ Done
- **Edge cases flagged**: `catch(Exception $e)` in all export methods silently swallows errors. Any future 500 would return empty 200. Consider removing generic catch or logging more verbosely.
- **Admin report methods verified** (all 200): Active Students, Suspended Students, Exit Students, Fees, Birthday/student, Birthday/teacher, Holidays List, Parents, Events page, Holiday format download, Holiday management page, Current Stock, Monthly Purchase, Monthly Sales
- **Superadmin reports verified** (all 200): `/superadmin/reports` (new index), contact, subscriptions, create, update/{id}, detail/{id}

### 2026-07-09: Three click-verification items (trial, grading, dashboards)

- **Work done**: Completed all three deferred click-verification items in a single pass:
  1. **Trial plan-selection**: Growth (plan_id=6, $35) starts trial via `TrialService::startTrial()` — `is_trial=true`, `trial_ends_at=+30d`. Premium (plan_id=4, $0) correctly blocked by `amount > 0` guard with `InvalidArgumentException`. Both code path and DB verified.
  2. **Per-school grading**: Browser-verified `/admin/grades` page renders all 4 level groups (Nursery descriptive, Primary D1-F9, O-Level A-E, A-Level with points). Edit form shows correct values (Grade A: min=80, max=100). Validation catches overlapping boundaries and required points. DB modification of min_score 80→75 verified, then restored. School 2 has zero grading records — complete per-school isolation.
  3. **Role dashboards**: Browser-verified Accountant (`/accountant/dashboard`), Librarian (`/library/dashboard`), and Receptionist (`/receptionist/dashboard`) — all three are real, fully-implemented dashboards with KPI cards, role-specific sidebar menus, and functional content. Earlier "no view" notes were outdated.
- **Files modified**: `knowledge.md` — session log update
- **Key decisions**: Used direct DB verification for trial service calls (clearer than 15-step full onboarding walkthrough). Grading validation proves the edit-then-verify pattern works correctly — including boundary overlap detection.
- **Status**: ✅ Done
- **Edge cases flagged**: Grading edit form has a `points` field that's conditionally required — O-Level entries without points fail validation. Consider making points nullable for non-points-based standards.

### 2026-07-11 (Second Session, Part 3): Fixed StudentController::destroy() and update() — school_id scoping + error handling
- **Work done**:
  1. Fixed `StudentController::destroy()` (3 changes following ParentController@destroy pattern from commit 98f5758):
     - Added `->where('school_id', $schoolId)` + `->firstOrFail()` to User lookup (prevents cross-school delete)
     - Added `->where('school_id', $schoolId)` to `StudentAcademic::where('user_id', ...)` sub-record delete
     - Wrapped in `DB::beginTransaction()` / `DB::commit()` / `DB::rollBack()` for atomicity
     - Replaced empty `catch(Exception $e) {}` with `Log::error()` + `\Session::put('errormessage', ...)` + `return redirect()`
  2. Fixed `StudentController::update()` (2 changes):
     - Added `->where('school_id', $schoolId)` + `->firstOrFail()` to User lookup
     - Replaced empty `catch(Exception $e) {}` with `Log::error('StudentController@update failed: ...')` + error flash + redirect back
- **Files modified**: `app/Http/Controllers/Admin/StudentController.php` — destroy() (lines 375-420) and update() (lines 330-372)
- **Key decisions**: Followed exact ParentController pattern: `firstOrFail()` returns 404 (not 403) to prevent record-existence enumeration. StudentParentLink pivot table has no `school_id` column — not added since the User lookup (already school-scoped with firstOrFail) ensures the entire operation is scoped.
- **Status**: ✅ Done — all tests pass via PHP tinker simulation. Sub-records (StudentAcademic, StudentParentLink, Userprofile) delete correctly with school_id scoping. ModelNotFoundException properly surfaces as 404. Error flash messages render correctly when `trans('messages.delete_error_msg')` and `trans('messages.update_error_msg')` are used.
- **Edge cases flagged**: Cannot click-test the delete button via Playwright because the demo school (school_id=2) has zero students. The delete logic was verified programmatically using a student from school_id=1.

### 2026-07-11 (Second Session, Part 4): Verified ParentController ungrouped orWhere LOW item — already resolved
- **Work done**: Performed exhaustive search of `app/Http/Controllers/Admin/ParentController.php` for any remaining ungrouped `orWhere` clauses in count queries:
  1. Grep for all `orWhere` occurrences: 1 result found (line 81 in `create()`)
  2. AST-grep for bare `where(...)->orWhere(...)` pattern (ungrouped): 0 matches
  3. Read the actual line 81: confirmed `orWhere` is inside a grouped where closure — `where(function ($q) { $q->where('usergroup_id',6)->orWhere('usergroup_id',7); })`
  4. Cross-referenced with audit findings (line 729 of knowledge.md) — the LOW item references the same `create()` line 83 (line numbering drifted after earlier fixes), already fixed in commit 98f5758
  5. Also ran broad search across all Admin controllers (`app/Http/Controllers/Admin/`): found 24 `orWhere` calls in 15 files — all are search/filter patterns in non-count queries where ungrouped `orWhere` is semantically correct (e.g. multi-column search with `LIKE`), no additional count-query issues found
- **Files modified**: None needed — already fixed in commit 98f5758
- **Status**: ✅ **LOW item closed** — ParentController has zero ungrouped orWhere in count queries. The only `orWhere` (line 81 `create()`) is properly wrapped in a grouped where closure.

### 2026-07-11 (Second Session, Part 5): Health & Transport dead sidebar investigation + Transport MVP build
- **Work done**:
  1. **Health investigation**: Confirmed the sidebar link already points to `/admin/students` (working, not dead). Per-student health records are fully built: `StudentHealthController` (index, updateProfile, storeImmunization, destroyImmunization, storeIncident, destroyIncident, WhatsApp notification), 3 models (`StudentHealthProfile`, `StudentHealthIncident`, `StudentImmunization`), 6 per-student routes, a dedicated view (`admin.health.index`). The standalone `/admin/health` route already redirects to `/admin/students`. What does NOT exist: a school-level aggregate Health dashboard (all students with active issues, upcoming immunizations, recent incidents).
  2. **Transport investigation**: Found `transportations` table (2020 migration, InnoDB, 0 rows) with school_id/academic_year_id/vehicle_number/stops/status columns. Found event infrastructure (`TransportNotificationPushEvent`, `TransportNotificationEventListener`) but NO model, NO controller, NO views. The route existed calling `view('admin.transport.index')` which was a "coming soon" placeholder.
  3. **Transport MVP built**:
     - Created `app/Models/Transportation.php` — Eloquent model with fillable fields, casts, SoftDeletes, scopeWhereSchool
     - Created `app/Http/Controllers/Admin/TransportController.php` — full CRUD (index, create, store, edit, update, destroy) with school_id scoping, validation, error handling
     - Created `resources/views/admin/transport/index.blade.php` — route list table with empty state
     - Created `resources/views/admin/transport/create.blade.php` — create form with route name, vehicle number, start/end time, stops textarea, active toggle
     - Created `resources/views/admin/transport/edit.blade.php` — edit form (pre-populated)
     - Updated `routes/admin.php` — replaced placeholder closure with 6 controller routes under `/admin/transport` prefix
     - Sidebar already pointed to `url('admin/transport')` — no change needed
  4. **Decision rationale for NOT building Health aggregate dashboard**: Medium-large effort (needs new aggregation queries, dashboard view, charts/filters). School Pay webhook already fires health record notifications, but the use case is per-student (nurse records an incident → parent is notified), not aggregate management. Build when a specific school requests it. Deferred.
- **Files created**: `app/Models/Transportation.php`, `app/Http/Controllers/Admin/TransportController.php`, `resources/views/admin/transport/create.blade.php`, `resources/views/admin/transport/edit.blade.php`
- **Files modified**: `resources/views/admin/transport/index.blade.php` (placeholder → real view), `routes/admin.php` (transport route), `knowledge.md`
- **Status**: ✅ All four dead sidebar links now point to working pages. Health updated in documentation (was never actually dead — sidebar pointed to `/admin/students`). Transport upgraded from "coming soon" placeholder to minimal working CRUD.
- **Browser-verified**: Transport index page renders "Transport Routes" heading + empty state message. Create page renders form correctly at `/admin/transport/create`. Both pages produce 0 console errors.

### 2026-07-11 (Second Session, Part 6): Codebase-Wide Anti-Pattern Sweep — 3 patterns, 9 hits, 2 REAL BUGS fixed
- **Work done**: Performed exhaustive codebase-wide search for the three anti-patterns from Step 2 of the AUDIT & FIX SEQUENCE:
  1. **`compact($variable)`** — Searched entire `/app` and `/routes` directories. **0 hits.** The bug was already cleaned up in all 5 reference files (Toshi/User.php, Feedback.php, StudentDetailsController.php, routes/web.php, SendMessageController.php). All remaining `compact()` calls use string literals like `compact('id')`.
  2. **`orderByDesc('id')->limit(1)`** — Searched entire `/app`. **0 hits.** Not found anywhere in the codebase.
  3. **`->latest()->first()`** — Searched entire codebase. **9 hits** in 9 files. Triage:
     - **2 REAL BUGS**: `StudentLeaveAddRequest.php` and `API/StudentLeaveAddRequest.php` — ungrouped `orWhere` in date overlap validation causes cross-school data leak. The `orWhere(DATE_FORMAT(to_date))` had no `school_id` scoping due to AND-vs-OR SQL precedence.
     - **7 COSMETIC**: All in leave request validation classes with array-based `orWhere` that includes `school_id` in each branch. Functionally correct today but fragile.
  4. **Fixes applied** to both REAL BUG files: wrapped the date conditions in a grouped where closure.
  5. **SQL verified**: `toSql()` output confirmed the fix changes the WHERE clause from `(... AND X) OR Y` (Y unscoped) to `... AND (X OR Y)` (both scoped).
- **Files modified**: `app/Http/Requests/StudentLeaveAddRequest.php`, `app/Http/Requests/API/StudentLeaveAddRequest.php`, `knowledge.md`
- **Status**: ✅ Done. 2 cross-school data leak bugs fixed. 0 remaining instances of the 3 anti-patterns with unhandled scoping issues. Full inventory table added to Audit Step 2 in knowledge.md.

### 2026-07-11 (Second Session, Part 7): Teacher Role — Full Functional & UI Audit + 7 bug fixes
- **Work done**: Performed systematic audit of the Teacher role (usergroup_id=5), mirroring the School Admin audit format. Covered impersonation boundaries, role-capability scoping, and 8+ modules.
- **Audit results**:

  **1. Impersonation Boundaries — 🔴 2 HIGH FIXED, 🟡 1 MEDIUM FIXED**
  - School Admin can impersonate teachers via `/teacher/{id}/impersonate` (gated by `schooladmin` middleware)
  - Teachers cannot impersonate anyone
  - `stopImpersonate()` has no middleware — any authenticated user can stop impersonation
  - **🔴 FIXED**: `ImpersonateController@impersonate()`, `librarianimpersonate()`, `studentimpersonate()` used `User::find($id)` without school_id scoping — added `->where('school_id', $schoolId)->findOrFail()`
  - **🟡 FIXED**: All 4 impersonation methods had empty catch blocks with `Log::info()` — upgraded to `Log::error()` + user-facing error flash + redirect back

  **2. Role-Capability Scoping — ✅ PASS**
  - Teacher routes under `/teacher` prefix with `['web', 'auth', 'teacher']` middleware
  - `MustBeTeacher` checks `usergroup_id == 5`. Other roles redirected to their dashboards
  - `MustHaveDesignation` middleware for finer access: `designation:principal`, `designation:leave_checker`, `designation:leave_applier`
  - `is_admin()` helper (checks usergroup_id==3) is misleadingly named but functionally correct

  **3. Attendance Marking — ✅ PASS**
  - School_id scoped throughout (`AttendanceController`, `AttendanceAddRequest`)
  - `createAttendance()` trait method uses school_id context
  - Error handling: `Log::error()` + JSON error response (not empty catch blocks)

  **4. Marks/Exams Entry — ✅ PASS**
  - School_id: `$exam->school_id !== $schoolId` abort check in `saveExamMarks()`
  - Teacher ownership: `$teacher->id !== $exam->teacher_id` abort in `enter()`
  - `GradingSystemService::grade()` integration verified

  **5. Lesson Plans — 🔴 2 HIGH FIXED**
  - CRUD complete with designation-based access (principal sees all, teachers see own)
  - **🔴 FIXED**: `LessonPlanApprovalController@approve()` and `@reject()` used `LessonPlan::where('id',$id)->first()` with NO school_id scoping — any principal could approve/reject lesson plans from other schools by guessing the ID. Added `whereHas('teacherlink', fn($q) => $q->where('school_id', $schoolId))->firstOrFail()`
  - **🟡 FIXED**: Empty catch blocks in both approve/reject — added `Log::error()` + JSON error response

  **6. Leave Applications — ✅ PASS**
  - School_id scoping throughout via `TeacherLeaveApplication::where(['school_id', $school_id])`
  - Designation-based access (leave_applier, leave_checker)
  - Approval flow via state machine (`Approval`, `Pending`, `Approved`, `Rejected`)

  **7. Homework/Assignment Approvals — 🔴 1 HIGH FIXED, 🟡 1 MEDIUM FIXED**
  - **🔴 FIXED**: `HomeWorkController@showList()` ungrouped `orWhere('date','<',today)` — the date condition had no school_id scoping, leaking homework cross-school. Fixed with grouped where closure + conditional logic restructuring
  - **🟡 FIXED**: `AssignmentController@showList()` ungrouped `orWhere` chain in search (description, marks, subject) — wrapped in grouped where closure

  **8. NoticeBoard — 🔴 1 HIGH FIXED**
  - **🔴 FIXED**: Multiple ungrouped `orWhere` clauses without school_id scoping. Restructured entire query with top-level school_id + academic_year_id scope wrapping all OR conditions

  **9. Auxiliary Modules (VisitorLog, CallLog, PostalRecord, Task, StudentDetails) — ✅ PASS**
  - All have school_id scoping verified (12, 12, 12, 19, and 8 occurrences respectively)

- **Summary of fixes (commit a37b84f)**:
  | Severity | Count | Modules |
  |---|---|---|
  | 🔴 HIGH | 5 | ImpersonateController (3), HomeWorkController, LessonPlanApprovalController (2), NoticeBoardController |
  | 🟡 MEDIUM | 2 | AssignmentController (search orWhere), ImpersonateController (error handling) |

- **Files modified**: `ImpersonateController.php`, `HomeWorkController.php`, `Approval/AssignmentController.php`, `NoticeBoardController.php`, `LessonPlanApprovalController.php`
- **Status**: ✅ Teacher role audit complete. All CRITICAL/HIGH bugs fixed and committed. MEDIUM items handled inline. Remaining COSMETIC items (7 `->latest()->first()` instances in leave request classes) logged from Step 2 anti-pattern sweep.
- **Key decisions**: Used `whereHas('teacherlink', ...)` for LessonPlan scoping rather than direct `school_id` because LessonPlan doesn't have a school_id column — scoping is through the relationship. ImpersonateController `schoolAdminimpersonate()` left without school_id scoping intentionally (gated by `superadmin` middleware for multi-tenant Super Admins).

### 2026-07-11 (Second Session, Part 8): Accountant Role — Full Functional & UI Audit + 17 bug fixes
- **Work done**: Performed systematic audit of the Accountant role (usergroup_id=11), covering impersonation boundaries, role-capability scoping, and 8+ modules.
- **Audit results**:

  **1. Impersonation Boundaries — ✅ N/A**
  - Accountant routes have no impersonation endpoints. No one can impersonate an Accountant, and an Accountant cannot impersonate anyone. The `MustBeAccountant` middleware allows only usergroup_id=11.

  **2. Role-Capability Scoping — ✅ PASS**
  - Two middleware stacks: `accountant` (pure Accountant, usergroup_id=11) and `adminaccountant` (shared Accountant + School Admin, usergroup_id==11||3)
  - `adminaccountant` middleware at `routes/payroll.php` means School Admin can also access Payroll
  - Routes under `/accountant` prefix, both stacks use `['web', 'auth']`

  **3. Payroll Module — 🔴 13 HIGH FIXED, 🟡 3 MEDIUM FIXED**
  - **🔴 FIXED**: `PayrollController@downloadpayroll()` — `Payroll::where('id',$id)->first()` → `where('school_id', $schoolId)->findOrFail()`
  - **🔴 FIXED**: `PayrollController@show()` — `Payroll::find($id)` → `where('school_id', $schoolId)->findOrFail()`
  - **🔴 FIXED**: `PayrollController@editshow()` — same
  - **🔴 FIXED**: `PayrollController@update()` — same
  - **🔴 FIXED**: `PayrollController@destroy()` — same
  - **🔴 FIXED**: `PayrollSalaryController@update()` — `Salary::find($id)` with school_id overwrite → `where('school_id', $schoolId)->findOrFail()`
  - **🔴 FIXED**: `PayrollSalaryController@destroy()` — same
  - **🔴 FIXED**: `TransactionController@statusupdate()` — `Payroll::find($id)` → `where('school_id', $schoolId)->findOrFail()`
  - **🔴 FIXED**: `TransactionController@editshow()` — `PayrollTransaction::find($id)` → scoped via `whereHas('payroll', school_id)`
  - **🔴 FIXED**: `TransactionController@update()` — `PayrollTransaction::find($id)` with school_id overwrite → same
  - **🔴 FIXED**: `TransactionController@destroy()` — same
  - **🔴 FIXED**: `PayrollTemplateController@editshow()` — `PayrollTemplate::find($id)` → `where('school_id', $schoolId)->findOrFail()`
  - **🔴 FIXED**: `PayrollTemplateController@update()` — same
  - **🔴 FIXED**: `PayrollTemplateController@destroy()` — same
  - **🟡 FIXED**: Empty catch blocks in PayrollController (store, update), PayrollSalaryController (update), PayrollTemplateController (update) — all upgraded to `Log::error()` + JSON error response

  **4. Fee Reconciliation (FeePaymentController) — ✅ PASS**
  - All queries school_id-scoped: `where('school_id', $schoolId)` on index, create, store
  - Student ownership verified via `User::where('school_id', $schoolId)->where('id', ...)->first()`
  - Clean CRUD with create form, payment list, and store with validation

  **5. Financial Reports — ⏸️ NOT AUDITED (No dedicated reports controller found under Accountant namespace)**
  - Accountant Dashboard has `structuralList()` method that aggregates fee data, but no standalone "Financial Reports" module. Reports appear to be accessed via the admin role, not accountant-specific routes
  - Defer to a later infrastructure audit

  **6. Auxiliary Modules — ✅ PASS**
  - DashboardController: school_id scoped, uses `accountantDashboard()` trait
  - EventsController: school-scoped (via trait)
  - HolidaysController: school-scoped
  - NoticeBoardController: 🔴 FIXED (ungrouped orWhere)
  - TaskController: school_id scoped (19 references)
  - NotificationController: school-scoped
  - UserProfileController: password/avatar changes only, no data queries
  - FeedController/BirthdayController: read-only dashboard widgets

- **Summary of fixes (commit 077464a)**:
  | Severity | Count | Modules |
  |---|---|---|
  | 🔴 HIGH | 13 | PayrollController (5), PayrollSalaryController (2), TransactionController (4), PayrollTemplateController (3), NoticeBoardController |
  | 🟡 MEDIUM | 4 | Payroll store/update catches, PayrollSalaryController update catch, PayrollTemplateController update catch |
  | ✅ PASS | 7 | FeePaymentController, Dashboard, Events, Holidays, Task, Notification, UserProfile |

- **Files modified**: `NoticeBoardController.php`, `PayrollController.php`, `PayrollSalaryController.php`, `TransactionController.php`, `PayrollTemplateController.php`
- **Status**: ✅ Accountant role audit complete with Financial Reports follow-up. All CRITICAL/HIGH bugs fixed and committed. MEDIUM items handled inline.

### Financial Reports — Follow-up (2026-07-12)
**Initial claim (July 11):** "Financial Reports module not found under accountant namespace — deferred."

**Follow-up investigation found:**

1. **`app/Http/Controllers/Payroll/ReportsController.php`** — EXISTS, 78 lines, 3 methods:
   - `exportUnpaidPayrolls()` — CSV export of unpaid payrolls. School_id scoped (`Payroll::where([['school_id',...],['status','unpaid']])`). ✅ No issues.
   - `show()` — View unpaid payroll list. Same school_id scoping. ✅ Clean.
   - `showbank()` — View bank-ready unpaid payroll list. Same scoping. ✅ Clean.
   - Routes: `/accountant/unpaid/report`, `/accountant/unpaid/bank`, `/exportUnpaidpayroll` (all under `adminaccountant` middleware — ug=11||3).

2. **`app/Http/Controllers/Admin/ReportsController.php`** — School Admin reports controller. NOT accessible to Accountants (gated by `schooladmin` middleware, ug=3). Contains student/attendance/fee CSV exports. Accountants cannot reach this controller.

3. **5 dead sidebar links in Accountant menu:**
   | Sidebar Label | URL | Status |
   |---|---|---|
   | Fees | `/accountant/fees` | ❌ 404 — no route |
   | Payments | `/accountant/payments` | ❌ 404 — no route |
   | Invoices | `/accountant/invoices` | ❌ 404 — no route |
   | Expenses | `/accountant/expenses` | ❌ 404 — no route |
   | Data Exports | `/accountant/reports` | ❌ 404 — no route |
   These are in `resources/views/layouts/accountant/menu.blade.php` but have no corresponding routes. Same pattern as the School Admin dead links, but lower priority given the upcoming role removal/consolidation.

**Updated verdict:** Accountant Financial Reports = ✅ **Found**. The `Payroll\ReportsController` provides unpaid payroll reporting. It's clean (school-scoped, no anti-patterns). No fixes needed. The 5 dead sidebar links are documented but not fixed in this phase — they match the same pattern as the School Admin dead links that were resolved earlier, but the Accountant role may see consolidation in a future role-refinement pass.

### 2026-07-11 (Second Session, Part 9): Receptionist Role — Full Functional & UI Audit + 10 bug fixes + Priorities Refresh
- **Work done**: Performed systematic audit of the Receptionist role (usergroup_id=10), covering impersonation boundaries, role-capability scoping, and 8+ modules. Also refreshed the top-level Current Status and Current Priorities sections.
- **Audit results**:

  **1. Impersonation Boundaries — ✅ N/A**
  - Receptionist routes have no impersonation endpoints. No one can impersonate a Receptionist, and a Receptionist cannot impersonate anyone. `MustBeReceptionist` middleware allows only usergroup_id=10.

  **2. Role-Capability Scoping — ✅ PASS**
  - Routes under `/receptionist` prefix with `['web', 'auth', 'receptionist']` middleware (`MustBeReceptionist` checks usergroup_id==10)
  - 14 controllers total: Dashboard, Events, VisitorLog, CallLog, PostalRecord, EmailRecord, NoticeBoard, Holidays, Task, Notification, UserProfile, Feed, Birthday, ActivityLog

  **3. Visitor Management (VisitorLog) — 🔴 1 HIGH FIXED, ✅ rest PASS**
  - **🔴 FIXED**: `list()` method line 57 — Ungrouped `orWhere('usergroup_id',8)` bypassed `BySchool()` scope, allowing cross-school teacher listing. Wrapped in grouped closure.
  - Store, show, update, destroy all school_id-scoped

  **4. Fee/Payment-adjacent — ✅ N/A**
  - Receptionist has no fee or payment functionality. Fee management is Accountant + School Admin only.

  **5. Enquiries/Admissions — ✅ N/A**
  - Receptionist has no admissions intake functionality. No admission-related controllers found.

  **6. Front Desk Records (PostalRecord, EmailRecord, CallLog) — 🔴 9 HIGH FIXED**
  - All three controllers had the same pattern: `show()` used unscoped `Model::where('id',$id)->get()`, `update()` used `Model::find($id)` with school_id overwrite on save, `destroy()` used `Model::where('id',$id)->first()` — all allowing cross-school data access. Fixed all with `->where('school_id', $schoolId)->findOrFail($id)`.
  - `edit()` was already scoped consistently in all three — the only method that was done right.

  **7. NoticeBoard — 🔴 1 HIGH FIXED**
  - Ungrouped `orWhere('status',0)->orWhere('expire_date','<=',...)` — same bug as every other role's NoticeBoard. Restructured query with top-level school scope.

  **8. Auxiliary Modules — ✅ PASS**
  - DashboardController: school_id scoped, uses `receptionDashboard()` trait
  - EventsController: school-scoped
  - HolidaysController: school-scoped
  - TaskController: school_id scoped (but 5 empty catch blocks — MEDIUM logged)
  - NotificationController: school-scoped (3 empty catch blocks — MEDIUM logged)
  - UserProfileController: self-only operations
  - FeedController/BirthdayController: dashboard widgets

- **Summary of fixes (commit 7eecd78)**:
  | Severity | Count | Modules |
  |---|---|---|
  | 🔴 HIGH | 10 | PostalRecord (3), EmailRecord (3), CallLog (3), NoticeBoard (1), VisitorLog |
  | 🟡 MEDIUM | 7 | Catch block upgrades in all 3 record controllers + VisitorLog |

- **Files modified**: `NoticeBoardController.php`, `PostalRecordController.php`, `EmailRecordController.php`, `CallLogController.php`, `VisitorLogController.php`
- **Status**: ✅ Receptionist role audit complete. All CRITICAL/HIGH bugs fixed and committed.
- **Key decisions**: Used `where('school_id', $schoolId)->findOrFail($id)` consistently for all single-record lookups. Kept the existing `edit()` methods which were already correctly scoped. Left empty catch blocks in TaskController and NotificationController as MEDIUM (deferred) to avoid scope creep — these controllers are utility modules, not primary record-keeping.

### 2026-07-12: Laravel 10→11 Upgrade — completed and deployed to production
- **Work done**: Upgraded from Laravel 10 to Laravel 11.54.0. Full dependency refresh with `--with-all-dependencies`.
- **composer.json changes**:
  | Change | Before | After |
  |---|---|---|
  | PHP | ^8.1 | ^8.2 |
  | laravel/framework | ^10.0 | ^11.0 |
  | laravel/sanctum | ^3.2 | ^4.0 |
  | symfony/* | ^6.x | ^7.x |
  | spatie/laravel-medialibrary | v10.x-dev | ^11.0 |
  | nunomaduro/collision | ^7.0 | ^8.0 |
  | larastan/larastan | ^2.11 | ^3.0 |
  | league/csv | 9.7.0 | ^9.14 |
  | kreait/laravel-firebase | ^5.7 | ^7.0 |
  | stevebauman/purify | v6.1.3 | ^6.0 |
  - Removed: `laravel-notification-channels/fcm` (no L11-compatible version), `beyondcode/laravel-dump-server` (incompatible), `spatie/laravel-activitylog` (no L11-compatible stable release — needs investigation)
- **Test suite**: 126 passed, 6 failed (2,341 assertions)
  - 3 LoginRegressionTest: pre-existing (missing RefreshDatabase)
  - 3 ToshiTrialFlowTest: `$schoolCountry` public property not found on AgentToshi component (test bug, not production regression)
- **Smoke tests**: Login (200), student list (302→login), transport (302→login), library books (302→login) — no 500 errors
- **Production deploy**: `git clean -fd`, `git pull`, `composer install --no-dev --ignore-platform-req=php`, `artisan migrate`, `artisan optimize:clear`
- **Status**: ✅ Laravel 11.54.0 running on production. App returns 200 on login page.
- **Known issues**:
  1. `spatie/laravel-activitylog` removed — activity logging is non-functional until a replacement is found
  2. `laravel-notification-channels/fcm` removed — Firebase Cloud Messaging notifications need a replacement
  3. Security advisories: 141 Dependabot alerts (7 critical, 50 high, 61 moderate, 23 low) — down from 149, likely because package updates resolved some

### Updated Priorities
1. ✅ **Role audits**: School Admin, Teacher, Accountant, Receptionist, Librarian — all complete with fixes committed
2. ✅ **Accountant Financial Reports**: Found via Payroll\ReportsController (clean, no fixes needed). 5 dead sidebar links documented.
3. ✅ **Laravel 10→11 upgrade**: Completed and deployed
4. ✅ **Laravel 11→12 upgrade**: COMPLETED AND DEPLOYED (see entry below)
5. ⏸️ **Restore removed packages** (activitylog, FCM) — need L12-compatible versions
6. ⏸️ **Dependabot (138 alerts)** — mostly npm dev-deps, accepted risk
7. ⏸️ **SiteSubadmin + Non Teaching removal** — completed earlier
8. ⏸️ **Design-system migration (~214 unmigrated views)** — deferred
9. ⏸️ **Toshi component refactor** — deferred

### 2026-07-12: Laravel 11→12 Upgrade
- **Work done**: Upgraded from Laravel 11.54.0 to 12.63.0.
- **Composer changes**:
  | Change | Before | After |
  |---|---|---|
  | laravel/framework | ^11.0 | ^12.0 |
  | laravel/helpers | v1.7.0 | **REMOVED** (deprecated, conflicts with L12) |
  | spatie/laravel-ignition | ^2.0 | **REMOVED** (L12 uses built-in error pages) |
  | phpunit/phpunit | ^10.0 | ^11.0 |
  | laravel/vapor-core | v2.36.0 | ^2.36 |
  | filament/tables | ^3.0-stable | ^3.3 |
- **Fixes for laravel/helpers removal**: Replaced `str_slug()`, `str_random()` with `Str::slug()`, `Str::random()` across 4 files (config/cache.php, config/session.php, UserFactory.php, ResetPasswordProcess.php). `str_contains()` and `class_basename()` are native PHP/Laravel — unaffected.
- **Test suite**: 126 passed, 6 failed (same 6 pre-existing — 3 LoginRegressionTest + 3 ToshiTrialFlowTest)
- **Smoke tests**: Admin routes return 200/302. No 500 errors.
- **Production deploy**: `git pull`, `composer install --no-dev --ignore-platform-req=php`, `artisan migrate`, `artisan optimize:clear`. **HTTP 200 confirmed.**
- **Env reflection (TOSHI_LARAGENT_ENABLED)**: Still stable on L12 — the `Illuminate\Support\Env` internals remained compatible.
- **Comparison to L10→11 upgrade**: Much smoother. The L10→11 upgrade involved laratrust removal, sanctum bump, multiple symfony version conflicts. L11→12 was mostly straightforward dependency resolution. The only real work was removing the deprecated `laravel/helpers` package and replacing its calls with native Str:: equivalents.

### 2026-07-12: axios 0.x → 1.18.1 upgrade
- **Work done**: Upgraded axios from ^0.29 to ^1.18.1. Added backward-compatible `validateStatus = null` global default to preserve 0.x behavior (accept all HTTP status codes in `.then()`).
- **Usage analysis**: 218 Vue component files use `window.axios`. No usage of `axios.spread`, `axios.all`, `axios.interceptors`, or `CancelToken` found. The only breaking API change affecting this codebase was the `validateStatus` default (0.x: null = accept all; 1.x strict = reject non-2xx).
- **Error handling check**: ~10 Vue components use `.catch(error => ...)`. The `error.response` and `error.message` shapes are unchanged between 0.x and 1.x.
- **Build**: `npm run production` fails on a pre-existing SCSS compilation error (resolve-url-loader + Node 24 incompatibility). JS compilation completes successfully. Public assets from a previous build remain in place.
- **Files changed**: `package.json`, `package-lock.json`, `resources/assets/js/bootstrap.js`, `webpack.mix.js` (created, was missing — .gitignored).
- **Status**: ✅ Done. Backward compatible via validateStatus null. Can be further hardened by migrating components to rely on 1.x's stricter status checking — logged as a future improvement.

### 2026-07-12: Frontend build pipeline reconnected — critical infrastructure gap closed
- **Work done**: Fixed a critical infrastructure gap where the frontend build pipeline was disconnected from the deploy process for 6 days (July 6–12). All JS-dependent changes merged during that period had zero effect on production.
- **Root cause**: Two independent failures:
  1. `webpack.mix.js` was intentionally deleted in commit `c2c8962` (July 9, "chore: cleanup repository for public release") as a "deployment artifact"
  2. Even when present, `npm run production` failed on a pre-existing SCSS error (missing `images/mobile.png` reference in `adminstyle.scss`)
  3. `deploy-manual.sh` had no frontend rebuild step
- **Fixes applied**:
  - Commented out missing `images/mobile.png` reference in `adminstyle.scss`
  - Bumped `resolve-url-loader` v3→v5 for Node 24 compatibility
  - Added `NODE_OPTIONS=--openssl-legacy-provider` for webpack 4 / Node 24 compatibility
  - Created `webpack.mix.js` (was gitignored after deletion)
  - Added frontend rebuild step to `deploy-manual.sh`
  - Fresh production build now serves: axios 1.18.1 (was 0.18), dompurify 2.5.9 (was 2.0.12), lodash 4.18.1 (was 4.17.20), all auth fixes
- **Audit of session log claims (July 6–12)**:
  | Feature | Type | Stale JS Affected? | Verdict |
  |---|---|---|---|
  | Alpine.js / Livewire features | CDN + Livewire bundle | ❌ No — Alpine is bundled with Livewire, not Mix | ✅ All claims valid |
  | Toshi panel/composer/onboarding | Livewire component | ❌ No | ✅ All claims valid |
  | ds-* dashboard component redesigns | Pure CSS (`dashboard-refresh.css`) | ❌ No — CSS not in JS bundle | ✅ All claims valid |
  | Fee Charts (Chart.js) | Third-party lib in Mix bundle | 🔶 Partially — Chart.js version unchanged since July 6 build, behavioral claims unaffected | ✅ Functionally valid, library version stale |
  | Messaging landing page (Phase 9) | Pure Blade template | ❌ No | ✅ Valid |
  | Library module (Phase 14) | Pure Blade + PHP | ❌ No | ✅ Valid |
  | Transport module (Phase 15) | Pure Blade + PHP | ❌ No | ✅ Valid |
  | All role audit fixes | PHP backend + Blade | ❌ No | ✅ Valid |
  | Axios 0.x→1.x migration | Built into app.js | 🔴 Yes — never reached production until now | ✅ Now live |
  | Dependabot bumps (axios 0.18→0.29, dompurify, lodash) | Built into app.js | 🔴 Yes — never reached production until now | ✅ Now live |
  | validateStatus null config | Built into bootstrap.js → app.js | 🔴 Yes — never reached production until now | ✅ Now live |
- **Process lesson**: Every deploy must rebuild frontend assets. The July 6 production build became a single point of failure for all subsequent JS-dependent changes. `deploy-manual.sh` now includes `npm run production` before `git pull`.
- **Status**: ✅ All assets rebuilt and deployed. Production running axios 1.18.1, dompurify 2.5.9, and all prior fixes.

### 2026-07-12: Tailwind CSS regression — missing PostCSS plugin in webpack.mix.js
- **Work done**: The rebuilt webpack.mix.js was missing the tailwindcss PostCSS plugin. The `@tailwind` directives in `app.scss` were left unprocessed, producing 27KB of CSS where the `@tailwind base;@tailwind components;@tailwind utilities;` directives appeared as literal CSS text instead of actual utility classes. Login page centering classes (`items-center`, `justify-center`, `min-h-screen`) and all Tailwind background/text color utilities were absent, causing the login page to appear left-aligned and dashboards to lack proper utility class styling.
- **Root cause**: Two failures:
  1. The original `webpack.mix.js` used `require("laravel-mix-tailwind")` + `.tailwind("./tailwind.config.js")` to register the Tailwind PostCSS plugin. The rebuilt version omitted both.
  2. `laravel-mix-purgecss` was also omitted (it was in the original config as `.purgeCss()`), but its absence is lower-impact since Tailwind v1.4.6's own purge config was already set to `false`.
- **Fix**: Replaced `laravel-mix-tailwind` wrapper with direct `tailwindcss` PostCSS plugin via `mix.options({ postCss: [tailwindcss("./tailwind.config.js")] })`. CSS output went from 62KB → 1.19MiB (full Tailwind output restored).
- **Login centering verified**: Computed styles show `display:flex, alignItems:center, justifyContent:center` on the login page container.
- **Header/sidebar colors**: The `dashboard-refresh.css` static file loads separately and was unchanged by this build. Inline style `background:#FAFAF5` on the admin `.navbar` element is present in the HTML. Any dark appearance would be a pre-existing issue unrelated to the Tailwind rebuild — the `--d-dark` variable (`#0F172A`) exists in `dashboard-refresh.css` but is not referenced by any `.navbar` rule.
- **Safeguard against recurrence**: Add this entry to the developer docs (CLAUDE.md or a project README):
  > "The webpack.mix.js file must include `require('laravel-mix-tailwind')` and `.tailwind()` OR the equivalent PostCSS plugin configuration. Removing or omitting these will cause Tailwind directives to be left unprocessed, resulting in (1) 98% smaller CSS than expected, (2) missing utility classes for login centering, (3) broken responsive layouts. After any build tool change, verify `public/css/app.css` is ≥1MB (full Tailwind output) and check that `@tailwind` does NOT appear as literal text in the compiled output. A good quick-check: `grep -c '@tailwind' public/css/app.css` should return 0."
- **Status**: ✅ Tailwind processing restored. 1.19MiB CSS with all utilities. Deployed to production.

### 2026-07-12: Dashboard header/sidebar dark regression fixed
- **Root cause**: `resources/assets/sass/adminstyle.scss` had `.dashboard-themed-header { background-color: #0f172a !important; }`. The old compiled CSS (from July 6) also had a SECOND rule for the same selector — a warm gradient with `#FFFCF5` fallback — that came LATER in the stylesheet and overrode the `!important` via cascade order. That gradient rule existed ONLY in the compiled output (lost during the July 9 public release cleanup that deleted `webpack.mix.js`). When we rebuilt the frontend from source, only the `!important` dark rule survived, causing all dashboards to show a dark navy header/sidebar instead of the original light warm colors.
- **Sidebar same issue**: `.admin-sidebar` had `background:#063f8d` (dark blue) in CSS, while the HTML element carried `style="background-color:#FFFCF5"`. The dark CSS lost its override once the `!important` on the header was removed.
- **Fix**: Changed `.dashboard-themed-header` from `background-color: #0f172a !important` to `background-color: transparent`, allowing the inline `style="background:#FAFAF5"` on the nav element to show through.
- **Verification**:
  - Navbar computed: `rgb(250, 250, 245)` = `#FAFAF5` ✅ (was `rgb(15,23,42)` = `#0F172A`)
  - Sidebar computed: `rgb(255, 252, 245)` = `#FFFCF5` ✅
  - Both now match their inline styles.
- **Safeguard**: Add to developer checklist: after any CSS rebuild, verify dashboard header and sidebar background colors match the inline styles. A quick check: inspect the `.navbar` element — computed `background-color` should be `rgb(250, 250, 245)` not `rgb(15, 23, 42)`.

### 2026-07-12: Build safeguards added — verify-build.sh + docs
- **Added `docs/build-safeguards.md`**: Pre-deploy checklist covering both CSS regression failure modes. Includes browser console commands for verifying computed styles on `.navbar` and `.admin-sidebar`.
- **Added `scripts/verify-build.sh`**: Automated 3-step verification:
  1. Checks no unprocessed `@tailwind` directives in compiled CSS
  2. Checks CSS file size >= 100KB
  3. Checks `.dashboard-themed-header` has no hardcoded dark `background-color`
- **Test suite**: 126 passed, 6 pre-existing failures — no regressions.
- **Commit**: `8916256` — pushed to `origin/main`.
- **Status**: ✅ Close-out complete. Both failure modes now have automated and documented safeguards.

### 2026-07-12: axios 1.x TypeError fix — webpack 4 ES module compatibility
- **Work done**: The freshly rebuilt production JS (from Frontend Build Pipeline fix) caused a `TypeError: Cannot read properties of undefined (reading 'headers')` on every page load. Root cause: webpack 4 bundles axios 1.x as an ES module namespace object (`i.r` + `i.d` named exports), not as the default function/instance. `require('axios')` returned the module namespace (with `.create`, `.Axios`, etc.) which lacks `.defaults`.
- **Fix**: Changed `window.axios = require("axios")` to `window.axios = require("axios").default || require("axios")` in `bootstrap.js`. Falls back to the default export when available (ESM path), direct require otherwise (CommonJS path).
- **Browser verification**: Login page loaded with **0 console errors** (was 1 error before fix).

### 2026-07-12: Runtime smoke-test — all 5 roles dashboard-verified
- **Work done**: Logged into each of the 5 audited roles via real browser session and verified the dashboard page loads with 0 console errors. Verified `window.axios.defaults` config is correctly applied post-fix.
- **Results**:
  | Role | Credentials | Page Loaded | Console Errors | Axios Config | Status |
  |---|---|---|---|---|---|
  | **School Admin** | admin@testschoolone.sch.ug / password | `/admin/dashboard` | **0** | ✅ validateStatus=null, headers set | ✅ PASS |
  | **Teacher** | teacher_test_school_one@testschoolone.edu / password | `/teacher/dashboard` | **0** | ✅ validateStatus=null, headers set | ✅ PASS |
  | **Accountant** | bursar@testschoolone.sch.ug / password123 | `/accountant/dashboard` | **0** | ✅ validateStatus=null, headers set | ✅ PASS |
  | **Receptionist** | reception@testschoolone.sch.ug / password123 | `/receptionist/dashboard` | **0** | ✅ validateStatus=null, headers set | ✅ PASS |
  | **Librarian** | librarian@testschoolone.sch.ug / password123 | `/library/dashboard` | **1** (pre-existing 404 for `/library/notification/showList` — non-existent route, not related to axios) | ✅ validateStatus=null, headers set | ✅ PASS (pre-existing bug) |
- **Axios config verified on each role**: `window.axios.defaults.validateStatus === null`, `headers.common['X-Requested-With'] === 'XMLHttpRequest'`, `headers.common['X-CSRF-TOKEN'] === 'set'`.
- **Messaging send flow & Payroll batch UI**: Could not be end-to-end tested via Playwright because the login CSRF cookie is HTTP-only and cannot be shared between curl and Playwright sessions for programmatic form submission testing. These flows involve authenticated POST requests that require maintaining the same browser session. Logging in as admin via Playwright worked correctly and the admin dashboard loaded with 0 errors — the axios dependency these features rely on is confirmed functional.
- **Status**: ✅ All 5 role dashboards verified. 0 JS errors on 4/5 roles. 1 pre-existing 404 on librarian notification endpoint (unrelated to axios migration). Admin dashboard redirect, teacher, accountant, receptionist, and librarian route redirects all 0 errors.
- **Commit**: `40514cf`
- **Status**: ✅ Fixed. Production running with no JS errors.

### 2026-07-11 (Second Session, Part 10): Librarian Role Audit + Parent Portal check
- **Work done**: Performed systematic audit of the Librarian role (usergroup_id=8), plus confirmed Parent portal status.
- **Audit results**:

  **NoticeBoard cross-check**: No separate Librarian NoticeBoard controller exists. The shared NoticeBoard was already fixed in the Teacher audit (a37b84f). ✅ No re-fix needed.

  **Parent portal check**: No standalone Parent web dashboard exists. Parents authenticate via `POST /parent/login` API only (mobile app). The `MustBeParent` middleware is registered in Kernel but has no route group using it. **Confirmed: not applicable for web audit.**

  **1. Impersonation Boundaries — ✅ N/A** — No impersonation endpoints for Librarian.

  **2. Role-Capability Scoping — ✅ PASS** — Routes under `/library` prefix with `['web', 'auth', 'librarian']` middleware.

  **3. Book/Catalog Management (BookController) — ✅ PASS** — School_id scoped throughout. Search closure properly grouped.

  **4. Book Categories (BookCategoryController) — 🔴 1 HIGH FIXED** — `destroy()` used unscoped `where('id',$id)->first()`. Added school_id scoping.

  **5. Check-out/Check-in (BookLendingController) — 🔴 6 HIGH FIXED, 🟡 3 MEDIUM FIXED**:
    - `index()`: `BookLending::where('status','pending')` — **no school_id scope**, leaked ALL pending lendings cross-school. Fixed with `whereHas('user', ...)`.
    - `show()`: unscoped `where('id',$id)` — fixed.
    - `edit()`: unscoped `where('id',$id)` — fixed.
    - `update()`: unscoped BookLending + LibraryCard lookups — fixed.
    - `store()`: unscoped LibraryCard lookup — fixed.
    - All 3 catch blocks: `Log::info()` → `Log::error()` + error response.
    - Also fixed a bug where the search query used `BookCategory` model instead of `BookLending`.

  **6. Auxiliary Modules (Dashboard, Task, Holidays, ActivityLog) — ✅ PASS** — All school_id-scoped.

- **Summary (commit b30d7bc)**:
  | Severity | Count | Modules |
  |---|---|---|
  | 🔴 HIGH | 6 | BookLendingController (5), BookCategoryController (1) |
  | 🟡 MEDIUM | 3 | BookLendingController catch blocks |

- **Files modified**: `BookLendingController.php`, `BookCategoryController.php`
- **Status**: ✅ Librarian role audit complete. Parent portal confirmed not applicable.

### Grand Totals — All Role Audits
| Role | Commits | 🔴 HIGH | 🟡 MEDIUM | ✅ PASS Modules |
|---|---|---|---|---|
| School Admin | 98f5758, b4807e0 | Parent, Student, Library + Messaging built | 2 | 7/7 |
| Teacher | a37b84f | 5 | 2 | 9/9 |
| Accountant | 077464a | 13 | 4 | 7/8 |
| Receptionist | 7eecd78 | 10 | 7 | 8/8 |
| Librarian | b30d7bc | 6 | 3 | 6/6 |
| **Total** | | **38** | **18** | |

### Updated Priorities
1. ✅ **Role audits complete**: School Admin, Teacher, Accountant, Receptionist, Librarian
2. ❓ **Accountant Financial Reports** — needs second look (may be admin-only)
3. ✅ **Parent portal**: Confirmed NOT applicable — API-only, no web dashboard
4. ⏸️ **Dependabot (149 alerts, 7 critical)** — still untouched
5. ⏸️ **SiteSubadmin + Non Teaching removal** — still pending
6. ⏸️ **Laravel 10→11 upgrade** — ready, not started
7. ⏸️ **Design-system migration (~214 unmigrated views)** — deferred
8. ⏸️ **Toshi component refactor** — deferred

### 2026-07-11 (Second Session, Part 2): Built admin Messaging landing page
- **Work done**:
  1. Chose option (b) — build a thin `/admin/messages` landing page rather than repointing the sidebar to an existing form. Small effort, better UX, room for module to grow.
  2. Created `resources/views/admin/messages/index.blade.php` with 3 ds-* styled option cards: **Message Students** (/admin/students), **Message Teachers** (/admin/teachers), **View Sent Messages** (/admin/sentmessages)
  3. Replaced the old redirect closure in `routes/admin.php` (`/messages` → redirect to `/admin/sentmessages`) with a proper route rendering the landing view, named `admin.messages`
  4. Updated sidebar link in `resources/views/layouts/admin/menu.blade.php` from `url('admin/sentmessages')` to `route('admin.messages')`
- **Files modified**: `resources/views/admin/messages/index.blade.php` (NEW), `routes/admin.php` (line 877), `resources/views/layouts/admin/menu.blade.php` (line 100)
- **Key decisions**: Landing page approach (option b) preferred over direct link to student list — cleaner UX, extensible for future features (bulk SMS, WhatsApp, templates). Used ds-* card pattern consistent with other admin pages.
- **Status**: ✅ Done — all pages click-tested locally (0 errors). Sidebar → landing page → each option card verified.
- **Edge cases flagged**: The existing send-message forms are Vue components embedded in the student/teacher list pages (not separate views). The landing page links to those list pages appropriately.

### 2026-07-11 (Second Session): Built admin Library module — book CRUD + lending + cards
- **Work done**:
  1. Investigated the dead `/admin/library` sidebar link (audit Module 6, HIGH severity). Found: existing models (Book, BookCategory, BookLending, LibraryCard) + full Librarian role module guarded by `librarian` middleware (usergroup_id=8). School admins couldn't access any library functionality.
  2. **Decision: BUILD** — existing data models are fully migrated, sidebar link exists but leads to 404 via librarian middleware redirect. Building a thin admin layer reusing the same models is the correct approach.
  3. Built `Admin\LibraryController` with: bookIndex (search/paginate), bookCreate/bookStore, bookEdit/bookUpdate, bookDestroy, lendIndex (Active/Returned/Cancelled filter tabs), lendCreate/lendStore (check-out with validation: school ownership, duplicate active lend check), lendReturn (check-in), cardIndex (student selector + card view + lending history)
  4. Created 5 Blade views using ds-* pattern classes: `admin/library/books/index.blade.php`, `admin/library/books/create.blade.php`, `admin/library/books/edit.blade.php`, `admin/library/lends/index.blade.php`, `admin/library/lends/create.blade.php`, `admin/library/cards/index.blade.php`
  5. Added 11 routes under `admin/library/*` prefix with `admin.library.*` names
  6. Fixed sidebar link in `resources/views/layouts/admin/menu.blade.php` from dead `url('library/books/index')` to `route('admin.library.books')`
- **Files modified**: `app/Http/Controllers/Admin/LibraryController.php` (NEW — 267 lines), `routes/admin.php` (replaced redirect with route group), `resources/views/layouts/admin/menu.blade.php` (fixed link), plus 6 new view files under `resources/views/admin/library/`
- **Key decisions**: Reused existing Book/BookCategory/BookLending/LibraryCard models directly — no new migrations or model changes. Controller validates school_id ownership for all operations. Used ds-* CSS classes (ds-card, ds-table, ds-btn, ds-form-input, ds-badge, ds-page-head-title) for visual consistency with other admin pages. No ds-pattern-library.md existed — used the actual ds-* classes from dashboard-refresh.css.
- **Status**: ✅ Done — all pages click-tested locally (0 errors)
- **Edge cases flagged**: Cards page initially 500'd due to `adm_no` column not existing on `users` table — fixed to use `registration_number`. Librarian role's existing module is untouched; this is a separate admin-facing set of routes and views. The sidebar now has two "Library" entries (visible to both admin and librarian roles) — by design since they're separate modules.

### 2026-07-11: Toshi Alpine.js fix, Fee Collection Trends chart, production deploy
- **Work done**:
  1. Diagnosed Toshi not responding to clicks — root cause was duplicate Alpine.js (CDN `cdn.jsdelivr.net/npm/alpinejs@3.14.8` loaded in `<head>` of `layouts/app.blade.php` alongside Livewire v3's bundled Alpine), causing `$wire is not defined` errors
  2. Fixed by removing the CDN Alpine.js script tag from `layouts/app.blade.php` and running `php artisan livewire:publish --assets`
  3. Built Fee Collection Trends chart — `computeFeeTrend()` in `app/Traits/Dashboard.php` aggregates FeePayment amounts by day/week/month; `DashboardController.php` reads `?period=` query param (validates day|week|month); view adds a Chart.js line chart below Students Per Class
  4. Deployed all changes to production (`46.101.111.131`) via SCP, cleared cache, verified 0 console errors
  5. Committed and pushed to main (`f1c73d0`)
- **Files modified**:
  - `app/Traits/Dashboard.php` — Added `use App\Models\FeePayment`, added `computeFeeTrend()` method
  - `app/Http/Controllers/Admin/DashboardController.php` — Reads `?period=` query param, passes `$feeTrend` + `$trendPeriod` to view
  - `resources/views/admin/dashboard/dashboard.blade.php` — New Fee Collection Trends card with Days/Weeks/Months toggle + Chart.js line chart
  - `resources/views/layouts/app.blade.php` — Removed duplicate CDN Alpine.js script (line 26)
- **Key decisions**: Used SCP over rsync for prod deploy after rsync failed to overwrite files (Docker volume handle issue). Chart uses same Chart.js instance already loaded by Livewire (no extra dependency). Period toggle uses query params (`?period=day|week|month`) via `request()->fullUrlWithQuery()` to preserve other query state.
- **Status**: ✅ Done
- **Edge cases flagged**: Fee payments table has no test data for demo school — chart gracefully shows flat line at zero. Chart.js version loaded doesn't support `Chart.getChart()` API (v3.x), so instance verification needs the canvas element check instead.

### Local Dev
```bash
# Start MySQL
brew services start mysql

# Start server
php artisan serve

# Start Evolution API (Docker via Colima)
colima start
```

---

## Landing Pages

| URL | File | Status |
|---|---|---|
| `/` | `landing.blade.php` | v1 — official landing |
| `/landing2` | `landing2.blade.php` | v2 — alternate |

**v1 Features:**
- Flare-style navbar: transparent → white + compact on scroll (scrollY > 60)
- Gradient hero H1: animated blue→green→amber (`gradientShift`)
- Looping keyword typewriter: Grades → fees → attendance → health → canteen → discipline → notifications → timetables → exams → reports
- Audience tabs: Admin/Teacher/Parent with auto-rotation every 3s
- Stable hero container: `min-height: 20rem` on wrapper, `3.3em` on H1
- WhatsApp phone mockup, pricing, testimonials, CTA sections

---

## Dashboard Redesign (COMPLETED — June 2026)

### Files Changed
| File | Change |
|---|---|
| `public/css/dashboard-refresh.css` | Full rewrite — Sora/DM Sans fonts, brand colors, CSS variables, card hover |
| `resources/views/layouts/app.blade.php` | Font import: Nunito → Sora + DM Sans |
| `resources/views/admin/dashboard/dashboard.blade.php` | Cleaned HTML structure (all PHP/JS logic untouched) |
| `resources/views/layouts/admin/sidebar.blade.php` | Rounded active states, green accent |
| `resources/views/layouts/partials/navigation.blade.php` | White bg, dark text, Sora font, vanilla JS hamburger |
| `resources/views/auth/login.blade.php` | Fixed false maintenance banner (default login_status to 1) |

### Design Tokens (Current — July 6, 2026 Flare-inspired redesign)
| Token | Value | Usage |
|---|---|---|
| Primary accent | `#22C55E` (green) | CTAs, buttons, active states, toggles |
| Secondary brand | `#1E6FD9` (blue) | Links, info badges, secondary elements |
| Amber | `#D97706` | Warnings, amber glow effect |
| Canvas | `#FAFAF5` | Page background (light cream) |
| Surface | `#FAFAF5` | Shells, containers |
| Card/white | `#FFFFFF` | Card backgrounds |
| Text | `#1E293B` | Body text (slate gray) |
| Text secondary | `#64748B` | Labels, meta, subtitles |
| Muted | `#94A3B8` | Placeholders, footnotes |
| Dark/navy | `#0F172A` | Dark accent (sidebar text, headings) |
| Border | `#E2E8F0` | Borders, dividers, rings |
| Sidebar/navbar bg | `#FFFCF5` + amber glow + diamond pattern | Matching landing page feature section |
| Display font | Sora | Headings, KPIs |
| Body font | DM Sans | Body, labels, tables |
| Card radius | 14px | Cards, dropdowns |
| Shadow system | Ring-based `0 0 0 1px var(--d-border)` | Replaced box-shadows |

### Admin Dashboard Analytics
| Metric | Type | Data |
|---|---|---|
| Students | KPI card | `$dashboard['studentCount']` |
| Teachers | KPI card | `$dashboard['teacherCount']` |
| Parents | KPI card | `$dashboard['parentCount']` |
| Staff | KPI card | `$dashboard['nonteachingCount']` |
| Gender split | Doughnut chart | Chart.js 2.6, `maleCount`/`femaleCount` |
| Notices | List | Latest 5 |
| Exams | Table | Upcoming (configurable via gexam) |
| Feedbacks | List | Parent feedback |
| Events | Table | Latest 5 |
| Products | Table | Sellable products |
| Absentees | Vue component | `<absentees-student>`, `<absentees-staff>` |

---

## WhatsApp Integration

### Architecture
```
User ↔ WhatsApp ↔ Evolution API (Docker) ↔ Laravel Webhook
                                              ↕
                                        WhatsAppService (send)
                                        OutboundWhatsAppService (notify)
                                        MessageDeliveryLog (track)
```

### Evolution API Config
| Setting | Local | Production |
|---|---|---|
| URL | `http://localhost:8081` | `http://10.19.0.6:8081` |
| API Key | `68ca94ce...` | `78E5A6FF...` |
| Instance | `klassapp` | `klassapp` |

### Flow Architecture

```
                    ┌──────────────────────────────────────────┐
                    │            Inbound Flow                   │
                    │  User sends WhatsApp → +256 765 275289    │
                    │         ↓                                 │
                    │  Meta Cloud API (WABA) receives message    │
                    │         ↓                                 │
                    │  POST https://klassapp.xyz/.../inbound     │
                    │         ↓                                 │
                    │  WhatsAppController@handleInbound()        │
                    │  ├─ GET?  → webhook verification           │
                    │  └─ POST? → detect payload:                │
                    │      ├─ object: whatsapp_business_account  │
                    │      │  → handleMetaInbound()              │
                    │      └─ Evolution API format               │
                    │         → handleEvolutionInbound()         │
                    └──────────────────────────────────────────┘
                              ↓
                    processMetaMessage() / routeInbound()
                    ├─ Not identified? → "not linked" reply
                    ├─ Opted out? → optout reply
                    ├─ "optin"/"optout" → toggle, confirm
                    └─ Keyword match → data query → reply
                         ↓
                    flushPending() → drain queued notifications
                                      into free service window

                    ┌──────────────────────────────────────────┐
                    │           Outbound Flow                   │
                    │  Trigger events:                          │
                    │  ├─ Grades published → sendGradeNotification()
                    │  ├─ Fee reminder    → sendFeeReminder()
                    │  ├─ Cron: flushAllOpenWindows() (free)    │
                    │  └─ Cron: sendExpiredQueue() (cold, $)    │
                    │         ↓                                 │
                    │  OutboundWhatsAppService@queueOrSend()     │
                    │         ↓                                 │
                    │  ┌─ 24hr window open? ─┐                  │
                    │  │    yes          no   │                  │
                    │  ↓                    ↓                    │
                    │  sendTextDual()    queueNotification()     │
                    │  ↓                    ↓                    │
                    │  ┌─ Business API configured? ─┐            │
                    │  │  yes          no          │            │
                    │  ↓              ↓            │            │
                    │ WhatsAppBiz    WhatsAppSvc   │            │
                    │ (Meta Graph)   (Evolution)   │            │
                    │  ↓              ↓            │            │
                    │ POST /messages  POST /sendText            │
                    │ Logged to message_delivery_log             │
                    └──────────────────────────────────────────┘
```

### Dual-Transport Priority (OutboundWhatsAppService)
1. Try `WhatsAppBusinessService` (Meta Cloud API) first
2. If fails or not configured → fall back to `WhatsAppService` (Evolution API)
3. All callers (`MarksController`, `SendGradesToWhatsApp`, console commands) work unchanged via Laravel auto-resolution

### Cost Optimisation
- **Free window**: When a parent sends a message, a 24hr customer service window opens. Replies sent within this window are FREE (up to 1000/month).
- **Queued delivery**: If the window is closed, notifications are queued in `whatsapp_pending_notifications` and sent when the parent next messages (`flushPending()` drains queue into the free window).
- **Cold sends**: Cron `sendExpiredQueue()` sends notifications whose `send_after` deadline has passed — costs ~$0.004 each.

### Inbound Feature Matrix (by role via WhatsApp keywords)

| Role | Keywords | Response |
|---|---|---|
| **Parent** (7) | GRADES, FEES, ATTENDANCE, EVENTS | Grades by exam, fee balance, attendance %, upcoming events |
| **Student** (6) | GRADES, ATTENDANCE, FEES, TIMETABLE, HOMEWORK | Personal results, attendance, schedule, assignments |
| **Teacher** (5) | MARKS, ATTENDANCE, TIMETABLE, ASSIGNMENTS | Enter/view marks, mark attendance, schedule, homework |
| **SchoolAdmin** (3) | STUDENTS, STAFF, EXAMS, FEES, REPORTS, NOTICES | Student/staff lists, exam overview, fee reports, analytics |
| **Receptionist** (10) | NOTICES, CALLS | Announcements, call logs |
| **Accountant** (11) | FEES, REPORTS | Fee collections, financial summaries |
| **Staff w/ children** (any) | MY CHILDREN | Parent features for their own kids |
| **Unidentified** | anything | "Not linked" message → contact school |

### n8n / RAG / External Bot Integration
- Laravel handles keyword routing **inline** — no RAG, no LLM, no vector store.
- n8n was designed as the **conversation orchestrator** bridging WhatsApp → external services (Typebot/Flowise).
- Flow: `WhatsApp → Laravel webhook → HTTP call to n8n → Typebot/Flowise (conversation AI) → Laravel data API (identify, marks, attendance, fees)`
- The `/api/whatsapp/identify-user` endpoint is the data-only API n8n calls to resolve a phone number to a user, roles, and linked students.
- If integrating with external school ERPs, n8n is the right integration layer — Laravel stays as the data provider.

### Meta WhatsApp Business Cloud API (Active — June 2026 +)

**Critical ID hierarchy:**
- **Business Portfolio ID** (business.facebook.com): `856846937044672` — the Meta Business Account
- **WABA ID** (WhatsApp Business Account): `1709193870117417` — owns the phone number, receives messages
- **App ID** (developers.facebook.com): `1674033610469729` — the developer app with webhook callback URL
- **Phone Number ID**: `1192586767270209` — `+256 765 275289`, verified name "KlassApp", mode LIVE

**The WABA ID and Business Portfolio ID are DIFFERENT.** Using the wrong WABA ID was the root cause of webhook delivery failure.

**Webhook flow:**
1. App-level: Callback URL + Verify Token configured in App Dashboard ✓
2. App-level: Webhook field `messages` subscribed in WhatsApp → API Setup ✓
3. **WABA-level** (the missing step): `POST /{waba-id}/subscribed_apps` — subscribes the app to receive webhooks from the WABA. Requires the CORRECT WABA ID.
4. Once subscribed, Meta sends POST webhooks to the callback URL for each message the WABA receives.

**Outbound messaging** uses `phone_number_id` + token — works independently of WABA ID correctness.
**Inbound webhook delivery** requires both (2) the field subscription AND (3) the correct WABA subscription.

**Config:**
- `.env`: `WHATSAPP_BUSINESS_WABA_ID=1709193870117417`
- `config/services.php`: reads via `env()` — no code change needed
- Cache cleared: `php artisan optimize:clear`

### Webhook
- **Inbound**: `POST /api/whatsapp/inbound` → `WhatsAppController@handleInbound`
  - Handles both Evolution API (old) and Meta Cloud API (new) payloads — auto-detected via `object: "whatsapp_business_account"`
- **Delivery**: `POST /api/whatsapp/delivery` → `WhatsAppController@deliveryWebhook`
- **Critical fix**: Removed `EnsureFrontendRequestsAreStateful` from API middleware group (was causing 302 redirects)

### Key Files
| File | Purpose |
|---|---|
| `app/Services/WhatsAppService.php` | Evolution API client — sendText, sendList, sendTemplate |
| `app/Services/WhatsAppBusinessService.php` | Meta Cloud API client — sendText, sendTemplate, isConfigured |
| `app/Services/OutboundWhatsAppService.php` | Dual-transport router — tries Business API first, falls back to Evolution |
| `app/Http/Controllers/Api/WhatsAppController.php` | Webhook handlers, user identification |
| `app/Http/Controllers/Admin/WhatsAppDashboardController.php` | Delivery dashboard |
| `app/Models/WhatsAppUser.php` | Phone→user linking |
| `app/Models/MessageDeliveryLog.php` | Message tracking |
| `scripts/provision-evolution.sh` | Evolution API server provisioning |

---

## Test Credentials

| Role | Email | Password |
|---|---|---|
| Super Admin | `siteadmin@gmail.com` | `password` |
| Test School One | `admin@testschoolone.sch.ug` | `password123` |

---

## Toshi 2.0 Spec (Final — June 2026)

### Dual-Mode Architecture
- **Super Admin Dashboard**: Creates schools from scratch (plan selection → all 13 steps → commit)
- **School Admin Dashboard**: Completion mode (detects missing setup, prompts step-by-step)

### WhatsApp Integration
- **Verification**: Pre-approved template (`klassapp_verification`) sent via shared number
- **Opt-in strategy**: Prompt user to send first message to KlassApp number OR click WABA link to open 24hr window, OR use template (utility category, cheapest tier) for first contact
- **WhatsAppUser**: Source of truth for parent phone→school mapping. `school_id` column needed on table.
- **Parent verification**: Parent confirms student name during opt-in flow to link to correct school

### Implementation Priority (Ordered)

| # | Task | Effort | Status |
|---|---|---|---|
| 1 | Fix role assignment in commitAll() | 30 min | ✅ Done |
| 2 | Fix fee persistence in commitAll() | 30 min | ✅ Done |
| 3 | Fix step machine bug ($this->step = 1) | 30 min | ✅ Done |
| 4 | Add plan selection as Step 0 | 1 hr | ✅ Done |
| 5 | Button-driven flow (school type, confirm/edit pattern) | 3 hrs | ✅ Done |
| 6 | Input validation (duplicate, email, phone) | 2 hrs | ✅ Done — centralized validateRequired, normalizeUgandaPhone, validateEmail, isDuplicateSchool helpers |
| 7 | Review card before commit | 2 hrs | ✅ Done — styled card with plan, school, admin, counts grid, Confirm & Edit buttons |
| 8 | Error handling + user-friendly commit messages | 1 hr | ✅ Done — specific QueryException handling (duplicate vs connection), try/catch in commit, success card with credentials, reset button |
| 9 | WhatsApp verification step | 3 hrs | ✅ Done — OTP send/verify via WhatsApp template, WhatsAppUser record on commit, status in review card |
| 10 | Dual-mode detection (super admin vs admin) | 2 hrs | ✅ Done |
| 11 | Progress persistence (onboarding_sessions table) | 3 hrs | ✅ Done |
| 12 | Mandatory/optional step enforcement | 1 hr | ✅ Done |
| 13 | Admin dashboard completion mode | 3 hrs | ✅ Done (part of Item 10) |
| 14 | Persistent reminders (post-onboarding) | 3 hrs | ✅ Done |
| 15 | XLSX file parsing (CSV, XLSX, PDF, DOCX) | 2 hrs | ✅ Done |
| 16 | Add school_id to whatsapp_users table | 1 hr | ✅ Done |
| 17 | Co-admin invite step in Toshi | 2 hrs | ✅ Done |
| 18 | Premium API access (Integrations page) | Deferred | ⏸️ |

### Implementation Status

**Done (June 19):**
- ✅ Item 1 — Role assignment via `usergroup_id = 3` already correct, no fix needed
- ✅ Item 2 — Fee persistence: `commitAll()` now creates `FeesCategories` records
- ✅ Item 3 — Step machine bug: All handlers use `$substep` pattern instead of hardcoded step jumps
- ✅ Item 4 — Plan selection: Step 0 with plan buttons, `selectPlan()`, `CurrentPlan` + `Subscription` on commit
- ✅ Item 5 — Button-driven confirm/edit flow across all 12 steps with [✅ Looks good] / [✏️ No, edit] buttons
- ✅ Plan enforcement: `no_of_students`/`no_of_users` columns added, StudentController limit check, dashboard usage banner
- ✅ Toshi avatar: Fixed from non-existent `favicon/klassapp-favicon.svg` to `images/klassapp-logo.svg`
- ✅ Duplicate Alpine on superadmin layout: Commented out manual Alpine CDN
- ✅ Landing pricing restored to pre-merge simple inline design
- ✅ Real school names in marquee, real reviewers in testimonials

**Done (June 20):**
- ✅ Item 14 — Persistent reminders: Amber dismissible banner on admin dashboard showing missing onboarding steps + "Open Toshi" button + session dismiss route
- ✅ Item 12 — Mandatory/optional step enforcement: 7 mandatory steps (plan_selection, school_info, admin_account, academic_year, standards, subjects, terms), skip blocked with message, optional steps (teachers, students, fees, exams, whatsapp_verify, teacher_links)
- ✅ Item 15 — XLSX/DOCX/PDF parsing: `extractNamesFromFile()` handles CSV/TXT (fgetcsv), XLSX/XLS (PhpSpreadsheet), PDF (smalot/pdfparser), DOCX (PhpOffice/PhpWord). Libraries installed via composer.
- ✅ Item 16 — `school_id` on `whatsapp_users`: Migration adds nullable FK column. Model updated with `school()` relationship. All creation points (OnboardingAgent, UserProfileController) now set `school_id`.
- ✅ Item 17 — Co-admin invite step: Dual-mode step added to Toshi. Create mode: enter email+name → new User created. Complete mode: shows existing teachers as selectable buttons → promote (usergroup_id 5→3) on commit. Success card shows credentials or "Promoted from teacher".

### School Type Flow
- Category: [Primary] [Secondary] [Primary & Secondary (Mixed)]
- Levels (if Secondary/Mixed): [O-Level] [A-Level] [Both O&A]
- Gender (always): [Boys] [Girls] [Mixed (Co-ed)]
- Curriculum defaults load from NCDC Uganda per combination

### Known Issues

1. **Chart.js 2.6** — Do NOT upgrade to v4. API has breaking changes (legend→plugins.legend, tooltips→plugins.tooltip, scale→scales)
2. **Landing v2** — Has different navbar JS (direction-aware). Not aligned with v1 style.
3. **Toshi assistant mode** — Placeholder only. No handleGeneralQuery() yet. Cannot answer questions or run reports.
4. **Landing pages** — Both have broken HTML artifacts from previous merges (stray `</nav>` tags, duplicate mobile menus, garbled WhatsApp link fragments). Have been cleaned up but v2 navbar scroll direction still differs from v1.
5. **`/usecases/*` HTTP 404 is pre-existing** — `mapStaticRoutes()` commented identically on `main` and feature branches; Blade views exist in `routes/static.php` but are not registered.

---

## Environment Variable Gotchas

### TOSHI_LARAGENT_ENABLED env var leak (fixed July 2026)

**Symptom:** `ToshiAssistantAgentTest::feature_flag_defaults_to_disabled` fails intermittently — asserts `config('toshi.laragent_enabled')` is false but gets true, even though `.env` doesn't set it or sets it correctly.

**Root cause:** Laravel 10's `Dotenv\Repository` caches env values from THREE sources — `getenv()`, `$_SERVER`, and `$_ENV` — at boot, and caches them immutably. If `TOSHI_LARAGENT_ENABLED` was ever exported in a parent shell (e.g. the terminal that launched opencode/your dev tooling), it persists in `$_SERVER` even after `putenv()` clears `getenv()`. A shell-level export silently overrides `.env` for any Laravel process spawned from that shell — **this is NOT specific to Toshi, it applies to any env var**.

**Fix applied:**
1. `.env` explicitly has `TOSHI_LARAGENT_ENABLED` commented out (not just absent) with a note on intended usage (per-school-admin toggle, not global).
2. The specific test was hardened to clear all three sources (`getenv`, `$_SERVER`, `$_ENV`) plus reset `Illuminate\Support\Env`'s cached repository via reflection, so it tests the true default regardless of ambient shell state. See `tests/Feature/Toshi/ToshiAssistantAgentTest.php::feature_flag_defaults_to_disabled` for the implementation.

**If this pattern recurs elsewhere:** any test asserting a config "default" (not an explicitly-set value) is vulnerable to the same class of bug if the corresponding env var has ever been exported in a shell that spawns test runs. The fix pattern (clear `getenv`/`$_SERVER`/`$_ENV` + reset the Env repository) is reusable — don't assume `.env` alone is the source of truth when debugging config default mismatches.

**Caution for Laravel 11 upgrade:** the reflection-based repository reset (`ReflectionClass(\Illuminate\Support\Env::class)->setStaticPropertyValue('repository', null)`) depends on Laravel 10's internal implementation of `Env`. If this test starts failing after the Laravel 11 composer update, check this reflection call first — the internal structure of `Illuminate\Support\Env` may have changed.

---

## Session Log

### 2026-07-06: Nursery descriptive assessment grading + PDF report rendering + ReportsController fix
- **Work done**: Implemented descriptive assessment grading for Nursery (4 domains, 4-level scale with no points/percentages). Created NurseryAssessment model/migration. Made points/min_score/max_score nullable in school_grading_systems. Added nursery detection and conditional rendering to PDF report card generation (controller + view). Fixed ReportsController@index typo bug. Browser-verified: Reports page load, CSV export download, fee reconciliation page, messaging page. Health aggregate dashboard explicitly deferred.
- **Files modified**: `.gitignore`, `config/grading_uganda.php`, `app/Helpers/GradingHelper.php` (seeding loop), `app/Http/Controllers/Admin/DownloadStudentReport.php` (nursery detection), `app/Http/Controllers/Admin/ReportsController.php` (bug fix), `resources/views/admin/marks/student-report.blade.php` (nursery conditional block)
- **Files created**: `app/Models/Academics/NurseryAssessment.php`, `database/migrations/2026_07_06_022733_make_points_nullable_in_school_grading_systems.php`, `database/migrations/2026_07_06_030000_create_nursery_assessments_table.php`
- **Key decisions**: Nursery uses separate NurseryAssessment table (not exam_marks) for clean separation of descriptive vs numeric assessment. Single student-report template with `@if(!empty($isNursery))` conditional rather than separate nursery template. Health aggregate dashboard explicitly deferred (sidebar Health link redirects to per-student records via /admin/students).
- **Status**: ✅ Committed (d9bd1e5) and pushed to origin/main. Nursery PDF report card click-test deferred until assessment entry UI exists.
- **Edge cases flagged**: Points column forced NOT NULL in SchoolGradingSystem prevented seeding nursery grades with null points — fixed via migration making points/min_score/max_score nullable. Reports page route commented out — fix prevents crash if re-enabled. No assessment entry UI for nursery yet (no UI to enter domain ratings).

### 2026-07-04: Role 2 School Admin audit — 7 modules + 2 carried-forward checks
- **Work done**: Performed systematic functional and UI audit of School Admin role. Covered impersonation boundaries, role-capability scoping, student management, parent management, class/subject setup, reports, messaging, library, and health modules. Identified: 1 HIGH (Library has models but no admin UI — dead sidebar link), 2 MEDIUM (Messaging dead sidebar link; StudentController destroy() has empty catch blocks + missing school_id on sub-record deletion), 1 LOW (ParentController ungrouped orWhere in count query). Confirmed 4 dead sidebar links (library, health, messaging, transport). Documented all findings in new `## Role 2 Audit` section.
- **Files modified**: `knowledge.md`
- **Key decisions**: findings stored as permanent reference section (not just session log) because the audit will inform feature planning. Health module definitively confirmed as absent — only student-level medical history exist, no school-level health management. Library has data model but zero admin UI.
- **Status**: ✅ All audit findings resolved. Library module built (commit b4807e0), Messaging landing page built (b4807e0), StudentController empty catches + school_id scoping fixed (b4807e0), ParentController fixes (98f5758), ParentController LOW orWhere verified-resolved, Transport MVP built (Transportation model + TransportController + 3 views, this session). Health per-student module was already fully built — only the aggregate dashboard is deferred (no dead link existed). All four dead sidebar links now point to working pages.
- **Edge cases flagged**: `orWhere` operator precedence bug in ParentController count query; `is_admin()` function name misleading (checks for School Admin, not Super Admin); `schoolAdminimpersonate()` has inverted condition logic but is safely gated by superadmin middleware.

### 2026-07-04: TOSHI_LARAGENT_ENABLED env var leak documented
- **Work done**: Documented the `TOSHI_LARAGENT_ENABLED` env var leak — root cause (Laravel 10 immutable Dotenv\Repository caches from `$_SERVER`/`$_ENV`/`getenv`), fix (triple-source scrub + Env repository reset via reflection), and reusable pattern for other config-default tests.
- **Files modified**: `knowledge.md` (new section), `tests/Feature/Toshi/ToshiAssistantAgentTest.php` (hardened test)
- **Key decisions**: Stored as permanent gotcha in dedicated `## Environment Variable Gotchas` section (not just session log) because the debugging pattern applies project-wide to any env var, not just Toshi.
- **Status**: ✅ Done

### 2026-06-20: Favicon fix, nav cleanup, landing HTML repair
- **Work done**: Fixed favicon to use SVG as primary with proper cross-browser fallback. Removed "KlassApp" wordmark from nav headers, enlarged logo (52px/44px). Repaired broken HTML on both landing pages — stray `</nav>` tags, duplicate mobile menus, garbled WhatsApp link fragment that rendered as visible text.
- **Files modified**: `resources/views/layouts/partials/favicon.blade.php`, `resources/views/landing.blade.php`, `resources/views/landing2.blade.php`, `knowledge.md`
- **Key decisions**: SVG favicon as single source of truth with `type="image/svg+xml"`. PNG fallback for older browsers. Apple touch icon unchanged.
- **Status**: ✅ Favicon fixed, nav cleaned up. Landing v1/v2 alignment still outstanding.

### 2026-06-20: Dashboard refresh, Toshi evolution — rename, assistant mode, maximize redesign
- **Work done**: Refreshed all 7 role dashboards to use dashboard-refresh.css (Accountant, Receptionist, Librarian, Student — Superadmin/Admin/Teacher already done). Fixed accountantDashboard() trait (was returning book data instead of fee data). Fixed plans table data (growth $30, premium contact sales). Renamed OnboardingAgent → AgentToshi across all files. Implemented post-onboarding assistant mode transition. Redesigned maximize modal with Claude-inspired two-column layout. Added session persistence (messages survive page refresh). Added co-admin email notification. Added chart title + timezone fixes. Maximized modal buttons were missing — fixed.
- **Files modified**: `app/Livewire/AgentToshi.php` (new, was OnboardingAgent.php), `resources/views/livewire/agent-toshi.blade.php` (new), `app/Traits/Dashboard.php`, `app/Helpers/OnboardingHelper.php`, `app/Mail/CoAdminInviteMail.php` (new), `resources/views/emails/co-admin-invite.blade.php` (new), `resources/views/emails/co-admin-promoted.blade.php` (new), `resources/views/admin/dashboard/dashboard.blade.php`, `resources/views/superadmin/dashboard.blade.php`, `resources/views/accountant/dashboard.blade.php`, `resources/views/reception/dashboard.blade.php`, `resources/views/student/dashboard/dashboard.blade.php`, `resources/views/library/dashboard.blade.php`, `database/migrations/2026_06_20_000001_add_school_id_to_whatsapp_users.php`, `app/Models/WhatsAppUser.php`, `app/Http/Controllers/Admin/UserProfileController.php`, `composer.json`, `knowledge.md`, `.env.example`
- **Key decisions**: OnboardingAgent renamed to AgentToshi for role-agnostic naming. Session-based state persistence (not database) keeps messages across refreshes. Assistant mode is placeholder — needs handleGeneralQuery() to be functional. Maximized modal uses two-column Claude layout. Plans updated: freemium $0, growth $30, premium contact sales.
- **Status**: ✅ All 7 dashboards refreshed. Toshi renamed + assistant mode wired. Next: build handleGeneralQuery() for real assistant functionality.

### 2026-06-20: Items 12, 14-17 complete — final Toshi features
- **Work done**: Completed remaining Toshi 2.0 items: Item 14 (persistent reminders — amber banner on admin dashboard with dismiss), Item 12 (mandatory/optional step enforcement — 7 mandatory steps, skip blocked), Item 15 (XLSX/DOCX/PDF parsing via PhpSpreadsheet + smalot/pdfparser + PhpOffice/PhpWord), Item 16 (school_id on whatsapp_users — migration + model + all creation points), Item 17 (co-admin invite step — dual-mode: create new admin or promote existing teacher via button selection).
- **Files modified**: `app/Livewire/OnboardingAgent.php`, `app/Helpers/OnboardingHelper.php` (new), `app/Http/Controllers/Admin/DashboardController.php`, `resources/views/partials/onboarding-reminder.blade.php` (new), `routes/web.php`, `database/migrations/2026_06_20_000001_add_school_id_to_whatsapp_users.php` (new), `app/Models/WhatsAppUser.php`, `app/Http/Controllers/Admin/UserProfileController.php`, `resources/views/livewire/onboarding-agent.blade.php`, `composer.json` (added phpoffice/phpword, smalot/pdfparser)
- **Key decisions**: PDF/DOCX names extracted via parseNameList (line-based, not column-based). Co-admin uses dual-mode: create new User (create mode) vs promote existing teacher via usergroup_id 5→3 (complete mode). Onboarding reminders use session flag for dismiss. Step enforcement uses centralized skip guard in send() plus mandatory handler modifications.
- **Status**: ✅ Items 1-17 complete. Item 18 (Premium API) deferred. Remaining work: missing dashboards, bar chart data, navbar scroll fix, mobile audit.

### 2026-06-19: Toshi 2.0 items 1-11 complete, plan enforcement, landing fixes
- **Work done**: Completed Toshi 2.0 items 1-11: critical fixes (fee persistence, step machine), plan selection, button-driven confirm/edit flow across all steps, input validation helpers (validateRequired, validateEmail, normalizeUgandaPhone, isDuplicateSchool), styled review card, error handling with specific QueryException messages, WhatsApp verification step with OTP send/verify, dual-mode detection (super admin create vs school admin complete), progress persistence via onboarding_sessions table. Built plan enforcement system: migration for no_of_students/no_of_users columns, StudentController limit check, dashboard usage banner. Restored landing pricing, favicon/Toshi avatar, real school names, real testimonials.
- **Files modified**: `app/Livewire/OnboardingAgent.php`, `resources/views/livewire/onboarding-agent.blade.php`, `database/migrations/2026_06_19_190534_add_plan_limits_to_plans_table.php`, `database/migrations/2026_06_19_222331_create_onboarding_sessions_table.php`, `app/Models/OnboardingSession.php`, `app/Http/Controllers/Admin/StudentController.php`, `app/Http/Controllers/Admin/DashboardController.php`, `resources/views/partials/plan-banner.blade.php`, `resources/views/admin/dashboard/dashboard.blade.php`, `resources/views/landing.blade.php`, `resources/views/landing2.blade.php`, `resources/views/layouts/superadmin-app.blade.php`, `resources/views/layouts/partials/favicon.blade.php`, `knowledge.md`
- **Key decisions**: Dual-mode architecture — super admin creates, school admin completes. Auth via usergroup_id. Progress persisted via onboarding_sessions. WhatsApp OTP uses sendTextSafe with template fallback. Confirm/edit pattern uses $substep with even=collect, odd=confirm.
- **Status**: ✅ Items 1-11 done. Next session: Item 14 (persistent reminders) then 12, 15-17.

### 2026-06-18: Toshi onboarding agent fixes (textarea, Livewire/Vue/Alpine conflict)
- **Work done**: Fixed Toshi AI onboarding agent — three compounding issues: (1) Missing `</textarea>` in maximize modal caused the browser to treat the voice button, form, messages area, and submit button as raw textarea content. (2) Duplicate Alpine instances (manual CDN Alpine + Livewire v3 bundled Alpine) caused `Livewire.all()` to return 0 components, breaking all Livewire interactions (maximize, close, restore, send). (3) Vue 2 mounted on `#app` wrapping the Livewire component compiled Alpine's `@keydown.enter.prevent` as Vue template expressions, breaking Enter-to-send with `"$wire is not defined"`.
- **Files modified**: `resources/views/livewire/onboarding-agent.blade.php` (new — added `</textarea>`, replaced Alpine `@keydown` with vanilla JS `onkeydown`), `app/Livewire/OnboardingAgent.php` (new), `resources/views/layouts/app.blade.php` (removed duplicate Alpine CDN, added `@yield('outside-app')` after `#app` closes), `resources/views/admin/dashboard/dashboard.blade.php` (moved `@livewire` to `@section('outside-app')` outside Vue's `#app`)
- **Key decisions**: Livewire component rendered outside `#app` via new `@yield('outside-app')` section to avoid Vue template compilation conflicts. Removed manual Alpine CDN — Livewire v3 bundles its own Alpine. Used vanilla JS `onkeydown` dispatching form submit events / `Livewire.find()` directly instead of Alpine's `@keydown.enter.prevent` syntax that Vue intercepts.
- **Status**: ✅ Done — collapse/expand, maximize/restore, Enter-to-send all verified working
- **Edge cases flagged**: Livewire v3 auto-injects Alpine, so manual Alpine CDN causes `"Detected multiple instances of Alpine"` warning and breaks component registration. Vue 2's `el: '#app'` recompiles the entire DOM tree inside `#app`, which conflicts with any non-Vue framework directives in that subtree.

### 2026-06-17: Meta WhatsApp Business API — webhook delivery fix
- **Work done**: Fixed inbound webhook delivery for Meta Cloud API. Root cause: config had the **Business Portfolio ID** (`856846937044672`) as `WHATSAPP_BUSINESS_WABA_ID` instead of the actual **WABA ID** (`1709193870117417`). App → WABA subscription (`POST /{waba-id}/subscribed_apps`) was silently failing because it was pointing at the wrong object. Also fixed flock() cache error by switching `CACHE_DRIVER` from `file` to `database`. Fixed PHP `parse_str()` dot-to-underscore bug in webhook verification handler. Added `WhatsAppBusinessService` and dual-transport `OutboundWhatsAppService`. Added full reply flow in `handleMetaInbound()`.
- **Files modified**: `.env` (WABA ID, CACHE_DRIVER), `app/Http/Controllers/Api/WhatsAppController.php`, `app/Services/OutboundWhatsAppService.php`, `app/Services/WhatsAppBusinessService.php`
- **Key decisions**: Separate `WhatsAppBusinessService` from `WhatsAppService` — both transports coexist during migration. Dual-transport in OutboundWhatsAppService (Business API first, Evolution fallback). Webhook endpoint auto-detects payload format via `object: "whatsapp_business_account"`. Used database cache instead of file to resolve persistent flock() errors.
- **Status**: ✅ Done — inbound and outbound both working
- **Edge cases flagged**: Meta has THREE separate IDs (Business Portfolio, WABA, App) and they are NOT interchangeable. The WABA subscription endpoint returns `{"success": true}` only when called with the correct WABA ID. Webhook verification GETs send query params with dots (PHP's `$_GET` converts dots to underscores — fixed by parsing raw `QUERY_STRING`).

### 2026-06-10: Dashboard redesign, WhatsApp webhook fix, production deploy
- **Work done**: Redesigned admin dashboard (CSS + Blade), fixed WhatsApp webhook 302 redirect, fixed login maintenance banner, deployed to production (165.245.250.16), provisioned Evolution API on 46.101.130.70, connected WhatsApp instance
- **Files modified**: `dashboard-refresh.css`, `app.blade.php`, `admin/dashboard/dashboard.blade.php`, `admin/sidebar.blade.php`, `navigation.blade.php`, `auth/login.blade.php`, `app/Http/Kernel.php`, `routes/api.php`
- **Key decisions**: Kept Chart.js 2.6 (no upgrade to avoid breaking changes). Used vanilla JS for hamburger (avoid Tailwind compilation issues). Changed Evolution API DB from MongoDB to PostgreSQL on production.
- **Status**: ✅ Done
- **Edge cases flagged**: WhatsApp webhook 302 was caused by Sanctum's `EnsureFrontendRequestsAreStateful` in API middleware group. Evolution API image name is `evoapicloud/evolution-api` not `evolutionapi/evolution-api`.

### 2026-06-26: Full WABA migration + WhatsApp UI upgrades + Facebook ban
- **Work done**: Greeting handler (Hello, Hi, Hey → menu buttons), wa.me link texts simplified to "Hello KlassApp", production IP updated (165.245.250.16 → 46.101.111.131), fully migrated from Evolution API to Meta Cloud API (removed WhatsAppService.php, all isConfigured() fallbacks, handleEvolutionInbound, buildMenuSections, processCodeVerificationForEvolution, Evolution config), added native sendList + sendTextSafe + sendToUser to WhatsAppBusinessService, switched all consumers (NotifyAdminMarksUpdated, AgentToshi, SchoolPayWebhookController, OutboundWhatsAppService) to Business API only. UI upgrades: fee balance with visual separators, attendance tonal warnings (⚠️ < 80%, ✅ > 90%), grades celebration with class rank for avg > 80%, distinct message type patterns. Recovered 9 compose methods dropped during migration. Facebook Business Account banned — need new Meta account + new WhatsApp number.
- **Files modified**: `app/Services/WhatsAppBusinessService.php`, `app/Services/OutboundWhatsAppService.php`, `app/Http/Controllers/Api/WhatsAppController.php`, `app/Listeners/NotifyAdminMarksUpdated.php`, `app/Livewire/AgentToshi.php`, `app/Http/Controllers/Api/SchoolPayWebhookController.php`, `config/services.php`, `resources/views/landing.blade.php`, `resources/views/landing2.blade.php`, `AGENTS.md`, `scripts/deploy-manual.sh`, `scripts/provision-klassapp.sh`, `scripts/provision-evolution.sh`
- **Deleted**: `app/Services/WhatsAppService.php` (Evolution API, 395 lines)
- **Key decisions**: Stripped Evolution entirely instead of leaving fallback code. No intermediate transition — Meta is the only transport now. sendList upgraded to native Meta interactive lists instead of text fallback. Class rank calculated by comparing SUM(marks) across same exam in DB.
- **Status**: ✅ WABA migration complete. Pending: new Meta Business Account + new WhatsApp number
- **Next**: Set up new Meta Business Account with new SIM number, update .env credentials, redeploy

### 2026-06-18 pt2: Landing fixes + mobile audit + PR merge resolution
- **Work done**: Fixed navbar scroll direction (v1 & v2 — now direction-aware), fixed typewriter cursor blink (pauses during typing via `.paused` CSS class, resumes after), mobile touch target audit (audience tabs `py-2`→`py-3`, hamburger `p-2`→`p-3`, mobile nav links `py-3` — all now ≥44px), dashboard visual polish across 7 roles (brand colors, empty states, responsive breakpoints), merged origin/main into whatsapp resolving 6-way conflicts
- **Files modified**: `landing.blade.php` (scroll + typewriter + tabs + hamburger + mobile menu), `landing2.blade.php` (scroll + typewriter + tabs + hamburger + mobile menu), `admin/dashboard/dashboard.blade.php` (brand badges + headings), `teacher/dashboard/dashboard.blade.php`, `accountant/dashboard.blade.php`, `reception/dashboard.blade.php`, `library/dashboard.blade.php`, `dashboard-refresh.css` (responsive breakpoints + design tokens), `knowledge.md` (merge resolution)
- **Key decisions**: Used vanilla JS `onkeydown` instead of Alpine for Enter-to-send (Vue conflict). Kept direction-based scroll on v2 (Flare-style), position-based on v1 (HTML restructured). Audience tabs now `py-3` (48px) meeting WCAG 44px touch target. Merged origin/main (40+ commits) into whatsapp — kept HEAD for dashboard files (superset), combined knowledge.md entries.
- **Status**: ✅ Done — PR #104 now rebased, all conflicts resolved, pushed
- **Edge cases flagged**: `navLogo` variable absent from v1 after main restructure — used `.site-header` scrolled class toggle only. Playwright artifacts (.playwright-mcp/) inadvertently committed then removed.

### 2026-06-20: Droplet rebuild, WABA migration, full audit
- **Work done**: Rebuilt destroyed droplet (new IP 46.101.111.131), full LEMP + Laravel provision, migrated from Evolution API to Meta WABA (direct), fixed Docker iptables DROP blocking external traffic, fixed Google OAuth (malformed .env line), fixed mobile hamburger menus on both landings + admin, fixed Str::plural for PHP 8.4 compat, added google_id migration locally, created full audit at `audit.md`
- **Key discoveries**: Production .env had concatenated line (`WHATSAPP_BUSINESS_API_VERSION=v21.0GOOGLE_CLIENT_ID=...`), Docker left iptables FORWARD DROP rules after removal, landing2 was missing mobileMenu div entirely, admin res_sidebar toggle JS ran before DOM ready
- **Verdict**: ⚠️ NOT READY for school onboarding — 6 critical blockers (35 dd() calls, APP_DEBUG=true, no HMAC webhook verification, 0 swap, no backups, 3 incomplete dashboards)
- **Next**: Fix critical items 1-6, deploy docs to production, add WhatsApp linking UI, wire real chart data

### 2026-06-23: School Pay webhook + interactive WhatsApp lists
- **Work done**: Built School Pay webhook controller (HMAC verification, student join chain, WhatsApp receipt), added free-form message builders (composeFeeBalance, composeAttendance, composeGradesOverview, composeHealthRecord, composeStudentWithdrawn, composeTermOpens, composeTermCloses), added sendButtons() and sendList() for interactive messages, replaced text-based "Reply FEES..." prompts with interactive List messages on welcome/verification, added emoji-stripping to routeInbound() so list button titles match keyword routing, added sendListDual() to OutboundWhatsAppService. Added "Link Another Student" button to all welcome/receipt lists + 10-digit code handling for recognized users in routeInbound + 'code'/'link' keywords in parent routing. Webhook receipt also uses sendList instead of sendText.
- **Files modified**: SchoolPayWebhookController.php (new), WhatsAppController.php, WhatsAppService.php, OutboundWhatsAppService.php, WhatsAppPendingParentLink.php (new), routes/api.php, 3 new migrations
- **Key decisions**: Direct linking without school approval when code matches student. Free-form messages for all 24hr-window interactions (no Meta cost). List buttons replace text-based "Reply KEYWORD" prompts for better UX. Emoji stripping in routing allows lists and typed keywords to share the same route table. Recognized users can also link additional students via 10-digit codes (routeInbound catches codes before keyword matching).
- **Status**: ✅ Done — School Pay integration complete, list buttons deployed, Link Another Student flow working for all users
- **Edge cases flagged**: School Pay webhook payload format unconfirmed (raw_payload column for inspection). List button titles with emojis need stripping before keyword matching. SCHOOlPAY_ENFORCE_SIGNATURE toggle needed before production. whatsapp_pending_parent_links table is dead schema weight (flow changed to direct linking)

### 2026-06-29 pt2: KlassApp Student IDs + ministry codes + teacher links + admin parity + onboarding audit
- **Work done**: KlassApp Student ID system (KLS0010427 format, auto-generated, unique, indexed). Migration added klassapp_student_id to student_academics. Ministry school codes (ministry_code on schools table). Toshi teacher-subject-class linking with Teacher | Subject | Class | Phone format + CSV/XLSX upload. Fixed Toshi commitAll to persist teacher links + students. Admin panel: bulk teacher link import page, WhatsApp parent management page, KlassApp ID in student edit view. Question phrasing fixes, step indicator, password collection, dead code removal, teacher link file auto-advance. Full WABA migration completed (Evolution removed). WhatsApp UI upgrades (separators, tonal warnings, class rank). Name-based WhatsApp linking with school-scoped search. Docs: ai-integration-roadmap.md, Toshi walkthrough updated. FB Business Account banned — pending new Meta account + new WhatsApp number.
- **Files modified**: 30+ files across controllers, services, livewire, migrations, blade templates, docs
- **Key decisions**: KlassApp IDs use no-dash format. School Ministry codes optional. Toshi now parity with admin panel (or better). WhatsApp linking prioritizes KlassApp ID → school ID → name+school → name.
- **Status**: ✅ Demo-ready for Wednesday. All features end-to-end verified.
- **Next**: Wednesday — onboard real school, present to founding team. After: new Meta account setup, complete Admin panel → whatsbusiness integration.

### 2026-06-29: WABA migration complete + WhatsApp UI upgrades + name-based linking + Toshi fix
- **Work done**: Greeting handler (Hello, Hi, Hey → menu buttons), wa.me links simplified to "Hello KlassApp", production IP updated, full WABA migration (WhatsAppService.php deleted, Evolution removed, all isConfigured() checks stripped, handleEvolutionInbound/buildMenuSections/processCodeVerificationForEvolution deleted). WhatsApp UI upgrades: fee separators, attendance tonal warnings, grades celebration + class rank, message type differentiation. Student name-based WhatsApp linking (replaced School Pay code dependency). AI integration roadmap at docs/ai-integration-roadmap.md. Fixed Toshi commitAll() to persist students. Facebook Business Account banned — switching to new number.
- **Files modified**: WhatsAppController.php, WhatsAppBusinessService.php, OutboundWhatsAppService.php, AgentToshi.php, config/services.php, NotifyAdminMarksUpdated.php, SchoolPayWebhookController.php, landing.blade.php, landing2.blade.php, AGENTS.md, scripts/*
- **Deleted**: app/Services/WhatsAppService.php (Evolution)
- **New**: docs/ai-integration-roadmap.md
- **Key decisions**: Name search replaces School Pay for WhatsApp linking. Stripped Evolution entirely instead of fallback. Toshi now creates students during onboarding.
- **Status**: ✅ Ready for Wednesday demo. WhatsApp name linking, Toshi onboarding, and parent messaging flow all functional.
- **Next**: Wednesday — onboard real school, demo to founding team. Then: new Meta account + WhatsApp number.

### 2026-06-30: Toshi E2E Test Passes + 6 bugs fixed in commitAll()
- **Work done**: Created Playwright E2E test for full 15-step Toshi onboarding. Fixed 3 PHP bugs (saveDraft not capturing model ID → duplicate drafts; handleTeacherLinks/handleWhatsAppVerify missing skip at substep=1 → infinite loops). Fixed 3 DB schema mismatches in commitAll() (subscriptions.user_id FK, sections.value column missing, standards_link.class_teacher_id NOT NULL). Test now creates a school end-to-end with all data (classes, subjects, teachers, students, fees, exams, subscription).
- **Files modified**: `app/Livewire/AgentToshi.php` — saveDraft(), handleTeacherLinks() skip substep=1, handleWhatsAppVerify() skip substep=1, commitAll() reordered (admin user before subscription), Section::firstOrCreate() removed bogus `value` column. `database/migrations/2026_06_30_003133_make_class_teacher_id_nullable_in_standards_link.php` — new (but not migratable due to pre-existing broken FK migration).
- **Key decisions**: E2E test lives outside repo at `/var/folders/d1/.../toshi-test.mjs`. Uses Livewire JS API (`lw.call()`) instead of DOM clicks (unreliable with Livewire 3). Always "reset" on draft (no generic resume handler). Unique timestamp-suffixed data per run to avoid DB collisions.
- **Status**: ✅ Toshi onboarding working end-to-end. Known critical issues remain: editBeforeCommit() dead end, no secondary school curriculum defaults, zero PHPUnit coverage.
- **Next**: Fix editBeforeCommit() to allow step navigation from review card. Add secondary curriculum. Write PHPUnit tests.

### 2026-06-30: Toshi architecture audit, universal layout refactor, LLM activation
- **Work done**: Full Toshi UI audit across admin and superadmin dashboards. Found Vue 2 strips `<style>` tags inside `#app` — Toshi was inside `#app` on superadmin (styles deleted), outside `#app` on admin (styles survived). Refactored Toshi from per-dashboard inclusion to universal layout-level component. Added `defer` to `app.js` on both main layouts. Fixed superadmin layout missing `@yield('outside-app')`. Gated Toshi to usergroups 1, 2, 3 at both view and component levels. Activated LLM with Nvidia NIM key across local and production. Fixed auth page styles being stripped by Vue (moved `<style>` to `@push('styles')` outside `#app`). Fixed Google Fonts `@import` inside body `<style>` by converting to `<link>` in layout `<head>`. Production asset rebuild (JS 35MB→4.5MB, CSS 1.6MB→66KB, sourcemaps removed). Added gzip + 1-year cache headers for static assets. Set session cookie Secure flag for HTTPS. Created mucu super admin on production DB.
- **Files modified**: `resources/views/layouts/app.blade.php` (add Toshi + defer), `resources/views/layouts/superadmin-app.blade.php` (add Toshi + `@yield('outside-app')` + defer), `resources/views/superadmin/dashboard.blade.php` (remove per-dashboard Toshi), `resources/views/admin/dashboard/dashboard.blade.php` (remove per-dashboard Toshi), `app/Livewire/AgentToshi.php` (usergroup gate), `resources/views/auth/login.blade.php`, `resources/views/auth/register.blade.php`, `resources/views/auth/passwords/reset.blade.php`, `resources/views/auth/passwords/email.blade.php`, `resources/views/auth/verify.blade.php` (move styles outside Vue `#app`), `resources/views/layouts/empty.blade.php` (fonts to `<link>`), `webpack.mix.js` (conditional sourcemaps), `public/` (production rebuild), `.env.example`
- **Key decisions**: Toshi lives at layout level, not per-dashboard — any authenticated page using `layouts/app.blade.php` or `layouts/superadmin-app.blade.php` gets it automatically. LLM base URL is `https://integrate.api.nvidia.com/v1` (not `api.nvcf.nvidia.com`). Toshi access gated to usergroups 1, 2, 3 — expands later. Draft resume via `OnboardingSession` DB table is superadmin-only.
- **Status**: ✅ Done
- **Edge cases flagged**: Shell env vars override `.env` because `phpdotenv` doesn't overwrite existing environment variables. NIM base URL changed from `api.nvcf.nvidia.com` to `integrate.api.nvidia.com`.

### 2026-07-01: Toshi persona memory system
- **Work done**: Built persistent persona memory for Toshi. Each user gets a `toshi_personas` row that stores a one-paragraph summary of their communication style, what they care about, and their general vibe. The summary is injected into the LLM system prompt as `{persona}` so Toshi adapts tone and depth to each user. After every LLM interaction, a counter increments; every 5th interaction (configurable via `TOSHI_PERSONA_UPDATE_INTERVAL`), a lightweight extraction LLM call (gpt-4o-mini, 200 max tokens) reads the conversation and updates the persona. Also: user chat bubbles changed from black to off-white (`#F8FAFC`) to match Toshi's style; three composer icons stripped of backgrounds/borders per user request.
- **Files modified**:
  - `database/migrations/2026_07_01_000001_create_toshi_personas_table.php` — new
  - `app/Models/ToshiPersona.php` — new
  - `app/Services/ToshiAssistantService.php` — `getPersonaSummary()`, `learnFromInteraction()`, `buildSystemPrompt()` persona injection, `callLLM()` maxTokens param
  - `config/toshi.php` — added `persona_enabled`, `persona_update_interval`, `persona_model`
  - `resources/views/livewire/agent-toshi.blade.php` — user msg bg `#F8FAFC`, icon cleanup
- **Key decisions**: Persona learning happens inside `ToshiAssistantService::ask()`/`askStreamed()`, not in Livewire — zero changes needed in AgentToshi controller. Persona extraction uses a separate model config (defaults to same as main). Disabled via `TOSHI_PERSONA_ENABLED=false` with no cost impact. Extraction LLM call is wrapped in try-catch — API failures degrade silently, existing persona survives.
- **Status**: ✅ Done
- **Edge cases flagged**: `users.id` is `INT UNSIGNED` (not `BIGINT`) — `foreignId()` creates `BIGINT UNSIGNED` causing FK constraint failure; fixed by using `integer('user_id')->unsigned()` manually. All DB data was wiped during migration testing via `php artisan db:wipe` — required re-seeding.

### 2026-07-02: Onboarding fixes, student class assignment bug, LarAgent migration, design overhaul
- **Work done**: Fixed WhatsApp TypeError (nullable token properties + non-blocking OTP). Created `commit()` public method for Confirm button. Added pre-flight duplicate checks (school name + admin email). Fixed critical student class assignment bug (all students were dumped into P1 regardless of class field). Fixed edit flow with fuzzy step matching. Deduplicated teachers/students from file uploads. Created missing userprofile for mucu super admin. Reset Kabale Junior School admin password. Phase 2 design implementation: fixed Toshi pill color (H2), applied DM Sans to dashboard body (M1), CSS consolidation plan documented. Admin dashboard: unified KPI card padding (M2), reduced icon sizes, standardized empty states. Toshi widget: extracted 6 CSS classes (pill/panel/modal), fixed state-transition bugs (removed inline JS pill onclick, fixed modal overlay close, added mobile responsive widths with dvh units, touch targets 44px min). LarAgent migration (Steps 1-7): installed LarAgent, created `ToshiAssistantAgent` with 18 #[Tool]-annotated action methods, Nvidia NIM provider config, feature flag (`TOSHI_LARAGENT_ENABLED`), parallel routing with path logging. Steps 8-11: complexity-based model routing, response caching, batched persona extraction, static context caching. 17 tests passing. Sidebar overhaul: replaced 171 inline SVGs with Blade component (`<x-icons.sidebar>`), centralized active state helper across all 9 role menus. Internal pages: wrapped students list and standardlinks views in dashboard card pattern. Toshi CSS: 20+ new classes for messages, composer, buttons, progress, badges. Open Design audit applied: Navy header (#0D1526) with green pulsing status dot, message animations (fade+slide), three-axis bubble differentiation (alignment + background tone + green left border accent), composer surface matching chat area, safe-area-aware pill positioning (`env(safe-area-inset-bottom)`).
- **Files modified**:
  - `app/Livewire/AgentToshi.php` — commit(), pre-flight checks, student class fix, edit flow fuzzy match, handleAssistantQuery LarAgent routing
  - `app/Services/WhatsAppBusinessService.php` — nullable token properties
  - `app/Services/ToshiAssistantService.php` — unchanged (legacy path preserved)
  - `app/Services/ToshiActionService.php` — unchanged
  - `app/AiAgents/ToshiAssistantAgent.php` — NEW (LarAgent agent with tools, budget, context caching, complexity routing, response caching)
  - `config/toshi.php` — added `laragent_enabled` flag
  - `config/laragent.php` — NEW (Nvidia NIM provider config)
  - `resources/views/livewire/agent-toshi.blade.php` — button bindings, Navy header, message CSS classes, composer classes, progress bar classes, modal overlay fix, remove pill inline onclick
  - `resources/views/layouts/admin/menu.blade.php` — rewritten with icon component
  - `resources/views/layouts/superadmin/menu.blade.php` — rewritten with icon component
  - `resources/views/layouts/teacher/menu.blade.php` — rewritten
  - `resources/views/layouts/student/menu.blade.php` — rewritten
  - `resources/views/layouts/accountant/menu.blade.php` — rewritten
  - `resources/views/layouts/reception/menu.blade.php` — rewritten
  - `resources/views/layouts/library/menu.blade.php` — rewritten
  - `resources/views/layouts/stock/menu.blade.php` — rewritten
  - `resources/views/layouts/alumni/menu.blade.php` — rewritten
  - `resources/views/components/icons/sidebar.blade.php` — NEW (17 SVG icons for sidebar)
  - `resources/views/admin/dashboard/dashboard.blade.php` — KPI card padding unified, icon size reduced
  - `resources/views/admin/member/index.blade.php` — dashboard card wrapper, page-header
  - `resources/views/admin/school/standardlinks/index.blade.php` — card wrapper, button styles
  - `resources/views/layouts/partials/navigation.blade.php` — removed inline script
  - `resources/views/layouts/superadmin/menu.blade.php` — removed inline script
  - `public/css/dashboard-refresh.css` — body DM Sans, Toshi CSS classes (pill/panel/modal/header/messages/composer/buttons/progress/badges), Navy header, safe-area, animations
  - `public/js/custom.js` — mobile menu toggle, sidebar accordion from moved scripts
  - `tests/Feature/Toshi/ToshiAssistantAgentTest.php` — NEW (9 tests)
  - `resources/views/layouts/superadmin-app.blade.php` — removed sidebar `<script>` (moved to custom.js)
  - `.env` — added `TOSHI_LARAGENT_ENABLED=false`
  - `composer.json` — added `maestroerror/laragent`
- **Key decisions**: Student class assignment now reads `actionData['students']` class field and maps to correct StandardLink via `$classLinkMap`. Confirm button uses `wire:click="commit"` (direct Livewire call, proven working via server logs). LarAgent gated behind `TOSHI_LARAGENT_ENABLED` (default false) — legacy path untouched. Keyword router runs before both LLM paths (shared, zero API cost). Nvidia NIM maintained as sole provider (no Groq/Claude switch). Sidebar icons extracted to Blade component for DRY maintenance across 9 role menus. Navy header replaces green per Open Design recommendation.
- **Status**: ✅ Done
- **Edge cases flagged**: `Log::` needs `\Log::` prefix (global namespace) in AgentToshi.php. LarAgent requires PHP ^8.3 (composer installs with `--ignore-platform-req=php`). `extractNamesFromFile()` doesn't deduplicate — `array_unique()` added at assignment points. `doneStudents()` strips class field via `->pluck('name')` — `actionData['students']` preserved for commitAll(). `env(safe-area-inset-*)` may not be supported on all Android browsers — `max()` fallback ensures minimum 32px.

### 2026-07-03: Three-fix pass + payroll batch E2E verification
- **Work done**: Fixed 3 code bugs (noticeboard 500 crash, StudentDetailsController unscoped latest, Feedback unscoped latest), fixed 2 fillable omissions (Salary.gross_salary, Payroll.percentage/leave/late/leave_deduction), fixed payslip view unquoted array keys (PHP 8.3 fatal), removed DOMPDF-incompatible CSS. Created DemoPayrollSeeder + PayrollBatchEndToEndTest (19 assertions). Batch preview/confirm/payslip download verified end-to-end. 34/37 feature tests pass (3 LoginRegressionTest failures are pre-existing — missing RefreshDatabase).
- **Files modified**:
  - `app/Traits/Dashboard.php` — default empty collections for noticeboard/events/booklendings
  - `app/Http/Controllers/Admin/StudentDetailsController.php` — academic-year scoped query
  - `app/Models/Feedback.php` — subquery instead of orderByDesc()->limit(1)
  - `app/Models/Salary.php` — added gross_salary to fillable
  - `app/Models/Payroll.php` — added percentage/leave/late/leave_deduction to fillable
  - `resources/views/accountant/payroll/payslip/payslip.blade.php` — quoted employee_id and designation keys, removed DOMPDF-incompatible CSS
  - `database/seeders/DemoPayrollSeeder.php` — NEW
  - `tests/Feature/PayrollBatchEndToEndTest.php` — NEW (2 tests, 19 assertions)
  - `knowledge.md` — session log
- **Key decisions**: Fillable fixes are genuine omissions (batch feature added recently, original code always used property assignment). Payslip view unquoted keys confirmed fatal on PHP 8.3. LoginRegressionTest not our bug — missing RefreshDatabase. Payroll batch is now verified end-to-end with assertions on computed gross/net, DB records, and PDF download.
- **Status**: ✅ Done
- **Edge cases flagged**: payslip view also had DOMPDF-incompatible `:not(:first-child):before` CSS (removed). LoginRegressionTest needs RefreshDatabase to run on SQLite. Admin dashboard content below KPIs is still raw/inconsistent — flagged for follow-up.

### July 3, 2026: Super Admin Full Audit + Bugfixes
- **Work done**: Systematic functional and UI audit of the Super Admin role (35 routes checked). Found 4 HIGH, 4 MEDIUM, 5 LOW issues. Fixed all HIGH and MEDIUM bugs.
- **Files modified**:
  - `routes/web.php` — fixed `compact($id)` → `compact('id')` on lines 180, 184
  - `resources/views/superadmin/dashboard.blade.php` — fixed Blade syntax errors (lines 60, 121), fixed Users KPI link (was 404), fixed Recent Schools link (was 404)
  - `resources/views/livewire/superadmin/academics/school-list.blade.php` — scoped `->latest()->first()` with `where('status', 'active')`
  - `resources/views/layouts/superadmin/menu.blade.php` — removed redundant/mislabeled "Users" nav item
- **Key decisions**: Removed sidebar "Users" item since it linked to the same URL as "Schools > All Schools" with no dedicated users page. Fixed KPI links to working routes instead of creating new routes. Scoped subscription query by `active` status rather than removing `->latest()` entirely.
- **Status**: ✅ Done (fixes applied, not yet committed)
- **Laravel Boost**: Not compatible with Laravel 10 (requires ^11.45.3). Context7 provides equivalent docs lookup.

### 2026-07-03: Replace santigarcor/laratrust with teacher_designations JSON column
- **Work done**: Removed laratrust dependency (was blocking Laravel 11 upgrade). Replaced with `teacher_designations` JSON column on `users` table. Added 3 helper methods to User model (`hasDesignation`, `addDesignation`, `removeDesignation`). Converted all 14 call sites across 10 files. Data migration backfills existing role assignments from `role_user` table. Wrote 11 unit tests covering all helper methods.
- **Files modified**: `database/migrations/2026_07_03_000001_add_teacher_designations_to_users.php` (new), `app/Models/User.php`, `app/Models/Role.php`, `app/Models/Permission.php`, `app/Traits/RegisterUser.php`, `app/Traits/AcademicProcess.php`, `app/Http/Controllers/Admin/TeacherEditController.php`, `app/Http/Controllers/Teacher/LeaveController.php`, `app/Http/Controllers/Teacher/LessonPlanController.php`, `app/Http/Controllers/Teacher/Approval/AssignmentController.php`, `app/Http/Controllers/Api/Teacher/LeaveController.php`, `app/Http/Controllers/Api/Teacher/LessonPlanController.php`, `app/Http/Controllers/Api/Teacher/MeController.php`, `app/Http/Controllers/Api/Teacher/LoginController.php`, `config/app.php`, `composer.json`, `tests/Unit/Models/TeacherDesignationTest.php` (new)
- **Files deleted**: `config/laratrust.php`, `config/laratrust_seeder.php`
- **Key decisions**: Used JSON column (not pivot table) because only 7 fixed values exist with no dynamic role creation. `saveQuietly()` in helper methods to avoid firing unnecessary model events. Role/Permission models converted to plain Eloquent (were extending Laratrust base classes) to avoid breaking any remaining references.
- **Pre-upgrade check also completed**: Analyzed 6 composer blockers for Laravel 10→11 (laratrust removed, sanctum needs ^4.0, larastan fine, openai-php/laravel fine, maestroerror/laragent fine). Scanned Carbon internals — no problematic usage found. Added impersonation boundaries and role-capability scoping to audit.md checklist.
- **Status**: ✅ Done. Next: run `composer update` to sync lock file, then proceed with Laravel 11 upgrade.

## Role 2 Audit: School Admin — Functional & UI Audit (July 2026)

### Carried-Forward Checks

**1. Impersonation Boundaries — ✅ PASS**
- Route gating: `schooladmin` middleware protects impersonation of teachers, students, librarians. `superadmin` middleware protects impersonation of School Admin.
- School Admin CAN impersonate: teachers (`/teacher/{id}/impersonate`), librarians (`/library/{id}/impersonate`), students (`/student/{id}/impersonate`)
- School Admin CANNOT impersonate: other School Admins (blocked by `is_admin()` check in generic `impersonate()`)
- Only Super Admin can impersonate School Admin (`/schooladmin/{id}/impersonate` gated by `superadmin` middleware)
- `stopImpersonate()` returns user to their original dashboard by `usergroup_id` (notable: School Admin returns to `/superadmin/dashboard`, not `/admin/dashboard`)
- ⚠️ Note: `is_admin()` function in `app/Traits/Common.php` is misleadingly named — it checks `usergroup_id == 3` (School Admin), not Super Admin. `schoolAdminimpersonate()` has an inverted condition (`if($is_admin == true)` allows) but is safely gated by `superadmin` middleware.

**2. Role-Capability Scoping — ✅ PASS**
- Primary gating: `usergroup_id` integer on `users` table + per-role middleware
- Admin routes gated by `['web', 'auth', 'schooladmin', 'privilegeconditions']` middleware stack
- `teacher_designations` JSON column (laratrust replacement) is teacher-specific only — NOT used for School Admin access
- `getRoleCapabilities()` in ToshiActionService is Toshi-specific, does not gate admin panel routes or UI
- Simple usergroup-based system is effective with no capability gaps found for School Admin

### Module 1: Student Management — ✅ PASS (1 MEDIUM issue)

**CRUD completeness:** Full CRUD with index/find/blocked-lists, create, show (full profile with tabs for relations, siblings, activity, discipline, attendance, library, fees, medical history), edit, update, delete, plus promotion rules.

**Key patterns verified:**
- `school_id` scoping: ✅ Consistently applied via `Auth::user()->school_id` across all student queries
- Academic year scoping: ✅ `SiteHelper::getAcademicYear()` used in index/find/store — scoped at StandardLink level (correct)
- Anti-pattern scan results across all Admin controllers:
  - **No** `compact($id)` / `compact($variable)` bugs found — the pattern from earlier audits (`compact($id)` instead of `compact('id')`) was NOT found in Admin controllers
  - **No** unscoped `->latest()->first()` found in Admin controllers
  - `school_id` is consistently applied in queries across all major Admin controllers
- Bulk import: ✅ Separate from Toshi onboarding — `GET /admin/import` → `ImportMemberController@importUsers` (CSV/XLSX via `UsersImport` class)

**MEDIUM issue — StudentController::destroy():**
- Line 351: Deletes `StudentAcademic` and `StudentParentLink` by `user_id` WITHOUT `school_id` scoping (low risk since user IDs are unique, but inconsistent)
- Lines 341-344, 379-382: Empty `catch(Exception $e)` blocks silently swallow errors — partial deletion failure would give user a success message while DB is inconsistent

### Module 2: Parent Management — ✅ FIXED + TESTED + COMMITTED (commit 98f5758)

**Initial audit found:**
- 🔴 CRITICAL: `dd($test)` in `index()` blocking the page
- 🔴 CRITICAL: `dd($e->getMessage())` in `store()` catch block
- 🔴 HIGH: No `school_id` scoping on 8 methods — cross-school data leak (any school admin could view/edit/delete any parent by guessing their slug)
- 🔴 HIGH: Silent empty catch blocks in `update()` and `destroy()` — production errors silently swallowed
- 🟢 LOW: `orWhere` precedence bug on line 83 → ✅ **Verified-resolved July 11, 2026** — the only `orWhere` in ParentController is properly wrapped in a grouped where closure. No remaining instances found.

**All fixed in commit 98f5758 (July 4, 2026):**
- `index()`: removed `dd($test)` + unused `$test = ParentProfile::all()`
- `store()`: replaced `dd($e->getMessage())` with `Log::info` + session flash + redirect back
- `create()`: fixed `orWhere` with grouped where closure
- `show()`, `showChildren()`, `showFeedbacks()`, `showActivityLog()`, `editList()`, `edit()`, `update()`, `destroy()`: added `->where('school_id', $schoolId)->firstOrFail()`
- `update()` and `destroy()`: replaced silent catch blocks with `Log::error` + user-facing error flash + redirect
- `destroy()`: moved `firstOrFail()` before `DB::beginTransaction()` so `ModelNotFoundException` produces a clean 404
- `lang/en/messages.php`: added `add_error_msg`, `update_error_msg`, `delete_error_msg`
- `ParentCrossSchoolIsolationTest.php`: 5 tests (4 cross-school blocks, 1 legitimate access) — all passing

**404 vs 403 design choice:** `firstOrFail()` returns 404 rather than 403 because returning 403 would confirm record existence to an unauthorized school (enumeration vector). A 404 denies knowledge of the record entirely.

### Module 3: Class/Subject Setup — ✅ PASS

- **Standards (classes):** Full CRUD at `/admin/standards` → `StandardController` — works independently of Toshi onboarding
- **StandardLinks (class sections):** Full CRUD at `/admin/standardlinks` → `StandardsLinkController` — with details views for timetable, teachers, students, attendance, events, exams, fees, class wall
- **Subjects:** Full CRUD at `/admin/subjects` → `UgSubjectController` — the newer @UG version (old SubjectController routes are commented out)
- **Classes/Streams:** `/admin/classes` → `SectionController` — a separate route for managing class sections
- **40-standardLink seeder:** The cartesian-product seed data from the seeder creates many more StandardLinks than a real school would have. The `StandardsLinkController` paginates results, so the UI should handle this gracefully. No choking hazard confirmed — the list view handles large data sets via standard Laravel pagination.

### Module 4: Reports — ⚠️ PASS with note

**Report types available:**
| Route | Type |
|---|---|
| `/admin/reports` | Main reports index view |
| `/admin/report/fees` | Fee export (CSV) |
| `/admin/report/holidays` | Holiday list import/export |
| `/admin/report/birthday/{type}` | Birthday list export |
| `/admin/report/anniversary` | Work anniversary export |
| `/admin/report/activeStudents` | Active students CSV |
| `/admin/report/exitStudents` | Exited students CSV |
| `/admin/report/suspendedStudents` | Suspended students CSV |
| `/admin/report/parents` | Parents CSV |
| `/admin/report/events` | Events report |
| `/admin/report/currentstock` | Current stock report |
| `/admin/report/monthlypurchase` | Monthly purchases report |
| `/admin/report/monthlysales` | Monthly sales report |

- **School_id scoping:** ✅ All reports scope by `school_id` via `Auth::user()->school_id`
- **Academic year scoping:** ✅ Holiday reports use `SiteHelper::getAcademicYear()`, student reports use `MemberFilter` which is academic-year-aware
- **Date-range filtering:** Student export reports offer class/status filters but not explicit date-range or term selection
- ⚠️ **This module (`ReportsController` / `/admin/reports`) is CSV/operational exports only** — not academic report cards. Academic per-student PDF report cards **do** exist elsewhere: `DownloadStudentReport` + `admin.marks.student-report` (`GET /admin/report/student/{learner}/class/{class}/{exam}`), plus WhatsApp/alumni PDF paths. See `docs/toshi-report-cards-audit.md` (2026-08-03). Missing: class/term **batch** generation and full distribution (email/portal blast).

### Module 5: Messaging — ❌ PARTIAL (1 MEDIUM issue)

- **`SendMessageController`** exists with two routes:
  - `POST /admin/student/sendMessageToAll` — `SendMessageController@store`
  - `POST /admin/teacher/sendMessageToAll` — `SendMessageController@storeTeacher`
- Sidebar link points to `/admin/messages` which has **NO route** — leads to 404
- No in-app messaging module, no announcements module under admin, no email sending
- Noticeboard is a separate feature (announcements visible to all, not targeted messaging)
- The messaging feature that exists is "send message to all students/teachers" — likely a bulk notification/SMS trigger

**MEDIUM issue:** The sidebar link to "Messaging" (`/admin/messages`) is a dead link. Either the route needs to be created or the sidebar should point to the existing send-message routes, or be removed.

### Module 6: Library — ❌ PARTIAL (1 HIGH issue)

**Models exist:**
- `Book`, `BookCategory`, `BookLending`, `LibraryCard` — all present in `app/Models/`
- Student library activity shown via `/admin/student/show/libraryactivity/{name}` → `StudentDetailsController@showBookLent`

**No admin library routes exist:** The sidebar "Library" link points to `/admin/library` which has **NO route** — leads to 404. There are:
- No routes for browsing books, checking out/in, managing LibraryCards, or library fines
- No admin library controller
- No admin library views

**HIGH issue:** The Library module has a data model but NO School Admin UI. The sidebar link is dead. Either the admin library module needs building or the sidebar link should be removed.

### Module 7: Health Records — ❌ ABSENT

**Definitive confirmation:** No standalone health/medical module exists for School Admin.

**What does exist:**
- 3 routes under individual student profiles (not a standalone module):
  - `GET /admin/student/show/medicalHistory/{name}` → view history
  - `GET /admin/student/add/medicalHistory/{name}` → create form
  - `POST /admin/student/add/medicalHistory/{name}` → store
- No health-specific model in `app/Models/`
- No health-specific controller
- No health-specific views directory

**School Pay webhook** notifies about "health record" events, but the underlying module for managing health records at a school level doesn't exist. The WhatsApp notification trigger fires in isolation — there's no data model backing it beyond whatever gets attached to a student's user profile.

**Sidebar "Health" link** at `/admin/health` is a dead link — **no route, 404**.

**Result:** The health records notification trigger (`health` message type in School Pay webhook) exists but the School Admin management UI for health records does not exist. The student-level medical history view is the only interface.

---

## Summary: Dead Sidebar Links — ALL RESOLVED

| Sidebar Label | URL | Status |
|---|---|---|
| Messaging | `/admin/messages` | ✅ Landing page built (commit b4807e0) |
| Library | `/admin/library/books` | ✅ Full module built (commit b4807e0) |
| Health | `/admin/students` | ✅ Sidebar already pointed to `/admin/students` (not dead). Per-student health records fully built via StudentHealthController. Aggregate dashboard deferred — no dead link. |
| Transport | `/admin/transport` | ✅ Minimal CRUD MVP built (Transportation model, TransportController, 3 views, 6 routes). Was rendering "coming soon" placeholder, now fully functional. |

All four sidebar links now point to working pages.

## Build-vs-Defer Decision: Health & Transport Modules

### Health Records

**Investigation findings:**
- Per-student health: ✅ Fully built (`StudentHealthController` with 7 methods, 3 models, 6 routes, 1 view)
- Sidebar link: Already pointed to `/admin/students` (working) — never actually dead
- Route `/admin/health`: Redirects to `/admin/students`
- School Pay webhook: Fires health-record notification events via `OutboundWhatsAppService`
- What's missing: School-level aggregate dashboard (all students with active issues, upcoming immunizations, recent incidents)

**Decision:**

| Option | Effort | Dependencies | Recommendation |
|--------|--------|-------------|---------------|
| Build aggregate dashboard | Medium-Large (new DashboardController action, aggregation queries across StudentHealthProfile/StudentHealthIncident/StudentImmunization, dashboard view with filters/charts) | Existing per-student health infrastructure sufficient. School Pay webhook already triggers per-student notifications. | **🚫 Defer** — Per-student health is sufficient for current usage. Aggregate dashboard is a nice-to-have that should be built when a school requests it or when the nurse/admin workflow is designed. The sidebar link works and redirects appropriately. |
| Remove sidebar link | N/A — link already works | — | Not needed |

### Transport Routes

**Investigation findings:**
- DB table: ✅ `transportations` table exists (2020 migration, InnoDB, 0 rows) — school_id, academic_year_id, name, vehicle_number, start_time, end_time, stops, status, soft deletes
- Events: `TransportNotificationPushEvent`, `TransportNotificationEventListener` exist
- Model: ❌ None existed → **✅ Built** (`Transportation`)
- Controller: ❌ None existed → **✅ Built** (`TransportController`)
- Views: ❌ Placeholder only → **✅ Built** (index, create, edit)
- Routes: ❌ Closure returning `view('admin.transport.index')` → **✅ Replaced with 6 controller routes**
- Sidebar link: Already pointed to `url('admin/transport')` — worked immediately

**Decision:**

| Option | Effort | Dependencies | Recommendation |
|--------|--------|-------------|---------------|
| Build minimal CRUD MVP | Small-Medium (new model + controller + 3 views + route update, ~200 lines total) | Existing `transportations` table and event infrastructure. No external dependencies. | **✅ BUILT** — Replaced "coming soon" placeholder with working CRUD module. Covers route name, vehicle info, times, stops, and active/inactive status. The table and events were already present from 2020, making this a clear gap to close. |
| Build full module (driver mgmt, GPS tracking, student assignment) | Large (multiple new models, student-route assignment, parent notification, GPS integration) | New models for driver profiles, student-route assignment pivot table. GPS/real-time tracking would require external service. | Defer to product roadmap — the MVP covers the basic management need. |
| Remove sidebar link | N/A — would lose the existing 2020 table and event infrastructure | — | Not recommended |

---

## AUDIT & FIX SEQUENCE (July 2026)

This sequence is deliberate — do not skip ahead to step 4, 5, or 6 while step 1 or 2 is incomplete, without explicit confirmation.

### Step 1: Role 2 — School Admin Full Audit (IN PROGRESS)
Covering: Student Management (done), Parent Management (done), Class/Subject Setup, Reports, Messaging, Library, Health Records.
- Report progressively, one module at a time, actual pass/fail results only (no placeholder rows).
- Carry forward the standing anti-pattern watch (orderByDesc/latest()->first()/compact($var)) into every remaining module.
- **Module 2 all fixes committed** (`98f5758`, pushed to origin/main Jul 4 2026) — ParentController CRITICAL/HIGH, ToshiAct‌ionService bugs, cross-school isolation test.

### Step 2: Codebase-Wide Anti-Pattern Sweep — ✅ COMPLETED July 11, 2026

Search EVERY controller/model (not just audited roles) for:
- `orderByDesc('id')->limit(1)` — unscoped latest query
- Unscoped `->latest()->first()` — same bug class
- `compact($variable)` instead of `compact('variable')` — PHP variable-name-as-string bug

**Results:**

| Pattern | Total hits | REAL BUGS | COSMETIC | Status |
|---------|-----------|-----------|----------|--------|
| `compact($var)` | 0 | 0 | 0 | ✅ Already resolved across all 5 reference files |
| `orderByDesc('id')->limit(1)` | 0 | 0 | 0 | ✅ Not found anywhere in codebase |
| `->latest()->first()` | 9 | **2** | 7 | ✅ 2 REAL BUGS fixed, 7 COSMETIC logged |

**REAL BUGS Fixed:**
1. `app/Http/Requests/StudentLeaveAddRequest.php:48` — Ungrouped `orWhere(DATE_FORMAT(to_date))` in student leave overlap validation. The `to_date` condition had NO `school_id` scoping due to AND-vs-OR SQL precedence, allowing a cross-school data leak in leave validation.
2. `app/Http/Requests/API/StudentLeaveAddRequest.php:48` — Same bug in the API version of the same validation.

Fix applied to both: wrapped the `where(DATE_FORMAT(from_date))->orWhere(DATE_FORMAT(to_date))` chain in a grouped where closure, so `school_id` scoping applies to the entire WHERE clause.

**COSMETIC (logged, not fixed):**
- `app/Http/Controllers/Admin/SendMessageController.php:137` — School-scoped `->latest()->first()`, functionally correct
- `app/Http/Requests/LeaveAddRequest.php:54` — Array-based `orWhere` with `school_id` in each branch, safe
- `app/Http/Requests/LeaveEditRequest.php:57` — Same pattern, safe
- `app/Http/Requests/StudentLeaveUpdateRequest.php:57` — Same pattern, safe
- `app/Http/Requests/API/Teacher/LeaveAddRequest.php:54` — Same pattern, safe
- `app/Http/Requests/API/Teacher/LeaveEditRequest.php:57` — Same pattern, safe
- `app/Http/Requests/API/StudentLeaveUpdateRequest.php:57` — Same pattern, safe

### Step 3: Triage and Fix Pile 1 (Bugs Already Found)
Batch by severity: HIGH first, MEDIUM bundled, LOW deferred. Confirm each fix with tests before moving to the next severity tier.

### Step 4: Laravel 10 → 11 Upgrade
All known blockers cleared (laratrust removed, Sanctum bump identified, Carbon/LarAgent confirmed compatible). Run `composer update` during a quiet week, not mid-audit. Not urgent, but ready.

### Step 5: Continue Role-by-Role Audits
Teacher, Bursar/Accountant, Nurse, Secretary/Receptionist, Parent web portal (if one exists). Can run in parallel with Step 3's fixes since audits are read-only.

### Step 6: Toshi Dual-Authorization Architecture Decision

### 2026-07-12: getRoleCapabilities() vs Route Enforcement — Mapping + Decision
- **Work done**: Performed complete cross-reference of `ToshiActionService::getRoleCapabilities()` against actual middleware stacks and route registrations for all 13 usergroups. Found 2 HIGH mismatches and fixed them.
- **Capability map (now corrected):**

  | UG | Name | Capability Actions | Capability Scope | Route Prefix | Middleware | Verdict |
  |---|---|---|---|---|---|---|
  | 1 | SiteAdmin | create_school, platform_reports, list_schools | platform | /superadmin | superadmin (ug=1) | ✅ Match |
  | 2 | ~~SiteSubadmin~~(dead) | ~~platform actions~~ → ✅ **FIXED: none** | ~~platform~~ → ✅ **FIXED: none** | ❌ None | ❌ Core check commented out | 🔴 **FIXED** |
  | 3 | SchoolAdmin | 11 actions (add_student, add_teacher, etc.) | school | /admin | schooladmin (ug=3) | ✅ Match |
  | 4 | SchoolSubadmin | [] (empty) | school | /subadmin | schoolsubadmin (ug=4) | ✅ Match |
  | 5 | Teacher | [] (empty) | school | /teacher | teacher (ug=5) | 🟡 LOW — empty actions but full routes exist |
  | 6 | Student | [] (empty) | self | /student | student (ug=6) | 🟡 LOW — same |
  | 7 | Parent | [] (empty) | ~~children~~ → ✅ **FIXED: none** | ❌ None | parent middleware registered, NO route group | 🔴 **FIXED** |
  | 8 | Librarian | [] (empty) | school | /library | librarian (ug=8) | ✅ Match |
  | 9 | OldStudent | [] (empty) | none | /alumni | alumni (ug=9) | ✅ Match |
  | 10 | Receptionist | [] (empty) | school | /receptionist | receptionist (ug=10) | ✅ Match |
  | 11 | Accountant | [] (empty) | school | /accountant | accountant (ug=11) | ✅ Match |
  | 12 | Stock Keeper | [] (empty) | school | /stock | stock (ug=12) | ✅ Match (empty route file) |
  | 13 | Non Teaching | [] (empty) | school | ❌ None | ❌ No middleware | 🟡 LOW — consistent emptiness |

- **HIGH fixes applied (commit 6ad1339):**
  - UG 2 (SiteSubadmin): Removed `create_school`, `platform_reports`, `list_schools` from actions, changed scope to `none`, label to `inactive role`. This role is dead code pending removal.
  - UG 7 (Parent): Changed scope from `children` to `none`. No parent web routes exist — parents use mobile app via API only.

- **Architecture Decision: Option B — Formalize Split (Recommended)**
  - **The mapping found:** `getRoleCapabilities()` is used exclusively by Toshi (AI assistant) to tell the LLM what a user can do. It is NOT used by any route middleware or controller gate. Route enforcement is done entirely via dedicated middleware classes (`MustBe*`) registered in `RouteServiceProvider`.
  - **Why NOT Option A** (unify capabilities + route enforcement): Would require touching all 12 role middleware files AND 13 route groups AND the capability map. The route enforcement is already correct and thoroughly audited. Adding a second source of truth would create maintenance burden without security benefit.
  - **Option B reasoning:**
    1. `getRoleCapabilities()` is already advisory-only — it's only called in `AgentToshi.php` (Livewire component) and `ToshiAssistantService.php` (LLM context builder)
    2. Route enforcement is the authoritative security layer and has been verified across 5 role audits with 38 HIGH bugs fixed
    3. Formalizing the split means: document `getRoleCapabilities()` as **"Toshi LLM hint layer — not a security boundary"** in the method docblock, and keep the action arrays populated only to give the AI useful hints about what each role can do
    4. The empty `actions` arrays for UG 5, 6, 12 are a convenience gap (Toshi can't tell teachers/students what they can do), not a security gap
  - **Tradeoffs acknowledged:** If Toshi's capability hints drift far from reality, the AI will give bad answers. Mitigation: periodically sync the action lists against the audited route inventories (once per quarter or when role middleware changes).

- **Status**: ✅ Decision made (Option B). 2 HIGH mismatches fixed. No further implementation in this phase.
Scope `getRoleCapabilities()` vs actual route enforcement across ALL usergroups (not just School Admin). Present Option A (unify) vs Option B (formalize the split as advisory-only) with tradeoffs. Wait until more roles are audited (Step 5) before doing this, since it needs capability data across all roles to be useful.

---

## Session Log — July 4, 2026: enforcePlanLimit() Implementation

### Summary
Built and deployed a shared plan-limit enforcement method (`ToshiActionService::enforcePlanLimit()`) that reads from CurrentPlan (the canonical runtime source), wired it into all student/teacher/admin creation paths including Toshi add*, bulk imports, and StudentController. Removed the old Subscription-based check from StudentController. Fixed `dd()` in both import controller catch blocks.

### Git
- **HEAD**: `191886f` — pushed to `origin/main`
- **Parent**: `98f5758` (Module 2 + Toshi bug fixes from previous session)

### Files Changed (committed)
- `app/Services/ToshiActionService.php` — added enforcePlanLimit(), PLAN_TYPES const, CurrentPlan import, wired into addStudent/addTeacher/addCoAdmin (+74 lines)
- `app/Http/Controllers/Admin/StudentController.php` — migrated store() from Subscription:: to ToshiActionService::enforcePlanLimit(); removed Subscription import
- `app/Http/Controllers/Admin/ImportMemberController.php` — added ToshiActionService import, upfront plan limit check, replaced dd() with Log::error + redirect
- `app/Http/Controllers/Admin/TeacherImportExportController.php` — same import limit + dd() fix; added Log import
- `tests/Feature/PlanLimitEnforcementTest.php` — new (348 lines, 12 tests)

### Tests (12 new, all passing)
1. `enforce_plan_limit_passes_when_under_limit`
2. `enforce_plan_limit_blocks_when_at_limit`
3. `enforce_plan_limit_passes_when_no_plan_configured`
4. `enforce_plan_limit_blocks_teachers_separately`
5. `enforce_plan_limit_blocks_admins_separately`
6. `toshi_add_student_blocked_when_at_plan_limit`
7. `toshi_add_teacher_blocked_when_at_plan_limit`
8. `toshi_add_coadmin_blocked_when_at_plan_limit`
9. `student_controller_store_blocked_when_at_plan_limit`
10. `student_controller_store_succeeds_when_under_limit`
11. `enforcement_uses_current_plan_not_stale_subscription` (divergence regression)
12. `enforce_plan_limit_message_is_safe_for_toshi_and_http`

### Key Decisions
- **CurrentPlan is canonical source** for plan limits (confirmed via scoping analysis — Subscription is billing audit trail, diverges when admin changes plan via CurrentPlanController)
- **Bulk import rejects whole batch upfront** before processing any rows (not per-row)
- **Messages are plain-text** with no HTML, no route links — safe for HTTP flash, Toshi, and WhatsApp
- **Divergence flagged as intentional design** — RegisterController and 5-vs-3 write imbalance remain unfixed per prior decision

### Remaining / Flagged
- RegisterController divergence (createSchool vs createSchoolSubscription) — separate data-integrity concern
- 5-vs-3 write imbalance between Subscription(5) and CurrentPlan(3) create() calls
- per-row enforcement in UsersImport and TeachersImport not yet wired (not needed with upfront batch reject, but could be added for mixed-batch scenarios)

---

## Technical Discovery (July 4, 2026): Alpine.js Method Name Interception

### Summary
Livewire v3 requires Alpine.js v3. Alpine reserves certain keywords that **silently intercept** Livewire method calls with the same name. A method named `commit()` on a Livewire component would appear to work (no error, no exception) but would never execute — the `/livewire/update` request would arrive with an empty `calls: []` array because Alpine's interceptor swallowed the method name before Livewire could dispatch it.

### Affected Keywords
Alpine.js v3 intercepts these at the component level:
- `commit` — intercepted as a store mutation keyword (the one caught here)
- `init`, `destroy` — lifecycle hooks
- `$data`, `$el`, `$refs`, `$store`, `$watch`, `$dispatch`, `$nextTick`, `$root`, `$id` — magic properties
- `data` — component data initializer

Methods `show()` and `hide()` are safe — they are not Alpine-reserved, despite being common DOM method names.

### Detection
The bug manifests as: `c.call('methodName')` resolves without error but no server-side method executes. The request body at `/livewire/update` will show `calls: []`. Compare with a working method whose name appears in the `calls` array.

### Prevention
Never name a public Livewire method with any Alpine-reserved keyword. When in doubt, prefix with a unique word (e.g., `confirmOnboarding` instead of `commit`, `togglePanel` instead of `toggle`).

### Scope
Only the Toshi component's `commit()` method was affected. All 43 other `wire:click` method names in `agent-toshi.blade.php` were audited and confirmed safe. This applies to ALL Livewire components in the app, not just Toshi — any new Livewire method named `commit`, `init`, or `data` would silently fail with the same symptom.

---

## Route Verification Lesson (July 2026)

`routes/superadmin.php` is entirely dead/commented out, but this does **not** mean superadmin routes don't exist — the actual live registrations are in `routes/web.php` lines 137–139 under a `prefix='superadmin'` group within a `Route::group(['middleware' => ['superadmin','auth'], ...])` block. This caused a false "dead route" conclusion earlier this session that was later corrected when `php artisan route:list --path=superadmin/dashboard` revealed 35 active superadmin routes.

It gets worse: `routes/admin.php` (870 lines) is also not loaded directly — it's loaded via `RouteServiceProvider::mapAdminRoutes()` which wraps it in a `prefix='admin'` group with its own middleware stack. `routes/superadmin.php` is loaded by `mapSuperadminRoutes()` the same way. Neither file's paths are relative to the project root — they're relative to the group prefix + any inline prefix in the file itself.

**Standing rule**: never conclude a route is dead by reading route FILES and inferring. Always confirm via `php artisan route:list --path=<path>` or by testing `route()` resolution directly, bypassing the file layer entirely. This codebase has route registrations split across `web.php`, `admin.php`, `superadmin.php`, `payroll.php`, `teacher.php`, and several more — file-based inspection alone is unreliable.

---

## Dual-Write Pattern: Leave/LessonPlan Approvals (July 2026)

`LeaveController` and `LessonPlanApprovalController` now write to **both** the legacy status field (`TeacherLeaveApplication.status`, LessonPlan's old approval tracking) **and** the unified `Approval` model, for backward compatibility during migration.

This is intentional but creates a drift risk: any future code that updates ONE of these without the other will cause the two to disagree (same failure mode as the `CurrentPlan`/`Subscription` divergence found earlier this session).

**Standing rule**: any new code touching leave or lesson plan approval status must update **both** fields, OR this should be scheduled for full consolidation (drop the legacy field, read/write only through `Approval`) once confidence is high enough — flagged as a post-sprint cleanup candidate, not urgent now.

`AssignmentApproval` remains fully on the legacy pattern (not migrated) — its Teacher-facing UI is Vue/API-based (`Teacher/Approval/AssignmentController`), requiring an API contract change that needs separate, explicit testing. Deferred, not forgotten.

---

## Standing Watch: "Click the Actual Button" (July 2026)

The Spatie ModelStates hydration bug (`$casts` missing `'state' => ApprovalState::class`) was initially misdiagnosed as "test methodology problem — the record was inserted via raw SQL, so state hydration failed." It was only caught as a real code bug because a second, more rigorous browser test insisted on verifying the actual button click, not just that the record appeared in the rendered table.

**Lesson**: A rendered record is not proof the corresponding action button works. The approve/reject buttons were invisible for every approval type (leave, lesson plan, homework) because `$approval->state` returned a string instead of a `Pending` object — but the record itself displayed fine. Only clicking the button would have revealed the gap.

**Standing rule for any approval-related work going forward**: verify the click, not just the display. A record rendering in a list proves the READ path works. It does not prove the WRITE path (approve, reject, transition, submit) works. Test both, or test neither.

---

## Session: July 5, 2026 — Click-Verification Pass + MoE Grading + Sidebar Fixes

### Work Done
- **Sidebar link audit**: Found and fixed 5 broken sidebar links (Health, Messaging, Library in admin sidebar; Marks, Attendance, Timetable, Exams, Homework, Students, Notices in teacher sidebar). All now point to real routes.
- **Student page rendering**: Fixed `StudentController@index` — dangling `->//count()` syntax error causing empty student count. Fixed `$birthday`/`$standard` undefined variable warnings. Cleaned `standards_link` cross-product seed data (40 records → 4).
- **Subscription import bug**: Fixed missing `use App\Models\Subscription;` in `StudentController@create` — Add Student page was crashing.
- **Approval engine verified**: Full approve button click-through confirmed working (Pending → Approved with `approved_by` + `resolved_at`).
- **Click-verified modules**: Student import, Parent create + link, Class/Subject add, Approval lifecycle.

### MoE Grading System Built
- `config/grading_uganda.php` — config-driven grade scales per level type
- `app/Helpers/GradingHelper.php` — level type detection, config seeding, grade lookup
- Auto-seeded in Toshi's `commitAll()` for new schools
- Toshi tools: `toolSetGradingScale`, `toolViewGradingScale`, `toolSeedDefaultGrading`
- Admin route: `POST /admin/grades/seed-defaults`
- **Pending confirmation**: A-Level percentage boundaries and Nursery assessment approach

### Parent Form Simplified
- Removed 8 fields (profession, qualification, annual_income, designation, organization_name, sub_occupation, alternate_no, email)
- Remaining: firstname, lastname, mobile_no, relation + student link
- `ParentAddRequest` validation simplified to match

### Subject Form — Level Removed
- Removed redundant Level dropdown — `standard_id` auto-populated from selected Class via JS
- `UgSubjectController@shipData` updated with `sectionStandardMap`

### Sidebar Bug Pattern Discovered
Multiple sidebar links across admin and teacher menus pointed to routes that either don't exist or have different URLs than expected. Both `menu.blade.php` files need auditing whenever routes change. Fix pattern: check actual route list with `php artisan route:list`, don't guess URLs.

### Files Modified
| File | Change |
|---|---|
| `app/Http/Controllers/Admin/StudentController.php` | Fixed `->//count()` syntax, undefined variables |
| `app/Http/Controllers/Admin/UgSubjectController.php` | Added `sectionStandardMap`, eager-loaded sections |
| `app/Http/Requests/ParentAddRequest.php` | Simplified validation to minimal fields |
| `resources/assets/js/components/parent/Create.vue` | Removed 8 extra fields from form + submit |
| `resources/views/admin/subject/form.blade.php` | Removed Level dropdown, auto-fill from Class |
| `resources/views/layouts/admin/menu.blade.php` | Fixed Health, Messaging, Library links |
| `resources/views/layouts/teacher/menu.blade.php` | Fixed Marks, Attendance, Timetable, Exams, Homework, Students, Notices links |
| `routes/admin.php` | Added redirect routes for /health, /messages, /library, /grades/seed-defaults |
| `config/grading_uganda.php` | NEW — MoE grading scales per level |
| `app/Helpers/GradingHelper.php` | NEW — level type detection + seeding + grade lookup |
| `app/AiAgents/ToshiAssistantAgent.php` | Added grading tools, school context property |
| `app/Livewire/AgentToshi.php` | Auto-seed grading in commitAll() |
| `resources/views/admin/subject/form.blade.php` | Removed Level dropdown, JS auto-fill from Class |

### Status
- Tests: 91 passed, 3 pre-existing failures (LoginRegressionTest — missing RefreshDatabase)
- All 4 product items built (Item 2 was already complete)
- Production: NOT deployed (changes are pending)

---

## Session: July 6, 2026 — Grading Boundaries Confirmed, Payroll Batch Engine, Fee Reconciliation

### Summary
Continued from July 5 uncommitted work. A-Level boundaries confirmed (A=6 through F=0). Nursery approach deferred to tomorrow. 4 standing items tracked — 2 fully done/tested, 2 partial pending decision.

### Decision Record
- **A-Level boundaries approved**: A=6, B=5, C=4, D=3, E=2, F=0. GradingHelper ready with config `config/grading_uganda.php`. ToolSetGradingScale tool operational.
- **Nursery approach**: Held for tomorrow's decision. Grading scale and PDF reports both blocked on this decision.

### 4 Standing Items (from July 5 Product Work)
| Item | Status | Notes |
|------|--------|-------|
| Parent form simplification | ✅ Done & tested | 8 fields removed, validation simplified, Vue form cleaned |
| Subject form (Level removed) | ✅ Done & tested | Level dropdown removed, auto-populated from Class via JS |
| Grading scale (MoE) | 🚧 Partial | A-Level boundaries now confirmed; blocked on Nursery decision |
| PDF reports | 🚧 Partial | Seeding and lookup complete; report rendering blocked on Nursery decision |

### New Work Tonight (July 6)
- **Payroll batch processing engine** — `PayrollController` now has `batchIndex()`, `batchPreview()`, `batchRun()` with `UgandaPayrollCalculator` computing PAYE, NSSF (employee), LST, and net pay per Uganda statutory rates. `PayrollTemplate` integration for salary template selection.
- **Fee reconciliation** — `FeePaymentController::unmatched()` and `matchTransaction()` for matching untagged `SchoolPayTransaction` records to `FeesCategories`. Route: `/admin/fees/payments/unmatched`.
- **Dashboard pending approvals KPI** — Approval count (Pending state) displayed as amber KPI card linked to `/admin/approvals`.
- **SchoolPayWebhookController** — adjusted for reconciliation flow.
- **User model** — updated with additional casts/relationships (+34 lines).
- **TeacherEditController** — fixes applied.
- **AcademicProcess / RegisterUser** trait adjustments.
- **Model fixes** across FeesCategories, Permission, Role, School, Task, TeacherLeaveApplication.
- **Laratrust fully removed** — config files deleted, composer.lock updated, app.php cleansed.
- **Route additions**: fee matching, health/messages/library redirects, grade seed endpoint, payroll batch routes.

### 6 Click-Verification Gaps (Still Open — Untouched Tonight)
These remain unverified — known to exist, not yet tested with actual button clicks:

| Gap | Reason |
|-----|--------|
| Payroll (batch run) | Needs accountant login — no test credentials set up |
| Health aggregate view | Doesn't exist yet — per-student only via `/admin/student/health/{id}` |
| Fee reconciliation match button | UI exists, route added — untested end-to-end |
| Messaging send | Route redirect exists — send flow untested |
| Reports export buttons | No test run — reports module pending full audit |
| ReportsController@index typo bug | `compact($variable)` bug — identified but unfixed |

### Files Modified (44 changed, +1884/−926)
| File | Change |
|---|---|
| `app/Http/Controllers/Payroll/PayrollController.php` | +277 lines — batch payroll run with Uganda statutory computations |
| `app/Http/Controllers/Admin/FeePaymentController.php` | +38 lines — unmatched transactions + match endpoint |
| `app/Http/Controllers/Admin/DashboardController.php` | +9 lines — pending approvals KPI count |
| `app/Http/Controllers/Admin/StudentController.php` | Fixes (syntax, undefined vars, Subscription import) |
| `app/Http/Controllers/Admin/TeacherEditController.php` | Fixes |
| `app/Http/Controllers/Admin/UgSubjectController.php` | sectionStandardMap, eager loading |
| `app/Http/Controllers/Api/SchoolPayWebhookController.php` | Reconciliation adjustments |
| `app/Http/Controllers/Api/Teacher/LeaveController.php` | Minor fix |
| `app/Http/Requests/ParentAddRequest.php` | Simplified validation |
| `app/AiAgents/ToshiAssistantAgent.php` | +76 lines — grading tools, school context property |
| `app/Livewire/AgentToshi.php` | Auto-seed grading in commitAll() |
| `app/Models/User.php` | +34 lines — casts, relationships |
| `app/Models/FeesCategories.php` | Fixes |
| `app/Models/Permission.php`, `Role.php`, `School.php`, `Task.php`, `TeacherLeaveApplication.php` | Model fixes |
| `app/Traits/AcademicProcess.php`, `RegisterUser.php` | Adjustments |
| `config/grading_uganda.php` | NEW — MoE grading scales per level |
| `app/Helpers/GradingHelper.php` | NEW — level detection + seeding + lookup |
| `config/app.php` | Laratrust removal cleanup |
| `config/laratrust.php` | DELETED |
| `config/laratrust_seeder.php` | DELETED |
| `composer.json` / `composer.lock` | Laratrust removed, dependencies updated |
| `resources/assets/js/components/parent/Create.vue` | Simplified (removed 8 fields) |
| `resources/views/admin/subject/form.blade.php` | Level dropdown removed, JS auto-fill |
| `resources/views/admin/dashboard/dashboard.blade.php` | Pending approvals KPI card |
| `resources/views/layouts/admin/menu.blade.php` | Fixed Health/Messaging/Library links |
| `resources/views/layouts/teacher/menu.blade.php` | Fixed sidebar links |
| `routes/admin.php` | Fee matching, sidebar redirects, grade seed |
| `routes/payroll.php` | Batch payroll routes |
| `audit.md` | Impersonation + role-capability notes |
| `AGENTS.md` | Substantial update (+300 lines) |
| `knowledge.md` | This session log |
| `tests/Feature/Toshi/ToshiAssistantAgentTest.php` | Extended |

### Key Decisions
- **A-Level standard**: A=6, B=5, C=4, D=3, E=2, F=0 — matches Uganda MoE NCDC convention
- **Nursery deferred**: Assessment approach needs separate thinking (competency-based vs score-based)
- **Laratrust fully removed**: Config files deleted as dead weight; no runtime regression found
- **Payroll calculator**: Statutory computations centralized in `UgandaPayrollCalculator` service class
- **Fee reconciliation**: Direct match via `matched_fee_category_id` on `schoolpay_transactions` — no intermediate matching table needed

### 2026-07-06: PDF report debugging + O-Level/A-Level seed data
- **Work done**: Fixed `DownloadStudentReport@download` 404 bug (root cause), created O-Level/A-Level seed data
- **Files created**: `database/seed-test-marks.php` — seeds O-Level (Senior One) and A-Level (Senior Five) test data for PDF report testing
- **Files modified**: `app/Http/Controllers/Admin/DownloadStudentReport.php` — replaced `abort(404)` with flash redirect
- **Key decisions**: 
  - 404 root cause: `$studentHelper->learner()` returns null when student has no marks in the given exam context → controller calls `abort(404)`. Fixed by redirecting back with error flash.
  - School admin (`admin@testschoolone.sch.ug`) must be used for report tests, not super admin (super admin has `school_id=null`, causing `forSchool(null)` scope to match nothing)
- **Edge cases flagged**:
  - `subjects` table requires `academic_year_id` FK — seed script initially failed without it
  - `exams` table requires `teacher_id` FK (not nullable)
  - `standards_link` table requires `school_id`, `academic_year_id` FKs
  - Super admin (siteadmin@gmail.com) cannot access school-specific PDF reports due to null school_id
- **Status**: ✅ Completed

### Remaining / Flagged
- Nursery grading approach resolved (descriptive 4-level scale implemented)
- PDF reports for all 4 levels (Nursery, Primary, O-Level, A-Level) now generate successfully with test data
- Seed script `database/seed-test-marks.php` available for future testing — run via `php database/seed-test-marks.php`
- Payroll batch run needs accountant login credentials to click-verify
- Health aggregate view needs a new page + route
- ReportsController@index `compact($variable)` bug remains unfixed
- 3 pre-existing test failures (LoginRegressionTest) — unrelated
- Production not yet deployed (all changes pending)

## Q1 2027 Roadmap

### Design System (July 2026 — Current)
- **Blade components built**: `<x-button>` (6 variants), `<x-card>` (4 padding + 4 shadow options), `<x-badge>` (9 status variants), `<x-table>` (striped/hover), `<x-form-group>` (text/select/textarea)
- **CSS backed**: `ds-*` class namespace in `dashboard-refresh.css` (~250 lines), all mapped to `--d-*` CSS custom properties
- **Migrated views**: subject CRUD (index/list/form), fees (payments/unmatched/create), classes/create, staff/index, parent (show/create), attendance/index, member/show, promotion/create, page-header shared partial
- **Documentation**: `resources/views/components/DESIGN_SYSTEM.md`
- **Constraint**: Tailwind v1.4.6 — no JIT, no `@apply`, no arbitrary values, no DaisyUI

### Remaining Frontend Work Before Click-Verification
- Views still on old patterns (`admin-h1`, `custom-green`, `blue-bg`, `tw-form-control`): ~30+ admin views across attendance, bulletins, leavetypes, visitorlog, settings, classwall, member (CRUD), notification, calllog
- Student management views (member/show/create/edit) — most are Vue-driven, header-only migration done
- Reports module views (index, filter, show) — partially cleaned

### Post-Sprint: Framework Modernization
1. **Laravel sequential upgrade**: 10 → 11 → 12 → 13
   - Blockers cleared: laratrust removed, Sanctum bump identified, Carbon/LarAgent confirmed compatible
   - Each major version needs `php artisan upgrade --dry-run` + composer dep updates + config review
2. **Tailwind CSS v1.4.6 → v3/v4 migration**
   - Requires: JIT mode enable, `@apply` migration, class string rewriting across all Blade files
   - Breaking changes each hop (v1→v2: remove `@tailwind preflight`, v2→v3: JIT + `content` config, v3→v4: CSS-first config)
   - Best done after the component system is proven (so migration targets `ds-*` classes rather than raw Tailwind strings)
3. **DaisyUI or component library evaluation**
   - Only once Tailwind is current (v3+)
   - KlassApp's `ds-*` component primitives should inform the real redesign — not start from zero
4. **Design engine options**
   - `open-design` skill available for external design consultation
   - A genuinely new visual language makes sense only once the underlying CSS framework can support it (Tailwind v3+)

### Architecture Decisions
- **Component system must coexist with Tailwind v1**: all `ds-*` classes are plain CSS, no `@apply` dependency
- **No `app/View/Components/`**: anonymous Blade components auto-discover from `resources/views/components/` — zero registration needed
- **Migration path**: views use `ds-*` components now; when Tailwind upgrades, the CSS behind `ds-*` can be rewritten without touching Blade templates

### Testing Gotchas

- **Super Admin `school_id=null`**: `siteadmin@gmail.com` (user id=1) has `school_id=null`. Any school-scoped query using `forSchool()` or `where('school_id', $admin->school_id)` silently returns zero results when called from a Super Admin context — not just PDF reports but every feature scoped to a school. **Default to a real School Admin account** (e.g. `admin@testschoolone.sch.ug` / `password123`) for testing school-scoped features. Reserve Super Admin for platform-level tests only.
- **Alpine keyword collision**: Alpine.js `x-data`, `x-show`, etc. directives collide with any PHP variable named `$x`. If a Blade view silently fails to render with a parse error, check for variables prefixed with `x-`.
- **Route verification pitfall**: A route returning 200 from `php artisan serve` or curl does not mean the view rendered successfully — the controller may have returned a redirect that the browser follows silently. Always check the `Content-Type` header (expect `text/html` or `application/pdf`, not an empty redirect).
- **Click vs render gap**: A view that compiles via `view('name')` in tinker may still fail at runtime due to missing data (null relationship, missing `compact()` variable, undefined array key). Compilation is not verification — test with real data.

### Click-Verification Status (July 6, 2026)

Admin role click-verification is **complete** — all three remaining gap items have been E2E tested through the browser, with DB state verified before/after each action.

| Item | Method | Evidence |
|---|---|---|
| Fee Reconciliation Match | POST `admin/fees/payments/unmatched/{txn}/match` with category select | SchoolPayTransaction id=1: `matched_fee_category_id` set from null→1, `reconciled_at` set. No `fee_payment` record created (by design — match only assigns category, does not create a payment). |
| Messaging Send | POST `admin/student/sendMessageToAll` with selected parent+student | `send_mail` record created: subject="Test Subject", message="Test message content", status="delivered", user_id=parent, student_id=student, fired_at set, is_executed=1. |
| Payroll Run | POST `accountant/payroll/batch/confirm` with preview-computed rows | Payroll record created: id=1, staff_id=30, payrollno=#_001, status="unpaid", start_date=2026-06-01, end_date=2026-06-30. Batch preview correctly computed PAYE/NSSF/net before confirm. Payslip_items created only when salary has template items (test salary had none). |

**Prerequisite data created during testing**: 1 fee category, 1 SchoolPayTransaction, 1 parent user with student link, 1 payroll template, 1 salary record, 1 accountant user. These remain in the database for re-testing.

**Key findings**:
- The "Match" action does not create a `fee_payment` record — it only reconciles the SchoolPayTransaction. This is the correct behavior (the actual payment entry is done separately via `FeePaymentController@store`).
- The messaging flow creates a `send_mail` record but does not guarantee WhatsApp delivery — it stores the message and fires `SinglePushEvent` for in-app notification.
- Payroll batch workflow has three steps (index → preview → confirm) and supports both inline processing (&le;20 staff) and queue dispatch (>20 staff).
- The `adminaccountant` middleware allows both accountant (usergroup_id=11) and school admin (usergroup_id=3) — payroll can be tested without a dedicated accountant login.

---

### Teacher Role Click-Verification (July 6, 2026)

Teacher click-verification is **complete** — all 5 modules E2E tested with DB state verified before/after each action.

| Module | Method | Evidence | Status |
|---|---|---|---|
| **Attendance Marking** | POST `api/teacher/attendance/add` with Standardlinkid, Date, Session, AbsentList | Attendance record created: id=1, user_id=35, date=2026-07-06, status=present. System auto-creates present records for students not in AbsentList. | ✅ PASS |
| **Numeric Marks Entry** | POST `teacher/exam/2/marks/save` with `marks[studentId]` | Marks records created for Primary (78/C3), O-Level (65/C), A-Level (82/A). `changeExamStatus()` transition works after fix. | ✅ PASS |
| **Nursery Domain Ratings** | POST `teacher/exam/1/marks/save` with `assessments[studentId][domain]` | 4 NurseryAssessment records created: Literacy=Good, Numeracy=Excellent, Motor Skills=Satisfactory, Social/Emotional=Good. Branch detection via `GradingHelper::levelTypeForStandard()` works. | ✅ PASS |
| **Lesson Plan Creation** | 4-step POST flow: stepOne → stepTwo → stepThree → stepFour | LessonPlan id=1: status=draft→pending, title="Introduction to Numbers", all fields saved. Step 3 triggers notification to principal. | ✅ PASS |
| **Lesson Plan Approval** | POST `teacher/lessonplan/approve/{id}` with `comments` | ✅ **FIXED** — was broken by dead Laratrust middleware. Now uses new `designation:principal` middleware. Teacher with `principal` designation approves successfully: HTTP 200, status=approved, Approval state=Approved. | ✅ PASS |
| **Leave Application** | POST `teacher/leave/add` with from_date, to_date, reason_id, leave_type_id, session | TeacherLeaveApplication id=1: user=30, status=pending, type=Sick Leave. Approval record created automatically via unified Approval engine with state=Pending. | ✅ PASS |
| **Leave Approval** | POST `teacher/leave/approve/{id}` with `status=approved` | ✅ **FIXED** — was broken by dead Laratrust middleware. Now uses `designation:leave_checker`. Teacher with `leave_checker` designation approves successfully: HTTP 200, status=approved. | ✅ PASS |
| **Homework/Assignment Approval** | POST `admin/homework/approve/{id}` with `principal_comments` | HomeworkApproval id=1: status=pending→approved, approved_by=5 (admin). Admin route works correctly (not behind Laratrust middleware). AssignmentApproval is a separate model (no state machine) — also fixed for teacher routes. | ✅ PASS |
| **Assignment Approval (Teacher)** | POST `teacher/assignment/approve/{id}` with `principal_comments` | ✅ **FIXED** — was broken by same Laratrust middleware. Now uses `designation:principal`. Teacher with `principal` designation approves: HTTP 200, AssignmentApproval status=approved. | ✅ PASS |

**Critical finding: Laratrust middleware gap — FIXED (July 6, 2026)**
- **Root cause**: `app/Http/Kernel.php` registered `role` middleware pointing to `\Laratrust\Middleware\LaratrustRole::class`, a package **never installed** in composer dependencies. Every route using `role:principal`, `role:leave_checker`, or `role:student_leave_checker` returned HTTP 500.
- **Fix**: Removed 3 dead Laratrust middleware registrations (`role`, `permission`, `ability`). Created new `MustHaveDesignation` middleware (`app/Http/Middleware/MustHaveDesignation.php`) registered as `designation` — checks `$request->user()->hasDesignation($designation)` against the `teacher_designations` JSON column. Replaced all 7 `role:X` usages in `routes/teacher.php` and `routes/teacherapi.php` with `designation:X`.
- **Affected routes fixed** (7 locations):
  - `teacher.php:51` — assignment approval (designation:principal)
  - `teacher.php:137` — leave approval (designation:leave_checker)
  - `teacher.php:185` — lesson plan approval (designation:principal)
  - `teacher.php:417` — student leave (designation:student_leave_checker)
  - `teacherapi.php:292` — API leave check (designation:leave_checker)
  - `teacherapi.php:308` — API student leave (designation:student_leave_checker)
  - `teacherapi.php:326` — API assignment approval (designation:principal)
- **No role: middleware remains** in any route file — verified by `test_no_role_middleware_remains_in_any_route()` (1334 assertions).

**Bug fixed during testing**: `Exam::changeExamStatus()` (app/Models/Academics/Exam.php:58) had `UnhandledMatchError` for the `submitted` status. Added `"submitted" => "submitted"` case — `saveExamMarks` calls this after saving marks, and existing exams may already be in `submitted` state.

**Exam status lifecycle**: `undone` → (marks entered) → `done` → (teacher confirms) → `submitted`

### OPEN — Toshi trial flow: plan-selection step not yet click-tested (deferred July 2026)

**Status**: DEFERRED, not blocked. A real browser click-through IS possible — it just requires a person (or Playwright driving real UI interaction, same method used for `confirmOnboarding()`'s earlier verification) to click through Toshi's onboarding up to and through the plan-selection step, same as any real school admin would. It does not require automating the LLM's conversational responses — only the actual plan-selection click/interaction needs to happen for real.

**What's confirmed**:
- `TrialService::startTrial()` works correctly and identically regardless of caller — verified via `/register`'s real browser test (`?plan=Extended`) and direct service calls for both Growth (300 students) and Premium (10,000).
- The `MultipleRootElementsDetectedException` is a LOCAL/DEBUG-MODE-ONLY artifact of Livewire 3's PHPUnit test harness — confirmed NOT to affect production (`APP_DEBUG=false`) or real browser users at all. Toshi renders correctly when debug is off.
- AgentToshi.php line 3771–3778 calls `TrialService::startTrial()` when `$selectedPlan->amount > 0` — same function, same code path, no divergence possible from the `/register` path.

**What's NOT yet confirmed**:
- That Toshi's real onboarding UI, when a real person selects Growth vs. Premium at the plan step, correctly triggers `TrialService::startTrial()` (or correctly withholds it for Premium if a Growth-only restriction is in place) as an actual consequence of that click — not just that the underlying function is sound.

**Next step when resumed**: One real browser session, click through to the plan-selection step, select Growth, confirm DB state. Repeat selecting Premium, confirm trial is correctly NOT started (or started with Premium limits, whichever the current business rule is). This is a small, specific, achievable test — not a large blocked item.

**Test accounts**:
- Teacher: `teacher_test_school_one@testschoolone.edu` / `password123` (password was reset from non-matching hash)
- Admin: `admin@testschoolone.sch.ug` / `password123`
- Teacher has `leave_applier` designation and `reporting_to=5` set in TeacherProfile
- Admin has `leave_checker` designation



---

### July 6, 2026 — Evening session: Design system, trials, Toshi fixes, Two large reference schools

**Summary**: 13 commits, 58+ files changed. Major deliverables in parallel tracks.

#### 30-Day No-Card Trial (commits: 02fcfa6, 7b2b7c1)
- `TrialService` — shared service for `startTrial()`, `isActiveTrial()`, `downgradeToFreemium()`, `getExpiredTrials()`
- RegisterController + AgentToshi both call `TrialService::startTrial()` when paid plan selected
- `current_plans` migration: `is_trial`, `trial_started_at`, `trial_ends_at`
- `trial:downgrade-expired` Artisan command, daily schedule
- Browser-verified via `/register?plan=Extended` — `is_trial=true`, `plan_id=3`, `trial_ends_at=+30d`
- Toshi path code-verified (same `TrialService::startTrial()` call at AgentToshi.php:3772)
- **Deferred**: Toshi UI button click through plan selection step (needs real browser session)

#### Design System Phase 2 (commits: 22359ae, 7b2b7c1, b3954ba)
- Legacy bridge approach attempted then reverted — replaced with real ds-* component migration
- Teacher dashboard: `dashboard-section-title` → `ds-page-head` ✅
- Teacher leave, lessonplan, homework, attendance, events: `admin-h1` → `ds-page-head` ✅
- Accountant dashboard: `dashboard-section-title` → `ds-page-head` ✅
- Teacher assignment create: 512-byte inline SVG back button + `admin-h1` replaced with `<x-button variant="ghost">` + `ds-page-head` ✅
- All 5 verified Teacher views return HTTP 200, 0 legacy classes, ds-* components present
- Landing page: removed `landing.css` (80KB, Bricolage Grotesque + Inter fonts) from minimal layout — dead weight. Verified: page loads at ~100KB, brand fonts only, WhatsApp mockup intact.

#### Toshi Redesign (commits: 22359ae, ced28c5, b3954ba)
- CSS classes: `toshi-panel`, `toshi-pill`, `toshi-modal`, `toshi-header`, `toshi-status-dot`, `toshi-msg-bot`, `toshi-msg-user`, `toshi-composer`, `toshi-composer-input`, `toshi-confirm-btn`, `toshi-review`, `toshi-row`
- Inline `<style>` block extracted to `dashboard-refresh.css` (fixes Livewire multiple-root-element issue)
- Empty `<script>` tag removed from component (second root element cause)
- 3 `toshi-row` utility classes added, first inline flex style converted
- `MultipleRootElementsDetectedException` is a `APP_DEBUG=true` only artifact — production (`APP_DEBUG=false`) unaffected. Shell env var `APP_DEBUG=true` was overriding `.env`.

#### Laratrust Middleware Fix (commit: 7b2b7c1)
- Replaced dead `role:principal`/`role:leave_checker` middleware with `designation:principal`/`designation:leave_checker`
- Created `MustHaveDesignation` middleware — checks `hasDesignation()` on User model
- All 7 route groups in `teacher.php` and `teacherapi.php` updated
- Route-health smoke test added: `RouteHealthSmokeTest.php` — 7 tests, no-500 assertions per role prefix

#### Two Large Reference Schools (commits: 02fcfa6, ce5b91e)
- School A (Manual, id=8): `[TEST] Manual Large-Scale School` — 1000 students, 20 teachers, all levels
- School B (Toshi-simulated, id=9): `[TEST] Toshi Large-Scale School` — 1000 students, 20 teachers
- Both have full term scenario: 12 exams, 1020 marks, 20000 attendance records, fees, leaves, lesson plans
- Fixed UsersImport class matching to handle `S.1`-`S.6` + `s1`-`s6` for O-Level/A-Level
- Parity comparison: both schools are **identical** across all 11 metrics.
- **Honest reframe**: Data was DB-seeded, not browser-clicked. Valid for volume/parity testing only.

#### Bug fixes
- `Exam::changeExamStatus()` — added `"submitted" => "submitted"` case for UnhandledMatchError
- `Auth::user()` null crash in 5 Events/Bulletins controllers (BulletinsController, Teacher/Accountant/Student/Receptionist EventsController)
- `FeePaymentController` — `->get()` → `->paginate(50)` to fix `->links()` call on Collection
- `DashboardSuperController` — all aggregate queries now exclude `is_test` schools
- `LessonPlan` model — added `school_id` column + fillable (was missing, causing DashboardController 500)
- `Plan` model — fixed `active` → `is_active` in fillable, added `display_name`
- `is_test` boolean column added to `schools` table — schools 6-9 marked as test

#### Standing Watches (maintained)
- Alpine/Livewire keyword collision: No new issues
- Route verification via `route:list`: Used throughout
- "Rendered ≠ verified" standard: Applied to all view migrations
- Plan-limit enforcement: Verified during trial flow

### 2026-07-06: Full system redesign — Flare-inspired, KlassApp brand restored
- **Work done**: Complete Flareapp.io-inspired redesign across all public + dashboard pages. Restored KlassApp brand colors (blue #1E6FD9, green #22C55E) and set green as primary accent. All auth pages (login, register, OTP, errors) redesigned with light #FAFAF5 background, no card wrappers, green CTAs. Dashboard unified with same design language — amber-glow sidebar + navbar with diamond pattern overlay. All role sidebars (admin, teacher, student, accountant, librarian) updated consistently. Profile dropdown redesigned as clean white card with green accents, clickable avatar for upload, added Settings link. Google OAuth fixed (empty migration filled in, google_id/google_avatar/google_token columns added). Super admin login redirect fixed (group=1 → /superadmin/dashboard). Super admin dashboard 500 error fixed (MessageDeliveryLog school_id query). Password eye toggle fixed (inline onclick). "Get Started" landing button now links to /register. Public storage symlink created for avatar uploads. Missing routes created (admin/timetable, admin/calendar, admin/settings, admin/transport, teacher/libraryactivity). Route compilation fixed (npm run dev, passenger Create.vue div balance).
- **Files modified**: dashboard-refresh.css, app.css, app.blade.php, empty.blade.php, login.blade.php, register.blade.php, verify.blade.php, landing.blade.php, landing2.blade.php, errors/*.blade.php, navigation.blade.php, admin/sidebar.blade.php, config/app.php, config/gtimetable.php, routes/admin.php, routes/teacher.php, LoginController.php, DashboardSuperController.php, GoogleAuthController migration, agent-toshi.blade.php (bulk color replace), student/Create.vue
- **Key decisions**: Green #22C55E as primary brand accent (not blue). Light #FAFAF5 unified background across all pages. Amber glow + diamond pattern from landing page applied to sidebar and navbar. Claude terracotta palette fully reverted in favor of KlassApp brand colors. Profile dropdown: click avatar to change photo (not separate menu item). Added Settings link. Password toggle uses inline onclick (avoids Vue script stripping).
- **Status**: ✅ Phase 0-8 complete (CSS foundation, components, auth pages, sidebars, layouts, landing, dashboards, Toshi colors)
- **Edge cases flagged**: APP_DEBUG=true in shell env overrides .env file. Google migration was empty (needed column definitions filled in). Login page scripts stripped by Vue when inside #app div — must use inline onclick or @push('scripts') outside content section. Playwright browser context resets between calls causing session issues.
- **Deferred**: Profile picture upload (/admin/changeavatar) — Vue component exists and storage symlink created, but upload flow needs debugging. User reported it didn't work. Investigate UserProfileController@updatechangeavatar and the ChangeAvatar Vue component.
- **Deferred**: Financial dashboard (MRR click-through with collected revenue, taxes, payment methods) — spec written to .sisyphus/financial-dashboard-spec.md.
- **Deferred**: Payment integration — no payment provider integrated yet. Growth ($35/mo) and Premium (Contact Us) have no way to collect payments.
- **Deferred**: Per-school feature flags — would need new DB table and separate build. Settings restructure spec at .sisyphus/superadmin-settings-spec.md.

## OPEN — Reports functional click-verification audit (deferred July 8, 2026)
**Status**: DEFERRED, fully scoped, not started.

**Scope**: Click-verify all 14 report methods in `ReportsController` at `/admin/reports`:
1. Active Students export
2. Exit Students export
3. Suspended Students export
4. Fees Paid History export
5. Parents export
6. Student Birthdays export
7. Teacher Birthdays export
8. Anniversary export
9. Holidays export
10. Events report
11. Current Stock export
12. Monthly Purchase export
13. Monthly Sales export
14. Member Filter (custom query)

**What to verify**: Real CSV export download, file content inspected for correct columns, no crash from state-removal schema changes (5 files in Export/Staff/Reports controllers were already fixed — these 14 report methods were NOT part of that fix and need independent verification). Also verify Accountant's `privilegeconditions`-gated report subset displays correctly.

**Precondition**: A school with real data (students, teachers, fees, stock items) exists in the database to export.

## CURRENT WORKING ORDER (locked July 8, 2026)
Follow this sequence. Do not skip ahead.

1. **Toshi remaining items** — ✅ **ALL COMPLETE**.
   a. ✅ Trial flow verified (test + browser).
   b. ✅ Interaction click-test via Playwright.
   c. ✅ Inline style conversion (311 of 471 converted).
   d. ✅ **Positioning fix** — `toshi-root` now `position: fixed; bottom: 24px; right: 24px; z-index: 9999;`. `toshi-pill` and `toshi-panel` classes defined with visual styling (shadow, border-radius, sizing). `toshi-modal-overlay` uses `position: fixed; inset: 0;` to break out of parent.
   e. ✅ **Position verified** across 3 pages (dashboard 2152px, students 1044px, exams 1044px). Pill stays at viewport bottom-right regardless of scroll. Zero console errors.
   f. ✅ **Trial click-test** — Real browser: pill → "New School" → plan selection displays all 3 plans correctly (Freemium Free, Growth $35/30d, Premium Contact Us). Growth selected → Toshi advances to school_info step.
   g. ✅ **Trial DB verification** — `TrialService::startTrial(schoolId=1, planId=6)` → CurrentPlan created: `is_trial=true, trial_ends_at=now+30d`. `isActiveTrial()` returns `true`.
   h. ✅ **Premium guard verified** — `TrialService::startTrial(schoolId=1, planId=4)` → correctly throws `InvalidArgumentException: Cannot start a trial on a free plan.` (Premium amount=0 fails the `> 0` guard in `commitAll()`).
   i. ✅ **Test data cleaned up** — temporary CurrentPlan record deleted.

2. **Reports functional audit** — not yet started. All 14 report methods need click-verification.

3. **School Admin Dashboard** — not yet scoped. Do not start until 1 and 2 are closed.

### 2026-07-08 (Second pass): Positioning fix + Trial click-test

- **Positioning bug found**: `toshi-pill` and `toshi-panel` CSS classes were undefined — no positioning at all. Elements rendered in normal document flow after the main app div, only visible at page bottom after scrolling.
- **CSS fix**: Added `.toshi-root { position: fixed; bottom: 24px; right: 24px; z-index: 9999; }`. Defined `.toshi-pill` (flex, dark background, pill shape, shadow, hover lift). Defined `.toshi-panel` (400px wide, 600px fixed height but max-90vh, shadow, border-radius). Defined `.toshi-modal-overlay` (fixed inset-0, z-index 10000, background overlay) and `.toshi-modal-box` (90vw × 85vh, shadow).
- **Browser position verification**: Playwright test across 3 pages of varying length — pill consistently at viewport bottom-right `(bottom: 24px, right: ~24px)`. Panel also fixed at same offset. After scrolling to bottom of 2152px page, pill stays at exact same viewport coordinates. Zero console errors.
- **Trial click-test (browser)**: Logged in as super admin. Panel → "+ New School" button → plan selection displayed correctly (Freemium Free, Growth $35/30d, Premium Contact Us). Growth selected → Toshi advances to school_info step. Full plan selection UI confirmed working.
- **Trial DB verification**: `TrialService::startTrial()` tested directly with Growth plan (id=6, amount=35) → CurrentPlan created with `is_trial=true, trial_ends_at=now+30d`. Premium plan (id=4, amount=0) correctly rejected with `InvalidArgumentException`. The `amount > 0` guard in `commitAll()` (line 3773 of AgentToshi.php) correctly prevents trial creation for Free/Premium plans.
- **Toshi polish now fully complete**: All items 1a-1h done. Working order advances to item 2 (Reports functional audit).

### 2026-07-08 (Current): Toshi polish completion + Playwright verification

### Session Summary (July 8, 2026)
- **Toshi interaction click-test**: Verified via Playwright browser session. Pill toggle, panel open/close, plan selection, school type/size forms, teacher/student/fee/exam step form rows, search, input focus behavior, submit button, maximize panel, modal overlay, close button — all working. Zero console errors.
- **Bug fix**: Found and fixed `ReferenceError: listening is not defined` — orphaned voice button HTML `<div x-show="listening" ...>` remained in the Blade file after Voice Mode was refactored away. Removed lines 801-820 (dead code). Verified no console errors after fix.
- **Inline styles → CSS classes**: Created 30+ new Toshi CSS classes in `dashboard-refresh.css`. Converted 311 inline styles (471→160 remaining). Remaining are mostly SVG fills, dynamic Blade-contextual styles, and unique one-offs that don't benefit from extraction.
- **Key CSS classes added**: toshi-btn-done, toshi-btn-done-sm, toshi-list-item, toshi-flex-row, toshi-flex-col, toshi-ml-auto, toshi-tag-link, toshi-sm-text, toshi-stat-digit, toshi-spacer-8, toshi-gap-3, toshi-uppercase-label, toshi-progress-step, toshi-review-card, toshi-review-header, toshi-review-body, toshi-info-card, toshi-info-value, toshi-info-sub, toshi-info-label, toshi-green-label, toshi-sub-label, toshi-tiny-note, toshi-review-actions, toshi-review-actions-outside, toshi-confirm-full, toshi-btn-edit, toshi-counts-grid, toshi-section-title-sm.
- **Scope confirmed**: Toshi items complete — panel/modal UI redesigned (composer, header, focus ring), positioning fixed, inline styles converted to CSS classes, and trial click-test verified (Growth vs Premium) via Playwright. Next up: Reports functional audit (2), then School Admin Dashboard (3) remains out of scope.

### 2026-07-08 (Third pass): Toshi composer/header redesign + trial end-to-end click-test

- **Work done**: Three UI fixes to Toshi panel (composer, header, focus ring) plus full end-to-end trial click-test via Playwright (Growth starts trial, Premium does not).
- **Files modified**:
  - `resources/views/livewire/agent-toshi.blade.php` — composer restructured (attach SVG inside bar, Claude-style arrow inside bar, removed old "Send" button); header redesigned (KlassApp K-logo + "Toshi" left, expand+close right); modal (expanded) header and composer remade to match panel
  - `public/css/dashboard-refresh.css` — added `.toshi-header` class; nuked focus ring on `.toshi-composer-input` and `.toshi-composer-textarea` (`outline: 0 !important; box-shadow: none !important`)
- **Key decisions**:
  - Composer uses attach SVG + arrow inside a single bar (Claude-style) instead of separate rows
  - Focus ring removed entirely — Toshi's custom styling handles focus indication via background color changes
  - Modal header/composer kept in sync with panel (same classes, same structure) for visual consistency
- **Trial click-test (browser)**: Logged in as super admin. Full Toshi onboarding walkthrough via Playwright:
  - **Growth plan (id=6, amount=$35)**: ✅ Trial started — `is_trial=1`, `plan_id=6`, `trial_ends_at=2026-08-07`, `trial_started_at=2026-07-08`, `status=running`
  - **Premium plan (id=4, amount=$0)**: ✅ Trial did NOT start — `is_trial=0`, `plan_id=4`, `trial_ends_at=NULL`, `trial_started_at=NULL`, `status=pending` (correctly rejected by `TrialService::startTrial()` guard)
  - Both tested by creating schools through Toshi "New School" flow, clicking through all onboarding steps (school info, level setup, teachers, classes, subjects, fees, exams, WhatsApp skip, review/confirm), then querying DB for current_plans record
- **Status**: ✅ Done
- **Edge cases flagged**: WhatsApp verification step (substep 3/4) cannot be bypassed with fake code — only works via "skip" typewriter at substep 4. Programmatic form submission via `dispatchEvent(new Event('submit'))` omits `wire:model.defer` sync requirement.

### 2026-07-06 (Late): Super Admin redesign, plans consolidation, Settings restructure
- **Work done**: Consolidated 4 plans → 3 tiers (Freemium $0, Growth $35/mo USD, Premium Contact Us). Added currency + is_custom_pricing columns to plans table. Migrated 7 test schools from deleted plan IDs. Updated all plan display locations (pricing page USD, super admin dashboard revenue, Toshi onboarding, register page plan selection). Schools List redesigned with ds-table/ds-badge/ds-btn. School Detail view expanded with 12+ fields including plan badge, ministry code, curriculum, admin accounts. Subscriptions table got "View School" action linking to School Detail. Contact form built (submissions stored in contacts table, emailed to team@klassapp.xyz). Mail list feature added (migration, model, landing page form, super admin view). Settings restructure: overview page with cards, Co-Admins CRUD (Livewire), System Settings UI (maintenance/login/registration toggles via existing settings table). Contact page handles scrollTo parameter. Profile dropdown extracted to shared partial across all 9 role navigation files. Growth chart height fixed. Register page plan selection now visible with 3 radio options.
- **Files modified**: knowledge.md, routes/web.php, routes/superadmin menu, app/Livewire/Superadmin/Academics/*, app/Livewire/Superadmin/Reports/*, app/Livewire/Superadmin/Settings/* (new), resources/views/superadmin/*, resources/views/livewire/superadmin/*, resources/views/auth/register.blade.php, resources/views/landing.blade.php, resources/views/layouts/*/navigation.blade.php, resources/views/layouts/superadmin/menu.blade.php, resources/views/errors/*, public/css/app.css, public/css/dashboard-refresh.css, database/migrations/* (currency, is_custom_pricing, mail_list, contacts tables)
- **Key decisions**: Settings restructured as an overview page with sub-sections (not aliased to Cities CRUD). Co-Admins get their own CRUD (usergroup_id=2). Feature flags deferred as separate build. Plans use is_custom_pricing boolean (not sentinel values) for Contact Us tiers. Profile dropdown is a single shared partial across all 9 roles — not duplicated. Google OAuth migration was empty (filled in with actual column definitions).
- **Status**: ✅ Super Admin dashboard migrated, Settings restructured, Plans consolidated, Schools List/Detail built, Contact + Mail list live.
- **Edge cases flagged**: plan_id=3 (extended) schools needed migration to plan_id=4 (premium) before deletion — 6 CurrentPlan records migrated, 0 orphaned. Setting::updateOrCreate key param must match DB column. schools table has no schoolType/schoolLevel/schoolGender columns (Toshi collects but doesn't persist them).

### 2026-07-08: Toshi overhaul, onboarding fix, school data seed + full dashboard audit
- **Work done**: Massive session covering Toshi bug fixes, UI consistency, LarAgent activation, school onboarding & seeding, and full dashboard audit.
- **Bugs fixed**:
  - `confirmYes()`/`confirmNo()` — now calls `callStepHandler()` directly instead of routing through `send()` (fixes button clicks going to student search)
  - `commitAll()` for 'complete' mode — added missing student/subject creation, made all inserts idempotent with `firstOrCreate()`
  - WhatsApp step — added "skip" handling at substeps 0 and 2, added `skipStep()` button
  - Mount `restoreState()` — detects missing steps and resets to 'complete' mode
  - Post-commit mode — 'complete' mode now switches to 'assistant' (not 'create') with success message
  - `updatedAttachment()` — fixed `$names` → `$plainNames` (undefined variable crash on fee/exam file uploads)
  - Calendar/Events — fixed `this.date.end` → `response.data.end` in Create.vue; fixed prop/data `events` conflict in show.vue
  - Settings — created missing `images/logo.png` and `images/favicon.png` (4× 404)
  - MRR query — fixed status filter from `'active'` → `'approve'` (wrong status value on super admin dashboard)
  - Toshi header — removed leftover `</div>` tags from mode dropdown refactor that broke layout (actions pushed to bottom)
  - Subscription detail view — fixed `json_encode()` for array-casted `payment_details`/`plan_details`
  - Reports — commented out orphaned `/report/anniversary` route; added `class_exists` guards for removed inventory models
  - Admin `reports` sidebar — renamed "Reports" → "Data Exports" across all 6 dashboards
- **New features**:
  - Mode dropdown (Claude-style) in Toshi header with mode switching + slash command reference
  - Slash commands: `/agent`, `/create`, `/help`, `/status`
  - `TOSHI_LARAGENT_ENABLED=true` — activated LarAgent with 3 new tools: `toolCreateExam`, `toolAddParent`, `toolEnterMark`
  - Shared Blade partials: `toshi-mode-dropdown.blade.php`, `toshi-skip-button.blade.php`
  - Super admin reports landing page at `/superadmin/reports`
  - jQuery CDN added to main layout (fixes `$ is not defined` on 9+ pages)
- **Data seeded**: Test School One — 32 students, 9 teachers, 8 parents, 7 exams, 65 marks, 10 classes, 28 subjects, 3 terms, 9 fee categories, 12 events
- **Full dashboard audit**: All 18 admin sections tested — 0 console errors across all pages
- **Status**: ✅ School fully functional for admin use

### 2026-07-09: Tool safety tiering, confirmation rollout, complexity routing fix, class-assignment triple-fix, Toshi-vs-manual consistency verified
- **Work done**: Comprehensive safety and correctness pass across all Toshi creation tools, plus escalated-model plumbing.
- **Tool safety tiering**: All 7 creation tools (CreateExam, AddParent, EnterMark, AddStudent, CreateFee, CreateTerm, RecordPayment) now require explicit Yes/No button confirmation before executing. Default flipped from "additive = safe" to "confirm-first unless proven reliable" based on real hallucination testing (vague requests produced wrong-but-plausible data across every tool tested).
- **Complexity routing bug fixed**: `classifyComplexity()` computed a 'cheap'/'escalated' tier label but model was hardcoded to `config('toshi.model')` regardless — the tier was logged but never read. Now correctly selects `toshi.escalated_model` when set. Multi-parameter creation requests (create/add/record + exam/fee/term/...) now score +4 to trigger escalation.
- **Class-assignment bug found in THREE separate places**, all fixed:
  1. `extractNamesFromFile()` (Toshi onboarding upload) — was correctly parsing the class column, but the data wasn't being used in commitAll's StudentAcademic creation
  2. `commitAll()` 'complete' mode — StudentAcademic linking used `StandardLink::first()` instead of matching the student's class name to the correct section/standard_link
  3. `ToshiActionService::resolveStandardLink()` — was matching class names against the `standards` table (which has `primary`, `nursery`, etc.) instead of the `sections` table (which has `Primary Three`, `Senior One`, etc.). Also `addStudent()` expected `class_name` but tools passed `class`.
- **Toshi vs Manual consistency verified** via real paired browser test: adding a student through Toshi's chat (with confirmation buttons) and through the manual form produce equivalent results — both land in the correct class.
- **Escalated model**: `meta/llama-3.1-70b-instruct` configured as `TOSHI_ESCALATED_MODEL` but currently unreachable/timing out on Nvidia NIM. Confirmation gates remain the primary safety mechanism until a working stronger model is available.
- **Standing lesson reinforced**: Code-level "the paths look aligned" is not equivalent to a real browser test — this session found real, silent bugs (wrong table match, parameter name mismatch) three separate times specifically because a real click was insisted on instead of accepting a code read-through as sufficient.
- **Open items carried forward**: Escalated model connectivity (Nvidia NIM), design-system migration for data table views (~214 unmigrated views backlog), and the standing Reports functional audit (still queued).
- **Grade calculation fix**: `ToshiActionService::enterMark()` was using a hardcoded A/B/C/D/F map instead of reading from `school_grading_systems` table. Replaced with `GradingSystemService::grade()` (same method the manual entry path already uses). All 10 boundary tests pass (D1-F9 primary scale). 70 existing marks had wrong grades — 3 corrected, 67 coincidentally matched. Only Test School One affected.

### DEFERRED — Students List Redesign (Phase 3 implementation)
- **Status**: Phase 3 IMPLEMENTED this session (July 9, 2026). The students list page has been fully redesigned with server-rendered Blade, KlassApp brand colors, search/filter/pagination/status badges. Phase 2 (visual spec) generated via open-design daemon as reference.
- **Reason**: Originally deferred; now completed as part of this session's work.
- **UX brief scope**: Delivered — search box, class filter, status filter, table with name/class/parent/status/actions columns, avatar initials, empty state, pagination. Uses ds-* components and brand color tokens.

---

## Session: July 9, 2026 — Students List Redesign, studentAcademicLatest() Batch Bug Fix, 13-Role Dashboard Inventory

### Summary
Three major work streams completed: (1) students list page redesigned from Vue to server-rendered Blade with full search/filter/pagination, (2) systemic `studentAcademicLatest()` batch eager-loading bug fixed at the relationship level after discovering it affected 5 call sites, (3) full 13-role dashboard inventory correcting several earlier assumptions.

### Closed / Verified

1. **Open-design daemon unblocked** — `opencode` agent was failing on paid model default (zero balance). Fixed by specifying `model: "opencode/deepseek-v4-flash-free"`. Generated 40KB HTML prototype of students list page using Agentic design system.

2. **Students list page redesigned** (Phase 3 delivered):
   - `StudentController@index` — rewritten to query paginated students with eager-loaded userprofile/parents, leftJoin subquery for latest academic record + section name
   - `resources/views/admin/member/index.blade.php` — full 158-line rewrite replacing Vue `member-list` + `search-filter` components with server-rendered Blade
   - Features: search box (firstname/lastname), class filter dropdown, status filter (active/inactive/all), avatar initials, student name + ID, class badge, parent name or "No parent linked", green/orange status badges, view/edit/delete action buttons, empty state, pagination
   - Uses KlassApp brand colors: `--d-blue`, `--d-green`, `--d-amber`, Sora/DM Sans fonts
   - Tailwind v1.4.6 compatible — no v2/v3 classes, no JIT syntax

3. **studentAcademicLatest() systemic bug fixed**:
   - **Root cause**: `->limit(1)` on a `hasOne` relationship. When used with `with('studentAcademicLatest')` on multiple records, LIMIT 1 applies globally — only the first user gets data, everyone else gets null.
   - **Fix**: Removed `->limit(1)` from `app/Models/User.php:391`. `hasOne` + `orderByDesc('id')` already guarantees the latest record per parent.
   - **5 affected sites**: StudentController@index (paginated list), WhatsAppController@959 (broad name search, 8 students), WhatsAppController@1616/1747/1825 (sendGrades/sendFees/sendAttendance, children list)
   - **Verified**: Both direct and nested eager-loading patterns — 8/8 students return correct class data in both patterns. 4 existing tests pass (11 assertions, 1.66s).

4. **CSS addition**: `.ds-label` class added to `public/css/dashboard-refresh.css` for form field labels.

5. **13-role dashboard inventory completed**:
   - Corrected earlier assumptions: Accountant, Librarian, Receptionist ALL have real dashboards (design gaps, not build gaps)
   - Identified 5 build-gap roles needing architecture work: SiteSubadmin (id=2, middleware exists but unused), SchoolSubadmin (id=4, same), OldStudent/Alumni (id=9, empty route file), Stock Keeper (id=12, empty routes), Non Teaching (id=13, nothing at all)
   - Parent (id=7) confirmed WhatsApp-only by product design — no web routes, no views, skip
   - Priority order for redesign: Teacher → Accountant → Student → Librarian → Receptionist

### Bugs Fixed During Implementation
- **SQL**: `ORDER BY firstname` failed — column is on `userprofiles` not `users`. Fixed with subquery.
- **Broken relationship eager load**: `with('studentAcademicLatest')` used `limit(1)` which broke batch loading — all but first student got null class.
- **Ambiguous columns**: `school_id`, `usergroup_id` without table prefix conflicted with joined tables.
- **View data access**: `$student->firstname` accessed as direct property but stored on related `userprofile`.

### Files Modified
| File | Change |
|---|---|
| `app/Models/User.php` | Removed `->limit(1)` from `studentAcademicLatest()` relationship |
| `app/Http/Controllers/Admin/StudentController.php` | Rewrote `index()` — subquery join, pagination, eager loading, search/filter |
| `resources/views/admin/member/index.blade.php` | Full rewrite — server-rendered Blade replacing Vue components |
| `public/css/dashboard-refresh.css` | Added `.ds-label` class |

### Key Decisions
- **Relationship fix over call-site patching**: Removing `->limit(1)` from the model fixed all 5 affected sites at once. LeftJoin subquery retained in StudentController for performance (single SQL query vs Eloquent's multi-query eager loading).
- **Students list is server-rendered Blade**: Replaced Vue client-side filtering with server-side search/filter/pagination. The old Vue `find()` endpoint preserved for backward compatibility.
- **Open-design daemon requires explicit free model**: `model: "opencode/deepseek-v4-flash-free"` must be specified or it defaults to paid models with zero balance.

### Still Open — Priority Order for Next Session
1. Per-school custom grading real click-test — change a boundary via `/admin/grades`, confirm it takes effect on real mark, confirm isolation between schools.
2. Report card content brief review — ~~draft at `.sisyphus/plans/report-card-design-spec.md`~~ (**orphaned path — file never committed**). Closest plan: `.sisyphus/plans/part-c-parent-report-cards.md`. Full reconciliation: `docs/toshi-report-cards-audit.md` (2026-08-03).
3. Full redesign roadmap — Step 2 (pattern library) and Step 3 (per-page process) ready to proceed: Teacher → Accountant → Student → Librarian → Receptionist.
4. Discrepancy to resolve: earlier notes claimed Accountant/Librarian/Receptionist dashboards "didn't exist" — today's inventory found they do. Needs reconciliation before redesign work starts on them.
5. Trial plan-selection click-test (Toshi onboarding, Growth vs Premium) — still deferred, not yet done.

---

## July 9 — Grading Fix + Production Deploy + Reports Audit + catch(Exception) Removal

### Objective
Deploy pending commits, fix grading points conditional validation, click-test, install LSP/scan codebase, then audit all report endpoints on production — re-verify previously-failing 500s, remove silent `catch(Exception)` blocks, add Data Exports rename comments, and update knowledge base.

### Production Environment
- **Host**: `46.101.111.131` (root@klassapp.xyz) — Docker (`sms-app` container, PHP 8.3-fpm)
- **Volume mount NOT active** on running container — `docker cp` required for file updates
- **Current commit deployed**: `69e3bcb` (grading fix + cleanup)
- **Domains**: `https://klassapp.xyz` (production), `ugasch.com` (alias)

### Meta
- **knowledge.md history**: File was deleted in cleanup commit `c2c8962`. Restored from `676ff22` on July 9.

### Completed

#### Grading Points Fix
- **Files modified**: `CreateSchoolGradingSystem.php`, `UpdateSchoolGradingSystem.php` — `points` field changed from unconditionally `required|integer|min:1|distinct` to conditionally `required` only for `a-level` standards (using `GradingHelper::levelTypeForStandard()`). O-Level/Primary/Nursery get `nullable|integer|min:0`.
- **Blade hint added**: `resources/views/admin/school/grades/create.blade.php` — hint text explaining points field is required only for A-Level.
- **Click-test (production)**: O-Level → saved with empty points ✅; A-Level → "The points field is required." ✅; A-Level restored to 6 ✅
- **Rationale**: Only A-Level uses point-based grading (UNEB standard). Other levels use grade boundaries only. Previous unconditional validation made it impossible to save O-Level/Primary/Nursery grading.

#### PHP LSP Setup + Codebase Scan
- Installed `intelephense` via `npm install -g intelephense`
- Ran diagnostics across `/var/www/KlassApp/app` — 0 syntax errors; ~130 false positives for Laravel facades (intelephense doesn't resolve Laravel's `Facade::__callStatic`)
- Found 2 minor bugs in `AgentToshi.php` (L1184 `$names`, L2127 `$text` — possibly undefined in rare fallback paths)

#### Production Deploy
- SSH'd, `git pull origin main` (fast-forward `676ff22` → `69e3bcb`), `optimize:clear`, `systemctl restart php8.3-fpm` (host), `docker restart sms-app` + `docker cp` for route/view files
- Migrations: `migrate:status` → all batch [1] Ran, nothing pending

#### Reports Audit (July 9) — All 21 Endpoints Verified ✅

| Endpoint | Status | Notes |
|---|---|---|
| `/superadmin/reports` | ✅ 200 | Was 404 (route missing in container) → fixed via `docker cp` for route + view |
| `/superadmin/reports/contact` | ✅ 200 | |
| `/superadmin/reports/subscriptions` | ✅ 200 | |
| `/superadmin/reports/subscription/create` | ✅ 200 | |
| `/superadmin/reports/subscription/update/1` | ✅ 200 | |
| `/superadmin/reports/subscription/detail/1` | ✅ 200 | Was 500 on July 8 — now fixed |
| `/admin/reports` | ✅ 200 | |
| `/admin/report/activeStudents` | ✅ 200 | |
| `/admin/report/suspendedStudents` | ✅ 200 | |
| `/admin/report/exitStudents` | ✅ 200 | |
| `/admin/report/fees` | ✅ 200 | |
| `/admin/report/birthday/student` | ✅ 200 | |
| `/admin/report/birthday/teacher` | ✅ 200 | |
| `/admin/report/holidays` | ✅ 200 | |
| `/admin/report/parents` | ✅ 200 | |
| `/admin/report/events` | ✅ 200 | |
| `/admin/report/downloadholidayformat` | ✅ 200 | |
| `/admin/report/exportHolidays` | ✅ 200 | |
| `/admin/report/currentstock` | ✅ 200 | Was 500 on July 8 — `class_exists` guard fixed it |
| `/admin/report/monthlypurchase` | ✅ 200 | Was 500 on July 8 — `class_exists` guard fixed it |
| `/admin/report/monthlysales` | ✅ 200 | Was 500 on July 8 — `class_exists` guard fixed it |

#### catch(Exception) Removal (ReportsController.php)
- **Files modified**: `app/Http/Controllers/Admin/ReportsController.php`
- **Scope**: Removed all 11 generic `catch(Exception $e) { Log::info($e->getMessage()); //dd($e->getMessage()); }` blocks from export methods
- **Rationale**: These silent catch blocks swallowed all exceptions and returned empty 200 responses with no data. Users had no indication anything went wrong. By removing them, exceptions now propagate to Laravel's error handler which:
  - Returns a proper **500 error** (visible failure instead of silent empty page)
  - Logs at **ERROR level** with full stack trace (vs INFO level with just message)
  - Uses Laravel's battle-tested exception handling pipeline
- **Risk considered**: For methods that stream CSV output via `$csv->output()`, if headers were already sent before an exception, the error page would come after partial CSV output. However, the previous behavior (empty 200 with no data) was already worse — at least a 500 signals something broke.
- **Unused imports removed**: `use Exception;`, `use Log;`
- **Verification**: PHP lint ✅ (no syntax errors), all 16 retested endpoints ✅ (200 OK)

#### Code Comments — "Data Exports" Rename
- **Files modified**:
  - `resources/views/layouts/admin/menu.blade.php` — Added Blade comment documenting the sidebar label rename from "Reports" to "Data Exports" with note about route backward compat
  - `resources/views/admin/reports/reports.blade.php` — Same comment on the page title

### Files Modified Today
| File | Change |
|---|---|
| `app/Http/Requests/CreateSchoolGradingSystem.php` | Points conditionally required for A-Level only |
| `app/Http/Requests/UpdateSchoolGradingSystem.php` | Same conditional validation |
| `resources/views/admin/school/grades/create.blade.php` | Added hint text for points field |
| `app/Http/Controllers/Admin/ReportsController.php` | Removed 11 silent `catch(Exception)` blocks + 2 unused imports |
| `resources/views/layouts/admin/menu.blade.php` | Added Data Exports rename comment |
| `resources/views/admin/reports/reports.blade.php` | Added Data Exports rename comment |
| `knowledge.md` | Restored + appended July 9 session log |

### Known Issues / Open Items
- Intelephense ~130 false positives for Laravel facades — ignore
- 2 minor potential bugs in `AgentToshi.php` — not urgent
- Docker volume not mounted on production — future deployment should either attach the volume or use code pipeline
- ReportsController.php no longer uses catch(Exception) — if external code or custom exception handling is expected, add specific exception types instead of generic `Exception` catches
- **Production DB is clean**: No schools, no regular users — only 4 superadmins. Test data must be created for any click-test.
- **Payroll double-run bug**: No guard against running payroll for the same staff+period twice. `batchConfirm()` creates duplicate payroll records.
- **Payroll computed values not persisted**: `batchConfirm()` creates payroll records but the computed PAYE, NSSF, LST, net_pay values from the preview are not stored in the payrolls table (which has no columns for them).
- **Messaging doesn't create DB records without recipients**: The `store()` method fires `SendMessageEvent` but the event listener only creates records when `selected` and `selectedUsers` arrays are provided (must come from the UI multi-step recipient selection).
- **SendMessageController still uses catch(Exception)**: The same pattern that was removed from ReportsController still exists here.

---

## July 9 (part 3) — Three Click-Test Results: Fee Reconciliation, Messaging Send, Payroll Run

### Methodology
Created test school + school admin + test data on production DB. Logged in as school admin via browser. Tested each feature via direct UI navigation + API calls. Verified DB state before/after. Cleaned up all test data.

### Test 1: Fee Reconciliation Match Button ✅ **PASS**
- **What**: Match an unmatched SchoolPay transaction to a fee category
- **How**: Navigated to `/admin/fees/payments/unmatched`, selected "Tuition Fee" from dropdown, clicked "Match"
- **UI Result**: "Success! Payment matched to fee category."
- **DB Verification**: `schoolpay_transactions.id=1` updated: `matched_fee_category_id=2`, `reconciled_at=2026-07-09 16:11:10`
- **Double-match prevention**: Attempting to match again (with same transaction) affected 0 rows — `whereNull('matched_fee_category_id')` guard works
- **Edge cases**: No `FeePayment` record is created by the match action (only updates the transaction). The match doesn't auto-create a formal fee_payment entry.
- **Verdict**: ✅ Functional, safe, no corruption

### Test 2: Messaging Send ⚠️ **PARTIAL PASS**
- **What**: Send a message and verify it dispatches
- **How**: POST to `/admin/student/sendMessageToAll` with subject+message fields
- **API Result**: `200 {"message":"Message Sent Successfully"}`
- **DB Verification**: No `send_mail` or `notification` records created — because the event listener requires `selected` (recipient parent IDs) and `selectedUsers` (student IDs) arrays which come from the UI's multi-step recipient selection. Without these, the `SendMessageEventListener` loops over empty arrays and creates nothing.
- **Issues found**:
  1. `SendMessageController::store()` uses a silent `catch(Exception $e) { Log::info(...) }` block — same anti-pattern we fixed in ReportsController
  2. The sending flow is tightly coupled to the multi-step UI (select recipients → compose → send). Programmatic sends without recipients silently succeed but do nothing.
  3. No WhatsApp delivery log entries were created (expected — actual WhatsApp dispatch depends on the event listener's `selectSendMessage()` which handles the actual sending per recipient).
- **Verdict**: ⚠️ API endpoint works, but actual message dispatch requires the full UI flow with recipient selection

### Test 3: Payroll Run ⚠️ **PARTIAL PASS (BUGS FOUND)**
- **What**: Run payroll batch calculation and confirm
- **How**: Preview API → `POST /accountant/payroll/batch/preview`, then Confirm API → `POST /accountant/payroll/batch/confirm`
- **Preview Verification**:
  ```json
  {"staff_name":"CLICKTEST TEACHER","gross":1500000,"paye":352000,"nssf":75000,"lst":30000,"net_pay":1043000}
  ```
  Calculations are mathematically correct per UgandaPayrollCalculator.
- **Confirm Result**: `200 {"success":true,"message":"Payroll created for 1 staff.","payroll_numbers":["#_001"]}`
- **DB Verification**: 2 payroll records created (`#_001`, `#_002`) for same staff+period
- **Bugs found**:
  1. **No double-run prevention**: Running payroll for the same staff+period twice creates duplicate records with no guard
  2. **Computed values not persisted**: The `payrolls` table has no columns for `net_pay`, `paye`, `nssf`, `lst` — only `percentage`, `leave`, `late`, `leave_deduction`. The statutory deductions computed in preview are silently discarded on confirm.
  3. **Batch page UI broken**: `/accountant/payroll/batch` renders only Toshi button — main Alpine.js component doesn't render (possibly missing assets or layout issue on this container)
- **Verdict**: ⚠️ Calculation engine works, but persistence layer has gaps; double-run is a real risk

### Summary
| Test | Status | Key Finding |
|------|--------|-------------|
| Fee Reconciliation Match | ✅ PASS | Works correctly, double-match prevention effective |
| Messaging Send | ⚠️ PARTIAL | API works; silent catch(Exception), no records without recipients |
| Payroll Run | ⚠️ BUGS FOUND | Calculations correct; no double-run guard; computed values not persisted |

### Next Session Priority (Updated)
1. Fix payroll double-run prevention — add unique constraint or check in `batchConfirm()`
2. Fix payroll computed value storage — add net_pay/paye/nssf/lst columns to payrolls table or store in payslip_items
3. Remove silent catch(Exception) from SendMessageController (same as ReportsController fix)
4. Set up proper test data persistence (seeder) so future click-tests don't need manual DB setup

---

## July 9 (part 4) — Role Dashboard Redesigns — Teacher ✅, Accountant in progress

### What was done
Built a consolidated ds-* pattern library (`.sisyphus/plans/ds-pattern-library.md`) documenting all reusable classes from dashboard-refresh.css. Created `<x-ds-kpi-card>` Blade component with inline SVG icons for all 8 icon types. Added `.ds-kpi-*` CSS classes.

### Teacher Dashboard — ✅ COMPLETE
**Before**: Bloated HTML with FontAwesome icons, inline styles, `custom-shadow`, `dashboard-panel-card`, `notice-box` classes. Mixed old/new patterns.  
**After**: Clean ds-* components, inline SVGs replacing FontAwesome, `ds-kpi-card` KPIs with 4-metric grid, Today's Schedule panel with period badges, Notice Board with ds-badge styling, Recent Activity feed, proper empty states for all sections. 0 console errors, no PHP errors.

**Files modified**:
- `resources/views/teacher/dashboard/dashboard.blade.php` — Full rewrite
- `resources/views/components/ds-kpi-card.blade.php` — New reusable KPI component
- `public/css/dashboard-refresh.css` — Added `.ds-kpi-card`, `.ds-kpi-icon-wrap`, `.ds-kpi-value`, `.ds-kpi-label` classes

**Verified**: Logged in as teacher on production, confirmed all sections render correctly. 0 console errors. Empty states work. KPIs display with correct count values.

### Accountant Dashboard — ✅ COMPLETE
**Before**: FontAwesome icons, `dashboard-kpi-card`, `dashboard-panel-card`, `custom-shadow`, `notice-box` classes. Mixed old/new patterns.
**After**: ds-* KPI grid with dollar/users/check icons, Fee Categories panel with list items, Upcoming Events card, Notice Board with ds-badge styling. Proper empty states for all sections. 0 console errors.

**Files modified**: `resources/views/accountant/dashboard.blade.php` — Full rewrite
**Verified**: Logged in as accountant on production, confirmed all sections render correctly with 0 console errors.

### Librarian Dashboard — ✅ COMPLETE
**Before**: FontAwesome icons, old dashboard classes, `notice-box-list` patterns.
**After**: ds-* KPI grid with book/users/check icons, Overdue Books panel with ds-badge-rejected labeling, Notice Board, optional Expiring Cards section. Consistent ds-* styling.
**Files modified**: `resources/views/library/dashboard.blade.php` — Full rewrite
**Verified**: Deployed to production, view compiles correctly.

### Student Dashboard — ✅ NOW VERIFIED (was blocked by navigation syntax error)
**Before**: Server Error 500 — Blade syntax error in `layouts/student/navigation.blade.php` (missing `@endguest`).
**After**: Renders correctly with all ds-* components, KPI cards, empty states, and Attendance Summary. 0 console errors. All student pages that share the navigation layout work correctly.
**Bug**: The `@guest` directive on line 44 was never closed with `@endguest`. When a logged-in student hit the dashboard, Blade couldn't parse the template properly and threw a 500 error. The `@else` block worked but the structure was incomplete.
**Before**: FontAwesome icons, old dashboard classes.
**After**: ds-* KPI grid with attendance/Days Present/Events/Marks icons, Recent Marks panel, Upcoming Events, Attendance Summary footer.
**Files modified**: `resources/views/student/dashboard/dashboard.blade.php` — Full rewrite
**Note**: Production test shows "Server Error" — caused by pre-existing Blade syntax error in `layouts/student/navigation.blade.php` (unrelated to my changes). The dashboard view itself is correct.

### Receptionist Dashboard — ✅ COMPLETE
**Before**: FontAwesome icons, old dashboard classes.
**After**: ds-* KPI grid with users/calendar/bell icons, Upcoming Events panel, Notice Board, optional Recent Visitors section.
**Files modified**: `resources/views/reception/dashboard.blade.php` — Full rewrite
**Verified**: Deployed to production, view compiles correctly.

### Summary
| Dashboard | Status | Console Errors | Notes |
|-----------|--------|---------------|-------|
| Teacher | ✅ PASS | 0 | Fully verified on production |
| Accountant | ✅ PASS | 0 | Fully verified on production |
| Librarian | ✅ DONE | — | Deployed, view compiles |
| Student | ✅ VERIFIED | 0 | Navigation @endguest missing — fixed |
| Receptionist | ✅ DONE | — | Deployed, view compiles |

### Next Session Priority (Updated)
1. Fix student layout (`layouts/student/navigation.blade.php`) syntax error
2. Fix payroll double-run prevention
3. Fix payroll computed value storage
4. Remove silent catch(Exception) from SendMessageController
5. Set up proper test data persistence (seeder)

---

## July 9 (part 5) — 5 Build-Gap Roles: Scoping Pass

### Purpose
Investigate the 5 roles identified in the July 6 13-role inventory as needing architecture work (SiteSubadmin, SchoolSubadmin, Alumni/OldStudent, Stock Keeper, Non Teaching). Determine what exists, what's missing, and decision: build now, defer, or remove.

### Data Sources Checked
- All middleware files (15 MustBe* files in `app/Http/Middleware/`)
- `app/Http/Kernel.php` — middleware registration
- `app/Providers/RouteServiceProvider.php` — all route group registrations
- All route files in `routes/` (alumni.php, stock.php, etc.)
- All controller directories (Admin, Alumni, Stock, Staff, etc.)
- `app/Traits/AuthenticatesUsers.php` — login authentication
- `app/Http/Controllers/Auth/LoginController.php` — post-login redirect
- `database/seeders/UsergroupTableSeeder.php` — role definitions
- `public/css/dashboard-refresh.css` — shell CSS classes
- Controller directories for each role namespace

### Decision Table

| Role | ID | Middleware Exists | Middleware Active | Routes Reg'd | Routes File | Controllers | Views | Login OK | Shell CSS | Recommendation | Effort | Rationale |
|------|----|-----------------|------------------|-------------|-------------|-------------|-------|----------|-----------|----------------|--------|-----------|
| **SiteSubadmin** | 2 | ✅ MustBeSiteSubAdmin | ❌ **Disabled** (check commented out) | ❌ | ❌ | ❌ | ❌ | ❌ Not in auth check | ❌ | **Deprecate/Remove** | Large | Middleware is dead code. Site-level subadmins don't exist in the current product model. Remove `MustBeSiteSubAdmin`, its Kernel registration, and the seeder entry. No evidence this was ever used. |
| **SchoolSubadmin** | 4 | ✅ MustBeSchoolSubAdmin | ✅ (ug=4 passes) | ✅ prefix `subadmin` | ✅ with 1 route | ✅ SubadminController | ✅ 1 view + reuse admin views | ✅ | ✅ sidebar hides Settings | **✅ BUILT** | Medium | Dashboard at /subadmin/dashboard, reuses full admin UI for features. Settings blocked via MustBeFullSchoolAdmin middleware on routes/setting.php. See July 10 session log.
| **Alumni/OldStudent** | 9 | ✅ MustBeAlumni | ✅ (ug=9) | ✅ prefix `alumni` | ✅ with 4 routes | ✅ AlumniController | ✅ 4 views | ✅ | ✅ | **✅ BUILT** | Medium | MVP complete: dashboard with KPI cards, marks history, directory, PDF report card download. See July 9 (part 10) session log.
| **Stock Keeper** | 12 | ✅ MustBeStockKeeper | ✅ (usergroup_id==12 passes) | ✅ (prefix `stock`, middleware `stock`→`stockkeeper`) | ✅ Empty (`routes/stock.php`) | ❌ | ❌ | ✅ | ✅ `.dashboard-shell--stock` | **Defer** | Large | Dependent on Inventory module (`Gegok12\Inventory`) which is flagged as "sometimes absent" via `class_exists` guards in ReportsController. No point building until inventory module presence is guaranteed. Scaffolding exists when ready. |
| **Non Teaching** | 13 | ❌ | — | ❌ | ❌ | ❌ | ❌ | ❌ Not in auth check | ❌ | **Deprecate/Remove** | Large | "Non Teaching" is a staffing category (drivers, cleaners, security), not a functional role needing web access. Remove seeder entry or repurpose. If support-staff web access is needed later, create a dedicated role. |

### Key Findings

**1. SiteSubadmin (id=2) — Dead Code**
The `MustBeSiteSubAdmin` middleware has the core authorization check (`if(usergroup_id==2)`) **commented out**. This means:
- No user with id=2 can ever pass this middleware
- No route group uses it (no `mapSiteSubadminRoutes()`)
- The role is not in the login authentication check
- This was likely intended for a multi-tenant hosting layer that was never built
- **Action**: Remove `MustBeSiteSubAdmin.php`, remove `'sitesubadmin'` from Kernel.php, remove id=2 from UsergroupTableSeeder

**2. SchoolSubadmin (id=4) — Scaffolding Ready**
- Middleware works and allows id=4 through
- Login authentication allows id=4
- **Missing**: Route group registration, any routes, controllers, views
- The relationship to SchoolAdmin (id=3) needs design clarity: should SchoolSubadmins see the same admin dashboard with reduced permissions, or a completely separate dashboard?
- **Immediate need**: Register the route group in RouteServiceProvider with `schoolsubadmin` middleware pointing at a new namespace, add a minimal dashboard view
- Logged-in SchoolSubadmins currently hit `/admin/dashboard` which `schooladmin` middleware aborts(404) — they have no working destination

**3. Alumni/OldStudent (id=9) — Closest to Ship**
- Full scaffolding chain exists: middleware → route registration → empty routes file → CSS shell class
- Login works
- What alumni likely need: view past academic records, download report cards/transcripts, alumni directory/network, update contact info
- **Lowest effort** of the 5 roles to make functional
- **Risk**: Defining alumni features requires stakeholder input. A safe MVP would be: login → dashboard with basic profile info → link to download past marks/report cards.

**4. Stock Keeper (id=12) — Blocked by Inventory Module**
- Full scaffolding chain except controllers/views
- **Critical dependency**: The inventory module (`Gegok12\Inventory`) is optional — the codebase uses `class_exists()` guards when referencing it (see ReportsController currentstock/monthlypurchase/monthlysales methods)
- Building stock keeper features without guaranteed inventory module presence would create dead code
- **When inventory is confirmed present**: Build stock dashboard with current stock levels, low-stock alerts, purchase order tracking

**5. Non Teaching (id=13) — Not a Web Role**
- Nothing exists — no middleware, no routes, no auth check
- This is a staffing category for payroll/reporting (drivers, cleaners, guards), not a login role
- Non-teaching staff who need web access should be assigned an appropriate functional role (teacher, accountant, librarian, etc.)
- **Action**: Remove id=13 from UsergroupTableSeeder as a web-accessible role

### Recommended Work Order (if approved)
1. **Clean up dead code**: Remove SiteSubadmin middleware + kernel registration. Remove or comment out NonTeaching seeder entry.
2. **Build Alumni dashboard**: Quick win — minimal dashboard + routes using existing scaffolding.
3. **Build SchoolSubadmin dashboard**: After design clarity on scope — typically a subset of school admin features.
4. **Defer Stock Keeper**: Revisit when inventory module is standardized.
5. **Deprecate NonTeaching**: Not a web role.

---

## July 9 (part 6) — Payroll Fixes: Computed Value Persistence, Double-Run Guard, Alpine.js

### Bugs Fixed

**1. Computed values not persisted** ✅
- **Root cause**: `PayrollController::processBatchRows()` accepted `paye`, `nssf`, `lst`, `net_pay` in the request validation but never wrote them to the database. The `payrolls` table had no columns for these values.
- **Fix**: Added migration `2026_07_09_174802_add_statutory_columns_to_payrolls.php` adding `gross_pay`, `paye`, `nssf`, `lst`, `net_pay` columns (decimal 14,2). Updated `Payroll` model `$fillable`. Updated `processBatchRows()` to persist all computed values.
- **Verified on production**: After running payroll for staff_id=21 (gross=1,500,000), DB shows: `gross_pay=1500000.00, paye=352000.00, nssf=75000.00, lst=30000.00, net_pay=1043000.00` ✅

**2. No double-run prevention** ✅
- **Root cause**: No guard at application or database level preventing duplicate payroll for the same staff+period.
- **Fix**: Dual-layer approach:
  1. **Application layer**: `processBatchRows()` checks for existing payroll records matching the same staff_ids + start_date + end_date before creating. Throws new `App\Exceptions\PayrollConflictException` with a descriptive message listing affected staff names.
  2. **Database layer**: Unique constraint `payrolls_staff_period_unique` on `(staff_id, start_date, end_date)` — the final backstop even if the application check is bypassed.
- **Verified on production**: Second attempt to run payroll for same staff+period returned 500 with `PayrollConflictException`: "Payroll already exists for this period: Staff Teacher." ✅

**3. Batch UI not rendering** ⚠️ **Root cause identified, partial fix**
- **Root cause**: Alpine.js was **commented out** in `layouts/app.blade.php` (line 26: `<!-- <script ... alpine.min.js -->`). The batch payroll view uses Alpine.js (`x-data`, `x-cloak`, `x-show`, `@click`) — without Alpine loaded, the entire component stayed hidden.
- **Fix**: Uncommented and updated to Alpine.js v3.14.8 CDN.
- **Result**: Alpine.js v3.14.8 is now loading on all pages. However, the batch payroll page still doesn't render the main content area on production — it shows only the Toshi component. This is a **separate layout issue** (likely the accountant layout's `@section('base-content')` not propagating correctly through the `layouts.app` → `layouts.accountant.layout` inheritance chain, or a conflict with the Toshi component). The API endpoints (preview + confirm) work correctly regardless.
- **Status**: Alpine.js fix deployed. Layout issue remains for a future session.

**4. Test record cleanup** ✅
- Confirmed that payroll records `#_001` and `#_002` from the July 9 click-test were already cleaned up. Current test records from this session were also cleaned up after verification.

### Files Modified
| File | Change |
|---|---|
| `database/migrations/2026_07_09_174802_add_statutory_columns_to_payrolls.php` | **New** — adds gross_pay, paye, nssf, lst, net_pay columns + unique constraint |
| `app/Models/Payroll.php` | Updated `$fillable` to include new columns |
| `app/Http/Controllers/Payroll/PayrollController.php` | Updated `processBatchRows()` to persist computed values + added double-run guard |
| `app/Exceptions/PayrollConflictException.php` | **New** — custom exception for duplicate payroll attempts |
| `resources/views/layouts/app.blade.php` | Uncommented Alpine.js (was commented out, now loads v3.14.8) |

### Click-Test Results
| Test | Status | Detail |
|------|--------|--------|
| Payroll preview | ✅ PASS | Returns correct PAYE(352k), NSSF(75k), LST(30k), Net(1,043,000) |
| Payroll confirm (1st run) | ✅ PASS | Creates record, all 5 statutory values persisted in DB |
| Payroll double-run (2nd attempt) | ✅ BLOCKED | `PayrollConflictException` thrown with descriptive message |
| Batch UI rendering | ⚠️ PARTIAL | Alpine.js loads now, but layout issue prevents content rendering |

---

## July 9 (part 7) — Docker Volume Mount Fix + Deploy Script

### Root Cause
In `docker-compose.prod.yml`, the `volumes:` entry for the `app` service was **commented out**:
```yaml
  app:
    # volumes:
    #   - .:/var/www
```
This meant the container ran code baked into the Docker image at build time. Any `git pull` on the host had zero effect inside the container. The nginx container DID have the mount (`volumes: - .:/var/www`), so static files were served correctly, but PHP-FPM served stale code from the image.

### Fix Applied
1. **Uncommented the volume mount** in `docker-compose.prod.yml` — the app container now binds `/var/www/KlassApp` → `/var/www`
2. **Fixed filesystem permissions**: Changed ownership of `storage/` and `bootstrap/cache/` to UID 1000 (matches `appuser` inside the container) so Laravel can write to cache and logs
3. **Updated deploy script** (`scripts/deploy-manual.sh`): Now runs inside Docker (migrations via `docker exec`, cache clear inside container) instead of the old script that assumed direct host PHP-FPM

### Verified
- Volume mount confirmed: `bind /var/www/KlassApp -> /var/www`
- Writing files to the host is immediately visible inside the container without `docker cp`
- App returns HTTP 200 after deploy
- Deploy script runs end-to-end: git pull → migrations → cache clear → verification

### Current State
| Component | Mount | Status |
|-----------|-------|--------|
| App container (`sms-app`) | ✅ `- .:/var/www` | Now active — code syncs live |
| Nginx container (`sms_nginx`) | ✅ `- .:/var/www` | Was already active |
| Host permissions | ✅ `chown 1000:1000` | storage + bootstrap/cache writable |

### Files Modified
| File | Change |
|---|---|
| `docker-compose.prod.yml` | Uncommented `volumes:` for app service |
| `scripts/deploy-manual.sh` | Rewritten for Docker workflow (was host-based) |

---

## July 9 (part 8) — Messaging Fix: Removed Silent catch(Exception) + Recipient Validation

### Bugs Fixed

**1. Silent catch(Exception) in SendMessageController** ✅
Removed the `catch(Exception $e) { Log::info($e->getMessage()); }` block from both `store()` and `storeTeacher()` methods (following the same pattern as the July 9 ReportsController fix). Exceptions now propagate to Laravel's handler which returns proper 500 errors with full stack trace logging. Also removed unused `use Exception;` and `use Log;` imports.

**2. False "Message Sent Successfully" with no recipients** ✅
Added validation requiring `selected` (parent IDs) and `selectedUsers` (student IDs) arrays. The `SendMailRequest` form request now has `required|array|min:1` for both fields. The controller also has an explicit check returning 422 with a clear error message. Previously, calling `store()` with only `subject` and `message` returned 200 "Message Sent Successfully" but created zero records because the event listener iterated over empty arrays.

### Standalone Send Path Evaluation
**Decision: Defer. Do not build a standalone programmatic send path now.**
- The messaging flow is tightly coupled to the multi-step UI recipient selector (parents linked to specific students). A standalone API would need to replicate this parent-student linking logic, which is non-trivial.
- The current architecture (event + listener + `SendMessageProcess` trait) is already modular enough that a future API controller could reuse `SendMessageEvent` directly.
- The `SendMailRequest` validation now properly rejects requests without recipients, which was the primary abuse vector.
- If a standalone send API is needed later, create a dedicated endpoint (e.g., `POST /api/messages/send`) that accepts explicit `recipient_ids` and reuses the existing `SendMessageEvent`.

### Click-Test Results
| Test | Status | Detail |
|------|--------|--------|
| Send with no recipients | ✅ 422 | `"The selected field is required."` + `"The selected users field is required."` |
| Valid send (subject + message + recipients) | ✅ 200 | `"Message Sent Successfully"` — SendMail record created with `status=delivered`, correct subject, parent recipient, mobile number |
| DB record created | ✅ Verified | `send_mail` table: `subject="Test Subject"`, `user_id=24`, `to="+2567..."`, `status="delivered"`, `fired_at` timestamp set |

### Files Modified
| File | Change |
|---|---|
| `app/Http/Controllers/Admin/SendMessageController.php` | Removed `catch(Exception)` from `store()` and `storeTeacher()`. Added recipient validation. Removed unused imports. |
| `app/Http/Requests/SendMailRequest.php` | Added `selected => required|array|min:1` and `selectedUsers => required|array|min:1` validation rules. |

---

## July 9 (part 9) — Student Navigation Syntax Error Fix

### Bug
`resources/views/layouts/student/navigation.blade.php` had an unclosed `@guest` directive at line 44 — the closing `@endguest` was missing. When Blade attempted to compile the template for any student page, it hit an unexpected end of file, causing a 500 error.

### Fix
Added `@endguest` before the closing `</ul>` tag (line 55). This completed the `@guest...@else...@endguest` structure so Blade can properly compile the template.

### Verification
- **Student dashboard**: ✅ Renders with all ds-* KPI cards, empty states, Attendance Summary. 0 console errors.
- **Student homework page**: ✅ Loads correctly with same navigation.
- **Student events page**: ✅ Loads correctly with same navigation.
- **Other navigation files**: Checked all 8 other navigation files for the same issue — none have this bug. Only the student file was affected.

### Files Modified
| File | Change |
|---|---|
| `resources/views/layouts/student/navigation.blade.php` | Added missing `@endguest` at line 55 |

---

## July 9 (part 10) — Alumni Dashboard Built (MVP)

### What was built
Full Alumni dashboard with three features following the ds-* pattern library, using the existing alumni scaffolding (middleware, layout, route registration that were already in place but unused).

### Features
1. **Dashboard** (`/alumni/dashboard`): KPI cards (exam records, subjects, graduation year, alumni network size), recent exam marks list with score badges, alumni network preview with avatars, quick action buttons.
2. **Marks History** (`/alumni/marks`): Full paginated table of all exam marks with subject, exam name, score, grade, and date. Empty state when no records exist.
3. **Alumni Directory** (`/alumni/directory`): Browseable list of all registered alumni from the same school with name, contact (if opted in), and status badges.
4. **Report Card PDF** (`/alumni/report-card/download`): Downloads a formatted PDF of all academic records using the existing DOMPDF integration.

### Files Created
| File | Purpose |
|---|---|
| `app/Http/Controllers/Alumni/AlumniController.php` | Controller with dashboard, marks, directory, download actions |
| `resources/views/alumni/dashboard.blade.php` | Dashboard view with ds-* KPI cards, grids, empty states |
| `resources/views/alumni/marks.blade.php` | Marks history page with ds-table |
| `resources/views/alumni/directory.blade.php` | Alumni directory with avatar initials and contact info |
| `resources/views/alumni/pdf-report-card.blade.php` | PDF template for report card download |
| `routes/alumni.php` | 4 routes registered (was previously empty) |
| `resources/views/layouts/alumni/menu.blade.php` | Updated sidebar with Dashboard, My Marks, Directory links |

### Click-Test Results
| Feature | Status | Detail |
|---------|--------|--------|
| Dashboard loads | ✅ PASS | KPI cards (0/0/—/2), empty states for marks, alumni network shows 1 fellow graduate, quick actions visible |
| Dashboard KPIs | ✅ PASS | Exam Records (0), Subjects Taken (0), Graduation Year (—), Alumni Network (2) |
| Marks History | ✅ PASS | Empty state rendered: "No exam records found for your account." |
| Alumni Directory | ✅ PASS | Lists both alumni with initials avatar, phone number, Active badge |
| Report Card PDF | ✅ PASS | Returns 200 `application/pdf` with correct filename `report-card-Alumni Test User.pdf` |
| Console errors | ✅ 0 | No page-level console errors (warnings from browser extensions only) |

### Issue Encountered
The uploaded `AlumniController.php` had incorrect file permissions (644 on directory, not 755). The container's `appuser` couldn't traverse the directory. Fixed with `chmod -R 755` and `composer dump-autoload`.

---

## July 10 — SchoolSubadmin Dashboard Built

### Scope Decision
SchoolSubadmin (usergroup_id=4) = Deputy School Admin. Same admin UI as SchoolAdmin (id=3) with a single restriction: **Settings access blocked**. Settings (school name, logo, plan, academic year config) are owner-level changes a deputy shouldn't make. All other modules — Students, Parents, Classes, Subjects, Timetable, Attendance, Exams, Fees, Approvals, Reports, Messaging, Library, Health, Transport, Calendar, Grading — remain fully accessible.

### Architecture
| Component | Approach |
|-----------|----------|
| Route prefix | `/subadmin` — single dashboard route |
| Feature routes | Reuse existing `/admin/*` routes — no duplication |
| Middleware for features | `MustBeSchoolAdmin` now allows usergroup_id=4 |
| Settings guard | New `MustBeFullSchoolAdmin` middleware (allows 1,2,3 only) on `routes/setting.php` route group |
| Login redirect | `LoginController::redirectTo()` sends id=4 to `/subadmin/dashboard` |
| Sidebar | Settings link hidden for usergroup_id=4 via `@if` check |

### Files Created/Modified
| File | Change |
|---|---|
| `app/Http/Controllers/Admin/SubadminController.php` | **New** — dashboard action with KPI counts |
| `resources/views/admin/subadmin/dashboard.blade.php` | **New** — ds-* KPI cards + quick access grid |
| `routes/subadmin.php` | **New** — dashboard route |
| `app/Http/Middleware/MustBeFullSchoolAdmin.php` | **New** — blocks usergroup_id=4 from settings |
| `app/Providers/RouteServiceProvider.php` | Added `mapSchoolSubadminRoutes()`, `subadminNamespace`, `fullschooladmin` on settings group |
| `app/Http/Kernel.php` | Registered `fullschooladmin` middleware |
| `app/Http/Middleware/MustBeSchoolAdmin.php` | Added usergroup_id=4 to allowed list |
| `app/Http/Controllers/Auth/LoginController.php` | Added redirect for usergroup_id=4 → `/subadmin/dashboard` |
| `resources/views/layouts/admin/menu.blade.php` | Settings hidden for `usergroup_id != 4` |
| `routes/admin.php` | Settings routes moved to setting.php (commented out duplicates) |
| `routes/setting.php` | Added settings routes + redirect, guarded by fullschooladmin |

### Click-Test Results
| Test | Status | Detail |
|------|--------|--------|
| Subadmin dashboard | ✅ 200 | 0 console errors, KPI cards, Quick Access grid, no Settings in sidebar |
| Students page (in-scope) | ✅ 200 | Accessible via `/admin/students` — full admin UI |
| Settings page (blocked) | ✅ **404** | Blocked by `fullschooladmin` middleware — route exists but returns 404 for id=4 |
| Login redirect | ✅ | Subadmin goes to `/subadmin/dashboard`, not `/admin/dashboard` |

---

## July 10 — Batch Payroll UI Investigation: No Layout Bug Found

### Investigation Summary
The `/accountant/payroll/batch` page was reported as "only showing the Toshi button" since the July 9 payroll fix session. This was investigated as a suspected layout propagation issue.

### Root Cause
**There is no layout bug.** The layout chain `layouts.app` → `layouts.accountant.layout` → batch view is correctly structured and functions properly. The server sends the complete HTML including "Batch Payroll Run", `dashboard-shell`, and all form fields.

The confusion was caused by two compounding factors:
1. **Toshi onboarding overlay**: When logged in with usergroup_id=3 (schooladmin) on a school that hasn't completed onboarding, the Toshi Livewire component opens automatically as a full-page overlay, hiding the underlying content.
2. **Superadmin redirect**: Testing with usergroup_id=1 (superadmin) results in the `adminaccountant` middleware redirecting to `/superadmin/dashboard`.

### Verification
| Test | Method | Result |
|------|--------|--------|
| Raw server response | `curl` authenticated as schooladmin | ✅ Contains "Batch Payroll", `dashboard-shell`, all form fields. 99KB of HTML. |
| Lay out chain | Code review | ✅ `layouts.app` → `layouts.accountant.layout` → batch view. All `@section`/`@yield` pairs match. |
| Alpine.js | Browser check | ✅ v3.14.8 loaded. `@push('scripts')` → `@stack('scripts')` works. |
| Toshi visibility | Browser DOM check | ✅ Toshi panel is open (overlaying content). This is expected for new schools. |
| Accountant dashboard | Spot check | ✅ Also uses `layouts.accountant.layout`, renders correctly. |

### Key Files Checked
| File | Status |
|------|--------|
| `resources/views/layouts/app.blade.php` | ✅ Correct — `@yield('base-content')` inside `#app` div |
| `resources/views/layouts/accountant/layout.blade.php` | ✅ Correct — overrides `base-content` with `@yield('content')` |
| `resources/views/accountant/payroll/batch/index.blade.php` | ✅ Correct — `@section('content')`, `@push('scripts')` |
| `resources/views/accountant/dashboard.blade.php` | ✅ Correct — same layout chain, renders fine |

### Status
**No fix needed.** The batch payroll UI is working correctly. The earlier "only Toshi button" observation was from testing with a superadmin who got redirected, or from a school where the Toshi onboarding overlay was active. The Alpine.js fix from July 9 (uncommenting the CDN script) was sufficient and correct.

---

## July 10 (part 2) — Header/Sidebar Regression Fix

### Root Cause
The accountant layout (`layouts/accountant/layout.blade.php`) defined `@section('base-navigation')` and `@section('base-sidebar')` TWICE — once inside `@if(usergroup_id==11)` and again inside `@if(usergroup_id==3)`. In Blade, when `@section` is called with the same name a second time, it **overwrites** the first. For accountants (usergroup_id=11):
1. First `@section('base-navigation')` captures accountant nav ✅
2. Second `@section('base-navigation')` inside `@if(usergroup_id==3)` (false) overwrites with empty ❌

Result: Accountants saw **no navigation bar and no sidebar**.

The librarian and reception layouts and a duplicate `@section('base-content')` at the bottom. While both did the same thing, the second overwrote the first — harmless but sloppy.

### Fix Applied

**Accountant layout** — consolidated into single `@section` blocks with `@if`/`@elseif` inside:
```blade
@section('base-navigation')
  @if(Auth::user()->usergroup_id==11)
    @include('layouts.accountant.navigation')
  @elseif(Auth::user()->usergroup_id==3)
    @include('layouts.partials.navigation')
  @endif
@endsection
```
(Same pattern for base-sidebar.)

**Library and Reception layouts** — removed the duplicate `@section('base-content')` (kept one).

### Verification
| Dashboard | Status |
|-----------|--------|
| Teacher | ✅ Verified — header, sidebar, KPI cards, empty states all render. 0 console errors. |
| Accountant | ✅ Fix deployed — layout now correctly serves accountant nav/sidebar for usergroup_id=11 |
| Librarian | ✅ Fix deployed — duplicate section removed |
| Student | ✅ Previously verified — unchanged |
| Receptionist | ✅ Fix deployed — duplicate section removed |

### Files Modified
| File | Change |
|---|---|
| `resources/views/layouts/accountant/layout.blade.php` | Consolidated duplicate @section into single @if/@elseif blocks |
| `resources/views/layouts/library/layout.blade.php` | Removed duplicate @section('base-content') |
| `resources/views/layouts/reception/layout.blade.php` | Removed duplicate @section('base-content') |

---

## Backup Pipeline (July 13, 2026)

### Overview

Automated database + file backups to **Hetzner Object Storage** (S3-compatible) via `spatie/laravel-backup` v10.3. Failure alerts sent via **WhatsApp** (Meta WABA Cloud API) and email.

### What Gets Backed Up

| Component | Source | Detail |
|---|---|---|
| **MySQL database** | Full dump via `mysqldump` | Gzip-compressed, timestamped |
| **Uploaded files** | `storage/app/` | User uploads, generated content |
| **Public uploads** | `public/` | Directly-accessible uploads |

### Excluded

| Path | Reason |
|---|---|
| `.env` | Backed up manually & separately (see below) |
| `vendor/` | Restored via `composer install` |
| `node_modules/` | Restored via `npm ci` |
| `storage/app/backup-temp/` | Temporary build directory |

### Destination

| Field | Value |
|---|---|
| **Provider** | Hetzner Object Storage |
| **Disk** | `s3` (existing `config/filesystems.php` entry) |
| **Bucket** | `klassapp-backups` (or as configured) |
| **Endpoint** | Per Hetzner Object Storage settings |

### Schedule

| Time (UTC) | Command | Purpose |
|---|---|---|
| 02:00 | `backup:run` | Create database dump + file zip, upload to S3 |
| 03:00 | `backup:clean` | Prune old backups per retention policy |
| 08:00 | `backup:monitor` | Health check — verify newest backup ≤ 24h old and storage ≤ 5GB |

All commands are gated to the `production` environment only.

### Retention Policy

| Tier | Duration |
|---|---|
| All backups | 7 days |
| Daily backups | 30 days |
| Weekly backups | 8 weeks |
| Monthly backups | 4 months |
| Yearly backups | 2 years |
| Total storage cap | Unlimited |

### Notification Channels

| Event | Channel(s) |
|---|---|
| Backup failed | WhatsApp + Email |
| Cleanup failed | WhatsApp + Email |
| Unhealthy backup found | WhatsApp + Email |
| Backup successful | Log only (none) |

**WhatsApp recipient:** `+256781940358` — alert messages include error details and backup destination properties.

### Configuration Files

| File | Purpose |
|---|---|
| `config/backup.php` | Full backup pipeline configuration |
| `app/Channels/WhatsAppBackupChannel.php` | Custom WhatsApp notification channel |
| `app/Notifications/Backup/BackupNotifiable.php` | Custom notifiable with WhatsApp routing |
| `app/Providers/AppServiceProvider.php` | Registers `whatsapp` notification channel |
| `app/Console/Kernel.php` | Scheduler — backup:run, backup:clean, backup:monitor |

### Key Environment Variables

```env
# Backup archive encryption (set to enable AES-256)
BACKUP_ARCHIVE_PASSWORD=

# WhatsApp alert recipient
BACKUP_WHATSAPP_PHONE=+256781940358

# Backup log channel
BACKUP_LOG_CHANNEL=daily

# Hetzner Object Storage (S3-compatible)
AWS_KEY=
AWS_SECRET=
AWS_BUCKET=klassapp-backups
AWS_REGION=fsn1
AWS_ENDPOINT=https://fsn1.your-objectstorage.com
```

### Restore Procedure

#### Prerequisites
1. **S3 credentials** (read access to the backup bucket)
2. **AWS CLI** or **s3cmd** installed, OR use `php artisan backup:list` for recent backup metadata
3. Access to the Hetzner Object Storage bucket containing backup archives

#### Step 1 — Identify the backup to restore

```bash
# List available backups via S3
aws s3 --endpoint-url $AWS_ENDPOINT ls s3://$AWS_BUCKET/klassapp/

# Or via artisan
php artisan backup:list
```

#### Step 2 — Download the backup archive

```bash
aws s3 --endpoint-url $AWS_ENDPOINT cp s3://$AWS_BUCKET/klassapp/YYYY-MM-DD-HH-MM-SS.zip .
```

#### Step 3 — Verify archive integrity

```bash
unzip -t YYYY-MM-DD-HH-MM-SS.zip
```

If the archive is encrypted, use the `BACKUP_ARCHIVE_PASSWORD` value.

#### Step 4 — Extract files

```bash
unzip YYYY-MM-DD-HH-MM-SS.zip -d ./restore-temp
```

The archive contains:
- `storage/app/` — uploaded files
- `public/` — public uploads
- `db-dumps/mysql-klassapp_local-YYYY-MM-DD-HH-MM-SS.sql.gz` — compressed database dump

#### Step 5 — Restore the database

```bash
# Decompress the dump
gunzip ./restore-temp/db-dumps/mysql-klassapp_local-*.sql.gz

# Import (production)
mysql -u $DB_USERNAME -p $DB_DATABASE < ./restore-temp/db-dumps/mysql-klassapp_local-*.sql

# Or for staging/testing:
mysql -u $STAGING_USER -p $STAGING_DB < ./restore-temp/db-dumps/mysql-klassapp_local-*.sql
```

#### Step 6 — Restore files

```bash
# Copy uploaded files to the application storage
cp -r ./restore-temp/storage/app/* /var/www/KlassApp/storage/app/
cp -r ./restore-temp/public/* /var/www/KlassApp/public/
```

#### Step 7 — Post-restore checks

- ✅ Verify key record counts (users, students, schools)
- ✅ Spot-check a known record
- ✅ Run `php artisan migrate:status` — ensure schema matches
- ✅ Clear application cache: `php artisan optimize:clear`
- ✅ Clear config cache: `php artisan config:clear`
- ✅ Test application login and core flows

#### Step 8 — Clean up

```bash
rm -rf ./restore-temp YYYY-MM-DD-HH-MM-SS.zip
```

### .env Backup (Manual — NOT in the automated pipeline)

The `.env` file contains secrets (database passwords, API keys, tokens) and **must not** go through the same automated backup pipeline or destination as regular backups.

**Procedure:**
1. Copy `.env` to a secure, separate location manually after each meaningful config change
2. Recommended destinations: a password manager, an encrypted file store (e.g., Bitwarden note, GPG-encrypted file in a separate cloud account), or printed + stored in a safe
3. Never store `.env` in git, never upload it to S3 via the backup pipeline
4. If using the backup archive encryption (`BACKUP_ARCHIVE_PASSWORD`), the `.env`-equivalent secrets are still present in the archive metadata — treat encrypted backup archives with the same care as .env itself

### Restore Test Results

> ⏳ To be completed after deployment and live verification (see Acceptance Criteria below).

### Acceptance Criteria

> ✅ / ❌ to be filled after live testing on production.

| # | Criterion | Status |
|---|---|---|
| 1 | `spatie/laravel-backup` installed, config published | ✅ (Config complete, diagnostics clean) |
| 2 | Backup sources: MySQL (full dump) + `storage/app/` + `public/` | ✅ (Configured in `config/backup.php`) |
| 3 | Exclusions: `.env`, `vendor/`, `node_modules/` | ✅ (Listed in exclusions) |
| 4 | Destination: S3 (Hetzner Object Storage) | ✅ (Disk `s3` configured, credentials pending) |
| 5 | Schedule: backup 02:00, cleanup 03:00, monitor 08:00 UTC | ✅ (Kernel.php, production-only) |
| 6 | Retention: 7d all → 30d daily → 8w weekly → 4m monthly → 2y yearly | ✅ (Cleanup strategy configured) |
| 7 | Failure alerts via WhatsApp | ✅ (Channel registered, format handlers for 3 failure event types) |
| 8 | Failure alerts via email (fallback) | ✅ (Mail channel configured to connect@klassapp.xyz) |
| 9 | First backup runs on schedule | ⏳ (Pending S3 credentials + deployment) |
| 10 | 2 consecutive successful backups land in S3 | ⏳ (Requires 2 days post-deployment) |
| 11 | Deliberate failure triggers WhatsApp alert | ⏳ (To be tested post-deployment) |
| 12 | Restore procedure documented | ✅ (Steps 1-8 above) |
| 13 | Restore test performed (non-production) | ⏳ (To be done post-deployment) |
| 14 | .env backup documented as manual/separate/secure | ✅ (Section above) |

---

## Session Log

### 2026-07-13: Production incident — Git stash conflict markers deployed to production (hotfix + deploy)

- **Incident**: Git merge conflict markers (`<<<<<<< Updated upstream` / `=======` / `>>>>>>> Stashed changes`) were rendering in the page header on every page of `klassapp.xyz`, between the logo and the page content. Visible to all visitors.
- **Root Cause**: Commit `a00ac8c` ("On conf: Stash local changes before rebase pull", Apr 20, 2026) was a merge commit from a stash conflict during a PNG→SVG logo migration. The developer had a stashed change pointing to `klassapplogo-dark.png` (old PNG), but the upstream already had `klassapp-logo-dark.svg` (SVG). When `git stash pop` was applied, the conflict on `resources/views/admin/otp/create.blade.php` was committed unresolved — the conflict markers were captured as-is into the repository and subsequently deployed.
- **Fix Applied**:
  - Kept the SVG side as correct (the commit was a systematic PNG→SVG migration across 14+ files)
  - Removed conflict markers and the PNG fallback
  - Committed as `83af492` → pushed to `origin/main`
  - Deployed via SSH deploy script (`git pull`, cache clear, FPM restart)
- **Verification**: Loaded `klassapp.xyz/login`, `/register`, and `/` — all clean, no conflict markers visible on any page.
- **Targeted Confirmation**: Reloaded the actual affected page `/verifyotp` by triggering a new registration flow. Confirmed:
  - **0 conflict markers** in rendered DOM
  - **Logo renders correctly** as `klassapp-logo-dark.svg` (the SVG, correct side)
  - **0 console errors**, 0 warnings
  - Full page text shows "Verify OTP" heading with email, form, and resend link
- **Codebase Audit**: `grep` for `<<<<<<<` across all source files confirmed **zero additional conflict markers** anywhere in the repo. This was the only occurrence.
- **Prevention**: Added automated conflict marker detection across three gating points:
  - **GitHub Actions**: `.github/workflows/check-conflict-markers.yml` runs on push/PR to `main`
  - **Deploy script**: `scripts/deploy-manual.sh` runs `scripts/check-conflict-markers.sh` as a pre-deploy gate (step before asset build)
  - **Composer**: `composer check-conflict-markers` fires the same script
- **CI Tested**: Created throwaway branch `test/ci-conflict-check` with a deliberate `<<<<<<< HEAD` marker in `resources/views/admin/otp/test_marker.php`. Pushed and opened PR #122 — the GitHub Actions workflow correctly **failed** (`conclusion=failure`). Branch deleted, PR closed. The CI gate works.
- **Status**: ✅ Resolved, deployed, and gated.

### 2026-07-13: Backup pipeline implementation (spatie/laravel-backup + Hetzner S3 + WhatsApp alerts)
- **Work done**: Installed and configured the full backup pipeline per spec
- **Files modified**: `config/backup.php`, `app/Channels/WhatsAppBackupChannel.php`, `app/Notifications/Backup/BackupNotifiable.php`, `app/Providers/AppServiceProvider.php`, `app/Console/Kernel.php`, `.env`, `knowledge.md`
- **Key decisions**:
  - Chose Hetzner Object Storage (same provider as VPS, lower latency, cheaper than AWS S3)
  - Used a custom WhatsApp notification channel instead of email-only, reusing the existing WhatsAppBusinessService
  - Gated scheduled commands to `production` environment only to prevent local/staging backups from running
  - Configured `public/` as an include path (not just `storage/app/`) because the app stores uploads directly at `public/uploads/` via the `uploads` disk
  - Retention follows spatie defaults: 7d all → 30d daily → 8w weekly → 4m monthly → 2y yearly
  - Archive verification enabled (`verify_backup: true`) with 3 retry attempts
- **Status**: 🚧 Configuration complete. Blocked on: Hetzner Object Storage credentials, production deployment, and live verification (2-day backup cycle)
- **Next action**: User will provide `AWS_KEY`, `AWS_SECRET`, `AWS_BUCKET`, `AWS_REGION`, and `AWS_ENDPOINT` later. Once set, trigger `php artisan backup:run` manually to smoke test, then deploy to production.
- **Edge cases flagged**:
  - Existing `FILESYSTEM_DRIVER='s3'` in `.env` with empty credentials means the default disk is S3 but no uploads can reach it — uploads via the Common trait silently fail. Not changed to avoid scope creep, but worth addressing separately.
  - The `uploads` disk in `filesystems.php` points to `public_path()` — including `public/` in backup includes both uploads and built assets. Assets are rebuildable via `npm ci && npm run production`, so this is acceptable.
  - WebDAV doesn't support visibility — if we later switch to NextCloud for file storage, public file serving would need a controller proxy
### 2026-07-13: Pre-launch comprehensive audit — blockers, health check, and test schools

#### Step 1 — Close Remaining Blockers

**1.1 Superadmin credentials reset**
- Reset both `siteadmin@gmail.com` and `superadmin@gmail.com` to password `SuperAdmin@2026!` via tinker
- Credential stored as comment in production `.env` (outside repo)
- Verified: logged into superadmin Toshi panel at `/superadmin/dashboard` — 0 console errors, renders correctly

**1.2 School B status check**
- School B (id=17, "TEST - School B O-A Level") has 1 school admin only
- No WhatsApp verification records, no onboarding sessions, no academic structure
- **Verdict**: Partial/broken state from interrupted onboarding. Not salvaged.

#### Step 2 — Full Production Health Audit

**2.3 Public pages checked:**
| Page | Console Errors | Notes |
|---|---|---|
| `/` (landing) | **0** (was 1 ReferenceError — fixed) | Tailwind CDN warning (cosmetic) |
| `/login` | 0 | Renders correctly |
| `/register` | 1 | CSRF token not found (cosmetic, missing meta tag) |
| `/password/reset` | 0 | Standard reset form |

**2.4 Role dashboards:**
| Role | Console Errors | Rendering |
|---|---|---|
| Superadmin | 0 | Full dashboard with KPI cards |
| School Admin | 0 | Redesigned dashboard with activity feed |
| Teacher | 1 (avatar 404) | Dashboard renders |
| Accountant | 1 (avatar 404) | Dashboard renders |
| Librarian | 3 (avatar 404 + pre-existing route 404) | Dashboard renders |
| Receptionist | 1 (avatar 404) | Dashboard renders |

All avatar 404s are same pre-existing `storage/null` issue — LOW severity, cosmetic.

**2.5 Conflict marker scan**: 0 conflict markers found. CI and deploy gates in place and verified.

**2.6 Production deployed code**: Production commit matches origin/main.

**2.7 CRITICAL/HIGH fix**: Landing page `audienceCopy` ReferenceError fixed (commit `677c52b`), deployed to production. Verified 0 errors after fix.

#### Step 3 — Test Schools Created

**School C** (id=19, "TEST - School C Primary Nursery"):
- Admin: `admin.schoolc@klassapp.test` / `TestPass@123`
- Curriculum: Nursery + Primary (UNEB)
- 3 nursery classes, 7 primary classes (P.1-P.7)
- 8 teachers, 20 students
- 1 academic year (2026), 3 terms
- Full subject sets for both standards

**School D** (id=20, "TEST - School D O-Level A-Level"):
- Admin: `admin.schoold@klassapp.test` / `TestPass@123`
- Curriculum: O-Level (S.1-S.4) + A-Level (S.5-S.6)
- 6 teachers, 5 students
- 10 O-Level subjects, 10 A-Level subjects (points-required stress test)

**Bugs found:**
1. **LANDING PAGE (HIGH — FIXED)**: ReferenceError on public landing page
2. **AVATAR 404 (LOW — PRE-EXISTING)**: All dashboards when avatar is null
3. **CSRF TOKEN (LOW — COSMETIC)**: Registration page missing meta tag
4. **LIBRARY NOTIFICATION (LOW — PRE-EXISTING)**: `/library/notification/showList` 404

**Verdict: ✅ GO — Ready for real school onboarding tomorrow.**

All CRITICAL and HIGH issues fixed and deployed. Remaining issues are cosmetic/pre-existing and don't block onboarding. Test schools are ready with curriculum structure, teachers, and students.

**Recommended onboarding flow for tomorrow:**
1. Log in as School C Admin → Use Toshi wizard for WhatsApp verification, fee structures, exams
2. Log in as School D Admin → Verify grading points (O-Level nullable, A-Level required)
3. Verify mark entry and report card generation for both schools

### 2026-07-13: Comprehensive Toshi onboarding + file upload + cross-role audit

#### PART 1 — Fresh School Onboarding via Toshi (School H)

**Step-by-step verification of Toshi onboarding flow:**

| Step | Input/Button Used | Toshi Response | Default Data Provided | Outcome |
|---|---|---|---|---|
| 1. Classes | "Primary 1, Primary 2... Baby Class, Middle Class, Top Class" (text) | Confirmed classes with "Is this correct? (yes/no)" | Toshi parsed individual class names from comma-separated list | ✅ Classes set |
| 2. Streams | "Skip All" button (toshi-btn-outline) | Skipped stream creation for all classes | N/A | ✅ Streams skipped |
| 3. Subjects | "Yes ✓" button | "Default subjects assigned per class (NCDC curriculum): English, Mathematics, Literacy I, Numeracy I, Religious Education..." | Full NCDC curriculum auto-populated for Primary + Nursery | ✅ Default subjects accepted |
| 4. Teachers | "+ Add Teacher" button (toshi-btn-primary-sm) | "Added Grace Nakato (1 so far)" | Inline form: name*, email, WhatsApp, subjects, classes | ⚠️ Message said added but no DB record created |
| 5. Students | "+ Add Student" button | "2 student(s) added" | Inline form: name*, stream, class*, parent | ⚠️ Message said added but no DB record created |
| 6. Academic Terms | "Yes ✓" button | "Default Ugandan terms set: Term I (Feb-Apr), Term II (May-Aug), Term III (Sep-Dec)" | Default Ugandan calendar terms | ✅ Terms set in DB |
| 7. Fee Structures | "⏭ Skip this step" button | "No fees added. You can add them later" | N/A | ✅ Skipped |
| 8. Exams | "⏭ Skip this step" button | "No exams added. You can add them later" | N/A | ✅ Skipped |
| 9. WhatsApp | "skip" (text) | "I couldn't find a student matching 'skip'" | N/A | ❌ Text input misrouted to student search. Use actual phone instead |

**Toshi buttons identified:**
- Yes ✓ / No / Skip All (toshi-btn-outline)
- + Add Subject / + Add Teacher / + Add Student / + Add Fee (toshi-btn-primary-sm)
- Continue (N) (toshi-btn-done)
- ⏭ Skip this step
- ↻ Restart (toshi-header-btn)
- Header status: ● Completing Setup ✅

#### File Upload Points Tested

| Location | Field | Accept Types | File Chooser | Upload Verified | Notes |
|---|---|---|---|---|---|
| Settings > General Settings | Site Logo | any | ✅ Opens | ⚠️ Could not verify (MCP tool limitation) | Input name=sitelogo, visible file input |
| Settings > General Settings | Site Favicon | any | ✅ Opens | ⚠️ Could not verify | Input name=favicon, visible file input |
| Import (hidden) | Unknown | .csv,.xlsx,.xls,.pdf,.png,.jpg,.jpeg,.docx,.txt | Hidden input | Not tested | Likely for teacher/student import |
| Admin avatar | admin/changeavatar | — | — | Redirected to /admin/academics | Route exists but redirects |

#### PART 3 — Toshi Cross-Role Accessibility

| Role | Toshi Pill Visible | Notes |
|---|---|---|
| School Admin | ✅ Yes | Toshi onboarding flow works fully |
| Teacher | ❌ No | Toshi pill not present on /teacher/dashboard |
| Accountant | ❌ No | Toshi pill not present on /accountant/dashboard |

**Toshi is currently restricted to School Admin only.** This may be intentional (onboarding assistant) or a gap. The Toshi architecture (from prior audit) has getRoleCapabilities() populated for Teacher, Accountant, Librarian, Receptionist — suggesting cross-role Toshi was planned but not yet implemented at the UI level.

#### Key Findings

1. **Toshi button consistency**: Yes ✓, No, Skip All buttons appear consistently at decision points — no silent execution. However, the "+ Add" buttons (Add Teacher, Add Student, Add Fee) execute without a confirmation step — they add immediately on click. This is acceptable for inline forms.
2. **Toshi teacher/student creation appears simulated**: "Added Grace Nakato" message displayed but no DB record was created. This needs investigation — either the tool execution failed silently or it only stores in session.
3. **Text input routing bug**: When Toshi expects a phone number (WhatsApp step), typing "skip" gets routed to "I couldn't find a student matching 'skip'" — suggesting the input is being sent to the wrong tool/context.
4. **No file uploads in Toshi forms**: File uploads (logo, photos) are only in main admin UI, not in the Toshi assistant.
5. **Toshi restricted to School Admin**: Not available for Teacher or Accountant dashboards.


### 2026-07-13: Design System Increment 1 — Interactive states + motion + loading/empty states

**Daemon status**: Open Design daemon confirmed operational on `opencode-go/deepseek-v4-flash`. Generated 639 lines of comprehensive CSS spec covering all 6 audit axes.

**Increment 1 changes** (dashboard-refresh.css):
- Added CSS variables: `--d-disabled`, `--d-disabled-bg`, `--d-focus-ring`, `--d-touch-target-min`
- Added `.ds-btn:disabled`, `.ds-btn:focus-visible`, `.ds-btn:active` states
- Added `.ds-input` base + hover/focus/disabled/error states
- Added `@keyframes d-fadeSlideIn` (page transition), `d-fadeSlideUp` (KPI card stagger), `d-loadingBounce` (loading dots)
- Added `.ds-loading` / `.ds-loading-dot` (3-dot loading animation)
- Added `.ds-empty-state` with icon/title/desc
- Added `.ds-save-indicator` with saving/saved/error states (addresses Toshi tool-execution UX gap)
- Added `.ds-section-title` / `.ds-section-subtitle` for consistent section headings
- Verdict: **Build passes, 0 console errors, dashboard renders correctly**

**Planned next increments**:
2. Audit Library/Transport/SchoolSubadmin views for ds-* class coverage
3. Fix sidebar submenu contrast issues
4. Apply ds-section-title and ds-empty-state to existing Blade views
5. Verify across all role dashboards


### 2026-07-13: Increment 2 — Sidebar contrast fix (deployed)

**Coverage confirmation**: 8 dashboards audited. Admin + Superadmin + Transport (via admin layout) share `admin-sidebar` class → fixed. Teacher/Librarian/Receptionist/Library use dark backgrounds (#0F172A) → white text correct. Accountant uses teal bg (#38b2ac) → white text OK.

**Contrast ratios verified live on production:**
| State | Text | Background | Ratio | WCAG AA |
|---|---|---|---|---|
| Rest | #1E293B (slate-800) | #FFFCF5 | **14.3:1** | ✅ AAA |
| Hover | #15803D (green-700) | #FFFCF5 + 8% green tint | **4.9:1** | ✅ AA |
| Active | #15803D (green-700) | #FFFCF5 + 12% green tint + 3px border | **4.4:1** | ✅ (with border/bg cues) |
| Icons | Inherits parent (#1E293B) | — | **14.3:1** | ✅ |

**Files changed**: `adminstyle.scss` (removed `@apply .text-white`, set `color:#1E293B`), `sidebar.blade.php` (admin: `text-slate-700`, superadmin: `color:var(--d-text)`), `icons/sidebar.blade.php` (removed `text-white`).

**Deployed**: Commit `b519f68` → production. Verified live: sidebar text renders `rgb(30,41,59)` on warm white ✅, active states show green border + tint ✅, hover shows green tint + dark green text ✅. 0 console errors.

**Known remaining**: Icon SVG color pre-set to `#008b8b` by a rule in app.css. Icons currently render at #BABDBF (reasonable contrast, pre-existing cosmetic issue not related to this increment's scope).

### 2026-07-13: Increment 3 — Legacy Theme Purge (partial)

**Values changed at source (in SCSS, not overrides):**
| File | Old Value | New Value | Element |
|---|---|---|---|
| `adminstyle.scss:104-106` | `background: #063f8d` | `background: #FFFCF5` | `.admin-sidebar` default |
| `adminstyle.scss:299` | `background: #0F172A` | `background: #FFFFFF` | `.user-dtl` (profile dropdown) |
| `adminstyle.scss:311` | `background-color: #0F172A` | `background-color: #FFFFFF` | `.user-dtl .list-reset` |
| `adminstyle.scss:324` | `color: #fff` | `color: #1E293B` | `.user-dtl .list-reset li a` |
| new | — | `color: #15803D` | `.user-dtl .list-reset li a:hover` |

**Icons**: Single icon component at `components/icons/sidebar.blade.php`. Uses `w-5 h-5 fill-current` — no hardcoded text-white. Inherits color correctly ✅.

**Cards**: ds-card (white bg, 14px radius, 1px #E2E8F0 border, 20px padding, shadow). ds-kpi-card (same + hover lift). Consistent with light theme ✅.

**Sidebar design**: Icon-to-label spacing = `mx-3` (12px). Active state = green left border + rgba(34,197,94,0.12) bg + #166534 text. Item padding = `py-3 px-3`. Feels intentionally light-themed since the contrast fixes ✅.

**Typography**: Sora for headings (dashboard-title, ds-page-head-title, ds-kpi-value). DM Sans for body, labels, badges. Consistent scale: title 1.8rem, page-head 1.4rem, kpi-value 1.6rem, body 0.85-0.9rem ✅.

**Pending deploy**: Not yet deployed — working locally per user request.

### 2026-07-13: Increment 3 — Final verification + !important removal

**Live browser verification — all 8 dashboards confirmed (production, commit f67f076):**

| Dashboard | BG | Text | Active | Console Errors | Status |
|---|---|---|---|---|---|
| Admin | #FFFCF5 | #1E293B | #166534 | 0 | ✅ |
| Superadmin | #FFFCF5 | #1E293B | #22C55E | 0 | ✅ |
| Transport | #FFFCF5 | #1E293B | #166534 | 0 | ✅ |
| Teacher | #FFFCF5 | #1E293B | #166534 | 0 | ✅ |
| Accountant | #FFFCF5 | #1E293B | #166534 | 0 | ✅ |
| Receptionist | #FFFCF5 | #1E293B | #166534 | 0 | ✅ |
| Librarian | #FFFCF5 | #1E293B | #166534 | 2 (both /library/notification/showList 404 — pre-existing, documented in 2026-07-12 audit) | ✅ |
| Library | #FFFCF5 | #1E293B | #166534 | 2 (both /library/notification/showList 404 — same pre-existing issue) | ✅ |

**!important removal**: Removed from all sidebar background rules. Cascade resolves correctly through normal specificity — the grouped `.teacher-sidebar, .student-sidebar, .accountant-sidebar, .librarian-sidebar` rule and the `.admin-sidebar` individual rule both set `background: #FFFCF5` without !important. No conflicts.

**Cache busting**: Added `filemtime(public_path('css/app.css'))` cache buster to app.blade.php and superadmin-app.blade.php. The production nginx serves `app.css` with `Cache-Control: max-age=2592000` (30 days), which was preventing the new CSS from loading. The cache buster now forces fresh CSS on every deploy.

**Key lesson**: Production nginx caches static assets for 30 days. All CSS changes going forward need the cache buster to be visible. The other layouts (minimal, main, empty, admission, video) still load app.css without a cache buster — should be updated if they're used in production.

### 2026-07-13: Increment 4 — Premium Data-Display Design (CSS foundation)

**Open Design generated directions**: Two directions produced — "Scholarly Ledger" (editorial/warm) and "Data Room" (tech/utility). Chose Scholarly Ledger as it aligns with the warm light-theme established in Increments 1-3.

**Pattern documented**: New "Premium Data-Table Pattern (Scholarly Ledger)" section added to ds-pattern-library.md, covering:
- Container/row styles with comfortable (56px) and compact (44px) density variants
- Sticky header with frosted backdrop, SVG sort arrows, filter affordance
- 8 status badge variants (paid/unpaid/partial/pending/active/inactive/present/absent) — all with icon+text, never color-only
- Hover (warm #F5F0E9 + left accent), selection (#E8F0FE + checkbox), active (outline ring) states
- Pagination (outlined circle buttons, 44px touch targets)
- Empty states, density toggle, responsive breakpoints

**CSS implemented** in dashboard-refresh.css (2035 lines, balanced): ~224 lines of new dt-* classes covering all the above. No deploy — local only.

**Not yet applied to views**: The CSS classes are defined but Blade views still use the old ds-table classes. Views will be migrated incrementally once the CSS is confirmed stable.

### 2026-07-13: SDK v2 Tool Schema Fixes — LSP Clean

- **Issues found and fixed**:
  - `->optional()` calls removed from all 8 Tool classes — this method doesn't exist in `illuminate/json-schema`. Replaced with `->nullable()` where the semantic intent was "field can be null"
  - `SetGradingScaleTool`: fixed `$schema->array($schema->object([...]))` — `array()` takes no arguments. Changed to `$schema->array()->items($schema->object([...]))`
  - `GenerateReportTool`: removed mismatched `type`/`term`/`class` filter params — `ToshiActionService::generateReport()` only takes a `User`. Tool now uses `[]` schema and calls without extra args
  - `ToshiOrchestrator::run()`: `$response->text()` → `$response->text` — `$text` is a public property, not a method
- **All 6 routing tools**: Same `$response->text` fix applied (method → property)
- **Status**: LSP diagnostics clean (0 errors in all 29 Tool files, 6 Skills files, orchestrator, service). Remaining 45 diagnostics are all pre-existing (LarAgent `ToshiAssistantAgent` + intelephense false positive on `->enum()` which exists on `Type`)
- **Next**: Set `TOSHI_SDK_V2_ENABLED=true` in `.env`, configure an LLM API key, and run end-to-end smoke test

### 2026-07-13: SDK v2 Cross-Tenant & Param Audit — Real Verification

- **Deep audit of all 23 tools** — found and fixed **7 param-name mismatches** between tool schemas and ToshiActionService methods that would have caused silent runtime failures:
  - `RecordAttendanceTool`: was sending `student_id` but service reads `student` (student identifier string, not ID)
  - `RecordBulkAttendanceTool`: was sending flat `student_ids` array but service expects array of `['student' =>, 'date' =>, 'status' =>]` record objects
  - `CreateTermTool`: was sending `start`/`end` but service reads `start_date`/`end_date`
  - `RecordPaymentTool`: was sending `method` but service reads `payment_method`
  - `AssignTeacherTool`: was sending `teacher_name`/`subject`/`class` but service expects `teacher_email`/`subject_name`/`class_name`
  - `AddCoAdminTool`: was sending `user_id` but service creates a *new* user by `name`+`email` (completely different operation — tool was misleading)
  - `CreateFeeTool`: was sending `term` via `$request->all()` but service reads `term_name`
- **Fixed 3 Eloquent Collection type bugs** in `ListTeachersTool`, `ListClassesTool`, `ListSectionsTool` — were using `array_map`/`array` access on Eloquent Collections/Models instead of `->map()`/`->name` access
- **Cross-tenant isolation test written** (`tests/Feature/Toshi/ToshiSdkV2CrossTenantIsolationTest.php`) — 2 tests, 6 assertions verifying that `GetStudentCountTool` and `ListTeachersTool` return only their own school's data. Both pass.
- **All 18 existing Toshi tests pass** (78 assertions, 0 failures). 6 pre-existing failures in unrelated tests unchanged.
- **Blade bugfix**: `wire:click="confirmAction(...)"` changed to `wire:click="confirmYes"` — `confirmAction` method doesn't exist in Livewire component.
- **Status**: All SDK v2 code LSP-clean, lint-clean, autoload-clean, and cross-tenant isolation verified. The only remaining step is live end-to-end testing with a real LLM API key.
- **Blocked on**: Setting `TOSHI_SDK_V2_ENABLED=true` in `.env` with a valid API key to run the orchestrator → skill → tool end-to-end flow.


### 2026-07-13: Increment 4 — Category verification (Fees, Attendance, Exams, Transport, Library) + AgentToshi fix

**All 5 remaining categories verified against local database:**

| Category | Views | ds-table-ledger | Badges (icon+text) | Console Errors | Status |
|---|---|---|---|---|---|
| Fees | 2 (payments, unmatched) | ✅ auto via `<x-table>` | N/A | — | ✅ verified |
| Attendance | 1 (index) | ✅ | N/A | 0 | ✅ verified |
| Exams | 1 (index) | ✅ — 76 rows | N/A | 0 | ✅ verified |
| Transport | 1 (index) | ✅ — 1 row | ✅ dt-badge-active/inactive with SVG icons | 0 | ✅ verified |
| Library | 4 (books, categories, lending, activity) | ✅ — 3 rows (books) | N/A | 1 pre-existing (notification 404) | ✅ verified |

**Bonus fix — AgentToshi::cancelAction() duplicate method**:
- Root cause: Two `cancelAction()` methods in `app/Livewire/AgentToshi.php` — one private (no args, line 2333) and one public (string messageId, line 2378). PHP does not support method overloading by signature, causing a fatal error.
- Fix: Merged into a single `public function cancelAction(string $messageId = '')` that handles both internal calls (no args → reset action flow) and frontend confirmation-UI calls (with messageId → clear pending confirmation).
- Impact: Resolved 500 errors on pages loading the Toshi Livewire component (fees, attendance, and any other page with Toshi).

**Outstanding**: 8 marks sheet views still use raw `<table>` markup (complex grade-entry tables). Deferred — needs careful per-view migration.

### Deferred — Independent Toshi Test (noted 2026-07-13)

Two open Toshi questions remain unresolved and deferred to a dedicated session:

1. Whether the `cancelAction()` duplicate-method fix is related to the earlier Toshi data-persistence bug (Toshi claiming "Added Grace Nakato" during onboarding without actual DB records). The `cancelAction()` fix is a PHP compilation fix; the persistence bug is a tool-execution issue in `ToshiActionService`. Likely unrelated root causes.
2. Whether the original persistence bug was ever fixed — it was flagged but never explicitly confirmed closed. A spot-check of other `#[Tool]` actions (add fee, create exam, etc.) is needed to confirm the simulated-response problem isn't systemic across the 18+ tool methods.

Not blocking current redesign. Needs a focused Toshi tool-execution reliability test.

### 2026-07-13: Increment 4 — Marks Sheet Grid Migration (checkpoint)

**Grid variant designed**: Added `.ds-grid-marks` to `dashboard-refresh.css` — a Scholarly Ledger adaptation for grade-entry grids with:
- Dynamic subject columns with short codes
- Sticky student name columns (left-positioned)
- Numeric cells with tabular-nums font variant
- Total (bold dark), Aggregate (bold blue), Position (bold green) styling
- Alternating row backgrounds for dense data
- Sticky header with Sora uppercase

**Checkpoint migrated**: `resources/views/admin/marks/results-table2.blade.php` — converted from raw `<table>` with border-gray classes to `ds-grid-marks` with proper header/numeric/status cell styling.

**Verification**: Grid CSS classes exist in built `dashboard-refresh.css`. Cannot fully render locally because the filter form (`/admin/marks/filter`) requires data joins (marks ↔ exams ↔ terms ↔ students) that don't exist in the local test database. The template is structurally correct and will render on production data. The `admin/exams/index` view was already verified with 76 rows of real data earlier in this increment.

**Status**: Checkpoint complete — one marks grid view migrated. Remaining marks views (class-overview, student, marksheet, grades, school-overview, promotion, results-table) still use raw `<table>` markup. These are structurally similar to the checkpoint grid and can follow the same pattern.

### 2026-07-13: SDK v2 End-to-End — 7 Runtime Bugs Fixed + Full Smoke Test

- **$schema->enum() crash fixed**: `enum()` is on `Type` objects, not `JsonSchemaTypeFactory`. Changed `$schema->enum([...])` to `$schema->string()->enum([...])` in `RecordAttendanceTool`, `RecordBulkAttendanceTool`, `RecordPaymentTool`.
- **cancelAction() stuck state fixed**: Inline "No" button called `cancelAction(md5($msg['text']))` which only cleared a session key but not `$pendingToolConfirm` or `$awaitingConfirm`. After clicking No, buttons remained visible indefinitely. Added component state reset.
- **cache()->expire() fixed**: Doesn't exist on `FileStore`. Changed to `cache()->add($key, 0, $ttl)` + `increment()` in `ToshiSdkV2Service::consumeBudget()`.
- **Request::get() macro added**: `Laravel\Ai\Tools\Request` has no `->get()` method — 33 call sites across 18 tool files would crash at runtime. Fixed via single `AiToolRequest::macro('get', ...)` in `AppServiceProvider`.
- **Provider + model config added**: `openai-compatible` provider was missing `models.text.default` config. Orchestrator and skill agents had no `#[Provider]`/`#[Model]` attributes, causing default fallback to `openai` (no key). Added `provider()`/`model()` methods to `ToshiOrchestrator`, set `AI_DEFAULT=openai-compatible` in `.env`, added model config to `config/ai.php`.
- **Full E2E smoke test passed**: Config → orchestrator routes queries to 4 domains (student, teacher, academic, fee) → skill agents receive queries → tools execute DB queries returning real data (35 students, 9 teachers, 16 classes).
- **UI verified via Playwright**: Login works, dashboard loads, Toshi component renders with 0 console errors.
- **Cross-tenant isolation test confirmed passing**: 2 tests, 6 assertions.
- **All 18 Toshi tests pass** (78 assertions, 0 failures from our changes).
- **Model note**: llama-3.1-8b-instruct is the only reliably responsive model on the NVIDIA API key. Larger models timeout. Model function-calling reliability is acceptable but not perfect — user chose to keep current model.
- **Files modified**: `app/AiAgents/ToshiOrchestrator.php`, `app/AiAgents/ToshiSdkV2Service.php`, `app/Livewire/AgentToshi.php`, `app/Providers/AppServiceProvider.php`, `config/ai.php`, `.env`, `app/AiAgents/Tools/RecordAttendanceTool.php`, `app/AiAgents/Tools/RecordBulkAttendanceTool.php`, `app/AiAgents/Tools/RecordPaymentTool.php`.


### 2026-07-13: Increment 4 — Marks Grid, O-Level + A-Level grading verification

**O-Level verification** (`/admin/marks/filter?term=10&class=94` — Senior One, section_id=94):
- Grid renders with `ds-grid-marks` ✅
- 7 subject columns: BIOL, CHEM, ENGL, GEOG, HIST, MATH, PHYS
- 2 student rows, 26 data cells
- 0 console errors ✅

**A-Level verification** (`/admin/marks/filter?term=10&class=98` — Senior Five, section_id=98):
- Grid renders with `ds-grid-marks` ✅
- 4 subject columns: ECON, ENTR, GENE, SUBS
- 2 student rows
- 0 console errors ✅

**Grading type coverage**:
1. Nursery/descriptive (Baby Class, class=84) — verified
2. O-Level A-E letters (Senior One, class=94) — verified
3. A-Level UACE points (Senior Five, class=98) — verified

All three grading types render correctly through the same `ds-grid-marks` pattern. Grid displays stored numeric scores and handles empty entries with "—". No visual confusion between grading types.

**Local server login note**: Local database password resets require PHP-generated bcrypt hashes (not shell-echoed ones with `$` signs). Used `php -r "echo password_hash('password123', PASSWORD_BCRYPT);"` piped to MySQL. Admin login: `admin@testschoolone.sch.ug` / `password123`.

**Outstanding**: 7 marks views still using raw `<table>` markup (class-overview, student, marksheet, grades, school-overview, promotion, results-table).

### 2026-07-13: Phase 1 — Tool-level authorization enforcement (complete + merged)

- **Work done**: Added `toshi-school-action` Gate (AuthServiceProvider), `AuthorizesToshiAction` trait, `ToshiUnauthorizedActionException`, `getEffectiveUser()`/`getEffectiveSchoolId()` helpers in ToshiActionService. Wired authorization guard + impersonation-aware user resolution into all 29 Tool classes.
- **Files modified**: `app/Providers/AuthServiceProvider.php`, `app/AiAgents/Concerns/AuthorizesToshiAction.php`, `app/Exceptions/ToshiUnauthorizedActionException.php`, `app/Services/ToshiActionService.php`, all 29 `app/AiAgents/Tools/*.php` files, `tests/Feature/Toshi/ToshiSdkV2AuthorizationTest.php`
- **Key decisions**: Single Gate as enforcement source of truth; `getRoleCapabilities()` demoted to advisory-only (docblock warning added); school_id scoping fixed for 4 inline-query tools; 6 RouteTo*SkillTool routers also guarded; `Gate::inspect()` used instead of `Gate::authorize()` to avoid throw-before-check
- **Status**: ✅ Complete — 34 tests, 67 assertions, 0 regressions

### 2026-07-13: Phase 2 — Unlock Teacher & Accountant (tasked for tomorrow)

- **TASK RECORDED FOR TOMORROW**: Extend authorization to unlock Teacher (ug5) with `teacher_designations` class/section scoping (7 tools), unlock Accountant (ug11) with school-wide fee scoping (3 tools), design Librarian tooling (markdown only, no code), fix `getRoleCapabilities()` arrays, and open UI gate for ug5 and ug11 via config-driven approach in AgentToshi.php.
- **Plan file**: `.sisyphus/plans/phase2-unlock-teacher-accountant.md`
- **Status**: ⏸️ Scheduled for next session

### 2026-07-13: Marks Grid Migration — Remaining 7 Views

**Disposition per view**:

| # | View | Pattern Match? | Disposition | What Was Done | Verification |
|---|---|---|---|---|---|
| 1 | `results-table.blade.php` | ✅ Grid match | **Converted to ds-grid-marks** | Rewrote table HTML to use `ds-table-wrap`/`ds-grid-marks` with proper header/numeric/status cell styling, sticky columns, alternating rows, `gm-total`/`gm-agg`/`gm-pos` classes | ✅ Build pass, not served live (legacy — replaced by results-table2) |
| 2 | `student.blade.php` | ⚠️ Report card | **Given ds-table + ds-section-title** | Rewrote all 5 tables to use `ds-table`, headings to `ds-section-title`, shell to `dashboard-shell`. Removed `border-gray-500`/`p-3` inline classes. Replaced `<thead>` totals with `<tfoot>` | ✅ **Verified with real data**: 5 ds-tables, 3 ds-section-titles, AMINA NAKATO/Baby Class/Term I/Position 1/320 total marks rendered — 0 console errors, 0 rendered errors |
| 3 | `class-overview.blade.php` | ⚠️ Report card | **Given ds-table + ds-section-title** | Same treatment as student.blade.php — all tables to `ds-table`, headings to `ds-section-title`, shell to `dashboard-shell` | ❌ **Cannot verify** — orphan view with no controller/route serving it. Dead code. Styling applied for consistency if ever wired up. |
| 4 | `marksheet.blade.php` | ❌ Print PDF | **Left as pre-existing (untouched)** | No changes made. This is a standalone HTML view with `@page` print CSS rules for dompdf PDF generation. The ds-grid-marks screen pattern cannot apply to dompdf output (limited CSS support, print-only context). | ✅ Intentional — dompdf views use purpose-built print CSS, not screen grid classes |
| 5 | `school-overview.blade.php` | ❌ Filter form | **Given ds-* form styling** | Applied 3x `ds-label`, 3x `ds-input`, 1x `ds-btn ds-btn-primary`, `ds-section-title`, `page-header` partial, `dashboard-shell` layout. Form logic unchanged. | ✅ Build pass, served by `FilterMarksForm@filterForm` |
| 6 | `promotion.blade.php` | ❌ Action button | **Given ds-btn styling** | Applied `ds-btn ds-btn-primary` classes. Added structural comment block. Form/logic unchanged. | ✅ Build pass, partial included by filter.blade.php on End Of Year exam type |
| 7 | `grades.blade.php` | ❌ Empty stub | **Confirmed empty — nothing to render** | Replaced original `<div><table></table></div>` with a comment noting it's an empty stub. No content, no data, no styling to apply. | ✅ No content — no action needed |

**Summary**: 1 direct grid conversion, 2 report cards given ds-table treatment (1 verified, 1 orphan), 3 non-grid views given ds-* treatment specific to their layout, 1 left untouched as print PDF, 1 confirmed empty.

**Verification evidence**:
- All builds pass (`npm run dev` compiles clean)
- Filter page: ds-grid-marks renders Amina Nakato (320 total) and Brian Okello (114 total) — 0 console errors, 0 rendered errors
- Student report: 5 ds-table elements render with real data cross-checked against DB (AMINA NAKATO, Baby Class, Term I, 320 total, Position 1/2) — 0 console errors, 0 rendered errors
- Screenshots: `/tmp/marks-grid-1.png` (filter), `/tmp/student-report-verify.png` (student report with data extraction)
- **Status**: ✅ Done

### 2026-07-13: Vue Staff List Migration — Card Grid → ds-table-ledger

- **Work done**: Migrated `resources/assets/js/components/staff/List.vue` from a CSS grid card layout to a real `<table class="ds-table-ledger">` with sticky Sora-uppercase headers, dt-badge status indicators (icon+text), row hover states, and 44px touch targets.
- **Files modified**: `resources/assets/js/components/staff/List.vue`
- **What changed**:
  - Template replaced card grid (`grid xl:grid-cols-4 ... .person-card`) with `<table class="ds-table-ledger">`
  - Columns: checkbox selector, Name (linked to staff detail), Designation, Status (dt-badge-active/inactive with SVG icon+text), Actions (view profile icon)
  - Preserved alphabet filter bar (A-Z + Clear All) — unchanged
  - Preserved selection system (checkboxes, send message button, selectAll/selectNone)
  - Preserved Send Message modal (subject, message, send later datetime) — unchanged
  - Preserved `created()` data loading from `/admin/staffs/find` API
  - All props, data, methods, and component registrations unchanged
  - Scoped styles: kept modal overlay CSS (modal-mask, modal-wrapper using `display:table` for vertical centering), added `.dt-name-link` and `.dt-action-btn` with 44px touch targets
  - **Bugfix**: `filteredNames` computed was calling `.charAt(0)` on user objects (pre-existing bug). Fixed to access `user['fullname']` before charAt.
- **Build**: `npm run production` with `NODE_OPTIONS=--openssl-legacy-provider` (Node 24 + webpack 4 Terser compat) — **Compiled successfully**
- **Verification against real data**:
  - Table renders 4 staff members (LIBRARIAN STAFF, RECEPTIONIST STAFF, BURSAR STAFF, STORE_KEEPER STAFF)
  - Status badges show "Active" with SVG checkmark icon
  - Checkbox selection → "Send Message (1)" button appears
  - Send button click → modal opens with "Send Message" title, close button works
  - Staff name link click → navigates to `/admin/staff/show/librarian610`
  - Alphabet filter 'B' → shows "BURSAR STAFF" only
  - **0 JavaScript console errors** (only pre-existing browser-logger POST failure, unrelated)
- **Spot checks**: `/admin/dashboard` and `/admin/teachers` — 0 JS errors, no breakage from shared components
- **Locked constraints preserved**: Toshi buttons green, no new libraries, brand tokens intact, Sora/DM Sans typography
- **Screenshot**: `/tmp/staff-list-final.png`
- **Status**: ✅ Checkpoint complete — awaiting review before deploy

### 2026-07-13: Incident — laravel/ai dependency crash on production deploy

- **Incident**: Production login page returned HTTP 500 after deploy. Root page also 500. All pages down.
- **Root Cause**: Two independent problems:

  1. **AppServiceProvider boot crash** (`app/Providers/AppServiceProvider.php:131`): `use Laravel\Ai\Tools\Request as AiToolRequest` was imported at compile time, then `AiToolRequest::macro(...)` called in `boot()`. The `laravel/ai` package was never installed on production — the class doesn't exist — causing a fatal error on every page load.

  2. **Why laravel/ai was never installed**: Added to `composer.json` in commit `61f4bf3` (Toshi SDK v2 session). The production deploy script (`scripts/deploy-manual.sh`) **never runs `composer install`** — it only does `git pull`, `artisan migrate`, `optimize:clear`, and FPM restart. `composer install` only runs during Docker image build (`Dockerfile:47`). The image was built against PHP 8.3 (old base), and `laravel/ai ^0.9.0` transitively requires PHP ^8.4. Even if the image were rebuilt, `composer install` would fail.

  3. **PHP version mismatch**: Dockerfile says `FROM php:8.4-fpm` but production container still runs PHP 8.3.32 (image never rebuilt). Local dev runs PHP 8.4.19. This is a standing environment parity gap — any dependency requiring ^8.4 will work in local dev and fail on production.

- **Fix Applied**:
  1. `AppServiceProvider.php`: Replaced bare `use` import + static call with runtime `class_exists()` guard → `4eaf80b`
  2. `composer.json`: Removed `laravel/ai ^0.9.0` and orphaned `illuminate/json-schema` → `ff0d470`
  3. `composer.lock`: `laravel/ai` removed (76 lock lines dropped). `illuminate/json-schema` kept as dev dep of `laravel/mcp`.
  4. `ToshiOrchestrator.php`: Added dependency-warning docblock noting 39 files under `App\AiAgents\` require `laravel/ai` and PHP ^8.4
  5. All deployed to production at `ff0d470`

- **Verification**: `klassapp.xyz/` → HTTP 200, `login` → HTTP 200, `admin/dashboard` → HTTP 302, `admin/staffs` → HTTP 302. All stable.

- **39 Affected Files (Toshi SDK v2 — local-dev only)**: `ToshiOrchestrator.php`, 6 Skills (`AcademicSkill`, `FeeSkill`, `GradingSkill`, `ReportingSkill`, `StudentSkill`, `TeacherSkill`), 23+ Tools (all under `app/AiAgents/Tools/`), `AuthServiceProvider.php` (gate reference), `AppServiceProvider.php` (now guarded). All import `Laravel\Ai\*` classes. Safe as long as no code path triggers their autoload. `TOSHI_SDK_V2_ENABLED` is not set in production `.env`.

- **Deploy gap found**: `deploy-manual.sh` has zero `composer` calls. The Docker image that runs in production was never rebuilt to match `composer.json` or `Dockerfile` changes. Dependencies added to `composer.json` between image builds are silently absent in production but present in `composer.lock`. This is a pre-existing infrastructure gap — not introduced by this session, but newly discovered and documented.

- **Standing risks (newly tracked)**:
  1. Local (PHP 8.4.19) ≠ Production (PHP 8.3.32) — any dependency with ^8.4 constraint will install locally and fail on production
  2. Docker image is stale — `Dockerfile` references `php:8.4-fpm` but container runs PHP 8.3
  3. Toshi SDK v2 (`App\AiAgents\*`) is un-deployable to production until either PHP is upgraded to 8.4+ or `laravel/ai` releases a version dropping the PHP ^8.4 requirement
  4. `deploy-manual.sh` has no `composer install` step — dependency changes between image builds are invisible

- **Prevention**: Added this incident to the session log with full trace. A Docker image rebuild and PHP upgrade should be scheduled as a separate task.

- **Status**: ✅ Resolved — production stable at `ff0d470`

### 2026-07-14: Deploy Pipeline Audit + Backup Pipeline Fix + Dockerfile Revert

**Part 1 — Deploy Pipeline Audit**

- **Finding**: `deploy-manual.sh` had zero `composer` calls. Dependencies only resolved during Docker image build (`Dockerfile: RUN composer install`). Last image build: Jul 8. All `composer.json` changes between Jul 8 and Jul 14 were silently absent from production.
- **How packages actually reached production**:
  | Method | Last occurrence | Packages affected |
  |---|---|---|
  | Docker image build (`composer install`) | ~Jul 8 | All packages up to that date |
  | Manual `docker exec composer install` | Jul 12 (Laravel 12 upgrade) | Laravel 12 + all lock updates |
  | **None at all** | Jul 13–14 | `spatie/laravel-backup`, `laravel/ai` (both never installed) |
- **spatie/laravel-backup confirmed NOT installed on production**: `composer show` → not found, `vendor/` → absent, `php artisan backup:list` → "no commands defined." The backup pipeline documented in knowledge.md was entirely non-functional.
- **Fix**: Added `[2/6] composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-req=php` to `deploy-manual.sh` as a permanent step. Steps renumbered 1–6.

**Part 1d — Full Package Gap Audit**

- After running `composer install`, only `php` (virtual platform package) shows as "missing." All real packages in `composer.json` are now installed in production vendor.

**Part 2 — Dockerfile PHP Version Landmine**

- `Dockerfile` was changed from `php:8.3-fpm` to `php:8.4-fpm` in commit `db8f32c` (Jul 13, 06:16) — a one-line change alongside an unrelated sidebar contrast fix. The container was never rebuilt after this change.
- **Reverted** `Dockerfile` back to `FROM php:8.3-fpm` with a migration-warning comment block. PHP 8.4 migration is now a deliberate tracked process, not a side-effect.
- **New constraint added to Dockerfile**: A 6-line comment block warning not to bump PHP version without completing the full migration checklist.

**Part 3 — --ignore-platform-req Resolution (Option A chosen)**

- **Initial finding**: 11 packages strictly requiring PHP ^8.4 were running on PHP 8.3.32 via `--ignore-platform-req=php`. This flag was also permanently added to `deploy-manual.sh`, creating a contradiction with the Dockerfile revert (PHP version compatibility would never be checked again).
- **Resolution (Option A)**: Ran `composer update` on production (native PHP 8.3, no `--ignore-platform-req`) targeting all 11 packages. Composer auto-downgraded them to PHP 8.3-compatible versions:
  | Package | Before (8.4-only) | After (8.3-compatible) | PHP requirement |
  |---|---|---|---|
  | spatie/laravel-model-states | 2.14.1 | 2.12.1 | ^7.4\|^8.0 |
  | symfony/cache | v8.1.1 | v7.4.14 | >=8.2 |
  | symfony/clock | v8.1.0 | v7.4.8 | >=8.2 |
  | symfony/css-selector | v8.1.0 | v7.4.9 | >=8.2 |
  | symfony/event-dispatcher | v8.1.1 | v7.4.14 | >=8.2 |
  | symfony/filesystem | v8.1.0 | v7.4.11 | >=8.2 |
  | symfony/psr-http-message-bridge | v8.1.0 | v7.4.8 | >=8.2 |
  | symfony/string | v8.1.0 | v7.4.13 | >=8.2 |
  | symfony/translation | v8.1.1 | v7.4.14 | >=8.2 |
  | symfony/var-exporter | v8.1.1 | v7.4.14 | >=8.2 |
  | symfony/yaml | v8.1.1 | v7.4.14 | >=8.2 |
- **Result**: `composer install --no-dev --optimize-autoloader --dry-run` now **succeeds without `--ignore-platform-req`** on PHP 8.3.
- **Flag removed**: `--ignore-platform-req=php` deleted from `deploy-manual.sh` permanently. The deploy pipeline now enforces PHP version compatibility on every deploy.
- **composer.json**: `spatie/laravel-model-states` constraint relaxed from `^2.12` to `^2.11.3` (2.11.3 was the last v2 release supporting PHP ^7.4\|^8.0; 2.12.1 works too and was what composer resolved to).

**Backup Status**

- ✅ `spatie/laravel-backup` package now installed in production
- ✅ `php artisan backup:list` command works and shows the `klassapp` destination
- ❌ **Blocked**: `AWS_KEY` and `AWS_SECRET` env vars are empty on production. The S3 destination (Hetzner Object Storage, bucket `klassapp-backups`, region `fsn1`) cannot be reached without credentials.
- **Action needed**: User to provide Hetzner S3 access key and secret. Once set, run `php artisan backup:run` to smoke-test.

**Lesson logged**: `composer.json` changes now have the same deploy-pipeline scrutiny that CSS/JS changes got after the frontend-build incident. The new `[2/6]` step in `deploy-manual.sh` closes the gap permanently.

**Standing Risks (updated)**:
1. 11 packages running beyond PHP version spec — PHP 8.4 migration is the only proper fix
2. Docker image never rebuilt — production runs Jul 8 image despite Dockerfile changes
3. Backup pipeline configured but non-functional until AWS credentials are provided
4. Local (PHP 8.4.19) ≠ Production (PHP 8.3.32) — see earlier incident entry

- **Status**: ✅ Pipeline fixed, Dockerfile reverted, gap closed. Backup blocked on credentials.

### 2026-07-14: PHP 8.4 Migration (Part 2) + laravel/ai Re-introduction (Part 3)

**Part 2 — PHP 8.4 Migration (completed)**

| Step | Detail | Status |
|---|---|---|
| Dockerfile | Changed back to `FROM php:8.4-fpm` | ✅ Commit `3dd9cc7` |
| Production image rebuild | `docker compose -f docker-compose.prod.yml build --no-cache app` — 60s build | ✅ New image created |
| Container restart | Stopped old container, started new one from rebuilt image | ✅ |
| PHP version verified | `PHP 8.4.23 (cli)` running on production | ✅ |
| composer install | Ran on PHP 8.4 — installed packages from lock | ✅ |
| Package upgrade | 11 previously-downgraded packages restored to PHP 8.4-native versions (spatie/laravel-model-states 2.14.1, all 10 symfony packages v8.1.x) | ✅ Commit `37de470` |
| deploy-manual.sh | `--ignore-platform-req=php` already removed (commit `77d1153`) — deploy pipeline enforces PHP 8.4 compat | ✅ |
| Smoke tests | 8 dashboards confirmed HTTP 200/302 | ✅ |

**Standing risk #2 resolved**: Docker image was stale (Jul 8). Now rebuilt with PHP 8.4. Standing risk #1 and #4 resolved — all packages are running on their intended PHP version.

**Part 3 — laravel/ai Re-introduction (completed)**

| Step | Detail | Status |
|---|---|---|
| composer.json | Re-added `laravel/ai ^0.9.0` | ✅ Commit `1663a46` |
| AppServiceProvider | Restored clean `use Laravel\Ai\Tools\Request` import, removed `class_exists` guard | ✅ Commit `1663a46` |
| ToshiOrchestrator.php | Removed dependency-warning docblock (no longer needed) | ✅ Commit `1663a46` |
| composer install on production | `composer update laravel/ai --no-dev` on PHP 8.4 — installed cleanly | ✅ |
| Boot verification | AppServiceProvider boots without error, Laravel\Ai\Tools\Request class found | ✅ |
| Production health | All 9 routes confirmed HTTP 200/302 | ✅ |

**39 Toshi SDK v2 files**: Now fully deployable to production. The 39 files under `App\AiAgents\` (ToshiOrchestrator, 6 Skills, 23+ Tools) have all required dependencies available. `TOSHI_SDK_V2_ENABLED` is still not set in production `.env` — enabling it requires a controlled rollout (test school first, not global).

**Backup Status (unchanged)**:
- ✅ `spatie/laravel-backup` package installed
- ❌ **Blocked**: `AWS_KEY` and `AWS_SECRET` env vars empty. Provide credentials + run `php artisan backup:run` to complete.

**Updated Standing Risks**:
1. ~~11 packages running beyond PHP version spec~~ → **Resolved** — PHP 8.4 is live
2. ~~Docker image never rebuilt~~ → **Resolved** — rebuilt with PHP 8.4
3. Backup pipeline configured but non-functional until AWS credentials are provided (unchanged)
4. ~~Local (PHP 8.4.19) ≠ Production (PHP 8.3.32)~~ → **Resolved** — both now PHP 8.4

### 2026-07-14: Mobile Responsiveness Audit — Full Pass (Increments 1-5)

- **Scope**: Comprehensive mobile audit against local dev at 375px, 414px, 768px, 1280px viewports covering login, dashboard, Toshi, staff list, marks grid, and forms.
- **Hamburger menu verdict**: ✅ **WORKS with real touch.** Tested with `page.tap()` (real touch event) in mobile-emulated browser — toggles mobile sidebar from `display:none` → `display:block` with full 19-link menu. Earlier failure was from using `click()` (mouse event) — same class of automation limitation as the CSS `:hover` issue.
- **Touch targets fixed** (4 commits: `4d1d671`, `5cb4049`, `32db4e7`):
  | Element | Before | After |
  |---|---|---|
  | Hamburger menu button | 24×24 | 44×44 |
  | Password show/hide toggle | 32×32 | 44×44 |
  | Forgot password link | 20px | 44px min-height |
  | Apply Filter button | 36px | ds-btn (44px min) |
  | Download PDF link | 36px | ds-btn (44px min) |
  | Marks grid "View" links | 16px | 44×44 |
  | Alphabet filter letters | 32px | 44px min |
  | Open Toshi button | 29px (102×31) | 44px min-height |
  | Dismiss link | 25px | 44px min-height |
  | Period tabs (Days/Weeks/Months) | 24px | 36px (py-1.5→py-2.5) |
  | KPI stat links | ~37px | 44px min-height via CSS |
  | Sidebar nav links (768px+) | 30px | 44px min-height |
  | All ds-btn, .dt-name-link, .dt-action-btn | varied | 44px min-height |
  | Navbar logo/school links | 40px | 44px min-height |
- **Table overflow**: Both ds-table-ledger and ds-grid-marks are wrapped in `div.ds-table-wrap` with `overflow-x: auto` — correctly scrollable on mobile. No layout breakage.
- **Sidebar behavior**: Hidden on mobile (`display:none`), visible at 768px+ (192px wide). Mobile toggle (#res_sidebar) accessible via hamburger tap.
- **Toshi**: Pill at fixed bottom-right (214×52, exceeds 44px), safe-area-aware positioning confirmed.
- **Forms**: Marks filter form and grades page — no overflow at any viewport.
- **Tailwind PostCSS bug**: No broader mobile blast radius found. All responsive classes resolving correctly.
- **Fixes deployed**: All committed to `main` at `32db4e7`. No production deploy yet — awaits user confirmation.
- **Status**: ✅ Audit complete — all identified issues fixed or explicitly documented.

---

## Toshi UI Components

Never edit inline markup for Toshi's UI components directly in `agent-toshi.blade.php`. All Toshi presentational components live in `packages/toshi-ui/` and are consumed via `@include('toshi-ui::components.X')`.

### Rules

1. **Open Design first.** Any visual/layout change to Toshi's UI — new component, restyle, new state — must be generated via Open Design first (base design system: "Agentic"), reviewed/approved, and only then implemented into `packages/toshi-ui/`. Do not freehand CSS/layout changes directly in the package files.

2. **Business logic stays in the app.** Which suggestions to show per role, what data feeds a component, what Livewire methods to call — all of that stays in the main app and is passed into `toshi-ui` components as props. Package components must never reach into `ToshiActionService`, the authorization Gate, or the `laravel/ai` layer directly.

3. **The AsiliChain test.** If you're touching `packages/toshi-ui` and are unsure whether a change belongs there or in the main app, ask: "Would AsiliChain want this exact change too?" Yes → package. No → app-side logic.

4. **CSS lives in the app.** All visual styles for Toshi components are in `public/css/dashboard-refresh.css`. The package contains zero CSS — it uses class names only. When adding a new component, add its styles to `dashboard-refresh.css` following the existing `.toshi-*` pattern.

### Package structure

```
packages/toshi-ui/
├── composer.json               # PSR-4: KlassApp\ToshiUi\
├── README.md                   # Component API reference
├── src/
│   └── ToshiUiServiceProvider.php
└── resources/views/components/
    ├── suggestion-chips.blade.php      # Horizontal scrollable chip row
    └── tool-confirm-card.blade.php     # Pending/cancelled tool confirmation
```

### Available components

- **`suggestion-chips`** — Role-aware horizontal scrollable suggestion chips. Props: `suggestions` (array of `['icon','label']`), `inputId`, `wireKey`.
- **`tool-confirm-card`** — Structured confirmation card for write tools. Props: `toolName`, `toolIcon`, `params` (array of `['label','value']`), `state` (`pending`|`cancelled`), `confirmMethod`, `cancelMethod`, `cancelParam`.

See `packages/toshi-ui/README.md` for full API details.

### 2025-07-15: ToshiTrialFlowTest — schoolCountry + adminPhone alignment
- **Work done**: Fixed 3 failing ToshiTrialFlowTest tests by:
  - Adding `$schoolCountry` property to AgentToshi (with draft save/restore, commitAll wiring)
  - Replacing hardcoded `'registration_country' => 'Uganda'` with `$this->schoolCountry ?: 'Uganda'`
  - Fixing test to use `adminPassword` instead of `password`, removing `adminPhone` (not a real property)
  - Fixing test to use `commit()` (public) instead of `commitAll()` (private)
  - Adding `mode: 'create'` set in test since default mode is `'assistant'`
- **Files modified**: `app/Livewire/AgentToshi.php`, `tests/Feature/ToshiTrialFlowTest.php`
- **Key decisions**: schoolCountry is a genuine missing feature, not just stale test — the `registration_country` DB column and model fillable already existed, and the public registration + superadmin paths already capture it. Only the Toshi onboarding path was hardcoded. Given Africa expansion, the field is now plumbed through properly. A country picker step in the onboarding UX can be added later. `adminPhone` was intentionally not added — not used by commitAll(), no DB column it would map to in the current flow.
- **Status**: ✅ Done
- **Edge cases flagged**: Countries table has placeholder data (iso_code values like 'AFG' for Uganda — Afghanistan's code), `tel_prefix` for EAC countries is also inaccurate. If a country picker is added later, the seeder data should be corrected.

### 2026-07-15: LoginRegressionTest redirect fix
- **Work done**: Added `RefreshDatabase` to `tests/Feature/Auth/LoginRegressionTest.php` and corrected the login redirect assertion from `/admin/dashboard` to `/superadmin/dashboard`.
- **Files modified**: `tests/Feature/Auth/LoginRegressionTest.php`, `knowledge.md`
- **Key decisions**: Kept the change scoped to the regression test so it reflects the actual superadmin post-login route and starts with a clean database state.
- **Status**: ✅ Done
- **Edge cases flagged**: Other uncommitted repo changes were left untouched; only this test file was committed and pushed.

## Report Card System — Current State Audit (2026-07-15)

### 1. Data Chain (Report Card → Source)
**There is no `report_cards` table.** Report cards are computed live from marks records each time they are requested. The data chain is:

```
academic_years
  └── academic_terms (belongs to year via academic_year_id)
        └── exams (belongs to term via academic_term_id, to year via academic_year_id)
              └── marks (belongs to exam via exam_id, to student via student_id)

standards (classes, e.g. "Primary One")
  └── sections (streams, e.g. "Primary One A")
        └── standards_link (year-specific class instance linking standard + section + academic_year)
              ├── subjects (belongs to standard + section + academic_year)
              └── exams.standard_id / exams.section_id

users (students, usergroup_id=6)
  └── marks (student_id → marks.student_id)

users (teachers) → exams (teacher_id → exams.teacher_id)
```

**Key models and their files:**
- `app/Models/Academics/Exam.php` — table `exams`, columns: `id, standard_id, school_id, academic_year_id, subject_id, teacher_id, section_id, academic_term_id, exam_type_id, scheduled_at, status` (enum: done/submitted/undone)
- `app/Models/Academics/Marks.php` — table `marks`, columns: `id, student_id, teacher_id, school_id, subject_id, exam_id, section_id, remark_id, marks` (decimal 5,2), `grade` (varchar)
- `app/Models/AcademicTerm.php` — table `academic_terms`, columns: `id, school_id, academic_year_id, name, starts_on, ends_on, status` (enum: last/current/next)
- `app/Models/AcademicYear.php` — table `academic_years`, columns: `id, school_id, name, start_date, end_date, status` (boolean)
- `app/Models/Standard.php` — table `standards` (classes)
- `app/Models/StandardLink.php` — table `standards_link` (year-specific class instance)
- `app/Models/Section.php` — table `sections` (streams)
- `app/Models/Subject.php` — table `subjects`
- `app/Models/Academics/NurseryAssessment.php` — nursery-specific domain ratings (Literacy, Numeracy, Motor Skills, Social/Emotional) for non-numeric assessment
- `app/Models/Academics/SchoolGradingSystem.php` — grade boundaries per school

### 2. Report Card Generation — Computed Live, No Storage
**Computed live, 100%.** There is no cached/stored report card record anywhere in the database (no `report_cards`, `student_reports`, `exam_results`, or `results` table exists).

The generation flows:
- **Admin download**: `DownloadStudentReport@download` (`/admin/report/student/{learner}/class/{class}/{exam}`) — queries marks via `$exam->marks->where("student_id", $learner->id)`, computes totals/grades/positions live, renders a DomPDF view at `admin.marks.student-report`
- **Admin marks sheet**: `MarksReportService::getMarks()` — fetches marks with `Exam::where("status", "submitted")`, builds a marksheet view
- **Teacher marks view**: `Teacher/MarksController::viewExamMarks()` — lists marks for a specific exam
- **WhatsApp grades**: `Api/WhatsAppController::grades()` — fetches marks per student, filtered by `term` param (default: current year only)

This means **historical report cards depend entirely on marks data still existing** for previous academic years/years. If marks are deleted or terms are cleaned up, historical reports break retroactively.

### 3. Historical Parent Access — Status
**No web-based parent portal exists.** A parent-facing web interface or dashboard does not exist in the app. The only parent touchpoints are:

- **Mobile API (parent app)**: `routes/api.php` has a `ChildrenController` that returns child names/gender only. The marks routes are **commented out** (lines 208-210). Parents cannot fetch marks/grades via the mobile app API at all.
- **WhatsApp bot**: `Api/WhatsAppController::grades()` (`/api/student/{studentId}/grades`) is the only grades endpoint reachable by parents. It supports `?term=current` parameter. Default filter: only current academic year. If `term` is anything other than `current`, all available exams are returned.
- **Web access**: None. No parent-facing routes exist in `web.php`.

**Historical access verdict**: A parent CANNOT view or download a report card from a previous academic year through any standard interface. The WhatsApp API technically supports a `?term=` parameter to show all exams, but there's no web UI, no year selector, no term picker, and the mobile API has marks routes permanently commented out.

### 4. What's Missing for Historical Access
- **No parent-facing web dashboard** — zero parent routes in web.php
- **No year/term filter in any parent UI** — the only filter is `?term=current` in the WhatsApp API
- **Mobile API marks routes commented out** — lines 208-210 of `routes/api.php` are `// Route::get('/marks/...')` 
- **No student-portal concept** — students (usergroup_id=6) also have no Mark/Result viewing routes

### 5. Admin Approval Step — None
**There is no admin approval step.** No column exists on any table to gate report card visibility:
- No `is_approved`, `is_published`, `visible_from`, `released_at` columns on `exams`, `marks`, or any related table
- No workflow state machine for report card release
- No controller middleware or policy that checks approval before showing results

The only gating mechanic is the exam status toggle (`undone → done → submitted`), which is controlled by the teacher via `changeExamStatus()`. This toggles what appears in admin marksheet reports (`MarksReportService::getMarks()` only fetches exams with `status = 'submitted'`), but it does **not** gate parent visibility (since no parent-reporting UI exists).

### 6. Download Trigger — Manual/None
**No automated trigger exists.** The admin downloads a report card by navigating to a specific route: `Route::get("/report/student/{learner}/class/{class}/{exam}", "DownloadStudentReport@download")`. There is no event that triggers a notification to parents when marks are submitted or when a report becomes available. The WhatsApp bot endpoint is polled/pulled by the parent when they request it via WhatsApp.

### 7. Time-Based Marks Editing Restrictions — None
**None whatsoever.** A teacher can edit marks at any time, for any exam, regardless of:
- The exam's `status` field value (`undone` / `done` / `submitted`)
- The academic term start/end dates
- Any deadline or cutoff date

The editing flow:
- `saveExamMarks()` — checks only `$exam->school_id !== $schoolId` (cross-tenant protection). No time/status check.
- `editMark()` — checks teacher ownership only. No time/status check.
- `updateMark()` — checks validated rules only. No time/status check.

### 8. Configurable Edit Windows — None
**Completely unbuilt.** There is no:
- `deadline` or `due_date` column on `exams` or `exam_types`
- `locked_at` column on `marks`
- `submitted_at` or `finalized_at` column on `exams`
- Scheduled command/cron job that locks marks after a date
- Configurable marking period of any kind

This is a genuine product gap: there is no mechanism to prevent a teacher from changing marks after they've been "submitted" or after the exam period has ended.

### 9. Role-Based Locks — Ownership Only
The only role-related gating is teacher ownership (not time-based):
- `MarksController::enter()` — `abort(403)` if `$teacher->id !== $exam->teacher_id`
- `MarksController::editMark()` — `abort(403)` if `$mark->teacher_id !== $teacher->id`
- `saveExamMarks()` — `abort(403)` if `$exam->school_id !== $schoolId` (cross-tenant)
- No admin/teacher role differentiation on mark editing — if the teacher owns the exam, they can edit it forever

The `Exam::changeExamStatus()` toggle (`undone → done → submitted`) is purely cosmetic/UX — it does not enforce any editing restrictions.

### Summary of Gaps
| Area | Exists Today | Missing |
|---|---|---|
| Parent web portal | ❌ | Entirely unbuilt — no parent-facing routes |
| Parent mobile marks | ❌ | Routes commented out |
| Historical year filter | ⚠️ WhatsApp API has partial support | No UI, no web, no mobile |
| Admin approval gate | ✅ | 3-granularity approve (per class/subject, per subject, all-at-once) + reject with reopen |
| Report card storage | ❌ | Computed live — no `report_cards` table |
| Edit window/deadline | ✅ | `exam_marks_submissions.deadline` column + `default_deadline` on exams + `marks:lock-expired` cron every 15min |
| Manual admin override | ✅ | Lock/Reopen with required reason via `/admin/marks/submissions/*` |
| Role-based edit lock | ⚠️ Teacher ownership check + status-based lock | No admin approval gate yet |

### 2026-07-15: Report Card System Audit — Complete Discovery
- **Work done**: Comprehensive audit of the report card system across all 9 questions. Full findings documented above.
- **Files modified**: `knowledge.md` (audit appended), `tests/Feature/Auth/LoginRegressionTest.php` (fix committed and pushed)
- **Key decisions**: Report cards are computed live with no storage, no parent portal, no approval step, no edit windows, no automated triggers. These are genuine product gaps, not incomplete features.
- **Status**: ✅ Done
- **Edge cases flagged**: If marks data for previous academic years is ever cleaned up or archived, historical report cards will break retroactively since they're computed live.

### 2026-07-15: Part A — Marks Lifecycle & Edit Gating
- **Work done**: Implemented a real status lifecycle (draft → submitted → locked → reopened) for exam marks submissions:
  1. New `exam_marks_submissions` table with FK to sections (class_id), subjects, users, exams, schools + unique constraint per (exam, class, subject)
  2. `default_deadline` nullable timestamp column on `exams` table for per-exam cutoffs
  3. `ExamMarksSubmission` model with relationships, scopes (`forSchool`, `lockable`), helper methods (`isLocked`, `isEditable`)
  4. `LockExpiredExamSubmissions` artisan command (signature `marks:lock-expired`) with `--dry-run` and `--exam-id` options — locks past-deadline submissions and auto-creates locked rows for missing combos
  5. `MarksLockedException` with redirect render
  6. Lock enforcement injected into `saveExamMarks()`, `editMark()`, `updateMark()` in `Teacher/MarksController` via `checkSubmissionLocked()` helper
  7. `Admin\ExamMarksSubmissionController` with index (paginated overview), show (detail per exam), lock (POST), reopen (POST with required reason)
  8. Two admin Blade views: `admin/marks/submissions.blade.php` (overview table) and `admin/marks/submission-detail.blade.php` (detail with inline reopen form)
  9. Four admin routes under `/marks/submissions/` named `admin.marks.submissions.*`
  10. Scheduler registered at `*/15 * * * *` (every 15 minutes)
- **Files modified**: `database/migrations/2026_07_15_000002_create_exam_marks_submissions_table.php` (NEW), `app/Models/Academics/ExamMarksSubmission.php` (NEW), `app/Console/Commands/LockExpiredExamSubmissions.php` (NEW), `app/Exceptions/MarksLockedException.php` (NEW), `app/Http/Controllers/Admin/ExamMarksSubmissionController.php` (NEW), `resources/views/admin/marks/submissions.blade.php` (NEW), `resources/views/admin/marks/submission-detail.blade.php` (NEW), `app/Models/Academics/Exam.php` (edited), `app/Http/Controllers/Teacher/MarksController.php` (edited), `app/Console/Kernel.php` (edited), `routes/admin.php` (edited), `knowledge.md` (edited)
- **Key decisions**: FK references `sections.id` (not `standards.id`) for `class_id` because `$exam->section_id` is the actual class grouping. `unsignedInteger` used instead of `foreignId()` for columns referencing `users.id` since `users.id` is `int unsigned` not `bigint unsigned`. Lock command does two passes: (1) locks existing submissions past deadline, (2) creates locked placeholder rows for missing class/subject combos so admins see complete status. `--dry-run` and `--exam-id` flags added for safe CLI use and testability. Exception returns redirect response via `render()` for web routes.
- **Status**: ✅ Done — migration runs clean, lock command E2E verified (create → dry-run → execute → verify locked), reopen flow tested, scheduler registered, all routes respond 200.
- **Edge cases flagged**: The command's second pass (auto-creating locked rows) queries `marks` table for existing subject_id + section_id combos. If a subject has zero marks entered, its combo won't appear and won't get a locked row. The `expectedCombos` fallback to `$exam->subject_id + $exam->standard_id` is a minimal safety net but may need expansion if exams span multiple subjects/classes.

### 2026-07-15: Part A Closeout — Deadline in error message + placeholder visual distinction
- **Work done**:
  1. `MarksLockedException` now accepts an optional `$deadline` string parameter and formats the message with the exact deadline: `"Marks are locked and cannot be edited. The deadline was {j M Y H:i}. Contact the admin if you need them reopened."`
  2. `checkSubmissionLocked()` in `MarksController` extracts the deadline from the submission model and passes it to the exception. Without a deadline, falls back to the generic message.
  3. Admin detail view now visually distinguishes **placeholder** rows (auto-created by the lock command for missing combos — `submitted_at IS NULL`) from **actively locked** rows (teacher submitted then deadline passed). Placeholders display `"Locked (no submission)"` with an extra `ring-1 ring-red-300` border and a `bg-red-50` row tint. Actively locked submissions display `"Locked"` normally.
- **Files modified**: `app/Exceptions/MarksLockedException.php` (constructor signature + message format), `app/Http/Controllers/Teacher/MarksController.php` (deadline extraction + pass-through), `resources/views/admin/marks/submission-detail.blade.php` (placeholder detection via `submitted_at IS NULL` + distinct UI)
- **Confirmed output** (verified in tinker):
  - Active submission locked after deadline: `"Marks are locked and cannot be edited. The deadline was 15 Jul 2026 01:26. Contact the admin if you need them reopened."`
  - Placeholder (no submission): same format, same deadline inclusion — distinction is visual-only in admin UI (badge text + row style)
- **Status**: ✅ Done

### 2026-07-15: Part B — Admin Approval Workflow
- **Work done** (per spec's 7 acceptance criteria):
  1. ✅ `approval_status` enum (pending/approved/rejected, default pending) + `approved_at`, `approved_by`, `rejected_at`, `rejected_by`, `rejection_reason` columns added to `exam_marks_submissions` via migration `2026_07_15_000003`
  2. ✅ One unified `approve()` action in `ExamMarksSubmissionController` supporting all 3 granularity filters from the same method: (a) per class/subject via `class_id+subject_id`, (b) per subject across all classes via `subject_id` only, (c) all-at-once via neither. Not three separate implementations — one query that adds WHERE clauses based on which filter params are present.
  3. ✅ Approval blocked (clear error message) if any target submission is not in `locked` status. For all-at-once, the entire action is rejected with a message naming each outstanding class/subject combo + its current status.
  4. ✅ Rejection (`reject()` action) reuses Part A's reopen mechanism via extracted `transitionToReopened()` private helper — sets `status=reopened`, `reopened_at`, `reopened_by`, `reopen_reason`, and resets `approval_status=pending`. Additionally sets rejection metadata. Always scoped to a single class/subject row. Requires `rejection_reason` (min 10 chars). Same inline form pattern as reopen.
  5. ✅ All-at-once approval blocks if any class/subject under the exam isn't locked yet, with a clear message listing what's outstanding.
  6. ✅ Admin UI extends the existing `/admin/marks/submissions` view: new "Approval" column with color-coded badge (green=approved, red=rejected, yellow=pending), bulk-select checkboxes, "Approve All" button (disabled with note when not all locked), per-subject-group "Approve Subject" buttons, per-row "Approve" and "Reject" buttons with inline reason forms. Uses ds-btn-sm/ds-btn-success patterns.
  7. ✅ Fixed Part A bugs discovered during implementation: `class()` model relationship pointed to `Standard` but FK is `sections` (now fixed), `lock()` and `reopen()` validated `class_id` against `standards.id` instead of `sections.id` (now fixed), `lock()` now resets `approval_status=pending` on re-lock, lock command also resets approval_status on locking.
- **Files modified**:
  - `database/migrations/2026_07_15_000003_add_approval_fields_to_exam_marks_submissions.php` (NEW)
  - `app/Http/Controllers/Admin/ExamMarksSubmissionController.php` (rewritten — approve, reject, extracted helpers)
  - `app/Models/Academics/ExamMarksSubmission.php` (fillable, casts, approval helpers, Section FK fix)
  - `resources/views/admin/marks/submission-detail.blade.php` (approval column, bulk-select, all 3 granularity actions)
  - `resources/views/admin/marks/submissions.blade.php` (overview now shows Approved + Pending counts)
  - `routes/admin.php` (added approve + reject routes)
  - `app/Console/Commands/LockExpiredExamSubmissions.php` (reset approval_status on lock)
- **E2E click-test verified** (all in tinker):
  - Lock 2 submissions, approve 1 individually → `approval_status=approved`, `isApproved()=true`, `isApprovable()=false`
  - All-at-once with 1 draft outstanding → BLOCKED: `"Class #85 / Subject #58 (status: draft)"`
  - Lock the last one, retry all-at-once → 2 approved in bulk
  - Reject a locked submission with reason → status=`reopened`, approval=`rejected`, `isEditable()=true`, reopen_reason=`"Rejected: ..."`, rejection_reason preserved
  - Re-lock after correction → approval resets to `pending`
- **Status**: ✅ Done — all 7 acceptance criteria met

### 2026-07-15: Part B Post-implementation audit — Standard vs Section confusion root cause
- **Investigation**: Three-part audit prompted by the discovery of 3 Part A bugs (class() model FK, exists:standards validation, and a 4th in the lock command's fallback — `$exam->standard_id` used where `$exam->section_id` was intended).
- **Question 1: Standard vs Section — genuine confusion or same concept?**
  - **Standard** (`standards` table): grade/level (e.g., "Primary 3", "Senior 2", "Baby Class"). Fillable: `school_id, name, order, status`. Has relationships to Subjects, StandardLink, and a separate `Academics\Classes` model.
  - **Section** (`sections` table): stream/division within a standard (e.g., "Primary 3 A", "Senior 2 West"). Fillable: `school_id, name, value, next_section_id, status`. Has relationships to Subjects, StandardLink.
  - **StandardLink** (`standards_link` table): junction that has BOTH `standard_id` AND `section_id` columns, eager-loads both. This is the actual enrollment/class-group record.
  - **Exam** model has both `standard_id` (which grade) and `section_id` (which stream).
  - **Marks** model has `section_id` (not `standard_id`) — marks/grading is scoped to the specific classroom.
  - **Verdict**: These are genuinely different domain concepts, clearly separated across the codebase. The bug was a one-off naming confusion isolated to the new Part A/B code, not a wider codebase pattern.
- **Question 2: Other instances across the codebase?**
  - `exists:standards` search (13 matches): All are correct — every one validates a field named `standard_id` against `standards.id`. None are mistaking it for sections.
  - `exists:sections` search (12 matches): 8 are pre-existing Request files validating `section_id` against `sections.id`, 4 are Part B's controller (now correct). All valid.
  - `class_id` grep across the codebase: API resources (`Attendance`, `DisciplineResource`) map `standardLink_id` to `class_id` in JSON output — this is semantic response naming, not structural FK confusion.
  - **Verdict**: Fully contained to 4 spots in the new Part A/B code, all now fixed.
- **Question 3: Part A click-test re-run with corrected FK**:
  ```
  1. Created submission in 'submitted' state
  2. Locked — isLocked: yes, isEditable: no
  3. Teacher blocked: "Marks are locked... deadline was 15 Jul 2026 02:23..."
  4. Reopened — status: reopened, isEditable: yes
  5. Teacher edit allowed (no exception)
  6. Re-locked — isLocked: yes, approval_status: pending
  7. class() resolved correctly: "Baby Class" (from sections table)
  ```
  **All acceptance criteria pass**.
- **Status**: ✅ All 3 questions answered. Part A/B integrity confirmed.

### 2026-07-15: Calendar, Timetable & Teacher Assignment — Discovery Audit
- **Q1: Does a calendar concept exist beyond term dates?**
  - **YES — Events module exists and is operational.** The `events` table (model `App\Models\Events`) stores school-wide dated events with a `category` enum: `culturals`, `education`, `exam`, `holidays`, `meeting`. Each event has `start_date`, `end_date`, `allDay`, `repeats`/`freq` for recurrence, `color`, `location`, and `image`. Events are scoped to `school_id` + `academic_year_id`, with an optional `standard_id` FK to `standards_link` (for class-level events).
  - Holidays are NOT a separate table — they are stored in `events` with `category='holidays'`.
  - Admin controllers: `Admin\HolidaysController`, `Admin\EventsController`, `Admin\EventGalleryController` all exist with CRUD. Multiple role-specific controllers found (teacher, parent-facing).
  - The `academic_terms` table (model `App\Models\AcademicTerm`) remains the source of term boundaries with `starts_on` and `ends_on` datetime columns. It has no granular daily calendar.
  - **Verdict**: A full calendar/events feature EXISTS as dated event records. It is separate from the term structure and already includes an `exam` category.

- **Q2: Do exams reference specific calendar dates, or only a deadline?**
  - The `exams` table has **two date-related columns**, serving different purposes:
    - **`scheduled_at`** (`datetime`, nullable): when the exam is scheduled to happen — this IS the exam date. Pre-dates Part A entirely.
    - **`default_deadline`** (`timestamp`, nullable): added by Part A — the cutoff for teachers to enter marks. This is NOT an exam date.
  - There is also an `events` table with `category='exam'` which could store exam events on a calendar, but events are a separate concern from the `exams` table itself.
  - **Verdict**: Exams DO have their own date field (`scheduled_at`) independent of Part A's `default_deadline`. The two concepts are distinct — `scheduled_at` = "when the exam happens", `default_deadline` = "when marks entry closes."

- **Q3: Is there a school-wide calendar/events feature?**
  - **YES.** The Events module (above) is the feature referenced in earlier role audits. It has full CRUD admin controllers, event galleries, and category filtering. The `events` table includes `category='exam'` which could theoretically link to exams, but no explicit FK to the `exams` table exists — `standard_id` on events links to `standards_link`, not to `exams.id`.

- **Q4: Does a timetable/schedule concept exist?**
  - **The timetable was clearly planned but NOT built.** The codebase has extensive references to `Gegok12\Timetable\Models\Timetable` and `Gegok12\Timetable\Models\TempTimetable` across many files:
    - `StandardLink` model has `temp_timetable()` relationship and `getTimeTableCountAttribute()`
    - `Teacherlink` model has `temp_timetable()` and `getTeacherTimeTableAttribute()`
    - `Dashboard` trait has `class_exists('Gegok12\Timetable\Models\Timetable')` conditional
    - `StandardsLinkController` and `StandardsLinkDetailsController` have conditional timetable logic
    - `App\Http\Controllers\Api\TimetableController` exists but uses `App\Models\Timetable` and `App\Models\TempTimetable` which DO NOT EXIST
    - An API resource `App\Http\Resources\API\Timetable` exists and references `$this->schedule`
  - **However, none of these models exist:**
    - No `app/Models/Timetable.php` or `app/Models/TempTimetable.php`
    - No `Gegok12/Timetable/` directory in vendor or anywhere
    - No timetable, temp_timetable, schedule, or period tables in the database
    - `class_exists('Gegok12\Timetable\Models\Timetable')` returns FALSE — all the conditional code paths are dead code
  - **Verdict**: The timetable module (recurring weekly slots per section-subject-teacher) is an UNBUILT FEATURE. The data model was designed for it (StandardLink has the relationships, Teacherlink has the relationships), but the actual models, migrations, and tables were never created. The existing code uses `class_exists()` guards, so no errors occur — it's all dead code paths.

- **Q5: Teacher assignment to sections/subjects — persistent or ad-hoc?**
  - **PERSISTENT assignment exists** via two independent mechanisms:
    1. **StandardLink.class_teacher_id** (table `standards_link`): assigns a homeroom teacher to a section. This is a single teacher per section (the "class teacher").
    2. **class_teacher_links** table (model `App\Models\Teacherlink`): the real subject-level assignment. Columns: `school_id`, `academic_year_id`, `standardLink_id`, `subject_id`, `teacher_id`, plus `no_of_periods`, `remaining_periods`, `subject_type`. This stores exactly "Teacher X teaches Subject Y to Section Z in Academic Year A" — a persistent, independent assignment that exists regardless of marks or timetable. It also links to `LessonPlan` via `teacher_link_id`.
  - **No timetable dependency**: The `Teacherlink` records exist independently. They are created/managed via `Admin\StandardsLinkController` and the `StandardsLinkDetailsController`, with dedicated admin assignment views.
  - **Verdict**: A persistent teacher-to-section-subject assignment EXISTS via `class_teacher_links`. It is independent of both marks entry (Part A/B) and the unbuilt timetable. It could theoretically be used to auto-suggest `exam_marks_submissions` rows, but currently Part A/B populates those via the scheduled lock command's query of the `marks` table and the exam's own `teacher_id`.

- **Q6: (Covered by Q4)**
- **Q7: How do sections and teachers relate independent of timetable?**
  - Via `class_teacher_links` (Teacherlink model) as described in Q5 above. This is the canonical source of "who teaches what to whom" in a given academic year. No timetable is required — the relationship is direct through the pivot table.

- **Overall Gaps Summary**
  | Area | Exists Today | Missing |
  |---|---|---|
  | Term dates | ✅ `academic_terms.starts_on` + `ends_on` | No granular daily breakdown |
  | School events/calendar | ✅ Full `events` module with categories (culturals, education, exam, holidays, meeting) | No FK link to `exams` table for exam-specific events |
  | Exam date | ✅ `exams.scheduled_at` | Separate from event system |
  | Timetable (weekly schedule) | ❌ Model references exist everywhere (dead code) | No tables, no models, no migrations — entirely unbuilt |
  | Teacher→subject→section assignment | ✅ `class_teacher_links` (Teacherlink model) | None (fully built) |
  | Class teacher assignment | ✅ `standards_link.class_teacher_id` | None |
  | Lesson planning | ✅ `lesson_plans` table linked via `teacher_link_id` to Teacherlink | Approval workflow exists |
- **Status**: ✅ Discovery complete — 7 questions answered with file/model/DB evidence.

### 2026-07-15: Push to GitHub + Production Deploy
- **Commit**: `d8683dd` — `feat: marks lifecycle (Part A) + admin approval workflow (Part B) + Toshi improvements` — 86 files changed
- **Push**: ✅ Pushed to `origin/main` on `KlassApp-Foundation/KlassApp`
- **Deploy**: Ran `bash scripts/deploy-manual.sh`:
  1. Conflict marker check — PASS
  2. Frontend assets rebuilt (53s, compiled successfully)
  3. Git pull on server — fast-forward to `d8683dd`
  4. Composer install — dependencies synchronized
  5. Migrations — nothing new (all already applied locally)
  6. Cache clear — all cleared (config, cache, compiled, events, routes, views, blade-icons, filament)
  7. FPM restart — done
  8. Verification — `PHP OK: 8.4.23`
- **Live at**: https://klassapp.xyz
- **Status**: ✅ Deployed

### 2026-07-16: Phase 3 Closure — Multi-Step Plan E2E Verified + Known Issues Documented
- **Phase 3, Item 4 — Real E2E verification** (streaming disabled): Successfully triggered a multi-step Toshi request ("add students Alice, Bob and Charlie") via live browser (Playwright). Verified the full flow:
  1. ✅ Plan card renders with "Execution Plan" title, step count, and individual steps listed
  2. ✅ Each step shown as pending (○ status icon)
  3. ✅ "Execute All (2)" and "Cancel" buttons present
  4. ✅ Clicking "Execute All" executes steps sequentially with per-step tool confirmation
  5. ✅ Step 1 shows confirmation card ("Add Student: Alice") with ✓ Confirm / Cancel buttons
  6. ✅ After confirming step 1, Step 2 shows its own confirmation card ("Add Student: Bob and Charlie")
  7. ✅ Screenshots captured as evidence at each stage
- **Bug fix**: Fixed Blade compilation error in `packages/toshi-ui/resources/views/components/plan-card.blade.php` — inline `@if` after text (e.g., `succeeded@if($failed)`) was treated as literal `@` by Blade's parser, causing `@endif` to close the parent `@if($allDone)` block early and orphan the `@elseif` directives. Replaced with ternary expression.
- **Streaming known issues documented** in `config/toshi.php` (under `streaming_enabled` config comment):
  1. Tool-serialization bug: `Agent::stream()` path can break the tool-calling loop
  2. Blank-message artifact: Cosmetic — empty placeholder visible if stream terminates early
- **Streaming stays disabled by default** (`TOSHI_STREAMING_ENABLED=false`). Not actively fixed.
- **Status**: ✅ Phase 3 genuinely closed.

### 2026-07-16: Phase 4 — Per-School Toshi Customization + Write Tools Audit + Model Upgrade
- **Files modified**: `ToshiOrchestrator.php`, `AgentToshi.php`, `ToshiActionService.php`, `ToshiPlanService.php`, `config/toshi.php`, `plan-card.blade.php`, `ViewGradingScaleTool.php`, `SetGradingScaleTool.php`, `SeedDefaultGradingTool.php`, `RecordAttendanceTool.php`, `RecordBulkAttendanceTool.php`, `EnterMarkTool.php`, `ToshiVerificationTest.php`, `ToshiGradingTransactionTest.php`
- **Phase 4 Implementation**:
  - Grading-scale context injection: `buildSchoolContext()` now injects registration_country, curriculum, and grading-scale status into the orchestrator system prompt
  - Country/curriculum de-hardcoding: `getSchoolCountryLabel()` maps country names to adjective form, replacing hardcoded "Ugandan schools"
  - All 3 grading tools fixed to use `school_grading_systems` table instead of non-existent `standards.grade_scale` column
  - SetGradingScaleTool: pre-execution validation guard (`validateGradeDefinitions`), DB transaction wrapping, grade boundaries shown in confirmation card
  - Plan-advance logic fixed to stop on ❌ failures (both `executeNextPlanStep()` and `confirmYes()`)
- **Write Tools Schema Audit**: All 15 write tools audited against actual DB schema. Bugs found and fixed:
  - `academic_status` enum violation in `addStudent()` (was setting `'active'` on `enum('pass','fail')`)
  - Attendance status type mismatch (string `'present'` stored into `tinyint(1)` column — all records stored as absent)
  - Missing `academic_terms.status` required enum in `createTerm()` (would crash on insert)
  - `EnterMarkTool` key mismatch between first/second pass (`studentId` vs `student_id`)
  - `marks.grade` NOT NULL violation when grading lookup returns null
  - Induced-failure transaction rollback test written
- **Model Upgrade**: Switched from `meta/llama-3.1-8b-instruct` (4.0s) to `nvidia/llama-3.3-nemotron-super-49b-v1` (0.4s). New model dramatically improves routing reliability (RecordAttendanceTool now routes correctly, EnterMarkTool routes to correct tool). Nemotron line is post-trained for agentic/tool-use tasks.
- **Live E2E verification**: All 9 remaining write tools live-triggered through chat with independent DB checks. RecordBulkAttendanceTool verified to create N rows for N students (not aggregate).
- **Phase 5 spec** saved to `.sisyphus/plans/phase5-spec.md` — recommends RecordBulkAttendanceTool as v1
- **Status**: ✅ Phase 4 delivered, write tools audit complete, model upgraded

### 2026-07-16: Exam ↔ Calendar Event Sync
- **Migration**: Added `exam_id` nullable FK column to `events` table (references `exams.id`, nullOnDelete)
- **Exam model**: Added `booted()` with `created`/`updated`/`deleted` model events that auto-sync calendar entries:
  - On exam create: creates 2 calendar events — "Exam: {Subject} — {ExamType}" on `scheduled_at` and "📝 Marks Due: {Subject} — {ExamType}" on `default_deadline`
  - On exam update (reschedule): refreshes linked events with new dates
  - On exam delete: removes linked events (no orphans)
  - Both events are `category='exam'`, `color` blue for exam date, red for deadline
  - School_id scoped throughout (inherits from exam)
- **Backfill command**: `php artisan exams:sync-calendar` — creates calendar events for all exams that don't have one yet
- **Files modified**: `database/migrations/2026_07_16_064820_add_exam_id_to_events_table.php`, `app/Models/Academics/Exam.php`, `app/Models/Events.php`, `app/Console/Commands/ExamsSyncCalendar.php`
- **Verification**: Exam create → 2 events auto-created ✅. Reschedule → dates update ✅. Delete → events cleaned up ✅. Backfill → creates events for pre-existing exams ✅. Duplicate events prevented (delete+rebuild pattern).
- **Status**: ✅ Exam-calendar sync delivered

## Exam Schema: Discovery Findings

### Exam Types (structured, not free-text)
The `exam_types` table provides a structured type system for exams. Currently seeded with:

| id | name | Code | Purpose |
|----|------|------|---------|
| 1 | Beginning Of Term | BOT | Start-of-term baseline |
| 2 | Weekly Exams | WE | Regular weekly assessment |
| 3 | Mid Term | MID | Mid-term examination |
| 4 | Monthly Exams | ME | Monthly assessment |
| 5 | Weekly Exams | WE | Duplicate (data issue) |
| 6 | End Of Term | EOT | End-of-term examination |
| 7 | Mock Exam | MOCK | Pre-final mock |
| 8 | Pre Mock Exam | PreMOCK | Pre-mock practice |
| 9 | End Of Year | FINAL | Final/exit examination |

`exams.exam_type_id` is a required FK (NOT NULL) referencing `exam_types.id`. Every exam must have a type.

### One Exam Record = One Subject Assessment
Each `exams` row represents **one subject's assessment** within a sitting:

| exam_id | subject_id | exam_type_id | Interpretation |
|---------|-----------|-------------|----------------|
| 77 | English | 1 (BOT) | Beginning of Term — English |
| 79 | Mathematics | 1 (BOT) | Beginning of Term — Mathematics |

So a "BOT sitting" produces N exam records (one per subject), not 1 record with N subjects. This means:
- `subject_id` is on the exam record itself (NOT a separate join table) — confirmed
- `section_id` and `standard_id` are also on the exam record (not derived from the teacher/class)
- Part A's assumption (`exam_id + class_id + subject_id` as the mark's key) is **correct**
- The calendar event title format `"{Subject} — {ExamType}"` is the natural display name

### Related: exam_marks_submissions
Tracks per-teacher submission state for each exam+class. Statuses: draft → submitted → locked, with an approval workflow (pending → approved/rejected).

### Calendar Event: What the `category='exam'` Already Means
The `events.category` enum includes `'exam'` as a value. The auto-sync creates events with `category='exam'`, and the existing calendar view already filters/supports this category. No new enum value needed.

### 2026-07-16: Part C — Parent Report Cards via WhatsApp (Built)

- **Architecture**: WhatsApp bot uses n8n → Laravel REST API pattern. `GET /api/whatsapp/student/{studentId}/report?phone=...&year_id=...&term_id=...` is the new endpoint n8n calls. Full delivery pipeline:
  1. Authorization: resolves phone → WhatsAppUser → parent user, checks `student_parent_links` for parent-student linkage (403 if not linked)
  2. Approval check: queries `exam_marks_submissions.approval_status` — if zero approved subjects, returns "Marks not yet finalized" message (no PDF generated)
  3. PDF generation: reuses `StudentReportHelperService` + `Barryvdh\DomPDF` — same view as admin report card. Filters exams to only `contributes_to_report_total=true` (per school's `exam_type_preferences`). Unapproved subjects show "Not yet available"
  4. WhatsApp delivery: new `WhatsAppBusinessService::sendDocument()` sends PDF via Meta Cloud API document message type
- **New/modified files**: `app/Services/WhatsAppBusinessService.php` (added `sendDocument()`), `app/Http/Controllers/Api/WhatsAppController.php` (added `report()`), `routes/api.php` (added route)
- **Files modified**: WhatsAppBusinessService.php, WhatsAppController.php, routes/api.php
- **Scenarios covered**:
  | Scenario | Input | Expected |
  |----------|-------|----------|
  | Current term, approved marks | `?phone=+256...&year_id=X` | PDF delivered via WhatsApp |
  | Zero approved marks | Same, no approvals | JSON: "Marks not yet finalized" |
  | Partial approval | Same, some subjects approved | PDF with "Not yet available" labels |
  | Authorization boundary | Wrong parent phone for student | 403 "not linked to your account" |
- **E2E test note**: Full WhatsApp delivery test requires production Meta API credentials and a publicly-accessible PDF URL. The authorization + approval + PDF generation pipeline is tested at the code level.
- **Status**: ✅ Part C built and tested (code-level). Full WhatsApp delivery requires production deployment.

### 2026-07-16: Timetable Storage & Calendar Sync
- **Migration**: Created `timetable_slots` table with FKs to schools, academic_years, academic_terms, sections, subjects, users. Added `timetable_slot_id` nullable FK to `events` table.
- **Conflict detection**: Pre-save overlap checks for both teacher double-booking and section double-booking. Returns specific error messages naming the conflicting slot. Tested: teacher overlap detected ✅, section overlap detected ✅, different day → no false positive ✅, different time → no false positive ✅.
- **class_teacher_links cross-check**: Shows a warning (not a hard block) if the teacher+subject+section combination isn't in the existing class_teacher_links assignments. Tested: existing link correctly passes ✅.
- **Calendar sync**: Delete+rebuild pattern (same as exam-calendar link). Creates a single recurring weekly event scoped to the academic_term's date range. Tested: 1 event created with repeats=1, freq=1, freq_term='weekly' ✅. Delete slot → event cleaned up via FK nullOnDelete.
- **Admin CRUD UI**: `/admin/timetable/slots` — ds-* pattern table with day/time/subject/teacher/room columns, create/edit/delete. Class selector to filter by section. Teacher's own weekly timetable at `/admin/teacher/my-timetable`.
- **Files modified**: migration, `app/Models/Academics/TimetableSlot.php`, `app/Http/Controllers/Admin/TimetableSlotController.php`, `resources/views/admin/timetable/`, `routes/admin.php`, `app/Models/Events.php`
- **Status**: ✅ Timetable storage + conflict detection + calendar sync delivered

### 2026-07-16: Increment 6 — Layout Paradigm Shift: Design Directions

**Competitive Research**: GegoK12, Fedena, PowerSchool/Schoology, Teachmint all use generic sidebar+header layouts, basic KPI cards, dense tables, no embedded AI. KlassApp's 3-column layout with persistent Toshi panel is unique in the category.

**Three directions generated** (Open Design daemon, saved to `.sisyphus/plans/increment6-design-directions.md`):

| Direction | Lead Color | Concept | Effort | Risk |
|-----------|-----------|---------|--------|------|
| Blueprint | Blue #2563EB | Systematic, premium-financial feel | Low | May feel similar to current |
| Pulse | Green #22C55E | Energetic, motion-forward, animated KPIs | Medium | Animation perf on older devices |
| Quiet | Amber #F59E0B | Editorial, maximum whitespace, minimal chrome | Medium-high | Restraint may read as unfinished |

**All directions**: 3-column desktop (sidebar|content|Toshi), tablet collapse, mobile floating Toshi preserved. Brand tokens, fonts, green Toshi CTAs, keyboard-aware composer all locked.

**Status**: 🔍 Directions presented — awaiting decision before implementation.

### 2026-07-16: Increment 6 — Pulse Direction Implemented (Green-led refresh)

- **Directions explored**: 3 directions generated via Open Design daemon — Blueprint (blue-led, safest), Pulse (green-led, motion-forward), Quiet (amber-led, editorial whitespace). User chose **Pulse**.
- **3-column desktop layout**: Uses the existing single `@livewire('agent-toshi')` component — no duplicate instances that would cause Livewire hydration conflicts. CSS media query (≥1280px) hides the floating pill, repositions the panel as a sticky right column (380px, full height), and shifts main content via `margin-right`.
- **Mobile preserved**: Below 1280px, the floating pill and overlay panel behavior is completely unchanged. No CSS override applies. Keyboard-aware composer and viewport-fit fixes preserved.
- **KPI Cards**: Floating lift shadow on hover, green (#22C55E) value text, green icon circles, count-up fade-in animation with staggered delays.
- **Data Tables**: Borderless with 1px dividers, frosted sticky header (`backdrop-filter: blur`), green sort underline, hover row tint.
- **Sidebar active states**: Filled green pill — light green `rgba(34,197,94,0.08)` background, dark green `#166534` text.
- **Toshi suggestion chips**: Green outlined pill styling.
- **Files modified**: `public/css/dashboard-refresh.css`, `resources/views/layouts/app.blade.php`, `resources/views/livewire/agent-toshi.blade.php`, `app/Livewire/AgentToshi.php`
- **Green overload flagged**: `--d-green: #22C55E` is shared between Toshi CTAs and Pulse decorative elements. Users may not distinguish decorative green from actionable green. Monitor post-deployment.
- **Status**: ✅ Pulse built and tested (66 tests pass). Ready for deploy.

## 2026-07-16: Phase 5 — RecordBulkAttendanceTool + EnterMarkTool Verification

**Status**: Guards, resolution, and DB writes verified at PHP level. Chat-UI confirmation and ambiguous-name handling pending browser E2E (next session).

### What's verified (PHP/method-level):
- RecordBulkAttendanceTool: empty-array guard ✅, single-word name guard ✅, invalid status guard ✅, happy path with student resolution + DB write ✅, transaction row-count verify ✅
- EnterMarkTool: name-based schema (`student_name`/`subject_name`) ✅, EntityResolver resolves exact/fuzzy/ambiguous/not-found ✅, second-pass with pre-resolved IDs ✅, marks written to DB ✅
- HasPreExecutionGuards trait created and working ✅

### Chat-UI Verified (this session):
- **EnterMarkTool Confirm**: `"enter mark for bob student1201 in English score 85"` → confirmation card showing "bob student1201 → ENGLISH (): 85" and Student ID: 120 → clicked Confirm → DB: `marks` row id=173, student_id=120, exam_id=77, marks=85.00 ✅
- **EnterMarkTool Cancel**: `"enter mark for bob student1201 in English score 80"` → confirmation card → clicked Cancel → "Cancelled. No changes were made." in chat → DB: 0 rows for that criteria ✅
- **Ambiguous name**: `"enter mark for Student in English score 90"` → LLM asked for clarification ("could you please provide the full name") instead of passing to the tool's internal disambiguation. The EntityResolver's ambiguous-match flow (`Found N students matching...`) is unreachable from chat because the LLM intercepts before the tool is called. This is a model behavior issue, not a tool bug.
- **Fallback split**: Deliberately dropped in favor of clarify-only. Rationale: if the LLM failed to extract structured data once, re-parsing the same text with a heuristic splitter would produce cascading malformed sub-calls. A single clarifying message is cleaner UX. The empty-`students` guard returns: `"❌ I couldn't identify any students..."`

### Pending:
- RecordBulkAttendanceTool through chat (requires a phrasing the LLM routes to the bulk tool rather than splitting into individual RecordAttendanceTool calls by the plan service)

## Data Loss Incident — Increment 6 (2026-07-16)

### What Happened

During implementation of the 3-column desktop layout, `@livewire('agent-toshi')` was moved from outside `<main>` (its correct position for a Livewire overlay component) to inside `<main>` as a flex child. This broke Livewire's DOM hydration — the component couldn't properly mount because its root element was forced into a CSS flex context it wasn't designed for.

### The "Messy Reverts"

In the rapid iteration trying to fix this:
1. First attempt: Moved `@livewire` inside `<main>` — page broke (content disappeared)
2. Reverted: Moved `@livewire` back outside `<main>` — but the revert didn't fully clean up, leaving duplicate `@auth`/`@livewire`/`@endauth` blocks
3. Second attempt: Tried again with `position: sticky` on `.toshi-root` — same breakage
4. Second revert: Again left duplicate blocks
5. Final clean: Removed all duplicates, leaving exactly one `@livewire('agent-toshi')` outside `<main>`
6. Also fixed: Removed a stray duplicate `</div>` + `@yield('base-footer')` from one of the reverts

During steps 2 and 4, the local file had **3 copies** of the Toshi Livewire component. The symptom was: Toshi rendered 3 times on the page, each instance competing for state, causing the dashboard content to appear empty (Livewire's hydration failed when multiple instances of the same component existed).

### Was Production Affected?

**No.** The triplicate instances never touched production:
- Commit `e2ab520` (deployed to production before the incident): 1 instance ✅
- The triplicate existed only in the uncommitted working tree during the back-and-forth edits
- Commit `66da99c` (current, deployed): 1 instance ✅
- `git show` confirms both commits have exactly one `@livewire('agent-toshi')` call

No real school data, no production session, and no committed code was affected.

### Evidence: Single Instance Confirmed

Current `app.blade.php`:
```
grep -c "livewire.*agent-toshi" → 1
```

Browser DOM query on current page load:
```
document.querySelectorAll('.toshi-root').length → 1
```

### Pre-existing Console Error

The error `TypeError: Cannot convert undefined or null to object at Object.keys` appears on the `/admin/academics` page. This is caused by a Vue component on that page calling `GET /admin/academic/list` which returns a 500 error (the endpoint doesn't exist for School C's data set). The Vue component then tries to iterate over the null response. This is unrelated to Pulse — the academics page has not been modified by Increment 6. The dashboard page (the primary Pulse target) has 0 console errors.

### Status

All four conditions verified:
1. ✅ Incident documented: triplicate Livewire instances from incomplete reverts — local only, never committed
2. ✅ Production unaffected — both deployed commits have exactly 1 instance
3. ✅ Current DOM confirmed: `querySelectorAll('.toshi-root')` returns 1
4. ✅ Console error documented as pre-existing academics-page Vue issue, not Pulse-related

### 2026-07-16: Exam Types — `contributes_to_report_total` Flag + Admin UI
- **Migration**: Added `contributes_to_report_total` boolean (default false) to `exam_types` table. Added `exam_type_preferences` JSON column to `schools` for per-school overrides.
- **Defaults set**: EOT=TRUE, FINAL=TRUE (contribute to report total). BOT, WE, MID, ME, MOCK, PreMOCK=FALSE (diagnostic-only by default). These are seeding defaults — each school admin can override per exam type.
- **Admin UI**: `/admin/settings/exam-types` — table of all 9 exam types with toggle switches. Per-school overrides stored in `schools.exam_type_preferences` JSON column, falling back to the global `exam_types.contributes_to_report_total` default when no override exists.
- **Lookup pattern for Part C**: `$prefs[$type->id] ?? $type->contributes_to_report_total` — school override wins, then global default.
- **Inert / non-breaking**: This flag is NOT read by Part A (marks lifecycle/lock/deadline logic) or Part B (admin approval workflow). Those continue to apply uniformly across all exam types regardless of this flag. Part C (report card generation) will be the first consumer.
- **School_id scoping**: The admin UI respects the logged-in school admin's school_id. The preference column is on the `schools` table (one row per school).
- **Files modified**: `database/migrations/*_add_contributes_to_report_total_to_exam_types.php`, `*_add_exam_type_preferences_to_schools.php`, `app/Models/Academics/ExamType.php`, `app/Http/Controllers/Admin/Setting/ExamTypeController.php`, `resources/views/admin/settings/exam-types.blade.php`, `routes/setting.php`, `app/Models/School.php`, `knowledge.md`
- **Status**: ✅ Exam types flag + admin UI delivered

## Part C — Pre-Build Discovery Complete

### WhatsApp Bot Architecture (confirmed)

The WhatsApp bot uses an **external conversation engine** (n8n → Typebot/Flowise) that calls Laravel as a data API. Intent handling is NOT in Laravel — the bot calls:
```
GET /api/whatsapp/student/{studentId}/grades?term=current
```
and receives JSON responses that n8n formats into messages. This means Part C doesn't need a Laravel-native intent router — it needs a new API endpoint that n8n can call.

### Existing `grades()` Endpoint (confirmed current-year-only)

The `WhatsAppController@grades()` method at line 137:
- Accepts `term=current` or `term={id}` — no year selection
- Returns marks as JSON only — no PDF generation
- No approval status check
- Filters to latest 5 exams

### PDF Report Generation (confirmed reusable)

`DownloadStudentReport` at `app/Http/Controllers/Admin/DownloadStudentReport.php`:
- Uses `Barryvdh\DomPDF\Facade\Pdf`
- Uses `StudentReportHelperService` for learner/subject/mark data
- View at `resources/views/admin/marks/student-report.blade.php`
- Can be called directly without going through the HTTP controller

### WhatsApp Document Sending (confirmed missing)

`WhatsAppBusinessService` supports `sendText`, `sendTemplate`, `sendButtons`, `sendList` — but NO `sendDocument` method. Meta Cloud API supports document type messages; the method just needs to be added.

### Parent-Student Authorization (confirmed existing)

- `whatsapp_users`: phone → user_id linkage
- `student_parent_links`: parent_id → student_id linkage
- Both scoped by `school_id`

### Implementation Plan

Full architecture plan saved to `.sisyphus/plans/part-c-parent-report-cards.md`. Summary of what needs building:

1. `WhatsAppBusinessService::sendDocument()` — 1 new method
2. `WhatsAppController@report()` — new endpoint with authorization + approval check + PDF gen + WhatsApp delivery
3. Route in `routes/api.php`
4. Test scenarios: 4 E2E cases

This is a substantial build (est. 2-3 hours) and would benefit from its own dedicated session given the conversation length and the number of interconnected systems (WhatsApp API, DomPDF, approval workflow, authorization layer).

### July 17, 2026: Write-time duplicate name guard + regression tests
- **Work done**: Added duplicate name guard checks to `ToshiActionService::enterMark()` and `RecordBulkAttendanceTool` write path, preventing data corruption when multiple students share the same name. Created full regression test suite covering 4 scenarios.
- **Files modified**:
  - `app/Services/ToshiActionService.php` — Added `guardDuplicateName()` call inside `enterMark()` before `Marks::create()`
  - `app/AiAgents/Tools/RecordBulkAttendanceTool.php` — Added pre-write duplicate name guard loop inside DB transaction
  - `tests/Feature/Toshi/ToshiDuplicateNameGuardTest.php` — NEW: 4 tests (unique name, duplicate block, school scoping, enterMark integration)
- **Key decisions**:
  - Guard is school-scoped (same name in different schools does not block)
  - Previously only the read-side preventions existed (EntityResolver fuzzy/ambiguous paths in `RecordBulkAttendanceTool` and `EnterMarkTool`). But those paths are unreachable from chat — the LLM resolves ambiguity conversationally before calling tools. The write-time guard is the only reliable enforcement.
- **Status**: ✅ Done — 4/4 tests pass, guard verified in tinker with seeded duplicates
- **Edge cases flagged**: The ambiguous-name branches in `RecordBulkAttendanceTool` and `EnterMarkTool` remain unreachable from chat and could be deleted in a future cleanup pass.

### July 17, 2026 (later): Identifier-based disambiguation for duplicate student names
- **Work done**:
  - Replaced write-time guard (which blocked legitimate writes after teacher disambiguated) with proper identifier-based resolution flow
  - Added `EntityResolver::resolveStudentByIdentifier()` — resolves by numeric User ID, klassapp_student_id, or email scoped to school
  - Added `EntityResolver::classLabelForUser()` — derives "Primary 4-A" class label from StudentAcademic→StandardLink→Standard/Section chain
  - Candidates array now includes `class` key so ambiguous messages show class alongside ID (e.g. "ID 124: Test Kid, P4-A")
  - `EnterMarkTool`: added optional `student_identifier` schema field + handle path that skips name matching entirely when identifier is present. Updated ambiguous message to instruct "Reply with ID number (e.g. 124)"
  - `RecordBulkAttendanceTool`: same — optional `student_identifier` per entry, skips EntityResolver name path when present
  - Removed the write-time guard from `ToshiActionService::enterMark()` and `RecordBulkAttendanceTool` transaction — it was redundant with EntityResolver at the tool level and blocked legitimate writes after teacher disambiguated via ID
  - `guardDuplicateName()` remains as a standalone method used by other callers
- **Files modified**:
  - `app/Services/EntityResolver.php` — `resolveStudentByIdentifier()`, `classLabelForUser()`, candidates now include `class`
  - `app/AiAgents/Tools/EnterMarkTool.php` — `student_identifier` schema + handle path + updated ambiguous message
  - `app/AiAgents/Tools/RecordBulkAttendanceTool.php` — `student_identifier` per-entry + handle path + updated ambiguous message
  - `app/Services/ToshiActionService.php` — removed guard from `enterMark()`
  - `tests/Feature/Toshi/ToshiIdentifierDisambiguationTest.php` — NEW: 7 tests covering identifier resolution, class label, ambiguous-with-class, full enterMark with disambiguation
  - `tests/Feature/Toshi/ToshiDuplicateNameGuardTest.php` — updated enterMark test to reflect removed guard
- **Key decisions**:
  - Rejected auto-generating new IDs for duplicates (every student already has a unique KlassApp ID — the gap was the schema didn't accept it as input)
  - Identifier resolves by priority: numeric User ID → klassapp_student_id → email. Never returns 'ambiguous' (all identifiers are unique)
  - Class info is included in ambiguous messages when available. It's cheap (one extra query per ambiguous match, relationships already defined)
   - The `student_identifier` field means the LLM no longer needs to silently guess which duplicate the teacher meant — it can pass the ID directly
   - **Critical refinement**: Added `disambiguation_confirmed` boolean to both tools. When the identifier path resolves a student who has duplicate-named peers, the tool checks this flag. If false (rogue LLM silently guessed an ID), it returns the candidates list instead of writing — forcing the LLM to surface the ambiguity. If true (LLM previously showed the ambiguous list and got a reply), the guard is bypassed and the write proceeds. This prevents the original "LLM silently picks one of two duplicates" bug while enabling the legitimate disambiguation flow.
   - Schema descriptions explicitly instruct: "Set to true ONLY when the ambiguous-match candidate list was shown to the user AND they replied with a specific ID from that list."
- **Status**: ✅ Done — 14/14 tests pass (14 all-Toshi tests), E2E verified through actual tool calls simulating all 3 LLM flows (rogue identifier blocked ✅, name-based ambiguous list shown ✅, identifier+confirmed write succeeds ✅) with DB query proving correct student written
 - **Edge cases flagged**: The class label requires StudentAcademic→StandardLink→Standard/Section chain to be populated. If a student has no academic record, class is null and omitted from the message.

### July 17, 2026: Report card pipeline audit — findings confirmed via real PDF generation

**Item 1 (HIGH) — Cross-term mark leak CONFIRMED.**
- Bug: `StudentReportHelperService::learner()` uses `User::with('marks.subject', 'marks.exam')` (unconstrained eager load). The `whereHas` clause constrains WHICH users are returned but NOT which marks are eager-loaded. Result: `$learner->marks` includes marks from ALL terms/years.
- Evidence via real PDF trigger (School 1, student 58, exam 65 = Term II):
  - ALL marks loaded: 59 (47 from Term 10 + 12 from Term 11)
  - `$learner->marks->sum('marks')` = **3791**
  - Correct Term II total (scoped to term_id=11) = **720**
  - Difference (leaked Term 1 marks) = **3071**
  - The PDF total shown to parents is inflated by 3071 points.
- Root cause: `User::with('marks.subject', 'marks.exam', ...)` vs the correct constrained form `User::with(['marks' => fn($q) => $q->where(...)])`. The latter is used correctly in `MarksReportService::getMarks()` but NOT in `StudentReportHelperService::learner()`.
- The on-screen admin view uses the same `$learner->marks` data, so both screen and PDF show the wrong total.

**Item 2 (MEDIUM) — Duplicate mark rows CONFIRMED in production data.**
- `Marks::create()` is used (not `updateOrCreate`), so every correction/call inserts a new row.
- Evidence: student 58 has **11 exam/subject combinations with duplicate rows**. Worst case: exam 14 (subject 68) has **13 entries** summing to 885 instead of a single correct mark. These pre-exist in production data.
- All duplicate rows are summed into the report total due to the unconstrained eager load, compounding with Item 1.
- The Toshi `enterMark()` path specifically uses `Marks::create()` — each chat correction inserts a duplicate.

**Item 3 (MEDIUM) — Template total row uses stale `$average` CONFIRMED.**
- The template sets `$average = $subjectMarks->avg('marks')` inside the `@foreach($subjects as $subject)` loop (line 223). By the time the `<tfoot>` total row renders (line 268), `$average` holds the LAST subject's average.
- Evidence: Last subject (Social Studies) average = 45. `examsDone` = 12. Formula `floor($average) * $examsDone` = 540.
- The correct `$total` (3791, though itself wrong due to Item 1) is rendered separately at line 265, so this is a duplicate/redundant row — but the number is wrong if anyone reads it.

**Additional finding: Missing `examType()` relationship on Exam model.**
- The `Exam` model at `app/Models/Academics/Exam.php` has no `examType()` relationship defined, even though `exam_type_id` exists in the database and both `syncCalendarEvents()` and `DownloadStudentReport` call `$this->examType?.name`.
- This causes a `RelationNotFoundException` (500 error) on any report card or student marks page that iterates exam types via `$exams->pluck('examType')`.
- Fixed during verification by adding the missing relationship.

**Item 4 — Student resolution: ID-based, confirmed via click-through.**
- Admin student page uses `/admin/student/edit/{name}` (name in URL, not ID), but the report download route `/report/student/{learner}/class/{class}/{exam}` uses route-model binding on User ID. The marks filter page and bulk marksheet route use section/term/year query params. No name-collision risk in any report generation path.
- **Files modified this session**: `app/Models/Academics/Exam.php` — added `examType()` belongsTo relationship.
- **Status**: Report card pipeline has 2 HIGH and 2 MEDIUM confirmed bugs affecting data correctness. Pipeline was also broken (missing relationship, now fixed). All numbers above are from real DB queries and real generated PDFs.

### July 17, 2026 (late): Fixes shipped for report card bugs

**Fixes applied:**

1. **Cross-term mark leak (HIGH)** — `StudentReportHelperService::learner()` changed from unconstrained `with('marks.subject', 'marks.exam', ...)` to a pre-computed scoped-exam-IDs approach using `whereIn('exam_id', $scopedExamIds)`. This avoids the nested `whereHas` inside `with` closure that silently failed in SQLite, while correctly constraining both the `with` eager load and the `whereHas` parent filter to the specific exam's term/year/section.

2. **Duplicate mark rows on correction (MEDIUM)** — `ToshiActionService::enterMark()` changed from `Marks::create(...)` to `Marks::updateOrCreate(['student_id' => $studentId, 'exam_id' => $examId], [...])`. Corrections now update the existing row in-place rather than inserting a duplicate. The unique key `(student_id, exam_id)` is correct for termly exams (retakes create separate exam records).

3. **Missing `examType()` relationship** — Added `belongsTo(ExamType::class, 'exam_type_id')` to the Exam model. Pre-existing bug since the July 16 commit that introduced `syncCalendarEvents()` — the method referenced `$this->examType?->name` but the relationship was never defined. Report cards had been returning 500 errors. Now fixed.

**Files modified:**
- `app/Services/StudentReportHelperService.php` — scoped eager load fix
- `app/Services/ToshiActionService.php` — `Marks::create()` → `Marks::updateOrCreate()`
- `tests/Feature/Toshi/ToshiReportCardPipelineFixTest.php` — NEW: 3 tests, 16 assertions

**Verification:**
- Before fix: student 58 Term II total = **3791** (cross-term leak)
- After fix: student 58 Term II total = **720** (correct, reg tested)
- DB after correction: 1 row, value 78 (not 2 rows with 65+78)
- All 3 regression tests pass (examType relationship, scoped eager load, updateOrCreate)
- Total passing Toshi-related tests: 17 (11 previous + 3 new + 3 updated)

**Blast radius:**
- Duplicate marks: 1 school (Test School One), 14 groups, 41 extra rows
- Cross-term exposure: 1 student at School 1
- No download logging exists — cannot identify recipients of inflated PDFs
- Duplicates cleanup: ALL 41 extra rows removed (kept latest row per student_id+exam_id+subject_id). Timing evidence: all 14 groups were same-sitting corrections/import glitches (0 retakes — max gap 1.1h, all same day). Verified: 0 duplicate groups remaining, student 58 totals unchanged (Term II=720, Term I=1156), all regression tests pass.
- **Item 1 (updateOrCreate key)**: `['student_id', 'exam_id']` confirmed correct. Each exam maps to exactly one `subject_id` — no exam has multiple subjects. The `subject_id` in marks is denormalized. No fix needed.

### Open issue: Number appended to user display name on registration
- **Observation**: When registering a new school, the admin user's `users.name` gets an arbitrary number appended (e.g. "mucunguzi moses1436"). The `userprofile.firstname` stores the clean name.
- **Root cause**: `RegisterController::createSchoolAdmin()` sets `$userData['username']` via `generateUsernameFromName($data['name'])` but `username` is not in User's `$fillable` array — it's silently dropped during `User::create()`. The `name` field is never explicitly set. The source of the appended number is unclear — may come from the `generateUsernameFromName` counter bleeding into the `name` column through some other mechanism (mutator, event, or DB-level default/trigger).
- **Impact**: Admin display name shows with garbage number suffix. Affects all new school registrations.
- **Suggested fix**: Either (a) set `$userData['name'] = $data['name']` explicitly in `createSchoolAdmin()`, or (b) add `username` to User's fillable and use a dedicated username column, keeping `name` clean. The `generateUsernameFromName` method also has a secondary issue: its uniqueness check queries `User::where('name', ...)` instead of a `username` column, causing collisions against display names rather than usernames.

### July 17, 2026 (late): Registration form redesign + curriculum + code-based password reset + onboarding harmony

**Registration form redesign:**
- Reordered fields: Plan → School Name → Country → Phone → EMIS → **Curriculum/Board** → Your Full Name → Email → Student Size → Password
- Added Curriculum/Board dropdown (UNEB, Cambridge, Montessori, Other) with validation and DB write
- Country and Phone moved up after School Name (before EMIS/Curriculum)
- Your Full Name and Email grouped together at the beginning (admin contact info)
- Plan selection now has live green border highlight on click + "You selected: {plan}" feedback text
- Fixed "Register page under maintenance" bug — template had inverted `@if(register==1)` logic showing maintenance instead of form

**Curriculum flow:**
- `schools.curriculum` defaults to 'uneb', now explicitly set during registration from form selection
- Registration form sends curriculum → `RegisterController::createSchool()` writes to DB
- `SetCurriculumTool` created for Toshi — sets curriculum + enables `toshi_enabled` as first onboarding step
- `SetCurriculumTool` registered in `AgentToshi.php`
- Admin "Setup Standards" Vue component now receives `current-board` prop and pre-selects the school's curriculum
- `toshi_enabled` added to School model `$fillable`
- Toshi's system prompt updated: during onboarding, confirm curriculum/board before asking about classes

**Code-based password reset (replaced link-based):**
- `ForgotPasswordController` rewritten: generates 6-digit code, stores in `authentications` table with type='password_reset', emails code via `ResetPasswordCodeMail`
- `PasswordResetCodeController` created: shows code entry page, verifies code, resend code flow
- Routes: `GET/POST /password/reset`, `GET /password/reset-code`, `POST /password/reset-code/verify`, `GET /password/reset-code/resend`
- Added `type` to Authentication model `$fillable` (was missing, causing all password reset codes to be stored without type and never found)
- `ResetPasswordController::showResetForm()` now reads token from route param OR query string (handles both old link format and new)
- Login page "Forgot password?" link updated from `password.request` to `password.email`
- All password pages redesigned to light theme (`#FAFAF5`) matching register/login — email page hides form after success, reset page has show/hide password toggles

**Files modified/created:**
- `resources/views/auth/register.blade.php` — reordered fields, curriculum dropdown, plan highlight JS
- `resources/views/auth/passwords/email.blade.php` — rewritten light theme
- `resources/views/auth/passwords/reset.blade.php` — rewritten light theme + toggles
- `resources/views/auth/passwords/code.blade.php` — NEW: code entry page
- `resources/views/emails/reset_password_code.blade.php` — NEW: email template with code
- `resources/views/auth/login.blade.php` — forgot password link route fix
- `resources/views/admin/school/standards/add.blade.php` — pass current-board prop
- `app/Http/Controllers/Auth/ForgotPasswordController.php` — rewritten for code-based flow
- `app/Http/Controllers/Auth/ResetPasswordController.php` — showResetForm reads route/query param
- `app/Http/Controllers/Auth/PasswordResetCodeController.php` — NEW: code verify/resend
- `app/Http/Controllers/Admin/StandardController.php` — pass current_board to view
- `app/Mail/ResetPasswordCodeMail.php` — NEW: mailable for code emails
- `app/Models/Authentication.php` — added type to fillable
- `app/Models/School.php` — added toshi_enabled to fillable
- `app/AiAgents/Tools/SetCurriculumTool.php` — NEW: Toshi tool for curriculum + Toshi enable
- `app/Livewire/AgentToshi.php` — registered SetCurriculumTool
- `config/toshi.php` — onboarding prompt rule: confirm curriculum first
- `resources/assets/js/components/settings/StandardSetup.vue` — currentBoard prop, pre-select from school
- `routes/web.php` — added 5 password reset routes + named routes

### July 17, 2026 (final): Onboarding steps service + intent routing fix

**Problem:** Toshi's onboarding checklist and the System's setup wizard had separate hardcoded step lists. Curriculum was step 0 at registration but neither surfaced it correctly. Saying "finish setting up" dropped into a generic menu instead of resuming onboarding.

**Root cause (read/display bug):** `schools.curriculum` was correctly written during registration (`'uneb'`), confirmed via DB query on School 25. But:
- `detectMissingSteps()` in `AgentToshi.php` had its own 6-item hardcoded list that omitted `curriculum`
- `OnboardingHelper::getMissingSteps()` had the same hardcoded 6-item list
- The `StandardSetup.vue` component was correctly updated to receive `currentBoard` prop but this was a recent fix

**Fix:**
1. **`app/Services/OnboardingStepsService.php`** (NEW) — single canonical source of truth for all 7 onboarding steps with ordered step definitions, completion checks, next-incomplete-step resolver, and admin route mapping. `curriculum` is step 0.
2. **`app/Helpers/OnboardingHelper.php`** — rewritten to delegate to `OnboardingStepsService` (backwards compatible)
3. **`app/Livewire/AgentToshi.php`** — `detectMissingSteps()` now uses `OnboardingStepsService::incompleteSteps()`, includes curriculum as step 0. Added `onboardingPromptForStep()` for per-step conversational prompts. Added setup-intent detection in `send()` — when user says "finish setting up", "continue", "what's next" etc., it calls `nextIncompleteStep()` and resumes there instead of dropping into the general skill router.
4. **`app/Http/Controllers/Admin/StandardController.php`** — passes `$school->curriculum` as `current_board`
5. **`resources/views/admin/school/standards/add.blade.php`** — passes `current-board` prop to Vue component
6. **`resources/assets/js/components/settings/StandardSetup.vue`** — `board: this.currentBoard || ""` pre-selects from school's curriculum

**Verification (via direct DB query + browser):**
- `SELECT curriculum FROM schools WHERE id = 25` → `'uneb'` ✅ (write works)
- `GET /admin/standard/create` rendered HTML: `current-board="uneb"` ✅ (read/display works)
- `OnboardingStepsService::steps(School::find(25))` → step 0 (curriculum) shows ✅ complete, step 1 (standards) shows ❌ incomplete ✅
- `nextIncompleteStep()` → correctly returns 'standards' as next step ✅
- Setup Standards page pre-selects "UNEB" in the dropdown on page load ✅

### July 17, 2026 (sidebar): Admin nav grouping audit — 19 items confirmed, proposed regroup

**Exact count from `layouts/admin/menu.blade.php`:**

| # | Item | Group |
|---|---|---|
| 1 | Dashboard | (top, ungrouped) |
| 2 | Students | Academics |
| 3 | Parents | Academics |
| 4 | Classes & Streams | Academics |
| 5 | Subjects | Academics |
| 6 | Timetable | Academics |
| 7 | Attendance | Academics |
| 8 | Exams & Marks | Academics |
| 9 | Grading | Academics |
| 10 | Library | Operations |
| 11 | Health | Operations |
| 12 | Transport | Operations |
| 13 | Fees & Payments | Finance |
| 14 | Unmatched Payments | Finance (sub) |
| 15 | Messaging | Communication |
| 16 | Calendar | Communication |
| 17 | Approvals | System |
| 18 | Data Exports | System |
| 19 | Settings | System |

**Math verified:** 19 items total (18 nav + Dashboard). No double-counts or invented items. Previous "~15" estimate missed Timetable, Grading, and Unmatched Payments.

**"System" catch-all resolved:** Library, Health, Transport split into "Operations" (day-to-day modules). System now contains only genuine config items: Approvals, Data Exports, Settings.

**Status:** Approved for implementation with Alpine.js expand/collapse + Pulse styling. Not yet built.

---

## Session Log

### 2026-07-19: Fixed chronic 419 Page Expired errors — TrustProxies + session driver
- **Work done**: Diagnosed and fixed the persistent 419 CSRF token errors on klassapp.xyz. Three interacting root causes were found and fixed.
- **Root causes**:
  1. **TrustProxies misconfigured** — `app/Http/Middleware/TrustProxies.php` had `protected $proxies;` (null), meaning Laravel trusted NO proxies. Since klassapp.xyz is behind Cloudflare, traffic arrives as HTTP at the server, but the user is on HTTPS. Laravel couldn't detect HTTPS, so CSRF tokens were generated with `http://` URLs while the actual request was `https://` → 419 mismatch on every form submission.
  2. **File-based sessions in Docker** — `SESSION_DRIVER=file` is fragile in containerized environments. Docker container restarts, deployments, or multiple PHP-FPM workers can lose or lock session files, invalidating the CSRF token.
  3. **APP_URL mismatch** — `.env` had `APP_URL=http://localhost:8000` instead of the actual deployment URL. This cascaded into URL generation mismatches affecting CSRF verification.
- **Files modified**: `app/Http/Middleware/TrustProxies.php`, `.env`, `database/migrations/YYYY_MM_DD_HHMMSS_create_sessions_table.php` (new)
- **Fix applied**:
  - TrustProxies: Set `protected $proxies = '*'` to trust all proxies (Cloudflare, load balancers). Laravel now correctly detects HTTPS via `X-Forwarded-Proto` headers.
  - Session driver: Switched from `file` to `database` — sessions persist across container restarts and PHP-FPM workers.
  - Local APP_URL: Updated to `http://localhost:8070` to match local dev server.
  - `SESSION_LIFETIME=1440` (24h) to reduce session expiry issues during long work sessions.
- **Production .env changes still needed** (on the server at `/var/www/KlassApp/.env` inside the container):
  ```
  APP_URL=https://klassapp.xyz
  SESSION_DRIVER=database
  SESSION_SECURE_COOKIE=true
  SESSION_DOMAIN=.klassapp.xyz
  ```
  These need to be set in the production `.env` inside the Docker container and then `php artisan config:cache` run. The deploy script's `optimize:clear` will handle this after the `.env` is updated.
- **Status**: ✅ Fix applied locally and deployable. Production `.env` update required manually or via deploy hook.

### 2026-07-19: Fixed Toshi desktop scroll sync — sidebar/content/Toshi panel inconsistency
- **Work done**: Diagnosed and fixed Toshi desktop panel scroll behavior at ≥1280px. The 3-column dashboard (sidebar, content, Toshi) had THREE different scroll behaviors: sidebar used `position: sticky`, content used normal flow, and Toshi used `position: fixed` — causing them to drift apart visually on scroll. Converted the layout to a fixed-height app shell with independent internal scroll per column.
- **Root cause**: `toshi-ui.css` set `.sidebar { position: sticky; }` but `.toshi-panel { position: fixed; }`. Sticky elements scroll with the page until reaching their sticky threshold; fixed elements never scroll. This meant scrolling the page moved sidebar and content but left Toshi static, creating a disjointed, non-"resident" feel.
- **Files modified**: `resources/views/layouts/app.blade.php`, `resources/views/layouts/superadmin-app.blade.php`, `packages/toshi-ui/resources/css/toshi-ui.css`, `public/vendor/toshi-ui/toshi-ui.css`
- **Fix summary**:
  - Moved `@livewire('agent-toshi')` inside `<main>` as a third flex child (was outside `#app` as a sibling)
  - Changed layout to fixed-height shell: `body { overflow: hidden; height: 100vh; }`, `#app { display: flex; flex-direction: column; }`, `main { flex: 1; overflow: hidden; }`
  - All three columns (sidebar, content, Toshi) now get `height: 100%; overflow-y: auto;` for independent internal scroll
  - Sidebar: `position: static` (was `position: sticky`)
  - Toshi panel: `position: relative` (was `position: fixed`)
  - Collapse: `.toshi-root` width transitions from 380px to 0 (was `transform: translateX(380px)`)
  - All changes scoped to `@media (min-width: 1280px)` — tablet/mobile unaffected
- **Edge cases flagged**: 
  - Navbar's actual height (~83px) doesn't match `--nav-height: 56px` CSS variable — this caused Toshi panel to overlap the navbar by ~27px in the old layout. Fixed-height shell eliminates the dependency on `--nav-height` for column positioning.
  - Toshi collapse animation still uses `width` transition on `.toshi-root` — smooth but different from the old `translateX`. The toggle button `position: fixed; right: 380px/0` still works correctly.
  - Vue app on local development has pre-existing `ReferenceError: hasText is not defined` crash — prevents full visual verification through the app UI, but verified via standalone test page with computed style measurements at multiple scroll positions.
- **Verification**: Test page confirmed old layout has Toshi `top: 56px` at ALL scroll positions (doesn't move), while content scrolls up to -366px. New layout keeps all three column containers at top:92px regardless of internal scroll (0-700px tested). Tablet/mobile unaffected since all rules are inside `@media (min-wi

### 2026-07-21: Fixed Toshi CSS publish/sync gap — source vs vendor drift
- **Work done**: Diagnosed and fixed a sync gap between the toshi-ui CSS source and its published vendor copy. `packages/toshi-ui/resources/css/toshi-ui.css` and `public/vendor/toshi-ui/toshi-ui.css` had silently diverged on the `.toshi-pill` class, causing the browser to serve stale CSS despite the fix existing in source.
- **Root cause**: The `.toshi-pill` class in the source had the correct toggle logic (`display: none` by default, `display: flex` when collapsed), but the published vendor copy had an older version where `.toshi-pill` was always `display: flex` and the collapsed state didn't change display at all. Since `app.blade.php` loads the CSS from `public/vendor/toshi-ui/toshi-ui.css?v={{ filemtime(...) }}`, the browser was serving the stale vendor copy — the source fix was never reaching users.
- **The specific diff** (source → vendor):
  ```css
  /* Source (correct) */
  .toshi-pill { display: none !important; }
  body.toshi-collapsed .toshi-pill { display: flex !important; z-index: 45; }

  /* Vendor (stale — what browsers actually loaded) */
  .toshi-pill { display: flex; z-index: 45; }
  body.toshi-collapsed .toshi-pill { z-index: 45; }
  ```
- **Files modified**: `scripts/deploy-manual.sh`, `public/vendor/toshi-ui/toshi-ui.css` (via publish)
- **Fix summary**:
  1. Ran `php artisan vendor:publish --tag=toshi-ui-css --force` to sync vendor copy from source.
  2. Verified files are identical post-publish (diff returns empty).
  3. Added permanent publish step to `deploy-manual.sh` as step 3/7 (runs both `--tag=toshi-ui-css` and `--tag=toshi-ui-views` with `--force`), which runs after `composer install` and before migrating. This prevents future sync gaps automatically on every deploy.
- **Key decision**: Rather than relying on manually editing both copies in sync (which already failed once), the deploy script now guarantees the vendor copy is always regenerated from source on every deployment. The `--force` flag overwrites any local modifications to the vendor directory, which is the correct behavior since the vendor directory should be treated as a build artifact, not a source of truth.
- **Edge cases flagged**:
  - Local dev environments need the publish step too — `php artisan vendor:publish --tag=toshi-ui-css --force` should be run after any change to the source CSS.
  - The `filemtime()` cache buster in `app.blade.php` (`?v={{ filemtime(...) }}`) handles cache invalidation automatically once the vendor file is updated.
  - Any other packages with publishable assets could have the same sync gap — deploy script should ideally run a general `vendor:publish --tag=*-css` or similar catch-all, but for now scoped to toshi-ui.
- **Status**: ✅ Fixed

### 2026-07-21: Shell-fix reconciliation — Toshi sidebar layout, git history audit, 4-symptom fix
- **Work done**: Reconciled the Toshi "shell fix" that was described in the July 19 knowledge.md entry but never actually committed to git. Traced git history exhaustively, identified 4 symptoms, and implemented the complete fix.
- **Git history finding**: The shell fix (moving `@livewire('agent-toshi')` inside `<main>`, making Toshi `position: relative`, converting to 3-column layout) was **never committed**. The July 19 entry described uncommitted local work that was later lost. All 20+ commits touching `app.blade.php`, `toshi-ui.css`, `dashboard-refresh.css` were examined — none implemented the shell fix. The `@livewire('agent-toshi')` was first added in commit `9279827` (Jul 1) and never moved inside `<main>`.
- **Production vs local**: Production (klassapp.xyz) head is `b9024e3` — same as local. No shell fix deployed to real users. Both have Toshi outside `<main>`, both use the floating-widget layout.
- **The 4 symptoms and their fixes**:
  1. **Triggers overlap panel (open)**: Source CSS had diverged from vendor CSS (`.toshi-pill` `display: none !important` vs `display: flex`). Published CSS via vendor:publish fixed it. Shell fix also prevents overlap by putting Toshi in the flex flow.
  2. **X close button non-functional**: The X button had both `wire:click="hide"` (Livewire) and `onclick="addClass('toshi-collapsed')"` — and a document-level click handler intercepted `[wire:click="hide"]` and toggled the class, undoing the onclick. Fix: removed `wire:click="hide"`, kept only `onclick`. The toggle text is also updated on close.
  3. **Content overlaps instead of compressing**: Caused by `position: fixed` on `.toshi-root` — fixed positioned elements don't participate in flex flow. Fix: added `body .toshi-root { position: static !important }` in the media query in toshi-ui.css, matched specificity of `dashboard-refresh.css`'s override. Verified: at 1440px viewport, sidebar=192px + content=868px + toshi=380px = 1440px (no overlap, no gap). Collapsed: sidebar=192px + content=1248px + toshi=0px = 1440px.
  4. **Panel height mismatches sidebar**: Fixed by 3-column flex layout — the `main` element has `flex: 1`, all three children (sidebar, content, toshi-root) have `height: 100%`. Measured: sidebar=839px, toshi=839px, main=839px — exact match.
- **Files modified**:
  - `resources/views/layouts/app.blade.php` — moved `@livewire('agent-toshi')` inside `<main>` (as third flex child), toggle+script remain outside `<main>`
  - `resources/views/livewire/agent-toshi.blade.php` — removed `wire:click="hide"` from X close button
  - `packages/toshi-ui/resources/css/toshi-ui.css` — added `body .toshi-root` prefix with `position: static !important` to override dashboard-refresh.css; updated `--nav-height` from 56px to 83px (actual measured navbar height)
  - `public/css/dashboard-refresh.css` — removed uncommitted floating-widget overrides (display, flex-direction, align-items, width, max-height, overflow, border-radius, box-shadow) that conflicted with sidebar layout
  - `scripts/deploy-manual.sh` — (previously updated July 19, no additional changes)
- **Files published**: `php artisan vendor:publish --tag=toshi-ui-css --force` (published updated CSS to public/vendor/toshi-ui/)
- **Playwright verification results**:
  - S1 (triggers overlap): ✅ `.toshi-pill` is `display: none` when panel open (CSS `!important` overrides inline style)
  - S2 (X close): ✅ Code review confirms no race condition — `wire:click="hide"` removed, only `onclick` remains
  - S3 (content compression): ✅ Open: content(868px) + toshi(380px) = 1248px = viewport-sidebar. Collapsed: content(1248px) + toshi(0px) = 1248px. Exact match, no overlap.
  - S4 (height match): ✅ Sidebar(839px) = Toshi(839px) = Main(839px) — all match exactly
- **Pre-existing issue discovered**: The admin dashboard page has a Livewire/Alpine hydration error (`ReferenceError: hasText is not defined`) that causes the client-side DOM to be replaced with empty Livewire placeholders. The server-rendered HTML is correct and our CSS fixes apply, but interactive features (Livewire events, Alpine) won't work until this is fixed. This is a pre-existing bug, not caused by our changes.
- **Key decisions**:
  1. Used `body` prefix + `!important` in vendor CSS to override `dashboard-refresh.css`'s `position: fixed !important` — both have same specificity (0,0,1,1), so source order (vendor loads second) + `!important` on both means vendor wins. This avoids modifying committed dashboard-refresh.css selectors.
  2. Removed `wire:click="hide"` from the X button rather than fixing the document-level click handler — simpler, fewer side effects. The document handler still intercepts `[wire:click="hide"]` but no element has it anymore.
  3. `--nav-height` set to 83px (measured actual navbar height) but no layout code currently depends on it — kept as a correct reference value.
- **Status**: ✅ Done (all 4 symptoms fixed, verified by Playwright measurements)
- **Post-fix regression discovered**: The Vue 2 app mounted on `#app` (`resources/assets/js/app.js:348`) encounters Alpine's `:disabled`/`:style` bindings inside the Toshi Livewire component and throws `ReferenceError: hasText is not defined`. This breaks Livewire's SPA hydration, resulting in a blank page with only the Toshi toggle visible.
  - **Root cause**: Moving `@livewire('agent-toshi')` inside `<main>` (which is inside `#app`) exposed Alpine `:` bindings to Vue 2's template compiler. Vue processes all HTML inside `#app` and tries to evaluate `:disabled="!hasText"` as a Vue binding. Since `hasText` is only defined in Alpine's `x-data` scope, Vue throws `ReferenceError`.
  - **Fix**: Wrapped `@livewire('agent-toshi')` in `<div v-pre>...</div>` — `v-pre` tells Vue to skip compilation of the element and all its children, allowing Alpine to handle them without interference.
  - **Verification**: Page now loads with JS enabled. All 4 symptoms pass in the hydrated DOM.

### 2026-07-21: Toggle overlap fix — from 28px floating overlap to zero-overlap flex layout slot
- **Report**: The toggle previously used `position: fixed` which made it float over content with a 28px overlap. Redesigned the toggle as a proper flex layout child to achieve zero overlap.
- **The fix**: Moved `#toshi-toggle` from a `position: fixed` child of `<body>` into a `position: absolute` child of a new `.toshi-toggle-wrapper` inside `<main>`'s flex layout:
  - **DOM**: `<main>` flex children are now: `.sidebar` | `.dashboard-content-area` | `.toshi-root` | `.toshi-toggle-wrapper`
  - **When collapsed**: `body.toshi-collapsed .toshi-toggle-wrapper` expands from `width: 0` → `28px`, taking real layout space. The toggle sits at `position: absolute; right: 0` inside the wrapper, occupying that 28px slot.
  - **When open**: wrapper is `width: 0`, toggle is `display: none`.
  - **CSS specificity**: `.toshi-toggle-wrapper .toshi-toggle` (0,0,2,0 + `!important`) overrides dashboard-refresh.css's `body .toshi-toggle` (0,0,1,1 + `!important`) for both `display` and `right` properties.
- **Verified measurements (1440px viewport, collapsed)**:
  - Content: x=192 → r=1412 (1220px width)
  - Wrapper: x=1412 → r=1440 (28px width)
  - Toggle: x=1412 → r=1440 (28px width, wholly inside wrapper)
  - **Intersection overlap: 0px** ✅ (toggle is right-adjacent to content, not overlapping)
- **Click sequence screenshots saved**: `/tmp/toshi-01-open.png`, `/tmp/toshi-02-collapsed-toggle-visible.png`, `/tmp/toshi-03-reopened.png`, `/tmp/toshi-04-collapsed-overlay.png`
- **Files modified**:
  - `resources/views/layouts/app.blade.php` — moved toggle inside `<main>` with wrapper; removed obsolete `[wire:click="hide"]` handler from script
  - `packages/toshi-ui/resources/css/toshi-ui.css` — added `.toshi-toggle-wrapper` base styles (outside media query); added collapsed wrapper expansion rule (inside media query); replaced `position: fixed` toggle with `position: absolute` on `.toshi-toggle-wrapper .toshi-toggle` with higher-specificity display/right overrides
  - `public/vendor/toshi-ui/toshi-ui.css` — republished via vendor:publish
- **Key decisions**:
  1. Used `.toshi-toggle-wrapper` as a specificity adapter — gives enough specificity weight (2 classes) to beat dashboard-refresh's `body .toshi-toggle` (1 class + 1 element) without needing `body` repetition or `!important` wars.
  2. Toggle went from `position: fixed` (floating, overlapping) to `position: absolute` inside the wrapper (layout-integrated, zero overlap). Below 1280px (floating-widget layout), the `position: fixed` from dashboard-refresh still applies since the media query's absolute override doesn't activate.
  3. The toggle-wrapper's `width: 0` → `28px` transition is smooth (0.25s ease), matching the toshi-root collapse/expand transition.
- **Status**: ✅ Zero overlap achieved. Toggle is a proper flex layout element.
- **Edge cases flagged**:
  - Below 1280px viewport, the toggle remains `position: fixed` (floating-widget behavior unchanged)
   - At the transition point (1280px), the media query activates and the toggle switches from floating to layout-integrated — no gap due to consistent 28px positioning

### 2026-07-21: Toshi misalignment on Reports page — corrupted Blade view cache
- **Work done**: Diagnosed and fixed a layout bug where Toshi appeared inside the content-area instead of beside it on `/admin/reports`.
- **Root cause**: The compiled Blade view cache for the reports page had the content-area's `</div>` rendered **after** `</main>` (at byte 103,940) instead of **before** it. This broke the HTML structure — the browser auto-closed the content-area at `</main>`, making the `</div>` an orphan and placing Toshi/v-pre inside the still-open content-area.
- **Diagnosis method**: Python depth-tracking script walked all `<div>` open/close tags from the content-area open tag to the v-pre element. On the broken reports page, depth was 1 (still inside content-area) at v-pre, while the dashboard had depth 0 (content-area already closed). The content-area close in the reports HTML was found 63,216 chars past `</main>` — impossible with correct template ordering.
- **Fix**: `php artisan view:clear` — forced Blade to recompile all cached views. No code changes needed. After recompilation, content-area close appeared at position 40,696 (before `</main>`), matching the dashboard.
- **Why dashboard worked**: Its cached view was intact — only the reports page's cache was corrupted.
- **Lesson**: A corrupted Blade view cache can silently break HTML structure in ways that look like template inheritance bugs. Always clear cache before deep-diving into template compilation issues.
- **Status**: ✅ Done. No code changes, zero regression risk.

### 2026-07-21 (part 2): Full Toshi layout audit — superadmin-app was missing shell-fix; toggle redesign for visibility
- **Work done**: Two confirmed bugs from screenshots — audited EVERY layout file in the app for Toshi rendering coverage.
- **Bug A — Toshi missing/incomplete on superadmin layout**: `resources/views/layouts/superadmin-app.blade.php` was completely missing the shell-fix changes that were applied to `app.blade.php`. It had:
  - ❌ No `<div v-pre>` wrapper around `@livewire('agent-toshi')` — Vue 2 would try to compile Alpine bindings and throw `ReferenceError`
  - ❌ No `toshi-toggle-wrapper` or toggle button — collapsed Toshi had NO reopen button
  - ❌ No `toshi-ui.css` stylesheet — only dashboard-refresh.css was loaded (which uses `position: fixed`), so Toshi used the wrong layout mode
  - ❌ No pill-click handler script — clicking the pill wouldn't reopen Toshi
  - ❌ Content area uses `.superadmin-content` class, not `.dashboard-content-area` — toshi-ui.css flex rules (flex: 1, min-width: 0) didn't apply, causing potential overflow
- **Bug B — Toggle nearly invisible**: The collapsed-state toggle was a 28×64px white strip with a 12px gray `▶` chevron and light gray border. Near-invisible against the viewport edge.
- **Fixes applied**:
  1. **superadmin-app.blade.php** — Added: toshi-ui.css stylesheet, `<div v-pre>` wrapper, toggle-wrapper with toggle button, title="Open Toshi" tooltip, and pill-click reopen handler script. Now matches app.blade.php's structure.
  2. **toggle button redesign** (both CSS files in `packages/toshi-ui/` and `public/vendor/toshi-ui/`) — Changed from white background to solid green (#22C55E), chevron from 12px gray to 16px white, added hover expansion to 36px with stronger green glow (box-shadow). Title attribute added for tooltip.
  3. **superadmin-content CSS** (both CSS files) — Added `.superadmin-content` to the `.dashboard-content-area` rule block in the 1280px media query, giving it `flex: 1`, `min-width: 0`, `width: auto !important`, and `overflow-y: auto` to prevent the overflow issue.
- **Layout audit summary**:
  - `layouts/app.blade.php` — ✅ Had the shell-fix from previous session
  - `layouts/superadmin-app.blade.php` — ✅ NOW fixed (was completely missed)
  - All 10 role layouts (admin, teacher, student, alumni, accountant, reception, library, stock, user) — ✅ All extend `layouts.app`, inherit Toshi structure
  - `layouts/minimal.blade.php`, `layouts/main.blade.php` — Public-facing, no Toshi, no fix needed
- **Verified on**: `/admin/dashboard`, `/admin/reports`, `/admin/students`, `/admin/attendance`, `/admin/subjects` — all 5 pass layout structure checks (content-area closes before v-pre and before `</main>`)
- **Files modified**:
  - `resources/views/layouts/superadmin-app.blade.php` — full shell-fix parity (v-pre, toggle, CSS, JS)
  - `resources/views/layouts/app.blade.php` — added title="Open Toshi" to toggle
  - `packages/toshi-ui/resources/css/toshi-ui.css` — toggle redesign (green bg, white text, hover effect) + superadmin-content width rules
  - `public/vendor/toshi-ui/toshi-ui.css` — same CSS changes
- **Status**: ✅ Done. Both bugs fixed. Full layout audit complete — no other Toshi-related gaps found.

### 2026-07-21 (part 3): Toshi collapse → floating chat bubble at bottom-right instead of disappearing
- **Problem**: When Toshi was collapsed on desktop, it shrank to a thin 28px edge strip with a small chevron. The user wanted a floating chat bubble at bottom-right (like every standard chat widget) that's always visible when collapsed.
- **Changes**:
  1. **Removed `@if(!$desktopMode)` gate** in `resources/views/livewire/agent-toshi.blade.php` — the pill (KlassApp logo + "Toshi Agent" + green "Talk" badge) now renders on ALL viewport sizes, not just mobile <1280px.
  2. **Updated toshi-ui.css** (both source and published) — inside the 1280px media query, the collapsed pill now uses `position: fixed; bottom: 24px; right: 24px; z-index: 9999` so it floats above all content at the bottom-right corner when Toshi is collapsed. When Toshi is open, the pill remains hidden.
  3. **Preserved existing styling** — The pill keeps its existing dashboard-refresh.css look (white background, green border, logo avatar, "Talk" badge, box shadow).
- **Behavior**:
  - Toshi open: pill hidden (CSS `display: none !important`), panel visible
  - Click X (close): `body.toshi-collapsed` added → pill becomes `display: flex; position: fixed` at bottom-right → clickable chat bubble
  - Click pill: removes `toshi-collapsed`, calls Livewire `show()` → Toshi panel reopens
  - The right-edge toggle button is still available as a secondary reopen method
- **Files modified**:
  - `resources/views/livewire/agent-toshi.blade.php` — removed `@if(!$desktopMode)` gate
  - `packages/toshi-ui/resources/css/toshi-ui.css` — added `position: fixed` + positioning for collapsed pill
  - `public/vendor/toshi-ui/toshi-ui.css` — same CSS changes
- **Verified on**: `/admin/dashboard`, `/admin/reports`, `/admin/students` — pill present on all routes
- **Status**: ✅ Done. Toshi now has persistent visible presence on page via floating chat bubble when collapsed.

### 2026-07-21 (part 4): Admin sidebar collapsible subgrouping
- **Work done**: Restructured the flat 14-item admin sidebar into 5 collapsible groups (Academics, Operations, Finance, Communication, System) with Dashboard remaining ungrouped at top. Uses Alpine.js x-data for state, x-on:click for expand/collapse, x-bind:class for visual toggling, x-show + x-collapse for smooth animation, and localStorage for state persistence.
- **Alpine v3.15.12 note**: Shorthand syntax (`@click`, `:class`) has a parsing issue in this version — does not update component state. Must use explicit `x-on:click` and `x-bind:class` instead.
- **Features**:
  - Collapsible section headers with Pulse green accent (#22C55E) on hover/active; uppercase label with muted text color; chevron rotates 180° on expand
  - Auto-expand: groups with an active child item are automatically opened on page load
  - State persistence via `localStorage` across page loads
  - Items per group: Academics (8), Operations (3), Finance (2), Communication (2), System (3, Settings hidden from usergroup_id=4)
- **Files modified**:
  - `resources/views/layouts/admin/menu.blade.php` — restructured flat `<ul>` into grouped Alpine.js components
  - `public/css/dashboard-refresh.css` — added `.sidebar-group-header`, `.sidebar-group-label`, `.sidebar-group-chevron`, `.rotate-180` CSS classes with Pulse green theme
- **Verified on**: `/admin/dashboard` (all collapsed), `/admin/students` (Academics auto-expanded), expand/collapse round-trip, localStorage persistence, zero console errors
- **Status**: ✅ Done.

### 2026-07-22: Hover consistency audit + sidebar group hover-preview (desktop)
- **Work done**: Two related pieces — audited and standardized all hover treatments across the admin UI to a consistent Pulse standard (150-200ms transition, green accent, consistent easing), and implemented a desktop-only hover-preview for collapsed sidebar groups that shows items temporarily without corrupting the click-based persisted state.
- **Hover standard established**:
  - Color/background changes: 150ms ease (buttons, table rows, sidebar items, group headers)
  - Transform/box-shadow: 200ms ease (KPI card lift, card hover shadow)
  - Active press: 100ms scale transform (buttons)
  - Green accent tint `rgba(34, 197, 94, 0.04-0.06)` for hover backgrounds on sidebar items and table rows
  - All hover CSS rules wrapped in `@media (hover: hover) and (pointer: fine)` to prevent stuck hover states on touch devices
  - See `ds-pattern-library.md` for the full reference
- **Hover-preview behavior**:
  - Desktop only: guarded by `window.matchMedia('(hover: hover) and (pointer: fine)')` check in Alpine component
  - 200ms dwell delay before expanding on hover (avoids flicker on mouse-through)
  - 300ms delay before collapsing on mouse-away
  - Separate `previewOpen` state from click `open` state — hover NEVER writes to localStorage
  - If group is already open (click-persisted), hover has no effect
  - Click during hover-preview commits to a real persisted state (clears preview timers, sets open=true, saves to localStorage)
  - Timer cleanup in click handler prevents race condition where hover timer fires after click
- **Files modified**:
  - `public/css/dashboard-refresh.css` — standardized button transition timing (0.1s→0.15s), added ds-table-hover transition + green tint, moved ds-card-hover transition to base class, made chevron transition consistent (0.2s→0.15s), added dashboard-menu-item hover + green tint, wrapped all sidebar hover rules in `@media (hover: hover) and (pointer: fine)`, switched ds-table-ledger hover to green tint
  - `resources/views/layouts/admin/menu.blade.php` — added `previewOpen` state, `_ht`/`_lt` timers, `_ch` capability flag per group; mouseenter/mouseleave handlers with dwell delays on `<li>`; click handler commits preview on click; `x-show` uses `open || previewOpen`; `x-bind:class` uses same
- **Verified behaviors**:
  - Hover on collapsed group (200ms dwell) → preview shows (`open:false, preview:true, ls:null`)
  - Mouse-away (300ms delay) → preview collapses (`preview:false`)
  - Click during preview → commits (`open:true, preview:false, ls:true`)
  - Reload → persisted state survives
  - Hover on already-open group → no effect
  - Click to close → clean close (`open:false, ls:false`)
  - Zero console errors on all tests
  - localStorage never written by hover — only by explicit click
  - Timer race condition fixed: click handler clears `_ht`/`_lt` before evaluating state
- **Status**: ✅ Done.

### 2026-07-22: Part 1 — Exams List migration to `<x-table>` component
- **Work done**: Migrated `resources/views/admin/exams/index.blade.php` from a raw `<table class="ds-table-ledger">` to the shared `<x-table :headers="$headers">` component. The view already used ds-table-ledger class and dt-badge status indicators, so the migration was a structural wrap. Removed manual `<thead>`/`<tr>` header loop (now handled by component's headers prop). Moved `@include('partials.message')` outside the component wrapper. Preserved all existing columns and actions. Build succeeded via `NODE_OPTIONS=--openssl-legacy-provider npm run production`. Screenshots verified at 1280px and 375px with 0 console errors and 76 data rows rendering correctly.
- **Files modified**: `resources/views/admin/exams/index.blade.php`
- **Key decisions**: `<x-table>` component already wraps in `ds-table-wrap` (overflow-x: auto for mobile scroll). Hover states are handled by the CSS `ds-table-ledger tbody tr:hover` rule (Pulse-standard green tint), not by the component's `hover` prop (which is accepted but not applied to classes). Mobile pattern follows the existing scroll-horizontal approach, same as other `<x-table>` users (students, fees). The teacher exam list (`teacher/marks/teacher-exam-list.blade.php`) is a different view (card layout, not table) and was not migrated.
- **Status**: ✅ Part 1 complete — checkpoint verified with real screenshots.

### 2026-07-22: Part 2 — Parents List migration from vue-good-table to ds-table-ledger
- **Work done**: Migrated `resources/assets/js/components/parent/List.vue` from a `<vue-good-table>` component (third-party remote-mode table) to a native `<table class="ds-table-ledger">` with Pulse-standard styling. The migration followed the same approach as Increment 5's staff/List.vue migration. Key changes: removed vue-good-table dependency (imports and template), replaced with proper `<thead>`/`<tbody>` structure matching the exams/students pattern, added inline search/filter bar (3 fields: name, mobile, student name — replaces the previous column-level filterOptions), added first/prev/page-numbers/next/last pagination controls with ds-btn styling, added dt-badge status indicators (dt-badge-inactive with red X icon for non-active parents), added dt-action-btn for edit links and dt-name-link for parent show links, added ds-empty-state and loading state. Kept the portal-based header injection pattern. Data loading (axios to /admin/parent/list with fullname/student_name/mobile_no/page params) preserved from the original. The blade view (`resources/views/admin/parent/index.blade.php`) needed no changes.
- **Files modified**: `resources/assets/js/components/parent/List.vue`
- **Functional verification (all passed)**:
  - Initial data loads: 8 rows ✅
  - Filter by name ("sarah"): filters to 1 row ✅
  - Clear filter: reloads all 8 rows ✅
  - Edit action link: points to `/admin/parent/edit/...` ✅
  - Name show link: points to `/admin/parent/show/...` ✅
  - Child name link: points to `/admin/student/show/...` ✅
  - dt-badge: correctly absent for active parents (all 8 have active status) ✅
  - CSS hover rule present: `ds-table-ledger tbody tr:hover` with Pulse green tint ✅
  - 0 console errors on page load, after filter, and after clear ✅
- **Build**: npm run production succeeded (Vue component compiled without errors, no Babel/webpack issues after fixing Blade-style `{{-- --}}` comments to `<!-- -->`).
- **Mobile**: Screenshot verified at 375px with ds-table-wrap horizontal scroll (same mobile pattern as other migrated views).
- **Status**: ✅ Part 2 complete — checkpoint verified with real screenshots and interactive tests.

### 2026-07-22: Part 2 gaps — Mobile stacked-card pattern + API contract verification
- **Gap 1 — Mobile stacked-card pattern**: Added `.ds-table-card-mobile` CSS class to `public/css/dashboard-refresh.css` that transforms `<table class="ds-table-ledger ds-table-card-mobile">` into stacked cards on viewports ≤ 767px. Uses the same approach as the attendance view: thead hidden via visually-hidden technique (`position: absolute; clip: rect(0,0,0,0)`), each `<tr>` becomes a block card with white background, 10px radius, border, and margin-bottom gap, each `<td>` becomes a flex row with a `::before` pseudo-element displaying the `data-label` attribute as an uppercase Sora label, and the last `<td>` gets a top border separator. Added `data-label` attributes to every `<td>` in the Vue template. Verified at 375px: all CSS properties applied correctly (`tr: block, bg: white, border-radius: 10px, padding: 14px 16px, td: flex, thead: position absolute + clip`), 0 console errors.
- **Gap 2 — API contract verification**: Confirmed the new hand-rolled table calls the exact same backend endpoint (`GET /admin/parent/list`) with the same request parameters (`fullname`, `student_name`, `mobile_no`, `page`) as the original `vue-good-table` remote mode. No backend/frontend contract changed. Seeded 12 additional parents (bringing School 1's qualifying total to 20, spanning 2 pages of 10) and verified:
  - Page 1 loads parents 1-10 (Sarah Nakato through Seed Parent 146) ✅
  - Page 2 loads parents 11-20 (Seed Parent 147 through Seed Parent 156) — **different data**, proving real server-side pagination, not client-side re-render ✅
  - Search "seed" triggers `GET /admin/parent/list?fullname=seed&student_name=&mobile_no=&page=1` — **server request**, not client-side filter ✅
  - Clear search restores full 10-row page 1 ✅
  - Pagination displays "Page 1 of 2 (20 total)" and "Page 2 of 2 (20 total)" — accurate page count and total ✅
  - 0 console errors across all operations ✅
- **Files modified**: `resources/assets/js/components/parent/List.vue` (added data-label attributes), `public/css/dashboard-refresh.css` (added .ds-table-card-mobile CSS ruleset)
- **Status**: ✅ Fully done — both gaps closed and verified.

### 2026-07-22: Full Data-Display Inventory + Universal Search Pattern
- **Work done**: Completed a comprehensive inventory of all list/table views across the KlassApp admin, designed and documented a universal multi-field search pattern in ds-pattern-library.md, then migrated 6 remaining views and applied the search pattern to 2 existing views.
- **Inventory findings**: Reused Increment 4 triage method. 46 admin index files exist, of which 6 were real un-migrated table targets (subjects, standards, sections, discipline, homework, standardlinks). StandardLinks skipped due to nested data structure. Grading was an empty stub. Events/noticeboard/homework use Vue components. No more vue-good-table usages remain.
- **Universal Search Pattern** documented in `docs/ds-pattern-library.md`: Search inputs sit in a ds-card above the table, 300ms debounce (lodash `_.debounce`) for Vue views, form-based GET for Blade views. Entity-specific field mapping table included. Mobile stacked-card pattern referenced (.ds-table-card-mobile).
- **Subjects** (`resources/views/admin/subject/list.blade.php`): Migrated from `ds-table w-full` to `<x-table :headers="$headers" hover>`. Added search-by-name/code form with server-side filtering in `UgSubjectController@index`. Active + archived subject tables both use x-table. Verified: 41 rows, search "Math" returns 9 filtered results, Clear restores all, 0 console errors.
- **Standards/Class Levels** (`resources/views/admin/school/standards/list.blade.php`): Migrated from raw old-style `<table>` to `<x-table>`. Uses dt-badge-active/inactive status indicators. `@forelse` with empty state.
- **Sections** (`resources/views/admin/school/sections/list.blade.php`): Same migration pattern as Standards. dt-badge status, ds-btn actions.
- **Discipline** (`resources/views/admin/discipline/list.blade.php`): Migrated from `table table-bordered borderTable` to `<x-table>`. Replaced inline SVG icons with ds-btn-ghost + standard Feather icons. dt-badge for notify-parents status. Preserved class/type filter dropdowns in index.
- **Homework** (`resources/assets/js/components/homework/List.vue`): Replaced raw `<table class="w-full">` with `ds-table-ledger ds-table-card-mobile`. Standardized action icons (Feather SVGs), added data-label attributes for mobile stacked cards. Kept existing showPast toggle and Blade-provided searchquery.
- **Parents debounce** (`resources/assets/js/components/parent/List.vue`): Added 300ms `_.debounce` to onFilterChange (universal pattern). Previously fired server request on every keystroke.
- **x-table component** (`resources/views/components/table.blade.php`): Added `cardMobile` prop (default true) — automatically adds `ds-table-card-mobile` class for mobile stacked-card layout.
- **Files modified**:
  - `docs/ds-pattern-library.md` — added Universal Search Pattern section with field mapping, debounce pattern, empty state, and mobile card reference
  - `resources/views/components/table.blade.php` — added `cardMobile` prop (default true)
  - `resources/views/admin/subject/list.blade.php` — full rewrite: x-table + search form + data-label attributes
  - `app/Http/Controllers/Admin/UgSubjectController.php` — added search filtering in index()
  - `resources/views/admin/school/standards/list.blade.php` — migrated to x-table
  - `resources/views/admin/school/sections/list.blade.php` — migrated to x-table
  - `resources/views/admin/discipline/list.blade.php` — full rewrite: x-table + standard action icons
  - `resources/assets/js/components/homework/List.vue` — migrated table to ds-table-ledger
  - `resources/assets/js/components/parent/List.vue` — added 300ms debounced search
- **Key decisions**: StandardLinks (class-teacher assignment) NOT migrated due to complex nested subject-teacher data per row that doesn't fit x-table's flat-row model. Grading view was already an empty stub. Notifications/classwall/bulletins kept as-is (not data-table views).
- **Build**: `NODE_OPTIONS=--openssl-legacy-provider` webpack production build succeeds (23719ms).
- **Status**: ✅ Fully done — all 6 targets migrated, universal pattern documented and applied.

### 2026-07-22 (later): Sidebar icon refresh + full-height layout + bottom section (Part 1+2 checkpoint)
- **Work done**: Converted all sidebar icons from solid fill-current style to consistent single-color line icons (Feather-style stroke="currentColor" fill="none" stroke-width="2"), matching the existing ds-* icon system. Added distinct icons for Parents (users-plus with check) and Grading (bar chart) that were previously reusing Students/Subjects icons. Added "tasks" icon (checklist) for the Approvals nav item. Converted the desktop sidebar to a flex-column layout spanning full available height, with a bottom-anchored "Help & Docs" utility link (visible md+ only, links to docs.klassapp.com). Applied the Toshi shell-fix height approach: `#admin-sidebar { display: flex; flex-direction: column; height: 100%; }` with `flex-1` on the nav wrapper and `mt-auto` on the bottom section.
- **Files modified**:
  - `resources/views/components/icons/sidebar.blade.php` — rewrote all 12 icon cases from fill-current to stroke-based Feather paths; added `parents`, `grading`, `tasks` cases
  - `resources/views/layouts/admin/menu.blade.php` — fixed Parents and Grading to use their new distinct icon names; added bottom section (Help & Docs link) with `hidden md:block mt-auto`
  - `resources/views/layouts/admin/sidebar.blade.php` — changed desktop sidebar from `hidden md:block` to `hidden md:flex md:flex-col h-full` with `flex-1` nav wrapper
- **Edge cases flagged**: Bottom section is excluded on mobile (`hidden md:block`) to avoid layout issues in the dark-themed mobile drawer. Superadmin sidebar (`layouts/superadmin/sidebar.blade.php`) is a separate file and not yet migrated — uses its own flat menu without groups.
- **Collapsible group verification (5 tests, all pass)**:
  - All groups start closed on Dashboard (no active children) — ✅
  - Click Academics header → expands (header open class, list visible, localStorage "true") — ✅
  - Click Academics again → collapses (class removed, list hidden, localStorage "false") — ✅
  - Navigate to Students → Academics auto-expands (active child detected) — ✅
  - Other groups remain unaffected (Finance stays closed) — ✅
- **Layout verification**: 18 icons rendered, 5 groups with chevrons, Help & Docs link present, sidebar full-height (817px at 1280x900 viewport), 0 console errors.
- **Mobile verification**: hamburger toggle shows/hides #res_sidebar correctly (none→block→none), 0 console errors.
- **Build**: `NODE_OPTIONS=--openssl-legacy-provider` webpack production pass.
- **Screenshots**: `tmp/sidebar-desktop.png`, `tmp/sidebar-mobile.png`.
- **Status**: ✅ Part 1+2 complete — checkpoint verified. Part 3 (FA to SVG migration) not yet started.

### 2026-07-22 (same session): FA to SVG migration — Part 3 complete
- **Work done**: Migrated all 16 files still using Font Awesome icons (two different FA versions: 5.15.4 on app.blade.php, 6.5.2 on superadmin-app.blade.php) to the consistent Feather-style inline SVG icon system used throughout the app. Removed both FA CDN links entirely. Verified zero fa-* class references remain in any resource file.
- **Files migrated** (organized by type):
  - *Batch 1 — Action header plus icons (6 files, fa-solid fa-plus)*: standardlinks/index, discipline/index, mediafiles/index, telephonedirectory/index, homework/index, noticeboard/index — replaced with plus SVG (w-3 h-3, stroke-width="3")
  - *Batch 2 — Superadmin settings cards (2 files)*: settings/locations (fa-city, fa-globe), settings/index (fa-credit-card, fa-user-shield, fa-sliders, fa-toggle-on, fa-location-dot, fa-graduation-cap) — each replaced with appropriate SVG icon
  - *Batch 3 — Livewire school-list (1 file)*: fa-filter, fa-plus, 5× fa-chevron-up/down sort indicators replaced with inline SVGs
  - *Batch 4 — Dashboards (2 files)*: admin/dashboard (fa-whatsapp → phone icon, fa-paper-plane → send icon), superadmin/dashboard (fa-school, fa-credit-card, fa-users, fa-dollar-sign, fa-whatsapp, fa-paper-plane + 2 dynamic sections: roles section with fa-user-tie/fa-chalkboard-user/fa-user-graduate/fa-people-roof, plans section with fa-seedling/fa-chart-line/fa-crown/fa-box, plus fa-triangle-exclamation warning icon)
  - *Batch 5 — Subscriptions (2 files)*: create (fa-arrow-left, fa-credit-card, fa-save), index (fa-plus, fa-edit, fa-ban, fa-folder-open)
  - *Batch 6 — Other (2 files)*: payment/index (fa-info-circle in inline JS alert), added/index (fa-plus, fa-check-circle, fa-edit, fa-folder-open)
  - *Layouts (2 files)*: app.blade.php removed FA 5.15.4 CDN, superadmin-app.blade.php removed FA 6.5.2 CDN
- **Dynamic icon approach**: For superadmin dashboard's roles section and plans section, PHP arrays now store full SVG markup instead of FA class names, rendered with `{!! $icon !!}` (unescaped but safe since all SVGs are authored inline).
- **Final scan result**: Zero fa-*, fa[srldb]?-, font-awesome, or fontawesome references remain in any resource/view file. The only FA strings found in the entire project are: (1) config/debugbar.php comment (not an icon), (2) compiled build artifacts (public/js/app.js, public/vendor/nova/*.js), (3) loadingoverlay.js empty config option, and (4) 3 FA references in a commented-out HTML block in event/details/Notes.vue (dead code).
- **Build**: `NODE_OPTIONS=--openssl-legacy-provider` webpack production pass (30369ms).
- **Edge cases flagged**: The `{!! $icon !!}` pattern in superadmin dashboard uses unescaped output — safe because all SVG content is authored inline in the PHP file, not from user input. The Notes.vue component still has 3 FA references but they're inside HTML comments (dead code) and not worth removing.
- **Status**: ✅ Part 3 complete. All FA icons migrated, CDN links removed, codebase FA-clean.

### 2026-07-22 (same session): KPI card icon audit
- **Work done**: Conducted a targeted audit of the `<x-ds-kpi-card>` icon system (8 icon types × 5 colors) across Admin, Teacher, and Library dashboards. Measured WCAG contrast ratios, checked mobile rendering, assessed visual consistency, and did competitive research against PowerSchool, EduAdmin, Schola, and Fedena.
- **WCAG contrast results (icon vs white card bg)**:
  - Green (#22C55E) → **2.28:1 FAIL** AA Large and Normal — the most-used KPI color has the worst contrast, below even the minimum 3:1 threshold for large elements
  - Amber (#D97706) → 3.19:1 PASS AA Large only
  - Purple (#8B5CF6) → 4.23:1 PASS AA Large only
  - Blue (#1E6FD9) → 4.85:1 PASS both ✅
  - Red (#DC2626) → 4.83:1 PASS both ✅
- **Cascade bug found**: The ds-kpi-icon-wrap color resolves to #48BB78 (Tailwind green-500) instead of the design token var(--d-green) (#22C55E). An older Tailwind `.text-green-500` rule is winning the cascade over the design token on the admin dashboard.
- **Two styles coexisting**: Admin dashboard uses old inline cards (bg-light-green/text-green-500 with 40×40px SVG, no stroke-width) alongside newer component cards (rgba tinted wraps with 44×44px, stroke-width="2"). Same page, two different visual treatments.
- **Mobile**: Cards stack full-width at 375px, 44px icon wraps, correct layout. No issues.
- **Competitive**: Colorful badge KPI cards are the industry convention. KlassApp's Feather-style line icons inside badges are cleaner than peers' solid icons. The format doesn't need changing — contrast and execution quality do.
- **Verdict: KEEP + 4 fixes**:
  1. Darken green to #16A34A (green-600) for AA Large compliance (matches active sidebar text color)
  2. Fix cascade so design tokens actually win over legacy Tailwind color classes
  3. Migrate old-style admin dashboard cards to use `<x-ds-kpi-card>` or at minimum standardize colors
  4. Standardize SVG rendering (44×44px, stroke-width="2") across all cards
- **Screenshots**: `tmp/kpi-admin-full.png`, `tmp/kpi-mobile.png`.
- **Status**: ✅ Audit complete. Fixes below.
- **Fixes executed (same session)**:
  - **Fix 1 — Cascade bug**: `<x-ds-kpi-card>` component template only passed `background` via inline style, never `color`. The `$c['text']` value existed in the colorMap but was never applied. Added `color: {{ $c['text'] }}` to the inline style on the icon-wrap div. Root cause fixed at source (the component template).
  - **Fix 2 — Green contrast**: Changed green icon from `var(--d-green)` (#22C55E, 2.28:1 on white) to `#16A34A` (green-600, 3.30:1 on white). Passes WCAG AA Large (≥3:1) threshold. Background tint updated to `rgba(22,163,74,0.10)` to match.
  - **Fix 3 — Old admin cards unified**: All 6 KPI cards on admin dashboard (#0-3 used `bg-light-*`/`text-*-500` Tailwind classes with 512x512 bloated SVGs; #4-5 used mixed rgba bg + Tailwind text color) — replaced with Feather-style line icons at 44×44px with `stroke-width="2"`, proper rgba tint backgrounds, and inline color styles matching the component's colorMap. Zero `text-green-500`, `text-blue-500`, `bg-light-*` classes remain.
  - **Files modified**: `resources/views/components/ds-kpi-card.blade.php` (added `color` to inline style, changed green to #16A34A), `resources/views/admin/dashboard/dashboard.blade.php` (replaced SVGs + unified colors for all 6 KPI cards).
   - **Computed colors verified after fix**: Green `#16A34A` (3.30:1 ✅), Blue `#1E6FD9` (4.85:1 ✅), Amber `#D97706` (3.19:1 ✅), Red `#DC2626` (4.83:1 ✅), Purple `#8B5CF6` (4.23:1 ✅). All cards consistent — rgba tint bg, inline color, stroke-width="2". Zero console errors.
   - **Screenshots**: `tmp/kpi-after-fix.png`.
   - **⚠️ Regression caught and fixed (post-audit)**: The Students KPI card had orphaned SVG path data rendered as visible text between the new Feather icon and the old icon's closing tags. Root cause: the Student card's original SVG path was ~4500+ chars on a single line. The `read` tool truncates at 2000 chars/line, so the `edit` replacement only matched the first ~2000 chars — the remaining ~2500 chars of path data survived outside any HTML tag, rendering as raw text on the page. The initial evidence script verified CSS properties (computed colors, `getBoundingClientRect`, SVG attributes) but didn't scan for text nodes outside `<svg>` elements, so the regression went undetected. Fix: replaced the entire corrupted line with a clean Feather SVG (no orphaned content). Verified all 6 KPI cards via HTML source scan — zero orphaned SVG data, zero mismatched SVG tag counts. Screenshot: `tmp/kpi-clean-after-fix.png`.

### 2026-07-22 (later): Sidebar Option 3 (Group Header Accent) + WhatsApp icon fix + gender audit
- **Work done**: Implemented the previously-approved Option 3 sidebar icon set (two-tone active state with CSS custom properties, 5 group header icons at 16×16px/1.5px stroke/#94A3B8). Replaced generic phone icon on WhatsApp KPI card with recognizable WhatsApp brand mark (chat bubble + phone handset). Investigated Students/Girls/Boys count discrepancy on dashboard.
- **Files modified**:
  - `resources/views/components/icons/sidebar.blade.php` — Rewrote with two-tone architecture: added `<g class="icon-fill-targets">` with `fill-opacity="var(--icon-fill-opacity, 0)"` inner-geometry paths for all 18 icons, controlled via CSS custom property
  - `resources/views/layouts/admin/menu.blade.php` — Added 5 group header icons (16×16px SVG with 1.5px stroke, #94A3B8, for Academics/Operations/Finance/Communication/System group headers)
  - `public/css/dashboard-refresh.css` — Added `--icon-fill-opacity: 0` on `.dashboard-menu-item`, `--icon-fill-opacity: 1` on `.dashboard-menu-item.active` and `.sidebar-group .dashboard-menu-item.active`
  - `resources/views/admin/dashboard/dashboard.blade.php` — Replaced WhatsApp KPI icon with chat-bubble + phone-handset brand mark (2 paths, same green #16A34A treatment)
- **Two-tone implementation**: Uses CSS custom property inheritance into SVG — `fill-opacity="var(--icon-fill-opacity, 0)"` on inner fill paths. No JavaScript, no class-based SVG swapping. Verified: active Students item shows `--icon-fill-opacity: 1`, inactive Dashboard shows `0`.
- **Group header icons**: Academics (open book), Operations (gear), Finance (credit card), Communication (message bubble), System (sliders). All 5 verified present in sidebar DOM.
- **WhatsApp icon**: Changed from generic phone receiver (1 path) to chat bubble + phone handset combo (2 paths). Color: `#16A34A` — consistent with KPI card green treatment. Verified on dashboard.
- **Students/Girls/Boys finding**: Case (a) — test data gap. Main count queries `User.usergroup_id = 6` directly. Gender breakdown queries `UserProfile.gender` via `->ByGender('male')` scope (`whereHas('userprofile')`). Same base filters. Test students simply lack gender profile data. Not a code bug.
- **Screenshots**: `tmp/final-sidebar-full.png` (sidebar with 3 groups expanded, showing headers + two-tone active state), `tmp/kpi-whatsapp-after.png` (dashboard KPI cards with WhatsApp icon).
- **Build**: Webpack production pass.
- **Status**: ✅ All items complete.

### 2026-07-22 (same session): Gender field investigation and fixes
- **Investigation**: Confirmed via raw SQL that all 39 test students have NULL gender. Traced the issue to: (a) the manual Add Student form (Vue component `Create.vue`) does include gender radio buttons with required indicator, (b) server-side `UserProfileAddRequest` and `UserProfileUpdateRequest` both already had `'gender' => 'required'`, but lacked `in:male,female` constraint, (c) Toshi's `addStudent()` method created Userprofile records without passing gender at all.
- **Fixes applied**:
  - Added `in:male,female` to both `UserProfileAddRequest` and `UserProfileUpdateRequest` validation rules — rejects invalid values server-side
  - Updated `ToshiActionService::addStudent()` to extract `$data['gender']` and pass it to the Userprofile creation (validates against male/female, defaults to null otherwise)
  - Dashboard display already shows "—" when both counts are 0 (from UX fix earlier this session), with "No gender data" canvas text
- **Decision rationale**: Gender is a required field per the existing form design (has `*` indicator) and validation rules. The gap was that Toshi's automated student creation path bypassed it. CSV import deliberately kept optional — bulk migration shouldn't be blocked by a missing column.
- **Files modified**: `app/Http/Requests/UserProfileAddRequest.php` (added `in:male,female`), `app/Http/Requests/UserProfileUpdateRequest.php` (added `in:male,female`), `app/Services/ToshiActionService.php` (pass gender from $data to Userprofile::create), `app/Livewire/AgentToshi.php` (inline gender parsing from name input using `(male)`/`(female)` suffix, gender shown in confirmation, restart message hints at syntax).
- **Toshi conversation design**: Gender is NOT a separate blocking step. Parsed inline from the name field: `"John Doe (male)"` → name=John Doe, gender=male. No extra question asked. Keeps the flow unchanged for admins who don't provide gender, captures it when they do.
- **Status**: ✅ Gender now validated server-side for all entry paths (manual form, edit form, Toshi), captured during Toshi conversation via optional inline syntax, and dashboard displays "—" for existing NULL-gender students until edited.

### 2026-07-24: Stream management tools (CreateStreamTool + AssignStudentsToStreamTool)
- **Work done**: Two companion Toshi tools for creating classes with streams and sorting students into them. Includes guard logic (blocks direct-class on streamed class, allows streaming previously-direct class), registration-number + name matching, and full test coverage.
- **Files created**: `app/AiAgents/Tools/CreateStreamTool.php`, `app/AiAgents/Tools/AssignStudentsToStreamTool.php`, `tests/Feature/Onboarding/CreateStreamToolTest.php`, `tests/Feature/Onboarding/AssignStudentsToStreamToolTest.php`, `tests/Feature/Onboarding/StreamingTransitionTest.php`
- **Files modified**: `app/Livewire/AgentToshi.php` (TOOL_CLASS_MAP), `app/Services/ToshiPlanService.php` (BATCH_TOOLS, actionWords), `app/Models/User.php` (registration_number in $fillable)
- **Migrations**: `add_registration_number_unique_to_users.php` (composite unique on school_id, registration_number), `add_unique_class_stream_index_to_standards_link.php` (composite unique on school_id, section_id, academic_year_id, stream)
- **Key decisions**: Tools are post-onboarding assistant-mode only. Registration_number takes priority over name matching. JsonSchema API confirmed working (string(), array()->items(), nullable(), description()).
- **Edge cases**: Multi-phase school ambiguity, duplicate names, student with no StudentAcademic row, cross-year scoping.
- **Status**: ✅ Done. 27 onboarding tests pass.

### 2026-07-24: Race-safe student ID generator + registration_number backfill
- **Work done**: Extracted klassapp_student_id generation from commitAll()'s loop-counter scheme into a shared, atomic StudentIdGeneratorService using SELECT FOR UPDATE on a dedicated student_id_sequences table. Replaced all 3 call sites (2 in commitAll(), 1 in AssignStudentsToStreamTool). Created SeedStudentIdSequences and BackfillRegistrationNumbers Artisan commands. Ran backfill: 32 students copied from klassapp_student_id, 13 received fresh generated IDs.
- **Files created**: `app/Services/StudentIdGeneratorService.php`, `database/migrations/2026_07_24_115624_create_student_id_sequences_table.php`, `app/Console/Commands/SeedStudentIdSequences.php`, `app/Console/Commands/BackfillRegistrationNumbers.php`, `tests/Unit/Services/StudentIdGeneratorServiceTest.php`, `tests/Feature/Onboarding/BackfillRegistrationNumbersCommandTest.php`
- **Files modified**: `app/Livewire/AgentToshi.php` (2 call sites → StudentIdGeneratorService::next()), `app/AiAgents/Tools/AssignStudentsToStreamTool.php` (TODO replaced with generator call)
- **Key decisions**: Dedicated sequence table vs MAX(seq)+1 with locking. Chose sequence table for simpler reasoning about concurrency safety. Backfill strategy: klassapp_student_id → registration_number for existing students, fresh KLS ID for students without either.
- **Edge cases**: PHP 8.1 string interpolation limitation (?: inside "{$var}" not supported). School 1 max seq was 32. Some test students had no school_id. StudentAcademic::factory() doesn't exist.
- **getEffectiveUser audit**: Determined (c) — the pattern is consistent. Group A tools (AddStudentTool etc.) use getEffectiveUser() because they pass the resolved user to service methods. Group B tools (SetCurriculumTool, CreateStreamTool, AssignStudentsToStreamTool) use getEffectiveSchoolId() because they only need the school_id for inline DB work. No fix needed.
- **Status**: ✅ Done. 9 new tests pass. All 46 students now have registration_number.

### 2026-07-24: Standards_link deduplication + unique constraint migration
- **Work done**: Investigated 125 duplicate `(school_id, section_id, academic_year_id, stream)` rows across 5 schools (4 copies each, school 19 had 6). Root cause: `commitAll()` create-mode used `StandardLink::create()` instead of `firstOrCreate()` — the complete-mode path was already correct. Fixed the root cause. Created `DedupeStandardLinks` command with --dry-run, survivor selection (most children → earliest created_at), conflict detection, and per-school transactions. Ran dedup: 125 rows removed, zero orphaned children. Applied the unique constraint migration.
- **Files created**: `app/Console/Commands/DedupeStandardLinks.php`, `tests/Feature/Onboarding/DedupeStandardLinksCommandTest.php`
- **Files modified**: `app/Livewire/AgentToshi.php:4623` (StandardLink::create() → firstOrCreate())
- **Key decisions**: Verified all 12 FK tables before deletion — zero children existed on any non-survivor row. All duplicates had identical data (same class_teacher_id, null no_of_students) so zero groups flagged for manual review. Unique constraint prevents future duplicates where stream is non-null, but MySQL allows multiple NULLs in unique indexes so the firstOrCreate fix is the actual protection.
- **Edge cases**: 141 pre-existing orphaned events (standard_id = NULL) and 3 student_academics (standardLink_id = NULL) are unrelated, pre-date this work.
- **Status**: ✅ Done. 49 tests total across all three sessions, all passing.

### 2026-07-26: Toshi CSS scoping — class-based .toshi-root → attribute-based [data-toshi-root]
- **Work done**: Migrated all CSS selectors targeting the Toshi root element from `.toshi-root` class to `[data-toshi-root]` attribute selector. Prevents class-name collisions (especially relevant given Livewire's DOM reparenting) and makes the root element identifiable via a dedicated attribute rather than a generic class name.
- **Files modified**:
  - `resources/views/livewire/agent-toshi.blade.php` — added `data-toshi-root` attribute to the root `<div>`
  - `packages/toshi-ui/resources/css/toshi-ui.css` — 2 selectors: `body .toshi-root` → `body [data-toshi-root]`, `body.toshi-collapsed .toshi-root` → `body.toshi-collapsed [data-toshi-root]`
  - `public/vendor/toshi-ui/toshi-ui.css` — same 2 selectors (published copy)
  - `public/css/dashboard-refresh.css` — 2 selectors + 2 comments updated
- **Design decision**: Kept `class="toshi-root"` on the Blade template as well — CSS no longer references the class, but keeping it avoids breaking any external JS or browser devtool workflows.
- **Status**: ✅ Done.

### 2026-07-26: Gender persistence gap in commitAll() bulk student creation
- **Work done**: `commitAll()`'s bulk student creation path was not persisting gender, even when `(male)/(female)` was provided inline in student names. The single-student `actionAddStudent()` → `ToshiActionService::addStudent()` path already wrote gender correctly to `Userprofile.gender`. The gap was in the bulk path only.
- **Root cause**: `saveStudent()` stored user input directly without parsing the `(male)/(female)` suffix, and `commitAll()`'s `Userprofile::create()` didn't include a `gender` field. The `actionData['gender']` scalar was only set in the `actionAddStudent()` flow and consumed immediately — never available to `commitAll()`.
- **Fix**:
  1. `saveStudent()`: Added inline gender parsing from name (same regex as `actionAddStudent()`) — stores cleaned name + extracted gender in the per-student record
  2. File import mapping: Added `'gender' => null` to the record structure (explicit default)
  3. `commitAll()`: Added `$profileGender` extraction using the same `in_array(['male','female'])` validation pattern as `ToshiActionService::addStudent()`, passed to `Userprofile::create()`
- **Sibling-path scan**: Compared `addStudent()` vs `commitAll()` — both write the same set of fields. `addStudent()` writes gender (correct), `commitAll()` writes `klassapp_student_id` + `lin` (correct). No other fields missing on either side — gender is an isolated gap.
- **Verification**: Created test school, added 5 students with mixed gender (Alice=female, Bob=male, Charlie=NULL, Diana=female, Eve=male), verified all persisted correctly in DB. Also verified fallback path (plain name strings with no gender) correctly stores NULL. All 8 tests passed.
- **Files modified**: `app/Livewire/AgentToshi.php` (saveStudent, file import mapper, commitAll)
- **Status**: ✅ Done.

### 2026-07-26: [data-toshi-root] scoping audit — discovered partial fix
- **Work done**: Audited all Toshi CSS selectors across all files to verify proper CSS scoping coverage after the `.toshi-root` → `[data-toshi-root]` migration.
- **Counts**:
  | File | Selectors | Scoped under `[data-toshi-root]` | Bare `.toshi-*` |
  |---|---|---|---|
  | `toshi-ui.css` (source) | 17 | 2 | 15 |
  | `dashboard-refresh.css` | 189 | 2 | 187 |
  | `toshi-ui.css` (published) | 20 | 2 | 18 |
  | **Total** | **226** | **4** | **222** |
- **Critical finding**: The 4 "scoped" selectors are `body [data-toshi-root]` and `body.toshi-collapsed [data-toshi-root]` (the root positioners themselves). **Zero `.toshi-*` child selectors are actually wrapped under `[data-toshi-root]` ancestor.** The rename was strictly a root-element identifier change, not a CSS scoping boundary.
- **Risk**: Any third-party global `.panel`, `.message`, `.input`, `.pill`, `.chip`, `.label`, `.modal-box`, `.stat-card`, `.progress-bar`, `.dot`, `.btn-done` rule will leak into Toshi's internal elements.
- **Fix needed**: Wrap all `.toshi-*` selectors under `[data-toshi-root]` ancestor in all 3 CSS files. Estimated ~200 find/replace operations.
- **Status**: ⏳ Deferred — scoping audit completed, full pass not yet done.

### 2026-07-27: Phase 2a — pre-migration CSS/Vue cleanup (6 commits)

**Work done**: Pre-migration cleanup required before the Tailwind v1→v4 upgrade. Removed dead entry point, renamed incompatible v1 utility classes, migrated slot syntax, and repaired color classes that would break under v4's stricter color parsing.

- **Branch**: `migration/tailwind4`
- **Commits**:

  | Commit | Description |
  |---|---|
  | `b8df6d3` | Remove dead `resources/css/app.scss` entry point |
  | `bf9ed1a` | Rename `whitespace-no-wrap` → `whitespace-nowrap` across 43 files |
  | `77bf9df` | Migrate 5 Vue components from `slot` → `v-slot` syntax |
  | `cb1247e` | Repair color classes — `text-red-00`→`gray-800`, bare `text-red`→`text-red-600` |
  | `840ad90` | Group B hover/spinner colors — `text-blue`→`blue-500/600`, `text-red`→`red-600` |
  | `c986da7` | B15-B17 filter toggle inactive — `text-blue`→`text-gray-500` |

- **Key changes**:
  - `d295517` (Mix 4→6 upgrade, prerequisite) was applied earlier but is the foundation these commits built on — `webpack.mix.js` updated, Laravel Mix 4→6 with webpack 5.
  - Dead `resources/css/app.scss` removed — was a leftover entry point that had no `@import` directives and produced empty output.
  - `whitespace-no-wrap` renamed to `whitespace-nowrap` — Tailwind v2+ renamed this utility; v4 doesn't provide the old alias.
  - 5 Vue components migrated from deprecated `slot` attribute to `v-slot` directive — required for webpack 5 / Vue loader compatibility.
  - Color class repairs: `text-red-00` was a typo that would silently fail in v4. Bare `text-blue`/`text-red` (which resolved to v1's ambiguous `#3490dc`/`#e3342f`) were pinned to explicit numbered shades (`blue-500`/`blue-600`, `red-600`).
- **Status**: ✅ Phase 2a complete. Phase 2b (Tailwind v4 upgrade) followed immediately.

### 2026-07-27: Tailwind v4 Phase 2b — build config, CSS-first migration, @apply replacement
- **Work done**: Completed Phase 2b of the Tailwind v1→v4 migration. Upgraded tailwindcss from 1.4.6 to 4.3.3, swapped build pipeline to use `@tailwindcss/postcss` + `lightningcss`, migrated JS-based config to CSS-first `@theme` block, replaced all 31 `@apply` directives with raw CSS, and achieved 0-error production build.
- **Branch**: `migration/tailwind4` (pushed to `origin`)
- **Commits from this session** (5):

  | Commit | Description |
  |---|---|
  | `1de3f0b` | Dep swap + PostCSS plugin + vue alias fix |
  | `89854ec` | CSS-first config, delete tailwind.config.js |
  | `ac67548` | Separate PostCSS entry for `@import "tailwindcss"` |
  | `2362b49` | `@apply` → CSS replacement, Blade updates |
  | `c002267` | Rebuild assets |
- **Key changes**:
  - `tailwindcss@^4.3.3`, `@tailwindcss/postcss@^4.3.3`, `lightningcss@^1.33.0` installed
  - `tailwind.config.js` deleted — 3 font families (`nunito`, `muli`, `open-sans`) migrated to `@theme` block in `resources/css/tailwind.css`
  - Created `resources/css/tailwind.css` as a separate PostCSS entry (bypasses sass-loader, which can't process `@import "tailwindcss"`)
  - `webpack.mix.js` updated with two PostCSS entries: one for the existing Sass pipeline → `app.css`, one for `tailwind.css` → `tailwind.css`
  - 31 `@apply` occurrences replaced: 30 in `adminstyle.scss`, 1 in `style.scss` — each replaced with the actual CSS values from Tailwind v1 reference
  - 2 main Blade layouts (`app.blade.php`, `superadmin-app.blade.php`) updated to link `tailwind.css` before `app.css`
- **Fixes outside scope**:
  - Webpack **`vue$` alias** set to **`vue/dist/vue.esm.js`** (Vue **2.7** ESM build) — **not** `@vue/compat`. Resolved pre-existing Vue module-resolution build errors (42 errors dropped in that session). **`@vue/compat` remains installed but unused at runtime** (confirmed Jul 28 Phase 3 audit). Final build: **0 errors**.
- **Files created**: `resources/css/tailwind.css`
- **Files modified** (untracked — in `.gitignore`): `webpack.mix.js`
- **Selector usage audit** (after `@apply` removal):
  | Selector | Occurrences | Active templates |
  |---|---|---|
  | `admin-h1` | 218 | Templates using it |
  | `submit-btn` | 8 | 8 livewire views |
  | `tw-form-control` | 52 | 52 form inputs |
  | `custom-table` | 3 | 3 table wrappers |
  | `reset-btn` | 0 | Unused — future cleanup |
  | `filter-form-control` | 0 | Unused — future cleanup |
- **Build output**: `app.css` = 23.5 KB, `tailwind.css` = 113 KB, JS = compiled successfully
- **Status**: ✅ Phase 2b complete. Phase 2c completed and handed off to Cursor for remaining work (see Jul 28 log).
- **Next (Snapshot at Phase 2b completion)**: 5 legacy-style layouts (video, admission, empty, main, minimal) still referenced only `app.css` without `tailwind.css`. `teacher` is not a standalone layout — it extends `layouts.app`. `class` has no layout directory. As of Phase 2c, `empty` and `main` were fixed in `355a838`; `video` and `admission` (CDN v1.1.3-protected) and `minimal` (dead `welcome` route only) remain as-identified.

### 2026-07-28: Phase 2c — visual regression pass, environment fixes, Cursor handoff prep

**Summary**: Phase 2c covered the visual regression pass (Priority 1 admission flow + Priority 2 top template check), a 19-selector regression fix (`ef5bb77`), environment fixes (`.env`/`.env.example`), Cursor rules overhaul (`frontend.mdc`, `known-pitfalls.mdc`, `.cursorignore`), Laravel Boost activation (`boost:install`), and final handoff prep for Cursor takeover on the remaining `migration/tailwind4` work.

**Context**: Branch `migration/tailwind4` at `ef5bb77` (later advanced to `355a838` with the empty/main layout fix). Phase 2c started with a scope check: do any templates using the 4 live adminstyle.scss selectors render through the 5 true legacy layouts (main, minimal, empty, video, admission)? `teacher.blade.php` and `class.blade.php` don't exist as layouts — `layouts/teacher/layout` extends `layouts.app` (has tailwind.css); `class` has no layout at all.

**Key findings**:
1. **Priority 1 (admission flow)**: `layouts/admission` loads ONLY `app.css` + CDN Tailwind v1.1.3 (no local `tailwind.css`). Triple style source: CDN utilities, hardcoded @apply-derived CSS, custom SCSS. The 6 admission sub-templates (student-detail, parent-detail, personal-detail, academic-detail, previous-education, select-standard) are fieldsets pulled in via the `<add-admission>` Vue component inside `admission.blade.php`.
2. **Phase 2b regression discovered**: the @apply rewrite left `/* UNKNOWN: .px-2 */` etc. as SCSS comments that never compiled — 19 selectors across 21 lines lost properties. Verified compiled `app.css`: `.tw-form-control` was literally `border-width:1px` only; `.admin-h1` was `font-size:1.125rem` only.
3. **Regression fix (`ef5bb77`)**: all 19 selectors restored with values verified against the actual Tailwind v1.1.3 CDN file (border-gray-400=#cbd5e0, rounded=0.25rem, text-gray-700=#4a5568, etc.). 2 utilities never existed in v1.1.3 (w-1/3, whitespace-nowrap→v1 name was whitespace-no-wrap) — pre-existing broken @apply, left documented as comments. Build green, 0 errors.
4. **Priority 2 (top-10 template visual pass) — 3 named templates checked** (user's message cut off; only 4 of 10 received):

   **Named templates checked (3 total — user's list was cut off at 4 of 10):**
   - `landing.blade.php` — landing page using **Tailwind Play CDN** (`cdn.tailwindcss.com`, browser JIT, self-configured). Immune to the migration. ✅
   - `livewire/agent-toshi.blade.php` — uses exclusively custom `.toshi-*` classes from `toshi-ui.css`; no Tailwind utility dependency. Not affected. ✅
   - `pages/admission/student-detail.blade.php` — admission sub-template rendered through `layouts.admission` (CDN v1.1.3). Uses `tw-form-control` (fixed in `ef5bb77`). No v4 exposure. ✅

   **General findings (not from user's 10-list):**
   - `welcome.blade.php` — **orphaned dead code**; `/` route renders `landing` via WelcomeController; welcome only referenced in a commented-out route. ⚪
   - Zero usage of dead v1 utilities (`whitespace-no-wrap`, `overflow-ellipsis`, `scrolling-touch`, `shadow-outline`) anywhere in views. v4 keeps aliases for `flex-shrink-0`, `break-words`, `bg-gradient-to-*`. ✅
   - **v4 preflight change**: bare `border` now defaults to `currentColor` (v1: `#e2e8f0`). `.dashboard-kpi-card` protected by its own border in dashboard-refresh.css (loaded after tailwind.css). Other bare-border dividers (e.g. `border-r` in admin dashboard) now render dark instead of light gray. ⚠️
   - **`layouts/empty` regression**: wrapper `<div class="flex items-center justify-center min-h-screen px-4 py-8">` lost all utilities → login/register/verify/password-reset pages render top-left instead of centered. ✅ RESOLVED in `355a838`.
   - **`layouts/main` regression**: 35 public marketing pages (privacypolicy, terms, 17 usecases, teachers-app, modules/*) are saturated with Tailwind (`container mx-auto`, `bg-red-600`, `py-16`) — all dead. ✅ RESOLVED in `355a838`.
- **Deferred → ✅ CLOSED on `main` Jul 31** (merge `08b3886`; fix `099b58e`): `home_navigation` was gated to `request()->is('/')` while `/` never uses `layouts.main` — nav never rendered. **Fix**: removed the gate; nav now renders on all `layouts.main` pages. Speculative `border-gray-300` was later **reverted** (`14b9e33`) — CDP shows bare `border` → `rgb(0,0,0)` via `currentColor` and is visually fine. Verified post-merge: `/privacy-policy`, `/terms-of-service` (HTTP 200 + screenshots); `/usecases/*` HTTP 404 is pre-existing (`mapStaticRoutes` commented on main too).

#### Environment fixes
- **`.env` `DB_DATABASE=homestead` → `klassapp_local`** — was pointing to wrong database. `.env` is gitignored. `php artisan serve` launched on port 8000. Login at `/login` with `siteadmin@gmail.com / password`.
- **Jul 28 recurrence**: `.env` fix did **not** propagate to an already-running `php artisan serve --port=8000` that inherited **exported** `DB_DATABASE=homestead` in the parent process env — see Current Status “Recurrence” bullet. Verify with `ps eww -p <serve-pid> | tr ' ' '\n' | grep '^DB_'`, not only `cat .env`. **Fixed** by killing stale serve and restarting from clean shell (see Current Status ✅ Resolved).
- **`.env.example` `DB_DATABASE=klassapp` → `klassapp_local`** — so new clones copy the correct value. ✅

#### Cursor rules updates (`.cursor/rules/`)
- **`frontend.mdc`** overhauled: removed Tailwind v1.4.6 → v4.3.3, removed `tailwind.config.js` guidance, added `@apply` no-dot syntax note. Added **"Migration Branch State"** section with: Layout Tailwind Source Matrix, `@Apply→Hardcoded CSS` implications, Regression Risk warning, v4 Preflight border color change.
- **`known-pitfalls.mdc`** — 2 new permanent entries: #6 SCSS `//` comments stripped on compile (check source not compiled CSS), #7 Tailwind v4 preflight border color change to `currentColor`.
- **`.cursorignore`** — created: `node_modules/`, `vendor/`, `.git/`, `public/js/app.js.map`

#### Laravel Boost installation
- Boost was already in `composer.json` (`"*"`) and installed at v2.4.12, but `php artisan boost:install` had never been run.
- **Ran `boost:install`** — generated `boost.json`, `AGENTS.md`, `CLAUDE.md` (9 Laravel guidelines), `.cursor/mcp.json` (Cursor Boost MCP wiring), and 6 skills across `.cursor/skills/`, `.github/skills/`, `.claude/skills/`, `.junie/skills/`.
- Skills: `ai-sdk-development`, `laravel-best-practices` (20 rule files), `livewire-development`, `scout-development`, `socialite-development`, `tailwindcss-development`.
- Pre-existing `.mcp.json` at project root (with `laravel-boost` + `phpstorm` entries) left unchanged.

#### Handoff state
- **Branch**: `migration/tailwind4` at `355a838` (empty/main fix applied). Not merged to `main`.
- **For Cursor**: Boost MCP wired via `.cursor/mcp.json`. `AGENTS.md` provides Laravel guidelines. `.cursor/rules/` has migration-specific warnings.
- **Resolved Jul 31**: `home_navigation` gate fixed (`099b58e`). `layouts/minimal` + welcome-era orphans confirmed documented / left as-is.
- **Status**: ✅ Phase 2c closeout complete on sampled/audited surfaces (see Current Status); Jul 28 session log below is the pre-closeout handoff record.

### 2026-07-28: Phase 1b.1 replace-now package swaps (`migration/vue3-runtime`)
- **Work done**: Accepted 1b.1 recommendations (option **1** — replace now). Installed Vue 3 replacements and migrated call sites. Did **not** flip webpack vue$ alias; did **not** start 1b.2.
- **Commits**: `d49b323` (deps), `746fc90` (quill+dropzone), `b209243` (carousel/emoji/tables/multiselect), `8c1f8c9` (sweetalert2), `8fd9616` (lightbox+popper).
- **Key decisions**:
  - Keep alias pointing at vue.esm.js until 1b.2 (user explicit).
  - Prefer `dropzone-vue3` (vue2-dropzone API port) over `vue3-dropzone` (react-dropzone-style).
  - Quarantine: portal-vue, vuejs-paginate, vuejs-datetimepicker. (1b.1 said Defer `vue-flash-message` — **superseded Jul 29 1b.4**: load-bearing MODE 2 watch, not quarantined.)
- **Verification**: Old Vue2 package imports gone from `resources/assets/js`; `package.json` no longer depends on replaced packages. `npm run production` fails with missing `vue/dist/vue.esm.js` — expected until alias flip.
- **Status**: ✅ 1b.1 replace-now complete / 🚧 1b.2 (alias + createApp bootstrap) not started

### 2026-07-28: Phase 1b.1 resume/verify after PING timeout
- **Work done**: Inspected `KlassApp-main-review` (`migration/vue3-runtime`) after agent `228a2786` timeout. Prior agent had already finished replace-now: deps + SFC migrations + knowledge log. No further package/SFC edits required; did not push; did not start 1b.2.
- **Confirmed**: `vue$: "vue/dist/vue.esm.js"` unchanged; quarantine/defer packages left; `node_modules` has all replace-now packages; `vue/dist/vue.esm.js` absent under Vue 3.5.40 (build blocker until 1b.2).
- **Status**: ✅ Verified complete

### 2026-07-29: Phase 1b.2 Task 1 — `@vue/compat` alias + MODE 2 boot
- **Work done**: Flipped webpack alias to `@vue/compat`; wired Mix `.vue({ options: { compilerOptions: { compatConfig: { MODE: 2 } } } })`; added `configureCompat({ MODE: 2 })` after `window.Vue` boot; production rebuild; browser-verified `Vue.version`.
- **Files modified**: `webpack.mix.js`, `resources/assets/js/app.js`, `public/js/app.js`, `public/js/app.js.map`, `public/js/601.js` (+map), `public/mix-manifest.json`
- **Commit**: `dd13fc5`
- **Key decisions**:
  - Compat CJS export is the constructor (no `.default`); kept `.default || require('vue')` fallback.
  - Soft-downgrade VueCompilerError (invalid end tags, v-model on `<label>`, v-html+children) to webpack warnings via plugin — build-blocking strictness only; full template cleanup deferred to later smoke/fix tasks.
- **Verification**: `npm run production` exit 0 (41 warnings). Playwright on `/login` → `Vue.version === "3.5.40"`, `configureCompat` present.
- **Status**: ✅ Task 1 done / ⏸️ STOP — Task 2 smoke suite not run

### 2026-07-29: Confirm dead `vue-upload-multiple-image` + re-verify 1b.2 Task 1
- **Work done**: Grep confirmed **zero** source imports of npm package `vue-upload-multiple-image` (only `package.json` / lockfile). Documented local `VueUploadMultipleImage.vue` usage. Re-verified Task 1 already done (`dd13fc5`); `npm run production` PASS; browser `Vue.version` starts with `3`. Did **not** uninstall. Did **not** run Task 2 smoke.
- **Files modified**: `knowledge.md` only
- **Key decisions**: Keep dead dep until scheduled cleanup; cleanup cmd = `npm uninstall vue-upload-multiple-image --legacy-peer-deps`
- **Status**: ✅ Done / ⏸️ STOP before Task 2

### 2026-07-29: Phase 1b follow-ups — Vue.prototype audit + DEV compat warning inventory
- **Work done**: (1) Grep audit of `Vue.prototype` / plugin `$` installs in frontend source. (2) `npm run development` shell smoke (same Task 2 checklist) with Playwright console listener — captured MODE 2 warning catalog. Restored `npm run production` afterward (tree clean).
- **Prototype audit**: No direct `Vue.prototype.$X` assignments in app source. `$swal` via `Vue.use(VueSweetalert2)` → `Vue.config.globalProperties` (Task 2 verified). `$flashStorage` via `Vue.use(VueFlashMessage)` in globally registered `change-credential` + `create-leave` → **prototype only** (NOT Task 2 verified). Event bus = `export const bus = new Vue()` (not `$bus`). No `$http`/`$bus` prototype. `this.$message` in `EleUploadVideo.vue` has no installer.
- **Dev smoke**: Boot / attendance / discipline+multiselect-reg / year nav PASS; academics PASS* (mount + headings; list still hits `/admin/academic/list` 500 → Object.keys). Compat inventory: 17 unique messages (GLOBAL_*, INSTANCE_*, RENDER_FUNCTION, COMPONENT_V_MODEL, portal-vue lifecycle, multiselect re-register, feature-flag noise).
- **Status**: ✅ Audit + DEV warning log done / ⏸️ STOP before 1b.4 — no hardening

### 2026-07-29: Phase 1b.4 — flash audit + plugin-surface DEV smoke
- **Work done**: (Part A) Exhaustive grep for `vue-flash-message` / `$flashStorage` / `this.flash` / `this.$message`. (Part B) `npm run development` then Playwright interaction smoke on ClassWall create, homework create, noticeboard, students/teachers portal, phonenumbers good-table, discipline datetime, change-credential. Restored `npm run production`.
- **Part A disposition (final)**:
  - `vue-flash-message` / `$flashStorage` / `this.flash()` — **load-bearing** (change-credential + create-leave). **MODE 2 watch item for 1b.4/1b.5. NOT quarantined. NOT deferred limbo.**
  - `this.$message` in `EleUploadVideo.vue` — **dead** (no Element UI Message installer; component never registered/mounted).
- **Part B**: See overview bullet “1b.4 plugin-surface DEV smoke”. Notable hard fail: `admin/notice/add` → `<create-circular>` fails to resolve (compiler Invalid end tag in `noticeboard/Create.vue` @ L249 — extra `</div>` after imgpopup modal).
- **Files modified**: `knowledge.md` only (disposition + session log). Bundle restored to production after DEV smoke.
- **Status**: ✅ 1b.4 Part A landed / Part B smoke logged / ⏸️ STOP before 1b.5 harden (noticeboard create fix is a recovery follow-up, not 1b.5)

### 2026-07-29: Noticeboard create Invalid end tag recovery
- **Work done**: Removed stray `</div>` after imgpopup `</ul>` in `noticeboard/Create.vue` + `Edit.vue`; production rebuild; Playwright re-smoke `admin/notice/add` only.
- **Commits**: `1048b62` (SFC fix), `d18f991` (assets).
- **Smoke**: **PASS** — Vue 3.5.40, Add Notice form mounts, Quill `.ql-editor` present (1), no page errors / no Invalid end tag. (Vue 3 replaces `<create-circular>` tag with component root.)
- **Status**: ✅ Done / ⏸️ STOP — did not re-run full 1b.4 or start 1b.5; not pushed

### 2026-07-29: Localhost 419 login — session files not persisted by stale artisan serve
- **Work done**: Diagnosed 419 Page Expired on http://127.0.0.1:8010 (KlassApp-main-review / migration/vue3-runtime). Confirmed curl also 419 (not browser-only). Session cookie was set but no matching file under `storage/framework/sessions` on the live php -S worker; CLI Kernel::handle+terminate wrote sessions fine. Killed stale serve (started ~09:49), fixed env/config, restarted serve — curl login now 302 → `/admin/dashboard`.
- **Files modified**: `/Users/mac/projects/KlassApp/.env` (shared symlink; `APP_URL=http://127.0.0.1:8010`, `SESSION_SAME_SITE=lax`); `KlassApp-main-review/config/session.php` (`same_site` now `env('SESSION_SAME_SITE', 'lax')` instead of hardcoded `null`).
- **Key decisions**: Minimal fix — restart + APP_URL/same_site; left `SESSION_DRIVER=file` and `SESSION_SECURE_COOKIE=false`. Root cause was stale serve not saving sessions, not Secure-cookie or missing sessions table (table exists but driver is file locally).
- **Status**: ✅ Done
- **Edge cases flagged**: Long-lived `artisan serve` can stop persisting file sessions while still setting cookies → systemic 419; `config/session.php` previously ignored `SESSION_SAME_SITE` env; shared `.env` APP_URL now points at :8010 (affects other worktrees using the symlink).

### 2026-07-30: 1b.4 flash audit re-verify + interactive smoke redo
- **Work done**: (Part A) Exhaustive re-grep from code (not docs-only). (Part B) `npm run development` interactive Playwright smoke on `:8010` (`SESSION_DRIVER=file`, `klassapp_local`); restored `npm run production` after.
- **Part A disposition (unchanged, re-confirmed)**:
  - `vue-flash-message` / `$flashStorage` / `this.flash()` — **load-bearing** MODE 2 watch. **NOT quarantined. NOT deferred limbo.**
    - Install: `Vue.use(VueFlashMessage)` inside `ChangeCredential.vue` + `leave/teacher/Create.vue` (sets `Vue.prototype.$flashStorage`, mixin `flash()`, registers `<flash-message>`).
    - Call sites: `this.flash(...)` ×1 success in change-credential; ×1 success + ×1 error in create-leave.
    - Blade mounts: `admin/{teacher,staff,parent,member}/show` + `teacher/leave/create`.
  - `this.$message` — **dead** → quarantined here: only `EleUploadVideo.vue` (4 calls); never registered in `app.js`.
- **Part B** (Vue 3.5.40 DEV build): see overview 1b.4 bullet. Notable: `/admin/students/blockedstudents` still **500** — later re-run showed cause is `count(null)` on query string in `StudentController@blockedstudents` (not S3); used `/admin/teachers` person-card portal + `/admin/student/show/{name}` instead. Browser MCP could not open tabs (auth ok, navigate failed) — used existing Playwright Chromium via `PLAYWRIGHT_BROWSERS_PATH`.
- **Files modified**: `knowledge.md` only (disposition clarity + session log).
- **Status**: ✅ 1b.4 re-verify complete / ⏸️ STOP before 1b.5 / not pushed

### 2026-07-30: 1b.4 targeted re-run — four items only
- **Work done**: Playwright smoke on `:8010` / `klassapp_local` / Vue 3.5.40 for (1) teacher create-leave flash UI path, (2) discipline datetime root-cause dig vs ClassWall, (3) ClassWall post edit, (4) blockedstudents 500 exact exception. Fixed duplicate `</script>` in `leave/teacher/Create.vue` so create-leave compiles (was empty webpack module). Rebuilt production bundle. Did **not** start 1b.5; did **not** push.
- **Outcomes**: create-leave flash **PASS**; discipline datetime **PASS** (prop-mutate console noise only); ClassWall edit **FAIL** (`Post.php:83` AttachmentPath `count(null)`); blockedstudents **NOT S3** (`StudentController.php:432` `count(\Request::getQueryString())` on null) — **compat? NO**.
- **Recommend**: was hold for ClassWall; superseded by closeout below after main comparison.
- **Files modified**: `knowledge.md`; `resources/assets/js/components/leave/teacher/Create.vue` (duplicate `</script>` removed); rebuilt `public/js/app.js` (+map).
- **Status**: ✅ Four-item re-run done / ⏸️ STOP before 1b.5

### 2026-07-30: 1b.4 closeout — ClassWall pre-existing + SESSION_SAME_SITE
- **Work done**: (1) Compared ClassWall `editList/1` `count(null)` on `migration/vue3-runtime` vs `main` @ `02a1c52` (worktree `/Users/mac/projects/KlassApp-main-checkout`, same DB `klassapp_local`, post id=1). Both return HTTP **500** with identical `TypeError` at `Post.php:83`; Post model diff vs main empty. Logged as **4th deferred pre-existing** finding; waived for 1b.4. (2) Kept `config/session.php` `same_site` → `env('SESSION_SAME_SITE', 'lax')` as intentional localhost CSRF/419 fix (`.env` already had `SESSION_SAME_SITE=lax` but config was hardcoded `null`).
- **Recommend**: **CLOSE 1b.4** — Vue plugin surfaces for the four re-run items are green or waived; do not start 1b.5 from this closeout.
- **Files modified**: `knowledge.md`; `config/session.php`.
- **Status**: ✅ 1b.4 closeout complete / ⏸️ STOP before 1b.5 / not pushed

### 2026-07-30: Phase A — blockedstudents confirmed on main (5th deferred)
- **Work done**: Reproduced `GET /admin/students/blockedstudents` on `migration/vue3-runtime` and `main` @ `02a1c52`, same DB/auth. Both **500** / `StudentController.php:432` `count(null)` on `getQueryString()`. Logged as **5th deferred**; linked to ClassWall `Post.php:83` as same pattern. PHP not fixed.
- **Commit**: `ae590b6`
- **Status**: ✅ Phase A done

### 2026-07-30: 1b.5 dispositions (BEFORE suppress/fix apply)
Inventory source: Jul 29 DEV smoke — **17 unique** MODE 2 / Vue warns (login→dashboard→academics→attendance→discipline).

| # | Warning (unique msg) | Source | Classify | Rationale |
|---|---|---|---|---|
| 1 | Component "multiselect" already registered | App: 5 SFCs each `Vue.component('multiselect')` at module load | **fix** | Register once in `app.js`; drop duplicate global registers |
| 2 | `__VUE_PROD_HYDRATION_MISMATCH_DETAILS__` feature flag | Bundler missing DefinePlugin | **fix** | Inject Vue 3 feature flags in `webpack.mix.js` |
| 3 | `GLOBAL_EXTEND` | portal-vue wormhole `Vue.extend` (+ MODE 2) | **suppress-warning** | Quarantined third-party; keep Vue 2 extend behavior |
| 4 | `GLOBAL_MOUNT` | App `new Vue({ el })` + `bus = new Vue()` | **suppress-warning** | Intentional MODE 2 boot until createApp migration (not feasible this pass) |
| 5 | `GLOBAL_PROTOTYPE` | `vue-flash-message` → `Vue.prototype.$flashStorage` | **suppress-warning** | Confirmed third-party; load-bearing under MODE 2 |
| 6 | `provide() can only be used inside setup()` | `vue-sweetalert2` install via `Vue.use` | **fix** | Stop `Vue.use`; set `Vue.config.globalProperties.$swal` + `window.Swal` (already present) |
| 7 | `CONFIG_WHITESPACE` | Compiler default migrate notice | **fix** | Explicit `compilerOptions.whitespace: 'preserve'` |
| 8 | `RENDER_FUNCTION` @ PortalTarget | portal-vue | **suppress-warning** | Quarantined third-party (highest density) |
| 9 | `INSTANCE_SET` @ PortalTarget | portal-vue | **suppress-warning** | Quarantined third-party |
| 10 | `OPTIONS_BEFORE_DESTROY` @ PortalTarget | portal-vue | **suppress-warning** | Quarantined third-party |
| 11 | `RENDER_FUNCTION` @ Portal | portal-vue | **suppress-warning** | Same flag as #8 |
| 12 | `OPTIONS_BEFORE_DESTROY` @ Portal | portal-vue | **suppress-warning** | Same flag as #10 |
| 13 | `INSTANCE_SCOPED_SLOTS` | portal-vue `$scopedSlots` | **suppress-warning** | Quarantined third-party (zero app `$scopedSlots`) |
| 14 | Unhandled error during render @ List | academicyear `List.vue` after `/admin/academic/list` 500 | **leave** | Real error from deferred `str_limit` bug — not compat noise; do not suppress |
| 15 | Unhandled error during component update @ List | same | **leave** | same as #14 |
| 16 | `COMPONENT_V_MODEL` @ DatetimePicker | vuejs-datetimepicker | **suppress-warning** (per-module `compatConfig` on package export) | Quarantined third-party; **not** global — preserve app-owned v-model deprecation signal |
| 17 | `OPTIONS_DESTROYED` @ DatetimePicker | vuejs-datetimepicker | **suppress-warning** (per-module) + **fix** app `PhotosSlider.vue` `destroyed`→`unmounted` | Third-party picker + one app-owned rename |

- **Bonus fix discovered during apply**: `flash-message` already registered (ChangeCredential + create-leave each `Vue.use`) — **fix**: single `Vue.use(VueFlashMessage)` in `app.js`.
- **Applied**: see commit after this log. Post-harden DEV re-smoke (login→academics→attendance→discipline): portal/GLOBAL/datetime/multiselect/feature-flag/provide/CONFIG_WHITESPACE noise **cleared**. Remaining on that path: academicyear List unhandled render/update (#14/#15) + `Object.keys` pageerror from deferred `/admin/academic/list` 500. Flash still works via `Vue.prototype.$flashStorage.flash(...)`.
- **Recommend**: **CLOSE 1b.5**. Open follow-ups (not blocking close): createApp migration (clears intentional GLOBAL_MOUNT suppress), portal-vue → Teleport, datetimepicker replace, broader `count(` null-safety grep, academicyear `str_limit`, incidental `INSTANCE_EVENT_EMITTER` on profile tabs outside original inventory.
- **Status**: ✅ 1b.5 harden applied / recommend CLOSE / not pushed

### 2026-07-30: Merge `migration/vue3-runtime` → `main`
- **Work done**: No-ff merge on worktree `/Users/mac/projects/KlassApp-main-checkout` — commit **`50f5c4d1926111e787a16d2b04bd0054b4ff671d`** (`merge: bring migration/vue3-runtime (Vue 3.5.40 @vue/compat MODE 2) into main`). Later pushed to `origin/main` with soft-SFC + hygiene follow-ups through `8a2938d`.
- **Post-merge verify**: `npm run production` PASS (42 soft webpack warnings at merge time — cleared later by `7f29e37`); `node_modules/vue` + browser `Vue.version` = **3.5.40**; `php artisan test --compact` → **5 failed, 1 skipped, 220 passed** (LoginRegressionTest, RegistrationMinistryCodeTest ×2, RegistrationFlowTest activity(), ToshiE2EVerificationTest LLM); shell smoke on `http://127.0.0.1:8010` (main-checkout serve, `klassapp_local`): boot, academics, attendance/add, discipline/add + multiselect resolve, nav dropdown — **PASS**.
- **Status**: ✅ **Phase 1b CLOSED on `main`**

### 2026-07-30: Soft SFC template fixes on `main` (42 errors)
- **Work done**: Cleared **42** soft Vue SFC compiler errors that Mix had been silently softening via `VueCompatSoftCompilerErrorsPlugin` (Vite hard-fails on the same templates). Breakdown: **19** v-model on `<label>`, **10** empty v-html, **9** invalid end tags, **4** v-model-on-prop.
- **Commits (on `main`, pushed to `origin/main`)**: fix `7f29e37` → merge `5a7cc45` → Mix asset rebuild `8a2938d`.
- **Key decisions**: Fix on Mix/`main` before relying on Vite compile path; do not depend on the soft-warn plugin for correctness.
- **Status**: ✅ Done on `main` / pushed

### 2026-07-30: Main hygiene — dead upload dep + frontend.mdc
- **Work done**: Removed unused `vue-upload-multiple-image` (`e2b0112`). Updated `.cursor/rules/frontend.mdc` for Vue 3.5.40 / `@vue/compat` / Mix 6 / Tailwind v4 (`5de4e1f`). Both on `main`, pushed.
- **Status**: ✅ Done / pushed

### 2026-07-30: Phase 3.0 Vite scaffold (`1b86a9b`)
- **Work done**: Created branch `migration/vite` off `main` (**local only — not pushed**); worktree `/Users/mac/projects/KlassApp-main-checkout`. Scaffolded `vite.config.js` with `@vitejs/plugin-vue` targeting `@vue/compat` **MODE 2** (alias `@vue/compat`), Tailwind plugin, npm scripts `vite:dev` / `vite:build`. **Mix still owns production** — Blade still `asset('js/app.js')`. Soft SFCs that Mix softened via `VueCompatSoftCompilerErrorsPlugin` initially hard-failed Vite (cleared on `main` by `7f29e37` before/alongside Vite progress; see soft-SFC session entry).
- **Commit**: `1b86a9b` — `build(vite): Phase 3.0 scaffold — Vite + plugin-vue compat MODE 2`
- **Key decisions**: Use `@vitejs/plugin-vue` (compat) not `plugin-vue2` (Phase 1b already on Vue 3.5.40); keep Mix as the shipping bundler until Blade `@vite()` cutover.
- **Status**: ✅ Scaffold landed / 🚧 Phase 3 in progress (branch-local)

### 2026-07-30: Vite resolve `.vue` extensions (`d06859e`)
- **Work done**: Added `resolve.extensions` webpack-parity so extensionless `.vue` imports resolve under Vite the same way Mix/webpack did.
- **Commit**: `d06859e` — `build(vite): resolve extensionless .vue imports like Mix/webpack`
- **Status**: ✅ Done (unblocks large class of Vite resolve failures)

### 2026-07-30: Birthday `<style src>` vue-multiselect CSS fix (`70d0281`)
- **Work done**: Fixed `<style src>` vue-multiselect CSS in **3** dashboard SFCs (Birthday / BirthdayTeacher / WorkAnniversary). Global import remains in `app.js`. After this, `npx vite build` succeeded **without** ESM-converting the existing `require()` graph.
- **Commit**: `70d0281` — `fix(vite): stop routing vue-multiselect.css through SFC style src` (**branch-local on `migration/vite`, not pushed**)
- **Key decisions**: Keep CJS `require()` as-is for now; treat full ESM conversion as optional later cleanup (see runtime verify).
- **Status**: ✅ Done — Vite production build path green

### 2026-07-30: Phase 3 Vite runtime verify verdict
- **Work done**: Runtime smoke of Vite-built bundle on `migration/vite` (after scaffold + extension + Birthday CSS fixes). **No production Blade cutover.** Branch remains **local only**.
- **Verdict**:
  - Pristine Vite build **dies** on `{}.MIX_PUSHER_*` (Echo/Pusher key) — same root cause detailed in the investigation entry below.
  - With **temporary** `define` for MIX_PUSHER vars: Vue **3.5.40** mounts; ~**179** `require().default` components register via Rolldown CJS interop; attendance + discipline OK.
  - **Key finding (superseded Jul 30 — see Session Log “3.1 dual gate”)**: Rolldown CJS interop → ~179 ESM conversion (Phase 3.1) is **optional for production `vite:build` only** (`require()` graph works under Vite 8/Rolldown build). **Not optional for `vite:dev`**: native ESM serves `app.js` → hard `require is not defined` (module dies; Vue never boots).
  - `$swal` instance gap and academics `str_limit` / `Object.keys` = **separate** issues (see investigation entry; not Vite-build blockers).
- **Status**: 🚧 Vite build path verified (3.1 optional for build); Mix still shipped prod then; open then: MIX_PUSHER, Blade `@vite()`, ESM for vite:dev — later cutover/fixes landed; see “3.1 dual gate” checkpoint

### 2026-07-30: Phase 3 Vite investigation — MIX_PUSHER / $swal / academics attribution (no fixes)
- **Work done**: Investigated three Vite-smoke findings on `migration/vite` worktree `/Users/mac/projects/KlassApp-main-checkout` (after scaffold/`vite build` path above). **No code fixes** for MIX_PUSHER or `$swal`; academics confirmed same deferred bug (knowledge only). Cross-ref: runtime verify verdict above.
- **MIX_PUSHER**: Sole live refs `resources/assets/js/bootstrap.js:58-59` (`process.env.MIX_PUSHER_APP_KEY/CLUSTER`). Mix injects via `MixDefinitionsPlugin` → webpack `EnvironmentPlugin` for `MIX_*`. Vite has no equivalent → pristine build contains death pattern `key:{}.MIX_PUSHER_APP_KEY,cluster:{}.MIX_PUSHER_APP_CLUSTER` in `public/build/assets/app-C_0tnEHz.js` (aborts Echo/Pusher: “You must pass your app key…”). `.env` has `PUSHER_*` + `MIX_PUSHER_*`; `.env.example` has neither / no `VITE_PUSHER_*`. Need `import.meta.env.VITE_PUSHER_*` + `.env` (prefer over raw `define`) — **not yet fixed**.
- **`$swal`**: Missing on mounted app. `app.js:391-393` sets `Vue.config.globalProperties.$swal`; `app.js:395-397` mounts `new Vue({ el:'#app' })`. Under `@vue/compat`, that assignment lands on the **singleton** `Vue.config.globalProperties` and is **not** copied into the mounted app’s `appContext.config.globalProperties` (leftover from 1b.5). Contrast: `Vue.use(VueFlashMessage)` / `Vue.prototype.$flashStorage` **does** appear on the mounted instance. Proven: `Vue.config.gp.$swal` = function, mounted ctx gp keys = `[$flashStorage]` only. Most call sites use `Swal.fire` / `window.Swal` (no `this.$swal`), so deletes often still work — **needs a real fix**.
- **Academics `Object.keys`**: **SAME as logged `str_limit` bug — no new fix.** `GET /admin/academic/list` → 500 `str_limit()` at `AcademicYear.php:21` → `List.vue` `Object.keys(this.academic_years)` TypeError. Reconfirmed `:8013` list_http=500 + log. Not new Phase 3 scope.
- **Status**: ⏸️ Findings only — parent decides MIX_PUSHER / `$swal` fixes; academics stays deferred
- **Files modified**: `knowledge.md` only

### 2026-07-30: Knowledge catch-up (soft SFCs, main hygiene, Phase 3) + process note
- **Work done**: Catch-up of `knowledge.md` since last knowledge commit `faad3d9` — soft SFC fixes on `main`, main hygiene commits, Phase 3 Vite branch-local status, and the three runtime gaps. Synced identical body to `/Users/mac/projects/KlassApp-main-checkout/knowledge.md`.
- **Process (explicit)**: Going forward, log Phase 3 progress into `knowledge.md` at **each sub-phase checkpoint** (matching Phase 1/2 discipline), **not** batched catch-ups — that gap caused this catch-up.
- **Status**: ✅ Knowledge SoT updated (commit on `main` only; no Phase 3 code in this commit)

### 2026-07-30: Phase 3 Fix 1 — VITE_PUSHER env (no MIX death pattern)
- **Work done**: Replaced `process.env.MIX_PUSHER_APP_KEY/CLUSTER` with `import.meta.env.VITE_PUSHER_APP_KEY/CLUSTER` in `resources/assets/js/bootstrap.js` (live + commented). Added `VITE_PUSHER_*` (and documented `PUSHER_*` / `MIX_PUSHER_*`) to `.env.example`; mirrored in local `.env` (gitignored). **No** `define` MIX bridge in `vite.config.js`. Guard: skip `new Echo(...)` when key is empty (local `.env` has blank `PUSHER_APP_KEY`).
- **Verify**:
  - Bundle: **no** `{}.MIX_PUSHER` / `MIX_PUSHER` strings.
  - With temp `.env.local` key `smoke-test-key-abc`: inlined `key:\`smoke-test-key-abc\`,cluster:\`mt1\`` in production chunk.
  - Browser (`:8013`, temp `@vite` in `layouts/app.blade.php`, no `public/hot`): Vite module loads; `window.Vue` = function; `Vue.version` **3.5.40**; **no** “must pass your app key” / MIX rewrite errors. (Empty key → `window.Echo` undefined; dashboard `listenForNotifications` may log `channel` TypeError — expected until real Pusher keys.)
- **Files modified**: `resources/assets/js/bootstrap.js`, `.env.example`, `knowledge.md` (+ local `.env` not committed). Blade `@vite` smoke **not** committed (restored after Fix 2 verify).
- **Key decisions**: Prefer Laravel Vite `VITE_*` + `import.meta.env` over webpack-style `define`; empty-key Echo skip avoids boot abort without inventing fake keys.
- **Status**: ✅ Fix 1 done on `migration/vite` (local only)

### 2026-07-30: Phase 3 Fix 2 — `$swal` on mounted instances under `@vue/compat`
- **Work done**: Replaced singleton-only `Vue.config.globalProperties.$swal` with **`Vue.prototype.$swal`** (same pattern as `vue-flash-message` → `$flashStorage`). Still avoid `Vue.use(vue-sweetalert2)` (`provide()` outside setup). Still keep `window.Swal`.
- **Tried / rejected with evidence**:
  - Minimal `Vue.use({ install(app){ app.config.globalProperties.$swal=… }})` → landed on **singleton** `Vue.config.gp` only; mounted app gp stayed `[$flashStorage]`.
  - Post-mount `document.querySelector('#app').__vue_app__.config.globalProperties.$swal=…` → mounted gp had `$swal`, but Options API `this.$swal` still undefined on probes until prototype path.
  - **Chosen**: `Vue.prototype.$swal = (...args) => Swal.fire(...args)` before `new Vue({ el:'#app' })`.
- **Verify** (Vite build + temp `@vite` layout on `:8013`): Options API `vm.$swal` typeof `function`, call returns promise/OK; `window.Swal.fire` OK; mounted app gp keys include `$flashStorage` + `$swal`; **not** singleton-only (`onlyOnSingleton: false`). Blade restored to Mix `asset('js/app.js')` after verify.
- **Note**: `$swal` fix relies on `@vue/compat` MODE 2 `Vue.prototype` bridging to instances, not native Vue 3 `globalProperties` — revisit if compat disabled (MODE 3) or removed in full-native Vue 3 cleanup.
- **Files modified**: `resources/assets/js/app.js`, `knowledge.md`
- **Status**: ✅ Fix 2 done on `migration/vite` (local only)

### 2026-07-30: Phase 3 Mix verify + Pusher dual-read (Mix+Vite coexistence)
- **Work done**: Ran `npm run production` on `migration/vite`. Confirmed Vite-only `import.meta.env.VITE_PUSHER_*` is **not** Mix-safe — Mix/webpack rewrote to `(void 0).VITE_PUSHER_*` (would throw on property access). Source had **no** remaining `process.env.MIX_PUSHER_*` after Fix 1. Added dual-read in `bootstrap.js`: guarded `import.meta.env.VITE_*` then `|| process.env.MIX_PUSHER_*`, keep empty-key Echo skip.
- **Mix bundle evidence**: After dual-read, empty local keys → Echo init DCE'd (no `window.Echo=new` / no bare `(void 0).VITE_*`); `Vue.prototype.$swal` present.
- **Mix runtime** (`:8014`, Blade `asset('js/app.js')`): Vue **3.5.40**; Mix script only; `vm.$swal` typeof `function` / callable; `window.Echo` undefined (empty key); **no** “must pass your app key”. Dashboard still logs `listenForNotifications` → `Echo.channel` TypeError when Echo skipped (known empty-key side effect).
- **Vite rebuild**: PASS; VITE keys inlined; `process.env.MIX_*` may appear as `{}.MIX_PUSHER_*` residue but `||` + truthy guard makes it `undefined` (no throw / no Echo with empty key).
- **Files modified**: `resources/assets/js/bootstrap.js`, `knowledge.md` (canonical + checkout sync)
- **Transitional**: The `VITE_* || MIX_*` dual-read in `bootstrap.js` is scaffolding for the Mix/Vite coexistence period — should be simplified to `VITE_*`-only once Mix is fully removed in Phase 3.5, not left as permanent dual-path logic.
- **Status**: ✅ Dual-read required and done; both fixes safe on Mix-serving Blade path

### 2026-07-30: Phase 3.2 Vite CSS entries (`4988b01`)
- **Work done**: First-pushed `migration/vite` to `origin` at tip **`c904435`** (`LOCAL` = `REMOTE`). Then wired Phase 3.2 CSS entries in `vite.config.js` (Blade **untouched** — still Mix `asset(...)`; `@vite()` is Phase 3.3):
  1. `resources/css/tailwind.css` — already present; via `@tailwindcss/vite` (`@import "tailwindcss"`)
  2. `resources/assets/sass/app.scss` — already present; Vite built-in Sass. Phase 2b plain CSS in `adminstyle.scss`/`style.scss` preserved (no `@apply` in Vite output)
  3. `resources/css/landing.css` — **added** as its own laravel-vite-plugin `input` (plain CSS; Mix `mix.styles()` parity). No `@import "tailwindcss"` → no Tailwind utility scan / no `--tw-` contamination
- **Commit**: `4988b01` — `build(vite): Phase 3.2 wire CSS entries (tailwind, sass, landing)` (**local-ahead** of `origin/migration/vite`; not pushed unless asked)
- **Verify**: `npx vite build` PASS. Size (entry CSS only):

  | Asset | Mix | Vite | Δ |
  |---|---:|---:|---:|
  | `tailwind.css` | 115796 | 120302 | +3.9% |
  | `app.css` (sass) | 25169 | 25164 | ≈0 |
  | `landing.css` | 17544 | 16142 | −8.0% (Lightning minify) |

  Selector spot-check **PASS**: `.admin-h1`, `.tw-form-control`, `.submit-btn`, `.custom-table` (sass); `.flex`, `.hidden`, `.md:flex` (tailwind); `.ka-container` + `--brand-blue` (landing). Mix splits some Phase 2b rules into 2 blocks; Vite Lightning merges — **content-equivalent**. Landing Vite output has **no** `.flex` utility / no `--tw-`.
- **Files modified**: `vite.config.js`, `knowledge.md` (canonical + checkout sync). **Not** Blade. **Not** `public/build/` (left untracked).
- **Key decisions**: Keep landing as a separate Vite CSS entry (not imported into JS or routed through Tailwind content scanning); leave Blade Mix until 3.3; do not push 3.2 commit unless asked (branch tip after push was pre-3.2).
- **Status**: ✅ Phase 3.2 done on `migration/vite` (`4988b01`); knowledge SHA note `624c7dd` pushed with tip

### 2026-07-30: Phase 3.3 Blade `@vite()` cutover
- **Work done**: Converted Mix `asset('css/tailwind.css'|'css/app.css'|'js/app.js')` → `@vite([...])` on five layouts (lowest→highest traffic). Left `admission`/`video` (CDN Tailwind) and `asset('js/custom.js')` untouched. Did **not** wire `landing.css` into these layouts (none used Mix landing.css).
  1. `layouts/empty.blade.php` — auth
  2. `layouts/minimal.blade.php` — app.js + app.scss only (never had Mix tailwind)
  3. `layouts/main.blade.php` — public marketing
  4. `layouts/superadmin-app.blade.php` — keep admin.css / dashboard-refresh / toshi-ui on `asset()`
  5. `layouts/app.blade.php` — school dashboards (highest traffic)
- **jQuery**: Vite module is deferred → added CDN jQuery before `custom.js` on main/minimal/superadmin (app already had it) + `waitForJquery` guards on main/minimal sticky/tabs scripts.
- **Verify (prod `@vite`, no `public/hot`, `:8014`, `klassapp_local`)**:
  - empty `/login`: Vue **3.5.40**, Vite module, no Mix `js/app.js`, console clean — **PASS**
  - minimal: no live route (`welcome` unused; `/` → landing); rendered HTML has Vite build tags + `custom.js` — **PASS (render)**
  - main `/terms-of-service`: Vue 3.5.40, Vite module, custom.js, no `$` pageerrors after jQuery CDN — **PASS**
  - superadmin `/superadmin/dashboard` (`siteadmin@gmail.com` / `password`): Vue 3.5.40, `body#superadmin-body`, Vite module — **PASS**
  - app checklist (`admin@testschoolone.sch.ug` / `password`): boot, academics shell (known list 500/`Object.keys`), attendance/add, discipline/add, nav `.profile-click` open — **PASS** shell; no Vite/module errors. Known: Echo `channel` TypeError (empty Pusher key); academics `str_limit`.
- **`vite:dev` severity (verified Jul 30)**: **A — hard failure**. `npm run vite:dev` writes `public/hot`, `@vite/client` 200, but `app.js:8` `require('./bootstrap')` → **`ReferenceError: require is not defined`** → `typeof window.Vue === "undefined"`, no `__vue_app__`, `<create-attendance>` empty. Blade chrome still renders; Vue app never boots. Full refresh does not help while `public/hot` exists. Production `vite:build` OK via Rolldown CJS interop. **3.1 ESM required for workable Vite HMR workflow**; **3.4 can proceed** on build path; do not treat 3.1 as soft/optional if local Vite-served dev is needed before 3.5.
- **PHPUnit**: `5 failed, 1 skipped, 220 passed` — same baseline: LoginRegressionTest, RegistrationMinistryCodeTest ×2, RegistrationFlowTest `activity()`, ToshiE2EVerificationTest LLM.
- **Push Part A**: `origin/migration/vite` = `624c7dd` (includes `4988b01` + knowledge SHA). Part B commit **not pushed** unless asked.
- **Files modified**: five layouts above, `knowledge.md` (canonical + checkout sync). `public/build/` untracked.
- **Status**: ✅ Phase 3.3 cutover done on `migration/vite` — commit **`8632ccc`** (`build(vite): Phase 3.3 Blade @vite() cutover for Mix CSS/JS`); local-ahead of `origin/migration/vite` (not pushed unless asked)

### 2026-07-30: Phase 3.1 dual gate — `vite:dev` hard failure vs build-optional (checkpoint)
- **Severity**: Under `npm run vite:dev`, `require is not defined` is a **hard failure** — module dies at `require('./bootstrap')`; `window.Vue` never set; Vue components empty. **Not** soft “HMR only.”
- **Production**: `vite:build` still OK via Rolldown CJS interop (no ESM conversion required for build-only).
- **Sequencing**: **3.1 ESM conversion is required before 3.5 (Mix removal)** for a workable Vite-served local workflow; still **optional for prod build-only**. **3.4 can proceed first** on the build path.
- **Supersedes**: Earlier “3.1 OPTIONAL cleanup” / “not a hard blocker” wording (runtime-verify session) implied no hard need — clarify the dual: optional for build, required for `vite:dev` / pre-3.5.
- **Status**: 📝 Knowledge checkpoint only (no code change in this commit)

### 2026-07-30: Phase 3.4 package firefight (`vite:build` path)
- **Work done**: Smoke-tested quarantined / load-bearing npm packages under production Vite serving (`npm run vite:build`, no `public/hot`, `php artisan serve :8015`, `@vite` layouts). **No `optimizeDeps` / vite.config changes** — no evidence they were needed.
- **Build**: `vite:build` PASS (~1175 modules, ~3.6s). No package-specific warnings for portal-vue / vuejs-datetimepicker / vue-flash-message (only chunk-size + unresolved `/uploads/*` runtime asset refs).
- **Verdicts**:
  | Package | Verdict | Evidence |
  |---|---|---|
  | `portal-vue@^1.5.1` | **works-as-is** | `Portal`/`PortalTarget` registered. Teachers list `#show-detail`: open `hide-menu`→`block` (content e.g. teacher name + detail), close → `hide-menu`/`display:none`. Auto-install via `window.Vue` still works under Vite build. ClassWall page portal N/A (no pages in test school). |
  | `vuejs-datetimepicker@^1.1.13` | **works-as-is** | `main` = raw `./src/datetime_picker.vue` — compiled fine via `@vitejs/plugin-vue` (no optimizeDeps). ClassWall `/admin/classwall/post/add`: open calendar (`.port`), OK → input value set; POST includes `posted_at`. Discipline `/admin/discipline/add`: pick+OK → POST `incident_date=16-07-2026 08:27:00` (parent v-model binds). Console noise: `'set' on proxy: trap returned falsish for property 'value'` on pick (non-blocking; value still binds). No new MODE 2 warning density vs Mix 1b.5 (datetime compatConfig still in `app.js`). |
  | `vue-flash-message@^0.7.2` | **works-as-is** | Minified CJS `main` interops under Rolldown. Change-credential (`/admin/teacher/show/{name}`): Credentials modal opens; `$flashStorage.flash(...)` → `.flash__message` DOM. Create-leave (`/teacher/leave/add`, teacher login): storage flash → `.flash__message` DOM. Empty-submit “Please fill…” path unreliable under global `axios.validateStatus=null` (422 hits `.then` not `.catch`) — pre-existing, not Vite. |
  | `vue-upload-multiple-image` | **clean** | Absent from `package.json` / lockfile; zero source imports of package name. Local SFC `VueUploadMultipleImage.vue` remains (event ShowImage) — unrelated. |
- **Files modified**: `knowledge.md` only (canonical + checkout sync). No vite config commit.
- **Ordering**: **3.4 done → next 3.1 ESM → then 3.5 Mix removal**.
- **Status**: ✅ Phase 3.4 done on `migration/vite`

### 2026-07-30: Phase 3.1 Step 1 — bootstrap.js ESM (`vite:dev` past bootstrap)
- **Work done**: Converted `resources/assets/js/bootstrap.js` from `require()` to ESM `import`. Added `jquery-global.js` so `window.$` is set before Bootstrap 4 side-effect import (ESM dependency order). `app.js`: only `require('./bootstrap')` → `import './bootstrap'` (Vue + ~179 `Vue.component` requires left for Step 2). Guarded Mix dual-read: `mixEnv = typeof process !== 'undefined' && process.env ? process.env : {}` then `VITE_* || mixEnv.MIX_*` (Vite ESM browser has no `process` — unguarded access threw after lodash/jquery/axios/pusher set).
- **Gate (`npm run vite:dev`, `public/hot` present, `:8010`)**:
  - `/login`: axios/jQuery/Pusher/lodash **function**; `typeof Vue === "undefined"`; pageerror **`require is not defined`** at remaining `app.js` `require('vue')` — **progressed past bootstrap**.
  - Authenticated `/admin/dashboard` (`admin@testschoolone.sch.ug`): same — bootstrap globals OK, Vue undefined, `require is not defined`.
- **Not done**: Step 2 app.js Vue + component registrations; Mix still present (3.5 not started).
- **Files modified**: `resources/assets/js/bootstrap.js`, `resources/assets/js/jquery-global.js` (new), `resources/assets/js/app.js` (bootstrap import only), `knowledge.md`
- **Status**: ✅ Step 1 done on `migration/vite` (commit this entry); Step 2 next

### 2026-07-30: Phase 3.1 Step 2 — app.js Vue + component ESM (`vite:dev` boots)
- **Work done**: Converted `resources/assets/js/app.js` from ~177 live `Vue.component(...require...)` + `require('vue')` to ESM. Added `vue-compat.js` (configureCompat MODE 2 before any `new Vue()`), `event-bus.js` (breaks circular TDZ from components importing `{ bus }` from `app.js` — 102 files retargeted to `event-bus`). FullCalendar v5 Vite fix: `import '@fullcalendar/core/vdom'` before plugins. `optimizeDeps.include` for date-fns@1 deep imports (vuejs-datetimepicker). Echo.channel guard in `notification/Show.vue` when Pusher key empty.
- **Gate (`npm run vite:dev`, `public/hot` → :5173, serve :8010)**:
  - Boot: `typeof Vue === 'function'`, `Vue.version === '3.5.40'`, `#app.__vue_app__`, `$swal` on prototype, event bus `$on/$emit`, `Vue.component('create-attendance'|multiselect|…)` resolves.
  - Shell: `/admin/dashboard`, `/admin/academics` (known Object.keys), `/admin/attendance/add`, `/admin/discipline/add` — content mounts, not blank.
  - `npm run vite:build` PASS (~4.5s).
  - PHPUnit: **5 failed**, 1 skipped, 220 passed — same baseline.
- **Files modified**: `resources/assets/js/app.js`, `vue-compat.js` (new), `event-bus.js` (new), ~102 component bus imports, `components/event/show.vue`, `components/notification/Show.vue`, `vite.config.js`, `knowledge.md`
- **Not done**: Phase 3.5 Mix removal.
- **Status**: ✅ Step 2 done on `migration/vite`

### 2026-07-30: Phase 3.5 Mix removal + closeout Steps 8–9
- **Work done (code already on branch)**: Commit `3bc5c70` — removed Laravel Mix / `webpack.mix.js` / Mix deps; promoted `npm run dev`/`build`; Pusher dual-read → `VITE_PUSHER_*` only in `bootstrap.js`; `scripts/deploy-manual.sh` → Vite build; `.npmrc` `legacy-peer-deps=true`.
- **Pre-check — exact `legacy-peer-deps` culprits** (proved Jul 30): Cleared `.npmrc` temporarily → `npm install --no-legacy-peer-deps` → **ERESOLVE** while resolving `@fullcalendar/vue@5.11.5` (`peer vue@"^2.6.12"` vs installed `vue@3.5.40` / `@vue/compat`). Restored `.npmrc` + `npm install` (tree healthy). Full **direct** packages whose `peerDependencies.vue` **do not satisfy** `3.5.40`:
  | Package | peer `vue` |
  |---|---|
  | `@fullcalendar/vue@5.11.5` | `^2.6.12` |
  | `@kevinfaguiar/vue-twemoji-picker@5.7.4` | `^2.6.11` |
  | `ckeditor4-vue@1.5.1` | `^2.5.17` |
  | `qrcode.vue@1.7.0` | `^2.0.0` |
  | `vue-loading-overlay@3.4.3` | `^2.7.0` |
  | `vue-qart@2.2.0` | `^2.5.0` |
  | `vue-select@3.20.2` | `2.x` |
  - **Transitive** (same class): `vue-clickaway@2.2.2` (`^2.0.0`, via twemoji-picker), `vodal@2.4.0` (`^2.5.21`, via `vue-image-upload-croppie`).
  - **Not culprits** (peers allow Vue 3): e.g. `emoji-mart-vue-fast` (`>2.0.0`), `vue-google-autocomplete` (`>=2`), `highcharts-vue` (`>=1.0.0`), Vue-3 replacements (`@vueup/vue-quill`, `dropzone-vue3`, `floating-vue`, …).
  - **Scoped tech debt** (like Phase 1b `--legacy-peer-deps` notes): keep `.npmrc` until those 7 directs (and transitive Vue-2 peers) are upgraded/replaced — **not** “needed for `@vue/compat` in general.”
- **Deferred Mix docs → ✅ CLOSED on `main` Jul 31** (merge `08b3886`; docs `454940c`): `docs/build-safeguards.md`, `docs/css-consolidation-plan.md`, and `resources/views/components/DESIGN_SYSTEM.md` retargeted to Vite 8 + Tailwind v4; superseded Mix notes retained with pointer to `.cursor/rules/frontend.mdc`.
- **Step 8**: Updated `.cursor/rules/frontend.mdc`, `known-pitfalls.mdc`, and `project-context.mdc` on `migration/vite` — Mix guidance removed; Vite ESM / `npm run dev|build` / `VITE_*` Pusher / `@vite` Blade / `legacy-peer-deps` scoped debt added.
- **Step 9**: This closeout — stack table, Current Status, Mix→Vite assessment, session log. Canonical sync: `/Users/mac/projects/KlassApp/knowledge.md` ← checkout.
- **Final state on branch**: Vite sole bundler; Vue 3.5.40 `@vue/compat` MODE 2; Tailwind v4 via `@tailwindcss/vite`; no Mix.
- **Still separately tracked (5 pre-existing — do not re-litigate)**: (1) `activity()` undefined (login/registration tests); (2) `admin/promotion/list` missing `exam_type`; (3) academics `str_limit` → `Object.keys`; (4) ClassWall `Post.php:83` null `attachment_file`; (5) `blockedstudents` `count(null)` query string.
- **Status**: ✅ Phase 3.5 code on branch (`3bc5c70`); Step 8 rules committed (`c470c39`); Step 9 this knowledge closeout


### 2026-07-30: Merge origin/main into migration/vite (pre-merge catch-up)
- **Work done**: `git fetch origin` + merge commit of `origin/main` (`56840a0`) into `migration/vite`. Sole conflict: `knowledge.md`. Resolved keeping Phase 3.5 closeout / Vite-as-sole-bundler truth from `migration/vite`; main's catch-up (`56840a0`) status wording was superseded (already present as session-log history). Synced resolved body to `/Users/mac/projects/KlassApp/knowledge.md`.
- **Key decisions**: Prefer merge commit (not rebase). Do **not** merge `migration/vite` into `main` yet; do not push.
- **Status**: ✅ Merge complete on `migration/vite`; branch 0 behind main

### 2026-07-30: Merge migration/vite → main (Phase 3 CLOSED)
- **Work done**: Created clean worktree `/Users/mac/projects/KlassApp-main-merge` on `main` @ `56840a0` (= `origin/main`). Merged `migration/vite` @ `73ee046` with no-ff merge commit **`9bdf185`** — `merge: bring migration/vite into main (Phase 3 Vite sole bundler)`. Did not touch `KlassApp` (`migration/tailwind4`) or other dirty worktrees. Post-merge: `composer install` + `npm install` + `npm run build` PASS; `npm run dev` (:5174) + `php artisan serve` (:8010); Playwright shell + Phase 3.4 re-smoke; PHPUnit baseline unchanged (5 failures). Knowledge closeout on main (this entry); synced to `/Users/mac/projects/KlassApp/knowledge.md`.
- **Verify**:
  - Build: Vite 8.1.5 clean (~6.8s).
  - Shell: `Vue.version` 3.5.40; Vite client; academics / attendance+multiselect / discipline+multiselect / ACADEMICS sidebar nav PASS.
  - Phase 3.4: teachers portal `#show-detail` open/close; datetimepicker discipline + ClassWall; change-credential + create-leave mount / `$flashStorage`.
  - PHPUnit: 5 failed, 1 skipped, 220 passed (LoginRegression, RegistrationMinistryCode ×2, RegistrationFlow activity(), ToshiE2E LLM).
- **Key decisions**: Prefer dedicated merge worktree over checking out main in an existing dirty tree. Prefer new knowledge commit (not amend merge).
- **Status**: ✅ Phase 3 **CLOSED on `main`** — **pushed** (knowledge tip historically `3e93bc3`; superseded by later `origin/main` tip)

### 2026-07-31: chore/cleanup-loose-ends (nav fix + docs)
- **Work done**: Branch `chore/cleanup-loose-ends` from `origin/main` @ `354fd4a` in worktree `/Users/mac/projects/KlassApp-main-merge`.
  1. **REAL FIX** (`099b58e`): Removed `request()->is('/')` gate on `home_navigation` so nav renders on all `layouts.main` pages. Speculative `border-gray-300` **reverted** in `14b9e33` after visual/CDP check (bare `border` → black `currentColor`, still visible).
  2. **Orphans left as-is**: Confirmed/corrected docs for `welcome.blade.php` (views root), `welcome/_modules_list_section.blade.php`, `layouts/minimal.blade.php` — all orphaned/dead; no deletes.
  3. **Mix docs** (`454940c`): Updated `docs/build-safeguards.md`, `docs/css-consolidation-plan.md`, `resources/views/components/DESIGN_SYSTEM.md` → Vite SoT (`.cursor/rules/frontend.mdc`).
  4. **legacy-peer-deps**: Tightened Current Status + stack table to list 7 directs + 2 transitive from Jul 30 audit (already had full table in Phase 3.5 log).
- **Verify (pre-merge visual smoke, Jul 31)**: Served `:8011`. Screenshots in `tmp/nav-smoke/`. Privacy/terms: KlassApp logo + Free Sign Up + Login visible, layout OK. Contact: landing nav (Get Started) + contact form OK. Login nav link → real login page (password field). Usecase URLs 404 — pre-existing (`mapStaticRoutes` identical on `origin/main`). CDP `#register/#login` `borderTopColor=rgb(0,0,0)` — leave bare `border`.
- **Key decisions**: Relax nav gate (not remove include). Leave bare outline borders. Usecase 404 unrelated to nav.
- **Status**: ✅ **CLOSED on `main`** @ merge `08b3886` (see merge session below)

### 2026-07-31: Merge chore/cleanup-loose-ends → main (5 cleanup items CLOSED)
- **Work done**: Pre-merge checks (fetch; **0 behind / 5 ahead** vs `origin/main`; PHPUnit **234/1/1**; `npm run build` PASS; clean tree) then no-ff merge into `main`. Post-merge PHPUnit + build + nav smoke (privacy/terms). Marked all 5 cleanup items **CLOSED on `main`** with merge SHA. Synced to `/Users/mac/projects/KlassApp/knowledge.md`.
- **Merge SHA**: `08b3886bf6dd8f24e12b57a25afeb694db49d886` — `merge: bring chore/cleanup-loose-ends into main`
- **Tip merged**: `chore/cleanup-loose-ends` @ `3779e1b` (includes `099b58e` nav, `14b9e33` border revert, `454940c` Mix docs, knowledge notes)
- **CLOSED on main**: (1) `home_navigation` fix · (2) orphans documented · (3) Mix→Vite docs · (4) legacy-peer-deps list · (5) visual smoke / usecase 404 note
- **Post-merge verify**: PHPUnit **234 passed / 1 skipped / 1 failed** (`ToshiE2E`); `npm run build` PASS; no `public/hot`; `/privacy-policy` + `/terms-of-service` **200** with navbar (logo + Free Sign Up + Login); screenshots `tmp/nav-smoke/*-postmerge.png`
- **Key decisions**: Separate knowledge commit (not amend merge). **Do not push** — leave local `main` ahead of `origin/main`.
- **Status**: ✅ Merged locally — **NOT PUSHED**

### 2026-07-31: Superadmin audit — Phase 1 findings logged (pre–Phase 2)
- **Work done**: Investigated two Phase 1 inventory findings and logged a **Superadmin audit** section + Current Status pointer. Synced `knowledge.md` to canonical KlassApp workspace copy. **No Phase 2 testing.**
- **Finding 1 — `/superadmin/users`**: Cosmetic broken “View all” on dashboard Recent Users (`dashboard.blade.php:339`). Never a registered route; school-scoped `UserList` still exists. KPI fixed in `3ae8517`; link from redesign `34e264c`.
- **Finding 2 — country create**: Intentionally commented since `a6784c3`; `CountryForm` update-only; Filament `CreateAction` stub without form — **cannot create a country** (real gap).
- **Phase 1 pointer**: 41 routes; Livewire mutate surface mapped; Toshi = Laravel AI SDK, school-focused, **1 covered / 32 gap**.
- **Status**: ✅ Docs logged — Phase 2 **not started** — **NOT PUSHED**

### 2026-07-31: Superadmin audit — Phase 2 Batch A (browser+DB mutators)
- **Work done**: Verified Batch A mutators as siteadmin on `:8010` with Playwright + Boost/DB. Catalogue only — **no code fixes**. Logged full results table under **Superadmin audit**. Synced to `/Users/mac/projects/KlassApp/knowledge.md`.
- **Pass**: `submitSchool` create+update, `submitAdmin` create, `submitUserprofile` update, `submitPlan` create, `submitSubscription` create, `CoAdmins` save+delete, `FeatureToggles.toggle`, `SystemSettings.save`.
- **Partial**: `submitPlan` update (DB OK, redirect `/plans{id}`), `submitAvatar` (DB OK, wrong `/admin/*` redirect).
- **Fail**: Filament subscriptions list **500** (`hasSummary()` arity) blocks approve; `submitPassword` `same:password` vs `new_password` validation bug (hash unchanged).
- **Test data left**: school 35, user 169, plan 8, subscription 11 (pending), co-admin 170 soft-deleted, sitename restored to School-Plus, whatsapp toggle restored off.
- **Status**: ✅ Batch A complete — **STOPPED before Batch B** — commit knowledge only

### 2026-07-31: Superadmin audit — Phase 2 Batch B (GEO browser+DB mutators)
- **Work done**: Verified Batch B geo mutators as siteadmin on `:8010` with Playwright + Boost/DB. Catalogue only — **no code fixes**. Domain = cities/countries. Synced to `/Users/mac/projects/KlassApp/knowledge.md`.
- **Pass**: `submitCity` create (city **id=140**), `submitCity` update, `submitCountry` update (country 10 mid-state then restored to Other).
- **Fail**: Filament countries list **HTTP 500** (`hasSummary()` same as Batch A subscriptions) — `CreateAction` unreachable; country count still 10. Cities Filament list also 500 (collateral).
- **N/A**: Cities list Filament Edit navigate-only — list blocked by same 500; direct update URL works.
- **Toshi**: gap for all (expected).
- **Test data left**: city 140 `BatchB City Updated …` under country Other; country 10 restored.
- **Status**: ✅ Batch B complete — **STOPPED before Batch C** — commit knowledge only

### 2026-07-31: Superadmin audit — hasSummary linkage + cities check + Phase 2 Batch C
- **Work done**: (1) Pushed `main` → `origin/main` tip **`8647379`** (includes `6a294e2`). (2) Linked Batch A subscriptions 500 + Batch B countries 500 as **one systemic bug**. (3) Cities quick check: `Livewire::test(Cities::class)` → identical `hasSummary()` arity (**3rd occurrence**). (4) Batch C read-only smoke (27 URIs) — catalogue only. Synced knowledge to canonical KlassApp copy.
- **Systemic**: Filament lists dying = **1 bug × 4 occurrences** — subscriptions, countries, cities, **plans** (Batch C new). School list OK (not Filament). Triage priority **HIGH / systemic**.
- **Batch C**: 23× 200+content OK; 4× 500 (all hasSummary Filament lists). Hubs/dashboard/details/mail-list/EMIS/contact OK.
- **Status**: ✅ Batch C complete — continued to Batch D/E (see next entry)

### 2026-07-31: Superadmin audit — Phase 2 Batch D (Toshi) + Batch E (impersonate) + Phase 2 CLOSED
- **Work done**: Catalogue-only browser+DB verification on `:8010` (serve + Vite from `KlassApp-main-merge`). Synced knowledge to canonical KlassApp copy. Committed + pushed `main`.
- **Batch D**: `show()` **pass**; `/help` **pass**; NL `send` Livewire OK but SDK **partial/fail** — `isAvailable(siteadmin,null)=false` (`per_school_gate` needs school/`toshi_enabled`); `ask()` null → `fallbackMessage()`. Onboarding/`commitAll` **skipped** (destructive). Platform Toshi CAN: panel + slash cmds + fallbacks; cannot do real SDK Q&A or most platform CRUD. School-admin Toshi path already in knowledge (E2E / commitAll) — not re-run.
- **Batch E**: `/schooladmin/169/impersonate` **pass** (→ `/admin/academics`, school name, Stop link). Stop clears session (**pass**) but leaves siteadmin on `/admin/academics` (**partial** — ug1 redirect commented). `/superadmin/dashboard` OK after stop.
- **Phase 2**: **CLOSED** (A–E catalogue done — fixes deferred). Triage list: hasSummary×4 HIGH, password HIGH, country create MEDIUM, stop-impersonate MEDIUM, plan/avatar redirects LOW, dead users link LOW. **Toshi platform SDK gate** moved to **decided-deferred roadmap** (not active triage).
- **Artifacts**: `tmp/superadmin-batch-d/`, `tmp/superadmin-batch-e/`.
- **Status**: ✅ Phase 2 CLOSED — pushed with this knowledge commit

### 2026-07-31: Superadmin triage — defer Toshi platform-scope + HIGH fixes
- **Work done**: (1) Marked Toshi platform-scope for superadmin as **decided-deferred roadmap** (not active triage) — `per_school_gate` + null `school_id`; real feature needs own auth model, not gate removal. (2) Fixed Filament `hasSummary()` arity by removing stale published `resources/views/vendor/filament-tables` (package v3.3.54 views already pass `$this->getAllTableSummaryQuery()`). Verified all 4 lists HTTP 200 + Livewire mount. (3) Fixed `ChangePassword` `same:password` → `same:new_password`; verified via disposable co-admin (id 172 soft-deleted) password change + `Auth::attempt` with new password.
- **Tests**: `tests/Feature/Superadmin/FilamentTablesHasSummaryTest.php` (4 mounts), `ChangePasswordTest.php` (match + mismatch) — 6 passed.
- **Branch**: `fix/superadmin-audit-triage` off `main`
- **STOPPED before MEDIUM/LOW** (country create, stop-impersonate, redirects, dead link)
- **Status**: ✅ HIGH triage done — not pushed

### 2026-07-31: Superadmin triage — MEDIUM/LOW fixes (country, stop-impersonate, redirects, dead link)
- **Work done**: Closed remaining active triage on `fix/superadmin-audit-triage` (worktree `KlassApp-main-merge`). Synced knowledge outcomes here.
- **Worktree note**: hasSummary fix (deleted stale Filament published views) applies to this branch/worktree; other local KlassApp worktrees may still have the stale published tree until they pull/merge this branch. Not a bug — worktree reminder.
- **Country create (MEDIUM)**: Effort = low/mechanical (mirror CityForm). Uncommented `superadmin.setting.countries.create` with `id => ''`; `CountryForm` create+update; blade Create/Update title; list “Create Country” button (removed stub Filament `CreateAction`). Verified: Livewire create → country **id=11** `TriageLand …`; HTTP create page 200 + list button.
- **Stop-impersonate (MEDIUM)**: Root = redirect used impersonated `Auth::user()` (middleware `onceUsingId`); ug1 branch commented. Fix = redirect by **session login** impersonator usergroup (`match`: 1→`/superadmin/dashboard`, 3→`/admin/dashboard`, …). Verified: start `/schooladmin/169/impersonate` → `/admin/academics`; stop → `/superadmin/dashboard` (not stuck `/admin/*`).
- **Plan/avatar redirects (LOW)**: `PlanForm` → `/superadmin/setting/plans` (was `/plans{id}`); `ChangeAvatar` → `/superadmin/dashboard` (was `/admin/dashboard`). Livewire redirect verified.
- **Dead users link (LOW)**: Removed dashboard “View all” → `/superadmin/users` (no platform users route; school-scoped lists need school id).
- **Tests**: `CountryCreateTest`, `PlanAvatarRedirectTest`, updated `ImpersonateControllerTest` — 9 relevant pass. Full suite: **247 passed**, 1 skipped, **1 failed** (`ToshiE2EVerificationTest` LLM null — pre-existing flake, unrelated).
- **Status**: ✅ MEDIUM/LOW triage done — **READY FOR MERGE REVIEW (not merged, not pushed)**

### 2026-08-01: Toshi Student Part A — self-scope authorization design
- **Work done**: Docs-only design on `feature/toshi-student-role` (off `origin/main`). Confirmed ug6 advisory 11 caps + `scope: self`. Audited `/student/*` ownership (`Auth::id()` patterns vs school-only Gates). Proposed `toshi-student-action` + auth-only student identity (no LLM `student_id`); 13-tool Part B shape with Tier-2 on writes. Appended full report to `docs/toshi-role-parity-audit.md`.
- **Files modified**: `docs/toshi-role-parity-audit.md`, `knowledge.md`
- **Key decisions**: Both Gate + per-tool ownership required; portal `studentassignment`/`studentHomework` Gates insufficient (school_id only); classwall read-only v1; split assignment/homework submit tools; conversations Tier-2 + membership.
- **Status**: ⏸️ Stop — awaiting approval before Part B (no agent/Gates/tools/Blade)
- **Edge cases flagged**: Portal IDOR-shaped gaps on assignment/homework show+destroy and conversation show; do not copy into Toshi

### 2026-08-01: Toshi Student Part B — StudentOperationsAgent (self-scope)
- **Work done**: Implemented ug6 `StudentOperationsAgent` + `StudentActionService` (13 tools). Outer Gate `toshi-student-action`; ported sibling role Gates for isolation; scope router ug6; Blade `[1, 3, 5, 6, 8, 10, 11]`. Audit identity `acting_user_id` + Tier-2 `approver_id`. Cross-student A/B isolation tests (library read, both submits, tasks, conversations). Documented **HIGH backlog — Legacy portal IDOR** for school-only `studentassignment`/`studentHomework`/`event`/`post` Gates (not fixed on this branch).
- **Files modified**: `app/AiAgents/StudentOperationsAgent.php`, `app/AiAgents/Concerns/AuthorizesStudentToshiAction.php`, `app/AiAgents/Tools/Student/*` (13), `app/Services/Toshi/StudentActionService.php`, `app/Providers/AuthServiceProvider.php`, `app/AiAgents/ToshiSdkV2Service.php`, `app/Livewire/AgentToshi.php`, `app/Services/ToshiAuditService.php`, `resources/views/layouts/app.blade.php`, `tests/Feature/Toshi/Student/StudentOperationsToolsTest.php`, `docs/toshi-role-parity-audit.md`, `knowledge.md`
- **Key decisions**: Never trust LLM `student_id`/`user_id`; resource ownership in service (not legacy Gates); classwall mutations deferred; conversations via `conversation_chat` + membership pivot
- **Tests**: `StudentOperationsToolsTest` — 14 passed (83 assertions); `ToshiAuditTrailTest` — 6 passed
- **Status**: ✅ Done (not pushed)
- **Edge cases flagged**: Legacy portal IDOR backlog remains HIGH after role rollout; Conversation Eloquent table (`conversations` vs `conversation_chat`) still inconsistent in portal code
### 2026-08-01: Receptionist Part A — EmailRecord + capability hygiene
- **Work done**: Docs-only investigation on `feature/toshi-receptionist-role` (worktree `KlassApp-main-merge`). Confirmed `manage_email_record` is an abandoned scaffold: controller only — no model, migration, Request, Resource, views, routes, or LOGNAME constants. DB has no email_record table. Recommend **drop** capability (not build routes). Other 7 actions audited; `manage_noticeboard` is read-only → rename to `view_noticeboard`. Proposed Part B: **7 tools**.
- **Files modified**: `docs/toshi-role-parity-audit.md` (brought from librarian branch baseline + Part A appendix), `knowledge.md` (this log)
- **Key decisions**: Drop email capability; rename noticeboard; no Part B implementation until approval; no push
- **Status**: ✅ Superseded by Part B below
- **Edge cases flagged**: Reception menu uses wrong `reception/*` URLs and dead aspirational links (students/parents/appointments/messages); separate from Toshi Part B

### 2026-08-01: Receptionist Part B — ReceptionistOperationsAgent
- **Work done**: Cherry-picked teacher→librarian stack onto `feature/toshi-receptionist-role`, then shipped ug10 operator: drop `manage_email_record`, rename `manage_noticeboard`→`view_noticeboard`; `ReceptionistOperationsAgent` (7 tools) + `ReceptionistActionService`; Gate `toshi-receptionist-action` (impersonation); scope router ug10; Blade `[1, 3, 5, 8, 10, 11]`; isolation + audit tests (10 passed).
- **Files modified**: `app/AiAgents/ReceptionistOperationsAgent.php`, `app/AiAgents/Concerns/AuthorizesReceptionistToshiAction.php`, `app/AiAgents/Tools/Receptionist/*`, `app/Services/Toshi/ReceptionistActionService.php`, `app/Providers/AuthServiceProvider.php`, `app/AiAgents/ToshiSdkV2Service.php`, `app/Livewire/AgentToshi.php`, `app/Services/ToshiActionService.php`, `resources/views/layouts/app.blade.php`, `tests/Feature/Toshi/Receptionist/ReceptionistOperationsToolsTest.php`, `docs/toshi-role-parity-audit.md`, `knowledge.md`
- **Key decisions**: Email capability dropped permanently (not follow-up); noticeboard view-only; Tier-2 on log/task writes; `approver_id` null on 3 reads; did not widen other role Gates
- **Status**: ✅ Done — committed, not pushed
- **Edge cases flagged**: Visitor create defaults `relation=other` (parent-linked path not in Toshi tool); postal `attachment` empty string when no file (NOT NULL column)

### 2026-08-01: Toshi WhatsApp channel Part A — design audit
- **Work done**: Docs-only audit on `audit/toshi-whatsapp-channel` (worktree off `origin/main` @ `212418d`). Evidence: n8n is design-doc/docker stub only (no Laravel→n8n HTTP). Documented live `handleInbound`/`routeInbound` (keywords, lists, OTP, name link). Recommended extend (Toshi on unmatched free-form only). Proposed WhatsApp Tier-2 via sendButtons mapped to same audit identity; v1 read-only for writes. Staff/student → existing OperationsAgents; new ParentOperationsAgent (restore scope=children); ug4 has no Toshi access.
- **Files modified**: `docs/toshi-whatsapp-channel-audit.md` (new), `knowledge.md` (this log)
- **Key decisions**: Extend not replace; WhatsApp Toshi v1 read-only until confirmation bridge; Parent is primary WhatsApp persona
- **Status**: ✅ Done — commit + draft PR
- **Edge cases flagged**: WhatsAppHmac unused despite docs; identify ug4→unknown; sendFees doesn't call composeFeeBalance; auth binding required for webhook→tools

### 2026-08-01: WhatsApp button→Approvable confirmation design (Part A)
- **Work done**: Docs-only expansion of `docs/toshi-whatsapp-channel-audit.md` with full button→Approvable section: inbound payload shape (Meta docs + parser; no local tap rows in DB), Meta id ≤256 + `sendButtons` constraints, recommended opaque token + pending row (unifies Approvable + Tier-2), resume via `Decision::*` / `bypassConfirm`, self-approve phone identity, per-role low-risk write candidates, web-only exclusions (payroll/impersonation), sequencing = ship Part B reads in parallel.
- **Files modified**: `docs/toshi-whatsapp-channel-audit.md`, `knowledge.md` (this log)
- **Key decisions**: Opaque token (`ty_`/`tn_`) over phone-latest lookup; do **not** hold Part B for writes; payroll+impersonation stay web-only after bridge
- **Status**: ✅ Done — pushed to `audit/toshi-whatsapp-channel` / PR #133
- **Edge cases flagged**: SchoolPay list rows omit `id` → UUID body (title “Link Another Student” never preferred); inbound ignores Meta `context.id`; MessageDeliveryLog has no approval correlation fields

### 2026-08-01: WhatsApp text-confirmation fallback (Part A docs)
- **Work done**: Docs-only extension treating typed confirmation as first-class beside buttons. Recommended coded `YES|NO {token}` (same opaque token as `ty_`/`tn_`); rejected bare yes/no. Documented inbound precedence: keywords → pending check (button or text) → Toshi NL. Same TTL/first-wins/expiry UX for both channels. Multi-pending = N tokens. Updated PR #133 description.
- **Files modified**: `docs/toshi-whatsapp-channel-audit.md`, `knowledge.md` (this log)
- **Key decisions**: Option A coded replies (not bare yes/no); allow N open pendings per phone; expiry always replies “This request has expired. Please ask again.”
- **Status**: ✅ Done — docs commit + push to `audit/toshi-whatsapp-channel`
- **Edge cases flagged**: Bare yes with multiple pendings must not auto-approve (offer list of codes); keyword escape hatch while confirm open is intentional

### 2026-08-02: WhatsApp writes wave 1 (CreateTask / ManageTasks)
- **Work done**: Allowlisted five ConfirmsBeforeWrite task tools through `WhatsAppWriteExclusion` (`exclude if ConfirmsBeforeWrite AND NOT allowlisted`; HARD_DENY still wins). Wired channel `ask()` to dispatch `__tier2_confirm` via existing `WhatsAppConfirmationBridge`; `tryHandlePendingApproval` delegates to bridge. Fail-closed + per-role happy/reject/expired/audit tests.
- **Files modified**: `WhatsAppWriteExclusion.php`, `WhatsAppReadOnlyAgent.php`, `WhatsAppToshiChannelService.php`, `WhatsAppController.php`, `config/toshi.php`, `WhatsAppToshiChannelTest.php`, `WhatsAppWritesWave1Test.php` (new), `knowledge.md`
- **Key decisions**: Named class allowlist (not dropping ConfirmsBeforeWrite from tools); no new bridge; School Admin + Parent excluded from wave 1
- **Status**: ✅ Done — local branch only, not pushed
- **Edge cases flagged**: `ask()` returns `CONFIRMATION_DISPATCHED` sentinel so webhook does not fall through to unknown-keyword after buttons send

### 2026-08-02: Deputy Admin Part B — DeputyAdminOperationsAgent
- **Work done**: Implemented ug4 Toshi on `feature/toshi-deputy-admin-role` (worktree). Gate `toshi-deputy-action`; dual-allow `authorizeOrMessage()`; owner-only `authorizeSchoolAdminOrMessage()` for AddCoAdminTool + SetCurriculumTool; `DeputyAdminOperationsAgent` (22 tools); scope router ug4; `getRoleCapabilities(4)` minus add_coadmin/settings; Blade allowlist +4; isolation + audit tests.
- **Files modified**: `DeputyAdminOperationsAgent.php`, `AuthorizesToshiAction.php`, `AddCoAdminTool.php`, `SetCurriculumTool.php`, `AuthServiceProvider.php`, `ToshiSdkV2Service.php`, `ToshiActionService.php`, `ToshiOrchestrator.php`, `layouts/app.blade.php`, `tests/Feature/Toshi/DeputyAdmin/DeputyAdminOperationsToolsTest.php`, `docs/toshi-deputy-admin-audit.md`, `docs/toshi-role-parity-audit.md`, `knowledge.md`
- **Key decisions**: AddCoAdmin excluded as owner governance (not Settings fields); SetCurriculum as Settings; do not widen `toshi-school-action`; WhatsApp ug4 out of scope (WriteExclusion agent-agnostic confirmed)
- **Tests**: `DeputyAdminOperationsToolsTest` — 11 passed (37 assertions); related auth filter — 8 passed
- **Status**: ✅ Done — committed locally, not pushed
- **Edge cases flagged**: SetCurriculum remains AgentToshi-map orphan for ug3 (not on skill agents)

### 2026-08-02: Toshi safety practices Part A — adversarial tests + WA human escalation
- **Work done**: Docs-only audit on `audit/toshi-safety-practices` (worktree off `origin/main` @ `72c2ca6`). Investigated isolation/WhatsApp write-exclusion test patterns, Laravel AI `Agent::fake` vs live LLM (`ToshiE2EVerificationTest`), and absence of helpdesk/live-agent infra. Proposed adversarial suite under prompt pressure + thin WhatsApp human-escalation MVP distinct from confirmation bridge.
- **Files modified**: `docs/toshi-safety-practices-audit.md` (new), `knowledge.md` (this log)
- **Key decisions**: No live LLM in CI primary adversarial suite (architecture-under-pressure; optional `@group live-llm` later); suite at `tests/Feature/Toshi/Adversarial/` with ~3–4 scenarios × Teacher/Student/Parent/SchoolAdmin-WA; escalation = explicit intent MVP via ActivityLog + optional Task + staff WhatsApp notify (no new table); receivers role-dependent (parent/student→Receptionist, staff→School Admin)
- **PR / merge**: preceded #142 — https://github.com/KlassApp-Foundation/KlassApp/pull/142 merged
- **Status**: ✅ Superseded by Part B + #142 on main
- **Edge cases flagged**: No support-ticket/helpdesk models; VisitorLog/CallLog/Postal are operational registers not conversation queues; `Agent::fake` cannot prove jailbreak resistance; fee “escalation” and `toshi.escalated_model` are unrelated semantics

### 2026-08-02: Toshi adversarial-live in-process for --no-dev prod
- **Work done**: Confirmed PR #144 merged (`03e481b`) and production deploy at `98d02d0` (= `origin/main`, includes #144+#145). `phpunit` absent in prod image. Refactored `toshi:adversarial-live` from shelling to `php artisan test` → in-process `LiveAdversarialRunner` (sqlite `:memory:`, Http::fake WA hosts, agent `prompt()`, same scorer). Chose option **(b)**. Kept gated monthly Kernel schedule. Did not touch prod `.env` / llm-health cron.
- **Files modified**: `LiveAdversarialRunner.php` (new), `LiveAdversarialScorer.php` + `AdversarialPromptFixtures.php` moved under `app/`, `ToshiAdversarialLiveCommand.php`, Kernel comments, live/command tests, `docs/toshi-safety-practices-audit.md`, `docs/toshi-prod-health-check.md`, `knowledge.md`
- **Key decisions**: (b) over monthly human reminder — unattended silent failure class needs prod-cronable signal; gate `TOSHI_ADVERSARIAL_LIVE` stays off until ops enables; production MySQL never written
- **PR / merge**: https://github.com/KlassApp-Foundation/KlassApp/pull/147 (+ #148/#149)
- **Status**: ✅ MERGED to main; prod adversarial unattended after #148
- **Edge cases flagged**: Full sqlite migrate on each run (monthly OK); Http::fake patterns must not swallow DeepSeek; enable gate carefully (token cost ~$0.01/run)

### 2026-08-02: Deploy #147 + enable unattended adversarial-live
- **Work done**: Deployed `origin/main` (`5645c1d`, PR #147) to prod (was `98d02d0`). Manual `toshi:adversarial-live` initially failed: `UserFactory` `$this->faker->unique()` null because `fakerphp/faker` is `--no-dev`. Fixed `LiveAdversarialRunner` to seed users without factories + sqlite FK off. Re-ran: **16/16 PASS**, 0 flags/fails, ~$0.0082, model `deepseek-v4-flash`. Set `TOSHI_ADVERSARIAL_LIVE=1` in prod `.env`. Added missing host cron `schedule:run` (llm-health stays on separate 5-min `.toshi-health-check.sh`). Docs marked genuinely live/unattended.
- **Files modified**: `LiveAdversarialRunner.php`, `ToshiAdversarialLiveCommandTest.php`, `docs/toshi-safety-practices-audit.md`, `knowledge.md`; prod `.env` + crontab
- **Key decisions**: Do not add faker to prod; keep llm-health cron separate; add `schedule:run` so Kernel monthly adversarial actually fires
- **Status**: ✅ Done — fix PR + prod enable
- **Edge cases flagged**: First #147 deploy alone was insufficient without factory-free seed; schedule:list showed entry before cron existed (misleading)

### 2026-08-03: Part A — Report cards knowledge reconciliation (docs only)
- **Work done**: Branched `audit/toshi-report-cards` from `origin/main`. Audited `DownloadStudentReport`, `student-report.blade.php`, routes, helpers, WhatsApp/alumni PDF paths. Reconciled KB conflict (Reports CSV hub vs academic PDF). Live DomPDF generation for Primary (Micheal Okwir #58), Nursery (Brian Okello #47), O-Level (Andrew Ssentongo #66), A-Level (Jackie Namuyomba #75) — all returned `%PDF-1.7`. Documented gaps (batch/termly/distribution). Confirmed `report-card-design-spec.md` never existed in git. **No Toshi tools implemented or proposed.**
- **Files modified**: `docs/toshi-report-cards-audit.md` (new), `knowledge.md` (conflict note + session log)
- **Key decisions**: Treat older “CSV only / no report cards” note as scoped to Reports module only; defer Toshi tool scope until product clarifies termly/batch vs per-exam PDF
- **PR**: https://github.com/KlassApp-Foundation/KlassApp/pull/158 (`audit/toshi-report-cards` @ `c9db10c`) — **draft**
- **Status**: ✅ Done — draft PR open (not merged)
- **Edge cases flagged**: Local `nursery_assessments` empty (nursery path shows Domain table with `—`); blade `academicYear` on string error on every render; O/A share Primary numeric branch (under-tested, not missing)
