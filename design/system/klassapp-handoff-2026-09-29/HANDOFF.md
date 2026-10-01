# KlassApp: consolidated implementation handoff (all open work)

**Date:** 2026-09-29
**Repo:** KlassApp-Foundation/KlassApp · branch `main`

| | SHA | When |
|---|---|---|
| **Designed from** | `a9013f74b384` (short; the tools return 12 characters) | 2026-09-28T21:28Z, including #874–#877 |
| **Checked at the end** | `main` moved **2 commits / 19 files** past `a9013f74b384` | 2026-09-28T21:38Z |

**Drift at the end check:** the Ugandan admission-fields PR landed (`2026_09_29_000000_add_ugandan_admission_fields_to_admissions_table.php`, `AdmissionController`, the 5 `Admission*Request`s, 6 Vue step components, `UgandanAdmissionFieldsTest`). The admission design here was built from the brief's field list, **not** from that PR. See PR 12 for three mismatches to reconcile. Nothing else in the diff touches the design work below.

**Before starting:** `git log --oneline a9013f74b384..main`. If files named in a PR below have changed since, re-read them before implementing.

**Already done on main, don't respecify:**
- **#874:** the admission form no longer shows raw exceptions, an unknown slug returns 404, closed admissions show `pages/admission/unavailable`, and expired report links show the `errors/report-link-expired` 410 page.
- **#875:** invite expiry is a single 72-hour value in `config/invites.php`.
- **#876:** reset codes are 6 digits, and the email says so.
- **#877:** "Standard detail" is renamed to "Class", and the Aadhar, community and Tamil-marks fields are removed.

**Measurement status:** every contrast ratio in this document is **calculated from hex** unless marked *(browser-measured)*. Frame timings are **not measured under a 4× CPU throttle**; see PR 16.

**Concepts:** everything is in `concepts/` in this ZIP. Open `concepts/task-c/*.html` and `concepts/public-pages/index.html` directly in a browser.

---

## Order at a glance

Bugs first, then foundations, then surfaces. Each PR is independently mergeable unless it lists a prerequisite.

| # | PR | Source spec | Prereq |
|---|---|---|---|
| 1 | Invite pages unstyled (missing classes) | B-handoff §B2 | — |
| 2 | Teacher student view: three crashes/errors | this doc | — |
| 3 | Contrast: landing `--text-muted`, auth `--ap-muted` and links, OSS banner | landing-polish-audit §1, B-handoff §B2, this doc | — |
| 4 | Landing: duplicate `reveal-delay-5` | this doc | — |
| 5 | App phase 1: tokens, buttons, forms | A-handoff Phase 1 | — |
| 6 | App phase 2: alert consolidation | A-handoff Phase 2 | 5 |
| 7 | App phase 3: tables, in batches | A-handoff Phase 3 | 5 |
| 8 | App phase 4: empty and loading states | A-handoff Phase 4 | 5 |
| 9 | App phase 5: sidebar footers for teacher/parent/student | A-handoff Phase 5 | — |
| 10 | Email shell and templates | B-handoff §B1 | — |
| 11 | Public pages: errors 401/403/429/503 and legal on the vintage shell | B-handoff §B2 | 1, 3 |
| 12 | Public admission form: visual rebuild plus the Ugandan field UI | this doc and `public-pages/screens/admission.html` | 3 |
| 13 | Landing: hero device frame, app-shot component, K mark | landing-polish-audit §2–4 | 3 |
| 14 | Landing: tower v2 and orbit (if not merged) | handoff-09-27-toshi-tower-v2, -toshi-orbit | 13 |
| 15 | Landing: background (option you pick) | this doc | 13 |
| 16 | Landing: capability-card and pillar motion | this doc | 4, 15 |
| 17 | Landing: open-source copy (stance you pick) | this doc | 3 |
| 18 | Roster column scoping (the "never sent" enforcement) | this doc | — |
| 19 | Role-scoped student list and student view | this doc | 5, 7, 18 |
| 20 | Marks entry grid | this doc | 5, 7, 18 |
| 21 | Parent dashboard | this doc | 5, 8 |
| 22 | Timetable week view | this doc | 5 |

