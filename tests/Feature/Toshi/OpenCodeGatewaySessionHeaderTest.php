<?php

namespace Tests\Feature\Toshi;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenCodeGatewaySessionHeaderTest extends TestCase
{
    public function test_session_header_attached_to_opencode_gateway_requests(): void
    {
        config([
            'services.opencode_gateway.session_id' => 'test-session-uuid-123',
            'ai.providers.openai-compatible.url' => 'https://opencode.ai/zen/go/v1',
        ]);

        Http::fake(['*' => Http::response(['ok' => true])]);

        Http::post('https://opencode.ai/zen/go/v1/chat/completions', [
            'model' => 'glm-5.3-flash',
            'messages' => [['role' => 'user', 'content' => 'hi']],
        ]);

        Http::assertSent(fn ($request) => $request->hasHeader('x-opencode-session', 'test-session-uuid-123'));
    }

    public function test_other_hosts_do_not_receive_the_session_header(): void
    {
        config([
            'services.opencode_gateway.session_id' => 'test-session-uuid-123',
            'ai.providers.openai-compatible.url' => 'https://opencode.ai/zen/go/v1',
        ]);

        Http::fake(['*' => Http::response(['ok' => true])]);

        Http::post('https://api.deepseek.com/chat/completions', ['model' => 'deepseek-chat']);

        Http::assertSent(fn ($request) => ! $request->hasHeader('x-opencode-session'));
    }

    public function test_no_header_when_session_id_unconfigured(): void
    {
        config([
            'services.opencode_gateway.session_id' => null,
            'ai.providers.openai-compatible.url' => 'https://opencode.ai/zen/go/v1',
        ]);

        Http::fake(['*' => Http::response(['ok' => true])]);

        Http::post('https://opencode.ai/zen/go/v1/chat/completions', ['model' => 'glm-5.3-flash']);

        Http::assertSent(fn ($request) => ! $request->hasHeader('x-opencode-session'));
    }
}
