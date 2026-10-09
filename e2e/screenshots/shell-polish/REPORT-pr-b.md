# SHELL POLISH — PR B Report (laptop shell + dashboard, items 4–12)

**Branch:** `shell-polish/laptop` @ `7bd584b3` (stacked on PR A `02fc1ce6` / `shell-polish/phone`)
**PR:** #1044 — https://github.com/KlassApp-Foundation/KlassApp/pull/1044 (MERGEABLE, base `shell-polish/phone`)
**Stacked PR A:** #1043 — https://github.com/KlassApp-Foundation/KlassApp/pull/1043
**Date:** 2026-10-09
**Harness:** `e2e/shell-polish-verify.cjs` — **zero failures** across chromium+webkit at 375/390/768/1280/1440 (`e2e/screenshots/shell-polish/report.json`, `failures: []`)
**PHPUnit:** 61 passed (473 assertions) — DashboardV2* + Navigation suites

---

## Items 4–12 — probe evidence

| # | Item | Probe check | Result | Detail |
|---|------|-------------|--------|--------|
| 4 | Laptop sidebar ~40px rows, icon-left | `sidebar-row-40-to-48` | **ok:true** | rowHeight=44 (chromium + webkit @1280); `sidebar-present` width=248, hasCard=true; `sidebar-never-moves` left=0 |
| 5 | Greeting + "{school} · {term}, {year}" one line | `dashboard-subtitle` | **ok:true** | "Demo Junior School · Term III, 2026" (verified already satisfied in PR A baseline; no change needed) |
| 6 | Compact quick actions + KPI tiles | live probe @1280 `?v2=1` | **ok** | kpiCount=5, kpiHeights all **144** (was 155), min-height 0 + padding 12px 14px; qaCols=5 (2-col ≤1023), qaItems=5 |
| 7 | Fees chart compact labels, muted future months | code + live | **ok** | `formatCompact()` (M/K suffixes) + `dsValueLabels` plugin in `chart.blade.php`; service sets `'future' => $cursor->isFuture()` (line 393); live `futureMonthMuted` signal present; `hasFees:true` |
| 8 | Seed 8 weeks attendance + truthful label | live probe | **ok** | body shows **"Last 8 weeks"** (label derives from `max(1,count(attendance_weeks))`); 3 seeders `count($days) < 40`; local backfill → 6600 rows school-4 |
| 9 | Latest exam WITH marks | service grep | **ok** | both queries use `whereHas('marks', fn($q)=>$q->where('school_id',$sid))` (lines 227 & 327); Marks has no SoftDeletes so plain whereHas safe; live `hasAttendance/hasFees` present, exam line "Performance by class" renders |
| 10 | Activity feed per-event icons | live probe | **ok** | activityIcons=1/1 (per-type SVG: wallet/calendar-check/clipboard-list/user-plus); not generic clock |
| 11 | Chart cards sized to content | live probe | **ok** | `.dv2-grid2` computed `align-items: start` (no forced equal height) |
| 12 | Toshi triangle hidden <1280, preview only | `toshi-triangle-hidden-below-1280` | **ok:true** | @375: `{present:true, visible:false}`; @1280: `{present:true, visible:true}` (via `data-toshi-mode` + `@media (max-width:1279px)` rule) |

All harness checks across every engine/viewport: **`failures: []`** (22 checks @375, 11 @1280 per engine; axe contrast clean — all baseline #94A3B8/#166534 violations fixed to #64748B/#14532D).

---

## Verification summary
- **Harness:** green (exit 0), chromium + WebKit, 375/390/768/1280/1440, axe clean.
- **PHPUnit:** 61 passed for PR B-touched suites.
- **Diff:** 15 files, +518/−44 (stacked on PR A; PR B-only files: dashboard-v2.css, DashboardV2DataService, dashboard_v2.blade.php, 3 seeders, chart.blade.php, toshi-embed, navigation — + dashboard-refresh.css/custom.js/layout/sidebar carry PR A changes already in #1043).
- **Screenshots:** `e2e/screenshots/shell-polish/` (harness) + `concept/` frames captured.

## Deviations (report-required)
1. **Evidence from local `127.0.0.1:8000`, not staging.** Staging `test.klassapp.xyz` unreachable + no Laravel Cloud token this session — side-by-side uses local. Concept frames rendered headlessly from `design/system/concepts/admin-mvp/app.html` (URL-driven SPA), not static image files (`screens/` empty).
2. **768px laptop checks gated to ≥1024px.** WebKit classic scrollbars shrink a 768px window's CSS viewport to ~753px < Tailwind `md`, so WebKit correctly shows the phone shell at 768 while Chromium shows desktop — both correct; harness gates sidebar/popover-laptop checks accordingly.
3. **Items 6/7/8/9/10/11 evidence via live DOM probe** (no dedicated harness check IDs); item 4/5/12 via harness check IDs. Combined for full item coverage.

## Global rules honored
- Currency from school settings (`formatCompact` formats, no hard-coded symbol) ✓
- No shipped phone numbers (concepts' numbers are examples only) ✓
- Gender: Female / Male / Not specified; "Sex" not used ✓

## Status
- PR B **open + MERGEABLE**, CI harness + PHPUnit green.
- Merge policy: merge PR A (#1043) first, then PR B (#1044) — only when CI green and no deploy running.
- `evidence/shell-polish` branch + knowledge.md session log still pending (next steps).
