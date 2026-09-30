The KlassApp action button — use for every clickable action; renders an `<a>` when `href` is set.

```jsx
<Button variant="primary" type="submit">Save Changes</Button>
<Button variant="success" size="sm">Record Payment</Button>
<Button variant="ghost" size="sm" href="/admin/standards">← Back</Button>
```

## Variants

`primary` (default, green) · `success` (green, money/confirm) · `danger` · `warning` · `outline` (cancel) · `ghost` (back link).

**Primary is green, not blue.** `--d-accent` resolves to `--d-green`. Blue is informational only.

## Sizes

`sm` · `md` (default) · `lg`. `.ds-btn-md` has **no rule** in the app stylesheet, so the default renders at the `.ds-btn` base size (8px/18px padding, 0.85rem). Pass `sm` or `lg` when you want a deliberate size. Every button carries `min-height: 44px` for touch.

## Disabled

On a `<button>` this sets `disabled` (50% opacity, `--d-disabled-bg`). On an `<a>` it sets `aria-disabled="true"` and `tabindex="-1"` — the link keeps its `href`.

## States

Hover darkens the fill (`--d-accent-dk`); press is `scale(0.97)`; focus-visible is a 2px blue outline at 2px offset.

- **Loading (proposed):** `loading` prepends a 14px `currentColor` spinner and sets `aria-busy`; the label stays so width never changes.
- **`v2` (proposed):** AA-passing warning fill (`--d-warning`, 5.02:1) and single-dim disabled.
- **`success` is retired** — renders as primary. Don't use it in new work; `--d-success` remains for badges and alerts.
- All sizes render 44px tall (global touch-target rule); `sm` differs only in padding-x and font size.
