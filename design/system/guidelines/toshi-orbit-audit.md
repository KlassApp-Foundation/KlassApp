# #toshi orbit-ring visual — audit and proposals (2026-09-27)

Source, read from `KlassApp-Foundation/KlassApp@main`:
- `resources/views/partials/landing-toshi-tower.blade.php`, the SVG
- `resources/css/landing-preview.css` L1095–1144 and L3985–4005, the CSS

**This is audit only. No changes have been made.**

## 0. The finding that affects everything else
**The orbit visual *is* the partial named `landing-toshi-tower`.** It's included at the exact spot in `#toshi` that the tower v2 handoff (`handoff-2026-09-27-toshi-tower-v2.md`) was written to replace. There is no separate tower in the repo. "Keep the orbit and put the tower nearby" therefore means **two Toshi visuals that depict the same things**: a Toshi core, the same six tools, and the same model logos, stacked in one section. They would also use two different 3D styles: glass and metal versus flat isometric blocks. See §6.

## 1. Contrast
- **There is no visible text inside the visual.** The only text is the SVG's `<title>`/`<desc>`, so there's nothing to fail 4.5:1.
- **Non-text contrast** (WCAG 1.4.11, 3:1 for meaningful graphics): the white tiles have a `#E2E8F0` border on a white card, about 1.2:1, so they rely entirely on the drop shadow to separate. The graphic is described as decorative, so this is exempt. It's worth fixing anyway as polish (§5).
- **Accessibility contradiction:** the SVG is `role="img"` with a title (announced as an image), but its `<desc>` ends with "Decorative." The wrapper `div` has `aria-label` but no role, so screen readers ignore that label.
  - **DECIDE:** either make it `aria-hidden="true"` and truly decorative, because the header copy already says it all, or keep `role="img"`, delete the word "Decorative", and remove the useless `aria-label` from the wrapper. My pick is `aria-hidden`.

## 2. Tokens — every colour is a hard-coded literal
| Literal | Where | Should be |
|---|---|---|
| `#22C55E` | glow, emissive ring, `.tt-beat` stroke (CSS L1111), ring gradients | `var(--brand-green)` |
| `#1E6FD9` | outer ring gradient end, Email and Calendar icon strokes | `var(--brand-blue)` |
| `#0F172A` | floor, ambient occlusion, drop shadow | `var(--brand-dark)` |
| `#E2E8F0` | all 12 tile borders | `var(--border)` |
| **`#e8e6dc`** | **K tile border** | **This is Toshi's app-side *clay* border.** It's the only clay value on the marketing page, and it contradicts the "Toshi stays green on marketing" decision. → `var(--border)` |
| `#86EFAC`, `#4ADE80`, `#34D399`, `#ECFDF5`, `#93C5FD` | light packets, orbit dots, emissive centre | Tints with no landing token. `#34D399` is emerald, a different green family from the brand. → `rgba(34,197,94,…)` / `rgba(30,111,217,…)` from the brand colours, as the rest of the page does |
| `#94A3B8`, `#475569`, `#E2E8F0` | metallic core gradient | Keep as literals: this is a material, not a UI colour |

Token swaps work because the SVG is inline. Move the fills and strokes into classes (`.tt-g{fill:var(--brand-green)}`); `var()` doesn't resolve inside presentation attributes in every browser.

**The K mark is redrawn, not the real asset.** The core tile has three flat-filled paths (`#29BF5D`, `#0273D4`) at `scale(0.011)`, not `klassapp-icon.svg` with its five gradients, which is what the hero uses. **Replace it with `<image href="{{ asset('images/klassapp-icon.svg') }}">`**, the same way the model logos are loaded. No redrawn brand mark should ship.

## 3. Motion
- **Reduced motion: correct.** Every animation (`tt-spin`, `tt-orbit`, `tt-core-pulse`, `tt-beat`) is inside `@media (prefers-reduced-motion: no-preference)`. The static state shows the rings, dots and tiles, and `.tt-beat` sits at `opacity:0`, so nothing flashes. `.reveal` belongs to the shared page reveal system and wasn't re-checked here.
- **The pulse rhythm is off.** The code comment and the `<desc>` promise "one at a time", but:
  - Each beat is visible for 13% of 17s, about 2.2s. The 12 delays step by 1.3s, so **two tiles are lit at once** for most of the cycle.
  - The 12 × 1.3s steps total 15.6s against a 17s cycle, so there's a **1.4s dead gap** each loop.
  - **On mobile it gets worse.** Below 700px, `.tt-x` hides 6 nodes. The remaining delays are 0, 3.9, 5.2, 9.1, 10.4 and 14.3s: gaps of 3.9/1.3/3.9/1.3/3.9/2.7s. That's a limping rhythm.
  - **Fix (SAFE):** set the cycle to `12 × 1.5 = 18s` and the delays to `n × 1.5s`, with the visible window at 8% (1.44s < 1.5s), so exactly one tile is lit. On mobile, override the six visible nodes to `n × 3s` on an 18s cycle.
- **Model logos sit 3.5px low.** Every model `<image>` is at `x="-11" y="-7.5"` in a 22×22 box, but centring needs `y="-11"`. **SAFE.**
- **Performance to check:** `.tt-core-pulse` animates opacity on an element with `feGaussianBlur`, which repaints the blur every frame. The 12 tiles each carry a `feDropShadow`; that's static, so it's fine. **Measure in a visible tab.** If the pulse shows up in paint profiling, animate a pre-blurred radial gradient instead.

## 4. Mobile composition (≤700px)
- `.tt-x` hides **Drive**, Email and Calendar, plus Anthropic, Grok and Kimi. **DECIDE:** Drive is one of the three headline tools (hero, "How it works", pillars). I'd swap it for SMS in the hidden set.
- `.tt-core` is scaled 1.3×. Its glow radius goes from 168 to about 218, which reaches past the top of the `viewBox` (y 150). **Needs a visual check** for a hard clip on the glow's top edge.

## 5. Polish — same concept, more considered
1. **Front and back ring depth.** Draw each ring as two arcs: the back half under the core and the front half over it. Right now the core covers both halves, which flattens the glass-ring illusion. It's the single change that most improves the "orbit". **SAFE**, geometry only.
2. **Tile edge.** Change the tile border to `var(--border)` plus a 1px inner highlight (`inset 0 1px 0 #fff`), matching `.toshi-node`, so the tiles belong to the page's node family.
3. **One green family.** Drop the emerald `#34D399` (§2).
4. Use the real K asset (§2), and move the rhythm to 18s (§3).

## 6. Orbit and tower side by side — my recommendation
They **compete**. Both answer the same question, "what does Toshi connect to", using the same logos, in two different 3D styles, in one section. A visitor sees the same message twice, and neither piece gets to be the signature.

Options, in order of preference:
1. **Pick one per section.** Keep the orbit in `#toshi` and take the tower's *new* idea (channels → core → **roles**) into **"How it works"**. That section is already about roles and protocol paths, and its hub node is a plain pill (`.how-hub-node`). The tower would be at home there, a whole section away from the orbit.
2. **If they must be neighbours, give each a different job and remove the overlap.**
   - The orbit becomes **models only**, answering "what Toshi runs on": the outer ring stays, the inner-ring tools go.
   - The tower carries **tools and roles** and drops its model cycling.
   - This does alter the orbit's current concept, so it needs your approval.
3. **Replace the orbit, as the v2 handoff originally specified.** You've ruled this out.

Whichever you choose, the tower v2 handoff needs a placement edit before Claude Code starts: it currently says "replace everything inside `.toshi-visual`", which no longer matches the repo.
