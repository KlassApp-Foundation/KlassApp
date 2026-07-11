@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell">
    <div class="dashboard-heading">
        <div>
            <h1 class="dashboard-section-title">Transport Routes</h1>
            <p class="dashboard-subtitle">Manage school transport routes and vehicles.</p>
        </div>
        <a href="{{ route('admin.transport.create') }}" class="ds-btn ds-btn-primary ds-btn-sm">
            + Add Route
        </a>
    </div>

    @include('partials.message')

    <div class="ds-card">
        <div class="ds-table-wrap">
            <table class="ds-table ds-table-striped ds-table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Route Name</th>
                        <th>Vehicle</th>
                        <th>Time</th>
                        <th>Stops</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($routes as $i => $route)
                    <tr>
                        <td class="text-muted">{{ $routes->firstItem() + $i }}</td>
                        <td><strong>{{ $route->name }}</strong></td>
                        <td>{{ $route->vehicle_number }}</td>
                        <td>{{ $route->start_time }} &ndash; {{ $route->end_time }}</td>
                        <td class="max-w-xs truncate">{{ Str::limit($route->stops, 60) }}</td>
                        <td>
                            @if($route->status)
                                <span class="ds-badge ds-badge-success">Active</span>
                            @else
                                <span class="ds-badge ds-badge-muted">Inactive</span>
                            @endif
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('admin.transport.edit', $route->id) }}"
                               class="ds-btn ds-btn-outline ds-btn-sm">Edit</a>
                            <form method="POST" action="{{ route('admin.transport.destroy', $route->id) }}"
                                  class="inline" onsubmit="return confirm('Remove this transport route?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="ds-btn ds-btn-danger ds-btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-8">
                            No transport routes created yet.
                            <a href="{{ route('admin.transport.create') }}" class="text-blue-600 hover:underline block mt-2">
                                Add your first route
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $routes->links() }}
    </div>
</div>
@endsection
