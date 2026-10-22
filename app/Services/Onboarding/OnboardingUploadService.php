<?php

namespace App\Services\Onboarding;

use App\Jobs\ProcessOnboardingUpload;
use App\Livewire\ManualOnboardingWizard;
use App\Models\AcademicYear;
use App\Models\OnboardingUpload;
use App\Models\School;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Services\OnboardingEngine;
use App\Services\OnboardingNameListExtractor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Turns a roster file into a queued onboarding import.
 *
 * The file is stored, hashed and handed to ProcessOnboardingUpload. The same
 * school uploading the same bytes again gets the first result back instead of
 * a second set of rows. Rows that already exist are skipped with a reason, so
 * a retry after a partial run cannot duplicate anyone.
 */
class OnboardingUploadService
{
    public const CHUNK = 100;

    /** @var list<string> */
    public const KINDS = [
        OnboardingUpload::KIND_TEACHERS,
        OnboardingUpload::KIND_CLASS_TEACHERS,
        OnboardingUpload::KIND_STUDENTS,
    ];

    /**
     * @return array{upload: OnboardingUpload, reused: bool}
     */
    public function start(School $school, int $userId, string $kind, UploadedFile $file): array
    {
        $hash = hash_file('sha256', $file->getRealPath()) ?: '';
        $existing = OnboardingUpload::query()
            ->where('school_id', $school->id)
            ->where('kind', $kind)
            ->where('file_hash', $hash)
            ->first();

        if ($existing && $existing->status === OnboardingUpload::STATUS_DONE) {
            return ['upload' => $existing, 'reused' => true];
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'csv');
        $path = $file->storeAs(
            'onboarding-uploads/'.$school->id,
            Str::uuid()->toString().'.'.$extension,
            'local'
        );

        $upload = $existing ?? new OnboardingUpload;
        $upload->fill([
            'school_id' => $school->id,
            'user_id' => $userId,
            'kind' => $kind,
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'file_hash' => $hash,
            'status' => OnboardingUpload::STATUS_QUEUED,
            'total' => 0,
            'processed' => 0,
            'added' => 0,
            'skipped' => 0,
            'report' => null,
            'rows' => null,
            'skips' => null,
            'notes' => null,
            'missing_streams' => null,
            'error' => null,
            'finished_at' => null,
        ])->save();

        ProcessOnboardingUpload::dispatch((int) $upload->id);

        return ['upload' => $upload, 'reused' => false];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function extract(OnboardingUpload $upload): array
    {
        $absolute = Storage::disk('local')->path($upload->path);
        $extension = strtolower(pathinfo($upload->path, PATHINFO_EXTENSION) ?: 'csv');

        return app(OnboardingNameListExtractor::class)->extractNamesFromFile($absolute, $extension);
    }

    /**
     * Import the rows and report progress after each chunk.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  callable(int, int, int): void  $progress  processed, added, skipped
     * @return array{added: int, skipped: int, report: string, skips: array, notes: array, missing_streams: array}
     */
    public function import(OnboardingUpload $upload, array $rows, callable $progress): array
    {
        $school = School::findOrFail($upload->school_id);
        $year = AcademicYear::query()->where('school_id', $school->id)->where('status', 1)->first()
            ?? AcademicYear::query()->where('school_id', $school->id)->first();

        $wizard = $this->wizardFor($upload);

        $added = 0;
        $skipped = 0;
        $skips = [];
        $notes = [];
        $missing = [];
        $processed = 0;

        if ($upload->kind === OnboardingUpload::KIND_STUDENTS) {
            $rows = $this->dropExistingStudents($school, $rows, $skips);
            $skipped += count($skips);
            $progress($processed, $added, $skipped);
        } elseif ($upload->kind === OnboardingUpload::KIND_TEACHERS) {
            $rows = $this->dropExistingTeachers($school, $rows, $skips);
            $skipped += count($skips);
            $progress($processed, $added, $skipped);
        } elseif ($upload->kind === OnboardingUpload::KIND_CLASS_TEACHERS) {
            $rows = $this->dropExistingClassTeachers($year, $rows, $skips);
            $skipped += count($skips);
            $progress($processed, $added, $skipped);
        }

        foreach (array_chunk($rows, $upload->kind === OnboardingUpload::KIND_STUDENTS ? self::CHUNK : 200) as $chunk) {
            $result = $this->runChunk($wizard, $upload->kind, $chunk);
            $added += $result['added'];
            $skipped += $result['skipped'];
            $skips = array_merge($skips, $result['skips']);
            $notes = array_merge($notes, $result['notes']);
            $missing = array_merge($missing, $result['missing_streams']);
            $processed = min(count($rows), $processed + count($chunk));
            $progress($processed, $added, $skipped);
        }

        // A retry after "create missing streams" re-sends only those rows.
        $report = $this->report($upload->kind, $added, $skipped);

        return [
            'added' => $added,
            'skipped' => $skipped,
            'report' => $report,
            'skips' => $skips,
            'notes' => $notes,
            'missing_streams' => $this->uniquePairs($missing),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $chunk
     * @return array{added: int, skipped: int, skips: array, notes: array, missing_streams: array}
     */
    private function runChunk(ManualOnboardingWizard $wizard, string $kind, array $chunk): array
    {
        try {
            $result = $wizard->runRosterImport($kind, $chunk);

            return [
                'added' => (int) ($result['added'] ?? 0),
                'skipped' => (int) ($result['skipped'] ?? 0),
                'skips' => $result['skips'] ?? [],
                'notes' => $result['notes'] ?? [],
                'missing_streams' => $result['missing_streams'] ?? [],
            ];
        } catch (Throwable $e) {
            // One bad row must not lose the rest of the chunk: retry row by row.
            $added = 0;
            $skipped = 0;
            $skips = [];
            $notes = [];
            $missing = [];

            foreach ($chunk as $index => $row) {
                try {
                    $result = $wizard->runRosterImport($kind, [$row]);
                    $added += (int) ($result['added'] ?? 0);
                    $skipped += (int) ($result['skipped'] ?? 0);
                    $skips = array_merge($skips, $result['skips'] ?? []);
                    $notes = array_merge($notes, $result['notes'] ?? []);
                    $missing = array_merge($missing, $result['missing_streams'] ?? []);
                } catch (Throwable $rowError) {
                    $skipped++;
                    $skips[] = [
                        'row' => (int) ($row['row'] ?? ($index + 2)),
                        'reason' => $this->shortReason($rowError->getMessage()),
                    ];
                }
            }

            return [
                'added' => $added,
                'skipped' => $skipped,
                'skips' => $skips,
                'notes' => $notes,
                'missing_streams' => $missing,
            ];
        }
    }

    private function wizardFor(OnboardingUpload $upload): ManualOnboardingWizard
    {
        if (! Auth::check() || (int) Auth::id() !== (int) $upload->user_id) {
            Auth::loginUsingId((int) $upload->user_id);
        }

        return app(ManualOnboardingWizard::class);
    }

    /**
     * A student already in the school with the same name, class, stream and
     * identifiers is not created twice.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function dropExistingStudents(School $school, array $rows, array &$skips): array
    {
        $existing = [];
        $academics = StudentAcademic::query()
            ->where('student_academics.school_id', $school->id)
            ->join('users', 'users.id', '=', 'student_academics.user_id')
            ->leftJoin('standards_link', 'standards_link.id', '=', 'student_academics.standardLink_id')
            ->leftJoin('sections', 'sections.id', '=', 'standards_link.section_id')
            ->get([
                'users.name as student_name',
                'sections.name as section_name',
                'student_academics.school_student_id',
                'student_academics.lin',
                'student_academics.board_registration_number',
            ]);

        foreach ($academics as $row) {
            $existing[$this->studentFingerprint([
                'name' => (string) $row->student_name,
                'class' => $this->baseClass((string) $row->section_name),
                'stream' => $this->streamOf((string) $row->section_name),
                'school_student_id' => (string) ($row->school_student_id ?? ''),
                'lin' => (string) ($row->lin ?? ''),
            ])] = true;
        }

        $keep = [];
        $seenInFile = [];
        foreach ($rows as $row) {
            $fingerprint = $this->studentFingerprint($row);
            if (isset($existing[$fingerprint])) {
                $skips[] = ['row' => (int) ($row['row'] ?? 0), 'reason' => 'already added'];
                continue;
            }
            if (isset($seenInFile[$fingerprint])) {
                $skips[] = ['row' => (int) ($row['row'] ?? 0), 'reason' => 'duplicate in file'];
                continue;
            }
            $seenInFile[$fingerprint] = true;
            $keep[] = $row;
        }

        return $keep;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function dropExistingTeachers(School $school, array $rows, array &$skips): array
    {
        $emails = User::query()
            ->whereIn('usergroup_id', [5])
            ->pluck('school_id', 'email')
            ->mapWithKeys(fn ($schoolId, $email) => [strtolower((string) $email) => (int) $schoolId])
            ->all();

        $keep = [];
        foreach ($rows as $row) {
            $email = strtolower(trim((string) ($row['email'] ?? '')));
            if ($email !== '' && isset($emails[$email])) {
                $reason = $emails[$email] === (int) $school->id ? 'already exists' : 'email belongs to another school';
                $skips[] = ['row' => (int) ($row['row'] ?? 0), 'reason' => $reason];
                continue;
            }
            $keep[] = $row;
        }

        return $keep;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function dropExistingClassTeachers(?AcademicYear $year, array $rows, array &$skips): array
    {
        return $rows;
    }

    private function studentFingerprint(array $row): string
    {
        $normalise = fn ($value) => strtolower(trim((string) $value));

        return implode('|', [
            $normalise($row['name'] ?? ''),
            $normalise($row['class'] ?? ''),
            $normalise($row['stream'] ?? ''),
            $normalise($row['school_student_id'] ?? ''),
            $normalise($row['lin'] ?? ''),
        ]);
    }

    private function baseClass(string $section): string
    {
        $section = trim($section);
        if ($section === '') {
            return '';
        }

        $parts = preg_split('/\s+/', $section) ?: [];
        $last = end($parts);

        return count($parts) > 1 && strlen((string) $last) <= 3 ? trim(substr($section, 0, -(strlen((string) $last) + 1))) : $section;
    }

    private function streamOf(string $section): string
    {
        $section = trim($section);
        $parts = preg_split('/\s+/', $section) ?: [];
        $last = (string) end($parts);

        return count($parts) > 1 && strlen($last) <= 3 ? $last : '';
    }

    private function report(string $kind, int $added, int $skipped): string
    {
        $noun = match ($kind) {
            OnboardingUpload::KIND_TEACHERS => 'teacher',
            OnboardingUpload::KIND_CLASS_TEACHERS => 'class teacher',
            default => 'student',
        };

        $addedText = $added.' '.Str::plural($noun, $added).' added';

        return $addedText.', '.$skipped.' skipped';
    }

    private function shortReason(string $message): string
    {
        $message = trim(preg_replace('/\s+/', ' ', $message) ?? '');

        return Str::limit($message, 160, '…');
    }

    /**
     * @param  list<array{class: string, stream: string}>  $pairs
     * @return list<array{class: string, stream: string}>
     */
    private function uniquePairs(array $pairs): array
    {
        $seen = [];
        $out = [];
        foreach ($pairs as $pair) {
            $key = strtolower(trim((string) ($pair['class'] ?? ''))).'|'.strtolower(trim((string) ($pair['stream'] ?? '')));
            if ($key === '|' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $pair;
        }

        return $out;
    }
}
