<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Services\WhatsApp\ParentLinkRequestService;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;

class ParentLinkStudentPicker extends Component
{
    public ?int $schoolId = null;

    public string $initialQuery = '';

    public string $query = '';

    public ?int $selectedStudentId = null;

    public string $selectedLabel = '';

    public bool $showSchoolName = false;

    public function mount(?int $schoolId = null, string $initialQuery = ''): void
    {
        $actor = auth()->user();
        abort_unless($actor !== null, 403);

        $actorSchoolId = (int) $actor->school_id;

        if ((int) $actor->usergroup_id === 3) {
            $this->schoolId = $actorSchoolId;
            $this->showSchoolName = false;
        } else {
            $this->schoolId = $schoolId;
            $this->showSchoolName = $schoolId === null;
        }

        $this->initialQuery = $initialQuery;
        $this->query = $initialQuery;
    }

    /**
     * @return Collection<int, User>
     */
    public function getResultsProperty(): Collection
    {
        if ($this->selectedStudentId !== null) {
            return collect();
        }

        return app(ParentLinkRequestService::class)
            ->searchStudentsForAdmin($this->query, $this->schoolId);
    }

    public function selectStudent(int $studentId): void
    {
        $student = $this->results->firstWhere('id', $studentId);
        if ($student === null) {
            $student = $this->authorizedStudent($studentId);
        }

        if ($student === null) {
            return;
        }

        $this->selectedStudentId = $student->id;
        $classLabel = $student->studentAcademicLatest?->standardLink?->StandardSection ?? 'class n/a';
        $schoolBit = $this->showSchoolName && $student->school
            ? ' · '.$student->school->name
            : '';
        $labelName = $student->displayName ?: $student->name;
        $this->selectedLabel = $labelName.' ('.$classLabel.')'.$schoolBit;
        $this->query = $labelName;
    }

    public function clearSelection(): void
    {
        $this->selectedStudentId = null;
        $this->selectedLabel = '';
        $this->query = $this->initialQuery;
    }

    public function render(): View
    {
        return view('livewire.admin.parent-link-student-picker', [
            'results' => $this->results,
        ]);
    }

    private function authorizedStudent(int $studentId): ?User
    {
        $query = User::query()
            ->where('id', $studentId)
            ->where('usergroup_id', 6)
            ->where('status', 'active')
            ->with(['studentAcademicLatest.standardLink.section', 'school']);

        if ($this->schoolId !== null) {
            $query->where('school_id', $this->schoolId);
        }

        return $query->first();
    }
}
