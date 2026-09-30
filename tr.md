# Teacher dashboard redesign — plan & progress

Source brief: tr-design.md (unchanged). refactor-sep is a dumped branch — UX reference only.

## A. Attendance recording form (teacher/attendance/add)
Redesigned `resources/assets/js/components/attendance/Create.vue` to the refactor-sep UX:
- [x] Class / Date / Session in a 3-column grid (`grid gap-4 md:grid-cols-3`)
- [x] Class options labelled `standard - section / stream`
- [x] Session as inline radio group (Forenoon / Afternoon)
- [x] "Select Students" button, then Present / Absent two-panel layout
- [x] Submit Attendance / Reset buttons
- [x] Verified: form renders, class dropdown populates, panels + submit appear (end-to-end click-through)

## B. Teacher menu changes (config/navigation.php)
- [x] Commented out *Marks* item (duplicate of Exams — exams only)
- [x] Commented out *Class Streams* item

## C. Notices routing
- [x] Notices menu item now uses `route: teacher.notices.index` → /teacher/notices (was `teacher/dashboard#notices`)

## D. Verification
- [x] Browser check at 375 / 414 / 768 / 1280: attendance form (3-col grid ≥768, stacked <768),
      menu without Marks/Class Streams, Notices href → /teacher/notices — all pass
