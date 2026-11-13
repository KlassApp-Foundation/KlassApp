<?php

namespace App\Http\Controllers\Admin\Setting;

use App\Http\Controllers\Controller;
use App\Helpers\GradingHelper;
use App\Models\AcademicTerm;
use App\Models\Academics\ExamType;
use App\Models\School;
use App\Services\Results\ReportComposition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Exam types are shared-or-owned: the catalog rows (school_id NULL) every school
 * sees, plus the rows a school adds for itself. The page lists both, toggles a
 * type's report-total contribution per school, and creates the school's own.
 */
class ExamTypeController extends Controller
{
    public function index()
    {
        $schoolId = Auth::user()->school_id;
        $school = School::find($schoolId);
        $composer = app(ReportComposition::class);
        $examTypes = $composer->orderedTypes((int) $schoolId);
        $prefs = $this->jsonColumn($school, 'exam_type_preferences');
        $stored = is_array($school?->report_composition) && $school->report_composition !== [];
        $composition = $stored ? $composer->resolved($school) : $composer->suggested($examTypes, (int) $schoolId);
        $labels = is_array($school?->exam_type_labels) ? $school->exam_type_labels : [];
        $names = [];
        foreach ($examTypes as $type) {
            $custom = trim((string) ($labels[(string) $type->id] ?? $labels[$type->id] ?? ''));
            $names[(int) $type->id] = $custom !== '' ? $custom : (string) $type->name;
        }
        $madeUpOf = $composer->madeUpOf($composition, $names);
        $mix = $composer->mixSummary($composition, $names);
        $preset = $composer->matchingPreset($examTypes, $composition);
        $ownReports = $school ? $composer->ownReports((int) $school->id) : [];
        $showMarksLater = ! ($school && $composer->hasReleasedCountedMark((int) $school->id, $composition));
        $profiles = config('curricula.profiles', []);
        $profileKey = GradingHelper::profileKeyForSchool($schoolId);
        $legacyBands = GradingHelper::legacyBandsForSchool($schoolId);
        $legacyIsExample = $school?->legacy_o_level_bands === null;

        return view('admin.settings.exam-types', compact(
            'examTypes', 'prefs', 'composition', 'profiles', 'profileKey', 'legacyBands', 'legacyIsExample',
            'madeUpOf', 'mix', 'preset', 'ownReports', 'showMarksLater', 'labels'
        ));
    }

    public function update(Request $request)
    {
        $schoolId = Auth::user()->school_id;
        $school = School::findOrFail($schoolId);
        $visibleIds = $this->visibleTypeIds((int) $schoolId);

        $compositionSent = $request->exists('include') || $request->exists('weight') || $request->exists('mode');
        $composition = $compositionSent
            ? app(ReportComposition::class)->fromRequest(
                (array) $request->input('include', []),
                (array) $request->input('weight', []),
                $visibleIds,
                (array) $request->input('mode', []),
            )
            : $school->report_composition;

        // Only this school's types are toggles it may set: posting another
        // school's id leaves no trace in this school's preferences.
        $prefs = [];
        foreach ((array) $request->input('contributes', []) as $typeId => $on) {
            $typeId = (int) $typeId;
            if (in_array($typeId, $visibleIds, true)) {
                $prefs[(string) $typeId] = filter_var($on, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
            }
        }
        $school->exam_type_preferences = $prefs ?: null;
        $school->report_composition = $composition ?: null;
        $school->exam_type_labels = $this->labelsFromRequest($request, $visibleIds, $school);

        $school->save();
        $this->storeCombinedReport($school, is_array($composition) ? $composition : null);

        return redirect()->route('admin.settings.exam-types')
            ->with('successmessage', 'Exam type preferences updated.');
    }

    /**
     * Add an exam type that belongs to this school only: shared rows stay
     * untouched and no other school sees the new row.
     */
    public function store(Request $request)
    {
        $schoolId = (int) Auth::user()->school_id;
        $school = School::findOrFail($schoolId);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'code' => ['required', 'string', 'max:10'],
            'short_name' => ['nullable', 'string', 'max:24'],
            'contributes_to_report_total' => ['nullable', 'boolean'],
        ]);

        $name = trim($data['name']);
        $code = strtoupper(trim($data['code']));

        $taken = ExamType::visibleToSchool($schoolId)
            ->where(function ($query) use ($name, $code) {
                $query->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                    ->orWhereRaw('UPPER(code) = ?', [$code]);
            })
            ->exists();

        if ($taken) {
            return back()->withInput()->withErrors([
                'name' => 'An exam type called "'.$name.'" or coded '.$code.' already exists for your school.',
            ]);
        }

        $short = trim((string) ($data['short_name'] ?? ''));
        ExamType::create([
            'school_id' => $school->id,
            'name' => $name,
            'code' => $code,
            'short_name' => $short === '' ? null : $short,
            'contributes_to_report_total' => $request->boolean('contributes_to_report_total'),
        ]);

