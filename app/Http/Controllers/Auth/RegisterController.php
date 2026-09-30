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
use App\Services\EmailVerificationCodeService;
use App\Services\SchoolSignupBootstrapService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
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
            ->view('auth.preview.register')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, private');
    }

    public function register(RegisterRequest $request, SchoolSignupBootstrapService $bootstrap, EmailVerificationCodeService $verificationCodes)
    {
        try {
            $user = $bootstrap->bootstrap([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'phone' => $request->validated('phone'),
                'password' => $request->validated('password'),
            ]);

            // bootstrap() marks new school admins as verified; the account must not
            // be usable until the emailed 6-digit code is confirmed instead.
            $user->forceFill([
                'email_verified' => 0,
                'email_verified_at' => null,
            ])->save();

            $verificationCodes->issue($user, $request->ip());
            $request->session()->put('pending_verification_user_id', $user->id);

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

        return redirect()->route('register.verify')
            ->with('status', 'We sent a 6-digit code to '.$user->email.'. Enter it to finish signing up.');
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
            // Guard: only attempt the slack channel if the webhook URL is configured.
            // On prod (LOG_SLACK_WEBHOOK_URL=null) this throws a TypeError when the
            // SlackWebhookHandler is constructed — a hard 500 that blocks all signups.
            $slackUrl = config('logging.channels.slack.url');
            if (! empty($slackUrl)) {
                Log::channel('slack')->info('A new user registered.', [
                    'Website' => env('APP_URL'),
                    'User_id' => $user->id,
                    'User_name' => $user->name,
                    'School_id' => $user->school_id,
                    'School_name' => optional($user->school)->name,
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('Slack registration log skipped', ['message' => $e->getMessage()]);
        }
    }
}
