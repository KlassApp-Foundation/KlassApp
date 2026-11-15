@extends('layouts.admin.layout')

@section('outside-app')
<style>
    .rules-field { width: 100%; min-width: 0; box-sizing: border-box; background: #fff; color: #0f172a; }
    @media (max-width: 760px) {
        [data-testid="division-ranges-scroll"],
        [data-testid="grade-bands-scroll"] { overflow: visible; width: 100%; }
        [data-testid="division-ranges"] thead,
        [data-testid="grade-bands"] thead { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); }
        [data-testid="division-ranges"],
        [data-testid="grade-bands"],
        [data-testid="division-ranges"] tbody,
        [data-testid="grade-bands"] tbody,
        [data-testid="division-ranges"] tr,
        [data-testid="grade-bands"] tr { display: block; width: 100%; text-align: left; }
        [data-testid="division-ranges"] td,
        [data-testid="grade-bands"] td { display: block; width: 100%; text-align: left; padding: 8px 0; border: none; }
        [data-testid="division-ranges"] tr,
        [data-testid="grade-bands"] tr { padding: 8px 0; border-top: 1px solid #e2e8f0; }
        [data-testid="division-ranges"] td::before,
        [data-testid="grade-bands"] td::before { content: attr(data-label); display: block; margin-bottom: 4px; color: #475569; font-size: .75rem; text-align: left; }
        [data-testid="division-ranges"] .rules-field,
        [data-testid="grade-bands"] .rules-field { display: block; width: 100%; max-width: none; }
    }
</style>
@endsection

@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4" style="background:#f8fafc;color:#0f172a;min-width:0;max-width:100%;">
    @include('layouts.partials.page-header', [
        'title' => 'Progressive report rules',
        'subtitle' => 'Grade bands, division ranges and how a class is ranked. Empty choices keep the ranges and ranking the card already uses. A report already released is not recalculated.',
    ])
    @include('partials.message')

    <form method="GET" action="{{ route('admin.reports.cards.progressive-rules') }}" style="margin:12px 0;">
        <label for="rules-standard" style="color:#0f172a;">Class level</label>
        <select id="rules-standard" name="standard" class="ds-input" style="width:100%;max-width:240px;background:#fff;color:#0f172a;" onchange="this.form.submit()" data-testid="rules-standard">
            <option value="">All classes</option>
            @foreach ($standards as $row)
                <option value="{{ $row->id }}" @selected($standard && (int) $standard->id === (int) $row->id)>{{ $row->name }}</option>
            @endforeach
        </select>
    </form>

    <form method="POST" action="{{ route('admin.reports.cards.progressive-rules.update') }}" data-testid="progressive-rules" style="background:#fff;color:#0f172a;border:1px solid #e2e8f0;border-radius:12px;padding:16px;max-width:100%;min-width:0;">
        @csrf
        @if ($standard)
            <input type="hidden" name="standard_id" value="{{ $standard->id }}">
        @endif

        <h2 style="font-size:1rem;margin:0 0 8px;">How this class is ranked</h2>
        <p style="color:#475569;font-size:.9rem;margin:0 0 8px;">Ties share a place and the next place is skipped. Aggregate ranks the lowest total first. Total ranks the highest mark total first.</p>
        @foreach (['automatic' => 'Automatic — by aggregate when this class has grade points, otherwise by the mark total', 'aggregate' => 'By aggregate', 'total' => 'By total'] as $value => $label)
            <label style="display:block;margin:0 0 6px;color:#0f172a;">
                <input type="radio" name="ranking_basis" value="{{ $value }}" @checked(old('ranking_basis', $ranking) === $value) data-testid="ranking-{{ $value }}">
                {{ $label }}
            </label>
        @endforeach

        <h2 style="font-size:1rem;margin:16px 0 8px;">Division ranges</h2>
        <p style="color:#475569;font-size:.9rem;margin:0 0 8px;">Today, 12 or less is division 1, 13 to 24 is 2, 25 to 28 is 3, 29 to 32 is 4, and 33 or more is U. Leave automatic to keep that.</p>
        <label style="display:block;margin:0 0 8px;color:#0f172a;">
            <input type="checkbox" name="use_automatic_division" value="1" @checked(old('use_automatic_division', $usingAutomaticDivision)) data-testid="automatic-division">
            Use the automatic ranges
        </label>
        @error('ranges')<p style="color:#b91c1c;">{{ $message }}</p>@enderror
        <div data-testid="division-ranges-scroll" style="overflow-x:auto;max-width:100%;">
        <table style="width:100%;color:#0f172a;border-collapse:collapse;" data-testid="division-ranges">
            <thead><tr><th>Division</th><th>From aggregate</th><th>To aggregate</th></tr></thead>
            <tbody>
            @foreach ($ranges as $index => $range)
                <tr>
                    <td data-label="Division"><input name="ranges[{{ $index }}][label]" value="{{ $range['label'] }}" class="ds-input rules-field" placeholder="Division"></td>
                    <td data-label="From aggregate"><input name="ranges[{{ $index }}][min]" value="{{ $range['min'] }}" class="ds-input rules-field" placeholder="no lower limit"></td>
                    <td data-label="To aggregate"><input name="ranges[{{ $index }}][max]" value="{{ $range['max'] }}" class="ds-input rules-field" placeholder="no upper limit"></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>

        @if ($standard)
            <h2 style="font-size:1rem;margin:16px 0 8px;">Grade bands for {{ $standard->name }}</h2>
            <p style="color:#475569;font-size:.9rem;margin:0 0 8px;">
                {{ $bandsAreStored ? 'These are the bands saved for this class level.' : 'These are the usual bands for this class level. Nothing is saved until you save.' }}
            </p>
            @error('bands')<p style="color:#b91c1c;" data-testid="bands-error">{{ $message }}</p>@enderror
            <div data-testid="grade-bands-scroll" style="overflow-x:auto;max-width:100%;">
            <table style="width:100%;color:#0f172a;border-collapse:collapse;" data-testid="grade-bands">
                <thead><tr><th>Grade</th><th>Points</th><th>From</th><th>To</th><th>Remark</th></tr></thead>
                <tbody>
                @foreach ($bands as $index => $band)
                    <tr>
                        <td data-label="Grade"><input name="bands[{{ $index }}][grade]" value="{{ $band['grade'] }}" class="ds-input rules-field"></td>
                        <td data-label="Points"><input name="bands[{{ $index }}][points]" value="{{ $band['points'] }}" class="ds-input rules-field"></td>
                        <td data-label="From"><input name="bands[{{ $index }}][min_score]" value="{{ $band['min_score'] }}" class="ds-input rules-field"></td>
                        <td data-label="To"><input name="bands[{{ $index }}][max_score]" value="{{ $band['max_score'] }}" class="ds-input rules-field"></td>
                        <td data-label="Remark"><input name="bands[{{ $index }}][remark]" value="{{ $band['remark'] }}" class="ds-input rules-field"></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        @endif

        <button type="submit" class="ds-btn ds-btn-primary" style="margin-top:16px;min-height:44px;" data-testid="rules-save">Save progressive report rules</button>
    </form>
</div>
@endsection
