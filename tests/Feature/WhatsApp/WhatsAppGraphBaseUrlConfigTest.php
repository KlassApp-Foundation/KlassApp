<?php

namespace Tests\Feature\WhatsApp;

use App\Services\DemoSchoolCommsGuard;
use App\Services\WhatsAppBusinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Task 10: the outbound WhatsApp Cloud API base URL is a config key
 * (services.whatsapp.graph_api_base_url, env WHATSAPP_GRAPH_API_BASE_URL)
 * so a local simulator can receive outbound calls. Production default unchanged.
 */
class WhatsAppGraphBaseUrlConfigTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.timezone' => 'Africa/Kampala']);
        date_default_timezone_set('Africa/Kampala');

        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        }

        DemoSchoolCommsGuard::flushCache();

        config([
            'services.whatsapp.business_api_token' => 'test-token',
            'services.whatsapp.business_phone_number_id' => '100000000000001',
            'services.whatsapp.business_api_version' => 'v21.0',
            'services.whatsapp.replies_enabled' => true,
        ]);
    }

    protected function tearDown(): void
    {
        DemoSchoolCommsGuard::flushCache();

        parent::tearDown();
    }

    /**
     * The value config/services.php gives when WHATSAPP_GRAPH_API_BASE_URL is not set,
     * even if a developer's local .env points it at the simulator.
     */
    private function configFileDefault(): mixed
    {
        $key = 'WHATSAPP_GRAPH_API_BASE_URL';
        $saved = [getenv($key), $_ENV[$key] ?? null, $_SERVER[$key] ?? null];
        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);

        try {
            $services = require config_path('services.php');
        } finally {
            if ($saved[0] !== false) {
                putenv("{$key}={$saved[0]}");
            }
            if ($saved[1] !== null) {
                $_ENV[$key] = $saved[1];
            }
            if ($saved[2] !== null) {
                $_SERVER[$key] = $saved[2];
            }
        }

        return $services['whatsapp']['graph_api_base_url'] ?? null;
    }

    public function test_T10_default_graph_api_base_url_is_the_meta_production_host(): void
    {
        $this->assertSame('https://graph.facebook.com', $this->configFileDefault());
    }

    public function test_T10_outbound_messages_go_to_the_configured_graph_api_base_url(): void
    {
        config(['services.whatsapp.graph_api_base_url' => 'http://127.0.0.1:9123/']);

        Http::fake([
            '*' => Http::response(['messages' => [['id' => 'wamid.local-test']]], 200),
        ]);

        $service = app(WhatsAppBusinessService::class);
        $service->sendText('+15550100001', 'Hello from the test');
        $service->sendText('+15550100002', 'Second message');

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request) => $request->url() === 'http://127.0.0.1:9123/v21.0/100000000000001/messages');
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'graph.facebook.com'));
    }

    public function test_T10_outbound_messages_use_the_production_host_when_not_overridden(): void
    {
        config(['services.whatsapp.graph_api_base_url' => $this->configFileDefault()]);

        Http::fake([
            '*' => Http::response(['messages' => [['id' => 'wamid.local-test']]], 200),
        ]);

        app(WhatsAppBusinessService::class)->sendText('+15550100001', 'Hello from the test');

        Http::assertSent(fn (Request $request) => $request->url() === 'https://graph.facebook.com/v21.0/100000000000001/messages');
    }
}
