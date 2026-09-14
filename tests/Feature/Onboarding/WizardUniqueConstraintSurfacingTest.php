<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\ManualOnboardingWizard;
use App\Models\Country;
use App\Models\Plan;
use App\Models\School;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class WizardUniqueConstraintSurfacingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBePrivilege::class);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Country::create([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'status' => 1,
            'order' => 1,
        ]);

        $this->school = School::create([
            'name' => 'Unique Constraint Primary',
            'email' => 'unique.constraint@test.sch.ug',
            'phone' => '0700000077',
            'slug' => 'unique-constraint-primary',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Unique Admin',
            'email' => 'admin@unique.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Unique',
            'lastname' => 'Admin',
        ]);

        Plan::create([
            'name' => 'Freemium',
            'display_name' => 'Freemium',
            'cycle' => 30,
            'no_of_students' => 0,
            'no_of_users' => 0,
            'amount' => 0,
            'order' => 1,
            'is_active' => 1,
        ]);

        DB::table('student_id_sequences')->insertOrIgnore([
            'school_id' => $this->school->id,
            'next_seq' => 1,
        ]);
    }

    private function advanceToTeachers(object $component): void
    {
        $component
            ->set('schoolName', 'Unique Constraint Primary')
            ->call('next')
            ->set('studentSize', 'Under 100 students')
            ->call('next')
            ->set('countryName', 'Uganda')
            ->call('next')
            ->set('curriculum', 'uneb')
            ->call('next')
            ->set('schoolCategory', 'primary')
            ->call('next')
            ->set('ministryCode', 'EMIS-UNIQUE')
            ->call('next')
            ->call('next') // uneb
            ->call('next') // academic year
            ->call('next') // structure
            ->call('next'); // subjects → teachers
    }

    public function test_teacher_email_collision_shows_specific_error_not_generic(): void
    {
        $other = School::create([
            'name' => 'Other Hold School '.Str::random(4),
            'email' => Str::random(8).'@test.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'slug' => Str::random(10),
            'status' => 1,
            'toshi_enabled' => 0,
        ]);

        User::create([
            'school_id' => $other->id,
            'usergroup_id' => 5,
            'name' => 'Taken Email Teacher',
            'email' => 'taken.teacher@school.ug',
            'password' => bcrypt('x'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToTeachers($component);

        $section = \App\Models\Section::where('school_id', $this->school->id)->orderBy('id')->value('name');
        $subject = \App\Models\Subject::where('school_id', $this->school->id)->orderBy('id')->value('name');

        $component
            ->set('teacherDrafts', [[
                'name' => 'New Teacher',
                'email' => 'taken.teacher@school.ug',
                'phone' => '',
                'classes' => [$section],
                'subjects' => [$subject],
            ]])
            ->call('next');

        $error = (string) $component->get('errorMessage');
        $this->assertStringContainsString('taken.teacher@school.ug', $error);
        $this->assertStringContainsString('already registered', $error);
        $this->assertStringNotContainsString('Could not save this step', $error);
        $this->assertStringNotContainsString('SQLSTATE', $error);
        $this->assertSame(
            'teachers',
            $component->instance()->steps[$component->get('stepIndex')]['key'] ?? null,
            'Must stay on teachers after collision'
        );
    }

    public function test_student_lin_collision_shows_specific_error_not_generic(): void
    {
        // Cross-school LIN collision (global unique) — do not create a same-school
        // student first or OnboardingStepsService marks students complete and skips the step.
        $other = School::create([
            'name' => 'LIN Hold School '.Str::random(4),
            'email' => Str::random(8).'@test.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'slug' => Str::random(10),
            'status' => 1,
            'toshi_enabled' => 0,
        ]);
        $otherYear = \App\Models\AcademicYear::create([
            'school_id' => $other->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 1,
        ]);
        $otherStandard = \App\Models\Standard::create([
            'school_id' => $other->id,
            'name' => 'primary_lower',
            'order' => 1,
            'status' => 1,
        ]);
        $otherSection = \App\Models\Section::create([
            'school_id' => $other->id,
            'name' => 'P.1',
            'status' => 1,
        ]);
        $otherLink = \App\Models\StandardLink::create([
            'school_id' => $other->id,
            'academic_year_id' => $otherYear->id,
            'standard_id' => $otherStandard->id,
            'section_id' => $otherSection->id,
            'status' => 1,
        ]);
        $holder = User::create([
            'school_id' => $other->id,
            'usergroup_id' => 6,
            'name' => 'LIN Holder',
            'email' => 'lin.holder.'.Str::random(6).'@other.sch.ug',
            'password' => bcrypt('x'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        StudentAcademic::create([
            'school_id' => $other->id,
            'academic_year_id' => $otherYear->id,
            'user_id' => $holder->id,
            'standardLink_id' => $otherLink->id,
            'klassapp_student_id' => 'KLS8880001',
            'lin' => 'LIN2501001001',
        ]);

        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToTeachers($component);

        $studentIndex = null;
        foreach ($component->instance()->steps as $i => $step) {
            if (($step['key'] ?? '') === 'students') {
                $studentIndex = $i;
                break;
            }
        }
        $this->assertNotNull($studentIndex);
        $component->call('goToStep', $studentIndex);

        $this->assertSame(
            'students',
            $component->instance()->steps[$component->get('stepIndex')]['key'] ?? null
        );

        $section = \App\Models\Section::where('school_id', $this->school->id)->orderBy('id')->value('name');

        $component
            ->set('studentDrafts', [[
                'name' => 'Grace Mbabazi',
                'class' => $section,
                'stream' => '',
                'email' => '',
                'phone' => '',
                'lin' => 'LIN2501001001',
                'gender' => 'female',
            ]])
            ->call('next');

        $error = (string) $component->get('errorMessage');
        $this->assertStringContainsString('LIN2501001001', $error);
        $this->assertStringContainsString('already registered', $error);
        $this->assertStringNotContainsString('Could not save this step', $error);
        $this->assertStringNotContainsString('SQLSTATE', $error);
        $this->assertSame(
            'students',
            $component->instance()->steps[$component->get('stepIndex')]['key'] ?? null
        );
    }

    public function test_clean_teacher_and_student_save_succeeds(): void
    {
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToTeachers($component);

        $section = \App\Models\Section::where('school_id', $this->school->id)->orderBy('id')->value('name');
        $subject = \App\Models\Subject::where('school_id', $this->school->id)->orderBy('id')->value('name');

        $component
            ->set('teacherDrafts', [[
                'name' => 'Clean Teacher',
                'email' => 'clean.teacher.'.Str::random(6).'@ok.test',
                'phone' => '',
                'classes' => [$section],
                'subjects' => [$subject],
            ]])
            ->call('next');

        $this->assertSame('', (string) $component->get('errorMessage'));
        $this->assertSame(
            'students',
            $component->instance()->steps[$component->get('stepIndex')]['key'] ?? null
        );

        $component
            ->set('studentDrafts', [[
                'name' => 'Clean Student',
                'class' => $section,
                'stream' => '',
                'email' => 'clean.student.'.Str::random(6).'@ok.test',
                'phone' => '',
                'lin' => 'LIN9'.random_int(100000000, 999999999),
                'gender' => 'male',
            ]])
            ->call('next');

        $this->assertSame('', (string) $component->get('errorMessage'));
        $this->assertSame(1, User::where('school_id', $this->school->id)->where('usergroup_id', 5)->count());
        $this->assertSame(1, User::where('school_id', $this->school->id)->where('usergroup_id', 6)->where('name', 'Clean Student')->count());
    }
}