The earlier specs are in `specs/`: `A-handoff-app-resync.md`, `B-handoff-emails-public.md`, `landing-polish-audit.md`, `handoff-2026-09-27-toshi-tower-v2.md`, `handoff-2026-09-27-toshi-orbit.md`, and the copy list `B-copy-corrections-production.md`. **Where a PR says "per X", X is the spec; nothing is restated here.** From the B-handoff, only the parts #874–#877 didn't already cover still apply; skip any item listed under "Already done".

---

## Bugs

### PR 1: `fix(auth): invite pages render unstyled`
Per B-handoff §B2. `auth/invite-set-password` and `auth/invite-invalid` use `auth-card`, `form-input` and `btn` classes that `auth-preview.css` doesn't define. Rebuild them on the `ap-*` classes, keeping every test hook and all three link states (expired / claimed / invalid). The expiry copy reads from `config('invites…')` (the #875 value) and is not hard-coded as "72 hours".
**Accept:** both pages render with the paper shell at 375 and 1280, and the existing invite tests pass. Before/after screenshots.

### PR 2: `fix(teacher): student profile crashes and wrong ID`
In `resources/views/teacher/student/show.blade.php`:
1. `@if($user->userprofile->gender == …)`: `userprofile` can be null, so wrap it in `optional()`.
2. `date('d-m-Y', strtotime(optional($user->userprofile)->date_of_birth))` prints **01-01-1970** when the date of birth is null. Show "Not recorded" instead.
3. `ID: {{ $user->id }}` shows the database id. Show the admission number, or drop the line if the school has none.
**Accept:** a feature test for a student with no userprofile returns 200, and there's no 1970 in the output.

