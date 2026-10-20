<?php

namespace App\Http\Controllers\Admin\Setting;

use App\Http\Controllers\Controller;
use App\Services\Google\Calendar\GoogleCalendarService;
use App\Services\Google\GoogleApiException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GoogleCalendarController extends Controller
{
    public function publish(Request $request, GoogleCalendarService $calendar)
    {
        $user = Auth::user();
        if ($user === null || $user->school_id === null) {
            abort(404);
        }

        try {
            $published = $calendar->publish($user);
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
            return response()->json($published);
        }

        return redirect()
            ->route('admin.settings.integrations')
            ->with('successmessage', 'Term dates and school events are on the KlassApp calendar. '.$published['terms'].' terms, '.$published['events'].' events.');
    }
}
