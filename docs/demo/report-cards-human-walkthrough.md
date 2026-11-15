# Report cards, clicked through on a disposable machine

disposable environment, not staging

This is a local click-through of the scratch branch `scratch/report-stack-2026-11-16`. Nothing here is staging or production. Every learner is invented (`PUPIL 001` to `PUPIL 076`). The school is Sample Junior School. A second school, Sample North School, exists only so another school's admin can be refused.

Screenshots in the review pack have a black strip at the top that reads "disposable environment, not staging".

## How to start it

From a checkout of `scratch/report-stack-2026-11-16`:

```bash
composer install --prefer-dist --no-interaction
cp .env.example .env
php artisan key:generate
```

Point the local `.env` at a disposable MySQL database. Set `MAIL_MAILER=log`, `QUEUE_CONNECTION=sync`, `WHATSAPP_REPLIES_ENABLED=false`, `WHATSAPP_DEMO_ONLY_RECIPIENTS=true`, and leave `DEMO_RELEASE_WEBHOOK_ENABLED=false` with an empty URL. Do not point this file at a shared or live database.

```bash
php artisan migrate --force
php artisan db:seed --class=Database\\Seeders\\ReferenceSeeder --force
php scratch-notes/bootstrap-local.php
php artisan serve --host=127.0.0.1 --port=8080
```

`bootstrap-local.php` creates the two schools, the year 2026, First Term (current) and Third Term, the class level `primary`, the sections Primary Five and Primary Six, four subjects on Primary Five, and the staff below. It does not import learners or marks. Those were loaded through the app screens, as described below.

Build the class file the checks use:

```bash
python3 scratch-notes/fixture/reference/make_fixture.py
```

The file `scratch-notes/fixture/fixtures/p5-synthetic-class.json` hashed to `d29576f347dcec546eeaf0271867609c535e71e1f1bbaf780f7f5cef6bc40aed`.

## Local-only logins

Password for every account below: `LocalOnly-2026`

| Who | Email |
| --- | --- |
| School admin | admin@sample-junior.test |
| Head teacher | head@sample-junior.test |
| Class teacher of Primary Five | class-teacher@sample-junior.test |
| Class teacher of Primary Six | other-teacher@sample-junior.test |
| Parent of PUPIL 001 | parent.bl5et88o@sample-junior-school.local |
| Admin of Sample North School | admin@sample-north.test |

The parent address was assigned by the student import. Mail is the log driver. WhatsApp replies are off. The release form's WhatsApp box was unchecked. No message left the machine.

## What was clicked

Sign-in starts at `/login`. After that, the steps below were real clicks unless a step says the address had to be typed. A typed address is not a pass.

1. **Sign in as the school admin.** Lands on the setup wizard, step "Approximate school size", with the sidebar visible. Works.
2. **Exams and reports, then Report cards.** The page offers Progressive report or Set-based reports, says a change applies from the next release, and shows "No changes yet. This school uses the Progressive report." The history line is the link "Report card template and earlier PDFs". With no released class yet, the body says "No classes yet" and "Report cards need a class with an exam." Works as an empty state. Owning screen: #1342.
3. **Progressive report rules.** Heading, the green note that a change applies from the next release, "Not changed yet. This screen is showing the app defaults.", presets App default / Older seeded scale (confirm with the school) / Custom, Try a mark, Try an aggregate, ranking, and Save. Choosing class level `primary` and typing 95 then Show says "95 is grade 1, 1 point." Saving reports success. At 375 px the page was captured. The letter grades on a later PDF (F9, P8, C5) are not the "grade 1" this screen saved. See change requests. Works as a screen. The grade words disagree with the card. #1355 and #1361.
4. **Link Primary Five.** Classes and streams shows "2 sections are not linked". Link to a class, choose `primary`, tick "Yes, link this section", Link section to class. The list then shows only Primary Six still unlinked, and "Primary Five is now linked" is the resulting state (Primary Six remains). Works. #1356.
5. **Academic terms.** Settings, then the card Academic terms, opens **Add Academic Term**, not the list that has "Make this the current term". There is no click from that card to the list. **Not a pass.** The control exists on the terms list, and the settings card does not open it. #1356.
6. **Exam sets.** Exam sets, New, Third Term, only Beginning Of Term and Mid Term ticked, whole school left on. The next page says 10 exams were created (four Primary Five subjects and Primary Six English, two sets). Works. #1261 is the exam-set page this stack is using; the create action is on the branch.
7. **Import learners.** Students, Import a list. Upload of the class file. The page says "76 students added, 0 skipped" and lists PUPIL 001 with Sample Parent. Works. The KLS number on the account is `KLS0010001` onward. The admission number from the file was stored as the school student id (`KLS9000001`) and was not used as the KLS number. # the students import screen.
8. **Import marks.** As the class teacher, each "Enter marks" card, then "Paste or upload marks", Check file, Save marks. Eight files. Before grade bands were saved, every row was skipped with "No grading band covers this mark". After step 3, the files saved. One row per file that contains ABS is skipped as "Mark is not a number". ABS is not accepted by this importer. #1326, which teaches the importer ABS, was not in this stack.
9. **Phone mark entry, 375 px.** On Beginning Of Term English, ABS was clicked for PUPIL 033 and Save. On Mid Term Science, ABS was clicked for PUPIL 010 and Save. Both pages said "Marks saved" and "76 of 76 entered". The two missing marks for PUPIL 076 (Beginning Of Term Mathematics and Science) were left blank by the file. Works for ABS. #1335.
10. **Submit for approval.** Submit is enabled only when nobody is blank. Six exams submitted. Beginning Of Term Mathematics and Beginning Of Term Science stay disabled because PUPIL 076 has no mark there. **Not ready** for a class that has a missing mark: the blank that the card is supposed to count as 0 blocks submit. #1335.
11. **Approve.** Head teacher opens Mark submissions (the badge said 6). School admin Approve on each sheet. `approval_status` becomes `approved`. The `status` column stays `submitted`. The list still reads as submitted in places because those two fields disagree. #1324 and #1338.
12. **Marks Release as the head teacher.** The head teacher opens Marks Release from Exams and reports. The form is there, WhatsApp is off, and the pilot choice is "Everyone in this class" or "Only the people I choose". For Mid Term the step says "4 of 5 approved". The fifth subject is Primary Six English, which was created with the whole-school set and was never submitted. The release button can be clicked; a full release of the class did not complete because the set is not fully approved. **Not ready** as a completed release. The head teacher can open the screen. #1325 for access, #1352 for the pilot list, #1338 for the phone release cards.
13. **Report PDF from the service after the clicks.** A PDF for PUPIL 076, PUPIL 033, PUPIL 010 and PUPIL 001 was rendered from the same database. It is titled PROGRESSIVE REPORT, Third Term 2026, and the columns are BEGINNING OF TERM and MID TERM. Missing and ABS are printed, with F9. "No EOT exam found" is not in the text. "No combined mark applies." is. The word Incomplete is not in the text. The download was not clicked from Report cards, because that page still had no released class. Treat the PDF as service output from this database, not as a clicked download.
14. **Parent, other class teacher, other school.** Not finished as clicks. The parent account exists and can be given the local password. Those refusals were not walked in the browser in this pass.

## What Rasta should expect to see

After the clicks above, Sample Junior School has 76 invented learners in Primary Five, two exam sets in Third Term, ABS on the two learners named in the fixture, and two missing marks on PUPIL 076. The progressive rules screen is saved. A release to parents was not completed.
