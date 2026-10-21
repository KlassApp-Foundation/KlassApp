<?php

namespace Tests\Feature\Fees;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\FeePayment;
use App\Models\FeesCategories;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Services\FeePositionService;
use App\Services\OutboundWhatsAppService;
use App\Services\StudentReportHelperService;
use App\Services\WhatsAppBusinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A student's fees are the level-wide rows for their standard (a whole-school
 * fee is stored once per level), plus their own section and, for a stream,
 * the base class fee. Other streams and other classes at the same level stay out.
 */
class StudentStreamFeeScopeTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $parent;

    private User $streamed;

    private User $unstreamed;

    private int $streamSectionId;

    private int $unstreamedSectionId;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Stream Fee School',
            'email' => 'stream-fees@test.sch.ug',
            'status' => 1,
        ]);

        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $primary = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);
        $nursery = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'nursery',
            'order' => 0,
            'status' => 1,
        ]);

        $base = $this->section('Primary One');
        $streamA = $this->section('Primary One A');
        $streamB = $this->section('Primary One B');
        $primaryTwo = $this->section('Primary Two');
        $this->streamSectionId = $streamA->id;
        $this->unstreamedSectionId = $primaryTwo->id;

        $this->link($year, $primary, $base);
        $this->link($year, $primary, $streamA);
        $this->link($year, $primary, $streamB);
        $primaryTwoLink = $this->link($year, $primary, $primaryTwo);
        $nurseryLink = $this->link($year, $nursery, $this->section('Baby Class'));

        $this->admin = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'status' => 'active',
        ]);
        $this->parent = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 7,
            'status' => 'active',
            'name' => 'stream-fee-parent',
        ]);

        $this->streamed = $this->learner($year, $this->link($year, $primary, $streamA), 'Amina Stream');
        $this->unstreamed = $this->learner($year, $primaryTwoLink, 'Brian Class');

        // Whole-school fee, stored once per level. Only the student's level counts.
        $this->fee($primary, null, 'Level development', 10000);
        $this->fee($nursery, null, 'Nursery development', 20000);
        // Section-level class fee on the undivided class. Stream students still owe it.
        $this->fee($primary, $base, 'Primary One class fee', 100000);
        $this->fee($primary, $streamA, 'Stream A fee', 5000);
        $this->fee($primary, $streamB, 'Stream B fee', 7000);
        $this->fee($primary, $primaryTwo, 'Primary Two class fee', 80000);
        $this->fee($nursery, $nurseryLink->section, 'Baby class fee', 3000);

        $this->payment($this->streamed, 15000);
        $this->payment($this->unstreamed, 5000);
    }

    public function test_streamed_student_fees_use_own_class_and_stream(): void
    {
        $this->actingAs($this->parent)
            ->get(route('parent.children.fees', $this->streamed->id))
            ->assertOk()
            ->assertSee('Level development')
            ->assertSee('Primary One class fee')
            ->assertSee('Stream A fee')
            ->assertSee('UGX 10,000')
            ->assertSee('UGX 100,000')
            ->assertSee('UGX 5,000')
            ->assertSee('data-testid="parent-fees-total">UGX 100,000', false)
            ->assertSee('Paid to date: UGX 15,000')
            ->assertDontSee('Stream B fee')
            ->assertDontSee('Primary Two class fee')
            ->assertDontSee('Nursery development')
            ->assertDontSee('Baby class fee');

        $balances = collect(app(FeePositionService::class)->studentsWithBalance($this->school->id))->keyBy('id');
        $this->assertSame(100000.0, (float) $balances[$this->streamed->id]['balance']);

        $reportTotal = app(StudentReportHelperService::class)->fees($this->admin, $this->streamSectionId);
        $this->assertSame(115000.0, (float) $reportTotal);

        $lines = $this->reminderLines($this->streamed);
        $this->assertEqualsCanonicalizing([
            '• Level development: UGX 10,000',
            '• Primary One class fee: UGX 100,000',
            '• Stream A fee: UGX 5,000',
        ], $lines);
    }

    public function test_unstreamed_student_fees_use_own_class_only(): void
    {
        $this->actingAs($this->parent)
            ->get(route('parent.children.fees', $this->unstreamed->id))
            ->assertOk()
            ->assertSee('Level development')
            ->assertSee('Primary Two class fee')
            ->assertSee('UGX 10,000')
            ->assertSee('UGX 80,000')
            ->assertSee('data-testid="parent-fees-total">UGX 85,000', false)
            ->assertSee('Paid to date: UGX 5,000')
            ->assertDontSee('Stream A fee')
            ->assertDontSee('Stream B fee')
            ->assertDontSee('Primary One class fee')
            ->assertDontSee('Nursery development')
            ->assertDontSee('Baby class fee');

        $balances = collect(app(FeePositionService::class)->studentsWithBalance($this->school->id))->keyBy('id');
        $this->assertSame(85000.0, (float) $balances[$this->unstreamed->id]['balance']);

        $reportTotal = app(StudentReportHelperService::class)->fees($this->admin, $this->unstreamedSectionId);
        $this->assertSame(90000.0, (float) $reportTotal);

        $lines = $this->reminderLines($this->unstreamed);
        $this->assertEqualsCanonicalizing([
            '• Level development: UGX 10,000',
            '• Primary Two class fee: UGX 80,000',
        ], $lines);
    }

    public function test_streamed_student_tuition_is_counted_once_per_term(): void
    {
        $year = AcademicYear::query()
            ->where('school_id', $this->school->id)
            ->where('status', 1)
            ->firstOrFail();
        $primary = Standard::query()
            ->where('school_id', $this->school->id)
            ->where('name', 'primary')
            ->firstOrFail();
        $stream = Section::query()->findOrFail($this->streamSectionId);

        $term1 = AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'status' => 'current',
            'starts_on' => '2026-02-01',
            'ends_on' => '2026-04-30',
        ]);
        $term2 = AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 2',
            'status' => 'next',
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-08-15',
        ]);

        // Same name, once per term, on the student's own stream. Untied fees from setUp stay.
        FeesCategories::create([
            'school_id' => $this->school->id,
            'standard_id' => $primary->id,
            'section_id' => $stream->id,
            'academic_term_id' => $term1->id,
            'name' => 'Tuition',
            'amount' => 120000,
        ]);
        FeesCategories::create([
            'school_id' => $this->school->id,
            'standard_id' => $primary->id,
            'section_id' => $stream->id,
            'academic_term_id' => $term2->id,
            'name' => 'Tuition',
            'amount' => 45000,
        ]);

        Cache::forget('academic_year_for_school_'.$this->school->id);

        // Current term (Term 1): untied 115,000 + Tuition 120,000 − paid 15,000 = 220,000.
        // Term 2 Tuition (45,000) must not be in this balance. Dropping the untied fees
        // would leave 105,000. Adding both Tuition rows would leave 265,000.
        $page = $this->actingAs($this->parent)
            ->get(route('parent.children.fees', $this->streamed->id));
        $page->assertOk()
            ->assertSee('Tuition')
            ->assertSee('UGX 120,000')
            ->assertSee('Level development')
            ->assertDontSee('UGX 45,000')
            ->assertSee('data-testid="parent-fees-total">UGX 220,000', false);
        $this->assertSame(1, substr_count($page->getContent(), '>Tuition<'));

        $balances = collect(app(FeePositionService::class)->studentsWithBalance($this->school->id))->keyBy('id');
        $this->assertSame(220000.0, (float) $balances[$this->streamed->id]['balance']);

        $helper = app(StudentReportHelperService::class);
        // Term 2 report: untied 115,000 + that term's Tuition 45,000. Term 1 Tuition stays out.
        $this->assertSame(160000.0, (float) $helper->fees($this->admin, $this->streamSectionId, $term2->id));
        // Term 1 report counts its own Tuition once, together with the same untied fees.
        $this->assertSame(235000.0, (float) $helper->fees($this->admin, $this->streamSectionId, $term1->id));
    }

    /**
     * @return list<string>
     */
    private function reminderLines(User $student): array
    {
        $builder = new class(app(WhatsAppBusinessService::class)) extends OutboundWhatsAppService
        {
            /** @var list<string> */
            public array $lines = [];

            protected function composeFeeMessage(User $student, iterable $fees, string $type): string
            {
                foreach ($fees as $fee) {
                    $this->lines[] = '• '.$fee->name.': UGX '.number_format((float) $fee->amount, 0);
                }

                return 'captured';
            }
        };

        $builder->notifyFeeReminder($student->id);

        return $builder->lines;
    }

    private function section(string $name): Section
    {
        return Section::create([
            'school_id' => $this->school->id,
            'name' => $name,
            'status' => 1,
        ]);
    }

    private function link(AcademicYear $year, Standard $standard, Section $section): StandardLink
    {
        return StandardLink::firstOrCreate(
            [
                'school_id' => $this->school->id,
                'academic_year_id' => $year->id,
                'standard_id' => $standard->id,
                'section_id' => $section->id,
            ],
            ['status' => 1],
        );
    }

    private function learner(AcademicYear $year, StandardLink $link, string $name): User
    {
        $student = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'status' => 'active',
            'name' => $name,
        ]);
        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'user_id' => $student->id,
            'standardLink_id' => $link->id,
        ]);
        StudentParentLink::create([
            'school_id' => $this->school->id,
            'parent_id' => $this->parent->id,
            'student_id' => $student->id,
            'status' => 1,
        ]);

        return $student;
    }

    private function fee(Standard $standard, ?Section $section, string $name, float $amount): void
    {
        FeesCategories::create([
            'school_id' => $this->school->id,
            'standard_id' => $standard->id,
            'section_id' => $section?->id,
            'name' => $name,
            'amount' => $amount,
        ]);
    }

    private function payment(User $student, float $amount): void
    {
        FeePayment::create([
            'school_id' => $this->school->id,
            'user_id' => $student->id,
            'amount' => $amount,
            'paid_on' => '2026-03-01',
            'recorded_by' => $this->admin->id,
            'status' => 'paid',
        ]);
    }
}
