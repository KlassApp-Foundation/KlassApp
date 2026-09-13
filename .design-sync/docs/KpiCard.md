---
category: Data Display
---

# KpiCard

Dashboard metric tile. Blade equivalent: `<x-ds-kpi-card>` in
`resources/views/components/ds-kpi-card.blade.php`.

```jsx
<KpiCard icon="users" value="1,284" label="Total Students" color="blue"
         link="/reception/students" />
```

Renders a `<div>`, or an `<a>` when `link` is set. Laid out in an
`auto-fit` grid across the top of the role dashboards.

## Props

- `icon` — see the glyph table below; anything unrecognised renders an
  info-circle fallback
- `value` — the headline figure; defaults to an em dash
- `label` — caption underneath
- `color`: `blue` (default) · `green` · `amber` · `red` · `purple` — tints the
  icon chip only
- `link` — makes the whole tile an anchor

## Icon keys

Several keys share one glyph:

| Glyph | Keys |
|---|---|
| people | `users` |
| building | `classes`, `door` |
| calendar | `exam`, `calendar` |
| chat | `whatsapp`, `message` |
| book | `book`, `library` |
| bell | `bell`, `notice` |
| currency | `dollar`, `money` |
| clipboard-check | `check`, `tasks` |
| info (fallback) | anything else |

The icon chip's background and foreground are inline styles from the Blade
`$colorMap`, not CSS classes — `color` is the only way to change them.
