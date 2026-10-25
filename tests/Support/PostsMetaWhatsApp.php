<?php

namespace Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;

trait PostsMetaWhatsApp
{
    protected function postMetaWhatsAppText(string $from, string $text): TestResponse
    {
        $secret = 'user-initiated-test-secret';
        config([
            'services.whatsapp.app_secret' => $secret,
            'services.whatsapp.verify_signature' => true,
            'services.whatsapp.business_api_token' => 'test-token',
            'services.whatsapp.business_phone_number_id' => '000000',
            'services.whatsapp.replies_enabled' => true,
            'services.whatsapp.demo_only_recipients' => false,
        ]);

        $this->relaxMessageDeliveryLogForInbound();

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.reply']]], 200),
        ]);

        $fromDigits = ltrim($from, '+');
        $payload = json_encode(['entry' => [['changes' => [['value' => [
            'contacts' => [['profile' => ['name' => 'Admin'], 'wa_id' => $fromDigits]],
            'messages' => [[
                'from' => $fromDigits,
                'id' => 'wamid.in.'.uniqid(),
                'type' => 'text',
                'text' => ['body' => $text],
            ]],
        ]]]]]], JSON_UNESCAPED_UNICODE);

        return $this->call('POST', '/api/whatsapp/inbound', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-Hub-Signature-256' => 'sha256='.hash_hmac('sha256', $payload, $secret),
        ], $payload);
    }

    protected function relaxMessageDeliveryLogForInbound(): void
    {
        if (! Schema::hasTable('message_delivery_log')) {
            return;
        }

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
