# Checklist onboarding demo — 5 minutes

Northwind Harbour School is fictional. The admin address is `northwind.admin@example.test`. The password is on the demo laptop, in `DEMO_CHECKLIST_ADMIN_PASSWORD`. It is not written in this file.

This script is the Wednesday demo. Speak the sentences in quotes. Click only the named buttons. The numbers below are what the cards showed when this script was clicked through on 9 November 2026. Read the numbers off the card on the day. Do not invent a count if a card disagrees with this sheet.

## Before you walk on

In the proof-of-concept checkout, on branch `poc/toshi-checklist-onboarding`:

```bash
php artisan demo:checklist-reset
bash scripts/demo/poc-serve.sh
```

Open `http://localhost:8000`. That is the address for this demo copy and for every later Google test. Type localhost in the browser. Google treats a numeric loopback host as a different origin. The reset command prints the same address. If port 8000 is already taken, the start command names the listener and leaves it running. The Riverside preview stays on port 8126. It does not use Google sign-in.

The workbook with two deliberate problems is `docs/demo/poc-checklist-workbook.xlsx`. The all-correct twin is `docs/demo/poc-checklist-workbook-correct.xlsx`. The same files are on the desktop as `Northwind-template-2-flags.xlsx` and `Northwind-template-clean.xlsx`. Class tabs run from Baby Class to Primary Seven. All Learners has no example row, so the first learner is on row 2. Phones are written `07XX XXXXXX`. The school is Kenya and the curriculum is CBC. Have the problem file ready to choose.

Sign in. You land on the school dashboard. If you land on "Setting up without Toshi", you are on the manual wizard, which hides this panel. Go back to the dashboard. Do not finish that wizard.

## 0:00 — Open Toshi

On a laptop-width window, click **Toshi** at the bottom right. For this demo school the pill says Toshi. The panel does not start the older 13-step setup list. The checklist is the conversation.

On a phone-width window the pill is not shown. Click the white **K** tab on the right edge. Its label is Toshi.

Say: "The admin never leaves the chat. The checklist is the only file they bring."

## 0:20 — Ask for the checklist

Click **Onboard from checklist**.

Or type this and press Enter:

> Please onboard this school from its checklist

Say: "Same request, either as a button or in a sentence."

Toshi shows **Data agreement** first, with short summaries and links to the privacy and terms pages this app already serves. Click **I agree, continue**. Toshi does not read the file before that.

Toshi then shows **Upload the checklist**. Choose `poc-checklist-workbook.xlsx`. Click **Read this checklist**.

If you paste a Google Sheets link instead, Toshi says to download the sheet as .xlsx and upload that file. This demo has no Google account that can read a shared sheet. Do not make the sheet public.

## 0:50 — Summary card

The card title is **Northwind Harbour School**. The numbers sit in tiles: learners, classes, streams, teachers, parents, subjects, and terms with their dates. Say: "Every number on this card was counted from the file. The model does not invent them."

The same counts, as a short list:

| Step | Read | Accepted | Flagged |
| --- | --- | --- | --- |
| Academic terms | 3 | 3 | 0 |
| Classes and streams | 8 | 8 | 0 |
| Subjects | 5 | 5 | 0 |
| Teachers and teaching assignments | 8 | 8 | 0 |
| Learners and parents | 122 | 120 | 2 |

Read equals Ready plus Flagged on every row. The People total uses the same figures as Done, after the flagged rows are left out: 8 teachers, 120 learners and 120 parents, 248 people.

Then the line under the list: class teachers listed 8, and this demo does not write class teachers.

Say: "Two rows are wrong. Toshi names the tab and the row, and says how to fix each one."

- Learners, row 123: parent phone `12345`. Learner ready, parent skipped. The row stays in Flagged, so Ready is 120 and the create is 120 learners and 120 parents.
- Learners, row 124: class name Primary Eight. Use a class name KlassApp already knows, or skip the row.

Say: "We can skip those two rows, or stop and fix the file. Nothing is saved until we confirm a step."

Click **Skip these rows**.

If instead you click **Stop, I'll fix the workbook**, the card says nothing was written. Fix the file and click **Onboard from checklist** again. Use that only if someone asks. It ends this run.

## 1:30 — Confirm each step

Each preview is one line saying why the sheet's own values are enough, then a primary button that names the outcome. **Change** keeps the sheet as written, or lets you type a note. A note does not write anything. **Skip step** leaves that step unwritten. Click the primary button every time. After each one, one line says what was created and links to the page. The next preview follows.

1. **Academic terms.** "Use the term names and dates as written." Button: **Create 3 terms**. Result: Created 3 terms. Link: **Open academic terms**.
2. **Classes and streams.** "Use the stream names as written, in the class order on the sheet." Button: **Create 6 classes and 4 streams**. Result: Created 6 classes and 4 streams. Link: **Open classes**.
3. **Subjects.** Button: **Create 5 subjects**. Result: Created 5 subjects. Link: **Open subjects**.
4. **Teachers and teaching assignments.** Button: **Create 8 teachers**. Result: Created 8 teachers. Link: **Open teachers**.

