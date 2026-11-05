<?php

namespace Tests\Feature\Account;

use App\Http\Middleware\AuthenticateSessionUnlessImpersonating;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\Support\AccountRoleFixture;
use Tests\TestCase;

/** Impersonation swaps the user for a request, so it must not be compared with the session's stored password hash. */
class AuthenticateSessionUnlessImpersonatingTest extends TestCase
{
    use AccountRoleFixture;
    use RefreshDatabase;

    private function request(array $session): Request
    {
        $request = Request::create('/teacher/dashboard', 'GET');
        $store = $this->app['session']->driver();
        $store->put($session);
        $request->setLaravelSession($store);
        $request->setUserResolver(fn ($guard = null) => Auth::guard($guard)->user());

        return $request;
    }

    public function test_a_mismatched_password_hash_signs_the_session_out(): void
    {
        $this->buildRoleFixture();
        Auth::guard()->setUser($this->userFor(5));

        $this->expectException(AuthenticationException::class);
        (new AuthenticateSessionUnlessImpersonating($this->app['auth']))
            ->handle($this->request(['password_hash_web' => 'stale-hash-from-before-a-password-change']), fn () => response('ok'));
    }

    public function test_the_same_mismatch_is_ignored_while_impersonating(): void
    {
        $this->buildRoleFixture();
        Auth::guard()->setUser($this->userFor(5));

        $response = (new AuthenticateSessionUnlessImpersonating($this->app['auth']))
            ->handle($this->request(['password_hash_web' => 'stale-hash', 'impersonate' => 123]), fn () => response('ok'));

        $this->assertSame('ok', $response->getContent());
    }

    public function test_a_session_that_now_belongs_to_another_user_is_restamped_not_signed_out(): void
    {
        $this->buildRoleFixture();
        $first = $this->userFor(5);
        $second = $this->userFor(6);
        $middleware = new AuthenticateSessionUnlessImpersonating($this->app['auth']);

        // The session was stamped for the first user...
        Auth::guard()->setUser($first);
        $request = $this->request([]);
        $middleware->handle($request, fn () => response('ok'));
        $firstStamp = $request->session()->get('password_hash_web');

        // ...then a different user signs in on the same session: re-stamped, not signed out.
        Auth::guard()->setUser($second);
        $response = $middleware->handle($request, fn () => response('ok'));

        $this->assertSame('ok', $response->getContent());
        $this->assertNotSame($firstStamp, $request->session()->get('password_hash_web'));

        // The same user whose password hash changed is still signed out (the password-change case).
        $second->forceFill(['password' => bcrypt('changed-elsewhere')])->save();
        $this->expectException(AuthenticationException::class);
        $middleware->handle($request, fn () => response('ok'));
    }

    public function test_a_first_request_with_no_stored_hash_is_accepted_and_stamps_the_session(): void
    {
        $this->buildRoleFixture();
        Auth::guard()->setUser($this->userFor(5));
        $request = $this->request([]);

        $response = (new AuthenticateSessionUnlessImpersonating($this->app['auth']))->handle($request, fn () => response('ok'));

        $this->assertSame('ok', $response->getContent());
        $this->assertNotNull($request->session()->get('password_hash_web'));
    }
}
