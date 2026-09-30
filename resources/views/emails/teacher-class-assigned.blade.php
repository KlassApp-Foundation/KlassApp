<x-mail::message :preheader="'Sign in with your usual email and password.'">
# You're assigned to a class

@if($className)
You're now the **class teacher** for **{{ $className }}** at **{{ $schoolName }}**.
@else
You've been added as a teacher at **{{ $schoolName }}**.
@endif

Sign in with your usual email and password. Your class appears on your dashboard.

<x-mail::button :url="$inviteUrl">Sign in to KlassApp</x-mail::button>

<x-slot:subcopy>
Button not working? Paste this link into your browser: <span class="break-all">[{{ $inviteUrl }}]({{ $inviteUrl }})</span>
</x-slot:subcopy>
</x-mail::message>
