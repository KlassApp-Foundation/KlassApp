# Frequently Asked Questions

---

## For Schools

### How much does it cost?

KlassApp uses WhatsApp's free 24-hour service window for most messages. Replies to parent queries cost nothing. Proactive notifications (grade publish, fee reminders) sent outside that window use Meta-approved templates at a tiny cost — typically 0-15 UGX per message, compared to 30 UGX for SMS. Most schools see **60-80% of their messages delivered for free**.

### Do parents need to install an app?

No. Everything works inside WhatsApp. Parents interact with a contact just like any other chat.

### How do we onboard parents?

Two ways:
1. **Import** — Upload your student roster (EMIS export or CSV). The system links parent phone numbers and sends a welcome message.
2. **Self-registration** — Parents send their child's LIN to the bot and are verified by their NIN. No admin effort needed.

### What if a parent doesn't have WhatsApp?

Then they can't receive messages. However, with 98% WhatsApp penetration among Ugandan smartphone users, this affects very few families. Those without smartphones continue with traditional communication (circulars, SMS).

### Can parents opt out?

Yes. Replying "OPTOUT" immediately stops all messages. Replying "OPTIN" re-enables them. The school admin can see opt-out counts on the dashboard.

### Is it secure?

- All communication between KlassApp and the school is encrypted
- Parent NINs are SHA-256 hashed and never stored as plaintext
- Messages are sent individually (not in broadcast groups)
- The school controls exactly what data is shared
- The school's data stays in their own database

### What roles can use KlassApp?

Every school role has something:

| Role | Use Case |
|---|---|
| **Admin** | Full control — campaigns, dashboard, settings |
| **Teacher** | Grade publishing, attendance alerts, class broadcasts |
| **Bursar** | Fee reminders, receipts, overdue escalation |
| **Librarian** | Overdue book alerts, new arrivals |
| **Nurse** | Health record updates, sick-day alerts |
| **Secretary** | Calendar distribution, exam schedules, transport updates |

### Does it integrate with our existing school management system?

KlassApp connects to your school's database. If you already use a school management system, KlassApp can read grade, fee, and attendance data from it. For schools without an existing system, KlassApp's platform includes what you need.

### What happens if the school internet goes down?

Messages queue locally and are delivered when the connection is restored. No data is lost.

---

## For Parents

### Is this free for me?

Receiving messages costs you nothing. Sending messages uses your regular WhatsApp data. Most Ugandan networks (MTN, Airtel) zero-rate WhatsApp, so even sending messages typically costs nothing.

### How do I start?

Your school can link you automatically by importing your phone number from their records. You'll receive a welcome message. Alternatively, if your school supports self-registration, send "lin" to the bot to link yourself.

### Can I check my child's information anytime?

Yes. Send any message to the KlassApp bot and you'll receive a menu. Tap "View Options" to see grades, fees, attendance, and events.

### What if I have more than one child?

All your children at the school are linked to the same number. When you check grades, you'll receive results for each child separately.

### Will I be added to a group chat?

No. All messages are sent to you individually — never as part of a broadcast group. Other parents cannot see your phone number or your child's information.

### How do I stop messages?

Reply "OPTOUT" to the bot. You'll stop receiving all messages. Your phone remains linked in case you want to opt back in later (reply "OPTIN").

### Is my personal data safe?

- Your **phone number** is stored only for messaging purposes
- Your **NIN** (if used for self-registration) is hashed immediately and never stored as plaintext
- Your **child's data** belongs to the school, not to KlassApp
- The school controls what data is accessed and when

### My child finished school at this school. What happens?

Your phone link is managed by the school. When your child leaves, the school can deactivate the link. You can also opt out at any time.

### Can I contact multiple schools through one bot?

Yes. If you have children at different schools that both use KlassApp, the bot handles them all from the same WhatsApp contact.

---

## Technical Questions

### What is the 24-hour service window?

When a parent sends a message to the bot, a 24-hour window opens during which the school can send proactive messages **for free**. This is Meta's pricing policy — service conversations cost nothing. KlassApp is designed to maximize in-window delivery.

### How does the self-registration (LIN) process work?

The Learner Identification Number (LIN) is a 12-digit unique identifier assigned to every Ugandan student by the Ministry of Education. When a parent sends their child's LIN to the bot, it looks up the student in the school's records and asks for the parent's NIN. If the NIN hash matches, the parent is linked to all children associated with that NIN.

### Why LIN and not just the student name?

Student names are not unique — there are dozens of "Amope Nandawula" in Ugandan schools. The LIN is guaranteed unique nationwide. This means self-registration works even if the parent doesn't know which class or section their child is in.

---

## Still Have Questions?

Contact the school directly, or reach out to KlassApp support through your school admin.
