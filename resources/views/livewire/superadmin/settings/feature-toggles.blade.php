<div>
    @if (session()->has('message'))
        <div class="ds-badge ds-badge-success" style="display:block;padding:8px 12px;border-radius:8px;margin-bottom:12px;">
            {{ session('message') }}
        </div>
    @endif

    <div class="ds-page-head">
        <h1 class="ds-page-head-title">Feature Toggles</h1>
        <p class="ds-page-head-sub">Enable or disable features per school</p>
    </div>

    <div class="mb-4">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search schools..." class="ds-form-input" style="max-width:320px;">
    </div>

    <div class="ds-table-wrap">
        <table class="ds-table">
            <thead>
                <tr>
                    <th>School</th>
                    @foreach($features as $key => $label)
                        <th class="text-center">{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($schools as $school)
                    <tr>
                        <td class="font-medium">{{ $school->name }}</td>
                        @foreach($features as $key => $label)
                            <td class="text-center">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox"
                                        class="form-toggle"
                                        wire:change="toggle({{ $school->id }}, '{{ $key }}', $event.target.checked)"
                                        {{ ($school->featureToggles[$key]['enabled'] ?? false) ? 'checked' : '' }}>
                                    <span class="ml-1 text-xs {{ ($school->featureToggles[$key]['enabled'] ?? false) ? 'text-green-600' : 'text-gray-400' }}">
                                        {{ ($school->featureToggles[$key]['enabled'] ?? false) ? 'On' : 'Off' }}
                                    </span>
                                </label>
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($features) + 1 }}" class="text-center py-8 text-gray-400">No schools found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $schools->links() }}</div>
</div>
