<?php

namespace App\Livewire\Superadmin\Settings;

use App\Models\EmisSchool;
use Livewire\Component;
use Livewire\WithPagination;

class EmisSchoolList extends Component
{
    use WithPagination;

    public $search = '';

    protected $paginationTheme = 'tailwind';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = EmisSchool::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('emis_code', 'like', '%' . $this->search . '%')
                  ->orWhere('school_name', 'like', '%' . $this->search . '%')
                  ->orWhere('district', 'like', '%' . $this->search . '%');
            });
        }

        $schools = $query->orderBy('district')->orderBy('school_name')->paginate(50);

        return view('livewire.superadmin.settings.emis-school-list', [
            'schools' => $schools,
        ]);
    }
}
