---
category: Data Display
---

# Table

The KlassApp ledger table. Blade equivalent: `<x-table>` in
`resources/views/components/table.blade.php`.

Wraps a `<table>` in `.ds-table-wrap` and renders your `<tr>` children into
`<tbody>`.

```jsx
<Table headers={['#', 'Student', 'Amount', 'Method', 'Paid On']}>
  <tr><td>1</td><td>Nakato Sarah</td><td>UGX 450,000</td><td>SchoolPay</td><td>12 Sep 2026</td></tr>
</Table>
```

## Props

- `headers`: `string[]` — omit or pass `[]` for no `<thead>`
- `density`: `comfortable` (default) · `compact`
- `selectable`: prepends a checkbox header cell
- `sortable`: appends a sort arrow to every header cell
- `cardMobile`: default `true` — stacked-card layout at ≤767px

## Two things to know

**It emits `.ds-table-ledger`, not `.ds-table`.** `DESIGN_SYSTEM.md` documents
the latter; the component has moved on. If you are writing raw markup to match,
use `.ds-table-ledger` + `.dt-comfortable` / `.dt-compact`.

**`striped` and `hover` do nothing.** Both are declared in the Blade `@props`
but never referenced in the template, so they emit no class. They are accepted
for API parity only. Several production views pass them expecting an effect —
do not copy that. Row hover comes from `.ds-table-ledger` itself.

## Complex tables

For a view that needs split content (active + archived in one screen), skip the
component and use the raw `.ds-table-wrap` / `.ds-table-ledger` classes.
