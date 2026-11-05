<?php

// Account pages shared by every role. Loaded by App\Providers\AccountRoutesServiceProvider with
// `web` and `auth`; the public demo account is refused by RefuseForPublicDemo. The account menu
// in the shell links here for every role, so the menu is the same for a teacher, a parent, a
// student and an admin.

use App\Http\Controllers\AccountController;
use Illuminate\Support\Facades\Route;

Route::prefix('account')->group(function () {
    Route::get('/profile', [AccountController::class, 'profile'])->name('account.profile');
    Route::get('/password', [AccountController::class, 'password'])->name('account.password');
    Route::post('/password', [AccountController::class, 'updatePassword'])->name('account.password.update');
});
