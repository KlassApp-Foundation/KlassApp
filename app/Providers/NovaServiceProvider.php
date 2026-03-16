<?php

namespace App\Providers;

use Laravel\Nova\NovaApplicationServiceProvider;
use Gegosoft\Subscriptioninfo\Subscriptioninfo;
use Illuminate\Support\Facades\Gate;
use App\Nova\Metrics\ActiveMembers;
use App\Nova\Metrics\ActiveSchool;
use App\Nova\Metrics\GenderStatus;
use App\Nova\Metrics\TotalSchool;
use App\Nova\Metrics\PaidSchool;
use App\Nova\Metrics\NewStudents;
use Laravel\Nova\Cards\Help;
use Laravel\Nova\Nova;

class NovaServiceProvider extends NovaApplicationServiceProvider
{
    public static $model = 'App\\Models\\User';
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }

    /**
     * Register the Nova routes.
     *
     * @return void
     */
    protected function routes()
    {
        Nova::routes()
                ->withAuthenticationRoutes()
                ->withPasswordResetRoutes()
                ->register();
    }

    /**
     * Register the Nova gate.
     *
     * This gate determines who can access Nova in non-local environments.
     *
     * @return void
     */
    protected function gate()
    {
        Gate::define('viewNova', function ($user) {
            return in_array($user->email, [
                'siteadmin@gegok12.com'
            ]);
        });
    }

    /**
     * Get the cards that should be displayed on the default Nova dashboard.
     *
     * @return array
     */
    protected function cards()
    {
        return [
            new Subscriptioninfo,
            new NewStudents,
            new ActiveMembers,
            new GenderStatus,
            new TotalSchool,
            new ActiveSchool,
            new PaidSchool,
        ];
    }

    /**
     * Get the extra dashboards that should be displayed on the Nova dashboard.
     *
     * @return array
     */
    protected function dashboards()
    {
        return [];
    }

    /**
     * Get the tools that should be listed in the Nova sidebar.
     *
     * @return array
     */
    public function tools()
    {
        return [];
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
