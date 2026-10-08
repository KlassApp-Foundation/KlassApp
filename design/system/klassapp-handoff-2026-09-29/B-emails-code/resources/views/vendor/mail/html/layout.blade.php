{{-- KlassApp email shell. 600px table layout, system fonts, hex-only colours, dark-mode safe. --}}
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="x-apple-disable-message-reformatting">
<meta name="color-scheme" content="light dark">
<meta name="supported-color-schemes" content="light dark">
<title>{{ $subject ?? config('app.name') }}</title>
<!--[if mso]><noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript><![endif]-->
<style>
:root{color-scheme:light dark;supported-color-schemes:light dark}
@media only screen and (max-width:480px){
.outer{padding:16px 8px!important}
.header,.content-cell{padding-left:20px!important;padding-right:20px!important}
.action,.action table{width:100%!important}
.button{display:block!important;text-align:center!important}
h1{font-size:20px!important;line-height:26px!important}
}
@media (prefers-color-scheme:dark){
body,.wrapper{background-color:#0F172A!important}
.card{background-color:#1E293B!important;border-color:#334155!important}
h1,h2,h3,strong{color:#F8FAFC!important}
p,li,td{color:#E2E8F0!important}
.footer p,.subcopy p,.muted{color:#CBD5E1!important}
a{color:#86EFAC!important}
a.button{color:#FFFFFF!important}
.subcopy,.table th,.table td{border-color:#334155!important}
.code-cell{background-color:#052E16!important;border-color:#166534!important;color:#BBF7D0!important}
.logo-plate{background-color:#FFFFFF!important;padding:8px 10px!important}
}
[data-ogsc] h1,[data-ogsc] h2,[data-ogsc] strong{color:#F8FAFC!important}
[data-ogsc] p,[data-ogsc] td{color:#E2E8F0!important}
[data-ogsc] a{color:#86EFAC!important}
</style>
</head>
<body>
@isset($preheader)
<div style="display:none;max-height:0;overflow:hidden;mso-hide:all;font-size:1px;line-height:1px;color:#F3F0E8">{{ $preheader }}&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;</div>
@endisset
<table class="wrapper" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#F3F0E8">
<tr><td class="outer" align="center">
<!--[if mso]><table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->
<table class="content" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
<tr><td>
<table class="card" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#FFFFFF">
{{ $header ?? '' }}
<tr><td class="content-cell">
{{ Illuminate\Mail\Markdown::parse($slot) }}
{{ $subcopy ?? '' }}
</td></tr>
</table>
</td></tr>
{{ $footer ?? '' }}
</table>
<!--[if mso]></td></tr></table><![endif]-->
</td></tr>
</table>
</body>
</html>
