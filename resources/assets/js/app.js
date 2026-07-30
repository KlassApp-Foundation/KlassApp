/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import './bootstrap';

// FullCalendar v5 + Vite: core vdom must evaluate before any plugin (daygrid/etc).
import '@fullcalendar/core/vdom';

import Vue from './vue-compat';
import { bus } from './event-bus';
export { bus };
import datetimepicker from 'vuejs-datetimepicker';
import Multiselect from 'vue-multiselect';
import 'vue-multiselect/dist/vue-multiselect.css';
import VueFlashMessage from 'vue-flash-message';
import 'vue-flash-message/dist/vue-flash-message.min.css';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import Paginate from 'vuejs-paginate';

import ExampleComponent from './components/ExampleComponent.vue';
import DemoTab from './components/demo/Tab.vue';
import AdmissionList from './components/admission/List.vue';
import EditAdmissionForm from './components/admission/Edit.vue';
import AddAdmission from './components/admission/AdmissionTab.vue';
import SelectStandard from './components/admission/SelectStandard.vue';
import StudentDetail from './components/admission/StudentDetail.vue';
import AcademicDetail from './components/admission/AcademicDetail.vue';
import ParentDetail from './components/admission/ParentDetail.vue';
import PersonalDetail from './components/admission/PersonalDetail.vue';
import PageList from './components/classwall/page/List.vue';
import CreatePage from './components/classwall/page/Create.vue';
import EditPage from './components/classwall/page/Edit.vue';
import ShowPage from './components/classwall/page/Show.vue';
import PageTab from './components/classwall/page/tabs/pageTab.vue';
import PostList from './components/classwall/post/List.vue';
import CreatePost from './components/classwall/post/Create.vue';
import EditPost from './components/classwall/post/Edit.vue';
import ShowPost from './components/classwall/post/Show.vue';
import CommentList from './components/classwall/post/Comments.vue';
import Emoji from './components/classwall/post/Emoji.vue';
import NotificationList from './components/notification/List.vue';
import Notification from './components/notification/Show.vue';
import CreateSchooldetail from './components/schooldetail/Create.vue';
import EditSchooldetail from './components/schooldetail/Edit.vue';
import MemberList from './components/student/List.vue';
import ProfileTab from './components/student/profile/ProfileTab.vue';
import SearchFilter from './components/student/Filter.vue';
import CreateMember from './components/student/Create.vue';
import EditMember from './components/student/Edit.vue';
import CreateMedicalHistory from './components/student/CreateMedicalHistory.vue';
import ChangePasswordStudent from './components/student/ChangePassword.vue';
import CreateBulletin from './components/bulletin/Create.vue';
import ParentList from './components/parent/List.vue';
import ParentSearchFilter from './components/parent/Filter.vue';
import CreateParent from './components/parent/Create.vue';
import EditParent from './components/parent/Edit.vue';
import ProfileTabParent from './components/parent/profile/ProfileTab.vue';
import TeacherList from './components/teacher/List.vue';
import TeacherFilter from './components/teacher/Filter.vue';
import CreateTeacher from './components/teacher/Create.vue';
import ProfileTabTeacher from './components/teacher/profile/ProfileTab.vue';
import EditTeacher from './components/teacher/Edit.vue';
import AddressTab from './components/teacher/Address.vue';
import NotesTab from './components/teacher/Notes.vue';
import AddTabTeacher from './components/teacher/addTab.vue';
import ChangePasswordTeacher from './components/teacher/ChangePassword.vue';
import ChangeAvatarTeacher from './components/teacher/ChangeAvatar.vue';
import StaffList from './components/staff/List.vue';
import StaffFilter from './components/staff/Filter.vue';
import CreatePromotion from './components/promotion/Create.vue';
import CreateAttendance from './components/attendance/Create.vue';
import AttendanceRecords from './components/attendance/Index.vue';
import CreateStaffAttendance from './components/attendance/staff/Create.vue';
import CreateDiscipline from './components/discipline/Create.vue';
import EditDiscipline from './components/discipline/Edit.vue';
import StandardSetup from './components/settings/StandardSetup.vue';
import ClassTab from './components/academic/class/classTab.vue';
import CreateUganda from './components/academic/CreateUganda.vue';
import EditClass from './components/academic/Edit.vue';
import Standardfilter from './components/academic/Filter.vue';
import ListAcademicYear from './components/academicyear/List.vue';
import CreateAcademicYear from './components/academicyear/Create.vue';
import EditAcademicYear from './components/academicyear/Edit.vue';
import AddHoliday from './components/academic/holiday/Create.vue';
import HolidayList from './components/academic/holiday/List.vue';
import AddSubjects from './components/subject/Create.vue';
import EditSubjects from './components/subject/Edit.vue';
import CreateHomework from './components/homework/Create.vue';
import EditHomework from './components/homework/Edit.vue';
import ShowHomework from './components/homework/Show.vue';
import HomeWorkList from './components/homework/List.vue';
import ListTabHomework from './components/homework/approvedhomework/listTab.vue';
import CreateCircular from './components/noticeboard/Create.vue';
import EditCircular from './components/noticeboard/Edit.vue';
import NoticeBoardList from './components/noticeboard/List.vue';
import CreateAssignment from './components/assignment/teacher/Create.vue';
import EditAssignment from './components/assignment/teacher/Edit.vue';
import StudentAssignmentList from './components/assignment/teacher/StudentAssignmentList.vue';
import AssignmentList from './components/assignment/List.vue';
import ListTabAssignment from './components/assignment/approvedassignment/listTab.vue';
import AssignmentListStudent from './components/assignment/student/List.vue';
import AttachmentAssignment from './components/assignment/student/Attachment.vue';
import HomeworkList from './components/homework/student/List.vue';
import AttachmentHomework from './components/homework/student/Attachment.vue';
import LessonPlanList from './components/lessonplan/List.vue';
import ApproveLessonPlan from './components/lessonplan/Approve.vue';
import ListTabLesson from './components/lessonplan/listTab.vue';
import AddTabLesson from './components/lessonplan/addTab.vue';
import LeaveTeacherList from './components/leave/teacher/List.vue';
import CreateLeave from './components/leave/teacher/Create.vue';
import EditLeave from './components/leave/teacher/Edit.vue';
import ApproveLeave from './components/leave/teacher/Approve.vue';
import PendingCount from './components/leave/teacher/PendingCount.vue';
import StudentLeaveTab from './components/leave/student/listTab.vue';
import StudentLeaveList from './components/leave/student/List.vue';
import ApproveStudentLeave from './components/leave/student/Approve.vue';
import AbsenteesStudent from './components/dashboard/StudentAttendance.vue';
import AbsenteesStaff from './components/dashboard/StaffAttendance.vue';
import AddVisitorLog from './components/visitorlog/Create.vue';
import ListVisitorLog from './components/visitorlog/List.vue';
import EditVisitorLog from './components/visitorlog/Edit.vue';
import AddCallLog from './components/calllog/Create.vue';
import ListCallLog from './components/calllog/List.vue';
import EditCallLog from './components/calllog/Edit.vue';
import AddPostalRecord from './components/postalrecord/Create.vue';
import ListPostalRecord from './components/postalrecord/List.vue';
import EditPostalRecord from './components/postalrecord/Edit.vue';
import AddTeacherVisitorLog from './components/teacher/visitorlog/Create.vue';
import ListTeacherVisitorLog from './components/teacher/visitorlog/List.vue';
import EditTeacherVisitorLog from './components/teacher/visitorlog/Edit.vue';
import AddTeacherCallLog from './components/teacher/calllog/Create.vue';
import ListTeacherCallLog from './components/teacher/calllog/List.vue';
import EditTeacherCallLog from './components/teacher/calllog/Edit.vue';
import AddTeacherPostalRecord from './components/teacher/postalrecord/Create.vue';
import ListTeacherPostalRecord from './components/teacher/postalrecord/List.vue';
import EditTeacherPostalRecord from './components/teacher/postalrecord/Edit.vue';
import Birthday from './components/dashboard/Birthday.vue';
import ViewBirthday from './components/dashboard/ViewBirthday.vue';
import BirthdayTeacher from './components/dashboard/BirthdayTeacher.vue';
import ViewBirthdayTeacher from './components/dashboard/ViewBirthdayTeacher.vue';
import WorkAnniversary from './components/dashboard/WorkAnniversary.vue';
import ViewWorkAnniversary from './components/dashboard/ViewWorkAnniversary.vue';
import DashboardTimetableTeacher from './components/dashboard/Timetable.vue';
import CreateEvent from './components/event/Create.vue';
import EditEvent from './components/event/Edit.vue';
import ShowEvent from './components/event/show.vue';
import EventPopup from './components/event/Popup.vue';
import EventTab from './components/event/details/EventTab.vue';
import EditProfile from './components/admin/EditProfile.vue';
import ChangePassword from './components/admin/ChangePassword.vue';
import ChangeAvatar from './components/admin/ChangeAvatar.vue';
import ChangeCredential from './components/admin/ChangeCredential.vue';
import Showimage from './components/event/details/ShowImage.vue';
import Contact from './components/contact.vue';
import Event from './components/dashboard/Event.vue';
import StudentExport from './components/export/Student.vue';
import TeacherExport from './components/export/Teacher.vue';
import StaffExport from './components/export/Staff.vue';
import AddBook from './components/book/Create.vue';
import EditBook from './components/book/Edit.vue';
import EditBookcategory from './components/bookcategory/Edit.vue';
import AddPhoneNumber from './components/telephonedirectory/Create.vue';
import EditPhoneNumber from './components/telephonedirectory/Edit.vue';
import ListPhoneNumber from './components/telephonedirectory/List.vue';
import PayrollTemplate from './components/accountant/payroll/template/List.vue';
import CreateTemplate from './components/accountant/payroll/template/Create.vue';
import EditTemplate from './components/accountant/payroll/template/Edit.vue';
import PayrollSalary from './components/accountant/payroll/salary/List.vue';
import CreateSalary from './components/accountant/payroll/salary/Create.vue';
import EditSalary from './components/accountant/payroll/salary/Edit.vue';
import PayrollList from './components/accountant/payroll/payslip/List.vue';
import CreatePayroll from './components/accountant/payroll/payslip/Create.vue';
import EditPayroll from './components/accountant/payroll/payslip/Edit.vue';
import TransactionList from './components/accountant/payroll/transaction/List.vue';
import CreateTransaction from './components/accountant/payroll/transaction/Create.vue';
import EditTransaction from './components/accountant/payroll/transaction/Edit.vue';
import TeacherPayrollList from './components/payroll/teacher/payslip/List.vue';
import TeacherTransactionList from './components/payroll/teacher/transaction/List.vue';
import PayrollSearchFilter from './components/accountant/PayrollFilter.vue';
import EmergencyMessage from './components/emergency/Create.vue';
import AddBooklending from './components/booklending/Create.vue';
import EditBooklending from './components/booklending/Edit.vue';
import ListLentbook from './components/booklending/List.vue';
import CreateTodo from './components/todolist/Create.vue';
import EditTodo from './components/todolist/Edit.vue';
import ListTodo from './components/todolist/List.vue';
import ListTaskTab from './components/todolist/listTab.vue';
import DashboardTask from './components/dashboard/Task.vue';
import AddStudentTask from './components/studenttask/List.vue';
import AddStudentTaskPopup from './components/studenttask/Create.vue';
import StockSearchFilter from './components/report/StockFilter.vue';
import AddPost from './components/feed/Create.vue';
import ShowFeed from './components/feed/ShowFeed.vue';
import SliderImage from './components/feed/slider.vue';
import Homeslider from './components/Slider.vue';
import NavBar from './components/Navigation.vue';


