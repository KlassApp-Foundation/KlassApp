<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuthReadinessCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gego:auth-readiness';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pre-go-live auth readiness checks for registration, login, reset password';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $issues = [];

        $this->line('Running auth readiness checks...');

        $this->checkUsergroups($issues);
        $this->checkPlans($issues);
        $this->checkSettings($issues);
        $this->checkMailConfig($issues);
        $this->checkRegistrationColumns($issues);

        $this->newLine();

        if (count($issues) > 0) {
            $this->error('Auth readiness: FAILED');
            foreach ($issues as $issue) {
                $this->line('- ' . $issue);
            }

            $this->newLine();
            $this->line('Suggested fix path:');
            $this->line('1) php artisan migrate --force');
            $this->line('2) php artisan db:seed --class=UsergroupTableSeeder --force');
            $this->line('3) php artisan db:seed --class=PlansTableSeeder --force');
            $this->line('4) php artisan db:seed --class=SettingsTableSeeder --force');
            $this->line('5) Ensure MAIL_* values are valid in .env then run php artisan optimize:clear');

            return 1;
        }

        $this->info('Auth readiness: PASS');
        return 0;
    }

    private function checkUsergroups(array &$issues)
    {
        if (!Schema::hasTable('usergroups')) {
            $issues[] = 'Table usergroups does not exist.';
            return;
        }

        $hasAny = DB::table('usergroups')->exists();
        if (!$hasAny) {
            $issues[] = 'Table usergroups is empty.';
            return;
        }

        $hasSchoolAdmin = DB::table('usergroups')->where('id', 3)->exists();
        if (!$hasSchoolAdmin) {
            $issues[] = 'usergroups.id=3 (SchoolAdmin) is missing.';
        }
    }

    private function checkPlans(array &$issues)
    {
        if (!Schema::hasTable('plans')) {
            $issues[] = 'Table plans does not exist.';
            return;
        }

        if (!DB::table('plans')->exists()) {
            $issues[] = 'Table plans is empty.';
        }
    }

    private function checkSettings(array &$issues)
    {
        if (!Schema::hasTable('settings')) {
            $issues[] = 'Table settings does not exist.';
            return;
        }

        if (!DB::table('settings')->exists()) {
            $issues[] = 'Table settings is empty.';
            return;
        }

        $hasLoginStatus = DB::table('settings')->where('key', 'login_status')->exists();
        if (!$hasLoginStatus) {
            $issues[] = 'settings key login_status is missing.';
        }

        $hasRegister = DB::table('settings')->where('key', 'register')->exists();
        $hasRegisterStatus = DB::table('settings')->where('key', 'register_status')->exists();

        if (!$hasRegister && !$hasRegisterStatus) {
            $issues[] = 'settings key register/register_status is missing.';
        }
    }

    private function checkMailConfig(array &$issues)
    {
        // Support both modern (mail.default) and legacy (mail.driver) config styles.
        $mailer = (string) Config::get('mail.default', Config::get('mail.driver', ''));

        if ($mailer === '') {
            $issues[] = 'mail.default is empty.';
            return;
        }

        if ($mailer === 'smtp') {
            $host = (string) Config::get('mail.mailers.smtp.host', Config::get('mail.host', ''));
            $port = (string) Config::get('mail.mailers.smtp.port', Config::get('mail.port', ''));
            $from = (string) Config::get('mail.from.address', '');

            if ($host === '') {
                $issues[] = 'MAIL_HOST is empty for smtp mailer.';
            }

            if ($port === '') {
                $issues[] = 'MAIL_PORT is empty for smtp mailer.';
            }

            if ($from === '') {
                $issues[] = 'MAIL_FROM_ADDRESS is empty.';
            }
        }
    }

    private function checkRegistrationColumns(array &$issues)
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'registration_role')) {
            $issues[] = 'users.registration_role column is missing (new registration field migration not applied).';
        }

        if (Schema::hasTable('schools')) {
            if (!Schema::hasColumn('schools', 'registration_country')) {
                $issues[] = 'schools.registration_country column is missing (new registration field migration not applied).';
            }

            if (!Schema::hasColumn('schools', 'student_size')) {
                $issues[] = 'schools.student_size column is missing (new registration field migration not applied).';
            }
        }
    }
}
