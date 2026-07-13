{{-- SPDX-License-Identifier: MIT --}}
<div>
    <h1 class="admin-h1 my-3">Activity Log</h1>
</div>

<div class="ds-table-wrap">
    @include('partials.message')
    <table class="ds-table-ledger">
        <thead>
            <tr>
                <th>Title</th>
                <th>Description</th>
                <th>Date and Time</th>
                <th>Ip</th>
            </tr>
        </thead>
        <tbody>
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
                <tr class="border-b">
                    <td colspan="4" style="text-align: center;">No Records Found</td>
                </tr>
            @endif 
        </tbody>
    </table>
    {{ $activitylog->links() }}
</div>