# Field spec — Progressive Scores card

Status: design for review. Not built. Not committed.

The card is one printed page for one learner, one class, one term. The class teacher fills it. A school admin can do every stage, including the head teacher's report. A head teacher already has the admin's powers inside their own school; this spec does not take those away. A subject teacher who is not the class teacher still enters only the subjects they teach.

Every figure on the card comes from the report service that already feeds the PDF. The template prints. It does not add, weight, or round on its own.

## 1. Header

All of these come from school settings. None of them is typed onto the card, and none of them is hardcoded for one school.

| On the card | Setting | Today |
| --- | --- | --- |
| Crest | School logo (`school_details.school_logo`) | Prints on Formal, Warm, and New look. No logo: New look draws the school's initials. The paper card does the same. |
| School name | `schools.name` | Prints. |
| "Nursery and Primary Day and Boarding" | A subtitle the school writes | Does not exist. The current subtitle is a fixed map (`Nursery`, `Primary`, `Nursery And Primary`, `O-Level`, `O-Level And A-Level`). Add one text setting, empty until the school fills it. Prefill nothing that claims day or boarding for a school that has not said so. |
| P.O. Box | A new text setting | Does not exist. Do not put it in `schools.pincode`. That column is a postal code from the older product. |
| Telephone numbers | `schools.phone` and `school_details.landline_no` | Already joined into one "Tel:" line. Keep that. |
| Motto | `schools.motto`, falling back to `school_details.moto` | Already prints, under the comments on Formal. This card also shows it in the header. For this school the stored motto is "Hard Work Pays". Other schools keep their own. |
| Serial number, optional | A new per-card value, off unless the school turns it on | Does not exist. It is not the KLS id. When it is off, the header does not leave an empty "Serial" label. |

The sample drawings use placeholder telephone numbers and a placeholder P.O. Box so the layout can be judged. Those placeholders are not this school's numbers.

## 2. Learner line

| On the card | Source |
| --- | --- |
| Name | The learner's report-card name. Look the learner up by id, never by name alone. |
| Class | The section's class name, plus the stream when the class has more than one. Example: Primary Three, East. |
| Term | A full phrase, not the stored short name. Term 1 prints "End of First Term". Term 2 prints "End of Second Term". Term 3 prints "End of Third Term". The stored name stays whatever the school saved. |
| Year | The academic year name, beside the term phrase. |

KLS id stays available in the data. The paper header does not require it. The new-look card already prints it, so the field stays on the learner record. Do not drop it from the export.

## 3. Monthly Results ledger

One row per calendar month. The columns are the subjects that learner offers.

English, Mathematics, Science, and Social Studies each have their own Agg column (the points, D1 through F9). The other subjects on the row have a mark and no Agg column. Then four closing columns: Total, Agg, Div, Psn.

### Where a sitting goes

The school defines its own sets. BOT, MOT, and EOT are starter names only, the same way a new school is offered three terms and can rename them. The card never branches on those three words.

Each sitting is placed by its exam date:

- Take the calendar month of `scheduled_at`.
- A sitting prints as a month row when its subjects are approved, the set is released, and the mix has not set `include: false`.
- A set the mix marks `include: false` does not print. On the sample, Practice sits in March and is excluded, so March is not a row.
- An unreleased set does not print.
- A set that nobody has marked is left out, as it is today.
- Two included sittings in the same month are two rows. Each row is labelled with the month and the set's own name, so they do not collapse into one cell.
- A sitting with no date sorts last and is listed for a person to date before it can print. It does not guess a month.

### What the row shows

- The mark cell prints the approved mark, the word Missing, or ABS. A subject that is not approved prints a dash and is left out of the row total.
- Missing on an approved subject counts as zero toward Total and takes the F9 band (9 points) when that subject has an Agg column.
- ABS on an approved subject prints ABS and counts as zero the same way.
- Total is the sum of the numeric marks on that row, after those rules. It is that sitting's own total, not the weighted term total.
- Agg on a core subject is that subject's points from the school's grading table.
- The row's Agg and Div use only English, Mathematics, Science, and Social Studies. That is the rule. Other offered subjects still print their marks. On the end-of-term ledger they still have their own Agg letter. They do not enter the row aggregate or the division.
- The code on `main` still adds every subject that has points. A build must pass only these four into the existing division function. It must not write a second table or a second sum.
- Div uses `ProgressiveRules` for that class level. With the default table, points of 3, 9, 3, and 4 are aggregate 19, which is division 2.
- Psn is the learner's position for that sitting. It is calculated. The teacher does not type it. The sample numbers are layout only.

