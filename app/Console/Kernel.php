<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
            \App\Console\Commands\DowngradeExpiredTrials::class,

        //Test
            \App\Console\Commands\Test\CheckEnv::class,
            \App\Console\Commands\Test\CheckTest::class,
            \App\Console\Commands\Test\CheckPushNotification::class,

        //
            \App\Console\Commands\CheckSubscription::class,
            \App\Console\Commands\CheckSubscriptionExpired::class,
            \App\Console\Commands\CheckMail::class,
            \App\Console\Commands\CheckSms::class,
            \App\Console\Commands\CheckBirthday::class,
            \App\Console\Commands\CheckAnniversary::class,
            \App\Console\Commands\CheckBirthdayReminder::class,
            \App\Console\Commands\CheckNotification::class,
            \App\Console\Commands\CheckWebNotification::class,
            \App\Console\Commands\CheckSendMail::class,
            \App\Console\Commands\CheckTask::class,

            \App\Console\Commands\DataSeeder\SeedAttendance::class,

            \App\Console\Commands\AddStandard::class,

        // WhatsApp
            \App\Console\Commands\SendFeeReminders::class,
            \App\Console\Commands\SendWhatsAppPendingNotifications::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();

        $schedule->command('gego:checksubscription')
                 ->daily()
                 ->withoutOverlapping();

        $schedule->command('gego:checksubscriptionexpired')
                 ->daily()
                 ->withoutOverlapping();

        $schedule->command('gego:checkbirthday')
                 ->daily()
                 ->withoutOverlapping();
                 
         $schedule->command('gego:checkbirthdayreminder')
                 ->daily()
                 ->withoutOverlapping();         

        $schedule->command('gego:checkanniversary')
                 ->daily()
                 ->withoutOverlapping(); 

        $schedule->command('gego:checktask')
                 ->everyMinute()
                 ->withoutOverlapping();

        // ─── Backup Pipeline ────────────────────────────────────────────
        // Database + file backup to Hetzner Object Storage (S3)
        $schedule->command('backup:run')
                 ->daily()
                 ->at('02:00')
                 ->withoutOverlapping()
                 ->environments(['production']);

        // Cleanup old backups per retention policy
        $schedule->command('backup:clean')
                 ->daily()
                 ->at('03:00')
                 ->withoutOverlapping()
                 ->environments(['production']);

        // Monitor backup health (age, size)
        $schedule->command('backup:monitor')
                 ->daily()
                 ->at('08:00')
                 ->withoutOverlapping()
                 ->environments(['production']);

        $schedule->command('gego:checknotification')
                 ->hourly()
                 ->withoutOverlapping();

        $schedule->command('gego:checksms')
                 ->hourly()
                 ->withoutOverlapping();

        $schedule->command('gego:checkmail')
                 ->hourly()
                 ->withoutOverlapping();

        $schedule->command('gego:checksendmail')
                 ->everyMinute()
                 ->withoutOverlapping();
                 
        $schedule->command('gego:checkwebnotification')
                 ->everyMinute()
                 ->withoutOverlapping();

        $schedule->command('whatsapp:send-fee-reminders --type=reminder')
                 ->weekly()
                 ->withoutOverlapping();

        $schedule->command('whatsapp:send-fee-reminders --type=overdue')
                 ->daily()
                 ->withoutOverlapping();

        $schedule->command('trial:downgrade-expired')
                 ->daily()
                 ->withoutOverlapping();

        $schedule->command('whatsapp:send-pending --flush-open')
                 ->everyFifteenMinutes()
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/whatsapp-pending.log'));
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
