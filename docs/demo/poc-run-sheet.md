# Checklist onboarding demo — 5 minutes

Northwind Harbour School is fictional. The admin address is `northwind.admin@example.test`. The password is on the demo laptop, in `DEMO_CHECKLIST_ADMIN_PASSWORD`. It is not written in this file.

This script is the Wednesday demo. Speak the sentences in quotes. Click only the named buttons. The numbers below are what the cards showed when this script was clicked through on 9 November 2026. Read the numbers off the card on the day. Do not invent a count if a card disagrees with this sheet.

## Before you walk on

In the proof-of-concept checkout, on branch `poc/toshi-checklist-onboarding`:

```bash
php artisan demo:checklist-reset
```

Open the demo URL on that machine. The rehearsal on this computer is `http://127.0.0.1:8130`. The Wednesday laptop gets its own port after the freeze (planned as 8127) and the same reset command. Leave port 8126 alone. That is the other preview.

The workbook with two deliberate problems is `docs/demo/poc-checklist-workbook.xlsx`. The all-correct twin is `docs/demo/poc-checklist-workbook-correct.xlsx`. Have the problem file on the desktop, ready to choose.

Sign in. You land on the school dashboard. If you land on "Setting up without Toshi", you are on the manual wizard, which hides this panel. Go back to the dashboard. Do not finish that wizard.

## 0:00 — Open Toshi

On a laptop-width window, click **Meet Toshi** at the bottom right.

On a phone-width window the Meet Toshi pill is not shown. Click the white **K** tab on the right edge.

Say: "The admin never leaves the chat. The checklist is the only file they bring."

## 0:20 — Ask for the checklist

Click **Onboard from checklist**.

Or type this and press Enter:

> Please onboard this school from its checklist

Say: "Same request, either as a button or in a sentence."

Toshi shows **Upload the checklist**. Choose `poc-checklist-workbook.xlsx`. Click **Read this checklist**.

## 0:50 — Summary card

The card title is **Northwind Harbour School**. Say: "Every number on this card was counted from the file. The model does not invent them."

Read the table:

| Step | Read | Accepted | Flagged |
| --- | --- | --- | --- |
| Academic terms | 3 | 3 | 0 |
| Classes and streams | 8 | 8 | 0 |
| Subjects | 5 | 5 | 0 |
| Teachers and teaching assignments | 8 | 8 | 0 |
| Learners and parents | 122 | 121 | 2 |

Then the line under the table: class teachers listed 8, and this demo does not write class teachers.

Say: "Two rows are wrong, and Toshi names the tab and the row."

- Learners, row 123: parent phone `12345`.
- Learners, row 124: class name Primary Eight.

Say: "We can skip those two rows, or stop and fix the file. Nothing is saved until we confirm a step."

Click **Skip these rows**.

If instead you click **Stop, I'll fix the workbook**, the card says nothing was written. Fix the file and click **Onboard from checklist** again. Use that only if someone asks. It ends this run.

## 1:30 — Confirm each step

Each preview has **Confirm step** and **Skip step**. Click **Confirm step** every time. After each one, a result card shows the counts and a link. The next preview is already underneath.

1. **Academic terms.** Create 3. Already exists 0. Conflict 0. Term 1, Term 2, Term 3. Confirm. Result: Created 3. Link: **Open academic terms**.
2. **Classes and streams.** Create 8. Examples include Baby Class, Middle Class, Primary One East. Confirm. Result: Created 8. Link: **Open classes**.
3. **Subjects.** Create 5. Examples include Mathematics, English, Science. Confirm. Result: Created 5. Link: **Open subjects**.
4. **Teachers and teaching assignments.** Create 8. Examples include Amina Example, Ben Example, Chidi Example. Confirm. Result: Created 8. Link: **Open teachers**.

Say, while those four confirm: "Each step is one transaction. If it fails, that step writes nothing. The steps already confirmed stay."

5. **Learners and parents.** Create 120. Already exists 0. Conflict 0. Confirm.

Say, while it saves: "One hundred and twenty learners and their parents, in one step. The two bad rows stayed out."

This confirm takes about half a minute. Wait for the result card. Do not click again.

Result: Created 120. Link: **Open the students list**.

## 4:00 — Completion card

**Checklist finished.**

Say these lines, which are on the card:

- "Invites ready: 128. That is the eight teachers and the one hundred and twenty parents. Nothing was sent. No email, no WhatsApp."
- "KLS numbers are present. Phones are in international format."
- "Every class matches the checklist: Baby Class 14, Middle Class 14, Primary One East 18, Primary One West 18, Primary Two 18, Primary Three A 16, Primary Three B 14, Primary Four 8."
- "Class teachers are listed and not written. That step is outside this demo."

Click **Open the students list**. The list is Baby Class: Learner 0001 Example, a KLS number, the parent, Active.

Say: "That is the school, set up from one spreadsheet, with a person confirming every write."

## If a step fails

The card title is **Not applied**, with the reason underneath. Earlier steps stay saved. That step does not.

Do not keep clicking Confirm on a failed step. In the proof-of-concept checkout run:

```bash
php artisan demo:checklist-reset
```

Sign in again and start from **Onboard from checklist**. The password is unchanged.

If the upload says the file is missing, choose the `.xlsx` again and click **Read this checklist**. Reading the file does not save the school.

If the card says the data processing row is not Yes, it names Confirmations and the row (row 3 on this template). Change that cell to Yes and upload again. Nothing is stored until that answer is Yes.

If you are on the manual wizard, Toshi is hidden there on purpose. Use the dashboard.

## If you have a spare minute

Upload `poc-checklist-workbook-correct.xlsx` and read it. There are no flags. Learners read 120, accepted 120, flagged 0. Every preview says Create 0, Already exists, Conflict 0. Confirming writes nothing. The completion card says **Invites ready: 0**, because the invites were already prepared.

## What this demo does not do

- It does not send email or WhatsApp.
- It does not create exams, fees, or SchoolPay records.
- It does not assign homeroom class teachers. Teaching assignments for the eight teachers are written in the teachers step.
- It does not call an AI model. The cards are rendered from the saved plan. The configured assistant, which is switched off for this school, is the OpenAI-compatible provider using the model `deepseek-chat`. A venue with no internet can still run this script.

Screenshots of the cards, at 1280 and 375 pixels, are in `docs/demo/screenshots/`.
