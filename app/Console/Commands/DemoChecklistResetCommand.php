<?php

namespace App\Console\Commands;

use App\Enums\ToshiMode;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use Database\Seeders\result\ExamTypes;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Returns the fictional Datamellon proof-of-concept school to
 * "not yet onboarded". Refuses any school that is not flagged demo or test.
 * The admin password is read from the environment and is never printed.
 */
class DemoChecklistResetCommand extends Command
{
    public const SLUG = 'northwind-harbour';

    public const SCHOOL_NAME = 'Datamellon X AWS POC Demo';

    public const ADMIN_EMAIL = 'northwind.admin@example.test';

    public const ADMIN_FIRST_NAME = 'Datamellon';

    protected $signature = 'demo:checklist-reset';

    protected $description = 'Reset the fictional Datamellon demo school to not yet onboarded';

    public function handle(): int
    {
        $password = (string) env('DEMO_CHECKLIST_ADMIN_PASSWORD', '');
        if (strlen($password) < 8) {
            $this->error('Set DEMO_CHECKLIST_ADMIN_PASSWORD in the environment (at least 8 characters). It is not printed.');

            return self::FAILURE;
        }

        $school = School::query()->where('slug', self::SLUG)->first();
        if ($school && ! $school->is_demo && ! $school->is_test) {
            $this->error('Refusing to reset a school that is not a demo or test school.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($school, $password): void {
            $school = $school ?? $this->makeSchool();
            if (! $school->is_demo || ! $school->is_test) {
                $school->forceFill([
                    'is_demo' => true,
                    'is_test' => true,
                    'school_category' => null,
                    'registration_country' => 'Kenya',
                ])->save();
            }
            $school->forceFill([
                'name' => self::SCHOOL_NAME,
                'email' => 'office@datamellon.example',
                'toshi_mode' => ToshiMode::Preview,
                'toshi_enabled' => 1,
                'registration_country' => 'Kenya',
                'curriculum' => 'cbc',
                'onboarding_finished_at' => null,
                'onboarding_skipped_steps' => null,
                'motto' => null,
            ])->save();
            $admin = $this->admin($school, $password);
            $this->wipeImported($school, (int) $admin->id);
        });

        (new ExamTypes)->run();

        $this->info(self::SCHOOL_NAME.' is ready and not yet onboarded.');
        $this->line('Admin: '.self::ADMIN_EMAIL);
        $this->line('Password: read from DEMO_CHECKLIST_ADMIN_PASSWORD (not printed).');
        $this->line('Open http://localhost:8000');

        return self::SUCCESS;
    }

    private function makeSchool(): School
    {
        $school = new School;
        $school->forceFill([
            'name' => self::SCHOOL_NAME,
            'email' => 'office@datamellon.example',
            'phone' => '+254712000099',
            'slug' => self::SLUG,
            'status' => 1,
            'registration_country' => 'Kenya',
            'curriculum' => 'cbc',
            'school_category' => null,
            'toshi_mode' => ToshiMode::Preview,
            'toshi_enabled' => 1,
            'is_demo' => true,
            'is_test' => true,
        ]);
        $school->save();
        $school->refresh();
        if ($school->slug !== self::SLUG) {
            $school->forceFill(['slug' => self::SLUG])->save();
        }

        return $school->fresh();
    }

    private function admin(School $school, string $password): User
    {
        $admin = User::withTrashed()
            ->where('email', self::ADMIN_EMAIL)
            ->first();

        $attributes = [
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => self::ADMIN_FIRST_NAME.' Admin',
            'email' => self::ADMIN_EMAIL,
            'password' => Hash::make($password),
            'status' => 'active',
            'email_verified' => 1,
            'is_reset' => 0,
            'mobile_no' => null,
        ];

        if ($admin) {
            if ($admin->trashed()) {
                $admin->restore();
            }
            $admin->forceFill($attributes)->save();
        } else {
            $admin = User::query()->create($attributes);
        }

        $profile = Userprofile::query()->firstOrNew(['user_id' => $admin->id]);
        $profile->forceFill([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'firstname' => self::ADMIN_FIRST_NAME,
            'lastname' => 'Admin',
            'profession' => $profile->profession ?: 'admin',
            'status' => 'active',
        ])->save();

        return $admin;
    }

    private function wipeImported(School $school, int $adminId): void
    {
        $schoolId = (int) $school->id;
        $userIds = User::withTrashed()
            ->where('school_id', $schoolId)
            ->where('id', '!=', $adminId)
            ->pluck('id');

        foreach (['marks', 'exam_marks_submissions', 'marks_releases', 'exams', 'exam_sets'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'school_id')) {
                DB::table($table)->where('school_id', $schoolId)->delete();
            }
        }
        DB::table('onboarding_plans')->where('school_id', $schoolId)->delete();
        DB::table('user_preferences')->where('key', 'checklist.school_agreement.'.$schoolId)->delete();
        DB::table('school_details')->where('school_id', $schoolId)->where('meta_key', 'moto')->delete();
        DB::table('teacher_invites')->where('school_id', $schoolId)->delete();
        DB::table('class_teacher_links')->where('school_id', $schoolId)->delete();
        DB::table('student_parent_links')->where('school_id', $schoolId)->delete();
        DB::table('student_academics')->where('school_id', $schoolId)->delete();
        DB::table('subjects')->where('school_id', $schoolId)->delete();
        DB::table('standards_link')->where('school_id', $schoolId)->delete();
        DB::table('sections')->where('school_id', $schoolId)->delete();
        DB::table('standards')->where('school_id', $schoolId)->delete();
        DB::table('academic_terms')->where('school_id', $schoolId)->delete();
        DB::table('academic_years')->where('school_id', $schoolId)->delete();
        if ($userIds->isNotEmpty()) {
            DB::table('userprofiles')->whereIn('user_id', $userIds)->delete();
        }
        DB::table('student_id_sequences')->where('school_id', $schoolId)->delete();

        User::withTrashed()
            ->where('school_id', $schoolId)
            ->where('id', '!=', $adminId)
            ->get()
            ->each
            ->forceDelete();
    }
}
