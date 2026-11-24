---
title: Missing marks and ABS
role: admin
draft: "Draft until #1344, #1350 and #1330 merge"
---
<Role r="admin" />

# Missing marks and ABS

A mark that was never entered and an absence both count as **0**. On the default scale that is grade **F9**, 9 points, and it counts in the aggregate and the division. The cell never shows a 0. It shows **Missing** or **ABS**.

## What the words mean

- **Missing** — no mark was entered for that learner and subject.
- **ABS** — the learner was absent. A teacher types **ABS** (or **AB**) or presses the **ABS** button. The stored grade stays AB, but every screen shows ABS.

## Where you see them

- **Enter marks** — a saved absence shows ABS in the mark box.
- **Report cards** — an absence shows ABS; a subject with no mark stays a dash until the set is released.
- **Class marks** — ABS next to the subject.
- **Exams and marks** (the class mark sheet) — the absent cell shows ABS.
- **Marks Release** — the preview shows ABS, and states how many marks are missing, for example "12 marks are missing and will count as 0".
- **Parent marks** — ABS instead of a zero and the stored grade.
- **Combined marksheet** — one sheet per exam set, named with your school's set name.

## There is no Incomplete marker

An earlier draft had an "Incomplete" state. It is closed. A missing mark is simply Missing and counts as 0. The screen offers no workaround and no choice.

## The guard before a combined report

While a set that counts toward the combined report has marks entered and is **not released**, the class report screen does not build the combined report. It names the counting set and links to **Marks Release**.

A set with no marks at all is not listed; it is treated as entirely Missing. A set that stands on its own does not block the combined report.

The guard is on the class report screen only. It is not inside the PDF or the results sheet, so a card can still be built for a set that has not been released.

## What to do before a release

1. For every subject on a set that has started, enter a mark or mark the learner **ABS**.
2. Read the release preview's missing count.
3. Release only when the count is what you expect.

A blank in a spreadsheet import is still skipped; the importer does not write a zero. The report is what treats the missing subject as 0.

## What a released report keeps

A released report is not recalculated. A new release writes a new version; the older snapshot keeps its numbers.

## Who this applies to

Every school, without switching anything on. School admins and head teachers see the same words on their screens. Parents see a report only after release.
