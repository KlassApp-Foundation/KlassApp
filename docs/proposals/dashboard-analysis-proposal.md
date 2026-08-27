# Dashboard Analysis & Redesign Proposal

**Author:** KlassApp Team (AI-assisted analysis)
**Date:** 2026-08-28
**Status:** Proposed — awaiting team review
**Companion to:** `landing-page-reframe-proposal.md`, `brand-identity-proposal.md`

---

## Executive Summary

KlassApp has **7 role-specific dashboards** (Admin, Teacher, Student, Accountant, Librarian, Receptionist, Alumni) plus a **WhatsApp Delivery Dashboard**. The dashboards are functional but have significant UX inconsistencies, branding gaps, and missed opportunities to showcase the Toshi AI agent.

This proposal audits every dashboard, identifies issues, and recommends a unified redesign aligned with the new "agentic protocol" positioning.

---

## 1. Dashboard Inventory

### 1.1 Role-Based Dashboards

| Dashboard | Route | Layout | Sidebar Color | Status |
|-----------|-------|--------|---------------|--------|
| **Admin** | `/admin/dashboard` | `layouts.admin.layout` | Warm cream (#FFFCF5) | ✅ Most complete |
| **Teacher** | `/teacher/dashboard` | `layouts.teacher.layout` | Purple (dark) | ⚠️ Basic |
| **Student** | `/student/dashboard` | `layouts.student.layout` | Teal (dark) | ⚠️ Basic |
| **Accountant** | `/accountant/dashboard` | `layouts.accountant.layout` | — | ✅ Good |
| **Librarian** | `/library/dashboard` | `layouts.library.layout` | — | ⚠️ Basic |
| **Receptionist** | `/reception/dashboard` | `layouts.reception.layout` | — | ⚠️ Basic |
| **Alumni** | `/alumni/dashboard` | `layouts.alumni.layout` | — | ⚠️ Basic |

### 1.2 Special Dashboards

| Dashboard | Route | Purpose |
|-----------|-------|---------|
| **WhatsApp Delivery** | `/admin/whatsapp/dashboard` | Message delivery stats, failure monitoring |
| **WhatsApp Parents** | `/admin/whatsapp/parents` | Parent WhatsApp link management |
| **Superadmin** | `/superadmin/dashboard` | Platform-level management |

---

## 2. Admin Dashboard — Detailed Analysis

### 2.1 Structure

The admin dashboard (`resources/views/admin/dashboard/dashboard.blade.php`) is the most complete. It includes:

**KPI Cards (Top Row):**
- Students count (green icon)
- Teachers count (blue icon)
- Parents count (amber icon)
- Non-Teaching Staff count (red icon)

**KPI Cards (Second Row):**
- WhatsApp Parents (green icon)
- Messages This Month (blue icon)

**Below the fold:**
- Fee collection overview
- Upcoming events
- Task list
- Birthday/anniversary alerts
- Setup banner (for onboarding)

### 2.2 Strengths

- ✅ **KPI cards with color-coded icons** — good visual hierarchy
- ✅ **WhatsApp integration stats** — shows the multi-channel story
- ✅ **Setup banner** — guides new schools through onboarding
- ✅ **Empty state handling** — shows product demo when setup is incomplete
- ✅ **Responsive grid** — works on mobile

### 2.3 Weaknesses

| Issue | Evidence | Impact |
|-------|----------|--------|
| **No Toshi AI visibility** | The most differentiating feature is invisible on the dashboard | Users don't know Toshi exists |
| **Inconsistent icon system** | Some use inline SVGs, some use `<x-icons.sidebar>` component | Visual inconsistency |
| **No dark mode** | Admin sidebar is cream (#FFFCF5), teacher is dark purple | Inconsistent experience across roles |
| **Generic KPI cards** | Same card pattern for everything — no data visualization | Missed opportunity for charts/graphs |
| **No activity feed** | No real-time stream of what's happening in the school | Admin has to check multiple pages |
| **No connector status** | WhatsApp is shown, but Drive, Slack, Email, SMS are not | Doesn't reflect multi-channel reality |
| **Hardcoded colors** | `style="background: rgba(22,163,74,0.10); color: #16A34A;"` inline | Should use design tokens |
| **Massive SVG inline** | The parent icon SVG is ~5KB of inline code | Should be a component/icon |

---

## 3. Teacher Dashboard — Analysis

### 3.1 Structure

The teacher sidebar (`resources/views/layouts/teacher/menu.blade.php`) includes:
- Dashboard
- Classes
- Timetable
- Attendance
- Exams
- Homework
- Marks
- Students
- Notices
- Events
- Library

### 3.2 Issues

| Issue | Evidence |
|-------|----------|
| **Purple hover states** | `hover:bg-purple-900` — dark purple on dark sidebar = poor contrast |
| **No Toshi integration** | Teacher has no AI assistant visible |
| **Hash-based navigation** | `teacher/dashboard#timetable` — should be proper routes |
| **No lesson plan shortcut** | Lesson plans are a key teacher feature but not in sidebar |
| **No assignment grading shortcut** | Teachers need quick access to pending submissions |

---

## 4. Student Dashboard — Analysis

### 4.1 Structure

The student sidebar (`resources/views/layouts/student/menu.blade.php`) includes:
- Dashboard
- Homework
- Assignments
- Quiz
- Timetable
- Events
- Notices
- Library
- Holidays
- Chats
- Activity

### 4.2 Issues

| Issue | Evidence |
|-------|----------|
| **Teal hover states** | `hover:bg-teal-900` — same contrast issue as teacher |
| **No grades/marks view** | Students can't see their own marks from the sidebar |
| **No report card access** | Report cards are a key student need |
| **No fee balance** | Students (or parents) should see fee status |
| **"Chats" is vague** | Is this Toshi? Internal messaging? WhatsApp? |

---

## 5. Accountant Dashboard — Analysis

### 5.1 Structure

The accountant dashboard (`resources/views/accountant/dashboard.blade.php`) uses a design system component (`<x-ds-kpi-card>`) and includes:
- Fee Categories (KPI)
- Total Fees (KPI)
- Total Students (KPI)
- Pending Tasks (KPI)
- Fee Categories list
- Upcoming Events

### 5.2 Strengths

- ✅ **Uses design system components** (`<x-ds-kpi-card>`) — more consistent
- ✅ **Clean layout** — two-column grid with cards
- ✅ **Empty states** — handles no-data gracefully

### 5.3 Issues

| Issue | Evidence |
|-------|----------|
| **No payment tracking** | Should show collected vs outstanding |
| **No payroll overview** | Payroll is a key accountant feature |
| **No fee collection chart** | Should show trend over time |
| **Currency hardcoded** | "UGX" — should be configurable for global use |

---

## 6. WhatsApp Dashboard — Analysis

### 6.1 Structure

The WhatsApp Delivery Dashboard (`resources/views/admin/whatsapp/dashboard.blade.php`) includes:
- Period filter (24H, 7 Days, 30 Days, 90 Days)
- KPI cards: Messages Sent, Delivery Rate, Failure Rate, Linked Users
- Daily trend chart
- Failure breakdown

### 6.2 Strengths

- ✅ **Period filter** — good data exploration
- ✅ **Delivery rate monitoring** — operational visibility
- ✅ **Failure rate with color coding** — red when > 10%

### 6.3 Issues

| Issue | Evidence |
|-------|----------|
| **WhatsApp-only** | Should be a unified "Connector Dashboard" showing all channels |
| **No Toshi agent metrics** | Should show agent conversations, tool calls, success rate |
| **No parent engagement metrics** | Should show response rates, popular queries |

---

## 7. Sidebar/Navigation Analysis

### 7.1 Current State

| Role | Sidebar BG | Hover | Menu Items |
|------|-----------|-------|------------|
| Admin | Cream (#FFFCF5) | Default | ~40 items in 5 groups |
| Teacher | Dark purple | `hover:bg-purple-900` | 11 items |
| Student | Dark teal | `hover:bg-teal-900` | 11 items |
| Accountant | — | — | — |
| Librarian | — | — | — |
| Receptionist | — | — | — |

### 7.2 Issues

| Issue | Impact |
|-------|--------|
| **5 different sidebar colors** | No visual consistency across roles |
| **Admin has ~40 items** | Overwhelming — needs better grouping |
| **No search** | Can't quickly find a feature |
| **No "Toshi" entry point** | The AI agent has no sidebar presence |
| **No connector status** | Can't see which channels are active |
| **Collapsible groups use Alpine.js** | Good, but no keyboard navigation |
| **Mobile sidebar is separate** | Duplicated code, potential drift |

---

## 8. Proposed Dashboard Redesign

### 8.1 Design Principles

1. **Toshi-first** — The AI agent should be the most prominent element on every dashboard
2. **Connector-aware** — Show which channels are active (WhatsApp, Drive, Slack, Email)
3. **Unified design system** — Same sidebar style, same card components, same typography
4. **Role-appropriate** — Each role sees what matters to them, but the framework is consistent
5. **Dark-mode ready** — Use CSS variables, not hardcoded colors

### 8.2 Proposed Admin Dashboard Layout

```
┌─────────────────────────────────────────────────────────────┐
│  NAVBAR                                                      │
│  [Logo] [Search] [Notifications] [Toshi] [Profile]          │
├──────────┬──────────────────────────────────────────────────┤
│          │                                                   │
│ SIDEBAR  │  ┌─────────────────────────────────────────────┐ │
│          │  │  TOSHI AGENT BAR                            │ │
│ Dashboard│  │  "Good morning! 3 things need your attention"│ │
│          │  │  [View] [Dismiss]                           │ │
│ Academics│  └─────────────────────────────────────────────┘ │
│  Students│                                                   │
│  Parents │  ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐  │
│  Classes │  │ 846  │ │  42  │ │ 312  │ │ 96%  │ │ $12K │  │
│  Subjects│  │Students│ │Teachers│ │Parents│ │Attend│ │ Fees │  │
│  Timetable│  └──────┘ └──────┘ └──────┘ └──────┘ └──────┘  │
│  Attend. │                                                   │
│  Exams   │  ┌──────────────────┐ ┌────────────────────────┐ │
│  Grades  │  │ CONNECTORS       │ │ ACTIVITY FEED          │ │
│  Reports │  │ ✅ WhatsApp  312 │ │ • 3 students absent    │ │
│          │  │ ✅ Drive     ✓   │ │ • Term reports ready   │ │
│ Finance  │  │ ⚠️ Slack     ✗   │ │ • Fee reminder sent    │ │
│  Fees    │  │ ✅ Email     ✓   │ │ • New teacher added    │ │
│  Payroll │  │ ✅ SMS       ✓   │ │                        │ │
│          │  └──────────────────┘ └────────────────────────┘ │
│ Comms    │                                                   │
│  WhatsApp│  ┌──────────────────┐ ┌────────────────────────┐ │
│  Email   │  │ FEE COLLECTION   │ │ UPCOMING EVENTS        │ │
│  SMS     │  │ [Chart]          │ │ • Sports Day - Fri     │ │
│          │  │ Collected: $8.2K │ │ • PTA Meeting - Mon    │ │
│ Library  │  │ Outstanding: $3K │ │ • Exams start - 15th   │ │
│ Transport│  └──────────────────┘ └────────────────────────┘ │
│ Health   │                                                   │
│          │  ┌──────────────────────────────────────────────┐ │
│ Settings │  │ QUICK ACTIONS                                │ │
│          │  │ [Add Student] [Send Notice] [Generate Report]│ │
│          │  └──────────────────────────────────────────────┘ │
└──────────┴──────────────────────────────────────────────────┘
```

### 8.3 Proposed Teacher Dashboard Layout

```
┌─────────────────────────────────────────────────────────────┐
│  TOSHI AGENT BAR                                             │
│  "You have 12 marks to submit and 4 homework to review"     │
│  [Enter Marks] [Review Homework] [Dismiss]                  │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐                      │
│  │  6   │ │  42  │ │  12  │ │  4   │                      │
│  │Classes│ │Students│ │Marks │ │Homework│                      │
│  │      │ │      │ │Due   │ │Due   │                      │
│  └──────┘ └──────┘ └──────┘ └──────┘                      │
│                                                              │
│  ┌──────────────────┐ ┌────────────────────────────────┐   │
│  │ TODAY'S TIMETABLE│ │ CLASS PERFORMANCE              │   │
│  │ 8:00 Math S.3    │ │ [Chart: Average marks by class]│   │
│  │ 9:00 Math S.2    │ │                                │   │
│  │ 10:30 Math S.1   │ │ S.3: 72% avg                   │   │
│  │                  │ │ S.2: 68% avg                   │   │
│  └──────────────────┘ └────────────────────────────────┘   │
│                                                              │
│  ┌──────────────────┐ ┌────────────────────────────────┐   │
│  │ PENDING ACTIONS  │ │ RECENT SUBMISSIONS             │   │
│  │ • Enter S.3 marks│ │ • John submitted homework      │   │
│  │ • Review 4 HW    │ │ • Mary submitted assignment    │   │
│  │ • Approve lesson │ │                                │   │
│  └──────────────────┘ └────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

### 8.4 Proposed Student Dashboard Layout

```
┌─────────────────────────────────────────────────────────────┐
│  TOSHI AGENT BAR                                             │
│  "You have 2 homework due this week. Need help?"            │
│  [View Homework] [Ask Toshi] [Dismiss]                      │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐                      │
│  │ A-   │ │  3rd │ │  2   │ │  0   │                      │
│  │Overall│ │Position│ │HW Due│ │Absent│                      │
│  └──────┘ └──────┘ └──────┘ └──────┘                      │
│                                                              │
│  ┌──────────────────┐ ┌────────────────────────────────┐   │
│  │ MY GRADES        │ │ HOMEWORK                       │   │
│  │ Math: A          │ │ 📕 Math Ch.5 — Due Fri        │   │
│  │ English: A-      │ │ 📗 English Essay — Due Thu     │   │
│  │ Science: B+      │ │                                │   │
│  │ Social: A        │ │ [View All]                     │   │
│  └──────────────────┘ └────────────────────────────────┘   │
│                                                              │
│  ┌──────────────────┐ ┌────────────────────────────────┐   │
│  │ TODAY'S TIMETABLE│ │ FEE BALANCE                    │   │
│  │ 8:00 Math        │ │ Term 2: UGX 150,000            │   │
│  │ 9:00 English     │ │ Paid: UGX 100,000              │   │
│  │ 10:30 Science    │ │ Balance: UGX 50,000            │   │
│  └──────────────────┘ └────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

---

## 9. Toshi Agent Integration

### 9.1 The Toshi Bar

Every dashboard should have a **Toshi Agent Bar** at the top:

```
┌─────────────────────────────────────────────────────────────┐
│  🤖 Toshi                                    [Ask Toshi ▸] │
│  "Good morning! 3 things need your attention today:"        │
│  • 12 marks pending submission (S.3 Mathematics)            │
│  • 4 homework awaiting review                               │
│  • Fee reminder sent to 23 parents                          │
│  [View All] [Dismiss]                                       │
└─────────────────────────────────────────────────────────────┘
```

**Behavior:**
- Shows role-specific proactive insights
- Clickable items deep-link to the relevant page
- "Ask Toshi" opens a chat panel (WhatsApp-style)
- Dismissible, but returns on next login with new insights

### 9.2 Toshi Chat Panel

A slide-out panel (right side) that provides:
- Natural language query ("How is S.3 doing this term?")
- Quick actions ("Send fee reminders to all P.6 parents")
- Contextual help ("What does this button do?")
- Recent conversation history

### 9.3 Toshi in Sidebar

Add a persistent sidebar entry:

```
┌──────────────┐
│ 🤖 Toshi     │  ← Always visible, always accessible
│ Ask anything │
└──────────────┘
```

---

## 10. Connector Status Widget

### 10.1 Proposed Design

A widget visible on admin dashboards showing the status of all connected channels:

```
┌─────────────────────────────────────┐
│ CONNECTORS                          │
│                                     │
│ ✅ WhatsApp    312 parents linked   │
│ ✅ Google Drive Connected           │
│ ⚠️ Slack       Not configured       │
│ ✅ Email       SMTP configured      │
│ ✅ SMS         Twilio connected     │
│ ✅ Firebase    Push notifications   │
│                                     │
│ [Manage Connectors →]               │
└─────────────────────────────────────┘
```

### 10.2 Why This Matters

- Shows the multi-channel reality (not just WhatsApp)
- Gives admins confidence that all channels are working
- Surfaces configuration issues early
- Aligns with the "agentic protocol" positioning

---

## 11. Design System Consolidation

### 11.1 Current State

| Component | Admin | Teacher | Student | Accountant |
|-----------|-------|---------|---------|------------|
| KPI Cards | Inline SVG + hardcoded colors | — | — | `<x-ds-kpi-card>` component |
| Sidebar | Cream bg, Alpine.js groups | Dark purple, flat list | Dark teal, flat list | — |
| Cards | `bg-white custom-shadow` | — | — | `ds-card` class |
| Typography | Mixed | — | — | Sora + CSS vars |
| Colors | Inline styles | Inline styles | Inline styles | CSS variables |

### 11.2 Proposed Design Tokens

```css
:root {
    /* ── Dashboard Tokens ── */
    --d-bg:             #F8FAFC;      /* Page background */
    --d-sidebar-bg:     #0C1528;      /* Sidebar background (dark) */
    --d-sidebar-text:   rgba(255,255,255,0.7);
    --d-sidebar-active: rgba(30,111,217,0.2);
    --d-card-bg:        #FFFFFF;
    --d-card-border:    #E2E8F0;
    --d-card-shadow:    0 1px 3px rgba(0,0,0,0.04);
    --d-text:           #0F172A;
    --d-text-muted:     #64748B;
    --d-text-faint:     #94A3B8;

    /* ── KPI Colors ── */
    --d-blue:           #1E6FD9;
    --d-green:          #22C55E;
    --d-amber:          #D97706;
    --d-red:            #DC2626;
    --d-violet:         #7C3AED;

    /* ── Spacing ── */
    --d-gap:            1.5rem;
    --d-radius:         12px;
    --d-padding:        1.25rem;
}
```

### 11.3 Proposed Component Library

| Component | Purpose | Status |
|-----------|---------|--------|
| `<x-ds-kpi-card>` | KPI metric card | ✅ Exists (accountant) |
| `<x-ds-card>` | Generic card container | ⚠️ Partial |
| `<x-ds-sidebar>` | Sidebar shell | ❌ Need to create |
| `<x-ds-toshi-bar>` | AI agent bar | ❌ Need to create |
| `<x-ds-connector-status>` | Channel status widget | ❌ Need to create |
| `<x-ds-activity-feed>` | Real-time activity stream | ❌ Need to create |
| `<x-ds-chart>` | Data visualization | ❌ Need to create |
| `<x-ds-empty-state>` | Empty state placeholder | ✅ Exists |
| `<x-ds-page-header>` | Page title + subtitle | ✅ Exists |

---

## 12. Implementation Roadmap

### Phase 1: Foundation (Week 1-2)
- [ ] Create CSS design tokens file
- [ ] Consolidate sidebar to single dark theme
- [ ] Build `<x-ds-toshi-bar>` component
- [ ] Build `<x-ds-connector-status>` component

### Phase 2: Admin Dashboard (Week 3-4)
- [ ] Redesign admin dashboard with Toshi bar
- [ ] Add connector status widget
- [ ] Add activity feed
- [ ] Replace inline SVGs with icon components
- [ ] Add fee collection chart

### Phase 3: Teacher Dashboard (Week 5-6)
- [ ] Redesign teacher dashboard with Toshi bar
- [ ] Add class performance chart
- [ ] Add pending actions widget
- [ ] Fix hash-based routes

### Phase 4: Student Dashboard (Week 7-8)
- [ ] Redesign student dashboard with Toshi bar
- [ ] Add grades overview
- [ ] Add fee balance widget
- [ ] Add homework tracker

### Phase 5: Other Dashboards (Week 9-10)
- [ ] Accountant: Add payment tracking, payroll overview
- [ ] Librarian: Add book inventory stats
- [ ] Receptionist: Add visitor/call log stats
- [ ] WhatsApp → Unified Connector Dashboard

---

## 13. Success Metrics

| Metric | Current | Target |
|--------|---------|--------|
| Toshi visibility | 0% of dashboards | 100% of dashboards |
| Connector visibility | WhatsApp only | All 6 channels |
| Design token usage | ~10% (accountant) | 100% |
| Sidebar consistency | 5 different colors | 1 unified theme |
| Time to key action | 3-4 clicks | 1-2 clicks (via Toshi) |

---

## 14. Conclusion

The dashboards are functional but don't reflect what KlassApp actually is — an agentic protocol with multi-channel connectors. The most differentiating feature (Toshi) is invisible. The most powerful capability (multi-channel) is hidden.

The proposed redesign puts Toshi at the center, shows connectors prominently, and unifies the design system across all roles. This aligns the product experience with the brand positioning.

---

## Appendix: File Inventory

### Dashboard Views
```
resources/views/admin/dashboard/dashboard.blade.php
resources/views/admin/dashboard/birthday.blade.php
resources/views/admin/dashboard/birthdayTeacher.blade.php
resources/views/admin/dashboard/unpaidfees.blade.php
resources/views/admin/dashboard/workAnniversary.blade.php
resources/views/admin/whatsapp/dashboard.blade.php
resources/views/admin/subadmin/dashboard.blade.php
resources/views/accountant/dashboard.blade.php
resources/views/alumni/dashboard.blade.php
resources/views/library/dashboard.blade.php
```

### Layout Files
```
resources/views/layouts/admin/layout.blade.php
resources/views/layouts/admin/sidebar.blade.php
resources/views/layouts/admin/menu.blade.php
resources/views/layouts/teacher/layout.blade.php
resources/views/layouts/teacher/sidebar.blade.php
resources/views/layouts/teacher/menu.blade.php
resources/views/layouts/student/layout.blade.php
resources/views/layouts/student/sidebar.blade.php
resources/views/layouts/student/menu.blade.php
resources/views/layouts/accountant/layout.blade.php
resources/views/layouts/accountant/sidebar.blade.php
resources/views/layouts/accountant/menu.blade.php
resources/views/layouts/library/layout.blade.php
resources/views/layouts/library/sidebar.blade.php
resources/views/layouts/library/menu.blade.php
resources/views/layouts/reception/layout.blade.php
resources/views/layouts/reception/sidebar.blade.php
resources/views/layouts/reception/menu.blade.php
resources/views/layouts/alumni/layout.blade.php
resources/views/layouts/alumni/sidebar.blade.php
resources/views/layouts/alumni/menu.blade.php
```

### Design System Components
```
resources/views/components/ds-kpi-card.blade.php (exists)
resources/views/components/icons/sidebar.blade.php (exists)
resources/views/partials/empty-state-product-demo.blade.php (exists)
resources/views/partials/setup-banner.blade.php (exists)
```
