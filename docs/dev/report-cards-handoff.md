# Report cards handoff

Updated 15 November 2026. This file is the living handoff for the report-card work. The packs in the download folder are the source the work started from. Sections 14 and 15 are not in the re-delivered handoff.

## Where the code is

Worktree `/Users/mac/projects/KlassApp-wt-report-type`, branch `feat/report-card-defects`, based on the tip of draft pull request 1358. The local UI is `http://127.0.0.1:8148` with the sqlite file `database/report-cards-ui.sqlite`. Do not use ports 8000 or 8126. Do not start a queue worker. Do not write `knowledge.md`. Do not commit `storage/fonts/`.

## Done in this branch

The six card defects, the Incomplete line, and the guard for a weighted set that has not been released. Tests cover the refusals and the new sentences. The scale check prints one page per learner for a class of 80, including learners with a missing mark in the pinned subject.

Clicked the report list as the school admin at 375 and at 1280, then downloaded one card. The list and the card say Incomplete and name the counting set. The arithmetic still counts the absence as 0.

## Not done

1. Make the numeric primary table the default for a new primary standard, with no country or school name in the label. Do not reseed schools that already have bands. Do not treat a school's own scale as confirmed.
2. Report builder: any one set or any combination, either report type, with the per-set block.
3. Comment bank: at least five options per section, school override, restore defaults, a released card keeps its text.
4. Calendar import from the official circular, on staging first, using the current-term action that already exists.
5. Read-only production check for backups and object storage. No production write.
6. Full rehearsal, including the 76-learner fixture inside the parity run, both templates, clicks at 375 and 1280.
7. Merge the named draft order. Do not force. Production deploy only after the read-only check is clean and Rasta says so.

## Conflicts to remember

The handoff's 90–94 table is not confirmed for any school. The product seed still uses 85–94 for 2 points. The latest instruction asks for the 90–94 numbers as the primary default, and also forbids country and school names in product copy. Use the numbers. Do not label the preset with a country or a school.

Formal and Warm still print MONTHLY RESULTS and END OF TERM. The new look does not. Leave the old headings until Rasta says to remove them.

Nothing goes to a school or a parent until grading is confirmed in writing.