A counting set (one the mix includes with a weight) appears as its own calendar-month row and still feeds the End of Term combined total. Marks Gained stays the figure the report service already returns. The template does not add the month row into that total a second time. This double placement is the working assumption. Confirm it with the school before a build treats it as final. On the sample, May is that row: total 643, core aggregate 11, division 1, and the same 643 is Marks Gained. The sample position on that row is a layout placeholder, not a calculated rank.

## 4. End of Term ledger

Rows are subjects. Columns are:

| Column | Rule |
| --- | --- |
| Subject | Only subjects this learner offers. |
| Full Marks | Default 100. The school can change the default. The current Formal cell is the hardcoded number 100, so the default matches what already prints, but it has to become a setting. |
| Marks Gained | The mark from the counting sets, using the weighted total the PDF already uses. One counting set at weight 100 prints that mark, including a decimal when the service returns one. Several counting sets print the one combined figure, not a second sum of the columns. |
| Agg | Points for that subject from the same grading table. A missing mark that counts as zero is F9. |
| Comments | Prefill with the grade remark. The teacher can replace it with a sentence. Today Formal prints only the remark. |
| Initials | Prefill from the subject teacher's name. The teacher can correct them. |

Then one total line: Total of Full Marks (100 times the number of subjects on the ledger), Total of Marks Gained (the service total, not a fresh sum), and Division from English, Mathematics, Science, and Social Studies only. Each offered subject still has its own Agg letter on this ledger. Only those four enter the division. The app does not do that split today.

### Which subjects

The subject list is set per class level by the school, for the whole school, not per child and not by sniffing who happened to receive a mark.

Starter list for a lower primary level, which the school edits:

- Literacy I
- Literacy II
- English
- Mathematics
- Local Language
- Science
- Social Studies
- Music, Dance and Drama (short form MDD on the monthly grid only; the legend states the full name)
- P.E.
- Arts and Tech

A level that does not use a subject leaves it off the list. The sample learner is Primary Three and offers eight of those ten. Literacy II and Arts and Tech are off her card because she does not offer them. That is the rule under test: the card shows the offered list, not every subject the school teaches.

Upper primary can keep English, Mathematics, Science, and Social Studies as its starter list. The four names are defaults, not a hardcoded primary syllabus.

A subject on the offered list with no approved mark prints Missing once the set is approved and released. It is not dropped.

## 5. Footer

| On the card | Rule | Today |
| --- | --- | --- |
| Position in class | "5 out of 28 pupils", calculated, ties sharing a place. The class is every stream of that level in the school. | One position, often "8th" or "1st of 76". Not split into class and stream. |
| Position in stream | "2 out of 14 pupils", same ranking rule, scoped to the stream (`section_id`). | Not printed as its own line. |
| Next term begins | The next term's `starts_on`, from the school's calendar. | Prints on Formal, date only. |
| Next term ends | The next term's end date. | Does not print. |
| Class teacher's report | A short paragraph. Offer the six comment-bank lines for her band, once #1365 is wired. The teacher can type instead. | Formal prints `teacherComment` and a SIGN line. The bank is not connected. |
| Class teacher's signature | Name under a line. | The line exists. The name is not always filled. |
| Head teacher's report | Same pattern, from the head-teacher bank. | Formal prints `headTeacherComment`. |
| Head teacher's signature and stamp | A line, the name, and the stamp image when the school has uploaded one. Otherwise the words "Sign and stamp" and an empty box. | Formal prints "SIGN & STAMP" and the name. No stamp image. |

Position uses the ranking already stored for that level (aggregate or total). The sample ranks are not a calculation.

## 6. Grading

Use the UNEB-style defaults already on `main`. The school edits them on the grading screen. Do not ship Kabale's scale as a national default, and do not overwrite a scale they have already saved.

Primary defaults, points in brackets:

| Mark | Grade | Points | Remark |
| --- | --- | --- | --- |
| 95–100 | D1 | 1 | Excellent |
| 90–94 | D2 | 2 | V.Good |
| 75–89 | C3 | 3 | Good |
| 65–74 | C4 | 4 | F.Good/Q.Good |
| 60–64 | C5 | 5 | Promising |
| 50–59 | C6 | 6 | Fair |
| 45–49 | P7 | 7 | Work hard |
| 40–44 | P8 | 8 | Aim higher |
| 0–39 | F9 | 9 | More effort |

