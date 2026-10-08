# Handoff for Claude Code — Toshi label contrast + Toshi tower v2 (2026-09-27)

Source: `KlassApp-Foundation/KlassApp@main`. Files read here: `resources/views/landing-v2.blade.php` and `resources/css/landing-preview.css`, as uploaded copies. `resources/js/landing-preview.js` was **not** available. Written from Claude Design, which **can't write to the repo, run the site, set the operating-system reduced-motion setting, measure frame rate in a visible tab, or open PRs**. Everything below is for Claude Code to apply, verify and ship.

> **⚠ SUPERSEDED IN PARTS, 2026-09-27 (late).** The design changed after this spec was written. **`concepts/toshi-tower/v2.html` is the source of truth**, and where it disagrees with the text below, it wins:
> - **Placement:** in **"How it works"**, *replacing `.how-hub-node`* as the section centrepiece. **There's no container:** no `.toshi-visual` card. Its mist and dot grid become the section backdrop, per `landing-debox-catalog.md` B10 and B12. **Everything in B1 about replacing `#toshi` content is void:** the orbit stays in `#toshi`.
> - **Nodes:** the 6 connectors and 3 roles are **scattered** around the tower at fixed stage positions (see `data-n` in the concept). **There are no static connector lines.**
> - **Signals:** the cube **bounces** on a single 3s keyframe (2px squash on landing, 24px rise). On each `animationiteration` (the landing): a shockwave ring on the core top, then 4 packets arc out **from that flash point (560,390)** to random nodes, and each node gets a 0.7s `.hit` glow. At about 1.4s, 2 random nodes send packets back **into the same flash point**. The core is the hub every signal leaves from and returns to. Packets move with transforms only (Web Animations API).
> - **Screens:** the cube's two side faces carry inset white screens (`matrix(1 ±.5 0 1 …)`). On each landing both change together, cycling 3 model pairs (Anthropic/OpenAI, Gemini/Grok, Kimi/Z.ai). **This replaces the single cycling tiles in B5.**
> - **Toshi = the K logo only.** There's no tile and no "Toshi" word. It's a coin spin (`rotateY`, 10s), and **the back face is a second K turned 180°, so it's never mirrored.** It bounces with the cube. **This replaces the K-tile rotation in B5.**
> - **Unchanged from below:** item A (label contrast), the token rules (B2), the `<x-brand.*>` swap (B3), the ≤760px fallback idea (B4, which now stacks nodes as a 3-column grid under the tower), and accessibility (B6).
> - **No labels on the tower:** "KlassApp · Core" under the base cube and the "Toshi" pill are both removed.
> - **Reduced motion:** no bounce, signals, spin or swap. The K faces forward, the screens show Anthropic and OpenAI, and the "Runs on" row lists all six.

## Suggested PRs
1. `fix(landing): Toshi label AA contrast` — item A. Small; ship it first and on its own.
2. `feat(landing): Toshi tower v2 replaces #toshi hub` — item B. Local first, then staging.

Each PR needs:
- Before/after screenshots at 1280, 1440 and 1920 wide, and at 375, 414 and 768.
- No regressions to: the mobile menu (`#navbarMobilePanel`), the hero role rotation (`#heroRoleStage`) and the earlier mobile-audit fixes.
- A `knowledge.md` stamp.

---

## A. `.toshi-visual-label` contrast
| File | Change |
|---|---|
| `resources/css/landing-preview.css` → `.toshi-visual-label` | `color: var(--brand-green)` → `color: #15803D`. Leave every other declaration (the tint background, border and shadow) unchanged. |

- **Why:** `#22C55E` text on its own `rgba(34,197,94,.08)` pill over white is about 2.1:1, which fails 4.5:1. `#15803D` on the same pill is about 4.6:1.
- **Don't add a token for it.** The landing CSS has no darker green token, and `#15803D` already appears as a literal in `.panel-badge.live`.
- **Verify:** calculate the ratio from the computed `color`, and the pill background blended over the section background, in the running page, not from source.

