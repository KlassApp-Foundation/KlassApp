# Staging Docsify serve gap — root cause (investigation only)

Date: 2026-09-17  
URL under test: `https://klassapp-staging-7mpoqg.laravel.cloud/docs/community/#/./`

## How staging actually serves docs

Not as a static tree under `public/docs/`. A **single Laravel route** in `routes/web.php`:

```php
Route::get('/docs/community/{path?}', function ($path = '') {
    $base = base_path('docs/community');
    // …serve file from docs/community only, or SPA-fallback index.html
})->where('path', '.*');
```

| Path | Staging result |
|---|---|
| `/docs/community/` | 200 `text/html` (index.html) |
| `/docs/community/README.md` | 200 `text/markdown` |
| `/docs/community/_sidebar.md` | 200 `text/markdown` |
| `/docs/shared/docsify-klassapp.css` | **404** Laravel error page (`text/html`) |
| `/docs/` / `/docs/index.html` / `/docs/roadmap.md` | **404** |

Local verification used `python3 -m http.server` with cwd=`docs/`, so `../shared/docsify-klassapp.css` resolved and returned CSS. That path **does not exist** on the Laravel-served surface.

## Browser evidence (Playwright)

- Theme link href: `../shared/docsify-klassapp.css`
- Resolved absolute URL: `https://klassapp-staging-7mpoqg.laravel.cloud/docs/shared/docsify-klassapp.css`
- Network: **404**, `Content-Type: text/html` (app 404 page, not CSS)
- Console: `Failed to load resource: the server responded with a status of 404 ()`
- `requestfailed`: same CSS URL → `net::ERR_ABORTED`
- Docsify **does** load and execute (CDN JS 200; `#app` renders markdown; H1 = “KlassApp Community”)
- Theme tokens absent: `--d-canvas` empty; `body` background `rgb(255, 255, 255)` (default vue.css white), not parchment `#FAFAF5`
- Secondary: jsDelivr pagination CSS hit `net::ERR_BLOCKED_BY_ORB` (MIME/ORB); pagination JS still 200 — secondary to theme break

Screenshots: `01-staging-community-hash-dot.png`, `02-after-roadmap-sidebar-click.png`  
Full dump: `REPORT.json`

## Root cause (one sentence)

**The DESIGN_SYSTEM Docsify theme lives at `docs/shared/`, but production/staging only expose `docs/community/*` via Laravel; the relative `../shared/` stylesheet URL 404s, so staging runs unthemed Docsify even though markdown content loads.**

Same class of gap: local static server ≠ Laravel route surface.

## Out of scope for this report

Fix options (not implemented here): extend Laravel routes to serve `docs/shared` (and hub `docs/`), or copy/inline the CSS under `docs/community/`, or change the link to a path the existing route can serve.
