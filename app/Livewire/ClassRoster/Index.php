<?php

namespace App\Livewire\ClassRoster;

use App\Models\AcademicYear;
use App\Models\Standard;
use App\Models\User;
use App\Services\RosterScopeService;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $selectedAcademicYearId = null;

    public ?int $levelId = null;

    public string $assignmentStatus = 'all';

    public int $perPage = 12;

    protected $queryString = [
        'search' => ['except' => ''],
        'selectedAcademicYearId' => ['except' => null, 'as' => 'academic_year_id'],
        'levelId' => ['except' => null, 'as' => 'level_id'],
        'assignmentStatus' => ['except' => 'all', 'as' => 'assignment'],
    ];

    public function mount(): void
    {
        $schoolId = (int) $this->actor()->school_id;

        $this->selectedAcademicYearId = AcademicYear::query()
            ->where('school_id', $schoolId)
            ->orderByDesc('id')
            ->value('id');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedAcademicYearId(): void
    {
        $this->resetPage();
    }

    public function updatingLevelId(): void
    {
        $this->resetPage();
    }

    public function updatingAssignmentStatus(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->levelId = null;
        $this->assignmentStatus = 'all';
        $this->resetPage();
    }

    public function render(RosterScopeService $rosterScope): View
    {
        /** @var User $actor */
        $actor = $this->actor();
        $schoolId = (int) $actor->school_id;
        $years = AcademicYear::query()
            ->where('school_id', $schoolId)
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();
        $levels = Standard::query()
            ->where('school_id', $schoolId)
            ->where('status', 1)
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        if ($years->isEmpty()) {
            return view('livewire.class-roster.index', [
                'sections' => collect(),
                'years' => $years,
                'levels' => $levels,
                'selectedYear' => null,
            ]);
        }

        $selectedYear = $years->firstWhere('id', $this->selectedAcademicYearId)
            ?? $years->first();
        $this->selectedAcademicYearId = $selectedYear->id;

        $query = $rosterScope->visibleSections($actor, $schoolId, $selectedYear->id);

        $query->when($this->search !== '', function ($sectionQuery): void {
            $sectionQuery->where('sections.name', 'like', '%' . $this->search . '%');
        });

        $query->when($this->levelId !== null, function ($sectionQuery): void {
            $sectionQuery->whereHas('standardLink', function ($streamQuery): void {
                $streamQuery
                    ->where('standards_link.school_id', auth()->user()->school_id)
                    ->where('standards_link.academic_year_id', $this->selectedAcademicYearId)
                    ->where('standards_link.standard_id', $this->levelId);
            });
        });

        if ($this->assignmentStatus === 'assigned') {
            $query->where(function ($sectionQuery): void {
                $sectionQuery
                    ->whereNotNull('sections.class_teacher_id')
                    ->orWhereHas('standardLink', function ($streamQuery): void {
                        $streamQuery
                            ->where('standards_link.school_id', auth()->user()->school_id)
                            ->where('standards_link.academic_year_id', $this->selectedAcademicYearId)
                            ->whereNotNull('standards_link.class_teacher_id');
                    });
            });
        }

        if ($this->assignmentStatus === 'unassigned') {
            $query
                ->whereNull('sections.class_teacher_id')
                ->whereDoesntHave('standardLink', function ($streamQuery): void {
                    $streamQuery
                        ->where('standards_link.school_id', auth()->user()->school_id)
                        ->where('standards_link.academic_year_id', $this->selectedAcademicYearId)
                        ->whereNotNull('standards_link.class_teacher_id');
                });
        }

        return view('livewire.class-roster.index', [
            'sections' => $query->paginate($this->perPage),
            'years' => $years,
            'levels' => $levels,
            'selectedYear' => $selectedYear,
        ]);
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