## B. Toshi tower v2
**Source of truth: `concepts/toshi-tower/v2.html`** in this project. That file's markup, CSS and cycling script are complete for widths of 760px and up. **Nothing from v1 carries over.** The v1 notes are corrected history only.

### B1. What's replaced
- Replace everything inside `<div class="toshi-visual">` in `#toshi` with v2's stage. That covers `.toshi-visual-lines`, `.toshi-visual-channels`, `.toshi-visual-hub` and `.toshi-visual-roles`.
- **Keep:** `.toshi-header` (the badge, the h2 with `.toshi-name`, and the paragraph), plus everything after `.toshi-visual`, including the `.toshi-card` grid.
- **`#protocol` is untouched.** v2 has no protocol layer, and it has been removed from the markup, not hidden.
- **Delete from `landing-preview.js`** the DOM-measuring layout pass that draws the old connector geometry. It isn't needed: v2's paths are fixed coordinates on a 1120×600 stage. Keep the `.reveal` observer.

### B2. Tokens
- **Allowed custom properties:** `--brand-*`, `--text-*`, `--border`, `--violet-accent`, `--font-*` — all already declared in `landing-preview.css :root`.
- **Allowed literals:** only those already used as literals in the landing CSS. These are `#2DD46A` / `#16A34A` (from `.hub-mark`), the channel-well gradients, and the `rgba()` glows.
- **Acceptance:** `grep -nE 'var\(--(d|toshi)-' resources/css/landing-preview.css resources/views/landing-v2.blade.php` returns nothing. **Verified in the concept: 0 hits** across every stylesheet rule and inline style.

### B3. ⚠ Not built — use the landing's real brand components
In the concept, WhatsApp, Slack and Drive are `<img src="../../assets/brand/*.svg">`. **Swap them for the landing's Blade components**, keeping the wrapper class exactly as it is:
```blade
<span class="ico brand-well"><x-brand.whatsapp /></span>
<span class="ico brand-well"><x-brand.slack /></span>
<span class="ico brand-well"><x-brand.google-drive /></span>
```
The live CSS sizes these as `.channel-ico.brand-well .brand-mark { 16×16 }`. Carry that rule over as `.ico.brand-well .brand-mark`.

**Other icons:**
- **Email, SMS and Calendar:** inline stroke SVGs, copied verbatim from the landing (lines 332/334 and connector chip line 194). They're already correct in the concept.
- **K mark and the six model marks:** the landing has no Blade components for these. Copy the files from this design system into `public/`:
  - `assets/brand/klassapp-icon.svg`
  - `assets/brand/models/{anthropic,openai,google-gemini,xai-grok,moonshot-kimi,zhipu-zai}-mark.svg`
  - **Don't add DeepSeek.** It was removed on purpose.

### B4. ⚠ Not built — below 760px
The concept only describes this. Build it following the landing's real `t-wrapped` pattern: `.toshi-visual.t-wrapped .toshi-visual-lines { display:none }`, landing-preview.css L1408. That class is toggled from `landing-preview.js`, which I haven't read. **Check how it's toggled there before reusing the name.**

The spec:
- **Trigger:** use CSS `@media (max-width: 759.98px)`, not JS. The v2 stage is fixed-geometry, so there's nothing to measure.
- **Hidden at this width:** the SVG connector and flow paths, the ground shadow, and the fixed-stage scaling. Remove the JS `transform: scale()`, or skip it below 760.
- **Stacked order, top to bottom:**
  1. **Channels:** a 2-column grid (3 rows), gap 12px, cards full width of their column. Keep the 46px height and every `.toshi-node` style. That gives a 46px tap height and a full-column tap width.
  2. **The tower:** the two blocks, K tile and model tiles as one centred unit, at a fixed 300×260 crop of the stage. A vertical `12px` green beam, using v2's `#beam` gradient, runs above and below it to join the three groups visually.
  3. **Roles:** one row of three pills, wrapping, centred, gap 10px, each at least 44px tall. **Note:** the live `.toshi-visual-role` is 40px. Raise the height at this breakpoint to meet the 44px touch target.
  4. **"Runs on" row:** unchanged; it already wraps.
