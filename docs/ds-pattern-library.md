# KlassApp Design System — Pattern Library

## Hover Interaction Standard (Pulse)

All hover effects in the admin dashboard follow this standard, established Jul 22, 2026.

### Transition Timing

| Property | Duration | Easing | Where used |
|---|---|---|---|
| `background`, `color`, `border-color` | **150ms** | `ease` | Buttons, table rows, sidebar items, group headers, form inputs |
| `box-shadow` | **200ms** | `ease` | KPI cards, ds-card-hover, buttons |
| `transform` (hover) | **200ms** | `ease` | KPI card lift, card hover |
| `transform` (active press) | **100ms** | `ease` | Button `:active` scale |
| `opacity` | **200ms** | `ease` | Toshi chips, fade transitions |

**Rule**: Never use `all` in transitions — be specific about which properties animate. Keep timing within 100-200ms for interaction feedback.

### Color Accent

- **Default hover tint**: `rgba(34, 197, 94, 0.04)` (Pulse green, 4% opacity)
  - Sidebar nav items, table rows (ds-table-hover, ds-table-ledger), group headers
- **Stronger tint**: `rgba(34, 197, 94, 0.06)` 
  - Group header background on hover
- **Active state**: `rgba(34, 197, 94, 0.24)` with `rgb(22, 101, 52)` text
  - Sidebar item active pill

### Visual Treatment

