# Demo run sheet: follow a class on WhatsApp (15 minutes)

A presenter-led demo. The audience opens the follow-along page, binds their
WhatsApp number to one dummy student of the demo school, and asks for that
student's marks. Everything uses fictional demo data only.

## Before the session (the day before)

The values below are environment settings. Set them on the server; never
paste them into slides, chats or this file.

| Setting | What it is |
| --- | --- |
| `WHATSAPP_DEMO_NUMBER` | The WhatsApp Business number people message, in international format. Without it the page hides its WhatsApp buttons. |
| `DEMO_BIND_TTL_MINUTES` | How long a bound number stays linked. Default `45`. The page and the replies say the same number. |
| `WHATSAPP_DEMO_PARENT_USER_ID` | An existing parent account in the demo school. It picks the school whose students appear on the page. |
| `PRIVACY_NOTICE_URL` | The public privacy notice linked on the page and in the replies. |
| `WHATSAPP_REPLIES_ENABLED` | Must be `true` for any reply to go out. Setting it to `false` stops every reply at once. |

Then, on the server:

1. `php artisan demo:refresh` to put the demo school back to its baseline. It
   also resets the demo exam: every exam class has an unreleased End of Term
   exam with marks for Mathematics, Science, English and Social Studies.
2. Check the scheduler runs: `demo:expire-chats` runs every minute and unlinks
   bound numbers one minute early, so no number stays linked past the TTL.
3. Open `/demo` on a phone and on a laptop. You should see the QR code, the
   **Open WhatsApp** button, the class list with a **Bind to …** button per
   student, and the line "Your number is removed after 45 minutes."

## The 15 minutes

| Time | Presenter | Audience |
| --- | --- | --- |
| 0:00 | Show `/demo` on the projector. Explain: these are dummy students, nobody here is real. | Scan the QR code, or open the page link. |
| 2:00 | "Tap **Open WhatsApp** and send the message." | Sends `JOIN`. |
| 3:00 | "Now pick any student and tap **Bind to** their name." | Sends `BIND <code>`, for example `BIND 9035`. |
| 4:00 | Read the reply aloud (below). | Gets the bind reply. |
| 6:00 | "Reply MARKS." | Sends `MARKS` (or `RESULTS`). |
| 7:00 | Show the class marks view in the admin: Dashboard, **Performance by class**, **View marks by class**. Point out that the marks match the phone. | Compares the table on the phone. |
| 10:00 | Show what happens with a typo. | Sends `BIND 0000`. |
| 12:00 | Explain privacy: the number is unlinked after the TTL, and the privacy notice is linked on the page and in every bind reply. | Opens the privacy link. |
| 14:00 | Questions. | |
| 15:00 | Done. Numbers are unlinked automatically; no clean-up needed. | |

## The exact messages

Codes are short and stable: they come from each student's KlassApp number.
Typing is case-insensitive and extra spaces are fine (`  bind   9035 `).

**Bind reply** (after `BIND <code>`):

```
✅ You are now following *Amina Nakato* in *P.7 A* at <demo school>.

What to expect: when the school releases this student's marks during the demo, they arrive in this chat.

Reply MARKS to see marks now.

Your number is removed after 45 minutes.
Privacy: <privacy notice link>
```

**Marks reply** (after `MARKS` or `RESULTS` from a bound number), the latest
exam of that student as a table:

````
📊 *Marks for Amina Nakato* · P.7 A
End of Term Examination · Term III

```
Subject           Mark  Grade
Mathematics         78  C3
Science             64  C5
English             91  D2
Social Studies      55  C6
-----------------------------
Total              288
Average           72.0
```
````

(The student, exam and numbers above are the test fixture; on the day the
reply shows the student that was bound.)

**Unknown code** (after `BIND 0000`, or a code from a school that is not the
demo school):

```
Sorry, we could not find a demo student with the code *0000*.

Open <site>/demo to see the demo class, then tap *Bind* next to a student.
```

**MARKS from a number that is not bound**:

```
This number is not following a demo student right now.

Open <site>/demo, tap *Bind* next to a student, then reply MARKS.
```

**JOIN** gets the existing soft-launch reply, which ends with "Reply DEMO to
try a sample school." That is expected.

## If something goes wrong

| Symptom | Check |
| --- | --- |
| No WhatsApp buttons on `/demo` | `WHATSAPP_DEMO_NUMBER` is not set. |
| The class list is empty | `WHATSAPP_DEMO_PARENT_USER_ID` does not point to a parent in the demo school, or the demo exam is missing: run `php artisan demo:refresh`. |
| No replies at all | `WHATSAPP_REPLIES_ENABLED` is `false`, or the webhook is not reaching the server. |
| A number stays linked | The scheduler is not running `demo:expire-chats`. Run it by hand: `php artisan demo:expire-chats`. |
| "Too many requests" on `/demo` | The page allows 60 loads a minute per address. Wait a minute. |

Only numbers that message the demo number get replies, and replies only ever
go back to the number that sent the message. Numbers already linked to a real
school are never rebound by `BIND`.
