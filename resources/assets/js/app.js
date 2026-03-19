
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

window.Vue = require('vue');

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */
// require('./inventory');
Vue.component('example-component', require('./components/ExampleComponent.vue'));

//demo
Vue.component('demo-tab', require('./components/demo/Tab.vue').default);

//admission
Vue.component('admission-list', require('./components/admission/List.vue').default);
Vue.component('edit-admission-form', require('./components/admission/Edit.vue').default);
Vue.component('add-admission', require('./components/admission/AdmissionTab.vue').default);
Vue.component('select-standard', require('./components/admission/SelectStandard.vue').default);
Vue.component('student-detail', require('./components/admission/StudentDetail.vue').default);
Vue.component('academic-detail', require('./components/admission/AcademicDetail.vue').default);
Vue.component('parent-detail', require('./components/admission/ParentDetail.vue').default);
Vue.component('personal-detail', require('./components/admission/PersonalDetail.vue').default);



// classwall

	//page
	Vue.component('page-list', require('./components/classwall/page/List.vue').default);
	Vue.component('create-page', require('./components/classwall/page/Create.vue').default);
	Vue.component('edit-page', require('./components/classwall/page/Edit.vue').default);
	Vue.component('show-page', require('./components/classwall/page/Show.vue').default);
	Vue.component('page-tab', require('./components/classwall/page/tabs/pageTab.vue').default);

	//post
	Vue.component('post-list', require('./components/classwall/post/List.vue').default);
	Vue.component('create-post', require('./components/classwall/post/Create.vue').default);
	Vue.component('edit-post', require('./components/classwall/post/Edit.vue').default);
	Vue.component('show-post', require('./components/classwall/post/Show.vue').default);
	Vue.component('comment-list', require('./components/classwall/post/Comments.vue').default);
	Vue.component('emoji', require('./components/classwall/post/Emoji.vue').default);

//notification

Vue.component('notification-list', require('./components/notification/List.vue').default);
Vue.component('notification', require('./components/notification/Show.vue').default);

//School Detail
Vue.component('create-schooldetail', require('./components/schooldetail/Create.vue').default);
Vue.component('edit-schooldetail', require('./components/schooldetail/Edit.vue').default);

//student
Vue.component('member-list', require('./components/student/List.vue').default);
Vue.component('profile-tab', require('./components/student/profile/ProfileTab.vue').default);
Vue.component('search-filter', require('./components/student/Filter.vue').default);
Vue.component('create-member', require('./components/student/Create.vue').default);
Vue.component('edit-member', require('./components/student/Edit.vue').default);
Vue.component('create-medical-history', require('./components/student/CreateMedicalHistory.vue').default);

Vue.component('change-password-student', require('./components/student/ChangePassword.vue').default);

//Bulletin
Vue.component('create-bulletin', require('./components/bulletin/Create.vue').default);





//parent
Vue.component('parent-list', require('./components/parent/List.vue').default);
Vue.component('parent-search-filter', require('./components/parent/Filter.vue').default);
Vue.component('create-parent', require('./components/parent/Create.vue').default);
Vue.component('edit-parent', require('./components/parent/Edit.vue').default);
Vue.component('profile-tab-parent', require('./components/parent/profile/ProfileTab.vue').default);

//teacher
Vue.component('teacher-list', require('./components/teacher/List.vue').default);
Vue.component('teacher-filter', require('./components/teacher/Filter.vue').default);
Vue.component('create-teacher', require('./components/teacher/Create.vue').default);
Vue.component('profile-tab-teacher', require('./components/teacher/profile/ProfileTab.vue').default);
Vue.component('edit-teacher', require('./components/teacher/Edit.vue').default);
Vue.component('address-tab', require('./components/teacher/Address.vue').default);
Vue.component('notes-tab', require('./components/teacher/Notes.vue').default);
Vue.component('add-tab-teacher', require('./components/teacher/addTab.vue').default);

