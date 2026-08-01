<?php

namespace App\Http\Controllers\Librarian;

use App\Http\Controllers\Controller;
use App\Services\Library\LibraryCardLookupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * View-only library card lookup for ug8 (MustBeLibrarian).
 * No issue/return/create/update — those are a follow-up.
 */
class LibraryCardController extends Controller
{
    public function index(Request $request): View
    {
        $schoolId = (int) Auth::user()->school_id;
        $userId = $request->filled('user_id') ? (int) $request->get('user_id') : null;
        $cardNo = $request->filled('library_card_no') ? (string) $request->get('library_card_no') : null;

        $data = LibraryCardLookupService::lookup($schoolId, $userId, $cardNo);

        return view('library.cards.index', $data);
    }
}
