<!-- SPDX-License-Identifier: MIT -->
<!--
  INTERNAL WORKING REFERENCE — not published onboarding.
  Comparison/reference research only: it recommends idea-level adaptation and
  explicitly does NOT propose importing external code. See docs/README.md for the
  docs map and the "Working notes, audits, and evidence screenshots" note.
-->

# External Comparison Research — KlassApp vs. Academico & the madewithlaravel.com Ecosystem

**Prepared:** 2026-09-21 · **For:** Mucu (KlassApp) · **Type:** comparison / reference research (no implementation)

**Scope:** (1) Academico (`academico-sis/academico`) reports + scheduling logic vs. KlassApp's current implementation; (2) a survey of madewithlaravel.com **Administrator / UI Components / Template** categories for genuinely relevant Laravel projects; (3) explicit attribution + licensing for anything flagged as worth adapting.

**Method note (what is actually comparable):** Academico is **Filament v5 / Laravel 12**; its "reports" are Filament *Pages* + *Widgets* and its CRUD is Filament *Resources*. KlassApp is **Laravel 12 + Blade + Livewire 3.4 + Tailwind v4 + Vue 3.5 (compat) + Alpine**. Filament UI code (Resources/Pages/Widgets) **does not transfer** to KlassApp and is not recommended for import. What *does* transfer — and what this report compares — is the **service/command/data-model layer, the caching strategy, the analytics semantics, and the solver/adjacency logic**, all of which are framework-agnostic Laravel.

---

## 0. TL;DR — opportunity areas ranked

| # | Area | Source | Verdict | Effort | Real follow-up decision? |
|---|------|--------|---------|--------|--------------------------|
| A1 | **Materialized report/KPI snapshot table + scheduled rebuild** | Academico `cached_reports` + `academico:build-report` | **Strong / genuinely worth it** | Med | **Yes** — schema + cadence decision |
| A2 | **One reusable `StatService` with polymorphic reference (Term\|Year\|DateRange)** | Academico `StatService` | Strong | Med | Yes |
| A3 | **Cohort retention + "new students" analytics** | Academico `Period::acquisitionRate / newStudents()` | Medium | Med | Yes (product metric decision) |
| A4 | **Precompute position/aggregate maps once per exam** | Academico (implicit) + KlassApp's own N-query loops | Strong (perf) | Low–Med | Yes |
| A5 | **Config-gated report sections + report error trait** | Academico `config/academico.php`, `ReportsErrors` | Low/utility | Low | No (fold into A1) |
| A6 | **Skill/competency evaluation model** | Academico Skills + `EvaluationType` | Strategic | High | Yes (ties to Uganda progressive assessment) |
| B1 | **"Preset week" timetable templates** | Academico `schedule_presets` | Medium | Low–Med | Yes |
| B2 | **Timetable↔calendar drift repair command + leave-aware occurrences** | Academico `ResyncCourseTimes`, `CourseTime::createEvents` | Medium | Low–Med | Yes |
| B3 | **Config-driven "current/default period" resolution + rollover** | Academico `Period::get_default_period()` | Low–Med | Low | Maybe |
| C1 | **Server-side data table (sort/filter/paginate) component** | livewire-powergrid / laravel-livewire-tables (MIT) | Medium | Med | Yes |
| C2 | **Stackable toast/notification pattern** | laravel-toaster-magic / livewire-toaster (MIT) | Medium | Low | Yes |
| C3 | **Blade/Alpine component library as ds-* gap-filler** | tallstackui / wire-ui (MIT) | Medium | Med | Yes (pick **one**) |
| D1 | **Sidebar a11y + command palette** | livewireui-spotlight + KlassApp's existing sidebar | Medium | Low–Med | Yes |

**Do NOT import (license):** Cruip "Mosaic/Laravel" template (GPL + explicit *no redistribute*); any repo with **no LICENSE file** (Graindashboard, laravel-mazer, Miaababikir preset, etc.) — reference patterns only.

---

## 1. Attribution & licensing — read before reusing anything

Everything below was verified live this session via GitHub's API (`gh api repos/<r>` and `/license`) and by reading the actual license files. **A declared license in `composer.json` is not the same as a detected repo license** — that gap matters, so it is called out explicitly.

