# Package architecture proposal

**Status: proposal only. Nothing in this document is built.** Phase 1 for owner review. Do not merge on the strength of this file, and do not start the extraction until the owner approves the shape.

**Product rule this proposal serves:** KlassApp is a global product (nursery, primary, O level, A level, any country). Country-specific and curriculum-specific behaviour must be data a package supplies, not code the core assumes.

**Related work this proposal builds on (do not duplicate):**

| Work | What it already decided | What this proposal adds |
|---|---|---|
| [docs/proposals/2026-10-02-a-level-combinations.md](../proposals/2026-10-02-a-level-combinations.md) (merged [#936](https://github.com/KlassApp-Foundation/KlassApp/pull/936)) | Curriculum packs as data: a pack owns subjects, combinations, and aggregate rules. UACE is one pack. The five tables are **not in the schema today**. | How a pack is shipped and enabled as a package, once that schema exists. |
| Open [#1118](https://github.com/KlassApp-Foundation/KlassApp/pull/1118) (`docs/product/marks-flow.md`, not on `main` yet) | Grading defaults, division rules, ranking, exam-type contribution, term structure, and report templates should become package configuration. The results service and the release gate stay core. | The package mechanism those notes point at ("designed elsewhere"). |
| [docs/architecture/toshi-shape-2026-09.md](toshi-shape-2026-09.md) | How a Toshi Skill is added today, and how `McpWriteGate` classifies connector tools. | A registry so a package can add a Skill or a connector without editing the orchestrator by hand. |
| [docs/plans/toshi-direct-google-integrations-and-custom-connector-registry-plan.md](../plans/toshi-direct-google-integrations-and-custom-connector-registry-plan.md) | Custom connectors stay curated manifests with a mandatory write-gate declaration. Google Classroom is not on `main` (open [#728](https://github.com/KlassApp-Foundation/KlassApp/pull/728) only). | Connectors as one extension point among several, under the same security review. |

Audited against `main` at `073e2322`. Class names and paths below are from that tree.

---

## 1. What is already modular

These seams exist. None of them is a community package system yet.

### 1.1 One real Laravel package

`packages/toshi-ui` (`composer.json` name `klassapp/toshi-ui`) is a path repository (`composer.json` `repositories`) and is auto-discovered (`extra.laravel.providers` → `KlassApp\ToshiUi\ToshiUiServiceProvider`). The provider only loads and publishes views and CSS. Its own description says presentation only, no business logic. It is the convention later packages should copy. It does not show how to plug in a domain.

### 1.2 In-app service providers that are not packages

`config/app.php` registers three application providers that bind a container alias and nothing else:

| Provider | Binds |
|---|---|
| `App\Schoolplus\StudentServiceProvider` | `'student'` → `App\Schoolplus\StudentService` |
| `App\Calendar\CalendarServiceProvider` | `'calendar'` → `App\Calendar\CalendarService` |
| `App\Conference\ConferenceServiceProvider` | `'conference'` → `App\Conference\ConferenceService` |

`Gegok12\Inventory\InventoryServiceProvider` is commented out in the same file. These are leftovers of an older split, not an extension API. A third party cannot drop one in.

Framework providers in the same array (`App\Providers\AppServiceProvider`, `AuthServiceProvider`, `RouteServiceProvider`, `EventServiceProvider`, `BroadcastServiceProvider`) are core. `App\Providers\NovaServiceProvider` is commented out.

### 1.3 Per-school feature toggles

`school_feature_toggles` (`database/migrations/2026_07_07_050000_create_school_feature_toggles_table.php`) is unique on `(school_id, feature)`, default `enabled = false`.

`App\Services\FeatureToggleService` is the only writer that enforces a catalogue:

- `availableFeatures()` returns exactly three keys: `toshi`, `whatsapp`, `schoolpay`.
- `isEnabled()` returns false when no row exists.
- `setEnabled()` rejects any key outside that array.

Callers: `App\Livewire\Superadmin\Settings\FeatureToggles`, `App\Ai\Tools\Superadmin\ToggleSchoolFeatureTool`.

`Database\Seeders\Phase5FeatureTogglesSeeder` writes three **other** keys (`roster`, `report_generation`, `bulk_attendance`) straight to the table. `setEnabled()` would refuse those keys. The table can store a package key; the service cannot yet admit one.

### 1.4 Connectors (catalog + per-school credentials)

`config/toshi.php` `mcp_connectors` lists three connectors: `slack`, `notion`, `google-drive`. Each entry has endpoint, OAuth, `read_tools`, `write_tools`, `enabled` (env flag, off unless set), and an optional `skill` class. Only Slack sets a skill: `App\AiAgents\Skills\SlackSkill`.

`App\Services\Toshi\McpWriteGate` fail-closes: an unknown connector or an unknown mode is treated as a write. Modes are `deny`, `classify`, `allowlist`. `config/toshi.php` `mcp_write_gates.master_switch` defaults off.

`App\Models\SchoolMcpConnector` stores one row per school per `connector_type`, with encrypted credentials, `trust_level`, `write_mode`, and tool allow/deny lists. `resolveTokenForRequest()` always scopes by `school_id`. `App\Models\McpConnectorTokenRefreshService` refreshes that row.

`routes/ai.php` registers the Slack MCP client with `Mcp::registerClient('slack', …)` and a local mock `App\Mcp\Servers\SpikeSlackMockServer`. There is no loop over the catalog. Adding a connector means editing `config/toshi.php` and `routes/ai.php`.

WhatsApp is **not** in this catalog. It is a core channel (section 2).

### 1.5 Grading data is per school; the default scale is not

`App\Models\Academics\SchoolGradingSystem` stores `grade`, `points`, `min_score`, `max_score`, `remark` per `school_id` + `standard_id`, and uses `App\Models\Concerns\SchoolScopedRouteBinding`.

`App\Services\GradingSystemService::grade()` and `aggregates()` read those rows. `App\Http\Controllers\Admin\Academics\SchoolGradingSystemController` and the Toshi tools `SetGradingScaleTool`, `ViewGradingScaleTool` edit them. A school can already have its own bands.

The **default** bands are Uganda-specific and live in `config/grading_uganda.php`. `App\Helpers\GradingHelper::scaleConfig()` reads that file. `seedGradingForSchool()` / `seedAllGradingForSchool()` copy it onto every matching standard. `levelTypeForStandard()` maps names such as "Primary" and "Senior" onto `nursery`, `primary`, `o-level`, `a-level`. `SeedDefaultGradingTool` and `App\AiAgents\Skills\GradingSkill` call this path. `GradingSkill::instructions()` still describes a single UNEB A–E scale, which does not match the four scales in the config file.

This is the smallest extraction. See section 5.

### 1.6 Toshi tools (a pattern, a hardcoded list)

Skills under `app/AiAgents/Skills/` follow one pattern: `Agent` + `HasTools` + `Promptable`, reached through a `RouteTo*SkillTool`. The checklist is [toshi-shape-2026-09.md](toshi-shape-2026-09.md) §1.3.

`App\AiAgents\ToshiOrchestrator::tools()` returns nine concrete tools, including `RouteToSlackSkillTool`. `config/toshi-skills.php` documents a shorter list and says the orchestrator reads it. It does not. Runtime registration is the method body.

Leaf tools use `App\AiAgents\Concerns\AuthorizesToshiAction` and, for writes, `App\AiAgents\Concerns\ConfirmsBeforeWrite`. MCP writes use `App\Ai\Tools\Toshi\ApprovableMcpTool` plus `App\Services\Toshi\Concerns\AuditsMcpToolCalls`.

A package cannot add a tool without editing `ToshiOrchestrator` (or a role agent such as the teacher path described in the shape audit).

### 1.7 Report card templates (a const, two views)

`App\Services\StudentReportCardService::TEMPLATES` is the list: `formal` → `admin.marks.report-templates.formal`, `warm` → `admin.marks.report-templates.warm`. `schools.report_template` selects one. `App\Http\Controllers\Admin\ReportCardsController` delegates `generatePdf()` to the service and validates the template key against that const. `App\Http\Controllers\Teacher\ReportCardsController` does not let a class teacher pick a template.

There is no template registry. A third layout means editing the const and adding a Blade file in the app.

---

## 2. What is hard-wired

These are the behaviours that should become package data. They are in core today.

### 2.1 Uganda grading defaults and UNEB division

- `config/grading_uganda.php` — primary nine-band, O-level A–E, A-level points, plus the nursery scale in the same file.
- `GradingHelper::formatAggLabel()` — letter map `D/C/P/F` for aggregate points.
- `StudentReportCardService` — closure `$divisionScale` with thresholds 12 / 24 / 28 / 32 / U (the same bands [#1118](https://github.com/KlassApp-Foundation/KlassApp/pull/1118) says must become a per-curriculum rule).
- `App\Models\Subject::sortByReportOrder()` — a fixed list (English, Mathematics, Religious Education, Literacy, Science, Social Studies). [#936](https://github.com/KlassApp-Foundation/KlassApp/pull/936) already says a pack should own subject order.

Per-school rows in `school_grading_systems` stay. The Uganda file and the division closure do not.

### 2.2 Curriculum seeding (Uganda class lists and subject codes)

`App\Services\SchoolCategorySeeder` runs only for UNEB schools (`school_category` step). It seeds:

- Nursery: Baby Class, Middle Class, Top Class
- Primary: Primary One–Primary Seven
- O level: Senior One–Senior Four
- A level: Senior Five, Senior Six

and then calls `GradingHelper`. The class comment says an empty or unknown category seeds nothing.

`App\Services\AcademicSetupService::defaultClassesAndSubjects()` repeats those section names and adds subject codes (primary `013`/`007`/`015`/`010`, O-level `112`/`456`/…, A-level including the `840` and `220` collisions [#936](https://github.com/KlassApp-Foundation/KlassApp/pull/936) already flagged). There is no `CurriculumPack` model and no `curriculum_packs` table. The [#936](https://github.com/KlassApp-Foundation/KlassApp/pull/936) schema is the target; these two classes are the data that should move into the first pack.

`App\Services\OnboardingEngine::saveCurriculum()` accepts only `uneb`, `cambridge`, `montessori`, `other` (`App\Onboarding\Steps\Steps\CurriculumStep` shows the same four labels, and `App\Http\Controllers\Admin\StandardController` also lists `ib`). `App\Livewire\AgentToshi` and `App\Livewire\ManualOnboardingWizard` still initialise `$curriculum` to `'uneb'`. The column itself was made nullable with no default in `database/migrations/2026_08_03_235031_make_schools_curriculum_nullable.php`. The form default and the seeder are what still assume one board.

### 2.3 Uganda EMIS and exam-candidate classes

- `schools.ministry_code` — required for Uganda in `App\Services\Superadmin\SchoolService` and `App\Onboarding\Steps\Steps\EmisStep`. `OnboardingStepsService::applicableSteps()` drops the `emis` step unless `registration_country` is Uganda.
- `schools.uneb_center_number` — `App\Onboarding\Steps\Steps\UnebCenterStep`. The step applies only when `curriculum` is `uneb`. Dashboard copy already uses neutral labels (`Registration code`, `Exam centre number`) via `OnboardingStepsService::NEUTRAL_LABELS`. `curriculumStepLabels()` is empty.
- `OnboardingEngine::isCandidateClass()` — hardcoded P.7, S.4, S.6. It gates `board_registration_number`. The method comment says this is intentionally hardcoded.

A school in another country can skip EMIS. The field, the step, and the candidate-class list still live in core, and the candidate check cannot be swapped for another exam system's years.

### 2.4 WhatsApp commands

Parents, staff, and students reach WhatsApp through `App\Http\Controllers\Api\WhatsAppController::routeInbound()`, which matches keyword lists in one method (`menu`, `grades`, `fees`, `attendance`, `report`, and the role branches under it). Menus are built beside that method and in `App\Services\WhatsApp\ParentLinkRequestService` (`FEES`, `GRADES`, `ATTENDANCE`). Transport is `App\Services\WhatsAppBusinessService` and `App\Services\OutboundWhatsAppService` (Meta Cloud API). The `whatsapp` feature toggle wraps the channel. The keyword table is not a registry.

`App\Services\WhatsApp\WhatsAppToshiChannelService` and `App\AiAgents\WhatsApp\WhatsAppWriteExclusion` are safety rails for free-form text. They stay in core. Commands are the extension point.

### 2.5 Payments

`schoolpay` is the only payment feature key. `App\Http\Controllers\Api\SchoolPayWebhookController` verifies a School Pay receipt signature. `App\Models\SchoolPayTransaction` stores `schoolpay_receipt`. `AgentToshi::handleSchoolPay()` talks to that provider. There is no gateway interface. Another provider means another controller written the same way, in core.

### 2.6 Dashboard cards

`resources/views/admin/dashboard/dashboard_v2.blade.php` hardcodes the quick actions and the KPI tiles, and mounts one Livewire card: `App\Livewire\Admin\WhatsappDashboardCard` (admin usergroup only). Teacher and student dashboards (`resources/views/teacher/dashboard/dashboard.blade.php`, `resources/views/student/dashboard/dashboard.blade.php`) list `x-ds-kpi-card` entries in the Blade file. Nothing can register a card.

### 2.7 Permissions

`App\Providers\AuthServiceProvider::$policies` is a fixed map (`SchoolPolicy`, `SectionPolicy`, `StandardLinkPolicy`, and the rest under `app/Policies/`). Gates such as `event` compare `school_id` inline. A package that adds a model has nowhere to register a policy except by editing this class.

---

## 3. Extension points

Each point is a contract the core owns and a package implements. The core does not switch on country or curriculum inside the contract.

| Point | Core keeps | A package provides | Enabled how |
|---|---|---|---|
| **Grading scales** | `SchoolGradingSystem` rows, `GradingSystemService` reads, per-school edits via `SetGradingScaleTool` | Default bands and level keys for one curriculum. Later: the division rule [#1118](https://github.com/KlassApp-Foundation/KlassApp/pull/1118) describes (thresholds, subjects counted, missing-core behaviour) | School `curriculum` value, default off when no package matches |
| **Curriculum packs** | The [#936](https://github.com/KlassApp-Foundation/KlassApp/pull/936) tables, once built: sections, students, marks stay school rows | Seed content: subjects, combinations, aggregate rule, report subject order. Replaces `SchoolCategorySeeder` and `AcademicSetupService` lists for that pack | School picks a pack at the category step. Core seeds nothing when no pack is selected |
| **Report card templates** | `StudentReportCardService::generatePdf()` pipeline, `schools.report_template`, the release gate from [#1118](https://github.com/KlassApp-Foundation/KlassApp/pull/1118) | A view plus a key and label, registered instead of editing `TEMPLATES` | School setting, same as today. Unknown keys 404, as `previewTemplate()` already does |
| **Connectors** | `SchoolMcpConnector`, `McpWriteGate`, `ApprovableMcpTool`, audit | A catalog entry: endpoint, OAuth, `read_tools`, `write_tools`, write-mode, optional Skill class. Same shape as the `slack` entry in `config/toshi.php` | Per school, row in `school_mcp_connectors`, plus the connector's env `enabled` flag. Default `deny` |
| **Payment gateways** | Fee balances, `FeePayment`, receipts stored per school | Webhook verification, provider reference, the toggle key | `FeatureToggleService` key, default off, same as `schoolpay` |
| **WhatsApp commands** | Transport, signature check (`VerifyWhatsAppWebhookSignature`), opt-in, `WhatsAppWriteExclusion`, role of the linked user | A keyword list, the role it applies to, and a handler that receives the already-resolved `WhatsAppUser` | School `whatsapp` toggle, then the command's own flag. Core commands (`menu`, `optout`, `help`) stay |
| **Toshi tools** | Orchestrator loop, `AuthorizesToshiAction`, `ConfirmsBeforeWrite`, the no-AI-by-default rule | A Skill class and its `RouteTo*SkillTool`, or a leaf tool on an existing Skill | School `toshi` toggle and `toshi_mode`. A package must not turn AI on |
| **Dashboard cards** | The dashboard shell in `dashboard_v2.blade.php` | A Livewire component (the `WhatsappDashboardCard` shape) plus a label and the roles that may see it | School feature flag. Cards render only for the signed-in school |

Contracts are PHP interfaces discovered from package service providers, not a second config dialect. `config/toshi-skills.php` is the cautionary example: a config map that runtime ignores.

**Stays in core, not an extension point:** tenancy and `school_id`, authentication, the policy/gate layer, student and section identity, KLS numbers, the marks release gate, WhatsApp transport, and the rule that AI is opt-in per school.

---

## 4. How a package is built, registered, versioned, enabled, and tested

### 4.1 Build

Follow `packages/toshi-ui`:

- Directory `packages/<name>/` with `composer.json` `"type": "library"`, PSR-4 under `src/`, `extra.laravel.providers` pointing at one service provider.
- Path repository entry in the application `composer.json` for packages that ship in this repo. Community packages are separate Composer packages, same provider shape.
- The provider's `register()` binds the extension (a grading provider, a command, a card). `boot()` may load views, translations, and migrations. It does not query other schools and it does not enable itself.
- No edits to `ToshiOrchestrator`, `WhatsAppController::routeInbound`, or `AuthServiceProvider::$policies` for a routine package. Those classes grow a registry; the package calls the registry.

Suggested first interfaces (names for the design, not code in this PR):

- `GradingScaleProvider` — `curriculumKey()`, `scales(): array` in the `grading_uganda.php` shape.
- `CurriculumPackProvider` — pack identity and seed payload matching the [#936](https://github.com/KlassApp-Foundation/KlassApp/pull/936) tables.
- `ReportTemplateProvider` — key, label, view name.
- `ConnectorProvider` — the `mcp_connectors` array shape, including `read_tools` and `write_tools`.
- `PaymentGatewayProvider` — feature key, webhook handler.
- `WhatsAppCommandProvider` — keywords, roles, handler class.
- `ToshiToolProvider` — Skill class to append to the orchestrator tool list.
- `DashboardCardProvider` — Livewire component class, roles.

A package may implement one of these. The UNEB proof of concept implements only `GradingScaleProvider`.

### 4.2 Register

Composer package discovery loads the provider (already how `ToshiUiServiceProvider` loads; `composer.json` `dont-discover` is empty). The provider registers its implementation on a core registry. Registration is not enablement.

Policies: the provider calls `Gate::policy()` for models it owns. It does not replace `SchoolPolicy` or widen an existing gate.

### 4.3 Version

Semantic versions, as `klassapp/toshi-ui` already declares `"version": "1.0.0"`. The application constrains each official package in `composer.json`. A breaking change to a core interface is a major version of core, with the previous interface left in place for one minor so installed packages keep booting. Packages pin the core contract they were tested against.

Package migrations are additive, safe to run twice, and never `UPDATE` or `DELETE` rows of another school. They do not alter core tables (`schools`, `users`, `student_academics`) in the first version of this system. Country fields such as `ministry_code` move only when that package's own extraction is approved, in its own PR.

### 4.4 Enable per school

Installing a Composer package does not turn it on for any school.

`FeatureToggleService::setEnabled()` must grow from a hardcoded three-key list to "core keys plus keys declared by registered packages". Until that change, a package key cannot be toggled from the superadmin screen or from `ToggleSchoolFeatureTool`. Default remains off: `isEnabled()` is false when no row exists.

Curriculum and grading are selected by the school's `curriculum` value (already `uneb` / `cambridge` / `montessori` / `other`), not by a second boolean. A value with no registered provider seeds no classes and no bands. The Livewire defaults of `'uneb'` on `AgentToshi` and `ManualOnboardingWizard` are part of the extraction: a new school starts with curriculum unset, which the nullable column already allows.

AI packages follow the existing rule: new schools stay `toshi_mode` `onboarding` or `preview` with `toshi_enabled = 0`. A package cannot set those.

### 4.5 Test

Every package ships tests that run under `bash scripts/test-guard.sh` when the package is in this repo. The proof is cross-tenant, not only "the seeder inserted rows":

- A school with the package enabled receives its defaults.
- A school with the package disabled, or a different curriculum, does not.
- Another school's user receives 404 on any route the package adds (`SchoolScopedRouteBinding` or an equivalent policy).
- A write tool still goes through `ConfirmsBeforeWrite` or `ApprovableMcpTool`.

Community packages outside the repo are tested by their authors against the published contract. They are not installable on the hosted product until the review in section 4.6.

### 4.6 Core guarantees a package gets

The core, not the package, guarantees:

- **School scoping.** Every query, cache key, and job carries `school_id`. Route models that belong to one school use `SchoolScopedRouteBinding`. A request never sets `school_id` or `user_id` on an existing row.
- **Identity.** Lookups use `id` (and `school_id` when the row is tenant data). Display name is not an identifier.
- **Permissions.** The package asks `Gate` or `AuthorizesToshiAction`. It does not invent a parallel role check. Positive equality on status fields (`status = 'active'`), never `!=`.
- **Section vs standard.** "This class" is `section_id`. `standard_id` is a grading band.
- **AI off until a person enables it** for that school.
- **Security review** before a community package can be enabled on the hosted product. The same review that opens supported self-hosting is the bar for "anyone can install this on klassapp.xyz". A package that has not been reviewed can still be written and run by a self-hoster who accepts that risk; the hosted product does not load it.
- **No raw production writes.** Package installers and seeders are committed code. They do not ship with a one-off `UPDATE`.

The hosted product does not run package code from a URL. The connector plan already rejected raw remote manifests. This proposal keeps that: a package is a reviewed Composer dependency, or a path package in this repo.

---

## 5. What should become a package, and the first extraction

Order is by how tangled the code is, not by product importance. Marks, release, and tenancy stay in core ([#1118](https://github.com/KlassApp-Foundation/KlassApp/pull/1118)).

| Later package | Moves out of core | Waits on |
|---|---|---|
| **UNEB grading defaults** (first) | `config/grading_uganda.php`, `GradingHelper::scaleConfig()` / `seedAllGradingForSchool()` level list | Nothing. Storage already exists |
| **Uganda curriculum pack** | Section and subject lists in `SchoolCategorySeeder` and `AcademicSetupService`; `Subject::sortByReportOrder()` list | [#936](https://github.com/KlassApp-Foundation/KlassApp/pull/936) tables. Do not invent a second schema |
| **Uganda registration fields** | `EmisStep`, `UnebCenterStep`, Uganda branch in `SchoolService`, `OnboardingEngine::isCandidateClass()` | Grading package, so "candidate class" can be pack data instead of P.7/S.4/S.6 in core |
| **Division and report columns** | `$divisionScale` in `StudentReportCardService`, AGG label map in `GradingHelper::formatAggLabel()` | The grading package, and the [#1118](https://github.com/KlassApp-Foundation/KlassApp/pull/1118) results service so there is one caller |
| **School Pay** | `SchoolPayWebhookController`, `SchoolPayTransaction`, `handleSchoolPay()` | Gateway interface. Not first: money movement |
| **Extra report templates, commands, cards, connectors** | As schools ask for them | The matching registry. Slack stays the reference connector |

Cambridge, Montessori, and `other` are labels on `CurriculumStep` today. They get packages when someone writes them. Core does not grow a scale file per board.

### First proof of concept: `packages/grading-uneb`

**Goal:** a school whose curriculum is UNEB still receives today's primary, O-level, A-level, and nursery defaults. A school with no matching provider receives no bands from core. Core no longer contains a Uganda scale file.

**In this extraction**

1. A `GradingScaleProvider` registry in core. `GradingHelper::scaleConfig()` reads the registry. With no provider registered, `seedAllGradingForSchool()` writes nothing.
2. `packages/grading-uneb`, path repository, one service provider, the arrays now in `config/grading_uganda.php`.
3. The provider applies only when the school's `curriculum` is `uneb`.
4. Tests: UNEB school gets the current primary `1`–`9` bands; a school with `curriculum` null or `cambridge` does not; a second school's rows are untouched.

**Out of this extraction**

- Division thresholds, report subject order, class-list seeding, EMIS, candidate classes, templates.
- Any change to `SchoolGradingSystem` columns.
- Turning the feature on for existing schools. Schools that already have rows keep them. The seeder stays idempotent (`updateOrCreate` on school + standard + grade, as `GradingHelper` does now).

**Estimate:** about three working days, two small PRs.

| PR | Work | Days |
|---|---|---|
| 1 | Registry + `GradingHelper` reads it. A test that fails while `config/grading_uganda.php` is still the source and passes once the helper has no Uganda data of its own | 1.5 |
| 2 | `packages/grading-uneb`, Composer path entry, UNEB vs non-UNEB seed tests under the guarded suite | 1.5 |

That is the proof that a community package can plug in. The curriculum-pack move is the next extraction, and it should not start before [#936](https://github.com/KlassApp-Foundation/KlassApp/pull/936)'s tables exist.

---

## Dependencies and order

- The grading-scale registry plugs into Grok A's `ResultsService` from [#1118](https://github.com/KlassApp-Foundation/KlassApp/pull/1118) (`App\Services\Results\ResultsService`, proposed in `docs/product/marks-flow.md`). The registry supplies the scale; `ResultsService` stays the one place that computes grades, totals, positions and divisions.
- The UNEB extraction (`packages/grading-uneb`) starts only after [#1118](https://github.com/KlassApp-Foundation/KlassApp/pull/1118) is merged.

## 6. Risks

### Security

A package runs in-process. It can read anything the application can read unless the core registry is the only API it is given and review rejects packages that query past it. Review checks: no unscoped `Model::query()`, no `school_id` taken from the request body, no credential logged, connector writes declared to `McpWriteGate`, WhatsApp handlers unable to bypass `WhatsAppWriteExclusion`, no code that sets `toshi_enabled`. Hosted installs are a reviewed allow-list. Composer discovery of an unreviewed package on the hosted app is a failure of review, not a feature.

`McpWriteGate` already fail-closes on unknown connectors. Package connectors must stay in that path. A package that calls a remote tool directly is out of policy.

### Upgrades

Core and packages will not ship on the same day. Interfaces need a deprecation window of one minor. A package that registers nothing must leave the app bootable: empty registry, schools with no provider, toggles default off. Package migrations that are not idempotent will break the "safe to run twice" rule and are rejected. Pinned versions mean a security fix in core does not wait on every community package, and a broken package version can be unpinned without a core rollback.

`packages/toshi-ui` publishes assets into `public/` and `resources/views/vendor/`. Domain packages should not depend on a local disk surviving a deploy. State goes to the database.

### Data isolation

The failure mode is a package query missing `school_id`, a cache key without the school, or a job dispatched without it. `SchoolScopedRouteBinding` covers HTTP binding only; jobs, commands, and exports need the same scope written down in the package guide and enforced in review. Seeders delete or rewrite only rows of the school they were given. National identifiers that core has already decided are global (for example a national learner number) stay a core decision; a package does not add a new global unique.

Toggles are per school. Enabling UNEB grading, School Pay, or a connector for one school must not change the neighbour. The Phase 5 seeder writing keys the service does not recognise is the current example of two mechanisms drifting; the registry should be the single catalogue so that cannot happen again.

---

## 7. What this proposal does not do

- No interfaces, providers, migrations, or config changes in the PR that adds this file.
- No claim that [#936](https://github.com/KlassApp-Foundation/KlassApp/pull/936) or [#1118](https://github.com/KlassApp-Foundation/KlassApp/pull/1118) are implemented. Packs are a design; the marks flow is an open proposal.
- No marketplace, no remote package URL, no self-hosting instructions. Self-hosting stays closed until the independent security review. The developer docs page that will point here is listed in the docs site structure proposal.
