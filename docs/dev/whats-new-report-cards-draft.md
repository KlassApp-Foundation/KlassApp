# What's new: report cards (draft for schools)

**Draft.** This page is written ahead of the release. Each item says which pull request it waits on. Nothing here is on a school's screens until that pull request merges.

## Choose your report type

**Draft until #1342 merges.**

Open **Report cards** and find **Report type**. A school chooses one:

- **Progressive report** — the formal card titled PROGRESSIVE REPORT, with a monthly results table and an end-of-term table.
- **Set-based reports** — each exam set has its own report, and one combined report uses the weights for the sets that count.

Press **Save report type**. The change applies from the next release; a report already released keeps its type.

## Set your own grade rules

**Draft until #1343 and #1355 merge.**

**Report cards → Progressive report rules** lets a school admin or head teacher set the grade points, the division ranges and the ranking. Choose **All classes** or one class level. Presets fill the form without saving. **Try a mark** and **Try an aggregate** show the answer before you save. **Reset to defaults** puts the rules back. **Last changed by** shows who saved last.

Confirm your school's grade scale in writing before reports go out.

## Missing marks and ABS

**Draft until #1344 and #1350 merge.**

A mark that was never entered and an absence both count as **0** — grade **F9**, 9 points on the default scale. The card prints **Missing** or **ABS**, never a 0. A set with no marks at all is left off the card.

## Your own exam-set names, in date order

**Draft until #1345 and #1351 merge.**

Report columns use the names your school saved on **Exam Type Settings**, not fixed "mid term" or "end of term" labels. Columns follow the field **Scheduled Date & Time** on the exam form: earliest first, a set with no date last.

## A released report does not change

**Draft until #1347 merges.**

A release copies the grade bands, division ranges and ranking. A reprint of that term uses the copy. Editing **Progressive report rules** changes the next release, not a report already out.

## One division table everywhere

**Draft until #1348 merges.**

The division on the card, the class mark sheet, the class marks page, the student result, the combined download, and the parent marks page come from the same table. A released term keeps the ranges it was released with.

## A new card look

**Draft until #1353 merges.**

**Report Cards → Report Card Template** gains **Progressive (new look)**: one table, each exam set as a Marks and Grade group in exam-date order, with total, aggregate, division and position for each set. **Open sample** adds a SAMPLE FOR APPROVAL tag and a grading key; **Open full preview** is the final card. **Formal** stays the default.

## Release to a chosen list

**Draft until #1352 merges.**

On **Marks Release**, choose **Everyone in this class** or **Only the people I choose**. The preview names each person and the message. **Confirm and release** messages only that list; the rest of the class is not told. A parent who is not linked, or has no number, is refused with a reason.

## Enter marks on a phone

**Draft until #1335 merges.**

**Marks → Enter marks** shows one learner per card, with an **ABS** button and **Save draft**. A mark reads **Saved** only after the server confirms it; offline, it stays **Not saved yet** and saves when the connection returns. **Submit** stays off until every learner has a mark or ABS.

## Read the mark sheet by exam set

**Draft until #1337 merges.**

**Exams and marks** shows a tab for every exam set with a sitting in the chosen term and class, in date order, under the school's own name. Before release, a subject not in the total shows a dash; after release, a blank mark says **Missing**.

## Approvals and release

**Draft until #1324, #1325 and #1338 merge.**

- A class teacher sees **Review marks** for the class they head, and can approve or return with a reason.
- A head teacher has the school admin's powers inside their own school.
- **Marks Release** names the set, shows how many marks are missing, and the confirm button states the outcome.
- **Reopen marks** asks for a reason. Families keep seeing the released report until the school releases again.

## Not in this release

- An **Incomplete** marker is planned but not built. A missing mark prints **Missing** and counts as 0.
- Nothing here changes a report that was released before these changes; a new release is a new version.
