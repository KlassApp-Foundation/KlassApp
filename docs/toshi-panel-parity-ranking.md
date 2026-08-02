# Toshi panel-parity expansion ranking (Part A)

> Branch: `audit/toshi-panel-parity-ranking` off `origin/main` @ `72c2ca6`  
> Date: 2026-08-02  
> Scope: **docs-only** — rank which role’s next coverage batch should go first. No tools built.  
> Method: HTTP route counts in role route files + current OperationsAgent/`ToshiOrchestrator` tool inventories + prior audits (`docs/toshi-role-parity-audit.md`, `docs/toshi-deputy-admin-audit.md`).  
> No panel activity-log ranking data exists — rank by **gap size**, **day-to-day domain criticality**, and **complexity/risk**.

---

## Framing (non-negotiable)

| Rule | Implication |
|---|---|
| **Deputy Admin (ug4)** | Tool set = School Admin surface minus Settings/governance (`AddCoAdminTool`, `SetCurriculumTool`). **Do not rank or scope ug4 separately** — expanding School Admin coverage benefits Deputy automatically (minus excluded tools). |
| **Parent (ug7)** | **Excluded from ranking.** No web panel; WhatsApp/`ParentOperationsAgent` only. “Full panel parity” does not apply. Note at end only. |
| **Alumni / OldStudent (ug9)** | Built MVP panel (4 GET routes); **never had Toshi**. Include as ranking candidate. |
| **Destructive defaults** | Prefer batches that are read/create/update with existing Tier-2/Approvable patterns. Do **not** open student/teacher destroy, promotion→alumni, or fee-category destroy in an early batch. |

---

## Inventory summary

| Role | ug | Panel routes (approx) | Toshi tools (web) | Advisory list | Relative panel coverage |
|---|---:|---:|---:|---|---|
| School Admin | 3 | **~615** (584 `admin.php` + 31 `setting.php`) | **23** skill tools (+6 routers; +3 orphans) | 16 ≈ tool-aligned | **~4% of domains** have any tool |
| Deputy Admin | 4 | **~584** (no settings) | **22** (inherits ug3 − co-admin/curriculum) | 15 | Same as ug3 minus owner tools |
| Teacher | 5 | **~243** | **12** | 12 ≈ covered | Advisory closed; panel depth remains |
| Accountant | 11 | **~109** (54 + 55 payroll) | **6** | 6 ≈ covered | Advisory closed; payroll tree deeper |
| Receptionist | 10 | **~97** | **7** | 7 ≈ covered | Advisory closed; classwall residual |
| Student | 6 | **~77** | **13** | 11 ≈ covered | Self-scope shipped; classwall mutations deferred |
| Librarian | 8 | **~40** | **6** | 6 ≈ covered | Advisory closed; **card issue CRUD** follow-up |
| Alumni | 9 | **4** (all GET) | **0** | none | No Toshi; MVP history portal |
| Parent *(excluded)* | 7 | **0 web** | 6 WA reads | children scope | Channel parity, not panel |

**School Admin domain skew (admin.php):** classwall 42, student 37, teacher 30, standardLink 24, homework 22, dashboard 22, report 19, … across **~117** first-segment domains. Prior audit ~588 ≈ measured **584** + settings **31**.

---

## Per-role gap notes (criticality × risk)

### School Admin (ug3) — + Deputy inherits

| | |
|---|---|
| **Central uncovered (day-to-day)** | Noticeboard, events, holidays, timetable, homework/assignment *oversight*, discipline, messaging — running the school, not “obscure CSV.” |
| **Partial** | Students/teachers/parents (add/find/list, not edit/block/import); reports (`GenerateReportTool` ≠ full CSV suite); fees (create/pay/balance, not full category CRUD). |
| **Edge / defer** | Magazines, documents, bank, addons, stock (empty routes), most CSV exports. |
| **High-risk (keep panel-only for now)** | Destroy student/teacher/parent; promotion→alumni; force-delete subjects; settings/academic-year (`setting.php`, ug3-only); co-admin (ug4 already excluded). |
| **Gap size** | Largest. |

### Teacher (ug5)

| | |
|---|---|
| **Advisory** | Covered (attendance, marks, lesson plans, assignment/homework create, leave apply, classwall post, tasks, views). |
| **Central remaining** | Homework/assignment *submission review* (mark/return), richer lesson-plan edit, leave status depth, classwall beyond single create-post. |
| **Edge** | Visitor/call/postal logs on teacher panel (~27 routes) — front-desk overlap, lower Toshi priority. |
| **Risk** | Medium — operational writes already Tier-2; no promote/delete. |

### Accountant (ug11)

| | |
|---|---|
| **Advisory** | Covered. |
| **Central remaining** | Deeper payroll (templates, batch edge cases, deletes) — **money**. |
| **Risk** | **High** — WA already `HARD_DENY`s payroll; expanding deletes needs CoAdmin-level rigor. Prefer **not** as first expansion wave. |

### Librarian (ug8)

| | |
|---|---|
| **Advisory** | Covered; cards are **view-only** by design. |
| **Central remaining** | Library **card issue / return / edit** CRUD (explicit prior follow-up). |
| **Risk** | Medium — lending writes already exist; card issue is contained domain. |

### Receptionist (ug10)

