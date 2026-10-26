<?php

namespace App\Http\Controllers\Admin\Setting;

use App\Http\Controllers\Controller;
use App\Helpers\GradingHelper;
use App\Models\Academics\ExamType;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        return view('admin.settings.exam-types', compact(
            'examTypes', 'prefs', 'composition', 'profiles', 'profileKey'
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

    private function jsonColumn(?School $school, string $column): array
    {
        if ($school === null || $school->$column === null) {
            return [];
        }

        return is_array($school->$column) ? $school->$column : (json_decode((string) $school->$column, true) ?? []);
    }
}
