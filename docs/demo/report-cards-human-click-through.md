# Report cards: human click-through for the final pass

**Who this is for:** the person who clicks before the report-card work is called ready.
**Use only made-up data.** A demo school, invented learners, invented parents. Never a real child's data.
**Every step is done twice: at 375 px first, then at 1280 px.** A typed URL is not a pass. Click from the screen you are on. Where a path has no link in the product, the step says so, and that is a finding to record, not a shortcut.

Mark **Pass** or **Fail** in the last column. On a fail, write the screen, the width, what you expected and what you saw.

Legend: **A** = school admin, **H** = head teacher, **C** = class teacher, **P** = parent.

---

## 0. Before you start

- [ ] A made-up school with one current academic year, at least two classes, and a second school for the refusal checks.
- [ ] One class teacher account for the reporting class, one subject teacher, one parent linked to one learner, and one parent not linked.
- [ ] The class has no marks yet for the term you will use.
- [ ] Browser at 375 px wide, then at 1280 px. No browser zoom.

| # | Step | Pass/Fail |
|---|---|---|
| 0.1 | Open the sign-in page and sign in as **A** with the email and password. You should see the admin dashboard for that school, with the school name in the header. | ☐ |
| 0.2 | Sign out, sign in as **H**. You should see the same school menu, without a separate "head teacher" skin. | ☐ |
| 0.3 | Sign out, sign in as **C**. You should see the teacher dashboard. | ☐ |
| 0.4 | Sign out, sign in as **P**. You should see the parent dashboard with the linked child. | ☐ |

---

## 1. Report type (A, then H)

Path: **Report cards**, section **Report type**.

| # | Click | You should see | Pass/Fail |
|---|---|---|---|
| 1.1 | **Report cards** in the menu; find **Report type** | Two choices: **Progressive report** and **Set-based reports**. The sentence says a change applies from the next release and a released report keeps its type. | ☐ |
| 1.2 | Choose **Progressive report**, press **Save report type** | A confirmation naming the progressive report; the choice stays selected after reload. | ☐ |
| 1.3 | Choose **Set-based reports**, press **Save report type** | A confirmation; **Show on Report Cards** and **Weight %** appear on **Exam Type Settings**. | ☐ |
| 1.4 | Choose **Progressive report** again and save | Back to the formal card type; the set-based columns disappear from **Exam Type Settings**. | ☐ |
| 1.5 | Sign in as **H** and repeat 1.1–1.2 | The head teacher can open and save the same setting. | ☐ |
| 1.6 | Sign in as **C** and look for **Report cards** → **Report type** | The teacher cannot open the setting (no link; the address is refused). | ☐ |

**375 check:** the two choices stack; no sideways scroll. **1280 check:** the choices sit side by side or in one row; nothing is cut off.

---

## 2. Progressive report rules (A)

Path: **Report cards** → link **Progressive report rules**.

