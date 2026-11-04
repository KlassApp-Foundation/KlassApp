<?php

namespace App\Providers;

use App\Http\Middleware\RefuseForPublicDemo;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the account pages every signed-in role shares (routes/account.php): profile and
 * change password. Role-agnostic on purpose: `web` + `auth` only, no role middleware.
 */
class AccountRoutesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware(['web', 'auth', RefuseForPublicDemo::class])
            ->namespace('App\Http\Controllers')
            ->group(base_path('routes/account.php'));
    }
}
