# Kabale Junior School — report cards, production runbook

**Status:** rehearsal written, not approved to run. Rasta decides what runs, in what order.
**Scope:** Primary Five first (76 learners, Beginning of Term and Mid Term, four subjects), then the whole school.
**Rehearsed on:** a local copy of `feat/report-progressive-parity` (PRs #1342–#1346), a scratch SQLite database, made-up learners only. Nothing here has touched production.

This runbook follows the handoff in `~/Downloads/report-card-handoff/HANDOFF.md` (15 Nov). Where the rehearsal found that KlassApp cannot yet do something, the step says so instead of pretending.

---

## 0. Before anything: the five gates

Nothing is written to production until each of these is true. They are Rasta's gates, restated as checkable lines.

| # | Gate | Who clears it | How you know |
|---|---|---|---|
| 1 | Data agreement, privacy policy and terms are in place and accepted | Rasta + Danson | Signed document on file; school admin has accepted in KlassApp |
| 2 | The school has confirmed grade bands, division ranges and ranking by aggregate | Kabale | Written confirmation naming all three, attached to the job |
| 3 | Crest, head teacher's name, motto and exam dates received — or Rasta chooses to proceed without them | Kabale / Rasta | Files in the job folder; a written "proceed without" if missing |
| 4 | Rasta approves the dry-run output before each write | Rasta | Message saying "approved, run step N" |
| 5 | Pilot parents nominated, told, numbers supplied, and each linked to their child | Kabale | A list of 5–10 names with numbers, and links in KlassApp |

Until gate 5 is cleared, no WhatsApp message of any kind goes out. Until gate 1 is cleared, no real learner data is loaded.

---

## 1. Learner load (76 learners, Primary Five)

**Screen:** Admin → Students → *Import a list* (`/admin/students/import`), or the *Import a list* link on the dashboard. The upload posts to `/admin/importUsers`.

**File:** CSV or XLSX, one row per learner. Required columns: `firstname`, `class`. Useful: `lastname`, `gender`, `date_of_birth`, `country`, `joining_date`. Leave `admission_number` empty so KlassApp mints a KLS number for each learner.

**Steps**

1. Confirm the class exists with exactly the name the file uses ("Primary Five"), and that it has a class link for the current year. Import matches the class name; a near-miss puts the learner in the school with no class.
2. Confirm the current academic year is the one you mean. The learner's class link is written against the year that is `status = 1`.
3. Upload the file. Read the result: *n inserted*, and any per-row failures shown on the same screen.
4. **Check:** Students list filtered to Primary Five shows 76 rows; every learner has a KLS number (`KLS…`); none is inactive; spot-check three names against the source file.

**Rehearsal timing:** 800 learners in one upload took **49.8 s** (including building the CSV). 76 will be a few seconds.

**Rollback:** do not delete. Set the wrongly imported rows to inactive (Students → learner → status), or re-import corrected rows; KLS numbers are never reused. If the whole batch is wrong, report to Rasta before touching anything else.

---

## 2. Exams and settings

**Screen:** Admin → Exams and reports → *Exam sets* (`/admin/exams/sets/create`).

**What Kabale needs:** the two sittings that exist — Beginning of Term and Mid Term — for Primary Five, each with the four subjects (English Language, Mathematics, Science, Social Studies).

1. Confirm the exam types exist (Settings → Exam types). Kabale's shared catalog has Beginning of Term, Mid Term and End of Term. The names are starter names; nothing depends on them.
2. **Decide which sittings count toward the term.** This matters: a Progressive report needs at least one **counting** sitting, or no card is produced at all. In the rehearsal, with both sittings off, the card failed with *"No EOT exam found for this class."* Turn on the sittings that must appear on the card (Settings → Exam types, per school), or ask the marks agent for the code change.
3. Create the exam set: term = Third Term, types = the two sittings, classes = Primary Five.
4. **Check:** Exam sets shows 8 exams (2 sittings × 4 subjects); each has the right subject and teacher; no duplicates from a second click.

**Rules the school must confirm (gate 2), entered on Report cards → *Progressive report rules*:**

- Bands: D1 95–100 (1 point) … F9 0–39 (9 points). These are Kabale's stated primary bands.
- Division: ≤12 = 1, 13–24 = 2, 25–28 = 3, 29–32 = 4, ≥33 = U.
- Ranking: aggregate, lowest first; ties share a place and the next place is skipped.

**Rollback:** delete the exam set (it removes its exams) *before* marks are imported. After marks exist, do not delete; correct through the marks screens.

---

## 3. Marks import (eight files)

**Screen:** Admin → Exams and marks → open the sitting → *Paste or upload marks* for each subject (`/admin/exams/{exam}/marks/import`). Preview first, then confirm.

**File shape:** `KLS number, student_name, Mark`. The KLS number is the match key; the name is ignored for matching. Use the KLS numbers KlassApp issued in step 1, not the numbers from the source sheet.

**Steps**

1. Map each source row's KLS to KlassApp's before upload. Keep a mapping file; it is the audit trail for this step.
2. Upload one file per subject and sitting (8 files for Primary Five). Read the preview: how many will be saved, how many are unchanged, and every skipped row with its reason.
3. Confirm. Repeat for all eight.
4. **Check:** each subject has the expected number of marks; the preview's "skipped" list is understood line by line; no row was skipped for `not_a_number` other than the known `ABS` rows.

**What the rehearsal found**

- A blank cell is **skipped**, not stored (reason `blank_mark`). `ABS` is **rejected** as `not_a_number` on this stack; PR #1326 adds it but is still a draft.
- **A skipped mark blocks submission.** Submit refuses with *"Every student needs a mark or AB before you submit for approval."* So a class with any blank mark cannot be submitted, approved or released. To release anything, every learner must have a number or be marked AB.
- Consequence: "missing" cannot be released as missing today. Either the school decides those learners were absent (enter AB), or the missing-as-zero PR is extended so a blank can pass submission.

**Rollback:** re-import with overwrite before submission; after submission an admin returns the subject (Approvals → *Send back* with a reason); after release, the sitting is closed and a correction is a new, explained release.

---

## 4. Release

**Screen:** Admin → Marks Release (`/admin/marks/releases/new`).

1. Confirm every subject is approved: Approvals shows all green for the sitting. Release refuses until it is.
2. Pick the sitting and the class, set the time, and **leave WhatsApp off** for the first release unless the pilot list is cleared (section 6).
3. Release. **Check:** status becomes *released*; the result log lists every learner with `sent`, `queued` or `skipped` and a reason. With no parents linked it is 76 × "No number is following this student" — expected, not an error.
4. **Check the card** for three learners (one high, one middle, one with a gap) before telling anyone.

**Rehearsal timing:** 10 classes (150 subjects) submitted, approved and released in one pass; the per-class release call itself is sub-second. Most of the time is approval, not release.

**Rollback:** there is no un-release. A wrong release is corrected by fixing the marks and making a new release with a written reason; both stay in the audit trail.

---

## 5. Report cards

**Screen:** Admin → Report cards (`/admin/reports/cards`), choose the class.

- **Per learner:** the download icon on the row (`/admin/reports/cards/{stdLink}/student/{learner}/download`).
- **Whole class:** *Download all* (`/admin/reports/cards/download/{stdLink}`). Up to 12 learners are built in the request; more go to a queued job that returns a ZIP (job timeout 900 s).

**Rehearsal timings (76 learners, one class):** 0.49 s per card, 37.4 s for all 76, 39.6 s for the merged class PDF (51.5 MB). **800 learners, 10 classes:** see the scale table in the rehearsal report; per-card time is the same, so the whole school is minutes, not hours, when run class by class.

**Check:** one page per learner; both sittings as columns in date order; TOTAL per sitting; the anchor sitting's aggregate and division; no "SAMPLE FOR APPROVAL" tag on a final card; name, KLS, class and term correct; prints legibly in black and white.

**What the rehearsal found the card does *not* print:** a grade per subject per sitting, the aggregate and division for the non-anchor sitting, and POSITION at all. The design in `card-template/` asks for all four. The values are computed (they match the oracle); they are not on the page yet.

**Rollback:** regenerate. Cards are rendered on demand, so a corrected mark or setting produces a new PDF; nothing is stored per learner except the temporary WhatsApp copy.

---

## 6. WhatsApp pilot (5–10 parents)

**How delivery works today**

- **Parent-initiated:** the parent messages the KlassApp number with `REPORT`. KlassApp replies with the child's card as a **PDF document** (signed URL, 30 minutes) plus a menu. Limit: 5 requests per number per hour. This is the only path that sends the PDF.
- **Release notification:** a release with WhatsApp on sends a **text or an approved template** (`results_released`), never the PDF. In the rehearsal, 8 linked parents got the template; the other 68 were skipped with "No approved, linked parent for this student".
- **Pilot selection:** there is **no per-parent selection**. The audience is every approved, opted-in parent linked to a learner in the class (or the whole school). The only lever today is which parents are linked — linking only the pilot parents works, but gives those parents access to the on-demand card as well.

**Before the pilot**

1. Link each pilot parent to their own child (Students → learner → parents, or the parent-link flow the school uses). Status must be approved (`student_parent_links.status = 1`).
2. Each parent messages the KlassApp number first, so the number is verified and the 24-hour window is open. Record the number they used.
3. Confirm the template is approved in the environment (`whatsapp.approved_templates` includes `results_released`); if it is not, the release skips every parent with "not approved by Meta yet".
4. Rasta approves the recipient list and the message preview.

**Run**

1. Release the sitting with WhatsApp on, or re-release with a reason if it is already out.
2. Watch the release result log: each parent is `sent`, `queued`, `skipped` or `failed` with a reason. Stop at the first wrong recipient.
3. Ask two parents to confirm by phone that they received **their own** child's card and nothing else.

**What the rehearsal found**

- The PDF file name is built from the learner's display name (`pupil-report-card.pdf`), not the KLS number. With 76 learners called "PUPIL …" every file has the same name. The handoff asks for the KLS number; the code needs a one-line change.
- A parent with no number is skipped silently (no message, no log line to the parent); an opted-out parent is skipped with "opted out". Both appear in the release result.
- Every send writes `message_delivery_log` (status, category) and the per-release result JSON.
- Nothing was sent during the rehearsal: the sender was a stub that recorded calls.

**Rollback:** WhatsApp cannot unsend. The only rollback is to stop before sending; after that, correct the next message and record what went out.

---

## 7. Order of work, with checks

| # | Step | Check before moving on | Approx. time (76) | Approx. time (800) |
|---|---|---|---|---|
| 1 | Gates 1–3 | Written confirmations on file | — | — |
| 2 | Learner load | 76 learners, KLS on each, right class | seconds | ~50 s |
| 3 | Exam set + rules | 8 exams, counting sittings on, rules confirmed | minutes | minutes |
| 4 | Marks import | 8 files previewed, skips explained | ~2 min | ~45 s per 150 subjects |
| 5 | Approvals | all subjects approved | minutes | minutes |
| 6 | Release (no WhatsApp) | status released, log read | seconds | seconds per class |
| 7 | Cards | 3 spot checks, then the class | ~40 s | ~0.5 s per card |
| 8 | WhatsApp pilot | list approved, template approved, 8 linked | — | — |
| 9 | Whole school | repeat 2–7 class by class | — | see scale table |

## 8. Known blockers before production

1. **No counting sitting → no card** (both Kabale sittings default to off).
2. **A blank mark blocks submission and release**; `ABS` is rejected by the importer on this stack.
3. **Released cards change when division ranges change** — 29 of 76 rehearsal cards changed after a rules edit, against decision 7 and acceptance 6.
4. **The card does not yet show the designed layout**: no grade per subject per sitting, no POSITION, no per-sitting aggregate/division for the non-anchor sitting.
5. **No pilot selection** in release; **file name uses the child's name**, not the KLS number.

Items 1–2 are prerequisites for Kabale's 76. Items 3–5 must be closed before the WhatsApp pilot and before anyone promises the school a card that matches the design.
