<?php

namespace App\Services;

use App\Models\MessageDeliveryLog;
use App\Models\WhatsAppUser;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * WhatsAppBusinessService — Outbound messaging via Meta WhatsApp Cloud API.
 *
 * Replaces Evolution API as the transport layer. Sends messages through
 * Meta's Graph API directly. Same interface as WhatsAppService so it
 * can be swapped in without changing business logic.
 *
 * Meta API docs: https://developers.facebook.com/docs/whatsapp/cloud-api
 */
class WhatsAppBusinessService
{
    protected string $token;
    protected string $phoneNumberId;
    protected string $apiVersion;

    public function __construct()
    {
        $this->token = config('services.whatsapp.business_api_token', env('WHATSAPP_BUSINESS_API_TOKEN'));
        $this->phoneNumberId = config('services.whatsapp.business_phone_number_id', env('WHATSAPP_BUSINESS_PHONE_NUMBER_ID'));
        $this->apiVersion = config('services.whatsapp.business_api_version', env('WHATSAPP_BUSINESS_API_VERSION', 'v21.0'));
    }

    /**
     * Check if the service is configured with Business API credentials.
     */
    public function isConfigured(): bool
    {
        return !empty($this->token) && !empty($this->phoneNumberId);
    }

    /**
     * Send a plain text message via Meta Cloud API.
     *
     * Used for responses within the 24hr customer service window (free).
     *
     * @param string $phone E.164 format (e.g. +256701234567)
     * @param string $message Plain text message (*bold*, _italic_, etc.)
     * @param string|null $flowType Category for analytics
     * @param int|null $userId KlassApp user ID (for tracking)
     * @return array{success: bool, message_id: string, error?: string}
     */
    public function sendText(string $phone, string $message, ?string $flowType = null, ?int $userId = null): array
    {
        $cleanPhone = $this->cleanPhone($phone);

        $response = Http::withToken($this->token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post("https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'recipient_type'    => 'individual',
                'to'                => $cleanPhone,
                'type'              => 'text',
                'text'              => [
                    'preview_url' => false,
                    'body'        => $message,
                ],
            ]);

        $body = $response->json();
        $messageId = $body['messages'][0]['id'] ?? Str::uuid()->toString();
        $success = $response->successful();

        // Log the outbound message
        MessageDeliveryLog::create([
            'whatsapp_message_id' => $messageId,
            'phone'               => $phone,
            'direction'           => 'outbound',
            'category'            => $flowType ?? 'service',
            'status'              => $success ? 'sent' : 'failed',
            'content_preview'     => Str::limit($message, 200),
        ]);

        if (!$success) {
            $error = $body['error']['message'] ?? 'Unknown error';
            Log::error('WhatsApp Business API: sendText failed', [
                'phone'  => $phone,
                'error'  => $error,
                'status' => $response->status(),
            ]);
            return [
                'success'    => false,
                'message_id' => $messageId,
                'error'      => $error,
            ];
        }

        return [
            'success'    => true,
            'message_id' => $messageId,
        ];
    }

    /**
     * Send a pre-approved template message via Meta Cloud API.
     *
     * Template messages can be sent outside the 24hr service window
     * and may incur charges depending on the template category.
     *
     * @param string $phone E.164 format
     * @param string $templateName The approved template name in Meta
     * @param array $variables Body parameter values (ordered)
     * @param string|null $language Template language code (default: en)
     * @param string|null $flowType Category for analytics
     * @return array{success: bool, message_id: string, error?: string}
     */
    public function sendTemplate(
        string $phone,
        string $templateName,
        array $variables = [],
        ?string $language = null,
        ?string $flowType = null,
    ): array {
        $cleanPhone = $this->cleanPhone($phone);
        $lang = $language ?? config('services.whatsapp.template_language', env('WHATSAPP_TEMPLATE_LANGUAGE', 'en'));

        $components = [
            [
                'type' => 'body',
                'parameters' => array_map(fn ($value) => ['type' => 'text', 'text' => $value], $variables),
            ],
        ];

        $response = Http::withToken($this->token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post("https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'recipient_type'    => 'individual',
                'to'                => $cleanPhone,
                'type'              => 'template',
                'template'          => [
                    'name'       => $templateName,
                    'language'   => [
                        'code' => $lang,
                    ],
                    'components' => $components,
                ],
            ]);

        $body = $response->json();
        $messageId = $body['messages'][0]['id'] ?? Str::uuid()->toString();
        $success = $response->successful();

        MessageDeliveryLog::create([
            'whatsapp_message_id' => $messageId,
            'phone'               => $phone,
            'direction'           => 'outbound',
            'category'            => $flowType ?? 'template',
            'status'              => $success ? 'sent' : 'failed',
        ]);

        if (!$success) {
            $error = $body['error']['message'] ?? 'Unknown error';
            Log::error('WhatsApp Business API: sendTemplate failed', [
                'phone'         => $phone,
                'template_name' => $templateName,
                'error'         => $error,
            ]);
            return [
                'success'    => false,
                'message_id' => $messageId,
                'error'      => $error,
            ];
        }

        return [
            'success'    => true,
            'message_id' => $messageId,
        ];
    }

    /**
     * Check if the 24-hour free-form messaging window is open for a phone.
     *
     * The window is open if the user has sent a message to the business
     * within the last 24 hours (tracked locally via last_inbound_at).
     *
     * @param string $phone E.164 format
     */
    public function isWithinServiceWindow(string $phone): bool
    {
        $whatsappUser = WhatsAppUser::findByPhone($phone);

        if (!$whatsappUser || !$whatsappUser->last_inbound_at) {
            return false;
        }

        return $whatsappUser->last_inbound_at->gt(now()->subHours(24));
    }

    /**
     * Strip the + prefix for Meta API (Meta expects just the digit string).
     */
    protected function cleanPhone(string $phone): string
    {
        return ltrim($phone, '+');
    }
}
