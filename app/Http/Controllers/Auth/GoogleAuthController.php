<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SchoolSignupBootstrapService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    /**
     * Stash signup fields, then redirect to Google OAuth.
     * Same bootstrap path as email/password signup after callback.
     */
    public function start(Request $request, SchoolSignupBootstrapService $bootstrap)
    {
        // Sign-up closed: the register page's Google button must not start a
        // new-account flow. Existing users sign in with GET /auth/google.
        if (! config('klassapp.signup_enabled')) {
            return redirect()->route('early-access.show');
        }

        $phone = $request->input('phone') ?: null;

        if ($phone !== null) {
            $phone = $bootstrap->normalizePhone($phone);
            if ($phone === null) {
                return redirect()->route('register')
                    ->withErrors(['phone' => 'Enter a valid WhatsApp phone number.'])
                    ->withInput();
            }

            if (User::where('mobile_no', $phone)->exists()) {
                return redirect()->route('register')
                    ->withErrors(['phone' => 'This phone number is already registered.'])
                    ->withInput();
            }
        }

        $sessionData = [
            'name' => $request->input('name') ? trim($request->input('name')) : null,
            'email' => $request->input('email') ? trim($request->input('email')) : null,
            'phone' => $phone,
        ];

        session([
            'saas_signup' => array_filter($sessionData, fn ($value) => $value !== null),
        ]);

        return Socialite::driver('google')->redirect();
    }

    /**
     * Redirect the user to Google's OAuth page (login / existing accounts).
     */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle the Google OAuth callback.
     */
    public function callback(SchoolSignupBootstrapService $bootstrap)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (Throwable $e) {
            Log::error('Google OAuth failed', ['message' => $e->getMessage()]);

            return redirect('/login')->with('failmessage', 'Google sign-in failed. Please try again.');
        }

        $googleId = $googleUser->getId();
        $googleEmail = $googleUser->getEmail();
        $googleName = $googleUser->getName() ?: 'Google User';
        $googleAvatar = $googleUser->getAvatar();

        if (! $googleEmail) {
            session()->forget('saas_signup');

            return redirect('/login')->with('failmessage', 'Google did not return an email address. Please try again or use email signup.');
        }

        $existingByGoogleId = User::where('google_id', $googleId)->first();
        if ($existingByGoogleId) {
            $this->updateGoogleData($existingByGoogleId, $googleUser);
            Auth::login($existingByGoogleId, true);
            session()->forget('saas_signup');

            return $this->postAuthRedirect($existingByGoogleId);
        }

        $existingByEmail = User::where('email', $googleEmail)->first();
        if ($existingByEmail) {
            $this->updateGoogleData($existingByEmail, $googleUser);
            Auth::login($existingByEmail, true);
            session()->forget('saas_signup');

            return $this->postAuthRedirect($existingByEmail);
        }

        // Sign-up closed: Google sign-in is for existing users only. An unknown
        // email never creates a school or an account.
        if (! config('klassapp.signup_enabled')) {
            session()->forget('saas_signup');
            Log::info('Google sign-in refused for unknown email: sign-up is closed');

            return redirect()->route('early-access.show')
                ->with('failmessage', 'There is no KlassApp account for that Google address yet. Request early access below.');
        }

        // Phone is optional on Google OAuth (Decision B): Google has no phone claim.
        // Email/password signup still requires WhatsApp phone. Prefer null over a
        // fake number; schools.phone UNIQUE allows multiple NULLs, not ''.
        $signup = session('saas_signup', []);
        $phone = $signup['phone'] ?? null;
        $adminName = trim((string) ($signup['name'] ?? '')) ?: $googleName;

        try {
            $user = $bootstrap->bootstrap([
                'name' => $adminName,
                'email' => $googleEmail,
                'phone' => $phone,
                'google_id' => $googleId,
                'google_avatar' => $googleAvatar,
                'email_verified' => true,
            ]);

            session()->forget('saas_signup');
            Auth::login($user, true);

            return redirect(\App\Helpers\AuthRedirectHelper::dashboardPathForUser($user))
                ->with('open_toshi_onboarding', true)
                ->with('successmessage', 'Welcome to KlassApp! Continue setup with Toshi.');
        } catch (Throwable $e) {
            Log::error('Google auth user creation failed', [
                'message' => 'Something went wrong. Please try again.',
                'exception' => $e::class,
            ]);
            session()->forget('saas_signup');

            return redirect('/login')->with('failmessage', 'We could not create your account. Please try again.');
        }
    }

    /**
     * Public marketing /welcome page remains untouched for pre-signup.
     * Legacy Google interstitial routes still resolve but send users to Toshi.
     */
    public function showOnboarding(Request $request)
    {
        if (! Auth::check()) {
            return redirect('/login');
        }

        $school = Auth::user()->school;
        $paths = app(\App\Services\Toshi\ToshiOnboardingPath::class);
        if ($school && ! $paths->openFor($school) && \App\Services\OnboardingEngine::setupDestination($school, Auth::id()) !== null) {
            return $paths->sendToWizard($school);
        }

        return redirect(\App\Helpers\AuthRedirectHelper::dashboardPathForUser(Auth::user()))
            ->with('open_toshi_onboarding', true);
    }

    public function storeOnboarding(Request $request)
    {
        return $this->showOnboarding($request);
    }

    private function postAuthRedirect(User $user)
    {
        $path = \App\Helpers\AuthRedirectHelper::dashboardPathForUser($user);

        // SiteAdmin has no school onboarding surface — always platform dashboard.
        if ((int) $user->usergroup_id === 1) {
            return redirect($path);
        }

        $school = $user->school;
        $needsSetup = $school && (
            $school->curriculum === null
            || $school->curriculum === ''
            || ! \App\Models\AcademicYear::where('school_id', $school->id)->exists()
        );

        if ($needsSetup) {
            return redirect($path)
                ->with('open_toshi_onboarding', true);
        }

        return redirect($path);
    }

    private function updateGoogleData(User $user, $googleUser): void
    {
        $user->google_id = $googleUser->getId();

        // Demo schools keep the name and avatar the seeder set. This method
        // never writes users.name. Avatar is the field a returning Google
        // login would otherwise replace (line below).
        $demoSchool = (int) ($user->school?->is_demo ?? 0) === 1;
        if (! $demoSchool) {
            $user->google_avatar = $googleUser->getAvatar();
        }

        if ((int) $user->email_verified === 0) {
            $user->email_verified = 1;
            $user->email_verified_at = Carbon::now();
        }

        $user->save();
    }
}
