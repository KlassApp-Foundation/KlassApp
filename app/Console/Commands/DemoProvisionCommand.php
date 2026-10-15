<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Models\Section;
use App\Models\StudentAcademic;
use App\Models\User;
use Database\Seeders\DemoJuniorSchoolSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Creates exactly one is_demo school from a fictional identity.
 *
 * Refuses unless DEMO_PROVISIONING_ENABLED=true. A real run also needs
 * --confirm. --dry-run prints a count and writes nothing. A second run
 * changes nothing. The admin password is never printed.
 *
 * Non-interactive (Laravel Cloud): put the password in the environment as
 * DEMO_PROVISION_ADMIN_PASSWORD rather than on the command line. The school
 * gets the live demo roster (70 students with marks) and its admin is marked
 * is_public_demo, which turns on PublicDemoLockdown for that account.
 */
class DemoProvisionCommand extends Command
{
    protected $signature = 'demo:provision
        {password? : Admin password. Omit to read DEMO_PROVISION_ADMIN_PASSWORD, or to be prompted. Never printed.}
        {--name= : School name}
        {--city= : City}
        {--admin-name= : Admin name}
        {--admin-email= : Admin email}
        {--terms= : Six dates: term starts and ends, comma separated}
        {--confirm : Required for a real run}
        {--dry-run : Print the plan and change nothing}';

    protected $description = 'Provision exactly one demo school with the staging demo dataset';

    public function handle(): int
    {
        if (! filter_var(env('DEMO_PROVISIONING_ENABLED', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->error('Refusing: DEMO_PROVISIONING_ENABLED is not true.');

            return self::FAILURE;
        }

        $name = trim((string) $this->option('name'));
        $city = trim((string) $this->option('city'));
        $adminName = trim((string) $this->option('admin-name'));
        $adminEmail = strtolower(trim((string) $this->option('admin-email')));
        $terms = $this->terms((string) $this->option('terms'));

        if ($name === '' || $city === '' || $adminName === '' || $adminEmail === '' || $terms === null) {
            $this->error('Refusing: name, city, admin name, admin email and term dates are required.');

            return self::FAILURE;
        }

        $existing = School::query()->where('name', $name)->first();
        if ($existing && (int) $existing->is_demo !== 1) {
            $this->error('Refusing: a school with that name exists and is not a demo school.');

            return self::FAILURE;
        }

        $emailOwner = User::query()->where('email', $adminEmail)->first();
        if ($emailOwner) {
            $ownerSchool = School::query()->find($emailOwner->school_id);
            $sameDemo = $existing && $ownerSchool && (int) $ownerSchool->id === (int) $existing->id && (int) $ownerSchool->is_demo === 1;
            if (! $sameDemo) {
                $this->error('Refusing: that admin email belongs to another school.');

                return self::FAILURE;
            }
        }

        if ($existing) {
            if (! $this->option('dry-run') && $this->markPublicDemoAdmin($existing, $adminEmail)) {
                $this->info('demo admin marked as public demo (locked down)');
            }
            $this->info('school already provisioned; nothing changed');
            $this->printCounts($existing);

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info('would create schools=1 (dry run — nothing changed)');

            return self::SUCCESS;
        }

        if (! $this->option('confirm')) {
            $this->error('Refusing: pass --confirm.');

            return self::FAILURE;
        }

        $password = (string) ($this->argument('password') ?: env('DEMO_PROVISION_ADMIN_PASSWORD') ?: ($this->input->isInteractive() ? $this->secret('Admin password') : ''));
        if ($password === '') {
            $this->error('Refusing: an admin password is required.');

            return self::FAILURE;
        }

        $schoolEmail = Str::slug($name) . '@example.com';
        if (School::query()->where('email', $schoolEmail)->exists()) {
            $this->error('Refusing: that school email is already in use.');

            return self::FAILURE;
        }

        DemoJuniorSchoolSeeder::$provision = [
            'name' => $name,
            'city' => $city,
            'admin_name' => $adminName,
            'admin_email' => $adminEmail,
            'school_email' => $schoolEmail,
            'domain' => 'example.com',
            'terms' => $terms,
            'student_plan' => DemoJuniorSchoolSeeder::LIVE_DEMO_PLAN,
        ];

        try {
            $this->call('db:seed', ['--class' => DemoJuniorSchoolSeeder::class, '--force' => true]);
        } finally {
            DemoJuniorSchoolSeeder::$provision = null;
        }

        $school = School::query()->where('name', $name)->where('is_demo', 1)->first();
        if (! $school) {
            $this->error('Provisioning did not create the demo school.');

            return self::FAILURE;
        }

        $admin = User::query()->where('school_id', $school->id)->where('email', $adminEmail)->first();
        if ($admin) {
            $admin->forceFill(['password' => Hash::make($password), 'is_public_demo' => true])->save();
        }

        $this->printCounts($school);

        return self::SUCCESS;
    }

    /** Mark the demo school's admin as the public demo account. True when it changed. */
    private function markPublicDemoAdmin(School $school, string $adminEmail): bool
    {
        if ((int) $school->is_demo !== 1) {
            return false;
        }
        $admin = User::query()->where('school_id', $school->id)->where('email', $adminEmail)->first();
        if (! $admin || $admin->isPublicDemo()) {
            return false;
        }
        $admin->forceFill(['is_public_demo' => true])->save();

        return true;
    }

    private function printCounts(School $school): void
    {
        $learners = StudentAcademic::query()->where('school_id', $school->id)->count();
        $parents = User::query()->where('school_id', $school->id)->where('usergroup_id', 7)->count();
        $classes = Section::query()->where('school_id', $school->id)->count();
        $this->info("schools=1 learners={$learners} parents={$parents} classes={$classes}");
    }

    /** @return array<int, array{name:string,starts_on:string,ends_on:string,status:string}>|null */
    private function terms(string $raw): ?array
    {
        $parts = array_map('trim', explode(',', $raw));
        if (count($parts) !== 6 || in_array('', $parts, true)) {
            return null;
        }

        return [
            ['name' => 'Term I', 'starts_on' => $parts[0], 'ends_on' => $parts[1], 'status' => 'last'],
            ['name' => 'Term II', 'starts_on' => $parts[2], 'ends_on' => $parts[3], 'status' => 'last'],
            ['name' => 'Term III', 'starts_on' => $parts[4], 'ends_on' => $parts[5], 'status' => 'current'],
        ];
    }
}
