{{-- SPDX-License-Identifier: MIT --}}
<div x-data="{ showFilters: false }">
    {{-- Header --}}
    <div class="ds-page-head mt-2">
        <div>
            <h1 class="ds-page-head-title">Schools</h1>
            <p class="ds-page-head-sub">{{ $schools->total() }} school(s) found</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="showFilters = !showFilters" class="ds-btn ds-btn-ghost ds-btn-sm">
                <i class="fa-solid fa-filter"></i> Filters
            </button>
            <a href="{{ url('/superadmin/academics/school/create') }}" class="ds-btn ds-btn-primary ds-btn-sm">
                <i class="fa-solid fa-plus"></i> Create School
            </a>
        </div>
    </div>

    {{-- Filter Panel --}}
    <div x-show="showFilters" x-transition class="ds-card mb-4">
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-3 mb-3">
            <div>
                <label class="ds-form-label">Search</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Name, email, phone..." class="ds-form-input">
            </div>
            <div>
                <label class="ds-form-label">Plan</label>
                <select wire:model.live="filterPlan" class="ds-form-input ds-form-select">
                    <option value="">All Plans</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}">{{ $plan->display_name ?? $plan->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="ds-form-label">Country</label>
                <select wire:model.live="filterCountry" class="ds-form-input ds-form-select">
                    <option value="">All Countries</option>
                    @foreach($countries as $country)
                        <option value="{{ $country->id }}">{{ $country->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="ds-form-label">City</label>
                <select wire:model.live="filterCity" class="ds-form-input ds-form-select">
                    <option value="">All Cities</option>
                    @foreach($cities as $city)
                        <option value="{{ $city->id }}">{{ $city->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="ds-form-label">Status</label>
                <select wire:model.live="filterStatus" class="ds-form-input ds-form-select">
                    <option value="">All</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
        </div>
        <div class="flex items-center justify-between">
            <span class="text-xs text-gray-500">
                @if($search || $filterPlan || $filterCity || $filterCountry || $filterStatus !== '')
                    <button wire:click="clearFilters" class="ds-btn ds-btn-ghost ds-btn-sm">Clear all filters</button>
                @endif
            </span>
            <div class="flex items-center gap-2">
                <label class="text-xs text-gray-500">Per page:</label>
                <select wire:model.live="perPage" class="ds-form-input ds-form-select" style="width:auto;padding:4px 28px 4px 8px;">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Table --}}
    @if (session()->has('message'))
        <div class="ds-badge ds-badge-success mb-3" style="display:block;padding:8px 12px;border-radius:8px;">
            {{ session('message') }}
        </div>
    @endif

    <div class="ds-table-wrap">
        <table class="ds-table ds-table-hover">
            <thead>
                <tr>
                    <th class="cursor-pointer" wire:click="sortBy('name')">
                        Name
                        @if($sortField === 'name') <i class="fa-solid fa-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i> @endif
                    </th>
                    <th>Contact</th>
                    <th class="cursor-pointer" wire:click="sortBy('city_id')">
                        City
                        @if($sortField === 'city_id') <i class="fa-solid fa-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i> @endif
                    </th>
                    <th class="text-center cursor-pointer" wire:click="sortBy('student_count')">
                        Students
                        @if($sortField === 'student_count') <i class="fa-solid fa-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i> @endif
                    </th>
                    <th class="text-center cursor-pointer" wire:click="sortBy('teacher_count')">
                        Teachers
                        @if($sortField === 'teacher_count') <i class="fa-solid fa-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i> @endif
                    </th>
                    <th class="text-center">Plan</th>
                    <th class="text-center cursor-pointer" wire:click="sortBy('status')">
                        Status
                        @if($sortField === 'status') <i class="fa-solid fa-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i> @endif
                    </th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($schools as $school)
                    <tr class="cursor-pointer hover:bg-gray-50" onclick="window.location='{{ url('/superadmin/academics/school/detail/' . $school->id) }}'">
                        <td class="font-medium">{{ $school->name }}</td>
                        <td>
                            <div class="text-xs text-gray-600">{{ $school->email }}</div>
                            <div class="text-xs text-gray-400">{{ $school->phone }}</div>
                        </td>
                        <td class="text-xs text-gray-600">{{ $school->city->name ?? ($school->country->name ?? '—') }}</td>
                        <td class="text-center">{{ $school->student_count ?? '—' }}</td>
                        <td class="text-center">{{ $school->teacher_count ?? '—' }}</td>
                        <td class="text-center">
                            @php
                                $plan = $school->subscription->first()?->plan;
                                $planName = $plan?->display_name ?? '—';
                                $planBadge = match($planName) {
                                    'Freemium' => 'info',
                                    'Growth' => 'approved',
                                    'Premium' => 'warning',
                                    default => 'info',
                                };
                            @endphp
                            <span class="ds-badge ds-badge-{{ $planBadge }}">{{ $planName }}</span>
                        </td>
                        <td class="text-center">
                            <span class="ds-dot ds-dot-{{ $school->status == 1 ? 'green' : 'red' }}"></span>
                            {{ $school->status == 1 ? 'Active' : 'Inactive' }}
                        </td>
                        <td class="text-right">
                            <a href="{{ url('/superadmin/academics/school/detail/' . $school->id) }}" class="ds-btn ds-btn-ghost ds-btn-sm">View</a>
                            <a href="{{ url('/superadmin/academics/school/update/' . $school->id) }}" class="ds-btn ds-btn-ghost ds-btn-sm">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-8 text-gray-400">No schools found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $schools->links() }}
    </div>
</div>
