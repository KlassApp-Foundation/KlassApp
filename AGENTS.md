# AGENTS.md — KlassApp Canonical Agent Rules

> **This is the single canonical rules file for every coding agent working on this repo** — Claude Code, Codex, OpenCode, Cursor, or any other tool. `.ai/rules/*.md` and `.cursor/rules/*.mdc` are now one-line pointers back to this file (see "Why one canonical file" below). If you're an agent reading a pointer file, come here for the actual rules.
>
> For full project history, past incidents, and session-by-session decisions, sync to latest `origin/main` first (standing rule #19), then read `knowledge.md` in the repo root **before planning**. Update it before ending a phase of work (see "Session workflow" below). This file is the distilled, standing rule set; `knowledge.md` is the historical record and the source of truth for current hosting/ops.

See TOOLING.md for the full stack reference — which tool to use for which kind of task.

## Environment reality check

Production is **Laravel Cloud** (`klassapp.xyz`, EU-West-1). Staging exists on the same Cloud app with **demo/seed data only**. The DigitalOcean droplet (`root@46.101.111.131` / `sms-app` Docker) is **retired** — do not SSH it, do not plan work that depends on that host, and do not treat droplet SSH as production access.

**Do not assume you have production access.** Whether a given session can inspect or operate Cloud is inconsistent across sessions and tools. Verify what you actually have *this session* before planning work that depends on it — Laravel Cloud MCP (read-only inspect), Commands API (artisan/shell on an environment), deploy POST (real release), Doppler token (`CLOUD_AGENT_TOOLING` or equivalent). Scope your work to what you've confirmed. If you lack access, say so plainly and hand off anything that genuinely needs it — **never guess at production state**.

**Verify this yourself, every session — do not carry over another session's answer.** Different sessions/tools running against this repo have had different Cloud credentials on the same night, and more than once that was only discovered after real work had already happened on a false assumption. A previous session (this one, or another tool) reporting "I have Cloud access" or "I don't" tells you nothing about your own session. Check the actual tools/credentials available now, note the result, then proceed.

Current Cloud env IDs, deploy vs Commands patterns, staging notes, and token retrieval live in `knowledge.md` (Verified Stack + Laravel Cloud MCP / Commands / Deploy sections). Prefer that file over duplicating long infra docs here. Do not hardcode secrets or tokens.

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
15. **Deploy from the correct dedicated main worktree**, not an arbitrary local checkout — this project uses a worktree-based workflow (see `knowledge.md`'s note on the canonical `knowledge.md` path and worktree sync). Confirm you're on the right tree and that it matches latest `origin/main` (standing rule #19) before triggering a production deploy. Production deploys are Laravel Cloud `POST …/deployments`, not SSH to a droplet — patterns live in `knowledge.md`.
16. **When output looks garbled or suspicious, write it to a file and read the file back** — don't trust a raw streamed terminal render for anything you're about to act on (a rendered PDF, an image, long structured output). This has caught real false negatives before.
17. **Configurable, but never blank — prefill sensible defaults.** When a step's underlying data model is genuinely configurable (any number of terms, any class names, any fee structures), the UI presented to the user should still start with sensible, real-world defaults pre-filled — not an empty form asking them to build structure from scratch. Example: academic terms are stored as fully configurable (any number, any names — see `saveTerms`), but the UI should prefill the 3 standard UNEB terms as a starting point, which the school can then edit, add to, or remove from. Apply this consistently across **both interfaces** — Toshi's chat flow and the manual wizard's forms — whenever either is designed or touched, not just one. **Status: documented principle, not yet implemented.** The terms backend (`saveTerms`) is shipped; the terms UI in Toshi and the wizard has not been redesigned to prefill defaults yet. Full skill with known defaults per step, trigger guidance, and implementation checklist: `docs/onboarding-defaults-skill.md`.
18. **Database lookups must always use the primary key (`id`) or another guaranteed-unique column — never `name`, or any other field with no uniqueness constraint.** This caused a real cross-school data bug (fixed in [PR #517](https://github.com/KlassApp-Foundation/KlassApp/pull/517)) where a name collision between two different students at two different schools resolved to the wrong user. Scope by `id` (and `school_id` when the lookup is tenant-bound); do not identify a row by display name alone.
19. **Sync to latest `origin/main` before starting any task.** Do not begin work on a stale branch or checkout. Practical sequence for agents (local worktrees, cloud VMs, or a fresh clone):
    1. `git fetch origin main`
    2. If this worktree is on `main`: fast-forward (`git merge --ff-only origin/main` or `git pull --ff-only origin main`).
    3. If you need a feature branch: create it from the just-fetched `origin/main` (`git checkout -b <branch> origin/main`), not from an old local SHA.
    4. If you are already on a feature branch that must continue: merge or rebase `origin/main` into it *before* new work, unless the task is explicitly to stay on a detached/historical SHA.
    If fetch/update fails, say so and stop rather than implementing against an unknown stale tree.
20. **Read `knowledge.md` first in any session, before planning** (after standing rule #19). Update it before ending a phase of work: a Session Log entry (date, work done, files touched, decisions, status, edge cases), PR number/URL/branch when you open one, and the merge commit SHA + refreshed "Current Status" when it merges. Don't leave "not pushed" / "opening PR" stubs once the PR is actually open or merged. On hosting/ops facts, `knowledge.md` wins if this file and that file disagree.
21. **Confirm `merged: true` via the GitHub API before treating a PR as shipped.** A chat claim, a local `gh pr merge` exit code you did not re-check, or "I think it merged" is not enough. After every merge (including your own admin merge), call `gh api repos/KlassApp-Foundation/KlassApp/pulls/{n} --jq '{merged,merge_commit_sha,merged_at}'` (or equivalent) and record the SHA. Do not update `knowledge.md` Current Status to MERGED until that check returns `merged: true`.
22. **UI and marketing surfaces need real browser verification, not assertion-only green.** Prefer Playwright (or an equivalent real browser pass) at the project viewports (375 / 414 / 768 / 1280) against local or staging. PHPUnit `assertSee` / "the Blade looks right" is not sufficient alone for landing, auth shells, or other user-visible chrome.
23. **Never invent or guess at facts you have not verified from a real source.** Hosting IDs, env values, "this is already fixed," file contents, API shapes, and contributor intent are all claims until checked. If you cannot verify, say so and ask or hand off. Do not fabricate a plausible answer to keep moving.
24. **Cloud deploys use the documented deploy POST, not the read-only Laravel Cloud MCP.** MCP inspect (`list-deployments`, metrics, logs) is not a release. Shipping is an empty-body `POST …/environments/{id}/deployments` (token and env IDs in `knowledge.md`), then poll until `deployment.succeeded` and verify the live URL. Staging and production are different environments; merging to `main` does not ship production.
25. **External contributor PRs require genuine human review before merge.** Do not self-merge unreviewed outside work on trust of the branch tip. Read the notes and the full diff. Keep useful findings; extract safe pieces into small follow-up PRs when the tip mixes good fixes with unsafe scope. Concrete precedent: Elijah-ug [#552](https://github.com/KlassApp-Foundation/KlassApp/pull/552) stayed open as the findings source; duplicate [#625](https://github.com/KlassApp-Foundation/KlassApp/pull/625) was closed; only verified safe pieces shipped in [#638](https://github.com/KlassApp-Foundation/KlassApp/pull/638). See also `CONTRIBUTING.md`.
26. **Confirm `pwd`, git branch, and worktree before any git write.** This machine often has dozens of KlassApp worktrees (40+ is normal). Running `commit` / `push` / `checkout` in the wrong tree is a real failure mode. `pwd` + `git branch --show-current` + `git worktree list` (when unsure) before mutating git state. Standing rule #15 still applies for production deploys from the dedicated main worktree.
27. **Periodically audit AI-tool scaffolding at the repo root.** Tool-specific dirs accumulate (`.cursor/`, `.ai/`, `.design-sync/`, `.devin/`, plus gitignored copies under `.agents/`, `.claude/`, `.opencode/`, etc.). Before adding a new one, check whether an existing path already covers it. Tracked rule *content* belongs only in this file; tool dirs may hold pointers, skills, or durable inputs (see "Why one canonical file" and the root audit note below). Do not commit regeneratable caches (`node_modules`, `.playwright-mcp/`, `.ds-sync/`, `ds-bundle/`).

## Known bug patterns (quick reference — full detail in `knowledge.md`)

Before editing code in these areas, check the fix markers below are still in place. Full root-cause / fix / verification detail lives in `knowledge.md`'s "Known Bug Patterns & Lessons" section — this is a locator, not a replacement for reading it.

1. **Duplicated business logic across render paths** (`generatePdf()`) — shared logic must live in exactly one place; real implementation is `StudentReportCardService::generatePdf()`; `ReportCardsController::generatePdf` is a thin BC delegate for jobs/commands (`grep -n "function generatePdf" app/Services/StudentReportCardService.php app/Http/Controllers/Admin/ReportCardsController.php`).
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

- After any change: `php artisan optimize:clear` locally (Docker) or via Cloud Commands on the target environment — not via SSH to the retired droplet.
- CSS/published assets: re-publish (`vendor:publish --force`) then hard-refresh; app CSS/JS: `npm run build` (or `npm run dev` locally).
- Database changes: confirm with an actual `SELECT`, not an assumption that the migration "should have" worked.
- Deploy: production is Laravel Cloud; merging to `main` does not ship (push-to-deploy is off on prod). Trigger and poll per `knowledge.md` (empty-body `POST …/environments/{id}/deployments`), then verify on the live site — not just that the HTTP call returned 201. Do not run `scripts/deploy-manual.sh` against the retired droplet. Standing rule #24.
- UI chrome: real browser/Playwright at 375 / 414 / 768 / 1280 when the change is user-visible — standing rule #22. Do not close on PHPUnit `assertSee` alone.
- PR ship claims: confirm GitHub API `merged: true` + record merge SHA before stamping `knowledge.md` — standing rule #21.
- Env vars: a shell-exported var can silently override `.env` via `Dotenv\Repository` reading `getenv()`/`$_SERVER`/`$_ENV` at boot. If an env value looks wrong, check all three sources, not just the `.env` file. Cloud env vars apply only after a **real deployment** (see `knowledge.md`).

## PhpStorm MCP direct-HTTP workaround

Devin's platform-level MCP support does **not** include the `phpstorm` server type. The platform explicitly reports `The agent does not support the following MCP servers: phpstorm`. This is **not** a missing header, malformed `mcpServers` config, or authentication problem — do not re-diagnose it.

Instead, call the native PhpStorm MCP server directly with raw HTTP. The native server is built into **PhpStorm 2026.1+** and runs on `http://127.0.0.1:64342/stream` by default. Do **not** confuse it with the old third-party "MCP Server AI Companion" plugin, which runs on a different port/endpoint and rejects these calls with `405` — that is a different problem and unrelated to this one.

### Initialize once per task

```bash
curl -s -i -X POST http://127.0.0.1:64342/stream \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{"jsonrpc":"2.0","method":"initialize","params":{"protocolVersion":"2024-11-05","capabilities":{},"clientInfo":{"name":"devin","version":"1.0"}},"id":1}'
```

- Save the `mcp-session-id` header from the response.
- Re-use that exact header on every subsequent call. Header name is `mcp-session-id` (lowercase in the raw response).
- Each tool call is another JSON-RPC `POST` to `http://127.0.0.1:64342/stream` using `method: "tools/call"` and `params: { "name": "...", "arguments": { ... } }`.

### Confirmed tools and exact required parameters

| Tool | Required arguments | Notes |
|---|---|---|
| `get_file_text_by_path` | `projectPath`, `pathInProject` | `pathInProject` is relative to the project root. Do **not** use `path`. Optional `maxLinesCount` and `truncateMode`. |
| `laravel_idea_get_eloquent_model` | `projectPath`, `modelFqn` | e.g. `App\\Models\\User` |
| `laravel_idea_get_routes` | `projectPath` | Optional `urlPattern` or `routeTargetPattern` |
| `run_inspections` | — | Confirmed available; call with the same JSON-RPC shape |

### Verified working examples

- `get_file_text_by_path` with `pathInProject: "knowledge.md"` returned real file content.
- `laravel_idea_get_eloquent_model` with `modelFqn: "App\\Models\\User"` returned ~12 KB of real fields, relations, and related files.

Use this direct-HTTP pattern whenever structured PHP/Laravel code access is needed instead of `mcp_call_tool` for the `phpstorm` server.

## Session workflow

1. **Sync to latest `origin/main`** before starting — fetch + fast-forward or branch from `origin/main` (standing rule #19). Do not start work on a stale checkout. Confirm `pwd` / branch / worktree first (standing rule #26).
2. Read `knowledge.md` first — session history, past incidents, current hosting/ops, and current state live there, not here. If this file and `knowledge.md` disagree on infra, `knowledge.md` wins.
3. Confirm real environment access (Cloud MCP / Commands API / deploy trigger / Doppler — not SSH to the retired droplet) before planning work that depends on it — see "Environment reality check" above. Do not invent missing facts (standing rule #23).
4. Scope the task, report the plan before implementing anything non-trivial.
5. Ship small atomic PRs with real verification evidence (tests + Playwright where UI; standing rules #11 / #22). External contributor PRs: human review before merge (standing rule #25).
6. Before ending a phase: update `knowledge.md`'s Session Log (work done, files touched, decisions, status, edge cases) and make sure "Current Status" at the top doesn't lag more than one session behind merged `main`. Log PR-open state (number/URL/branch) and merge state **only after** GitHub API `merged: true` (standing rule #21) — don't leave stale "not pushed"/"opening PR" stubs after the PR is actually open or merged.

## Why one canonical file

This repo previously had three parallel, overlapping rule systems: `.ai/rules/*.md` (path-scoped, for OpenCode), `.cursor/rules/*.mdc` (path-scoped + always-applied, for Cursor), and this file (for Codex). They drifted — notably, `.cursor/rules/project-context.mdc` still said production hosting was Hetzner and documented the old Evolution API WhatsApp integration. Both of those were already wrong then; current ops truth (see `knowledge.md`) is **Laravel Cloud** (`klassapp.xyz`, EU-West-1) with WhatsApp on the **Meta Cloud API**. The DigitalOcean droplet is retired. A rule three tools can't agree on isn't a rule, it's a trap for whichever agent reads the stale copy.

**`AGENTS.md` is the single source of truth for agent rules.** `.ai/rules/*.md` and `.cursor/rules/*.mdc` are **not** a second rules location. They are thin machine pointers (YAML/frontmatter + a one-line "see AGENTS.md" body) kept only so each tool's native rule-loading mechanism (Cursor `alwaysApply` / globs, OpenCode path globs) still fires and lands the agent here. Do not add standing rules, stack facts, or bug lessons into those pointer files — edit this file only. If a pointer grows past a redirect, it has drifted; shrink it back.

### Root AI-tool audit note (2026-09-16)

| Path | Verdict | Why |
| --- | --- | --- |
| `.cursor/rules/*.mdc` | **Keep (pointers)** | Cursor always-apply / glob attach. Content = redirect to this file. |
| `.ai/rules/*.md` | **Keep (pointers)** | OpenCode path attach + `index.md` map. Content = redirect to this file. |
| `.design-sync/` | **Keep (durable inputs)** | Claude Design sync sources (`NOTES.md`, shim, previews, conventions). Regeneratable caches stay gitignored (`.cache/`, `ds-bundle/`, `.ds-sync/`). See [#547](https://github.com/KlassApp-Foundation/KlassApp/pull/547). |
| `.devin/skills/phpstorm-mcp/` | **Keep** | Devin-only skill for native PhpStorm MCP over HTTP (platform lacks `phpstorm` server type). Mirrors the PhpStorm section above; not a rules fork. |
| `.agents/`, `.claude/`, `.opencode/`, `.playwright-mcp/`, `.gemini/`, `.junie/`, `.sisyphus/`, `.history/` | **Local clutter (gitignored)** | Tool caches / generated skill copies. Do not commit. Do not treat as canonical rules. |
| `CLAUDE.md`, `opencode.json`, `.mcp.json`, `boost.json` | **Local / generated (mostly gitignored)** | Boost guidelines may exist for local install; standing KlassApp rules still live here. |

Legitimate app structure at repo root (keep): `app/`, `bootstrap/`, `config/`, `database/`, `docs/`, `e2e/`, `lang/`, `packages/`, `public/`, `resources/`, `routes/`, `scripts/`, `storage/`, `tests/`, plus committed project docs (`AGENTS.md`, `TOOLING.md`, `README.md`, community health files) and build manifests. Scratch/`tmp/` and `node_modules`/`vendor` are expected local noise.

Standing rule #27: re-run this kind of audit when new tool dirs appear, before adding another parallel rules tree.