Division defaults, from `ProgressiveRules::defaultDivisionRanges()`:

| Aggregate | Division |
| --- | --- |
| 12 or less | 1 |
| 13–24 | 2 |
| 25–28 | 3 |
| 29–32 | 4 |
| 33 or more | U |

With exactly four subjects the lowest aggregate is 4, so "12 or less" and "4–12" label the same division 1. The product keeps "12 or less". A saved range replaces it for that school.

Division uses only English, Mathematics, Science, and Social Studies. Literacy, Local Language, Music, Dance and Drama, P.E., and Arts and Tech keep their own marks and, on the end-of-term ledger, their own Agg letter. They do not enter the division. The code on `main` still sums every subject that has points, so a build passes only the four cores into `division()`. It does not add a second division table.

A release copies the table onto the release. Later edits apply to the next release only.

## 7. Print rules

These are the same rules as the marks grid and the report-card PDF.

1. A subject that is not approved shows a dash and is left out of that set's total.
2. An approved blank shows Missing and counts as zero. ABS on an approved subject shows ABS and counts as zero.
3. An excluded set does not print. An unreleased set does not print.
4. Marks Gained and any combined line call the existing report service. They do not reimplement the weights. Opening 40, Middle 30, Closing 30 is a mix the school stores. It is not a formula in the template. The sample card has one counting set, so Marks Gained is that set's mark.

The grading key (the two tables above) prints on a sample pulled for the school to approve. A card that goes to a parent leaves the key off, matching the new-look rule. The three drawings include the key because they are samples.

## 8. Toshi guided fill

