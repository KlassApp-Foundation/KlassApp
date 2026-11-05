<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Services\Parent\ParentPortalService;
use App\Support\DisplayName;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ChildDataController extends Controller
{
    public function __construct(private ParentPortalService $portal) {}

    public function fees(Request $request, int $student): View|RedirectResponse
    {
        return $this->page($request, $student, 'fees', 'parent.child-fees');
    }

    public function grades(Request $request, int $student): View|RedirectResponse
    {
        return $this->page($request, $student, 'marks', 'parent.child-grades');
    }

    public function attendance(Request $request, int $student): View|RedirectResponse
    {
        return $this->page($request, $student, 'attendance', 'parent.child-attendance');
    }

    public function report(int $student): Response|RedirectResponse
    {
        $parent = auth()->user();
        $resolved = $this->portal->resolveChild($parent, null, $student);
        if (! ($resolved['ok'] ?? false)) {
            abort(($resolved['denied'] ?? false) ? 403 : 404);
        }

        $schoolId = $this->portal->schoolIdForLinkedChild($parent, $resolved['student']);
        $result = $this->portal->releasedReportPdf($resolved['student'], (int) $schoolId);
        if (! ($result['ok'] ?? false)) {
            return back()->with('failmessage', $result['message'] ?? 'No report card has been released yet.');
        }

        return response($result['pdf'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$result['filename'].'"',
        ]);
    }

    private function page(Request $request, int $student, string $panel, string $view): View|RedirectResponse
    {
        $parent = auth()->user();
        $resolved = $this->portal->resolveChild($parent, null, $student);
        if (! ($resolved['ok'] ?? false)) {
            if ($resolved['denied'] ?? false) {
                abort(403, $resolved['message'] ?? 'Access denied.');
            }

            return redirect()->route('parent.children');
        }

        $childUser = $resolved['student'];
        $schoolId = (int) $this->portal->schoolIdForLinkedChild($parent, $childUser);
        $listed = $this->portal->listChildren($parent);
        $children = array_map(function (array $child) {
            $name = DisplayName::title((string) ($child['name'] ?? ''));
            $child['name'] = $name;
            $child['initials'] = DisplayName::initials($name);

            return $child;
        }, $listed['children'] ?? []);
        $selected = null;
        foreach ($children as $child) {
            if ((int) $child['student_id'] === $student) {
                $selected = $child;
                break;
            }
        }

        $year = $panel === 'marks' ? $request->query('year') : null;
        $bundle = $this->portal->bundle($childUser, $schoolId, $year);
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

        $name = DisplayName::title($childUser->displayName ?: $childUser->name);

        return view($view, [
            'panel' => $panel,
            'childName' => $name,
            'studentId' => $childUser->id,
            'children' => $children,
            'selectedChild' => $selected,
            'bundle' => $bundle,
            'loading' => $loading,
            'grades' => $bundle['grades'],
            'fees' => $bundle['fees'],
            'attendance' => $bundle['attendance'],
            'message' => ($bundle['grades']['exam_groups'] ?? []) === [] ? 'No results published yet for '.$name.'.' : null,
            'links' => [
                'dashboard' => route('parent.dashboard', ['child' => $childUser->id]),
                'marks' => route('parent.children.grades', $childUser->id),
                'attendance' => route('parent.children.attendance', $childUser->id),
                'fees' => route('parent.children.fees', $childUser->id),
                'report' => route('parent.children.report', $childUser->id),
            ],
        ]);
    }
}
