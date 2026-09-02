@component('mail::message')
# Welcome to {{ $schoolName }}

@if($className)
You've been added as the **class teacher** for **{{ $className }}** at **{{ $schoolName }}** on KlassApp.
@else
You've been added as a **teacher** at **{{ $schoolName }}** on KlassApp.
@endif

Your temporary login credentials:

**Email:** {{ $email }}
**Password:** {{ $password }}

@component('mail::button', ['url' => $loginUrl, 'color' => 'green'])
Log in to KlassApp
@endcomponent

After logging in, you'll be asked to set a new password.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