Say, while those four confirm: "Each step is one transaction. If it fails, that step writes nothing. The steps already confirmed stay."

5. **Learners and parents.** Button: **Create 120 learners**.

Say, while it saves: "One hundred and twenty learners and their parents, in one step. The two bad rows stayed out."

This approval takes about half a minute. Wait for the result line. Do not click again.

Result: Created 120 learners. Link: **Open the students list**.

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

Sign in again at `http://localhost:8000` and start from **Onboard from checklist**. The password is unchanged.

If the upload says the file is missing, choose the `.xlsx` again and click **Read this checklist**. Reading the file does not save the school.

If the card says the data processing row is not Yes, it names Confirmations and the row (row 3 on this template). Change that cell to Yes and upload again. Nothing is stored until that answer is Yes.

If you are on the manual wizard, Toshi is hidden there on purpose. Use the dashboard.

## If you have a spare minute

Upload `poc-checklist-workbook-correct.xlsx` and read it. There are no flags. Learners read 120, accepted 120, flagged 0. Every preview says Create 0, Already exists, Conflict 0. Confirming writes nothing. The completion card says **Invites ready: 0**, because the invites were already prepared.

## What Toshi says during this script

A short greeting is enough: "Hi." Toshi answers in one or two lines.

These sentences open the data agreement, the same as the button:

- "set up my school"
- "here's our checklist"
- "Please onboard this school from its checklist"

While a step is on screen, Toshi explains that step in one line and names the button. A typed "yes", "ok", or "skip" does not write anything. Use the button.

A free-text question, once a checklist has been read, is one chat call and no tools. The call sends counts, classes and streams, terms and dates, flags, and the waiting step. It does not send learner names or phone numbers. The call asks for the lowest reasoning effort, because this model's default thinking did not finish inside 5 seconds. Toshi answers in one or two sentences from those facts. If the facts do not cover the question, Toshi says it does not know that from this checklist.

With no key, or if the call fails or takes longer than 5 seconds, Toshi answers from the saved plan instead. That answer uses the same numbers. If the limit is hit, the only sentence is: "The free model limit was hit. Wait before trying again. Nothing was written, and this was not retried." Do not send the question again.

Buttons, a typed "yes", and "set up my school" do not call the model. A full click-through that only uses the buttons makes no model request. Each extra question makes one.

On the upload card, Connect Google Sheet opens Google's file picker. The request is only the drive.file scope, so Google grants that one file. A native Google Sheet is exported as .xlsx. An .xlsx file stored in Drive as itself is downloaded with files.get alt=media. Either way it is read on the same path as an upload. The access token stays in the session and is not logged. Upload stays available. The Google client for this demo uses JavaScript origin `http://localhost:8000` and redirect `http://localhost:8000/auth/google/callback`. The picker key's website restriction includes `http://localhost:8000/*` and `https://docs.google.com/*`.

## If the free model limit appears

The sentence is: "The free model limit was hit. Wait before trying again. Nothing was written, and this was not retried."

Wait. Do not send the message again. This script does not call a model, so that sentence should not appear during the rehearsal. If it does, stop and tell Rasta. Nothing on the cards was written by that failure.

## What this demo does not do

- It does not send email or WhatsApp.
- It does not create exams, fees, or SchoolPay records.
- It does not assign homeroom class teachers. Teaching assignments for the eight teachers are written in the teachers step.
- The approval buttons do not call a model. A free-text question does, once, and falls back to the saved plan if that call cannot finish. A venue with no internet can still run the button script. `demo:checklist-reset` leaves the assistant switched on for Northwind Harbour School only. No other school is changed.

## The free Gemini key

The key in this laptop's gitignored `.env` (`OPENAI_COMPATIBLE_API_KEY`) is a Google AI Studio free-tier key. Google may use free-tier prompts to improve its products. It is for this fictional school only. Never send a real school's data through it. A real school needs a paid key.

The base URL is `https://generativelanguage.googleapis.com/v1beta/openai/` with no `/chat/completions` on the end. The model is `gemini-3.5-flash`. The `TOSHI_LLM_BASE_URL`, `TOSHI_LLM_API_KEY`, and `TOSHI_LLM_MODEL` lines stay unset.

Leave `TOSHI_SDK_V2_ENABLED` false for the rehearsal. On this beta compatibility layer the model can emit a tool call, and the follow-up is then rejected because the function call is missing a thought signature. With that switch off, the panel never starts that loop. The checklist path never starts it either.

Google publishes requests-per-minute, input tokens-per-minute, and requests-per-day as the limits, and shows the project's own numbers in AI Studio. It does not print one free-tier figure for this model. Independent notes on Flash free tier describe about 5 requests a minute and about 20 a day. Stay inside that until Rasta reads this project's own number. Do not add retries.

Screenshots of the cards, at 1280 and 375 pixels, are in `docs/demo/screenshots/`.
