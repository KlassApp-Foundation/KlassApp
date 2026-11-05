<?php

namespace App\Livewire\Admin;

use App\Models\AcademicYear;
use App\Models\OnboardingUpload;
use App\Models\School;
use App\Models\User;
use App\Services\ClassStructureService;
use App\Services\Onboarding\OnboardingUploadService;
use App\Services\OnboardingEngine;
use App\Services\OnboardingNameListExtractor;
use App\Services\ToshiActionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * The student list's Import and Add page. It runs the onboarding wizard's
 * student import: the same template, the same queued chunks, and the same
 * preview, errors and missing-stream retry.
 */
class ImportStudents extends Component
{
    use WithFileUploads;

    public $studentUpload = null;

    public string $studentUploadReport = '';

    /** @var list<array<string, mixed>> */
    public array $studentUploadSkips = [];

    /** @var list<array<string, mixed>> */
    public array $studentUploadNotes = [];

    /** @var list<array<string, mixed>> */
    public array $studentUploadRows = [];

    /** @var list<array{class: string, stream: string}> */
    public array $studentMissingStreams = [];

    /** @var array<string, mixed>|null */
    public ?array $activeUpload = null;

    public string $errorMessage = '';

    public ?string $overLimitNotice = null;

    /** @var list<array<string, mixed>> */
    public array $structureClasses = [];

    public string $studentName = '';

    public string $studentClass = '';

    public string $studentStream = '';

    public string $studentParent = '';

    public string $studentParentPhone = '';

    public string $studentGender = '';

    public function mount(): void
    {
        $this->refreshStructure();
    }

    public function updatedStudentUpload(): void
    {
        $file = $this->studentUpload;
        if (! $file) {
            return;
        }

        try {
            $this->validate([
                'studentUpload' => 'file|max:10240',
            ]);
        } catch (ValidationException $e) {
            $this->errorMessage = collect($e->errors())->flatten()->first() ?: 'Upload failed.';

            return;
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: '');
        if (! in_array($extension, OnboardingNameListExtractor::ACCEPTED_MIMES, true)) {
            $this->errorMessage = 'Supported files: CSV, XLSX, TXT, DOCX, PDF.';

            return;
        }

        $this->errorMessage = '';
        $result = app(OnboardingUploadService::class)
            ->start($this->school(), (int) Auth::id(), OnboardingUpload::KIND_STUDENTS, $file);

        $this->studentUpload = null;
        $this->activeUpload = $result['upload']->progress();

        if ($result['reused']) {
            if ($result['upload']->isFinished()) {
                $this->applyFinishedUpload($result['upload']->fresh());
            }

            return;
        }

        $inline = config('queue.default') === 'sync'
            || (app()->runningUnitTests() && (config('onboarding.uploads.run_inline_in_tests') ?? true));

        if ($inline) {
            (new \App\Jobs\ProcessOnboardingUpload((int) $result['upload']->id))
                ->handle(app(OnboardingUploadService::class));
            $this->applyFinishedUpload($result['upload']->fresh());
        }
    }

    public function refreshUpload(): void
    {
        $id = (int) ($this->activeUpload['id'] ?? 0);
        if ($id < 1) {
            return;
        }

        $upload = OnboardingUpload::query()
            ->where('school_id', $this->school()->id)
            ->find($id);
        if (! $upload) {
            $this->activeUpload = null;

            return;
        }

        $this->activeUpload = $upload->progress();
        if ($upload->isFinished()) {
            $this->applyFinishedUpload($upload);
        }
    }

    public function createMissingStreamsAndRetry(string $upload = 'students'): void
    {
        if ($upload !== 'students') {
            return;
        }

        $school = $this->school();
        $year = $this->year();
        if (! $year) {
            $this->errorMessage = 'Create an academic year first.';

            return;
        }

        if ($this->studentMissingStreams !== []) {
            app(OnboardingEngine::class)->createMissingStreams($school, $year, $this->studentMissingStreams);
        }

        $this->importRows($this->studentUploadRows);
        $this->refreshStructure();
    }

