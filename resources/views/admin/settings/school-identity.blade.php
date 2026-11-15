@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4" style="background:#f8fafc;color:#0f172a;min-width:0;max-width:100%;">
    @include('layouts.partials.page-header', [
        'title' => 'School details for report cards',
        'subtitle' => 'The crest and the head teacher\'s name that are printed on report cards.',
    ])
    @include('partials.message')

    <form method="POST" action="{{ route('admin.school-identity.update') }}" enctype="multipart/form-data" data-testid="school-identity" style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px;max-width:560px;min-width:0;">
        @csrf
        <label for="head-teacher-name" style="display:block;color:#0f172a;font-weight:600;">Head teacher's name</label>
        <input id="head-teacher-name" name="head_teacher_name" value="{{ old('head_teacher_name', $headTeacherName) }}" maxlength="120" class="ds-input" style="width:100%;min-height:44px;background:#fff;color:#0f172a;" data-testid="head-teacher-name" autocomplete="off">
        @error('head_teacher_name')<p style="color:#b91c1c;">{{ $message }}</p>@enderror
        <p style="color:#475569;font-size:.85rem;margin:4px 0 16px;">Printed under the head teacher's comment on each report card. Leave empty to print a blank line.</p>

        <label for="crest" style="display:block;color:#0f172a;font-weight:600;">Crest (a PNG or JPG, up to 2 MB)</label>
        <p data-testid="crest-status" style="margin:4px 0 8px;color:#0f172a;">
            @if ($crestFound)
                A crest is saved and will be printed on report cards.
            @elseif ($hasStoredCrest)
                A crest was saved, but the file can no longer be found (uploaded files can be lost when the app is updated). Report cards show the school's initials until you upload it again.
            @else
                No crest yet. Report cards show the school's initials until you upload one.
            @endif
        </p>
        <input id="crest" type="file" name="crest" accept="image/png,image/jpeg" style="width:100%;min-height:44px;color:#0f172a;" data-testid="crest-file">
        @error('crest')<p style="color:#b91c1c;" data-testid="crest-error">{{ $message }}</p>@enderror

        <button type="submit" class="ds-btn ds-btn-primary" style="margin-top:16px;min-height:44px;" data-testid="school-identity-save">Save school details</button>
    </form>
</div>
@endsection
