<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ToshiActivityController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $schoolId = $user->school_id;

        $query = ActivityLog::where('log_name', 'toshi')
            ->where('school_id', $schoolId)
            ->orderBy('id', 'desc');

        if ($tool = $request->get('tool')) {
            $query->where('properties', 'like', '%"tool":"' . $tool . '"%');
        }

        if ($status = $request->get('status')) {
            $query->where('properties', 'like', '%"status":"' . $status . '"%');
        }

        $logs = $query->paginate(25);

        $tools = ActivityLog::where('log_name', 'toshi')
            ->where('school_id', $schoolId)
            ->get()
            ->map(fn ($log) => $log->properties['tool'] ?? null)
            ->unique()
            ->filter()
            ->values();

        return view('admin.toshi_activity.show', [
            'logs' => $logs,
            'tools' => $tools,
            'currentTool' => $request->get('tool', ''),
            'currentStatus' => $request->get('status', ''),
        ]);
    }
}
