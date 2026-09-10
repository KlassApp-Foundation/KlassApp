<?php

namespace App\Http\Controllers\Teacher;

use App\Helpers\SiteHelper;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Section;
use App\Models\User;
use App\Services\ClassStructureService;
use App\Services\ExamAuthorization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Class-teacher stream surface: add streams to an owned class, rename owned streams.
 * Delete/merge stay admin-only. Auth uses ExamAuthorization::sectionIdsForClassTeacher()
 * (not school-wide SectionPolicy).
 */
class ClassStreamController extends Controller
{
    public function __construct(
        private ClassStructureService $structure,
        private ExamAuthorization $examAuthorization,
    ) {
    }

    public function index(): View
    {
        $teacher = $this->actor();
        $schoolId = (int) $teacher->school_id;
        $year = $this->currentYear($schoolId);

        if (! $year) {
            return view('teacher.class-stream.index', [
                'sections' => collect(),
                'year' => null,
            ]);
        }

        $sectionIds = $this->ownedSectionIds($teacher, $schoolId, (int) $year->id);
        $sections = Section::query()
            ->where('school_id', $schoolId)
            ->whereIn('id', $sectionIds)
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        return view('teacher.class-stream.index', [
            'sections' => $sections,
            'year' => $year,
        ]);
    }

    public function create(Section $section): View
    {
        $teacher = $this->actor();
        $year = $this->requireYear((int) $teacher->school_id);
        $this->assertOwnsSection($teacher, $section, (int) $year->id);

        $base = $this->structure->resolveBaseSection($section);

        return view('teacher.class-stream.create', [
            'section' => $section,
            'base' => $base,
        ]);
    }

    public function store(Request $request, Section $section): RedirectResponse
    {
        $teacher = $this->actor();
        $year = $this->requireYear((int) $teacher->school_id);
        $this->assertOwnsSection($teacher, $section, (int) $year->id);

        $validated = $request->validate([
            'stream' => 'required|string|max:50',
        ]);

        try {
            $result = $this->structure->addStream(
                $teacher->school,
                $year,
                $section,
                $validated['stream']
            );
        } catch (ValidationException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors($e->errors());
        }

        $name = $result['section']->name;
        $msg = $result['created']
            ? "Stream \"{$name}\" added. Base class kept."
            : "Stream \"{$name}\" already existed.";

        return redirect()
            ->route('teacher.class-stream.index')
            ->with('successmessage', $msg);
    }

    public function edit(Section $section): View
    {
        $teacher = $this->actor();
        $year = $this->requireYear((int) $teacher->school_id);
        $this->assertOwnsSection($teacher, $section, (int) $year->id);

        return view('teacher.class-stream.edit', [
            'section' => $section,
        ]);
    }

    public function update(Request $request, Section $section): RedirectResponse
    {
        $teacher = $this->actor();
        $year = $this->requireYear((int) $teacher->school_id);
        $this->assertOwnsSection($teacher, $section, (int) $year->id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        try {
            $updated = $this->structure->renameStream(
                $teacher->school,
                $section,
                $validated['name']
            );
        } catch (ValidationException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('teacher.class-stream.index')
            ->with('successmessage', "Renamed to \"{$updated->name}\".");
    }

    private function actor(): User
    {
        $user = Auth::user();
        if (! $user instanceof User || (int) $user->usergroup_id !== 5) {
            abort(403, 'Not Authorized');
        }

        return $user;
    }

    private function currentYear(int $schoolId): ?AcademicYear
    {
        return SiteHelper::getAcademicYear($schoolId)
            ?? AcademicYear::query()
                ->where('school_id', $schoolId)
                ->where('status', 1)
                ->orderByDesc('id')
                ->first();
    }

    private function requireYear(int $schoolId): AcademicYear
    {
        $year = $this->currentYear($schoolId);
        if (! $year) {
            abort(403, 'No current academic year configured.');
        }

        return $year;
    }

    /**
     * @return list<int>
     */
    private function ownedSectionIds(User $teacher, int $schoolId, int $yearId): array
    {
        return $this->examAuthorization->sectionIdsForClassTeacher($teacher, $schoolId, $yearId);
    }

    private function assertOwnsSection(User $teacher, Section $section, int $yearId): void
    {
        abort_if(
            (int) $section->school_id !== (int) $teacher->school_id,
            403,
            'You are not authorized for this school.'
        );
        abort_if((int) $section->status !== 1, 404, 'Class not found.');

        $owned = $this->ownedSectionIds($teacher, (int) $teacher->school_id, $yearId);
        abort_unless(
            in_array((int) $section->id, $owned, true),
            403,
            'You are not the class teacher for this class.'
        );
    }
}
