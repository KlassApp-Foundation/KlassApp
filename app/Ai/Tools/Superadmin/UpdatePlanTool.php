<?php

namespace App\Ai\Tools\Superadmin;

use App\Ai\Approvals\Approval;
use App\Ai\Concerns\AuthorizesPlatformAction;
use App\Ai\Concerns\InteractsWithApprovals;
use App\Ai\Contracts\Approvable;
use App\Services\Superadmin\PlanService;
use App\Services\Toshi\PlatformToolInvoker;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Updates a billing plan. Approvable — plan mutation is in the Phase 1 approval set.
 * Do not use withoutApproval() on this tool.
 */
class UpdatePlanTool implements Approvable, Tool
{
    use AuthorizesPlatformAction;
    use InteractsWithApprovals;

    public function __construct(private readonly PlanService $plans) {}

    public function description(): Stringable|string
    {
        return 'Update an existing KlassApp billing plan by id. Requires human approval before write.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Plan id')->required(),
            'cycle' => $schema->string()->description('Billing cycle')->required(),
            'name' => $schema->string()->description('Plan name')->required(),
            'amount' => $schema->number()->description('Price amount')->required(),
            'no_of_users' => $schema->integer()->description('Max users')->required(),
            'no_of_students' => $schema->integer()->description('Max students')->required(),
            'order' => $schema->integer()->description('Display order')->nullable(),
            'is_active' => $schema->integer()->description('1=active')->nullable(),
            'whatsapp_services' => $schema->integer()->nullable(),
            'no_of_events' => $schema->integer()->nullable(),
            'no_of_folders' => $schema->integer()->nullable(),
            'no_of_files' => $schema->integer()->nullable(),
            'no_of_videos' => $schema->integer()->nullable(),
            'no_of_audios' => $schema->integer()->nullable(),
            'no_of_bulletins' => $schema->integer()->nullable(),
            'no_of_groups' => $schema->integer()->nullable(),
        ];
    }

    protected function needsApproval(Request $request): Approval|bool
    {
        $id = $request['id'] ?? '?';
        $name = $request['name'] ?? 'unnamed';

        return Approval::required(
            "Update plan id={$id} (\"{$name}\"). This changes platform billing offerings."
        );
    }

    public function handle(Request $request): Stringable|string
    {
        if (! PlatformToolInvoker::isExecutionApproved()) {
            return '❌ Plan update requires human approval before execution (Decision::approve).';
        }

        $user = auth()->user() ?? request()->user();
        if ($error = $this->authorizeOrMessage($user)) {
            return $error;
        }

        try {
            $id = (int) $request['id'];
            $existing = \App\Models\Plan::find($id);

            $plan = $this->plans->update($id, [
                'cycle' => $request['cycle'],
                'name' => $request['name'],
                'amount' => $request['amount'],
                'no_of_users' => $request['no_of_users'],
                'no_of_students' => $request['no_of_students'],
                'order' => $request['order'] ?? $existing?->order,
                'is_active' => $request['is_active'] ?? 1,
                'whatsapp_services' => $request['whatsapp_services'] ?? null,
                'no_of_events' => $request['no_of_events'] ?? null,
                'no_of_folders' => $request['no_of_folders'] ?? null,
                'no_of_files' => $request['no_of_files'] ?? null,
                'no_of_videos' => $request['no_of_videos'] ?? null,
                'no_of_audios' => $request['no_of_audios'] ?? null,
                'no_of_bulletins' => $request['no_of_bulletins'] ?? null,
                'no_of_groups' => $request['no_of_groups'] ?? null,
            ]);
        } catch (ValidationException $e) {
            return '❌ Validation failed: '.collect($e->errors())->flatten()->implode('; ');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return '❌ Plan not found.';
        }

        return "✅ Plan updated: {$plan->name} (id={$plan->id}, amount={$plan->amount}).";
    }
}