// Vue 3 migration build (@vue/compat via Vite alias + vue-compat.js).
// keep .default fallback for interop edge cases.
window.Vue = Vue.default || Vue;

// Quarantined vuejs-datetimepicker: silence its deprecations on the shared module export
// without globally suppressing COMPONENT_V_MODEL / OPTIONS_DESTROYED for app code.
const dt = datetimepicker.default || datetimepicker;
dt.compatConfig = Object.assign({}, dt.compatConfig || {}, {
    COMPONENT_V_MODEL: 'suppress-warning',
    OPTIONS_DESTROYED: 'suppress-warning',
});

// Single global multiselect registration (SFCs previously re-registered on every require).
Vue.component('multiselect', Multiselect);

// vue-flash-message once (ChangeCredential + create-leave previously each Vue.use'd at module load).
Vue.use(VueFlashMessage);

Vue.component('paginate', Paginate);

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */
// require('./inventory');
Vue.component('example-component', ExampleComponent);

//demo
Vue.component('demo-tab', DemoTab);

//admission
Vue.component('admission-list', AdmissionList);
Vue.component('edit-admission-form', EditAdmissionForm);
Vue.component('add-admission', AddAdmission);
Vue.component('select-standard', SelectStandard);
Vue.component('student-detail', StudentDetail);
Vue.component('academic-detail', AcademicDetail);
Vue.component('parent-detail', ParentDetail);
Vue.component('personal-detail', PersonalDetail);



