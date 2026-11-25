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
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * classMatchVariants: all supported input forms must expand to the full set of
 * variants so a section stored as "Primary Five" can be matched by "P.5",
 * "P5", "Primary 5", "primary five" etc.
 *
 * Also asserts that an unknown class does not drop the parent link request —
 * it is created with candidate_student_ids = null and child_class preserved.
 */
class ClassMatchVariantsTest extends TestCase
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
    // Unit: variant expansion
    // ------------------------------------------------------------------ //

    #[DataProvider('primaryFormsProvider')]
    public function test_primary_form_expands_to_full_set(string $input, int $n): void
    {
        $primaryWords = [1=>'One',2=>'Two',3=>'Three',4=>'Four',5=>'Five',6=>'Six',7=>'Seven'];
        $word = $primaryWords[$n];

        $variants = $this->service->classMatchVariants($input);

        $this->assertContains("P.{$n}", $variants, "P.{$n} missing for input '{$input}'");
        $this->assertContains("P{$n}", $variants, "P{$n} missing for input '{$input}'");
        $this->assertContains("Primary {$n}", $variants, "Primary {$n} missing for input '{$input}'");
        $this->assertContains("Primary {$word}", $variants, "Primary {$word} missing for input '{$input}'");
    }

    public static function primaryFormsProvider(): array
    {
        return [
            'dot-digit P.5'       => ['P.5', 5],
            'compact P5'          => ['P5', 5],
            'spaced P 5'          => ['P 5', 5],
            'Primary numeral 5'   => ['Primary 5', 5],
            'Primary word Five'   => ['Primary Five', 5],
            'Primary word five lc'=> ['primary five', 5],
            'P.1'                 => ['P.1', 1],
            'Primary 7'           => ['Primary 7', 7],
            'Primary Seven'       => ['Primary Seven', 7],
            'P7'                  => ['P7', 7],
        ];
    }

    #[DataProvider('seniorFormsProvider')]
    public function test_senior_form_expands_to_full_set(string $input, int $n): void
    {
        $seniorWords = [1=>'One',2=>'Two',3=>'Three',4=>'Four',5=>'Five',6=>'Six'];
        $word = $seniorWords[$n];

        $variants = $this->service->classMatchVariants($input);

        $this->assertContains("S.{$n}", $variants, "S.{$n} missing for input '{$input}'");
        $this->assertContains("S{$n}", $variants, "S{$n} missing for input '{$input}'");
        $this->assertContains("Senior {$n}", $variants, "Senior {$n} missing for input '{$input}'");
        $this->assertContains("Senior {$word}", $variants, "Senior {$word} missing for input '{$input}'");
        $this->assertContains("Secondary {$word}", $variants, "Secondary {$word} missing for input '{$input}'");
    }

    public static function seniorFormsProvider(): array
    {
        return [
            'dot-digit S.1'      => ['S.1', 1],
            'compact S1'         => ['S1', 1],
            'spaced S 1'         => ['S 1', 1],
            'Senior numeral 1'   => ['Senior 1', 1],
            'Senior word One'    => ['Senior One', 1],
            'senior word one lc' => ['senior one', 1],
            'S.6'                => ['S.6', 6],
            'Senior 4'           => ['Senior 4', 4],
            'Senior Four'        => ['Senior Four', 4],
            'S4'                 => ['S4', 4],
        ];
    }

    public function test_unknown_class_returns_raw_string_only(): void
    {
        $variants = $this->service->classMatchVariants('Purple Grade 99');

        $this->assertSame(['Purple Grade 99'], $variants);
    }

    public function test_empty_class_returns_empty_string(): void
    {
        $variants = $this->service->classMatchVariants('');

        // normalizeClassToken trims → empty string; filter removes empty → []
        $this->assertSame([], $variants);
    }

    // ------------------------------------------------------------------ //
    // Integration: unknown class → request created, candidate unresolved
    // ------------------------------------------------------------------ //

    public function test_unknown_class_does_not_drop_request(): void
    {
        $school = School::create([
            'name'   => 'Class Fallback School',
            'email'  => 'class-fallback@test.sch.ug',
            'status' => 1,
        ]);

        // Student exists but class "Purple Grade 99" matches no section.
        $this->makeStudentWithClass($school, 'Primary Five', 'Grace Auma');

        $request = $this->service->createFromFlowSubmission(
            '+256700000050',
            [
                'parent_name' => 'Sunday Johnson',
                'child_name'  => 'Grace Auma',
                'child_class' => 'Purple Grade 99',
                'school_name' => 'Class Fallback School',
            ],
        );

        // Request is created.
        $this->assertTrue($request->wasRecentlyCreated);
        $this->assertSame($school->id, $request->school_id);

        // Class typed by parent is preserved exactly.
        $this->assertSame('Purple Grade 99', $request->child_class);

        // No candidate found (class filter excluded the student).
        $this->assertNull($request->suggested_student_id);
    }

    public function test_empty_child_class_creates_request_with_candidate_by_name(): void
    {
        $school  = School::create([
            'name'   => 'Empty Class School',
            'email'  => 'empty-class@test.sch.ug',
            'status' => 1,
        ]);
        $student = $this->makeStudentWithClass($school, 'Primary Five', 'Grace Auma');

        // child_class is empty string — no class filter.
        $request = $this->service->createFromFlowSubmission(
            '+256700000051',
            [
                'parent_name' => 'Sunday Johnson',
                'child_name'  => 'Grace Auma',
                'child_class' => '',
                'school_name' => 'Empty Class School',
            ],
        );

        $this->assertTrue($request->wasRecentlyCreated);
        $this->assertSame($school->id, $request->school_id);
        $this->assertSame('', $request->child_class);
        // Name-only match should still find the unique student.
        $this->assertSame($student->id, $request->suggested_student_id);
    }

    // ------------------------------------------------------------------ //
    // Integration: Primary 5 (numeral form) now matches "Primary Five" section
    // ------------------------------------------------------------------ //

    public function test_primary_numeral_form_matches_word_section(): void
    {
        $school  = School::create([
            'name'   => 'Numeral Match School',
            'email'  => 'numeral-match@test.sch.ug',
            'status' => 1,
        ]);
        $student = $this->makeStudentWithClass($school, 'Primary Five', 'Grace Auma');

        $candidates = $this->service->findCandidateStudents('Grace Auma', 'Primary 5', $school->id);

        $this->assertCount(1, $candidates);
        $this->assertSame($student->id, $candidates->first()->id);
    }

    public function test_senior_numeral_form_matches_word_section(): void
    {
        $school  = School::create([
            'name'   => 'Senior Numeral School',
            'email'  => 'senior-numeral@test.sch.ug',
            'status' => 1,
        ]);
        $student = $this->makeStudentWithClass($school, 'Senior One', 'Grace Auma');

        $candidates = $this->service->findCandidateStudents('Grace Auma', 'Senior 1', $school->id);

        $this->assertCount(1, $candidates);
        $this->assertSame($student->id, $candidates->first()->id);
    }

    // ------------------------------------------------------------------ //
    // Helpers
    // ------------------------------------------------------------------ //

    private function makeStudentWithClass(School $school, string $sectionName, string $name): User
    {
        $ay = AcademicYear::create([
            'school_id'   => $school->id,
            'name'        => '2026',
            'description' => 'Current',
            'start_date'  => '2026-01-01 00:00:00',
            'end_date'    => '2026-12-31 23:59:59',
            'status'      => 1,
        ]);

        $standard = Standard::create(['school_id' => $school->id, 'name' => 'primary_upper', 'order' => 1]);
        $section  = Section::create(['school_id' => $school->id, 'name' => $sectionName]);
        $link     = StandardLink::create([
            'school_id'       => $school->id,
            'standard_id'     => $standard->id,
            'section_id'      => $section->id,
            'academic_year_id'=> $ay->id,
        ]);

        $student = User::factory()->create([
            'school_id'    => $school->id,
            'usergroup_id' => 6,
            'name'         => $name,
            'status'       => 'active',
        ]);

        StudentAcademic::create([
            'school_id'       => $school->id,
            'academic_year_id'=> $link->academic_year_id,
            'user_id'         => $student->id,
            'standardLink_id' => $link->id,
        ]);

        return $student;
    }
}
