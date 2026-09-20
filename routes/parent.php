<?php

Route::get('/dashboard', 'DashboardController@index')->name('parent.dashboard');
Route::get('/children', 'ChildrenController@index')->name('parent.children');

Route::prefix('children')->group(function () {
    Route::get('/{student}/fees', 'ChildDataController@fees')->whereNumber('student')->name('parent.children.fees');
    Route::get('/{student}/grades', 'ChildDataController@grades')->whereNumber('student')->name('parent.children.grades');
    Route::get('/{student}/attendance', 'ChildDataController@attendance')->whereNumber('student')->name('parent.children.attendance');
});

// Notifications (same contract as the other roles: the shared Vue components call
// {mode}/notification/{list,showList,read} and {mode}/notifications).
Route::get('/notification/list', 'NotificationController@indexList');
Route::get('/notifications', 'NotificationController@index');
Route::post('/notification/read', 'NotificationController@store');
Route::get('/notification/showList', 'NotificationController@showList');
