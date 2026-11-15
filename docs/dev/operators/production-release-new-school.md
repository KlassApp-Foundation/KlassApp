# Production release checklist for a new school

**Audience:** whoever runs the deployment and the first release. This is an operator note, not a school-facing guide.
Every write below needs three things first: a verified backup, a dry run whose output is kept, and approval from the deployment owner. If one is missing, stop.

## 0. Before the school

- Confirm the code on production is the commit you tested. Record the deployment id and commit.
- Confirm the environment's default disk is object storage with a bucket attached. Upload one test file, redeploy, and check it still opens. A local disk loses files on every deploy.
- Confirm `php artisan db:health` (or the row-count baseline you use) matches the last record.

## 1. Backup, verified

1. Take a database snapshot. Record its id and time.
2. Restore it to a scratch database.
3. Compare row counts for the tables this release will touch: schools, users, sections, standards_link, subjects, exams, exam_marks_submissions, marks, marks_releases.
4. Keep the comparison. If the restore fails, do not continue.

**Rollback:** restore the snapshot; nothing else in this list has run yet.

## 2. Object storage attached

- Check the environment variable names only (never print values): the bucket, endpoint, region, key and secret.
- Upload a test file through the app, redeploy, open the file again.
- Confirm the crest/report upload screen shows **Saved**, not **Saved but missing**.

**Rollback:** revert the environment change and redeploy. Files already in the bucket stay there; files written to a local disk are gone.

## 3. Current term

- Confirm the academic year is active.
- Make the reporting term current:
  - On `main`: re-save the terms in the onboarding wizard terms step, dry run first on staging.
  - With #1356 merged: `php artisan terms:set-current {school} {term}` without `--apply`, then with it after approval.
- Read back all three term flags.

**Rollback:** set the previous term current the same way. Record both changes.

## 4. Class link

- List sections with no `standards_link` for the active year.
- Link each one:
  - On `main`: the classes screen.
  - With #1356 merged: `php artisan classes:link-section {school} {section} {standard} [--year=]`, dry run first.
- Read back each new row.

**Rollback:** removing a link is a data decision. Stop and report before changing one.

## 5. Learner load (dry run first)

- The upload screen has no preview. The dry run is the same file on staging plus a count of the file's rows.
- Confirm the plan's student cap and the used count before importing.
- Confirm `student_id_sequences` has a starting row, or accept that numbering starts at 1.
- Import. Report counts and ids only.
- Check: every learner has a KLS number, the right class link, and no duplicate.

**Rollback:** set the batch to inactive; do not delete. KLS numbers are never reused.

## 6. Marks import

- Confirm the exam sets and their **Scheduled Date & Time** (this sets column order).
- Import per exam or as a term workbook. Preview first and read every skipped row.
- A blank is skipped, not stored as 0. Missing and ABS count as 0 on the report.
- Submit, then approve every subject. Release refuses until all are approved.

**Rollback:** re-import with overwrite before submission; after submission, return the subject; after release, a new release with a reason.

## 7. Release without notifying

- Release with the WhatsApp send switched off.
- Read the release log: each family is sent, skipped or switched off, with a reason.
- Check one strong, one middle and one learner with a gap before telling anyone.

**Rollback:** there is no un-release. Correct the mark, then release again with a reason; the old release stays in the history.

## 8. Record

For every step: what changed, the ids, the command or screen, the actor, the backup id, the dry-run output, and the time. If a step is wrong, stop and report before attempting a correction.
