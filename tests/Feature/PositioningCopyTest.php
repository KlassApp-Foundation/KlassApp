<?php

namespace Tests\Feature;

use Tests\TestCase;

class PositioningCopyTest extends TestCase
{
    private const TAGLINE = 'An open education protocol for humans and agents.';

    private const DESCRIPTION = 'KlassApp is an education protocol that runs in the tools educationists already use. Admins manage school operations in Slack, teachers enter marks from spreadsheets, and parents receive their children\'s school updates on WhatsApp, all by chatting in natural language with Toshi, your school\'s AI assistant.';

    public function test_live_landing_uses_locked_tagline_and_description(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee(self::TAGLINE, false);
        $response->assertSee(self::DESCRIPTION, false);
        $response->assertDontSee("Educationists' tools connected by intelligence.", false);
        $response->assertDontSee('Built for African schools.', false);
    }

    public function test_package_manifests_carry_locked_positioning(): void
    {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true);
        $package = json_decode(file_get_contents(base_path('package.json')), true);

        $this->assertStringContainsString(self::TAGLINE, (string) ($composer['description'] ?? ''));
        $this->assertStringContainsString(self::TAGLINE, (string) ($package['description'] ?? ''));
    }
}
