# Operator checklist: releasing a new school's first reports

**Audience:** whoever runs the deployment and the first release. Draft.
This is the operator view. The school-facing version is `docs-site/help/reports/first-term-checklist.md`.

Every production write in this list needs three things first: a verified backup, a dry run whose output is kept, and approval from the person who owns the deployment. If any of the three is missing, stop.

## 1. Term

- Confirm the academic year is active (`status = 1`).
- Make the reporting term current, dry run first: `php artisan terms:set-current {school} {term}`.
- Read back the three term flags.

## 2. Class link

- List sections with no `standards_link` for the active year.
- Link each one, dry run first: `php artisan classes:link-section {school} {section} {standard} [--year=]`.
- Read back the new row and the `setup.link-section` log line.

## 3. Learners

- Confirm the plan's student cap and the used count before importing.
- Confirm `student_id_sequences` has a starting row, or accept that numbering starts at 1.
- Import through the school's upload path. Report counts and ids only; never paste names.
- Check every learner has a KLS number and the right class link.

## 4. Marks

- Confirm exam sets and dates. Column order follows **Scheduled Date & Time**.
- Import marks per exam or term workbook as drafts. Preview first; read every skipped row.
- Missing and ABS count as 0. A blank is not stored as 0.
- Submit, then approve every subject. Release refuses until all are approved.

## 5. Release without notifying

- Release with the WhatsApp send switched off for the first term.
- Read the release log: each family entry is sent, skipped or switched off, with a reason.
- Check one strong, one middle and one learner with a gap before telling anyone.

## 6. Notify (only after the pilot list is approved)

- Choose the recipient list, read the preview, then release.
- One parent, one child, one message. Honour opt-outs.
- Keep the delivery log.

## After each write

Record: what changed, the ids, the command or screen, the actor, the backup id, and the dry-run output. If a step is wrong, stop and report before attempting a correction.
