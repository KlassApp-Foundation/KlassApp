# Landing page polish pass — scope and constraints (2026-09-27)

Status: **blocked on source.** The copy of `landing-v2.blade.php` in this project (`uploads/landing-v2.blade.php.txt`) predates staging: it has **no "Real screenshots from the app" section** and no hero device-mockup changes beyond `#heroRoleStage`. This project also has no record of the earlier "hero device-mockup framing, full page polish" brief. Nothing below has been designed yet.

## Needed before the pass starts
1. The current `resources/views/landing-v2.blade.php` and `resources/css/landing-preview.css` from the branch that staging deploys.
2. The original polish brief, or confirmation that "full page polish" is the whole brief.

## Preserve word for word — credibility markers
These are deliberate honesty signals for developers, the n8n ambassador community, and the founder-led, African open-source story. A polish pass must not soften, shorten, restyle into small print, or move them away from the claim they qualify.

| Where (in the uploaded copy) | Text | Rule |
|---|---|---|
| Pillars, "Provable" card: `.pillar-note`, L679 | "Stated direction. Not a live feature yet." | Keep it under the claim, at readable size and contrast (≥4.5:1). Keep the `Coming` badge and its `title`. |
| `#compare` header: `.lead`, L689 | "Illustrative of real product capability. Not sourced from a named school." | Keep it as the section lead, above the list, not a footnote. |

**Note:** staging wording may differ slightly, for example "Illustrative... not sourced from a named school". Whatever staging ships is the text to preserve; check it against the current source.

## Screenshot section — what the review will check
- **Structure:** one reusable Blade partial (e.g. `<x-landing.app-shot>`), with props for `src`, `alt`, `label`, `role`, and a `captured` date. The frame, label and caption styles belong to the partial, not the image.
- **Fixed aspect-ratio frame** (`aspect-ratio` plus `object-fit: cover; object-position: top`), so a new screenshot with slightly different dimensions doesn't reflow the section.
- **Images live in one folder,** e.g. `public/images/landing/app/`, named by screen (`admin-dashboard.webp`), not by date. Updating a screenshot means replacing one file.
- **Visible capture date** in the caption ("Captured Sep 2026") if it matches the page's honesty tone. This needs a decision: it's accurate, but it will look stale over time.
- **Real `alt` text** describing the screen, not "screenshot".
- **The frame must not imply a device** the screenshot wasn't taken on. Use browser chrome for desktop captures and a phone frame only for real mobile captures.
