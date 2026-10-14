<?php

namespace Tests\Feature\Demo;

use App\Models\School;
use App\Models\User;
use App\Services\DemoSchoolCommsGuard;
use App\Services\WhatsAppBusinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppRepliesKillSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_kill_switch_stops_replies_as_well_as_business_sends(): void
    {
        Http::fake();
        config(['services.whatsapp.replies_enabled' => false]);

        $school = School::create([
            'name' => 'Kill Switch School',
            'slug' => 'kill-switch-school',
            'email' => 'kill-switch@schools.test',
            'registration_country' => 'Uganda',
            'curriculum' => 'UNEB',
            'status' => 1,
        ]);
        $user = User::create([
            'email' => 'parent.kill@example.com',
            'school_id' => $school->id,
            'usergroup_id' => 7,
            'name' => 'Parent Kill',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        DemoSchoolCommsGuard::beginInboundReply();
        try {
            $reply = app(WhatsAppBusinessService::class)->sendText('+256770009001', 'reply', 'service', $user->id);
        } finally {
            DemoSchoolCommsGuard::endInboundReply();
        }

        $this->assertTrue($reply['blocked'] ?? false);
        $this->assertSame('whatsapp_replies_disabled', $reply['reason'] ?? null);
        Http::assertNothingSent();
    }
}
