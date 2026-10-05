<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Academics\Exam;
use App\Models\User;
use App\Services\MarksImport\MarksImportBlocked;
use App\Services\MarksImport\MarksImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Teacher and admin share one marks-import flow (page, preview, confirm, result). The template
 * download is not here: teachers use #986's teacher.exam.marks.template, admins have their own route.
 * The using controller supplies the layout and route names through marksImportContext().
 * All logic lives in MarksImportService; this only moves data between HTTP and the service.
 */
trait HandlesMarksImport
{
    /** @return array{layout:string,routePrefix:string,templateRoute:string,backRoute:string,backLabel:string} */
    abstract protected function marksImportContext(): array;

    /** Where the saved marks for this exam can be viewed in this context (teacher page / admin marks area). */
    abstract protected function marksViewUrl(Exam $exam): string;

    public function importPage(Exam $exam, MarksImportService $service)
    {
        $actor = $this->marksImportActor();
        $service->assertCanImport($actor, $exam);

        return view('marks-import.upload', $this->marksImportView($exam));
    }

    public function importPreview(Request $request, Exam $exam, MarksImportService $service)
    {
        $actor = $this->marksImportActor();
        $service->assertCanImport($actor, $exam);

        $request->validate([
            'file' => ['required', 'file', 'max:2048', 'extensions:xlsx,xls,csv,txt'],
        ], [
            'file.required' => 'Choose a spreadsheet file to upload.',
            'file.max' => 'The file is larger than 2 MB. Split it and import in parts.',
            'file.extensions' => 'Upload an .xlsx, .xls or .csv file.',
        ]);

        $file = $request->file('file');
        try {
            $plan = $service->preview(
                $exam, $actor, $file->getRealPath(), $file->getClientOriginalName(), hash_file('sha256', $file->getRealPath()),
            );
        } catch (\Throwable $e) {
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            report($e);

            return back()->withErrors(['file' => 'The file could not be read. Check that it is a valid spreadsheet or CSV and try again.']);
        }

        return view('marks-import.preview', $this->marksImportView($exam) + [
            'plan' => $plan,
            'token' => $service->stashPlan($plan, $actor),
            'reasons' => MarksImportService::REASONS,
        ]);
    }

    public function importConfirm(Request $request, Exam $exam, MarksImportService $service)
    {
        $actor = $this->marksImportActor();
        $service->assertCanImport($actor, $exam);
        $ctx = $this->marksImportContext();

        $data = $request->validate([
            'token' => ['required', 'string', 'size:40'],
            'overwrite' => ['nullable', 'boolean'],
            'correction_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $plan = $service->retrievePlan($data['token'], $actor, $exam);
        if (! $plan) {
            return redirect()->route($ctx['routePrefix'].'.page', $exam)
                ->withErrors(['file' => 'That preview has expired. Upload the file again to see a fresh preview.']);
        }

        try {
            $result = $service->commit(
                $plan, $actor, (bool) ($data['overwrite'] ?? false), $data['correction_reason'] ?? null,
                $request->header('X-Request-ID'),
            );
        } catch (MarksImportBlocked $e) {
            $field = $e->reasonCode === 'reason_required' ? 'correction_reason' : 'import';

            return back()->withErrors([$field => $e->getMessage()])->withInput();
        }

        $service->stashResult($result, $actor, $data['token']);

        return redirect()->route($ctx['routePrefix'].'.result', [$exam, 'token' => $data['token']]);
    }

    public function importResult(Request $request, Exam $exam, MarksImportService $service)
    {
        $actor = $this->marksImportActor();
        $service->assertCanImport($actor, $exam);

        $result = $service->retrieveResult((string) $request->query('token'), $actor, $exam);
        if (! $result) {
            return redirect()->route($this->marksImportContext()['routePrefix'].'.page', $exam);
        }

        return view('marks-import.result', $this->marksImportView($exam) + [
            'result' => $result,
            'reasons' => MarksImportService::REASONS,
        ]);
    }

    private function marksImportActor(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403, 'Not Authorized');

        return $user;
    }

    /** @return array<string,mixed> */
    private function marksImportView(Exam $exam): array
    {
        $exam->loadMissing('subject', 'section', 'standard', 'examType', 'academicTerm');

        return ['exam' => $exam, 'marksViewUrl' => $this->marksViewUrl($exam)] + $this->marksImportContext();
    }
}
