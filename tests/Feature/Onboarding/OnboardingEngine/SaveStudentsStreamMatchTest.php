<?php

namespace Tests\Feature\Onboarding\OnboardingEngine;

use App\Livewire\ManualOnboardingWizard;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\OnboardingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class SaveStudentsStreamMatchTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private StandardLink $linkA;

    private StandardLink $linkB;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => 'Stream School '.Str::random(6),
            'email' => Str::random(8).'@test.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'slug' => Str::random(10),
            'status' => 1,
            'toshi_enabled' => 0,
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 1,
        ]);

        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);

        // Create A first so legacy class-only "P1" first-match would pick A.
        $sectionA = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P1 A',
            'status' => 1,
        ]);
        $sectionB = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P1 B',
            'status' => 1,
        ]);

        $this->linkA = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $sectionA->id,
            'status' => 1,
        ]);
        $this->linkB = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $sectionB->id,
            'status' => 1,
        ]);

        \DB::table('usergroups')->insertOrIgnore([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);
        \DB::table('student_id_sequences')->insertOrIgnore([
            'school_id' => $this->school->id,
            'next_seq' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'stream.admin.'.Str::random(4),
            'email' => 'admin.'.Str::random(6).'@test.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        Userprofile::create([
            'user_id' => $this->admin->id,
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'firstname' => 'Stream',
            'lastname' => 'Admin',
            'status' => 'active',
        ]);
    }

    public function test_compose_class_and_stream_avoids_double_append(): void
    {
        $this->assertSame('P1 A', OnboardingEngine::composeClassAndStream('P1', 'A'));
        $this->assertSame('P1 A', OnboardingEngine::composeClassAndStream('P1 A', 'A'));
        $this->assertSame('P1', OnboardingEngine::composeClassAndStream('P1', ''));
    }

    public function test_class_plus_stream_lands_on_specific_section_not_first_match(): void
    {
        $engine = app(OnboardingEngine::class);

        $result = $engine->saveStudents($this->school, $this->year, [
            ['name' => 'Alice A', 'class' => 'P1', 'stream' => 'A'],
            ['name' => 'Bob B', 'class' => 'P1', 'stream' => 'B'],
        ]);

        $this->assertCount(2, $result['created']);

        $alice = User::where('school_id', $this->school->id)->where('name', 'Alice A')->first();
        $bob = User::where('school_id', $this->school->id)->where('name', 'Bob B')->first();
        $this->assertNotNull($alice);
        $this->assertNotNull($bob);

        $this->assertSame($this->linkA->id, StudentAcademic::where('user_id', $alice->id)->value('standardLink_id'));
        $this->assertSame($this->linkB->id, StudentAcademic::where('user_id', $bob->id)->value('standardLink_id'));
    }

    public function test_full_section_name_in_class_column_still_resolves(): void
    {
        $engine = app(OnboardingEngine::class);

        $result = $engine->saveStudents($this->school, $this->year, [
            ['name' => 'Carol A', 'class' => 'P1 A'],
            ['name' => 'Dave B', 'class' => 'P1 B'],
        ]);

        $carol = User::where('name', 'Carol A')->first();
        $dave = User::where('name', 'Dave B')->first();

        $this->assertSame($this->linkA->id, StudentAcademic::where('user_id', $carol->id)->value('standardLink_id'));
        $this->assertSame($this->linkB->id, StudentAcademic::where('user_id', $dave->id)->value('standardLink_id'));
        $this->assertSame('P1 A', $result['created'][0]['class']);
        $this->assertSame('P1 B', $result['created'][1]['class']);
    }

    public function test_class_only_keeps_legacy_first_match_prefix_behaviour(): void
    {
        $engine = app(OnboardingEngine::class);

        $result = $engine->saveStudents($this->school, $this->year, [
            ['name' => 'Eve Ambiguous', 'class' => 'P1'],
        ]);

        // Without stream, prefix LIKE still returns the first matching section (P1 A).
        $this->assertSame($this->linkA->id, $result['created'][0]['standardLink_id']);
    }

    public function test_unknown_stream_throws_instead_of_wrong_section(): void
    {
        $engine = app(OnboardingEngine::class);

        try {
            $engine->saveStudents($this->school, $this->year, [
                ['name' => 'Lost', 'class' => 'P1', 'stream' => 'C'],
            ]);
            $this->fail('Expected ValidationException for unmatched stream');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('stream C', $e->getMessage());
        }

        $this->assertSame(0, User::where('school_id', $this->school->id)->where('usergroup_id', 6)->count());
    }

    public function test_wizard_maps_stream_into_save_students_drafts(): void
    {
        $this->actingAs($this->admin);

        // Mirror ManualOnboardingWizard::saveStudents draft mapping (private).
        $component = Livewire::test(ManualOnboardingWizard::class)
            ->set('studentName', 'Wizard Stream Kid')
            ->set('studentClass', 'P1')
            ->set('studentStream', 'B')
            ->call('addStudentDraft')
            ->assertSet('studentDrafts.0.name', 'Wizard Stream Kid')
            ->assertSet('studentDrafts.0.class', 'P1')
            ->assertSet('studentDrafts.0.stream', 'B');

        $drafts = array_map(function ($draft) {
            return [
                'name' => trim((string) ($draft['name'] ?? '')),
                'class' => trim((string) ($draft['class'] ?? '')),
                'stream' => trim((string) ($draft['stream'] ?? '')),
                'school_student_id' => trim((string) ($draft['school_student_id'] ?? '')),
                'board_registration_number' => trim((string) ($draft['board_registration_number'] ?? '')),
            ];
        }, $component->get('studentDrafts'));

        $this->assertSame('B', $drafts[0]['stream']);

        $result = app(OnboardingEngine::class)->saveStudents($this->school, $this->year, $drafts);
        $this->assertSame($this->linkB->id, $result['created'][0]['standardLink_id']);
    }
}
