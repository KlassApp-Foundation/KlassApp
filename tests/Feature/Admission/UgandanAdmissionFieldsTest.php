<?php

namespace Tests\Feature\Admission;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\School;
use App\Models\SchoolDetail;
use App\Models\Standard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PR 5 - Ugandan admission fields (basic set).
 * Required/optional rules are enforced server-side; class-dependent rules
 * (PLE for S.1 entry, UCE for S.5 entry, previous school for non-nursery/P.1)
 * have their own cases; health fields stay out of admin list views.
 */
class UgandanAdmissionFieldsTest extends TestCase
{
    use RefreshDatabase;

    private string $slug;

    private int $p1Id;

    private int $p5Id;

    private int $s1Id;

    private int $s5Id;

    private int $babyClassId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutVite();

        $school = School::create([
            'name' => 'Ugandan Fields School',
            'slug' => 'ugandan-fields',
            'email' => 'school@ugandanfields.test',
            'curriculum' => 'uneb',
            'registration_country' => 'Uganda',
            'status' => 'active',
        ]);
        $this->slug = $school->refresh()->slug;

        $this->p1Id = Standard::create(['school_id' => $school->id, 'name' => 'P.1', 'order' => 1])->id;
        $this->p5Id = Standard::create(['school_id' => $school->id, 'name' => 'P.5', 'order' => 2])->id;
        $this->s1Id = Standard::create(['school_id' => $school->id, 'name' => 'S.1', 'order' => 3])->id;
        $this->s5Id = Standard::create(['school_id' => $school->id, 'name' => 'S.5', 'order' => 4])->id;
        $this->babyClassId = Standard::create(['school_id' => $school->id, 'name' => 'Baby Class', 'order' => 5])->id;
    }

    // ---------------- step 1: class ----------------

    public function test_class_step_requires_term_and_year(): void
    {
        $response = $this->post('/'.$this->slug.'/admission-form/validationStandard', [
            'standard_id' => $this->p1Id,
        ]);

        $response->assertSessionHasErrors(['entry_term', 'entry_year']);
    }

    public function test_class_step_passes_with_full_entry_details(): void
    {
        $response = $this->post('/'.$this->slug.'/admission-form/validationStandard', [
            'standard_id' => $this->p1Id,
            'entry_term' => '1',
            'entry_year' => '2027',
        ]);

        $response->assertOk();
        $response->assertSessionDoesntHaveErrors(['entry_term', 'entry_year', 'boarding_type']);
    }

    public function test_boarding_is_required_only_when_the_school_offers_it(): void
    {
        // No boarding_available meta: optional.
        $this->post('/'.$this->slug.'/admission-form/validationStandard', [
            'standard_id' => $this->p1Id, 'entry_term' => '1', 'entry_year' => '2027',
        ])->assertSessionDoesntHaveErrors('boarding_type');

        SchoolDetail::updateOrCreate(
            ['school_id' => School::where('slug', $this->slug)->value('id'), 'meta_key' => 'boarding_available'],
            ['meta_value' => '1']
        );

        $this->post('/'.$this->slug.'/admission-form/validationStandard', [
            'standard_id' => $this->p1Id, 'entry_term' => '1', 'entry_year' => '2027',
        ])->assertSessionHasErrors('boarding_type');

        $this->post('/'.$this->slug.'/admission-form/validationStandard', [
            'standard_id' => $this->p1Id, 'entry_term' => '1', 'entry_year' => '2027', 'boarding_type' => 'boarding',
        ])->assertSessionDoesntHaveErrors('boarding_type');
    }

    // ---------------- step 2: student ----------------

    private function studentPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Amina',
            'lastname' => 'Nakato',
            'date_of_birth' => '2015-03-12',
            'gender' => 'female',
            'nationality' => 'Ugandan',
            'home_district' => 'Kampala',
            'village_town' => 'Ntinda',
            'lin' => 'LIN-123',
            'religion' => 'Christian',
            'identification_marks' => 'Scar',
            'permanent_address' => 'Kampala Road 1',
            'address_for_communication' => 'Kampala Road 1',
            'siblings' => 'igottwo',
            'standard_id' => $this->p1Id,
        ], $overrides);
    }

    public function test_student_step_requires_surname_dob_nationality_and_district(): void
    {
        $payload = $this->studentPayload();
        unset($payload['lastname'], $payload['date_of_birth'], $payload['nationality'], $payload['home_district']);

        $response = $this->post('/'.$this->slug.'/admission-form/validationStudentDetail', $payload);

        $response->assertSessionHasErrors(['lastname', 'date_of_birth', 'nationality', 'home_district']);
    }

    public function test_student_step_passes_with_the_full_basic_set(): void
    {
        $response = $this->post('/'.$this->slug.'/admission-form/validationStudentDetail', $this->studentPayload());

        $response->assertOk();
        $response->assertSessionDoesntHaveErrors(['lin', 'religion', 'village_town']);
    }

    public function test_student_step_keeps_lin_religion_and_village_optional(): void
    {
        $payload = $this->studentPayload();
        unset($payload['lin'], $payload['religion'], $payload['village_town']);

        $response = $this->post('/'.$this->slug.'/admission-form/validationStudentDetail', $payload);

        $response->assertOk();
    }

    // ---------------- step 3: academic ----------------

    private function academicPayload(int $standardId, array $overrides = []): array
    {
        return array_merge([
            'standard_id' => $standardId,
            'board_of_education' => 'uneb',
            'choice_of_language' => 'english',
            'school_last_studied' => 'Previous Primary',
            'last_class_completed' => 'P.4',
        ], $overrides);
    }

    public function test_previous_school_optional_for_nursery_and_p1(): void
    {
        foreach ([$this->babyClassId, $this->p1Id] as $id) {
            $payload = $this->academicPayload($id);
            unset($payload['school_last_studied'], $payload['last_class_completed']);

            $this->post('/'.$this->slug.'/admission-form/validationAcademicDetail', $payload)
                ->assertSessionDoesntHaveErrors(['school_last_studied', 'last_class_completed']);
        }
    }

    public function test_previous_school_required_above_p1(): void
    {
        $payload = $this->academicPayload($this->p5Id);
        unset($payload['school_last_studied'], $payload['last_class_completed']);

        $this->post('/'.$this->slug.'/admission-form/validationAcademicDetail', $payload)
            ->assertSessionHasErrors(['school_last_studied', 'last_class_completed']);
    }

    public function test_ple_required_for_s1_entry_only(): void
    {
        // Missing PLE for S.1 -> errors.
        $this->post('/'.$this->slug.'/admission-form/validationAcademicDetail', $this->academicPayload($this->s1Id))
            ->assertSessionHasErrors(['ple_index_number', 'ple_aggregate']);

        // Full PLE -> passes and does not demand UCE.
        $this->post('/'.$this->slug.'/admission-form/validationAcademicDetail', $this->academicPayload($this->s1Id, [
            'ple_index_number' => '012345/067',
            'ple_aggregate' => '12',
        ]))->assertSessionDoesntHaveErrors(['ple_index_number', 'ple_aggregate', 'uce_index_number', 'uce_results_summary']);

        // PLE not required for P.5.
        $this->post('/'.$this->slug.'/admission-form/validationAcademicDetail', $this->academicPayload($this->p5Id))
            ->assertSessionDoesntHaveErrors(['ple_index_number', 'ple_aggregate']);
    }

    public function test_uce_required_for_s5_entry_only(): void
    {
        $this->post('/'.$this->slug.'/admission-form/validationAcademicDetail', $this->academicPayload($this->s5Id))
            ->assertSessionHasErrors(['uce_index_number', 'uce_results_summary']);

        $this->post('/'.$this->slug.'/admission-form/validationAcademicDetail', $this->academicPayload($this->s5Id, [
            'uce_index_number' => 'U1234/567',
            'uce_results_summary' => 'First grades in Maths and Physics',
        ]))->assertSessionDoesntHaveErrors(['uce_index_number', 'uce_results_summary', 'ple_index_number', 'ple_aggregate']);
    }

    // ---------------- step 4: parent or guardian ----------------

    private function parentPayload(array $overrides = []): array
    {
        return array_merge([
            'father_name' => 'John Nakato',
            'father_relationship' => 'Father',
            'father_mobile_no' => '+256 772 123 456',
            'father_district' => 'Kampala',
        ], $overrides);
    }

    public function test_parent_step_primary_contact_is_required(): void
    {
        $this->post('/'.$this->slug.'/admission-form/validationParentDetail', [])
            ->assertSessionHasErrors(['father_name', 'father_relationship', 'father_mobile_no', 'father_district']);
    }

    public function test_parent_step_passes_with_primary_only_and_accepts_ugandan_phone_formats(): void
    {
        $response = $this->post('/'.$this->slug.'/admission-form/validationParentDetail', $this->parentPayload());

        $response->assertOk();
        $response->assertSessionDoesntHaveErrors(['mother_name', 'emergency_contact_1']);
    }

    public function test_parent_step_second_parent_and_emergency_are_optional(): void
    {
        $response = $this->post('/'.$this->slug.'/admission-form/validationParentDetail', $this->parentPayload([
            'mother_name' => 'Mary Nakato',
            'mother_relationship' => 'Mother',
            'mother_mobile_no' => '0772 987654',
            'father_on_whatsapp' => 1,
        ]));

        $response->assertOk();
    }

    // ---------------- step 5: health and support ----------------

    public function test_health_fields_are_optional_and_free_text(): void
    {
        $this->post('/'.$this->slug.'/admission-form/validationPersonalDetail', [])
            ->assertSessionDoesntHaveErrors(['medical_conditions', 'special_needs']);

        $this->post('/'.$this->slug.'/admission-form/validationPersonalDetail', [
            'medical_conditions' => 'Asthma - carries an inhaler.',
            'special_needs' => 'Needs a front-row seat.',
        ])->assertOk();
    }

    public function test_health_fields_do_not_appear_in_admin_admission_views(): void
    {
        foreach (['index.blade.php', 'edit.blade.php'] as $file) {
            $source = file_get_contents(resource_path('views/admin/admission/'.$file));
            $this->assertNotFalse($source);
            foreach (['medical_conditions', 'special_needs'] as $needle) {
                $this->assertStringNotContainsString(
                    $needle,
                    $source,
                    "Admin admission {$file} must not surface health field '{$needle}'"
                );
            }
        }
    }
}
