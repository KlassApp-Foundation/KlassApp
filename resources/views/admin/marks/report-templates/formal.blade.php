<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Report Card — {{ $learner->displayName ?: 'Student' }}</title>
    <style>
        @page { margin: 0; }

        * { margin: 0; padding: 0; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            font-weight: 600;
            line-height: 1.4;
            color: #1E293B;
            background: #fff;
        }

        .header-band {
            text-align: center;
            padding: 14px 22px 12px;
            border-bottom: 2px solid #0F172A;
        }

        .content-wrap { padding: 14px 16px; }

        .watermark {
            position: absolute;
            top: 330px;
            left: 50%;
            margin-left: -190px;
            width: 380px;
            opacity: 0.04;
        }

        .footer-band {
            text-align: center;
            padding: 8px 16px 14px;
            border-top: 2px solid #0F172A;
            font-family: 'DejaVu Serif', serif;
            font-size: 9px;
            color: #334155;
        }
        .powered { font-size: 9px; font-weight: 800; color: #22C55E; margin-top: 3px; }

        .next-term {
            text-align: center;
            font-family: 'DejaVu Serif', serif;
            font-size: 10px;
            font-weight: 700;
            color: #15803D;
            margin: 6px 0 10px;
        }

        .sig-table { width: 100%; border-collapse: collapse; margin: 4px 0 10px; }
        .sig-card { border: 1px solid #22C55E; border-top: 3px solid #15803D; background: #FCFDFB; padding: 10px 12px 8px; margin-bottom: 8px; }
        .sig-card-table { width: 100%; border-collapse: collapse; }
        .sig-card-table td { width: 50%; vertical-align: top; padding: 2px 10px; }
        .sig-card-table td + td { border-left: 1px solid #CFE8D6; }
        .comments-label { font-family: 'DejaVu Serif', serif; font-size: 8.5px; color: #15803D; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 3px; font-weight: 700; }
        .sig-table td { width: 50%; text-align: center; vertical-align: bottom; padding: 0 12px; }
        .sig-row { text-align: left; margin-top: 6px; border-top: 1px dashed #CFE8D6; padding-top: 6px; }
        .sig-dash { letter-spacing: 3px; color: #0F172A; }
        .sig-caption { font-family: 'DejaVu Serif', serif; font-size: 8.5px; text-transform: uppercase; letter-spacing: 1px; color: #475569; }

        .frame {
            border: 2px solid #0F172A;
            padding: 4px;
        }

        .frame-inner {
            border: 1px solid #22C55E;
            padding: 14px 18px 12px;
        }

        /* ── Header (logo left, details right) ── */
        /* Logo + details form one lockup, shrink-wrapped and centered as a
           unit (not stretched edge-to-edge) — the logo sits immediately
           left of the text rather than pinned to the page margin with the
           text centered independently, which left a lopsided gap between
           them. */
        .hdr-table { border-collapse: collapse; margin: 0 auto 10px; }
        .hdr-logo { text-align: left; vertical-align: middle; padding-right: 16px; }
        .hdr-details { text-align: center; vertical-align: middle; }
        .school-name {
            font-family: 'DejaVu Serif', serif;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 2px;
            color: #0F172A;
            text-transform: uppercase;
        }
        .school-meta {
            font-family: 'DejaVu Serif', serif;
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            margin-top: 3px;
        }
        .school-meta-tel { font-size: 8.5px; }

        .doc-title-wrap { text-align: center; margin: 8px 0 14px; }
        .doc-title {
            display: inline-block;
            font-family: 'DejaVu Serif', serif;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 4px;
            color: #0F172A;
            text-transform: uppercase;
            padding: 7px 36px;
            border-top: 1px solid #0F172A;
            border-bottom: 1px solid #0F172A;
        }
        .doc-sub { font-family: 'DejaVu Serif', serif; font-size: 10px; color: #15803D; margin-top: 5px; letter-spacing: 1px; }

        /* ── Particulars ── */
        .particulars { width: 100%; border-collapse: collapse; margin-bottom: 10px; border: 1px solid #0F172A; }
        .particulars td { padding: 6px 10px; font-size: 10px; border: none; border-right: 1px solid #CFE8D6; }
        .particulars td:last-child { border-right: none; }
        .part-label { font-family: 'DejaVu Serif', serif; font-size: 7px; color: #15803D; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
        .part-value { font-family: 'DejaVu Serif', serif; font-size: 13px; font-weight: 700; color: #0F172A; }
        .part-value-sm { font-family: 'DejaVu Serif', serif; font-size: 12px; font-weight: 700; color: #0F172A; }

        /* ── Section labels ── */
        .section-label {
            font-family: 'DejaVu Serif', serif;
            font-size: 11px;
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
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 5px 4px;
            border: 1px solid #15803D;
            text-align: center;
        }
        .ledger td {
            font-size: 10px;
            padding: 3.5px 4px;
            border: 1px solid #22C55E;
            text-align: center;
            color: #1E293B;
        }
        .ledger tr:nth-child(even) td { background: #F0FBF4; }
        .ledger td.left { text-align: left; font-family: 'DejaVu Serif', serif; }
        .ledger td.empty { color: #94A3B8; }

        .total-row td {
            font-weight: 800;
            font-size: 10.5px;
            background: #DCF5E3 !important;
            border-top: 2px solid #0F172A;
            color: #0F172A;
        }

        .division-cell { text-align: center; font-size: 9.5px; color: #15803D; }

        /* ── Comments ── */
        .comments-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .comments-table td { width: 50%; padding: 0 4px; vertical-align: top; border: none; }
        .comments-table td:first-child { padding-left: 0; }
        .comments-table td:last-child { padding-right: 0; }
        .comments-box {
            padding: 0;
            font-family: 'DejaVu Serif', serif;
            font-style: italic;
            font-size: 10px;
            height: 56px;
            overflow: hidden;
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
        .sign-caption { font-family: 'DejaVu Serif', serif; font-size: 8.5px; text-transform: uppercase; letter-spacing: 1px; color: #475569; }

        .motto-row { text-align: center; margin-top: 10px; }
        .motto {
            font-family: 'DejaVu Serif', serif;
            font-style: italic;
            font-size: 9.5px;
            color: #0F172A;
            letter-spacing: 1px;
        }
        .generated-row { text-align: center; margin-top: 5px; font-family: 'DejaVu Serif', serif; font-size: 8.5px; color: #64748B; }

        .no-records { text-align: center; padding: 40px; }
    </style>
</head>
<body>

@if (!is_null($learner))

@php
    $roman = ['1' => 'I', '2' => 'II', '3' => 'III', '4' => 'IV', '5' => 'V', '6' => 'VI'];
    $termRaw = $learner->marks->first()?->exam?->academicTerm?->name ?? '';
    $termYear = $learner->marks->first()?->exam?->academicTerm?->academicYear?->name ?? '';
    if (preg_match('/^(.*?)\s*(\d+)\s*$/', trim($termRaw), $m)) {
        $termName = strtoupper(trim($m[1])) . ' ' . ($roman[$m[2]] ?? $m[2]);
    } else {
        $termName = strtoupper($termRaw);
    }
    if ($termName !== '' && $termYear !== '') {
        $termName .= ' ' . $termYear;
    }
    $termNumeralArabic = preg_match('/\D*(\d+)/', $termRaw, $tmm) ? $tmm[1] : '';
    $termNumeral = $termNumeralArabic !== '' ? ($roman[$termNumeralArabic] ?? $termNumeralArabic) : '';
    $ordinal = function ($n) {
        $n = (int) $n;
        $j = $n % 100;
        if ($j >= 11 && $j <= 13) { return $n . 'th'; }
        return $n . (['1' => 'st', '2' => 'nd', '3' => 'rd'][$n % 10] ?? 'th');
    };
    $initials = function ($full) {
        $t = preg_split('/\s+/', trim($full ?? ''));
        if (count($t) >= 2) { return strtoupper(mb_substr($t[0], 0, 1)) . ' ' . strtoupper(mb_substr($t[1], 0, 1)); }
        return $t ? strtoupper(mb_substr($t[0], 0, 1)) : '-';
    };
    $gradeLetters = ['1' => 'D', '2' => 'D', '3' => 'C', '4' => 'C', '5' => 'C', '6' => 'C', '7' => 'P', '8' => 'P', '9' => 'F'];
@endphp

<div class="header-band">

    {{-- ═══ HEADER (logo left, details right) ═══ --}}
    <table class="hdr-table">
        <tr>
            <td class="hdr-logo">
                @if (!empty($logoPath))
                <img src="{{ $logoPath }}" style="width: 84px; height: auto;" alt="Logo">
                @endif
            </td>
            <td class="hdr-details">
                <div class="school-name">{{ $schoolIdentity['name'] ?? $learner->school->name }}</div>
                @if (!empty($schoolIdentity['category_subtitle']))
                <div class="school-meta">{{ $schoolIdentity['category_subtitle'] }}</div>
                @endif
                @if (!empty($schoolIdentity['address']))
                <div class="school-meta">{{ $schoolIdentity['address'] }}</div>
                @endif
                @if (!empty($schoolIdentity['phones_line']))
                <div class="school-meta school-meta-tel">{{ $schoolIdentity['phones_line'] }}</div>
                @endif
            </td>
        </tr>
    </table>

    <div class="doc-title-wrap">
        <span class="doc-title">PROGRESSIVE REPORT</span>
        <div class="doc-sub">{{ $termName }}</div>
    </div>

</div>

<div class="content-wrap">

    @if (!empty($logoPath))
    <img class="watermark" src="{{ $logoPath }}" alt="">
    @endif

    <div class="frame"><div class="frame-inner">

    {{-- ═══ PARTICULARS ═══ --}}
    <table class="particulars">
        <tr>
            <td style="width:40%;">
                <div class="part-label">Student</div>
                <div class="part-value">{{ $learner->displayName }}</div>
            </td>
            <td style="width:25%;">
                <div class="part-label">Class</div>
                <div class="part-value-sm">{{ $class_name }}</div>
            </td>
            <td style="width:35%;">
                <div class="part-label">KLS ID</div>
                <div class="part-value-sm">{{ $learner->registration_number ?: '—' }}</div>
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
                    <td><strong>{{ $a?->rating ?? '-' }}</strong></td>
                    <td>{{ $a?->remarks ?? '-' }}</td>
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
                        @if ($showAgg) <th>AGG</th> @endif
                    @endif
                @endforeach
                <th>TOTAL</th>
                <th>POSITION</th>
                @if ($showAgg) <th>DIVISION</th> @endif
            </tr>
            @foreach ($midExams as $midExam)
                @php $monthLabel = \App\Services\StudentReportCardService::midExamMonthRowLabel($midExam); @endphp
                @php $ms = $midStats[$midExam->id] ?? null; @endphp
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
                                    $midGrade = \App\Helpers\GradingHelper::formatAggLabel($g) ?? '-';
                                }
                            @endphp
                            <td>{{ $midMark && $midMark->marks !== null ? floor($midMark->marks) : '-' }}</td>
                            @if ($showAgg) <td>{{ $midGrade }}</td> @endif
                        @endif
                    @endforeach
                    <td><strong>{{ $ms['total'] ?? '-' }}</strong></td>
                    <td>{{ ($ms && $ms['pos']) ? $ordinal($ms['pos']) : '-' }}</td>
                    @if ($showAgg) <td><strong>{{ $ms['division'] ?? '-' }}</strong></td> @endif
                </tr>
            @endforeach
        </table>
        @endif

        @if ($eotExams->isNotEmpty())
        <div class="section-label">END OF TERM {{ $termNumeral ?: 'EXAMINATION' }}</div>
        <table class="ledger">
            <tr>
                <th>Subject</th>
                <th style="width:8%">Full Mark</th>
                <th style="width:8%">Mark Gained</th>
                @if ($showAgg) <th style="width:10%">AGG</th> @endif
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
                        $eotGrade = \App\Helpers\GradingHelper::formatAggLabel($g) ?? '-';
                        $eotComment = $g ? $g->remark : '-';
                    }
                    $teacherLink = \App\Models\Teacherlink::where('standardLink_id', $stdLink->id)
                        ->where('subject_id', $subject->id)->first();
                    $teacherName = '-';
                    if ($teacherLink && $teacherLink->teacher) {
                        $fn = preg_replace('/[\d\s\-]+$/', '', $teacherLink->teacher->userprofile->firstname ?? '');
                        $ln = preg_replace('/[\d\s\-]+$/', '', $teacherLink->teacher->userprofile->lastname ?? '');
                        if ($fn) {
                            $teacherName = $initials($ln ? $fn . ' ' . $ln : $fn);
                        }
                    }
                @endphp
                <tr>
                    <td class="left"><strong>{{ $subject->name }}</strong></td>
                    <td>100</td>
                    <td>{{ $eotMark && $eotMark->marks !== null ? floor($eotMark->marks) : '-' }}</td>
                    @if ($showAgg) <td>{{ $eotGrade }}</td> @endif
                    <td>{{ $eotComment }}</td>
                    <td>{{ $teacherName }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td><strong>TOTAL</strong></td>
                <td>{{ isset($examinedSubjectCount) ? $examinedSubjectCount * 100 : count($subjects) * 100 }}</td>
                <td><strong>{{ $total }}</strong></td>
                @if ($showAgg) <td><strong>{{ $eotAgg ?? ($grade['agg'] ?? '-') }}</strong></td> @endif
                <td colspan="2" class="division-cell">@if ($showAgg) DIVISION: <strong>{{ $eotDivision }}</strong> @endif</td>
            </tr>
        </table>

        @if ($nextTerm && !$isNursery)
        <div class="next-term">Next term begins on {{ $nextTerm->starts_on->format('d/m/Y') }}</div>
        @endif
        @endif
    @endif

    {{-- ═══ COMMENTS + SIGNATURES (one card) ═══ --}}
    <div class="sig-card">
        <table class="sig-card-table">
            <tr>
                <td>
                    <div class="comments-label">CLASSTEACHER'S COMMENT</div>
                    <div class="comments-box">{{ $teacherComment ?? '-' }}</div>
                    <div class="sig-row"><span class="sig-caption">SIGN</span>&nbsp;<span class="sig-dash">____________________</span></div>
                </td>
                <td>
                    <div class="comments-label">HEADTEACHER'S COMMENT</div>
                    <div class="comments-box">{{ $headTeacherComment ?? '-' }}</div>
                    <div class="sig-row"><span class="sig-caption">SIGN &amp; STAMP</span>&nbsp;<span class="sig-dash">____________________</span></div>
                </td>
            </tr>
        </table>
    </div>

    @if (!empty($schoolIdentity['motto']))
    <div class="motto-row">
        <span class="motto">{{ $schoolIdentity['motto'] }}</span>
    </div>
    @endif

</div></div>
</div>

<div class="footer-band">
    {{ $schoolIdentity['footer_line'] ?? $learner->school->name }}
    <div class="powered">Powered by klassapp.xyz</div>
</div>

@else
    <div class="no-records">No records found</div>
@endif

</body>
</html>
