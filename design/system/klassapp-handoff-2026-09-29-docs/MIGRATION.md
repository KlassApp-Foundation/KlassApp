# Docs migration map (Docsify + GitBook → VitePress)

- **Read at:** `main` @ `9f3297546d8c`. That's the ref the GitHub tool resolved; confirm it with `git rev-parse main`.
- **GitBook:** read from its public index (`klassdocs.gitbook.io/klassapp-documentation/llms.txt`) on 2026-09-29. Your message still had the `<PASTE URL>` placeholder, so I used the URL linked from `docs/README.md` and `docs/community/_sidebar.md`. **Confirm that's the right space.**
- **Old links:** Docsify URLs are hash routes (`/docs/community/#/for-parents`), and the browser never sends the `#…` part to the server. Server redirects can't catch them, so they're handled by the inline script in `config.mts` `head`, driven by `.vitepress/legacy-hash-map.ts`. Path-based URLs get real 301s in Laravel (section 5).

## 1. Docsify hub (`/docs/#/…`)
| Today | New URL | Action |
|---|---|---|
| `/docs/#/` (README.md, docs map) | `/docs/` | **Replace**: the new home page with the Help and Community doors. The docs-map table moves to the Community overview. |
| `#/architecture.md` | `/docs/community/architecture` | Move (Community template) |
| `#/roadmap.md` | `/docs/community/roadmap` | Move |
| `#/project-provenance.md` | `/docs/community/provenance` | Move. **It's linked from the hub sidebar today but isn't in `DocsController::PUBLIC_ROOT_FILES`, so the link 404s now.** |
| `#/community/`, `#/dev/` | `/docs/community/`, `/docs/community/archive/` | Hash-map redirect |

