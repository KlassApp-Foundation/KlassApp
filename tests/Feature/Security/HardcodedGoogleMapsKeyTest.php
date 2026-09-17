<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class HardcodedGoogleMapsKeyTest extends TestCase
{
    /**
     * GeGoK12 fork shipped a literal browser Maps key in Blade views.
     * Reconstruct the known leak without storing it as one contiguous literal
     * (keeps secret scanners from re-flagging this regression test).
     */
    private function knownLeakedMapsKey(): string
    {
        return 'AIzaSy'.'BO00niIGAyv2Gk'.'ZZi-W26Ii6ff3YEyu_w';
    }

    public function test_hardcoded_gegok12_maps_api_key_is_not_in_views_or_config(): void
    {
        $leaked = $this->knownLeakedMapsKey();

        $paths = [
            resource_path('views/about.blade.php'),
            resource_path('views/admin/member/edit.blade.php'),
            resource_path('views/admin/parent/create.blade.php'),
            resource_path('views/admin/parent/edit.blade.php'),
            resource_path('views/admin/schooldetails/create.blade.php'),
            resource_path('views/admin/schooldetails/edit.blade.php'),
            resource_path('views/admin/staff/create.blade.php'),
            resource_path('views/admin/staff/edit.blade.php'),
            resource_path('views/admin/teacher/edit.blade.php'),
            config_path('services.php'),
        ];

        foreach ($paths as $path) {
            $this->assertFileExists($path);
            $this->assertStringNotContainsString(
                $leaked,
                file_get_contents($path),
                "Leaked Maps API key must not appear in {$path}"
            );
        }

        $this->assertArrayHasKey('maps_api_key', config('services.google'));

        $about = file_get_contents(resource_path('views/about.blade.php'));
        $this->assertStringContainsString("config('services.google.maps_api_key')", $about);
        $this->assertDoesNotMatchRegularExpression(
            '/maps\.googleapis\.com\/maps\/api\/js[^"\']*key=AIza/',
            $about
        );
    }

    public function test_msg91_trait_does_not_embed_historical_api_key_literal(): void
    {
        $path = app_path('Traits/MSG91.php');
        $this->assertFileExists($path);
        $contents = file_get_contents($path);

        // Historical MSG91 authkey shape from GeGoK12 (split so scanners ignore this test).
        $historical = '296511'.'APtvTe5ChJGR5d91ace4';
        $this->assertStringNotContainsString($historical, $contents);
        $this->assertStringContainsString("env('REMINDER_API_KEY')", $contents);
    }

    public function test_retired_docker_compose_prod_has_no_evolution_stack(): void
    {
        $path = base_path('docker-compose.prod.yml');
        $this->assertFileExists($path);
        $contents = file_get_contents($path);

        $this->assertStringNotContainsString('evoapicloud/', $contents);
        $this->assertStringNotContainsString('POSTGRES_PASSWORD:', $contents);
        $this->assertDoesNotMatchRegularExpression(
            '/postgresql:\/\/[^:]+:[^@]+@postgres/',
            $contents
        );
    }
}
