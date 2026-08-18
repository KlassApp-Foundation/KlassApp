<?php

namespace App\Livewire\ClassRoster;

use App\Models\AcademicYear;
use App\Models\Section;
use App\Models\StandardLink;
use App\Services\RosterScopeService;
use App\Models\User;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use WithPagination;

    public int $sectionId;

    public ?int $selectedAcademicYearId = null;

    public ?int $selectedStreamId = null;

    public string $studentSearch = '';

    public int $perPage = 20;

    protected $queryString = [
        'selectedAcademicYearId' => ['except' => null, 'as' => 'academic_year_id'],
        'selectedStreamId' => ['except' => null, 'as' => 'stream_id'],
        'studentSearch' => ['except' => '', 'as' => 'student'],
    ];

    public function mount(int $sectionId, ?int $academicYearId = null): void
    {
        $this->sectionId = $sectionId;
        $schoolId = (int) $this->actor()->school_id;
        $this->selectedAcademicYearId = $academicYearId
            ?? AcademicYear::query()->where('school_id', $schoolId)->orderByDesc('id')->value('id');

        if ($this->selectedAcademicYearId === null) {
            abort(404, 'Academic year not found.');
        }

        $this->authorizedSection(app(RosterScopeService::class));
    }

    public function updatingSelectedAcademicYearId(): void
    {
        $this->selectedStreamId = null;
        $this->resetPage('rosterPage');
    }

    public function updatingStudentSearch(): void
    {
        $this->resetPage('rosterPage');
    }

    public function selectStream(int $streamId): void
    {
        $this->selectedStreamId = $streamId;
        $this->resetPage('rosterPage');
    }

    public function isAdmin(): bool
    {
        return in_array((int) $this->actor()->usergroup_id, [1, 3], true);
    }

    public function render(RosterScopeService $rosterScope): View
    {
        /** @var User $actor */
        $actor = $this->actor();
        $schoolId = (int) $actor->school_id;
        $section = $this->authorizedSection($rosterScope);
        $streams = $rosterScope->visibleStreams($actor, $section, $this->selectedAcademicYearId);
        $sectionTeacher = $rosterScope->effectiveClassTeacher($section, null);
        $selectedStream = $streams->firstWhere('id', $this->selectedStreamId)
            ?? $streams->first();
        $this->selectedStreamId = $selectedStream?->id;
        $students = collect();
        $fullStudentDetail = $this->isAdmin();

        if ($selectedStream !== null) {
            $studentsQuery = $rosterScope->studentsForStream($selectedStream, $schoolId, $actor);
            $studentsQuery->when($this->studentSearch !== '', function ($studentQuery) use ($actor): void {
                $studentQuery->whereHas('user', function ($userQuery) use ($actor): void {
                    $userQuery
                        ->where('users.school_id', $actor->school_id)
                        ->where('users.name', 'like', '%' . $this->studentSearch . '%');
                });
            });
            $students = $studentsQuery->paginate($this->perPage, ['*'], 'rosterPage');
            $fullStudentDetail = $fullStudentDetail
                || $rosterScope->effectiveClassTeacher($section, $selectedStream)?->is($actor);
        }

        $streamRows = $streams->map(function (StandardLink $stream) use ($rosterScope, $section, $actor): array {
            $effectiveTeacher = $rosterScope->effectiveClassTeacher($section, $stream);
            $fullDetail = $this->isAdmin() || $effectiveTeacher?->is($actor);
            $teacherLinks = $stream->teacherlink;

            if (! $fullDetail) {
                $teacherLinks = $teacherLinks->where('teacher_id', $actor->id)->values();
            }

            return [
                'stream' => $stream,
                'effectiveTeacher' => $effectiveTeacher,
                'teacherLinks' => $teacherLinks,
            ];
        });

        return view('livewire.class-roster.show', [
            'section' => $section,
            'sectionTeacher' => $sectionTeacher,
            'streams' => $streamRows,
            'selectedStream' => $selectedStream,
            'students' => $students,
            'fullStudentDetail' => $fullStudentDetail,
            'years' => AcademicYear::query()
                ->where('school_id', $schoolId)
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    private function authorizedSection(RosterScopeService $rosterScope): Section
    {
        $actor = $this->actor();

        return $rosterScope
            ->visibleSections($actor, (int) $actor->school_id, (int) $this->selectedAcademicYearId)
            ->whereKey($this->sectionId)
            ->firstOrFail();
    }

    private function actor(): User
    {
        $actor = auth()->user();

        if (! $actor instanceof User) {
            abort(403, 'You must be authenticated to view classes.');
        }

        return $actor;
    }
}
