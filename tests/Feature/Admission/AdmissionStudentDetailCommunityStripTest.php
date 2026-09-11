<?php

namespace Tests\Feature\Admission;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Guards the Admission student-detail step after stripping Indian caste-category
 * ("community") — UI-only; DB column retained.
 */
class AdmissionStudentDetailCommunityStripTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    #[Test]
    public function validation_student_detail_succeeds_without_community(): void
    {
        $school = School::query()->create([
            'name' => 'Admission Community Strip School',
            'slug' => 'admission-community-strip',
            'email' => 'admission-community-strip@example.test',
            'status' => 1,
        ]);

        $payload = [
            'name' => 'Amina',
            'lastname' => 'Nakato',
            'date_of_birth' => '2015-03-12',
            'gender' => 'female',
            'identification_marks' => 'Scar on left knee',
            'school_last_studied' => 'Previous Primary',
            'reason_for_leaving' => 'Relocation',
            'permanent_address' => 'Kampala Road 1',
            'address_for_communication' => 'Kampala Road 1',
            'siblings' => 'igottwo',
            'standard_id' => 1,
        ];

        $this->assertArrayNotHasKey('community', $payload);

        $response = $this->post('/'.$school->slug.'/admission-form/validationStudentDetail', $payload);

        $response->assertOk();
        $response->assertSessionDoesntHaveErrors('community');
    }

    #[Test]
    public function validation_student_detail_still_requires_core_fields(): void
    {
        $school = School::query()->create([
            'name' => 'Admission Core Fields School',
            'slug' => 'admission-core-fields',
            'email' => 'admission-core-fields@example.test',
            'status' => 1,
        ]);

        $response = $this->postJson('/'.$school->slug.'/admission-form/validationStudentDetail', [
            'name' => 'Amina',
            'gender' => 'female',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'identification_marks',
            'permanent_address',
            'address_for_communication',
            'siblings',
        ]);
        $response->assertJsonMissingValidationErrors(['community']);
    }
}
