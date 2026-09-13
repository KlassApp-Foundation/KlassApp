---
category: Surfaces
---

# Badge

Status pill. Blade equivalent: `<x-badge>` in
`resources/views/components/badge.blade.php`.

```jsx
<Badge variant="info" size="sm">SchoolPay</Badge>
```

## Variants and their colours

| Variant | Meaning in KlassApp |
|---|---|
| `pending`, `info` | blue — awaiting action, or a payment channel tag |
| `approved`, `paid`, `active` | green — settled / enrolled |
| `rejected`, `unpaid` | red — refused / outstanding |
| `warning` | amber — needs reconciliation |
| `inactive` | slate — archived |

Anything outside that list silently falls back to `info` (the Blade whitelist
behaviour) — so a typo degrades to blue rather than rendering unstyled.

## Sizes

`sm` (default) · `md`

Badges are most often composed into a `Table` status cell, or paired with an
amount on a reconciliation row.
