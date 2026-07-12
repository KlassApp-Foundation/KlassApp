# Frontend Build Safeguards

## Pre-deploy Checklist

After any `npm run production` or SCSS/webpack config change, verify:

### 1. Tailwind processed correctly (catches missing PostCSS plugin)

```bash
grep -c '@tailwind' public/css/app.css
# Expected: 0   (if > 0, @tailwind directives weren't resolved)
```

```bash
ls -lh public/css/app.css
# Expected: >= 1MB   (Tailwind v1 full output)
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

## PostCSS / Tailwind Config Notes

- `webpack.mix.js` must include `tailwindcss("./tailwind.config.js")` as a PostCSS plugin.
- `laravel-mix-tailwind` wrapper package is NOT required — the direct PostCSS plugin works with Mix 4.
- `dashboard-refresh.css` is a **static file** loaded separately — not rebuilt by `npm run production`. If dashboard styles need changes, edit it directly.
- The SCSS source files (`adminstyle.scss`) must NOT have `!important` rules on layout elements that conflict with inline styles on those same elements in Blade templates.
