# Gaps, in the order to close them

The card in `02-field-spec.md` compared with `origin/main` at `169c092c` and with the two drafts that are not in that commit (#1349, #1365). Items already shipped are in `01-what-already-exists.md` and are not repeated here as work.

## P0 — decide before anyone builds the ledger

1. **Month rows do not exist.** Formal, Warm, and New look all put a subject in a row and a set in a column. #1345 removed the old month heading on purpose. #1351 orders those columns by exam date and does not group them into months. The new ledger is one row per calendar month, subjects across. Reuse the date sort and the set names. Do not add a second total. A counting set appears as its own month row and still feeds the End of Term combined total. Marks Gained stays the report-service figure. That double placement is the working assumption, still to confirm with the school.

2. **Division from the four core subjects is decided, and the code does not do it yet.** The rule is English, Mathematics, Science, and Social Studies only. Other offered subjects still print, and on the end-of-term ledger they still have their own Agg letter. The aggregate on `main` adds every subject that has points. A build must pass only those four into the existing division function. Do not write a second calculation, and do not claim the app does this today.

3. **The term phrase is different.** A stored "Term 3" prints "TERM III 2026" on Formal. The card wants "End of Third Term". The stored name should stay. Only the printed phrase changes.

4. **A term with only monthly sets must still produce a card.** The runbook (#1349, not on `main`) recorded "No EOT exam found" when nothing counted toward the term total. This card's monthly ledger is exactly that case. Confirm the current service no longer refuses it, and add a test if it still does. This pass did not re-run that rehearsal.

## P1 — fields the header and footer still lack

5. **No "Day and Boarding" subtitle.** The category map cannot store that sentence. It needs its own text setting.

6. **No P.O. Box.** `schools.pincode` is not that field.

7. **No optional serial.** KLS id is a different number and already prints on the new look.

8. **Next term has a start and no end** on the card. Both dates exist as term dates once the school saves them. #1365's national calendar is a starter file, not wired, and it is not this school's calendar.

9. **One position, not two.** The card wants position in class and position in stream, each "out of N pupils". Ranking basis already exists. The stream scope (`section_id`, never `standard_id` alone) does not have its own line. The monthly Psn column is the same gap, once per sitting.

10. **No stamp image.** Formal prints the words "SIGN & STAMP".

## P2 — subjects, comments, and the list behind them

11. **No per-level offered-subject list.** The card shows a subject when a mark or a counted blank exists. Lower primary's Literacy I and II, Local Language, Music, Dance and Drama, P.E., and Arts and Tech are not a stored list. Without that list, a child who offers Music, Dance and Drama but has no mark yet disappears from the card instead of showing Missing. The monthly grid may shorten that subject to MDD, with the legend giving the full name.

12. **The end-of-term comment is the grade remark**, not a sentence, and the initials are derived from the subject teacher. The spec wants both prefilled and editable.

13. **Full marks are hardcoded to 100** in the Formal cell. The decision is 100 as the default, editable. The cell has to read the setting.

14. **The comment bank and the calendar file are not connected** (#1365). The Toshi walk and the footer both want them. Wiring the file is not the same as inventing new sentences.

## P3 — the walk and the three skins

15. **There is no guided fill, and there is no configuration object.** Toshi saves one mark for one learner on one exam. The schema, the defaults, the validation, and the natural-language edits are in `04-configuration-and-toshi-skill.md`. Section 8 of the field spec is the guided order. A mark still goes through `ExamMarkWriter`. A total, an aggregate, a division, or a rank still comes from the existing services. The chat shows a preview and waits for confirmation before a write. Every confirmed write is audited. The skill does not turn Toshi on.

16. **The three drawings are not the three templates, and Kabale is not a special case in the renderer.** Formal, Warm, and New look already cover "set columns, three skins". Kabale's drawings are one arrangement (month rows, then an end-of-term subject ledger) in three skins. `designs/lakeside-day.html` is the same schema with the monthly ledger off, set columns on, different subjects, and different wording. Adding either school as its own template would duplicate the arrangement. Build one renderer that reads the configuration, then offer the skin the way Report Cards already offers Formal, Warm, and New look.

17. **Do not rebuild grading, division, release freeze, date order, or the weighted total.** They are on `main`. A new template that adds its own weights will recreate the 326 versus 326.4 bug.

## P4 — leave for after the card prints

18. **#1349's other rehearsal notes** (importer blanks, WhatsApp file names, pilot audience) are operator work. #1352 already added a chosen recipient list on `main`. The rehearsal was not repeated here, so those notes stay open until someone runs them again.

19. **The young-learners preview** (#1365) is a static nursery page. It is not this primary card. Nursery ratings already have their own block on Formal.

20. **Parent delivery** is the pilot release, not a layout change.

## Suggested build order, after review

1. School confirms the questions still open in the field spec, and confirms the working assumption that a counting set is both a month row and the end-of-term total. Division from the four cores, and Music, Dance and Drama, are decided. Those answers are stored on the configuration, including `confirmed: false` where the school has not answered. They are not a Kabale-only branch.
2. The configuration schema in `04-configuration-and-toshi-skill.md`: sections, header fields, term wording, the monthly ledger, the end-of-term ledger, subjects per level, the division list, full marks, footer lines, and the skin. Defaults and validation are in that file. Grading bands, division ranges, report kind, the scoring rule, the progressive-rules copy, and the mix are reused from `main`. The new piece is a template-version freeze beside the four copies a release already keeps.
3. Prove the schema with two configurations before wiring a renderer. Kabale is the monthly ledger. `designs/lakeside-day.html` is an invented set-based school with the monthly ledger off, other subjects, and other wording. A third school that needs its own code path means the schema is wrong.
4. One renderer that reads the configuration. Month rows only when that section is on, and only from the exam date. Set columns when the monthly ledger is off. Totals, aggregates, divisions, and ranks come from the existing services. Tests first, with invented learners.
5. The header, footer, subject list, full-marks default, and term phrase, as the settings the schema already names. Prefill the defaults. Do not present a blank form.
6. The Toshi report skill in `04`: the guided fill from section 8 of the field spec, then natural-language edits. Preview and confirmation before any write. Toshi does not calculate. Every confirmed change is audited. The skill stays off until the school opts in.
7. Wire the comment bank when the walk asks for it. Leave #1349 and #1365 unmerged until that review; do not merge the ten drafts whose tips are already in `main`.
