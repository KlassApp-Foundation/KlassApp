<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StudentProfile\StudentAttendanceRecorder;
use App\Services\StudentProfile\StudentMarksDraftService;
use App\Services\StudentProfile\StudentPaymentRecorder;
use App\Services\StudentProfile\StudentPhotoService;
use App\Services\StudentProfileService;
use App\Support\StudentProfileTabs;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class StudentProfileController extends Controller
{
    public function show(Request $request, int $student, StudentProfileService $profiles, StudentMarksDraftService $drafts)
    {
        $schoolId = (int) Auth::user()->school_id;
        $record = $profiles->find($schoolId, $student);
        $tab = StudentProfileTabs::normalize((string) $request->query('tab', 'overview'));

        $page = $profiles->page($schoolId, $record);
        $sittings = $drafts->sittings($record);
        $sittingSubjects = [];
        foreach ($sittings as $sitting) {
            $sittingSubjects[$sitting['key']] = $drafts->subjects($record, $sitting['exam_type_id'], $sitting['term_id']);
        }

        return view('admin.students.profile', [
            'page' => $page,
            'tab' => $tab,
            'tabs' => StudentProfileTabs::labels(),
            'sittings' => $sittings,
            'sittingSubjects' => $sittingSubjects,
            'paymentMethods' => StudentPaymentRecorder::METHODS,
            'requestId' => (string) Str::uuid(),
        ]);
    }

    public function deactivate(int $student, StudentProfileService $profiles)
    {
        $schoolId = (int) Auth::user()->school_id;
        $record = $profiles->deactivate($schoolId, $student);

        return redirect('/admin/students/'.$record->id);
    }

    public function recordPayment(Request $request, int $student, StudentProfileService $profiles, StudentPaymentRecorder $payments)
    {
        $record = $profiles->find((int) Auth::user()->school_id, $student);
        $payment = $payments->record(Auth::user(), $record, $request->only(['amount', 'payment_method', 'paid_on', 'reference']), (string) $request->input('request_id'));

        return redirect('/admin/students/'.$record->id.'?tab=fees')
            ->with('successmessage', 'Payment of '.$profiles->money((float) $payment->amount, app(\App\Services\DashboardV2DataService::class)->currencyFor((int) $record->school_id)).' recorded.');
    }

    /** "Mark as paid": the same recorder as "Record a payment", for the whole outstanding term balance. */
    public function markPaid(Request $request, int $student, StudentProfileService $profiles, StudentPaymentRecorder $payments)
    {
        $schoolId = (int) Auth::user()->school_id;
        $record = $profiles->find($schoolId, $student);
        $owed = $profiles->outstanding($schoolId, $record);
        if ($owed <= 0) {
            return redirect('/admin/students/'.$record->id.'?tab=fees')->with('failmessage', 'Nothing is owed for this term.');
        }

        $payment = $payments->record(Auth::user(), $record, [
            'amount' => $owed,
            'payment_method' => $request->input('payment_method'),
            'paid_on' => now()->toDateString(),
            'reference' => null,
        ], (string) $request->input('request_id'));

        return redirect('/admin/students/'.$record->id.'?tab=fees')
            ->with('successmessage', 'Marked as paid: '.$profiles->money((float) $payment->amount, app(\App\Services\DashboardV2DataService::class)->currencyFor($schoolId)).' recorded.');
    }

    public function uploadPhoto(Request $request, int $student, StudentProfileService $profiles, StudentPhotoService $photos)
    {
        $record = $profiles->find((int) Auth::user()->school_id, $student);
        $photos->set(Auth::user(), $record, $request->file('photo'));

        return redirect('/admin/students/'.$record->id)->with('successmessage', 'Photo saved.');
    }

    public function removePhoto(int $student, StudentProfileService $profiles, StudentPhotoService $photos)
    {
        $record = $profiles->find((int) Auth::user()->school_id, $student);
        $photos->remove(Auth::user(), $record);

        return redirect('/admin/students/'.$record->id)->with('successmessage', 'Photo removed.');
    }

    public function addMarks(Request $request, int $student, StudentProfileService $profiles, StudentMarksDraftService $drafts)
    {
        $record = $profiles->find((int) Auth::user()->school_id, $student);
        [$typeId, $termId] = array_pad(array_map('intval', explode(':', (string) $request->input('sitting'))), 2, 0);
        $result = $drafts->save(Auth::user(), $record, $typeId, $termId, (array) $request->input('marks', []));

        $message = $result['saved'].' '.($result['saved'] === 1 ? 'mark' : 'marks').' saved as draft. They still go through Submit, Approve and Release.';
        $redirect = redirect('/admin/students/'.$record->id.'?tab=performance')->with('successmessage', $message);
        if ($result['skipped'] !== []) {
            $redirect->with('failmessage', 'Not saved: '.collect($result['skipped'])->map(fn ($s) => $s['subject'].' ('.$s['reason'].')')->implode('; '));
        }

        return $redirect;
    }

    public function recordAttendance(Request $request, int $student, StudentProfileService $profiles, StudentAttendanceRecorder $attendance)
    {
        $record = $profiles->find((int) Auth::user()->school_id, $student);
        $row = $attendance->record(Auth::user(), $record, $request->only(['date', 'status', 'comment']));

        return redirect('/admin/students/'.$record->id.'?tab=attendance')
            ->with('successmessage', 'Attendance saved for '.$row->date->format('j M Y').'.');
    }

    /** The current progress report: approved marks that are not released yet, labelled as such. */
    public function progressReport(int $student, StudentProfileService $profiles)
    {
        $schoolId = (int) Auth::user()->school_id;
        $record = $profiles->find($schoolId, $student);
        $page = $profiles->page($schoolId, $record);
        abort_unless($page['performance']['progress'] !== null, 404);

        $term = app(\App\Services\StudentProfile\StudentEnrollment::class)->term($schoolId, app(\App\Services\StudentProfile\StudentEnrollment::class)->year($schoolId));
        $rows = $profiles->approvedUnreleasedMarks($schoolId, $record, (int) $term->id)
            ->sortBy(fn ($m) => (string) $m->exam?->subject?->name)
            ->values();

        $pdf = Pdf::loadView('admin.students.progress-report', [
            'school' => $record->school,
            'page' => $page,
            'term' => $term,
            'rows' => $rows,
            'average' => $page['performance']['progress']['average'],
        ])->setPaper('a4', 'portrait');

        return $pdf->download(Str::slug($page['name'] ?: 'student').'-progress-report.pdf');
    }
}
