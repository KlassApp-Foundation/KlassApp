@extends('layouts.admin.layout')
@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

@include('layouts.partials.page-header', [
    'title' => 'Toshi Activity',
    'subtitle' => 'Every action Toshi has performed or proposed for your school.',
])

{{-- Filters --}}
<div class="flex flex-wrap gap-3 mt-4 mb-4">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Tool</label>
            <select name="tool" class="form-select text-sm border rounded px-3 py-1.5">
                <option value="">All tools</option>
                @foreach($tools as $t)
                    <option value="{{ $t }}" {{ $currentTool === $t ? 'selected' : '' }}>{{ $t }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
            <select name="status" class="form-select text-sm border rounded px-3 py-1.5">
                <option value="">All statuses</option>
                <option value="success" {{ $currentStatus === 'success' ? 'selected' : '' }}>Success</option>
                <option value="failed" {{ $currentStatus === 'failed' ? 'selected' : '' }}>Failed</option>
                <option value="cancelled" {{ $currentStatus === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
        </div>
        <button type="submit" class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">Filter</button>
        @if($currentTool || $currentStatus)
            <a href="{{ route('admin.toshi.activity') }}" class="px-3 py-1.5 text-sm text-gray-600 hover:text-gray-800">Clear</a>
        @endif
    </form>
</div>

{{-- Log table --}}
<div class="relative overflow-x-auto">
    <table class="w-full text-sm text-left">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
            <tr>
                <th class="px-4 py-3">Time</th>
                <th class="px-4 py-3">Action</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Details</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            @php $props = $log->properties; @endphp
            <tr class="border-b hover:bg-gray-50">
                <td class="px-4 py-2.5 whitespace-nowrap text-gray-500 text-xs">
                    {{ $log->created_at->format('M j, g:ia') }}
                </td>
                <td class="px-4 py-2.5 whitespace-nowrap font-medium">
                    <span class="text-base mr-1">
                        @switch($props['tool'] ?? '')
                            @case('toolRecordAttendance') 📋 @break
                            @case('toolRecordBulkAttendance') 📋 @break
                            @case('toolAddStudent') 👤 @break
                            @case('toolAddTeacher') 👨‍🏫 @break
                            @case('toolAddParent') 👪 @break
                            @case('toolAddCoAdmin') 👑 @break
                            @case('toolCreateExam') 📝 @break
                            @case('toolEnterMark') 📊 @break
                            @case('toolCreateFee') 💰 @break
                            @case('toolCreateTerm') 📅 @break
                            @case('toolRecordPayment') 💳 @break
                            @case('toolAssignTeacher') 🔗 @break
                            @case('toolCreateSubject') 📚 @break
                            @case('toolSeedDefaultGrading') ⚙️ @break
                            @case('toolSetGradingScale') ⚖️ @break
                            @default ⚡
                        @endswitch
                    </span>
                    {{ $props['tool'] ?? 'unknown' }}
                </td>
                <td class="px-4 py-2.5 whitespace-nowrap">
                    @switch($props['status'] ?? '')
                        @case('success')
                            <span class="inline-block px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800">Success</span>
                            @break
                        @case('failed')
                            <span class="inline-block px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-800">Failed</span>
                            @break
                        @case('cancelled')
                            <span class="inline-block px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-600">Cancelled</span>
                            @break
                        @default
                            <span class="inline-block px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-600">{{ $props['status'] ?? 'unknown' }}</span>
                    @endswitch
                </td>
                <td class="px-4 py-2.5 text-gray-600 text-xs max-w-xs truncate">
                    {{ $log->description }}
                    @if($props['status'] === 'cancelled')
                        <br><span class="text-gray-400 italic">User declined confirmation</span>
                    @elseif($props['status'] === 'failed' && isset($props['result']))
                        <br><span class="text-red-500">{{ \Illuminate\Support\Str::limit($props['result'], 80) }}</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="px-4 py-8 text-center text-gray-400">
                    No Toshi activity yet. Ask Toshi to perform an action and it will appear here.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($logs->hasPages())
    <div class="mt-4">
        {{ $logs->links() }}
    </div>
@endif

</div>
@endsection
