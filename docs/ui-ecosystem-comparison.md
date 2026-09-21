# UI Ecosystem Comparison Research — KlassApp vs open-source reference projects

> **Independent findings doc (2026-09-21, rounds 1+2).** This is the agent-authored record of the Academico reports/scheduling comparison and the madewithlaravel.com survey, written independently of `docs/research/klassapp-external-comparison.md` (a parallel-authored doc covering the same research brief). Both are kept deliberately separate per the owner's direction; where they overlap, verify against the cited repos — each doc's evidence was gathered separately. This doc's verification path: Academico shallow-cloned to `/tmp/academico` (branch `main`), madewithlaravel.com category pages fetched live, licenses checked via GitHub API `contents/` + raw license files.

> **Research only — no code imported or planned for wholesale import.** This document records two research rounds (2026-09-21): a comparison of Academico's reports/scheduling logic against KlassApp's, and a full survey of madewithlaravel.com's categories for stack-relevant UI patterns. KlassApp's own design system (`ds-*`, role layouts, dashboard UX work) remains the baseline; findings here are pattern references and targeted enhancement ideas, each requiring its own explicit follow-up decision before any implementation.
>
> License diligence rule used throughout: a project is only marked **adoptable** if an actual LICENSE file was verified in the repo (GitHub API `contents/` or raw fetch), not just a README/composer claim. Everything else is **reference-only**.

---

## Round 1 — Academico (reports & scheduling logic)

Repo: github.com/academico-sis/academico — MIT *declared in composer.json*, **no LICENSE file at repo root** → treat as pattern-reference only, do not copy code. Filament-based language-school SIS (~400★, active). Domain differs from KlassApp (Course/Enrollment/Period adult-ed vs K-12 section/standard/stream) — comparisons are data-model/logic patterns only.

### Reports (vs `StudentReportCardService`, 504 lines, single shared pipeline)

| # | Academico pattern | KlassApp today | Opportunity |
|---|---|---|---|
| R1 | **GradeType / GradeTypeCategory / EvaluationType presets** — report *columns and weightings are data* (morph-pivot presets per evaluation type), not code. | Report structure hardcoded in `generatePdf()` (MID columns by examType code, EOT appended, aggregate/division closures inline). `contributes_to_report_total` is the only configurable part. | **Strongest finding.** A per-school/per-standard "report column/weighting definition" table that the service *reads* would make report structure genuinely configurable (e.g. adding BOT columns) without code edits. |
| R2 | **Skills/SkillEvaluation/SkillScale** — competencies track with its own scales, per level. | `nurseryAssessments => collect()` — an **empty collection passed to every template today**; no competency data model. | Natural wave-2 of nursery report cards; the seam already exists. |
| R3 | **`CachedReport` + scheduled rebuild** (`academico:build-report` truncates/rebuilds aggregate stats; report pages read the table; `ReportsErrors` trait → log + Sentry with context tags). | `computeEotKpis()` computes per request; batch report generation is queued but dashboards recompute. | Medium — at KlassApp's scale not urgent, but precomputed per-school term KPIs would offload dashboard loads. The structured error-context trait pattern is a small steal-by-pattern (write our own). |
| R4 | **Result + morphic Comments + ResultType (color-coded) + `ResultSavedEvent`** — final result record per enrollment, evented. | Rule-based comment service; no eventing on marks/results. | Partly — evented "marks finalized" maps onto queue/WhatsApp notification machinery, but adjacent to already-scoped work. |
| R5 | **Grade-edit as spreadsheet grid** — all enrollments × all grade types, inline editing, client-side totals, per-course read-only gate, save-all. | Per-exam forms, class-by-class. | **Big UX pattern for KlassApp's own marks-entry** (build KlassApp-native, informed by the pattern). |

### Scheduling (vs `TimetableSlot` + `TimetableSlotController`)

