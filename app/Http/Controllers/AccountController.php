<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Models\User;
use App\Services\AccountPasswordService;
use App\Support\PortalProfileLinks;
use App\Support\RoleShell;
use Illuminate\Support\Facades\Auth;

/**
 * The account pages every signed-in role shares: the profile and change password items of the
 * account menu (resources/views/layouts/partials/profile-dropdown.blade.php) land here for a
 * teacher, a parent, a student, staff and a school admin alike.
 */
class AccountController extends Controller
{
    public function profile()
    {
        $user = $this->user();

        return view('account.profile', [
            'layout' => RoleShell::layoutFor($user),
            'roleLabel' => RoleShell::roleLabel($user),
            'profile' => $user->userprofile,
            'school' => $user->school,
            'links' => PortalProfileLinks::forUser($user),
        ]);
    }

    public function password()
    {
        $user = $this->user();

        return view('account.password', [
            'layout' => RoleShell::layoutFor($user),
            'links' => PortalProfileLinks::forUser($user),
        ]);
    }

    public function updatePassword(ChangePasswordRequest $request, AccountPasswordService $passwords)
    {
        $passwords->change($this->user(), (string) $request->input('newpassword'));

        return redirect()->route('account.password')->with('status', AccountPasswordService::SUCCESS_MESSAGE);
    }

    private function user(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403, 'Not Authorized');

        return $user;
    }
}
