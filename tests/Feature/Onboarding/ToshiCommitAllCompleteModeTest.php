<?php

namespace Tests\Feature\Onboarding;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ToshiCommitAllCompleteModeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed usergroups
        DB::table('usergroups')->insert([
            ['id' => 1, 'name' => 'superadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Create a school and admin that already exist (simulating an existing school doing complete-mode)
        $this->school = School::create([
            'name' => 'Complete Mode School',
            'email' => 'complete@test.sch.ug',
            'phone' => '0700000000',
            'slug' => 'complete-mode-school',
            'status' => 1,
            'curriculum' => 'uneb',
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'School Admin',
            'email' => 'admin@complete.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        // Ensure an academic year exists (required for StandardLink)
        AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => date('Y'),
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'type' => 'Current Academic Year',
        ]);
    }

    /** @test */
    public function commit_all_complete_mode_creates_standard_link_for_each_class(): void
    {
        // Regression test for Finding 2:
        // complete-mode in commitAll() was creating Section rows but NOT StandardLink rows,
        // causing subjects, teacher links, and student academics to silently no-op.
        //
        // Additionally: the old code created a single $phase Standard and mapped ALL classes
        // to it (Bug 1 — single-Standard mapping for mixed-level schools). Now that content
        // steps delegate to OnboardingEngine, each class maps to its CORRECT tier Standard
        // (nursery/primary/o-level/a-level). For P1-P3, all map to 'primary', so we verify
        // the tier name explicitly rather than asserting all links share one arbitrary phase.

        $this->actingAs($this->admin);

        $component = Livewire::test(\App\Livewire\AgentToshi::class);

        // Set up the component in complete mode with standards data
        $component->set('mode', 'complete');
        $component->set('schoolId', $this->school->id);
        $component->set('standards', [
            ['name' => 'P1'],
            ['name' => 'P2'],
            ['name' => 'P3'],
        ]);
        $component->set('subjects', []);
        $component->set('teacherList', []);
        $component->set('teacherLinks', []);
        $component->set('terms', []);
        $component->set('studentList', []);
        $component->set('actionData', []);

        // Call commit (public entry point that internally calls commitAll)
        $component->call('commit');

        // Assert: StandardLink rows were created for each class
        $this->assertEquals(3, StandardLink::where('school_id', $this->school->id)->count(),
            'complete-mode must create a StandardLink for each class');

        // Assert: Each Section has a corresponding StandardLink
        $sections = Section::where('school_id', $this->school->id)->get();
        foreach ($sections as $section) {
            $link = StandardLink::where('school_id', $this->school->id)
                ->where('section_id', $section->id)
                ->first();
            $this->assertNotNull($link,
                "Section '{$section->name}' must have a StandardLink after complete-mode commitAll()");
        }

        // Assert: StandardLink references the CORRECT per-class tier Standard
        // (Bug 1 fix: each class maps to its grading-tier Standard, not one arbitrary $phase)
        $primaryStandard = Standard::where('school_id', $this->school->id)
            ->where('name', 'primary')
            ->first();
        $this->assertNotNull($primaryStandard, 'A primary Standard must exist for P1-P3 classes');

        foreach (StandardLink::where('school_id', $this->school->id)->get() as $link) {
            $this->assertEquals($primaryStandard->id, $link->standard_id,
                'P1/P2/P3 StandardLink must reference the primary tier Standard');
            $this->assertNotNull($link->academic_year_id,
                'StandardLink must reference an academic year');
        }
    }
}
