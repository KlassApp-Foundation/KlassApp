# assets/

## brand/ — KlassApp logo

The user supplied three files. **The source codebase contains no logo**, so none of
this comes from `ds-bundle/`; a codebase re-sync will not produce them.

### Official, as supplied

| File | What it actually is | Canvas | Background |
|---|---|---|---|
| `klassapp-icon.svg` | Icon only — the K in a mortarboard | 2000×2000 | Transparent |
| `klassapp-horizontal-dark.svg` | Horizontal lockup, dark-surface colours | 2048×754 | Baked-in `#0C1528` plate |
| `klassapp-stacked.svg` | **Misnamed — this is a HORIZONTAL lockup**, light-surface colours, on a wide social-card canvas | 2048×1117 | Baked-in `#FAFBFA` plate |

> **`klassapp-stacked.svg` is not stacked.** Its icon sits at x 425–714 and its
> wordmark at x 773–1637 — side by side, vertically overlapping. The name is
> wrong, not the file. **It has been kept under its original name** because it may
> be wired into OG/Twitter meta images, where its 2048×1117 canvas and opaque
> plate are exactly right for a social card. **Do not rename or delete it without
> checking what references it.**

### Derived — all mechanical, path data byte-identical

| File | Operation | Use |
|---|---|---|
| `klassapp-icon-reversed.svg` | Icon with gradients removed, all fills `#FFFFFF` | Dark surfaces |
| `klassapp-horizontal-light.svg` | `klassapp-stacked.svg` with plate deleted, counters knocked out, cropped to content | Light surfaces |
| `klassapp-horizontal-transparent.svg` | `klassapp-horizontal-dark.svg` with plate deleted — **dark surfaces only** (its “Klass” is `#FEFEFE`) | Dark surfaces |
| `klassapp-stacked-light.svg` | Icon centred above wordmark, composed from `klassapp-stacked.svg` | Light surfaces |
| `klassapp-stacked-dark.svg` | Same composition from `klassapp-horizontal-dark.svg` | Dark surfaces |

**No letterform was ever re-set, so no typeface was identified or guessed.** Both
official files contain no `<text>`, no `<tspan>` and no font metadata — the
wordmark is already outlines, and those exact outlines are reused everywhere. If
you ever need to *extend* the wordmark (a tagline, a new word), that will require
identifying the real typeface from the design source; nothing here answers it.

### The real colours

Confirmed from the official files, not chosen:

| Context | “Klass” | “App” |
|---|---|---|
| Light surfaces | `#0E2347` | `#22B560` |
| Dark surfaces | `#FEFEFE` | `#26B45F` |

The icon's own fills differ slightly between the two exports (`#1E6EC8`/`#22B560`
in the light file, `#1E6BC5`/`#26B45F` in the dark one). Each derived file keeps
its own source's values rather than normalising them — normalising would be a
brand decision.

### Why the plates could not simply be deleted

Both official exports were **flattened over their background plate**. Deleting the
plate leaves two artefacts:

1. The four counters (the enclosed holes in **A**, **p**, **p**, **a**) are not
   holes — they are separate paths *painted* the plate colour, so they render as
   solid blobs.
2. In the dark file, “Klass” is `#FEFEFE` — invisible on a light surface.

Every derived file fixes (1) by merging each counter into its parent glyph's `d`
with `fill-rule="evenodd"`, producing **true knockouts**. Counters are matched to
parents by bounding-box containment, not by hand. Problem (2) is why the light
lockup is extracted from `klassapp-stacked.svg` (which has real light colours)
rather than recoloured from the dark file. Verify with
`guidelines/brand-logo-knockout-check.card.html`.

### The one open judgement call

**The stacked lockup's vertical gap.** No source has a stacked arrangement, so
there is no real value to copy. Used: **20% of icon height** — 58px light, 60px
dark. Corroboration: the official files' own *horizontal* mark-to-wordmark gaps
are 59px and 67px, so the derived value sits inside the range the brand already
uses. Everything else about both stacked files is mechanical. **Needs sign-off:**
review `guidelines/brand-logo-stacked.card.html`.

Icon and wordmark are at **native scale** in the stacked files, so each source's
own icon:wordmark height ratio (1.66:1 light, 1.18:1 dark) is preserved exactly.
A chunkier icon relative to the wordmark would be a new design decision and was
not made.

### Rules

- **Never recolour, rotate, outline, add effects to, stretch or redraw the mark.**
  Full-colour variants on light surfaces; reversed/dark variants on dark ones.
- The two official lockups **carry their own background plates** — place them on a
  matching surface, or use the transparent derived files instead.
- Clear space: at least the width of the K's upright on all sides.
- Minimum sizes: icon 16px; lockups 120px wide.
- The originals carry C2PA provenance metadata; the verbatim copies keep it.
  Derived files drop it, since their content changed.

### Placement

**Deliberately undecided.** Which variant goes in nav, footer, login, email and
social is pending sign-off on the unified system. The files are documented, not
yet assigned.

### Favicons and app icons

All raster icons are generated from `klassapp-icon.svg`. The required size set,
head tags and `manifest.json` fields are specified in
[`../guidelines/favicons.md`](../guidelines/favicons.md) — read it before shipping
a new page or subdomain.

## brand/ — third-party marks

Marks of the tools KlassApp operates inside, copied verbatim from the source
bundle's React components. Never recolour these either.

- `whatsapp.svg` — official WhatsApp mark, #25D366.
- `google-drive.svg` — official Google Drive mark; viewBox is wider than tall.
- `slack.svg` — official Slack 2019 hash.

## Icons

No icon binaries. The app draws icons as inline SVG paths (Heroicons v1 outline,
24×24, stroke 2, `currentColor`) — the eight glyphs `KpiCard` ships are verbatim
Heroicons v1. See `components/core/Icon.jsx`.

## Imagery

None exists. No photography, illustration, pattern or texture — ask rather than
generating any.
