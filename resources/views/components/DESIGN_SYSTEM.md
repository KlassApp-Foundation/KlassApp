# KlassApp Design System

> **Stack row superseded.** Canonical tooling SoT: `.cursor/rules/frontend.mdc` (Vite 8 sole bundler, Tailwind v4.3.3, Vue 3.5.40 `@vue/compat`). Component API docs below remain valid.

## Stack Context

| Layer | Technology | Notes |
|---|---|---|
| CSS framework | Tailwind CSS **v4.3.3** | CSS-first `@theme` in `resources/css/tailwind.css`; Vite plugin `@tailwindcss/vite` |
| Build | **Vite 8** + `laravel-vite-plugin` | `npm run dev` / `npm run build` — **no** Mix / `npm run production` |
| Components | Laravel anonymous Blade components | `resources/views/components/*.blade.php` auto-register as `x-*` |
| Design tokens | CSS custom properties in `dashboard-refresh.css` | Colors, fonts, shadows in `:root` block |
| Fonts | Sora (headings), DM Sans (body) | Google Fonts via `@import` |
| Vue (app shell) | Vue 3.5.40 via `@vue/compat` MODE 2 | Options API; alias `vue` → `@vue/compat` |

## Available Components

All components use the `ds-*` CSS namespace in `public/css/dashboard-refresh.css`.

### `<x-button />`

```blade
<x-button variant="primary" size="md" href="/url" type="button">
    Label
</x-button>
```

**Props:**
- `variant`: `primary` (default) | `success` | `danger` | `warning` | `outline` | `ghost`
- `size`: `sm` | `md` (default) | `lg`
- `href`: URL string — renders `<a>` instead of `<button>`
- `type`: `button` (default) | `submit`
- `disabled`: boolean
- `class`: additional CSS classes

**CSS classes applied:** `.ds-btn .ds-btn-{variant} .ds-btn-{size}`

---

### `<x-card />`

```blade
<x-card title="Optional Title" padding="default" shadow="sm" :hover="true">
    Content
</x-card>
```

**Props:**
- `padding`: `default` | `sm` | `none` | `lg`
- `shadow`: `sm` (default) | `md` | `lg` | `none`
- `hover`: boolean — adds lift-on-hover effect
- `title`: string — optional card heading
- `class`: additional CSS classes

**CSS classes applied:** `.ds-card .ds-card-padding-{size} .ds-card-shadow-{size}` (+ `.ds-card-hover`)

---

### `<x-badge />`

```blade
<x-badge variant="pending" size="sm">Pending</x-badge>
```

**Props:**
- `variant`: `pending` | `approved` | `rejected` | `paid` | `unpaid` | `active` | `inactive` | `warning` | `info`
- `size`: `sm` (default) | `md`
- `class`: additional CSS classes

**Status color map:**

| Variant | Background | Text |
|---|---|---|
| `pending`, `info` | `#EFF6FF` | `#1D4ED8` |
| `approved`, `paid`, `active` | `#F0FDF4` | `#15803D` |
| `rejected`, `unpaid` | `#FEF2F2` | `#DC2626` |
| `warning` | `#FFFBEB` | `#D97706` |
| `inactive` | `#F1F5F9` | `#64748B` |

---

### `<x-table />`

```blade
<x-table :headers="['Name', 'Class', 'Status']" striped hover>
    @foreach($items as $item)
    <tr>
        <td>{{ $item->name }}</td>
        <td>{{ $item->class }}</td>
        <td><x-badge variant="active">Active</x-badge></td>
    </tr>
    @endforeach
</x-table>
```

**Props:**
- `headers`: array — column header labels (optional)
- `striped`: boolean — alternating row backgrounds
- `hover`: boolean — highlight on hover
- `class`: additional CSS classes

**Note:** For tables with split content (active + archived in same view), use the raw `.ds-table` CSS classes instead:

```html
<div class="ds-table-wrap">
    <table class="ds-table w-full">
        <thead><tr><th>...</th></tr></thead>
        <tbody>...</tbody>
    </table>
</div>
```

---

### `<x-form-group />`