| Project | Repo | Declared / detected license | Verified how | Reuse guidance |
|---|---|---|---|---|
| **Academico** | `academico-sis/academico` | **Ambiguous**: `composer.json` = `"license": "MIT"`; **no `LICENSE` file**; GitHub API returns **none**; website only says "open-source license" | `gh api .../license` → 404; repo root has no LICENSE | **Do not copy code.** Treat as read-only reference. If any adaptation is ever pursued, get written MIT confirmation from the maintainer (Thomas Debay) first. |
| TailAdmin Laravel | `sinan-aydogan/tailadmin-laravel` | **MIT** | `gh api` license spdx=MIT | Safe, keep `LICENSE` + copyright. Note: Vue/Inertia, so **patterns only** for a Blade app. |
| TallStackUI | `tallstackui/tallstackui` | **MIT** | license endpoint | Safe to depend on via Composer. |
| Wire UI | `wireui/wireui` | **MIT** | license endpoint | Safe. |
| Mary UI | `robsontenorio/mary` | **MIT** (file named `license.md`, text is verbatim MIT) | read file | Safe. Needs daisyUI + Tailwind v3 → **verify v4**. |
| TallCraftUI | `developermithu/tallcraftui` | **MIT** | license endpoint | Safe. |
| FlexiWind | `unoforge/flexiwind` | **MIT** | license endpoint | Safe; young project — pin version. |
| Dash UI | `combindma/dash-ui` | **MIT** | license endpoint | Safe. |
| Luvi UI | `luvi-ui/laravel-luvi` | **MIT** | license endpoint | Safe (copy-paste style). |
| BladeWind UI | `bladewindui/bladewindui` | **MIT** | license endpoint | Safe (original `mkocansey/bladewind` now forwards here). |
| Tall Forms | `tanthammar/tall-forms` | **MIT** | license endpoint | Safe. |
| PowerGrid | `Power-Components/livewire-powergrid` | **MIT** | license endpoint | Safe. |
| Laravel Livewire Tables | `rappasoft/laravel-livewire-tables` | **MIT** | license endpoint | Safe. |
| Laravel Toaster Magic | `devrabiul/laravel-toaster-magic` | **MIT** | license endpoint | Safe. |
| Livewire Toaster | `masmerise/livewire-toaster` | **MIT** | license endpoint | Safe. |
| Wire Elements Modal | `wire-elements/modal` | **MIT** | license endpoint | Safe. |
| Laratrust | `santigarcor/laratrust` | **MIT** | license endpoint | Safe — **KlassApp already uses Laratrust**; this is a maintenance check, not an import. |
| Tablar | `takielias/tablar` | **MIT** | license endpoint | Safe. Bootstrap/Tabler (not Tailwind) → **patterns only**. |
| Sneat (free) | `themeselection/sneat-bootstrap-html-laravel-admin-template-free` | **MIT** | license endpoint | Safe. Bootstrap → patterns only. |
| Volt Laravel | `themesberg/volt-laravel-dashboard` | **MIT** | license endpoint | Safe. Bootstrap + Livewire/Alpine → auth-page patterns. |
| Admin One | `vikdiesel/admin-one-laravel-dashboard` | **MIT** | license endpoint | Safe but **stale (2022)**, Bulma/Vue → patterns only. |
| Laravel Impersonate | `404labfr/laravel-impersonate` | **MIT** (`composer.json`); **no LICENSE file** in repo | `composer.json` | Safe in practice; note the documentation gap. KlassApp already has impersonation. |
| Craftable | `BRACKETS-by-TRIAD/craftable` | **MIT** | license endpoint | Safe; Bootstrap admin builder — patterns only. |
| **Cruip Mosaic Lite (Laravel)** | `cruip/laravel-tailwindcss-admin-dashboard-template` | **GPL** + *"use for personal/commercial, but don't republish, redistribute, or resell"*; **no LICENSE file** | README + [cruip.com/terms](https://cruip.com/terms/) | **Avoid for code import.** GPL is copyleft and contradicts KlassApp's MIT posture; the no-redistribute clause is stricter still. Visual reference at most — do not copy markup/CSS. |
| Graindashboard | `maxkostinevich/Graindashboard` | **No LICENSE file** (README says "MIT License"); GitHub detects none | root contents + README | **Patterns only**, no code, until clarified. Also **stale (2023)**. |
| Laravel Mazer | `zuramai/laravel-mazer` | `composer.json` MIT; **no LICENSE file**; detected none | `composer.json` | Patterns only. Bootstrap, stale (2024). |
| Tailwind CSS Dashboard Preset | `Miaababikir/laravel-tailwind-css-dashboard-preset` | `composer.json` MIT; **no LICENSE file**; detected none | `composer.json` | Patterns only; **stale (2021)**. |
| Laravel Nova / Pro templates (argon/black/material/white/paper dashboards) | various `creativetimofficial/*`, `themesberg/*` | Mostly MIT (free) but **Pro variants are paid** | spot-checked | Free variants MIT; do not pull "Pro". **Bootstrap**, not Tailwind. |

> **Attribution principle for KlassApp:** keep KlassApp's `SPDX-License-Identifier: MIT` headers intact; anything borrowed as *ideas* needs no attribution (ideas aren't copyrightable), but anything copied as *code or CSS* must retain the upstream `LICENSE` + copyright notice. Since KlassApp has a real, hand-built `ds-*` design system, the default posture remains **adapt ideas, do not import** — this research is explicitly *not* a plan to replace the design system.

