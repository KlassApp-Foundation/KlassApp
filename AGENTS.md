# AGENTS.md — KlassApp Canonical Agent Rules

> **This is the single canonical rules file for every coding agent working on this repo** — Claude Code, Codex, OpenCode, Cursor, or any other tool. `.ai/rules/*.md` and `.cursor/rules/*.mdc` are now one-line pointers back to this file (see "Why one canonical file" below). If you're an agent reading a pointer file, come here for the actual rules.
>
> For full project history, past incidents, and session-by-session decisions, read `knowledge.md` in the repo root **first**, before starting any task. Update it before ending a phase of work (see "Session workflow" below). This file is the distilled, standing rule set; `knowledge.md` is the historical record.

## Environment reality check

**Do not assume you have production access.** Whether a given session has real SSH access to `root@46.101.111.131` (the DigitalOcean droplet running the `sms-app` Docker container) is inconsistent across sessions and tools — some have it, some don't. Check for real (attempt an SSH connection or confirm the binary/key exist) before planning work that depends on it, and scope your work to what you've actually confirmed. If you don't have production access, say so plainly and hand off anything that genuinely needs it rather than guessing at production state.

**Verify this yourself, every session — do not carry over another session's answer.** This isn't hypothetical: different sessions/tools running against this repo have had different production access on the same night, and more than once that was only discovered after real work had already happened on a false assumption. A previous session (this one, or another tool) reporting "I have SSH access" or "I don't" tells you nothing about your own session. As a first step, before planning anything that touches production: actually attempt the SSH connection (`ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131`) or confirm the binary and key exist, and note the result before proceeding.

## Standing rules

These apply to every change, in every session, regardless of which tool is running you.

