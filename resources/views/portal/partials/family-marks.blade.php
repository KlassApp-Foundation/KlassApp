{{-- SPDX-License-Identifier: MIT --}}
@php
    $groups = $bundle['grades']['exam_groups'] ?? [];
    $latest = $groups === [] ? null : $groups[array_key_last($groups)];
    $earlier = $groups === [] ? [] : array_slice($groups, 0, -1);
    $kls = trim((string) ($bundle['grades']['registration_number'] ?? ''));
    $shown = fn (array $subject) => $subject['display'] ?? (string) (int) round((float) ($subject['score'] ?? 0));
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
                {{-- design review: the KLS number is required wherever a learner number is shown. ParentMarks.dc.html does not draw it. --}}
                @if($kls !== '')
                    <p data-testid="parent-marks-kls">{{ $kls }}</p>
                @endif
            </div>
            <a class="ds-btn ds-btn-outline ka-hit ka-report-download" href="{{ $links['report'] }}" data-testid="family-report-download">Download latest report card</a>
        </div>
        @include('portal.partials.results-year-filter', ['grades' => $bundle['grades']])
        @include('portal.partials.performance-chart', ['grades' => $bundle['grades']])
        <section class="family-table-card" data-testid="parent-marks-card">
            <h2>{{ $latest['exam_type'] }}</h2>
            <div class="ka-sheet-table">
                <table>
                    <thead>
                        <tr><th>Subject</th><th>Mark / 100</th></tr>
                    </thead>
                    <tbody>
                        @foreach($latest['subjects'] as $subject)
                            <tr>
                                <th scope="row">{{ $subject['name'] }}</th>
                                <td>{{ $shown($subject) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="ka-phone-cards" data-testid="parent-marks-phone">
                @if($kls !== '')
                    <p data-testid="parent-marks-phone-kls">{{ $kls }}</p>
                @endif
                @foreach($latest['subjects'] as $subject)
                    <div class="ka-mark-row"><span>{{ $subject['name'] }}</span><strong>{{ $shown($subject) }}</strong></div>
                @endforeach
            </div>
        </section>
        @if($earlier !== [])
            <details class="family-earlier">
                <summary>Earlier released results</summary>
                @foreach(array_reverse($earlier) as $group)
                    <h3>{{ $group['exam_type'] }}</h3>
                    <ul>
                        @foreach($group['subjects'] as $subject)
                            <li>{{ $subject['name'] }} <strong>{{ $shown($subject) }}</strong></li>
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
