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
<script src="{{ asset('js/progressive-rules.js') }}?v={{ filemtime(public_path('js/progressive-rules.js')) }}" defer></script>
@endsection

@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4" style="background:#f8fafc;color:#0f172a;min-width:0;max-width:100%;">
    @include('layouts.partials.page-header', [
        'title' => 'Progressive report rules',
        'subtitle' => 'Grade bands and points, division ranges, and how a class is ranked, on one screen.',
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

    <p data-testid="rules-apply-note" style="margin:0 0 12px;padding:10px 12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;color:#14532d;font-size:.9rem;">
        Changes you save here apply from the next release. A report that has already been released keeps the rules it was released with and never changes.
    </p>

    <p data-testid="rules-last-changed" style="margin:0 0 12px;color:#475569;font-size:.9rem;">
        @if ($lastChange)
            Last changed by {{ $lastChange->user?->name ?? 'a former user' }} on {{ $lastChange->created_at->format('j M Y, H:i') }}@if ($lastChange->summary) ({{ $lastChange->summary }})@endif.
        @else
            Not changed yet. This screen is showing the app defaults.
        @endif
    </p>

    <form method="GET" action="{{ route('admin.reports.cards.progressive-rules') }}" data-testid="rules-presets" style="margin:0 0 12px;display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end;">
        @if ($standard)<input type="hidden" name="standard" value="{{ $standard->id }}">@endif
        <div style="min-width:0;flex:1 1 260px;">
            <label for="rules-preset" style="display:block;color:#0f172a;">Start from a preset</label>
            <select id="rules-preset" name="preset" class="ds-input" style="width:100%;background:#fff;color:#0f172a;min-height:44px;">
                @foreach ($presets as $key => $label)
                    <option value="{{ $key }}" @selected($preset === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="ds-btn" style="min-height:44px;" data-testid="rules-load-preset">Load preset</button>
    </form>
    @if ($presetLoaded)
        <p data-testid="rules-preset-note" style="margin:0 0 12px;color:#92400e;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:10px 12px;font-size:.9rem;">
            "{{ $presetLoaded }}" is filled in below but not saved yet. Check it, then press Save.
        </p>
    @endif

    <div data-testid="rules-live" style="display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));margin:0 0 12px;">
        <form method="GET" action="{{ route('admin.reports.cards.progressive-rules') }}" style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:12px;min-width:0;" data-testid="try-mark-form">
            @if ($standard)<input type="hidden" name="standard" value="{{ $standard->id }}">@endif
            @if ($presetLoaded)<input type="hidden" name="preset" value="{{ $preset }}">@endif
            <label for="try-mark" style="display:block;color:#0f172a;font-weight:600;">Try a mark</label>
            <p style="margin:2px 0 6px;color:#475569;font-size:.85rem;">Type a mark from 0 to 100 to see its grade and points.</p>
            <div style="display:flex;gap:8px;"><input id="try-mark" name="try_mark" type="number" min="0" max="100" inputmode="numeric" value="{{ $tryMark }}" class="ds-input" style="min-height:44px;width:7rem;background:#fff;color:#0f172a;" data-testid="try-mark"><button type="submit" class="ds-btn" style="min-height:44px;">Show</button></div>
            <p data-testid="try-mark-result" aria-live="polite" style="margin:8px 0 0;color:#0f172a;">
                @if ($tryMark !== null)
                    @if ($tryBand)
                        {{ $tryMark }} is grade <strong>{{ $tryBand['grade'] }}</strong>@if ($tryBand['points'] !== null), <strong>{{ $tryBand['points'] }}</strong> {{ $tryBand['points'] === 1 ? 'point' : 'points' }}@endif.
                    @else
                        {{ $tryMark }} is not in any band. Marks run from 0 to 100.
                    @endif
                @endif
            </p>
        </form>
        <form method="GET" action="{{ route('admin.reports.cards.progressive-rules') }}" style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:12px;min-width:0;" data-testid="try-aggregate-form">
            @if ($standard)<input type="hidden" name="standard" value="{{ $standard->id }}">@endif
            @if ($presetLoaded)<input type="hidden" name="preset" value="{{ $preset }}">@endif
            <label for="try-aggregate" style="display:block;color:#0f172a;font-weight:600;">Try an aggregate</label>
            <p style="margin:2px 0 6px;color:#475569;font-size:.85rem;">Type an aggregate to see the division it gets.</p>
            <div style="display:flex;gap:8px;"><input id="try-aggregate" name="try_aggregate" type="number" min="0" inputmode="numeric" value="{{ $tryAggregate }}" class="ds-input" style="min-height:44px;width:7rem;background:#fff;color:#0f172a;" data-testid="try-aggregate"><button type="submit" class="ds-btn" style="min-height:44px;">Show</button></div>
            <p data-testid="try-aggregate-result" aria-live="polite" style="margin:8px 0 0;color:#0f172a;">
                @if ($tryAggregate !== null)
                    @if ($tryDivision !== null)
                        Aggregate {{ $tryAggregate }} is division <strong>{{ $tryDivision }}</strong>.
                    @else
                        Aggregate {{ $tryAggregate }} is not in any division range.
                    @endif
                @endif
            </p>
        </form>
    </div>

    <form method="POST" action="{{ route('admin.reports.cards.progressive-rules.update') }}" data-testid="progressive-rules" style="background:#fff;color:#0f172a;border:1px solid #e2e8f0;border-radius:12px;padding:16px;max-width:100%;min-width:0;">
        @csrf
        @if ($standard)
            <input type="hidden" name="standard_id" value="{{ $standard->id }}">
        @endif
        @if ($presetLoaded)
            <input type="hidden" name="preset" value="{{ $preset }}">
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

    <form method="POST" action="{{ route('admin.reports.cards.progressive-rules.reset') }}" data-testid="rules-reset-form" style="margin-top:12px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px;">
        @csrf
        @if ($standard)<input type="hidden" name="standard_id" value="{{ $standard->id }}">@endif
        <h2 style="font-size:1rem;margin:0 0 8px;">Go back to the app defaults</h2>
        <p style="color:#475569;font-size:.9rem;margin:0 0 8px;">Puts the grade bands, the division ranges and the ranking {{ $standard ? 'for this class level' : 'for all classes' }} back to what the app starts with. This applies from the next release.</p>
        <label style="display:block;margin:0 0 8px;color:#0f172a;"><input type="checkbox" name="confirm" value="1" data-testid="rules-reset-confirm"> Yes, reset these rules to the app defaults</label>
        <button type="submit" class="ds-btn" style="min-height:44px;" data-testid="rules-reset">Reset to defaults</button>
    </form>
</div>
@endsection
