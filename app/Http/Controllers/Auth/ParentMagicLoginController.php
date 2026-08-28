<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ParentMagicLoginService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParentMagicLoginController extends Controller
{
    public function __invoke(Request $request, User $user, string $nonce, ParentMagicLoginService $magicLogin)
    {
        if ((int) $user->usergroup_id !== 7 || ! $magicLogin->canIssueLink($user)) {
            abort(403);
        }

        if ($magicLogin->isNonceConsumed($nonce) || ! $magicLogin->consumeNonce($nonce)) {
            abort(403, 'This login link has already been used.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('parent.dashboard');
    }
}
