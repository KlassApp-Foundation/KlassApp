<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Results\CombinedSheetPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DownloadMarksSheet extends Controller
{
    public function download(Request $request, CombinedSheetPage $sheet)
    {
        return $sheet->download((int) Auth::user()->school_id, $request);
    }
}
