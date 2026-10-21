<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ClassTeacherUploadTemplateController extends Controller
{
    public function download(): Response
    {
        $user = Auth::user();
        abort_unless($user && $user->school, 403, 'You are not authorized for this school.');

        $path = public_path('templates/class-teacher-upload-template.csv');
        abort_unless(is_file($path), 404);

        $csv = file_get_contents($path);
        abort_unless(is_string($csv) && $csv !== '', 404);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="class-teacher-upload-template.csv"',
        ]);
    }
}
