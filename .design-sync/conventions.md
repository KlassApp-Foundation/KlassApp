# Building with the KlassApp design system

KlassApp is a WhatsApp-first school management platform for East African
schools. These components are React ports of the app's Laravel Blade
components; they emit the identical CSS class contract, so anything you build
here maps back to Blade 1:1.

## No provider, no wrapper — but you must not invent classes

There is no theme provider and no required root wrapper. Components style
themselves entirely from the global stylesheet, which is already loaded.

**The one rule that will silently break your work: Tailwind utility classes do
not exist here.** `mt-4`, `flex`, `grid`, `gap-4`, `p-4`, `text-sm`,
`text-center`, `rounded`, `w-6` — none of them ship in this design system's
CSS. The KlassApp app itself loads Tailwind separately, so you will see those
classes in the Blade source; they are **not** available to anything you build
with this bundle. A `className="mt-4 flex"` here is a no-op.

For your own layout glue, use **inline styles with the design tokens** below.
For anything the DS already covers, use its `ds-*` classes.

```jsx
// ✅ right — DS component + tokens for the surrounding layout
<div style={{ display: 'grid', gap: 16, background: 'var(--d-canvas)', padding: 24 }}>
  <KpiCard icon="users" value="1,284" label="Total Students" color="blue" />
</div>

// ❌ wrong — these classes resolve to nothing
<div className="grid gap-4 bg-gray-50 p-6">…</div>
```

## Design tokens (28, all `--d-*`)

Use these rather than hex literals. Defined on `:root`:

| Group | Tokens |
|---|---|
| Brand | `--d-blue` `--d-green` `--d-amber` `--d-red` |
| Accent (green is the primary CTA) | `--d-accent` `--d-accent-lt` `--d-accent-dk` `--d-text-on-accent` |
| Surface | `--d-canvas` `--d-surface` `--d-shell` `--d-white` |
| Text | `--d-text` `--d-text-secondary` `--d-muted` |
| Dark (sidebar) | `--d-dark` `--d-dark-surface` `--d-dark-fg` `--d-dark-border` `--d-dark-active` |
| Border / ring | `--d-border` `--d-border-strong` `--d-ring` `--d-focus-ring` |
| State | `--d-disabled` `--d-disabled-bg` `--d-touch-target-min` `--d-transition-normal` |

**Primary actions are green, not blue.** `--d-accent` resolves to `--d-green`;
`.ds-btn-primary` renders green. Blue (`--d-blue`) is an informational accent.

## Typography

Sora for headings, DM Sans for body — both loaded from Google Fonts by the
stylesheet, and `body` is already set to DM Sans. Do not introduce another
family.

## Class vocabulary beyond the components

These ship and are safe to use directly on your own markup:

| Family | Classes |
|---|---|
| Page header | `ds-page-head` `ds-page-head-title` `ds-page-head-sub` |
| Shell | `dashboard-shell` (+ role modifiers e.g. `dashboard-shell--admin`, `--teacher`, `--student`, `--accountant`, `--library`, `--reception`, `--alumni`, `--stock`, `--superadmin`) |
| Top fold | `dashboard-topfold` (+ `--teacher` `--student` `--accountant` `--superadmin`) |
| Status dot | `ds-dot` + `ds-dot-green` `ds-dot-blue` `ds-dot-amber` `ds-dot-red` `ds-dot-gray` |
| Empty state | `ds-empty-state` `ds-empty-state-icon` `ds-empty-state-title` `ds-empty-state-desc` |
| Save indicator | `ds-save-indicator` + `--saving` `--saved` `--error` |
| Table internals | `ds-table-wrap` `ds-table-ledger` `dt-comfortable` `dt-compact` `ds-table-striped` `ds-table-hover` `dt-cell-num` `dt-cell-check` `dt-cell-badge` `dt-pagination` |
| Toshi (AI assistant) | `toshi-panel` `toshi-pill` `toshi-header` `toshi-composer` `toshi-chip` `toshi-plan-card` `toshi-confirm-card` `toshi-stat-card` `toshi-progress-dots` |

`ds-table-striped` / `ds-table-hover` work on raw tables but the `Table`
component does **not** emit them — see its doc.

## Two quirks worth knowing

- **`.ds-btn-md` has no rule.** It is `Button`'s default size, so default
  buttons render at the `.ds-btn` base size. Pass `size="sm"` or `size="lg"`
  when you want a deliberate size.
- **`Table`'s `striped` and `hover` props do nothing.** They are accepted for
  API parity but emit no class.

## Where the truth is

Read these before styling anything — they beat this summary:

- `_ds/<folder>/styles.css` → `_ds_bundle.css` — the complete stylesheet
  (364 class selectors), which is the app's real `dashboard-refresh.css`
- `components/<group>/<Name>/<Name>.prompt.md` — per-component API and house
  patterns
- `components/<group>/<Name>/<Name>.d.ts` — the exact props contract

## A representative build

```jsx
<div className="dashboard-shell dashboard-shell--admin">
  <div className="ds-page-head">
    <div>
      <h1 className="ds-page-head-title">Fee Payments</h1>
      <p className="ds-page-head-sub">Term 2 2026 · Senior 2</p>
    </div>
    <Button variant="success" size="sm" href="/admin/fees/create">
      Record Payment
    </Button>
  </div>

  <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(160px, 1fr))', gap: 16, marginBottom: 24 }}>
    <KpiCard icon="dollar" value="UGX 18.4M" label="Collected" color="green" />
    <KpiCard icon="users" value="1,284" label="Students" color="blue" />
  </div>

  <Card title="Recent payments" padding="none">
    <Table headers={['Student', 'Amount', 'Method', 'Status']}>
      <tr>
        <td>Nakato Sarah</td>
        <td>UGX 450,000</td>
        <td>SchoolPay</td>
        <td><Badge variant="paid">Paid</Badge></td>
      </tr>
    </Table>
  </Card>
</div>
```
