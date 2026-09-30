<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\CurrentPlan;
use App\Models\Plan;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\OnboardingEngine;
use App\Services\OnboardingStepsService;
use App\Services\ToshiActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class SchoolSizeAndLimitsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            \App\Http\Middleware\MustBePrivilege::class,
            \App\Http\Middleware\MustBeSchoolAdmin::class,
        ]);

        DB::table('usergroups')->insertOrIgnore([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Size Check School '.Str::random(6),
            'email' => Str::random(8).'@test.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'slug' => Str::random(10),
            'status' => 1,
            'toshi_enabled' => 0,
        ]);

        AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 1,
        ]);

        DB::table('student_id_sequences')->insertOrIgnore([
            'school_id' => $this->school->id,
            'next_seq' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Size Admin',
            'email' => Str::random(8).'@test.sch.ug',
            'password' => bcrypt(Str::random(16)),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'user_id' => $this->admin->id,
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'firstname' => 'Size',
            'lastname' => 'Admin',
        ]);
    }

    private function capStudentsAt(int $limit): void
    {
        $plan = Plan::create([
            'cycle' => 1,
            'name' => 'SizeTest'.Str::random(4),
            'display_name' => 'Size Test '.Str::random(4),
            'no_of_students' => $limit,
            'is_active' => 1,
            'order' => 99,
        ]);

        CurrentPlan::create([
            'school_id' => $this->school->id,
            'plan_id' => $plan->id,
            'status' => 'running',
        ]);
    }

    public function test_size_options_are_the_final_three_buckets(): void
    {
        $this->assertSame([
            'Up to 500',
            'Up to 1,000',
            'More than 1,000',
        ], OnboardingStepsService::STUDENT_SIZE_OPTIONS);
    }

    public function test_engine_accepts_new_buckets_and_refuses_legacy_wording(): void
    {
        $engine = app(OnboardingEngine::class);

        foreach (OnboardingStepsService::STUDENT_SIZE_OPTIONS as $option) {
            $engine->saveStudentSize($this->school->fresh(), $option);
            $this->assertSame($option, $this->school->fresh()->student_size);
        }

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $engine->saveStudentSize($this->school, 'Under 100 students');
    }

    public function test_migration_maps_all_legacy_buckets(): void
    {
        $migration = require database_path('migrations/2026_09_30_150000_map_school_student_size_to_new_buckets.php');
        $map = $migration::MAP;

        $this->assertSame([
            'Under 100 students' => 'Up to 500',
            '100-300 students' => 'Up to 500',
            '300-500 students' => 'Up to 500',
            '500+ students' => 'Up to 1,000',
        ], $map);

        foreach ($map as $old => $new) {
            $school = School::create([
                'name' => 'Legacy '.Str::random(6),
                'email' => Str::random(8).'@test.sch.ug',
                'phone' => '+256700'.random_int(100000, 999999),
                'slug' => Str::random(10),
                'status' => 1,
                'toshi_enabled' => 0,
                'student_size' => $old,
            ]);
            $this->assertSame($old, $school->fresh()->student_size);
        }

        $migration->up();

        foreach ($map as $old => $new) {
            $this->assertSame(
                0,
                School::where('student_size', $old)->count(),
                "{$old} should no longer exist"
            );
        }
        $this->assertSame(3, School::where('student_size', 'Up to 500')->count());
        $this->assertSame(1, School::where('student_size', 'Up to 1,000')->count());
        $this->assertSame(0, School::where('student_size', 'More than 1,000')->count());
    }

    public function test_engine_saves_six_hundred_students_without_blocking_and_notice_fires(): void
    {
        $this->capStudentsAt(500);

        $drafts = [];
        for ($i = 1; $i <= 600; $i++) {
            $drafts[] = ['name' => sprintf('Bulk Learner %03d', $i), 'class' => ''];
        }

        $result = app(OnboardingEngine::class)->saveStudents(
            $this->school->fresh(),
            AcademicYear::where('school_id', $this->school->id)->first(),
            $drafts
        );

        $this->assertCount(600, $result['created'], 'onboarding must never block or truncate');
        $this->assertSame([], $result['skipped']);
        $this->assertSame(600, User::where('school_id', $this->school->id)->where('usergroup_id', 6)->count());

        $overLimit = ToshiActionService::enforcePlanLimit($this->school->id, 'students');
        $this->assertFalse($overLimit['success']);
        $this->assertStringContainsString('upgrade', strtolower($overLimit['message']));
    }

    public function test_csv_import_saves_all_600_over_plan_limit_and_notices(): void
    {
        $this->capStudentsAt(500);

        $year = AcademicYear::where('school_id', $this->school->id)->first();
        $standard = \App\Models\Standard::create([
            'school_id' => $this->school->id, 'name' => 'primary_lower', 'order' => 1, 'status' => 1,
        ]);
        $section = \App\Models\Section::create([
            'school_id' => $this->school->id, 'name' => 'P.1', 'status' => 1,
        ]);
        \App\Models\StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['firstname', 'lastname', 'class'], null, 'A1');
        for ($i = 1; $i <= 600; $i++) {
            $sheet->fromArray([sprintf('Imported %03d', $i), 'Learner', 'P.1'], null, 'A'.($i + 1));
        }
        $tmp = tempnam(sys_get_temp_dir(), 'size600').'.xlsx';
        (new Xlsx($spreadsheet))->save($tmp);

        $file = new UploadedFile($tmp, 'size600.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($this->admin)->post('/admin/importUsers', ['import_file' => $file]);

        $response->assertRedirect();
        $response->assertSessionHas('overlimit', fn ($message) => str_contains(strtolower($message), 'upgrade'));
        $this->assertSame(600, User::where('school_id', $this->school->id)->where('usergroup_id', 6)->count(), 'import must save every row, never block or truncate');
        $response->assertSessionHas('successmessage');

        @unlink($tmp);
    }
}
