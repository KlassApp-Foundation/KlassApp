# Docs site structure

**Status: proposal.** Phase 1, docs only. This file is internal (not published) and lists where public docs pages live and what each is for.

## Stack and design

- **Stack:** the existing VitePress site in `docs-site/` (`npm run docs:build` / `docs:dev`, config in `docs-site/.vitepress/config.mts`, sidebars in `docs-site/.vitepress/sidebars.ts`). It builds to `public/docs-preview/` and is served at `/docs-preview/` (Help is at `klassapp.xyz/help`). No second docs stack.
- **Design:** Claude Design's docs handoff in `design/system/klassapp-handoff-2026-09-29-docs/` (`HANDOFF.md` URL plan, `MIGRATION.md`, `design-system/styles/docs.css`, the voice guide). Pages use its theme (`docs-site/.vitepress/theme/klassapp.css`), its components (`<Steps>`, `<Step>`, `<ToshiCallout>`, …) and VitePress `::: tip` / `::: info` / `::: warning` callouts, and start from the page templates in `docs-site/help/_templates/` and `docs-site/community/_templates/`.
- The legacy Docsify/GitBook tree under `docs/community/` is being retired by that handoff (P3). New public pages are not added there.

## Home

| Page | Purpose |
|---|---|
| `docs-site/index.md` | Docs home: two doors (Help for schools, Community) and the one-line parent note. It carries the tagline; no other page repeats it. |

## Help (schools, by role)

| Page | Purpose |
|---|---|
| `docs-site/help/{admins,teachers,bursars,parents,students}/` | Role landings and how-tos, from `help/_templates/`. |
| `docs-site/help/admins/onboarding.md` | Set the school up with the onboarding Wizard or with Toshi. |
| `docs-site/help/marks.md`, `generate-report-cards.md` | Marks and reports. Extend from the marks-flow proposal on [#1118](https://github.com/KlassApp-Foundation/KlassApp/pull/1118) once the owner accepts it. |
| `docs-site/help/parents/whatsapp.md` | How parents get school updates on WhatsApp. |

## Community (developers and contributors)

| Page | Purpose |
|---|---|
| `docs-site/community/getting-started.md` | Run KlassApp locally (one copy of the Quick Start). |
| `docs-site/community/contributing.md` | How to contribute code, tests and docs. |
| `docs-site/community/reference/` | Reference pages. Building packages extends from `docs/architecture/packages.md` on [#1119](https://github.com/KlassApp-Foundation/KlassApp/pull/1119) once accepted. |
| `docs-site/community/connectors.md` | The HTTP API, MCP connectors and integrations. |
| `docs-site/community/early-adopters.md` | Who the Early Adopters Program is for, what a school receives, and how to apply. Drafted in this change. |

Self-hosting is written only after the independent security review; do not revive `docs/dev/digitalocean-deployment.md`.
