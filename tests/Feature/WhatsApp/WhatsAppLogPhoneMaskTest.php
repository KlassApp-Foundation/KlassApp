<?php

namespace Tests\Feature\WhatsApp;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * WhatsApp application logs keep the country code and the last three digits.
 * The number that is sent to the provider is unchanged.
 */
class WhatsAppLogPhoneMaskTest extends TestCase
{
    use RefreshDatabase;

    private const APP_SECRET = 'log-mask-test-secret';

    private const FULL = '+256700000123';

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('PRAGMA foreign_keys = ON');
        config([
            'app.timezone' => 'Africa/Kampala',
            'services.whatsapp.app_secret' => self::APP_SECRET,
            'services.whatsapp.verify_signature' => true,
            'services.whatsapp.business_api_token' => 'test-token',
            'services.whatsapp.business_phone_number_id' => '000000',
            'services.whatsapp.replies_enabled' => true,
        ]);
        Carbon::setTestNow(Carbon::parse('2026-10-21 12:00:00', 'Africa/Kampala'));
        $this->relaxDeliveryLog();
        Http::preventStrayRequests();
        Http::fake([
            'graph.facebook.com/*' => Http::response(['error' => ['message' => 'rejected']], 500),
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_inbound_and_failed_send_logs_omit_the_full_number(): void
    {
        Log::spy();

        $this->inbound('256700000123', 'hello')->assertOk();

        Http::assertSent(function ($request) {
            return ($request->data()['to'] ?? null) === '256700000123';
        });

        Log::shouldHaveReceived('info')->withArgs(function ($message, $context = []) {
            if ($message !== 'Meta message parsed') {
                return false;
            }

            $this->assertSame('+256*****123', $context['phone'] ?? null);
            $this->assertStringNotContainsString('256700000123', (string) json_encode($context));
            $this->assertStringNotContainsString(self::FULL, (string) $message);

            return true;
        });

        Log::shouldHaveReceived('error')->withArgs(function ($message, $context = []) {
            if (! is_string($message) || ! str_contains($message, 'failed')) {
                return false;
            }

            $blob = $message.' '.json_encode($context);
            $this->assertSame('+256*****123', $context['phone'] ?? null);
            $this->assertStringNotContainsString('256700000123', $blob);
            $this->assertStringNotContainsString(self::FULL, $blob);

            return true;
        });
    }

    private function inbound(string $from, string $text): \Illuminate\Testing\TestResponse
    {
        $body = json_encode(['entry' => [['changes' => [['value' => [
            'contacts' => [['profile' => ['name' => 'Guest'], 'wa_id' => $from]],
            'messages' => [['from' => $from, 'id' => 'wamid.in.mask', 'type' => 'text', 'text' => ['body' => $text]]],
        ]]]]]]);

        return $this->call('POST', '/api/whatsapp/inbound', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-Hub-Signature-256' => 'sha256='.hash_hmac('sha256', $body, self::APP_SECRET),
        ], $body);
    }

    /**
     * sqlite enforces message_delivery_log's enum CHECKs, which inbound rows
     * (status "received") do not fit; MySQL runs with strict off. Same columns,
     * as strings, for this test only.
     */
    private function relaxDeliveryLog(): void
    {
        Schema::drop('message_delivery_log');
        Schema::create('message_delivery_log', function (Blueprint $table) {
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
        });
    }
}
