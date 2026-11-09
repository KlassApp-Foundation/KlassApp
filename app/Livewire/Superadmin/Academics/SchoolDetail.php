<?php

namespace App\Livewire\Superadmin\Academics;

use Livewire\Component;
use App\Models\CurrentPlan;
use App\Models\Plan;
use App\Models\School;
use App\Services\PlanLimitService;

class SchoolDetail extends Component
{
    public $schoolDetailId;

    public ?int $selectedPlanId = null;

    public function mount($id)
    {
        $this->schoolDetailId = $id;
        $this->selectedPlanId = CurrentPlan::query()
            ->where('school_id', (int) $id)
            ->value('plan_id');
    }

    /**
     * Platform admin assigns the runtime plan (CurrentPlan), not billing history.
     * Early adopter is platform-only: it is listed here but never in a school's
     * own plan picker.
     */
    public function assignPlan(PlanLimitService $limits): void
    {
        $this->validate([
            'selectedPlanId' => ['required', 'integer', 'exists:plans,id'],
        ]);

        $plan = Plan::query()
            ->whereKey($this->selectedPlanId)
            ->where('is_active', 1)
            ->first();

        if ($plan === null) {
            $this->addError('selectedPlanId', 'That plan is not active.');

            return;
        }

        CurrentPlan::updateOrCreate(
            ['school_id' => (int) $this->schoolDetailId],
            ['plan_id' => $plan->id, 'status' => 'running'],
        );

        $this->selectedPlanId = $plan->id;
        session()->flash('success', 'Current plan updated to '.($plan->display_name ?: $plan->name).'.');
    }

    public function render(PlanLimitService $limits)
    {
        $schoolDetail = School::with(['subscription.plan', 'user', 'subscription.plan'])
            ->where('id', $this->schoolDetailId)
            ->first();
        $admins = $schoolDetail ? $schoolDetail->user()->whereIn('usergroup_id', [2,3])->get() : collect();

        return view('livewire.superadmin.academics.school-detail', [
            'schoolDetail' => $schoolDetail,
            'admins' => $admins,
            'plans' => Plan::query()->where('is_active', 1)->orderBy('order')->get(),
            'currentPlan' => CurrentPlan::with('plan')->where('school_id', (int) $this->schoolDetailId)->first(),
            'limits' => $schoolDetail ? $limits->status((int) $schoolDetail->id, 'teachers') : null,
            'studentLimits' => $schoolDetail ? $limits->status((int) $schoolDetail->id, 'students') : null,
        ]);
    }
}
