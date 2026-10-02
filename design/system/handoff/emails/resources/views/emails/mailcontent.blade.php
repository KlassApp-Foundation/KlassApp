{{-- Frames arbitrary DB bodies (mailtemplates). Leading whitespace is stripped so Markdown never turns the
     32-space-indented rows into code blocks, and the legacy inline #008CBA buttons are restyled in code. --}}
<x-mail::message :preheader="$preheader ?? null">
{!! \App\Support\MailContent::normalise($content) !!}
</x-mail::message>
