# KlassApp External Comparison Research — Reports · Scheduling · UI/UX (living doc)

**Owner:** Mucu (KlassApp) · **Type:** comparison / reference research (no implementation) · **Living document** — append/update here, do not fork a second doc.

**Round history:** R1 — 2026-09-21 (reports, scheduling, first madewithlaravel.com pass: Administrator / UI Components / Template). **R2 — 2026-09-21** (theme: design/visual patterns, **data rendering** — tables/charts/KPI, **admin roles/controls** — permissions/settings/role dashboards; **all 13** madewithlaravel.com categories; deeper license diligence on every new find).

**Method note (what is actually comparable):** Academico is **Filament v5 / Laravel 12** and madewithlaravel.com hosts projects across many stacks. KlassApp is **Laravel 12 + Blade + Livewire 3.4 + Tailwind CSS v4 + Vue 3.5 (compat) + Alpine + MySQL**. Filament *Resources/Pages/Widgets* and Bootstrap/Vue-React-only projects **do not transfer UI code**; what transfers is the **service / command / data-model / component-pattern layer**. This doc compares on those terms and flags UI code as "patterns only" wherever a stack mismatch exists. *(Note: KlassApp's declared "Tailwind 1.4 / Laravel 10" is stale — verified actuals are Laravel 12 / Livewire 3.4 / Tailwind v4 / Vue 3.5.)*

---

## 0. TL;DR — opportunity areas ranked (R1 + R2 merged)

| # | Area | Source (license) | Verdict | Effort | Real follow-up decision? |
|---|------|------------------|---------|--------|--------------------------|
| A1 | **Materialized report/KPI snapshot + scheduled rebuild** | Academico `cached_reports` (MIT-declared, no file) | **Strong** | Med | **Yes** — schema + cadence |
| A2 | **One `StatService` (polymorphic Term\|Year\|DateRange)** | Academico `StatService` | Strong | Med | Yes |
| A3 | **Cohort retention + new-student analytics** | Academico `Period` | Medium | Med | Yes (metric decision) |
| A4 | **Precompute position/aggregate maps per exam** | Academico + KlassApp's own N-query loops | Strong (perf) | Low–Med | Yes |
| A5 | **Config-gated sections + report error trait** | Academico config + `ReportsErrors` | Utility | Low | Fold into A1 |
| A6 | **Skill/competency evaluation model** | Academico Skills/`EvaluationType` | Strategic | High | Yes (Uganda progressive assessment) |
| B1 | **"Preset week" timetable templates** | Academico `schedule_presets` | Medium | Low–Med | Yes |
| B2 | **Timetable↔calendar drift audit + leave-aware occurrences** | Academico `ResyncCourseTimes`/`CourseTime` | Medium | Low–Med | Yes |
| B3 | **Config-driven "current/default period" resolution** | Academico `Period::get_default_period()` | Low–Med | Low | Maybe |
| C1 | **Server-side data-table component** | livewire-powergrid / laravel-livewire-tables / Okipa laravel-table (MIT) | Medium | Med | Yes |
| C2 | **Modern chart layer (replace Chart.js v2)** | asantibanez/livewire-charts · lavacharts (MIT) | Medium | Med | Yes |
| C3 | **Sparkline / trend KPI data** | Flowframe/laravel-trend (MIT) | Medium | Low | Yes |
| C4 | **Dashboard widget registry + cache** | imanghafoori1/laravel-widgetize (MIT) | Medium | Low–Med | Yes |
| C5 | **Stackable toasts** | usernotnull/tall-toasts · toaster-magic (MIT) | Medium | Low | Yes |
| D1 | **Permission/role matrix UI** | GeneaLabs/laravel-governor · jeremykenedy/laravel-roles (MIT); filament-shield (ref) | Medium | Med | Yes |
| D2 | **Grouped/tabbed settings shell** | laravel-tall-preset · guacpanel (MIT) | Medium | Low–Med | Yes |
| D3 | **Role-aware modular dashboard patterns** | laradashboard (MIT, Tailwind v4 + Livewire) | Strong (reference) | Med | Yes |
| D4 | **Unified approval/state machine** | HPWebdeveloper/laravel-stateflow · pixelworxio/livewire-workflows (MIT) | Medium | Med | Yes |
| E1 | **Sidebar a11y + command palette** | wire-elements/spotlight (MIT) + KlassApp sidebar | Medium | Low–Med | Yes |
| E2 | **Icon-set standardization** | blade-ui-kit/blade-icons (MIT) | Low–Med | Low | Yes |
| E3 | **Feature flags for config-gated surfaces** | ylsideas/feature-flags (MIT) | Utility | Low | Yes |
| E4 | **Scheduler/queue observability surface** | always-open/laravel-totem (MIT) | Utility | Low | Maybe |

**Do NOT import (license):** Cruip **Mosaic/Laravel** (GPL + *no redistribute*); **Relaticle** (AGPL-3.0); any repo with **no LICENSE file** — Academico (MIT only in `composer.json`), Turbine UI, artisan-gui, kaido-kit, larament, Graindashboard, laravel-mazer, Miaababikir preset. Reference patterns only; get written permission before copying any code/CSS.

---

## 1. Licensing master table (verified live this session)

Verified with `gh api repos/<owner>/<repo>` (spdx) **and** `gh api .../license` / reading the actual license file. **A license declared only in `composer.json` is not the same as a repo license GitHub can detect** — called out explicitly. Keep KlassApp's `SPDX-License-Identifier: MIT` headers intact; copied code/CSS must retain upstream `LICENSE` + copyright.

### 1a. Round-1 entries (unchanged)

| Project | Repo | License (verified) | Guidance |
|---|---|---|---|
| Academico | `academico-sis/academico` | `composer.json` MIT; **no LICENSE file**; GH none | Reference only; get written MIT confirmation before adapting |
| TailAdmin Laravel | `sinan-aydogan/tailadmin-laravel` | MIT | Safe; Vue/Inertia → patterns only |
| TallStackUI | `tallstackui/tallstackui` | MIT | Safe (Composer) |
| Wire UI | `wireui/wireui` | MIT | Safe |
| Mary UI | `robsontenorio/mary` | MIT (`license.md`, verbatim MIT) | Safe; needs daisyUI/Tailwind v3 → verify v4 |
| TallCraftUI | `developermithu/tallcraftui` | MIT | Safe |
| FlexiWind | `unoforge/flexiwind` | MIT | Safe; young → pin |
| Dash UI | `combindma/dash-ui` | MIT | Safe |
| Luvi UI | `luvi-ui/laravel-luvi` | MIT | Safe |
| BladeWind UI | `bladewindui/bladewindui` | MIT | Safe (old `mkocansey/bladewind` forwards here) |
| Tall Forms | `tanthammar/tall-forms` | MIT | Safe |
| PowerGrid | `Power-Components/livewire-powergrid` | MIT | Safe |
| Livewire Tables | `rappasoft/laravel-livewire-tables` | MIT | Safe |
| Toaster Magic | `devrabiul/laravel-toaster-magic` | MIT | Safe |
| Livewire Toaster | `masmerise/livewire-toaster` | MIT | Safe |
| Wire Elements Modal | `wire-elements/modal` | MIT | Safe |
| Laratrust | `santigarcor/laratrust` | MIT | Already used by KlassApp |
| Tablar | `takielias/tablar` | MIT | Safe; Bootstrap/Tabler → patterns |
| Sneat (free) | `themeselection/sneat-bootstrap-html-laravel-admin-template-free` | MIT | Safe; Bootstrap → patterns |
| Volt Laravel | `themesberg/volt-laravel-dashboard` | MIT | Safe; Bootstrap |
| Admin One | `vikdiesel/admin-one-laravel-dashboard` | MIT | Stale (2022); Bulma/Vue → patterns |
| Laravel Impersonate | `404labfr/laravel-impersonate` | `composer.json` MIT; no LICENSE file | Safe in practice; doc gap |
| Laravel Roles | `jeremykenedy/laravel-roles` | MIT | Safe |
| Craftable | `BRACKETS-by-TRIAD/craftable` | MIT | Patterns (Bootstrap) |
| Okipa Laravel Table | `Okipa/laravel-table` | MIT | Safe |
| Laravel Datagrid | `wdev-rs/laravel-datagrid` | MIT | Safe (Grid.js) |
| **Cruip Mosaic Lite (Laravel)** | `cruip/laravel-tailwindcss-admin-dashboard-template` | **GPL + no-redistribute**; no LICENSE file | ⚠️ **Avoid import** |
| Graindashboard | `maxkostinevich/Graindashboard` | No LICENSE file (README "MIT") | ⚠️ Patterns only; stale |
| Laravel Mazer | `zuramai/laravel-mazer` | `composer.json` MIT; no LICENSE file | Patterns only; stale |
| Tailwind Dashboard Preset | `Miaababikir/laravel-tailwind-css-dashboard-preset` | `composer.json` MIT; no LICENSE file | Patterns only; stale |

### 1b. Round-2 entries (new)

| Project (category) | Repo | License (verified) | Stack | Guidance |
|---|---|---|---|---|
| **Lara Dashboard** (app) | `laradashboard/laradashboard` | **MIT** | Laravel 13 + **Livewire 3** + **Tailwind v4** + spatie/permission + modules + MCP | **Adoptable reference** — closest stack match for role-aware modular dashboards |
| Livewire Charts (ui-components) | `asantibanez/livewire-charts` | MIT | Livewire, ApexCharts | Safe |
| Live Google Charts (ui-components) | `mvnrsa/livewire-live-google-charts` | MIT | Livewire 3, Google Charts | Safe (small) |
| Lavacharts (plugins) | `kevinkhill/lavacharts` | **MIT** (LICENSE, "Other" auto-detect) | PHP Google Charts wrapper | Safe |
| Laravel Trend (utility) | `Flowframe/laravel-trend` | MIT | Eloquent | Safe — KPI sparklines |
| Laravel Widgetize (utility) | `imanghafoori1/laravel-widgetize` | MIT | Blade partials | Safe — widget + cache registry |
| Tall Toasts (utility) | `usernotnull/tall-toasts` | MIT | TALL | Safe |
| Laravel Filterable (utility) | `thejano/laravel-filterable` | MIT | Eloquent | Safe |
| Laravel Totem (utility) | `always-open/laravel-totem` | MIT | Vue + Laravel | Safe — schedule dashboard |
| Blade Icons (utility) | `driesvints/blade-icons` | MIT | Blade | Safe — icon sets |
| Feature Flags (utility) | `ylsideas/feature-flags` | MIT | Alpha | Safe |
| Laravel Governor (utility) | `mike-bronner/laravel-governor` | MIT | spatie/laravel-permission | Safe — role/permission **UI** |
| Laravel Auditable (utility) | `yajra/laravel-auditable` | MIT | Eloquent | Safe |
| Laravel Stateflow (utility) | `HPWebdeveloper/laravel-stateflow` | MIT | Eloquent state machine | Safe |
| Livewire Workflows (boilerplate) | `pixelworxio/livewire-workflows` | MIT | Livewire | Safe — multi-step flows/approvals |
| Laravel TALL preset (boilerplate) | `laravel-frontend-presets/tall` | MIT | TALL | Safe; legacy preset (Laravel dropped `presets`) → reference |
| Guacpanel Tailwind (boilerplate) | `otatechie/guacpanel-tailwind` | MIT | Inertia + Tailwind + spatie/permission | Patterns (Inertia) |
| Saucebase (boilerplate) | `saucebase-dev/saucebase` | MIT | TS | Patterns |
| Livewire Calendar (ui-components) | `asantibanez/livewire-calendar` | MIT | Livewire | Safe |
| Filament Shield (plugins) | `bezhanSalleh/filament-shield` | MIT | Filament + spatie/permission | Filament-only → reference for matrix UI |
| Filament Apex Charts (plugins) | `leandrocfe/filament-apex-charts` | MIT | Filament | Reference |
| Filament Menu Builder (plugins) | `Biostate/filament-menu-builder` | MIT | Filament | Reference |
| Filament Page w/ Sidebar (plugins) | `aymanalhattami/filament-page-with-sidebar` | MIT | Filament | Reference |
| Laravel Datatables (plugins) | `yajra/laravel-datatables` | MIT | jQuery DataTables | jQuery → likely skip; patterns |
| Tomato Admin (boilerplate) | `tomatophp/tomato-admin` | MIT | Filament/Splade | Reference |
| Cagilo (ui-components) | `cagilo/cagilo` | MIT | Blade components (Laravel 12/13, orchid/blade-icons) | Safe |
| Wireflow / artisanflow.dev (ui-components) | `getartisanflow/wireflow` | MIT | Livewire ^3/4 + Alpine | Safe — flow diagrams |
| z-song Laravel Admin (administrator) | `z-song/laravel-admin` | MIT | Bootstrap admin framework | Patterns only |
| Hej (boilerplate) | `renoki-co/hej` | **Apache-2.0** | Socialite | Auth scaffold |
| **Relaticle** (boilerplate) | `Relaticle/relaticle` | **AGPL-3.0** | Filament | ⚠️ **Avoid import** (strong copyleft) |
| Turbine UI (ui-components) | `brandymedia/turbine-ui-core` | **No LICENSE file** | Tailwind Blade | ⚠️ Patterns only |
| artisan-gui (plugins) | `infureal/artisan-gui` | **No LICENSE file**; stale (2024) | Vue | ⚠️ Patterns only |
| Kaido Kit (boilerplate) | `siubie/kaido-kit` | **No LICENSE file** | Filament | ⚠️ Patterns only |
| Larament (boilerplate) | `CodeWithDennis/larament` | **No LICENSE file** | Filament | ⚠️ Patterns only |

> **Licensing rules going forward:** (1) verify the **actual license file**, not a `composer.json` claim; (2) treat GPL/AGPL and "no-redistribute" as **avoid**; (3) "no LICENSE file" = **reference/patterns only** unless clarified in writing; (4) MIT/Apache are safe to depend on via Composer — keep the notice.

---

## 2. Baseline — what KlassApp actually does today (verified in repo)

### 2.1 Reports
- **Report cards (PDF):** single pipeline `App\Services\StudentReportCardService::generatePdf()` (the fix for "fixes reaching only some surfaces"). `resolveExam()` → latest exam for `(school_id, section_id, standard_id)` with `examType.contributes_to_report_total = 1`. Templates registered `formal` + `warm`; `modern.blade.php` exists on disk but is **not registered**. Per-school choice via `schools.report_template`.
- **Ranking/aggregation:** position map, stream position, division (1/U), aggregate points — computed inline; per-student loops issue **repeated `Marks::where(...)->sum()` queries**.
- **Bulk:** `report_generations` table + `GenerateClassReportsJob` (merged PDF / ZIP).
- **Analytics:** `ReportCardsController::computeEotKpis()` — **live raw SQL on every request** (called by `DashboardController.php:136` and `ReportCardsController.php:64`). **No cache, no snapshot table.**
- **Exports:** on-demand CSV (`league/csv`) + Excel (`maatwebsite/excel`) in `ReportsController`.
- **Delivery:** WhatsApp (`WhatsAppReportCardDeliveryService`, `PruneWhatsAppReportFiles`).

### 2.2 Scheduling / timetable
- `timetable_slots` (school, year, term, section, subject, teacher, `day_of_week`, start/end, room) + unique constraint. `TimetableSlot` has **real conflict detection** (`detectConflict()`: teacher + section/room overlap; `checkTeacherLink()` warning).
- `syncCalendarEvent()` = delete + rebuild **one weekly-recurring** `Events` row per slot. **Does not subtract teacher leave** (`TeacherLeaveApplication` exists).
- `TimetableSlotController` (index/CRUD/`teacherWeekly()`); toggle `config/gtimetable.php`.
- **KlassApp's timetable is ahead of Academico's** (Academico has none).

### 2.3 Dashboard UX / design system / roles / settings / charts
- **Design system:** `public/css/dashboard-refresh.css` (~3,692 lines) with a `ds-*` contract (`ds-btn`, `ds-card`, `ds-badge`, `ds-form-*`, `ds-kpi-card`, `ds-empty-state`, `ds-dot`, `ds-grid-marks`…); anonymous Blade components `x-button`, `x-card`, `x-badge`, `x-table`, `x-form-group`, `x-ds-kpi-card`, `x-brand.*`.
- **Shells:** `dashboard-shell--admin` (+ role variants). Per-role sidebars: admin, superadmin, teacher, parent, student, accountant, library, reception, stock, alumni (share `layouts.admin.menu`).
- **Sidebar:** collapsible groups + `localStorage` persistence + capability-gated hover-preview (`(hover:hover) and (pointer:fine)`) + `x-collapse`. **Active state uses `Request()->segment('2')`** (brittle URL-shape assumption).
- **KPI card:** `x-ds-kpi-card` — fixed inline-SVG icon set (users/classes/exam/whatsapp/library/bell/money/check), value + label, optional link, 5 colors.
- **Charts:** **Chart.js v2** used directly in `resources/views/admin/dashboard/dashboard.blade.php` (line/bar/trend). `highcharts` also in `package.json`.
- **Tables:** Vue `vue-good-table-next` (e.g., book lending, telephone directory) + server-side lists.
- **Settings:** flat `layouts.partials.settings-nav` list (All / Site branding / Exam types / Integrations / SEO / Maintenance) with `is-current` highlighting.
- **Approvals:** `Approval`, `HomeworkApproval`, `AssignmentApproval`, `LessonPlanApproval`, `HasApprovals` trait; superadmin `Toshi/PlatformApprovalGate` Livewire.
- **Livewire:** already used for `Admin`, `Superadmin`, `ManualOnboardingWizard`, `ClassRoster`, `Conversations`, `AgentToshi`.
- **Roles:** Laratrust (superadmin/schooladmin/teacher/parent/student/librarian/accountant…).

---

## 3. Academico deep-dive — patterns that matter (not the Filament UI)

Academico = language/small-institution SIS (Laravel 12 + Filament v5, Backpack→Filament rewrite, schema unchanged; 401★). Full detail preserved from R1:

- **Materialized reports:** `cached_reports` table + `academico:build-report` (truncate+rebuild, config-anchored `first_period`); `ReportService::buildInternalCoursesReport()` chunks by year + `gc_collect_cycles()`; `ReportsErrors` trait wraps failures with context.
- **Polymorphic `StatService(external, Period|Year|DateRange, ?Partner)`:** one stats source for widgets + reports (`studentsCount`, `enrollmentsCount`, `paid/pending`, `taught/soldHours`, `newStudents`, `partnershipsCount`).
- **Cohort analytics:** `Period::getAcquisitionRateAttribute()` (retention P-1→P), `newStudents()` (never-enrolled-before), `takings`, taught-vs-sold hours, all gated by `config('academico.include_takings_in_reports')`.
- **Period lifecycle:** `Period::get_default_period()` / `get_enrollments_period()` (rollover heuristic) + global ordering scope; `PeriodSelection` trait.
- **Attendance completeness:** `getCoursesWithPendingAttendanceAttribute()` + `HandlesAttendance::remindPendingAttendance()` (email nudge, config-gated).
- **Scheduling:** **no timetable module**; `Period` + `CourseTime (course_id, day, start, end)` → **materialized per-session `Event` rows**, **skipping teacher-leave dates**; delete+rebuild on change; `ResyncCourseTimes` repair; `schedule_presets` JSON.
- **Skills/competency:** `Skill`/`SkillType`/`SkillScale`/`SkillEvaluation` + `EvaluationType` presets alongside numeric `Grade`/`GradeType`/`GradeTypeCategory`.

---

## 4. Opportunity Area A — Reports

(Largely as in R1; restated compactly.)

- **A1 ★ Materialized snapshot + scheduled rebuild.** Add a `report_snapshots`/`kpi_snapshots` table keyed by `(school_id, academic_term_id, scope, metric)` populated by `computeEotKpis` (write-through) and refreshed by a queued command on the **existing** scheduler (`scheduler-heartbeat`); dashboard + report-cards read the snapshot. Biggest, lowest-drama win. *Decision: schema + KPI set + cadence.*
- **A2 One `SchoolStatService($schoolId, Term|Year|DateRange)`.** Consolidate `StudentReportHelperService`, `MarksReportService`, `computeEotKpis`, `DashboardController`, exports. *Decision: yes/no + metric scope.*
- **A3 Cohort retention / new-vs-returning KPIs.** Reuse enrollment data; a `schooladmin`/`superadmin` KPI card. *Decision: product call.*
- **A4 Precompute position/aggregate maps once per exam** (one grouped `SUM(marks) GROUP BY student_id`) instead of per-student loops. Low risk, bundle into report-card perf backlog.
- **A5 Config-gated report sections + shared `ReportsErrors`-style trait** for export controllers. Fold into A1.
- **A6 Skill/competency model** — strategic; ties to Uganda progressive assessment. *Decision: roadmap.*

---

## 5. Opportunity Area B — Scheduling / timetable

**KlassApp's timetable is already more capable than Academico's.** Transferable, narrow:

- **B1 "Preset week" templates.** A reusable week (e.g., "Standard P.1–P.7 week") that bulk-seeds `timetable_slots` **through the existing `detectConflict()` validation**. Removes the biggest manual cost. *Decision: yes/no + preset UX.*
- **B2 Drift audit + leave-aware occurrences.** Add a `timetable:verify` command (every slot ↔ exactly one event; report orphans). If per-session features (attendance/substitution) are ever wanted, generate **materialized occurrences** that subtract `TeacherLeaveApplication` dates (Academico's `CourseTime::createEvents` pattern). *Decision: is per-session granularity on the roadmap?*
- **B3 Config-driven current/default period resolution** — minor; only if term/enrolment defaults drift.

---

## 6. Opportunity Area C — Data rendering (tables · charts · KPI displays)

**Focus of R2.** KlassApp's current mix: Chart.js **v2** (old) for dashboard charts, Vue `vue-good-table-next` for a few grids, server-rendered lists elsewhere, and a fixed-icon `x-ds-kpi-card`.

### C1. Tables / data grids
| Option | License | Pattern worth taking |
|---|---|---|
| `Power-Components/livewire-powergrid` | MIT | Livewire 3 server-side sort/filter/paginate; **column visibility**, saved filters, bulk-action bar, row detail, CSV export, persisted state |
| `rappasoft/laravel-livewire-tables` | MIT | Similar; strong filter/column API |
| `Okipa/laravel-table` | MIT | Pure server-side HTML tables from Eloquent — closest to KlassApp's Blade style (no JS grid dependency) |
| `yajra/laravel-datatables` | MIT | jQuery-centric → likely **skip** (KlassApp is moving off jQuery-era plugins) |
**KlassApp tie-in:** `knowledge.md` flags **`<x-table>`'s `striped`/`hover` dead props** — the table component is an active gap. Standardize on one server-side table pattern (Livewire or Okipa-style Blade) with: sortable headers, empty state (reuse `ds-empty-state`), sticky header, column visibility, and persisted page/filter state.

### C2. Charts
| Option | License | Pattern worth taking |
|---|---|---|
| `asantibanez/livewire-charts` | MIT | Blade components over **ApexCharts** (line/bar/pie/donut/radar), `wire:model`-driven, no hand-written JS — replaces the raw `new Chart(...)` blocks |
| `kevinkhill/lavacharts` | MIT | Server-side Google Charts wrapper (data defined in PHP) |
| `mvnrsa/livewire-live-google-charts` | MIT | **Auto-refresh/poll** Google Charts — good for live KPIs |
**KlassApp tie-in:** dashboard charts are Chart.js **v2** (2019-era). Moving to an ApexCharts component (or Chart.js v4 with a Blade wrapper) gives consistent theming via `ds-*` tokens, responsive/accessible charts, and sparklines in KPI cards.

### C3. KPI / trend data
| Option | License | Pattern worth taking |
|---|---|---|
| `Flowframe/laravel-trend` | MIT | Compute **trend series** (per-day/week/month counts/sums) for sparklines + "up/down vs last term" deltas on KPI cards |
| `imanghafoori1/laravel-widgetize` | MIT | **Widgetize** partials = a per-widget registry with `Cacheable`/`WhenNotCached` decorators — a clean home for dashboard widgets backed by the A1 snapshot |
**KlassApp tie-in:** `x-ds-kpi-card` shows a static value; add an optional **trend/sparkline slot** + delta chip, fed by a snapshot/trend service.

### C4. Toast / notification surface
`usernotnull/tall-toasts` (MIT) · `devrabiul/laravel-toaster-magic` (MIT) · `masmerise/livewire-toaster` (MIT) — stackable, dismissible, queue-aware toasts that fit next to `ds-alert`. **KlassApp tie-in:** the WhatsApp/report flows already emit events; toasts give immediate feedback without full-page `successmessage` flashes.

### C5. Calendar
`asantibanez/livewire-calendar` (MIT) — Blade/Livewire month grid. Reference for a unified timetable/events view alongside the existing `@fullcalendar/vue` usage.

---

## 7. Opportunity Area D — Admin roles / controls (permissions · settings · role dashboards)

**Focus of R2.**

### D1. Permission / role matrix UI
KlassApp uses **Laratrust**. Today there is no first-class permission-matrix screen (the superadmin audit in `knowledge.md` shows Filament-based approve surfaces that 500'd). Options:
| Option | License | Pattern |
|---|---|---|
| `mike-bronner/laravel-governor` (GeneaLabs) | MIT | **UI for assigning roles/permissions to users** on top of `spatie/laravel-permission` — the *matrix screen* pattern (role × permission grid, per-user role assignment) |
| `jeremykenedy/laravel-roles` | MIT | Roles/permissions GUI + user assignment |
| `bezhanSalleh/filament-shield` | MIT | Filament-only, but its **resource→permission generation + matrix UI** is the cleanest reference model |
**KlassApp tie-in:** author a Blade/Livewire **role × permission matrix** for superadmin/schooladmin consistent with `ds-*`, rather than adopting a package wholesale (Laratrust vs spatie-permission differ). *Decision: build vs adapt.*

### D2. Settings panels
KlassApp's settings nav is a **flat 6-link list** (`layouts.partials.settings-nav`) with simple `is-current`.
- `laravel-frontend-presets/tall` (MIT) and `otatechie/guacpanel-tailwind` (MIT) show **grouped/tabbed settings shells** with per-section save, dirty-state indication, and inline validation.
- **KlassApp tie-in:** convert settings to a grouped shell (School / Academics / Integrations / Branding / Maintenance) with per-section save + `ds-form-*` validation states and a sticky section sidebar — reusing the existing `settings-nav` concept, not replacing it.

### D3. Role-based / modular dashboards
- **`laradashboard/laradashboard` (MIT)** — *closest stack match in the whole survey*: **Laravel 13 + Livewire 3 + Tailwind v4**, modular (`nwidart/laravel-modules` + `mhmiton/laravel-modules-livewire`), `spatie/laravel-permission`, charts + user-activity, media library, MCP. Reference for: **module-scoped, permission-aware dashboards**, a **user-activity panel**, and an **inline-AI content tool** pattern (relevant to Toshi).
- `kaido-kit`, `larament`, `tomato-admin` (Filament) → reference for role-panel structure only (no LICENSE for kaido-kit/larament).
- **KlassApp tie-in:** KlassApp already has per-role shells; the transferable idea is **per-role widget sets driven by permission gates** (each role's dashboard composes widgets it's allowed to see) rather than fixed templates.

### D4. Approvals / state machines
KlassApp has `Approval`, `HomeworkApproval`, `AssignmentApproval`, `LessonPlanApproval`, `HasApprovals`.
- `HPWebdeveloper/laravel-stateflow` (MIT) — explicit **state machine** for Eloquent (transitions + guards).
- `pixelworxio/livewire-workflows` (MIT) — **multi-step workflows** in Livewire (fits the existing wizard/approval Livewire components).
- **KlassApp tie-in:** unify the several approval models' transitions behind one state machine to remove duplicated approve/reject logic (the same class of bug as the report-card "logic in multiple places" issue).

---

## 8. Opportunity Area E — Design / visual patterns

### E1. Sidebar & navigation
No dedicated "sidebar" project exists on madewithlaravel.com (verified R1). KlassApp's sidebar is already advanced. Targeted adds:
- **`wire-elements/spotlight`** (MIT) — ⌘K command palette (Alfred/Spotlight-like) for quick admin navigation; complements the sidebar.
- **Robust active state:** replace `Request()->segment('2')` with named-route matching (`request()->routeIs(...)`).
- **A11y:** focus trap for the mobile off-canvas, `aria-expanded`/`aria-current`, visible focus rings.
- **Respect the shell math:** Toshi is `position: fixed` at the 380px sidebar assumption (`knowledge.md`) — any width/rail change must update the shell pixel math.

### E2. Icons
`blade-ui-kit/blade-icons` (MIT; repo now `driesvints/blade-icons`) + curated sets — standardize beyond the hand-built `<x-icons.sidebar>` set, with a single source of truth.

### E3. Feature flags
`ylsideas/feature-flags` (MIT) — small DB/array flag layer; useful for config-gated report sections / staged rollouts (mirrors Academico's `config('…_enabled')` toggles).

### E4. Scheduler/queue observability
`always-open/laravel-totem` (MIT) — dashboard for the Laravel schedule. KlassApp has an active scheduler; a small observability surface (heartbeat age, last run, failures) fits the ops posture.

### E5. Component libraries (Blade + Tailwind + Alpine/Livewire)
`tallstackui` (MIT), `wireui/wireui` (MIT), `mary` (MIT, daisyUI→verify v4), `tallcraftui` (MIT), `dashui` (MIT), `luvi-ui` (MIT), `flexiwind` (MIT, Tailwind v4), `cagilo` (MIT), `turbine-ui` (**no license → reference**). Use one as a **ds-* gap-filler**, verify Tailwind **v4**, or vendor selected components.

### E6. Toasts / modals
`usernotnull/tall-toasts` (MIT), `wire-elements/modal` (MIT) — see C4.

---

## 9. Full category coverage (all 13) — what was scanned and what mattered

Every category listing was enumerated via each page's JSON-LD `ItemList`. Deep-dive applied only to stack-relevant candidates (Laravel/Blade/Tailwind/Livewire/Alpine); pure-API/ORM/ops and non-UI categories were scanned and triaged as "not applicable to the UI/UX focus".

| Category | Listed | Stack-relevant picks surfaced | Verdict |
|---|---|---|---|
| **API** | 82 | Few UI-relevant; mostly API tooling (OpenAPI, auth) | Out of focus for UI; nothing adoptable for dashboard/UX work |
| **Administrator** | 88 | **laratrust** (MIT, in-use), **jeremykenedy/laravel-roles** (MIT), **z-song/laravel-admin** (MIT, Bootstrap), **laravel-governor** (utility/MIT), **feastable** role/dashboard templates | Role/permission **matrix UI** + role-aware shell patterns (D1/D3) |
| **App** | 160 | **laradashboard/laradashboard** (MIT, L13+Livewire+TWv4), **academico**, **schedpilot/timegrid** (scheduling apps), **simplestats** | **laradashboard** is the standout reference (D3) |
| **Boilerplate** | 67 | **laravel-frontend-presets/tall** (MIT), **guacpanel-tailwind** (MIT), **pixelworxio/livewire-workflows** (MIT), **saucebase** (MIT), **wave** (MIT), `kaido-kit`/`larament`/**Relaticle(AGPL)** | TALL starters + workflow/approval patterns (D2/D4) |
| **Eloquent** | 90 | ORM helpers (not UI) | Not applicable to UI/UX focus |
| **Essentials** | 14 | laravel-dompdf, laravel-backup-spatie, sentry-laravel, ray (tooling) | Not UI-pattern sources |
| **Plugins** | 122 | **livewire-charts/lavacharts** (charts), **filament-shield** (permission UI, ref), **filament-apex-charts**, **filament-menu-builder**, **filament-page-with-sidebar**, **artisan-gui** (no license), **laravel-datatables** | Charts + permission-UI references (C2/D1) |
| **Template** | 41 | **tailadmin** (MIT), **tablar** (MIT), **sneat** (MIT), **volt** (MIT), **mosaic(GPL)** | Dashboard shell/sidebar patterns (E1) |
| **Testing** | 33 | Test tooling (not UI) | Not applicable |
| **Tutorials** | 20 | `eloquent-performance-patterns`, `securing-laravel`, `domain-driven-laravel`, `battle-ready-laravel`, Laracasts | **Reading/reference only** (no code) |
| **UI Components** | 61 | **tallstackui/wireui/mary/tallcraftui/dashui/luvi/flexiwind/cagilo** (MIT), **turbine-ui** (no license), **livewire-charts**, **livewire-calendar**, **toaster-magic/livewire-toaster**, **spotlight** | Component gap-filling, charts, tables, toasts, palette (C/E) |
| **Utility** | 158 | **laravel-trend** (MIT), **laravel-widgetize** (MIT), **laravel-filterable** (MIT), **laravel-totem** (MIT), **blade-icons** (MIT), **feature-flags** (MIT), **laravel-governor** (MIT), **laravel-auditable** (MIT), **laravel-stateflow** (MIT), **tall-toasts** (MIT), **laratrust** (MIT) | Strong source of KPI/trend/widget/settings/role primitives |
| **Websites** | 31 | Sites built *with* Laravel (not reusable) | Not applicable |

**Highlight:** the single most stack-relevant new find is **`laradashboard/laradashboard`** (MIT, Laravel 13 + Livewire 3 + **Tailwind v4**, modular, permission-aware dashboards).

---

## 10. Named-resource confirmation (R2 ask — covered vs new)

| Resource | Previously covered? | Repo | License (verified) | Verdict |
|---|---|---|---|---|
| `luvi-ui/laravel-luvi` | **Yes (R1)** | `luvi-ui/laravel-luvi` | MIT | Adoptable (copy-paste component patterns) |
| `turbineui.com` | Mentioned R1 as "verify" | `brandymedia/turbine-ui-core` | **No LICENSE file** | **Reference only** (Tailwind Blade components) |
| `artisanflow.dev` | **No — new** | `getartisanflow/wireflow` | MIT | Adoptable; Livewire+Alpine **flow diagrams** |
| `mary-ui.com` | **Yes (R1)** | `robsontenorio/mary` | MIT | Adoptable (daisyUI → verify Tailwind v4) |
| `z-song/laravel-admin` | Listed R1, not evaluated | `z-song/laravel-admin` | MIT | Reference only (Bootstrap admin framework) |
| `flexiwind.laravel.cloud` | **Yes (R1)** | `unoforge/flexiwind` | MIT | Adoptable (Tailwind v4 native) |
| `cagilo.github.io` | **No — new** | `cagilo/cagilo` | MIT | Adoptable (Blade components) |
| `madewithlaravel.com/go/livewire-charts` | Listed R1, not evaluated | `asantibanez/livewire-charts` | MIT | Adoptable (ApexCharts Blade components) |

---

## 11. Recommended follow-up decisions (real, own decisions)

1. **[High]** Report/KPI **snapshot table + scheduled rebuild**; then route dashboard + report-cards reads through it *(A1)*.
2. **[High]** Consolidate analytics into one **`SchoolStatService`**; migrate dashboard/report/export aggregates *(A2)*.
3. **[High]** **Modernize the chart layer** off Chart.js v2 (ApexCharts/Livewire component or Chart.js v4 Blade wrapper) and add **sparkline/trend** KPI data *(C2/C3)*.
4. **[High]** Standardize a **server-side data-table** pattern with sorting/filters/column-visibility/empty-state, fixing `<x-table>`'s dead props *(C1)*.
5. **[Medium]** **Permission matrix UI** (build on Laratrust vs adapt governor/roles) + **grouped settings shell** *(D1/D2)*.
6. **[Medium]** Study **`laradashboard`** for **permission-aware modular dashboards** and a user-activity panel *(D3)*.
7. **[Medium]** **Unify approval transitions** via a state machine / Livewire workflows *(D4)*.
8. **[Medium]** Timetable **preset weeks** + **drift-audit command** (+ leave-aware occurrences if per-session features are planned) *(B1/B2)*.
9. **[Medium]** Sidebar **a11y/keyboard** pass + optional **command palette**; **icon-set** standardization *(E1/E2)*.
10. **[Low–Med]** **Stackable toasts**, **feature flags** for gated surfaces, **schedule observability** *(C4/E3/E4)*.
11. **[Strategic]** **Skills/competency** model scoping vs Uganda progressive assessment *(A6)*.
12. **[Legal]** Written MIT confirmation for any Academico adaptation; never import **Mosaic (GPL)**, **Relaticle (AGPL)**, or **license-less** repos without written permission.

---

## Appendix — Verification notes & sources

**KlassApp (repo, read-only):** `app/Services/StudentReportCardService.php`, `MarksReportService.php`, `StudentReportHelperService.php`; `app/Http/Controllers/Admin/ReportCardsController.php` (`computeEotKpis` L97) + `DashboardController.php:136`; `ReportsController.php`; `Jobs/GenerateClassReportsJob.php`; `Models/ReportGeneration.php`; `Models/Academics/TimetableSlot.php`; `Admin/TimetableSlotController.php`; `config/gtimetable.php`; `resources/views/components/ds-kpi-card.blade.php`; `resources/views/layouts/admin/{sidebar,menu}.blade.php`; `layouts/partials/settings-nav.blade.php`; `resources/views/admin/dashboard/dashboard.blade.php` (Chart.js v2); `public/css/dashboard-refresh.css` (3,692 lines); `app/Livewire/*`; `app/Models/{Approval,HomeworkApproval,AssignmentApproval,LessonPlanApproval}.php`; `package.json` (tailwindcss ^4.3.3, livewire ^3.4, vue 3.5.40, chart.js ^2.9.3); `composer.json` (laravel ^12.0); `AGENTS.md`; `knowledge.md`.

**Academico (shallow clone of `github.com/academico-sis/academico` @ `main`):** `composer.json` (Laravel ^12, Filament ^5, php ^8.5), `README.md`, `app/Services/{ReportService,StatService}.php`, `app/Console/Commands/{BuildCachedReport,ResyncCourseTimes}.php`, `app/Models/{CachedReport,Period,CourseTime,Grade,GradeType,EvaluationType,Enrollment,Skill*}.php`, `app/Traits/{PeriodSelection,HandlesAttendance,ReportsErrors}.php`, `app/Filament/Pages/*` + `Widgets/*`, migrations for `periods`/`course_times`/`cached_reports`/`schedule_presets`. **No timetable module** (verified). License: `composer.json` MIT, **no LICENSE file**, GH none.

**madewithlaravel.com:** all 13 categories enumerated from JSON-LD `ItemList` (counts in §9). Per-project pages fetched for name/tagline/GitHub. Licenses verified with `gh api repos/<r>` + `/license` + reading license files (`robsontenorio/mary` `license.md`, `kevinkhill/lavacharts` `LICENSE`, etc.). Cruip terms: repo README → <https://cruip.com/terms/>.

*Comparison/reference research only. Recommends targeted, idea-level adaptation while preserving KlassApp's own design system and MIT posture — not a plan to import external code.*
