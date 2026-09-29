<?php

namespace Tests\Feature\Admission;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Admission;
use App\Models\School;
use App\Models\Standard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The public admission form must save end to end with the Ugandan basic set:
 * the legacy NOT NULL columns the form no longer fills (height, weight, the
 * second emergency contact) must not block the insert, and the new fields
 * must persist.
 */
class AdmissionSubmissionStoreTest extends TestCase
{
    use RefreshDatabase;

    private string $slug;

    private int $s1Id;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutVite();

        $school = School::create([
            'name' => 'Submission Store School',
            'slug' => 'submission-store',
            'email' => 'school@submissionstore.test',
            'curriculum' => 'uneb',
            'registration_country' => 'Uganda',
            'status' => 'active',
        ]);
        $this->slug = $school->refresh()->slug;

        User::create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Store Admin',
            'email' => 'admin@submissionstore.test',
            'password' => Hash::make('Secret!123'),
            'status' => 'active',
        ]);

        $this->s1Id = Standard::create(['school_id' => $school->id, 'name' => 'S.1', 'order' => 1])->id;
    }

    public function test_full_submission_saves_with_the_basic_set(): void
    {
        $response = $this->post('/'.$this->slug.'/admission-form', [
            'standard_id' => $this->s1Id,
            'entry_term' => '1',
            'entry_year' => '2027',
            'boarding_type' => 'boarding',
            'name' => 'Amina',
            'lastname' => 'Testchild',
            'date_of_birth' => '2014-05-01',
            'gender' => 'female',
            'nationality' => 'Ugandan',
            'home_district' => 'Kampala',
            'village_town' => 'Ntinda',
            'lin' => 'LIN-0001',
            'religion' => 'Christian',
            'identification_marks' => 'Scar on left knee',
            'permanent_address' => '12 Kampala Road',
            'address_for_communication' => '12 Kampala Road',
            'siblings' => 'igottwo',
            'reason_for_leaving' => '',
            'mother_name' => '',
            'father_occupation' => '',
            'emergency_contact_1' => '',
            'relation_with_student_1' => '',
            'english' => '70',
            'maths' => '80',
            'science' => '75',
            'social' => '65',
            'school_last_studied' => 'Demo Primary School',
            'last_class_completed' => 'P.7',
            'ple_index_number' => '012345/067',
            'ple_aggregate' => '12',
            'board_of_education' => 'uneb',
            'choice_of_language' => 'french',
            'father_name' => 'Demo Parent',
            'father_relationship' => 'Father',
            'father_mobile_no' => '+256 700 000 111',
            'father_on_whatsapp' => '1',
            'father_district' => 'Kampala',
            'medical_conditions' => 'Asthma - carries an inhaler.',
            'special_needs' => 'Needs a front-row seat.',
        ]);

        $response->assertRedirect();

        $this->assertSame(1, Admission::count());
        $admission = Admission::first();

        $this->assertSame('Amina', $admission->name);
        $this->assertSame('Testchild', $admission->lastname);
        $this->assertSame('1', (string) $admission->entry_term);
        $this->assertSame('2027', (string) $admission->entry_year);
        $this->assertSame('boarding', $admission->boarding_type);
        $this->assertSame('Ugandan', $admission->nationality);
        $this->assertSame('Kampala', $admission->home_district);
        $this->assertSame('012345/067', $admission->ple_index_number);
        $this->assertSame('Father', $admission->father_relationship);
        $this->assertTrue((bool) $admission->father_on_whatsapp);
        $this->assertSame('Asthma - carries an inhaler.', $admission->medical_conditions);
        $this->assertSame('Needs a front-row seat.', $admission->special_needs);
        $this->assertSame('unpaid', $admission->payment_status);
        $this->assertNotNull($admission->remarks);
        $this->assertSame('Draft', $admission->application_status);

        $marks = json_decode((string) $admission->half_yearly_mark_details, true);
        $this->assertSame('70', $marks['english'] ?? null);
    }
}
