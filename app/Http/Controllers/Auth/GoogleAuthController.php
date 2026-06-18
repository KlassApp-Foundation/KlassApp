<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Traits\AuthenticationProcess;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\AcademicYear;
use App\Models\Subscription;
use App\Models\Userprofile;
use App\Models\Usergroup;
use App\Models\School;
use App\Models\User;
use Carbon\Carbon;
use Throwable;

class GoogleAuthController extends Controller
{
    use AuthenticationProcess;

    /**
     * Redirect the user to Google's OAuth page.
     */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle the Google OAuth callback.
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            Log::error('Google OAuth failed', ['message' => $e->getMessage()]);
            return redirect('/login')->with('failmessage', 'Google sign-in failed. Please try again.');
        }

        $googleId = $googleUser->getId();
        $googleEmail = $googleUser->getEmail();
        $googleName = $googleUser->getName();
        $googleAvatar = $googleUser->getAvatar();

        // 1. Existing user by google_id — just log them in
        $existingByGoogleId = User::where('google_id', $googleId)->first();
        if ($existingByGoogleId) {
            $this->updateGoogleData($existingByGoogleId, $googleUser);
            Auth::login($existingByGoogleId, true);
            return redirect('/admin/dashboard');
        }

        // 2. Existing user by email — link Google account and log in
        $existingByEmail = User::where('email', $googleEmail)->first();
        if ($existingByEmail) {
            $this->updateGoogleData($existingByEmail, $googleUser);
            Auth::login($existingByEmail, true);
            return redirect('/admin/dashboard');
        }

        // 3. New user — create a minimal placeholder, then redirect to onboarding
        try {
            $user = DB::transaction(function () use ($googleName, $googleEmail, $googleId, $googleAvatar) {
                $baseName = (!empty($googleName) ? $googleName : 'Google User');

                // Placeholder school (will be updated during onboarding)
                $school = School::create([
                    'name' => $baseName . "'s School",
                    'email' => $googleEmail,
                    'phone' => '',
                    'slug' => Str::slug($baseName . '-' . Str::random(8)),
                    'country' => '',
                    'student_size' => '',
                    'status' => '1',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                $schoolAdminGroupId = Usergroup::where('id', 3)->exists()
                    ? 3
                    : (DB::table('usergroups')->orderBy('id')->value('id') ?? 3);

                $user = User::create([
                    'school_id' => $school->id,
                    'usergroup_id' => $schoolAdminGroupId,
                    'name' => $this->generateUniqueUsername($baseName),
                    'email' => $googleEmail,
                    'password' => Hash::make(Str::random(32)),
                    'email_verified' => 1,
                    'email_verified_at' => Carbon::now(),
                    'email_verification_code' => Str::random(40),
                    'google_id' => $googleId,
                    'google_avatar' => $googleAvatar,
                    'google_token' => '',
                    'is_activated' => 1,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                Userprofile::create([
                    'user_id' => $user->id,
                    'school_id' => $school->id,
                    'usergroup_id' => $schoolAdminGroupId,
                    'firstname' => $googleName,
                    'mobile_no' => '',
                    'avatar' => $googleAvatar ?: 'uploads/male.png',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                try {
                    Subscription::create([
                        'school_id' => $school->id,
                        'user_id' => $user->id,
                        'plan_id' => $this->resolveDefaultPlanId(),
                        'status' => 'pending',
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                } catch (Throwable $e) {
                    Log::warning('Google auth: subscription creation skipped', ['message' => $e->getMessage()]);
                }

                try {
                    $year = Carbon::now()->year;
                    AcademicYear::create([
                        'school_id' => $school->id,
                        'name' => $year,
                        'description' => 'Current Academic Year',
                        'start_date' => Carbon::create($year, 2, 1),
                        'end_date' => Carbon::create($year, 12, 15),
                        'status' => 1,
                    ]);
                } catch (Throwable $e) {
                    Log::warning('Google auth: academic year creation skipped', ['message' => $e->getMessage()]);
                }

                Log::info('New Google user registered (pending onboarding)', [
                    'user_id' => $user->id,
                    'school_id' => $school->id,
                    'google_id' => $googleId,
                ]);

                return $user;
            });

            Auth::login($user, true);

            // Redirect to onboarding, not dashboard
            return redirect('/welcome?name=' . urlencode($googleName));

        } catch (Throwable $e) {
            Log::error('Google auth user creation failed', ['message' => $e->getMessage()]);
            return redirect('/login')->with('failmessage', 'We could not create your account. Please try again.');
        }
    }

    /**
     * Show the onboarding form (interstitial after first Google sign-in).
     */
    public function showOnboarding(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect('/login');
        }

        // If the user already has a proper school name and country, skip onboarding
        $school = School::find($user->school_id);
        if ($school && !empty($school->country) && !empty($school->phone) && !empty($school->student_size)) {
            return redirect('/admin/dashboard');
        }

        $name = $request->query('name', $school->name ?? $user->name);

        return view('auth.onboarding', [
            'googleName' => $name,
            'email' => $user->email,
        ]);
    }

    /**
     * Process the onboarding form.
     */
    public function storeOnboarding(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect('/login');
        }

        $validated = $request->validate([
            'school_name' => 'required|string|max:255',
            'country' => 'required|string|max:100',
            'mobile_no' => 'required|string|max:20',
            'student_size' => 'required|in:Under 100 students,100-300 students,300-500 students,500+ students',
        ]);

        try {
            DB::transaction(function () use ($user, $validated) {
                $school = School::findOrFail($user->school_id);

                $school->name = $validated['school_name'];
                $school->slug = Str::slug($validated['school_name'] . '-' . Str::random(6));
                $school->country = $validated['country'];
                $school->phone = $validated['mobile_no'];
                $school->student_size = $validated['student_size'];
                $school->save();

                $profile = Userprofile::where('user_id', $user->id)->first();
                if ($profile) {
                    $profile->mobile_no = $validated['mobile_no'];
                    $profile->save();
                }

                Log::info('Google user onboarding completed', [
                    'user_id' => $user->id,
                    'school_id' => $school->id,
                    'school_name' => $validated['school_name'],
                ]);
            });

            return redirect('/admin/dashboard')->with('successmessage', 'Welcome to KlassApp! Your school is all set up.');

        } catch (Throwable $e) {
            Log::error('Google onboarding failed', ['message' => $e->getMessage()]);
            return redirect()->back()->withInput()->with('failmessage', 'Something went wrong. Please try again.');
        }
    }

    /**
     * Update user's Google-related fields from socialite data.
     */
    private function updateGoogleData(User $user, $googleUser): void
    {
        $user->google_id = $googleUser->getId();
        $user->google_avatar = $googleUser->getAvatar();

        if ($user->email_verified == 0) {
            $user->email_verified = 1;
            $user->email_verified_at = Carbon::now();
        }

        $user->save();
    }

    /**
     * Generate a unique username from a base name.
     */
    private function generateUniqueUsername(string $name): string
    {
        $base = Str::slug($name ?: 'user', '');
        if ($base === '') {
            $base = 'user';
        }

        $username = $base;
        $counter = 1;

        while (User::where('name', $username)->exists()) {
            $username = $base . $counter;
            $counter++;
        }

        return $username;
    }

    /**
     * Resolve a default plan id for new subscriptions.
     */
    private function resolveDefaultPlanId(): int
    {
        $id = DB::table('plans')->orderBy('id')->value('id');
        return $id ? (int) $id : 1;
    }
}
