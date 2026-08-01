<?php

namespace App\Ai\Tools\Superadmin;

use App\Ai\Concerns\AuthorizesPlatformAction;
use App\Services\Superadmin\SubscriptionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Approvals\Approval;
use Laravel\Ai\Concerns\InteractsWithApprovals;
use Laravel\Ai\Contracts\Approvable;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Approves a pending subscription (sets approved + start/end dates).
 * Approvable — grants billing access for a school.
 */
class ApproveSubscriptionTool implements Approvable, Tool
{
    use AuthorizesPlatformAction;
    use InteractsWithApprovals;

    public function __construct(private readonly SubscriptionService $subscriptions) {}

    public function description(): Stringable|string
    {
        return 'Approve a pending school subscription by id. Sets status=approved and a one-month window from today. Requires human approval.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Subscription id')->required(),
        ];
    }

    protected function needsApproval(Request $request): Approval|bool
    {
        $id = $request['id'] ?? '?';

        return Approval::required(
            "Approve subscription id={$id}. This grants an active billing window for the school."
        );
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        if ($error = $this->authorizeOrMessage($user)) {
            return $error;
        }

        try {
            $subscription = $this->subscriptions->approve((int) $request['id']);
        } catch (ValidationException $e) {
            return '❌ Validation failed: '.collect($e->errors())->flatten()->implode('; ');
        } catch (ModelNotFoundException $e) {
            return '❌ Subscription not found.';
        }

        return "✅ Subscription approved: id={$subscription->id}, school_id={$subscription->school_id}, start={$subscription->start_date?->toDateString()}, end={$subscription->end_date?->toDateString()}.";
    }
}
