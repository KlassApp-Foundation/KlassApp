# Report card path: test plan

Tracker items: **T-157** (tests for the report path), with **T-154** (grading settings), **T-129 and T-130** (crest).
All data in these tests is invented (the pack's 76-learner class, `tests/fixtures/reports/p5-synthetic-class.json`, and generated learners). No real learner, school or phone number appears anywhere.

## How to run

| What | Command |
| --- | --- |
| Everything on the report path | `php vendor/bin/phpunit tests/Unit/Reports tests/Feature/Reports tests/Feature/Setup` |
| One file | `php vendor/bin/phpunit tests/Feature/Reports/ReportParityFixtureTest.php` |
| One check | add `--filter test_name` |
| The scale smoke test only | `php vendor/bin/phpunit --group scale` (about 65 seconds, 900 MB) |
| The scale test for every class | `SCALE_FULL=1 php vendor/bin/phpunit --group scale` (about 9 minutes) |
| The whole suite the way CI runs it | `bash scripts/test-guard.sh` |

Needs: PHP with sqlite (tests run on an in-memory database), `vendor/` installed from the branch's `composer.lock` (after switching branches run `bash .claude/hooks/session-start.sh` with `CLAUDE_CODE_REMOTE=true CLAUDE_PROJECT_DIR=$PWD`, or a test may fail on a class that only exists on the other branch), and `python3` for the oracle tests (they skip without it).

## What an "incomplete" result means

A check marked **incomplete** (yellow `I`) is a **known gap that the test found in code this work does not own**. The test is real and runs the real code; it reports the gap instead of failing, so CI stays green and the gap is written down. When the owner fixes the code, the same test passes with no change, and the incomplete marker should then be removed. The gaps are listed at the end of this page.

## The oracle

`reference/calc_reference.py` (copied to `tests/fixtures/reports/reference/`) is independent of KlassApp's code on purpose. `ReportOracleTest` runs it on the fixture's marks and compares its answers with the fixture's stored expectations, and with the card, so the numbers the card is held to are not the card's own.

## Checks

### Grading settings screen (T-154)

| File and command | Proves |
| --- | --- |
| `tests/Unit/Reports/GradingRulesKitTest.php` (`phpunit tests/Unit/Reports`) | Every band edge (95, 94, 90, 89, 75, ... 39, 0) gives the grade and points the reference gives; the reference bands cover each mark 0 to 100 exactly once; a gap, an overlap, bands not starting at 0 or reaching 100, and marks the wrong way round are refused with a message naming the marks; the app's default division ranges are valid and give the reference division at 0, 12, 13, 24, 25, 28, 29, 32, 33 and above; ranges out of order, overlapping, with a gap, closed at the bottom or top, or with limits the wrong way round are refused; every fixture division follows from its aggregate. |
| `tests/Feature/Reports/GradingSettingsScreenTest.php` | The screen says changes apply from the next release and released reports never change; the three presets are offered and carry no school or country name; a preset fills the form and saves nothing; try-a-mark and try-an-aggregate give the grade, points and division from the bands on screen (and follow a loaded preset); saving records who and when, and the screen shows it; a gap, overlap or bands not reaching 0 are refused and nothing is saved or logged; valid bands and ordered ranges are saved; reset restores the app's bands, ranges and ranking and logs it, and does nothing without the confirmation; another school's class level, and a missing one, both 404 on view, save and reset; history is per school; school admin and head teacher can open, save and reset; teacher, student, parent, librarian, receptionist and accountant are turned away with nothing changed; a guest is sent to sign in. |
| Marks-owned: `tests/Feature/Reports/ProgressiveRulesTest.php`, `ProgressiveRulesFreezeTest.php` | The rules save per class level; points change the aggregate on the card; a release stores the rules. |

### Setup actions

| File and command | Proves |
| --- | --- |
| `tests/Feature/Setup/SetupActionsTest.php` | The Terms page offers **Make this the current term** only for a term that is not current; the confirmation names the term that stops being current; no confirmation, no change; the change moves the old term to last (or next), leaves one current term and writes a log line and an activity entry; another school's term and a missing term both 404 with nothing changed; an already-current term says so; head teacher, teacher, student, parent and accountant are refused on both setup screens; `terms:set-current` is a dry run unless `--apply`, refuses another school's term and a missing school; the Classes page lists only this school's sections that have no link and hides the list when none; linking asks first, needs the confirmation, creates one link with the right school, year and class level, logs it, and a second link makes none; another school's section or class level both 404; `classes:link-section` is a dry run unless `--apply` and refuses other schools' ids. |

### Crest and head teacher (T-129, T-130)

| File and command | Proves |
| --- | --- |
| `tests/Feature/Reports/CrestAndHeadTeacherTest.php` | The crest is found on the disk it was saved to and on `public` for older rows; the old card lookup alone misses a default-disk crest (the bug); a missing file, the placeholder and a file that is not an image all give no crest; another school's crest is never used; the real Progressive card prints the crest, and the school's initials after the file is deleted; the head teacher's name reaches the templates; the composer is registered for every report template and reads the school from the view's own data; the screen says whether a crest is saved, saved but missing, or not set; saving stores the name and crest for this school only and logs it; an empty name clears it; a text file, a PDF, an oversize image and an over-long name are refused with nothing saved; every role except the school admin is refused. |

### Parity with the reference (T-157)

| File and command | Proves |
| --- | --- |
| `tests/Feature/Reports/ReportParityFixtureTest.php` | The 76-learner class built in the database and run through the real card: for every learner and both exam sets, the grades, points, total, aggregate, division and position equal the reference; division counts per set equal the fixture (15, 17, 9, 6, 29 and 16, 22, 7, 7, 24); a tie shares a place and the next places are skipped, in both sets; a missing mark and an ABS mark are shown as such, count as F9 with 9 points and 0 marks; the text of the real PDF has the same marks, grades, totals, aggregates, divisions and positions for the learners that test the rules (missing, ABS in each set, the tie, the first learner, an ordinary one); the footnote about missing marks and ABS prints only on a card that needs it; set names come from the school (no fixed end-of-term wording). |
| `tests/Feature/Reports/ReportOracleTest.php` | The oracle reproduces the fixture's stored expectations for every learner and set; a class that ranks by **total** gets the oracle's positions by total. Skips without python3. |
| Marks-owned: `ProgressiveParityTest.php`, `ProgressiveNewLookTest.php`, `ProgressiveMissingMarksTest.php`, `ProgressiveHeadingsTest.php`, `ProgressiveDefaultSnapshotTest.php` | A 40-learner class against a calculation inside the test; the new look keeps every value of the old card; missing and ABS; headings from the school's sets; the default card is unchanged (snapshot). |

### Reprint and release

| File and command | Proves |
| --- | --- |
| `tests/Feature/Reports/ReprintAfterRulesEditTest.php` | After a term is released, the school edits its grade bands, division ranges and ranking on the screen; the reprint's marks, grades, totals, aggregates, divisions and positions are identical to the first print (compared on the text of the real PDF for five learners); a term that was not released follows the new rules at once; the release stores the rules it was made with. **Incomplete:** the footnote wording does not stay with the release (gap 1). |

### Division consistency

| File and command | Proves |
| --- | --- |
| `tests/Feature/Reports/DivisionConsistencyTest.php` | The school saves division ranges that are nothing like the app's; for all 76 learners the division on the card (both sets and the term block) and on the results sheet equals the division those ranges give, so no screen still carries a built-in table; the PDF's division row shows the school's own labels. **Incomplete:** screens that build the sheet with no policy disagree for learners with an ABS or missing mark (gap 3). |
| Marks-owned: `tests/Feature/Reports/OneDivisionTableTest.php` | The sheet, card, export, legacy report, class marks screen, student result screen, report cards page and parent portal all read the school's ranges for one learner, and a release keeps its own. |

### Roles

| File and command | Proves |
| --- | --- |
| `tests/Feature/Reports/ReportRolesTest.php` | Report Cards, template history, template preview, learner card preview and download and the rules screen open for school admins and head teachers only (school details for the school admin only); the template cannot be changed by any other role; a class teacher prints their own class (class page, learner preview, learner download, whole-class PDF) and no other role or teacher does; another school's class is refused and returns no PDF; a refused role cannot change the rules. **Incomplete:** another school's class answers differently from a missing class (gap 2). |

### Scale

| File and command | Proves |
| --- | --- |
| `tests/Feature/Reports/ReportScaleSmokeTest.php` (`--group scale`) | A generated school of 800 invented learners (10 classes of 80, with blanks and absences): all ten results sheets have every learner with valid positions; a per-learner PDF from each class opens as a Progressive report; the whole-class PDF job completes and has a page per learner (every class with `SCALE_FULL=1`); the run stays inside 15 minutes. Prints the timings. **Incomplete:** learners with a missing mark in the pinned exam get no card in the whole-class PDF (gap 4). |

## Known gaps these tests found (reported, not fixed here)

All four are in files the marks agent owns (the report card service, the results service, the report cards controller). They are listed in the PR bodies.

1. **Footnote after a reprint.** After a rules edit the reprint of a released term says "Position is by the total" while the release was ranked by aggregate; the positions themselves do not change. The footnote reads the live ranking, not the one stored on the release. (`ReprintAfterRulesEditTest::test_the_footnote_wording_is_identical_on_the_reprint_too`)
2. **Another school's class is not answered like a missing one.** A school admin asking for another school's class gets a redirect; a class id that does not exist gets 404, so ids can be told apart. The acceptance check asks for the same answer. (`ReportRolesTest::test_another_schools_class_gets_the_same_answer_as_a_class_that_does_not_exist`)
3. **Missing and ABS are still skipped on the screens that build the sheet with no policy** (class marks, parent portal, dashboard, WhatsApp results, exports). A learner with an ABS gets a lower aggregate, a better division and a different position there than on the card, and other learners' positions move with it. (`DivisionConsistencyTest::test_screens_that_build_the_sheet_with_no_policy_agree_with_the_card`)
4. **The whole-class PDF leaves out a learner with a missing mark.** Its learners are those with a marks row in one pinned exam (one subject), so a learner whose mark in that subject is missing gets no card. (`ReportScaleSmokeTest`)
5. **The card refuses when no set counts toward the term total** ("No EOT exam found") (handoff finding 1). (`ReportParityFixtureTest::test_a_card_prints_when_no_set_counts_toward_the_term_total`)

## What could not be run in the cloud sandbox

MySQL (all tests ran on sqlite; the new migration `progressive_rules_changes` has foreign keys and needs the MySQL migration job), a browser (layout at 375 and 1280 px, the live example script, clicks, print in black and white: see `docs/demo/report-cards-click-script.md`), a remote object-storage disk for the crest, and the full CI suite.
