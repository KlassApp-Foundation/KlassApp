<?php

namespace App\Http\Controllers\Admin\Setting;

use App\Http\Controllers\Controller;
use App\Helpers\GradingHelper;
use App\Models\Academics\ExamType;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ExamTypeController extends Controller
{
    public function index()
    {
        $schoolId = Auth::user()->school_id;
        $examTypes = ExamType::orderBy('id')->get();

        // Load current toggles: each school's preference is stored in a JSON column
        // on the schools table, or falls back to the exam_types default.
        $school = School::find($schoolId);
        $prefs = $this->jsonColumn($school, 'exam_type_preferences');
        $composition = $this->jsonColumn($school, 'report_composition');
        $profiles = config('curricula.profiles', []);
        $profileKey = GradingHelper::profileKeyForSchool($schoolId);
        $legacyBands = GradingHelper::legacyBandsForSchool($schoolId);
        $legacyIsExample = $school?->legacy_o_level_bands === null;

        return view('admin.settings.exam-types', compact(
            'examTypes', 'prefs', 'composition', 'profiles', 'profileKey', 'legacyBands', 'legacyIsExample'
        ));
    }

    public function update(Request $request)
    {
        $schoolId = Auth::user()->school_id;
        $school = School::findOrFail($schoolId);

        $prefs = $request->input('contributes', []);
        $school->exam_type_preferences = $prefs;

        $composition = [];
        foreach ((array) $request->input('include', []) as $typeId => $on) {
            $composition[(string) (int) $typeId] = ['include' => (string) $on === '1', 'weight' => null];
        }
        foreach ((array) $request->input('weight', []) as $typeId => $weight) {
            $key = (string) (int) $typeId;
            $weight = trim((string) $weight);
            if ($weight === '') {
                continue;
            }
            if (! is_numeric($weight) || (float) $weight <= 0 || (float) $weight > 100) {
                continue;
            }
            $composition[$key] = ($composition[$key] ?? ['include' => true, 'weight' => null]) + ['weight' => (float) $weight];
        }
        foreach ($composition as $key => $settings) {
            $composition[$key] = ['include' => true, 'weight' => null] + $settings;
        }
        $school->report_composition = $composition ?: null;

        $school->save();

        return redirect()->route('admin.settings.exam-types')
            ->with('successmessage', 'Exam type preferences updated.');
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
