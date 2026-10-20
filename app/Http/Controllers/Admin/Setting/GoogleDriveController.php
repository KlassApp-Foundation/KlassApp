<?php

namespace App\Http\Controllers\Admin\Setting;

use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Services\Google\Drive\GoogleDriveService;
use App\Services\Google\GoogleApiException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GoogleDriveController extends Controller
{
    public function file(Request $request, GoogleDriveService $drive)
    {
        $user = Auth::user();
        if ($user === null || $user->school_id === null) {
            abort(404);
        }

        $request->validate([
            'exam_id' => ['required', 'integer'],
            'learner_id' => ['required', 'integer'],
        ]);

        $exam = Exam::query()
            ->where('school_id', $user->school_id)
            ->whereKey((int) $request->input('exam_id'))
            ->firstOrFail();

        try {
            $filed = $drive->fileReportCard($user, $exam, (int) $request->input('learner_id'));
        } catch (GoogleApiException $e) {
            return $this->fail($e);
        }

        if ($request->expectsJson()) {
            return response()->json($filed);
        }

        return redirect()
            ->route('admin.settings.integrations')
            ->with('successmessage', 'Report card filed: '.$filed['webViewLink']);
    }

    private function fail(GoogleApiException $e)
    {
        $status = $e->status >= 400 && $e->status < 600 ? $e->status : 502;

        if (request()->expectsJson()) {
            return response()->json(['message' => $e->getMessage()], $status);
        }

        return redirect()
            ->route('admin.settings.integrations')
            ->with('failmessage', $e->getMessage());
    }
}
