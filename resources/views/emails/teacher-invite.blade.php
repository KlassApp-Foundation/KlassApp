@component('mail::message')
# Welcome to {{ $schoolName }}

@if($className)
You've been added as the **class teacher** for **{{ $className }}** at **{{ $schoolName }}** on KlassApp.
@else
You've been added as a **teacher** at **{{ $schoolName }}** on KlassApp.
@endif

@if($password)
Your temporary login credentials:

**Email:** {{ $email }}
**Password:** {{ $password }}

@component('mail::button', ['url' => $loginUrl, 'color' => 'green'])
Log in to KlassApp
@endcomponent

After logging in, you'll be asked to set a new password.
@else
You already have a KlassApp account. The next time you log in, you'll see **{{ $className ?? 'your assigned class' }}** on your class-teacher dashboard.

@component('mail::button', ['url' => $loginUrl, 'color' => 'green'])
Log in to KlassApp
@endcomponent
@endif

Thanks,<br>
{{ config('app.name') }}
@endcomponent
