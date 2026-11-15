{{-- Per-school report type. Admins and head teachers both use this form. --}}
<section data-testid="report-kind" style="background:#ffffff;color:#0f172a;border:1px solid #e2e8f0;border-radius:12px;padding:16px;margin:16px 0;">
    <h2 style="margin:0 0 4px;font-size:1.05rem;color:#0f172a;">Report type</h2>
    <p style="margin:0 0 12px;color:#475569;font-size:.9rem;">
        Each school uses one. A change applies from the next release. A report that has already been released stays as it was.
    </p>
    <form method="POST" action="{{ route('admin.reports.cards.report-kind') }}">
        @csrf
        @foreach (\App\Services\Reports\ReportKindSetting::OPTIONS as $value => $option)
            <label style="display:block;margin:0 0 12px;color:#0f172a;cursor:pointer;">
                <input type="radio" name="report_kind" value="{{ $value }}"
                       @checked(old('report_kind', $reportKind ?? 'progressive') === $value)
                       data-testid="report-kind-{{ $value }}">
                <strong>{{ $option['label'] }}</strong>
                <span style="display:block;margin:2px 0 0 1.4rem;color:#334155;font-size:.9rem;">{{ $option['description'] }}</span>
            </label>
        @endforeach
        @error('report_kind')
            <p style="color:#b91c1c;font-size:.85rem;">{{ $message }}</p>
        @enderror
        <button type="submit" class="ds-btn ds-btn-primary" data-testid="report-kind-save" style="min-height:44px;">Save report type</button>
    </form>
    <div data-testid="report-kind-history" style="margin-top:12px;color:#334155;font-size:.85rem;">
        @forelse ($reportKindChanges ?? [] as $change)
            <p style="margin:0 0 4px;">{{ $change->created_at?->timezone(config('app.timezone'))->format('d M Y H:i') }} — {{ $change->user->name ?? 'A staff member' }} changed {{ \App\Services\Reports\ReportKindSetting::OPTIONS[$change->from_kind]['label'] ?? $change->from_kind }} to {{ \App\Services\Reports\ReportKindSetting::OPTIONS[$change->to_kind]['label'] ?? $change->to_kind }}.</p>
        @empty
            <p style="margin:0;">No changes yet. This school uses the Progressive report.</p>
        @endforelse
    </div>
</section>
