<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Report Card — {{ $learner->userprofile->firstname ?? 'Student' }} {{ $learner->userprofile->lastname ?? '' }}</title>
    <style>
        @page { margin: 0; background: #FFFBF2; }

        * { margin: 0; padding: 0; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            font-weight: 600;
            line-height: 1.4;
            color: #4A3624;
            background: #FFFBF2;
        }

        .page { padding: 16px 22px; background: #FFFBF2; }

        .watermark {
            position: absolute;
            top: 330px;
            left: 50%;
            margin-left: -190px;
            width: 380px;
            opacity: 0.04;
        }

        /* ── Header card ── */
        .header-card {
            background: #fff;
            padding: 16px 22px;
            border-bottom: 1px solid #F0DFC0;
        }
        .header-top-row { text-align: right; margin-bottom: 8px; }
        .header-centered { text-align: center; }
        .h-school { font-size: 22px; font-weight: 800; color: #7C3A11; margin-top: 10px; }
        .h-meta { font-size: 12px; font-weight: 700; color: #A88865; margin-top: 2px; }
        .term-chip {
            display: inline-block;
            background: #EBF5E4;
            color: #3F6B1F;
            font-size: 9px;
            font-weight: 700;
            padding: 6px 16px;
            border-radius: 20px;
        }
        .year-chip { display: block; font-size: 7.5px; color: #A88865; margin-top: 3px; }

        .ribbon {
            margin-top: 14px;
            background: #EBF5E4;
            border-radius: 10px;
            padding: 9px 24px;
            text-align: center;
            font-size: 12px;
            font-weight: 700;
            color: #3F6B1F;
            letter-spacing: 0.5px;
        }

        /* ── Info pills ── */
        .pills-table { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin: 10px 0 0; }
        .pill { border-radius: 12px; padding: 9px 12px; }
        .pill-student { background: #EAF2FB; }
        .pill-class { background: #FCEEDD; }
        .pill-agg { background: #F3E8FB; }
        .pill-label { font-size: 7px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
        .pill-student .pill-label { color: #3B6FA8; }
        .pill-class .pill-label { color: #B5651D; }
        .pill-agg .pill-label { color: #7C3AAF; }
        .pill-value { font-size: 13px; font-weight: 800; color: #4A3624; }

        /* ── Section headings ── */
        .sec-heading { margin: 14px 0 6px; font-size: 11px; font-weight: 800; color: #7C3A11; }
        .dot { display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: #D97706; margin-right: 6px; }
        .sec-heading-eot { color: #3F6B1F; }
        .sec-heading-eot .dot { background: #3F6B1F; }

        /* ── Tables ── */
        .card-table { width: 100%; border-collapse: collapse; margin-bottom: 4px; border: 1px solid #F0DFC0; border-radius: 10px; overflow: hidden; }
        .card-table th {
            background: #D97706;
            color: #fff;
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 6px 4px;
            border: none;
            text-align: center;
        }
        .card-table th.eot-th { background: #3F6B1F; }
        .card-table td {
            font-size: 10px;
            padding: 5px 4px;
            border: none;
            border-bottom: 1px solid #F5EBD8;
            text-align: center;
            color: #4A3624;
        }
        .card-table tr:nth-child(even) td { background: #FFFCF5; }
        .card-table td.left { text-align: left; font-weight: 700; color: #7C3A11; }
        .card-table td.empty { color: #D8C4A0; }

        .chip {
            display: inline-block;
            border-radius: 8px;
            padding: 1px 7px;
            font-weight: 700;
            font-size: 8px;
            color: #fff;
        }
        .chip-good { background: #4D9A3A; }
        .chip-mid { background: #D97706; }
        .chip-low { background: #C2542A; }
        .chip-flat { background: #B9A784; }

        .total-row td {
            font-weight: 800;
            font-size: 9.5px;
            background: #FCEEDD !important;
            border-top: 2px solid #D97706 !important;
            border-bottom: none !important;
            color: #7C3A11;
        }

        .pos-card { margin: 5px 0 12px; background: #FCEEDD; border-radius: 12px; padding: 9px 14px; }
        .pos-card-table { width: 100%; border-collapse: collapse; }
        .pos-card-table td { border: none; padding: 0; font-size: 10.5px; font-weight: 800; color: #7C3A11; }
        .pos-card-table td.right { text-align: right; }

        /* ── Comments ── */
        .comments-table { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin: 10px 0 12px; }
        .comments-label { font-size: 7.5px; color: #A88865; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; font-weight: 700; }
        .comments-box {
            padding: 9px 11px;
            background: #fff;
            border: 1px solid #F0DFC0;
            border-radius: 10px;
            font-size: 10px;
            min-height: 30px;
            color: #6B543C;
        }

        /* ── Grading system ── */
        .grades-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; border: 1px solid #F0DFC0; border-radius: 8px; overflow: hidden; }
        .grades-table th { font-size: 7px; padding: 4px 2px; background: #FCEEDD; color: #7C3A11; border: none; border-bottom: 1px solid #F0DFC0; text-transform: uppercase; font-weight: 700; text-align: center; }
        .grades-table td { font-size: 7px; padding: 4px 2px; border: none; text-align: center; color: #6B543C; }

        /* ── Next term / signatures / footer ── */
        .next-term { text-align: center; font-size: 10px; font-weight: 800; color: #3F6B1F; margin: 6px 0 10px; }
        .sig-table { width: 100%; border-collapse: collapse; margin: 4px 0 10px; }
        .sig-table td { width: 50%; text-align: center; vertical-align: bottom; padding: 0 12px; }
        .sig-caption { font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.5px; color: #A88865; font-weight: 700; }
        .sig-line { border-bottom: 1px solid #7C3A11; height: 22px; }
        .motto-row { text-align: center; margin-top: 8px; font-size: 10px; font-weight: 800; color: #D97706; }
        .footer-band {
            text-align: center;
            padding: 8px 22px 14px;
            border-top: 1px solid #F0DFC0;
            font-family: 'DejaVu Serif', serif;
            font-size: 9px;
            color: #6B543C;
            background: #FFFBF2;
        }

        .no-records { text-align: center; padding: 40px; }
    </style>
</head>
<body>

@if (!is_null($learner))

@php
    $chipClass = function ($gradeStr) {
        if (!preg_match('/(\d+)/', (string) $gradeStr, $m)) return 'chip-flat';
        $n = (int) $m[1];
        if ($n <= 3) return 'chip-good';
        if ($n <= 6) return 'chip-mid';
        return 'chip-low';
    };
    $termRaw = $learner->marks->first()?->exam?->academicTerm?->name ?? '';
    if (preg_match('/^(.*?)\s*(\d+)\s*$/', trim($termRaw), $m)) {
        $roman = ['1' => 'I', '2' => 'II', '3' => 'III', '4' => 'IV', '5' => 'V', '6' => 'VI'];
        $termName = strtoupper(trim($m[1])) . ' ' . ($roman[$m[2]] ?? $m[2]);
    } else {
        $termName = strtoupper($termRaw);
    }
@endphp

<div class="header-card">

    {{-- ═══ HEADER (centered letterhead, edge-to-edge) ═══ --}}
    <div class="header-top-row">
        <span class="term-chip">{{ $termName }}</span>
        <span class="year-chip">{{ optional($learner->marks->first()->exam->academicTerm)->academicYear->name ?? '' }}</span>
    </div>
    <div class="header-centered">
        @if (!empty($logoPath))
        <img src="{{ $logoPath }}" style="width: 76px; height: auto;" alt="Logo">
        @endif
        <div class="h-school">{{ $learner->school->name }}</div>
        <div class="h-meta">(Nursery And Primary, Day And Boarding)</div>
        <div class="h-meta">P.O Box 283 - Kabale - UGA</div>
        <div class="h-meta">Tel: +256782255758 / +256784119149 / +256704301646</div>
    </div>
    <div class="ribbon">PROGRESSIVE REPORT</div>
</div>

<div class="page">

    @if (!empty($logoPath))
    <img class="watermark" src="{{ $logoPath }}" alt="">
    @endif

    {{-- ═══ INFO PILLS ═══ --}}
    <table class="pills-table">
        <tr>
            <td style="width:50%;">
                <div class="pill pill-student">
                    <div class="pill-label">Student</div>
                    <div class="pill-value">{{ $learner->userprofile->firstname }} {{ $learner->userprofile->lastname }}</div>
                </div>
            </td>
            <td style="width:25%;">
                <div class="pill pill-class">
                    <div class="pill-label">Class</div>
                    <div class="pill-value">{{ $class_name }}</div>
                </div>
            </td>
            <td style="width:25%;">
                <div class="pill pill-agg">
                    <div class="pill-label">Aggregate</div>
                    <div class="pill-value">@if(!empty($isNursery)) - @else {{ $grade['agg'] ?? '-' }} @endif</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- ═══ MAIN MARKS ═══ --}}
    @if(!empty($isNursery))
        <table class="card-table">
            <tr><th>Domain</th><th>Rating</th><th>Remarks</th></tr>
            @foreach (['Literacy', 'Numeracy', 'Motor Skills', 'Social/Emotional'] as $domain)
                @php $a = $nurseryAssessments->get($domain); @endphp
                <tr>
                    <td class="left">{{ $domain }}</td>
                    <td><strong>{{ $a?->rating ?? '-' }}</strong></td>
                    <td>{{ $a?->remarks ?? '-' }}</td>
                </tr>
            @endforeach
        </table>
    @else
        @if ($midExams->isNotEmpty())
        <div class="sec-heading"><span class="dot"></span>MONTHLY RESULTS — MID TERM</div>
        <table class="card-table">
            <tr>
                <th style="width:12%">Month Of</th>
                @foreach ($subjects as $subject)
                    @if ($learner->marks->where('subject_id', $subject->id)->isNotEmpty())
                        <th>{{ $subject->name }}</th>
                        <th>AGG</th>
                    @endif
                @endforeach
            </tr>
            @foreach ($midExams as $midExam)
                @php $monthLabel = strtoupper($midExam->scheduled_at->format('F')); @endphp
                <tr>
                    <td class="left">{{ $monthLabel }}</td>
                    @foreach ($subjects as $subject)
                        @if ($learner->marks->where('subject_id', $subject->id)->isNotEmpty())
                            @php
                                $midMark = $learner->marks->firstWhere('exam_id', $midExam->id) && $learner->marks->firstWhere('exam_id', $midExam->id)->subject_id == $subject->id
                                    ? $learner->marks->firstWhere('exam_id', $midExam->id) : $learner->marks->where('subject_id', $subject->id)->firstWhere('exam_id', $midExam->id);
                                $midGrade = '-';
                                if ($midMark && $midMark->marks !== null) {
                                    $g = $grading_system->first(fn($gs) => $gs->min_score <= $midMark->marks && $gs->max_score >= $midMark->marks);
                                    $midGrade = $g ? 'D' . $g->points : '-';
                                }
                            @endphp
                            <td>{{ $midMark ? floor($midMark->marks) : '-' }}</td>
                            <td>@if($midGrade !== '-')<span class="chip {{ $chipClass($midGrade) }}">{{ $midGrade }}</span>@else - @endif</td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </table>
        @endif

        @if ($eotExams->isNotEmpty())
        <div class="sec-heading sec-heading-eot"><span class="dot"></span>END OF TERM EXAMINATION</div>
        <table class="card-table">
            <tr>
                <th class="eot-th">Subject</th>
                <th class="eot-th" style="width:8%">Full Mark</th>
                <th class="eot-th" style="width:8%">Mark Gained</th>
                <th class="eot-th" style="width:8%">AGG</th>
                <th class="eot-th">Comment</th>
                <th class="eot-th" style="width:10%">TR Initials</th>
            </tr>
            @foreach ($subjects as $subject)
                @php $subjectMarks = $learner->marks->where('subject_id', $subject->id); @endphp
                @php $eotMark = $subjectMarks->firstWhere('exam_id', $eotExams->first()->id); @endphp
                @if (!$eotMark) @continue @endif
                @php
                    $hasEotMarks = $eotMark->marks !== null;
                    $eotGrade = '-';
                    $eotComment = '-';
                    if ($hasEotMarks) {
                        $g = $grading_system->first(fn($gs) => $gs->min_score <= $eotMark->marks && $gs->max_score >= $eotMark->marks);
                        $eotGrade = $g ? 'D' . $g->points : '-';
                        $eotComment = $g ? $g->remark : '-';
                    }
                    $teacherLink = \App\Models\Teacherlink::where('standardLink_id', $stdLink->id)
                        ->where('subject_id', $subject->id)->first();
                    $teacherName = '-';
                    if ($teacherLink && $teacherLink->teacher) {
                        $fn = $teacherLink->teacher->userprofile->firstname ?? '';
                        $ln = $teacherLink->teacher->userprofile->lastname ?? '';
                        if ($fn) {
                            $teacherName = $ln ? ucwords(strtolower($fn)) . ' ' . ucwords(strtolower($ln)) : ucwords(strtolower($fn));
                        }
                    }
                @endphp
                <tr>
                    <td class="left">{{ $subject->name }}</td>
                    <td>100</td>
                    <td>{{ $eotMark ? floor($eotMark->marks) : '-' }}</td>
                    <td>@if($eotGrade !== '-')<span class="chip {{ $chipClass($eotGrade) }}">{{ $eotGrade }}</span>@else - @endif</td>
                    <td>{{ $eotComment }}</td>
                    <td>{{ $teacherName }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td class="left"><strong>TOTAL</strong></td>
                <td>{{ isset($examinedSubjectCount) ? $examinedSubjectCount * 100 : count($subjects) * 100 }}</td>
                <td><strong>{{ $total }}</strong></td>
                <td><strong>{{ $grade['agg'] ?? '-' }}</strong></td>
                <td colspan="2"></td>
            </tr>
        </table>

        @if (!empty($myPos) && !$isNursery)
        <div class="pos-card">
            <table class="pos-card-table">
                <tr>
                    <td>POSITION</td>
                    <td class="right">{{ $myPos ?? '-' }}&nbsp;&nbsp;of&nbsp;&nbsp;{{ $totalLearners ?? '-' }}</td>
                </tr>
            </table>
        </div>
        @endif
        @endif
    @endif

    {{-- ═══ COMMENTS ═══ --}}
    <table class="comments-table">
        <tr>
            <td style="width:50%;">
                <div class="comments-label">Class Teacher</div>
                <div class="comments-box">{{ $teacherComment ?? '-' }}</div>
            </td>
            <td style="width:50%;">
                <div class="comments-label">Head Teacher</div>
                <div class="comments-box">{{ $headTeacherComment ?? '-' }}</div>
            </td>
        </tr>
    </table>

    @if ($nextTerm)
    <div class="next-term">Next Term Begins: {{ $nextTerm->starts_on->format('d/m/Y') }}</div>
    @endif

    {{-- ═══ SIGNATURES ═══ --}}
    <table class="sig-table">
        <tr>
            <td><div class="sig-caption">Class Teacher</div><div class="sig-line"></div></td>
            <td><div class="sig-caption">Head Teacher</div><div class="sig-line"></div></td>
        </tr>
    </table>

    <div class="motto-row">HARD WORK PAYS</div>

</div>

<div class="footer-band">
    Kabale Junior School, UNEB Center No. {{ $school->uneb_center_number }} Tel: +256782255758 / +256784119149 / +256704301646
</div>

@else
    <div class="no-records">No records found</div>
@endif

</body>
</html>
