# Stack upgrade plan (T-044)

**Status:** plan only. Draft. From `main` at `2aaf327b` (9 Nov 2026).
**No file in this plan has been changed:** no `composer.json`, no `composer.lock`, no `package.json`, no lockfile, no code, no open PR.
**Scope:** PHP, Laravel, Laravel AI SDK, Vue, Vite, Blade, Tailwind, Node, the Laravel packages, the PDF path and the database driver.

Versions checked on **16 November 2026**. Sources are listed at the end. Where a version could not be confirmed from an official source, the cell says **could not confirm** rather than a guess.

---

## 1. Recommended order, and why

| Phase | What moves | Why this position |
|---|---|---|
| **0. Baseline** | No version change: add a PHP 8.5 job and a Node 26 job to CI, record the current test and PDF baselines | Proves the target runtimes before any package moves |
| **1. PHP 8.5 + Laravel 13.42 + patch-level Composer packages** | PHP 8.4 → 8.5; `laravel/framework` 13.38 → 13.42; Sanctum, Tinker, Socialite, Nightwatch, Backup, Medialibrary, Model States, Predis, Dompdf, PHPUnit toolchain | Everything else requires the runtime and framework to be current first. Low risk, one lockfile change |
| **2. Front end and Node** | Node 24 → 26 LTS; Vite 8.1 → 8.3; laravel-vite-plugin 3.1 → 3.2; plugin-vue 6.0.8 → 6.0.11; Vue 3.5.40 → 3.5.45; Tailwind 4.3.3 → 4.3.5; PostCSS; **remove the unused `vue-router` 3**; Playwright 1.62 → 1.65 | Independent of PHP. Doing it before Livewire means asset builds are settled before the biggest UI change |
| **3. Laravel AI 1.4 + Laravel MCP 1.2** | AI 0.10.2 → 1.4.0; MCP 0.8.2 → 1.2.0; the conversation-table migration | AI 1.0 **requires** MCP ^1.0 (`High` impact in its upgrade guide). They move together, before Toshi is exercised again |
| **4. Livewire 4.4** | Livewire 3.8.10 → 4.4.8; config keys; `wire:model` behaviour; `stream()` signature; endpoint prefix | Touches 52 components and 77 component tags. It is independent of AI, and doing it after the AI move keeps one large change per phase |
| **5. Filament Tables 5 and Laravel Excel 4** | `filament/tables` 3.3.54 → 5.11.4; `maatwebsite/excel` 3.1.x-dev → 4.0.6 (PhpSpreadsheet 5) | Isolated to four superadmin screens and the import/export classes. Highest per-file risk, lowest blast radius |
| **6. Database** | Stay on MySQL 8.4 LTS (latest 8.4.13); evaluate 9.7 LTS separately | The app and CI are on 8.4. 8.4 is supported to 2029 (premier) / 2032 (extended). No upgrade is needed for this stack move |

**Why not one big jump:** the AI/MCP move needs its own migration and its own test pass; Livewire 4 changes request behaviour on every Livewire screen; Filament 5 and Excel 4 are two and one major versions ahead of code that uses them lightly. Keeping them in separate phases means a failure has one cause and one rollback.

---

## 2. Current stack, as found in the repo

