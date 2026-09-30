# KlassApp - email and SMS template export (for design review)

Exported read-only from the **production** database on 2026-09-28. Source tables: `mailtemplates` (15 rows) and `sms_templates` (25 rows). Nothing was changed; this file is the only artifact.

## Read this first
- Placeholders use a colon prefix (`:name`, `:school_name`, ...) and are filled at send time. Keep them intact when rewriting copy.
  - Tokens in use: `:application_no`, `:attachments`, `:category`, `:contact_no`, `:date`, `:description`, `:emailid`, `:end_date`, `:fullname`, `:location`, `:mail`, `:message`, `:name`, `:resetlink`, `:role`, `:school_name`, `:select`, `:serve_at`, `:start_date`, `:subject`, `:title`, `:url`
- The `<br>` tags are the line breaks these templates render with; the source is a single text field per template.
- Sign-off is the generic "Administration Team" everywhere.
- Redaction check (requested): **no row contains a real person's name, phone number, or email address** - all identity fields are placeholders, so no redaction was applied.
- Two content artifacts to treat as replaceable defaults, left as-is for fidelity:
  - Email `event_reminder` says "posted for this church" (template origin leftover).
  - SMS `Event` ends with the third-party URL `https://churchcms.appsexpress.net` (template origin leftover).
  - Email `login` has a straight-quote typo: `Don"t` (from the legacy source).

## Email templates (`mailtemplates`) - 15 rows

### ✉ `login` (id 1)

**Subject:** Logged In

````text
Hi :name <br>
                                Successful Authorization. <br>
                                You have successfully logged into your account. <br>
                                Don"t recognize this activity? <br>
                                Please change password for your email immediately <br>
                                Thanks & Regards <br>
                                Administration Team <br>
````

### ✉ `new_user_register` (id 2)

**Subject:** New User Registration

````text
Hello ! <br>
                                New user has registered - :mail. Please login to see details.<br>
                                Thanks & Regards <br>
                                Administration Team <br>
````

### ✉ `reset_password` (id 3)

**Subject:** Reset Password

````text
Hi :name <br>
                                Please click below link to reset your password. <br>
                                <a href=":resetlink" style="border: none; color: white; padding: 10px 15px; text-align: center; text-decoration: none; display: inline-block; font-size: 16px; margin: 4px 2px; cursor: pointer; background-color: #008CBA;">Reset Password</a> <br>
                                Thanks & Regards <br>
                                Administration Team <br>
````

### ✉ `expired_approve_alert` (id 4)

**Subject:** New Expired Alert

````text
Hi <br>
                                Subscription Expiration Details<br><br>

                                School Name - :school_name <br>
                                User Name   - :name <br>
                                End Date    - :end_date <br><br>

                                New expired has been posted for this school <br>
                                If you want to continue subscription , Please click the below link<br><br>

                                :url <br><br>

                                Thanks & Regards <br>
                                Administration Team
````

### ✉ `site_expired_mail` (id 5)

**Subject:** Site Expired Mail

````text
Hi <br>
                                Subscription Expiration Details<br><br>

                                School Name - :school_name <br>
                                User Name   - :name <br>
                                End Date    - :end_date <br><br>

                                Your site going to expiry within a week <br><br>
                                Thanks & Regards <br>
                                Administration Team
````

### ✉ `contact` (id 6)

**Subject:** Contact

````text
Hi <br>
                                Contact Details.<br><br>

                                Name          - :fullname <br>
                                Email         - :emailid <br>
                                Serve         - :serve_at <br>
                                Role          - :role <br>
                                Phone number  - :contact_no <br>
                                Select        - :select <br><br>

                                Contact Details Created <br>
                                Thanks & Regards <br>
                                Administration Team
````

### ✉ `send_mail` (id 7)

**Subject:** :subject

````text
Hi :name, <br>
                                :message <br>
                                <a href=":attachments" style="border:none; color:white; padding:10px 15px; text-align:center; text-decoration:none; display:inline-block; font-size:16px; margin:4px 2px; cursor: pointer; background-color:#008CBA;">Click Here</a> <br>
                                Thanks & Regards <br>
                                Administration Team
````

### ✉ `calendar_event` (id 8)

**Subject:** Calendar Event

````text
Hi <br>
                                Event Details.<br><br>

                                Title       - :title <br>
                                Location    - :location <br>
                                Category    - :category <br>
                                Start Date  - :start_date <br>
                                End Date    - :end_date <br><br>

                                New Event Created <br>
                                Thanks & Regards <br>
                                Administration Team
````

### ✉ `event_reminder` (id 9)

**Subject:** Event Reminder

````text
Hi <br>
                                Event Reminder Details<br><br>

                                School Name   - :school_name <br>
                                Title         - :title <br>
                                Description   - :description <br>
                                Location      - :location <br>
                                Start Date    - :start_date <br>
                                End Date      - :end_date <br><br>

                                New event has been posted for this church.<br><br>
                                Thanks & Regards <br>
                                Administration Team
````

### ✉ `birthday_reminder` (id 10)

**Subject:** Birthday Wishes

````text
:message <br><br>
                                Thanks & Regards <br>
                                Administration Team
````

### ✉ `email_verification` (id 11)

**Subject:** Email Verification

````text
Hi :name <br>
                                To verify your account.<br>
                                <a href=":url" style="border: none; color: white; padding: 10px 15px; text-align: center; text-decoration: none; display: inline-block; font-size: 16px; margin: 4px 2px; cursor: pointer; background-color: #008CBA;">Click here to verify</a> <br>
                                Thanks & Regards <br>
                                Administration Team <br>
