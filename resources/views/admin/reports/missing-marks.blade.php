<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Missing Marks Report</title>
    <style>
        @page { margin: 10mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; line-height: 1.4; color: #1E293B; }
        h1 { font-size: 16px; color: #1E6FD9; margin-bottom: 4px; }
        h2 { font-size: 13px; color: #1E6FD9; margin: 12px 0 4px; border-bottom: 1px solid #E2E8F0; padding-bottom: 2px; }
        .meta { font-size: 8px; color: #94A3B8; margin-bottom: 12px; }
        .class-count { font-size: 9px; color: #DC2626; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th { background: #F1F5F9; font-size: 8px; font-weight: 700; text-transform: uppercase; color: #475569; padding: 4px 6px; border: 1px solid #E2E8F0; text-align: left; }
        td { font-size: 9px; padding: 2px 6px; border: 1px solid #E2E8F0; }
        .empty { text-align: center; padding: 20px; color: #22C55E; font-size: 12px; }
        .footer { margin-top: 16px; font-size: 7px; color: #94A3B8; border-top: 1px solid #E2E8F0; padding-top: 4px; }
    </style>
</head>
<body>
    <h1>Missing Marks Report</h1>
    <div class="meta">{{ $school->name }} &middot; Generated {{ now()->format('d M Y') }} &middot; Students with zero EOT marks</div>

    @php
        $nurseryStudents = \App\Models\User::where('school_id', $school->id)
            ->where('usergroup_id', 6)
            ->where('status', 'active')
            ->whereHas('studentAcademic.standardLink.standard', fn($q) => $q->where('name', 'nursery'))
            ->count();
    @endphp
    @if ($nurseryStudents > 0)
    <div style="background:#FEF2F2;border:1px solid #FECACA;padding:8px 12px;margin-bottom:12px;border-radius:4px;">
        <div style="font-weight:700;color:#DC2626;font-size:11px;margin-bottom:2px;">NURSERY: NO EXAM DATA RECEIVED</div>
        <div style="font-size:9px;color:#7F1D1D;">{{ $nurseryStudents }} nursery students are enrolled but no marks exist yet for any nursery student. This is a separate outstanding item — not "some students missing," but "no exam data received for nursery at all."</div>
    </div>
    @endif

    @php $total = 0; @endphp
    @forelse ($missing as $group)
        @php $total += $group['count']; @endphp
        <h2>{{ $group['class'] }} <span style="font-weight:400;font-size:10px;color:#94A3B8;">({{ $group['standard'] }})</span></h2>
        <div class="class-count">{{ $group['count'] }} students missing marks</div>
        <table>
            <tr><th style="width:5%">#</th><th>Student Name</th></tr>
            @foreach ($group['students'] as $i => $student)
                <tr><td>{{ $i + 1 }}</td><td>{{ $student->name }}</td></tr>
            @endforeach
        </table>
    @empty
        <div class="empty">All students have marks. No missing students found.</div>
    @endforelse

    <div class="footer">
        Total: {{ $total }} students missing marks across {{ count($missing) }} classes &middot; {{ $school->name }}
    </div>
</body>
</html>
