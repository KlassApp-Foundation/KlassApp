# Frequently Asked Questions

---

## For Parents

### How do I get started?

When your school activates KlassApp, you'll receive a WhatsApp message from the bot inviting you to register. Follow the prompts to link your child. If your school supports self-registration, you can also text the bot your child's LIN (Learner Identification Number) to get started immediately.

### Is it free?

Receiving messages is always free. Sending messages uses your standard WhatsApp data, which is often zero-rated on MTN and Airtel in Uganda, meaning it doesn't count against your data bundle.

### Do I need to install anything?

No. Everything works inside WhatsApp, the app already on your phone. There is no account to create, no password to remember, and no separate app to download.

### How do I check my child's fees?

Send "fees" to the bot. You'll receive a full breakdown of what's due and when.

### How do I check my child's grades?

Send "results" or "grades" to the bot. You'll receive the latest term results.

### How do I report my child absent?

Send "absent" followed by the reason to the bot. The school's attendance records are updated in real time.

### What about my National ID (NIN)?

If the school requires NIN for verification, you send it once during registration. It is hashed (converted to a code) the moment it arrives and is never stored as plaintext. Only the school can verify your identity; KlassApp staff cannot see your NIN.

### Can I opt out?

Yes. Reply "OPTOUT" at any time to stop receiving messages. Reply "OPTIN" to re-enable them. Your phone number stays linked either way.

### I have children at different schools. Will this work?

Yes. If both schools use KlassApp, everything comes through your one WhatsApp number. Each school's data is kept separate.

### Can my child's other parent also get messages?

The school can link multiple parents to the same child. Speak to the school if you need a second parent added.

### What if I lose my phone or change my number?

Contact your school. They can update your linked phone number in their records.

---

## For Schools

### How is KlassApp different from SMS?

SMS costs 30 UGX per message, has ~70% delivery rate, and is one-way. KlassApp WhatsApp costs 0-15 UGX per message, delivers at 95%+, and parents can reply to check fees, report absence, or ask for grades.

### How is KlassApp different from other school apps?

Most school apps require parents to download, register, and remember yet another password. KlassApp works inside WhatsApp, the app parents already use daily. Zero friction.

### Does KlassApp replace our existing school system?

No. KlassApp adds a parent-facing communication layer on top of whatever you already use, whether that's a high-end ERP, a spreadsheet, or paper records.

### What data do we need to provide?

Your student roster: student names, their LINs (if available), and parent phone numbers. A CSV export from your EMIS system or school records is all it takes.

### How long does setup take?

Most schools are up and running in under an hour. Uploading the student roster takes about 10 minutes for a school of 500 students.

### Is parent data secure?

Yes. Parent phone numbers are never visible to other parents. Messages are sent individually (no broadcast groups). NIN data is hashed immediately on receipt. All data is stored in Uganda.

### What if a parent opts out?

They can reply "OPTOUT" at any time. The school sees which parents have opted out in the delivery dashboard. Parents can re-enable with "OPTIN".

### What kind of support do you offer?

Starter schools get email support. Growth and Premium schools get priority support with dedicated onboarding assistance.

### Can we send messages in bulk?

Yes, and unlike WhatsApp broadcast lists, our messages are automated, scheduled, role-based, and track delivery per parent.

### Does it work on slow networks?

Yes. WhatsApp messages are lightweight and deliver reliably even on 2G/3G connections.

---

## Technical

### Does KlassApp store any parent data?

We store only what's needed to deliver the service:
- Phone number (for message delivery)
- Linked children (for personalization)
- Hashed NIN (the original is immediately discarded after verification)

We never store message content beyond what's required for delivery tracking.

### Is my data encrypted?

Yes. All data in transit is encrypted via WhatsApp's end-to-end protocol. Data at rest is encrypted in our database.

### Where is data stored?

All data is stored within Uganda, in compliance with local data protection regulations.

### What happens if KlassApp goes down?

Parent messages queue up and are delivered once service resumes. The school's own systems remain unaffected. KlassApp is a separate layer, not a replacement.

### Can KlassApp integrate with our existing ERP?

Yes. KlassApp's custom WhatsApp extension layer bridges any school ERP to WhatsApp, whether it's a local system, an international platform, or a custom-built solution.

### What about EMIS/LIN integration?

KlassApp connects to Uganda's EMIS database via three paths:
1. **CSV import** (available now): upload your EMIS export
2. **Self-registration** (coming soon): parents link using their child's LIN
3. **Ministry API** (long-term): real-time verification

### What happens to messages in the 24-hour WhatsApp window?

Meta's 24-hour service window means we can proactively message parents about fees, grades, and attendance within that window, and the messages are free. Replies from parents extend the window. KlassApp optimizes message timing to maximize free delivery within the window.