// classwall

	//page
	Vue.component('page-list', PageList);
	Vue.component('create-page', CreatePage);
	Vue.component('edit-page', EditPage);
	Vue.component('show-page', ShowPage);
	Vue.component('page-tab', PageTab);

	//post
	Vue.component('post-list', PostList);
	Vue.component('create-post', CreatePost);
	Vue.component('edit-post', EditPost);
	Vue.component('show-post', ShowPost);
	Vue.component('comment-list', CommentList);
	Vue.component('emoji', Emoji);

//notification

Vue.component('notification-list', NotificationList);
Vue.component('notification', Notification);

//School Detail
Vue.component('create-schooldetail', CreateSchooldetail);
Vue.component('edit-schooldetail', EditSchooldetail);

//student
Vue.component('member-list', MemberList);
Vue.component('profile-tab', ProfileTab);
Vue.component('search-filter', SearchFilter);
Vue.component('create-member', CreateMember);
Vue.component('edit-member', EditMember);
Vue.component('create-medical-history', CreateMedicalHistory);

Vue.component('change-password-student', ChangePasswordStudent);

//Bulletin
Vue.component('create-bulletin', CreateBulletin);





//parent
Vue.component('parent-list', ParentList);
Vue.component('parent-search-filter', ParentSearchFilter);
Vue.component('create-parent', CreateParent);
Vue.component('edit-parent', EditParent);
Vue.component('profile-tab-parent', ProfileTabParent);

