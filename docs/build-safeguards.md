# Frontend Build Safeguards

> **Superseded for bundler tooling.** Vite is the sole bundler (Phase 3.5). Canonical SoT: `.cursor/rules/frontend.mdc`. This file keeps useful post-build visual checks; Mix / `npm run production` / PostCSS-via-webpack guidance below is historical.

## Pre-deploy Checklist

After any `npm run build` (or `npm run dev` locally) or SCSS/Vite config change, verify:

### 1. Vite assets linked correctly (not Mix)

Blade layouts must use `@vite([...])` — not `mix()` / not bare `asset('js/app.js')` for the app bundle.

```bash
# Production must NOT leave a stale Vite HMR pointer
test ! -f public/hot && echo "ok: no public/hot"

# Manifest present after production build
test -f public/build/manifest.json && echo "ok: Vite manifest"
```

```bash
# Sass entry compiles (no leftover @tailwind directives in compiled SCSS output)
# Tailwind v4 lives in resources/css/tailwind.css via @tailwindcss/vite — not in app.scss
ls -lh public/build/assets/*.css | head
```

### 2. Header/sidebar colors not overridden by !important (catches stale SCSS rules)

**Selector:** `.dashboard-themed-header`
- **Expected computed style:** `background-color: transparent` (or the inline `#FAFAF5` shows through)
- **Failure mode:** `background-color: #0f172a !important` — header renders dark
- **Check in browser console:**
  ```js
  getComputedStyle(document.querySelector('.navbar')).backgroundColor
  // Expected: 'rgb(250, 250, 245)' (#FAFAF5)
  ```

**Selector:** `.admin-sidebar` (or `.dashboard-themed-sidebar`)
- **Expected computed style:** `background-color: rgb(255, 252, 245)` (#FFFCF5) — the inline value
- **Failure mode:** `background: #063f8d` (or other hardcoded dark color) takes over
- **Check in browser console:**
  ```js
  getComputedStyle(document.querySelector('.admin-sidebar')).backgroundColor
  // Expected: 'rgb(255, 252, 245)' (#FFFCF5)
  ```

### 3. Login page centered (catches missing utility classes)

```js
// In browser console on /login page:
getComputedStyle(document.querySelector('main > div')).justifyContent
// Expected: 'center'
```

## Vite / Tailwind Notes (current)

- **Scripts**: `npm run dev` (HMR) / `npm run build` (production). There is **no** Laravel Mix / `webpack.mix.js` / `npm run production`.
- **Tailwind v4.3.3**: CSS-first `@theme` in `resources/css/tailwind.css`; Vite plugin `@tailwindcss/vite`. No `tailwind.config.js`.
- **`dashboard-refresh.css`**: static file loaded separately — not rebuilt by Vite. Edit it directly for dashboard style changes.
- **SCSS**: `resources/assets/sass/app.scss` is plain CSS post–Phase 2b (`@apply` not reintroduced). Prefer raw CSS values in SCSS; `@apply` only inside `resources/css/tailwind.css`.
- **v4 preflight**: default `border-color` is `currentColor` — bare `border` needs an explicit color (e.g. `border-gray-200`).
- See `.cursor/rules/frontend.mdc` and `.cursor/rules/known-pitfalls.mdc` for full conventions.

## Historical (Mix era — do not follow)

~~`webpack.mix.js` must include `tailwindcss("./tailwind.config.js")` as a PostCSS plugin.~~ Removed Phase 3.5.
~~`laravel-mix-tailwind` / Mix 4 / `npm run production` / Tailwind v1 full `public/css/app.css` size checks.~~ Superseded by Vite hashed assets under `public/build/`.
