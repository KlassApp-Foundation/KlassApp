<?php

namespace App\Livewire\TeacherSetup;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Services\OnboardingEngine;
use App\Services\OnboardingNameListExtractor;
use App\Services\TeacherInviteLinkService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * "Set up my class": the class teacher confirms their class, adds learners,
 * confirms the subjects they teach, names the subject teachers, and can link
 * WhatsApp. Each save is idempotent.
 */
class TeacherClassSetup extends Component
{
    use WithFileUploads;

    public ?int $selectedLinkId = null;

    /** @var list<int> */
    public array $subjectIds = [];

    /** @var array<int, string> */
    public array $subjectTeacher = [];

    public $studentUpload = null;

    public string $report = '';

    public string $errorMessage = '';

    public function mount(): void
    {
        $links = $this->links();
        if ($links->count() === 1) {
            $this->selectedLinkId = (int) $links->first()->id;
        }
        $this->loadSubjects();
        $this->loadSubjectTeachers();
    }

    public function selectClass(int $linkId): void
    {
        $this->selectedLinkId = $linkId;
        if ($this->ownedLink() === null) {
            $this->selectedLinkId = null;
        }
        $this->loadSubjects();
        $this->loadSubjectTeachers();
    }

    public function uploadStudents(): void
    {
        $this->errorMessage = '';
        $this->report = '';

        if (! $this->studentUpload) {
            return;
        }

        $link = $this->ownedLink();
        if (! $link) {
            $this->errorMessage = 'Choose your class first.';

            return;
        }

        $extension = strtolower($this->studentUpload->getClientOriginalExtension() ?: '');
        if (! in_array($extension, OnboardingNameListExtractor::ACCEPTED_MIMES, true)) {
            $this->errorMessage = 'Supported files: CSV, XLSX, TXT, DOCX, PDF.';

            return;
        }

        $rows = app(OnboardingNameListExtractor::class)->extractNamesFromFile($this->studentUpload->getRealPath(), $extension);
        if ($rows === []) {
            $this->errorMessage = 'No names found in that file.';

            return;
        }

        $section = $link->section?->name ?? '';
        $drafts = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $drafts[] = [
                'name' => $name,
                'class' => (string) ($row['class'] ?? $section),
                'stream' => (string) ($row['stream'] ?? ''),
                'parent' => (string) ($row['parent'] ?? ''),
                'parent_phone' => (string) ($row['parent_phone'] ?? ''),
                'school_student_id' => (string) ($row['school_student_id'] ?? ''),
                'lin' => (string) ($row['lin'] ?? ''),
                'board_registration_number' => (string) ($row['board_registration_number'] ?? ''),
                'gender' => (string) ($row['gender'] ?? ''),
                'date_of_birth' => (string) ($row['date_of_birth'] ?? ''),
                'row' => (int) ($row['row'] ?? 0),
            ];
        }

        try {
            $result = app(OnboardingEngine::class)->saveStudents($this->school(), $this->year(), $drafts, (int) $link->id);
            $this->report = count($result['created'] ?? []).' students added, '.count($result['skipped'] ?? []).' skipped';
        } catch (ValidationException $e) {
            $this->errorMessage = (string) (collect($e->errors())->flatten()->first() ?: 'Could not add those students.');
        }