| Layer | Current | Where it is set |
|---|---|---|
| PHP | `^8.3` in `composer.json`; **8.4** in CI (`test-guard.yml`, `mysql-migrations.yml`) and on Laravel Cloud | composer, CI, Cloud |
| Laravel | **13.38.0** (`composer.lock`) | composer |
| Laravel AI SDK | **0.10.2** | composer |
| Laravel MCP | **0.8.2** | composer |
| Livewire | **3.8.10** | composer |
| Filament Tables | **3.3.54** | composer (only `filament/tables`) |
| Laravel Excel | **3.1.x-dev** (a branch pin, not a tag) | composer |
| PHPUnit | **12.5.40**; Paratest 7.20.0 | composer |
| Vue | **3.5.40** with `@vue/compat` **MODE 2**; 242 `.vue` files | `package.json`, `vite.config.js`, `resources/assets/js/app.js` |
| Vue Router | **3.6.5**, **not imported anywhere** in `resources/`, `public/js` or `*.js` | `package.json` only |
| Inertia / Pinia | not used | — |
| Vite | **8.1.5**; laravel-vite-plugin **3.1.3**; `@vitejs/plugin-vue` **6.0.8** | `package.json`, `vite.config.js` |
| Build output | `vite build` to `public/build`, then `vitepress build docs-site`; no Mix, no `public/hot` in production | `package.json`, `vite.config.js` |
| Blade | **982** `.blade.php` files; **20** anonymous components under `resources/views/components`; Livewire components embedded in Blade | `resources/views` |
| State library | none: no Vuex, no Pinia, no Inertia; component-local state | `package.json`, `resources/assets/js` |
| Test runner | PHPUnit (no Pest); Playwright for browsers; CI uses `actions/checkout@v4`, `shivammathur/setup-php@v2` (PHP 8.4), `actions/upload-artifact@v4` | `composer.json`, `package.json`, `.github/workflows` |
| Tailwind / PostCSS | Tailwind **4.3.3** via `@tailwindcss/vite`; PostCSS **8.5.23** | `package.json`, `vite.config.js` |
| Node | **24.15.0** locally; **24** on Laravel Cloud; CI does not install Node | local, Cloud env |
| Browser tests | Playwright **1.62.1** (`@playwright/test`) | `package.json`, `playwright.config.*` |
| PDF | `barryvdh/laravel-dompdf` **3.1.2**, Dompdf engine, **DejaVu** fonts in the report templates, `font_dir` `storage/fonts/`, `chroot` `base_path()`, remote images enabled | composer, `config/dompdf.php`, `resources/views/admin/marks/report-templates/*` |
| Database | MySQL **8.4** in CI; Laravel Cloud managed MySQL (engine version not exposed — **could not confirm**) | CI, Cloud |

---

## 3. Target versions, latest stable, support

