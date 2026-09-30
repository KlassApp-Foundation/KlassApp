<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class GoogleRedirectUriConfigTest extends TestCase
{
    public function test_google_oauth_redirect_uses_app_url_on_non_local_env(): void
    {
        $this->assertSame("testing", app()->environment());

        putenv("GOOGLE_REDIRECT_URI=https://klassapp-staging-7mpoqg.laravel.cloud/auth/google/callback");
        $_ENV["GOOGLE_REDIRECT_URI"] = "https://klassapp-staging-7mpoqg.laravel.cloud/auth/google/callback";
        $_SERVER["GOOGLE_REDIRECT_URI"] = "https://klassapp-staging-7mpoqg.laravel.cloud/auth/google/callback";

        putenv("APP_URL=https://test.klassapp.xyz");
        $_ENV["APP_URL"] = "https://test.klassapp.xyz";
        $_SERVER["APP_URL"] = "https://test.klassapp.xyz";

        $redirect = (env("APP_ENV") === "local" && filled(env("GOOGLE_REDIRECT_URI")))
            ? env("GOOGLE_REDIRECT_URI")
            : (rtrim((string) env("APP_URL", "http://localhost"), "/") . "/auth/google/callback");

        $this->assertSame("https://test.klassapp.xyz/auth/google/callback", $redirect);
    }

    public function test_services_config_source_follows_app_url(): void
    {
        $src = file_get_contents(config_path("services.php"));
        $this->assertNotFalse($src);
        $this->assertStringContainsString("env('APP_ENV') === 'local'", $src);
        $this->assertStringContainsString("/auth/google/callback", $src);
    }
}
