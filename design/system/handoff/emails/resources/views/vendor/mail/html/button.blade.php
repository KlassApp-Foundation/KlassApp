{{-- 48px tall (14 + 20 + 14). VML roundrect for Outlook desktop. $color is accepted but every variant resolves to #15803D except red. --}}
@php($bg = in_array($color ?? 'primary', ['red','error']) ? '#B91C1C' : '#15803D')
<table class="action" role="presentation" cellpadding="0" cellspacing="0" border="0">
<tr><td align="center" bgcolor="{{ $bg }}" style="border-radius:8px">
<!--[if mso]><v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" href="{{ $url }}" style="height:48px;v-text-anchor:middle;width:260px" arcsize="17%" stroke="f" fillcolor="{{ $bg }}"><w:anchorlock/><center style="color:#FFFFFF;font-family:Arial,sans-serif;font-size:16px;font-weight:bold">{{ $slot }}</center></v:roundrect><![endif]-->
<!--[if !mso]><!--><a href="{{ $url }}" class="button button-{{ $color ?? 'primary' }}" target="_blank" rel="noopener">{{ $slot }}</a><!--<![endif]-->
</td></tr>
</table>
