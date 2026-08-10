---
paths:
  - app/Http/Controllers/Teacher/MarksController.php
---

# Teacher

## Teacher mark save requires exam ownership
saveExamMarks must abort 403 unless exam.school_id === auth.school_id AND exam.teacher_id === auth.id. School-only checks allow same-school teachers to POST another teacher’s exam URL. View Entered Marks must not pass undefined view vars (exms); load examType/academicTerm/academicYear/subject/teacher and avoid brittle Term I match()/officialTeacher FK.
