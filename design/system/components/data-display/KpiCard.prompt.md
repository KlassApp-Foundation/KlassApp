The dashboard metric tile — four to six of these in an `auto-fit` grid open every role dashboard.

```jsx
<div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: 16 }}>
  <KpiCard icon="users" value="1,284" label="Total Students" color="blue" link="/reception/students" />
  <KpiCard icon="dollar" value="UGX 18.4M" label="Fees Collected" color="amber" />
  <KpiCard icon="whatsapp" value="1,102" label="WhatsApp Linked" color="green" />
</div>
```

## Props

- `icon` — glyph keys share aliases: `classes`/`door`, `exam`/`calendar`, `whatsapp`/`message`, `book`/`library`, `bell`/`notice`, `dollar`/`money`, `check`/`tasks`. Unknown keys render an info circle.
- `value` — defaults to an em dash; that is the deliberate pre-sync empty state, so leave it rather than showing `0`.
- `color` — tints the 44px icon chip only (10% fill, solid stroke). The value is always `--d-dark`.
- `link` — makes the tile an anchor; hover then lifts it 1px.

Money is written `UGX 18.4M` — currency code first, no symbol.
