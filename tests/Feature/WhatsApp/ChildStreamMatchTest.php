<?php

namespace Tests\Feature\WhatsApp;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Services\WhatsApp\ParentLinkRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * child_stream: when a parent provides a stream, narrow candidates by
 * standards_link.stream.  When stream doesn't match any candidate, fall back
 * to class-only matching so the request is never silently dropped.
 */
class ChildStreamMatchTest extends TestCase
{
    use RefreshDatabase;

    private ParentLinkRequestService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.test']]], 200),
        ]);

        config([
            'services.whatsapp.business_api_token'       => 'test-token',
            'services.whatsapp.business_phone_number_id' => '1416403124879552',
            'services.whatsapp.business_api_version'     => 'v21.0',
        ]);

        \DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student',     'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent',      'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->service = app(ParentLinkRequestService::class);
    }

    // ------------------------------------------------------------------ //
    // Scenario 1: stream given + matching → candidate found via stream
    // ------------------------------------------------------------------ //

    public function test_stream_given_and_matching_narrows_to_correct_student(): void
    {
        $school = School::create(['name' => 'Stream Match School', 'email' => 'stream-match@test.sch.ug', 'status' => 1]);

        // Two students in Primary Four — different streams.
        $studentEast = $this->makeStudentWithStream($school, 'Primary Four', 'East', 'Grace Auma');
        $studentWest = $this->makeStudentWithStream($school, 'Primary Four', 'West', 'Grace Auma');

        $candidates = $this->service->findCandidateStudents('Grace Auma', 'P.4', $school->id, 'East');

        $this->assertCount(1, $candidates);
        $this->assertSame($studentEast->id, $candidates->first()->id);
    }

    // ------------------------------------------------------------------ //
    // Scenario 2: stream given + no match → fallback to class-only
    // ------------------------------------------------------------------ //

    public function test_stream_given_but_unmatched_falls_back_to_class_only_in_submission(): void
    {
        $school  = School::create(['name' => 'Stream Fallback School', 'email' => 'stream-fallback@test.sch.ug', 'status' => 1]);
        $student = $this->makeStudentWithStream($school, 'Primary Four', 'East', 'Grace Auma');

        // Parent types "North" which does not match any stream — fallback should
        // find Grace Auma by class only and store the request.
        $request = $this->service->createFromFlowSubmission(
            '+256700000060',
            [
                'parent_name'  => 'Sunday Johnson',
                'child_name'   => 'Grace Auma',
                'child_class'  => 'P.4',
                'child_stream' => 'North',
                'school_name'  => 'Stream Fallback School',
            ],
        );

        // Request is created.
        $this->assertTrue($request->wasRecentlyCreated);
        $this->assertSame($school->id, $request->school_id);

        // Stream is stored as typed.
        $this->assertSame('North', $request->child_stream);

        // Fallback class-only match found Grace Auma.
        $this->assertSame($student->id, $request->suggested_student_id);
    }

    // ------------------------------------------------------------------ //
    // Scenario 3: stream empty → class-only (unchanged behaviour)
    // ------------------------------------------------------------------ //

    public function test_stream_empty_matches_by_class_only(): void
    {
        $school  = School::create(['name' => 'No Stream School', 'email' => 'no-stream@test.sch.ug', 'status' => 1]);
        $student = $this->makeStudentWithStream($school, 'Primary Four', 'East', 'Grace Auma');

        $candidates = $this->service->findCandidateStudents('Grace Auma', 'P.4', $school->id, '');

        $this->assertCount(1, $candidates);
        $this->assertSame($student->id, $candidates->first()->id);
    }

    // ------------------------------------------------------------------ //
    // Scenario 4: summaryLine includes stream when present
    // ------------------------------------------------------------------ //

    public function test_summary_line_includes_stream_when_stored(): void
    {
        $school  = School::create(['name' => 'Summary Stream School', 'email' => 'summary-stream@test.sch.ug', 'status' => 1]);
        $student = $this->makeStudentWithStream($school, 'Primary Four', 'East', 'Grace Auma');

        $request = $this->service->createFromFlowSubmission(
            '+256700000061',
            [
                'parent_name'  => 'Sunday Johnson',
                'child_name'   => 'Grace Auma',
                'child_class'  => 'P.4',
                'child_stream' => 'East',
                'school_name'  => 'Summary Stream School',
            ],
        );

        $this->assertStringContainsString('/ East', $request->summaryLine());
        $this->assertSame('East', $request->child_stream);
    }

    // ------------------------------------------------------------------ //
    // Helpers
    // ------------------------------------------------------------------ //

    private function makeStudentWithStream(
        School $school,
        string $sectionName,
        ?string $stream,
        string $name,
    ): User {
        $ay = AcademicYear::create([
            'school_id'   => $school->id,
            'name'        => '2026',
            'description' => 'Current',
            'start_date'  => '2026-01-01 00:00:00',
            'end_date'    => '2026-12-31 23:59:59',
            'status'      => 1,
        ]);

        $standard = Standard::firstOrCreate(
            ['school_id' => $school->id, 'name' => 'primary_upper'],
            ['order' => 1],
        );

        // Each (school_id, section_id, academic_year_id, stream) must be unique.
        $section = Section::firstOrCreate(
            ['school_id' => $school->id, 'name' => $sectionName],
        );

        $link = StandardLink::create([
            'school_id'        => $school->id,
            'standard_id'      => $standard->id,
            'section_id'       => $section->id,
            'academic_year_id' => $ay->id,
            'stream'           => $stream,
        ]);

        $student = User::factory()->create([
            'school_id'    => $school->id,
            'usergroup_id' => 6,
            'name'         => $name,
            'status'       => 'active',
        ]);

        StudentAcademic::create([
            'school_id'        => $school->id,
            'academic_year_id' => $link->academic_year_id,
            'user_id'          => $student->id,
            'standardLink_id'  => $link->id,
        ]);

        return $student;
    }
}
