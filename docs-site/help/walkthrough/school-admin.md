---
title: School admin walkthrough
role: admin
draft: "Draft until the stable release"
---
<Role r="admin" />

# School admin walkthrough

**Draft until the stable release.** Demo school only. The learner names are invented.

This is the whole path: marks, report cards, release, and the printed card.

## Step 1 — Sign in

Open the sign-in page, type the school admin's email and password, and press **Sign in**.

**You should see** the admin dashboard: the school name, the term, and the marks waiting for approval.

<img src="/walkthrough/admin-01-dashboard-375.png" alt="Admin dashboard at 375 px" style="max-width:375px;border:1px solid #e2e8f0;border-radius:8px" />
<img src="/walkthrough/admin-01-dashboard-1280.png" alt="Admin dashboard at 1280 px" style="max-width:100%;border:1px solid #e2e8f0;border-radius:8px" />

## Step 2 — Open the marks overview

In the sidebar, open **Exams and reports**, then **Exams and marks**. Choose the class and the term.

**You should see** one tab per exam set, in date order, with the school's own names. A tab says **Released**, **Approving**, or **Coming in**.

<img src="/walkthrough/admin-02-marks-sets-375.png" alt="Marks overview tabs at 375 px" style="max-width:375px;border:1px solid #e2e8f0;border-radius:8px" />
<img src="/walkthrough/admin-02-marks-sets-1280.png" alt="Marks overview tabs at 1280 px" style="max-width:100%;border:1px solid #e2e8f0;border-radius:8px" />

## Step 3 — Read a set with a missing mark

Open the **Opening Paper** tab.

**You should see** a card for each learner. A subject with no mark says **Missing**; an absence says **ABS**. Both count as zero. The total, aggregate and division below the marks already include the zero.

<img src="/walkthrough/admin-03-opening-missing-375.png" alt="Opening Paper with Missing at 375 px" style="max-width:375px;border:1px solid #e2e8f0;border-radius:8px" />
<img src="/walkthrough/admin-03-opening-missing-1280.png" alt="Opening Paper with Missing at 1280 px" style="max-width:100%;border:1px solid #e2e8f0;border-radius:8px" />

## Step 4 — Open Report cards

In the sidebar, open **Report cards**. Choose the class. The page lists every exam set.

**You should see** a green **Released** badge with the date for a released set, and **Not released yet** for a set that is not out. Report cards are built from released marks only.

<img src="/walkthrough/admin-04-report-cards-list-375.png" alt="Report cards list at 375 px" style="max-width:375px;border:1px solid #e2e8f0;border-radius:8px" />
<img src="/walkthrough/admin-04-report-cards-list-1280.png" alt="Report cards list at 1280 px" style="max-width:100%;border:1px solid #e2e8f0;border-radius:8px" />

## Step 5 — Read the class report

Open the released **Opening Paper** set.

**You should see** every learner with the four subjects, the total, the aggregate, the division and the position. **Missing** appears in the subject cell and still counts zero. The line **Incomplete: Opening Paper.** names the set with a gap.

<img src="/walkthrough/admin-05-opening-report-375.png" alt="Opening Paper class report at 375 px" style="max-width:375px;border:1px solid #e2e8f0;border-radius:8px" />
<img src="/walkthrough/admin-05-opening-report-1280.png" alt="Opening Paper class report at 1280 px" style="max-width:100%;border:1px solid #e2e8f0;border-radius:8px" />

## Step 6 — The report card

Press **PDF** on a learner, or **Build all cards** for the class.

**You should see** one A4 card. The learner's name, KlassApp number and class are at the top. Each set has its own Marks and Grade columns. A missing mark prints **Missing** with grade F9; an absence prints **ABS**. Marks are whole numbers, rounded once, half up. The division under the table comes from the four core subjects.

<img src="/walkthrough/admin-07-card-375.png" alt="Report card PDF at 375 px" style="max-width:375px;border:1px solid #e2e8f0;border-radius:8px" />
<img src="/walkthrough/admin-07-card-1280.png" alt="Report card PDF at desktop width" style="max-width:100%;border:1px solid #e2e8f0;border-radius:8px" />

## Step 7 — Release with notifications off

In the sidebar, open **Marks Release**. Choose the exam and the class. Read the preview. Leave **Send these results on WhatsApp** off for the first release.

**You should see** the preview name the set and the number of marks that will count as zero. Press **Release now**. The log says the messages are switched off. Families see the card; nobody is messaged.

<img src="/walkthrough/admin-06-release-375.png" alt="Marks Release at 375 px" style="max-width:375px;border:1px solid #e2e8f0;border-radius:8px" />
<img src="/walkthrough/admin-06-release-1280.png" alt="Marks Release at 1280 px" style="max-width:100%;border:1px solid #e2e8f0;border-radius:8px" />

**At 375 px**, the release sheet is wider than the screen in this build. If the right side is cut off, record it as a finding and finish the release at desktop width; do not mark the 375 step as passed.

## Linking a parent

A parent is linked in one of two ways:

1. **The child's KlassApp number.** The parent sends the number to the school on WhatsApp; the school links that number to the learner.
2. **The link-request form.** The school opens the parent link request and enters the parent's details and the learner.

One parent, one child, one record. A parent who is not linked sees nothing, even after a release.
