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
    ├── schoolpay-integration.md  # SchoolPay API integration spec (designed May 2026)
    ├── ai-agent-layer.md         # AI Agent Layer spec (designed May 2026)
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
- Problem/solution overview, East African opportunity
- School role workflows (6 roles with Mermaid diagrams)
- Parent experience, LIN self-registration, opt-out
- Market analysis (TAM/SAM/SOM), EMIS/LIN as moat
- Competition quadrant (KlassApp vs SchoolBridge, etc.)
- ERP integration as core differentiator
- FAQ for parents first, then schools, then technical
- Roadmap (student transfers, roles, pricing tiers, PTA elections, digital records, canteen, alumni, integrations)

### What's HIDDEN from public
- **Meta 24-hour service window** (free messages) is NOT exposed. Rephrased as "cost-effective."
- **Revenue model** — removed from all public docs.
- **Developer/ops details** — all moved to `docs/dev/` (gated/private).

### Key Positioning
- **Opening line**: "KlassApp: The school in every parent's pocket."
- **Core framing**: "Two products, one platform." — schools get management, parents get WhatsApp interface, parent is first-class user
- **Verbatim line** (appears in README, for-schools, ecosystem): "KlassApp doesn't replace a school's existing system — it adds a parent-facing communication layer on top."
- ERP integration is the **core differentiator**: KlassApp sits on top of any school ERP and surfaces data to parents, without replacing the school's existing system.
- EMIS/LIN integration is the **key moat** — Uganda's national education database.
- **Competition positioning verbatim**: "Every competitor on this chart serves the school. KlassApp is the only platform built to serve the parent."
- Student transfers: Former school keeps a copy. Long-term vision: blockchain where student/parent owns their data.
- Roadmap items reframed by outcome: "Transparent PTA Elections" (not blockchain voting), "Permanent Digital Records" (not IPFS), "Pricing Tiers" (not Fix Pricing Model).
- **Geography rule**: "East Africa" for all market-scale claims; "Uganda" only for Uganda-specific facts (EMIS, LIN, 74K schools, UNEB, MTN/Airtel, UGX).
- Footer/credits: keep "Built in Uganda" as pride statement.
- **SchoolPay integration**: SchoolPay (Fincom Technologies, licensed by Bank of Uganda) is a real payment aggregator with 20K+ schools and 5M+ parents. It processes school fees via MTN MoMo, Airtel Money, and 10+ banks. There is NO existing integration in the codebase — it's a Phase 5 roadmap item. The integration spec is at `docs/dev/schoolpay-integration.md`. Community docs correctly frame this as "LIN & Fee Management" (fee notifications via WhatsApp, payment handled externally by the school's existing channels).

---

## Roadmap (Planned)

1. Student transfers between KlassApp schools
2. More school roles (add to content)
3. Fix pricing presentation
4. Blockchain-based voting (not WhatsApp-dependent)
5. IPFS file management
6. Canteen module
7. Alumni module
8. Integrations: SchoolPay, Google Workspace, Notion, custom WhatsApp extensions for ERPs
9. **SchoolPay integration** — spec designed (May 2026). See `docs/dev/schoolpay-integration.md`.
10. **AI Agent Layer** — spec designed (May 2026). See `docs/dev/ai-agent-layer.md`.

---

## Completed Rewrite (May 2026)

All 6 community docs files were rewritten in a comprehensive pass:

