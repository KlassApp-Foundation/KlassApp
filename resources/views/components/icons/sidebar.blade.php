<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
    <g class="icon-fill-targets" fill="currentColor" fill-opacity="var(--icon-fill-opacity, 0)" stroke="none">
@switch($name)
    @case('dashboard')
    {!! '    ' !!}<polyline points="3 9 12 3 21 9"/>
    @break
    @case('students')
    {!! '    ' !!}<circle cx="9" cy="7" r="3"/>
    @break
    @case('teachers')
    {!! '    ' !!}<circle cx="9" cy="7" r="3"/>
    @break
    @case('parents')
    {!! '    ' !!}<path d="M21 9l-2 2-1-1"/>
    @break
    @case('classes')
    {!! '    ' !!}<rect x="13" y="7" width="8" height="14" rx="1"/>
    @break
    @case('subjects')
    {!! '    ' !!}<rect x="10" y="2" width="4" height="18" rx="1"/>
    @break
    @case('attendance')
    {!! '    ' !!}<path d="M16.5 3.5a2.12 2.12 0 013 3"/>
    @break
    @case('exams')
    {!! '    ' !!}<rect x="8" y="13" width="8" height="2" rx="1"/>
    {!! '    ' !!}<rect x="8" y="17" width="8" height="2" rx="1"/>
    @break
    @case('grading')
    {!! '    ' !!}<rect x="11" y="4" width="2" height="16" rx="1"/>
    @break
    @case('fees')
    {!! '    ' !!}<path d="M12 1v22"/>
    @break
    @case('timetable')
    {!! '    ' !!}<circle cx="12" cy="14" r="2"/>
    @break
    @case('reports')
    {!! '    ' !!}<rect x="8" y="13" width="8" height="2" rx="1"/>
    {!! '    ' !!}<rect x="8" y="17" width="8" height="2" rx="1"/>
    @break
    @case('messages')
    {!! '    ' !!}<polyline points="3 5 12 11 21 5"/>
    @break
    @case('settings')
    {!! '    ' !!}<circle cx="12" cy="12" r="3"/>
    @break
    @case('library')
    {!! '    ' !!}<rect x="11" y="6" width="2" height="7" rx="1"/>
    @break
    @case('transport')
    {!! '    ' !!}<rect x="7" y="9" width="12" height="6" rx="1.5"/>
    @break
    @case('health')
    {!! '    ' !!}<polyline points="2 12 8 12 11 3 13 21 16 12 22 12"/>
    @break
    @case('accountant')
    {!! '    ' !!}<path d="M12 1v22"/>
    @break
    @case('calendar')
    {!! '    ' !!}<circle cx="12" cy="14" r="2"/>
    @break
    @case('tasks')
    {!! '    ' !!}<polyline points="9 11 12 14 22 4"/>
    @break
@endswitch
    </g>
@switch($name)
    @case('dashboard')
    <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
    <polyline points="9 22 9 12 15 12 15 22"/>
    @break
    @case('students')
    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
    <circle cx="9" cy="7" r="4"/>
    <path d="M23 21v-2a4 4 0 00-3-3.87"/>
    <path d="M16 3.13a4 4 0 010 7.75"/>
    @break
    @case('teachers')
    <path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/>
    <circle cx="9" cy="7" r="4"/>
    <path d="M22 21v-2a4 4 0 00-3-3.87"/>
    <path d="M16 3.13a4 4 0 010 7.75"/>
    @break
    @case('parents')
    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
    <circle cx="9" cy="7" r="4"/>
    <path d="M23 21v-2a4 4 0 00-3-3.87"/>
    <path d="M20 3.13a4 4 0 010 7.75"/>
    <path d="M21 9l-2 2-1-1"/>
    @break
    @case('classes')
    <path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/>
    <path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/>
    @break
    @case('subjects')
    <path d="M4 19.5A2.5 2.5 0 016.5 17H20"/>
    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
    @break
    @case('attendance')
    <path d="M12 20h9"/>
    <path d="M16.5 3.5a2.12 2.12 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/>
    @break
    @case('exams')
    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
    <polyline points="14 2 14 8 20 8"/>
    <line x1="16" y1="13" x2="8" y2="13"/>
    <line x1="16" y1="17" x2="8" y2="17"/>
    <polyline points="10 9 9 9 8 9"/>
    @break
    @case('grading')
    <path d="M18 20V10"/>
    <path d="M12 20V4"/>
    <path d="M6 20v-6"/>
    @break
    @case('fees')
    <line x1="12" y1="1" x2="12" y2="23"/>
    <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
    @break
    @case('timetable')
    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
    <line x1="16" y1="2" x2="16" y2="6"/>
    <line x1="8" y1="2" x2="8" y2="6"/>
    <line x1="3" y1="10" x2="21" y2="10"/>
    @break
    @case('reports')
    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
    <polyline points="14 2 14 8 20 8"/>
    <line x1="16" y1="13" x2="8" y2="13"/>
    <line x1="16" y1="17" x2="8" y2="17"/>
    @break
    @case('messages')
    <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
    @break
    @case('settings')
    <circle cx="12" cy="12" r="3"/>
    <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
    @break
    @case('library')
    <path d="M4 19.5A2.5 2.5 0 016.5 17H20"/>
    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
    <polyline points="12 6 12 13"/>
    <line x1="10" y1="8" x2="14" y2="8"/>
    @break
    @case('transport')
    <path d="M5 17h14M5 17a2 2 0 01-2-2V9a2 2 0 012-2h14a2 2 0 012 2v6a2 2 0 01-2 2"/>
    <path d="M5 17l-1 4h3l1-4"/>
    <path d="M19 17l1 4h-3l-1-4"/>
    <circle cx="8" cy="14" r="1.5"/>
    <circle cx="16" cy="14" r="1.5"/>
    @break
    @case('health')
    <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
    @break
    @case('accountant')
    <line x1="12" y1="1" x2="12" y2="23"/>
    <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
    <polyline points="18 9 19 10 21 8"/>
    @break
    @case('calendar')
    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
    <line x1="16" y1="2" x2="16" y2="6"/>
    <line x1="8" y1="2" x2="8" y2="6"/>
    <line x1="3" y1="10" x2="21" y2="10"/>
    <circle cx="12" cy="14" r="1"/>
    @break
    @case('tasks')
    <path d="M9 11l3 3L22 4"/>
    <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
    @break
    @default
    <circle cx="12" cy="12" r="10"/>
    <line x1="12" y1="16" x2="12" y2="12"/>
    <line x1="12" y1="8" x2="12.01" y2="8"/>
@endswitch
</svg>
