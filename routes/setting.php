<?php

// Settings index — redirect to general settings (guarded by fullschooladmin)
Route::get('/settings', function () {
    return view('admin.settings.index');
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

// Integrations — MCP connector registry per school (PR3 Slack wave-1)
Route::get('/settings/integrations', 'Setting\IntegrationsController@index')->name('admin.settings.integrations');
Route::post('/settings/integrations/{type}/disconnect', 'Setting\IntegrationsController@disconnect')->name('admin.settings.integrations.disconnect');

// School Google account. One connection, incremental scopes per connector.
Route::get('/settings/google/connect/{connector}', 'Setting\GoogleConnectController@connect')->name('admin.google.connect');
Route::get('/settings/google/callback', 'Setting\GoogleConnectController@callback')->name('admin.google.callback');
Route::post('/settings/google/{connector}/disconnect', 'Setting\GoogleConnectController@disconnect')->name('admin.google.disconnect');
Route::post('/settings/google/sheets/export', 'Setting\GoogleSheetsController@export')->name('admin.google.sheets.export');
Route::post('/settings/google/sheets/import', 'Setting\GoogleSheetsController@import')->name('admin.google.sheets.import');
Route::post('/settings/google/drive/file', 'Setting\GoogleDriveController@file')->name('admin.google.drive.file');
Route::post('/settings/google/classroom/sync', 'Setting\GoogleClassroomController@sync')->name('admin.google.classroom.sync');
Route::post('/settings/google/calendar/publish', 'Setting\GoogleCalendarController@publish')->name('admin.google.calendar.publish');

// Navigation dropdown + notification list are served by routes/admin.php under
// the schooladmin group. The copies that used to live here registered later and
// shadowed the admin definitions with fullschooladmin — every SchoolSubadmin
// nav load got a 404 for the year list and the notification feed.

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
