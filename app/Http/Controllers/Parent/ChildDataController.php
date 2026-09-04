<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Services\Parent\ParentPortalService;
use Illuminate\Contracts\View\View;

class ChildDataController extends Controller
{
    public function __construct(private ParentPortalService $portal) {}

    public function fees(int $student): View
    {
        return $this->respond('fees', 'parent.child-fees', $this->portal->feeBalance(auth()->user(), null, $student));
    }

    public function grades(int $student): View
    {
        return $this->respond('grades', 'parent.child-grades', $this->portal->grades(auth()->user(), null, $student));
    }

    public function attendance(int $student): View
    {
        return $this->respond('attendance', 'parent.child-attendance', $this->portal->attendance(auth()->user(), null, $student));
    }

    /**
     * @param  array{success: bool, message?: string, denied?: bool, data?: array<string, mixed>}  $result
     */
    private function respond(string $panelKey, string $view, array $result): View
    {
        if (! $result['success']) {
            if ($result['denied'] ?? false) {
                abort(403, $result['message'] ?? 'Access denied.');
            }

            abort(422, $result['message'] ?? 'Unable to load data.');
        }

        $data = $result['data'] ?? [];

        return view($view, [
            'panelKey' => $panelKey,
            'childName' => $data['student_name'] ?? 'Child',
            'studentId' => $data['student_id'] ?? null,
            'fees' => $panelKey === 'fees' ? $data : null,
            'grades' => $panelKey === 'grades' ? $data : null,
            'attendance' => $panelKey === 'attendance' ? $data : null,
            'message' => $result['message'] ?? null,
        ]);
    }
}
