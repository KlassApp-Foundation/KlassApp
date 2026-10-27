<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportMemberRequest;
use App\Imports\ParentsImport;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Facades\Excel;

class ParentImportController extends Controller
{
    public function index()
    {
        return view('admin.parent.import');
    }

    public function import(ImportMemberRequest $request)
    {
        Excel::import(new ParentsImport, $request->file('import_file'));

        $inserted = (int) Session::get('insertedcount');
        $failures = Session::get('import_row_failures', []);
        Session::forget('insertedcount');
        Session::forget('import_row_failures');

        $redirect = $inserted > 0
            ? back()->with('successmessage', $inserted.' Records Inserted successfully')
            : back()->with('failmessage', 'No Records Inserted');

        if (is_array($failures) && $failures !== []) {
            $redirect = $redirect->with('import_row_failures', array_slice($failures, 0, 50));
        }

        return $redirect;
    }
}
