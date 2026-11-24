---
title: Answers for support
role: admin
---
<Role r="admin" />

# Answers for support

Short answers for a person replying to schools and families on chat or WhatsApp. Each entry links to the full guide. Use the guide if the question needs a screen name or a sequence of steps.

**Never ask for or repeat marks, learner names, phone numbers or any personal data in a chat.** Ask the school to check the record in KlassApp instead. Never name another school.

**Draft entries** depend on a pull request that is not merged. If the screen is not on the school yet, say the feature is coming and do not walk them through it.

## Choosing and setting up reports

### How do we choose between the two report types?

**Audience:** school admin, head teacher. **Draft — PR #1342 not merged.**

Open **Report cards** and find **Report type**. **Progressive report** is the formal card; **Set-based reports** gives each exam set its own report and one combined report. Press **Save report type**. The change applies from the next release; a released report keeps its type.

Guide: [Choose a report type](/help/reports/choose-report-type).

### Will changing the report type change reports we already sent?

No. A released report keeps the type it was released with.

### How do we change grade points, divisions or ranking?

**Audience:** school admin, head teacher. **Draft — PRs #1343 and #1355 not merged.**

Open **Report cards**, then **Progressive report rules**. Choose **All classes** or one class level, edit the bands, division ranges or ranking, and press **Save progressive report rules**. Confirm your school's own grade scale in writing before reports go out.

Guide: [Progressive report rules](/help/reports/progressive-report-rules).

### What division ranges does the app use if we change nothing?

12 or less is 1, 13 to 24 is 2, 25 to 28 is 3, 29 to 32 is 4, 33 or more is U.

### How do we undo a rules change?

**Draft — PR #1355 not merged.** On **Progressive report rules**, use **Reset to defaults**, tick **Yes, reset these rules to the app defaults**, and press the button. It applies from the next release.

### Which exam sets count toward the combined report?

**Audience:** school admin, head teacher. **Draft — PR #1330 not merged.**

Open **Settings**, then **Exam types**. For each set choose **Counts toward the combined report** or **Shown on its own**. The weights of the counting sets must total 100 before **Save report settings** works.

Guide: [Exam types and exam sets](/help/reports/exam-types-and-sets).

### Why is our column order different from last term?

Columns follow **Scheduled Date & Time** on the exam form, earliest first. A set with no date goes last. Creation order is not used.

### Where do we put the school crest and the head teacher's name?

**Audience:** school admin. **Draft — PR #1357 not merged.**

**Report cards → Progressive report rules → School details for report cards**. Type the **Head teacher's name**, upload the **Crest** (PNG or JPG, up to 2 MB), press **Save school details**. With no crest, the card prints the school's initials.

Guide: [Crest, head teacher name and uploads](/help/reports/crest-head-teacher-uploads).

### Why did our crest disappear after an update?

Uploads saved on a local disk do not survive a deploy. The screen says **Saved but missing** when that happens. Upload the crest again; the lasting fix is object storage, which the deployment owner sets.

### How do we make a term current?

**Audience:** school admin. **Draft — PR #1356 not merged.**

Open **Academic Terms**, press **Make this the current term** on the term, tick **Yes, make this the current term**, and confirm.

Guide: [Make a term current and link a class](/help/reports/setup-actions).

### How do we link a class?

**Draft — PR #1356 not merged.** Open **Classes**. If a yellow box lists unlinked sections, press **Link to a class**, choose the class, press **Continue**, tick **Yes, link this section**, then **Link section to class**.

## Marks and release

### What do Missing and ABS mean?

**Draft — PRs #1344 and #1350 not merged.**

**Missing** means no mark was entered. **ABS** means the learner was absent. Both count as 0 — F9, 9 points on the default scale — and both count in the aggregate and division. The cell never shows 0.

Guide: [Missing marks and ABS](/help/reports/missing-marks-and-abs).

### A learner's division dropped after we left a subject blank. Why?

A blank now counts as 0 instead of being left out, so the aggregate rises and the division can fall. Enter the mark, or mark the learner ABS if they were absent, before the next release.

### Is there an Incomplete marker?

Not today. A missing mark is shown as **Missing** and counts as 0. There is no separate incomplete state.

### How does a teacher enter marks on a phone?

**Draft — PR #1335 not merged.** Open **Marks**, then **Enter marks** or **Continue**. Type the mark, or press **ABS** for an absence. Press **Save draft** as you go.

Guide: [Enter marks on a phone](/help/reports/enter-marks-phone).

### Why is Submit turned off?

Every learner needs a saved mark or an ABS. The screen says how many are missing: "A mark or ABS is missing for N learners."

### What does "Not saved yet" mean?

The server has not confirmed the mark. If the connection dropped, keep the page open; it saves when the connection returns. Check the row says **Saved** before leaving.

### Who can approve or return marks?

A class teacher for the class they head. A school admin or head teacher for any class in their own school. A return needs a reason of at least 10 characters.

Guide: [Approvals and release](/help/reports/approvals-and-release).

### Who can release marks?

A school admin or a class teacher for their class. **Draft — PR #1352 not merged:** a head teacher is still refused on **Marks Release**; the fix is in review. Until it merges, do not tell a head teacher they can release.

### How do we release without messaging parents?

Leave **Send these results on WhatsApp** off. The release still happens and the log says the messages are switched off.

### Can we tell only some parents?

**Draft — PR #1352 not merged.** On **Marks Release**, choose **Only the people I choose**, tick the parents or learners, press **Preview who will be told**, then **Confirm and release**. The rest of the class is not told.

### Why can't a parent see the report yet?

Reports appear only after release. If the release is done, check the parent is linked to the learner and has a number on file.

## For parents

### How do I get my child's report card?

Your school releases reports first. After that, open KlassApp on WhatsApp and ask for the report card. If it says nothing has been released, the school has not released that term yet.

Guide: [Report cards on WhatsApp](/help/send-report-cards-whatsapp).

### Why does the report show Missing or ABS?

**Missing** means the school has no mark for that subject. **ABS** means your child was absent. Both count as 0. Ask the school if you think a mark is wrong.

## For teachers

### Can I release marks for my class?

No. A teacher submits; a class teacher approves or returns; a school admin or class teacher releases. Ask your class teacher or the office.

### I cannot see Review marks. Why?

**Review marks** is for the class teacher of that class. A subject teacher who does not head the class does not see it.
