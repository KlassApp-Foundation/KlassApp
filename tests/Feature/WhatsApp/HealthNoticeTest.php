<?php

namespace Tests\Feature\WhatsApp;

use App\Models\School;
use App\Models\StudentHealthIncident;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Models\WhatsAppPendingNotification;
use App\Models\WhatsAppUser;
use App\Services\OutboundWhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\PostsMetaWhatsApp;
use Tests\TestCase;

/**
 * A health notice never carries clinical detail until the parent asks for it
 * from their linked number.
 */
class HealthNoticeTest extends TestCase
{
    use PostsMetaWhatsApp;
    use RefreshDatabase;

    private School $school;

    private User $parent;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => 'Health Notice School',
            'slug' => 'health-notice-school',
            'email' => 'health-notice@schools.test',
            'registration_country' => 'Uganda',
            'curriculum' => 'UNEB',
            'status' => 1,
        ]);

        $this->student = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'name' => 'Sick Pupil',
            'email' => 'sick-pupil@schools.test',
            'password' => bcrypt('x'),
            'status' => 'active',
        ]);

        $this->parent = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 7,
            'name' => 'Parent Of Sick Pupil',
            'email' => 'sick-parent@schools.test',
            'password' => bcrypt('x'),
            'status' => 'active',
        ]);

        StudentParentLink::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'parent_id' => $this->parent->id,
            'status' => 1,
        ]);

        WhatsAppUser::create([
            'phone' => '+256770444555',
            'user_id' => $this->parent->id,
            'school_id' => $this->school->id,
            'opted_in' => true,
            'verified_at' => now(),
        ]);
    }

    public function test_the_notice_queues_no_clinical_detail(): void
    {
        $incident = StudentHealthIncident::create([
            'school_id' => $this->school->id,
            'user_id' => $this->student->id,
            'incident_date' => now()->toDateString(),
            'description' => 'Deep cut on the left hand',
            'action_taken' => 'Cleaned and bandaged at the clinic',
            'severity' => 'moderate',
            'recorded_by' => $this->parent->id,
        ]);

        app(OutboundWhatsAppService::class)->notifyHealthIncident($this->student->fresh(), $incident);

        $queued = WhatsAppPendingNotification::where('flow_type', 'health_notice')->get();
        $this->assertCount(1, $queued);
        $body = (string) $queued->first()->message;
        $this->assertStringContainsString('Reply *HEALTH*', $body);
        $this->assertStringNotContainsString('Deep cut', $body);
        $this->assertStringNotContainsString('bandaged', $body);
    }

    public function test_replying_health_from_the_linked_number_sends_the_detail(): void
    {
        StudentHealthIncident::create([
            'school_id' => $this->school->id,
            'user_id' => $this->student->id,
            'incident_date' => now()->toDateString(),
            'description' => 'Deep cut on the left hand',
            'action_taken' => 'Cleaned and bandaged at the clinic',
            'severity' => 'moderate',
            'recorded_by' => $this->parent->id,
        ]);

        $this->postMetaWhatsAppText('+256770444555', 'HEALTH')->assertOk();

        Http::assertSent(function ($request) {
            $body = json_encode($request->data());

            return str_contains($body, 'Deep cut on the left hand');
        });
    }
}