| Layer | Current | Latest stable (16 Nov 2026) | Support / EOL | Source |
|---|---|---|---|---|
| PHP | 8.4 | **8.5.x** | 8.5: active to 31 Dec 2027, security to 31 Dec 2029. 8.4: active to 31 Dec 2026, security to 31 Dec 2028. 8.6 releases **19 Nov 2026** — not stable on the plan date | [php.net supported versions](https://www.php.net/supported-versions.php) |
| Laravel | 13.38.0 | **13.42.0** | 13: bug fixes Q3 2027, security to 17 Mar 2028; PHP 8.3–8.5 | [Laravel 13 releases](https://laravel.com/docs/13.x/releases), [framework release](https://github.com/laravel/framework/releases/tag/v13.42.0) |
| Laravel AI | 0.10.2 | **1.4.0** (1.0 on 23 Sep 2026) | Latest major only; supports Laravel 12–13, PHP ^8.3 | [laravel/ai releases](https://github.com/laravel/ai/releases), [UPGRADE.md](https://github.com/laravel/ai/blob/1.x/UPGRADE.md) |
| Laravel MCP | 0.8.2 | **1.2.0** | Latest major only; supports Laravel 12.41+/13, PHP ^8.2 | [laravel/mcp releases](https://github.com/laravel/mcp/releases), [UPGRADE.md](https://github.com/laravel/mcp/blob/main/UPGRADE.md) |
| Livewire | 3.8.10 | **4.4.8** | 4.x supported; Laravel 10–13, PHP ^8.1 | [Livewire releases](https://github.com/livewire/livewire/releases), [upgrade guide](https://livewire.laravel.com/docs/4.x/upgrading) |
| Filament Tables | 3.3.54 | **5.11.4** | 5.x supported; Laravel 11.28+/12/13, PHP ^8.2 | [Packagist filament/tables](https://packagist.org/packages/filament/tables) |
| Laravel Excel | 3.1.x-dev | **4.0.6** | 4.x requires PHP 8.3+, Laravel 12+, PhpSpreadsheet 5.3+ | [Upgrade to 4.0](https://docs.laravel-excel.com/4.x/getting-started/upgrade.html) |
| PHPUnit | 12.5.40 | **13.4.7** | 13.x requires PHP >= 8.4.1 | [Packagist phpunit/phpunit](https://packagist.org/packages/phpunit/phpunit) |
| Vue | 3.5.40 | **3.5.45** | 3.5 line; no newer major | [vuejs/core releases](https://github.com/vuejs/core/releases) |
| Vue Router | 3.6.5 (unused) | **5.5.1** | 5.x for Vue 3; the package is unused here, so remove it | [npm vue-router](https://www.npmjs.com/package/vue-router) |
| Vite | 8.1.5 | **8.3.8** | 8.x line | [vite releases](https://github.com/vitejs/vite/releases) |
| laravel-vite-plugin | 3.1.3 | **3.2.1** | 3.x | [laravel/vite-plugin releases](https://github.com/laravel/vite-plugin/releases) |
| `@vitejs/plugin-vue` | 6.0.8 | **6.0.11** | 6.x | [vite-plugin-vue releases](https://github.com/vitejs/vite-plugin-vue/releases) |
| Tailwind | 4.3.3 | **4.3.5** | 4.x | [tailwindcss releases](https://github.com/tailwindlabs/tailwindcss/releases) |
| PostCSS | 8.5.23 | **8.5.33** | 8.x | [npm postcss](https://www.npmjs.com/package/postcss) |
| Node | 24.15 | **26.13.1 LTS** ("Lithium") | 26: EOL 30 Apr 2029. 24: EOL 30 Apr 2028 | [nodejs.org dist](https://nodejs.org/dist/index.json), [endoflife.date/nodejs](https://endoflife.date/nodejs) |
| Playwright | 1.62.1 | **1.65.1** | latest | [Playwright releases](https://github.com/microsoft/playwright/releases) |
| MySQL | 8.4 (CI) | **8.4.13 LTS**; 9.7.4 LTS also available; 26.10 innovation | 8.4: premier to 30 Apr 2029, extended to 2032. 9.7: premier to 2031, extended to 2034 | [endoflife.date/mysql](https://endoflife.date/mysql) |
| Dompdf | 3.1.2 | **3.1.2** | no newer stable; nothing to do | [Packagist laravel-dompdf](https://packagist.org/packages/barryvdh/laravel-dompdf) |

---

## 4. Composer package table

"Supports target" means: its own constraints allow Laravel 13 **and** PHP 8.5. `yes` is from the package's published `require` block on 16 Nov 2026. `unknown` means no published constraint test was possible here.

| Package | Current | Latest | Abandoned | Supports target (L13 / PHP 8.5) | Action |
|---|---|---|---|---|---|
| laravel/framework | 13.38.0 | 13.42.0 | no | yes / yes | Patch bump |
| laravel/ai | 0.10.2 | 1.4.0 | no | yes / yes | **Major**; needs MCP 1.x and conversation migration |
| laravel/mcp | 0.8.2 | 1.2.0 | no | yes / yes | **Major**; protocol headers, session ids |
| laravel/sanctum | 4.3.2 | 4.4.0 | no | yes / yes | Patch bump |
| laravel/tinker | 3.0.2 | 3.1.0 | no | yes / yes | Minor bump |
| laravel/socialite | 5.28.0 | 5.32.0 | no | yes / yes | Minor bump |
| laravel/nightwatch | 1.30.0 | 1.30.6 | no | yes / yes | Patch bump |
| laravel/ui | 4.6.3 | 4.6.3 | no | yes (no Laravel constraint change) / yes | None |
| livewire/livewire | 3.8.10 | 4.4.8 | no | yes / yes | **Major**; see §5 |
| filament/tables | 3.3.54 | 5.11.4 | no | yes / yes | **Two majors**; four screens |
| maatwebsite/excel | 3.1.x-dev | 4.0.6 | no | yes / yes | **Major**; typed signatures, PhpSpreadsheet 5 |
| barryvdh/laravel-dompdf | 3.1.2 | 3.1.2 | no | yes / yes | None |
| spatie/laravel-backup | 10.3.0 | 10.4.0 | no | yes (PHP ^8.4) / yes | Patch bump |
| spatie/laravel-medialibrary | 11.23.2 | 11.23.13 | no | yes / yes | Patch bump |
| spatie/laravel-model-states | 2.14.1 | 2.14.3 | no | yes / yes | Patch bump |
| phpunit/phpunit | 12.5.40 | 13.4.7 | no | n/a / yes (>=8.4.1) | **Major**; annotations to attributes (§5) |
| brianium/paratest | 7.20.0 | 7.29.0 | no | n/a / yes | Minor bump with PHPUnit |
| larastan/larastan | 3.10.0 | 3.15.0 | no | yes / yes | Minor bump |
| nunomaduro/collision | 8.9.4 | 8.9.6 | no | yes / yes | Patch bump |
| laravel/boost | 2.4.12 | 2.10.4 | no | yes / yes | Minor bump; needed for the AI upgrade command |
| barryvdh/laravel-debugbar | 3.16.6 (dev) | 4.4.5 | no | yes / yes | **Major**, dev only |
| jantinnerezo/livewire-alert | 3.0.4 (custom fork) | 4.2.0 | no | yes / yes | **Major**; v4 replaces the `alert()` trait; the fork can go |
| predis/predis | 3.7.0 | 3.7.1 | no | yes / yes | Patch bump |
| league/csv | 9.28.0 | 9.29.0 | no | yes / yes | Patch bump |
| smalot/pdfparser | 2.12.5 | 2.13.1 | no | unknown / yes | Patch bump |
| iio/libmergepdf | 4.0.4 | 4.0.4 | no | unknown / yes | None |
| phpoffice/phpword | 1.4.0 | 1.4.0 | no | unknown / yes | None |
| kreait/laravel-firebase | 7.2.1 | 7.2.1 | no | yes / yes | None |
| twilio/sdk | 8.13.0 | 8.14.0 | no | n/a / yes | Minor bump |
| giggsey/libphonenumber-for-php | 9.0.41 | 9.0.43 | no | n/a / yes | Patch bump |
| bacon/bacon-qr-code | 3.1.1 | 3.1.1 | no | n/a / yes | None |
| stevebauman/purify | 6.3.2 | 6.3.2 | no | yes / yes | None |
| wire-elements/spotlight | 2.0.4 | 2.0.4 | no | yes / yes | None |
| nckg/laravel-impersonate | 4.0.1 | 4.0.1 | no | unknown / yes | None |
| flowframe/laravel-trend | 0.5.0 | 0.5.0 | no | yes / yes | None |
| laracasts/presenter | 0.2.9 | 0.2.9 | no | unknown / yes | None |
| aws/aws-sdk-php-laravel | 3.11.0 | 3.11.0 | no | yes / yes | None |
| symfony/http-client | 7.4.14 | 7.4.x LTS (8.2 is beta) | no | yes / yes | Stay on 7.4 LTS; no forced move |

No package in the checked set is marked abandoned on Packagist (16 Nov 2026).

Composer packages do not publish end-of-life dates. Support follows the latest major, and for Laravel packages that means the Laravel 13 window (bug fixes to Q3 2027, security to 17 Mar 2028). A package left on an older major is out of bug-fix support even if no date is printed.

---

## 5. Breaking changes that apply to this codebase

### 5.1 Laravel AI 0.10 → 1.4 — **High**

From [UPGRADE.md](https://github.com/laravel/ai/blob/1.x/UPGRADE.md):

- **Laravel MCP ^1.0 is required.** AI 1.0 conflicts with MCP < 1.0. This app has `laravel/mcp ^0.8`.
- **Conversation messages change shape.** `tool_calls` and `tool_results` are replaced by a single `steps` column; `approval_state` is replaced by `status` (`completed`/`paused`/`failed`), and the approval reason moves onto the call. The package migration does not re-run, so a new migration is needed.

Files that read or write the old shape:

| File | Why it breaks |
|---|---|
| `database/migrations/2026_01_11_000001_create_agent_conversations_table.php` | creates `approval_state`; needs the new `steps`/`status` shape for fresh installs |
| `app/Services/Toshi/PlatformOpsConversationService.php` | reads `$message->approval_state['pending']`, queries `whereNotNull('approval_state')` |
| `app/AiAgents/Skills/SlackSkill.php` | comment and flow assume `approval_state` on `agent_conversation_messages` |
| `app/Livewire/Superadmin/Toshi/PlatformApprovalGate.php`, `app/Http/Controllers/Superadmin/PlatformOpsConversationController.php` | approval screens read the same messages |
| `config/ai.php` | conversation table names and provider config; verify keys against 1.x |

Also in 1.4: OpenAI quota/billing errors throw `InsufficientCreditsException` instead of `RateLimitedException`. Grep catches no direct catch of `RateLimitedException` in `app/`, but the Toshi retry path must be reviewed.

### 5.2 Laravel MCP 0.8 → 1.2 — **High**

From [UPGRADE.md](https://github.com/laravel/mcp/blob/main/UPGRADE.md):

- Default protocol becomes `2026-07-28`; `server/discover` replaces `initialize` for modern clients.
- Every POST to an `Mcp::web(...)` route needs `MCP-Protocol-Version`, `Mcp-Method`, and for tool calls `Mcp-Name` headers; mismatches return HTTP 400 / `-32020`.
- `Request::sessionId()` / `setSessionId()` and `MCP-Session-Id` are removed; requests are independent.

Files:

| File | Why it breaks |
|---|---|
| `app/Providers/AppServiceProvider.php` | registers `Mcp::web(...)`; routes inherit the header middleware |
| `app/Mcp/Servers/SpikeSlackMockServer.php`, `app/Mcp/Tools/*` | server/tool shape and protocol metadata |
| `app/AiAgents/Skills/SlackSkill.php` | calls MCP tools; transport assumptions |
| `tests/Architecture/McpTransportEraReverificationTest.php`, `tests/Feature/Toshi/SpikeSlackMcpClientTest.php`, `tests/Feature/Toshi/McpWriteGateDefenseInDepthTest.php`, `tests/Feature/Toshi/SlackSkillTest.php` | tests post JSON without the new headers; they will 400 |

### 5.3 Livewire 3.8 → 4.4 — **Medium–High**

From the [Livewire 4 upgrade guide](https://livewire.laravel.com/docs/4.x/upgrading):

- Config keys renamed (`layout` → `component_layout`, `lazy_placeholder` → `component_placeholder`), `smart_wire_keys` defaults true, component locations/namespaces added. This app has `config/livewire.php`.
- `wire:model` ignores child events by default; `.blur`/`.change` now control client sync (use `.live.blur` to keep v3 behaviour). **Grep: no `wire:model.blur` or `.change` in `resources/views`.**
- `wire:transition` modifiers removed. **Grep: none used.**
- `wire:scroll` becomes `wire:navigate:scroll`. **Grep: none used.**
- Component tags must be closed; 77 `<livewire:` tags across the views need a lint pass.
- Endpoint prefix changes from `/livewire/` to `/livewire-{APP_KEY hash}/`; check CDN/firewall rules.
- `stream()` signature changes: `to:` → `el:`, and positional order changes. **One call:** `app/Livewire/AgentToshi.php:1714` uses `$this->stream($this->streamingMessageId, $chunk, false)` — positional; must become `stream($chunk, el: ...)`.
- 52 Livewire components under `app/Livewire` need their tests re-run.

### 5.4 Filament Tables 3.3 → 5.11 — **High**

Two major jumps. Only four files use `Filament\Tables`:

- `app/Livewire/Superadmin/Setting/Cities.php`
- `app/Livewire/Superadmin/Setting/PlanList.php`
- `app/Livewire/Superadmin/Setting/Countries.php`
- `app/Livewire/Superadmin/Reports/Subscriptions.php`

Plus the published view `resources/views/vendor/filament-actions/components/modals.blade.php`, which is tied to the Filament version and must be republished. The published upgrade guides for 4 and 5 must be read before work; **could not confirm** their exact breaking list from here. Treat the four screens as "upgrade or replace"; they are internal admin screens, not school-facing.

### 5.5 Laravel Excel 3.1 → 4.0 — **Medium**

From the [4.0 upgrade guide](https://docs.laravel-excel.com/4.x/getting-started/upgrade.html):

- PHP 8.3+, Laravel 12+, PhpSpreadsheet `^5.3` (from `^1.30`).
- Fully typed interfaces: implementers must add native return types (`array(): array`, `headings(): array`, etc.). The app's exports already declare them; the import classes must be checked one by one.
- `WithEvents`, `WithCharts`, `WithDrawings`, `WithCustomValueBinder` change with PhpSpreadsheet 5. **Grep: none used.**
- `FromScout` only if Scout is used. **Not used here.**

Files: `app/Imports/*` (UsersImport, QuestionImport, ParentsImport, MarksSheetReader, HolidaysImport, PromotionImport, TeachersImport) and `app/Exports/*` (SittingSheetExport, MarksheetExport, Marks template sheets, combined marksheet export). No event/chart/binder usage found.

### 5.6 Blade and Laravel 13 minor changes — **Low**

- Blade itself has no version and no breaking change in Laravel 13.x. `laravel/framework` 13.38 → 13.42 is minor and patch only.
- Packages that touch Blade: `livewire/livewire` (components and directives), `jantinnerezo/livewire-alert` (the `alert()` trait, replaced in v4), `mallardduck/blade-lucide-icons` (icon components), `stevebauman/purify` (sanitising rendered HTML), `filament/tables` (published actions view under `resources/views/vendor`), and the app's own 20 anonymous components.
- Laravel 13 features that are optional, not required: JSON:API resources, `PreventRequestForgery` origin checks, queue routing by class, controller/job attributes, `Cache::touch`, vector search. Adopting any of them is a separate decision; the upgrade does not need them.
- What to check: Blade component tags close correctly (Livewire 4 is stricter), the published Filament view is republished with Filament 5, and the icon package still resolves its component namespace.

### 5.7 PHP 8.5 — **Low**

- Laravel 13 supports PHP 8.5; CI is on 8.4. `composer.json` allows `^8.3`.
- PHP 8.5 deprecations were already addressed in #1169 (`Stack upgrade 2: fix PHP 8.5 deprecations`), so no known blocker from grep.
- Run PHPStan/Larastan under 8.5 and the suite with `error_reporting=E_ALL` to catch runtime deprecations.

### 5.8 PHPUnit 13 — **Low**

- Requires PHP >= 8.4.1. Compatible with Laravel 13's test runner.
- Annotation metadata is removed in PHPUnit 13; five files still use `@dataProvider` / `@test`:
  - `tests/Unit/Models/McpConnectorTokenRefreshServiceTest.php`
  - `tests/Unit/Models/SchoolMcpConnectorTest.php`
  - `tests/Feature/LegacyPortalIdorOwnershipTest.php`
  - `tests/Feature/ParentLinkStudentPickerTest.php`
  - one grading backfill test whose filename carries a school name (omitted here)
  Convert to attributes before the bump.

### 5.9 Vue, Vite, Tailwind, Node — **Low**

- Vue 3.5.40 → 3.5.45 is a patch in the same minor. `@vue/compat` MODE 2 stays; dropping compat is a separate, larger project (242 components).
- `vue-router` 3.6.5 is in `package.json` but not imported anywhere. Remove it rather than upgrade to 5.
- Vite 8.1 → 8.3, plugin and Tailwind are minor. `vite.config.js` uses `laravel-vite-plugin`, `@vitejs/plugin-vue`, `@tailwindcss/vite`; no removed options seen.
- Node 24 → 26 LTS. CI does not build assets today, so add a Node 26 build job; Laravel Cloud's build uses Node 24 and must be switched in the environment (not in this repo).

### 5.10 MySQL — **No change**

- CI and the workflow use `mysql:8.4`; the app's queries are ordinary MySQL 8 SQL. No 9.x-only syntax.
- Laravel Cloud's managed MySQL engine version is not exposed by the API or the repo — **could not confirm**. Ask the Cloud owner before planning 9.7.
- 8.4 LTS is supported to 2029 (premier) / 2032 (extended), so there is no deadline pressure.

### 5.11 PDF and fonts path — **Low**

- Report cards render through `barryvdh/laravel-dompdf` 3.1.2 and the Dompdf engine, called from `StudentReportCardService`. No wkhtmltopdf, no headless browser.
- The formal card uses **DejaVu Sans/Serif**, which ships with Dompdf. `storage/fonts/` is empty in the repo; Dompdf's font cache rebuilds there when a new font is registered. `config/dompdf.php` sets `font_dir` to `storage/fonts/`, `chroot` to `base_path()`, and remote images on.
- There is no Dompdf upgrade in this plan (3.1.2 is the latest). The risk is indirect: the new-look card from the report stack uses DM Sans and Sora; when that lands, it must embed those fonts or the PDF falls back. Test by rendering one card and checking the font names in the PDF.
- A deploy replaces the local filesystem, so `storage/fonts/` starts empty on a new instance. Dompdf rebuilds the cache on first use; a card must still render on a cold instance.

---

## 6. Risk table

| Item | What we gain | Effort | Risk | Must move with | How to test |
|---|---|---|---|---|---|---|
| PHP 8.4 → 8.5 | Active support through 2027; faster runtime; deprecations cleared | S | Low | Laravel 13.42; PHPUnit 13 | Full suite on 8.5; PHPStan; deprecations log |
| Laravel 13.38 → 13.42 | Bug and security fixes; Laravel 13 support window | S | Low | PHP 8.5 | Full suite; `php artisan about`; migration status |
| Laravel AI 0.10 → 1.4 | Streaming, tool approval across turns, provider coverage incl. Bedrock/Claude | L | **High** | MCP 1.x | Toshi conversation tests, approval gate, PlatformOps, one real provider call in staging |
| Laravel MCP 0.8 → 1.2 | Current protocol, no session affinity, proxy-safe metadata | M | **High** | AI 1.x | MCP client tests with the new headers; Slack connector tests; 400/`-32020` cases |
| Livewire 3.8 → 4.4 | Parallel live updates, non-blocking polls, smaller payloads | L | Medium–High | Nothing | Every Livewire screen; 52 component tests; endpoint prefix check; `stream()` call |
| Filament Tables 3.3 → 5.11 | Supported admin tables; current Livewire compatibility | M | **High** | Nothing | Four superadmin screens; republished view |
| Laravel Excel 3.1 → 4.0 | Supported PhpSpreadsheet 5; typed API; batch marker | M | Medium | PhpSpreadsheet 5 | Import/export tests; a real marks workbook and a learner list |
| PHPUnit 12 → 13 | Supported runner on PHP 8.5; attributes only | S | Low | PHP 8.5 | Convert five annotated files; full suite |
| Vue 3.5 patch + remove vue-router | Patch fixes; one less unused dependency | S | Low | Nothing | `npm run build`; every Vue page; no router imports |
| Vite 8.1 → 8.3 + plugins | Build fixes; current plugin compatibility | S | Low | Node 26 | `npm run build`; asset manifest; dev server |
| Tailwind 4.3.3 → 4.3.5 / PostCSS | Patch fixes in the utility engine | S | Low | Nothing | Visual check of 10 screens; build |
| Node 24 → 26 | Supported LTS to 2029; current build toolchain | M | Low–Medium | Vite build; Cloud build image | Build on 26; Cloud deploy to staging; Playwright |
| MySQL 8.4 → 9.7 | Newer LTS, longer support; no feature this app needs | L | High | — | Separate project; not in this plan |

---

## 7. Toshi gains

- **AI SDK 1.x** adds the pieces Toshi has been working around: streamed responses across more providers, tool approval that survives a resumed turn, structured sub-agents, runtime tool overrides, conversation pagination, classification with attachments, and provider capability checks. The 1.0 conversation model stores each model round trip and each tool result on the call that produced it, which is what the approval gate needs.
- **Claude on Bedrock (T-013):** `config/ai.php` already defines `bedrock`; AI 1.2 fixed structured output for Bedrock models without forced tool choice, and 1.4 fixed Anthropic tool calls that end with `end_turn` and streamed text across `pause_turn` continuations. That is the path T-013 needs.
- **MCP 1.x** moves the connector to the current protocol with per-request metadata, which is more robust behind a proxy and removes session affinity.
- **Livewire 4** makes polls non-blocking and live updates parallel, which matters on the Toshi chat surface and long admin tables.
- **What Toshi does not gain from PHP 8.5 or Laravel 13.42 directly:** no new model features; the gains are stability and support.

---

## 8. Test plan

### 8.1 Existing suites

- `php artisan test` (or `vendor/bin/phpunit`) on PHP 8.5, SQLite in memory, as today.
- The guarded run: `bash scripts/test-guard.sh` on the full tree.
- `vendor/bin/phpstan analyse` (Larastan) at the current level.

### 8.2 Report path (must stay identical)

Run before and after each phase, on the same made-up data, and compare:

- `tests/Feature/Reports/ReportParityFixtureTest.php`
- `tests/Feature/Reports/ReportOracleTest.php`
- `tests/Feature/Reports/ReprintAfterRulesEditTest.php`
- `tests/Feature/Reports/ReportRolesTest.php`
- `tests/Feature/Reports/ReportScaleSmokeTest.php` (800 learners)
- `tests/Feature/Reports/DivisionConsistencyTest.php`
- `tests/Feature/Reports/ProgressiveParityTest.php` (from the report stack, once merged)

Also render three cards (one strong, one middle, one with Missing/ABS) to PDF before and after and compare the extracted text. Byte hashes will differ because Dompdf writes timestamps; compare text, not bytes.

### 8.3 MySQL-backed migrations

- CI job `mysql-migrations.yml` on `mysql:8.4`.
- Locally: `DB_CONNECTION=mysql DB_DATABASE=upgrade_check php artisan migrate:fresh --seed`, then `php artisan migrate:rollback --step=1` for any new migration, then forward again.
- For the AI 1.x conversation migration: run it on a **copy** of a database that has real conversation rows, and check the `steps`/`status` backfill before dropping the old columns.

### 8.4 Click-through, as a person

At **375 px first, then 1280 px**, clicking only (a typed URL does not count):

1. Sign in as a school admin.
2. Open **Report cards**, choose a class, open one learner's **PDF**.
3. Open **Exams and marks**, switch set tabs, open **Enter marks**, save one mark and one **ABS**.
4. Open the approvals queue and **Marks Release** preview.
5. Sign in as a parent and open the released report.
6. Check no sideways scroll at 375, controls at least 44 px, and no console errors.

Repeat the same path on staging after each phase that touches UI (Livewire, Filament, Vue/Vite).

### 8.5 Rollback

- **Before each phase:** tag the last good commit, keep its `composer.lock` and `package-lock.json`, and take a verified database backup.
- **Code:** deploy the previous commit and its lockfiles. Do not "downgrade" by editing constraints in place.
- **Database:** new migrations get a tested `down()`. The AI conversation migration **drops** old columns after backfill, so a code rollback needs the backup restored, not `migrate:rollback` alone.
- **Assets:** keep the previous `public/build` artifact until the new one is verified; a failed build must not leave a half-written manifest.
- **Toshi:** if AI/MCP fails, disable the Toshi surface with its existing switch rather than reverting mid-conversation; conversations already stored in the new shape stay readable.
- **Cloud:** a failed deploy rolls back to the previous deployment; confirm the migration is backward-compatible before deploying (expand, then contract).

---

## 9. DO NOT START

No upgrade work starts until **all** of these are true:

1. The report stack is merged: #1342, #1343, #1344, #1345, #1346, #1347, #1348, #1350, #1351, #1352, #1353, #1355, #1356, #1357, #1358.
2. The marks drafts are merged: #1324, #1325, #1330, #1335, #1336, #1337, #1338, #1339, #1340.
3. The demo is done.
4. A verified backup exists (snapshot taken, restored to scratch, row counts checked).
5. The owner says go.

**Why the lockfiles force this order:** an upgrade changes `composer.lock` and `package-lock.json`. Every open PR is based on the current lockfiles, so the moment an upgrade lands, every one of them conflicts on both files and has to rebase and re-resolve. With the report and marks PRs still open, that is dozens of conflicts for no benefit. Merge the feature work first, then upgrade once.

---

## 10. T-044 tracker note (paste as-is)

```
Plan first (docs agent), execute after the report stack merges (local agent, Mac with MySQL), verify from a clean install on a cloud machine. Scope: PHP, Laravel, AI SDK, Vue, Vite, Blade, Tailwind, Node, packages.
```

---

## 11. What could not be confirmed

- **Laravel Cloud MySQL engine version** — not exposed in the repo or the Cloud API; the plan assumes 8.4 (CI) and does not move it.
- **Filament 4/5 exact breaking list** — the two upgrade guides were not read in full here; the four affected files are listed, but the per-method changes need a pass before work starts.
- **Laravel Excel 4.0.6 with Laravel 13.42** — its published constraint says Laravel 12+; a smoke run on 13.42 is still required.
- **Vite 8.3 on Node 26** — the engines field should be checked in the installed package; the plan adds a Node 26 build job to prove it.
- **Livewire 4.4 with this exact Blade set** — the upgrade guide is clear, but the 77 component tags and 242 Vue components need the real test pass.
- **Symfony 8 stable** — 8.2 is beta on the plan date; the plan deliberately stays on 7.4 LTS.

## 12. Sources

- PHP support: https://www.php.net/supported-versions.php
- Laravel 13 support and PHP range: https://laravel.com/docs/13.x/releases
- Laravel framework 13.42.0: https://github.com/laravel/framework/releases/tag/v13.42.0
- Laravel AI releases and upgrade: https://github.com/laravel/ai/releases · https://github.com/laravel/ai/blob/1.x/UPGRADE.md
- Laravel MCP upgrade: https://github.com/laravel/mcp/blob/main/UPGRADE.md
- Livewire 4 upgrade: https://livewire.laravel.com/docs/4.x/upgrading
- Laravel Excel 4 upgrade: https://docs.laravel-excel.com/4.x/getting-started/upgrade.html
- Vue: https://github.com/vuejs/core/releases · Vite: https://github.com/vitejs/vite/releases
- laravel-vite-plugin: https://github.com/laravel/vite-plugin/releases · plugin-vue: https://github.com/vitejs/vite-plugin-vue/releases
- Tailwind: https://github.com/tailwindlabs/tailwindcss/releases · PostCSS: https://www.npmjs.com/package/postcss
- Node: https://nodejs.org/dist/index.json · https://endoflife.date/nodejs
- MySQL: https://endoflife.date/mysql
- PHPUnit: https://packagist.org/packages/phpunit/phpunit
- Packages: Packagist package pages, checked 16 Nov 2026 (latest stable and `require` blocks).
