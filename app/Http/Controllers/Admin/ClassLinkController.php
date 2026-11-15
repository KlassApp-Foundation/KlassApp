<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\SiteHelper;
use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Standard;
use App\Services\Setup\SectionClassLinker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Classes page: link a section that has no class link to one of the school's class levels.
 * School admin only (routes sit behind the full-school-admin gate). Another school's section or class
 * level gets the same 404 as one that does not exist.
 */
class ClassLinkController extends Controller
{
    /** Shows what will happen and asks for confirmation. */
    public function confirm(Request $request, int $section, SectionClassLinker $linker)
    {
        $schoolId = (int) Auth::user()->school_id;
        $found = Section::query()->where('school_id', $schoolId)->whereKey($section)->first();
        abort_if($found === null, 404);

        $standardId = (int) $request->query('standard');
        $standard = $standardId > 0
            ? Standard::query()->where('school_id', $schoolId)->whereKey($standardId)->first()
            : null;
        abort_if($standardId > 0 && $standard === null, 404);

        return view('admin.classes.link-section', [
            'section' => $found,
            'standards' => Standard::query()->where('school_id', $schoolId)->where('status', 1)->orderBy('order')->orderBy('id')->get(),
            'standard' => $standard,
            'year' => SiteHelper::getAcademicYear($schoolId),
            'plan' => $standard === null ? null : $linker->plan($schoolId, $section, (int) $standard->id),
        ]);
    }

    public function store(Request $request, int $section, SectionClassLinker $linker)
    {
        $data = $request->validate([
            'standard_id' => ['required', 'integer'],
            'confirm' => ['accepted'],
        ]);

        $result = $linker->apply((int) Auth::user()->school_id, $section, (int) $data['standard_id'], null, (int) Auth::id(), 'screen');
        abort_if($result['result'] === SectionClassLinker::NOT_FOUND, 404);

        if ($result['result'] === SectionClassLinker::ALREADY_LINKED) {
            return redirect()->route('admin.classes.index')
                ->with('errormessage', $result['section']->name.' is already linked to a class this year. Nothing was changed.');
        }

        return redirect()->route('admin.classes.index')
            ->with('successmessage', $result['section']->name.' is now linked to '.$result['standard']->name.' for '.$result['year']->name.'.');
    }
}
