# The plan layer: design

**Tracker:** T-123a. **Status:** design only. No code. Intake (T-123b) is the only part approved to build, and only up to storing a plan and showing its flags.

This is the middle the audit found missing: a validated **plan** between the workbook and `OnboardingEngine`, a **preview** of that plan against the school's current data, and a **confirmation** that covers a step rather than one tool call.

---

## 1. `OnboardingPlan`

One JSON document, produced by `ChecklistWorkbookConverter` (PR #1291) and stored as-is. It is data, not a model of the school: nothing in it is authoritative once applied.

```json
{
  "schema": 1,
  "source": {"file": "northwind.xlsx", "sha256": "…", "exported_at": "2026-11-08T09:00:00Z"},
  "school": {"name": "Northwind Academy", "country": "Kenya", "curriculum": "cambridge"},
  "dpa": {"confirmed": true, "tab": "Confirmations", "row": 4, "answer": "Yes"},
  "steps": [
    {
      "key": "standards",
      "rows": [{"class": "Primary One", "stream": "A", "learners": 32, "source": {"tab": "Classes & Streams", "row": 5}}],
      "flags": [{"tab": "Classes & Streams", "row": 7, "field": "Class", "message": "Unknown class 'P.9'.", "severity": "error"}],
      "counts": {"read": 14, "accepted": 13, "flagged": 1}
    }
  ],
  "pending": [{"file": "learner-guardian-details.csv", "why": "no importer column"}]
}
```

Rules for the shape:

- **Step keys are `OnboardingStepsService::ALL_STEPS` keys.** No second taxonomy (the audit found three).
- **Every row keeps its source `{tab, row}`.** A flag without a location is not actionable; the converter already produces both.
- **Counts are per step and per tab** so the review can say "13 of 14 rows accepted" without re-reading rows.
- **`pending` is first-class.** Things no importer takes (parent email, teacher gender) are not silently dropped.
- **No child's name is duplicated into a second store.** The plan holds the row values the importer needs; the workbook itself is not copied into the repository.

## 2. Where it lives, how long, who may read it

This holds children's data, so the storage decision is part of the design, not an implementation detail.

| Question | Proposal |
| --- | --- |
| At rest | `payload` uses Laravel's `encrypted:array` cast, so the row is unreadable without `APP_KEY`. The same cast applies to any later stored copy of the workbook |
| Table | `onboarding_plans`: `id`, `school_id`, `created_by`, `source_sha256`, `source_name`, `schema`, `payload` (JSON/LONGTEXT), `status` (`draft`, `previewed`, `applying`, `applied`, `abandoned`), `dpa_confirmed_at`, `expires_at`, timestamps |
| Who may read | School admins of that school (`school_id` scope), and platform operators only through impersonation, which is already audited. No route returns the raw payload to a parent, teacher or student |
| Retention | 30 days after `applied` or `abandoned`, then the row is deleted. A scheduled command deletes expired rows; it does not archive them |
| Raw workbook | Not stored by the app in this pass. The admin uploads it, the converter reads it, the plan keeps values and source locations. If a later pass stores the file, it goes to a private disk with the same 30-day rule |
| Logs | Never log row values (names, phones). Log `school_id`, plan id, step key, counts and flag codes only |
| Export | The plan is not downloadable as CSV from the UI. The importer CSVs the converter already writes are the export, and they exist only on the operator's machine in the CLI flow |

If the bootcamp wants a different retention (Q7), only `expires_at` and the command change.

## 3. Preview: create, already exists, conflict

Preview compares the plan with the school's current rows **per step**, using the same natural keys the engine uses. It writes nothing.

| Entity | Natural key for the comparison | `create` | `already exists` | `conflict` |
| --- | --- | --- | --- | --- |
| Academic year | `(school_id, name)` | no year yet | same name | a different year is current |
| Class / stream | `(school_id, section name)` where a stream is `"{Class} {Label}"` | no row | same name and stream | class exists without that stream, or a stream exists with different learners |
| Subject | `(school_id, name)` + its class link | no row | same name on the same class | same name on a different class |
| Teacher | `(school_id, email)` else `(school_id, phone)` | no match | same person | email and phone point at two different people |
| Student | `(school_id, school_student_id)` else `(school_id, name, class, stream)` | no match | same person | same name in the same class with a different ID, or the ID belongs to another learner |
| Parent | `(school_id, phone)` normalised | no match | same person | phone shared by two guardians with different names |
| Class teacher | `(standard_link_id)` | empty | same teacher | a different teacher already heads the class |
| Fee | `(school_id, name, scope, term)` | no row | same amount and scope | same name and scope with a different amount |
| Term | `(school_id, academic_year_id, name)` | no row | same dates | overlapping dates |

The preview result is a list per step: `create`, `exists`, `conflict`, each with its plan row and the existing record id. A conflict is never auto-resolved; the admin either fixes the workbook or accepts "skip this row", which is recorded.

## 4. Confirmation: one per step, and what "one step" means

**Proposal, not a decision (Q6).** The confirmation unit is per **step**, not per tool call and not per row. What happens *inside* a confirmed step is the open question, because two defensible models disagree:

| | A. All-or-nothing per step | B. Row-level results per step |
| --- | --- | --- |
| How | The step's rows run in one transaction; any failure rolls the whole step back | Each row commits; failures are collected and reported with their row number |
| Good | The school never has half a class list or half a teacher set; a retry is safe because nothing moved | One bad row (a duplicate phone, an unknown stream) does not block the other 239 |
| Bad | One bad row blocks the whole step until the workbook is fixed | A failed step can leave a school with some classes and not others; downstream steps must tolerate gaps |
| Fits | Teachers (class-teacher assignment depends on the full set), classes and streams (subjects and students depend on them) | Students and parents (large, independent rows), fees (independent rows) |

The review card is the same in both: one card per step, counts and flagged rows, Confirm or Reject. The difference is what Confirm promises.

- **Who may confirm** — school admin only, or also a KlassApp operator — is part of Q6. The design supports both: `confirmed_by` records the actor and whether they were impersonating.
- **Recommendation to the bootcamp:** B for students and fees, A for teachers, classes and terms. That is a proposal, not a decision.

## 5. Idempotency

Applying the same plan twice must create nothing new.

- Every write goes through `OnboardingEngine`'s existing `firstOrCreate` paths; the preview's natural keys are the same keys.
- The plan records `applied_rows` per step: `{plan_row_hash → model_id}`. A second apply checks that map first, then the natural key.
- Running apply on a plan whose `status = applied` returns the recorded result and writes nothing.
- The acceptance test is explicit: apply, snapshot row counts, apply again, assert the counts are identical and the second result is all `exists`.

## 6. The DPA gate

The data processing agreement is checked **before preview**, not only before conversion.

- The converter refuses to write files when the Confirmations tab is not `Yes` (already implemented in #1291). The plan stores that answer and its source row.
- The intake page refuses to store a plan without `dpa.confirmed = true`, and says which tab and row failed.
- `preview` and `apply` re-check the stored flag and the timestamp; a plan with no DPA cannot be previewed, even if it was stored by an older build.
- Where the signed record lives (workbook tab vs a record in the product) is Q7 and stays open. The plan carries whatever the bootcamp chooses as `dpa.reference`.

## 7. Surfaces

| Surface | What it does | Writes school data? |
| --- | --- | --- |
| `POST /admin/onboarding/checklist` (T-123b) | Upload `.xlsx`, run the converter, store the plan, render counts and flags with tab and row | No |
| `GET /admin/onboarding/checklist/{plan}` | The stored plan: steps, counts, flags, pending | No |
| `POST /admin/onboarding/checklist/{plan}/preview` | Compare with current data; store the comparison | No |
| `POST /admin/onboarding/checklist/{plan}/apply` | Apply one confirmed step | Yes, after Confirm |
| `GET /admin/onboarding/checklist/{plan}/result` | Post-import check (counts, KLS present, phone format) | No |

Every one of these is a school-admin route scoped by `school_id`, plus the platform operator path through impersonation.

## 8. What T-123b builds

Only the first two rows: upload, convert, store, show. No preview, no apply, no engine call. The acceptance test is that a deliberately broken workbook produces the same flags as `php artisan checklist:convert`, with the same tab and row.

## 9. Open questions this design does not answer

- Q6: the confirmation unit and who may confirm (§4 is a proposal).
- Q7: where the DPA record lives (§6 carries a reference, not a decision).
- Q5: whether the workbook is uploaded per run or read from the school's sheet. This design assumes upload; a sheet reader would replace the intake step and nothing else.
- Q9: target sizes. They change chunk sizes and the all-or-nothing choice in §4, not the structure.
