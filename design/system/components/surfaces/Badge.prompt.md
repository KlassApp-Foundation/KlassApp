The KlassApp status pill — use it for every state word in a table, list or detail header.

```jsx
<Badge variant="paid">Paid</Badge>
<Badge variant="pending" size="md">Awaiting approval</Badge>
```

## Variants

`paid` / `approved` / `active` share the green treatment (#e8f5e9 on #2e7d32); `unpaid` / `rejected` are red; `warning` is amber on #fff8e1; `pending` / `info` / `inactive` are the warm neutral #f0eee6. Unknown values silently fall back to `info`.

## Sizes

`sm` (default, 3px/10px, 0.72rem) · `md` (5px/14px, 0.78rem).

Write the label in sentence case — "Paid", "Awaiting approval" — never all-caps, never with an emoji or dot prefix. For a dot indicator use `.ds-dot` + `.ds-dot-green` alongside plain text instead.