        return redirect()->route('admin.settings.exam-types')
            ->with('successmessage', $name.' added to your exam types. Only your school sees it.');
    }

    /**
     * Names the school typed for its exam sets. The catalog row is unchanged.
     * An empty box keeps the catalog name.
     *
     * @param  list<int>  $visibleIds
     * @return array<string, string>|null
     */
    private function labelsFromRequest(Request $request, array $visibleIds, School $school): ?array
    {
        $labels = is_array($school->exam_type_labels) ? $school->exam_type_labels : [];
        foreach ((array) $request->input('type_name', []) as $typeId => $raw) {
            $typeId = (int) $typeId;
            if (! in_array($typeId, $visibleIds, true)) {
                continue;
            }
            $name = trim((string) $raw);
            if ($name === '' || mb_strlen($name) > 80) {
                unset($labels[(string) $typeId]);
                continue;
            }
            $labels[(string) $typeId] = $name;
        }

        return $labels === [] ? null : $labels;
    }

    /** @return list<int> */
    private function visibleTypeIds(int $schoolId): array
    {
        return ExamType::visibleToSchool($schoolId)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * One "Term report" row for the current term. A second name on this table
     * is how another combined report will be added later.
     *
     * @param  array<int|string, mixed>|null  $composition
     */
    private function storeCombinedReport(School $school, ?array $composition): void
    {
        if (! is_array($composition) || $composition === [] || ! Schema::hasTable('combined_reports')) {
            return;
        }

        $termId = AcademicTerm::query()
            ->where('school_id', $school->id)
            ->where('status', 'current')
            ->orderByDesc('id')
            ->value('id');

        $existing = DB::table('combined_reports')
            ->where('school_id', $school->id)
            ->where('name', 'Term report')
            ->when($termId === null, fn ($query) => $query->whereNull('academic_term_id'), fn ($query) => $query->where('academic_term_id', $termId))
            ->value('id');

        if ($existing === null) {
            $existing = DB::table('combined_reports')->insertGetId([
                'school_id' => $school->id,
                'academic_term_id' => $termId,
                'name' => 'Term report',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('combined_reports')->where('id', $existing)->update(['updated_at' => now()]);
        }

        DB::table('combined_report_components')->where('combined_report_id', $existing)->delete();
        $rows = [];
        foreach ($composition as $key => $settings) {
            if (! is_numeric($key) || ! is_array($settings) || ! array_key_exists('include', $settings)) {
                continue;
            }
            $counts = $settings['include'] === true || $settings['include'] === 1 || $settings['include'] === '1';
            $rows[] = [
                'combined_report_id' => $existing,
                'exam_type_id' => (int) $key,
                'mode' => $counts ? 'count' : 'alone',
                'weight' => $counts && is_numeric($settings['weight'] ?? null) ? $settings['weight'] : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        if ($rows !== []) {
            DB::table('combined_report_components')->insert($rows);
        }
    }

    public function updateProfile(Request $request)
    {
        $schoolId = Auth::user()->school_id;
        $school = School::findOrFail($schoolId);

        $validated = $request->validate([
            'curriculum_profile' => ['nullable', 'string', 'max:40'],
        ]);
        $key = trim((string) ($validated['curriculum_profile'] ?? ''));
        $profiles = config('curricula.profiles', []);
        abort_unless($key === '' || isset($profiles[$key]), 422, 'Unknown curriculum profile.');
        $school->curriculum_profile = $key === '' ? null : $key;
        $school->save();

        foreach (['nursery', 'primary', 'o-level', 'a-level'] as $levelType) {
            GradingHelper::seedGradingForSchool($schoolId, $levelType);
        }

        return redirect()->route('admin.settings.exam-types')
            ->with('successmessage', 'Curriculum profile applied. Grading bands were refreshed for this school.');
    }

    /**
     * Save this school's own legacy O-level boundaries.
     *
     * UNEB never published one fixed pre-NLSC table, so the boundaries belong
     * to the school. The letters and points are fixed (D1 best to F9, 1 to 9);
     * only the cut-offs are editable, and they must run from 100 down to 0 with
     * no gap and no overlap, or the scale cannot grade every mark.
     */
    public function updateBands(Request $request)
    {
        $schoolId = Auth::user()->school_id;
        $school = School::findOrFail($schoolId);

        $rows = (array) $request->input('bands', []);
        $clean = [];

        foreach (GradingHelper::legacyLetters() as $letter) {
            $grade = $letter['grade'];
            $row = $rows[$grade] ?? null;
            if (! is_array($row)) {
                throw ValidationException::withMessages([
                    'bands' => "Enter a range for {$grade}.",
                ]);
            }

            $min = filter_var($row['min_score'] ?? null, FILTER_VALIDATE_INT);
            $max = filter_var($row['max_score'] ?? null, FILTER_VALIDATE_INT);

            if ($min === false || $max === false || $min < 0 || $max > 100 || $min > $max) {
                throw ValidationException::withMessages([
                    'bands' => "The {$grade} range must be two whole marks from 0 to 100, lowest first.",
                ]);
            }

            $clean[] = ['grade' => $grade, 'points' => $letter['points'], 'min_score' => $min, 'max_score' => $max];
        }

        $expectedMax = 100;
        foreach ($clean as $band) {
            if ($band['max_score'] !== $expectedMax) {
                throw ValidationException::withMessages([
                    'bands' => 'The bands must run from 100 down to 0 with no gaps and no overlaps.',
                ]);
            }
            $expectedMax = $band['min_score'] - 1;
        }
        if ($expectedMax !== -1) {
            throw ValidationException::withMessages([
                'bands' => 'The lowest band must start at 0.',
            ]);
        }

        $school->legacy_o_level_bands = $clean;
        $school->save();

        GradingHelper::seedGradingForSchool($schoolId, 'o-level');

        return redirect()->route('admin.settings.exam-types')
            ->with('successmessage', "This school's O-level bands were saved and its grading scale refreshed.");
    }

    private function jsonColumn(?School $school, string $column): array
    {
        if ($school === null || $school->$column === null) {
            return [];
        }

        return is_array($school->$column) ? $school->$column : (json_decode((string) $school->$column, true) ?? []);
    }
}