Vue.component('change-password-teacher', require('./components/teacher/ChangePassword.vue').default);
Vue.component('change-avatar-teacher', require('./components/teacher/ChangeAvatar.vue').default);

//Staff
Vue.component('staff-list', require('./components/staff/List.vue').default);
Vue.component('staff-filter', require('./components/staff/Filter.vue').default);

//promotion
Vue.component('create-promotion', require('./components/promotion/Create.vue').default);

//attendance
Vue.component('create-attendance', require('./components/attendance/Create.vue').default);
Vue.component('create-staff-attendance', require('./components/attendance/staff/Create.vue').default);



//discipline
Vue.component('create-discipline', require('./components/discipline/Create.vue').default);
Vue.component('edit-discipline', require('./components/discipline/Edit.vue').default);

//academic
Vue.component('standard-setup', require('./components/settings/StandardSetup.vue').default);
Vue.component('class-tab', require('./components/academic/class/classTab.vue').default);
Vue.component('create-class', require('./components/academic/Create1.vue').default);
Vue.component('edit-class', require('./components/academic/Edit.vue').default);
Vue.component('standardfilter', require('./components/academic/Filter.vue').default);

//academic year
Vue.component('list-academic-year', require('./components/academicyear/List.vue').default);
Vue.component('create-academic-year', require('./components/academicyear/Create.vue').default);
Vue.component('edit-academic-year', require('./components/academicyear/Edit.vue').default);

//holiday
Vue.component('add-holiday', require('./components/academic/holiday/Create.vue').default);
Vue.component('holiday-list', require('./components/academic/holiday/List.vue').default);

//subject
Vue.component('add-subjects', require('./components/subject/Create.vue').default);
Vue.component('edit-subjects', require('./components/subject/Edit.vue').default);



//Homework
Vue.component('create-homework', require('./components/homework/Create.vue').default);
Vue.component('edit-homework', require('./components/homework/Edit.vue').default);
Vue.component('show-homework', require('./components/homework/Show.vue').default);
Vue.component('home-work-list', require('./components/homework/List.vue').default);
Vue.component('list-tab-homework', require('./components/homework/approvedhomework/listTab.vue').default);

//Notice Board
Vue.component('create-circular', require('./components/noticeboard/Create.vue').default);
Vue.component('edit-circular', require('./components/noticeboard/Edit.vue').default);
Vue.component('notice-board-list', require('./components/noticeboard/List.vue').default);






//assignment-teacher
Vue.component('create-assignment', require('./components/assignment/teacher/Create.vue').default);
Vue.component('edit-assignment', require('./components/assignment/teacher/Edit.vue').default);
Vue.component('student-assignment-list', require('./components/assignment/teacher/StudentAssignmentList.vue').default);

//assignment
Vue.component('assignment-list', require('./components/assignment/List.vue').default);
Vue.component('list-tab-assignment', require('./components/assignment/approvedassignment/listTab.vue').default);

//student assignment
Vue.component('assignment-list-student', require('./components/assignment/student/List.vue').default);
Vue.component('attachment-assignment', require('./components/assignment/student/Attachment.vue').default);


//student homework
Vue.component('homework-list', require('./components/homework/student/List.vue').default);
Vue.component('attachment-homework', require('./components/homework/student/Attachment.vue').default);

//lesson-plan
Vue.component('lesson-plan-list', require('./components/lessonplan/List.vue').default);
Vue.component('approve-lesson-plan', require('./components/lessonplan/Approve.vue').default);
Vue.component('list-tab-lesson', require('./components/lessonplan/listTab.vue').default);
Vue.component('add-tab-lesson', require('./components/lessonplan/addTab.vue').default);


//leave application
Vue.component('leave-teacher-list', require('./components/leave/teacher/List.vue').default);
Vue.component('create-leave', require('./components/leave/teacher/Create.vue').default);
Vue.component('edit-leave', require('./components/leave/teacher/Edit.vue').default);
Vue.component('approve-leave', require('./components/leave/teacher/Approve.vue').default);
Vue.component('pending-count', require('./components/leave/teacher/PendingCount.vue').default);

