<?php

namespace App\Livewire\Superadmin\Settings;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\School;
use App\Services\FeatureToggleService;

class FeatureToggles extends Component
{
    use WithPagination;

    public string $search = '';

    public function toggle(int $schoolId, string $feature, bool $enabled)
    {
        FeatureToggleService::setEnabled($schoolId, $feature, $enabled);

        $label = FeatureToggleService::availableFeatures()[$feature] ?? $feature;
        $state = $enabled ? 'enabled' : 'disabled';
        session()->flash('message', "{$label} {$state} for this school.");
    }

    public function render()
    {
        $query = School::query();

        if ($this->search) {
            $query->where('name', 'like', "%{$this->search}%");
        }

        $schools = $query->orderBy('name')->paginate(20);

        $schools->getCollection()->transform(function ($school) {
            $school->featureToggles = FeatureToggleService::getForSchool($school->id);
            return $school;
        });

        return view('livewire.superadmin.settings.feature-toggles', [
            'schools' => $schools,
            'features' => FeatureToggleService::availableFeatures(),
        ]);
    }
}
