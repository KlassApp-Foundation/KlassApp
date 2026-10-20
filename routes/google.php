<?php

use App\Http\Controllers\Admin\Setting\GoogleConnectController;
use App\Http\Controllers\Admin\Setting\GoogleSheetsController;
use App\Http\Controllers\Google\GoogleMineController;
use Illuminate\Support\Facades\Route;

/*
 * Each person's own Google account. The callback path stays
 * /admin/settings/google/callback so GOOGLE_CONNECTOR_REDIRECT_URI does not change.
 * These routes are not fullschooladmin: a teacher, parent, or student connects
 * only their own row. The handler rejects a platform admin.
 */
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/settings/connected-tools', [GoogleMineController::class, 'index'])->name('google.mine');
    Route::get('/admin/settings/google/connect/{connector}', [GoogleConnectController::class, 'connect'])->name('admin.google.connect');
    Route::get('/admin/settings/google/callback', [GoogleConnectController::class, 'callback'])->name('admin.google.callback');
    Route::post('/admin/settings/google/{connector}/disconnect', [GoogleConnectController::class, 'disconnect'])->name('admin.google.disconnect');
    Route::post('/admin/settings/google/sheets/export', [GoogleSheetsController::class, 'export'])->name('admin.google.sheets.export');
    Route::post('/admin/settings/google/sheets/import', [GoogleSheetsController::class, 'import'])->name('admin.google.sheets.import');
});
