# KLASSAPP, THE UGANDA'S SCHOOL MANAGEMENT SYSTEM

## Flow of setting the system up to start working for a school

# ADMIN SETUP
## **1. Signu**

The school admin signs up by providing the School Name, Name Of The Admin(eg HM, Secretary or any other assigned person), Country, Mobile number, Approximate number of students, Email Address, Password

## **2. Level And Board Of Curriculum Setup**

After signup, the user will be taken to school standard setup, here, the school is expected to choose the board of education (preferably UNEB) and the highest level of education provided, eg nursery, primary, o'level etc
NOTE: Chosing primary, nursery is created along, so primary schools don't need to set nursery section as it's auto created for them.
There are some default models setupautomatically anlong Level setup, they include;

-   Subjects for each class
-   Grading System

## **3. Add Teachers**

After, the user adds teachers from users > Staff > Teachers
Here the admin views all teachers in the school, and to add, in top right corner, you'll get three oprions ie(Add, Export, Import)

-   **Add**
    Here, you'll be adding a single user, prefered when you have few teachers to add, like 2
-   **Import**
    This' the most efficient for the first school setup, the user is expected to have teachers in an excel or csv file with the following values

    firstname,lastname,mobile_no,email,gender,date_of_birth,address,district,region,country,joining_date,employee_id, specialization, designation, notes

    To achieve this, on the top right corner, click Import, this takes you to a page where you'll be required to choose the file from your local machine, then the job is accomplished.
    **Note:** You can download the format by clicking Download Sample Format button

## **4. Assigning Teachers to Classes and subjects**

On step 3, head over to Classes, you'll see all teachers assigned to classes and subjects. If you haven't set anything on this module, you'll see nothing.
On the top right corner, there are three buttons, ie, Levels, Classes and Setup A Class.
Head over to Setup A Class, here you'll be required to select the following;

-   Level (eg primary)
-   Class (eg P.6)
-   Class Teacher
    after selecting those, there will appear a form to link subjects to teachers. The subjects of a selected class are auto filled, and in front of each, you'll just select a teacher, then after click the save class details button to save.

## **5. Add Learners**

The same process as adding teachers

To import students from an excel or csv file, here is the format to follow
firstname,lastname,gender,date_of_birth,class,address,region,district,country,mother_tongue,joining_date

## **Settings**

**Academic Years**
Here, you'll see 2 years, the current year and the next year. Make sure in the type column, the current academic year is highlighted as "Current Academic Year", if not, click the "Change Current Academic Year" on the top right corner
OR
Edit years, by clicking the pen icon under actions on a specific year you want to edit
Verdict: when editing, on the type
New Academic Year = Next Year
Current Academic Year = Current year (today)
Old Year = Last Year

**School Details**
You can edit the school details here before you move on, like address, school motto etc. This helps in providing the correct school info on learner's report cards

**Grading System**
This' straight forward, ther's a default grading system, so you can modify it the way you want, or even add a new range by clicking the Add Grading Rule on the top right corner

**Promotion Rules**
If you have added a rule, you'll see it here and if not, you can add it by clicking the + Add Promotion Rule
on the top right corner
Here, you'll select a;

-   class
-   Rule Type (eg points, aggregates or average). After selecting this, there will appear an input to ennter either
    Minimum Average, Minimum Points or Maximum Aggregate, according to the rule type you select
    Forexample, here you can select aggregates as a rule type > then set maximum aggregate to be 20, meaning those who have 20 aggregates and below will have passed and promoted

## **Academic Term**

This' straight forward to, at this page, you'll see academic terms if you have added them, if not, clicke the Add Term on the top right corner
You'll need to provide the following info

-   Name: The name of the term
-   Starts on: The date When the term starts
-   Ends on: The date when the term end
    After selecting these, click the Save Academic Term button and add other terms in the same process
    **Note:** When an academic year ends, you just have to update dates of the academic terms by clicking the edit button of a term to be updated under Actions column

## **Fees Structure**

On this page, you'll see your school's fees structure if added, if not, click + Add Category button on the top right corner, to add a fee
Here, you'll select the following

-   Level: Eg primary
-   Class: Select All if the fee is the same for all classes
-   Academic Term: Select All if the fee is the same for all classes
-   Fee Name: Eg Academics, School System, Food, Security etc
-   Amount: The fee amount for that fee name (Not all total school fees amount)

## **Exams**

Here, you'll see a list of exams and assigned teachers
You can add an exam by clicking the Add Exam button in the top right corner, when there, you'll need to first select a class before others

## **Marks, Report card & marksheet generation and prototion of students**

Here, you'll need to filter by Term, Class and Exam type to see marks
After getting results, you can proceed to download the marks sheet by clicking download marks sheet on the top right corner just after the filter form
You can view student's marks in a report format or download the reports for each student, reports are in pdf format
**Note:** If it's the end of year exam, you'll first download marks sheet and reports before proceeding to promote students
After everything, you can proceed to finalize student promotion just below the marks table


# LIBRARIAN SETUP
This' the easiest dashboard to onboard users(Librarians)
**Enter Book Categories** This' seen on the librarian dashboard side menu. On this page, the librarian can view and add book categories. To add a book category, click "Add Book Category" on the top right corner. Here you'll enter a correct name of the book category and save by hitting the submit button

**Books** The same idea for book categories, the librarian can view and add books. To add a book, you'll Click add book on the top right corner

**Book Lending** The librarian can view book lending records and add also lend a book . To add a new record, you'll Click add on the top right corner, and provide Library Card No, Book Code and Issue Date

**Todo List** The librarian can take notes of todos, to help him keep track of his tasks. To add a new task, click Add on the top right corner and enter the task.

**Holidays** Holidays are automatically updated on this page so that the librarian can know when he's off duty

**Activity Logs** On this page, the librarian views all activities he has done
## Upcoming modules

-   Student and teacher Attendance
-   WhatsApp integration
-   Bulk messaging

https://klassapp.xyz/
✅
