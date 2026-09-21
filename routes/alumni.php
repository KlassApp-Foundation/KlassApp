<?php

// Alumni Routes
Route::get('/dashboard', 'AlumniController@dashboard')->name('alumni.dashboard');
Route::get('/marks', 'AlumniController@marks')->name('alumni.marks');
Route::get('/directory', 'AlumniController@directory')->name('alumni.directory');
Route::get('/report-card/download', 'AlumniController@downloadReportCard')->name('alumni.report-card.download');

// Notifications — same contract as the other roles: the shared Vue components call
// {mode}/notification/{list,showList,read} and {mode}/notifications.
Route::get('/notification/list', 'NotificationController@indexList');
Route::get('/notifications', 'NotificationController@index');
Route::post('/notification/read', 'NotificationController@store');
Route::get('/notification/showList', 'NotificationController@showList');
