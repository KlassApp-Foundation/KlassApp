<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Report Card — {{ $learner->userprofile->firstname ?? 'Student' }} {{ $learner->userprofile->lastname ?? '' }}</title>
    <style>
        @page { margin: 0; }

        :root {
            --brand-blue: #1E6FD9;
            --brand-green: #22C55E;
            --brand-dark: #0F172A;
            --brand-light: #F8FAFC;
            --gray-100: #F1F5F9;
            --gray-200: #E2E8F0;
            --gray-400: #94A3B8;
            --gray-600: #475569;
            --gray-800: #1E293B;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Nunito Sans', 'DejaVu Sans', sans-serif;
            font-size: 10px;
            line-height: 1.35;
            color: var(--gray-800);
            background: #fff;
            padding: 0;
        }

        .page {
            width: 100%;
            padding: 12px 16px 8px;
            background: #fff;
        }

        /* ── Top accent bar ── */
        .accent-bar {
            height: 3px;
            background: var(--brand-blue);
            margin-bottom: 10px;
        }

        /* ── Header ── */
        .header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--gray-200);
        }
        .header-logo {
            width: 52px; height: 52px;
            background: var(--brand-blue);
            color: #fff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 800;
            font-family: 'Exo 2', 'DejaVu Sans', sans-serif;
            flex-shrink: 0;
        }
        .header-info { flex: 1; }
        .header-school {
            font-size: 16px;
            font-weight: 800;
            color: var(--brand-dark);
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 1px;
        }
        .header-meta {
            font-size: 8px;
            color: var(--gray-600);
        }
        .header-badge {
            text-align: right;
            flex-shrink: 0;
        }
        .badge-term {
            display: inline-block;
            background: var(--brand-blue);
            color: #fff;
            font-size: 8px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .badge-year {
            font-size: 8px;
            color: var(--gray-400);
            margin-top: 2px;
        }

        /* ── Info card ── */
        .info-card {
            display: flex;
            gap: 6px;
            margin-bottom: 6px;
            padding: 6px 8px;
            background: var(--brand-light);
            border: 1px solid var(--gray-200);
            border-left: 3px solid var(--brand-blue);
            border-radius: 4px;
        }
        .info-item {
            flex: 1;
            text-align: center;
        }
        .info-label {
            font-size: 7px;
            color: var(--gray-400);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1px;
        }
        .info-value {
            font-size: 10px;
            font-weight: 700;
            color: var(--brand-dark);
        }
        .info-value.large {
            font-size: 12px;
        }

        /* ── Tables ── */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        th {
            background: var(--gray-100);
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: var(--gray-600);
            padding: 5px 4px;
            border: 1px solid var(--gray-200);
            text-align: center;
        }
        td {
            font-size: 9px;
            padding: 3px 4px;
            border: 1px solid var(--gray-200);
            text-align: center;
        }
        td.subject-col { text-align: left; font-weight: 600; }
        td.empty { color: var(--gray-400); }

        .section-header {
            background: var(--brand-dark);
            color: #fff;
            font-size: 8px;
            font-weight: 700;
        }
        .eot-header {
            background: var(--brand-blue);
            color: #fff;
        }

        .total-row td, .total-row th {
            font-weight: 800;
            font-size: 9px;
            background: #EEF2FF;
            border-top: 2px solid var(--brand-blue);
        }

        /* ── Comments ── */
        .comments-box {
            padding: 6px 8px;
            border: 1px solid var(--gray-200);
            border-radius: 4px;
            margin-bottom: 5px;
            font-size: 9px;
            min-height: 28px;
            color: var(--gray-600);
        }
        .comments-label {
            font-size: 7px;
            color: var(--gray-400);
            text-transform: uppercase;
            margin-bottom: 2px;
            font-weight: 600;
        }

        /* ── Grading table ── */
        .grading-table th {
            font-size: 7px;
            padding: 3px 2px;
        }
        .grading-table td {
            font-size: 7px;
            padding: 2px 2px;
        }

        /* ── Footer ── */
        .footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 8px;
            padding-top: 4px;
            border-top: 1px solid var(--gray-200);
        }
        .footer-left { font-size: 7px; color: var(--gray-400); }
        .footer-right { font-size: 7px; color: var(--gray-400); text-align: right; }

        .watermark {
            position: absolute;
            top: 45%;
            left: 5%;
            width: 90%;
            text-align: center;
            opacity: 0.03;
            font-size: 48px;
            color: var(--brand-blue);
            font-weight: 900;
            pointer-events: none;
        }

        .sign-line {
            display: inline-block;
            width: 100px;
            border-bottom: 1px solid var(--gray-400);
            margin-left: 4px;
        }
    </style>
</head>
<body>

@if (!is_null($learner))

<div class="watermark">{{ $learner->school->name }}</div>

