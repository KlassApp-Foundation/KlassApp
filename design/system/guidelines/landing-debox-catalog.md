# Landing page — boxed-element catalog (Phase 1 of the "sits freely on the surface" redesign)

Source: `KlassApp-Foundation/KlassApp@main`, `resources/views/landing-v2.blade.php` plus `resources/css/landing-preview.css`. The boundary values below were parsed from the CSS: every rule whose selector matches, filtered to `background`, `border`, `border-radius` and `box-shadow`. **This phase is a catalog only; nothing is designed yet.**

**The connective tissue already on the page:**
- Hero: the vintage paper wash, ledger rules and deckle edges, plus `.hero-depth`, a radial glow with a faint grid.
- `#toshi`: `.toshi-depth`, a green radial glow, plus the tower v2 mist and dot grid.
- Section fills alternate: `--brand-light` on `.connectors` and `.compare`; white on `.toshi` and elsewhere.

That's already enough ambient material to carry structure without boxes.

## A. Structurally necessary — the box is the concept (stays boxed, can soften)
| # | Element | Current boundary | Why the box stays | Softening possible |
|---|---|---|---|---|
| A1 | **Hero device frame** `.hero-device-frame` (+ `.hero-device-base`) | Dark gradient, 22px radius, deep drop shadow, inset hairline | It *is* the device. The desktop window / phone split is already specced. | Let the shadow pool onto the paper (a wider, lower-opacity ellipse beneath the device) instead of a hard drop shadow. The device then sits *on* the paper. |
| A2 | **Screenshots** `.shot` ×3 | White, 1px border, radius 16, `--shadow-md` | A captured frame must read as a frame, and the `x-landing.app-shot` component is specced around it. | Remove the 1px border and keep the chrome bar plus a softer shadow. The screenshot's own edge becomes the boundary. |
| A3 | **"How it works" previews** `.how-preview` ×3 (WhatsApp, attendance, digest mockups with `.ui-chrome`) | `--brand-light` fill, 1px border, radius 12 | They're mock app windows, so they must read as windows. | Same as A2. |
| A4 | **Mock UI inside the previews:** `.kpi-chip`, `.teach-table`, `.admin-panel`, `.wa-bubble` | Borders and fills | They imitate real app UI, and boxes are part of the imitation. | Leave them alone; they're inside A3. |
| A5 | **Orchestration panel** `.orchestration-panel` + `.panel-row` ×6 | White panel, border, radius; row dividers | It's a mock control-panel window with chrome and table header. | Same as A2, and keep the row dividers (they're table structure). |
| A6 | **FAQ items** `details.faq-item` ×4 | White, 1px border, radius 12, shadow | **Functional:** each is a disclosure control, and the box is its hit area and open/closed affordance. | **Ask:** a divider-list pattern (a full-width hairline between items, with a chevron) keeps the 44px+ tap target and the affordance without cards. This counts as a de-box. |
| A7 | **Navbar**, **announce bar** | A translucent paper band, bottom border | Sticky chrome needs separation from the content scrolling under it. | None needed. |

