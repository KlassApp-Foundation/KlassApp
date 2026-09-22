<?php
/**
 * SPDX-License-Identifier: MIT
 *
 * Health overview landing for /admin/health, replacing a closure that just redirected to
 * the student list. Health data is tracked per student (profiles, immunizations and
 * incidents), so the useful overview is a school-level summary of that same data rather
 * than a duplicate student list.
 *
 * Everything is scoped with whereSchool(Auth::user()->school_id); no school id is ever
 * taken from the request.
 */
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentHealthIncident;
use App\Models\StudentHealthProfile;
use App\Models\StudentImmunization;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StudentHealthOverviewController extends Controller
{
    public function index()
    {
        $schoolId = (int) Auth::user()->school_id;
        $today = now()->toDateString();
        $since30 = now()->subDays(30)->toDateString();

        $profiles = StudentHealthProfile::whereSchool($schoolId)->count();
        $allergies = StudentHealthProfile::whereSchool($schoolId)
            ->whereNotNull('allergies')->where('allergies', '!=', '')->count();
        $chronic = StudentHealthProfile::whereSchool($schoolId)
            ->whereNotNull('chronic_conditions')->where('chronic_conditions', '!=', '')->count();

        $immunizations = StudentImmunization::whereSchool($schoolId)->count();
        $overdue = StudentImmunization::whereSchool($schoolId)
            ->whereNotNull('next_due_date')->where('next_due_date', '<', $today)->count();

        $incidents30 = StudentHealthIncident::whereSchool($schoolId)
            ->where('incident_date', '>=', $since30)->count();

        $bySeverity = StudentHealthIncident::whereSchool($schoolId)
            ->where('incident_date', '>=', $since30)
            ->select('severity', DB::raw('count(*) as total'))
            ->groupBy('severity')
            ->pluck('total', 'severity');

        $recent = StudentHealthIncident::whereSchool($schoolId)
            ->orderByDesc('incident_date')
            ->take(6)
            ->get();

        $names = User::whereIn('id', $recent->pluck('user_id')->filter()->unique()->all())
            ->pluck('name', 'id');

        $recentRows = $recent->map(fn ($incident) => [
            'date' => $incident->incident_date ? \Carbon\Carbon::parse($incident->incident_date)->format('d M Y') : '-',
            'student' => $names[$incident->user_id] ?? 'Student',
            'severity' => $incident->severity ?: 'minor',
            'description' => \Illuminate\Support\Str::limit((string) $incident->description, 90),
            'action' => $incident->action_taken ? \Illuminate\Support\Str::limit((string) $incident->action_taken, 60) : null,
        ])->values()->all();

        return view('admin.health.index', [
            'profiles' => $profiles,
            'allergies' => $allergies,
            'chronic' => $chronic,
            'immunizations' => $immunizations,
            'overdue' => $overdue,
            'incidents30' => $incidents30,
            'bySeverity' => $bySeverity,
            'recentRows' => $recentRows,
        ]);
    }
}
