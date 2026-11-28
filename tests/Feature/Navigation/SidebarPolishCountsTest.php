<?php

namespace Tests\Feature\Navigation;

use App\Models\AcademicYear;
use App\Models\Admission;
use App\Models\Approval;
use App\Models\ParentLinkRequest;
use App\Models\School;
use App\Models\SchoolPayTransaction;
use App\Models\User;
use App\States\Approval\Pending;
use App\Support\SidebarCounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SidebarPolishCountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_chips_use_this_schools_pending_counts_and_zero_draws_nothing(): void
    {
        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $school = School::create([
            'name' => 'Chip School',
            'slug' => 'chip-school-'.uniqid(),
            'email' => uniqid().'@chip.test',
            'phone' => '+256700000111',
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);
        $other = School::create([
            'name' => 'Other Chip School',
            'slug' => 'chip-other-'.uniqid(),
            'email' => uniqid().'@chip.test',
            'phone' => '+256700000222',
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);
        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'y',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        $admin = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Chip Admin',
        ]);

        $this->actingAs($admin);
        $this->assertSame(
            ['admissions' => 0, 'unmatched' => 0, 'approvals' => 0, 'whatsapp' => 0, 'mark_submissions' => 0],
            SidebarCounts::forSchool($school->id)
        );
        $quiet = view('layouts.partials.sidebar-menu', ['role' => 'admin'])->render();
        // Zero draws no visible chip; count rows keep a hidden span so the
        // live poll can reveal the badge without a reload.
        $this->assertDoesNotMatchRegularExpression('/<span class="sidebar-v3-count"(?![^>]*hidden)[^>]*>\s*\d/', $quiet);
        $this->assertMatchesRegularExpression('/data-sidebar-count="whatsapp"[^>]*hidden/', $quiet);

        Admission::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Pending Child',
            'date_of_birth' => '2016-05-01',
            'gender' => 'male',
            'permanent_address' => 'Addr',
            'address_for_communication' => 'Addr',
            'half_yearly_mark_details' => 'n/a',
            'father_name' => 'Father',
            'father_mobile_no' => '+256700999888',
            'application_no' => 'ADM-CHIP-1',
            'application_status' => 'Pending',
            'payment_status' => 'unpaid',
            'remarks' => '',
        ]);
        Admission::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Draft Child',
            'date_of_birth' => '2016-05-01',
            'gender' => 'female',
            'permanent_address' => 'Addr',
            'address_for_communication' => 'Addr',
            'half_yearly_mark_details' => 'n/a',
            'father_name' => 'Father',
            'father_mobile_no' => '+256700999889',
            'application_no' => 'ADM-CHIP-2',
            'application_status' => 'Draft',
            'payment_status' => 'unpaid',
            'remarks' => '',
        ]);
        Admission::create([
            'school_id' => $other->id,
            'name' => 'Other Child',
            'date_of_birth' => '2016-05-01',
            'gender' => 'male',
            'permanent_address' => 'Addr',
            'address_for_communication' => 'Addr',
            'half_yearly_mark_details' => 'n/a',
            'father_name' => 'Father',
            'father_mobile_no' => '+256700999880',
            'application_no' => 'ADM-OTHER-1',
            'application_status' => 'Pending',
            'payment_status' => 'unpaid',
            'remarks' => '',
        ]);

        SchoolPayTransaction::create([
            'school_id' => $school->id,
            'amount' => 1000,
            'student_payment_code' => 'SPC-CHIP-1',
            'schoolpay_receipt' => 'RCPT-CHIP-1',
            'paid_at' => now(),
        ]);
        SchoolPayTransaction::create([
            'school_id' => $other->id,
            'amount' => 1000,
            'student_payment_code' => 'SPC-OTHER-1',
            'schoolpay_receipt' => 'RCPT-OTHER-1',
            'paid_at' => now(),
        ]);

        $student = User::factory()->create(['school_id' => $school->id, 'usergroup_id' => 6, 'name' => 'Chip Student']);
        $link = ParentLinkRequest::create([
            'school_id' => $school->id,
            'phone' => '+256700444111',
            'parent_name' => 'Chip Parent',
            'child_name' => 'Chip Student',
            'child_class' => 'P5',
            'school_name' => 'Chip School',
            'suggested_student_id' => $student->id,
            'candidate_student_ids' => [$student->id],
            'status' => 'pending',
        ]);
        Approval::create([
            'approvable_type' => ParentLinkRequest::class,
            'approvable_id' => $link->id,
            'state' => Pending::class,
            'requested_by' => null,
            'comments' => $link->summaryLine(),
        ]);

        $counts = SidebarCounts::forSchool($school->id);
        $this->assertSame(1, $counts['admissions']);
        $this->assertSame(1, $counts['unmatched']);
        $this->assertSame(1, $counts['approvals']);
        $this->assertSame(1, $counts['whatsapp']);

        $html = view('layouts.partials.sidebar-menu', ['role' => 'admin'])->render();
        $this->assertSame(4, substr_count($html, 'sidebar-v3-count'));
        $this->assertMatchesRegularExpression('/data-sidebar-count="whatsapp"[^>]*>1</', $html);
        $this->assertStringContainsString('>Release marks</span>', $html);
        $this->assertStringContainsString('>Admissions</span>', $html);
        $this->assertStringContainsString('>Unmatched payments</span>', $html);
        $this->assertStringContainsString('>Approvals</span>', $html);
        $this->assertDoesNotMatchRegularExpression(
            '/aria-label="Students"[\s\S]{0,180}sidebar-v3-count/',
            $html
        );
    }

    public function test_whatsapp_badge_counts_all_three_request_sources(): void
    {
        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $school = School::create([
            'name' => 'Badge School',
            'slug' => 'badge-school-'.uniqid(),
            'email' => uniqid().'@badge.test',
            'phone' => '+256700000333',
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);
        $admin = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Badge Admin',
        ]);
        $student = User::factory()->create(['school_id' => $school->id, 'usergroup_id' => 6, 'name' => 'Badge Student']);

        foreach (['klassapp_number', 'form', 'demo'] as $i => $source) {
            $link = ParentLinkRequest::create([
                'school_id' => $school->id,
                'phone' => '+25670055510'.$i,
                'parent_name' => 'Badge Parent '.$i,
                'child_name' => 'Badge Student',
                'child_class' => 'P5',
                'school_name' => 'Badge School',
                'suggested_student_id' => $student->id,
                'candidate_student_ids' => [$student->id],
                'status' => 'pending',
                'source' => $source,
            ]);
            Approval::create([
                'approvable_type' => ParentLinkRequest::class,
                'approvable_id' => $link->id,
                'state' => Pending::class,
                'requested_by' => null,
                'comments' => $link->summaryLine(),
            ]);
        }

        $this->actingAs($admin);

        $counts = SidebarCounts::forSchool($school->id);
        $this->assertSame(3, $counts['whatsapp'], 'all three request sources must count');

        $html = view('layouts.partials.sidebar-menu', ['role' => 'admin'])->render();
        $this->assertMatchesRegularExpression('/data-sidebar-count="whatsapp"[^>]*>3</', $html);
    }

    public function test_exams_and_reports_offers_release_marks(): void
    {
        $exams = collect(config('navigation.roles.admin.sections'))
            ->flatMap(fn (array $section) => $section['rows'] ?? [])
            ->firstWhere('label', 'Exams and reports');

        $release = collect($exams['children'])->firstWhere('label', 'Release marks');
        $this->assertNotNull($release, 'Exams and reports must offer Release marks');
        $this->assertSame('admin/marks/releases/new', $release['url']);
        $this->assertContains('admin/marks/releases*', $release['paths']);
    }

    public function test_settings_children_use_the_existing_pages(): void
    {
        $settings = collect(config('navigation.roles.admin.sections'))
            ->flatMap(fn (array $section) => $section['rows'] ?? [])
            ->firstWhere('label', 'Settings');

        $years = collect($settings['children'])->firstWhere('label', 'Academic years and terms');
        $exports = collect($settings['children'])->firstWhere('label', 'Data exports');

        $this->assertSame('admin/academics', $years['url']);
        $this->assertSame('admin/reports', $exports['url']);
        $this->assertContains('admin/academic-term*', $years['paths']);
    }
}
