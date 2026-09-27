# Teacher Dashboard UI Refactor Plan

## Current State Analysis

**URL:** `/teacher/dashboard`  
**Controller:** `app/Http/Controllers/Teacher/DashboardController.php`  
**View:** `resources/views/teacher/dashboard/dashboard.blade.php` (163 lines)  
**Layout:** `layouts/teacher/layout.blade.php` → extends `layouts/app.blade.php`  
**Sidebar:** `layouts/teacher/sidebar.blade.php` → uses shared `partials/sidebar-menu.blade.php` with `role => 'teacher'`

**Data source:** `DashboardController` calls `$this->teacherDashboard($school_id, $teacher_id)` from `app/Traits/Dashboard.php:385` which returns:
- `activitylog` — 5 recent activity entries
- `subject` — teacher's subject/class assignments
- `timetable` — today's schedule grouped by class
- `noticeboard` — 5 recent notices
- `upcomingExam` — upcoming exam schedule
- `whatsapp` — `{ totalLinked, messagesThisMonth }`
- `myStudents` — count of students in teacher's classes
- `myClasses` — count of class assignments
- `marksToEnter` — exams not yet entered/submitted
- `marksReopened` — submissions reopened by admin
- `marksAttention` — `marksToEnter + marksReopened`

**New data needed for additional sections:**
- `pendingApprovals` — leave requests, homework submissions, etc. awaiting teacher action
- `upcomingDeadlines` — assignment due dates, exam dates, etc. sorted by date
- `teacherName` — for personalized greeting (already available via `Auth::user()->name`)

**Existing design system components already in use:**
- `<x-ds-kpi-card>` — with icon, value, label, color, link, tone, direction, spark support
- `<x-ds-card>` / `.ds-card` — with padding and shadow variants
- `.dashboard-kpi-grid` — responsive KPI grid
- `.dashboard-shell--teacher` — page wrapper
- `.ds-badge` — status badges
- `.ds-btn` — button styles

**Legacy task views** (`task/todaytask.blade.php`, `task/overduetask.blade.php`, `task/upcomingtask.blade.php`) use raw checkboxes, inline SVG toggles, and vanilla JS — these are **not used** by the current dashboard.blade.php but still exist in the codebase.

---

## Refactor Plan

### Phase 1: Information Architecture & Layout

**Goal:** Establish a clean, scannable layout that surfaces what a teacher needs on arrival.

1. **Header section** — personalized greeting with teacher's name (e.g., "Good morning, Ms. Smith") based on time of day
2. **KPI row** — 5 cards (already in place, minor polish):
   - My Students (link to classes)
   - My Classes
   - Upcoming Exams (link to exams)
   - WhatsApp Linked
   - Marks needing attention (link to marks entry)
3. **Quick-action bar** — row of shortcut buttons for common tasks:
   - Take Attendance
   - Enter Marks
   - Post Homework
4. **Marks entry banner** — keep existing card with CTA to marks
5. **Empty-state warning** — keep existing "no classes assigned" alert
6. **Main content grid** (2-col on desktop):
   - Left: Today's Schedule (timetable) — with current period highlighted
   - Right: Notice Board
7. **Three activity sections** (full-width below the grid):
   - Recent Activity
   - Pending Approvals
   - Upcoming Deadlines

**Action items:**
- [x] Audit current layout at 375 / 768 / 1280 viewports — identify overflow, cramped cards, misaligned grid
- [x] Ensure KPI grid is responsive (1-col mobile → 2-col tablet → 5-col desktop, or 3+2)
- [x] Add `data-testid` attributes for E2E testing on key sections
- [x] Verify Tailwind v4 classes are applied (not just custom CSS)
- [x] Implement personalized greeting with teacher's name
- [x] Build quick-action bar with shortcut buttons
- [x] Add Pending Approvals section (requires new data from controller)
- [x] Add Upcoming Deadlines section (requires new data from controller)

### Phase 2: Component Polish & Consistency

**Goal:** Align every element with the design system; remove any remaining legacy patterns.

**Timetable card:**
- [x] Show class name as a clear sub-heading per group
- [x] Display period badge, subject name, time range
- [x] Highlight current period based on time of day (subtle background tint or left border)
- [x] Empty state: friendly illustration + "No classes scheduled for today" + link to full timetable
- [x] Add a "View full timetable" link in card header

**Notice Board card:**
- [x] Display notice title as badge, publish date, type badge
- [x] Sanitize/truncate description (`{!! !!}` is used — consider `Str::limit()` + `strip_tags()` for safety)
- [x] Show author with avatar/icon
- [x] Empty state: "No notices published yet" + link to notices page
- [x] Add a "View all notices" link in card header

**Recent Activity card:**
- [x] Icon per activity type (currently all same info icon)
- [x] Activity description + relative timestamp
- [x] Empty state: "No recent activity"
- [ ] Consider pagination or "View all" link if activity log grows

