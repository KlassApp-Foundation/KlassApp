<?php

namespace Tests\Feature\Onboarding;

use App\Livewire\AgentToshi;
use App\Mail\TeacherInviteMail;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\ClassStructureService;
use App\Services\OnboardingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Toshi parity with wizard Structure & Class Teachers (#502):
 * optional additive streams (base kept) + optional CT invite; never blocks;
 * student form defaults stream when the class is split.
 */
class ToshiStructureStreamsParityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    private AcademicYear $year;

    private Section $base;

    private StandardLink $baseLink;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Toshi Structure School',
            'email' => 'toshi-structure@test.sch.ug',
            'phone' => '0700000091',
            'slug' => 'toshi-structure-school',
            'status' => 1,
            'curriculum' => 'uneb',
            'school_category' => 'primary',
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Structure Admin',
            'email' => 'admin@toshi-structure.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Structure',
            'lastname' => 'Admin',
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'status' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);
        Cache::forget('academic_year_for_school_'.$this->school->id);

        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 2,
            'status' => '1',
        ]);

        $this->base = Section::create([
            'school_id' => $this->school->id,
            'name' => 'Primary One',
            'status' => '1',
        ]);

        $this->baseLink = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $this->base->id,
            'status' => '1',
        ]);
    }

    /**
     * Mount lands on the next incomplete checklist step (often student_size).
     * Pin Standards and clear actionStep so send() hits handleStandards.
     */
    private function onStandards(object $component): object
    {
        $idx = array_search('standards', $component->get('steps'), true);
        $this->assertNotFalse($idx);

        return $component
            ->set('mode', 'complete')
            ->set('schoolId', $this->school->id)
            ->set('scope', 'school')
            ->set('actionStep', null)
            ->set('actionSubstep', 0)
            ->set('awaitingConfirm', false)
            ->set('step', $idx)
            ->set('substep', 0);
    }

    public function test_structure_checkpoint_lists_classes_and_done_advances(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(AgentToshi::class);
        $this->onStandards($component)
            ->set('input', 'help')
            ->call('send');

        $messages = collect($component->get('messages'))->pluck('text')->implode("\n");
        $this->assertStringContainsString('Primary One', $messages);
        $this->assertStringContainsString('done', strtolower($messages));

        $component->set('input', 'done')->call('send');

        $this->assertSame(
            'subjects',
            $component->get('steps')[$component->get('step')] ?? null
        );
    }

    public function test_stream_command_keeps_base_and_adds_name_encoded_section(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(AgentToshi::class);
        $this->onStandards($component)
            ->set('input', 'stream Primary One: East')
            ->call('send');

        $this->assertTrue(
            Section::query()
                ->where('school_id', $this->school->id)
                ->where('name', 'Primary One East')
                ->exists()
        );
        $this->assertTrue(
            Section::query()->whereKey($this->base->id)->exists(),
            'Base class must remain after Toshi addStream'
        );
    }

    public function test_ct_command_assigns_class_teacher(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(AgentToshi::class);
        $this->onStandards($component)
            ->set('input', 'ct Primary One: Grace CT, ct-grace@toshi-structure.sch.ug')
            ->call('send');

        $link = $this->baseLink->fresh();
        $this->assertNotNull($link?->class_teacher_id);
        $teacher = User::find($link->class_teacher_id);
        $this->assertSame('ct-grace@toshi-structure.sch.ug', $teacher->email);
        $this->assertSame(5, (int) $teacher->usergroup_id);
        Mail::assertQueued(TeacherInviteMail::class);
    }

    public function test_after_academic_year_lands_on_structure_checkpoint(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(AgentToshi::class);

        $ayIdx = array_search('academic_year', $component->get('steps'), true);
        $this->assertNotFalse($ayIdx);

        $component
            ->set('mode', 'complete')
            ->set('schoolId', $this->school->id)
            ->set('scope', 'school')
            ->set('actionStep', null)
            ->set('actionSubstep', 0)
            ->set('awaitingConfirm', false)
            ->set('step', $ayIdx)
            ->set('substep', 0)
            ->set('input', 'go')
            ->call('send')
            ->set('input', 'yes')
            ->call('send');

        $this->assertSame(
            'standards',
            $component->get('steps')[$component->get('step')] ?? null
        );
        $messages = collect($component->get('messages'))->pluck('text')->implode("\n");
        $this->assertStringContainsString('Primary One', $messages);
    }

    public function test_student_form_defaults_stream_when_class_has_streams(): void
    {
        $this->actingAs($this->admin);

        app(ClassStructureService::class)->addStream(
            $this->school,
            $this->year,
            $this->base,
            'West'
        );

        $component = Livewire::test(AgentToshi::class);

        $studentsIdx = array_search('students', $component->get('steps'), true);
        $this->assertNotFalse($studentsIdx);

        $component
            ->set('mode', 'complete')
            ->set('schoolId', $this->school->id)
            ->set('scope', 'school')
            ->set('actionStep', null)
            ->set('step', $studentsIdx)
            ->call('showStudentFormFn')
            ->set('studentFormClass', 'Primary One')
            ->assertSet('studentFormStream', 'West');

        $streams = $component->instance()->streamsForStudentFormClass();
        $this->assertContains('West', $streams);
    }

    public function test_save_standards_keeps_base_when_streams_provided(): void
    {
        $school = School::create([
            'name' => 'Engine Stream Base School',
            'email' => 'engine-stream-base@test.sch.ug',
            'phone' => '0700000092',
            'slug' => 'engine-stream-base',
            'status' => 1,
            'curriculum' => 'uneb',
        ]);
        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'status' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        app(OnboardingEngine::class)->saveStandards($school, $year, [
            ['name' => 'P1', 'streams' => ['A', 'B']],
        ]);

        $names = Section::where('school_id', $school->id)->pluck('name')->sort()->values()->all();
        $this->assertSame(['P1', 'P1 A', 'P1 B'], $names);
    }
}
