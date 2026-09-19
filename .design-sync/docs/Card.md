---
category: Surfaces
---

# Card

The standard KlassApp surface container. Blade equivalent: `<x-card>` in
`resources/views/components/card.blade.php`.

```jsx
<Card title="Fee Balance" padding="none" className="parent-panel">
  …
</Card>
```

## Props

- `padding`: `default` · `sm` · `none` · `lg`
- `shadow`: `sm` (default) · `md` · `lg` · `none` — `none` emits no shadow class
- `hover`: adds `.ds-card-hover` (lift on hover); use for clickable cards
- `title`: renders `<h3 class="ds-card-title">` above the content

## House patterns

The parent-facing dashboard panels use `padding="none"` plus a
`className="parent-panel"` and let the inner content own its spacing. Centred
empty states use `padding="lg" className="text-center"`.
