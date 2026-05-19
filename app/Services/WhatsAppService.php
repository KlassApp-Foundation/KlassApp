<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Services;

use App\Models\MessageDeliveryLog;
use App\Models\WhatsAppUser;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * WhatsAppService — Outbound messaging via Evolution API.
 *
 * This service sends outbound WhatsApp messages through a self-hosted
 * Evolution API instance. It handles:
 *   - Text messages (within 24hr service window)
 *   - Template messages (outside 24hr window, pre-approved by Meta)
 *   - Delivery tracking to message_delivery_log
 *
 * Evolution API docs: https://doc.evolution-api.com/
 */
class WhatsAppService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $instanceName;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.whatsapp.evolution_url', env('EVOLUTION_API_URL')), '/');
        $this->apiKey = config('services.whatsapp.evolution_api_key', env('EVOLUTION_API_KEY'));
        $this->instanceName = config('services.whatsapp.instance_name', env('EVOLUTION_INSTANCE_NAME', 'klassapp'));
    }

    /**
     * Send a text message to a phone number via Evolution API.
     *
     * Used for responses within the 24hr customer service window (free).
     *
     * @param string $phone E.164 format (e.g. +256701234567)
     * @param string $message Plain text message (WhatsApp formatting: *bold*, _italic_, ~strikethrough~, ```code```)
     * @param string|null $flowType Category for analytics (e.g. 'grades', 'attendance', 'fee_reminder')
     * @param int|null $userId KlassApp user ID (for tracking)
     */
    public function sendText(string $phone, string $message, ?string $flowType = null, ?int $userId = null): array
    {
        $response = Http::withHeaders([
            'apikey' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/message/sendText/{$this->instanceName}", [
            'number'    => $this->cleanPhone($phone),
            'text'      => $message,
            'delay'     => config('services.whatsapp.send_delay', 1200), // ms delay for rate limiting
        ]);

        $messageId = $response->json('key.id') ?? Str::uuid()->toString();
        $status = $response->successful() ? 'sent' : 'failed';

        // Log the outbound message
        $log = MessageDeliveryLog::create([
            'whatsapp_message_id' => $messageId,
            'phone'               => $phone,
            'category'            => 'service',
            'status'              => $status,
            'content_preview'     => Str::limit($message, 200),
            'user_id'             => $userId,
            'flow_type'           => $flowType,
        ]);

        if (!$response->successful()) {
            Log::error('WhatsApp send failed', [
                'phone'    => $phone,
                'status'   => $response->status(),
                'body'     => $response->body(),
            ]);
            $log->markFailed("HTTP {$response->status()}: {$response->body()}");
        }

        return [
            'success'    => $response->successful(),
            'message_id' => $messageId,
            'log_id'     => $log->id,
            'status'     => $status,
        ];
    }

    /**
     * Send a pre-approved template message via Evolution API.
     *
     * Used for proactive outbound messages outside the 24hr window.
     * Templates must be pre-approved by Meta.
     *
     * @param string $phone E.164 format
     * @param string $templateName Meta-approved template name
     * @param array $variables Template variables (e.g. ['John', 'Amon', '500,000', '15 June 2026'])
     * @param string $category Template category (utility, marketing, authentication)
     * @param int|null $userId KlassApp user ID
     */
    public function sendTemplate(string $phone, string $templateName, array $variables = [], string $category = 'utility', ?int $userId = null): array
    {
        $response = Http::withHeaders([
            'apikey' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/template/send/{$this->instanceName}", [
            'number'         => $this->cleanPhone($phone),
            'name'           => $templateName,
            'language'       => config('services.whatsapp.template_language', 'en'),
            'components'     => [
                [
                    'type'      => 'body',
                    'parameters' => collect($variables)->map(function ($value) {
                        return ['type' => 'text', 'text' => $value];
                    })->values()->toArray(),
                ],
            ],
        ]);

        $messageId = $response->json('key.id') ?? Str::uuid()->toString();
        $status = $response->successful() ? 'sent' : 'failed';

        // Estimate cost (Uganda = Rest of Africa pricing)
        $costEstimate = match ($category) {
            'utility'         => 0.006,
            'marketing'       => 0.025,
            'authentication'  => 0.004,
            default           => 0.006,
        };

        $log = MessageDeliveryLog::create([
            'whatsapp_message_id' => $messageId,
            'phone'               => $phone,
            'template_name'       => $templateName,
            'category'            => $category,
            'status'              => $status,
            'cost_usd'            => $costEstimate,
            'content_preview'     => "Template: {$templateName} (" . implode(', ', $variables) . ")",
            'user_id'             => $userId,
            'flow_type'           => 'template_' . $templateName,
        ]);

        if (!$response->successful()) {
            Log::error('WhatsApp template send failed', [
                'phone'    => $phone,
                'template' => $templateName,
                'status'   => $response->status(),
                'body'     => $response->body(),
            ]);
            $log->markFailed("HTTP {$response->status()}: {$response->body()}");
        }

        return [
            'success'    => $response->successful(),
            'message_id' => $messageId,
            'log_id'     => $log->id,
            'status'     => $status,
            'cost_usd'   => $costEstimate,
        ];
    }

    /**
     * Send a message to a WhatsApp user by KlassApp user ID.
     *
     * Convenience method that resolves the user's WhatsApp phone number.
     */
    public function sendToUser(int $userId, string $message, ?string $flowType = null): array
    {
        $whatsappUser = WhatsAppUser::where('user_id', $userId)
            ->optedIn()
            ->first();

        if (!$whatsappUser) {
            Log::warning('WhatsApp send: user has no linked WhatsApp number', ['user_id' => $userId]);
            return [
                'success' => false,
                'error'   => 'User has no linked WhatsApp number',
            ];
        }

        return $this->sendText($whatsappUser->phone, $message, $flowType, $userId);
    }

    /**
     * Check if a phone number has an open 24hr service window.
     *
     * Returns true if the user has sent a message within the last 24 hours,
     * meaning free-form messages can be sent without using a template.
     *
     * Note: This is a simplified check. In production, you'd track the
     * exact window open/close times from Evolution API webhooks.
     */
    public function isWithinServiceWindow(string $phone): bool
    {
        // Check if user sent a message in the last 24 hours
        // This would ideally be tracked via Evolution API webhook events
        $lastActivity = MessageDeliveryLog::where('phone', $phone)
            ->where('sent_at', '>=', now()->subHours(24))
            ->exists();

        return $lastActivity;
    }

    /**
     * Clean phone number for Evolution API (remove + prefix, keep digits).
     */
    protected function cleanPhone(string $phone): string
    {
        return ltrim(preg_replace('/\D/', '', $phone), '0');
    }
}
