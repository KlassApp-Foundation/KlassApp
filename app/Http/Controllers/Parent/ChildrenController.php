<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Services\Parent\ParentPortalService;

class ChildrenController extends Controller
{
    public function __construct(private ParentPortalService $portal) {}

    public function index()
    {
        $listed = $this->portal->listChildren(auth()->user());

        return view('parent.children', [
            'children' => $listed['children'] ?? [],
            'groupedBySchool' => $listed['grouped_by_school'] ?? [],
            'emptyMessage' => ($listed['success'] ?? false) ? null : ($listed['message'] ?? null),
        ]);
    }
}
