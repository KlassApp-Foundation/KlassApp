<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingAuthErrorCutoverTest extends TestCase
{
    public function test_home_serves_landing_v2_with_protocol_cores_and_vintage_hero(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Protocol Cores', false);
        $response->assertSee('Human in the loop', false);
        $response->assertSee('hero-bg-vintage', false);
        $response->assertSee('site-footer', false);
        $response->assertSee('Smarter schools start here.', false);
        $response->assertDontSee('id="community"', false);
        $response->assertDontSee('noindex,nofollow', false);
        $response->assertSee('build/assets/landing-preview-', false);
        $response->assertSee('family=Sora', false);
        $response->assertSee('family=DM+Sans', false);
        $this->assertStringNotContainsString('Bricolage', $response->getContent());
        $this->assertStringNotContainsString('family=Inter', $response->getContent());
    }

    public function test_legacy_landing_preview_redirects_to_home(): void
    {
        $this->get('/landing-preview')->assertRedirect('/');
    }

    public function test_live_login_and_register_use_vintage_auth_shells(): void
    {
        $login = $this->get('/login');
        $login->assertOk();
        $login->assertSee('data-ap-paper="vintage"', false);
        $login->assertSee('data-ap-layout="split"', false);
        $login->assertSee('data-ap-screen="login"', false);
        $login->assertSee('action="/login"', false);
        $login->assertSee('href="'.url('/auth/google').'"', false);
        $login->assertDontSee('ap-preview-badge', false);
        $login->assertSee('family=Sora', false);

        $register = $this->get('/register');
        $register->assertOk();
        $register->assertSee('data-ap-screen="register"', false);
        $register->assertSee('formnovalidate', false);
        $register->assertSee('/auth/google/start', false);
        $register->assertDontSee('ap-preview-badge', false);
        $this->assertStringContainsString('no-store', (string) $register->headers->get('Cache-Control'));
    }

    public function test_live_password_reset_pages_use_new_shells(): void
    {
        $this->get('/password/reset')->assertOk()
            ->assertSee('data-ap-screen="reset-request"', false)
            ->assertSee('action="'.url('/password/reset').'"', false);

        $this->get('/password/reset-code?email=cutover@klassapp.test')->assertOk()
            ->assertSee('data-ap-screen="reset-code"', false)
            ->assertSee('pattern="[0-9]{6}"', false)
            ->assertSee('cutover@klassapp.test');

        $this->get('/password/reset/preview-token?email=cutover@klassapp.test')->assertOk()
            ->assertSee('data-ap-screen="reset-newpw"', false)
            ->assertSee('name="token"', false);
    }

    public function test_live_404_and_419_use_vintage_error_shells(): void
    {
        $notFound = $this->get('/this-route-definitely-does-not-exist-cutover-check');
        $notFound->assertNotFound();
        $notFound->assertSee('data-error-shell="pass2"', false);
        $notFound->assertSee('data-error-paper="vintage"', false);
        $notFound->assertSee('data-error-code="404"', false);
        $notFound->assertSee('Page Not Found');
        $notFound->assertDontSee('>Preview<', false);

        $expired = $this->get('/preview/errors/419');
        $expired->assertOk();
        $expired->assertSee('data-error-code="419"', false);
        $expired->assertSee('Page Expired');
    }
}
