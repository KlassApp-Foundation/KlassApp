<?php

namespace Tests\Feature\WhatsApp;

use Tests\TestCase;

class WebhookValidationTest extends TestCase
{
    protected function validPayload(): array
    {
        return [
            'event'    => 'messages.upsert',
            'instance' => 'klassapp',
            'data'     => [
                'key' => [
                    'remoteJid' => '256781940358@s.whatsapp.net',
                    'fromMe'    => false,
                    'id'        => 'ABC123',
                ],
                'message' => [
                    'conversation' => 'Hello',
                ],
                'pushName' => 'Test User',
            ],
        ];
    }

    public function test_event_is_required(): void
    {
        $rules = (new \App\Http\Requests\WhatsApp\StoreWhatsAppWebhookRequest)->rules();
        $validator = validator($this->validPayload(), $rules);
        $this->assertFalse($validator->fails());
    }

    public function test_rejects_missing_event(): void
    {
        $payload = $this->validPayload();
        unset($payload['event']);

        $rules = (new \App\Http\Requests\WhatsApp\StoreWhatsAppWebhookRequest)->rules();
        $validator = validator($payload, $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('event', $validator->errors()->toArray());
    }

    public function test_rejects_wrong_event(): void
    {
        $payload = $this->validPayload();
        $payload['event'] = 'messages.delete';

        $rules = (new \App\Http\Requests\WhatsApp\StoreWhatsAppWebhookRequest)->rules();
        $validator = validator($payload, $rules);
        $this->assertTrue($validator->fails());
    }

    public function test_rejects_missing_remote_jid(): void
    {
        $payload = $this->validPayload();
        unset($payload['data']['key']['remoteJid']);

        $rules = (new \App\Http\Requests\WhatsApp\StoreWhatsAppWebhookRequest)->rules();
        $validator = validator($payload, $rules);
        $this->assertTrue($validator->fails());
    }

    public function test_rejects_missing_message_body(): void
    {
        $payload = $this->validPayload();
        unset($payload['data']['message']['conversation']);

        $rules = (new \App\Http\Requests\WhatsApp\StoreWhatsAppWebhookRequest)->rules();
        $validator = validator($payload, $rules);
        $this->assertTrue($validator->fails());
    }

    public function test_rejects_empty_message_body(): void
    {
        $payload = $this->validPayload();
        $payload['data']['message']['conversation'] = '';

        $rules = (new \App\Http\Requests\WhatsApp\StoreWhatsAppWebhookRequest)->rules();
        $validator = validator($payload, $rules);
        $this->assertTrue($validator->fails());
    }

    public function test_allows_extended_text_message(): void
    {
        $payload = $this->validPayload();
        unset($payload['data']['message']['conversation']);
        $payload['data']['message']['extendedTextMessage'] = ['text' => 'Hello via extended text'];

        $rules = (new \App\Http\Requests\WhatsApp\StoreWhatsAppWebhookRequest)->rules();
        $validator = validator($payload, $rules);
        $this->assertFalse($validator->fails());
    }
}
