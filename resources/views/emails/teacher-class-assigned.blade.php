<x-mail::message :preheader="'Sign in with your usual email and password.'">
# You're assigned to a class

@if($className)
@if(!empty($assignedBy))
**{{ $assignedBy }}** assigned you as the class teacher for **{{ $className }}** at **{{ $schoolName }}**.
@else
You're now the **class teacher** for **{{ $className }}** at **{{ $schoolName }}**.
@endif
@else
You've been added as a teacher at **{{ $schoolName }}**.
@endif

Sign in with your usual email and password. Your class appears on your dashboard.

If this is a mistake, contact {{ $assignedBy ?: 'your school admin' }} and ask them to change the class teacher on Classes and streams.

<x-mail::button :url="$inviteUrl">Sign in to KlassApp</x-mail::button>

<x-slot:subcopy>
Button not working? Paste this link into your browser: <span class="break-all">[{{ $inviteUrl }}]({{ $inviteUrl }})</span>
</x-slot:subcopy>
</x-mail::message>
