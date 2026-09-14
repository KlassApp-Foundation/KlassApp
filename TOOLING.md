# KlassApp Tool Stack — What to Use When

*Living reference. Update this file whenever a new tool is adopted or a tool's role changes. The goal: every task has one obvious right tool, not a guess.*

---

## Coding agents

| Tool | Use for | Notes |
|---|---|---|
| **Cursor** (via `cursor-agent` CLI, run inside PHPStorm) | Default daily driver for all real feature work, bug fixes, and design implementation | Has laravel-boost, phpstorm, laravel-cloud, miro-mcp, and canva MCP servers wired in |
| **Claude Code** | When you specifically need `/design` or `/design-sync` integration with Claude Design | Requires its own setup (JetBrains plugin or CLI), separate from Cursor. Must be authenticated via a real claude.ai account login (Pro/Max/Team/Enterprise) — API/Console credits alone will NOT work for Design Sync. |
| **OpenCode** | Backup/secondary agent, proven working with the same MCP stack | Use if Cursor is unavailable or for a second opinion on a tricky fix |
| **Goose** | Not currently reliable | Has an unresolved MCP-loading bug — avoid until fixed |

## Orchestration

| Tool | Use for |
|---|---|
| **Nimbalyst** | Multi-session task tracking when work needs to span more than one sitting — Tracker items, kanban view, periodic check-ins instead of babysitting every step |

## Design & mockups

| Tool | Use for |
|---|---|
| **Open Design** | Default for ALL mockup work before touching real Blade/CSS — fast HTML/CSS generation, tightly coupled to Cursor, produces code that ports directly into the build |
| **Claude Design** | Only for the harder creative pieces Open Design has genuinely struggled with (e.g. the Toshi hub illustration) — also the right tool for pitch decks/GTM materials, social/campaign visuals, and a final unifying polish pass across the whole product once the full design phase (public pages + dashboards) is done | Runs on its OWN separate weekly quota, distinct from Claude chat/Code/Cowork — genuinely exhausts fast on heavy component-sync work. |
| **Canva** (MCP-connected) | Quick social/marketing assets, especially anything template-driven | Same OAuth pattern as Miro |
| **Miro** (MCP-connected) | Shared planning boards, reference moodboards, structure diagrams — for the team to collaborate visually | "KlassApp Landing Design" board already exists |

## Knowledge work & document synthesis

| Tool | Use for | Notes |
|---|---|---|
| **Claude Cowork** | Non-technical, cross-file, multi-source document/data synthesis — point it at a folder or set of files for a finished deliverable without coordinating each step. Real fits: the formal go-to-market plan, organizing the "KlassApp — Business & Ops" Drive folder, turning the data-collection audit into a finished privacy policy document. | Shares the SAME usage pool as Claude chat and Claude Code (NOT a separate quota like Design) — burns usage 5-20x faster per task than plain chat. Avoid heavy Cowork sessions on the same day as heavy Claude Code work, since they compete for one shared allowance. Don't use for anything overlapping actual coding — that stays with Cursor/Claude Code. |

## Infrastructure

| Tool | Use for |
|---|---|
| **Laravel Cloud** | Hosting, managed MySQL + Valkey, deploys, staging environment | Deploy trigger is a raw API call (documented in knowledge.md), not the MCP (read-only) |
| **Doppler** | Secrets management | Never print secrets to stdout when scripting against it |
| **Cloudflare R2** | Object storage for WhatsApp report PDFs | |

## Monitoring & reliability

| Tool | Use for |
|---|---|
| **Nightwatch** | Real application monitoring — slow routes, failed jobs, exceptions, N+1 queries | Live on staging + production, first-party Laravel, request sampling at 10% |
| **Instatus** (deferred until after UI phase) | Public status page for schools during any real incident | Free tier to start, custom domain needs the paid tier later |
| **Sentry** (not adopted — deliberate) | Only relevant later if frontend/Vue-specific JS error tracking becomes a real need | Nightwatch covers the backend; don't add Sentry unless this specific gap becomes real |

## Communication

| Tool | Use for |
|---|---|
| **WhatsApp Business API (Meta)** | The core product channel — parent links, report delivery, Toshi's primary interface | |
| **Mailtrap** | Transactional email — invites, OTPs, password resets | Production-configured |

## Version control & testing

| Tool | Use for |
|---|---|
| **GitHub** | Repo, PRs, issues, branch protection | Every PR needs `merged:true` confirmed via API before being treated as shipped |
| **Playwright** | Real browser/E2E verification | Required for any UI change — screenshots and functional checks, not just visual claims |
| **PHPUnit** | Backend/unit test coverage | |

---

## The rule of thumb

Before starting any task, ask: is this (a) writing/fixing code → Cursor, (b) exploring a visual direction before code exists → Open Design first, escalate to Claude Design only if it's a genuinely hard creative piece, (c) tracking multi-session work → Nimbalyst, (d) planning/collaborating visually with the team → Miro, (e) non-technical document/data synthesis across files → Cowork (mindful it shares your coding budget), (f) infrastructure/deploy → the real API, not the read-only MCP, (g) anything needing a real account login (Doppler, Cloud console, Google Console, Nightwatch, Canva/Miro OAuth) → you personally, never delegated to an agent.
