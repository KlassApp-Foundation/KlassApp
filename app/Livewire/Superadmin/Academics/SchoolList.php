<?php

namespace App\Livewire\Superadmin\Academics;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\School;
use App\Models\Plan;
use App\Models\City;
use App\Models\Country;

class SchoolList extends Component
{
    use WithPagination;

    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 10;

    // Filters
    public $filterPlan = '';
    public $filterCountry = ''; // Default: all countries
    public $filterCity = '';
    public $filterStatus = '';
    public $filterMinStudents = null;
    public $filterMaxStudents = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
        'filterPlan' => ['except' => ''],
        'filterCountry' => ['except' => ''],
        'filterCity' => ['except' => ''],
        'filterStatus' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterPlan()
    {
        $this->resetPage();
    }

    public function updatingFilterCity()
    {
        $this->resetPage();
    }

    public function updatingFilterCountry()
    {
        $this->filterCity = ''; // Reset city when country changes
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->filterPlan = '';
        $this->filterCity = '';
        $this->filterCountry = '';
        $this->filterStatus = '';
        $this->filterMinStudents = null;
        $this->filterMaxStudents = null;
        $this->sortField = 'created_at';
        $this->sortDirection = 'desc';
        $this->resetPage();
    }

    public function render()
    {
        $schools = School::query()
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'like', '%' . $this->search . '%')
                       ->orWhere('email', 'like', '%' . $this->search . '%')
                       ->orWhere('phone', 'like', '%' . $this->search . '%')
                       ->orWhere('ministry_code', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterStatus !== '', function ($q) {
                $q->where('status', $this->filterStatus);
            })
            ->when($this->filterCity, function ($q) {
                $q->where('city_id', $this->filterCity);
            })
            ->when($this->filterCountry, function ($q) {
                $q->where('country_id', $this->filterCountry);
            })
            ->when($this->filterPlan, function ($q) {
                $q->whereHas('subscription', function ($sub) {
                    $sub->where('plan_id', $this->filterPlan);
                });
            })
            ->when($this->filterMinStudents, function ($q) {
                $q->has('user', '>=', $this->filterMinStudents, 'and', function ($uq) {
                    $uq->where('usergroup_id', 6)->where('status', '!=', 'exit');
                });
            })
            ->with(['city', 'country', 'subscription.plan'])
            ->withCount(['user as student_count' => function ($q) {
                $q->where('usergroup_id', 6)->where('status', '!=', 'exit');
            }])
            ->withCount(['user as teacher_count' => function ($q) {
                $q->where('usergroup_id', 5)->where('status', '!=', 'exit');
            }])
            ->withCount('user as total_users_count')
            ->withCount('subscription as subscription_count')
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        $plans = Plan::where('is_active', 1)->orderBy('order')->get();
        $cities = City::when($this->filterCountry, function ($q) {
            $q->where('country_id', $this->filterCountry);
        })->orderBy('name')->get();

        $countries = Country::orderBy('name')->get();

        return view('livewire.superadmin.academics.school-list', [
            'schools' => $schools,
            'plans' => $plans,
            'cities' => $cities,
            'countries' => $countries,
        ]);
    }
}
