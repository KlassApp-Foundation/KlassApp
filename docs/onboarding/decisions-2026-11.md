# Onboarding decisions — November 2026

**Status:** decided by Rasta on 2026-11-09. This note is the reference the code and the other onboarding docs follow.
**Supersedes:** the open questions at the end of `docs/onboarding/agentic-onboarding-audit.md` (#1297), where they conflict.
**Tracker:** T-116.

---

## The decisions

### D1. #1285 merges after one pending attendance check (T-114)

`integration/monday-2026-11-09` (#1285) is the base for onboarding work until it merges. One attendance check (T-114) is outstanding; after it, #1285 merges.

**Consequences**

- Branches for onboarding work are cut from `origin/integration/monday-2026-11-09`, not `main`.
- Their draft PRs target `main` and carry the marker **"merge after #1285"**. When #1285 merges, rebase onto `main` and drop the marker.
- Nothing waits for the merge: docs start now, code is built on the branch.

### D2. The wizard is the only setup surface

Every school onboards through `/admin/onboarding/wizard` — nursery, primary, secondary O and A level, any country, and **including test schools**.

Toshi's scripted onboarding (v1) is **retired everywhere**. There is no onboarding choice that sends a school to a chat, and no school is left in a chat onboarding mode.

**Consequences**

- Remove the v1 handlers, `AgentToshi::$steps`, `commitAll()`, `handleExams`, `handleSchoolPay` and the `school_pay_api_password` write (T-117).
- Remove the onboarding choice's Toshi path and `ToshiOnboardingPath` (T-117).
- Add a migration that moves any school still in `toshi_mode = onboarding` to the wizard safely (T-117).
- The v2 step driver (`ToshiOnboardingV2Driver`) **stays**, behind `toshi.onboarding_v2` (default off). It is the later agent surface. Nothing in onboarding may reach it through a path choice.
- Toshi as an assistant (chat about the school's data, with its confirm gate) is **not** retired by this decision. Only onboarding is.

### D3. Exams and SchoolPay are out of onboarding, everywhere

Out of the wizard, out of Toshi, and out of the workbook. They remain **features**: a school sets them up afterwards from their own pages. Do not remove the features, their tables, their routes, or their settings.

**Consequences**

- Toshi v1's `exams` and `school_pay` steps go with v1 (T-117).
- No wizard step, checklist step, agent tool or workbook tab may create an exam or a SchoolPay configuration during onboarding.
- `school_pay_api_password` is not written by any onboarding path.
- The workbook validator must reject or ignore assessment and SchoolPay tabs rather than convert them into onboarding rows.
- `docs/onboarding/playbook.md` and the runbook say this once, in the same words.

### D4. Uganda defaults are soft-launch, not schema

A new school may open on Uganda, UNEB, UGX and `P1` **as defaults**. Those defaults must not harden into columns, validation, completion checks, or behaviour that only makes sense in Uganda.

**Consequences**

- The 56 `FLAGGED(product-decision)` markers are catalogued with a proposed settings-driven default for each (T-122). No code changes until Rasta approves that table.
- Country, currency and curriculum come from the school's own settings wherever the code currently assumes Uganda.
- No new Uganda-only behaviour is added to onboarding.

### D5. Bootcamp questions stay open

These are **not decided here** and no code or doc may assume an answer. They go to the team bootcamp.

| Audit question | Status |
| --- | --- |
| Q2 — Is the agent the primary surface, or a back-office operator whose work the admin reviews in the wizard? | **Open — bootcamp** |
| Q4 — Which curricula and countries must the workbook support at launch? | **Open — bootcamp** |
| Q5 — Where does the workbook live: upload per run, or read from the school's Google Sheet? | **Open — bootcamp** |
| Q6 — What is the confirmation unit (per phase, per step, per N rows), and who may confirm? | **Open — bootcamp** |
| Q7 — Where is the DPA confirmation recorded, and who audits it? | **Open — bootcamp** |
| Q9 — Target size per school and acceptable time for one onboarding? | **Open — bootcamp** |
| Q10 — For imported parents with no email and no WhatsApp, is admin hand-over of a link the default? | **Open — bootcamp** |

For contrast, three of the audit's questions are answered by D1–D3: Q1 (#1285) by D1, Q3 (retire Toshi v1) by D2, Q8 (exams and SchoolPay) by D3.

Until the bootcamp answers Q6, the plan layer stops at **intake and preview** (T-123b). Applying a plan is not built.

---

## What this changes in the documents

| Document | Change |
| --- | --- |
| `docs/onboarding/playbook.md` | The wizard is the only surface; the Toshi v1 script is gone; exams and SchoolPay are out everywhere, including the workbook |
| `docs/onboarding/school-onboarding-runbook.md` (#1296) | Same three statements; the runbook is a draft PR and is corrected there |
| `docs/onboarding/agentic-onboarding-audit.md` (#1297) | Kept as the audit of record. Its open questions are answered or deferred here; its build plan is re-estimated in T-123a |
| `knowledge.md` | A session entry recording these decisions and the tracker items that implement them |

## What this does not change

- Toshi the **assistant** (not onboarding), its tools, its authorization gate and its confirm-before-write system.
- The wizard's steps, order, defaults, completion checks and Finish behaviour — except that no exam or SchoolPay step exists anywhere in onboarding.
- Exam results, marks, report cards, fee payments and SchoolPay as features.

---

## Implementation map

| Item | What it does | Depends on |
| --- | --- | --- |
| T-116 | This note; playbook and knowledge aligned | — |
| T-117 | Retires Toshi v1 onboarding; migration moves schools to the wizard | D2, D3 |
| T-118 | Legacy student/teacher imports onto `OnboardingEngine` (or routed to the engine-backed pages); remove `dd()` | #1285 |
| T-119 | Architecture test: no onboarding write outside `OnboardingEngine` | T-117, T-118 |
| T-120 | One phone normaliser, country-driven | #1286 or main |
| T-121 | Merge follow-through report: the eight Bariyo123 PRs inside #1285, and every other open PR | #1285 |
| T-122 | `global-defaults.md`: every Uganda assumption with a proposed settings-driven default | — |
| T-125 | `wizard-walkthrough.md`: every current wizard step, for Rasta's post-deploy walk | T-117 |
| T-123a | `plan-layer.md`: plan structure, storage, preview, confirmation proposal, idempotency, DPA gate | Bootcamp Q6 for the confirmation unit |
| T-123b | Intake page: workbook → converter → stored plan → flags with tab and row. No school-data writes | T-123a, #1291 |
