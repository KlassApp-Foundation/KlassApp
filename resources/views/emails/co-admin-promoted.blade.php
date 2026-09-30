<x-mail::message :preheader="'Sign in with your usual email and password.'">
# You're now a Co-Admin

You now have full admin access to **{{ $schoolName }}** on KlassApp.

Sign in with your usual email and password. Nothing else changes.

<x-mail::button :url="url('/login')">Sign in to KlassApp</x-mail::button>

<x-slot:subcopy>
Button not working? Paste this link into your browser: <span class="break-all">[{{ url('/login') }}]({{ url('/login') }})</span>
</x-slot:subcopy>
</x-mail::message>
