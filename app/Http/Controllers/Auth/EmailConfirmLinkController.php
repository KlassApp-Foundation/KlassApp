<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\EmailVerificationCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

/**
 * The signed "Confirm email" link from the signup verification email.
 *
 * GET only renders a page with a Confirm button (mail scanners and link previews
 * open links, so a GET must never confirm). The button POSTs to the same signed
 * URL, and only that POST confirms. The link never signs in the device it is
 * opened on, except the browser that holds the pending signup session.
 */
class EmailConfirmLinkController extends Controller
{
    public function __construct(private readonly EmailVerificationCodeService $codes)
    {
    }

    public function show(Request $request, string $token)
    {
        if ($blocked = $this->rejectUnusable($request, $token)) {
            return $blocked;
        }

        return response()->view('auth.preview.verify-link', [
            'state' => 'confirm',
            'postUrl' => $request->fullUrl(),
        ]);
    }

    public function confirm(Request $request, string $token)
    {
        if ($blocked = $this->rejectUnusable($request, $token)) {
            return $blocked;
        }

        $tokenUserId = $this->codes->userIdForLinkToken($token);

        [$result, $user] = $this->codes->confirmLinkToken($token);

        if ($result !== EmailVerificationCodeService::OK || $user === null) {
            return $this->expired();
        }

        // Same browser as the signup: carry straight on into onboarding.
        if ($tokenUserId !== null
            && (int) $request->session()->get('pending_verification_user_id', 0) === $tokenUserId) {
            $request->session()->forget('pending_verification_user_id');
            Auth::login($user);
            $request->session()->regenerate();

            return redirect('/admin/dashboard')
                ->with('open_toshi_onboarding', true)
                ->with('successmessage', "Email confirmed. Welcome to KlassApp! Let's set up your school.");
        }

        // Any other device: confirmed, but never signed in here. No school name or account data.
        return response()->view('auth.preview.verify-link', [
            'state' => 'confirmed',
            'email' => $user->email,
        ]);
    }

    /** 403 for a bad or missing signature, 410 for expired / already used. Null when usable. */
    private function rejectUnusable(Request $request, string $token)
    {
        if (! URL::hasCorrectSignature($request)) {
            return response()->view('auth.preview.verify-link', ['state' => 'invalid'], 403);
        }

        if (! URL::signatureHasNotExpired($request) || ! $this->codes->peekLinkToken($token)) {
            return $this->expired();
        }

        return null;
    }

    private function expired()
    {
        return response()->view('auth.preview.verify-link', ['state' => 'expired'], 410);
    }
}
