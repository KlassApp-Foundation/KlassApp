@extends($layout)

@section('content')
<div class="container" style="background:#FFFFFF;color:#0F172A;padding:24px 16px">
    <h1 style="color:#0F172A;font-size:24px;font-weight:700;margin:0 0 4px">My connected tools</h1>
    <p style="color:#64748B;font-size:14px;margin:0 0 14px">These are your own Google accounts. A tool shows Live only after you connect it, and only if your school allows it.</p>
    <ul class="dashboard-connected-list">
        @foreach ($googleConnectors as $tool)
            <li class="dashboard-connected-row ds-card ds-card-padding-default" data-testid="google-connector-card-{{ $tool['key'] }}" style="background:#FFFFFF;color:#0F172A">
                <span class="dashboard-connected-label">
                    <span>
                        <span class="font-semibold" style="color:#0F172A">{{ $tool['label'] }}</span>
                        @if ($tool['live'])
                            <span class="ds-badge ds-badge-active" data-testid="google-connector-live-{{ $tool['key'] }}">Live</span>
                        @else
                            <span class="ds-badge ds-badge-inactive" data-testid="google-connector-status-{{ $tool['key'] }}">Not connected</span>
                        @endif
                        <span class="block text-sm" style="color:#64748B">{{ $tool['why'] }}</span>
                        @if ($tool['live'] && $tool['email'])
                            <span class="block text-sm" style="color:#64748B" data-testid="google-connector-email-{{ $tool['key'] }}">Account: {{ $tool['email'] }}</span>
                        @endif
                        @if (! $tool['allowed'])
                            <span class="block text-sm" style="color:#64748B" data-testid="google-connector-blocked-{{ $tool['key'] }}">Your school has not turned this on.</span>
                        @endif
                    </span>
                </span>
                @if ($tool['live'])
                    <form method="POST" action="{{ route('admin.google.disconnect', ['connector' => $tool['key']]) }}">
                        @csrf
                        <button type="submit" class="ds-btn ds-btn-danger ds-btn-md" data-testid="google-connector-disconnect-{{ $tool['key'] }}">Disconnect</button>
                    </form>
                @elseif ($tool['configured'] && $tool['allowed'])
                    <a href="{{ route('admin.google.connect', ['connector' => $tool['key']]) }}" class="ds-btn ds-btn-primary ds-btn-md" data-testid="google-connector-connect-{{ $tool['key'] }}">Connect</a>
                @else
                    <span class="text-sm" style="color:#94A3B8" data-testid="google-connector-unconfigured-{{ $tool['key'] }}">Connect unavailable</span>
                @endif
            </li>
        @endforeach
    </ul>
</div>
@endsection
