# Filling the term's marks workbook

One workbook per term. It collects every class's marks for the term's three exam sets, so KlassApp can import them and produce the report cards.

Open the workbook and read the **Instructions** tab first. The rules below are the same rules, in short.

## The tabs

| Tab | What it is for |
| --- | --- |
| **Instructions** | The rules for the whole workbook. Read only |
| **Setup** | The school, academic year, term and the three exam sets |
| **Class Summary** | Learners, averages and the highest end-of-term total per class |
| **One tab per class** | Baby Class to Primary Seven. Each holds that class's learners and marks |

## Setup

Check these before anything else:

- **School** (cell B3), **Academic year** (B4) and **Term** (B5).
- **The three exam sets**, in rows 8 to 10:

| Column | What goes in it |
| --- | --- |
| B | Exam set name, as it is in KlassApp |
| C | Out of (marks) |
| D | On the report card? Write `Yes` or `No` |
| E | Weight on the report (%) |
| F | Notes |

If the report combines exam sets, the weights should add up to 100. Change a name or the marks it is out of on Setup only; every class tab follows.

## A class tab

Row 1 holds the three exam set names, taken from Setup. Row 2 holds the headers. **Data starts at row 3; there is no example row.** Each class tab has room for 150 learners.

| Column | What goes in it |
| --- | --- |
| A | **KLS number** (`*` required): the learner's KlassApp number, for example `KLS0001234` |
| B | **Learner name**, so you can check you have the right person |

Then three exam blocks:

- the first six columns are the subjects;
- **Total**, **Average** and **Position** come next, in grey cells.

The first block is columns C to K: subjects in C to H, then Total, Average and Position in I, J and K. The second block is L to T, the third U to AC. The second and third blocks copy the subject names from the first.

**Type the class's subjects once**, in the yellow cells of row 2 under the first exam set. Use exactly the subject names on the **Subjects** tab of the onboarding checklist. Leave a subject cell empty if the class takes fewer than six subjects; an empty header is ignored.

Primary Four to Primary Seven come pre-filled with English, Mathematics, Science and Social Studies. Change them if the school's subjects differ.

## The KLS number and the name

- **Copy the KLS numbers out of KlassApp**, from the class list, so you do not retype them. Column A is how KlassApp matches the marks to a learner.
- **If a learner has no KLS number yet**, leave column A empty and write the learner's name in column B exactly as it is on the onboarding checklist. KlassApp then matches by name and class, and never guesses.
- Column B is for checking. It is not a second place to identify the learner when column A is filled.

## Entering marks

- A number from `0` up to the **Out of** value on Setup.
- `ABS` if the learner was absent.
- Leave the cell empty if the subject was not taken.

The sheet refuses anything else. Do not type in **Total**, **Average** or **Position**: they fill in by themselves.

## Positions

Position ranks the class by **total** for that exam set. A learner marked `ABS` scores nothing, so an absent learner has a lower total and ranks lower. Check the positions before sharing them.

## Class Summary

It shows, for every class: the number of learners, each exam set's class average, and the highest end-of-term total. It also shows the whole-school learner count. It is a check, not a place to type.

## Reports

The official report cards come from KlassApp: grading, divisions, positions and the PDFs. This workbook is how the marks get into KlassApp. The totals and averages in the workbook are for checking only.

## Privacy

The workbook holds learners' names and marks. Share it only with the school's staff who enter marks and the KlassApp team.

## Common mistakes

- Typing the learner's name in column A. A is the KLS number; the name goes in B.
- Typing the KLS number with spaces, a prefix or lower case (`kls 123`); copy it as it appears in KlassApp.
- Adding a Stream or Gender column. The workbook no longer has them; names and streams come from the onboarding checklist.
- Pasting a list with formatting. Use **Edit → Paste special → Values only**.
- Typing in Total, Average or Position; they are formulas.
- A mark above the Setup "Out of" value, or a letter other than `ABS`.
- A subject name that differs from the Subjects tab of the checklist; the two must match.
- Inserting a column to add a subject. Use the empty subject columns; do not move the blocks.

## Placeholders

- Screenshot: the Setup tab with the three exam sets.
- Screenshot: one class tab with the KLS column, the name column and the three blocks.
