@php($hours = (int) config('invites.expiry_hours', 72))
<x-mail::message :preheader="'Set your password to open your ' . $roleLabel . ' account. The link expires in ' . $hours . ' hours.'">
# Set up your account

Hello {{ $name }},

**{{ $schoolName }}** has added you as a {{ $roleLabel }} on KlassApp. Choose a password to open your account.

<x-mail::button :url="$inviteUrl">Set your password</x-mail::button>

<span class="muted">This link works once and expires in **{{ $hours }} hours** ({{ $expiresAt->format('j M Y, g:i A') }}).</span>

<x-slot:subcopy>
Button not working? Paste this link into your browser: <span class="break-all">[{{ $inviteUrl }}]({{ $inviteUrl }})</span>

Not expecting this? Ignore this email. Nobody can sign in without a password you set.
</x-slot:subcopy>
</x-mail::message>
