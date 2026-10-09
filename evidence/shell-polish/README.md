# SHELL POLISH — Side-by-side evidence (concept | live)

**Branch:** `evidence/shell-polish` (stacked on PR B `shell-polish/laptop` @ `7bd584b3`)
**Purpose:** Rasta's TESTING GAP requirement — "put side-by-side screenshots (concept frame beside staging) for every item below on the branch `evidence/shell-polish`."

Each image is **LEFT = admin-mvp concept frame** (rendered headlessly from
`design/system/concepts/admin-mvp/app.html`, a URL-driven SPA) beside
**RIGHT = the live app frame** captured by `e2e/shell-polish-verify.cjs`, the
same harness that reported **zero failures** across chromium+webkit at
375/390/768/1280/1440 with axe clean.

## Deviation (why "live", not "staging")
The acceptance text says *concept frame beside staging*. Staging
(`test.klassapp.xyz`) was unreachable this session and there was no Laravel
Cloud token, so the RIGHT side is the **locally-served app** (`127.0.0.1:8000`)
at the identical viewports. The concept's `screens/` folder is empty (no static
frames), so the LEFT side is rendered from the live concept SPA, not a PNG file.

## Pairs (`pairs/`)

| File | Item | Viewport / engine | What it shows |
|------|------|-------------------|---------------|
| `item1.png`  | 1 phone drawer | 375 / webkit | light 288px drawer, 48px rows |
| `item2.png`  | 2 phone account pop | 375 / webkit | popover opens downward, in-viewport |
| `item2b.png` | 2 laptop account pop | 1280 / chromium | popover not clipped, opens up |
| `item3.png`  | 3 phone top bar | 375 / webkit | 56px bar, single year picker |
| `item4.png`  | 4 laptop sidebar | 1280 / chromium | ~44px rows, icon-left |
| `item5.png`  | 5 header greeting | 1280 / chromium | greeting + "{school} · {term}, {year}" one line |
| `item6.png`  | 6 compact KPI + actions | 1280 / chromium | KPI tiles ~144px, 5-col quick actions |
| `item7.png`  | 7 fees chart | 1280 / chromium | compact 14.1M-style labels, muted future months |
| `item8.png`  | 8 attendance 8 weeks | 1280 / chromium | "Last 8 weeks" truthful label |
| `item9.png`  | 9 latest exam w/ marks | 1280 / chromium | perf + reports use latest exam WITH marks |
| `item10.png` | 10 activity icons | 1280 / chromium | per-event-type icon (not generic clock) |
| `item11.png` | 11 chart card sizing | 1280 / chromium | cards sized to content (`align-items:start`) |
| `item12.png` | 12 Toshi triangle <1280 | 375 / webkit | triangle hidden in preview |
| `item12b.png`| 12 Toshi triangle ≥1280 | 1280 / chromium | triangle visible (desktop) |

Regenerate: `node e2e/compose-evidence.cjs` (needs the harness shots from a
prior `node e2e/shell-polish-verify.cjs` run). `pairs/manifest.json` lists the
machine-readable mapping.

## Verification backing these frames
- Harness: **zero failures**, all engines/viewports, axe contrast clean.
- PHPUnit: 61 passed (473 assertions) — DashboardV2* + Navigation.
- Full `scripts/test-guard.sh`: run separately on PR B (see PR #1044 checks).
