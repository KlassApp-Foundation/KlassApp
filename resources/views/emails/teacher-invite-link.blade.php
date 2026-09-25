@component('mail::message')
# You're invited!

@if($className)
You've been invited to be the **class teacher** for **{{ $className }}** at **{{ $schoolName }}** on KlassApp.
@else
You've been invited to join **{{ $schoolName }}** on KlassApp as a teacher.
@endif

Click the button below to set your own password and activate your account. This link is one-time use and expires **{{ $expiresAt->diffForHumans() }}** ({{ $expiresAt->format('j M Y, g:i A') }}).

@component('mail::button', ['url' => $inviteUrl, 'color' => 'green'])
Set Your Password
@endcomponent

If you did not expect this invitation, you can safely ignore this email — no account will be created unless you set a password.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