- **No horizontal scroll at 320px.** Check with `document.documentElement.scrollWidth <= innerWidth`.
- **768 is desktop layout, scaled.** 768 is above the breakpoint, so check it scales cleanly at 728/1120 = 0.65.

### B5. Motion — verified in Claude Design, re-verify in the app

**K tile rotation — a bug was found and fixed in the concept.** The plate and the K `<img>` are **siblings**. The first draft gave the K a reverse `orbitSpin`, which on a sibling makes the mark rotate *on its own*. Measured with `getComputedStyle` matrices:

| Cycle point | Plate | K, first draft | K, fixed |
|---|---|---|---|
| 0 s | 0° | 0° | **0°** |
| 12 s | 90° | **−90° (sideways)** | **0°** |
| 24 s | 180° | **180° (upside down)** | **0°** |
| 36 s | −90° | 90° | **0°** |
| 43.2 s | −36° | 36° | **0°** |

Fixed: **the K has no animation.** Its bounding box stayed 32.8×32.8 at every sample, so it never rotates. `det = 1` everywhere, so it's never mirrored.
- **Acceptance:** sample the same five points in the app. The K's computed transform is `none` or 0°, and the plate's is 0/90/180/−90/−36°.
- **If anyone nests the K inside the plate later,** a reverse spin becomes correct. As siblings, it must stay unanimated.

**Model cycling.** Sampled at about 1.5s intervals: `[3] → [3] → [4] → [4] → [5]`. So there is exactly one tile on at a time. Each changes at about 3s, and they alternate faces: index 3 is on the right (`left:593px`), 4 on the left (`493px`) and 5 on the right. The "Runs on" row is static markup and always lists all six.

**Layout shift:** CLS measured at **0** during cycling and rotation. Every animated property is `transform` or `opacity`.

**Frame rate: not measured.** My preview frame is off-screen, so `requestAnimationFrame` returned 0 frames. **Measure it in a visible tab:**
- Use Chrome's Performance panel over 10s at 1440 wide.
- Target: no long frames over 16.7ms that are caused by these animations.
- The heaviest paint is the blurred ground ellipse (`feGaussianBlur` std-dev 8). If it causes repaints, rasterise it or drop the filter.

**Reduced motion.** Checked by reading the stylesheet rules, but **not** by switching on the real setting, which I can't do here:
- **Every `animation` in the file sits inside `@media (prefers-reduced-motion: no-preference)`**: `.fl.in`, `.fl.out`, `.bloom`, `.breath` and `.ktile .plate`. No animation is declared outside it.
- **A gap was found and fixed:** the hover lift on nodes (`transform` on `.ch:hover` / `.role:hover`, plus transitions) still ran under reduced motion. It's now `transition:none; transform:none` under `reduce`.
- **The JS listens to `matchMedia('(prefers-reduced-motion: reduce)')`.** When it's set, the cycling timer isn't started, tiles 0 and 1 show (one on each face), and the JS reacts to the setting changing live.
- **Acceptance, with the real setting on (macOS: Reduce motion; or the Chrome DevTools rendering emulation):**
  - `document.getAnimations().length === 0`.
  - Exactly two `.mt.on`, unchanged after 10s.
  - Hovering a node doesn't move it.
  - The flow streaks are `display:none`.

### B6. Accessibility
- **Keep:** `role="img"` with `<title>` and `<desc>`. The flow paths are `aria-hidden`.
- The model tiles are decorative duplicates of the "Runs on" row, so give them `alt=""` in the build. The concept has names, which a screen reader would announce every 3s.

## Regression checks (both PRs)
- The mobile menu opens and closes once per tap.
- The hero role rotation still flips, and the K avatar is still 38×38 by `getBoundingClientRect`.
- `#connectors` and `#protocol` are visually unchanged.
- No new console errors.
- Lighthouse accessibility score on `/` is unchanged or better.
