<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Services\Parent\ParentPortalService;
use App\Support\DisplayName;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private ParentPortalService $portal) {}

    public function index(Request $request)
    {
        $parent = auth()->user();
        $listed = $this->portal->listChildren($parent);
        $children = $this->children($listed['children'] ?? []);
        $requestedChildId = $request->query('child') !== null ? (int) $request->query('child') : null;
        $selectedRow = $this->portal->selectChild($listed['children'] ?? [], $requestedChildId);
        $selected = $selectedRow ? $this->presentChild($selectedRow) : null;

        $bundle = null;
        $loading = false;
        if ($selected !== null) {
            $resolved = $this->portal->resolveChild($parent, null, (int) $selected['student_id']);
            if ($resolved['ok'] ?? false) {
                $bundle = $this->portal->bundle($resolved['student'], (int) $selected['school_id']);
                [$bundle, $loading] = $this->preview($request, $bundle);
            }
        }

        return view('parent.dashboard', [
            'linkedChildCount' => $listed['count'] ?? 0,
            'children' => $children,
            'selectedChild' => $selected,
            'bundle' => $bundle,
            'loading' => $loading,
            'emptyMessage' => ($listed['success'] ?? false) ? null : ($listed['message'] ?? 'No children are linked to your account. Contact the school office to link a student.'),
            'whatsappPhone' => $this->maskPhone($parent->mobile_no ?? null),
            'links' => $selected ? $this->links((int) $selected['student_id']) : [],
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $children
     * @return list<array<string, mixed>>
     */
    private function children(array $children): array
    {
        return array_map(fn (array $child) => $this->presentChild($child), $children);
    }

    /**
     * @param  array<string, mixed>  $child
     * @return array<string, mixed>
     */
    private function presentChild(array $child): array
    {
        $name = DisplayName::title((string) ($child['name'] ?? ''));
        $child['name'] = $name;
        $child['initials'] = DisplayName::initials($name);

        return $child;
    }

    /**
     * @return array{dashboard: string, marks: string, attendance: string, fees: string, report: string}
     */
    private function links(int $studentId): array
    {
        return [
            'dashboard' => route('parent.dashboard', ['child' => $studentId]),
            'marks' => route('parent.children.grades', $studentId),
            'attendance' => route('parent.children.attendance', $studentId),
            'fees' => route('parent.children.fees', $studentId),
            'report' => route('parent.children.report', $studentId),
        ];
    }

    /**
     * @param  array<string, mixed>  $bundle
     * @return array{0: array<string, mixed>, 1: bool}
     */
    private function preview(Request $request, array $bundle): array
    {
        $state = (string) $request->query('state', '');
        if (! in_array($state, ['loading', 'empty', 'error'], true) || ! app()->environment(['local', 'testing'])) {
            return [$bundle, false];
        }
        if ($state === 'loading') {
            return [$bundle, true];
        }
        if ($state === 'empty') {
            $bundle['grades']['exam_groups'] = [];
            $bundle['grades']['chart'] = ['labels' => [], 'scores' => []];
            $bundle['headline'] = ['average' => null, 'label' => '', 'delta' => null, 'since' => null, 'has_unreleased' => false];

            return [$bundle, false];
        }
        $bundle['errors']['fees'] = true;

        return [$bundle, false];
    }

    private function maskPhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';
        if (strlen($digits) < 7) {
            return null;
        }

        return '+'.substr($digits, 0, 3).' '.substr($digits, 3, 3).' ••• '.substr($digits, -3);
    }
}
