<?php

namespace Tests\Feature\Onboarding;

use App\Livewire\AgentToshi;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Services\OnboardingStepsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Defect #3 (A-lite): Toshi teacher form collects teacherClasses × teacherSubjects
 * but never expanded them into Teacherlink rows, so OnboardingStepsService kept
 * the Teachers step incomplete (completion = Teacherlink::exists()).
 */
class ToshiFormTeacherlinkCommitTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 1, 'name' => 'superadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Form Teacherlink School',
            'email' => 'form-teacherlink@test.sch.ug',
            'phone' => '0700111222',
            'slug' => 'form-teacherlink-school',
            'status' => 1,
            'curriculum' => 'uneb',
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'School Admin',
            'email' => 'admin@form-teacherlink.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => date('Y'),
            'description' => 'Current Academic Year',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 1,
            'type' => 'Current Academic Year',
        ]);
    }

    /** @test */
    public function done_teachers_expands_form_classes_and_subjects_into_teacher_links(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(AgentToshi::class)
            ->set('mode', 'complete')
            ->set('schoolId', $this->school->id)
            ->set('actionData', [
                'teachers' => ['Agnes Nabirye'],
                'teacherClasses' => ['Agnes Nabirye' => ['S.4']],
                'teacherSubjects' => ['Agnes Nabirye' => ['Mathematics', 'Physics']],
                'teacherPhones' => ['Agnes Nabirye' => '0700123456'],
            ])
            ->call('doneTeachers')
            ->assertSet('teacherList', ['Agnes Nabirye'])
            ->assertSet('teacherLinks', [
                [
                    'teacher' => 'Agnes Nabirye',
                    'class' => 'S.4',
                    'subject' => 'Mathematics',
                    'phone' => '0700123456',
                ],
                [
                    'teacher' => 'Agnes Nabirye',
                    'class' => 'S.4',
                    'subject' => 'Physics',
                    'phone' => '0700123456',
                ],
            ]);
    }

    /** @test */
    public function complete_mode_commit_creates_teacherlinks_from_form_classes_and_subjects(): void
    {
        $this->actingAs($this->admin);

        $this->assertFalse(
            OnboardingStepsService::isStepComplete('teachers', $this->school),
            'Teachers step must start incomplete (no Teacherlink yet)'
        );

        Livewire::test(AgentToshi::class)
            ->set('mode', 'complete')
            ->set('schoolId', $this->school->id)
            ->set('schoolName', $this->school->name)
            ->set('standards', [
                ['name' => 'S.4'],
            ])
            ->set('subjects', [
                'S.4' => ['Mathematics', 'Physics'],
            ])
            ->set('teacherList', ['Agnes Nabirye'])
            ->set('teacherPhones', ['Agnes Nabirye' => '0700123456'])
            ->set('teacherLinks', [])
            ->set('terms', [])
            ->set('studentList', [])
            ->set('actionData', [
                'teachers' => ['Agnes Nabirye'],
                'teacherClasses' => ['Agnes Nabirye' => ['S.4']],
                'teacherSubjects' => ['Agnes Nabirye' => ['Mathematics', 'Physics']],
                'teacherPhones' => ['Agnes Nabirye' => '0700123456'],
            ])
            ->call('commit');

        $this->assertSame(
            2,
            Teacherlink::where('school_id', $this->school->id)->count(),
            'Form teacherClasses × teacherSubjects must become Teacherlink rows'
        );

        $math = Subject::where('school_id', $this->school->id)
            ->whereRaw('LOWER(name) = ?', ['mathematics'])
            ->first();
        $this->assertNotNull($math);

        $this->assertTrue(
            Teacherlink::where('school_id', $this->school->id)
                ->where('subject_id', $math->id)
                ->exists()
        );

        $this->assertTrue(
            OnboardingStepsService::isStepComplete('teachers', $this->school),
            'Teachers step must complete once Teacherlink rows exist'
        );
    }

    /** @test */
    public function form_class_alias_resolves_to_existing_section_name(): void
    {
        $this->actingAs($this->admin);

        // Pre-create Senior Four via engine-style names, then assign with form alias S.4
        Livewire::test(AgentToshi::class)
            ->set('mode', 'complete')
            ->set('schoolId', $this->school->id)
            ->set('schoolName', $this->school->name)
            ->set('standards', [
                ['name' => 'Senior Four'],
            ])
            ->set('subjects', [
                'Senior Four' => ['English'],
            ])
            ->set('teacherList', ['Peter Okello'])
            ->set('teacherLinks', [])
            ->set('terms', [])
            ->set('studentList', [])
            ->set('actionData', [
                'teachers' => ['Peter Okello'],
                'teacherClasses' => ['Peter Okello' => ['S.4']],
                'teacherSubjects' => ['Peter Okello' => ['English']],
            ])
            ->call('commit');

        $this->assertSame(
            1,
            Teacherlink::where('school_id', $this->school->id)->count(),
            'S.4 form class must resolve to Senior Four StandardLink'
        );
        $this->assertTrue(
            OnboardingStepsService::isStepComplete('teachers', $this->school)
        );
    }
}