| | |
|---|---|
| **Advisory** | Covered (visitor/call/postal, dashboard, events, noticeboard view, tasks). |
| **Central remaining** | Classwall (~22) — less core than desk logs already shipped. |
| **Risk** | Low–medium. |

### Student (ug6)

| | |
|---|---|
| **Advisory / self-scope** | Largely shipped (incl. submit assignment/homework, tasks, conversations). |
| **Remaining** | Classwall mutations (deferred); profile chrome. |
| **Separate** | Legacy portal IDOR fixes already merged (#130–#132) — not Toshi expansion. |
| **Risk** | Low blast radius if expanding; product chose deferral for classwall writes. |

### Alumni (ug9)

| | |
|---|---|
| **Panel** | Built: dashboard, marks, directory, report-card download (`MustBeAlumni`). |
| **Toshi** | None — empty capabilities, no Blade mount, no agent. |
| **Risk** | Low (read-only history). Cheap greenfield if “complete the role matrix” matters; low product urgency vs operators. |

### Parent (excluded)

WhatsApp `ParentOperationsAgent` (6 reads). Digests / preference memory are the relevant next Parent tracks — not panel parity.

---

## Recommended ranking

| Rank | Role | Why (not gap-size alone) |
|---:|---|---|
| **1** | **School Admin** *(Deputy auto-benefits)* | Largest leverage: day-to-day **comms + calendar + schedule** domains still have **zero** tools, while advisory CRUD is already partially there. Expanding here moves the product from “onboarding/operator subset” toward “run the school.” Risk is manageable if Batch 1 stays **non-destructive**. |
| **2** | **Teacher** | Advisory closed, but remaining depth (submission review, leave, classwall) is **core teaching loop**, not edge admin. Smaller surface than ug3; high centrality per route. |
| **3** | **Librarian** | Smallest live ops panel; **card issue CRUD** is a known, contained follow-up — good “finish the role” batch without money risk. |
| **4** | **Receptionist** | Desk logs done; residual classwall is less central. |
| **5** | **Student** | Self-scope done; further writes were consciously deferred. |
| **6** | **Accountant** | Advisory done; residual = **payroll depth** — high risk / WA deny — schedule only after confirm design appetite. |
| **7** | **Alumni** | Candidate only — 4-route MVP, read-only, no Toshi yet. Cheap later; not the strategic next operator win. |

**Parent:** out of ranking — continue via WhatsApp / digests / prefs, not panel parity.

---

## Top-ranked role — School Admin Batch 1 proposal

Mirror Superadmin’s phased batches (Geo → Plans → Schools → …). **Do not** attempt the full ~600-route gap.

### Batch 1 theme: **School communications & calendar**

Day-to-day for headteachers: announce, schedule, remind — without touching destroy/promote/settings.

| Sub-batch | Domains (panel) | Proposed tool direction | Confirm rigor |
|---|---|---|---|
| **1a — Noticeboard** | list / create / update / show notices | `ListNoticesTool`, `CreateNoticeTool`, `UpdateNoticeTool` (no destroy in 1a) | Create/update → Tier-2 ConfirmsBeforeWrite |
| **1b — Events** | list / details / create / update school events | Reuse school_id Gate patterns from #131; `ListEventsTool`, `CreateEventTool`, `UpdateEventTool` | Create/update → Tier-2; destroy stays panel / later batch |
| **1c — Holidays** | list / create holiday | `ListHolidaysTool`, `CreateHolidayTool` | Create → Tier-2 |

**Explicitly out of Batch 1:** student/teacher destroy, promotion, settings/academic year, co-admin, fee-category destroy, full report CSV suite, classwall (large; schedule Batch 2+), transport, admissions.

**Deputy Admin:** receives 1a–1c automatically via shared tools + dual Gate (`toshi-school-action` **or** `toshi-deputy-action`) — no Settings tools added.

**Success criteria for Batch 1:** ug3 (and ug4) can create/list notices, events, holidays via Toshi with audit identity; isolation tests prove destroy/settings still absent; Blade already includes 3 and 4.

### Suggested later School Admin batches (sketch only)

| Batch | Theme | Notes |
|---:|---|---|
| 2 | Timetable + homework/assignment *admin* oversight | Central academics; avoid student destroy |
| 3 | Discipline + leave types | Moderate risk |
| 4 | Classwall (admin) | Large surface — own pass |
| 5 | Reports expansion | Prefer curated report tools over dumping every CSV |
| ∞ | Destructive / promotion / settings | Product + HITL design required; not “next” |

---

## If ranking is rejected toward Teacher-first

Acceptable alternate: **Teacher Batch 1 = submission review** (list/mark student homework & assignment submissions) + leave status — highest *per-role* criticality with smaller blast radius than opening ug3 domains. Still keep School Admin Batch 1 queued immediately after.

---

## Open questions for Part B approval

1. Confirm **School Admin Batch 1 = notices + events + holidays** (vs Teacher submission-review first).  
2. Confirm destroy remains panel-only through at least Batch 2.  
3. Alumni: park until after operator batches, or schedule a tiny read-only agent as a matrix-completion sprint?  
4. Accountant payroll depth: explicit “not until HITL appetite” vs never on Toshi (WA already denies).

---

## Ready for Part B?

**Awaiting approval of ranking + School Admin Batch 1 scope.** No implementation on this branch.
