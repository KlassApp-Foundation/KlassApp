# Proposal: prefilling the marks entry form with saved marks (not built)

Status: proposal, 2026-10-06. The marks entry form deliberately stays blank today; saved
marks are shown through the "N marks already saved" note and the marks view page. This note
describes what prefilling would take and the risks to settle first.

## What prefill would mean

- The entry form shows each student's saved mark inside the input instead of an empty
  placeholder, so teachers can see and correct values in place.

## Risks (why this is not a one-line change)

- Accidental overwrite. Prefilled values post back as-is, so any submit rewrites every row.
  Without a change summary, a stray submit can save stale numbers over newer ones (another
  teacher, or an import that happened after the page was opened).
- Locked and submitted exams. Inputs must respect the submission lock and the correction
  reason flow exactly as today; prefilling must never create a path around them.
- Cross-exam safety. The prefill query must be scoped exactly like the import surface
  (exam + subject + school); a loose scope could show another exam's or subject's marks.
- Nursery classes. Nursery uses domain assessments, not numeric marks; the nursery branch
  must stay excluded.
- Cleared marks. A blank input currently reads as "nothing here"; an explicit way to clear
  a saved mark is needed so "blank" and "cleared" cannot be confused.
- Concurrency. Saving is last-write-wins per student today; prefilled forms widen the window
  (the page can sit open), which strengthens the case for a change summary before saving.

## Suggested shape (when picked up)

1. Server-render saved values into the same inputs; no other behavior change.
2. Client-side dirty tracking; on submit, show the changed rows (old to new) once, like the
   import preview, before applying.
3. Keep locked/submitted handling and the correction reason flow unchanged.
4. Keep an explicit "clear this mark" affordance.

Not estimated; not built in this change.
