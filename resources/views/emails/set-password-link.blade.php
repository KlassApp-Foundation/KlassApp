<x-mail::message :preheader="'This link expires in '.$minutes.' minutes.'">
{{-- FLAGGED(product-decision): school creation emails a set-password link after storing a password hash. see docs/onboarding-engine-plan.md --}}
# Set your password

Hi {{ $name }},

Choose a password with the link below. It expires in {{ $minutes }} minutes ({{ $expiresAt->format('j M Y, g:i A') }}).

<x-mail::button :url="$url">Set your password</x-mail::button>

<span class="muted">If you were not expecting this, you can ignore this email. Your password stays unchanged until you use the link.</span>

<x-slot:subcopy>
Button not working? Paste this link into your browser: <span class="break-all">[{{ $url }}]({{ $url }})</span>
</x-slot:subcopy>
</x-mail::message>
