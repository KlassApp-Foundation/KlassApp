# Roadmap

Honest public direction for KlassApp. Sourced from real shipped work and the **Future Initiatives** logged in maintainer notes — not from speculative product fiction.

Last curated: September 2026.

## Shipped (recent)

| Area | What landed |
|---|---|
| **Four-surface design** | Coordinated production cutover of landing/auth/errors, admin dashboards, onboarding wizard, and Toshi panel (design-system kit + clay chrome). |
| **Security hardening** | Cross-school access-control fixes on teacher student surfaces; secrets removed from the tree (env-only LLM keys); staging MySQL isolated from production. |
| **Community health** | Code of Conduct, Contributing, Security, issue/PR templates; Docsify theme aligned to the design system; docs map + archive banner for stale `docs/dev/`. |
| **Class teacher invite (Phase 1)** | Admin can invite a teacher as class teacher by email (optional path; admin-driven setup remains). |

Live product: [klassapp.xyz](https://klassapp.xyz). Staging demo access: [community@klassapp.xyz](mailto:community@klassapp.xyz).

## In progress

| Area | Notes |
|---|---|
| **Docs structure** | This architecture bridge + public roadmap; Docsify brand theme shipped; community Docsify *content* refresh still pending. |
| **Kabale Junior School** | Real production school (ongoing ops and onboarding follow-through — not a speculative pilot). |

## Future (logged initiatives)

These are real backlog items from maintainer Future Initiatives — scoped or deferred, not invented here:

| Initiative | Status |
|---|---|
| **Public status page (Instatus)** | Deferred until after the UI design phase. |
| **CT invite Phase 2** | Wizard nudge to invite a class teacher after a class is created (Phase 1 already shipped). |
| **CT invite magic link** | Replace temp-password email with a tokenized accept link. |
| **GeGoK12 → KlassApp UI migration** | Phased migration off inherited UI toward the formal design system — not yet scoped. |
| **Security & trust** | Break-glass superadmin access; staff business IDs; self-hosted hash-chained tamper-evident student records; optional governance voting later. |
| **Security maturity ladder** | Growing test coverage → professional pentest before wide launch → bug bounty only after that. |
| **Toshi as an MCP server** | Let external AI clients call into KlassApp via Laravel MCP — buildable, not started. |
| **Toshi free-form chat** | Guided flows work today; open-ended LLM chat stays gated until a funded key is activated. |
| **Go-to-market plan** | Formal GTM as its own discovery-then-build track — not started. |
| **After UI phase** | Broader name-lookup sweeps; WhatsApp OAuth for parent linking; more wizard/Toshi parity. |

## Out of scope here

Do **not** treat the old community Docsify roadmap file as current. It mixed unconfirmed product ideas and incorrect pricing. That file is deprecated — see the note on [`community/roadmap.md`](community/roadmap.md).
