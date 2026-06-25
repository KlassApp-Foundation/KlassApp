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
     * Send interactive reply buttons via Meta Cloud API.
     *
     * Max 3 buttons, each title max 20 characters.
     * Body text max 1024 characters.
     *
     * @param string $phone E.164 format
     * @param string $body Plain text body above the buttons
     * @param array $buttons Array of ['id' => 'keyword', 'title' => 'Label']
     * @param string|null $flowType Category for analytics
     * @param int|null $userId KlassApp user ID
     * @return array{success: bool, message_id: string, error?: string}
     */
    public function sendInteractiveButtons(
        string $phone,
        string $body,
        array $buttons,
        ?string $flowType = null,
        ?int $userId = null,
    ): array {
        $cleanPhone = $this->cleanPhone($phone);

        $metaButtons = array_map(fn ($btn) => [
            'type' => 'reply',
            'reply' => [
                'id'    => $btn['id'] ?? Str::uuid()->toString(),
                'title' => mb_substr($btn['title'] ?? 'Option', 0, 20),
            ],
        ], array_slice($buttons, 0, 3));

        $response = Http::withToken($this->token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post("https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'recipient_type'    => 'individual',
                'to'                => $cleanPhone,
                'type'              => 'interactive',
                'interactive'       => [
                    'type'   => 'button',
                    'body'   => ['text' => mb_substr($body, 0, 1024)],
                    'action' => ['buttons' => $metaButtons],
                ],
            ]);

        $result = $response->json();
        $messageId = $result['messages'][0]['id'] ?? Str::uuid()->toString();
        $success = $response->successful();

        $preview = collect($buttons)->pluck('title')->implode(', ');

        MessageDeliveryLog::create([
            'whatsapp_message_id' => $messageId,
            'phone'               => $phone,
            'direction'           => 'outbound',
            'category'            => 'interactive',
            'status'              => $success ? 'sent' : 'failed',
            'content_preview'     => "Buttons: {$body} — [{$preview}]",
            'user_id'             => $userId,
            'flow_type'           => $flowType ?? 'interactive',
        ]);

        if (!$success) {
            $error = $result['error']['message'] ?? 'Unknown error';
            Log::error('WhatsApp Business API: sendInteractiveButtons failed', [
                'phone'  => $phone,
                'body'   => $body,
                'error'  => $error,
                'status' => $response->status(),
            ]);
            return ['success' => false, 'message_id' => $messageId, 'error' => $error];
        }

        Log::info('WhatsApp Business API: interactive buttons sent', [
            'phone'  => $phone,
            'body'   => $body,
            'buttons' => $preview,
        ]);

        return ['success' => true, 'message_id' => $messageId];
    }

    /**
     * Send a list message via Meta Cloud API interactive list.
     *
     * Uses native WhatsApp interactive list messages when possible.
     * Falls back to well-formatted text for larger lists (Meta limit: 10 rows).
     *
     * @param string $phone E.164 format
     * @param string $title Header text
     * @param array $sections Array of ['title' => string, 'rows' => [['id' => string, 'title' => string]]]
     * @param string $description Body text
     * @param string $footerText Footer text
     * @param string $buttonText CTA button label
     * @param string|null $flowType
     * @param int|null $userId
     * @return array
     */
    public function sendList(
        string $phone,
        string $title,
        array $sections,
        string $description = '',
        string $footerText = '',
        string $buttonText = 'View Options',
        ?string $flowType = null,
        ?int $userId = null,
    ): array {
        $cleanPhone = $this->cleanPhone($phone);

        // Count total rows across all sections (Meta limit: 10)
        $totalRows = collect($sections)->sum(fn ($s) => count($s['rows'] ?? []));

        if ($totalRows <= 10 && $totalRows > 0) {
            // Send as native interactive list message
            $metaSections = array_map(function ($section) {
                return [
                    'title' => mb_substr($section['title'] ?? '', 0, 24),
                    'rows'  => array_map(function ($row) {
                        return [
                            'id'          => $row['id'] ?? Str::uuid()->toString(),
                            'title'       => mb_substr($row['title'] ?? '', 0, 24),
                            'description' => isset($row['description'])
                                ? mb_substr($row['description'], 0, 72) : '',
                        ];
                    }, array_slice($section['rows'] ?? [], 0, 10)),
                ];
            }, array_slice($sections, 0, 10)); // Max 10 sections

            $response = Http::withToken($this->token)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'recipient_type'    => 'individual',
                    'to'                => $cleanPhone,
                    'type'              => 'interactive',
                    'interactive'       => [
                        'type'   => 'list',
                        'header' => ['type' => 'text', 'text' => mb_substr($title, 0, 60)],
                        'body'   => ['text' => mb_substr($description ?: $title, 0, 1024)],
                        'footer' => ['text' => mb_substr($footerText, 0, 60)],
                        'action' => [
                            'button'   => mb_substr($buttonText, 0, 20),
                            'sections' => $metaSections,
                        ],
                    ],
                ]);

            $result = $response->json();
            $messageId = $result['messages'][0]['id'] ?? Str::uuid()->toString();
            $success = $response->successful();

            MessageDeliveryLog::create([
                'whatsapp_message_id' => $messageId,
                'phone'               => $phone,
                'direction'           => 'outbound',
                'category'            => 'interactive',
                'status'              => $success ? 'sent' : 'failed',
                'content_preview'     => "Native List: {$title}",
                'user_id'             => $userId,
                'flow_type'           => $flowType ?? 'menu',
            ]);

            if (!$success) {
                Log::error('WhatsApp Business API: sendList failed', [
                    'phone'  => $phone,
                    'title'  => $title,
                    'error'  => $result['error']['message'] ?? 'Unknown',
                ]);
                return ['success' => false, 'message_id' => $messageId, 'error' => $result['error']['message'] ?? 'Unknown'];
            }

            return ['success' => true, 'message_id' => $messageId];
        }

        // Fallback: render as well-formatted text
        $text = "*{$title}*\n\n{$description}\n";
        foreach ($sections as $section) {
            $secTitle = $section['title'] ?? '';
            if ($secTitle) {
                $text .= "\n*{$secTitle}*\n";
            }
            foreach ($section['rows'] ?? [] as $row) {
                $rowTitle = $row['title'] ?? '';
                $rowDesc = $row['description'] ?? '';
                $text .= "• {$rowTitle}" . ($rowDesc ? " — {$rowDesc}" : '') . "\n";
            }
        }
        if ($footerText) {
            $text .= "\n_{$footerText}_";
        }

        return $this->sendText($phone, $text, $flowType ?? 'menu', $userId);
    }

    /**
     * Send interactive reply buttons via Meta Cloud API.
     * Alias for sendInteractiveButtons with (buttons, title) parameter order.
     */
    public function sendButtons(
        string $phone,
        string $message,
        array $buttons,
        string $title = '',
        string $footer = '',
        ?string $flowType = null,
        ?int $userId = null,
    ): array {
        $body = $title ? "*{$title}*\n\n{$message}" : $message;
        if ($footer) {
            $body .= "\n\n_{$footer}_";
        }
        return $this->sendInteractiveButtons($phone, $body, $buttons, $flowType, $userId);
    }

    /**
     * Send a text message with automatic 24hr window fallback.
     *
     * If the service window is open, sends free-form text (free).
     * If closed and a fallback template is provided, sends the template instead.
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
            Log::info('WhatsApp Business: window closed, using template fallback', [
                'phone'     => $phone,
                'template'  => $fallbackTemplate,
            ]);
            return $this->sendTemplate($phone, $fallbackTemplate, $templateVariables, $flowType);
        }

        // Window closed but no fallback template — send free-form anyway
        Log::warning('WhatsApp Business: window closed, sending free-form outside window', [
            'phone' => $phone,
        ]);
        return $this->sendText($phone, $message, $flowType, $userId);
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
            Log::warning('WhatsApp Business: user has no linked WhatsApp number', ['user_id' => $userId]);
            return [
                'success' => false,
                'error'   => 'User has no linked WhatsApp number',
            ];
        }

        return $this->sendText($whatsappUser->phone, $message, $flowType, $userId);
    }

    /**
     * Strip the + prefix for Meta API (Meta expects just the digit string).
     */
    protected function cleanPhone(string $phone): string
    {
        return ltrim($phone, '+');
    }
}