## B. Boxed by convention — candidates to float freely
| # | Element | Current boundary | Free-floating option (for Phase 2) |
|---|---|---|---|
| B1 | **Toshi capability cards** `.toshi-card` ×6 (Role-Aware … Safe by Design) | White, 1px border, radius 16 | Icon plus heading plus text on the section surface, in a 3×2 grid held by alignment and gap. The icon tile carries the accent. |
| B2 | **Protocol Cores pillars** `.pillar` ×5 | White, border, radius 16, `--shadow-sm`. **`.pillar-future`** is dashed with an amber wash. | Float the four live pillars. **Keep a boundary on the Provable pillar only**, as a dashed amber outline. The dash is *meaning* ("not shipped"), so it becomes the one boxed item and gets more emphasis, not less. **The honesty note is unaffected.** |
| B3 | **Compare cards** `.compare-card` ×4 (Before / KlassApp way) | White, border, radius 16, shadow; Before and After column fills | Drop the outer card. Keep a single vertical rule between Before and After (the comparison needs it) and a soft green wash behind "KlassApp way" only. **The honesty lead stays the section lead.** |
| B4 | **How-it-works columns** `.how-column` ×3 | White, border, radius 16, `--shadow-sm` | Remove the column card. Each column becomes preview (A3) → number → label → steps, held by the existing step dots and line. **This is where the tower lands** (see C). |
| B5 | **Human-in-the-loop note** `.toshi-hitl` | Violet gradient fill, violet border, radius 12, shadow | Float it as an inline callout: icon plus text, with a violet hairline on one side at most. **Watch out:** that's close to the "left-border accent card" trope. A soft violet glow behind the icon alone is better. |
| B6 | **Orbit container** `.toshi-tower` | White, border, radius 20, ring shadow | Remove the card. The orbit then sits directly on `.toshi-depth`'s green glow, the textbook case for this redesign. |
| B7 | **Connector float** (hero) `.connector-float` + `.connector-icon` ×6 | A paper-white card, border, `--shadow-md`; icon wells with borders | Float the icons as a row with a "Connected" micro-label. Keep the icon wells (brand-mark legibility) and lose the outer card. |
| B8 | **Connector chips** `.connector-chip.ka-node` ×6 | The `ka-node` gradient card, border, shadow | Mark plus label, with no card. |
| B9 | **Protocol cards** `.protocol-card.ka-node` ×3 and **mesh nodes** `.mesh-node` ×3 | `ka-node` gradient, border, `--shadow-md` | The same treatment as B1, and they should match each other. |
| B10 | **Hub pill** `.how-hub-node` | White pill, violet border, glow | **Superseded:** the tower replaces it (see C). |
| B11 | **Trust strip** `.trust-strip` | A white band, bottom border | Drop the border and let spacing separate it. |
| B12 | **Tower v2's own container** `.toshi-visual` | Glass card, border, 24px radius, shadow | Remove the card. Its mist and dot grid become the section background, the same move as B6. |

## C. Reconciliation with work already out for Claude Code
I can't see PRs #842–846 themselves. They're mapped below by the handoff each came from; **check which PR number is which**.

| Existing work | Does this redesign change it? |
|---|---|
| **Toshi label contrast** (`.toshi-visual-label` → `#15803D`) | No. That's a text colour, independent of the box. |
| **Muted text → AA** (`--text-muted` → `--text-secondary`, both honesty lines' colours) | No. Text colours survive de-boxing, **but** ratios were measured on white. Floated items then sit on `--brand-light` (#F8FAFC) or `--paper-base`. `#64748B` is still 4.55:1 or better on both, and `#B45309` is still 4.8:1 or better, so no rework is needed. |
| **Hero device frame** (desktop window ≥901, phone ≤900) | Adds only A1's shadow pooling on top. It doesn't conflict. |
| **Screenshot component** (`x-landing.app-shot`) | A2 changes its CSS (no border). The component API is unchanged, so this is a small follow-up. |
| **Orbit fixes** (a11y, tokens, real K, 18s pulse, mobile glow, globe) | B6 removes its container. The fixes live *inside* the SVG, so they're all unaffected. The mobile glow fix still matters, because the SVG `viewBox` still clips. **Item 7 in that handoff (tile edge → `.toshi-node`) is unaffected.** |
| **Tower v2 → "How it works"** | **Changes the most.** The handoff builds the tower inside the `.toshi-visual` glass card (B12) and the section keeps its card columns (B4). If both de-box, the tower should sit *on the section surface*, with its mist and dots as the backdrop, and replace the `.how-hub-node` pill (B10) as the section's centrepiece. **Hold that PR's container CSS until Phase 2 settles this.** Its internals (SVG, nodes, motion, reduced-motion, sizing guard) are unaffected. |
| **Reduced motion, pulse timing** | Unaffected. No box change touches animation. |

## Decisions (resolved 2026-09-27)
1. **FAQ (A6):** a divider list with chevrons and 56px summaries across the full width.
2. **Provable pillar (B2):** the one boxed exception, with a dashed amber outline.
3. **Tower:** replaces `.how-hub-node` as the centrepiece of "How it works", with no container.

Phase 2 design: `concepts/landing-debox/index.html`.
