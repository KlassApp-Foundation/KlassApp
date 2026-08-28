<?php

Route::get('/dashboard', 'DashboardController@index')->name('parent.dashboard');
Route::get('/children', 'ChildrenController@index')->name('parent.children');
