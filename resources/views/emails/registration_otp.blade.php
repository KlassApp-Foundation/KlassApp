<x-mail::message :preheader="'It expires in 5 minutes.'">
# Verify your KlassApp account

Hello {{ $name ?? 'there' }},

Enter this code to finish creating your account:

<x-mail::code>{{ $otp }}</x-mail::code>

It expires in 5 minutes.

<span class="muted">Didn't ask for this code? You can ignore this email. Nobody can sign in without it.</span>
</x-mail::message>
