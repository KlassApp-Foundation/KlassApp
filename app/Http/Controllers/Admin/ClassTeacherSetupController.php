<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\StaffLinkRequest;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\TeacherSetupToken;
use App\Models\User;
use App\Services\TeacherSetup\TeacherSetupLinkService;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Admin view for class-teacher self-setup: the staff link and QR, requests
 * from people who were not on the teacher list, and per-class progress.
 */
class ClassTeacherSetupController extends Controller
{
    public function __construct(private TeacherSetupLinkService $links) {}

    public function index(): View
    {
        $school = School::findOrFail(Auth::user()->school_id);
        $year = $this->year($school);

        // The token is only stored hashed, so a new link is shown once, right
        // after it is created.
        $staffUrl = session('staff_link_url');
        $qrSvg = null;
        if ($staffUrl) {
            $writer = new Writer(new ImageRenderer(new RendererStyle(220, 2), new SvgImageBackEnd));
            $qrSvg = preg_replace('/^<\?xml[^>]*>\s*/', '', $writer->writeString($staffUrl));
        }

        $requests = StaffLinkRequest::query()
            ->where('school_id', $school->id)
            ->where('status', StaffLinkRequest::STATUS_PENDING)
            ->orderBy('id')
            ->get();

        $classes = [];
        if ($year) {
            $links = StandardLink::query()->with('section')
                ->where('school_id', $school->id)
                ->where('academic_year_id', $year->id)
                ->orderBy('id')->get();

            foreach ($links as $link) {
                $students = StudentAcademic::query()->where('school_id', $school->id)
                    ->where('standardLink_id', $link->id)->count();
                $subjects = Subject::query()->where('school_id', $school->id)
                    ->where('standard_id', $link->standard_id)->count();
                $withTeacher = Teacherlink::query()->where('school_id', $school->id)
                    ->where('standardLink_id', $link->id)
                    ->distinct('subject_id')->count('subject_id');

                $classes[] = [
                    'name' => $link->section?->name ?? 'Class '.$link->id,
                    'students' => $students,
                    'subjects' => $subjects,
                    'subjects_with_teacher' => $withTeacher,
                    'class_teacher' => $link->class_teacher_id
                        ? (User::find($link->class_teacher_id)?->name ?? 'teacher removed')
                        : null,
                ];
            }
        }

        return view('admin.class-setup.index', [
            'school' => $school,
            'staffUrl' => $staffUrl,
            'qrSvg' => $qrSvg,
            'requests' => $requests,
            'classes' => $classes,
            'issued' => session('teacher_setup_url'),
        ]);
    }

    public function createStaffLink(): RedirectResponse
    {
        $school = School::findOrFail(Auth::user()->school_id);
        $token = $this->links->issueStaffLink($school, Auth::user());

        return back()->with('staff_link_url', $this->links->staffUrl($token));
    }

    public function approve(StaffLinkRequest $request): RedirectResponse
    {
        $school = School::findOrFail(Auth::user()->school_id);
        abort_unless((int) $request->school_id === (int) $school->id, 403);

        if ($request->status !== StaffLinkRequest::STATUS_PENDING) {
            return back();
        }

        $email = $request->email ?: 'teacher-'.Str::lower(Str::random(8)).'@'.$school->slug.'.local';
        $teacher = User::query()->where('school_id', $school->id)->whereRaw('lower(email) = ?', [mb_strtolower($email)])->first();

        if (! $teacher) {
            $teacher = User::create([
                'school_id' => $school->id,
                'usergroup_id' => 5,
                'name' => $request->name ?: 'Teacher',
                'email' => $email,
                'mobile_no' => $request->phone,
                'password' => Hash::make(Str::random(24)),
                'status' => 'active',
                'is_reset' => 1,
            ]);
        }

        $request->forceFill([
            'status' => 'approved',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ])->save();

        $token = $this->links->issueFor($teacher, Auth::user());

        return back()->with('teacher_setup_url', $this->links->personalUrl($token));
    }

    public function reject(StaffLinkRequest $request): RedirectResponse
    {
        $school = School::findOrFail(Auth::user()->school_id);
        abort_unless((int) $request->school_id === (int) $school->id, 403);

        $request->forceFill([
            'status' => 'rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ])->save();

        return back();
    }

    private function year(School $school): ?AcademicYear
    {
        return AcademicYear::query()->where('school_id', $school->id)->where('status', 1)->first()
            ?? AcademicYear::query()->where('school_id', $school->id)->first();
    }
}
