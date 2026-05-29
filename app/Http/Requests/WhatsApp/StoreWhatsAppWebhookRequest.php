<?php

namespace App\Http\Requests\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;

class StoreWhatsAppWebhookRequest extends FormRequest
{
    public const MAX_PAYLOAD_SIZE = 1048576; // 1 MB

    /**
     * Determine if the user is authorized.
     */
    public function authorize(): bool
    {
        // Verify Evolution API secret if configured
        $expectedSecret = config('services.whatsapp.evolution_api_secret');
        if ($expectedSecret) {
            $providedSecret = $this->header('X-Evolution-Webhook-Secret')
                ?? $this->header('X-Evolution-Secret')
                ?? $this->header('X-Webhook-Secret');

            return hash_equals($expectedSecret, $providedSecret);
        }

        // No secret configured — allow through (dev mode)
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'event'                              => 'required|string|in:messages.upsert',
            'instance'                           => 'sometimes|string',
            'data.key.remoteJid'                 => 'required_without:key.remoteJid|string',
            'key.remoteJid'                      => 'required_without:data.key.remoteJid|string',
            'data.key.fromMe'                    => 'sometimes|boolean',
            'key.fromMe'                         => 'sometimes|boolean',
            'data.message.conversation'           => 'required_without:data.message.extendedTextMessage.text|string',
            'data.message.extendedTextMessage.text' => 'required_without:data.message.conversation|string',
            'message.conversation'               => 'sometimes|string',
            'data.key.id'                        => 'sometimes|string',
            'key.id'                             => 'sometimes|string',
            'data.pushName'                      => 'sometimes|string',
            'pushName'                           => 'sometimes|string',
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'event.required'            => 'Webhook payload must include an event type.',
            'event.in'                  => 'Only messages.upsert events are processed.',
            'data.key.remoteJid'        => 'Sender identifier (remoteJid) is required.',
            'data.message.conversation' => 'Message content is required.',
        ];
    }

    /**
     * Additional validation after rules pass.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Payload size check
            $contentLength = $this->header('Content-Length');
            if ($contentLength && (int) $contentLength > static::MAX_PAYLOAD_SIZE) {
                $validator->errors()->add(
                    'payload',
                    'Payload exceeds maximum size of ' . (static::MAX_PAYLOAD_SIZE / 1024) . ' KB.'
                );
            }

            // Validate phone format if remoteJid is present
            $remoteJid = $this->input('data.key.remoteJid') ?? $this->input('key.remoteJid');
            if ($remoteJid && !str_contains($remoteJid, '@g.us')) {
                $phone = preg_replace('/@.*$/', '', $remoteJid);
                if (!preg_match('/^\d{5,15}$/', $phone)) {
                    $validator->errors()->add('data.key.remoteJid', 'Invalid phone format in remoteJid.');
                }
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure we have a Content-Length check — if not present, compute it
        if (!$this->hasHeader('Content-Length')) {
            $this->headers->set('Content-Length', (string) strlen($this->getContent()));
        }
    }
}