| # | Academico pattern | KlassApp today | Opportunity |
|---|---|---|---|
| S1 | **`CourseTime` (recurring template) → materialized `Event` rows per session**, with boot hooks delete+rebuild on CRUD. | Single recurring `Events` row per slot (delete+rebuild). No per-session records. | **Most important scheduling finding.** Per-occurrence operations (reschedule one session, cancel one lesson, attach attendance) are impossible without materialization. |
| S2 | **Teacher `Leave` consulted during event generation** — sessions during leave simply don't materialize. | No teacher-leave concept. | Folds into S1; later: substitute assignment. |
| S3 | **`Room` as entity** — but note: Academico does **not** room-conflict-check either (verified). | `room` is free text; **never checked for overlap** — but KlassApp's teacher+section conflict detection is genuinely *ahead* of Academico. | Rooms table + room-overlap in existing `detectConflict()` (school_id-scoped) — small, clean. |
| S4 | Attendance per materialized Event. | Attendance exists but unlinked to sessions. | Consequence of S1. |
| S5 | **`Period` with explicit order + `previousPeriod()`** — trend-vs-last-period trivial. | `AcademicTerm` ordering via dates only; "nextTerm" by `starts_on > now()`. | Small: an `order` column on academic_terms makes previous-term comparisons deterministic. |

Not to adopt from Academico: Filament resource layer, invoicing/SEPA machinery, partner/external-course concepts. Their "reports" are business-stats (enrollment, takings), not academic report cards — the report comparison is about *grading structure*.

---

## Round 2 — the 8 specifically-named candidates

Coverage status vs round 1: mary-ui was partially covered (license checked, deprioritized); **the other 7 appeared only as names in category inventories — never evaluated.** All 8 now evaluated with file-level license diligence:

