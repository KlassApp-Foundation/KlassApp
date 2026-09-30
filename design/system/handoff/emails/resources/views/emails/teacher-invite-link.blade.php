<x-mail::message :preheader="'Set your password to get started. The link expires in 72 hours.'">
# Set your password

@if($className)
You've been invited to be the **class teacher** for **{{ $className }}** at **{{ $schoolName }}**.
@else
You've been invited to join **{{ $schoolName }}** as a teacher.
@endif

Set a password to activate your account.

<x-mail::button :url="$inviteUrl">Set your password</x-mail::button>

<span class="muted">This link works once and expires in **72 hours** ({{ $expiresAt->format('j M Y, g:i A') }}).</span>

<x-slot:subcopy>
Button not working? Paste this link into your browser: <span class="break-all">[{{ $inviteUrl }}]({{ $inviteUrl }})</span>

Not expecting this? Ignore this email. No account is created unless you set a password.
</x-slot:subcopy>
</x-mail::message>
