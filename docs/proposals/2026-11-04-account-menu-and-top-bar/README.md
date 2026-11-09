# Account menu and top bar, before and after (demo data only)

Taken from the app on SQLite with the Demo Junior School seeder (plus one demo parent), signed in as each demo role, at 1280 and 375 wide. "before" is `main` @ `d0998db`, "after" is this branch.

- `*-menu.png`: the account menu open. Before, a teacher, student, receptionist or bursar menu is clipped to 120px at desktop width (only the identity row and "Change password" show, "Log out" is scrolled out of view), the librarian's menu is only "Log out", and the parent has no menu at all. After, every role shows Profile, Change password, Log out (the school admin also has Settings and Help).
- `after-teacher-1280-1-dashboard.png`: the teacher dashboard with the admin's stat tiles, and the bell and account card in the sidebar footer like the admin's.
- `chrome-before.json`, `chrome-after.json`: measured per role and width. `sidebarFooter` / `topbarAvatar` / `topbarCard` count the visible chrome, `menu` lists the menu items, `menuBox` is the popover's top, height and whether it scrolls, `sw` is the page scroll width. Before, the teacher, student, receptionist, bursar and librarian pages scroll sideways at 375 (`sw` 430 to 449); after, none do.
