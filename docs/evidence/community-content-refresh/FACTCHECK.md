# Community Docsify content refresh — evidence

Date: 2026-09-17  
Branch: `docs/community-content-refresh`

## Theme (Playwright, 1280×900)

Served from `docs/` via `python3 -m http.server 8765`. Shared CSS: `docs/shared/docsify-klassapp.css`.

| Check | Result |
|---|---|
| `--d-canvas` | `#fafaf5` on all 8 pages |
| `--d-green` / `--d-blue` | present (`#22c55e` / `#1e6fd9`) |
| Theme stylesheet linked | yes |
| Uganda-first body copy | none (`\bUganda\b` false on all pages) |
| `$30` Growth price | none |
| `$35` where plans listed | overview, for-schools, school-onboarding, book-onboarding, faq |

Screenshots: `01-overview.png` … `08-roadmap-deprecated.png`.

## Fact-check vs shipped product

| Claim area | Source of truth | Docs status |
|---|---|---|
| Global positioning (hardest constraints first) | README | Aligned |
| Four-surface design shipped | `docs/roadmap.md` | Stated on for-schools / ecosystem |
| WhatsApp = Meta Cloud API live | README / Verified Stack | Stated; Evolution not mentioned |
| Drive / Slack UI model, not live API clients | README | Stated |
| Toshi guided live; free-form gated | roadmap | Stated |
| Plans Freemium / Growth **$35** / Premium custom | `PlansTableSeeder` (`amount` 0 / 35 / custom) | Corrected (was $30 in school-onboarding) |
| Roadmap content | `docs/roadmap.md` | Cross-linked; community `roadmap.md` remains deprecated stub |
| Book onboarding form → fake API | was posting to `/api/onboarding/book` | Replaced with mailto `community@klassapp.xyz` |

## Pages refreshed

`README.md`, `for-schools.md`, `for-parents.md`, `faq.md`, `school-onboarding.md`, `ecosystem.md`, `book-onboarding.md`. Sidebar already pointed at hub roadmap.
