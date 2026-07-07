<?php

namespace App\Livewire\Superadmin\Settings;

use Livewire\Component;
use App\Models\Setting;

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
        foreach (['sitetitle', 'sitename', 'sitelogo', 'favicon', 'maintenance', 'login_status', 'register_status'] as $key) {
            $setting = Setting::where('key', $key)->first();
            $this->$key = $setting ? $setting->value : '';
        }
    }

    public function save()
    {
        foreach (['sitetitle', 'sitename', 'sitelogo', 'favicon', 'maintenance', 'login_status', 'register_status'] as $key) {
            Setting::updateOrCreate(['key' => $key], ['value' => $this->$key]);
        }
        // Clear cached config so changes take effect immediately
        \Illuminate\Support\Facades\Config::set('settings.login_status', $this->login_status);
        session()->flash('message', 'Settings saved.');
    }

    public function render()
    {
        return view('livewire.superadmin.settings.system-settings');
    }
}
