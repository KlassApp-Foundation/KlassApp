<x-mail::message :preheader="'This link works once and expires on '.$expiresAt->format('j M Y').'">
# Set up your class

Hi {{ $name }},

Use the link below to set your password and finish setting up your class at {{ $schoolName }}. It works once and expires on {{ $expiresAt->format('j M Y, g:i A') }}.

<x-mail::button :url="$url">Set up my class</x-mail::button>

<span class="muted">If you were not expecting this, you can ignore this email. Your password stays unchanged until you use the link.</span>

<x-slot:subcopy>
Button not working? Paste this link into your browser: <span class="break-all">[{{ $url }}]({{ $url }})</span>
</x-slot:subcopy>
</x-mail::message>
