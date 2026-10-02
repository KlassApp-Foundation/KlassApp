The KlassApp ledger table — every roster, fee list and attendance register is one of these.

```jsx
<Table headers={['#', 'Student', 'Amount', 'Method', 'Status']} sortable>
  <tr>
    <td data-label="#">1</td>
    <td data-label="Student">Nakato Sarah</td>
    <td className="dt-cell-num" data-label="Amount">UGX 450,000</td>
    <td data-label="Method">SchoolPay</td>
    <td className="dt-cell-badge" data-label="Status"><Badge variant="paid">Paid</Badge></td>
  </tr>
</Table>
```

## Props

`headers` · `density` (`comfortable` default / `compact`) · `selectable` · `sortable` · `cardMobile` (default true).

## Things to know

- It emits `.ds-table-ledger`, not `.ds-table`. Header rule is a 2px `--d-blue` underline; row hover is a 4% green wash with an inset 3px green rail.
- **`striped` and `hover` do nothing** — declared upstream, never referenced. Don't pass them expecting an effect.
- Put `data-label="Column"` on every `<td>`: at ≤767px rows restack as cards and the label becomes the row's field name.
- Right-align numbers with `.dt-cell-num` (tabular figures); wrap status pills in `.dt-cell-badge`.
- For a screen that needs two tables in one shell (active + archived), skip the component and use raw `.ds-table-wrap` / `.ds-table-ledger`.