```blade
{{-- Text input --}}
<x-form-group label="Full Name" name="name" required placeholder="Enter name"
    :error="$errors->first('name') ?? null" />

{{-- Select --}}
<x-form-group label="Class" name="class_id" type="select" required
    :options="$classes->pluck('name', 'id')->prepend('Select...', '')->toArray()" />

{{-- Textarea --}}
<x-form-group label="Description" name="desc" type="textarea" />

{{-- With custom slot content (for complex inputs) --}}
<x-form-group label="Department" name="dept_id" :error="$errors->first('dept_id') ?? null">
    <select name="dept_id" class="ds-form-input ds-form-select">
        ...
    </select>
</x-form-group>
```

**Props:**
- `label`: string — label text
- `name`: string — input name attribute (also used for `id`)
- `type`: `text` (default) | `email` | `select` | `textarea` | `number` | `date`
- `value`: mixed — default value
- `error`: string — validation error message
- `required`: boolean — adds red asterisk to label
- `placeholder`: string
- `help`: string — help text below input
- `options`: array — for select type (`[key => label]`)
- `class`: additional CSS classes on wrapper

---

### Status Dot

```blade
<span class="ds-dot ds-dot-green"></span> Active
```

CSS classes: `.ds-dot` + `.ds-dot-{color}` where color is `green`, `blue`, `amber`, `red`, `gray`.

---

### CSS Utility Classes (raw)

| Class | Purpose |
|---|---|
| `.ds-page-head` | Flex container for page title + action buttons |
| `.ds-page-head-title` | Page heading (Sora, bold, dark) |
| `.ds-page-head-sub` | Subtitle below heading (muted) |
| `.ds-card-title` | Card heading within ds-card |

## Component Architecture Rules

1. **No inline styles** — all styling goes through `ds-*` CSS classes or Tailwind utilities.
2. **No `@apply`** — Tailwind v1.4.6 doesn't support it; use regular CSS instead.
3. **No arbitrary values** (`bg-[#1E6FD9]`) — Tailwind v1.4.6 doesn't support JIT.
4. **No DaisyUI** — not installed or available.
5. **CSS custom properties** in `dashboard-refresh.css` are the source of truth for brand tokens.
6. **Components are anonymous Blade files** — no `app/View/Components/` classes needed.

## Migration Pattern

When migrating a view to the design system:

1. Replace `<h1 class="admin-h1 ...">...` with `<h1 class="ds-page-head-title">`
2. Replace raw `<a>` action buttons with `<x-button variant="..." size="sm">`
3. Replace `.bg-white.rounded.shadow.p-*` cards with `<x-card>`
4. Replace raw `<table>` with `<x-table>` (simple tables) or `.ds-table` classes (complex tables)
5. Replace status text with `<x-badge variant="...">`
6. Replace raw `<input>/<select>` with `<x-form-group>`
7. Replace `.custom-green` / `.blue-bg` with `<x-button variant="success/primary">`
8. Replace `.tw-form-control` / `.tw-form-label` with `ds-form-input` / `ds-form-label`

## Migration Status (July 6, 2026)

### Fully Migrated (14 Admin views)
Subject CRUD (index, list, form, edit), Fee payments/unmatched/create, Staff index,
Parent show/create, Classes create, Attendance index, Member show, Promotion create
Page header partial — all use `ds-page-head`, `x-button`, `x-card`, `x-table`, `x-form-group`.

### Partially Migrated (headings only)
Teacher dashboard, leave views, lesson plan create, homework create,
attendance create, events index — headings migrated to `ds-page-head` / `ds-page-head-title`.
Buttons, cards, tables still use legacy classes in most views.

### Legacy Classes Still in Use
The following views across Admin, Teacher, Accountant, and Library roles still use
`admin-h1`, `tw-form-control`, `custom-green`, `dashboard-section-title`, or `tw-form-label`:
- Teacher: 52/83 views
- Accountant: 21/43 views
- Library: 17/19 views
- Admin: 129/221 views (14 already migrated)
- Student: most views

### Migration Priority
1. High-traffic teacher surfaces (leave, lesson plan, homework, assignment)
2. Accountant payroll/fee views (most used by that role)
3. Remaining admin views
4. Library and student views (lowest priority)

### Toshi
AgentToshi's panel/pill/header/composer/message-bubbles now use dedicated
`toshi-*` CSS classes defined in dashboard-refresh.css. ~473 inline `style=`
attributes remain on smaller elements (icons, spacing tweaks). These are
lower priority — the structural redesign is complete; remaining inline
styles are cosmetic polish on top of the class-based layout.
