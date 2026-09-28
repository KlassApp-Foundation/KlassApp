<?php

namespace App\Livewire\Superadmin\Reports;

use App\Models\DemoRequest;
use Livewire\Component;
use Livewire\WithPagination;

class DemoRequests extends Component
{
    use WithPagination;

    public function render()
    {
        return view('livewire.superadmin.reports.demo-requests', [
            'demoRequests' => DemoRequest::orderByDesc('id')->paginate(15),
        ]);
    }
}
