{{-- SPDX-License-Identifier: MIT --}}
@php
    $groups = $bundle['grades']['exam_groups'] ?? [];
    $latest = $groups === [] ? null : $groups[array_key_last($groups)];
    $earlier = $groups === [] ? [] : array_slice($groups, 0, -1);
    $released = array_map(fn ($group) => [
        'label' => $group['exam_type'],
        'average' => $group['average'],
        'scores' => array_map(fn ($subject) => [$subject['name'], $subject['score']], $group['subjects']),
    ], $groups);
@endphp
<div data-released="{{ json_encode($released) }}">
    @if($latest)
        <div class="family-page-head">
            <div>
                <h1>Marks</h1>
                <p>Released results only · average across subjects</p>
            </div>
            <a class="ds-btn ds-btn-outline" href="{{ $links['report'] }}" data-testid="family-report-download">Download latest report card</a>
        </div>
        @include('portal.partials.results-year-filter', ['grades' => $bundle['grades']])
        @include('portal.partials.performance-chart', ['grades' => $bundle['grades']])
        <section class="family-table-card">
            <h2>{{ $latest['exam_type'] }}</h2>
            <table>
                <thead>
                    <tr><th>Subject</th><th>Mark / 100</th></tr>
                </thead>
                <tbody>
                    @foreach($latest['subjects'] as $subject)
                        <tr>
                            <th scope="row">{{ $subject['name'] }}</th>
                            <td>{{ (int) round((float) $subject['score']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
        @if($earlier !== [])
            <details class="family-earlier">
                <summary>Earlier released results</summary>
                @foreach(array_reverse($earlier) as $group)
                    <h3>{{ $group['exam_type'] }}</h3>
                    <ul>
                        @foreach($group['subjects'] as $subject)
                            <li>{{ $subject['name'] }} <strong>{{ (int) round((float) $subject['score']) }}</strong></li>
                        @endforeach
                    </ul>
                @endforeach
            </details>
        @endif
        @if(!empty($bundle['headline']['has_unreleased']))
            <p class="family-card__note">More results appear here once the school releases them.</p>
        @endif
    @endif
</div>
