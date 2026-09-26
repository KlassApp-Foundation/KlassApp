<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Services\Parent\ParentPortalService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ChildDataController extends Controller
{
    public function __construct(private ParentPortalService $portal) {}

    public function fees(int $student): View|RedirectResponse
    {
        return $this->respond('fees', 'parent.child-fees', $this->portal->feeBalance(auth()->user(), null, $student));
    }

    public function grades(int $student): View|RedirectResponse
    {
        return $this->respond('grades', 'parent.child-grades', $this->portal->grades(auth()->user(), null, $student));
    }

    public function attendance(int $student): View|RedirectResponse
    {
        return $this->respond('attendance', 'parent.child-attendance', $this->portal->attendance(auth()->user(), null, $student));
    }

    /**
     * @param  array{success: bool, message?: string, denied?: bool, data?: array<string, mixed>}  $result
     */
    private function respond(string $panelKey, string $view, array $result): View|RedirectResponse
    {
        if (! $result['success']) {
            if ($result['denied'] ?? false) {
                abort(403, $result['message'] ?? 'Access denied.');
            }

            // Not denied, just nothing to show (the zero-children case): send the parent
            // to the Children page, which carries the helpful empty state with the
            // contact-the-school guidance — never a stock "Something is broken" error page.
            return redirect()->route('parent.children');
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
