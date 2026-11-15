@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4" style="background:#f8fafc;color:#0f172a;min-width:0;max-width:100%;">
    @include('layouts.partials.page-header', [
        'title' => 'Link '.$section->name.' to a class',
        'subtitle' => 'A section must be linked to a class before it can hold learners and appear on class screens.',
    ])
    @include('partials.message')

    @if (! $year)
        <p style="color:#b91c1c;" data-testid="link-section-no-year">This school has no current academic year. Set one first, then come back.</p>
    @else
        <form method="GET" action="{{ route('admin.classes.link-section', $section->id) }}" style="margin:12px 0;" data-testid="link-section-pick">
            <label for="link-standard" style="display:block;color:#0f172a;">Class</label>
            <select id="link-standard" name="standard" class="ds-input" style="width:100%;max-width:280px;min-height:44px;background:#fff;color:#0f172a;">
                <option value="">Choose a class</option>
                @foreach ($standards as $row)
                    <option value="{{ $row->id }}" @selected($standard && (int) $standard->id === (int) $row->id)>{{ $row->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="ds-btn" style="min-height:44px;" data-testid="link-section-choose">Continue</button>
        </form>

        @if ($standard && $plan)
            <form method="POST" action="{{ route('admin.classes.link-section.store', $section->id) }}" data-testid="link-section-confirm" style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px;">
                @csrf
                <input type="hidden" name="standard_id" value="{{ $standard->id }}">
                @if ($plan['result'] === \App\Services\Setup\SectionClassLinker::ALREADY_LINKED)
                    <p data-testid="link-section-already">{{ $section->name }} is already linked to a class for {{ $year->name }}. Nothing needs doing.</p>
                @else
                    <p data-testid="link-section-sentence">Link <strong>{{ $section->name }}</strong> to <strong>{{ $standard->name }}</strong> for <strong>{{ $year->name }}</strong>? Learners can then be added to it.</p>
                    <label style="display:block;margin:0 0 8px;"><input type="checkbox" name="confirm" value="1" data-testid="link-section-confirm-box"> Yes, link this section</label>
                    <button type="submit" class="ds-btn ds-btn-primary" style="min-height:44px;" data-testid="link-section-submit">Link section to class</button>
                @endif
                <a href="{{ route('admin.classes.index') }}" class="ds-btn" style="min-height:44px;display:inline-flex;align-items:center;">Cancel</a>
            </form>
        @endif
    @endif
</div>
@endsection
