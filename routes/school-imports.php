<?php

/**
 * Parent import routes. routes/admin.php is owned by another open pull request,
 * so this file is registered beside it with the same admin middleware.
 */

Route::get('/import/parents', 'ParentImportController@index');
Route::post('/importParents', 'ParentImportController@import');
