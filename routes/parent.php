<?php

Route::get('/dashboard', 'DashboardController@index')->name('parent.dashboard');
Route::get('/children', 'ChildrenController@index')->name('parent.children');

Route::prefix('children')->group(function () {
    Route::get('/{student}/fees', 'ChildDataController@fees')->whereNumber('student')->name('parent.children.fees');
    Route::get('/{student}/grades', 'ChildDataController@grades')->whereNumber('student')->name('parent.children.grades');
    Route::get('/{student}/attendance', 'ChildDataController@attendance')->whereNumber('student')->name('parent.children.attendance');
});
