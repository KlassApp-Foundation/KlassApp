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
 * Cancels a subscription (status → canceled). Approvable — access / billing downgrade.
 */
class CancelSubscriptionTool implements Approvable, Tool
{
    use AuthorizesPlatformAction;
    use InteractsWithApprovals;

    public function __construct(private readonly SubscriptionService $subscriptions) {}

    public function description(): Stringable|string
    {
        return 'Cancel a school subscription by id (status → canceled). Requires human approval.';
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
            "Cancel subscription id={$id}. This revokes the school's active subscription status."
        );
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        if ($error = $this->authorizeOrMessage($user)) {
            return $error;
        }

        try {
            $subscription = $this->subscriptions->cancel((int) $request['id']);
        } catch (ValidationException $e) {
            return '❌ Validation failed: '.collect($e->errors())->flatten()->implode('; ');
        } catch (ModelNotFoundException $e) {
            return '❌ Subscription not found.';
        }

        return "✅ Subscription canceled: id={$subscription->id}, school_id={$subscription->school_id}.";
    }
}
