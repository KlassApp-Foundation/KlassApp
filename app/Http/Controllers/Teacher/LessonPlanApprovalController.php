<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\LessonPlan;
use App\Models\Approval;
use App\Helpers\SiteHelper;
use App\States\Approval\Approved;
use App\States\Approval\Pending;
use App\States\Approval\Rejected;
use Illuminate\Http\Request;
use App\Traits\LogActivity;
use App\Traits\Common;
use Exception;
use Log;

class LessonPlanApprovalController extends Controller
{
    use LogActivity;
    use Common;

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function approve(Request $request,$id)
    {
        \DB::beginTransaction();
        try
        {
            $schoolId = Auth::user()->school_id;
            $lessonplan = LessonPlan::where('id',$id)
                ->whereHas('teacherlink', fn($q) => $q->where('school_id', $schoolId))
                ->firstOrFail();
            $lessonplan->status = 'approved';
            $lessonplan->save();

            // Create or update unified Approval record
            $approval = $lessonplan->pendingApproval ?? $lessonplan->approvals()->create([
                'state'        => Pending::class,
                'requested_by' => $lessonplan->user_id ?? Auth::id(),
            ]);
            $approval->state->transitionTo(Approved::class);
            $approval->approved_by = Auth::id();
            $approval->comments = $request->comments;
            $approval->resolved_at = now();
            $approval->save();

            $message = trans('messages.approve_success_msg',['module' => 'Lesson Plan']);

            $ip = $this->getRequestIP();
            $this->doActivityLog(
                $approval,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT']],
                LOGNAME_APPROVE_LESSON_PLAN,
                $message
            );

            \DB::commit();
            return ['success' => $message];
        }
        catch(Exception $e)
        {
            \DB::rollBack();
            Log::error('LessonPlanApprovalController@approve failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to approve lesson plan.'], 422);
        }
    }

    public function reject(Request $request, $id)
    {
        \DB::beginTransaction();
        try
        {
            $schoolId = Auth::user()->school_id;
            $lessonplan = LessonPlan::where('id',$id)
                ->whereHas('teacherlink', fn($q) => $q->where('school_id', $schoolId))
                ->firstOrFail();
            $lessonplan->status = 'rejected';
            $lessonplan->save();

            // Create or update unified Approval record
            $approval = $lessonplan->pendingApproval ?? $lessonplan->approvals()->create([
                'state'        => Pending::class,
                'requested_by' => $lessonplan->user_id ?? Auth::id(),
            ]);
            $approval->state->transitionTo(Rejected::class);
            $approval->approved_by = Auth::id();
            $approval->comments = $request->comments;
            $approval->resolved_at = now();
            $approval->save();

            $message = trans('messages.reject_success_msg',['module' => 'Lesson Plan']);

            $ip = $this->getRequestIP();
            $this->doActivityLog(
                $approval,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT']],
                LOGNAME_REJECT_LESSON_PLAN,
                $message
            );

            \DB::commit();
            return ['success' => $message];
        }
        catch(Exception $e)
        {
            \DB::rollBack();
            Log::error('LessonPlanApprovalController@reject failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to reject lesson plan.'], 422);
        }
    }
}
