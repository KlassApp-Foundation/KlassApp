<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Report Card — {{ $learner->userprofile->firstname ?? 'Student' }} {{ $learner->userprofile->lastname ?? '' }}</title>
    <style>
        @page { margin: 14px 16px; }

        * { margin: 0; padding: 0; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            font-weight: 600;
            line-height: 1.4;
            color: #1E293B;
            background: #fff;
        }

        .frame {
            border: 2px solid #0F172A;
            padding: 4px;
        }

        .frame-inner {
            border: 1px solid #22C55E;
            padding: 14px 18px 10px;
        }

        /* ── Header (centered letterhead) ── */
        .header-block { text-align: center; margin-bottom: 6px; }
        .school-name {
            font-family: 'DejaVu Serif', serif;
            font-size: 19px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #0F172A;
            text-transform: uppercase;
            margin-top: 6px;
        }
        .school-meta {
            font-family: 'DejaVu Serif', serif;
            font-size: 8px;
            color: #475569;
            margin-top: 2px;
        }

        .doc-title-wrap { text-align: center; margin: 4px 0 10px; }
        .doc-title {
            display: inline-block;
            font-family: 'DejaVu Serif', serif;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 3px;
            color: #0F172A;
            text-transform: uppercase;
            padding: 4px 22px;
            border-top: 1px solid #0F172A;
            border-bottom: 1px solid #0F172A;
        }
        .doc-sub { font-family: 'DejaVu Serif', serif; font-size: 8px; color: #15803D; margin-top: 3px; letter-spacing: 1px; }

        /* ── Particulars ── */
        .particulars { width: 100%; border-collapse: collapse; margin-bottom: 10px; border: 1px solid #0F172A; }
        .particulars td { padding: 6px 10px; font-size: 9px; border: none; border-right: 1px solid #CFE8D6; }
        .particulars td:last-child { border-right: none; }
        .part-label { font-family: 'DejaVu Serif', serif; font-size: 7px; color: #15803D; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
        .part-value { font-family: 'DejaVu Serif', serif; font-size: 12px; font-weight: 700; color: #0F172A; }
        .part-value-sm { font-family: 'DejaVu Serif', serif; font-size: 11px; font-weight: 700; color: #0F172A; }

        /* ── Section labels ── */
        .section-label {
            font-family: 'DejaVu Serif', serif;
            font-size: 10px;
            font-weight: 700;
            color: #0F172A;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 10px 0 4px;
            padding-bottom: 2px;
            border-bottom: 1px solid #22C55E;
        }

        /* ── Tables (ledger style) ── */
        .ledger { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .ledger th {
            background: #15803D;
            color: #fff;
            font-family: 'DejaVu Serif', serif;
            font-size: 7.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 5px 4px;
            border: 1px solid #15803D;
            text-align: center;
        }
        .ledger td {
            font-size: 9px;
            padding: 3.5px 4px;
            border: 1px solid #CFE8D6;
            text-align: center;
            color: #1E293B;
        }
        .ledger tr:nth-child(even) td { background: #F0FBF4; }
        .ledger td.left { text-align: left; font-family: 'DejaVu Serif', serif; }
        .ledger td.empty { color: #94A3B8; }

        .total-row td {
            font-weight: 800;
            font-size: 9.5px;
            background: #DCF5E3 !important;
            border-top: 2px solid #0F172A;
            color: #0F172A;
        }

        .pos-table { width: 100%; border-collapse: collapse; margin: 2px 0 8px; }
        .pos-table td {
            font-family: 'DejaVu Serif', serif;
            font-size: 9.5px;
            font-weight: 700;
            padding: 5px 10px;
            border: 1px solid #0F172A;
            background: #F0FBF4;
            color: #0F172A;
        }
        .pos-table td.pos-label { text-transform: uppercase; letter-spacing: 1px; width: 50%; }

        /* ── Comments ── */
        .comments-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .comments-table td { width: 50%; padding: 0 4px; vertical-align: top; border: none; }
        .comments-table td:first-child { padding-left: 0; }
        .comments-table td:last-child { padding-right: 0; }
        .comments-label { font-family: 'DejaVu Serif', serif; font-size: 7.5px; color: #15803D; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 3px; }
        .comments-box {
            padding: 7px 9px;
            border: 1px solid #22C55E;
            font-family: 'DejaVu Serif', serif;
            font-style: italic;
            font-size: 9px;
            min-height: 30px;
            color: #334155;
        }

        /* ── Grading system ── */
        .grades-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .grades-table th { font-family: 'DejaVu Serif', serif; font-size: 7px; padding: 3px 2px; background: #15803D; color: #fff; border: 1px solid #15803D; text-transform: uppercase; font-weight: 700; text-align: center; }
        .grades-table td { font-size: 7px; padding: 3px 2px; border: 1px solid #CFE8D6; text-align: center; color: #1E293B; }

        /* ── Footer / seal / signatures ── */
        .footer-wrap { margin-top: 6px; }
        .signoff-table { width: 100%; border-collapse: collapse; }
        .signoff-table td { border: none; vertical-align: bottom; padding: 0; }
        .seal-cell { width: 68px; text-align: center; }
        .seal {
            width: 56px; height: 56px;
            border: 1.5px dashed #22C55E;
            border-radius: 50%;
            display: inline-block;
            text-align: center;
            line-height: 1.3;
            padding-top: 16px;
        }
        .seal-text { font-family: 'DejaVu Serif', serif; font-size: 6px; color: #15803D; text-transform: uppercase; letter-spacing: 0.5px; }

        .sign-block { text-align: center; padding: 0 6px; }
        .sign-line { border-top: 1px solid #0F172A; width: 100%; display: block; margin-bottom: 3px; padding-top: 24px; }
        .sign-caption { font-family: 'DejaVu Serif', serif; font-size: 7.5px; text-transform: uppercase; letter-spacing: 1px; color: #475569; }

        .motto-row { text-align: center; margin-top: 10px; }
        .motto {
            font-family: 'DejaVu Serif', serif;
            font-style: italic;
            font-size: 9.5px;
            color: #0F172A;
            letter-spacing: 1px;
        }
        .generated-row { text-align: center; margin-top: 5px; font-family: 'DejaVu Serif', serif; font-size: 6.5px; color: #64748B; }

        .no-records { text-align: center; padding: 40px; }
    </style>
</head>
<body>

@if (!is_null($learner))

<div class="frame"><div class="frame-inner">

    {{-- ═══ HEADER (centered letterhead) ═══ --}}
    <div class="header-block">
        @if (!empty($logoPath))
        <img src="{{ $logoPath }}" style="width: 60px; height: auto;" alt="Logo">
        @endif
        <div class="school-name">{{ $learner->school->name }}</div>
        <div class="school-meta">(Nursery And Primary, Day And Boarding)</div>
        <div class="school-meta">P.O Box 283 - Kabale - UGA</div>
        <div class="school-meta">Tel: +256782255758 / +256784119149 / +256704301646</div>
    </div>

    <div class="doc-title-wrap">
        <span class="doc-title">PROGRESSIVE REPORT</span>
        <div class="doc-sub">{{ $learner->marks->first()->exam->academicTerm->name ?? 'Term' }} &middot; {{ optional($learner->marks->first()->exam->academicTerm)->academicYear->name ?? '' }}</div>
    </div>

    {{-- ═══ PARTICULARS ═══ --}}
    <table class="particulars">
        <tr>
            <td style="width:50%;">
                <div class="part-label">Student</div>
                <div class="part-value">{{ $learner->userprofile->firstname }} {{ $learner->userprofile->lastname }}</div>
            </td>
            <td style="width:25%;">
                <div class="part-label">Class</div>
                <div class="part-value-sm">{{ $class_name }}</div>
            </td>
            <td style="width:25%;">
                <div class="part-label">Aggregate</div>
                <div class="part-value-sm">@if(!empty($isNursery)) &mdash; @else {{ $grade['agg'] ?? '&mdash;' }} @endif</div>
            </td>
        </tr>
    </table>

    {{-- ═══ MAIN MARKS ═══ --}}
    @if(!empty($isNursery))
        <table class="ledger">
            <tr><th>Domain</th><th>Rating</th><th>Remarks</th></tr>
            @foreach (['Literacy', 'Numeracy', 'Motor Skills', 'Social/Emotional'] as $domain)
                @php $a = $nurseryAssessments->get($domain); @endphp
                <tr>
                    <td class="left"><strong>{{ $domain }}</strong></td>
                    <td><strong>{{ $a?->rating ?? '&mdash;' }}</strong></td>
                    <td>{{ $a?->remarks ?? '&mdash;' }}</td>
                </tr>
            @endforeach
        </table>
    @else
        @if ($midExams->isNotEmpty())
        <div class="section-label">MONTHLY RESULTS — MID TERM</div>
        <table class="ledger">
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
                    <td class="left"><strong>{{ $monthLabel }}</strong></td>
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
                            <td>{{ $midMark ? floor($midMark->marks) : '&mdash;' }}</td>
                            <td>{{ $midGrade }}</td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </table>
        @endif

        @if ($eotExams->isNotEmpty())
        <div class="section-label">END OF TERM EXAMINATION</div>
        <table class="ledger">
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
                        $eotGrade = $g ? 'D' . $g->points : '-';
                        $eotComment = $g ? $g->remark : '-';
                    }
                    $teacherLink = \App\Models\Teacherlink::where('standardLink_id', $stdLink->id)
                        ->where('subject_id', $subject->id)->first();
                    $teacherName = '&mdash;';
                    if ($teacherLink && $teacherLink->teacher) {
                        $fn = $teacherLink->teacher->userprofile->firstname ?? '';
                        $ln = $teacherLink->teacher->userprofile->lastname ?? '';
                        if ($fn) {
                            $teacherName = $ln ? ucwords(strtolower($fn)) . ' ' . ucwords(strtolower($ln)) : ucwords(strtolower($fn));
                        }
                    }
                @endphp
                <tr>
                    <td class="left"><strong>{{ $subject->name }}</strong></td>
                    <td>100</td>
                    <td>{{ $eotMark ? floor($eotMark->marks) : '&mdash;' }}</td>
                    <td>{{ $eotGrade }}</td>
                    <td>{{ $eotComment }}</td>
                    <td>{{ $teacherName }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td><strong>TOTAL</strong></td>
                <td>{{ isset($examinedSubjectCount) ? $examinedSubjectCount * 100 : count($subjects) * 100 }}</td>
                <td><strong>{{ $total }}</strong></td>
                <td><strong>{{ $grade['agg'] ?? '&mdash;' }}</strong></td>
                <td colspan="2"></td>
            </tr>
        </table>

        @if (!empty($myPos) && !$isNursery)
        <table class="pos-table">
            <tr>
                <td class="pos-label">POSITION</td>
                <td>{{ $myPos ?? '&mdash;' }} of {{ $totalLearners ?? '&mdash;' }}</td>
            </tr>
        </table>
        @endif
        @endif
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
                <div class="comments-box">{{ $headTeacherComment ?? '&mdash;' }}</div>
            </td>
        </tr>
    </table>

    {{-- ═══ FOOTER ═══ --}}
    <div class="footer-wrap">
        <table class="signoff-table">
            <tr>
                <td class="sign-block" style="width: 50%;">
                    <span class="sign-line"></span>
                    <span class="sign-caption">Class Teacher</span>
                </td>
                <td class="sign-block" style="width: 50%;">
                    <span class="sign-line"></span>
                    <span class="sign-caption">Head Teacher</span>
                </td>
            </tr>
        </table>

        <div class="motto-row">
            <span class="motto">HARD WORK PAYS</span>
        </div>
        <div class="generated-row">
            {{ $learner->school->name }} &middot; Generated {{ now()->format('d M Y') }}
            @if ($nextTerm) &middot; Next Term Begins: {{ $nextTerm->starts_on->format('d/m/Y') }} @endif
        </div>
    </div>

</div></div>

@else
    <div class="no-records">No records found</div>
@endif

</body>
</html>