//student leave application
Vue.component('student-leave-tab', require('./components/leave/student/listTab.vue').default);
Vue.component('student-leave-list', require('./components/leave/student/List.vue').default);
Vue.component('approve-student-leave', require('./components/leave/student/Approve.vue').default);

//absentees
Vue.component('absentees-student', require('./components/dashboard/StudentAttendance.vue').default);
Vue.component('absentees-staff', require('./components/dashboard/StaffAttendance.vue').default);

//visitor Log
Vue.component('add-visitor-log', require('./components/visitorlog/Create.vue').default);
Vue.component('list-visitor-log', require('./components/visitorlog/List.vue').default);
Vue.component('edit-visitor-log', require('./components/visitorlog/Edit.vue').default);

//call Log
Vue.component('add-call-log', require('./components/calllog/Create.vue').default);
Vue.component('list-call-log', require('./components/calllog/List.vue').default);
Vue.component('edit-call-log', require('./components/calllog/Edit.vue').default);


//postal Log
Vue.component('add-postal-record', require('./components/postalrecord/Create.vue').default);
Vue.component('list-postal-record', require('./components/postalrecord/List.vue').default);
Vue.component('edit-postal-record', require('./components/postalrecord/Edit.vue').default);

//visitor Log
Vue.component('add-teacher-visitor-log', require('./components/teacher/visitorlog/Create.vue').default);
Vue.component('list-teacher-visitor-log', require('./components/teacher/visitorlog/List.vue').default);
Vue.component('edit-teacher-visitor-log', require('./components/teacher/visitorlog/Edit.vue').default);

//call Log
Vue.component('add-teacher-call-log', require('./components/teacher/calllog/Create.vue').default);
Vue.component('list-teacher-call-log', require('./components/teacher/calllog/List.vue').default);
Vue.component('edit-teacher-call-log', require('./components/teacher/calllog/Edit.vue').default);


//postal Log
Vue.component('add-teacher-postal-record', require('./components/teacher/postalrecord/Create.vue').default);
Vue.component('list-teacher-postal-record', require('./components/teacher/postalrecord/List.vue').default);
Vue.component('edit-teacher-postal-record', require('./components/teacher/postalrecord/Edit.vue').default);




//dashboard
Vue.component('birthday', require('./components/dashboard/Birthday.vue').default);
Vue.component('view-birthday', require('./components/dashboard/ViewBirthday.vue').default);
Vue.component('birthday-teacher', require('./components/dashboard/BirthdayTeacher.vue').default);
Vue.component('view-birthday-teacher', require('./components/dashboard/ViewBirthdayTeacher.vue').default);
Vue.component('work-anniversary', require('./components/dashboard/WorkAnniversary.vue').default);
Vue.component('view-work-anniversary', require('./components/dashboard/ViewWorkAnniversary.vue').default);
Vue.component('dashboard-timetable-teacher', require('./components/dashboard/Timetable.vue').default);

//Event
Vue.component('create-event', require('./components/event/Create.vue').default);
Vue.component('edit-event', require('./components/event/Edit.vue').default);
Vue.component('show-event', require('./components/event/show.vue').default);
Vue.component('event-popup', require('./components/event/Popup.vue').default);
Vue.component('event-tab', require('./components/event/details/EventTab.vue').default);

//Edit Userprofile
Vue.component('edit-profile', require('./components/admin/EditProfile.vue').default);
Vue.component('change-password', require('./components/admin/ChangePassword.vue').default);
Vue.component('change-avatar', require('./components/admin/ChangeAvatar.vue').default);
Vue.component('change-credential', require('./components/admin/ChangeCredential.vue').default);

Vue.component('showimage', require('./components/event/details/ShowImage.vue').default);
//Vue.component('galleryimage', require('./components/event/details/GalleryImage.vue').default);

