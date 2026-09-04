{{-- SPDX-License-Identifier: MIT --}}
@props(['grades', 'emptyMessage' => null])

<div class="px-4 md:px-5 py-4" data-testid="parent-grades-panel-body">
    @if ($grades && !empty($grades['exam_groups']))
        @foreach ($grades['exam_groups'] as $group)
            <div class="mb-5 last:mb-0">
                <h4 class="text-sm font-semibold mb-2" style="font-family: Sora, sans-serif; color: var(--d-text);">
                    {{ $group['exam_type'] }}
                </h4>
                <div class="ds-table-wrap overflow-x-auto">
                    <table class="ds-table w-full text-sm">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Score</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($group['subjects'] as $subject)
                                <tr>
                                    <td>{{ $subject['name'] }}</td>
                                    <td>{{ $subject['score'] }}/{{ $subject['total'] }}</td>
                                    <td>{{ $subject['grade'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    @else
        <div class="ds-empty-state py-6">
            <p class="ds-empty-state-title">No results published</p>
            <p class="ds-empty-state-desc">
                {{ $emptyMessage ?? 'Results will appear here once the school publishes marks.' }}
            </p>
        </div>
    @endif
</div>
