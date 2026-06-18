# KlassApp: The school in every parent's pocket.

> Grades, fees, and attendance. Delivered to parents on WhatsApp.
> No app to install. No login required. One message.

---

## The Problem

In Uganda, over **20 million students** attend school, yet the average parent has no real-time visibility into their child's education.

- **Grades** arrive on a report card once a term, often weeks late
- **Fee balances** are a surprise at the school gate
- **Attendance status** is unknown until the child doesn't come home
- **School events** are communicated by handwritten note, if at all
- **Emergency closures** travel by word of mouth

Schools try to bridge this gap with SMS gateways, Facebook groups, and printed circulars, but these are fragmented, costly, and one-way. A school sending 500 SMS reminders at 30 UGX each spends 15,000 UGX per blast. Parents miss messages because they changed their SIM or the SMS landed in spam.

Meanwhile, **WhatsApp sits in every parent's pocket**. 98% of Ugandan smartphone users have it, with higher engagement than SMS, email, or any app.

---

## The Solution

KlassApp is two products working as one:

- **For schools**: Grade publishing, fee management, attendance tracking, role-based staff access, and a real-time delivery dashboard.
- **For parents**: A WhatsApp interface that delivers everything above, instantly, without any app or login.

Neither works without the other. The school provides the data. WhatsApp delivers it to the parent who cares most.

KlassApp doesn't replace a school's existing system. It adds a parent-facing communication layer on top.

### For Schools (Admins + Staff)

```mermaid
flowchart LR
    S[School Admin] -->|Publishes grades| G[Grades sent via WhatsApp]
    S -->|Records attendance| A[Daily attendance pushed]
    S -->|Triggers fee notice| F[Fee reminders automated]
    S -->|Creates event| E[Event broadcasts]
    S -->|Uploads health record| H[Nurse alerts to parents]

    G --> P[Parent receives on WhatsApp]
    A --> P
    F --> P
    E --> P
    H --> P
```

- **Admin**: One-click grade publishing, fee reminder campaigns, school-wide broadcasts, delivery dashboard with real-time KPIs
- **Teacher**: Voice-mark attendance, push grade notifications, communicate class-specific updates
- **Bursar**: Automated fee reminders with balance breakdowns, payment confirmation receipts, overdue escalation
- **Librarian**: Overdue book alerts, new arrivals announcements, library closure notices
- **Nurse**: Health record updates, immunization reminders, sick-day notifications, medical emergency alerts
- **Secretary**: Exam schedule broadcasts, term calendar distribution, transport route changes

### For Parents

```mermaid
sequenceDiagram
    participant P as Parent
    participant B as KlassApp Bot
    participant S as School System

    P->>B: Send any message
    B->>P: Interactive Menu
    P->>B: Select "Grades"
    B->>S: Fetch results
    S->>B: Student grades
    B->>P: Term results per child

    P->>B: Select "Fees"
    B->>S: Fee balances
    S->>B: Balance data
    B->>P: Outstanding + due amounts

    P->>B: Select "Attendance"
    B->>S: Attendance records
    S->>B: Monthly summary
    B->>P: Present/Absent/Late breakdown
```

- **Grades received instantly**, not at end of term
- **Fee balances on demand**, no more surprises at the gate
- **Daily attendance**, know when your child misses class
- **Event calendar**, never miss a school meeting
- **No app to install**, works inside WhatsApp, zero data cost to learn
- **Self-registration**, link yourself to your child using their LIN (Learner Identification Number)

---

## Why It Works

```mermaid
flowchart LR
    subgraph Channel["Why WhatsApp Works in East Africa"]
        W1["98% smartphone users have WhatsApp"]
        W2["Zero-rated on MTN & Airtel"]
        W3["Already used daily by parents"]
        W4["Works on low-end devices over 2G"]
    end

    subgraph Bridge["KlassApp Bridge"]
        B1["Schools push notifications"]
        B2["Parents pull information"]
        B3["Two-way conversation"]
        B4["Cost: fraction of SMS"]
    end

    subgraph Outcomes["Results"]
        O1["Parents engaged, not just notified"]
        O2["Fee collection improves"]
        O3["Attendance accountability"]
        O4["Admin burden drops"]
    end

    Channel --> Bridge --> Outcomes
```

---

## The East African Opportunity

Uganda has **74,000+ schools** (primary, secondary, and tertiary). Over **90% use WhatsApp informally** (class groups, parent-teacher chats) but none have a structured, integrated system.

The **LIN** (Learner Identification Number) system, mandated by the Ministry of Education, gives every Ugandan student a unique 12-digit identifier. KlassApp uses this to solve the hardest problem in edtech: **connecting the right parent to the right student at national scale**.

- **Path 1 — CSV Import**: Schools upload their LIN data. Parents are linked in bulk. Days to deploy.
- **Path 2 — Self-Registration**: Parents register themselves by sending their child's LIN via WhatsApp. No admin effort. National reach.
- **Path 3 — Ministry API**: Direct integration with the Ministry's LIN database for real-time verification. (Long-term.)

This isn't a school management system. It's a **parent engagement network**, and every Ugandan student is a node waiting to be connected.

No other school communication platform in East Africa has built this connection. KlassApp isn't just a tool. It's the infrastructure layer that connects every school, every student, and every parent in the national education system.

The same gap exists across **Kenya (35,000+ schools)**, **Tanzania (20,000+ schools)**, and **Rwanda (4,000+ schools)**. Every market has the same profile: high WhatsApp penetration, low parent digital engagement, and school systems that communicate by paper. KlassApp's architecture works identically across all four countries. The LIN integration is Uganda-specific today, but every East African country has an equivalent national student ID system we are mapping.

---

## What's in These Docs

| Doc | For |
|---|---|
| **[for-schools.md](for-schools.md)** | School admins and staff: how to deploy, configure roles, run notifications |
| **[for-parents.md](for-parents.md)** | Parents: how to link, use the menu, manage preferences |
| **[ecosystem.md](ecosystem.md)** | The bigger picture: market, competition, vision, roadmap |
| &nbsp;&nbsp;└ **[roadmap.md](roadmap.md)** | Detailed feature timeline and milestones |
| **[faq.md](faq.md)** | Common questions from schools and parents |

---

*KlassApp is built by GegoSoft Technologies. We believe that when parents are informed, students perform better.*
