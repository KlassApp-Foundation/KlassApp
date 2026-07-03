{{-- SPDX-License-Identifier: MIT --}}
<div x-data="{ showFilters: false }">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mt-2 py-2">
        <div class="text-xl ml-3 font-bold">School List</div>
        <div class="flex items-center gap-2 flex-wrap">
            <button @click="showFilters = !showFilters" class="px-3 py-1.5 rounded text-xs border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 flex items-center gap-1">
                <i class="fa-solid fa-filter text-xs"></i> Filters
            </button>
            <a href="{{ url('/superadmin/academics/school/create') }}" class="px-3 py-1.5 rounded text-xs text-white bg-green-600 hover:bg-green-700 flex items-center gap-1">
                <i class="fa-solid fa-plus text-xs"></i> Create School
            </a>
        </div>
    </div>

    {{-- Filter Panel --}}
    <div x-show="showFilters" x-transition class="bg-white shadow px-4 py-3 mb-2 rounded">
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-3 mb-3">
            {{-- Search --}}
            <div>
                <label class="block text-xs text-gray-500 mb-1">Search</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Name, email, phone..."
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
            </div>
            {{-- Plan --}}
            <div>
                <label class="block text-xs text-gray-500 mb-1">Plan</label>
                <select wire:model.live="filterPlan" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs">
                    <option value="">All Plans</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                    @endforeach
                </select>
            </div>
            {{-- Country --}}
            <div>
                <label class="block text-xs text-gray-500 mb-1">Country</label>
                <select wire:model.live="filterCountry" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs">
                    <option value="">All Countries</option>
                    @foreach($countries as $country)
                        <option value="{{ $country->id }}">{{ $country->name }}</option>
                    @endforeach
                </select>
            </div>
            {{-- City --}}
            <div>
                <label class="block text-xs text-gray-500 mb-1">City</label>
                <select wire:model.live="filterCity" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs">
                    <option value="">All Cities</option>
                    @foreach($cities as $city)
                        <option value="{{ $city->id }}">{{ $city->name }}</option>
                    @endforeach
                </select>
            </div>
            {{-- Status --}}
            <div>
                <label class="block text-xs text-gray-500 mb-1">Status</label>
                <select wire:model.live="filterStatus" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs">
                    <option value="">All</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
        </div>
        <div class="flex items-center justify-between">
            <span class="text-xs text-gray-400">
                {{ $schools->total() }} school(s) found
                @if($search || $filterPlan || $filterCity || $filterCountry || $filterStatus !== '')
                    · <button wire:click="clearFilters" class="text-blue-600 hover:underline">Clear all</button>
                @endif
            </span>
            <div>
                <label class="text-xs text-gray-500 mr-1">Per page:</label>
                <select wire:model.live="perPage" class="border border-gray-300 rounded px-1 py-1 text-xs">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white shadow px-4 py-3 rounded">
        @if (session()->has('message'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-3 py-2 rounded mb-3 text-xs">
                {{ session('message') }}
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full border">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:text-gray-700"
                            wire:click="sortBy('name')">
                            <span class="flex items-center gap-1">
                                Name
                                @if($sortField === 'name')
                                    <i class="fa-solid fa-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-[10px]"></i>
                                @endif
                            </span>
                        </th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:text-gray-700"
                            wire:click="sortBy('city_id')">
                            <span class="flex items-center gap-1">
                                City
                                @if($sortField === 'city_id')
                                    <i class="fa-solid fa-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-[10px]"></i>
                                @endif
                            </span>
                        </th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:text-gray-700"
                            wire:click="sortBy('student_count')">
                            <span class="flex items-center justify-center gap-1">
                                Students
                                @if($sortField === 'student_count')
                                    <i class="fa-solid fa-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-[10px]"></i>
                                @endif
                            </span>
                        </th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:text-gray-700"
                            wire:click="sortBy('teacher_count')">
                            <span class="flex items-center justify-center gap-1">
                                Teachers
                                @if($sortField === 'teacher_count')
                                    <i class="fa-solid fa-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-[10px]"></i>
                                @endif
                            </span>
                        </th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:text-gray-700"
                            wire:click="sortBy('status')">
                            <span class="flex items-center justify-center gap-1">
                                Status
                                @if($sortField === 'status')
                                    <i class="fa-solid fa-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-[10px]"></i>
                                @endif
                            </span>
                        </th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:text-gray-700"
                            wire:click="sortBy('created_at')">
                            <span class="flex items-center justify-center gap-1">
                                Joined
                                @if($sortField === 'created_at')
                                    <i class="fa-solid fa-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-[10px]"></i>
                                @endif
                            </span>
                        </th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-gray-100">
                    @forelse($schools as $school)
                        <tr class="hover:bg-gray-50 transition">
                            {{-- Name --}}
                            <td class="px-3 py-2.5">
                                <a href="{{ url('/superadmin/academics/school/detail', $school->id) }}" class="font-medium text-blue-600 hover:underline text-sm">
                                    {{ $school->name }}
                                </a>
                                @if($school->ministry_code)
                                    <span class="text-[10px] text-gray-400 block">{{ $school->ministry_code }}</span>
                                @endif
                            </td>

                            {{-- Contact --}}
                            <td class="px-3 py-2.5">
                                <div class="text-xs">{{ $school->email ?? '—' }}</div>
                                <div class="text-xs text-gray-400">{{ $school->phone ?? '—' }}</div>
                            </td>

                            {{-- City --}}
                            <td class="px-3 py-2.5 text-xs">
                                {{ $school->city->name ?? '—' }}
                                @if($school->country)
                                    <span class="text-gray-400">, {{ $school->country->name }}</span>
                                @endif
                            </td>

                            {{-- Students --}}
                            <td class="px-3 py-2.5 text-center">
                                <span class="text-sm font-semibold">{{ number_format($school->student_count) }}</span>
                            </td>

                            {{-- Teachers --}}
                            <td class="px-3 py-2.5 text-center">
                                <span class="text-sm">{{ number_format($school->teacher_count) }}</span>
                            </td>

                            {{-- Plan --}}
                            <td class="px-3 py-2.5 text-center">
                                @php
                                    $currentPlan = $school->subscription()->with('plan')->where('status', 'active')->latest()->first();
                                @endphp
                                @if($currentPlan && $currentPlan->plan)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                        {{ $currentPlan->plan->name === 'Growth' ? 'bg-blue-100 text-blue-800' : '' }}
                                        {{ $currentPlan->plan->name === 'Premium' ? 'bg-amber-100 text-amber-800' : '' }}
                                        {{ $currentPlan->plan->name === 'Freemium' ? 'bg-gray-100 text-gray-700' : '' }}">
                                        {{ $currentPlan->plan->name }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="px-3 py-2.5 text-center">
                                @if($school->status == 1)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Inactive
                                    </span>
                                @endif
                            </td>

                            {{-- Joined --}}
                            <td class="px-3 py-2.5 text-center">
                                <span class="text-xs text-gray-500" title="{{ $school->created_at }}">
                                    {{ $school->created_at ? $school->created_at->diffForHumans() : '—' }}
                                </span>
                            </td>

                            {{-- Actions --}}
                            <td class="px-3 py-2.5">
                                <div class="flex items-center justify-center gap-1">
                                    <a href="{{ url('/superadmin/academics/school/detail', $school->id) }}" title="View"
                                        class="p-1 rounded hover:bg-gray-100 text-gray-500 hover:text-gray-700">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </a>
                                    <a href="{{ url('/superadmin/academics/school/update', $school->id) }}" title="Edit"
                                        class="p-1 rounded hover:bg-blue-50 text-blue-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-3 py-8 text-center text-sm text-gray-400">
                                <i class="fa-solid fa-school text-2xl mb-2 block opacity-30"></i>
                                @if($search || $filterPlan || $filterCity || $filterCountry || $filterStatus !== '')
                                    No schools match your filters.
                                    <button wire:click="clearFilters" class="text-blue-600 hover:underline block mx-auto mt-1">Clear filters</button>
                                @else
                                    No schools registered yet.
                                    <a href="{{ url('/superadmin/academics/school/create') }}" class="text-blue-600 hover:underline block mx-auto mt-1">Create the first school</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $schools->links() }}
        </div>
    </div>
</div>
