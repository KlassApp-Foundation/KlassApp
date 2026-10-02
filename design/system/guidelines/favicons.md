# Favicon & app-icon spec

**Canonical source: `assets/brand/klassapp-icon.svg`.** Every raster icon below is
generated from that one file. Do not hand-author icon PNGs, and do not reuse an
icon from another page — regenerate from the SVG.

## Why this page exists

Production shipped **legacy GeGoK12 orange icons** long after the rebrand: they
had never been replaced with KlassApp's green mark. Alongside that, `manifest.json`
was broken (wrong name, wrong relative paths) and several required sizes were
missing entirely. All three are fixed. This page is the spec so a new page,
subdomain or standalone build can't silently reintroduce any of them.

## Required size set

| File | Size | Format | Notes |
|---|---|---|---|
| `favicon-16.png` | 16×16 | PNG | Browser tab |
| `favicon-32.png` | 32×32 | PNG | Tab at 2× / bookmarks bar |
| `apple-touch-icon.png` | 180×180 | **Opaque PNG** | **Never SVG.** Safari ignores SVG here and renders transparency as black — this one file needs a solid white plate baked in. No rounded corners; iOS masks it. |
| `icon-192.png` | 192×192 | PNG | PWA / Android home screen — **required by the manifest** |
| `icon-512.png` | 512×512 | PNG | PWA splash + store listings — **required by the manifest** |
| `favicon.svg` | — | SVG | Optional modern favicon; serve `klassapp-icon.svg` as-is |

Transparent background everywhere **except** `apple-touch-icon.png`.

## Head tags

```html
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16.png">
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
<link rel="manifest" href="/manifest.json">
```

## manifest.json

Both 192 and 512 must be present, the name must be the real product name, and
paths must resolve from where the manifest is served — a subdomain or subpath
build is exactly where this broke before.

```json
{
  "name": "KlassApp",
  "short_name": "KlassApp",
  "description": "The school platform that operates in the tools educationists already use.",
  "start_url": "/",
  "scope": "/",
  "display": "standalone",
  "background_color": "#FAFAF5",
  "theme_color": "#22C55E",
  "icons": [
    { "src": "/icon-192.png", "sizes": "192x192", "type": "image/png" },
    { "src": "/icon-512.png", "sizes": "512x512", "type": "image/png" },
    { "src": "/icon-512.png", "sizes": "512x512", "type": "image/png", "purpose": "maskable" }
  ]
}
```

`background_color` is `--d-canvas`, `theme_color` is `--d-green` — the same tokens
the app uses, so the splash screen matches the product.

## Checklist for a new page or subdomain

1. All five PNGs regenerated from `klassapp-icon.svg` — no orange, no GeGoK12.
2. `apple-touch-icon.png` is opaque PNG at 180×180.
3. `manifest.json` present, correct `name`, both 192 and 512 listed.
4. Icon paths resolve from the served path (check a subpath deploy, not just root).
5. Hard-reload and confirm the tab icon is the green mark — browsers cache favicons aggressively.
