<?php

namespace Tests\Feature\WhatsApp;

use App\Models\School;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Models\WhatsAppUser;
use App\Services\OutboundWhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A parent is only reachable on WhatsApp through a linked, opted-in
 * WhatsAppUser row — never through the phone on their profile.
 */
class ParentPhonesLinkedOnlyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_profile_phone_without_a_whatsapp_link_is_not_returned(): void
    {
        $school = School::create([
            'name' => 'Phone Fallback School',
            'slug' => 'phone-fallback-school',
            'email' => 'phone-fallback@schools.test',
            'registration_country' => 'Uganda',
            'curriculum' => 'UNEB',
            'status' => 1,
        ]);

        $student = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'name' => 'Pupil One',
            'email' => 'pupil-one@schools.test',
            'password' => bcrypt('x'),
            'status' => 'active',
        ]);

        $parent = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 7,
            'name' => 'Parent One',
            'email' => 'parent-one@schools.test',
            'mobile_no' => '+256700111222',
            'password' => bcrypt('x'),
            'status' => 'active',
        ]);

        StudentParentLink::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'parent_id' => $parent->id,
            'status' => 1,
        ]);

        $phones = app(OutboundWhatsAppService::class)->getParentPhones($student->fresh());
        $this->assertSame([], $phones);

        WhatsAppUser::create([
            'phone' => '+256700111222',
            'user_id' => $parent->id,
            'school_id' => $school->id,
            'opted_in' => true,
        ]);

        $this->assertSame(['+256700111222'], app(OutboundWhatsAppService::class)->getParentPhones($student->fresh()));
    }
}
