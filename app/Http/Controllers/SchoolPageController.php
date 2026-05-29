<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\SchoolDetail;
use Illuminate\View\View;

class SchoolPageController extends Controller
{
    public function show(string $slug): View
    {
        $school = School::where('slug', $slug)->firstOrFail();

        $page = $school->premiumPage;

        if (! $page || ! $page->is_published) {
            abort(404);
        }

        $details = SchoolDetail::where('school_id', $school->id)
            ->pluck('meta_value', 'meta_key');

        $template = 'schools.templates.template-' . min(max((int) $page->template_id, 1), 5);

        return view($template, compact('school', 'page', 'details'));
    }
}
