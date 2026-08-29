<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Services\Parent\ParentPortalService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private ParentPortalService $portal) {}

    public function index(Request $request)
    {
        $parent = auth()->user();
        $listed = $this->portal->listChildren($parent);
        $children = $listed['children'] ?? [];
        $grouped = $listed['grouped_by_school'] ?? $this->portal->groupChildrenBySchool($children);

        $requestedChildId = $request->query('child') !== null
            ? (int) $request->query('child')
            : null;

        $selected = $this->portal->selectChild($children, $requestedChildId);

        $panel = null;
        if ($selected !== null) {
            $panel = $this->portal->childDashboard($parent, (int) $selected['student_id']);
        }

        return view('parent.dashboard', [
            'linkedChildCount' => $listed['count'] ?? 0,
            'children' => $children,
            'groupedBySchool' => $grouped,
            'selectedChild' => $selected,
            'panel' => $panel,
            'emptyMessage' => ($listed['success'] ?? false) ? null : ($listed['message'] ?? 'No children linked to your account.'),
        ]);
    }
}
