@php($hours = (int) config('invites.expiry_hours', 72))
@php($preheader = isset($expiresInMinutes) ? 'Set your password to get started. The link expires in '.$expiresInMinutes.' minutes.' : (isset($expiresAt) ? 'Set your password to get started. The link expires in '.$hours.' hours.' : 'Set your password to get started.'))
<x-mail::message :preheader="$preheader">
# Set your password

@if($className)
You've been invited to be the **class teacher** for **{{ $className }}** at **{{ $schoolName }}**.
@else
You've been invited to join **{{ $schoolName }}** as a teacher.
@endif

Set a password to activate your account.

<x-mail::button :url="$inviteUrl">Set your password</x-mail::button>

@if(!empty($expiresInMinutes) && isset($expiresAt))
<span class="muted">This link expires in **{{ $expiresInMinutes }} minutes** ({{ $expiresAt->format('j M Y, g:i A') }}).</span>
@elseif(isset($expiresAt))
<span class="muted">This link works once and expires in **{{ $hours }} hours** ({{ $expiresAt->format('j M Y, g:i A') }}).</span>
@endif

<x-slot:subcopy>
Button not working? Paste this link into your browser: <span class="break-all">[{{ $inviteUrl }}]({{ $inviteUrl }})</span>

Not expecting this? Ignore this email. @if(isset($expiresAt) && empty($expiresInMinutes))No account is created unless you set a password.@else Nobody can sign in without a password you set.@endif
</x-slot:subcopy>
</x-mail::message>