//teacher
Vue.component('teacher-list', TeacherList);
Vue.component('teacher-filter', TeacherFilter);
Vue.component('create-teacher', CreateTeacher);
Vue.component('profile-tab-teacher', ProfileTabTeacher);
Vue.component('edit-teacher', EditTeacher);
Vue.component('address-tab', AddressTab);
Vue.component('notes-tab', NotesTab);
Vue.component('add-tab-teacher', AddTabTeacher);

Vue.component('change-password-teacher', ChangePasswordTeacher);
Vue.component('change-avatar-teacher', ChangeAvatarTeacher);

//Staff
Vue.component('staff-list', StaffList);
Vue.component('staff-filter', StaffFilter);

//promotion
Vue.component('create-promotion', CreatePromotion);

//attendance
Vue.component('create-attendance', CreateAttendance);
Vue.component('attendance-records', AttendanceRecords);
Vue.component('create-staff-attendance', CreateStaffAttendance);



//discipline
Vue.component('create-discipline', CreateDiscipline);
Vue.component('edit-discipline', EditDiscipline);

//academic
Vue.component('standard-setup', StandardSetup);
Vue.component('class-tab', ClassTab);
// Vue.component('create-class', /*require*/('./components/academic/Create1.vue').default);
Vue.component('create-uganda', CreateUganda);
Vue.component('edit-class', EditClass);
Vue.component('standardfilter', Standardfilter);

