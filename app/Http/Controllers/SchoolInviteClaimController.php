<?php

namespace App\Http\Controllers;

use App\Models\SchoolInvite;
use App\Models\User;
use App\Services\SchoolInviteService;
use App\Services\SchoolSignupBootstrapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class SchoolInviteClaimController extends Controller
{
    public function show(string $token)
    {
        $result = SchoolInviteService::validateToken($token);

        if ($result['error'] !== null) {
            return view('auth.invite-invalid', [
                'reason' => $result['error'] === 'expired' ? 'expired' : ($result['error'] === 'claimed' ? 'claimed' : 'invalid'),
                'invite' => $result['invite'],
            ]);
        }

        return view('auth.school-invite-claim', [
            'invite' => $result['invite'],
            'token' => $token,
        ]);
    }

    public function claim(Request $request, string $token)
    {
        $result = SchoolInviteService::validateToken($token);

        if ($result['error'] !== null || $result['invite'] === null) {
            return redirect()->route('school-invite.show', $token);
        }

        /** @var SchoolInvite $invite */
        $invite = $result['invite'];

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100', "regex:/^[\pL\s'\-]+$/u"],
            'phone' => ['required', 'string', 'max:20', 'regex:/^(\+?256)?0?7[0578]\d{7}$/'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ]);

        $phone = app(SchoolSignupBootstrapService::class)->normalizePhone($data['phone']);
        if ($phone === null) {
            return back()->withErrors(['phone' => 'Enter a valid WhatsApp phone number.'])->withInput();
        }

        if (User::where('mobile_no', $phone)->exists()) {
            return back()->withErrors(['phone' => 'This phone number is already registered.'])->withInput();
        }

        $claim = SchoolInviteService::claim($invite, [
            'name' => $data['name'],
            'phone' => $phone,
            'password' => $data['password'],
        ]);

        if (! $claim['success']) {
            return back()->withErrors(['invite' => $claim['message']])->withInput();
        }

        Auth::login($claim['user'], true);

        return redirect()->route('onboarding.choice')
            ->with('successmessage', 'Your school is ready. Choose how you want to set it up.');
    }
}