//Contact
Vue.component('contact', require('./components/contact.vue').default);

Vue.component('event', require('./components/dashboard/Event.vue').default);

//library


//custom-export
Vue.component('student-export', require('./components/export/Student.vue').default);
Vue.component('teacher-export', require('./components/export/Teacher.vue').default);
Vue.component('staff-export', require('./components/export/Staff.vue').default);

//books
Vue.component('add-book', require('./components/book/Create.vue').default);
Vue.component('edit-book', require('./components/book/Edit.vue').default);
Vue.component('edit-bookcategory', require('./components/bookcategory/Edit.vue').default);

//telephone directory
Vue.component('add-phone-number', require('./components/telephonedirectory/Create.vue').default);
Vue.component('edit-phone-number', require('./components/telephonedirectory/Edit.vue').default);
Vue.component('list-phone-number', require('./components/telephonedirectory/List.vue').default);



//Payroll
Vue.component('payroll-template', require('./components/accountant/payroll/template/List.vue').default);
Vue.component('create-template', require('./components/accountant/payroll/template/Create.vue').default);
Vue.component('edit-template', require('./components/accountant/payroll/template/Edit.vue').default);
Vue.component('payroll-salary', require('./components/accountant/payroll/salary/List.vue').default);
Vue.component('create-salary', require('./components/accountant/payroll/salary/Create.vue').default);
Vue.component('edit-salary', require('./components/accountant/payroll/salary/Edit.vue').default);
Vue.component('payroll-list', require('./components/accountant/payroll/payslip/List.vue').default);
Vue.component('create-payroll', require('./components/accountant/payroll/payslip/Create.vue').default);
Vue.component('edit-payroll', require('./components/accountant/payroll/payslip/Edit.vue').default);
Vue.component('transaction-list', require('./components/accountant/payroll/transaction/List.vue').default);
Vue.component('create-transaction', require('./components/accountant/payroll/transaction/Create.vue').default);
Vue.component('edit-transaction', require('./components/accountant/payroll/transaction/Edit.vue').default);

Vue.component('teacher-payroll-list', require('./components/payroll/teacher/payslip/List.vue').default);
Vue.component('teacher-transaction-list', require('./components/payroll/teacher/transaction/List.vue').default);
Vue.component('payroll-search-filter', require('./components/accountant/PayrollFilter.vue').default);

//Emergency Message

Vue.component('emergency-message', require('./components/emergency/Create.vue').default);

//booklending
Vue.component('add-booklending', require('./components/booklending/Create.vue').default);
Vue.component('edit-booklending', require('./components/booklending/Edit.vue').default);

//student library avtivity
Vue.component('list-lentbook', require('./components/booklending/List.vue').default);

//to do list
Vue.component('create-todo', require('./components/todolist/Create.vue').default);
Vue.component('edit-todo', require('./components/todolist/Edit.vue').default);
Vue.component('list-todo', require('./components/todolist/List.vue').default);

//dashboard to do 
Vue.component('list-task-tab',require('./components/todolist/listTab.vue').default);
Vue.component('dashboard-task',require('./components/dashboard/Task.vue').default);

//student task
Vue.component('add-student-task', require('./components/studenttask/List.vue').default);
Vue.component('add-student-task-popup', require('./components/studenttask/Create.vue').default);


//report
Vue.component('stock-search-filter', require('./components/report/StockFilter.vue').default);


//feed
Vue.component('add-post', require('./components/feed/Create.vue').default);
Vue.component('show-feed', require('./components/feed/ShowFeed.vue').default);
Vue.component('slider-image', require('./components/feed/slider.vue').default);


// home slider
Vue.component('homeslider', require('./components/Slider.vue').default);
Vue.component('nav-bar', require('./components/Navigation.vue').default);





export const bus = new Vue();
import VueSwal from 'vue-swal';
Vue.use(VueSwal);

const app = new Vue({
    el: '#app'
});

import Paginate from 'vuejs-paginate'
Vue.component('paginate', Paginate);