<?php

namespace Tests\Feature\Toshi\SchoolAdmin;

use App\AiAgents\ParentOperationsAgent;
use App\AiAgents\Skills\AcademicSkill;
use App\AiAgents\Skills\FeeSkill;
use App\AiAgents\Skills\StudentSkill;
use App\AiAgents\TeacherOperationsAgent;
use App\AiAgents\Tools\ListStreamsWithoutClassTeacherTool;
use App\AiAgents\Tools\ListUnpaidFeeBalancesTool;
use App\AiAgents\Tools\SummarizeClassAttendanceTool;
use App\AiAgents\WhatsApp\SchoolAdminWhatsAppReadAgent;
use App\Models\User;
use App\Services\FeePositionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class SchoolAdminReadToolsTest extends TestCase
{
    use RefreshDatabase;

    private int $schoolId;

    private int $otherSchoolId;

    private int $yearId;

    private int $standardId;

    private int $eastLinkId;

    private int $westLinkId;

    private User $admin;

    private User $deputy;

    private User $teacher;

    private User $parent;

    private User $ada;

    private User $ben;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'schoolsubadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolId = $this->insertSchool('Read Tools School', 'read-tools', 'read-tools@test.sch', '+256700000091');
        $this->otherSchoolId = $this->insertSchool('Other Read School', 'other-read', 'other-read@test.sch', '+256700000092');

        $this->yearId = $this->insertYear($this->schoolId);
        $otherYearId = $this->insertYear($this->otherSchoolId);

        $this->admin = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 3,
            'name' => 'Ada Admin',
            'email' => 'admin.read@test.sch',
        ]);
        $this->deputy = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 4,
            'name' => 'Dan Deputy',
            'email' => 'deputy.read@test.sch',
        ]);
        $this->teacher = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 5,
            'name' => 'Tess Teacher',
            'email' => 'teacher.read@test.sch',
        ]);
        $this->parent = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 7,
            'name' => 'Pat Parent',
            'email' => 'parent.read@test.sch',
        ]);

        $this->standardId = $this->insertStandard($this->schoolId, 'Primary');
        $upperId = $this->insertStandard($this->schoolId, 'Upper');
        $p1 = $this->insertSection($this->schoolId, 'P.1');
        $p2 = $this->insertSection($this->schoolId, 'P.2');

        $this->eastLinkId = $this->insertLink($this->schoolId, $this->yearId, $this->standardId, $p1, 'East', null);
        $this->westLinkId = $this->insertLink($this->schoolId, $this->yearId, $this->standardId, $p1, 'West', $this->teacher->id);
        $p2LinkId = $this->insertLink($this->schoolId, $this->yearId, $upperId, $p2, 'Main', null);

        $this->ada = $this->student($this->schoolId, 'Ada Okello', 'ada.read@test.sch', $this->eastLinkId);
        $this->ben = $this->student($this->schoolId, 'Ben Kato', 'ben.read@test.sch', $this->westLinkId);
        $paid = $this->student($this->schoolId, 'Cara Paid', 'cara.read@test.sch', $this->eastLinkId);
        $inactive = $this->student($this->schoolId, 'Eve Inactive', 'eve.read@test.sch', $this->eastLinkId);
        DB::table('users')->where('id', $inactive->id)->update(['status' => 'inactive']);
        $this->student($this->schoolId, 'Finn Upper', 'finn.read@test.sch', $p2LinkId);

        $tuitionId = DB::table('fees_categories')->insertGetId([
            'school_id' => $this->schoolId,
            'standard_id' => $this->standardId,
            'name' => 'Tuition',
            'amount' => 100000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->payment($this->schoolId, $this->ada->id, 25000, $this->admin->id, null);
        $this->payment($this->schoolId, $paid->id, 100000, $this->admin->id, $tuitionId);

        $otherStandard = $this->insertStandard($this->otherSchoolId, 'Primary');
        $otherSection = $this->insertSection($this->otherSchoolId, 'P.1');
        $otherLink = $this->insertLink($this->otherSchoolId, $otherYearId, $otherStandard, $otherSection, 'East', null);
        $foreign = $this->student($this->otherSchoolId, 'Zara Foreign', 'zara.read@test.sch', $otherLink);
        DB::table('fees_categories')->insert([
            'school_id' => $this->otherSchoolId,
            'standard_id' => $otherStandard,
            'name' => 'Tuition',
            'amount' => 999999,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([
            ['2026-01-01 08:00:00', 0, null],
            ['2026-02-02 08:00:00', 1, null],
            ['2026-02-03 08:00:00', 1, 'present'],
            ['2026-02-04 08:00:00', 0, null],
            ['2026-02-05 08:00:00', 1, 'late'],
        ] as [$date, $status, $remarks]) {
            $this->attendance($this->schoolId, $this->yearId, $this->eastLinkId, $this->ada->id, $date, $status, $remarks);
        }
        $this->attendance($this->otherSchoolId, $otherYearId, $otherLink, $foreign->id, '2026-02-04 08:00:00', 0, null);

        unset($tuitionId);
    }

    public function test_tools_are_registered_for_admin_skills_and_not_for_teacher_or_parent(): void
    {
        $fee = collect((new FeeSkill)->tools())->map(fn ($tool) => $tool::class)->all();
        $academic = collect((new AcademicSkill)->tools())->map(fn ($tool) => $tool::class)->all();
        $student = collect((new StudentSkill)->tools())->map(fn ($tool) => $tool::class)->all();
        $whatsapp = collect((new SchoolAdminWhatsAppReadAgent)->tools())->map(fn ($tool) => $tool::class)->all();
        $teacher = collect((new TeacherOperationsAgent)->tools())->map(fn ($tool) => $tool::class)->all();
        $parent = collect((new ParentOperationsAgent)->tools())->map(fn ($tool) => $tool::class)->all();

        $this->assertContains(ListUnpaidFeeBalancesTool::class, $fee);
        $this->assertContains(ListStreamsWithoutClassTeacherTool::class, $academic);
        $this->assertContains(SummarizeClassAttendanceTool::class, $student);
        $this->assertContains(ListUnpaidFeeBalancesTool::class, $whatsapp);
        $this->assertContains(ListStreamsWithoutClassTeacherTool::class, $whatsapp);
        $this->assertContains(SummarizeClassAttendanceTool::class, $whatsapp);

        foreach ([ListUnpaidFeeBalancesTool::class, ListStreamsWithoutClassTeacherTool::class, SummarizeClassAttendanceTool::class] as $tool) {
            $this->assertNotContains($tool, $teacher);
            $this->assertNotContains($tool, $parent);
        }
    }

    public function test_unpaid_fee_balances_match_the_dashboard_and_stay_in_school(): void
    {
        $this->actingAs($this->admin);

        $dashboard = app(FeePositionService::class)->studentsWithBalance($this->schoolId);
        $names = array_column($dashboard, 'name');
        $this->assertContains('ADA OKELLO', $names);
        $this->assertContains('BEN KATO', $names);
        $this->assertNotContains('CARA PAID', $names);
        $this->assertNotContains('EVE INACTIVE', $names);
        $this->assertNotContains('ZARA FOREIGN', $names);

        $ada = collect($dashboard)->firstWhere('name', 'ADA OKELLO');
        $this->assertEquals(75000.0, (float) $ada['balance']);

        $result = (new ListUnpaidFeeBalancesTool)->handle(new Request([]));

        $this->assertStringContainsString('ADA OKELLO', $result);
        $this->assertStringContainsString('UGX 75,000', $result);
        $this->assertStringContainsString('BEN KATO', $result);
        $this->assertStringNotContainsString('CARA PAID', $result);
        $this->assertStringNotContainsString('EVE INACTIVE', $result);
        $this->assertStringNotContainsString('ZARA FOREIGN', $result);
        $this->assertStringNotContainsString('999,999', $result);
    }

    public function test_unpaid_fee_balances_filter_by_stream_and_term(): void
    {
        $termOne = DB::table('academic_terms')->insertGetId([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->yearId,
            'name' => 'Term 1',
            'status' => 'current',
            'starts_on' => '2026-02-01 00:00:00',
            'ends_on' => '2026-04-30 00:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $termTwo = DB::table('academic_terms')->insertGetId([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->yearId,
            'name' => 'Term 2',
            'status' => 'next',
            'starts_on' => '2026-05-01 00:00:00',
            'ends_on' => '2026-08-01 00:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $termOneFee = DB::table('fees_categories')->insertGetId([
            'school_id' => $this->schoolId,
            'standard_id' => $this->standardId,
            'academic_term_id' => $termOne,
            'name' => 'Term 1 levy',
            'amount' => 30000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('fees_categories')->insert([
            'school_id' => $this->schoolId,
            'standard_id' => $this->standardId,
            'academic_term_id' => $termTwo,
            'name' => 'Term 2 levy',
            'amount' => 50000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->payment($this->schoolId, $this->ben->id, 30000, $this->admin->id, $termOneFee);

        $this->actingAs($this->deputy);

        $east = (new ListUnpaidFeeBalancesTool)->handle(new Request([
            'class' => 'P.1',
            'stream' => 'East',
        ]));
        $this->assertStringContainsString('ADA OKELLO', $east);
        $this->assertStringNotContainsString('BEN KATO', $east);
        $this->assertStringNotContainsString('ZARA FOREIGN', $east);

        // Term 1 is current: Tuition (no term) + Term 1 levy, never Term 2's levy.
        // Payments count as on the payments dashboard (#1202).
        $current = (new ListUnpaidFeeBalancesTool)->handle(new Request([]));
        $this->assertStringContainsString('ADA OKELLO', $current);
        $this->assertStringContainsString('UGX 105,000', $current);
        $this->assertStringContainsString('UGX 100,000', $current);
        $this->assertStringNotContainsString('ZARA FOREIGN', $current);

        $termOneRows = (new ListUnpaidFeeBalancesTool)->handle(new Request(['term' => 'Term 1']));
        $this->assertSame($current, $termOneRows);

        $termTwoRows = (new ListUnpaidFeeBalancesTool)->handle(new Request(['term' => 'Term 2']));
        $this->assertStringContainsString('ADA OKELLO', $termTwoRows);
        $this->assertStringContainsString('UGX 125,000', $termTwoRows);
        $this->assertStringContainsString('UGX 120,000', $termTwoRows);
        $this->assertStringNotContainsString('ZARA FOREIGN', $termTwoRows);

        $service = app(FeePositionService::class)->studentsWithBalance($this->schoolId, [
            'academic_term_id' => $termOne,
        ]);
        $ada = collect($service)->firstWhere('name', 'ADA OKELLO');
        $this->assertEquals(105000.0, (float) $ada['balance']);

        // The unfiltered list matches what the dashboard (no filters) returns.
        $this->assertEquals(
            app(FeePositionService::class)->studentsWithBalance($this->schoolId),
            $service,
        );

        $unknown = (new ListUnpaidFeeBalancesTool)->handle(new Request(['term' => 'Term 9']));
        $this->assertStringContainsString('No term named', $unknown);
    }

    public function test_unpaid_fee_balances_deny_teacher_and_parent(): void
    {
        $this->actingAs($this->teacher);
        $teacher = (new ListUnpaidFeeBalancesTool)->handle(new Request([]));
        $this->assertStringStartsWith('❌', $teacher);
        $this->assertStringContainsString('not authorized', strtolower($teacher));
        $this->assertStringNotContainsString('ADA OKELLO', $teacher);

        $this->actingAs($this->parent);
        $parent = (new ListUnpaidFeeBalancesTool)->handle(new Request([]));
        $this->assertStringStartsWith('❌', $parent);
        $this->assertStringContainsString('not authorized', strtolower($parent));
        $this->assertStringNotContainsString('ADA OKELLO', $parent);
    }

    public function test_streams_without_a_class_teacher_are_scoped(): void
    {
        $this->actingAs($this->admin);

        $result = (new ListStreamsWithoutClassTeacherTool)->handle(new Request([]));

        $this->assertStringContainsString('Primary P.1 · East', $result);
        $this->assertStringContainsString('Upper P.2', $result);
        $this->assertStringNotContainsString('West', $result);
        $this->assertStringNotContainsString('ZARA FOREIGN', $result);

        $east = (new ListStreamsWithoutClassTeacherTool)->handle(new Request([
            'class' => 'p.1',
            'stream' => 'east',
        ]));
        $this->assertStringContainsString('Primary P.1 · East', $east);
        $this->assertStringNotContainsString('P.2', $east);
        $this->assertStringNotContainsString('West', $east);
    }

    public function test_streams_without_a_class_teacher_deny_teacher_and_parent(): void
    {
        $this->actingAs($this->teacher);
        $teacher = (new ListStreamsWithoutClassTeacherTool)->handle(new Request([]));
        $this->assertStringStartsWith('❌', $teacher);
        $this->assertStringContainsString('not authorized', strtolower($teacher));
        $this->assertStringNotContainsString('East', $teacher);

        $this->actingAs($this->parent);
        $parent = (new ListStreamsWithoutClassTeacherTool)->handle(new Request([]));
        $this->assertStringStartsWith('❌', $parent);
        $this->assertStringContainsString('not authorized', strtolower($parent));
    }

    public function test_class_attendance_summary_counts_and_stays_in_school(): void
    {
        $this->actingAs($this->deputy);

        $result = (new SummarizeClassAttendanceTool)->handle(new Request([
            'class' => 'P.1',
            'stream' => 'East',
            'from' => '2026-02-01',
            'to' => '2026-02-05',
        ]));

        $this->assertStringContainsString('ADA OKELLO — present 2, absent 1, late 1', $result);
        $this->assertStringNotContainsString('BEN KATO', $result);
        $this->assertStringNotContainsString('ZARA FOREIGN', $result);
        $this->assertStringNotContainsString('EVE INACTIVE', $result);
    }

    public function test_class_attendance_summary_denies_teacher_and_parent_and_rejects_a_long_range(): void
    {
        $this->actingAs($this->teacher);
        $teacher = (new SummarizeClassAttendanceTool)->handle(new Request([
            'class' => 'P.1',
            'from' => '2026-02-01',
            'to' => '2026-02-05',
        ]));
        $this->assertStringStartsWith('❌', $teacher);
        $this->assertStringContainsString('not authorized', strtolower($teacher));
        $this->assertStringNotContainsString('ADA OKELLO', $teacher);

        $this->actingAs($this->parent);
        $parent = (new SummarizeClassAttendanceTool)->handle(new Request([
            'class' => 'P.1',
            'from' => '2026-02-01',
            'to' => '2026-02-05',
        ]));
        $this->assertStringStartsWith('❌', $parent);
        $this->assertStringContainsString('not authorized', strtolower($parent));

        $this->actingAs($this->admin);
        $long = (new SummarizeClassAttendanceTool)->handle(new Request([
            'class' => 'P.1',
            'from' => '2026-01-01',
            'to' => '2026-06-01',
        ]));
        $this->assertStringContainsString('92 days', $long);
        $this->assertStringNotContainsString('ADA OKELLO', $long);
    }

    private function insertSchool(string $name, string $slug, string $email, string $phone): int
    {
        return DB::table('schools')->insertGetId([
            'name' => $name,
            'slug' => $slug,
            'email' => $email,
            'phone' => $phone,
            'status' => 1,
            'toshi_enabled' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertYear(int $schoolId): int
    {
        return DB::table('academic_years')->insertGetId([
            'school_id' => $schoolId,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertStandard(int $schoolId, string $name): int
    {
        return DB::table('standards')->insertGetId([
            'school_id' => $schoolId,
            'name' => $name,
            'order' => 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertSection(int $schoolId, string $name): int
    {
        return DB::table('sections')->insertGetId([
            'school_id' => $schoolId,
            'name' => $name,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertLink(int $schoolId, int $yearId, int $standardId, int $sectionId, string $stream, ?int $teacherId): int
    {
        return DB::table('standards_link')->insertGetId([
            'school_id' => $schoolId,
            'academic_year_id' => $yearId,
            'class_teacher_id' => $teacherId,
            'standard_id' => $standardId,
            'section_id' => $sectionId,
            'stream' => $stream,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function student(int $schoolId, string $name, string $email, int $linkId): User
    {
        $yearId = (int) DB::table('academic_years')->where('school_id', $schoolId)->where('status', 1)->value('id');
        $user = User::factory()->create([
            'school_id' => $schoolId,
            'usergroup_id' => 6,
            'name' => $name,
            'email' => $email,
            'status' => 'active',
        ]);
        DB::table('student_academics')->insert([
            'school_id' => $schoolId,
            'academic_year_id' => $yearId,
            'user_id' => $user->id,
            'standardLink_id' => $linkId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }

    private function payment(int $schoolId, int $userId, float $amount, int $recordedBy, ?int $categoryId): void
    {
        DB::table('fee_payments')->insert([
            'school_id' => $schoolId,
            'fee_category_id' => $categoryId,
            'user_id' => $userId,
            'amount' => $amount,
            'paid_on' => '2026-02-10',
            'recorded_by' => $recordedBy,
            'status' => 'paid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function attendance(int $schoolId, int $yearId, int $linkId, int $userId, string $date, int $status, ?string $remarks): void
    {
        DB::table('attendances')->insert([
            'school_id' => $schoolId,
            'academic_year_id' => $yearId,
            'standardLink_id' => $linkId,
            'user_id' => $userId,
            'date' => $date,
            'session' => 'forenoon',
            'status' => $status,
            'remarks' => $remarks,
            'recorded_by' => $this->admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
