<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Student;

use App\Http\Resources\Student\Task as TaskResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Parent\ParentPortalService;
use App\Support\DisplayName;
use App\Models\Task;

class DashboardController extends Controller
{
    public function __construct(private ParentPortalService $portal) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $student = Auth::user();
        $schoolId = (int) $student->school_id;
        $bundle = $this->portal->bundle($student, $schoolId);
        $loading = false;
        $state = (string) $request->query('state', '');
        if (in_array($state, ['loading', 'empty', 'error'], true) && app()->environment(['local', 'testing'])) {
            if ($state === 'loading') {
                $loading = true;
            } elseif ($state === 'empty') {
                $bundle['grades']['exam_groups'] = [];
                $bundle['grades']['chart'] = ['labels' => [], 'scores' => []];
                $bundle['headline'] = ['average' => null, 'label' => '', 'delta' => null, 'since' => null, 'has_unreleased' => false];
            } else {
                $bundle['errors']['fees'] = true;
            }
        }

        $name = DisplayName::title($student->displayName ?: $student->name);
        $link = $student->studentAcademicLatest?->standardLink;
        $className = ($link && (int) $link->school_id === $schoolId) ? (string) ($link->StandardSection ?: '') : '';

        return view('student.dashboard.dashboard', [
            'childName' => $name,
            'className' => $className,
            'bundle' => $bundle,
            'loading' => $loading,
            'links' => [
                'dashboard' => route('student.dashboard'),
                'marks' => route('student.marks'),
                'attendance' => route('student.attendance'),
                'fees' => route('student.fees'),
                'report' => route('student.report'),
            ],
        ]);
    }

    public function list(Request $request,$task_flag)
    {
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
        $tasks = Task::where([['school_id',Auth::user()->school_id],['user_id',Auth::id()],['task_status',0]])->ByType('to_me',Auth::id())->get()->groupBy('Flag');

        foreach ($tasks as $key => $value)
        {
            $tasks[$key] = count($value);
        }

        return $tasks;
    }
}
