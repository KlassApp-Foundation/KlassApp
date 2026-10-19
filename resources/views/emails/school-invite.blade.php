@component('mail::message')
# You're invited to set up {{ $schoolName }} on KlassApp

Hi {{ $name }},

Your early-access request has been approved. Use the button below to create the school admin account for **{{ $schoolName }}**. The link is personal to this email address, works once, and expires on {{ $expiresAt->format('j M Y \a\t g:i A') }}.

@component('mail::button', ['url' => $inviteUrl])
Set up your school
@endcomponent

After you create the account, you can choose to set up step by step or by chatting with Toshi.

If you weren't expecting this, you can ignore it.

Thanks,<br>
KlassApp
@endcomponent
