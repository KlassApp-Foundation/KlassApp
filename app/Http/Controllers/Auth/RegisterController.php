<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Mail\AdminNotifyNewUserMail;
use App\Models\User;
use App\Services\SchoolSignupBootstrapService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class RegisterController extends Controller
{
    use RegistersUsers;

    /**
     * Post-signup destination: admin dashboard.
     * Continue-setup + Toshi auto-open derive from incomplete school state
     * ($setupIncomplete → $openToshiOnboarding), not the query string.
     */
    protected $redirectTo = '/admin/dashboard';

    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showRegistrationForm(Request $request)
    {
        if ($request->has('plan')) {
            session(['selected_plan' => $request->plan]);
        }

        return response()
            ->view('auth.register')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, private');
    }

    public function register(RegisterRequest $request, SchoolSignupBootstrapService $bootstrap)
    {
        try {
            $user = $bootstrap->bootstrap([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'phone' => $request->validated('phone'),
                'password' => $request->validated('password'),
                'email_verified' => true,
            ]);

            event(new Registered($user));
            $this->dispatchRegistrationSideEffects($user);
        } catch (Throwable $e) {
            Log::error('Registration Failed', ['message' => $e->getMessage()]);

            return redirect()->back()->withInput(
                $request->except(['password', 'password_confirmation'])
            )->withErrors([
                'register' => 'We could not complete your registration right now. Please try again in a moment.',
            ]);
        }

        $this->guard()->login($user);

        return redirect($this->redirectPath())
            ->with('open_toshi_onboarding', true)
            ->with('successmessage', 'Welcome to KlassApp! Continue setup with Toshi.');
    }

    protected function guard()
    {
        return Auth::guard();
    }

    private function dispatchRegistrationSideEffects(User $user): void
    {
        try {
            $admin = User::where('usergroup_id', 1)->first();
            if ($admin && env('MAIL_STATUS') == 'on') {
                Mail::to($admin->email)->queue(new AdminNotifyNewUserMail($user));
            }
        } catch (Throwable $e) {
            Log::warning('Admin notify email queue failed', ['message' => $e->getMessage()]);
        }

        try {
            Log::channel('slack')->info('A new user registered.', [
                'Website' => env('APP_URL'),
                'User_id' => $user->id,
                'User_name' => $user->name,
                'School_id' => $user->school_id,
                'School_name' => optional($user->school)->name,
            ]);
        } catch (Throwable $e) {
            Log::warning('Slack registration log skipped', ['message' => $e->getMessage()]);
        }
    }
}
