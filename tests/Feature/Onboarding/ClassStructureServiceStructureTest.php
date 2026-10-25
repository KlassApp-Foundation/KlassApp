<?php

namespace Tests\Feature\Onboarding;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Onboarding\Steps\Steps\StandardsStep;
use App\Services\ClassStructureService;
use App\Services\OnboardingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ClassStructureServiceStructureTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private ClassStructureService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => 'Structure Service School',
            'email' => 'structure-service@test.sch.ug',
            'phone' => '0700000044',
            'slug' => 'structure-service',
            'status' => 1,
            'curriculum' => 'uneb',
            'toshi_enabled' => 0,
        ]);
        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2027',
            'description' => 'Current Academic Year',
            'status' => 1,
            'start_date' => '2027-01-01',
            'end_date' => '2027-12-31',
        ]);
        $this->service = app(ClassStructureService::class);
    }

    public function test_save_creates_classes_and_streams_and_a_repeat_is_idempotent(): void
    {
        $payload = [[
            'name' => 'Primary One',
            'streams' => ['A', 'B'],
        ]];

        $preview = $this->service->preview($this->school, $payload);
        $this->assertSame("I'll create 1 class and 2 streams", $preview['summary']);
        $this->assertSame(0, Section::where('school_id', $this->school->id)->count());

        $this->service->saveClasses($this->school, $this->year, $payload);
        $this->service->saveClasses($this->school, $this->year, $payload);

        $names = Section::where('school_id', $this->school->id)->orderBy('name')->pluck('name')->all();
        $this->assertSame(['Primary One', 'Primary One A', 'Primary One B'], $names);
        $this->assertSame(3, StandardLink::where('school_id', $this->school->id)->count());
        $again = $this->service->preview($this->school->fresh(), $payload);
        $this->assertSame('All listed classes already exist — nothing will change.', $again['summary']);
    }

    public function test_rename_changes_the_stream_and_keeps_the_base_class(): void
    {
        $this->service->saveClasses($this->school, $this->year, [[
            'name' => 'Primary One',
            'streams' => ['A'],
        ]]);

        $this->service->saveClasses($this->school, $this->year, [[
            'name' => 'Primary One',
            'rename_streams' => ['A' => 'East'],
        ]]);

        $this->assertSame('Primary One', Section::where('school_id', $this->school->id)->where('name', 'Primary One')->value('name'));
        $this->assertNull(Section::where('school_id', $this->school->id)->where('name', 'Primary One A')->first());
        $renamed = Section::where('school_id', $this->school->id)->where('name', 'Primary One East')->first();
        $this->assertNotNull($renamed);
        $this->assertSame(1, (int) $renamed->status);
    }

    public function test_removing_a_stream_with_students_returns_a_step_issue_and_writes_nothing(): void
    {
        $this->service->saveClasses($this->school, $this->year, [[
            'name' => 'Primary One',
            'streams' => ['A'],
        ]]);
        $stream = Section::where('school_id', $this->school->id)->where('name', 'Primary One A')->firstOrFail();
        $link = StandardLink::where('school_id', $this->school->id)->where('section_id', $stream->id)->firstOrFail();
        $student = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'name' => 'Amina Nalwoga',
        ]);
        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'user_id' => $student->id,
            'standardLink_id' => $link->id,
        ]);

        $payload = [[
            'name' => 'Primary One',
            'remove_streams' => ['A'],
        ]];
        $issues = $this->service->issues($this->school, $payload);
        $this->assertCount(1, $issues);
        $this->assertSame('standards.stream_students', $issues[0]->code);
        $this->assertSame("Primary One, stream A still has students.", $issues[0]->cause);
        $this->assertNotSame('', $issues[0]->fix);

        $stepIssues = app(StandardsStep::class)->issues($this->school->fresh(), $payload);
        $this->assertSame($issues[0]->cause, $stepIssues[0]->cause);
        $this->assertSame($issues[0]->fix, $stepIssues[0]->fix);

        try {
            $this->service->saveClasses($this->school, $this->year, $payload);
            $this->fail('A stream with students was removed.');
        } catch (ValidationException $e) {
            $this->assertSame("Primary One, stream A still has students.", $e->errors()['classes'][0]);
        }

        $this->assertSame(1, (int) $stream->fresh()->status);
        $this->assertSame('1', (string) $link->fresh()->status);
    }

    public function test_missing_and_duplicate_streams_name_the_class_and_stream(): void
    {
        $this->service->saveClasses($this->school, $this->year, [[
            'name' => 'Primary One',
        ]]);

        $missing = $this->service->issues($this->school, [[
            'name' => 'Primary One',
            'remove_streams' => ['A'],
        ]]);
        $this->assertSame('standards.stream_missing', $missing[0]->code);
        $this->assertSame("Primary One, stream A doesn't exist.", $missing[0]->cause);

        $duplicate = $this->service->issues($this->school, [[
            'name' => 'Primary One',
            'streams' => ['A', 'A'],
        ]]);
        $this->assertSame('standards.stream_duplicate', $duplicate[0]->code);
        $this->assertSame('Primary One, stream A is listed more than once.', $duplicate[0]->cause);
    }

    public function test_save_classes_rolls_back_when_a_later_class_fails(): void
    {
        $before = $this->structureRowCounts();

        $dispatcher = new \Illuminate\Events\Dispatcher();
        $dispatcher->listen('eloquent.created: '.Section::class, function (Section $section): void {
            if ($section->name === 'Primary Two') {
                throw new \RuntimeException('forced mid-save failure');
            }
        });
        $original = Section::getEventDispatcher();
        Section::setEventDispatcher($dispatcher);

        try {
            try {
                $this->service->saveClasses($this->school, $this->year, [
                    ['name' => 'Primary One', 'streams' => ['A']],
                    ['name' => 'Primary Two', 'streams' => ['B']],
                ]);
                $this->fail('A failure part-way through saveClasses was ignored.');
            } catch (\RuntimeException $e) {
                $this->assertSame('forced mid-save failure', $e->getMessage());
            }

            try {
                app(OnboardingEngine::class)->saveStandards($this->school, $this->year, [
                    ['name' => 'Primary One', 'streams' => ['A']],
                    ['name' => 'Primary Two', 'streams' => ['B']],
                ]);
                $this->fail('A failure part-way through the caller was ignored.');
            } catch (\RuntimeException $e) {
                $this->assertSame('forced mid-save failure', $e->getMessage());
            }

            $this->assertSame($before, $this->structureRowCounts());
        } finally {
            Section::setEventDispatcher($original);
        }
    }

    public function test_renaming_a_stream_to_a_name_taken_in_that_class_and_year_errors_on_the_stream_field(): void
    {
        $this->service->saveClasses($this->school, $this->year, [[
            'name' => 'Primary One',
            'streams' => ['A', 'B'],
        ]]);

        $taken = Section::where('school_id', $this->school->id)->where('name', 'Primary One B')->firstOrFail();
        $link = StandardLink::where('school_id', $this->school->id)->where('section_id', $taken->id)->firstOrFail();
        $this->assertSame((int) $this->year->id, (int) $link->academic_year_id);

        $payload = [[
            'name' => 'Primary One',
            'rename_streams' => ['A' => 'B'],
        ]];

        $issues = $this->service->issues($this->school, $payload);
        $this->assertSame('standards.stream_exists', $issues[0]->code);
        $this->assertSame('stream', $issues[0]->field);
        $this->assertSame(
            'Primary One, stream B: a stream with that name already exists.',
            $issues[0]->cause,
        );

        $stepIssues = app(StandardsStep::class)->issues($this->school->fresh(), $payload);
        $this->assertSame('stream', $stepIssues[0]->field);
        $this->assertSame($issues[0]->cause, $stepIssues[0]->cause);

        $names = Section::where('school_id', $this->school->id)->orderBy('name')->pluck('name')->all();

        try {
            $this->service->saveClasses($this->school, $this->year, $payload);
            $this->fail('A stream was renamed onto a name already used in that class.');
        } catch (ValidationException $e) {
            $this->assertSame(
                'Primary One, stream B: a stream with that name already exists.',
                $e->errors()['stream'][0],
            );
        }

        $this->assertSame($names, Section::where('school_id', $this->school->id)->orderBy('name')->pluck('name')->all());

        $streamA = Section::where('school_id', $this->school->id)->where('name', 'Primary One A')->firstOrFail();
        try {
            $this->service->renameStream($this->school, $streamA, 'Primary One B');
            $this->fail('renameStream accepted a name already used in that class.');
        } catch (ValidationException $e) {
            $this->assertSame('A stream with that name already exists.', $e->errors()['name'][0]);
        }

        $this->assertSame('Primary One A', $streamA->fresh()->name);
        $this->assertSame('Primary One B', $taken->fresh()->name);
    }

    /**
     * @return array{sections: int, links: int, standards: int, subjects: int}
     */
    private function structureRowCounts(): array
    {
        return [
            'sections' => Section::where('school_id', $this->school->id)->count(),
            'links' => StandardLink::where('school_id', $this->school->id)->count(),
            'standards' => Standard::where('school_id', $this->school->id)->count(),
            'subjects' => Subject::where('school_id', $this->school->id)->count(),
        ];
    }

    public function test_writes_stay_inside_the_school(): void
    {
        $other = School::create([
            'name' => 'Other Structure School',
            'email' => 'other-structure@test.sch.ug',
            'phone' => '0700000045',
            'slug' => 'other-structure',
            'status' => 1,
            'toshi_enabled' => 0,
        ]);
        $otherYear = AcademicYear::create([
            'school_id' => $other->id,
            'name' => '2027',
            'description' => 'Current Academic Year',
            'status' => 1,
            'start_date' => '2027-01-01',
            'end_date' => '2027-12-31',
        ]);

        $this->service->saveClasses($this->school, $this->year, [[
            'name' => 'Primary One',
            'streams' => ['A'],
        ]]);

        $this->assertSame(0, Section::where('school_id', $other->id)->count());

        $otherIssues = $this->service->issues($other, [[
            'name' => 'Primary One',
            'remove_streams' => ['A'],
        ]]);
        $this->assertSame("Primary One, stream A doesn't exist.", $otherIssues[0]->cause);

        try {
            $this->service->saveClasses($this->school, $otherYear, [[
                'name' => 'Primary Two',
            ]]);
            $this->fail('A year from another school was accepted.');
        } catch (ValidationException) {
            $this->assertSame(0, Section::where('school_id', $other->id)->count());
            $this->assertNull(Section::where('school_id', $this->school->id)->where('name', 'Primary Two')->first());
        }
    }
}
