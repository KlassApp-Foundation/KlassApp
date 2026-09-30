<x-mail::message :preheader="'Enter it on the sign-up page, or tap Confirm email.'">
# Confirm your email

Enter this code on the KlassApp sign-up page:

<x-mail::code>{{ $spacedCode }}</x-mail::code>

@if($confirmUrl)
Or confirm with one tap:

<x-mail::button :url="$confirmUrl">Confirm email</x-mail::button>

@endif
The code{{ $confirmUrl ? ' and the button work' : ' works' }} for **{{ $minutes }} minutes** and can be used once.@if($confirmUrl) If you open this on another phone or computer, you'll still need to continue on the device where you signed up.@endif

<x-slot:subcopy>
Didn't sign up for KlassApp? You can ignore this email; no one can use this account until the email is confirmed.
@if($confirmUrl)

Button not working? Copy this link: <span class="break-all">[{{ $confirmUrl }}]({{ $confirmUrl }})</span>
@endif
</x-slot:subcopy>
</x-mail::message>