````

### ✉ `new_message` (id 12)

**Subject:** Send Mail to User

````text
Hi :name, <br>
                                :message <br>
                                Thanks & Regards <br>
                                Administration Team <br>
````

### ✉ `room_invitation` (id 13)

**Subject:** Room Invitation

````text
Hi :name, <br>
                                Title - :title <br>
                                Description - :description <br>
                                :message <br>
                                Thanks & Regards <br>
                                Administration Team <br>
````

### ✉ `change_password` (id 14)

**Subject:** Change Password

````text
Hi :name <br>
                                Your Password is changed successfully. <br>
                                Thanks & Regards <br>
                                Administration Team <br>
````

### ✉ `admission_confirmation` (id 15)

**Subject:** Admission Confirmation Mail

````text
Hi Sir/Madam <br>
                                Your Application No. :application_no for the admission in :school_name has been approved. <br>
                                Thanks & Regards <br>
                                Administration Team <br>
````

## SMS templates (`sms_templates`) - 25 rows

### 💬 `Event` (id 1)

````text
Hi.. Your event has been scheduled on :date at :location. For more details log in to church social App. https://churchcms.appsexpress.net
````

### 💬 `birthday_message` (id 2)

````text
Wishing you a happy birthday and a wonderful year.
Thanks & Regards
Administration Team
````

### 💬 `birthday_message` (id 3)

````text
May this special day bring you endless joy and tons of precious memories! Happy birthday.
Thanks & Regards
Administration Team
````

### 💬 `birthday_message` (id 4)

````text
Happy birthday! Here’s to a bright, healthy and exciting future!
Thanks & Regards
Administration Team
````

### 💬 `birthday_message` (id 5)

````text
Wishing you a wonderful day and all the most amazing things on your Big Day! Happy birthday.
Thanks & Regards
Administration Team
````

### 💬 `birthday_message` (id 6)

````text
Happy birthday! May your day be filled with lots of love and happiness.
Thanks & Regards
Administration Team
````

### 💬 `birthday_message` (id 7)

````text
May this year surprise you with full of joy and happiness! Happy birthday!
Thanks & Regards
Administration Team
````

### 💬 `birthday_message` (id 8)

````text
Sending you a birthday wish wrapped with all my love. Have a very happy birthday!
Thanks & Regards
Administration Team
````

### 💬 `birthday_message` (id 9)

````text
Many happy returns on your birthday today from all of us. We hope you have a wonderful day!
Thanks & Regards
Administration Team
````

### 💬 `birthday_message` (id 10)

````text
May your birthday be sprinkled with fun and laughter. Have a great day!
Thanks & Regards
Administration Team
````

### 💬 `birthday_message` (id 11)

````text
Happy Birthday! I hope you have a great day today and the year ahead is full of many blessings.
Thanks & Regards
Administration Team
````

### 💬 `reset_password` (id 12)

````text
Click this link :url to reset your password.
````

### 💬 `absent_message` (id 13)

````text
:message
Thanks & Regards
:school_name
````

### 💬 `birthday` (id 14)

````text
:message
Thanks & Regards
:school_name
````

### 💬 `work_anniversary_message` (id 15)

````text
Another year of excellence! Thanks for all the amazing work you do. Your effort and enthusiasm are much needed, and very much appreciated.
Thanks & Regards
Administration Team
````

### 💬 `work_anniversary_message` (id 16)

````text
From all of us… happy anniversary! Thank you for your hard work, your generosity, and your contagious enthusiasm.
Thanks & Regards
Administration Team
````

### 💬 `work_anniversary_message` (id 17)

````text
Congratulations on your work anniversary! We appreciate your energy, your kindness, and all the work you do, but most of all, we just appreciate you!
Thanks & Regards
Administration Team
````

### 💬 `work_anniversary_message` (id 18)

````text
Congratulations on your work anniversary. Working with a wonderful person like you was always a great experience.
Thanks & Regards
Administration Team
````

### 💬 `work_anniversary_message` (id 19)

````text
Sending heartiest wishes to the nicest employee! We are grateful to you for all the contributions that you afforded to make our company progressed.
Thanks & Regards
Administration Team
````

### 💬 `work_anniversary_message` (id 20)

````text
Many congratulations on your happy work anniversary! May you accomplish more successful working years with this organization. Wish you good luck.
Thanks & Regards
Administration Team
````

### 💬 `work_anniversary_message` (id 21)

````text
We feel lucky and glad to be a part of your team. Your exceptional leadership is beyond words. Happy work anniversary.
Thanks & Regards
Administration Team
````

### 💬 `work_anniversary_message` (id 22)

````text
A great employee like you is valuable for both the organization as well as co-workers. Well done and enjoy your happy work anniversary.
Thanks & Regards
Administration Team
````

### 💬 `work_anniversary_message` (id 23)

````text
This is to remind you that you have come a long way and your contributions have continued to inspire us. Wish you a very Happy Work Anniversary!
Thanks & Regards
Administration Team
````

### 💬 `work_anniversary_message` (id 24)

````text
Everyone requires a person with an abundance of positive vibe and confidence to get things done in a flawless manner. Thank you for being that person. Warm wishes on your work anniversary!
Thanks & Regards
Administration Team
````

### 💬 `admission_confirmation` (id 25)

````text
Hi Sir/Madam
Your Application No. :application_no for the admission in :school_name has been approved.
Thanks & Regards
Administration Team
````

---

Export method: SELECT-only reads via the Laravel Cloud console runner. No database writes, no deployments, no template changes.