**Pending Approvals card:**
- [x] Show approval requests (leave requests, homework submissions, etc.)
- [ ] Approve/Reject action buttons inline
- [x] Empty state: "No pending approvals"
- [ ] Link to full approvals page

**Upcoming Deadlines card:**
- [x] Show deadlines (assignment due dates, exam dates, etc.)
- [x] Sort by date, show days remaining
- [x] Empty state: "No upcoming deadlines"
- [ ] Link to full deadlines/calendar page

**KPI cards:**
- [x] Add semantic `tone` prop where appropriate (e.g., `marksAttention` should be `warning` tone when > 0, `neutral` when 0)
- [ ] Add `direction` + `delta` if trend data is available
- [x] Ensure all cards have consistent height

### Phase 3: Interactive Elements & Micro-interactions

**Goal:** Make the dashboard feel alive without sacrificing performance.

- [ ] KPI cards: subtle hover lift (already via `.ds-card-hover` if applied)
- [ ] Timetable: highlight current period based on time of day
- [ ] Notice Board: auto-refresh or manual refresh button (optional, low priority)
- [ ] Activity log: relative timestamps that update (e.g., "2 min ago") — can be done with Alpine.js
- [ ] Loading skeleton for initial page load (if data is slow)

### Phase 4: Mobile Optimization

**Goal:** The dashboard must be fully usable on a phone (375px) and tablet (768px).

- [ ] KPI grid: stack vertically on mobile, 2-col on tablet
- [ ] Timetable + Notice Board: stack vertically on mobile
- [ ] Font sizes: ensure readability at small sizes
- [ ] Touch targets: all links/buttons at least 44px
- [ ] Test with Playwright at 375 / 414 / 768 / 1280

### Phase 5: Accessibility & Performance

**Goal:** WCAG AA compliance and fast load times.

- [ ] All icons have `aria-hidden` or meaningful `aria-label`
- [ ] Color contrast meets AA (check badge colors, muted text)
- [ ] Keyboard navigation works for all interactive elements
- [ ] Images/icons are inline SVG (no extra HTTP requests)
- [ ] No render-blocking resources introduced

### Phase 6: Testing & Verification

- [x] PHPUnit feature test: `TeacherDashboardTest` — verify page loads, key sections present, data renders (18/18 passing)
- [ ] Playwright E2E: screenshot at 375 / 414 / 768 / 1280 — visual regression check
- [x] Verify marks entry link works
- [x] Verify empty states render correctly for a teacher with no classes
- [x] Cross-school scoping: verify no data leaks between schools (standing rule #13)

---

## Files to Modify

| File | Change |
|------|--------|
| `resources/views/teacher/dashboard/dashboard.blade.php` | Main refactor target |
| `app/Http/Controllers/Teacher/DashboardController.php` | Add data for new sections |
| `app/Traits/Dashboard.php` | Add `pendingApprovals`, `upcomingDeadlines` to `teacherDashboard()` |
| `public/css/dashboard-refresh.css` | Add any new utility classes or component styles |
| `resources/views/components/ds-kpi-card.blade.php` | Minor prop additions if needed |
| `tests/Feature/Teacher/DashboardTest.php` | New test file |

## Files to Create

| File | Purpose |
|------|---------|
| `tests/Feature/Teacher/DashboardTest.php` | Feature test for teacher dashboard |

## Files to Remove (if confirmed unused)

| File | Reason |
|------|--------|
| `resources/views/teacher/dashboard/task.blade.php` | Legacy task view, not used by current dashboard |
| `resources/views/teacher/dashboard/task/todaytask.blade.php` | Legacy |
| `resources/views/teacher/dashboard/task/overduetask.blade.php` | Legacy |
| `resources/views/teacher/dashboard/task/upcomingtask.blade.php` | Legacy |

> **Note:** Before deleting legacy task views, grep for any remaining references to them in routes, controllers, or other views.

---

## Design Principles

1. **Mobile-first** — design for 375px first, scale up
2. **Design system first** — use `ds-*` components and Tailwind utilities, not ad-hoc CSS
3. **School-scoped** — all data queries must remain scoped by `school_id` (standing rule #13)
4. **Real evidence** — verify with actual browser screenshots, not just code review
5. **Small atomic PRs** — ship each phase as a separate PR for easy review and rollback

---

## Resolved Design Decisions

1. **Greeting with teacher's name** — YES. Show a personalized greeting (e.g., "Good morning, Ms. Smith") based on time of day.
2. **Quick-action bar** — YES. Add a row of shortcut buttons (e.g., "Take Attendance", "Enter Marks", "Post Homework") for common tasks.
3. **Activity sections** — Keep ALL THREE: Recent Activity, Pending Approvals, and Upcoming Deadlines. These are distinct enough to warrant separate sections.
4. **Current period highlighted** — YES. Highlight the current period in the timetable based on time of day.
