# What already covers this card

Read this before anyone builds the Kabale card. The brief was to reuse the open work, not to draw a second report engine.

## How this was checked

On 17 November 2026 the twelve pull requests were still **open drafts** on GitHub. Their tip commits were then compared with `origin/main` at `169c092c`. A tip that is an ancestor of that commit is already in `main`, even though the pull request was never closed.

| PR | Title | Tip | In `main` at `169c092c`? |
| --- | --- | --- | --- |
| #1343 | Let each school set progressive points, divisions, and ranking | `75acbdc3` | Yes |
| #1345 | Name columns after each school's own exam sets | `f6c8b12e` | Yes |
| #1346 | Check points, division, and position against an independent calculation | `8e0fefc7` | Yes |
| #1347 | Store the progressive rules on each release | `2ecfc388` | Yes |
| #1348 | Use one division table on every results screen | `3c37f28e` | Yes |
| #1349 | Kabale report-card runbook and rehearsal scripts | `7b915bc5` | No |
| #1351 | Order exam-set columns by the exam date | `bd609410` | Yes |
| #1352 | Let a release tell only a chosen list of parents | `9947a258` | Yes |
| #1353 | Progressive (new look) template | `2e38dc6a` | Yes. `main` also has a later spacing commit, `fa86937f` |
| #1355 | One grading settings screen | `6b8869bd` | Yes |
| #1361 | 90 to 94 is 2 points on a new primary standard | `1aa69db5` | Yes |
| #1365 | Comment bank, 2026 school calendar, young-learners preview | `b86997c7` | No |

Do not merge these drafts on top of `main` to "get the card". For the ten that are already contained, merging the stale draft would only risk a replay. #1349 and #1365 are the two that still add files `main` does not have, and they are documents and a static preview, not the card calculator.

`main` has moved on past several of these tips. The notes below describe the code that is on `main` now, read from the tree at `169c092c`, not only the pull-request text.

## Already on main, and what it gives the card

