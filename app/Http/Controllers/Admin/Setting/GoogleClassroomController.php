<?php

namespace App\Http\Controllers\Admin\Setting;

use App\Http\Controllers\Controller;
use App\Services\Google\Classroom\GoogleClassroomSyncService;
use App\Services\Google\GoogleApiException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GoogleClassroomController extends Controller
{
    public function sync(Request $request, GoogleClassroomSyncService $classroom)
    {
        $user = Auth::user();
        if ($user === null || $user->school_id === null) {
            abort(404);
        }

        try {
            $plan = $classroom->sync($user);
        } catch (GoogleApiException $e) {
            $status = $e->status >= 400 && $e->status < 600 ? $e->status : 502;

            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], $status);
            }

            return redirect()
                ->route('admin.settings.integrations')
                ->with('failmessage', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json($plan);
        }

        $matched = collect($plan['classes'])->sum(fn ($class) => count($class['matched']));
        $review = count($plan['needsReview']) + collect($plan['classes'])->sum(fn ($class) => count($class['needsReview']));

        return redirect()
            ->route('admin.settings.integrations')
            ->with('successmessage', 'Classroom read complete. '.$matched.' students matched, '.$review.' need review. No students were created.');
    }
}
