<?php

namespace App\Http\Controllers\Admin\Setting;

use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Services\Google\GoogleApiException;
use App\Services\Google\Sheets\GoogleSheetsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GoogleSheetsController extends Controller
{
    public function export(Request $request, GoogleSheetsService $sheets)
    {
        $user = Auth::user();
        $exam = $this->exam($user->school_id, (int) $request->input('exam_id'));

        try {
            $created = $sheets->export($user, $exam);
        } catch (GoogleApiException $e) {
            return $this->fail($e);
        }

        if ($request->expectsJson()) {
            return response()->json($created);
        }

        return redirect()
            ->route('admin.settings.integrations')
            ->with('successmessage', 'Mark sheet created: '.$created['spreadsheetUrl']);
    }

    public function import(Request $request, GoogleSheetsService $sheets)
    {
        $user = Auth::user();
        $exam = $this->exam($user->school_id, (int) $request->input('exam_id'));
        $request->validate([
            'spreadsheet_id' => ['required', 'string', 'max:128'],
            'overwrite' => ['sometimes', 'boolean'],
        ]);

        try {
            $outcome = $sheets->import(
                $user,
                $exam,
                (string) $request->input('spreadsheet_id'),
                $request->boolean('overwrite'),
            );
        } catch (GoogleApiException $e) {
            return $this->fail($e);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'committed' => $outcome['committed'],
                'blockers' => $outcome['plan']->blockers,
                'counts' => $outcome['plan']->counts(),
            ]);
        }

        if (! $outcome['committed']) {
            return redirect()
                ->route('admin.settings.integrations')
                ->with('failmessage', 'Nothing was saved. '.implode(' ', $outcome['plan']->blockers));
        }

        $counts = $outcome['plan']->counts();

        return redirect()
            ->route('admin.settings.integrations')
            ->with('successmessage', 'Marks imported. '.$counts['new'].' new, '.$counts['update'].' updated, '.$counts['skipped'].' skipped.');
    }

    private function exam(?int $schoolId, int $examId): Exam
    {
        if ($schoolId === null) {
            abort(404);
        }

        return Exam::query()->where('school_id', $schoolId)->whereKey($examId)->firstOrFail();
    }

    private function fail(GoogleApiException $e)
    {
        if (request()->expectsJson()) {
            return response()->json(['message' => $e->getMessage()], $e->status >= 400 && $e->status < 600 ? $e->status : 502);
        }

        return redirect()
            ->route('admin.settings.integrations')
            ->with('failmessage', $e->getMessage());
    }
}
