<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppUser;
use App\Traits\LogActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WhatsAppParentController extends Controller
{
    use LogActivity;

    public function index(Request $request)
    {
        $schoolId = Auth::user()->school_id;

        $query = WhatsAppUser::with(['user.userprofile', 'user.children'])
            ->where('school_id', $schoolId);

        // Filter by linked status
        if ($request->filled('linked')) {
            $linked = $request->input('linked') === 'yes';
            if ($linked) {
                $query->whereNotNull('user_id');
            } else {
                $query->whereNull('user_id');
            }
        }

        // Search by phone or name
        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('phone', 'LIKE', "%{$s}%")
                  ->orWhereHas('user', fn($uq) => $uq->where('name', 'LIKE', "%{$s}%"));
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(30);

        $stats = [
            'total' => WhatsAppUser::where('school_id', $schoolId)->count(),
            'linked' => WhatsAppUser::where('school_id', $schoolId)->whereNotNull('user_id')->count(),
            'opted_in' => WhatsAppUser::where('school_id', $schoolId)->where('opted_in', true)->count(),
        ];

        return view('admin.whatsapp.parents', compact('users', 'stats'));
    }

    public function unlink($id)
    {
        $user = WhatsAppUser::where('school_id', Auth::user()->school_id)->findOrFail($id);
        $user->update(['user_id' => null, 'verified_at' => null]);
        $this->log("Unlinked WhatsApp {$user->phone}", 'whatsapp-unlink');
        return back()->with('success', "WhatsApp number {$user->phone} unlinked.");
    }
}
