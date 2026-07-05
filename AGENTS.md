# KlassApp — Session Memory & Project Context

## Active Branch: `main`

---

## DASHBOARD REDESIGN (COMPLETED — June 2026)

- Sora + DM Sans fonts aligned with landing page
- Brand colors: Blue #1E6FD9, Green #22C55E, Dark #0F172A, Amber #D97706
- White navbar with subtle border and shadow
- Dark sidebar (#0F172A) with green active accent
- KPI cards with colored icon circles, hover lift effect
- Chart.js 2.6 — do NOT upgrade (breaking API changes)
- Vanilla JS hamburger (replaced Tailwind + jQuery dependency)
- Dashboard at `/admin/dashboard`

### Files Modified
- `public/css/dashboard-refresh.css` — full rewrite
- `resources/views/layouts/app.blade.php` — fonts
- `resources/views/admin/dashboard/dashboard.blade.php`
- `resources/views/layouts/admin/sidebar.blade.php`
- `resources/views/layouts/partials/navigation.blade.php`
- `resources/views/auth/login.blade.php` — fixed false maintenance banner
- `app/Http/Kernel.php` — removed Sanctum from API middleware (fixes webhook 302)
- `routes/api.php` — added delivery webhook route

---

## WHATSAPP INTEGRATION (ACTIVE — June 2026)

### Production (Meta Cloud API only — Evolution removed)
- Webhook: https://klassapp.xyz/api/whatsapp/inbound
- Business Number: +256765275289
- Meta WABA fully active, Evolution API decommissioned
- ⚠️ Facebook Business Account banned (June 26). Need new number + fresh Meta account.
- Pending: Replace business number once new WABA is set up

### Deploy Command
```bash
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 \
  "cd /var/www/klassapp && git pull origin main && php artisan optimize:clear && systemctl restart php8.3-fpm"
```

### Local Dev
```bash
colima start                    # Docker
brew services start mysql       # MySQL
php artisan serve               # Laravel on :8000
```

### Test Credentials
- Super Admin: siteadmin@gmail.com / password
- School Admin: admin@testschoolone.sch.ug / password123

---

## LANDING PAGES (COMPLETED — June 2026)

### Two versions live

| Route | Version | Description |
|---|---|---|
| `http://localhost:8000/` | v1 | Unified landing/welcome merge |
| `http://localhost:8000/landing` | v1 | Direct landing URL |
| `http://localhost:8000/landing2` | **v2** | **Recommended** — gradient hero, horizontal navbar |

### v1 Features (`/`)
- Light theme: transparent → white navbar, warm-glow hero, slate footer
- Typewriter H1: "The school in every parent's pocket."
- Looping keyword typewriter: Grades → fees → attendance → health → canteen → discipline → notifications → timetables → exams → reports
- Audience tabs: Admin/Teacher/Parent with green active highlight
- CTAs: Join (green) + Portal (blue) + Book a demo (Calendly) + WhatsApp
- Navbar: vertical shrink on scroll (py-5 → py-3, h-14 → h-10)
- Footer: oversized "KlassApp" wordmark (22vw, mint, opacity 0.05)
- "Smarter schools start here." + "All rights reserved."
- "across Africa" (not East Africa), USD (not UGX)

### v2 Features (`/landing2`)
- Same content as v1
- **Gradient H1**: Animated blue → green → amber (gradientShift keyframes)
- **Horizontal navbar shrink**: Left-to-right scaleX on scroll DOWN, restore on scroll UP (Flare-style from https://flareapp.io)
- **Brand-colored footer**: Blue-green-dark gradient wordmark at 0.03 opacity
- **Pure white**: All backgrounds white (no warm glow)

---

## TECHNICAL FIXES APPLIED

### AppServiceProvider.php
```php
// Added try/catch around DB settings query
if (!App::runningInConsole()) {
    try {
        if (Schema::hasTable('settings')) {
            $settings = Setting::all();
            // ...
        }
    } catch (\Exception $e) {
        // Silently skip settings when DB unavailable
    }
}
```
Prevents boot crash when MySQL is not running.

### WelcomeController.php
Returns `view('landing')` instead of `view('welcome')`.

### Routes
- `routes/web.php`: Added `/landing2` route
- `routes/admin.php`: Removed premium-page routes

---

## FILES MODIFIED (13 total)

| File | Change |
|---|---|
| `resources/views/landing.blade.php` | v1 unified (1550+ lines) |
| `resources/views/landing2.blade.php` | v2 gradient/horizontal (1550+ lines) |
| `resources/views/welcome.blade.php` | Old file, not used by routes |
| `app/Http/Controllers/WelcomeController.php` | Points to landing |
| `app/Providers/AppServiceProvider.php` | DB-safe boot |
| `routes/web.php` | Added /landing2 |
| `routes/admin.php` | Removed premium routes |
| `docs/dev/digitalocean-deployment.md` | New |
| `scripts/provision-evolution.sh` | New |
| `scripts/provision-klassapp.sh` | New |

---

## PENDING WORK

### 1. WhatsApp Cloud API Migration ✅
- Evolution API (Baileys) fully removed. Meta Cloud API is the sole transport.

### 2. Incomplete Dashboards
- Accountant dashboard (no main view)
- Receptionist dashboard (partial)
- Librarian dashboard (no view)
- Superadmin dashboard (controller exists, no dedicated view)

### 3. Bar Chart Data
Admin dashboard bar chart uses hardcoded placeholder data. Needs real attendance or revenue data wiring.

### 4. Landing Page v1 ↔ v2
v2 (`/landing2`) has different navbar JS (direction-aware). Not aligned with v1 style. Consider merging into single canonical landing.

### 5. School Pay Signature Enforcement
SchoolPayWebhookController silently accepts unsigned webhooks during pilot. Add `SCHOOLPAY_ENFORCE_SIGNATURE=true` env flag or `school_pay_enforce_signature` toggle on School model to reject unsigned webhooks with 403 once payload format is confirmed.

### 6. WhatsAppPendingParentLink Table
`whatsapp_pending_parent_links` table and model exist but flow was changed to direct linking. Table is dead schema weight — either drop migration or keep as note.

---

## TECHNICAL NOTES

- **Native interactive list messages**: Max 10 rows across all sections. Meta Cloud API enforces this natively.

## KLASSAPP STUDENT ID SYSTEM

- Format: `KLS{school_code_3}{sequential_4}` (e.g., KLS0010427 — no dashes)
- Auto-generated during Toshi onboarding for each student
- Stored in `student_academics.klassapp_student_id` (unique, indexed)
- Primary identifier for WhatsApp parent linking (no School Pay code needed)
- School's own IDs supported via `id_card_number` / `board_registration_number`
- School admin responsible for distributing KlassApp IDs to parents (report cards, SMS)

## MINISTRY SCHOOL CODES

- `schools.ministry_code` — 4-digit Ministry of Education code (Uganda)
- Added migration June 29, 2026
- Used in KlassApp ID and WhatsApp school lookup
- Optional — schools without MoE codes use auto-generated codes

## BRAND ASSETS

| Element | Value |
|---|---|
| Blue | `#1E6FD9` |
| Green | `#22C55E` |
| Dark | `#0F172A` |
| Amber | `#D97706` |
| Surface (v1) | `#FAFAF5` |
| Display font | Sora |
| Body font | DM Sans |
| Logo | `images/klassapp-logo-primary.svg` |

---

## CONTENT RULES (Enforced)

| Rule | Status |
|---|---|
| "across Africa" (not East Africa) | ✅ |
| "Uganda" only for Uganda facts | ✅ |
| USD pricing (not UGX) | ✅ |
| No em dashes — commas/periods | ✅ (most removed) |
| No "Built in Uganda" public | ✅ |
| Parents are first-class users | ✅ |
| "And the system your admin team operates on" | ✅ |

---

---

## WHATSAPP SCHOOL PAY INTEGRATION (COMPLETED — June 23)

### Self-Verification Flow
- Parents verify by texting their School Pay payment code to the KlassApp WhatsApp number
- Code matched against `student_academics.std_school_pay_number` → joins through `student_parent_links` → `whatsapp_users`
- No school approval needed — code match = sufficient proof of parent relationship
- First-time texter flow: button message → code entry → auto-linked

### Interactive List Messages
- Welcome messages now use `sendList()` with tap-able buttons instead of "Reply FEES..." text prompts
- List buttons: Fee Balance, Exam Results, Attendance, Help & Options
- `routeInbound()` strips emojis from incoming messages so list button titles match keyword routing
- `sendListDual()` added to `OutboundWhatsAppService` for Business API fallback

### School Pay Webhook
- `SchoolPayWebhookController.php` — SHA256 HMAC verification, student join chain, WhatsApp receipt
- `schoolpay_transactions` table: dedup by receipt_no, raw_payload capture
- Route: `POST /api/schoolpay/webhook` (CSRF exempt)
- Message types: fee receipt, attendance, grades, health, student withdrawn, term opens/closes

### Free-Form Messages
- `OutboundWhatsAppService`: composeFeeBalance, composeAttendance, composeGradesOverview, composeHealthRecord, composeStudentWithdrawn, composeTermOpens, composeTermCloses
- Public notify methods: notifyFeeBalance, notifyAttendance, notifyStudentWithdrawn
- `sendButtons()` / `sendButtonsDual()` for interactive button messages via Evolution API

### Files Added/Modified
- `app/Http/Controllers/Api/SchoolPayWebhookController.php` — new
- `app/Http/Controllers/Api/WhatsAppController.php` — interactive lists, emoji matching, code verification flow
- `app/Services/WhatsAppService.php` — sendList() method
- `app/Services/OutboundWhatsAppService.php` — free-form builders + notify methods + sendListDual
- `app/Models/WhatsAppPendingParentLink.php` — new (created but flow changed to direct linking)
- `database/migrations/2026_06_23_000001_add_schoolpay_integration.php` — new
- `database/migrations/2026_06_23_074056_create_whatsapp_pending_parent_links_table.php` — new
- `database/migrations/2026_06_23_074057_make_user_id_nullable_on_whatsapp_users.php` — new
- `routes/api.php` — webhook route

---

## GITHUB STATUS

```bash
Branch: main
Commit: a58f3d0
Message: fix: replace Kampala High School with Kabale Junior School in testimonials (#105)
Status: Ahead of origin/main
```

---

## TOSHI 2.0 STATUS (June 19)

Items 1-11: ✅ Complete
- Critical fixes, plan selection, confirm/edit flow, input validation, review card, error handling, WhatsApp verification, dual-mode detection, progress persistence
- See `knowledge.md` for full spec and implementation details

## CURRENT STATUS (July 2, 2026)

### Onboarding Fixes
- WhatsApp TypeError fixed (nullable token, non-blocking OTP)
- `commit()` public method created + pre-flight duplicate checks
- Student class assignment fixed (was dumping all into P1)
- Edit flow gets fuzzy step matching
- Teachers/students deduplicated from file uploads

### LarAgent Migration (assistant mode, feature-flagged)
- `ToshiAssistantAgent` with 18 #[Tool] methods, Nvidia NIM provider
- Complexity-based model routing (cheap vs escalated)
- Response caching + static context caching
- Feature flag: `TOSHI_LARAGENT_ENABLED` (default false)
- 9 new tests passing, 17 total

### Design Overhaul
- **Sidebar**: 171 inline SVGs replaced with `<x-icons.sidebar>` component across all 9 role menus. Centralized active state helper.
- **Dashboard**: KPI padding unified (px-5 py-4), icon sizes reduced (w-14)
- **Internal pages**: Students + standardlinks wrapped in dashboard card pattern
- **Toshi UI**: Navy header (#0D1526) with green status dot, 20+ CSS classes extracted, message animations, safe-area positioning, composer matched to chat area

### Key Files
- `app/AiAgents/ToshiAssistantAgent.php` — NEW
- `config/laragent.php` — NEW
- `resources/views/components/icons/sidebar.blade.php` — NEW (17 icons)
- `tests/Feature/Toshi/ToshiAssistantAgentTest.php` — NEW (9 tests)

## NEXT SESSION CHECKLIST

- [ ] Enable `TOSHI_LARAGENT_ENABLED` for test user after review
- [ ] Monitor `Assistant path:` logs for LarAgent vs legacy distribution
- [ ] Create new Meta Business Account with fresh email + new WhatsApp number
- [ ] Update .env with new WABA credentials (token, phone number ID, WABA ID)
- [ ] Run any new migrations on production
- [ ] Test end-to-end WhatsApp flow with new business number
- [ ] Schedule school onboarding demo with founding team

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.19
- laravel/framework (LARAVEL) - v10
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v3
- laravel/scout (SCOUT) - v11
- laravel/socialite (SOCIALITE) - v5
- livewire/livewire (LIVEWIRE) - v3
- larastan/larastan (LARASTAN) - v2
- laravel/mcp (MCP) - v0
- phpunit/phpunit (PHPUNIT) - v10
- laravel-echo (ECHO) - v1
- tailwindcss (TAILWINDCSS) - v1
- vue (VUE) - v2

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs
- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches when dealing with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The `search-docs` tool is perfect for all Laravel-related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless there is something very complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v10 rules ===

## Laravel 10

- Use the `search-docs` tool to get version-specific documentation.
- Middleware typically live in `app/Http/Middleware/` and service providers in `app/Providers/`.
- Laravel 10 has a `bootstrap/app.php` file that creates the application instance and binds kernel contracts, but does not use it for application configuration like Laravel 11:
    - Middleware registration is in `app/Http/Kernel.php`
    - Exception handling is in `app/Exceptions/Handler.php`
    - Console commands and schedule registration is in `app/Console/Kernel.php`
    - Rate limits likely exist in `RouteServiceProvider` or `app/Http/Kernel.php`
- When using Eloquent model casts, you must use `protected $casts = [];` and not the `casts()` method. The `casts()` method isn't available on models in Laravel 10.

=== livewire/core rules ===

## Livewire

- Use the `search-docs` tool to find exact version-specific documentation for how to write Livewire and Livewire tests.
- Use the `php artisan make:livewire [Posts\CreatePost]` Artisan command to create new components.
- State should live on the server, with the UI reflecting it.
- All Livewire requests hit the Laravel backend; they're like regular HTTP requests. Always validate form data and run authorization checks in Livewire actions.

## Livewire Best Practices
- Livewire components require a single root element.
- Use `wire:loading` and `wire:dirty` for delightful loading states.
- Add `wire:key` in loops:

    ```blade
    @foreach ($items as $item)
        <div wire:key="item-{{ $item->id }}">
            {{ $item->name }}
        </div>
    @endforeach
    ```

- Prefer lifecycle hooks like `mount()`, `updatedFoo()` for initialization and reactive side effects:

<code-snippet name="Lifecycle Hook Examples" lang="php">
    public function mount(User $user) { $this->user = $user; }
    public function updatedSearch() { $this->resetPage(); }
</code-snippet>

## Testing Livewire

<code-snippet name="Example Livewire Component Test" lang="php">
    Livewire::test(Counter::class)
        ->assertSet('count', 0)
        ->call('increment')
        ->assertSet('count', 1)
        ->assertSee(1)
        ->assertStatus(200);
</code-snippet>

<code-snippet name="Testing Livewire Component Exists on Page" lang="php">
    $this->get('/posts/create')
    ->assertSeeLivewire(CreatePost::class);
</code-snippet>

=== livewire/v3 rules ===

## Livewire 3

### Key Changes From Livewire 2
- These things changed in Livewire 3, but may not have been updated in this application. Verify this application's setup to ensure you conform with application conventions.
    - Use `wire:model.live` for real-time updates, `wire:model` is now deferred by default.
    - Components now use the `App\Livewire` namespace (not `App\Http\Livewire`).
    - Use `$this->dispatch()` to dispatch events (not `emit` or `dispatchBrowserEvent`).
    - Use the `components.layouts.app` view as the typical layout path (not `layouts.app`).

### New Directives
- `wire:show`, `wire:transition`, `wire:cloak`, `wire:offline`, `wire:target` are available for use. Use the documentation to find usage examples.

### Alpine
- Alpine is now included with Livewire; don't manually include Alpine.js.
- Plugins included with Alpine: persist, intersect, collapse, and focus.

### Lifecycle Hooks
- You can listen for `livewire:init` to hook into Livewire initialization, and `fail.status === 419` for the page expiring:

<code-snippet name="Livewire Init Hook Example" lang="js">
document.addEventListener('livewire:init', function () {
    Livewire.hook('request', ({ fail }) => {
        if (fail && fail.status === 419) {
            alert('Your session expired');
        }
    });

    Livewire.hook('message.failed', (message, component) => {
        console.error(message);
    });
});
</code-snippet>

=== phpunit/core rules ===

## PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should test all of the happy paths, failure paths, and weird paths.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

### Running Tests
- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

=== tailwindcss/core rules ===

## Tailwind CSS

- Use Tailwind CSS classes to style HTML; check and use existing Tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc.).
- Think through class placement, order, priority, and defaults. Remove redundant classes, add classes to parent or child carefully to limit repetition, and group elements logically.
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing; don't use margins.

<code-snippet name="Valid Flex Gap Spacing Example" lang="html">
    <div class="flex gap-8">
        <div>Superior</div>
        <div>Michigan</div>
        <div>Erie</div>
    </div>
</code-snippet>

### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.
</laravel-boost-guidelines>
