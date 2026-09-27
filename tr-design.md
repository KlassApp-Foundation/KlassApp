# Teacher dashboard design
on teacher/attendance/add, there is no form to record attendance, do git checkout refactor-sep and check the UI design on that page, it can help
 you when designing the current one.
- Comment out *marks* on the menu as it looks a duplicate of exams, yet we need exams only
- Comment out Class Streams
- Currently, when you tap Notice, it routes you to the teacher/dashboard#notices, which is not correct. Let it take the user to the right place

## Constraints
- when you checkout to refactor-sep, keep in mind that it was actually dumped. So you can just use it to pull UX designs only
- Check knowledge.md before implementation, and keep in mind that it's the driver, so follow it correctly
- First plan and categorize these changes in tr.md, then keep putting a checkmark imoji on the one you finish
