<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transportation;
use App\Helpers\SiteHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TransportController extends Controller
{
    /**
     * List all transport routes for the school.
     */
    public function index()
    {
        $schoolId = Auth::user()->school_id;
        $routes = Transportation::whereSchool($schoolId)
            ->orderBy('name')
            ->paginate(15);

        return view('admin.transport.index', compact('routes'));
    }

    /**
     * Show create form.
     */
    public function create()
    {
        return view('admin.transport.create');
    }

    /**
     * Store a new transport route.
     */
    public function store(Request $request)
    {
        $schoolId = Auth::user()->school_id;
        $academicYear = SiteHelper::getAcademicYear($schoolId);

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'vehicle_number'=> 'required|string|max:255',
            'start_time'    => 'required|date_format:H:i',
            'end_time'      => 'required|date_format:H:i|after:start_time',
            'stops'         => 'required|string|max:5000',
            'status'        => 'nullable|boolean',
        ]);

        try {
            Transportation::create([
                'school_id'        => $schoolId,
                'academic_year_id' => $academicYear->id,
                'name'             => $validated['name'],
                'vehicle_number'   => $validated['vehicle_number'],
                'start_time'       => $validated['start_time'],
                'end_time'         => $validated['end_time'],
                'stops'            => $validated['stops'],
                'status'           => $validated['status'] ?? true,
            ]);

            return redirect()->route('admin.transport')
                ->with('successmessage', 'Transport route created.');
        } catch (\Exception $e) {
            Log::error('TransportController@store failed: ' . $e->getMessage());
            return redirect()->back()->withInput()
                ->with('errormessage', 'Failed to create transport route.');
        }
    }

    /**
     * Show edit form.
     */
    public function edit($id)
    {
        $schoolId = Auth::user()->school_id;
        $route = Transportation::whereSchool($schoolId)->findOrFail($id);

        return view('admin.transport.edit', compact('route'));
    }

    /**
     * Update a transport route.
     */
    public function update(Request $request, $id)
    {
        $schoolId = Auth::user()->school_id;
        $route = Transportation::whereSchool($schoolId)->findOrFail($id);

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'vehicle_number'=> 'required|string|max:255',
            'start_time'    => 'required|date_format:H:i',
            'end_time'      => 'required|date_format:H:i|after:start_time',
            'stops'         => 'required|string|max:5000',
            'status'        => 'nullable|boolean',
        ]);

        try {
            $route->update([
                'name'          => $validated['name'],
                'vehicle_number'=> $validated['vehicle_number'],
                'start_time'    => $validated['start_time'],
                'end_time'      => $validated['end_time'],
                'stops'         => $validated['stops'],
                'status'        => $validated['status'] ?? true,
            ]);

            return redirect()->route('admin.transport')
                ->with('successmessage', 'Transport route updated.');
        } catch (\Exception $e) {
            Log::error('TransportController@update failed: ' . $e->getMessage());
            return redirect()->back()->withInput()
                ->with('errormessage', 'Failed to update transport route.');
        }
    }

    /**
     * Delete a transport route.
     */
    public function destroy($id)
    {
        $schoolId = Auth::user()->school_id;
        $route = Transportation::whereSchool($schoolId)->findOrFail($id);

        try {
            $route->delete();
            return redirect()->route('admin.transport')
                ->with('successmessage', 'Transport route removed.');
        } catch (\Exception $e) {
            Log::error('TransportController@destroy failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('errormessage', 'Failed to remove transport route.');
        }
    }
}