1. **No raw production writes.** Production data changes go through migrations or Artisan commands committed to the repo — never a one-off `UPDATE`/`DELETE` typed by hand against the live database.
2. **Backup and verify before any production data write.** Confirm you can see the current state and have a way back before a migration/command runs against real data.
3. **Flag junk/duplicate records inactive — never delete.** Use `status = 'inactive'` (see `users.status` enum) so the data is recoverable and auditable. This project has real precedent of junk-record deletion causing data loss; flagging is the reversible option.
4. **Never force a weak or ambiguous match.** Name-matching, record-linking, or dedup logic that can't clear a real confidence threshold must be flagged for human review, not auto-resolved. See "Known bug pattern #3" below for the concrete incident this rule comes from.
5. **`standard_id` ≠ `section_id`.** `Standard` is a grading-tier band (e.g. `primary_lower` spans P.1–P.3); `Section` is the actual class; `standards_link` is one stream of one class (the join between them, one row per section). Any query that means "this specific class" must scope by `section_id`, never `standard_id` alone. This exact confusion has caused 4+ separate bugs — see "Known bug pattern #2".
6. **Positive-equality scoping on multi-value enums — never `!=`.** On a 3+ value enum (e.g. `users.status`: `active`/`inactive`/`exit`), a negative filter (`!= 'exit'`) silently includes every other value, not just the one you meant. Always filter `= '<intended-value>'`. See "Known bug pattern #6" — this exact mistake has recurred 4 times across unrelated features.
7. **Prefer structured tooling over raw grep/bash for codebase investigation** when a better tool exists — e.g. PHPStorm MCP / Laravel Boost's `get_eloquent_model` for relationships/FKs, `get_routes`/`search_symbol` for lookups, `search-docs` for framework questions. Reserve grep/bash for what those genuinely can't cover.
8. **Verify claims independently before acting on them — including a prior session's "confirmed" finding.** "The worker is running" / "the queue is connected" / "this was fixed already" are all claims, not evidence, until you've checked the actual state yourself (the real `jobs` table, an actual query, an actual render). See "Known bug pattern #4" for a case where the infrastructure *looked* verified and wasn't.
9. **Never touch real user credentials or real student/school data for verification.** Build isolated demo/seeded accounts and synthetic fixtures to test against. Production data is real people's data — treat it accordingly, even in a "just checking" context.
10. **Ship small, atomic PRs.** Keep unrelated work stashed or in a separate branch rather than folding it into the diff for the thing you were actually asked to do.
11. **Real evidence over claims.** "Should work" is not a completion report. Back up "done" with an actual test run, an actual query result, an actual screenshot, or an actual login — not a description of what you expect to have happened.
12. **Disclose tool and environment limitations honestly.** If you don't have production access, can't reach a URL, can't run a binary, or a tool returned something you're not fully sure about, say so plainly rather than working around it silently or guessing.
13. **Every new feature must be genuinely multi-tenant, scoped by `school_id` throughout.** This is a multi-school platform — a query, a cache key, a background job, or a UI list that isn't scoped by `school_id` end-to-end is a cross-tenant data leak waiting to happen.
14. **PHP `||` with string literals is always truthy; locale/domain assumptions from one educational system must not be hardcoded for another.** `$x == '10' || '11' || '12'` evaluates as `($x == '10') || '11' || '12'` — always truthy because non-empty strings are truthy. Use `in_array()` or explicit comparisons. Indian-system standard numbers (10/11/12) don't exist in the Ugandan schema; always verify data-format constraints match the real data (UNEB numbers are alphanumeric like `U1234/567`, not numeric). See "Known bug pattern #8".
15. **Deploy from the correct dedicated main worktree**, not an arbitrary local checkout — this project uses a worktree-based workflow (see `knowledge.md`'s note on the canonical `knowledge.md` path and worktree sync). Confirm you're on the right one before running the deploy script.
15. **When output looks garbled or suspicious, write it to a file and read the file back** — don't trust a raw streamed terminal render for anything you're about to act on (a rendered PDF, an image, long structured output). This has caught real false negatives before.
16. **Read `knowledge.md` first in any session, before planning.** Update it before ending a phase of work: a Session Log entry (date, work done, files touched, decisions, status, edge cases), PR number/URL/branch when you open one, and the merge commit SHA + refreshed "Current Status" when it merges. Don't leave "not pushed" / "opening PR" stubs once the PR is actually open or merged.

## Known bug patterns (quick reference — full detail in `knowledge.md`)

Before editing code in these areas, check the fix markers below are still in place. Full root-cause / fix / verification detail lives in `knowledge.md`'s "Known Bug Patterns & Lessons" section — this is a locator, not a replacement for reading it.

1. **Duplicated business logic across render paths** (`generatePdf()`) — shared logic must live in exactly one place; check `grep -n "function generatePdf" app/Http/Controllers/Admin/ReportCardsController.php` returns exactly one match.
2. **`standard_id` mistaken for class identity** — see standing rule #5. Verify: no `where('standard_id', …)` in class-scoped report queries; class-scoped queries use `section_id`.
3. **Digit-suffix heuristic for junk-record identification — unreliable.** Never trust a proxy signal (digit suffix, name length) for identity matching when a real source of truth (direct name-matching against an authoritative roster) is available. Proxy heuristics are for *display* cleanup only, never identity decisions.
4. **`QUEUE_CONNECTION` vs `QUEUE_DRIVER` env-var mismatch — silent sync mode.** A config reading the wrong/legacy env var name silently degraded async job processing to synchronous inline execution, while every surface-level signal ("worker is running", "Redis connected") looked fine. Verify: `config/queue.php` reads the current env var name; check the actual `jobs` table / queue backend after a dispatch, not just eventual completion.
5. **`contributes_to_report_total` not respected at every call site.** When a boolean flag decides "does this count toward the total", it must be checked at *every* aggregation call site — grep the column name before writing any new aggregate query.
6. **`status != 'exit'` conflates "not exited" with "currently active"** — see standing rule #6. This is the same broad-category-vs-specific-value mistake as patterns #2–#4 above; it has now recurred 4 times across unrelated features. Treat it as a reflex check on any new aggregate/count query touching a multi-value enum.
7. **`board_registration_number` validation — operator-precedence + wrong-locale bug.** `$standard->name == '10' || '11' || '12'` is always truthy (PHP `||` with string literals), and Indian system numbers 10/11/12 don't exist in the Ugandan schema. `nullable|numeric` rejected real UNEB numbers like `U1234/567`. Fixed by `OnboardingEngine::isCandidateClass()` matching actual Ugandan exam-candidate classes + `string|max:50` validation. See standing rule #14.

## Project Provenance

KlassApp is a fork of **GeGoK12** (GoGo Technologies, India). The codebase contains fork-legacy artifacts — Indian grade-level references (`'10'`/`'11'`/`'12'`), `id_card_number` naming, Aadhaar/caste demographic fields, and CBSE "Board" terminology — that predate the Ugandan/UNEB adaptation. When code seems mismatched to the Ugandan context, check `docs/project-provenance.md` before assuming it's a new bug. Standing rule #14 (PHP operator precedence / locale assumptions) and Known Bug Pattern #7 (`board_registration_number`) are concrete instances of this provenance issue.

## Path-specific notes

### `app/Console/Commands/**`
Section naming convention: `P.1`–`P.7` = Primary One through Primary Seven; `S.1`–`S.6` = Senior One through Senior Six ("S" = Secondary/Senior, never spell out "Primary One" as a long-form string in matching logic). Nursery sections use word names: Baby Class, Middle Class, Top Class.

### `app/Helpers/SiteHelper.php`
`getAcademicYear()` resolves the current year by `academic_years.status = 1` (legacy description-text fallback exists but is not the primary signal — never filter on the magic string "Current Academic Year"). Cache key `academic_year_for_school_{id}` must be forgotten on `AcademicYear` create/update/delete (`AcademicYearObserver` + the onboarding wizard's `saveAcademicYear`).

### `app/Http/Controllers/Teacher/MarksController.php`
`saveExamMarks` must abort 403 unless `exam.school_id === auth.school_id` **AND** `exam.teacher_id === auth.id`. A school-only check lets any same-school teacher POST to another teacher's exam URL. "View Entered Marks" must not pass undefined view vars — load `examType`/`academicTerm`/`academicYear`/`subject`/`teacher` explicitly.

### Frontend (`**/*.{blade.php,vue,js,css,scss}`)
- **Vite 8** is the sole bundler (`npm run dev` / `npm run build`) — there is no Mix, no `webpack.mix.js`, no `npm run production`. Don't reintroduce them.
- **Vue 3.5.40 via `@vue/compat` MODE 2** — Options API, `Vue.component()` registration, `vue` aliased to `@vue/compat` in `vite.config.js`. ESM only — don't reintroduce `require()` in `app.js`/`bootstrap.js`.
- **Tailwind v4.3.3**, CSS-first `@theme` in `resources/css/tailwind.css` — no `tailwind.config.js`. v4's default `border-color` is `currentColor`; a bare `border`/`border-{side}` utility needs an explicit color utility alongside it.
- Not every Blade layout loads local Tailwind v4 — `layouts/admission.blade.php` and `layouts/video.blade.php` use a CDN build instead. Check which layout a page renders through before assuming a utility class will apply.
- `ds-*` classes (`ds-kpi-card`, `ds-btn`, etc.) are hardcoded CSS in `resources/assets/sass/`, not `@apply`-derived from Tailwind anymore (Phase 2b rewrite) — changing a Tailwind utility value will not propagate to them.
- SCSS strips `//` comments on compile — if CSS output looks incomplete, grep the `.scss` **source**, not compiled `public/css/app.css`; a false negative there has happened before (`grep -rn "// UNKNOWN:" resources/assets/sass/`).
- Mobile-first breakpoints: 375px, 414px, 768px, 1280px+. Verify with real screenshots at each — this project has shipped regressions from trusting computed CSS values instead.
- `.npmrc`'s `legacy-peer-deps=true` is scoped package debt (specific Vue-2-era packages still declare Vue 2 peers) — don't remove it without a package audit pass.
- `npm run dev` writes `public/hot`; production must not leave it behind, or Laravel points at a dead Vite dev server.
- Pusher/Echo reads `import.meta.env.VITE_PUSHER_APP_KEY`/`VITE_PUSHER_APP_CLUSTER` only — no Mix dual-read.
- Toshi UI CSS/views are a published package copy — after editing the source, run `php artisan vendor:publish --tag=toshi-ui-css --force` (and `--tag=toshi-ui-views`) or the change won't show up.
- Legacy dark-theme values (`#0F172A`, `#063f8d`) resurface on new components without explicit light-theme styling — always set explicit background/text colors rather than relying on inheritance.
- Livewire 3 can reparent DOM nodes during hydration — components relying on precise DOM position for layout should use `position: fixed`/`absolute` rather than flex parent-child order.

## Verification discipline

- After any change: `php artisan optimize:clear` inside the container.
- CSS/published assets: re-publish (`vendor:publish --force`) then hard-refresh; app CSS/JS: `npm run build` (or `npm run dev` locally).
- Database changes: confirm with an actual `SELECT`, not an assumption that the migration "should have" worked.
- Deploy: run the full deploy script (`scripts/deploy-manual.sh`), then verify on the live site — not just that the script exited 0.
- Env vars: a shell-exported var can silently override `.env` via `Dotenv\Repository` reading `getenv()`/`$_SERVER`/`$_ENV` at boot. If an env value looks wrong, check all three sources, not just the `.env` file.

## Session workflow

1. Read `knowledge.md` first — session history, past incidents, and current state live there, not here.
2. Confirm real environment access (SSH, credentials, tool availability) before planning work that depends on it — see "Environment reality check" above.
3. Scope the task, report the plan before implementing anything non-trivial.
4. Ship small atomic PRs with real verification evidence.
5. Before ending a phase: update `knowledge.md`'s Session Log (work done, files touched, decisions, status, edge cases) and make sure "Current Status" at the top doesn't lag more than one session behind merged `main`. Log PR-open state (number/URL/branch) and merge state (commit SHA) as they happen — don't leave stale "not pushed"/"opening PR" stubs after the PR is actually open or merged.

## Why one canonical file

This repo previously had three parallel, overlapping rule systems: `.ai/rules/*.md` (path-scoped, for OpenCode), `.cursor/rules/*.mdc` (path-scoped + always-applied, for Cursor), and this file (for Codex). They drifted — notably, `.cursor/rules/project-context.mdc` still said production hosting was Hetzner and documented the old Evolution API WhatsApp integration, both superseded (production is DigitalOcean; WhatsApp migrated to the Meta Cloud API — see `knowledge.md`). A rule three tools can't agree on isn't a rule, it's a trap for whichever agent reads the stale copy.

`.ai/rules/*.md` and `.cursor/rules/*.mdc` are now one-line pointers back to this file, kept only so each tool's native rule-loading mechanism (glob-scoped auto-attach) still fires and lands the agent here. Update rules in exactly one place: this file.
