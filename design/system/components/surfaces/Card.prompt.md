The KlassApp surface container — white, 14px radius, hairline ring; wrap any panel of dashboard content in one.

```jsx
<Card title="Recent payments" padding="none">
  <Table headers={['Student', 'Amount', 'Status']}>…</Table>
</Card>
```

## Props

- `padding`: `default` (20px) · `sm` (14px) · `lg` (28px) · `none` — use `none` when the card holds a table, so the ledger goes edge to edge.
- `shadow`: `sm` (default, the 1px ring) · `md` · `lg` · `none`.
- `hover`: adds the 1px lift. Only use on cards that are themselves links.
- `title`: renders `<h3 class="ds-card-title">` — Sora 600, 1rem, 12px below.

House rule: elevation is a *ring*, not a drop shadow. Don't reach past `shadow="md"` unless the card floats above other content.
