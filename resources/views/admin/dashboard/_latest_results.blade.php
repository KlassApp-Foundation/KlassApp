{{-- Latest results: the most recently released class. Totals, aggregates and divisions come from ResultsService (DashboardV2DataService::latestResults). --}}
@php $latest = $v2['latestResults'] ?? null; @endphp
<section class="dv2-card dv2-surface dv2-latest" data-testid="dashboard-latest-results" aria-label="Latest results">
    @if($latest)
        <div class="act-head">
            <div>
                <h2>Latest results · {{ $latest['class'] }}</h2>
                <small data-testid="dashboard-latest-results-sub">{{ $latest['exam'] }} · released {{ $latest['released'] }}</small>
            </div>
            <a class="dv2-link-action" href="{{ $latest['url'] }}" data-testid="dashboard-latest-results-open">Open release sheet</a>
        </div>
        <div class="dv2-table-scroll">
            <table class="dv2-latest-table">
                <thead><tr><th scope="col">Learner</th><th scope="col">Total</th><th scope="col">Agg</th><th scope="col">Div</th></tr></thead>
                <tbody>
                @foreach($latest['rows'] as $row)
                    <tr data-testid="dashboard-latest-results-row">
                        <th scope="row">{{ $row['name'] }}</th>
                        <td data-testid="latest-total">{{ $row['total'] }}</td>
                        <td data-testid="latest-agg">{{ $row['aggregate'] }}</td>
                        <td data-testid="latest-div">{{ $row['division'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @else
        <h2>Latest results</h2>
        <div class="dv2-empty-in" data-testid="dashboard-latest-results-empty">
            <p>No results released yet. Release a class to see its results here.</p>
            <a class="dv2-btn dv2-btn--soft" href="{{ route('admin.marks.releases.index') }}">Marks Release</a>
        </div>
    @endif
</section>
