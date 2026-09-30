# Handoff for Claude Code — #toshi orbit fixes (2026-09-27)

File: `resources/views/partials/landing-toshi-tower.blade.php`, plus the `.tt-*` block in `resources/css/landing-preview.css` (L1095–1144). Reference build: `concepts/toshi-orbit/index.html` in this design system. It's the live partial with the fixes below applied. The concept loads tool marks through `<image>`; **the live file should keep its inline tool-mark paths.**

Suggested PR: `fix(landing): toshi orbit a11y, tokens, real K, pulse rhythm`.

| # | Change | Verified in the concept |
|---|---|---|
| 1 | `<svg>`: remove `role="img"`, `aria-labelledby`, `<title>` and `<desc>`. Add `aria-hidden="true" focusable="false"`. Remove the `aria-label` from `#toshiTower`. | `aria-hidden="true"` ✓ |
| 2 | Core tile `stroke="#e8e6dc"` → `style="stroke:var(--border)"`. | No `e8e6dc` anywhere ✓ |
| 3 | Replace the three flat K paths with `<image href="{{ asset('images/klassapp-icon.svg') }}" x="-16" y="-16" width="32" height="32"/>`. | ✓ |
| 4 | `.tt-beat`: `animation: tt-beat 18s …`. Keyframes `0% {0, .35}`, `4% {.55, 1}`, `8% {0, 1.4}`, `100% {0, 1.4}`. The `--d` values follow the existing interleave, with each old step of 1.3s becoming 1.5s: WhatsApp 0, Anthropic 1.5, Drive 3, OpenAI 4.5, Slack 6, Grok 7.5, Email 9, Gemini 10.5, SMS 12, Kimi 13.5, Calendar 15, Z.ai 16.5. | 12 beats, 18,000ms. **At most 1 lit at a time**, sampled every 10ms over a full cycle. The only unlit time is 12 even 60ms gaps (840ms in all) ✓ |
| 4b | Mobile rhythm: add `--dm` to the six visible nodes (WhatsApp 0, Drive 3, OpenAI 6, Slack 9, Gemini 12, Z.ai 15) and `@media (max-width:700px){ .tt-beat{animation-delay:var(--dm,0s)} }`. | Even 3s spacing ✓ |
| 5 | All six model `<image>`: `y="-7.5"` → `y="-11"`. | ✓ |
| 6 | `.tt-x`: remove it from **Drive**, add it to **SMS**. | ✓ |
| 7 | Ring depth: replace the two static ring ellipses with **back-half arcs** (`M156 330 A344 132 0 0 1 844 330` and `M288 330 A212 84 0 0 1 712 330`) drawn before `.tt-core`, and **front-half arcs** (the same paths with sweep `0 0 0`) drawn after `.tt-core` and before `.tt-nodes`. The spinning packet ellipses stay behind the core. | Same gradients and widths as before. There are only 2 extra elements, and no new animation. |
| 8 | Hard-coded colours → tokens, through classes (`.tt-stop-g{stop-color:var(--brand-green)}`, `.tt-stop-b`, `.tt-stop-d`, `.tt-gs`, `.tt-bs`, `.tt-tile rect{fill:var(--brand-white);stroke:var(--border)}`). The tints `#86EFAC`, `#4ADE80`, `#34D399`, `#93C5FD` and `#ECFDF5` become `rgba(34,197,94,…)` / `rgba(30,111,217,…)`. The metal-core greys stay as literals (a material). | ✓ |

| 9 | **Globe core (new, approved for review).** Inside the emissive disc: a `clipPath` circle r63; two static latitude ellipses (y ±32, rx 55, ry 7, white at .32) and an equator (rx 64, ry 10, white at .5); **4 meridians**, each a `circle r63` with `vector-effect="non-scaling-stroke"`, turned with `transform: scaleX()`. CSS: `.tt-mer{transform-box:fill-box;transform-origin:50% 50%;transform:scaleX(var(--ms))}`. Under no-preference: `animation: tt-mer 32s ease-in-out infinite; animation-delay: calc(var(--m) * -4s)`, keyframes `scaleX(1) → (-1) → (1)`. Reduced-motion static values `--ms`: 1, .71, 0, −.71. | Meridians evenly spaced 45° at every sample: scaleX 1/.74/0/−.74 at t=0, stepping one position every 4s. ease-in-out gives .74 against a true cos 45° of .71, close enough to read as a sphere. It's transform only, with **0 animated elements carrying a filter**. It sits inside `.tt-core`, so it scales with the core on mobile. The glow is still outside `.tt-core`, and the pulse is unchanged (18s, 1.5s steps). |

| 10 | **One centre shape (user decision).** **This also updates item 9's colours:** the globe lines are now `stroke:var(--brand-green)` (latitudes .28, equator .42, meridians .34), and `#tt-emis` becomes `0 #FFF → .62 #FFF@.92 → 1 green@.35`, so the multi-colour K reads on a light centre. Delete the metal sphere, meaning `circle r86 fill=url(#tt-metal)`, its white rim, and the two white band ellipses (`rx86 ry30` and `rx66 ry22 @ cy300`), plus the `#tt-metal` gradient. Delete the specular highlight `ellipse rx30 ry15 rotate(-28)` and the white K tile. The globe (r64, green rim) is the whole core. The K rides the globe as two surface decals, 180° apart, 32s per turn, hidden on the back face so it is never mirrored. The pulse ring (r80) stays as a halo. | Only one shape remains at the centre ✓ |

## The two open checks
**Mobile glow clip: real bug, fixed.**
- **Live:** the glow ellipse sits inside `.tt-core`, which is scaled 1.3× at ≤700px. That takes its radius from 168 to 218.4, putting its top at **y 111.6 against a viewBox top of 150**. The glow is cut off by 38px in a straight line. At that line the gradient is still at about 6% green, so it's faint but visible.
- **Fix:** move the glow ellipse *out of* `.tt-core` into its own `<g class="tt-glow">`, drawn before the back rings, so it isn't scaled. Its top stays at **y 162, inside the viewBox**.
- The core, rings and tiles still scale as before.

**Frame rate: the paint risk is removed; the fps number is unverified.**
- **Live:** `.tt-core-pulse` animates opacity on a circle carrying `filter="url(#tt-beatblur)"` (`feGaussianBlur` std-dev 4). That re-runs the blur on every frame, the most expensive thing in the graphic on low-end phones.
- **Fix:** replace the blurred stroke with a pre-blurred radial-gradient ring: `<circle r="80" fill="url(#tt-pulse)">`, with stops `.62 → 0`, `.8 → .7`, `1 → 0`. The opacity animation is the same, but now it's compositor-only. **The concept has 0 animated elements carrying a filter.** The static tile `feDropShadow`s aren't re-painted by any animation.
- **Unverified:** actual fps. This preview's `requestAnimationFrame` timing returned implausible numbers (a median frame time of 0ms), so I won't cite an fps figure.
  - **Claude Code should measure on the live page:** use Chrome's Performance panel with 4× CPU throttling at 390px and 1440px, over 10s.
  - **Acceptance:** no frame over 16.7ms attributed to `.toshi-tower`, and no "Paint" entries repeating every frame for the SVG.

## Regression checks
- Reduced motion: `document.getAnimations()` within `#toshiTower` returns 0, and every tile is visible.
- ≤700px: 6 tiles visible, including Drive and excluding SMS.
- No console errors.
- The K and model marks are centred in their tiles.
