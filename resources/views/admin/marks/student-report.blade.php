<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Report Card — {{ $learner->userprofile->firstname ?? 'Student' }} {{ $learner->userprofile->lastname ?? '' }}</title>
    <style>
        @page { margin: 0; }

        * { margin: 0; padding: 0; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            line-height: 1.35;
            color: #1E293B;
            background: #fff;
            padding: 0;
        }

        .page {
            width: 100%;
            padding: 12px 16px 8px;
            background: #fff;
        }

        .accent-bar {
            height: 3px;
            background: #1E6FD9;
            margin-bottom: 10px;
        }

        /* ── Header table ── */
        .header-table { width: 100%; margin-bottom: 8px; border-collapse: collapse; }
        .header-table td { padding: 0; vertical-align: middle; border: none; }
        .header-logo {
            width: 52px; height: 52px;
            background: #1E6FD9;
            color: #fff;
            text-align: center;
            line-height: 52px;
            font-size: 18px;
            font-weight: 800;
        }
        .header-logo-cell { width: 60px; }
        .header-info { padding-left: 12px; }
        .header-school {
            font-size: 16px;
            font-weight: 800;
            color: #0F172A;
            text-transform: uppercase;
            margin-bottom: 1px;
        }
        .header-meta { font-size: 8px; color: #475569; }
        .header-badge-cell { width: 100px; text-align: right; }
        .badge-term {
            display: inline-block;
            background: #1E6FD9;
            color: #fff;
            font-size: 8px;
            font-weight: 700;
            padding: 3px 10px;
            text-transform: uppercase;
        }
        .badge-year { display: block; font-size: 8px; color: #94A3B8; margin-top: 2px; }

        .header-divider { border-bottom: 1px solid #E2E8F0; margin-bottom: 8px; }

        /* ── Info card (table-based) ── */
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; border: 1px solid #E2E8F0; }
        .info-table td { text-align: center; padding: 6px 8px; font-size: 9px; border: none; }
        .info-table td:first-child { border-left: 3px solid #1E6FD9; }
        .info-label { font-size: 7px; color: #94A3B8; text-transform: uppercase; margin-bottom: 1px; }
        .info-value { font-size: 10px; font-weight: 700; color: #0F172A; }
        .info-value-lg { font-size: 12px; font-weight: 700; color: #0F172A; }

        /* ── Tables ── */
        .marks-table { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
        .marks-table th {
            background: #F1F5F9;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            color: #475569;
            padding: 5px 4px;
            border: 1px solid #E2E8F0;
            text-align: center;
        }
        .marks-table td {
            font-size: 9px;
            padding: 3px 4px;
            border: 1px solid #E2E8F0;
            text-align: center;
        }
        .marks-table td.left { text-align: left; }
        .marks-table td.empty { color: #94A3B8; }
        .marks-table td.strong { font-weight: 600; }

        .section-mid { background: #0F172A; color: #fff; font-size: 8px; font-weight: 700; }
        .section-eot { background: #1E6FD9; color: #fff; font-size: 8px; font-weight: 700; }

        .total-row td { font-weight: 800; font-size: 9px; background: #EEF2FF; border-top: 2px solid #1E6FD9; }

        /* ── Comments (table-based) ── */
        .comments-table { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
        .comments-table td { width: 50%; padding: 0 3px; vertical-align: top; border: none; }
        .comments-label { font-size: 7px; color: #94A3B8; text-transform: uppercase; margin-bottom: 2px; font-weight: 600; }
        .comments-box {
            padding: 6px 8px;
            border: 1px solid #E2E8F0;
            font-size: 9px;
            min-height: 28px;
            color: #475569;
        }

        /* ── Grading table ── */
        .grades-table { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
        .grades-table th { font-size: 7px; padding: 3px 2px; background: #F1F5F9; border: 1px solid #E2E8F0; text-transform: uppercase; font-weight: 700; color: #475569; text-align: center; }
        .grades-table td { font-size: 7px; padding: 2px 2px; border: 1px solid #E2E8F0; text-align: center; }
        .grades-table td.strong { font-weight: 600; }
        .grades-heading { margin: 8px 0 4px; font-size: 10px; font-weight: 600; color: #0F172A; }

        /* ── Footer ── */
        .footer-table { width: 100%; border-collapse: collapse; margin-top: 8px; padding-top: 4px; border-top: 1px solid #E2E8F0; }
        .footer-table td { font-size: 7px; color: #94A3B8; padding: 2px 0; border: none; }
        .footer-table td.right { text-align: right; }
        .sign-line { border-bottom: 1px solid #94A3B8; display: inline-block; width: 80px; margin-left: 4px; }

        .no-records { text-align: center; padding: 40px; }
    </style>
</head>
<body>

@if (!is_null($learner))

<div class="page">

    <div class="accent-bar"></div>

    {{-- ═══ HEADER ═══ --}}
    <table class="header-table">
        <tr>
            <td class="header-logo-cell">
                <div class="header-logo">{{ strtoupper(mb_substr($learner->school->name, 0, 2)) }}</div>
            </td>
            <td class="header-info">
                <div class="header-school">{{ $learner->school->name }}</div>
                <div class="header-meta">{{ $school->address ?? 'School Address' }} &middot; {{ $school->phone ?? '' }}</div>
            </td>
            <td class="header-badge-cell">
                <span class="badge-term">{{ $learner->marks->first()->exam->academicTerm->name ?? 'Term' }}</span>
                <br><span class="badge-year">{{ optional($learner->marks->first()->exam->academicTerm)->academicYear->name ?? '' }}</span>
            </td>
        </tr>
    </table>
    <div class="header-divider"></div>

    {{-- ═══ STUDENT INFO CARD ═══ --}}
    <table class="info-table">
        <tr>
            <td>
                <div class="info-label">Student</div>
                <div class="info-value-lg">{{ $learner->userprofile->firstname }} {{ $learner->userprofile->lastname }}</div>
            </td>
            <td>
                <div class="info-label">Class</div>
                <div class="info-value">{{ $class_name }}</div>
            </td>
            <td>
                <div class="info-label">Aggregate</div>
                <div class="info-value">@if(!empty($isNursery)) &mdash; @else {{ $grade['agg'] ?? '&mdash;' }} @endif</div>
            </td>
            <td>
                <div class="info-label">Position</div>
                <div class="info-value">@if(!empty($isNursery)) &mdash; @else {{ $myPos ?? '&mdash;' }} of {{ $totalLearners ?? '&mdash;' }} @endif</div>
            </td>
        </tr>
    </table>

    {{-- ═══ MAIN MARKS TABLE ═══ --}}
    @if(!empty($isNursery))
        <table class="marks-table">
            <tr><th>Domain</th><th>Rating</th><th>Remarks</th></tr>
            @foreach (['Literacy', 'Numeracy', 'Motor Skills', 'Social/Emotional'] as $domain)
                @php $a = $nurseryAssessments->get($domain); @endphp
                <tr>
                    <td class="left strong">{{ $domain }}</td>
                    <td><strong>{{ $a?->rating ?? '&mdash;' }}</strong></td>
                    <td>{{ $a?->remarks ?? '&mdash;' }}</td>
                </tr>
            @endforeach
        </table>
    @else
        <table class="marks-table">
            <tr>
                <th style="width:22%">Subject</th>
                <th style="width:6%">Out of</th>
                @if ($midCount > 0) <th class="section-mid" colspan="{{ $midCount }}">MID TERM</th> @endif
                @if ($eotCount > 0) <th class="section-eot" colspan="{{ $eotCount }}">END OF TERM</th> @endif
                <th style="width:8%">Grade</th>
            </tr>
            <tr>
                <th></th><th></th>
                @foreach ($allExamColumns as $colExam)
                    @php $label = $colExam->examType->code === 'MID' ? strtoupper($colExam->scheduled_at->format('M')) : 'EOT'; @endphp
                    <th class="{{ $colExam->examType->code !== 'MID' ? 'section-eot' : '' }}">{{ $label }}</th>
                @endforeach
                <th></th>
            </tr>
            @foreach ($subjects as $subject)
                @php $subjectMarks = $learner->marks->where('subject_id', $subject->id); @endphp
                <tr>
                    <td class="left strong">{{ $subject->name }}</td>
                    <td>100</td>
                    @foreach ($allExamColumns as $colExam)
                        @php $markForExam = $subjectMarks->firstWhere('exam_id', $colExam->id); @endphp
                        <td class="{{ $markForExam ? '' : 'empty' }}">{{ $markForExam ? floor($markForExam->marks) : '&mdash;' }}</td>
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
            <tr class="total-row">
                <td><strong>TOTAL</strong></td>
                <td>{{ isset($examinedSubjectCount) ? $examinedSubjectCount * 100 : count($subjects) * 100 }}</td>
                <td colspan="{{ $midCount + $eotCount }}"><strong>{{ $total }}</strong></td>
                <td></td>
            </tr>
        </table>
    @endif

    {{-- ═══ COMMENTS ═══ --}}
    <table class="comments-table">
        <tr>
            <td>
                <div class="comments-label">Class Teacher</div>
                <div class="comments-box">{{ $teacherComment ?? '&mdash;' }}</div>
            </td>
            <td>
                <div class="comments-label">Head Teacher</div>
                <div class="comments-box"></div>
            </td>
        </tr>
    </table>

    {{-- ═══ GRADING SYSTEM ═══ --}}
    <div class="grades-heading">School Grading System</div>
    <table class="grades-table">
        <tr>
            <th>Grade</th>
            @foreach ($grading_system as $grade) <th>{{ $grade->grade }}</th> @endforeach
        </tr>
        <tr>
            <td class="strong">Range</td>
            @foreach ($grading_system as $grade) <td>{{ $grade->min_score }}&ndash;{{ $grade->max_score }}</td> @endforeach
        </tr>
    </table>

    {{-- ═══ FOOTER ═══ --}}
    <table class="footer-table">
        <tr>
            <td>{{ $learner->school->name }} &middot; Generated {{ now()->format('d M Y') }}</td>
            <td class="right">
                Class Teacher <span class="sign-line"></span>
                &nbsp;&nbsp;
                Head Teacher <span class="sign-line"></span>
            </td>
        </tr>
    </table>

</div>

@else
    <div class="no-records">No records found</div>
@endif

</body>
</html>
