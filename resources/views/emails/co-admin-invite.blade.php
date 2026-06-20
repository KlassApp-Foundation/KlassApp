@component('mail::message')
# Welcome to {{ $schoolName }}

You've been added as a **Co-Admin** for **{{ $schoolName }}** on KlassApp.

Your login credentials:

**Email:** {{ $email }}
**Password:** {{ $password }}

@component('mail::button', ['url' => url('/login'), 'color' => 'green'])
Log in to KlassApp
@endcomponent

After logging in, you'll have full admin access to manage the school.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
