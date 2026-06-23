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
     * Send an interactive List Message via Evolution API.
     *
     * WhatsApp List Messages present a "View Options" button that opens a
     * scrollable list of up to 10 items. Each item has a title and optional
     * description. Items can span multiple sections with section headers.
     *
     * @param string $phone E.164 format
     * @param string $title Header text above the list
     * @param array $sections Array of sections, each with 'title' and 'rows'.
     *                        Rows: [['title' => 'Option', 'description' => '...'], ...]
     *                        Max 10 rows total across all sections.
     * @param string|null $description Body text below the title
     * @param string|null $footerText Footer text
     * @param string $buttonText Label for the CTA button (default: "View Options")
     * @param string|null $flowType Category for analytics
     * @param int|null $userId KlassApp user ID
     */
    public function sendList(
        string $phone,
        string $title,
        array $sections,
        ?string $description = null,
        ?string $footerText = null,
        string $buttonText = 'View Options',
        ?string $flowType = null,
        ?int $userId = null,
    ): array {
        // Build the rows preview for logging
        $preview = collect($sections)->flatMap(fn($s) => $s['rows'] ?? [])
            ->pluck('title')->implode(', ');

        $response = Http::withHeaders([
            'apikey' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/message/sendList/{$this->instanceName}", [
            'number'     => $this->cleanPhone($phone),
            'title'      => $title,
            'description' => $description ?? '',
            'footerText'  => $footerText ?? '',
            'buttonText'  => $buttonText,
            'sections'    => $sections,
        ]);

        $messageId = $response->json('key.id') ?? Str::uuid()->toString();
        $status = $response->successful() ? 'sent' : 'failed';

        $log = MessageDeliveryLog::create([
            'whatsapp_message_id' => $messageId,
            'phone'               => $phone,
            'category'            => 'interactive',
            'status'              => $status,
            'content_preview'     => "List: {$title} — {$preview}",
            'user_id'             => $userId,
            'flow_type'           => $flowType ?? 'menu',
        ]);

        if (!$response->successful()) {
            Log::error('WhatsApp sendList failed', [
                'phone'    => $phone,
                'title'    => $title,
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
     * Send a text message with automatic 24hr window fallback.
     *
     * If the service window is closed and $fallbackTemplate is provided,
     * sends a template instead of free-form text. This ensures proactive
     * outbound messages always reach the user.
     *
     * @param string $phone E.164 format
     * @param string $message Free-form text (used when window is open)
     * @param string|null $fallbackTemplate Template name if window is closed
     * @param array $templateVariables Variables for the fallback template
     * @param string|null $flowType Category for analytics
     * @param int|null $userId KlassApp user ID
     */
    public function sendTextSafe(
        string $phone,
        string $message,
        ?string $fallbackTemplate = null,
        array $templateVariables = [],
        ?string $flowType = null,
        ?int $userId = null,
    ): array {
        if ($this->isWithinServiceWindow($phone)) {
            return $this->sendText($phone, $message, $flowType, $userId);
        }

        if ($fallbackTemplate) {
            Log::info('WhatsApp: window closed, using template fallback', [
                'phone'     => $phone,
                'template'  => $fallbackTemplate,
            ]);
            return $this->sendTemplate($phone, $fallbackTemplate, $templateVariables, 'utility', $userId);
        }

        // Window closed but no fallback template — log warning, send as free-form anyway
        Log::warning('WhatsApp: window closed, sending free-form outside window', [
            'phone' => $phone,
        ]);
        return $this->sendText($phone, $message, $flowType, $userId);
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
     * Send an interactive Button Message via Evolution API.
     *
     * WhatsApp button messages present 1-3 reply buttons beneath the text.
     * The buttons are labelled actions (not URLs); tapping one sends the
     * button ID back as the user's reply.
     *
     * @param string $phone E.164 format
     * @param string $message Body text above the buttons
     * @param array $buttons Array of ['title' => 'Button', 'id' => 'button_id']
     *                       Max 3 buttons. 'id' is sent back when tapped.
     * @param string|null $title Optional header text (bold, single line)
     * @param string|null $footer Optional footer text (small, dimmed)
     * @param string|null $flowType Category for analytics
     * @param int|null $userId KlassApp user ID
     */
    public function sendButtons(
        string $phone,
        string $message,
        array $buttons,
        ?string $title = null,
        ?string $footer = null,
        ?string $flowType = null,
        ?int $userId = null,
    ): array {
        $response = Http::withHeaders([
            'apikey' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/message/sendButtons/{$this->instanceName}", [
            'number' => $this->cleanPhone($phone),
            'title'  => $title ?? '',
            'text'   => $message,
            'footer' => $footer ?? '',
            'button' => array_map(fn($b) => [
                'type'  => 'replyButton',
                'title' => $b['title'] ?? 'Button',
                'id'    => $b['id'] ?? 'button',
            ], $buttons),
        ]);

        $messageId = $response->json('key.id') ?? Str::uuid()->toString();
        $status = $response->successful() ? 'sent' : 'failed';

        $log = MessageDeliveryLog::create([
            'whatsapp_message_id' => $messageId,
            'phone'               => $phone,
            'category'            => 'interactive',
            'status'              => $status,
            'content_preview'     => 'Buttons: ' . collect($buttons)->pluck('title')->implode(', '),
            'user_id'             => $userId,
            'flow_type'           => $flowType ?? 'interactive',
        ]);

        if (!$response->successful()) {
            Log::error('WhatsApp sendButtons failed', [
                'phone'    => $phone,
                'title'    => $title,
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
     * Uses the tracked last_inbound_at timestamp on whatsapp_users.
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
     * Clean phone number for Evolution API (remove + prefix, keep digits).
     */
    protected function cleanPhone(string $phone): string
    {
        return ltrim(preg_replace('/\D/', '', $phone), '0');
    }
}
