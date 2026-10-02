<x-mail::message :preheader="'It expires in 5 minutes.'">
# Reset your password

Hi {{ $name }},

Enter this code on the reset page to choose a new password:

<x-mail::code>{{ $code }}</x-mail::code>

It expires in 5 minutes.

<span class="muted">Didn't ask to reset your password? Ignore this email. Your password stays the same.</span>
</x-mail::message>
