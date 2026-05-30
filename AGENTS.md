# KlassApp — Community Documentation

## Project

KlassApp is a WhatsApp-based school-parent communication platform. This repo contains the public-facing community documentation (clients + investors) and private developer/ops docs.

**Docs URL (local preview):** http://localhost:4000  
**Served via:** docsify-cli (port 4000, docsify v4)

---

## Directory Structure

```
docs/
├── community/          # Public-facing docs (clients & investors)
│   ├── README.md       # Overview — problem, solution, national opportunity
│   ├── for-schools.md  # 6 roles: admin, teacher, bursar, librarian, nurse, secretary
│   ├── for-parents.md  # Menu walkthrough, LIN self-registration, opt-out, multi-child
│   ├── ecosystem.md    # Market (TAM/SAM/SOM), EMIS/LIN, competition, integrations, metrics
│   ├── faq.md          # 3 sections: schools, parents, technical
│   ├── roadmap.md      # Student transfers, roles, pricing, blockchain, IPFS, canteen, alumni
│   ├── index.html      # Docsify config with brand theme
│   ├── _sidebar.md     # Sidebar navigation
│   └── klassapp-logo.svg  # KlassApp icon mark (500x500, transparent bg, gradient)
└── dev/                # Private developer/ops docs (originally docs/whatsapp/*)
    ├── index.html      # Docsify config (port 4001)
    ├── _sidebar.md     # Sidebar navigation
    ├── klassapp-logo.svg  # Same icon mark as community
    ├── README.md       # Architecture overview
    ├── admin-dashboard.md
    ├── api-reference.md
    ├── cost-optimization.md
    ├── emis-lin-onboarding.md
    ├── interactive-menu.md
    ├── models.md
    ├── service-layer.md
    ├── setup.md
    └── testing.md
```

---

## Brand Assets

| Element | Value |
|---|---|
| Blue | `#1E6FD9` |
| Green | `#22C55E` |
| Dark | `#0F172A` |
| Light bg | `#F8FAFC` |
| Blue dark | `#185DA8` |
| Green dark | `#16A34A` |
| Heading font | `Bricolage Grotesque` (Google Fonts) |
| Body font | `Inter` (Google Fonts) |
| Logo | `docs/community/klassapp-logo.svg` (icon mark only, transparent bg, gradient) |

Docsify serves from `/docs/community/` (port 4000) and `/docs/dev/` (port 4001), each via `index.html` with:
- Logo in sidebar via `logo` config + `name` config (name must be non-empty for logo to show)
- Favicon using the icon SVG (`<link rel="icon" type="image/svg+xml" href="klassapp-logo.svg">`)
- Pagination (prev/next) at page bottom (community only)
- Mermaid diagram rendering (community only)
- Dark mode toggle (community only)
- Syntax highlighting (bash, json)
- Emoji plugin

---

## Content Decisions

### What's PUBLIC (docs/community/)
- Problem/solution overview, national opportunity
- School role workflows (6 roles with Mermaid diagrams)
- Parent experience, LIN self-registration, opt-out
- Market analysis (TAM/SAM/SOM), EMIS/LIN as moat
- Competition quadrant (KlassApp vs SchoolBridge, etc.)
- ERP integration as core differentiator
- FAQ for schools, parents, technical
- Roadmap (student transfers, blockchain voting, IPFS, canteen, alumni, integrations)

### What's HIDDEN from public
- **Pricing/cost details** — the Meta 24-hour service window (free messages) is NOT exposed. Rephrased as "cost-effective."
- **Revenue model** — removed from all public docs.
- **Developer/ops details** — all moved to `docs/dev/` (gated/private).

### Key Positioning
- ERP integration is the **core differentiator**: KlassApp sits on top of any school ERP and surfaces data to parents, without replacing the school's existing system.
- EMIS/LIN integration is the **key moat** — Uganda's national education database.
- Student transfers: Former school keeps a copy. Long-term vision: blockchain where student/parent owns their data.
- Blockchain voting: No WhatsApp mention — just the core concept.

---

## Roadmap (Planned)

1. Student transfers between KlassApp schools
2. More school roles (add to content)
3. Fix pricing presentation
4. Blockchain-based voting (not WhatsApp-dependent)
5. IPFS file management
6. Canteen module
7. Alumni module
8. Integrations: School Pay, Google Workspace, Notion, custom WhatsApp extensions for ERPs

---

## Session History

- Docker-based docsify was replaced with `npx docsify-cli` to avoid Docker slowness/hangs.
- Original content moved from `docs/whatsapp/*` → `docs/dev/`.
- Pivoted from developer docs to community-facing docs for clients and investors.
- Docs are Markdown + Mermaid (no separate SSG; docsify for preview only).
- Logo SVG was a long debugging process:
  - Fake composite SVG (wrong) → dark bg SVG (didn't appear) → icon-only SVG with `logo` + `name` config (current, working).
  - Issue was: `name` config must be set for `logo` to display in docsify.
- Dev docs now have their own docsify server at port 4001 with sidebar navigation.
- Favicon added to both sites using the KlassApp icon SVG.
- AGENTS.md created to capture project context, decisions, and session memory.

---

## Commands

```bash
# Serve docs locally
cd docs/community && npx docsify-cli serve . --port 4000

# Hard refresh (Cmd+Shift+R) to bypass cache
```
