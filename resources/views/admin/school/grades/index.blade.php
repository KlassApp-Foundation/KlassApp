@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

    {{-- Header --}}
    <div class="dashboard-heading">
        <div>
            <h1 class="dashboard-section-title" style="font-size: 1.25rem;">Grading System</h1>
            <p class="dashboard-subtitle" style="margin-top: 4px;">
                Define and customize grading boundaries per level. Changes apply to this school only.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.grades.create') }}"
               class="ds-btn ds-btn-primary ds-btn-sm">
                + Add Grading Rule
            </a>
        </div>
    </div>

    @include('partials.message')

    @php
        $groups = [
            'Nursery & Primary' => ['nursery', 'primary'],
            'O-Level & A-Level' => ['o-level', 'a-level'],
        ];
        $grouped = [];
        foreach ($gradingRules as $rule) {
            $stdName = $rule->standard->name ?? 'other';
            $grouped[$stdName][] = $rule;
        }
    @endphp

    @foreach ($groups as $groupLabel => $levelNames)
    <div class="mt-6">
        <h2 class="text-base font-semibold text-gray-700 mb-3" style="font-family: 'Sora', sans-serif;">{{ $groupLabel }}</h2>

        @foreach ($levelNames as $levelName)
            @php $rules = $grouped[$levelName] ?? []; @endphp
            @if (empty($rules)) @continue @endif

            @php
                $levelLabel = ucfirst($levelName);
                $isDescriptive = $levelName === 'nursery';
                $isPointsBased = $levelName === 'a-level';
                $headerLabel = $isPointsBased ? 'Points' : 'Aggregates';
            @endphp

            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden mb-4">
                <div class="px-4 py-3 border-b border-gray-100" style="background: #FAFAF5;">
                    <h3 class="text-sm font-semibold" style="font-family: 'Sora', sans-serif; color: #0F172A;">
                        {{ $levelLabel }}
                        @if($isDescriptive)
                            <span class="text-xs font-normal text-gray-500 ml-2">— descriptive ratings, no numeric boundaries</span>
                        @endif
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-gray-50 text-xs uppercase text-gray-600">
                                <th class="px-3 py-2 text-left font-semibold">{{ $headerLabel }}</th>
                                <th class="px-3 py-2 text-left font-semibold">Grade</th>
                                @if(!$isDescriptive)
                                <th class="px-3 py-2 text-center font-semibold">Min</th>
                                <th class="px-3 py-2 text-center font-semibold">Max</th>
                                @else
                                <th class="px-3 py-2 text-center font-semibold" colspan="2">Type</th>
                                @endif
                                <th class="px-3 py-2 text-left font-semibold">Remark</th>
                                <th class="px-3 py-2 text-center font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($rules as $rule)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-3 py-2 text-gray-600">
                                    {{ $isPointsBased ? ($rule->points ?? '—') : ($rule->points ?? '—') }}
                                </td>
                                <td class="px-3 py-2 font-semibold" style="color: #0F172A;">
                                    {{ $rule->grade }}
                                </td>
                                @if(!$isDescriptive)
                                <td class="px-3 py-2 text-center">{{ $rule->min_score }}</td>
                                <td class="px-3 py-2 text-center">{{ $rule->max_score }}</td>
                                @else
                                <td class="px-3 py-2 text-center text-gray-400" colspan="2">—</td>
                                @endif
                                <td class="px-3 py-2 text-gray-600">{{ $rule->remark ?? '—' }}</td>
                                <td class="px-3 py-2 text-center">
                                    <a href="{{ route('admin.grades.edit', $rule->id) }}"
                                       class="text-xs font-medium text-blue-600 hover:text-blue-800 mr-2">Edit</a>
                                    <form action="{{ route('admin.grades.remove', $rule->id) }}" method="POST"
                                          class="inline" onsubmit="return confirm('Delete this grading rule?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs font-medium text-red-500 hover:text-red-700">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-2 border-t border-gray-100 bg-gray-50 text-xs text-gray-500">
                    <a href="{{ route('admin.grades.create') }}?standard={{ $levelName }}"
                       class="text-blue-600 hover:text-blue-800">+ Add rule</a> for {{ $levelLabel }}
                </div>
            </div>
        @endforeach
    </div>
    @endforeach

</div>
@endsection
