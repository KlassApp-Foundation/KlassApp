# Interactive Menu System

The WhatsApp bot uses interactive **List Messages** to present role-appropriate options to users. When a user sends any message to the business number, the bot responds with a greeting and a menu tailored to their role.

---

## How It Works

```
User sends any message
        ↓
handleInbound() processes the webhook
        ↓
identify() resolves phone → user → role (usergroup_id)
        ↓
sendMenu() builds and sends the interactive List Message
        ↓
buildMenuSections() generates role-specific sections/rows
        ↓
User selects a row → Evolution API sends back the selection
        ↓
handleInbound() routes the selected keyword
```

---

## Role-Based Routing

Menus are determined by the user's `usergroup_id` in the `users` table.

| usergroup_id | Role | Menu Includes |
|---|---|---|
| 3 | Admin | School management options, student lookup, reports |
| 5 | Teacher | Class list, mark attendance, view grades |
| 6 | Student | View grades, view timetable, check attendance |
| 7 | Parent | Child grades, fees, attendance, events, timetable |
| 10 | Receptionist | Student lookup, parent contact, attendance |
| 11 | Accountant | Fee balances, payment reports, receipts |

### Dual-Role Handling

A user can have both a staff role (admin, teacher, accountant, receptionist) AND be a parent. The bot detects this by checking if the user has linked children. If yes:

- The staff menu items are shown **plus** parent-specific items (grades, fees, attendance for their own children)
- Both sets of options are combined into a single menu with multiple sections

---

## List Message Format

WhatsApp List Messages present a scrollable list when the user taps a button. KlassApp's format:

```
━━━━━━━━━━━━━━━━━━━
Title: Welcome to KlassApp!
━━━━━━━━━━━━━━━━━━━

Hello Joseph! How can I help you today?

▼ Section: Grades & Academics
  • View Grades        → grades
  • View Timetable     → timetable

▼ Section: Payments
  • Fee Balance        → fees
  • Pay Fee            → pay_fee

▼ Section: Attendance
  • My Attendance      → attendance

▼ Section: School Info
  • Upcoming Events    → events
  • School Calendar    → calendar
━━━━━━━━━━━━━━━━━━━
[View Options] (button)
```

### Technical Structure

```php
$sections = [
    [
        'title' => 'Grades & Academics',
        'rows' => [
            ['title' => 'View Grades', 'description' => 'Check your exam results'],
            ['title' => 'View Timetable', 'description' => 'See class schedule'],
        ],
    ],
    [
        'title' => 'Payments',
        'rows' => [
            ['title' => 'Fee Balance', 'description' => 'Check outstanding fees'],
            ['title' => 'Pay Fee', 'description' => 'Make a payment'],
        ],
    ],
];

$this->whatsApp->sendList(
    phone: '+256701234567',
    title: 'Welcome to KlassApp!',
    sections: $sections,
    description: 'Hello Joseph! How can I help you today?',
    footerText: 'KlassApp School Management',
    buttonText: 'View Options',
);
```

### Limits

- Maximum 10 rows total across all sections
- Each row title: max 24 characters
- Each row description: max 72 characters
- Section titles: max 24 characters
- Description text: max 1024 characters

---

## Menu Building Logic

`WhatsAppController::buildMenuSections()` builds the menu dynamically:

```php
private function buildMenuSections(User $user): array
{
    return match ((int) $user->usergroup_id) {
        3       => $this->adminMenu(),
        5       => $this->teacherMenu($user),
        6       => $this->studentMenu(),
        7       => $this->parentMenu($user),
        10      => $this->receptionistMenu(),
        11      => $this->accountantMenu(),
        default => $this->defaultMenu(),
    };
}
```

For dual-role (staff + parent) users, the method merges the staff menu with parent menu sections.

---

## Keyword Handling

When a user selects a menu item (or types a keyword), `WhatsAppController::handleInbound()` routes it:

| Keyword / Selection | Action | Data Source |
|---|---|---|
| `grades` | Fetch latest exam results per child | `Academics\Exam`, `Academics\Marks` |
| `fees` | Check fee balance per child | `FeesCategories` |
| `attendance` | Get attendance summary per child | `Attendance` |
| `events` | List upcoming school events | `Events` |
| `timetable` | Show class timetable | `StudentAcademic` → standard |
| `menu` | Re-send the interactive menu | `buildMenuSections()` |
| `lin` | Start LIN self-registration flow (Path 3) | `student_records` |
| `register` | Start parent self-registration flow | `WhatsAppUser` |
| `optin` | Re-enable WhatsApp notifications | Sets `opted_in = true` |
| `optout` | Disable WhatsApp notifications | Sets `opted_in = false`, `unsubscribed_at = now()` |

### Multi-Child Handling

For parents with multiple children, GRADE, FEE, and ATTENDANCE queries iterate all linked children and send one message per child with personalised data:

```
*Grade Report*
_Amope Nandawula_

Mathematics: A (85%)
English: B+ (72%)
Science: A- (80%)
Social Studies: B (68%)

---
*Grade Report*
_Kizito Nandawula_

Mathematics: B+ (74%)
English: A (90%)
Science: B (65%)
Social Studies: A- (82%)
```

---

## Opt-In / Opt-Out Flow

| Command | Effect | Next Action |
|---|---|---|
| `optin` | Sets `opted_in = true`, clears `unsubscribed_at` | Sends menu |
| `optout` | Sets `opted_in = false`, `unsubscribed_at = now()` | No further proactive messages |
| `stop` | Same as optout | Silent confirmation |
| `start` | Same as optin | Silent confirmation |

Proactive notifications (grade publish, fee reminders, etc.) only send to opted-in users. The `OutboundWhatsAppService` checks `WhatsAppUser::optedIn()` before sending.

---

## Menu Examples by Role

### Parent Menu

```
━━━━━━━━━━━━━━━━━━━
Welcome to KlassApp!
━━━━━━━━━━━━━━━━━━━

▼ My Children
  • View Grades        → grades
  • Check Fees         → fees
  • View Attendance    → attendance

▼ School Info
  • Upcoming Events    → events
  • Class Timetable    → timetable

▼ Account
  • Settings           → menu
  • Opt Out            → optout
━━━━━━━━━━━━━━━━━━━
```

### Teacher Menu

```
━━━━━━━━━━━━━━━━━━━
Welcome to KlassApp!
━━━━━━━━━━━━━━━━━━━

▼ My Classes
  • My Students        → students
  • Record Attendance  → attendance
  • Enter Grades       → grades

▼ Schedule
  • My Timetable       → timetable

▼ My Children (if parent)
  • View Child Grades  → grades
  • Child Attendance   → attendance
━━━━━━━━━━━━━━━━━━━
```

---

## Troubleshooting

### "Wrong menu shown for user"

Check the user's `usergroup_id`:

```sql
SELECT id, name, usergroup_id FROM users WHERE id = <user_id>;
```

If `usergroup_id` doesn't match the expected values (3, 5, 6, 7, 10, 11), the `defaultMenu()` is returned — a generic menu with basic options.

### "Menu has too few options"

- Verify `buildMenuSections()` has all sections configured for that role
- For parents, check that the user has linked children via `user->children()` relationship
- For dual-role users, ensure both the staff and parent sections are merging correctly

### "List Message sends but user sees no options"

- WhatsApp List Messages require the user to tap the button (labelled "View Options" by default)
- Some older WhatsApp versions on KaiOS or GB WhatsApp may not support interactive messages
- Fallback: if the List Message send fails, `sendMenu()` falls back to `sendText()` with a text-based numbered list
