{{-- SPDX-License-Identifier: MIT --}}
<div>
    <h1 class="admin-h1 my-3">Activity Log</h1>
</div>

@include('partials.message')

<x-table :headers="['Title', 'Description', 'Date and Time', 'Ip']">
    @if(count($activitylog) > 0)
        @foreach ($activitylog as $activity)
            <tr>
                <td>{{$activity->log_name}}</td>
                <td>{{$activity->description}}</td>
                <td>{{ \Carbon\Carbon::parse($activity->created_at)->format(' d-m-Y  H:i:s') }}</td>
                <td>
                    @if(asset($activity->properties['ip']) !='')
                        {{ $activity->properties['ip'] }}
                    @else
                        --
                    @endif
                </td>
            </tr>
        @endforeach
    @else
        <tr>
            <td colspan="4" style="text-align: center;">No records found</td>
        </tr>
    @endif
</x-table>

{{ $activitylog->links() }}
