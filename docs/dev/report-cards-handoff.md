# Report cards handoff

Updated 16 November 2026. This file is the living handoff for the report-card work. The packs in the download folder are the source the work started from. Section 15 was supplied after the re-delivered file, which still ends at section 13.

## Where the code is

Worktree `/Users/mac/projects/KlassApp-wt-report-type`, branch `feat/head-teacher-marks-release`. Draft pull request 1362 is stacked on draft pull request 1361, which is stacked on draft pull request 1359. The local UI is `http://127.0.0.1:8148` with the sqlite file `database/report-cards-ui.sqlite`. Do not use ports 8000 or 8126. Do not start a queue worker. Do not write `knowledge.md`. Do not commit `storage/fonts/`.

## Done in this branch

The six card defects, the Incomplete line, and the guard for a weighted set that has not been released. Tests cover the refusals and the new sentences. The scale check prints one page per learner for a class of 80, including learners with a missing mark in the pinned subject.

Clicked the report list as the school admin at 375 and at 1280, then downloaded one card. The list and the card say Incomplete and name the counting set. The arithmetic still counts the absence as 0.

## Not done

1. Report builder: any one set or any combination, either report type, with the per-set block.
2. Comment bank: at least five options per section, school override, restore defaults, a released card keeps its text.
3. Calendar import from the official circular, on staging first, using the current-term action that already exists.
4. Read-only production check for backups and object storage. No production write.
5. Full rehearsal, including the 76-learner fixture inside the parity run, both templates, clicks at 375 and 1280.
6. Merge the named draft order. Do not force. Production deploy only after the read-only check is clean and Rasta says so.

## Conflicts to remember

The primary default for a new primary standard is now 90 to 94 for 2 points and 75 to 89 for 3 points. The preset label stays "App default". Opening the grading screen does not rewrite bands a school already stored. Reset does. No school has confirmed its own scale.

Formal and Warm still print MONTHLY RESULTS and END OF TERM. The new look does not. Leave the old headings until Rasta says to remove them.

Nothing goes to a school or a parent until grading is confirmed in writing.

## 15. Defects found by the new tests

These six are the defects on draft pull request 1359. The card tests cover them.

1. A reprint after a rules edit keeps the position sentence stored on the release. Positions do not move.
2. Another school's class and a missing class both answer 404.
3. A results sheet with no policy of its own counts a blank the same way the card does. A learner is not shown as aggregate 8 on that sheet and 17 on the card.
4. The whole-class PDF includes a learner who has no mark in the pinned subject. The scale check is one page per learner for a class of 80.
5. The card prints when no set counts toward the term total, and says that no combined mark applies. The refusal "No EOT exam found" is gone (T-150, T-153).
6. The exam edit page answers 404 when the exam is not in the signed-in school (found on proof 1334).

The nine bands in the handoff match the demo junior seeder, lines 113 to 123. Draft pull request 1361 makes a new primary standard start at 90 to 94 for 2 points and 75 to 89 for 3 points, which matches those bands. Do not run the restructure command. Do not reseed a school that already has bands.

The captured progressive card was refreshed for spacing only. The marks, the aggregate 7, and division 1 are unchanged.

A head teacher of a school can open Marks Release and release inside that school. A head teacher of another school cannot see the release. A class teacher still cannot release another class or the whole school. Teacher routes stay closed to a head teacher.
