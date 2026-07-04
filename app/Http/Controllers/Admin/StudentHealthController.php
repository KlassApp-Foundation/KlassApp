<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentHealthProfile;
use App\Models\StudentImmunization;
use App\Models\StudentHealthIncident;
use App\Models\User;
use App\Services\OutboundWhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentHealthController extends Controller
{
    public function __construct(
        private OutboundWhatsAppService $whatsapp
    ) {}

    /**
     * Show the health tab for a student (profile + immunizations + incidents).
     */
    public function index($userId)
    {
        $schoolId = Auth::user()->school_id;
        $student = User::where('school_id', $schoolId)->findOrFail($userId);

        $profile = StudentHealthProfile::whereSchool($schoolId)
            ->where('user_id', $userId)
            ->first();

        $immunizations = StudentImmunization::whereSchool($schoolId)
            ->where('user_id', $userId)
            ->orderBy('administered_date', 'desc')
            ->get();

        $incidents = StudentHealthIncident::whereSchool($schoolId)
            ->where('user_id', $userId)
            ->orderBy('incident_date', 'desc')
            ->get();

        return view('admin.health.index', compact('student', 'profile', 'immunizations', 'incidents'));
    }

    // ─── Profile ───────────────────────────────────────────────

    public function updateProfile(Request $request, $userId)
    {
        $schoolId = Auth::user()->school_id;
        $student = User::where('school_id', $schoolId)->findOrFail($userId);

        $validated = $request->validate([
            'allergies' => 'nullable|string|max:5000',
            'chronic_conditions' => 'nullable|string|max:5000',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'blood_type' => 'nullable|string|max:10',
            'medical_notes' => 'nullable|string|max:5000',
        ]);

        $profile = StudentHealthProfile::updateOrCreate(
            ['school_id' => $schoolId, 'user_id' => $userId],
            $validated
        );

        return redirect()->back()->with('success', 'Health profile updated.');
    }

    // ─── Immunizations ─────────────────────────────────────────

    public function storeImmunization(Request $request, $userId)
    {
        $schoolId = Auth::user()->school_id;
        $student = User::where('school_id', $schoolId)->findOrFail($userId);

        $validated = $request->validate([
            'vaccine_name' => 'required|string|max:255',
            'administered_date' => 'required|date',
            'administered_by' => 'nullable|string|max:255',
            'next_due_date' => 'nullable|date|after:administered_date',
            'notes' => 'nullable|string|max:5000',
        ]);

        $immunization = StudentImmunization::create([
            'school_id' => $schoolId,
            'user_id' => $userId,
            ...$validated,
        ]);

        return redirect()->back()->with('success', 'Immunization record added.');
    }

    public function destroyImmunization($id)
    {
        $schoolId = Auth::user()->school_id;
        $immunization = StudentImmunization::whereSchool($schoolId)->findOrFail($id);
        $immunization->delete();

        return redirect()->back()->with('success', 'Immunization record deleted.');
    }

    // ─── Incidents ─────────────────────────────────────────────

    public function storeIncident(Request $request, $userId)
    {
        $schoolId = Auth::user()->school_id;
        $student = User::where('school_id', $schoolId)->findOrFail($userId);
        $staff = Auth::user();

        $validated = $request->validate([
            'incident_date' => 'required|date',
            'description' => 'required|string|max:10000',
            'action_taken' => 'nullable|string|max:10000',
            'severity' => 'nullable|in:minor,moderate,serious',
        ]);

        $incident = StudentHealthIncident::create([
            'school_id' => $schoolId,
            'user_id' => $userId,
            'recorded_by' => $staff->id,
            ...$validated,
        ]);

        // Send WhatsApp notification to parent if severity is moderate or serious
        if (in_array($validated['severity'] ?? 'minor', ['moderate', 'serious'])) {
            try {
                $this->whatsapp->notifyHealthRecord($student, $incident);
            } catch (\Throwable $e) {
                \Log::warning('Health incident WhatsApp notification failed: ' . $e->getMessage());
            }
        }

        return redirect()->back()->with('success', 'Health incident recorded.');
    }

    public function destroyIncident($id)
    {
        $schoolId = Auth::user()->school_id;
        $incident = StudentHealthIncident::whereSchool($schoolId)->findOrFail($id);
        $incident->delete();

        return redirect()->back()->with('success', 'Incident record deleted.');
    }
}