---

## 2. Baseline — what KlassApp actually does today (verified in repo)

### 2.1 Reports
- **Report cards (PDF):** single shared pipeline `App\Services\StudentReportCardService::generatePdf()` (the fix for the "fixes reaching only some surfaces" bug — `knowledge.md` §Known Bug Patterns #1). `resolveExam()` picks the latest exam for `(school_id, section_id, standard_id)` whose `examType.contributes_to_report_total = 1`. Templates registered as `formal` + `warm` (`StudentReportCardService::TEMPLATES`); a `modern.blade.php` exists on disk but is **not** registered. Per-school choice via `schools.report_template` (default `formal`).
- **Ranking/aggregation:** position map, stream position, division (1/U scale), aggregate points — computed inline; the per-student loops issue **repeated `Marks::where(...)->sum()` queries per student** (e.g. the `midStats` and `stream` blocks in `generatePdf`). DomPDF renders A4 portrait.
- **Bulk class generation:** `report_generations` table (`ReportGeneration` model; statuses `pending|processing|completed|failed`) + `App\Jobs\GenerateClassReportsJob` (merged PDF via `iio/libmergepdf` or per-student ZIP). Good async foundation.
- **Analytics/KPIs:** `ReportCardsController::computeEotKpis()` — a **live** raw-SQL aggregate, called on every request by both `ReportCardsController::index()` **and** `DashboardController::index()`. **No cache, no snapshot table** (only `report_generations` exists).
- **Exports:** on-demand CSV via `league/csv` + Excel via `maatwebsite/excel` in `ReportsController` (fees, birthdays, active/suspended/exit students, parents, stock, purchases, sales). Each export re-queries live.
- **Delivery:** WhatsApp report-card delivery (`WhatsAppReportCardDeliveryService`, `WhatsAppReportFileController`) + `PruneWhatsAppReportFiles` command.

### 2.2 Scheduling / timetable
- `timetable_slots` table (school, academic_year, term, section, subject, teacher, `day_of_week`, start/end, room) + unique slot constraint. Model `TimetableSlot` has **real conflict detection** — `detectConflict()` (teacher overlap **and** section/room overlap) and `checkTeacherLink()` (warns if teacher/subject/class isn't in `class_teacher_links`).
- `syncCalendarEvent()` = **delete + rebuild a single weekly-recurring `Events` row** per slot (with `timetable_slot_id` FK, `freq_term=weekly`).
- `TimetableSlotController`: index (per section, grouped by day), CRUD, `teacherWeekly()` teacher view. Toggle `config/gtimetable.php` (`enabled`). Vue `timetable.vue` / `dashboard/Timetable.vue`.
- Supports teacher leave (`TeacherLeaveApplication` model exists) but the timetable's calendar sync **does not subtract leave dates**.

### 2.3 Dashboard UX / design system (the "just completed" work)
- `public/css/dashboard-refresh.css` (~3,692 lines) defines a `ds-*` token/class contract: `ds-btn`, `ds-card`, `ds-badge`, `ds-form-*`, `ds-kpi-card`, `ds-empty-state`, `ds-dot`, `ds-grid-marks`, etc.
- Anonymous Blade components: `x-button`, `x-card`, `x-badge`, `x-table`, `x-form-group`, `x-ds-kpi-card`, `x-brand.*`.
- Shells: `dashboard-shell dashboard-shell--admin` (+ role variants). Per-role sidebars under `resources/views/layouts/<role>/sidebar.blade.php` (admin, superadmin, teacher, parent, student, accountant, library, reception, stock, alumni) sharing `layouts.admin.menu`.
- **The admin sidebar is already advanced**: collapsible groups with `localStorage` persistence (`sidebar-group-academics`), hover-preview gated by `(hover: hover) and (pointer: fine)`, `x-collapse` animation, active-state via a `sidebarActive()` helper that matches `Request()->segment('2')`.
- Known caveat from `knowledge.md`: the Toshi panel is `position: fixed` with hard-coded pixel math aligned to the 380px sidebar; **any sidebar-width change silently breaks that alignment**. Also `DESIGN_SYSTEM.md`'s "Tailwind v1.4.6" claim is stale — the app is actually on **Tailwind v4.3**.

---

## 3. Academico deep-dive — the patterns that matter (not the Filament UI)

Academico is a **language/small-institution** SIS (Laravel 12 + Filament v5, rewritten from Backpack with **unchanged DB schema**; 401★, 14 contributors, in production since 2017). Its scope is narrower than KlassApp (no K-12 timetables, no UNEB divisions), but its **data/analytics layer is more mature** in the areas below.

### 3.1 Materialized reports (the headline pattern)
- `cached_reports` table: one row per reporting unit (`period_name`, `year_id`, `period_id`, `students`, `enrollments`, `acquisition_rate`, `new_students`, `taught_hours`, `sold_hours`, `takings`, `avg_takings`, `order`).
- Built by `php artisan academico:build-report` → `BuildCachedReport` **truncate + rebuild**, anchored on a configurable `first_period`; heavy lifting in `ReportService::buildInternalCoursesReport()` (chunks by year, `gc_collect_cycles()` between periods to bound memory, `ReportsErrors` trait wraps failures with context).
- Report pages then read the **cached** rows — request time is O(rows), not O(enrollments).

### 3.2 Polymorphic `StatService`
`StatService(external: bool, reference: Period|Year|DateRange, partner: ?Partner)` — one class computes `studentsCount`, `enrollmentsCount`, `paid/pendingEnrollmentsCount`, `taughtHoursCount`, `soldHoursCount`, `newStudentsCount`, `partnershipsCount` for any reference window. Filament widgets (`StatsOverview`) and report pages all consume the **same** service. (Uses `match ($reference::class)` + query scopes.)

### 3.3 Cohort analytics
- `Period::getAcquisitionRateAttribute()` = % of last period's real enrollments retained this period (intersection of student-id sets).
- `Period::newStudents()` = students this period **never** enrolled in any prior period.
- `Period::getTakingsAttribute()`, `taughtHours` vs `soldHours` (capacity vs utilisation) gated by `config('academico.include_takings_in_reports')`.

### 3.4 Period lifecycle
`periods` = `(name, start, end, year_id, order, archived)`. `Period::get_default_period()` resolves the "current" period from a `Config` row with fallbacks; `get_enrollments_period()` adds a **rollover heuristic** (switch to next period when the current one is ≥½ elapsed). A global scope orders periods. `PeriodSelection` trait injects the current period into UI.

### 3.5 Attendance completeness + nudge
`Period::getCoursesWithPendingAttendanceAttribute()` finds courses with unmarked sessions; `HandlesAttendance::remindPendingAttendance()` **queues email reminders** to teachers (config-gated) for sessions older than 24h.

### 3.6 Scheduling model (and what it is *not*)
- **Academico has no timetable module.** Scheduling = `Period` (term) + `CourseTime` (`course_id, day, start, end`) per course.
- `CourseTime` **materializes concrete occurrences**: on create/update it loops each day in the course span and creates individual `Event` (class-session) rows — **skipping teacher leave dates** (`$teacher->leaves->contains(...)`). Update = delete events + regenerate (delete+rebuild). `ResyncCourseTimes` command repairs drift by re-creating events for a course.
- `schedule_presets` table = stored JSON presets (reusable schedule templates).
- **No conflict detection, no room booking, no teacher weekly grid, no substitution** — i.e. KlassApp's timetable is *ahead* here.

### 3.7 Skills / competency evaluation
`Grade`/`GradeType`/`GradeTypeCategory` (grade-based) **plus** `Skill`/`SkillType`/`SkillScale`/`SkillEvaluation` and `EvaluationType` presets (`MorphToMany` between grade types, skills, and courses). i.e. a first-class **competency framework** alongside numeric grades.

---

## 4. Opportunity Area A — Reports

### A1. Materialized KPI/report snapshot + scheduled rebuild  ★ top pick
**Academico:** `cached_reports` + `academico:build-report` (truncate+rebuild, config-anchored).
**KlassApp today:** `computeEotKpis()` runs a **live raw-SQL aggregate on every dashboard and report-cards page load**; no snapshot table; heavy per-student loops in report-card generation.
**Concrete, transferable idea:**
- Add a `report_snapshots` / `kpi_snapshots` table keyed by `(school_id, academic_term_id, scope, metric)` storing the EOT KPIs (class totals, subject averages, gender split) already produced by `computeEotKpis`.
- Rebuild via a queued command (KlassApp **already has** an active scheduler `scheduler-heartbeat` and queues) — nightly + on-demand "recompute" button, mirroring Academico's truncate/rebuild anchored to a configurable term.
- Keep `computeEotKpis` as the source of truth but have it write-through to the snapshot; dashboard reads the snapshot.
- **Why it fits:** KlassApp's dashboard and per-class report cards both hammer the same aggregate; this is the single biggest, lowest-drama win.
- **Decision needed:** snapshot schema + which KPIs + cadence (nightly vs on mark-submit).

### A2. One reusable `StatService` (polymorphic reference)
Academico funnels *all* stats through one service with a `Period|Year|DateRange` reference. KlassApp spreads analytics across `StudentReportHelperService`, `MarksReportService`, `computeEotKpis`, `DashboardController`, and various exports. A single `SchoolStatService(int $schoolId, AcademicTerm|AcademicYear|DateRange $ref)` would remove duplication and make reports + dashboard + exports consistent.
**Decision needed:** yes/no on introducing the abstraction; scope of metrics.

### A3. Cohort retention / new-student analytics
Academico's `acquisition_rate` + `newStudents()` are genuine, differentiated metrics KlassApp lacks. A `schooladmin` (and `superadmin`) KPI card "Retention vs last term" / "New vs returning students" is a small, visible addition that reuses existing enrollment data. Ties directly to the Uganda enrolment narrative.
**Decision needed:** product call — include retention KPIs in the dashboard refresh?

### A4. Precompute position/aggregate maps once per exam (performance)
In `StudentReportCardService::generatePdf` and `computePositionMap`, per-student marks are re-summed with fresh queries inside loops. Academico's `StatService` and `cached_reports` show the alternative: compute **one grouped query per exam** (`SUM(marks) GROUP BY student_id`) and index into it. For a 96-learner class this collapses hundreds of queries into one or two. Low risk, high payoff; pairs naturally with A1.
**Decision needed:** bundle into the existing report-card perf backlog.

### A5. Config-gated sections + report error trait
Academico exposes report switches (`include_takings_in_reports`) and wraps report building in `ReportsErrors` (logs stack + context). KlassApp already logs `ReportGeneration` failures with `error` text; a small shared `ReportsErrors`-style trait for the export controllers would make failures consistent. **Utility only — fold into A1.**

### A6. Skill/competency evaluation model (strategic)
Academico's Skills + `EvaluationType` presets model competency-based assessment alongside grades. `knowledge.md` already references **"Uganda's new progressive-assessment curriculum"**; Academico is a concrete, real-world example of a competency layer that could inform how KlassApp records non-numeric assessments. **This is a product/roadmap decision, not a code reuse.** High effort.

---

## 5. Opportunity Area B — Scheduling / timetable

**Honest framing:** Academico has **no timetable**. KlassApp's timetable is *more capable* (conflict detection across teacher **and** room/class, teacher-link warnings, per-teacher weekly view, term/year scoping, feature toggle). So the transferable value is narrow and specific:

### B1. "Preset week" timetable templates (`schedule_presets`)
Academico stores reusable schedule presets as JSON. KlassApp has no equivalent. A **"preset week"** (e.g. "Standard P.1–P.7 week") that bulk-seeds `timetable_slots` — still routed through the existing `detectConflict()` validation — would remove the biggest manual cost of setting up timetables per term. **Low–medium effort, clean fit.** Decision needed: yes/no + preset UX.

### B2. Drift repair command + leave-aware occurrences
- Academico's `ResyncCourseTimes` is a **repair command** for when generated occurrences drift from the source schedule. KlassApp's `syncCalendarEvent()` is delete+rebuild per slot but there is **no auditing command** to detect slots whose calendar event is missing/stale. A `timetable:verify` command (assert every slot has exactly one event, and vice-versa) is cheap insurance.
- Academico's `CourseTime::createEvents()` **skips teacher-leave dates** when materializing; KlassApp's weekly-recurring event does not subtract `TeacherLeaveApplication` dates. If KlassApp ever wants per-session attendance/substitutions, generating **materialized occurrences** (instead of one recurring row) that respect leave is the Academico pattern to copy conceptually.
**Decision needed:** is per-session granularity (attendance/substitution) on the roadmap? If not, only B2-audit is worth it.

### B3. Config-driven "current/default period" resolution + rollover
Academico resolves the active period from a `Config` row with a rollover heuristic. KlassApp resolves the active `AcademicYear` (see `NavigationController` cache) but has no analogous "default enrollment term" helper. Minor; useful if enrolment/term defaults become inconsistent. **Maybe** — only if it removes real bugs.

---

## 6. Opportunity Area C — madewithlaravel.com survey (Administrator / UI Components / Template)

**Coverage:** enumerated the full category listings via JSON-LD — Administrator **88**, UI Components **61**, Template **41** projects — then filtered for the KlassApp stack (**Laravel + Blade/Tailwind/Alpine, ideally Livewire**). Filament-first, Vue/React-only, Bootstrap-only, and paid "Pro" products were down-weighted or excluded except where the *pattern* is useful. Ranked by genuine relevance to the *completed dashboard-UX work* (targeted enhancement, not replacement).

### C-tier 1 — Component libraries that fit the stack (Blade + Tailwind + Alpine/Livewire)

| Project | Stars | Lang/stack | License | What it does well | KlassApp enhancement idea |
|---|---|---|---|---|---|
| **TallStackUI** `tallstackui/tallstackui` | 751 | PHP, TALL (Tailwind+Alpine+Livewire) | MIT | 80+ Blade components; layout/sidebar, modals, forms, tables; actively maintained (2026-09) | Best single fit as a **gap-filler** for ds-* (rich select, date pickers, dialogs). Verify Tailwind **v4** support; otherwise vendor selected components. |
| **Wire UI** `wireui/wireui` | 1,790 | PHP, Tailwind + Alpine (Livewire-optional) | MIT | Mature dropdowns, modals, notifications; framework-agnostic | Alpine-native components that don't force Livewire — good for the Vue/Blade hybrid surfaces. |
| **Mary UI** `robsontenorio/mary` | 1,480 | PHP, Livewire + **daisyUI** | MIT | Polished component set on daisyUI | Attractive but pulls **daisyUI/Tailwind v3** — flag as **v4-compat risk**; patterns (stat cards, layout) reusable without the dependency. |
| **TallCraftUI** `developermithu/tallcraftui` | 168 | PHP, TALL | MIT | Lighter TALL component set | Alternative to TallStackUI if a smaller footprint is preferred. |
| **Dash UI** `combindma/dash-ui` | 63 | Blade, **Polaris**-inspired | MIT | Opinionated admin components (Polaris-like) | Reference for consistent admin component *semantics* (states, spacing) matching a design system. |
| **Luvi UI** `luvi-ui/laravel-luvi` | 456 | Blade + Alpine, shadcn port, copy-paste | MIT | shadcn-style copy-paste components | Good source for **table/dialog/sidebar markup patterns** without a runtime dependency. |
| **FlexiWind** `unoforge/flexiwind` | 30 | Blade + Livewire, **Tailwind v4 native** | MIT | shadcn-like, explicitly Tailwind v4 | Only listed lib asserting **v4**; young → watch, pin. |
| **BladeWind UI** | — | Blade + Tailwind | MIT | Broad component set | Optional alt to TallStackUI. |
| **Turbine UI** `brandymedia/turbine-ui-core` | — | Blade + Tailwind | *verify* | Marketing/UI components | Verify license before use. |

### C-tier 2 — Tables & toasts (utility components with clear gaps)

| Project | Stars | License | Relevance |
|---|---|---|---|
| **PowerGrid** `Power-Components/livewire-powergrid` | 1,694 | MIT | Server-side sort/filter/paginate tables for Livewire 3 — pattern/solution for large lists (fees, students, marks). |
| **Livewire Tables** `rappasoft/laravel-livewire-tables` | 1,975 | MIT | Same class of solution; compare ergonomics vs PowerGrid. |
| **Laravel Toaster Magic** `devrabiul/laravel-toaster-magic` | 227 | MIT | Stackable, themeable toasts with **no jQuery/Bootstrap/Tailwind class dependency** — easy add-on to `ds-alert`. |
| **Livewire Toaster** `masmerise/livewire-toaster` | 511 | MIT | Alternative toast layer. |
| **Wire Elements Modal** `wire-elements/modal` | 1,211 | MIT | State-preserving nested modals — useful if Vue modals are being replaced with Blade/Alpine. |
| **Tall Forms** `tanthammar/tall-forms` | 680 | MIT | Form generation + realtime validation patterns. |

> Note: KlassApp's `knowledge.md` flags **`<x-table>`'s `striped`/`hover` dead props** — the table component is an *active* improvement area, so the table libs above are directly relevant.

### C-tier 3 — Templates / Administrator (patterns; KlassApp's side is already stronger)

| Project | Stack | License | Why it's here / caveat |
|---|---|---|---|
| **TailAdmin Laravel** `sinan-aydogan/tailadmin-laravel` | Laravel + **Tailwind + Inertia + Vue** | MIT | The confirmed benchmark. **Vue, not Blade** → reference for *visual* sidebar/KPI patterns only, not markup. |
| **Tablar** `takielias/tablar` | Laravel Blade + Vite + **Tabler/Bootstrap** | MIT | A real **Laravel Blade starter kit** — useful for its sidebar/layout *structure*; Bootstrap CSS means patterns only. |
| **Sneat (free)** | Laravel Blade + Bootstrap | MIT | Same category; sidebar/menu patterns. |
| **Volt Laravel** | Bootstrap + Livewire/Alpine | MIT | Auth-page + dashboard patterns. |
| **Admin One** | Bulma + Vue | MIT | Stale (2022); skip. |
| **Craftable** | Bootstrap admin builder | MIT | CRUD/export patterns; Bootstrap. |
| **Cruip Mosaic Lite (Laravel)** | Tailwind + Blade | **GPL + no-redistribute** | ⚠️ **Do not import.** Visual reference only. |
| **Graindashboard / Laravel Mazer / Tailwind preset** | mixed | **no LICENSE file** | ⚠️ Patterns only; stale. |

---

## 7. Opportunity Area D — Sidebar / navigation patterns

**Finding:** madewithlaravel.com has **no dedicated "sidebar" category or package** in these three categories (checked the full listings and site search — no `sidebar`-slug project). Sidebar/nav expertise lives inside the admin templates (Tablar, Sneat, TailAdmin, Mosaic) and the component libs (Dash UI, Luvi, TallStackUI, Wire UI). KlassApp's sidebar is **already more capable than most**: collapsible groups + `localStorage` persistence + capability-gated hover preview + `x-collapse`.

Targeted enhancements (not replacements):

1. **Active-state robustness.** `sidebarActive()` currently matches `Request()->segment('2')`, which is brittle (assumes URL shape) — prefer named-route matching (`request()->routeIs('admin.students.*')`). (Pattern seen in Tabler/Sneat menu definitions.)
2. **Keyboard + a11y.** Add focus trap for the mobile off-canvas, `aria-expanded`/`aria-current` on groups, and visible focus rings. Template sidebars (Tablar/Sneat) show the conventional markup.
3. **Command palette / quick-jump.** `livewireui-spotlight` (Livewire) or a small Alpine palette gives admins ⌘K navigation — a modern pattern not present in KlassApp, complementing (not replacing) the sidebar.
4. **Respect the shell math.** Because Toshi is `position: fixed` at the 380px assumption, any sidebar-width or rail/toggle change must update the shell pixel math (or the deferred flex-sibling fix) — flagged in `knowledge.md`.
5. **Icon system.** KlassApp uses `<x-icons.sidebar name="…"/>`; keep it as the single source rather than importing a foreign icon set.

---

## 8. Recommended follow-up decisions (flagged as real, own decisions)

1. **[High]** Adopt a **report/KPI snapshot table + scheduled rebuild** (Academico `cached_reports` pattern) for `computeEotKpis`. Decide schema, KPI set, cadence. *(A1, A4)*
2. **[High]** Introduce a single **`SchoolStatService`** with a `Term|Year|DateRange` reference and migrate dashboard/report/export aggregates to it. *(A2)*
3. **[Medium]** Add **retention / new-vs-returning** KPI cards. *(A3)*
4. **[Medium]** Add **"preset week" timetable templates** + a **timetable↔calendar verify/repair command**; consider leave-aware materialized occurrences if per-session features are planned. *(B1, B2)*
5. **[Medium]** Pick **one** Blade/Tailwind component source to fill `ds-*` gaps (perf the shortlist: TallStackUI vs Wire UI), verifying **Tailwind v4** compatibility; add stackable toasts and a server-side table solution. *(C1–C3)*
6. **[Medium]** Sidebar a11y pass + optional command palette. *(D1)*
7. **[Strategic]** Scope the **skills/competency evaluation** model against Uganda's progressive-assessment curriculum. *(A6)*
8. **[Legal]** If any Academico adaptation is ever wanted, **obtain written MIT confirmation** first (declared MIT, no LICENSE file). Never import **Cruip Mosaic** (GPL + no-redistribute) or license-less repos.

---

## Appendix — Verification notes & sources

**KlassApp evidence (repo `/Users/mac/projects/KlassApp`, read-only):**
`app/Services/StudentReportCardService.php` (504 lines; `TEMPLATES` = formal+warm; `resolveExam`; `computePositionMap`; inline per-student loops), `app/Services/MarksReportService.php`, `app/Services/StudentReportHelperService.php`; `app/Http/Controllers/Admin/ReportCardsController.php` (`computeEotKpis` L97, called from `DashboardController.php:136`), `app/Http/Controllers/Admin/ReportsController.php` (CSV/Excel exports), `app/Jobs/GenerateClassReportsJob.php`, `app/Models/ReportGeneration.php` + migration `2026_08_12_194918_create_report_generations_table.php`, `app/Models/Academics/TimetableSlot.php` (`detectConflict`, `checkTeacherLink`, `syncCalendarEvent`), `app/Http/Controllers/Admin/TimetableSlotController.php`, migration `2026_07_16_074716_create_timetable_slots_table.php`, `config/gtimetable.php`, `resources/views/layouts/admin/{sidebar,menu}.blade.php`, `public/css/dashboard-refresh.css` (3,692 lines), `resources/views/components/*`, `package.json` (tailwindcss ^4.3.3, livewire ^3.4, vue 3.5.40), `composer.json` (laravel/framework ^12.0), `knowledge.md`.
*Declared stack elsewhere ("Tailwind 1.4"/"Laravel 10") is stale — verified actuals are Laravel 12 / Livewire 3.4 / Tailwind v4 / Vue 3.5 (compat).*

**Academico evidence (shallow clone of `github.com/academico-sis/academico`, `main`):**
`composer.json` (Laravel ^12, Filament ^5, php ^8.5, mpdf, phpword, maatwebsite/excel, laraveldaily/laravel-invoices, spatie/*), `README.md` (Backpack→Filament rewrite, unchanged schema), `app/Services/{ReportService,StatService}.php`, `app/Console/Commands/{BuildCachedReport,ResyncCourseTimes}.php`, `app/Models/{CachedReport,Period,CourseTime,Grade,GradeType,EvaluationType,Enrollment,Attendance,Skill*}.php`, `app/Traits/{PeriodSelection,HandlesAttendance,ReportsErrors}.php`, `app/Filament/Pages/{InternalReport,AttendanceReport,ReportPage,…}.php`, `app/Filament/Widgets/{StatsOverview,DailyOverview,PeriodInfo}.php`, migrations for `periods`, `course_times`, `cached_reports`, `schedule_presets`. **No timetable module** (verified by search). License: `composer.json` = MIT; **no LICENSE file**; GitHub API license = none.

**madewithlaravel.com evidence:** category listings (Administrator 88 / UI Components 61 / Template 41) enumerated from each page's JSON-LD `ItemList`; per-project pages fetched for name/tagline/GitHub; licenses verified via `gh api repos/<r>` and `/license` + license files. Cruip terms from repo README → <https://cruip.com/terms/>.

*This document is comparison/reference research. It recommends targeted, idea-level adaptation while preserving KlassApp's own design system and MIT licensing posture — it is not a plan to import external code.*