**Points, divisions, and ranking (#1343, #1348, #1355, #1361).** Grade points live on the school's grading rows. The screen is `/admin/reports/cards/progressive-rules`. Presets, a live "try a mark", reset, and a last-changed note are there. A class level can override the school. `ProgressiveRules::defaultDivisionRanges()` is the table to keep: 12 or less is division 1, 13–24 is 2, 25–28 is 3, 29–32 is 4, 33 or more is U. Ranking is by aggregate (lower is better) when the level uses points, or by total when it does not, unless the school stored a choice. The same division table is what the other results screens use. `config/grading_uganda.php` already defaults a new primary level to D1 95–100 (1 point), D2 90–94 (2), C3 75–89 (3), then C4 through F9. Opening the screen does not rewrite bands the school has saved. Reset does. No school has confirmed its own scale. Do not add a second points table.

**A release freezes the rules (#1347).** `marks_releases.progressive_rules` stores the bands, ranges, and ranking used for that release. `ProgressiveRulesStamp` writes it. A later edit does not redraw a released card. An older release whose `progressive_rules` is null still follows the default division and automatic ranking; grades on a reprint of that older release still follow the bands saved now. That gap is documented in the release work. Do not invent a second freeze.

**Columns are the school's own sets, in exam-date order (#1345, #1351).** The card does not look for the words Beginning, Mid, or End. A set that does not count toward the term total sits under MONTHLY RESULTS, headed with the set's name. A set that counts sits in the END OF TERM block, headed with the set's name. Order inside each block is `scheduled_at`, then exam id. A set with no mark and no absence in the class is left out. When more than one set counts, each column keeps its own total and the combined total is the weighted result the term already uses. There is no calendar-month row. The field spec now asks for one, and for a counting set to appear both as that row and in the end-of-term total. The code does not do either yet. BOT, MOT, and EOT are not special words in this code.

**The weighted total is one calculation.** The report card reads the result row's total. It does not add the set totals again. A sample used in the headings work is 30% of 60 and 70% of 80, which is 74, not 140. The Kabale card must call that same service for Marks Gained and for the combined line. A second formula would drift, which is the bug the per-student page just had (326 against 326.4).

**Checked arithmetic (#1346).** An independent recalculation covers points, division boundaries, ties, Missing, and ABS, on a made-up class. Keep that approach for any new month-row total. Do not replace it with a hand-added column.

**Three looks already exist (#1353, plus Formal and Warm).** `StudentReportCardService` offers `formal` (label Standard, the default), `warm`, and `progressive` (label New look). Formal still prints MONTHLY RESULTS and END OF TERM as set columns. Warm is the same information in the warm skin. The new look is one A4 page for up to six subjects and three sets, each set a Marks and Grade pair, in exam-date order. Crest is the school logo, or the school's initials when there is no logo. Missing and ABS print. SAMPLE FOR APPROVAL and the grading key appear only on Open sample. A term-total line appears when more than one set counts. The new look does not print the school's phone numbers or centre number. A school admin or head teacher picks the template under Report Cards. Formal stays in use until they save another.

**Who the card already knows.** `resolveSchoolIdentity` reads the school row and school details: name, a category subtitle, one address line, two phone fields joined as "Tel: …", UNEB centre number, and motto (`schools.motto`, with the old `school_details.moto` as fallback). The logo is `school_details.school_logo`. The category subtitle is a fixed map: Nursery, Primary, Nursery And Primary, O-Level, O-Level And A-Level. There is no "Day and Boarding" line, no P.O. Box, and no card serial. `schools.pincode` is a postal-code field from the older product. It is not a P.O. Box and must not be reused as one without the school saying so.

**What the formal sheet prints today**, from `formal.blade.php` on `main`:

- Header: logo, school name, category subtitle, address, telephone line. Title PROGRESSIVE REPORT. The term line uppercases the stored term and turns a trailing digit into a Roman numeral, then adds the year. A stored "Term 3" in 2026 becomes "TERM III 2026". It does not become "End of Third Term".
- Learner line: student name, class, KLS id (`registration_number`).
- Monthly block: one column per non-counting set, plus an AGG column when the level uses points, then a total row, a position row, an aggregate row, and a division row. The row is the subject. The column is the set. This is the opposite of a month row.
- End of term: Subject, Full Mark (the cell is the hardcoded number 100), one column per counting set, AGG, a comment, and teacher initials. The comment is the grade remark ("Good", "V.Good"), not a sentence the teacher typed. Initials come from the subject teacher's name. The total row prints the combined total and the division.
- Next term begins, from `starts_on` only. There is no end date on the card.
- Class teacher's comment, SIGN. Head teacher's comment, SIGN & STAMP, and the head teacher's name when it is known.
- Motto, then a footer of school name, UNEB centre, and telephone.

**Print rules that the card must keep, from the marks work already on the branch under review.** A subject that is not approved is a dash and is left out of that set's total. An approved blank prints Missing and counts as zero when the release says missing-as-zero. ABS on an approved subject prints ABS. An excluded set and an unreleased set do not count in the combined line. The combined figure is the report service's figure, including the decimal. Those gates are the grid and the PDF. This spec does not add another one.

**Pilot messaging (#1352).** A release can message a chosen parent list (`pilot_recipients` on the release). That is who receives the card. It does not change the card.

## Not on main

**#1349, the runbook.** Docs and rehearsal scripts only. It is the operator order: learners, exams and settings, marks import, release, cards, a small WhatsApp send, with a check and a rollback after each step. The rehearsal it describes used a synthetic Primary Five, 76 learners, 2 sittings, 4 subjects. Treat those counts as rehearsal notes, not as this school's live register, and do not copy them into a public document as if they were a census. The runbook also recorded blockers at the time it was written. Some of those now have code on `main` (rules stored on the release, a pilot recipient list, set names and dates, position on the new look). This pass did not re-run the rehearsal, so it does not claim those blockers are closed. One warning still matters for the design: a term with no counting sitting used to render no card at all ("No EOT exam found"). The month-row card must still render when the only sittings are monthly, and it must say so rather than looking blank.

**#1365, content files.** Not wired to a route or a template.

- `docs/data/comment-bank/defaults.json`: `teacherComment` and `headTeacherComment`, six lines in each of six bands, including a missing-marks line. No digits and no gendered pronouns in those lines. Thresholds are not in the file.
- `docs/data/school-calendar/uganda-2026.json`: a national starter calendar. Third Term in that file runs 14 September 2026 to 4 December 2026. It is not this school's own term dates. The card should print the dates the school saved.
- `resources/views/reports/young-learners-preview.html`: a static nursery-style preview with a made-up learner. Not served.

## What this means for the new card

The paper card is a new arrangement of fields the product mostly already stores. The pieces to reuse are the grading screen, the division table, the release stamp, the school's own set names, exam-date order, the single weighted total, the three existing skins as the place a fourth arrangement would be chosen, and the identity fields that already exist. The pieces that do not exist yet are listed in `03-gaps.md`. The field-by-field target is `02-field-spec.md`.
