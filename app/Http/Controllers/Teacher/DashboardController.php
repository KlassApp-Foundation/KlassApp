<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Teacher;

use App\Http\Resources\Teacher\Task as TaskResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\Dashboard;
use App\Models\Task;
use App\Models\Homework;
use App\Models\Assignment;
use App\Models\TeacherLeaveApplication;

class DashboardController extends Controller 
{
    use Dashboard;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $teacher_id = Auth::id();
        $school_id  = Auth::user()->school_id;

        $dashboard = $this->teacherDashboard( $school_id, $teacher_id );

        $dashboard['greeting'] = $this->dashboardGreeting(Auth::user());

        $dashboard['pendingApprovals'] = $this->getPendingApprovals($school_id, $teacher_id);

        $dashboard['upcomingDeadlines'] = $this->getUpcomingDeadlines($school_id, $teacher_id);

        return view( '/teacher/dashboard/dashboard',['dashboard' => $dashboard]);
    }

    private function dashboardGreeting(\App\Models\User $user): array
    {
        $hour = (int) now()->timezone(config('app.timezone'))->format('G');
        if ($hour < 12) {
            $phrase = 'Good morning';
        } elseif ($hour < 17) {
            $phrase = 'Good afternoon';
        } else {
            $phrase = 'Good evening';
        }

        $profile = $user->userprofile;
        $name = trim((string) ($profile->firstname ?? ''));
        if ($name === '') {
            $name = explode(' ', (string) $user->name)[0] ?: 'Teacher';
        }

        return [
            'phrase' => $phrase,
            'name' => $name,
        ];
    }

    private function getPendingApprovals(int $school_id, int $teacher_id): array
    {
        $standardLinks = \App\Models\StandardLink::where('school_id', $school_id)
            ->where('class_teacher_id', $teacher_id)
            ->pluck('id')
            ->toArray();

        $leaveApprovals = TeacherLeaveApplication::where('school_id', $school_id)
            ->where('status', 'pending')
            ->whereHas('teacher', function ($q) use ($teacher_id) {
                $q->where('id', $teacher_id);
            })
            ->count();

        $homeworkApprovals = \App\Models\HomeworkApproval::where('status', 'pending')
            ->whereHas('homework', function ($q) use ($school_id, $standardLinks) {
                $q->where('school_id', $school_id)
                  ->whereIn('standardLink_id', $standardLinks);
            })
            ->count();

        $assignmentApprovals = \App\Models\AssignmentApproval::where('status', 'pending')
            ->whereHas('assignment', function ($q) use ($school_id, $standardLinks) {
                $q->where('school_id', $school_id)
                  ->whereIn('standardLink_id', $standardLinks);
            })
            ->count();

        return [
            'leave' => $leaveApprovals,
            'homework' => $homeworkApprovals,
            'assignment' => $assignmentApprovals,
            'total' => $leaveApprovals + $homeworkApprovals + $assignmentApprovals,
        ];
    }

    private function getUpcomingDeadlines(int $school_id, int $teacher_id): array
    {
        $standardLinks = \App\Models\StandardLink::where('school_id', $school_id)
            ->where('class_teacher_id', $teacher_id)
            ->pluck('id')
            ->toArray();

        $assignmentDeadlines = Assignment::where('school_id', $school_id)
            ->whereIn('standardLink_id', $standardLinks)
            ->where('submission_date', '>=', now()->toDateString())
            ->where('submission_date', '<=', now()->addDays(7)->toDateString())
            ->orderBy('submission_date')
            ->get()
            ->map(function ($a) {
                return [
                    'type' => 'Assignment',
                    'title' => $a->title,
                    'date' => $a->submission_date,
                    'subject' => $a->subject->name ?? '',
                ];
            });

        $homeworkDeadlines = Homework::where('school_id', $school_id)
            ->whereIn('standardLink_id', $standardLinks)
            ->where('date', '>=', now()->toDateString())
            ->where('date', '<=', now()->addDays(7)->toDateString())
            ->orderBy('date')
            ->get()
            ->map(function ($h) {
                return [
                    'type' => 'Homework',
                    'title' => $h->description ? \Illuminate\Support\Str::limit($h->description, 50) : 'Homework',
                    'date' => $h->date,
                    'subject' => $h->subject->name ?? '',
                ];
            });

        // Both deadline maps are plain arrays; base collections merge them safely.
        // Eloquent\Collection::merge() would call getKey() on each item and fatal.
        return collect($assignmentDeadlines)->merge(collect($homeworkDeadlines))->sortBy('date')->values()->all();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function timetable(Request $request)
    {
        //
        $teacher_id = Auth::id();
        $school_id  = Auth::user()->school_id;

        $dashboard = $this->teacherDashboard( $school_id, $teacher_id );

        return $dashboard['timetable'];
    }

    public function timetablePage(Request $request)
    {
        $teacher_id = Auth::id();
        $school_id = Auth::user()->school_id;

        $dashboard = $this->teacherDashboard($school_id, $teacher_id);

        return view('/teacher/timetable/index', ['dashboard' => $dashboard]);
    }

    public function list(Request $request,$task_flag)
    {
        //
        $tasks = Task::where([['school_id',Auth::user()->school_id],['task_status',0],['task_flag',$task_flag]])->ByType('to_me',Auth::id());

        if($request->q != null)
        {
            $tasks = $tasks->where('title','LIKE','%'.$request->q.'%');
        }
        $tasks = $tasks->get();

        $tasks = TaskResource::collection($tasks);

        return $tasks;    
    }

    public function listCount()
    {
        //
        $tasks = Task::where([['school_id',Auth::user()->school_id],['user_id',Auth::id()],['task_status',0]])->ByType('to_me',Auth::id())->get()->groupBy('Flag');

        foreach ($tasks as $key => $value) 
        {
            $tasks[$key] = count($value);
        }

        return $tasks;    
    }
}