    public function addStudent(): void
    {
        $name = trim($this->studentName);
        if ($name === '') {
            $this->errorMessage = 'Enter a student name.';

            return;
        }

        $gender = strtolower(trim($this->studentGender));
        if (! in_array($gender, ['male', 'female'], true)) {
            $gender = '';
        }

        $this->importRows([[
            'name' => $name,
            'class' => trim($this->studentClass),
            'stream' => trim($this->studentStream),
            'parent' => trim($this->studentParent),
            'parent_phone' => trim($this->studentParentPhone),
            'gender' => $gender,
            'row' => 0,
        ]]);

        if ($this->errorMessage === '' && $this->studentMissingStreams === []) {
            $this->studentName = '';
            $this->studentClass = '';
            $this->studentStream = '';
            $this->studentParent = '';
            $this->studentParentPhone = '';
            $this->studentGender = '';
        }
    }

    public function render()
    {
        return view('livewire.admin.import-students');
    }

    private function applyFinishedUpload(OnboardingUpload $upload): void
    {
        $this->activeUpload = $upload->progress();

        if ($upload->status === OnboardingUpload::STATUS_FAILED) {
            $this->errorMessage = 'Import failed: '.Str::limit((string) $upload->error, 200);

            return;
        }

        $this->studentUploadSkips = is_array($upload->skips) ? $upload->skips : [];
        $this->studentUploadNotes = is_array($upload->notes) ? $upload->notes : [];
        $this->studentUploadReport = (string) $upload->report;
        $this->studentUploadRows = is_array($upload->rows) ? $upload->rows : [];
        $this->studentMissingStreams = is_array($upload->missing_streams) ? $upload->missing_streams : [];
        $this->notePlanLimit();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importRows(array $rows): void
    {
        $school = $this->school();
        $year = $this->year();
        if (! $year) {
            $this->errorMessage = 'Create an academic year first.';

            return;
        }

        $deduped = app(OnboardingUploadService::class)->filterExistingStudents($school, $rows);
        $pending = $deduped['rows'];
        $skipped = $deduped['skips'];

        $missing = app(OnboardingEngine::class)->collectMissingStreams($school, $year, $pending);
        if (($missing['pairs'] ?? []) !== []) {
            $this->studentMissingStreams = $missing['pairs'];
            $this->studentUploadSkips = array_merge($skipped, $missing['skips'] ?? []);
            $this->studentUploadRows = $pending;
            $added = 0;
            $skipCount = count($this->studentUploadSkips);
            $this->studentUploadReport = $added.' '.Str::plural('student', $added).' added, '.$skipCount.' skipped';
            $this->errorMessage = (string) ($missing['skips'][0]['reason'] ?? "A stream doesn't exist yet.");

            return;
        }

        try {
            $saved = app(OnboardingEngine::class)->saveStudents($school, $year, $pending);
        } catch (ValidationException $e) {
            $this->errorMessage = collect($e->errors())->flatten()->first() ?: 'Import failed.';

            return;
        }

        $created = count($saved['created'] ?? []);
        $skipped = array_merge($skipped, $saved['skipped'] ?? []);
        $this->studentMissingStreams = [];
        $this->studentUploadSkips = $skipped;
        $this->studentUploadRows = $pending;
        $this->studentUploadReport = $created.' '.Str::plural('student', $created).' added, '.count($skipped).' skipped';
        $this->errorMessage = '';
        $this->notePlanLimit();
    }

    private function notePlanLimit(): void
    {
        $over = ToshiActionService::enforcePlanLimit($this->school()->id, 'students');
        $this->overLimitNotice = $over['success'] ? null : ($over['message'] ?? null);
    }

    private function refreshStructure(): void
    {
        $year = $this->year();
        $this->structureClasses = $year
            ? app(ClassStructureService::class)->structureSnapshot($this->school(), $year)
            : [];
    }

    private function school(): School
    {
        /** @var User $user */
        $user = Auth::user();

        return School::query()->findOrFail($user->school_id);
    }

    private function year(): ?AcademicYear
    {
        return AcademicYear::query()->where('school_id', $this->school()->id)->where('status', 1)->first()
            ?? AcademicYear::query()->where('school_id', $this->school()->id)->first();
    }
}