### PR 3: `fix(a11y): contrast on landing, auth and banner`
- **Landing:** per landing-polish-audit §1 (`--text-muted` #94A3B8 used as body text, about 30 uses). The two honesty lines change colour only, and their wording is preserved exactly.
- **Auth pages:** `--ap-muted` #64748B → **#475569**; links #1E6FD9 → **#1D4ED8** (per the B-handoff).
- **Open-source banner:** the "MIT licensed" text is rgb(134,239,172) on #FAFAF5, **1.34:1** *(browser-measured, from your catalog)*. Use `#166534`. The final wording comes from PR 17.
**Accept:** axe shows 0 colour-contrast failures on `/`, `/login` and `/password/reset` at 375 and 1280.

### PR 4: `fix(landing): Toshi card stagger`
`landing-v2.blade.php` L371–372: Extensible and Safe by Design both carry `reveal-delay-5`, so they land at the same moment. Fold this into PR 16 if that ships in the same release.

---

## App resync (PRs 5–9)
Per A-handoff Phases 1–5 exactly. One change: the success button variant is retired (aliased to primary); A-handoff already reflects this. The phase 5 regression checks still apply: the mobile menu opens exactly once, the Toshi split layout still collapses and resizes, and the admin sidebar footer is unchanged.

## Emails and public pages (PRs 10–11)
Per B-handoff §B1 and §B2. Skip the invite-expiry code change (#875), the reset-code length (#876) and the admission raw-exception, unknown-slug and 410 work (#874); the visual shell for the 410 page is still in PR 11. The copy-corrections list is data, not code; apply it only through the admin UI or a reviewed seeder.

---

## PR 12: `feat(admission): public form on the auth shell + Ugandan field UI`
**Concept:** `concepts/public-pages/screens/admission.html` (#step1–#step5, #done, #error, #closed, #noboarding).

**Structure:** 5 steps: Class, Student, Academic, Parents, Health. The progress bar and step count match the step-1 concept from Task B. Controls are 44px tall (48px for primary), and a missing school logo falls back to the school name.

**Conditional rules (all server-validated, not just hidden in the UI):**
- Day/boarding is shown and required only when the school offers boarding.
- Previous school and last class completed are optional for Baby, Middle and Top class and P.1, and required otherwise.
- PLE index number and aggregate are shown and required for S.1 only. UCE index number and results summary are shown and required for S.5 only.
- Religion is optional, never required, and never shown in lists.
- Health (step 5) always carries the notice: "Used only to care for your child. Visible only to authorised staff."

**Never collected:** tribe or ethnicity, community, parents' national ID, parents' income.

**Health data:** never appears in list views or exports by default (see PR 18).

**States (#874 behaviour, now with visuals):**
- **Sent:** a reference number and what happens next.
- **Submission failed:** "Nothing you entered has been lost" plus a support reference, never the exception.
- **Closed:** `pages/admission/unavailable` in this shell.
- **Unknown slug:** the existing vintage 404.

**Drift against the PR that landed at the end check. These three need a decision before implementing:**
1. **Parents are stored as `father_*` / `mother_*` slots.** The brief is "Primary contact + Second parent/guardian + relationship". A grandmother or aunt as primary contact would have to be stored as "father" or "mother". Recommendation: keep the columns for now but map primary → first slot and second → second slot by position rather than by sex, and label them in the UI only by the chosen relationship. Or migrate to `guardian_1_*` / `guardian_2_*`. **Your call.**
2. **Emergency contact has only `emergency_contact_name_1`.** The brief also has relationship and phone. Check whether existing columns cover these; if not, add `emergency_contact_relationship_1` and `emergency_contact_phone_1`.
3. **The migration adds no nationality, religion, sex, passport-photo or occupation columns.** These presumably already exist on `admissions`. Confirm that nationality defaults to "Ugandan" and that religion is nullable.

**Accept:**
- `UgandanAdmissionFieldsTest` still passes.
- New tests: S.1 without PLE fails; S.5 without UCE fails; P.1 without a previous school passes; religion is absent in every case.
- Screenshots of each step at 375 and 1280.

**Later (not this PR):** the configurable form builder. See the sketch in `task-c/coverage-map.html`. The field set above is its default template. Protected fields can't be hidden, and health fields can't be made required.

---

## Landing (PRs 13–17)

### PR 13
Per landing-polish-audit §2–4 and its "Suggested PRs" 2–4.

### PR 14
Per the tower v2 and orbit handoffs, if they aren't already merged. Check `partials/landing-toshi-tower.blade.php` on main first.

### PR 15: `feat(landing): continuous paper background`
**Concept:** `task-c/landing-background.html`. Three options were rendered at 1280 and 375 (as skeletons that use the real CSS).

**Pick one (recommended: A):**
- **A · One paper.** The hero vintage fades out over its bottom 30%. The page runs one `--paper-base → --paper-mid → --paper-base` gradient. The footer moves from `#F8FAFC` to `--paper-mid`. Device-frame shadows move to the warm `rgba(92,74,48,.35)`. Radial glows stay on the orbit and the tower only.
- **B · Paper + mist bands.** Alternate sections sit on `--paper-mid` with 64px feathered edges, assigned per section, not by `nth-child`.
- **C · Quiet grid.** One static page-wide dot layer, `rgba(92,74,48,.09)` 1px on a 24px grid, masked in after the hero and out before the footer. The orbit's own grid is removed.

**Rules:** static only, no `background-attachment: fixed`, no blur, and `--paper-*` tokens only. Any text on `--paper-mid` must be **#475569** (6.65:1): `--text-secondary` #64748B there is **4.18:1** and fails.
**Accept:** no visible seam at the hero's bottom at 375, 768 and 1280; axe clean; Lighthouse CLS unchanged.

### PR 16: `feat(landing): once-only motion for Toshi cards and pillars`
**Concept:** `task-c/landing-motion.html`. It has a replay button and a reduced-motion toggle, and the copy is verbatim from the Blade file.

**Motion rules:**
- Uses one IntersectionObserver at threshold 0.25 and unobserves after the first play.
- Plays once and never loops.
- Animates only `transform` and `opacity`: no blur, no animated `box-shadow`, no `stroke-dashoffset`.
- Card entrance: 0.6s using the page's `.reveal` ease `cubic-bezier(.22,1,.36,1)`, 80ms stagger for cards and 100ms for pillars.
- Each card's secondary gesture starts 0.45s after its card lands, and the whole sequence ends within about 2.2s.
- `will-change` is set only while a section plays and removed on `transitionend`.

**Toshi cards:**
- **Role-Aware:** a pill reads Teacher, then Parent, then Admin, and stays on Admin.
- **Multi-Channel:** 4 channel dots fan out.
- **Action-Taking:** a "Sent ✓" pill scales in.
- **Context-Aware:** 3 memory bars stack.
- **Extensible:** 2 connector tiles dock.
- **Safe by Design:** an "Approved by admin" pill scales in.

**Pillars:**
- **Secure:** the padlock shackle snaps shut.
- **Scalable:** three growing squares appear (classroom → school → district).
- **Private:** a scope ring contracts onto the shield, then fades.
- **Interoperable:** WhatsApp, Drive and Slack dots dock.
- **Provable:** two term ticks fill and the third stays hollow; it never completes. The dashed **Coming** badge and "Stated direction. Not a live feature yet." are untouched.

**Reduced motion:** every element renders in its final state with no transition. Role-Aware shows "Admin", and the Private ring stays hidden.

**Performance:** frame timing was sampled in the concept without throttle only. The implementing agent must record a DevTools trace at **4× CPU throttle**, with the hero rotation, orbit and tower running, while both sections play. Pass condition: no frame over 16.7ms attributable to this work.

**Accept:** the trace is attached, a reduced-motion screenshot is attached, and there's no CLS.

### PR 17: `copy(landing): one open-source stance`
**Concept:** `task-c/landing-background.html` (second half). The live page currently contradicts itself: the banner says it's open source now, the hero says "when we open source", and the protocol section says "after a security review".

**Pick one:**
- **Stance 1 · Open today.** Banner: "KlassApp is open source under the MIT licence. Read the code on GitHub". Hero CTA: "View the source". Protocol intro: "…designed to be extended, self-hosted, and shaped by the education community." Open Source card: "MIT licensed. Read it, run it on your own servers, or contribute. No vendor lock-in." **Requires the public repo and the self-hosting docs to be live.**
- **Stance 2 · Opening later.** Banner: "KlassApp will be open source under the MIT licence after an independent security review. Get notified". Hero CTA stays "Get notified when we open source". Protocol intro: "…self-hostable once the source opens…". Open Source card: "Will be MIT licensed. Source and self-hosting open publicly after an independent security review. No vendor lock-in." It also gets the amber **Coming** badge.

**Accept:** `grep -n "open source\|MIT" resources/views/landing-v2.blade.php resources/views/components/landing-layout.blade.php` shows only the chosen stance's strings.

---

## PR 18: `feat(roster): column-scoped student payloads`
**Concept:** `task-c/role-lists.html`, which has the full matrix.

**Pattern already on main:** `RosterScopeService::studentsForStream()` selects only `id, school_id, academic_year_id, user_id, standardLink_id, academic_status` plus `users.id, school_id, usergroup_id, name, status` when `isSubjectTeacherOnly()` is true. Generalise it:
1. Add `RosterScopeService::studentColumnsFor(User $actor, ?StandardLink $stream): array`, returning the allowed column set per the matrix. `studentsForStream()` and the student-profile data endpoint `select()` only those columns. **"Never sent" means the column isn't in the query; nothing is hidden with CSS.**
2. **"On request"** fields (health, special needs, religion, and fee balance for the head teacher, plus the two open decisions below) come from `GET …/students/{id}/sensitive/{field}`. The endpoint is policy-gated and writes an `activity_log` row: actor, field and student.
3. **Role mapping (the repo has no Head teacher or Bursar):**
   - Bursar = Accountant (usergroup 11).
   - Head teacher = SchoolSubadmin (4), **pending your decision** on whether a real role is needed.
   - Class teacher and subject teacher are both usergroup 5, split by the existing CT / section-fallback / `class_teacher_links` logic.
4. **`assertActorCanAccessSchool` currently allows only usergroups 1, 3 and 5.** Add explicit branches for 4, 8 and 11 that return their scoped column sets and never the teacher stream visibility. Parents (7) stay on their own `children` relation and never enter RosterScope.
5. Health fields are excluded from every list and export query by default. An admin export gets an explicit "include health notes" checkbox that is also logged.

**Two cells need your decision:**
- Class teacher → fee balance: currently "on request".
- Subject teacher → special needs: currently "on request".

**Accept:**
- A new `RosterColumnScopeTest`, per role: assert the JSON keys returned are exactly the matrix's "default" set, and that "never sent" keys are absent from the SQL. Use `DB::listen` to assert the `select` list.
- `RosterScopeServiceTest`, `RosterScopeServiceCrossTenantIsolationTest` and `TeacherStudentDetailsRosterScopeTest` still pass.

## PR 19: `feat(students): role-scoped list and student view`
**Concept:** `task-c/role-lists.html`, with Admin, Class teacher and Bursar views.

**List:** columns per role. The default sort is Name for admin, lowest attendance first for the class teacher, and largest balance first for the bursar. Search covers name and admission number. The class teacher's list shows a scope note.

**Student view:** tabs are rendered only for fields in the payload, never shown disabled. **Default tab per role:**

| Role | Opens on |
|---|---|
| Admin | Overview |
| Head teacher | Marks |
| Class teacher | Attendance (Marks is the next tab) |
| Subject teacher | Marks (own subject) |
| Bursar | Fees |
| Librarian | Library |
| Parent | Overview |

**Accept:** screenshots per role at 375 and 1280, and every tab button is ≥ 44px.

## PR 20: `feat(marks): entry grid`
**Concept:** `task-c/coverage-map.html` §1.
- **Scope:** one stream × one exam × the teacher's own subject columns (from PR 18).
- **Keyboard:** Enter moves down and Tab moves right.
- **Grade:** computed live from the school's grade bands (`admin/school/grades`). The concept's bands are samples.
- **Saving:** autosave on blur with a single status line, not a toast per cell. Out-of-range values are flagged inline (red border, "Max is N", `aria-invalid`) and are **not saved**.
- **Layout:** the student column is sticky, and inputs are 44px tall.
- **Actions:** "Submit for approval" hands off to the existing approvals flow.

**Accept:** a test that a value above the maximum is rejected server-side, and a test that a subject teacher can't post another subject's column.

## PR 21: `feat(parent): dashboard`
**Concept:** `task-c/coverage-map.html` §2.

**Layout (375 first):**
- A child switcher when there are several children.
- Three cards: Today (attendance), Latest results (published only), and Fees.
- Two actions: "Pay with Mobile Money" and "Ask Toshi on WhatsApp".

**Empty states:** each names what's missing and the next step. For example: "No attendance taken yet today. Class teachers usually mark attendance by 9:00."

**Accept:** a parent sees only their own children (existing tests), and each empty state is covered.

## PR 22: `feat(timetable): week view`
**Concept:** `task-c/coverage-map.html` §3.
- **Layout:** a period × weekday grid. A teacher sees "My week"; an admin sees a stream and can edit.
- **Clashes:** the same teacher in two streams at once is shown amber **with a "Clash" label**, never by colour alone.
- **Mobile:** below 760px the grid becomes a one-day list with day tabs.
- **Scope:** build on the new `teacher/timetable/{index,form,manage}` from this sync, not a parallel view.

**Accept:** a clash is detected server-side and has a test.

---

## Coverage map (reference, not a PR)
See `task-c/coverage-map.html`: every view family at `a9013f74b384`, labelled designed-and-shipped / designed-not-built / not-designed.

**Largest remaining undesigned areas, after PRs 20–22:**
- Printed documents: report card, ID card, bus pass and payslip layouts.
- WhatsApp and SMS copy.
- Admin people pages.
- Accountant fees and payroll.
- Library and reception.
- The small staff modules shared across 8 roles (one shared design would cover all of them).
- Legacy `landing.blade.php`, `landing2.blade.php` and `welcome/*`: candidates for deletion.

## knowledge.md
Stamp each PR with its number from this table, the source spec file, and the `main` SHA it was branched from.
