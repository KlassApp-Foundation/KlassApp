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
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
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
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'min:2', 'max:100', "regex:/^[\pL\s'\-]+$/u"],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => [
                'required',
                'string',
                'max:20',
                'regex:/^(\+?256)?0?7[0578]\d{7}$/',
            ],
            'termsandcondn' => ['required', 'accepted'],
        ], [
            'phone.required' => 'Phone (WhatsApp) is required.',
            'termsandcondn.accepted' => 'Please agree to the Terms and Conditions.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('register')
                ->withErrors($validator)
                ->withInput($request->except(['password', 'password_confirmation']));
        }

        $phone = $bootstrap->normalizePhone($request->input('phone'));
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

        session([
            'saas_signup' => [
                'name' => trim($request->input('name')),
                'email' => trim($request->input('email')),
                'phone' => $phone,
            ],
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

            return redirect('/admin/dashboard')
                ->with('open_toshi_onboarding', true)
                ->with('successmessage', 'Welcome to KlassApp! Continue setup with Toshi.');
        } catch (Throwable $e) {
            Log::error('Google auth user creation failed', [
                'message' => $e->getMessage(),
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

        return redirect('/admin/dashboard')
            ->with('open_toshi_onboarding', true);
    }

    public function storeOnboarding(Request $request)
    {
        return $this->showOnboarding($request);
    }

    private function postAuthRedirect(User $user)
    {
        $school = $user->school;
        $needsSetup = $school && (
            $school->curriculum === null
            || $school->curriculum === ''
            || ! \App\Models\AcademicYear::where('school_id', $school->id)->exists()
        );

        if ($needsSetup) {
            return redirect('/admin/dashboard')
                ->with('open_toshi_onboarding', true);
        }

        return redirect('/admin/dashboard');
    }

    private function updateGoogleData(User $user, $googleUser): void
    {
        $user->google_id = $googleUser->getId();
        $user->google_avatar = $googleUser->getAvatar();

        if ((int) $user->email_verified === 0) {
            $user->email_verified = 1;
            $user->email_verified_at = Carbon::now();
        }

        $user->save();
    }
}
