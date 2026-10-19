<?php

/**
 * Per-connector Google scopes. Secrets stay in config/services.php (env only).
 * Each connector's consent requests only its own scopes, plus the email scope
 * so the school can see which Google account is connected.
 */
return [
    'email_scope' => 'https://www.googleapis.com/auth/userinfo.email',

    'connectors' => [
        'sheets' => [
            'label' => 'Google Sheets',
            'scopes' => [
                'https://www.googleapis.com/auth/spreadsheets',
            ],
            'why' => 'Create a class mark sheet and read marks back from that sheet.',
        ],
        'drive' => [
            'label' => 'Google Drive',
            'scopes' => [
                // Non-restricted: only files this app creates or the user opens with it.
                'https://www.googleapis.com/auth/drive.file',
            ],
            'why' => 'File report-card PDFs this app creates into a folder per term and class.',
        ],
        'classroom' => [
            'label' => 'Google Classroom',
            'scopes' => [
                'https://www.googleapis.com/auth/classroom.courses.readonly',
                'https://www.googleapis.com/auth/classroom.rosters.readonly',
                'https://www.googleapis.com/auth/classroom.coursework.students.readonly',
            ],
            'why' => 'Read classes, students, and coursework. No guardian or email scopes.',
        ],
        'calendar' => [
            'label' => 'Google Calendar',
            'scopes' => [
                // Secondary calendars this app creates, and events on those calendars only.
                'https://www.googleapis.com/auth/calendar.app.created',
            ],
            'why' => 'Publish this school\'s term dates and calendar events onto a KlassApp calendar.',
        ],
    ],
];
