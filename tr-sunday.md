## Classes
On teacher/classes, under streams, if a teacher teaches in P.5 stream A and P.5 stream B, they should be displayed as *A, B* under streams
Under Action, change roster to Manage, so a teacher can manage his class, this properly matches with what is expected by teachers in simple terms,
and when navigated to manage, eg teacher/classes/52?academic_year_id=11, the student list there should be like a table under admin/students, also a
 teacher should be able to edit the student

## Timetable
under /teacher/timetable, the teacher should be able to do CRUD of his timetable, I think these files to handle its CRUD under the teacher are 
already there

## Attendance
under teacher/attendance/add, change select class to class, and put (class, Date, Session) in flex ie on one row to minimize space usage and 
maintain nice design, also still under the same page, class selector doesn't show classes, yet the selector at teacher/attendance shows them

## Events
Re-shape the /teacher/events page to show events in a table formatt. Also allow the teacher to access the calendar on the same page if possible

## Verdict
- Maintain nice UX designs