This walk is not built. Today a teacher can save one number for one learner on one exam (`TeacherActionService::enterMarks`, and the admin's `enterMark`). There is no step order and no read-back of a row. The walk below is the order to build. Each save goes through `ExamMarkWriter`. The chat does not write a mark by another path.

The class teacher is walked through one learner, subject by subject. An admin can start at step 1 and continue through step 12.

1. **Class.** Chips of the classes this person heads. Confirm the stream. An admin sees every class in the school.
2. **Term.** Prefill the current term and year. Show the phrase that will print: "End of Third Term, 2026". Ask them to confirm.
3. **Learner.** A list of names with KLS ids. The save uses the learner id.
4. **Subjects.** Show the list stored for that class level. Say which of them this learner offers. The teacher can remove one she does not take. They cannot add a subject that the level does not offer without an admin changing the level list.
5. **Sets.** Show the school's sets for this term, each with its exam date, whether it counts, and whether it is included. Name the ones that will not print, and why (excluded, or not released). Offer to date a set that has no date. BOT, MOT, and EOT appear here only when the school has not renamed them.
6. **Pick a set to fill.** Default to the earliest included set that is still open. Then, for that set, one subject at a time, in the level's order:
   - "English. Full marks 100. Reply with the mark, ABS, or Missing."
   - Read it back with the grade and the points: "English 78, C3, 3 points."
   - A reply of Missing is stored as an approved blank only after the set is approved. Until then the chat says it will print Missing and count as zero once the set is approved and released.
   - Next subject. Do not skip a subject on the offered list.
7. **Read the month row.** Total, aggregate, division, and the set name beside the month. Position is not asked. Say "Position is calculated when the class is released."
8. **Next set, or comments.** The teacher chooses another open set, or moves on. A counting set still asks for each subject's mark here. It will print as its own month row and in the End of Term ledger. That double placement is the working assumption in section 3, still to confirm with the school.
9. **End of term, subject by subject.** Show Marks Gained from the service. Ask for the comment, prefilled with the grade remark. Ask for the initials, prefilled from the subject teacher. Read the line back before the next subject.
10. **Class teacher's report.** Offer the six bank lines for the learner's band. The teacher picks one or types their own. Then "Sign with your name."
11. **Stop for a class teacher.** "The head teacher adds their report, signature, and stamp. An admin can approve and release."
12. **Admin only.** Approve or return each subject (a return needs a reason). Release the set. Head teacher's report, signature, and stamp. Then open the card. The card still hides any set that is not approved, released, and included.

If the person is a head teacher, they follow the admin path inside their own school.

The chat never asks the teacher to type a total, an aggregate, a division, or a position.

## 9. Sample arithmetic

So a reviewer can check the drawings without trusting the layout. Primary defaults. One counting set (EOT), weight 100, so Marks Gained equals the EOT mark. That same sitting is also the May month row. Practice in March is excluded and is not shown. Psn 3 on the May row is a layout sample, the same way Psn 6 and Psn 4 are. It is not a calculated rank.

February, BOT, not counting:

| Subject | Mark | Agg |
| --- | --- | --- |
| English | 78 | C3, 3 |
| Mathematics | Missing, counts as 0 | F9, 9 |
| Science | 81 | C3, 3 |
| Social Studies | 70 | C4, 4 |
| Literacy I | 66 | no Agg column |
| Local Language | 58 | no Agg column |
| Music, Dance and Drama (MDD) | 72 | no Agg column |
| P.E. | 80 | no Agg column |

Row total 78+0+81+70+66+58+72+80 = 505. Core aggregate 3+9+3+4 = 19. Division 2.

April, MOT, not counting:

| Subject | Mark | Agg |
| --- | --- | --- |
| English | 84 | C3, 3 |
| Mathematics | 71 | C4, 4 |
| Science | 88 | C3, 3 |
| Social Studies | 76 | C3, 3 |
| Literacy I | 74 | |
| Local Language | 63 | |
| Music, Dance and Drama (MDD) | 70 | |
| P.E. | 85 | |

Row total 611. Core aggregate 13. Division 2.

May, EOT, the only counting set. It is both this month row and the End of Term ledger below. Working assumption, still to confirm with the school. Psn 3 is a layout sample, not a calculated rank.

| Subject | Mark | Agg |
| --- | --- | --- |
| English | 90 | D2, 2 |
| Mathematics | 68 | C4, 4 |
| Science | 92 | D2, 2 |
| Social Studies | 85 | C3, 3 |
| Literacy I | 80 | |
| Local Language | 61 | |
| Music, Dance and Drama (MDD) | 77 | |
| P.E. | 90 | |

Row total 90+68+92+85+80+61+77+90 = 643. Core aggregate 2+4+2+3 = 11. Division 1. Sample Psn 3.

End of Term ledger for that same set:

| Subject | Full | Gained | Agg | Comment | Initials |
| --- | --- | --- | --- | --- | --- |
| English | 100 | 90 | D2, 2 | V.Good | AK |
| Mathematics | 100 | 68 | C4, 4 | Fair | JM |
| Science | 100 | 92 | D2, 2 | V.Good | RS |
| Social Studies | 100 | 85 | C3, 3 | Good | NL |
| Literacy I | 100 | 80 | C3, 3 | Good | PK |
| Local Language | 100 | 61 | C5, 5 | Promising | MA |
| Music, Dance and Drama | 100 | 77 | C3, 3 | Good | TB |
| P.E. | 100 | 90 | D2, 2 | V.Good | ZN |

Full marks 800. Marks gained 643. Core aggregate 2+4+2+3 = 11. Division 1.

The drawings put position in class as 5 out of 28 pupils and position in stream as 2 out of 14 pupils. Those two sentences are layout samples.

## 10. Questions for the school

Answered, and applied above:

- MDD means Music, Dance and Drama. The monthly grid keeps the short form. The end-of-term row, this spec, and the legend use the full name.
- Division uses only English, Mathematics, Science, and Social Studies.
- A counting set appears as its own calendar-month row and still feeds the End of Term combined total. Working assumption. Confirm it with the school before a build treats it as final.

Still open:

1. If two sittings fall in one month, are two rows right, or should that month be one row with both set names?
2. Is "position in class" every stream of that level, and "position in stream" the one stream?
3. Is the monthly Psn the class position or the stream position?
4. Who writes the per-subject comment: the subject teacher, or the class teacher speaking for the card?
5. Is Full Marks ever anything but 100, for example for Music, Dance and Drama or P.E.?
6. Does the serial number identify the sheet, the learner, or both? Can it stay blank?
7. Is "Nursery and Primary Day and Boarding" one subtitle for every card, including a nursery-only class?
8. For next term's dates, does the school type them, or adopt the national 2026 calendar as a starting point and edit?
9. Should parents see the grading key on the card, or only on the sample the school approves?
