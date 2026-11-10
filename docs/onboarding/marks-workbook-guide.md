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

Row 1 holds the three exam set names, taken from Setup. Row 2 holds the column headers. **Data starts at row 3; there is no example row.** Each class tab has room for 150 learners.

| Column | What goes in it |
| --- | --- |
| A | Learner full name (`*` required) |
| B | Stream |
| C | Gender |
| D | KLS number — **leave empty**; KlassApp fills it after onboarding |

Then three exam blocks. Each block has:

- six subject columns;
- **Total**, **Average** and **Position** in grey cells.

The first block's subjects are in columns E to J, with Total, Average and Position in K, L and M. The second block is N to V, the third W to AE. The second and third blocks copy the subject names from the first.

**Type the class's subjects once**, in the yellow cells of row 2 under the first exam set. Use exactly the subject names on the **Subjects** tab of the onboarding checklist. Leave a subject cell empty if the class takes fewer than six subjects; an empty header is ignored.

Primary Four to Primary Seven come pre-filled with English, Mathematics, Science and Social Studies. Change them if the school's subjects differ.

## Entering marks

- A number from `0` up to the **Out of** value on Setup.
- `ABS` if the learner was absent.
- Leave the cell empty if the subject was not taken.

The sheet refuses anything else. Do not type in **Total**, **Average** or **Position**: they fill in by themselves.

## Names and streams

The learner's full name and stream must match the onboarding checklist exactly. Copy them from that class's tab on the checklist with **Edit → Paste special → Values only**. If the checklist says `Primary One` and `East`, the marks tab says `Primary One` and `East`, not `P.1` or `P1 East`.

## Positions

Position ranks the class by **total** for that exam set. A learner marked `ABS` scores nothing, so an absent learner has a lower total and ranks lower. Check the positions before sharing them.

## Class Summary

It shows, for every class: the number of learners, each exam set's class average, and the highest end-of-term total. It also shows the whole-school learner count. It is a check, not a place to type.

## Reports

The official report cards come from KlassApp: grading, divisions, positions and the PDFs. This workbook is how the marks get into KlassApp. The totals and averages in the workbook are for checking only.

## Privacy

The workbook holds learners' names and marks. Share it only with the school's staff who enter marks and the KlassApp team.

## Placeholders

- Screenshot: the Setup tab with the three exam sets.
- Screenshot: one class tab showing the three blocks and the grey calculated columns.
