<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthPreviewTest extends TestCase
{
    public function test_preview_login_renders_fields_and_google_get(): void
    {
        $response = $this->get('/preview/login');

        $response->assertOk();
        $response->assertSee('data-ap-screen="login"', false);
        $response->assertSee('name="email"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('name="remember"', false);
        $response->assertSee('action="/login"', false);
        $response->assertSee('href="'.url('/auth/google').'"', false);
        $response->assertSee('build/assets/auth-preview-', false);
        $response->assertSee('ap-password-toggle', false);
    }

    public function test_preview_register_preserves_google_post_asymmetry(): void
    {
        $response = $this->get('/preview/register');

        $response->assertOk();
        $response->assertSee('data-ap-screen="register"', false);
        $response->assertSee('name="name"', false);
        $response->assertSee('name="email"', false);
        $response->assertSee('name="phone"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('name="password_confirmation"', false);
        $response->assertSee('name="termsandcondn"', false);
        $response->assertSee('formaction="'.route('auth.google.start').'"', false);
        $response->assertSee('formnovalidate', false);
        $response->assertSee('ap-password-toggle', false);
    }

    public function test_preview_reset_request_and_code_keep_single_input(): void
    {
        $this->get('/preview/reset-request')->assertOk()
            ->assertSee('name="email"', false)
            ->assertSee('action="'.url('/password/reset').'"', false);

        $code = $this->get('/preview/reset-code');
        $code->assertOk();
        $code->assertSee('name="code"', false);
        $code->assertSee('pattern="[0-9]{6}"', false);
        $code->assertSee('maxlength="6"', false);
        $code->assertSee('grace@school.ug');
        $code->assertDontSee('code-box');
        $code->assertDontSee('Digit 1');
    }

    public function test_preview_reset_newpw_and_force_change_rules(): void
    {
        $this->get('/preview/reset-newpw')->assertOk()
            ->assertSee('name="token"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false);

        $force = $this->get('/preview/force-change-password');
        $force->assertOk();
        $force->assertSee('name="current_password"', false);
        $force->assertSee('At least 8 characters');
        $force->assertSee('One uppercase letter');
        $force->assertSee('One lowercase letter');
        $force->assertSee('One number');
        $force->assertSee('One special character');
        $force->assertDontSee('Skip');
        $force->assertDontSee('Cancel');
        $force->assertDontSee('Sign out');
        $force->assertDontSee('sign out', false);
    }

    public function test_preview_demo_errors_show_pass2_error_banner(): void
    {
        $response = $this->get('/preview/login?demo_errors=1');

        $response->assertOk();
        $response->assertSee('data-testid="auth-flash-error"', false);
        $response->assertSee('These credentials do not match our records.');
    }

    public function test_preview_pages_use_vintage_paper_and_sora_not_bricolage(): void
    {
        foreach (['login', 'register', 'reset-request', 'reset-code', 'reset-newpw', 'force-change-password'] as $screen) {
            $response = $this->get('/preview/'.$screen);

            $response->assertOk();
            $response->assertSee('data-ap-paper="vintage"', false);
            $response->assertSee('data-ap-layout="split"', false);
            $response->assertSee('ap-bg-vintage', false);
            $response->assertSee('ap-brand-panel', false);
            $response->assertSee('ap-brand-row', false);
            $response->assertSee('ap-brand-copy', false);
            $response->assertSee('ap-form-shell', false);
            $response->assertSee('family=Sora', false);
            $response->assertSee('family=DM+Sans', false);
            $response->assertDontSee('Bricolage', false);
            $response->assertDontSee("\u{2014}");
        }
    }

    public function test_preview_login_form_posts_to_real_login_validation(): void
    {
        $response = $this->from('/preview/login')->post('/login', [
            'email' => '',
            'password' => '',
        ]);

        $response->assertRedirect('/preview/login');
        $response->assertSessionHasErrors(['email', 'password']);
    }

    public function test_auth_preview_css_uses_canonical_canvas_ink_and_ds_btn_metrics(): void
    {
        $css = file_get_contents(resource_path('css/auth-preview.css'));

        $this->assertMatchesRegularExpression('/--ap-bg:\s*#FAFAF5/i', $css);
        $this->assertMatchesRegularExpression('/--ap-ink:\s*#1E293B/i', $css);
        $this->assertMatchesRegularExpression('/--paper-base:\s*#FAFAF5/i', $css);
        $this->assertMatchesRegularExpression('/--ap-focus:\s*#1E6FD9/i', $css);
        $this->assertMatchesRegularExpression('/--ap-focus-ring:\s*rgba\(\s*30,\s*111,\s*217,\s*0\.25\s*\)/i', $css);
        $this->assertMatchesRegularExpression('/\.ap-submit\s*\{[^}]*padding:\s*8px\s+18px;/s', $css);
        $this->assertMatchesRegularExpression('/\.ap-submit\s*\{[^}]*min-height:\s*44px;/s', $css);
        $this->assertMatchesRegularExpression('/\.ap-submit\s*\{[^}]*font-size:\s*0\.85rem;/s', $css);
        $this->assertMatchesRegularExpression('/\.ap-submit:focus-visible\s*\{[^}]*outline:\s*2px\s+solid\s+var\(--ap-focus\)/s', $css);
        $this->assertDoesNotMatchRegularExpression('/--ap-bg:\s*#F5F0E6/i', $css);
        $this->assertDoesNotMatchRegularExpression('/--ap-ink:\s*#0F172A/i', $css);
        $this->assertDoesNotMatchRegularExpression('/--paper-base:\s*#F5F0E6/i', $css);
        $this->assertStringNotContainsString('0 0 0 3px rgba(34, 197, 94', $css);
        $this->assertStringNotContainsString('0 10px 18px rgba(34, 197, 94, 0.2)', $css);
        $this->assertDoesNotMatchRegularExpression('/\.ap-submit\s*\{[^}]*padding:\s*14px;/s', $css);
        $this->assertDoesNotMatchRegularExpression('/\.ap-submit\s*\{[^}]*min-height:\s*50px;/s', $css);
    }
}
