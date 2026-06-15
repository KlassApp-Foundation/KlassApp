# KlassApp

#### Version 1.0

## How to Install

1. Pull the Repo from the GitLab
2. Run "composer install"
3. Run "dusk install"
4. Duplicate .env file as .env.dusk.local
5. Add your mysql db details there
6. Run php artisan dusk --filter classname //for single test
7. Run php artisan dusk --filter classname::functionname//for single function
8. Run php artisan dusk //to run all the test

## Things to change

✅ To change board of education to UG based
✅ education for teachers
✅ Mother toungue
✅ LIN
✅ Center number
**school**
✅ Admission
✅ affiliation
✅ city & state cleanup

✅ promotion rule ui
✅ academic term and fees relocation

exam setup **teacher**
✅ student and teacher full names in UI
✅ teacher marks input cleanup (UI)

**Later** Last year performance
✅ promotion bug check
Report card re-design

<!-- **school pay  -->

## Teacher

**To remove**
✅ Reception
✅ Leave
✅ Class wall
**TO Fix**
- attendance bug

-   Teachers' images/avatars

## Next
- Redirecting user after section creation
- Validating (payment reference, payment method)
-   Holidays
-   Librarian cleanup
-   Calendar
