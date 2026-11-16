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
14. **Pass 1 stopped before the parent, the other class teacher, and the other school.** Those clicks are in the pass 2 section below. A release had not been completed in pass 1.

## Pass 2

disposable environment, not staging

The same scratch branch and the same invented school. Primary Six's two exams were removed, so Beginning Of Term and Mid Term are Primary Five only. Third Term was made current by this path: Settings, Academic terms (that card opens the add form), save a Second Term, land on the terms list, then "Make this the current term" on Third Term.

### Release

PUPIL 076's two blank marks were left blank. The class teacher's Submit stays disabled on Beginning Of Term Mathematics and Beginning Of Term Science. The head teacher opening that mark-entry address gets 404. Nothing was invented for that learner. Those two sheets were not approved. Recorded as not ready (#1335).

Mid Term was 4 of 4 approved. The head teacher released it with WhatsApp unchecked and the pilot list set to one fictional parent (the parent of PUPIL 001). The release row says queued 0, sent 0, skipped 1, reason "This parent has no number", webhook not sent. No message left the machine.

Beginning Of Term was confirmed the same way. The post came back as a redirect to the preview address, and the browser then got 405, because that address does not accept GET. The set was not released. Two of four subjects are still unapproved.

### Who was clicked, at 375 and at 1280

Every picture in the review pack has a black strip that reads "disposable environment, not staging".

| Who | What opened |
| --- | --- |
| School admin | Report type and the history line, rules (presets, try a mark, try an aggregate, ranking, reset, last changed by, save), all three templates, the terms list, the crest and the head teacher's name, exam-type settings, the marks sheet, Marks Release, one set and the class PDF |
| Head teacher | Home, report cards, rules, Marks Release, the marks sheet. The teacher-prefixed release address is 404. The admin address opens |
| Class teacher of Primary Five | Home, one class's report cards, the marks sheet, Beginning Of Term English. The class list of report cards is 500 (see below) |
| Parent of PUPIL 001 | Children, Marks (the four Mid Term marks, average 45, KLS0010001). Division on that page is a dash. Download latest report card says "No report card has been released yet" |
| Class teacher of Primary Six | Own report-card list opens and does not offer Primary Five. Typing Primary Five's class or its exam is a URL refusal test: both 403 |
| Admin of Sample North School | With no academic year, every admin address bounced to that school's own dashboard. After a year and a class level were added so the gate would let the request through, Primary Five's card and its exam edit were URL refusal tests: both 404, the same answer as an id that does not exist |

Try a mark of 95 says "95 is grade 1, 1 point." Loading "Older seeded scale (confirm with the school)" fills the form and does not save until Save. Last changed by reads "Sample Admin".

Switching the report type to set-based shows an include box on every exam type, and all eight were ticked. That is the "all sets" state. The type was switched back to progressive before any reprint. One sitting at a time is the Exam menu on Report cards (Beginning Of Term, or Mid Term). There is no separate builder that combines an arbitrary subset on the progressive card. The handoff still lists that builder as not done.

The crest (a green "SJS" square) and the name Sample Head were saved on School details. Both print on the card. Sample Head is under the head teacher's comment. `pdfimages` shows the crest as a 120×120 RGB image.

### Cards

For each of Formal, Warm, and Progressive (new look): one card for the top learner (PUPIL 013), a middle learner (PUPIL 032, the nearest place to 38; ties mean there is no place 38), PUPIL 076, PUPIL 033, and PUPIL 010. Plus one whole-class PDF each. Each whole-class PDF is 76 pages, and page 76 is KLS0010076, including the two missing marks. The class PDF did not drop that learner.

PUPIL 076's Beginning Of Term line prints Missing and F9 for Mathematics and Science. PUPIL 033 prints ABS. PUPIL 010 prints ABS on Mid Term Science. None of the cards say "No EOT exam found". They say "No combined mark applies."

### Acceptance

1. **Text of all 76 cards against the reference.** Grades, totals, and positions match for every learner on all three templates, both sets. Formal and Warm also match division, and they do not print the aggregate number (76 learners, KLS0010001 through KLS0010076). The new look matches the aggregate number, and it does not print division (the same 76). No learner was missing from a file.
2. **Snapshot of the default progressive card.** `ProgressiveDefaultSnapshotTest` fails. The rendered card is not the fixture committed with #1345. A second checkout of main was not booted, so this is the branch against its own captured card, not a side-by-side render of main.
3. **Six subjects and three sets, new look.** A made-up class at Sample North School (North Class, LEARNER 001, KLS0040001, subjects English, Mathematics, Science, Social Studies, Art, Music, sets Beginning Of Term, Mid Term, End Of Term). One page, A4 (595.276 × 841.89 pt). Not greyscale: the new look draws a green bar, and about a third of the sampled pixels are coloured. That school has no grade bands, so the grades and the aggregate print as a dash and the sentence says position is by the total.
5 and 6. **A released card keeps its rules.** After Mid Term was released, the rules were saved as ranking by total, division 1 ending at 11 (division 2 starting at 12), and grade 1 starting at 96. The reprinted card for PUPIL 001 was byte-for-byte the same text as the card from before the edit, including "Position is by aggregate". The rules screen itself did change: aggregate 12, which had been division 1, became division 2. The rules were then put back (automatic ranking, automatic divisions, grade 1 at 95).

### Known defects, clicked again

| | What the click showed |
| --- | --- |
| (a) Reprint says position is by the total while the rank is by aggregate | Does not reproduce on the released card. After the ranking was saved as total, the reprint still said "Position is by aggregate" and the numbers did not move |
| (b) Another school's class redirects, a missing class is 404 | Does not reproduce. Both addresses are 404 for the other school's admin. URL refusal test |
| (c) A sheet with no policy skips missing and ABS | This school has saved rules. On that sheet, PUPIL 076's blanks say Missing and count in the total (69), and PUPIL 033 and PUPIL 010 show ABS and count as 0. The suite test that expects a missing mark to count as zero still fails (80, expected 40), so the no-policy path is not cleared |
| (d) The whole-class PDF drops a learner who has a missing mark | Does not reproduce. All three class PDFs are 76 pages and include KLS0010076 |
| (e) The card refuses when no set counts | Does not reproduce. The card renders and says "No combined mark applies." It does not say "No EOT exam found" |
| (f) Exam edit does not check the school | Does not reproduce. The other school's admin gets 404 for exam 1 and for an id that does not exist. URL refusal test |

### Still not ready

- Beginning Of Term cannot be submitted or released while PUPIL 076's two marks are blank (#1335). The failed confirm then shows a 405 instead of the approval message (#1338).
- The parent sees the released marks and cannot download the card (#13 in `scratch-notes/change-requests.md`).
- The class teacher's report-card list is 500 because `examSetNames()` does not exist (#1339).
- The new look does not print division. Formal and Warm do not print the aggregate number.
- The default-card snapshot test and the 40-learner parity test fail (#1345, #1346, with the band change in #1361).
- The 800-learner scale test (#1358) passes when it is run on its own: 44.6 seconds, about 1 GB, times 1.6 / 1.6 / 4.5 / 35.9 seconds for the learners, the ten sheets, the ten learner cards, and one class PDF. Inside the full suite, with a 2 GB limit, the same test ended the PHP process.
- The new look is A4 and is not greyscale.
- The report builder that would combine any subset of sets is the set-based exam-type screen. It is not a progressive builder. The handoff still lists it as not done.
