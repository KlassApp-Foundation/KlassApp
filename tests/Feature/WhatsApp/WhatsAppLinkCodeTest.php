<?php

namespace Tests\Feature\WhatsApp;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Country;
use App\Models\Plan;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppLinkCode;
use App\Models\WhatsAppUser;
use App\Services\WhatsApp\WhatsAppLinkCodeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Support\PostsMetaWhatsApp;
use Tests\TestCase;

/**
 * The person never types their number. They send a short code from WhatsApp
 * and the number that message arrives from is the one that gets linked.
 */
class WhatsAppLinkCodeTest extends TestCase
{
    use PostsMetaWhatsApp;
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('PRAGMA foreign_keys = ON');
        config([
            'app.timezone' => 'Africa/Kampala',
            'services.whatsapp.business_api_token' => 'test-token',
            'services.whatsapp.business_phone_number_id' => '000000',
            'services.whatsapp.replies_enabled' => true,
            'services.whatsapp.demo_only_recipients' => false,
            'services.whatsapp.business_number' => '+256793844906',
            'services.whatsapp.enforce_link_limits_in_tests' => false,
        ]);
        Carbon::setTestNow(Carbon::parse('2026-10-23 12:00:00', 'Africa/Kampala'));
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.out']]], 200),
        ]);

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBePrivilege::class);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Country::create([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'status' => 1,
            'order' => 1,
        ]);

        $this->school = School::create([
            'name' => 'Link Academy',
            'email' => 'link@test.sch.ug',
            'phone' => '0700000099',
            'slug' => 'link-academy',
            'status' => 1,
            'curriculum' => 'uneb',
            'registration_country' => 'Uganda',
            'toshi_enabled' => 0,
        ]);
        $this->school->forceFill(['is_demo' => 0, 'is_test' => 0])->save();

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Link Admin',
            'email' => 'admin@link.sch.ug',
            'password' => bcrypt('password'),
            'email_verified' => 1,
        ]);
        $this->admin->refresh();

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Link',
            'lastname' => 'Admin',
        ]);

        Plan::create([
            'name' => 'Freemium',
            'display_name' => 'Freemium',
            'cycle' => 30,
            'no_of_students' => 0,
            'no_of_users' => 0,
            'amount' => 0,
            'order' => 1,
            'is_active' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_the_phone_page_shows_a_code_a_qr_and_the_number_as_text(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/whatsapp/phone');

        $response->assertOk();
        $response->assertSee('wa-link-code', false);
        $response->assertSee('wa-link-open', false);
        $response->assertSee('wa-link-qr', false);
        $response->assertSee('+256 793 844 906', false);
        $response->assertSee('Link KlassApp: ', false);
        $response->assertSee('Open WhatsApp');

        $response->assertDontSee('256XXXXXXXXX');
        $response->assertDontSee('(Uganda)');

        $code = WhatsAppLinkCode::query()->first();
        $this->assertNotNull($code);
        $this->assertStringContainsString('wa.me/256793844906', $response->getContent());
        $this->assertStringContainsString('Link%20KlassApp%3A', $response->getContent());
    }

    public function test_the_code_is_six_safe_characters_and_is_stored_as_a_hash(): void
    {
        $issued = $this->service()->issue($this->admin, '203.0.113.5');

        $this->assertTrue($issued['ok']);
        $this->assertMatchesRegularExpression('/^[ABCDEFGHJKMNPQRSTUVWXYZ23456789]{6}$/', (string) $issued['code']);
        $this->assertDoesNotMatchRegularExpression('/[01ILO]/', (string) $issued['code']);

        $row = WhatsAppLinkCode::query()->first();
        $this->assertNotNull($row);
        $this->assertSame($this->school->id, (int) $row->school_id);
        $this->assertSame($this->admin->id, (int) $row->user_id);
        $this->assertNotSame($issued['code'], $row->code_hash);
        $this->assertStringNotContainsString((string) $issued['code'], $row->code_hash);
        $this->assertSame(hash_hmac('sha256', (string) $issued['code'], (string) config('app.key')), $row->code_hash);
        $this->assertSame(
            Carbon::parse('2026-10-23 12:10:00', 'Africa/Kampala')->utc()->getTimestamp(),
            $row->expires_at->utc()->getTimestamp()
        );
    }

    public function test_the_message_links_whatever_number_it_arrives_from(): void
    {
        $issued = $this->service()->issue($this->admin, '203.0.113.5');

        $this->postMetaWhatsAppText('+256771234567', 'Link KlassApp: '.(string) $issued['code'])->assertOk();

        $wa = WhatsAppUser::query()->where('user_id', $this->admin->id)->first();
        $this->assertNotNull($wa);
        $this->assertSame('+256771234567', $wa->phone);
        $this->assertSame($this->school->id, (int) $wa->school_id);
        $this->assertTrue((bool) $wa->opted_in);
        $this->assertNotNull($wa->verified_at);
    }

    public function test_a_number_already_linked_to_someone_else_is_not_taken_over(): void
    {
        WhatsAppUser::create([
            'school_id' => $this->school->id,
            'user_id' => 999,
            'phone' => '+256771234567',
            'opted_in' => true,
            'verified_at' => now(),
        ]);

        $issued = $this->service()->issue($this->admin, '203.0.113.5');
        $this->postMetaWhatsAppText('+256771234567', 'Link KlassApp: '.(string) $issued['code'])->assertOk();

        $this->assertNull(WhatsAppUser::query()->where('user_id', $this->admin->id)->first());
        $this->assertSame(999, (int) WhatsAppUser::query()->where('phone', '+256771234567')->first()->user_id);
    }

    public function test_an_expired_code_links_nothing(): void
    {
        $issued = $this->service()->issue($this->admin, '203.0.113.5');

        Carbon::setTestNow(Carbon::parse('2026-10-23 12:11:00', 'Africa/Kampala'));
        $this->postMetaWhatsAppText('+256771234567', 'Link KlassApp: '.(string) $issued['code'])->assertOk();

        $this->assertNull(WhatsAppUser::query()->where('user_id', $this->admin->id)->first());
    }

    public function test_a_second_code_stops_the_first_one_working(): void
    {
        $first = $this->service()->issue($this->admin, '203.0.113.5');
        $second = $this->service()->issue($this->admin, '203.0.113.5');

        $this->postMetaWhatsAppText('+256771234567', 'Link KlassApp: '.(string) $first['code'])->assertOk();
        $this->assertNull(WhatsAppUser::query()->where('user_id', $this->admin->id)->first());

        $this->postMetaWhatsAppText('+256779999999', 'Link KlassApp: '.(string) $second['code'])->assertOk();
        $this->assertSame(
            '+256779999999',
            (string) WhatsAppUser::query()->where('user_id', $this->admin->id)->value('phone')
        );
    }

    public function test_posting_a_phone_number_no_longer_links_anything(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/whatsapp/phone', ['phone' => '+256771234567'])
            ->assertRedirect();

        $this->assertNull(WhatsAppUser::query()->where('user_id', $this->admin->id)->first());
    }

    public function test_the_status_endpoint_reports_when_the_number_is_linked(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/admin/whatsapp/phone/status')
            ->assertOk()
            ->assertJson(['linked' => false, 'verified' => false]);

        $issued = $this->service()->issue($this->admin, '203.0.113.5');
        $this->postMetaWhatsAppText('+256771234567', 'Link KlassApp: '.(string) $issued['code'])->assertOk();

        $this->actingAs($this->admin->fresh())
            ->getJson('/admin/whatsapp/phone/status')
            ->assertOk()
            ->assertJson(['linked' => true, 'verified' => true]);
    }

    public function test_rate_limits_are_applied_when_they_are_switched_on(): void
    {
        config(['services.whatsapp.enforce_link_limits_in_tests' => true]);

        $ok = true;
        for ($i = 0; $i < 11; $i++) {
            $result = $this->service()->issue($this->admin, '203.0.113.5');
            $ok = $ok && ($result['ok'] ?? false);
        }

        $this->assertFalse($ok);

        RateLimiter::clear('wa-link:user:'.$this->admin->id);
    }

    private function service(): WhatsAppLinkCodeService
    {
        return app(WhatsAppLinkCodeService::class);
    }
}
