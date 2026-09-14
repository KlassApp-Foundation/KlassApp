<?php

namespace Tests\Feature;

use Tests\TestCase;

class FaviconBrandAssetsTest extends TestCase
{
    /**
     * GeGo leftover favicons were orange-dominant (~RGB 224,64,32).
     * KlassApp icons must be green-dominant from klassapp-logo.svg.
     */
    public function test_login_page_head_links_klassapp_favicon_set(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('images/klassapp-logo.svg', false);
        $response->assertSee('favicon/favicon-32x32.png', false);
        $response->assertSee('favicon/favicon-16x16.png', false);
        $response->assertSee('favicon/apple-icon-180x180.png', false);
        $response->assertSee('favicon/manifest.json', false);
        $response->assertSee('favicon/browserconfig.xml', false);
        $response->assertSee('theme-color" content="#199D52"', false);
        $response->assertDontSee('asset(\'favicon.svg\')', false);
    }

    public function test_home_page_includes_full_favicon_partial(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('favicon/apple-icon-180x180.png', false);
        $response->assertSee('favicon/manifest.json', false);
        $response->assertSee('type="image/svg+xml"', false);
    }

    public function test_favicon_and_manifest_asset_files_exist(): void
    {
        $paths = [
            'images/klassapp-logo.svg',
            'favicon.svg',
            'favicon.ico',
            'favicon/favicon.svg',
            'favicon/favicon.ico',
            'favicon/favicon-16x16.png',
            'favicon/favicon-32x32.png',
            'favicon/apple-icon-180x180.png',
            'favicon/android-icon-192x192.png',
            'favicon/android-icon-512x512.png',
            'favicon/manifest.json',
            'favicon/browserconfig.xml',
            'images/favicon.png',
        ];

        foreach ($paths as $path) {
            $this->assertFileExists(public_path($path), "Missing public asset: {$path}");
        }
    }

    public function test_web_manifest_points_at_relative_klassapp_icons(): void
    {
        $manifestPath = public_path('favicon/manifest.json');
        $this->assertFileExists($manifestPath);

        $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('KlassApp', $manifest['name']);
        $this->assertSame('#199D52', $manifest['theme_color']);
        $this->assertNotEmpty($manifest['icons']);

        $sizes = [];
        foreach ($manifest['icons'] as $icon) {
            $this->assertStringStartsNotWith('/', $icon['src'], 'Manifest icon src must be relative to /favicon/');
            $this->assertFileExists(public_path('favicon/'.$icon['src']));
            $sizes[] = $icon['sizes'];
        }

        $this->assertContains('192x192', $sizes);
        $this->assertContains('512x512', $sizes);
    }

    public function test_png_favicons_are_klassapp_green_not_gego_orange(): void
    {
        $samples = [
            public_path('favicon/favicon-32x32.png'),
            public_path('favicon/apple-icon-180x180.png'),
            public_path('favicon/android-icon-192x192.png'),
            public_path('images/favicon.png'),
        ];

        foreach ($samples as $path) {
            [$green, $orange] = $this->sampleGreenVsOrange($path);
            $this->assertGreaterThan(
                $orange,
                $green,
                basename($path).' still looks GeGo-orange (green='.$green.', orange='.$orange.')'
            );
        }
    }

    /**
     * @return array{0:int,1:int}
     */
    private function sampleGreenVsOrange(string $path): array
    {
        $image = imagecreatefrompng($path);
        $this->assertNotFalse($image, 'Could not read PNG: '.$path);

        $width = imagesx($image);
        $height = imagesy($image);
        $green = 0;
        $orange = 0;
        $step = max(1, (int) floor(min($width, $height) / 32));

        for ($y = 0; $y < $height; $y += $step) {
            for ($x = 0; $x < $width; $x += $step) {
                $rgba = imagecolorat($image, $x, $y);
                $a = ($rgba & 0x7F000000) >> 24;
                // GD alpha: 0=opaque, 127=transparent
                if ($a > 100) {
                    continue;
                }
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;

                if ($g > $r + 20 && $g > $b) {
                    $green++;
                }
                if ($r > 160 && $g < 120 && $b < 100) {
                    $orange++;
                }
            }
        }

        imagedestroy($image);

        return [$green, $orange];
    }
}
