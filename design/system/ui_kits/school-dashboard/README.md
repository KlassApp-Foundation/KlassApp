# UI kit — School dashboard

Recreation of the KlassApp staff dashboard from the app's own class vocabulary
(`dashboard-shell`, `ds-page-head`, `ds-kpi-card`, `ds-table-ledger`,
`ds-grid-marks`, `ds-reminder-banner`) plus the nine bundled components.

## Screens

| File | Screen |
|---|---|
| `DashboardHome.jsx` | Admin home — greeting, live badge, KPI fold, weekly collection chart, connected tools, two panels |
| `StudentsScreen.jsx` | Student roster — search, class filter, selectable/sortable ledger, pagination, empty state |
| `FeesScreen.jsx` | Fee payments — KPI row, inline "Record payment" form, live ledger, save indicator |
| `ExamsScreen.jsx` | Marks grid (`.ds-grid-marks`) with frozen name columns, plus the amber reminder banner |
| `Sidebar.jsx` | Cream sidebar with collapsible groups and the green active state |
| `App.jsx` | Shell, top bar, routing between screens |

## Interactions

Click any sidebar item. On **Fee payments**, "Record payment" opens the inline
form — save a payment and the row appears at the top of the ledger with the
"Payment recorded" save indicator. On **Students**, typing filters the ledger
down to the empty state.

## Fidelity notes

- The sidebar is cream (`#FFFFFC`), not dark: the source CSS neutralises the legacy
  dark `.sidebar` background and comments that the inner sidebar supplies a cream
  fill. The dark tokens (`--d-dark-*`) remain for the dark-shell variants.
- Nav items not recreated (Teachers, Classes, Statements, Notices, WhatsApp log)
  render a blank state saying so — the bundle ships their styling but no markup,
  so nothing was invented.
- The weekly collection chart is bars built from tokens; the source has no chart
  component in the bundle.
