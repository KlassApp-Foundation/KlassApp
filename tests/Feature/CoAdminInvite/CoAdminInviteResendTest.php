<?php

namespace Tests\Feature\CoAdminInvite;

use App\Mail\CoAdminInviteLinkMail;
use App\Models\CoAdminInvite;
use App\Models\School;
use App\Services\CoAdminInviteLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class CoAdminInviteResendTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->school = School::create([
            'name' => 'CoAdmin Resend School',
            'slug' => 'coadmin-resend',
            'email' => 'school@resend.test',
            'curriculum' => 'uneb',
            'registration_country' => 'Uganda',
            'status' => 'active',
        ]);
    }

    public function test_reissue_invalidates_old_token_and_extends_expiry(): void
    {
        ['invite' => $invite, 'token' => $oldToken] = CoAdminInviteLinkService::issue(
            school: $this->school,
            email: 'resend@resend.test',
            name: 'Resend Target',
        );

        $invite->expires_at = now()->addHour();
        $invite->save();

        ['token' => $newToken] = CoAdminInviteLinkService::reissue($invite);

        $this->assertNotSame($oldToken, $newToken);

        $oldResult = CoAdminInviteLinkService::validateToken($oldToken);
        $this->assertNull($oldResult);

        $newResult = CoAdminInviteLinkService::validateToken($newToken);
        $this->assertNotNull($newResult);
        $this->assertNull($newResult['error']);
        $this->assertTrue($newResult['invite']->expires_at->greaterThan(now()->addHours(70)));
    }

    public function test_reissue_resend_queues_new_link_email(): void
    {
        ['invite' => $invite, 'token' => $oldToken] = CoAdminInviteLinkService::issue(
            school: $this->school,
            email: 'resend2@resend.test',
            name: 'Resend Mail Target',
        );

        CoAdminInviteLinkService::sendEmail($invite, $oldToken, $this->school);

        ['token' => $newToken] = CoAdminInviteLinkService::reissue($invite);
        CoAdminInviteLinkService::sendEmail($invite, $newToken, $this->school);

        Mail::assertQueuedCount(2);
        Mail::assertQueued(CoAdminInviteLinkMail::class, function (CoAdminInviteLinkMail $mail) use ($newToken) {
            return $mail->inviteUrl === url('/invite/co-admin/'.$newToken);
        });
    }

    public function test_pending_lists_only_unclaimed_invites_newest_first(): void
    {
        $claimed = CoAdminInvite::create([
            'school_id' => $this->school->id,
            'email' => 'claimed@resend.test',
            'token_hash' => hash('sha256', Str::random(64)),
            'name' => 'Claimed',
            'expires_at' => now()->addDay(),
            'claimed_at' => now()->subDay(),
        ]);

        CoAdminInviteLinkService::issue($this->school, 'pending-b@resend.test', 'Pending B');
        CoAdminInviteLinkService::issue($this->school, 'pending-a@resend.test', 'Pending A');

        $pending = CoAdminInviteLinkService::pending($this->school);

        $this->assertSame(2, $pending->count());
        $this->assertSame(
            ['pending-a@resend.test', 'pending-b@resend.test'],
            $pending->pluck('email')->all()
        );
        $this->assertNotContains($claimed->id, $pending->pluck('id')->all());
    }

    public function test_pending_is_scoped_to_school(): void
    {
        $otherSchool = School::create([
            'name' => 'Other Resend School',
            'slug' => 'other-resend',
            'email' => 'school2@resend.test',
            'curriculum' => 'uneb',
            'registration_country' => 'Uganda',
            'status' => 'active',
        ]);

        CoAdminInviteLinkService::issue($this->school, 'mine@resend.test', 'Mine');
        CoAdminInviteLinkService::issue($otherSchool, 'theirs@resend.test', 'Theirs');

        $pending = CoAdminInviteLinkService::pending($this->school);

        $this->assertSame(1, $pending->count());
        $this->assertSame('mine@resend.test', $pending->first()->email);
    }
}
