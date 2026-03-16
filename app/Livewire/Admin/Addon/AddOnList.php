<?php

namespace App\Livewire\Admin\Addon;

use Livewire\Component;
use Illuminate\Support\Facades\Http;

class AddOnList extends Component
{
    public function render()
    {
        $api_url=env('ADDON_API_URL').'/api/addons';

        $response = Http::withoutVerifying()->get($api_url);

        if ($response->successful()) 
        {
            $data = $response->json()['data'];
        }
        else {
        
            $data = []; 
        }

        return view('livewire.admin.addon.add-on-list',[
            'addonsList' => $data
        ]);
    }
}
