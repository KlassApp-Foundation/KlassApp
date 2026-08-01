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

    public function render()
    {
        return view('livewire.superadmin.settings.system-settings');
    }
}