| File | Changes |
|---|---|
| **README.md** | New opening line "KlassApp: The school in every parent's pocket." + subtitle. Two products framing. Mermaid "Why It Works" diagram. "The National Opportunity" → "The East African Opportunity." EMIS/LIN moat paragraph. East Africa expansion paragraph. |
| **for-schools.md** | Verbatim line added to Overview. Bursar section reordered (outcome lead-in before table). Pricing tiers section added. Step 2 bulk import time estimate. |
| **for-parents.md** | Opening replaced with emotional/trust framing + LIN context before self-registration. Important to Know table moved to bottom. NIN row added. Multiple Schools section added. |
| **ecosystem.md** | "Uganda" → "East Africa" in market-scale claims. Competition chart title updated. "Beyond School ERPs" section condensed to single introductory line. Phase 1 renamed "Core." East Africa expansion table + gantt timeline added. |
| **roadmap.md** | Timeline diagram added at top. Pricing section replaced with concrete tiers table. Blockchain → "Transparent PTA Elections." IPFS → "Permanent Digital Records." Geography fix. Phases table updated. |
| **faq.md** | Complete rewrite: For Parents section first, For Schools second, Technical third. Includes 24-hour window note (discreet). |
| **AGENTS.md** | Updated with new positioning, geography rule, outcome reframing, verbatim lines, competition quote.

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
- **Comprehensive rewrite (May 2026)**: All 6 community docs files rewritten in a single pass with new product positioning ("Two products, one platform"), East Africa geography expansion, outcome-based roadmap reframing, concrete pricing tiers, Mermaid timeline/gantt diagrams, emotional parent framing, LIN context, and new FAQ structure (parents first). Background agents failed to apply edits, all work done directly.
- **Post-rewrite fixes**: Fixed phases table separator mismatch (3-column separator for 2-column header). Replaced unsupported mermaid gantt `YYYY-QQ` date format with `YYYY-MM` dates. Bundled School Pay + LIN as same student entity in integrations. Replaced 71 AI-style em dashes with natural punctuation across all 6 files (39 remaining in Mermaid labels, phase names, and code samples only).
- **Accuracy correction (School Pay)**: The "School Pay" integration was aspirational — no School Pay API, no in-chat mobile money payment processing exists in the codebase. Renamed to "LIN & Fee Management" and reframed to only describe what's real: fee balance notifications via WhatsApp, with payment handled externally by the school's existing channels.
- **SchoolPay deep research**: Discovered SchoolPay (Fincom Technologies) is a real payment aggregator embedded within MTN/USSD school fees flow. 20K+ schools, 5M+ parents, licensed by Bank of Uganda. API has webhook + sync endpoints. Designed full integration spec at `docs/dev/schoolpay-integration.md` (4 phases, ~7-11 days effort). Community docs correctly frame this as LIN & Fee Management in the integrations section.
- **AI Agent Layer spec (v1)**: Initial spec designed an LLM-powered natural language layer for parents querying grades/fees via WhatsApp. Used function-calling pattern (GPT-4o-mini). 4 phases: read-only Q&A → actions + RAG → proactive → multi-modal.
- **AI Agent Layer spec (v2 — rewrite)**: Rejected v1 after discussion. Pivoted from parent-facing AI chatbot to **staff-only premium intelligence layer**. Key shifts:
  - Parents keep existing deterministic menus (no AI needed)
  - Agent is **web primary, WhatsApp secondary** — web for heavy work, WhatsApp for quick queries/notifications
  - **Marksheet ingestion** (vision LLM reads scanned/excel marksheets, extracts names+scores, writes to database) is the killer feature
  - **Report enrichment** — AI doesn't generate reports (they auto-generate), but appends edge cases: discipline notes, achievements (sports medals), exceptional comments
  - **Performance analysis** — natural language queries on trends, anomalies, comparisons
  - **Voice pipeline** — WhatsApp voice messages transcribed via Whisper, processed same as text
  - **Premium gating** — all AI features are premium-tier only
  - 4-phase roadmap: Foundation (web+text) → Vision+Voice → Alerts+Proactive → Parent Voice Access
  - Cost model: ~$1-5/month per school in LLM costs, supporting $50-200/month premium pricing
  - See `docs/dev/ai-agent-layer.md` for full spec.

---

## Commands

```bash
# Serve docs locally
cd docs/community && npx docsify-cli serve . --port 4000

# Hard refresh (Cmd+Shift+R) to bypass cache
```
