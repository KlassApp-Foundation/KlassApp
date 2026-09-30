@php($hours = (int) config('invites.expiry_hours', 72))
<x-mail::message :preheader="isset($expiresAt) ? 'Set your password to get started. The link expires in '.$hours.' hours.' : 'Set your password to get started.'">
# Set your password

@if($className)
You've been invited to be the **class teacher** for **{{ $className }}** at **{{ $schoolName }}**.
@else
You've been invited to join **{{ $schoolName }}** as a teacher.
@endif

Set a password to activate your account.

<x-mail::button :url="$inviteUrl">Set your password</x-mail::button>

@isset($expiresAt)
<span class="muted">This link works once and expires in **{{ $hours }} hours** ({{ $expiresAt->format('j M Y, g:i A') }}).</span>
@endisset

<x-slot:subcopy>
Button not working? Paste this link into your browser: <span class="break-all">[{{ $inviteUrl }}]({{ $inviteUrl }})</span>

Not expecting this? Ignore this email. @isset($expiresAt)No account is created unless you set a password.@else Nobody can sign in without a password you set.@endisset
</x-slot:subcopy>
</x-mail::message>
