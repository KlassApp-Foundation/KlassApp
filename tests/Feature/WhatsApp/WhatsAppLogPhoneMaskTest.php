<?php

namespace Tests\Feature\WhatsApp;

use App\Services\WhatsAppBusinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * No WhatsApp log line may contain the full recipient number.
 */
class WhatsAppLogPhoneMaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_window_closed_warning_masks_the_number(): void
    {
        config([
            'services.whatsapp.business_api_token' => 'test-token',
            'services.whatsapp.business_phone_number_id' => '106540352242922',
            'services.whatsapp.replies_enabled' => true,
        ]);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.test']]], 200)]);

        Log::spy();

        $service = app(WhatsAppBusinessService::class);
        $service->sendTextSafe('+256770123456', 'Hello');

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message, array $context = []) => ! str_contains(json_encode($context), '256770123456'))
            ->atLeast()->once();
    }
}