## 2. Community Docsify (`/docs/community/#/…`)
| Today | New URL | Action |
|---|---|---|
| `#/` README ("moved to GitBook") | `/docs/community/` | Rewrite as the Community overview. Drop the GitBook banner and the plans table. |
| `#/for-schools` | `/docs/help/admins/` | **Merge** into the Admin role landing |
| `#/school-onboarding` | `/docs/help/admins/onboarding` | Move to Help |
| `#/book-onboarding` | `/docs/help/admins/book-onboarding` | Move to Help. Keep the form, which posts to `/api/onboarding/book`. |
| `#/for-parents` | `/docs/help/parents/` + `/docs/help/parents/whatsapp` | Split: the landing plus a how-to |
| `#/ecosystem` | `/docs/community/connectors` | Merge into Connectors and MCP |
| `#/faq` | `/docs/help/faq` | Move; developer questions go to the Community overview |
| `#/roadmap` (deprecated stub) | `/docs/community/roadmap` | Drop the stub; redirect |
| `klassapp-logo.svg`, `klassapp-horizontal-light.svg` | `docs-site/public/` | Copy once (they're duplicated in `dev/` today) |

## 3. Dev Docsify, archived (`/docs/dev/#/…`)
It documents the old Evolution API and DigitalOcean stack. Production is now Meta Cloud API on Laravel Cloud.

| Today | New URL | Action |
|---|---|---|
| `#/` README | `/docs/community/archive/` | Archive index with the existing banner. **Its env examples contain a real business WhatsApp number; replace it with a placeholder.** |
| `#/setup`, `#/service-layer`, `#/interactive-menu`, `#/admin-dashboard`, `#/api-reference`, `#/models`, `#/cost-optimization`, `#/ai-agent-layer` | `/docs/community/archive/{same-name}` | Keep in Archive with the "Historical: Evolution API" banner. Rewrite or delete later. |
| `#/emis-lin-onboarding` | `/docs/community/reference/emis-lin` | Refresh and move to Reference (LIN is live product scope) |
| `#/schoolpay-integration` | `/docs/community/reference/schoolpay` | Move to Reference, labelled **Spec, not shipped** unless it's live |
| `#/digitalocean-deployment` | `/docs/community/archive/` | **Drop** (historical ops) |
| `#/testing` | `/docs/community/getting-started#tests` | Merge into Getting started |

## 4. GitBook (`klassdocs.gitbook.io/klassapp-documentation/…`)
GitBook mirrors the **whole repo** via GitHub Sync, including files `DocsController` deliberately blocks. **Unpublish the space as soon as P4 lands, and consider doing it now:** it publicly serves internal audits, the IDOR tracking note and `knowledge.md`.

| GitBook page(s) | New URL | Action |
|---|---|---|
| `/` (repo README) | `/docs/community/` + `/docs/community/getting-started` | Merge: overview plus Quick Start |
| `/contributing`, `/.github/pull_request_template` | `/docs/community/contributing` | Merge |
| `/security`, `/.github/security` | `/docs/community/security` | Move (one copy) |
| `/code_of_conduct` | `/docs/community/code-of-conduct` | Move |
| `/.github/issue_template/*` (3) | none | Drop: GitHub renders these |
| `/docs` (docs map), every `*/_sidebar` | none | Drop: Docsify internals |
| `/docs/architecture`, `/docs/roadmap`, `/docs/project-provenance` | as in section 1 | Move |
| `/docs/community/*` (7 pages) | as in section 2 | Same as Docsify |
| `/docs/dev/*` (12 pages) | as in section 3 | Same as Docsify |
| `/docs/ds-pattern-library`, `/resources/views/components/design_system`, `/.design-sync`, `/.design-sync/conventions`, `/.design-sync/docs/*` (9) | `/docs/community/design-system` | Merge into one page; drop the per-component stubs |
| `/resources/assets/brand/favicons` | `/docs/community/design-system#favicons` | Merge |
| `/resources/assets/brand/models/sources`, `/resources*` index pages | none | Drop (keep them in the repo) |
| `/packages/toshi-ui` | `/docs/community/reference/toshi-ui` | Move |
| `/e2e` | `/docs/community/getting-started#tests` | Merge |
| `/docs/build-safeguards` | `/docs/community/reference/build-safeguards` | Move |
| `/docs/ops/slack-connector-go-live-checklist` | `/docs/community/connectors#slack` | Summarise the status only; the checklist stays internal |
| `/docs/plans/*` (4), `/docs/ai-integration-roadmap`, `/docs/onboarding-engine-plan`, `/docs/onboarding-defaults-skill` | none | **Drop from public docs.** These are plans, not shipped behaviour. |
| `/docs/landing-page-content`, `/docs/klassapp-landing-content-final-v4`, `/docs/css-consolidation-plan` | none | Drop (working notes) |
| `/docs/toshi-*-audit` (5), `/docs/toshi-prod-health-check`, `/docs/legacy-portal-idor`, `/docs/stage3-unresolved-cases`, `/docs/test-suite-triage-2026-09-26` | none | **Drop: internal and security-sensitive.** |
| `/docs/internal/*`, `/docs/research/*`, `/docs/ui-ecosystem-comparison`, `/docs/evidence/*`, `/docs/bugfix-ay-whatsapp`, `/docs/archive/*`, `/docs/readme` | none | Drop (DocsController already denies these) |
| `/agents`, `/tooling`, `/knowledge`, `/.ai/rules/*` (5), `/.devin/skills/*` (3), `/.design-sync/notes` | none | **Drop: maintainer and agent files.** |

GitBook has no page-level redirects after unpublishing. Replace the space's homepage with a one-line pointer to `https://klassapp.xyz/docs/`, keep it for 90 days (**you decide the 90 days**), then delete the space.

## 5. Server redirects (Laravel, 301)
| From | To |
|---|---|
| `/help`, `/help/` | `/docs/help/` |
| `/docs/README.md`, `/docs/index.html` | `/docs/` |
| `/docs/architecture.md` | `/docs/community/architecture` |
| `/docs/roadmap.md` | `/docs/community/roadmap` |
| `/docs/project-provenance.md` | `/docs/community/provenance` |
| `/docs/community/{page}.md` | the section 2 target |
| `/docs/dev/`, `/docs/dev/{page}.md` | the section 3 target |
| `/docs/shared/docsify-klassapp.css` | 410 Gone |