<div class="page">

    <div class="accent-bar"></div>

    {{-- ═══ HEADER ═══ --}}
    <div class="header">
        <div class="header-logo">{{ strtoupper(str($learner->school->name)->limit(2, "")) }}</div>
        <div class="header-info">
            <div class="header-school">{{ $learner->school->name }}</div>
            <div class="header-meta">{{ $school->address ?? 'School Address' }} &middot; {{ $school->phone ?? '' }}</div>
        </div>
        <div class="header-badge">
            <div class="badge-term">{{ $learner->marks->first()->exam->academicTerm->name ?? 'Term' }}</div>
            <div class="badge-year">{{ optional($learner->marks->first()->exam->academicTerm)->academicYear->name ?? '' }}</div>
        </div>
    </div>

    {{-- ═══ STUDENT INFO CARD ═══ --}}
    <div class="info-card">
        <div class="info-item">
            <div class="info-label">Student</div>
            <div class="info-value large">{{ $learner->userprofile->firstname }} {{ $learner->userprofile->lastname }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Class</div>
            <div class="info-value">{{ $class_name }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Aggregate</div>
            <div class="info-value">@if(!empty($isNursery)) — @else {{ $grade['agg'] ?? '—' }} @endif</div>
        </div>
        <div class="info-item">
            <div class="info-label">Position</div>
            <div class="info-value">@if(!empty($isNursery)) — @else {{ $myPos ?? '—' }} of {{ $totalLearners ?? '—' }} @endif</div>
        </div>
    </div>

    {{-- ═══ MAIN MARKS TABLE ═══ --}}
    @if(!empty($isNursery))
        <table>
            <thead>
                <tr>
                    <th>Domain</th>
                    <th>Rating</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach (['Literacy', 'Numeracy', 'Motor Skills', 'Social/Emotional'] as $domain)
                    @php $a = $nurseryAssessments->get($domain); @endphp
                    <tr>
                        <td class="subject-col">{{ $domain }}</td>
                        <td><strong>{{ $a?->rating ?? '—' }}</strong></td>
                        <td>{{ $a?->remarks ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width:22%">Subject</th>
                    <th style="width:6%">Out of</th>
                    @if ($midCount > 0)
                        <th class="section-header" colspan="{{ $midCount }}">Mid Term</th>
                    @endif
                    @if ($eotCount > 0)
                        <th class="section-header eot-header" colspan="{{ $eotCount }}">End of Term</th>
                    @endif
                    <th style="width:8%">Grade</th>
                </tr>
                <tr>
                    <th></th>
                    <th></th>
                    @foreach ($allExamColumns as $colExam)
                        @php
                            $label = $colExam->examType->code === 'MID'
                                ? strtoupper($colExam->scheduled_at->format('M'))
                                : 'EOT';
                        @endphp
                        <th class="{{ $colExam->examType->code !== 'MID' ? 'eot-header' : '' }}">{{ $label }}</th>
                    @endforeach
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($subjects as $subject)
                    @php $subjectMarks = $learner->marks->where('subject_id', $subject->id); @endphp
                    <tr>
                        <td class="subject-col">{{ $subject->name }}</td>
                        <td>100</td>
                        @foreach ($allExamColumns as $colExam)
                            @php $markForExam = $subjectMarks->firstWhere('exam_id', $colExam->id); @endphp
                            <td class="{{ $markForExam ? '' : 'empty' }}">
                                {{ $markForExam ? floor($markForExam->marks) : '—' }}
                            </td>
                        @endforeach
                        @php
                            $firstMark = $subjectMarks->first();
                            $subjectGrade = '-';
                            if ($firstMark && $firstMark->marks !== null) {
                                $g = $grading_system->first(fn($gs) => $gs->min_score <= $firstMark->marks && $gs->max_score >= $firstMark->marks);
                                $subjectGrade = $g ? $g->grade : '-';
                            }
                        @endphp
                        <td>{{ $subjectGrade }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td><strong>TOTAL</strong></td>
                    <td>{{ count($subjects) * 100 }}</td>
                    <td colspan="{{ $midCount + $eotCount }}"><strong>{{ $total }}</strong></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    @endif

    {{-- ═══ COMMENTS ═══ --}}
    <div style="display:flex; gap:6px;">
        <div style="flex:1;">
            <div class="comments-label">Class Teacher</div>
            <div class="comments-box">{{ $teacherComment ?? '—' }}</div>
        </div>
        <div style="flex:1;">
            <div class="comments-label">Head Teacher</div>
            <div class="comments-box"></div>
        </div>
    </div>

    {{-- ═══ GRADING SYSTEM ═══ --}}
    <table class="grading-table">
        <thead>
            <tr>
                <th>Grade</th>
                @foreach ($grading_system as $grade)
                    <th>{{ $grade->grade }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Range</strong></td>
                @foreach ($grading_system as $grade)
                    <td>{{ $grade->min_score }}–{{ $grade->max_score }}</td>
                @endforeach
            </tr>
        </tbody>
    </table>

    {{-- ═══ FOOTER ═══ --}}
    <div class="footer">
        <div class="footer-left">
            {{ $learner->school->name }} &middot; Generated {{ now()->format('d M Y') }}
        </div>
        <div class="footer-right">
            <span>Class Teacher <span class="sign-line"></span></span>
            &nbsp;&nbsp;
            <span>Head Teacher <span class="sign-line"></span></span>
        </div>
    </div>

</div>

@else
    <h3 style="text-align:center; padding:40px;">No records found</h3>
@endif

</body>
</html>