| Candidate | What it actually is | License (file-verified) | Verdict |
|---|---|---|---|
| **luvi-ui/laravel-luvi** (luvi-ui/laravel-luvi, 456★) | shadcn-style **copy-paste** Blade+Alpine+Tailwind component kit (composer-installable but designed to be copied): accordion, alert, avatar, badge, breadcrumb, button, card, checkbox, dialog, dropdown-menu, form, hover-card, input, label, link, menubar, popover, portal, radio-group, select, separator, sheet, switch, tabs, textarea, tooltip, typography. | **MIT** — LICENSE file present ✔ | **Best raw-stack match found in either round** (Blade+Alpine+Tailwind, no Livewire requirement, copy-paste model respects KlassApp's own design system). Composable card (header/title/description/content/footer) is directly the pattern KlassApp's `ds-kpi-card`/panel cards use — reference for targeted adaptation. |
| **turbineui.com** (brandymedia/turbine-ui-core, 82★) | Laravel Blade & Tailwind UI component library (alert, toast, modal, sidebar, theme switcher, list-group). Actively maintained (release notes show real a11y work — accessible dismiss controls, semantic heading levels). | composer.json declares MIT, **no LICENSE file at repo root** → reference-only per our rule | Good a11y-conscious Blade+Tailwind patterns; license-file gap means **do not copy code**; pattern reference fine. |
| **artisanflow.dev** (getartisanflow/wireflow, 98★, active) | Livewire flow-diagram engine (AlpineFlow) — interactive flowcharts, schema designer, execution logs, replay controls. | **MIT** — LICENSE file ✔ | Not a UI-kit; niche. Relevant only if a visual workflow builder is ever wanted (e.g. Toshi flow design). No current KlassApp need. |
| **mary-ui.com** (robsontenorio/mary, 1480★, very active) | Livewire UI kit on **daisyUI + Tailwind**. 60+ components incl. `Stat`, `Chart`, `Spotlight`, `Table`, `Menu`/`Nav`, `Drawer`, `ProgressRadial`, `Signature`, `Diff`. | **MIT (custom `license.md` file, standard MIT text with attribution header)** ✔ | Component inventory is the deepest of any TALL kit; **daisyUI dependency is the adoption blocker** (would fight KlassApp's own design system). Reference for *component API design*: `Stat` (value/title/description/color/icon + tooltip slots) and `Chart` (`@entangle` + `wire:key` uuid — the cleanest Livewire-Chart.js bridge pattern found) are the two concrete patterns worth copying-by-idea. |
| **z-song/laravel-admin** (11k★) | Legacy-gen admin UI builder (grid+form builders). | MIT (LICENSE, (c) 2015 Jens Segers) ✔ | **Last real activity Feb 2023** — effectively unmaintained. Reject: age + architecture (jQuery-era) make it irrelevant despite the star count. |
| **flexiwind.laravel.cloud** (unoforge/flexiwind, 30★, active) | Composable TALL "UI blocks" (marketing-shape blocks + components), **Tailwind CSS v4** — the only kit found already on Tailwind v4 like KlassApp. | **MIT** — LICENSE file ✔ | Tailwind-v4-first and blade/Livewire dual support make it stack-aligned; very small/young (30★) and block-shaped rather than component-shaped. Watch, don't adopt. |
| **cagilo.github.io** (cagilo/cagilo, 172★) | Small Blade component set (Alert, Device, Error, Icon, Submit, Meta, Time). | **MIT** — LICENSE.md ✔ | Modest scope, no overlap advantage over Luvi/Bladewind. No action. |
| **madewithlaravel.com/go/livewire-charts** → 302 → **github.com/asantibanez/livewire-charts** (898★) | Livewire chart wrapper. | **MIT** — LICENSE.md ✔ | **Last push Jan 2026, slowing**; also asantibanez/livewire-calendar same state. Chart wrapper pattern is thin (mary's `Chart` component does it better). Reference-only, deprioritized. |

---

## Round 2 — full madewithlaravel.com category survey

All 13 categories inventoried this round (round 1 covered only Administrator/UI Components/Template). Totals: API 82, App 157, Boilerplate 67, Eloquent 90, Essentials 14, Plugins 124, Template 40, Testing 33, Tutorials 18, UI Components 61, Utility 93, Websites 31, Administrator 87. Screening rule: only Laravel/Blade/Tailwind/Livewire/Alpine stack-matching candidates were deep-dived.

**Categories screened out as irrelevant to KlassApp's UI focus:** API (pure API client/tooling — Saloon, Scribe, etc.; KlassApp isn't building an API surface here), Testing (dev tooling — Larastan, Telescope already known; noted: *Missing Livewire Assertions* and *Bladestan* are the only Livewire/Blade-specific ones, possible future dev tooling), Tutorials (content, not code), Websites (showcase sites), App (end-products in other domains), most of Eloquent (backend model behavior — see below for the exceptions worth noting).

### Genuinely relevant finds by category

| Category | Find | License (verified) | Relevance |
|---|---|---|---|
| Boilerplate | nothing new stack-relevant beyond round 1 (FilamentFlow/Larament = Filament-based; Laravel TALL Preset & Laravue/Breeze-family = starter kits for greenfield apps, wrong shape for existing app) | — | — |
| Eloquent | **flowframe/laravel-trend** (1122★, active) | **MIT** | Not UI — but directly relevant to KlassApp's **chart/KPI backend**: fluent `Trend::model(...)->between(...)->perMonth()->count()/average()` gives date-bucketed aggregates (fills date gaps in SQL) for the fee-trend/gender/attendance charts. The admin dashboard currently hand-rolls chart data in JS/PHP. **Adoptable candidate** (MIT, tiny, no UI dependency). |
| Eloquent | **spatie/laravel-settings** (1514★) / rawilk/laravel-settings (304★) | **MIT** / MIT | The *settings-class* pattern (typed settings objects backed by a table, cache-aware) vs KlassApp's `SchoolDetail` meta_key/meta_value rows. Relevant to any settings-panel improvement — pattern-level. |
| Essentials | laravel-dompdf (already in use), spatie/laravel-backup (ops, already known) | — | Nothing new. |
| Plugins | Mostly Filament-* plugins (wrong layer). Non-Filament: **Laravel Notify / Toastie / TALL Toasts (usernotnull/tall-toasts, 573★, MIT ✔)** — toast UX patterns; **Larapex Charts (ArielMejiaDev/larapex-charts, 303★, MIT ✔)** — chart class→JS bridge ( mary's pattern is cleaner). | MIT ✔ | Reference for toast/KPI/notification UX; KlassApp already has its own toast styles. |
| Utility | **wire-elements/spotlight** re-confirmed (MIT, LICENSE.md (c) 2021 Philo Hermans ✔ — round 1 finding upgraded to file-verified). filament-shield = Filament-only (reject). | MIT ✔ | Spotlight stays the top command-palette candidate. |
| Administrator | (round 1 covered) — nothing new surfaced | — | — |

---

## Deep pass: concrete UI/UX patterns vs KlassApp's actual implementation

Grounded in KlassApp's real current surfaces (verified in-repo this session): Chart.js **2.9.3 loaded via `<script src="asset('js/Chart.min.js')">`** in `admin/dashboard/dashboard.blade.php`, `_eot-kpi-card.blade.php` (tabbed Chart.js panel), KPI cards in fees/marks/approvals/messages; 12+ hand-rolled per-role sidebars (`menu.blade.php`, 275 lines for admin alone) with Alpine accordions + localStorage; role gates via 20 `MustBe*` middleware files keyed on **hardcoded `usergroup_id` integers** (1/3/5/6/10/11); **no permission UI anywhere** (no spatie/laravel-permission, no role-management view).

### 1. KPI/stat cards

- **KlassApp now:** `ds-kpi-card` (hardcoded SCSS) + per-page ad-hoc cards; chart panels get tabs and canvas but **no trend deltas, no "vs last term", no sparklines**.
- **Best-in-class patterns found:**
  - **BladewindUI `statistic`** (MIT, file-verified ✔ — the single most relevant component examined): `tone` prop (neutral/positive/negative/warning/info) with the *library owning the color map* so a stat can't read as warning on one page and description on another; `direction` (up/down/flat) + **`invertDirection`** — "for metrics where down is good — arrears, churn" (exactly KlassApp's fee-arrears case!); optional `progress` bar *in place of* the note; `hint` on hover; config-driven defaults per project. The component comment literally describes KlassApp's failure mode: "the consuming app had a copy of it per stat card and they drifted."
  - **mary `Stat`** (daisyUI-dependent, pattern only): value/title/description/icon/color + daisyUI tooltip slots; simpler, less thoughtful than Bladewind's.
- **Concrete KlassApp adaptation:** add tone/direction/invertDirection semantics to `ds-kpi-card` (own implementation, KlassApp design tokens) — arrears cards flip direction, pass-rate cards get tone. Follow-up decision.

### 2. Data tables

- **KlassApp now:** hand-built Blade tables across admin views; no consistent sort/filter/search/empty-state system; PowerGrid's **hot zones (`pg-tbody`, `pg-pagination`, `pg-filters` — Livewire DOM isolation during updates)** is the pattern that prevents table re-render flicker — KlassApp's chart-panel scripts mutate canvases directly and have had re-render bugs (clearRect calls visible in dashboard JS).
- **Best-in-class:** PowerGrid (MIT ✔, 1694★): column sorting, filters + global search, **column summaries (Sum/Count/Avg) in the footer** (direct fit for marks/fees tables), queued XLSX/CSV export, inline-edit plugins. BladewindUI `table`: config-first props, built-in searchable/empty-state-as-component.
- **Concrete KlassApp adaptation:** the two realistic routes — adopt PowerGrid for the heavy admin lists (marks, fees payments, students), or lift specific patterns (footer summaries, DOM-isolated update zones, empty-state component) into KlassApp's own tables. Adoption requires Tailwind v4 + `.npmrc` + design-system compatibility checks. Follow-up decision.

### 3. Charts

- **KlassApp now:** Chart.js **2.9.3** (2019-era version!) via static script tag, chart configs written inline per page in `<script>` blocks, per-page canvas IDs, manual clearRect. Chart.js 3+/4 is a breaking upgrade; the current set-up predates the design system.
- **Best-in-class patterns:**
  - **mary `Chart`** (pattern): the Livewire bridge — `wire:model` entangled settings, `x-init(){ new Chart($refs.chart, this.settings) }`, `wire:key` uuid for re-render safety. ~25 lines, framework-quality.
  - **livewire-charts** (MIT but slowing): same idea, heavier.
  - **Laravel Trend** (MIT, adoptable): the *data* side — SQL-side date bucketing with gap-filling so charts don't hand-stitch arrays in JS.
- **Concrete KlassApp adaptation:** (a) a tiny shared `<x-chart>` Blade component (mary's pattern, Chart.js 4) replacing per-page inline scripts; (b) Laravel Trend for the fee-trend/attendance series. Follow-up decision — and Chart.js 2.9→4 is its own small migration task.

### 4. Admin roles/controls

- **KlassApp now (verified):** role authorization = 20 `MustBe*` middleware classes comparing hardcoded `usergroup_id` ints (1040 lines total middleware); staff role assignment presumably via the staff forms; **no permission matrix, no settings panel for roles, no ability to delegate subsets of permissions** (relevant to the deputy-admin parity work already documented in `docs/toshi-deputy-admin-audit.md`).
- **Patterns found:** Filament Shield (spatie permission UI) — Filament-only, rejected at the framework level; **spatie/laravel-permission** itself never appeared as a project page but is the ecosystem standard — relevant only as a future architecture decision (KlassApp's usergroup enum is load-bearing; migration is a real project, not an enhancement).
- **Honest verdict:** the survey surfaced **no non-Filament permission-UI reference worth copying**. The KlassApp-native path is the deputy-admin/parity work already scoped in-repo. No new action from this research.

### 5. Sidebars (round 1 follow-through)

Round 1 findings stand (menu-as-data à la Tablar; icon-rail + flyout à la Bladewind/Tabler; command palette via Spotlight). Round 2 additions:

- **BladewindUI `sidebar`** (examined in full): props `collapsible`, `collapsed`, `persist`, `persistGroups`, `multipleActive`, `storageKey` (namespaced `bladewind:sidebar:{name}`), `closeOnNavigate`, mobile=drawer with automatic drawer wiring — i.e. exactly the feature set KlassApp's `menu.blade.php` hand-implements per group with raw Alpine, but as one tested component. KlassApp's `sidebarActive()` lives in a `@php` block re-imported via partial — the pattern to retire in any refactor.
- **luvi-ui** ships no sidebar component (component list verified) — Luvi's value is the card/dialog/select layer, not nav.

---

## Consolidated adoptable-vs-reference table (file-verified licenses only)

| Project | License file | KlassApp-compatible? | Verdict |
|---|---|---|---|
| flowframe/laravel-trend | MIT ✔ | ✔ (no UI deps) | **Adoptable candidate** — chart/KPI data layer |
| wire-elements/spotlight | MIT ✔ (LICENSE.md) | ✔ (Alpine) | **Adoptable candidate** — command palette |
| Power-Components/livewire-powergrid | MIT ✔ | Livewire+Tailwind ✔ (needs v4 check) | **Adoptable candidate** — admin tables, after compat audit |
| bladewindui/ui | MIT ✔ | Blade+Alpine+Tailwind ✔ | Reference (or selective component adaptation with attribution) — statistic/table/sidebar patterns |
| luvi-ui/laravel-luvi | MIT ✔ | Blade+Alpine+Tailwind ✔ | Reference/copy-paste-compatible — card, select, sheet patterns |
| mary (robsontenorio) | MIT (license.md) ✔ | daisyUI dep ✖ | Reference only — Stat/Chart component API design |
| tallstackui | MIT ✔ | Blade+Alpine+Livewire ✔ | Reference — interaction patterns quality bar |
| turbine-ui-core | **no LICENSE file** ✖ | Blade+Tailwind ✔ | **Reference-only** (a11y patterns) — do not copy code |
| Academico | **no LICENSE file** ✖ (composer says MIT) | Filament ✖ | **Reference-only** — reports/scheduling data-model patterns |
| flexiwind | MIT ✔ | Tailwind v4 ✔ | Watch (young, 30★) |
| z-song/laravel-admin | MIT ✔ | unmaintained since 2023 | Reject |
| asantibanez/livewire-charts & calendar | MIT ✔ | slowing | Reject/deprioritize |
| Tablar/Tabler, TailAdmin Laravel | MIT ✔ | Bootstrap / Inertia+Vue respectively | Pattern reference only |

---

## Follow-up decision register (updated, ranked)

1. **Timetable materialization** (Academico S1/S2/S4) — recurring slot → per-session records; largest item; needs design session.
2. **Report column/weighting as data** (R1) — per-school report structure definition table.
3. **Marks-entry grid** (R5 + PowerGrid UX floor) — one class × all subjects inline grid, KlassApp-built.
4. **Menu-as-data sidebar refactor** — one renderer + per-role arrays; retire 12 copy-pasted `menu.blade.php` files and the `@php`-block helper.
5. **`ds-kpi-card` tone/direction upgrade** (BladewindUI `statistic` pattern, incl. `invertDirection` for arrears) + **`<x-chart>` shared component** (mary pattern, Chart.js 4) + **Laravel Trend** for chart data.
6. **Command palette** (Spotlight pattern) — alongside or after sidebar work.
7. **Nursery skills/competency model** (R2) — fills the empty `nurseryAssessments` seam.
8. **Room entity + room-conflict check** (S3); **AcademicTerm order column** (S5).
9. **Chart.js 2.9.3 → 4 migration** — prerequisite-adjacent to item 5's chart component.
10. Minor: orphaned `modern.blade.php` report template (register or remove); precomputed per-school KPI cache (R3).

**Standing practices:** survey madewithlaravel.com (all relevant categories) as a first research step for future UI/UX tasks — recorded in `AGENTS.md`.

**Attribution:** any future adaptation of code (not just patterns) from MIT-licensed projects above must retain the upstream copyright/license notice per MIT terms. Academico and turbine-ui-core are excluded from code adaptation entirely (no LICENSE file present) — pattern reference only.
