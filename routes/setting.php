<?php

// Settings index — redirect to general settings (guarded by fullschooladmin)
Route::get('/settings', function () {
    return redirect('/admin/settings/generalsettings');
});

// School settings — guarded by fullschooladmin so subadmins can't access
Route::get('/settings/generalsettings', 'Setting\GeneralController@create');
Route::post('/settings/generalsettings', 'Setting\GeneralController@store');
Route::get('/settings/maintenancesettings', 'Setting\MaintenanceController@create');
Route::post('/settings/maintenancesettings', 'Setting\MaintenanceController@store');
Route::get('/settings/seodetailsettings', 'Setting\SeoDetailController@create');
Route::post('/settings/seodetailsettings', 'Setting\SeoDetailController@store');

// Exam type preferences — per-school toggle for report card contribution
Route::get('/settings/exam-types', 'Setting\ExamTypeController@index')->name('admin.settings.exam-types');
Route::post('/settings/exam-types', 'Setting\ExamTypeController@update')->name('admin.settings.exam-types.update');

//navigation drop-down
Route::get('/list/academicyear','NavigationController@list');
Route::post('/academicyear/index','NavigationController@index');

//navigation notification
Route::get('/notification/showList', 'NotificationController@showList');

//dashboard
//Route::get( '/dashboard', 'DashboardController@index' )->name( 'dashboard' );
Route::get( '/dashboard/event', 'DashboardController@event' );

//dashboard-birthday
Route::get( '/dashboard/showBirthday', 'BirthdayController@showBirthday' );
Route::get( '/dashboard/showBirthdayTeacher', 'BirthdayController@showBirthdayTeacher' );
Route::get( '/dashboard/showWorkAnniversary', 'BirthdayController@showWorkAnniversary' );

//absentees - dashboard
Route::get( '/absentees/student', 'AttendanceController@student' );
Route::get( '/absentees/student/list', 'AttendanceController@studentList' );
Route::get( '/absentees/staff', 'StaffAttendanceController@staff' );
Route::get( '/absentees/staff/list', 'StaffAttendanceController@stafflist' );

//fees-dashboard
// Route::get( '/fees/list/{status}', 'FeesController@indexList' ); //error in fees addon

//academics
	//index
	Route::get( '/academics', 'AcademicYearController@index' );
	//add
	Route::get( '/academic/add', 'AcademicYearController@create' );
	Route::post( '/academic/add', 'AcademicYearController@store' );
	//edit
	Route::get( '/academic/edit/{id}', 'AcademicYearController@edit' );
	Route::post( '/academic/update/{id}', 'AcademicYearController@update' );
	Route::post( '/academic/updateStatus/{id}', 'AcademicYearController@updateStatus' );
	//delete
	Route::get( '/academic/delete/{id}', 'AcademicYearController@destroy' );

//standard setup
Route::get( '/standard/create', 'StandardController@create' );
Route::post( '/standard/create', 'StandardController@add' );