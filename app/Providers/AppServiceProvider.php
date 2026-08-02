<?php

namespace App\Providers;

use Illuminate\Support\Facades\Validator;
use App\Observers\TeacherProfileObserver;
use App\Observers\AcademicYearObserver;
use App\Observers\StandardLinkObserver;
use Illuminate\Support\ServiceProvider;

use App\Observers\UserprofileObserver;

use Laravel\Dusk\DuskServiceProvider;
use App\Observers\HomeworkObserver;
use App\Observers\BulletinObserver;

use App\Observers\SchoolObserver;
use App\Observers\EventObserver;
use App\Observers\TaskObserver;
use App\Observers\UserObserver;

use App\Models\TeacherProfile;
use App\Models\AcademicYear;
use App\Models\StandardLink;

use App\Models\Userprofile;

use App\Models\Bulletin;

use App\Models\Homework;
use App\Models\Setting;
use App\Models\Events;
use App\Models\School;

use App\Models\User;
use App\Models\Task;
use Schema;
use Config;
use App;
use Illuminate\Pagination\Paginator;
use Illuminate\Notifications\ChannelManager;
use App\Channels\WhatsAppBackupChannel;
use Laravel\Ai\Tools\Request as AiToolRequest;
use Illuminate\Support\Facades\Notification;
use App\Services\Toshi\AuditingMcpClientManager;
use Laravel\Mcp\Client\ClientManager;
// Importing DuskServiceProvider class

class AppServiceProvider extends ServiceProvider {
    /**
    * Bootstrap any application services.
    *
    * @return void
    */

    public function boot() { 
        // Suppress PHP deprecation warnings in debug mode (PHP 8.4 compatibility)
        if (config('app.debug')) {
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
        }

     Validator::extend('check_logoutdevice_id', function ($attribute, $value, $parameters, $validator) 
        {
            $inputs = $validator->getData();
            $email = $inputs['email'];
            $user = User::where('mobile_no', $email)->where('usergroup_id',7)->first();
            
            if ($user!=null) 
            {
                if ($user->device_id == null) 
                {

                    return true;
                }
                return false;
            }
        });     

        Validator::extend('teacher_logoutdevice_id', function ($attribute, $value, $parameters, $validator) 
        {
            $inputs = $validator->getData();
            $email = $inputs['email'];
            $user = User::where('mobile_no', $email)->where('usergroup_id',5)->first();
            
            if ($user!=null) 
            {
                if ($user->device_id == null) 
                {

                    return true;
                }
                return false;
            }
        });        

        Events::observe( EventObserver::class );
        Bulletin::observe( BulletinObserver::class );
        Homework::observe( HomeworkObserver::class );
        Userprofile::observe( UserprofileObserver::class );
        TeacherProfile::observe( TeacherProfileObserver::class );
        School::observe( SchoolObserver::class );
 

       
        StandardLink::observe(StandardLinkObserver::class);
        User::observe(UserObserver::class);
        Task::observe(TaskObserver::class);
        AcademicYear::observe(AcademicYearObserver::class); //new

        //hide to receive mail
        if ( version_compare( PHP_VERSION, '7.2.0', '>=' ) ) {
            // Ignores notices and reports all other kinds... and warnings
            error_reporting( E_ALL ^ E_NOTICE ^ E_WARNING );
            // error_reporting( E_ALL ^ E_WARNING );
            // Maybe this is enough
        }
        //

        try {
            if ( !\App::runningInConsole() && Schema::hasTable('settings') && count( Schema::getColumnListing( 'settings' ) ) ) {
                $settings = Setting::all();
                foreach ( $settings as $key => $setting ) {
                    Config::set( 'settings.'.$setting->key, $setting->value );
                }
            }
        } catch (\Exception $e) {
            // DB not available — skip settings load
        }

        Paginator::useBootstrap();

        // Add get() helper to the AI Tool Request class for convenience
        AiToolRequest::macro('get', function (string $key, mixed $default = null): mixed {
            return $this->offsetExists($key) ? $this->offsetGet($key) : $default;
        });

        // Register WhatsApp backup notification channel
        Notification::resolved(function (ChannelManager $service) {
            $service->extend('whatsapp', function ($app): WhatsAppBackupChannel {
                return $app->make(WhatsAppBackupChannel::class);
            });
        });
    }

    /**
    * Register any application services.
    *
    * @return void
    */
    public function register() {
        // Named Mcp::client() resolutions always return auditing clients (closes raw callTool bypass).
        $this->app->singleton(ClientManager::class, fn (): ClientManager => new AuditingMcpClientManager);
    }
}