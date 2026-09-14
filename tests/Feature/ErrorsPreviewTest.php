<?php

namespace Tests\Feature;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ErrorsPreviewTest extends TestCase
{
    public function test_preview_error_routes_render_pass2_shells(): void
    {
        foreach ([404, 419, 500] as $code) {
            $response = $this->get("/preview/errors/{$code}");

            $response->assertOk();
            $response->assertSee('data-error-shell="pass2"', false);
            $response->assertSee('data-error-paper="vintage"', false);
            $response->assertSee('data-error-layout="split"', false);
            $response->assertSee('err-bg-vintage', false);
            $response->assertSee('err-brand-panel', false);
            $response->assertSee('err-brand-row', false);
            $response->assertSee('err-brand-copy', false);
            $response->assertSee('data-error-code="'.$code.'"', false);
            $response->assertSee('Error '.$code);
            $response->assertSee('Preview');
            $response->assertSee('data-testid="exception-present"', false);
            $response->assertSee('family=Sora', false);
            $response->assertSee('--paper-base', false);
            $response->assertSee('repeating-linear-gradient', false);
            $response->assertDontSee('Bricolage', false);
            $response->assertDontSee("\u{2014}");
        }
    }

    public function test_preview_404_copy_and_actions(): void
    {
        $response = $this->get('/preview/errors/404');

        $response->assertOk();
        $response->assertSee('Page Not Found');
        $response->assertSee('could not be found');
        $response->assertSee('Go to home');
        $response->assertSee('Go back');
        $response->assertSee('err-icon-blue', false);
    }

    public function test_preview_419_verbatim_copy_and_amber_refresh(): void
    {
        $response = $this->get('/preview/errors/419');

        $response->assertOk();
        $response->assertSee('Page Expired');
        $response->assertSee('Your session has expired. Please refresh the page and try again.');
        $response->assertSee('no changes have been lost');
        $response->assertSee('Refresh and try again');
        $response->assertSee('Return to home');
        $response->assertSee('err-btn-amber', false);
        $response->assertSee('err-icon-amber', false);
    }

    public function test_preview_500_does_not_leak_exception_message(): void
    {
        $response = $this->get('/preview/errors/500');

        $response->assertOk();
        $response->assertSee('Server Error');
        $response->assertSee('Something went wrong on our end');
        $response->assertSee('Try again');
        $response->assertDontSee('Preview: synthetic HttpException');
        $response->assertDontSee('TypeError');
        $response->assertDontSee('$schoolId');
    }

    public function test_real_404_uses_pass2_vintage_shell_after_cutover(): void
    {
        $response = $this->get('/this-route-definitely-does-not-exist-phase-c-'.uniqid());

        $response->assertNotFound();
        $response->assertSee('data-error-shell="pass2"', false);
        $response->assertSee('data-error-paper="vintage"', false);
        $response->assertSee('err-card', false);
        $response->assertSee('Page Not Found');
        $response->assertDontSee('klass-error-shell', false);
        $response->assertDontSee('>Preview<', false);
    }

    public function test_preview_500_view_is_structurally_sound_with_http_exception(): void
    {
        $exception = new HttpException(
            500,
            'TypeError: App\\Services\\Foo::bar(): Argument #1 ($schoolId) must be of type int, null given'
        );

        $html = view('errors-preview.500', [
            'errors' => new \Illuminate\Support\ViewErrorBag,
            'exception' => $exception,
            'isPreview' => true,
        ])->render();

        $this->assertStringContainsString('data-error-shell="pass2"', $html);
        $this->assertStringContainsString('Server Error', $html);
        $this->assertStringContainsString('Something went wrong on our end', $html);
        $this->assertStringContainsString('data-testid="exception-present"', $html);
        $this->assertStringContainsString(HttpException::class, $html);
        $this->assertStringNotContainsString('TypeError', $html);
        $this->assertStringNotContainsString('$schoolId', $html);
        $this->assertStringNotContainsString('must be of type int', $html);
    }

    public function test_live_500_view_uses_pass2_shell_and_does_not_leak_exception_text(): void
    {
        $exception = new HttpException(
            500,
            'TypeError: App\\Services\\Foo::bar(): Argument #1 ($schoolId) must be of type int, null given'
        );

        $html = view('errors.500', [
            'errors' => new \Illuminate\Support\ViewErrorBag,
            'exception' => $exception,
        ])->render();

        $this->assertStringContainsString('data-error-shell="pass2"', $html);
        $this->assertStringContainsString('Server Error', $html);
        $this->assertStringContainsString('Something went wrong on our end', $html);
        $this->assertStringNotContainsString('klass-error-shell', $html);
        $this->assertStringNotContainsString('TypeError', $html);
        $this->assertStringNotContainsString('$schoolId', $html);
    }

    public function test_preview_rejects_unknown_error_codes(): void
    {
        $this->get('/preview/errors/503')->assertNotFound();
        $this->get('/preview/errors/abc')->assertNotFound();
    }
}
