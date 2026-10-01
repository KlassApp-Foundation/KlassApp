---
title: WhatsApp how-to texts
---

# Soft-launch WhatsApp how-to texts

Six how-to texts built on the [WhatsApp how-to format](https://github.com/KlassApp-Foundation/KlassApp/tree/main/design/system/concepts/whatsapp-howto): one `*Title*` line, at most 5 numbered steps, one "what happens next" line, and the Help link as the last line. Only `*bold*`, no emoji, full `https://` URLs. Character and step counts were measured with the script below (limits: 500 characters, 5 steps).

Keyword checks against the live app (`app/Http/Controllers/Api/WhatsAppController.php`): `report` → report card PDF for parents; fee reminder is sent *by* the bursar from the Fees page; attendance save happens in the app, the WhatsApp message is automatic to parents.

## 1. Parent — get the report card

```
*See your child's report card*

1. Send *report* to this number.
2. If you have more than one child, reply with the number next to your child's name.
3. The report card arrives as a PDF. Tap it to open.

The link works for 7 days. After that, send *report* again.

https://klassapp.xyz/help/send-report-cards-whatsapp
```

## 2. Parent — know what you owe (fees)

```
*Check what you owe*

1. The bursar sends you a fee reminder on this number.
2. Each message shows only your child's balance.
3. Reply *help* any time to see what you can ask for.

You never have to click anything to see your balance — you get a message when it changes.

https://klassapp.xyz/help/parents/whatsapp
```

## 3. Teacher — mark attendance

```
*Mark today's attendance*

1. Open app.klassapp.xyz and log in.
2. In the menu, choose *Attendance*.
3. Everyone starts as present. Tap a name to change it to *Absent* or *Late*.
4. Save attendance.

Parents of absent students get a WhatsApp message the same morning.

https://klassapp.xyz/help/teachers/attendance
```

## 4. Teacher — enter marks

```
*Enter marks*

1. Open app.klassapp.xyz and log in.
2. In the menu, choose *Exams & Marks*.
3. Choose the exam, your class and subject.
4. Type each student's marks.

Marks stay private until the headteacher publishes results.

https://klassapp.xyz/help/marks
```

## 5. Bursar — send fee reminders

```
*Send fee reminders*

1. Log in at app.klassapp.xyz.
2. In the menu, choose *Fees & Payments*.
3. Choose the class, for example *P.5*.
4. Check the list and the amount each parent owes.
5. Send reminders.

Each parent gets one WhatsApp message with their own balance only.

https://klassapp.xyz/help/bursars/
```

## 6. Admin — publish and share report cards

```
*Put report cards on WhatsApp*

1. Log in at app.klassapp.xyz.
2. In the menu, choose *Exams & Marks* and approve the marks.
3. In *Report Cards*, choose the class and term and download the PDFs.
4. Tell parents to send *report* to the school's KlassApp number.

Parents who ask get the PDF straight in their WhatsApp chat.

https://klassapp.xyz/help/generate-report-cards
```

## Measured sizes

| # | Text | Characters | Steps | Link |
| --- | --- | --- | --- | --- |
| 1 | Parent — report card | 323 | 3 | /docs/help/send-report-cards-whatsapp |
| 2 | Parent — what you owe | 320 | 3 | /docs/help/parents/whatsapp |
| 3 | Teacher — attendance | 320 | 4 | /docs/help/teachers/attendance |
| 4 | Teacher — marks | 265 | 4 | /docs/help/marks |
| 5 | Bursar — fee reminders | 314 | 5 | /docs/help/bursars/ |
| 6 | Admin — report cards | 378 | 4 | /docs/help/generate-report-cards |
