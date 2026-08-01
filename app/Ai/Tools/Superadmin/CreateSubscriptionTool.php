<?php

namespace App\Ai\Tools\Superadmin;

use App\Ai\Concerns\AuthorizesPlatformAction;
use App\Services\Superadmin\SubscriptionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Creates a school subscription (typically pending). Not Approvable — create is not
 * inherently destructive; approving/canceling are the sensitive mutators.
 */
class CreateSubscriptionTool implements Tool
{
    use AuthorizesPlatformAction;

    public function __construct(private readonly SubscriptionService $subscriptions) {}

    public function description(): Stringable|string
    {
        return 'Create a school subscription. Required: user_id, plan_id, school_id. Status defaults to pending. Use ApproveSubscriptionTool to approve.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'user_id' => $schema->integer()->description('Subscriber user id')->required(),
            'plan_id' => $schema->integer()->description('Billing plan id')->required(),
            'school_id' => $schema->integer()->description('School id')->required(),
            'status' => $schema->string()->description('pending|approved|canceled|expired; default pending')->nullable(),
            'payment_details' => $schema->string()->nullable(),
            'plan_details' => $schema->string()->nullable(),
            'amount_paid' => $schema->number()->nullable(),
            'payment_reference' => $schema->string()->nullable(),
            'payment_method' => $schema->string()->nullable(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        if ($error = $this->authorizeOrMessage($user)) {
            return $error;
        }

        try {
            $subscription = $this->subscriptions->create([
                'user_id' => $request['user_id'],
                'plan_id' => $request['plan_id'],
                'school_id' => $request['school_id'],
                'status' => $request['status'] ?? 'pending',
                'payment_details' => $request['payment_details'] ?? null,
                'plan_details' => $request['plan_details'] ?? null,
                'amount_paid' => $request['amount_paid'] ?? null,
                'payment_reference' => $request['payment_reference'] ?? null,
                'payment_method' => $request['payment_method'] ?? null,
            ]);
        } catch (ValidationException $e) {
            return '❌ Validation failed: '.collect($e->errors())->flatten()->implode('; ');
        }

        return "✅ Subscription created: id={$subscription->id}, school_id={$subscription->school_id}, plan_id={$subscription->plan_id}, status={$subscription->status}.";
    }
}
