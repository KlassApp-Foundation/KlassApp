<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\ActivityLog;
use App\Models\School;
use App\Models\User;
use App\Models\WhatsAppUser;
use App\Services\WhatsApp\WhatsAppLinkCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Support\PostsMetaWhatsApp;
use Tests\TestCase;

/**
 * Linking a WhatsApp number writes an activity row — linked by sending the
 * link code from WhatsApp, unlinked from the profile page.
 */
class LinkWhatsAppActivityLogTest extends TestCase
{
    use PostsMetaWhatsApp;
    use RefreshDatabase;

    private const PHONE = '+256700000123';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        DB::statement('PRAGMA foreign_keys = ON');
        config([
            'app.timezone' => 'Africa/Kampala',
            'services.whatsapp.business_api_token' => 'test-token',
            'services.whatsapp.business_phone_number_id' => '000000',
            'services.whatsapp.replies_enabled' => true,
            'services.whatsapp.demo_only_recipients' => false,
            'services.whatsapp.business_number' => '+256793844906',
        ]);
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.out']]], 200),
        ]);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_linking_and_unlinking_a_number_writes_an_activity_log(): void
    {
        $school = School::create([
            'name' => 'Activity Log Primary',
            'email' => 'activity-log@example.com',
            'phone' => '0700000123',
            'slug' => 'activity-log-primary',
            'status' => 1,
        ]);

        $admin = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Activity Admin',
            'email' => 'admin@activity-log.example',
            'password' => bcrypt('password'),
            'email_verified' => 1,
        ]);
        $admin->refresh();

        $issued = app(WhatsAppLinkCodeService::class)->issue($admin, '203.0.113.5');
        $this->assertTrue($issued['ok']);
        $this->postMetaWhatsAppText(self::PHONE, 'Link KlassApp: '.(string) $issued['code'])->assertOk();

        $waUser = WhatsAppUser::where('user_id', $admin->id)->first();
        $this->assertNotNull($waUser);
        $this->assertSame(self::PHONE, $waUser->phone);

        $linked = ActivityLog::query()->where('description', 'Linked WhatsApp Number')->first();
        $this->assertNotNull($linked);
        $this->assertSame('linkwhatsapp', $linked->log_name);
        $this->assertSame($waUser->id, (int) $linked->subject_id);
        $this->assertSame($waUser->getMorphClass(), $linked->subject_type);
        $this->assertSame($admin->id, (int) $linked->causer_id);
        $this->assertSame($admin->getMorphClass(), $linked->causer_type);
        $this->assertSame(self::PHONE, $linked->properties['phone'] ?? null);
        $this->assertSame($school->id, (int) $linked->school_id);

        $this->actingAs($admin)
            ->post('/admin/whatsapp/phone', ['unlink' => true])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNull(WhatsAppUser::where('user_id', $admin->id)->first());

        $unlinked = ActivityLog::query()->where('description', 'Unlinked WhatsApp Number')->first();
        $this->assertNotNull($unlinked);
        $this->assertSame('unlinkwhatsapp', $unlinked->log_name);
        $this->assertSame($admin->id, (int) $unlinked->subject_id);
        $this->assertSame($admin->getMorphClass(), $unlinked->subject_type);
        $this->assertSame($admin->id, (int) $unlinked->causer_id);
    }
}
