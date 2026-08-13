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
            font-size: 11px;
            font-weight: 600;
            line-height: 1.5;
            color: #334155;
            background: #fff;
        }

        .page { width: 100%; padding: 0 0 16px; }

        /* ── Top band ── */
        .band { background: #1E6FD9; padding: 14px 24px; }
        .band-table { width: 100%; border-collapse: collapse; }
        .band-table td { border: none; padding: 0; vertical-align: middle; }
        .band-mark {
            width: 34px; height: 34px;
            background: #fff;
            color: #1E6FD9;
            border-radius: 8px;
            text-align: center;
            line-height: 34px;
            font-size: 14px;
            font-weight: 800;
        }
        .band-mark-cell { width: 44px; }
        .band-school { font-size: 16px; font-weight: 700; color: #fff; letter-spacing: 0.2px; }
        .band-meta { font-size: 8.5px; color: #D6E6FA; margin-top: 1px; }
        .band-meta-tel { font-size: 7px; }
        .band-badge-cell { text-align: right; width: 140px; }
        .term-pill {
            display: inline-block;
            background: rgba(255,255,255,0.16);
            color: #fff;
            font-size: 8px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .year-label { display: block; font-size: 7.5px; color: #D6E6FA; margin-top: 3px; }

        .content { padding: 18px 24px 0; }

        .doc-title { font-size: 10px; font-weight: 700; color: #94A3B8; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 14px; }

        /* ── Stat tiles ── */
        .tiles-table { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin: 0 0 18px; }
        .tile { background: #F8FAFC; border-radius: 10px; padding: 10px 14px; }
        .tile-label { font-size: 7px; color: #94A3B8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 3px; }
        .tile-value { font-size: 15px; font-weight: 700; color: #0F172A; }

        /* ── Section headings ── */
        .sec-heading-table { width: 100%; border-collapse: collapse; margin: 20px 0 8px; }
        .sec-heading-table td { border: none; padding: 0; vertical-align: middle; }
        .sec-tab { width: 4px; background: #1E6FD9; border-radius: 2px; }
        .sec-tab-eot { background: #0F172A; }
        .sec-heading-text { padding-left: 8px; font-size: 10.5px; font-weight: 700; color: #0F172A; text-transform: uppercase; letter-spacing: 1.5px; }

        /* ── Tables — flat, row-separator style ── */
        .flat-table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .flat-table th {
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94A3B8;
            padding: 6px 6px;
            border: none;
            border-bottom: 1px solid #1E6FD9;
            text-align: center;
        }
        .flat-table td {
            font-size: 10px;
            padding: 6px 6px;
            border: none;
            border-bottom: 1px solid #F1F5F9;
            text-align: center;
            color: #334155;
        }
        .flat-table tr:nth-child(even) td { background: #FAFBFD; }
        .flat-table td.left { text-align: left; font-weight: 600; color: #0F172A; }
        .flat-table td.empty { color: #CBD5E1; }

        .total-row td {
            font-weight: 800;
            font-size: 9.5px;
            border-top: 2px solid #0F172A !important;
            border-bottom: none !important;
            background: #fff !important;
            color: #0F172A;
        }

        .pos-strip-table { width: 100%; border-collapse: collapse; margin: 4px 0 14px; }
        .pos-strip-table td { padding: 9px 14px; background: #EFF6FF; border-radius: 10px; border: none; }
        .pos-strip-table td.pos-label { font-size: 8px; text-transform: uppercase; letter-spacing: 1px; color: #1E6FD9; font-weight: 700; }
        .pos-strip-table td.pos-value { text-align: right; font-size: 13px; font-weight: 800; color: #0F172A; }

        /* ── Comments ── */
        .comments-table { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin: 8px 0 12px; }
        .comments-label { font-size: 7px; color: #94A3B8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; font-weight: 700; }
        .comments-box {
            padding: 10px 12px;
            background: #F8FAFC;
            border-radius: 10px;
            font-size: 10px;
            min-height: 30px;
            color: #475569;
        }

        .next-term { text-align: center; font-size: 10px; font-weight: 700; color: #1E6FD9; margin: 6px 0 10px; }
        .sig-table { width: 100%; border-collapse: collapse; margin: 4px 0 10px; }
        .sig-table td { width: 50%; text-align: center; vertical-align: bottom; padding: 0 12px; }
        .sig-row { text-align: center; }
        .sig-dash { letter-spacing: 3px; color: #334155; }
        .sig-caption { font-size: 8.5px; text-transform: uppercase; letter-spacing: 1px; color: #94A3B8; font-weight: 700; }

        /* ── Grading system ── */
        .grades-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .grades-table th { font-size: 7px; padding: 4px 2px; color: #94A3B8; border: none; border-bottom: 1px solid #E2E8F0; text-transform: uppercase; font-weight: 700; text-align: center; }
        .grades-table td { font-size: 7px; padding: 4px 2px; border: none; text-align: center; color: #334155; }

        /* ── Footer ── */
        .footer-divider { border-top: 1px solid #E2E8F0; margin: 6px 24px 0; }
        .footer-table { width: 100%; border-collapse: collapse; padding: 10px 24px 0; }
        .footer-table td { font-size: 8px; color: #94A3B8; padding: 10px 0 0; border: none; vertical-align: top; }
        .footer-table td.right { text-align: right; }
        .sign-block { display: inline-block; text-align: center; width: 80px; }
        .sign-line { border-bottom: 1px solid #CBD5E1; display: block; height: 16px; }
        .sign-caption { font-size: 6.5px; text-transform: uppercase; letter-spacing: 0.5px; color: #94A3B8; margin-top: 3px; }
        .motto-tag { color: #1E6FD9; font-weight: 700; letter-spacing: 0.5px; }
        .powered { font-size: 9px; font-weight: 800; color: #22C55E; margin-top: 2px; }

        .no-records { text-align: center; padding: 40px; }
    </style>
</head>
<body>

@if (!is_null($learner))

@php
    $termRaw = $learner->marks->first()?->exam?->academicTerm?->name ?? '';
    $termYear = $learner->marks->first()?->exam?->academicTerm?->academicYear?->name ?? '';
    if (preg_match('/^(.*?)\s*(\d+)\s*$/', trim($termRaw), $m)) {
        $roman = ['1' => 'I', '2' => 'II', '3' => 'III', '4' => 'IV', '5' => 'V', '6' => 'VI'];
        $termName = strtoupper(trim($m[1])) . ' ' . ($roman[$m[2]] ?? $m[2]);
    } else {
        $termName = strtoupper($termRaw);
    }
    if ($termName !== '' && $termYear !== '') {
        $termName .= ' ' . $termYear;
    }
    $gradeLetters = ['1' => 'D', '2' => 'D', '3' => 'C', '4' => 'C', '5' => 'C', '6' => 'C', '7' => 'P', '8' => 'P', '9' => 'F'];
@endphp

<div class="page">

    {{-- ═══ TOP BAND ═══ --}}
    <div class="band">
        <table class="band-table">
            <tr>
                <td class="band-mark-cell"><div class="band-mark">{{ strtoupper(substr($learner->school->name, 0, 1)) }}</div></td>
                <td>
                    <div class="band-school">{{ $learner->school->name }}</div>
                    <div class="band-meta">(Nursery And Primary, Day And Boarding)</div>
                    <div class="band-meta">P.O Box 283 - Kabale - UGA</div>
                    <div class="band-meta band-meta-tel">Tel: +256782255758 / +256784119149 / +256704301646</div>
                </td>
                <td class="band-badge-cell">
                    <span class="term-pill">{{ $termName }}</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="content">

    <div class="doc-title">PROGRESSIVE REPORT</div>

    {{-- ═══ STAT TILES ═══ --}}
    <table class="tiles-table">
        <tr>
            <td style="width:50%;">
                <div class="tile">
                    <div class="tile-label">Student</div>
                    <div class="tile-value">{{ $learner->userprofile->firstname }} {{ $learner->userprofile->lastname }}</div>
                </div>
            </td>
            <td style="width:25%;">
                <div class="tile">
                    <div class="tile-label">Class</div>
                    <div class="tile-value">{{ $class_name }}</div>
                </div>
            </td>
            <td style="width:25%;">
                <div class="tile">
                    <div class="tile-label">Aggregate</div>
                    <div class="tile-value">@if(!empty($isNursery)) - @else {{ $grade['agg'] ?? '-' }} @endif</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- ═══ MAIN MARKS ═══ --}}
    @if(!empty($isNursery))
        <table class="flat-table">
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
        <table class="sec-heading-table"><tr><td class="sec-tab"></td><td class="sec-heading-text">MONTHLY RESULTS — MID TERM</td></tr></table>
        <table class="flat-table">
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
                                    $midGrade = $g ? ($gradeLetters[$g->points] ?? 'D') . $g->points : '-';
                                }
                            @endphp
                            <td>{{ $midMark ? floor($midMark->marks) : '-' }}</td>
                            <td>{{ $midGrade }}</td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </table>
        @endif

        @if ($eotExams->isNotEmpty())
        <table class="sec-heading-table"><tr><td class="sec-tab sec-tab-eot"></td><td class="sec-heading-text">END OF TERM EXAMINATION</td></tr></table>
        <table class="flat-table">
            <tr>
                <th>Subject</th>
                <th style="width:8%">Full Mark</th>
                <th style="width:8%">Mark Gained</th>
                <th style="width:8%">AGG</th>
                <th>Comment</th>
                <th style="width:10%">TR Initials</th>
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
                        $eotGrade = $g ? ($gradeLetters[$g->points] ?? 'D') . $g->points : '-';
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
                    <td>{{ $eotGrade }}</td>
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
        <table class="pos-strip-table">
            <tr>
                <td class="pos-label">POSITION</td>
                <td class="pos-value">{{ $myPos ?? '-' }} of {{ $totalLearners ?? '-' }}</td>
            </tr>
        </table>
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
            <td><div class="sig-row"><span class="sig-caption">Class Teacher</span>&nbsp;<span class="sig-dash">____________________</span></div></td>
            <td><div class="sig-row"><span class="sig-caption">Head Teacher</span>&nbsp;<span class="sig-dash">____________________</span></div></td>
        </tr>
    </table>

    {{-- ═══ FOOTER ═══ --}}
    <div class="footer-divider"></div>
    <table class="footer-table">
        <tr>
            <td style="width:100%; text-align:center;">
                <span class="motto-tag">HARD WORK PAYS</span><br>
                Kabale Junior School, UNEB Center No. {{ $school->uneb_center_number }} Tel: +256782255758 / +256784119149 / +256704301646
                <br><span class="powered">Powered by klassapp.xyz</span>
            </td>
        </tr>
    </table>

</div>

@else
    <div class="no-records">No records found</div>
@endif

</body>
</html>
