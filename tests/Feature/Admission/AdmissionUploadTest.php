<?php

namespace Tests\Feature\Admission;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Admission;
use App\Models\School;
use App\Models\Standard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The public admission form's two uploads (passport photo and the birth
 * certificate / baptism card) must survive the whole five step flow into
 * storage: both ride the native multipart submit, land in the school's folder
 * on the public disk, and the row keeps their paths. The birth certificate
 * also honours the 5 MB cap enforced at the student step.
 */
class AdmissionUploadTest extends TestCase
{
    use RefreshDatabase;

    private string $slug;

    private int $schoolId;

    private int $s1Id;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutVite();

        config(['filesystems.default' => 'public']);
        Storage::fake('public');

        $school = School::create([
            'name' => 'Admission Upload School',
            'slug' => 'admission-upload',
            'email' => 'school@admissionupload.test',
            'curriculum' => 'uneb',
            'registration_country' => 'Uganda',
            'status' => 'active',
        ]);
        $this->slug = $school->refresh()->slug;
        $this->schoolId = $school->id;

        User::create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Upload Admin',
            'email' => 'admin@admissionupload.test',
            'password' => Hash::make('Secret!123'),
            'status' => 'active',
        ]);

        $this->s1Id = Standard::create(['school_id' => $school->id, 'name' => 'S.1', 'order' => 1])->id;
    }

    private function fullPayload(array $overrides = []): array
    {
        return array_merge([
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
        ], $overrides);
    }

    private function studentStepPayload(array $overrides = []): array
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
            'standard_id' => $this->s1Id,
        ], $overrides);
    }

    public function test_submission_stores_passport_photo_and_birth_certificate(): void
    {
        $avatar = UploadedFile::fake()->create('passport.jpg', 120, 'image/jpeg');
        $birth = UploadedFile::fake()->create('birth-certificate.pdf', 300, 'application/pdf');

        $response = $this->post('/'.$this->slug.'/admission-form', $this->fullPayload([
            'avatar' => $avatar,
            'birth_certificate' => $birth,
        ]));

        $response->assertRedirect();

        $admission = Admission::first();
        $this->assertNotNull($admission);

        $this->assertNotNull($admission->avatar);
        $this->assertNotNull($admission->birth_certificate);

        $this->assertStringStartsWith($this->schoolId.'/student/avatar/', (string) $admission->avatar);
        $this->assertStringStartsWith($this->schoolId.'/student/documents/', (string) $admission->birth_certificate);

        Storage::disk('public')->assertExists($admission->avatar);
        Storage::disk('public')->assertExists($admission->birth_certificate);
    }

    public function test_submission_without_files_keeps_columns_null(): void
    {
        $this->post('/'.$this->slug.'/admission-form', $this->fullPayload())->assertRedirect();

        $admission = Admission::first();
        $this->assertNotNull($admission);
        $this->assertNull($admission->avatar);
        $this->assertNull($admission->birth_certificate);
    }

    public function test_student_step_accepts_a_small_birth_certificate_file(): void
    {
        $this->post('/'.$this->slug.'/admission-form/validationStudentDetail', $this->studentStepPayload([
            'birth_certificate' => UploadedFile::fake()->create('birth.pdf', 900, 'application/pdf'),
        ]))->assertOk();
    }

    public function test_student_step_rejects_an_oversized_birth_certificate_file(): void
    {
        $response = $this->post('/'.$this->slug.'/admission-form/validationStudentDetail', $this->studentStepPayload([
            'birth_certificate' => UploadedFile::fake()->create('birth.pdf', 6000, 'application/pdf'),
        ]));

        $response->assertSessionHasErrors(['birth_certificate']);
    }
}
