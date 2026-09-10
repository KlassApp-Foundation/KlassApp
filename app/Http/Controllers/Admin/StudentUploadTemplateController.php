<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\SiteHelper;
use App\Http\Controllers\Controller;
use App\Services\StudentUploadTemplateService;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentUploadTemplateController extends Controller
{
    public function __construct(private StudentUploadTemplateService $templates)
    {
    }

    public function download(): StreamedResponse
    {
        $user = Auth::user();
        abort_unless($user && $user->school, 403, 'You are not authorized for this school.');

        $school = $user->school;
        $year = SiteHelper::getAcademicYear((int) $school->id);

        return $this->templates->downloadResponse($school, $year);
    }
}
