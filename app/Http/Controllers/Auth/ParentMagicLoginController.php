<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ParentMagicLoginService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParentMagicLoginController extends Controller
{
    public function show(Request $request, User $user, string $nonce, ParentMagicLoginService $magicLogin)
    {
        if ($this->isLinkPreviewCrawler($request)) {
            return response('', 204);
        }

        if ((int) $user->usergroup_id !== 7 || ! $magicLogin->canIssueLink($user)) {
            abort(403);
        }

        if ($magicLogin->isNonceConsumed($nonce)) {
            abort(403, 'This login link has already been used.');
        }

        $request->session()->put('parent_magic_login', [
            'user_id' => $user->id,
            'nonce' => $nonce,
            'expires_at' => (int) $request->query('expires', now()->addMinutes(ParentMagicLoginService::TTL_MINUTES)->timestamp),
        ]);

        return view('auth.parent-magic-login', [
            'parent' => $user,
        ]);
    }

    public function confirm(Request $request, ParentMagicLoginService $magicLogin)
    {
        $payload = $request->session()->pull('parent_magic_login');

        if (! is_array($payload) || empty($payload['user_id']) || empty($payload['nonce'])) {
            abort(403, 'This login link has already been used.');
        }

        if (! empty($payload['expires_at']) && now()->timestamp > (int) $payload['expires_at']) {
            abort(403, 'This login link has expired.');
        }

        $user = User::find($payload['user_id']);
        $nonce = (string) $payload['nonce'];

        if (! $user || (int) $user->usergroup_id !== 7 || ! $magicLogin->canIssueLink($user)) {
            abort(403);
        }

        if ($magicLogin->isNonceConsumed($nonce) || ! $magicLogin->consumeNonce($nonce)) {
            abort(403, 'This login link has already been used.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('parent.dashboard');
    }

    /**
     * WhatsApp/Facebook unfurl crawlers GET the URL to generate a preview.
     * In-app browsers include a Mozilla token; bare WhatsApp UAs do not.
     */
    private function isLinkPreviewCrawler(Request $request): bool
    {
        $ua = strtolower($request->userAgent() ?? '');

        if ($ua === '') {
            return false;
        }

        if (str_contains($ua, 'facebookexternalhit') || str_contains($ua, 'facebot')) {
            return true;
        }

        return str_contains($ua, 'whatsapp') && ! str_contains($ua, 'mozilla');
    }
}
