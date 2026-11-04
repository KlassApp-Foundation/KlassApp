{{-- SPDX-License-Identifier: MIT --}}
{{-- Current progress report for one student: APPROVED marks that are not released yet. PDF (dompdf). --}}
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Progress report, {{ $page['name'] }}</title>
<style>
    body { font-family: DejaVu Sans, sans-serif; color: #1E293B; font-size: 12px; }
    h1 { font-size: 20px; margin: 0 0 4px; }
    .muted { color: #475569; }
    .banner { margin: 14px 0; padding: 8px 12px; border: 1px solid #B45309; background: #FFFBEB; color: #78350F; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { text-align: left; padding: 7px 8px; border-bottom: 1px solid #CBD5E1; }
    th { font-size: 10px; text-transform: uppercase; color: #475569; border-bottom: 2px solid #1E6FD9; }
    td.num, th.num { text-align: right; }
</style>
</head>
<body>
    <p class="muted" style="margin:0;">{{ $school?->name }}</p>
    <h1>{{ $page['name'] }}</h1>
    <p class="muted" style="margin:0;">{{ $page['class_name'] ?: 'No class' }}@if($page['kls']) &middot; {{ $page['kls'] }}@endif @if($term) &middot; {{ $term->name }}@endif</p>

    <div class="banner">Progress report, not released. Built from approved marks only. It is not an official report card.</div>

    <table>
        <thead><tr><th>Subject</th><th>Exam</th><th class="num">Mark</th><th>Grade</th></tr></thead>
        <tbody>
        @foreach($rows as $mark)
            <tr>
                <td>{{ ucfirst(mb_strtolower((string) $mark->exam?->subject?->name)) }}</td>
                <td>{{ $mark->exam?->examType?->name }}</td>
                <td class="num">{{ rtrim(rtrim(number_format((float) $mark->marks, 2, '.', ''), '0'), '.') }}</td>
                <td>{{ $mark->grade ?: '-' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <p><strong>Average {{ $average }}</strong> across {{ $rows->count() }} {{ $rows->count() === 1 ? 'subject' : 'subjects' }}.</p>
</body>
</html>
