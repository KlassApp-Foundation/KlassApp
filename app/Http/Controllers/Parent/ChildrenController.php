<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Services\Parent\ParentPortalService;

class ChildrenController extends Controller
{
    public function __construct(private ParentPortalService $portal) {}

    public function index()
    {
        $children = $this->portal->listChildren(auth()->user());

        return view('parent.children', [
            'children' => $children['children'] ?? [],
            'emptyMessage' => $children['success'] ? null : ($children['message'] ?? null),
        ]);
    }
}