        $this->studentUpload = null;
    }

    public function saveSubjects(): void
    {
        $this->errorMessage = '';
        $link = $this->ownedLink();
        if (! $link) {
            $this->errorMessage = 'Choose your class first.';

            return;
        }

        $teacher = Auth::user();
        $year = $this->year();

        foreach ($this->subjectIds as $subjectId) {
            if ($this->subjectOnLink($link, (int) $subjectId) === null) {
                continue;
            }
            Teacherlink::query()->firstOrCreate([
                'school_id' => $this->school()->id,
                'academic_year_id' => $year->id,
                'standardLink_id' => $link->id,
                'subject_id' => (int) $subjectId,
                'teacher_id' => $teacher->id,
            ]);
        }

        $this->report = 'Your subjects were saved.';
    }

    public function saveSubjectTeachers(): void
    {
        $this->errorMessage = '';
        $this->report = '';
        $link = $this->ownedLink();
        if (! $link) {
            $this->errorMessage = 'Choose your class first.';

            return;
        }

        $year = $this->year();
        $saved = 0;
        $invited = 0;

        foreach ($this->subjectTeacher as $subjectId => $value) {
            $subjectId = (int) $subjectId;
            $value = trim((string) $value);
            if ($value === '' || $this->subjectOnLink($link, $subjectId) === null) {
                continue;
            }

            $colleagueId = $this->teacherIdInSchool($value);
            if ($colleagueId !== null) {
                Teacherlink::query()->firstOrCreate([
                    'school_id' => $this->school()->id,
                    'academic_year_id' => $year->id,
                    'standardLink_id' => $link->id,
                    'subject_id' => $subjectId,
                    'teacher_id' => $colleagueId,
                ]);
                $saved++;

                continue;
            }

            $email = $this->explicitEmail($value);
            if ($email === null) {
                continue;
            }

            try {
                $issued = TeacherInviteLinkService::issue(
                    $this->school(),
                    $email,
                    'Subject teacher',
                    null,
                );
                TeacherInviteLinkService::sendEmail($issued['invite'], $issued['token'], $this->school(), $link->section?->name);
                $invited++;
            } catch (\Throwable) {
                Log::warning('Subject teacher invite could not be queued', [
                    'school_id' => (int) $this->school()->id,
                    'user_id' => (int) Auth::id(),
                    'subject_id' => $subjectId,
                ]);
            }
        }

        $this->report = $saved.' subject teachers confirmed'.($invited > 0 ? ', '.$invited.' invited by email' : '');
    }

    public function render()
    {
        $link = $this->currentLink();
        $subjects = $link
            ? Subject::query()->where('school_id', $this->school()->id)
                ->where('section_id', $link->section_id)
                ->orderBy('name')
                ->get(['id', 'name'])
            : collect();

        $students = $link
            ? \App\Models\StudentAcademic::query()->where('school_id', $this->school()->id)
                ->where('standardLink_id', $link->id)->count()
            : 0;

        $teachers = User::query()
            ->where('school_id', $this->school()->id)
            ->where('usergroup_id', 5)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        $subjectTeachers = $link
            ? Teacherlink::query()->where('school_id', $this->school()->id)
                ->where('standardLink_id', $link->id)
                ->get(['subject_id', 'teacher_id'])
                ->mapWithKeys(fn ($row) => [(int) $row->subject_id => (string) $row->teacher_id])
                ->all()
            : [];

        return view('livewire.teacher-setup.teacher-class-setup', [
            'links' => $this->links(),
            'link' => $link,
            'subjects' => $subjects,
            'students' => $students,
            'teachers' => $teachers,
            'subjectTeachers' => $subjectTeachers,
        ]);
    }

    private function links()
    {
        $year = AcademicYear::query()->where('school_id', $this->school()->id)->where('status', 1)->first()
            ?? AcademicYear::query()->where('school_id', $this->school()->id)->first();

        if (! $year) {
            return collect();
        }

        return StandardLink::query()
            ->with('section')
            ->where('school_id', $this->school()->id)
            ->where('academic_year_id', $year->id)
            ->where('class_teacher_id', Auth::id())
            ->orderBy('id')
            ->get();
    }

    private function currentLink(): ?StandardLink
    {
        return $this->ownedLink();
    }

    /**
     * The class the signed-in teacher actually holds. The id from the browser
     * is only a lookup key; another class, stream, or school never matches.
     */
    private function ownedLink(): ?StandardLink
    {
        $id = (int) $this->selectedLinkId;
        if ($id < 1) {
            return null;
        }

        return StandardLink::query()
            ->with('section')
            ->whereKey($id)
            ->where('school_id', $this->school()->id)
            ->where('academic_year_id', $this->year()->id)
            ->where('class_teacher_id', Auth::id())
            ->first();
    }

    private function subjectOnLink(StandardLink $link, int $subjectId): ?Subject
    {
        if ($subjectId < 1 || ! $link->section_id) {
            return null;
        }

        return Subject::query()
            ->whereKey($subjectId)
            ->where('school_id', $this->school()->id)
            ->where('section_id', $link->section_id)
            ->first();
    }

    private function teacherIdInSchool(string $value): ?int
    {
        if (! ctype_digit($value)) {
            return null;
        }

        $id = (int) $value;
        $teacher = User::query()
            ->whereKey($id)
            ->where('school_id', $this->school()->id)
            ->where('usergroup_id', 5)
            ->where('status', 'active')
            ->first();

        return $teacher ? (int) $teacher->id : null;
    }

    /**
     * An address the teacher typed. A name, an id, or a fragment is not an address.
     */
    private function explicitEmail(string $value): ?string
    {
        $value = trim($value);
        if ($value === '' || ! str_contains($value, '@')) {
            return null;
        }
        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        return mb_strtolower($value);
    }

    private function loadSubjects(): void
    {
        $link = $this->currentLink();
        if (! $link) {
            $this->subjectIds = [];

            return;
        }

        $this->subjectIds = Teacherlink::query()
            ->where('school_id', $this->school()->id)
            ->where('standardLink_id', $link->id)
            ->where('teacher_id', Auth::id())
            ->pluck('subject_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function loadSubjectTeachers(): void
    {
        $link = $this->currentLink();
        if (! $link) {
            $this->subjectTeacher = [];

            return;
        }

        $this->subjectTeacher = Teacherlink::query()
            ->where('school_id', $this->school()->id)
            ->where('standardLink_id', $link->id)
            ->get(['subject_id', 'teacher_id'])
            ->mapWithKeys(fn ($row) => [(int) $row->subject_id => (string) $row->teacher_id])
            ->all();
    }

    private function school(): School
    {
        return School::query()->findOrFail(Auth::user()->school_id);
    }

    private function year(): AcademicYear
    {
        return AcademicYear::query()->where('school_id', $this->school()->id)->where('status', 1)->first()
            ?? AcademicYear::query()->where('school_id', $this->school()->id)->firstOrFail();
    }
}