| Element | Hover effect | Notes |
|---|---|---|
| Sidebar nav item (dashboard-menu-item) | Green tint background + text | `background-color 0.15s ease` |
| Sidebar group header | Green tint background + green label/chevron | `.sidebar-group-header:hover` |
| ds-btn-primary | Darken accent bg | `background 0.15s ease` |
| ds-btn-success | Darken green (#16a34a) | `background 0.15s ease` |
| ds-btn-danger | Darken red (#992a2a) | `background 0.15s ease` |
| ds-btn-outline | Surface bg + accent border/text | `background 0.15s ease` |
| ds-btn-ghost | Surface bg + dark text | `background 0.15s ease` |
| KPI cards (ds-kpi-card, dashboard-kpi-card) | Box-shadow lift + translateY(-1px) | `box-shadow 0.2s ease, transform 0.2s ease` |
| ds-card-hover | Box-shadow lift + translateY(-1px) | `box-shadow 0.2s ease, transform 0.2s ease` |
| ds-table-hover row | Green tint background | `background 0.15s ease` |
| ds-table-ledger row | Green tint background + green left inset border | `background 0.15s ease, box-shadow 0.15s ease` |

### Mobile/Touch Guard

All CSS hover rules are wrapped in `@media (hover: hover) and (pointer: fine) {}` to prevent stuck hover states on touch devices. Never use viewport width alone to scope hover effects — some devices are touch+mouse hybrids.

### Active Press

```css
.ds-btn:active { transform: scale(0.97); }
```

Active state uses `transform: scale(0.97)` at 100ms for a subtle press-in effect. This is NOT wrapped in the hover media query since `:active` works naturally on touch.

## Sidebar Group Hover-Preview (Desktop)

### Behavior
- Hover over a collapsed group header → after 200ms dwell → group previews expanded
- Move mouse away from the group → after 300ms → collapses back to original state
- The preview uses a separate `previewOpen` state that never writes to localStorage
- Click during preview commits to a persisted state (saves to localStorage)
- Hover on an already-open group has no effect

### Implementation
- Alpine.js `x-data` with `previewOpen`, `_ht` (hover timer), `_lt` (leave timer), `_ch` (capability flag) properties
- `_ch` set from `window.matchMedia('(hover: hover) and (pointer: fine)').matches` in `init()`
- `x-on:mouseenter` + `x-on:mouseleave` on the `<li>` element with setTimeout/clearTimeout
- `x-show="open || previewOpen"` on the group's `<ul>`
- Click handler clears both timers before evaluating state to prevent race conditions

---

## Universal Search Pattern (ds-search-bar)

### Overview
All data-table views share a consistent multi-field search pattern. Replace per-view ad-hoc searches with this standard approach.

### Placement
- Search inputs sit **inside a ds-card above the table** (not in the page header)
- Cards use `flex flex-wrap items-end gap-3` layout
- Each field has a `ds-label` above and a `ds-form-input` below
- The "Reset" link sits in the page header (top-right), not inside the search card

### Input Fields
- **Name/Fullname search**: `<input type="text" v-model="fullname" ...>` — applies to People-based entities (students, parents, staff)
- **ID/Code search**: `<input type="text" v-model="code" ...>` — applies to code-based entities (subjects, library cards)
- **Class filter**: `<input type="text" v-model="student_name" ...>` — for parent-child relationships only

### Interaction Design
| Aspect | Standard |
|---|---|
| Trigger | `@input="onFilterChange"` — fires on every keystroke |
| Debounce | **300ms** via `_.debounce` from lodash (aliased as `_` in Vue 2 context) |
| Page reset | `onFilterChange` sets `this.page = 1` before fetching |
| Server request | Axios GET with field params + `&page=N` appended |
| Empty state | `<div class="ds-table-empty">` with "No results for your search" message |

### Debounce Implementation (Vue 2)
```javascript
// In data() — store the debounced function, not the timer
data() {
    return {
        fullname: '',
        code: '',
        // ...
        debouncedSearch: null,
    }
},

// In created() — ONE debounced function attached to the instance
created() {
    this.debouncedSearch = _.debounce(function() {
        this.page = 1;
        this.getData();
    }, 300);
    this.getData();
},

// onFilterChange just triggers the debounced function
methods: {
    onFilterChange() {
        this.debouncedSearch();
    },
}
```

For Blade views (PHP), search is via form submission (GET), not live debounce — the input triggers a full page reload.

### Empty State
```html
<div v-if="!rows.length && !isLoading" class="ds-table-empty">
    <svg class="ds-empty-state-icon">...</svg>
    <p class="ds-empty-state-title">No results found</p>
    <p class="ds-empty-state-desc">Try adjusting your search.</p>
</div>
```

### Field-appropriate targets per entity
| Entity | Search field 1 | Search field 2 | Search field 3 |
|---|---|---|---|
| Students | fullname | standard/class | — |
| Parents | fullname | mobile_no | student_name |
| Staff | fullname | — | — |
| Subjects | name | code | — |
| Classes/Standards | name | — | — |
| Sections | name | — | — |
| Discipline | student_name | teacher_name | type |
| Homework | class | subject | description |
| Library Books | title | author | isbn |
| Feedbacks | parent_name | category | — |

### Mobile
- The search card collapses to a single-column stack on mobile (flex-wrap handles this naturally)
- No horizontal scroll on the search inputs — each input gets `min-w-[200px]` and wraps to full width below 640px
- The table beneath uses the `.ds-table-card-mobile` stacked-card pattern (see mobile data-display section)

### Table Header Pattern (for `<x-table>`)
When using the Blade `<x-table>` component, pass headers as an array:
```blade
@php $headers = ['Subject Name', 'Class', 'Subject Code', 'Type', 'Actions']; @endphp
<x-table :headers="$headers" hover>
    @foreach($subjects as $subject)
    <tr>
        <td>{{ $subject->name }}</td>
        <td>{{ $subject->section->name ?? '-' }}</td>
        <td><code>{{ $subject->code }}</code></td>
        <td>{{ $subject->type ?? '-' }}</td>
        <td>@include('admin.subject.actions', ['subject' => $subject])</td>
    </tr>
    @endforeach
</x-table>
```

### Mobile Stacked-Card Pattern (`.ds-table-card-mobile`)
See the CSS in `dashboard-refresh.css`:
- `<thead>` hidden via `position: absolute; clip: rect(0,0,0,0)` at ≤767px
- Each `<tr>` renders as a block card (white bg, 10px radius, 14px padding, 10px bottom margin)
- Each `<td>` renders as a flex row with `data-label` pseudo-element label in uppercase
- Every `<td>` must include `data-label="Column Name"` for the mobile card layout to work
