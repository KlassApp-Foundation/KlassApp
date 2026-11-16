<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Report Card — {{ $learner->reportCardName() ?: 'Student' }}</title>
    <style>
        @font-face { font-family: 'DM Sans'; font-weight: 400; src: url("{{ resource_path('fonts/report-card/DMSans-Regular.ttf') }}") format('truetype'); }
        @font-face { font-family: 'DM Sans'; font-weight: 700; src: url("{{ resource_path('fonts/report-card/DMSans-Bold.ttf') }}") format('truetype'); }
        @font-face { font-family: 'Sora'; font-weight: 400; src: url("{{ resource_path('fonts/report-card/Sora-Regular.ttf') }}") format('truetype'); }
        @font-face { font-family: 'Sora'; font-weight: 700; src: url("{{ resource_path('fonts/report-card/Sora-Bold.ttf') }}") format('truetype'); }
        @page { size: 210mm 297mm; margin: 0; }
        body { margin: 0; font-family: 'DM Sans', 'DejaVu Sans', sans-serif; color: #1E293B; font-size: 12.5px; }
        .page { position: relative; width: 794px; background: #fff; padding: 28px 52px 20px; }
        .bar { position: absolute; left: 0; top: 0; width: 794px; height: 8px; background: #15803D; }
        .in { width: 690px; }
        table { border-collapse: collapse; }
        .hdr { width: 690px; }
        .hdr td { vertical-align: middle; }
        .mono { width: 62px; height: 62px; border-radius: 31px; background: #15803D; color: #fff; text-align: center; font: 700 19px/62px 'Sora', 'DejaVu Sans', sans-serif; letter-spacing: 1px; }
        .crest { width: 62px; height: 62px; }
        .school { font: 700 24px 'Sora', 'DejaVu Sans', sans-serif; color: #0F172A; }
        .motto { font-size: 12px; font-style: italic; color: #475569; margin-top: 3px; }
        .doc { font: 700 13px 'Sora', 'DejaVu Sans', sans-serif; letter-spacing: 2.4px; color: #15803D; text-align: right; }
        .term { font-size: 13px; color: #334155; text-align: right; margin-top: 4px; }
        .rule { height: 3px; background: #15803D; margin: 14px 0 16px 0; }
        .lrn { width: 690px; background: #F4F8F5; border: 1px solid #D3E3D8; }
        .lrn td { padding: 10px 14px; vertical-align: top; }
        .lbl { font-size: 9.5px; letter-spacing: 1.4px; color: #475569; text-transform: uppercase; }
        .val { font-size: 15px; font-weight: 700; margin-top: 2px; color: #0F172A; }
        .val.big { font: 700 18px 'Sora', 'DejaVu Sans', sans-serif; }
        .res { width: 690px; margin-top: 16px; border: 1px solid #94A3B8; }
        .res th { background: #15803D; color: #fff; font-size: 13px; padding: 8px 4px; text-align: center; border-left: 1px solid #fff; }
        .res th.s { text-align: left; padding-left: 12px; border-left: 0; }
        .res th.sub { background: #E4EFE8; color: #14532D; font-size: 10px; letter-spacing: 1px; padding: 4px; text-transform: uppercase; }
        .res td { padding: 8px 4px; text-align: center; border-top: 1px solid #CBD5E1; font-size: 14px; }
        .res td.s { text-align: left; padding-left: 12px; font-weight: 700; font-size: 13px; }
        .res td.m { font-weight: 700; }
        .res td.g { font-weight: 700; color: #14532D; background: #F4F8F5; }
        .res tr.alt td { background: #FAFBFA; }
        .res tr.alt td.g { background: #EEF4F0; }
        .miss { font-size: 11px; font-style: italic; font-weight: 400; color: #64748B; }
        .sum td { background: #F1F5F9; border-top: 1px solid #94A3B8; padding: 7px 4px; }
        .sum td.s { font-size: 11px; letter-spacing: 1.3px; color: #334155; }
        .sum td.v { font: 700 15px 'Sora', 'DejaVu Sans', sans-serif; color: #0F172A; }
        .sum.k td { background: #E4EFE8; border-top: 2px solid #15803D; }
        .sum.k td.v { font-size: 17px; color: #14532D; }
        .cmt { width: 690px; margin-top: 16px; }
        .cmt td { width: 50%; vertical-align: top; }
        .box { border: 1px solid #94A3B8; min-height: 96px; padding: 8px 12px; }
        .box .t { font-size: 9.5px; letter-spacing: 1.4px; color: #475569; text-transform: uppercase; font-weight: 700; }
        .box .comment { margin-top: 6px; font-size: 12px; color: #1E293B; }
        .ln { border-bottom: 1px solid #CBD5E1; height: 22px; }
        .sig { width: 690px; margin-top: 28px; }
        .sig td { width: 33%; padding: 0 14px 0 0; vertical-align: bottom; }
        .sl { border-top: 1px solid #475569; padding-top: 4px; font-size: 10px; letter-spacing: 1px; color: #475569; text-transform: uppercase; }
        .foot { width: 690px; margin-top: 18px; }
        .next { margin-top: 0; font-size: 12px; color: #334155; }
        .fn { margin-top: 8px; font-size: 10.5px; color: #475569; line-height: 1.45; }
        .key { margin-top: 8px; border-top: 1px solid #CBD5E1; padding-top: 6px; font-size: 9.5px; color: #475569; line-height: 1.5; }
        .flag { position: absolute; right: 52px; top: 14px; z-index: 2; font-size: 9px; letter-spacing: 1.6px; color: #92400E; background: #FFFBEB; border: 1px solid #FDE68A; padding: 3px 9px; font-weight: 700; }
        .compact .res td { padding: 5px 3px; font-size: 12px; }
        .compact .res th { padding: 6px 3px; font-size: 11px; }
        .compact .box { min-height: 72px; }
        .compact .sig { margin-top: 16px; }
        .compact .cmt { margin-top: 12px; }
        body.bw, body.bw .page { background: #fff; color: #000; }
        body.bw .bar, body.bw .rule, body.bw .mono { background: #000; color: #fff; }
        body.bw .doc, body.bw .school, body.bw .res td.g, body.bw .sum.k td.v, body.bw .val, body.bw .sum td.v { color: #000; }
        body.bw .res th { background: #000; color: #fff; border-left-color: #fff; }
        body.bw .res th.sub { background: #fff; color: #000; }
        body.bw .lrn, body.bw .res, body.bw .box { background: #fff; border-color: #000; }
        body.bw .res td, body.bw .sum td, body.bw .sum.k td { background: #fff; border-color: #000; color: #000; }
        body.bw .miss, body.bw .lbl, body.bw .fn, body.bw .key, body.bw .motto, body.bw .term, body.bw .box .t, body.bw .sl { color: #000; }
        body.bw .flag { color: #000; background: #fff; border-color: #000; }
    </style>
</head>
<body class="{{ ! empty($printBlackAndWhite) ? 'bw' : '' }}">
@php
    $sets = $setColumns ?? [];
    if ($sets === []) {
        $sets = array_merge($monthlySets ?? [], $termSets ?? []);
    }
    $roman = ['1' => 'I', '2' => 'II', '3' => 'III', '4' => 'IV', '5' => 'V', '6' => 'VI'];
    $termRaw = $learner->marks->first()?->exam?->academicTerm?->name ?? '';
    $termYear = $learner->marks->first()?->exam?->academicTerm?->academicYear?->name ?? '';
    if (preg_match('/^(.*?)\s*(\d+)\s*$/', trim((string) $termRaw), $m)) {
        $termName = trim($m[1]).' '.($roman[$m[2]] ?? $m[2]);
    } else {
        $termName = trim((string) $termRaw);
    }
    if ($termName !== '' && $termYear !== '') {
        $termName .= ' · '.$termYear;
    }
    $ordinal = function ($n) {
        $n = (int) $n;
        $j = $n % 100;
        if ($j >= 11 && $j <= 13) {
            return $n.'th';
        }

        return $n.(['1' => 'st', '2' => 'nd', '3' => 'rd'][$n % 10] ?? 'th');
    };
    $markText = function ($cell) use ($countMissingAsZero) {
        if ($cell && $cell->mark !== null) {
            return (string) (int) floor((float) $cell->mark);
        }
        if (\App\Services\Reports\MarkCellText::isCountedBlank($cell, ! empty($countMissingAsZero))) {
            return \App\Services\Reports\MarkCellText::word($cell);
        }

        return '-';
    };
    $gradeText = function ($cell) use ($grading_system, $countMissingAsZero) {
        $markValue = ($cell && $cell->mark !== null) ? $cell->mark : null;
        $row = null;
        if ($markValue !== null) {
            $row = $grading_system->first(fn ($gs) => $gs->min_score <= $markValue && $gs->max_score >= $markValue);
        } elseif (\App\Services\Reports\MarkCellText::isCountedBlank($cell, ! empty($countMissingAsZero))) {
            $row = $grading_system->first(fn ($gs) => (float) $gs->min_score <= 0 && (float) $gs->max_score >= 0);
        }

        return \App\Helpers\GradingHelper::formatAggLabel($row) ?? '-';
    };
    $shown = [];
    foreach ($subjects as $subject) {
        $showSubject = $learner->marks->where('subject_id', $subject->id)->isNotEmpty();
        if (! $showSubject) {
            foreach ($sets as $set) {
                if (\App\Services\Reports\MarkCellText::isCountedBlank($set['cells'][$subject->id] ?? null, ! empty($countMissingAsZero))) {
                    $showSubject = true;
                }
            }
        }
        if ($showSubject) {
            $shown[] = $subject;
        }
    }
    $needsBlankNote = false;
    foreach ($sets as $set) {
        foreach ($shown as $subject) {
            if (\App\Services\Reports\MarkCellText::isCountedBlank($set['cells'][$subject->id] ?? null, ! empty($countMissingAsZero))) {
                $needsBlankNote = true;
            }
        }
    }
    $zeroBand = $grading_system->first(fn ($gs) => (float) $gs->min_score <= 0 && (float) $gs->max_score >= 0);
    $zeroLabel = \App\Helpers\GradingHelper::formatAggLabel($zeroBand);
    $zeroPoints = $zeroBand ? \App\Helpers\GradingHelper::effectivePoints($zeroBand) : null;
    $blankSentence = '';
    if ($needsBlankNote) {
        $blankSentence = ($zeroLabel !== null && $zeroPoints !== null)
            ? 'Missing marks and ABS count as 0 (grade '.$zeroLabel.', '.$zeroPoints.' points). '
            : 'Missing marks and ABS count as 0. ';
    }
    $positionSentence = ($rankingBasis ?? '') === \App\Services\Results\ResultsPolicy::BASIS_TOTAL
        ? 'Position is by the total; learners with the same total share a place.'
        : 'Position is by aggregate; learners with the same aggregate share a place.';
    $gradeKey = [];
    foreach ($grading_system->sortByDesc(fn ($gs) => (float) $gs->max_score) as $band) {
        $label = \App\Helpers\GradingHelper::formatAggLabel($band);
        if ($label === null) {
            continue;
        }
        $gradeKey[] = $label.' '.(int) $band->min_score.'–'.(int) $band->max_score;
    }
    $divisionKey = [];
    foreach ($divisionRanges ?? [] as $range) {
        $min = $range['min'] ?? null;
        $max = $range['max'] ?? null;
        if ($min === null && $max !== null) {
            $divisionKey[] = $range['label'].' ('.$max.' or less)';
        } elseif ($max === null && $min !== null) {
            $divisionKey[] = $range['label'].' ('.$min.' and above)';
        } elseif ($min !== null && $max !== null) {
            $divisionKey[] = $range['label'].' ('.$min.'–'.$max.')';
        }
    }
    $monogram = '';
    foreach (preg_split('/\s+/', trim((string) ($schoolIdentity['name'] ?? ''))) ?: [] as $word) {
        $letter = mb_substr(trim((string) $word), 0, 1);
        if ($letter === '') {
            continue;
        }
        $monogram .= mb_strtoupper($letter);
        if (mb_strlen($monogram) >= 3) {
            break;
        }
    }
    $classLabel = (string) $class_name;
    if (! empty($streamName) && ! str_contains($classLabel, (string) $streamName)) {
        $classLabel .= ' · '.$streamName;
    }
    $pupils = (int) ($totalLearners ?? 0);
    foreach ($sets as $set) {
        if ((int) ($set['of'] ?? 0) > 0) {
            $pupils = (int) $set['of'];
            break;
        }
    }
    $compact = count($shown) >= 6 && count($sets) >= 3;
    $number = function ($value) {
        if ($value === null || $value === '') {
            return '-';
        }
        if (is_float($value) || is_int($value) || is_numeric($value)) {
            $number = (float) $value;

            return ((int) $number == $number) ? (string) (int) $number : (string) $number;
        }

        return (string) $value;
    };
@endphp
<div class="page {{ $compact ? 'compact' : '' }}">
    <div class="bar"></div>
    @if (! empty($sampleForApproval))
        <div class="flag">SAMPLE FOR APPROVAL</div>
    @endif
    <div class="in">
        <table class="hdr">
            <tr>
                <td style="width:78px">
                    @if (! empty($logoPath))
                        <img class="crest" src="{{ $logoPath }}" alt="">
                    @else
                        <div class="mono" data-kind="monogram">{{ $monogram }}</div>
                    @endif
                </td>
                <td>
                    <div class="school" data-kind="school">{{ $schoolIdentity['name'] ?? '' }}</div>
                    @if (! empty($schoolIdentity['motto']))
                        <div class="motto" data-kind="motto">{{ $schoolIdentity['motto'] }}</div>
                    @endif
                </td>
                <td style="width:210px">
                    <div class="doc">PROGRESSIVE REPORT</div>
                    @if ($termName !== '')
                        <div class="term">{{ $termName }}</div>
                    @endif
                </td>
            </tr>
        </table>
        <div class="rule"></div>
        <table class="lrn">
            <tr>
                <td style="width:250px">
                    <div class="lbl">Learner</div>
                    <div class="val big" data-kind="learner">{{ $learner->reportCardName() }}</div>
                </td>
                @if (! empty($learner->registration_number))
                    <td>
                        <div class="lbl">KLS number</div>
                        <div class="val" data-kind="kls">{{ $learner->registration_number }}</div>
                    </td>
                @endif
                <td>
                    <div class="lbl">Class</div>
                    <div class="val" data-kind="class">{{ $classLabel }}</div>
                </td>
                <td>
                    <div class="lbl">Pupils in class</div>
                    <div class="val" data-kind="pupils">{{ $pupils }}</div>
                </td>
            </tr>
        </table>
        <table class="res">
            <tr>
                <th class="s" rowspan="2" style="width:180px">Subject</th>
                @foreach ($sets as $set)
                    <th colspan="2">{{ $set['label'] }}</th>
                @endforeach
            </tr>
            <tr>
                @foreach ($sets as $set)
                    <th class="sub">Marks</th>
                    <th class="sub">Grade</th>
                @endforeach
            </tr>
            @foreach ($shown as $index => $subject)
                <tr class="{{ $index % 2 === 1 ? 'alt' : '' }}">
                    <td class="s">{{ $subject->name }}</td>
                    @foreach ($sets as $set)
                        @php
                            $cell = $set['cells'][$subject->id] ?? null;
                            $printedMark = $markText($cell);
                            $printedGrade = $gradeText($cell);
                        @endphp
                        <td class="m" data-kind="mark" data-set="{{ $set['label'] }}" data-subject="{{ $subject->id }}">@if ($printedMark === 'Missing')<span class="miss">Missing</span>@else{{ $printedMark }}@endif</td>
                        <td class="g" data-kind="grade" data-set="{{ $set['label'] }}" data-subject="{{ $subject->id }}">{{ $printedGrade }}</td>
                    @endforeach
                </tr>
            @endforeach
            <tr class="sum">
                <td class="s">TOTAL MARKS</td>
                @foreach ($sets as $set)
                    <td class="v" colspan="2" data-kind="total" data-set="{{ $set['label'] }}">{{ $number($set['total'] ?? null) }}</td>
                @endforeach
            </tr>
            @if (! empty($showAgg))
                <tr class="sum">
                    <td class="s">AGGREGATE</td>
                    @foreach ($sets as $set)
                        <td class="v" colspan="2" data-kind="aggregate" data-set="{{ $set['label'] }}">{{ $number($set['aggregate'] ?? null) }}</td>
                    @endforeach
                </tr>
            @endif
            <tr class="sum k">
                <td class="s">DIVISION</td>
                @foreach ($sets as $set)
                    <td class="v" colspan="2" data-kind="division" data-set="{{ $set['label'] }}">{{ $set['division'] ?? '-' }}</td>
                @endforeach
            </tr>
            <tr class="sum k">
                <td class="s">POSITION</td>
                @foreach ($sets as $set)
                    <td class="v" colspan="2" data-kind="position" data-set="{{ $set['label'] }}">{{ empty($set['pos']) ? '-' : $ordinal($set['pos']).((int) ($set['of'] ?? 0) > 0 ? ' of '.(int) $set['of'] : '') }}</td>
                @endforeach
            </tr>
        </table>
        @if (count($termSets ?? []) > 1)
            <div class="fn" data-kind="term-total">Term total {{ $number($total ?? null) }}@if (! empty($showAgg)) · Aggregate {{ $number($eotAgg ?? null) }}@endif @if (! empty($showDivision)) · Division {{ $eotDivision }}@endif</div>
        @endif
        <table class="cmt">
            <tr>
                <td style="padding-right:8px">
                    <div class="box">
                        <div class="t">Class teacher's comment</div>
                        @if (trim((string) ($teacherComment ?? '')) !== '')
                            <div class="comment" data-kind="teacher-comment">{{ $teacherComment }}</div>
                        @else
                            <div class="ln"></div><div class="ln"></div><div class="ln"></div>
                        @endif
                    </div>
                </td>
                <td style="padding-left:8px">
                    <div class="box">
                        <div class="t">Head teacher's comment</div>
                        @if (trim((string) ($headTeacherComment ?? '')) !== '')
                            <div class="comment" data-kind="head-comment">{{ $headTeacherComment }}</div>
                        @else
                            <div class="ln"></div><div class="ln"></div><div class="ln"></div>
                        @endif
                    </div>
                </td>
            </tr>
        </table>
        <table class="sig">
            <tr>
                <td><div class="sl">Class teacher</div></td>
                <td><div class="sl">Head teacher</div></td>
                <td><div class="sl">Parent or guardian</div></td>
            </tr>
        </table>
        <div class="foot">
            <div class="next">
                @if (! empty($nextTerm))
                    Next term begins on {{ $nextTerm->starts_on->format('d/m/Y') }}
                @else
                    Next term begins: ______________________
                @endif
            </div>
            <div class="fn">{{ $blankSentence }}Aggregate is the sum of the grade points. {{ $positionSentence }}</div>
            @if (! empty($sampleForApproval) && ($gradeKey !== [] || $divisionKey !== []))
                <div class="key" data-kind="grading-key">Grades: {{ implode(' · ', $gradeKey) }}. Division by aggregate: {{ implode(' · ', $divisionKey) }}.</div>
            @endif
        </div>
    </div>
</div>
</body>
</html>
