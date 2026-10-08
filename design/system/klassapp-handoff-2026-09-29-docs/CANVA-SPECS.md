# CANVA-SPECS: social templates and pitch deck

This spec is for rebuilding items 4 (social) and 6 (pitch deck) as Canva templates. The reference renders are `design-system/concepts/social/kit.html` (all 6 types × 8 formats, 60 artboards at real size, with a safe-zone overlay and an automatic overflow check) and `concepts/social/index.html` (use "Show safe zones") and `templates/pitch-deck/PitchDeck.dc.html`. All values are in export pixels.

**Not measured in Canva:** these specs were rendered and checked in a browser only. Contrast ratios are calculated from hex.

## Logo (decided 2026-10-01)
- **Default: the stacked lockup.** Use `klassapp-stacked-light.svg` on paper and `klassapp-stacked-dark.svg` on navy, 96 px tall at 1080 wide (≈ 9% of the short side), bottom left inside the safe zone.
- **Exception: the horizontal lockup** (56 px tall) **only where the format is wide and short:** the X post (1600 × 900, 16:9). There, the stacked mark would eat the text height.
- Never both on one image, and never the icon alone as a substitute for the wordmark.

## Brand kit (load into Canva's Brand Kit first)
| Item | Value |
|---|---|
| **Fonts** | Headings: **Sora** 600/700. Body: **DM Sans** 500/600/700. Both are in Canva's library; upload them from Google Fonts if missing. |
| **Paper (light bg)** | #FAFAF5 |
| **Navy (dark bg)** | #0C1528, matching the reversed wordmark's own plate so the logo has no visible box |
| **Ink** | #0F172A for headlines and #334155 for body on paper |
| **Green (text on paper)** | #15803D: kickers and big numbers, 5.0:1 on paper |
| **Green (text on navy)** | #4ADE80 for kickers only |
| **Pill** | #DCFCE7 background with #14532D text (paper), or #14532D background with #DCFCE7 text (navy) |
| **Light chrome on navy** | #CBD5E1 for sub-lines, 11:1 |
| **Placeholder highlight** | #FEF3C7 background with #78350F text. Use it only for "[fill me]" fields, and never publish with it showing. |
| **Logos** | `assets/brand/klassapp-horizontal-light.svg` on paper and `klassapp-horizontal-dark.svg` on navy. Never recolour or stretch them, never put them on a photo, and never use the icon alone as the only brand mark on a post. |

Don't use gradients, emoji, stock photos of children, or robot or AI imagery.

## Type scale (at 1080px width; scale proportionally for other widths)
| Role | Font | Size / line height | Notes |
|---|---|---|---|
| Kicker | DM Sans 700, caps, +14% tracking | 26px | Green |
| Headline | Sora 700, −3% tracking | 88px / 1.08 | 8 words at most; 72–84px on dense layouts; never below 64px |
| Sub-line | DM Sans 500 | 38px / 1.35 | 25 words at most; never below 30px |
| Quote | Sora 600, −2% | 58px / 1.25 | |
| Big number | Sora 700, −4% | 180px | Tip number and carousel index |
| Detail rows | DM Sans 600 | 34px | Events |
| URL / pill | DM Sans 600/700 | 26–28px | |

## Sizes and safe zones
Every layout has an **80px margin on the left and right**. The top and bottom margins vary by format:

| Channel | Size (px) | Top / bottom safe | Headline | Logo |
|---|---|---|---|---|
| LinkedIn post | 1200 × 1200 | 80 / 80 | 84 | bottom-left, 56px tall |
| LinkedIn carousel | 1080 × 1350 per page, exported as PDF | 80 / 80 | 72–88 | bottom-left on every page |
| X post | 1600 × 900 | 72 / 72 | 76 (sub 34) | bottom-left |
| Instagram post | 1080 × 1350 | 80 / 80 | 88 | bottom-left |
| Instagram story | 1080 × 1920 | **250 / 340** (UI overlays) | 88 | bottom-left, above the 340px band |
| Facebook post | 1080 × 1350 | 80 / 80 | 88 | bottom-left |
| WhatsApp Status | 1080 × 1920 | **220 / 220** | 88 | bottom-left, above the band |
| WhatsApp Channel image | 1080 × 1080 | 80 / 80 | 78 (sub 34) | bottom-left |

