<?php

namespace Tests\Feature\WhatsAppDemo;

use App\Models\School;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Models\WhatsAppUser;
use App\Services\DemoSchoolCommsGuard;
use App\Services\WhatsAppReportCardDeliveryService;
use Database\Seeders\DemoJuniorSchoolSeeder;
use Database\Seeders\UsergroupTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

/**
 * Task 3: on a demo school, replies to the person's own message go out
 * (Try Demo, the parent menu, REPORT and its PDF), business-initiated sends
 * (fee reminders) do not, and WHATSAPP_REPLIES_ENABLED=false stops every
 * outbound WhatsApp message. Drives the real Meta webhook route with the
 * Graph API faked. Foreign keys on, non-UTC timezone.
 */
class WhatsAppDemoFlowTest extends TestCase
{
    use RefreshDatabase;

    private string $stranger = '+256700104477';

    private User $demoParent;

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('PRAGMA foreign_keys = ON');
        config(['app.timezone' => 'Africa/Kampala']);
        date_default_timezone_set('Africa/Kampala');
        Cache::flush();
        DemoSchoolCommsGuard::flushCache();

        $this->looseDeliveryLogForSqlite();
        $this->seed(UsergroupTableSeeder::class);
        $this->seed(DemoJuniorSchoolSeeder::class);

        $school = School::where('is_demo', 1)->firstOrFail();
        $this->demoParent = User::where('school_id', $school->id)
            ->where('usergroup_id', 7)
            ->whereIn('id', StudentParentLink::where('status', 1)->pluck('parent_id'))
            ->orderBy('id')
            ->firstOrFail();

        config([
            'services.whatsapp.verify_signature' => false,
            'services.whatsapp.business_api_token' => 'test-token',
            'services.whatsapp.business_phone_number_id' => '1000000001',
            'services.whatsapp.demo_parent_user_id' => $this->demoParent->id,
            'services.whatsapp.replies_enabled' => true,
        ]);

        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.test']], 'id' => 'media-1'], 200)]);
    }

    protected function tearDown(): void
    {
        date_default_timezone_set('UTC');
        DemoSchoolCommsGuard::flushCache();
        Mockery::close();
        parent::tearDown();
    }

    /**
     * The SQLite test schema turns the delivery log enums into CHECK
     * constraints, and the handler writes values outside them ('received',
     * flow names as category). The log is not under test here, so use the
     * same columns without the CHECKs.
     */
    private function looseDeliveryLogForSqlite(): void
    {
        \Illuminate\Support\Facades\Schema::drop('message_delivery_log');
        \Illuminate\Support\Facades\Schema::create('message_delivery_log', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('whatsapp_message_id', 100)->nullable();
            $table->string('phone', 20);
            $table->string('template_name', 100)->nullable();
            $table->string('direction')->default('outbound');
            $table->string('category')->nullable();
            $table->string('status')->default('sent');
            $table->decimal('cost_usd', 8, 6)->default(0);
            $table->text('content_preview')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->string('flow_type', 50)->nullable();
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    private function inbound(string $body): void
    {
        $this->postJson('/api/whatsapp/inbound', [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'contacts' => [['profile' => ['name' => 'Test Visitor']]],
                        'messages' => [[
                            'from' => ltrim($this->stranger, '+'),
                            'id' => 'wamid.in.' . uniqid(),
                            'type' => 'text',
                            'text' => ['body' => $body],
                        ]],
                    ],
                ]],
            ]],
        ])->assertOk();
    }

    /** @return list<array<string,mixed>> */
    private function sentMessages(): array
    {
        return collect(Http::recorded())
            ->map(fn ($pair) => $pair[0])
            ->filter(fn (HttpRequest $r) => str_ends_with($r->url(), '/messages'))
            ->map(fn (HttpRequest $r) => $r->data())
            ->values()
            ->all();
    }

    public function test_T3_stranger_taps_try_demo_and_gets_the_parent_menu_on_a_demo_school(): void
    {
        $this->inbound('demo');

        $messages = $this->sentMessages();
        $this->assertNotEmpty($messages, 'No reply went out for Try Demo on a demo school.');

        $texts = collect($messages)->pluck('text.body')->filter()->implode("\n");
        $this->assertStringContainsString('Your number is removed after 30 minutes.', $texts);
        $this->assertTrue(
            collect($messages)->contains(fn ($m) => ($m['type'] ?? '') === 'interactive'),
            'The parent menu (interactive message) was not sent.'
        );

        $wa = WhatsAppUser::where('phone', $this->stranger)->firstOrFail();
        $this->assertSame((int) $this->demoParent->id, (int) $wa->user_id);
    }

    public function test_T3_report_returns_the_report_card_pdf_on_a_demo_school(): void
    {
        $this->inbound('demo');
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.test']], 'id' => 'media-1'], 200)]);

        $delivery = Mockery::mock(WhatsAppReportCardDeliveryService::class);
        $delivery->shouldReceive('prepareForStudent')->andReturn([
            'ok' => true,
            'url' => 'https://example.com/report-card.pdf',
            'caption' => 'Report card',
            'filename' => 'report-card.pdf',
        ]);
        $this->app->instance(WhatsAppReportCardDeliveryService::class, $delivery);

        $this->inbound('report');

        $this->assertTrue(
            collect($this->sentMessages())->contains(fn ($m) => ($m['type'] ?? '') === 'document'),
            'REPORT on a demo school did not send the report card PDF.'
        );
    }

    public function test_T3_fee_reminder_on_a_demo_school_still_sends_nothing(): void
    {
        $school = School::where('is_demo', 1)->firstOrFail();
        WhatsAppUser::create([
            'phone' => $this->stranger,
            'user_id' => $this->demoParent->id,
            'school_id' => $school->id,
            'opted_in' => true,
            'last_inbound_at' => now(),
        ]);
        $this->demoParent->forceFill(['mobile_no' => $this->stranger])->save();

        Artisan::call('whatsapp:send-fee-reminders', ['--school-id' => $school->id]);

        $this->assertSame([], $this->sentMessages());
    }

    public function test_T3_kill_switch_stops_all_outbound_whatsapp_including_replies(): void
    {
        config(['services.whatsapp.replies_enabled' => false]);

        $this->inbound('demo');
        $this->inbound('menu');

        $this->assertSame([], $this->sentMessages());
        Http::assertNothingSent();
    }

    public function test_T3_kill_switch_blocks_a_direct_send_to_a_non_demo_number(): void
    {
        config(['services.whatsapp.replies_enabled' => false]);

        $result = app(\App\Services\WhatsAppBusinessService::class)->sendText('+256700109999', 'hello');

        $this->assertTrue($result['blocked']);
        $this->assertSame('kill_switch', $result['reason']);
        Http::assertNothingSent();
    }

    public function test_T3_kill_switch_defaults_to_on(): void
    {
        $this->assertFalse(getenv('WHATSAPP_REPLIES_ENABLED'));
        $this->assertStringContainsString(
            "'replies_enabled' => (bool) env('WHATSAPP_REPLIES_ENABLED', true)",
            file_get_contents(config_path('services.php')),
        );
    }
}
