<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Report Card — {{ $learner->reportCardName() ?: 'Student' }}</title>
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
        /* Logo + details form one lockup, shrink-wrapped and centered as a
           unit (not stretched edge-to-edge) — the logo sits immediately
           left of the text rather than pinned to the page margin with the
           text centered independently, which left a lopsided gap between
           them. */
        .hdr-table { border-collapse: collapse; margin: 0 auto 4px; }
        .hdr-logo { text-align: left; vertical-align: middle; padding-right: 14px; }
        .hdr-details { text-align: center; vertical-align: middle; }
        .h-school { font-size: 22px; font-weight: 800; color: #7C3A11; }
        .h-meta { font-size: 12px; font-weight: 700; color: #A88865; margin-top: 2px; }
        .h-meta-tel { font-size: 8.5px; }
        .term-chip {
            display: inline-block;
            background: #EBF5E4;
            color: #3F6B1F;
            font-size: 9px;
            font-weight: 700;
            padding: 6px 16px;
            border-radius: 20px;
        }
        .year-chip { display: block; font-size: 8.5px; color: #A88865; margin-top: 3px; }

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
        .card-table { width: 100%; border-collapse: collapse; margin-bottom: 4px; border: 1px solid #D97706; border-radius: 10px; overflow: hidden; }
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

        .division-cell { text-align: center; font-size: 9.5px; color: #B5651D; }

        /* ── Comments ── */
        .comments-table { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin: 10px 0 12px; }
        .comments-label { font-size: 8.5px; color: #A88865; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; font-weight: 700; }
        .comments-box {
            padding: 0;
            font-size: 10px;
            height: 60px;
            overflow: hidden;
            color: #6B543C;
        }

        /* ── Grading system ── */
        .grades-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; border: 1px solid #F0DFC0; border-radius: 8px; overflow: hidden; }
        .grades-table th { font-size: 7px; padding: 4px 2px; background: #FCEEDD; color: #7C3A11; border: none; border-bottom: 1px solid #F0DFC0; text-transform: uppercase; font-weight: 700; text-align: center; }
        .grades-table td { font-size: 7px; padding: 4px 2px; border: none; text-align: center; color: #6B543C; }

        /* ── Next term / signatures / footer ── */
        .next-term { text-align: center; font-size: 10px; font-weight: 800; color: #3F6B1F; margin: 6px 0 10px; }
        .sig-table { width: 100%; border-collapse: collapse; margin: 4px 0 10px; }
        .sig-card { background: #FFFDF9; border: 1px solid #F0DFC0; border-top: 3px solid #D97706; border-radius: 12px; padding: 12px 14px 10px; margin-bottom: 10px; }
        .sig-card-table { width: 100%; border-collapse: collapse; }
        .sig-card-table td { width: 50%; vertical-align: top; padding: 2px 10px; }
        .sig-card-table td + td { border-left: 1px solid #F0DFC0; }
        .sig-table td { width: 50%; text-align: center; vertical-align: bottom; padding: 0 12px; }
        .sig-row { text-align: left; margin-top: 6px; border-top: 1px dashed #E8D3B8; padding-top: 6px; }
        .sig-dash { letter-spacing: 3px; color: #7C3A11; }
        .sig-caption { font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.5px; color: #A88865; font-weight: 700; }
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
        .powered { font-size: 9px; font-weight: 800; color: #22C55E; margin-top: 3px; }

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

<div class="header-card">

    {{-- ═══ HEADER (logo left, details right) ═══ --}}
    <div class="header-top-row">
        <span class="term-chip">{{ $termName }}</span>
    </div>
    <table class="hdr-table">
        <tr>
            <td class="hdr-logo">
                @if (!empty($logoPath))
                <img src="{{ $logoPath }}" style="width: 76px; height: auto;" alt="Logo">
                @endif
            </td>
            <td class="hdr-details">
                <div class="h-school">{{ $schoolIdentity['name'] ?? $learner->school->name }}</div>
                @if (!empty($schoolIdentity['category_subtitle']))
                <div class="h-meta">{{ $schoolIdentity['category_subtitle'] }}</div>
                @endif
                @if (!empty($schoolIdentity['address']))
                <div class="h-meta">{{ $schoolIdentity['address'] }}</div>
                @endif
                @if (!empty($schoolIdentity['phones_line']))
                <div class="h-meta h-meta-tel">{{ $schoolIdentity['phones_line'] }}</div>
                @endif
            </td>
        </tr>
    </table>
    <div class="ribbon">PROGRESSIVE REPORT</div>
    @if (!empty($compositionLine ?? null))
    <div class="doc-sub" data-testid="eot-composition">{{ $compositionLine }}</div>
    @endif
</div>

<div class="page">

    @if (!empty($logoPath))
    <img class="watermark" src="{{ $logoPath }}" alt="">
    @endif

    {{-- ═══ INFO PILLS ═══ --}}
    <table class="pills-table">
        <tr>
            <td style="width:40%;">
                <div class="pill pill-student">
                    <div class="pill-label">Student</div>
                    <div class="pill-value">{{ $learner->reportCardName() }}</div>
                </div>
            </td>
            <td style="width:25%;">
                <div class="pill pill-class">
                    <div class="pill-label">Class</div>
                    <div class="pill-value">{{ $class_name }}</div>
                </div>
            </td>
            <td style="width:35%;">
                <div class="pill pill-kls">
                    <div class="pill-label">KLS ID</div>
                    <div class="pill-value">{{ $learner->registration_number ?: '—' }}</div>
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
        @if (!empty($monthlySets))
        <div class="sec-heading"><span class="dot"></span>MONTHLY RESULTS</div>
        <table class="card-table">
            <tr>
                <th>Subject</th>
                @foreach ($monthlySets as $set)
                    <th>{{ $set['label'] }}</th>
                    @if ($showAgg) <th>AGG</th> @endif
                @endforeach
            </tr>
            @foreach ($subjects as $subject)
                @php
                    $showSubject = $learner->marks->where('subject_id', $subject->id)->isNotEmpty();
                    if (! $showSubject) {
                        foreach ($monthlySets as $set) {
                            if (\App\Services\Reports\MarkCellText::isCountedBlank($set['cells'][$subject->id] ?? null, !empty($countMissingAsZero))) {
                                $showSubject = true;
                            }
                        }
                    }
                @endphp
                @if (! $showSubject) @continue @endif
                <tr>
                    <td class="left">{{ $subject->name }}</td>
                    @foreach ($monthlySets as $set)
                        @php
                            $cell = $set['cells'][$subject->id] ?? null;
                            $markValue = ($cell && $cell->mark !== null) ? $cell->mark : null;
                            $setGrade = '-';
                            if ($markValue !== null) {
                                $g = $grading_system->first(fn($gs) => $gs->min_score <= $markValue && $gs->max_score >= $markValue);
                                $setGrade = \App\Helpers\GradingHelper::formatAggLabel($g) ?? '-';
                            } elseif (\App\Services\Reports\MarkCellText::isCountedBlank($cell, !empty($countMissingAsZero))) {
                                $g = $grading_system->first(fn($gs) => (float) $gs->min_score <= 0 && (float) $gs->max_score >= 0);
                                $setGrade = \App\Helpers\GradingHelper::formatAggLabel($g) ?? '-';
                            }
                        @endphp
                        <td>{{ $markValue !== null ? $cell->sheetLabel() : (\App\Services\Reports\MarkCellText::isCountedBlank($cell, !empty($countMissingAsZero)) ? \App\Services\Reports\MarkCellText::word($cell) : '-') }}</td>
                        @if ($showAgg) <td>@if($setGrade !== '-')<span class="chip {{ $chipClass($setGrade) }}">{{ $setGrade }}</span>@else - @endif</td> @endif
                    @endforeach
                </tr>
            @endforeach
            <tr class="total-row">
                <td class="left"><strong>TOTAL</strong></td>
                @foreach ($monthlySets as $set)
                    <td><strong>{{ $set['total'] ?? '-' }}</strong></td>
                    @if ($showAgg) <td></td> @endif
                @endforeach
            </tr>
            <tr>
                <td class="left"><strong>POSITION</strong></td>
                @foreach ($monthlySets as $set)
                    <td>{{ !empty($set['pos']) ? $ordinal($set['pos']) : '-' }}</td>
                    @if ($showAgg) <td></td> @endif
                @endforeach
            </tr>
            @if ($showAgg)
            <tr>
                <td class="left"><strong>AGGREGATE</strong></td>
                @foreach ($monthlySets as $set)
                    <td></td>
                    <td data-kind="aggregate" data-set="{{ $set['label'] }}"><strong>{{ $set['aggregate'] ?? '-' }}</strong></td>
                @endforeach
            </tr>
            <tr>
                <td class="left"><strong>DIVISION</strong></td>
                @foreach ($monthlySets as $set)
                    <td><strong>{{ $set['division'] ?? '-' }}</strong></td>
                    <td></td>
                @endforeach
            </tr>
            @endif
        </table>
        @endif

        @if (!empty($monthlySets) && empty($termSets))
        <p>No combined mark applies.</p>
        @endif
        @if (!empty($incompleteSets))
        <p>Incomplete: {{ implode(', ', $incompleteSets) }}.</p>
        @endif
        @if (!empty($termSets))
        <div class="sec-heading sec-heading-eot"><span class="dot"></span>END OF TERM {{ $termNumeral ?: 'EXAMINATION' }}</div>
        <table class="card-table">
            <tr>
                <th class="eot-th">Subject</th>
                <th class="eot-th" style="width:8%">Full Mark</th>
                @foreach ($termSets as $set)
                    <th class="eot-th">{{ $set['label'] }}</th>
                @endforeach
                @if ($showAgg) <th class="eot-th" style="width:10%">AGG</th> @endif
                <th class="eot-th">Comment</th>
                <th class="eot-th" style="width:10%">TR Initials</th>
            </tr>
            @foreach ($subjects as $subject)
                @php
                    $eotCell = ($eotCells ?? [])[$subject->id] ?? null;
                    $showSubject = false;
                    foreach ($termSets as $set) {
                        $setCell = $set['cells'][$subject->id] ?? null;
                        if (($setCell && $setCell->mark !== null) || \App\Services\Reports\MarkCellText::isCountedBlank($setCell, !empty($countMissingAsZero))) {
                            $showSubject = true;
                        }
                    }
                    if (! $showSubject && count($termSets) === 1 && $eotCell && $eotCell->mark !== null) {
                        $showSubject = true;
                    }
                @endphp
                @if (! $showSubject) @continue @endif
                @php
                    $gradeMark = ($eotCell && $eotCell->mark !== null) ? $eotCell->mark : null;
                    $eotGrade = '-';
                    $eotComment = '-';
                    if ($gradeMark !== null) {
                        $g = $grading_system->first(fn($gs) => $gs->min_score <= $gradeMark && $gs->max_score >= $gradeMark);
                        $eotGrade = \App\Helpers\GradingHelper::formatAggLabel($g) ?? '-';
                        $eotComment = $g ? $g->remark : '-';
                    } elseif (\App\Services\Reports\MarkCellText::isCountedBlank($eotCell, !empty($countMissingAsZero))) {
                        $g = $grading_system->first(fn($gs) => (float) $gs->min_score <= 0 && (float) $gs->max_score >= 0);
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
                    <td class="left">{{ $subject->name }}</td>
                    <td>100</td>
                    @foreach ($termSets as $set)
                        @php
                            $setCell = $set['cells'][$subject->id] ?? null;
                            $printedCell = (count($termSets) === 1 && $eotCell && $eotCell->mark !== null) ? $eotCell : $setCell;
                        @endphp
                        <td>{{ $printedCell && $printedCell->mark !== null ? $printedCell->sheetLabel() : (\App\Services\Reports\MarkCellText::isCountedBlank($printedCell, !empty($countMissingAsZero)) ? \App\Services\Reports\MarkCellText::word($printedCell) : '-') }}</td>
                    @endforeach
                    @if ($showAgg) <td>@if($eotGrade !== '-')<span class="chip {{ $chipClass($eotGrade) }}">{{ $eotGrade }}</span>@else - @endif</td> @endif
                    <td>{{ $eotComment }}</td>
                    <td>{{ $teacherName }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td class="left"><strong>TOTAL</strong></td>
                <td>{{ isset($examinedSubjectCount) ? $examinedSubjectCount * 100 : count($subjects) * 100 }}</td>
                @foreach ($termSets as $set)
                    <td><strong>{{ count($termSets) === 1 ? $total : ($set['total'] ?? '-') }}</strong></td>
                @endforeach
                @if ($showAgg) <td><strong>{{ !empty($withheldSets) ? '—' : ($eotAgg ?? ($grade['agg'] ?? '-')) }}</strong></td> @endif
                <td colspan="2" class="division-cell">@if (!empty($withheldSets))<span data-kind="withheld">{{ $withheldSentence }}</span>@elseif (count($termSets) > 1) TOTAL: <strong>{{ $total }}</strong> @if ($showDivision) DIVISION: <strong>{{ $eotDivision }}</strong> @endif @elseif ($showDivision) DIVISION: <strong>{{ $eotDivision }}</strong> @endif
                @if ($showPoints) POINTS: <strong>{{ $eotAgg ?? ($grade['agg'] ?? '-') }}</strong> @endif</td>
            </tr>
            @if ($showAgg)
            <tr>
                <td class="left"><strong>AGGREGATE</strong></td>
                <td></td>
                @foreach ($termSets as $set)
                    <td data-kind="aggregate" data-set="{{ $set['label'] }}"><strong>{{ $set['aggregate'] ?? '-' }}</strong></td>
                @endforeach
                <td></td>
                <td colspan="2"></td>
            </tr>
            @endif
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
                    @if (!empty($headTeacherName))<div>{{ $headTeacherName }}</div>@endif
                </td>
            </tr>
        </table>
    </div>

    @if (!empty($schoolIdentity['motto']))
    <div class="motto-row">{{ $schoolIdentity['motto'] }}</div>
    @endif

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
