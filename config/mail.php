<?php

// env() returns an empty string when the variable is set but blank, and then
// ignores the default. A blank MAIL_FROM_ADDRESS (as in .env.example) must
// not leave outbound mail without a From header.
$configuredFrom = env('MAIL_FROM_ADDRESS');
$fromAddress = is_string($configuredFrom) && filter_var($configuredFrom, FILTER_VALIDATE_EMAIL)
    ? $configuredFrom
    : 'connect@klassapp.xyz';

$configuredName = env('MAIL_FROM_NAME');
$fromName = is_string($configuredName) && trim($configuredName) !== ''
    ? trim($configuredName)
    : 'KlassApp';

$configuredLogChannel = env('MAIL_LOG_CHANNEL');
$logChannel = is_string($configuredLogChannel) && trim($configuredLogChannel) !== ''
    ? trim($configuredLogChannel)
    : null;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    */

    'default' => env('MAIL_MAILER', env('MAIL_DRIVER', 'smtp')),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    */

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            'host' => env('MAIL_HOST', 'smtp.mailgun.org'),
            'port' => env('MAIL_PORT', 587),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'auth_mode' => null,
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'mailgun' => [
            'transport' => 'mailgun',
        ],

        'postmark' => [
            'transport' => 'postmark',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => $logChannel,
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    */

    'from' => [
        'address' => $fromAddress,
        'name' => $fromName,
        // True when MAIL_FROM_ADDRESS was missing or not a valid email.
        'used_default_address' => $fromAddress !== $configuredFrom,
    ],

    /*
    |--------------------------------------------------------------------------
    | Markdown Mail Settings
    |--------------------------------------------------------------------------
    */

    'markdown' => [
        'theme' => 'klassapp',

        'paths' => [
            resource_path('views/vendor/mail'),
        ],
    ],

];
