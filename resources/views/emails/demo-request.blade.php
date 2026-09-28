@component('mail::message')
# New demo request

**School:** {{ $demoRequest->school_name }}

**Contact:** {{ $demoRequest->contact_name }}

**Email:** {{ $demoRequest->email }}

**Phone:** {{ $demoRequest->phone }}

@if($demoRequest->district)
**District:** {{ $demoRequest->district }}

@endif
@if($demoRequest->message)
**Message:**

{{ $demoRequest->message }}

@endif
Reply directly to this email to reach {{ $demoRequest->contact_name }} ({{ $demoRequest->email }}).

Thanks,<br>
{{ config('app.name') }}
@endcomponent
