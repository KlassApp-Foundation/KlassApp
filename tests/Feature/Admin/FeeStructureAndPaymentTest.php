<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\FeePayment;
use App\Models\FeesCategories;
use App\Models\School;
use App\Models\SchoolDetail;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Services\FeePositionService;
use App\Services\OnboardingEngine;
use App\Services\StudentFeeResolver;
use App\Services\StudentReportHelperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FeeStructureAndPaymentTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private School $otherSchool;

    private User $admin;

    private User $student;

    private User $otherStudent;

    private Section $primaryOne;

    private Section $primaryTwo;

    private Standard $primary;

    private Standard $nursery;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = $this->school('Fee Structure School', 'fee-structure', '0700000101');
        $this->otherSchool = $this->school('Other Fee School', 'other-fee', '0700000102');

        $year = $this->year($this->school);
        $otherYear = $this->year($this->otherSchool);

        $this->nursery = $this->standard($this->school, 'nursery', 1);
        $this->primary = $this->standard($this->school, 'primary', 2);
        $otherPrimary = $this->standard($this->otherSchool, 'primary', 1);

        $this->primaryOne = $this->section($this->school, 'Primary One');
        $this->primaryTwo = $this->section($this->school, 'Primary Two');
        $otherClass = $this->section($this->otherSchool, 'Primary One');

        $this->link($year, $this->primary, $this->primaryOne);
        $this->link($year, $this->primary, $this->primaryTwo);
        $otherLink = $this->link($otherYear, $otherPrimary, $otherClass);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Structure Admin',
            'email' => 'structure.admin@test.sch.ug',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $this->student = $this->learner($this->school, $year, $this->link($year, $this->primary, $this->primaryOne), 'Amina Kato', 'KLS0000101');
        $this->otherStudent = $this->learner($this->otherSchool, $otherYear, $otherLink, 'Amina Kato', 'KLS0000102');

        $this->withoutMiddleware([
            \App\Http\Middleware\VerifyCsrfToken::class,
            \App\Http\Middleware\MustBeSchoolAdmin::class,
            \App\Http\Middleware\MustBeFullSchoolAdmin::class,
            \App\Http\Middleware\MustBePrivilege::class,
        ]);
    }

    public function test_creating_a_whole_school_fee_makes_one_row_and_termly_is_the_default(): void
    {
        app(OnboardingEngine::class)->saveFees($this->school, [
            ['name' => 'Development', 'amount' => 25000],
            ['name' => 'Uniform', 'amount' => 40000],
            ['name' => 'Transport', 'amount' => 15000],
            ['name' => 'Activity', 'amount' => 10000],
        ]);

        $this->assertSame(4, FeesCategories::where('school_id', $this->school->id)->count());
        $created = FeesCategories::where('school_id', $this->school->id)->get();
        foreach ($created as $fee) {
            $this->assertTrue($fee->applies_school_wide);
            $this->assertNull($fee->standard_id);
            $this->assertNull($fee->section_id);
            $this->assertSame('termly', $fee->duration);
        }

        $this->actingAs($this->admin)
            ->post(route('admin.fees-categories.store'), [
                'name' => 'Tuition',
                'amount' => 100000,
                'applies_to' => 'whole_school',
            ])
            ->assertRedirect(route('admin.fees-categories'));

        $tuition = FeesCategories::where('school_id', $this->school->id)->where('name', 'Tuition')->get();
        $this->assertCount(1, $tuition);
        $this->assertTrue($tuition->first()->applies_school_wide);
        $this->assertSame('termly', $tuition->first()->duration);
        $this->assertSame('tuition', $tuition->first()->fee_type);

        $html = $this->actingAs($this->admin)
            ->get(route('admin.fees-categories'))
            ->assertOk()
            ->getContent();
        $this->assertSame(1, substr_count($html, 'data-name="Tuition"'));
        $this->assertStringContainsString('data-classes="All classes"', $html);
        $this->assertStringContainsString('data-duration="Termly"', $html);
    }

    public function test_legacy_whole_school_copies_stay_and_display_as_one_row(): void
    {
        foreach (['Tuition' => 100000, 'Lunch' => 20000, 'Uniform' => 40000, 'Development' => 25000] as $name => $amount) {
            foreach ([$this->nursery, $this->primary] as $standard) {
                FeesCategories::create([
                    'school_id' => $this->school->id,
                    'standard_id' => $standard->id,
                    'name' => $name,
                    'amount' => $amount,
                    'fee_type' => 'tuition',
                    'duration' => 'termly',
                ]);
            }
        }

        $this->assertSame(8, FeesCategories::where('school_id', $this->school->id)->count());

        $html = $this->actingAs($this->admin)
            ->get(route('admin.fees-categories'))
            ->assertOk()
            ->getContent();

        $this->assertSame(8, FeesCategories::where('school_id', $this->school->id)->count());
        foreach (['Tuition', 'Lunch', 'Uniform', 'Development'] as $name) {
            $this->assertSame(1, substr_count($html, 'data-name="'.$name.'"'), $name.' is one row on the list');
        }
    }

    public function test_per_class_totals_count_a_whole_school_fee_once_for_every_class(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.fees-categories.store'), [
                'name' => 'Tuition',
                'amount' => 100000,
                'applies_to' => 'whole_school',
                'duration' => 'termly',
            ])
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->post(route('admin.fees-categories.store'), [
                'name' => 'Lunch',
                'amount' => 20000,
                'applies_to' => 'classes',
                'section_ids' => [$this->primaryOne->id],
                'duration' => 'termly',
                'fee_type' => 'lunch',
            ])
            ->assertRedirect();

        $html = $this->actingAs($this->admin)
            ->get(route('admin.fees-categories'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-class="Primary One" data-total="120000.00"', $html);
        $this->assertStringContainsString('data-class="Primary Two" data-total="100000.00"', $html);
    }

    public function test_editing_a_fee_changes_its_classes_type_and_duration(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.fees-categories.store'), [
                'name' => 'Tuition',
                'amount' => 100000,
                'applies_to' => 'whole_school',
            ])
            ->assertRedirect();

        $fee = FeesCategories::where('school_id', $this->school->id)->where('name', 'Tuition')->firstOrFail();

        $this->actingAs($this->admin)
            ->patch(route('admin.fees-categories.update', $fee->id), [
                'name' => 'Tuition',
                'amount' => 100000,
                'fee_type' => 'lunch',
                'duration' => 'yearly',
                'applies_to' => 'classes',
                'section_ids' => [$this->primaryOne->id, $this->primaryTwo->id],
            ])
            ->assertRedirect(route('admin.fees-categories'));

        $fee->refresh();
        $this->assertFalse($fee->applies_school_wide);
        $this->assertSame('lunch', $fee->fee_type);
        $this->assertSame('yearly', $fee->duration);
        $this->assertEqualsCanonicalizing(
            [$this->primaryOne->id, $this->primaryTwo->id],
            $fee->classes()->pluck('sections.id')->map(fn ($id) => (int) $id)->all()
        );
    }

    public function test_recording_a_payment_with_no_category_reduces_the_student_balance(): void
    {
        FeesCategories::create([
            'school_id' => $this->school->id,
            'name' => 'Tuition',
            'amount' => 10000,
            'duration' => 'termly',
            'applies_school_wide' => true,
            'fee_type' => 'tuition',
        ]);

        $before = $this->balance($this->student->id);
        $this->assertSame(10000.0, $before);

        $this->actingAs($this->admin)
            ->post(route('admin.fee-payments.store'), [
                'user_id' => $this->student->id,
                'amount' => 4000,
                'payment_method' => 'cash',
            ])
            ->assertRedirect(route('admin.fee-payments'));

        $payment = FeePayment::where('user_id', $this->student->id)->first();
        $this->assertNotNull($payment);
        $this->assertNull($payment->fee_category_id);
        $this->assertSame($this->admin->id, (int) $payment->recorded_by);
        $this->assertStringContainsString('School fees', $payment->reminderMessage());
        $this->assertSame(6000.0, $this->balance($this->student->id));

        $category = FeesCategories::where('school_id', $this->school->id)->first();
        $this->actingAs($this->admin)
            ->post(route('admin.fee-payments.store'), [
                'user_id' => $this->student->id,
                'amount' => 1000,
                'fee_category_id' => $category->id,
                'payment_method' => 'cash',
            ])
            ->assertRedirect(route('admin.fee-payments'));

        $allocated = FeePayment::where('user_id', $this->student->id)->where('amount', 1000)->first();
        $this->assertSame($category->id, (int) $allocated->fee_category_id);
        $this->assertSame(5000.0, $this->balance($this->student->id));
    }

    public function test_a_class_fee_with_the_same_name_warns_and_is_added_on_top(): void
    {
        SchoolDetail::create([
            'school_id' => $this->school->id,
            'meta_key' => 'currency',
            'meta_value' => 'UGX',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.fees-categories.store'), [
                'name' => 'Tuition',
                'amount' => 100000,
                'applies_to' => 'whole_school',
                'duration' => 'termly',
            ])
            ->assertRedirect(route('admin.fees-categories'));

        $seniorOne = $this->section($this->school, 'Senior One');
        $this->link($this->yearFor($this->school), $this->primary, $seniorOne);

        $this->actingAs($this->admin)
            ->followingRedirects()
            ->post(route('admin.fees-categories.store'), [
                'name' => 'Tuition',
                'amount' => 50000,
                'applies_to' => 'classes',
                'section_ids' => [$seniorOne->id],
                'duration' => 'termly',
            ])
            ->assertOk()
            ->assertSee('data-testid="fee-structure-warning"', false)
            ->assertSee('Tuition already exists as a whole-school fee (UGX 100,000). This class fee will be added on top, so Senior One pays UGX 150,000 in total.', false)
            ->assertSee('data-class="Senior One" data-total="150000.00"', false)
            ->assertSee('data-class="Primary One" data-total="100000.00"', false);

        $rows = FeesCategories::query()
            ->where('school_id', $this->school->id)
            ->where('name', 'Tuition')
            ->get();
        $this->assertCount(2, $rows);
        $classFee = $rows->first(fn (FeesCategories $fee) => ! $fee->applies_school_wide);
        $this->assertNotNull($classFee);
        $this->assertSame(50000.0, (float) $classFee->amount);
        $this->assertSame($seniorOne->id, (int) $classFee->section_id);
    }

    public function test_yearly_fee_counts_once_in_its_due_term_and_the_year_total(): void
    {
        [$first, $current, $third] = $this->makeTerms();
        $parent = $this->parentLinkedToStudent();

        FeesCategories::create([
            'school_id' => $this->school->id,
            'name' => 'Development',
            'amount' => 90000,
            'duration' => 'yearly',
            'applies_school_wide' => true,
            'fee_type' => 'development',
        ]);
        FeesCategories::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->primary->id,
            'section_id' => $this->primaryOne->id,
            'name' => 'Class fee',
            'amount' => 10000,
            'duration' => 'termly',
            'fee_type' => 'other',
        ]);

        $resolver = app(StudentFeeResolver::class);
        $due = $resolver->forStudent($this->student, $this->school->id, $first->id);
        $this->assertTrue($due->contains(fn (FeesCategories $fee) => $fee->name === 'Development'));
        $this->assertSame(90000.0, (float) $due->where('name', 'Development')->sum('amount'));
        $this->assertFalse(
            $resolver->forStudent($this->student, $this->school->id, $current->id)->contains(fn (FeesCategories $fee) => $fee->name === 'Development')
        );
        $this->assertFalse(
            $resolver->forStudent($this->student, $this->school->id, $third->id)->contains(fn (FeesCategories $fee) => $fee->name === 'Development')
        );

        $year = $resolver->forYear($this->student, $this->school->id);
        $this->assertCount(1, $year->where('name', 'Development'));
        $this->assertSame(90000.0, (float) $year->where('name', 'Development')->sum('amount'));
        // Class fee is termly with no term, so it is charged in each of the three terms.
        // Development is not: 90,000 x 3 + 10,000 x 3 would be 300,000.
        $this->assertSame(120000.0, (float) $year->sum(fn (FeesCategories $fee) => (float) $fee->amount));

        $this->assertSame(100000.0, $this->balanceForTerm($first->id));
        $this->assertSame(10000.0, $this->balanceForTerm($current->id));
        $this->assertSame(10000.0, $this->balanceForTerm($third->id));
        $this->assertSame(10000.0, $this->balanceForTerm(null));

        $report = app(StudentReportHelperService::class);
        $this->assertSame(100000.0, (float) $report->fees($this->admin, $this->primaryOne->id, $first->id));
        $this->assertSame(10000.0, (float) $report->fees($this->admin, $this->primaryOne->id, $current->id));

        $html = $this->actingAs($this->admin)
            ->get(route('admin.fees-categories'))
            ->assertOk()
            ->getContent();
        $this->assertSame(1, substr_count($html, 'data-name="Development"'));
        $this->assertStringContainsString('data-name="Development" data-duration="Yearly" data-classes="All classes" data-due="Term 1"', $html);
        $this->assertStringContainsString('data-class="Primary One" data-total="100000.00" data-year-total="120000.00"', $html);
        $this->assertStringContainsString('data-class="Primary Two" data-total="90000.00" data-year-total="90000.00"', $html);

        $this->actingAs($parent)
            ->get(route('parent.children.fees', $this->student->id))
            ->assertOk()
            ->assertSee('Class fee')
            ->assertDontSee('Development')
            ->assertSee('data-testid="parent-fees-total">UGX 10,000', false)
            ->assertSee('Year total: UGX 120,000', false);
    }

    public function test_yearly_fee_with_a_chosen_term_is_due_only_in_that_term(): void
    {
        [$first, $current, $third] = $this->makeTerms();

        $trip = FeesCategories::create([
            'school_id' => $this->school->id,
            'name' => 'Trip',
            'amount' => 7000,
            'duration' => 'yearly',
            'academic_term_id' => $third->id,
            'applies_school_wide' => true,
            'fee_type' => 'other',
        ]);

        $resolver = app(StudentFeeResolver::class);
        $this->assertSame($third->id, $resolver->dueTermId($trip, $this->school->id));
        $this->assertFalse(
            $resolver->forStudent($this->student, $this->school->id, $first->id)->contains(fn (FeesCategories $fee) => $fee->name === 'Trip')
        );
        $this->assertFalse(
            $resolver->forStudent($this->student, $this->school->id, $current->id)->contains(fn (FeesCategories $fee) => $fee->name === 'Trip')
        );
        $this->assertTrue(
            $resolver->forStudent($this->student, $this->school->id, $third->id)->contains(fn (FeesCategories $fee) => $fee->name === 'Trip')
        );

        $year = $resolver->forYear($this->student, $this->school->id);
        $this->assertCount(1, $year->where('name', 'Trip'));
        $this->assertSame(7000.0, (float) $year->sum(fn (FeesCategories $fee) => (float) $fee->amount));
        $this->assertNotSame(21000.0, (float) $year->sum(fn (FeesCategories $fee) => (float) $fee->amount));

        $html = $this->actingAs($this->admin)
            ->get(route('admin.fees-categories'))
            ->assertOk()
            ->getContent();
        $this->assertSame(1, substr_count($html, 'data-name="Trip"'));
        $this->assertStringContainsString('data-due="Term 3"', $html);
        $this->assertStringContainsString('data-class="Primary One" data-total="7000.00" data-year-total="7000.00"', $html);
    }

    public function test_student_search_returns_the_matching_student_and_stays_in_the_school(): void
    {
        $byName = $this->actingAs($this->admin)
            ->getJson(route('admin.fee-payments.students', ['q' => 'Amina']))
            ->assertOk()
            ->json('students');
        $this->assertCount(1, $byName);
        $this->assertSame($this->student->id, $byName[0]['id']);
        $this->assertSame('KLS0000101', $byName[0]['admission_number']);

        $byAdmission = $this->actingAs($this->admin)
            ->getJson(route('admin.fee-payments.students', ['q' => 'KLS0000101']))
            ->assertOk()
            ->json('students');
        $this->assertSame([$this->student->id], array_column($byAdmission, 'id'));

        $byClass = $this->actingAs($this->admin)
            ->getJson(route('admin.fee-payments.students', ['q' => 'Primary One']))
            ->assertOk()
            ->json('students');
        $this->assertSame([$this->student->id], array_column($byClass, 'id'));
        $this->assertNotContains($this->otherStudent->id, array_column($byClass, 'id'));
    }

    private function balance(int $studentId): float
    {
        return $this->balanceForTerm(null, $studentId);
    }

    private function balanceForTerm(?int $termId, ?int $studentId = null): float
    {
        $filters = $termId ? ['academic_term_id' => $termId] : [];
        $rows = collect(app(FeePositionService::class)->studentsWithBalance($this->school->id, $filters))->keyBy('id');

        return (float) ($rows[$studentId ?? $this->student->id]['balance'] ?? 0);
    }

    /**
     * @return array{0: AcademicTerm, 1: AcademicTerm, 2: AcademicTerm}
     */
    private function makeTerms(): array
    {
        $year = $this->yearFor($this->school);
        // Insert the later terms first so "first" is the earliest start, not the lowest id or the current term.
        $current = AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 2',
            'status' => 'current',
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-08-15',
        ]);
        $third = AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 3',
            'status' => 'next',
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-11-30',
        ]);
        $first = AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'status' => 'last',
            'starts_on' => '2026-02-01',
            'ends_on' => '2026-04-30',
        ]);

        return [$first, $current, $third];
    }

    private function yearFor(School $school): AcademicYear
    {
        return AcademicYear::query()
            ->where('school_id', $school->id)
            ->where('status', 1)
            ->firstOrFail();
    }

    private function parentLinkedToStudent(): User
    {
        DB::table('usergroups')->insert([
            'id' => 7,
            'name' => 'parent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $parent = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 7,
            'name' => 'Fee Parent',
            'email' => 'fee.parent@test.sch.ug',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        StudentParentLink::create([
            'school_id' => $this->school->id,
            'parent_id' => $parent->id,
            'student_id' => $this->student->id,
            'status' => 1,
        ]);

        return $parent;
    }

    private function school(string $name, string $slug, string $phone): School
    {
        return School::create([
            'name' => $name,
            'email' => $slug.'@test.sch.ug',
            'phone' => $phone,
            'slug' => $slug,
            'status' => 1,
        ]);
    }

    private function year(School $school): AcademicYear
    {
        return AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
    }

    private function standard(School $school, string $name, int $order): Standard
    {
        return Standard::create([
            'school_id' => $school->id,
            'name' => $name,
            'order' => $order,
            'status' => 1,
        ]);
    }

    private function section(School $school, string $name): Section
    {
        return Section::create([
            'school_id' => $school->id,
            'name' => $name,
            'status' => 1,
        ]);
    }

    private function link(AcademicYear $year, Standard $standard, Section $section): StandardLink
    {
        return StandardLink::firstOrCreate(
            [
                'school_id' => $standard->school_id,
                'academic_year_id' => $year->id,
                'standard_id' => $standard->id,
                'section_id' => $section->id,
            ],
            ['status' => 1]
        );
    }

    private function learner(School $school, AcademicYear $year, StandardLink $link, string $name, string $admission): User
    {
        $student = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'.'.$school->id.'@test.sch.ug',
            'password' => Hash::make('password'),
            'status' => 'active',
            'registration_number' => $admission,
        ]);
        StudentAcademic::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'user_id' => $student->id,
            'standardLink_id' => $link->id,
        ]);

        return $student;
    }
}
