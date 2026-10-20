<?php

/**
 * School admin → Connected tools.
 *
 * Every product the page lists lives here, in display order. A card stays
 * "Coming soon" until something can report this person's status:
 *
 * 1. A later pull request calls ConnectedToolsRegistry::register() with a
 *    ConnectedTool whose key() matches one of these keys. That provider
 *    replaces Coming soon (and replaces the Google catalog fallback below).
 * 2. Or `provider` names a ConnectedTool class that exists on this branch.
 * 3. Or the key is already in config/google.php connectors. Sheets, Drive,
 *    and Classroom are, so their connect and disconnect routes are real and
 *    the card reads that user's own Google connection. Calendar, Gmail, Docs, Slides,
 *    and Tasks are not in that catalog on this branch, so they stay Coming soon.
 */
return [
    'tools' => [
        'whatsapp' => [
            'label' => 'WhatsApp',
            'summary' => 'Send school updates to parents on WhatsApp.',
            'icon' => 'images/brand/connectors/whatsapp.svg',
            'provider' => \App\Services\ConnectedTools\WhatsAppConnectedTool::class,
        ],
        'sheets' => [
            'label' => 'Google Sheets',
            'summary' => 'Create a class mark sheet and read marks back from that sheet.',
            'icon' => 'images/brand/connectors/google-sheets.png',
            'provider' => null,
        ],
        'drive' => [
            'label' => 'Google Drive',
            'summary' => 'File report-card PDFs this app creates into a folder per term and class.',
            'icon' => 'images/brand/connectors/google-drive.png',
            'provider' => null,
        ],
        'classroom' => [
            'label' => 'Google Classroom',
            'summary' => 'Read classes, students, and coursework.',
            'icon' => 'images/brand/connectors/google-classroom.png',
            'provider' => null,
        ],
        'calendar' => [
            'label' => 'Google Calendar',
            'summary' => 'Publish term dates and school events on a calendar this app creates in your Google account.',
            'icon' => 'images/brand/connectors/google-calendar.png',
            'provider' => null,
        ],
        'gmail' => [
            'label' => 'Gmail',
            'summary' => 'Send school email from a connected Gmail account.',
            'icon' => 'images/brand/connectors/gmail.png',
            'provider' => null,
        ],
        'docs' => [
            'label' => 'Google Docs',
            'summary' => 'Create and share school documents.',
            'icon' => 'images/brand/connectors/google-docs.png',
            'provider' => null,
        ],
        'slides' => [
            'label' => 'Google Slides',
            'summary' => 'Create and share school presentations.',
            'icon' => 'images/brand/connectors/google-slides.png',
            'provider' => null,
        ],
        'tasks' => [
            'label' => 'Google Tasks',
            'summary' => 'Keep school follow-ups on a shared task list.',
            'icon' => 'images/brand/connectors/google-tasks.png',
            'provider' => null,
        ],
        'slack' => [
            'label' => 'Slack',
            'summary' => 'Read channels and post messages after a person approves each post.',
            'icon' => 'images/brand/connectors/slack.svg',
            'provider' => \App\Services\ConnectedTools\SlackConnectedTool::class,
        ],
    ],
];