//academic year
Vue.component('list-academic-year', ListAcademicYear);
Vue.component('create-academic-year', CreateAcademicYear);
Vue.component('edit-academic-year', EditAcademicYear);

//holiday
Vue.component('add-holiday', AddHoliday);
Vue.component('holiday-list', HolidayList);

//subject
Vue.component('add-subjects', AddSubjects);
Vue.component('edit-subjects', EditSubjects);



//Homework
Vue.component('create-homework', CreateHomework);
Vue.component('edit-homework', EditHomework);
Vue.component('show-homework', ShowHomework);
Vue.component('home-work-list', HomeWorkList);
Vue.component('list-tab-homework', ListTabHomework);

//Notice Board
Vue.component('create-circular', CreateCircular);
Vue.component('edit-circular', EditCircular);
Vue.component('notice-board-list', NoticeBoardList);






//assignment-teacher
Vue.component('create-assignment', CreateAssignment);
Vue.component('edit-assignment', EditAssignment);
Vue.component('student-assignment-list', StudentAssignmentList);

//assignment
Vue.component('assignment-list', AssignmentList);
Vue.component('list-tab-assignment', ListTabAssignment);

//student assignment
Vue.component('assignment-list-student', AssignmentListStudent);
Vue.component('attachment-assignment', AttachmentAssignment);


//student homework
Vue.component('homework-list', HomeworkList);
Vue.component('attachment-homework', AttachmentHomework);

//lesson-plan
Vue.component('lesson-plan-list', LessonPlanList);
Vue.component('approve-lesson-plan', ApproveLessonPlan);
Vue.component('list-tab-lesson', ListTabLesson);
Vue.component('add-tab-lesson', AddTabLesson);


//leave application
Vue.component('leave-teacher-list', LeaveTeacherList);
Vue.component('create-leave', CreateLeave);
Vue.component('edit-leave', EditLeave);
Vue.component('approve-leave', ApproveLeave);
Vue.component('pending-count', PendingCount);

//student leave application
Vue.component('student-leave-tab', StudentLeaveTab);
Vue.component('student-leave-list', StudentLeaveList);
Vue.component('approve-student-leave', ApproveStudentLeave);

//absentees
Vue.component('absentees-student', AbsenteesStudent);
Vue.component('absentees-staff', AbsenteesStaff);

//visitor Log
Vue.component('add-visitor-log', AddVisitorLog);
Vue.component('list-visitor-log', ListVisitorLog);
Vue.component('edit-visitor-log', EditVisitorLog);

//call Log
Vue.component('add-call-log', AddCallLog);
Vue.component('list-call-log', ListCallLog);
Vue.component('edit-call-log', EditCallLog);


//postal Log
Vue.component('add-postal-record', AddPostalRecord);
Vue.component('list-postal-record', ListPostalRecord);
Vue.component('edit-postal-record', EditPostalRecord);

//visitor Log
Vue.component('add-teacher-visitor-log', AddTeacherVisitorLog);
Vue.component('list-teacher-visitor-log', ListTeacherVisitorLog);
Vue.component('edit-teacher-visitor-log', EditTeacherVisitorLog);

//call Log
Vue.component('add-teacher-call-log', AddTeacherCallLog);
Vue.component('list-teacher-call-log', ListTeacherCallLog);
Vue.component('edit-teacher-call-log', EditTeacherCallLog);


//postal Log
Vue.component('add-teacher-postal-record', AddTeacherPostalRecord);
Vue.component('list-teacher-postal-record', ListTeacherPostalRecord);
Vue.component('edit-teacher-postal-record', EditTeacherPostalRecord);




