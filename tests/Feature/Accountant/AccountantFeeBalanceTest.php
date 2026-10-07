<?php

namespace Tests\Feature\Accountant;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\FeePayment;
use App\Models\FeesCategories;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The bursar records fee payments — so the bursar's payments page must show the
 * resulting balance (collected this term, outstanding, arrears, collection
 * rate), the same numbers the admin kit reports.
 */
class AccountantFeeBalanceTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $bursar;
    private User $student;
    private FeesCategories $category;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        DB::table('usergroups')->upsert([
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'name' => 'accountant', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Bursar Balance School',
            'slug' => 'bursar-balance-' . uniqid(),
            'email' => 'bursar-balance-' . uniqid() . '@t.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1,
        ]);

        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
            'status' => 'current',
        ]);

        $this->bursar = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 11,
            'email' => 'bursar.balance@t.sch.ug',
        ]);

        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);

        $section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'Primary One',
            'status' => 1,
        ]);

        $link = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'class_teacher_id' => $this->bursar->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);

        $this->student = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'name' => 'Bursar Balance Pupil',
            'status' => 'active',
        ]);

        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'user_id' => $this->student->id,
            'standardLink_id' => $link->id,
        ]);

        $this->category = FeesCategories::create([
            'school_id' => $this->school->id,
            'standard_id' => $standard->id,
            'name' => 'Tuition',
            'amount' => 500000,
        ]);
    }

    public function test_bursar_payments_page_shows_the_fee_position_strip(): void
    {
        $this->actingAs($this->bursar)
            ->get(route('accountant.fee-payments'))
            ->assertOk()
            ->assertSee('Collected this term')
            ->assertSee('Outstanding')
            ->assertSee('Students in arrears')
            ->assertSee('Collection rate')
            ->assertSee('accountant-fees-kpi-grid', false);
    }

    public function test_recording_a_payment_updates_the_balance_and_shows_the_saved_indicator(): void
    {
        $this->actingAs($this->bursar)
            ->post(route('accountant.fee-payments.store'), [
                'user_id' => $this->student->id,
                'amount' => 50000,
                'fee_category_id' => $this->category->id,
                'payment_method' => 'cash',
                'paid_on' => now()->toDateString(),
            ])
            ->assertRedirect(route('accountant.fee-payments'));

        $this->assertSame(1, FeePayment::where('school_id', $this->school->id)->count());

        $html = $this->actingAs($this->bursar)
            ->get(route('accountant.fee-payments'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Payment recorded', $html);
        $this->assertStringContainsString('UGX 50K', $html, 'collected this term must include the recorded payment');
        $this->assertStringContainsString('UGX 450K', $html, 'outstanding must be due minus paid');
        $this->assertStringContainsString('10%', $html, 'collection rate must reflect the payment');
    }
}
