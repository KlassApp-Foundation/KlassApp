# STAINGING shell verification — concept | staging pairs (2026-10-10)

Live side captured from **staging** (`test.klassapp.xyz`, deploy `e8778feb`) after
the K55/K63 correctives (#1049 icon-left rows + #1050 groups default open).

| Pair | Viewport / engine | Shows |
|---|---|---|
| `pairs/sidebar-1280-chromium.png`, `-webkit` | 1280 | compact rows 44px, icon LEFT (svg x26 → label x60, same line), all 5 groups open with items |
| `pairs/sidebar-1440-chromium.png`, `-webkit` | 1440 | same |
| `pairs/popover-1280-chromium.png`, `-webkit` | 1280 | account popover fully inside the viewport (no clipping) |
| `pairs/drawer-375-webkit.png` | 375 / webkit | light drawer, 48px rows, items visible |

Metrics: `e2e/screenshots/staging-shell-verify-final/results.json` — all combos:
5/5 group lists open, 23 visible rows @44px, icon-left on one line; popover
in-viewport; drawer 23 rows @48px.
