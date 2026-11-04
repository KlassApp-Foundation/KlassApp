# KlassApp demo designs — November 2026

The approved designs for the Innovation Week demo. **These boards are the reference for the demo build**: when a screen here and an older design or an existing page disagree, build to these.

`project/` is the design source exactly as exported — do not edit it. It holds one `.dc.html` file per board, `canvas.json` with the eleven pages and every board's title/page/position, and `ds/klassapp/tokens.json` (the design tokens).

## The eleven pages

| Page | What it is |
|---|---|
| **1 · Exam sets** | New set (admin, desktop), the set page, sets by term; phone: new set, set list, set page; empty/loading/error |
| **2 · Marks entry** | Teacher's exams to mark, the marks grid, paste-or-upload marks; phone grid and paste; empty/loading/error/returned |
| **3 · Approval and release** | Approvals queue (admin, class head), combined mark sheet, Marks Release; phone approvals, stacked-card mark sheet, filters-as-a-sheet, release; empty/loading/error |
| **4 · Report cards** | Report cards · class (admin, class head) plus sample cards at Nursery, Primary, O level and A level; phone; empty/loading/error |
| **5 · Student profile** | Overview (admin), the actions board (record a payment, add marks, record attendance), phone, and the payment-as-bottom-sheet; empty/loading/error |
| **6 · Fees** | Fee structure (admin), balances and record a payment, phone, empty/loading/error |
| **7 · Classes** | Classes (admin), phone, empty/loading/error |
| **8 · Teacher dashboard** | Desktop, phone, empty/loading/error |
| **9 · Parent and student** | Parent dashboard, marks, attendance and fees pages, parent and student phone dashboards, parent marks phone, empty/loading/error |
| **10 · Admin dashboard** | Admin dashboard with the demo additions, phone, empty/loading/error |
| **Shared parts** | Sidebar, top bar, phone top bar, marks lifecycle strip, account menu (all roles) |

## Boards and states

Every screen has three kinds of board: **desktop**, **phone (375 px)**, and an **empty / loading / error** board. Two shared rules come from them:

- Below 768 px every filter row collapses into the **Filters bottom sheet** (page 3, `FilterSheet.dc.html`).
- The account card and bell stay in the sidebar footer on desktop; on phones the avatar sits top right (as shipped in #1265).

## How to view a board

Open any `.dc.html` file directly in a browser:

```bash
open docs/design/2026-11-demo/project/StudentHome.dc.html
```

`canvas.json` is the map: each board's `title`, `page` and position. `ds/klassapp/tokens.json` holds the colour, type and spacing tokens the boards use.
