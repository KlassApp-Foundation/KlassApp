<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ForceChangePasswordController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function showForm()
    {
        $user = Auth::user();

        if ((int) $user->is_reset !== 1) {
            return redirect()->intended('/');
        }

        return view('auth.force-change-password');
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if ((int) $user->is_reset !== 1) {
            return redirect()->intended('/');
        }

        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.'])->withInput();
        }

        $user->password = Hash::make($request->password);
        $user->is_reset = 0;
        $user->save();

        return redirect()->intended('/')->with('successmessage', 'Your password has been updated successfully.');
    }
}
