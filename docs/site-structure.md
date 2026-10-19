# Docs site structure

**Status: proposal.** Phase 1, docs only. One public docs home, three sections. This file lists the pages and a one-line purpose for each. It does not write those pages, except the Early Adopters Program page, which is drafted at the path named below.

The current public set is the community tree under `docs/community/`, published on GitBook from this repo, plus the short map in `docs/README.md`. `docs/dev/` is archived (the DigitalOcean and Evolution API era) and is not a section of this site. DeepWiki stays the code-derived architecture reference; this site does not become a second wiki.

## Home

| Page | Purpose |
|---|---|
| `docs/README.md` | The docs home: three sections (Schools, Developers, Community) and nothing else on the first screen. |

## Schools

| Page | Purpose |
|---|---|
| `docs/schools/getting-started.md` | What a school does on day one, and where staff sign in. |
| `docs/schools/onboarding.md` | Set the school up with the onboarding Wizard or with Toshi. |
| `docs/schools/whatsapp-for-parents.md` | How parents get school updates on WhatsApp once they are linked. |
| `docs/schools/marks-and-reports.md` | How marks are entered, approved, and released as reports. |

## Developers

| Page | Purpose |
|---|---|
| `docs/developers/self-hosting.md` | How to run KlassApp yourself; published only after the independent security review. |
| `docs/developers/building-packages.md` | How to build, register, version, enable, and test a package. |
| `docs/developers/api-and-mcp.md` | The HTTP API and the MCP connectors a developer can call. |

## Community

| Page | Purpose |
|---|---|
| `CONTRIBUTING.md` | How to contribute code, tests, and docs. |
| `docs/community/early-adopters.md` | Who the Early Adopters Program is for, what a school receives, and how to apply. |

The Early Adopters page is the one full draft in this change. It lives at `docs/community/early-adopters.md` so it sits in the community section and in the tree GitBook already syncs.

## Where today's pages go

Do not start a parallel copy. When a section is written, extend the existing page.

| New page | Start from |
|---|---|
| Getting started | `docs/community/for-schools.md` |
| Onboarding | `docs/community/school-onboarding.md` and `docs/community/book-onboarding.md` |
| WhatsApp for parents | `docs/community/for-parents.md` |
| Marks and reports | The marks-flow proposal on [#1118](https://github.com/KlassApp-Foundation/KlassApp/pull/1118) (`docs/product/marks-flow.md`), once the owner accepts it |
| Building packages | `docs/architecture/packages.md` on [#1119](https://github.com/KlassApp-Foundation/KlassApp/pull/1119), once the owner accepts it |
| Contributing | `CONTRIBUTING.md` (already canonical; do not add a second file) |
| Self-hosting | New, after the security review. Do not revive `docs/dev/digitalocean-deployment.md` |
| API and MCP | `docs/architecture/toshi-shape-2026-09.md` and `routes/ai.php` as the code map, not as the public page |

`docs/community/faq.md`, `docs/community/ecosystem.md`, and `docs/roadmap.md` stay linked from the home until a section absorbs them. They are not a fourth section.
