# Architecture (start here)

KlassApp is a **multi-tenant** Laravel 12 school platform: every school’s data is scoped by `school_id`. Staff work in role portals (admin, teacher, …); parents use **WhatsApp** (Meta Cloud API). **Toshi** is the in-product AI agent (Livewire + Laravel AI) for guided setup and day-to-day help.

This page is a **short bridge**, not a second wiki. Depth lives on DeepWiki (auto-generated from the current codebase).

## Read next on DeepWiki

| Topic | DeepWiki |
|---|---|
| Platform shape | [Core Platform Architecture](https://deepwiki.com/KlassApp-Foundation/KlassApp/2-core-platform-architecture) |
| Tenancy & data model | [Data Model & Multi-Tenancy](https://deepwiki.com/KlassApp-Foundation/KlassApp/2.2-data-model-and-multi-tenancy) |
| Toshi | [Toshi AI Assistant](https://deepwiki.com/KlassApp-Foundation/KlassApp/3-toshi-ai-assistant) |
| Wizard / engine | [School Onboarding](https://deepwiki.com/KlassApp-Foundation/KlassApp/4-school-onboarding) |
| Tests | [Testing Strategy](https://deepwiki.com/KlassApp-Foundation/KlassApp/9.3-testing-strategy) |
| Hosting / ops | [Deployment & Operations](https://deepwiki.com/KlassApp-Foundation/KlassApp/9.4-deployment-and-operations) |

Overview hub: [DeepWiki — KlassApp](https://deepwiki.com/KlassApp-Foundation/KlassApp).

## Also in this repo

- Contribute / run tests: [`CONTRIBUTING.md`](../CONTRIBUTING.md)
- Agent rules (AI maintainers): [`AGENTS.md`](../AGENTS.md)
- Fork history: [`project-provenance.md`](project-provenance.md)
- Public direction: [`roadmap.md`](roadmap.md)

## Do not use for current architecture

[`docs/dev/`](dev/) is **archived historical** material (Evolution API + DigitalOcean era). Production today is **Laravel Cloud** + **Meta WhatsApp Cloud API**. Ignore `docs/dev/` when learning how the system works now.