| # | Click | You should see | Pass/Fail |
|---|---|---|---|
| 2.1 | Open the screen | Heading **Progressive report rules**; **Class level** menu with **All classes**; a note that changes apply from the next release; **Save progressive report rules** at the bottom. | ☐ |
| 2.2 | Choose a class level | The **Grade bands** table appears with **Grade**, **Points**, **From**, **To**, **Remark**. | ☐ |
| 2.3 | **Start from a preset** → **Older seeded scale (confirm with the school)** → **Load preset** | An amber note that the preset is filled in but not saved yet; the bands change. Reload without saving: the previous bands return. | ☐ |
| 2.4 | In **Try a mark**, type `95`, press **Show** | The grade and points from the bands on screen (on the app default scale that is grade 1, 1 point; a school using D1–F9 sees D1). Type `150`: a message that 150 is not in any band. | ☐ |
| 2.5 | In **Try an aggregate**, type `12`, then `13`, then `33` | Division 1, division 2, division U. | ☐ |
| 2.6 | Change one band so a gap appears, press **Save progressive report rules** | A red message naming the marks with no band; nothing saved. | ☐ |
| 2.7 | Fix the band; change the division ranges so they overlap; save | A red message that the ranges must not overlap and must run from the lowest aggregate to the highest. | ☐ |
| 2.8 | Scroll to **Go back to the app defaults**; press **Reset to defaults** without ticking | An error asking for confirmation; nothing reset. | ☐ |
| 2.9 | Tick **Yes, reset these rules to the app defaults**, press **Reset to defaults** | A confirmation; bands and ranges return to the app defaults; **Last changed by** names you and today. | ☐ |
| 2.10 | Save a real change (for example one band's **To** down by 2 and the band above **From** down by 2), press **Save progressive report rules** | A saved message; **Last changed by NAME on DATE** updates. | ☐ |
| 2.11 | Sign in as **H** and open the same screen | The head teacher can open it and save. | ☐ |
| 2.12 | Sign in as **C** and try to open it | The teacher is turned away and cannot change anything. | ☐ |

**375 check:** the bands and ranges appear as stacked cards, each field labelled (**Division**, **From aggregate**, **To aggregate**, and **Grade**, **Points**, **From**, **To**, **Remark**); no sideways scroll. **1280 check:** both tables stay in columns.

---

## 3. Link a class (A)

Path: **Classes**, the yellow box above the list.

| # | Click | You should see | Pass/Fail |
|---|---|---|---|
| 3.1 | Open **Classes** | If a section has no class link, a yellow box lists it with **Link to a class**. If every section is linked, the box is absent — prepare one unlinked section first. | ☐ |
| 3.2 | **Link to a class** → choose the **Class** → **Continue** | "Link SECTION to CLASS for YEAR?" with **Yes, link this section**, **Link section to class**, **Cancel**. | ☐ |
| 3.3 | Press **Link section to class** without ticking | An error; nothing linked. | ☐ |
| 3.4 | Tick **Yes, link this section**, press **Link section to class** | Back on **Classes** with "SECTION is now linked to CLASS for YEAR."; the section leaves the yellow box. | ☐ |
| 3.5 | Open the same link page again | It says the section is already linked; nothing changes. | ☐ |
| 3.6 | Sign in as **H**, then **C**, and try the same page | Neither can change the class link. | ☐ |

---

## 4. Make a term current (A)

Path: **Academic Terms**.

| # | Click | You should see | Pass/Fail |
|---|---|---|---|
| 4.1 | Open **Academic Terms** | A term table with a **Status** column. The current term says **Current term**; every other term has **Make this the current term**. | ☐ |
| 4.2 | Press **Make this the current term** on a non-current term | "Make TERM the current term?" with the sentence naming the term that stops being current, **Yes, make this the current term**, and **Cancel**. | ☐ |
| 4.3 | Press **Make this the current term** without ticking | An error; the flags do not move. | ☐ |
| 4.4 | Tick **Yes, make this the current term**, press the button | The list returns with a confirmation; **Current term** moves to the chosen term. | ☐ |
| 4.5 | Open the same term's confirm page again | It says the term is already current and offers nothing to change. | ☐ |
| 4.6 | Sign in as **H**, **C** and try | None can change the current term. | ☐ |

---

## 5. Import learners (A)

Path: **Students** → **Import a list**.

| # | Click | You should see | Pass/Fail |
|---|---|---|---|
| 5.1 | Open **Students**, then **Import a list** | The upload page with a file chooser and a template download. | ☐ |
| 5.2 | Upload a made-up CSV for the reporting class | A result with the number imported and any per-row failures. | ☐ |
| 5.3 | Open the class list | The learners are there; each has a KLS number; none is in another class. | ☐ |
| 5.4 | Upload the same file again | The result reports no duplicates (or the page says nothing new was added), and the class count does not double. | ☐ |

**375 check:** the chooser and the result read without sideways scroll. **1280 check:** the same.

---

## 6. Enter marks, including ABS and a blank (C)

Path: **Marks** → **Enter marks** (or **Continue**).

| # | Click | You should see | Pass/Fail |
|---|---|---|---|
| 6.1 | Open the sheet | One card per learner, each with a mark field and an **ABS** button; **Save draft** at the bottom. | ☐ |
| 6.2 | Type a mark for one learner and press **Save draft** | The row reads **Saved** only after the save. | ☐ |
| 6.3 | Press **ABS** for a second learner, save | The row shows **ABS**, not a zero and not "AB". | ☐ |
| 6.4 | Leave one learner's mark blank and try **Submit** | **Submit** is off; the screen says "A mark or ABS is missing for N learners." | ☐ |
| 6.5 | Fill the blank or mark that learner **ABS**, save | **Submit** turns on. | ☐ |
| 6.6 | Press **Submit** | The subject is submitted for review; the teacher can no longer edit it. | ☐ |
| 6.7 | Turn the connection off, type a mark, save, turn it back on | The row reads **Not saved yet** while offline and **Saved** after the connection returns. | ☐ |

**375 check:** one card at a time, 44 px controls, no sideways scroll. **1280 check:** the same sheet reads in columns/cards without clipping.

---

## 7. Approve and return (C, then A/H)

Path: **Review marks** (class teacher) and the approvals queue (admin/head teacher).

| # | Click | You should see | Pass/Fail |
|---|---|---|---|
| 7.1 | As **C**, open **Review marks** | Only the classes the class teacher heads; one row per submitted subject. | ☐ |
| 7.2 | Press **Approve** on one subject | A confirmation; the row leaves the queue. | ☐ |
| 7.3 | Return another subject with a reason of 9 characters | Refused: the reason must be at least 10 characters. | ☐ |
| 7.4 | Return it with a reason of 12+ characters | A confirmation; the teacher sees the return note on their exam list. | ☐ |
| 7.5 | As **A**, open the approvals queue | The same subject shows as returned; **Approve** is still available once it is resubmitted. | ☐ |
| 7.6 | As **H**, open the same queue | The head teacher can approve and return for this school only. | ☐ |

---

## 8. Release with notifications off, then a pilot list (A)

Path: **Marks Release**.

| # | Click | You should see | Pass/Fail |
|---|---|---|---|
| 8.1 | Open **Marks Release**, choose the sitting and the class | A preview that names the set and states how many marks are missing, for example "N marks are missing and will count as 0"; **ABS** is shown as ABS. | ☐ |
| 8.2 | Leave **Send these results on WhatsApp** off, press **Confirm and release** | The release is created; the log says the messages are switched off; Sent is 0. | ☐ |
| 8.3 | Open the release again and choose **Only the people I choose** | The radios **Everyone in this class** and **Only the people I choose**; a list of parents/learners to tick. | ☐ |
| 8.4 | Tick one linked parent, press **Preview who will be told** | The preview names that parent, the number, the sentence, and says the rest of the class is not told. Ticking a parent with no number shows the refusal. | ☐ |
| 8.5 | Press **Confirm and release** (or **Confirm schedule**) | A confirmation naming the outcome; the release log records the chosen list; the rest of the class is not messaged. | ☐ |
| 8.6 | Press **Reopen marks** with an empty reason | Refused: a reason of at least 10 characters. | ☐ |
| 8.7 | Reopen with a reason | The status line: "Reopened. Families keep seeing the released results until you release again." | ☐ |

**Note:** nothing is actually sent in this pass. Leave the send switch off unless the owner has approved a live pilot.

---

## 9. Report cards, each template, and the class PDF (A)

Path: **Report Cards** → **Report Card Template**.

| # | Click | You should see | Pass/Fail |
|---|---|---|---|
| 9.1 | Open **Report Cards**; find **Report Card Template** | Three choices: **Formal**, **Warm**, **Progressive (new look)**; the current one says **Currently in use**. | ☐ |
| 9.2 | Select **Formal**, press **Save**; open **Open full preview** | The formal card titled PROGRESSIVE REPORT; MONTHLY RESULTS and END OF TERM EXAMINATION headings. | ☐ |
| 9.3 | Select **Warm**, save, preview | The warm layout; no sample tag on a final card. | ☐ |
| 9.4 | Select **Progressive (new look)**, save, press **Open full preview** | One table; each exam set is a Marks and Grade group in exam-date order; total, aggregate, division and position per set; no SAMPLE tag. | ☐ |
| 9.5 | Press **Open sample** | The same card with **SAMPLE FOR APPROVAL** and a grading key built from the school's bands and ranges. **Open full preview** has neither. | ☐ |
| 9.6 | From **Report Cards**, press **PDF** for one learner | One A4 page with the learner's name, KLS number, class, term and marks; no clipping. | ☐ |
| 9.7 | Press **Build all cards** (or **Download all**) for the class | A merged class PDF, one page per learner; the file downloads and opens. | ☐ |

**375 check:** the template choices stack; **Open full preview** and **Open sample** are reachable without sideways scroll. **1280 check:** the choices read in one row.

---

## 10. Reprint after a rules edit (A)

| # | Click | You should see | Pass/Fail |
|---|---|---|---|
| 10.1 | Note one learner's total, aggregate, division and position on the released card. | The values to compare. | ☐ |
| 10.2 | **Report cards** → **Progressive report rules**, change the division ranges (for example make division 1 end at 10 instead of 12), save. | A saved message; **Last changed by** updates. | ☐ |
| 10.3 | Return to **Report Cards**, reprint the same learner. | The marks, grades, total, aggregate, division and position are exactly the same as 10.1. A published term does not change. | ☐ |
| 10.4 | Change the grade bands, save, reprint again. | Still identical to 10.1. | ☐ |

If anything changes, that is a fail; write down which value moved.

---

## 11. Parent view and download (P)

| # | Click | You should see | Pass/Fail |
|---|---|---|---|
| 11.1 | Sign in as **P** | The dashboard for the linked child; no other family's child. | ☐ |
| 11.2 | Open **Marks** | The released term's marks; **Missing** where a mark was never entered; **ABS** where the learner was absent. Before release, nothing. | ☐ |
| 11.3 | Open the child's report card and download it | The same card the school sees; one page; the learner's own name only. | ☐ |
| 11.4 | Ask for the card on WhatsApp (if the pilot is on) | The card arrives as a PDF document named after the learner, and only that learner's card. Nothing is sent in this pass unless the owner approved it. | ☐ |

**375 check:** the parent screens read on a phone with no sideways scroll. **1280 check:** the same content is readable.

---

## 12. Refusals

| # | Step | You should see | Pass/Fail |
|---|---|---|---|
| 12.1 | As the **other school's admin**, open this school's report card or class report | Not found. Nothing of this school is shown. | ☐ |
| 12.2 | As the **other school's head teacher**, open this school's approvals or release | Refused. | ☐ |
| 12.3 | As **another class teacher** (not the one who heads this class), open **Review marks** and try to approve | The other class is not listed; a direct approve attempt is refused. | ☐ |
| 12.4 | As a **subject teacher**, try **Marks Release** and **Progressive report rules** | Both are refused; no release or rule change is possible. | ☐ |
| 12.5 | As **P**, try another family's learner address | Not found; no marks are shown. | ☐ |

---

## 13. What to write down

For every fail or surprise:

- the step number and width (375 or 1280);
- the screen name and the button you pressed;
- what you expected and what you saw;
- a screenshot, if the owner allows one (made-up data only).

**A typed URL is not a pass.** If a screen can only be reached by typing its address, record that as a navigation finding even if the screen itself works.
