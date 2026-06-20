@component('mail::message')
# You've been promoted at {{ $schoolName }}

You are now a **Co-Admin** for **{{ $schoolName }}** on KlassApp.

Your existing login credentials still work. Log in with your current email and password.

@component('mail::button', ['url' => url('/login'), 'color' => 'green'])
Log in to KlassApp
@endcomponent

As a Co-Admin, you now have full admin access to manage the school.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
