# Landing page — launch polish audit (2026-09-27)

Source: `KlassApp-Foundation/KlassApp@main` (02c28aa), read from the repo. **The uploaded `.txt` copies never arrived:** `uploads/` still holds the older copy, which has no screenshots section. `landing-preview.css` is about 4,100 lines. I read its `:root` block and searched it with targeted patterns, but **didn't read it end to end**. Line numbers are from `main`.

The page is already in good shape. The v2 tower ships (`@include('partials.landing-toshi-tower')`), `--brand-accent: #15803D` exists, and the honesty lines are in place. What's left is mostly **one systemic contrast problem** plus groundwork for the screenshots section.

Legend: **SAFE** = mechanical, no visible design decision · **DECIDE** = needs a call.

## 1. Contrast — the one systemic issue
**`--text-muted: #94A3B8` is used as body text about 30 times.** On white that's **2.56:1**; on `--paper-base` it's about 2.45:1. Both fail 4.5:1. Examples: L749, 873, 943 (`.panel-badge.idle`), 1061 (`.connector-card p`), 1318, 1835, 2087, 2185, 2299, 2580, 2635, 2707, 2714, 2807, 2872, 2915, 3075, 3157, 3241, 3276, 3378, 3405, 3442, **3632 (`.compare-header .lead`)**, 3668 (`.compare-label`), 3712. Also hard-coded `#94A3B8` at L533 and L2392.

- **Fix (SAFE):** make each of those text uses `var(--text-secondary)` `#64748B`. That's 4.76:1 on white and 4.55:1 on paper.
- **Keep `--text-muted`** for placeholders (L2903 — placeholder text is exempt, but raising it is kinder), disabled states, and decoration.
- Don't change the token's value. Some uses are borders and dots, which should stay light.

**The honesty line is one of the failures.** "Illustrative of real product capability. Not sourced from a named school." (`.compare-header .lead`, L3632) is `--text-muted` at 2.56:1. So today it is technically hard to read.
- **Fix:** switch it to `--text-secondary`, and change nothing else: same position, same size, same wording.
- That makes it *more* legible, not less. It meets the "keep it as prominent as it is" brief.
- **`.pillar-note`, checked (L3609–3614):** it is **not** `--text-muted`. It's `color: var(--brand-amber)` `#D97706` at 0.75rem/500, which is **3.19:1** on white and **also fails** 4.5:1. The fix is colour only, keeping the amber hue so it keeps its prominence and its link to the `Coming` badge: `#B45309`, 5.02:1. The landing has no darker amber token, so this is a literal. It's the same value the app uses for `--d-warning`.

`.hero-device-chrome em`, `rgba(226,232,240,.6)` on the dark frame: about 5.8:1, which passes. It's superseded by the hero framing work anyway.

## 2. Hero device framing (the brief)
Concept: `concepts/landing-hero-frame/index.html`.
- **≥901px:** the existing dark window, with its real values: padding 16, radius 22, `linear-gradient(160deg,#1E293B,#0F172A)`, and a base 14px tall at `margin:0 16%`, gradient `#1E293B→#334155`. These are now copied into the concept from L3905–3925. The italic caption becomes an address-bar pill that tracks the active card (`web.whatsapp.com` / `drive.google.com` / `app.slack.com`), driven by the existing flip JS. The pill is the only new element.
- **Existing mobile rule:** at ≤700px (L3953), the live CSS already hides the chrome and base and compacts the frame (padding 10, radius 18). The phone replaces that compact frame, and the switch moves up to 900px.
- **≤900px:** a real phone, built from the landing's own phone values (280×560, radius 40, padding 12, `--brand-dark`, 120×28 notch).
- It's CSS only, switching at the existing 900px breakpoint. `#heroRoleStage` and its JS don't change.
- **Unverified here:** the Admin card's height inside a 536px screen. If it overflows, the screen scrolls internally; the phone must not grow.

## 3. Screenshots section — groundwork (SAFE)
Current markup (`.shots`, L4037 on) repeats the chrome by hand three times, and the images have `height:auto`.
1. **Extract** it to `resources/views/components/landing/app-shot.blade.php`:
   ```blade
   @props(['src','alt','title','url'])
   <figure class="shot">
     <div class="ui-chrome"><span class="ui-dot"></span><span class="ui-dot"></span><span class="ui-dot"></span><span class="ui-chrome-title">{{ $title }}</span><span class="shot-url">{{ $url }}</span></div>
     <div class="shot-frame"><img src="{{ $src }}" alt="{{ $alt }}" width="1600" height="1000" loading="lazy" decoding="async"></div>
   </figure>
   ```
2. **Fix the aspect ratio:** `.shot-frame{aspect-ratio:16/10;overflow:hidden;background:var(--brand-light)}` and `.shot-frame img{width:100%;height:100%;object-fit:cover;object-position:top}`. A replacement capture at a slightly different size then can't reflow the grid.
3. **One file per screen:** `public/images/landing/app-dashboard.webp`, `app-books.webp`, `app-page.webp`. **Rename `app-page.webp` to `app-fees.webp`**; its alt text and title say "Fees & payments", and the generic name is how stale images go unnoticed.
4. **Keep the section's own honesty copy as it is:** "Captured from a local KlassApp instance…".
5. **No capture date** (decided).

## 4. Visual-language drift
| # | Where | Issue | Fix |
|---|---|---|---|
| 4.1 | "How it works" WhatsApp preview `.wa-avatar` | Still the text initials **"KA"**. Everywhere else, Toshi's avatar is the K mark (the hero cards, as ruled). | **SAFE:** use `<img src="{{ asset('images/klassapp-icon.svg') }}" alt="">` in the same circle. |
| 4.2 | Same section, `.teach-side-mark` | A text **"K"** stands in for the logo. | **SAFE:** use the icon, as in 4.1. |
| 4.3 | Hero `.hero-chip.live` "Live", and `.panel-badge.live` ×3 | The app's LIVE badge was removed on purpose. On a marketing mockup, "Live" reads as a claim. | **DECIDE:** keep it, since it describes connector state inside a mock, or change it to "Active". I'd keep it; it's inside a mockup of a tool, not a claim about the product. |
| 4.4 | `resources/css/landing.css` (legacy; `text-primary #0F172A`, `text-secondary #475569`) | This is the file the earlier Bricolage/Inter finding came from. `landing-v2` doesn't load it. | **DECIDE:** confirm no route still serves it, then delete it. That closes the landing-fonts investigation without a history check. |
| 4.5 | `auth-preview.css` L236/269/402 | `#94A3B8` text on the auth pages | Out of scope (not the landing page), but it's the same fix as §1. Flag it for the auth pass. |

## 5. Preserved word for word — do not edit
- "Stated direction. Not a live feature yet." (`.pillar-note`), together with the `Coming` badge and its `title`.
- "Illustrative of real product capability. Not sourced from a named school." (`.compare-header .lead`) — only its colour changes, per §1.
- The "Five commitments… four live platform primitives, and one honest forward direction." lead in `#trust`. It's the same kind of honesty signal, so add it to the preserved list.

## Suggested PRs
1. `fix(landing): muted text → AA` — §1 (every text use, including both honesty lines' colours only)
2. `feat(landing): viewport-appropriate hero device frame` — §2
3. `refactor(landing): x-landing.app-shot component + fixed aspect ratio` — §3
4. `chore(landing): K mark in how-it-works previews` — §4.1–4.2
