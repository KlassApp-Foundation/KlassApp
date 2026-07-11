<?php

// Alumni Routes
Route::get('/dashboard', 'AlumniController@dashboard')->name('alumni.dashboard');
Route::get('/marks', 'AlumniController@marks')->name('alumni.marks');
Route::get('/directory', 'AlumniController@directory')->name('alumni.directory');
Route::get('/report-card/download', 'AlumniController@downloadReportCard')->name('alumni.report-card.download');
