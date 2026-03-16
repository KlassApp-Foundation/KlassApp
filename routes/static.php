<?php

//Usecase Pages
Route::view( '/usecases/school-management-software', 'pages.usecases.school-management-software' );
Route::view( '/usecases/school-administrative-software', 'pages.usecases.school-administrative-software' );
Route::view( '/usecases/student-database-management-system', 'pages.usecases.student-database-management-system' );
Route::view( '/usecases/teacher-parent-communication-app', 'pages.usecases.teacher-parent-communication-app' );
Route::view( '/usecases/school-attendance-management', 'pages.usecases.school-attendance-management' );
Route::view( '/usecases/k12-attendance-management', 'pages.usecases.k12-attendance-management' );
Route::view( '/usecases/attendance-taker-app', 'pages.usecases.attendance-taker-app' );
Route::view( '/usecases/parent-teacher-chat-app', 'pages.usecases.parent-teacher-chat-app' );
Route::view( '/usecases/attendance-management-app-for-teachers', 'pages.usecases.attendance-management-app-for-teachers' );
Route::view( '/usecases/attendance-app-for-teachers', 'pages.usecases.attendance-app-for-teachers' );
Route::view( '/usecases/emergency-notification-system-for-schools', 'pages.usecases.emergency-notification-system-for-schools' );
Route::view( '/usecases/text-management-system-for-schools', 'pages.usecases.text-management-system-for-schools' );
Route::view( '/usecases/school-grade-management-software', 'pages.usecases.school-grade-management-software' );
Route::view( '/usecases/school-inventory-management-software', 'pages.usecases.school-inventory-management-software' );
Route::view( '/usecases/facilities-management-software-for-schools', 'pages.usecases.facilities-management-software-for-schools' );
// modules
Route::view( '/modules/student-database-management', 'pages.modules.student-database-management' );
Route::view( '/modules/classroom-record-management', 'pages.modules.classroom-record-management' );
Route::view( '/modules/event-management', 'pages.modules.event-management' );
Route::view( '/modules/student-journal', 'pages.modules.student-journal' );
Route::view( '/modules/library-management', 'pages.modules.library-management' );
Route::view( '/modules/school-media-library', 'pages.modules.school-media-library' );
Route::view( '/modules/school-notice-board-management', 'pages.modules.school-notice-board-management' );
Route::view( '/modules/student-disciplinary-records', 'pages.modules.student-disciplinary-records' );
//pages
// Section One
Route::view( '/school-admin-app', 'pages.school-admin-app' );
Route::view( '/school-teacher-staff-app', 'pages.school-teacher-staff-app' );
Route::view( '/school-librarian-app', 'pages.school-librarian-app' );
// Section Two
Route::view( '/school-parents-app', 'pages.parents-app' );
Route::view( '/school-students-app', 'pages.students-app' );
Route::view( '/school-teachers-app', 'pages.teachers-app' );
//pricing
Route::get( '/pricing', 'PricingController@create' );
//about
Route::get( '/about', 'AboutController@index' );
//privacypolicy
Route::get( '/privacy-policy', 'AboutController@create' );
//faq
Route::get( '/faq', 'FaqController@index' );
//swotanalysis
Route::get( '/swotanalysis', 'AboutController@show' );
Route::get( '/terms-of-service', 'AboutController@terms' );
