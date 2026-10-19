<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

/**
 * The Meta webhook handshake accepts only a configured verify token.
 * An unset token, an empty token, and a mismatch all fail closed.
 */
class WhatsAppWebhookVerifyTokenTest extends TestCase
{
    private function handshake(?string $token, string $challenge = 'challenge-ok'): \Illuminate\Testing\TestResponse
    {
        $query = 'hub.mode=subscribe&hub.challenge='.urlencode($challenge);
        if ($token !== null) {
            $query .= '&hub.verify_token='.urlencode($token);
        }

        return $this->get('/api/whatsapp/inbound?'.$query);
    }

    public function test_handshake_succeeds_when_the_configured_token_matches(): void
    {
        config(['services.whatsapp.business_verify_token' => 'configured-token']);

        $this->handshake('configured-token')
            ->assertStatus(200)
            ->assertSee('challenge-ok');
    }

    public function test_handshake_fails_when_the_token_is_unset(): void
    {
        config(['services.whatsapp.business_verify_token' => null]);

        $this->handshake('anything')->assertStatus(403);
        $this->handshake(null)->assertStatus(403);
        $this->handshake('')->assertStatus(403);
    }

    public function test_handshake_fails_when_the_token_is_empty(): void
    {
        config(['services.whatsapp.business_verify_token' => '']);

        $this->handshake('')->assertStatus(403);
        $this->handshake('anything')->assertStatus(403);
    }

    public function test_handshake_fails_when_the_token_does_not_match(): void
    {
        config(['services.whatsapp.business_verify_token' => 'configured-token']);

        $this->handshake('other-token')->assertStatus(403);
        $this->handshake('')->assertStatus(403);
    }
}
