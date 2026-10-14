{{--
  SPDX-License-Identifier: MIT

  <x-profile-photo> — the one frame for a user's profile photo.

  Props
    user   REQUIRED (may be null). Null renders the default image with alt="".
    size   xs 32 | sm 40 | md 64 | lg 128 | xl 192 (px). xs exists only for the
           32px nav trigger in layouts/partials/profile-dropdown. lg/xl shrink
           (still square) in a narrower column; xs/sm/md never shrink.
    shape  square (default: 12px radius, 1px --d-border ring) | circle.
           circle is reserved for the nav trigger (decision 2026-09-27, option b);
           every other photo is square.

  Fallback test is the same one profile-dropdown always used:
  `userprofile->avatar != null`. Do NOT switch to `?->AvatarPath ?? default` —
  Userprofile::getAvatarPathAttribute() returns '' (not null) when there is no
  avatar (Common::getFilePath), so `??` would render `src=""`.

  Not for print/PDF templates (id-card, bus pass): they keep fixed inline px
  styles because the PDF renderer may not resolve CSS custom properties.

  Usage
    <x-profile-photo :user="$user" size="xl" class="mx-auto" />
    <x-profile-photo :user="Auth::user()" size="xs" shape="circle" />
--}}
@props([
    'user',
    'size' => 'md',
    'shape' => 'square',
    'initialsWhenEmpty' => false,
])

@php
    $px = ['xs' => 32, 'sm' => 40, 'md' => 64, 'lg' => 128, 'xl' => 192][$size] ?? 64;

    $profile = $user?->userprofile;
    $hasPhoto = $profile && $profile->avatar != null;
    $src = $hasPhoto
        ? url($profile->AvatarPath)
        : asset('uploads/user/avatar/default-user.jpg');
    $initials = '';
    if (! $hasPhoto && $initialsWhenEmpty && $user) {
        $parts = array_filter([
            trim((string) ($profile?->firstname ?? '')),
            trim((string) ($profile?->lastname ?? '')),
        ]);
        if ($parts === []) {
            $parts = preg_split('/\s+/', trim((string) $user->name)) ?: [];
        }
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }
        if ($initials === '') {
            $initials = 'KA';
        }
    }
    $swatches = ['#DCFCE7', '#DBEAFE', '#FEF3C7', '#FCE7F3'];
    $ink = ['#166534', '#1E40AF', '#92400E', '#9D174D'];
    $swatch = $swatches[((int) ($user->id ?? 0)) % 4];
    $swatchInk = $ink[((int) ($user->id ?? 0)) % 4];

    // Same display-name rule as the dropdown header; `name` is the login handle.
    $alt = $user
        ? (($profile && $profile->firstname) ? $user->FullName : $user->name)
        : '';

    // lg/xl sit in page columns and may shrink (staying square) when the column
    // is narrower; xs/sm/md sit in nav/menu rows and keep their exact size.
    $sizeStyle = in_array($size, ['lg', 'xl'], true)
        ? "width: {$px}px; max-width: 100%; height: auto;"
        : "width: {$px}px; height: {$px}px; max-width: none;";

    $shapeStyle = $shape === 'circle'
        ? 'border-radius: 50%; border: 2px solid var(--d-avatar-ring, rgba(34,197,94,0.3));'
        : 'border-radius: var(--d-radius-xl, 12px); box-shadow: 0 0 0 1px var(--d-border, #E2E8F0);';
@endphp

@if($initials !== '')
<span {{ $attributes->merge(['class' => 'ds-profile-photo account-initials']) }} aria-hidden="true"
      style="display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;{{ $sizeStyle }} aspect-ratio:1;box-sizing:border-box;{{ $shapeStyle }} background:{{ $swatch }};color:{{ $swatchInk }};font-weight:700;font-size:{{ (int) round($px * 0.4) }}px;">{{ $initials }}</span>
@else
<img src="{{ $src }}" alt="{{ $alt }}" width="{{ $px }}" height="{{ $px }}"
     {{ $attributes->merge(['class' => 'ds-profile-photo']) }}
     style="display: block; flex-shrink: 0; {{ $sizeStyle }} aspect-ratio: 1; object-fit: cover; box-sizing: border-box; {{ $shapeStyle }}">
@endif
