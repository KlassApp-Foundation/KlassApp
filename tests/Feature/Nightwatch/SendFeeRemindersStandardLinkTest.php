<?php

namespace Tests\Feature\Nightwatch;

use App\Models\AcademicYear;
use App\Models\FeesCategories;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Services\OutboundWhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class SendFeeRemindersStandardLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_finds_students_via_standard_link_not_missing_standard_id_column(): void
    {
        DB::table('usergroups')->upsert([
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $school = School::create([
            'name' => 'Fee Reminder School',
            'email' => 'fee-reminder@test.sch.ug',
            'phone' => '+256700000200',
            'slug' => 'fee-reminder-'.uniqid(),
            'status' => 1,
        ]);

        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        // Burn a standard row so fee.standard_id ≠ student.standardLink_id by coincidence.
        Standard::create([
            'school_id' => $school->id,
            'name' => 'nursery',
            'order' => 0,
            'status' => 1,
        ]);

        $standard = Standard::create([
            'school_id' => $school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);

        $section = Section::create([
            'school_id' => $school->id,
            'name' => 'P.1',
            'status' => 1,
        ]);

        $link = StandardLink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);

        // Distinct IDs so a naive whereIn(standardLink_id, fee.standard_ids) would miss.
        $this->assertNotSame((int) $standard->id, (int) $link->id);

        FeesCategories::create([
            'school_id' => $school->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'name' => 'Tuition',
            'amount' => 100000,
        ]);

        $student = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'email' => 'fee.student@test.sch.ug',
        ]);

        StudentAcademic::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'user_id' => $student->id,
            'standardLink_id' => $link->id,
        ]);

        $outbound = Mockery::mock(OutboundWhatsAppService::class);
        $outbound->shouldReceive('notifyFeeReminder')
            ->once()
            ->with($student->id, 'reminder')
            ->andReturn(1);
        $this->app->instance(OutboundWhatsAppService::class, $outbound);

        $this->artisan('whatsapp:send-fee-reminders', [
            '--type' => 'reminder',
            '--school-id' => $school->id,
        ])->assertSuccessful();
    }

    public function test_notify_fee_reminder_resolves_standard_via_standard_link(): void
    {
        DB::table('usergroups')->upsert([
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $school = School::create([
            'name' => 'Fee Notify School',
            'email' => 'fee-notify@test.sch.ug',
            'phone' => '+256700000201',
            'slug' => 'fee-notify-'.uniqid(),
            'status' => 1,
        ]);

        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        Standard::create([
            'school_id' => $school->id,
            'name' => 'nursery',
            'order' => 0,
            'status' => 1,
        ]);

        $standard = Standard::create([
            'school_id' => $school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);

        $section = Section::create([
            'school_id' => $school->id,
            'name' => 'P.2',
            'status' => 1,
        ]);

        $link = StandardLink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);

        FeesCategories::create([
            'school_id' => $school->id,
            'standard_id' => $standard->id,
            'name' => 'Boarding',
            'amount' => 50000,
        ]);

        $student = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'email' => 'fee.notify.student@test.sch.ug',
        ]);

        StudentAcademic::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'user_id' => $student->id,
            'standardLink_id' => $link->id,
        ]);

        $student->load('studentAcademicLatest.standardLink');
        $resolvedStandardId = $student->studentAcademicLatest?->standardLink?->standard_id;

        $this->assertSame((int) $standard->id, (int) $resolvedStandardId);
        $this->assertTrue(
            FeesCategories::where('school_id', $school->id)
                ->where('standard_id', $resolvedStandardId)
                ->exists()
        );
        // Old buggy path read a non-existent student_academics.standard_id → null → no fees.
        $this->assertNull($student->studentAcademicLatest->getAttribute('standard_id'));
    }

    public function test_get_parent_phones_scopes_by_school_without_where_pivot(): void
    {
        DB::table('usergroups')->upsert([
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $school = School::create([
            'name' => 'Parent Phones School',
            'email' => 'parent-phones@test.sch.ug',
            'phone' => '+256700000202',
            'slug' => 'parent-phones-'.uniqid(),
            'status' => 1,
        ]);

        $student = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'email' => 'phones.student@test.sch.ug',
        ]);

        $parent = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 7,
            'email' => 'phones.parent@test.sch.ug',
            'mobile_no' => '+256700555666',
        ]);

        DB::table('student_parent_links')->insert([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'parent_id' => $parent->id,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $phones = app(OutboundWhatsAppService::class)->getParentPhones($student);
        $this->assertContains('+256700555666', $phones);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
