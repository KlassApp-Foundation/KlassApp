# Teacher's dashboard refactor and enhancement

## Incomplete

-   Show the real pages under these links on the teacher's side menu, now, when you tap them, they take you to /teacher/dashboard with a # then the page inteded to visit eg /teacher/dashboard#attendance, which keeps on /teacher/dashboard
    These pages include;
    **timetable**
    **attendance**
    **Notices**
    They should move the user to a relevant page, because all pages are already available

## Non Important

There are non-important pages for now, so you'll have to comment their links on the teacher's menu
Eg,
**Library**
**Students**
**Marks**

## To enhance

**Exam:**

-   remove edit exam
-   Re-do enter marks by teacher. Allow the teacher to enter marks of the exam he's linked/assigned to'
-   Realistic urls, eg, when the teacher heads over to Exams, he should be at /teacher/exam/. When he taps the enter marks of a specific exam, he should be at a relevant page with a relevant url, the same applies to edit

## Constraints

-   Do not hallucinate
-   Take advanced UI design decisions, actually, visit knowledge.md to pick up some ideas

**NB:** Actually clean up the teacher's dashboard, make sure all links work well, because most of pages and files are already in the project, just because they were disorganized by someone, so you'll work as a real refactor engineer
In fact, don't do only what I discored as leakages o on teacher dashboard, you can implement in some other cool designs
And in fact, check well on the teacher's menu, the links commented out, there might be vital ones that shouldn't miss on the dashboard. So check all those
