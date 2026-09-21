<?php

namespace App\Livewire\Superadmin\Settings;

use Livewire\Component;
use App\Services\Superadmin\SystemSettingsService;

class SystemSettings extends Component
{
    public $sitetitle = '';
    public $sitename = '';
    public $sitelogo = '';
    public $favicon = '';
    public $maintenance = '0';
    public $login_status = '1';
    public $register_status = '1';

    public function mount()
    {
        $values = app(SystemSettingsService::class)->all();
        foreach ($values as $key => $value) {
            $this->$key = $value !== '' ? $value : $this->$key;
        }
    }

    public function save()
    {
        app(SystemSettingsService::class)->save([
            'sitetitle' => $this->sitetitle,
            'sitename' => $this->sitename,
            'sitelogo' => $this->sitelogo,
            'favicon' => $this->favicon,
            'maintenance' => $this->maintenance,
            'login_status' => $this->login_status,
            'register_status' => $this->register_status,
        ]);
        session()->flash('message', 'Settings saved.');
    }

    /**
     * Read-only view of what each school ACTUALLY uses.
     *
     * The maintenance/login values on this page are platform DEFAULTS only: a
     * school with its own setting is never affected by them. Showing each school's
     * effective value (and whether it is its own setting or inherited) keeps the
     * page honest instead of implying platform-wide control it does not have.
     *
     * @return list<array<string, mixed>>
     */
    public function schoolAccessRows(): array
    {
        $defaultMaintenance = \Config::get('settings.maintenance');

        return \App\Models\School::orderBy('name')->get()->map(function ($school) use ($defaultMaintenance) {
            $ownLogin = $school->detailValue('login_status');
            $ownMaintenance = $school->detailValue('maintenance');

            $effectiveMaintenance = ($ownMaintenance === null || $ownMaintenance === '')
                ? $defaultMaintenance
                : $ownMaintenance;

            return [
                'id' => $school->id,
                'name' => $school->name,
                'own_login' => $ownLogin,
                'effective_login' => $school->loginStatus(),
                'own_maintenance' => $ownMaintenance,
                'effective_maintenance' => $effectiveMaintenance,
            ];
        })->all();
    }

    public function render()
    {
        return view('livewire.superadmin.settings.system-settings', [
            'schoolAccess' => $this->schoolAccessRows(),
        ]);
    }
}