**Logo placement is fixed:** bottom-left inside the safe zone, 56px tall at 1080 width (about 5%). The URL, when shown, sits bottom-right on the same baseline.

## The six post types
**Fixed elements:** background, logo, margins, kicker style and type styles; the stack order is kicker, headline, sub-line, flexible space, footer. **Editable elements:** the text in each field and the screenshot. Nothing else moves.

| Type | Background | Fields (editable) | Fixed |
|---|---|---|---|
| **Announcement** | Paper | Kicker ("Now in KlassApp"), headline, sub-line | Layout, logo, URL klassapp.xyz |
| **Feature spotlight** | Paper | Kicker, headline (76px), screenshot | The screenshot frame: 24px radius, 3px #0F172A border, filling the flexible space. **Demo Junior School data only.** |
| **School testimonial** | Paper | Quote, name, role, school, district | **The quote must come from a real school with written consent on file.** Until then, the #FEF3C7 placeholder stays and the post isn't published. |
| **Event / webinar** | Navy | Kicker, headline, date/time/where rows, register-link pill | Rows are label (#94A3B8) then value (white); time is in **EAT** |
| **Hiring** | Navy | Role title, location line, apply-link pill | |
| **Tip of the week** | Paper | Tip number (two digits), headline (72px), sub-line | URL klassapp.xyz/help |

## LinkedIn carousel
- **Length:** 5–8 pages, all 1080 × 1350.
- **Page 1, cover:** kicker plus headline, with "Swipe" as the sub-line.
- **Middle pages:** big index number ("01"), then headline (72), then one sub-line.
- **Last page:** a call to action and no URL pill. The logo appears on every page.

## Copy rules (from the voice guide)
- Only features that are live today. Anything planned says "Coming".
- For open source, use the sentence exactly as written: "The source is public on GitHub; supported self-hosting opens after an independent security review."
- Never use a real school, child, parent or phone number without written consent.

---

## Pitch deck: 16:9 master
**Canvas and grid:**
- **Canvas:** 1920 × 1080.
- **Margins:** 140px left and right, 110–120px top, 100–110px bottom.
- **Grid:** 2 columns with a 100px gap on content and data slides.
- **Footer:** logo bottom-left (horizontal, 44px tall on paper) and slide number bottom-right in DM Sans 500 22px #475569.

**Six layouts:**

| # | Layout | Background | Fixed | Editable |
|---|---|---|---|---|
| 1 | **Title** | Navy #0C1528 | Dark lockup top-left, 132px tall (it includes its own plate padding) | Kicker (#4ADE80, 30px caps), title (Sora 700 120px, −3.5%), presenter line (DM Sans 40px #CBD5E1) |
| 2 | **Section** | Green #15803D | Layout | Section number (Sora 150px #DCFCE7), title (Sora 104px white, 5.0:1), one line (40px #F0FDF4) |
| 3 | **Content** | Paper | Kicker, title (Sora 72px), numbered list with 56px #0F172A circles | List items (DM Sans 36px, 3–4 items), screenshot (right column, same frame as social) |
| 4 | **Data** | Paper | Big number in Sora 200px #15803D, bars in #CBD5E1 with the latest bar in #15803D, a 3px #0F172A baseline, and the value above each bar | Number, label, **source and date (required)**, bar values |
| 5 | **Quote** | Paper | Layout, logo | Quote (Sora 600 76px), attribution. **Same consent rule as social.** |
| 6 | **Closing** | Navy | The open-source sentence, verbatim; lockup bottom-left, 60px | Title and contact line |

**Type floor:** nothing on a slide is smaller than 22px, and body text is at least 36px.

**Canva build:** create each layout as a page in one Canva template. Lock the background, logo and footer elements (right-click, then Lock). The editable text boxes keep Brand Kit styles.
