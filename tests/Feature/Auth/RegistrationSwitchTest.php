<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

/**
 * Regression guard for the inverted register_status gate.
 *
 * settings.register_status is documented and seeded as 1 = open, 0 = closed
 * (see SettingsTableSeeder and UpdateSystemSettingsTool). Both register views
 * rendered the maintenance notice when the flag was 1, which closed public
 * sign-up on production while the platform believed it was open. Do not let this
 * inversion come back: 1 must render the real form, 0 must render maintenance.
 */
class RegistrationSwitchTest extends TestCase
{
    public function test_register_status_one_shows_the_real_signup_form(): void
    {
        config(['settings.register_status' => '1']);

        $response = $this->get('/register');

        $response->assertOk();
        $response->assertSee('saas-register-form', false);
        $response->assertSee('name="email"', false);
        $response->assertDontSee('under maintenance');
    }

    public function test_register_status_zero_shows_maintenance_and_not_the_form(): void
    {
        config(['settings.register_status' => '0']);

        $response = $this->get('/register');

        $response->assertOk();
        $response->assertSee('under maintenance');
        // The layout's own footer script mentions the form id, so assert on the
        // submit button instead: only the real form renders it.
        $response->assertDontSee('Create account with password');
    }

    public function test_missing_register_status_stays_open(): void
    {
        // Default-safe: staging ships zero settings rows, and a missing row must not
        // close sign-up by accident.
        config(['settings.register_status' => null]);

        $response = $this->get('/register');

        $response->assertOk();
        $response->assertSee('saas-register-form', false);
        $response->assertDontSee('under maintenance');
    }

    public function test_both_register_views_agree_with_the_flag(): void
    {
        // Rendered straight from a test, the shared $errors bag is absent; both views
        // read it, so hand them an empty bag.
        $errors = new \Illuminate\Support\ViewErrorBag;

        foreach (['auth.register', 'auth.preview.register'] as $view) {
            config(['settings.register_status' => '1']);
            $open = view($view, compact('errors'))->render();

            config(['settings.register_status' => '0']);
            $closed = view($view, compact('errors'))->render();

            $this->assertStringContainsString('saas-register-form', $open, $view.' must show the form when register_status=1');
            $this->assertStringNotContainsString('under maintenance', $open, $view.' must not show maintenance when register_status=1');
            $this->assertStringContainsString('under maintenance', $closed, $view.' must show maintenance when register_status=0');
            $this->assertStringNotContainsString('Create account with password', $closed, $view.' must not show the form when register_status=0');
        }
    }
}
