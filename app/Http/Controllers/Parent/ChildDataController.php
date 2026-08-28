<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Services\Parent\ParentPortalService;
use Illuminate\Http\JsonResponse;

class ChildDataController extends Controller
{
    public function __construct(private ParentPortalService $portal) {}

    public function fees(int $student): JsonResponse
    {
        return $this->respond($this->portal->feeBalance(auth()->user(), null, $student));
    }

    public function grades(int $student): JsonResponse
    {
        return $this->respond($this->portal->grades(auth()->user(), null, $student));
    }

    public function attendance(int $student): JsonResponse
    {
        return $this->respond($this->portal->attendance(auth()->user(), null, $student));
    }

    /**
     * @param  array{success: bool, message?: string, denied?: bool, data?: array<string, mixed>}  $result
     */
    private function respond(array $result): JsonResponse
    {
        if (! $result['success']) {
            if ($result['denied'] ?? false) {
                abort(403, $result['message'] ?? 'Access denied.');
            }

            abort(422, $result['message'] ?? 'Unable to load data.');
        }

        return response()->json([
            'success' => true,
            'data' => $result['data'] ?? [],
            'message' => $result['message'] ?? null,
        ]);
    }
}
