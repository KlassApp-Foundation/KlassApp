<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyMarketingRedirectsTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_marketing_paths_301_to_the_new_landing(): void
    {
        $map = [
            '/landing'  => '/',
            '/landing2' => '/',
            '/features' => '/',
            '/pricing'  => '/?source=sales#demo',
            '/schools'  => '/',
            '/demo'     => '/#demo',
            '/contact'  => '/#demo',
        ];

        foreach ($map as $path => $target) {
            $response = $this->get($path);

            $this->assertSame(301, $response->getStatusCode(), "Expected 301 for {$path}");
            $location = (string) $response->headers->get('Location');

            if ($path === '/pricing') {
                $this->assertStringContainsString('source=sales', $location, 'Pricing must land on the sales lead form');
                $this->assertStringContainsString('#demo', $location, "Literal fragment required for {$path}");
                $this->assertStringNotContainsString('%23', $location, "Fragment must not be percent-encoded for {$path}");
                continue;
            }

            $response->assertRedirect($target);

            if (str_contains($target, '#')) {
                $this->assertStringContainsString('#demo', $location, "Literal fragment required for {$path}");
                $this->assertStringNotContainsString('%23', $location, "Fragment must not be percent-encoded for {$path}");
            }
        }
    }

    public function test_post_contact_form_handler_is_unchanged(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $response = $this->post('/contact', [
            'fullname'     => 'Redirect Test Contact',
            'emailid'      => 'redirect-test@example.test',
            'school_name'  => 'Redirect Test School',
            'message'      => 'Sent while verifying legacy redirects.',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/contact?sent=true#contact');
        $this->assertDatabaseHas('contacts', [
            'fullname' => 'Redirect Test Contact',
            'email'    => 'redirect-test@example.test',
        ]);
    }

    public function test_demo_routes_under_prefix_still_resolve_to_their_controllers(): void
    {
        $schoolList = $this->get('/demo/schoolList');
        $this->assertSame(200, $schoolList->getStatusCode(), 'schoolList must return its JSON, not a redirect');
        $schoolList->assertJsonStructure(['data']);

        $roster = $this->get('/demo/list/999999');
        $this->assertSame(404, $roster->getStatusCode(), 'Unknown demo school id must 404 from the controller, not 301');
    }

    public function test_school_page_route_still_resolves(): void
    {
        $response = $this->get('/schools/no-such-school');
        $this->assertSame(404, $response->getStatusCode(), 'Unknown slug must 404 from SchoolPageController, not 301');
    }

    public function test_landing_preview_redirect_still_works(): void
    {
        $this->get('/landing-preview')->assertStatus(301)->assertRedirect('/');
    }
}
