---
title: Progressive report rules
role: admin
draft: PRs #1343, #1347, #1355
---
<Role r="admin" />

# Progressive report rules

This screen sets the grade points, the division ranges and the ranking for the Progressive report. Nothing is stored until you press **Save progressive report rules**.

**Confirm your school's own grade scale in writing before reports go out.** The screen can show a preset that looks plausible and still be wrong for your school. The school's written confirmation is the source of truth.

## Open the screen

1. Open **Report cards**.
2. Press the link **Progressive report rules** (`/admin/reports/cards/progressive-rules`).
3. Choose **All classes** for a school-wide rule, or one class level to override it for that level only.

A class level's choice wins over **All classes**.

## Grade bands and points

- The bands must run from 100 down to 0 with no gaps and no overlap. If a gap exists, the screen names the marks left out.
- **Points** are editable. They are what the aggregate adds up.
- The bands are shown only after you choose a class level.
- While the table says the rows are an example, nothing has been saved.

## Division ranges

- Leave **Use the automatic ranges** ticked to keep today's table: 12 or less is 1, 13 to 24 is 2, 25 to 28 is 3, 29 to 32 is 4, 33 or more is U.
- Untick it to edit the range table. Each row has a label, **From aggregate** and **To aggregate**. The ranges must run from the lowest aggregate to the highest, in order, with no gap and no overlap. The first range is open below and the last is open above.

## Ranking

Choose one:

- **Automatic — by aggregate when this class has grade points, otherwise by the mark total**
- **By aggregate**
- **By total**

Ties share a place and the next place is skipped.

## Presets, examples and reset

- **Start from a preset** and press **Load preset**: **App default**, **Older seeded scale (confirm with the school)**, or **Custom**. A preset only fills the form; it shows "not saved yet" until you save.
- **Try a mark**: type a mark from 0 to 100 and press **Show** to see the grade and points from the bands on screen.
- **Try an aggregate**: see the division from the ranges on screen.
- **Reset to defaults**: tick **Yes, reset these rules to the app defaults**, then press the button. This puts the bands, division ranges and ranking back to the app's own for that class level, or for all classes.
- **Last changed by NAME on DATE** is shown at the top, with the preset it came from.

## Save

Press **Save progressive report rules**.

- The change applies from the next release.
- A report that is already released keeps the rules it was released with. Editing this screen does not change a reprint of that term.
- A term released before rules were stored keeps the default division table and automatic ranking. Its printed grades still follow the current bands.

## On a phone

The same fields are listed one under another. The labels are **Division**, **From aggregate**, **To aggregate**, and for a class level **Grade**, **Points**, **From**, **To**, **Remark**. The page does not scroll sideways.

## Who can use it

A school admin and a head teacher, for their own school. A teacher cannot open it. A class level from another school is refused like a missing one.