//dashboard
Vue.component('birthday', Birthday);
Vue.component('view-birthday', ViewBirthday);
Vue.component('birthday-teacher', BirthdayTeacher);
Vue.component('view-birthday-teacher', ViewBirthdayTeacher);
Vue.component('work-anniversary', WorkAnniversary);
Vue.component('view-work-anniversary', ViewWorkAnniversary);
Vue.component('dashboard-timetable-teacher', DashboardTimetableTeacher);

//Event
Vue.component('create-event', CreateEvent);
Vue.component('edit-event', EditEvent);
Vue.component('show-event', ShowEvent);
Vue.component('event-popup', EventPopup);
Vue.component('event-tab', EventTab);

//Edit Userprofile
Vue.component('edit-profile', EditProfile);
Vue.component('change-password', ChangePassword);
Vue.component('change-avatar', ChangeAvatar);
Vue.component('change-credential', ChangeCredential);

Vue.component('showimage', Showimage);
//Vue.component('galleryimage', /*require*/('./components/event/details/GalleryImage.vue').default);

//Contact
Vue.component('contact', Contact);

Vue.component('event', Event);

//library


//custom-export
Vue.component('student-export', StudentExport);
Vue.component('teacher-export', TeacherExport);
Vue.component('staff-export', StaffExport);

//books
Vue.component('add-book', AddBook);
Vue.component('edit-book', EditBook);
Vue.component('edit-bookcategory', EditBookcategory);

//telephone directory
Vue.component('add-phone-number', AddPhoneNumber);
Vue.component('edit-phone-number', EditPhoneNumber);
Vue.component('list-phone-number', ListPhoneNumber);



//Payroll
Vue.component('payroll-template', PayrollTemplate);
Vue.component('create-template', CreateTemplate);
Vue.component('edit-template', EditTemplate);
Vue.component('payroll-salary', PayrollSalary);
Vue.component('create-salary', CreateSalary);
Vue.component('edit-salary', EditSalary);
Vue.component('payroll-list', PayrollList);
Vue.component('create-payroll', CreatePayroll);
Vue.component('edit-payroll', EditPayroll);
Vue.component('transaction-list', TransactionList);
Vue.component('create-transaction', CreateTransaction);
Vue.component('edit-transaction', EditTransaction);

Vue.component('teacher-payroll-list', TeacherPayrollList);
Vue.component('teacher-transaction-list', TeacherTransactionList);
Vue.component('payroll-search-filter', PayrollSearchFilter);

//Emergency Message

Vue.component('emergency-message', EmergencyMessage);

//booklending
Vue.component('add-booklending', AddBooklending);
Vue.component('edit-booklending', EditBooklending);

//student library avtivity
Vue.component('list-lentbook', ListLentbook);

//to do list
Vue.component('create-todo', CreateTodo);
Vue.component('edit-todo', EditTodo);
Vue.component('list-todo', ListTodo);

//dashboard to do 
Vue.component('list-task-tab', ListTaskTab);
Vue.component('dashboard-task', DashboardTask);

//student task
Vue.component('add-student-task', AddStudentTask);
Vue.component('add-student-task-popup', AddStudentTaskPopup);


//report
Vue.component('stock-search-filter', StockSearchFilter);


//feed
Vue.component('add-post', AddPost);
Vue.component('show-feed', ShowFeed);
Vue.component('slider-image', SliderImage);


// home slider
Vue.component('homeslider', Homeslider);
Vue.component('nav-bar', NavBar);





// Avoid Vue.use(vue-sweetalert2): its install() calls provide() outside setup under
// compat Vue.use. Avoid Vue.config.globalProperties alone / post-mount app.config.gp —
// under @vue/compat those do not expose this.$swal on instances (singleton gp ≠ mounted
// proxy). Match vue-flash-message: Vue.prototype.$swal (compat maps this onto instances).
window.Swal = Swal;
Vue.prototype.$swal = function (...args) {
    return Swal.fire(...args);
};

const app = new Vue({
    el: '#app'
});

