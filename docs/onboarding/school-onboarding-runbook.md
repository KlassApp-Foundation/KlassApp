# School onboarding runbook: from the filled checklist workbook

Serves **T-089** and **T-110**. For a person now (Elijah) and an agent later. Generic: no real school's names or data belong in this file. For the wizard's own steps, error codes and starting values see `docs/onboarding/playbook.md`; this runbook covers what comes before and after the wizard when a school sends a filled checklist workbook.

> **Status.** `checklist:convert` is in draft PR [#1291](https://github.com/KlassApp-Foundation/KlassApp/pull/1291) (built on #1286, T-077). Until it is merged, step 2 cannot be run on `main`. Parts of steps 6 and 7 describe flows that are designed but not built (T-094, T-095/T-096); each says so.

## 0. Gates: do not start without these

Real children's data goes in only when **all** of these are true. If one is not, stop and tell Rasta.

1. **Data processing agreement:** the school's Confirmations tab has the data processing agreement row set to **Yes**. `checklist:convert` refuses to write import files if it is not, and says why.
2. **Privacy policy and terms approved (T-001).** Rasta confirms they are approved before any real child's data is entered anywhere. Until then use synthetic data only.
3. **AI stays off.** A new school starts with Toshi in `preview` or `onboarding` mode, AI/assistant off (standing rule 33). Nothing in this runbook turns it on; that is an explicit admin or Rasta action for that school.
4. **Rehearse on staging first.** Staging has demo/seed data only. Run steps 1 to 5 there with a synthetic copy of the workbook (same shape, made-up names) before touching production. Never use a real child's name on staging.
5. **Production access.** Check what you actually have this session (AGENTS.md "Environment reality check"). Cloud settings and environment variables belong to Rasta; report what needs changing, never change it. Never print, save or paste a credential or a demo password.

## 1. Export the sheet

1. In the school's checklist (Google Sheets), wait until every tab has finished calculating. The **All Learners (for import)** tab is built by formulas from the class tabs; if it looks empty, the formulas have not run.
2. File > Download > Microsoft Excel (.xlsx). Keep the file name as exported. Do not edit it in Excel (that can change dates and phone numbers).
3. Keep the file out of the repository. Store it where Rasta says (not in git, not in chat).

## 2. Convert and read the report

```
php artisan checklist:convert path/to/workbook.xlsx --out=path/to/output
```

It reads the workbook and writes only to the output folder; it does not touch the database. Output:

| Path | What it is |
| --- | --- |
| `students.csv`, `teachers.csv`, `teacher-links.csv`, `class-teachers.csv` | The files the existing importers take, with their exact headers |
| `no-importer/` | Clean copies of terms, classes and streams, subjects, fees and admin contacts: nothing imports these, they are for entering by hand (step 5) |
| `pending/` | Things no importer can take: parent/guardian email, relationship, second guardian, teacher gender and role, assessments, grading notes |
| `report.md` | Counts per tab, learners per class and stream, every flagged row with its tab and row number, missing required fields, pending items |

Exit code 1 and no files means it refused: read the message. The two refusals are the data processing agreement not being Yes, and **All Learners** being empty while a class tab has rows (the formula values did not export; go back to step 1).

Read `report.md` top to bottom before doing anything else:
- **Counts per tab** against what the school says (rows read, accepted, flagged).
- **Learners per class and stream** against the workbook's own Learners per Class tab. A difference is a finding, not a rounding issue.
- **Flagged rows.** Each says what is wrong and where (tab, row). Nothing is guessed: a wrong class name, an unreadable phone, a missing required field, or a teacher or subject that does not match is left out of the import file, not repaired.
- **Notes** (class names rewritten such as `P.3` to `Primary Three`, learner counts that differ from the "approx." number).

## 3. Fix the flagged rows with the school

1. Send the school the list of flagged rows (tab and row only; do not send other families' data around).
2. The school corrects **the sheet**, not your CSV. Then export again (step 1) and re-run step 2. Repeat until the report has no flagged rows you cannot explain.
3. Rows left deliberately (for example a learner with no parent phone) stay flagged; write down the decision and who made it.
4. Duplicate teachers or learners flagged as "same name, class and stream" need a person to decide; never merge or delete automatically (standing rules 3 and 4: flag inactive, never delete).

## 4. Back up first

Before **any** write to production data (standing rules 1 and 2):
1. Ask Rasta to confirm a database backup exists from today, and note its time in the work log. Backups and Cloud settings are Rasta's; do not change them.
2. Confirm you can see the school's current state: counts of classes, streams, teachers, students and parents before the import. Record them; they are the "before" numbers for step 8.
3. Keep the converted output folder and `report.md` as the record of exactly what was imported.

## 5. Import order

Do these in this order. Each depends on the one before. The wizard (`/admin/onboarding/wizard`) or Toshi walks the same steps; `docs/onboarding/playbook.md` lists them as school name, student size, country, curriculum, category, EMIS, UNEB centre, academic year, standards (classes), subjects, teachers, students, terms, fees, WhatsApp, plan.

1. **School basics and country** (from School Details). The country matters: it decides how local phone numbers are completed.
2. **Academic year and terms** by hand from `no-importer/terms.csv`.
3. **Classes and streams** by hand from `no-importer/classes-and-streams.csv`. Class names must match the seeded names (Baby Class, Middle Class, Top Class, Primary One to Primary Seven, Senior One to Senior Six). Streams are their own sections named "{Class} {Label}", for example "Primary Four E".
4. **Subjects** by hand from `no-importer/subjects.csv`.
5. **Teachers:** upload `teachers.csv` (Name, Email, Subjects, Classes, Phone) in the Teachers step.
6. **Teaching assignments:** `teacher-links.csv` (Teacher, Subject, Class, Stream, Phone) through the teacher-links import. The importer reads the class only; the stream column is kept but ignored.
7. **Class teachers:** `class-teachers.csv` (class, stream, name, email, phone).
8. **Students:** upload `students.csv` (Name, Class, Stream, Parent Name, Parent Phone, Gender, Date of Birth, School Student ID) in the Students step, after classes exist. Large files import in the background and show progress; wait for the result and read it.
9. **Fees** by hand from `no-importer/fees.csv`. The Fee upload template in the app is a payment file (Name, Amount, Payment Method, Reference, Date, Notes), not a fee structure; do not use it for this.
10. **Admin contacts** from `no-importer/admin-contacts.csv`, and the school's WhatsApp number in the WhatsApp step.

If a step reports an error code, look it up in `docs/onboarding/playbook.md`.

## 6. What must be set up by hand, and what is pending

| Item | Where it goes | Why by hand |
| --- | --- | --- |
| Terms, academic year | Wizard terms step | No importer |
| Classes and streams | Wizard standards step / classes pages | No importer |
| Subjects | Wizard subjects step | No importer |
| Fee structure | Wizard fees step | The fee importer takes payments |
| Admin contacts | Settings / WhatsApp step | No importer |
| Parent/guardian email, relationship, second guardian | `pending/learner-guardian-details.csv`, entered on the parent's profile | The student importer has no column |
| Teacher gender and role | `pending/teacher-details.csv` | The teacher importer has no column |
| Assessments | `pending/assessments.csv`, entered when exam types are set up | The exam importer takes different columns |
| Grading notes | `pending/grading.txt`, entered in grading settings | Free text |

## 7. Invites and first sign-in

State of the product today; be honest with the school about it.

- **Teachers and class teachers** get a one-time invite link by email and WhatsApp (single use, expires after the configured hours). They choose their own password. Send these after step 5.
- **Parents** do not sign in with a password they were given. They message the school's WhatsApp number and link to their child; the school approves the request on the link request board (a parent can send the child's KlassApp ID, the one starting `KLS`). Tell parents which number to message and where their child's KLS number is shown.
- **Imported parents and students** have a random password nobody sees, so they can sign in on the web only through a reset code sent to a real email. Many have only a generated placeholder email, so for them the web sign-in does not work yet. The safe first sign-in is designed in `docs/product/parent-student-access.md` (T-094) and is **not built**. Do not promise web sign-in to parents or students until it is.
- Never send or tell anyone a password. If someone needs access now, use the invite link for teachers, WhatsApp for parents.

## 8. Checks after the import

Do all of these and write the results down.

1. **Counts.** Classes, streams, teachers and students now equal the "before" numbers plus what the report said was accepted.
2. **Learners per class and stream.** The students list, filtered by class and by stream, matches the report's Learners per class and stream table **and** the workbook's Learners per Class tab. Check at least one base class with streams; open `/admin/classes/{id}` and confirm each stream and its students are listed.
3. **KLS numbers.** Every imported student has a KlassApp number (`KLS` plus digits) on their profile.
4. **A class head's register.** Sign in as (or impersonate, per Rasta) one class head and open Take attendance: every stream of their class is on the register and the student count equals the class. Sign in as one stream teacher: only their stream.
5. **One parent link end to end,** with a test parent number the school agrees to: send the child's KLS number to the school's WhatsApp number, see the request on the link request board, approve it, check the welcome message, then check what that parent sees (fees, results, attendance only for released data).
6. **Phones.** A sample of stored phone numbers is in international format (`+` and the country code). `php artisan phones:to-e164` (T-077, #1286) is a dry run by default and reports what it would change.
7. **No stray data.** Nothing from the synthetic rehearsal is in production, and no real child's data is on staging.

## 9. If something is wrong

- Do not delete. Flag a record inactive (`status = inactive`) so it can be recovered (standing rule 3).
- Do not edit the database by hand. Corrections go through the app or a committed command (standing rule 1).
- If the counts are wrong after an import, stop, keep the output folder and report, and tell Rasta what the backup point is. Restoring from a backup is Rasta's decision.

## 10. Record

When done, note: the workbook export date, the report file, who fixed which flagged rows, the backup time, the before and after counts, the checks above and their result, and anything left pending. Update `knowledge.md` only at the end of the work phase, per AGENTS.md.
