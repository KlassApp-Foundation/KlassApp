<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * The old filter action rendered the sheet without its variables.
 * Both marks addresses now use MarksController. This redirect remains
 * so an old call still lands on that sheet.
 */
class FilterMarksForm extends Controller
{
    public function filterForm(Request $request)
    {
        return redirect()->route('admin.marks.filter', $request->query());
    }
}
