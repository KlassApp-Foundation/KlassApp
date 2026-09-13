---
category: Actions
---

# Button

The KlassApp action button. Blade equivalent: `<x-button>` in
`resources/views/components/button.blade.php`.

Renders a `<button>` by default, or an `<a>` when `href` is set.

```jsx
<Button variant="primary" type="submit">Save Changes</Button>
<Button variant="ghost" size="sm" href="/admin/standards">← Back</Button>
```

## Variants

`primary` (default) · `success` · `danger` · `warning` · `outline` · `ghost`

In the app, `primary` is the form-submit action, `success` is the money/confirm
action (recording a payment), `ghost` is the back link, and `outline` is cancel.

## Sizes

`sm` · `md` (default) · `lg`

**`.ds-btn-md` has no rule in `dashboard-refresh.css`.** The default size
therefore renders at the `.ds-btn` base size. This is faithful to the Blade
source, not a porting bug — use `sm` or `lg` when you need a deliberate size.

## Disabled

On a `<button>` this sets `disabled`. On an `<a>` it sets `aria-disabled="true"`
and `tabindex="-1"` — the link is still in the DOM and still has its `href`.
