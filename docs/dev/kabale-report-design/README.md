# Kabale Junior School — Progressive Scores card

Design and field spec only. This folder is the written spec. The preview drawings are not published here.

Checked on 17 November 2026 against `origin/main` at `169c092c` (Marks and report cards through head-teacher parity, #1325). The twelve pull requests named in the brief were still open drafts that day. Ten of those tips are already contained in that `main`. Two are not: #1349 (runbook) and #1365 (comment bank, calendar, young-learners preview).

## Files

| Path | What it is |
| --- | --- |
| `01-what-already-exists.md` | What those pull requests and current `main` already cover. Reuse this. Do not build it again. |
| `02-field-spec.md` | The field spec for the paper card, including the Toshi step order and the questions for the school. |
| `03-gaps.md` | Prioritised gaps between the card and what already exists, and the build order. |
| `04-configuration-and-toshi-skill.md` | The report-card configuration, what stays fixed, how a release freezes it, and the Toshi report skill. Kabale and an invented second school are written out as data. |

The HTML sample drawings and their preview images stay out of this repository. The spec still names them where a sentence needs the drawing: `designs/paper-faithful.html`, `designs/warm.html`, `designs/modern.html`, and `designs/lakeside-day.html`.

## Sample data

The three Kabale drawings use one invented learner, Amina Kisembo, Primary Three, Stream East, 2026. `designs/lakeside-day.html` uses a second invented learner, Tendo Mukasa, so the set-based card is not a renamed Kabale sheet. The marks were chosen for this sample. They are not a real child's marks. Positions on the drawings are placeholders so the layout has somewhere to put "out of N". A real card must calculate position. It must not copy these ranks.

The telephone numbers and the P.O. Box on the Kabale drawings are placeholders (`0700 000 111`, `0700 000 222`, `P.O. Box 100, Sample Town`). The real lines come from school settings. Lakeside Day School leaves both fields off. Do not paste a real number from a report PDF into this folder or into the repository.

The motto on the Kabale drawings is "Hard Work Pays". Lakeside's motto is "Learn and serve". Each is a school setting, not a string to hardcode.

March is missing from the sample on purpose. That sitting is an excluded set, so it does not print. May is both a monthly row and the end-of-term total. That double placement is a working assumption to confirm with the school. MDD on the monthly grid means Music, Dance and Drama. Division uses only English, Mathematics, Science, and Social Studies.
