<?php

// School Subadmin routes — reuse existing admin controllers
// The dashboard is the only unique route; all feature routes use /admin/*.
Route::get('/dashboard', 'SubadminController@index')->name('subadmin.dashboard');
