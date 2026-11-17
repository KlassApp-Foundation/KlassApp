# Report cards: click script

For the person who clicks. Tracker items: T-154, T-129, T-130, T-157. Nothing here has been seen in a browser by the person who built it: the screens were built and tested in a cloud session with no browser, so **every "you should see" below is a claim to check, and anything that looks wrong or different is a finding to write down**.

Use a made-up school only (for example the demo school), never a real one, and never a real child's data. Use your own password from your own notes; do not paste or save it anywhere.

Check each screen at **375 px** wide (a phone) and **1280 px** wide, and at 375 px check there is no sideways scroll. Every button and link should be easy to hit (about 44 px tall).

Sign in as a **school admin** unless a step says otherwise.

## A. Grading settings (T-154)

Path: **Report Cards** page, then the link **Progressive report rules**. Address: `/admin/reports/cards/progressive-rules`.

1. Open the screen. **You should see:** the heading "Progressive report rules"; a green note "Changes you save here apply from the next release. A report that has already been released keeps the rules it was released with and never changes."; a line "Not changed yet. This screen is showing the app defaults."; a link "School details for report cards"; a select "Start from a preset" with **App default**, **Older seeded scale (confirm with the school)**, **Custom**; two boxes **Try a mark** and **Try an aggregate**; the ranking choices; the division table; and at the bottom **Save progressive report rules** and a section "Go back to the app defaults". There should be no school name and no country name anywhere on the screen.
2. In **Class level** choose a class level (for example "primary"). **You should see:** a **Grade bands** table appear (grade, points, from, to, remark).
3. In **Try a mark** type `95` and press **Show** (or just type). **You should see:** "95 is grade 1, 1 point." (the label of the grade depends on the school's bands). Try `0` (grade with 9 points), `100`, and `150` ("150 is not in any band. Marks run from 0 to 100."). With scripts on, the answer should change as you type, before pressing anything.
4. In **Try an aggregate** type `12`, then `13`, `33`. **You should see:** division 1, division 2, division U (with the app's ranges).
5. Change a band in the table (for example make a grade's "From" 70 instead of 75) and type a mark inside the changed range in **Try a mark**. **You should see:** the answer follows what you typed, without saving.
6. Press **Save progressive report rules** with that gap left in. **You should see:** a red message saying the bands must run from 100 down to 0 with no gaps and no overlaps, naming the marks that are in no band, and nothing saved ("Last changed" line unchanged).
7. Put the band back, then change two bands consistently (one band's "To" down by 2 and the band above it "From" down by 2). Press **Save**. **You should see:** a message "Progressive report rules saved. This applies from the next release; reports already released keep the rules they were released with." and the line at the top now says "Last changed by YOUR NAME on TODAY'S DATE".
8. In **Start from a preset** choose **Older seeded scale (confirm with the school)** and press **Load preset**. **You should see:** an amber note: "'Older seeded scale (confirm with the school)' is filled in below but not saved yet. Check it, then press Save." and the table shows different bands (for example 80 to 89 for the third grade). Nothing is saved until you press Save. Reload the page without saving: the table is back to what was saved.
9. In the division table untick **Use the automatic ranges** and make the ranges overlap (for example set the second range to start at the same number the first one ends). Press **Save**. **You should see:** a red message that division ranges must not overlap and must go from the lowest aggregate to the highest. Make a gap (skip a number) instead: a message naming the aggregates that belong to no division. Make the first range have a "From" value: a message that the first division should have no lower limit.
10. Scroll to **Go back to the app defaults**. Press **Reset to defaults** without ticking the box. **You should see:** an error asking you to confirm, and nothing reset. Tick **Yes, reset these rules to the app defaults** and press **Reset to defaults**. **You should see:** "Back to the app defaults. This applies from the next release...", the bands and ranges back to normal, and the "Last changed by" line mentioning "Back to the app defaults".
11. Sign in as a **teacher** (not a head teacher) and open the same address. **You should be turned away** (sent to the teacher dashboard), and you should not be able to change anything. Repeat for a student or parent account if you have one.
12. At 375 px: the two "Try" boxes stack, the bands and division tables turn into stacked cards with a label above each field (From, To, Points...), and nothing needs sideways scrolling.

## B. Make a term the current term

Path: **Settings**, card **Academic terms**. That card opens the *add a term* form, not the list; the list with the new button is at `/admin/academic-term` (open that address directly, and note whether any link on the form leads to it: it is a finding if not).

1. **You should see:** a table of terms with a **Status** column. The current term says **Current term**. Every other term has a button **Make this the current term**.
2. Press **Make this the current term** on a term that is not current. **You should see:** a page "Make TERM the current term" with the sentence "Make TERM the current term? OLD TERM stops being the current term and becomes a past term" (or "a coming term"), a tick box **Yes, make this the current term**, the button **Make this the current term** and **Cancel**.
3. Press the button **without** ticking the box. **You should see:** an error and nothing changed (go back to the terms list: the same term is still current).
4. Tick the box and press the button. **You should see:** the list again with "TERM is now the current term." and the **Current term** label has moved. Open the same term's make-current address again: it should say it is already the current term and offer nothing to change.
5. Sign in as a head teacher, a teacher and an accountant and try the address `/admin/academic-term`: they should not be able to change the current term.

## C. Link a section to a class

Path: **Classes**. Address: `/admin/classes`. (You need a section that has no class link: the demo school may have none; ask for one to be prepared, or add a section first on the Classes setup screen.)

1. **You should see:** above the class list, a yellow box "N sections are not linked to a class for YEAR, so they cannot hold learners yet", with each section name and a button **Link to a class**. If every section is linked, the box does not appear.
2. Press **Link to a class** on a section. **You should see:** a page "Link SECTION to a class" with a **Class** select and **Continue**.
3. Choose a class and press **Continue**. **You should see:** the sentence "Link SECTION to CLASS for YEAR? Learners can then be added to it.", a tick box **Yes, link this section**, **Link section to class** and **Cancel**.
4. Press **Link section to class** without ticking: an error, nothing linked. Tick it and press it again. **You should see:** back on the Classes page, "SECTION is now linked to CLASS for YEAR." and the section is no longer in the yellow box (and appears in the class list).
5. Open the same link page for the same section again and choose the class: it should say the section is already linked and nothing needs doing.
6. Head teacher and teacher accounts should not be able to open `/admin/classes/link-section/ID`.

## D. School details for report cards: crest and head teacher (T-129, T-130)

Path: **Progressive report rules** screen, link **School details for report cards**. Address: `/admin/school-identity` (school admin only).

1. **You should see:** a field **Head teacher's name**, a line saying whether a crest is saved ("No crest yet..." / "A crest is saved..." / "A crest was saved, but the file can no longer be found..."), a file chooser **Crest (a PNG or JPG, up to 2 MB)** and **Save school details**.
2. Type a made-up head teacher's name, choose a small PNG, press **Save school details**. **You should see:** "School details for report cards saved." and the line now says a crest is saved.
3. Try to upload a text file or a PDF: a red message about the crest and nothing saved. Try a name longer than 120 characters: a red message.
4. Open a report card for a learner (**Report Cards**, a class, **preview** a learner) with the **Progressive (new look)** template selected. **You should see:** the crest in the header circle. With no crest saved (or after a deploy removed it), the circle shows the school's initials instead. The head teacher's name is not printed by this work: the card owner prints it (check with them).
5. Sign in as a head teacher or teacher: `/admin/school-identity` should not open.

## E. Things to look at on the report card (T-157, for the person checking the card)

These are the cases the automated tests use. Use the demo school with the pack's invented class (Pupil 001 to 076).
- Pupil 076, first set: two subjects print **Missing** with grade **F9**; a footnote "Missing marks and ABS count as 0 (grade F9, 9 points)..." appears **only** on cards that need it (Pupil 001 has no footnote).
- Pupil 010 (second set Science) and Pupil 033 (first set English): the cell prints **ABS** with grade **F9**.
- Pupil 041 and 042: the same position in the second set ("53rd of 76" for both), and nobody holds the next places.
- Position is "Nth of 76"; the aggregate, division and total rows match the numbers in the pack's fixture (`fixtures/p5-synthetic-class.json`).
- After a term is released, edit the rules on screen A and print the same learner again: the marks, grades, totals, aggregate, division and position must not change.

Write down anything that differs from what is described, with the screen, the width and what you saw.
