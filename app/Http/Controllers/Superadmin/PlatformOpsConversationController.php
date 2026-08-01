<?php

namespace App\Http\Controllers\Superadmin;

use App\Services\Toshi\PlatformOpsConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Laravel\Ai\Approvals\Decision;
use Laravel\Ai\Approvals\Decisions;
use Laravel\Ai\Models\Conversation;

/**
 * Complete Approval Flow for PlatformOperationsAgent (laravel/ai HITL).
 *
 * Shape mirrors Laravel AI SDK docs: GET chat screen, POST message XOR decisions.
 * Extended with `edit` action (package Decision::edit) beyond the doc's approve/reject-only validation.
 */
class PlatformOpsConversationController
{
    public function __construct(private readonly PlatformOpsConversationService $ops) {}

    public function show(Request $request, Conversation $conversation): View
    {
        Gate::authorize('view', $conversation);

        $conversation->load(['messages' => fn ($q) => $q->orderBy('created_at')]);

        return view('superadmin.toshi.ops-conversation', [
            'conversation' => $conversation,
            'pendingApprovals' => $this->ops->pendingApprovals($conversation),
            'hasPendingApprovals' => $this->ops->hasPendingApprovals($conversation),
        ]);
    }

    public function store(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('view', $conversation);

        // Doc uses prohibited_with (Laravel 13); this app is Laravel 12 — use prohibits (equivalent XOR).
        $validated = $request->validate([
            'message' => ['nullable', 'string', 'required_without:decisions', 'prohibits:decisions'],
            'decisions' => ['nullable', 'array', 'required_without:message', 'prohibits:message'],
            'decisions.*.action' => ['required_with:decisions', Rule::in(['approve', 'reject', 'edit'])],
            'decisions.*.result' => ['nullable', 'string'],
            'decisions.*.arguments' => ['nullable', 'array', 'required_if:decisions.*.action,edit'],
        ]);

        // Doc snippet uses `$validated->collect(...)` but validate() returns an array — use collect().
        $prompt = isset($validated['decisions'])
            ? Decisions::from(collect($validated['decisions'])->map(
                fn (array $decision) => match ($decision['action']) {
                    'approve' => Decision::approve(),
                    'reject' => Decision::reject($decision['result'] ?? null),
                    'edit' => Decision::edit($decision['arguments'] ?? []),
                }
            )->all())
            : $validated['message'];

        $response = $this->ops->prompt($request->user(), $conversation, $prompt);

        return response()->json([
            'conversation_id' => $response->conversationId,
            'status' => $response->hasPendingApprovals() ? 'awaiting_approval' : 'complete',
            'message' => $response->text,
            'approvals' => $response->pendingApprovals,
        ]);
    }
}
