<?php

namespace App\Services\Superadmin;

use App\Models\Plan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Shared plan mutators for Livewire PlanForm and Toshi platform tools.
 * Mirrors submitPlan() create/update behaviour from Batch A audit.
 */
class PlanService
{
    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function create(array $data): Plan
    {
        $validated = $this->validate($data);

        if (! array_key_exists('order', $validated) || $validated['order'] === null) {
            $validated['order'] = (int) Plan::max('order') + 1;
        }

        if (empty($validated['display_name'])) {
            $validated['display_name'] = $validated['name'];
        }

        return Plan::create($validated);
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function update(int $id, array $data): Plan
    {
        $plan = Plan::findOrFail($id);
        $validated = $this->validate($data);

        if (empty($validated['display_name'])) {
            $validated['display_name'] = $validated['name'];
        }

        // Preserve existing order when caller omits it (nullable in validation).
        if (! array_key_exists('order', $validated) || $validated['order'] === null) {
            unset($validated['order']);
        }

        $plan->update($validated);

        return $plan->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private function validate(array $data): array
    {
        return Validator::make($data, [
            'cycle' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
            'is_active' => 'nullable',
            'amount' => 'required|numeric',
            'no_of_users' => 'required|integer',
            'no_of_students' => 'required|integer',
            'whatsapp_services' => 'nullable',
            'no_of_events' => 'nullable|integer',
            'no_of_folders' => 'nullable|integer',
            'no_of_files' => 'nullable|integer',
            'no_of_videos' => 'nullable|integer',
            'no_of_audios' => 'nullable|integer',
            'no_of_bulletins' => 'nullable|integer',
            'no_of_groups' => 'nullable|integer',
        ])->validate();
    }
}
