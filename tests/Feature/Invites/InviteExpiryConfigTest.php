<?php

namespace Tests\Feature\Invites;

use App\Models\School;
use App\Services\CoAdminInviteLinkService;
use App\Services\TeacherInviteLinkService;
use App\Services\WhatsAppBusinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * One config value (invites.expiry_hours) drives teacher AND co-admin invite
 * link expiry - issue, reissue, and the wording of every derived message.
 */
class InviteExpiryConfigTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        config(['invites.expiry_hours' => 72]);

        $this->school = School::create([
            'name' => 'Invite Expiry School',
            'slug' => 'invite-expiry-school',
            'email' => 'school@inviteexpiry.test',
            'curriculum' => 'uneb',
            'registration_country' => 'Uganda',
            'status' => 'active',
        ]);
    }

    public function test_teacher_issue_defaults_to_72_hours(): void
    {
        ['invite' => $invite] = TeacherInviteLinkService::issue($this->school, 'jane@example.test', 'Jane');

        $this->assertTrue($invite->expires_at->between(now()->addHours(71), now()->addHours(73)));
    }

    public function test_teacher_issue_uses_configured_expiry(): void
    {
        config(['invites.expiry_hours' => 48]);

        ['invite' => $invite] = TeacherInviteLinkService::issue($this->school, 'jane@example.test', 'Jane');

        $this->assertTrue($invite->expires_at->between(now()->addHours(47), now()->addHours(49)));
    }

    public function test_teacher_reissue_uses_configured_expiry(): void
    {
        config(['invites.expiry_hours' => 96]);

        ['invite' => $invite] = TeacherInviteLinkService::issue($this->school, 'jane@example.test', 'Jane');
        ['invite' => $invite] = TeacherInviteLinkService::reissue($invite);

        $this->assertTrue($invite->expires_at->between(now()->addHours(95), now()->addHours(97)));
    }

    public function test_teacher_whatsapp_text_states_configured_hours(): void
    {
        config(['invites.expiry_hours' => 48]);

        ['invite' => $invite] = TeacherInviteLinkService::issue(
            $this->school,
            'jane@example.test',
            'Jane',
            null,
            '+256 700 000 000'
        );

        $this->instance(WhatsAppBusinessService::class, \Mockery::mock(WhatsAppBusinessService::class, function ($m) use ($invite) {
            $m->shouldReceive('sendText')->once()->withArgs(function ($phone, $message) use ($invite) {
                return str_contains($message, '48 hours') && str_contains($message, 'raw-token');
            })->andReturn([]);
        }));

        TeacherInviteLinkService::sendWhatsApp($invite, 'raw-token', $this->school, 'P5 Blue');
    }

    public function test_coadmin_issue_defaults_to_72_hours(): void
    {
        ['invite' => $invite] = CoAdminInviteLinkService::issue($this->school, 'jane@example.test', 'Jane');

        $this->assertTrue($invite->expires_at->between(now()->addHours(71), now()->addHours(73)));
    }

    public function test_coadmin_issue_uses_configured_expiry(): void
    {
        config(['invites.expiry_hours' => 96]);

        ['invite' => $invite] = CoAdminInviteLinkService::issue($this->school, 'jane@example.test', 'Jane');

        $this->assertTrue($invite->expires_at->between(now()->addHours(95), now()->addHours(97)));
    }

    public function test_coadmin_reissue_uses_configured_expiry(): void
    {
        config(['invites.expiry_hours' => 120]);

        ['invite' => $invite] = CoAdminInviteLinkService::issue($this->school, 'jane@example.test', 'Jane');
        ['invite' => $invite] = CoAdminInviteLinkService::reissue($invite);

        $this->assertTrue($invite->expires_at->between(now()->addHours(119), now()->addHours(121)));
    }
}
